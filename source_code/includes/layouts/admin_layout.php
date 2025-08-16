<?php
// admin_layout.php - bản cập nhật theo yêu cầu: có nút "Thêm người dùng" và bỏ thanh tìm kiếm
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$username = $_SESSION['username'] ?? $_SESSION['user'] ?? 'Guest';
$role     = $_SESSION['role'] ?? '';
$user_id  = $_SESSION['user_id'] ?? null;


$base = '/mini-warehouse/source_code/public';

?><!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin - Mini Warehouse</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{--sidebar-width:240px;}
    body { min-height:100vh; display:flex; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; }
    .sidebar { width: var(--sidebar-width); flex-shrink:0; background:#f8f9fa; border-right:1px solid #e7e7e7; padding:12px; }
    .content-wrap { flex:1; display:flex; flex-direction:column; min-height:100vh; }
    .topbar { background:#fff; border-bottom:1px solid #e7e7e7; padding:10px 16px; }
    .nav .nav-link { color:#333; padding:8px 10px; border-radius:6px; }
    .nav .nav-link.active, .nav .nav-link:hover { background:#eef2ff; color:#0b5ed7; text-decoration:none; }
    .brand { font-weight:700; color:#0d6efd; letter-spacing:0.2px; }
    .user-badge { font-size:0.9rem; }
    .small-muted { color:#6c757d; font-size:0.9rem; }
    @media (max-width:767px) { .sidebar { position:fixed; left:-100%; top:0; height:100%; z-index:1050; transition:left .2s; } .sidebar.show{left:0;} }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="mb-3 px-2">
      <div class="brand mb-2">Mini Warehouse</div>
      <div class="small-muted">Hệ thống quản lý kho mini</div>
    </div>

    <nav class="nav flex-column">
      <a class="nav-link" href="<?= $base ?>/dashboard.php">🏠 Dashboard</a>

      <div class="mt-2 mb-1 px-2 text-muted small">— Quản lý chính —</div>
      <a class="nav-link" href="<?= $base ?>/sanpham.php">📦 Sản phẩm</a>
      <a class="nav-link" href="<?= $base ?>/nhap_kho.php">📥 Nhập kho</a>
      <a class="nav-link" href="<?= $base ?>/xuat_kho.php">📤 Xuất kho</a>

      <div class="mt-3 mb-1 px-2 text-muted small">— Lịch sử & Kiểm kê —</div>
      <a class="nav-link" href="<?= $base ?>/phieu_nhap_list.php">🕘 Lịch sử phiếu nhập</a>
      <a class="nav-link" href="<?= $base ?>/phieu_xuat_list.php">🕘 Lịch sử phiếu xuất</a>

      <div class="mt-3 mb-1 px-2 text-muted small">— Báo cáo & Thống kê —</div>
      <a class="nav-link" href="<?= $base ?>/thong_ke.php">📊 Thống kê</a>

      <?php if ($role === 'admin'): ?>
        <div class="mt-3 mb-1 px-2 text-muted small">— Quản trị —</div>
        <a class="nav-link" href="<?= $base ?>/nguoi_dung.php">👥 Người dùng</a>
        <!-- nút Thêm người dùng nhanh -->
        <a class="nav-link" href="<?= $base ?>/tao_nguoi_dung.php">➕ Thêm người dùng</a>
      <?php endif; ?>

      <div class="mt-4 px-2">
        <a class="nav-link text-danger" href="<?= $base ?>/logout.php">🔒 Đăng xuất</a>
      </div>
    </nav>
  </aside>

  <!-- CONTENT -->
  <div class="content-wrap">

    <!-- TOPBAR -->
    <div class="topbar d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center">
        <button id="btnToggleSidebar" class="btn btn-sm btn-outline-secondary me-2 d-md-none">☰</button>
        <h5 class="mb-0">Xin chào, <span class="text-primary"><?= htmlspecialchars($username) ?></span></h5>
      </div>

      <div class="d-flex align-items-center">
        <!-- Thanh tìm kiếm đã được loại bỏ theo yêu cầu -->
        <div class="me-3 text-end user-badge">
          <div class="fw-bold"><?= htmlspecialchars($username) ?></div>
          <div class="small text-muted"><?= htmlspecialchars($role) ?></div>
        </div>
      </div>
    </div>

    <!-- MAIN -->
    <main class="p-4">
      <!-- Nội dung trang bắt đầu từ đây -->
