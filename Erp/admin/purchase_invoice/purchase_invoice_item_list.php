<?php
/**
 * 查看发票明细与开票清单明细的关联
 * 
 * 页面参数：
 * invoice_no: 发票号
 */

define('IN_ECS', true);
require('../includes/init.php');
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

$invoice_no = trim($_REQUEST['invoice_no']);

if ($invoice_no == "") {
	die("发票号非法");
}

$purchase_invoice_soapclient = soap_get_client('PurchaseInvoiceService');

$result = $purchase_invoice_soapclient->getPurchaseInvoiceItemMatchByHead(array("arg0"=>"$invoice_no", "arg1"=>""));
$match_list = wrap_object_to_array($result->return->result->anyType);

if (is_array($match_list)) {
	foreach ($match_list as $key => $item) {
		$match_list[$key]->purchaseInvoiceItem->product_map = getGoodsIdStyleIdByProductId($item->purchaseInvoiceItem->productId);
		$match_list[$key]->purchaseInvoiceRequestItem->product_map = getGoodsIdStyleIdByProductId($item->purchaseInvoiceRequestItem->productId);
	}
}

//pp(array("arg0"=>"$invoice_no", "arg1"=>""), $result);
$smarty->assign('match_list', $match_list);
$smarty->display('oukooext/purchase_invoice/purchase_invoice_item_list.htm');
?>