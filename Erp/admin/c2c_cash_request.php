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
} else {
	admin_priv('4cw_finance_manage_order_csv');
}

$condition = getCondition();

$sql = "
	SELECT s.name AS store_name, r.* FROM bj_store_apply_cash_request r, bj_store_bank b, bj_store s
	WHERE 1
		AND r.store_bank_id = b.store_bank_id
		AND b.store_id = s.store_id
		{$condition}
	ORDER BY s.store_id, r.request_time DESC $limit $offset
";
$sqlc = "
	SELECT COUNT(*) FROM bj_store_apply_cash_request r, bj_store_bank b, bj_store s
	WHERE 1
		AND r.store_bank_id = b.store_bank_id
		AND b.store_id = s.store_id
		{$condition}
";

$requests = $db->getAll($sql);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

$smarty->assign('requests', $requests);
$smarty->assign('count', $count);
$smarty->assign('pager', $pager);
$smarty->assign('stores', getStores());
$smarty->assign('back', $_SERVER['REQUEST_URI']);

if ($csv) {
	admin_priv('4cw_finance_manage_order_csv');
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","镖局提现申请列表") . ".csv");	
	$out = $smarty->fetch('oukooext/c2c_cash_request_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();
} else {
	$smarty->display('oukooext/c2c_cash_request.htm');
}
?>

<?php
function getCondition() {
	$store_id = $_REQUEST['store_id'];
	$request_status = $_REQUEST['request_status'];
	$act = $_REQUEST['act'];
	$condition = '';
	
	if ($store_id !== null && $store_id != -1) {
		$condition .= " AND s.store_id = '$store_id'";		
	}
	if ($request_status !== null && $request_status != -1) {
		$condition .= " AND request_status = '$request_status'";		
	}
	if ($condition == '' && $act != 'search') {
		$condition = " AND request_status = 'PENDING'";
	}
	
	return $condition;
}
?>