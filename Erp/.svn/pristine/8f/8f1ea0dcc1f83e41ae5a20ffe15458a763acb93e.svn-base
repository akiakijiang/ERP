<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('cw_finance_yingshou_main');
require_once("function.php");

$size = $_REQUEST['size'] ? $_REQUEST['size'] : 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$shipping_id = $_REQUEST["shipping_id"];
if ($shipping_id){
	$condition .= " AND shipping_id = $shipping_id"; 
}
$bill_time = $_REQUEST["bill_time"];
if ($bill_time) {
	$condition .= " AND bill_time = $bill_time";
}

$limit = "LIMIT $size";
$offset = "OFFSET $start";

/* 取得快递公司结算的数据库 */
$finance_sf_db = new cls_mysql($finance_sf_db_host, 
							   $finance_sf_db_user, 
							   $finance_sf_db_pass, 
							   $finance_sf_db_name);

$sql = "SELECT seq, shipping_id, type, bill_time, times,	status
		FROM job_schedule WHERE 1 $condition order by bill_time DESC $limit $offset";
$sqlc = "SELECT COUNT(*) FROM job_schedule WHERE 1 $condition";

$count = $finance_sf_db->getOne($sqlc);
$pager = Pager($count, $size, $page);
$jobs = $finance_sf_db->getAll($sql);

$smarty->assign('jobs', $jobs);
$smarty->assign('pager', $pager);
$smarty->display('oukooext/finance_dshk.htm');





?>