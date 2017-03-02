<?php
/*
 * Created on 2014-3-1
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
admin_priv('heinz_sync_order');

$smarty->assign('sync_status_list',array(
	'ALL' => 'ALL',
	'NORMAL' => 'NORMAL',
	'FAILURE' => 'FAILURE'
));

$act = $_REQUEST['act'] != '' ? $_REQUEST['act']:null;

if($act == "search"){
	$condition = get_condition();
	$sql = "SELECT order_id,order_sn, create_time, FROM_UNIXTIME(pay_time,'%Y-%m-%d %h:%M:%s'), sync_status, sync_note, province, 
				city, district,referer 
			FROM ecshop.brand_heinz_order_info
			WHERE 1 ".$condition;
	$order_list = $db->getAll($sql);
}elseif($act == "insert"){
	$heinz_goods_sn = isset($_REQUEST['heinz_goods_sn'])?trim($_REQUEST['heinz_goods_sn']):'';
	$erp_outer_id = isset($_REQUEST['erp_outer_id'])?trim($_REQUEST['erp_outer_id']):'';
	
	$sql = "select * from ecshop.brand_heinz_goods where heinz_goods_sn = '$heinz_goods_sn'";
	$result = $db->getAll($sql);
	if($result){
		$smarty->assign('message',"已经存在亨氏货号为".$heinz_goods_sn."的映射记录了。请仔细核对数据。");
	}else{
		$sql = "insert into ecshop.brand_heinz_goods (heinz_goods_sn,goods_outer_id,create_time,update_time,create_user,update_user)values('$heinz_goods_sn','$erp_outer_id',now(),now(),'{$_SESSION['admin_name']}','{$_SESSION['admin_name']}')";
		$db->query($sql);
	}
}

$smarty->assign('order_sn',trim($_REQUEST['order_sn']));
$smarty->assign('sync_status',trim($_REQUEST['sync_status']));
$smarty->assign('order_list',$order_list);
$smarty->display('taobao/heinz_sync_order.htm');

function get_condition(){
	$startTime = isset($_REQUEST['startTime']) ? $_REQUEST['startTime'] : '';
	$endTime = isset($_REQUEST['endTime']) ? $_REQUEST['endTime'] : '';
	$condition = "";
	if( trim($_REQUEST['order_sn']) != '' ){
		$condition .= " AND order_sn='".trim($_REQUEST['order_sn'])."'";
	}
	if( trim($_REQUEST['sync_status']) != 'ALL' ){
		$condition .= " AND sync_status = '".trim($_REQUEST['sync_status'])."'";
	}
	if($startTime && $endTime){
		$condition .= "and create_time > '".trim($_REQUEST['startTime'])."' and create_time <= '".trim($_REQUEST['endTime'])."'";
	}
	
	return $condition;
}
?>
