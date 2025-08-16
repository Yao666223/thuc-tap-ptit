<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';

// helper: bind params using call_user_func_array with references
function stmt_bind_params(mysqli_stmt $stmt, string $types, array $params) {
    if (empty($types)) return;
    $bind_names = [];
    $bind_names[] = $types;
    // create variables to hold values and push references
    foreach ($params as $i => $value) {
        // unique var name
        ${"param" . $i} = $value;
        $bind_names[] = &${"param" . $i};
    }
    return call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

// ----------------- get product id -----------------
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($product_id <= 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>ID sản phẩm không hợp lệ.</div></div>";
    exit();
}

// get product name
$stmtp = $conn->prepare("SELECT ten_san_pham FROM san_pham WHERE id = ? LIMIT 1");
$stmtp->bind_param("i", $product_id);
$stmtp->execute();
$prod = $stmtp->get_result()->fetch_assoc();
$stmtp->close();
if (!$prod) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Không tìm thấy sản phẩm.</div></div>";
    exit();
}

// filters
$from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : null;
$to   = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : null;
$per_page = 50;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// check if stock_changes table exists
$dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
$colCheck = $conn->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'stock_changes'");
$colCheck->bind_param("s", $dbName);
$colCheck->execute();
$exists = $colCheck->get_result()->fetch_row()[0] > 0;
$colCheck->close();

$rows = [];
$total_count = 0;

if ($exists) {
    // use stock_changes
    $sqlBase = "FROM stock_changes sc LEFT JOIN users u ON sc.user_id = u.id WHERE sc.san_pham_id = ?";
    $types = "i";
    $params = [$product_id];

    if ($from) { $sqlBase .= " AND DATE(sc.created_at) >= ?"; $types .= "s"; $params[] = $from; }
    if ($to)   { $sqlBase .= " AND DATE(sc.created_at) <= ?"; $types .= "s"; $params[] = $to; }

    // count
    $countSql = "SELECT COUNT(*) as cnt " . $sqlBase;
    $stmtc = $conn->prepare($countSql);
    if (!empty($params)) {
        stmt_bind_params($stmtc, $types, $params);
    }
    $stmtc->execute();
    $total_count = $stmtc->get_result()->fetch_row()[0];
    $stmtc->close();

    // get page
    $sql = "SELECT sc.id AS ref_id, sc.change_type AS type, sc.change_qty AS qty, NULL AS price, sc.created_at AS created_at, sc.note AS note, u.username
            " . $sqlBase . " ORDER BY sc.created_at DESC LIMIT ?, ?";
    // bind params + offset + per_page
    $types2 = $types . "ii";
    $params2 = array_merge($params, [$offset, $per_page]);

    $stmt = $conn->prepare($sql);
    stmt_bind_params($stmt, $types2, $params2);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();

} else {
    // fallback: union from chi_tiet_nhap and chi_tiet_phieu_xuat
    $sql_in = "SELECT p.id AS ref_id, 'nhap' AS type, ct.so_luong AS qty, ct.gia AS price, p.ngay_tao AS created_at, p.ghi_chu AS note, NULL AS username
               FROM chi_tiet_nhap ct JOIN phieu_nhap p ON ct.id_phieu_nhap = p.id
               WHERE ct.id_san_pham = ?";
    $sql_out= "SELECT p.id AS ref_id, 'xuat' AS type, -ct.so_luong AS qty, ct.gia AS price, p.ngay_tao AS created_at, p.ghi_chu AS note, NULL AS username
               FROM chi_tiet_phieu_xuat ct JOIN phieu_xuat p ON ct.id_phieu_xuat = p.id
               WHERE ct.id_san_pham = ?";

    $union = "SELECT * FROM ( ($sql_in) UNION ALL ($sql_out) ) t WHERE 1=1 ";
    $types = "i";
    $params = [$product_id];

    if ($from) { $union .= " AND DATE(created_at) >= ?"; $types .= "s"; $params[] = $from; }
    if ($to)   { $union .= " AND DATE(created_at) <= ?"; $types .= "s"; $params[] = $to; }

    // count
    $countSql = "SELECT COUNT(*) FROM (" . $union . ") tt";
    $stmtc = $conn->prepare($countSql);
    stmt_bind_params($stmtc, $types, $params);
    $stmtc->execute();
    $total_count = $stmtc->get_result()->fetch_row()[0];
    $stmtc->close();

    $union .= " ORDER BY created_at DESC LIMIT ?, ?";
    $params2 = array_merge($params, [$offset, $per_page]);
    $types2 = $types . "ii";

    $stmt = $conn->prepare($union);
    stmt_bind_params($stmt, $types2, $params2);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
}

// export CSV option
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=history_product_' . $product_id . '.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['Ref ID','Type','Qty','Price','Datetime','Note','User']);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['ref_id'] ?? '',
            $r['type'] ?? ($r['change_type'] ?? ''),
            $r['qty'] ?? ($r['change_qty'] ?? ''),
            $r['price'] ?? ($r['gia'] ?? 0),
            $r['created_at'] ?? ($r['ngay_tao'] ?? ''),
            $r['note'] ?? ($r['ghi_chu'] ?? ''),
            $r['username'] ?? ''
        ]);
    }
    fclose($out);
    exit();
}

// render page
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Lịch sử sản phẩm: <?= htmlspecialchars($prod['ten_san_pham']) ?> (ID <?= $product_id ?>)</h4>
    <div>
      <a href="product_history.php?product_id=<?= $product_id ?>&export=csv" class="btn btn-sm btn-success">Export CSV</a>
      <a href="sanpham.php" class="btn btn-sm btn-outline-secondary">Quay lại</a>
    </div>
  </div>

  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="product_id" value="<?= $product_id ?>">
    <div class="col-auto"><input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>" class="form-control"></div>
    <div class="col-auto"><input type="date" name="to"   value="<?= htmlspecialchars($to ?? '') ?>" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-primary">Lọc</button></div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-dark"><tr><th>#</th><th>Loại</th><th>Số lượng</th><th>Giá</th><th>Ngày</th><th>Ghi chú</th><th>Người</th></tr></thead>
      <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="text-center">Không có lịch sử.</td></tr>
      <?php else:
        foreach ($rows as $i => $r): ?>
        <tr>
          <td><?= $i + 1 + $offset ?></td>
          <td><?= htmlspecialchars($r['type'] ?? $r['change_type']) ?></td>
          <td class="text-center"><?= htmlspecialchars($r['qty'] ?? $r['change_qty']) ?></td>
          <td class="text-end"><?= number_format($r['price'] ?? $r['gia'] ?? 0) ?> đ</td>
          <td><?= htmlspecialchars($r['created_at'] ?? $r['ngay_tao']) ?></td>
          <td><?= htmlspecialchars($r['note'] ?? $r['ghi_chu']) ?></td>
          <td><?= htmlspecialchars($r['username'] ?? '') ?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

  <?php
    $total_pages = max(1, ceil($total_count / $per_page));
    if ($total_pages > 1):
  ?>
  <nav><ul class="pagination">
  <?php for ($p=1;$p<=$total_pages;$p++):
    $qs = http_build_query(array_merge($_GET, ['page'=>$p]));
  ?>
    <li class="page-item <?= $p==$page?'active':'' ?>"><a class="page-link" href="?<?= $qs ?>"><?= $p ?></a></li>
  <?php endfor; ?>
  </ul></nav>
  <?php endif; ?>

</div>
