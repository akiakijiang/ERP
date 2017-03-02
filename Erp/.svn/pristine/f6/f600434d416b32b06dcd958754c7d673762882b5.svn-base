<?php
define('IN_ECS', true);
require_once ('../includes/init.php');
require_once ('monitor_tools.php');

$input_param_name_list = array("nes_order_number");
$monitor_header = new MonitorHeader("Babynes对接监控界面", $input_param_name_list);
$smarty->assign('monitor_header', $monitor_header);

$nes_order_number = $_REQUEST["nes_order_number"];

if(CheckInputParams($nes_order_number)){
	$smarty->assign('monitor_data', GenerateDataForMonitor($nes_order_number));
}else{
	$smarty->assign('msg', '检索参数异常，请检查');
}
$smarty->display('SinriTest/common_monitor.htm');

function CheckInputParams($nes_order_number){
	return $nes_order_number != '';
}

function GenerateDataForMonitor($nes_order_number){
	global $db;

	//ecshop.brand_nes_order_v2
	$sql = "SELECT *
			from ecshop.brand_nes_order
			where orderNumber = '{$nes_order_number}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_nes_order', $sql, 'nes_order_id', array('erp_order_id', 'nes_order_id'));
	$data_array[] = $result['monitor_info'];
	$erp_order_id_str = $result['query_info']['erp_order_id'];
	$nes_order_id_str = $result['query_info']['nes_order_id'];
	if(empty($erp_order_id_str)){
		return $data_array;
	}

	//ecshop.brand_nes_customer_v2
	$sql = "SELECT *
			from ecshop.brand_nes_customer
			where nes_order_id = {$nes_order_id_str}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_nes_customer', $sql, 'nes_customer_id');
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_nes_shipping
	$sql = "SELECT *
			from ecshop.brand_nes_shipping
			where nes_order_id = {$nes_order_id_str}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_nes_shipping', $sql, 'nes_shipping_id');
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_nes_payment
	$sql = "SELECT *
			from ecshop.brand_nes_payment
			where nes_order_id = {$nes_order_id_str}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_nes_payment', $sql, 'nes_payment_id');
	$data_array[] = $result['monitor_info'];

	//ecshop.brand_nes_order_item
	$sql = "SELECT *
			from ecshop.brand_nes_order_item
			where nes_order_id = {$nes_order_id_str}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_nes_order_item', $sql, 'nes_order_item_id', array('nes_order_item_id'));
	$data_array[] = $result['monitor_info'];
	$nes_order_item_ids_str = $result['query_info']['nes_order_item_id'];
	
	//ecshop.brand_nes_order_item_monetary
	$sql = "SELECT *
			from ecshop.brand_nes_order_item_monetary
			where nes_order_item_id in ({$nes_order_item_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.brand_nes_order_item_monetary', $sql, 'nes_order_item_monetary_id');
	$data_array[] = $result['monitor_info'];



	//ecshop.ecs_order_info 
	$sql = "SELECT *
			from ecshop.ecs_order_info
			where order_id ={$erp_order_id_str}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_order_info', $sql, 'order_id');
	$data_array[] = $result['monitor_info'];

	//ecshop.ecs_order_goods 
	$sql = "SELECT *
			from ecshop.ecs_order_goods
			where order_id ={$erp_order_id_str}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_order_goods', $sql, 'rec_id');
	$data_array[] = $result['monitor_info'];

	return $data_array;
}
?>