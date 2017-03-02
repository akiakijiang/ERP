<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('c2c_dc','finance_order');
require("function.php");
$order_ids = $_REQUEST['order_id'];
$type = $_REQUEST['type'];
$action_time = date("Y-m-d H:i:s");

if (is_array($order_ids)) {
	foreach ($order_ids as $order_id) {
		$account_id = $_POST["account_id-$order_id"];;
		$attributes['real_ouku_collect_amount'] = $_POST["real_ouku_collect_amount-$order_id"];
		$attributes['is_ouku_paid'] = $_POST["is_ouku_paid-$order_id"];
		$attributes['real_ouku_paid_amount'] = $_POST["real_ouku_paid_amount-$order_id"];
		$attributes['note'] = $_POST["note-$order_id"];

		if ($account_id) {
			$pair = array();
			foreach ($attributes AS $key=>$value) {
				if ($value !== null) {
					$pair[] = "$key='$value'";
				}
			}
			$sql = "UPDATE bj_account AS b SET order_id = '{$order_id}', " . join(',', $pair) . " WHERE account_id = '$account_id'";
		} else {
			// 网上支付：手续费1%、实际代收金额＝订单金额
			$sql = "SELECT * FROM {$ecs->table('order_info')} WHERE order_id = '$order_id'";
			$order = $db->getRow($sql);
			if ($order['pay_id'] == 3 || $order['pay_id'] == 4 || $order['pay_id'] == 8 || $order['pay_id'] == 9) {
				$sql = "UPDATE {$ecs->table('order_info')} SET proxy_amount = order_amount * 0.01 WHERE order_id = '{$order_id}'";
				$db->query($sql);
				$attributes['real_paid'] = $order['order_amount'];
			}
						
			$keys = $values = array();
			foreach ($attributes AS $key=>$value) {
				if ($value !== null) {
					$keys[] = $key;
					$values[] = "'$value'";
				}
			}
			$sql = "INSERT INTO bj_account (order_id, " . join(',', $keys) . ") VALUES ('$order_id', " . join(',', $values) . ")";
			

		}
		$db->query($sql);

		
		if ($type == 'finance') {
			//处理订单信息
			// 查出订单的信息
			$sql = "SELECT pay_status FROM {$ecs->table('order_info')} WHERE order_id = '$order_id'";
			$order = $db->getRow($sql);	
			$pay_status = $_POST["pay_status-{$order_id}"];
			$proxy_amount = $_POST["proxy_amount-{$order_id}"];
			$real_shipping_fee = $_POST["real_shipping_fee-{$order_id}"];
			$pay_method = $_POST["pay_method-$order_id"];
			$shipping_status = $_POST["shipping_status-{$order_id}"];
			$real_paid = $_POST["real_paid-$order_id"];
			
			if ($pay_status != $order['pay_status']) {		
				$action_sql = "INSERT INTO " . $ecs->table('order_action') . "(order_id, pay_status, action_time, action_note, action_user) VALUES('{$order_id}', '$pay_status', '$action_time', '{$attributes['note']}', '{$_SESSION['admin_name']}')";
				$db->query($action_sql);
				if ($pay_status == 2) {
				    // update order mixed status 
                    // include_once('includes/lib_order_mixed_status.php');
                    // update_order_mixed_status($order_id, array('pay_status' => 'paid'), 'worker');
				}
			}
			/*
			if ($shipping_status == 3) {
				$sql = "UPDATE {$ecs->table('order_info')} SET order_status = 4, shipping_status = '{$shipping_status}', pay_status = '{$pay_status}', real_paid = '{$real_paid}', real_shipping_fee = '{$real_shipping_fee}', proxy_amount = '{$proxy_amount}', pay_method = '{$pay_method}' WHERE order_id = '{$order_id}'";
			} else {
				$sql = "UPDATE {$ecs->table('order_info')} SET shipping_status = '{$shipping_status}', pay_status = '{$pay_status}', real_paid = '{$real_paid}', real_shipping_fee = '{$real_shipping_fee}', proxy_amount = '{$proxy_amount}', pay_method = '{$pay_method}' WHERE order_id = '{$order_id}'";
			}
			*/		
			if ($shipping_status == 3) {
				$sql = "UPDATE {$ecs->table('order_info')} SET order_status = 4, shipping_status = '{$shipping_status}', pay_status = '{$pay_status}', real_shipping_fee = '{$real_shipping_fee}', proxy_amount = '{$proxy_amount}', pay_method = '{$pay_method}' WHERE order_id = '{$order_id}'";
			} else {
				$sql = "UPDATE {$ecs->table('order_info')} SET shipping_status = '{$shipping_status}', pay_status = '{$pay_status}', real_shipping_fee = '{$real_shipping_fee}', proxy_amount = '{$proxy_amount}', pay_method = '{$pay_method}' WHERE order_id = '{$order_id}'";
			}
			$db->query($sql);
		}

		if ($type == 'dc') {
			$sql = "SELECT shipping_status FROM {$ecs->table('order_info')} WHERE order_id = '$order_id'";
			$order = $db->getRow($sql);
			$shipping_status = $_POST["shipping_status-{$order_id}"];
			$bill_no = $_POST["bill_no-{$order_id}"];
			
			if ($shipping_status != $order['shipping_status']) {
				// killed by Sinri 20160105
				// $sql = "UPDATE {$ecs->table('order_info')} a, {$ecs->table('carrier_bill')} b 
				// 		SET a.shipping_status = '{$shipping_status}',
				// 			b.bill_no = '{$bill_no}' 
				// 		WHERE a.order_id = '{$order_id}' and a.carrier_bill_id = b.bill_id";
				// $db->query($sql);				
				$action_sql = "INSERT INTO " . $ecs->table('order_action') . "(order_id, shipping_status, action_time, action_note, action_user) VALUES('{$order_id}', '$shipping_status', '$action_time', '{$attributes['note']}', '{$_SESSION['admin_name']}')";
				$db->query($action_sql);				
			}
			
			// killed by Sinri 20160105
			// $sql = "UPDATE {$ecs->table('order_info')} a, {$ecs->table('carrier_bill')} b 
			// 			SET b.bill_no = '{$bill_no}' 
			// 			WHERE a.order_id = '{$order_id}' and a.carrier_bill_id = b.bill_id";
			// 			pp($sql);
			// $db->query($sql);		
		}
	}
}
$back = $_REQUEST['back'] !== null ? $_REQUEST['back'] : "/";
Header("Location: $back");
?>