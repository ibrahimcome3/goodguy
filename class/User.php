<?php

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
         $pdo = $this->pdo;
         $sql = "SELECT * FROM `customer` WHERE `customer_id` =  " . $this->user_id;
         $stmt = $pdo->query($sql);
         $row = $stmt->fetch();
         $this->user_email = $row['customer_email'];
         $this->user_address = $row['customer_address1'] . " " . $row['customer_address2'];
         $this->user_role = $row['user_role'];
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
      $sql = "SELECT user_role FROM `customer` WHERE `customer_id` = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      return $row['user_role'];
   }

   /**
    * Fetches user details by user ID using the class's PDO instance.
    *
    * @param int $userId The ID of the user.
    * @return array|false User details as an associative array, or false if not found or on error.
    */
   public function getUserById($userId)
   {
      $sql = "SELECT customer_id, customer_email, customer_fname AS first_name, customer_lname AS last_name, customer_address1, customer_address2, user_role FROM customer WHERE customer_id = :user_id LIMIT 1";
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

   public function getAllAdmins($mysqli)
   {
      $sql = "SELECT c.* FROM customer c JOIN admins a ON c.customer_id = a.user_id";
      $result = $mysqli->query($sql);
      $admins = [];
      while ($row = $result->fetch_assoc()) {
         $admins[] = $row;
      }
      return $admins;
   }

   public function getUserByUsername($mysqli, $username)
   {
      $sql = "SELECT * FROM customer WHERE username = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("s", $username);
      $stmt->execute();
      $result = $stmt->get_result();
      return $result->fetch_assoc();
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
   public function getPrimaryActivePhoneNumber(int $userId): ?string // Renamed for clarity
   {
      $pdo = $this->pdo;
      // *** ADJUST table and column names if necessary ***
      $sql = "SELECT phonenumber
               FROM user_phones
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

   public function addPhoneNumber($userId, $newPhoneNumber)
   {
      if (empty($userId) || !is_numeric($userId) || empty($newPhoneNumber)) {
         return false;
      }

      // Optional: Check if this exact phone number already exists for the user
      try {
         $sqlCheck = "SELECT COUNT(*) FROM `{$this->phone_number_table}` WHERE `CustomerID` = :user_id AND `PhoneNumber` = :phone_number";
         $stmtCheck = $this->pdo->prepare($sqlCheck);
         $stmtCheck->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmtCheck->bindParam(':phone_number', $newPhoneNumber);
         $stmtCheck->execute();
         if ($stmtCheck->fetchColumn() > 0) {
            error_log("Attempt to add duplicate phone number {$newPhoneNumber} for user ID {$userId}");
            return false; // Phone number already exists for this user
         }

         // Determine if this should be the default phone number
         $sqlCountActiveDefault = "SELECT COUNT(*) FROM `{$this->phone_number_table}` WHERE `CustomerID` = :user_id AND `default_` = 1 AND `is_active` = 1";
         $stmtCount = $this->pdo->prepare($sqlCountActiveDefault);
         $stmtCount->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmtCount->execute();
         $hasActiveDefault = ($stmtCount->fetchColumn() > 0);

         $isNewDefault = !$hasActiveDefault; // If no active default exists, this new one becomes default

         $this->pdo->beginTransaction();

         // If this new number is to be default, ensure no others are default for this user
         if ($isNewDefault) {
            $sqlClearDefaults = "UPDATE `{$this->phone_number_table}` SET `default_` = 0 WHERE `CustomerID` = :user_id";
            $stmtClear = $this->pdo->prepare($sqlClearDefaults);
            $stmtClear->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtClear->execute();
         }

         // Insert the new phone number
         $sqlInsert = "INSERT INTO `{$this->phone_number_table}` (`CustomerID`, `PhoneNumber`, `default_`, `is_active`) 
                          VALUES (:user_id, :phone_number, :is_default, 1)";
         $stmtInsert = $this->pdo->prepare($sqlInsert);
         $stmtInsert->bindParam(':user_id', $userId, PDO::PARAM_INT);
         $stmtInsert->bindParam(':phone_number', $newPhoneNumber);
         $stmtInsert->bindValue(':is_default', $isNewDefault ? 1 : 0, PDO::PARAM_INT);
         $stmtInsert->execute();

         // If it's the new default, update the customer table's main phone_number field
         if ($isNewDefault) {
            $sqlUpdateCustomer = "UPDATE `{$this->customer_table}` SET `phone_number` = :phone_number WHERE `customer_id` = :user_id";
            $stmtUpdateCustomer = $this->pdo->prepare($sqlUpdateCustomer);
            $stmtUpdateCustomer->bindParam(':phone_number', $newPhoneNumber);
            $stmtUpdateCustomer->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmtUpdateCustomer->execute();
         }

         $this->pdo->commit();
         return true;

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
         $sql = "SELECT `phone_id`, `CustomerID`, `PhoneNumber`, `default_`, `is_active` 
                    FROM `{$this->phone_number_table}` 
                    WHERE `CustomerID` = :user_id ORDER BY `default_` DESC, `phone_id` ASC"; // Show default first
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

}
