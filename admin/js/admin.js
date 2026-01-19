// CONTENT WEBSITE
document.addEventListener('DOMContentLoaded', function() {
    // Web content image preview
    const contentFileInputs = document.querySelectorAll('.content-form .file-input');
    contentFileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                const previewContainer = this.closest('.form-group').querySelector('.image-preview');
                
                reader.onload = function(e) {
                    let img = previewContainer.querySelector('.preview-image');
                    if (!img) {
                        img = document.createElement('img');
                        img.className = 'preview-image';
                        previewContainer.innerHTML = '';
                        previewContainer.appendChild(img);
                    }
                    img.src = e.target.result;
                }
                
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Location image preview
    const locationFileInputs = document.querySelectorAll('.location-form .file-input');
    locationFileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                const previewContainer = this.closest('.image-upload-container');
                
                reader.onload = function(e) {
                    let img = previewContainer.querySelector('.preview-image');
                    if (!img) {
                        img = document.createElement('img');
                        img.className = 'preview-image location-image';
                        const noImage = previewContainer.querySelector('.no-image');
                        if (noImage) noImage.remove();
                        previewContainer.appendChild(img);
                    }
                    img.src = e.target.result;
                }
                
                reader.readAsDataURL(file);
            }
        });
    });
});


// Orders Management JavaScript

// Confirm order deletion
function confirmDelete(customerName) {
    return confirm(`Apakah Anda yakin ingin menghapus order dari ${customerName}? Tindakan ini tidak dapat dibatalkan.`);
}

// Filter orders by status
function filterOrders(status) {
    const rows = document.querySelectorAll('.orders-table tbody tr');
    
    rows.forEach(row => {
        switch(status) {
            case 'all':
                row.style.display = '';
                break;
            case 'pending':
                row.style.display = row.classList.contains('pending') ? '' : 'none';
                break;
            case 'completed':
                row.style.display = row.classList.contains('completed') ? '' : 'none';
                break;
        }
    });
    
    // Update active filter button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
}

// Search orders by customer name
function searchOrders() {
    const searchTerm = document.getElementById('searchOrders').value.toLowerCase();
    const rows = document.querySelectorAll('.orders-table tbody tr');
    
    rows.forEach(row => {
        const customerName = row.querySelector('.customer-name').textContent.toLowerCase();
        row.style.display = customerName.includes(searchTerm) ? '' : 'none';
    });
}

// Export orders to CSV
function exportToCSV() {
    const table = document.querySelector('.orders-table');
    let csv = [];
    
    // Headers
    let headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        if (th.textContent !== 'Aksi') { // Exclude actions column
            headers.push(`"${th.textContent}"`);
        }
    });
    csv.push(headers.join(','));
    
    // Rows
    table.querySelectorAll('tbody tr').forEach(row => {
        if (row.style.display !== 'none') {
            let rowData = [];
            row.querySelectorAll('td').forEach((cell, index) => {
                if (index !== 7) { // Exclude actions column (8th column)
                    let text = cell.textContent.trim();
                    
                    // Handle order details
                    if (cell.classList.contains('order-details')) {
                        const details = [];
                        cell.querySelectorAll('.detail-item').forEach(item => {
                            const label = item.querySelector('.detail-label').textContent.trim();
                            const value = item.querySelector('.detail-value').textContent.trim();
                            details.push(`${label} ${value}`);
                        });
                        text = details.join(', ');
                    }
                    
                    rowData.push(`"${text.replace(/"/g, '""')}"`);
                }
            });
            csv.push(rowData.join(','));
        }
    });
    
    // Download
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `orders_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Auto-refresh orders every 30 seconds (optional)
function startAutoRefresh() {
    setInterval(() => {
        if (!document.hidden) {
            window.location.reload();
        }
    }, 30000); // 30 seconds
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add search functionality if search input exists
    const searchInput = document.getElementById('searchOrders');
    if (searchInput) {
        searchInput.addEventListener('input', searchOrders);
    }
    
    // Add filter buttons if they exist
    const filterButtons = document.querySelectorAll('.filter-btn');
    filterButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            filterOrders(this.dataset.filter);
        });
    });
    
    // Add export button if it exists
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportToCSV);
    }
    
    // Start auto-refresh if enabled
    // startAutoRefresh(); // Uncomment if needed
});

// ORDERS
function quickUpdateStatus(orderId, isCompleted) {
    const formData = new FormData();
    formData.append('order_id', orderId);
    formData.append('is_completed', isCompleted ? 1 : 0);
    formData.append('update_order_status', 1);
    
    fetch('orders.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(() => {
        // Show quick feedback
        const statusText = document.querySelector(`[data-order="${orderId}"] .status-text`);
        if (statusText) {
            statusText.textContent = isCompleted ? 'Completed' : 'Pending';
            statusText.style.color = isCompleted ? '#27ae60' : '#e74c3c';
        }
    })
    .catch(error => {
        console.error('Error updating status:', error);
        alert('Error updating order status');
    });
}

// LAPORAN
// Laporan Penjualan JavaScript

// Export to PDF
function exportToPDF() {
    alert('Fitur export PDF akan segera tersedia!');
    // Implementation using jsPDF library can be added here
}

// Export to Excel
function exportToExcel() {
    const table = document.querySelector('.sales-table');
    let csv = [];
    
    // Headers
    let headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(`"${th.textContent}"`);
    });
    csv.push(headers.join(','));
    
    // Rows
    table.querySelectorAll('tbody tr').forEach(row => {
        let rowData = [];
        row.querySelectorAll('td').forEach(cell => {
            let text = cell.textContent.trim();
            
            // Handle order details
            if (cell.classList.contains('order-details')) {
                const details = [];
                cell.querySelectorAll('.detail-item span').forEach(span => {
                    details.push(span.textContent.trim());
                });
                text = details.join(' | ');
            }
            
            rowData.push(`"${text.replace(/"/g, '""')}"`);
        });
        csv.push(rowData.join(','));
    });
    
    // Download
    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `laporan_penjualan_${new Date().toISOString().split('T')[0]}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Date range validation
function validateDateRange() {
    const startDate = document.querySelector('input[name="start_date"]');
    const endDate = document.querySelector('input[name="end_date"]');
    
    if (startDate && endDate) {
        if (new Date(startDate.value) > new Date(endDate.value)) {
            alert('Tanggal mulai tidak boleh lebih besar dari tanggal akhir');
            startDate.value = endDate.value;
        }
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add date validation
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(input => {
        input.addEventListener('change', validateDateRange);
    });
    
    // Auto-submit custom date range form
    const customDateInputs = document.querySelectorAll('input[name="start_date"], input[name="end_date"]');
    customDateInputs.forEach(input => {
        input.addEventListener('change', function() {
            setTimeout(() => {
                this.form.submit();
            }, 1000);
        });
    });
});

// Quick analytics refresh
function refreshAnalytics() {
    const location = document.querySelector('select[name="location"]').value;
    const dateRange = document.querySelector('select[name="date_range"]').value;
    
    const url = new URL(window.location);
    url.searchParams.set('location', location);
    url.searchParams.set('date_range', dateRange);
    
    window.location.href = url.toString();
}

// Print report
function printReport() {
    window.print();
}

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + P for print
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        printReport();
    }
    
    // Ctrl + E for export
    if (e.ctrlKey && e.key === 'e') {
        e.preventDefault();
        exportToExcel();
    }
});