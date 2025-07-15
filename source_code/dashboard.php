<?php
include 'session_check.php';
include 'admin_layout.php';
include 'db_connect.php';

// Thống kê dữ liệu
$tong_san_pham = $conn->query("SELECT COUNT(*) AS total FROM san_pham")->fetch_assoc()['total'];
$tong_so_luong = $conn->query("SELECT SUM(so_luong) AS total FROM san_pham")->fetch_assoc()['total'];
$tong_phieu_nhap = $conn->query("SELECT COUNT(*) AS total FROM phieu_nhap")->fetch_assoc()['total'];
$tong_phieu_xuat = $conn->query("SELECT COUNT(*) AS total FROM phieu_xuat")->fetch_assoc()['total'];
?>

<div class="container mt-4">
  <h2 class="text-primary fw-bold mb-4 text-center">📊 BẢNG ĐIỀU KHIỂN HỆ THỐNG</h2>

  <!-- Thống kê -->
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4 mb-4">
    <div class="col">
      <div class="card shadow-sm text-center border-start border-primary border-5">
        <div class="card-body">
          <h6 class="text-muted">Tổng sản phẩm</h6>
          <div class="fs-3 fw-bold text-primary"><?= $tong_san_pham ?></div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card shadow-sm text-center border-start border-success border-5">
        <div class="card-body">
          <h6 class="text-muted">Tổng tồn kho</h6>
          <div class="fs-3 fw-bold text-success"><?= $tong_so_luong ?></div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card shadow-sm text-center border-start border-info border-5">
        <div class="card-body">
          <h6 class="text-muted">Phiếu nhập</h6>
          <div class="fs-3 fw-bold text-info"><?= $tong_phieu_nhap ?></div>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card shadow-sm text-center border-start border-danger border-5">
        <div class="card-body">
          <h6 class="text-muted">Phiếu xuất</h6>
          <div class="fs-3 fw-bold text-danger"><?= $tong_phieu_xuat ?></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Các chức năng chính -->
  <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
    <div class="col">
      <div class="card bg-primary text-white h-100 shadow">
        <div class="card-body text-center">
          <div class="fs-4">📦</div>
          <h5 class="card-title">Quản lý sản phẩm</h5>
          <p class="card-text">Thêm, sửa, xoá sản phẩm.</p>
          <a href="sanpham.php" class="btn btn-light btn-sm mt-2 fw-bold">Truy cập</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card bg-success text-white h-100 shadow">
        <div class="card-body text-center">
          <div class="fs-4">📥</div>
          <h5 class="card-title">Nhập kho</h5>
          <p class="card-text">Tạo phiếu nhập & chi tiết.</p>
          <a href="nhap_kho.php" class="btn btn-light btn-sm mt-2 fw-bold">Truy cập</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card bg-danger text-white h-100 shadow">
        <div class="card-body text-center">
          <div class="fs-4">📤</div>
          <h5 class="card-title">Xuất kho</h5>
          <p class="card-text">Tạo phiếu xuất & chi tiết.</p>
          <a href="xuat_kho.php" class="btn btn-light btn-sm mt-2 fw-bold">Truy cập</a>
        </div>
      </div>
    </div>
    <div class="col">
      <div class="card bg-dark text-white h-100 shadow">
        <div class="card-body text-center">
          <div class="fs-4">📊</div>
          <h5 class="card-title">Thống kê</h5>
          <p class="card-text">Xem tồn kho, báo cáo.</p>
          <a href="thong_ke.php" class="btn btn-light btn-sm mt-2 fw-bold">Truy cập</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Gợi ý -->
  <div class="alert alert-info mt-4">
    💡 <strong>Mẹo:</strong> Bạn có thể nhấn vào từng ô để điều hướng nhanh đến chức năng chính của hệ thống.
  </div>
</div>
