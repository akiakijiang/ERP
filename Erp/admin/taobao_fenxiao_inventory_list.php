<?php

/**
 * 查看预付款交易明细
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once ('includes/debug/lib_log.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'includes/cls_page.php');


/**
 * 
 * 获取库存信息
 * @param $outer_id
 */
function get_inventory_info($outer_id,$begin_time,$end_time,$offset,$limit,$num_iid=null,$sku_id=null) {
	global $db;
	$order_sql = " order by created_time desc ";
	$and_sql = " where outer_id='{$outer_id}' ";
	$limit_sql = " limit {$offset},{$limit} ";
	
	if($num_iid) {
		$and_sql .=" and pid='{$num_iid}' ";
	}
	if(isset($_REQUEST['sku_id']) && trim($_REQUEST['sku_id']) != '') {
		$and_sql .=" and sku_id='{$sku_id}' ";
	}
	
	if ($begin_time) {
		$and_sql .= " and created_time>='{$begin_time}'";
	}
	if ($end_time) {
		$and_sql .= " and created_time<='{$end_time}'";
	}
	$sql = "select pid as num_iid,sku_id,outer_id,stock_quantity,facility_reserved_quantity,wait_send_quantity,supplier_return_quantity,(stock_quantity-wait_send_quantity) as new_quantity,taobao_quantity,created_time from ecshop.sync_taobao_fenxiao_inventory" 
	       . $and_sql . $order_sql . $limit_sql;
	QLog::log(var_export($sql,true));
	return $db->getAll($sql);
}

function get_inventory_count($outer_id,$begin_time,$end_time) {
	global $db;
	$and_sql = " where outer_id='{$outer_id}' ";
	if ($begin_time) {
		$and_sql .= " and created_time>='{$begin_time}'";
	}
	if ($end_time) {
		$and_sql .= " and created_time<='{$end_time}'";
	}
	$sql = "select count(1) from ecshop.sync_taobao_fenxiao_inventory" . $and_sql;
	return intval($db->getOne($sql));
}


// 处理请求
$outerId = isset($_REQUEST['outerId']) ? $_REQUEST['outerId'] : 
		      (isset($_REQUEST['filter_outerId']) ? $_REQUEST['filter_outerId'] : null);


$start = isset($_REQUEST['filter_start']) && strtotime($_REQUEST['filter_start']) 
    ? $_REQUEST['filter_start'] 
    : null;
$end = isset($_REQUEST['filter_end']) && strtotime($_REQUEST['filter_end']) 
    ? $_REQUEST['filter_end'] 
    : null;
$page =         // 当前页码
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
    

// 取得交易总数
$total = get_inventory_count($outerId,$start,$end);
   
// 构造分页
$page_size = 10;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;

$extra_params = array('outerId' => $outerId);
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url = 'taobao_fenxiao_inventory_list.php', null, $extra_params
);

// 取得库存同步情况列表
$inventory_info = get_inventory_info($outerId,$start,$end,$offset,$limit,$num_iid,$sku_id);

$smarty->assign('outerId',$outerId);
$smarty->assign('inventory_info',$inventory_info);        
$smarty->assign('total',$total); 
$smarty->assign('pagination',$pagination->get_simple_output());

$smarty->display('oukooext/taobao_inventory_list.htm');
