<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    die('Access denied');
}

require_once dirname(__DIR__) . '/includes/config.php';

// Get parameters
$location = $_GET['location'] ?? 'Sate Taichan Warman Galaxy 1';
$start_date = $_GET['start_date'] ?? date('Y-m-d');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get sales data
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
$analytics_query->execute([$location, $start_date, $end_date]);
$analytics = $analytics_query->fetch(PDO::FETCH_ASSOC);

// Get sales details
$sales_query = $pdo->prepare("
    SELECT o.*, l.name as location_name 
    FROM orders o 
    LEFT JOIN locations l ON o.location_id = l.id 
    WHERE o.is_completed = 1 AND l.name = ? AND DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at ASC
");
$sales_query->execute([$location, $start_date, $end_date]);
$sales_result = $sales_query->fetchAll(PDO::FETCH_ASSOC);

// Create simple HTML report that user can print as PDF
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Penjualan - <?= date('d-m-Y') ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            line-height: 1.6;
        }
        .header { 
            text-align: center; 
            margin-bottom: 30px; 
            border-bottom: 2px solid #333; 
            padding-bottom: 10px; 
        }
        .header h1 { 
            color: #23120B; 
            margin: 0; 
            font-size: 24px;
        }
        .summary { 
            margin: 20px 0; 
            padding: 15px; 
            background: #f8f9fa; 
            border-radius: 5px; 
        }
        .summary-grid { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 15px; 
            margin: 20px 0; 
        }
        .summary-card { 
            background: white; 
            padding: 15px; 
            border-radius: 5px; 
            border-left: 4px solid #FDB827; 
        }
        .table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            font-size: 12px;
        }
        .table th { 
            background: #23120B; 
            color: white; 
            padding: 8px; 
            text-align: left; 
        }
        .table td { 
            padding: 6px 8px; 
            border-bottom: 1px solid #ddd; 
        }
        .table tr:nth-child(even) { 
            background: #f9f9f9; 
        }
        .products { 
            margin: 20px 0; 
        }
        .product-grid { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 10px; 
        }
        .product-item { 
            padding: 10px; 
            background: #fff8e1; 
            border-radius: 5px; 
            font-size: 14px;
        }
        .footer { 
            margin-top: 30px; 
            text-align: center; 
            color: #666; 
            font-size: 12px; 
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
            .header { border-bottom: 2px solid #000; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PENJUALAN SATE TAICHAN WARMAN</h1>
        <p>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
        <p>Lokasi: <?= htmlspecialchars($location) ?></p>
        <p>Dibuat pada: <?= date('d/m/Y H:i') ?></p>
        <button class="no-print" onclick="window.print()" style="padding: 10px 20px; background: #FDB827; border: none; border-radius: 5px; cursor: pointer;">
            🖨️ Print sebagai PDF
        </button>
    </div>

    <div class="summary">
        <h2>Ringkasan Penjualan</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <strong>Total Pendapatan</strong><br>
                Rp <?= number_format($analytics['total_revenue'], 0, ',', '.') ?>
            </div>
            <div class="summary-card">
                <strong>Total Orders</strong><br>
                <?= $analytics['total_orders'] ?> orders
            </div>
            <div class="summary-card">
                <strong>Rata-rata per Order</strong><br>
                Rp <?= number_format($analytics['avg_order_value'], 0, ',', '.') ?>
            </div>
            <div class="summary-card">
                <strong>Orders Selesai</strong><br>
                <?= $analytics['completed_orders'] ?> orders
            </div>
        </div>
    </div>

    <div class="products">
        <h2>Performance Produk</h2>
        <div class="product-grid">
            <div class="product-item">
                <strong>Sate Daging:</strong> <?= $analytics['total_daging'] ?> porsi<br>
                <em>Rp <?= number_format($analytics['total_daging'] * 25000, 0, ',', '.') ?></em>
            </div>
            <div class="product-item">
                <strong>Sate Kulit:</strong> <?= $analytics['total_kulit'] ?> porsi<br>
                <em>Rp <?= number_format($analytics['total_kulit'] * 25000, 0, ',', '.') ?></em>
            </div>
            <div class="product-item">
                <strong>Sate Campur:</strong> <?= $analytics['total_campur'] ?> porsi<br>
                <em>Rp <?= number_format($analytics['total_campur'] * 25000, 0, ',', '.') ?></em>
            </div>
            <div class="product-item">
                <strong>Lontong:</strong> <?= $analytics['total_lontong'] ?> porsi<br>
                <em>Rp <?= number_format($analytics['total_lontong'] * 5000, 0, ',', '.') ?></em>
            </div>
        </div>
    </div>

    <?php if (count($sales_result) > 0): ?>
    <h2>Detail Penjualan</h2>
    <table class="table">
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Customer</th>
                <th>Detail Order</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $counter = 1; ?>
            <?php foreach($sales_result as $sale): 
                $total_sate = $sale['daging_qty'] + $sale['kulit_qty'] + $sale['campur_qty'];
                $total_harga = ($total_sate * 25000) + ($sale['lontong_qty'] * 5000);
                $order_date = date('d/m/Y H:i', strtotime($sale['created_at']));
            ?>
                <tr>
                    <td><?= $counter++ ?></td>
                    <td><?= $order_date ?></td>
                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                    <td>
                        Daging: <?= $sale['daging_qty'] ?> | 
                        Kulit: <?= $sale['kulit_qty'] ?> | 
                        Campur: <?= $sale['campur_qty'] ?> | 
                        Lontong: <?= $sale['lontong_qty'] ?>
                    </td>
                    <td>Rp <?= number_format($total_harga, 0, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p><em>Tidak ada data penjualan pada periode ini</em></p>
    <?php endif; ?>

    <div class="footer">
        Laporan dibuat secara otomatis oleh Sistem Sate Taichan Warman &copy; <?= date('Y') ?>
    </div>

    <script>
        // Auto print atau user bisa manual print
        setTimeout(() => {
            if (confirm('Print laporan sebagai PDF?')) {
                window.print();
            }
        }, 500);
    </script>
</body>
</html>