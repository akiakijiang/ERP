<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');


$monitor_header = new MonitorHeader(
	"供应商退货订单监控页",
	array('SUPPLIER_RETURN_ID'));
$smarty->assign('monitor_header', $monitor_header);

if(!empty($_REQUEST['SUPPLIER_RETURN_ID'])) {
	$smarty->assign('monitor_data', GenerateMonitorInfo($_REQUEST['SUPPLIER_RETURN_ID']));
}
$smarty->display('SinriTest/common_gt_monitor.htm');


function GenerateMonitorInfo($order_ids){
	global $db;
	
	//供应商退货申请记录
	$sql = "select * from romeo.supplier_return_request where SUPPLIER_RETURN_ID= '".$order_ids."'";	
	$oi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单表[romeo.supplier_return_request]', $sql, 'SUPPLIER_RETURN_ID',array('FACILITY_ID','PRODUCT_ID'));
	$monitor_info_for_generate[] = $oi_result['monitor_info'];
	$facility_id = $oi_result['query_info']['FACILITY_ID'];
	$product_id = $oi_result['query_info']['PRODUCT_ID'];
	
	//查询inventory_summary表
	$sql = "select * from romeo.inventory_summary where product_id={$product_id} and facility_id={$facility_id} and status_id in ('INV_STTS_AVAILABLE','INV_STTS_USED') ";	
	$oi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单表[romeo.inventory_summary]', $sql,'INVENTORY_SUMMARY_ID');
	$monitor_info_for_generate[] = $oi_result['monitor_info'];
	
	//查询romeo.inventory_item表
	$sql = "select * from romeo.inventory_item where product_id={$product_id} and facility_id={$facility_id}   and QUANTITY_ON_HAND_TOTAL<>0";	
	$oi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单表[romeo.inventory_item]', $sql,'INVENTORY_ITEM_ID');
	$monitor_info_for_generate[] = $oi_result['monitor_info'];
	
	//查询romeo.order_inv_reserved_detail表
	$sql = "select * from romeo.order_inv_reserved_detail  where product_id={$product_id} and facility_id ={$facility_id}" .
			" and RESERVED_TIME >=date_add(now(),interval - 15 day) and STATUS='Y' limit 100";	
	$oi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单表[romeo.order_inv_reserved_detail]', $sql,'ORDER_INV_RESERVED_DETAIL_ID');
	$monitor_info_for_generate[] = $oi_result['monitor_info'];
	
	return $monitor_info_for_generate;
}
?>