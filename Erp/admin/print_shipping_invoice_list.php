<?php
define('IN_ECS', true);
require('includes/init.php');

require("function.php");

$act = $_REQUEST['act'];

$size = $_REQUEST['size'] ? $_REQUEST['size'] : 15;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$limit = "LIMIT $size";
$offset = "OFFSET $start";

$condition = get_condition();

if ($act == "delete") {
	$print_order_invoice_id = $_REQUEST["print_order_invoice_id"];
	$sql = "
		UPDATE print_order_invoice SET status = 'DELETED'
		WHERE 
			print_order_invoice_id = '{$print_order_invoice_id}'
	";
	$db->query($sql);
	$back = $_REQUEST['back'];
	header("location: {$back}");
}


$sql = "
	SELECT * FROM print_order_invoice p
	LEFT JOIN {$ecs->table('order_info')} o ON o.order_id = p.order_id
	WHERE 
		1
		{$condition}
	ORDER BY print_order_invoice_id DESC
	$limit $offset
";
$sqlc = "
	SELECT COUNT(*) FROM print_order_invoice p
	LEFT JOIN {$ecs->table('order_info')} o ON o.order_id = p.order_id
	WHERE 
		1
		{$condition}
";

$orders = $db->getAll($sql);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('orders', $orders);
$smarty->assign('pager', $pager);

$smarty->display('oukooext/print_shipping_invoice_list.htm');

function get_condition() {
	$condition = "";
	$status = $_REQUEST['status'];
	$order_sn = trim($_REQUEST['order_sn']);
	
	$act = $_REQUEST['act'];
	
	if ($status != null && $status != -1) {
		$condition .= " AND status = '$status'";
	}
	if ($order_sn != '') {
		$condition .= " AND order_sn = '{$order_sn}'";
	}
	
	if ($act != 'search') {
		$condition .= " AND status = 'PENDING'";
	}
	# 添加party_id
	$condition .= " AND ". party_sql('o.party_id');
	return $condition;
}
?>