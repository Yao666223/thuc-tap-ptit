<?php
$host = "localhost";
$user = "root";
$pass = ""; // Nếu có mật khẩu thì điền vào đây
$db = "mini_warehouse";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
?>
