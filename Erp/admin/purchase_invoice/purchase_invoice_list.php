<?php
/**
 * 显示发票列表，可以进行搜索
 * 
 * 页面参数：
 * invoice_no: 发票号
 * provider_id: 搜索条件，供应商id，只有当provider_name不为空时有效
 * provider_name: 搜索条件，供应商名，根据这个参数判断provider_id是否有效
 * status: 搜索条件，发票状态
 * start_invoice_time: 搜索条件，起始发票时间
 * end_invoice_time: 搜索条件，结束发票时间
 * search: 搜索标记，不为空时才会进行搜索
 * page: 搜索结果第几页
 */
define('IN_ECS', true);
require('../includes/init.php');
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

$search = trim($_REQUEST['search']);
$csv = trim($_REQUEST['csv']);

if ($search || $csv) {
	$purchase_invoice_soapclient = soap_get_client('PurchaseInvoiceService');
	$invoice_no = trim($_REQUEST['invoice_no']);
	$provider_id = trim($_REQUEST['provider_name']) ? intval($_REQUEST['provider_id']) : "";
	$status = trim($_REQUEST['status']);
	$start_invoice_time = trim($_REQUEST['start_invoice_time']);
	$end_invoice_time = trim($_REQUEST['end_invoice_time']);
	$is_not_used = trim($_REQUEST['is_not_used']);
	if (!$csv) {
    	$page = intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
    	$size = 20;	
	} else {
	    $page = $size = -1;
	}
	$params = array("arg0"=>"$invoice_no", 
	    "arg1"=>"", "arg2"=>"$provider_id", "arg3"=>"$status", 
	    "arg4"=>"$start_invoice_time", "arg5"=>"$end_invoice_time", 
	    "arg6"=>"$page", "arg7"=>"$size", "arg8"=>"$is_not_used");
	$result = $purchase_invoice_soapclient->getPurchaseInvoice($params);
	$purchase_invoice_list = wrap_object_to_array($result->return->result->anyType);
	$count = $result->return->size;
	if (!$csv) {
	    $pager = Pager($count, $size);
	    $smarty->assign('pager', $pager);
	}
	
	$smarty->assign('purchase_invoice_list', $purchase_invoice_list);
}

if ($csv) {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","采购发票列表") . ".csv");	
	$out = $smarty->fetch('oukooext/purchase_invoice/purchase_invoice_list_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
	exit();
} else {
	$smarty->display('oukooext/purchase_invoice/purchase_invoice_list.htm');
}

?>