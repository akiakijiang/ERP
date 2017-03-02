<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('customer_service_manage_order');
require("function.php");


$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$csv = $_REQUEST['csv'];
if ($csv == null) {
	$limit = "LIMIT $size";
	$offset = "OFFSET $start";
} else {
	admin_priv("admin_other_csv");
}

$condition = getCondition();

$sql = "
	SELECT * FROM {$ecs->table('oukoo_consult')} 
	WHERE 1 {$condition}
	ORDER BY consult_time DESC, consult_id DESC $limit $offset
";
$sqlc = "
	SELECT COUNT(*) FROM {$ecs->table('oukoo_consult')} 
	WHERE 1 {$condition}
";

$consults = $db->getAll($sql);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

$smarty->assign('consults', $consults);
$smarty->assign('pager', $pager);
$smarty->assign('back', $_SERVER['REQUEST_URI']);

if ($csv) {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","售前售后汇总报表") . ".csv");	
	$out = $smarty->fetch('oukooext/oukoo_consult_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();
} else {
	$smarty->display('oukooext/oukoo_consult.htm');
}
?>

<?php
function getCondition() {
	$method = $_REQUEST['method'];
	$result = $_REQUEST['result'];
	$search_text = trim($_REQUEST['search_text']);
	
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	
	$condition = "";
	if ($method != -1 && $method !== null) {
		$condition .= " AND method = '{$method}'";
	}
	if ($result != -1 && $result !== null) {
		$condition .= " AND result = '{$result}'";
	}
	if (strtotime($start) > 0) {
		$condition .= " AND consult_time >= '$start'";
	}
	if (strtotime($end) > 0) {
		$condition .= " AND consult_time <= '$end'";
	}
	if ($search_text != '') {
		$condition .= " AND (customer_name like '%$search_text%' OR consult_area like '%$search_text%' OR action_user like '%$search_text%')";
	}
	return $condition;
}

?>