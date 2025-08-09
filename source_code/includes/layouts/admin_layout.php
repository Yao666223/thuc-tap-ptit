<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Lấy thông tin user từ session (cố gắng hỗ trợ nhiều key)
$username = $_SESSION['username'] ?? $_SESSION['user'] ?? 'Guest';
$role = $_SESSION['role'] ?? '';

// đường dẫn base nếu cần (nếu public/ là webroot bạn có thể để '')
$base = ''; // ví dụ: '/mini-warehouse/public' nếu cần

?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin - Mini Warehouse</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{--sidebar-width:240px;}
    body { min-height:100vh; display:flex; }
    .sidebar {
      width: var(--sidebar-width);
      flex-shrink:0;
      background: #f8f9fa;
      border-right: 1px solid #e7e7e7;
      padding: 12px 8px;
    }
    .content-wrap { flex:1; display:flex; flex-direction:column; min-height:100vh; }
    .topbar { background: #fff; border-bottom:1px solid #e7e7e7; padding: 10px 16px; }
    .nav .nav-link { color: #333; padding: 8px 10px; border-radius:6px; }
    .nav .nav-link.active, .nav .nav-link:hover { background: #eef2ff; color: #0b5ed7; text-decoration:none; }
    .brand { font-weight:700; color:#0d6efd; letter-spacing:0.2px; }
    .user-badge { font-size:0.9rem; }
    @media (max-width: 767px) {
      .sidebar { position: fixed; left:-100%; top:0; height:100%; z-index:1050; transition:left .2s ease; }
      .sidebar.show { left:0; }
    }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="mb-3 px-2">
      <div class="brand mb-2">Mini Warehouse</div>
      <div class="small text-muted">Quản trị kho nhỏ</div>
    </div>

    <nav class="nav flex-column">
      <a class="nav-link" href="<?= $base ?>../public/dashboard.php">🏠 Dashboard</a>

      <a class="nav-link" href="<?= $base ?>../public/sanpham.php">📦 Sản phẩm</a>
      <a class="nav-link" href="<?= $base ?>../public/nhap_kho.php">📥 Nhập kho</a>
      <a class="nav-link" href="<?= $base ?>../public/xuat_kho.php">📤 Xuất kho</a>

      <div class="mt-3 mb-1 px-2 text-muted small">— Lịch sử & kiểm kê —</div>
      <a class="nav-link" href="<?= $base ?>../public/phieu_nhap_list.php">🕘 Lịch sử phiếu nhập</a>
      <a class="nav-link" href="<?= $base ?>../public/phieu_xuat_list.php">🕘 Lịch sử phiếu xuất</a>

      <?php if ($role === 'admin'): ?>
        <div class="mt-3 mb-1 px-2 text-muted small">— Quản trị —</div>
        <a class="nav-link" href="<?= $base ?>../public/nguoi_dung.php">👥 Người dùng</a>
      <?php endif; ?>

      <div class="mt-4 px-2">
        <a class="nav-link text-danger" href="<?= $base ?>../public/logout.php">🔒 Đăng xuất</a>
      </div>
    </nav>
  </aside>

  <!-- CONTENT -->
  <div class="content-wrap">

    <!-- TOPBAR -->




    <main class="p-4">
