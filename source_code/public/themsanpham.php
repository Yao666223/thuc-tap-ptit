<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';

// Chỉ admin mới được thêm sản phẩm
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo "<div class='container mt-4'><div class='alert alert-danger'>Bạn không có quyền truy cập trang này.</div></div>";
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ten = trim($_POST['ten_san_pham'] ?? '');
    $gia = $_POST['gia'] ?? '';
    $so_luong = $_POST['so_luong'] ?? '';
    $don_vi = trim($_POST['don_vi'] ?? '');

    // Validate
    if ($ten === '') $error = "Tên sản phẩm không được để trống.";
    elseif (!is_numeric($gia) || floatval($gia) < 0) $error = "Giá phải là số và >= 0.";
    elseif (!is_numeric($so_luong) || intval($so_luong) < 0) $error = "Số lượng phải là số nguyên >= 0.";
    else {
        // Kiểm tra trùng tên (nếu cần)
        $stmt = $conn->prepare("SELECT id FROM san_pham WHERE ten_san_pham = ? LIMIT 1");
        $stmt->bind_param("s", $ten);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $error = "Sản phẩm đã tồn tại. Vui lòng kiểm tra hoặc dùng chức năng sửa.";
            $stmt->close();
        } else {
            $stmt->close();
            // Insert
            $stmt2 = $conn->prepare("INSERT INTO san_pham (ten_san_pham, gia, so_luong, don_vi) VALUES (?, ?, ?, ?)");
            $g = floatval($gia);
            $sl = intval($so_luong);
            $stmt2->bind_param("sdis", $ten, $g, $sl, $don_vi);
            if ($stmt2->execute()) {
                $success = "Thêm sản phẩm thành công.";
            } else {
                $error = "Lỗi khi thêm sản phẩm: " . $stmt2->error;
            }
            $stmt2->close();
        }
    }
}
?>

<div class="container mt-4">
  <h3 class="mb-4 text-primary">➕ Thêm sản phẩm mới</h3>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?> <a href="sanpham.php" class="btn btn-sm btn-outline-primary ms-2">Về danh sách</a></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Tên sản phẩm</label>
      <input type="text" name="ten_san_pham" class="form-control" required value="<?= isset($_POST['ten_san_pham']) ? htmlspecialchars($_POST['ten_san_pham']) : '' ?>">
    </div>

    <div class="row">
      <div class="col-md-4 mb-3">
        <label class="form-label">Giá (VNĐ)</label>
        <input type="number" step="100" min="0" name="gia" class="form-control" required value="<?= isset($_POST['gia']) ? htmlspecialchars($_POST['gia']) : '0' ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Số lượng</label>
        <input type="number" name="so_luong" min="0" class="form-control" required value="<?= isset($_POST['so_luong']) ? htmlspecialchars($_POST['so_luong']) : '0' ?>">
      </div>
      <div class="col-md-4 mb-3">
        <label class="form-label">Đơn vị</label>
        <input type="text" name="don_vi" class="form-control" value="<?= isset($_POST['don_vi']) ? htmlspecialchars($_POST['don_vi']) : '' ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-success">Lưu</button>
    <a href="sanpham.php" class="btn btn-outline-secondary ms-2">Hủy</a>
  </form>
</div>
