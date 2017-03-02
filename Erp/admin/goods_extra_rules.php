<?php

/**
 * 免运费设置
 * 
 * @author ncchen@oukoo.com
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('goods_extra_rules');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 

extract($_REQUEST);
/*
 * 添加或修改
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act == 'edit') {
    // goods_id, style_id, shipping_type, start_date, end_date, code, status, admin_user, created_time, last_modified, description
    $message = "";
    if (!is_numeric($goods_id) || $goods_id == 0) {
        $message = "商品编码错误";
    }
    if (trim($start_date) == '' || trim($end_date) == '' || strtotime($start_date) <= 0
        || strtotime($end_date) <= 0) {
        $message = "活动时间错误，0点请填写为00";
    }
    if ($end_date < date("Y-m-d H:i:s")) {
        $message = "活动结束时间必须大于当前时间";
    }
    if ($code == '') {
        $message = "活动编码错误";
    }
    if ($status == '') {
        $message = "状态错误";
    }
    if ($message == '') {
        $admin_user = $_SESSION['admin_name'];
        $goods_extra_rules = array(
            'shipping_type'     => $shipping_type,
            'code'              => $code,
            'status'            => $status,
            'admin_user'        => $admin_user,
            'description'       => $description,
            );
        if ($goods_extra_rules_id == 0) {
            // add
            $goods_extra_rules['goods_id'] = $goods_id;
            $goods_extra_rules['style_id'] = 0;
            $goods_extra_rules['start_date'] = $start_date;
            $goods_extra_rules['end_date'] = $end_date;
            $goods_extra_rules['created_time'] = date('Y-m-d H:i:s');
            $db->autoExecute("goods_extra_rules", $goods_extra_rules, "INSERT");
            $message = "添加成功";
        } else {
            // update
            $goods_extra_rules['last_modified'] = date('Y-m-d H:i:s');
            $db->autoExecute("goods_extra_rules", $goods_extra_rules, "UPDATE",
                " goods_extra_rules_id = {$goods_extra_rules_id}");
            $message = "修改成功";
        }
    }
    header("Location: goods_extra_rules.php?message=" . urlencode($message));
    exit;
}

$condition = get_condition();
$goods_extra_rules_list = get_goods_extra_rules_list($condition);

$smarty->assign('goods_extra_rules_list', $goods_extra_rules_list);

$smarty->display('oukooext/goods_extra_rules.htm');


/**
 * 取得活动时间范围内商品列表
 * 
 * @param array $condition
 * @return unknown
 */
function get_goods_extra_rules_list($condition) {
    // 取得商品信息
    $sql = "
        SELECT 
            ger.*, g.goods_party_id, g.goods_name, g.shop_price, (ger.end_date < NOW()) AS expired
        FROM
            goods_extra_rules ger 
            LEFT JOIN {$GLOBALS['ecs']->table('goods')} AS g ON ger.goods_id = g.goods_id
        WHERE 1 {$condition}
        ORDER BY ger.end_date DESC, ger.start_date DESC, ger.goods_extra_rules_id DESC
    ";
    $goods_list = $GLOBALS['db']->getAll($sql); 
    return $goods_list;
}

/**
 * 取得指定商品的信息
 *
 * @param unknown_type $condition 条件
 * @return string
 */
function get_goods_extra_rules($condition) {
    /**
    * @param $condition['goods_id'] 商品id
    * @param $condition['date'] 时间
    * @param $condition['shipping_type'] 快递方式
    */
    if ($condition['goods_id'] == '' || $condition['date'] == ''
         || $condition['shipping_type'] == '') {
        return null;
    }
    // 取得商品信息
    $sql = "
        SELECT code 
        FROM
            goods_extra_rules
        WHERE 
            status = 'OK' AND goods_id = '{$condition['goods_id']}'
            AND start_date <= '{$condition['date']}' AND end_date >= '{$condition['date']}'
            AND (shipping_type = 'ALL' OR shipping_type = '{$condition['shipping_type']}')
        ORDER BY goods_extra_rules_id DESC
        LIMIT 1
    ";
    $code_list = $GLOBALS['db']->getCol($sql);
    return implode(",", $code_list);
}

/**
 * 判断指定商品的信息
 *
 * @param unknown_type $condition 条件
 * @return string
 */
function check_goods_extra_rules($condition) {
    /**
    * @param $condition['goods_id'] 商品id
    * @param $condition['date'] 时间
    * @param $condition['shipping_type'] 快递方式
    * @param $condition['code'] 快递方式
    */
    if ($condition['goods_id'] == '' || $condition['date'] == ''
         || $condition['shipping_type'] == '' || $condition['code'] == '') {
        return null;
    }
    // 取得商品信息
    $sql = "
        SELECT 1 
        FROM
            goods_extra_rules
        WHERE 
            status = 'OK' AND goods_id = '{$condition['goods_id']}'
            AND start_date <= '{$condition['date']}' AND end_date >= '{$condition['date']}'
            AND (shipping_type = 'ALL' OR shipping_type = '{$condition['shipping_type']}')
            AND code = '{$condition['code']}'
        ORDER BY goods_extra_rules_id DESC
        LIMIT 1
    ";
    $exist = $GLOBALS['db']->getOne($sql);
    return $exist;
}

function get_condition() {
    extract($_REQUEST);
    if ($act != 'search') {
        return " AND ger.status = 'OK' AND ger.end_date > NOW() ";
    }
    $condition = "";
    if (trim($goods_name) != "") {
        $condition .= " AND g.goods_name LIKE '%". mysql_escape_string(trim($goods_name)). "%' ";
    }
    if ($shipping_type != -1) {
        $condition .= " AND ger.shipping_type = '{$shipping_type}' " ;
    }
    if ($status != -1) {
        $condition .= " AND ger.status = '{$status}' ";
    }
    if ($code != -1) {
        $condition .= " AND ger.code = '{$code}' ";
    }
    if ($expired != -1) {
        $condition .= " AND ger.end_date {$expired} NOW() ";
    }
    return $condition;
}