<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check session
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

// Get all active locations for filter
$locations_query = $pdo->query("SELECT * FROM locations WHERE is_active = 1");
$locations = $locations_query->fetchAll(PDO::FETCH_ASSOC);

// Get selected location from filter or default to FIRST active location
if (isset($_GET['location'])) {
    $selected_location = $_GET['location'];
} else {
    // Default ke lokasi pertama yang aktif
    $selected_location = $locations[0]['name'] ?? 'Sate Taichan Warman Galaxy 1';
}

// Get date filter
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Get location ID dari nama lokasi yang dipilih
$location_id = null;
foreach ($locations as $loc) {
    if ($loc['name'] === $selected_location) {
        $location_id = $loc['id'];
        break;
    }
}

// Get all orders for selected location - TANPA FILTER DATE
if ($location_id) {
    $orders_query = $pdo->prepare("
        SELECT o.*, l.name as location_name 
        FROM orders o 
        LEFT JOIN locations l ON o.location_id = l.id 
        WHERE o.location_id = ?
        ORDER BY o.created_at ASC ");
    $orders_query->execute([$location_id]);
    $orders_result = $orders_query->fetchAll(PDO::FETCH_ASSOC);

    // Calculate daily revenue - INI TETAP PAKAI DATE FILTER
    $revenue_query = $pdo->prepare("
        SELECT 
            COALESCE(SUM((daging_qty + kulit_qty + campur_qty) * 25000 + lontong_qty * 5000), 0) as total_revenue,
            COALESCE(COUNT(*), 0) as total_orders,
            COALESCE(SUM(is_completed), 0) as completed_orders
        FROM orders 
        WHERE location_id = ? AND DATE(created_at) = ?
    ");
        $revenue_query->execute([$location_id, $selected_date]);
        $revenue_data = $revenue_query->fetch(PDO::FETCH_ASSOC);
    } else {
        $orders_result = [];
        $revenue_data = ['total_revenue' => 0, 'total_orders' => 0, 'completed_orders' => 0];
}

$daily_revenue = $revenue_data['total_revenue'];
$daily_orders = $revenue_data['total_orders'];
$daily_completed = $revenue_data['completed_orders'];

// Process order completion update
if (isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $is_completed = isset($_POST['is_completed']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET is_completed = ? WHERE id = ?");
        $stmt->execute([$is_completed, $order_id]);
        
        $_SESSION['message'] = "Status order berhasil diupdate!";
        $_SESSION['message_type'] = "success";
        
        header("Location: orders.php?location=" . urlencode($selected_location) . "&date=" . urlencode($selected_date));
        exit;
    } catch (Exception $e) {
        $_SESSION['message'] = "Error update status: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
}

// Process order deletion
if (isset($_POST['delete_order'])) {
    $order_id = $_POST['order_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        
        $_SESSION['message'] = "Order berhasil dihapus!";
        $_SESSION['message_type'] = "success";
        
        header("Location: orders.php?location=" . urlencode($selected_location) . "&date=" . urlencode($selected_date));
        exit;
    } catch (Exception $e) {
        $_SESSION['message'] = "Error hapus order: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/orders.css">
</head>
<body>
       <!-- NAVBAR -->
       <nav class="main-navbar">
        <div class="nav-brand">
            <img src="../assets/img/LogoHome.png" alt="Logo" class="nav-logo-img">
            <div class="nav-title-group">
                <span class="nav-title">Sate Taichan Warman Senayan</span>
                <span class="nav-subtitle">Admin Dashboard</span>
            </div>
        </div>
        
        <div class="nav-links">
            <?php 
                $current_page = basename($_SERVER['PHP_SELF']);
                $location_param = isset($locations[0]['name']) ? urlencode($locations[0]['name']) : 'galaxy%201';?>
        
            <?php if ($current_page != 'dashboard.php'): ?>
                <a href="dashboard.php" class="nav-link">Dashboard</a>
            <?php endif; ?>
        
                <a href="content.php" class="nav-link <?= $current_page == 'content.php' ? 'nav-active' : '' ?>">Web Content</a>
                <a href="orders.php?location=<?= $location_param ?>" class="nav-link <?= $current_page == 'orders.php' ? 'nav-active' : '' ?>">Orders</a>
                <a href="laporan.php?location=<?= $location_param ?>" class="nav-link <?= $current_page == 'laporan.php' ? 'nav-active' : '' ?>">Laporan</a>
                <a href="logout.php" class="nav-link nav-logout">Logout</a>
        </div>
    </nav>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="message <?= $_SESSION['message_type'] ?>"><?= $_SESSION['message'] ?></div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <!-- Location Filter -->
        <section class="location-section">
            <h2>Filter Lokasi</h2>
            <form method="GET" class="location-filter">
                <select name="location" onchange="this.form.submit()">
                    <?php foreach($locations as $location): ?>
                        <option value="<?= $location['name'] ?>" 
                            <?= $location['name'] == $selected_location ? 'selected' : '' ?>>
                            <?= $location['name'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <div class="location-info">
                Menampilkan order untuk: <strong><?= htmlspecialchars($selected_location) ?></strong>
            </div>
        </section>

        <!-- Daily Revenue & Date Filter -->
        <section class="revenue-section">
            <div class="section-header">
                <h2>Daily Revenue Report</h2>
                <form method="GET" class="date-filter-form">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($selected_location) ?>">
                    <input type="date" name="date" value="<?= htmlspecialchars($selected_date) ?>" 
                           onchange="this.form.submit()" max="<?= date('Y-m-d') ?>">
                </form>
            </div>
            
            <div class="revenue-cards">
                <div class="revenue-card">
                    <div class="revenue-icon">💰</div>
                    <div class="revenue-info">
                        <div class="revenue-label">Total Pendapatan</div>
                        <div class="revenue-amount">Rp <?= number_format($daily_revenue, 0, ',', '.') ?></div>
                    </div>
                </div>
                
                <div class="revenue-card">
                    <div class="revenue-icon">📦</div>
                    <div class="revenue-info">
                        <div class="revenue-label">Total Orders</div>
                        <div class="revenue-amount"><?= $daily_orders ?> orders</div>
                    </div>
                </div>
                
                <div class="revenue-card">
                    <div class="revenue-icon">✅</div>
                    <div class="revenue-info">
                        <div class="revenue-label">Orders Selesai</div>
                        <div class="revenue-amount"><?= $daily_completed ?> orders</div>
                    </div>
                </div>
                
                <div class="revenue-card">
                    <div class="revenue-icon">⏳</div>
                    <div class="revenue-info">
                        <div class="revenue-label">Orders Pending</div>
                        <div class="revenue-amount"><?= $daily_orders - $daily_completed ?> orders</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Orders Table -->
        <section class="orders-section">
            <div class="section-header">
                <h2>All Orders - <?= htmlspecialchars($selected_location) ?></h2>
                <div class="orders-stats">
                    <span class="stat-total">Total: <?= count($orders_result) ?> orders</span>
                    <span class="stat-pending">Pending: <?= count(array_filter($orders_result, fn($order) => !$order['is_completed'])) ?></span>
                    <span class="stat-completed">Completed: <?= count(array_filter($orders_result, fn($order) => $order['is_completed'])) ?></span>
                </div>
            </div>
            
            <?php if (count($orders_result) > 0): ?>
                <div class="table-container">
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Nama Customer</th>
                                <th>Detail Order</th>
                                <th>Total Harga</th>
                                <th>Catatan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; ?>
                            <?php foreach($orders_result as $order): 
                                $total_sate = $order['daging_qty'] + $order['kulit_qty'] + $order['campur_qty'];
                                $total_harga = ($total_sate * 25000) + ($order['lontong_qty'] * 5000);
                                $order_date = date('d/m/Y', strtotime($order['created_at']));
                                $order_time = date('H:i', strtotime($order['created_at']));
                            ?>
                                <tr class="<?= $order['is_completed'] ? 'completed' : 'pending' ?>">
                                    <td><?= $counter++ ?></td>
                                    <td>
                                        <div class="order-date"><?= $order_date ?></div>
                                        <div class="order-time"><?= $order_time ?></div>
                                    </td>
                                    <td class="customer-name"><?= htmlspecialchars($order['customer_name']) ?></td>
                                    <td class="order-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Daging:</span>
                                            <span class="detail-value"><?= $order['daging_qty'] ?> porsi</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Kulit:</span>
                                            <span class="detail-value"><?= $order['kulit_qty'] ?> porsi</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Campur:</span>
                                            <span class="detail-value"><?= $order['campur_qty'] ?> porsi</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Lontong:</span>
                                            <span class="detail-value"><?= $order['lontong_qty'] ?> porsi</span>
                                        </div>
                                        <div class="detail-total">
                                            <strong>Total Sate: <?= $total_sate ?> porsi</strong>
                                        </div>
                                    </td>
                                    <td class="total-price">
                                        Rp <?= number_format($total_harga, 0, ',', '.') ?>
                                    </td>
                                    <td class="order-notes">
                                        <?= $order['catatan'] ? htmlspecialchars($order['catatan']) : '<em>No notes</em>' ?>
                                    </td>
                                    <td class="order-status">
                                        <form method="POST" class="status-form">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <label class="status-toggle">
                                                <input type="checkbox" name="is_completed" 
                                                    <?= $order['is_completed'] ? 'checked' : '' ?>
                                                    onchange="this.form.submit()">
                                                <span class="toggle-slider"></span>
                                                <span class="status-text">
                                                    <?= $order['is_completed'] ? 'Completed' : 'Pending' ?>
                                                </span>
                                            </label>
                                            <input type="hidden" name="update_order_status" value="1">
                                        </form>
                                    </td>
                                    <td class="order-actions">
                                        <form method="POST" class="delete-form" onsubmit="return confirmDelete('<?= htmlspecialchars($order['customer_name']) ?>')">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <button type="submit" name="delete_order" class="btn-delete" title="Hapus Order">
                                                🗑️
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-orders">
                    <div class="no-orders-icon">📝</div>
                    <h3>Tidak ada order untuk lokasi ini</h3>
                    <p>Belum ada order yang diterima untuk <?= htmlspecialchars($selected_location) ?></p>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <script>
    function confirmDelete(customerName) {
        return confirm('Apakah Anda yakin ingin menghapus order dari ' + customerName + '?');
    }
    </script>
</body>
</html>