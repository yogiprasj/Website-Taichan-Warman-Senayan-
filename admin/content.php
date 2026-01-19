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

// Create uploads directory if not exists
$upload_dir = dirname(__DIR__) . '/assets/img/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$message = '';
$message_type = '';

// Process Web Content Update
if (isset($_POST['update_content'])) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST['content'] as $content_key => $content_value) {
            // Handle file upload for images
            if (($content_key === 'home_image' || $content_key === 'price_list_image') && 
                isset($_FILES['content_files'][$content_key]) && 
                $_FILES['content_files'][$content_key]['error'] === UPLOAD_ERR_OK) {
                
                $file = $_FILES['content_files'][$content_key];
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $new_filename = $content_key . '_' . time() . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $content_value = 'assets/img/' . $new_filename;
                    }
                }
            }
            
            $stmt = $pdo->prepare("UPDATE website_content SET content_value = ?, updated_at = NOW() WHERE content_key = ?");
            $stmt->execute([trim($content_value), $content_key]);
        }
        
        $pdo->commit();
        $message = 'Web content berhasil diupdate!';
        $message_type = 'success';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Error update web content: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Process Location Update
if (isset($_POST['update_location'])) {
    try {
        $location_id = $_POST['location_id'];
        $image_path = trim($_POST['image_path']);
        
        // Handle location image upload
        if (isset($_FILES['location_image']) && $_FILES['location_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['location_image'];
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            
            if (in_array(strtolower($file_extension), $allowed_extensions)) {
                $new_filename = 'location_' . $location_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $image_path = 'assets/img/' . $new_filename;
                }
            }
        }
        
        $stmt = $pdo->prepare("UPDATE locations SET name = ?, image_path = ?, google_maps_url = ?, is_active = ? WHERE id = ?");
        $stmt->execute([
            trim($_POST['location_name']),
            $image_path,
            trim($_POST['google_maps_url']),
            isset($_POST['is_active']) ? 1 : 0,
            $location_id
        ]);
        
        $message = 'Data lokasi berhasil diupdate!';
        $message_type = 'success';
    } catch (Exception $e) {
        $message = 'Error update lokasi: ' . $e->getMessage();
        $message_type = 'error';
    }
}

// Get all web content
$content_query = $pdo->query("SELECT * FROM website_content ORDER BY id");
$web_content = $content_query->fetchAll(PDO::FETCH_ASSOC);

// Get all locations
$locations_query = $pdo->query("SELECT * FROM locations ORDER BY name");
$locations = $locations_query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Content Management</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="css/content.css">
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

        <?php if ($message): ?>
            <div class="message <?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>

        <!-- Web Content Section -->
        <section class="content-management">
            <div class="section-header">
                <h2>Web Content</h2>
            </div>
            
            <form method="POST" class="content-form" enctype="multipart/form-data">
                <div class="form-grid">
                    <?php foreach($web_content as $content): ?>
                        <div class="form-group">
                            <label for="content_<?= $content['id'] ?>">
                                <?= ucfirst(str_replace('_', ' ', $content['content_key'])) ?>
                                <?php if ($content['content_type'] === 'image'): ?>
                                    <span class="field-info">(Upload Gambar)</span>
                                <?php elseif ($content['content_type'] === 'caption'): ?>
                                    <span class="field-info">(Description)</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if ($content['content_type'] === 'image'): ?>
                                <!-- File Upload for Images -->
                                <input 
                                    type="file" 
                                    id="content_<?= $content['id'] ?>" 
                                    name="content_files[<?= $content['content_key'] ?>]"
                                    accept=".jpg,.jpeg,.png,.webp,.gif"
                                    class="file-input"
                                >
                                <input 
                                    type="hidden" 
                                    name="content[<?= $content['content_key'] ?>]" 
                                    value="<?= htmlspecialchars($content['content_value']) ?>"
                                >
                                <div class="image-preview">
                                    <?php if ($content['content_value']): ?>
                                        <img src="../<?= htmlspecialchars($content['content_value']) ?>" 
                                             alt="Preview" 
                                             class="preview-image"
                                             onerror="this.style.display='none'">
                                        <div class="current-path">
                                            <small>Current: <?= htmlspecialchars($content['content_value']) ?></small>
                                        </div>
                                    <?php else: ?>
                                        <div class="no-preview">No image selected</div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($content['content_type'] === 'caption'): ?>
                                <textarea 
                                    id="content_<?= $content['id'] ?>" 
                                    name="content[<?= $content['content_key'] ?>]" 
                                    rows="4" 
                                    placeholder="Masukkan <?= str_replace('_', ' ', $content['content_key']) ?>..."
                                ><?= htmlspecialchars($content['content_value']) ?></textarea>
                            <?php else: ?>
                                <input 
                                    type="text" 
                                    id="content_<?= $content['id'] ?>" 
                                    name="content[<?= $content['content_key'] ?>]" 
                                    value="<?= htmlspecialchars($content['content_value']) ?>" 
                                    placeholder="Masukkan <?= str_replace('_', ' ', $content['content_key']) ?>..."
                                >
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_content" class="btn-save">Update Web Content</button>
                    <a href="dashboard.php" class="btn-cancel">Kembali ke Dashboard</a>
                </div>
            </form>
        </section>

       <!-- Locations Management Section -->
<section class="locations-management">
    <div class="section-header">
        <h2>Managemen Lokasi Cabang</h2>
        <p>Kelola semua lokasi Sate Taichan Anda</p>
    </div>
    
    <div class="locations-grid">
        <?php foreach($locations as $location): ?>
            <div class="location-card">
                <div class="location-header">
                    <h3><?= htmlspecialchars($location['name']) ?></h3>
                    <span class="status-badge <?= $location['is_active'] ? 'active' : 'inactive' ?>">
                        <?= $location['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                    </span>
                </div>
                
                <form method="POST" class="location-form" enctype="multipart/form-data">
                    <input type="hidden" name="location_id" value="<?= $location['id'] ?>">
                    <input type="hidden" name="image_path" value="<?= htmlspecialchars($location['image_path']) ?>">
                    
                    <div class="image-upload-section">
                        <div class="image-preview-container">
                            <?php if ($location['image_path']): ?>
                                <img src="../<?= htmlspecialchars($location['image_path']) ?>" 
                                     alt="<?= htmlspecialchars($location['name']) ?>" 
                                     class="location-image preview-image"
                                     onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="image-placeholder">
                                    <span>No Image Available</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="upload-button-container">
                            <input type="file" 
                                   name="location_image" 
                                   accept=".jpg,.jpeg,.png,.webp,.gif"
                                   class="file-input"
                                   id="location_image_<?= $location['id'] ?>">
                            <label for="location_image_<?= $location['id'] ?>" class="upload-btn">
                                Upload Gambar
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-fields">
                        <div class="form-group">
                            <label class="form-label">Nama Lokasi</label>
                            <input type="text" 
                                   name="location_name" 
                                   value="<?= htmlspecialchars($location['name']) ?>" 
                                   class="form-input"
                                   placeholder="Masukkan nama lokasi..."
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Google Maps URL</label>
                            <input type="url" 
                                   name="google_maps_url" 
                                   value="<?= htmlspecialchars($location['google_maps_url']) ?>" 
                                   class="form-input"
                                   placeholder="https://maps.google.com/...">
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" 
                                       name="is_active" 
                                       value="1" 
                                       <?= $location['is_active'] ? 'checked' : '' ?>
                                       class="checkbox-input">
                                <span class="checkmark"></span>
                                <span class="checkbox-text">Lokasi Aktif</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="location-actions">
                        <button type="submit" name="update_location" class="btn-update">
                            Update Lokasi
                        </button>
                    </div>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
</section>

    <script src="admin/js/admin.js"></script>
</body>
</html>