<?php

/**
 * 订单管理
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');

require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

// 分页
$page_size_list =
	array('20'=>'20','50'=>'50','100'=>'100');

// 每页数据量
$page_size = 
    is_numeric($_REQUEST['size']) && in_array($_REQUEST['size'], $page_size_list)
    ? $_REQUEST['size']
    : 20;
// 页码
$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
// 订单状态
$status =
	isset($_REQUEST['status']) && trim($_REQUEST['status'])
	? $_REQUEST['status']
	: null ; 

$filter=array(
	'page_size'=>$page_size,
	'status'=>$status,
);

// 链接
$url="order_list.php";
$url=add_param_in_url($url, 'status', $status);


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
#$smarty->assign('order_status_list',$order_status_list);  // 发货状态 
#$smarty->assign('shipping_status_list',$shipping_status_list);  // 订单状态列表
#$smarty->assign('pay_status_list',$pay_status_list);  // 支付状态列表
#$smarty->assign('distributor_list',distribution_get_distributor_list()); // 分销商列表
#$smarty->assign('facility_list',$facility_list);  // 仓库列表
#$smarty->assign('shipping_list',$shipping_list);  // 配送方式列表
#$smarty->assign('payment_list',payment_list());  // 支付方式列表
$smarty->assign('page_size_list',$page_size_list);  // 每页分页数
	
$smarty->display('order/order_list.htm');
