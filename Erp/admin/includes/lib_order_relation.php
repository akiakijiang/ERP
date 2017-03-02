<?php
/**
 * @author zwsun $
 * @copyright oukoo.com 2009-7-23 21:22:28 $
 * 
 * 订单关系相关函数文件
 * 
 */


/**
 * 获得订单相关的-t -h 订单
 *
 * @param int $order_id 订单号
 * @param bool $cal_b2c 商品金额只计算b2c商品
 * @return array
 */
function get_order_related_orders($order_id, $cal_b2c = false) {
    global  $db, $ecs;
    static $orders_array = array();
    
    if (!isset($orders_array[$order_id][$cal_b2c])) {
        if ($cal_b2c) { // 计算商品金额时只计算b2c商品的金额
            $sql = "SELECT o.order_id, o.order_sn, o.order_type_id, 
                           SUM(IF(ii.inventory_item_acct_type_id = 'B2C',og.goods_price*iid.quantity_on_hand_diff, 0)) as goods_amount, 
                           o.shipping_fee, o.pack_fee
                    FROM order_relation orl
                    INNER JOIN ecshop.ecs_order_info o ON  o.order_id = orl.order_id
                    LEFT JOIN ecshop.ecs_order_goods og ON og.order_id = o.order_id
                    LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
					LEFT JOIN romeo.inventory_item ii ON ii.inventory_item_id = iid.inventory_item_id
                    WHERE orl.root_order_id = '$order_id'
                    GROUP BY o.order_id ";
        } else {
            $sql = "SELECT o.order_id, o.order_sn, o.order_type_id,
                           o.goods_amount, o.shipping_fee, o.bonus, o.pack_fee 
                    FROM order_relation orl
                    INNER JOIN {$ecs->table('order_info')} o ON  o.order_id = orl.order_id
                    WHERE orl.root_order_id = '$order_id' ";
        }
        $orders_array[$order_id][$cal_b2c] = $db->getAll($sql);
    }
    
    return $orders_array[$order_id][$cal_b2c];
}

/**
 * 获得订单相关-t -h订单的商品金额，返还的红包，运费，包装费
 *
 * @param int $order_id
 * @param bool $cal_b2c 商品金额只计算b2c商品
 * @return array array(goods_amount_returned => , bonus_returned => )
 */
function get_order_related_amount($order_id, $cal_b2c = false) {
    $goods_amount_returned  = 0 ;   // 退回的商品金额（全部，或者只计算b2c的）
    $bonus_returned         = 0;    // 退回的红包
    $shipping_fee_returned  = 0;    // 退回的运费
    $pack_fee_returned      = 0;    // 退回的包装费
    
    $orders = get_order_related_orders($order_id, $cal_b2c);
    if ($orders) {
        foreach ($orders as $order) {
            $goods_amount = $order['goods_amount'];
            $bonus = $order['bonus'];
            if ($order['order_type_id'] == 'SHIP_ONLY') { 
                continue;
            } elseif ($order['order_type_id'] == 'RMA_RETURN' 
                        || substr($order['order_sn'], -2) == '-t') {
                $goods_amount_returned  += (-1 * abs($goods_amount));
                $bonus_returned         += (-1 * abs($bonus));
                $shipping_fee_returned  += (-1 * abs($order['shipping_fee']));
                $pack_fee_returned      += (-1 * abs($order['pack_fee']));
            } elseif ($order['order_type_id'] == 'RMA_EXCHANGE' 
                        || substr($order['order_sn'], -2) == '-h') {
                $goods_amount_returned  += abs($goods_amount);
                $bonus_returned         += abs($bonus);
                $shipping_fee_returned  += (abs($order['shipping_fee']));
                $pack_fee_returned      += (abs($order['pack_fee']));
            }
        }
    }
    
    return  array('goods_amount_returned'   => $goods_amount_returned, 
                  'bonus_returned'          => $bonus_returned,                         
                  'shipping_fee_returned'   => $shipping_fee_returned,
                  'pack_fee_returned'       => $pack_fee_returned,
                 );
}

/**
 * 查询 -t -h订单的原始销售订单
 *
 * @param int $order_id -t 或者 -h订单的order_id
 * @param boolean $force 强制从数据库中读取
 * @return string
 */
function get_order_related_root_order($order_id, $force = false) {
    global  $db, $ecs;
    static $orders_array = array();
    
    $order_id = intval($order_id);
    
    if (!isset($orders_array[$order_id]) || $force) {
        $sql = "SELECT o.order_id, o.order_sn, o.order_type_id,  
                o.goods_amount, o.shipping_fee, o.bonus, o.pack_fee, o.order_amount, o.misc_fee 
                FROM order_relation orl
                INNER JOIN {$ecs->table('order_info')} o ON o.order_id = orl.root_order_id
                WHERE orl.order_id = '$order_id' LIMIT 1";
        $orders_array[$order_id] = $db->getRow($sql);
    }
    
    return $orders_array[$order_id];
}

