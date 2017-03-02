<?php
define('IN_ECS',true);
require('../includes/init.php');
require_once('monitor_tools.php');

$monitor_header = new MonitorHeader("惠氏订单监控页",array('order_sn','taobao_order_sn'));
$smarty -> assign('monitor_header',$monitor_header);

// $order_sn=trim($_REQUEST['order_sn']);
if(empty($_REQUEST['order_sn'])&&empty($_REQUEST['taobao_order_sn'])){
	$smarty->assign('msg','【惠氏】请输入taobao_order_sn或order_sn');
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
		$smarty->assign('monitor_data',getWyethOrderInfo($order_sn,$taobao_order_sn));
	}
}
$smarty->display('SinriTest/common_monitor.htm');

function getWyethOrderInfo($order_sn,$taobao_order_sn){
		if($order_sn!==null&&$order_sn!==''){
			// ecshop.sync_weixin_wyeth_info
			$sql="SELECT swwi.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_weixin_wyeth_info swwi
					ON swwi.weixin_order_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn = '{$order_sn}'";//6746370694
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【惠氏】惠氏订单中间表[ecshop.sync_weixin_wyeth_info]',$sql,'weixin_wyeth_id');
			$wyeth_order_dates[] = $result['monitor_info'];

			// ecshop.sync_weixin_wyeth_goods
			$sql="SELECT swwg.* FROM ecshop.ecs_order_info eoi 
					INNER JOIN ecshop.sync_weixin_wyeth_goods swwg
					ON swwg.weixin_order_id = eoi.taobao_order_sn 
					WHERE eoi.order_sn='{$order_sn}'";//1560002937
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【惠氏】惠氏订单商品中间表[ecshop.sync_weixin_wyeth_goods]',$sql,'wyeth_goods_id');
			$wyeth_order_dates[] = $result['monitor_info'];

		}elseif($taobao_order_sn!==null &&($order_sn==null||$order_sn=''){
			// ecshop.sync_weixin_wyeth_info
			$sql="SELECT swwi.* FROM ecshop.sync_weixin_wyeth_info swwi
					WHERE swwi.weixin_order_id = '{taobao_order_sn}'";//6746370694
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【惠氏】惠氏订单中间表[ecshop.sync_weixin_wyeth_info]',$sql,'weixin_wyeth_id');
			$wyeth_order_dates[] = $result['monitor_info'];

			// ecshop.sync_weixin_wyeth_goods
			$sql="SELECT swwg.* FROM ecshop.sync_weixin_wyeth_goods swwg
					WHERE swwg.weixin_order_id = '{taobao_order_sn}'";//1560002937
			$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【惠氏】惠氏订单商品中间表[ecshop.sync_weixin_wyeth_goods]',$sql,'wyeth_goods_id');
			$wyeth_order_dates[] = $result['monitor_info'];
		}

		// ecshop.ecs_order_info
		$sql = "SELECT * FROM ecshop.ecs_order_info WHERE order_sn='{$order_sn}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【惠氏订单】总信息表[ecshop.ecs_order_info]', $sql, 'order_id');
		$wyeth_order_dates[] = $result['monitor_info'];

		// ecshop.order_attribute
		$sql="SELECT oa.* FROM  ecshop.ecs_order_info eoi
				INNER JOIN ecshop.order_attribute oa
				ON oa.order_id = eoi.order_id 
				WHERE eoi.order_sn='{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【惠氏】惠氏订单属性表[ecshop.order_attribute]',$sql,'attribute_id');
		$wyeth_order_dates[] = $result['monitor_info'];

		// ecshop.ecs_order_goods
		$sql="SELECT eog.* FROM ecshop.ecs_order_info eoi
				INNER JOIN  ecshop.ecs_order_goods eog
				ON eog.order_id = eoi.order_id 
				WHERE eoi.order_sn = '{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【惠氏】惠氏订单商品表[ecshop.ecs_order_goods]',$sql,'rec_id');
		$wyeth_order_dates[] = $result['monitor_info'];

		// ecshop.order_goods_attribute
		$sql="SELECT oga.* 
			FROM ecshop.ecs_order_info eoi
			INNER JOIN ecshop.ecs_order_goods eog ON eog.order_id = eoi.order_id 
			INNER JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = eog.rec_id
			WHERE eoi.order_sn='{$order_sn}'";
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【惠氏】订单商品属性表[ecshop.order_goods_attribute]',$sql,'order_goods_attribute_id');
		$wyeth_order_dates[] = $result['monitor_info'];

		// ecshop.ecs_qishu_order_mapping
		$sql="SELECT eqom.* FROM ecshop.ecs_order_info eoi
			INNER JOIN  ecshop.ecs_qishu_order_mapping eqom
			ON eqom.order_id = eoi.order_id 
			WHERE eoi.order_sn= '{$order_sn}'";//3171827294
		$result=GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'【惠氏】惠氏订单映射表[ecshop.ecs_qishu_order_mapping]',$sql,'mapping_id');
		$wyeth_order_dates[] = $result['monitor_info'];


	return $wyeth_order_dates;
}

?>