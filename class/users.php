<?php


require_once(__DIR__ . '/db.php');
class users extends db{
  
  const REG_SUCCESS = "<script>alert('Registration Successfull!');</script>";
  const REG_UNSUCCESS = "<script>alert('Registration Unsuccessfull!');</script>";
  const LOG_UNSUCCESS = "<script>alert('Login Unsuccessfull/Error!');</script>";
  const LOG_INVALID = "<script>alert('Unsuccessfull Invalid username or password!');</script>";
  const ADMIN_SUCCESS = "<script>alert('Login successful!'); location.href='" . BASE_URL . "/users/admin/admin.php';</script>";
  const USER_SUCCESS = "<script>alert('Login successful!'); location.href='" . BASE_URL . "/users/user/user.php';</script>";
  const LOG_OUT = "<script>alert('Log Out successful!'); location.href='" . BASE_URL . "/index.php';</script>";
  public function __construct(
    private string $full_name,
    private string $username,
    private ?string $email = null,
    private ?string $contact = null,
    private ?string $password = null,
    private ?string $role = null
  )
  {
    $this->full_name = $full_name;
    $this->username = $username;
    $this->email = $email;
    $this->contact = $contact;
    $this->password = $password;
    $this->role = $role;
  }
  public function sessions(array $user)
  {
    session_start();
    
    $_SESSION['USERNAME'] = $user['username'];
    $_SESSION['FULLNAME'] = $user['full_name'];
    $_SESSION['EMAIL'] = $user['email'];
    $_SESSION['CONTACT'] = $user['contact'];
    $_SESSION['PASSWORD'] = $this->password;
    $_SESSION["ROLE"] = $user['role'];
    $_SESSION['LOG_OUT'] = self::LOG_OUT;
  }

  public function reg_user(){
    $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users(full_name,username,email,phone,password,role) VALUES(:full_name,:username,:email,:contact,:password,:role)";
   
    $stmt = $this->connect()->prepare($sql)->execute([
      ":full_name"=>$this->full_name,
      ":username"=>$this->username,
      ":email"=>$this->email,
      ":contact"=>$this->contact,
      ":password"=>$hashedPassword,
      ":role"=>$this->role
    ]);
    if($stmt){
        return self::REG_SUCCESS;
    } else {
      return self:: REG_UNSUCCESS;
    }
  }
  public function log_user() {
    $stmt = $this->connect()->prepare(
    "SELECT * FROM users WHERE username = ?");

    $stmt->execute([$this->username]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($this->password, $user['password'])) {

        if ($user['role'] === 'admin') {
            $this->sessions($user);
            return self::ADMIN_SUCCESS;
        }

        // Accounts created before the role field was saved are regular users.
        if (empty($user['role']) || $user['role'] === 'user') {
            $this->sessions($user);
            return self ::USER_SUCCESS;
        }

    }

    return self::LOG_INVALID;
  }
}
