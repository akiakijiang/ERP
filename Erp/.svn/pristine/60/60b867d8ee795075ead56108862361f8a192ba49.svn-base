<?php
define('IN_ECS', true);
require_once ('../includes/init.php');
require_once ('monitor_tools.php');

$input_param_name_list = array("order_ids");
$monitor_header = new MonitorHeader("OR对接监控界面", $input_param_name_list);
$smarty->assign('monitor_header', $monitor_header);

$order_ids_string = $_REQUEST["order_ids"];

if(CheckInputParams($order_ids_string)){
	$smarty->assign('monitor_data', GenerateDataForMonitor($order_ids_string));
}else{
	$smarty->assign('msg', '检索参数异常，请检查');
}
$smarty->display('SinriTest/common_monitor.htm');

function CheckInputParams($order_ids_string){
	return $order_ids_string != '';
}

function GenerateDataForMonitor($order_ids_string){
	global $db;

	//ecshop.brand_or_erp_order_remark
	$sql = "SELECT *
			from ecshop.brand_or_erp_order_remark
			where order_id in ({$order_ids_string})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_or_erp_order_remark', $sql, 'order_id', array('order_id'));
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_or_order
	$sql = "SELECT *
			from ecshop.brand_or_order
			where order_id in ({$order_ids_string})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_or_order', $sql, 'or_order_id', array('or_order_id'));
	$or_order_ids_str = $result['query_info']['or_order_id'];
	if($or_order_ids_str ==''){
		$or_order_ids_str = "''";
	}
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_or_order_goods
	$sql = "SELECT *
			from ecshop.brand_or_order_goods
			where or_order_id in ({$or_order_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_or_order_goods', $sql, 'or_order_goods_id');
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_or_header 
	$sql = "SELECT *
			from ecshop.brand_or_header
			where order_id in ({$order_ids_string})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_or_header', $sql, 'or_header_id', array('or_header_id'));
	$or_header_ids_str = $result['query_info']['or_header_id'];
	if($or_header_ids_str ==''){
		$or_header_ids_str = "''";
	}
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_or_header_action 
	$sql = "SELECT *
			from ecshop.brand_or_header_action
			where header_id in ({$or_header_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_or_header_action', $sql, 'action_id', array('action_id'));
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_or_header_attribute 
	$sql = "SELECT *
			from ecshop.brand_or_header_attribute
			where header_id in ({$or_header_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_or_header_attribute', $sql, 'header_id', array('header_id'));
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_or_line 
	$sql = "SELECT *
			from ecshop.brand_or_line
			where header_id in ({$or_header_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_or_line', $sql, 'or_line_id');
	$data_array[] = $result['monitor_info'];



	//ecshop.brand_or_line 
	$sql = "SELECT *
			from ecshop.brand_or_sync_record
			where header_id in ({$or_header_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_or_sync_record', $sql, 'record_id');
	$data_array[] = $result['monitor_info'];

	return $data_array;
}
?>