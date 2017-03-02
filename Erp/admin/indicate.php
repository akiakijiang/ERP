<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once ROOT_PATH . 'includes/cls_page.php';
admin_priv('indicate_query');
$act = $_REQUEST['act'];
if ($act == 'search') {
	$info = isset($_REQUEST['info']) ? $_REQUEST['info'] : '';
	$timeType = $_REQUEST['timeType'];
	$searchType = $_REQUEST['searchType'];
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$searchText = trim($_REQUEST['search_text']);
	
	$cond = "";
	$url = "indicate.php?act=search";
	if (! empty($info)) {
		Helper_Array :: removeEmpty($info);
		foreach ($info as $key => $value) {
			$cond .= " and {$key} = '{$value}'";
			$url .="&info[{$key}]={$value}";
			$smarty->assign($key."_search", $value);
		}
	}
	
	if (! empty($start)) {
		$cond .= " and {$timeType} >= '{$start}' ";
		$url .= "&start={$start}";
	}
	
	if (! empty($end)) {
		$cond .= " and {$timeType} < '{$end}'";
		$url .= "&end={$end}";
	}
	
	if (! empty($searchText)) {
		
		if ($searchType == "barcode") {
			$cond .= " and exists (select 1 from ecshop.ecs_indicate_detail id where id.barcode = '{$searchText}' and id.indicate_id = i.indicate_id ) ";
		} else {
			$cond .= " and {$searchType} = '{$searchText}'";
		}
		$url .= "&search_text={$searchText}";
	}
	$url .= "&timeType={$timeType}&searchType={$searchType}";
	
$page_size = 20;
$page = isset($_REQUEST['page']) && (is_numeric($_REQUEST['page']) > 0) ? $_REQUEST['page'] : 1 ;
$offset = ($page - 1) * $page_size;
$total =  getIndicate($cond, true, false, null, null);
$list = getIndicate($cond, false, true, $page_size, $offset);
$pagination = new Pagination($total[0]['total'], $page_size, $page ,'page' ,$url);

$search_message = "搜索结果 总数:{$total[0]['total']}; 销售订单:{$total[0]['INVENTORY_OUT']}; 退货订单:{$total[0]['INVENTORY_RETURN']}; 采购入库订单:{$total[0]['INVENTORY_IN']}; 供应商退货:{$total[0]['SUPPLIER_RETURN']}";
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->assign("list", $list);
$smarty->assign("start", $start);
$smarty->assign("end", $end);
$smarty->assign("search_text", $searchText);
$smarty->assign("timeType", $timeType);
$smarty->assign("searchType", $searchType);
$smarty->assign("search_message", $search_message);
}

if ($act == 'query') {
	$indicate_id = $_REQUEST['indicate_id'];
	$cond = " and indicate_id = '{$indicate_id}'";
	$list = getIndicate($cond, false, false, 0, 0);
	$indicate_detail_list = getIndicateDetail($indicate_id);
	$actual_list = getActual($indicate_id);
	$actual_detail_list = getActualDetail($indicate_id);
	
	$smarty->assign("list", $list);
	$smarty->assign("indicate_detail_list", $indicate_detail_list);
	$smarty->assign("actual_list", $actual_list);
	$smarty->assign("actual_detail_list", $actual_detail_list);
	$smarty->assign("search_text", $indicate_id);
	$smarty->assign("searchType", "indicate_id");
	
}
$smarty->assign('indicate_types', getIndicateTypes());
$smarty->assign('indicate_statuss', getIndicateStatuss());
$smarty->display("oukooext/indicate.htm");

function getIndicateTypes() {
	$indicateTypes = array (
		"INVENTORY_OUT" => "出库指示(销售)", 
		"INVENTORY_IN" => "入库指示(采购)", 
		"INVENTORY_RETURN" => "退货指示(退换货)", 
		"SUPPLIER_RETURN" => "返厂退库指示(-gt)", 
	);
	return $indicateTypes;
}

function getIndicateStatuss() {
	$indicate_statuss = array (
		"INIT" => "初始化", 
		"SENDED" => "已发出", 
		"RECEIVED" => "接收实绩", 
		"FINISHED" => "已完成", 
		"CANCEL" => "已取消",
	);
	return $indicate_statuss;
}

function getIndicate($cond, $isCount, $isLimit, $page_size, $offset) {
	global $db;
	if ($isCount) {
		$content = "count(i.indicate_id) total, sum(if(i.indicate_type='INVENTORY_OUT', 1, 0)) INVENTORY_OUT, 
				sum(if(i.indicate_type='INVENTORY_IN', 1, 0)) INVENTORY_IN,
				sum(if(i.indicate_type='INVENTORY_RETURN', 1, 0)) INVENTORY_RETURN,
				sum(if(i.indicate_type='SUPPLIER_RETURN', 1, 0)) SUPPLIER_RETURN
				";
	} else {
		$content = "i.indicate_id, i.indicate_type, i.indicate_status,  
		i.created_stamp, i.sended_stamp, i.received_stamp, i.finished_stamp, 
		i.order_id, i.order_sn, i.taobao_order_sn, i.order_time ";
	}
	$limit = $isLimit ? " limit {$page_size} offset {$offset} " : "";
	$sql = "select $content
		from ecshop.ecs_indicate i 
		where i.party_id = {$_SESSION['party_id']} {$cond} {$limit}
	";
	return $db->getAll($sql);
}

function getIndicateDetail($indicate_id) {
	global $db;
	if ($indicate_id) {
		$sql = "select indicate_detail_id, indicate_id, order_goods_id, 
		goods_id, s.color, d.goods_type, d.goods_name, d.goods_number, d.barcode 
		from ecshop.ecs_indicate_detail d 
		left join ecshop.ecs_style s on d.style_id = s.style_id 
		where d.indicate_id = {$indicate_id} 
		";
		return $db->getAll($sql);
	}
	return null;
}

function getActual($indicate_id) {
	global $db;
	if ($indicate_id) {
		$sql = "select a.actual_id, a.indicate_id, a.actual_status, a.created_stamp,
		a.finished_stamp, a.last_update_stamp, a.in_out_time, a.carrier_name, a.tracking_number
		from ecshop.ecs_actual a
		where a.indicate_id = {$indicate_id}
		";
		return $db->getAll($sql);
	}
	return null;
}

function getActualDetail($indicate_id) {
	global $db;
	if ($indicate_id) {
		$sql = "select d.actual_detail_id, d.actual_id, d.indicate_id, 
		d.indicate_detail_id, d.goods_type, d.goods_number, d.created_stamp, 
		d.last_update_stamp, d.actual_detail_status, d.msg
		from ecshop.ecs_actual_detail d
		where d.indicate_id = {$indicate_id}
		order by d.indicate_detail_id 
		";
		return $db->getAll($sql);
	}
	return null;
}