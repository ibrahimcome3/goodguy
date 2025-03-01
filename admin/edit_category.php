<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit_category') {
    editCategory($mysqli, $_POST);
    exit; // Stop further processing after handling AJAX
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_category') {
    deleteCategory($mysqli, $_POST);
    exit; // Stop further processing after handling AJAX
}

function editCategory($mysqli, $postData)
{
    $categoryId = $postData['category_id'];
    $newName = $postData['name'];

    $sql = "UPDATE categories SET name = ? WHERE category_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("si", $newName, $categoryId);

    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Category updated successfully!'];
    } else {
        $response = ['success' => false, 'message' => 'Error updating category: ' . $stmt->error];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
}

function deleteCategory($mysqli, $postData)
{
    $categoryId = $postData['category_id'];
    $sql = "SELECT * FROM categories WHERE parent_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $categoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    $children = $result->num_rows;
    if ($children > 0) {
        $response = ['success' => false, 'message' => 'Cannot delete a category with child categories!'];
    } else {
        $sql = "DELETE FROM categories WHERE category_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $categoryId);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Category deleted successfully!'];
        } else {
            $response = ['success' => false, 'message' => 'Error deleting category: ' . $stmt->error];
        }
    }
    header('Content-Type: application/json');
    echo json_encode($response);
}