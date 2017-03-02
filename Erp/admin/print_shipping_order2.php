<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('purchase_order', 'distribution_consignment');
require("function.php");

$order_ids = $_REQUEST['order_id'];
if (is_array($order_ids) && !empty($order_ids)) {
	if (count($order_ids) == 1) {
		header("location: print_shipping_order3.php?order_id={$order_ids[0]}&type={$_REQUEST['type']}");
		die();
	}
	
	$in_order_id = join(', ', $order_ids);
	$sql = "SELECT * FROM {$ecs->table('order_info')} o WHERE order_id IN ({$in_order_id})";
	$orders = $db->getAll($sql);
	
	foreach ($orders as $key => $order) {
		$sql = "SELECT goods_name FROM {$ecs->table('order_goods')} WHERE order_id = '{$order['order_id']}'";
		$goods_names = $db->getCol($sql);
		$orders[$key]['goods_names'] = $goods_names;
	}
	
	$smarty->assign('orders', $orders);
}

$smarty->display('oukooext/print_shipping_order2.htm');
?>