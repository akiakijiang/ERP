<?php
/**
 * 汇通接口对接
 */
//define('IN_ECS', true);
require_once('init.php');
require_once('admin/function.php');
require_once(ROOT_PATH . 'includes/HttpClient.php');
require_once (ROOT_PATH . "admin/PartyXTelephone.php");

define(HT_TEST_URL,'http://183.129.172.49/ems/api/process');
define(HT_TEST_PARTNER_ID,'GUANYI_DZMD');
define(HT_TEST_PARTNER_KEY,'12345');

define(HT_EXPRESS_BASE_PATH, 'http://ebill.ns.800best.com/ems/api/process');

define(HT_PARTNER_ID_SH,'201714_0005');
define(HT_PARTNER_KEY_SH,'zxcvbng845fdgh');

//北京仓汇通测试
define(HT_TESTBJ_URL,'http://183.129.172.49/ems/api/process');
define(HT_TESTBJ_PARTNER_ID,'TESTXML');
define(HT_TESTBJ_PARTNER_KEY,'12345');

//北京仓汇通
//define(HT_PARTNER_ID_BJ,'101136_0003');
//define(HT_PARTNER_KEY_BJ,'eidkxkdj893kaxd');
//新密钥
//define(HT_PARTNER_ID_BJ,'100008_0001');
//define(HT_PARTNER_KEY_BJ,'Zb6F7Tko4KoY');
//再改密钥
//define(HT_PARTNER_ID_BJ,'102051_0001');
//define(HT_PARTNER_KEY_BJ,'DhZ33frWkweV');
define(HT_PARTNER_ID_BJ,'102119_0001');
define(HT_PARTNER_KEY_BJ,'k5U0ocqWohTN');

//嘉兴水果
define(HT_PARTNER_ID_JXSG,'314030_0037');
define(HT_PARTNER_KEY_JXSG,'XAA9i2bQSeux');
//上海水果
define(HT_PARTNER_ID_SHSG,'201714_0088');
define(HT_PARTNER_KEY_SHSG,'NgzXFRI6JOHe');
//康贝奉贤==上海水果
define(HT_PARTNER_ID_KBFX,'201714_0088');
define(HT_PARTNER_KEY_KBFX,'NgzXFRI6JOHe');
//北京水果
define(HT_PARTNER_ID_BJSG,'100768_0004');
define(HT_PARTNER_KEY_BJSG,'DkxdRkvpgj38');
//武汉水果
define(HT_PARTNER_ID_WHSG,'430009_9');
define(HT_PARTNER_KEY_WHSG,'FRGbFcUQ0PjX');
//万霖北京仓
define(HT_PARTNER_ID_WLBJC,'102119_0004');
define(HT_PARTNER_KEY_WLBJC,'1rvf7D3GR4Fu');

function _ht_error_code_means($errorCode){
	switch ($errorCode) {
		case 'S01':
			return '系统错误';
			break;
		case 'S02':
			return '签名验证失败';
			break;
		case 'S03':
			return '无法识别的ServiceType';
			break;
		case 'S04':
			return '请求格式错误';
			break;
		case 'B01':
			return '可用单号不足';
			break;
		case 'B02':
			return '用户不存在';
			break;
		case 'B10':
			return '获取电子面单超过数量限制';
			break;
		case 'B99':
			return 'msgId为空';
			break;
		
		default:
			return '惟爾知，惟殷先人有典有冊，殷革夏命';
			break;
	}
}

function _ht_request($bizData,$serviceType,$site){
	$url=HT_EXPRESS_BASE_PATH;
	
	if($site=='SH'){
		$partner_id=HT_PARTNER_ID_SH;
		$partner_key=HT_PARTNER_KEY_SH;
	}elseif($site=='BJ'){
		$partner_id=HT_PARTNER_ID_BJ;
		$partner_key=HT_PARTNER_KEY_BJ;
	}elseif($site=='JXSG'){
		$partner_id=HT_PARTNER_ID_JXSG;
		$partner_key=HT_PARTNER_KEY_JXSG;
	}elseif($site=='SHSG'){
		$partner_id=HT_PARTNER_ID_SHSG;
		$partner_key=HT_PARTNER_KEY_SHSG;
	}elseif($site=='WHSG'){
		$partner_id=HT_PARTNER_ID_WHSG;
		$partner_key=HT_PARTNER_KEY_WHSG;
	}elseif($site=='BJSG'){
		$partner_id=HT_PARTNER_ID_BJSG;
		$partner_key=HT_PARTNER_KEY_BJSG;
	}elseif($site=='KBFX'){
		$partner_id=HT_PARTNER_ID_KBFX;
		$partner_key=HT_PARTNER_KEY_KBFX;
	}elseif($site=='TEST'){
		$partner_id=HT_TEST_PARTNER_ID;
		$partner_key=HT_TEST_PARTNER_KEY;
		$url=HT_TEST_URL;
	}elseif($site=='WLBJC'){
		$partner_id=HT_PARTNER_ID_WLBJC;
		$partner_key=HT_PARTNER_KEY_WLBJC;
	}

	$digest=base64_encode(md5($bizData.$partner_key,true));
	$data=array(
		'bizData'=>$bizData,
		'serviceType'=>$serviceType,
		'parternID'=>$partner_id,
		'digest'=>$digest,
		'msgId'=>uniqid('HT_Arata'),
	);
	$method='POST';
	
	$response=HttpClient::quickPost($url,$data);
	return $response;
}

//function ht_applyNewBillCode($site,$count=2){
function ht_applyNewBillCode($site,$count=200){
	global $db;	
	//global $smarty;
	$xml ='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
	'<FetchBillCodeRequest xmlns:ems="http://express.800best.com">'.
		'<count>'.$count.'</count>'.
	'</FetchBillCodeRequest>';
	$response=_ht_request($xml,'BillCodeFetchRequest',$site);//BillCodeFetchRequest
	$obj=simplexml_load_string($response);
	//$trackNumArr = array();
	if($obj->success=='SUCCESS' || $obj->success=='success' || $obj->result=='success' || $obj->result=='SUCCESS'){
		foreach ($obj->billCodes as $key => $value) {
			$sql = "INSERT into ecshop.thermal_express_mailnos (tracking_number,shipping_id,branch,create_time,update_time) 
			values ('{$value}',99,'{$site}',now(),now()) ";
			 //echo "APPLIED: $sql";
			$db->query($sql);
			//array_push($trackNumArr, $value);
		}
		//$trackNumCount = sizeof($trackNumArr);
		//$trackNumStr = (string)implode(',',$trackNumArr);
		//$smarty->assign('trackNumCount',$trackNumCount);
		//$smarty->assign('trackNumStr',$trackNumStr);
		return true;
	}else{
		print_r($obj);
		echo "FAILED! Error Code: ".$obj->errorCode."("._ht_error_code_means($obj->errorCode).") Error Description: ".$obj->errorDesc;
	}
	return false;
}

function ht_reportPrintedBillInfo($shipment_id){
	global $db;
	$sql="SELECT
			s.SHIPMENT_ID,
			s.TRACKING_NUMBER billno,
			o.shipping_time senddate,
			o.facility_id,
			o.party_id,
			o.order_sn orderno,
			o.consignee receiveperson,
			o.mobile receivetel,
			er_p.region_name receiveprovince,
			er_c.region_name receivecity,
			er_d.region_name receivearea,
			o.address receiveaddress
		FROM
			romeo.shipment s
		LEFT JOIN ecshop.ecs_order_info o ON s.primary_order_id = o.order_id
		LEFT JOIN ecshop.ecs_region er_p ON o.province = er_p.region_id
		LEFT JOIN ecshop.ecs_region er_c ON o.city = er_c.region_id
		LEFT JOIN ecshop.ecs_region er_d ON o.city = er_d.region_id
		WHERE
			s.SHIPMENT_ID = '{$shipment_id}'
		LIMIT 1";
	$bill_info=$db->getRow($sql);

	$mailNo=$bill_info['billno'];

	$site=getLocalBranchWithFacilityId($bill_info['facility_id']);

	if($site=='SH'){
		$sendMan='乐其';
		$sendManPhone='021-51876842';
		$sendManAddress='上海青浦';
		$sendProvince='上海';
		$sendCity='上海';
		$sendCounty='青浦';
	}elseif($site=='BJ'){
		$sendMan='乐其';
		$sendManPhone='010-57739408';
		$sendManAddress='北京市通州马驹桥镇杨秀店168号';
		$sendProvince='北京';
		$sendCity='北京';
		$sendCounty='通州';
	}elseif($site=='JXSG'){
		$sendManAddress='浙江嘉兴南湖区';
		$sendProvince='浙江';
		$sendCity='嘉兴';
		$sendCounty='南湖区';
	}elseif($site=='SHSG'){
		$sendManAddress='上海青浦';
		$sendProvince='上海';
		$sendCity='上海';
		$sendCounty='青浦';
	}elseif($site=='WHSG'){
		$sendManAddress='湖北武汉东西湖区';
		$sendProvince='湖北';
		$sendCity='武汉';
		$sendCounty='东西湖区';
	}elseif($site=='BJSG'){
		$sendManAddress='北京大兴';
		$sendProvince='北京';
		$sendCity='北京';
		$sendCounty='大兴';
	}elseif($site=='KBFX'){
		$sendManAddress='上海奉贤';
		$sendProvince='上海';
		$sendCity='上海';
		$sendCounty='奉贤';
	}
	$receiveMan=$bill_info['receiveperson'];
	$receiveManPhone=$bill_info['receivetel'];
	$receiveManAddress=$bill_info['receiveaddress'];
	$receiveProvince=$bill_info['receiveprovince'];
	$receiveCity=$bill_info['receivearea'];
	$receiveCounty=$bill_info['receiveperson'];

	$xml='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.
	'<BillCodeFeedbackRequest xmlns:ems="http://express.800best.com">'.
		'<createPrintFeedbackList>'.
			'<txLogisticID>'.$bill_info['orderno'].'</txLogisticID>'.
			'<mailNo>'.$mailNo.'</mailNo>'.
			'<sendMan>'.$sendMan.'</sendMan>'.
			'<sendManPhone>'.$sendManPhone.'</sendManPhone>'.
			'<sendManAddress>'.$sendManAddress.'</sendManAddress>'.
			'<sendProvince>'.$sendProvince.'</sendProvince>'.
			'<sendCity>'.$sendCity.'</sendCity>'.
			'<sendCounty>'.$sendCounty.'</sendCounty>'.
			'<receiveMan>'.$receiveMan.'</receiveMan>'.
			'<receiveManPhone>'.$receiveManPhone.'</receiveManPhone>'.
			'<receiveManAddress>'.$receiveManAddress.'</receiveManAddress>'.
			'<receiveProvince>'.$receiveProvince.'</receiveProvince>'.
			'<receiveCity>'.$receiveCity.'</receiveCity>'.
			'<receiveCounty>'.$receiveCounty.'</receiveCounty>'.
		'</createPrintFeedbackList>'.
	'</BillCodeFeedbackRequest>';

	// $site='TEST';//TEST
	$response=_ht_request($xml,'BillPrintDeliveryRequest',$site);//BillPrintDeliveryRequest

	$obj=simplexml_load_string($response);
	
	if($obj->success=='SUCCESS' || $obj->success=='success' || $obj->result=='success' || $obj->result=='SUCCESS'){
		return true;
	}else{
		print_r($obj);
		echo "FAILED! Error Code: ".$obj->errorCode."("._ht_error_code_means($obj->errorCode).") Error Description: ".$obj->errorDesc;
	}
	return false;
}

function ht_checkBillStat($site){
	$xml = "<?xml version='1.0' encoding='UTF-8' standalone='yes'?><BillCodeRequest xmlns:ems='http://express.800best.com'><printSite></printSite></BillCodeRequest>";
	//$xml = new SimpleXMLElement($xml);
	//echo "xml=<pre>".$xml->asXML()."</pre>";
	$response=_ht_request($xml,'BillCodeRequest',$site);
	$obj=simplexml_load_string($response);
	
	if($obj->success=='SUCCESS' || $obj->success=='success' || $obj->result=='success' || $obj->result=='SUCCESS'){
		return $obj->count;
	}else{
		print_r($obj);
		echo "FAILED! Error Code: ".$obj->reason."("._ht_error_code_means($obj->reason).")";
	}
	return 0;
}

function ht_auto_apply_mailno($site){
	$available=ht_checkBillStat($site);
	$count=0;
	$added=false;
	while($available>0){
		$done=ht_applyNewBillCode($site);
		$count+=1;
		$added = $added || $done;
		if(!$done || $count>20){
			break;
		}
	}
	return $added;
}

function shipOrder_ht($site,$shipmentId,$record, $order){

	$xml = "<?xml version='1.0' encoding='UTF-8' standalone='yes' ?>
			<PrintRequest xmlns:ems='http://express.800best.com'> 
			<deliveryConfirm>true</deliveryConfirm> 
			<EDIPrintDetailList>
			<sendMan>乐其</sendMan>
			<sendManPhone>".$record['sender_phone']."</sendManPhone>
			<sendManAddress>上海青浦区</sendManAddress>
			<sendPostcode></sendPostcode>
			<sendProvince>上海</sendProvince>
			<sendCity>上海</sendCity>
			<sendCounty>上海青浦区</sendCounty>
			<receiveMan>".htmlspecialchars($order['consignee'], ENT_QUOTES)."</receiveMan>
			<receiveManPhone>".$order['tel']."</receiveManPhone>
			<receiveManAddress><![CDATA[".$order['address']."]]></receiveManAddress>
			<receivePostcode>".$order['zipcode']."</receivePostcode>
			<receiveProvince>".$order['province_name']."</receiveProvince>
			<receiveCity>".$order['city_name']."</receiveCity>
			<receiveCounty>".$order['district_name']."</receiveCounty>
			<txLogisticID>".$order['order_id'].$shipmentId."</txLogisticID>
			<itemName>母婴用品</itemName>
			<itemWeight>0</itemWeight>
			<itemCount>1</itemCount>
			<remark></remark>
			</EDIPrintDetailList> 
			</PrintRequest>";
	$response=_ht_request($xml,'BillPrintRequest',$site); 
	$response_arr=simplexml_load_string($response);

	if($response_arr->result == 'SUCCESS' && !empty($response_arr->EDIPrintDetailList)){
		foreach($response_arr->EDIPrintDetailList as $value){
			$return['flag'] = true;
			$return['tracking_number'] = $value->mailNo;
			$return['bigPen'] = $value->markDestination;
		}
	}else{
		$return['flag'] = false;
		$return['mes'] = $response_arr->errorCode.$response_arr->errorDesc;
	}
	return $return;
	
}
?>