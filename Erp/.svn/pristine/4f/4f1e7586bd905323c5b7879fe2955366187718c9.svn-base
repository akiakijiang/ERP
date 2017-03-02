<?php
define('IN_ECS',true);
require('../includes/init.php');
require_once('monitor_tools.php');

$monitor_header = new MonitorHeader("跨境购订单监控页",array('order_sn','taobao_order_sn'));
$smarty -> assign('monitor_header',$monitor_header);

if(empty($_REQUEST['order_sn']) && empty($_REQUEST['taobao_order_sn'])) {
	$smarty->assign('msg','【跨境购】请输入ERP订单号或者外部订单号');
} else {
	if(!empty($_REQUEST['order_sn'])){
			$order_sn = $_REQUEST['order_sn'];
			$sql = "select taobao_order_sn from ecshop.ecs_order_info where order_sn='{$_REQUEST['order_sn']}'";
			$taobao_order_sn = $db->getOne($sql);
		}else{
			$sql = "select order_sn from ecshop.ecs_order_info where taobao_order_sn='{$_REQUEST['taobao_order_sn']}'";
			$order_sn = $db->getOne($sql);
			$taobao_order_sn = $_REQUEST['taobao_order_sn'];
		}
		$sql = "select count(*) from ecshop.ecs_order_info where order_sn='{$order_sn}'";
		$order_count = $db->getOne($sql);
		if(empty($order_count) || $order_count<=0){
			$smarty->assign('smg','请输入正确的order_sn或taobao_order_sn!');
		}else{
			$smarty->assign('monitor_data',getKuajinggouOrderInfo($order_sn,$taobao_order_sn));
		}
}
$smarty->display('SinriTest/common_monitor.htm');

function getKuajinggouOrderInfo($order_sn,$taobao_order_sn) {
	if ($order_sn !== null) { //通过order_sn查询中间表
		// ecshop.sync_kjg_order_info
		$sql="SELECT spoi.* FROM ecshop.ecs_order_info eoi 
				INNER JOIN ecshop.sync_kjg_order_info spoi
				ON spoi.taobao_order_sn = eoi.taobao_order_sn 
				WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【跨境购】跨境购订单中间表[ecshop.sync_kjg_order_info]',$sql,'kjg_order_id');
		$pinduoduo_order_dates[] = $result['monitor_info'];
		// ecshop.sync_kjg_order_goods
		$sql="SELECT spog.* FROM ecshop.sync_kjg_order_goods spog
			INNER JOIN ecshop.ecs_order_info eoi
			ON spog.taobao_order_sn = eoi.taobao_order_sn 
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【跨境购】跨境购订单商品中间表[ecshop.sync_kjg_order_goods]',$sql,'kjg_goods_id');
		$pinduoduo_order_dates[] = $result['monitor_info'];
		
		$sql="SELECT spog.* FROM ecshop.sync_kjg_order_status spog
			INNER JOIN ecshop.ecs_order_info eoi
			ON spog.taobao_order_sn = eoi.taobao_order_sn 
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【跨境购】跨境购订单状态中间表[ecshop.sync_kjg_order_status]',$sql,'mft_no');
		$pinduoduo_order_dates[] = $result['monitor_info'];
		
		$sql="SELECT spog.* FROM ecshop.sync_kjg_order_status_log spog
			INNER JOIN ecshop.ecs_order_info eoi
			ON spog.taobao_order_sn = eoi.taobao_order_sn 
			WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【跨境购】跨境购订单状态记录中间表[ecshop.sync_kjg_order_status_log]',$sql,'status_create_time');
		$pinduoduo_order_dates[] = $result['monitor_info'];
		
		
	} elseif($taobao_order_sn !== null && $order_sn == null) { //通过taobao_order_sn查询中间表
		// ecshop.sync_kjg_order_info
		$sql="SELECT spoi.* FROM ecshop.sync_kjg_order_info spoi
			  WHERE spoi.taobao_order_sn = '{$taobao_order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【跨境购】跨境购订单中间表[ecshop.sync_kjg_order_info]',$sql,'kjg_order_id');
		$pinduoduo_order_dates[] = $result['monitor_info'];
		// ecshop.sync_kjg_order_goods
		$sql="SELECT spog.* FROM ecshop.sync_kjg_order_goods spog
			WHERE spog.taobao_order_sn = '{$taobao_order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【跨境购】跨境购订单商品中间表[ecshop.sync_kjg_order_goods]',$sql,'kjg_goods_id');
		$pinduoduo_order_dates[] = $result['monitor_info'];
		
		$sql="SELECT spog.* FROM ecshop.sync_kjg_order_status spog
			WHERE spog.taobao_order_sn = '{$taobao_order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【跨境购】跨境购订单状态中间表[ecshop.sync_kjg_order_status]',$sql,'mft_no');
		$pinduoduo_order_dates[] = $result['monitor_info'];
		
		$sql="SELECT spog.* FROM ecshop.sync_kjg_order_status_log spog
			WHERE spog.taobao_order_sn = '{$taobao_order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【跨境购】跨境购订单状态记录中间表[ecshop.sync_kjg_order_status_log]',$sql,'status_create_time');
		$pinduoduo_order_dates[] = $result['monitor_info'];
	}
	// ecshop.ecs_order_info
	$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【跨境购】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
	$pinduoduo_order_dates[] = $result['monitor_info'];

	// ecshop.order_attribute
	$sql="SELECT oa.* FROM ecshop.ecs_order_info eoi
			INNER JOIN  ecshop.order_attribute oa
			ON oa.order_id = eoi.order_id 
			WHERE eoi.order_sn='{$order_sn}'";//0295128559
	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【跨境购】订单属性表[ecshop.order_attribute]',$sql,'attribute_id');
	$pinduoduo_order_dates[] = $result['monitor_info'];

	// ecshop.ecs_order_goods
	$sql="SELECT eog.* FROM ecshop.ecs_order_info eoi
			INNER JOIN  ecshop.ecs_order_goods eog
			ON eog.order_id = eoi.order_id 
			WHERE eoi.order_sn = '{$order_sn}'";//1961361545
	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【跨境购】跨境购订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id');
	$pinduoduo_order_dates[] = $result['monitor_info'];

	// ecshop.order_goods_attribute
	$sql="SELECT oga.* 
	FROM ecshop.ecs_order_info eoi
	INNER JOIN ecshop.ecs_order_goods eog ON eog.order_id = eoi.order_id 
	INNER JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = eog.rec_id
	WHERE eoi.order_sn='{$order_sn}'";//6178117038
	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【跨境购】订单商品属性表[ecshop.order_goods_attribute]',$sql,'order_goods_attribute_id');
	$pinduoduo_order_dates[] = $result['monitor_info'];

//			// ecshop.ecs_pinduoduo_order_mapping
//	$sql="SELECT epom.* FROM ecshop.ecs_order_info eoi
//		INNER JOIN ecshop.ecs_pinduoduo_order_mapping epom
//		ON epom.order_id = eoi.order_id 
//		WHERE eoi.order_sn = '{$order_sn}'";
//	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
//		'【跨境购】跨境购礼物社交订单映射表[ecshop.ecs_pinduoduo_order_mapping]',$sql,'mapping_id');
//	$pinduoduo_order_dates[] = $result['monitor_info'];
    
    return $pinduoduo_order_dates;
}

?>