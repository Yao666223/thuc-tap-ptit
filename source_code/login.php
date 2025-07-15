<?php
session_start();
include 'db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // Lưu session
            $_SESSION['user'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Chuyển đến dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Sai mật khẩu!";
        }
    } else {
        $error = "❌ Tài khoản không tồn tại!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <title>Đăng nhập hệ thống kho</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container" style="max-width: 420px; margin-top: 60px;">
  <div class="card shadow">
    <div class="card-body">
      <h4 class="text-center text-primary mb-4">🔐 Đăng nhập hệ thống</h4>

      <?php if (!empty($error)): ?>
        <div class="alert alert-danger text-center"><?= $error ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label">Tên đăng nhập</label>
          <input type="text" name="username" class="form-control" required autofocus>
        </div>
        <div class="mb-3">
          <label class="form-label">Mật khẩu</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
      </form>
    </div>
  </div>

  <p class="text-center text-muted mt-3 small">Tài khoản mẫu: admin / admin123, nv1 / nv123</p>
</div>

</body>
</html>
