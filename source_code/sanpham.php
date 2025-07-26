<?php
include 'session_check.php';
include 'admin_layout.php';
include 'db_connect.php';

// Lấy danh sách sản phẩm
$san_pham = $conn->query("SELECT * FROM san_pham ORDER BY id ASC");

?>

<div class="container mt-4">
  <h3 class="mb-4 text-primary fw-bold">📦 Danh sách sản phẩm trong kho</h3>

  <table class="table table-bordered table-hover align-middle text-center bg-white shadow-sm">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Tên sản phẩm</th>
        <th>Giá</th>
        <th>Số lượng tồn</th>
        <th>Đơn vị</th>
        <?php if ($_SESSION['role'] === 'admin'): ?>
          <th>Hành động</th>
        <?php endif; ?>
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
          <?php if ($_SESSION['role'] === 'admin'): ?>
            <td>
              <a href="suasanpham.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>
            </td>
          <?php endif; ?>
        </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
