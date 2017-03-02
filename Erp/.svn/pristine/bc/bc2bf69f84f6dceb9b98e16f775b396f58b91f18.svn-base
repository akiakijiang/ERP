<?php
define('IN_ECS', true);
require_once('../../includes/init.php');
require_once ('../monitor_tools.php');

$monitor_header = new MonitorHeader("销售订单修改支付方式监控页",array('order_id', 'order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['order_id']) && empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入order_id或order_sn');
}else
{
	if(!empty($_REQUEST['order_id'])){
		$order_id = $_REQUEST['order_id'];
	}else{
		$sql = "SELECT order_id from ecshop.ecs_order_info where order_sn = '{$_REQUEST['order_sn']}'";
		$order_id = $db->getOne($sql);
	}
	$smarty->assign('monitor_data', GenerateOrderInfo($order_id));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateOrderInfo($order_id){
	global $db;
	$data_for_generate = array();

	//ecshop.ecs_order_info
	$sql = "SELECT order_id, pay_id, pay_name
			from ecshop.ecs_order_info
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_order_info', $sql, 'order_id');
	$data_for_generate[] = $result['monitor_info'];


	//romeo.payment_transaction
	$sql = "SELECT order_id, pay_id
			from romeo.payment_transaction
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'romeo.payment_transaction', $sql, 'payment_transaction_id');
	$data_for_generate[] = $result['monitor_info'];


	require_once('order_action_basic.php');
	return array_merge($data_for_generate, GenerateOrderActionBasicData($order_id));
}
?>