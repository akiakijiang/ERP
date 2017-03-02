<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('wl_print_shipping_order');
require("function.php");

$order_id = intval($_REQUEST['order_id']);

$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$limit = "LIMIT $size";
$offset = "OFFSET $start";

$condition = getCondition();

$sql = "
	SELECT *, IF(LENGTH(order_sn) = 10, (SELECT action_time FROM {$ecs->table('order_action')} a WHERE a.order_id = o.order_id AND order_status = 1 limit 1), order_time) confirm_time 
	FROM {$ecs->table('order_info')} o 
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
	LEFT JOIN {$ecs->table('shipping')} s ON s.shipping_id = o.shipping_id
	WHERE 
		biaoju_store_id in (0, 7)
		AND order_status = 1 
		AND shipping_status IN (0, 4)
		AND order_sn NOT LIKE '%-t'
		{$condition} 
	ORDER BY o.confirm_time $limit $offset
";
$sqlc = "
	SELECT COUNT(*) FROM {$ecs->table('order_info')} o 
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
	LEFT JOIN {$ecs->table('shipping')} s ON s.shipping_id = o.shipping_id
	WHERE 
		biaoju_store_id in (0, 7)
		AND order_status = 1 
		AND shipping_status IN (0, 4)
		AND order_sn NOT LIKE '%-t'
		{$condition} 
";

$orders = $db->getAll($sql);
$count = $db->getOne($sqlc);

	
foreach ($orders as $key => $order) {
	$sql = "SELECT goods_name FROM {$ecs->table('order_goods')} WHERE order_id = '{$order['order_id']}'";
	$goods_names = $db->getCol($sql);
	$orders[$key]['goods_names'] = $goods_names;
}


$pager = Pager($count, $size, $page);
$smarty->assign('orders', $orders);
$smarty->assign('pager', $pager);
$smarty->assign('carriers', getCarriers());
$smarty->assign('shippingTypes', getShippingTypes());
$smarty->display('oukooext/print_shipping_order.htm');
?>

<?php

function getCondition() {
	$order_id = $_REQUEST['order_id'];
	$carrier_id = $_REQUEST['carrier_id'];
	
	$condition = '';
	if ($order_id > 0) {
		$condition .= " AND o.order_id = '{$order_id}'";
	}
	
	if ($carrier_id > 0) {
		$condition .= " AND cb.carrier_id = '$carrier_id'";
	}
	
	return $condition;
}	
?>
