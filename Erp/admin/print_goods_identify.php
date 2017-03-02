<?php

/**
 * 商品标识打印
 * 
 */

define('IN_ECS', true);
require_once('includes/init.php');
include_once 'function.php';
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

if($_SESSION['party_id'] != 65562){
	 die("只有ECCO业务组使用打印商品标识！");
}

admin_priv('print_goods_identify');


$barcode = trim($_REQUEST['barcode']);
$number = trim($_REQUEST['number']);

$smarty->assign('barcode', $barcode);//商品标识
$smarty->assign('number', $number);//打印个数
$smarty->display('oukooext/print_goods_identify.htm');
?>