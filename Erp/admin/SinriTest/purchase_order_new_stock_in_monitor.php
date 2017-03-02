<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');



$monitor_header = new MonitorHeader(
	"采购订单收货入库[新流程]监控页",
	array('batch_order_sn', 'location_barcode', 'goods_barcode'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['batch_order_sn'])){
	$smarty->assign('msg', '请输入batch_order_sn');
}else
{
	$sql = "SELECT boi.facility_id, eog.order_id, p.product_id, p.goods_id, p.style_id
			FROM ecshop.ecs_batch_order_info boi
				LEFT JOIN ecshop.ecs_batch_order_mapping bom on bom.batch_order_id = boi.batch_order_id
				LEFT JOIN ecshop.ecs_order_goods eog on eog.order_id = bom.order_id
				NATURAL JOIN (select pm.product_id, g.goods_id, if(gs.style_id is null, 0, gs.style_id) as style_id
					from ecshop.ecs_goods g
						left join ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
						left join romeo.product_mapping pm ON g.goods_id = pm.ecs_goods_id and ifnull(gs.style_id,0)=pm.ecs_style_id
					where if(gs.barcode is not null and gs.barcode !='',gs.barcode,g.barcode) = '{$_REQUEST['goods_barcode']}') p
			where boi.batch_order_sn = '{$_REQUEST['batch_order_sn']}'";
	$result = $db->getRow($sql);
	$sql = "SELECT location_id from romeo.location where location_barcode = '{$_REQUEST['location_barcode']}'";
	$location_id = $db->getOne($sql);
	if(empty($result)){
		$smarty->assign('msg', 'batch_order_sn或goods_barcode输入错误');
	}else if (empty($location_id)){
		$smarty->assign('msg', 'location_barcode输入错误');
	}
	else{
		$smarty->assign('monitor_data', GenerateMonitorInfo($result['facility_id'], $result['order_id'], $location_id, $result['product_id'], $result['goods_id'], $result['style_id']));
	}
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateMonitorInfo($facility_id, $order_id, $location_id, $product_id, $goods_id, $style_id){
	global $db;
	//订单相关
	//ecshop.ecs_batch_order_mapping
	$sql = "SELECT * 
			from ecshop.ecs_batch_order_mapping 
			where order_id = {$order_id}";
	$bom_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单映射关系表[ecshop.ecs_batch_order_mapping]', $sql, 'order_id', array('batch_order_id'));
	$monitor_info_for_generate[] = $bom_result['monitor_info'];
	//ecshop.ecs_batch_order_info
	$sql = "SELECT batch_order_id, batch_order_sn, party_id, facility_id, order_time, in_time, in_storage_user, is_cancelled, is_over_c, 
				is_in_storage
			from ecshop.ecs_batch_order_info
			where batch_order_id in ({$bom_result['query_info']['batch_order_id']})";
	$boi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'批次订单信息表[ecshop.ecs_batch_order_info]', $sql, 'batch_order_id');
	$monitor_info_for_generate[] = $boi_result['monitor_info'];
	//ecshop.ecs_order_goods
	$sql = "SELECT rec_id, order_id, goods_id, style_id, goods_name, goods_sn, goods_number,status_id
			from ecshop.ecs_order_goods
			where order_id = {$order_id} and goods_id = {$goods_id} and style_id = {$style_id}";
	$boi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id', array('rec_id'));
	$monitor_info_for_generate[] = $boi_result['monitor_info'];

	//库存相关
	//romeo.inventory_item_detail
	$sql = "SELECT INVENTORY_ITEM_DETAIL_ID, ORDER_ID, ORDER_GOODS_ID, CANCELLATION_FLAG, INVENTORY_ITEM_ID, INVENTORY_TRANSACTION_ID,QUANTITY_ON_HAND_DIFF, AVAILABLE_TO_PROMISE_DIFF, CREATED_STAMP, LAST_UPDATED_STAMP
		from romeo.inventory_item_detail
		where order_id = {$order_id} and order_goods_id in ({$boi_result['query_info']['rec_id']})";
	$inv_it_d_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存记录明细表[romeo.inventory_item_detail]', $sql, 'INVENTORY_ITEM_DETAIL_ID', array('INVENTORY_ITEM_DETAIL_ID','INVENTORY_ITEM_ID', 'INVENTORY_TRANSACTION_ID'));
	$monitor_info_for_generate[] = $inv_it_d_result['monitor_info'];
	if(!empty($inv_it_d_result['query_info']['INVENTORY_ITEM_ID'])){
		//romeo.inventory_item
		$sql = "SELECT * 
			from romeo.inventory_item
			where inventory_item_id in ({$inv_it_d_result['query_info']['INVENTORY_ITEM_ID']})";
		$inv_it_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存记录表[romeo.inventory_item]', $sql, 'INVENTORY_ITEM_ID');
		$monitor_info_for_generate[] = $inv_it_result['monitor_info'];
	}
	if(!empty($inv_it_d_result['query_info']['INVENTORY_TRANSACTION_ID']) and !empty($inv_it_d_result['query_info']['INVENTORY_ITEM_ID'])){
		//romeo.inventory_transaction
		$sql = "SELECT * 
			from romeo.inventory_transaction
			where inventory_transaction_id in ({$inv_it_d_result['query_info']['INVENTORY_TRANSACTION_ID']})
				and to_inventory_item_id in ({$inv_it_d_result['query_info']['INVENTORY_ITEM_ID']})";
		$inv_tran_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存转移记录表[romeo.inventory_transaction]', $sql, 'INVENTORY_TRANSACTION_ID');
		$monitor_info_for_generate[] = $inv_tran_result['monitor_info'];
	}

	//romeo.inventory_summary
	$sql = "SELECT * 
		from romeo.inventory_summary
		where facility_id = '{$facility_id}' and STATUS_ID = 'INV_STTS_AVAILABLE' and product_id = {$product_id}";
	$inv_sum_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存汇总表[romeo.inventory_summary]', $sql, 'INVENTORY_SUMMARY_ID');
	$monitor_info_for_generate[] = $inv_sum_result['monitor_info'];


	//容器相关
	//romeo.inventory_location
	$sql = "SELECT *
			from romeo.inventory_location
			where facility_id = '{$facility_id}' 
			and location_id = '{$location_id}'
			and product_id = '{$product_id}'";
	$inv_loc_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'容器商品表[romeo.inventory_location]', $sql, 'inventory_location_id', array('inventory_location_id'));
	$monitor_info_for_generate[] = $inv_loc_result['monitor_info'];
	if(!empty($inv_loc_result['query_info']['inventory_location_id'])){
		//romeo.inventory_location_detail
		$sql = "SELECT * 
			from romeo.inventory_location_detail
			where order_id = '{$order_id}' 
			and inventory_location_id in({$inv_loc_result['query_info']['inventory_location_id']})
			and action_type = 'RECEIVE' and product_id = {$product_id}";
		$inv_loc_d_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'容器转移记录表[romeo.inventory_location_detail]', $sql, 'inventory_location_id');
		$monitor_info_for_generate[] = $inv_loc_d_result['monitor_info'];	
	}
	return $monitor_info_for_generate;
}
?>