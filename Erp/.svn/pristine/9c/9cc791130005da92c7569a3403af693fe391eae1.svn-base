<?php
/**
 * 双十一订单监控
 * 
 * @author created by cywang 2014/11/01
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'includes/helper/array.php');

$startDate = $_REQUEST['startCalendar'] ? $_REQUEST['startCalendar'] : date('Y-m-d',strtotime("-7 day"));
$endDate = $_REQUEST['endCalendar'] ? $_REQUEST['endCalendar'] : date('Y-m-d',time());
$facility = $_REQUEST['facility'] ? $_REQUEST['facility'] : 0;
$yesterday = date("Y-m-d",(strtotime($endDate) - 3600*24));

$party_ids_array = array('16','64','65614','65617','65539','65553',
						 '65558','65569','65571','65572','65574',
						 '65579','65581','65586','65600','65603',
						 '65606','65608','65609','65619','65620',
						 '65621','65622','65623','65624','65628',
						 '65632','65633','65636','65644','65639',
						 '65643','65645','65646','65647','65648',
						 '65649','65562','65587','65565','65617',
						 '65632','65650','65600','65652','65653',
						 '65651','65661','65668','65638','65655',
						 '65656','65657','65664','65683','65677',
						 '65680'
						 );
$party_ids_string = join(',', $party_ids_array);
$start_time = microtime(true);

//获取组织信息
$sql_for_party_info = "select party_id, name from romeo.party where " .
		"party_id in ($party_ids_string);";
$parties = $db->getAll($sql_for_party_info);
$parties = Helper_Array::toHashmap($parties, 'party_id', 'name');

//获取仓库信息
$facility_list = array(
	0 => '不选',
	1 => '上海仓',
	2 => '东莞仓',
	3 => '北京仓',
	4 => '外包仓',
	5 => '品牌商直货仓',
	6 => '依云虚拟仓',
	7 => 'Blackmores虚拟上海仓',
	8 => '上海精品仓',
    9 => '武汉仓',
    10 => '成都仓',
    11 => '嘉善仓',
    12 => '苏州仓',
    13 =>'其他新建仓',	
);
$smarty->assign('facility_list', $facility_list);

$facility_ids_array = array('19568549', '22143846', '22143847', '81569822', 
							'81569823', '83972713', '83972714', '19568548', '49858449', 
							'76065524', '3580047', '79256821',  '92718101', 
							'119603091' ,'119603092' ,'119603093', '100170590', 
							'100170591', '108341690', '100170588', '100170589', '105142919', '107742558',
							'137059427','137059428','137059426','24196974',
							'119603094','120801050','137059424','149849259','149849260',
							'176053000','178036538','178036540','181093101','181093104',
							'185963128','185963130','185963132','185963134','185963136',
							'185963138','185963140','185963142','185963147','194788297',
							'120801050','137059424','176053000'
							);
$facility_ids_string = join("','", $facility_ids_array);
$sql_for_facility_info = "select facility_id, facility_name from romeo.facility where " .
		"facility_id in ('{$facility_ids_string}');";
$facility_info = $db->getAll($sql_for_facility_info);
$facility_info = Helper_Array::toHashmap($facility_info, 'facility_id', 'facility_name');

$fCondition = get_facility($facility);

$sum['party_id'] = $party_ids_string;
$sum['party_name'] = '小计';
$sum['facility_id'] = $fCondition;
$sum['facility_name'] = '';
$sum['overstock_num'] = 0;
$sum['overShipping_num'] = 0;
$sum['yesterShipping_num'] = 0;
$sum['new_num'] = 0;
$sum['unconfirmed_order'] = 0;
$sum['confirmed_order'] = 0;
$sum['shipmentlist_order'] = 0;
$sum['new_batched_order'] = 0;
$sum['new_delivered_order'] = 0;
$sum['to_ship_order'] = 0;
$sum['shipped_order'] = 0;

$sql_for_order = "
	select party_id, facility_id,
  	  sum(if(o.shipping_status not in (1, 2, 3, 11) and o.pay_time < unix_timestamp('".$yesterday." 16:00:00'), 1, 0)) overstock_num,
  	  sum(if(o.pay_time < unix_timestamp('".$yesterday." 16:00:00') and shipping_time >= unix_timestamp('".$endDate." 00:00:00') and shipping_time <= unix_timestamp('".$endDate." 23:59:59'), 1, 0)) overShipping_num, 
	  sum(if(o.pay_time >= unix_timestamp('".$yesterday." 16:00:00') and o.pay_time < unix_timestamp('".$endDate." 16:00:00') and shipping_time >= unix_timestamp('".$yesterday." 16:00:00') and shipping_time <= unix_timestamp('".$yesterday." 23:59:59'), 1, 0)) yesterShipping_num, 
	  sum(if(o.pay_time >= unix_timestamp('".$yesterday." 16:00:00') and o.pay_time < unix_timestamp('".$endDate." 16:00:00'), 1, 0)) new_num,
	  sum(if(order_status = 0, 1, 0)) unconfirmed_order, 
	  sum(if(o.order_status = 1 and o.shipping_status = 0 and o.reserved_time = 0, 1, 0)) confirmed_order,
	  sum(if(o.order_status = 1 and o.shipping_status = 0 and o.reserved_time != 0, 1, 0)) shipmentlist_order,
	  sum(if(shipping_status = 13, 1, 0)) new_batched_order, 
	  sum(if(shipping_status = 12, 1, 0)) new_delivered_order, 
	  sum(if(shipping_status = 8, 1, 0)) to_ship_order,
	  sum(if(shipping_time >= unix_timestamp('".$endDate." 00:00:00') and shipping_time <= unix_timestamp('".$endDate." 23:59:59'), 1, 0)) shipped_order 
	from ecshop.ecs_order_info o use index(order_time)
	where order_time >= '{$startDate}'
	  and order_status in(0, 1, 11)
	  and shipping_status in(0, 1, 2, 8, 9, 12, 13)
	  and party_id in ($party_ids_string)
	  and order_type_id ='SALE'
	  AND facility_id in('{$fCondition}')  
	  GROUP BY party_id, facility_id
";
Qlog::log($sql_for_order);
$order_info = $slave_db->getAll($sql_for_order);

foreach ( $order_info as $key => $order ) {
	if($order['overstock_num'] != 0 || $order['overShipping_num'] != 0 || $order['yesterShipping_num'] != 0 || $order['new_num'] != 0 || $order['unconfirmed_order'] != 0 || $order['confirmed_order'] != 0 ||
	   $order['shipmentlist_order'] != 0 || $order['new_batched_order'] != 0 || $order['new_delivered_order'] != 0 || $order['to_ship_order'] != 0 || $order['shipped_order'] != 0){
		$order_info[$key]['party_name'] = $parties[$order['party_id']];
		$order_info[$key]['facility_name'] = $facility_info[$order['facility_id']];  
        $sum['overstock_num'] += $order['overstock_num'];
        $sum['overShipping_num'] += $order['overShipping_num'];
        $sum['yesterShipping_num'] += $order['yesterShipping_num'];
        $sum['new_num'] += $order['new_num'];
        $sum['unconfirmed_order'] += $order['unconfirmed_order'];
        $sum['confirmed_order'] += $order['confirmed_order'];
        $sum['shipmentlist_order'] += $order['shipmentlist_order'];
        $sum['new_batched_order'] += $order['new_batched_order'];
        $sum['new_delivered_order'] += $order['new_delivered_order'];
        $sum['to_ship_order'] += $order['to_ship_order'];
        $sum['shipped_order'] += $order['shipped_order'];
	}else{
	  unset($order_info[$key]);
	}
}
array_push($order_info, $sum);

$cost_time = microtime(true)-$start_time;

$smarty->assign('order_info',$order_info);
$smarty->assign('cost_time',$cost_time);
$smarty->assign('startDate', $startDate);
$smarty->assign('endDate', $endDate);
$smarty->assign('yesterday', $yesterday);
$smarty->assign('facility',$facility);
$smarty->display('order_tracking_for_11111.htm');

function get_facility($facility){
	$condition = "";
	$facility_array = array();
	$facility_list_array = array(
		1 => array('19568549', '22143846', '22143847', '81569822', '81569823', '83972713', '83972714', '120801050','137059424','176053000'),
		2 => array('19568548', '49858449', '76065524', '3580047'),
		3 => array('79256821'),
		4 => array( '92718101', '119603091' ,'119603092' ,'119603093'),
		5 => array('100170590', '100170591', '108341690'),
		6 => array('100170588', '100170589', '105142919'),
		7 => array('107742558'),
		8 => array('137059426','24196974'),
		9 => array('137059427'),
		10 => array('137059428'),
		11 => array('194788297'),
		12 => array('185963138'),
		13 => array('119603094','120801050','137059424','149849259','149849260',
					'176053000','178036538','178036540','181093101','181093104',
					'185963128','185963130','185963132','185963134','185963136',
					'185963140','185963142','185963147'),
		0 => array('19568549', '22143846', '22143847', '81569822', '120801050','137059424','176053000',
				'81569823', '83972713', '83972714', '19568548', '49858449', 
				'76065524', '3580047', '79256821', '92718101', 
				'119603091','119603092','119603093', '100170590', 
				'100170591', '108341690', '100170588', '100170589', '105142919', '107742558',
				'137059427','137059428','137059426','24196974','194788297')
	);
	
	$facility_array = $facility_list_array[$facility];
	$facilities = array_intersect ($facility_array, explode(',', trim($_SESSION['facility_id'])));
	
	if(!empty($facilities)){
		$condition = join("','", $facilities);
	}else{
		$condition = 0;
	}
	
	return $condition;
}
?>
