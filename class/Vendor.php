<?php

class Vendor
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetches a vendor's profile by their user ID.
     *
     * @param integer $userId
     * @return array|false
     */
    public function getVendorByUserId(int $userId)
    {
        $sql = "SELECT * FROM vendors WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Inserts a new vendor into the database.
     *
     * @param array $vendorData
     * @return mixed The new vendor ID on success, 'duplicated' if exists, or false on failure.
     */
    public function insertVendor(array $vendorData): mixed
    {
        if ($this->getVendorByUserId($vendorData['user_id'])) {
            return "duplicated";
        }

        $sql = "INSERT INTO vendors (user_id, contact_name, business_name, business_email, business_phone, business_address, description, status)
            VALUES (:user_id, :contact_name, :business_name, :business_email, :business_phone, :business_address, :description, :status)";

        $stmt = $this->pdo->prepare($sql);

        $params = [
            ':user_id' => $vendorData['user_id'],
            ':contact_name' => $vendorData['contact_name'],
            ':business_name' => $vendorData['business_name'],
            ':business_email' => $vendorData['business_email'],
            ':business_phone' => $vendorData['business_phone'] ?? null,
            ':business_address' => $vendorData['business_address'] ?? null,
            ':description' => $vendorData['description'] ?? null,
            ':status' => $vendorData['status'] ?? 'pending',
        ];

        if ($stmt->execute($params)) {
            return $this->pdo->lastInsertId();
        }

        return false;
    }

    /**
     * Updates a vendor's business name and description.
     *
     * @param integer $userId
     * @param string $businessName
     * @param string $description
     * @return boolean
     */
    public function updateVendorDetails(int $userId, string $businessName, string $description): bool
    {
        $sql = "UPDATE vendors SET business_name = :business_name, description = :description WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':business_name' => $businessName,
            ':description' => $description,
            ':user_id' => $userId
        ]);
    }

    /**
     * Updates a vendor's status.
     *
     * @param integer $vendorId
     * @param string $status
     * @return boolean
     */
    public function updateVendorStatus(int $vendorId, string $status): bool
    {
        $sql = "UPDATE vendors SET status = :status WHERE vendor_id = :vendor_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':status' => $status, ':vendor_id' => $vendorId]);
    }

    public function getVendorById($vendorId)
    {
        $sql = "SELECT * FROM vendors WHERE vendor_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllVendors()
    {
        $sql = "SELECT * FROM vendors ORDER BY business_name ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}