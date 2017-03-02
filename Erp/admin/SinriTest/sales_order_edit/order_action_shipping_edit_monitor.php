<?php
define('IN_ECS', true);
require_once('../../includes/init.php');
require_once ('../monitor_tools.php');

$monitor_header = new MonitorHeader("销售订单修改快递方式监控页",array('order_id', 'order_sn'));
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
	$sql = "SELECT order_id, shipping_id, shipping_name, 
					shipping_fee, shipping_proxy_fee, 
					goods_amount, pack_fee, bonus, integral_money, order_amount
			from ecshop.ecs_order_info
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_order_info', $sql, 'order_id');
	$data_for_generate[] = $result['monitor_info'];


	//romeo.shipment
	$sql = "SELECT os.order_id, s.shipment_id, shipment_type_id, carrier_id, last_modified_by_user_login
			from romeo.shipment s
			left join romeo.order_shipment os on s.shipment_id = os.shipment_id
			where os.order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'romeo.shipment', $sql, 'shipment_id');
	$data_for_generate[] = $result['monitor_info'];

	//ecshop.ecs_shipping
	$sql = "SELECT shipping_id from ecshop.ecs_order_info where order_id = {$order_id}";
	$shipping_id = $db->getOne($sql);
	$shipping_id = isset($shipping_id) ? $shipping_id : -1;
	$sql = "SELECT shipping_id, default_carrier_id
			from ecshop.ecs_shipping
			where shipping_id = {$shipping_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_shipping', $sql, 'shipping_id');
	$data_for_generate[] = $result['monitor_info'];


	require_once('order_action_basic.php');
	return array_merge($data_for_generate, GenerateOrderActionBasicData($order_id));
}
?>