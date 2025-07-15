<?php
include 'session_check.php';
include 'admin_layout.php';
include 'db_connect.php';

// Xử lý thêm sản phẩm
if (isset($_POST['add'])) {
    $ten = $_POST['ten'];
    $gia = $_POST['gia'];
    $so_luong = $_POST['so_luong'];
    $don_vi = $_POST['don_vi'];

    $stmt = $conn->prepare("INSERT INTO san_pham (ten_san_pham, gia, so_luong, don_vi) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdis", $ten, $gia, $so_luong, $don_vi);
    $stmt->execute();
    header("Location: sanpham.php");
    exit();
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM san_pham WHERE id = $id");
    header("Location: sanpham.php");
    exit();
}

// Lấy danh sách sản phẩm
$san_pham = $conn->query("SELECT * FROM san_pham ORDER BY id DESC");
?>

<div class="container mt-4">
  <h3 class="mb-4 text-primary fw-bold">📦 Danh sách sản phẩm</h3>

  <table class="table table-bordered table-hover align-middle text-center bg-white shadow-sm">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Tên sản phẩm</th>
        <th>Giá</th>
        <th>Số lượng</th>
        <th>Đơn vị</th>
        <th>Hành động</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $san_pham->fetch_assoc()): ?>
        <tr>
          <td><?= $row['id'] ?></td>
          <td class="text-start"><?= $row['ten_san_pham'] ?></td>
          <td><?= number_format($row['gia'], 0, ',', '.') ?> đ</td>
          <td><?= $row['so_luong'] ?></td>
          <td><?= $row['don_vi'] ?></td>
          <td>
            <a href="suasanpham.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <a href="sanpham.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Bạn chắc chắn muốn xoá sản phẩm này?')">Xoá</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>

  <hr class="my-4">

  <h4 class="text-success mb-3">➕ Thêm sản phẩm mới</h4>
  <form method="post" class="row g-3">
    <div class="col-md-4">
      <label class="form-label">Tên sản phẩm</label>
      <input type="text" name="ten" class="form-control" required>
    </div>
    <div class="col-md-2">
      <label class="form-label">Giá</label>
      <input type="number" name="gia" class="form-control" required>
    </div>
    <div class="col-md-2">
      <label class="form-label">Số lượng</label>
      <input type="number" name="so_luong" class="form-control" required>
    </div>
    <div class="col-md-2">
      <label class="form-label">Đơn vị</label>
      <input type="text" name="don_vi" class="form-control" required>
    </div>
    <div class="col-md-2 d-flex align-items-end">
      <button type="submit" name="add" class="btn btn-success w-100">Thêm</button>
    </div>
  </form>
</div>
