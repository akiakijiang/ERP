<?php
define('IN_ECS', true);
require_once('../../includes/init.php');
require_once ('../monitor_tools.php');

$monitor_header = new MonitorHeader("销售订单合并订单监控页",array('当前订单order_sn', '待合并订单外部类型(taobao or erp)', '待合并订单order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['当前订单order_sn'])){
	$smarty->assign('msg', '请输入当前订单order_sn');
}else if(empty($_REQUEST['待合并订单外部类型'])){
	$smarty->assign('msg', '请输入待合并订单外部类型');
}else if(empty($_REQUEST['待合并订单order_sn'])){
	$smarty->assign('msg', '请输入待合并订单order_sn');
}else{
	$sql = "SELECT order_id from ecshop.ecs_order_info where order_sn = '{$_REQUEST['当前订单order_sn']}'";
	$original_order_id = $db->getOne($sql);
	if($_REQUEST['待合并订单外部类型'] == 'taobao'){
		$sql = "SELECT order_id from ecshop.ecs_order_info where taobao_order_sn = '{$_REQUEST['待合并订单order_sn']}'";
		$to_merge_order_id = $db->getOne($sql);	
	}else{
		$sql = "SELECT order_id from ecshop.ecs_order_info where order_sn = '{$_REQUEST['待合并订单order_sn']}'";
		$to_merge_order_id = $db->getOne($sql);	
	}
	$smarty->assign('monitor_data', 
		array_merge(GenerateOrderActionBasicData($original_order_id), GenerateOrderActionBasicData($to_merge_order_id)));
}
$smarty->display('SinriTest/common_monitor.htm');
?>