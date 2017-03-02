<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
party_priv(PARTY_OUKU);
admin_priv('tj_analyze_bonus');

$act = $_REQUEST['act'];
// 当前页码
$page = is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0 ? $_REQUEST['page'] : 1 ;
$page_size = 50;

/**
 * 搜索红包信息
 */
if ($act == "search") {
    $search_name = $_REQUEST['search_name'];
    if (trim($search_name) == '') {
        $smarty->assign('message', "请填写查询条件");
    } else {
        $bonus_config_list = get_bonus_config_list($search_name, $_REQUEST['bonus_type']);
        $bonus_count = count($bonus_config_list);
        if ($bonus_count != 0) {
            $bonus_list = get_bonus_list($bonus_config_list, $page, $page_size);
            if (count($bonus_list) > 0) {
                $condition = getCondition();
                $gtc_id_condition = getGtcIdCondition($bonus_list);
                // 获得红包使用情况
                $bonus_info_list = get_bonus_info_list($condition. $gtc_id_condition);
                if ($bonus_info_list) {
                    foreach ($bonus_info_list as $bonus_info) {
                        $bonus_list[$bonus_info['gtc_id']]['bonus_info'] = $bonus_info;
                    }
                }
                // 获得红包发送总数
                $bonus_total_list = get_bonus_total_list($gtc_id_condition);
                if ($bonus_total_list) {
                    foreach ($bonus_total_list as $bonus_total) {
                        $bonus_list[$bonus_total['gtc_id']]['bonus_info']['total'] =
                            $bonus_total['total'];
                    }
                }
            }
        }
        $pager = Pager($bonus_count, $page_size, $page);
        $smarty->assign('bonus_info_list', $bonus_list);
        $smarty->assign('pager', $pager);
    }
}
$smarty->display('oukooext/analyze_bonus.htm');

/**
 * 获得红包配置信息
 *
 * @param unknown_type $search_name
 * @param unknown_type $bonus_type
 * @return unknown
 */
function get_bonus_config_list($search_name, $bonus_type) {
    global $slave_db;
    if ($bonus_type) {
        $sql_type = " AND gtc_type_id = '{$bonus_type}' ";
    }
    $sql = "SELECT gtc_id, gtc_comment FROM membership.ok_gift_ticket_config 
        WHERE gtc_comment LIKE '%{$search_name}%' {$sql_type} order by gtc_id desc ";
    $fields = array('gtc_id');
    $fields_value = array();
    $ref = array();
    $slave_db->getAllRefby($sql, $fields, $fields_value, $ref);
    $bonus_config_list = $ref['gtc_id'];
    return $bonus_config_list;
}
/**
 * 获得红包使用信息
 *
 * @param unknown_type $condition
 */
function get_bonus_info_list($condition) {
    global $slave_db;
    // 统计订单中商品总金额，除去红包
    $sql = "SELECT gt.gtc_id, COUNT( * ) as used, SUM( oi.goods_amount + oi.bonus) as goods_amount,
            SUM( oi.bonus ) as bonus_amount
        FROM membership.ok_gift_ticket gt
            LEFT JOIN ecshop.ecs_order_info oi ON gt.gt_code = oi.bonus_id
        WHERE gt.gt_state = 4 AND gt.user_id !=  '' {$condition} 
        GROUP BY gt.gtc_id
        ";
    $bonus_info_list = $slave_db->getAll($sql);
    foreach ($bonus_info_list as $key => $bonus) {
        $bonus_info_list[$key]['ave'] =
            $bonus['goods_amount'] / ($bonus['used'] > 0 ? $bonus['used']:0);
    }
    return $bonus_info_list;
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

    if (!strtotime($start)) {
        $start = date('Y-m-d');
    }
    if (!strtotime($end)) {
        $end = date('Y-m-d');
    }

    $smarty->assign('start', $start);
    $smarty->assign('end', $end);
    $end_t = date("Y-m-d", strtotime($end) + 24 * 3600);

    $condition = " AND oi.order_time >= '{$start}' AND oi.order_time < '{$end_t}' ";
    if ($_REQUEST['order_status']) {
        $condition .= " AND oi.order_status = '{$_REQUEST['order_status']}' ";
    }

    return $condition;
}

/**
 * 获得红包id组合条件
 *
 * @param unknown_type $bonus_config_list
 * @return unknown
 */
function getGtcIdCondition($bonus_config_list) {
    $condition = "";
    if (!empty($bonus_config_list)) {
        $gtc_ids = array();
        foreach ($bonus_config_list as $bonus_config) {
            $gtc_ids[] = $bonus_config[0]['gtc_id'];
        }
        $condition = " AND gtc_id IN ( ". join(',', $gtc_ids). " ) ";
    }
    return $condition;
}

/**
 * 统计红包数量
 *
 * @param unknown_type $condition
 * @return unknown
 */
function get_bonus_total_list($condition) {
    global $slave_db;
    // 统计红包数量
    $sql = "SELECT gtc_id, COUNT( * ) as total
        FROM membership.ok_gift_ticket
        WHERE 1 {$condition} 
        GROUP BY gtc_id
        ";
    $bonus_total_list = $slave_db->getAll($sql);
    return $bonus_total_list;
}

/**
 * 获取指定页的红包列表
 *
 * @param unknown_type $bonus_config_list
 * @param unknown_type $page
 * @param unknown_type $page_size
 * @return unknown
 */
function get_bonus_list($bonus_config_list, $page, $page_size) {
    $bonus_list = array();
    $i = -1;
    foreach ($bonus_config_list as $key => $bonus_config) {
        $i = $i + 1;
        if ($i < ($page-1)*$page_size) {
            continue;
        }
        if ($i >= $page*$page_size) {
            break;
        }
        $bonus_list[$key] = $bonus_config;
    }
    return $bonus_list;
}