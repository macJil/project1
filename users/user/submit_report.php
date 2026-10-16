<?php
require_once __DIR__ . '/../../includes/session.php';
if (empty($_SESSION['USER_ID'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
if (($_SESSION['ROLE'] ?? '') !== 'user') {
    http_response_code(403);
    exit('Access denied.');
}
$pageTitle = 'Ground reports';
$activePage = 'report';
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="page-shell"><div class="page-heading"><div><span class="eyebrow">SmartSlope · Irisan</span><h1>Ground reports</h1></div></div><section class="card"><div class="card-body"><p>Report submission is not available yet. You can browse saved weather readings from your dashboard.</p><a class="btn btn-outline-primary" href="<?= $home ?>">Back to dashboard</a></div></section></main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
