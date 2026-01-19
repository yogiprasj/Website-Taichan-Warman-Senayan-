<?php
include 'includes/config.php';

echo "<h3>🔍 Checking Orders in Database</h3>";

try {
    // Test connection
    echo "✅ Database connected<br>";
    
    // Count total orders
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders");
    $count = $stmt->fetch();
    echo "📊 Total orders in database: <strong>" . $count['total'] . "</strong><br><br>";
    
    // Get all orders
    $stmt = $pdo->query("
        SELECT o.*, l.name as location_name 
        FROM orders o 
        LEFT JOIN locations l ON o.location_id = l.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
    
    if (count($orders) > 0) {
        echo "<h4>📦 All Orders:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>
                <th>ID</th>
                <th>Nama</th>
                <th>Cabang</th>
                <th>Daging</th>
                <th>Kulit</th>
                <th>Campur</th>
                <th>Lontong</th>
                <th>Total</th>
                <th>Waktu</th>
              </tr>";
        
        foreach ($orders as $order) {
            echo "<tr>";
            echo "<td>{$order['id']}</td>";
            echo "<td>{$order['customer_name']}</td>";
            echo "<td>{$order['location_name']}</td>";
            echo "<td>{$order['daging_qty']}</td>";
            echo "<td>{$order['kulit_qty']}</td>";
            echo "<td>{$order['campur_qty']}</td>";
            echo "<td>{$order['lontong_qty']}</td>";
            echo "<td>Rp " . number_format($order['total_harga'], 0, ',', '.') . "</td>";
            echo "<td>{$order['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ No orders found in database</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test recent activity
echo "<br><h4>🕒 Recent Activity Test:</h4>";
try {
    $test_data = [
        'nama' => 'Test Manual',
        'location_id' => 1,
        'daging_qty' => 2,
        'kulit_qty' => 1,
        'campur_qty' => 1,
        'lontong_qty' => 2,
        'catatan' => 'Test manual insert',
        'total_harga' => 125000
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO orders 
        (customer_name, location_id, daging_qty, kulit_qty, campur_qty, lontong_qty, catatan, total_harga) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $test_data['nama'],
        $test_data['location_id'],
        $test_data['daging_qty'],
        $test_data['kulit_qty'],
        $test_data['campur_qty'],
        $test_data['lontong_qty'],
        $test_data['catatan'],
        $test_data['total_harga']
    ]);
    
    $new_id = $pdo->lastInsertId();
    echo "✅ Test order inserted with ID: <strong>$new_id</strong><br>";
    
} catch (PDOException $e) {
    echo "❌ Test insert failed: " . $e->getMessage() . "<br>";
}
?>