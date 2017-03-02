<?php
define('IN_ECS', true);
require('includes/init.php');
require('includes/alipay/alipay.php');
require_once("function.php");

$sql = "";

$batch_order_sn = trim($_REQUEST['batch_order_sn']);
if ($batch_order_sn != '') {
	$order_sns = preg_split('/[\s]+/', $batch_order_sn);
	foreach ($order_sns as $key => $order_sn) {
		if (trim($order_sn) == '') {
			unset($order_sns[$key]);
		}
	}
	$condition .= " AND " . db_create_in($order_sns, "order_sn");
} else {
	$condition .= " AND 0";
}

$sql = "
	SELECT *, c.name AS carrier_name, '支付宝' AS pay_name FROM {$ecs->table('order_info')} o, {$ecs->table('carrier_bill')} cb, {$ecs->table('carrier')} c, {$ecs->table('pay_log')} pl
	WHERE
		o.carrier_bill_id = cb.bill_id
		AND cb.carrier_id = c.carrier_id
		AND pl.order_id = o.order_id
		AND pay_id = 5
		AND order_status = 1
		AND shipping_status IN (1, 2)
		AND pay_status = 2
		AND bill_no != ''
		{$condition}
	ORDER BY order_time DESC
";
$orders = $db->getAll($sql);

if ($_REQUEST['submit'] == "批量发货") {
	foreach ($orders as $key => $order) {
		$match = array();
		// 分析中转地址格式
		preg_match ("/\ntrade_no=([0-9]+)\n/", $order['request_data'], $match);
		$order['trade_no'] = $match[1];
		if ($order['trade_no'] != '') {
			$result = alipay_shipping($order['trade_no'], $order['carrier_name'], $order['bill_no']);
			// 发货结果
			if (strpos($result, "<is_success>T</is_success>") === false) {
				$orders[$key]['result'] = false;
			} else {
				$orders[$key]['result'] = true;
			}
		} else {
			$orders[$key]['result'] = "trade_no非法";
		}

	}
}

$smarty->assign('orders', $orders);
$smarty->display('oukooext/batch_alipay_shipping.htm');

?>