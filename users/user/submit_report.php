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
<main class="container py-4"><h1>Submit report</h1><p>This component will be added in a later phase.</p></main>
</body>
</html>
