<?php
include "../conn.php";
 $file = $_FILES['files'];
 if((int)($_FILES['files']['error'][0]) !== 4) {
     $description = $_POST['description'];
     $qonhand = $_POST['quintity_in_inventory'];
     $cost = $_POST['cost'];
     $reorder_quitity = $_POST['reorder_quintity'];
     $product_item = $_POST['productid'];
     $cat = $_POST['category'];
     $sku = $_POST['sku'];
     $barcode = $_POST['barcode'];

        foreach ($file['name'] as $i => $name) {
        $allowedTypes = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);
        $detectedType = exif_imagetype($_FILES["files"]["tmp_name"][$i]);
        if (!in_array($detectedType, $allowedTypes)) {
        echo "<b style='color:#CC0000'>".$_FILES["files"]["tmp_name"][$i]." image type not allowed.</b>";
        echo "<a href=". $_SERVER['HTTP_REFERER'].">Back</a>";
        //echo '<img src="'.$_FILES["files"]["tmp_name"][$i].'" width="300" style="border: 0; float: left; vertical-align: middle" alt="image that cannot be uploaded">';
        exit();
        }
        }

     $sql = "INSERT INTO `inventoryitem`(`InventoryItemID`, `small_description`, `quantityOnHand`, `cost`, `reorderQuantity`, `productItemID`, `date_added`, `category`, `sku`, barcode) VALUES (null,'$description','$qonhand','$cost','$reorder_quitity','$product_item', CURRENT_TIMESTAMP,'$cat','$sku', '$barcode')";
     $result = $mysqli->query($sql);
     $last_id = mysqli_insert_id($mysqli);

     if($result){
         if (!file_exists("../products/product-".$product_item."/"."product-".$product_item."-image/"."inventory-".$product_item."-".$last_id."/")) {
         mkdir("../products/product-".$product_item."/"."product-".$product_item."-image/"."inventory-".$product_item."-".$last_id."/", 0777, true);
         mkdir("../products/product-".$product_item."/"."product-".$product_item."-image/"."inventory-".$product_item."-".$last_id."/resized/", 0777, true);
         mkdir("../products/product-".$product_item."/"."product-".$product_item."-image/"."inventory-".$product_item."-".$last_id."/resized_600/", 0777, true);
         }
            $c = 0;
        foreach ($file['name'] as $i => $name) {
                $target_dir = "../products/product-".$product_item."/"."product-".$product_item."-image/"."inventory-".$product_item."-".$last_id."/";
                $target_dir_second = "../products/product-".$product_item."/"."product-".$product_item."-image/"."inventory-".$product_item."-".$last_id."/resized/";
                $target_dir_second_600 = "../products/product-".$product_item."/"."product-".$product_item."-image/"."inventory-".$product_item."-".$last_id."/resized_600/";
                $temp = explode(".", $file["name"][$i]);
                $newfilename = round(microtime(true))+$i . '.' . end($temp);
                $target_file = $target_dir . $newfilename;
                $target_file2 = $target_dir_second . $newfilename;
                echo "<br>";
                //echo $target_file2;
                echo "<br>";
        
                if (move_uploaded_file($file["tmp_name"][$i], $target_file)) {
                    $out = convertImage($target_file, $target_dir, 100, $newfilename);
                    //move_uploaded_file( $file["tmp_name"][$i], $target_file2);
                    //convertImage($target_file2, $target_dir_second, 100, $newfilename);
                    resize_image($target_file, 199, 199, $target_dir_second, $newfilename);
                    resize_image($target_file, 600, 600, $target_dir_second_600, $newfilename);
                  
                    
                    
                    $new_out  = substr($out, 3);
                    $file_name = $file["name"][$i];
                    $newfilename = explode(".", $newfilename);
                    $inserted_file_name = $newfilename[0].".jpg";
                     if($i === 0){
                        $sql = "INSERT INTO `inventory_item_image` (`inventory_item_image_id`, `image_name`, `image_path`, `is_primary`, `inventory_item_id`) VALUES (NULL, '$inserted_file_name', '$new_out', '1', '$last_id');";  
                    }else{
                    $sql = "INSERT INTO `inventory_item_image` (`inventory_item_image_id`, `image_name`, `image_path`, `is_primary`, `inventory_item_id`) VALUES (NULL, '$inserted_file_name', '$new_out', '0', '$last_id');";
                    }
                    $result = $mysqli->query($sql);
                    if($result){
                   // echo "The file " . htmlspecialchars(basename($file["name"][$i])) . " has been uploaded.<br>";
                    echo "<a href=". $_SERVER['HTTP_REFERER'].">Back</a>";
                    }
                } else {
                    echo "Sorry, there was an error uploading your file.<br>";
                }
                $c++;
           }
      }






 }else{

 echo "pls select an image to upload<br>";
 echo "<a href=". $_SERVER['HTTP_REFERER'].">Back</a>";
 exit();
 }


/*$sql = " ";
$target_dir = "uploads/";
foreach ($_FILES["files"]["tmp_name"] as $key => $tmp_name) {
    $target_file = $target_dir . basename($_FILES["files"]["name"][$key]);
    move_uploaded_file($tmp_name, $target_file);
}
  */
function convertImage1($originalImage, $outputImage, $quality)
{
    // jpg, png, gif or bmp?
    $exploded = explode('.',$originalImage);
    $ext = $exploded[count($exploded) - 1]; 

    if (preg_match('/jpg|jpeg/i',$ext))
        $imageTmp=imagecreatefromjpeg($originalImage);
    else if (preg_match('/png/i',$ext))
        $imageTmp=imagecreatefrompng($originalImage);
    else if (preg_match('/gif/i',$ext))
        $imageTmp=imagecreatefromgif($originalImage);
    else if (preg_match('/bmp/i',$ext))
        $imageTmp=imagecreatefrombmp($originalImage);
    else
        return 0;

    // quality is a value from 0 (worst) to 100 (best)
    imagejpeg($imageTmp, $outputImage, $quality);
    imagedestroy($imageTmp);

    return 1;
}

function makedir($last_id)
{

    $dir = "../products/product-".$last_id."/"."product-".$last_id."-image";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
}



function convertImage($originalImage, $outputImage, $quality, $newfilename)
{
    // jpg, png, gif or bmp?
   // $originalImage = "products/product-140/product-140-image/inventory-140-90/images.png";
    $exploded = explode('.',$originalImage);
    $ext = $exploded[count($exploded) - 1];
    $newfilename = explode(".", $newfilename);
    

    if (preg_match('/jpg|jpeg/i',$ext))
        $imageTmp=imagecreatefromjpeg($originalImage);
    else if (preg_match('/png/i',$ext))
        $imageTmp=imagecreatefrompng($originalImage);
    else if (preg_match('/gif/i',$ext))
        $imageTmp=imagecreatefromgif($originalImage);
    else if (preg_match('/bmp/i',$ext))
        $imageTmp=imagecreatefrombmp($originalImage);
    else
        return 0;

    // quality is a value from 0 (worst) to 100 (best)
    $outputImage = $outputImage.$newfilename[0].".jpg";
    echo $outputImage;

    imagejpeg($imageTmp, $outputImage, $quality);
    imagedestroy($imageTmp);

    return $outputImage;
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




function resize_image_($file,  $new_width, $new_height, $to_be_saved, $filename)
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

?>