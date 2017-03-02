<?php
/*
 * Created on 2014-2-20 by zjli
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("退换货收货、验货入库【新流程】情况监控页",array('service_id'));
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
		$smarty->assign('monitor_data', GenerateBackOrderInfo($result['service_id'], $result['back_order_id'], $result['facility_id']));
	}
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateBackOrderInfo($service_id, $back_order_id, $facility_id){
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
	$back_info_for_generate[] = $result['monitor_info'];
	
	// ecshop.service_log
	$sql = "select service_log_id, service_id, service_status, type_name,
				   status_name, log_username, log_note, log_datetime, log_type
			from ecshop.service_log where service_id = '{$service_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('售后服务日志表[ecshop.service_log]', $sql, 'service_log_id');
	$back_info_for_generate[] = $result['monitor_info'];
	
	// ecshop.ecs_order_goods (-t订单)
	$sql = "select * from ecshop.ecs_order_goods where order_id = '{$back_order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id', array('rec_id'));
	// $back_info_for_generate[] = $result['monitor_info'];
	$back_order_goods_ids = $result['query_info']['rec_id'];
	
	// 库存相关
	// romeo
	if(!empty($back_order_goods_ids)){
		// romeo.inventory_item_detail
		$sql = "select inventory_item_detail_id, inventory_item_id, quantity_on_hand_diff,
					   order_id, inventory_transaction_id, order_goods_id
				from romeo.inventory_item_detail
				where order_goods_id in ({$back_order_goods_ids})";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】库存记录明细表[romeo.inventory_item_detail]', $sql, 'inventory_item_detail_id', array('inventory_item_id', 'inventory_transaction_id'));
		$back_info_for_generate[] = $result['monitor_info'];
		$inventory_item_ids = $result['query_info']['inventory_item_id'];
		$inventory_transaction_ids = $result['query_info']['inventory_transaction_id'];
		
		// romeo.inventory_item
		if(!empty($inventory_item_ids)){
			$sql = "select inventory_item_id, serial_number, status_id, inventory_item_acct_type_id,
						   inventory_item_type_id, facility_id, container_id, quantity_on_hand_total, available_to_promise_total,
						   product_id, party_id, unit_cost, root_inventory_item_id, parent_inventory_item_id, provider_id
					from romeo.inventory_item where inventory_item_id in ({$inventory_item_ids})";
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【退货(-t)订单】库存记录表[romeo.inventory_item]', $sql, 'inventory_item_id');
			$back_info_for_generate[] = $result['monitor_info'];
			// $product_ids = $result['query_info']['product_id'];
		}
		
		if(!empty($inventory_item_ids) && !empty($inventory_transaction_ids)){
			//romeo.inventory_transaction
			$sql = "SELECT inventory_transaction_id, inventory_transaction_type_id, available_to_promise, quantity_on_hand,
					 	   from_inventory_item_id, to_inventory_item_id, from_facility_id, to_facility_id, from_container_id,
					 	   to_container_id, from_status_id, to_status_id
					from romeo.inventory_transaction
					where inventory_transaction_id in ({$inventory_transaction_ids})
					and to_inventory_item_id in ({$inventory_item_ids})";
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'【退货(-t)订单】库存转移记录表[romeo.inventory_transaction]', $sql, 'inventory_transaction_id');
			$back_info_for_generate[] = $result['monitor_info'];
		}
	}
	
	$sql = "select pm.product_id
			from romeo.product_mapping pm
			inner join ecshop.ecs_order_goods og on pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
			inner join ecshop.service s on s.order_id = og.order_id
			where s.service_id = '{$service_id}'";
	$product_ids = $db->getCol($sql);
	if(!empty($product_ids)){
		$product_id_array = "";
		foreach($product_ids as $index=>$product_id){
			if($index == 0){
				$product_id_array .= "'{$product_id}'";
			}else{
				$product_id_array .= ",'{$product_id}'";
			}
		}
		//romeo.inventory_summary
		$sql = "SELECT * 
				from romeo.inventory_summary
				where facility_id = '{$facility_id}' and (status_id = 'INV_STTS_AVAILABLE' or status_id = 'INV_STTS_USED') and product_id in ({$product_id_array})";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('库存汇总表[romeo.inventory_summary]', $sql, 'INVENTORY_SUMMARY_ID');
		$back_info_for_generate[] = $result['monitor_info'];
	}
	
	return $back_info_for_generate;
}
?>
