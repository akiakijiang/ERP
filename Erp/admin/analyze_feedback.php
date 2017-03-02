<?php
/**
 * 客服回访统计
 */
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
party_priv(PARTY_OUKU);
admin_priv('tj_analyze_feedback');

$act = $_REQUEST['act'];
$csv = $_REQUEST['csv'];
// 当前页码
$page = is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0 ? $_REQUEST['page'] : 1 ;
$page_size = 50;
$type_list = array('feedback' => '回访人员', 'sales' => '销售人员');
$type = isset($_REQUEST['type']) && array_key_exists($_REQUEST['type'], $type_list) ? $_REQUEST['type'] : 'feedback';

$smarty->assign('type_list', $type_list);

/**
 * 搜索回访信息
 */
if ($csv != null || $act == "search") {
    $condition = getCondition();
    // 获得红包使用情况
    $feedback_list = get_feedback_list($condition, $type);
    foreach ($feedback_list as $key => $feedback) {
        $feedback_list[$key]['status'] = get_order_status($feedback['order_status']);
    }
    $smarty->assign('type', $type);
    $smarty->assign('feedback_list', $feedback_list);
}

if ($csv == null) {
    $smarty->display('oukooext/analyze_feedback.htm');
} else {
  	header("Content-type:application/vnd.ms-excel");
  	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","回访清单") . ".csv");
  	$out = $smarty->fetch('oukooext/analyze_feedback_csv.htm');
  	echo iconv("UTF-8","GB18030", $out);
  	exit();
}

/**
 * 获得客服回访信息
 *
 * @param unknown_type $condition
 */
function get_feedback_list($condition, $type) {
    global $slave_db;
    switch ($type) {
        case 'sales' :
            $time_field = 'SALESTIME';
            $name_field = 'SALESPERSON';
            break;

        case 'feedback' :
            $time_field = 'FEEDBACK_TIME';
            $name_field = 'FEEDBACK_PERSON';
            break;
    }
    // 统计订单中商品总金额，除去红包
    $sql = "
        SELECT
            oa1.attr_value as handle_person, oa2.attr_value as handle_time,
            oi.order_time, oi.order_status,oi.order_id, oi.order_sn
        FROM
            order_attribute oa1 
            INNER JOIN order_attribute oa2 ON oa1.order_id = oa2.order_id AND oa2.attr_name = '{$time_field}' 
            LEFT JOIN ecs_order_info oi ON oa1.order_id = oi.order_id
        WHERE oa1.attr_name = '{$name_field}' {$condition}
        GROUP BY oi.order_id DESC
    ";
    $feedback_list = $slave_db->getAll($sql);
    return $feedback_list;
}

/**
 * 获得搜索条件
 *
 * @return unknown
 */
function getCondition() {
    global $smarty;
    $start = $_REQUEST['start'];
    $end = $_REQUEST['end'];
    $search_name = trim($_REQUEST['search_name']);

    if (!strtotime($start)) {
        $start = date('Y-m-d');
    }
    if (!strtotime($end)) {
        $end = date('Y-m-d');
    }

    $smarty->assign('start', $start);
    $smarty->assign('end', $end);
    $end_t = date("Y-m-d", strtotime($end) + 24 * 3600);

    $condition = " AND oa2.attr_value >= '{$start}' AND oa2.attr_value < '{$end_t}' ";
    if ($_REQUEST['order_status'] && $_REQUEST['order_status'] != -1) {
        $condition .= " AND oi.order_status = '{$_REQUEST['order_status']}' ";
    }
    if ($search_name) {
        $condition .= " AND oa1.attr_value = '{$search_name}' ";
    }

    return $condition;
}
