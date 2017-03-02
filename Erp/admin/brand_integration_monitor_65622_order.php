<?php
define('IN_ECS', true);
require_once ('includes/init.php');
//require_once('brand_integration_monitor_65617_func.php');

$nes_order_type_list = 
								array(
									'All' => array('name' => '不限', 'count' => 0),
									'ToSet' => array('name' => '待标记类型', 'count' => 0),
									'DontCare' => array('name' => '无需处理', 'count' => 0),
									'InvalidProduct' => array('name' => '商品未维护', 'count' => 0),
									'InvalidRegion' => array('name' => '区域未维护', 'count' => 0),
									'Invalid' => array('name' => '其他异常', 'count' => 0),
									'SalesOrder' => array('name' => '销售订单', 'count' => 0),
									'ExchangeOrder' => array('name' => '换货订单', 'count' => 0)
									);
$nes_status_list = 
							array(
								'All' => array('name' => '不限', 'count' => 0),
								'INIT' => array('name' => '已同步，待Ack对方', 'count' => 0),
								'CONFIRMED' => array('name' => '已Ack，待处理', 'count' => 0),
								'FAILED_TO_CONFIRM' => array('name' => 'Ack失败', 'count' => 0),
								'PROCESSING' => array('name' => 'erp流程处理中', 'count' => 0),
								'TOSEND' => array('name' => '已发货，待同步到Magento', 'count' => 0),
								'SENT' => array('name' => '已同步到Magento', 'count' => 0),
								'FAILED_TO_SEND' => array('name' => '同步到Magento失败', 'count' => 0),
								'CLOSED' => array('name' => '不该出现此状态', 'count' => 0)
								);
global $db;
$sql = "SELECT `order_type`,`status`, count(*) as c
		FROM ecshop.brand_nes_order
		GROUP BY `order_type`,`status`";
$rst = $db->getAll($sql);
foreach ($rst as $value) {
	$nes_order_type_list['All']['count'] += $value['c'];
	$nes_order_type_list[$value['order_type']]['count'] += $value['c'];
	$nes_status_list['All']['count'] += $value['c'];
	$nes_status_list[$value['status']]['count'] += $value['c'];
}
//
foreach ($nes_order_type_list as $key => $value) {
	if($value['count'] == 0)
		unset($nes_order_type_list[$key]);
}
foreach ($nes_status_list as $key => $value) {
	if($value['count'] == 0)
		unset($nes_status_list[$key]);
}
//
$search_option_list = array(
	'nes_order_type' => array('name'=>'BabyNes订单类型', 'type'=>'select','value'=>$nes_order_type_list),
	'nes_status' => array('name'=>'BabyNes订单状态', 'type'=>'select','value'=>$nes_status_list),
	'orderNumber' => array('name'=>'BabyNes订单号', 'type'=>'text','value'=>$_REQUEST["orderNumber"]),
	'created_stamp' => array('name'=>'BabyNes订单创建时间', 'type'=>'time_interval', 'start'=>$_REQUEST["created_stamp_start"],  'end'=>$_REQUEST["created_stamp_end"]),
	'last_updated_stamp' => array('name'=>'BabyNes订单更新时间', 'type'=>'time_interval', 'start'=>$_REQUEST["last_updated_stamp_start"],  'end'=>$_REQUEST["last_updated_stamp_end"]),
	);


$sql = "SELECT bno.nes_order_id, bno.orderNumber, eoi.order_id, eoi.order_sn, bno.orderDate,
       bno.order_type, bno.status,bno.created_stamp, bno.last_updated_stamp,peoi.order_sn as parent_order_sn,
       peoi.order_id as parent_order_id,pbno.orderNumber as parentOrderNumber
		FROM ecshop.brand_nes_order bno
		LEFT JOIN ecshop.ecs_order_info eoi on bno.erp_order_id = eoi.order_id
		LEFT JOIN ecshop.brand_nes_order pbno ON bno.parentOrderNumber = pbno.orderNumber
		LEFT JOIN ecshop.ecs_order_info peoi ON pbno.erp_order_id = peoi.order_id
		WHERE 1 " . get_condition() . " order by bno.orderDate desc limit 100";
$data_list = $db->getAll($sql);
foreach ($data_list as &$value) {
	$value['order_type'] = $nes_order_type_list[$value['order_type']]['name'];
	$value['status'] = $nes_status_list[$value['status']]['name'];
}
//tab info
$smarty->assign('tab_info', array('value'=>'order', 'name'=>'订单'));
//搜索项
$smarty->assign('nes_order_type_list', $nes_order_type_list);
$smarty->assign('nes_status_list', $nes_status_list);
$smarty->assign('orderNumber', $_REQUEST["orderNumber"]);
$smarty->assign('created_stamp_start', $_REQUEST["created_stamp_start"]);
$smarty->assign('created_stamp_end', $_REQUEST["created_stamp_end"]);
$smarty->assign('last_updated_stamp_start', $_REQUEST["last_updated_stamp_start"]);
$smarty->assign('last_updated_stamp_end', $_REQUEST["last_updated_stamp_end"]);
//展示项
$smarty->assign('data_list', $data_list);
//
$smarty->display('brand_integration_monitor_65622_order.html');
die();


function get_condition() {
	$cond = '';
	$nes_order_type = trim($_REQUEST["nes_order_type"]);
	$nes_order_status = trim($_REQUEST["nes_status"]);
	$orderNumber = trim($_REQUEST["orderNumber"]);

	$created_stamp_start = trim($_REQUEST["search_created_stamp_start"]);
	$created_stamp_end = trim($_REQUEST["search_created_stamp_end"]);
	$last_updated_stamp_start = trim($_REQUEST["search_last_updated_stamp_start"]);
	$last_updated_stamp_end = trim($_REQUEST["search_last_updated_stamp_end"]);
	
	if($orderNumber) {
		$cond .= " and bno.orderNumber like '%{$orderNumber}%' ";
	}
	if($nes_order_type && $nes_order_type != 'All'){
		$cond .= " and bno.order_type = '{$nes_order_type}' ";
	}
	if($nes_order_status && $nes_order_status != 'All'){
		$cond .= " and bno.status = '{$nes_order_status}' ";
	}


	if($created_stamp_start) {
		$cond .= " and bno.created_stamp >= '{$created_stamp_start}' ";
	}
	if($created_stamp_end) {
		$cond .= " and bno.created_stamp < DATE_ADD('{$created_stamp_end}', INTERVAL 1 day) ";
	}
	if($last_updated_stamp_start) {
		$cond .= " and bno.last_updated_stamp >= '{$last_updated_stamp_start}' ";
	}
	if($last_updated_stamp_end) {
		$cond .= " and bno.last_updated_stamp < DATE_ADD('{$last_updated_stamp_end}', INTERVAL 1 day) ";
	}
	return $cond;
	
}

?>