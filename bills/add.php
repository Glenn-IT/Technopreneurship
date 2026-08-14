<?php
// bills/add.php - Add Record Module
$pageTitle = 'Add New Water Bill Record';
require_once __DIR__ . '/../includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consumerName = trim($_POST['consumer_name'] ?? '');
    $meterNumber  = trim($_POST['meter_number'] ?? '');
    $billingMonth = trim($_POST['billing_month'] ?? '');
    $consumption  = (float)($_POST['consumption'] ?? 0);
    $amountDue    = (float)($_POST['amount_due'] ?? 0);
    $dueDate      = trim($_POST['due_date'] ?? '');
    $status       = trim($_POST['status'] ?? 'unpaid');
    $remarks      = trim($_POST['remarks'] ?? '');
    $csrfToken    = $_POST['csrf_token'] ?? '';

    if (!verifyCsrfToken($csrfToken)) {
        $error = 'Invalid security token. Please try again.';
    } elseif (empty($consumerName) || empty($meterNumber) || empty($billingMonth) || empty($dueDate)) {
        $error = 'Consumer Name, Meter Number, Billing Month, and Due Date are required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO bills (consumer_name, meter_number, billing_month, consumption, amount_due, due_date, status, remarks) 
                                   VALUES (:cn, :mn, :bm, :cs, :ad, :dd, :st, :rm)");
            $stmt->execute([
                'cn' => $consumerName,
                'mn' => $meterNumber,
                'bm' => $billingMonth,
                'cs' => $consumption,
                'ad' => $amountDue,
                'dd' => $dueDate,
                'st' => $status,
                'rm' => $remarks
            ]);

            setFlash('success', 'New water bill record for ' . htmlspecialchars($consumerName) . ' added successfully.');
            header('Location: ' . baseUrl('bills/index.php'));
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

$defaultMonth = date('F Y');
$defaultDueDate = date('Y-m-d', strtotime('+14 days'));
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-box">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                <div>
                    <h3 class="m-0" style="font-size:1.2rem; font-weight:700;">Add Water Billing Statement</h3>
                    <p class="text-muted m-0" style="font-size:0.85rem;">Create a new billing entry for consumer meter usage</p>
                </div>
                <a href="<?= baseUrl('bills/index.php'); ?>" class="btn-secondary-custom">
                    <i data-feather="arrow-left"></i> Back to List
                </a>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i data-feather="alert-circle" class="me-2"></i>
                    <div><?= sanitize($error); ?></div>
                </div>
            <?php endif; ?>

            <form action="" method="POST">
                <?= csrfField(); ?>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="consumer_name" class="form-label-custom">Consumer Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="consumer_name" id="consumer_name" class="form-control-custom" 
                               placeholder="e.g. Maria Santos" required value="<?= sanitize($_POST['consumer_name'] ?? ''); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="meter_number" class="form-label-custom">Meter Number <span class="text-danger">*</span></label>
                        <input type="text" name="meter_number" id="meter_number" class="form-control-custom" 
                               placeholder="e.g. MTR-10045" required value="<?= sanitize($_POST['meter_number'] ?? ''); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="billing_month" class="form-label-custom">Billing Month <span class="text-danger">*</span></label>
                        <input type="text" name="billing_month" id="billing_month" class="form-control-custom" 
                               placeholder="e.g. August 2026" required value="<?= sanitize($_POST['billing_month'] ?? $defaultMonth); ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="due_date" class="form-label-custom">Due Date <span class="text-danger">*</span></label>
                        <input type="date" name="due_date" id="due_date" class="form-control-custom" 
                               required value="<?= sanitize($_POST['due_date'] ?? $defaultDueDate); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="consumption" class="form-label-custom">Water Consumption (m³)</label>
                        <input type="number" step="0.01" min="0" name="consumption" id="consumption" class="form-control-custom" 
                               placeholder="0.00" value="<?= sanitize($_POST['consumption'] ?? '0.00'); ?>">
                        <small class="text-muted">Rate: ₱25.00 per cubic meter</small>
                    </div>

                    <div class="col-md-6">
                        <label for="amount_due" class="form-label-custom">Amount Due (₱) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="amount_due" id="amount_due" class="form-control-custom" 
                               placeholder="0.00" required value="<?= sanitize($_POST['amount_due'] ?? '0.00'); ?>">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="status" class="form-label-custom">Payment Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select-custom" required>
                            <option value="unpaid" <?= (($_POST['status'] ?? 'unpaid') === 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                            <option value="paid" <?= (($_POST['status'] ?? '') === 'paid') ? 'selected' : ''; ?>>Paid</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="remarks" class="form-label-custom">Remarks / Notes</label>
                        <input type="text" name="remarks" id="remarks" class="form-control-custom" 
                               placeholder="e.g. Paid via Cash / Notice sent" value="<?= sanitize($_POST['remarks'] ?? ''); ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                    <a href="<?= baseUrl('bills/index.php'); ?>" class="btn-secondary-custom">Cancel</a>
                    <button type="submit" class="btn-primary-custom">
                        <i data-feather="save"></i> Save Bill Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
