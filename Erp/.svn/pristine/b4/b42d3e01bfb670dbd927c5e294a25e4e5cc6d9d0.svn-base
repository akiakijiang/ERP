<?php
/**
 * 显示采购订单 -- 仓库查询使用(某天/某批次采购批次查询--当前及子业务组下，业务组拥有仓库及用户拥有仓库权限)
 * 
 * by ytchen 2015.11.23
 */

define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require_once(ROOT_PATH. 'includes/debug/lib_log.php');

// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if(check_goods_common_party()) {
	admin_priv('ck_in_storage_common');
} else {
	admin_priv('ck_in_storage', 'wl_in_storage');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['act']=='search'){
	//查询时，如果输入查询条件，按照条件查询；否则日期为当天
    $request_batch_order_time = $_REQUEST['batch_order_time'];  //采购日期
	$request_batch_order_sn = trim($_REQUEST['batch_order_sn']);   //采购批次
	$request_batch_arrive_time = $_REQUEST['arrive_time'];  //到期日期
	$flag= false;
	if($request_batch_order_time && strtotime($request_batch_order_time) !== false){
		$_REQUEST['batch_in_storage']['batch_order_time'] = $request_batch_order_time;
		$flag = true;
	}
	if($request_batch_arrive_time && strtotime($request_batch_arrive_time) !== false){
		$_REQUEST['batch_in_storage']['arrive_time'] = $request_batch_arrive_time;
		$flag = true;
	}
	if($request_batch_order_sn != ''){
		$_REQUEST['batch_in_storage']['batch_order_sn'] = $request_batch_order_sn;
		$flag = true;
	}
	if(!$flag){
		$_REQUEST['batch_in_storage']['batch_order_time'] = date('Y-m-d');
	}
}else{
	//默认进入页面时查询当天采购批次
    $_REQUEST['batch_in_storage']['batch_order_time'] = date('Y-m-d');
}

//拼接搜索条件：限制业务组与仓库权限
$condition = get_batch_condition();
$sql = "
	SELECT o.* FROM  {$ecs->table('batch_order_info')} AS o
	WHERE true {$condition}
    ORDER BY o.order_time DESC, o.batch_order_id
";
$refs_value_order = $refs_order = array();
$search_orders = $db->getAllRefBy($sql, array('batch_order_id'), $refs_value_order, $refs_order, false);
	
if (!empty($search_orders)) {
    $in_order_ids = db_create_in($refs_value_order['batch_order_id'], 'batch_order_id');
    // 查询订单是否已被取消
    $sql = "
	SELECT
        o.batch_order_id, count(o.batch_order_id) AS count
	FROM 
        {$ecs->table('batch_order_info')} AS o
	WHERE
        $in_order_ids and o.is_cancelled = 'Y'
    GROUP BY o.batch_order_id
    ";
    $refs_value_count = $refs_count = array();
    $db->getAllRefBy($sql, array('batch_order_id'), $refs_value_count, $refs_count);
    
    foreach ($search_orders as $key => $order) {
        $search_orders[$key]['canceled'] = $refs_count['batch_order_id'][$order['batch_order_id']][0]['count'];
        $search_orders[$key]['party_name'] = party_mapping($order['party_id']);
        $search_orders[$key]['facility_name'] = facility_mapping($order['facility_id']);
    }
}
$smarty->assign('search_orders', $search_orders);
$smarty->assign('facility_lists',implode(",",array_intersect_assoc(get_available_facility(),get_user_facility())));
$smarty->assign('party_name',party_mapping($_SESSION['party_id']));
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->display('oukooext/purchase_order_list_displayV5.htm');


/**
 * 根据request中的信息构造查询条件
 */
function get_batch_condition()
{
    $condition = "";

    // 从session 中获取检索条件
    $batch_order_sn = trim($_REQUEST['batch_in_storage']['batch_order_sn']);
    $batch_order_time  = $_REQUEST['batch_in_storage']['batch_order_time'];
    $batch_arrive_time  = $_REQUEST['batch_in_storage']['arrive_time'];

    if ($batch_order_sn != '')
    {
        $condition .= " AND batch_order_sn = '{$batch_order_sn}' ";
    }

    // 指定哪一天的
    if ($batch_order_time && strtotime($batch_order_time) !== false)
    {
        $start = $batch_order_time;
        $end = date('Y-m-d', strtotime("+1 day", strtotime($start)));
        $condition .= " AND (order_time > '{$start}' AND order_time < '{$end}') ";
    }elseif($batch_order_sn==''){
    	$condition .= " AND order_time > '".date('Y-m-d')."' ";
    }

	if ($batch_arrive_time && strtotime($batch_arrive_time) !== false){
		$condition .= " AND arrive_time < '{$batch_arrive_time}' ";
	}
	
	$facility_user_list = array_intersect_assoc(get_available_facility(),get_user_facility());
	$facility_user_str = implode("','",array_keys($facility_user_list));
    $condition .= ' AND '. party_sql('o.party_id')." AND o.facility_id in ('{$facility_user_str}')";
    
    return $condition;
}

?>