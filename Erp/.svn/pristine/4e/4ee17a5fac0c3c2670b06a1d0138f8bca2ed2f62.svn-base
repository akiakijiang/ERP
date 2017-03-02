<?php
define('IN_ECS', true);
require('includes/init.php');

admin_priv('analyze_finance_op');
require("function.php");

$date = $_REQUEST['date'];
$type = $_REQUEST['type'];

$ouku_store = " AND (SELECT COUNT(*) FROM {$ecs->table('order_info')} AS tmo WHERE tmo.parent_order_id = o.order_id) = 0 
				AND o.order_type_id = 'SALE'
				AND pay_status = 2";

$info['huikuan'] = ' AND pay_id >= 10 and pay_id <= 16';
$info['youju']   = ' AND pay_id = 2';


// 获取财务确认到款的时间
$sql = "
	SELECT order_sn, order_id, 
		   IF(pay_time != 0, pay_time, (SELECT action_time FROM {$ecs->table('order_action')} a WHERE o.order_id = a.order_id and pay_status = 2 ORDER BY action_time LIMIT 1)) AS pay_time, 
		   order_time
	FROM {$ecs->table('order_info')} o 
	WHERE 
		1
		$ouku_store
		{$info[$type]}
		AND UNIX_TIMESTAMP(order_time) > UNIX_TIMESTAMP('$date') 
		AND UNIX_TIMESTAMP(order_time) < UNIX_TIMESTAMP(DATE_ADD('$date',INTERVAL 1 DAY)) 
";
$values = $db->getAll($sql);
foreach ($values as $key=>$order) {
	$dif_time = calculate_diff_time($order['order_time'], $order['pay_time']);
	$values[$key]['diff_time'] = $dif_time;
	if ($dif_time > 0)
		$sum['sum_second'] += $dif_time;
}		

$smarty->assign('values', $values);
$smarty->assign('sum', $sum);
$smarty->display('oukooext/analyze_finance_op_detail.htm');


?>