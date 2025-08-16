<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';

// *** EXPORT HANDLER***
$can_export = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$input_from = $_GET['from'] ?? date('Y-m-d', strtotime('-11 months'));
$input_to   = $_GET['to']   ?? date('Y-m-d');

if ($can_export && isset($_GET['export']) && in_array($_GET['export'], ['csv','excel'])) {
    $efrom = $_GET['from'] ?? $input_from;
    $eto   = $_GET['to'] ?? $input_to;
    $from_dt = $efrom . ' 00:00:00';
    $to_dt   = $eto   . ' 23:59:59';

    // Lấy business data: doanh thu theo tháng
    $stmt = $conn->prepare("
      SELECT DATE_FORMAT(p.ngay_tao, '%Y-%m') AS thang, COALESCE(SUM(ct.so_luong * ct.gia),0) AS doanh_thu
      FROM phieu_xuat p
      JOIN chi_tiet_phieu_xuat ct ON p.id = ct.id_phieu_xuat
      WHERE p.ngay_tao BETWEEN ? AND ?
      GROUP BY thang
      ORDER BY thang ASC
    ");
    $stmt->bind_param("ss", $from_dt, $to_dt);
    $stmt->execute();
    $rows = $stmt->get_result();

    $type = $_GET['export'];
    $filename = "doanhthu_{$efrom}_to_{$eto}." . ($type === 'excel' ? 'xls' : 'csv');

    if ($type === 'excel') {
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
    }
    header("Content-Disposition: attachment; filename=\"$filename\"");
    echo "\xEF\xBB\xBF"; // BOM để Excel nhận UTF-8

    if ($type === 'excel') {
        echo "Tháng\tDoanh thu (VNĐ)\n";
        while ($r = $rows->fetch_assoc()) {
            echo "{$r['thang']}\t{$r['doanh_thu']}\n";
        }
    } else {
        $out = fopen('php://output','w');
        fputcsv($out, ['Tháng','Doanh thu (VNĐ)']);
        while ($r = $rows->fetch_assoc()) {
            fputcsv($out, [$r['thang'], $r['doanh_thu']]);
        }
        fclose($out);
    }
    exit(); // rất quan trọng
}

// Hàm tiện ích: tạo danh sách tháng (YYYY-MM) từ $from tới $to
function months_between($from, $to) {
    $out = [];
    $current = (new DateTime($from))->modify('first day of this month');
    $end = (new DateTime($to))->modify('first day of this month');
    while ($current <= $end) {
        $out[] = $current->format('Y-m');
        $current->modify('+1 month');
    }
    return $out;
}

// --- Lấy filter from/to từ query, mặc định 12 tháng gần nhất
$input_from = $_GET['from'] ?? null;
$input_to   = $_GET['to'] ?? null;

if (!$input_to) $input_to = date('Y-m-d');
if (!$input_from) $input_from = date('Y-m-d', strtotime('-11 months', strtotime($input_to))); // 12 tháng

// chuẩn hoá full datetime
$from_dt = $input_from . ' 00:00:00';
$to_dt   = $input_to . ' 23:59:59';

// --- KPI: tổng nhập (cost) và tổng xuất (revenue) trong khoảng filter
// Tổng giá trị nhập (cost) trong khoảng
$stmt = $conn->prepare("
  SELECT COALESCE(SUM(ct.so_luong * ct.gia),0) AS total_cost
  FROM chi_tiet_nhap ct
  JOIN phieu_nhap p ON ct.id_phieu_nhap = p.id
  WHERE p.ngay_tao BETWEEN ? AND ?
");
$stmt->bind_param("ss", $from_dt, $to_dt);
$stmt->execute();
$total_cost = (float)$stmt->get_result()->fetch_assoc()['total_cost'];
$stmt->close();

// Tổng giá trị xuất (revenue) trong khoảng
$stmt = $conn->prepare("
  SELECT COALESCE(SUM(ct.so_luong * ct.gia),0) AS total_revenue
  FROM chi_tiet_phieu_xuat ct
  JOIN phieu_xuat p ON ct.id_phieu_xuat = p.id
  WHERE p.ngay_tao BETWEEN ? AND ?
");
$stmt->bind_param("ss", $from_dt, $to_dt);
$stmt->execute();
$total_revenue = (float)$stmt->get_result()->fetch_assoc()['total_revenue'];
$stmt->close();

$profit = $total_revenue - $total_cost;

// Tổng giá trị tồn hiện tại (tính theo san_pham.so_luong * gia hiện tại)
$stmt = $conn->prepare("SELECT COALESCE(SUM(so_luong * gia),0) FROM san_pham");
$stmt->execute();
$total_stock_value = (float)$stmt->get_result()->fetch_row()[0];
$stmt->close();

// Sản phẩm sắp hết (ngưỡng có thể chỉnh)
$low_threshold = intval($_GET['threshold'] ?? 30);
$stmt = $conn->prepare("SELECT id, ten_san_pham, so_luong, don_vi, gia FROM san_pham WHERE so_luong <= ? ORDER BY so_luong ASC LIMIT 200");
$stmt->bind_param("i", $low_threshold);
$stmt->execute();
$low_rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- Doanh thu theo tháng (điền zero cho tháng không có dữ liệu)
// Lấy dữ liệu grouped từ DB
$stmt = $conn->prepare("
  SELECT DATE_FORMAT(p.ngay_tao, '%Y-%m') AS thang, COALESCE(SUM(ct.so_luong * ct.gia),0) AS doanh_thu
  FROM phieu_xuat p
  JOIN chi_tiet_phieu_xuat ct ON p.id = ct.id_phieu_xuat
  WHERE p.ngay_tao BETWEEN ? AND ?
  GROUP BY thang
  ORDER BY thang ASC
");
$stmt->bind_param("ss", $from_dt, $to_dt);
$stmt->execute();
$res_monthly = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// map kết quả theo tháng
$months = months_between($input_from, $input_to);
$monthly_map = array_fill_keys($months, 0.0);
foreach ($res_monthly as $r) {
    $monthly_map[$r['thang']] = (float)$r['doanh_thu'];
}
$monthly_labels = array_keys($monthly_map);
$monthly_values = array_values($monthly_map);

// --- Export CSV/Excel (chỉ admin)
// export types: csv (text/csv) or excel (xls as tab-separated)
$can_export = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
if ($can_export && isset($_GET['export']) && in_array($_GET['export'], ['csv','excel'])) {
    $type = $_GET['export'];
    $filename = "doanhthu_{$input_from}_to_{$input_to}." . ($type === 'excel' ? 'xls' : 'csv');

    if ($type === 'excel') {
        header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    } else {
        header('Content-Type: text/csv; charset=UTF-8');
    }
    header("Content-Disposition: attachment; filename=\"$filename\"");
    // BOM để Excel nhận UTF-8
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output','w');
    // header
    fputcsv($out, ['Tháng','Doanh thu (VNĐ)'], $type === 'excel' ? "\t" : ",");
    foreach ($monthly_labels as $i => $lab) {
        $val = $monthly_values[$i];
        // format raw number (Excel can format), but we output numeric
        if ($type === 'excel') {
            // tab separated
            fwrite($out, $lab . "\t" . $val . "\n");
        } else {
            fputcsv($out, [$lab, $val]);
        }
    }
    fclose($out);
    exit();
}

$self = htmlspecialchars($_SERVER['SCRIPT_NAME']); // ví dụ "/mini-warehouse/source_code/public/thongke.php"
$from_val = htmlspecialchars($input_from);
$to_val = htmlspecialchars($input_to);
$can_export = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// --- HTML render ---
?>
<div class="container mt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>📊 Thống kê - Doanh thu theo tháng</h4>
    <div>
      <?php if ($can_export): ?>
        <a class="btn btn-sm btn-success" href="thong_ke.php?export=excel&from=<?= htmlspecialchars($input_from) ?>&to=<?= htmlspecialchars($input_to) ?>">⬇️ Xuất Excel</a>
        <a class="btn btn-sm btn-outline-success" href="thong_ke.php?export=csv&from=<?= htmlspecialchars($input_from) ?>&to=<?= htmlspecialchars($input_to) ?>">⬇️ Xuất CSV</a>
      <?php endif; ?>
    </div>
  </div>

  <!-- Filter -->
  <form class="row g-2 mb-3" method="get" action="thong_ke.php">
    <div class="col-auto">
      <label class="form-label small mb-1">Từ ngày</label>
      <input type="date" name="from" class="form-control form-control-sm" value="<?= htmlspecialchars($input_from) ?>">
    </div>
    <div class="col-auto">
      <label class="form-label small mb-1">Đến ngày</label>
      <input type="date" name="to" class="form-control form-control-sm" value="<?= htmlspecialchars($input_to) ?>">
    </div>
    <div class="col-auto align-self-end">
      <button class="btn btn-primary btn-sm">Áp dụng</button>
    </div>
    <div class="col-auto align-self-end">
      <a class="btn btn-outline-secondary btn-sm" href="thong_ke.php">Mặc định 12 tháng</a>
    </div>
  </form>

  <!-- KPI -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <div class="small text-muted">Doanh thu (khoảng)</div>
        <div class="h5 text-success"><?= number_format($total_revenue,0,',','.') ?> đ</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <div class="small text-muted">Chi phí nhập (khoảng)</div>
        <div class="h5 text-danger"><?= number_format($total_cost,0,',','.') ?> đ</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <div class="small text-muted">Lợi nhuận (ước tính)</div>
        <div class="h5 text-primary"><?= number_format($profit,0,',','.') ?> đ</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card p-3 shadow-sm">
        <div class="small text-muted">Tổng giá trị tồn</div>
        <div class="h5"><?= number_format($total_stock_value,0,',','.') ?> đ</div>
      </div>
    </div>
  </div>

  <!-- Chart: monthly revenue -->
  <div class="card mb-4">
    <div class="card-body">
      <h6>📆 Doanh thu theo tháng (<?= htmlspecialchars($input_from) ?> → <?= htmlspecialchars($input_to) ?>)</h6>
      <canvas id="chartMonthly" height="120"></canvas>
    </div>
  </div>

  <!-- Monthly table -->
  <div class="card mb-4">
    <div class="card-body">
      <h6 class="mb-3">Bảng doanh thu theo tháng</h6>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr><th>Tháng</th><th class="text-end">Doanh thu (VNĐ)</th></tr>
          </thead>
          <tbody>
            <?php foreach ($monthly_labels as $i => $lab): ?>
              <tr>
                <td><?= htmlspecialchars($lab) ?></td>
                <td class="text-end"><?= number_format($monthly_values[$i],0,',','.') ?> đ</td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr><th>Tổng</th><th class="text-end"><?= number_format(array_sum($monthly_values),0,',','.') ?> đ</th></tr>
          </tfoot>
        </table>
      </div>
    </div>
  </div>

  <!-- Low stock -->
  <div class="card mb-5">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6>⚠️ Sản phẩm sắp hết (<= <?= $low_threshold ?>)</h6>
        <form method="get" class="d-flex align-items-center" style="gap:8px">
          <input type="hidden" name="from" value="<?= htmlspecialchars($input_from) ?>">
          <input type="hidden" name="to" value="<?= htmlspecialchars($input_to) ?>">
          <input type="number" class="form-control form-control-sm" name="threshold" value="<?= $low_threshold ?>" style="width:90px">
          <button class="btn btn-sm btn-outline-primary">Cập nhật</button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead class="table-light"><tr><th>#</th><th>Sản phẩm</th><th class="text-center">Tồn</th><th>Đơn vị</th><th class="text-end">Giá</th></tr></thead>
          <tbody>
            <?php if(empty($low_rows)): ?>
              <tr><td colspan="5" class="text-center">Không có sản phẩm sắp hết</td></tr>
            <?php else: foreach($low_rows as $i=>$r): ?>
              <tr>
                <td><?= $i+1 ?></td>
                <td><?= htmlspecialchars($r['ten_san_pham']) ?></td>
                <td class="text-center"><?= intval($r['so_luong']) ?></td>
                <td><?= htmlspecialchars($r['don_vi']) ?></td>
                <td class="text-end"><?= number_format($r['gia'],0,',','.') ?> đ</td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div> <!-- /container -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode($monthly_labels, JSON_UNESCAPED_UNICODE) ?>;
const dataVals = <?= json_encode($monthly_values) ?>;

const ctx = document.getElementById('chartMonthly').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: labels,
    datasets: [{
      label: 'Doanh thu (VNĐ)',
      data: dataVals,
      backgroundColor: 'rgba(54,162,235,0.7)'
    }]
  },
  options: {
    responsive: true,
    scales: { y: { beginAtZero: true, ticks: { callback: function(value){ return value.toLocaleString('en-US') + ' đ'; } } } },
    plugins: {
      tooltip: {
        callbacks: {
          label: function(context) {
            return context.parsed.y.toLocaleString('en-US') + ' đ';
          }
        }
      }
    }
  }
});
</script>