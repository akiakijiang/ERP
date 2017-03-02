<?php
define('IN_ECS', true);
require_once('../../includes/init.php');
require_once ('../monitor_tools.php');

$monitor_header = new MonitorHeader("销售订单拒收监控页",array('order_id', 'order_sn'));
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
	$sql = "SELECT order_id, order_status, shortage_status, shipping_status, bonus_id
			from ecshop.ecs_order_info
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_order_info', $sql, 'order_id');
	$data_for_generate[] = $result['monitor_info'];

	//ecshop.ecs_user_bonus
	$sql = "SELECT bonus_id FROM ecshop.ecs_order_info where order_id = {$order_id}";
	$bonus_id = $db->getOne($sql);
	$bonus_id = isset($bonus_id) ? $bonus_id : -1;
	$sql = "SELECT bonus_sn, used_time,order_id 
			from ecshop.ecs_user_bonus
			where bonus_sn = '{$bonus_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_user_bonus', $sql, 'bonus_id');
	$data_for_generate[] = $result['monitor_info'];

	//membership.ok_gift_ticket //die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
	// $sql = "SELECT gt_code, gt_state
	// 		from membership.ok_gift_ticket
	// 		where gt_code = '{$bonus_id}'";
	// $result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
	// 	'membership.ok_gift_ticket', $sql, 'gt_id');
	// $data_for_generate[] = $result['monitor_info'];

	require_once('order_action_basic.php');
	return array_merge($data_for_generate, GenerateOrderActionBasicData($order_id));
}
?>