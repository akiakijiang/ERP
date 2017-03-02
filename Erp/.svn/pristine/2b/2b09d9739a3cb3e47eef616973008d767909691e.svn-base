<?php
define('IN_ECS', true);
require_once ('../includes/init.php');
require_once ('monitor_tools.php');

$input_param_name_list = array(
"order_ids",'document_ids','order_type',
'order_date_start'=>'order_date_start','order_date_end'=>'order_date_end',
'sync_status','time_outs',
'pricing_date_start'=>'pricing_date_start','pricing_date_end'=>'pricing_date_end',
'requested_delivery_date_start'=>'requested_delivery_date_start','requested_delivery_date_end'=>'requested_delivery_date_end',
'created_stamp_start'=>'created_stamp_start','created_stamp_end'=>'created_stamp_end',
'last_updated_stamp_start'=>'last_updated_stamp_start','last_updated_stamp_end'=>'last_updated_stamp_end'
);

$monitor_header = new MonitorHeader("OR对接监控页-header list", $input_param_name_list);
$smarty->assign('monitor_header', $monitor_header);

$cond = get_condition();
//var_dump($cond);
if(CheckInputParams($cond)){
	$smarty->assign('monitor_data', GenerateDataForMonitor($cond));
}else{
	$smarty->assign('msg', '请至少填一个筛选项，请检查');
}

$smarty->display('SinriTest/common_monitor.htm');

function CheckInputParams($cond){
	if(empty($cond)){
		return false;
	}
	return true;
}


function GenerateDataForMonitor($cond){
	global $db;

	$sql = "SELECT * from ecshop.brand_or_header h where 1 $cond";
//	var_dump($sql);
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'[ecshop.brand_or_header]', $sql, 'or_header_id');
	$data[] = $result['monitor_info'];

	return $data;
}


function get_condition() {
	$cond = '';

	$order_ids = trim($_REQUEST["order_ids"]);
	$order_ids = convert_str_to_sql($order_ids);
	$document_ids = trim($_REQUEST["document_ids"]);
	$document_ids = convert_str_to_sql($document_ids);
	$order_type = trim($_REQUEST["order_type"]);
	$order_date_start = trim($_REQUEST["order_date_start"]);
	$order_date_end = trim($_REQUEST["order_date_end"]);
	$sync_status = trim($_REQUEST["sync_status"]);
	$time_outs = trim($_REQUEST["time_outs"]);
	$pricing_date_start = trim($_REQUEST["pricing_date_start"]);
	$pricing_date_end = trim($_REQUEST["pricing_date_end"]);
	$requested_delivery_date_start = trim($_REQUEST["requested_delivery_date_start"]);
	$requested_delivery_date_end = trim($_REQUEST["requested_delivery_date_end"]);
	$created_stamp_start = trim($_REQUEST["created_stamp_start"]);
	$created_stamp_end = trim($_REQUEST["created_stamp_end"]);
	$last_updated_stamp_start = trim($_REQUEST["last_updated_stamp_start"]);
	$last_updated_stamp_end = trim($_REQUEST["last_updated_stamp_end"]);
	
	if($order_ids) {
		$cond .= " and order_id in ({$order_ids}) ";
	}
	if($document_ids) {
		$cond .= " and document_id in ({$document_ids}) ";
	}
	if($order_date_start) {
		$cond .= " and order_date >=  '{$order_date_start}' ";
	}
	if($order_date_end) {
		$cond .= " and order_date <  '{$order_date_end}' ";
	}
	if($order_type) {
		$cond .= " and order_type= '{$order_type}' ";
	}
	if($sync_status) {
		$cond .= " and sync_status = '{$sync_status}' ";
	}
	if($time_outs) {
		$cond .= " and time_outs = '{$time_outs}' ";
	}
	if($pricing_date_start) {
		$cond .= " and pricing_date >= '{$pricing_date_start}' ";
	}
	if($pricing_date_end) {
		$cond .= " and pricing_date < '{$pricing_date_end}' ";
	}
	if($requested_delivery_date_start) {
		$cond .= " and requested_delivery_date >= '{$requested_delivery_date_start}' ";
	}
	if($requested_delivery_date_end) {
		$cond .= " and requested_delivery_date < '{$requested_delivery_date_end}' ";
	}
	if($created_stamp_start) {
		$cond .= " and created_stamp >= '{$created_stamp_start}' ";
	}
	if($created_stamp_end) {
		$cond .= " and created_stamp < '{$created_stamp_end}' ";
	}
	if($last_updated_stamp_start) {
		$cond .= " and last_updated_stamp >= '{$last_updated_stamp_start}' ";
	}
	if($last_updated_stamp_end) {
		$cond .= " and last_updated_stamp < '{$last_updated_stamp_end}' ";
	}

	return $cond;
	
}


?>