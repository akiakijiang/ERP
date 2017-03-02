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
require_once(ROOT_PATH. 'includes/debug/lib_log.php');

if(isset($_REQUEST['order_id']) && is_string($_REQUEST['order_id'])){
    $order_ids = preg_split('/\s*,\s*/',$_REQUEST['order_id'],-1,PREG_SPLIT_NO_EMPTY);
}
else {
	echo "此单参数解析错误，ERP已在追踪中，请将此页面区域全部内容抄送给ERP以协助维修工作！";
	pp($_REQUEST);
    die("死于参数错误");
}

$smarty->assign('order_ids',$order_ids);
//韵达快递
global $db;
$sql ="select shipping_id,facility_id from ecshop.ecs_order_info where order_id = '{$order_ids[0]}' ";
$order_info = $db->getRow($sql);
//QLog::log("print_shipping_order_arata_sql : ".$order_info['facility_id']);
//QLog::log("order_id2:".$order_ids[1]);

$is_out_facility = $_REQUEST['is_out_facility'];
if(!empty($is_out_facility)){
	$smarty->assign('is_out_facility',$is_out_facility);
	$batch_pick_sn = $_REQUEST['batch_pick_sn'];
	$smarty->assign('batch_pick_sn',$batch_pick_sn);
}

//判断是不是追加面单
$isAdd=(isset($_REQUEST['isAdd'])?$_REQUEST['isAdd']:0);
//判断是不是追加面单的再次打印
$pici=(isset($_REQUEST['pici'])?$_REQUEST['pici']:0);

if($isAdd==1){
	$smarty->assign('pici',$pici);
	$smarty->display('waybill/BillListArataAdd.htm');
}
else if ($order_info['shipping_id']==100  && in_array($order_info['facility_id'],array('24196974','137059426'))){//贝亲青浦仓韵达快递专用打印方式。
	$str_order_ids = implode("','",$order_ids);
	$sql = "select a.pdf_info from ecshop.ecs_order_yunda_mailno_apply a " .
	    " inner join romeo.order_shipment s on concat(s.shipment_id,0) = a.shipment_id " .
	    " where s.order_id in ('{$str_order_ids}') " .
	    " order by a.tracking_number asc ";
//	    QLog::log("sql_11 : ".$sql);
    $apply_mails=$db->getCol($sql);
    $pdf_infos = implode("@",$apply_mails);
	$smarty->assign('pdf_infos',$pdf_infos);
//	Qlog::log("pdf_info : ".$pdf_infos);
	$smarty->display('waybill/yd-arata.htm');
}else{
	$smarty->display('waybill/BillListArata.htm');
}
	

?>