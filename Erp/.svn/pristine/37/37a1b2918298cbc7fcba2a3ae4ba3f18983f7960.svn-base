<?php

/**
 * fedex相关库
 * @author Zandy
 * 
 * 注意：需要定时删除 templates/caches/fedex/ 目录下的图片
 * cronjob 例子：* * * * * root `find /var/www/http/erp/templates/caches/fedex/ -mtime +1 | xargs -I{} rm -f` 
 */

include_once dirname(__FILE__) . '/../function.php';
include_once dirname(__FILE__) . '/lib_order.php';
include_once dirname(__FILE__) . '/fedex/fedex.php';

function convert_deutsch_to_ascii($str)
{
	$array = array(
		'Ü' => 'Ue', 
		'ü' => 'ue', 
		'Ä' => 'Ae', 
		'ä' => 'ae', 
		'Ö' => 'Oe', 
		'ö' => 'oe', 
		'ß' => 'ss'
	);
	return str_replace(array_keys($array), array_values($array), $str);
}

/**
 * 将pdf转成图片
 *
 */
function fedex_ship_pdf_to_image($pdf_file, $awb_folder, $awb) {
	#$cmd = "convert -density 300x300 -crop 1200x2380+2000+45 {$pdf_file} {$awb_folder}/$awb.png";
	if (is_array($pdf_file)) {
		foreach ($pdf_file as $k => $v) {
			$cmd = "sudo convert -density 300x300 -crop 1200x2008+125+0 {$v} {$awb_folder}$awb$k.png";
			//var_dump($cmd);
			`$cmd`;
		}
	} else {
		$cmd = "sudo convert -density 300x300 -crop 1200x2008+125+0 {$pdf_file} {$awb_folder}$awb.png";
		//var_dump($cmd);
		`$cmd`;
	}

	$image_paths = array();
	$handle = opendir($awb_folder);
	if ($handle) {
		while (false !== ($file = readdir($handle))) {
			if (substr($file, -4) == '.png') {
				$image_paths[] = $file;
			}
			//var_dump($file);
		}
	}

	sort($image_paths);
	return $image_paths;
}

function convert_adderess_to_fedex($order_id)
{
	global $db;
	
	$getOrderInfo = getOrderInfo($order_id);
	$order_attrs = get_order_attribute_list($order_id, null);
	$order_attributes = array();
	if ($order_attrs)
	{
		foreach ($order_attrs as $attr_name => $attr_value)
		{
			$order_attributes[$attr_name] = $attr_value[0]['attr_value'];
		}
	}
	
	$CompanyName = $getOrderInfo['consignee'];
	$AttentionName = $getOrderInfo['consignee'];
	$PhoneNumber = $getOrderInfo['tel'];
	$AddressLine = $getOrderInfo['address'];
	$AddressLine = htmlspecialchars($AddressLine);
	$City = $getOrderInfo['city_name'] ? $getOrderInfo['city_name'] : ($order_attributes['city_text'] ? $order_attributes['city_text'] : '*');
	$StateProvince = $getOrderInfo['province_name'] ? $getOrderInfo['province_name'] : ($order_attributes['province_text'] ? $order_attributes['province_text'] : '*');
	$Country = $getOrderInfo['country_name'];
	$PostalCode = $getOrderInfo['zipcode'];
	
	// 德语
	if ($order_attributes['language_id'] == 2)
	{
		$CompanyName = convert_deutsch_to_ascii($CompanyName);
		$AttentionName = convert_deutsch_to_ascii($AttentionName);
		$AddressLine = convert_deutsch_to_ascii($AddressLine);
		$City = convert_deutsch_to_ascii($City);
		$StateProvince = convert_deutsch_to_ascii($StateProvince);
	}
	
	// {{{ 处理地址过长的情况
	$AddressLine1 = $AddressLine2 = $AddressLine3 = '';
	if (strlen($AddressLine) > 35)
	{
		$_tmp = explode(" ", $AddressLine);
		$_a = array();
		foreach ($_tmp as $_k => $_t)
		{
			$_a[] = trim($_t);
			if (strlen(join(" ", $_a)) >= 33)
			{
				array_pop($_a);
				if ($AddressLine1 == '')
				{
					$AddressLine1 = join(" ", $_a);
				}
				elseif ($AddressLine2 == '')
				{
					$AddressLine2 = join(" ", $_a);
				}
				else
				{
					$AddressLine3 = join(" ", $_a);
				}
				$_a = array();
				$_a[] = trim($_t);
			}
		}
	}
	if ($AddressLine2 && $_a)
	{
		$AddressLine3 = join(" ", $_a);
	}
	elseif ($AddressLine1 && $_a)
	{
		$AddressLine2 = join(" ", $_a);
	}
	if (!$AddressLine1)
	{
		$AddressLine1 = $AddressLine;
	}
	
	$AddressLine_array = array();
	$AddressLine_array[] = $AddressLine1;

	if ($AddressLine3)
	{
		###echo "有错，地址太长，fedex可能无法打印。$AddressLine3\n";
		//$AddressLine_array[] = $AddressLine3;
		$AddressLine2 .= ' ' . $AddressLine3;
	}
	if ($AddressLine2)
	{
		$AddressLine_array[] = $AddressLine2;
	}
	// }}}
	
	if (!$Country)
	{
		echo "国家名称为空！无法继续使用。";
		die();
	}
	
	switch ($Country)
	{
		case "United States":
		case "Canada":
		case "Ireland":
			$sql = "select * 
                from 
                	fedex_region
                where
                	region_name like '$Country%' 
                	AND is_ship_to = 'Y' ";
			$fedex_region_list = $db->getAll($sql);
			if (count($fedex_region_list) > 1)
			{
				foreach ($fedex_region_list as $_fedex_region)
				{
					#if (strtoupper($_fedex_region['state_name']) == strtoupper($StateProvince))
					if (stripos($_fedex_region['state_name'], $StateProvince) === 0)
					{
						$fedex_region = $_fedex_region;
					}
				}
			}
			else
			{
				$fedex_region = $fedex_region_list[0];
			}
			
			$CountryCode = $fedex_region['region_code'];
			$City = $CountryCode ? $City . ($City && $StateProvince ? ',' : '') . $StateProvince : $City;
			$StateProvinceCode = $fedex_region['state_code'];
			
			if (strtoupper($Country) == strtoupper('Ireland'))
			{
				$StateProvinceCode = $CountryCode;
			}
			
			break;
		
		default:
			// 下面这几个国家不能使用 like '%{$Country}%' 该条件不能确定一个国家  因为有类似的国家名
			if (in_array(strtoupper($Country), array(
				'CONGO', 
				'DOMINICA', 
				'NIGER'
			)))
			{
				$region_condition = " region_name like '{$Country}' ";
			}
			else
			{
				$region_condition = " region_name like '%{$Country}%' ";
			}
			
			$sql = "select * 
                from 
                	fedex_region
                where
	                {$region_condition} 
	                AND is_ship_to = 'Y' ";
			$fedex_region_list = $db->getAll($sql);
			if (count($fedex_region_list) > 1)
			{
				foreach ($fedex_region_list as $_fedex_region)
				{
					#if (strtoupper($_fedex_region['state_name']) == strtoupper($StateProvince))
					if (stripos($_fedex_region['state_name'], $StateProvince) === 0)
					{
						$fedex_region = $_fedex_region;
					}
				}
			}
			else
			{
				$fedex_region = $fedex_region_list[0];
			}
			
			$CountryCode = $fedex_region['region_code'];
			$City = $CountryCode ? $City . ($City && $StateProvince ? ',' : '') . $StateProvince : $City;
			$StateProvinceCode = $fedex_region['state_code'];
			
			break;
	}
	
	if (!$CountryCode)
	{
		echo "无法获得国家代码({$Country})";
		die();
	}
	
	/* for test
	$CountryCode = 'US';
	$StateProvinceCode = 'MD';
	$PostalCode = '21093';
	*/

	$ShipTo = array(
			'Contact' => array(
					'PersonName' => $CompanyName,
					'CompanyName' => $AttentionName,
					'PhoneNumber' => $PhoneNumber
			),
			'Address' => array(
					/*
					'StreetLines' => array(//最多2行
							'Address Line 1g',//每行最多35字符,剩余自动截断
							'abcdef'//每行最多35字符,剩余自动截断
					),
					*/
					'StreetLines' => $AddressLine_array,
					'City' => $City,
					'StateOrProvinceCode' => $StateProvinceCode,
					'PostalCode' => $PostalCode,
					'CountryCode' => $CountryCode,
					'Residential' => false
			)
	);
	
	return $ShipTo;
}

/**
 * 向fedex请求，并打印fedex的快递单
 * @param int $order_id
 * @param int $pieces
 * @return string $request_content
 */
function fedex_ship_request($order_id, $pieces, $param = array())
{
	
	$shipclient_wsdl = dirname(__FILE__) . '/fedex/ShipService_v10.wsdl';

	// {{{
	$data['PackageCount'] = $pieces;
	/*$data['TotalWeight'] = array(
			'Value' => 103.0,
			'Units' => 'LB'
	);*/
	$data['TotalWeight'] = array(
			'Value' => 0.5,
			'Units' => 'KG'
	);
	
	/*
	$data['Recipient'] = array(
			'Contact' => array(
					'PersonName' => 'guan 2',
					'CompanyName' => 'g Company Name',
					'PhoneNumber' => '1234567890'
			),
			'Address' => array(
					'StreetLines' => array(//最多2行
							'Address Line 1g',//每行最多35字符,剩余自动截断
							'abcdef'//每行最多35字符,剩余自动截断
					),
					'City' => 'Richmond',
					'StateOrProvinceCode' => 'BC',
					'PostalCode' => 'V7C4V4',
					'CountryCode' => 'CA',
					'Residential' => false
			)
	);
	*/
	
	// 地址
	$data['Recipient'] = convert_adderess_to_fedex($order_id);
	
	// desc 信息，这里显示品名
	$pinming = isset($param['pinming']) && $param['pinming'] ? $param['pinming'] : '';
	if (!$pinming) {
		return array(
				'code' => 1,
				'msg' => "请设置品名"
		);
	}
	// 申报价值
	$declaredValue = isset($param['declaredValue']) && $param['declaredValue'] ? $param['declaredValue'] : 0;
	if (!$declaredValue) {
		return array(
				'code' => 2,
				'msg' => "申报价值有问题，设置的值为：$declaredValue"
		);
	}
	
	$data['CustomerClearanceDetail'] = array(
	
			'CustomsValue' => array(
					'Currency' => 'USD',
					'Amount' => $declaredValue
			),
			'Commodities' => array(
					'0' => array(
							'NumberOfPieces' => 1,
							'Description' => $pinming, // 品名
							'CountryOfManufacture' => 'CN',
							/*'Weight' => array(
									'Units' => 'LB',
									'Value' => 1.0
							),*/
							'Weight' => array(
									'Units' => 'KG',
									'Value' => 0.5
							),
							'Quantity' => 1,
							'QuantityUnits' => 'EA',
							'UnitPrice' => array(
									'Currency' => 'USD',
									'Amount' => 102.000000
							),
							'CustomsValue' => array(
									'Currency' => 'USD',
									'Amount' => 400.000000
							)
					)
			)
	);
	// }}}

	$mdir = 'templates/caches/fedex/' . microtime(true) . '_' . mt_rand(100000, 999999) . '/';
	
	!defined("ROOT_PATH") && define('ROOT_PATH', str_replace('admin/includes/lib_fedex.php', '', str_replace('\\', '/', __FILE__)));
	$WEB_ROOT = substr(realpath(dirname(__FILE__).'/../../'), strlen(realpath($_SERVER['DOCUMENT_ROOT'])));
	if (trim($WEB_ROOT, '/\\')) {
		$WEB_ROOT = '/'.trim($WEB_ROOT, '/\\').'/';
	} else {
		$WEB_ROOT = '/';
	}
	$WEB_ROOT = str_replace("\\", "/", $WEB_ROOT);
	!defined("WEB_ROOT") && define('WEB_ROOT', $WEB_ROOT);
	
	$img_web_root = WEB_ROOT . $mdir;
	$img_root_path = ROOT_PATH . $mdir;

	if (!file_exists($img_root_path))
	{
		mkdir($img_root_path, 0777, true);
	}
	@chmod($img_root_path, 0777);
	
	// {{{
	$pdf_files = array();
	
	$PackageLineItems = array();
	for ($i = 1; $i <= $pieces; $i++) {
		$data['PackageLineItem'] = array(
				'SequenceNumber' => $i,
				'GroupPackageCount' => 1,
				/*'Weight' => array(
						'Value' => 20.0,
						'Units' => 'LB'
				)*/
				'Weight' => array(
						'Value' => 0.5,
						'Units' => 'KG'
				)
		);
		$result = shipClient($shipclient_wsdl, $data);
		
		$data['MasterTrackingId'] = $result['data']['MasterTrackingId'];
		
		if ($result['code'] == 1)
		{
			print_r($result);
		}
		
		$pdf_file = $img_root_path . 'shipexpresslabel' . $i . '.pdf';
		$fp = fopen($pdf_file, 'wb');
		fwrite($fp, $result['data']['Image']);
		fclose($fp);
		
		$pdf_files[] = $pdf_file;
		
	}
	// }}}
	
	$imgs = fedex_ship_pdf_to_image($pdf_files, $img_root_path, "fedex");
	//var_dump($pdf_files, $img_root_path, $imgs);

	$images_html = array();
	foreach ($imgs as $img) {
		$label_image_url = $img_web_root . $img;
		$images_html_tmp = <<<image_html
<table border="0" cellpadding="0" cellspacing="0"><tr>
<td align="left" valign="top">
<IMG SRC="$label_image_url" width="400" height="650">
</td>
</tr></table>
image_html;
		$images_html[] = $images_html_tmp;
	}
	
	// 旋转为纵向
	$images_html = join("", $images_html);
	$imghtml = <<<html
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 3.2//EN">
<html><head><title>
View/Print Label</title></head><style>
    .small_text {font-size: 80%;}
    .large_text {font-size: 115%;}
</style>
<body bgcolor="#FFFFFF" onload="window.print();">
$images_html
</body>
</html>
html;
	
	return array(
		'code' => 0, 
		'msg' => $imghtml
	);

}


// $a = fedex_ship_request(959122, 1, $param = array('pinming' => 'Dress'));
// print_r($a['msg']);



