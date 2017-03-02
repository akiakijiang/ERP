<?php

function GenerateOrderActionBasicData($order_id){
	global $db;
	$data_for_generate = array();

	//ecshop.ecs_order_info
	$sql = "SELECT order_id, order_status, shipping_status, pay_status, invoice_status,shortage_status
			from ecshop.ecs_order_info
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'ecshop.ecs_order_info(用于校对ecs_order_action记录)', $sql, 'order_id');
	$data_for_generate[] = $result['monitor_info'];

	//ecshop.ecs_order_action
	$sql = "SELECT *
			from ecshop.ecs_order_action 
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单操作表[ecshop.ecs_order_action]', $sql, 'action_id', array('action_id'));
	$data_for_generate[] = $result['monitor_info'];

	return $data_for_generate;
}
?>