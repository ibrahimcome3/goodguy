<?php

require_once 'User.php'; // Ensure User class is loaded

class Admin extends User
{
    /**
     * Constructor for the Admin class.
     * Calls the parent User class constructor.
     *
     * @param PDO $pdo The PDO database connection object.
     */
    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
    }

    /**
     * Get admin_id by user_id if it exists.
     *
     * @param int $userId
     * @return int|null
     */
    public function getAdminIdByUserId($userId)
    {
        $sql = "SELECT admin_id FROM admins WHERE user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int) $row['admin_id'] : null;
    }
    /**
     * Attempts to log in an administrator.
     *
     * @param string $usernameOrEmail The username or email of the admin.
     * @param string $password The plain text password to verify.
     * @return bool True if login is successful, false otherwise.
     */
    public function loginAdmin($usernameOrEmail, $password)
    {
        if (empty($usernameOrEmail) || empty($password)) {
            return false; // Basic validation
        }

        // Attempt to get user by username or email using $this->pdo
        $customerDetails = null;
        try {
            $sqlCustomer = "SELECT customer_id, username, customer_fname FROM customer WHERE username = :username OR customer_email = :email LIMIT 1";
            $stmtCustomer = $this->pdo->prepare($sqlCustomer);
            $stmtCustomer->bindParam(':username', $usernameOrEmail);
            $stmtCustomer->bindParam(':email', $usernameOrEmail); // Use the same variable for both username or email
            $stmtCustomer->execute();
            $customerDetails = $stmtCustomer->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Admin login method: Error fetching customer details - " . $e->getMessage());
            return false;
        }

        if ($customerDetails) {
            $customerId = $customerDetails['customer_id'];

            // Check the 'admins' table for this customer_id and their admin-specific password
            try {
                $sqlAdmin = "SELECT user_id, `password` FROM admins WHERE user_id = :user_id";
                $stmtAdmin = $this->pdo->prepare($sqlAdmin);
                $stmtAdmin->bindParam(':user_id', $customerId, PDO::PARAM_INT);
                $stmtAdmin->execute();
                $adminRecord = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log("Admin login method: Error fetching admin record - " . $e->getMessage());
                return false;
            }

            if ($adminRecord && $adminRecord['password'] !== null) {
                // Admin record exists and has a specific password set.
                // Verify the password using password_verify()
                if (password_verify($password, $adminRecord['password'])) {
                    // Admin-specific password correct

                    // Set session variables
                    $_SESSION['admin_id'] = $adminRecord['user_id']; // This is the customer_id
                    $_SESSION['admin_username'] = $customerDetails['username'] ?: $customerDetails['customer_fname'];
                    $_SESSION['is_super_admin'] = !empty($customerDetails['super_admin']); // Boolean

                    // Regenerate session ID for security
                    session_regenerate_id(delete_old_session: true);

                    return true; // Login successful
                }
            }
        }
        return false; // Login failed (user not found, not an admin, or password incorrect)
    }


    /**
     * Sets or removes a user's admin status, including their admin-specific password.
     *
     * @param int $userId The ID of the user.
     * @param bool $isAdmin True to make the user an admin, false to remove admin status.
     * @param string|null $adminPassword The admin-specific password. Required if $isAdmin is true.
     * @return bool True on success, false on failure.
     * @throws Exception If an error occurs or if password is not provided when making an admin.
     */
    public function setAdminStatus($userId, $isAdmin, $adminPassword = null)
    {
        if (!is_int($userId) || $userId <= 0) {
            throw new InvalidArgumentException("User ID must be a positive integer.");
        }
        if (!is_bool($isAdmin)) {
            throw new InvalidArgumentException("isAdmin flag must be a boolean.");
        }

        $this->pdo->beginTransaction();

        try {
            if ($isAdmin) {
                if (empty($adminPassword)) {
                    $this->pdo->rollBack();
                    throw new InvalidArgumentException("An admin-specific password is required to grant admin status.");
                }
                $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
                if ($hashedPassword === false) {
                    $this->pdo->rollBack();
                    throw new RuntimeException("Failed to hash admin password.");
                }

                // Add/Update user in admins table with their specific password
                // Using INSERT ... ON DUPLICATE KEY UPDATE is good if an admin might be re-enabled or password changed
                $sqlAdmins = "INSERT INTO admins (user_id, password, created_at) VALUES (?, ?, NOW())
                              ON DUPLICATE KEY UPDATE password = :password_update, created_at = NOW()";
                $stmtAdmins = $this->pdo->prepare($sqlAdmins);
                if (!$stmtAdmins) {
                    throw new RuntimeException("Prepare failed (admins insert/update): " . implode(", ", $this->pdo->errorInfo()));
                }
                // For ON DUPLICATE KEY UPDATE, you might need to bind the password twice or use named placeholders consistently
                if (!$stmtAdmins->execute(['user_id' => $userId, 'password' => $hashedPassword, 'password_update' => $hashedPassword])) {
                    throw new RuntimeException("Execute failed (admins insert/update): " . implode(", ", $stmtAdmins->errorInfo()));
                }

                // Update is_admin flag in customer table
                $sqlCustomer = "UPDATE customer SET is_admin = 1 WHERE customer_id = :user_id";
                $stmtCustomer = $this->pdo->prepare($sqlCustomer);
                if (!$stmtCustomer) {
                    throw new RuntimeException("Prepare failed (customer update is_admin): " . implode(", ", $this->pdo->errorInfo()));
                }
                if (!$stmtCustomer->execute(['user_id' => $userId])) {
                    throw new RuntimeException("Execute failed (customer update is_admin): " . implode(", ", $stmtCustomer->errorInfo()));
                }

            } else {
                // Remove user from admins table
                $sqlAdmins = "DELETE FROM admins WHERE user_id = :user_id";
                $stmtAdmins = $this->pdo->prepare($sqlAdmins);
                if (!$stmtAdmins) {
                    throw new RuntimeException("Prepare failed (admins delete): " . implode(", ", $this->pdo->errorInfo()));
                }
                if (!$stmtAdmins->execute(['user_id' => $userId])) {
                    throw new RuntimeException("Execute failed (admins delete): " . implode(", ", $stmtAdmins->errorInfo()));
                }

                // Update is_admin and super_admin flags in customer table
                // Removing admin status should also revoke super_admin status for consistency
                $sqlCustomer = "UPDATE customer SET is_admin = 0, super_admin = 0 WHERE customer_id = :user_id";
                $stmtCustomer = $this->pdo->prepare($sqlCustomer);
                if (!$stmtCustomer) {
                    throw new RuntimeException("Prepare failed (customer update flags): " . implode(", ", $this->pdo->errorInfo()));
                }
                if (!$stmtCustomer->execute(['user_id' => $userId])) {
                    throw new RuntimeException("Execute failed (customer update flags): " . implode(", ", $stmtCustomer->errorInfo()));
                }
            }

            $this->pdo->commit();
            return true;

        } catch (PDOException $e) { // Catch PDOException specifically for database errors
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            // Log the error for debugging
            error_log("Error in Admin::setAdminStatus for User ID {$userId}: " . $e->getMessage());
            // Re-throw the exception so the calling code can handle it if needed,
            // or return false if you prefer to handle errors more silently.
            throw $e; // Or return false;
        } catch (Exception $e) { // Catch other general exceptions
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Error in Admin::setAdminStatus for User ID {$userId}: " . $e->getMessage());
            throw $e;
        }
    }

    // You can add other admin-specific methods here. For example:
    // - Methods to manage admin permissions (if you implement a more granular system)
    // - Methods to fetch admin-specific logs or reports
    // - Overriding methods from User class if admin behavior needs to be different

    /**
     * Example: A method to get all users who have an entry in the 'admins' table,
     * along with their admin-specific creation date.
     * This overrides or complements User::getAllAdmins if needed.
     */
    public function getAllAdminDetails()
    {
        $sql = "SELECT c.customer_id, c.username, c.customer_email, c.customer_fname, c.customer_lname, c.is_admin, c.super_admin, a.created_at as admin_since
                FROM customer c
                JOIN admins a ON c.customer_id = a.user_id
                ORDER BY c.customer_lname, c.customer_fname";
        $admins = [];
        try {
            $stmt = $this->pdo->query($sql);
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in Admin::getAllAdminDetails: " . $e->getMessage());
            // Optionally return empty array or re-throw, depending on desired error handling
        }
        return $admins;
    }
}
