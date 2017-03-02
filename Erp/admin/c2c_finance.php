<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('finance_order');
require("function.php");

$csv = $_REQUEST['csv'];
$submit = $_REQUEST['submit'];

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
	$limit = "LIMIT $size";
	$offset = "OFFSET $start";
} else {
	admin_priv('4cw_finance_manage_order_csv');
}
$condition .= getCondition();

if ($submit == '批量欧酷已收款') {
	$sql = "UPDATE {$ecs->table('order_info')} AS o, bj_account AS a SET pay_status = '2' WHERE a.order_id = o.order_id $condition";
	$db->query($sql);
	$sql = "SELECT o.order_id FROM {$ecs->table('order_info')} AS o, bj_account a  WHERE a.order_id = o.order_id $condition ";
	$order_ids = $db->getAll($sql);
	// foreach ($order_ids as $order_id) {
	//     // update order mixed status 
 //        include_once('includes/lib_order_mixed_status.php');
 //        update_order_mixed_status($order_id, array('pay_status' => 'paid'), 'worker');
	// }
	
	$url = $_SERVER['REQUEST_URI'];
	$new_url = remove_param_in_url($url, 'submit');
	header("location: $new_url");
}
if ($submit == '批量欧酷已付款') {
	$sql = "UPDATE {$ecs->table('order_info')} AS o, bj_account AS a SET a.is_ouku_paid = 'YES' WHERE a.order_id = o.order_id $condition";
	$db->query($sql);
	$url = $_SERVER['REQUEST_URI'];
	$new_url = remove_param_in_url($url, 'submit');
	header("location: $new_url");	
}
if ($submit == '批量欧酷实付等于应付') {
	$sql = "UPDATE {$ecs->table('order_info')} AS o, bj_account AS a SET real_ouku_paid_amount = (order_amount - IF(pay_status = '2', real_shipping_fee + proxy_amount, shipping_fee)) WHERE a.order_id = o.order_id $condition";
	$db->query($sql);
	$url = $_SERVER['REQUEST_URI'];
	$new_url = remove_param_in_url($url, 'submit');
	header("location: $new_url");	
}

$sql = "
	SELECT o.*, c.name AS carrier_name, cb.bill_no, p.pay_name, a.*, o.order_id, s.name store_name, IF(shipping_status NOT IN (0, 8), IF(pay_status = 2, real_shipping_fee + proxy_amount, shipping_fee), 0) AS fix_shipping_fee, IF(order_status = 1 AND shipping_status in (1, 2, 6) , IF(pay_status = 2, real_paid, order_amount), 0) AS fix_order_amount
	FROM {$ecs->table('order_info')} AS o
	LEFT JOIN bj_account a ON o.order_id = a.order_id
	LEFT JOIN bj_store s ON o.biaoju_store_id = s.store_id
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
	LEFT JOIN {$ecs->table('carrier')} c ON c.carrier_id = cb.carrier_id
	LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id
	WHERE 
		(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS o2 WHERE o2.parent_order_id = o.order_id) = 0 
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

// 汇总信息
$sql = "
	SELECT SUM(order_amount) order_amount, SUM(shipping_fee) shipping_fee, SUM(real_shipping_fee) real_shipping_fee, SUM(proxy_amount) proxy_amount, SUM(real_ouku_collect_amount) real_ouku_collect_amount, SUM(real_paid) real_paid, SUM(real_ouku_paid_amount) real_ouku_paid_amount, SUM(ouku_settle_amount) ouku_settle_amount, SUM(fix_shipping_fee) fix_shipping_fee, SUM(fix_order_amount) fix_order_amount
	FROM (SELECT tpo.*, IF(shipping_status NOT IN (0, 8), IF(pay_status = 2, real_shipping_fee + proxy_amount, shipping_fee), 0) AS fix_shipping_fee, IF(order_status = 1 AND shipping_status in (1, 2, 6) , IF(pay_status = 2, real_paid, order_amount), 0) AS fix_order_amount FROM {$ecs->table('order_info')} tpo LEFT JOIN bj_account ta ON tpo.order_id = ta.order_id) AS o 
	LEFT JOIN bj_account a ON o.order_id = a.order_id
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
	LEFT JOIN {$ecs->table('carrier')} c ON c.carrier_id = cb.carrier_id	
	LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id	
	WHERE
		(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS o2 WHERE o2.parent_order_id = o.order_id) = 0 
		{$condition}		
";
$sum = $db->getRow($sql);

$smarty->assign('orders', $orders);
$smarty->assign('sum', $sum);
$smarty->assign('stores', getStores());
$smarty->assign('payments', getPayments());
$smarty->assign('carriers', getCarriers());
$smarty->assign('all_pay_status', $_CFG['adminvars']['pay_status']);
$smarty->assign('all_shipping_status', $_CFG['adminvars']['shipping_status']);
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('pager', $pager);
$smarty->assign('all_pay_status', $_CFG['adminvars']['pay_status']);

if ($csv) {
	// 未写
	admin_priv('4cw_finance_manage_order_csv');
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","c2c财务报表") . ".csv");	
	$out = $smarty->fetch('oukooext/c2c_finance_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();	
} else {
	$smarty->display('oukooext/c2c_finance.htm');
}
?>

<?php
function getCondition() {
	$store_id = intval($_REQUEST['store_id']);
	$order_status = $_REQUEST['order_status'];
	$pay_id = $_REQUEST['pay_id'];
	$pay_status = $_REQUEST['pay_status'];	
	$carrier_id = $_REQUEST['carrier_id'];
	$shipping_status = $_REQUEST['shipping_status'];
	$red_notice = $_REQUEST['red_notice'];
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$search_text = trim($_REQUEST['search_text']);
	$act = trim($_REQUEST['act']);
	
	if ($store_id > 0) {
		$condition .= " AND biaoju_store_id = '{$store_id}'";
	} else {
		$condition .= " AND biaoju_store_id != 0";
	}
	if ($pay_id != null && $pay_id != -1) {
		$condition .= " AND o.pay_id = '$pay_id'";		
	}
	if ($pay_status != null && $pay_status != -1) {
		$condition .= " AND pay_status = '$pay_status'";		
	}
	if ($carrier_id != null && $carrier_id != -1) {
		$condition .= " AND cb.carrier_id = '$carrier_id'";		
	}	
	if ($shipping_status != null && $shipping_status != -1) {
		$condition .= " AND shipping_status = '$shipping_status'";		
	}
	if ($order_status !== null && $order_status != -1) {
		$condition .= " AND order_status = '$order_status'";
	}
	
	if ($red_notice != null && $red_notice != -1) {
		switch ($red_notice) {
			case 1:
				$condition .= " AND shipping_status = 0 AND order_time < DATE_ADD(now(), INTERVAL -48 HOUR)";
				break;
			case 2:
				$condition .= " AND is_ouku_paid != 'YES'";
				break;
			case 3:
				$condition .= " AND o.pay_id != 1 AND proxy_amount = 0";
				break;
		}
	}
		
	if (strtotime($start) > 0) {
		$condition .= " AND order_time >= '$start'";
	}
	if (strtotime($end) > 0) {
		$end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
		$condition .= " AND order_time <= '$end'";
	}
	if ($search_text != '') {
		$condition .= parseSearchText($search_text);
	}
	if ($act != "search") {
		$condition .= " AND order_status = 1";
	}
	return $condition;
}

?>