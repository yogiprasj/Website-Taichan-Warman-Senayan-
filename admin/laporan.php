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

// Get filters
$selected_location = isset($_GET['location']) ? $_GET['location'] : 'Sate Taichan Warman Galaxy 1';
$date_range = isset($_GET['date_range']) ? $_GET['date_range'] : 'today';
$custom_start = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
$custom_end = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Calculate date range
switch ($date_range) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        $end_date = date('Y-m-d');
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        $end_date = date('Y-m-d');
        break;
    case 'custom':
        $start_date = $custom_start;
        $end_date = $custom_end;
        break;
    default: // today
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d');
}

// Get sales analytics data
$analytics_query = $pdo->prepare("
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM((daging_qty + kulit_qty + campur_qty) * 25000 + lontong_qty * 5000), 0) as total_revenue,
        COALESCE(SUM(daging_qty), 0) as total_daging,
        COALESCE(SUM(kulit_qty), 0) as total_kulit,
        COALESCE(SUM(campur_qty), 0) as total_campur,
        COALESCE(SUM(lontong_qty), 0) as total_lontong,
        COALESCE(AVG((daging_qty + kulit_qty + campur_qty) * 25000 + lontong_qty * 5000), 0) as avg_order_value,
        COALESCE(SUM(is_completed), 0) as completed_orders
    FROM orders o 
    LEFT JOIN locations l ON o.location_id = l.id 
    WHERE l.name = ? AND DATE(o.created_at) BETWEEN ? AND ?
");
$analytics_query->execute([$selected_location, $start_date, $end_date]);
$analytics = $analytics_query->fetch(PDO::FETCH_ASSOC);

// ===== Produk Terlaris =====
$products = [
    'Sate Daging' => (int)$analytics['total_daging'],
    'Sate Kulit'  => (int)$analytics['total_kulit'],
    'Sate Campur' => (int)$analytics['total_campur'],
    'Lontong'     => (int)$analytics['total_lontong'],
];

arsort($products); // urutkan dari qty terbesar

$best_product_name = key($products);
$best_product_qty  = current($products);

// Guard kalau belum ada penjualan sama sekali
if ($best_product_qty === 0) {
    $best_product_name = 'Belum ada penjualan';
}


// Get daily revenue trend
$trend_query = $pdo->prepare("
    SELECT 
        DATE(o.created_at) as order_date,
        COALESCE(SUM((daging_qty + kulit_qty + campur_qty) * 25000 + lontong_qty * 5000), 0) as daily_revenue,
        COUNT(*) as daily_orders
    FROM orders o 
    LEFT JOIN locations l ON o.location_id = l.id 
    WHERE l.name = ? AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY DATE(o.created_at)
    ORDER BY order_date
");
$trend_query->execute([$selected_location, $start_date, $end_date]);
$revenue_trend = $trend_query->fetchAll(PDO::FETCH_ASSOC);

// Get completed orders for the report
$sales_query = $pdo->prepare("
    SELECT o.*, l.name as location_name 
    FROM orders o 
    LEFT JOIN locations l ON o.location_id = l.id 
    WHERE o.is_completed = 1 AND l.name = ? AND DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at ASC
");
$sales_query->execute([$selected_location, $start_date, $end_date]);
$sales_result = $sales_query->fetchAll(PDO::FETCH_ASSOC);

// Get all active locations for filter
$locations_query = $pdo->query("SELECT * FROM locations WHERE is_active = 1");
$locations = $locations_query->fetchAll(PDO::FETCH_ASSOC);

// Calculate completion rate
$completion_rate = $analytics['total_orders'] > 0 ? 
    round(($analytics['completed_orders'] / $analytics['total_orders']) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/laporan.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        <!-- Filters Section -->
        <section class="filters-section">
            <h2>Filter Laporan</h2>
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label>Lokasi:</label>
                    <select name="location" onchange="this.form.submit()">
                        <?php foreach($locations as $location): ?>
                            <option value="<?= $location['name'] ?>" 
                                <?= $location['name'] == $selected_location ? 'selected' : '' ?>>
                                <?= $location['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Periode:</label>
                    <select name="date_range" onchange="this.form.submit()">
                        <option value="today" <?= $date_range == 'today' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="week" <?= $date_range == 'week' ? 'selected' : '' ?>>7 Hari Terakhir</option>
                        <option value="month" <?= $date_range == 'month' ? 'selected' : '' ?>>30 Hari Terakhir</option>
                        <option value="custom" <?= $date_range == 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                
                <?php if ($date_range == 'custom'): ?>
                <div class="filter-group">
                    <label>Dari:</label>
                    <input type="date" name="start_date" value="<?= $custom_start ?>" 
                           onchange="this.form.submit()" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="filter-group">
                    <label>Sampai:</label>
                    <input type="date" name="end_date" value="<?= $custom_end ?>" 
                           onchange="this.form.submit()" max="<?= date('Y-m-d') ?>">
                </div>
                <?php endif; ?>
                
                <div class="filter-group">
                    <button type="button" onclick="exportToPDF()" class="btn-export">Export PDF</button>
                    <button type="button" onclick="exportToExcel()" class="btn-export">Export Excel</button>
                </div>
            </form>
            
            <div class="period-info">
                Periode: <strong><?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></strong>
                | Lokasi: <strong><?= htmlspecialchars($selected_location) ?></strong>
            </div>
        </section>

        <!-- Analytics Overview -->
        <section class="analytics-section">
            <h2>Analytics Overview</h2>
            <div class="analytics-grid">
                <div class="analytic-card revenue">
                    <div class="analytic-icon">💰</div>
                    <div class="analytic-info">
                        <div class="analytic-label">Total Pendapatan</div>
                        <div class="analytic-value">Rp <?= number_format($analytics['total_revenue'], 0, ',', '.') ?></div>
                    </div>
                </div>
                
                <div class="analytic-card orders">
                    <div class="analytic-icon">📦</div>
                    <div class="analytic-info">
                        <div class="analytic-label">Total Orders</div>
                        <div class="analytic-value"><?= $analytics['total_orders'] ?></div>
                    </div>
                </div>
                
                <div class="analytic-card avg-order">
                    <div class="analytic-icon">🔥</div>
                    <div class="analytic-info">
                        <div class="analytic-label">Produk Terlaris</div>
                        <div class="analytic-value"> <?= htmlspecialchars($best_product_name) ?>
                        </div>
        <div class="analytic-sub">
            <?= $best_product_qty ?> terjual
        </div>
    </div>
</div>

                
                <div class="analytic-card completion">
                    <div class="analytic-icon">✅</div>
                    <div class="analytic-info">
                        <div class="analytic-label">Completion Rate</div>
                        <div class="analytic-value"><?= $completion_rate ?>%</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Product Performance -->
        <section class="products-section">
            <h2>Product Performance</h2>
            <div class="products-grid">
                <div class="product-card">
                    <h3>Sate Daging</h3>
                    <div class="product-stats">
                        <span class="product-qty"><?= $analytics['total_daging'] ?> porsi</span>
                        <span class="product-revenue">Rp <?= number_format($analytics['total_daging'] * 25000, 0, ',', '.') ?></span>
                    </div>
                </div>
                
                <div class="product-card">
                    <h3>Sate Kulit</h3>
                    <div class="product-stats">
                        <span class="product-qty"><?= $analytics['total_kulit'] ?> porsi</span>
                        <span class="product-revenue">Rp <?= number_format($analytics['total_kulit'] * 25000, 0, ',', '.') ?></span>
                    </div>
                </div>
                
                <div class="product-card">
                    <h3>Sate Campur</h3>
                    <div class="product-stats">
                        <span class="product-qty"><?= $analytics['total_campur'] ?> porsi</span>
                        <span class="product-revenue">Rp <?= number_format($analytics['total_campur'] * 25000, 0, ',', '.') ?></span>
                    </div>
                </div>
                
                <div class="product-card">
                    <h3>Lontong</h3>
                    <div class="product-stats">
                        <span class="product-qty"><?= $analytics['total_lontong'] ?> Pcs</span>
                        <span class="product-revenue">Rp <?= number_format($analytics['total_lontong'] * 5000, 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Revenue Trend Chart -->
        <section class="chart-section">
            <h2>Revenue Trend</h2>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </section>

        <!-- Sales Report Table -->
        <section class="sales-section">
            <div class="section-header">
                <h2>Detail Penjualan</h2>
                <div class="sales-count">
                    <?= count($sales_result) ?> orders completed
                </div>
            </div>
            
            <?php if (count($sales_result) > 0): ?>
                <div class="table-container">
                    <table class="sales-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Customer</th>
                                <th>Detail Order</th>
                                <th>Total</th>
                                <th>Lokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $counter = 1; ?>
                            <?php foreach($sales_result as $sale): 
                                $total_sate = $sale['daging_qty'] + $sale['kulit_qty'] + $sale['campur_qty'];
                                $total_harga = ($total_sate * 25000) + ($sale['lontong_qty'] * 5000);
                                $order_date = date('d/m/Y', strtotime($sale['created_at']));
                                $order_time = date('H:i', strtotime($sale['created_at']));
                            ?>
                                <tr>
                                    <td><?= $counter++ ?></td>
                                    <td>
                                        <div class="order-date"><?= $order_date ?></div>
                                        <div class="order-time"><?= $order_time ?></div>
                                    </td>
                                    <td class="customer-name"><?= htmlspecialchars($sale['customer_name']) ?></td>
                                    <td class="order-details">
                                        <div class="detail-item">
                                            <span>Daging: <?= $sale['daging_qty'] ?></span>
                                            <span>Kulit: <?= $sale['kulit_qty'] ?></span>
                                            <span>Campur: <?= $sale['campur_qty'] ?></span>
                                            <span>Lontong: <?= $sale['lontong_qty'] ?></span>
                                        </div>
                                        <div class="detail-total">
                                            Total Sate: <?= $total_sate ?> porsi
                                        </div>
                                    </td>
                                    <td class="total-price">Rp <?= number_format($total_harga, 0, ',', '.') ?></td>
                                    <td class="location"><?= $sale['location_name'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="no-data">
                    <div class="no-data-icon">📊</div>
                    <h3>Tidak ada data penjualan</h3>
                    <p>Belum ada order yang completed pada periode ini</p>
                </div>
            <?php endif; ?>
        </section>
    </div>

<script>
function exportToPDF() {
    // Ambil nilai langsung dari form
    const locationSelect = document.querySelector('select[name="location"]');
    const dateRangeSelect = document.querySelector('select[name="date_range"]');
    const startDateInput = document.querySelector('input[name="start_date"]');
    const endDateInput = document.querySelector('input[name="end_date"]');
    
    const location = locationSelect ? locationSelect.value : 'Sate Taichan Warman Galaxy 1';
    const dateRange = dateRangeSelect ? dateRangeSelect.value : 'today';
    
    let startDate, endDate;
    const today = new Date().toISOString().split('T')[0];
    
    // Hitung tanggal berdasarkan pilihan periode
    if (dateRange === 'custom' && startDateInput && endDateInput) {
        startDate = startDateInput.value;
        endDate = endDateInput.value;
    } else {
        // Gunakan periode yang sama seperti di PHP
        <?php
        // PHP code untuk generate JavaScript variables
        echo "startDate = '" . $start_date . "';";
        echo "endDate = '" . $end_date . "';";
        ?>
    }
    
    // Build URL untuk export
    const params = new URLSearchParams();
    params.append('location', location);
    params.append('start_date', startDate);
    params.append('end_date', endDate);
    
    window.open(`export_pdf.php?${params.toString()}`, '_blank');
}

function exportToExcel() {
    // Sama seperti di atas
    const locationSelect = document.querySelector('select[name="location"]');
    const dateRangeSelect = document.querySelector('select[name="date_range"]');
    const startDateInput = document.querySelector('input[name="start_date"]');
    const endDateInput = document.querySelector('input[name="end_date"]');
    
    const location = locationSelect ? locationSelect.value : 'Sate Taichan Warman Galaxy 1';
    const dateRange = dateRangeSelect ? dateRangeSelect.value : 'today';
    
    let startDate, endDate;
    
    if (dateRange === 'custom' && startDateInput && endDateInput) {
        startDate = startDateInput.value;
        endDate = endDateInput.value;
    } else {
        <?php
        echo "startDate = '" . $start_date . "';";
        echo "endDate = '" . $end_date . "';";
        ?>
    }
    
    const params = new URLSearchParams();
    params.append('location', location);
    params.append('start_date', startDate);
    params.append('end_date', endDate);
    
    window.open(`export_excel.php?${params.toString()}`, '_blank');
}
</script>

    <script>
    // Revenue Chart
    const revenueData = <?= json_encode($revenue_trend) ?>;
    
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: revenueData.map(item => new Date(item.order_date).toLocaleDateString('id-ID')),
            datasets: [{
                label: 'Daily Revenue',
                data: revenueData.map(item => item.daily_revenue),
                borderColor: '#FDB827',
                backgroundColor: 'rgba(253, 184, 39, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
    </script>
</body>
</html>