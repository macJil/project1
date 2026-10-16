<?php
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/class/users.php';

if (!empty($_SESSION['USER_ID']) && in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    $page = $_SESSION['ROLE'] === 'admin' ? '/users/admin/admin.php' : '/users/user/user.php';
    header('Location: ' . BASE_URL . $page);
    exit;
}

$error = '';
$success = $_SESSION['registration_success'] ?? '';
unset($_SESSION['registration_success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values = [];
    foreach (['full_name', 'username', 'email', 'contact', 'password'] as $field) {
        $values[$field] = isset($_POST[$field]) && is_string($_POST[$field]) ? $_POST[$field] : '';
        if ($field !== 'password') {
            $values[$field] = trim($values[$field]);
        }
    }

    try {
        $user = new users();
        if (isset($_POST['register'])) {
            $error = $user->reg_user($values['full_name'], $values['username'],
                $values['email'], $values['contact'], $values['password']);
            if ($error === '') {
                $_SESSION['registration_success'] = 'Registration successful. You can now log in.';
                header('Location: ' . BASE_URL . '/index.php', true, 303);
                exit;
            }
        } elseif (isset($_POST['login'])) {
            if ($values['username'] === '' || $values['password'] === '') {
                $error = 'Enter your username and password.';
            } elseif ($user->log_user($values['username'], $values['password'])) {
                $page = $_SESSION['ROLE'] === 'admin' ? '/users/admin/admin.php' : '/users/user/user.php';
                header('Location: ' . BASE_URL . $page, true, 303);
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Choose Login or Register.';
        }
    } catch (PDOException $exception) {
        error_log('SmartSlope database: ' . $exception->getMessage());
        http_response_code(503);
        $error = 'Database unavailable. Check that MySQL is running and config.php matches your database.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome | SmartSlope</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<main class="auth-shell">
    <div class="auth-intro"><span class="brand-mark" aria-hidden="true">SS</span><h1>SmartSlope</h1><p class="page-subtitle">Landslide awareness for Barangay Irisan</p></div>
    <?php if ($error !== ''): ?><p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <?php if ($success !== ''): ?><p class="alert alert-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
    <div class="card auth-card">
        <section class="card-body"><h2>Welcome back</h2><p class="text-muted small mb-4">Sign in to view your community dashboard.</p>
            <form method="post">
                <div class="form-floating mb-3"><input id="login-username" name="username" class="form-control" placeholder="Username" autocomplete="username" required><label for="login-username">Username</label></div>
                <div class="form-floating mb-3"><input id="login-password" type="password" name="password" class="form-control" placeholder="Password" autocomplete="current-password" required><label for="login-password">Password</label></div>
                <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
            </form>
        </section>
        <section class="card-body auth-register"><h2>Join your community</h2><p class="text-muted small mb-4">Create a resident account.</p>
            <form method="post">
                <div class="form-floating mb-3"><input id="register-username" type="text" name="username" class="form-control" placeholder="Username" autocomplete="username" maxlength="50" required><label for="register-username">Username</label></div>
                <div class="form-floating mb-3"><input id="register-full_name" type="text" name="full_name" class="form-control" placeholder="Full name" autocomplete="name" maxlength="100" required><label for="register-full_name">Full name</label></div>
                <div class="form-floating mb-3"><input id="register-email" type="email" name="email" class="form-control" placeholder="Email address" autocomplete="email" maxlength="254" required><label for="register-email">Email address</label></div>
                <div class="form-floating mb-3"><input id="register-contact" type="tel" name="contact" class="form-control" placeholder="Contact number" autocomplete="tel" maxlength="20" required><label for="register-contact">Contact number</label></div>
                <div class="form-floating mb-3"><input id="register-password" type="password" name="password" class="form-control" placeholder="Password" autocomplete="new-password" maxlength="72" required><label for="register-password">Password</label></div>
                <p class="small text-muted">Use a password of 8–72 bytes.</p>
                <button type="submit" name="register" class="btn btn-outline-primary w-100">Create account</button>
            </form>
        </section>
    </div>
    <p class="prototype-note text-center mt-4">Academic prototype · Not an official warning service</p>
</main>
</body>
</html>
