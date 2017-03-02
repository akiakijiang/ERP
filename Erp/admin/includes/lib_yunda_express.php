<?php
define('IN_ECS', true);
require_once('init.php');
require_once(ROOT_PATH . 'includes/bytes.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

define('YUNDA_EXPRESS_URL_TEST','http://orderdev.yundasys.com:10110/cus_order/order_interface/'); //TEST
define('YUNDA_VALIDATION_ID_TEST','KRTujU2BSpAQJFiW4d6VrkwtICb9Ng');
define('YUNDA_PARTERN_ID_TEST','1001051024');


define('YUNDA_EXPRESS_URL','http://order.yundasys.com:10235/cus_order/order_interface/'); 
//贝亲青浦仓使用账户
define('YUNDA_VALIDATION_ID_BQ','IKbsnYTcX25pzwqm8N3Ex4BhvQMW6g');
define('YUNDA_PARTERN_ID_BQ','1998788012');
//嘉善TP
define('YUNDA_VALIDATION_ID_SH','mhsDWXG3CAxeF8QJHZ29Ytyqr5n6wz');
define('YUNDA_PARTERN_ID_SH','31410010016');
//嘉兴水果
define('YUNDA_VALIDATION_ID_JXSG','WXj6fKV9CDY4A8egHhniyU3PbcGxQk');
define('YUNDA_PARTERN_ID_JXSG','3140059160');
//上海水果
define('YUNDA_VALIDATION_ID_SHSG','VC8FgUSNfkvGE3tdIBM7XYz6ebmyPh');
define('YUNDA_PARTERN_ID_SHSG','2016061005');
//苏州水果
define('YUNDA_VALIDATION_ID_SZSG','rSVY8hXWGiT7jwdNFcMakeDKUxyHsm');
define('YUNDA_PARTERN_ID_SZSG','2151251123');
//成都水果
define('YUNDA_VALIDATION_ID_CDSG','KHRbI7nCEBSd2tyvFhq86sUNepkWzu');
define('YUNDA_PARTERN_ID_CDSG','6101941201');
//武汉水果
define('YUNDA_VALIDATION_ID_WHSG','bTyJvXYtekzPsZWq8pARHSj4CxduUm');
define('YUNDA_PARTERN_ID_WHSG','4306638588');
//北京水果
define('YUNDA_VALIDATION_ID_BJSG','bTUkMJ7uPIfdiEaFmS4VjXzysACw2Z');
define('YUNDA_PARTERN_ID_BJSG','1018911018');
//深圳水果
//define('YUNDA_VALIDATION_ID_SHZSG','QPSI4pwicH97YurkF6BGVznjMqT58x');
//define('YUNDA_PARTERN_ID_SHZSG','68016710011');

define('YUNDA_VALIDATION_ID_SHZSG','G5IieVxpJdSK2rfj4EQ9HDgbTRmMWh');
define('YUNDA_PARTERN_ID_SHZSG','51876730011');
function yunda_post_data($url,$data){
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_URL, $url );
	curl_setopt ( $ch, CURLOPT_POST, 1 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS,  http_build_query($data) );
	$res = curl_exec ( $ch );  
	curl_close ( $ch );
	return $res;
}

//其他账户获取面单信息
function yd_qt_sendOrder($branch,$shipment_id,$shipmentCount,$sender,$receiver,$goods_info){
	$result = array();
	$url = YUNDA_EXPRESS_URL;
	$sendName = "乐其";
	if($branch=='SH'){
		$sendCity = "上海市,上海市,青浦区";  $sendAddress = '';  
		$partnerid = YUNDA_PARTERN_ID_SH;  $validation = YUNDA_VALIDATION_ID_SH;
	}elseif($branch=='JXSG'){
		$sendCity = "浙江省,嘉兴市,南湖区";  $sendAddress = '欧嘉路明新路交叉口'; 
		$partnerid = YUNDA_PARTERN_ID_JXSG;  $validation = YUNDA_VALIDATION_ID_JXSG; 
	}elseif($branch=='SHSG'){
		$sendCity = "上海市,上海市,青浦区";  $sendAddress = '青赵公路5009号'; 
		$partnerid = YUNDA_PARTERN_ID_SHSG;  $validation = YUNDA_VALIDATION_ID_SHSG; 
	}elseif($branch=='SZSG'){
		$sendCity = "江苏省,苏州市,高新区";  $sendAddress = '浒墅关工业园青花路128号'; 
		$partnerid = YUNDA_PARTERN_ID_SZSG;  $validation = YUNDA_VALIDATION_ID_SZSG; 
	}elseif($branch=='CDSG'){
		$sendCity = "四川省,成都市,新都区";  $sendAddress = '物流园区燕塘路307号'; 
		$partnerid = YUNDA_PARTERN_ID_CDSG;  $validation = YUNDA_VALIDATION_ID_CDSG; 
	}elseif($branch=='WHSG'){
		$sendCity = "湖北省,武汉市,东西湖区";  $sendAddress = '径河街道小李家墩191号'; 
		$partnerid = YUNDA_PARTERN_ID_WHSG;  $validation = YUNDA_VALIDATION_ID_WHSG; 
	}elseif($branch=='BJSG'){
		$sendCity = "北京市,北京市,大兴区";  $sendAddress = '小龙庄路63号';
		$partnerid = YUNDA_PARTERN_ID_BJSG;  $validation = YUNDA_VALIDATION_ID_BJSG;  
	}elseif($branch=='SHZSG'){
		$sendCity = "广东省,深圳市,光明新区";  $sendAddress = '塘家大道6号';
		$partnerid = YUNDA_PARTERN_ID_SHZSG;  $validation = YUNDA_VALIDATION_ID_SHZSG;  
	}elseif($branch=='TEST'){
		$sendCity = "广东省,深圳市,光明新区";  $sendAddress = '塘家大道6号';
		$partnerid = YUNDA_PARTERN_ID_TEST;  $validation = YUNDA_VALIDATION_ID_TEST; 
		$url = YUNDA_EXPRESS_URL_TEST; //TEST
	}else{
		$return['flag'] = false;
		$return['mes'] = "该划分区域还未申请到账号";
		return $return;
	}
	$SendNowtime = date("Y-m-d H:i:s");
	$sendID = '';
	global $db;
	$action = 'interface_receive_order__mailno.php';
	$xmldata = "<orders>";
	$xmldata .= "<order>";
	$xmldata .= "<order_serial_no>".$receiver['order_id'].$shipment_id.$shipmentCount."</order_serial_no>";
	$xmldata .= "<khddh></khddh><nbckh></nbckh><order_type>common</order_type>";
	$xmldata .= "<sender>";
	$xmldata .= "<name>".$sendName."</name>";
	$xmldata .= "<company></company>";
	$xmldata .= "<city>".$sendCity."</city>";
	$xmldata .= "<address>".$sendCity.$sendAddress."</address>";
	$xmldata .= "<postcode></postcode>";
	$xmldata .= "<phone></phone>";
	$xmldata .= "<mobile>".$sender['sendTel']."</mobile>";
	$xmldata .= "<branch></branch>";
	$xmldata .= "</sender>";
	$xmldata .= "<receiver>";
	$xmldata .= "<name>".htmlspecialchars($receiver['consignee'], ENT_QUOTES)."</name>";
	$xmldata .= "<company></company>";
	$xmldata .= "<city>".htmlspecialchars($receiver['d_province'].",".$receiver['d_city'].",".$receiver['d_district'], ENT_QUOTES)."</city>";
	$xmldata .= "<address>".htmlspecialchars($receiver['d_province'].$receiver['d_city'].$receiver['d_district'].$receiver['address'], ENT_QUOTES)."</address>";
	$xmldata .= "<postcode>".$receiver['zipcode']."</postcode>";
	$xmldata .= "<phone>".$receiver['tel']."</phone>";
	$xmldata .= "<mobile>".$receiver['mobile']."</mobile>";
	$xmldata .= "<branch></branch>";
	$xmldata .= "</receiver>";
	$xmldata .= "<weight></weight><size></size><value></value><collection_value></collection_value><special></special>";
	$xmldata .= "<items>";
	foreach($goods_info as $goods){
		$xmldata .= "<item>"; 
		$xmldata .= "<name>".htmlspecialchars(str_replace('%','',$goods['goods_name']), ENT_QUOTES)."</name>";  
		$xmldata .= "<number>".$goods['num']."</number>";  
		$xmldata .= "<remark></remark>";  
		$xmldata .= "</item>";
	} 
	$xmldata .= "</items>"; 
	$xmldata .= "<remark>".$sendID."</remark><cus_area1></cus_area1><cus_area2></cus_area2><callback_id></callback_id><wave_no></wave_no>"; 
	$xmldata .= "</order>"; 
	$xmldata .= "</orders>";
	$data_digest= md5(Bytes::toStr(Bytes::getBytes(base64_encode($xmldata).$partnerid.$validation)));
	
	$data = array();
	$data['partnerid']=$partnerid;
	$data['version']=1.0;
	$data['request']='data';
	$data['xmldata'] = base64_encode($xmldata);
	$data['validation'] = $data_digest;
//	var_dump("request:");
//	var_dump($data);

	$response = yunda_post_data($url.$action,$data);
	$response_xml = simplexml_load_string($response);
//	var_dump($response_xml->response);
	$result['is_ok'] = (string)$response_xml->response->status;
	$result['mes']  = (string)$response_xml->response->msg;
	if($result['is_ok']){
		$result['flag'] = true;
		$result['tracking_number'] = (string)$response_xml->response->mail_no;
		$pdf_info = $response_xml->response->pdf_info;
		
		$pdf_info_json = json_decode($pdf_info);
		$pdf_detail = $pdf_info_json['0']['0'];
//		var_dump($pdf_detail);
		$package = $pdf_detail->package_wdjc;
		$package_no = $pdf_detail->package_wd;
		$station = $pdf_detail->position;
		$station_no = $pdf_detail->bigpen_code;
		$sender_branch_no = $pdf_detail->sender_branch;
		$sender_branch = $pdf_detail->sender_branch_jc;
		$lattice_mouth_no = $pdf_detail->lattice_mouth_no;
		$tracking_number = $result['tracking_number'];
		if(!empty($tracking_number)){
			$selectfrom = $shipment_id.$shipmentCount;
			$sql = "insert into ecshop.ecs_order_yunda_mailno_apply " .
				"values('{$selectfrom}',{$receiver['shipping_id']},'{$receiver['facility_id']}','{$tracking_number}',
				'',NOW(),'{$package}','{$package_no}','{$station}','{$station_no}','{$sender_branch_no}','{$sender_branch}','{$lattice_mouth_no}')";
			$db->query($sql);	
		}
	}else{
		$result['flag'] = false;
	}
	return $result;
}


//贝亲账户获取面单信息
function yd_bq_sendOrder($branch,$shipment_id,$shipmentCount,$sender,$receiver,$goods_info){
	$result = array();
	if($branch == 'BQ'){
		$partnerid = YUNDA_PARTERN_ID_BQ;
		$validation = YUNDA_VALIDATION_ID_BQ;
		$url = YUNDA_EXPRESS_URL;
		$sendName = "乐其";
		$sendCity = "上海市,上海市,青浦区";
		$sendAddress = "新丹路359号";
	}else{
		$return['flag'] = false;
		$return['mes'] = "该账号只允许贝亲青浦仓，上海精品仓使用，如有调用异常请联系ERP";
		return $return;
	}
	$SendNowtime = date("Y-m-d H:i:s");
	$sendID = '';
	global $db;
	$action = 'interface_receive_order__mailno.php';
	$xmldata = "<orders>";
	$xmldata .= "<order>";
	$xmldata .= "<order_serial_no>".$receiver['order_id'].$shipment_id.$shipmentCount."</order_serial_no>";
	$xmldata .= "<khddh></khddh><nbckh></nbckh><order_type>common</order_type>";
	$xmldata .= "<sender>";
	$xmldata .= "<name>".$sender['sendName']."</name>";
	$xmldata .= "<company></company>";
	$xmldata .= "<city>".$sendCity."</city>";
	$xmldata .= "<address>".$sendCity.$sendAddress."</address>";
	$xmldata .= "<postcode></postcode>";
	$xmldata .= "<phone></phone>";
	$xmldata .= "<mobile>".$sender['sendTel']."</mobile>";
	$xmldata .= "<branch></branch>";
	$xmldata .= "</sender>";
	$xmldata .= "<receiver>";
	$xmldata .= "<name>".htmlspecialchars($receiver['consignee'], ENT_QUOTES)."</name>";
	$xmldata .= "<company></company>";
	$xmldata .= "<city>".htmlspecialchars($receiver['d_province'].",".$receiver['d_city'].",".$receiver['d_district'], ENT_QUOTES)."</city>";
	$xmldata .= "<address>".htmlspecialchars($receiver['d_province'].$receiver['d_city'].$receiver['d_district'].$receiver['address'], ENT_QUOTES)."</address>";
	$xmldata .= "<postcode>".$receiver['zipcode']."</postcode>";
	$xmldata .= "<phone>".$receiver['tel']."</phone>";
	$xmldata .= "<mobile>".$receiver['mobile']."</mobile>";
	$xmldata .= "<branch></branch>";
	$xmldata .= "</receiver>";
	$xmldata .= "<weight></weight><size></size><value></value><collection_value></collection_value><special></special>";
	$xmldata .= "<items>";
	foreach($goods_info as $goods){
		$xmldata .= "<item>"; 
		$xmldata .= "<name>".htmlspecialchars($goods['goods_name'], ENT_QUOTES)."</name>";  
		$xmldata .= "<number>".$goods['num']."</number>";  
		$xmldata .= "<remark></remark>";  
		$xmldata .= "</item>";
	} 
	$xmldata .= "</items>"; 
	$xmldata .= "<remark>".$sendID."</remark><cus_area1></cus_area1><cus_area2></cus_area2><callback_id></callback_id><wave_no></wave_no>"; 
	$xmldata .= "</order>"; 
	$xmldata .= "</orders>";
	$data_digest= md5(Bytes::toStr(Bytes::getBytes(base64_encode($xmldata).$partnerid.$validation)));
	
	$data = array();
	$data['partnerid']=$partnerid;
	$data['version']=1.0;
	$data['request']='data';
	$data['xmldata'] = base64_encode($xmldata);
	$data['validation'] = $data_digest;
	$response = yunda_post_data($url.$action,$data);
	$response_xml = simplexml_load_string($response);
	if($response_xml->response->status=='0'){
		$return['flag'] = false;
		$return['mes'] = $response_xml->response->msg;
	}else{
		$tracking_number = $response_xml->response->mail_no;
		$pdf_info = $response_xml->response->pdf_info;
		if(!empty($tracking_number)){
			$selectfrom = $shipment_id.$shipmentCount;
			$sql = "insert into ecshop.ecs_order_yunda_mailno_apply(shipment_id,shipping_id,facility_id,tracking_number,pdf_info,apply_mailno_time) " .
				"values('{$selectfrom}',{$receiver['shipping_id']},'{$receiver['facility_id']}','{$tracking_number}','{$pdf_info}',NOW())";
			$db->query($sql);	
		}
		$return['flag'] = true;
		$return['tracking_number'] = $tracking_number;
	}
	return $return;
}

/*并未投入使用，在cls_sales_order_action.php被废弃了*/
function yunda_cancel_order($shipping_id,$shipment_id,$tracking_number){
	global $db;
	$action = 'interface_cancel_order.php';
	$partnerid = YUNDA_PARTERN_ID;
	$validation = YUNDA_VALIDATION_ID;
	$xmldata = "<orders>";
	$xmldata .= "<order>";
	$xmldata .= "<order_serial_no>".$shipment_id."</order_serial_no>";
	$xmldata .= "<mailno>".$tracking_number."</mailno>";
	$xmldata .= "</order>";
	$xmldata .= "</orders>";

	$data_digest= md5(Bytes::toStr(Bytes::getBytes(base64_encode($xmldata).$partnerid.$validation)));
	
	$data = array();
	$data['partnerid']=$partnerid;
	$data['version']=1.0;
	$data['request']='cancel_order';
	$data['xmldata'] = base64_encode($xmldata);
	$data['validation'] = $data_digest;
//	var_dump("request:");
//	var_dump($data);
	$response = yunda_post_data(YUNDA_EXPRESS_URL.$action,$data);
	$response_xml = simplexml_load_string($response);
//	var_dump("response_xml:");
//	var_dump($response_xml);
	if($response_xml->response->status =='0'){
		return $response_xml->response->msg;
	}else{
		$sql = "delete from ecshop.ecs_order_yunda_mailno_apply " .
			" where shipping_id = '{$shipping_id}' and shipment_id = '{$shipment_id}' and tracking_number = '{$tracking_number}' ";
		if($db->query($sql)){
			return 'true';
		}else{
			return "中间表数据没有删除成功，请联系ERP处理掉（运单号：{$tracking_number}）";
		}
	}
}

?>