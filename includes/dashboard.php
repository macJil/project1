<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../class/events.php';
require_once __DIR__ . '/../class/locations.php';
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$admin = $_SESSION['ROLE'] === 'admin';
$pageTitle = $admin ? 'Admin dashboard' : 'Your dashboard';
$activePage = 'dashboard';
$event = new event();
$location = new location();
$showMap = true;
$locations = [];
$selectedLocation = null;
$readings = $reports = [];
$detail = null;
$totalLocations = $totalReports = $highReadings = $mediumReadings = null;
$error = '';
try {
    $locations = $location->get_locations();
    $locationId = filter_input(INPUT_GET, 'location_id', FILTER_VALIDATE_INT);
    if ($locationId) {
        $selectedLocation = $location->get_location($locationId);
        if (!$selectedLocation || !$selectedLocation['active']) {
            $selectedLocation = null;
            $error = 'That location is unavailable. Showing all recent readings.';
        }
    }
    $readings = array_slice($event->get_readings($selectedLocation['id'] ?? null), 0, 5);
    $totalLocations = $event->get_total_locations();
    $highReadings = $event->get_high_risk();
    $mediumReadings = $event->get_med_risk();
    if ($admin) {
        $reports = $event->get_pending_reports();
        $totalReports = $event->get_pending_reports_total();
    } else {
        $totalReports = $event->get_user_report_count($_SESSION['USER_ID']);
    }
    $id = filter_input(INPUT_GET, 'dialog_id', FILTER_VALIDATE_INT);
    if ($id && $id > 0) {
        $detail = $event->get_dialog($id);
    }
} catch (PDOException $exception) {
    error_log('SmartSlope dashboard: ' . $exception->getMessage());
    http_response_code(503);
    $error = 'Dashboard data is unavailable. Please try again later.';
    $readings = $reports = [];
    $totalLocations = $totalReports = $highReadings = $mediumReadings = null;
}
require_once __DIR__ . '/header.php';
?>
<main class="page-shell">
    <div class="page-heading">
        <div><span class="eyebrow">Barangay Irisan · Baguio City</span><h1><?= $admin ? 'Community overview' : 'Your community, at a glance' ?></h1><p class="page-subtitle">Welcome, <?= htmlspecialchars($_SESSION['FULLNAME'], ENT_QUOTES, 'UTF-8') ?>. <?= $admin ? 'Review saved readings and community reports.' : 'Explore saved weather readings for your community.' ?></p></div>
        <a class="btn btn-outline-primary" href="<?= BASE_URL ?>/readings.php">View readings <span aria-hidden="true">↗</span></a>
    </div>
    <?php if ($error): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body"><div class="stat-label">Monitored locations</div><div class="stat-number"><?= $totalLocations ?? '—' ?></div><p class="stat-note">Active locations in Irisan</p></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card brown"><div class="card-body"><div class="stat-label"><?= $admin ? 'Pending reports' : 'Your reports' ?></div><div class="stat-number"><?= $totalReports ?? '—' ?></div><p class="stat-note"><?= $admin ? 'Awaiting administrator review' : 'Reports submitted by your account' ?></p></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card"><div class="card-body"><div class="stat-label">Saved high readings</div><div class="stat-number"><?= $highReadings ?? '—' ?></div><p class="stat-note">Historical records · not live risk</p></div></div></div>
        <div class="col-6 col-lg-3"><div class="card stat-card brown"><div class="card-body"><div class="stat-label">Saved medium readings</div><div class="stat-number"><?= $mediumReadings ?? '—' ?></div><p class="stat-note">Historical records · not live risk</p></div></div></div>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-lg-8"><section class="card h-100" aria-labelledby="map-title">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2"><h2 id="map-title">Barangay Irisan map</h2><span class="role-tag">Irisan study area</span></div>
            <?php require __DIR__ . '/location-map.php'; ?>
        </section></div>
        <div class="col-lg-4"><section class="card h-100" aria-labelledby="assessment-title">
            <div class="card-header"><h2 id="assessment-title">Landslide awareness</h2></div>
            <div class="card-body"><div class="assessment-empty"><h3>Current assessment unavailable</h3><p>Live assessment will be added in Phase 4. Map selection currently shows saved records only.</p></div>
                <span class="eyebrow">Rainfall totals</span><div class="rainfall-grid"><div><span>1 hour</span><strong>—</strong></div><div><span>24 hours</span><strong>—</strong></div><div><span>72 hours</span><strong>—</strong></div></div>
                <p class="prototype-note mb-0">Academic prototype, not an official warning. No automatic evacuation instructions.</p>
            </div>
        </section></div>
    </div>
    <section class="card mb-4" aria-labelledby="readings-title">
        <div class="card-header d-flex align-items-center justify-content-between gap-2"><h2 id="readings-title">Recent weather readings<?= $selectedLocation ? ' · ' . htmlspecialchars($selectedLocation['name'], ENT_QUOTES, 'UTF-8') : '' ?></h2><a class="btn btn-sm btn-outline-primary" href="<?= BASE_URL ?>/readings.php">View all</a></div>
        <?php require __DIR__ . '/readings-table.php'; ?>
    </section>
    <?php if ($admin): ?>
        <section class="card" aria-labelledby="reports-title">
            <div class="card-header d-flex align-items-center justify-content-between gap-2"><h2 id="reports-title">Pending reports</h2><a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/users/admin/admin_panel.php">Management</a></div>
            <div class="table-responsive"><table class="table align-middle"><caption class="visually-hidden">Reports awaiting administrator review</caption><thead><tr><th scope="col">Location</th><th scope="col">Reporter</th><th scope="col">Contact</th><th scope="col">Submitted (UTC)</th><th scope="col">Status</th></tr></thead><tbody>
                <?php foreach ($reports as $report): ?><tr>
                    <td><?= htmlspecialchars($report['location_name'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($report['reporter'] ?? 'Former account', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($report['contact'] ?? 'Not provided', ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($report['report_date'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="risk-badge risk-medium">Pending</span></td>
                </tr><?php endforeach; ?>
                <?php if (!$reports): ?><tr><td colspan="5" class="empty-cell">No pending reports to review.</td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
    <?php else: ?>
        <section class="card action-card"><div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3"><div><h3>Notice something in your area?</h3><p class="mb-0 small">Ground reports help provide context alongside weather readings.</p></div><a class="btn btn-secondary" href="<?= BASE_URL ?>/users/user/submit_report.php">Ground reports</a></div></section>
    <?php endif; ?>
</main>
<?php require_once __DIR__ . '/footer.php'; ?>
