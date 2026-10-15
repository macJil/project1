<?php

?>
<?php include_once __DIR__ . "/../../includes/header.php"; ?>

  <div>
    <form action="" method="post">
      <h1>This is user</h1>
      <h1><?php echo $_SESSION['USERNAME'];?></h1>
      <h1><?php echo $_SESSION['FULLNAME'];?></h1>
      <h1><?php echo $_SESSION['PASSWORD'];?></h1>
      <h1><?php echo $_SESSION['ROLE'];?></h1>
      
     
    </form>
    
  </div>
</body>
</html>