<?php
class Review extends Connn
{

  function __construct()
  {
    parent::__construct();
    $defaultTimeZone = 'UTC';
    date_default_timezone_set($defaultTimeZone);
    $this->timestamp = date('Y-m-d');
  }
  function get_total_review_of_product($id)
  {
    $pdo = $this->dbc;
    $sql = "select count(*) as total_reviews from product_review where `inventory_item` = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row['total_reviews'];
  }

  function get_reviews_by_inventory_item($id)
  {
    $pdo = $this->dbc;
    $sql = "select * from product_review left join customer on customer.customer_id = product_review.user_id where `inventory_item` = $id";
    $stmt = $pdo->query($sql);
    return $stmt;
    // return $stmt->fetch();
  }

  function get_reviews_by_inventory_item_with_limit($id, $page, $records_per_page)
  {
    $pdo = $this->dbc;
    $sql = "select * from product_review left join customer on customer.customer_id = product_review.user_id where `inventory_item` = $id limit " . (($page - 1) * $records_per_page) . ', ' . $records_per_page;
    $stmt = $pdo->query($sql);
    return $stmt;
    // return $stmt->fetch();
  }

  function get_rating_($item_id)
  {
    if (isset($item_id)) {

      $pdo = $this->dbc;
      $result = array();
      $sql = "SELECT a.rating as rating, max(a.x) as x FROM (SELECT `rating`, ROW_NUMBER() OVER(PARTITION BY `rating`) AS x from product_review where `inventory_item` in ($item_id)) as a group by a.rating;";
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if ($row) {
        while ($row = $stmt->fetch()) {
          $result[$row['rating']] = $row['x'];
        }
        $rating = 0;
        $per = 0;

        foreach ($result as $key => $value) {
          // echo $value."<br>";
          if ($key == "bad") {
            $rating = $rating + $value * 33;
          }
          if ($key == "ok") {
            $rating = $rating + $value * 29;
          }
          if ($key == "good") {
            $rating = $rating + $value * 40;
          }
          if ($key == "great") {
            $rating = $rating + $value * 124;
          }
          if ($key == "excellent") {
            $rating = $rating + $value * 252;
          }

          $per = ($rating / (252 + 124 + 40 + 29 + 33)) / 5;

        }
        return $per * 100;
      } else {
        return 0;
      }
    } else {
      return 0;
    }
  }


  function get_rating_review_number($item_id)
  {
    if (isset($item_id)) {
      $pdo = $this->dbc;
      $result = array();
      $sql = "SELECT count(*) as x from product_review where `inventory_item` in ($item_id)";
      $stmt = $pdo->query($sql);
      $row = $stmt->fetch();
      return $row['x'];
    } else {
      return 0;
    }
  }

  function get_total_count_reviewed_item($id)
  {
    $pdo = $this->dbc;
    $result = array();
    $sql = "SELECT count(*) as x from product_review where `inventory_item` in ($id)";
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch();
    return $row['x'];

  }

}


?>