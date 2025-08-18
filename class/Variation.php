<?php
class Variation extends ProductItem
{
  private $timestamp;
  private $parentIDS;

  protected $pdo; // Store the PDO connection here
  public function __construct($pdo)
  {

    $this->pdo = $pdo; // Store the PDO connection
    $defaultTimeZone = 'UTC';
    date_default_timezone_set($defaultTimeZone);
    $this->timestamp = date('Y-m-d');
  }
  public function get_variations_for_product($product_id)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM variations WHERE productItemID = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  public function get_color_variations_for_product_from_sku($product_id)
  {
    $stmt = $this->pdo->prepare("SELECT DISTINCT ii.InventoryItemID, ii.sku FROM inventoryitem ii WHERE ii.productItemID = ?");
    $stmt->execute([$product_id]);
    $colors = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $skuData = json_decode($row['sku'], true);
      if (isset($skuData['color'])) {
        $colors[$row['InventoryItemID']] = $skuData['color'];
      }
    }
    return $colors;
  }

  public function get_size_variations_for_product_from_sku($product_id, $color = null)
  {
    $stmt = $this->pdo->prepare("SELECT ii.InventoryItemID, ii.sku FROM inventoryitem ii WHERE ii.productItemID = ?");
    $stmt->execute([$product_id]);
    $sizes = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $skuData = json_decode($row['sku'], true);
      if (isset($skuData['size'])) {
        if ($color === null || (isset($skuData['color']) && strtoupper($skuData['color']) === strtoupper($color))) {
          $sizes[$row['InventoryItemID']] = $skuData['size'];
        }
      }
    }
    return $sizes;
  }

  /**
   * Fetch all variants for a given product, including their primary thumbnail and all images.
   *
   * @param int $productId
   * @return array
   */



  /**
   * Returns all “color” variants for a product, each with its thumbnail
   * and full image list.
   *
   * @param int $productId
   * @return array
   */
  public function getProductVariants(int $productId): array
  {
    // 1) Get primary thumbnail + color from SKU JSON
    $sql = "
          SELECT 
            ii.InventoryItemID,
            JSON_UNQUOTE(JSON_EXTRACT(ii.sku,'$.color')) AS color,
            img.image_path AS thumbnail
          FROM inventoryitem ii
          LEFT JOIN inventory_item_image img
            ON img.inventory_item_id = ii.InventoryItemID
            AND img.is_primary = 1
          WHERE ii.productItemID = ?
          ORDER BY ii.InventoryItemID
        ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2) For each variant, load all its images
    foreach ($variants as &$v) {
      $v['images'] = $this->loadImagesForInventoryItem((int) $v['InventoryItemID']);
    }

    return $variants;
  }

  /**
   * Helper to fetch all image_paths for one inventory item.
   */
  private function loadImagesForInventoryItem(int $inventoryItemId): array
  {
    $stmt = $this->pdo->prepare("
          SELECT image_path
          FROM inventory_item_image
          WHERE inventory_item_id = ?
          ORDER BY sort_order
        ");
    $stmt->execute([$inventoryItemId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  }


  public function getVariantsByProductId(int $productId): array
  {
    // 1) get each inventory item’s JSON‐encoded SKU and primary image
    $sql = "
            SELECT
             ii.quantity,
                ii.InventoryItemID,
                ii.sku,
                img.image_path AS thumbnail
            FROM inventoryitem ii
            LEFT JOIN inventory_item_image img 
              ON img.inventory_item_id = ii.InventoryItemID 
             AND img.is_primary = 1
            WHERE ii.productItemID = ?
            ORDER BY ii.InventoryItemID
        ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$productId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $variants = [];
    foreach ($rows as $row) {
      // decode SKU JSON, pull out color
      $skuData = json_decode($row['sku'], true) ?: [];
      if (empty($skuData['color'])) {
        continue;
      }
      $images = $this->loadImagesForInventoryItem((int) $row['InventoryItemID']);
      $variants[] = [
        'InventoryItemID' => $row['InventoryItemID'],
        'color' => $skuData['color'],
        'thumbnail' => $row['thumbnail'] ?? '',
        'images' => $images,
        'quantity' => $row['quantity'] ?? 0,
        'sku' => $row['sku'] ?? '',
        'price' => $row['price'] ?? 0.0,
        'cost' => $row['cost'] ?? 0.0
      ];
    }

    return $variants;
  }


  /**
   * Get all images for a specific inventory item.
   *
   * @param int $inventoryItemId
   * @return array
   */
  public function getImagesForInventoryItem(int $inventoryItemId): array
  {
    $sql = "
        SELECT 
            inventory_item_image_id,
            image_name,
            image_path,
            is_primary,
            sort_order
        FROM inventory_item_image
        WHERE inventory_item_id = ?
        ORDER BY is_primary DESC, sort_order ASC
    ";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$inventoryItemId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getVariantById($inventoryItemId)
  {
    $stmt = $this->pdo->prepare("SELECT * FROM inventoryitem WHERE inventoryitemID = ?");
    $stmt->execute([$inventoryItemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
      return null;
    }

    $skuData = json_decode($item['sku'] ?? '{}', true);

    // The form uses 'sku' for the code, 'quantity' for the stock count.
    // The database columns are assumed to be `sku_code` and `quantity`.
    return [
      'inventory_item_id' => $item['InventoryItemID'],
      'productItemID' => $item['productItemID'],
      'sku' => $item['sku'] ?? '',
      'quantity' => $item['quantity'] ?? 0,
      'cost' => $item['cost'] ?? 0.0,
      'price' => $item['price'] ?? 0.0,
      'color' => $skuData['color'] ?? '',
      'size' => $skuData['size'] ?? '',
      'description' => $item['description'] ?? '',
      'discount_percentage' => $item['discount_percentage'] ?? 0.0,
      'status' => $item['status'] ?? 1,
      'is_on_discount' => $item['is_on_discount'] ?? 0,
      'tax_rate' => $item['tax_rate'] ?? 0.0,
      'barcode' => $item['barcode'] ?? ''
    ];
  }

  public function updateVariant(array $data): bool
  {
    try {
      $this->pdo->beginTransaction();

      // 1. Fetch the current SKU JSON from the database.
      $stmt = $this->pdo->prepare("SELECT sku FROM inventoryitem WHERE inventoryitemID = ?");
      $stmt->execute([$data['inventory_item_id']]);
      $currentSkuJson = $stmt->fetchColumn();

      // 2. Decode it, update color and size, and re-encode.
      $skuArray = $currentSkuJson ? json_decode($currentSkuJson, true) : [];
      $skuArray['color'] = $data['color'];
      $skuArray['size'] = $data['size'];
      $updatedSkuJson = json_encode($skuArray);

      // 3. Prepare and execute the UPDATE statement for the inventory item.
      // Note: We are assuming column names like `sku_code`, `quantity`, `price`.
      // These might need adjustment to match your exact database schema.
      $sql = "UPDATE inventoryitem SET 
                sku = :sku_json, 
                quantity = :quantity, 
                cost = :cost, 
                price = :price,
                description = :description,
                discount_percentage = :discount_percentage,
                status = :status,
                is_on_discount = :is_on_discount,
                tax_rate = :tax_rate,
                barcode = :barcode
            WHERE inventoryitemID = :inventory_item_id";


      $stmt = $this->pdo->prepare($sql);

      $success = $stmt->execute([

        ':quantity' => $data['quantity'],
        ':cost' => $data['cost'],
        ':price' => $data['price'],
        ':sku_json' => $updatedSkuJson,
        ':inventory_item_id' => $data['inventory_item_id'],
        ':description' => $data['description'],
        ':discount_percentage' => $data['discount_percentage'],
        ':status' => $data['status'],
        ':is_on_discount' => $data['is_on_discount'],
        ':tax_rate' => $data['tax_rate'],
        ':barcode' => $data['barcode']
      ]);


      if ($success) {
        $this->pdo->commit();
        return true;
      } else {
        if ($this->pdo->inTransaction()) {
          $this->pdo->rollBack();
        }
        return false;
      }
    } catch (PDOException $e) {
      if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
      }
      // In a real application, you would log this error more robustly.
      error_log('Variation update failed: ' . $e->getMessage());
      return false;
    }
  }

}

?>