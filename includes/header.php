<?php
require_once __DIR__ . '/session.php';
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$home = BASE_URL . ($_SESSION['ROLE'] === 'admin' ? '/users/admin/admin.php' : '/users/user/user.php');
$pageTitle = $pageTitle ?? 'SmartSlope';
$activePage = $activePage ?? '';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | SmartSlope</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js" defer></script>
    <?php if (!empty($showMap)): ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/vendor/leaflet/leaflet.css">
    <script src="<?= BASE_URL ?>/assets/vendor/leaflet/leaflet.js" defer></script>
    <script src="<?= BASE_URL ?>/assets/js/location-map.js?v=p5-report-selection" defer></script>
    <?php endif; ?>
</head>
<body>
<nav class="navbar navbar-expand-lg site-nav py-3" aria-label="Main navigation">
    <div class="container-xl">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= $home ?>"><span class="brand-mark" aria-hidden="true">SS</span> SmartSlope</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarMenu" aria-controls="navbarMenu" aria-label="Open navigation"><span class="navbar-toggler-icon"></span></button>
        <div class="offcanvas offcanvas-end" tabindex="-1" id="navbarMenu" aria-labelledby="navbarMenuLabel">
            <div class="offcanvas-header"><h2 class="offcanvas-title h5" id="navbarMenuLabel">SmartSlope</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close navigation"></button></div>
            <div class="offcanvas-body align-items-center gap-3">
                <ul class="navbar-nav mx-lg-auto gap-1">
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>" <?= $activePage === 'dashboard' ? 'aria-current="page"' : '' ?> href="<?= $home ?>">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'readings' ? 'active' : '' ?>" <?= $activePage === 'readings' ? 'aria-current="page"' : '' ?> href="<?= BASE_URL ?>/readings.php">Readings</a></li>
                    <?php if ($_SESSION['ROLE'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'admin' ? 'active' : '' ?>" href="<?= BASE_URL ?>/users/admin/admin_panel.php">Management</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link <?= $activePage === 'report' ? 'active' : '' ?>" href="<?= BASE_URL ?>/users/user/submit_report.php">Submit report</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link <?= $activePage === 'methodology' ? 'active' : '' ?>" href="<?= BASE_URL ?>/methodology.php">Methodology</a></li>
                </ul>
                <span class="role-tag"><?= $_SESSION['ROLE'] === 'admin' ? 'Administrator' : 'Resident' ?></span>
                <form action="<?= BASE_URL ?>/logout.php" method="post" class="mt-3 mt-lg-0"><button type="submit" class="btn btn-sm btn-outline-secondary">Log out</button></form>
            </div>
        </div>
    </div>
</nav>
