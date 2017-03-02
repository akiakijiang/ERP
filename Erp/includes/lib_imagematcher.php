<?php
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

function parse($path, $name) {
  static $standards;
  
  $img = imagecreatefrompng($path);
  if (!$starnds[$name]) {
    $basepath = dirname(__FILE__).DIRECTORY_SEPARATOR."imagematcher".DIRECTORY_SEPARATOR;
    $standard_img = imagecreatefrompng($basepath.$name.".png"); //   $standard_img = imagecreatefrombmp($name.".bmp");
    $standard_rects = cut_down($standard_img);
   
    $chs = file_get_contents($basepath.$name.".txt");
    $standards[$name] = array('standard_img' => $standard_img, 'standard_rects' => $standard_rects, 'chs' => $chs);
  }
  
  $rects = cut_down($img);
  foreach ($rects as $rect) {
    $result .= dotest($img, $rect, $standards[$name]['standard_img'], $standards[$name]['standard_rects'], $standards[$name]['chs']);
  }
  return $result;
}

function   imagecreatefrombmp($fname) {  
  $buf=@file_get_contents($fname);  
  
  if(strlen($buf)<54)   return   false;  
  $file_header=unpack("sbfType/LbfSize/sbfReserved1/sbfReserved2/LbfOffBits",substr($buf,0,14));  
  if($file_header["bfType"]!=19778)   return   false;  
  $info_header=unpack("LbiSize/lbiWidth/lbiHeight/sbiPlanes/sbiBitCountLbiCompression/LbiSizeImage/lbiXPelsPerMeter/lbiYPelsPerMeter/LbiClrUsed/LbiClrImportant",substr($buf,14,40));  
  //懒得支持2色位图  
  if($info_header["biBitCountLbiCompression"]==2)   return   false;  
  $line_len=round($info_header["biWidth"]*$info_header["biBitCountLbiCompression"]/8);  
  $x=$line_len%4;  
  if($x>0)   $line_len+=4-$x;  
   
  $img=imagecreatetruecolor($info_header["biWidth"],$info_header["biHeight"]);  
  switch($info_header["biBitCountLbiCompression"]){  
  case   4:  
  $colorset=unpack("L*",substr($buf,54,64));  
  for($y=0;$y<$info_header["biHeight"];$y++){  
  $colors=array();  
  $y_pos=$y*$line_len+$file_header["bfOffBits"];  
  for($x=0;$x<$info_header["biWidth"];$x++){  
  if($x%2)  
  $colors[]=$colorset[(ord($buf[$y_pos+($x+1)/2])&0xf)+1];  
  else  
  $colors[]=$colorset[((ord($buf[$y_pos+$x/2+1])>>4)&0xf)+1];  
  }  
  imagesetstyle($img,$colors);  
  imageline($img,0,$info_header["biHeight"]-$y-1,$info_header["biWidth"],$info_header["biHeight"]-$y-1,IMG_COLOR_STYLED);  
  }  
  break;  
  case   8:  
  $colorset=unpack("L*",substr($buf,54,1024));  
  for($y=0;$y<$info_header["biHeight"];$y++){  
  $colors=array();  
  $y_pos=$y*$line_len+$file_header["bfOffBits"];  
  for($x=0;$x<$info_header["biWidth"];$x++){  
  $colors[]=$colorset[ord($buf[$y_pos+$x])+1];  
  }  
  imagesetstyle($img,$colors);  
  imageline($img,0,$info_header["biHeight"]-$y-1,$info_header["biWidth"],$info_header["biHeight"]-$y-1,IMG_COLOR_STYLED);  
  }  
  break;  
  case   16:  
  for($y=0;$y<$info_header["biHeight"];$y++){  
  $colors=array();  
  $y_pos=$y*$line_len+$file_header["bfOffBits"];  
  for($x=0;$x<$info_header["biWidth"];$x++){  
  $i=$x*2;  
  $color=ord($buf[$y_pos+$i])|(ord($buf[$y_pos+$i+1])<<8);  
  $colors[]=imagecolorallocate($img,(($color>>10)&0x1f)*0xff/0x1f,(($color>>5)&0x1f)*0xff/0x1f,($color&0x1f)*0xff/0x1f);  
  }  
  imagesetstyle($img,$colors);  
  imageline($img,0,$info_header["biHeight"]-$y-1,$info_header["biWidth"],$info_header["biHeight"]-$y-1,IMG_COLOR_STYLED);  
  }  
  break;  
  case   24:  
  for($y=0;$y<$info_header["biHeight"];$y++){  
  $colors=array();  
  $y_pos=$y*$line_len+$file_header["bfOffBits"];  
  for($x=0;$x<$info_header["biWidth"];$x++){  
  $i=$x*3;  
  $colors[]=imagecolorallocate($img,ord($buf[$y_pos+$i+2]),ord($buf[$y_pos+$i+1]),ord($buf[$y_pos+$i]));  
  }  
  imagesetstyle($img,$colors);  
  imageline($img,0,$info_header["biHeight"]-$y-1,$info_header["biWidth"],$info_header["biHeight"]-$y-1,IMG_COLOR_STYLED);  
  }  
  break;  
  default:  
  return   false;  
  break;  
  }  
  return   $img;  
}

function cut_down($img, $bg = array()) {
  $width = imagesx($img);
  $height = imagesy($img);
  if(!$bg) $bg = get_pixel($img, 2, 2, array(255,255,255));
  list($r0, $g0, $b0) = $bg;
  
  $result = array();
  $rect = array(-1, -1, $height, 0);
  
  for($i = 2; $i < $width-1; $i++ ) {
    $hitted = False;
    for($j = 2; $j < $height-1; $j++ ) {
      list($r, $g, $b) = get_pixel($img, $i, $j, $bg);
      if ($r == $r0 && $g == $g0 && $b == $b0) continue;
      
      $hitted = true;
      if ($rect[2] > $j) $rect[2] = $j;
      if ($rect[3] < $j) $rect[3] = $j;
    }
    
    if($hitted) {
      if ($rect[0] == -1) $rect[0] = $i;
      $rect[1] = $i;
    }
    else {
      if ($rect[1] >= 0) $result[] = $rect;
      $rect = array(-1,-1,$height,0);
    }
  }
  if($rect[1] >= 0) $result[] = $rect;
  return $result;
}


function get_pixel($img, $x, $y, $bg) {
  $width = imagesx($img);
  $height = imagesy($img);
  if ($x >= $width || $y >= $height)  return $bg;
  
  $rgb = imagecolorat($img, $x, $y);
  $r = ($rgb >> 16) & 0xFF;
  $g = ($rgb >> 8) & 0xFF;
  $b = $rgb & 0xFF;

  $color = array($r, $g, $b);
  return $color;
}

function dotest($img, $rect, $standard_image, $standard_rects, $chs) {
  $value = 100;
  $result = "";
  for ($i=0; $i<count($standard_rects); $i++) {
    
    $v = compare_rect($img, $rect, $standard_image, $standard_rects[$i]);
//    print_r($standard_rects[$i]);
//    print $v."<br/>";
    if ($value > $v) {
//      print $v." {$chs[$i]} <br />";
    	$value = $v;
    	$result = $chs[$i];
    }
  }
  
  return $result;
}


function  compare_rect($img1, $rect1, $img2, $rect2, $bg1=array(), $bg2=array()) {
  $notmatched = 0;
  if (!$bg1) {
  	$bg1 = get_pixel($img1, 2, 2, array(255,255,255));
  	list($r1, $g1, $b1) = $bg1;
  }
  if (!$bg2) {
  	$bg2 = get_pixel($img2, 2, 2, array(255,255,255));
  	list($r2, $g2, $b2) = $bg2;
  }
//  print_r($bg2);
  for ($i = $rect1[0]; $i< $rect1[1]; $i++) {
    for ($j = $rect1[2]; $j< $rect1[3]; $j++) {
      list($r, $g, $b) = get_pixel($img1, $i, $j, $bg1);
      $hited1 = ($r != $r1 || $g != $g1  || $b != $b1 );
      list($r, $g, $b) = get_pixel($img2, $i - $rect1[0] + $rect2[0], $j - $rect1[2] + $rect2[2], $bg2);
      $hited2 = ($r != $r2 || $g != $g2  || $b != $b2 );
//      print $hited1."\n";
      if ($hited1 != $hited2) $notmatched ++;
    }
  }
  
  for ($i = $rect2[0]; $i< $rect2[1]; $i++) {
    for ($j = $rect2[2]; $j< $rect2[3]; $j++) {
      list($r, $g, $b) = get_pixel($img2, $i, $j, $bg2);
      $hited2 = ($r != $r2 || $g != $g2  || $b != $b2 );
      list($r, $g, $b) = get_pixel($img1, $i - $rect2[0] + $rect1[0], $j - $rect2[2] + $rect1[2], $bg1);
      $hited1 = ($r != $r1 || $g != $g1  || $b != $b1 );
      
      if ($hited1 != $hited2) $notmatched ++;
    }
  }
//print $notmatched."\n";
  return $notmatched * 50.0 / ($rect1[1] - $rect1[0]) / ($rect1[3] - $rect1[2]);
}
