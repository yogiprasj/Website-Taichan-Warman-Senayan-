// ===== WEBSITE JAVASCRIPT - SATE TAICHAN WARMAN SENAYAN =====
document.addEventListener('DOMContentLoaded', function() {
    
    // ===== RESPONSIVE NAVBAR =====
    const menuIcon = document.getElementById('menu-icon');
    const navLinks = document.getElementById('nav-links');
    const navbar = document.querySelector('.navbar');

    if (menuIcon && navLinks) {
        // Toggle mobile menu
        menuIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            navLinks.classList.toggle('active');
            menuIcon.textContent = navLinks.classList.contains('active') ? '✕' : '☰';
        });

        // Close menu when clicking links
        const navLinksList = navLinks.querySelectorAll('a');
        navLinksList.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('active');
                menuIcon.textContent = '☰';
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.navbar')) {
                navLinks.classList.remove('active');
                menuIcon.textContent = '☰';
            }
        });

        // Navbar background on scroll (DESKTOP ONLY)
        if (window.innerWidth > 900) {
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    navbar.classList.add('scrolled');
                } else {
                    navbar.classList.remove('scrolled');
                }
            });
        }
    }

    // ===== SMOOTH SCROLL =====
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Skip # links
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            
            if (target) {
                const offsetTop = target.offsetTop - 80; // Adjust for navbar height
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });

    // ===== ORDER FORM SYSTEM =====
    const orderForm = document.getElementById("orderForm");
    
    if (orderForm) {
        
        // Form elements
        const inputNama = document.getElementById("nama");
        const inputDaging = document.getElementById("qtyDaging");
        const inputKulit = document.getElementById("qtyKulit");
        const inputCampur = document.getElementById("qtyCampur");
        const inputLontong = document.getElementById("lontong");
        const selectLocation = document.getElementById("location_id");
        const textareaCatatan = document.getElementById("catatan");
        const totalHargaText = document.getElementById("totalHarga");
        const submitBtn = document.getElementById("submitBtn");

        // Price configuration
        const PRICES = {
            TUSUK_PER_PORSI: 10,
            HARGA_PER_TUSUK: 2500,
            HARGA_LONTONG: 5000
        };

        // Calculate total function
        function hitungTotal() {
            const daging = parseInt(inputDaging.value) || 0;
            const kulit = parseInt(inputKulit.value) || 0;
            const campur = parseInt(inputCampur.value) || 0;
            const lontong = parseInt(inputLontong.value) || 0;

            const totalTusuk = (daging + kulit + campur) * PRICES.TUSUK_PER_PORSI;
            const totalHargaSate = totalTusuk * PRICES.HARGA_PER_TUSUK;
            const totalHargaLontong = lontong * PRICES.HARGA_LONTONG;
            const total = totalHargaSate + totalHargaLontong;

            // Update display
            if (totalHargaText) {
                totalHargaText.textContent = `Total: Rp ${total.toLocaleString('id-ID')}`;
                totalHargaText.style.color = total > 0 ? '#23120B' : '#666';
            }

            // Update button state
            if (submitBtn) {
                const hasOrder = total > 0;
                submitBtn.disabled = !hasOrder;
                submitBtn.style.opacity = hasOrder ? '1' : '0.6';
                submitBtn.style.cursor = hasOrder ? 'pointer' : 'not-allowed';
            }

            return { total, totalTusuk };
        }

        // Real-time calculation
        [inputDaging, inputKulit, inputCampur, inputLontong].forEach(input => {
            if (input) {
                input.addEventListener('input', hitungTotal);
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#FDB827';
                });
                input.addEventListener('blur', function() {
                    this.style.borderColor = '#ddd';
                });
            }
        });

        // Input validation - only allow numbers
        [inputDaging, inputKulit, inputCampur, inputLontong].forEach(input => {
            if (input) {
                input.addEventListener('keypress', function(e) {
                    const charCode = e.which ? e.which : e.keyCode;
                    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                        e.preventDefault();
                    }
                });
            }
        });

        // ===== FORM SUBMISSION WITH DATABASE SAVE =====
        orderForm.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form values
            const nama = inputNama.value.trim();
            const catatan = textareaCatatan.value.trim();
            const locationId = selectLocation.value;
            const locationName = selectLocation.options[selectLocation.selectedIndex].text;

            const daging = parseInt(inputDaging.value) || 0;
            const kulit = parseInt(inputKulit.value) || 0;
            const campur = parseInt(inputCampur.value) || 0;
            const lontong = parseInt(inputLontong.value) || 0;

            const { total, totalTusuk } = hitungTotal();

            // Validation
            if (!nama) {
                showAlert('❌ Silakan isi nama terlebih dahulu!', 'error');
                inputNama.focus();
                return;
            }

            if (!locationId) {
                showAlert('❌ Silakan pilih cabang terlebih dahulu!', 'error');
                selectLocation.focus();
                return;
            }

            if (total === 0) {
                showAlert('❌ Silakan pilih pesanan terlebih dahulu!', 'error');
                inputDaging.focus();
                return;
            }

            // Create WhatsApp message
            const message = `Halo, saya mau pesan:

*SATE TAICHAN WARMAN SENAYAN*

*Nama:* ${nama}
*Cabang:* ${locationName}

*Detail Pesanan:*
• Sate Daging: ${daging} porsi (${daging * 10} tusuk)
• Sate Kulit: ${kulit} porsi (${kulit * 10} tusuk)  
• Sate Campur: ${campur} porsi (${campur * 10} tusuk)
• Lontong: ${lontong} pcs

*Catatan:* ${catatan || 'Tidak ada catatan'}

*Total Pembayaran:*
Total Tusuk: ${totalTusuk} tusuk
*Total Harga: Rp ${total.toLocaleString('id-ID')}*

Terima kasih! 🎉`;

            // Show loading alert
            showAlert('⏳ Menyimpan pesanan ke database...', 'info');

            // 1. SIMPAN KE DATABASE DULU
            fetch('order-proces.php', {
                method: 'POST',
                body: new FormData(orderForm)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
            .then(data => {
                if (data.includes('SUCCESS') || data.includes('Order saved')) {
                    showAlert('✅ Pesanan tersimpan! Membuka WhatsApp...', 'success');
                    
                    // 2. SETELAH BERHASIL SIMPAN, BUKA WHATSAPP
                    setTimeout(() => {
                        const waNumber = "6287780515082";
                        const url = `https://wa.me/${waNumber}?text=${encodeURIComponent(message)}`;
                        window.open(url, '_blank');
                        
                        // 3. RESET FORM SETELAH SUKSES
                        setTimeout(() => {
                            orderForm.reset();
                            hitungTotal();
                        }, 1000);
                        
                    }, 1500);
                } else {
                    throw new Error('Server response: ' + data);
                }
            })
            .catch(error => {
                showAlert('❌ Gagal menyimpan pesanan: ' + error.message, 'error');
            });
        });

        // Initial calculation
        hitungTotal();
    }

    // ===== IMAGE LAZY LOADING =====
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('load', function() {
            this.style.opacity = '1';
        });
        
        if (img.complete) {
            img.style.opacity = '1';
        }
    });

    // ===== SCROLL ANIMATIONS =====
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe sections for animation
    document.querySelectorAll('section').forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(20px)';
        section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(section);
    });

    // ===== UTILITY FUNCTIONS =====
    function showAlert(message, type = 'info') {
        // Remove existing alert
        const existingAlert = document.querySelector('.custom-alert');
        if (existingAlert) {
            existingAlert.remove();
        }

        // Create alert
        const alert = document.createElement('div');
        alert.className = `custom-alert ${type}`;
        alert.innerHTML = `
            <span>${message}</span>
            <button onclick="this.parentElement.remove()">×</button>
        `;

        // Style alert
        Object.assign(alert.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            borderRadius: '8px',
            color: 'white',
            fontWeight: '600',
            zIndex: '10000',
            display: 'flex',
            alignItems: 'center',
            gap: '10px',
            maxWidth: '400px',
            boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
            transform: 'translateX(100%)',
            transition: 'transform 0.3s ease'
        });

        // Type-based styling
        const styles = {
            success: { background: '#25d366' },
            error: { background: '#e74c3c' },
            info: { background: '#3498db' }
        };

        Object.assign(alert.style, styles[type] || styles.info);

        // Add to page and animate in
        document.body.appendChild(alert);
        setTimeout(() => {
            alert.style.transform = 'translateX(0)';
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alert.parentElement) {
                alert.style.transform = 'translateX(100%)';
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    }

    // ===== PAGE LOAD COMPLETE =====
    window.addEventListener('load', function() {
        document.body.classList.add('loaded');
    });
});

// ===== GLOBAL FUNCTIONS =====
// Add any global functions here if needed

// Export for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { /* exports if needed */ };
}