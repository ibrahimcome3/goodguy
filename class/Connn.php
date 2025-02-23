<?php
class Connn
{
  public $dbc;
  private $host = "localhost"; // Host name
  private $username = "root";
  private $password = ""; // Mysql password
  private $db_name = "lm_test"; // Database name



  function __construct()
  {
    $options = [
      \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
      \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
      \PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $host = $this->host;
    $db = $this->db_name;
    $user = $this->username;
    $pass = $this->password;
    $charset = 'utf8mb4';
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    try {
      $pdo = new \PDO($dsn, $user, $pass, $options);
      $this->dbc = $pdo;
    } catch (\PDOException $e) {
      throw new \PDOException($e->getMessage(), (int) $e->getCode());
    }

  }

  public function getConnection()
  {
    return $this->dbc;
  }






}

?>