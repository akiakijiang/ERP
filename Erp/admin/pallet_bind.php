<?php

/**
 * 码托与快递单绑定
 */
define('IN_ECS', true);
require_once ('includes/init.php');
require_once (ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

$act = isset ($_REQUEST['act']) ? trim($_REQUEST['act']) : false;
$exist = '0'; //初始化，0为不重复，1为重复
$json = new JSON;

switch ($act) {
	case "check_pallet_sn" :
		check_sn(check_pallet_sn);
		break;
	case "check_tracking_sn" :
		check_sn(check_tracking_sn);
		break;
 }

/**
 * 根据act执行不同的行为  jwli 2016.03.01
 */
function check_sn($act) {
	unset ($_REQUEST['act']);
	// $_POST的数据会传递给回调函数
	$result = call_user_func($act, isset ($_POST) ? $_POST : null);
	$json = new JSON;
	print $json->encode($result);
	exit;
}

/**
 * 检测码托的合法性  jwli 2016.03.01
 */

function check_pallet_sn($args) {

	if (!empty ($args)) extract($args);
	$pallet_sn = trim($pallet_sn);
	$result = check_pallet($pallet_sn);
	return $result;
}
/*
* 检测码托条码的合法性 jwli 2016.03.01
*/
function check_pallet($pallet_sn) {
	global $db;
	$sql = "select pallet_no,ship_status from romeo.pallet where pallet_no = '{$pallet_sn}' limit 1";
	$pallet_sql = $db->getRow($sql);
	if (empty ($pallet_sql)) {
		$result['error'] = "码托条码不存在！";
		$result['success'] = false;
	} else if ($pallet_sql["ship_status"] == "SHIPPED") {
		$result['error'] = "该码托已经发货！";
		$result['success'] = false;
	}
	if (empty ($result)) {
		$result['success'] = true;
		$sql = "select s.tracking_number,p.shipping_id,es.shipping_name,p.physical_facility 
			 	from romeo.pallet_shipment_mapping psm  
				INNER JOIN romeo.pallet p on p.pallet_no = psm.pallet_no
			 	inner join romeo.shipment s on s.shipment_id = psm.shipment_id
				inner JOIN ecshop.ecs_shipping es on es.shipping_id = p.shipping_id
			 	where psm.pallet_no = '{$pallet_sn}'  and psm.bind_status='BINDED'";
		$tn_shipping_arr = $db->getAll($sql);
		if(empty($tn_shipping_arr)){
			$result['num'] = 0;
		}else{
			$result['num'] = count($tn_shipping_arr);
			$result['ship'] = $tn_shipping_arr;
			$result['way'] = $tn_shipping_arr[0]['shipping_name'];
			$result['physical_facility'] = $tn_shipping_arr[0]['physical_facility'];
		}
	}
	return $result;
}

/**
 * 检测快递单号的合法性  jwli 2016.03.01
 */
function check_tracking_sn($args) {
	if (!empty ($args)) extract($args);
	$tracking_no = trim($tracking_no);
	$pallet_no = trim($pallet_no);
	$result = check_tracking($tracking_no, $pallet_no);
	return $result;
}

/*
 * 检测快递单号的合法性 jwli 2016.03.01
 */
function check_tracking($tracking_no, $pallet_no) {
	global $db;
	//扫描快递单号时再次检查码托是否存在 且 未发货
	$sql_1 = "select pallet_no,ship_status,shipping_id,physical_facility from romeo.pallet where pallet_no = '{$pallet_no}' limit 1";
	$pallet_info = $db->getRow($sql_1);
	if (empty ($pallet_info)) {
		$result['error'] = "码托条码不存在！";
		$result['success'] = false;
		$result['error_id'] = 1;
	} else if ($pallet_info["ship_status"] == "SHIPPED") {
		$result['error'] = "该码托已经发货！";
		$result['success'] = false;
		$result['error_id'] = 1;
	} else { 
		$sql = "select oi.shipping_status,oi.order_status,oi.order_id,s.status,s.SHIPPING_LEQEE_WEIGHT,s.shipment_id,oi.shipping_id,oi.shipping_name,f.physical_facility 
			from romeo.shipment s 
			inner join romeo.order_shipment os on os.shipment_id = s.shipment_id 
			inner join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as UNSIGNED)
			INNER JOIN romeo.facility f on f.facility_id = oi.facility_id 
			where s.tracking_number = '{$tracking_no}' ";
		$tn_order_info_arr = $db->getAll($sql);
		if(empty($tn_order_info_arr)){
			$result['error'] = "快递单号不存在！";
			$result['error_id'] = 2;
			$result['success'] = false;
		} else if($tn_order_info_arr[0]['SHIPPING_LEQEE_WEIGHT']<=0 || empty($tn_order_info_arr[0]['SHIPPING_LEQEE_WEIGHT'])){
			$result['error'] = "该快递单号还未称重，不能绑定码托！";
			$result['error_id'] = 2;
			$result['success'] = false;
		}else{ //快递单号存在，但是对应订单已经发货
			$error_2 = true;
			foreach($tn_order_info_arr as $tn_order_info){
				if($tn_order_info['shipping_status'] == 1 || $tn_order_info['order_status'] != 1 || $tn_order_info['status']=='SHIPMENT_SHIPPED'){
					$error_2 = false;
					break;
				}
			}
			if(!$error_2){
				$result['error'] = "绑定失败，该快递单号已发货，或对应的订单 “非”（已确认待发货）状态！";
				$result['error_id'] = 2;
				$result['success'] = false;
			}else{
				$sql = "select pallet_no,bind_status,unbind_user,s.status  
					 from romeo.pallet_shipment_mapping p  
					 inner join romeo.shipment s on p.shipment_id = s.shipment_id  
					 where s.tracking_number = '{$tracking_no}' 
					 order by bind_status,pallet_no";
				$bind_info_list = $db->getAll($sql);
				if(!empty($bind_info_list)){
					foreach($bind_info_list as $bind_info){
						if($bind_info['bind_status'] == 'BINDED'){
							$error_2 = false;
							$result['error'] = "快递单号" . $tracking_no . "已经与" . $bind_info["pallet_no"] . "绑定！";
							$result['error_id'] = 2;
							$result['success'] = false;
							break;
						}else if($bind_info['pallet_no']==strtoupper($pallet_no)){
							$error_2 = false;
							$result['error'] = "快递单号" . $tracking_no . "与" . $bind_info["pallet_no"] . "已经被" . $bind_info['unbind_user'] . "解绑！";
							$result['error_id'] = 2;
							$result['success'] = false;
							break;
						}
					}
				}
				
				if ($error_2) {
					$pallet_no = strtoupper($pallet_no);
					$sql = "select count(*) from romeo.pallet_shipment_mapping where pallet_no = '{$pallet_no}' and bind_status = 'BINDED' ";
					$bind_count = $db->getOne($sql);
					$tracking_info = $tn_order_info_arr[0];
					if ($bind_count == 0) { //快递单号存在，初次绑定
						$sql_1 = "insert into `romeo`.`pallet_shipment_mapping` (shipment_id,pallet_no,bind_status,bind_user,bind_time) " .
							"values ('{$tracking_info['shipment_id']}','{$pallet_no}','BINDED','{$_SESSION['admin_name']}',now())";
						$sql_2 = "update romeo.pallet set shipping_id = {$tracking_info['shipping_id']},physical_facility='{$tracking_info['physical_facility']}' where pallet_no = '{$pallet_no}'";
						$db->start_transaction();
						if($db->query($sql_1) && $db->query($sql_2)){
							$db->commit();
							$result['success'] = true;
							$result['num'] = 1;
							$result['ship'] = array(array('tracking_number'=>$tracking_no));
							$result['way'] = $tracking_info['shipping_name'];
							$result['physical_facility'] = $tracking_info['physical_facility'];
						}else{
							$db->rollback();
							$result['error'] = "系统异常！";
							$result['error_id'] = 3;
							$result['success'] = false;
						}
					} else { //快递单号存在，非初次绑定
						
						if ($tracking_info['shipping_id'] == $pallet_info['shipping_id'] 
						&& $tracking_info['physical_facility'] == $pallet_info['physical_facility'] ) { 
							
							$sql = "insert into `romeo`.`pallet_shipment_mapping` (shipment_id,pallet_no,bind_status,bind_user,bind_time) " .
									"values ('{$tracking_info['shipment_id']}','{$pallet_no}','BINDED','{$_SESSION['admin_name']}',now())";
							$db->query($sql);
							
							$result['success'] = true;
							$sql = "SELECT s.tracking_number,p.shipping_id,es.shipping_name,p.physical_facility 
								from romeo.pallet p 
								INNER JOIN romeo.pallet_shipment_mapping psm on psm.pallet_no = p.pallet_no
								INNER JOIN romeo.shipment s on s.SHIPMENT_ID = psm.shipment_id
								INNER JOIN ecshop.ecs_shipping es on es.shipping_id = p.shipping_id
								where p.pallet_no = '{$pallet_no}' and psm.bind_status='BINDED'";
							$tn_shipping_arr = $db->getAll($sql);
							
							$result['num'] = count($tn_shipping_arr);
							$result['ship'] = $tn_shipping_arr;
							$result['way'] = $tn_shipping_arr[0]['shipping_name'];
							$result['physical_facility'] = $tn_shipping_arr[0]['physical_facility'];
						} else if($tracking_info['shipping_id'] == $pallet_info['shipping_id'] ){
							$result['error'] = "快递单所属物理仓（".$tracking_info['physical_facility']."）与码托已属物理仓（".$pallet_info['physical_facility']."）不符";
							$result['error_id'] = 2;
							$result['success'] = false;
						}else{ //快递方式不一致
							$result['error'] = "快递单快递（".$tracking_info['shipping_name']."）与码托现有快递单快递不符";
							$result['error_id'] = 2;
							$result['success'] = false;
						}
					}
				}
			}
		}
	}
	return $result;
}

$smarty->display('shipment/pallet_bind.htm');
?>