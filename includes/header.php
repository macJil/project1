<?php
require_once __DIR__ . '/session.php';

if (empty($_SESSION['USER_ID']) || !in_array($_SESSION['ROLE'] ?? '', ['admin', 'user'], true)) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
$home = BASE_URL . ($_SESSION['ROLE'] === 'admin' ? '/users/admin/admin.php' : '/users/user/user.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SMART SLOPE</title>

  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <script src="<?= BASE_URL ?>/assets/js/bootstrap.bundle.min.js" defer></script>
 
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo $home?>">
      <img src="<?= BASE_URL ?>/assets/img/logo.jpeg"
           alt="Smart Slope logo"
           width="30"
           height="30"
           class="d-inline-block align-text-top">
      Smart Slope
    </a>

    <button class="navbar-toggler"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#navbarMenu"
            aria-controls="navbarMenu"
            aria-label="Open navigation menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="offcanvas offcanvas-end"
         tabindex="-1"
         id="navbarMenu"
         aria-labelledby="navbarMenuLabel">

      <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="navbarMenuLabel">Menu</h5>

        <button type="button"
                class="btn-close"
                data-bs-dismiss="offcanvas"
                aria-label="Close menu"></button>
      </div>

      <div class="offcanvas-body">
        <ul class="navbar-nav flex-column">
          <li class="nav-item">
            <a class="nav-link active text-dark" href="<?= $home ?>">Dashboard</a>
          </li>
          <?php if($_SESSION["ROLE"] === 'user'):?>
            <li class="nav-item">
              <a class="nav-link text-dark" href="<?= BASE_URL ?>/users/user/submit_report.php">Submit Report</a>
            </li>
          <?php else:?>
            <li class="nav-item">
              <a class="nav-link text-dark" href="<?= BASE_URL ?>/users/admin/admin_panel.php">Admin</a>
            </li>
          <?php endif?>
          <li class="nav-item">
            <a class="nav-link text-dark" href="<?= BASE_URL ?>/readings.php">Readings</a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="<?= BASE_URL ?>/methodology.php">Source/Methodology</a>
          </li>
          <li class="nav-item mt-3">
            
            <form action="<?= BASE_URL ?>/logout.php" method="post">
              <button type="submit" class="btn btn-outline-danger w-100">Log Out</button>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </div>
</nav>
