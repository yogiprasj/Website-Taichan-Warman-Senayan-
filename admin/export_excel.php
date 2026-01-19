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

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="laporan_penjualan_' . date('Y-m-d') . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Excel content
echo "LAPORAN PENJUALAN SATE TAICHAN WARMAN\n\n";
echo "Periode: " . date('d/m/Y', strtotime($start_date)) . " - " . date('d/m/Y', strtotime($end_date)) . "\n";
echo "Lokasi: " . $location . "\n";
echo "Dibuat pada: " . date('d/m/Y H:i') . "\n\n";

echo "RINGKASAN PENJUALAN\n";
echo "Total Pendapatan\tRp " . number_format($analytics['total_revenue'], 0, ',', '.') . "\n";
echo "Total Orders\t" . $analytics['total_orders'] . " orders\n";
echo "Rata-rata per Order\tRp " . number_format($analytics['avg_order_value'], 0, ',', '.') . "\n";
echo "Orders Selesai\t" . $analytics['completed_orders'] . " orders\n\n";

echo "PERFORMANCE PRODUK\n";
echo "Sate Daging\t" . $analytics['total_daging'] . " porsi\tRp " . number_format($analytics['total_daging'] * 25000, 0, ',', '.') . "\n";
echo "Sate Kulit\t" . $analytics['total_kulit'] . " porsi\tRp " . number_format($analytics['total_kulit'] * 25000, 0, ',', '.') . "\n";
echo "Sate Campur\t" . $analytics['total_campur'] . " porsi\tRp " . number_format($analytics['total_campur'] * 25000, 0, ',', '.') . "\n";
echo "Lontong\t" . $analytics['total_lontong'] . " porsi\tRp " . number_format($analytics['total_lontong'] * 5000, 0, ',', '.') . "\n\n";

if (count($sales_result) > 0) {
    echo "DETAIL PENJUALAN\n";
    echo "No\tTanggal\tCustomer\tDaging\tKulit\tCampur\tLontong\tTotal Sate\tTotal Harga\n";
    
    $counter = 1;
    foreach ($sales_result as $sale) {
        $total_sate = $sale['daging_qty'] + $sale['kulit_qty'] + $sale['campur_qty'];
        $total_harga = ($total_sate * 25000) + ($sale['lontong_qty'] * 5000);
        $order_date = date('d/m/Y H:i', strtotime($sale['created_at']));
        
        echo $counter++ . "\t";
        echo $order_date . "\t";
        echo $sale['customer_name'] . "\t";
        echo $sale['daging_qty'] . "\t";
        echo $sale['kulit_qty'] . "\t";
        echo $sale['campur_qty'] . "\t";
        echo $sale['lontong_qty'] . "\t";
        echo $total_sate . "\t";
        echo "Rp " . number_format($total_harga, 0, ',', '.') . "\n";
    }
} else {
    echo "Tidak ada data penjualan pada periode ini\n";
}
?>