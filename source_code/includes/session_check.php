<?php
// includes/session_check.php
// Kiểm tra session và quyền truy cập trang (gọi ở đầu các trang cần login)

// gọi session nếu chưa có
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// nếu muốn kiểm tra login (chặn truy cập nếu chưa login)
if (!isset($_SESSION['user_id'])) {
    // nếu file login nằm ở root public, chỉnh đường dẫn phù hợp
    header('Location: /mini-warehouse/source_code/public/login.php'); // Chỉnh lại đường dẫn nếu cần
    exit();
}
