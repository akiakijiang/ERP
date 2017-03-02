<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('cw_c2c_buy_sale');
require("function.php");
include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

$csv = $_REQUEST['csv'];
$back = $_REQUEST['back'] !== null ? $_REQUEST['back'] : "/";

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

if ($csv == null) {
	$limit = "LIMIT $size";
	$offset = "OFFSET $start";
} else {
	admin_priv('4cw_c2c_buy_sale_csv');
}

$purchase_bill_id = $_REQUEST['purchase_bill_id'];
$sql = "SELECT * from {$ecs->table('purchase_bill')} where purchase_bill_id = {$purchase_bill_id} and status = 'P_PAID'";
$bill = $db->getRow($sql);
if ($bill != null) {
	$sql = "
		INSERT INTO {$ecs->table('oukoo_inside_c2c_bill')} 
		(date, amount, return_amount, rebate_amount, prepayment_amount, user, type, purchaser, bank_no, paid_type, currency) VALUES
		(NOW(), '{$bill['amount']}', '{$bill['return_amount']}', '{$bill['rebate_amount']}', '{$bill['prepayment_amount']}', '{$_SESSION['admin_name']}', '{$bill['type']}', '{$_REQUEST['purchaser_name']}','{$_REQUEST['bank_no']}', '{$bill['paid_type']}', '{$bill['currency']}')
	";
	$db->query($sql);
	$finance_bill_id = $db->insert_id();
	$sqls[] = $sql;
	
	$sql = "select order_goods_id from ecshop.order_bill_mapping  where purchase_bill_id = {$purchase_bill_id} group by order_goods_id";
	$orderGoodsIds = array();
	$order_goods_ids = $db->getCol($sql);
	foreach($order_goods_ids as $order_goods_id) {
		$orderGoodsIds[] = $order_goods_id;
	}
	$tmp = join(", ", $orderGoodsIds);
	
	$sqls = array();
	if ($tmp != '') {
		$sql = "
			UPDATE ecshop.supplier_return_order_info
			SET is_finance_paid = 'YES'
			WHERE 
				order_goods_id in ({$tmp})
		";
		$db->query($sql);
		$sqls[] = $sql;
		
		$sql = "UPDATE romeo.purchase_order_info 
		        SET is_finance_paid = 'YES'
				WHERE order_goods_id in ({$tmp})
		";
		$db->query($sql);
		$sqls[] = $sql;
	}
	$sql = "UPDATE ecshop.order_bill_mapping SET oukoo_inside_c2c_bill_id = '{$finance_bill_id}' where purchase_bill_id = {$purchase_bill_id}";
	$db->query($sql);
	$sqls[] = $sql;
	
	$sql = "UPDATE {$ecs->table('purchase_bill')} SET status = 'F_PAID' where purchase_bill_id = {$purchase_bill_id}";
	$db->query($sql);
	$sqls[] = $sql;
	
	$info = "财务付款已操作成功";
	echo("<script type='text/javascript'>alert('{$info}');location.href='purchase_bill.php?".time()."';</script>");
	//header("location: {$back}");		
	
}

?>