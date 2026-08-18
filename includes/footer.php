<?php
// includes/footer.php - Global Footer Component
?>
        </main> <!-- End content-container -->
    </div> <!-- End main-wrapper -->
</div> <!-- End app-wrapper -->

<!-- ── Logout Confirmation Modal ──────────────────────────────────────────── -->
<div id="logoutModal" class="logout-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle" aria-hidden="true">
    <div class="logout-modal-box">
        <div class="logout-modal-icon">
            <i data-feather="log-out"></i>
        </div>
        <h4 class="logout-modal-title" id="logoutModalTitle">Sign Out</h4>
        <p class="logout-modal-msg">Are you sure you want to logout?</p>
        <div class="logout-modal-actions">
            <button type="button" id="logoutCancelBtn" class="logout-btn-no">
                <i data-feather="x"></i> No, Stay
            </button>
            <a href="" id="logoutConfirmBtn" class="logout-btn-yes">
                <i data-feather="log-out"></i> Yes, Logout
            </a>
        </div>
    </div>
</div>

<style>
/* ── Overlay ── */
.logout-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    padding: 1rem;
}
.logout-modal-overlay.show {
    display: flex;
    animation: fadeInOverlay 0.18s ease;
}
@keyframes fadeInOverlay {
    from { opacity: 0; }
    to   { opacity: 1; }
}

/* ── Box ── */
.logout-modal-box {
    background: #ffffff;
    border-radius: 16px;
    padding: 2rem 2rem 1.75rem;
    max-width: 360px;
    width: 100%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.18);
    animation: slideUpBox 0.22s cubic-bezier(0.34, 1.56, 0.64, 1);
}
@keyframes slideUpBox {
    from { opacity: 0; transform: translateY(24px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1);    }
}

/* ── Icon ── */
.logout-modal-icon {
    width: 58px;
    height: 58px;
    border-radius: 50%;
    background: #fff1f2;
    border: 2px solid #fecaca;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.1rem;
    color: #ef4444;
}
.logout-modal-icon svg { width: 26px; height: 26px; stroke-width: 2; }

/* ── Text ── */
.logout-modal-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: #0f172a;
    margin: 0 0 0.35rem;
}
.logout-modal-msg {
    font-size: 0.88rem;
    color: #64748b;
    margin: 0 0 1.5rem;
}

/* ── Buttons ── */
.logout-modal-actions {
    display: flex;
    gap: 10px;
}
.logout-btn-no,
.logout-btn-yes {
    flex: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 9px;
    font-size: 0.85rem;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.16s ease;
    border: none;
}
.logout-btn-no {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}
.logout-btn-no:hover {
    background: #e2e8f0;
    color: #1e293b;
}
.logout-btn-yes {
    background: #ef4444;
    color: #ffffff;
}
.logout-btn-yes:hover {
    background: #dc2626;
    color: #ffffff;
}
.logout-btn-no svg,
.logout-btn-yes svg { width: 14px; height: 14px; }
</style>

<!-- Bootstrap 5 JS & Feather Icons Replacement -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof feather !== 'undefined') feather.replace();
    });
</script>
<script src="<?= baseUrl('assets/js/main.js'); ?>"></script>

<script>
(function () {
    const modal      = document.getElementById('logoutModal');
    const confirmBtn = document.getElementById('logoutConfirmBtn');
    const cancelBtn  = document.getElementById('logoutCancelBtn');

    if (!modal) return;

    function openModal(href) {
        confirmBtn.href = href;
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        if (typeof feather !== 'undefined') feather.replace();
        cancelBtn.focus();
    }

    function closeModal() {
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden', 'true');
    }

    // Intercept all logout links
    document.querySelectorAll('[data-logout-confirm]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            openModal(this.href);
        });
    });

    // Close on Cancel
    cancelBtn.addEventListener('click', closeModal);

    // Close on overlay click (outside box)
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && modal.classList.contains('show')) closeModal();
    });
})();
</script>
</body>
</html>
