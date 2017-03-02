<?php

/**
 * 打印发货单，支持批量打印
 */

define('IN_ECS', true);
require('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_delivery');
include_once 'function.php';
require_once('includes/lib_order.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');

$order_id_str = $_REQUEST['order_ids'];
$order_ids = $_REQUEST['order_id'];
if (!$order_ids) {
    $order_ids = explode(',', $order_id_str);
    //过滤掉重复的order_id
    $order_ids = array_unique($order_ids);
}

// 测试使用
if ($_REQUEST['limit'] > 0) {
	$limit = "LIMIT {$_REQUEST['limit']}";
}


// 如果没有查询到，说明传入的订单号是有问题的
if (!is_array($order_ids) && $order_ids == 0) {
	die("非法输入");
} else if (!is_array($order_ids)) {
	$order_ids = array($order_ids);
}

$payments = getPayments();
$shippingTypes = getShippingTypes();
$orders = array();
foreach ($order_ids as $order_id) {
    $order_id = intval($order_id);
    if (!$order_id) continue;
    
    // 取得订单信息
	// $sql = "
	// 	SELECT * 
	// 	FROM {$ecs->table('order_info')} o 
	// 	LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
	// 	WHERE order_id = '{$order_id}' AND ". party_sql('o.party_id') ."
	// ";
    // 邪恶的大鲵 20151202
    $sql="SELECT o.*,s.tracking_number bill_no,s.shipment_id
        FROM ecshop.ecs_order_info o
        LEFT JOIN romeo.order_shipment os on os.order_id=convert(o.order_id using utf8)
        LEFT JOIN romeo.shipment s on os.shipment_id=s.shipment_id
        WHERE s.STATUS != 'SHIPMENT_CANCELLED'
        AND o.order_id = '{$order_id}' 
        AND ". party_sql('o.party_id') ."
        GROUP BY o.order_id
        LIMIT 1
    ";
	$order = $db->getRow($sql);
	
	// 取得订单的备注
	$sql = "
		SELECT * 
		FROM {$ecs->table('order_action')} 
		WHERE order_id = '{$order_id}' AND action_note != ''
	";
	$order['note'] = $db->getAll($sql);
	
	// 取得分销商信息
	$sql = "
		SELECT * FROM distributor WHERE distributor_id = '{$order['distributor_id']}'
	";
	$order['distributor'] = $db->getRow($sql, true);
		
	// 取得订单的商品
	$sql = "
        SELECT 
            og.rec_id, og.goods_id, og.style_id, og.goods_name, og.goods_number,ii.serial_number,
            og.customized, og.goods_price * og.goods_number as goods_amount, g.top_cat_id, g.cat_id
        FROM
            {$ecs->table('order_goods')} AS og 
            LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8)
            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
            LEFT JOIN {$ecs->table('goods')} g on og.goods_id = g.goods_id 
        WHERE
            og.order_id = '{$order_id}'
	";	
	$result = $GLOBALS['db']->getAllRefby($sql, array('rec_id'), $ref_field, $ref, false);

    foreach ($ref['rec_id'] as $group) {
        $g = reset($group);
        $k = $g['rec_id'];
        $order['goods_list'][$k] = $g;
    	
        if (getInventoryItemType($g['goods_id']) == 'SERIALIZED') {
            // 有串号控制取得商品串号
            $_serial_number = array();
            foreach ($group as $item) {
                $_serial_number[] = '('.$item['serial_number'].')';
            }
            $order['goods_list'][$k]['serialNumber'] = implode('<br />', $_serial_number);
            
            // 有电教品，则显示盖章处
            if ($g['cat_id'] == 1512 && !isset($stamp)) {
                $stamp = true;
            }
        } else {
            // 无串号控制取得配件编号
            $order['goods_list'][$k]['productCode'] = encode_goods_id($g['goods_id'], $g['style_id']);
    		
            // 定制图案	
            if ($g['cat_id'] == 1509) {	
                $attr = get_order_goods_attribute($g['rec_id']);
                if ($attr) {
                    $customize[] = $g['rec_id'];	
                }
            }
        }
    	
        // 是否有定制信息
        $order['goods_list'][$k]['goods_name'] .= get_customize_type($g['customized'], true);
    	
        // 记录该批次打印订单中各类商品的数量
        $goods_count[$g['goods_id']] += $g['goods_number'];
    }
	//获取改订单中的商品是否需要下载资料的信息
	$sql = "
				select * from ecshop.ecs_guest_info ig 
				left join ecshop.ecs_order_info oi on oi.taobao_order_sn = ig.taobao_order_sn
				where oi.order_id = '{$order_id}'
	";
	$res = $db->getRow($sql);
	if(!empty($res['download_info'])&&$res['download_info']!='无'){
		$order['remind'] = 1;
	}
	else{
		$order['remind'] = 0;
	}
	//重新获得支付方式和货运方式
	$order['shipping_name'] = $shippingTypes[$order['shipping_id']] ? $shippingTypes[$order['shipping_id']]['shipping_name'] : $order['shipping_name'];
	$order['pay_name'] = $payments[$order['pay_id']] ? $payments[$order['pay_id']]['pay_name'] : $order['pay_name'];
	$order['code_width'] = max(240 + (str_len($order['order_sn']) - 10) * 30, 150);
	
	$orders[] = $order;
	$order_shipping = $shippingTypes[$order['shipping_id']];

	// update order mixed status 
    // include_once('includes/lib_order_mixed_status.php');
    // update_order_mixed_status($order_id, array('pick_list_status' => 'printed'), 'worker');
    // 
    
    include_once('includes/lib_print_action.php');
    LibPrintAction::addPrintRecord('SHIPMENT',$order['shipment_id'],$order['order_sn']);
}

//按数量进行倒序排
arsort($goods_count);
$goods_ids = array_keys($goods_count);

//获得最多的两种商品
$top_goods_id1 = $goods_ids[0];
$top_goods_id2 = $goods_ids[1];

//对订单检查，记录是否存在最多的商品
foreach ($orders as $order_key => $order) {
    $orders[$order_key]['is_top1'] = 0;
    $orders[$order_key]['is_top2'] = 0;
    foreach ($orders[$order_key]['goods_list'] as $goods) {
        if (!$orders[$order_key]['is_top1'] && $goods['goods_id'] == $top_goods_id1) {
            $orders[$order_key]['is_top1'] = 1;
        }
        if (!$orders[$order_key]['is_top2'] && $goods['goods_id'] == $top_goods_id2) {
            $orders[$order_key]['is_top2'] = 1;
        }
        if ($orders[$order_key]['is_top1'] && $orders[$order_key]['is_top2']) {
            break;
        }
    }

    $orders[$order_key]['tel']=keitai_angouka($order['tel']);
    $orders[$order_key]['mobile']=keitai_angouka($order['mobile']);
}

//对订单进行排序，把最多的放在一起
uasort($orders, 'sortOrderByGoods');
$smarty->assign('orders', $orders);
$smarty->assign('customize', $customize);
$smarty->assign('stamp', $stamp);  // 图章
$smarty->display('distributor/distribution_print_delivery_order.htm');	



function sortOrderByGoods($order1, $order2) {
//    if($order1['order_sn'] == '1441271949') pp($order1['is_top1'], $order1['is_top2']);
    //先比较是否含最多商品
    if ($order1['is_top1'] > $order2['is_top1']) return -1;
    elseif ($order1['is_top1'] < $order2['is_top1']) return 1;
    //再比较是否含次多商品
    if ($order1['is_top2'] > $order2['is_top2']) return -1;
    elseif ($order1['is_top2'] < $order2['is_top2']) return 1;
    
    if ($order1['order_id'] > $order2['order_id']) return 1; 
    else return -1;
}

/**
携帯電話の最中の四位を隠す
**/
function keitai_angouka($bangou){
    if(strlen($bangou)==11)
        return substr($bangou, 0,3).'****'.substr($bangou,7);
    else
        return substr($bangou, 0,-4).'****';
}