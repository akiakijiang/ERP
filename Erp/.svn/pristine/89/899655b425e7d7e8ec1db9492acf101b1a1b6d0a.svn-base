<?php
define ( "IN_ECS", true );
require_once ('../includes/init.php');
include_once ('../includes/lib_function_inventory.php');

/*
 * 这个页面仅仅作为展示，处理操作等行为，都在apply页面中做
 */
admin_priv("VOrderApply","VOrderCheckZhuguan","VOrderCheckWL");

if ($_REQUEST['act'] == 'view'){
	$order_id = $_REQUEST['order_id'];
	view_order($order_id);
}else{
	echo "WELCOME!";
}

function view_order($order_id){
	global $smarty;
	$return_order_array = get_just_now_order(array($order_id));
	$smarty->assign ( 'return_just_now_order', $return_order_array );
	$smarty->display ( 'virance_inventory/inventory_adjust_view_v3.html' );
}