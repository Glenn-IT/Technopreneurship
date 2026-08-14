<?php
// includes/footer.php - Global Footer Component
?>
        </main> <!-- End content-container -->
    </div> <!-- End main-wrapper -->
</div> <!-- End app-wrapper -->

<!-- Bootstrap 5 JS & Feather Icons Replacement -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof feather !== 'undefined') {
            feather.replace();
        }
    });
</script>
<script src="<?= baseUrl('assets/js/main.js'); ?>"></script>
</body>
</html>
