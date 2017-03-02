<?php
define('IN_ECS', true);

require('../includes/init.php');
admin_priv('taobao_goods_list');
require_once("../includes/lib_taobao.php");

$application_nicks = get_taobao_application_nicks();
$smarty->assign('application_nicks', $application_nicks);
$smarty->assign('status_list', array(
    'OK'        =>  '同步',
    'STOP'      =>  '不同步',
    'DELETE'    =>  '删除' ));

if ($_REQUEST['act'] == 'delete') {
    $taobao_goods_id = $_REQUEST['taobao_goods_id'];
    // 停止同步
    $sql = "UPDATE ecs_taobao_goods SET status = 'STOP'
     WHERE taobao_goods_id = '{$taobao_goods_id}' LIMIT 1";
    $db->query($sql);
} elseif ($_REQUEST['act'] == 'add') {
    $taobao_goods_id = $_REQUEST['taobao_goods_id'];
    $sql = "UPDATE ecs_taobao_goods SET status = 'OK'
     WHERE taobao_goods_id = '{$taobao_goods_id}' LIMIT 1";
    $db->query($sql);
} else if ($_REQUEST['act'] == 'remove') {
    $taobao_goods_id = $_REQUEST['taobao_goods_id'];
    // 去除匹配关系
    // 暂时删除，需要添加状态
    $sql = "DELETE FROM ecs_taobao_goods
     WHERE taobao_goods_id = '{$taobao_goods_id}' LIMIT 1";
    $db->query($sql);
}

$condition = get_condition();
//获得欧酷商品
$taobao_goods_list = get_taobao_goods_list($condition);
foreach ($taobao_goods_list as $key => $taobao_goods) {
    $taobao_goods_list[$key]['new_sale_status'] =
        $taobao_goods['new_sale_status'] == 'normal' ? "在售" : "非在售";
    if (($taobao_goods['new_sale_status'] == 'normal' && $taobao_goods['quantity'] == 0) ||
      ($taobao_goods['new_sale_status'] != 'normal' && $taobao_goods['quantity'] > 0) ||
      ($taobao_goods['new_price'] != $taobao_goods['price']) ) {
        $taobao_goods_list[$key]['error'] = true;
    }
    $taobao_goods_list[$key]['nick'] = $application_nicks[$taobao_goods['application_key']];
}
$smarty->assign('taobao_goods_list', $taobao_goods_list);
$smarty->display("taobao/taobao_goods_list.htm");

/**
 * 获得欧酷在淘宝的商品列表
 *
 * @param unknown_type $condition
 * @return unknown
 */
function get_taobao_goods_list($condition) {
    global $db;
    // 获取价格统一修改的配置
    $taobao_goods_fee = get_taobao_goods_fee();
    $sql = "SELECT tg.*, CONCAT_WS(',', g.goods_name, 
                IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name,
             IF(tg.style_id = 0, g.shop_price, gs.style_price) + 
               IF(tg.status = 'OK' AND g.top_cat_id != 414, {$taobao_goods_fee}, 0) AS new_price,
             IF(tg.style_id = 0, g.sale_status, gs.sale_status) AS new_sale_status
         FROM ecs_taobao_goods tg 
            LEFT JOIN ecs_goods g ON tg.goods_id = g.goods_id
            LEFT JOIN ecs_goods_style gs ON g.goods_id = gs.goods_id AND gs.style_id = tg.style_id
            LEFT JOIN ecs_style s ON gs.style_id = s.style_id
         WHERE 1 {$condition}
         ORDER BY tg.application_key, goods_name
        ";   
    $taobao_goods_list = $db->getAll($sql);
    return $taobao_goods_list;
}

/**
 * 获得条件
 *
 */
function get_condition() {
    extract($_REQUEST);
    
    $condition = "";
    if ($application_key != 'ALL') {
        $condition .= " AND application_key = '{$application_key}' ";
    }
    if ($status != 'ALL') {
        $condition .= " AND status = '". mysql_escape_string($status). "' ";
    }
    if (trim($goods_name) != '') {
        $condition .= " AND tg.title LIKE '%". mysql_escape_string($goods_name). "%' ";
    }
    if($is_use_reserve_status != 'ALL') {
    	$condition .= " AND tg.is_use_reserve = '{$is_use_reserve_status}' ";
    }
    $condition .= " AND tg.party_id = '{$_SESSION['party_id']}'";
    return $condition;
}

/**
 * 获取价格统一修改的价格配置
 *
 * @return int
 */
function get_taobao_goods_fee() {
    global $db;
    $sql = "SELECT value FROM ecs_shop_config WHERE code = 'taobao_goods_fee' LIMIT 1 ";
    $taobao_goods_fee = $db->getOne($sql);
    if ($taobao_goods_fee == null) {
        $taobao_goods_fee = 0;
    } else {
        $taobao_goods_fee = intval($taobao_goods_fee);
    }
    return $taobao_goods_fee;
}
?>