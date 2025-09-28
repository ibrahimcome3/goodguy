<?php

class Category
{
      protected $pdo;
      private $timestamp;
      private $parentIDS;

      function __construct(PDO $pdo)
      {
            $this->pdo = $pdo;
            date_default_timezone_set('UTC');
            $this->timestamp = date('Y-m-d');
      }

      /**
       * @param int $parentId
       * @return array
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

      /**
       * Return PDOStatement or false (no union type for PHP <8)
       * @param int $parentId
       * @return PDOStatement|false
       */
      public function getDirectSubcategoriesByParentId(int $parentId) /* no union type */
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

      public function getRelatedCategories(int $productId) /* was ?PDOStatement */
      {
            $sql = "SELECT cat_path FROM category_new WHERE cat_id = (SELECT `category` FROM productitem WHERE `productID` = ?)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$productId]);
            $row = $stmt->fetch();

            if ($row && isset($row['cat_path'])) {
                  $string_to_be_sa = "\\" . $productId;
                  $search_string = str_replace($string_to_be_sa, '', $row['cat_path']);

                  $sql_related = "SELECT * FROM category_new WHERE cat_path LIKE ?";
                  $stmt_related = $this->pdo->prepare($sql_related);
                  $stmt_related->execute(['\\' . $search_string . '%']);
                  return $stmt_related;
            }
            return null;
      }

      /**
       * Gets all categories related to a product, starting from an inventory item ID.
       * It first finds the primary category on the product, then finds all categories
       * from the product_categories junction table, and returns the unique combined list.
       *
       * @param int $inventoryItemId The ID of the inventory item.
       * @return array An array of category details, or an empty array on failure.
       */
      public function get_related_categories(int $inventoryItemId): array
      {
            try {
                  // A single, efficient query to get all categories for a given inventory item,
                  // respecting the new database schema where categories are in `product_categories`.
                  $sql = "SELECT c.category_id, c.name
                          FROM inventoryitem AS ii
                          INNER JOIN productitem AS pi ON ii.productItemID = pi.productID
                          INNER JOIN product_categories AS pc ON pi.productID = pc.product_id
                          INNER JOIN categories AS c ON pc.category_id = c.category_id
                          WHERE ii.InventoryItemID = :inventory_item_id
                          ORDER BY c.name ASC";

                  $stmt = $this->pdo->prepare($sql);
                  $stmt->execute([':inventory_item_id' => $inventoryItemId]);

                  return $stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                  // Log the error and return an empty array so the calling code doesn't break.
                  error_log("Database error in Category::get_related_categories: " . $e->getMessage());
                  return [];
            }
      }

      /**
       * @param string $cat
       * @return PDOStatement|false
       */
      public function getCategorySpecific(string $cat) /* removed union type */
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

      public function getProductCount(int $categoryId): int
      {
            if ($categoryId <= 0) {
                  return 0;
            }
            try {
                  $sql = "SELECT COUNT(*) FROM productitem WHERE category = :category_id";
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
                  $stmt->execute();
                  return (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
                  error_log("Error fetching product count for category ID {$categoryId}: " . $e->getMessage());
                  return 0;
            }
      }

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
                  return 0;
            }
      }

      /**
       * @return bool
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
                  error_log("Error deleting category ID {$categoryId}: " . $e->getMessage());
                  return false;
            }
      }

      /**
       * @return int|false
       */
      public function addCategory(string $name, string $description = '', ?int $parentId = null)
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

      public function updateCategory(int $categoryId, string $name, string $description = '', ?int $parentId = null): bool
      {
            if (empty(trim($name)) || $categoryId <= 0) {
                  return false;
            }
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
       * @return int|false new status or false
       */
      public function toggleCategoryStatus(int $categoryId)
      {
            if ($categoryId <= 0) {
                  return false;
            }
            try {
                  $this->pdo->beginTransaction();

                  $stmt = $this->pdo->prepare("SELECT active FROM categories WHERE category_id = :category_id FOR UPDATE");
                  $stmt->execute([':category_id' => $categoryId]);
                  $currentStatus = $stmt->fetchColumn();

                  if ($currentStatus === false) {
                        $this->pdo->rollBack();
                        return false;
                  }

                  $newStatus = $currentStatus == 1 ? 0 : 1;

                  $updateStmt = $this->pdo->prepare("UPDATE categories SET active = :new_status WHERE category_id = :category_id");
                  $updateStmt->execute([':new_status' => $newStatus, ':category_id' => $categoryId]);

                  $this->pdo->commit();
                  return (int) $newStatus;
            } catch (PDOException $e) {
                  if ($this->pdo->inTransaction()) {
                        $this->pdo->rollBack();
                  }
                  error_log("Error toggling status for category ID {$categoryId}: " . $e->getMessage());
                  return false;
            }
      }

      public function getTopLevelParentCategories()
      {
            try {
                  $sql = "SELECT `category_id`, `name`, `parent_id`, `level`, `owner_id` FROM `categories` WHERE `level` = 0 AND `parent_id` IS NULL";
                  return $this->pdo->query($sql);
            } catch (PDOException $e) {
                  error_log("Error fetching top-level parent categories: " . $e->getMessage());
                  return false;
            }
      }

      public function getAllCategoriesOfProduct($productId)
      {
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
            $in = str_repeat('?,', count($categoryIds) - 1) . '?';
            $sql = "SELECT category_id, name, parent_id, level, owner_id, icon_class FROM categories WHERE category_id IN ($in)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($categoryIds);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      public function getAllCategories()
      {
            $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
      }

      public function getAllCategoriesWithDepth(): array
      {
            try {
                  $sql = "SELECT category_id, name, description, parent_id, active, level FROM categories ORDER BY name ASC";
                  $stmt = $this->pdo->query($sql);

                  $categoriesByParent = [];
                  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $key = $row['parent_id'] ?? '';
                        $categoriesByParent[$key][] = $row;
                  }

                  $sortedCategories = [];
                  $this->buildSortedCategoryArray($categoriesByParent, $sortedCategories, null, 0);
                  return $sortedCategories;
            } catch (PDOException $e) {
                  error_log("Error fetching categories with depth: " . $e->getMessage());
                  return [];
            }
      }

      private function buildSortedCategoryArray(array $categoriesByParent, array &$result, ?int $parentId, int $depth = 0): void
      {
            $key = $parentId ?? '';
            if (!isset($categoriesByParent[$key])) {
                  return;
            }
            foreach ($categoriesByParent[$key] as $category) {
                  $category['depth'] = $depth;
                  $result[] = $category;
                  $this->buildSortedCategoryArray($categoriesByParent, $result, $category['category_id'], $depth + 1);
            }
      }

      /**
       * Gets all categories associated with a specific product ID.
       *
       * @param int $productId The ID of the product.
       * @return array An array of categories associated with the product.
       */
      public function getCategoriesByProductId(int $productId): array
      {
            $sql = "SELECT c.category_id, c.name 
                FROM categories c
                JOIN product_categories pc ON c.category_id = pc.category_id
                WHERE pc.product_id = ?";

            try {
                  $stmt = $this->pdo->prepare($sql);
                  $stmt->execute([$productId]);
                  return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                  error_log("Database error in Category::getCategoriesByProductId: " . $e->getMessage());
                  return [];
            }
      }
}

?>