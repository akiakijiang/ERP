<?php
define('IN_ECS', true);
require_once('includes/init.php');

admin_priv('finance_order');
require_once("function.php");

/* 取得快递公司结算的数据库 */
$finance_sf_db = new cls_mysql($finance_sf_db_host, $finance_sf_db_user, $finance_sf_db_pass, $finance_sf_db_name);

$bill_no = $_REQUEST['bill_no'];
if ($bill_no != '') {
	$bill_nos = preg_split('/[\s]+/', $bill_no);
	foreach ($bill_nos as $key=>$bill_no) {
		if (trim($bill_no) == '') {
			unset($bill_nos[$key]);
		}
	}
	if (count($bill_nos) > 0) {
		$in_bill_no = "'" . join("','", $bill_nos) . "'";
	} 
}
if ($in_bill_no) {
	$sql = "select bill_no, order_amount AS real_order_amount, 
				   proxy_amount as proxy_amount, shipping_fee AS real_shipping_fee
				   from ouku_order_amount 
			where bill_no in ($in_bill_no) union 
			(select a.bill_no, a.order_amount AS real_order_amount, 
					a.proxy_amount as proxy_amount, b.fee AS real_shipping_fee
					from order_amount a, real_shipping_fee b 
			 where a.bill_no in ($in_bill_no) and b.bill_no in ($in_bill_no) and a.bill_no = b.bill_no)";
	$orders = $finance_sf_db->getAll($sql);
}

$smarty->assign('orders', $orders);
$smarty->display('oukooext/finance_dshk_search.htm');
?>