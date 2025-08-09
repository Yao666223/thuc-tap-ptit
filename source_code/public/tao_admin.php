<?php
// Chỉ nên chạy file này 1 lần rồi xóa hoặc đổi tên file để tránh bị lạm dụng
include 'db_connect.php';

$username = 'admin';
$password = 'admin123';  // Bạn có thể đổi mật khẩu nếu cần
$role = 'admin';

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Kiểm tra xem user đã tồn tại chưa
$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "🔁 Tài khoản admin đã tồn tại!";
} else {
    // Thêm admin mới
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $role);

    if ($stmt->execute()) {
        echo "✅ Tạo tài khoản admin thành công!";
    } else {
        echo "❌ Lỗi khi tạo tài khoản admin: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();
?>
