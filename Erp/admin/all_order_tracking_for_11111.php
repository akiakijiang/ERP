<?php
/**
 * 双十一所有订单监控
 * 
 * 2015.10.29
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_json.php');

$startDate = $_REQUEST['startCalendar'] ? $_REQUEST['startCalendar'] : date('Y-m-d',strtotime("-7 day"));
$endDate = $_REQUEST['endCalendar'] ? $_REQUEST['endCalendar'] : date('Y-m-d',time());
$facility = $_REQUEST['facility'] ? $_REQUEST['facility'] : 0;
$party = $_REQUEST['party'] ? $_REQUEST['party'] : 0;
$shipping = $_REQUEST['shipping'] ? $_REQUEST['shipping'] : -2;


$start_time = microtime(true);

//获取组织信息
$sql_for_party_info = "select party_id, name from romeo.party WHERE STATUS='ok' AND (system_mode = '2' OR system_mode = '3')";
$parties = $db->getAll($sql_for_party_info);
$parties = Helper_Array::toHashmap($parties, 'party_id', 'name');
// $smarty->assign('party_list', $parties);

//获取仓库信息
$sql_for_facility_info = "select facility_id, facility_name from romeo.facility where IS_CLOSED='N'";
$facility_info = $db->getAll($sql_for_facility_info);
$facility_info = Helper_Array::toHashmap($facility_info, 'facility_id', 'facility_name');


//获取快递信息
$sql_for_shipping_info ="SELECT shipping_id,shipping_name from ecshop.ecs_shipping";
$shipping_info = $db->getAll($sql_for_shipping_info);
$shipping_info = Helper_Array::toHashmap($shipping_info, 'shipping_id', 'shipping_name');


$fCondition = get_Condition($facility,$party,$shipping);

$sum['party_id'] = $party_ids_string;
$sum['party_name'] = '小计';
$sum['facility_id'] = '';
$sum['facility_name'] = '';
$sum['new_num'] = 0;
$sum['unconfirmed_order'] = 0;
$sum['confirmed_order'] = 0;
$sum['shipmentlist_order'] = 0;
$sum['new_batched_order'] = 0;
$sum['new_delivered_order'] = 0;
$sum['to_ship_order'] = 0;
$sum['shipped_order'] = 0;

if($shipping == -2){
	$gCondition = "GROUP BY party_id, facility_id";
}else{
	$gCondition = "GROUP BY party_id, facility_id,shipping_id";
}

$sql_for_order = "
	select party_id, facility_id,shipping_id, 
	  sum(if(o.order_time >= '".$startDate." 00:00:00' and o.order_time < '".$endDate." 23:59:59', 1, 0)) new_num,
	  sum(if(order_status = 0, 1, 0)) unconfirmed_order, 
	  sum(if(o.order_status = 1 and o.shipping_status = 0 and o.reserved_time = 0, 1, 0)) confirmed_order,
	  sum(if(o.order_status = 1 and o.shipping_status = 0 and o.reserved_time != 0, 1, 0)) shipmentlist_order,
	  sum(if(shipping_status = 13, 1, 0)) new_batched_order, 
	  sum(if(shipping_status = 12, 1, 0)) new_delivered_order, 
	  sum(if(shipping_status = 8, 1, 0)) to_ship_order,
	  sum(if(shipping_status = 1 or shipping_status = 2, 1, 0)) shipped_order 
	from ecshop.ecs_order_info o use index(order_time)
	where order_time >= '".$startDate." 00:00:00' and order_time <= '".$endDate." 23:59:59'
	  and order_status in(0, 1, 11)
	  and shipping_status in(0, 1, 2, 8, 9, 12, 13)
	  and order_type_id ='SALE'
	  $fCondition
	  $gCondition
";
Qlog::log($sql_for_order);
$order_info = $slave_db->getAll($sql_for_order);
foreach ( $order_info as $key => $order ) {
	if($order['new_num'] != 0 || $order['unconfirmed_order'] != 0 || $order['confirmed_order'] != 0 ||
	   $order['shipmentlist_order'] != 0 || $order['new_batched_order'] != 0 || $order['new_delivered_order'] != 0 || $order['to_ship_order'] != 0 || $order['shipped_order'] != 0){
		   	if($parties[$order['party_id']]!="" && $facility_info[$order['facility_id']]!="" && $shipping_info[$order['shipping_id']]!=""){
				$order_info[$key]['party_name'] = $parties[$order['party_id']]; 
				$order_info[$key]['facility_name'] = $facility_info[$order['facility_id']];
				$order_info[$key]['shipping_name'] = ($shipping==-2 ? '汇总' : $shipping_info[$order['shipping_id']]);
		        $sum['new_num'] += $order['new_num'];
		        $sum['unconfirmed_order'] += $order['unconfirmed_order'];
		        $sum['confirmed_order'] += $order['confirmed_order'];
		        $sum['shipmentlist_order'] += $order['shipmentlist_order'];
		        $sum['new_batched_order'] += $order['new_batched_order'];
		        $sum['new_delivered_order'] += $order['new_delivered_order'];
		        $sum['to_ship_order'] += $order['to_ship_order'];
		        $sum['shipped_order'] += $order['shipped_order'];
	        }else{unset($order_info[$key]);}
	}else{
	  unset($order_info[$key]);
	}
}

array_push($order_info, $sum);

$sql_for_order_export = "
	select party_id, facility_id,shipping_id, 
	  sum(if(o.order_time >= '".$startDate." 00:00:00' and o.order_time < '".$endDate." 23:59:59', 1, 0)) new_num,
	  sum(if(order_status = 0, 1, 0)) unconfirmed_order, 
	  sum(if(o.order_status = 1 and o.shipping_status = 0 and o.reserved_time = 0, 1, 0)) confirmed_order,
	  sum(if(o.order_status = 1 and o.shipping_status = 0 and o.reserved_time != 0, 1, 0)) shipmentlist_order,
	  sum(if(shipping_status = 13, 1, 0)) new_batched_order, 
	  sum(if(shipping_status = 12, 1, 0)) new_delivered_order, 
	  sum(if(shipping_status = 8, 1, 0)) to_ship_order,
	  sum(if(shipping_status = 1, 1, 0)) shipped_order 
	from ecshop.ecs_order_info o use index(order_time)
	where order_time >= '".$startDate." 00:00:00' and order_time <= '".$endDate." 23:59:59'
	  and order_status in(0, 1, 11)
	  and shipping_status in(0, 1, 2, 8, 9, 12, 13)
	  and order_type_id ='SALE'
	  $fCondition
	  GROUP BY party_id, facility_id,shipping_id
";
$order_info3 = $slave_db->getAll($sql_for_order_export);
foreach ( $order_info3 as $key => $order ) {
	if($order['new_num'] != 0 || $order['unconfirmed_order'] != 0 || $order['confirmed_order'] != 0 ||
	   $order['shipmentlist_order'] != 0 || $order['new_batched_order'] != 0 || $order['new_delivered_order'] != 0 || $order['to_ship_order'] != 0 || $order['shipped_order'] != 0){
		   	if($parties[$order['party_id']]!="" && $facility_info[$order['facility_id']]!="" && $shipping_info[$order['shipping_id']]!=""){
				$order_info3[$key]['party_name'] = $parties[$order['party_id']]; 
				$order_info3[$key]['facility_name'] = $facility_info[$order['facility_id']];
				$order_info3[$key]['shipping_name'] = $shipping_info[$order['shipping_id']];
		        $sum2['new_num'] += $order['new_num'];
		        $sum2['unconfirmed_order'] += $order['unconfirmed_order'];
		        $sum2['confirmed_order'] += $order['confirmed_order'];
		        $sum2['shipmentlist_order'] += $order['shipmentlist_order'];
		        $sum2['new_batched_order'] += $order['new_batched_order'];
		        $sum2['new_delivered_order'] += $order['new_delivered_order'];
		        $sum2['to_ship_order'] += $order['to_ship_order'];
		        $sum2['shipped_order'] += $order['shipped_order'];
	        }else{unset($order_info3[$key]);}
	}else{
	  unset($order_info3[$key]);
	}
}

array_push($order_info3, $sum2);
$smarty->assign('order_info3',$order_info3);

$sql_for_order2 = "
	select party_id, facility_id,shipping_id
	from ecshop.ecs_order_info o use index(order_time)
	where order_time >= '".$startDate." 00:00:00' and order_time <= '".$endDate." 23:59:59'
	  and order_status in(0, 1, 11)
	  and shipping_status in(0, 1, 2, 8, 9, 12, 13)
	  and order_type_id ='SALE'
	  GROUP BY party_id, facility_id,shipping_id
";
$order_info2 = $slave_db->getAll($sql_for_order2);
foreach ( $order_info2 as $key => $order2 ){
	if($parties[$order2['party_id']]!="" && $facility_info[$order2['facility_id']]!="" && $shipping_info[$order2['shipping_id']]!=""){
			$party_info2[$key]['party_id'] = $order_info2[$key]['party_id'];
			$party_info2[$key]['party_name'] = $parties[$order2['party_id']];  
			$facility_info2[$key]['facility_id'] = $order_info2[$key]['facility_id'];
			$facility_info2[$key]['facility_name'] = $facility_info[$order2['facility_id']];
			$shipping_info2[$key]['shipping_id'] = $order_info2[$key]['shipping_id'];
			$shipping_info2[$key]['shipping_name'] = $shipping_info[$order2['shipping_id']];
	}
}


$facility_info3 = Helper_Array::toHashmap($facility_info2, 'facility_id', 'facility_name');
$smarty->assign('facility_list', $facility_info3);	

$shipping_info3 = Helper_Array::toHashmap($shipping_info2, 'shipping_id', 'shipping_name');
$shipping_info3[-2] = "汇总";

$smarty->assign('shipping_list', $shipping_info3);

$party_info3 = Helper_Array::toHashmap($party_info2, 'party_id', 'party_name');
$smarty->assign('party_list', $party_info3);

$order_list_result_check = "";
if(!empty($order_info)){
	$order_list_result_check = "order_info is not empty";
}

$cost_time = microtime(true)-$start_time;

$smarty->assign('order_info',$order_info);
$smarty->assign('cost_time',$cost_time);
$smarty->assign('startDate', $startDate);
$smarty->assign('endDate', $endDate);
$smarty->assign('yesterday', $yesterday);
$smarty->assign('facility',$facility);
$smarty->assign('party',$party);
$smarty->assign('shipping',$shipping);
$smarty->assign('order_list_result_check',$order_list_result_check);

if ($_REQUEST['type'] == '导出') {
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "全部订单状态追踪") . ".csv" );
	$out = $smarty->fetch ( 'oukooext/all_order_tracking_for_11111_csv.htm');
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}

$smarty->display('all_order_tracking_for_11111.htm');

function get_Condition($facility,$party,$shipping){
	$condition = "";
	if($facility !="" && $facility != 0 && $facility != -1){
		$condition .= "and facility_id='{$facility}' ";
	}
	if ($party !="" && $party != 0 && $party != -1) {
		$condition .= "and party_id='{$party}' ";
	}
	if ($shipping !="" && $shipping != 0 && $shipping != -1 && $shipping != -2) {
		$condition .= "and shipping_id='{$shipping}' ";
	}
	return $condition;
}	

?>
