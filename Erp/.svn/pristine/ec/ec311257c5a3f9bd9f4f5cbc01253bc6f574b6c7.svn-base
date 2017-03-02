<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
admin_priv('taobao_order_list');
require_once('../includes/lib_taobao.php');

$size = 20;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
if ($csv == null) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
}

$application_nicks = get_taobao_application_nicks();
$smarty->assign('application_nicks', $application_nicks);

$condition = get_condition();


//添加ecs_order_mapping中间表

$sql = "SELECT o.order_sn, o.order_amount, o.order_status, o.shipping_status as ss, m.* 
    FROM ecs_order_mapping m, ecs_order_info o 
    WHERE m.erp_order_id = o.order_id {$condition} 
    ORDER BY created_time DESC $limit $offset";
$taobao_order_list = $slave_db->getAll($sql);
foreach ($taobao_order_list as $key => $taobao_order) {
    $taobao_order_list[$key]['order_status_desc'] =
        $_CFG['adminvars']['order_status'][$taobao_order['order_status']];
    $taobao_order_list[$key]['shipping_status_desc'] =
        $_CFG['adminvars']['shipping_status'][$taobao_order['ss']];
    $taobao_order_list[$key]['nick'] = $application_nicks[$taobao_order['application_key']];
}

//添加ecs_order_mapping中间表
$sql = "SELECT COUNT(*) 
        FROM ecs_order_mapping m, ecs_order_info o 
        WHERE m.erp_order_id = o.order_id {$condition} 
        ORDER BY created_time DESC";
$count = $slave_db->getOne($sql);
$pager = Pager($count, $size, $page);

$smarty->assign('pager', $pager);
$smarty->assign('taobao_order_list', $taobao_order_list);
$smarty->display('taobao/taobao_order_list.htm');

function get_condition() {
    $condition = "";
    $application_key = trim($_REQUEST['application_key']);
    $order_sn = trim($_REQUEST['order_sn']);
    $taobao_order_sn = trim($_REQUEST['taobao_order_sn']);
    $start_time = trim($_REQUEST['start_time']);
    $end_time = trim($_REQUEST['end_time']);
    $order_status = trim($_REQUEST['order_status']);
    $shipping_status = trim($_REQUEST['shipping_status']);
    $type = trim($_REQUEST['type']) ? trim($_REQUEST['type']) : "fixed";
    
    if ($application_key != "ALL" && $application_key != "") {
        $condition .= " AND m.application_key = '{$application_key}'";
    }
    
    if ($order_sn != "") {
        $condition .= " AND o.order_sn = '{$order_sn}'";
    }
    
    if ($taobao_order_sn != "") {
        $condition .= " AND m.outer_order_sn = '{$taobao_order_sn}'";
    }
    
    if (strtotime($start_time) > 0) {
        $condition .= " AND created_time > '{$start_time}'";
    }
    
    if (strtotime($end_time) > 0) {
        $end_time = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($end_time)));
        $condition .= " AND created_time < '{$end_time}'";
    }
    
    if ($order_status != "all") {
        $condition .= " AND o.order_status = '{$order_status}'";
    }
    
    if ($shipping_status != "all") {
        $condition .= " AND o.shipping_status = '{$shipping_status}'";
    }
    
    if ($type != "all") {
        $condition .= " AND m.platform = '{$type}' ";
    }
    
    $condition .= " AND ". party_sql("o.party_id") ;
    return $condition;
}

?>