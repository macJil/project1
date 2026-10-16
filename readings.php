<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/class/events.php';
if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$pageTitle = 'Weather readings';
$activePage = 'readings';
$readings = [];
$detail = null;
$error = '';
try {
    $event = new event();
    $readings = $event->get_readings();
    $id = filter_input(INPUT_GET, 'dialog_id', FILTER_VALIDATE_INT);
    if ($id && $id > 0) {
        $detail = $event->get_dialog($id);
    }
} catch (PDOException $exception) {
    error_log('SmartSlope readings: ' . $exception->getMessage());
    http_response_code(503);
    $error = 'Saved readings are unavailable. Please try again later.';
}
require_once __DIR__ . '/includes/header.php';
?>
<main class="page-shell">
    <div class="page-heading"><div><span class="eyebrow">Weather history</span><h1>Saved readings</h1><p class="page-subtitle">Recorded observations for Irisan. Categories describe saved readings, not current warnings.</p></div><a class="btn btn-outline-primary" href="<?= $home ?>">Back to dashboard</a></div>
    <?php if ($error): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <section class="card"><div class="card-header"><h2>Weather observations</h2></div><?php require __DIR__ . '/includes/readings-table.php'; ?></section>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
