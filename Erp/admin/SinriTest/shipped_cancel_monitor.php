<?php
/*
 * Created on 2014-3-6
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("取消追回情况监控页",array('order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入order_sn');
}else
{
	$order_sn = trim($_REQUEST['order_sn']);
	$sql = "select * from ecshop.ecs_order_info where order_sn = '{$order_sn}'";
	$result = $db->getRow($sql);
	if(empty($result)){
		$smarty->assign('msg', 'order_sn输入错误');
	}else{
		$smarty->assign('monitor_data', GenerateBackOrderInfo($order_sn, $result['order_id']));
	}
}
$smarty->display('SinriTest/common_monitor.htm');
 
function GenerateBackOrderInfo($order_sn, $order_id){
	global $db;
	// 原始订单相关
	// ecshop.ecs_order_info
	$sql = "select order_sn, order_id, order_time, user_id, order_status, shipping_status, order_amount, goods_amount, 
             	   shipping_fee, is_back, pack_fee, bonus, misc_fee, pay_id, pay_name, shipping_id,
             	   shipping_name, party_id, facility_id, distributor_id, currency, order_type_id
			from ecshop.ecs_order_info
			where order_sn = '{$order_sn}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'【原始订单】订单信息表[ecshop.ecs_order_info]', $sql, 'order_id');
	$back_info_for_generate[] = $result['monitor_info'];
	
	// ecshop.ecs_order_goods
	$sql = "select * from ecshop.ecs_order_goods where order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【原始订单】订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id');
	$back_info_for_generate[] = $result['monitor_info'];
	
	// romeo.inventory_item
	$sql = "select ii.inventory_item_id, ii.serial_number, ii.status_id, ii.inventory_item_acct_type_id,
				   ii.inventory_item_type_id, ii.facility_id, ii.container_id, ii.quantity_on_hand_total, ii.available_to_promise, ii.available_to_promise_total, ii.quantity_on_hand,
				   ii.product_id, ii.party_id, ii.unit_cost, ii.root_inventory_item_id, ii.parent_inventory_item_id, ii.provider_id, ii.LAST_UPDATED_STAMP
			from romeo.inventory_item_detail iid
			inner join romeo.inventory_item ii on ii.inventory_item_id = iid.inventory_item_id
			where iid.order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【原始订单】库存表[romeo.inventory_item]', $sql, 'inventory_item_id');
	$back_info_for_generate[] = $result['monitor_info'];
	
	// romeo.inventory_item_detail
	$sql = "select inventory_item_detail_id, inventory_item_id, quantity_on_hand_diff,
				   order_id, inventory_transaction_id, order_goods_id
		    from romeo.inventory_item_detail where order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【原始订单】库存明细表[romeo.inventory_item_detail]', $sql, 'inventory_item_detail_id');
	$back_info_for_generate[] = $result['monitor_info'];
	
	// ecshop.ecs_order_action
	$sql = "select * from ecshop.ecs_order_action where order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【原始订单】订单操作记录表[ecshop.ecs_order_action]', $sql, 'action_id');
	$back_info_for_generate[] = $result['monitor_info'];
	
	// ecshop.order_relation
	$sql = "select * from ecshop.order_relation where parent_order_id = '{$order_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('订单关系表[ecshop.order_relation]', $sql, 'order_relation_id', array('order_id'));
	$back_info_for_generate[] = $result['monitor_info'];
	
	$sql = "select order_id from ecshop.order_relation where parent_order_id = '{$order_id}'";
	$back_order_id_arr = $db->getCol($sql);
	if(!empty($back_order_id_arr)){
		$back_order_ids_str = implode(',',$back_order_id_arr);
		// ecshop.ecs_order_info
		$sql = "select order_sn, order_id, order_time, user_id, order_status, shipping_status,order_amount, goods_amount, 
	             	   shipping_fee, is_back, pack_fee, bonus, misc_fee, pay_id, pay_name, shipping_id,
	             	   shipping_name, party_id, facility_id, distributor_id, currency, order_type_id
	            from ecshop.ecs_order_info where order_id in ({$back_order_ids_str})";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【-t订单】订单信息表[ecshop.ecs_order_info]', $sql, 'order_id', array('order_id'));
		$back_info_for_generate[] = $result['monitor_info'];
		
		if(!empty($result['query_info']['order_id'])){
			$back_order_ids = explode(',',$result['query_info']['order_id']);
			if(!empty($back_order_ids)){
				foreach($back_order_ids as $back_order_id){
					// ecshop.ecs_order_goods
					$sql = "select * from ecshop.ecs_order_goods where order_id = {$back_order_id}";
					$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【-t订单】订单商品表[ecshop.ecs_order_goods]', $sql, 'rec_id');
					$back_info_for_generate[] = $result['monitor_info'];
					
					// romeo.inventory_item
					$sql = "select ii.inventory_item_id, ii.serial_number, ii.status_id, ii.inventory_item_acct_type_id,
								   ii.inventory_item_type_id, ii.facility_id, ii.container_id, ii.quantity_on_hand_total, ii.available_to_promise_total,
								   ii.product_id, ii.party_id, ii.unit_cost, ii.root_inventory_item_id, ii.parent_inventory_item_id, ii.provider_id
							from romeo.inventory_item_detail iid
							inner join romeo.inventory_item ii on ii.inventory_item_id = iid.inventory_item_id
							where iid.order_id = {$back_order_id}";
					$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【-t订单】库存表[romeo.inventory_item]', $sql, 'inventory_item_id');
					$back_info_for_generate[] = $result['monitor_info'];
			
					// romeo.inventory_item_detail
					$sql = "select inventory_item_detail_id, inventory_item_id, CREATED_STAMP, LAST_UPDATED_STAMP, quantity_on_hand_diff, AVAILABLE_TO_PROMISE_DIFF, order_id, inventory_transaction_id, order_goods_id, CANCELLATION_FLAG
						    from romeo.inventory_item_detail where order_id = {$back_order_id}";
					$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('【-t订单】库存明细表[romeo.inventory_item_detail]', $sql, 'inventory_item_detail_id');
					$back_info_for_generate[] = $result['monitor_info'];
	
					//romeo.inventory_transaction
					$sql = "SELECT rit.inventory_transaction_id, rit.inventory_transaction_type_id, rit.CREATED_STAMP, rit.LAST_UPDATED_STAMP, rit.CREATED_BY_USER_LOGIN, rit.available_to_promise, rit.quantity_on_hand, rit.from_inventory_item_id, rit.to_inventory_item_id, rit.from_facility_id, rit.to_facility_id, rit.from_container_id,
							 	   rit.to_container_id, rit.from_status_id, rit.to_status_id
							from romeo.inventory_item_detail iid
							inner join romeo.inventory_transaction rit on iid.inventory_transaction_id = rit.inventory_transaction_id
							where iid.order_id = {$back_order_id}";
					$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
						'【-t订单】库存转移记录表[romeo.inventory_transaction]', $sql, 'inventory_transaction_id');
					$back_info_for_generate[] = $result['monitor_info'];
				}
			}
		}
		
	}

	$sql = "select pm.product_id
			from romeo.product_mapping pm
			inner join ecshop.ecs_order_goods og on pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
			where og.order_id = '{$order_id}'";
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
				where (status_id = 'INV_STTS_AVAILABLE' or status_id = 'INV_STTS_USED') and product_id in ({$product_id_array})";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL('库存汇总表[romeo.inventory_summary]', $sql, 'INVENTORY_SUMMARY_ID');
		$back_info_for_generate[] = $result['monitor_info'];
	}
	
	return $back_info_for_generate;
}
?>
