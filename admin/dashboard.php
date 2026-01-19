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

// Get selected location from filter or default to "galaxy 1"
$selected_location = isset($_GET['location']) ? $_GET['location'] : 'galaxy 1';

// Get latest 3 orders for selected location
$orders_query = $pdo->prepare("
    SELECT o.*, l.name as location_name 
    FROM orders o 
    LEFT JOIN locations l ON o.location_id = l.id 
    WHERE l.name = ? 
    ORDER BY o.created_at DESC 
    LIMIT 3
");
$orders_query->execute([$selected_location]);
$orders_result = $orders_query->fetchAll(PDO::FETCH_ASSOC);

// Get completed orders for sales report
$sales_query = $pdo->prepare("
    SELECT o.*, l.name as location_name 
    FROM orders o 
    LEFT JOIN locations l ON o.location_id = l.id 
    WHERE o.is_completed = 1 AND l.name = ?
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$sales_query->execute([$selected_location]);
$sales_result = $sales_query->fetchAll(PDO::FETCH_ASSOC);

// Get all active locations for filter
$locations_query = $pdo->query("SELECT * FROM locations WHERE is_active = 1");
$locations = $locations_query->fetchAll(PDO::FETCH_ASSOC);

// Get web content for preview
$content_query = $pdo->query("SELECT * FROM website_content");
$web_content = $content_query->fetchAll(PDO::FETCH_ASSOC);

// Convert to associative array for easy access
$content_data = [];
foreach ($web_content as $content) {
    $content_data[$content['content_key']] = $content['content_value'];
}

// Display message jika ada
if (isset($_SESSION['message'])) {
    echo '<div class="message success">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="icon" href="assets/img/LogoHome.png" type="image/png">
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

        <!-- Choose Location Section -->
        <section class="location-section">
            <h2>Pilih Lokasi Cabang</h2>
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
        </section>

        <!-- Web Content Preview Section -->
        <section class="content-preview">
            <div class="section-header">
                <h2>Web Content</h2>
                <a href="content.php" class="btn-edit">Edit</a>
            </div>
            <div class="preview-grid">
                <div class="preview-item">
                    <strong>Home Image:</strong> 
                    <span><?= htmlspecialchars($content_data['home_image'] ?? 'Not set') ?></span>
                </div>
                <div class="preview-item">
                    <strong>Home Title:</strong> 
                    <span><?= htmlspecialchars($content_data['home_title'] ?? 'Not set') ?></span>
                </div>
                <div class="preview-item">
                    <strong>Home Description:</strong> 
                    <span><?= htmlspecialchars($content_data['home_description'] ?? 'Not set') ?></span>
                </div>
                <div class="preview-item">
                    <strong>Price List Image:</strong> 
                    <span><?= htmlspecialchars($content_data['price_list_image'] ?? 'Not set') ?></span>
                </div>
            </div>
        </section>

        <!-- Orders Preview Section -->
        <section class="orders-preview">
            <div class="section-header">
                <h2>Orderan Terbaru - <?= htmlspecialchars($selected_location) ?></h2>
                <a href="orders.php?location=<?= urlencode($selected_location) ?>" class="btn-see-more">See More</a>
            </div>
    
    <?php if (count($orders_result) > 0): ?>
        <div class="table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Porsi Sate</th>
                        <th>Lontong</th>
                        <th>Total Harga</th>
                        <th>Completed</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $counter = 1; ?>
                    <?php foreach($orders_result as $order): 
                        $total_sate = $order['daging_qty'] + $order['kulit_qty'] + $order['campur_qty'];
                        $total_harga = ($total_sate * 25000) + ($order['lontong_qty'] * 5000);
                    ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= htmlspecialchars($order['customer_name']) ?></td>
                            <td><?= $total_sate ?></td>
                            <td><?= $order['lontong_qty'] ?></td>
                            <td>Rp <?= number_format($total_harga, 0, ',', '.') ?></td>
                            <td>
                                <form method="POST" action="update_order_simple.php" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="is_completed" value="<?= $order['is_completed'] ? 0 : 1 ?>">
                                    <input type="checkbox" <?= $order['is_completed'] ? 'checked' : '' ?> 
                                        onchange="this.form.submit()">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="no-data">Tidak ada order untuk lokasi ini</p>
    <?php endif; ?>
</section>
        <!-- Sales Report Preview Section -->
        <section class="sales-preview">
            <div class="section-header">
                <h2>Laporan Penjualan - <?= htmlspecialchars($selected_location) ?></h2>
                <a href="laporan.php?location=<?= urlencode($selected_location) ?>" class="btn-see-more">See More</a>
            </div>
            
            <?php if (count($sales_result) > 0): ?>
                <div class="table-container">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Nama</th>
                                <th>Porsi Sate</th>
                                <th>Lontong</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($sales_result as $sale): 
                                $total_sate = $sale['daging_qty'] + $sale['kulit_qty'] + $sale['campur_qty'];
                                $order_date = date('d/m/Y', strtotime($sale['created_at']));
                                $order_time = date('H:i', strtotime($sale['created_at']));
                            ?>
                                <tr>
                                    <td><?= $order_date ?></td>
                                    <td><?= $order_time ?></td>
                                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                    <td><?= $total_sate ?></td>
                                    <td><?= $sale['lontong_qty'] ?></td>
                                    <td><?= $sale['location_name'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="no-data">Tidak ada laporan penjualan</p>
            <?php endif; ?>
        </section>
    </div>
    

    <script src="js/admin.js"></script>
</body>
</html>