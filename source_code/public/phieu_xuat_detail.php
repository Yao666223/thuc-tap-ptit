<?php
// phieu_xuat_detail.php
include __DIR__ . '/../includes/session_check.php';
include __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/layouts/admin_layout.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>ID phiếu không hợp lệ.</div></div>";
    exit();
}

$stmt = $conn->prepare("SELECT p.*, u.username AS user_name FROM phieu_xuat p LEFT JOIN users u ON p.created_by = u.id WHERE p.id = ? LIMIT 1");
$stmt->bind_param("i",$id);
$stmt->execute();
$ph = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$ph) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Không tìm thấy phiếu.</div></div>";
    exit();
}

$stmt2 = $conn->prepare("SELECT ct.id_san_pham, sp.ten_san_pham, ct.so_luong, ct.gia FROM chi_tiet_phieu_xuat ct JOIN san_pham sp ON sp.id = ct.id_san_pham WHERE ct.id_phieu_xuat = ?");
$stmt2->bind_param("i",$id);
$stmt2->execute();
$items = $stmt2->get_result();
$stmt2->close();
?>

<div class="container mt-4">
  <h3>Chi tiết Phiếu Xuất #<?= $ph['id'] ?></h3>
  <p>Ngày: <?= $ph['ngay_tao'] ?> — Người tạo: <?= htmlspecialchars($ph['created_by']) ?> — Ghi chú: <?= htmlspecialchars($ph['ghi_chu']) ?></p>

  <table class="table table-bordered">
    <thead class="table-dark"><tr><th>#</th><th>Sản phẩm</th><th>Số lượng</th><th>Giá</th><th>Thành tiền</th></tr></thead>
    <tbody>
      <?php $sum=0; while($it=$items->fetch_assoc()): $sum += $it['so_luong'] * $it['gia']; ?>
      <tr>
        <td><?= $it['id_san_pham'] ?></td>
        <td><?= htmlspecialchars($it['ten_san_pham']) ?></td>
        <td><?= $it['so_luong'] ?></td>
        <td><?= number_format($it['gia']) ?> đ</td>
        <td><?= number_format($it['so_luong'] * $it['gia']) ?> đ</td>
      </tr>
      <?php endwhile; ?>
      <tr class="table-secondary"><td colspan="4" class="text-end"><strong>Tổng</strong></td><td><strong><?= number_format($sum) ?> đ</strong></td></tr>
    </tbody>
  </table>

  <a href="phieu_xuat_list.php" class="btn btn-outline-secondary">Quay lại</a>
</div>
