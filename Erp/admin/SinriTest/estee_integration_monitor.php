<?php
define('IN_ECS', true);
require_once ('../includes/init.php');
require_once ('monitor_tools.php');

$input_param_name_list = array("order_ids");
$monitor_header = new MonitorHeader("estee对接监控界面", $input_param_name_list);
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

	//ecshop.brand_estee_erp_order_remark
	$sql = "SELECT *
			from ecshop.brand_estee_erp_order_remark
			where order_id in ({$order_ids_string})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_estee_erp_order_remark', $sql, 'order_id', array('order_id'));
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_estee_order
	$sql = "SELECT *
			from ecshop.brand_estee_order
			where order_id in ({$order_ids_string})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_estee_order', $sql, 'estee_order_id', array('estee_order_id'));
	$estee_order_ids_str = $result['query_info']['estee_order_id'];
	if($estee_order_ids_str ==''){
		$estee_order_ids_str = "''";
	}
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_estee_order_goods
	$sql = "SELECT *
			from ecshop.brand_estee_order_goods
			where estee_order_id in ({$estee_order_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_estee_order_goods', $sql, 'estee_order_goods_id');
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_estee_header 
	$sql = "SELECT *
			from ecshop.brand_estee_header
			where order_id in ({$order_ids_string})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_estee_header', $sql, 'estee_header_id', array('estee_header_id'));
	$estee_header_ids_str = $result['query_info']['estee_header_id'];
	if($estee_header_ids_str ==''){
		$estee_header_ids_str = "''";
	}
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_estee_header_action 
	$sql = "SELECT *
			from ecshop.brand_estee_header_action
			where header_id in ({$estee_header_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_estee_header_action', $sql, 'action_id', array('action_id'));
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_estee_header_attribute 
	$sql = "SELECT *
			from ecshop.brand_estee_header_attribute
			where header_id in ({$estee_header_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_estee_header_attribute', $sql, 'header_id', array('header_id'));
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_estee_line 
	$sql = "SELECT *
			from ecshop.brand_estee_line
			where header_id in ({$estee_header_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_estee_line', $sql, 'estee_line_id');
	$data_array[] = $result['monitor_info'];



	//ecshop.brand_estee_line 
	$sql = "SELECT *
			from ecshop.brand_estee_sync_record
			where header_id in ({$estee_header_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_estee_sync_record', $sql, 'record_id');
	$data_array[] = $result['monitor_info'];

	return $data_array;
}
?>