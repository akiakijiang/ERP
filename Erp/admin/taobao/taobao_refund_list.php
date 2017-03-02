<?php
define('IN_ECS', true);

require('../includes/init.php');
admin_priv('taobao_refund_list');
require_once("../includes/lib_taobao.php");

$application_nicks = get_taobao_application_nicks();
$smarty->assign('application_nicks', $application_nicks);

$condition = get_condition();
//获得欧酷商品
$taobao_refund_list = get_taobao_refund_list($condition);

foreach ($taobao_refund_list as $key => $taobao_refund) {
    $taobao_refund_list[$key]['nick'] = $application_nicks[$taobao_refund['application_key']];
}

$status_list = array(
    'WAIT_SELLER_AGREE'         =>  '买家已经申请退款，等待卖家同意',
    'WAIT_BUYER_RETURN_GOODS'   =>  '卖家已经同意退款，等待买家退货',
    'WAIT_SELLER_CONFIRM_GOODS' =>  '买家已经退货，等待卖家确认收货',
    'SELLER_REFUSE_BUYER'       =>  '卖家拒绝退款',
    'SUCCESS'                   =>  '退款成功',
    'CLOSED'                    =>  '退款关闭',
    );

$smarty->assign('status_list', $status_list);
$smarty->assign('taobao_refund_list', $taobao_refund_list);
$smarty->display("taobao/taobao_refund_list.htm");


/**
 * 获得欧酷在淘宝的退款列表
 *
 * @param unknown_type $condition
 * @return unknown
 */
function get_taobao_refund_list($condition) {
    global $db;
    $sql = "SELECT tr.*, r.REFUND_ID as romeo_refund_id, oi.order_sn, oi.order_amount, 
            (SELECT oa.attr_value
             FROM order_attribute oa 
             WHERE oa.order_id = oi.order_id
                AND oa.attr_name = 'TAOBAO_ORDER_AMOUNT'
             LIMIT 1) AS original_order_amount,
            (SELECT SUM(TOTAL_AMOUNT) FROM romeo.refund r1
             WHERE r1.STATUS = 'RFND_STTS_EXECUTED'
                AND r1.ORDER_ID = tr.order_id 
             GROUP BY r1.ORDER_ID) AS total_refund,
             oi.order_status
         FROM taobao_refund tr
            LEFT JOIN ecs_order_info oi ON tr.order_id = oi.order_id
            LEFT JOIN romeo.refund r 
                on r.ORDER_ID = tr.order_id AND tr.romeo_refund_id = r.refund_id
         WHERE 1 {$condition}
         ORDER BY tr.application_key
        ";
    $taobao_refund_list = $db->getAll($sql);
    return $taobao_refund_list;
}

/**
 * 获得条件
 *
 */
function get_condition() {
    extract($_REQUEST);
    $condition = "";
    if ($application_key != 'ALL' && $application_key != "") {
        $condition .= " AND tr.application_key = '{$application_key}' ";
    }
    if (trim($status) == "") {
        $status = "WAIT_SELLER_AGREE";
    }
    if ($status != "" && $status != 'ALL') {
        $condition .= " AND tr.status = '{$status}' ";
    }
    return $condition;
}

?>