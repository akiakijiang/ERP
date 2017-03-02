<?php
define('IN_ECS', true);
require_once ('../includes/init.php');
require_once ('monitor_tools.php');

$input_param_name_list = array(
"order_ids",'taobao_order_sns',
'shipping_time_start'=>'shipping_time_start','shipping_time_end'=>'shipping_time_end',
'po_nos','sync_status',
'created_stamp_start'=>'created_stamp_start','created_stamp_end'=>'created_stamp_end',
'last_updated_stamp_start'=>'last_updated_stamp_start','last_updated_stamp_end'=>'last_updated_stamp_end'
);
//var_dump('2');var_dump($input_param_name_list);

$monitor_header = new MonitorHeader("OR对接监控页-当前订单同步情况", $input_param_name_list);
$smarty->assign('monitor_header', $monitor_header);

$cond = get_condition();
//var_dump($cond);
if(CheckInputParams($cond)){
	$smarty->assign('search_condition_list_', $input_param_name_list);
	$smarty->assign('monitor_data', GenerateDataForMonitor($cond));
}else{
	$smarty->assign('msg', '请至少填一个筛选项，请检查');
}
$smarty->display('SinriTest/common_monitor.htm');

function CheckInputParams($cond){
	if(empty($cond)){
		return false;
	}
	return true;
}

function GenerateDataForMonitor($cond){
	global $db;

	$sql = "SELECT boh.or_header_id, oi.order_id, oi.taobao_order_sn, from_unixtime(oi.shipping_time) as shipping_time, boh.document_id as po_no, boh.sync_status, boh.created_stamp, boh.last_updated_stamp
				FROM ecshop.ecs_order_info oi
				LEFT JOIN ecshop.brand_or_order boo ON boo.order_id = oi.order_id
				LEFT JOIN ecshop.sync_taobao_order_info stoi ON stoi.tid = oi.taobao_order_sn
				LEFT JOIN ecshop.brand_or_header boh on oi.order_id = boh.order_id
				WHERE oi.order_type_id = 'SALE' AND oi.party_id = 65619 AND (oi.taobao_order_sn <> '' or oi.taobao_order_sn is not null) 
					AND oi.order_status = 1 AND oi.shipping_status in (1, 2, 3) $cond ";
//	var_dump($sql);
	$result = GetTableMonitorInfoAndAdditionalQueryInfoFromSQL(
		'[同步情况]', $sql, 'or_header_id');
	$data[] = $result['monitor_info'];

	return $data;
}


function get_condition() {
	$cond = '';
	$order_ids = trim($_REQUEST["order_ids"]);
	$order_ids = convert_str_to_sql($order_ids);
	$taobao_order_sns = trim($_REQUEST["taobao_order_sns"]);
	$taobao_order_sns = convert_str_to_sql($taobao_order_sns);
	$po_nos = trim($_REQUEST["po_nos"]);
	$po_nos = convert_str_to_sql($po_nos);
	$shipping_time_start = trim($_REQUEST["shipping_time_start"]);
	$shipping_time_end = trim($_REQUEST["shipping_time_end"]);
	$sync_status = trim($_REQUEST["sync_status"]);
	$created_stamp_start = trim($_REQUEST["created_stamp_start"]);
	$created_stamp_end = trim($_REQUEST["created_stamp_end"]);
	$last_updated_stamp_start = trim($_REQUEST["last_updated_stamp_start"]);
	$last_updated_stamp_end = trim($_REQUEST["last_updated_stamp_end"]);
	
	if($order_ids) {
		$cond .= " and oi.order_id in ({$order_ids}) ";
	}
	if($taobao_order_sns) {
		$cond .= " and oi.taobao_order_sn in ({$taobao_order_sns}) ";
	}
	$shipping_time_start = strtotime($shipping_time_start);
	if($shipping_time_start) {
		$cond .= " and oi.shipping_time >= '{$shipping_time_start}' ";
	}
	$shipping_time_end = strtotime($shipping_time_end);
	if($shipping_time_end) {
		$cond .= " and oi.shipping_time < '{$shipping_time_end}' ";
	}
	if($po_nos) {
		$cond .= " and boh.document_id in ({$po_nos}) ";
	}
	if($sync_status) {
		$cond .= " and boh.sync_status = '{$sync_status}' ";
	}
	if($created_stamp_start) {
		$cond .= " and boh.created_stamp >= '{$created_stamp_start}' ";
	}
	if($created_stamp_end) {
		$cond .= " and boh.created_stamp < '{$created_stamp_end}' ";
	}
	if($last_updated_stamp_start) {
		$cond .= " and boh.last_updated_stamp >= '{$last_updated_stamp_start}' ";
	}
	if($last_updated_stamp_end) {
		$cond .= " and boh.last_updated_stamp < '{$last_updated_stamp_end}' ";
	}
	
	return $cond;
	
}


?>