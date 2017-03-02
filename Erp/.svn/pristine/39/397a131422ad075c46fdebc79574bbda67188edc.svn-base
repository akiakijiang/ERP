<?php

/**
 * 打印快递面单
 *
 * @param $_REQUEST::order_id
 * 
 * $Id: print_shipping_orders.php 50188 2013-08-07 ljni $
 */

define('IN_ECS', true);
require ('includes/init.php');
require ("function.php");
include_once ('includes/lib_order.php');

if(isset($_REQUEST['order_id']) && is_string($_REQUEST['order_id'])){
    $order_ids = preg_split('/\s*,\s*/',$_REQUEST['order_id'],-1,PREG_SPLIT_NO_EMPTY);
}
else {
	echo "此单参数解析错误，ERP已在追踪中，请将此页面区域全部内容抄送给ERP以协助维修工作！";
	pp($_REQUEST);
    die("死于参数错误");
}
if(isset($_REQUEST['arata']) && $_REQUEST['arata']==1){
	$smarty->assign('arata',1);
}else{
	$smarty->assign('arata',0);
}

$smarty->assign('order_ids',$order_ids);

$smarty->display('waybill/BillList.htm');

?>