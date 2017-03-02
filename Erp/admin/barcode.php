<?php 

define('IN_ECS', true);

/**
 * 条码生成
 * 
 * @author yxiang@leqee.com
 * @example <img src="barcode.php?barcode=ab456781" />
 */

require_once('includes/init.php');
require_once(ROOT_PATH . "includes/lib_barcode.php");


// download a ttf font here for example : http://www.dafont.com/fr/nottke.font
// $font     = './NOTTB___.TTF';
// - -

$fontSize = 10;   // GD1 in px ; GD2 in point
$marge    = 10;   // between barcode and hri in pixel
$height   = 30;   // barcode height in 1D ; module size in 2D
$width    = 230;
$scale    = 2;    // barcode height in 1D ; not use in 2D
$angle    = 0;    // rotation in degrees : nb : non horizontable barcode might not be usable because of pixelisation
$type     = 'code128';

/*
if(isset($_GET["text"])) $text=$_GET["text"];
if(isset($_GET["format"])) $format=$_GET["format"];
if(isset($_GET["quality"])) $quality=$_GET["quality"];
*/
if(isset($_GET["barcode"])) $barcode=$_GET["barcode"];
if(isset($_GET["height"])) $height=$_GET["height"];
if(isset($_GET["width"])) $width=$_GET["width"];
if(isset($_GET["type"])) $type=$_GET["type"];
if(isset($_GET["scale"])) $scale=$_GET["scale"];

// -------------------------------------------------- //
//            ALLOCATE GD RESSOURCE
// -------------------------------------------------- //
$im     = imagecreatetruecolor($width, $height);
$black  = ImageColorAllocate($im,0x00,0x00,0x00);
$white  = ImageColorAllocate($im,0xff,0xff,0xff);
$red    = ImageColorAllocate($im,0xff,0x00,0x00);
$blue   = ImageColorAllocate($im,0x00,0x00,0xff);
imagefilledrectangle($im, 0, 0, $width, $height, $white);

// -------------------------------------------------- //
//                      BARCODE
// -------------------------------------------------- //
$x = $width/2;   // barcode center
$y = $height/2;  // barcode center
$data = Barcode::gd($im, $black, $x, $y, $angle, $type, array('code'=>$barcode), $scale, $height);

// -------------------------------------------------- //
//                        HRI
// -------------------------------------------------- //
if (isset($font)){
	$box = imagettfbbox($fontSize, 0, $font, $data['hri']);
	$len = $box[2] - $box[0];
	Barcode::rotate(-$len / 2, ($data['height'] / 2) + $fontSize + $marge, $angle, $xt, $yt);
	imagettftext($im, $fontSize, $angle, $x + $xt, $y + $yt, $blue, $font, $data['hri']);
}

// -------------------------------------------------- //
//                    GENERATE
// -------------------------------------------------- //
header('Content-type: image/gif');
imagegif($im);
imagedestroy($im);
