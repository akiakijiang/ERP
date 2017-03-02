<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('finance_order');
require_once("function.php");


$condition = getCondition();
$csv = $_REQUEST['csv'];
$submit = $_REQUEST['submit'];

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;



if ($submit == '提交') {
    $order_sns = $_POST["order_sn"];
    if (is_array($order_sns)) {
	    foreach ($order_sns AS $order_sn) {
			$clear_type = $_POST["clear_type_{$order_sn}"];

		    $action_time = date("Y-m-d H:i:s");
			$sql = "UPDATE {$ecs->table('order_info')} SET finance_clear_type = '{$clear_type}' WHERE order_sn = '{$order_sn}'";
			$db->query($sql);
			$sqls[] = $sql;
		}
	}
	Header("Location: $back"); 
}

if ($csv == null) {
	$limit = "LIMIT $size";
	$offset = "OFFSET $start";
} else {
	admin_priv("admin_other_csv");
}

$sql = "
	SELECT order_id, order_sn, order_time, order_status, order_amount, goods_amount, 
	shipping_fee, shipping_status, shipping_name, integral_money, bonus, consignee, 
	pay_id, pay_status, real_paid, real_shipping_fee, proxy_amount, pay_method, 
	bill_no, pay_name, is_finance_clear, finance_clear_type, postscript
	FROM {$ecs->table('order_info')} AS info 
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = carrier_bill_id 
	WHERE is_finance_clear = 1 AND biaoju_store_id = 0 AND order_amount > real_paid
		{$condition}
	ORDER BY (order_amount-real_paid) DESC $limit $offset";

$sqlc = "
	SELECT COUNT(*) 
	FROM {$ecs->table('order_info')} AS info 
	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = carrier_bill_id 
	WHERE is_finance_clear = 1 AND biaoju_store_id = 0 AND order_amount > real_paid
		{$condition}";


$orders = $db->getAll($sql);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

foreach ($orders as $key => $order) {
	$sql = "
		SELECT action_user, action_time, action_note FROM {$ecs->table('order_action')} 
		WHERE order_id = '{$order['order_id']}' 
		  AND action_note != ''
		  order by action_time desc
	";
	$notes = $db->getAll($sql);
	
	$all_note = "";
	foreach ($notes as $note) {
		$all_note .= $note['action_user'].":".$note['action_note']."<br>";
	}
	
	// 备注+客户留言
	if (Trim($order['postscript']) == "") {
		$orders[$key]['note'] = "{$all_note}<br>{$order['postscript']}";
	} else {
		$orders[$key]['note'] = "{$all_note}*****************************<br>{$order['postscript']}";
	}
	
	if (strlen($order['order_sn']) > 10) {
		$orders[$key]['order_special'] = true; 
		$orders[$key]['final_amount'] = $order['order_amount'];
	} else {
		$orders[$key]['final_amount'] = $order['goods_amount'] + $order['shipping_fee'] + $order['integral_money'] + $order['bonus'];
	}
	$orders[$key]['final_minus_order_amount'] = round(($orders[$key]['final_amount'] - $orders[$key]['real_paid']),2);
	$orders[$key]['order_status_name'] = get_order_status($order['order_status']);
	$orders[$key]['shipping_status_name'] = get_shipping_status($order['shipping_status']);
	$orders[$key]['pay_status_name'] = get_pay_status($order['pay_status']);
	
	
}

$sql = "select finance_clear_type, type_name, description, is_written_of
		  from ecs_oukoo_finance_clear";
$finance_clear = $db->getAll($sql);

$smarty->assign('finance_clear', $finance_clear);
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('orders', $orders);
$smarty->assign('pager', $pager);
$smarty->assign('all_pay_status', $_CFG['adminvars']['pay_status']);

if ($csv) {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","清算列表") . ".csv");	
	$out = $smarty->fetch('oukooext/finance_dz_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();	
} else {
	$smarty->display('oukooext/finance_dz.htm');
}


function getCondition() {
	global $ecs;
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];	
	$seq = $_REQUEST['seq'];
	$finance_clear_type = $_REQUEST['type'];
	
	$act = $_REQUEST['act'];
	
	$condition = "";
	if (strtotime($start) > 0) {
		$condition .= " AND order_time >= '$start'";
	}
	if (strtotime($end) > 0) {
		$end = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end)));
		$condition .= " AND order_time <= '$end'";
	}
	$condition .= " AND finance_clear_type = $finance_clear_type";
	
	return $condition;
}

?>