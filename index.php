<?php 
define('BASE_URL', '/project1');
require_once(__DIR__ . "/class/users.php");
if(isset($_POST['register'])){
    $user = new users(
        full_name: trim($_POST['full_name'] ?? ''),
        username: trim($_POST['username'] ?? ''),
        email: trim($_POST['email'] ?? ''),
        contact: trim($_POST['contact'] ?? ''),
        password: $_POST['password'] ?? '',
        role: 'user'
    );
    echo $user->reg_user();
}
if(isset($_POST['login'])){
    $user = new users(
        full_name: '',
        username: trim($_POST['username'] ?? ''),
        password: $_POST['password'] ?? ''
    );
    echo $user->log_user();
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
        <center>

        
            <div class="card " style="border:1px solid; width:300px;height:100%" >
                <div class="card-header">
                    <h2>Login</h2>
                </div>
                <div class="card-body">
                    <form action="" method="post">
                        <label for="">User Name</label><br>
                        <input type="text" name="username" class="form-control"><br>
                        <label for="">Password</label><br>
                        <input type="password" name="password" class="form-control"><br>
                        <button type="submit" class="btn btn-primary" name="login">Login</button>
                    </form>

                </div>
                <div class="card-footer">
                    <h2>Register</h2>
                </div>
                <div class="card-body" style="margin: 10px;padding:5px">
                    <form action="" method="post">
                        <label for="">User Name</label><br>
                        <input type="text" name="username" class="form-control"><br>
                        <label for="">Full Name</label><br>
                        <input type="text" name="full_name" class="form-control"><br>
                        <label for="">Email</label><br>
                        <input type="email" name="email" class="form-control"><br>
                        <label for="">Contact</label><br>
                        <input type="text" name="contact" class="form-control"><br>
                        <label for="">Password</label><br>
                        <input type="password" name="password" class="form-control"><br>
                        <button type="submit" class="btn btn-primary" name="register">Register</button>
                    </form>
                    <hr>

                </div>
        
            </div>
        </center>
    </main>
</body>
</html>
