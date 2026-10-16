<?php
require_once __DIR__ . '/../../includes/session.php';
if (empty($_SESSION['USER_ID'])) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
if (($_SESSION['ROLE'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}
require_once __DIR__ . '/../../includes/dashboard.php';
