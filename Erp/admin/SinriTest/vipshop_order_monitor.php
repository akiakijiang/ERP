<?php
define('IN_ECS',true);
require('../includes/init.php');
require_once('monitor_tools.php');

$monitor_header = new MonitorHeader("唯品会订单监控页",array('order_sn','taobao_order_sn'));
$smarty -> assign('monitor_header',$monitor_header);

// $order_sn=trim($_REQUEST['order_sn']);
if(empty($_REQUEST['order_sn'])&&empty($_REQUEST['taobao_order_sn'])){
	$smarty->assign('msg','【唯品会】请输入taobao_order_sn或order_sn');
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
		$smarty->assign('monitor_data',getVipshopOrderInfo($order_sn,$taobao_order_sn));
	}
}
$smarty->display('SinriTest/common_monitor.htm');

function getVipshopOrderInfo($order_sn,$taobao_order_sn){
		if($order_sn!==null&&$order_sn!==''){
			// ecshop.sync_vipshop_order_info
			$sql="SELECT svoi.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_vipshop_order_info svoi
					ON svoi.vip_order_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn = '{$order_sn}'";//5999422835
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【唯品会】唯品会订单中间表[ecshop.sync_vipshop_order_info]',$sql,'vip_order_id');
			$vipshop_order_dates[] = $result['monitor_info'];

			// ecshop.sync_vipshop_order_goods
			$sql="SELECT svog.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_vipshop_order_goods svog
					ON svog.vip_order_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn = '{$order_sn}'";//6941007231
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【唯品会】唯品会订单商品中间表[ecshop.sync_vipshop_order_goods]',$sql,'goods_item_id');
			$vipshop_order_dates[] = $result['monitor_info'];
		}elseif($taobao_order_sn!==null &&($order_sn==null||$order_sn=''){
			// ecshop.sync_vipshop_order_info
			$sql="SELECT svoi.* FROM ecshop.sync_vipshop_order_info svoi
					WHERE svoi.vip_order_id = '{$taobao_order_sn}'";
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【唯品会】唯品会订单中间表[ecshop.sync_vipshop_order_info]',$sql,'vip_order_id');
			$vipshop_order_dates[] = $result['monitor_info'];

			// ecshop.sync_vipshop_order_goods
			$sql="SELECT svog.* FROM ecshop.sync_vipshop_order_goods svog
				WHERE svog.vip_order_id = '{$taobao_order_sn}'";
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【唯品会】唯品会订单商品中间表[ecshop.sync_vipshop_order_goods]',$sql,'goods_item_id');
			$vipshop_order_dates[] = $result['monitor_info'];
		}

		// ecshop.ecs_order_info
		$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【唯品会订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
		$vipshop_order_dates[] = $result['monitor_info'];

		// ecshop.order_attribute
		$sql="SELECT oa.* FROM  ecshop.ecs_order_info eoi
				INNER JOIN ecshop.order_attribute oa
				ON oa.order_id = eoi.order_id 
				WHERE eoi.order_sn='{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【唯品会】唯品会订单属性表[ecshop.order_attribute]',$sql,'attribute_id');
		$vipshop_order_dates[] = $result['monitor_info'];

		// ecshop.ecs_order_goods
		$sql="SELECT eog.* FROM ecshop.ecs_order_info eoi
				INNER JOIN  ecshop.ecs_order_goods eog
				ON eog.order_id = eoi.order_id 
				WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【唯品会】唯品会订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id');
		$vipshop_order_dates[] = $result['monitor_info'];

		// ecshop.order_goods_attribute
		$sql="SELECT oga.* 
			FROM ecshop.ecs_order_info eoi
			INNER JOIN ecshop.ecs_order_goods eog ON eog.order_id = eoi.order_id 
			INNER JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = eog.rec_id
			WHERE eoi.order_sn='{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【唯品会】订单商品属性表[ecshop.order_goods_attribute]',$sql,'order_goods_attribute_id');
		$vipshop_order_dates[] = $result['monitor_info'];

		// ecshop.ecs_vipshop_order_mapping
		$sql="SELECT evom.* FROM ecshop.ecs_order_info eoi
			INNER JOIN  ecshop.ecs_vipshop_order_mapping evom
			ON evom.order_id = eoi.order_id 
			WHERE eoi.order_sn='{$order_sn}'";//8796411020
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【唯品会】唯品会订单映射表[ecshop.ecs_vipshop_order_mapping]',$sql,'mapping_id');
		$vipshop_order_dates[] = $result['monitor_info'];


	return $vipshop_order_dates;
}

?>