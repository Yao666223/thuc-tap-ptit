<?php
// phieu_nhap_list.php
include __DIR__ . '/../includes/session_check.php';
include __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/layouts/admin_layout.php';

// Filters
$from = isset($_GET['from']) && $_GET['from'] !== '' ? $_GET['from'] : null;
$to   = isset($_GET['to']) && $_GET['to'] !== '' ? $_GET['to'] : null;
$q    = isset($_GET['q']) ? trim($_GET['q']) : '';

// Check if column created_by exists in phieu_nhap
$dbName = $conn->query("SELECT DATABASE()")->fetch_row()[0];
$colCheckStmt = $conn->prepare("
  SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'phieu_nhap' AND COLUMN_NAME = 'created_by'
");
$colCheckStmt->bind_param("s", $dbName);
$colCheckStmt->execute();
$colExists = $colCheckStmt->get_result()->fetch_row()[0] > 0;
$colCheckStmt->close();

// Build SELECT depending on column existence
if ($colExists) {
    $selectUser = "COALESCE(u.username,'system') AS created_by_name, ";
    $joinUser = "LEFT JOIN users u ON p.created_by = u.id ";
} else {
    $selectUser = "'system' AS created_by_name, ";
    $joinUser = "";
}

// Build SQL
$sql = "SELECT p.id, p.ngay_tao, p.ghi_chu, {$selectUser}
        GROUP_CONCAT(CONCAT(sp.ten_san_pham,' (', ct.so_luong, ')') SEPARATOR '; ') AS items
        FROM phieu_nhap p
        {$joinUser}
        JOIN chi_tiet_nhap ct ON ct.id_phieu_nhap = p.id
        JOIN san_pham sp ON sp.id = ct.id_san_pham
        WHERE 1=1 ";

$params = [];
$types = '';

// date filters
if ($from) { $sql .= " AND DATE(p.ngay_tao) >= ? "; $params[] = $from; $types .= 's'; }
if ($to)   { $sql .= " AND DATE(p.ngay_tao) <= ? "; $params[] = $to;   $types .= 's'; }

// keyword filter (search ghi_chu or product name or id)
if ($q !== '') {
    $sql .= " AND (p.ghi_chu LIKE ? OR sp.ten_san_pham LIKE ? OR p.id = ?) ";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = is_numeric($q) ? intval($q) : 0;
    $types .= 'ssi';
}

$sql .= " GROUP BY p.id ORDER BY p.ngay_tao DESC LIMIT 1000";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    // bind params (works with PHP 5.6+)
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}

// Export CSV if requested
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=phieu_nhap_list.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Ngày tạo','Người tạo','Ghi chú','Mục hàng']);
    if ($res) {
        $res->data_seek(0);
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [$row['id'], $row['ngay_tao'], $row['created_by_name'], $row['ghi_chu'], $row['items']]);
        }
    }
    fclose($out);
    exit();
}
?>

<div class="container mt-4">
  <h3 class="mb-3">📥 Lịch sử Phiếu Nhập</h3>

  <form class="row g-2 mb-3" method="get">
    <div class="col-auto"><input type="date" name="from" value="<?= htmlspecialchars($from ?? '') ?>" class="form-control"></div>
    <div class="col-auto"><input type="date" name="to"   value="<?= htmlspecialchars($to ?? '') ?>" class="form-control"></div>
    <div class="col-md-4"><input type="text" name="q" class="form-control" placeholder="Tìm theo ghi chú / tên SP / ID" value="<?= htmlspecialchars($q) ?>"></div>
    <div class="col-auto">
      <button class="btn btn-primary">Lọc</button>
      <a class="btn btn-outline-secondary" href="phieu_nhap_list.php">Reset</a>
      <a class="btn btn-success" href="<?= 'phieu_nhap_list.php?from=' . urlencode($from ?? '') . '&to=' . urlencode($to ?? '') . '&q=' . urlencode($q) . '&export=csv' ?>">Export CSV</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered">
      <thead class="table-dark">
        <tr><th>#</th><th>Ngày</th><th>Người tạo</th><th>Ghi chú</th><th>Hàng (tóm tắt)</th><th>Hành động</th></tr>
      </thead>
      <tbody>
      <?php if (!$res || $res->num_rows === 0): ?>
        <tr><td colspan="6" class="text-center">Không có phiếu nào.</td></tr>
      <?php else: while ($r = $res->fetch_assoc()): ?>
        <tr>
          <td><?= $r['id'] ?></td>
          <td><?= $r['ngay_tao'] ?></td>
          <td><?= htmlspecialchars($r['created_by_name']) ?></td>
          <td><?= htmlspecialchars($r['ghi_chu']) ?></td>
          <td class="text-start"><?= htmlspecialchars($r['items']) ?></td>
          <td><a href="phieu_nhap_detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-info">Chi tiết</a></td>
        </tr>
      <?php endwhile; endif; ?>
      </tbody>
    </table>
  </div>
</div>
