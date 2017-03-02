<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("供应商内部结账（-c,-gt）信息监控页",array('order_id', 'order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['order_id']) && empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入order_sn或order_id');
}else
{
	if(!empty($_REQUEST['order_id'])){
		$order_id = $_REQUEST['order_id'];
	}else{
		$sql = "SELECT order_id from ecshop.ecs_order_info where order_sn = '{$_REQUEST['order_sn']}'";
		$order_id = $db->getOne($sql);
	}
	$smarty->assign('monitor_data', GenerateSupplierFinanceInfo($order_id));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateSupplierFinanceInfo($order_id){
	global $db;

	//ecshop.ecs_batch_order_info
	$sql = "SELECT order_id,order_goods_id,is_purchase_paid,is_finance_paid,purchase_paid_type,cheque
			from romeo.purchase_order_info
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'-c订单信息表[romeo.purchase_order_info]', $sql, 'order_goods_id');
	$return_data[] = $result['monitor_info'];

	//ecshop.ecs_batch_order_info
	$sql = "SELECT order_id,order_goods_id,is_purchase_paid,is_finance_paid,purchase_paid_type,cheque
			from ecshop.supplier_return_order_info
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'-gt订单信息表[ecshop.supplier_return_order_info]', $sql, 'order_goods_id');
	$return_data[] = $result['monitor_info'];
	//
	$sql = "SELECT *
			from ecshop.order_bill_mapping
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单和账单的映射表[ecshop.order_bill_mapping]', $sql, 'order_goods_id');
	$return_data[] = $result['monitor_info'];
	
	$sql = "SELECT pb.*
			from ecshop.order_bill_mapping obm
			inner join ecshop.ecs_purchase_bill pb on obm.purchase_bill_id = pb.purchase_bill_id 
			where obm.order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'采购付款[ecshop.ecs_purchase_bill]', $sql, 'purchase_bill_id');
	$return_data[] = $result['monitor_info'];
	
	$sql = "SELECT oicb.*
			from ecshop.order_bill_mapping obm
			inner join ecshop.ecs_oukoo_inside_c2c_bill oicb on oicb.bill_id = obm.oukoo_inside_c2c_bill_id 
			where obm.order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'财务付款[ecshop.ecs_oukoo_inside_c2c_bill]', $sql, 'bill_id');
	$return_data[] = $result['monitor_info'];

	return $return_data;
}
?>