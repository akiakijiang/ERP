<?php
define('IN_ECS', true);
require('includes/init.php');
party_priv(PARTY_OUKU);
admin_priv('tj_analyze_finance_op');
require("function.php");

$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

// 得到一系列的日期
$dates = get_dates($start, $end);
$values = array();
$sum = array();


$one_day_seconds = 60 * 60 * 24;
$two_day_seconds = $one_day_seconds * 2;
$ten_day_seconds = $one_day_seconds * 10;

$ouku_store = " AND (SELECT COUNT(*) FROM {$ecs->table('order_info')} AS tmo WHERE tmo.parent_order_id = o.order_id) = 0 
				AND o.order_type_id = 'SALE'
				AND pay_status = 2";

$info[] = array('type'=>'huikuan', 'name'=>'银行汇款/转账', 'cond'=>' AND pay_id >= 10 and pay_id <= 16');
$info[] = array('type'=>'youju', 'name'=>'邮局汇款', 'cond'=>' AND pay_id = 2');

foreach ($info as $key => $one_info) {
	foreach ($dates as $date) {
		$values[$one_info['type']][$date] = array();
		// 银行汇款
		$sql = "
			SELECT COUNT(*) FROM {$ecs->table('order_info')} o 
			WHERE 
				1
				$ouku_store
				{$one_info['cond']}
				AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$date') 
				AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY)) 
		";
		$values[$one_info['type']][$date]['pay_order_count'] = $db->getOne($sql);
		$sum[$one_info['type']]['pay_order_count'] += $values[$one_info['type']][$date]['pay_order_count'];
		
		// 获取财务确认到款的时间
		$sql = "
			SELECT order_sn, order_id, 
				   IF(pay_time != 0, pay_time, (SELECT action_time FROM {$ecs->table('order_action')} a WHERE o.order_id = a.order_id and pay_status = 2 ORDER BY action_time LIMIT 1)) AS pay_time, 
				   order_time
			FROM {$ecs->table('order_info')} o 
			WHERE 
				1
				$ouku_store
				{$one_info['cond']}
				AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$date') 
				AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY)) 
		";
		$values[$one_info['type']][$date]['orders'] = $db->getAll($sql);
		foreach ($values[$one_info['type']][$date]['orders'] as $order) {
			$dif_time = calculate_diff_time($order['order_time'], $order['pay_time']);
			if ($dif_time > 0)
				$sum[$one_info['type']][$date]['sum_second'] += $dif_time;
		}	
		$sum[$one_info['type']][$date]['sum_hour'] = round($sum[$one_info['type']][$date]['sum_second']/3600,1);
		if ($values[$one_info['type']][$date]['pay_order_count'] == 0) {
			$sum[$one_info['type']][$date]['avg_hour'] = 'N/A';
		} else {
			$sum[$one_info['type']][$date]['avg_hour'] = round($sum[$one_info['type']][$date]['sum_hour']/$values[$one_info['type']][$date]['pay_order_count'],1);
		}
		$sum[$one_info['type']]['sum_second'] += $sum[$one_info['type']][$date]['sum_second'];
	}
	
	$sum[$one_info['type']]['sum_hour'] = round($sum[$one_info['type']]['sum_second']/3600,1);
	if ($sum[$one_info['type']]['pay_order_count'] == 0) {
		$sum[$one_info['type']]['avg_hour'] = 'N/A';
	} else {
		$sum[$one_info['type']]['avg_hour'] = round($sum[$one_info['type']]['sum_hour']/$sum[$one_info['type']]['pay_order_count'],1);
	}
}

$smarty->assign('info', $info);
$smarty->assign('values', $values);
$smarty->assign('dates', $dates);
$smarty->assign('sum', $sum);
$smarty->display('oukooext/analyze_finance_op.htm');

?>