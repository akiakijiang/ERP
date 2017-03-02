<?php
/**
 * 1. 展示搜索屏蔽电话
 *
 * @author ncchen
 */
define('IN_ECS', true);

require('../includes/init.php');
require('../function.php');
admin_priv('callcenter_mask_phone');

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

$limit = "LIMIT $size";
$offset = "OFFSET $start";

$sql_condition = get_condition();
// 搜索屏蔽电话号码
$sql_base = "SELECT distinct mp.order_sn 
             FROM callcenter_mask_phone mp
                LEFT JOIN ecs_order_info oi 
                    ON oi.order_sn = mp.order_sn
                LEFT JOIN callcenter_mask_talk_history mth 
                    ON mth.mask_phone_no = mp.mask_phone_no
             WHERE 1 {$sql_condition} ORDER BY created_time DESC
            $limit $offset";
$order_sns = $slave_db->getCol($sql_base);
$sql_order_sns = db_create_in($order_sns, 'mp.order_sn');
$sql = "SELECT mp.mask_phone_no, mp.cus_phone_no, created_time, actived_time, no_status, oi.order_id,
            mth.carrier_phone_no, mth.in_begin_time, mth.in_end_time, mth.out_begin_time,
            mth.out_end_time, mth.call_status, mth.record_file_url, mp.order_sn,
            oi.shipping_status, oi.shipping_name, c.bill_no, c.carrier_id
         FROM callcenter_mask_phone mp 
            LEFT JOIN ecs_order_info oi ON oi.order_sn = mp.order_sn
            LEFT JOIN ecs_carrier_bill c ON oi.carrier_bill_id = c.bill_id
            LEFT JOIN callcenter_mask_talk_history mth ON mth.mask_phone_no = mp.mask_phone_no
         WHERE {$sql_order_sns}";
$sql_c = " SELECT count(distinct mp.order_sn) FROM callcenter_mask_phone mp 
            LEFT JOIN ecs_order_info oi ON oi.order_sn = mp.order_sn
            LEFT JOIN callcenter_mask_talk_history mth ON mth.mask_phone_no = mp.mask_phone_no
           WHERE 1 {$sql_condition} ";

$fields_value = $ref = array();
$slave_db->getAllRefby($sql, array("order_sn"), $fields_value, $ref);
$count = $slave_db->getOne($sql_c);

$mask_phones = array();
if ($ref['order_sn']) {
    foreach ($ref['order_sn'] as $order_sn => $mask_phone) {
        $mask_phones[$order_sn] = array();
        foreach ($mask_phone as $phone) {
            $mask_phone_no = $phone['mask_phone_no'];
            if (empty($mask_phones[$order_sn][$mask_phone_no])) {
                $mask_phones[$order_sn][$mask_phone_no] = array();
                $mask_phones[$order_sn][$mask_phone_no]['total'] = count($mask_phone);
                $mask_phones[$order_sn][$mask_phone_no]['talk_history'] = array();
                $mask_phones[$order_sn][$mask_phone_no]['mask_phone_no'] = $mask_phone_no;
                $mask_phones[$order_sn][$mask_phone_no]['cus_phone_no'] = $phone['cus_phone_no'];
                $mask_phones[$order_sn][$mask_phone_no]['created_time'] = $phone['created_time'];
                $mask_phones[$order_sn][$mask_phone_no]['actived_time'] = $phone['actived_time'];
                $mask_phones[$order_sn][$mask_phone_no]['no_status'] = $phone['no_status'];
                $mask_phones[$order_sn][$mask_phone_no]['order_id'] = $phone['order_id'];
                $mask_phones[$order_sn][$mask_phone_no]['order_sn'] = $phone['order_sn'];
                $mask_phones[$order_sn][$mask_phone_no]['shipping_status'] = $phone['shipping_status'];
                $mask_phones[$order_sn][$mask_phone_no]['bill_no'] = $phone['bill_no'];
                $mask_phones[$order_sn][$mask_phone_no]['shipping_name'] = $phone['shipping_name'];
                $mask_phones[$order_sn][$mask_phone_no]['carrier_id'] = $phone['carrier_id'];
            }
            if ($phone['carrier_phone_no']) {
                $mask_phones[$order_sn][$mask_phone_no]['talk_history'][] = $phone;
            }
        }
    }
}
$pager = Pager($count, $size, $page, remove_param_in_url($_SERVER['REQUEST_URI'], 'info'));
$smarty->assign('pager', $pager);
$smarty->assign("mask_phones", $mask_phones);
$smarty->display("call_center/list_mask_phone.htm");

/**
 * 获得搜索条件
 *
 * @return unknown
 */
function get_condition() {
    extract($_REQUEST);
    $sql_condition = "";
    if ($act != 'search') {
        $sql_condition .= " AND shipping_status = '1' "; 
        return $sql_condition;
    }
    if ($start) {
        $sql_condition .= " AND actived_time > '{$start}' ";
    }
    if ($end) {
        $sql_condition .= " AND actived_time < '{$end} 23:59:59' ";
    }
    if (trim($search_text) != '') {
        $sql_condition .= " AND (mp.mask_phone_no LIKE '{$search_text}' 
            OR mp.cus_phone_no LIKE '{$search_text}' 
            OR mp.order_sn  LIKE '{$search_text}') ";
    }
    if ($no_status != 'ALL' && trim($no_status) != '') {
        $sql_condition .= " AND no_status = '{$no_status}' ";
    }
    if ($call_status == 'Y') {
        $sql_condition .= " AND mth.carrier_phone_no IS NOT NULL ";
    } else if ($call_status == 'N') {
        $sql_condition .= " AND mth.carrier_phone_no IS NULL ";
    }
    if (trim($shipping_status) == '' || $shipping_status == 'fahuo') {
        $sql_condition .= " AND shipping_status = '1' "; 
    } else if ($shipping_status == 'weifahuo') {
        $sql_condition .= " AND shipping_status not in (1, 2) ";
    } else if ($shipping_status == 'shouhuo') {
        $sql_condition .= " AND shipping_status = '2' ";
    }
    return $sql_condition;
}