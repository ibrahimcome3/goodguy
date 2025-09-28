<?php
// ProductItem is not a direct parent, so this require is likely not needed unless for another reason.
// If ProductItem is not a parent, we must declare our own PDO property.
// For now, assuming it's not a parent based on typical class design.
require_once __DIR__ . '/Email.php';

class Tag
{
    protected $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Get all available tags with creator information
     *
     * @param bool $approvedOnly Whether to get only approved tags
     * @return array List of all tags
     */
    public function getAllTags(bool $approvedOnly = true): array
    {
        try {
            $sql = "SELECT t.*, a.username as admin_username
                FROM tags t 
                LEFT JOIN admins a ON t.admin_id = a.admin_id
                ";

            if ($approvedOnly) {
                $sql .= " WHERE t.status = 'approved'";
            }

            $sql .= " ORDER BY t.name";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching tags: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get the total count of approved tags, optionally filtered by a search term.
     *
     * @param string $searchTerm The term to search for in tag names.
     * @return int The total number of matching tags.
     */
    public function getTagsCount(string $searchTerm = ''): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM tags WHERE status = 'approved'";
            $params = [];
            if (!empty($searchTerm)) {
                $sql .= " AND name LIKE :searchTerm";
                $params[':searchTerm'] = "%$searchTerm%";
            }
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting tags: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get a paginated list of approved tags, optionally filtered by a search term.
     *
     * @param string $searchTerm The term to search for.
     * @param int $limit The number of records per page.
     * @param int $offset The starting record number.
     * @return array A list of tags.
     */
    public function getPaginatedTags(string $searchTerm = '', int $limit = 15, int $offset = 0): array
    {
        try {
            $sql = "SELECT t.*, a.username as admin_username
                    FROM tags t 
                    LEFT JOIN admins a ON t.admin_id = a.admin_id
                    WHERE t.status = 'approved'";
            if (!empty($searchTerm)) {
                $sql .= " AND t.name LIKE :searchTerm";
            }
            $sql .= " ORDER BY t.name ASC LIMIT :limit OFFSET :offset";
            $stmt = $this->pdo->prepare($sql);
            if (!empty($searchTerm))
                $stmt->bindValue(':searchTerm', "%$searchTerm%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching paginated tags: " . $e->getMessage());
            return [];
        }
    }

    public function addProductTags($productId, $tagIds)
    {
        // Implementation needed
        if (empty($tagIds) || !is_array($tagIds)) {
            return;
        }

        $stmt = $this->pdo->prepare("INSERT INTO product_tags (product_id, tag_id) VALUES (?, ?)");

        foreach ($tagIds as $tagId) {
            // Binds the product ID and the current tag ID to the prepared statement and executes it.
            $stmt->execute([$productId, (int) $tagId]);
        }
    }

    /**
     * Get tags pending approval
     *
     * @return array List of pending tags
     */
    public function getPendingTags(): array
    {
        try {
            $sql = "SELECT t.*, v.business_name as vendor_name 
                FROM tags t 
                LEFT JOIN vendors v ON t.created_by_vendor_id = v.vendor_id
                WHERE t.status = 'pending'
                ORDER BY t.created_at DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching pending tags: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Add a new tag with proper attribution
     *
     * @param string $tagName Tag name
     * @param int|null $adminId Admin ID if added by admin
     * @param int|null $vendorId Vendor ID if added by vendor
     * @return int|false Tag ID if successful, false otherwise
     */
    public function addTag(string $tagName, ?int $adminId = null, ?int $vendorId = null): int|false
    {
        try {
            // Determine tag status based on who's adding it
            $status = $adminId ? 'approved' : 'pending';

            // First check if tag already exists
            $checkSql = "SELECT tag_id, status FROM tags WHERE LOWER(name) = LOWER(:name)";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([':name' => trim($tagName)]);
            $existingTag = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if ($existingTag) {
                // If tag exists and an admin is trying to add it again, approve it
                if ($adminId && $existingTag['status'] === 'pending') {
                    $updateSql = "UPDATE tags SET status = 'approved', admin_id = :admin_id WHERE tag_id = :tag_id";
                    $updateStmt = $this->pdo->prepare($updateSql);
                    $updateStmt->execute([
                        ':admin_id' => $adminId,
                        ':tag_id' => $existingTag['tag_id']
                    ]);
                }
                return $existingTag['tag_id'];
            }

            // If tag doesn't exist, add it
            $sql = "INSERT INTO tags (name, admin_id, created_by_vendor_id, status) 
                VALUES (:name, :admin_id, :vendor_id, :status)";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => trim($tagName),
                ':admin_id' => $adminId,
                ':vendor_id' => $vendorId,
                ':status' => $status
            ]);

            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error adding tag: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update an existing tag's name.
     *
     * @param int $tagId The ID of the tag to update.
     * @param string $tagName The new name for the tag.
     * @return bool True on success, false on failure.
     */
    public function updateTag(int $tagId, string $tagName): bool
    {
        try {
            // Check if another tag with the new name already exists to prevent duplicates.
            $checkSql = "SELECT tag_id FROM tags WHERE LOWER(name) = LOWER(:name) AND tag_id != :tag_id";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([':name' => trim($tagName), ':tag_id' => $tagId]);
            if ($checkStmt->fetch()) {
                // Another tag with this name exists.
                return false;
            }

            $sql = "UPDATE tags SET name = :name WHERE tag_id = :tag_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([':name' => trim($tagName), ':tag_id' => $tagId]);
        } catch (PDOException $e) {
            error_log("Error updating tag: " . $e->getMessage());
            return false;
        }
    }
    /**
     * Approve a pending tag
     *
     * @param int $tagId Tag ID
     * @param int $adminId Admin ID who is approving
     * @return bool True if successful, false otherwise
     */
    public function approveTag(int $tagId, int $adminId): bool
    {
        try {
            // First, get tag and vendor info for the email notification
            $sqlInfo = "SELECT t.name, v.email 
                        FROM tags t 
                        LEFT JOIN vendors v ON t.created_by_vendor_id = v.vendor_id 
                        WHERE t.tag_id = :tag_id AND t.status = 'pending' AND t.created_by_vendor_id IS NOT NULL";
            $stmtInfo = $this->pdo->prepare($sqlInfo);
            $stmtInfo->execute([':tag_id' => $tagId]);
            $tagInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            // Now, approve the tag
            $sql = "UPDATE tags SET status = 'approved', admin_id = :admin_id WHERE tag_id = :tag_id AND status = 'pending'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':admin_id' => $adminId,
                ':tag_id' => $tagId
            ]);

            $success = $stmt->rowCount() > 0;

            // If approval was successful and we have vendor info, send email
            if ($success && $tagInfo && !empty($tagInfo['email'])) {
                $emailObj = new Email();
                $subject = "Your tag suggestion has been approved";
                $message = "<p>Hello,</p><p>Your tag suggestion '<strong>" . htmlspecialchars($tagInfo['name']) . "</strong>' has been approved by an administrator and is now available for use.</p><p>Thank you for your contribution!</p><p>The GoodGuy Team</p>";
                $emailObj->send($tagInfo['email'], $subject, $message);
            }

            return $success;
        } catch (PDOException $e) {
            error_log("Error approving tag: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject (delete) a pending tag
     *
     * @param int $tagId Tag ID
     * @return bool True if successful, false otherwise
     */
    public function rejectTag(int $tagId): bool
    {
        try {
            // First, get tag and vendor info for the email notification
            $sqlInfo = "SELECT t.name, v.email 
                        FROM tags t 
                        LEFT JOIN vendors v ON t.created_by_vendor_id = v.vendor_id 
                        WHERE t.tag_id = :tag_id AND t.status = 'pending' AND t.created_by_vendor_id IS NOT NULL";
            $stmtInfo = $this->pdo->prepare($sqlInfo);
            $stmtInfo->execute([':tag_id' => $tagId]);
            $tagInfo = $stmtInfo->fetch(PDO::FETCH_ASSOC);

            // Now, delete the tag
            $sql = "DELETE FROM tags WHERE tag_id = :tag_id AND status = 'pending'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':tag_id' => $tagId]);

            $success = $stmt->rowCount() > 0;

            // If rejection was successful and we have vendor info, send email
            if ($success && $tagInfo && !empty($tagInfo['email'])) {
                $emailObj = new Email();
                $subject = "Regarding your tag suggestion";
                $message = "<p>Hello,</p><p>Thank you for your tag suggestion '<strong>" . htmlspecialchars($tagInfo['name']) . "</strong>'.</p><p>After review, we have decided not to approve this tag at this time. This could be because it is a duplicate, does not meet our guidelines, or for other reasons.</p><p>We appreciate your input.</p><p>The GoodGuy Team</p>";
                $emailObj->send($tagInfo['email'], $subject, $message);
            }

            return $success;
        } catch (PDOException $e) {
            error_log("Error rejecting tag: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Associate tags with a product with attribution
     *
     * @param int $productId Product ID
     * @param array $tagIds Array of tag IDs
     * @param int|null $adminId Admin ID if added by admin
     * @param int|null $vendorId Vendor ID if added by vendor
     * @return bool True on success, false on failure
     */
    public function setProductTags(int $productId, array $tagIds, ?int $adminId = null, ?int $vendorId = null): bool
    {
        try {
            // Start transaction
            $this->pdo->beginTransaction();

            // Remove existing tag associations
            $sql = "DELETE FROM product_tags WHERE product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':product_id' => $productId]);

            // Add new tag associations with attribution
            if (!empty($tagIds)) {
                $sql = "INSERT INTO product_tags (product_id, tag_id, added_by_admin_id, added_by_vendor_id) 
                    VALUES (:product_id, :tag_id, :admin_id, :vendor_id)";
                $stmt = $this->pdo->prepare($sql);

                foreach ($tagIds as $tagId) {
                    $stmt->execute([
                        ':product_id' => $productId,
                        ':tag_id' => $tagId,
                        ':admin_id' => $adminId,
                        ':vendor_id' => $vendorId
                    ]);
                }
            }

            // Commit transaction
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            // Rollback on error
            $this->pdo->rollBack();
            error_log("Error setting product tags: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add tags to a product by tag names (creates tags if they don't exist)
     *
     * @param int $productId Product ID
     * @param array $tagNames Array of tag names
     * @param int|null $adminId Admin ID if added by admin
     * @param int|null $vendorId Vendor ID if added by vendor
     * @return bool True on success, false on failure
     */
    public function addTagsByName(int $productId, array $tagNames, ?int $adminId = null, ?int $vendorId = null): bool
    {
        try {
            // Filter out empty tags
            $tagNames = array_filter(array_map('trim', $tagNames));

            if (empty($tagNames)) {
                return true; // Nothing to do
            }

            // Start transaction
            $this->pdo->beginTransaction();

            $tagIds = [];
            foreach ($tagNames as $tagName) {
                // Skip empty strings
                if (empty($tagName))
                    continue;

                $tagId = $this->addTag($tagName, $adminId, $vendorId);
                if ($tagId) {
                    $tagIds[] = $tagId;
                }
            }

            // Associate tags with product
            if (!empty($tagIds)) {
                // Only include approved tags or tags created by this admin
                if ($adminId) {
                    // Admins can add any approved tag or ones they created
                    $sql = "SELECT tag_id FROM tags WHERE tag_id IN (" . implode(',', $tagIds) . ") 
                        AND (status = 'approved' OR admin_id = :admin_id)";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute([':admin_id' => $adminId]);
                } else {
                    // Vendors can only add approved tags
                    $sql = "SELECT tag_id FROM tags WHERE tag_id IN (" . implode(',', $tagIds) . ") AND status = 'approved'";
                    $stmt = $this->pdo->query($sql);
                }

                $approvedTagIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($approvedTagIds)) {
                    $existingTags = $this->getTagsByProductId($productId);
                    $existingTagIds = array_column($existingTags, 'tag_id');

                    // Merge existing and new tags (unique)
                    $allTagIds = array_unique(array_merge($existingTagIds, $approvedTagIds));

                    $this->setProductTags($productId, $allTagIds, $adminId, $vendorId);
                }
            }

            // Commit transaction
            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            // Rollback on error
            $this->pdo->rollBack();
            error_log("Error adding tags by name: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get tags for a specific product with attribution info
     *
     * @param int $productId Product ID
     * @return array Tags associated with the product
     */
    public function getTagsByProductId(int $productId): array
    {
        try {
            $sql = "SELECT t.tag_id, t.name, t.status, pt.added_by_admin_id, pt.added_by_vendor_id,
                a.username as admin_name, v.business_name as vendor_name
                FROM tags t
                JOIN product_tags pt ON t.tag_id = pt.tag_id
                LEFT JOIN admins a ON pt.added_by_admin_id = a.admin_id
                LEFT JOIN vendors v ON pt.added_by_vendor_id = v.vendor_id
                WHERE pt.product_id = :product_id
                ORDER BY t.name";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':product_id' => $productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching tags for product: " . $e->getMessage());
            return [];
        }
    }


}