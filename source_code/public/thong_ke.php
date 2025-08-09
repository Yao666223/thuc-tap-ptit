<?php
// thong_ke.php
include '../includes/session_check.php';
include '../includes/layouts/admin_layout.php';
include '../includes/db_connect.php';

// Tháng hiện tại
$thang = date('Y-m');

// 1. Tính KPI
// 1.1 Tổng sản phẩm
$tongSP = $conn->query("SELECT COUNT(*) FROM san_pham")->fetch_row()[0];
// 1.2 Phiếu nhập trong tháng
$tongPN = $conn->query("
  SELECT COUNT(*) 
  FROM phieu_nhap 
  WHERE DATE_FORMAT(ngay_tao,'%Y-%m')='$thang'
")->fetch_row()[0];
// 1.3 Phiếu xuất trong tháng
$tongPX = $conn->query("
  SELECT COUNT(*) 
  FROM phieu_xuat 
  WHERE DATE_FORMAT(ngay_tao,'%Y-%m')='$thang'
")->fetch_row()[0];
// 1.4 Giá trị xuất tháng
$tongGTX = $conn->query("
  SELECT IFNULL(SUM(ct.gia * ct.so_luong),0)
  FROM chi_tiet_phieu_xuat ct
  JOIN phieu_xuat p ON ct.id_phieu_xuat=p.id
  WHERE DATE_FORMAT(p.ngay_tao,'%Y-%m')='$thang'
")->fetch_row()[0];

// 2. Dữ liệu Biểu đồ 1: Nhập vs Xuất theo ngày trong tháng
$days = []; $nhapData = []; $xuatData = [];
for ($d=1; $d<=date('t'); $d++) {
  $day = sprintf("%02d",$d);
  $days[] = $thang . "-$day";
  // nhập
  $r1 = $conn->query("
    SELECT IFNULL(SUM(so_luong),0) 
    FROM chi_tiet_nhap ct 
    JOIN phieu_nhap p ON ct.id_phieu_nhap=p.id 
    WHERE DATE(p.ngay_tao)='{$thang}-$day'
  ")->fetch_row()[0];
  // xuất
  $r2 = $conn->query("
    SELECT IFNULL(SUM(so_luong),0) 
    FROM chi_tiet_phieu_xuat ct 
    JOIN phieu_xuat p ON ct.id_phieu_xuat=p.id 
    WHERE DATE(p.ngay_tao)='{$thang}-$day'
  ")->fetch_row()[0];
  $nhapData[] = (int)$r1;
  $xuatData[] = (int)$r2;
}

// 3. Dữ liệu Biểu đồ 2: Top 5 sản phẩm xuất nhiều nhất (số lượng)
$top = $conn->query("
  SELECT sp.ten_san_pham, SUM(ct.so_luong) AS total 
  FROM chi_tiet_phieu_xuat ct
  JOIN san_pham sp ON ct.id_san_pham=sp.id
  GROUP BY ct.id_san_pham
  ORDER BY total DESC
  LIMIT 5
");
$topLabels=[]; $topValues=[];
while($r=$top->fetch_assoc()){
  $topLabels[] = $r['ten_san_pham'];
  $topValues[] = (int)$r['total'];
}

// 4. Danh sách “sắp hết” (tồn ≤ 10)
$low = $conn->query("
  SELECT id, ten_san_pham, so_luong 
  FROM san_pham 
  WHERE so_luong <= 10 
  ORDER BY so_luong ASC
");
?>

<div class="container mt-4">
  <!-- KPI Cards -->
  <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-5">
    <div class="col"><div class="card border-primary shadow-sm p-3">
      <h6 class="mb-0">Tổng SP</h6>
      <h3 class="mt-1"><?= $tongSP ?></h3>
    </div></div>
    <div class="col"><div class="card border-success shadow-sm p-3">
      <h6 class="mb-0">Phiếu Nhập Tháng</h6>
      <h3 class="mt-1"><?= $tongPN ?></h3>
    </div></div>
    <div class="col"><div class="card border-info shadow-sm p-3">
      <h6 class="mb-0">Phiếu Xuất Tháng</h6>
      <h3 class="mt-1"><?= $tongPX ?></h3>
    </div></div>
    <div class="col"><div class="card border-danger shadow-sm p-3">
      <h6 class="mb-0">Giá Trị Xuất Tháng</h6>
      <h3 class="mt-1"><?= number_format($tongGTX) ?> đ</h3>
    </div></div>
  </div>

  <!-- Biểu đồ Nhập vs Xuất -->
  <div class="card mb-5 p-4">
    <h5>Nhập vs Xuất theo ngày (<?= date('m/Y') ?>)</h5>
    <canvas id="chart1" height="100"></canvas>
  </div>

  <!-- Biểu đồ Top 5 sản phẩm Xuất -->
  <div class="card mb-5 p-4">
    <h5>Top 5 sản phẩm Xuất nhiều nhất</h5>
    <canvas id="chart2" height="100"></canvas>
  </div>

  <!-- Bảng “sắp hết” -->
  <div class="card p-4 mb-5">
    <h5 class="mb-3">Danh sách sản phẩm sắp hết (≤ 10)</h5>
    <div class="table-responsive">
      <table class="table table-bordered mb-0">
        <thead class="table-light">
          <tr><th>#</th><th>Sản phẩm</th><th>Tồn</th></tr>
        </thead>
        <tbody>
          <?php $i=1; while($r=$low->fetch_assoc()): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td class="text-start"><?= htmlspecialchars($r['ten_san_pham']) ?></td>
            <td><?= $r['so_luong'] ?></td>
          </tr>
          <?php endwhile; ?>
          <?php if ($low->num_rows===0): ?>
          <tr><td colspan="3" class="text-center">Không có sản phẩm sắp hết</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Chart 1: Nhập vs Xuất
  new Chart(document.getElementById('chart1'), {
    type: 'line',
    data: {
      labels: <?= json_encode($days) ?>,
      datasets: [
        { label: 'Nhập', data: <?= json_encode($nhapData) ?>, fill:false, borderColor:'rgba(40,167,69,0.8)' },
        { label: 'Xuất', data: <?= json_encode($xuatData) ?>, fill:false, borderColor:'rgba(220,53,69,0.8)' }
      ]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
  });

  // Chart 2: Top 5 Xuất
  new Chart(document.getElementById('chart2'), {
    type: 'bar',
    data: {
      labels: <?= json_encode($topLabels) ?>,
      datasets: [{ label:'Số lượng', data:<?= json_encode($topValues) ?>, backgroundColor:'rgba(54,162,235,0.7)' }]
    },
    options: { responsive:true, scales:{ y:{ beginAtZero:true } }, plugins:{ legend:{ display:false } } }
  });
</script>
