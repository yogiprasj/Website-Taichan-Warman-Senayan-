<?php
// admin/login.php
session_start();

// Include config dari folder includes
$config_path = dirname(__DIR__) . '/includes/config.php';
if (file_exists($config_path)) {
    include $config_path;
} else {
    die("File config.php tidak ditemukan di: " . $config_path);
}

// Redirect jika sudah login
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Buat CSRF token jika belum ada
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

// Process login dengan database
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validasi CSRF
    $posted_csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $posted_csrf)) {
        $error = 'Request tidak valid (CSRF).';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Username dan password harus diisi.';
        } else {
            try {
                // Cek di tabel admins
                $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($admin) {
                    // Verifikasi password
                    if (password_verify($password, $admin['password'])) {
                        // Login sukses
                        session_regenerate_id(true);
                        $_SESSION['admin_id'] = $admin['id'];
                        $_SESSION['admin_username'] = $admin['username'];
                        $_SESSION['admin_login_time'] = time();

                        // Reset CSRF token setelah login
                        unset($_SESSION['csrf_token']);

                        header('Location: dashboard.php');
                        exit();
                    } else {
                        $error = 'Username atau password salah.';
                    }
                } else {
                    $error = 'Username atau password salah.';
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'Terjadi error sistem. Coba lagi nanti.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login - Sate Taichan Warman Senayan</title>
    <link rel="icon" href="assets/img/LogoHome.png" type="image/png">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h1><img src="../assets/img/LogoHome.png" alt="Logo" class="login-logo"> Sate Taichan Warman Senayan</h1>
                <p>Admin Panel</p>
            </div>

            <?php if ($error): ?>
                <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="login-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input autocomplete="username" type="text" id="username" name="username" required
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input autocomplete="current-password" type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>

            <div class="login-footer">
                <p>Contact developer untuk reset password</p>
            </div>
        </div>
    </div>
</body>
</html>