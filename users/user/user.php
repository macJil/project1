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
require_once __DIR__ . '/../../includes/header.php';
?>
<main class="container py-4">
    <h1>This is User</h1>
    <p>Welcome, <?= htmlspecialchars($_SESSION['FULLNAME'], ENT_QUOTES, 'UTF-8') ?>.</p>
    <p>Username: <?= htmlspecialchars($_SESSION['USERNAME'], ENT_QUOTES, 'UTF-8') ?></p>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/dashboard.php">View dashboard</a>
</main>
</body>
</html>
