<?php
/**
 *
 * 中粮店铺库存比例设置
 * 
 */
define('IN_ECS', true);
require_once('includes/init.php');
require("function.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH.'/includes/helper/array.php');

//判断组织是否为中粮业务组
if($_SESSION['party_id'] != '65625') {
    sys_msg("该功能只针对中粮业务组！");
}


$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
if($act == 'edit') {
//	pp($_REQUEST);exit;
	$group_id = $_REQUEST['group_id'];
	$taobao_shop_conf_id = $_REQUEST['taobao_shop_conf_id'];
	$inventory_ratio = $_REQUEST['inventory_ratio'];
	$is_main = $_REQUEST['is_main'];
	$sum = 0.0;
	foreach($inventory_ratio as $item) {	
		$sum += $item;
	}
	if($sum != '1') {
		sys_msg("库存比例填写有误，请检查后再修改！");
	}
	$is_success = true;
	foreach($inventory_ratio as $key => $item) {
		if($key == $is_main) {
			$sql = "update ecshop.taobao_shop_conf set inventory_ratio = '{$item}', is_main = 'Y' where taobao_shop_conf_id = '{$key}'";
		} else {
			$sql = "update ecshop.taobao_shop_conf set inventory_ratio = '{$item}', is_main = 'N' where taobao_shop_conf_id = '{$key}'";
		}
		if(!$db->query($sql)){
			$is_success = false;
		}
	}
	if($is_success){
		sys_msg("修改成功！！");
	}
	
	
}


global $db;

$sql = "select taobao_shop_conf_id, nick, group_id, inventory_ratio, is_main from ecshop.taobao_shop_conf where party_id='65625' " .
		"GROUP BY group_id, taobao_shop_conf_id";
$shop_groups = $db -> getAll($sql);


$shop_groups = Helper_Array::GroupBy($shop_groups,'group_id');



$smarty -> assign('shop_groups',$shop_groups);
$smarty -> display('inventory_ratio_zl.htm');


?>