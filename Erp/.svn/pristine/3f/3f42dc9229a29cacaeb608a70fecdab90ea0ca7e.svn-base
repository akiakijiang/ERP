<?php 

/**
 * 分销打印拣货单 
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once('includes/lib_order.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

// 传递了一个订单ID
$order_id = 
    isset($_REQUEST['order_id']) && $_REQUEST['order_id'] > 0
    ? $_REQUEST['order_id']
    : false ;

// 传递了一个订单ID序列
$serial = 
    isset($_REQUEST['serial']) && $_REQUEST['serial'] > 0
    ? $_REQUEST['serial']
    : false ;

// 如果不是数组，处理成数组
if ($serial) {
    $order_ids = Helper_Array::normalize($serial);    
}
if ($order_id) {
    $order_ids[] = $order_id;
}

if (!empty($order_ids)) {
    $orders = array();
    $storage_list = getStorage();  // 新库存
    foreach ($order_ids as $order_id) {
        // 取得订单信息
        $order = order_info($order_id);
        if (!$order) { continue; }
        $order['code_width'] = max(240 + (str_len($order['order_sn']) - 10) * 30, 150);  // 订单的条形码长度
        $order['goods_list'] = distribution_get_order_goods($order['order_id']);  // 订单的商品
        $order['include_customize'] = false;  // 该订单是否包含定制手机
        foreach ($order['goods_list'] as $goods) {
            if ($goods['customize']) {
                $order['include_customize'] = true;
            }
            
            // 如果订单中包含库存不足的商品，则该订单不打印拣货单
            $idx = $goods['goods_id'] . '_' . $goods['style_id'];
            if (!isset($storage_list[$idx]['qohTotal']) || $storage_list[$idx]['qohTotal'] < $goods['goods_number']) {
                $exclude = true;
                break;
            }
        }
        if ($exclude !== true) { $orders[] = $order; }
    }
    
    $smarty->assign('orders', $orders);
    $smarty->display('distributor/distribution_pickticket.htm');
} else {
    die('错误的订单号');
}

?>