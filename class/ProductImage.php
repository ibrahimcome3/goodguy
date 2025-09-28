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
}