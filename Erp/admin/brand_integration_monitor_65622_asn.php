<?php
define('IN_ECS', true);
require_once ('includes/init.php');
//require_once('brand_integration_monitor_65617_func.php');
$nes_asn_type_list = 
								array(
								    'All' => array('name' => '不限', 'count' => 0),
									'ToSet' => array('name' => '待标记类型', 'count' => 0),
									'Valid' => array('name' => '正常ASN', 'count' => 0),
									'Invalid' => array('name' => '异常ASN', 'count' => 0),
									);
$nes_status_list = 
							array(
							    'All' => array('name' => '不限', 'count' => 0),
								'INIT' => array('name' => '已同步，待生成Service', 'count' => 0),
								'PROCESSING' => array('name' => '已生成Service，erp处理中', 'count' => 0),
								'TO_CLOSE' => array('name' => '已完成入库，待同步到Magento', 'count' => 0),
								'CLOSED' => array('name' => '已同步到Magento', 'count' => 0),
								'FAILED_TO_CLOSE' => array('name' => '同步到Magento失败', 'count' => 0)
								);
global $db;
$sql = "SELECT `asn_type`,`status`, count(*) as c
		FROM ecshop.brand_nes_asn
		GROUP BY `asn_type`,`status`";
$rst = $db->getAll($sql);
foreach ($rst as $value) {
	$nes_asn_type_list['All']['count'] += $value['c'];
	$nes_asn_type_list[$value['asn_type']]['count'] += $value['c'];
	$nes_status_list['All']['count'] += $value['c'];
	$nes_status_list[$value['status']]['count'] += $value['c'];
}
//
foreach ($nes_asn_type_list as $key => $value) {
	if($value['count'] == 0)
		unset($nes_asn_type_list[$key]);
}
foreach ($nes_status_list as $key => $value) {
	if($value['count'] == 0)
		unset($nes_status_list[$key]);
}
//
$search_option_list = array(
	'nes_asn_type' => array('name'=>'ASN类型', 'type'=>'select','value'=>$nes_asn_type_list),
	'nes_status' => array('name'=>'ASN状态', 'type'=>'select','value'=>$nes_status_list),
	'asnID' => array('name'=>'ASN号', 'type'=>'text','value'=>$_REQUEST["asnID"]),
	'created_stamp' => array('name'=>'创建时间', 'type'=>'time_interval', 'start'=>$_REQUEST["created_stamp_start"],  'end'=>$_REQUEST["created_stamp_end"]),
	'last_updated_stamp' => array('name'=>'更新时间', 'type'=>'time_interval', 'start'=>$_REQUEST["last_updated_stamp_start"],  'end'=>$_REQUEST["last_updated_stamp_end"]),
	);


$sql = "SELECT bna.*,oi.order_id,oi.order_sn,oi.order_time as orderDate,poi.order_id as parent_order_id,poi.order_sn as parent_order_sn
		FROM ecshop.brand_nes_asn bna
			LEFT JOIN ecshop.service s on bna.erp_service_id = s.service_id
			LEFT JOIN ecshop.ecs_order_info oi ON s.back_order_id = oi.order_id
			LEFT JOIN ecshop.brand_nes_order bno ON bna.orderNumber = bno.orderNumber
			LEFT JOIN ecshop.ecs_order_info poi ON bno.erp_order_id = poi.order_id
		WHERE 1 " . get_condition() . " order by bna.created_stamp desc limit 100";
//var_dump($sql);
$data_list = $db->getAll($sql);
foreach ($data_list as &$value) {
	$value['asn_type'] = $nes_asn_type_list[$value['asn_type']]['name'];
	$value['status'] = $nes_status_list[$value['status']]['name'];
}

//tab info
$smarty->assign('tab_info', array('value'=>'asn', 'name'=>'ASN'));
//搜索项
$smarty->assign('nes_asn_type_list', $nes_asn_type_list);
$smarty->assign('nes_status_list', $nes_status_list);
$smarty->assign('asnID', $_REQUEST["asnID"]);
$smarty->assign('created_stamp_start', $_REQUEST["created_stamp_start"]);
$smarty->assign('created_stamp_end', $_REQUEST["created_stamp_end"]);
$smarty->assign('last_updated_stamp_start', $_REQUEST["last_updated_stamp_start"]);
$smarty->assign('last_updated_stamp_end', $_REQUEST["last_updated_stamp_end"]);
//展示项
$smarty->assign('data_list', $data_list);
//
$smarty->display('brand_integration_monitor_65622_asn.html');
die();


function get_condition() {
	$cond = '';
	$nes_asn_type = trim($_REQUEST["nes_asn_type"]);
	$nes_asn_status = trim($_REQUEST["nes_asn_status"]);
	$asnID = trim($_REQUEST["asnID"]);
	$orderNumber = trim($_REQUEST["orderNumber"]);

	$created_stamp_start = trim($_REQUEST["search_created_stamp_start"]);
	$created_stamp_end = trim($_REQUEST["search_created_stamp_end"]);
	$last_updated_stamp_start = trim($_REQUEST["search_last_updated_stamp_start"]);
	$last_updated_stamp_end = trim($_REQUEST["search_last_updated_stamp_end"]);
	
	if($asnID) {
		$cond .= " and bna.asnID like '%{$asnID}%' ";
	}
	if($orderNumber) {
		$cond .= " and bna.orderNumber like '%{$orderNumber}%' ";
	}
	if($nes_asn_type && $nes_asn_type != 'All'){
		$cond .= " and bna.asn_type = '{$nes_asn_type}' ";
	}
	if($nes_asn_status && $nes_asn_status != 'All'){
		$cond .= " and bna.status = '{$nes_asn_status}' ";
	}


	if($created_stamp_start) {
		$cond .= " and bna.created_stamp >= '{$created_stamp_start}' ";
	}
	if($created_stamp_end) {
		$cond .= " and bna.created_stamp < DATE_ADD('{$created_stamp_end}', INTERVAL 1 day) ";
	}
	if($last_updated_stamp_start) {
		$cond .= " and bna.last_updated_stamp >= '{$last_updated_stamp_start}' ";
	}
	if($last_updated_stamp_end) {
		$cond .= " and bna.last_updated_stamp < DATE_ADD('{$last_updated_stamp_end}', INTERVAL 1 day) ";
	}
	return $cond;
	
}

?>