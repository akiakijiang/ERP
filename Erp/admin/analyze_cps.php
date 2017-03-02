<?php
define('IN_ECS', true);

require('includes/init.php');
party_priv(PARTY_OUKU);
admin_priv('tj_analyze_cps');
$start = $_REQUEST['start'];
$end = $_REQUEST['end'];
require("function.php");
require('includes/lib_cps.php');

$cps_mapping = array(
    ''=>'',
    'yiqifa' => '亿起发',
    'weiyi' => '唯一',
    'chanet' => '成果网',
    'netease' => '网易有道',
    'egou' => '易购',
);
$order_status_mapping = array('' => '');
$order_status_mapping = array_merge($order_status_mapping, $_CFG['adminvars']['order_status']);


$sql = "select cps_name, count(*) from {$ecs->table('cps_request')} group by cps_name ";
$condition = getCondition();

$sql = "SELECT c.*, o.*, u.user_name FROM {$ecs->table('cps_request')} c
        INNER JOIN {$ecs->table('order_info')} o ON c.order_sn = o.order_sn
        INNER JOIN {$ecs->table('users')} u ON o.user_id = u.user_id WHERE 1 $condition ";
$sql_c = "";
$cps_orders = $slave_db->getAll($sql);
// 返现总额
$cps_amount_total = 0;
// 商品总额
$order_amount_total = 0;
foreach ($cps_orders as $key => $order) {
    $sql = "SELECT og.goods_name, og.goods_price, og.goods_number,
             og.goods_id, g.top_cat_id, g.cat_id, g.goods_party_id,
             CASE func_get_goods_category_detail(g.top_cat_id, g.cat_id, og.goods_id, 'N')
              WHEN '电教产品' THEN '电教品' 
              WHEN 'DVD' THEN 'DVD'
              WHEN '鞋品' THEN '鞋品'
              ELSE '手机' END AS cat_name
        from {$ecs->table('order_goods')} og 
            left join {$ecs->table('goods')} g ON og.goods_id = g.goods_id
        where order_id = '{$order['order_id']}' ";
    $cps_orders[$key]['order_goods'] = $slave_db->getAll($sql);
    $cps_orders[$key]['cps_code'] = $order['cps_name'];
    $cps_orders[$key]['cps_name'] = $cps_mapping[$order['cps_name']];

    $cps_orders[$key] = get_cps_rebate($cps_orders[$key]);
    $cps_orders[$key]['goods_amount'] += $order['bonus'];
    if ($cps_orders[$key]['goods_amount'] < 0) {
        $cps_orders[$key]['goods_amount'] = 0;
    }
    // 统计总金额
    $order_amount_total += $cps_orders[$key]['goods_amount'];
    $cps_amount_total += $cps_orders[$key]['cps_amount'];
    $order_amount_cps[$cps_orders[$key]['cps_name']]['goods_amount']
        += $cps_orders[$key]['goods_amount'];
    $order_amount_cps[$cps_orders[$key]['cps_name']]['cps_amount']
        += $cps_orders[$key]['cps_amount'];
}

$smarty->assign('cps_orders', $cps_orders);
$smarty->assign('cps_orders_count', count($cps_orders));
$smarty->assign('order_amount_total', $order_amount_total);
$smarty->assign('cps_amount_total', $cps_amount_total);
$smarty->assign('order_amount_cps', $order_amount_cps);
$smarty->assign('cps_mapping', $cps_mapping);
$smarty->assign('order_status_mapping', $order_status_mapping);
$smarty->display('oukooext/analyze_cps.htm');


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
    $condition = " AND c.request_datetime >= '{$start}' AND c.request_datetime < '{$end_t}' ";
    if ($_REQUEST['cps']) {
        $condition .= " AND c.cps_name = '{$_REQUEST['cps']}' ";
    }
    if ($_REQUEST['order_status'] != '') {
        $condition .= " AND o.order_status = '{$_REQUEST['order_status']}' ";
    }
    if ($_REQUEST['party_id'] != '') {
        $condition .= " AND o.party_id = '{$_REQUEST['party_id']}' ";
    }

    return $condition;

}

/**
 * 根据订单金额和部门取得返现额
 *
 * @param unknown_type $order
 */
function getCpsByAmount($order) {
    if ($order['party_id'] == 1) {
        return getOukuCpsByAmount($order);
    } else if ($order['party_id'] == 4) {
        return getShoesCpsByAmount($order);
    }
}

/**
 * for ouku
0-100元	2
101-300元	4
301-500元	8
501-800元	10
801-1000元	12
1001-2000元	15
2001-3000元	20
3001-5000元	30（购买蓝光额外加20元返现）
5001-8000元	50
8001-10000元	80
10000元以上	返100元
 *
 * @param unknown_type $goods_amount
 * @param unknown_type $cps_name
 * @return unknown
 */
function getOukuCpsByAmount($order) {
    if ($order['cps_name'] == 'netease') {
        $goods_amount = $order['goods_amount'];
        // 在2009-11-25以前的按2%计算
        if ($order['order_time'] < '2009-11-25') {
            return $goods_amount * 0.02;
        }
        if ($goods_amount <= 100) {
            return 2;
        } else if ($goods_amount <= 300) {
            return 4;
        } else if ($goods_amount <= 500) {
            return 8;
        } else if ($goods_amount <= 800) {
            return 10;
        } else if ($goods_amount <= 1000) {
            return 12;
        } else if ($goods_amount <= 2000) {
            return 15;
        } else if ($goods_amount <= 3000) {
            return 20;
        } else if ($goods_amount <= 5000) {
            return 30;
        } else if ($goods_amount <= 8000) {
            return 50;
        } else if ($goods_amount <= 10000) {
            return 80;
        } else {
            return 100;
        }
    } else {
        return 0;
    }
}

/**
 * for 鞋子
满500返15
满700返20
满900返30
满1000返40
 *
 * @param unknown_type $order
 * @return unknown
 */
function getShoesCpsByAmount($order) {
    if ($order['cps_name'] == 'netease') {
        $goods_amount = $order['goods_amount'];
        if ($order['order_time'] < '2009-11-25') {
            return $goods_amount * 0.02;
        }
        if ($goods_amount >= 1000) {
            return 40;
        } else if ($goods_amount >= 900) {
            return 30;
        } else if ($goods_amount >= 700) {
            return 20;
        } else if ($goods_amount >= 500) {
            return 15;
        } else {
            return 0;
        }
    }
}
 
/**
 * 根据商品获得返现
 *
 * @param unknown_type $order_goods
 * @param unknown_type $cps_name
 * @return unknown
 */
function getCpsByOrderGoods($order_goods, $cps_name) {
    global $slave_db;
    // 有道，dvd额外返现20
    $category_name = $slave_db->getOne("SELECT func_get_goods_category_detail(
            '{$order_goods['top_cat_id']}', '{$order_goods['cat_id']}',
            '{$order_goods['goods_id']}', 'N') ");
    if ($cps_name == 'netease') {
        // dvd 大分类825，小分类 1157 | 1502
        if ($category_name == 'DVD') {
            return 20 * $order_goods['goods_number'];
        } else {
            return 0;
        }
    } else if ($cps_name == 'egou') {
        if ($order_goods['goods_party_id'] == 4) {
            return $order_goods['goods_number'] * $order_goods['goods_price'] * 0.07;
        } if ($order_goods['goods_party_id'] == 1
             && ($category_name == '电教产品' || $category_name == 'DVD')) {
            return $order_goods['goods_number'] * $order_goods['goods_price'] * 0.02;
        } else if ($order_goods['goods_party_id'] == 1) {
            return $order_goods['goods_number'] * $order_goods['goods_price'] * 0.01;
        }
        return 0;
    }
}