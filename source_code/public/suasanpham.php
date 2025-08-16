<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "<div class='container mt-4'><div class='alert alert-danger'>Bạn không có quyền truy cập trang này.</div></div>";
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>ID sản phẩm không hợp lệ.</div></div>";
    exit();
}

$error = '';
$success = '';

// Lấy dữ liệu hiện tại
$stmt = $conn->prepare("SELECT * FROM san_pham WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Không tìm thấy sản phẩm.</div></div>";
    exit();
}
$product = $res->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = trim($_POST['ten_san_pham'] ?? '');
    $gia = $_POST['gia'] ?? '';
    $so_luong = $_POST['so_luong'] ?? '';
    $don_vi = trim($_POST['don_vi'] ?? '');

    if ($ten === '') $error = "Tên sản phẩm không được để trống.";
    elseif (!is_numeric($gia) || floatval($gia) < 0) $error = "Giá phải là số và >= 0.";
    elseif (!is_numeric($so_luong) || intval($so_luong) < 0) $error = "Số lượng phải là số nguyên >= 0.";
    else {
        // Có thể kiểm tra trùng tên với id khác
        $stmtC = $conn->prepare("SELECT id FROM san_pham WHERE ten_san_pham = ? AND id != ? LIMIT 1");
        $stmtC->bind_param("si", $ten, $id);
        $stmtC->execute();
        $rC = $stmtC->get_result();
        if ($rC->num_rows > 0) {
            $error = "Tên sản phẩm đã được sử dụng cho sản phẩm khác.";
            $stmtC->close();
        } else {
            $stmtC->close();
            // Cập nhật
            $stmt2 = $conn->prepare("UPDATE san_pham SET ten_san_pham = ?, gia = ?, so_luong = ?, don_vi = ? WHERE id = ?");
            $g = floatval($gia);
            $sl = intval($so_luong);
            $stmt2->bind_param("sdisi", $ten, $g, $sl, $don_vi, $id);
            if ($stmt2->execute()) {
                $success = "Cập nhật sản phẩm thành công.";
                // Cập nhật lại biến $product hiển thị form
                $product['ten_san_pham'] = $ten;
                $product['gia'] = $g;
                $product['so_luong'] = $sl;
                $product['don_vi'] = $don_vi;
            } else {
                $error = "Lỗi khi cập nhật: " . $stmt2->error;
            }
            $stmt2->close();
        }
    }
}
?>

<div class="container mt-4">
  <h3 class="mb-4 text-primary">✏️ Sửa sản phẩm</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="sanpham.php" class="btn btn-sm btn-outline-primary ms-2">Về danh sách</a></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Tên sản phẩm</label>
      <input type="text" name="ten_san_pham" class="form-control" required value="<?= htmlspecialchars($product['ten_san_pham']) ?>">
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Giá (VNĐ)</label>
        <input type="number" step="100" min="0" name="gia" class="form-control" required value="<?= htmlspecialchars($product['gia']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Số lượng</label>
        <input type="number" name="so_luong" min="0" class="form-control" required value="<?= htmlspecialchars($product['so_luong']) ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Đơn vị</label>
        <input type="text" name="don_vi" class="form-control" value="<?= htmlspecialchars($product['don_vi']) ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-success">Cập nhật</button>
    <a href="sanpham.php" class="btn btn-outline-secondary ms-2">Hủy</a>
  </form>
</div>

