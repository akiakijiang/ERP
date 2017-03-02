<?php
define('IN_ECS',true);
require('../includes/init.php');
require_once('monitor_tools.php');

$monitor_header = new MonitorHeader("人头马订单监控页",array('order_sn','taobao_order_sn'));
$smarty -> assign('monitor_header',$monitor_header);

// $order_sn=trim($_REQUEST['order_sn']);
if(empty($_REQUEST['order_sn'])&&empty($_REQUEST['taobao_order_sn'])){
	$smarty->assign('msg','【人头马】请输入taobao_order_sn或order_sn');
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
		$smarty->assign('monitor_data',getRtmOrderInfo($order_sn,$taobao_order_sn));
	}
}
$smarty->display('SinriTest/common_monitor.htm');

function getRtmOrderInfo($order_sn,$taobao_order_sn){
		if($order_sn!==null&&$order_sn!==''){
			// ecshop.sync_weixin_rtm_order_info
			$sql="SELECT swroi.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_weixin_rtm_order_info swroi
					ON swroi.weixin_order_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn='{$order_sn}'";//1152353257
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【人头马】微信积分商城人头马订单-中间表[ecshop.sync_weixin_rtm_order_info]',$sql,'weixin_order_id');
			$rtm_order_dates[] = $result['monitor_info'];

			// ecshop.sync_weixin_rtm_order_goods
			$sql="SELECT swrog.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_weixin_rtm_order_goods swrog
					ON swrog.weixin_order_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn='{$order_sn}'";//1152353257
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【人头马】微信积分商品人头马订单商品-中间表[ecshop.sync_weixin_rtm_order_goods]',$sql,'id');
			$rtm_order_dates[] = $result['monitor_info'];

		}elseif($taobao_order_sn!==null &&($order_sn==null||$order_sn='')){
			// ecshop.sync_weixin_rtm_order_info
			$sql="SELECT swroi.* FROM ecshop.sync_weixin_rtm_order_info swroi
					WHERE swroi.weixin_order_id = '{taobao_order_sn}' ";//1152353257
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【人头马】微信积分商城人头马订单-中间表[ecshop.sync_weixin_rtm_order_info]',$sql,'weixin_order_id');
			$rtm_order_dates[] = $result['monitor_info'];

			// ecshop.sync_weixin_rtm_order_goods
			$sql="SELECT swrog.* FROM ecshop.sync_weixin_rtm_order_goods swrog
					WHERE swrog.weixin_order_id = '{taobao_order_sn}'";//1152353257
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【人头马】微信积分商品人头马订单商品-中间表[ecshop.sync_weixin_rtm_order_goods]',$sql,'id');
			$rtm_order_dates[] = $result['monitor_info'];
		}

		// ecshop.ecs_order_info
		$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【人头马】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
		$rtm_order_dates[] = $result['monitor_info'];

		// ecshop.order_attribute
		$sql="SELECT oa.* FROM  ecshop.ecs_order_info eoi
				INNER JOIN ecshop.order_attribute oa
				ON oa.order_id = eoi.order_id 
				WHERE eoi.order_sn='{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【人头马】订单属性表[ecshop.order_attribute]',$sql,'attribute_id');
		$rtm_order_dates[] = $result['monitor_info'];

		// ecshop.ecs_order_goods
		$sql="SELECT eog.* FROM ecshop.ecs_order_info eoi
				INNER JOIN  ecshop.ecs_order_goods eog
				ON eog.order_id = eoi.order_id 
				WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【人头马】订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id');
		$rtm_order_dates[] = $result['monitor_info'];

		// ecshop.order_goods_attribute
		$sql="SELECT oga.* 
			FROM ecshop.ecs_order_info eoi
			INNER JOIN ecshop.ecs_order_goods eog ON eog.order_id = eoi.order_id 
			INNER JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = eog.rec_id
			WHERE eoi.order_sn='{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【人头马】订单商品属性表[ecshop.order_goods_attribute]',$sql,'order_goods_attribute_id');
		$rtm_order_dates[] = $result['monitor_info'];

		// ecshop.ecs_weixin_rtm_order_mapping
		$sql="SELECT ewrom.* FROM ecshop.ecs_order_info eoi
			INNER JOIN  ecshop.ecs_weixin_rtm_order_mapping ewrom
			ON ewrom.order_id = eoi.order_id 
			WHERE eoi.order_sn= '{$order_sn}'";//1152353257
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【人头马】微信订单映射表[ecshop.ecs_weixin_rtm_order_mapping]',$sql,'mapping_id');
		$rtm_order_dates[] = $result['monitor_info'];


	return $rtm_order_dates;
}

?>