<?php
// XAMPP: /project1. Herd at project1.test: use an empty string.
const BASE_URL = '/project1';

// Set these values to match your local MySQL server (Herd does not supply MySQL).
const DB_HOST = '127.0.0.1';
const DB_PORT = 8889;
const DB_NAME = 'smartslope_mvp';
const DB_USER = 'root';
const DB_PASS = 'root';

// Existing dashboard display helpers.
function check_role() {
    return ($_SESSION['ROLE'] ?? '') === 'admin' ? '' : 'hidden';
}

function check_status($status) {
    return $status ? 'Outdated' : 'Current';
}

function check_report_status($status) {
    return $status === 'pending' ? 'Review' : 'Resolve';
}
