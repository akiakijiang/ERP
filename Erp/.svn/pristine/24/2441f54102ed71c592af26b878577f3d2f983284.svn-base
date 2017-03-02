<?php
/**
 * 根据快递单号查询绑定码托信息
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

$pallet_party_list = array('65644','65650','65581','65617','65652','65653','65569','65622','65645','65661','65668','65646','65539','65670','65619','65628','65639');

// 快递单号
$tracking_number = 
    isset($_REQUEST['tracking_number']) && trim($_REQUEST['tracking_number']) 
    ? $_REQUEST['tracking_number'] 
    : false ;
global $db;    
$smarty->assign('tracking_number', $tracking_number);

if ($tracking_number) {
	$sql = "SELECT s.SHIPMENT_ID,s.SHIPPING_LEQEE_WEIGHT,s.STATUS,oi.order_sn,oi.party_id,psm.pallet_no,p.ship_status
		from romeo.shipment s 
		INNER JOIN ecshop.ecs_order_info oi on oi.order_id = CAST(s.primary_order_id as UNSIGNED)
		LEFT JOIN romeo.pallet_shipment_mapping psm on psm.shipment_id = s.SHIPMENT_ID and psm.bind_status = 'BINDED'
		LEFT JOIN romeo.pallet p on p.pallet_no = psm.pallet_no
		where s.TRACKING_NUMBER = '{$tracking_number}' limit 1 ";
	$trackingInfo = $db->getRow($sql);
	if(empty($trackingInfo)){
		$message = "此快递单号在系统中不存在，请核实！";
	}else if(!in_array($trackingInfo['party_id'],$pallet_party_list)){
		$message = "该快递单号所属业务不需要通过码托发货！";
	}else if($trackingInfo['SHIPPING_LEQEE_WEIGHT']<=0 || empty($trackingInfo['SHIPPING_LEQEE_WEIGHT'])){
		$message = "此快递单号还未称重！";
	}else if(empty($trackingInfo['pallet_no'])){
		$message = "此快递单号已称重，还未绑定码托！";
	}else if($trackingInfo['ship_status']=='SHIPPED' || $trackingInfo['STATUS']=='SHIPMENT_SHIPPED'){
		$message = "此快递单系统已发货,码托".$trackingInfo['pallet_no'];
	} 
	$smarty->assign('message', $message);
}

$smarty->display('oukooext/search_pallet_bind.htm');

?>