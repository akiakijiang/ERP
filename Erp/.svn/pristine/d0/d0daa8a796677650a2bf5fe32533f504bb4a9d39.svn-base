<?php


/**
 * 获得淘宝应用的昵称
 *
 * @param unknown_type $condition
 * @return unknown
 */
function get_taobao_application_nicks($condition = null) {
    $application_list = get_taobao_application_list($condition);
    $application_nicks = array();
    foreach ($application_list as $application) {
        $application_nicks[$application['application_key']] = $application['nick'];
    }
    return $application_nicks;
}

/**
 * 获得淘宝应用的配置
 *
 * @param unknown_type $condition
 * @return unknown
 */
function get_taobao_application_list($condition = null) {
    global $db;
    $sql = "SELECT * FROM taobao_shop_conf WHERE status = 'OK' and shop_type = 'taobao' ";
    if (!empty($condition)) {
        $sql .= " {$condition} ";
    }
    $application_list = $db->getAll($sql);
    $res = array();
    foreach ($application_list as $application) {
        $res[$application['application_key']] = $application;
    }
    return $res;
}
/**
 * 通过淘宝昵称获得淘宝应用码
 *
 * @param unknown_type $application_nick
 * @return unknown
 */
function get_taobao_application_key_by_nick($application_nick) {
    global $db;
    $sql = "SELECT application_key FROM taobao_shop_conf 
        WHERE nick = '{$application_nick}' LIMIT 1";
    return $db->getOne($sql);
}

/**
 * 添加订单映射关系(ecs_order_mapping)
 *
 * @param unknown_type $order_id
 * @param unknown_type $taobao_order_sn
 * @param unknown_type $application_nick
 */
function add_ecs_order_mapping($order_id, $taobao_order_sn, $application_nick = '乐其数码专营店',$platform='fenxiao',$memo='FromERP') {  //添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
    global $db;
    $application_key = get_taobao_application_key_by_nick($application_nick);
    $sql = "INSERT INTO ecs_order_mapping (erp_order_id, outer_order_sn, 
        status, created_time, shipping_status, application_key, platform ,memo) VALUES 
        ('{$order_id}', '{$taobao_order_sn}', 'OK', NOW(), '',
            '{$application_key}', '{$platform}','{$memo}') ";
    $db->query($sql);
}



