<?php
include 'session_check.php';
include 'admin_layout.php';
include 'db_connect.php';

$san_pham_result = $conn->query("SELECT * FROM san_pham ORDER BY ten_san_pham ASC");
$san_pham_list = [];
while ($sp = $san_pham_result->fetch_assoc()) {
    $san_pham_list[$sp['id']] = $sp;
}

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ghi_chu = trim($_POST['ghi_chu']);
    $ten_sp_arr = $_POST['ten_san_pham'];
    $gia_arr = $_POST['gia'];
    $so_luong_arr = $_POST['so_luong'];

    // Tạo phiếu xuất
    $stmt = $conn->prepare("INSERT INTO phieu_xuat (ngay_tao, ghi_chu) VALUES (NOW(), ?)");
    $stmt->bind_param("s", $ghi_chu);
    $stmt->execute();
    $id_phieu = $stmt->insert_id;
    $stmt->close();

    for ($i = 0; $i < count($ten_sp_arr); $i++) {
        $ten = trim($ten_sp_arr[$i]);
        $gia = floatval($gia_arr[$i]);
        $so_luong = intval($so_luong_arr[$i]);

        $res = $conn->query("SELECT * FROM san_pham WHERE ten_san_pham = '$ten'");
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $id_sp = $row['id'];
            $so_luong_hien_tai = $row['so_luong'];

            if ($so_luong > $so_luong_hien_tai) {
                $errors[] = "❌ Không đủ hàng để xuất: $ten (còn $so_luong_hien_tai)";
                continue;
            }

            // Trừ hàng và cập nhật giá nếu cần
            $conn->query("UPDATE san_pham SET so_luong = so_luong - $so_luong, gia = $gia WHERE id = $id_sp");

            // Ghi vào chi tiết phiếu xuất
            $stmt = $conn->prepare("INSERT INTO chi_tiet_phieu_xuat (id_phieu_xuat, id_san_pham, so_luong, gia) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $id_phieu, $id_sp, $so_luong, $gia);
            $stmt->execute();
            $stmt->close();
        } else {
            $errors[] = "❌ Sản phẩm không tồn tại trong kho: $ten";
        }
    }

    if (empty($errors)) {
        $success = "✅ Đã xuất kho thành công!";
    }
}
?>

<div class="container mt-4">
  <h3 class="text-danger fw-bold">🚚 Xuất kho sản phẩm</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-warning">
      <ul class="mb-0">
        <?php foreach ($errors as $err): ?>
          <li><?= $err ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post">
    <div class="mb-3">
      <label class="form-label">Ghi chú</label>
      <input type="text" name="ghi_chu" class="form-control" placeholder="VD: Xuất cho cửa hàng A">
    </div>

    <div id="product-area">
      <div class="row g-2 product-row align-items-end mb-2">
        <div class="col-md-4">
          <label>Tên sản phẩm</label>
          <input list="ten_sp_list" name="ten_san_pham[]" class="form-control ten-sp" required>
          <datalist id="ten_sp_list">
            <?php foreach ($san_pham_list as $sp): ?>
              <option value="<?= $sp['ten_san_pham'] ?>">
            <?php endforeach; ?>
          </datalist>
        </div>
        <div class="col-md-3">
          <label>Giá xuất</label>
          <input type="number" name="gia[]" class="form-control gia" required>
        </div>
        <div class="col-md-3">
          <label>Số lượng</label>
          <input type="number" name="so_luong[]" class="form-control" min="1" required>
        </div>
        <div class="col-md-2">
          <button type="button" class="btn btn-danger remove-product">❌</button>
        </div>
      </div>
    </div>

    <button type="button" class="btn btn-secondary" id="add-product">+ Thêm sản phẩm</button>
    <br><br>
    <button type="submit" class="btn btn-danger">💾 Xác nhận xuất kho</button>
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
