<?php
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../class/reports.php';
if (empty($_SESSION['USER_ID'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
if (($_SESSION['ROLE'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}
$pageTitle = 'Report review';
$activePage = 'admin';
$showMap = true;
$statuses = ['pending', 'reviewed', 'resolved'];
$filter = filter_input(INPUT_GET, 'status');
if (!in_array($filter, $statuses, true)) $filter = 'all';
$reportModel = new reports();
$items = [];
$error = '';
$notice = $_SESSION['review_notice'] ?? '';
unset($_SESSION['review_notice']);
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $action = filter_input(INPUT_POST, 'action');
        $status = filter_input(INPUT_POST, 'status');
        if (!$id || !$reportModel->find($id)) {
            $error = 'Report not found.';
        } elseif ($action === 'status' && in_array($status, $statuses, true)) {
            $reportModel->set_status($id, $status, $_SESSION['USER_ID']);
            $_SESSION['review_notice'] = 'Report status updated.';
        } elseif ($action === 'delete') {
            $reportModel->delete($id);
            $_SESSION['review_notice'] = 'Report deleted.';
        } else {
            $error = 'Choose a valid report action and status.';
        }
        if (!$error) {
            header('Location: ' . BASE_URL . '/users/admin/reports.php?status=' . $filter, true, 303);
            exit;
        }
    }
    $items = $reportModel->get_reports($filter);
} catch (PDOException $exception) {
    error_log('SmartSlope report review: ' . $exception->getMessage());
    http_response_code(503);
    $error = 'Reports are unavailable. Please try again later.';
}
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="page-shell">
    <div class="page-heading"><div><span class="eyebrow">Administrator management</span><h1>Report review</h1><p class="page-subtitle">Review user-submitted observations. A resolved status is a workflow label, not confirmation that a slope is safe.</p></div><a class="btn btn-outline-primary" href="<?= BASE_URL ?>/users/admin/admin_panel.php">Location management</a></div>
    <?php if ($notice): ?><p class="alert alert-success" role="status"><?= htmlspecialchars($notice, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <nav class="nav nav-pills gap-2 mb-3" aria-label="Report status filters">
    <?php foreach (['all', 'pending', 'reviewed', 'resolved'] as $status): ?><a class="nav-link <?= $filter === $status ? 'active' : '' ?>" <?= $filter === $status ? 'aria-current="page"' : '' ?> href="?status=<?= $status ?>"><?= ucfirst($status) ?></a><?php endforeach; ?>
    </nav>
    <section class="card"><?php $bulkKind = 'reports'; require __DIR__ . '/../../includes/bulk-tools.php'; ?><div class="table-responsive"><table class="table align-middle"><thead><tr><th><input type="checkbox" data-select-all="bulk-reports" aria-label="Select all displayed reports"></th><th>Location</th><th>Reporter</th><th>Submitted (UTC)</th><th>Status</th><th>Details</th></tr></thead><tbody>
    <?php foreach ($items as $report): ?><tr data-report-id="<?= (int) $report['id'] ?>"><td><input type="checkbox" name="ids[]" form="bulk-reports" value="<?= (int) $report['id'] ?>" aria-label="Select report <?= (int) $report['id'] ?>"></td><td><?= htmlspecialchars($report['name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($report['reporter'] ?? 'Former account', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($report['created_at'], ENT_QUOTES, 'UTF-8') ?></td><td><?= ucfirst($report['status']) ?></td><td><button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#report-<?= (int) $report['id'] ?>">View</button></td></tr><?php endforeach; ?>
    <?php if (!$items): ?><tr><td colspan="6" class="empty-cell">No reports match this status.</td></tr><?php endif; ?>
    </tbody></table></div></section>
    <?php foreach ($items as $report): ?>
    <div class="modal fade" id="report-<?= (int) $report['id'] ?>" tabindex="-1" aria-labelledby="report-title-<?= (int) $report['id'] ?>" aria-hidden="true"><div class="modal-dialog modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h2 class="modal-title fs-5" id="report-title-<?= (int) $report['id'] ?>">Report #<?= (int) $report['id'] ?></h2><button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button></div>
        <div class="modal-body">
            <p class="fw-semibold"><?= htmlspecialchars($report['name'], ENT_QUOTES, 'UTF-8') ?></p>
            <div class="report-map mb-2" style="height: 300px; min-height: 300px; width: 100%;" data-lat="<?= htmlspecialchars($report['lat'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-lng="<?= htmlspecialchars($report['lng'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-risk="<?= htmlspecialchars($report['risk_level'] ?? 'unknown', ENT_QUOTES, 'UTF-8') ?>" data-base="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>" aria-label="Reported location map"></div>
            <p class="report-map-status small" role="status"><?= htmlspecialchars($report['map_note'], ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars(($report['lat'] ?? 'Unavailable') . ', ' . ($report['lng'] ?? 'Unavailable'), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="small">Marker color: latest saved risk for the linked location — <?= htmlspecialchars(ucfirst($report['risk_level'] ?? 'No reading'), ENT_QUOTES, 'UTF-8') ?>. Review status is separate.</p>
            <p>Type: <?= htmlspecialchars($report['report_type'] ?? 'Not specified', ENT_QUOTES, 'UTF-8') ?></p>
            <p class="report-message"><?= nl2br(htmlspecialchars($report['message'] ?? '', ENT_QUOTES, 'UTF-8')) ?></p>
            <dl class="detail-list">
            <?php foreach (['house_landmark' => 'Nearby landmark', 'contact_phone' => 'Contact number', 'contact_email' => 'Email', 'occurred_at' => 'Observed (UTC)', 'reviewed_at' => 'Last review (UTC)'] as $field => $label): ?><div><dt><?= $label ?></dt><dd><?= htmlspecialchars($report[$field] ?: 'Not provided', ENT_QUOTES, 'UTF-8') ?></dd></div><?php endforeach; ?>
            </dl>
            <form method="post" action="?status=<?= $filter ?>" class="mt-3">
                <input type="hidden" name="id" value="<?= (int) $report['id'] ?>"><input type="hidden" name="action" value="status">
                <label class="form-label" for="status-<?= (int) $report['id'] ?>">Review status</label>
                <select class="form-select mb-3" id="status-<?= (int) $report['id'] ?>" name="status"><?php foreach ($statuses as $status): ?><option value="<?= $status ?>" <?= $status === $report['status'] ? 'selected' : '' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select>
                <button class="btn btn-primary" type="submit">Save review status</button>
            </form>
            <form method="post" action="?status=<?= $filter ?>" class="mt-3" onsubmit="return confirm('Permanently delete this report?');"><input type="hidden" name="id" value="<?= (int) $report['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-outline-danger" type="submit">Delete report</button></form>
        </div>
    </div></div></div>
    <?php endforeach; ?>
</main>
<script src="<?= BASE_URL ?>/assets/js/report-maps.js?v=p5-map-fixes" defer></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
