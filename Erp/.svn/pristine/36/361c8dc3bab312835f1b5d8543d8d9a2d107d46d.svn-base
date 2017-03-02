<?php

define('IN_ECS', true);
require('includes/init.php');
require('function.php');
admin_priv('cw_invoice_main', 'cg_no_shipping_invoice'); // 权限名为 发票相关 

$order_sn = 
    isset($_GET['order_sn']) ? $_GET['order_sn'] : null ;
$print_no_begin =
    isset($_GET['print_no_begin']) ? $_GET['print_no_begin'] : 1 ;
    
if ($order_sn) {
	$order_sn = array_filter(array_map('trim', explode(',', $order_sn)), 'strlen');
} else {
	print('<script type="text/javascript">No Order Sn !</script>');
	exit;
}

// 查询订单列表
$sql = "
    SELECT  order_id, order_sn, consignee, shipping_time, shipping_fee, inv_payee
    FROM {$ecs->table('order_info')} WHERE order_sn " . db_create_in($order_sn);
$links = array(
    // 订单明细
    array(
        'sql' => "
            SELECT 
                og.order_id, og.goods_name, og.goods_price, og.goods_number,
                og.goods_price * og.goods_number AS goods_amount,
		        CASE
		            WHEN (g.cat_id IN (1512, 1508) OR g.top_cat_id IN (1)) then '台'
		            WHEN (g.cat_id IN (1509, 1862) OR g.top_cat_id IN (597)) then '个'
		            WHEN (g.cat_id IN (1517)) then '本'
		        ELSE '个'
		        END AS uom 
            FROM 
                {$ecs->table('order_goods')} AS og
                LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id
            WHERE :in
        ",
        'source_key' => 'order_id',
        'target_key' => 'order_id',
        'mapping_name' => 'goods_list',
        'type' => 'HAS_MANY'    
    ),
    // 订单属性（出库单打印时间）
    array(
        'sql' => "
            SELECT * FROM order_attribute 
            WHERE :in AND attr_name = 'SHIPPING_INVOICE_PRINT_TIME'
        ",
        'source_key' => 'order_id',
        'target_key' => 'order_id',
        'mapping_name' => 'print_time',
        'type' => 'HAS_ONE'
    ),
    // 订单属性（订单积分）
    array(
        'sql' => "
            SELECT * FROM order_attribute
            WHERE :in AND attr_name = 'TAOBAO_POINT_FEE'
        ",
        'source_key' => 'order_id',
        'target_key' => 'order_id',
        'mapping_name' => 'point_fee',
        'type' => 'HAS_ONE'
    )
);
$order_list = $db->findAll($sql, $links);

if ($order_list) {
    // 计算每个订单要打几张纸，每张纸只能打10个商品记录
    foreach ($order_list as & $order) {
        // 积分 
        $order['point_fee'] =
            isset($order['point_fee']['attr_value'])
            ? round($order['point_fee']['attr_value']/100, 2)
            : 0 ;

        // 需要将运费与积分金额的差额平摊到商品单价上
        if ($order['shipping_fee'] > 0 || $order['point_fee'] > 0) {
            $diff = $order['shipping_fee'] - $order['point_fee'];
            $diff = round($diff, 2);

            // 计算价格不为0的商品的数量
            $count = 0;  // int
            foreach ($order['goods_list'] as $goods) {
                if ($goods['goods_price'] > 0) {
                    $count += $goods['goods_number'];
                }
            }
            
            // 将差价平摊到商品上
            if ($count > 1 && abs($diff) >= $count) {
                $int = intval($diff);  // int
                while (!is_int($avg = $int/$count)) {  // 保证平摊数是int
                    $int++;
                }
                $first = ($int != $diff);
                foreach ($order['goods_list'] as $key => $goods) {
                    if ($goods['goods_price'] > 0) {
                        $order['goods_list'][$key]['goods_price'] += $avg;
                        $order['goods_list'][$key]['goods_amount'] += $avg * $goods['goods_number'];
                        
                        if ($first) {
                            $first = false;
                            $order['goods_list'][$key]['goods_amount'] += $diff - $int;
                            $order['goods_list'][$key]['goods_price'] = round($order['goods_list'][$key]['goods_amount']/$goods['goods_number'], 2);
                        }
                    }
               }
            }
            // 将差价直接加在第一项商品上
            else {
                foreach ($order['goods_list'] as $key => $goods) {
                    if ($goods['goods_price'] > 0) {
                        $order['goods_list'][$key]['goods_amount'] += $diff;
                        $order['goods_list'][$key]['goods_price'] = round($order['goods_list'][$key]['goods_amount']/$goods['goods_number'], 2);
                        break;
                    }
                }
            }
        }

        // 打印时间
        $order['print_time'] = 
            isset($order['print_time']['attr_value'])
            ? $order['print_time']['attr_value']
            : ($order['shipping_time'] > 0 ? date('Y-m-d', $order['shipping_time']) : '' ) ;

        // 用空的商品记录来填充打印记录行
    	$order['_size']  = 10;
    	$order['_page']  = ceil(@count($order['goods_list'])/$order['_size']);
    	$pad_size = $order['_page'] * $order['_size'];
    	$order['goods_list'] = array_pad($order['goods_list'], $pad_size, array());
        $print_no_begin += $order['_page'];
    }

	// 更新打印的起始单号 
    $sql = "UPDATE ecs_shop_config set value = '$print_no_begin' WHERE code = 'no_shipping_invoice_begin_no' ";
    $db->query($sql);
    if (!$db->affected_rows()) {
        $sql = "INSERT INTO ecs_shop_config (`code`, `value`) VALUES ('no_shipping_invoice_begin_no', '{$print_no_begin}')";
        $db->query($sql, 'SILENT');		
    }
	
    $smarty->assign('order_list', $order_list);
    $smarty->display('oukooext/no_shipping_invoice_print.htm');
} 
else {
    die('<script type="text/javascript">alert("find no order");</script>');	
}
