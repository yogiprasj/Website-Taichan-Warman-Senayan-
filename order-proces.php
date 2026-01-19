<?php
include 'includes/config.php';

// Cek jika form dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Ambil data dari form dan CONVERT ke integer
    $customer_name = $_POST['nama'] ?? '';
    $location_id = $_POST['location_id'] ?? '';
    $daging_qty = intval($_POST['qtyDaging'] ?? 0);  // PAKAI intval()
    $kulit_qty = intval($_POST['qtyKulit'] ?? 0);    // PAKAI intval()
    $campur_qty = intval($_POST['qtyCampur'] ?? 0);  // PAKAI intval()
    $lontong_qty = intval($_POST['lontong'] ?? 0);   // PAKAI intval()
    $catatan = $_POST['catatan'] ?? '';
    
    // Validasi data
    if (empty($customer_name) || empty($location_id)) {
        http_response_code(400);
        die('VALIDATION_ERROR: Nama dan cabang harus diisi!');
    }
    
    // Hitung total harga
    $tusukPerPorsi = 10;
    $hargaPerTusuk = 2500;
    $hargaLontong = 5000;
    
    $totalTusuk = ($daging_qty + $kulit_qty + $campur_qty) * $tusukPerPorsi;
    $totalHargaSate = $totalTusuk * $hargaPerTusuk;
    $totalHargaLontong = $lontong_qty * $hargaLontong;
    $total_harga = $totalHargaSate + $totalHargaLontong;
    
    try {
        // Simpan ke database
        $stmt = $pdo->prepare("
            INSERT INTO orders 
            (customer_name, location_id, daging_qty, kulit_qty, campur_qty, lontong_qty, catatan, total_harga) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $customer_name,
            $location_id,
            $daging_qty,
            $kulit_qty,
            $campur_qty,
            $lontong_qty,
            $catatan,
            $total_harga
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Return success
        http_response_code(200);
        echo "SUCCESS: Order saved with ID: $order_id";
        
    } catch (PDOException $e) {
        error_log("❌ Database error: " . $e->getMessage());
        http_response_code(500);
        die('DATABASE_ERROR: Terjadi error saat menyimpan pesanan.');
    }
    
} else {
    http_response_code(405);
    die('METHOD_NOT_ALLOWED: Only POST requests allowed.');
}
?>