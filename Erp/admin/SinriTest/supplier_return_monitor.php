<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("-gt信息监控页",array('supplier_return_id','order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['supplier_return_id']) && empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入supplier_return_id 或者-gt订单');
}else
{
	$supplier_return_id = $_REQUEST['supplier_return_id'];
	if(empty($supplier_return_id)){
		$sql = "SELECT supplier_return_id from romeo.supplier_return_request_gt where supplier_return_gt_sn = '{$_REQUEST['order_sn']}'";
		$supplier_return_id = $db->getOne($sql);
	}
	$smarty->assign('monitor_data', GenerateSupplierFinanceInfo($supplier_return_id));
}
$smarty->display('SinriTest/common_monitor.htm');


function GenerateSupplierFinanceInfo($supplier_return_id){
	global $db;

	//ecshop.ecs_batch_order_info
	$sql = "SELECT *
			from romeo.supplier_return_request
			where supplier_return_id = {$supplier_return_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'-gt申请信息表[romeo.supplier_return_request]', $sql, 'supplier_return_id');
	$return_data[] = $result['monitor_info'];

	//ecshop.ecs_batch_order_info
	$sql = "SELECT *
			from romeo.supplier_return_request_item
			where supplier_return_id = {$supplier_return_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'-gt申请商品信息表[ecshop.supplier_return_request_item]', $sql, 'supplier_return_id');
	$return_data[] = $result['monitor_info'];
	
	//ecshop.ecs_batch_order_info
	$sql = "SELECT *
			from romeo.supplier_return_request_gt
			where supplier_return_id = {$supplier_return_id}";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'-gt申请和订单映射表[romeo.supplier_return_request_gt]', $sql, 'supplier_return_id');
	$return_data[] = $result['monitor_info'];
	
	$sql = "SELECT oi.order_id
			from romeo.supplier_return_request_gt srrg
			inner join ecshop.ecs_order_info oi on srrg.supplier_return_gt_sn = oi.order_sn 
			where srrg.supplier_return_id = {$supplier_return_id} limit 1";
	$order_id = $db->getOne($sql);
	
	$sql = "select 
				*
			  from romeo.supplier_return_request 
			  where supplier_return_id = {$supplier_return_id}";
	$product_data = $db->getRow($sql);
	$product_id = $product_data['product_id'];
	$status_id = $product_data['status_id'];
	$facility_id = $product_data['facility_id'];
	$unit_pice = $product_data['unit_price'];
	
	if(!empty($order_id)){
		//
		$sql = "SELECT oi.order_id,oi.order_sn,og.rec_id,og.goods_number,og.market_price,og.market_price
				from  ecshop.ecs_order_info oi 
				inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
				where oi.order_id = {$order_id}";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'对应订单及商品信息[ecs_order_info,ecs_order_goods]', $sql, 'order_id');
		$return_data[] = $result['monitor_info'];	
		
		$sql = "SELECT sroi.*
			from ecshop.supplier_return_order_info sroi 
			where sroi.order_id = {$order_id}";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'-gt订单特殊属性[ecshop.supplier_return_order_info]', $sql, 'supplier_return_id');
		$return_data[] = $result['monitor_info'];
		
		$sql = "SELECT *
			from  ecshop.order_attribute
			where order_id = {$order_id}";
		$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'金宝贝退货仓[ecshop.order_attribute]', $sql, 'supplier_return_id');
		$return_data[] = $result['monitor_info'];
		
		//库存
		$sql = "SELECT *
			from romeo.inventory_item_detail
			where order_id = '{$order_id}'";
		$item_detail_data = $db->getAll($sql);
		
		
		if(empty($item_detail_data)){
			$sql = "SELECT *
				from romeo.inventory_item ii
				where ii.product_id = '{$product_id}' and ii.status_id = '{$status_id}' and ii.facility_id = '{$facility_id}' and ii.unit_cost = '{$unit_pice}' 
					and ii.quantity_on_hand_total > 0";
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'出库前库存[romeo.inventory_item]', $sql, 'inventory_item_id');
			$return_data[] = $result['monitor_info'];
		}else{
			//已经出库
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
			'出库详情[romeo.inventory_item_detail]', $sql, 'inventory_item_detail_id');
			$return_data[] = $result['monitor_info'];
			
			$sql = "SELECT ii.*
				from romeo.inventory_item_detail iid 
				inner join romeo.inventory_item ii on ii.inventory_item_id = iid.inventory_item_id 
				where iid.order_id = '{$order_id}'";
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'出库后库存[romeo.inventory_item]', $sql, 'inventory_item_id');
			$return_data[] = $result['monitor_info'];
			
			$sql = "select * from romeo.purchase_return_map where RETURN_ORDER_ID = '{$order_id}'";
			$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
				'-gt和-c，inventory_item级别关联[romeo.purchase_return_map]', $sql, 'PURCHASE_RETURN_MAP_ID');
			$return_data[] = $result['monitor_info'];
		}
	}
	//romeo.inventory_summary
	$sql = "SELECT *
		from romeo.inventory_summary 
		where product_id = '{$product_id}' and status_id = '{$status_id}' and facility_id = '{$facility_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'库存总表[romeo.inventory_summary]', $sql, 'inventory_summary_id');
	$return_data[] = $result['monitor_info'];

	return $return_data;
}
?>