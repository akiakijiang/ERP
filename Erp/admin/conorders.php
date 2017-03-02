<?php
/**
 *相关订单
 */


define('IN_ECS', true);

require('includes/init.php');
require("function.php");
require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
admin_priv('manage_hot_goods');
// 查询相关订单

$goods_id = intval($_REQUEST['goods_id']);
$starttime = sqlSafe($_REQUEST['starttime']);
$endtime = sqlSafe($_REQUEST['endtime']);

if (strtotime($starttime) > 0){
		$startCalendar = date("Y-m-d H:i:s", strtotime($starttime));
		$time_condition = " AND o.order_time >= '$starttime'";
}

if (strtotime($endtime) > 0) {
		$endCalendar = date("Y-m-d H:i:s", strtotime("+1 day", strtotime($endCalendar)));
		$time_condition .= " AND o.order_time <= '$endtime'";
}



$sql = "SELECT rec_id,og.goods_id, og.goods_number, og.goods_price, og.goods_number , og.goods_name,o.order_sn,o.order_id,o.taobao_order_sn
		FROM ecs_order_info o, ecs_order_goods og
		WHERE o.order_id = og.order_id
		AND ". party_sql('o.party_id')."
		AND o.order_status =1 AND o.pay_status =2 AND ( o.shipping_status = 2 OR o.shipping_status = 1 ) AND o.order_type_id = 'SALE'
		AND og.goods_id = $goods_id
		$time_condition";

$order_list = $db->getAll($sql);
$num = 0;

if (is_array($order_list)){
	foreach ($order_list as $key => $orders){
		$num = $num + $order_list[$key]['goods_number'];
	}
}

$smarty->assign('order_list',$order_list);
$smarty->assign('num',$num);
$smarty->display('oukooext/conorders.htm');



?>
