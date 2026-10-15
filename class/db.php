<?php
class db{
  private $host = 'localhost';
  private $database ='smartslope_mvp';
  private $username ='root';
  private $password = 'root';
  private $port = 8889;

  protected function connect(){
  try{
    $dsn = "mysql:host={$this->host};dbname={$this->database}";
    $pdo = new PDO($dsn,$this->username,$this->password);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $pdo;
  }catch(PDOException $e){
    die("Connection Failed" . $e->getMessage());
  }
  
}
}



?>
