<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');


$monitor_header = new MonitorHeader(
	"下分销订单监控页",
	array('order_id'));
$smarty->assign('monitor_header', $monitor_header);

$cond = '';

if(!empty($_REQUEST['order_id'])) {
	$cond .= " oi.order_id='".$_REQUEST['order_id']."' ";
}

if(empty($cond)){
	$smarty->assign('msg', '请输入order_id');
}else
{
	$sql = "SELECT oi.order_id,oi.facility_id
			FROM ecshop.ecs_order_info oi 
			where $cond group by oi.order_id";
//    pp($sql);
	$result = $db->getRow($sql);
//	pp($result['order_ids']);
	if(empty($result)){
		$smarty->assign('msg', 'order_id输入错误');
	}
	else{
		$smarty->assign('monitor_data', GenerateMonitorInfo($result['facility_id'], $result['order_id']));
	}
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateMonitorInfo($facility_id, $order_ids){
	global $db;
	$product_ids = explode(',',$product_ids);
	
	//订单相关
	//ecshop.ecs_order_info
	$sql = "SELECT *
			from ecshop.ecs_order_info
			where order_id in ({$order_ids})";
	$oi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单表[ecshop.ecs_order_info]', $sql, 'order_id',array('order_id','carrier_bill_id'));
	$monitor_info_for_generate[] = $oi_result['monitor_info'];
	$order_ids = $oi_result['query_info']['order_id'];
	
	//ecshop.ecs_order_goods
	$sql = "SELECT *
			from ecshop.ecs_order_goods
			where order_id in ({$order_ids})";
	$og_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id', array('rec_id'));
	$monitor_info_for_generate[] = $og_result['monitor_info'];
	$order_goods_ids = $og_result['query_info']['rec_id'];
//	var_dump('$og_result');var_dump($og_result);

	//ecshop.ecs_order_action
	$sql = "SELECT *
			from ecshop.ecs_order_action
			where order_id in ({$order_ids})";
	$oa_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单动作记录表[ecshop.ecs_order_action]', $sql, 'action_id');
	$monitor_info_for_generate[] = $oa_result['monitor_info'];

  	//ecshop.order_attribute
	$sql = "SELECT *
			from ecshop.order_attribute
			where order_id in ({$order_ids})";
	$oat_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单属性表[ecshop.order_attribute]', $sql, 'attribute_id');
	$monitor_info_for_generate[] = $oat_result['monitor_info'];
	
  	//ecshop.order_goods_attribute
	$sql = "SELECT *
			from ecshop.order_goods_attribute
			where order_goods_id in ({$order_goods_ids})";
	$ogat_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单商品属性表[ecshop.order_goods_attribute]', $sql, 'order_goods_attribute_id');
	$monitor_info_for_generate[] = $ogat_result['monitor_info'];
	
	
	//ecshop.order_mixed_status_history
	$sql = "SELECT *
			from ecshop.order_mixed_status_history
			where order_id in ({$order_ids})";
	$omsh_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单状态历史表[ecshop.order_mixed_status_history]', $sql, 'order_mixed_status_history_id');
	$monitor_info_for_generate[] = $omsh_result['monitor_info'];
	
	//ecshop.order_mixed_status_note
	$sql = "SELECT *
			from ecshop.order_mixed_status_note
			where order_id in ({$order_ids})";
	$omsn_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单状态备注表[ecshop.order_mixed_status_note]', $sql, 'order_mixed_status_note_id');
	$monitor_info_for_generate[] = $omsn_result['monitor_info'];
	
	//ecshop.ecs_taobao_order_mapping
	$sql = "SELECT *
			from ecshop.ecs_taobao_order_mapping
			where order_id in ({$order_ids})";
	$omsn_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'淘宝订单映射表[ecshop.ecs_taobao_order_mapping]', $sql, 'mapping_id');
	$monitor_info_for_generate[] = $omsn_result['monitor_info'];
	
	//ecshop.ecs_order_mapping ，添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
	$sql = "SELECT *
			from ecshop.ecs_order_mapping
			where erp_order_id in ({$order_ids})";
	$omsn_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'新淘宝订单映射表[ecshop.ecs_order_mapping]', $sql, 'mapping_id');
	$monitor_info_for_generate[] = $omsn_result['monitor_info'];
	
	//romeo.order_shipment
	$sql = "SELECT *
			from romeo.order_shipment
			where order_id in ({$order_ids})";
	$os_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'快递订单映射表[ecshop.order_shipment]', $sql, 'order_id');
	$monitor_info_for_generate[] = $os_result['monitor_info'];
	
	//romeo.shipment
	$sql = "SELECT *
			from romeo.shipment
			where primary_order_id in ({$order_ids})";
	$s_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'快递表[ecshop.shipment]', $sql, 'shipment_id');
	$monitor_info_for_generate[] = $s_result['monitor_info'];
	
	//romeo.order_inv_reserved
	$sql = "SELECT *
			from romeo.order_inv_reserved
			where order_id in ({$order_ids})";
	$s_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'预定表[romeo.order_inv_reserved]', $sql, 'order_inv_reserved_id');
	$monitor_info_for_generate[] = $s_result['monitor_info'];
	
	//romeo.order_inv_reserved_detail
	$sql = "SELECT *
			from romeo.order_inv_reserved_detail
			where order_id in ({$order_ids})";
	$s_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'预定表明细[romeo.order_inv_reserved_detail]', $sql, 'ORDER_INV_RESERVED_DETAIL_ID',array('ORDER_INV_RESERVED_DETAIL_ID'));
	$monitor_info_for_generate[] = $s_result['monitor_info'];
	$order_inv_reserved_detail_ids = $s_result['query_info']['ORDER_INV_RESERVED_DETAIL_ID'];
	
	
	if(!empty($order_inv_reserved_detail_ids)) {
		//romeo.order_inv_reserverd_inventory_mapping
		$sql = "SELECT *
				from romeo.order_inv_reserved_inventory_mapping
				where order_inv_reserved_detail_id <>'' " .
				" and order_inv_reserved_detail_id in ({$order_inv_reserved_detail_ids})";
		$s_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'预定表inventory_item明细[romeo.order_inv_reserved_inventory_mapping]', $sql, 'inventory_item_id');
		$monitor_info_for_generate[] = $s_result['monitor_info'];
	}
	
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
		     LAST_UPDATED_STAMP,UNIT_COST,ROOT_INVENTORY_ITEM_ID,PARENT_INVENTORY_ITEM_ID,currency,provider_id ,validity,batch_sn
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
		where facility_id = '{$facility_id}' and STATUS_ID = 'INV_STTS_AVAILABLE' and product_id ".db_create_in($product_ids);
	$inv_sum_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存汇总表[romeo.inventory_summary]', $sql, 'INVENTORY_SUMMARY_ID');
	$monitor_info_for_generate[] = $inv_sum_result['monitor_info'];
	
	
	return $monitor_info_for_generate;
}
?>