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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SMART SLOPE</title>
  <link rel="stylesheet" href="<?=BASE_URL?>/assets/css/bootstrap.min.css">
  <script src="<?=BASE_URL?>/assets/js/bootstrap.bundle.min.js" defer></script>
</head>
<body>
    <main class="container py-4" style="text-align:center;">
        <h1>SMART SLOPE</h1>
        <?php if ($error !== ''): ?>
            <p class="alert alert-danger" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <p class="alert alert-success" role="status"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <center>

        
            <div class="card " style="border:1px solid; width:300px;height:100%" >
                <div class="card-header">
                    <h2>Login</h2>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <label for="">User Name</label><br>
                        <input type="text" name="username" class="form-control" required><br>
                        <label for="">Password</label><br>
                        <input type="password" name="password" class="form-control" required><br>
                        <button type="submit" class="btn btn-primary" name="login">Login</button>
                    </form>

                </div>
                <div class="card-footer">
                    <h2>Register</h2>
                </div>
                <div class="card-body" style="margin: 10px;padding:5px">
                    <form action="" method="post">
                        <label for="">User Name</label><br>
                        <input type="text" name="username" class="form-control" required><br>
                        <label for="">Full Name</label><br>
                        <input type="text" name="full_name" class="form-control" required><br>
                        <label for="">Email</label><br>
                        <input type="email" name="email" class="form-control" required><br>
                        <label for="">Contact</label><br>
                        <input type="text" name="contact" class="form-control" required><br>
                        <label for="">Password</label><br>
                        <input type="password" name="password" class="form-control" required><br>
                        <button type="submit" class="btn btn-primary" name="register">Register</button>
                    </form>
                    <hr>

                </div>
        
            </div>
        </center>
    </main>
</body>
</html>
