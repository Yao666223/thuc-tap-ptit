<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "<div class='container mt-4'><div class='alert alert-danger'>Bạn không có quyền thực hiện hành động này.</div></div>";
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>ID không hợp lệ.</div></div>";
    exit();
}

// Lấy tên sản phẩm
$stmt = $conn->prepare("SELECT ten_san_pham FROM san_pham WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Không tìm thấy sản phẩm.</div></div>";
    exit();
}
$row = $res->fetch_assoc();
$stmt->close();

$error = '';
$success = '';

// Nếu POST: thực hiện xóa (sau xác nhận)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra ràng buộc: có chi_tiet_nhap / chi_tiet_phieu_xuat liên quan không?
    $stmt1 = $conn->prepare("SELECT COUNT(*) AS c FROM chi_tiet_nhap WHERE id_san_pham = ?");
    $stmt1->bind_param("i", $id);
    $stmt1->execute();
    $c1 = $stmt1->get_result()->fetch_assoc()['c'];
    $stmt1->close();

    $stmt2 = $conn->prepare("SELECT COUNT(*) AS c FROM chi_tiet_phieu_xuat WHERE id_san_pham = ?");
    $stmt2->bind_param("i", $id);
    $stmt2->execute();
    $c2 = $stmt2->get_result()->fetch_assoc()['c'];
    $stmt2->close();

    if ($c1 > 0 || $c2 > 0) {
        $error = "Không thể xóa sản phẩm vì đã có giao dịch liên quan (phiếu nhập/phiếu xuất). Nếu muốn ẩn, hãy cân nhắc thêm cột 'deleted' (soft delete).";
    } else {
        // Thực hiện xóa
        $stmt3 = $conn->prepare("DELETE FROM san_pham WHERE id = ?");
        $stmt3->bind_param("i", $id);
        if ($stmt3->execute()) {
            $success = "Xóa sản phẩm thành công.";
        } else {
            $error = "Lỗi khi xóa: " . $stmt3->error;
        }
        $stmt3->close();
    }
}
?>

<div class="container mt-4">
  <h3 class="mb-4 text-danger">🗑️ Xóa sản phẩm</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <a href="sanpham.php" class="btn btn-outline-secondary">Về danh sách</a>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <a href="sanpham.php" class="btn btn-primary">Về danh sách</a>
  <?php else: ?>
    <div class="alert alert-warning">
      Bạn có chắc muốn xóa sản phẩm: <strong><?= htmlspecialchars($row['ten_san_pham']) ?></strong> ?
    </div>
    <form method="post">
      <button type="submit" class="btn btn-danger">Xác nhận xóa</button>
      <a href="sanpham.php" class="btn btn-outline-secondary ms-2">Hủy</a>
    </form>
  <?php endif; ?>
</div>
