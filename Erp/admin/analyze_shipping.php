<?php
/**
 * 发货统计
 */
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');
admin_priv('tj_analyze_shipping');
$start = $_REQUEST['start'] ? $_REQUEST['start'] : date("Y-m-d");
$end = $_REQUEST['end'] ? $_REQUEST['end'] : date('Y-m-d', strtotime('+1 day'));
// 仓库列表
$facility_list = facility_list();
$facility_id = $_REQUEST['facility_id'];
if($facility_id){
    $facility_condition = " AND o.facility_id = "."{$facility_id}";
}else{
    $facility_condition = null;
}
// 配货
$sql = "
    SELECT
        COUNT(DISTINCT o.order_id) 
    FROM 
        {$ecs->table('order_action')} AS a
        LEFT JOIN {$ecs->table('order_info')} o ON o.order_id = a.order_id 
    WHERE
        a.shipping_status = '9' AND ". party_sql('o.party_id') ." AND
        a.action_time >= '{$start}' AND a.action_time < '{$end}'".$facility_condition
;
$count_dp = $slave_db->getOne($sql);

// 出库
$sql = "
    SELECT 
        COUNT(DISTINCT o.order_id) 
    FROM
        {$ecs->table('order_action')} AS a
        LEFT JOIN {$ecs->table('order_info')} o ON o.order_id = a.order_id
    WHERE 
        a.shipping_status = '8' AND ". party_sql('o.party_id') ." AND 
        a.action_time >= '{$start}' AND a.action_time < '{$end}'".$facility_condition
;
$count_dc = $slave_db->getOne($sql);

// 发货
$sql = "
    SELECT 
        COUNT(DISTINCT o.order_id) 
    FROM
        {$ecs->table('order_action')} AS a
        LEFT JOIN {$ecs->table('order_info')} AS o ON o.order_id = a.order_id 
    WHERE
        a.shipping_status = '1' AND ". party_sql('o.party_id') ." AND
        a.action_time >= '{$start}' AND a.action_time < '{$end}'".$facility_condition
;
$count_shipping = $slave_db->getOne($sql);


    
$smarty->assign('count_dp', $count_dp);
$smarty->assign('count_dc', $count_dc);
$smarty->assign('count_shipping', $count_shipping);
$smarty->assign('facility_id', $facility_id);
$smarty->assign('facility_list', $facility_list);
if($facility_id){
    $facility_condition = " AND info.facility_id = "."{$facility_id}";
}else{
    $facility_condition = null;   
}
# 添加 party_sql 限制 20090915 yxiang@oukoo.com 
$sql = "
    SELECT 
        COUNT(DISTINCT info.order_id) AS c, 
        IF( (s.support_cod =1 AND s.support_no_cod = 1), 
            IF(info.province != 10, 44, 51), 
            info.shipping_id
        ) AS n_shipping_id
    FROM 
        {$ecs->table('order_info')} info 
        INNER JOIN {$ecs->table('shipping')} s ON info.shipping_id = s.shipping_id
    WHERE 
        EXISTS ( 
            SELECT 1 FROM {$ecs->table('order_action')} oa WHERE oa.order_id = info.order_id AND oa.shipping_status = 1 AND action_time >= '{$start}' AND action_time < '{$end}' 
        ) AND ". party_sql('info.party_id') . $facility_condition."
    GROUP BY n_shipping_id 
";
//province != 10

$temp_all = $slave_db->getAll($sql);
$carrier = getCarriers();
$carrier_count = array();
foreach ($temp_all as $temp) {
    //$carrier_name = $temp['carrier_id'] > 0 ? $carrier[$temp['carrier_id']]['name'] : '自提';
    $sql = "select shipping_name, support_cod from {$ecs->table('shipping')} where shipping_id = '{$temp['n_shipping_id']}' ";
    $shipping = $slave_db->getRow($sql);
    $shipping_name = $shipping['shipping_name'].($shipping['support_cod'] ? "cod" :"");
    //$shipping_name = $temp['shipping_name'].($temp['support_cod'] ? "cod" :"");
    $carrier_count[$shipping_name] = $temp['c'];
}
$smarty->assign('carrier_count', $carrier_count);
if($facility_id){
    $facility_condition = " AND e.facility_id =".$facility_id;
}else{
    $facility_condition = null;
}
$sql = "SELECT
            eog.goods_name, count(iid.QUANTITY_ON_HAND_DIFF) as c 
        from ecshop.ecs_order_info eoi
            LEFT JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
            LEFT JOIN romeo.inventory_item_detail iid on iid.ORDER_GOODS_ID = convert(eog.rec_id using utf8)
        where iid.QUANTITY_ON_HAND_DIFF < 0 AND ".party_sql('eoi.party_id')." AND 
        eoi.shipping_time >=  UNIX_TIMESTAMP('{$start}')
        AND eoi.shipping_time < UNIX_TIMESTAMP('{$end}') 
        {$facility_condition}
        GROUP BY eog.goods_id, eog.style_id";
$temp_all = $slave_db->getAll($sql);
$goods_name_count = array();
foreach ($temp_all as $temp) {
  $goods_name = $temp['goods_name'];
  $goods_name_count[$goods_name] = $temp['c'];
}
$smarty->assign('goods_name_count', $goods_name_count);
$smarty->assign('start', $start);
$smarty->assign('end', $end);

$smarty->display('oukooext/analyze_shipping.htm');
