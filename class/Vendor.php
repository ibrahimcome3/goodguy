<?php

class Vendor
{
    private $pdo;

    /**
     * Constructor for the Vendor class.
     *
     * @param PDO $pdo The PDO database connection object.
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetches a vendor's profile by their user ID. Returns false if not found.
     *
     * @param int $userId
     * @return array|false
     */
    public function getVendorByUserId(int $userId)
    {
        try {
            $sql = "SELECT * FROM vendors WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching vendor by user ID {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches a vendor's profile by their vendor ID. Returns false if not found.
     *
     * @param int $vendorId
     * @return array|false
     */
    public function getVendorById(int $vendorId)
    {
        try {
            $sql = "SELECT * FROM vendors WHERE vendor_id = :vendor_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':vendor_id' => $vendorId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching vendor by ID {$vendorId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches a single vendor's details by their vendor ID, including user info.
     *
     * @param int $vendorId
     * @return array|false
     */
    public function getVendorDetailsById(int $vendorId)
    {
        try {
            $sql = "SELECT v.*, c.username, c.customer_email, c.customer_fname, c.customer_lname
                    FROM vendors v
                    LEFT JOIN customer c ON v.user_id = c.customer_id
                    WHERE v.vendor_id = :vendor_id";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':vendor_id' => $vendorId]);

            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($vendor) {
                // Ensure all fields that should be strings are strings
                $stringFields = ['business_name', 'contact_name', 'business_phone', 'business_address', 'status'];
                foreach ($stringFields as $field) {
                    // Convert any null values to empty strings
                    if (isset($vendor[$field]) && $vendor[$field] === null) {
                        $vendor[$field] = '';
                    }

                    // Convert any array values to strings
                    if (isset($vendor[$field]) && is_array($vendor[$field])) {
                        $vendor[$field] = implode(", ", $vendor[$field]);
                    }
                }
            }

            return $vendor ?: false;
        } catch (PDOException $e) {
            error_log("Error fetching vendor details: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fetches all vendors, optionally with pagination and search.
     * Joins with the customer table to get user details.
     * @param string $searchTerm
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPaginatedVendors(string $searchTerm = '', int $limit = 15, int $offset = 0): array
    {
        $sql = "SELECT v.*, c.username, c.customer_email 
                FROM vendors v
                JOIN customer c ON v.user_id = c.customer_id";

        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " WHERE v.business_name LIKE :searchTerm OR c.username LIKE :searchTerm OR c.customer_email LIKE :searchTerm";
            $params[':searchTerm'] = "%$searchTerm%";
        }

        $sql .= " ORDER BY v.created_at DESC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            if (!empty($searchTerm)) {
                $stmt->bindValue(':searchTerm', $params[':searchTerm']);
            }
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching paginated vendors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Counts the total number of vendors, optionally filtered by a search term.
     * @param string $searchTerm
     * @return int
     */
    public function getVendorsCount(string $searchTerm = ''): int
    {
        $sql = "SELECT COUNT(v.vendor_id) 
                FROM vendors v
                JOIN customer c ON v.user_id = c.customer_id";

        $params = [];
        if (!empty($searchTerm)) {
            $sql .= " WHERE v.business_name LIKE :searchTerm OR c.username LIKE :searchTerm OR c.customer_email LIKE :searchTerm";
            $params[':searchTerm'] = "%$searchTerm%";
        }

        try {
            $stmt = $this->pdo->prepare($sql);
            if (!empty($searchTerm)) {
                $stmt->bindValue(':searchTerm', $params[':searchTerm']);
            }
            $stmt->execute();
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting vendors: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Fetches all vendors for dropdowns or lists.
     * @return array
     */
    public function getAllVendors(): array
    {
        try {
            $sql = "SELECT vendor_id, business_name FROM vendors ORDER BY business_name ASC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching all vendors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a new vendor
     *
     * @param array $data Vendor data (user_id, business_name, contact_name, phone, address, status)
     * @return int|false The new vendor ID if successful, false otherwise
     */
    public function addVendor(array $data): int|false
    {
        try {
            // Validate required fields
            if (empty($data['user_id']) || empty($data['business_name'])) {
                return false;
            }

            // Insert the new vendor
            $sql = "INSERT INTO vendors (
                        user_id, 
                        business_name, 
                        contact_name, 
                        business_phone, 
                        business_address, 
                        status, 
                        created_at, 
                        updated_at
                    ) VALUES (
                        :user_id, 
                        :business_name, 
                        :contact_name, 
                        :phone, 
                        :address, 
                        :status, 
                        NOW(), 
                        NOW()
                    )";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':user_id' => $data['user_id'],
                ':business_name' => $data['business_name'],
                ':contact_name' => $data['contact_name'] ?? null,
                ':phone' => $data['phone'] ?? null,
                ':address' => $data['address'] ?? null,
                ':status' => $data['status'] ?? 'active'
            ]);

            return (int) $this->pdo->lastInsertId();

        } catch (PDOException $e) {
            error_log("Error adding vendor: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Updates a vendor's status.
     *
     * @param int $vendorId
     * @param string $status
     * @return bool
     */
    public function updateVendorStatus(int $vendorId, string $status): bool
    {
        $allowed_statuses = ['active', 'inactive', 'pending', 'suspended'];
        if (!in_array($status, $allowed_statuses)) {
            return false;
        }
        $sql = "UPDATE vendors SET status = :status WHERE vendor_id = :vendor_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':status' => $status, ':vendor_id' => $vendorId]);
    }

    /**
     * Deletes a vendor.
     *
     * @param int $vendorId
     * @return bool
     */
    public function deleteVendor(int $vendorId): bool
    {
        // Add checks here, e.g., cannot delete if they have products.
        // For now, just a simple delete.
        try {
            $stmt = $this->pdo->prepare("DELETE FROM vendors WHERE vendor_id = :vendor_id");
            return $stmt->execute([':vendor_id' => $vendorId]);
        } catch (PDOException $e) {
            // Catch foreign key constraint violations
            error_log("Error deleting vendor ID {$vendorId}: " . $e->getMessage());
            return false;
        }
    }

}