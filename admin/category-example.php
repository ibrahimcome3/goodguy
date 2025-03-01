<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';

$categories = getCategories(6); // Get top-level categories

function getCategories($parentId)
{
    global $mysqli;
    $categories = [];
    $sql = "SELECT * FROM categories WHERE parent_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $parentId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $categories[$row['category_id']] = [
            'name' => $row['name'],
            'children' => getCategories($row['category_id']) //Recursive call
        ];
    }
    return $categories;
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

    <form method="post" action="add_product.php">
        <div id="category-selector">
            <h3>Select Category</h3>
            <ul>
                <?php foreach ($categories as $category):
                    //var_dump($category); ?>

                    <li><?= $category['name'] ?></li>
                <?php endforeach; ?>
            </ul>

        </div>
        <div>
            <button type="button" id="add-category">Add Category</button>
        </div>
        <input type="submit" value="submit">

    </form>


    <script>
        $(document).ready(function () {
            $('#add-category').click(function () {
                let level = prompt("Enter category level (0, 1, or 2)");
                let parent = prompt("Enter the parent Category ID (0 for top level)");

                let categoryName = prompt("Enter the Category name");

                $.ajax({
                    type: "POST",
                    url: "add_category.php",
                    data: { level: level, parent: parent, name: categoryName },
                    dataType: "JSON",
                    success: function (response) {
                        if (response.success) {
                            alert(response.message);
                            location.reload(); //Refresh the page
                        } else {
                            alert(response.message);
                        }
                    },
                    error: function (xhr, status, error) {
                        alert("Error adding category. Please check your inputs.");
                    }
                });

            });
        });
    </script>

</body>

</html>