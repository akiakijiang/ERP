<?php
/*
 * Created on 2014-2-21 by zjli
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
 define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("退换货服务申请、审核情况监控页",array('order_id','order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['order_id']) && empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入order_id或order_sn');
}else{
	if(!empty($_REQUEST['order_id'])){
		$order_id = $_REQUEST['order_id'];
	}else{
		$sql = "select order_id from ecshop.ecs_order_info where order_sn = '{$_REQUEST['order_sn']}'";
		$order_id = $db->getOne($sql);
	}
	$sql = "select count(*) from ecshop.ecs_order_info where order_id = '{$order_id}'";
	$order_count = $db->getOne($sql);
	if(empty($order_count) || $order_count <= 0){
		$smarty->assign('msg', '请输入正确的order_id或order_sn!');
	}else{
		$smarty->assign('monitor_data', GenerateServiceInfo($order_id));	
	}
}
$smarty->display('SinriTest/common_monitor.htm');

function GenerateServiceInfo($order_id){
	// 订单相关
	// ecshop.ecs_order_info
	$sql = "select order_id, order_sn, goods_amount, shipping_fee, pack_fee, bonus, order_amount, real_paid from ecshop.ecs_order_info where order_id  = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【销售订单】订单信息表[ecshop.ecs_order_info]', $sql, 'order_id');
	$service_for_generate[] = $result['monitor_info'];
	
	// ecshop.ecs_order_goods
	$sql = "select * from ecshop.ecs_order_goods where order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【销售订单】订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id');
	$service_for_generate[] = $result['monitor_info'];
	
	// ecshop.order_attribute
	$sql = "select * from ecshop.order_attribute where order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【销售订单】订单属性表[ecshop.order_attribute]', $sql, 'attribute_id');
	$service_for_generate[] = $result['monitor_info'];
	
	// ecshop.order_goods_attribute
	$sql = "select oga.* from ecshop.ecs_order_info oi 
			inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id
			inner join ecshop.order_goods_attribute oga on oga.order_goods_id = og.rec_id
			where oi.order_id  = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【销售订单】订单商品属性表[ecshop.order_goods_attribute]', $sql, 'order_goods_attribute_id');
	$service_for_generate[] = $result['monitor_info'];
	
	//die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
	// $sql = "SELECT * FROM membership.ok_gift_ticket WHERE refer_id = '{$order_id}'";
	$sql = "SELECT '是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)' ";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【销售订单】红包[membership.ok_gift_ticket]', $sql, 'refer_id');
	$service_for_generate[] = $result['monitor_info'];
	
	// ecshop.service
	$sql = "select service_id, order_id, facility_id, apply_reason, apply_datetime,
			       service_type, service_status, service_call_status, back_shipping_status,
			       inner_check_status, back_order_id, change_order_id, is_complete, service_amount
			from ecshop.service
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'售后服务表[ecshop.service]', $sql, 'service_id');
	$service_for_generate[] = $result['monitor_info'];
	
	// ecshop.service_order_goods
	$sql = "select sog.service_order_goods_id, sog.service_id, sog.order_id, sog.user_id,
				   sog.order_goods_id, approve_datetime, is_approved, amount
			from ecshop.service_order_goods sog
			inner join ecshop.service s on s.service_id = sog.service_id
			where s.order_id = '$order_id'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'售后服务商品表[ecshop.service_order_goods]', $sql, 'service_order_goods_id');
	$service_for_generate[] = $result['monitor_info'];
	
	// ecshop.service_return
	$sql = "select sr.* from ecshop.service_return sr
			inner join ecshop.service s on s.service_id = sr.service_id
			where s.order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'售后服务反馈表[ecshop.service_return]', $sql, 'service_return_id');
	$service_for_generate[] = $result['monitor_info'];
	
	// ecshop.service_log
	$sql = "select sl.service_log_id, sl.service_id, sl.service_status, sl.type_name,
				   sl.status_name, sl.log_username, sl.log_note, sl.log_datetime, sl.log_type
			from ecshop.service_log sl
			inner join ecshop.service s on s.service_id = sl.service_id
			where s.order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'售后服务日志表[ecshop.service_log]', $sql, 'service_log_id');
	$service_for_generate[] = $result['monitor_info'];
	
	// ecshop.ecs_order_info (-t订单)
	$sql = "select o.order_sn, o.order_id, o.order_time, o.user_id, o.order_status, o.order_amount, o.goods_amount, 
             	   o.shipping_fee, o.pack_fee, o.bonus, o.misc_fee, o.pay_id, o.pay_name, o.shipping_id,
             	   o.shipping_name, o.party_id, o.facility_id, o.distributor_id, o.currency, o.order_type_id
			from ecshop.ecs_order_info o
			inner join ecshop.service s on s.back_order_id = o.order_id
			where s.order_id = '{$order_id}'";
	$res_back_order = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】订单表[ecshop.ecs_order_info]', $sql, 'order_id');
	$service_for_generate[] = $res_back_order['monitor_info'];
	
	if(!empty($res_back_order)){
		// ecshop.order_relation
		$sql = "select orl.* from ecshop.order_relation orl
				inner join ecshop.service s on s.back_order_id = orl.order_id
				where s.order_id = '{$order_id}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】订单关系表[ecshop.order_relation]', $sql, 'order_relation_id');
		$service_for_generate[] = $result['monitor_info'];
		
		// ecshop.ecs_order_info (-t订单)
		$sql = "select o.order_id, o.order_sn, o.goods_amount, o.shipping_fee, o.pack_fee, o.bonus, o.order_amount
			from ecshop.ecs_order_info o
			inner join ecshop.service s on s.back_order_id = o.order_id
			where s.order_id = '{$order_id}'";
		$res_back_order = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】订单表[ecshop.ecs_order_info]', $sql, 'order_id');
		$service_for_generate[] = $res_back_order['monitor_info'];
		
		// ecshop.ecs_order_goods (-t订单)
		$sql = "select og.rec_id, og.order_id, og.goods_id, og.style_id, og.goods_name,
				       og.goods_number, og.market_price, og.goods_price, og.customized,
				       og.status_id, og.added_fee
				from ecshop.ecs_order_goods og
				inner join ecshop.service s on s.back_order_id = og.order_id
				where s.order_id = '{$order_id}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id');
		$service_for_generate[] = $result['monitor_info'];
		
		// ecshop.order_attribute
		$sql = "select oa.* from ecshop.order_attribute oa
				inner join ecshop.service s on oa.order_id = s.back_order_id
				where s.order_id = '{$order_id}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】订单级别折扣表[ecshop.order_attribute]', $sql, 'attribute_id');
		$service_for_generate[] = $result['monitor_info'];
		
		// ecshop.order_goods_attribute
		$sql = "select oga.*
				from ecshop.ecs_order_goods og
			    inner join ecshop.service s on s.back_order_id = og.order_id
				inner join ecshop.order_goods_attribute oga on og.rec_id = oga.order_goods_id
				where s.order_id = '{$order_id}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】商品级别折扣表[ecshop.order_goods_attribute]', $sql, 'order_goods_attribute_id');
		$service_for_generate[] = $result['monitor_info'];
	}
	
	return $service_for_generate;
}
?>
