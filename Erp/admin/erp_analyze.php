<?php
define('IN_ECS', true);
require('includes/init.php');

admin_priv('all');
require("function.php");

$condition = getCondition();

$sql = "SELECT * FROM {$ecs->table('oukoo_analyze_attribute')} ORDER BY sort_order, attr_id";
$attributes = $db->getAll($sql);

foreach ($attributes as $key => $attribute) {
	$sql = "
		SELECT * FROM {$ecs->table('oukoo_analyze')}
		WHERE attr_id = '{$attribute['attr_id']}' {$condition}
		ORDER BY refer_time DESC, analyze_id
	";
	$values = $db->getAll($sql);
	foreach ($values as $value) {
		$attributes[$key][$value['refer_time']] = $value;
	}
}

$sql = "SELECT DISTINCT refer_time FROM {$ecs->table('oukoo_analyze')} WHERE 1 {$condition} ORDER BY refer_time DESC";
$dates = $db->getCol($sql);

$smarty->assign('dates', $dates);
$smarty->assign('attributes', $attributes);
$smarty->display('oukooext/erp_analyze.htm');

?>

<?php
function getCondition() {
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$act = $_REQUEST['act'];
	
	$condition = "";
	
	if (strtotime($start) > 0) {
		$condition .= " AND refer_time >= '$start'";
	}
	if (strtotime($end) > 0) {
		$condition .= " AND refer_time <= '$end'";
	}
	
	if ($act != 'search') {
		$condition .= " AND refer_time <= now() AND refer_time >= DATE_ADD(now(), INTERVAL -1 MONTH)";
	}
	
	return $condition;
}
?>