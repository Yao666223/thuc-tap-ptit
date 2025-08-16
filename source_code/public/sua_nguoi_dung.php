<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';
if ($_SESSION['role'] !== 'admin') {
  echo "<div class='alert alert-danger m-4'>🚫 Bạn không có quyền truy cập trang này!</div>";
  include 'admin_footer.php';
  exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
  echo "<div class='alert alert-warning m-4'>Không tìm thấy người dùng!</div>";
  include 'admin_footer.php';
  exit();
}

// Lấy thông tin người dùng
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
  echo "<div class='alert alert-warning m-4'>Người dùng không tồn tại!</div>";
  include 'admin_footer.php';
  exit();
}

$success = $error = '';

// Xử lý cập nhật
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST['username']);
  $role = $_POST['role'];
  $password = $_POST['password'];

  // Không cho đổi role nếu đang sửa chính mình
  if (isset($_SESSION['username']) && $user['username'] === $_SESSION['username'] && $role !== 'admin')
 {
    $error = "❌ Bạn không thể tự đổi vai trò của chính mình!";
  } else {
    // Cập nhật username và role
    $stmt = $conn->prepare("UPDATE users SET username = ?, role = ? WHERE id = ?");
    $stmt->bind_param("ssi", $username, $role, $id);
    $stmt->execute();

    // Nếu có nhập mật khẩu mới, thì cập nhật
    if (!empty($password)) {
      $hashed = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
      $stmt->bind_param("si", $hashed, $id);
      $stmt->execute();
    }

    $success = "✅ Cập nhật thông tin người dùng thành công!";
  }
}
?>

<div class="container mt-4" style="max-width: 500px;">
  <h3 class="text-primary mb-4 fw-bold">✏️ Sửa người dùng</h3>

  <?php if ($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
  <?php elseif ($error): ?>
    <div class="alert alert-danger"><?= $error ?></div>
  <?php endif; ?>

  <form method="post" class="border p-4 bg-white shadow-sm rounded">
    <div class="mb-3">
      <label class="form-label">Tên đăng nhập</label>
      <input type="text" name="username" class="form-control" value="<?= $user['username'] ?>" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Vai trò</label>
      <select name="role" class="form-select">
        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Quản trị viên</option>
        <option value="nhanvien" <?= $user['role'] === 'nhanvien' ? 'selected' : '' ?>>Nhân viên</option>
      </select>
    </div>

    <div class="mb-3">
      <label class="form-label">Mật khẩu mới (nếu muốn đổi)</label>
      <input type="password" name="password" class="form-control">
    </div>

    <button type="submit" class="btn btn-success w-100">Lưu thay đổi</button>
  </form>
</div>

<?php include 'admin_footer.php'; ?>
