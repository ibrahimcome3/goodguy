<?php
include "../conn.php";
require_once '../class/Conn.php';
require_once '../class/Category.php';


try {

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Get form data
        $productName = $_POST["product_name"];
        $category = $_POST["category"];
        $vendor = $_POST["vendor"];
        $brand = $_POST["brand"];
        $productInformation = $_POST["product_information"];
        $additionalInformation = $_POST["additional_information"];
        $shippingReturns = $_POST["shipping_returns"];
        $userId = 1; // Replace with actual user ID

        // Image handling
        $targetDir = "uploads/"; // Directory to store uploaded images
        $targetFile = $targetDir . basename($_FILES["product_image"]["name"]);
        $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
        $uploadOk = 1;

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["product_image"]["tmp_name"]);
        if ($check !== false) {
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size
        if ($_FILES["product_image"]["size"] > 500000) {
            echo "Sorry, your file is too large.";
            $uploadOk = 0;
        }
        // Allow certain file formats
        if (
            $imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
            && $imageFileType != "gif"
        ) {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }
        // Check if $uploadOk is set to 0 by an error
        if ($uploadOk == 0) {
            echo "Sorry, your file was not uploaded.";
            // if everything is ok, try to upload file
        } else {
            if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $targetFile)) {
                //echo "The file ". htmlspecialchars( basename( $_FILES["product_image"]["name"])). " has been uploaded.";
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }


        // Insert into database
        $sql = "INSERT INTO productitem (product_name, category, date_added, vendor, brand, product_information, additional_information, shipping_returns, vendor_id) 
                VALUES (:product_name, :category, NOW(), :vendor, :brand, :product_information, :additional_information, :shipping_returns, :user_id)";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':product_name', $productName);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':vendor', $vendor);
        $stmt->bindParam(':brand', $brand);
        $stmt->bindParam(':product_information', $productInformation);
        $stmt->bindParam(':additional_information', $additionalInformation);
        $stmt->bindParam(':shipping_returns', $shippingReturns);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        echo "New product added successfully";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}

$conn = null;
?>