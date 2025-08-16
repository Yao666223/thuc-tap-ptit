<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../includes/db_connect.php'; // chỉnh đường dẫn nếu cần

// Nếu đã login -> chuyển về dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Vui lòng điền đầy đủ tên đăng nhập và mật khẩu.";
    } else {
        // Tìm user theo username (unique)
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ? LIMIT 1");
        if (!$stmt) {
            $error = "Lỗi kết nối cơ sở dữ liệu.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $res->num_rows === 1) {
                $user = $res->fetch_assoc();
                $stored = $user['password'];

                $verified = false;
                $needs_rehash = false;

                // 1) Nếu password được hash bằng password_hash()
                if (password_verify($password, $stored)) {
                    $verified = true;
                    // nếu cần rehash (ví dụ thuật toán cũ) -> set flag
                    if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                        $needs_rehash = true;
                    }
                } else {
                    // 2) thử so sánh MD5 (tương thích dữ liệu cũ)
                    if (md5($password) === $stored) {
                        $verified = true;
                        $needs_rehash = true; // nâng cấp sang password_hash()
                    }
                }

                if ($verified) {
                    // nếu cần rehash (MD5 hoặc cần nâng cấp) -> cập nhật password trong DB
                    if ($needs_rehash) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $up = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                        if ($up) {
                            $up->bind_param("si", $newHash, $user['id']);
                            $up->execute();
                            $up->close();
                        }
                    }

                    // Lưu session an toàn
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    // redirect
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "❌ Tên đăng nhập hoặc mật khẩu không đúng.";
                }
            } else {
                $error = "❌ Tên đăng nhập hoặc mật khẩu không đúng.";
            }
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Đăng nhập hệ thống kho</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container" style="max-width:420px; margin-top:60px;">
  <div class="card shadow">
    <div class="card-body">
      <h4 class="text-center text-primary mb-4">🔐 Đăng nhập hệ thống</h4>

      <?php if ($error !== ''): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="mb-3">
          <label class="form-label">Tên đăng nhập</label>
          <input type="text" name="username" class="form-control" required autofocus value="<?= isset($username) ? htmlspecialchars($username) : '' ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Mật khẩu</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <button class="btn btn-primary w-100" type="submit">Đăng nhập</button>
      </form>
    </div>
  </div>
</div>

</body>
</html>
