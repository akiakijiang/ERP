<?php 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_common.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/config.vars.php');

// 批拣单
$batch_pick_sn = 
    isset($_REQUEST['batch_pick_sn']) && trim($_REQUEST['batch_pick_sn']) 
    ? trim($_REQUEST['batch_pick_sn']) 
    : null ;

$result = get_out_bpsn_data($batch_pick_sn);
$smarty->assign('batch_pick_sn', $batch_pick_sn);
$smarty->assign('goods_name', $result['goods_name']);
$smarty->assign('shipment_number', $result['shipment_number']);

$tracking_number_first = $result['min_tracking_number'];
$tracking_number_last = $result['max_tracking_number'];
$smarty->assign('tracking_number_first', $tracking_number_first);
$smarty->assign('tracking_number_last', $tracking_number_last);


$smarty->display('print_out_batch_pick_sn.htm');

function get_out_bpsn_data($batch_pick_sn){
	global $db;
	$result = array();
	
    $sql = " select goods_name from romeo.out_batch_pick where batch_pick_sn = '{$batch_pick_sn}'" ;
    $goods_name = $db->getOne($sql);
    $result['goods_name'] = $goods_name;
	
	$sql = "select count(bpm.shipment_id) 
    		  from romeo.out_batch_pick_mapping bpm 
    		  	inner join romeo.out_batch_pick bp on bpm.batch_pick_id = bp.batch_pick_id  
    		  where bp.batch_pick_sn = '{$batch_pick_sn}' ";
    $result['shipment_number'] = $db->getOne($sql);
	
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


