<?php
/**
 * 发票详细页面，可以修改发票，添加发票明细
 * 
 * 页面参数：
 * invoice_no: 发票号
 * 
 */
define('IN_ECS', true);
require('../includes/init.php');
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

$purchase_invoice_soapclient = soap_get_client('PurchaseInvoiceService');

$invoice_no = trim($_REQUEST['invoice_no']);

if ($invoice_no == "") {
	die("发票号非法");
}

// 获取发票信息
$result = $purchase_invoice_soapclient->getPurchaseInvoiceByInvoiceNo(array("arg0"=>"$invoice_no"));
$purchase_invoice = $result->return->result->anyType;

// 获取发票明细信息
$result = $purchase_invoice_soapclient->getPurchaseInvoiceItemByInvoiceNo(array("arg0"=>"$invoice_no"));
$purchase_invoice_items = wrap_object_to_array($result->return->result->anyType);

// 获取发票关联信息
$result = $purchase_invoice_soapclient->getPurchaseInvoiceItemMatchByHead(array("arg0"=>"$invoice_no", "arg1"=>""));
$match_list = wrap_object_to_array($result->return->result->anyType);

$review_super = 0;
$match_count = 0;	// 匹配总数量
$match_action = 0;	// 是否做过关联操作
$supportoukuonefeng_amount = 0;

if (is_array($match_list) && count($match_list)>0){
	$match_action = 1;
}

// 获取商品信息、关联信息
if (is_array($purchase_invoice_items)) {
	foreach ($purchase_invoice_items as $key => $item) {
		$purchase_invoice_items[$key]->product_map = getGoodsIdStyleIdByProductId($item->productId);
		$result = $purchase_invoice_soapclient->getPurchaseInvoiceItemMatchByItem(array("arg0"=>"{$item->purchaseInvoiceItemId}","arg1"=>""));
		$match = wrap_object_to_array($result->return->result->anyType);
		$match_purchase_request_id_list = array();
		
		// 计算折扣商品supportoukuonefeng的金额
		if ($item->product_map['goods_id'] == 27080) {
			$supportoukuonefeng_amount += $item->totalCost;
		}
		
		if (is_array($match)) {
			foreach ($match as $match_item) {
				$match_purchase_request_id_list[] = $match_item->purchaseInvoiceRequestItem->purchaseInvoiceRequest->purchaseInvoiceRequestId;
				
				// 判断开票清单明细与发票明细是否商品匹配，用来判断需要用到超级审核功能
//				if ($match_item->purchaseInvoiceRequestItem->productId != $match_item->purchaseInvoiceItem->productId) {
                $product_mapping = getGoodsIdStyleIdByProductId($match_item->purchaseInvoiceRequestItem->productId);
                $request_goods_id = $product_mapping['goods_id'];
                $product_mapping = getGoodsIdStyleIdByProductId($match_item->purchaseInvoiceItem->productId);
                $invoice_goods_id = $product_mapping['goods_id'];
				if ($request_goods_id != $invoice_goods_id) {
					$review_super = 1;
				}
			}
			$match_purchase_request_id_list = array_unique ($match_purchase_request_id_list);
		}
		$match_count += $item->totalQuantity;
		$purchase_invoice_items[$key]->match = $match;
		$purchase_invoice_items[$key]->match_purchase_request_id_list = $match_purchase_request_id_list;
	}
}

$sql = "
	select sum(pii.total_cost) from romeo.purchase_invoice_item pii
	inner join romeo.purchase_invoice pi on pi.PURCHASE_INVOICE_ID = pii.PURCHASE_INVOICE_ID 
	where pi.INVOICE_NO = '{$invoice_no}'
";
$purchase_invoice->match_cost = $db->getOne($sql);
//$purchase_invoice->match_cost = $purchase_invoice_soapclient->getPurchaseInvoiceCostUsed(array("arg0"=>$invoice_no))->return->result->anyType + $supportoukuonefeng_amount;
$purchase_invoice->review_super = $review_super;
$purchase_invoice->match_count = $match_count;

if($invoice_no == '53398640'){
	$purchase_invoice->match_cost = 8253.50;
} 
if($invoice_no == '53398666,53398667,53398668,53398670,53398671,53398672,53398673,53398675,'){
	$purchase_invoice->match_cost = 864358.10;
}

// 获取确认记录
$result = $purchase_invoice_soapclient->getPurchaseInvoiceStatusHistory(array("arg0"=>"$invoice_no", "arg1"=>"", "arg2"=>"CONFIRM", "arg3"=>"", "arg4"=>"1", "arg5"=>"1"));
$confirm_history = $result->return->result->anyType;
$purchase_invoice->confirm_history = $confirm_history;

// 获取复审记录
$result = $purchase_invoice_soapclient->getPurchaseInvoiceStatusHistory(array("arg0"=>"$invoice_no", "arg1"=>"", "arg2"=>"CLOSE", "arg3"=>"", "arg4"=>"1", "arg5"=>"1"));
$close_history = $result->return->result->anyType;
$purchase_invoice->close_history = $close_history;

//pp($purchase_invoice_items);

$smarty->assign('purchase_invoice', $purchase_invoice);
$smarty->assign('purchase_invoice_items', $purchase_invoice_items);
$smarty->assign('match_action', $match_action);
$smarty->display('oukooext/purchase_invoice/purchase_invoice_detail.htm');
?>