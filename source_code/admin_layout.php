<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Hệ thống quản lý kho</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 230px;
      background-color: #343a40;
      color: white;
      padding: 20px;
      flex-shrink: 0;
    }
    .sidebar h4 {
      font-weight: bold;
      margin-bottom: 30px;
      text-align: center;
    }
    .sidebar a {
      color: white;
      display: block;
      margin: 12px 0;
      text-decoration: none;
      padding: 8px 12px;
      border-radius: 5px;
      transition: background 0.3s;
    }
    .sidebar a:hover {
      background-color: #495057;
    }
    .content {
      flex: 1;
      padding: 30px;
      background-color: #f8f9fa;
    }
  </style>
</head>
<div class="sidebar">
  <h4>📦 Kho hàng mini</h4>
  <a href="dashboard.php">🏠 Trang chính</a>
  <a href="sanpham.php">📋 Quản lý sản phẩm</a>
  <a href="nhap_kho.php">📥 Nhập kho</a>
  <a href="xuat_kho.php">📤 Xuất kho</a>
  <a href="thong_ke.php">📊 Thống kê</a>
  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
  <a href="tao_nguoi_dung.php">➕ Tạo nhân viên</a>
<?php endif; ?>
  <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
  <a href="nguoi_dung.php">👥 Quản lý người dùng</a>
<?php endif; ?>

  <hr>
  <?php if (isset($_SESSION['username'])): ?>
    <div class="text-white small mt-3">
      👤 <?= $_SESSION['username'] ?> (<?= $_SESSION['role'] ?>)<br>
      <a href="logout.php" class="text-decoration-underline text-white">Đăng xuất</a>
    </div>
  <?php endif; ?>


</div>
