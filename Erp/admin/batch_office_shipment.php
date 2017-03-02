<?php
define('IN_ECS', true);
require_once 'includes/init.php';
include_once 'function.php';
	include_once('../RomeoApi/lib_currency.php');
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_soap.php');
	require_once (ROOT_PATH . 'includes/lib_order.php');
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');

	$json = new JSON();
	$error = "";
	$msg = "";
	$fileElementName = 'fileToUpload';
	
	$query_tracking_number = array();
	
	
	$uploader = new Helper_Uploader();
	
	$max_size = $uploader -> allowedUploadSize(); // 允许上传的最大值
	
	
	$config = array('办公件' =>
	            array('party'=>'组织',
	                  'shpping_date'=>'发货日期(yyyy-mm-dd)',
	                  'start_province'=>'发件省', 
	                  'start_city'=>'发件市' ,
	            		'start_district' =>'发件区',
	            		'end_province' =>'收件省',
	            		'end_city' =>'收件市',
	            		'end_district' =>'收件区',
	            		'shipping_id' =>'快递方式',
	            		'tracking_number' =>'运单号',
	            		'package_type' =>'运单类型',
	            		'package_weight' =>'包裹重量',
	            		'note' =>'备注',
	          ));
	          
	if(!$uploader->existsFile ('fileToUpload')){
		$final['message'] = '没有选择上传文件，或者文件上传失败';
	}
	
	//取得要上传的文件句柄
	if($final['message'] == ""){
		$file = $uploader->file ('fileToUpload');
	}
	
	//检查上传文件
	if($final['message'] == "" && !$file->isValid ('xls,xlsx', $max_size)){
		$final['message'] = "非法文件！请检查文件类型(xls,xlsx),并且系统限制的上传大小为'. $max_size /1024/1024 . 'MB'";
	}
	
	//读取excel
	if($final['message'] == ""){
		$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$final['message'] = reset ( $failed );
		}
	}
	
	if($final['message'] == ""){
		$rowset = $result ['办公件'];
		if(empty($rowset)){
			$final['message'] = "excel文件中没有数据，请检查文件";
		}
	}
	if($final['message'] == ""){
		$in_party = Helper_Array::getCols ($rowset,'party');
		$in_shpping_date = Helper_Array::getCols($rowset, 'shpping_date');
		$in_start_province = Helper_Array::getCols ($rowset,'start_province');
		$in_start_city = Helper_Array::getCols ($rowset,'start_city');
		$in_start_district = Helper_Array::getCols ($rowset,'start_district');
		$in_end_province = Helper_Array::getCols ($rowset,'end_province');
		$in_end_city = Helper_Array::getCols ($rowset,'end_city');
		$in_end_district = Helper_Array::getCols ($rowset,'end_district');
		$in_shipping_id = Helper_Array::getCols ($rowset,'shipping_id');
		$in_tracking_number = Helper_Array::getCols ($rowset,'tracking_number');
		$in_package_type = Helper_Array::getCols ($rowset,'package_type');
		$in_package_weight = Helper_Array::getCols ($rowset,'package_weight');
		$in_note = Helper_Array::getCols ($rowset,'note');

		$check_value_arr = array('party'=>'组织',
				'shpping_date'=>'发货日期(yyyy-mm-dd)',
				'shipping_id' =>'快递方式',
				'start_province'=>'发件省',
				'start_city'=>'发件市' ,
				'end_province' =>'收件省',
				'end_city' =>'收件市',
				'tracking_number' =>'运单号',
				'package_type' =>'运单类型',
				'package_weight' =>'包裹重量',
		);
		 
		foreach ( array_keys ( $check_value_arr ) as $val ) {
			$in_val = Helper_Array::getCols ( $rowset, $val );
			$in_len = count ( $in_val );
			Helper_Array::removeEmpty ( $in_val );
			if (empty ( $in_val ) || $in_len > count ( $in_val )) {
				$empty_col = true;
				$final['message'] = "文件中存在空的{$check_value_arr[$val]}，请确保这几列每一行都有数据";
			}
		}
	}
	
	if($final['message'] == ""){
		foreach($in_package_type as $key=> $item_value){
			$type_name = $item_value;
			if($item_value == '文件'){
				$in_package_type[$key] = 1;
			}
			elseif($item_value == '包裹'){
				$in_package_type[$key] = 2;
			}
			else{
				$final['message'] = "包裹类型有误，只能是'文件'或者'包裹'";
				break;
			}
		}
	}
	if($final['message'] == ""){
		foreach ($in_party as $key=>$item_value){
			$party_name = $item_value;
			$sql = "select party_id from romeo.party where name = '{$item_value}'
			";
			$item_value = $db->getOne($sql);
			if($item_value == null){
				$final['message'] = "不存在组织：".$party_name;
				break;
			}
			else{
				$in_party[$key] = $item_value;
			}
		}
	}
	
	if($final['message'] == ""){
		$temp=region_confirm($in_start_province,$in_start_city,$in_start_district);
		$in_start_province =$temp['province'];
		$in_start_city =$temp['city'];
		$in_start_district =$temp['district'];
		$final['message'] = $temp['message'];
		if($final['message'] != ""){
			$final['message'] = "发件".$final['message'];
		}
	}
	if($final['message'] == ""){
		$temp=region_confirm($in_end_province,$in_end_city,$in_end_district);
		$in_end_province =$temp['province'];
		$in_end_city =$temp['city'];
		$in_end_district =$temp['district'];
		$final['message'] = $temp['message'];
		if($final['message'] != ""){
			$final['message'] = "收件".$final['message'];
		}
	}

	
	if($final['message'] == ""){
		foreach($in_shipping_id as $key =>$item_value){
			$shipping_name = $item_value;
			$sql = "select shipping_id from ecshop.ecs_shipping where shipping_name = '{$item_value}'
			";
			$item_value = $db->getOne($sql);
			if($item_value == null){
				$final['message'] = "快递方式".$shipping_name."有误";
				break;
			}
			else{
				$in_shipping_id[$key] = $item_value;
			}
		}
	}
	if($final['message'] == ""){
		foreach($in_tracking_number as $key =>$item_value){
			$tracking_number = $item_value;
			$sql = "select of.tracking_number, se.shipping_name from romeo.office_shipment   of
						left join ecshop.ecs_shipping se on se.shipping_id = of.shipping_id
						where of.tracking_number = '{$tracking_number}' and of.shipping_id = '{$in_shipping_id[$key]}'
			";
			$item_value_temp = $db->getRow($sql);
			if($item_value_temp['tracking_number'] != null){
				$final['message'] = $item_value_temp['shipping_name']."    面单号为".$tracking_number."的办公件已经存在";
				break;
			}
			$flag = true;
			switch ($in_shipping_id[$key]){
				case '85'://圆通
					if (!preg_match('/^(0|1|2|3|5|6|7|8|9|S|E|D|F|G|V|W|e|d|f|g|s|v|w)[0-9]{9}([0-9]{2})?([0-9]{6})?$/',$tracking_number)) {
							$flag = false;
						}
						break;
				case '44'://顺丰快递
				case '49'://顺丰快递COD
					if (!preg_match('/^\d{12}$/',$tracking_number)) {
						$flag = false;
					}
					break;
				case '47'://邮政EMS13位ER，EQ，ET，EF，开头
				case '36'://邮政COD
					if (!preg_match('/^[A-Z]{2}[0-9]{9}[A-Z]{2}$/',$tracking_number)) {
							$flag = false;
					}
					break;
				case '100':   // 韵达快运13位12,16开头
					if (!preg_match('/^[\s]*[0-9]{13}[\s]*$/',$tracking_number)) {
						$flag = false;
					}
					break;
				case '99':   // 汇通快运
					if (!preg_match('/^(A|B|C|D|E|H|0)(D|X|[0-9])(A|[0-9])[0-9]{10}$|^(21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39)[0-9]{10}$|^(50|70)[0-9]{12}$/',$tracking_number)) {
						$flag = false;
					}
					break;
				case '89'://申通快运12位268,368,468,568,668开头
					if (!preg_match('/^(229|268|888|588|688|368|468|568|668|768|868|968|220|227)[0-9]{9}$|^(229|268|888|588|688|368|468|568|668|768|868|968|220)[0-9]{10}$|^(STO)[0-9]{10}$/',$tracking_number)) {
						$flag = false;
					}
					
					break;
				case '107'://E邮宝快递13位，EQ开头
					if (!preg_match('/^(ET|EW|EQ|EV)[0-9]{9}[A-Z]{2}$/',$tracking_number)) {
						$flag = false;
					}
					break;
				case '115'://中通快递
					if (!preg_match('/^((618|680|688|618|828|988|118|888|571|518|010|628|205|880|717|718|719|728|738|761|762|763|701|757|358|359|530)[0-9]{9})$|^((36|37|40)[0-9]{10})$|^((1)[0-9]{12})$|^((2008|2010|8050|7518)[0-9]{8})$/',$tracking_number)) {
						$flag = false;
					}
					break;
				}
			if(!$flag){
				$final['message'] = "面单号 ".$tracking_number." 格式不规范";
				break;
			}
		}
	}
	
	if($final['message'] ==""){
		
		foreach ($in_party as $key =>$item_value){
			$office_shipment = new stdClass();
			$office_shipment->partyId = $in_party[$key];
			$office_shipment->shippingDate =$in_shpping_date[$key] ; //romeo API中的参数date 为Date格式
			$office_shipment->startProvince = $in_start_province[$key];
			$office_shipment->startCity = $in_start_city[$key];
			$office_shipment->startDistrict = $in_start_district[$key];
			$office_shipment->endProvince = $in_end_province[$key];
			$office_shipment->endCity = $in_end_city[$key];
			$office_shipment->endDistrict = $in_end_district[$key];
			$office_shipment->trackingNumber = $in_tracking_number[$key];
			$office_shipment->packageType = $in_package_type[$key];
			$office_shipment->shippingId = $in_shipping_id[$key];
			$office_shipment->weight = $in_package_weight[$key];
			$office_shipment->actionUser = $_SESSION['admin_name'];
			$office_shipment->status = 'OK';
			$office_shipment->note = $in_note[$key];
			$office_shipment->actionTime = date("Y-m-d H:i:s", time());
			$office_shipment->shippingCost = 0.00;
			$office_shipment->outWeight = 0.0000;
			$office_shipment->lastActionUser = '';
			$office_shipment->lastUpdateTime = date("Y-m-d H:i:s", time());
			$soap_client = soap_get_client('OfficeShipmentService');
			$result = $soap_client->createOfficeShipment(array('officeShipment' => $office_shipment));
			if (!$result->return) {
				$final['message'] = "快递单号：".$in_tracking_number[$key] ."录入失败，如有疑问，请联系ERP组，谢谢";
				break;
			}
		}
	}
	function region_confirm($province,$city,$district){
		global $db;
		foreach($province as $key=>$item_value){
			$province_name = $item_value;
			$sql = "select region_id from ecshop.ecs_region where region_name = '{$item_value}' and region_type = 1
			";
			$province_region_id = $db->getOne($sql);
			if($province_region_id == ''){
				$finals['message'] = "省份名 ".$province_name."有误";
				break;
			}
			else{
				$province[$key] = $province_region_id;
				$city_name = $city[$key];
				$sql = "select region_id from ecshop.ecs_region where region_name like '%{$city_name}%' and region_type = 2 and parent_id = '{$province[$key]}'";
				$city_region_id = $db->getOne($sql);
				if($city_region_id == ''){
					$finals['message'] = "城市名 ".$city_name." 有误，或者不属于 ".$province_name;
					break;
				}
				else{
					$city[$key] = $city_region_id;
					$district_name = $district[$key];
					$sql = "select region_id from ecshop.ecs_region where region_name like '%{$district_name}%' and region_type = 3 and parent_id = '{$city[$key]}'";
					$district_region_id = $db->getOne($sql);
					if($district_region_id == ''&&$district_name!= ""){
						$finals['message'] = "地区名 ".$district_name." 有误，或者不属于 ".$city_name;
						break;
					}
					else{
						$district[$key] = $district_region_id;
					}
				}
			}
		}
		$finals['province'] = $province;
		$finals['city'] = $city;
		$finals['district'] = $district;
		return $finals;
	}
	
	
	
	QLog::log($final['message']);
	
	print 	$json->encode($final);
	exit();
?>