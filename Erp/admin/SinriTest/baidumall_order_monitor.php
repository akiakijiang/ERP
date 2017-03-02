<?php
define('IN_ECS',true);
require('../includes/init.php');
require_once('monitor_tools.php');

$monitor_header = new MonitorHeader("百度MALL订单监控页",array('order_sn','taobao_order_sn'));
$smarty -> assign('monitor_header',$monitor_header);

// $order_sn=trim($_REQUEST['order_sn']);
if(empty($_REQUEST['order_sn'])&&empty($_REQUEST['taobao_order_sn'])){
	$smarty->assign('msg','【百度Mall】请输入taobao_order_sn或order_sn');
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
		$smarty->assign('monitor_data',getbaidumallOrderInfo($order_sn,$taobao_order_sn));
	}
}
$smarty->display('SinriTest/common_monitor.htm');

function getbaidumallOrderInfo($order_sn,$taobao_order_sn){
		if($order_sn!==null&&$order_sn!==''){
			// ecshop.sync_baidumall_order_info
			$sql="SELECT sboi.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_baidumall_order_info sboi
					ON sboi.trade_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn = '{$order_sn}'";//5071684826  
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】百度MALL订单中间表[ecshop.sync_baidumall_order_info]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];

			// ecshop.sync_baidumall_order_goods
			$sql="SELECT sbog.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_baidumall_order_goods sbog
					ON sbog.trade_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn = '{$order_sn}'";//5071684826
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】百度MALL订单商品中间表[ecshop.sync_baidumall_order_goods]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];

			// ecshop.sync_baidumall_order_promotion
			$sql="SELECT sbop.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_baidumall_order_promotion sbop
					ON sbop.trade_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn = '{$order_sn}'";//2199760894  
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】中间表[ecshop.sync_baidumall_order_promotion]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];

			// ecshop.sync_baidumall_order_promotion_gift
			$sql="SELECT sbopg.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_baidumall_order_promotion_gift sbopg
					ON sbopg.trade_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn = '{$order_sn}'";//2199760894  
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】中间表[ecshop.sync_baidumall_order_promotion_gift]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];

			// ecshop.sync_baidumall_order_coupon
			$sql="SELECT sboc.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_baidumall_order_coupon sboc
					ON sboc.trade_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn = '{$order_sn}'";//2199760894  
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】中间表[ecshop.sync_baidumall_order_coupon]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];


		}elseif($taobao_order_sn!==null &&($order_sn==null||$order_sn='')){
			// ecshop.sync_baidumall_order_info
			$sql="SELECT sboi.* FROM ecshop.sync_baidumall_order_info sboi
					WHERE sboi.trade_id = '{$taobao_order_sn}'";
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】百度MALL订单中间表[ecshop.sync_baidumall_order_info]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];

			// ecshop.sync_baidumall_order_goods
			$sql="SELECT sbog.* FROM ecshop.sync_baidumall_order_goods sbog
				WHERE sbog.trade_id = '{$taobao_order_sn}'";
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】百度MALL订单商品中间表[ecshop.sync_baidumall_order_goods]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];

			// ecshop.sync_baidumall_order_promotion
			$sql="SELECT sbop.* FROM ecshop.sync_baidumall_order_promotion sbop
				  WHERE sbop.trade_id = '{$taobao_order_sn}'";//2199760894  
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】中间表[ecshop.sync_baidumall_order_promotion]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];

			// ecshop.sync_baidumall_order_promotion_gift
			$sql="SELECT sbopg.* FROM ecshop.sync_baidumall_order_promotion_gift sbopg
					WHERE sbopg.trade_id = '{$taobao_order_sn}'";//2199760894  
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】中间表[ecshop.sync_baidumall_order_promotion_gift]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];

			// ecshop.sync_baidumall_order_coupon
			$sql="SELECT sboc.* FROM ecshop.sync_baidumall_order_coupon sboc
					WHERE sboc.trade_id = '{$taobao_order_sn}'";//2199760894  
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【百度MALL】中间表[ecshop.sync_baidumall_order_coupon]',$sql,'trade_id');
			$baidumall_order_dates[] = $result['monitor_info'];
		}

		// ecshop.ecs_order_info
		$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【百度MALL订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
		$baidumall_order_dates[] = $result['monitor_info'];

		// ecshop.order_attribute
		$sql="SELECT oa.* FROM  ecshop.ecs_order_info eoi
				INNER JOIN ecshop.order_attribute oa
				ON oa.order_id = eoi.order_id 
				WHERE eoi.order_sn='{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【百度MALL】百度MALL订单属性表[ecshop.order_attribute]',$sql,'attribute_id');
		$baidumall_order_dates[] = $result['monitor_info'];

		// ecshop.ecs_order_goods
		$sql="SELECT eog.* FROM ecshop.ecs_order_info eoi
				INNER JOIN  ecshop.ecs_order_goods eog
				ON eog.order_id = eoi.order_id 
				WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【百度MALL】百度MALL订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id');
		$baidumall_order_dates[] = $result['monitor_info'];

		// ecshop.order_goods_attribute
		$sql="SELECT oga.* 
			FROM ecshop.ecs_order_info eoi
			INNER JOIN ecshop.ecs_order_goods eog ON eog.order_id = eoi.order_id 
			INNER JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = eog.rec_id
			WHERE eoi.order_sn='{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【百度MALL】订单商品属性表[ecshop.order_goods_attribute]',$sql,'order_goods_attribute_id');
		$baidumall_order_dates[] = $result['monitor_info'];

		// ecshop.ecs_baidumall_order_mapping
		$sql="SELECT ebom.* FROM ecshop.ecs_order_info eoi
			INNER JOIN  ecshop.ecs_order_mapping ebom
			ON ebom.erp_order_id = eoi.order_id 
			WHERE eoi.order_sn= '{$order_sn}'";//5071684826
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【百度MALL】百度MALL订单映射表[ecshop.ecs_order_mapping]',$sql,'mapping_id');
		$baidumall_order_dates[] = $result['monitor_info'];


	return $baidumall_order_dates;
}

?>