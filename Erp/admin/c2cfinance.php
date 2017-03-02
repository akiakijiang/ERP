<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('finance_order');
require("function.php");

$csv = $_REQUEST['csv'];

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
$limit = "LIMIT $size";
$offset = "OFFSET $start";
}

$condition .= getCondition();

$sql = "
	SELECT order_sn, order_time, order_amount, shipping_status, consignee, o.address, pay_status, real_shipping_fee, proxy_amount, c.name AS carrier_name, cb.bill_no, p.pay_name, o.shipping_fee, o.real_shipping_fee, a.*, o.order_id
	FROM {$ecs->table('order_info')} AS o
	LEFT JOIN bj_account a ON o.order_id = a.order_id
	LEFT JOIN bj_store s ON o.biaoju_store_id = s.store_id
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
	LEFT JOIN {$ecs->table('carrier')} c ON c.carrier_id = cb.carrier_id
	LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id
	WHERE 
		(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS o2 WHERE o2.parent_order_id = o.order_id) = 0 
		AND order_status = 1
		AND shipping_status in (1, 6)
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
		AND shipping_status in (1, 6)
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

$sql = "
	SELECT SUM(order_amount) order_amount, SUM(shipping_fee) shipping_fee, SUM(real_shipping_fee) real_shipping_fee, SUM(proxy_amount) proxy_amount, SUM(real_ouku_collect_amount) real_ouku_collect_amount, SUM(ouku_paid_amount) ouku_paid_amount, SUM(real_ouku_paid_amount) real_ouku_paid_amount
	FROM {$ecs->table('order_info')} AS o 
	LEFT JOIN bj_account a ON o.order_id = a.order_id
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
	LEFT JOIN {$ecs->table('carrier')} c ON c.carrier_id = cb.carrier_id	
	LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id	
	WHERE
		(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS o2 WHERE o2.parent_order_id = o.order_id) = 0 
		AND order_status = 1
		AND shipping_status in (1, 6)
		{$condition}		
";

$sum = $db->getRow($sql);

$smarty->assign('orders', $orders);
$smarty->assign('sum', $sum);
$smarty->assign('stores', getStores());
$smarty->assign('payments', getPayments());
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('pager', $pager);
$smarty->assign('all_pay_status', $_CFG['adminvars']['pay_status_for_finance']);

if ($csv) {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","gbk","c2c财务报表") . ".csv");	
	$out = $smarty->fetch('oukooext/c2cfinance_csv.htm');
	echo iconv("UTF-8","gbk", $out);
	exit();	
} else {
	$smarty->display('oukooext/c2cfinance.htm');
}
?>

<?php
function getCondition() {
	$store_id = intval($_REQUEST['store_id']);
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
		
	if ($store_id > 0) {
		$condition .= " AND biaoju_store_id = '{$store_id}'";
	} else {
		$condition .= " AND biaoju_store_id != 0";
	}
	if (strtotime($start) > 0) {
		$condition .= " AND order_time >= '$start'";
	}
	if (strtotime($end) > 0) {
		$condition .= " AND order_time <= '$end'";
	}
	return $condition;
}
?>