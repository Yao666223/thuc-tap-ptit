<?php
include __DIR__ . '/../includes/session_check.php';
include __DIR__ . '/../includes/db_connect.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$ghi_chu = trim($_POST['ghi_chu'] ?? '');
$product_ids = $_POST['product_id'] ?? [];
$qtys = $_POST['qty'] ?? [];
$prices = $_POST['price'] ?? [];

if (empty($product_ids)) die('Không có sản phẩm để xuất.');

$conn->begin_transaction();

try {
    // tạo phieu_xuat
    $stmt = $conn->prepare("INSERT INTO phieu_xuat (ngay_tao, ghi_chu, created_by) VALUES (NOW(), ?, ?)");
    $stmt->bind_param("si", $ghi_chu, $user_id);
    $stmt->execute();
    $phieu_id = $conn->insert_id;
    $stmt->close();

    // prepare
    $stmt_insert_ct = $conn->prepare("INSERT INTO chi_tiet_phieu_xuat (id_phieu_xuat, id_san_pham, so_luong, gia) VALUES (?, ?, ?, ?)");
    $stmt_get_qty = $conn->prepare("SELECT so_luong FROM san_pham WHERE id = ? FOR UPDATE");
    $stmt_update_sp = $conn->prepare("UPDATE san_pham SET so_luong = so_luong - ? WHERE id = ?");
    $stmt_insert_sc = $conn->prepare("INSERT INTO stock_changes (san_pham_id, change_qty, change_type, ref_table, ref_id, user_id, note) VALUES (?, ?, 'xuat', 'chi_tiet_phieu_xuat', ?, ?, ?)");

    for ($i = 0; $i < count($product_ids); $i++) {
        $pid = intval($product_ids[$i]);
        $q   = intval($qtys[$i]);
        $p   = floatval($prices[$i]);

        if ($pid <= 0 || $q <= 0) continue;

        // kiểm tồn for update
        $stmt_get_qty->bind_param("i", $pid);
        $stmt_get_qty->execute();
        $res = $stmt_get_qty->get_result();
        if ($res->num_rows === 0) throw new Exception("Sản phẩm ID $pid không tồn tại.");
        $row = $res->fetch_assoc();
        $current = intval($row['so_luong']);
        if ($current < $q) {
            throw new Exception("Không thể xuất sản phẩm ID $pid: tồn $current < yêu cầu $q.");
        }

        // chèn chi_tiet_phieu_xuat
        $stmt_insert_ct->bind_param("iiid", $phieu_id, $pid, $q, $p);
        $stmt_insert_ct->execute();

        // update tồn
        $stmt_update_sp->bind_param("ii", $q, $pid);
        $stmt_update_sp->execute();

        // ghi stock_changes (ghi âm)
        $negq = -$q;
        $note = $ghi_chu;
        $stmt_insert_sc->bind_param("iiiis", $pid, $negq, $phieu_id, $user_id, $note);
        $stmt_insert_sc->execute();
    }

    $stmt_insert_ct->close();
    $stmt_get_qty->close();
    $stmt_update_sp->close();
    $stmt_insert_sc->close();

    $conn->commit();
    header("Location: phieu_xuat_list.php?success=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    error_log("Lỗi lưu phieu_xuat: " . $e->getMessage());
    echo "Lỗi khi lưu phiếu xuất: " . htmlspecialchars($e->getMessage());
    exit();
}
