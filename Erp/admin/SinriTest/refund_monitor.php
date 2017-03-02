<?php
/*
 * Created on 2014-4-21
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("退款操作信息监控页",array('order_id', 'order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['order_id']) && empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入order_sn或order_id');
}else
{
	if(!empty($_REQUEST['order_id'])){
		$order_id = $_REQUEST['order_id'];
	}else{
		$sql = "SELECT order_id from ecshop.ecs_order_info where order_sn = '{$_REQUEST['order_sn']}'";
		$order_id = $db->getOne($sql);
	}
	$smarty->assign('monitor_data', GenerateSupplierFinanceInfo($order_id));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateSupplierFinanceInfo($order_id){
	global $db;

	//ecshop.ecs_batch_order_info
	$sql = "SELECT order_id,order_sn,pay_name,inv_payee,goods_amount,order_amount,bonus,shipping_fee,real_paid,goods_amount,shipping_fee,insure_fee,pay_fee,pack_fee,card_fee
			from ecshop.ecs_order_info 
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'销售订单[ecshop.ecs_order_info]', $sql, 'service_id');
	$return_data[] = $result['monitor_info'];
	
		// ecshop.order_attribute
	$sql = "select * from ecshop.order_attribute where order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【销售订单】订单属性表[ecshop.order_attribute]', $sql, 'attribute_id');
	$return_data[] = $result['monitor_info'];
	
	//die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
	// $sql = "SELECT * FROM membership.ok_gift_ticket WHERE refer_id = '{$order_id}'";
	$sql = "SELECT '是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)' ";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【销售订单】订单属性表[ecshop.order_attribute]', $sql, 'gt_id');
	$return_data[] = $result['monitor_info'];
	// ecshop.order_goods_attribute
	$sql = "select oga.* from ecshop.ecs_order_info oi 
			inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id
			inner join ecshop.order_goods_attribute oga on oga.order_goods_id = og.rec_id
			where oi.order_id  = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【销售订单】订单商品属性表[ecshop.order_goods_attribute]', $sql, 'order_goods_attribute_id');
	$return_data[] = $result['monitor_info'];
	
	//ecshop.ecs_batch_order_info
	$sql = "SELECT *
			from ecshop.back_amount
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'确认退款信息表[ecshop.back_amount]', $sql, 'service_id');
	$return_data[] = $result['monitor_info'];


	//ecshop.ecs_batch_order_info
	$sql = "SELECT service_id,service_amount,service_status,service_call_status,back_shipping_status,inner_check_status,order_id,back_order_id
			from ecshop.service 
			where order_id = {$order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'售后服务信息表[ecshop.service]', $sql, 'service_id');
	$return_data[] = $result['monitor_info'];
	//
	$sql = "select order_id from ecshop.order_relation where parent_order_id = {$order_id}";
	$t_order_id = $db->getOne($sql);
	
	$sql = "SELECT order_id,order_sn,misc_fee,goods_amount,order_amount,bonus,shipping_fee,real_paid,goods_amount,shipping_fee,insure_fee,pay_fee,pack_fee,card_fee
			from ecshop.ecs_order_info
			where order_id = {$t_order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'-t订单的费用[ecshop.ecs_order_info]', $sql, 'order_id');
	$return_data[] = $result['monitor_info'];
	
	$sql = "SELECT *
			from romeo.refund
			where order_id = '{$t_order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'退款订单[romeo.refund]', $sql, 'refund_id');
	$return_data[] = $result['monitor_info'];
	$refund_id = '';

	$sql = "SELECT rd.refund_detail_id,rd.refund_id,rd.product_id,rd.amount,rd.product_amount,rd.order_goods_id,rd.receivable,rd.note
			from romeo.refund_detail rd
					inner join romeo.refund r on rd.refund_id = r.refund_id
			where r.order_id = {$t_order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'退款商品明细[romeo.refund_detail]', $sql, 'refund_detail_id');
	$return_data[] = $result['monitor_info'];

	// ecshop.order_attribute
	$sql = "select oa.* from ecshop.order_attribute oa
			inner join ecshop.service s on oa.order_id = s.back_order_id
			where s.order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】订单级别折扣表[ecshop.order_attribute]', $sql, 'attribute_id');
	$return_data[] = $result['monitor_info'];
	
	// ecshop.order_goods_attribute
	$sql = "select oga.*
			from ecshop.ecs_order_goods og
		    inner join ecshop.service s on s.back_order_id = og.order_id
			inner join ecshop.order_goods_attribute oga on og.rec_id = oga.order_goods_id
			where s.order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】商品级别折扣表[ecshop.order_goods_attribute]', $sql, 'order_goods_attribute_id');
	$return_data[] = $result['monitor_info'];
	return $return_data;
}
?>
