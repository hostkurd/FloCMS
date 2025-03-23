<?php


function generate_string($input, $strength = 5) {
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $input[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }
  
    return $random_string;
}


// $permitted_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
// $string_length = 6;
// $captcha_string = generate_string($permitted_chars, $string_length);

function secure_generate_string($input, $strength = 5, $secure = true) {
    $input_length = strlen($input);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        if($secure) {
            $random_character = $input[random_int(0, $input_length - 1)];
        } else {
            $random_character = $input[mt_rand(0, $input_length - 1)];
        }
        $random_string .= $random_character;
    }
  
    return $random_string;
}


$black = imagecolorallocate($image, 0, 0, 0);
$white = imagecolorallocate($image, 255, 255, 255);
$textcolors = [$black, $white];
 
$fonts = [
    SITE_URI.'\fonts\Acme.ttf', 
    SITE_URI.'\fonts\Ubuntu.ttf', 
    SITE_URI.'\fonts\Merriweather.ttf', 
    SITE_URI.'\fonts\PlayfairDisplay.ttf'];
 
$string_length = 6;
$captcha_string = secure_generate_string($permitted_chars, $string_length);
 
for($i = 0; $i < $string_length; $i++) {
  $letter_space = 170/$string_length;
  $initial = 15;
   
  imagettftext($image, 20, rand(-15, 15), $initial + $i*$letter_space, rand(20, 40), $textcolors[rand(0, 1)], $fonts[array_rand($fonts)], $captcha_string[$i]);
}
 
header('Content-type: image/png');
imagepng($image);
imagedestroy($image);

?>