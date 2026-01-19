<?php include 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://fonts.googleapis.com/css2?family=Gorditas:wght@400;700&display=swap" rel="stylesheet">
  <title>Sate Taichan Warman Senayan</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
  <meta name="description" content="Sate Taichan Warman Senayan - Sate pedas khas Senayan dengan cita rasa autentik. Buka setiap hari!">
  <link rel="icon" href="assets/img/LogoHome.png" type="image/png">
</head>
<body>

  <!-- NAVBAR -->
  <nav class="navbar">
    <div class="logo">
      <img src="assets/img/LogoHome.png" alt="Logo Sate Taichan" class="logo-img">
      <span>Sate Taichan <br>Warman Senayan</span>
    </div>
    <div class="menu-icon" id="menu-icon">&#9776;</div>
    <ul class="nav-links" id="nav-links">
      <li><a href="#home">Home</a></li>
      <li><a href="#location">Location</a></li>
      <li><a href="#menu">Menu</a></li>
      <li><a href="#order">Order</a></li>
      <li><a href="#contact">Contact</a></li>
    </ul>
  </nav>

  <!-- HOME SECTION -->
  <section class="home" id="home">
    <div class="home-hero">
      <div class="hero-img">
        <img src="<?php echo getContent('home_image', 'assets/img/home.webp'); ?>" alt="Sate Taichan Warman Senayan">
      </div>
      <div class="hero-text">
        <h1><?php echo getContent('home_title', 'Sate Taichan<br>Warman Senayan'); ?></h1>
        <p><?php echo getContent('home_description', 'Nikmati sensasi sate taichan dengan cita rasa khas yang menggugah selera! Dibakar langsung di atas bara api dengan bumbu rahasia menghasilkan perpaduan gurih, pedas, dan aroma smokey yang bikin nagih.'); ?></p>
      </div>
    </div>
  </section>

  <!-- LOCATION -->
  <section id="location" class="location">
    <h2>OUR LOCATION</h2>
    <div class="location-grid">
      <?php
      $locations = getLocations();
      foreach ($locations as $location): ?>
        <div class="location-card">
          <div class="card-image">
            <img src="<?php echo htmlspecialchars($location['image_path']); ?>" alt="<?php echo htmlspecialchars($location['name']); ?>">
          </div>
          <div class="card-content">
            <h3><?php echo htmlspecialchars($location['name']); ?></h3>
            <!-- <p class="address"><?php echo htmlspecialchars($location['address']); ?></p> -->
            <a href="<?php echo htmlspecialchars($location['google_maps_url']); ?>" target="_blank" class="map-link">
              Lihat di Google Maps
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- MENU -->
  <section id="menu" class="menu-section">
    <div class="menu-overlay">
      <div class="container">
        <h2 class="menu-title">OUR MENU</h2>
        <div class="menu-pricelist">
          <img src="<?php echo getContent('price_list_image', 'assets/img/PriceList.png'); ?>" alt="Daftar Harga Sate Taichan" />
        </div>
      </div>
    </div>
  </section>

  <!-- ORDER FORM -->
  <section id="order" class="order-section">
    <form id="orderForm" class="order-form" action="process-order.php" method="POST">
      <h2>PESAN DI TEMPAT</h2>
      <p>Isi form di bawah, lalu pesan langsung via WhatsApp. Pembayaran langsung di kasir ya!</p>

      <input type="text" id="nama" name="nama" placeholder="Nama" required class="input-field">

      <label class="label">Pilih Cabang</label>
      <select id="location_id" name="location_id" required class="input-field">
        <option value="">-- Pilih Cabang --</option>
        <?php foreach ($locations as $location): ?>
          <option value="<?php echo $location['id']; ?>">
            <?php echo htmlspecialchars($location['name']); ?>
          </option>
        <?php endforeach; ?>
      </select>

      <label class="label">Pesanan Sate (per porsi = 10 tusuk)</label>
      <div class="row"><span>Daging</span><input type="number" id="qtyDaging" name="qtyDaging" min="0" value="" class="qty-input"></div>
      <div class="row"><span>Kulit</span><input type="number" id="qtyKulit" name="qtyKulit" min="0" value="" class="qty-input"></div>
      <div class="row"><span>Campur</span><input type="number" id="qtyCampur" name="qtyCampur" min="0" value="" class="qty-input"></div>

      <label class="label">Jumlah Lontong</label>
      <input type="number" id="lontong" name="lontong" min="0" value="" class="input-field">

      <label class="label">Catatan</label>
      <textarea id="catatan" name="catatan" placeholder="Contoh: Tidak pedas ya" class="textarea-field"></textarea>

      <p id="totalHarga" class="total-harga">Total: Rp 0</p>
      <button type="submit" id="submitBtn" class="btn-wa">Pesan via WhatsApp</button>
    </form>
  </section>

  <!-- CONTACT -->
  <section id="contact" class="special">
    <h3>Punya Acara Spesial?</h3>
    <p>Sate Taichan Warman Senayan siap jadi bintang di meja hidangan!</p>

    <div class="contact-buttons">
      <a href="https://wa.me/6289524304313" class="contact-btn whatsapp">
        <img src="assets/img/whatsapp.svg" alt="WhatsApp"> WhatsApp
      </a>
      <a href="https://www.instagram.com/sate_taichan_warman_senayan/" class="contact-btn instagram">
        <img src="assets/img/instagram.svg" alt="Instagram"> Instagram
      </a>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <p>© 2026 Sate Taichan Warman Senayan. All rights reserved.</p>
    <p>Open daily : 17.00 - 02.00 WIB</p>
  </footer>

  <script src="<?php echo BASE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
