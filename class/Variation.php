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
  public function getVariantsByProductId(int $productId): array
  {
    // First get all variants and their primary image
    $sql = "SELECT v.variant_id, v.name, vi.image_path AS thumbnail
            FROM variants v
            LEFT JOIN variant_images vi 
              ON v.variant_id = vi.variant_id AND vi.is_primary = 1
            WHERE v.product_id = ?
            ORDER BY v.name ASC";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([$productId]);
    $variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Then fetch all images for each variant
    foreach ($variants as &$variant) {
      $sqlImgs = "SELECT image_path 
                    FROM variant_images 
                    WHERE variant_id = ? 
                    ORDER BY sort_order ASC";
      $imgStmt = $this->pdo->prepare($sqlImgs);
      $imgStmt->execute([$variant['variant_id']]);
      $variant['images'] = $imgStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
      // fallback thumbnail
      $variant['thumbnail'] = $variant['thumbnail'] ?? '../assets/img/products/default-variant.png';
    }
    return $variants;
  }
}

?>