<?php
/**
 * 发票明细与开票清单明细编辑页面
 * 
 * 页面参数：
 * purchase_invoice_request_id:  开票清单id
 * 
 */

define('IN_ECS', true);
require('../includes/init.php');
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");


$purchase_invoice_soapclient = soap_get_client('PurchaseInvoiceService');
$purchase_invoice_request_id = $_REQUEST['purchase_invoice_request_id'];

$result = $purchase_invoice_soapclient->getPurchaseInvoiceRequestById(array("arg0"=>"$purchase_invoice_request_id"));
$purchase_invoice_request = $result->return->result->anyType;

// 未关联商品
$result = $purchase_invoice_soapclient->getPurchaseInvoiceRequestItemToMatchByRequestId(array("arg0"=>"$purchase_invoice_request_id"));
$purchase_invoice_request_item_to_match_list = wrap_object_to_array($result->return->result->anyType);
if (is_array($purchase_invoice_request_item_to_match_list)) {
	foreach ($purchase_invoice_request_item_to_match_list as $key => $item) {
		$purchase_invoice_request_item_to_match_list[$key]->product_map = getGoodsIdStyleIdByProductId($item->productId);
	}
}

// 已经关联商品
$result = $purchase_invoice_soapclient->getPurchaseInvoiceRequestItemMatchedByRequestId(array("arg0"=>"$purchase_invoice_request_id"));
$purchase_invoice_request_item_matched_list = wrap_object_to_array($result->return->result->anyType);
if (is_array($purchase_invoice_request_item_matched_list)) {
	foreach ($purchase_invoice_request_item_matched_list as $key => $item) {
		$purchase_invoice_request_item_matched_list[$key]->product_map = getGoodsIdStyleIdByProductId($item->productId);
		$result = $purchase_invoice_soapclient->getPurchaseInvoiceItemMatchByItem(array("arg0"=>"", "arg1"=>"{$item->purchaseInvoiceRequestItemId}"));
		$purchase_invoice_request_item_matched_list[$key]->match = $result->return->result->anyType;
	}
}

/* 获取已经开票的金额 */
$result = $purchase_invoice_soapclient->getPurchaseInvoiceItemMatchByHead(array("arg0"=>"", "arg1"=>"$purchase_invoice_request_id"));
$match_list = wrap_object_to_array($result->return->result->anyType);
$match_cost = 0;
if (is_array($match_list)) {
	foreach ($match_list as $match) {
	    if ($match->purchaseInvoiceRequestItem->returnInventoryTransactionId) {
            $match_cost -= $match->purchaseInvoiceRequestItem->returnAmount * $match->purchaseInvoiceRequestItem->returnQuantity;
	    }	    
		$match_cost += $match->purchaseInvoiceRequestItem->unitCost * $match->quantity;
	}
}
$purchase_invoice_request->match_cost = $match_cost;

//pp($purchase_invoice_request_item_matched_list);

$smarty->assign('purchase_invoice_request_item_to_match_list', $purchase_invoice_request_item_to_match_list);
$smarty->assign('purchase_invoice_request_item_matched_list', $purchase_invoice_request_item_matched_list);
$smarty->assign('purchase_invoice_request', $purchase_invoice_request);
$smarty->display('oukooext/purchase_invoice/match.htm');

?>