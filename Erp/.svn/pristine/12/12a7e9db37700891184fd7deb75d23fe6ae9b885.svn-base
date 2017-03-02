<?php
define('IN_ECS', true);

require('../includes/init.php');
admin_priv('taobao_goods_list');


$application_nicks = get_jd_application_nicks();
$smarty->assign('application_nicks', $application_nicks);
$smarty->assign('status_list', array(
    'OK'        =>  '同步',
    'STOP'      =>  '不同步',
    'DELETE'    =>  '删除' ));

if ($_REQUEST['act'] == 'delete') {
    $jd_goods_id = $_REQUEST['jd_goods_id'];
    // 停止同步
    $sql = "UPDATE ecs_jd_goods SET status = 'STOP'
     WHERE jd_goods_id = '{$jd_goods_id}' LIMIT 1";
    $db->query($sql);
} elseif ($_REQUEST['act'] == 'add') {
    $jd_goods_id = $_REQUEST['jd_goods_id'];
    $sql = "UPDATE ecs_jd_goods SET status = 'OK'
     WHERE jd_goods_id = '{$jd_goods_id}' LIMIT 1";
    $db->query($sql);
} else if ($_REQUEST['act'] == 'remove') {
    $jd_goods_id = $_REQUEST['jd_goods_id'];
    // 去除匹配关系
    // 暂时删除，需要添加状态
    $sql = "DELETE FROM ecs_jd_goods
     WHERE jd_goods_id = '{$jd_goods_id}' LIMIT 1";
    $db->query($sql);
}

$condition = get_condition();
//获得欧酷商品
$jd_goods_list = get_jd_goods_list($condition);
foreach ($jd_goods_list as $key => $jd_goods) {
    $jd_goods_list[$key]['new_sale_status'] =
        $jd_goods['new_sale_status'] == 'normal' ? "在售" : "非在售";
    if (($jd_goods['new_sale_status'] == 'normal' && $jd_goods['quantity'] == 0) ||
      ($jd_goods['new_sale_status'] != 'normal' && $jd_goods['quantity'] > 0) ) {
        $jd_goods_list[$key]['error'] = true;
    }
    $jd_goods_list[$key]['nick'] = $application_nicks[$jd_goods['application_key']];
}
$smarty->assign('jd_goods_list', $jd_goods_list);
$smarty->display("baiduMall/baiduMall_goods_list.htm");

/**
 * 获得欧酷在淘宝的商品列表
 *
 * @param unknown_type $condition
 * @return unknown
 */
function get_jd_goods_list($condition) {
    global $db;
    // 获取价格统一修改的配置
    $taobao_goods_fee = get_taobao_goods_fee();
    $sql = "SELECT 
            jg.*, 
            CONCAT_WS(',', g.goods_name, 
                IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name,
            IF(jg.style_id = 0, g.sale_status, gs.sale_status) AS new_sale_status
         FROM ecs_jd_goods jg 
            LEFT JOIN ecs_goods g ON jg.goods_id = g.goods_id
            LEFT JOIN ecs_goods_style gs ON g.goods_id = gs.goods_id AND gs.style_id = jg.style_id
            LEFT JOIN ecs_style s ON gs.style_id = s.style_id
         WHERE jg.sku_status = 'Valid' {$condition}
         ORDER BY jg.application_key, goods_name
        ";   
    $jd_goods_list = $db->getAll($sql);
    return $jd_goods_list;
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
        $condition .= " AND g.goods_name LIKE '%". mysql_escape_string($goods_name). "%' ";
    }
    if($is_use_reserve_status != 'ALL') {
    	$condition .= " AND jg.is_use_reserve = '{$is_use_reserve_status}' ";
    }
    $condition .= " AND jg.party_id = '{$_SESSION['party_id']}'";
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

/* 获取京东店铺信息 -nick*/
function get_jd_application_nicks($condition = null){
    global $db;
    $sql = "SELECT * FROM taobao_shop_conf WHERE status = 'OK' and shop_type = '360buy' ";
    if (!empty($condition)) {
        $sql .= " {$condition} ";
    }
    $application_list = $db->getAll($sql);
    $res = array();
    foreach ($application_list as $application) {
        $res[$application['application_key']] = $application['nick'];
    }
    return $res;
}

?>