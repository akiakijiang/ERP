<?php 
/**
 * 仓库统计 
 */
define('IN_ECS', true);

require_once('includes/init.php');
admin_priv('analyze_order');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');

//
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('查询', '导出'))
    ? $_REQUEST['act']
    : '查询' ;

// 期初时间
$start = 
    !empty($_REQUEST['start']) && strtotime($_REQUEST['start']) > 0
    ? $_REQUEST['start'] 
    : date('Y-m-d') ;
    
// 期末时间
$end =
    !empty($_REQUEST['end']) && strtotime($_REQUEST['end']) > 0
    ? $_REQUEST['end']
    : date('Y-m-d') ;
    
$filter = array(
    'start' => $start, 'end' => $end,
);

// 查询在该区间内的销售订单 
$sql = "
    SELECT
        o.order_id, o.party_id, o.order_sn, o.order_time, o.facility_id, o.order_status, o.pay_status, 
        o.shipping_status, o.shipping_time, o.shipping_id, o.order_type_id, o.province, p.pay_code,
        CONCAT_WS('_', o.facility_id, o.province) AS facility_province_id,
        CONCAT_WS('_', o.facility_id, o.shipping_id) AS facility_shipping_id,
        CONCAT_WS('_', o.facility_id, o.province, o.shipping_id) AS facility_province_shipping_id
    FROM 
        {$ecs->table('order_info')} AS o
        LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id
    WHERE 
        o.order_status = 1 AND o.order_type_id IN ('SALE', 'RMA_EXCHANGE', 'SHIP_ONLY') AND
        o.order_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND
        ((o.order_type_id = 'SALE' AND (p.pay_code = 'cod' OR o.pay_status = 2)) OR o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY'))
    ORDER BY o.facility_id, o.shipping_id ASC 
";
$ref_order_fields = $ref_order_rowset = array();
$order_list = $db->getAllRefby(
    $sql, array('order_id', 'facility_id', 'facility_province_id', 'facility_shipping_id', 'facility_province_shipping_id'), 
    $ref_order_fields, $ref_order_rowset, false
);

if (!empty($order_list)) {
    // 库存
    $storage_list = getStorage('INV_STTS_AVAILABLE');  
    
    // 查询出订单商品，用于判断订单是否缺货
    $sql = "
        SELECT order_id, goods_name, goods_id, style_id, goods_number FROM {$ecs->table('order_goods')} 
        WHERE order_id " . db_create_in($ref_order_fields['order_id']);
    $ref_goods_fields = $ref_goods_rowset = array();
    $goods_list = $db->getAllRefby($sql, array('order_id'), $ref_goods_fields, $ref_goods_rowset, false);
    unset($ref_order_rowset['order_id']);
    
    // 配送方式
    $shipping_list = array();
    $sql = "
        SELECT party_id, shipping_id, shipping_name, support_cod, support_no_cod
        FROM {$GLOBALS['ecs']->table('shipping')} WHERE enabled = 1 
    ";
    $result = $db->query($sql);
    while ($row = $db->fetchRow($result)) {
        if ($row['support_cod'] && !$row['support_no_cod']) {
            $row['shipping_name'] .= "(货到付款)";
        }
        $shipping_list[$row['party_id']][$row['shipping_id']] = $row['shipping_name'];
    }
    
    // 地区列表
    $region_list = Helper_Array::toHashmap((array)get_regions(1, $GLOBALS['_CFG']['shop_country']), 'region_id', 'region_name');
    
    foreach ($order_list as & $order) {
        // 订单的商品
        if (isset($ref_goods_rowset['order_id'][$order['order_id']])) {
            $order['goods_list'] = & $ref_goods_rowset['order_id'][$order['order_id']] ;
        } else {
            $order['goods_list'] = array();
        }
        
        // 待配货
        if (in_array($order['shipping_status'], array(0, 10))) {
            // 检查是否缺货
            $order['shortage'] = false;
            foreach ($order['goods_list'] as & $og) {
                $og['storage_number'] = isset($storage_list[$og['goods_id'].'_'.$og['style_id']]['qohTotal'])
                    ? $storage_list[$og['goods_id'].'_'.$og['style_id']]['qohTotal'] : 0 ;
    
                // 缺货
                if ($og['storage_number'] <= 0 || $og['storage_number'] < $og['goods_number']) {
                    $order['shortage'] = true;
                    break;
                }
            }
            
            if ($order['shortage']) {
                // 缺货的订单
                $order['taxology'] = '1';
            } else {
                // 待配货
                $order['taxology'] = '2';
            }
        }
        // 已配货
        else if ($order['shipping_status'] == 9) {
            $order['taxology'] = '3';
        }
        // 已出库待发货
        else if ($order['shipping_status'] == 8) {
            $order['taxology'] = '4';
        }
        // 已发货/收货确认
        else if (in_array($order['shipping_status'], array(1, 2, 3))) {
            $order['taxology'] = '5';
        }
    }


    // 分组
    $taxology = array();
    foreach ($ref_order_rowset as $group => $_array1) {
        $taxology[$group] = array();
        foreach ($ref_order_rowset[$group] as $key => $_array2) {
            foreach ($ref_order_rowset[$group][$key] as & $order) {
                $taxology[$group][$key]['taxo'][$order['taxology']]['order_list'][] = $order;
            }
                      
            if (isset($taxology[$group][$key])) {
                $first = true;
                foreach ($taxology[$group][$key]['taxo'] as $taxo => $_array3) {
                    $o = reset($_array3['order_list']);
                    if ($o) { 
                        if ($first) {
                            $taxology[$group][$key]['facility_id'] = $o['facility_id'];
                            if (in_array($group, array('facility_province_id', 'facility_province_shipping_id'))) {
                                $taxology[$group][$key]['province_id'] = $o['province'];
                            }
                            if (in_array($group, array('facility_shipping_id', 'facility_province_shipping_id'))) {
                                $taxology[$group][$key]['shipping_id'] = $o['shipping_id'];
                            }
                            $taxology[$group][$key]['facility_name'] = facility_mapping($o['facility_id']);      
                            $taxology[$group][$key]['shipping_name'] = isset($shipping_list[$o['party_id']][$o['shipping_id']])
                                ? $shipping_list[$o['party_id']][$o['shipping_id']] : $shipping_list[PARTY_ALL][$o['shipping_id']] ;      
                            $taxology[$group][$key]['province_name'] = $region_list[$o['province']];      
                        }
                        
                        $taxology[$group][$key]['taxo'][$taxo]['order_count'] = count($_array3['order_list']); 
                    }
                    $first = false;
                }
            }
        }
    }
}

$smarty->assign('taxology', $taxology);
$smarty->assign('filter', $filter);
$smarty->display('oukooext/analyze_warehouse.htm');

