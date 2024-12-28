<?php
include "includes.php";
$product_colums = ['InventoryItemID,small_description,	quantityOnHand,	cost,	reorderQuantity,	productItemID,	category,	sku'];


$CSVfp = fopen("csv/i.csv", "r");
$c = 0;
$p_colums = '';
while (($data = fgetcsv($CSVfp)) !== FALSE) {
    $c++;
    //if ($c === 1)
       // continue;

    //if ($c == 10)
    // break;
    $c++;
    $sql = "";

   if ($data[0] === 'InventoryItemID') {
   continue;
    }    
      if ($data) {
            $last = $data[0];
            $productItemID = $data[5];
            $dir = "products/product-" . $productItemID . "/" . "product-" . $productItemID . "-image/";
            $sql = "insert into inventoryitem (" . implode(",", $product_colums) . ")";
            $sql .= " values ('" . implode("','", $data) . "')";
            echo $sql . "<br/>";
            if ($result = $mysqli->query($sql)) {

                
                $dir2 = "products/product-" . $productItemID . "/" . "product-" . $productItemID . "-image/" . "inventory-" . $productItemID . "-" . $last . "/resized/";
                $dir3 = "products/product-" . $productItemID . "/" . "product-" . $productItemID . "-image/" . "inventory-" . $productItemID . "-" . $last . "/resized_600/";
                if (!file_exists($dir2)) {
                    mkdir($dir2, 0777, true);
                    mkdir($dir3, 0777, true);
                }

                // echo $dir . "<br/>";
                var_dump(glob($dir . '*.{jpg,png}', GLOB_BRACE));
                echo "<br/>";
                //echo "-------------------------";
              
                foreach (glob($dir . '*.{jpg,png}', GLOB_BRACE) as $name) {
                  
         
                    $time = $uniqueId = uniqid();
                    $time = $time . ".jpg";
                    $d = $dir."inventory-".$productItemID."-".$last."/".$time;
                    
                    
                    $sql = "INSERT INTO `inventory_item_image`(`inventory_item_image_id`, `image_name`, `image_path`, `is_primary`, `inventory_item_id`) 
                    VALUES (null,'$time', '$d', '0' ,'$data[0]')";
                    $result = $mysqli->query($sql);
                    if ($result) {
                        $img = new imageupload();
                        $url = explode('/', rtrim($name, '/'));
                        $url[count($url) - 1] = $time;
                        $url = implode('/', $url);
                       if ($img->convertImage($name, $url, 100)) {
                            $img->resize_image_600($name, $dir3, 600, 600, $time);
                            $img->resize_image_200($name, $dir2, 200, 200, $time);
                        }

                        echo "<br/>";
                    }
                }
                //echo $dir2 . "<br/>";
            }
        }
}

class imageupload
{


    function resize_image_600($file, $dir, $new_width, $new_height, $filename)
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

        $resized_image = imagecreatetruecolor(600, 600);
        $x = (600 / 2) - ($new_width / 2);
        $y = (600 / 2) - ($new_height / 2);
        $color = imagecolorallocate($resized_image, 255, 255, 255);
        imagefill($resized_image, 0, 0, $color);
        imagecopyresampled($resized_image, $image, $x, $y, 0, 0, $new_width, $new_height, $original_width, $original_height);

        $explode = explode("/", $filename);
        // echo $explode[count($explode)-1]; 

        imagejpeg($resized_image, $dir . "/" . $explode[count($explode) - 1]);

        return 1;//$resized_image;
    }

    function resize_image_200($file, $dir, $new_width, $new_height, $filename)
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

        $explode = explode("/", $filename);
        imagejpeg($resized_image, $dir . "/" . $explode[count($explode) - 1]);

        return 1;//$resized_image;
    }

    function convertImage($originalImage, $outputImage, $quality)
    {
        // jpg, png, gif or bmp?
        // $originalImage = "products/product-140/product-140-image/inventory-140-90/images.png";
        $exploded = explode('.', $originalImage);
        $ext = $exploded[count($exploded) - 1];
        //$newfilename = explode(".", $newfilename);

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
        //$outputImage = $outputImage . $newfilename[0];

        imagejpeg($imageTmp, $outputImage, $quality);
        //imagejpeg($imageTmp, "img/" . $newfilename[0] . ".jpg", $quality);
        imagedestroy($imageTmp);

        return $outputImage;
    }


}

exit();
/*
    //echo $dir . "<br/>";
    echo $last;
    echo "<br/>";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
    }
    $from = "copy/" . $last . '/';
    $to = $dir;
    echo "<br/>";
    //echo $from;
    // in this way glob() can give us just the file names
    foreach (glob($from . '*.{jpg,png}', GLOB_BRACE) as $name) {

        //echo $name;
        // echo $name;
        //echo $to;
        //$id = substr($name, strrpos($name, '/') + 1);
        $pos = strrpos($name, '/');
        $id = $pos === false ? $name : substr($name, $pos + 1);
        echo $id;
        copy($name, $to . $id);
    }

    /*
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                echo "filename:" . $file . "<br>";
            }
            closedir($dh);

        }
    }
    

    //echo $sql;
    echo "<br/>";

//}


fclose($CSVfp);

*/