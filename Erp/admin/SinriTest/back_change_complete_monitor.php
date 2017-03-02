<?php
/*
 * Created on 2014-2-28
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("退换货完结（退款、-h订单生成）监控页",array('service_id'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['service_id'])){
	$smarty->assign('msg', '请输入service_id');
}else
{
	$service_id = $_REQUEST['service_id'];
	$sql = "select * from ecshop.service where service_id = '{$service_id}'";
	$result = $db->getRow($sql);
	if(empty($result)){
		$smarty->assign('msg', 'service_id输入错误');
	}else{
		$smarty->assign('monitor_data', GenerateChangeOrderInfo($result['service_id'], $result['back_order_id'], $result['change_order_id']));
	}
}
$smarty->display('SinriTest/common_monitor.htm');

function GenerateChangeOrderInfo($service_id, $back_order_id, $change_order_id){
	global $db;
	// 订单相关
	// ecshop.service
	$sql = "select service_id, order_id, facility_id, apply_datetime,
			       service_type, service_status, service_call_status, back_shipping_status,
			       inner_check_status, back_order_id, change_order_id, is_complete, service_amount
			from ecshop.service
			where service_id = {$service_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'售后服务表[ecshop.service]', $sql, 'service_id');
	$change_info_for_generate[] = $result['monitor_info'];
	
	// ecshop.service_log
	$sql = "select service_log_id, service_id, service_status, type_name,
				   status_name, log_username, log_note, log_datetime, log_type
			from ecshop.service_log where service_id = '{$service_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('售后服务日志表[ecshop.service_log]', $sql, 'service_log_id');
	$change_info_for_generate[] = $result['monitor_info'];
	
	// 退货服务相关
	// ecshop.back_amount
	$sql = "select back_amount_id, service_id, order_id, apply_amount, apply_datetime, back_amount_reason, apply_note, apply_username, back_detail
			from ecshop.back_amount
			where service_id={$service_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('退款记录表[ecshop.back_amount]', $sql, 'back_amount_id');
	$change_info_for_generate[] = $result['monitor_info'];
	
	// ecshop.ecs_order_info (-t订单)
	$sql = "select order_sn, order_id, order_time, user_id, order_status, order_amount, goods_amount, 
             	   shipping_fee, pack_fee, bonus, misc_fee, pay_id, pay_name, shipping_id,
             	   shipping_name, party_id, facility_id, distributor_id, currency, order_type_id
			from ecshop.ecs_order_info
			where order_id = '{$back_order_id}'";
	$res_back_order = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】订单表[ecshop.ecs_order_info]', $sql, 'order_id');
	$change_info_for_generate[] = $res_back_order['monitor_info'];
	
	// 换货服务相关
	// ecshop.ecs_order_info (-h订单)
	$sql = "select order_sn, order_id, order_time, user_id, order_status, order_amount, goods_amount, 
             	   shipping_fee, pack_fee, bonus, misc_fee, pay_id, pay_name, shipping_id,
             	   shipping_name, party_id, facility_id, distributor_id, currency, order_type_id
			from ecshop.ecs_order_info
			where order_id = '{$change_order_id}'";
	$res_change_order =GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【换货(-h)订单】订单表[ecshop.ecs_order_info]', $sql, 'order_id');
	$change_info_for_generate[] = $res_change_order['monitor_info'];
	
	// ecshop.order_relation
	$sql = "select * from ecshop.order_relation
			where order_id = '{$change_order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【换货(-h)订单】订单关系表[ecshop.order_relation]', $sql, 'order_relation_id');
	$change_info_for_generate[] = $result['monitor_info'];
	
	// ecshop.ecs_order_goods (-h订单)
	$sql = "select og.rec_id, og.order_id, og.goods_id, og.style_id, og.goods_name,
			       og.goods_number, og.market_price, og.goods_price, og.customized,
			       og.status_id, og.added_fee
			from ecshop.ecs_order_goods og
			where order_id = '{$change_order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【换货(-h)订单】订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id');
	$change_info_for_generate[] = $result['monitor_info'];
	
	// romeo.shippment (-h订单)
	$sql = "select *
			from romeo.shipment
			where primary_order_id = '{$change_order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【换货(-h)订单】发货单表[romeo.shipment]', $sql, 'SHIPMENT_ID');
	$change_info_for_generate[] = $result['monitor_info'];
	
	// romeo.order_shipment
	$sql = "select *
			from romeo.order_shipment
			where order_id = '{$change_order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【换货(-h)订单】订单-发货单关系表[romeo.order_shipment]', $sql, 'SHIPMENT_ID');
	$change_info_for_generate[] = $result['monitor_info'];
	
	return $change_info_for_generate;
}

?>
