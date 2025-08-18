<?php
// No longer need to require the connection file here.
// It should be provided via the constructor.

class Brand
{
    private $pdo;
    private $uploadDir = '../brand/';

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        // Ensure upload directory exists
        if (!is_dir($this->uploadDir) && !mkdir($this->uploadDir, 0777, true)) {
            die("Failed to create upload directory: " . $this->uploadDir);
        }
    }

    public function getBrandById(int $brandId)
    {
        $sql = "SELECT * FROM brand WHERE brandID = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$brandId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBrandByName(string $brandName)
    {
        $sql = "SELECT * FROM brand WHERE Name = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$brandName]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllBrands(): array
    {
        $sql = "SELECT * FROM brand ORDER BY Name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adds a new brand to the database
     *
     * @param string $name The brand name
     * @param string $description The brand description (optional)
     * @param string $websiteURL The brand's website URL (optional)
     * @param string $logo The brand logo filename (optional)
     * @return int|false The new brand ID if successful, false otherwise
     */
    public function addBrand(string $name, string $description = '', string $websiteURL = '', string $logo = '')
    {
        // Get the current user's ID from the session (if available)
        $brandOwner = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;
        $adminId = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null;

        try {
            $sql = "INSERT INTO brand (Name, brand_description, websiteURL, brand_logo, brand_owner, admin_id, Dateadded) 
                    VALUES (:name, :brand_description, :websiteURL, :brand_logo, :brand_owner, :admin_id, NOW())";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':brand_description' => $description,
                ':websiteURL' => $websiteURL,
                ':brand_logo' => $logo,
                ':brand_owner' => $brandOwner,
                ':admin_id' => $adminId
            ]);

            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error adding brand: " . $e->getMessage());
            return false;
        }
    }

    public function updateBrand(int $brandId, array $brandData): bool
    {
        $brandOwner = isset($_SESSION['uid']) ? $_SESSION['uid'] : null;
        if ($brandOwner === null) {
            return false;
        }

        $params = [
            'name' => $brandData['name'],
            'brand_description' => $brandData['brand_description'],
            'websiteURL' => $brandData['websiteURL'],
            'brand_owner' => $brandOwner,
            'brandID' => $brandId
        ];

        $brandLogoQuery = "";
        if (!empty($brandData['brand_logo'])) {
            $brandLogoQuery = ", brand_logo = :brand_logo";
            $params['brand_logo'] = $brandData['brand_logo'];
        }

        $sql = "UPDATE brand SET 
                    Name = :name, 
                    brand_description = :brand_description, 
                    websiteURL = :websiteURL
                    {$brandLogoQuery}, 
                    brand_owner = :brand_owner 
                WHERE brandID = :brandID";

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteBrand(int $brandId): bool
    {
        $this->pdo->beginTransaction();
        try {
            // Check if the brand is used by any product
            $productSql = "SELECT productID FROM productitem WHERE brand_id = ?";
            $productStmt = $this->pdo->prepare($productSql);
            $productStmt->execute([$brandId]);

            if ($productStmt->fetch()) {
                throw new Exception("Cannot delete brand because it is associated with products.");
            }

            // Get the brand information to delete image
            $brand = $this->getBrandById($brandId);
            if ($brand && !empty($brand['brand_logo'])) {
                $brandLogoPath = $this->uploadDir . $brand['brand_logo'];
                if (file_exists($brandLogoPath)) {
                    if (!unlink($brandLogoPath)) {
                        // Non-fatal, so we can log it but still proceed.
                        error_log("Could not delete brand logo file: " . $brandLogoPath);
                    }
                }
            }

            $sql = "DELETE FROM brand WHERE brandID = ?";
            $stmt = $this->pdo->prepare($sql);
            if (!$stmt->execute([$brandId])) {
                throw new Exception("Error deleting brand from database.");
            }
            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // In a real app, you'd log the error message.
            // For the user, you might just return false.
            error_log($e->getMessage());
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