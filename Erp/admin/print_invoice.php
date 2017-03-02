<?php

/**
 * 打印发货单
 */
define('IN_ECS', true);
require('includes/init.php');
include_once 'function.php';
require_once('includes/lib_order.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');

$order_id_str = $_REQUEST['order_ids'];
$order_ids = $_REQUEST['order_id'];
if (!$order_ids) {
    $order_ids = explode(',', $order_id_str);
    $order_ids = array_unique($order_ids);
}

// 测试使用
if ($_REQUEST['limit'] > 0) {
	$limit = "LIMIT {$_REQUEST['limit']}";
}

if (empty($order_ids)) {
	die("非法输入");
}

if (!is_array($order_ids) && $order_ids == 0) {
	die("非法输入");
} else if (!is_array($order_ids)) {
	$order_ids = array($order_ids);
}
$payments = getPayments();
$shippingTypes = getShippingTypes();
$orders = array();
//修改状态为配货中 ncchen 081211
/*$order_id_list = implode(",", $order_ids);
$sql = sprintf("UPDATE {$ecs->table('order_info')} SET shipping_status = '%d' WHERE order_id IN ($order_id_list) ", SS_PEIHUO);
$db->query($sql);*/

$distributor_ids = array();
foreach ($order_ids as $order_id) {
    $order_id = intval($order_id);
    if (!$order_id) continue;
	$sql = "SELECT * 
		FROM {$ecs->table('order_info')} o 
		-- LEFT JOIN {$ecs->table('carrier_bill')} cb ON cb.bill_id = o.carrier_bill_id
		WHERE order_id = '{$order_id}'
	";
	$order = $db->getRow($sql);
	// 生成屏蔽号码
	convert_mask_phone($order, 'add');
	
	// 取得备注
	$sql = "SELECT * FROM {$ecs->table('order_action')} 
	        WHERE order_id = '{$order_id}' AND note_type = 'SHIPPING' AND action_note != ''
	        ";
	$order['shipping_note'] = $db->getAll($sql);
	$distributor_ids[] = $order['distributor_id'];
	
	// 取得商品
	$sql = "SELECT og.*, og.goods_price * og.goods_number as goods_amount, g.top_cat_id 
	        FROM {$ecs->table('order_goods')} og 
	        INNER JOIN {$ecs->table('goods')} g on og.goods_id = g.goods_id 
	        WHERE order_id = '{$order_id}'";
	$order['goods_list'] = $db->getAll($sql);
	// 客服修改移动定制信息
	foreach ($order['goods_list'] as $key => $goods) {
	    if($goods['top_cat_id'] != 1) $order['goods_list'][$key]['productCode'] = encode_goods_id($goods['goods_id'], $goods['style_id']);
		$order['goods_list'][$key]['goods_name'] .= get_customize_type($goods['customized'], true);
		//记录该批次打印订单中各类商品的数量
		$goods_count[$goods['goods_id']] += $goods['goods_number'];
	}
	
	//重新获得支付方式和货运方式
	$order['shipping_name'] = $shippingTypes[$order['shipping_id']] ? $shippingTypes[$order['shipping_id']]['shipping_name'] : $order['shipping_name'];
	$order['pay_name'] = $payments[$order['pay_id']] ? $payments[$order['pay_id']]['pay_name'] : $order['pay_name'];
	$order['code_width'] = max(240 + (str_len($order['order_sn']) - 10) * 30, 150);
	$orders[] = $order;
	$order_shipping = $shippingTypes[$order['shipping_id']];
	if ($order_shipping['support_cod'] && $order_shipping['support_no_cod']) { //如果是自提点的，打印两次发货单
		$orders[] = $order;
	}
	// update order mixed status 
    // include_once('includes/lib_order_mixed_status.php');
    // update_order_mixed_status($order_id, array('pick_list_status' => 'printed'), 'worker');
}


//判断 是否有不同的分销商
$distributor_ids = array_unique($distributor_ids);
if (count($distributor_ids) > 1) {
    print "<script>alert('打印批次里面存在不同的分销商的订单')</script>";
    exit();
}
// 获得该批次订单的distributor_id
$distributor_id = $distributor_ids[0];

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
    $orders[$order_key]['facility_name'] = facility_mapping($order['facility_id']);;
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
    // 订单使用的发货单模板
    $orders[$order_key]['invoice_tpl']=print_invoice_search_template($order['party_id'], $order['facility_id']);
    
    // 仓库
    $orders[$order_key]['facility_name'] = facility_mapping($order['facility_id']);
    
    // 取得商品的存放库位，并将商品按库位排序
    if (is_array($orders[$order_key]['goods_list'])) {
    	$sort=array();
        foreach ($orders[$order_key]['goods_list'] as $goods_key=>$og) {
            $sort[$goods_key]='0';
            $facility_location_list=facility_location_list_by_product(getProductId($og['goods_id'],$og['style_id']),$order['facility_id']);
            $facility_location=reset($facility_location_list);
            if($facility_location!==false) {
                $orders[$order_key]['goods_list'][$goods_key]['location_seq_id']=$facility_location->locationSeqId;
                $sort[$goods_key]=$facility_location->locationSeqId;
            }
        }
        
        if (!empty($sort)) {
            array_multisort($sort, SORT_ASC, SORT_STRING, $orders[$order_key]['goods_list']);
        }
    }
}

//对订单进行排序，把最多的放在一起
uasort($orders, 'sortOrderByGoods');
$smarty->assign('orders', $orders);
$smarty->display('oukooext/print_invoice.htm');


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
 * 查询不同组织的发货单模板
 * 模板的定义应该为   组织ID#仓库ID_模板名,  如   4#74539_invoice.htm, 120_invoice.htm
 * 
 * @param int $party_id    订单的party_id
 * @param int $facility_id 订单的facility_id
 */
function print_invoice_search_template($party_id, $facility_id, $tpl = 'invoice.htm') {
    global $smarty;
    $dir = 'invoice/';
    
    // 可以容忍的party_id
    $PARTY_ID = array($party_id);
    foreach(array_keys(party_list(PARTY_ALL)) as $parent_party_id) {  // 取得订单PARTY的父PARTY
        if (party_check($parent_party_id, $party_id)) {
            array_push($PARTY_ID, $parent_party_id);
            break;
        }
    }
        
    // 可容忍的facility_id
    $FACILITY_ID = array($facility_id);

    // 配置搜索模板的级别
    $levels = array('PARTY_ID' => $PARTY_ID, 'FACILITY_ID' => $FACILITY_ID);
    for ($i = count($levels); $i > 0; $i--) {
        $level = array_slice($levels, 0, $i, TRUE);
        
        if (isset($level['FACILITY_ID'])) {
            foreach ($level['PARTY_ID'] as $pid) {
            foreach ($level['FACILITY_ID'] as $fid) {
                $htm = $pid .'#'. $fid . '_' . $tpl;
                if ($smarty->template_exists($dir. $htm)) {
                    $tpl = $htm;
                    break;
                }
            }}
        } 
        else {
            foreach ($level['PARTY_ID'] as $pid) {
                $htm = $pid . '_' . $tpl;
                if ($smarty->template_exists($dir. $htm)) {
                    $tpl = $htm;
                    break;
                }
            }
        }
    }
    
    return $dir.$tpl;
}
