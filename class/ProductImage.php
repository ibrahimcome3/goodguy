<?php

class ProductImage
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Fetches all images for a given product ID.
     *
     * @param int $productId
     * @return array
     */
    public function getImagesByProductId(int $productId): array
    {
        $stmt = $this->pdo->prepare("SELECT p_imgeid, image, product_id, image_path, date_added FROM product_images WHERE product_id = ? ORDER BY date_added DESC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adds a new image record for a product.
     *
     * @param int $productId
     * @param string $imagePath The relative path to the image file.
     * @param string $imageName The name of the image file.
     * @return bool
     */
    public function addImage(int $productId, string $imagePath, string $imageName): bool
    {
        $stmt = $this->pdo->prepare("INSERT INTO product_images (product_id, image_path, image, date_added) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$productId, $imagePath, $imageName]);
    }

    /**
     * Deletes an image from the database and the filesystem.
     *
     * @param int $imageId
     * @return bool
     */
    public function deleteImage(int $imageId): bool
    {
        // For simplicity, this example only deletes the database record.
        // In a real application, you should also fetch the image_path and delete the file from the server.
        // Example: unlink(__DIR__ . '/../' . $image['image_path']);
        $stmt = $this->pdo->prepare("DELETE FROM product_images WHERE p_imgeid = ?");
        return $stmt->execute([$imageId]);
    }

    function get_image($inventory_item_id)
    {
        // 1. Check for a dedicated thumbnail in the inventoryitem table first.
        $stmt = $this->pdo->prepare("SELECT thumbnail FROM inventoryitem WHERE InventoryItemID = ?");
        $stmt->execute([$inventory_item_id]);
        $thumbnail = $stmt->fetchColumn();

        // If a non-empty thumbnail path exists, use it.
        if (!empty($thumbnail)) {
            return $thumbnail;
        }

        // 2. If no thumbnail, fall back to the primary image in inventory_item_image.
        $stmt = $this->pdo->prepare("SELECT image_path FROM inventory_item_image WHERE inventory_item_id = ? AND `is_primary` = 1");
        $stmt->execute([$inventory_item_id]);
        $primaryImage = $stmt->fetchColumn();

        if ($primaryImage) {
            return $primaryImage;
        }

        // 3. If neither exists, return a default image.
        return "e.jpg";
    }

    /**
     * Uploads and processes a product image
     *
     * @param array $file The uploaded file data
     * @param int $inventoryItemId The inventory item ID
     * @param int $productId The product ID
     * @param string $baseDir The base directory for storage
     * @param bool $isPrimary Whether this is the primary image
     * @param int $sortOrder The sort order for gallery images
     * @return array|bool The image data on success, false on failure
     */
    public function uploadProductImage($file, $inventoryItemId, $productId, $baseDir, $isPrimary = false, $sortOrder = 0)
    {
        if (!$this->isValidImage($file)) {
            return $this->useDefaultImage($inventoryItemId, $productId, $isPrimary);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fname = time() . '_' . uniqid() . '.' . $ext;

        // Create directory structure
        $productDir = $baseDir . "/products/product-$productId";
        $imageParentDir = $productDir . "/product-$productId-image";
        $resizedImageDir = $imageParentDir . "/resized";

        if (!$this->ensureDirectoryExists($resizedImageDir)) {
            return $this->useDefaultImage($inventoryItemId, $productId, $isPrimary);
        }

        // Save the original uploaded file
        $dest = $imageParentDir . "/$fname";
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return $this->useDefaultImage($inventoryItemId, $productId, $isPrimary);
        }

        // Create resized version
        $resizedDest = $resizedImageDir . "/$fname";
        $this->resizeImage($dest, $resizedDest, 600);

        $relPath = "products/product-$productId/product-$productId-image/resized/$fname";

        // Insert into database
        $stmtImg = $this->pdo->prepare("
            INSERT INTO inventory_item_image 
            (inventory_item_id, image_name, image_path, is_primary, sort_order, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtImg->execute([$inventoryItemId, $fname, $relPath, $isPrimary ? 1 : 0, $sortOrder]);

        // Update product's primary image if this is the primary
        if ($isPrimary) {
            $this->pdo->prepare("UPDATE productitem SET primary_image = ? WHERE productID = ?")
                ->execute([$fname, $productId]);
        }

        return [
            'name' => $fname,
            'path' => $relPath,
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder
        ];
    }

    /**
     * Uses the default image when no image is available
     *
     * @param int $inventoryItemId The inventory item ID
     * @param int $productId The product ID
     * @param bool $isPrimary Whether this is the primary image
     * @return array The default image data
     */
    public function useDefaultImage($inventoryItemId, $productId, $isPrimary = true)
    {
        $defaultImagePath = "goodguy/default.jpg";

        $stmtImg = $this->pdo->prepare("
            INSERT INTO inventory_item_image 
            (inventory_item_id, image_name, image_path, is_primary, sort_order, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmtImg->execute([$inventoryItemId, 'default.jpg', $defaultImagePath, $isPrimary ? 1 : 0, 0]);

        if ($isPrimary) {
            $this->pdo->prepare("UPDATE productitem SET primary_image = ? WHERE productID = ?")
                ->execute(['default.jpg', $productId]);
        }

        return [
            'name' => 'default.jpg',
            'path' => $defaultImagePath,
            'is_primary' => $isPrimary,
            'sort_order' => 0
        ];
    }

    /**
     * Validates an image file
     *
     * @param array $file The uploaded file data
     * @return bool Whether the file is a valid image
     */
    public function isValidImage($file)
    {
        // Check if file exists and has no errors
        if (!isset($file) || $file['error'] !== 0) {
            return false;
        }

        // Check file extension
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowedTypes)) {
            return false;
        }

        // Check MIME type if fileinfo extension is available
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            $validMimes = [
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp'
            ];

            if (!in_array($mime, $validMimes)) {
                return false;
            }
        }

        // Check file size (limit to 5MB)
        $maxFileSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxFileSize) {
            return false;
        }

        return true;
    }

    /**
     * Creates directories recursively
     *
     * @param string $path The path to create
     * @return bool Whether the directory exists or was created
     */
    private function ensureDirectoryExists($path)
    {
        if (is_dir($path)) {
            return true;
        }
        return @mkdir($path, 0755, true);
    }

    /**
     * Resizes an image to specified dimensions
     *
     * @param string $sourceImage Path to source image
     * @param string $targetImage Path to save resized image
     * @param int $maxDimension Maximum width/height
     * @return bool Whether resizing succeeded
     */
    public function resizeImage($sourceImage, $targetImage, $maxDimension = 600)
    {
        list($width, $height, $type) = getimagesize($sourceImage);

        // Calculate new dimensions while maintaining aspect ratio
        if ($width > $height) {
            if ($width <= $maxDimension) {
                // No need to resize
                return copy($sourceImage, $targetImage);
            }
            $newWidth = $maxDimension;
            $newHeight = round(($height / $width) * $maxDimension);
        } else {
            if ($height <= $maxDimension) {
                // No need to resize
                return copy($sourceImage, $targetImage);
            }
            $newHeight = $maxDimension;
            $newWidth = round(($width / $height) * $maxDimension);
        }

        // Create a new image based on the source type
        $sourceImg = null;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $sourceImg = imagecreatefromjpeg($sourceImage);
                break;
            case IMAGETYPE_PNG:
                $sourceImg = imagecreatefrompng($sourceImage);
                break;
            case IMAGETYPE_GIF:
                $sourceImg = imagecreatefromgif($sourceImage);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $sourceImg = imagecreatefromwebp($sourceImage);
                }
                break;
            default:
                return false;
        }

        if (!$sourceImg) {
            return false;
        }

        // Create resized image
        $targetImg = imagecreatetruecolor($newWidth, $newHeight);

        // Preserve transparency for PNG and GIF
        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_GIF) {
            imagealphablending($targetImg, false);
            imagesavealpha($targetImg, true);
            $transparent = imagecolorallocatealpha($targetImg, 255, 255, 255, 127);
            imagefilledrectangle($targetImg, 0, 0, $newWidth, $newHeight, $transparent);
        }

        // Resize
        imagecopyresampled($targetImg, $sourceImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        // Save the image based on its type
        $success = false;
        switch ($type) {
            case IMAGETYPE_JPEG:
                $success = imagejpeg($targetImg, $targetImage, 85); // 85% quality
                break;
            case IMAGETYPE_PNG:
                $success = imagepng($targetImg, $targetImage, 8); // Compression level 8
                break;
            case IMAGETYPE_GIF:
                $success = imagegif($targetImg, $targetImage);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagewebp')) {
                    $success = imagewebp($targetImg, $targetImage, 85);
                }
                break;
        }

        // Free up memory
        imagedestroy($sourceImg);
        imagedestroy($targetImg);

        return $success;
    }




}