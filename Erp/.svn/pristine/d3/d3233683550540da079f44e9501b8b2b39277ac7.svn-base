<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('c2c_dc');
require("function.php");

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$limit = "LIMIT $size";
$offset = "OFFSET $start";

$condition .= getCondition();

$sql = "
	SELECT order_sn, order_time, order_amount, shipping_status, consignee, o.address, pay_status, o.pay_id, real_shipping_fee, proxy_amount, c.name AS carrier_name, cb.bill_no, p.pay_name, o.shipping_fee, o.real_shipping_fee, a.*, o.order_id, s.name store_name, IF(pay_status = 2, real_shipping_fee, shipping_fee) AS fix_shipping_fee
	FROM {$ecs->table('order_info')} AS o
	LEFT JOIN bj_account a ON o.order_id = a.order_id
	LEFT JOIN bj_store s ON o.biaoju_store_id = s.store_id
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
	LEFT JOIN {$ecs->table('carrier')} c ON c.carrier_id = cb.carrier_id
	LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id
	WHERE 
		(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS o2 WHERE o2.parent_order_id = o.order_id) = 0 
		AND order_status = 1
		{$condition} 
	ORDER BY o.order_time DESC $limit $offset
";
$sqlc = "
	SELECT COUNT(*) 
	FROM {$ecs->table('order_info')} AS o 
	LEFT JOIN bj_account a ON o.order_id = a.order_id
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
	LEFT JOIN {$ecs->table('carrier')} c ON c.carrier_id = cb.carrier_id	
	LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id
	WHERE 
		(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS o2 WHERE o2.parent_order_id = o.order_id) = 0 
		AND order_status = 1
		{$condition}
";

$orders = $db->getAll($sql);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

foreach ($orders as $key => $order) {
	$sql = "SELECT * FROM {$ecs->table('order_goods')} g WHERE g.order_id = '{$order['order_id']}'";
	$goods_list = $db->getAll($sql);
	$orders[$key]['goods_list'] = $goods_list;
	
	$orders[$key]['shipping_status_name'] = get_shipping_status($order['shipping_status']);
}

$smarty->assign('orders', $orders);
$smarty->assign('all_shipping_status', $_CFG['adminvars']['shipping_status']);
$smarty->assign('stores', getStores());
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('pager', $pager);
$smarty->display('oukooext/c2c_dc.htm');
?>

<?php
function getCondition() {
	$act = $_REQUEST['act'];
	$store_id = intval($_REQUEST['store_id']);
	$shipping_status = $_REQUEST['shipping_status'];
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$search_text = trim($_REQUEST['search_text']);
	
	if ($store_id > 0) {
		$condition .= " AND biaoju_store_id = '{$store_id}'";
	} else {
		$condition .= " AND biaoju_store_id != 0";
	}
	if ($shipping_status != null && $shipping_status != -1) {
		$condition .= " AND shipping_status = '$shipping_status'";		
	}
	
	if (strtotime($start) > 0) {
		$condition .= " AND order_time >= '$start'";
	}
	if (strtotime($end) > 0) {
		$end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
		$condition .= " AND order_time <= '$end'";
	}

	if ($search_text != '') {
		$condition .= " AND (o.order_sn = '{$search_text}' 
		                     OR o.consignee = '{$search_text}' 
		                     OR cb.bill_no LIKE '{$search_text}'
		                     )";
	}
	return $condition;
}

?>