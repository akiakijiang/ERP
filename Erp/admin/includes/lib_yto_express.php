<?php
define('IN_ECS', true);
require_once('init.php');
require_once(ROOT_PATH . 'includes/bytes.php');

define('YT_EXPRESS_URL','http://service.yto56.net.cn/');

define('YTO_PARTERN_ID_WH','fR0Q794A');
define("YTO_CLIENT_ID_WH", "K11102763");
define("YTO_CUSTOMER_ID_WH", "K11102763");

define('YTO_PARTERN_ID_DG','5929Z53O');
define("YTO_CLIENT_ID_DG", "K76968163");
define("YTO_CUSTOMER_ID_DG", "K76968163");

define('YTO_PARTERN_ID_JXSG','XQ4NO2rp');
define("YTO_CLIENT_ID_JXSG", "K01300666");
define("YTO_CUSTOMER_ID_JXSG", "K01300666");

define('YTO_PARTERN_ID_BJSG','cW9wP6jF');
define("YTO_CLIENT_ID_BJSG", "K100513547");
define("YTO_CUSTOMER_ID_BJSG", "K100513547");

define('YTO_PARTERN_ID_SHZSG','v71dSMSD');
define("YTO_CLIENT_ID_SHZSG", "K755164441");
define("YTO_CUSTOMER_ID_SHZSG", "K755164441");

define('YTO_PARTERN_ID_SHSG','9NYE1ReN');
define("YTO_CLIENT_ID_SHSG", "K11106404");
define("YTO_CUSTOMER_ID_SHSG", "K11106404");

define('YTO_PARTERN_ID_SZSG','3eVwp9r8');
define("YTO_CLIENT_ID_SZSG", "K512137516");
define("YTO_CUSTOMER_ID_SZSG", "K512137516");

define('YTO_PARTERN_ID_WHSG','sq7E7Ure');
define("YTO_CLIENT_ID_WHSG", "K11106229");
define("YTO_CUSTOMER_ID_WHSG", "K11106229");

define('YTO_PARTERN_ID_HQWHC','h9iB1eu5');
define("YTO_CLIENT_ID_HQWHC", "k11109825");
define("YTO_CUSTOMER_ID_HQWHC", "k11109825");

define('YTO_PARTERN_ID_JXWBC','5wO6RHFV');
define("YTO_CLIENT_ID_JXWBC", "k210313918");
define("YTO_CUSTOMER_ID_JXWBC", "k210313918");

define('YT_EXPRESS_URL_TEST','http://58.32.246.71:8000/'); //TEST
define('YTO_PARTERN_ID_WH_TEST','u2Z1F7Fh');
define("YTO_CLIENT_ID_WH_TEST", "K21000119");
define("YTO_CUSTOMER_ID_WH_TEST", "K21000119");

function post_data($url,$data){
	$ch = curl_init ();
	curl_setopt ( $ch, CURLOPT_URL, $url );
	curl_setopt ( $ch, CURLOPT_POST, 1 );
	curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	curl_setopt ( $ch, CURLOPT_POSTFIELDS,  http_build_query($data) );
	$res = curl_exec ( $ch );  
	curl_close ( $ch );
	return $res;
}

//批量拉取面单，并通知拉取成功
function yto_applyNewBillCodes($site,$count=1000){
	
	if($count<100){//圆通接口面单批量获取上限100
		return yto_applyNewBillCode($site,$count);
	}
	$result=true;
	$i = 100;
	while($result&& $count>0){
		$result = yto_applyNewBillCode($site,$i);
		$count = $count-$i;
		$i = $count>$i?$i:$count;
	}
	return $result;
}
function yto_applyNewBillCode($site,$count=100){
	global $db;
	$getMailNos = yto_getMailNos($site,$count);
	if($getMailNos != -1){
		$mailNoList = $getMailNos['mailNoList'];
		$sequence = $getMailNos['sequence'];
		foreach($mailNoList as $mailNo){
			foreach($mailNo as $mail){
				$sql = "select count(*) from ecshop.thermal_express_mailnos where tracking_number = '{$mail}' and shipping_id = 85 and branch='{$site}'";
				$exists = $db->getOne($sql);
				if($exists==0){
					$sql = "insert into ecshop.thermal_express_mailnos values('{$mail}','N',85,'{$site}',now(),now()) ";
					$db->query($sql);
				}
			}
		}
		$sendConfirmMail = yto_sendConfirm($site,$sequence);
		return $sendConfirmMail;
	}else{
		return 0;
	}
	
}
function yto_getMailNos($site,$count){
	$url = YT_EXPRESS_URL;
	if($site=='WH'){
		$customer = YTO_CUSTOMER_ID_WH;
		$partern = YTO_PARTERN_ID_WH;
		$client = YTO_CLIENT_ID_WH;
	}elseif($site=='DG'){
		$customer = YTO_CUSTOMER_ID_DG;
		$partern = YTO_PARTERN_ID_DG;
		$client = YTO_CLIENT_ID_DG;
	}elseif($site=='JXSG'){
		$customer = YTO_CUSTOMER_ID_JXSG;
		$partern = YTO_PARTERN_ID_JXSG;
		$client = YTO_CLIENT_ID_JXSG;
	}elseif($site=='BJSG'){
		$customer = YTO_CUSTOMER_ID_BJSG;
		$partern = YTO_PARTERN_ID_BJSG;
		$client = YTO_CLIENT_ID_BJSG;
	}elseif($site=='SHZSG'){
		$customer = YTO_CUSTOMER_ID_SHZSG;
		$partern = YTO_PARTERN_ID_SHZSG;
		$client = YTO_CLIENT_ID_SHZSG;
	}elseif($site=='SHSG'){
		$customer = YTO_CUSTOMER_ID_SHSG;
		$partern = YTO_PARTERN_ID_SHSG;
		$client = YTO_CLIENT_ID_SHSG;
	}elseif($site=='SZSG'){
		$customer = YTO_CUSTOMER_ID_SZSG;
		$partern = YTO_PARTERN_ID_SZSG;
		$client = YTO_CLIENT_ID_SZSG;
	}elseif($site=='WHSG'){
		$customer = YTO_CUSTOMER_ID_WHSG;
		$partern = YTO_PARTERN_ID_WHSG;
		$client = YTO_CLIENT_ID_WHSG;
	}
	$action = 'api!synWaybill.action';
	$string ="<MailNoRequest>";
	$string .= "<customerCode>".$customer."</customerCode>";
	$string .= "<quantity>".$count."</quantity>";
	$string .="<type>offline</type>";
	$string .= "<materialCode>DZ100301</materialCode>";
	$string .= "</MailNoRequest>";//普通电子面单DZ100301，COD电子面单DZ100302
	$data_digest= base64_encode(Bytes::toStr(Bytes::getBytes(md5($string.$partern,true))));
//	$string_xml = simplexml_load_string($string);
//	var_dump("get_string_xml:");
//	var_dump($string_xml);
	$data=array();
	$data['logistics_interface']=$string;//urlencode();
	$data['data_digest']=($data_digest);
	$data['clientId']=$client;
	
	$response = post_data($url.$action,$data);
	$response_xml = simplexml_load_string($response);
//	var_dump("get_response_xml:");
//	var_dump($response_xml);
	if($response_xml->success=='true'){
		 $returnMailNo['sequence'] = $response_xml->sequence;
		 $returnMailNo['mailNoList'] = $response_xml->mailNoList;
		 return $returnMailNo;
	}
	return -1;
}
function  yto_sendConfirm($site,$sequence){
	$url = YT_EXPRESS_URL;
	if($site=='WH'){
		$customer = YTO_CUSTOMER_ID_WH;
		$partern = YTO_PARTERN_ID_WH;
		$client = YTO_CLIENT_ID_WH;
	}elseif($site=='DG'){
		$customer = YTO_CUSTOMER_ID_DG;
		$partern = YTO_PARTERN_ID_DG;
		$client = YTO_CLIENT_ID_DG;
	}elseif($site=='JXSG'){
		$customer = YTO_CUSTOMER_ID_JXSG;
		$partern = YTO_PARTERN_ID_JXSG;
		$client = YTO_CLIENT_ID_JXSG;
	}elseif($site=='BJSG'){
		$customer = YTO_CUSTOMER_ID_BJSG;
		$partern = YTO_PARTERN_ID_BJSG;
		$client = YTO_CLIENT_ID_BJSG;
	}elseif($site=='SHZSG'){
		$customer = YTO_CUSTOMER_ID_SHZSG;
		$partern = YTO_PARTERN_ID_SHZSG;
		$client = YTO_CLIENT_ID_SHZSG;
	}elseif($site=='SHSG'){
		$customer = YTO_CUSTOMER_ID_SHSG;
		$partern = YTO_PARTERN_ID_SHSG;
		$client = YTO_CLIENT_ID_SHSG;
	}elseif($site=='SZSG'){
		$customer = YTO_CUSTOMER_ID_SZSG;
		$partern = YTO_PARTERN_ID_SZSG;
		$client = YTO_CLIENT_ID_SZSG;
	}elseif($site=='WHSG'){
		$customer = YTO_CUSTOMER_ID_WHSG;
		$partern = YTO_PARTERN_ID_WHSG;
		$client = YTO_CLIENT_ID_WHSG;
	}
	$action = 'api!synWaybill.action';
	$string = "<MailNoRequest>";
	$string .= "<customerCode>".$customer."</customerCode>";
	$string .= "<sequence>".$sequence."</sequence>";
	$string .= "</MailNoRequest>";
	$data_digest= base64_encode(Bytes::toStr(Bytes::getBytes(md5($string.$partern,true))));
//	$string_xml = simplexml_load_string($string);
//	var_dump("send_string_xml:");
//	var_dump($string_xml);
	$data=array();
	$data['logistics_interface']=$string;//urlencode();
	$data['data_digest']=($data_digest);
	$data['clientId']=$client;
	
	$response = post_data($url.$action,$data);
	$response_xml = simplexml_load_string($response);
//	var_dump("send_response_xml:");
//	var_dump($response_xml);
	return $response_xml->success;
}
//A模式批量拉取面单，使用记录回传
function yto_reportPrintedBillInfo($shipment_id){
	global $db;
	$sql="SELECT
			s.SHIPMENT_ID,
			s.TRACKING_NUMBER billno,
			o.shipping_time senddate,
			o.facility_id,
			o.party_id,
			o.order_id orderid,
			o.order_sn orderno,
			o.consignee receiveperson,
			o.zipcode receivecode,
			IF(o.tel is null or o.tel='',o.mobile,o.tel) as receivetel,
			er_p.region_name receiveprovince,
			er_c.region_name receivecity,
			er_d.region_name receivearea,
			o.address receiveaddress
		FROM
			romeo.shipment s
		LEFT JOIN ecshop.ecs_order_info o ON s.primary_order_id = o.order_id
		LEFT JOIN ecshop.ecs_region er_p ON o.province = er_p.region_id
		LEFT JOIN ecshop.ecs_region er_c ON o.city = er_c.region_id
		LEFT JOIN ecshop.ecs_region er_d ON o.district = er_d.region_id
		WHERE
			s.SHIPMENT_ID = '{$shipment_id}'
		LIMIT 1";
	$bill_info=$db->getRow($sql);
	
	$mailNo=$bill_info['billno'];

	$site=getBranchWithFacilityId($bill_info['facility_id']);
	$url = YT_EXPRESS_URL;
	$sendMan='乐其';
	$sendManPhone='0571-28329302';
	$orderno = $bill_info['orderno'];
	if($site=='WH'){
		$customer = YTO_CUSTOMER_ID_WH;  $partern = YTO_PARTERN_ID_WH;  $client = YTO_CLIENT_ID_WH;
		$sendProvince='湖北';  $sendCity='武汉';  $sendAddress='东西湖区';
	}elseif($site=='DG'){
		$customer = YTO_CUSTOMER_ID_DG;  $partern = YTO_PARTERN_ID_DG;  $client = YTO_CLIENT_ID_DG;
		$sendProvince='广东';  $sendCity='东莞';  $sendAddress='长安镇';
	}elseif($site=='JXSG'){
		$customer = YTO_CUSTOMER_ID_JXSG;  $partern = YTO_PARTERN_ID_JXSG;  $client = YTO_CLIENT_ID_JXSG;
		$sendProvince='浙江';  $sendCity='嘉兴';  $sendAddress='南湖区';
	}elseif($site=='BJSG'){
		$customer = YTO_CUSTOMER_ID_BJSG;  $partern = YTO_PARTERN_ID_BJSG;  $client = YTO_CLIENT_ID_BJSG;
		$sendProvince='北京';  $sendCity='北京';  $sendAddress='大兴区';
	}elseif($site=='SHZSG'){
		$customer = YTO_CUSTOMER_ID_SHZSG;  $partern = YTO_PARTERN_ID_SHZSG;  $client = YTO_CLIENT_ID_SHZSG;
		$sendProvince='广东';  $sendCity='深圳';  $sendAddress='光明新区';
	}elseif($site=='SHSG'){
		$customer = YTO_CUSTOMER_ID_SHSG;  $partern = YTO_PARTERN_ID_SHSG;  $client = YTO_CLIENT_ID_SHSG;
		$sendProvince='上海';  $sendCity='上海';  $sendAddress='青浦区';
	}elseif($site=='SZSG'){
		$customer = YTO_CUSTOMER_ID_SZSG;  $partern = YTO_PARTERN_ID_SZSG;  $client = YTO_CLIENT_ID_SZSG;
		$sendProvince='江苏';  $sendCity='苏州';  $sendAddress='高新区';
	}elseif($site=='WHSG'){
		$customer = YTO_CUSTOMER_ID_WHSG;  $partern = YTO_PARTERN_ID_WHSG;  $client = YTO_CLIENT_ID_WHSG;
		$sendProvince='湖北';  $sendCity='武汉';  $sendAddress='东西湖区';
	}else{
		return false;
	}
	$sql = "select CONCAT_WS('_',goods_id,style_id) goods_style,goods_name,goods_number from ecshop.ecs_order_goods where order_id = '{$bill_info['orderid']}' ";
	$goods = $db->getAll($sql);
	
	$receiveMan=$bill_info['receiveperson'];
	$receivecode = $bill_info['receivecode'];
	$receiveManPhone=$bill_info['receivetel'];
	$receiveProvince=$bill_info['receiveprovince'];
	$receiveCity=$bill_info['receivecity'];
	$receiveDistrict=$bill_info['receivearea'];
	$receiveAddress=$bill_info['receiveaddress'];
	$date= date('Y-m-d',time());
	$action = 'CommonOrderServlet.action';
	$string = "<RequestOrder>";
	$string  .= "<clientID>".$client."</clientID>";
	$string  .= "<logisticProviderID>YTO</logisticProviderID>";
	$string  .= "<customerId>".$customer."</customerId>";
	$string  .= "<txLogisticID>".$client.$orderno."</txLogisticID>";
	$string  .= "<tradeNo>".$orderno."</tradeNo>";
	$string  .= "<mailNo>".$mailNo."</mailNo>";//A模式
	$string  .= "<totalServiceFee>0</totalServiceFee>";
	$string  .= "<codSplitFee>0</codSplitFee>";
	$string  .= "<orderType>1</orderType>";
	$string  .= "<serviceType>0</serviceType>";
	$string  .= "<flag>1</flag>";
	$string  .= "<sender>";
	$string  .= "<name>".$sendMan."</name>";
	$string  .= "<postCode>0</postCode>";
	$string  .= "<phone>0</phone>";
	$string  .= "<mobile>".$sendManPhone."</mobile>";
	$string  .= "<prov>".$sendProvince."</prov>";
	$string  .= "<city>".$sendCity."</city>";
	$string  .= "<address>".$sendAddress."</address>";
	$string  .= "</sender>";
	$string  .= "<receiver>";
	$string  .= "<name>".htmlspecialchars($receiveMan, ENT_QUOTES)."</name>";
	$string  .= "<postCode>".$receivecode."</postCode>";
	$string  .= "<phone>".$receiveManPhone."</phone>";
	$string  .= "<prov>".htmlspecialchars($receiveProvince, ENT_QUOTES)."</prov>";
	$string  .= "<city>".htmlspecialchars($receiveCity, ENT_QUOTES).",".htmlspecialchars($receiveDistrict, ENT_QUOTES)."</city>";
	$string  .= "<address>".htmlspecialchars($receiveAddress, ENT_QUOTES)."</address>";
	$string  .= "</receiver>";
	$string  .= "<sendStartTime>".$date." 08:00:00</sendStartTime>";
	$string  .= "<sendEndTime>".$date." 24:00:00</sendEndTime>";
	$string  .= "<goodsValue>0</goodsValue>";
	$string  .= "<itemsValue>0</itemsValue>";
	$string  .= "<items>";
	foreach ($goods as $good){
		$string  .= "<item>";
		$string  .= "<itemName>".htmlspecialchars($good['goods_name'], ENT_QUOTES)."</itemName>";
		$string  .= "<number>".$good['goods_number']."</number>";
		$string  .= "<itemValue>0</itemValue>";
		$string  .= "</item>";
	}
	$string  .= "</items>";
	$string  .= "<insuranceValue>0</insuranceValue>";
	$string  .= "<special>0</special>";
	$string  .= "<remark>0</remark>";
	$string  .= "</RequestOrder>";
	
	$data_digest= base64_encode(Bytes::toStr(Bytes::getBytes(md5($string.$partern,true))));
	$data=array();
	$data['logistics_interface']=$string;//urlencode();
	$data['data_digest']=($data_digest);
	$data['clientId']=$client;
//	$request_xml = (simplexml_load_string($string)); 
//var_dump($request_xml);
	$response = post_data($url.$action,$data);
	$response_xml = (simplexml_load_string($response)); 
//	var_dump($response_xml);
	if($response_xml->success!='false'){
		return true; 
	}else{
		return false;
	}
}

//B模式单个拉取面单
function shipOrder_yto($site,$shipmentId,$record, $order, $goods){
	$url = YT_EXPRESS_URL;
	$record['sender_name'] = '乐其';
	if($site=='WH'){
		$customer = YTO_CUSTOMER_ID_WH;  $partern = YTO_PARTERN_ID_WH;  $client = YTO_CLIENT_ID_WH;
		$record['sender_prov']='湖北';  $record['sender_city']='武汉';  $record['sender_address']='东西湖区';
	}elseif($site=='DG'){
		$customer = YTO_CUSTOMER_ID_DG;  $partern = YTO_PARTERN_ID_DG;  $client = YTO_CLIENT_ID_DG;
		$record['sender_prov']='广东';  $record['sender_city']='东莞';  $record['sender_address']='长安镇';
	}elseif($site=='JXSG'){
		$customer = YTO_CUSTOMER_ID_JXSG;  $partern = YTO_PARTERN_ID_JXSG;  $client = YTO_CLIENT_ID_JXSG;
		$record['sender_prov']='浙江';  $record['sender_city']='嘉兴';  $record['sender_address']='南湖区';
	}elseif($site=='BJSG'){
		$customer = YTO_CUSTOMER_ID_BJSG;  $partern = YTO_PARTERN_ID_BJSG;  $client = YTO_CLIENT_ID_BJSG;
		$record['sender_prov']='北京';  $record['sender_city']='北京';  $record['sender_address']='大兴区';
	}elseif($site=='SHZSG'){
		$customer = YTO_CUSTOMER_ID_SHZSG;  $partern = YTO_PARTERN_ID_SHZSG;  $client = YTO_CLIENT_ID_SHZSG;
		$record['sender_prov']='广东';  $record['sender_city']='深圳';  $record['sender_address']='光明新区';
	}elseif($site=='SHSG'){
		$customer = YTO_CUSTOMER_ID_SHSG;  $partern = YTO_PARTERN_ID_SHSG;  $client = YTO_CLIENT_ID_SHSG;
		$record['sender_prov']='上海';  $record['sender_city']='上海';  $record['sender_address']='青浦区';
	}elseif($site=='SZSG'){
		$customer = YTO_CUSTOMER_ID_SZSG;  $partern = YTO_PARTERN_ID_SZSG;  $client = YTO_CLIENT_ID_SZSG;
		$record['sender_prov']='江苏';  $record['sender_city']='苏州';  $record['sender_address']='高新区';
	}elseif($site=='WHSG'){
		$customer = YTO_CUSTOMER_ID_WHSG;  $partern = YTO_PARTERN_ID_WHSG;  $client = YTO_CLIENT_ID_WHSG;
		$record['sender_prov']='湖北';  $record['sender_city']='武汉';  $record['sender_address']='东西湖区';
	}elseif($site=='TEST'){
		$url = YT_EXPRESS_URL_TEST;
		$customer = YTO_CUSTOMER_ID_WH_TEST;  $partern = YTO_PARTERN_ID_WH_TEST;  $client = YTO_CLIENT_ID_WH_TEST;
		$record['sender_prov']='湖北';  $record['sender_city']='武汉';  $record['sender_address']='东西湖区';
	}elseif($site=='HQWHC'){
		//湖北省武汉市东西湖区张柏路220号（东光工业园道达尔加油站旁进来500米金广信园区2号库  孙国平 18571665455
		$customer = YTO_CUSTOMER_ID_HQWHC;  $partern = YTO_PARTERN_ID_HQWHC;  $client = YTO_CLIENT_ID_HQWHC;
		$record['sender_prov']='湖北';  $record['sender_city']='武汉';  $record['sender_address']='东西湖区';
	}elseif($site=='JXWBC'){
		//嘉兴市南湖区大桥镇工业园区欧嘉路与明新路交叉口
		$customer = YTO_CUSTOMER_ID_JXWBC;  $partern = YTO_PARTERN_ID_JXWBC;  $client = YTO_CLIENT_ID_JXWBC;
		$record['sender_prov']='浙江';  $record['sender_city']='嘉兴';  $record['sender_address']='南湖区大桥镇工业园区欧嘉路与明新路交叉口';
	}else{
		$return['flag'] = false;
		$return['mes'] = "该划分区域还未申请到账号";
		return $return;
	}
	$date= date('Y-m-d',time());
	$action = 'CommonOrderModeBServlet.action';
	$string = "<RequestOrder>";
	$string  .= "<clientID>".$client."</clientID>";
	$string  .= "<logisticProviderID>YTO</logisticProviderID>";
	$string  .= "<customerId>".$customer."</customerId>";
	$string  .= "<txLogisticID>".$client.$order['order_id'].$shipmentId."</txLogisticID>";
	$string  .= "<tradeNo>".$order['order_sn']."</tradeNo>";
	$string  .= "<totalServiceFee>0</totalServiceFee>";
	$string  .= "<codSplitFee>0</codSplitFee>";
	$string  .= "<orderType>1</orderType>";
	$string  .= "<serviceType>0</serviceType>";
	$string  .= "<flag>1</flag>";
	$string  .= "<sender>";
	$string  .= "<name>".$record['sender_name']."</name>";
	$string  .= "<postCode>".$record['sender_postcode']."</postCode>";
	$string  .= "<phone>".$record['sender_phone']."</phone>";
	$string  .= "<mobile>0</mobile>";
	$string  .= "<prov>".$record['sender_prov']."</prov>";
	$string  .= "<city>".$record['sender_city']."</city>";
	$string  .= "<address>".$record['sender_address']."</address>";
	$string  .= "</sender>";
	$string  .= "<receiver>";
	$string  .= "<name>".htmlspecialchars($order['consignee'], ENT_QUOTES)."</name>";
	$string  .= "<postCode>0</postCode>";
	$string  .= "<phone>".$order['t_mobile']."</phone>";
	$string  .= "<prov>".$order['province_name']."</prov>";
	$string  .= "<city>".$order['city_name'].",".$order['district_name']."</city>";
	$string  .= "<address>".htmlspecialchars($order['address'], ENT_QUOTES)."</address>";
	$string  .= "</receiver>";
	$string  .= "<sendStartTime>".$date." 08:00:00</sendStartTime>";
	$string  .= "<sendEndTime>".$date." 24:00:00</sendEndTime>";
	$string  .= "<goodsValue>0</goodsValue>";
	$string  .= "<itemsValue>0</itemsValue>";
	$string  .= "<items>";
	foreach ($goods as $good){
		$string  .= "<item>";
		$string  .= "<itemName>".htmlspecialchars(str_replace('%','',$good['goods_name']), ENT_QUOTES)."</itemName>";
		$string  .= "<number>".$good['num']."</number>";
		$string  .= "<itemValue>0</itemValue>";
		$string  .= "</item>";
	}
	$string  .= "</items>";
	$string  .= "<insuranceValue>0</insuranceValue>";
	$string  .= "<special>0</special>";
	$string  .= "<remark>0</remark>";
	$string  .= "</RequestOrder>";
	
	$data_digest= base64_encode(Bytes::toStr(Bytes::getBytes(md5($string.$partern,true))));
	$data=array();
	$data['logistics_interface']=$string;//urlencode();
	$data['data_digest']=($data_digest);
	$data['clientId']=$client;
	$request_xml = (simplexml_load_string($string)); 
//var_dump($request_xml);
	$response = post_data($url.$action,$data);
//	var_dump("response:");
//	var_dump($response);
	$response_xml = simplexml_load_string($response); 
	if($response_xml->success!='false'){
		$return['flag'] = true;
		$return['tracking_number'] = $response_xml->orderMessage->mailNo;
		$return['bigPen'] = $response_xml->orderMessage->bigPen;
	}else{
		$return['flag'] = false;
		$return['mes'] = $response_xml->reason;
	}
	return $return;
	
}

//B模式取消面单使用，然而并未投入使用，在cls_sales_order_action.php已被限制
function yto_cancel_order($site,$order_sn,$tracking_number){
	$url = YT_EXPRESS_URL_TEST;
	$customer = YTO_CUSTOMER_ID_WH_TEST;
	$partern = YTO_PARTERN_ID_WH_TEST;
	$client = YTO_CLIENT_ID_WH_TEST;
	if(empty($partern) || empty($client) || empty($customer)){
		return null;
	}
	$action = 'CommonOrderModeBServlet.action';
	$string = "<UpdateInfo>";
	$string  .= "<logisticProviderID>YTO</logisticProviderID>";
	$string  .= "<clientID>".$client."</clientID>";
//	$string  .= "<customerId>".$customer."</customerId>";
	$string  .= "<mailNo>".$tracking_number."</mailNo>";
	$string  .= "<txLogisticID>".$client.$order_sn."</txLogisticID>";
	$string  .= "<infoType>INSTRUCTION</infoType>";
	$string  .= "<infoContent>WITHDRAW</infoContent>";
	$string  .= "<remark>0</remark>";
	$string  .= "</UpdateInfo>";
//	var_dump($string);
	$data_digest= base64_encode(Bytes::toStr(Bytes::getBytes(md5($string.$partern,true))));
	$data=array();
	$data['logistics_interface']=$string;//urlencode();
	$data['data_digest']=($data_digest);
	$data['clientId']=$client;
//$request_xml = (simplexml_load_string($string)); 
//var_dump($request_xml);
	$response = post_data($url.$action,$data);
	$response_xml = (simplexml_load_string($response));
var_dump($response_xml);
	if($response_xml->success!='false'){
		return 'true';
	}else{
		return "ERP订单：".$order_sn."取消失败，原因：".$response_xml->reason;
	}
}

?>