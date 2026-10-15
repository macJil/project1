<?php
session_start();
if(isset($_POST['log_out'])){
  session_destroy();
  echo $_SESSION['LOG_OUT'];
}
if(empty($_SESSION["USERNAME"])){
  session_destroy();
  header("Location: /project1/index.php");
}

?>
<?php include_once __DIR__ . "/../../includes/header.php"; ?>

  <div>
    <form action="" method="post">
      <h1>This is Admin</h1>
      <h1><?php echo $_SESSION['USERNAME'];?></h1>
      <h1><?php echo $_SESSION['FULLNAME'];?></h1>
      <h1><?php echo $_SESSION['PASSWORD'];?></h1>
      <h1><?php echo $_SESSION['ROLE'];?></h1>
      
     
    </form>
    
</body>
</html>