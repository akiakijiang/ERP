<?php

/**
 * @brief 快递数据爬取函数 
 * @file lib_carrier.php
 */


if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

include_once('lib_http.php');

/* ---------------------------------------------------------*/
/**
    * @brief 取得快递信息 
    * 
    * @param $bill_no      快递单号
    * @param $carrier_id   快递的carried_id
    * @param $type         返回数据的类型
    * 
    * @return 
 */
/* ---------------------------------------------------------*/
function get_carrier_info($bill_no, $carrier_id, $type = 'detail') {
    global $db, $ecs;
    $sql = "SELECT context, shipping_status, details, UNIX_TIMESTAMP(last_modified) AS last_modified, last_modified_success, last_modified_result,details_format
  			FROM {$ecs->table('shipping_data')} 
  			WHERE bill_no = '$bill_no' 
  			LIMIT 1";
    $bill_res = $db->getRow($sql);
    if (!$bill_res  // 没有相关记录
        || ($bill_res['shipping_status'] != 'SIGNIN' 
            && $bill_res['shipping_status'] != 'REJECT' // 且不是收货或者拒收的
            && ($bill_res['last_modified_success'] == 0 || $bill_res['last_modified'] < time() - 10 * 60)
           )
        ) {
        
        switch ($carrier_id) {
            case 10:
            case 17:
                $search_res = get_sf_carrier_info($bill_no, $type);
                $shipping_code = 'sf';
                break;
            case 9:
            case 14:
                $search_res = get_ems_carrier_info($bill_no, $type);
                $shipping_code = 'ems';
                break;
            case 13:
            case 16:
                $search_res = get_fenghuo_carrier_info($bill_no, $type);
                $shipping_code = 'fh';
                break;
            case 3:
                $search_res = get_yto_carrier_info($bill_no, $type);
                $shipping_code = 'yto';
                break;
            case 21:
                $search_res = get_dhl_carrier_info($bill_no, $type);
                $shipping_code = 'dhl';
                break;
            default:
                break;
        }
        
        $bill_exist = $bill_res;
        $search_res['modify_suc'] = 1;
        $sql_err = "";
        $sql_update = "";
        $sql_shipping_time = "";
        if (!$search_res['details']) {
            $search_res['modify_suc'] = 0;
            $sql_err = " last_modified_result = '{$search_res['modify_err']}', ";
        } else {
            if ($search_res['shipping_status']) {
                if ($search_res['shipping_status'] === true) {
                    $search_res['shipping_status'] = 'SIGNIN';
                }
                $search_res['shipping_time'] = str_replace('&nbsp;', ' ', mysql_escape_string($search_res['shipping_time']));
                $sql_shipping_time = " shipping_time = '".mysql_escape_string($search_res['shipping_time'])."', ";
            }	else {
                $search_res['shipping_status'] = 'ONWAY';
            }
            
            $sql_update = 
                "context = '".mysql_escape_string($search_res['context'])."', 
                 shipping_status = '".mysql_escape_string($search_res['shipping_status'])."',
	  		     details = '".mysql_escape_string($search_res['details'])."',";
            $bill_res = $search_res;
        }

        if (!$bill_exist) {
            $sql_o = "
                SELECT o.order_id, o.order_sn
                FROM ecshop.ecs_order_info o
                LEFT JOIN romeo.order_shipment os ON os.order_id = o.order_id
                LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
                WHERE s.tracking_number = '{$bill_no}'
            ";
            $order_info = $db->getRow($sql_o); 
            $sql = "INSERT INTO {$ecs->table('shipping_data')}
	  			SET {$sql_update} {$sql_shipping_time} last_modified = NOW(), shipping_code = '$shipping_code',  $sql_err
	  				last_modified_success = '{$search_res['modify_suc']}', bill_no = '$bill_no', 
	  				order_id = '{$order_info['order_id']}' ,order_sn = '{$order_info['order_sn']}' ";
        } else {
            $sql = "UPDATE {$ecs->table('shipping_data')}
	  			SET {$sql_update} {$sql_shipping_time} last_modified = NOW(), shipping_code = '$shipping_code', $sql_err
	  				last_modified_success = '{$search_res['modify_suc']}' 
	  			WHERE bill_no = '$bill_no' LIMIT 1";
        }
        $db->query($sql);
    }
    switch ($type) {
        case 'detail' :
            if($bill_res['details_format'] == 'json') {
                $details = json_decode($bill_res['details']);
                if ($details->message == 'ok') {
                    $result = "";
                    foreach($details->data as $data){
                        $result .= "操作时间:".$data->time."&nbsp;&nbsp;邮件状态:".$data->context."<br/>";
                    }
                } else {
                    $result = $details->message;
                }
            } else {
                $result = $bill_res['details'];
            }
            break;
        case 'result' :
            $result = $bill_res['context'];
            break;
        case 'isok' :
            $result = ($bill_res['shipping_status'] == 'SIGNIN' || $bill_res['shipping_status'] === true);
            break;
    }
    
    return $result;
}

/* ---------------------------------------------------------*/
/**
    * @brief 取得顺丰快递信息 
    * 
    * @param $bill_no  快递单号
    * @param $type     返回数据类型
    * 
    * @return 
 */
/* ---------------------------------------------------------*/
function get_sf_carrier_info($bill_no, $type = 'detail') {
    include_once('lib_imagematcher.php');
    $ps = DIRECTORY_SEPARATOR;
    if(substr(strtolower(PHP_OS),0,3)=='win') {
        $temp_jpg_path = dirname(__FILE__)."{$ps}imagematcher{$ps}sf_tmp.jpg";
        $temp_path = dirname(__FILE__)."{$ps}imagematcher{$ps}sf_tmp.png";
    } else {
        $temp_jpg_path = "/tmp/sf_tmp.jpg";
        $temp_path = "/tmp/sf_tmp.png";
    }
        //$post = "tracklist={$bill_no}&checkimg={$code}";
    
    #$code = get_sf_imgcode($temp_jpg_path);
    $samples_file = dirname(__FILE__)."{$ps}imagematcher{$ps}sf-image-samples.txt";
    $code = get_sf_imgcode2($samples_file);
    $post = "verifycode={$code}";
    $post .= "&waybills={$bill_no}";

    //$content = get_page_content("http://219.134.187.248/sfwebc2_ch/result_c2_index.jsp", $post);
    $content = $content = get_page_content("http://kf.sf-express.com/css/myquery/queryBill.action", $post);
    //$content = iconv("GB2312", "UTF-8", $content);
    //preg_match('/<a name="head"><\/a>(.*)<\/body>/s',$content, $match);
    preg_match('/<body topmargin="0">(.*)<form id="queryImage"/s',$content, $match);
    $content =  $match[1]; //继续去掉其他的html
    $result['modify_err'] = mysql_escape_string($content);
    
//    preg_match_all('/<table [^>]*>(.*?)<\/table>/s', $content, $match);
    //$track_result = $match[1][1];
    //pp($track_result);
    //  if ($type == 'result') {
    //preg_match_all('/<td [^>]*>([^<>]*)<\/td>/s', $track_result, $match_result);
    //$result['context'] = trim($match_result[1][1]);
    //  	return $match_result[1][4];
    //  }

//    if ($type == 'isok') {
//        preg_match_all('/<td .*>(.*)<\/td>/Us', $track_result, $match_isok);
//        $isok = strtotime($match_isok[1][3]) > 0 ? true : false;
//        $result['shipping_status'] = $isok;
//        if ($isok) {
//            $result['shipping_time'] = trim($match_isok[1][3]);
//        }
//        return $isok;
//    }
    
    //preg_match_all('/<td [^>]*>([^<>]*)<\/td>/s', $track_result, $match);
    preg_match_all('/<td align="center" bgcolor="#F7F7F7">(.*)<\/td>/', $content, $match);
    $content = $match[1];
    if ($content[2] != ''){
        $result['context'] = $content[2];    //签收人的信息
    }

    $result['shipping_status'] = $content[3] !='' ? true : false;
    if ($type == 'isok'){
        return $result['shipping_status'];
    }

    if ( $content[3] != '') {
        $result['shipping_time'] = trim($content[3]);
    }
    
    //$content = strstr($content, '<br>');
    //$content = str_replace(array("<tr>", "</tr>", " "), array("<div>", "</div>",""), $content);
    //if ($content != "") {
    //    $content = "<div>".strip_tags($content,"<div>");
    //}
    $html = '';
    $len = count($content);
    for($i=5; $i<$len; $i++){
        if ($i%2 == 0){
            $html .= strip_tags($content[$i])."</div>";
        }else{
            $html .= "<div>".strip_tags($content[$i])."&nbsp;&nbsp;";
        }
    }
    $result['details'] = $html;
    return $result;
}

function get_sf_imgcode($temp_path){
    $i = 1;
    include_once('cls_get_imgcode.php');
    do {
        $i++;
        $img = get_page_content("http://kf.sf-express.com/css/loginmgmt/imgcode");
        $fp = fopen($temp_path, "w");
        fwrite($fp, $img);
        fclose($fp);
        $imgCode = new imageCode();
        $imgCode->setImage($temp_path);
        $imgCode->getHec();
        $code = $imgCode->run();
        //$code = parse($temp_path,'sf', 4, 2);
    } while( $code == false && $i < 4);
    return $code;
}

/**
 * 利用图片生成的特征码和特征码库做比较来匹配最佳字符
 *
 * @param string $cache_path 特征码库文件路径
 * @return string
 */
function get_sf_imgcode2($cache_path) {
	static $counter;
	if (!isset($counter)) {
		$counter = 1;
	}
	else {
		if ($counter++ > 4) {
			return '';
		}
	}
	
    // 创建顺丰验证码图片的操作句柄
    $temp_file = tempnam(sys_get_temp_dir(), 'SF');
    file_put_contents($temp_file, get_page_content("http://kf.sf-express.com/css/loginmgmt/imgcode"));
    $im = imagecreatefromjpeg($temp_file);
    $width  = imagesx($im) - 2;  // 图片宽
    $height = imagesy($im) - 2;  // 图片高
	
    // 去掉边框
    $dest = imagecreatetruecolor($width, $height);
    imagecopy($dest, $im, 0, 0, 1, 1, $width, $height);
    imagedestroy($im);
    $im = $dest;
	
    // 对图片进行采样分析
    $indexs = $pixels = array();
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($im, $x, $y);
            $colors = imagecolorsforindex($im, $rgb);
            $pixels[$x][$y] = $colors;
            $pixels[$x][$y]['index'] = $rgb;
            if (!isset($indexs[$rgb])) { $indexs[$rgb] = 1; }
        }
    }
    imagedestroy($im);
    unlink($temp_file);
	
    // 噪点分析
    $indexs = array_keys($indexs);
    $min = min($indexs);  // 0  黑色
    $max = max($indexs);  // 16777215  白色
    for ($y = 0; $y < $height; $y++) {   
	    for ($x = 0; $x < $width; $x++) {
            $rate = round($pixels[$x][$y]['index']/$max, 2);
            if ($rate > 0.41)
                $pixels[$x][$y]['off'] = 1;  // 和深色色差过大的点， 标记为噪点
        }
    }
    for ($y = 0; $y < $height; $y++) {   
        for ($x = 0; $x < $width; $x++) {
            $around = 0;
            foreach (array(-1, 0, 1) as $xv) {
                foreach (array(-1, 0, 1) as $yv) {
                    $_x = $x + $xv;
                    $_y = $y + $yv;
                    if (($xv == 0 && $yv == 0) || $pixels[$_x][$_y]['off']) {
                        continue;
                    }
                    $around++;
                }
            }
            if ($around == 0) {
                $pixels[$x][$y]['off'] = 1;  // 孤立的点，标记为噪点
            }
        }
    }
	
    // 采样
    $samples = array();
    // 取得有效像素点的x坐标集合（即每一列都存在至少一个有效点）
    $_x = array();
    for ($x = 1; $x < $width; $x++) {
        for ($y = 1; $y < $height; $y++) {
            if (!$pixels[$x][$y]['off']) {
                $_x[] = $x;
                break;
            }
        }
    }
    // 将连续的x坐标分组， 每一组即为一个字符的x坐标的连续，分出来有几组则表示有几个字母
    $_d = array();  
    $_c = count($_x);
    for ($i = 0, $k = 0; $i < $_c; $i++, $k++) {
        $_d[$k][] = $_x[$i];
        for ($j = $i+1; $j <= $_c; $j++) {
            if ($_x[$j] == $_x[$i] + 1) {
                $_d[$k][] = $_x[$j];
                $i++;
            }
            else {
                break;
            }
        }
    } 
    // 取得每个字符的最小和最大y坐标
    foreach ($_d as $k => $_x) {
        $_y = array();
        foreach ($_x as $x) {
            for ($y = 1; $y < $height; $y++) {
                if (!$pixels[$x][$y]['off']) {
                    array_push($_y, $y);
                }
            }
        }
        if (count($_x) > 1 && count($_y) > 1) {
            $samples[$k] = array(
               'x1' => min($_x),  // 字符的左边x坐标
               'x2' => max($_x),  // 字符的右边x坐标
               #'y1' => min($_y),
               #'y2' => max($_y)
            );
            // 得到该字符的特征码
            $code = '';
            for ($x = $samples[$k]['x1']; $x <= $samples[$k]['x2']; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $code .= $pixels[$x][$y]['off'] ? '0' : '1';
                }
            }
            $samples[$k]['code'] = $code;
        }
    }

    // 匹配命中字符
    $hits = array();
    if (count($samples) == 4 && ($fp = @fopen($cache_path, 'r'))) {
        foreach ($samples as $key => $sample) {
            $p = 0;
            rewind($fp);
            
            while (!feof($fp)) {
                $buffer = fgets($fp, 1024);
                if (empty($buffer)) { 
                    continue; 
                }
                $tmp = explode('|', $buffer);
                if (!isset($tmp[1]) || empty($tmp[1])) {
                    continue;
                }
		        
                $percent = 0;
                similar_text($sample['code'] , $tmp[0], $percent);
                if ($percent > 80 && $percent > $p) {
                    $p = $percent;
                    $hits[$key] = trim($tmp[1]);
                }
            }
        }
        fclose($fp);
    }
    
    return $hits && count($hits) == count($samples) ? implode($hits) : get_sf_imgcode2($cache_path);
}

/* ---------------------------------------------------------*/
/**
    * @brief 取得ems的快递信息 
    * 
    * @param $bill_no
    * @param $type
    * 
    * @return 
 */
/* ---------------------------------------------------------*/
function get_ems_carrier_info($bill_no, $type = 'detail') {
    include_once('lib_imagematcher.php');
    $ps = DIRECTORY_SEPARATOR;
    if(substr(strtolower(PHP_OS),0,3)=='win') {
        $temp_jpg_path = dirname(__FILE__)."{$ps}imagematcher{$ps}ems_tmp.jpg";
        $temp_path = dirname(__FILE__)."{$ps}imagematcher{$ps}ems_tmp.png";
    } else {
        $temp_path = "/tmp/ems_tmp.png";
        $temp_jpg_path = "/tmp/ems_tmp.jpg";
    }
    $i = 1;
    do {
        $i++;
        $img = get_page_content("http://www.ems.com.cn/servlet/ImageCaptchaServlet");
        $fp = fopen($temp_jpg_path, "w");
        fwrite($fp, $img);
        fclose($fp);
        imageprocess($temp_jpg_path, $temp_path);
        $code = parse($temp_path, 'ems', 5, 0);
    } while( $code == false && $i < 4);
    $content = get_page_content("http://www.ems.com.cn/qcgzOutQueryAction.do?reqCode=gotoSearch");
    preg_match('/name=\"myEmsbarCode\" value=\"(\d*)\"\/>/',$content, $myEmsbarCode);
    preg_match('/input name=\"(.*)\" type=\"text\" maxlength=\"5\" class=\"input1\"/',$content, $randcode);
    $post = "reqCode=browseBASE&myEmsbarCode={$myEmsbarCode[1]}&mailNum={$bill_no}&{$randcode[1]}={$code}&optijiaot.x=10&optijiaot.y=11";
    $content = get_page_content("http://www.ems.com.cn/qcgzOutQueryAction.do", $post);
    $content = iconv("GB2312", "UTF-8", $content);
    $result['modify_err'] = mysql_escape_string($content);
    preg_match('/投递结果：<.*>(.*)<.*>/', $content, $match);
    $result['context'] = $match[1];
    //  if ($type == 'result') {
    //  	return $match[1];
    //  }
    //  if ($type == 'isok') {
    preg_match('/您的邮件于(.*)<br \/>/', $content, $match_isok);
    //ncchen 090329 增加收件时间
    preg_match_all('/<span class=\"txt-basic-info-e-time\">(.*)<\/span>/sU',$match_isok[1], $match_result);
    //    print $match[1];
    $isok = (strpos($match_isok[1],'已妥投') !== false) ? true : false ;
    $result['shipping_status'] = $isok;
    //ncchen 090329 增加收件时间
    if ($isok) {
        $result['shipping_time'] = trim($match_result[1][0]);
    }
    //  	return $is_ok;
    //  }
    preg_match('/<table width=\"560\" height=\"80\".*>(.*)<\/table>/sU', $content, $match);
    $content = $match[0];

    $content = str_replace(array('<tr align="center">', '</tr>',"\r\n0fe8\r\n"), array('<div>', '</div>' ,''), $content);
    if ($content != "") {
        $content = "<div>".strip_tags($content,"<div>");
    }
    $result['details'] = $content;
    return $result;
}


/* ---------------------------------------------------------*/
/**
    * @brief 取得万象快递的信息 
    * 
    * @param $bill_no 快递单号
    * @param $type
    * 
    * @return 
 */
/* ---------------------------------------------------------*/
function get_fenghuo_carrier_info($bill_no, $type = 'detail') {
    $post = "orderId={$bill_no}"; // by mzhou 20091113，万象快递网站更改
    $content = get_page_content("http://www.ewinshine.com/ewinshine/ewinshineorderactionlist.action", $post);
    $result['modify_err'] = mysql_escape_string($content);

    //preg_match('/<table align="center" class="table_item">(.*)<\/table>/sU', $content, $match);
    preg_match('/<p style="color:green;">运单号：\d+<\/p>(.*)<p style="color:green;">备注：/sU', $content, $match);
    $content = $match[1];
    //pp($match);
    //$content = str_replace(' bgcolor="#b5daff"', '', $content);
    //preg_match_all('/<td><span class="span_line">(.*)<\/span>\r\n<\/td>/sU', $content, $match);
    preg_match_all('/<td\s*><span\s*>(.*)<\/span><\/td>/sU', $content, $match);
    $end = count($match[1]);
    $result['context'] = $match[1][$end-1];
    //  if ($type == 'result') {
    //  	return $match[1][$end-1];
    //  }

    //  if ($type == 'isok') {
    $isok = trim($match[1][$end-1]) == '配送成功确认' ? true : false;
    //  	return $isok;
    //  }
    $result['shipping_status'] = $isok;
    //ncchen 090329 增加收件时间
    if ($isok) {
        $result['shipping_time'] = trim($match[1][$end-2]);
    }
    $content = "";
    $i =0;
    if (count($match[1] > 3)) {
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
    }
    $result['details'] = $content;
    return $result;
}

/**
 * 查询圆通快递的信息
 *
 * @param string $bill_no
 * @param string $type
 * @return array
 */

function get_yto_carrier_info($bill_no, $type = 'detail') {
    $url = "http://www.yto.net.cn/service/sql.aspx";
    $post = "NumberText={$bill_no}&loginvip.x=3&loginvip.y=19&yanma=dsfd";
    
    $content = get_page_content($url, $post);
    $content = iconv("GB2312", "UTF-8", $content);

    $result['modify_err'] = mysql_escape_string($content);

    // 获得查询出来的快递信息区域
    preg_match('/id="GridView1"[^<]*>(.*)<\/table>/sU', $content, $match);
    $content = $match[1];
    $content = str_replace("<td>{$bill_no}</td>", '', $content);
    $content = str_replace('<th scope="col">快件单号</th>', '', $content);
    
    $result['details'] = $content;
    preg_match_all('/<tr>\s*<td>(.*)<\/td><td>(.*)<\/td>\s*<\/tr>/sU', $content, $match);
    
    $end = count($match[1]);
    $result['context'] = $match[2][$end-1];

    // 判断快递是否签收
    $isok = strpos($match[2][$end-1], '签收人') ? true : false;
    $result['shipping_status'] = $isok;

    // 签收了记录签收时间
    if ($isok) {
        $result['shipping_time'] = trim($match[1][$end-1]);
    }
    
    // 将他们的样式替换掉
    $result['details'] = str_replace(
    array('<th scope="col">', '</th>', '<td>', '</td>', '<tr>', '</tr>'),
    array(' ', ' ', ' ', ' ', '<div>', '</div>'),
    $result['details']
    );
    return $result;
}



/**
 * 根据tagName获得对应的nodeValue
 * @param object domnode
 * @param string $tagName
 × @return string
 *
 */
function getElementsByTagNameValue($obj, $tagName) {
    if (!$obj) {
        return null;
    }
    
    $tagObj = $obj->getElementsByTagName($tagName);
    if (!$tagObj) {
        return null;
    }
    
    return $tagObj->item(0)->nodeValue;
}


/**
 * 获得dhl的快递信息
 * @param string $bill_no
 * @param string $type
 × @return mixed
 */
function get_dhl_carrier_info($bill_no, $type = 'detail') {
    $serverUrl = "https://xmlpi-ea.dhl.com/XMLShippingServlet";
    $awb = $bill_no;
    $result = array();
    
    $data = @file_get_contents(dirname(__FILE__) . "/dhl/TrackRequest.xml");
    
    $data = str_replace(array('{AWBNumber}', '{MessageTime}'), array($awb, date("c")), $data);
    
    $timeout = 60;
    $ch = curl_init($serverUrl);  
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727)");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    $content = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if (!$content) {
        $result['details'] = null;
        $result['modify_err'] = $content;
        return $result;
    }
    
    $doc = new DOMDocument();
    $doc->loadXML($content);
    $xpath = new DOMXPath($doc);
    
    $actionStatus = $xpath->query("//req:TrackingResponse/AWBInfo[1]/Status/ActionStatus")->item(0)->nodeValue;
    
    $result['shipping_status'] = "ONWAY";
    if ($actionStatus == 'success') { // 正常返回结果
        $content = "";
        $shipmentEvents = $xpath->query("//req:TrackingResponse/AWBInfo[1]/ShipmentInfo/ShipmentEvent");
        for ($i = 0; $i < $shipmentEvents->length; $i++) {
            $shipment = $shipmentEvents->item($i);
            
            $date = getElementsByTagNameValue($shipment, "Date");
            $time = getElementsByTagNameValue($shipment, "Time");
            
            $eventCode = getElementsByTagNameValue($shipment->getElementsByTagName("ServiceEvent")->item(0), "EventCode");
            $description = getElementsByTagNameValue($shipment->getElementsByTagName("ServiceEvent")->item(0), "Description");
            $description = preg_replace("/\s+/", ' ', $description);
            
            $areaCode = getElementsByTagNameValue($shipment->getElementsByTagName("ServiceArea")->item(0), "ServiceAreaCode");
            $areaDescription = getElementsByTagNameValue($shipment->getElementsByTagName("ServiceArea")->item(0), "Description");
            $areaDescription = preg_replace("/\s+/", ' ', $areaDescription);
            
            if ($eventCode == 'OK' || $eventCode == 'DL') {
                $result['shipping_status'] = "SIGNIN";
                $result['context'] = getElementsByTagNameValue($shipment, "Signatory");
                $result['shipping_time'] = "{$date} {$time}";
            }
            
            if ($eventCode == 'RD' || $eventCode == 'RT') {
                $result['shipping_status'] = "REJECT";
            }
            
            $content .= "{$date} {$time} {$eventCode} {$description} {$areaDescription}({$areaCode}) <br />";
        }
        
        $result['details'] = $content;
        
    } else { // 非正常返回结果
        $result['details'] = null;
        $result['modify_err'] = $content;
    }
    
    return $result;
}