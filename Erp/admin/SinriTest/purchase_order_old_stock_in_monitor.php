<?php
define('IN_ECS', true);
require('../includes/init.php');
require('../function.php');

require_once ('monitor_tools.php');



$monitor_header = new MonitorHeader(
	"采购订单收货入库[老流程]监控页",
	array('batch_order_id','batch_order_sn','order_id', 'order_sn'));
$smarty->assign('monitor_header', $monitor_header);

$cond = '1';

if(!empty($_REQUEST['batch_order_id'])) {
	$cond .= " and boi.batch_order_id='".$_REQUEST['batch_order_id']."' ";
}
if(!empty($_REQUEST['batch_order_sn'])) {
	$cond .= " and boi.batch_order_sn='".$_REQUEST['batch_order_sn']."' ";
}
if(!empty($_REQUEST['order_id'])) {
	$cond .= " and oi.order_id='".$_REQUEST['order_id']."' ";
}
if(!empty($_REQUEST['order_sn'])) {
	$cond .= " and oi.order_sn='".$_REQUEST['order_sn']."' ";
}

if('1' == $cond){
	$smarty->assign('msg', '请输入order_id或order_sn或batch_order_id或batch_order_sn');
}else
{
	$sql = "SELECT oi.facility_id, boi.batch_order_id,group_concat(pm.product_id) as product_ids
			FROM ecshop.ecs_order_info oi
				INNER JOIN ecshop.ecs_order_goods og ON og.order_id = oi.order_id
				LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
				INNER JOIN ecshop.ecs_batch_order_mapping bom ON oi.order_id = bom.order_id
				INNER JOIN ecshop.ecs_batch_order_info boi ON bom.batch_order_id = boi.batch_order_id
			where $cond group by boi.batch_order_id";
	$result = $db->getRow($sql);

	if(empty($result)){
		$smarty->assign('msg', 'order_id或order_sn或batch_order_id或batch_order_sn输入错误');
	}
	else{
		$smarty->assign('monitor_data', GenerateMonitorInfo($result['facility_id'], $result['batch_order_id'], $result['product_ids']));
	}
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateMonitorInfo($facility_id, $batch_order_id, $product_ids){
	$product_ids = explode(',',$product_ids);
	global $db;
	//订单相关
	//ecshop.ecs_batch_order_mapping
	$sql = "SELECT * 
			from ecshop.ecs_batch_order_mapping 
			where batch_order_id = {$batch_order_id}";
	$bom_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单映射关系表[ecshop.ecs_batch_order_mapping]', $sql, 'order_id', array('order_id'));
	$monitor_info_for_generate[] = $bom_result['monitor_info'];
	$order_ids  = $bom_result['query_info']['order_id'];
	
	//ecshop.ecs_batch_order_info
	$sql = "SELECT batch_order_id, batch_order_sn, party_id, facility_id, order_time, in_time, in_storage_user, is_cancelled, is_over_c, 
				is_in_storage
			from ecshop.ecs_batch_order_info
			where batch_order_id in ({$batch_order_id})";
	$boi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'批次订单信息表[ecshop.ecs_batch_order_info]', $sql, 'batch_order_id');
	$monitor_info_for_generate[] = $boi_result['monitor_info'];
	//ecshop.ecs_order_goods
	$sql = "SELECT rec_id, order_id, goods_id, style_id, goods_name, goods_sn, goods_number,status_id
			from ecshop.ecs_order_goods
			where order_id in ({$order_ids})";
	$boi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id', array('rec_id'));
	$monitor_info_for_generate[] = $boi_result['monitor_info'];

	//库存相关
	//romeo.inventory_item_detail
	$sql = "SELECT INVENTORY_ITEM_DETAIL_ID, ORDER_ID, ORDER_GOODS_ID, CANCELLATION_FLAG, INVENTORY_ITEM_ID, INVENTORY_TRANSACTION_ID,QUANTITY_ON_HAND_DIFF, AVAILABLE_TO_PROMISE_DIFF, CREATED_STAMP, LAST_UPDATED_STAMP
		from romeo.inventory_item_detail
		where order_id in ({$order_ids}) order by created_stamp desc";
	$inv_it_d_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存记录明细表[romeo.inventory_item_detail]', $sql, 'INVENTORY_ITEM_DETAIL_ID', array('INVENTORY_ITEM_DETAIL_ID','INVENTORY_ITEM_ID', 'INVENTORY_TRANSACTION_ID'));
	$monitor_info_for_generate[] = $inv_it_d_result['monitor_info'];
	if(!empty($inv_it_d_result['query_info']['INVENTORY_ITEM_ID'])){
		//romeo.inventory_item
		$sql = "SELECT INVENTORY_ITEM_ID,SERIAL_NUMBER,STATUS_ID,INVENTORY_ITEM_ACCT_TYPE_ID,INVENTORY_ITEM_TYPE_ID,FACILITY_ID,
		     CONTAINER_ID,QUANTITY_ON_HAND_TOTAL,AVAILABLE_TO_PROMISE,AVAILABLE_TO_PROMISE_TOTAL,QUANTITY_ON_HAND,PRODUCT_ID,CREATED_STAMP,
		     LAST_UPDATED_STAMP,UNIT_COST,ROOT_INVENTORY_ITEM_ID,PARENT_INVENTORY_ITEM_ID,currency,provider_id,validity,batch_sn 
			from romeo.inventory_item
			where inventory_item_id in ({$inv_it_d_result['query_info']['INVENTORY_ITEM_ID']}) order by last_updated_stamp desc";
		$inv_it_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存记录表[romeo.inventory_item]', $sql, 'INVENTORY_ITEM_ID',array('INVENTORY_ITEM_ID','PRODUCT_ID'));
		$monitor_info_for_generate[] = $inv_it_result['monitor_info'];
	}
	if(!empty($inv_it_d_result['query_info']['INVENTORY_TRANSACTION_ID']) and !empty($inv_it_d_result['query_info']['INVENTORY_ITEM_ID'])){
		//romeo.inventory_transaction
		$sql = "SELECT *
			from romeo.inventory_transaction
			where inventory_transaction_id in ({$inv_it_d_result['query_info']['INVENTORY_TRANSACTION_ID']}) order by CREATED_STAMP desc";
		$inv_tran_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存转移记录表[romeo.inventory_transaction]', $sql, 'INVENTORY_TRANSACTION_ID');
		$monitor_info_for_generate[] = $inv_tran_result['monitor_info'];
	}

	//romeo.inventory_summary
	$sql = "SELECT INVENTORY_SUMMARY_ID,STATUS_ID,FACILITY_ID,CONTAINER_ID,PRODUCT_ID,STOCK_QUANTITY,AVAILABLE_TO_RESERVED,CREATED_STAMP,LAST_UPDATED_STAMP 
		from romeo.inventory_summary
		where facility_id = '{$facility_id}' and STATUS_ID in('INV_STTS_AVAILABLE','INV_STTS_USED') and product_id ".db_create_in($product_ids);
	$inv_sum_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存汇总表[romeo.inventory_summary]', $sql, 'INVENTORY_SUMMARY_ID');
	$monitor_info_for_generate[] = $inv_sum_result['monitor_info'];

	return $monitor_info_for_generate;
}
?>