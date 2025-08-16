<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
include __DIR__ . '/../includes/session_check.php';   // chỉnh đường dẫn nếu cần
include __DIR__ . '/../includes/db_connect.php';
include __DIR__ . '/../includes/layouts/admin_layout.php';

// Lấy danh sách sản phẩm để populate dropdown
$products = [];
$res = $conn->query("SELECT id, ten_san_pham, gia, so_luong, don_vi FROM san_pham ORDER BY ten_san_pham ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) $products[] = $r;
}
?>

<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📥 Nhập kho</h4>
    <a href="phieu_nhap_list.php" class="btn btn-outline-secondary">Danh sách phiếu nhập</a>
  </div>

  <form id="formNhap" method="post" action="luu_nhap_kho.php" onsubmit="return prepareAndValidate();">
    <div class="mb-3">
      <label class="form-label">Ghi chú</label>
      <input type="text" name="ghi_chu" class="form-control" placeholder="Ghi chú (ví dụ: Nhập hàng từ NCC A)">
    </div>

    <div class="table-responsive">
      <table class="table table-bordered" id="tblItems">
        <thead class="table-dark">
          <tr>
            <th style="width:40%;">Sản phẩm</th>
            <th style="width:15%;">Số lượng</th>
            <th style="width:20%;">Giá (vnđ)</th>
            <th style="width:15%;">Đơn vị</th>
            <th style="width:10%;">Hành động</th>
          </tr>
        </thead>
        <tbody>
          <!-- 1 row mẫu -->
          <tr>
            <td>
              <select class="form-select product-select" name="product_id[]">
                <option value="0">-- Thêm sản phẩm mới --</option>
                <?php foreach ($products as $p): ?>
                  <option value="<?= $p['id'] ?>" data-price="<?= htmlspecialchars($p['gia']) ?>" data-donvi="<?= htmlspecialchars($p['don_vi']) ?>">
                    <?= htmlspecialchars($p['ten_san_pham']) ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <!-- nếu chọn thêm mới: hiển thị ô nhập -->
              <input type="text" class="form-control mt-2 d-none new-name" name="ten_san_pham_new[]" placeholder="Tên sản phẩm mới">
              <input type="text" class="form-control mt-2 d-none new-donvi" name="don_vi[]" placeholder="Đơn vị (ví dụ: cái, kg)">
            </td>
            <td><input type="number" min="1" name="qty[]" class="form-control qty-input" value="1"></td>
            <td><input type="number" min="0" step="0.01" name="price[]" class="form-control price-input" value="0"></td>
            <td class="text-center unit-cell">-</td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-danger btn-remove">Xóa</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mb-3">
      <button id="btnAdd" type="button" class="btn btn-outline-primary btn-sm">+ Thêm dòng</button>
      <button type="submit" class="btn btn-success">Lưu phiếu nhập</button>
    </div>
  </form>
</div>

<script>
  // helper: clone row
  function addRow() {
    const tbody = document.querySelector('#tblItems tbody');
    const tr = tbody.querySelector('tr').cloneNode(true);
    // reset values
    tr.querySelectorAll('input').forEach(i => {
      if (i.name === 'qty[]') i.value = 1;
      else i.value = '';
    });
    // reset selects
    const sel = tr.querySelector('select.product-select');
    sel.selectedIndex = 0;
    tr.querySelector('.unit-cell').textContent = '-';
    tr.querySelector('.new-name').classList.add('d-none');
    tr.querySelector('.new-donvi').classList.add('d-none');
    tbody.appendChild(tr);
  }

  document.getElementById('btnAdd').addEventListener('click', addRow);

  // delegate remove & product change
  document.querySelector('#tblItems tbody').addEventListener('click', function(e){
    if (e.target.classList.contains('btn-remove')) {
      const row = e.target.closest('tr');
      // if only one row, reset fields instead of removing
      if (document.querySelectorAll('#tblItems tbody tr').length === 1) {
        row.querySelectorAll('input').forEach(i=> i.value = '');
        row.querySelector('select.product-select').selectedIndex = 0;
        row.querySelector('.unit-cell').textContent = '-';
        row.querySelector('.new-name').classList.add('d-none');
        row.querySelector('.new-donvi').classList.add('d-none');
      } else {
        row.remove();
      }
    }
  });

  // when product select changes: populate price and unit, or show new inputs
  document.querySelector('#tblItems tbody').addEventListener('change', function(e){
    if (e.target.classList.contains('product-select')) {
      const row = e.target.closest('tr');
      const opt = e.target.selectedOptions[0];
      const priceInput = row.querySelector('.price-input');
      const unitCell = row.querySelector('.unit-cell');
      const newName = row.querySelector('.new-name');
      const newDonvi = row.querySelector('.new-donvi');

      if (opt.value === '0') {
        // show new product fields
        newName.classList.remove('d-none');
        newDonvi.classList.remove('d-none');
        priceInput.value = '';
        unitCell.textContent = newDonvi.value || '-';
      } else {
        newName.classList.add('d-none');
        newDonvi.classList.add('d-none');
        const price = opt.dataset.price || 0;
        const donvi = opt.dataset.donvi || '-';
        priceInput.value = price;
        unitCell.textContent = donvi;
      }
    }
  });

  // keep unit updated when user types new donvi
  document.querySelector('#tblItems tbody').addEventListener('input', function(e){
    if (e.target.classList.contains('new-donvi')) {
      const row = e.target.closest('tr');
      row.querySelector('.unit-cell').textContent = e.target.value || '-';
    }
  });

  // before submit: validate and ensure arrays align
  function prepareAndValidate() {
    const rows = document.querySelectorAll('#tblItems tbody tr');
    let ok = true;
    let any = false;
    rows.forEach((row, idx) => {
      const pid = row.querySelector('select.product-select').value;
      const qty = row.querySelector('.qty-input').value;
      const price = row.querySelector('.price-input').value;
      if ((pid === '0' || pid === '0') && row.querySelector('.new-name').classList.contains('d-none') === false) {
        // new product -> require name
        const newname = row.querySelector('.new-name').value.trim();
        if (!newname) { alert('Vui lòng nhập tên sản phẩm mới ở dòng ' + (idx+1)); ok = false; return; }
      }
      if (!qty || parseInt(qty) <= 0) { alert('Số lượng phải > 0 ở dòng ' + (idx+1)); ok = false; return; }
      if (price === '' || parseFloat(price) < 0) { if (!confirm('Giá chưa hợp lệ ở dòng ' + (idx+1) + '. Bạn muốn tiếp tục?')) { ok = false; return; } }
      any = true;
    });
    if (!any) { alert('Vui lòng nhập ít nhất một dòng sản phẩm'); ok = false; }
    return ok;
  }
</script>

