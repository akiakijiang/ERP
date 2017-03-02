<?php
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

include_once('lib_http.php');

function get_carrier_info($bill_no, $carrier_id, $type = 'detail') {
  switch ($carrier_id) {
    case 10:
    case 17:
      $result = get_sf_carrier_info($bill_no, $type);
  	  break;
    case 9:
    case 14:
      $result = get_ems_carrier_info($bill_no, $type);
      break;
    case 13:
    case 16:
      $result = get_fenghuo_carrier_info($bill_no, $type);
      break;
    default:
      break;
  }
  return $result;
}

function get_sf_carrier_info($bill_no, $type = 'detail') {
  include_once('lib_imagematcher.php');
  $ps = DIRECTORY_SEPARATOR;
  if(substr(strtolower(PHP_OS),0,3)=='win') {
    $temp_path = dirname(__FILE__)."{$ps}imagematcher{$ps}sf_tmp.png";
  } else {
  	$temp_path = "/tmp/sf_tmp.png";
  }
  
  
  $img = get_page_content("http://58.251.25.71/sfwebc2_ch/getCheckImg");  
  $fp = fopen($temp_path, "w");
  fwrite($fp, $img);
  fclose($fp);
  
  $code = parse($temp_path,'sf');
  $post = "tracklist={$bill_no}&checkimg={$code}";
  $content = get_page_content("http://58.251.25.71/sfwebc2_ch/result_c2_index.jsp", $post);
  $content = iconv("GB2312", "UTF-8", $content);
  preg_match('/<a name="head"><\/a>(.*)<\/body>/s',$content, $match);
  $content =  $match[1]; //继续去掉其他的html
  
  preg_match_all('/<table [^>]*>(.*?)<\/table>/s', $content, $match);
  
  $track_result = $match[1][1];
  
  if ($type == 'result') {
    preg_match_all('/<td [^>]*>([^<>]*)<\/td>/s', $track_result, $match);
  	return $match[1][4];
  }
  
  if ($type == 'isok') {
    preg_match_all('/<td [^>]*>([^<>]*)<\/td>/s', $track_result, $match);
  	$isok = strtotime($match[1][4]) > 0 ? true : false;
  	return $isok;
  }
  
  $content =  $match[1][2];
  preg_match_all('/<td [^>]*>([^<>]*)<\/td>/s', $track_result, $match);
  $content = strstr($content, '<br>');
  $content = str_replace(array("<tr>", "</tr>", " "), array("<div>", "</div>",""), $content);
  $content = "<div>".strip_tags($content,"<div>");
  
  return $content;
}


function get_ems_carrier_info($bill_no, $type = 'detail') {
  $content = get_page_content("http://www.ems.com.cn/qcgzOutQueryAction.do?reqCode=gotoSearch");
  preg_match('/name=\"myEmsbarCode\" value=\"(\d*)\"\/>/',$content, $match);
  
  $post = "reqCode=browseBASE&myEmsbarCode={$match[1]}&mailNum={$bill_no}&optijiaot.x=10&optijiaot.y=11";
  $content = get_page_content("http://www.ems.com.cn/qcgzOutQueryAction.do", $post);
  $content = iconv("GB2312", "UTF-8", $content);
  
  preg_match('/投递结果：<.*>(.*)<.*>/', $content, $match);
  if ($type == 'result') {
  	return $match[1];
  }
  if ($type == 'isok') {
    preg_match('/您的邮件于(.*)<br \/>/', $content, $match);
//    print $match[1];
  	$is_ok = (strpos($match[1],'已妥投') !== false) ? true : false ;
  	return $is_ok;
  }
  
  preg_match('/<table width=\"560\" height=\"80\".*>(.*)<\/table>/sU', $content, $match);
  $content = $match[0];
  
  $content = str_replace(array('<tr align="center">', '</tr>',"\r\n0fe8\r\n"), array('<div>', '</div>' ,''), $content);
  $content = strip_tags($content,"<div>");
  return $content;
}


function get_fenghuo_carrier_info($bill_no, $type = 'detail') {
  $post = "order_codes={$bill_no}";
  $content = get_page_content("http://www.efunhome.com:8000/efunhome/trace.do", $post);
  
  preg_match('/<table align="center" class="table_item">(.*)<\/table>/sU', $content, $match);
  $content = $match[0];
  $content = str_replace(' bgcolor="#b5daff"', '', $content);
  preg_match_all('/<td><span class="span_line">(.*)<\/span>\r\n<\/td>/sU', $content, $match);
  
  $end = count($match[1]);
  if ($type == 'result') {
  	return $match[1][$end-1];
  }
  
  if ($type == 'isok') {
  	$isok = trim($match[1][$end-1]) == '配送成功确认' ? true : false;
  	return $isok;
  }
  
  $content = "";
  $i =0;
  foreach ($match[1] as $m) {
    if ($i == 0) {
    	$content .= "<div>";
    }
    $i++;
    $content .= " ".$m;
    if ($i == 3) {
    	$content .= "</div>";
    	$i = 0;
    }
  }
  
  return $content;
}