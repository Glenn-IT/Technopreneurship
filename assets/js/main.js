// assets/js/main.js - Interactive Client Logic for Water Billing System

document.addEventListener('DOMContentLoaded', function() {
    
    // Auto calculate amount due based on consumption (default rate: ₱25.00 per m³)
    const consumptionInput = document.getElementById('consumption');
    const amountInput = document.getElementById('amount_due');
    const RATE_PER_CUBIC = 25.00;

    if (consumptionInput && amountInput) {
        consumptionInput.addEventListener('input', function() {
            const val = parseFloat(this.value) || 0;
            if (!amountInput.dataset.userModified) {
                amountInput.value = (val * RATE_PER_CUBIC).toFixed(2);
            }
        });
        
        amountInput.addEventListener('change', function() {
            this.dataset.userModified = "true";
        });
    }

    // Real-time table search filter
    const searchInput = document.getElementById('tableSearchInput');
    const statusFilter = document.getElementById('tableStatusFilter');
    const recordsTable = document.getElementById('recordsTable');

    if (recordsTable && (searchInput || statusFilter)) {
        function filterTable() {
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const statusVal = statusFilter ? statusFilter.value.toLowerCase().trim() : '';
            const rows = recordsTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const statusCell = row.querySelector('.badge-paid, .badge-unpaid');
                const statusText = statusCell ? statusCell.textContent.toLowerCase().trim() : '';

                const matchesQuery = !query || text.includes(query);
                const matchesStatus = !statusVal || statusText.includes(statusVal);

                if (matchesQuery && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterTable);
        if (statusFilter) statusFilter.addEventListener('change', filterTable);
    }

    // Sidebar Toggle for Mobile View
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.app-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Delete Confirmation dialog
    const deleteButtons = document.querySelectorAll('.btn-delete-confirm');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const item = this.dataset.item || 'this record';
            if (!confirm(`Are you sure you want to delete ${item}? This action cannot be undone.`)) {
                e.preventDefault();
            }
        });
    });

    // Alert dismissal handler (smooth fade out & removal)
    document.addEventListener('click', function(e) {
        const dismissBtn = e.target.closest('[data-bs-dismiss="alert"], .btn-close');
        if (dismissBtn) {
            const alertEl = dismissBtn.closest('.alert');
            if (alertEl) {
                alertEl.style.transition = 'opacity 0.25s ease-out, max-height 0.25s ease-out, margin 0.25s ease-out, padding 0.25s ease-out';
                alertEl.style.opacity = '0';
                alertEl.style.maxHeight = '0';
                alertEl.style.margin = '0';
                alertEl.style.padding = '0';
                alertEl.style.overflow = 'hidden';
                setTimeout(() => alertEl.remove(), 250);
            }
        }
    });
});
