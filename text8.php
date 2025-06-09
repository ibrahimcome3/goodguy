<?php
/**
 * AJAX handler for fetching related products with pagination.
 */

require_once "includes.php"; // Go up one directory to find includes.php in the root
// This should provide $pdo and autoload classes.

// Ensure $pdo is available from includes.php
if (!isset($pdo) || !$pdo instanceof PDO) {
  error_log("PDO connection not available in text8.php");
  echo json_encode(['error' => 'Database connection error.']); // Or a more user-friendly HTML error
  exit;
}

try {
  $product_obj = new ProductItem($pdo);
  $promotion = new Promotion($pdo);
  // $Orvi = new Review($pdo); // Instantiated but not used in the provided logic
  // $category_obj = new Category($pdo); // Instantiated but not used
  // $variation_obj = new Variation($pdo); // Instantiated but not used
} catch (Throwable $e) { // Catch any error during class instantiation
  error_log("Error instantiating classes in text8.php: " . $e->getMessage());
  echo json_encode(['error' => 'Server configuration error.']);
  exit;
}

$records_per_page = 6;
$page = '';
$output = '';
if (isset($_POST["page"])) {
  $page = $_POST["page"];
} else {
  $page = 1;
}

// Validate and sanitize POST inputs
$category_id_filter = isset($_POST['cat']) ? filter_var($_POST['cat'], FILTER_VALIDATE_INT) : null;
$current_product_id_exclude = isset($_POST['pid']) ? filter_var($_POST['pid'], FILTER_VALIDATE_INT) : null;

if ($category_id_filter === null || $current_product_id_exclude === null) {
  echo "Required parameters missing or invalid."; // Or return JSON error
  exit;
}

try {
  // Query for count
  $sql_count = "SELECT count(*) AS counts 
                  FROM `inventoryitem` ii
                  LEFT JOIN `productitem` pi ON pi.productID = ii.productItemID
                  WHERE pi.category = :category_id AND ii.InventoryItemID != :exclude_product_id";
  $stmt_count = $pdo->prepare($sql_count);
  $stmt_count->bindParam(':category_id', $category_id_filter, PDO::PARAM_INT);
  $stmt_count->bindParam(':exclude_product_id', $current_product_id_exclude, PDO::PARAM_INT);
  $stmt_count->execute();
  $total_records_row = $stmt_count->fetch(PDO::FETCH_ASSOC);
  $total_records = $total_records_row ? (int) $total_records_row['counts'] : 0;
  $total_pages = ceil($total_records / $records_per_page);

  $output = '';
  if ($total_records > 0) {
    $starting_limit = ($page - 1) * $records_per_page;

    // Query for records
    $sql_records = "SELECT ii.*, pi.product_name as product_description
                        FROM `inventoryitem` ii
                        LEFT JOIN `productitem` pi ON pi.productID = ii.productItemID
                        WHERE pi.category = :category_id AND ii.InventoryItemID != :exclude_product_id
                        ORDER BY ii.InventoryItemID DESC -- Or some other relevant order
                        LIMIT :limit OFFSET :offset";
    $stmt_records = $pdo->prepare($sql_records);
    $stmt_records->bindParam(':category_id', $category_id_filter, PDO::PARAM_INT);
    $stmt_records->bindParam(':exclude_product_id', $current_product_id_exclude, PDO::PARAM_INT);
    $stmt_records->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt_records->bindParam(':offset', $starting_limit, PDO::PARAM_INT);
    $stmt_records->execute();

    while ($row = $stmt_records->fetch(PDO::FETCH_ASSOC)) {
      // Use htmlspecialchars for all dynamic output
      $item_id_safe = htmlspecialchars($row['InventoryItemID']);
      $description_safe = htmlspecialchars($row['product_description'] ?? $row['description'] ?? 'N/A'); // Prioritize productitem.description
      $cost_safe = htmlspecialchars(number_format((float) ($row['product_cost'] ?? $row['cost'] ?? 0), 2));

      // Assuming get_image_600_199 now uses PDO or is part of ProductItem that uses PDO
      // The second parameter to get_image_600_199 was $_id_of_what_get_image, which is $current_product_id_exclude.
      // It's unclear what its purpose was in that context. If it's not needed, remove it.
      // For now, I'll keep it, assuming it has a specific purpose within that method.
      $image_src_safe = htmlspecialchars($product_obj->get_image_600_199($row['InventoryItemID'], $current_product_id_exclude));

      $output .= '<div class="product product-sm">
                          <figure class="product-media">
                          <a href="product-detail.php?itemid=' . $item_id_safe . '">
                          <img src="' . $image_src_safe . '" alt="Product image" class="product-image">
                          </a>
                          </figure>';

      $output .= '<div class="product-body">
                          <h5 class="product-title"><a class="truncate" href="product-detail.php?itemid=' . $item_id_safe . '">' . $description_safe . '</a></h5>
                          <div class="product-price">';
      if ($promotion->check_if_item_is_in_inventory_promotion($row['InventoryItemID'])) {
        $promo_price_safe = htmlspecialchars(number_format((float) $promotion->get_promoPrice_price($row['InventoryItemID']), 2));
        $regular_price_safe = htmlspecialchars(number_format((float) $promotion->get_regular_price($row['InventoryItemID']), 2));
        $output .= '<span class="product-price" style="margin-bottom: 0px;">&#8358;' . $promo_price_safe . '&nbsp;</span><div>';
        $output .= '<span class="old-price" style="font-size: 12px; text-decoration: line-through;"> Was N' . $regular_price_safe . '</span></div>';
      } else {
        $output .= '<div class="product-price"> &#8358; ' . $cost_safe . '</div>';
      }
      $output .= '</div></div></div>';
    }

    // Pagination links (kept the hidden style as in original)
    $output .= '<div style="display: block;  visibility: hidden;">'; // This div is hidden, so pagination links won't be visible
    if ($page < $total_pages) {
      $output .= '<a href="#" class="next-link pagination_link" id="' . ($page + 1) . '"><span>next</span></a>';
    } else {
      $output .= "<a href='javascript:void(0)' aria-label='Next'><span aria-hidden='true'>next</span></a>";
    }
    for ($i = 1; $i <= $total_pages; $i++) {
      $output .= "<span class='pagination_link' style='cursor:pointer; padding:6px; border:1px solid #ccc;' id='" . $i . "'>" . $i . "</span>";
    }
    if ($page > 1) {
      $output .= '<a href="#" class="prev-link pagination_link" id="' . ($page - 1) . '" aria-label="Previous"><span>previous</span></a>';
    } else {
      $output .= "<a href='javascript:void(0)' aria-label='Previous'><span aria-hidden='true'>previous</span></a>";
    }
    $output .= '</div>';
  } else {
    $output = "No related products found.";
  }

  echo $output;

} catch (PDOException $e) {
  error_log("PDO Error in text8.php: " . $e->getMessage());
  echo "Error fetching related products. Please try again later."; // User-friendly error
} catch (Throwable $e) { // Catch other general errors
  error_log("General Error in text8.php: " . $e->getMessage());
  echo "An unexpected error occurred. Please try again later.";
}

// Helper function getImage (refactored to use PDO and accept $pdo as parameter)
// This function should ideally be part of your ProductItem or a dedicated Image utility class.
function getImage(PDO $pdo, $inventoryItemId)
{
  try {
    $sql = "SELECT image_path FROM inventory_item_image WHERE inventory_item_id = :item_id AND is_primary = 1 LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':item_id', $inventoryItemId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['image_path'] : "assets/images/products/product-grey.jpg"; // Default image
  } catch (PDOException $e) {
    error_log("Error in getImage function: " . $e->getMessage());
    return "assets/images/products/product-grey.jpg"; // Default image on error
  }
}

// The getrelatedproducts function was defined but not used in the main logic.
// If needed, it should also be refactored to use PDO.
/*
function getrelatedproducts(PDO $pdo, $categoryId, $excludeProductId) {
    // ... implementation using PDO ...
}
*/
?>