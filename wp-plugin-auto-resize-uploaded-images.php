<?php


/*

Plugin Name: Auto Image Resizer 
Description: Resizes uploaded images automaticly to "Large Size Max Width / Height"
Plugin URI: http://dennishoppe.de/wordpress-plugins/auto-image-resizer
Version: 1.0.1
Author: Dennis Hoppe
Author URI: http://DennisHoppe.de

*/


If (!Class_Exists('wp_plugin_auto_resize_uploaded_images')){
Class wp_plugin_auto_resize_uploaded_images {

  Function __construct(){
    // Add the CatchUpload Hook as early as possible
    Add_Action ('add_attachment', Array($this, 'CatchUpload'), 1);
  }
  
  Function CatchUpload($attachment_id){
    // Check if it is an Image:
    If (!wp_attachment_is_image($attachment_id)) return;
    
    // Read the path:
    $file_path = get_attached_file($attachment_id);
    
    // New Size:
    $width = get_option('large_size_w');
    $height = get_option('large_size_h');
    
    // Check if its necessary to resize it:
    List ($current_width, $current_height) = GetImageSize($file_path);
    If ($current_width > $width || $current_height > $height){
      // Resize the image:
      $this->resize_image ($file_path, $width, $height, $file_path);
    }
  }
  
  Function Resize_Image($src, $width = 0, $height = 0, $dst ) {
    if ( $height <= 0 && $width <= 0 ) return false;
    
    // Setting defaults and meta
    $image = '';
    $final_width = 0;
    $final_height = 0;
    List ($width_old, $height_old, $image_type) = GetImageSize($src);
    
    // Calculating proportionality
    If     ($width  == 0) $factor = $height / $height_old;
    ElseIf ($height == 0) $factor = $width / $width_old;
    Else                  $factor = Min( $width / $width_old, $height / $height_old );
    
    $final_width  = Round( $width_old * $factor );
    $final_height = Round( $height_old * $factor );
    
    // Loading image to memory according to type
    Switch ( $image_type ) {
      case IMAGETYPE_GIF:  $image = imagecreatefromgif($src);  Break;
      case IMAGETYPE_JPEG: $image = imagecreatefromjpeg($src); Break;
      case IMAGETYPE_PNG:  $image = imagecreatefrompng($src);  Break;
      default: return false;
    }
    
    // This is the resizing/resampling/transparency-preserving magic
    $image_resized = ImageCreateTrueColor( $final_width, $final_height );
    If ( $image_type == IMAGETYPE_GIF || $image_type == IMAGETYPE_PNG ){
      $transparency = ImageColorTransparent($image);
      
      If ( $image_type == IMAGETYPE_GIF && $transparency >= 0 ){
        List($r, $g, $b) = Array_Values (ImageColorsForIndex($image, $transparency));
        $transparency = ImageColorAllocate($image_resized, $r, $g, $b);
        Imagefill($image_resized, 0, 0, $transparency);
        ImageColorTransparent($image_resized, $transparency);
      }
      Elseif ($image_type == IMAGETYPE_PNG) {
        ImageAlphaBlending($image_resized, false);
        $color = ImageColorAllocateAlpha($image_resized, 0, 0, 0, 127);
        ImageFill($image_resized, 0, 0, $color);
        ImageSaveAlpha($image_resized, true);
      }
    }
    ImageCopyResampled($image_resized, $image, 0, 0, 0, 0, $final_width, $final_height, $width_old, $height_old);
    
    // Writing image
    Switch ( $image_type ) {
      Case IMAGETYPE_GIF:  imagegif($image_resized, $dst);  Break;
      Case IMAGETYPE_JPEG: imagejpeg($image_resized, $dst, 85); Break;
      Case IMAGETYPE_PNG:  imagepng($image_resized, $dst);  Break;
      default: return false;
    }
  }

} /* End of the Class */
New wp_plugin_auto_resize_uploaded_images();
Require_Once DirName(__FILE__).'/contribution.php';
} /* End of the If-Class-Exists-Condition */
/* End of File */