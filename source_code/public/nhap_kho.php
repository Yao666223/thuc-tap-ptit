<?php
include '../includes/session_check.php';
include '../includes/layouts/admin_layout.php';
include '../includes/db_connect.php';

$errors = [];
$success = "";

// Lấy danh sách sản phẩm
$san_pham_result = $conn->query("SELECT * FROM san_pham ORDER BY ten_san_pham ASC");
$san_pham_list = [];
while ($sp = $san_pham_result->fetch_assoc()) {
    $san_pham_list[$sp['id']] = $sp;
}

// Xử lý khi submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ghi_chu = trim($_POST['ghi_chu']);
    $ten_sp_arr = $_POST['ten_san_pham'];
    $gia_arr = $_POST['gia'];
    $so_luong_arr = $_POST['so_luong'];
    $don_vi_arr = $_POST['don_vi'];

    // Tạo phiếu nhập
    $stmt = $conn->prepare("INSERT INTO phieu_nhap (ngay_tao, ghi_chu) VALUES (NOW(), ?)");
    $stmt->bind_param("s", $ghi_chu);
    $stmt->execute();
    $id_phieu = $stmt->insert_id;
    $stmt->close();

    for ($i = 0; $i < count($ten_sp_arr); $i++) {
        $ten = trim($ten_sp_arr[$i]);
        $gia = floatval($gia_arr[$i]);
        $so_luong = intval($so_luong_arr[$i]);
        $don_vi = trim($don_vi_arr[$i]);

        // Kiểm tra sản phẩm có tồn tại không (theo tên)
        $res = $conn->query("SELECT * FROM san_pham WHERE ten_san_pham = '$ten'");
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $id_sp = $row['id'];

            // Cập nhật số lượng và giá
            $conn->query("UPDATE san_pham SET so_luong = so_luong + $so_luong, gia = $gia WHERE id = $id_sp");
        } else {
            // Thêm mới sản phẩm
            $stmt = $conn->prepare("INSERT INTO san_pham (ten_san_pham, gia, so_luong, don_vi) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdis", $ten, $gia, $so_luong, $don_vi);
            $stmt->execute();
            $id_sp = $stmt->insert_id;
            $stmt->close();
        }

        // Thêm chi tiết nhập
        $stmt = $conn->prepare("INSERT INTO chi_tiet_nhap (id_phieu_nhap, id_san_pham, so_luong, gia) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $id_phieu, $id_sp, $so_luong, $gia);
        $stmt->execute();
        $stmt->close();
    }

    $success = "✅ Phiếu nhập đã được lưu, dữ liệu đã cập nhật.";
}
?>

<div class="container mt-4">
  <h3 class="text-success fw-bold">📥 Nhập kho sản phẩm</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Ghi chú</label>
      <input type="text" name="ghi_chu" class="form-control" placeholder="Nhập ghi chú nếu cần...">
    </div>

    <div id="product-area">
      <div class="row g-2 product-row align-items-end mb-2">
        <div class="col-md-3">
          <label>Tên sản phẩm</label>
          <input list="ten_sp_list" name="ten_san_pham[]" class="form-control ten-sp" required>
          <datalist id="ten_sp_list">
            <?php foreach ($san_pham_list as $sp): ?>
              <option value="<?= $sp['ten_san_pham'] ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-2">
          <label>Giá nhập</label>
          <input type="number" step="100" name="gia[]" class="form-control gia" required>
        </div>
        <div class="col-md-2">
          <label>Số lượng</label>
          <input type="number" min="1" name="so_luong[]" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label>Đơn vị</label>
          <input type="text" name="don_vi[]" class="form-control" placeholder="ví dụ: gói, hộp..." required>
        </div>
        <div class="col-md-2">
          <button type="button" class="btn btn-danger remove-product">❌</button>
        </div>
      </div>
    </div>

    <button type="button" class="btn btn-secondary" id="add-product">+ Thêm sản phẩm</button>
    <br><br>
    <button type="submit" class="btn btn-success">💾 Lưu phiếu nhập</button>
    <a href="dashboard.php" class="btn btn-outline-dark ms-2">⬅ Quay lại</a>
  </form>
</div>

<script>
const productList = <?= json_encode($san_pham_list) ?>;

document.getElementById('add-product').addEventListener('click', function () {
  const container = document.getElementById('product-area');
  const row = container.querySelector('.product-row').cloneNode(true);

  row.querySelectorAll('input').forEach(input => input.value = '');
  container.appendChild(row);
});

document.addEventListener('click', function (e) {
  if (e.target.classList.contains('remove-product')) {
    const rows = document.querySelectorAll('.product-row');
    if (rows.length > 1) e.target.closest('.product-row').remove();
  }
});

document.addEventListener('input', function (e) {
  if (e.target.classList.contains('ten-sp')) {
    const ten = e.target.value;
    const row = e.target.closest('.product-row');
    const giaInput = row.querySelector('.gia');
    for (let id in productList) {
      if (productList[id]['ten_san_pham'] === ten) {
        giaInput.value = productList[id]['gia'];
        break;
      }
    }
  }
});
</script>
