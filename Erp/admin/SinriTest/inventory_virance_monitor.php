<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("-v信息监控页",array('order_id','order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['order_id']) && empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入order_id');
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

	//订单相关
	//ecshop.ecs_order_info
	$sql = "SELECT order_id,order_sn, order_time, order_status, pay_status, user_id, postscript, 
                    order_type_id, party_id, facility_id
			from ecshop.ecs_order_info
			where order_id in ({$order_id})";
	$oi_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单表[ecshop.ecs_order_info]', $sql, 'order_id',array('order_id','carrier_bill_id'));
	$return_data[] = $oi_result['monitor_info'];
	$order_ids = $oi_result['query_info']['order_id'];
	
	//ecshop.ecs_order_action
//	$sql = "SELECT *
//			from ecshop.ecs_order_action
//			where order_id in ({$order_id})";
//	$oa_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
//		'订单动作记录表[ecshop.ecs_order_action]', $sql, 'action_id');
//	$return_data[] = $oa_result['monitor_info'];
	
	//ecshop.ecs_order_goods
	$sql = "SELECT rec_id,order_id, goods_id, style_id, goods_name, goods_number, goods_price
			from ecshop.ecs_order_goods
			where order_id in ({$order_id})";
	$og_result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id', array('rec_id'));
	$return_data[] = $og_result['monitor_info'];

	$sql = "select 
					og.rec_id,og.status_id,pm.product_id,oi.facility_id
				from  ecshop.ecs_order_info oi  
				inner join ecshop.ecs_order_goods AS og on oi.order_id = og.order_id 
				inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
				where oi.order_id = '{$order_id}' group by og.rec_id";
	//遍历发货单商品,库存
	$goods = $db->getAll($sql);
	foreach($goods as $good){
		$facility_id = $good['facility_id'];
		$product_id = $good['product_id'];
		$status_id = $good['status_id'];
		$order_goods_id = $good['rec_id'];

		//romeo.inventory_item_detail
		$sql = "SELECT inventory_item_detail_id,inventory_item_id,quantity_on_hand_diff,order_id,inventory_transaction_id,order_goods_id
				from romeo.inventory_item_detail
				where order_goods_id = '{$order_goods_id}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'出库明细[romeo.inventory_item_detail]', $sql, 'inventory_item_detail_id');
		$return_data[] = $result['monitor_info'];
		
		//romeo.inventory_transaction
		$sql = "SELECT it.*
				from romeo.inventory_item_detail iid
				inner join romeo.inventory_transaction it on iid.inventory_transaction_id = it.inventory_transaction_id
				where iid.order_goods_id = '{$order_goods_id}'";
		
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'出库动作[romeo.inventory_transaction]', $sql, 'inventory_transaction_id');
		$return_data[] = $result['monitor_info'];
		
		//romeo.inventory_transaction
		$sql = "SELECT pi.*
				from romeo.inventory_item_detail iid
				inner join romeo.physical_inventory pi on iid.physical_inventory_id = pi.physical_inventory_id
				where iid.order_goods_id = '{$order_goods_id}'";
		
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'盘点库存物理点[romeo.physical_inventory]', $sql, 'physical_inventory');
		$return_data[] = $result['monitor_info'];
		
		//romeo.inventory_transaction
		$sql = "SELECT iiv.*
				from romeo.inventory_item_detail iid
				inner join romeo.inventory_item_variance iiv on iid.physical_inventory_id = iiv.physical_inventory_id
				where iid.order_goods_id = '{$order_goods_id}'";
		
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'盘点详情，和inventory_item_detail重复[romeo.inventory_item_variance]', $sql, 'physical_inventory');
		$return_data[] = $result['monitor_info'];
		
		$sql = "SELECT ii.inventory_item_id,ii.product_id,ii.status_id,ii.facility_id,ii.unit_cost,
				ii.quantity_on_hand_total,ii.available_to_promise,ii.available_to_promise_total,ii.quantity_on_hand,ii.provider_id
			from romeo.inventory_item_detail iid 
			inner join romeo.inventory_item ii on ii.inventory_item_id = iid.inventory_item_id 
			where iid.order_goods_id = '{$order_goods_id}'";
		$data = $db->getAll($sql);
		if(empty($data)){
			$sql = "SELECT ii.inventory_item_id,ii.product_id,ii.status_id,ii.facility_id,ii.unit_cost,
				ii.quantity_on_hand_total,ii.available_to_promise,ii.available_to_promise_total,ii.quantity_on_hand,ii.provider_id
			from romeo.inventory_item ii
			where ii.product_id = '{$product_id}' and ii.status_id = '{$status_id}' and ii.facility_id = '{$facility_id}' 
				and ii.quantity_on_hand_total > 0";
		}

		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存明细[romeo.inventory_item]', $sql, 'inventory_item_id');
		$return_data[] = $result['monitor_info'];

		
		//romeo.inventory_summary
		$sql = "SELECT inventory_summary_id,product_id,status_id,facility_id,stock_quantity,available_to_reserved
			from romeo.inventory_summary 
			where product_id = '{$product_id}' and status_id = '{$status_id}' and facility_id = '{$facility_id}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'库存总表[romeo.inventory_summary]', $sql, 'inventory_summary_id');
		$return_data[] = $result['monitor_info'];
	}

	return $return_data;
}
?>