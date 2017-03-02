<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("批次出库完结",array('batch_pick_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['batch_pick_sn'])){
	$smarty->assign('msg', '请输入batch_pick_sn');
}else
{
	$batch_pick_sn = $_REQUEST['batch_pick_sn'];
	$smarty->assign('monitor_data', GenerateSupplierFinanceInfo($batch_pick_sn));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateSupplierFinanceInfo($batch_pick_sn){
	global $db,$smarty;

	//ecshop.ecs_batch_order_info
	$sql = "select *  from romeo.batch_pick where batch_pick_sn = '{$batch_pick_sn}' ";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'批拣单[romeo.batch_pick]', $sql, 'batch_pick_id');
	$return_data[] = $result['monitor_info'];

	//ecshop.ecs_batch_order_info
	$sql = "select * from romeo.batch_pick_mapping where batch_pick_sn = '{$batch_pick_sn}' ";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'批捡单发货单映射表[romeo.batch_pick_mapping]', $sql, 'shipment_id');
	$return_data[] = $result['monitor_info'];
	
	//ecshop.ecs_batch_order_info
	$sql = "select * from romeo.inventory_location_reserve where batch_pick_sn = '{$batch_pick_sn}' ";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库位预定表[romeo.inventory_location_reserve]', $sql, 'inventory_location_reserve_id');
	$return_data[] = $result['monitor_info'];
	//
	
	$sql = "select shipment_id from romeo.batch_pick_mapping where batch_pick_sn = '{$batch_pick_sn}' group by shipment_id";
	$shipment_ids = $db->getCol($sql);
	foreach($shipment_ids as $shipment_id){
		//romeo.shipment
		$sql = "select shipment_id,status,tracking_number from romeo.shipment where shipment_id = '{$shipment_id}' ";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'发货单状态[romeo.shipment]', $sql, 'shipment_id');
		$return_data[] = $result['monitor_info'];
		
		//romeo.shipment
		$sql = "select 
					oi.order_id,
					oi.order_sn,
					oi.facility_id,
					oi.handle_time,
					oi.order_type_id,
					ep.pay_code,
					ep.is_cod,
					oi.pay_status,
					oi.order_status,
					oi.shipping_status
				from romeo.order_shipment os
				inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id 
				inner join ecshop.ecs_payment ep on oi.pay_id = ep.pay_id
				where os.shipment_id = '{$shipment_id}'";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'订单发货状态[ecshop.ecs_order_info]', $sql, 'order_id');
		$return_data[] = $result['monitor_info'];
		
		$sql = "select 
					og.order_id,og.rec_id,og.goods_name,pm.product_id,oi.facility_id,og.goods_number
				from	romeo.order_shipment os 
				inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id 
				inner join ecshop.ecs_order_goods AS og on os.order_id = og.order_id 
				inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
				where os.shipment_id = '{$shipment_id}' group by og.rec_id";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'订单商品', $sql, 'rec_id');
		$return_data[] = $result['monitor_info'];
		//遍历发货单商品,库存
		$goods = $db->getAll($sql);
		foreach($goods as $good){
			$facility_id = $good['facility_id'];
			$product_id = $good['product_id'];
			$status_id = "INV_STTS_AVAILABLE";
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
	}

	return $return_data;
}
?>
