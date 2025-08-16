<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../includes/session_check.php';
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/layouts/admin_layout.php';

// Chỉ cho admin truy cập
if ($_SESSION['role'] !== 'admin') {
  echo "<div class='alert alert-danger m-4'>🚫 Bạn không có quyền truy cập trang này!</div>";
  include 'admin_footer.php';
  exit();
}

// Xử lý xóa
if (isset($_GET['delete'])) {
    $delete_user = $_GET['delete'];

    if ($delete_user !== $_SESSION['username']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE username = ?");
        $stmt->bind_param("s", $delete_user);
        $stmt->execute();
        header("Location: nguoi_dung.php");
        exit();
    } else {
        echo "<div class='alert alert-warning m-4'>⚠️ Bạn không thể xoá chính mình!</div>";
    }
}

// Lấy danh sách người dùng
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<div class="container mt-4">
  <h3 class="text-primary mb-4 fw-bold">👥 Quản lý người dùng</h3>

  <table class="table table-bordered bg-white shadow-sm text-center align-middle">
    <thead class="table-dark">
      <tr>
        <th>ID</th>
        <th>Tên đăng nhập</th>
        <th>Vai trò</th>
        <th>Hành động</th>
      </tr>
    </thead>
    <tbody>
      <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td class="text-start"><?= $row['username'] ?></td>
            <td><?= $row['role'] === 'admin' ? 'Quản trị viên' : 'Nhân viên' ?></td>
            <td>
            <!-- Nút sửa luôn hiện -->
            <a href="sua_nguoi_dung.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Sửa</a>

            <!-- Nút xoá chỉ hiện nếu không phải chính mình -->
            <?php if ($row['username'] !== $_SESSION['username']): ?>
                <a href="nguoi_dung.php?delete=<?= $row['username'] ?>" class="btn btn-danger btn-sm"
                onclick="return confirm('Bạn có chắc muốn xoá người dùng này không?')">Xoá</a>
            <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>

    </tbody>
  </table>
</div>

<?php include '../includes/layouts/admin_footer.php'; ?>
