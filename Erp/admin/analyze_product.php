<?php
define('IN_ECS', true);
require('includes/init.php');
party_priv(PARTY_OUKU);
admin_priv('analyze_product');
require("function.php");

$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

// 得到一系列的日期
$dates = get_dates($start, $end);

$values = array();
$sum = array();

$store_info = "(SELECT COUNT(*) FROM {$ecs->table('order_info')} AS tmo WHERE tmo.parent_order_id = o.order_id) = 0 
				AND order_type_id != 'RMA_RETURN'
				AND biaoju_store_id in (0, 7)";

foreach ($dates as $date) {
	$values[$date] = array();

	// 订单数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('order_info')} o 
		WHERE 
			order_time > '$date' 
			AND order_time < DATE_ADD('$date',INTERVAL 1 DAY) 
			AND  o.order_type_id = 'SALE'
	";
	$values[$date]['order_count'] = $slave_db->getOne($sql);
	$sum['order_count'] += $values[$date]['order_count'];
	
	// 确认订单数
	$sql = "
		SELECT COUNT(*) FROM {$ecs->table('order_info')} o 
		WHERE 
			order_status = 1
			AND order_time > '$date' 
			AND order_time < DATE_ADD('$date',INTERVAL 1 DAY) 
			AND o.order_type_id = 'SALE'
	";
	$values[$date]['confirm_order_count'] = $slave_db->getOne($sql);
	$sum['confirm_order_count'] += $values[$date]['confirm_order_count'];
}
$smarty->assign('start', $dates[count($dates) - 1]);
$smarty->assign('end', $dates[0]);
$smarty->assign('values', $values);
$smarty->assign('dates', $dates);
$smarty->assign('sum', $sum);
$smarty->display('oukooext/analyze_product.htm');
?>