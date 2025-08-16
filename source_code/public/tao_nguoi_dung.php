<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';

// Kiểm tra quyền
if ($_SESSION['role'] !== 'admin') {
  echo "<div class='alert alert-danger m-4'>🚫 Bạn không có quyền truy cập trang này!</div>";
  include 'admin_footer.php';
  exit();
}

$success = $error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    // Kiểm tra trùng tên đăng nhập
    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $error = "❌ Tên đăng nhập đã tồn tại!";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'nhanvien')");
        $stmt->bind_param("ss", $username, $hashed);
        $stmt->execute();
        $success = "✅ Tạo tài khoản nhân viên thành công!";
    }
}
?>

<div class="container mt-4" style="max-width: 500px;">
  <h3 class="mb-4 text-primary fw-bold">👤 Tạo tài khoản nhân viên</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="post" class="border p-4 bg-white shadow-sm rounded">
    <div class="mb-3">
      <label class="form-label">Tên đăng nhập</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Mật khẩu</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-success w-100">Tạo tài khoản</button>
  </form>
</div>

<?php include '../includes/layouts/admin_footer.php'; ?>
