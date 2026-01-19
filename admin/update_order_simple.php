<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include config dari folder includes
$config_path = dirname(__DIR__) . '/includes/config.php';
if (file_exists($config_path)) {
    include $config_path;
} else {
    die("File config.php tidak ditemukan di: " . $config_path);
}

$order_id = $_POST['order_id'] ?? null;
$is_completed = $_POST['is_completed'] ?? 0;

if ($order_id) {
    try {
        $stmt = $pdo->prepare("UPDATE orders SET is_completed = ? WHERE id = ?");
        $stmt->execute([$is_completed, $order_id]);
        
        // Success message bisa ditambahkan di session jika perlu
        $_SESSION['message'] = "Status order berhasil diupdate!";
    } catch (Exception $e) {
        $_SESSION['message'] = "Error: " . $e->getMessage();
    }
}

// Redirect back to dashboard dengan location filter yang sama
$location = $_GET['location'] ?? 'galaxy 1';
header("Location: dashboard.php?location=" . urlencode($location));
exit;
?>