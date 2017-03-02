<?php 

/**
 * 发货单复核
 * 
 * @author ljzhou 
 * @copyright 2013.10.08
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_order.php');
require_once('includes/lib_common.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
// require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'admin/config.vars.php');

admin_priv('ck_out_shipment_recheck');
// 批拣单
$batch_pick_sn = 
    isset($_REQUEST['batch_pick_sn']) && trim($_REQUEST['batch_pick_sn']) 
    ? trim($_REQUEST['batch_pick_sn']) 
    : null ;

// 
$act = 
    isset($_REQUEST['act']) && trim($_REQUEST['act']) 
    ? trim($_REQUEST['act']) 
    : null ;

// 消息
$message = '';
$show_scan_tracking_number = false;
if($act == 'check'){
	$result = is_batch_can_recheck($batch_pick_sn);
	if($result['status']){
		$smarty->assign('batch_pick_sn', $batch_pick_sn);
		$message = $result['info'];
		$show_scan_tracking_number = true;
		
		$tracking_number_first = $result['min_tracking_number'];
		$tracking_number_last = $result['max_tracking_number'];
		$smarty->assign('tracking_number_first', $tracking_number_first);
		$smarty->assign('tracking_number_last', $tracking_number_last);
	}else{
		$message = $result['error'];
	}
	
}else if($act == 'update_batch_status'){
	update_out_batch_recheck_status($batch_pick_sn);
	$message = "批次号 '{$batch_pick_sn}' 复核成功！";
}

$smarty->assign('show_scan_tracking_number', $show_scan_tracking_number);
if ($message) {
    // 如果传递过来了消息则显示消息
    $smarty->assign('message', $message);
}
$smarty->display('out_shipment_recheck.htm');

function is_batch_can_recheck($batch_pick_sn){
	global $db;
	$result = array();
	$result['status'] = true;
    
    $sql = " select check_status,print_number,print_note from romeo.out_batch_pick where batch_pick_sn = '{$batch_pick_sn}'" ;
    $status_data = $db->getRow($sql);
    if(empty($status_data)){
    	$result['status'] = false;
	    $result['error'] = "该批次单（{$batch_pick_sn}）不存在，请检查是否输入有误！";
	    return $result;
    }
    $check_status = $status_data['check_status'];
    $print_number = $status_data['print_number'];
    $print_note = $status_data['print_note'];
    if($status_data['print_number'] == 0){
    	$result['status'] = false;
	    $result['error'] = "该批次单（{$batch_pick_sn}）还没打印，请先打印面单再复核！";
	    return $result;
    }else if($status_data['print_number'] > 1){
    	$result['info'] = "该批次单'{$batch_pick_sn}'打印'{$print_number}'次,请检查是否有问题！！！打印日志：{$print_note}！";
    }
    if($check_status == 'F'){
    	$result['status'] = false;
    	$result['error'] = "该批次单'{$batch_pick_sn}'已经复核";
    }
    $sql = "select max(bpm.batch_pick_mapping_id) as max_mapping_id,min(bpm.batch_pick_mapping_id) as min_mapping_id 
    		  from romeo.out_batch_pick_mapping bpm 
    		  	inner join romeo.out_batch_pick bp on bpm.batch_pick_id = bp.batch_pick_id  
    		  where bp.batch_pick_sn = '{$batch_pick_sn}' ";
    $mapping_ids = $db->getRow($sql);
    $max_mapping_id = $mapping_ids['max_mapping_id'];
    $min_mapping_id = $mapping_ids['min_mapping_id'];
    
    $sql = "select s.tracking_number
			from  romeo.out_batch_pick_mapping bpm
			inner join romeo.shipment s on bpm.shipment_id = s.shipment_id
			where bpm.batch_pick_mapping_id = '{$max_mapping_id}' ";
	$result['max_tracking_number'] = $db->getOne($sql);
	
	$sql = "select s.tracking_number
			from  romeo.out_batch_pick_mapping bpm
			inner join romeo.shipment s on bpm.shipment_id = s.shipment_id
			where bpm.batch_pick_mapping_id = '{$min_mapping_id}' ";
	$result['min_tracking_number'] = $db->getOne($sql);
    return $result;
}


function update_out_batch_recheck_status($batch_pick_sn){
	global $db;
	$action_user = $_SESSION['admin_name'];
	$now_time = date("Y-m-d h:i:sa");
	$note = "（{$action_user}）于 {$now_time} 复核成功";
	$sql = "update romeo.out_batch_pick set check_status = 'F', check_note = check_note .'{$note}'
			 where  batch_pick_sn = '{$batch_pick_sn}' ";
	$db->query($sql);
}
