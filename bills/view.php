<?php
// bills/view.php - View Bill Record (Read-Only)
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$billId = (int)($_GET['id'] ?? 0);

if ($billId <= 0) {
    setFlash('danger', 'Invalid bill ID.');
    header('Location: ' . baseUrl('bills/index.php'));
    exit;
}

// Fetch the bill record
try {
    $stmt = $pdo->prepare("SELECT * FROM tblaprilyn WHERE bill_id = :id LIMIT 1");
    $stmt->execute(['id' => $billId]);
    $bill = $stmt->fetch();

    if (!$bill) {
        setFlash('danger', 'Bill record not found.');
        header('Location: ' . baseUrl('bills/index.php'));
        exit;
    }
} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . baseUrl('bills/index.php'));
    exit;
}

$pageTitle = 'View Water Bill #' . str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT);
require_once __DIR__ . '/../includes/header.php';

$isPaid = strtolower(trim($bill['status'])) === 'paid';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card-box">

            <!-- Page Header -->
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                <div>
                    <h3 class="m-0" style="font-size:1.2rem; font-weight:700;">
                        Bill Record&nbsp;<span style="font-weight:800; color:var(--primary);">#<?= str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </h3>
                    <p class="text-muted m-0" style="font-size:0.85rem;">
                        Read-only view of billing statement for <strong><?= sanitize($bill['consumer_name']); ?></strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= baseUrl('bills/edit.php?id=' . $bill['bill_id']); ?>" class="btn-primary-custom">
                        <i data-feather="edit-2"></i> Edit
                    </a>
                    <a href="<?= baseUrl('bills/index.php'); ?>" class="btn-secondary-custom">
                        <i data-feather="arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <?= getFlash(); ?>

            <!-- Status Banner -->
            <div class="view-status-banner mb-4 <?= $isPaid ? 'status-paid' : 'status-unpaid'; ?>">
                <div class="d-flex align-items-center gap-3">
                    <div class="status-icon-wrap">
                        <?php if ($isPaid): ?>
                            <i data-feather="check-circle"></i>
                        <?php else: ?>
                            <i data-feather="clock"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="status-label">Payment Status</div>
                        <div class="status-value"><?= $isPaid ? 'Paid' : 'Unpaid'; ?></div>
                    </div>
                    <div class="ms-auto text-end">
                        <div class="status-label">Amount Due</div>
                        <div class="amount-value"><?= formatMoney($bill['amount_due']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Detail Grid -->
            <div class="view-detail-grid">

                <div class="detail-section">
                    <div class="detail-section-title">
                        <i data-feather="user"></i> Consumer Information
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Full Name</span>
                        <span class="detail-value"><?= sanitize($bill['consumer_name']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Meter Number</span>
                        <span class="detail-value"><code><?= sanitize($bill['meter_number']); ?></code></span>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">
                        <i data-feather="calendar"></i> Billing Period
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Billing Month</span>
                        <span class="detail-value"><?= sanitize($bill['billing_month']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Due Date</span>
                        <span class="detail-value"><?= sanitize($bill['due_date']); ?></span>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">
                        <i data-feather="droplet"></i> Consumption & Charges
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Water Consumption</span>
                        <span class="detail-value"><?= number_format($bill['consumption'], 2); ?> m³</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Rate per m³</span>
                        <span class="detail-value">₱25.00</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount Due</span>
                        <span class="detail-value"><strong><?= formatMoney($bill['amount_due']); ?></strong></span>
                    </div>
                </div>

                <div class="detail-section">
                    <div class="detail-section-title">
                        <i data-feather="file-text"></i> Additional Notes
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Remarks</span>
                        <span class="detail-value"><?= sanitize($bill['remarks'] ?? '—'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Bill ID</span>
                        <span class="detail-value"><strong>#<?= str_pad($bill['bill_id'], 5, '0', STR_PAD_LEFT); ?></strong></span>
                    </div>
                </div>

            </div>

            <!-- Footer Actions -->
            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                <a href="<?= baseUrl('bills/delete.php?id=' . $bill['bill_id']); ?>"
                   class="btn-action-delete btn-delete-confirm"
                   data-item="Bill #<?= $bill['bill_id']; ?> for <?= sanitize($bill['consumer_name']); ?>"
                   title="Delete Bill">
                    <i data-feather="trash-2"></i> Delete
                </a>
                <a href="<?= baseUrl('bills/edit.php?id=' . $bill['bill_id']); ?>" class="btn-primary-custom">
                    <i data-feather="edit-2"></i> Edit Record
                </a>
            </div>

        </div>
    </div>
</div>

<style>
/* ── View Page – Status Banner ── */
.view-status-banner {
    border-radius: 10px;
    padding: 1.1rem 1.4rem;
}
.view-status-banner.status-paid {
    background: linear-gradient(135deg, #d1fae5 0%, #ecfdf5 100%);
    border: 1px solid #6ee7b7;
    color: #065f46;
}
.view-status-banner.status-unpaid {
    background: linear-gradient(135deg, #fef3c7 0%, #fffbeb 100%);
    border: 1px solid #fcd34d;
    color: #92400e;
}
.status-icon-wrap {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: rgba(0,0,0,0.08);
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.status-icon-wrap svg { width: 20px; height: 20px; }
.status-label  { font-size: 0.72rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.75; }
.status-value  { font-size: 1.05rem; font-weight: 700; }
.amount-value  { font-size: 1.4rem; font-weight: 800; }

/* ── Detail Grid ── */
.view-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}
@media (max-width: 576px) {
    .view-detail-grid { grid-template-columns: 1fr; }
}

.detail-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 1.1rem 1.2rem;
}
.detail-section-title {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: var(--primary, #4f46e5);
    margin-bottom: 0.85rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
}
.detail-section-title svg { width: 14px; height: 14px; }

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 0.4rem 0;
    border-bottom: 1px dashed #e2e8f0;
    gap: 0.5rem;
}
.detail-row:last-child { border-bottom: none; padding-bottom: 0; }

.detail-label {
    font-size: 0.8rem;
    color: #64748b;
    font-weight: 500;
    white-space: nowrap;
    flex-shrink: 0;
}
.detail-value {
    font-size: 0.88rem;
    color: #1e293b;
    font-weight: 500;
    text-align: right;
}
.detail-value code {
    background: #e0f2fe;
    color: #0369a1;
    padding: 0.1rem 0.4rem;
    border-radius: 4px;
    font-size: 0.82rem;
}

/* ── Delete button inline fix ── */
.btn-action-delete {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.45rem 0.9rem;
    border-radius: 7px;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    border: 1px solid #fca5a5;
    background: #fff1f2;
    color: #dc2626;
    transition: background 0.18s, border-color 0.18s;
}
.btn-action-delete:hover {
    background: #fee2e2;
    border-color: #f87171;
    color: #b91c1c;
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
