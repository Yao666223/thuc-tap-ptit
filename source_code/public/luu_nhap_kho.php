<?php
//lưu phiếu nhập, cập nhật san_pham và ghi stock_changes
include __DIR__ . '/../includes/session_check.php';
include __DIR__ . '/../includes/db_connect.php';

// bật báo lỗi mysqli (tùy dev)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$ghi_chu = trim($_POST['ghi_chu'] ?? '');
$product_ids = $_POST['product_id'] ?? [];
$qtys = $_POST['qty'] ?? [];
$prices = $_POST['price'] ?? [];

if (empty($product_ids) || count($product_ids) === 0) {
    die('Không có sản phẩm để lưu phiếu nhập.');
}

$conn->begin_transaction();

try {
    // Tạo phieu_nhap với created_by
    $stmt = $conn->prepare("INSERT INTO phieu_nhap (ngay_tao, ghi_chu, created_by) VALUES (NOW(), ?, ?)");
    $stmt->bind_param("si", $ghi_chu, $user_id);
    $stmt->execute();
    $phieu_id = $conn->insert_id;
    $stmt->close();

    // prepare các statement dùng lặp
    $stmt_insert_ct = $conn->prepare("INSERT INTO chi_tiet_nhap (id_phieu_nhap, id_san_pham, so_luong, gia) VALUES (?, ?, ?, ?)");
    $stmt_update_sp = $conn->prepare("UPDATE san_pham SET so_luong = so_luong + ?, gia = ? WHERE id = ?");
    $stmt_insert_sc = $conn->prepare("INSERT INTO stock_changes (san_pham_id, change_qty, change_type, ref_table, ref_id, user_id, note) VALUES (?, ?, 'nhap', 'chi_tiet_nhap', ?, ?, ?)");

    for ($i = 0; $i < count($product_ids); $i++) {
        $pid = intval($product_ids[$i]);
        $q   = intval($qtys[$i]);
        $p   = floatval($prices[$i]);

        if ($pid <= 0 || $q <= 0) continue;

        // chèn chi_tiet_nhap
        $stmt_insert_ct->bind_param("iiid", $phieu_id, $pid, $q, $p);
        $stmt_insert_ct->execute();

        // cập nhật tồn và giá
        $stmt_update_sp->bind_param("idi", $q, $p, $pid);
        $stmt_update_sp->execute();

        // ghi stock_changes (types: int,int,int,int,string => "iiiis")
        $note = $ghi_chu;
        $stmt_insert_sc->bind_param("iiiis", $pid, $q, $phieu_id, $user_id, $note);
        $stmt_insert_sc->execute();
    }

    $stmt_insert_ct->close();
    $stmt_update_sp->close();
    $stmt_insert_sc->close();

    $conn->commit();
    header("Location: phieu_nhap_list.php?success=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Lỗi lưu phieu_nhap: " . $e->getMessage());
    echo "Lỗi khi lưu phiếu nhập: " . htmlspecialchars($e->getMessage());
    exit();
}
