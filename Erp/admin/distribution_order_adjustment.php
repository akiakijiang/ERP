<?php 

/**
 * 分销订单调整项
 * 
 * @author yxiang@leqee.com
 * @copyright 2010 leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('cg_edu_sale_report');
require_once('function.php');
require_once('distribution.inc.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');


// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('search')) 
    ? $_REQUEST['act'] 
    : NULL ;

// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;

// 每页多少记录数
$page_size =
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 20 ;
    
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;

// 查询关键字
$keyword = 
    isset($_REQUEST['keyword']) && trim($_REQUEST['keyword'])
    ? $_REQUEST['keyword']
    : false;

// 开始时间
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start']) > 0
    ? $_REQUEST['start']
    : false;
    
// 结束时间
$end =
    isset($_REQUEST['end']) && strtotime($_REQUEST['end']) > 0
    ? $_REQUEST['end']
    : false;
    
// 分销商
$main_distributor_id =
    isset($_REQUEST['main_distributor_id']) && $_REQUEST['main_distributor_id'] > 0
    ? $_REQUEST['main_distributor_id']
    : false;
    

if ($end) {
	$format = explode(' ', date('Y-m-d H:i:s', strtotime($end)));
	if ($format[1]=='00:00:00') {
        $end = $format[0].' 23:59:59';		
	}
}
    
// 查询每页显示数列表
$page_size_list = array('20' => '20', '50' => '50', '100' => '100');

// 分销商列表
$main_distributor_list = Helper_Array::toHashmap(
    (array)distribution_get_main_distributor_list(), 'main_distributor_id', 'name');

// 过滤条件
$filter = array(
    // 每页多少条记录
    'size' => 
        $page_size,
    'start' => 
        $start,
    'end' => 
        $end,
    'keyword' =>
        $keyword,
    'main_distributor_id' => 
        $main_distributor_id,
);

// 链接
$url = 'distribution_order_adjustment.php';
foreach ($filter as $k => $v) {
	if ($v) {
		$url = add_param_in_url($url, $k, $v);
	}
}

if(empty($filter['start'])){
    $filter['start'] = date("Y-m-d H:i:s",strtotime("-1 month",time()));
}

if(empty($filter['end'])){
    $filter['end'] = date("Y-m-d H:i:s",time());
}
// 构造条件
if ($filter['main_distributor_id']) {
    $conditions[] = "d.main_distributor_id = '{$filter['main_distributor_id']}'";
}
if ($filter['start']) {
    $conditions[] = "a.created > '{$filter['start']}'";   
}
if ($filter['end']) {
    $conditions[] = "a.created <= '{$filter['end']}'";
}
if ($filter['keyword']) {
	$conditions[] = "o.order_sn = '{$filter['keyword']}'";
}
if (!empty($conditions)) {
    $conditions = "WHERE ". implode(' AND ', $conditions);
}


$total = $slave_db->getOne("
    SELECT COUNT(DISTINCT a.order_id)
    FROM distribution_order_adjustment a
    LEFT JOIN ecs_order_info o ON o.order_id = a.order_id
    LEFT JOIN distributor d ON d.distributor_id = o.distributor_id
    {$conditions}
    ");
if ($total) {

// 构造分页
$total_page = ceil($total/$page_size);  // 总页数
$page = max($page, 1);
$page = min($page, $total_page);
$offset = ($page - 1) * $page_size;
$limit = $page_size;

$keys = $slave_db->getCol("
    SELECT DISTINCT a.order_id
    FROM distribution_order_adjustment a
    LEFT JOIN ecs_order_info o ON o.order_id = a.order_id
    LEFT JOIN distributor d ON d.distributor_id = o.distributor_id
    {$conditions} ORDER BY a.created DESC LIMIT {$offset},{$limit}
    ");
$keys = array_chunk($keys, 50);

// 取得当页显示的按订单分组的列表
$sql = "
    SELECT a.*, o.order_sn, o.shipping_time, d.name AS distributor_name
    FROM distribution_order_adjustment as a
    LEFT JOIN ecs_order_info as o ON o.order_id = a.order_id
    LEFT JOIN distributor as d ON d.distributor_id = o.distributor_id
    WHERE a.order_id %s
";
$ref_fields = $ref_rowset = array();
foreach ($keys as $in) {
    $tmp_fields=$tmp_rowset=array();
    $result = $slave_db->getAllRefby(sprintf($sql,db_create_in($in)),array('order_id'),$tmp_fields,$tmp_rowset);
    if ($result) {
        $ref_fields = array_merge_recursive($ref_fields,$tmp_fields);
        $ref_rowset = array_merge_recursive($ref_rowset,$tmp_rowset);
    }
}

// 取得调价金额列表
$i=0;
$list = array();
foreach ($ref_rowset['order_id'] as $key=>$item) {
    $list[$i]=reset(/*&*/$ref_rowset['order_id'][$key]);
    $list[$i]['goods_list']=&$ref_rowset['order_id'][$key];
    $i++;
}

// 分页
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url, null, $filter
);

$smarty->assign('total', $total);  // 总数
$smarty->assign('list', $list);    // 当前页列表
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
}


$smarty->assign('url', $url);
$smarty->assign('filter', $filter);                          // 筛选条件
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('main_distributor_list', $main_distributor_list);   // 分销商列表
 
$smarty->display('oukooext/distribution_order_adjustment.htm');
