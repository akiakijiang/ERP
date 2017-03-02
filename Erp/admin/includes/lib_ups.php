<?php

/**
 * ups相关库
 * @author Zandy
 * 
 * 注意：需要定时删除 templates/caches/ups/ 目录下的图片
 * cronjob 例子：* * * * * root `find /var/www/http/erp/templates/caches/ups/ -mtime +1 | xargs -I{} rm -f` 
 */

include_once dirname(__FILE__) . '/../function.php';
include_once dirname(__FILE__) . '/lib_order.php';

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

function convert_adderess_to_ups($order_id)
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
	$AddressLine2_xml = $AddressLine3_xml = '';
	if ($AddressLine2)
	{
		$AddressLine2_xml = "<AddressLine2>  {$AddressLine2}</AddressLine2>";
	}
	if ($AddressLine3)
	{
		$AddressLine3_xml = "<AddressLine3>  {$AddressLine3}</AddressLine3>";
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
                	ups_region
                where
                	region_name like '$Country%' ";
			$ups_region_list = $db->getAll($sql);
			if (count($ups_region_list) > 1)
			{
				foreach ($ups_region_list as $_ups_region)
				{
					#if (strtoupper($_ups_region['state_name']) == strtoupper($StateProvince))
					if (stripos($_ups_region['state_name'], $StateProvince) === 0)
					{
						$ups_region = $_ups_region;
					}
				}
			}
			else
			{
				$ups_region = $ups_region_list[0];
			}
			
			$CountryCode = $ups_region['region_code'];
			$City = $CountryCode ? $City . ($City && $StateProvince ? ',' : '') . $StateProvince : $City;
			$StateProvinceCode = $ups_region['state_code'];
			
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
                	ups_region
                where
	                {$region_condition} ";
			$ups_region_list = $db->getAll($sql);
			if (count($ups_region_list) > 1)
			{
				foreach ($ups_region_list as $_ups_region)
				{
					#if (strtoupper($_ups_region['state_name']) == strtoupper($StateProvince))
					if (stripos($_ups_region['state_name'], $StateProvince) === 0)
					{
						$ups_region = $_ups_region;
					}
				}
			}
			else
			{
				$ups_region = $ups_region_list[0];
			}
			
			$CountryCode = $ups_region['region_code'];
			$City = $CountryCode ? $City . ($City && $StateProvince ? ',' : '') . $StateProvince : $City;
			$StateProvinceCode = $ups_region['state_code'];
			
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
	
	$ShipTo = <<<ShipTo
		<ShipTo>
			<CompanyName>{$CompanyName}</CompanyName>
			<AttentionName>{$AttentionName}</AttentionName>
			<PhoneNumber>{$PhoneNumber}</PhoneNumber>
			<Address>
				<AddressLine1>{$AddressLine1}</AddressLine1>{$AddressLine2_xml}{$AddressLine3_xml}
				<City>{$City}</City>
				<StateProvinceCode>{$StateProvinceCode}</StateProvinceCode>
				<CountryCode>{$CountryCode}</CountryCode>
				<PostalCode>{$PostalCode}</PostalCode>
			</Address>
		</ShipTo>
ShipTo;
	
	return $ShipTo;
}

function convert_rotate_90_degrees($img)
{
	$cmd = "convert -rotate 90 $img $img";
	`$cmd`;
}

/**
 * 向ups请求，并打印ups的快递单
 * @param int $order_id
 * @param int $pieces
 * @return string $request_content
 */
function ups_ship_request($order_id, $pieces, $param = array())
{
	//BC86A8B0A1506BB0
	$access_license_xml = <<<al
<?xml version="1.0"?>
<AccessRequest xml:lang='en-US'>
	<AccessLicenseNumber>BC867A9FFBBC5B68</AccessLicenseNumber>
	<UserId>Zandy</UserId>
	<Password>leqee888ADMIN</Password>
</AccessRequest>
al;
	
	$microtime = microtime(true);
	
	#test
	#$url_shipment_confirm = 'https://wwwcie.ups.com/ups.app/xml/ShipConfirm';
	#product
	$url_shipment_confirm = 'https://onlinetools.ups.com/ups.app/xml/ShipConfirm';
	
	$shipment_confirm_request_xml = file_get_contents(dirname(__FILE__) . '/ups/ups_shipment_confirm_request.xml');
	
	// {{{
	$ShipTo = convert_adderess_to_ups($order_id);
	$shipment_confirm_request_xml = str_replace('{$ShipTo}', $ShipTo, $shipment_confirm_request_xml);
	// }}}
	// {{{
	if (isset($param['pinming']) && $param['pinming']) {
		// desc 信息，这里显示品名
		$shipment_confirm_request_xml = str_replace("<Description>See invoice</Description>", "<Description>{$param['pinming']}</Description>", $shipment_confirm_request_xml);
	}
	// }}}
	// {{{
	$Package = <<<Package
		<Package>
			<PackagingType>
				<Code>02</Code>
			</PackagingType>
			<PackageWeight>
				<Weight>0.5</Weight>
			</PackageWeight>
		</Package>
Package;
	if ($pieces > 1)
	{
		$Package = str_repeat($Package, $pieces);
	}
	$shipment_confirm_request_xml = str_replace('{$Package}', $Package, $shipment_confirm_request_xml);
	// }}}
	

	#$proxy = '192.168.1.254:9002';
	$proxy = null;
	$shipment_confirm_response = ups_request($url_shipment_confirm, $access_license_xml . $shipment_confirm_request_xml, $proxy);
	
	#var_dump($shipment_confirm_response);
	#$label_image_tmp_dir = '/tmp/';
	#$label_image_tmp_dir = __DIR__ . '/tmp/';
	$label_image_tmp_dir = dirname(__FILE__) . '/../../templates/caches/ups/';
	if (!file_exists($label_image_tmp_dir))
	{
		mkdir($label_image_tmp_dir, 0777, true);
	}
	@chmod($label_image_tmp_dir, 0777);
	$label_images = array();
	
	file_put_contents($label_image_tmp_dir . "shipment_confirm_request_" . $microtime . ".xml", $access_license_xml . $shipment_confirm_request_xml);
	file_put_contents($label_image_tmp_dir . "shipment_confirm_response_" . $microtime . ".xml", $shipment_confirm_response);
	
	$doc = new DOMDocument();
	$doc->loadXML($shipment_confirm_response);
	$xpath = new DOMXPath($doc);
	
	$responseStatus = $xpath->query("/ShipmentConfirmResponse/Response/ResponseStatusDescription")->item(0)->nodeValue;
	if ($responseStatus == 'Success')
	{
		$images_html = array();
		#echo "\$responseStatus: $responseStatus\n";
		$ShipmentDigest = $xpath->query("/ShipmentConfirmResponse/ShipmentDigest")->item(0)->nodeValue;
		$ShipmentIdentificationNumber = $xpath->query("/ShipmentConfirmResponse/ShipmentIdentificationNumber")->item(0)->nodeValue;
		
		#test
		#$url_shipment_accept = 'https://wwwcie.ups.com/ups.app/xml/ShipAccept';
		#product
		$url_shipment_accept = 'https://onlinetools.ups.com/ups.app/xml/ShipAccept';
		
		$shipment_accept_request_xml = file_get_contents(dirname(__FILE__) . '/ups/ups_shipment_accept_request.xml');
		$shipment_accept_request_xml = str_replace("{ShipmentDigest}", $ShipmentDigest, $shipment_accept_request_xml);
		$shipment_accept_response = ups_request($url_shipment_accept, $access_license_xml . $shipment_accept_request_xml, $proxy);
		
		file_put_contents($label_image_tmp_dir . "shipment_accept_request_" . $microtime . ".xml", $access_license_xml . $shipment_accept_request_xml);
		file_put_contents($label_image_tmp_dir . "shipment_accept_response_" . $microtime . ".xml", $shipment_accept_response);
		
		#var_dump($shipment_accept_response);
		$doc = new DOMDocument();
		$doc->loadXML($shipment_accept_response);
		$xpath = new DOMXPath($doc);
		$TrackingNumbers = $xpath->query("/ShipmentAcceptResponse/ShipmentResults/PackageResults/TrackingNumber");
		$GraphicImages = $xpath->query("/ShipmentAcceptResponse/ShipmentResults/PackageResults/LabelImage/GraphicImage");
		#$HTMLImage = $xpath->query("/ShipmentAcceptResponse/ShipmentResults/PackageResults/LabelImage/HTMLImage")->item(0)->nodeValue;
		$img_first = '';
		if ($TrackingNumbers)
		{
			foreach ($TrackingNumbers as $k => $TrackingNumber)
			{
				$label_image = $label_image_tmp_dir . 'label' . $TrackingNumber->nodeValue . '.gif';
				$label_images[] = $label_image;
				$label_image_url = WEB_ROOT . 'templates/caches/ups/label' . $TrackingNumber->nodeValue . '.gif';
				file_put_contents($label_image, base64_decode($GraphicImages->item($k)->nodeValue));
				chmod($label_image, 0777);
				
				/* 原始横向
				 * $images_html_tmp = <<<image_html
<table border="0" cellpadding="0" cellspacing="0" width="650" ><tr>
<td align="left" valign="top">
<IMG SRC="$label_image_url" height="400" width="700">
</td>
</tr></table>
image_html;*/
				// 旋转为纵向
				convert_rotate_90_degrees($label_image);
				$images_html_tmp = <<<image_html
<table border="0" cellpadding="0" cellspacing="0"><tr>
<td align="left" valign="top">
<IMG SRC="$label_image_url" width="400" height="650">
</td>
</tr></table>
image_html;
				$images_html[] = $images_html_tmp;
				$images_html[] = $images_html_tmp;
				$images_html[] = $images_html_tmp;
				if ($img_first == '')
				{
					$img_first = $images_html_tmp;
				}
			}
		}
		else
		{
			return array(
				'code' => 1, 
				'msg' => '$TrackingNumbers = ' . sizeof($TrackingNumbers)
			);
		}
		
		#header('Content-Type: image/gif');
		#echo base64_decode($GraphicImage);
		#echo base64_decode($HTMLImage);
		/* 原始横向
		 * $images_html = join("<span style=\"page-break-after:always;\"></span>", $images_html);
		$imghtml = <<<html
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 3.2//EN">
<html><head><title>
View/Print Label</title></head><style>
    .small_text {font-size: 80%;}
    .large_text {font-size: 115%;}
</style>
<body bgcolor="#FFFFFF" onload="window.print();">
$images_html
<span style="page-break-after:always;"></span>
$img_first
<span style="page-break-after:always;"></span>
$img_first
</body>
</html>
html;*/
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
	else
	{
		$ErrorDescription = $xpath->query("/ShipmentConfirmResponse/Response/Error/ErrorDescription")->item(0)->nodeValue;
		return array(
			'code' => 2, 
			'msg' => '$responseStatus = ' . $responseStatus . '; $ErrorDescription = ' . $ErrorDescription
		);
	}

}

/**
 * 请求ups的服务，输入为xml，输出为xml
 * @param string $request
 * @return string $response_content
 */
function ups_request($serverUrl, $request, $proxy = null)
{
	$timeout = 30;
	
	$ch = curl_init($serverUrl);
	curl_setopt($ch, CURLOPT_TIMEOUT, $timeout * 2);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727)");
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
	
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	
	if ($proxy)
	{
		curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, TRUE);
		curl_setopt($ch, CURLOPT_PROXY, $proxy);
	}
	
	$content = curl_exec($ch);
	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	
	if ($httpcode == 200)
	{
		return $content;
	}
	else
	{
		echo "错误信息：ups服务器不正常，httpcode为 " . $httpcode;
		return null;
	}
}
