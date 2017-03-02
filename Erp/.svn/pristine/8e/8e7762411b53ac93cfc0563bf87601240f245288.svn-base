<?php
/**
 * 查询绑定码托信息
 */
define('IN_ECS', true);
require_once('includes/init.php');
//require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

global $db; 

$pallet_party_list = array('65644','65650','65581','65617','65652','65653','65569','65622','65645','65661','65668','65646','65539','65670','65619','65628','65639');

$fSql = "SELECT DISTINCT physical_facility FROM romeo.facility WHERE IS_CLOSED = 'N'";
$physical_facility_list = $db->getCol($fSql);
$smarty->assign('physical_facility_list', $physical_facility_list);

$info['order_sn'] = isset($_REQUEST['order_sn']) && trim($_REQUEST['order_sn']) ? $_REQUEST['order_sn'] : '';
$info['tracking_number'] = isset($_REQUEST['tracking_number']) && trim($_REQUEST['tracking_number']) ? $_REQUEST['tracking_number'] : '';
$info['status'] = isset($_REQUEST['status']) && trim($_REQUEST['status']) ? $_REQUEST['status'] : 0;
$info['physical_facility'] = isset($_REQUEST['physical_facility']) && trim($_REQUEST['physical_facility']) ? $_REQUEST['physical_facility'] : '';

$act = isset($_REQUEST['act']) && trim($_REQUEST['act']) ? $_REQUEST['act'] : '';
if ($act == 'search') {
	$condition = get_condition($info);
	$sql = "SELECT pt.name, f.physical_facility, oi.order_sn, s.SHIPMENT_ID, s.TRACKING_NUMBER, psm.pallet_no,  
			if(p.ship_status = 'SHIPPED', '已交接', 
				if(psm.pallet_no is not null, '已码托,待交接', 
					if(s.SHIPPING_LEQEE_WEIGHT > 0, '已称重,待码托', 
						if(oi.shipping_status = 8, '已复核,待称重', '未复核')
					)
				)
			) as status, 
			if(p.ship_status = 'SHIPPED', shipped_time, 
				if(psm.pallet_no is not null, psm.bind_time, 
					if(s.SHIPPING_LEQEE_WEIGHT > 0, (select oa2.action_time from ecshop.ecs_order_action oa2 where oi.order_id = oa2.order_id and oa2.action_note like concat('快递包裹',s.TRACKING_NUMBER,'称重%') LIMIT 1), 
						if(oi.shipping_status = 8, (select oa.action_time from ecshop.ecs_order_action oa where oi.order_id = oa.order_id and oa.action_note = '复核成功' LIMIT 1), 
							(select max(oa1.action_time) from ecshop.ecs_order_action oa1 where oa1.order_id = oi.order_id LIMIT 1))
					)
				)
			) as action_time  
		from {$condition['f']} 
		INNER JOIN romeo.facility f on oi.facility_id = f.facility_id AND f.IS_CLOSED = 'N'
		INNER JOIN romeo.party pt on CONVERT(oi.party_id USING utf8) = pt.party_id AND pt.status = 'ok'
		LEFT JOIN romeo.pallet_shipment_mapping psm on psm.shipment_id = s.SHIPMENT_ID and psm.bind_status = 'BINDED' 
		LEFT JOIN romeo.pallet p on p.pallet_no = psm.pallet_no 
		where oi.order_status = 1 AND oi.pay_status = 2 {$condition['w']}
		";
		
//	print_r($sql);	
	$list = $db->getAll($sql);
	
	/*if(empty($trackingInfo)){
		$message = "此快递单号在系统中不存在，请核实！";
	}else if(!in_array($trackingInfo['party_id'],$pallet_party_list)){
		$message = "该快递单号所属业务不需要通过码托发货！";
	}else if($trackingInfo['SHIPPING_LEQEE_WEIGHT']<=0 || empty($trackingInfo['SHIPPING_LEQEE_WEIGHT'])){
		$message = "此快递单号还未称重！";
	}else if(empty($trackingInfo['pallet_no'])){
		$message = "此快递单号已称重，还未绑定码托！";
	}else if($trackingInfo['ship_status']=='SHIPPED' || $trackingInfo['STATUS']=='SHIPMENT_SHIPPED'){
		$message = "此快递单系统已发货,码托".$trackingInfo['pallet_no'];
	} */
	$smarty->assign('list', $list);
}

function get_condition($info){
	$condition['w'] = "";
	$condition['f'] = "	ecshop.ecs_order_info oi INNER JOIN romeo.shipment s on CONVERT(oi.order_id USING utf8) = s.primary_order_id";
	
	if($info['order_sn'] != ''){
		$condition['w'] .= " AND oi.order_sn = '{$info['order_sn']}'";
	}
	
	if($info['tracking_number'] != ''){
		$condition['w'] .= " AND s.TRACKING_NUMBER = '{$info['tracking_number']}'";
		$condition['f'] = "	romeo.shipment s INNER JOIN ecshop.ecs_order_info oi on cast(s.primary_order_id as unsigned) = oi.order_id";
	}
	
	if($info['status'] == 1){
		$condition['w'] .= " AND oi.shipping_status = 8 AND (s.SHIPPING_LEQEE_WEIGHT = 0 || s.SHIPPING_LEQEE_WEIGHT is null)";
	}else if($info['status'] == 2){
		$condition['w'] .= " AND oi.shipping_status = 8 AND s.SHIPPING_LEQEE_WEIGHT > 0 AND psm.pallet_no is null";
	}else if($info['status'] == 3){
		$condition['w'] .= " AND oi.shipping_status = 8 AND psm.pallet_no is not null AND p.ship_status != 'SHIPPED'";
	}
	
	if($info['physical_facility'] != ''){
		$condition['w'] .= " AND f.physical_facility = '{$info['physical_facility']}'";
	}
	
	return $condition;
	
}

$smarty->assign('order_sn', $info['order_sn']);
$smarty->assign('tracking_number', $info['tracking_number']);
$smarty->assign('status', $info['status']);
$smarty->assign('physical_facility', $info['physical_facility']);
$smarty->display('oukooext/search_pallet_bind_new.htm');

?>