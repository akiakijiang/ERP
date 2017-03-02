<?php
define('IN_ECS', true);
require_once ('includes/init.php');
require_once('brand_integration_monitor_65619_func.php');

//erp订单类别
$erp_order_type_id_list = array(
	array("name" => "所有订单", "value" => "ALL"),
	array("name" => "销售订单", "value" => "SALE"),
	array("name" => "退货订单", "value" => "RMA_RETURN"),
	array("name" => "换货订单", "value" => "RMA_EXCHANGE"),
	);
$smarty->assign('erp_order_type_id_list', $erp_order_type_id_list);
//ERP订单同步类型
$erp_order_sync_type_list = array(
	array("name" => "所有订单", "value" => "ALL"),
	array("name" => "需同步订单", "value" => "NEED_SYNC"),
	array("name" => "无需同步订单", "value" => "DONT_NEED_SYNC"),
	);
$smarty->assign('erp_order_sync_type_list', $erp_order_sync_type_list);

//erp转化为PO状态
$erp_to_po_status_list = array(
	array("name" => "不限", "value" => "ALL"),
	array("name" => "已转化", "value" => true),
	array("name" => "未转化", "value" => false)
	);
$smarty->assign('erp_to_po_status_list', $erp_to_po_status_list);

//PO状态
$po_status_list = array(
	array("name" => "不限", "value" => "ALL"),
	array("name" => "有效PO", "value" => "OK"),
	array("name" => "已废除PO", "value" => "CLOSED"),
	);
$smarty->assign('po_status_list', $po_status_list);
//SAP订单类别
$sap_order_type_list = array(
	array("name" => "不限", "value" => "ALL"),
	array("name" => "销售订单(Z3OS)", "value" => "Z3OS"),
	array("name" => "退货订单(ZRTO)", "value" => "ZRTO")
	);
$smarty->assign('sap_order_type_list', $sap_order_type_list);

//同步状态
$sql = "SELECT sync_status, count(*) as count from ecshop.brand_or_header where header_status = 'OK' GROUP BY sync_status";
$sync_status_list = $db->getAll($sql);
$sync_status_mapping = array("INIT" => "未开始同步", 'Doing'=>'发送中','Finish'=> '完成同步','ConnectionErr' => '连接错误', 'WrongData' => '生成数据错误', 'WrongXML' => '传输数据格式错误','Timeout' => '连接超时','Pending' => '等待发送','AccessDenied' => '发送不成功','ProxyException' => 'Estee代理错误');
foreach ($sync_status_list as $key => &$value) {
	$value['name'] = isset($sync_status_mapping[$value['sync_status']]) ? $sync_status_mapping[$value['sync_status']] : '其他['.$value['sync_status'].']';
}
array_unshift($sync_status_list, array('sync_status' =>'ALL', 'name' => '不限'));
$smarty->assign('sync_status_list', $sync_status_list);

$sql = get_sql();
$data_list = $order_ids = array();
$db->getAllRefBy($sql, array('order_id'), $order_ids, $data_list);
$order_list = array();
if(!empty($data_list['order_id'])){
	foreach ($data_list['order_id'] as $order_id => $PO_list) {
		//order_info
		$order_info['order_sn'] = $PO_list[0]['order_sn'];
		$order_info['taobao_order_sn'] = $PO_list[0]['taobao_order_sn'];
		$order_info['remark'] = $PO_list[0]['remark'];
		$order_info['remarker'] = $PO_list[0]['remarker'];
		$order_info['or_order_id'] = $PO_list[0]['or_order_id'];
		$order_info['order_time'] = $PO_list[0]['order_time'];
		$order_info['pricing_time'] = $PO_list[0]['pricing_time'];
		$order_info['shipping_time'] = $PO_list[0]['shipping_time'];
		$order_info['PO_count'] = count($PO_list);
		//po_info
		foreach ($PO_list as &$PO) {
			$PO['action_list'] = array();
			if(isset($PO['or_header_id'])){
				GenerateAllowedActionsForHeader($PO['order_type'], $PO['header_status'], $PO['sync_status'], $PO['action_list']);
			}
			if(isset($PO['sync_status'])){
				$PO['sync_status'] = isset($sync_status_mapping[$PO['sync_status']]) ? $sync_status_mapping[$PO['sync_status']] : '其他('.$PO['sync_status'].')';
			}else{
				$PO['sync_status'] = '';
			}
			unset($PO['order_id']);
			unset($PO['order_sn']);
			unset($PO['taobao_order_sn']);
			unset($PO['or_order_id']);
			unset($PO['order_time']);
			unset($PO['pricing_time']);
			unset($PO['shipping_time']);
		}
		$order_info['PO_list'] = $PO_list;
		//push into order_list
		$order_list[$order_id] = $order_info;
	}
}
$smarty->assign('data_list', $order_list);
$smarty->display('brand_integration_monitor_65619.html');
die();


function get_sql(){
	if(!isset($_REQUEST['search_type']) || $_REQUEST['search_type'] == 'NEED_SYNC_BUT_NOT'){
		$sql = "SELECT eoi.order_id, order_sn, eoi.taobao_order_sn, boeor.remark, boeor.remarker, boo.or_order_id, order_time, stoi.pay_time as pricing_time, from_unixtime(eoi.shipping_time) as shipping_time,
                   null as or_header_id, null as document_id, null as created_stamp, null as sync_status, null as sync_time,
                   null as header_status, null as order_type
                   FROM ecshop.ecs_order_info eoi
                   LEFT JOIN ecshop.brand_or_erp_order_remark boeor on eoi.order_id = boeor.order_id
                   LEFT JOIN ecshop.brand_or_order boo ON boo.order_id = eoi.order_id
                   LEFT JOIN ecshop.sync_taobao_order_info stoi ON stoi.tid = eoi.taobao_order_sn
                   WHERE eoi.order_type_id = 'SALE' AND eoi.party_id = 65619 AND (eoi.taobao_order_sn <> '' AND eoi.taobao_order_sn is not null) 
                       AND eoi.order_status = 1 AND eoi.shipping_status in (1, 2, 3) AND boo.or_order_id is null
                       AND eoi.facility_id in ('137059426') AND eoi.order_time > DATE_SUB(curdate(),INTERVAL 1 month) 
               UNION
               SELECT oi.order_id, oi.order_sn, oi.taobao_order_sn, boeor.remark, boeor.remarker, boo.or_order_id, oi.order_time, null as pricing_time, from_unixtime(oi.shipping_time) as shipping_time,
                               null as or_header_id, null as document_id, null as created_stamp, null as sync_status, null as sync_time,
                               null as header_status, null as order_type
               FROM ecshop.ecs_order_info oi
                   LEFT JOIN ecshop.brand_or_erp_order_remark boeor on oi.order_id = boeor.order_id
                               INNER JOIN romeo.refund r ON convert(oi.order_id using utf8) = r.order_id
                               LEFT JOIN ecshop.brand_or_order boo ON boo.order_id = oi.order_id
                               INNER JOIN ecshop.order_relation orl ON oi.order_id = orl.order_id
                               INNER JOIN ecshop.brand_or_order rboo ON rboo.order_id = orl.root_order_id
                               INNER JOIN ecshop.ecs_order_info roi ON roi.order_id = orl.root_order_id
               WHERE oi.order_type_id = 'RMA_RETURN' AND oi.party_id = 65619 AND r.status IN ('RFND_STTS_CHECK_OK','RFND_STTS_EXECUTED')
                                 AND (roi.taobao_order_sn <> '' AND roi.taobao_order_sn is not null)  AND boo.or_order_id is null
                                 AND oi.facility_id in ('137059426')  AND roi.order_time > DATE_SUB(curdate(),INTERVAL 1 month) 
               UNION
               SELECT toi.order_id, toi.order_sn, toi.taobao_order_sn, boeor.remark, boeor.remarker, null as or_order_id, toi.order_time, null as pricing_time, from_unixtime(toi.shipping_time) as shipping_time, 
                               null as or_header_id, null as document_id, null as created_stamp, null as sync_status, null as sync_time,
                               null as header_status, null as order_type
               FROM ecshop.ecs_order_info hoi
                   LEFT JOIN ecshop.brand_or_erp_order_remark boeor on hoi.order_id = boeor.order_id
                               INNER JOIN ecshop.service s ON s.change_order_id = hoi.order_id
                             INNER JOIN ecshop.ecs_order_info toi ON toi.order_id = s.back_order_id
                             INNER JOIN ecshop.order_relation orl ON hoi.order_id = orl.order_id
                               INNER JOIN ecshop.brand_or_order rboo ON rboo.order_id = orl.root_order_id
                               INNER JOIN ecshop.ecs_order_info roi ON roi.order_id = orl.root_order_id
                             LEFT JOIN ecshop.brand_or_order tboo ON tboo.order_id = toi.order_id
                             LEFT JOIN romeo.refund r ON convert(hoi.order_id using utf8) = r.order_id
               WHERE hoi.order_type_id = 'RMA_EXCHANGE' AND hoi.party_id = 65619 AND hoi.order_status = 1 AND hoi.shipping_status in (1, 2, 3)
                                   AND (roi.taobao_order_sn <> '' AND roi.taobao_order_sn is not null) AND tboo.or_order_id is null
                                   AND hoi.facility_id in ('137059426')  AND roi.order_time > DATE_SUB(curdate(),INTERVAL 1 month) 
               UNION
               SELECT oi.order_id, oi.order_sn, root_eoi.taobao_order_sn, boeor.remark, boeor.remarker, null as or_order_id, oi.order_time, stoi.pay_time as pricing_time, from_unixtime(oi.shipping_time) as shipping_time, 
                               null as or_header_id, null as document_id, null as created_stamp, null as sync_status, null as sync_time,
                               null as header_status, null as order_type
               FROM ecshop.ecs_order_info oi
                   LEFT JOIN ecshop.brand_or_erp_order_remark boeor on oi.order_id = boeor.order_id
                               LEFT JOIN ecshop.brand_or_order boo ON boo.order_id = oi.order_id
                               LEFT JOIN ecshop.service s ON oi.order_id = s.change_order_id
                               LEFT JOIN ecshop.order_relation orr on oi.order_id = orr.order_id
                               INNER JOIN ecshop.brand_or_order tboo ON s.back_order_id = tboo.order_id
                               LEFT JOIN ecshop.ecs_order_info root_eoi on orr.root_order_id = root_eoi.order_id
                               LEFT JOIN ecshop.sync_taobao_order_info stoi ON stoi.tid = root_eoi.taobao_order_sn
               WHERE oi.order_type_id = 'RMA_EXCHANGE' AND oi.party_id = 65619 AND (root_eoi.taobao_order_sn <> '' AND root_eoi.taobao_order_sn is not null) 
                                   AND oi.order_status = 1 AND oi.shipping_status in (1, 2, 3) AND boo.or_order_id is null
                                   AND oi.facility_id in ('137059426')  AND oi.order_time > DATE_SUB(curdate(),INTERVAL 1 month) ";

	}else{
		$sql = "SELECT eoi.order_id, order_sn, eoi.taobao_order_sn, boeor.remark, boeor.remarker, boo.or_order_id, order_time, stoi.pay_time as pricing_time, from_unixtime(eoi.shipping_time) as shipping_time,
					boh.or_header_id, boh.document_id, boh.created_stamp, boh.sync_status, if(boh.sync_status='INIT', '', boh.last_updated_stamp) as sync_time,
					boh.header_status, boh.order_type
				from ecshop.ecs_order_info eoi
					LEFT JOIN ecshop.brand_or_erp_order_remark boeor on eoi.order_id = boeor.order_id
					LEFT JOIN ecshop.sync_taobao_order_info stoi ON stoi.tid = eoi.taobao_order_sn
					LEFT JOIN ecshop.brand_or_order boo ON eoi.order_id = boo.order_id
					LEFT JOIN ecshop.brand_or_header boh on eoi.order_id = boh.order_id
				where eoi.party_id = 65619 AND eoi.facility_id in ('137059426') " . get_condition() . " order by eoi.order_time limit 100";
	}
	return $sql;
} 


function get_condition() {
	$cond = '';
	$order_sn = trim($_REQUEST["search_order_sn"]);
	$taobao_order_sn = trim($_REQUEST["search_taobao_order_sn"]);
	$erp_order_type_id = trim($_REQUEST["search_erp_order_type_id"]);
	$erp_order_sync_type = trim($_REQUEST["search_erp_order_sync_type"]);
	$po_no = trim($_REQUEST["seach_po_no"]);
	//print_r($_REQUEST);
	$po_status = trim($_REQUEST["search_PO_status"]);
	$sap_order_type_id = trim($_REQUEST["search_sap_order_type_id"]);
	$order_time_start = trim($_REQUEST["search_order_time_start"]);
	$order_time_end = trim($_REQUEST["search_order_time_end"]);
	$shipping_time_start = trim($_REQUEST["search_shipping_time_start"]);
	$shipping_time_end = trim($_REQUEST["search_shipping_time_end"]);
	$sync_status = trim($_REQUEST["search_sync_status"]);
	$erp_to_po_status = trim($_REQUEST["search_erp_to_po_status"]);
	$created_stamp_start = trim($_REQUEST["search_created_stamp_start"]);
	$created_stamp_end = trim($_REQUEST["search_created_stamp_end"]);
	$last_updated_stamp_start = trim($_REQUEST["search_last_updated_stamp_start"]);
	$last_updated_stamp_end = trim($_REQUEST["search_last_updated_stamp_end"]);
	
	if($order_sn) {
		$cond .= " and eoi.order_sn like '{$order_sn}%' ";
	}
	if($erp_order_type_id && $erp_order_type_id != 'ALL'){
		$cond .= " and eoi.order_type_id = '{$erp_order_type_id}' ";
	}else{
		$cond .= " and eoi.order_type_id in ('SALE', 'RMA_RETURN', 'RMA_EXCHANGE') ";
	}

	if($taobao_order_sn) {
		$cond .= " and eoi.taobao_order_sn like '{$taobao_order_sn}%' ";
	}
	if($order_time_start) {
		$cond .= " and eoi.order_time >= '{$order_time_start}' ";
	}
	if($order_time_end) {
		$cond .= " and eoi.order_time < DATE_ADD('{$order_time_end}', INTERVAL 1 day) ";
	}
	if($shipping_time_start) {
		$shipping_time_start = strtotime($shipping_time_start);
		$cond .= " and eoi.shipping_time >= '{$shipping_time_start}' ";
	}
	if($shipping_time_end) {
		$shipping_time_end = strtotime($shipping_time_end);
		$cond .= " and eoi.shipping_time < DATE_ADD('{$shipping_time_end}', INTERVAL 1 day) ";
	}
	if($po_no) {
		$cond .= " and boh.document_id like '{$po_no}%' ";
	}
	if($po_status && $po_status != 'ALL') {
		$cond .= " and boh.header_status = '{$po_status}' ";
	}else{
		$cond .= " and (boh.or_header_id is null or boh.header_status = 'OK') ";
	}
	if($sap_order_type_id && $sap_order_type_id != 'ALL'){
		$cond .= " and boh.order_type = '{$sap_order_type_id}' ";
	}
	if($sync_status && $sync_status!='ALL') {
		$cond .= " and boh.sync_status = '{$sync_status}' ";
	}

	if(isset($_REQUEST["search_erp_to_po_status"])){
		if($erp_to_po_status === "ALL"){
			
		}else if($erp_to_po_status){
			$cond .= " and boh.or_header_id is not null ";
		}else{
			$cond .= " and boh.or_header_id is null ";
		}
	}

	if($created_stamp_start) {
		$cond .= " and boh.created_stamp >= '{$created_stamp_start}' ";
	}
	if($created_stamp_end) {
		$cond .= " and boh.created_stamp < DATE_ADD('{$created_stamp_end}', INTERVAL 1 day) ";
	}
	if($last_updated_stamp_start) {
		$cond .= " and boh.last_updated_stamp >= '{$last_updated_stamp_start}' ";
	}
	if($last_updated_stamp_end) {
		$cond .= " and boh.last_updated_stamp < DATE_ADD('{$last_updated_stamp_end}', INTERVAL 1 day) ";
	}
	return $cond;
	
}

?>