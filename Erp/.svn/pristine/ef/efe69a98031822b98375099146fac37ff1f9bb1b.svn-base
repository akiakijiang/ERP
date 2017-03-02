<?php
require_once('init.php');
require_once('lib_zto_express.php');
require_once('lib_sto_express.php');
require_once('lib_ht_express.php');
require_once('lib_jd_express.php');
require_once('lib_yto_express.php');
require_once('lib_yunda_express.php');
require_once('lib_sf_express.php');
require_once('lib_sf_arata_insure.php');


function getLocalBranchWithFacilityId($facility_id,$distributor_id='0',$shipping_id='0'){
	//使用京东货到付款店铺
	$jd_distributor_ids = array('1950','2010');
	//北京仓
	$beijingcang = array('100170589','42741887','79256821','83077350','119603092');
	//上海仓
	$shanghaicang = array('137059424','120801050','12768420', '19568549', '22143846', '22143847','24196974', 
		'3633071', '69897656', '81569822', '81569823', '83972713', '83972714',
		'119603093','119603091','119603094','137059426','149849259');
	//康贝奉贤仓
	$kbfengxian = array();//'119603094'后续要移动到奉贤，从上海账号撤出
	//东莞仓
	$dongguancang = array('105142919','19568548','3580046','3580047','49858449','53316284','76065524','83077349');
	//嘉兴仓
	$jiaxingcang = array('149849256');
	//成都仓
	$chengducang = array('137059428');
	//武汉仓
	$wuhancang = array('137059427');
	//嘉兴水果外包仓
	$jxshuiguo = array('185963131','185963132','185963148');
	//上海水果外包仓
	$shshuiguo = array('185963133','185963134');
	//苏州水果外包仓
	$szshuiguo = array('185963137','185963138');
	//成都水果外包仓
	$cdshuiguo = array('185963139','185963140');
	//武汉水果外包仓
	$whshuiguo = array('185963141','185963142');
	//北京水果外包仓
	$bjshuiguo = array('185963127','185963128');
	//深圳水果外包仓
	$shzshuiguo = array('185963146','185963147');
	
	//ecco京东男鞋女鞋店，返回都为JDCOD
	if($distributor_id!='0' && in_array($distributor_id,$jd_distributor_ids) && in_array($shipping_id,array('146','149'))){
		return "JDCOD";
	}else if(in_array($facility_id, $beijingcang)){
		return "BJ";
	}else if(in_array($facility_id, $shanghaicang)){
		return "SH";
	}else if(in_array($facility_id, $dongguancang)){
		return "DG";
	}else if(in_array($facility_id, $jiaxingcang)){
		return "JX";
	}else if(in_array($facility_id, $chengducang)){
		return "CD";
	}else if(in_array($facility_id, $wuhancang)){
		return "WH";
	}else if(in_array($facility_id,$kbfengxian)){
		return "KBFX";
	}else if(in_array($facility_id, $jxshuiguo)){
		return "JXSG";
	}else if(in_array($facility_id, $shshuiguo)){
		return "SHSG";
	}else if(in_array($facility_id, $szshuiguo)){
		return "SZSG";
	}else if(in_array($facility_id, $cdshuiguo)){
		return "CDSG";
	}else if(in_array($facility_id, $whshuiguo)){
		return "WHSG";
	}else if(in_array($facility_id, $bjshuiguo)){
		return "BJSG";
	}else if(in_array($facility_id, $shzshuiguo)){
		return "SHZSG";
	}else{
		return "";
	}
}

/**
 * 不同仓库使用不同代号，后期需根据统计结果将对应关系移至数据库中
 */
function getBranchWithFacilityId($facility_id){  // 注意 JS:'19568549','194788297'   -- 针对 顺丰有效  写在 lib_sinri_DealPrint.php
	//北京TP仓
	$beijingcang = array('100170589','42741887','79256821','83077350','119603092');
	//电商服务上海仓+上海精品仓+电商服务嘉善仓
	$shanghaicang = array('176053000','137059424','120801050','12768420', '19568549', '22143846', '22143847','24196974', 
		'3633071', '69897656', '81569822', '81569823', '83972713', '83972714',
		'119603093','119603091','137059426','149849259','194788297');
	//康贝奉贤仓
	$kbfengxian = array('119603094');	
	//东莞仓
	$dongguancang = array('105142919','19568548','3580046','3580047','49858449','53316284','76065524','83077349');
	//嘉兴仓
	$jiaxingcang = array('149849256');
	//成都仓
	$chengducang = array('137059428');
	//武汉仓
	$wuhancang = array('137059427');
	//嘉兴水果外包仓
	$jxshuiguo = array('185963131','185963132','185963148');
	//上海水果外包仓
	$shshuiguo = array('185963133','185963134');
	//苏州水果外包仓
	$szshuiguo = array('185963137','185963138');
	//成都水果外包仓
	$cdshuiguo = array('185963139','185963140');
	//武汉水果外包仓
	$whshuiguo = array('185963141','185963142');
	//北京水果外包仓
	$bjshuiguo = array('185963127','185963128');
	//深圳水果外包仓
	$shzshuiguo = array('185963146','185963147');
	//水果深圳仓
	$sgsz = array('253372943');
	//万霖北京仓
	$wanlinbeijingcang = array('253372945');
	//华庆武汉仓
	$huaqingwuhancang = array('253372944');
	
	
	if(in_array($facility_id, $beijingcang)){
		return "BJ";
	}else if(in_array($facility_id, $shanghaicang)){
		return "SH";
	}else if(in_array($facility_id, $dongguancang)){
		return "DG";
	}else if(in_array($facility_id, $jiaxingcang)){
		return "JX";
	}else if(in_array($facility_id, $chengducang)){
		return "CD";
	}else if(in_array($facility_id, $wuhancang)){
		return "WH";
	}else if(in_array($facility_id,$kbfengxian)){
		return "KBFX";
	}else if(in_array($facility_id, $jxshuiguo)){
		return "JXSG";
	}else if(in_array($facility_id, $shshuiguo)){
		return "SHSG";
	}else if(in_array($facility_id, $szshuiguo)){
		return "SZSG";
	}else if(in_array($facility_id, $cdshuiguo)){
		return "CDSG";
	}else if(in_array($facility_id, $whshuiguo)){
		return "WHSG";
	}else if(in_array($facility_id, $bjshuiguo)){
		return "BJSG";
	}else if(in_array($facility_id, $shzshuiguo)){
		return "SHZSG";
	}else if(in_array($facility_id,$sgsz)){
		return "SGSZ";
	}else if(in_array($facility_id, $wanlinbeijingcang)){
		return "WLBJC";
	}else if(in_array($facility_id,$huaqingwuhancang)){
		return "HQWHC";
	}else{
		return "";
	}
}




function getSiteWithShipmentId($shipmentId){
	global $db;
	$sql="SELECT
		o.facility_id
	FROM
		romeo.order_shipment os
	LEFT JOIN ecshop.ecs_order_info o ON os.ORDER_ID = o.order_id
	WHERE
		os.SHIPMENT_ID = '{$shipmentId}'
	LIMIT 1";
	$facility_id=$db->getOne($sql);
	$site=getLocalBranchWithFacilityId($facility_id);
	return  $site;
}

/**
 * 单独获取面单号 圆通热敏 (以order_id.Shipment_id作为快递唯一识别符)
 */
function get_yto_thermal_mailno($order_id,$shipment_id,$branch){
	global $db;
	require_once (ROOT_PATH . "admin/PartyXTelephone.php");  //后期需整改
	$record = array();
	$record['sender_phone'] = sinri_get_telephone_for_order_to_print($order_id);
	$record['sender_mobile'] = $record['sender_phone'];
	$record['sender_postcode'] = '';
	
	$sql = "select order_id,order_sn,consignee,if(zipcode is null or zipCode='',100000,zipcode) code,
		IF(mobile is null or mobile='',tel,mobile) as t_mobile,r1.region_name province_name, 
		r2.region_name city_name,r3.region_name district_name,address from ecshop.ecs_order_info oi 
		left join ecshop.ecs_region r1 on r1.region_id = oi.province
		left join ecshop.ecs_region r2 on r2.region_id = oi.city
		left join ecshop.ecs_region r3 on r3.region_id = oi.district
		where order_id = '{$order_id}' and order_status = 1 ";
	$receive = $db->getRow($sql);
	$orders = $db->getCol("select order_id from romeo.order_shipment where shipment_id = '{$shipment_id}' ");
	$str_order_id = implode(",",$orders);
	$sql = "select CONCAT_WS('_',goods_id,style_id) goods_style,goods_name,sum(goods_number) num from ecshop.ecs_order_goods where order_id  in ({$str_order_id}) group by goods_id,style_id ";
	$goods = $db->getAll($sql);
	$shipmentCount = $db->getOne("select count(DISTINCT s1.SHIPMENT_ID) from romeo.order_shipment s1 
		inner join romeo.order_shipment s2 on s2.order_id = s1.order_id
		inner join romeo.shipment ss1 on ss1.SHIPMENT_ID = s1.SHIPMENT_ID and ss1.tracking_number is not null
		where s2.shipment_id = '{$shipment_id}' ");
	$selectfrom = $shipment_id.$shipmentCount;
	$return = shipOrder_yto($branch,$selectfrom,$record,$receive,$goods); //lib_yto_express.php
	if($return['flag'] == false){
		Qlog::log("order_id：".$order_id.",圆通快递热敏面单不能获取原因：".$return['mes']."\n");
		if(strstr($return['mes'],'have enough waybills')){
			return 0;
		}else{
			var_dump($return['mes']);
			return -1;
		}
	}else{
		$tracking_number = $return['tracking_number'];
		$bigPen = $return['bigPen'];
		if(empty($tracking_number) ){
			Qlog::log("order_id：".$order_id.",圆通快递热敏面单不能获取原因：接口反馈信息异常！flag:true;tracking_number:".$tracking_number.";bigPen:".$bigPen."\n");
			return -1;
		}
		$sql = "select count(*) from ecshop.thermal_express_mailnos where tracking_number = '{$tracking_number}' and shipping_id = 85 ";
		$exists = $db->getOne($sql);
		if($exists==0){
			$sql = "insert into ecshop.thermal_express_mailnos(tracking_number,status,shipping_id,branch,create_time,update_time) values('{$tracking_number}','R',85,'{$branch}',now(),now()) ";
			$db->query($sql);
		}
		if(!empty($bigPen)){
			$sql = "select attr_value from ecshop.order_attribute where order_id = '{$order_id}' and attr_name = 'ytoBigPen' and attr_value!='' limit 1 ";
			$exists =$db->getOne($sql);
			if(empty($exists)){
				$sql = "insert into ecshop.order_attribute(order_id,attr_name,attr_value) values('{$order_id}','ytoBigPen','{$bigPen}') ";
				$db->query($sql);
			}
		}
		
		return $tracking_number;
	}
}

/**
 * 单独获取面单号 韵达热敏 (以order_id.Shipment_id作为快递唯一识别符)
 */
function get_yunda_thermal_mailno_by_order($order_id,$shipmentId,$branch){
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
	require_once (ROOT_PATH . "admin/PartyXTelephone.php");
	$sender = array();
	$sender['sendTel'] = sinri_get_telephone_for_order_to_print($order_id);
	
	global $db;
	$shipmentCount = $db->getOne("select count(DISTINCT s1.SHIPMENT_ID) from romeo.order_shipment s1 
		inner join romeo.order_shipment s2 on s2.order_id = s1.order_id 
		inner join romeo.shipment ss1 on ss1.SHIPMENT_ID = s1.SHIPMENT_ID and ss1.tracking_number is not null
		where s2.shipment_id = '{$shipmentId}' ");
	$selectfrom = $shipmentId.$shipmentCount;
	$sql = "select * from ecshop.ecs_order_yunda_mailno_apply where shipment_id = '{$selectfrom}' ";
	$exist = $db->getRow($sql);
	if(!empty($exist)){
		return $exist['tracking_number'];
	}
	$sql = "select oi.shipping_id,oi.facility_id, oi.order_id,oi.order_sn,
			  oi.consignee,r1.region_name d_province,r2.region_name d_city,r3.region_name d_district,
			 oi.address,oi.zipcode,oi.mobile,oi.tel
			 from ecshop.ecs_order_info oi
			inner join romeo.order_shipment os on CAST(os.order_id AS unsigned) = oi.order_id
			left join ecshop.ecs_region r1 on r1.region_id = oi.province
			left join ecshop.ecs_region r2 on r2.region_id = oi.city
			left join ecshop.ecs_region r3 on r3.region_id = oi.district
			where  oi.order_status=1 and os.shipment_id = '{$shipmentId}'
			limit 1 ";
	$receiver = $db->getRow($sql);
	$orders = $db->getCol("select order_id from romeo.order_shipment where shipment_id = '{$shipmentId}' ");
	$str_order_id = implode(",",$orders);
	$goods_info = $db->getAll("select goods_name,sum(goods_number) num from ecshop.ecs_order_goods " .
			"where order_id in ({$str_order_id}) group by goods_id,style_id ");
	if($branch=='BQ'){
		$return = yd_bq_sendOrder($branch,$shipmentId,$shipmentCount,$sender,$receiver,$goods_info);// lib_yunda_express.php
	}else{
		$return = yd_qt_sendOrder($branch,$shipmentId,$shipmentCount,$sender,$receiver,$goods_info);
	}
	
	if($return['flag'] == false){
		Qlog::log("发货单：".$shipmentId.",韵达快递热敏面单不能获取原因：".$return['mes']."\n");
		if(strstr($return['mes'],'面单不足')){
			return 0;
		}else{
			var_dump($return['mes']);
			return -1;
		}
	}else{
		$tracking_number = $return['tracking_number'];
		$sql = "select count(*) from ecshop.thermal_express_mailnos where tracking_number = '{$tracking_number}' and shipping_id = 100 ";
		$exists = $db->getOne($sql);
		if($exists==0){
			$sql = "insert into ecshop.thermal_express_mailnos(tracking_number,status,shipping_id,branch,create_time,update_time)  values('{$tracking_number}','R',100,'{$branch}',now(),now()) ";
			$db->query($sql);
		}
		return $tracking_number;
	}
}

/**
 * 单独获取面单号 中通热敏 (以order_id.Shipment_id作为快递唯一识别符)
 */
function get_zto_thermal_mailno($order_id,$shipment_id,$branch){
	global $db;
	require_once (ROOT_PATH . "admin/PartyXTelephone.php");   
	$record = array();
	$record['sender_phone'] = sinri_get_telephone_for_order_to_print($order_id);
	$record['sender_mobile'] = $record['sender_phone'];
	$record['sender_postcode'] = '';
	
	$sql = "select order_id,order_sn,consignee,if(zipcode is null or zipCode='',100000,zipcode) code,
		mobile,tel,r1.region_name province_name, 
		r2.region_name city_name,r3.region_name district_name,address from ecshop.ecs_order_info oi 
		left join ecshop.ecs_region r1 on r1.region_id = oi.province
		left join ecshop.ecs_region r2 on r2.region_id = oi.city
		left join ecshop.ecs_region r3 on r3.region_id = oi.district
		where order_id = '{$order_id}' and order_status = 1 ";
	$receive = $db->getRow($sql);
	$shipmentCount = $db->getOne("select count(DISTINCT s1.SHIPMENT_ID) from romeo.order_shipment s1 
		inner join romeo.order_shipment s2 on s2.order_id = s1.order_id
		inner join romeo.shipment ss1 on ss1.SHIPMENT_ID = s1.SHIPMENT_ID and ss1.tracking_number is not null
		where s2.shipment_id = '{$shipment_id}' ");
	$selectfrom = $shipment_id.$shipmentCount;
	if (!preg_match("/^[0-9]+(\-|_)?[0-9]$/", $receive['tel'])) {
		$receive['tel'] = '';
	}
	if (!preg_match("/^[0-9]{11}$/", trim($receive['mobile']))) {
		$receive['mobile'] = '';
	}
	
	$return = shipOrder_zto($branch,$selectfrom,$record,$receive); //lib_zto_express.php
	if($return['flag'] == false){
		Qlog::log("order_id：".$order_id.",中通快递热敏面单不能获取原因：".$return['mes']."\n");
		if(strstr($return['mes'],'可用单号不足')){
			return 0;
		}else{
			return -1;
		}
	}else{
		$tracking_number = $return['tracking_number'];
		$bigPen = $return['bigPen'];
		if(empty($tracking_number) ){
			Qlog::log("order_id：".$order_id.",中通快递热敏面单不能获取原因：接口反馈信息异常！flag:true;tracking_number:".$tracking_number.";bigPen:".$bigPen."\n");
			return -1;
		}
		$sql = "select count(*) from ecshop.thermal_express_mailnos where tracking_number = '{$tracking_number}' and shipping_id = 115 ";
		$exists = $db->getOne($sql);
		if($exists==0){
			$sql = "insert into ecshop.thermal_express_mailnos(tracking_number,status,shipping_id,branch,create_time,update_time) values('{$tracking_number}','R',115,'{$branch}',now(),now()) ";
			$db->query($sql);
		}
		if(!empty($bigPen)){
			$sql = "select attr_value from ecshop.order_attribute where order_id = '{$order_id}' and attr_name = 'ztoBigPen' and attr_value!='' limit 1 ";
			$exists =$db->getOne($sql);
			if(empty($exists)){
				$sql = "insert into ecshop.order_attribute(order_id,attr_name,attr_value) values('{$order_id}','ztoBigPen','{$bigPen}') ";
				$db->query($sql);
			}
		}
		
		return $tracking_number;
	}
}

/**
 * 单独获取面单号 汇通热敏 (以order_id.Shipment_id作为快递唯一识别符)
 */
function get_ht_thermal_mailno($order_id,$shipment_id,$branch){
	global $db;
	require_once (ROOT_PATH . "admin/PartyXTelephone.php");   
	$record = array();
	$record['sender_phone'] = sinri_get_telephone_for_order_to_print($order_id);
	$record['sender_mobile'] = $record['sender_phone'];
	$record['sender_postcode'] = '';
	
	$sql = "select order_id,order_sn,consignee,if(zipcode is null or zipCode='',100000,zipcode) code,
		mobile,tel,r1.region_name province_name, 
		r2.region_name city_name,r3.region_name district_name,address from ecshop.ecs_order_info oi 
		left join ecshop.ecs_region r1 on r1.region_id = oi.province
		left join ecshop.ecs_region r2 on r2.region_id = oi.city
		left join ecshop.ecs_region r3 on r3.region_id = oi.district
		where order_id = '{$order_id}' and order_status = 1 ";
	$receive = $db->getRow($sql);
	$shipmentCount = $db->getOne("select count(DISTINCT s1.SHIPMENT_ID) from romeo.order_shipment s1 
		inner join romeo.order_shipment s2 on s2.order_id = s1.order_id
		inner join romeo.shipment ss1 on ss1.SHIPMENT_ID = s1.SHIPMENT_ID and ss1.tracking_number is not null
		where s2.shipment_id = '{$shipment_id}' ");
	$selectfrom = $shipment_id.$shipmentCount;
	if (!preg_match("/^[0-9]+(\-|_)?[0-9]$/", $receive['tel'])) {
		$receive['tel'] = '';
	}
	if (!preg_match("/^[0-9]{11}$/", trim($receive['mobile']))) {
		$receive['mobile'] = '';
	}
	
	$return = shipOrder_ht($branch,$selectfrom,$record,$receive); //lib_ht_express.php
	if($return['flag'] == false){
		Qlog::log("order_id：".$order_id.",汇通快递热敏面单不能获取原因：".$return['mes']."\n");
		if(strstr($return['mes'],'没有可用单号')){
			return 0;
		}else{
			return -1;
		}
	}else{
		$tracking_number = $return['tracking_number'];
		$bigPen = $return['bigPen'];
		if(empty($tracking_number) ){
			Qlog::log("order_id：".$order_id.",汇通快递热敏面单不能获取原因：接口反馈信息异常！flag:true;tracking_number:".$tracking_number.";bigPen:".$bigPen."\n");
			return -1;
		}
		$sql = "select count(*) from ecshop.thermal_express_mailnos where tracking_number = '{$tracking_number}' and shipping_id = 99 ";
		$exists = $db->getOne($sql);
		if($exists==0){
			$sql = "insert into ecshop.thermal_express_mailnos(tracking_number,status,shipping_id,branch,create_time,update_time) values('{$tracking_number}','R',99,'{$branch}',now(),now()) ";
			$db->query($sql);
		}
		if(!empty($bigPen)){
			$sql = "select attr_value from ecshop.order_attribute where order_id = '{$order_id}' and attr_name = 'htBigPen' and attr_value!='' limit 1 ";
			$exists =$db->getOne($sql);
			if(empty($exists)){
				$sql = "insert into ecshop.order_attribute(order_id,attr_name,attr_value) values('{$order_id}','htBigPen','{$bigPen}') ";
				$db->query($sql);
			}
		}
		return $tracking_number;
	}
}

/**
顺丰热敏
**/
function get_sf_thermal_mailno_for_shipment($shipmentId,$branch){
	global $db;
	$sql="SELECT 
		    s.shipment_id,
		    oi.order_id,
		    oi.facility_id,
		    oi.shipping_id,
		    oi.distributor_id,
		    oi.consignee,
		    oi.province,
		    er_p.region_name province_name,
		    oi.city,
		    er_c.region_name city_name,
		    oi.district,
		    er_d.region_name county_name,
		    oi.address,
		    oi.tel,
		    oi.mobile,
		    IF(es.shipping_code = 'ems_cod', 'cod', '') is_cod, 
		    (select count(DISTINCT s1.SHIPMENT_ID) from romeo.order_shipment s1 
				inner join romeo.order_shipment s2 on s2.order_id = s1.order_id
				inner join romeo.shipment ss1 on ss1.SHIPMENT_ID = s1.SHIPMENT_ID and ss1.tracking_number is not null
				where s2.shipment_id = '{$shipmentId}'
		    ) as sc
		FROM
		    romeo.shipment s
		        INNER JOIN
		    ecshop.ecs_order_info oi ON s.primary_order_id = oi.order_id
		        LEFT JOIN
		    ecshop.ecs_region er_p ON er_p.region_id = oi.province
		        LEFT JOIN
		    ecshop.ecs_region er_c ON er_c.region_id = oi.city
		        LEFT JOIN
		    ecshop.ecs_region er_d ON er_d.region_id = oi.district
		    	LEFT JOIN
    		ecshop.ecs_shipping es ON s.shipment_type_id = es.shipping_id
		WHERE
		    s.shipment_id = '{$shipmentId}'";
	$order=$db->getRow($sql);

	require_once (__DIR__."/../PartyXTelephone.php");
	$order['goods_type']=sinri_get_goods_type_for_party_to_print($order['party_id'],$order['distributor_id']);

	$selectfrom = $order['shipment_id'].$order['sc'];

	$sf_order=array(
		'order_id' => $selectfrom,
		'j_company'=>"LEQEE",
		'j_contact'=>"LEQEE",
		'j_tel'=>"", 
		'j_mobile'=>"15901915477", 
		'j_province'=>"上海",
		'j_city'=>"上海", 
		'j_county'=>"青浦区", 
		'j_address'=>"上海市青浦区青赵公路5009号北面车间", 
		'd_company'=>"LEQEE CUSTOMER",
		'd_contact'=>$order['consignee'], 
		'd_tel'=>$order['tel'],
		'd_mobile'=>$order['mobile'], 
		'd_province'=>$order['province_name'],
		'd_city'=>$order['city_name'],
		'd_county'=>$order['county_name'],
		'd_address'=>$order['address'],
		// 'express_type'=>"1",//Default, Standard SF
		'pay_method'=>"1",//付款方式: 1:寄方付 2:收方付 3:第三方付 
		// 'custid'=>"7551878519",//顺丰月结卡号
		'parcel_quantity'=>"1",//包裹数
		// 'cargo_total_weight'=>"2.35",
		// 'sendstarttime'=>'',//date("Y-m-d H:i:s"),
		// 'order_source'=>"",
		'remark'=>"",
		"cargo_name"=>$order['goods_type'],
	);

	if($branch=='SH'){
	//上海精品仓
		$sf_order['j_company']="LEQEE";
		$sf_order['j_contact']="孙亚威";
		$sf_order['j_tel']='';
		$sf_order['j_mobile']='15901915477';
		$sf_order['j_province']="上海";
		$sf_order['j_city']="上海";
		$sf_order['j_county']="青浦区"; 
		$sf_order['j_address']="上海市青浦区新丹路359号5栋4楼"; 
		$sf_order['d_company']="LEQEE CUSTOMER";
		$sf_order['pay_method']='3';//3:第三方付 采用上海水果仓结账
	}elseif($branch=='SHSG'){
	//水果上海仓
		$sf_order['j_company']="LEQEE";
		$sf_order['j_contact']="谷迟琛";
		$sf_order['j_tel']='';
		$sf_order['j_mobile']='13918563708';
		$sf_order['j_province']="上海";
		$sf_order['j_city']="上海";
		$sf_order['j_county']="青浦区"; 
		$sf_order['j_address']="上海市青浦区青赵公路5009号北面车间"; 
		$sf_order['d_company']="LEQEE CUSTOMER";
		$sf_order['pay_method']='1';//1:寄方付 采用上海水果仓结账
	}elseif($branch=='BJSG'){
	//水果北京仓：月结账号——东莞仓   第三方地区： E769FF      仓库地址码：010BJJ   
		$sf_order['j_company']="LEQEE";
		$sf_order['j_contact']="李博学";
		$sf_order['j_tel']='';
		$sf_order['j_mobile']='13311158377';
		$sf_order['j_province']="北京";
		$sf_order['j_city']="北京";
		$sf_order['j_county']="大兴区"; 
		$sf_order['j_address']="北京市大兴区后辛庄村立阳路临12号"; 
		$sf_order['d_company']="LEQEE CUSTOMER";
		$sf_order['pay_method']='3';//1:第三方付 采用东莞仓结账
	}elseif($branch=='DG'){
		//乐其东莞仓
		$sf_order['j_company']="LEQEE";
		$sf_order['j_contact']="黄浩";
		$sf_order['j_tel']='';
		$sf_order['j_mobile']='13711843574';
		$sf_order['j_province']="广东";
		$sf_order['j_city']="东莞市";
		$sf_order['j_county']="长安镇"; 
		$sf_order['j_address']="广东省东莞市长安镇步步高大道126号"; 
		$sf_order['d_company']="LEQEE CUSTOMER";
		$sf_order['pay_method']='1';//1:寄方付 采用东莞乐其结账
	}elseif($branch=='SZSG'){
		//双11苏州水果仓-正常（外包其实也在里面）
		$sf_order['j_company']="LEQEE";
		$sf_order['j_contact']="高昌勇";
		$sf_order['j_tel']='';
		$sf_order['j_mobile']='17051198262';
		$sf_order['j_province']="江苏";
		$sf_order['j_city']="苏州";
		$sf_order['j_county']="高新区"; 
		$sf_order['j_address']="江苏省苏州市高新区浒墅关工业园青花路128号安博物流园4号库"; 
		$sf_order['d_company']="LEQEE CUSTOMER";
		$sf_order['pay_method']='3';//3:第三方付 采用上海水果仓结账
	}elseif($branch=='JS'){
		//嘉善仓(电商服务嘉善仓，电商服务上海仓)
		$sf_order['j_company']="LEQEE";
		$sf_order['j_contact']="售后部";
		$sf_order['j_tel']='';
		$sf_order['j_mobile']='0573-89109773';  
		$sf_order['j_province']="浙江";
		$sf_order['j_city']="嘉兴";
		$sf_order['j_county']="嘉善县"; 
		$sf_order['j_address']="浙江省嘉善县惠民街道松海路88号晋亿物流集团2号仓库"; 
		$sf_order['d_company']="LEQEE CUSTOMER";
		$sf_order['pay_method']='3';//3:第三方付  采用东莞乐其结账
	}elseif($branch=='SGSZ'){
		//水果深圳仓
		$sf_order['j_company']="LEQEE";
		$sf_order['j_contact']="张玉龙";
		$sf_order['j_tel']='';
		$sf_order['j_mobile']='15936079884';  
		$sf_order['j_province']="广东";
		$sf_order['j_city']="深圳";
		$sf_order['j_county']="光明新区"; 
		$sf_order['j_address']="广东省深圳市光明新区塘家大道6号"; 
		$sf_order['d_company']="LEQEE CUSTOMER";
		$sf_order['pay_method']='3';//3:第三方付  采用东莞乐其结账
	}

// 所有顺丰热敏发货单保价；
// 保价声明金额为订单价格。
	// $sql="SELECT sum(oi.order_amount) FROM romeo.order_shipment os 
	// INNER JOIN ecshop.ecs_order_info oi ON cast(os.order_id as unsigned)=oi.order_id
	// WHERE os.shipment_id='{$shipmentId}'";
	// $insure=$db->getOne($sql);
	// $insure=0;//保价，0为不保价
	$insure=SFArataInsure::getInsuranceForShipment($shipmentId);

	$SFAA=new SFArataAgent($branch);
	// $new_order_result=$sf->createOrder($sf_order,$order['is_cod'],$insure);
	$res=$SFAA->OrderService($sf_order,$order['is_cod'],$insure);
	if($res!==false){
		if(!empty($res['mailno'])){
			$tracking_number = $res['mailno'];
			$sql = "select count(*) from ecshop.thermal_express_mailnos where tracking_number = '{$tracking_number}' and shipping_id = {$order['shipping_id']} ";
			$exists = $db->getOne($sql);
			if($exists==0){
				$sql = "insert into ecshop.thermal_express_mailnos(tracking_number,status,shipping_id,branch,create_time,update_time) values('{$tracking_number}','R',{$order['shipping_id']},'{$branch}',now(),now()) ";
				$db->query($sql);
			}
			return $tracking_number;
		}
	}
	return -1;
}

/** 
 * 中通，申通，汇通  绑定运单号
 */
function get_thermal_mailno($shipping_id,$branch){
	global $db;
	$sql = "SELECT tracking_number from ecshop.thermal_express_mailnos 
			where shipping_id = '{$shipping_id}' and status = 'N'  and branch='{$branch}'  limit 1";
	$tracking_number = $db->getOne($sql);
	//尝试抢占此面单号 status->R
	$sql_reserve="UPDATE ecshop.thermal_express_mailnos SET status='R',update_time=now()
	 WHERE status = 'N' 
	 AND shipping_id = '{$shipping_id}' 
	 AND tracking_number='{$tracking_number}'";
	$affected_rows=$db->exec($sql_reserve);
	if($affected_rows==1){
		return $tracking_number;
	}elseif(empty($tracking_number)){
		return 0;
	}else{
		return -1;
	}
}

/** 
 * 京东COD,京东配送  绑定运单号
 */
function get_jd_bill_code($distributor_id,$order_id){
	global $db;
	$sqla = "select attr_value from ecshop.order_attribute where order_id = $order_id and attr_name='JDsendCode' order by attribute_id desc ";
	$isJdSenda = $db->getOne($sqla);
	if(empty($isJdSenda) || ($isJdSenda!='100' && $isJdSenda!='200')){
		//1. 调用 是否可以京配接口 获取信息 ，错误情况：连接失败（京东接口异常，稍后再试）；不能京配（可操作）
		include_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
	
		$erpsync_http_auth_array['trace'] = true;
		if(defined('ERPSYNC_HTTP_USER') && ERPSYNC_HTTP_USER) $erpsync_http_auth_array['login'] = ERPSYNC_HTTP_USER;
		if(defined('ERPSYNC_HTTP_PASS') && ERPSYNC_HTTP_PASS) $erpsync_http_auth_array['password'] = ERPSYNC_HTTP_PASS;
		
		$sql="select tc.application_key,ap.customer_code,ap.taobao_api_params_id from ecshop.taobao_shop_conf tc
			INNER JOIN ecshop.taobao_api_params ap on tc.taobao_api_params_id = ap.taobao_api_params_id
			where tc.status='OK' and tc.shop_type = '360buy' and tc.distributor_id = {$distributor_id} ";
		$taobaoShop = $db->getRow($sql);
		if(empty($taobaoShop)){
			return 2; // 没有找到京东店铺信息
		}
		
		$sql = "select concat(r1.region_name,r2.region_name,ifnull(r3.region_name,''),address) as orderAddress 
				 from ecshop.ecs_order_info oi 
				 inner join ecshop.ecs_region r1 on r1.region_id = oi.province 
				 inner join ecshop.ecs_region r2 on r2.region_id = oi.city 
				 left join ecshop.ecs_region r3 on r3.region_id = oi.district 
				 where oi.order_id = {$order_id} limit 1 ";
		$orderAddress = $db->getOne($sql);
		 
		try{
			$soapclient = new SoapClient(ERPSYNC_WEBSERVICE_URL."SyncJdService?wsdl",$erpsync_http_auth_array);
			$request=array("applicationKey"=>$taobaoShop['application_key'],"customerCode"=>$taobaoShop['customer_code'],"orderId"=>$order_id,"orderAddress"=>$orderAddress);
			$soapclient->SyncIsJdSend($request);
		}catch(Exception $e){
			Qlog::log("访问京东“是否京配”接口中断，异常信息为：".$e);
			return 3; // 连接中断
		}
		
		$sql = "select attr_value from ecshop.order_attribute where order_id = $order_id and attr_name='JDsendCode' ";
		$isJdSend = $db->getOne($sql);
		if($isJdSend=='200'){
			return 4; // 不能京配
		}else if($isJdSend=='150'){
			return 5; //稍后再试
		}else if($isJdSend!='100'){
			return 6;
		}
	}elseif($isJdSenda=='200'){
		return 4; // 不能京配
	}
	//2. 
	$sql = "SELECT tracking_number from ecshop.jd_bill_code 
			where distributor_id = '{$distributor_id}' and status = 'N'  and branch='JDCOD'  limit 1";
	$tracking_number = $db->getOne($sql);

	//尝试抢占此面单号 status->R
	$sql_reserve="UPDATE ecshop.jd_bill_code SET status='R'
	 WHERE status = 'N'  and branch='JDCOD'
	 AND distributor_id = '{$distributor_id}' 
	 AND tracking_number='{$tracking_number}'";
	$affected_rows=$db->exec($sql_reserve);
		
	if($affected_rows==1){
		return $tracking_number;
	}elseif(empty($tracking_number)){
		return 0;
	}else{
		return -1;
	}
}

function get_thermal_mailnos($shipping_id,$branch,$number){
	global $db;

	$sql = "SELECT tracking_number from ecshop.thermal_express_mailnos 
			where shipping_id = '{$shipping_id}' and status = 'N'  and branch='{$branch}'  limit {$number}";
	$tracking_numbers = $db->getAll($sql);

	if(count($tracking_numbers) != $number){
		return false;
	}else{
		$tn=array();
		foreach ($tracking_numbers as $tnline) {
			$tn[]="'".$tnline['tracking_number']."'";
		}
		$tn_range=implode(',', $tn);
		$sql_reserve="UPDATE ecshop.thermal_express_mailnos SET status='R',update_time=now()
			WHERE status = 'N' 
	 		AND shipping_id = '{$shipping_id}' 
	 		AND tracking_number in ({$tn_range})";
	 	//print_r($sql_reserve);
		$affected_rows=$db->exec($sql_reserve);
		if($affected_rows==$number){
			return $tracking_numbers;
		}else{
			return array();
		}
	}
}

/*
 * 面单绑定快递单
 * */
function bind_arata_shipment_mailnos($shipping_id,$tracking_numbers){
	global $db;
	$sql ="update ecshop.thermal_express_mailnos set status = 'Y',update_time=now() 
		   where tracking_number in ('" . implode("','", $tracking_numbers) . "') and shipping_id = '{$shipping_id}' ";
	$db->exec($sql);
}

function bind_arata_shipment_mailno($shipping_id,$tracking_number){
	update_thermal_mailno_status($shipping_id,$tracking_number,'Y');
}
/*
 * 异常回传后，更新面单状态
 * */
function error_arata_shipment_mailno($shipping_id,$tracking_number){
	update_thermal_mailno_status($shipping_id,$tracking_number,'E');
}

/*
 * 回传成功后，更新面单状态
 * */
function finish_arata_shipment_mailno($shipping_id,$tracking_number){
	update_thermal_mailno_status($shipping_id,$tracking_number,'F');
}
/*
 * 
 * */
function update_thermal_mailno_status($shipping_id,$tracking_number,$status){
	global $db;
	$sql ="update ecshop.thermal_express_mailnos set status = '{$status}',update_time=now()
	   where tracking_number = '{$tracking_number}'  and shipping_id = '{$shipping_id}' ";
	$db->query($sql);
}

/**
 * 京东COD，京东配送  均只需从R->Y,后期不存在F状态（发货同步时完成运单推送）
 */
function bind_jd_bill_code($distributor_id,$tracking_number){
	global $db;
	$sql = "update ecshop.jd_bill_code set status = 'Y' 
	   where tracking_number = '{$tracking_number}'  and distributor_id = '{$distributor_id}'";
	$db->query($sql);
}
?>