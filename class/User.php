<?php
require_once 'Connn.php';
class User extends Connn
{
   public $user_id;
   public $user_email;
   public $user_address;
   public $user_role;
   public const ORDER_STATUS = [
      'PENDING' => 'Pending',
      'PROCESSING' => 'Processing',
      'SHIPPED' => 'Shipped',
      'DELIVERED' => 'Delivered',
      'CANCELLED' => 'Cancelled',
   ];


   function __construct()
   {
      parent::__construct();
      if (isset($_SESSION['uid'])) {
         $this->user_id = $_SESSION['uid'];
      }
      if (isset($this->user_id)) {
         $pdo = $this->dbc;
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
      $pdo = $this->dbc;
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
      $user_details = $this->getUserDetails($mysqli, $user_id);

      if ($user_details && !empty($user_details['shipping_address'])) {
         return $user_details['shipping_address'];
      }

      // If not found in either place, return a message
      return "Shipping address not available.";
   }
   function get_user_records()
   {
      $pdo = $this->dbc;
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
      $pdo = $this->dbc;
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
      $pdo = $this->dbc;
      $stmt = $pdo->query($sql);
      if ($stmt) {
         return true;
      } else {
         return false;
      }
   }

   function get_password()
   {
      $pdo = $this->dbc;
      $sql = "SELECT password FROM `customer` WHERE `customer_id` =  " . $this->user_id;
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch();
      return $row['password'];
   }

   function get_address_()
   {
      $pdo = $this->dbc;
      $sql = "select * from shipping_address left join shipping_state on shipping_address.state = shipping_state.state_id where customer_id = " . $this->user_id;
      $stmt = $pdo->query($sql);
      return $stmt;
   }

   function update_phone_number($phone_no, $phone_id)
   {

      $sql = " UPDATE phonenumber SET `PhoneNumber`= '$phone_no' WHERE `phone_id`=$phone_id";
      $pdo = $this->dbc;
      $stmt = $pdo->query($sql);
      if ($stmt) {
         return true;
      } else {
         return false;
      }
   }


   function make_phone_number_my_default($phone_id)
   {
      $pdo = $this->dbc;
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
      $pdo = $this->dbc;
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
      $pdo = $this->dbc;
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
      $pdo = $this->dbc;
      $sql = "select * from phonenumber where CustomerID =  " . $id;
      $stmt = $pdo->query($sql);
      return $stmt;
   }
   function delete_phone_number($id)
   {

      $pdo = $this->dbc;
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
      $pdo = $this->dbc;
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

   public function getUserById($mysqli, $userId)
   {
      $sql = "SELECT * FROM customer WHERE customer_id = ?";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("i", $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      return $result->fetch_assoc();
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

   public function addPhoneNumber($mysqli, $customerId, $phoneNumber, $isDefault = false)
   {
      $sql = "INSERT INTO phonenumber (CustomerID, PhoneNumber, default_) VALUES (?, ?, ?)";
      $stmt = $mysqli->prepare($sql);
      $stmt->bind_param("isi", $customerId, $phoneNumber, $isDefault);
      $stmt->execute();
      return $stmt->affected_rows > 0;
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


}
