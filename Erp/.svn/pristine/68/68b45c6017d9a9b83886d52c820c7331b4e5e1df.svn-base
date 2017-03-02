<?php

/**
 * 打印快递面单
 *
 * @param $_REQUEST::order_id
 * 
 * $Id: print_shipping_orders.php 50188 2013-08-07 ljni $
 */

define('IN_ECS', true);
require ('includes/mini_init.php');
require ("function.php");
include_once ('includes/lib_order.php');

include_once('includes/lib_sinri_DivCarrierBill.php');

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

$content_array=array();

/*
此处应有各种fetch
 */
$timer_list=array();
$timer_list['start_point']=microtime(true);

$cache_db_instance=new LibSinriDivCarrierBillDBCache($order_ids);

foreach ($order_ids as $order_id) {
	$timer_list['each'][$order_id]['start']=microtime(true);

	$delegate=new LibSinriDivCarrierBill($order_id,$cache_db_instance);
	$tpl='waybill_div/'.$delegate->getTPL();
	$params=$delegate->getAssignments();

	$smarty->clear_all_assign();
	foreach ($params as $key => $value) {
		$smarty->assign($key,$value);
	}
	$content=$smarty->fetch($tpl);
	$content_array[$order_id]=$content;

	$timer_list['each'][$order_id]['end']=microtime(true);
	$timer_list['each'][$order_id]['time']=$timer_list['each'][$order_id]['end']-$timer_list['each'][$order_id]['start'];
}

$timer_list['end_point']=microtime(true);
$timer_list['total_time']=$timer_list['end_point']-$timer_list['start_point'];

$smarty->assign('div_array',$content_array);

$smarty->display('waybill_div/BillDivs.htm');
echo "<!--".PHP_EOL;
print_r($timer_list);
echo PHP_EOL."-->".PHP_EOL;

?>