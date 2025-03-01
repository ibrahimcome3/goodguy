<?php
require_once "Connn.php";
class ProductItem extends Connn
{
    private $timestamp;
    public $dbc;


    function __construct()
    {
        parent::__construct();
        $defaultTimeZone = 'UTC';
        date_default_timezone_set($defaultTimeZone);
        $this->timestamp = date('Y-m-d');
        $this->dbc = $this->getConnection();

    }

    function get_image_600_199($ivid)
    {
        $pid = $this->get_product_id($ivid);
        if ($this->check_dirtory_resized_600($pid, $ivid)) {
            $i = $ivid;
            $pi = glob("products/product-$pid/product-$pid-image/inventory-$pid-$i/resized_600/" . '*.{jpg,gif}', GLOB_BRACE);
            $img = $pi[0];
        } else {
            $img = $this->get_image($ivid);
        }
        return $img;
    }

    function add_product()
    {
        $pdo = $this->dbc;
        $data = ['description_' => 'Heinz', 'date_added_' => $this->timestamp, 'vendor_' => 'lm', 'Brand_' => 'Heinz',];
        $sql = "INSERT INTO `productitem`(`description`, `date_added`, `vendor`, `Brand`) VALUES (:description_, :date_added_, :vendor_, :Brand_)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($data);
    }

    function get_products()
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("select * from productitem");
        while ($row = $stmt->fetch()) {
            echo $row['description'] . "<br />\n";
        }
    }

    function get_product_inventory($product_id = 1)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->prepare("SELECT * FROM inventoryitem as i WHERE i.productItemID = ?");
        $stmt->execute([$product_id]);
        while ($row = $stmt->fetch()) {
            echo $row['description'] . "<br />\n";
        }

    }

    public function getAllProductsByVendorId_($mysqli, $vendorId)
    {
        $sql = "SELECT * FROM productitem WHERE vendor_id = ?";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            // Handle prepare failure
            error_log("Error in prepare: " . $mysqli->error);
            return false; // Or throw an exception
        }

        $stmt->bind_param("i", $vendorId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return []; // Return an empty array if no products are found
        }
    }
    public function getAllProductsByVendorId($mysqli, $vendorId, $limit = null, $offset = null)
    {
        $sql = "SELECT * FROM productitem WHERE vendor_id = ?";
        $params = [$vendorId];
        $types = "i";

        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
        }

        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing statement: " . $mysqli->error);
            return false;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            return [];
        }
    }

    function get_product_id($inventory_item_id)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->prepare("select productItemID from inventoryitem where `InventoryItemID`  = ?");
        $stmt->execute([$inventory_item_id]);
        $row = $stmt->fetch();
        if ($stmt->rowCount() > 0)
            return $row['productItemID'];
        else
            return 1;

    }

    public function getProductById($mysqli, $productId)
    {
        $sql = "SELECT * FROM productitem WHERE productID = ?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log("Error preparing statement: " . $mysqli->error);
            return false;
        }
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    function makeInventoryItemDirectory($productId, $inventoryItemId)
    {
        $productDir = "../products/product-" . $productId;

        if (!is_dir($productDir)) {  //Check if the product directory exists
            return false; // Or create it if you want: mkdir($productDir, 0777, true);
        }

        $inventoryDir = $productDir . "/inventory-" . $productId . "-" . $inventoryItemId;
        $resizedDir = $inventoryDir . "/resized";
        $resized600Dir = $inventoryDir . "/resized_600";
        echo $resizedDir;
        echo $resized600Dir;

        if (!is_dir($inventoryDir)) {
            mkdir($inventoryDir, 0777, true);
            if (!is_dir($resizedDir)) {   //Resized subdir
                mkdir($resizedDir, 0777, true);
            }

            if (!is_dir($resized600Dir)) { //Resized 600 subdir
                mkdir($resized600Dir, 0777, recursive: true);
            }
            return true;
        }





    }
    function makeSubDirectoriesForVarients($product_id, $inventory_item_id)
    {
        //Construct the full path
        $basePath = "../products/product-{$product_id}/inventory-{$product_id}-{$inventory_item_id}/";
        $resizedPath = $basePath . "resized/";
        $resized600Path = $basePath . "resized_600/";

        // Attempt to create directories; handle potential errors
        if (!is_dir($basePath) && !mkdir($basePath, 0777, true)) {
            return ["error" => "Error creating base directory: " . $basePath];
        }
        if (!is_dir($resizedPath) && !mkdir($resizedPath, 0777, true)) {
            return ["error" => "Error creating resized directory: " . $resizedPath];
        }
        if (!is_dir($resized600Path) && !mkdir($resized600Path, 0777, true)) {
            return ["error" => "Error creating resized_600 directory: " . $resized600Path];
        }

        return ["success" => true]; // Indicate success
    }

    function get_image($inventory_item_id)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->prepare("select * from inventory_item_image where inventory_item_id = ? and `is_primary` = 1");
        $stmt->execute([$inventory_item_id]);
        $row = $stmt->fetch();
        if ($stmt->rowCount() > 0)
            return $row['image_path'];
        else
            return "e.jpg";


    }
    function getbrand()
    {
        $pdo = $this->dbc;
        $stmt = $pdo->query("SELECT brandID, Name FROM brand");
        return $stmt;
    }

    function get_other_images_of_item_in_inventory($inventory_item_id)
    {

        $pdo = $this->dbc;
        $stmt = $pdo->prepare("select * from inventory_item_image where inventory_item_id = ? order by `is_primary` desc");
        $stmt->execute([$inventory_item_id]);
        if ($stmt->rowCount() > 0)
            return $stmt;
        // else return "e.jpg";

    }

    function get_other_images_of_item_in_inventory_not_1($inventory_item_id)
    {

        $pdo = $this->dbc;
        $stmt = $pdo->prepare("select * from inventory_item_image where inventory_item_id = ? order by `is_primary` desc LIMIT 18446744073709551615 OFFSET 1;");
        $stmt->execute([$inventory_item_id]);
        if ($stmt->rowCount() > 0)
            return $stmt;
        // else return "e.jpg";

    }

    function get_all_product_items_that_are_less_than_one_month()
    {
        $ar = array();
        $pdo = $this->dbc;
        //$stmt = $pdo->prepare("SELECT * FROM productitem WHERE `date_added` > DATE_SUB(NOW(), INTERVAL 1 MONTH)");
        $stmt = $pdo->prepare("SELECT * FROM inventoryitem WHERE `date_added` BETWEEN NOW() - INTERVAL 30 DAY AND NOW()");
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            while ($row = $stmt->fetch()) {
                array_push($ar, $row['InventoryItemID']);
            }
            return $ar;
        } else {
            array_push($ar, -1);
            return $ar;
        }



    }

    function check_dirctory_resized($product_item, $last_id)
    {
        $dir = "products/product-" . $product_item . "/" . "product-" . $product_item . "-image/" . "inventory-" . $product_item . "-" . $last_id . "/resized/";
        // $dir; echo "<br/>";
        if (!is_dir($dir)) {
            return false;
        } else {
            return true;
        }


    }

    function check_dirtory_resized_600($product_item, $last_id)
    {
        $dir = "products/product-" . $product_item . "/" . "product-" . $product_item . "-image/" . "inventory-" . $product_item . "-" . $last_id . "/resized_600/";
        //echo $dir; echo "<br/>";
        if (!is_dir($dir)) {
            return false;
        } else {
            return true;
        }

    }


    function shipping_and_re_trun_rule($id)
    {
        $pdo = $this->dbc;
        $stmt = $pdo->prepare("SELECT * FROM `shipping_policy` WHERE `shipping_policy_id` = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            return $row['shipping_policy'];


        }
    }


    function checkAllowableImage($file)
    {
        var_dump($file);
        foreach ($file['name'] as $i => $name) {
            $allowedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
            $detectedType = @exif_imagetype($_FILES["file"]["tmp_name"][$i]); //Added @ to suppress warnings
            if ($detectedType === false) {
                return false; //Not an image
            }
            if (!in_array($detectedType, $allowedTypes)) {
                return false; //Image type not allowed
            }
        }
        return true; //All images are allowed
    }


    // Function to validate a single POST element.  More robust error reporting.
    function validatePost($key, $type, $required = true, $min = null, $max = null, $pattern = null)
    {
        if (!isset($_POST[$key]) && $required) {
            return ["error" => "$key is required."];
        }
        $value = isset($_POST[$key]) ? trim($_POST[$key]) : null; //Get and trim value

        switch ($type) {
            case 'int':
                if (!is_numeric($value) || $value < $min || $value > $max) {
                    return ["error" => "$key must be an integer between $min and $max."];
                }
                return ["value" => (int) $value]; //Return validated integer
                break;
            case 'float':
                if (!is_numeric($value) || $value < $min || $value > $max) {
                    return ["error" => "$key must be a number between $min and $max."];
                }
                return ["value" => (float) $value]; //Return validated number
                break;
            case 'string':
                if (strlen($value) < $min || strlen($value) > $max) {
                    return ["error" => "$key must be between $min and $max characters."];
                }
                if ($pattern !== null && !preg_match($pattern, $value)) {
                    return ["error" => "$key does not match the required pattern."];
                }
                return ["value" => $value]; //Return validated string
                break;
            default:
                return ["error" => "Invalid validation type for $key."];
        }
    }

    function insertProductItem($mysqli, $productData)
    {
        // Prepare the SQL statement (preventing SQL injection)
        $stmt = $mysqli->prepare("INSERT INTO `productitem` (
                                   `product_name`,  `vendor`, `category`, `brand`, 
                                   `product_information`,  `shipping_returns`, `user_id` ) 
                               VALUES (
                                   ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            die("Error preparing statement: " . $mysqli->error);
        }
        var_dump($productData);
        extract($productData);

        // Bind parameters.  Order matters!  Match placeholders in SQL.
        $stmt->bind_param("siiisii", $product_name, $vendor, $category, $brand, $product_information, $shipping_returns, $user_id);

        // Execute the query
        if ($stmt->execute()) {
            return $mysqli->insert_id; // Return the last insert ID
        } else {
            die("Error inserting product item: " . $stmt->error);
        }

        // Close statement
        $stmt->close();
    }



    function moveImageforProduct($target_file_name, $file, $last_id)
    {
        $pdo = $this->dbc;
        if (move_uploaded_file($file["tmp_name"][0], $$target_file_name)) {
            convertImage1($$target_file_name, $$target_file_name, 100);
            $file_name = time();
            $sql = "INSERT INTO `product_images` (`p_imgeid`, `image`, `product_id`) VALUES (NULL, '$file_name', '$last_id');";

            $result = $pdo->query($sql);
        }


    }

    function moveProductImage($productId, $files)
    {

        $productDir = "../products/product-" . $productId;
        $productImageDir = $productDir . "/product-" . $productId . "-image";

        // Create directories if they don't exist
        if (!is_dir($productDir) && !mkdir($productDir, 0777, true)) {
            return ["error" => "Error creating product directory."];
        }
        if (!is_dir($productImageDir) && !mkdir($productImageDir, 0777, true)) {
            return ["error" => "Error creating product image directory."];
        }


        $uploadedImages = []; //Store paths of uploaded images

        foreach ($files['name'] as $key => $name) {
            if ($files["error"][$key] == UPLOAD_ERR_OK) {  //Check for upload errors for each file

                $temp = explode(".", $name);
                $newFilename = round(microtime(true)) . $key . '.' . end($temp); //More unique filename
                $targetFile = $productImageDir . "/" . $newFilename;


                if (move_uploaded_file($files["tmp_name"][$key], $targetFile)) {
                    $this->convertImage1($targetFile, $targetFile, 100);

                    $sql = "INSERT INTO `product_images` (`p_imgeid`, `image`, `product_id`) VALUES (NULL, ?, ?)";
                    $stmt = $this->dbc->prepare($sql);

                    if ($stmt->execute([$newFilename, $productId])) {
                        $uploadedImages[] = ["name" => $name, "path" => $targetFile];
                    } else {
                        return ["error" => "Database insertion failed for image: " . $name];
                    }


                } else {
                    return ["error" => "Error uploading file: " . $name];
                }
            } else {
                return ["error" => "Upload error for image: " . $name . ". Error code: " . $files['error'][$key]];
            }
        }

        return ["images" => $uploadedImages];
    }




    function makedir_for_product($last_id)
    {

        $dir = "../products/product-" . $last_id . "/" . "product-" . $last_id . "-image";
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }






    function resize_image_($file, $new_width, $new_height, $to_be_saved, $filename)
    {

        list($original_width, $original_height) = getimagesize($file);
        $image = imagecreatefromjpeg($file);

        // Calculate new dimensions while maintaining aspect ratio
        $aspect_ratio = $original_width / $original_height;
        if ($new_width / $new_height > $aspect_ratio) {
            $new_width = $new_height * $aspect_ratio;
        } else {
            $new_height = $new_width / $aspect_ratio;
        }

        $resized_image = imagecreatetruecolor(200, 200);
        $x = (200 / 2) - ($new_width / 2);
        $y = (200 / 2) - ($new_height / 2);
        $color = imagecolorallocate($resized_image, 255, 255, 255);
        imagefill($resized_image, 0, 0, $color);
        imagecopyresampled($resized_image, $image, $x, $y, 0, 0, $new_width, $new_height, $original_width, $original_height);

        // $explode = explode("/", $filename);
        // echo $explode[count($explode)-1]; 
        imagejpeg($resized_image, $to_be_saved . $filename);
        //imagejpeg($resized_image, $dir."/" . $explode[count($explode)-1]);

        return 1;//$resized_image;
    }


    function convertImage($originalImage, $outputImage, $quality, $newfilename)
    {
        // jpg, png, gif or bmp?
        // $originalImage = "products/product-140/product-140-image/inventory-140-90/images.png";
        $exploded = explode('.', $originalImage);
        $ext = $exploded[count($exploded) - 1];
        $newfilename = explode(".", $newfilename);


        if (preg_match('/jpg|jpeg/i', $ext))
            $imageTmp = imagecreatefromjpeg($originalImage);
        else if (preg_match('/png/i', $ext))
            $imageTmp = imagecreatefrompng($originalImage);
        else if (preg_match('/gif/i', $ext))
            $imageTmp = imagecreatefromgif($originalImage);
        else if (preg_match('/bmp/i', $ext))
            $imageTmp = imagecreatefrombmp($originalImage);
        else
            return 0;

        // quality is a value from 0 (worst) to 100 (best)
        $outputImage = $outputImage . $newfilename[0] . ".jpg";
        echo $outputImage;

        imagejpeg($imageTmp, $outputImage, $quality);
        imagedestroy($imageTmp);

        return $outputImage;
    }


    function convertImage1($originalImage, $outputImage, $quality)
    {
        // jpg, png, gif or bmp?
        $exploded = explode('.', $originalImage);
        $ext = $exploded[count($exploded) - 1];

        if (preg_match('/jpg|jpeg/i', $ext))
            $imageTmp = imagecreatefromjpeg($originalImage);
        else if (preg_match('/png/i', $ext))
            $imageTmp = imagecreatefrompng($originalImage);
        else if (preg_match('/gif/i', $ext))
            $imageTmp = imagecreatefromgif($originalImage);
        else if (preg_match('/bmp/i', $ext))
            $imageTmp = imagecreatefrombmp($originalImage);
        else
            return 0;

        // quality is a value from 0 (worst) to 100 (best)
        imagejpeg($imageTmp, $outputImage, $quality);
        imagedestroy($imageTmp);

        return 1;
    }
    function imageprocessorforproductInInventory($product_id, $inventory_item_id, $files)
    {
        $pdo = $this->dbc;

        //Correct and simpler path construction
        $basePath = "../products/product-{$product_id}/inventory-{$product_id}-{$inventory_item_id}/";
        $resizedPath = $basePath . "resized/";
        $resized600Path = $basePath . "resized_600/";


        // Create directories (using makeSubDirectoriesForVarients)
        $dirResult = $this->makeSubDirectoriesForVarients($product_id, $inventory_item_id); // Call the function
        if (isset($dirResult['error'])) {
            return ["error" => "Directory creation failed: " . $dirResult['error']];
        }

        //Process uploaded files
        var_dump($files);
        for ($i = 0; $i < count($files); $i++) {
            if ($files["image"]["error"][$i] == UPLOAD_ERR_OK) {
                $temp = explode(".", $files["image"]["name"][$i]);
                $newFilename = round(microtime(true)) . $i . '.' . end($temp);
                $targetFile = $basePath . $newFilename;  //Simplified target path

                if (move_uploaded_file($files["image"]["tmp_name"][$i], $targetFile)) {

                    //Resize and convert images in one step
                    $this->resizeImage($targetFile, $resizedPath . $newFilename, 199, 199);
                    $this->resizeImage($targetFile, $resized600Path . $newFilename, 600, 600);


                    $this->insertImageIntoDatabase($inventory_item_id, $newFilename, $i); //Insert into db.

                } else {
                    return ["error" => "Error uploading image: " . $files["image"]["name"][$i]];
                }
            } else {
                return ["error" => "Upload error for image: " . $files["image"]["name"][$i] . ". Error code: " . $files["image"]["error"][$i]];
            }
        }

        return ["success" => true];
    }

    //Improved image resizing function; handles more image types
    function resizeImage($source, $destination, $width, $height)
    {
        list($originalWidth, $originalHeight) = getimagesize($source);
        $image = imagecreatefromjpeg($source); // Attempt to create image from JPEG

        if (!$image) {
            $image = imagecreatefrompng($source); //Try PNG
            if (!$image) {
                $image = imagecreatefromgif($source); //Try GIF
                if (!$image) {
                    return false; // Or handle the error appropriately
                }
            }
        }

        $aspect_ratio = $originalWidth / $originalHeight;
        if ($width / $height > $aspect_ratio) {
            $width = $height * $aspect_ratio;
        } else {
            $height = $width / $aspect_ratio;
        }
        $resizedImage = imagecreatetruecolor($width, $height);
        $x = ($width / 2) - ($width / 2);
        $y = ($height / 2) - ($height / 2);
        $color = imagecolorallocate($resizedImage, 255, 255, 255);
        imagefill($resizedImage, 0, 0, $color);
        imagecopyresampled($resizedImage, $image, $x, $y, 0, 0, $width, $height, $originalWidth, $originalHeight);
        imagejpeg($resizedImage, $destination, 100); // Save as JPEG
        imagedestroy($resizedImage);
        return true;
    }



    function resize_image($file, $new_width, $new_height, $to_be_saved, $filename)
    {
        echo $file;
        list($original_width, $original_height) = getimagesize($file);
        $image = imagecreatefromjpeg($file);

        // Calculate new dimensions while maintaining aspect ratio
        $aspect_ratio = $original_width / $original_height;
        if ($new_width / $new_height > $aspect_ratio) {
            $new_width = $new_height * $aspect_ratio;
        } else {
            $new_height = $new_width / $aspect_ratio;
        }
        $resized_image = imagecreatetruecolor(600, 600);
        $x = (600 / 2) - ($new_width / 2);
        $y = (600 / 2) - ($new_height / 2);

        $color = imagecolorallocate($resized_image, 255, 255, 255);
        imagefill($resized_image, 0, 0, $color);
        imagecopyresampled($resized_image, $image, $x, $y, 0, 0, $new_width, $new_height, $original_width, $original_height);


        imagejpeg($resized_image, $to_be_saved . $filename);

        return 1;//$resized_image;
    }
    function insertImageIntoDatabase($inventoryItemId, $newFilename, $isPrimary)
    {
        $sql = "INSERT INTO `inventory_item_image` (`inventory_item_image_id`, `image_name`, `image_path`, `is_primary`, `inventory_item_id`) 
                VALUES (NULL, ?, ?, ?, ?)";
        $stmt = $this->dbc->prepare($sql);
        $isPrimary = ($isPrimary === 0) ? 0 : 1; //Ensure is_primary is 0 or 1
        $stmt->execute([$newFilename, $newFilename, $isPrimary, $inventoryItemId]);
        if (!$stmt) {
            return ["error" => "Database insertion failed for image"];
        }

    }

    function imageprocessorforproductInInventory1($product_item, $file, $i, $c = 0)
    {
        $pdo = $this->dbc;
        $target_dir = "../products/product-" . $product_item . "/" . "product-" . $product_item . "-image/" . "inventory-" . $product_item . "-" . $last_id . "/";
        $target_dir_second = "../products/product-" . $product_item . "/" . "product-" . $product_item . "-image/" . "inventory-" . $product_item . "-" . $last_id . "/resized/";
        $target_dir_second_600 = "../products/product-" . $product_item . "/" . "product-" . $product_item . "-image/" . "inventory-" . $product_item . "-" . $last_id . "/resized_600/";
        $temp = explode(".", $file["name"][$i]);
        $newfilename = round(microtime(true)) + $i . '.' . end($temp);
        $target_file = $target_dir . $newfilename;
        $target_file2 = $target_dir_second . $newfilename;




        if (move_uploaded_file($file["tmp_name"][$i], $target_file)) {
            $out = convertImage($target_file, $target_dir, 100, $newfilename);
            //move_uploaded_file( $file["tmp_name"][$i], $target_file2);
            //convertImage($target_file2, $target_dir_second, 100, $newfilename);
            $this->resize_image_($target_file, 199, 199, $target_dir_second, $newfilename);
            $this->resize_image($target_file, 600, 600, $target_dir_second_600, $newfilename);



            $new_out = substr($out, 3);
            $file_name = $file["name"][$i];
            $newfilename = explode(".", $newfilename);
            $inserted_file_name = $newfilename[0] . ".jpg";

            if ($i === 0) {
                $sql = "INSERT INTO `inventory_item_image` (`inventory_item_image_id`, `image_name`, `image_path`, `is_primary`, `inventory_item_id`) VALUES (NULL, '$inserted_file_name', '$new_out', '1', '$last_id');";
            } else {
                $sql = "INSERT INTO `inventory_item_image` (`inventory_item_image_id`, `image_name`, `image_path`, `is_primary`, `inventory_item_id`) VALUES (NULL, '$inserted_file_name', '$new_out', '0', '$last_id');";
            }
            $result = $pdo->query($sql);
            if ($result) {
                // echo "The file " . htmlspecialchars(basename($file["name"][$i])) . " has been uploaded.<br>";

            }
        } else {

        }
        $c++;
    }

    function handleVariantImageUpload($productId, $files)
    {

        $productDir = "../products/product-" . $productId . "/product-" . $productId . "-image/"; // Consistent directory structure
        if (!is_dir($productDir) && !mkdir($productDir, 0777, true)) {
            return "default_image.jpg"; // Or handle the directory creation error appropriately
        }


        if ($files["error"] == UPLOAD_ERR_OK) {
            $temp = explode(".", $files["name"]);
            $newFilename = round(microtime(true)) . '.' . end($temp); // Include index in filename
            $targetFile = $productDir . $newFilename;

            if (move_uploaded_file($files["tmp_name"], $targetFile)) {
                // ... your image processing/resizing functions ...
                return $targetFile; // Return full path
            } else {
                return "default_image.jpg"; // Or handle the move_uploaded_file error
            }
        } else {
            return "default_image.jpg"; // Handle any upload errors
        }

    }

    public function deleteProduct($mysqli, $productId)
    {
        $sql = "DELETE FROM productitem WHERE productID = ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $productId);
            return $stmt->execute();
        }
        return false;
    }

    public function deleteProductCompletely($mysqli, $productId)
    {
        $mysqli->begin_transaction(); // Start transaction for atomicity

        try {
            // 1. Delete associated inventory items
            $sql = "DELETE FROM inventoryitem WHERE productItemID = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            if ($stmt->error) {
                throw new Exception("Error deleting inventory items: " . $stmt->error);
            }

            // 2. Delete associated images
            $sql = "DELETE FROM inventory_item_image WHERE inventory_item_id IN (SELECT InventoryItemID FROM inventoryitem WHERE productItemID = ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            if ($stmt->error) {
                throw new Exception("Error deleting images: " . $stmt->error);
            }

            // 3. Delete product images from the product_images table
            $sql = "DELETE FROM product_images WHERE product_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            if ($stmt->error) {
                throw new Exception("Error deleting product images: " . $stmt->error);
            }


            // 4. Delete the product itself
            $sql = "DELETE FROM productitem WHERE productID = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            if ($stmt->error) {
                throw new Exception("Error deleting product: " . $stmt->error);
            }

            // 5. Delete the product's directory and subdirectories
            $this->deleteProductDirectory($productId);

            $mysqli->commit(); // Commit transaction if all went well
            return true;
        } catch (Exception $e) {
            $mysqli->rollback(); // Rollback if any error occurred
            error_log("Error deleting product completely: " . $e->getMessage());
            return false;
        }
    }
    //Helper Function to delete Product Directory
    private function deleteProductDirectory($productId)
    {
        $productDir = "../products/product-" . $productId;
        if (is_dir($productDir)) {
            $this->recursiveDeleteDirectory($productDir);
        }
    }

    private function recursiveDeleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false; // Not a directory
        }
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== "." && $file !== "..") {
                $path = $dir . "/" . $file;
                if (is_dir($path)) {
                    $this->recursiveDeleteDirectory($path); // Recursive call for subdirectories
                } else {
                    unlink($path); // Delete file
                }
            }
        }
        rmdir($dir); // Delete the directory itself after deleting files
        return true;
    }



    public function deleteImage($mysqli, $imageId, $productId)
    {
        // 1. Get the image path from the database
        $sql = "SELECT image FROM product_images WHERE p_imgeid = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $imageId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $imagePath = "../products/product-{$productId}/product-{$productId}-image/" . $row['image'];

        // 2. Delete the image file from the file system
        if (file_exists($imagePath)) {
            if (!unlink($imagePath)) {
                throw new Exception("Error deleting image file: $imagePath");
            }
        } else {
            // Handle case where file does not exist
            error_log("Image file not found: " . $imagePath);
        }

        // 3. Delete the image record from the database
        $sql = "DELETE FROM product_images WHERE p_imgeid = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $imageId);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting image from database: " . $stmt->error);
        }

        return true;
    }

    // ... rest of your ProductItem class code ...



}
?>