<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('purchase_order', 'distribution_consignment');
require("function.php");

$temp_ids = $_REQUEST['order_ids'];

$order_ids = explode(',',$temp_ids);
foreach ($order_ids as $key => $order_id) {
	$order_ids[$key] = intval($order_id);
}

$type = $_REQUEST['type'];
$shipping_id = $_REQUEST['shipping_id'];

if (is_array($order_ids) && !empty($order_ids)) {	
	$in_order_id = join(', ', $order_ids);
	$sql = "SELECT * FROM {$ecs->table('order_info')} o WHERE order_id IN ({$in_order_id})";
	$orders = $db->getAll($sql);
} elseif ($type == 'selfget' && $shipping_id) {
  $sql = "SELECT * FROM {$ecs->table('order_info')} o WHERE shipping_id = '{$shipping_id}' and shipping_status = 9";
	$orders = $db->getAll($sql);
}


if ($orders) {
  $payments = getPayments();
    $shipping = getShippingTypes();	  

	foreach ($orders as $key => $order) {
	  $sql = "SELECT goods_name, goods_number FROM ecs_order_goods WHERE order_id = '{$order['order_id']}'";
	  $order_goods = $db->getAll($sql);
	  $goods_list = array();

	  foreach ($order_goods as $goods) {
	  	$goods_list[] = "{$goods['goods_name']}({$goods['goods_number']})";
	  }

	  $orders[$key]['goods_names'] = join('<br>', $goods_list);
	  
	  $pay_name = $payments[$order['pay_id']]['pay_name'];

	  $pay_status_name = ($order['pay_status'] == 2) ? '(已付款)' : '(未付款)';
		$orders[$key]['pay_name'] = $pay_name.$pay_status_name;
		$orders[$key]['shipping_name'] = $shippingTypes[$order['shipping_id']] ? $shippingTypes[$order['shipping_id']]['shipping_name'] : $order['shipping_name'];

	}
	
	$smarty->assign('orders', $orders);
    $smarty->assign('shipping_name', $order['shipping_name']);
	$smarty->display('oukooext/print_invoice_summary.dwt');
} else {
  header("location: dcV2.php");
}
	
	



