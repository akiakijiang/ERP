<?php
define('IN_ECS',true);
require('../includes/init.php');
require_once('monitor_tools.php');

$monitor_header = new MonitorHeader("天猫订单监控页",array('order_sn','taobao_order_sn'));
$smarty -> assign('monitor_header',$monitor_header);

// $order_sn=trim($_REQUEST['order_sn']);
if(empty($_REQUEST['order_sn'])&&empty($_REQUEST['taobao_order_sn'])){
	$smarty->assign('msg','【天猫】请输入taobao_order_sn或order_sn');
}else{
	if(!empty($_REQUEST['order_sn'])){
		$order_sn=$_REQUEST['order_sn'];
	}else{
		$sql="select order_sn from ecshop.ecs_order_info where taobao_order_sn='{$_REQUEST['taobao_order_sn']}'";
		$order_sn=$db->getOne($sql);
		$taobao_order_sn=$_REQUEST['taobao_order_sn'];
	}
	$sql="select count(*) from ecshop.ecs_order_info where order_sn='{$order_sn}'";
	$order_count=$db->getOne($sql);
	if(empty($order_count) || $order_count<=0){
		$smarty->assign('smg','请输入正确的order_sn或taobao_order_sn!');
	}else{
		$smarty->assign('monitor_data',getTmallOrderInfo($order_sn,$taobao_order_sn));
	}
}
$smarty->display('SinriTest/common_monitor.htm');

function getTmallOrderInfo($order_sn,$taobao_order_sn){
	if ($order_sn!==null&&$order_sn!==''){ //通过order_sn查询中间表
		// ecshop.sync_taobao_order_info
		$sql="SELECT stoi.* FROM ecshop.ecs_order_info eoi 
				INNER JOIN ecshop.sync_taobao_order_info stoi
				ON stoi.tid = eoi.taobao_order_sn 
				WHERE eoi.order_sn = '{$order_sn}'";//taobao_order_sn=1001310339562629
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【天猫】淘宝订单中间表[ecshop.sync_taobao_order_info]',$sql,'tid');
		$tmall_order_dates[] = $result['monitor_info'];
		// ecshop.sync_taobao_order_goods
		$sql="SELECT stog.* FROM ecshop.sync_taobao_order_goods stog
			INNER JOIN ecshop.ecs_order_info eoi
			ON stog.tid = eoi.taobao_order_sn 
			WHERE eoi.order_sn = '{$order_sn}'";//0899297593,3645029495
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【天猫】淘宝订单商品中间表[ecshop.sync_taobao_order_goods]',$sql,'oid');
		$tmall_order_dates[] = $result['monitor_info'];
	}elseif($taobao_order_sn!==null &&($order_sn==null||$order_sn='')){ //通过taobao_order_sn查询中间表
		// ecshop.sync_taobao_order_info
		$sql="SELECT stoi.* FROM ecshop.sync_taobao_order_info stoi
			  WHERE stoi.tid = '{$taobao_order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【天猫】淘宝订单中间表[ecshop.sync_taobao_order_info]',$sql,'tid');
		$tmall_order_dates[] = $result['monitor_info'];
		// ecshop.sync_taobao_order_goods
		$sql="SELECT stog.* FROM ecshop.sync_taobao_order_goods stog
			WHERE stog.tid = '{$taobao_order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【天猫】淘宝订单商品中间表[ecshop.sync_taobao_order_goods]',$sql,'oid');
		$tmall_order_dates[] = $result['monitor_info'];
	}
	// ecshop.ecs_order_info
	$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【天猫订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
	$tmall_order_dates[] = $result['monitor_info'];

	// ecshop.order_attribute
	$sql="SELECT oa.* FROM ecshop.ecs_order_info eoi
			INNER JOIN  ecshop.order_attribute oa
			ON oa.order_id = eoi.order_id 
			WHERE eoi.order_sn='{$order_sn}'";//0295128559
	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【天猫】订单属性表[ecshop.order_attribute]',$sql,'attribute_id');
	$tmall_order_dates[] = $result['monitor_info'];

	// ecshop.ecs_order_goods
	$sql="SELECT eog.* FROM ecshop.ecs_order_info eoi
			INNER JOIN  ecshop.ecs_order_goods eog
			ON eog.order_id = eoi.order_id 
			WHERE eoi.order_sn = '{$order_sn}'";//1961361545
	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【天猫】淘宝订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id');
	$tmall_order_dates[] = $result['monitor_info'];

	// ecshop.order_goods_attribute
	$sql="SELECT oga.* 
	FROM ecshop.ecs_order_info eoi
	INNER JOIN ecshop.ecs_order_goods eog ON eog.order_id = eoi.order_id 
	INNER JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = eog.rec_id
	WHERE eoi.order_sn='{$order_sn}'";//6178117038
	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【天猫】订单商品属性表[ecshop.order_goods_attribute]',$sql,'order_goods_attribute_id');
	$tmall_order_dates[] = $result['monitor_info'];

			// ecshop.ecs_taobao_order_mapping
	$sql="SELECT etom.* FROM ecshop.ecs_order_info eoi
		INNER JOIN ecshop.ecs_taobao_order_mapping etom
		ON etom.order_id = eoi.order_id 
		WHERE eoi.order_sn = '{$order_sn}'";//9796010869
	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【天猫】淘宝订单映射表[ecshop.ecs_taobao_order_mapping]',$sql,'mapping_id');
	$tmall_order_dates[] = $result['monitor_info'];
    
    
    // ecshop.ecs_order_mapping 添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
	$sql="SELECT eom.* FROM ecshop.ecs_order_info eoi
		INNER JOIN ecshop.ecs_order_mapping eom
		ON eom.outer_order_sn = eoi.taobao_order_sn 
		WHERE eoi.order_sn = '{$order_sn}'";//9796010869
	$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【天猫】新淘宝订单映射表[ecshop.ecs_order_mapping]',$sql,'mapping_id');
	$tmall_order_dates[] = $result['monitor_info'];

	return $tmall_order_dates;
}

?>