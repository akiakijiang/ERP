<?php

/**
 * 订单管理
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once('function.php');

require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

// 分页
$page_size_list =
	array('20'=>'20','50'=>'50','100'=>'100');
// 订单状态
$order_status_list =
	$_CFG['adminvars']['order_status'];
// 支付状态
$pay_status_list =
	$_CFG['adminvars']['pay_status'];
// 配送状态
$shipping_status_list =
	$_CFG['adminvars']['shipping_status'];
// 配送方式
$shipping_type_list =
	Helper_Array::toHashmap((array)getShippingTypes(),'shipping_id','shipping_name');
// 支付方式
$payment_type_list =
	Helper_Array::toHashmap((array)getPayments(),'pay_id','pay_name');
// 仓库列表
$facility_list = get_user_facility();


// 每页数据量
$page_size = 
    isset($_REQUEST['page_size']) && isset($page_size_list[$_REQUEST['page_size']])
    ? $_REQUEST['page_size']
    : 20;
// 页码
$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
/*
// 订单状态
$status =
	isset($_REQUEST['status']) && trim($_REQUEST['status'])
	? $_REQUEST['status']
	: null ; 
*/

$filter=array(
	// 分页
	'page_size'=>
		$page_size,
	// 订单状态 
	'order_status'=>
		isset($_REQUEST['order_status']) && isset($order_status_list[$_REQUEST['order_status']])
		? $_REQUEST['order_status']
		: null,
	// 支付状态 
	'pay_status'=>
		isset($_REQUEST['pay_status']) && isset($pay_status_list[$_REQUEST['pay_status']])
		? $_REQUEST['pay_status']
		: null,
	// 配送状态 
	'shipping_status'=>
		isset($_REQUEST['shipping_status']) && isset($shipping_status_list[$_REQUEST['shipping_status']])
		? $_REQUEST['shipping_status']
		: null,
	// 配送方式
	'shipping_id'=>
		isset($_REQUEST['shipping_id']) && isset($shipping_type_list[$_REQUEST['shipping_id']])
		? $_REQUEST['shipping_id']
		: null,
	// 支付方式
	'pay_id'=>
		isset($_REQUEST['pay_id']) && isset($payment_type_list[$_REQUEST['pay_id']])
		? $_REQUEST['pay_id']
		: null,
	// 仓库
	'facility_id'=>
		isset($_REQUEST['facility_id']) && isset($facility_list[$_REQUEST['facility_id']])
		? $_REQUEST['facility_id']
		: null,
	// 收货人
	'consignee'=>
		isset($_REQUEST['consignee']) && trim($_REQUEST['consignee'])
		? trim($_REQUEST['consignee'])
		: null,
	// 面单号
	'shipment_tracking_number'=>
		isset($_REQUEST['shipment_tracking_number']) && trim($_REQUEST['shipment_tracking_number'])
		? trim($_REQUEST['shipment_tracking_number'])
		: null,
	// 淘宝订单号
	'taobao_order_sn'=>
		isset($_REQUEST['taobao_order_sn']) && trim($_REQUEST['taobao_order_sn'])
		? trim($_REQUEST['taobao_order_sn'])
		: null,
	// ERP订单号
	'order_sn'=>
		isset($_REQUEST['order_sn']) && trim($_REQUEST['order_sn'])
		? trim($_REQUEST['order_sn'])
		: null,
);

// 链接
$url="order_list.php";
foreach ($filter as $filter_key=>$filter_value) {
	if(!is_null($filter_value)) {
		$url=add_param_in_url($url, $filter_key, $filter_value);
	}
}



$sql_from="from ecs_order_info as o";


// 构造分页参数
$total=$db->getOne("select count(o.order_id) {$sql_from}"); // 总记录数
if ($total > 0)
{
	$total_page=ceil($total/$page_size);  // 总页数
	$page=max(1, min($page, $total_page));
    $offset=($page-1)*$page_size;
    $limit=$page_size;
	
    // 分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $filter
    );

	$smarty->assign('total',$total);  // 总数
	$smarty->assign('pagination',$pagination->get_simple_output());  // 分页
	$smarty->assign('order_list',$order_list);  // 订单列表
}

$smarty->assign('url',$url);
$smarty->assign('filter',$filter);
$smarty->assign('order_status_list',$order_status_list);  // 发货状态 
$smarty->assign('pay_status_list',$pay_status_list);  // 支付状态列表
$smarty->assign('shipping_status_list',$shipping_status_list);  // 订单状态列表
$smarty->assign('shipping_type_list',$shipping_type_list);  // 配送方式列表
$smarty->assign('payment_type_list',$payment_type_list);  // 支付方式列表
$smarty->assign('page_size_list',$page_size_list);  // 每页分页数
#$smarty->assign('distributor_list',distribution_get_distributor_list()); // 分销商列表
$smarty->assign('facility_list',$facility_list);  // 仓库列表
	
$smarty->display('order/order_search.htm');
