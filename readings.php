<?php 

include_once __DIR__ . "/includes/header.php"; ?>
<div class="container text-center" style="margin-top: 5px; padding:5px">
  <div class="row">
    <div class="col ">
      <h1>Weather Readings</h1>
    </div>
    <div class="col">
    
    </div>
    <div class="col">
      <button class="btn btn-danger">Remove Selected</button>
      <button class="btn btn-success">Export Selected</button>
    </div>
  </div>
</div>
<div class="container">
  <form action="" method="post">
    <table class="table table-striped">
      <tr>
        <th>Location</th>
        <th>Current assessment</th>
        <th>Rainfall <br>
          mm1h / 24h / 72h</th>
        <th>Provider time (PHT)</th>
        <th>Data status</th>
        <th>Details</th>
        <?php if($_SESSION["ROLE"] === 'admin'):?>
          <th>Action</th>
        <?php endif?>
      </tr>

    </table>
  </form>
</div>
 <h1 ><?php echo $_SESSION['USERNAME'];?></h1>
 <h1><?php echo $_SESSION['FULLNAME'];?></h1>
  <h1><?php echo $_SESSION['PASSWORD'];?></h1>
  <h1><?php echo $_SESSION['ROLE'];?></h1>
</body>
</html>