<?php
$host = "localhost"; // Host name
$username = "root";
$password = ""; // Mysql password
$db_name = "lm_test"; // Database name
$mysqli = mysqli_connect("$host", "$username", "$password", "$db_name") or die("cannot connect");
if (mysqli_connect_errno()) {
  echo "Failed to connect to MySQL: " . mysqli_connect_error();
}


$options = [
  \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
  \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
  \PDO::ATTR_EMULATE_PREPARES => false,
];

$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db_name;charset=$charset";
try {
  global $pdo;
  $pdo = new \PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
  // Log the detailed error
  error_log("PDO Connection Error in conn.php: " . $e->getMessage());
  // Make the script die explicitly to prevent further execution if $pdo is not set.
  die("Database connection failed. Please check server logs. PDO Error: " . htmlspecialchars($e->getMessage()));
}

?>