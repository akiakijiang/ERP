<?php
/**
 * dhl相关库
 * @author zwsun zwsun@i9i8.com
 * 
 */


/**
 * 将Erp系统中的地址转换成dhl可以识别的地址，并组装成xml
 * @param int $order_id
 * @param int $pieces 
 * @return string $request_content
 */
function convert_adderess_to_dhl($order_id, $pieces = 1, $param = array()) {
    global $db;
    
    $order_id = intval($order_id);
    
    $sql = "select 
	            c.region_name as country_name, 
	            p.region_name as province_name, 
	            oap.attr_value as province_text, 
	            oac.attr_value as city_text,
	            o.consignee, o.zipcode, o.address,
	            o.tel, o.mobile, o.sign_building
            from ecs_order_info o 
	            left join order_attribute oap on o.order_id = oap.order_id and oap.attr_name = 'province_text'
	            left join order_attribute oac on o.order_id = oac.order_id and oac.attr_name = 'city_text'
	            left join ecs_region p on o.province = p.region_id
	            left join ecs_region c on o.country = c.region_id
            where o.order_id = {$order_id} ";
    
    $order_info = $db->getRow($sql);
    
    if (!$order_info) {
        return false;
    }
    
    $address = $order_info['address'] ;
    // dhl 需要转义 &
    $address = htmlspecialchars($address);
    // if have sign_building
    if ($order_info['sign_building']) {
        $address .= " , " . $order_info['sign_building'];
    }
    $Consignee = $order_info['consignee'];
    $country = $order_info['country_name'];
    $province = $order_info['province_name'];
    $province_text = $order_info['province_text'];
    $city_text = $order_info['city_text'];
    
    $tel = $order_info['tel'];
    $zipcode = $order_info['zipcode'];
    
    /* 
      DivisionCode is the state_code field in table dhl_region Division/State/Prefecture/Province code  (Required if the country code = US)
      City is the city field in table dhl_region, optional
     */
    $City = null;
    if ($province) $City = $province;
    if ($province_text) $City = $province_text;
    if ($city_text) {
        if ($City) {
            $City .= "," . $city_text;
        } else {
            $City = $city_text;
        }
    }
    
    if (!$City) $City = null;
    
    $DivisionCode = "";
    
    // 去除中间的非数字部分
    // $tel = preg_replace('/[^\d+]/', '',$tel);
    $zipcode = preg_replace('/[^\w+]/', '',$zipcode);
    
    
    $region_map = array(
        "United States" => "US",
        "American Samoa" => "AS",
        "Hong Kong" => "",
        "Australia" => "",
        "United Kingdom" => "",
        "Canada" => "",
        "Ireland" => "",
        "Moldova, Republic of" => "",
        "Russian Federation" => "",
        "Netherlands" => "",
    );
    
    switch($country) {
        case "United States":
            $zipcode = substr($zipcode, 0, 5);
            $sql = "select * 
                from 
                dhl_region
                where
                region_code = 'US' and
                state_name like '{$province}' and
                postcode_begin <= '{$zipcode}' and 
                postcode_end >= '{$zipcode}' ";
            $dhl_region_list = $db->getAll($sql);
            if (count($dhl_region_list) > 1) {
                foreach ($dhl_region_list as $_dhl_region) {
                    if (strtoupper($_dhl_region['city']) == strtoupper($city_text)) {
                        $dhl_region = $_dhl_region;
                    }
                }
                // 没有匹配城市使用第一个地址，并在收件人上做标记
                if ($dhl_region == null) {
                    $dhl_region = $dhl_region_list[0];
                    $Consignee = "* " . $Consignee;
                }
            } else {
                $dhl_region = $dhl_region_list[0];    
            }
            
            
            $CountryName = $dhl_region['region_name'];
            $CountryCode = $dhl_region['region_code'];
            $DivisionCode = $dhl_region['state_code'];
            $City = $dhl_region['city'];
            
            break;
        
        case "United Kingdom":
            $sql = "select *
                from dhl_region
                where 
                region_code = 'GB' and
                (instr('{$zipcode}', postcode_begin) > 0 
                or 
                 instr('{$zipcode}', postcode_end) > 0 
                )";
            $dhl_region_list = $db->getAll($sql);
            if (count($dhl_region_list) > 1) {
                foreach ($dhl_region_list as $_dhl_region) {
                    if (strtoupper($_dhl_region['city']) == strtoupper($city_text)) {
                        $dhl_region = $_dhl_region;
                    }
                }
                // 没有匹配城市使用第一个地址，并在收件人上做标记
                if ($dhl_region == null) {
                    $dhl_region = $dhl_region_list[0];
                    $Consignee = "* " . $Consignee;
                }
            } else {
                $dhl_region = $dhl_region_list[0];    
            }
            $CountryName = $dhl_region['region_name'];
            $CountryCode = $dhl_region['region_code'];
            $City = $dhl_region['city'];
            
            break;
        
        /*
        case "Australia":
        case "Canada":
        case "Ireland":
        case "Hong Kong" :        
        case "Moldova, Republic of":
        case "Russian Federation":
        case "Netherlands":
        case "American Samoa":
        */
        default :
            // 下面这几个国家不能使用 like '%{$country}%' 该条件不能确定一个国家  因为有类似的国家名
            if (in_array(strtoupper($country), array('CONGO', 'DOMINICA', 'NIGER'))) {
                $region_condition = " region_name like '{$country}' ";
            } else {
                $region_condition = " region_name like '%{$country}%' ";
            }
            
            $sql = "select * 
                from 
                dhl_region
                where
                {$region_condition} and
                (
                (postcode_begin <= '{$zipcode}' and postcode_end >= '{$zipcode}' ) 
                or 
                instr('{$zipcode}', postcode_begin) > 0
                or 
                instr('{$zipcode}', postcode_end) > 0
                )";
            $dhl_region_list = $db->getAll($sql);
            if (count($dhl_region_list) > 1) {
                foreach ($dhl_region_list as $_dhl_region) {
                    if (strtoupper($_dhl_region['city']) == strtoupper($city_text)) {
                        $dhl_region = $_dhl_region;
                    }
                }
                // 没有匹配城市使用第一个地址，并在收件人上做标记
                if ($dhl_region == null) {
                    $dhl_region = $dhl_region_list[0];
                    $Consignee = "* " . $Consignee;
                }
            } else {
                $dhl_region = $dhl_region_list[0];    
            }
            
            $CountryName = $dhl_region['region_name'];
            $CountryCode = $dhl_region['region_code'];
            $DivisionCode = $dhl_region['state_code'];
            
            break;
    }
    
    $_tmpx = array();
    if ($City) $_tmpx[] = $City;
    if ($province) $_tmpx[] = $province;
    if ($zipcode) $_tmpx[] = $zipcode;
    $city_province_zipcode = join(",", $_tmpx);
    

	// {{{ 处理地址过长的情况
	$AddressLine = $address;
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
		$AddressLine2_xml = "<AddressLine>  {$AddressLine2}</AddressLine>";
	}
	if ($AddressLine3)
	{
		$AddressLine3_xml = "<AddressLine>  {$AddressLine3}</AddressLine>";
	}
	// }}}
	
	$CompanyName = $country == 'Australia' ? $Consignee : '  ';
    
    $consignee_content =
    "<Consignee>
		<CompanyName>{$CompanyName}</CompanyName>
		<AddressLine>{$AddressLine1}</AddressLine>{$AddressLine2_xml}{$AddressLine3_xml}
		<City>{$City}</City>" .
		($DivisionCode ? "<DivisionCode>{$DivisionCode}</DivisionCode>" : "") .
		"<PostalCode>{$zipcode}</PostalCode>
		<CountryCode>{$CountryCode}</CountryCode>
		<CountryName>{$CountryName}</CountryName>
		<Contact>
			<PersonName>{$Consignee}</PersonName>
			<PhoneNumber>{$tel}</PhoneNumber>
		</Contact>
	</Consignee>";
	
	// 使用苏州、杭州模板	
    $dhl_area = isset($param['area']) && $param['area'] && in_array($param['area'], array('hz', 'sz')) ? $param['area'] : 'sz';
	// 读取模版文件，替换内容
    if ($_SESSION['party_id'] == 65560) {
		$tpl_file = dirname(__FILE__) . "/dhl/ShipmentValidateRequest.$dhl_area.faucet.xml";
	} else {
		$tpl_file = dirname(__FILE__) . "/dhl/ShipmentValidateRequest.$dhl_area.xml";
	}
	$request_content = @file_get_contents($tpl_file);
    
    $piecesContent = '';
    
    for ($i = 1; $i <= $pieces; $i++) {
        $piecesContent .= 
        "<Piece>
			    <PieceID>{$i}</PieceID>
				<PackageType>YP</PackageType>
				<Weight>0.5</Weight>
				<DimWeight>10.0</DimWeight>
				<Depth>2</Depth>
				<Width>2</Width>
				<Height>2</Height>
				<PieceContents>See invoice</PieceContents>
		</Piece>";
    }
	
	$request_content = str_replace(
	    array('{Consignee}',     '{pieces}',  '{piecesContent}', '{MessageTime}', '{Date}',      '{Contents}'),
	    array($consignee_content, $pieces,     $piecesContent,    date("c"),      date("Y-m-d"), '  '),
	    $request_content
	);
	
	return $request_content;
}

/**
 * 向dhl请求，并打印dhl的快递单
 * @param int $order_id
 * @param int $pieces 
 * @return string $request_content
 */
function dhl_ship_request($order_id, $pieces = 1, $param = array()) {
    
	$request_content = convert_adderess_to_dhl($order_id, $pieces, $param);
    if (!$request_content) {
        return false;
    }
    
	// {{{ P 包裹 D 文件
    $GlobalProductCode = isset($param['P_D']) && $param['P_D'] && in_array($param['P_D'], array('P', 'D')) ? $param['P_D'] : 'P';
    $LocalProductCode = isset($param['P_D']) && $param['P_D'] && in_array($param['P_D'], array('P', 'D')) ? $param['P_D'] : 'P';
	
	$request_content = str_replace(array(
		'{GlobalProductCode}', 
		'{LocalProductCode}'
	), array(
		$GlobalProductCode, 
		$LocalProductCode
	), $request_content);
	// }}}
    
    // {{{ 使用打印之前的传过来品名、申报价值
    if ($GlobalProductCode == 'D') {
    	// 文件
    	$request_content = str_replace(">See invoice<", ">Document<", $request_content);
    } elseif (isset($param['pinming']) && $param['pinming']) {
    	// 包裹
    	$request_content = str_replace(">See invoice<", ">{$param['pinming']}<", $request_content);
    }
    if ($GlobalProductCode == 'D') {
    	// 文件
    	$request_content = str_replace("<TermsOfTrade>DDU</TermsOfTrade>", "<TermsOfTrade></TermsOfTrade>", $request_content);
    }
    if ($GlobalProductCode == 'D') {
    	// 文件
    	$request_content = str_replace("<DeclaredValue>50.00</DeclaredValue>", "<DeclaredValue>0.00</DeclaredValue>", $request_content);
    } elseif (isset($param['declaredValue']) && $param['declaredValue']) {
    	// 包裹
    	$request_content = str_replace("<DeclaredValue>50.00</DeclaredValue>", "<DeclaredValue>{$param['declaredValue']}</DeclaredValue>", $request_content);
    }
    // }}}
    
    @file_put_contents(DHL_ROOT_PATH . '/request.xml', $request_content);
	
	// send dhl shipment request to get a airway bill number
	$response_content = dhl_request(DHL_API, $request_content);
	
	// check airway bill number
    if (!$response_content || !preg_match('/<AirwayBillNumber>(\d+)<\/AirwayBillNumber>/', $response_content, $matches)) {
        print "错误信息：dhl响应内容不正常，响应的内容为：". $response_content;
        return false;
    }
    
    $awb = $matches[1];
    
    // mkdir awb_folder
    $awb_folder = DHL_ROOT_PATH . '/' . $awb . '/';
    mkdir($awb_folder);
    
    // save the xml file
    $response_xml_file = $awb_folder . "/{$awb}.xml";
    file_put_contents($response_xml_file, $response_content);
    
    // generate pdf file
    $pdf_file = $awb_folder . "/{$awb}.pdf";
    if(!dhl_ship_pdf($response_content, $pdf_file)) {
        return false;
    }
    
    // generate img from pdf file
    $image_paths = dhl_ship_pdf_to_image($pdf_file, $awb_folder, $awb);
    if (!$image_paths) {
        print "错误信息：将pdf转成图片，请检查imagick是否正常。 ";
        return false;
    }
    
    
    $image_html = 
"<html>
<script>
function windowprint() {
    if (typeof jsPrintSetup == \"undefined\" || !jsPrintSetup) {
        alert('请安装firefox js print setup插件，或者手工设置页面边距均为0');
    }
    // set portrait orientation
    jsPrintSetup.setOption('orientation', jsPrintSetup.kPortraitOrientation);
    
    // set top margins in millimeters
    jsPrintSetup.setOption('marginTop', 0);
    jsPrintSetup.setOption('marginBottom', 0);
    jsPrintSetup.setOption('marginLeft', 1);
    jsPrintSetup.setOption('marginRight', 0);
    
    // set page header
    jsPrintSetup.setOption('headerStrLeft', '');
    jsPrintSetup.setOption('headerStrCenter', '');
    jsPrintSetup.setOption('headerStrRight', '');
    
    // set empty page footer
    jsPrintSetup.setOption('footerStrLeft', '');
    jsPrintSetup.setOption('footerStrCenter', '');
    jsPrintSetup.setOption('footerStrRight', '');
    
    // clears user preferences always silent print value
    // to enable using 'printSilent' option
    jsPrintSetup.clearSilentPrint();
    
    // Suppress print dialog (for this context only)
    // jsPrintSetup.setOption('printSilent', 1);
    // Do Print 
    // When print is submitted it is executed asynchronous and
    // script flow continues after print independently of completetion of print process! 
    jsPrintSetup.print();
    // next commands
} </script>"
   .'<body style="margin:0;padding:0;" onload="windowprint();">';
   
    foreach ($image_paths as $image) {
        $image_html .= "<img src=\"/admin/image.php?p={$awb_folder}{$image}\" style=\"heght:20cm;width:10cm;\" /><br />";
    }

    $image_html .= ('</body></html>');
    return array($awb, $image_html);
}

/*
$response_content = file_get_contents('/tmp/6002965220/6002965220.xml');
$pdf_file = '/tmp/6002965220/6002965220.pdf';
dhl_ship_pdf($response_content, $pdf_file);
*/
function dhl_ship_pdf ($response_content, $pdf_file) {
    $serverUrl = DHL_ERP_API . "?action=response_pdf";
    
    $pdfcontent = dhl_request($serverUrl, $response_content);
    if (!$pdfcontent) {
        print "错误信息：无法将dhl的响应转成pdf，请检查 {$serverUrl} 是否正常。 ";
        return false;
    }
    file_put_contents($pdf_file, $pdfcontent);
    return true;
}

/**
 * 将pdf转成图片 
 *
 **/
function dhl_ship_pdf_to_image($pdf_file, $awb_folder, $awb) {
    #$cmd = "convert -density 300x300 -crop 1200x2380+2000+45 {$pdf_file} {$awb_folder}/$awb.png";
    $cmd = "sudo convert -density 300x300 -crop 1200x2380+2000+45 {$pdf_file} {$awb_folder}/$awb.png";
    `$cmd`;
    
    $image_paths = array();
    if ($handle = opendir($awb_folder)) {
        while (false !== ($file = readdir($handle))) {
            if (substr($file, -4) == '.png') {
                $image_paths[] = $file;
            }
        }
    }
    
    // 依次返回，最后一张是archive，需要打印两张
    if (!empty($image_paths)) {
        sort($image_paths);
        $image_paths[] = $image_paths[count($image_paths)-1];
    }
    
    return $image_paths;
}

/**
 * 请求dhl的服务，输入为xml，输出为xml
 * @param string $request
 * @return string $response_content
 */
function dhl_request($serverUrl, $request) {
    $timeout = 30;

    $ch = curl_init($serverUrl);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout * 2);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; .NET CLR 2.0.50727)");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $request);

    $content = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode == 200) {
        return $content;
    } else {
        print "错误信息：dhl服务器不正常，httpcode为 " . $httpcode;
        return null;
    }
}
