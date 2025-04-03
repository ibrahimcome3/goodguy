<?php
require_once '../db_connection/conn.php';

class Brand
{
    private $uploadDir = '../brand/';

    public function __construct()
    {
        // Ensure upload directory exists
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0777, true)) {
            die("Failed to create upload directory: " . $this->uploadDir);
        }
    }

    public function getBrandById($mysqli, $brandId)
    {
        $sql = "SELECT * FROM brand WHERE brandID = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $brandId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getBrandByName($mysqli, $brandName)
    {
        $sql = "SELECT * FROM brand WHERE Name = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $brandName);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }


    public function getAllBrands($mysqli)
    {
        $sql = "SELECT * FROM brand";
        $result = $mysqli->query($sql);
        $brands = [];
        while ($row = $result->fetch_assoc()) {
            $brands[] = $row;
        }
        return $brands;
    }

    public function addBrand($mysqli, $brandData)
    {
        // Get the current user's ID from the session (make sure session_start() is called)
        $brandOwner = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;

        //If there is no user id return false
        if ($brandOwner === null) {
            return false;
        }

        $sql = "INSERT INTO brand (Name, brand_description, brand_logo, websiteURL, brand_owner) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ssssi", $brandData['name'], $brandData['brand_description'], $brandData['brand_logo'], $brandData['websiteURL'], $brandOwner);
        return $stmt->execute();
    }
    public function updateBrand($mysqli, $brandId, $brandData)
    {
        // Get the current user's ID from the session (make sure session_start() is called)
        $brandOwner = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;

        //If there is no user id return false
        if ($brandOwner === null) {
            return false;
        }

        $brandLogoQuery = "";
        $paramTypes = "sss";
        $params = [$brandData['name'], $brandData['brand_description'], $brandData['websiteURL']];
        if (!empty($brandData['brand_logo'])) {
            $brandLogoQuery = ", brand_logo = ?";
            $paramTypes .= "s";
            $params[] = $brandData['brand_logo'];
        }
        // Add brandOwner to the parameters
        $params[] = $brandOwner;
        $params[] = $brandId;
        $paramTypes .= "ii";
        $sql = "UPDATE brand SET name = ?, brand_description = ?, websiteURL = ? {$brandLogoQuery}, brand_owner = ? WHERE brandID = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($paramTypes, ...$params);
        return $stmt->execute();
    }


    public function deleteBrand($mysqli, $brandId)
    {
        $mysqli->begin_transaction();
        try {
            // Check if the brand is used by any product
            $productSql = "SELECT productID FROM productitem WHERE brand_id = ?";
            $productStmt = $mysqli->prepare($productSql);
            $productStmt->bind_param("i", $brandId);
            $productStmt->execute();
            $productResult = $productStmt->get_result();

            if ($productResult->num_rows > 0) {
                throw new Exception("Cannot delete brand because it is associated with products.");
            }

            //get the brand information to delete image
            $brand = $this->getBrandById($mysqli, $brandId);
            $brandLogo = $brand['brand_logo'];
            //delete the file if any
            if (!empty($brandLogo)) {
                $brandLogoPath = "../brand/" . $brandLogo;
                if (file_exists($brandLogoPath)) {
                    if (!unlink($brandLogoPath)) {
                        throw new Exception("Error deleting brand logo.");
                    }
                } else {
                    error_log("Image file not found: " . $brandLogoPath);
                }
            }

            $sql = "DELETE FROM brand WHERE brandID = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $brandId);
            if (!$stmt->execute()) {
                throw new Exception("Error deleting brand.");
            }
            $mysqli->commit();
            return true;

        } catch (Exception $e) {
            $mysqli->rollback();
            throw new Exception($e->getMessage());
            return false;
        }
    }
    public function validatePost($field, $type, $required, $minLength = null, $maxLength = null)
    {
        if (!isset($_POST[$field]) && $required) {
            return ['error' => "$field is required"];
        }

        $value = isset($_POST[$field]) ? $_POST[$field] : '';

        switch ($type) {
            case 'string':
                if (!is_string($value)) {
                    return ['error' => "$field must be a string"];
                }
                break;
            case 'integer':
                if (!is_numeric($value) || (int) $value != $value) {
                    return ['error' => "$field must be an integer"];
                }
                $value = (int) $value;
                break;
            case 'float':
                if (!is_numeric($value)) {
                    return ['error' => "$field must be a number"];
                }
                $value = (float) $value;
                break;
            default:
                return ['error' => "Invalid type specified for $field"];
        }

        //Check min and max lenght
        if ($minLength !== null && strlen($value) < $minLength) {
            return ['error' => "$field must be at least $minLength characters long"];
        }
        if ($maxLength !== null && strlen($value) > $maxLength) {
            return ['error' => "$field must be no more than $maxLength characters long"];
        }

        return ['value' => $value];
    }

    public function moveBrandLogo($files)
    {
        if (isset($files['brand_logo']) && $files['brand_logo']['error'] === UPLOAD_ERR_OK) {
            $tempName = $files['brand_logo']['tmp_name'];
            $fileName = basename($files['brand_logo']['name']);
            $exploded = explode('.', $fileName);
            $ext = end($exploded);
            $uniqueFileName = time() . uniqid() . '.' . $ext;
            $targetPath = $this->uploadDir . $uniqueFileName;
            if ($this->resizeAndCenterImage($tempName, $targetPath, 600, 600)) {
                return $uniqueFileName;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    private function resizeAndCenterImage($sourcePath, $destinationPath, $targetWidth, $targetHeight)
    {
        // Get image dimensions and create image resource
        list($originalWidth, $originalHeight, $imageType) = getimagesize($sourcePath);
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($sourcePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($sourcePath);
                break;
            default:
                return false; // Unsupported image type
        }

        if (!$image) {
            return false; // Unable to create image resource
        }

        // Calculate aspect ratio and dimensions for resizing
        $aspectRatio = $originalWidth / $originalHeight;
        if ($targetWidth / $targetHeight > $aspectRatio) {
            $width = $targetHeight * $aspectRatio;
            $height = $targetHeight;
        } else {
            $width = $targetWidth;
            $height = $targetWidth / $aspectRatio;
        }

        // Create a blank canvas with white background
        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        // Calculate positioning for centering
        $x = ($targetWidth - $width) / 2;
        $y = ($targetHeight - $height) / 2;

        // Copy and resize the original image onto the canvas
        imagecopyresampled($canvas, $image, $x, $y, 0, 0, $width, $height, $originalWidth, $originalHeight);

        // Save the result as JPEG (you can change to PNG if needed)
        $success = imagejpeg($canvas, $destinationPath, 90); // 90 is the quality (0-100)

        // Free memory
        imagedestroy($image);
        imagedestroy($canvas);

        return $success;
    }
}
?>