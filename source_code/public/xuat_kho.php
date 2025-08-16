<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
include __DIR__ . '/../includes/session_check.php';
include __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/layouts/admin_layout.php';

// lấy danh sách sản phẩm
$products = [];
$res = $conn->query("SELECT id, ten_san_pham, gia, so_luong, don_vi FROM san_pham ORDER BY ten_san_pham ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $products[] = $r;
}
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📤 Xuất kho</h4>
    <a href="phieu_xuat_list.php" class="btn btn-outline-secondary">Danh sách phiếu xuất</a>
  </div>

  <form id="formXuat" method="post" action="luu_xuat_kho.php" onsubmit="return validateXuat();">
    <div class="mb-3">
      <label class="form-label">Ghi chú</label>
      <input type="text" name="ghi_chu" class="form-control" placeholder="Ghi chú (ví dụ: Xuất cho KH A)">
    </div>

    <div class="table-responsive">
      <table class="table table-bordered" id="tblXuat">
        <thead class="table-dark">
          <tr>
            <th style="width:45%;">Sản phẩm</th>
            <th style="width:15%;">Số lượng</th>
            <th style="width:20%;">Giá (vnđ)</th>
            <th style="width:10%;">Tồn hiện tại</th>
            <th style="width:10%;">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <select class="form-select product-select" name="product_id[]">
                <option value="">-- Chọn sản phẩm --</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= $p['id'] ?>" data-price="<?= htmlspecialchars($p['gia']) ?>" data-stock="<?= intval($p['so_luong']) ?>">
                    <?= htmlspecialchars($p['ten_san_pham']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="number" min="1" name="qty[]" class="form-control qty-input" value="1"></td>
            <td><input type="number" min="0" step="0.01" name="price[]" class="form-control price-input" value="0"></td>
            <td class="text-center stock-cell">0</td>
            <td class="text-center"><button type="button" class="btn btn-sm btn-danger btn-remove">Xóa</button></td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mb-3">
      <button id="btnAddX" type="button" class="btn btn-outline-primary btn-sm">+ Thêm dòng</button>
      <button type="submit" class="btn btn-success">Lưu phiếu xuất</button>
    </div>
  </form>
</div>

<script>
  function addRowX() {
    const tbody = document.querySelector('#tblXuat tbody');
    const tr = tbody.querySelector('tr').cloneNode(true);
    tr.querySelectorAll('input').forEach(i => { i.value = (i.name==='qty[]') ? 1 : '' });
    tr.querySelector('select').selectedIndex = 0;
    tr.querySelector('.stock-cell').textContent = '0';
    tbody.appendChild(tr);
  }
  document.getElementById('btnAddX').addEventListener('click', addRowX);

  document.querySelector('#tblXuat tbody').addEventListener('click', function(e){
    if (e.target.classList.contains('btn-remove')) {
      const row = e.target.closest('tr');
      if (document.querySelectorAll('#tblXuat tbody tr').length === 1) {
        row.querySelector('select').selectedIndex = 0;
        row.querySelectorAll('input').forEach(i => i.value = '');
        row.querySelector('.stock-cell').textContent = '0';
      } else row.remove();
    }
  });

  // when product selected -> populate price and stock
  document.querySelector('#tblXuat tbody').addEventListener('change', function(e){
    if (e.target.classList.contains('product-select')) {
      const row = e.target.closest('tr');
      const opt = e.target.selectedOptions[0];
      const priceInput = row.querySelector('.price-input');
      const stockCell = row.querySelector('.stock-cell');
      if (!opt || !opt.value) {
        priceInput.value = '';
        stockCell.textContent = '0';
        return;
      }
      const price = opt.dataset.price || 0;
      const stock = opt.dataset.stock || 0;
      priceInput.value = price;
      stockCell.textContent = stock;
    }
  });

  function validateXuat() {
    const rows = document.querySelectorAll('#tblXuat tbody tr');
    if (rows.length === 0) { alert('Vui lòng thêm ít nhất 1 dòng sản phẩm'); return false; }
    for (let i=0;i<rows.length;i++) {
      const row = rows[i];
      const pid = row.querySelector('select.product-select').value;
      const qty = parseInt(row.querySelector('.qty-input').value || 0);
      const stock = parseInt(row.querySelector('.stock-cell').textContent || 0);
      if (!pid) { alert('Vui lòng chọn sản phẩm ở dòng ' + (i+1)); return false; }
      if (qty <= 0) { alert('Số lượng phải > 0 ở dòng ' + (i+1)); return false; }
      if (qty > stock) { if (!confirm('Số lượng xuất ('+qty+') lớn hơn tồn hiện có ('+stock+') ở dòng '+(i+1)+'. Bạn có muốn tiếp tục?')) return false; }
    }
    return true;
  }
</script>

