<?php
/**
 * 开票清单列表页面，可以进行搜索
 * 
 * 页面参数：
 * search: 标记页面是否出搜索结果
 * purchase_invoice_request_id： 搜索条件，开票清单id
 * provider_name: 搜索条件，用于控制provider_id是否有效
 * provider_id: 搜索条件，供应商id，仅当provider_name不为空时有效
 * status: 搜索条件，开票清单状态
 * start_invoice_time: 搜索条件，开票清单状态
 * end_invoice_time: 搜索条件，开票清单状态
 * page: 搜索结果第几页
 */
define('IN_ECS', true);
require('../includes/init.php');
admin_priv("cw_review_purchase_invoice", "cw_review_purchase_invoice_plus", "cg_edit_purchase_invoice", "cg_purchase_list");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

if(in_array($_SESSION['party_id'], array('65614', '65572'))){
	die('业务组：玛氏宠物食品、百威英博 请用下面的开票模式：新开票清单管理');
}

$search = trim($_REQUEST['search']);

if ($search) {
	$purchase_invoice_soapclient = soap_get_client('PurchaseInvoiceService');
	
	$purchase_invoice_request_id = trim($_REQUEST['purchase_invoice_request_id']);
	$provider_id = trim($_REQUEST['provider_name']) ? intval($_REQUEST['provider_id']) : "";
	$status = trim($_REQUEST['status']);
    //增加时间筛选项
	$start_invoice_time = trim($_REQUEST['start_invoice_time']);
	$end_invoice_time = trim($_REQUEST['end_invoice_time']);
	
	$page = intval($_REQUEST['page']) > 0 ? intval($_REQUEST['page']) : 1;
	$size = 20;
	
	$result = $purchase_invoice_soapclient->getPurchaseInvoiceRequest(array("arg0"=>"$purchase_invoice_request_id", "arg1"=>"", "arg2"=>"$provider_id", "arg3"=>"$status", "arg4"=>"$page", "arg5"=>"$size","arg6"=>"$start_invoice_time","arg7"=>"$end_invoice_time"));
	$purchase_invoice_request_list = wrap_object_to_array($result->return->result->anyType);
   
	$count = $result->return->size;
	$pager = Pager($count, $size);
	
	$smarty->assign('pager', $pager);
	$smarty->assign('purchase_invoice_request_list', $purchase_invoice_request_list);	
}
   //开票清单明细导出CSV
  $purchase_invoice_request_id_excel = trim($_POST['purchase_invoice_request_id_excel']);
  $provider_name = trim($_POST['provider_name']);
  if ($purchase_invoice_request_id_excel) {
  	
    $sql="
    	select 
    	oi.order_sn,go.goods_sn,go.barcode,og.goods_name,re.quantity,re.unit_cost,re.unit_net_cost
    	from romeo.purchase_invoice_request_item as re
    	left join romeo.inventory_item_detail as de
    	on de.inventory_transaction_id = re.inventory_transaction_id  
		left join ecshop.ecs_order_goods as og
    	on og.order_id = de.order_id
    	left join ecshop.ecs_provider as pr 
    	on og.provider_id = pr.provider_id
    	left join ecshop.ecs_order_info as oi
    	on de.order_id = oi.order_id
    	left join ecshop.ecs_goods as go 
    	on go.goods_id = og.goods_id
    	where re.purchase_invoice_request_id = '$purchase_invoice_request_id_excel'
    	order by og.goods_name ";

    $purchase_result = $db->getAll($sql);
    $purchase_invoice_details = wrap_object_to_array($purchase_result);

    $smarty->assign('purchase_invoice_request_id_excel',$purchase_invoice_request_id_excel);
    $smarty->assign('provider_name',$provider_name);
    $smarty->assign('purchase_invoice_details',$purchase_invoice_details);
    header ( "Content-type:application/vnd.ms-excel" );
    header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", $provider_name . "开票清单明细" ) . ".csv" );
    $out = $smarty->fetch ( 'oukooext/purchase_invoice/purchase_invoice_request_list_csv.htm' );
    echo iconv ( "UTF-8", "GB18030", $out );
    exit ();
  }

$smarty->display('oukooext/purchase_invoice/purchase_invoice_request_list.htm');

?>