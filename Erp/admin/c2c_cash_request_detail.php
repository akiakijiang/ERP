<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('finance_order');
require("function.php");

$csv = $_REQUEST['csv'];
$request_id = intval($_REQUEST['request_id']);

$sql = "SELECT o.*, ro.* FROM bj_store_apply_cash_request_order ro LEFT JOIN {$ecs->table('order_info')} o ON ro.order_id = o.order_id WHERE request_id = '$request_id'";
$request_orders = $db->getAll($sql);

$sql = "
	SELECT * 
	FROM bj_store_apply_cash_request r
	LEFT JOIN bj_store_bank sb ON sb.store_bank_id = r.store_bank_id
	WHERE 
		request_id = '$request_id'
";
$request = $db->getRow($sql);

$smarty->assign('request', $request);
$smarty->assign('request_orders', $request_orders);

if ($csv) {
	admin_priv('4cw_finance_manage_order_csv');
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","对帐详情") . ".csv");	
	$out = $smarty->fetch('oukooext/c2c_cash_request_detail_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();	
} else {
	$smarty->display('oukooext/c2c_cash_request_detail.htm');
}

?>