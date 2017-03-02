<?php
/**
 * 开票清单详细页面，可以编辑开票清单，搜索入库商品进行开票清单明细添加，以及删除商品
 *
 * 页面参数：
 * purchase_invoice_request_id: 开票清单id
 * csv: csv导出标记位，值为"csv"导出开票清单明细，值为"搜索结果导出csv"导出入库记录搜索结果
 * search: 搜索标记位，如果不为空则进行商品搜索
 * start_in_time: 搜索商品条件，起始入库时间
 * end_in_time: 搜索商品条件，结束入库时间
 * order_sn: 搜索采购单号
 * search_goods_name: 搜索商品名，用于标记search_goods_id是否有效
 * search_goods_id: 搜索商品id，仅当search_goods_name不为空时有效
 *search_goods_price:搜索商品条件，商品价格
 * search_goods_style_id: 搜索商品styleid，仅当search_goods_style不为空时有效
 */
define('IN_ECS', true);
require('../includes/init.php');
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
require_once (ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
set_time_limit(300);
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice");

$request = //请求
     isset($_REQUEST['request']) &&
     in_array($_REQUEST['request'], array('ajax'))
     ? $_REQUEST['request']
     : null ;
$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('init_request')) 
    ? $_REQUEST['act'] 
    : null ;

if($request == 'ajax'){
	$json = new JSON;
	
	switch($act) {
		case 'init_request':
			$request_id = trim($_REQUEST['request_id']);
			$check_result = check_has_invoice($request_id);
			$result = array();
			if(!$check_result['success']){
				print $json->encode($check_result);
				break;
			}else{
				$item = init_request($request_id);
				if($item){
					$result['success'] = 1;
					$result['message'] = "开票清单初始化成功";
				}else{
					$result['success'] = 0;
					$result['message'] = "开票清单初始化失败";
				}
			}
			print $json -> encode($result);
		break;
	}
	exit;
}
    
$purchase_invoice_request_id = trim($_REQUEST['purchase_invoice_request_id']);
$csv = trim($_REQUEST["csv"]);

if ($purchase_invoice_request_id == '') {
	die("开票清单id非法");
}

/* 获取开票清单 */
$purchase_invoice_soapclient = soap_get_client('PurchaseInvoiceService');
$result = $purchase_invoice_soapclient->getPurchaseInvoiceRequestById(array("arg0"=>"$purchase_invoice_request_id"));
$purchase_invoice_request = $result->return->result->anyType;
/* 获取已经加入的明细 */
$result = $purchase_invoice_soapclient->getPurchaseInvoiceRequestItemByRequestId(array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"{$purchase_invoice_request->typeId}"));
$added_purchase_invoice_request_items = wrap_object_to_array($result->return->result->anyType);
if (is_array($added_purchase_invoice_request_items)) {
	foreach ($added_purchase_invoice_request_items as $key => $item) {
		$added_purchase_invoice_request_items[$key]->product_map = getGoodsIdStyleIdByProductId($item->productId);
	}
}

/* 获取已经开票的金额 */
$result = $purchase_invoice_soapclient->getPurchaseInvoiceItemMatchByHead(array("arg0"=>"", "arg1"=>"$purchase_invoice_request_id"));
$match_list = wrap_object_to_array($result->return->result->anyType);
$match_cost = 0;
if (is_array($match_list)) {
	foreach ($match_list as $match) {
	    if ($match->purchaseInvoiceRequestItem->returnInventoryTransactionId) {
            $match_cost -= $match->purchaseInvoiceRequestItem->returnAmount;
	    }
		$match_cost += $match->purchaseInvoiceRequestItem->unitCost * $match->quantity;
	}
}
$purchase_invoice_request->match_cost = $match_cost;

/* 获取确认记录 */
$result = $purchase_invoice_soapclient->getPurchaseInvoiceRequestStatusHistory(array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"", "arg2"=>"CONFIRM", "arg3"=>"", "arg4"=>"1", "arg5"=>"1"));
$confirm_history = $result->return->result->anyType;
$purchase_invoice_request->confirm_history = $confirm_history;

/* 获取关闭记录 */
$result = $purchase_invoice_soapclient->getPurchaseInvoiceRequestStatusHistory(array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"", "arg2"=>"CLOSE", "arg3"=>"", "arg4"=>"1", "arg5"=>"1"));
$close_history = $result->return->result->anyType;
$purchase_invoice_request->close_history = $close_history;

// 搜索添加到开票清单的商品
if ($_REQUEST['search'] !== null) {
	$start_in_time = $_REQUEST['start_in_time'];
	$end_in_time = $_REQUEST['end_in_time'];
	$order_sn = $_REQUEST['order_sn'] ? $_REQUEST['order_sn'] : "";
	$search_goods_id = $_REQUEST['search_goods_name'] ? $_REQUEST['search_goods_id'] : "";
	
	$provider_order_sn = $_REQUEST['provider_order_sn'] ? $_REQUEST['provider_order_sn'] : "";
	$batch_order_sn = $_REQUEST['batch_order_sn'] ? $_REQUEST['batch_order_sn'] : "";

	$facility_id = $_REQUEST['facility_id'];
	
	$search_goods_price = trim($_REQUEST['search_goods_price']);

	$product_id_list = getProductIdListByGoodsId($search_goods_id);
	
	$result = $purchase_invoice_soapclient->getProductToRequestMinusOukooErp(array("arg0"=>"$start_in_time", "arg1"=>"$end_in_time", "arg2"=>"{$purchase_invoice_request->supplierId}", "arg3"=>$product_id_list, "arg4"=>$facility_id, "arg5"=>$_SESSION['party_id'], "arg6"=>$order_sn, "arg7"=>$provider_order_sn, "arg8"=>$search_goods_price,"arg9"=>$batch_order_sn));
	$search_purchase_invoice_request_items = $result->return->result->anyType;
	// var_dump($result->return->result);
	
	// 处理soap php端的解析问题
	if (!is_array($search_purchase_invoice_request_items) && is_object($search_purchase_invoice_request_items)) {
		$search_purchase_invoice_request_items = array($search_purchase_invoice_request_items);
	}
	
	$order_sn_array = array();
	if (is_array($search_purchase_invoice_request_items)) {
		foreach ($search_purchase_invoice_request_items as $key => $item) {
			$search_purchase_invoice_request_items[$key]->product_map = getGoodsIdStyleIdByProductId($item->productId);
			$order_sn = explode("#",$item->purchaseInvoiceRequestItemId);
			$order_sn_array[] = $order_sn[0];
		}		
	}
	
	
	$sql = "select o.order_sn,boi.provider_order_sn FROM ecshop.ecs_order_info o  
			INNER JOIN ecshop.ecs_batch_order_mapping om on o.order_id = om.order_id
			INNER JOIN ecshop.ecs_batch_order_info boi ON om.batch_order_id = boi.batch_order_id
		    WHERE o.order_sn ".db_create_in($order_sn_array);
	global $db;
	$s = $v = array();
	$db->getAllRefby($sql,array("order_sn"),$s,$v);
	foreach($order_sn_array as $key=>$item){
		$search_purchase_invoice_request_items[$key]->provider_order_sn = $v['order_sn'][$item][0]['provider_order_sn'];
	}
	$smarty->assign('search_purchase_invoice_request_items', $search_purchase_invoice_request_items);
}

//增加时间限制
$end = date("Y-m-d");
$start = date("Y-m-d", strtotime('-2 month'));
$smarty->assign('end', $end);
$smarty->assign('start', $start);

//增加仓库搜索
$smarty->assign('facility_list', facility_list());
$smarty->assign('match_list', $match_list);
$smarty->assign('purchase_invoice_request', $purchase_invoice_request);
$smarty->assign('available_purchase_invoice_request_items', $available_purchase_invoice_request_items);
$smarty->assign('added_purchase_invoice_request_items', $added_purchase_invoice_request_items);

if ($csv == "csv") {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030", get_provider_name($purchase_invoice_request->supplierId) . "开票清单") . ".csv");
	$out = $smarty->fetch('oukooext/purchase_invoice/purchase_invoice_request_detail_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();
} elseif ($csv == "搜索结果导出csv") {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030", get_provider_name($purchase_invoice_request->supplierId) . "搜索结果") . ".csv");
	$out = $smarty->fetch('oukooext/purchase_invoice/purchase_invoice_request_detail_search_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();	
} else {
	$smarty->display('oukooext/purchase_invoice/purchase_invoice_request_detail.htm');
}

/*
 * 检测开票清单能否初始化
 */
function check_has_invoice($request_id){
	global $db;
	$sql = "
		select 1 
		from romeo.purchase_invoice_request 
		where purchase_invoice_request_id = '{$request_id}'
	";
	$item = $db -> getOne($sql);
	if($item){
		$sql = "
			select ir.status as request_status,ir.purchase_invoice_request_id,i.status as invoice_status,i.invoice_no
			from romeo.purchase_invoice_request ir
			left join romeo.purchase_invoice_request_item iri on iri.purchase_invoice_request_id = ir.PURCHASE_INVOICE_REQUEST_ID
			left join romeo.purchase_invoice_item_match im on im.purchase_invoice_request_item_id = iri.PURCHASE_INVOICE_REQUEST_ITEM_ID
			left join romeo.purchase_invoice_item ii on ii.purchase_invoice_item_id = im.PURCHASE_INVOICE_ITEM_ID
			left join romeo.purchase_invoice i on i.purchase_invoice_id = ii.purchase_invoice_id
			where ir.purchase_invoice_request_id = '{$request_id}' and i.status is not null
			limit 1
		";
		$item = $db -> getRow($sql);
		if($item){
			if($item['invoice_status'] == 'INIT'){
				$message = "该开票清单已有发票关联，请先删除关联再做操作";
			}else if($item['invoice_status'] == 'CONFIRM' || $item['invoice_status'] == 'CLOSE'){
				$message = "该开票清单已有发票关联，并且进行了已审或者已复审操作，无法再进行更改";
			}
		}
	}else{
		$message = "无法找该发票清单{$request_id},请联系erp";
	}
	if(isset($message)){
		$result['message'] = $message;
		$result['success'] = 0;
	}else{
		$result['success'] = 1;
	}
	return $result;
}

function init_request($request_id){
	global $db;
	$sql = "
		update romeo.purchase_invoice_request 
		set status = 'INIT' 
		where purchase_invoice_request_id = '{$request_id}'
	";
	$result = $db -> query($sql);
	return $result;
}
?>