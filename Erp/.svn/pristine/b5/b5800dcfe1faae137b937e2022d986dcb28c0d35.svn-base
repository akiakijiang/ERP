<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("订单详情页操作监控",array('order_id'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['order_id'])){
	$smarty->assign('msg', '请输入order_id');
}else
{
	$smarty->assign('monitor_data', GenerateOrderInfo($_REQUEST['order_id']));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateOrderInfo($order_id){
	global $db;
	$order_info_for_generate = array();

	//订单头
	$sql = "select order_id, eoi.order_sn, eoi.party_id, p.name as party_name, eoi.order_status, eoi.shipping_status, eoi.pay_status,
		order_time, confirm_time, pay_time, shipping_time 
		from ecshop.ecs_order_info eoi
		left join romeo.party p on eoi.party_id = p.party_id
		where eoi.order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单头[ecshop.ecs_order_info]', $sql, 'order_id');
	$order_info_for_generate[] = $result['monitor_info'];

	//ecshop.ecs_order_info
	$sql = "SELECT order_id, taobao_order_sn, distributor_id
			from ecshop.ecs_order_info
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单平台信息1[ecshop.ecs_order_info]', $sql, 'order_id');
	$order_info_for_generate[] = $result['monitor_info'];

	//订单额外属性表
	$sql = "SELECT * from ecshop.order_attribute
			where order_id = {$order_id} and attr_name in ('TAOBAO_POINT_FEE', 'TAOBAO_USER_ID', 'OUTER_TYPE', 'SUB_OUTER_TYPE')";
	$order_attribute_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单平台信息2[ecshop.order_attribute]', $sql, 'attribute_id');
	$order_info_for_generate[] = $order_attribute_result['monitor_info'];

	//收货人信息
	$sql = "select user_id, consignee, sex, tel, mobile, email, zipcode, country, province, city, district, address,
	is_shortage_await,postscript
	from ecshop.ecs_order_info eoi
	where eoi.order_id = '{$order_id}'";
	$order_attribute_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'收货人信息[ecshop.ecs_order_info]', $sql, 'order_id');
	$order_info_for_generate[] = $order_attribute_result['monitor_info'];

	//支付方式
	$sql = "select eoi.pay_id, ep.pay_name
			from ecshop.ecs_order_info eoi
			left join ecshop.ecs_payment ep on eoi.pay_id = ep.pay_id
			where eoi.order_id = '{$order_id}'";
	$order_attribute_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'支付方式', $sql, 'order_id');
	$order_info_for_generate[] = $order_attribute_result['monitor_info'];

	//快递信息
	$sql = "select eoi.shipping_id, es.shipping_name, f.facility_id, facility_name
			from ecshop.ecs_order_info eoi
			left join romeo.facility f on f.facility_id = eoi.facility_id
			left join ecshop.ecs_shipping es on eoi.shipping_id = es.shipping_id
			where eoi.order_id = '{$order_id}'";
	$order_attribute_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'快递方式', $sql, 'order_id');
	$order_info_for_generate[] = $order_attribute_result['monitor_info'];

	//订单商品
	$sql = "SELECT eog.rec_id, eog.goods_id, eog.style_id, goods_name, es.color, goods_price, goods_number, goods_number * goods_price AS total_price, 
			if(oir.STATUS='Y', '是', '否') as reserve_status,
				sum(s.AVAILABLE_TO_RESERVED) as AVAILABLE_TO_RESERVED
			from ecshop.ecs_order_goods eog
			LEFT JOIN ecshop.ecs_order_info eoi on eog.order_id = eoi.order_id
			LEFT JOIN ecshop.ecs_style es on eog.style_id = es.style_id
			LEFT JOIN romeo.order_inv_reserved oir on eog.order_id = oir.ORDER_ID
			LEFT JOIN romeo.product_mapping pm on eog.goods_id = pm.ECS_GOODS_ID and eog.style_id = pm.ECS_STYLE_ID
			LEFT JOIN romeo.inventory_summary s on pm.product_id = s.product_id and s.STATUS_ID = eog.status_id and s.facility_id = eoi.facility_id
			where eog.order_id = {$order_id} and s.STATUS_ID = 'INV_STTS_AVAILABLE' and s.AVAILABLE_TO_RESERVED > 0
			group by eog.rec_id";
	$order_goods_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单商品', $sql, 'rec_id');
	$order_info_for_generate[] = $order_goods_result['monitor_info'];

	//订单操作
	$sql = "SELECT *
			from ecshop.ecs_order_action
			where order_id = ({$order_id})";
	$order_goods_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单操作表[ecshop.ecs_order_action]', $sql, 'action_id');
	$order_info_for_generate[] = $order_goods_result['monitor_info'];

	//订单状态历史
	$sql = "SELECT *
			from ecshop.order_mixed_status_history
			where order_id = ({$order_id})";
	$order_goods_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单状态历史表[ecshop.order_mixed_status_history]', $sql, 'order_mixed_status_history_id');
	$order_info_for_generate[] = $order_goods_result['monitor_info'];




	return $order_info_for_generate;
}
?>