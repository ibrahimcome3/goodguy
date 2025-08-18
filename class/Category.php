<?php

class Category
{
      protected $pdo; // Property to hold the PDO connection
      private $timestamp;
      private $parentIDS;

      function __construct(PDO $pdo) // Accept PDO connection in constructor
      {
            $this->pdo = $pdo; // Assign the passed PDO connection
            $defaultTimeZone = 'UTC';
            date_default_timezone_set($defaultTimeZone);
            $this->timestamp = date('Y-m-d');

            //var_dump( $this->parentIDS);
      }






      /**
       * Fetches all direct subcategories for a given parent category ID.
       * This method is used in `manage_categories.php` to check if a category can be safely deleted.
       *
       * @param int $parentId The ID of the parent category.
       * @return array An array of subcategory records. Returns an empty array if none are found or on error.
       */
      public function getSubcategories(int $parentId): array
      {
            try {
                  $stmt = $this->getDirectSubcategoriesByParentId($parentId);
                  return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
            } catch (PDOException $e) {
                  error_log("Error in getSubcategories for parent ID {$parentId}: " . $e->getMessage());
                  return [];
            }
      }
      public function getDirectSubcategoriesByParentId(int $parentId): PDOStatement|false
      {
            try {
                  $sql = "SELECT * FROM categories WHERE parent_id = :parent_id";
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->bindParam(':parent_id', $parentId, PDO::PARAM_INT);
                  $stmt->execute();
                  return $stmt;
            } catch (PDOException $e) {
                  error_log("Error fetching direct subcategories for parent ID {$parentId}: " . $e->getMessage());
                  return false;
            }
      }

      public function getCategoryNameById(int $categoryId): ?string
      {
            // Assuming you are migrating to the 'categories' table.
            // If 'category_new' is still needed, use that table name.
            $sql = "SELECT name FROM categories WHERE category_id = :category_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':category_id' => $categoryId]);
            return $stmt->fetchColumn() ?: null;
      }

      public function getCategoryNameByInventoryId(int $inventoryItemId): string
      {
            $sql = "SELECT c.name 
                    FROM inventoryitem ii
                    JOIN productitem pi ON ii.productItemID = pi.productID
                    JOIN categories c ON pi.category = c.category_id
                    WHERE ii.inventoryitemID = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $inventoryItemId]);
            return $stmt->fetchColumn() ?: 'Unknown Category';
      }

      /**
       * Fetches related categories based on a product's category path.
       * Note: This method seems to rely on an older table 'category_new' and a 'cat_path' column.
       * This logic might need updating for the new 'categories' table structure.
       *
       * @param int $productId
       * @return PDOStatement|null
       */
      public function getRelatedCategories(int $productId): ?PDOStatement
      {
            $sql = "SELECT cat_path FROM category_new WHERE cat_id = (SELECT `category` FROM productitem WHERE `productID` = ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$productId]);
            $row = $stmt->fetch();

            if ($row && isset($row['cat_path'])) {
                  // The original logic for string replacement seems specific and might need review.
                  // This implementation fixes the SQL injection and double-fetch bug.
                  $string_to_be_sa = "\\" . $productId;
                  $search_string = str_replace($string_to_be_sa, '', $row['cat_path']);

                  $sql_related = "SELECT * FROM category_new WHERE cat_path LIKE ?";
                  $stmt_related = $this->pdo->prepare($sql_related);
                  $stmt_related->execute(['\\' . $search_string . '%']);
                  return $stmt_related;
            }

            return null;
      }

      public function getCategorySpecific(string $cat): PDOStatement|false
      {
            $sql = "SELECT * FROM category_new WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = ? AND depth != 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cat]);
            return $stmt;
      }

      public function getCategorySpecificCount(string $cat): int
      {
            $sql = "SELECT COUNT(*) FROM category_new WHERE JSON_EXTRACT(`json_`, '$.roots[0]') = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$cat]);
            return (int) $stmt->fetchColumn();
      }

      public function countInventoryItemsByCategory(int $categoryId): int
      {
            $sql = "SELECT COUNT(*) FROM inventoryitem WHERE category = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$categoryId]);
            return (int) $stmt->fetchColumn();
      }

      //SELECT * FROM `variation` left join variation_option on variation.`vid` = variation_option.`variation_id` where category_id = 1



      public function getCategoryById($categoryId)
      {
            if (!$categoryId) {
                  return false;
            }
            try {
                  $sql = "SELECT * FROM categories WHERE category_id = ?";
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->execute([$categoryId]);
                  return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                  error_log("Error fetching category by ID: " . $e->getMessage());
                  return false;
            }
      }


      /**
       * Gets the total number of products associated with a specific category.
       *
       * @param int $categoryId The ID of the category.
       * @return int The number of products in that category.
       */
      public function getProductCount(int $categoryId): int
      {
            if ($categoryId <= 0) {
                  return 0;
            }
            try {
                  // Assumes the 'productitem' table has a 'category' column storing the category_id
                  $sql = "SELECT COUNT(*) FROM productitem WHERE category = :category_id";
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                  $stmt->execute();
                  return (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
                  error_log("Error fetching product count for category ID {$categoryId}: " . $e->getMessage());
                  return 0; // Return 0 on error
            }
      }

      /**
       * Gets product counts for multiple categories in a single query to avoid N+1 issues.
       *
       * @param array $categoryIds An array of category IDs.
       * @return array An associative array mapping category_id to product_count.
       */
      public function getProductCountsForCategories(array $categoryIds): array
      {
            if (empty($categoryIds)) {
                  return [];
            }
            $placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';
            $sql = "SELECT category, COUNT(*) as product_count 
                    FROM productitem 
                    WHERE category IN ($placeholders) 
                    GROUP BY category";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($categoryIds);
            return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
      }

      /**
       * Gets the total number of direct subcategories for a given parent ID.
       *
       * @param int $categoryId The ID of the parent category.
       * @return int The number of direct subcategories.
       */
      public function getSubcategoryCount(int $categoryId): int
      {
            if ($categoryId <= 0) {
                  return 0;
            }
            try {
                  $sql = "SELECT COUNT(*) FROM categories WHERE parent_id = :category_id";
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                  $stmt->execute();
                  return (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
                  error_log("Error fetching subcategory count for parent ID {$categoryId}: " . $e->getMessage());
                  return 0; // Return 0 on error
            }
      }

      /**
       * Deletes a category from the database.
       *
       * @param int $categoryId The ID of the category to delete.
       * @return bool True on success, false on failure.
       */
      public function deleteCategory(int $categoryId): bool
      {
            if ($categoryId <= 0) {
                  return false;
            }
            try {
                  $sql = "DELETE FROM categories WHERE category_id = :category_id";
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                  return $stmt->execute();
            } catch (PDOException $e) {
                  // This will catch foreign key constraint violations if a category is still in use
                  error_log("Error deleting category ID {$categoryId}: " . $e->getMessage());
                  return false;
            }
      }

      /**
       * Adds a new category to the database.
       *
       * @param string $name The name of the category.
       * @param string $description The description of the category.
       * @param int|null $parentId The ID of the parent category, or null for a top-level category.
       * @return int|false The ID of the newly created category, or false on failure.
       */
      public function addCategory(string $name, string $description = '', ?int $parentId = null): int|false
      {
            if (empty(trim($name))) {
                  return false;
            }

            $level = 0;
            if ($parentId !== null) {
                  $parentCategory = $this->getCategoryById($parentId);
                  if ($parentCategory) {
                        $level = $parentCategory['level'] + 1;
                  } else {
                        // Invalid parent ID provided, treat as top-level
                        $parentId = null;
                  }
            }

            $ownerId = $_SESSION['admin_id'] ?? null;

            try {
                  $sql = "INSERT INTO categories (name, description, parent_id, level, owner_id, active) 
                          VALUES (:name, :description, :parent_id, :level, :owner_id, 1)";
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':parent_id' => $parentId,
                        ':level' => $level,
                        ':owner_id' => $ownerId
                  ]);
                  return (int) $this->pdo->lastInsertId();
            } catch (PDOException $e) {
                  error_log("Error adding category: " . $e->getMessage());
                  return false;
            }
      }

      /**
       * Updates an existing category.
       *
       * @param int $categoryId The ID of the category to update.
       * @param string $name The new name for the category.
       * @param string $description The new description.
       * @param int|null $parentId The new parent ID.
       * @return bool True on success, false on failure.
       */
      public function updateCategory(int $categoryId, string $name, string $description = '', ?int $parentId = null): bool
      {
            if (empty(trim($name)) || $categoryId <= 0) {
                  return false;
            }

            // Prevent setting category as its own parent
            if ($categoryId === $parentId) {
                  return false;
            }

            $level = 0;
            if ($parentId !== null) {
                  $parentCategory = $this->getCategoryById($parentId);
                  $level = $parentCategory ? $parentCategory['level'] + 1 : 0;
            }

            try {
                  $sql = "UPDATE categories SET name = :name, description = :description, parent_id = :parent_id, level = :level WHERE category_id = :category_id";
                  $stmt = $this->pdo->prepare($sql);
                  return $stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':parent_id' => $parentId,
                        ':level' => $level,
                        ':category_id' => $categoryId
                  ]);
            } catch (PDOException $e) {
                  error_log("Error updating category ID {$categoryId}: " . $e->getMessage());
                  return false;
            }
      }

      /**
       * Toggles the active status of a category and returns the new status.
       *
       * @param int $categoryId The ID of the category to toggle.
       * @return int|false The new status (0 or 1) on success, false on failure.
       */
      public function toggleCategoryStatus(int $categoryId): int|false
      {
            if ($categoryId <= 0) {
                  return false;
            }
            try {
                  $this->pdo->beginTransaction();

                  // Get current status
                  $stmt = $this->pdo->prepare("SELECT active FROM categories WHERE category_id = :category_id FOR UPDATE");
                  $stmt->execute([':category_id' => $categoryId]);
                  $currentStatus = $stmt->fetchColumn();

                  if ($currentStatus === false) {
                        $this->pdo->rollBack();
                        return false; // Category not found
                  }

                  $newStatus = $currentStatus == 1 ? 0 : 1;

                  // Update to new status
                  $updateStmt = $this->pdo->prepare("UPDATE categories SET active = :new_status WHERE category_id = :category_id");
                  $updateStmt->execute([':new_status' => $newStatus, ':category_id' => $categoryId]);

                  $this->pdo->commit();
                  return $newStatus;
            } catch (PDOException $e) {
                  if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                  }
                  error_log("Error toggling status for category ID {$categoryId}: " . $e->getMessage());
                  return false;
            }
      }

      /**
       * Fetches top-level parent categories.
       * These are categories where level is 1 and parent_id is NULL.
       *
       * @return PDOStatement|false The PDOStatement object on success, or false on failure.
       */
      public function getTopLevelParentCategories()
      {
            try {
                  $sql = "SELECT `category_id`, `name`, `parent_id`, `level`, `owner_id` FROM `categories` WHERE `level` = 0 AND `parent_id` IS NULL";
                  $stmt = $this->pdo->query($sql);
                  return $stmt;
            } catch (PDOException $e) {
                  error_log("Error fetching top-level parent categories: " . $e->getMessage());
                  return false;
            }
      }


      /**
       * Get all categories (full info) for a given product ID.
       * Uses product_categories and categories tables.
       *
       * @param int $productId
       * @return array
       */
      public function getAllCategoriesOfProduct($productId)
      {
            // Get all category_ids for the product
            $sql = "SELECT category_id FROM product_categories WHERE product_id = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$productId]);
            $categoryIds = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                  $categoryIds[] = $row['category_id'];
            }

            if (empty($categoryIds)) {
                  return [];
            }

            // Get all category info for those IDs
            $in = str_repeat('?,', count($categoryIds) - 1) . '?';
            $sql = "SELECT category_id, name, parent_id, level, owner_id, icon_class FROM categories WHERE category_id IN ($in)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($categoryIds);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }



      public function getAllCategories()
      {
            $sql = "SELECT * FROM categories ORDER BY name ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      /**
       * Get all categories with their depth level in the hierarchy
       *
       * @return array Array of categories with depth information
       */
      public function getAllCategoriesWithDepth(): array
      {
            try {
                  // Fetch all categories and group them by parent_id in PHP for efficient tree building
                  $sql = "SELECT category_id, name, description, parent_id, active, level FROM categories ORDER BY name ASC";
                  $stmt = $this->pdo->query($sql);

                  $categoriesByParent = [];
                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        // Use an empty string as the key for top-level categories (where parent_id is NULL)
                        $key = $row['parent_id'] ?? '';
                        $categoriesByParent[$key][] = $row;
                  }

                  $sortedCategories = [];
                  // Start building the sorted array from the root (null parent_id)
                  $this->buildSortedCategoryArray($categoriesByParent, $sortedCategories, null, 0);

                  return $sortedCategories;
            } catch (PDOException $e) {
                  error_log("Error fetching categories with depth: " . $e->getMessage());
                  return [];
            }
      }

      /**
       * More efficient helper to build a flattened, sorted category tree with depth information.
       *
       * @param array $categoriesByParent Categories grouped by their parent_id
       * @param array &$result Output array to store the result
       * @param int|null $parentId Parent category ID to start from
       * @param int $depth Current depth level
       */
      private function buildSortedCategoryArray(array $categoriesByParent, array &$result, ?int $parentId, int $depth = 0): void
      {
            $key = $parentId ?? '';
            if (!isset($categoriesByParent[$key])) {
                  return;
            }

            foreach ($categoriesByParent[$key] as $category) {
                  $category['depth'] = $depth; // Use the calculated depth
                  $result[] = $category;
                  $this->buildSortedCategoryArray($categoriesByParent, $result, $category['category_id'], $depth + 1);
            }
      }
}





?>