<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('cw_c2c_buy_sale', 'cg_c2c_buy_sale');
require("function.php");

$act = $_REQUEST['act'];
$bill_id = $_REQUEST['bill_id'];
if ($act == 'has_invoice') {
	$sql = "UPDATE {$ecs->table('oukoo_inside_c2c_bill')} SET has_invoice = 'YES' WHERE bill_id = '$bill_id'";
	$db->query($sql);
}
if ($act == 'is_dif_returned') {
	$sql = "UPDATE {$ecs->table('oukoo_inside_c2c_bill')} SET is_dif_returned = 'YES' WHERE bill_id = '$bill_id'";
	$db->query($sql);
}

$csv = $_REQUEST['csv'];
$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
	$limit = "LIMIT $size";
	$offset = "OFFSET $start";
} else {
	admin_priv("admin_other_csv");
}

$condition = getCondition();
$sql = "
	SELECT * FROM {$ecs->table('oukoo_inside_c2c_bill')}
	WHERE
		type = 'C2C'
		$condition
	ORDER BY date DESC
	$limit $offset
";
$sqlc = "
	SELECT COUNT(*) FROM {$ecs->table('oukoo_inside_c2c_bill')}
	WHERE
		type = 'C2C'
		$condition
";
$sqls = "
	SELECT sum(return_amount) as amount FROM {$ecs->table('oukoo_inside_c2c_bill')}
	WHERE
		type = 'C2C'
		$condition
";
$bills = $db->getAll($sql);
$count = $db->getOne($sqlc);
$sum = $db->getOne($sqls);
$pager = Pager($count, $size, $page);


$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('bills', $bills);
$smarty->assign('pager', $pager);
$smarty->assign('sum', $sum);

if ($csv) {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","C2C内部对帐表") . ".csv");	
	$out = $smarty->fetch('oukooext/c2c_buy_sale_return_money_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();
} else {
	$smarty->display('oukooext/c2c_buy_sale_return_money.htm');
}
?>

<?php
function getCondition() {
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$is_returned = $_REQUEST['type'];
	$condition = "";
	
	if ($start > 0) {
		$condition .= " AND date >= {$start}";
	}
	if ($end > 0) {
		$condition .= " AND date <= {$end}";
	}
	if ($is_returned == 'YES') {
		$condition .= " AND is_dif_returned = 'YES'";
	} 
	if ($is_returned == 'NO') {
		$condition .= " AND is_dif_returned != 'YES'";
	}
	return $condition;
}
?>