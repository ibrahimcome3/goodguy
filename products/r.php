<?php
//$images= glob("*.jpg");
//182-42
$p = 413;
$i = 265;
$imagePath = "product-$p/product-$p-image/inventory-$p-$i/";
$dir = "product-$p/product-$p-image/inventory-$p-$i/resized_600";
$dir2 = "product-$p/product-$p-image/inventory-$p-$i/resized"; 



if(!is_dir($imagePath)){
  //  echo "true";
    //mkdir($dir, 0777, true);
    //mkdir($dir2, 0777, true);
}

//echo $imagePath;
$imageFiles = glob("product-$p/product-$p-image/inventory-$p-$i/".'*.{jpg,png}', GLOB_BRACE);
//print_r($imageFiles);

foreach ($imageFiles as $filename) {
   //resize the image
 
   echo $filename."<br/><br/><br/>";
   
   if(resize_image($filename,$dir,600, 600,$filename)){
     echo $filename.' resize Success 600 * 600!<br />';
   }
   
    if(resize_image_($filename,$dir2,200,200,$filename)){
     echo $filename.' resize Success 200 * 200!<br />';
   }
}

function resize_image($file, $dir, $new_width, $new_height, $filename)
{
    echo $dir."<br/>";
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
    
    imagejpeg($resized_image, $dir."/" . $explode[count($explode)-1]);

    return 1;//$resized_image;
}

function resize_image_($file, $dir, $new_width, $new_height, $filename)
{
    echo $file."<br/>";
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
    echo $explode[count($explode)-1]; 
    imagejpeg($resized_image, $dir."/" . $explode[count($explode)-1]);

    return 1;//$resized_image;
}


?>
