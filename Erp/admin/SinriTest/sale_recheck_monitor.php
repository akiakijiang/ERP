<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once ('monitor_tools.php');

$monitor_header = new MonitorHeader("批次采购订单信息监控页",array('shipment_id', 'order_sn'));
$smarty->assign('monitor_header', $monitor_header);

if(empty($_REQUEST['shipment_id']) && empty($_REQUEST['order_sn'])){
	$smarty->assign('msg', '请输入shipment_id或order_sn');
}else
{
	if(!empty($_REQUEST['shipment_id'])){
		$shipment_id = $_REQUEST['shipment_id'];
	}else{
		$sql = "select os.shipment_id 
			from romeo.order_shipment os
			inner join ecshop.ecs_order_info oi on os.order_id = convert(oi.order_id using utf8) where order_sn = '{$_REQUEST['order_sn']}'";
		$shipment_id = $db->getOne($sql);
	}
	$smarty->assign('monitor_data', GeneratePurchaseOrderInfo($shipment_id));
}
$smarty->display('SinriTest/common_monitor.htm');


function GeneratePurchaseOrderInfo($shipment_id){
	//romeo.shipment
	$sql = "select shipment_id,status,tracking_number from romeo.shipment where shipment_id = '{$shipment_id}' ";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'发货单[romeo.shipment]', $sql, 'shipment_id');
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
		'订单[ecshop.ecs_order_info]', $sql, 'order_id');
	$return_data[] = $result['monitor_info'];
	
	$sql = "select 
				og.order_id,og.rec_id,og.goods_name,og.goods_number,ifnull(gs.barcode,g.barcode)  as barcode
			from	romeo.order_shipment os
			inner join  ecshop.ecs_order_goods AS og on os.order_id = og.order_id
			              LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
			              LEFT JOIN ecshop.ecs_goods_style gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id and gs.is_delete=0
			where os.shipment_id = '{$shipment_id}'";
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'提示订单商品barcode', $sql, 'rec_id');
	$return_data[] = $result['monitor_info'];
	return $return_data;
}
?>
