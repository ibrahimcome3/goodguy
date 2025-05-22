<?php
class Review
{
  private $pdo;

  public function __construct(PDO $pdo)
  {
    $this->pdo = $pdo;
  }

  function get_total_review_of_product($id)
  {
    $pdo = $this->pdo;
    $sql = "select count(*) as total_reviews from product_review where `inventory_item` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row['total_reviews'];
  }

  function get_reviews_by_inventory_item($id)
  {
    $pdo = $this->pdo;
    $sql = "select * from product_review left join customer on customer.customer_id = product_review.user_id where `inventory_item` = $id";
    $stmt = $pdo->query($sql);
    return $stmt;
    // return $stmt->fetch();
  }

  function get_reviews_by_inventory_item_with_limit($id, $page, $records_per_page)
  {
    $pdo = $this->pdo;
    $sql = "select * from product_review left join customer on customer.customer_id = product_review.user_id where `inventory_item` = $id limit " . (($page - 1) * $records_per_page) . ', ' . $records_per_page;
    $stmt = $pdo->query($sql);
    return $stmt;
    // return $stmt->fetch();
  }

  function get_total_count_reviewed_item($id)
  {
    $pdo = $this->pdo;
    $result = array();
    $sql = "SELECT count(*) as x from product_review where `inventory_item` in ($id)";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch();
    return $row['x'];

  }


  public function addReview(int $inventory_item_id, int $customer_id, int $rating, string $comment, string $review_title = '', string $status = 'approved'): bool
  {
    // Optional: Prevent duplicate reviews by the same user for the same product
    if ($this->hasUserReviewedProduct($inventory_item_id, $customer_id)) {
      $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'You have already reviewed this product.'];
      return false;
    }

    $sql = "INSERT INTO reviews (inventory_item_id, customer_id, rating, review_title, comment, review_date, status)
              VALUES (:inventory_item_id, :customer_id, :rating, :review_title, :comment, NOW(), :status)";
    try {
      $stmt = $this->pdo->prepare($sql);
      return $stmt->execute([
        ':inventory_item_id' => $inventory_item_id,
        ':customer_id' => $customer_id,
        ':rating' => $rating,
        ':review_title' => $review_title,
        ':comment' => $comment,
        ':status' => $status
      ]);
    } catch (PDOException $e) {
      error_log("Error adding review: " . $e->getMessage());
      return false;
    }
  }

  /**
   * Fetches approved reviews for a given product, along with customer names.
   *
   * @param int $inventory_item_id
   * @return array List of reviews.
   */
  public function getReviewsByProduct(int $inventory_item_id): array
  {
    $sql = "SELECT r.*, c.customer_fname, c.customer_lname
              FROM reviews r
              JOIN customer c ON r.customer_id = c.customer_id
              WHERE r.inventory_item_id = :inventory_item_id AND r.status = 'approved'
              ORDER BY r.review_date DESC";
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute([':inventory_item_id' => $inventory_item_id]);
      return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      error_log("Error fetching reviews: " . $e->getMessage());
      return [];
    }
  }

  /**
   * Checks if a user has already reviewed a specific product.
   *
   * @param int $inventory_item_id
   * @param int $customer_id
   * @return bool True if already reviewed, false otherwise.
   */
  public function hasUserReviewedProduct(int $inventory_item_id, int $customer_id): bool
  {
    $sql = "SELECT COUNT(*) FROM reviews
              WHERE inventory_item_id = :inventory_item_id AND customer_id = :customer_id";
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->execute([':inventory_item_id' => $inventory_item_id, ':customer_id' => $customer_id]);
      return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
      error_log("Error checking if user reviewed product: " . $e->getMessage());
      return false;
    }
  }


  /**
   * Gets the average rating for a product as a percentage (for star display).
   * Assumes rating is 1-5.
   *
   * @param int $inventory_item_id
   * @return float Percentage value (0-100).
   */
  public function get_rating_(int $inventory_item_id): float
  {
    $sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE inventory_item_id = :item_id AND status = 'approved'";
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindParam(':item_id', $inventory_item_id, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($result && $result['avg_rating'] !== null) {
        return ((float) $result['avg_rating'] / 5) * 100;
      }
      return 0;
    } catch (PDOException $e) {
      error_log("Error in get_rating_: " . $e->getMessage());
      return 0;
    }
  }

  /**
   * Gets the total number of approved reviews for a product.
   *
   * @param int $inventory_item_id
   * @return int Count of reviews.
   */
  public function get_rating_review_number(int $inventory_item_id): int
  {
    $sql = "SELECT COUNT(*) as review_count FROM reviews WHERE inventory_item_id = :item_id AND status = 'approved'";
    try {
      $stmt = $this->pdo->prepare($sql);
      $stmt->bindParam(':item_id', $inventory_item_id, PDO::PARAM_INT);
      $stmt->execute();
      $result = $stmt->fetch(PDO::FETCH_ASSOC);
      return $result ? (int) $result['review_count'] : 0;
    } catch (PDOException $e) {
      error_log("Error in get_rating_review_number: " . $e->getMessage());
      return 0;
    }
  }

}


?>