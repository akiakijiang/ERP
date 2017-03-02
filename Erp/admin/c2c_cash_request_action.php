<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('finance_order');
require("function.php");

$request_id = $_POST['request_id'];
$act = $_POST['act'];
if ($request_id > 0) {
	switch ($act) {
	case 'approve':
		$sql = "UPDATE bj_store_apply_cash_request SET request_status = 'APPROVED' WHERE request_id = '{$request_id}'";
		$db->query($sql);
		
		// 查询每单明细
		$sql = "UPDATE bj_account a, bj_store_apply_cash_request_order ro SET real_ouku_paid_amount = real_ouku_paid_amount + ro.amount WHERE a.order_id = ro.order_id AND ro.request_id = '{$request_id}'";
		$db->query($sql);
		break;
	case 'decline':
		$sql = "UPDATE bj_store_apply_cash_request SET request_status = 'DECLINED' WHERE request_id = '{$request_id}'";
		$db->query($sql);
		break;
	case 'pending';
		$sql = "UPDATE bj_store_apply_cash_request SET request_status = 'PENDING' WHERE request_id = '{$request_id}'";
		$db->query($sql);
		break;
	}
}

$back = $_REQUEST['back'] !== null ? $_REQUEST['back'] : "/";
Header("Location: $back");
?>