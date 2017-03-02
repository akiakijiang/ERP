<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$input_param_name_list = array();
$monitor_header = new MonitorHeader("这是标题", $input_param_name_list);
$smarty->assign('monitor_header', $monitor_header);

if(CheckInputParams()){
	$smarty->assign('monitor_data', GenerateDataForMonitor(...));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateDataForMonitor(...){
	global $db;

	//ecshop.ecs_batch_order_info
	$sql = "SELECT *
			from ecshop.ecs_batch_order_info
			where batch_order_id = {$batch_order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'批次订单信息表[ecshop.ecs_batch_order_info]', $sql, 'batch_order_id');
	$purchase_info_for_generate[] = $result['monitor_info'];

	//ecshop.ecs_batch_order_mapping
	$sql = "SELECT *
			from ecshop.ecs_batch_order_mapping 
			where batch_order_id = {$batch_order_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单映射关系表[ecshop.ecs_batch_order_mapping]', $sql, 'order_id', array('order_id'));
	$order_ids_str = $result['query_info']['order_id'];
	$purchase_info_for_generate[] = $result['monitor_info'];

	//ecshop.ecs_order_info and romeo.purchase_order_info
	$sql = "SELECT eoi.order_id, order_sn, party_id, facility_id, user_id, order_time, order_status, pay_status, order_type_id, currency,
				is_purchase_paid,  purchase_paid_type, purchase_paid_amount, purchase_paid_time, purchaser, purchase_invoice, 
				order_type, is_serial
			from ecshop.ecs_order_info eoi
				LEFT JOIN romeo.purchase_order_info poi on poi.order_id = eoi.order_id
			where eoi.order_id in ({$order_ids_str})";
	$order_info_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'主订单表[ecshop.ecs_order_info]和采购订单表[romeo.purchase_order_info]', $sql, 'order_id');
	$purchase_info_for_generate[] = $order_info_result['monitor_info'];

	//订单额外属性表
	$sql = "SELECT * from ecshop.order_attribute
			where order_id in ({$order_ids_str})";
	$order_attribute_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单额外属性表[ecshop.order_attribute]', $sql, 'attribute_id');
	$purchase_info_for_generate[] = $order_attribute_result['monitor_info'];

	//订单商品
	$sql = "SELECT og.rec_id, og.order_id, og.goods_id, og.goods_name, og.goods_number, og.goods_price, og.style_id, og.added_fee,eg.barcode as goods_barcode,egs.barcode as style_barcode
			from ecshop.ecs_order_goods og
					left join ecshop.ecs_goods eg on og.goods_id = eg.goods_id
					left join ecshop.ecs_goods_style egs on egs.goods_id = og.goods_id and 
							egs.style_id = og.style_id and egs.is_delete=0
			where order_id in ({$order_ids_str})";
	$order_goods_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id', array('goods_id'));
	$purchase_info_for_generate[] = $order_goods_result['monitor_info'];

	//返利信息
	$sql = "SELECT * FROM ecshop.purchase_order_applied_rebate where order_id in ({$order_ids_str})";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单返利信息表[ecshop.purchase_order_applied_rebate]', $sql, 'order_id');
	$purchase_info_for_generate[] = $result['monitor_info'];


	// 特殊表格
	// 金宝贝出入库单表
	if(!empty($order_attribute_result['monitor_info']['item_list'])){
		$gymboree_vouchID = '';
		foreach ($order_attribute_result['monitor_info']['item_list'] as $order_attribute_record) {
			# code...
			if($order_attribute_record['attr_name'] == 'gymboree_vouchID'){
				$gymboree_vouchID = $order_attribute_record['attr_value'];
				break;
			}
		}
		if($gymboree_vouchID != ''){
			$sql = "SELECT *
					from ecshop.brand_gymboree_inoutvouch
					where fchrInOutVouchID = '{$gymboree_vouchID}'";
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'金宝贝出入库单表[ecshop.brand_gymboree_inoutvouch]', $sql, 'brand_gymboree_inoutvouch_id');
			$purchase_info_for_generate[] = $result['monitor_info'];
		}
	}
		//库存相关
	//romeo.inventory_item_detail
	$sql = "SELECT INVENTORY_ITEM_DETAIL_ID, ORDER_ID, ORDER_GOODS_ID, CANCELLATION_FLAG, INVENTORY_ITEM_ID, INVENTORY_TRANSACTION_ID,QUANTITY_ON_HAND_DIFF, AVAILABLE_TO_PROMISE_DIFF, CREATED_STAMP, LAST_UPDATED_STAMP
		from romeo.inventory_item_detail
		where order_id in ({$order_ids_str})";
	$inv_it_d_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存记录明细表[romeo.inventory_item_detail]', $sql, 'INVENTORY_ITEM_DETAIL_ID', array('INVENTORY_ITEM_DETAIL_ID','INVENTORY_ITEM_ID', 'INVENTORY_TRANSACTION_ID'));
	$purchase_info_for_generate[] = $inv_it_d_result['monitor_info'];
	if(!empty($inv_it_d_result['query_info']['INVENTORY_ITEM_ID'])){
		//romeo.inventory_item
		$sql = "SELECT * 
			from romeo.inventory_item
			where inventory_item_id in ({$inv_it_d_result['query_info']['INVENTORY_ITEM_ID']})";
		$inv_it_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存记录表[romeo.inventory_item]', $sql, 'INVENTORY_ITEM_ID');
		$purchase_info_for_generate[] = $inv_it_result['monitor_info'];
	}
	return $purchase_info_for_generate;
}
?>