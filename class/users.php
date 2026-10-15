<?php
require_once __DIR__ . '/db.php';

class users extends db {
    // An empty message means registration succeeded.
    public function reg_user($full_name, $username, $email, $phone, $password) {
        if ($full_name === '' || $username === '' || $email === '' || $phone === '' || $password === '') {
            return 'Complete all registration fields.';
        }
        if (strlen($full_name) > 100 || strlen($username) > 50
            || strlen($email) > 254 || strlen($phone) > 20) {
            return 'One or more fields are too long.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Enter a valid email address.';
        }
        if (!preg_match('/^[0-9+() .-]{7,20}$/', $phone)) {
            return 'Enter a valid contact number (7 to 20 characters).';
        }
        if (strlen($password) < 8 || strlen($password) > 72) {
            return 'Use a password between 8 and 72 bytes long.';
        }

        $pdo = $this->connect();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? OR email = ? OR phone = ?');
        $stmt->execute([$username, $email, $phone]);
        if ($stmt->fetch()) {
            return 'That username, email, or contact number is already registered.';
        }

        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, phone, password, role)
            VALUES (?, ?, ?, ?, ?, 'user')");
        try {
            $stmt->execute([$full_name, $username, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
        } catch (PDOException $error) {
            // Another request may have registered the same details in the meantime.
            if (($error->errorInfo[1] ?? 0) == 1062) {
                return 'That username, email, or contact number is already registered.';
            }
            throw $error;
        }
        return '';
    }

    public function log_user($username, $password) {
        $stmt = $this->connect()->prepare('SELECT id, full_name, username, password, role FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }
        if ($user['role'] !== 'admin' && $user['role'] !== 'user') {
            return false;
        }

        // Keep only identity and role in the session, never the password.
        $_SESSION = [];
        $_SESSION['USER_ID'] = (int) $user['id'];
        $_SESSION['USERNAME'] = $user['username'];
        $_SESSION['FULLNAME'] = $user['full_name'];
        $_SESSION['ROLE'] = $user['role'];
        return true;
    }
}
