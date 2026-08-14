<?php
// bills/add.php - Add Record Module
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$error = '';
$defaultMeter = 'MTR-' . rand(10000, 99999);
$defaultMonthInput = date('Y-m');
$defaultDueDate = date('Y-m-d', strtotime('+14 days'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $consumerName = trim($_POST['consumer_name'] ?? '');
    $meterNumber  = trim($_POST['meter_number'] ?? '');
    $rawMonth     = trim($_POST['billing_month'] ?? '');
    $consumption  = (float)($_POST['consumption'] ?? 0);
    $amountDue    = (float)($_POST['amount_due'] ?? 0);
    $dueDate      = trim($_POST['due_date'] ?? '');
    $status       = trim($_POST['status'] ?? 'unpaid');
    $remarks      = trim($_POST['remarks'] ?? '');
    $csrfToken    = $_POST['csrf_token'] ?? '';

    // Convert YYYY-MM datetime/month picker value to "Month Year" format (e.g., "August 2026")
    if (preg_match('/^\d{4}-\d{2}$/', $rawMonth)) {
        $billingMonth = date('F Y', strtotime($rawMonth . '-01'));
    } else {
        $billingMonth = $rawMonth;
    }

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

$pageTitle = 'Add New Water Bill Record';
require_once __DIR__ . '/../includes/header.php';

// Prepare value for month input
$currentMonthVal = $_POST['billing_month'] ?? $defaultMonthInput;
if (!preg_match('/^\d{4}-\d{2}$/', $currentMonthVal)) {
    $time = strtotime('1 ' . $currentMonthVal);
    if ($time !== false) {
        $currentMonthVal = date('Y-m', $time);
    } else {
        $currentMonthVal = $defaultMonthInput;
    }
}
$currentMeterVal = $_POST['meter_number'] ?? $defaultMeter;
$currentRemarksVal = $_POST['remarks'] ?? 'Regular Billing';
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
                        <div class="input-group">
                            <input type="text" name="meter_number" id="meter_number" class="form-control-custom" 
                                   placeholder="e.g. MTR-10045" required value="<?= sanitize($currentMeterVal); ?>">
                            <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-1" id="btnRandomMeter" title="Generate New Random Meter Number">
                                <i data-feather="refresh-cw" style="width:14px; height:14px;"></i> Random
                            </button>
                        </div>
                        <small class="text-muted">Auto-generated random meter number</small>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="billing_month" class="form-label-custom">Billing Month <span class="text-danger">*</span></label>
                        <input type="month" name="billing_month" id="billing_month" class="form-control-custom" 
                               required value="<?= sanitize($currentMonthVal); ?>">
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
                        <div class="d-flex flex-column gap-2">
                            <select id="remarks_select" class="form-select-custom">
                                <option value="Regular Billing" <?= ($currentRemarksVal === 'Regular Billing') ? 'selected' : ''; ?>>Regular Billing</option>
                                <option value="Paid on time" <?= ($currentRemarksVal === 'Paid on time') ? 'selected' : ''; ?>>Paid on time</option>
                                <option value="Payment received via OTC" <?= ($currentRemarksVal === 'Payment received via OTC') ? 'selected' : ''; ?>>Payment received via OTC</option>
                                <option value="Payment received via GCash / Online" <?= ($currentRemarksVal === 'Payment received via GCash / Online') ? 'selected' : ''; ?>>Payment received via GCash / Online</option>
                                <option value="First notice sent" <?= ($currentRemarksVal === 'First notice sent') ? 'selected' : ''; ?>>First notice sent</option>
                                <option value="Follow-up call requested" <?= ($currentRemarksVal === 'Follow-up call requested') ? 'selected' : ''; ?>>Follow-up call requested</option>
                                <option value="Overdue notice pending" <?= ($currentRemarksVal === 'Overdue notice pending') ? 'selected' : ''; ?>>Overdue notice pending</option>
                                <option value="Disconnection Warning" <?= ($currentRemarksVal === 'Disconnection Warning') ? 'selected' : ''; ?>>Disconnection Warning</option>
                                <option value="High Consumption Alert" <?= ($currentRemarksVal === 'High Consumption Alert') ? 'selected' : ''; ?>>High Consumption Alert</option>
                                <option value="Meter Inspection Required" <?= ($currentRemarksVal === 'Meter Inspection Required') ? 'selected' : ''; ?>>Meter Inspection Required</option>
                                <option value="custom">Other / Custom Remark...</option>
                            </select>
                            <input type="text" name="remarks" id="remarks" class="form-control-custom" 
                                   placeholder="Select remark or type custom note" value="<?= sanitize($currentRemarksVal); ?>">
                        </div>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Random Meter Number Generator
    const btnRandom = document.getElementById('btnRandomMeter');
    const inputMeter = document.getElementById('meter_number');
    if (btnRandom && inputMeter) {
        btnRandom.addEventListener('click', function() {
            const randNum = Math.floor(10000 + Math.random() * 90000);
            inputMeter.value = 'MTR-' + randNum;
        });
    }

    // 2. Remarks Combobox
    const remarksSelect = document.getElementById('remarks_select');
    const remarksInput = document.getElementById('remarks');
    if (remarksSelect && remarksInput) {
        remarksSelect.addEventListener('change', function() {
            if (this.value === 'custom') {
                remarksInput.value = '';
                remarksInput.focus();
            } else if (this.value !== '') {
                remarksInput.value = this.value;
            }
        });
        
        remarksInput.addEventListener('input', function() {
            let foundMatch = false;
            for (let i = 0; i < remarksSelect.options.length; i++) {
                if (remarksSelect.options[i].value === this.value) {
                    remarksSelect.selectedIndex = i;
                    foundMatch = true;
                    break;
                }
            }
            if (!foundMatch) {
                remarksSelect.value = 'custom';
            }
        });
    }

    // 3. Water Consumption Auto Amount Calculation (Rate: P25 / m3)
    const consumptionInput = document.getElementById('consumption');
    const amountDueInput = document.getElementById('amount_due');
    if (consumptionInput && amountDueInput) {
        consumptionInput.addEventListener('input', function() {
            const val = parseFloat(this.value) || 0;
            const rate = 25.00;
            amountDueInput.value = (val * rate).toFixed(2);
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
