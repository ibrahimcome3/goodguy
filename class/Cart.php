<?php

class Cart extends Promotion
{
  public $pdo;
  private $promotion;

  public function __construct($pdo, $promotion)
  {
    $this->pdo = $pdo;
    $this->promotion = $promotion;
  }


  public function getCartItemCount()
  {
    // Assuming your getCartDetails() method returns an array of all cart items
    // where each element represents a unique product line.
    $cartItems = $this->getCartDetails();
    return count($cartItems);
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
    $columns = "ii.InventoryItemID, ii.description, ii.cost, iii.image_path"; // ii for inventoryitem, iii for inventory_item_image
    $placeholders = implode(',', array_fill(0, count($inventoryItemIds), '?'));
    $sql = "SELECT {$columns} 
            FROM inventoryitem ii
            LEFT JOIN inventory_item_image iii ON ii.InventoryItemID = iii.inventory_item_id AND iii.is_primary = 1
            WHERE ii.InventoryItemID IN ({$placeholders})";


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

  public function calculateCartTotal(array $cartItems): float
  {
    $total = 0.0;
    if (empty($cartItems)) {
      return $total;
    }
    foreach ($cartItems as $item) {

      // Ensure 'cost' and 'quantity' keys exist and are numeric
      $cost = isset($item['cost']) && is_numeric($item['cost']) ? (float) $item['cost'] : 0.0;

      $quantity = isset($item['quantity']) && is_numeric($item['quantity']) ? (int) $item['quantity'] : 0;
      $total += $cost * $quantity;
    }

    return $total;
  }


  // c:\wamp64\www\goodguy\class\Cart.php
// ... (other Cart class code) ...

  /**
   * Adds an item to the cart or updates its quantity if it already exists.
   * Considers product ID, size, and color for uniqueness.
   *
   * @param int $productId The ID of the inventory item.
   * @param int $quantity The quantity to add.
   * @param string|null $size The size of the product (optional).
   * @param string|null $color The color of the product (optional).
   * @return bool True on success, false on failure (e.g., product not found, invalid quantity).
   */
  public function addItem(int $productId, int $quantity, $size = null, $color = null): bool
  {
    if ($quantity <= 0) {
      return false; // Invalid quantity
    }

    // Optional: You could add validation here to check if $productId actually exists
    // in your inventoryitem table using $this->pdo if desired.
    // For example:
    // $stmt = $this->pdo->prepare("SELECT InventoryItemID FROM inventoryitem WHERE InventoryItemID = ?");
    // $stmt->execute([$productId]);
    // if (!$stmt->fetch()) {
    //     error_log("Attempted to add non-existent product ID to cart: " . $productId);
    //     return false; // Product not found
    // }

    if (!isset($_SESSION['cart'])) {
      $_SESSION['cart'] = [];
    }

    foreach ($_SESSION['cart'] as $key => &$item) { // Note the & to modify the array item by reference
      if (
        $item['inventory_product_id'] == $productId &&
        (isset($item['size']) ? $item['size'] : null) == $size && // Compare size, handling nulls
        (isset($item['color']) ? $item['color'] : null) == $color
      ) { // Compare color, handling nulls
        $item['quantity'] += $quantity;
        return true; // Item found and quantity updated
      }
    }
    // If item not found with same variations, add as new
    $_SESSION['cart'][] = [
      'inventory_product_id' => $productId,
      'quantity' => $quantity,
      'size' => $size,
      'color' => $color
    ];
    return true;
  }

  // ... (rest of Cart class) ...



}




?>