<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Sms.php'; // Add this line


// require_once "Order.php"; // Self-inclusion, likely not needed here
require_once "ProductItem.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Sms;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class User
{
   public $user_id;
   public $user_email;
   public $user_address;
   public $user_role;
   private $shipping_address_table = "shipping_address"; // Name of your shipping address table

   private $phone_number_table = "phonenumber";
   private $customer_table = "customer";
   public $pdo;
   public const ORDER_STATUS = [
      'PENDING' => 'Pending',
      'PROCESSING' => 'Processing',
      'SHIPPED' => 'Shipped',
      'DELIVERED' => 'Delivered',
      'CANCELLED' => 'Cancelled',
   ];


   function __construct($pdo)
   {

      if (isset($_SESSION['uid'])) {
         $this->user_id = $_SESSION['uid'];
      }
      $this->pdo = $pdo; // *** CHANGE THIS: Pass PDO ***


      if (isset($this->user_id)) {
         // Use a prepared statement to prevent SQL injection
         $sql = "SELECT * FROM `customer` WHERE `customer_id` = :user_id";
         $stmt = $this->pdo->prepare($sql);
         $stmt->execute([':user_id' => $this->user_id]);
         $row = $stmt->fetch();

         if ($row) {
            $this->user_email = $row['customer_email'] ?? null;
            $this->user_address = ($row['customer_address1'] ?? '') . " " . ($row['customer_address2'] ?? '');
            // Check if 'user_role' key exists before assigning it to prevent warning
            $this->user_role = $row['user_role'] ?? null;
         }
      }
   }
   public function getUserAddresses($userId)
   {
      $pdo = $this->pdo;
      $stmt = $pdo->prepare("SELECT * FROM shipping_address WHERE customer_id = ?");
      $stmt->execute([$userId]);
      $addresses = $stmt->fetchAll(PDO::FETCH_ASSOC);

      return $addresses ? $addresses : [];
   }
   function getShippingAddress($mysqli, $order, $u)
   {
      // Check if the shipping address is already in the order details
      if (!empty($order['order_shipping_address'])) {
         return $order['order_shipping_address'];
      }

      // If not, try to get it from the user's profile
      $user_id = $order['customer_id'];
      $user_details = $this->getCustomerById($mysqli, $user_id); // Changed to use getCustomerById

      if ($user_details && !empty($user_details['shipping_address'])) {
         return $user_details['shipping_address'];
      }

      // If not found in either place, return a message
      return "Shipping address not available.";
   }
   function get_user_records()
   {
      $pdo = $this->pdo;
      $sql = "SELECT * FROM `customer` WHERE `customer_id` =  " . $this->user_id;
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch();
      return $row;
   }



   function get_address()
   {
      return $this->user_address;

   }
   function get_email()
   {
      return $this->user_email;
   }

   function update_user_credentials($fname, $lname, $display_name, $email_address)
   {
      $sql = "UPDATE `customer` SET `customer_fname`='$fname',`customer_lname`='$lname',`customer_email`='$email_address', `customer_display_name`='$display_name'  WHERE `customer_id` = " . $this->user_id;
      $pdo = $this->pdo;
      $stmt = $pdo->query($sql);
      if ($stmt) {
         return true;
      } else {
         return false;
      }
   }

   /**
    * Gets the total number of registered customers.
    *
    * @return integer
    */
   public function getTotalUserCount(): int
   {
      try {
         $stmt = $this->pdo->query("SELECT COUNT(*) FROM customer");
         return (int) $stmt->fetchColumn();
      } catch (PDOException $e) {
         error_log("Database error fetching total user count: " . $e->getMessage());
         return 0;
      }
   }

   function update_user_credentials_password($fname, $lname, $display_name, $email_address, $password_)
   {
      $sql = "UPDATE `customer` SET `customer_fname`='$fname',`customer_lname`='$lname',`customer_email`='$email_address', `customer_display_name`='$display_name' , password = '$password_' WHERE `customer_id` = " . $this->user_id;
      $pdo = $this->pdo;
      $stmt = $pdo->query($sql);
      if ($stmt) {
         return true;
      } else {
         return false;
      }
   }

   function get_password()
   {
      $pdo = $this->pdo;
      $sql = "SELECT password FROM `customer` WHERE `customer_id` =  " . $this->user_id;
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch();
      return $row['password'];
   }

   function get_address_()
   {
      $pdo = $this->pdo;
      $sql = "select * from shipping_address left join shipping_state on shipping_address.state = shipping_state.state_id where customer_id = " . $this->user_id;
      $stmt = $pdo->query($sql);
      return $stmt;
   }

   /**
    * Registers a new user.
    *
    * @param array $data User data including firstname, lastname, email, phone, and password.
    * @return int|string The new user ID on success, or an error message string on failure.
    */
   public function registerUser(array $data)
   {
      // 1. Validate inputs
      if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
         return "Invalid email address.";
      }
      if (empty($data['password']) || strlen($data['password']) < 8) {
         return "Password must be at least 8 characters long.";
      }
      if (empty($data['firstname']) || empty($data['lastname'])) {
         return "First and last name are required.";
      }

      // 2. Check if email already exists
      try {
         $stmt = $this->pdo->prepare("SELECT customer_id FROM customer WHERE customer_email = :email");
         $stmt->execute([':email' => $data['email']]);
         if ($stmt->fetch()) {
            return "An account with this email address already exists.";
         }
      } catch (PDOException $e) {
         error_log("Error checking for existing email: " . $e->getMessage());
         return "A database error occurred. Please try again later.";
      }

      // 3. Hash password
      $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
      if ($hashedPassword === false) {
         error_log("Password hashing failed.");
         return "A server error occurred during registration.";
      }

      // 4. Insert new user
      // IMPORTANT: Verify column names match your 'customer' table schema
      $sql = "INSERT INTO customer (customer_fname, customer_lname, customer_email, password, customer_phone, date_created, customer_status) 
              VALUES (:firstname, :lastname, :email, :password, :phone, NOW(), 'MEMBER')";

      try {
         $stmt = $this->pdo->prepare($sql);
         $stmt->execute([
            ':firstname' => $data['firstname'],
            ':lastname' => $data['lastname'],
            ':email' => $data['email'],
            ':password' => $hashedPassword,
            ':phone' => $data['phone'] ?? null // Handle optional phone
         ]);
         return (int) $this->pdo->lastInsertId();
      } catch (PDOException $e) {
         error_log("Error inserting new user: " . $e->getMessage());
         return "A database error occurred while creating your account.";
      }
   }

   function update_phone_number($phone_no, $phone_id)
   {

      $sql = " UPDATE phonenumber SET `PhoneNumber`= '$phone_no' WHERE `phone_id`=$phone_id";
      $pdo = $this->pdo;
      $stmt = $pdo->query($sql);
      if ($stmt) {
         return true;
      } else {
         return false;
      }
   }


   function make_phone_number_my_default($phone_id)
   {
      $pdo = $this->pdo;
      $sql = "UPDATE phonenumber SET default_= '0' WHERE CustomerID =  " . $this->user_id;
      echo $sql;
      $stmt = $pdo->query($sql);
      if ($stmt) {
         $sql = "UPDATE phonenumber SET default_= '1' WHERE `phone_id`=$phone_id";
         $stmt = $pdo->query($sql);
         if ($stmt) {
            return true;
         } else {
            return false;
         }
      } else {
         return false;
      }
   }

   function get_phone_number()
   {
      $pdo = $this->pdo;
      $sql = "select * from phonenumber where CustomerID =  " . $this->user_id;
      $stmt = $pdo->query($sql);
      $phoneNumbers = [];
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
         $phoneNumbers[] = $row;
      }
      return $phoneNumbers;
   }
   function add_phone_number($phone_numner)
   {
      $pdo = $this->pdo;
      $sql = "INSERT INTO `phonenumber` (`phone_id`, `CustomerID`, `PhoneNumber`, `default_`) VALUES (NULL, '" . $this->user_id . "', '$phone_numner', '0')";
      $stmt = $pdo->query($sql);
      if ($stmt) {
         return true;
      } else {
         return false;
      }

   }

   function get_all_phone_number($id)
   {
      $pdo = $this->pdo;
      $sql = "select * from phonenumber where CustomerID =  " . $id;
      $stmt = $pdo->query($sql);
      return $stmt;
   }
   function delete_phone_number($id)
   {

      $pdo = $this->pdo;
      $sql = "DELETE FROM `phonenumber` WHERE `phone_id` = $id";
      $stmt = $pdo->query($sql);

      if ($stmt) {
         return true;
      } else {
         return false;
      }

   }

   function count_phone_number()
   {
      $pdo = $this->pdo;
      $sql = "select count(*) as c from phonenumber where CustomerID =  " . $this->user_id;
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch();
      if ($row['c'] <= 1)
         return false;
      else
         return true;
   }
   public function updateUserVendorStatus($mysqli, $userId, $newStatus)
   {
      $sql = "UPDATE customer SET vendor_status = ? WHERE customer_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("si", $newStatus, $userId);
      return $stmt->execute();
   }
   public function getVendorStatus($mysqli, $userId)
   {
      // Corrected to select 'vendor_status' as the method name implies
      $sql = "SELECT vendor_status FROM `customer` WHERE `customer_id` = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      return $row['vendor_status'] ?? null; // Return null if not set
   }

   /**
    * Fetches user details by user ID using the class's PDO instance.
    *
    * @param int $userId The ID of the user.
    * @return array|false User details as an associative array, or false if not found or on error.
    */
   public function getUserById($userId)
   {
      // Corrected SQL: removed duplicate 'user_role' and added actual role columns 'is_admin', 'super_admin'
      $sql = "SELECT customer_id, 
                     customer_email, 
                     customer_fname AS first_name, 
                     customer_lname AS last_name, 
                     customer_address1, 
                     customer_address2 FROM customer WHERE customer_id = :user_id LIMIT 1";
      try {
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmt->execute();
         return $stmt->fetch(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
         error_log("Error in User::getUserById: " . $e->getMessage());
         return false;
      }
   }

   public function isAdmin($mysqli, $userId)
   {
      $sql = "SELECT COUNT(*) AS adminCount FROM admins WHERE user_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      return $row['adminCount'] > 0;
   }

   public function addAdmin($mysqli, $userId)
   {
      $sql = "INSERT INTO admins (user_id) VALUES (?)";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $userId);
      return $stmt->execute();
   }

   public function isSuperAdmin($mysqli, $userId)
   {
      $sql = "SELECT super_admin FROM customer WHERE customer_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      return isset($row['super_admin']) && $row['super_admin'];
   }

   public function setSuperAdminStatus($mysqli, $userId, $isSuperAdmin)
   {
      $sql = "UPDATE customer SET super_admin = ? WHERE customer_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("ii", $isSuperAdmin, $userId);
      return $stmt->execute();
   }

   public function getSuperAdminsCount($mysqli)
   {
      $sql = "SELECT COUNT(*) AS superAdminCount FROM customer WHERE super_admin = 1";
      $result = $mysqli->query($sql);
      $row = $result->fetch_assoc();
      return $row['superAdminCount'];
   }

   public function getCustomerById($mysqli, $customerId)
   {
      $sql = "SELECT * FROM customer  WHERE customer_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      return $result->fetch_assoc();
   }

   public function getCustomerByUsernameOrEmail($mysqli, $username, $email)
   {
      $sql = "SELECT * FROM customer WHERE username = ? OR customer_email = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("ss", $username, $email);
      $stmt->execute();
      $result = $stmt->get_result();
      return $result->fetch_assoc();
   }
   public function setAdminStatus($mysqli, $userId, $isAdmin)
   {
      $mysqli->begin_transaction(); //Start Transaction

      try {
         if ($isAdmin) {
            // Add user to admins table
            $sql = "INSERT IGNORE INTO admins (user_id) VALUES (?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Update a column in the customers table
            $sqlCustomers = "UPDATE customer SET is_admin = 1 WHERE customer_id = ?";
            $stmtCustomers = $mysqli->prepare($sqlCustomers);
            $stmtCustomers->bind_param("i", $userId);
            $stmtCustomers->execute();
         } else {
            // Remove user from admins table
            $sql = "DELETE FROM admins WHERE user_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            // Update a column in the customers table
            $sqlCustomers = "UPDATE customer SET is_admin = 0 WHERE customer_id = ?";
            $stmtCustomers = $mysqli->prepare($sqlCustomers);
            $stmtCustomers->bind_param("i", $userId);
            $stmtCustomers->execute();
         }
         $mysqli->commit(); //Commit transaction
         return true; // Return true if successful
      } catch (Exception $e) {
         $mysqli->rollback(); //Rollback transaction in case of failure
         throw new Exception($e->getMessage()); // Throw the exception to handle in calling function
         return false; // Return false if there is an error.
      }
   }

   public function removeAdmin($mysqli, $customerId)
   {
      $mysqli->begin_transaction(); // Start transaction for atomicity
      try {
         // Remove from admins table
         $sqlAdmins = "DELETE FROM admins WHERE user_id = ?";
         $stmtAdmins = $mysqli->prepare($sqlAdmins);
         $stmtAdmins->bind_param("i", $customerId);
         $stmtAdmins->execute();


         $sqlCustomers = "UPDATE customer SET is_admin = 0 WHERE customer_id = ?";
         $stmtCustomers = $mysqli->prepare($sqlCustomers);
         $stmtCustomers->bind_param("i", $customerId);
         $stmtCustomers->execute();

         $mysqli->commit(); // Commit transaction if all updates were successful
         return true;
      } catch (Exception $e) {
         $mysqli->rollback(); // Rollback transaction if any error occurred
         throw new Exception("Error removing admin: " . $e->getMessage()); // Re-throw the exception for handling in calling function
         return false;
      }
   }

   public function searchAdmins($mysqli, $searchTerm)
   {
      $sql = "SELECT * FROM customer WHERE is_admin = 1 AND (username LIKE ? OR customer_email LIKE ?)";
      $stmt = $mysqli->prepare($sql);
      $searchTerm = "%{$searchTerm}%";
      $stmt->bind_param("ss", $searchTerm, $searchTerm);
      $stmt->execute();
      $result = $stmt->get_result();
      $admins = [];
      while ($row = $result->fetch_assoc()) {
         $admins[] = $row;
      }
      return $admins;
   }

   public function getUserDetailsById($mysqli, $customerId)
   {
      $sql = "SELECT * FROM customer WHERE customer_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $customerId); // 'i' indicates an integer parameter
      $stmt->execute();
      $result = $stmt->get_result();
      return $result->fetch_assoc();
   }

   public function updateUser($mysqli, $userId, $username, $email, $phone, $address1, $address2)
   {
      $sql = "UPDATE customer SET username = ?, customer_email = ?, customer_phone = ?, customer_address1 = ? , customer_address2 = ? WHERE customer_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("sssssi", $username, $email, $phone, $address1, $address2, $userId);
      return $stmt->execute();
   }
   public function updateSeller($mysqli, $userId, $businessName, $description)
   {
      $sql = "UPDATE seller SET seller_business_name = ?, seller_description = ? WHERE customer_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("ssi", $businessName, $description, $userId);
      return $stmt->execute();
   }

   public function updatePhoneNumber($mysqli, $phoneId, $phoneNumber, $isDefault)
   {
      $sql = "UPDATE phonenumber SET PhoneNumber = ?, default_ = ? WHERE phone_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("sii", $phoneNumber, $isDefault, $phoneId);
      $stmt->execute();
      return $stmt->affected_rows > 0;
   }

   public function deletePhoneNumber($mysqli, $phoneId)
   {
      $sql = "DELETE FROM phonenumber WHERE phone_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $phoneId);
      $stmt->execute();
      return $stmt->affected_rows > 0;
   }
   public function getPrimaryPhoneNumber($userId)
   {
      $pdo = $this->pdo;
      // *** ADJUST table and column names if necessary ***
      $sql = "SELECT PhoneNumber
               FROM {$this->phone_number_table}
               WHERE CustomerID = :user_id
                 AND default_ = 1
                 AND is_active = 1
               LIMIT 1";

      try {
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmt->execute();

         // FetchColumn returns the value of the first column or false if no row
         $phoneNumber = $stmt->fetchColumn();

         return ($phoneNumber !== false) ? (string) $phoneNumber : null;

      } catch (PDOException $e) {
         // Log the error is recommended
         error_log("Database error fetching primary phone number for user $userId: " . $e->getMessage());
         return null; // Return null on error
      }
   }
   public function getAllPhoneNumbers($mysqli, $customerId)
   {
      $sql = "SELECT * FROM phonenumber WHERE CustomerID = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $phoneNumbers = [];
      while ($row = $result->fetch_assoc()) {
         $phoneNumbers[] = $row;
      }
      return $phoneNumbers;
   }


   public function resetDefaultPhoneNumber($mysqli, $customerId)
   {
      $sql = "UPDATE phonenumber SET default_ = 0 WHERE CustomerID = ?";
      $stmt = $mysqli->prepare($sql);
      if ($stmt) {
         $stmt->bind_param("i", $customerId);
         $stmt->execute();
         return $stmt->affected_rows > 0; // Return true if rows were updated
      } else {
         // Handle the error appropriately, perhaps log it or throw an exception.
         return false; // Indicate failure
      }
   }


   public function wasDefaultPhoneNumber($mysqli, $phoneId)
   {
      $sql = "SELECT default_ FROM phonenumber WHERE phone_id = ?";
      $stmt = $mysqli->prepare($sql);
      if (!$stmt)
         return false;
      $stmt->bind_param("i", $phoneId);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      return $row['default_'] == 1;
   }

   public function setNextPhoneNumberAsDefault($mysqli, $customerId)
   {
      // Get all phone numbers for the customer, ordered by some criteria (e.g., phone_id)
      $sql = "SELECT phone_id FROM phonenumber WHERE CustomerID = ? ORDER BY phone_id";
      $stmt = $mysqli->prepare($sql);
      if (!$stmt) {
         return false;
      }
      $stmt->bind_param("i", $customerId);
      $stmt->execute();
      $result = $stmt->get_result();
      $phoneIds = [];
      while ($row = $result->fetch_assoc()) {
         $phoneIds[] = $row['phone_id'];
      }

      // Find the next phone ID
      $phoneIds = array_values(array_filter($phoneIds, fn($id) => $id != $_POST['phone_id']));
      if (empty($phoneIds))
         return true;
      $nextPhoneId = $phoneIds[0];

      // Update the database to set the next phone number as default.
      $sql = "UPDATE phonenumber SET default_ = 1 WHERE phone_id = ?";
      $stmt = $mysqli->prepare($sql);
      if (!$stmt) {
         return false;
      }
      $stmt->bind_param("i", $nextPhoneId);
      $stmt->execute();
      return $stmt->affected_rows > 0;
   }


   public function getOrders($mysqli, $statusFilter = null, $searchQuery = null)
   {
      $sql = "SELECT o.*, c.customer_fname, c.customer_lname, c.customer_id as CustomerID
           FROM `order` o
           JOIN customer c ON o.customer_id = c.customer_id";

      $whereClauses = [];
      $params = [];
      $types = "";

      if ($statusFilter) {
         $whereClauses[] = "o.order_status = ?";
         $params[] = $statusFilter;
         $types .= "s";
      }

      if ($searchQuery) {
         $whereClauses[] = "(o.order_id LIKE ? OR c.customer_fname LIKE ? OR c.customer_lname LIKE ?)";
         $searchParam = "%" . $searchQuery . "%";
         $params[] = $searchParam;
         $params[] = $searchParam;
         $params[] = $searchParam;
         $types .= "sss";
      }

      if (!empty($whereClauses)) {
         $sql .= " WHERE " . implode(" AND ", $whereClauses);
      }

      $stmt = $mysqli->prepare($sql);

      if (!$stmt) {
         // Handle prepare error
         error_log("Error preparing statement: " . $mysqli->error);
         return [];
      }

      if (!empty($params)) {
         $stmt->bind_param($types, ...$params);
      }

      $stmt->execute();
      $result = $stmt->get_result();
      $orders = [];
      while ($row = $result->fetch_assoc()) {
         $orders[] = $row;
      }
      return $orders;
   }

   public function updateOrderStatus($mysqli, $orderId, $newStatus)
   {
      $sql = "UPDATE `order` SET order_status = ? WHERE order_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("si", $newStatus, $orderId);
      return $stmt->execute();
   }

   public function getOrderDetails($mysqli, $orderId)
   {
      $sql = "SELECT o.*, c.customer_fname, c.customer_lname 
              FROM `order` o
              JOIN customer c ON o.customer_id = c.customer_id
              WHERE o.order_id = ?";
      $stmt = $mysqli->prepare($sql);
      if (!$stmt) {
         error_log("Error preparing statement: " . $mysqli->error);
         return null; // Or throw an exception
      }

      $stmt->bind_param("i", $orderId);
      if (!$stmt->execute()) {
         error_log("Error executing statement: " . $stmt->error);
         return null; // Or throw an exception
      }

      $result = $stmt->get_result();
      return $result->fetch_assoc();
   }



   public function getOrdersFromLmOrders($mysqli, $statusFilter = null, $searchQuery = null)
   {
      $sql = "SELECT * FROM `lm_orders`";

      $whereClauses = [];
      $params = [];
      $types = "";

      if ($statusFilter) {
         $whereClauses[] = "order_status = ?";
         $params[] = $statusFilter;
         $types .= "s";
      }

      if ($searchQuery) {
         $whereClauses[] = "(order_id LIKE ? OR customer_id LIKE ? )";
         $searchParam = "%" . $searchQuery . "%";
         $params[] = $searchParam;
         $params[] = $searchParam;
         $types .= "ss";
      }

      if (!empty($whereClauses)) {
         $sql .= " WHERE " . implode(" AND ", $whereClauses);
      }

      $stmt = $mysqli->prepare($sql);

      if (!$stmt) {
         // Handle prepare error
         error_log("Error preparing statement: " . $mysqli->error);
         return [];
      }

      if (!empty($params)) {
         $stmt->bind_param($types, ...$params);
      }

      $stmt->execute();
      $result = $stmt->get_result();
      $orders = [];
      while ($row = $result->fetch_assoc()) {
         $orders[] = $row;
      }
      return $orders;
   }


   public function updateOrderStatusLmOrders($mysqli, $orderId, $newStatus)
   {
      $sql = "UPDATE `lm_orders` SET order_status = ? WHERE order_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("si", $newStatus, $orderId);
      return $stmt->execute();
   }
   public function getOrderDetailsFromLmOrders($mysqli, $orderId)
   {
      $sql = "SELECT * FROM lm_orders WHERE order_id = ?";
      $stmt = $mysqli->prepare($sql);
      if (!$stmt) {
         error_log("Error preparing statement: " . $mysqli->error);
         return null; // Or throw an exception
      }

      $stmt->bind_param("i", $orderId);
      if (!$stmt->execute()) {
         error_log("Error executing statement: " . $stmt->error);
         return null; // Or throw an exception
      }

      $result = $stmt->get_result();
      return $result->fetch_assoc();
   }

   public function updateOrderLmOrders($mysqli, $orderId, $orderStatus, $orderDueDate)
   {
      $sql = "UPDATE `lm_orders` SET order_status = ?, order_due_date = ? WHERE order_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("ssi", $orderStatus, $orderDueDate, $orderId);
      return $stmt->execute();
   }

   public function isAdmin_($mysqli, $userId)
   {
      $sql = "SELECT user_role FROM users WHERE user_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      return $row && $row['user_role'] === "admin";
   }

   public function getOrderItemsFromLmOrders($mysqli, $orderId)
   {
      $sql = "SELECT ol.*, ii.*, pi.* FROM lm_order_line ol LEFT JOIN inventoryitem ii ON ol.InventoryItemID = ii.InventoryItemID LEFT JOIN productitem pi ON ii.`productItemID` = pi.`productID` WHERE ol.orderID =  ?";

      $stmt = $mysqli->prepare($sql);
      if (!$stmt) {
         error_log("Error preparing statement: " . $mysqli->error);
         return [];
      }

      $stmt->bind_param("i", $orderId);
      $stmt->execute();
      $result = $stmt->get_result();
      $orderItems = [];
      while ($row = $result->fetch_assoc()) {
         $orderItems[] = $row;
      }
      return $orderItems;
   }


   public function addOrderItem($mysqli, $orderId, $productId, $itemName, $quantity, $price)
   {
      $sql = "INSERT INTO lm_order_items (order_id, product_id, item_name, quantity, price) VALUES (?, ?, ?, ?, ?)";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("iisdd", $orderId, $productId, $itemName, $quantity, $price);
      return $stmt->execute();
   }

   public function updateOrderItemQuantity($mysqli, $orderItemId, $newQuantity)
   {
      $sql = "UPDATE lm_order_items SET quantity = ? WHERE order_item_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("ii", $newQuantity, $orderItemId);
      return $stmt->execute();
   }

   public function deleteOrderItem($mysqli, $orderItemId)
   {
      $sql = "DELETE FROM lm_order_items WHERE order_item_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $orderItemId);
      return $stmt->execute();
   }

   public function getOrderItems($mysqli, $orderId)
   {
      $sql = "SELECT oi.*, pi.* FROM lm_order_items oi JOIN product_item pi ON oi.product_id = pi.productID WHERE oi.order_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $orderId);
      $stmt->execute();
      $result = $stmt->get_result();
      return $result->fetch_all(MYSQLI_ASSOC);
   }

   public function getShippingAddressFromTable($mysqli, $ship_address)
   {
      $stmt = $mysqli->prepare("SELECT * FROM shipping_address WHERE shipping_address_no = ?");
      $stmt->bind_param("i", $ship_address);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();

      if ($row) {
         // Construct the full address from the table columns
         $address = "";
         if (!empty($row['address1'])) {
            $address .= $row['address1'];
         }
         if (!empty($row['address2'])) {
            $address .= (!empty($address) ? ", " : "") . $row['address2'];
         }
         if (!empty($row['city'])) {
            $address .= (!empty($address) ? ", " : "") . $row['city'];
         }
         if (!empty($row['state'])) {
            //get the state name
            $state_name = $this->get_state_name($mysqli, $row['state']);
            $address .= (!empty($address) ? ", " : "") . $state_name;
         }
         if (!empty($row['zip'])) {
            $address .= (!empty($address) ? ", " : "") . $row['zip'];
         }
         if (!empty($row['country'])) {
            $address .= (!empty($address) ? ", " : "") . $row['country'];
         }
         return $address;
      } else {
         return null;
      }
   }
   private function get_state_name($mysqli, $state_id)
   {
      $stmt = $mysqli->prepare("SELECT state_name FROM shipping_state WHERE state_id = ?");
      $stmt->bind_param("i", $state_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      if ($row) {
         return $row['state_name'];
      } else {
         return null;
      }
   }

   public function getShippingAddressesByCustomerId($customerId)
   {
      try {
         $sql = "SELECT * FROM {$this->shipping_address_table} WHERE customer_id = :customer_id ORDER BY shipping_address_no DESC";
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
         $stmt->execute();
         return $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
         error_log("Error fetching shipping addresses for customer ID {$customerId}: " . $e->getMessage());
         return [];
      }
   }

   /**
    * Fetches a single shipping address by its ID, ensuring it belongs to the user.
    * @param int $shippingAddressNo
    * @param int $customerId
    * @return array|false
    */
   public function getShippingAddressById($shippingAddressNo, $customerId)
   {
      try {
         $sql = "SELECT * FROM {$this->shipping_address_table} WHERE shipping_address_no = :shipping_address_no AND customer_id = :customer_id";
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':shipping_address_no', $shippingAddressNo, PDO::PARAM_INT);
         $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
         $stmt->execute();
         return $stmt->fetch(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
         error_log("Error fetching shipping address ID {$shippingAddressNo} for customer ID {$customerId}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Adds a new shipping address for a user.
    * @param int $customerId
    * @param array $data Associative array of address data
    * @return int|false The new address ID (shipping_address_no) or false on failure
    */
   public function addShippingAddress($customerId, array $data)
   {
      $sql = "INSERT INTO {$this->shipping_address_table} 
                    (customer_id, address1, address2, zip, shipping_area_id, city, country, ship_cost, state) 
                VALUES 
                    (:customer_id, :address1, :address2, :zip, :shipping_area_id, :city, :country, :ship_cost, :state)";
      try {
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
         $stmt->bindParam(':address1', $data['address1']);
         $stmt->bindParam(':address2', $data['address2']);
         $stmt->bindParam(':zip', $data['zip']);
         $stmt->bindParam(':shipping_area_id', $data['shipping_area_id']);
         $stmt->bindParam(':city', $data['city']);
         $stmt->bindParam(':country', $data['country']);
         $stmt->bindParam(':ship_cost', $data['ship_cost']); // Consider if ship_cost should be here or calculated elsewhere
         $stmt->bindParam(':state', $data['state']);

         if ($stmt->execute()) {
            return $this->pdo->lastInsertId();
         }
         return false;
      } catch (PDOException $e) {
         error_log("Error adding shipping address for customer ID {$customerId}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Updates an existing shipping address.
    * @param int $shippingAddressNo
    * @param int $customerId
    * @param array $data
    * @return bool
    */
   public function updateShippingAddress($shippingAddressNo, $customerId, array $data)
   {
      $sql = "UPDATE {$this->shipping_address_table} SET 
                    address1 = :address1, 
                    address2 = :address2, 
                    zip = :zip, 
                    shipping_area_id = :shipping_area_id, 
                    city = :city, 
                    country = :country, 
                    ship_cost = :ship_cost, 
                    state = :state
                WHERE shipping_address_no = :shipping_address_no AND customer_id = :customer_id";
      try {
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':address1', $data['address1']);
         $stmt->bindParam(':address2', $data['address2']);
         $stmt->bindParam(':zip', $data['zip']);
         $stmt->bindParam(':shipping_area_id', $data['shipping_area_id']);
         $stmt->bindParam(':city', $data['city']);
         $stmt->bindParam(':country', $data['country']);
         $stmt->bindParam(':ship_cost', $data['ship_cost']);
         $stmt->bindParam(':state', $data['state']);
         $stmt->bindParam(':shipping_address_no', $shippingAddressNo, PDO::PARAM_INT);
         $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
         return $stmt->execute();
      } catch (PDOException $e) {
         error_log("Error updating shipping address ID {$shippingAddressNo}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Deletes a shipping address, ensuring it belongs to the user.
    * @param int $shippingAddressNo
    * @param int $customerId
    * @return bool
    */
   public function deleteShippingAddress($shippingAddressNo, $customerId)
   {
      try {
         $sql = "DELETE FROM {$this->shipping_address_table} WHERE shipping_address_no = :shipping_address_no AND customer_id = :customer_id";
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':shipping_address_no', $shippingAddressNo, PDO::PARAM_INT);
         $stmt->bindParam(':customer_id', $customerId, PDO::PARAM_INT);
         return $stmt->execute();
      } catch (PDOException $e) {
         error_log("Error deleting shipping address ID {$shippingAddressNo}: " . $e->getMessage());
         return false;
      }
   }


   public function updateUserProfile($userId, array $data)
   {
      if (empty($userId) || !is_numeric($userId) || empty($data)) {
         return false;
      }

      // Ensure expected keys exist in $data to prevent errors if not all are passed
      $firstName = $data['firstname'] ?? null;
      $lastName = $data['lastname'] ?? null;
      $email = $data['email'] ?? null;

      $sql = "UPDATE customer SET 
                    `customer_fname` = :firstname, 
                    `customer_lname` = :lastname, 
                    `customer_email` = :email 
                WHERE `customer_id` = :user_id";
      try {
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':firstname', $firstName);
         $stmt->bindParam(':lastname', $lastName);
         $stmt->bindParam(':email', $email);
         $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
         return $stmt->execute();
      } catch (PDOException $e) {
         error_log("Error in updateUserProfile for user ID {$userId}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Fetches a single phone number by its ID, ensuring it belongs to the user.
    * @param int $phoneId
    * @param int $userId
    * @return array|false
    */
   public function getPhoneNumberById($phoneId, $userId)
   {
      try {
         $stmt = $this->pdo->prepare("SELECT * FROM {$this->phone_number_table} WHERE phone_id = :phone_id AND CustomerID = :user_id");
         $stmt->bindParam(':phone_id', $phoneId, PDO::PARAM_INT);
         $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmt->execute();
         return $stmt->fetch(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
         error_log("Error fetching phone number ID {$phoneId} for user ID {$userId}: " . $e->getMessage());
         return false;
      }
   }
   /**
    * Add phone number with validation
    * 
    * @param int $userId User ID
    * @param string $newPhoneNumber The phone number to add
    * @return mixed True on success, error message on validation failure, false on other failures
    */
   public function addPhoneNumber($userId, $newPhoneNumber)
   {
      // Rule: Prevent adding a new number if an unverified one exists.
      if ($this->hasUnverifiedPhoneNumber($userId)) {
         return "You must verify your pending phone number before adding a new one.";
      }

      if (empty($userId) || !is_numeric($userId) || empty($newPhoneNumber)) {
         return false;
      }

      // Validate phone number format
      $validationResult = $this->validatePhoneNumber($newPhoneNumber);
      if ($validationResult !== true) {
         // If validation returns a string (corrected number), use it
         if (is_string($validationResult) && strlen($validationResult) === 11) {
            $newPhoneNumber = $validationResult;
         } else {
            // If validation failed with an error message
            return $validationResult; // Return error message
         }
      }

      // Optional: Check if this exact phone number already exists for the user
      try {
         $sqlCheck = "SELECT COUNT(*) FROM `{$this->phone_number_table}` WHERE `CustomerID` = :user_id AND `PhoneNumber` = :phone_number";
         $stmtCheck = $this->pdo->prepare($sqlCheck);
         $stmtCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmtCheck->bindParam(':phone_number', $newPhoneNumber);
         $stmtCheck->execute();
         if ($stmtCheck->fetchColumn() > 0) {
            return false; // Phone number already exists for this user
         }
      } catch (PDOException $e) {
         error_log("Error checking existing phone number: " . $e->getMessage());
         return false; // Or handle as appropriate
      }

      // New numbers are inactive until verified. They are not made default upon adding.
      try {
         // Insert the new phone number
         $sqlInsert = "INSERT INTO `{$this->phone_number_table}` (`CustomerID`, `PhoneNumber`, `default_`, `is_active`) 
                          VALUES (:user_id, :phone_number, 0, 1)"; // default_ = 0, is_active = 0
         $stmtInsert = $this->pdo->prepare($sqlInsert);
         $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmtInsert->bindParam(':phone_number', $newPhoneNumber);
         $stmtInsert->execute();
         $phoneId = $this->pdo->lastInsertId();

         // Send verification code
         $code = $this->generatePhoneVerificationCode($phoneId);
         $sms = new Sms();
         $sms->send($newPhoneNumber, "Your GoodGuy verification code is: " . $code);

         return $phoneId; // Return the new phone ID to redirect to verification page

      } catch (PDOException $e) {
         if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
         }
         error_log("Error in addPhoneNumber for user ID {$userId}: " . $e->getMessage());
         return false;
      }
   }

   public function getPhoneNumbersByUserId($userId)
   {
      if (empty($userId) || !is_numeric($userId)) {
         return [];
      }

      try {
         $sql = "SELECT `phone_id`, `CustomerID`, `PhoneNumber`, `default_`, `is_active`, 
                IFNULL(`is_verified`, 0) as is_verified
                FROM `{$this->phone_number_table}` 
                WHERE `CustomerID` = :user_id ORDER BY `default_` DESC, `phone_id` ASC";
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmt->execute();
         return $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
         error_log("Error fetching phone numbers for user ID {$userId}: " . $e->getMessage());
         return [];
      }
   }


   /**
    * Deletes a phone number for a given user.
    * If the deleted phone number was the default, it attempts to set a new default
    * from other active phone numbers and updates the customer.phone_number field.
    *
    * @param int $phoneId The ID of the phone number to delete.
    * @param int $userId The ID of the user.
    * @return bool True on success, false on failure.
    */
   public function deleteUserPhoneNumber($phoneId, $userId)
   {
      if (empty($phoneId) || !is_numeric($phoneId) || empty($userId) || !is_numeric($userId)) {
         return false;
      }

      try {
         $this->pdo->beginTransaction();

         // Get details of the phone number being deleted, especially if it's default
         $sqlGetPhone = "SELECT `PhoneNumber`, `default_` FROM `{$this->phone_number_table}` WHERE `phone_id` = :phone_id AND `CustomerID` = :user_id";
         $stmtGetPhone = $this->pdo->prepare($sqlGetPhone);
         $stmtGetPhone->bindParam(':phone_id', $phoneId, PDO::PARAM_INT);
         $stmtGetPhone->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmtGetPhone->execute();
         $phoneToDelete = $stmtGetPhone->fetch(PDO::FETCH_ASSOC);

         if (!$phoneToDelete) {
            $this->pdo->rollBack();
            error_log("Attempt to delete non-existent or unauthorized phone ID {$phoneId} for user ID {$userId}");
            return false; // Phone number not found or doesn't belong to the user
         }

         // Delete the phone number
         $sqlDelete = "DELETE FROM `{$this->phone_number_table}` WHERE `phone_id` = :phone_id AND `CustomerID` = :user_id";
         $stmtDelete = $this->pdo->prepare($sqlDelete);
         $stmtDelete->bindParam(':phone_id', $phoneId, PDO::PARAM_INT);
         $stmtDelete->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmtDelete->execute();

         // If the deleted phone was the default, try to set a new default
         if ($phoneToDelete['default_'] == 1) {
            $sqlFindNextDefault = "SELECT `phone_id`, `PhoneNumber` FROM `{$this->phone_number_table}` 
                                       WHERE `CustomerID` = :user_id AND `is_active` = 1 
                                       ORDER BY `phone_id` ASC LIMIT 1"; // Pick the one with the smallest ID as new default
            $stmtFindNext = $this->pdo->prepare($sqlFindNextDefault);
            $stmtFindNext->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtFindNext->execute();
            $nextDefaultPhone = $stmtFindNext->fetch(PDO::FETCH_ASSOC);

            $newDefaultPhoneNumberForCustomerTable = null;

            if ($nextDefaultPhone) {
               $sqlSetNewDefault = "UPDATE `{$this->phone_number_table}` SET `default_` = 1 WHERE `phone_id` = :new_default_phone_id AND `CustomerID` = :user_id";
               $stmtSetNewDefault = $this->pdo->prepare($sqlSetNewDefault);
               $stmtSetNewDefault->bindParam(':new_default_phone_id', $nextDefaultPhone['phone_id'], PDO::PARAM_INT);
               $stmtSetNewDefault->bindParam(':user_id', $userId, PDO::PARAM_INT);
               $stmtSetNewDefault->execute();
               $newDefaultPhoneNumberForCustomerTable = $nextDefaultPhone['PhoneNumber'];
            }

            // Update the customer table's main phone_number field
            $sqlUpdateCustomer = "UPDATE `{$this->customer_table}` SET `phone_number` = :phone_number WHERE `customer_id` = :user_id";
            $stmtUpdateCustomer = $this->pdo->prepare($sqlUpdateCustomer);
            $stmtUpdateCustomer->bindParam(':phone_number', $newDefaultPhoneNumberForCustomerTable); // Binds NULL if $newDefaultPhoneNumberForCustomerTable is null
            $stmtUpdateCustomer->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtUpdateCustomer->execute();
         }

         $this->pdo->commit();
         return true;

      } catch (PDOException $e) {
         if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
         }
         error_log("Error in deleteUserPhoneNumber for phone ID {$phoneId}, user ID {$userId}: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Counts the number of active phone numbers for a given user.
    *
    * @param int $userId The ID of the user.
    * @return int The count of active phone numbers.
    */
   public function countUserPhoneNumbers($userId)
   {
      if (empty($userId) || !is_numeric($userId)) {
         return 0;
      }
      try {
         $sql = "SELECT COUNT(*) FROM `{$this->phone_number_table}` WHERE `CustomerID` = :user_id AND `is_active` = 1";
         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmt->execute();
         return (int) $stmt->fetchColumn();
      } catch (PDOException $e) {
         error_log("Error counting phone numbers for user ID {$userId}: " . $e->getMessage());
         return 0; // Return 0 on error, or handle as appropriate
      }
   }

   /**
    * Get all users who are not currently registered as vendors
    *
    * @return array List of users not registered as vendors
    */
   public function getUsersNotVendors(): array
   {
      try {
         // Query to get all users who don't have a vendor account
         $sql = "SELECT c.customer_id, c.username, c.customer_email, c.customer_fname, c.customer_lname FROM customer c LEFT JOIN vendors v ON c.customer_id = v.user_id WHERE v.vendor_id IS NULL ORDER BY c.username ASC";

         $stmt = $this->pdo->prepare($sql);
         $stmt->execute();

         return $stmt->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
         error_log("Error fetching non-vendor users: " . $e->getMessage());
         return [];
      }
   }

   /**
    * Validates a Nigerian phone number format
    * 
    * @param string $phoneNumber The phone number to validate
    * @return bool|string True if valid, error message if invalid
    */
   public function validatePhoneNumber($phoneNumber)
   {
      // Remove any non-digit characters
      $cleaned = preg_replace('/\D/', '', $phoneNumber);

      // Nigerian phone numbers validation logic
      if (strlen($cleaned) === 11 && substr($cleaned, 0, 1) === '0') {
         // Standard 11-digit format starting with 0
         return true;
      } else if (strlen($cleaned) === 13 && substr($cleaned, 0, 3) === '234') {
         // International format with country code
         return true;
      } else if (strlen($cleaned) === 10 && preg_match('/^[789]/', $cleaned)) {
         // 10 digits without leading zero (we'll add it)
         return '0' . $cleaned;
      }

      return "Please enter a valid Nigerian phone number (e.g., 08012345678)";
   }

   /**
    * Set a phone number as the default for a user.
    * This will unset any previously default phone number for that user.
    *
    * @param int $phone_id The ID of the phone number to set as default
    * @param int $user_id The user ID
    * @return bool True on success, false on failure
    */
   public function setDefaultUserPhoneNumber($phone_id, $user_id)
   {
      // Rule: Do not allow setting a number as default if it's not verified.
      try {
         $stmt = $this->pdo->prepare("SELECT is_active FROM phonenumber WHERE phone_id = :phone_id AND CustomerID = :user_id");
         $stmt->execute([':phone_id' => $phone_id, ':user_id' => $user_id]);
         if ($stmt->fetchColumn() != 1) {
            return false; // Not active, cannot be made default.
         }
      } catch (PDOException $e) {
         error_log("Error checking phone active status in setDefault: " . $e->getMessage());
         return false;
      }

      try {
         // Start a transaction
         $this->pdo->beginTransaction();

         // First verify the phone belongs to this user
         $checkSql = "SELECT COUNT(*) FROM {$this->phone_number_table} 
                     WHERE phone_id = :phone_id AND CustomerID = :user_id";
         $checkStmt = $this->pdo->prepare($checkSql);
         $checkStmt->execute([
            ':phone_id' => $phone_id,
            ':user_id' => $user_id
         ]);

         if ($checkStmt->fetchColumn() == 0) {
            // Phone doesn't belong to this user
            $this->pdo->rollBack();
            return false;
         }

         // 1. Unset any existing default phone number for this user
         $unsetSql = "UPDATE {$this->phone_number_table} 
                     SET default_ = 0 
                     WHERE CustomerID = :user_id AND default_ = 1";
         $unsetStmt = $this->pdo->prepare($unsetSql);
         $unsetStmt->execute([':user_id' => $user_id]);

         // 2. Set the new default phone number
         $setSql = "UPDATE {$this->phone_number_table} 
                   SET default_ = 1 
                   WHERE phone_id = :phone_id AND CustomerID = :user_id";
         $setStmt = $this->pdo->prepare($setSql);
         $setStmt->execute([
            ':phone_id' => $phone_id,
            ':user_id' => $user_id
         ]);

         // 3. Update the main phone number in the customer table
         $getPhoneSql = "SELECT PhoneNumber FROM {$this->phone_number_table} 
                        WHERE phone_id = :phone_id";
         $getPhoneStmt = $this->pdo->prepare($getPhoneSql);
         $getPhoneStmt->execute([':phone_id' => $phone_id]);
         $phoneNumber = $getPhoneStmt->fetchColumn();

         if ($phoneNumber) {
            $updateCustomerSql = "UPDATE {$this->customer_table} 
                                 SET customer_phone = :phone_number 
                                 WHERE customer_id = :user_id";
            $updateCustomerStmt = $this->pdo->prepare($updateCustomerSql);
            $updateCustomerStmt->execute([
               ':phone_number' => $phoneNumber,
               ':user_id' => $user_id
            ]);
         }

         // Commit the transaction
         $this->pdo->commit();
         return true;

      } catch (PDOException $e) {
         if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
         }
         error_log("Error setting default phone number: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Checks if a user has any phone numbers that are not yet active (verified).
    * @param int $userId
    * @return bool
    */
   public function hasUnverifiedPhoneNumber(int $userId): bool
   {
      try {
         $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$this->phone_number_table} WHERE CustomerID = :user_id AND is_verified = 0");
         $stmt->execute([':user_id' => $userId]);
         return (int) $stmt->fetchColumn() > 0;
      } catch (PDOException $e) {
         error_log("Error checking for unverified phone numbers: " . $e->getMessage());
         return false;
      }
   }
   /**
    * Formats a phone number for display
    * 
    * @param string $phoneNumber The phone number to format
    * @return string Formatted phone number
    */
   public function formatPhoneNumber($phoneNumber)
   {
      // Remove any non-digit characters
      $cleaned = preg_replace('/\D/', '', $phoneNumber);

      // Format based on length
      if (strlen($cleaned) === 11) {
         return substr($cleaned, 0, 4) . ' ' . substr($cleaned, 4, 3) . ' ' . substr($cleaned, 7);
      } else if (strlen($cleaned) === 13 && substr($cleaned, 0, 3) === '234') {
         return '+' . substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6, 3) . ' ' . substr($cleaned, 9);
      }

      // Return original if can't format
      return $phoneNumber;
   }

   /**
    * Generates a verification code and stores it for email verification
    * 
    * @param string $email Email to verify
    * @return string|bool Verification code or false on failure
    */
   public function generateEmailVerificationCode($email)
   {
      //try {
      // Generate a 6-digit verification code
      $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

      // Store in database with expiration (1 hour)
      $sql = "INSERT INTO email_verification (email, verification_code, expiry_time) 
              VALUES (:email, :code, DATE_ADD(NOW(), INTERVAL 1 HOUR))
              ON DUPLICATE KEY UPDATE 
              verification_code = VALUES(verification_code), 
              expiry_time = DATE_ADD(NOW(), INTERVAL 1 HOUR),
              attempts = 0";

      $stmt = $this->pdo->prepare($sql);
      $stmt->execute([
         ':email' => $email,
         ':code' => $verificationCode
      ]);

      return $verificationCode;
      // } catch (PDOException $e) {
      //    error_log("Error generating email verification code: " . $e->getMessage());
      //    return false;
      // }
   }

   /**
    * Sends verification email to customer using PHPMailer
    * 
    * @param string $email Email address
    * @param string $code Verification code
    * @param string $firstName Optional first name for personalization
    * @return bool Success status
    */
   public function sendVerificationEmail($email, $code, $firstName = '')
   {


      $mail = new PHPMailer(true);

      try {
         // Server settings
         // $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output for testing
         $mail->isSMTP();
         $mail->Host = 'smtp.hostinger.com';       // Your SMTP host
         $mail->SMTPAuth = true;
         $mail->Username = 'care@goodguyng.com';   // Your SMTP username
         $mail->Password = 'Password1@';           // Your SMTP password
         $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
         $mail->Port = 465;

         // Recipients
         $mail->setFrom('care@goodguyng.com', 'GoodGuy');
         $mail->addAddress($email);
         $mail->addReplyTo('care@goodguyng.com', 'GoodGuy Support');

         // Content
         $mail->isHTML(true);
         $mail->Subject = "Verify Your Email Address - GoodGuy";

         $greeting = empty($firstName) ? 'Hello,' : "Hello $firstName,";

         $message = "
         <html>
         <head>
             <style>
                 body { font-family: Arial, sans-serif; line-height: 1.6; }
                 .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                 .header { background: #0088cc; color: white; padding: 20px; text-align: center; }
                 .content { padding: 20px; background: #f8f9fa; }
                 .verification-code { font-size: 24px; font-weight: bold; text-align: center; 
                                    padding: 10px; background: #e9ecef; margin: 20px 0; }
                 .footer { text-align: center; font-size: 12px; color: #6c757d; margin-top: 20px; }
             </style>
         </head>
         <body>
             <div class='container'>
                 <div class='header'>
                     <h2>Email Verification</h2>
                 </div>
                 <div class='content'>
                     <p>{$greeting}</p>
                     <p>Thank you for using GoodGuy. To verify your email address, please use the verification code below:</p>
                     <div class='verification-code'>{$code}</div>
                     <p>This code will expire in 1 hour.</p>
                     <p>If you didn't request this verification, please ignore this email.</p>
                 </div>
                 <div class='footer'>
                     <p>&copy; " . date('Y') . " GoodGuy. All rights reserved.</p>
                 </div>
             </div>
         </body>
         </html>
         ";

         $mail->Body = $message;
         $mail->AltBody = "Verification Code: {$code}. This code will expire in 1 hour.";

         return $mail->send();

      } catch (Exception $e) {
         error_log("Error sending email verification: " . $e->getMessage());
         return false;
      } catch (\Exception $e) {
         error_log("General error in email verification: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Verifies email with provided code
    * 
    * @param string $email Email address
    * @param string $code Verification code
    * @return bool|string True if verified, error message on failure
    */
   public function verifyEmail($email, $code)
   {
      try {
         // Check if verification code exists and is valid
         $sql = "SELECT verification_code, attempts 
                  FROM email_verification 
                  WHERE email = :email AND expiry_time > NOW()";

         $stmt = $this->pdo->prepare($sql);
         $stmt->execute([':email' => $email]);
         $verification = $stmt->fetch(PDO::FETCH_ASSOC);

         if (!$verification) {
            return "Verification code expired or not found";
         }

         // Check if max attempts exceeded (5)
         if ($verification['attempts'] >= 5) {
            return "Too many failed attempts. Request a new code";
         }

         // Increment attempts
         $updateAttempts = $this->pdo->prepare("
              UPDATE email_verification SET attempts = attempts + 1
              WHERE email = :email
          ");
         $updateAttempts->execute([':email' => $email]);

         // Check if code matches
         if ($verification['verification_code'] !== $code) {
            return "Invalid verification code";
         }

         // Mark email as verified in customer table
         $markVerified = $this->pdo->prepare("
              UPDATE customer SET email_verified = 1 
              WHERE customer_email = :email
          ");
         $markVerified->execute([':email' => $email]);

         // Remove verification entry
         $removeVerification = $this->pdo->prepare("
              DELETE FROM email_verification WHERE email = :email
          ");
         $removeVerification->execute([':email' => $email]);

         return true;
      } catch (PDOException $e) {
         error_log("Error verifying email: " . $e->getMessage());
         return "System error occurred during verification";
      }
   }

   // /**
   //  * Generates a verification code for a phone number.
   //  * @param int $phoneId
   //  * @return string|bool
   //  */
   // public function generatePhoneVerificationCode($phoneId)
   // {
   //    try {
   //       $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

   //       $sql = "INSERT INTO phone_verification (phone_id, verification_code, expiry_time) 
   //               VALUES (:phone_id, :code, DATE_ADD(NOW(), INTERVAL 15 MINUTE))
   //               ON DUPLICATE KEY UPDATE 
   //               verification_code = VALUES(verification_code), 
   //               expiry_time = VALUES(expiry_time),
   //               attempts = 0";

   //       $stmt = $this->pdo->prepare($sql);
   //       $stmt->execute([
   //          ':phone_id' => $phoneId,
   //          ':code' => $verificationCode
   //       ]);

   //       return $verificationCode;
   //    } catch (PDOException $e) {
   //       error_log("Error generating phone verification code: " . $e->getMessage());
   //       return false;
   //    }
   // }
   /**
    * Generates a verification code for a phone number.
    * @param int $phoneId
    * @return string|bool
    */
   public function generatePhoneVerificationCode($phoneId)
   {
      try {
         // First check if the phone ID exists
         $checkStmt = $this->pdo->prepare("SELECT phone_id FROM {$this->phone_number_table} WHERE phone_id = :phone_id");
         $checkStmt->execute([':phone_id' => $phoneId]);
         if (!$checkStmt->fetchColumn()) {
            error_log("Attempted to generate verification code for non-existent phone ID: $phoneId");
            return false;
         }

         // Generate a 6-digit verification code
         $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

         // Use simpler SQL syntax to avoid potential database compatibility issues
         $sql = "INSERT INTO phone_verification 
                (phone_id, verification_code, expiry_time, attempts) 
                VALUES 
                (:phone_id, :code, NOW() + INTERVAL 15 MINUTE, 0)
                ON DUPLICATE KEY UPDATE 
                verification_code =  VALUES(verification_code), 
                expiry_time = NOW() + INTERVAL 15 MINUTE,
                attempts = 0";

         $stmt = $this->pdo->prepare($sql);
         $stmt->bindParam(':phone_id', $phoneId, PDO::PARAM_INT);
         $stmt->bindParam(':code', $verificationCode);
         $success = $stmt->execute();

         if (!$success) {
            error_log("Failed to insert phone verification code. Error: " . print_r($stmt->errorInfo(), true));
            return false;
         }

         // Log successful generation for debugging
         error_log("Generated verification code for phone ID $phoneId: $verificationCode");

         return $verificationCode;
      } catch (PDOException $e) {
         error_log("Error generating phone verification code: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Verifies a phone number with a given code.
    * @param int $phoneId
    * @param string $code
    * @return bool|string
    */
   public function verifyPhoneNumber($phoneId, $code)
   {
      try {
         // Check if verification code exists and is valid
         $sql = "SELECT v.verification_code, v.attempts, p.PhoneNumber
                FROM phone_verification v
                JOIN {$this->phone_number_table} p ON v.phone_id = p.phone_id
                WHERE v.phone_id = :phone_id AND v.expiry_time > NOW()";

         $stmt = $this->pdo->prepare($sql);
         $stmt->execute([':phone_id' => $phoneId]);
         $verification = $stmt->fetch(PDO::FETCH_ASSOC);

         if (!$verification) {
            return "Verification code expired or not found. Please request a new code.";
         }

         // Check if max attempts exceeded (5)
         if ($verification['attempts'] >= 5) {
            return "Too many failed attempts. Please request a new code.";
         }

         // Increment attempts
         $updateAttempts = $this->pdo->prepare("
            UPDATE phone_verification SET attempts = attempts + 1
            WHERE phone_id = :phone_id
         ");
         $updateAttempts->execute([':phone_id' => $phoneId]);

         // Check if code matches
         if ($verification['verification_code'] !== $code) {
            return "Invalid verification code. Please try again.";
         }

         // Mark phone as verified
         $markVerified = $this->pdo->prepare("
            UPDATE {$this->phone_number_table} SET is_verified = 1 WHERE phone_id = :phone_id
         ");
         $markVerified->execute([':phone_id' => $phoneId]);

         // Remove verification entry
         $removeVerification = $this->pdo->prepare("
            DELETE FROM phone_verification WHERE phone_id = :phone_id
         ");
         $removeVerification->execute([':phone_id' => $phoneId]);

         // Log the successful verification
         error_log("Phone number {$verification['PhoneNumber']} verified successfully for phone_id: $phoneId");

         return true;
      } catch (PDOException $e) {
         error_log("Error verifying phone number: " . $e->getMessage());
         return "A system error occurred during verification.";
      }
   }

   /**
    * Checks if a user's email is verified
    *
    * @param int $userId User ID
    * @return bool True if verified
    */
   public function isEmailVerified($userId)
   {
      try {
         $sql = "SELECT email_verified FROM customer WHERE customer_id = :user_id";
         $stmt = $this->pdo->prepare($sql);
         $stmt->execute([':user_id' => $userId]);
         return (bool) $stmt->fetchColumn();
      } catch (PDOException $e) {
         error_log("Error checking email verification status: " . $e->getMessage());
         return false;
      }
   }

   private function getUserIdByPhoneId($phoneId)
   {
      $stmt = $this->pdo->prepare("SELECT CustomerID FROM {$this->phone_number_table} WHERE phone_id = :phone_id");

      $stmt->execute([':phone_id' => $phoneId]);
      return $stmt->fetchColumn();
   }

   /**
    * Send phone verification email using PHPMailer
    * 
    * @param string $email User's email
    * @param string $phone Phone number being verified
    * @param string $code Verification code
    * @return bool Success status
    */
   public function sendPhoneVerificationEmail($email, $phone, $code)
   {
      try {
         // Format phone for display (add spaces for better readability)
         $formattedPhone = $this->formatPhoneNumber($phone);

         // Use PHPMailer
         require_once __DIR__ . '/../vendor/autoload.php';

         $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

         // Server settings
         $mail->isSMTP();
         $mail->Host = 'smtp.hostinger.com';
         $mail->SMTPAuth = true;
         $mail->Username = 'care@goodguyng.com';
         $mail->Password = 'Password1@';
         $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
         $mail->Port = 465;

         // Recipients
         $mail->setFrom('care@goodguyng.com', 'GoodGuy');
         $mail->addAddress($email);
         $mail->addReplyTo('care@goodguyng.com', 'GoodGuy Support');

         // Content
         $mail->isHTML(true);
         $mail->Subject = "Verify Your Phone Number - GoodGuy";

         $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0088cc; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .verification-code { font-size: 24px; font-weight: bold; text-align: center; 
                                   padding: 10px; background: #e9ecef; margin: 20px 0; }
                .footer { text-align: center; font-size: 12px; color: #6c757d; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Verify Your Phone Number</h2>
                </div>
                <div class='content'>
                    <p>Hello,</p>
                    <p>We need to verify your phone number: <strong>{$formattedPhone}</strong></p>
                    <p>Please use the verification code below to verify this phone number:</p>
                    <div class='verification-code'>{$code}</div>
                    <p>This code will expire in 1 hour.</p>
                    <p>If you didn't request to add this phone number to your account, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " GoodGuy. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

         $mail->Body = $message;
         $mail->AltBody = "Verification Code: {$code} for phone number {$formattedPhone}. This code will expire in 1 hour.";

         $result = $mail->send();

         // Log successful email sending
         if ($result) {
            error_log("Phone verification email sent successfully to {$email} for phone {$phone}");
         }

         return $result;

      } catch (\PHPMailer\PHPMailer\Exception $e) {
         error_log("Error sending phone verification email: " . $e->getMessage());
         return false;
      } catch (\Exception $e) {
         error_log("General error in phone verification email: " . $e->getMessage());
         return false;
      }
   }

   /**
    * Format phone number for display
    * 
    * @param string $phoneNumber The phone number to format
    * @return string Formatted phone number
    */
   // public function formatPhoneNumber($phoneNumber)
   // {
   //    // Remove any non-digit characters
   //    $cleaned = preg_replace('/\D/', '', $phoneNumber);

   //    // Format based on length
   //    if (strlen($cleaned) === 11) {
   //       return substr($cleaned, 0, 4) . ' ' . substr($cleaned, 4, 3) . ' ' . substr($cleaned, 7);
   //    } else if (strlen($cleaned) === 13 && substr($cleaned, 0, 3) === '234') {
   //       return '+' . substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6, 3) . ' ' . substr($cleaned, 9);
   //    }

   //    // Return original if can't format
   //    return $phoneNumber;
   // }

   /**
    * Gets the primary active phone number for a customer
    * 
    * @param int $customerId The ID of the customer
    * @return string|null Returns the phone number or null if not found
    */
   public function getPrimaryActivePhoneNumber(int $customerId): ?string
   {
      try {
         $sql = "SELECT customer_phone 
                FROM customer 
                WHERE customer_id = ? 
                AND customer_phone IS NOT NULL 
                AND customer_phone != ''
                LIMIT 1";

         $stmt = $this->pdo->prepare($sql);
         $stmt->execute([$customerId]);

         $result = $stmt->fetch(PDO::FETCH_COLUMN);
         return $result ?: null;
      } catch (PDOException $e) {
         error_log("Error getting primary phone number: " . $e->getMessage());
         return null;
      }
   }
}
