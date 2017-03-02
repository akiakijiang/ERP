<?php
if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

function get_page_content($url, $post_data=""){
  static $http_cookie;
  
  $url = eregi_replace('^http://', '', $url);
  $temp = explode('/', $url);
  $host = array_shift($temp);
  $path = '/'.implode('/', $temp);
  $temp = explode(':', $host);
  $host = $temp[0];
  $port = isset($temp[1]) ? $temp[1] : 80;
  $fp = @fsockopen($host, $port, &$errno, &$errstr, 30);
  if ($fp){
    if ($post_data) {
      $header = "POST $path HTTP/1.1\r\n";
      $header .= "Referer:$url\r\n";
      $header .= "Accept-Language: zh-cn\r\n";
      $header .= "Content-Type: application/x-www-form-urlencoded\r\n";//      $header .= "Accept: */*\r\n";
      $header .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)\r\n";
      $header .= "Host: $host\r\n";
      $header .= "Content-Length: ".strlen($post_data)."\r\n";
      if ($http_cookie[$host]) {
      	$header .= "Cookie: {$http_cookie[$host]}\r\n";
      }
      $header .= "Connection: Close\r\n\r\n";
    	@fputs($fp, $header.$post_data);
    } else {
      $header = "GET $path HTTP/1.0\r\n";
      $header .= "Host: $host\r\n";
      $header .= "Accept: image/gif\r\n";
      $header .= "Accept-Language: zh-cn\r\n";
      $header .= "Referer:$url\r\n";
      $header .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)\r\n";
      if ($http_cookie[$host]) {
      	$header .= "Cookie: {$http_cookie[$host]}\r\n";
      }
      
      $header .= "Connection: Close\r\n\r\n";
      
      @fputs($fp, $header);
    }
    
  }
  
//  print $header;

  $Content = '';
  while ($str = @fread($fp, 4096)){
    $Content .= $str;
  }
  @fclose($fp);
  
//  $chunk_size = (integer)hexdec(fgets( $fp, 4096 ) );
//  while(!feof($fp) && $chunk_size > 0) {
//    $Content .= fread($fp, $chunk_size );
////    fread($fp, 2 ); // skip \r\n
//    $chunk_size = (integer)hexdec(fgets( $fp,  4096) );
//  }
  
  //重定向
  if(preg_match("/^HTTP\/\d.\d 301[^\r\n]*/is",$Content) || preg_match("/^HTTP\/\d.\d 303[^\r\n]*/is",$Content)){
    if(preg_match("/Location:(.*?)\r\n/is",$Content,$murl)){
      return get_page_content($murl[1]);
    }
  }
  
  //读取内容
//  print $Content;
  if(preg_match("/^HTTP\/\d.\d 200 OK/is",$Content)){
    preg_match("/Content-Type:(.*?)\r\n/is",$Content,$murl);
    $contentType=trim($murl[1]);
    $Content_array=explode("\r\n\r\n",$Content,2);
    $Content=$Content_array[1];
    
    $headerinfo = $Content_array[0];
    if (preg_match("/Set-Cookie:/is",$headerinfo)) {
    	preg_match_all("/Set-Cookie:(.*);[^\r\n]*/iU",$headerinfo,$match);
    	$http_cookie[$host] = join(";", $match[1]);
    }
  }
  
  return $Content;
}