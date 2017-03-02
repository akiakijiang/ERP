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

// 仓库
$facility_id = 
    isset($_REQUEST['facility_id']) && $_REQUEST['facility_id'] > 0
    ? $_REQUEST['facility_id'] 
    : NULL ;
    
// 配送方式
$shipping_id = 
    isset($_REQUEST['shipping_id']) && $_REQUEST['shipping_id'] > 0
    ? $_REQUEST['shipping_id'] 
    : NULL ;
    
// 配送区域
$province_id = 
    isset($_REQUEST['province_id']) && $_REQUEST['province_id'] > 0
    ? $_REQUEST['province_id']
    : NULL ;

// 分类
$taxology = 
    isset($_REQUEST['taxology']) && $_REQUEST['taxology'] > 0
    ? $_REQUEST['taxology']
    : NULL ;

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
    'facility_id' => $facility_id, 'shipping_id' => $shipping_id, 'province_id' => $province_id,
    'taxology' => $taxology, 'start' => $start, 'end' => $end
);

$conditions = _get_conditions($filter);

// 查询在该区间内的销售订单 
$sql = "
    SELECT
        o.order_id, o.order_sn, o.order_time, o.facility_id, o.order_status, o.pay_status, o.shipping_status,
        o.shipping_time, o.shipping_id, o.order_type_id, o.province, p.pay_code
    FROM 
        {$ecs->table('order_info')} AS o
        LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id
    WHERE 
        o.order_status = 1 AND o.order_type_id IN ('SALE', 'RMA_EXCHANGE', 'SHIP_ONLY') AND
        o.order_time BETWEEN '{$filter['start']}' AND DATE_ADD('{$filter['end']}', INTERVAL 1 DAY) AND
        ((o.order_type_id = 'SALE' AND (p.pay_code = 'cod' OR o.pay_status = 2)) OR o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY'))
        {$conditions} 
    ORDER BY o.order_id ASC 
";
$ref_order_fields = $ref_order_rowset = array();
$order_list = $db->getAllRefby($sql, array('order_id'), $ref_order_fields, $ref_order_rowset, false);


if (!empty($order_list)) {
    // 查询订单的action
    $sql = "
        SELECT 
            order_id, order_status, shipping_status, pay_status, 
            warehouse_status, created_by_user_login, created_stamp 
        FROM order_mixed_status_history
        WHERE created_by_user_class = 'worker' AND order_id ". db_create_in($ref_order_fields['order_id']) ." 
        ORDER BY order_mixed_status_history_id ASC
    "; 
    $ref_history_fields = $ref_history_rowset = array();
    $db->getAllRefby($sql, array('order_id'), $ref_history_fields, $ref_history_rowset, false);
    
    if (in_array($filter['taxology'], array(1, 2))) {
        // 库存
        $storage_list = getStorage('INV_STTS_AVAILABLE');  
        
        // 查询出订单商品，用于判断订单是否缺货
        $sql = "
            SELECT order_id, goods_name, goods_id, style_id, goods_number 
            FROM {$ecs->table('order_goods')} 
            WHERE order_id " . db_create_in($ref_order_fields['order_id']);
        $ref_goods_fields = $ref_goods_rowset = array();
        $goods_list = $db->getAllRefby($sql, array('order_id'), $ref_goods_fields, $ref_goods_rowset, false);
    }
    
    // 配送方式
    #$shipping_list = Helper_Array::toHashmap((array)shipping_list(), 'shipping_id', 'shipping_name');
    
    // 地区列表
    #$region_list = Helper_Array::toHashmap((array)get_regions(1, $GLOBALS['_CFG']['shop_country']), 'region_id', 'region_name');
    
    foreach ($order_list as $key => & $order) {
        // 缺货 / 待配货
        if (in_array($filter['taxology'], array(1, 2))) {
            // 订单的商品
            if (isset($ref_goods_rowset['order_id'][$order['order_id']])) {
                $order['goods_list'] = & $ref_goods_rowset['order_id'][$order['order_id']] ;
            } else {
                $order['goods_list'] = array();
            }
            
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
            
            if ( ($filter['taxology'] == 1 && !$order['shortage'] ) || 
                 ($filter['taxology'] == 2 && $order['shortage']  ) ) {
                unset($order_list[$key]);
                continue;
            }
        }
        
        // 根据action计算订单的操作项时间
        if (isset($ref_history_rowset['order_id'][$order['order_id']])) {
            $order['status_history'] = & $ref_history_rowset['order_id'][$order['order_id']];
            $continue = 0;
            foreach ($order['status_history'] as $history) {
                // 订单确认
                if ($history['order_status'] == 'confirmed' && !$order['order_confirm_time']) {
                    $order['order_confirm_time'] = $history['created_stamp'];
                    $order['order_confirm_user'] = $history['created_by_user_login'];
                }
                
                // 配货
                if ($history['warehouse_status'] == 'picked' && !$order['order_pick_time']) {
                    $order['order_pick_time'] = $history['created_stamp'];
                    $order['order_pick_user'] = $history['created_by_user_login'];
                }
                
                // 出库
                if ($history['warehouse_status'] == 'delivered' && !$order['order_delivery_time']) {
                    $order['order_delivery_time'] = $history['created_stamp'];
                    $order['order_delivery_user'] = $history['created_by_user_login'];
                }
                
                // 发货
                if ($history['shipping_status'] == 'shipped' && !$order['order_shipping_time']) {
                    $order['order_shipping_time'] = $history['created_stamp'];
                    $order['order_shipping_user'] = $history['created_by_user_login'];
                }
            }
        }
    }
}



$smarty->assign('order_list', $order_list);
$smarty->display('oukooext/analyze_warehouse_detail.htm');


/**
 * 构造查询条件
 * 
 * @param $filter
 * @return string
 */
function _get_conditions(& $filter)
{
    $cond = '';
    if ($filter['facility_id']) {
        $cond .= " AND o.facility_id = '{$filter['facility_id']}' ";
    }
    
    if ($filter['shipping_id']) {
        $cond .= " AND o.shipping_id = '{$filter['shipping_id']}' ";
    }
    
    if ($filter['province_id']) {
        $cond .= " AND o.province = '{$filter['province_id']}' ";
    }
    
    if ($filter['taxology']) {
        if (in_array($filter['taxology'], array(1 ,2))) {
            $cond .= " AND o.shipping_status IN (0, 10) ";
        }
        else if ($filter['taxology'] == 3) {
            $cond .= " AND o.shipping_status = 9 ";
        }
        else if ($filter['taxology'] == 4) {
            $cond .= " AND o.shipping_status = 8 ";
        }
        else if ($filter['taxology'] == 5) {
            $cond .= " AND o.shipping_status IN (1, 2, 3)";            
        }
    }
    
    return $cond;
}

