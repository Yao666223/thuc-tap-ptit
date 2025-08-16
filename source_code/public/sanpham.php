<?php
// Hiển thị danh sách sản phẩm, tìm kiếm, phân trang, link Thêm/Sửa/Xóa

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';

// Quyền: admin mới có quyền xóa/sửa thêm (có thể chỉnh nếu muốn)
$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Tìm kiếm
$q = '';
if (isset($_GET['q'])) $q = trim($_GET['q']);

// Phân trang
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Tính tổng bản ghi (với filter)
$like = "%$q%";
$stmt_count = $conn->prepare("SELECT COUNT(*) FROM san_pham WHERE ten_san_pham LIKE ?");
$stmt_count->bind_param("s", $like);
$stmt_count->execute();
$total = $stmt_count->get_result()->fetch_row()[0];
$stmt_count->close();

$total_pages = max(1, ceil($total / $per_page));

// Lấy danh sách sản phẩm (with limit)
$stmt = $conn->prepare("SELECT id, ten_san_pham, gia, so_luong, don_vi FROM san_pham WHERE ten_san_pham LIKE ? ORDER BY id ASC LIMIT ?, ?");
$stmt->bind_param("sii", $like, $offset, $per_page);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0 text-primary">📦 Danh sách sản phẩm</h3>
    <?php if ($isAdmin): ?>
      <a href="themsanpham.php" class="btn btn-success">➕ Thêm sản phẩm</a>
    <?php endif; ?>
  </div>

  <!-- Search -->
  <form method="get" class="row g-2 mb-3">
    <div class="col-auto">
      <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Tìm theo tên sản phẩm">
    </div>
    <div class="col-auto">
      <button class="btn btn-secondary">Tìm</button>
      <a href="sanpham.php" class="btn btn-outline-secondary">Xóa</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle bg-white">
      <thead class="table-dark">
        <tr class="text-center">
          <th style="width:60px">ID</th>
          <th class="text-start">Tên sản phẩm</th>
          <th style="width:120px">Giá</th>
          <th style="width:100px">Tồn</th>
          <th style="width:120px">Đơn vị</th>
          <?php if ($isAdmin): ?><th style="width:160px">Hành động</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows === 0): ?>
          <tr><td colspan="<?= $isAdmin ? 6 : 5 ?>" class="text-center py-4">Không tìm thấy sản phẩm.</td></tr>
        <?php else: ?>
          <?php while ($r = $result->fetch_assoc()): ?>
            <tr>
              <td class="text-center"><?= $r['id'] ?></td>
              <td class="text-start"><?= htmlspecialchars($r['ten_san_pham']) ?></td>
              <td class="text-end"><?= number_format($r['gia'], 0, ',', '.') ?> đ</td>
              <td class="text-center"><?= intval($r['so_luong']) ?></td>
              <td class="text-center"><?= htmlspecialchars($r['don_vi']) ?></td>
              <?php if ($isAdmin): ?>
              <td class="text-center">
                <a href="suasanpham.php?id=<?= $r['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>
                <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $r['id'] ?>, '<?= addslashes(htmlspecialchars($r['ten_san_pham'])) ?>')">Xóa</button>
                <a href="product_history.php?product_id=<?= $r['id'] ?>" class="btn btn-info btn-sm">Lịch sử</a>
              </td>
              <?php endif; ?>
            </tr>
          <?php endwhile; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  <nav aria-label="Page navigation">
    <ul class="pagination">
      <?php
      $base = 'sanpham.php';
      $query_params = $_GET;
      for ($p = 1; $p <= $total_pages; $p++):
        $query_params['page'] = $p;
        $link = $base . '?' . http_build_query($query_params);
      ?>
      <li class="page-item <?= $p == $page ? 'active' : '' ?>"><a class="page-link" href="<?= $link ?>"><?= $p ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script>
function confirmDelete(id, name) {
  if (confirm("Bạn có chắc muốn xóa sản phẩm:\n" + name + " (ID: " + id + ")?\n\nLưu ý: nếu sản phẩm đã có giao dịch, thao tác sẽ bị từ chối.")) {
    window.location.href = "xoasanpham.php?id=" + encodeURIComponent(id);
  }
}
</script>

<?php
$stmt->close();
?>
