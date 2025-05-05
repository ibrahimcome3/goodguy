<?php

class Cart extends Promotion
{
  private $pdo;
  private $promotion;

  public function __construct($pdo, $promotion)
  {
    $this->pdo = $pdo;
    $this->promotion = $promotion;
  }

  function get_cart_item()
  {
    if (isset($_SESSION['cart'])) {
      if (count($_SESSION['cart']) > 0) {
        if (isset($_SESSION['cart_final']) && is_array($_SESSION['cart_final'])) {
          //echo "yes";
          if (array_key_exists($product_id_cost, $_SESSION['cart_final'])) {

            // Product exists in cart so just update the quanity
            if ($this->check_if_item_is_in_inventory_promotion($product_id_cost)) {
              $_SESSION['cart_final'][$product_id_cost] = $this->get_promoPrice_price($row['InventoryItemID']) * $products_in_cart[$row['InventoryItemID']];
            } else {
              $_SESSION['cart_final'][$product_id_cost] = $row['cost'] * $products_in_cart[$row['InventoryItemID']];
            }
          } else {
            // Product is not in cart so add it
            if ($this->check_if_item_is_in_inventory_promotion($product_id_cost)) {
              $_SESSION['cart_final'][$product_id_cost] = $this->get_promoPrice_price($row['InventoryItemID']) * $products_in_cart[$row['InventoryItemID']];
            } else {
              $_SESSION['cart_final'][$product_id_cost] = $row['cost'] * $products_in_cart[$row['InventoryItemID']];
            }
          }
        }
      }

    }

  }


  function get_reviews_by_inventory_item($id)
  {
    $pdo = $this->dbc;
    $sql = "select * from product_review left join customer on customer.customer_id = product_review.user_id where `inventory_item` = $id";
    $stmt = $pdo->query($sql);
    return $stmt;
    // return $stmt->fetch();
  }

  function get_right_inventroy_item_neede($arryofproperties)
  {
    $pdo = $this->dbc;
    $str = '';
    $counter = 0;
    foreach ($arryofproperties as $key => $val) {
      $str .= "JSON_EXTRACT(sku, '$." . $key . "') =  '" . $val . "'";
      if ($counter != count($arryofproperties) - 1) {
        $str .= "  and  ";
      }

      $counter = $counter + 1;


    }

    $sql = "SELECT InventoryItemID, barcode, sku, JSON_CONTAINS_PATH(sku, 'all', '$.size', '$.color') as size_color from inventoryitem   WHERE  ";
    $sql = $sql . " " . $str;
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch();
    return $row['InventoryItemID'];


  }






  public function getCartItems()
  {
    if (!isset($_SESSION['cart']) || count($_SESSION['cart']) == 0) {
      return [];
    }

    $cartItems = $_SESSION['cart'];
    $inventoryItemIds = array_column($cartItems, 'inventory_product_id');
    $placeholders = implode(',', array_fill(0, count($inventoryItemIds), '?'));

    $sql = "SELECT * FROM inventoryitem WHERE InventoryItemID IN ($placeholders)";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute($inventoryItemIds);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cartData = [];
    foreach ($products as $product) {
      $cartData[$product['InventoryItemID']] = [
        'product' => $product,
        'quantity' => 0,
        'cost' => $product['cost'],
      ];
      foreach ($cartItems as $item) {
        if ($item['inventory_product_id'] == $product['InventoryItemID']) {
          $cartData[$product['InventoryItemID']]['quantity'] += $item['quantity'];
        }
      }
      if ($this->promotion->check_if_item_is_in_inventory_promotion($product['InventoryItemID'])) {
        $cartData[$product['InventoryItemID']]['cost'] = $this->promotion->get_promoPrice_price($product['InventoryItemID']);
      }
    }

    return $cartData;
  }


  public function getCartDetails(): array
  {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
      return [];
    }

    // 1. Create lookup map for quantities and collect unique IDs
    $itemQuantities = [];
    $inventoryItemIds = [];
    foreach ($_SESSION['cart'] as $item) {
      if (!isset($item['inventory_product_id']) || !isset($item['quantity']))
        continue;
      $id = (int) $item['inventory_product_id'];
      $qty = (int) $item['quantity'];
      if ($id > 0 && $qty > 0) {
        $itemQuantities[$id] = ($itemQuantities[$id] ?? 0) + $qty;
        $inventoryItemIds[$id] = $id; // Use ID as key for uniqueness
      }
    }

    if (empty($inventoryItemIds))
      return [];

    // 2. Fetch product data efficiently
    // *** SELECT ONLY NEEDED COLUMNS, including image path if available ***
    // *** Adjust 'image_path' column name if different ***
    $columns = "InventoryItemID, description, cost, image_path"; // Add other needed columns
    $placeholders = implode(',', array_fill(0, count($inventoryItemIds), '?'));
    $sql = "SELECT {$columns} FROM inventoryitem WHERE InventoryItemID IN ($placeholders)";

    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute(array_values($inventoryItemIds));
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Database error fetching cart item details: " . $e->getMessage());
      return [];
    }

    // 3. Combine data, apply promotions, calculate totals
    $cartData = [];
    foreach ($products as $product) {
      $id = $product['InventoryItemID'];
      $quantity = $itemQuantities[$id] ?? 0;

      if ($quantity > 0) {
        $unitCost = $product['cost'];
        // Apply promotion if applicable
        if ($this->promotion->check_if_item_is_in_inventory_promotion($id)) {
          $unitCost = $this->promotion->get_promoPrice_price($id);
        }

        $cartData[$id] = [
          'product' => $product, // Contains ID, description, original cost, image_path, etc.
          'quantity' => $quantity,
          'cost' => $unitCost, // Unit cost after potential promotion
          'line_total' => $unitCost * $quantity
        ];
      }
    }
    return $cartData;
  }

  /**
   * Calculates the total value of items in the cart.
   *
   * @param array $cartDetails The array returned by getCartDetails().
   * @return float The total cart value.
   */
  public function calculateCartTotal(array $cartDetails): float
  {
    $total = 0.0;
    foreach ($cartDetails as $item) {
      $total += $item['line_total'];
    }
    return $total;
  }


}




?>