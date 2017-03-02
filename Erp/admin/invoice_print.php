<?php

/**
 * 发货单打印
 * 
 * @author yxiang@leqee.com
 */

define('IN_ECS', true);
require('includes/init.php');
include_once 'function.php';
require_once('includes/lib_order.php');

// 发货单号
$shipment_id = isset($_REQUEST['shipment_id']) && trim($_REQUEST['shipment_id']) ? $_REQUEST['shipment_id'] : false;
// 当前页的url,构造url用
$url = 'shipment_pick.php';
if ($shipment_id) {
    // 如果传递了发货单号则查询相关信息
    $handle = soap_get_client('ShipmentService');
    $response = $handle->getShipment(array('shipmentId' => $shipment_id));
    $shipment = is_object($response->return) ? $response->return : null;
    if (!$shipment){
        header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment->shipmentId}）不存在"));
        exit;
    }
    
    // 取得发货单的主订单信息
    // 取得发货单的所有订单信息
    // 如果是没有合并发货的订单，查找其发货单信息
    $order = null;
    $order_list = array();
    $shipment_list = array($shipment);
    
    // 取得发货单的所有订单信息
    $response2 = $handle->getOrderShipmentByShipmentId(array('shipmentId' => $shipment->shipmentId));
    // 合并发货的
    if (is_array($response2->return->OrderShipment)){
        $i = 0;
        foreach($response2->return->OrderShipment as $orderShipment) {
            $order_list[$i] = get_core_order_info('', $orderShipment->orderId);
            if ($shipment->primaryOrderId == $orderShipment->orderId){
                $order = $order_list[$i];
            }
            $i++;
        }
    }
    // 非合并发货
    // 非合并发货的是可以分开发货的，查找其的发货单
    elseif (is_object($response2->return->OrderShipment)){
        $order = get_core_order_info('', $shipment->primaryOrderId);
        $order_list[] = $order;
    
        // 取得这个订单的发货单
        $response3 = $handle->getShipmentByOrderId(array('orderId' => $order['order_id']));
        // 该订单是分开发货的
        if (is_array($response3->return->Shipment)) {
            foreach($response3->return->Shipment as $_shipment){
                if ($_shipment->primaryOrderId != $shipment->primaryOrderId){
                    $shipment_list[] = $_shipment;
                }
            }
        }
    }
    else{
        header("Location: ".add_param_in_url($url, 'message', "该发货单（shipmentId:{$shipment->shipmentId}）异常，找不到对应的主订单"));
        exit;
    }
    
    foreach ($order_list as $k => $_order) {
        $order_list[$k] = getOrderInfo($_order['order_id']);
        if ($order['order_id'] == $_order['order_id']) {
            $order = $order_list[$k];
        }
    }
}

// $order_id = intval($_REQUEST['order_id']);
// $order_info = getOrderInfo($order_id);
$order_attributes = get_order_attribute_list($order['order_id'], null);
$order['order_attributes'] = $order_attributes;

require_once ROOT_PATH . 'admin/config_vars.php';
$sql = "SELECT hscode_id, hscode, description as name FROM ecshop.hscode";
$pinming_array_tmp = $db->getAll($sql);
$pinming_array = array();
foreach ($pinming_array_tmp as $k => $v) {
    $pinming_array[$v['hscode_id']] = $v;
}
$smarty->assign('pinming_array', $pinming_array);


if (is_jjshouse($order['party_id'])) {
    $Quantity = trim($_REQUEST['Quantity']);
    $UnitPrice = trim($_REQUEST['UnitPrice']);
    $carrier_code = trim($_REQUEST['carrier_code']);
    $hscode_id = trim($_REQUEST['hscode_id']);
    $compress_rate = 1*trim($_REQUEST['compress_rate']) / 100;
    if (!array_key_exists($hscode_id, $pinming_array)) {
    	echo '<script>alert("请先选择品名。当前品名ID是“' . $hscode_id . '”");</script>';
    	die();
    	$hscode_id = 1;
    }
    $pinming = $pinming_array[$hscode_id];
    $smarty->assign("Quantity", $Quantity);
    $smarty->assign("UnitPrice", $UnitPrice);
    //$smarty->assign("order_info", $order_info);
    $smarty->assign("carrier_code", $carrier_code);
    $smarty->assign("pinming", $pinming);
    $smarty->assign("compress_rate", $compress_rate);
    
    
    // {{{
    $order['goods_amount_usd'] = 0;
    $order['goods_amount_usd_compress'] = 0;
    
    $goods_list = array();
    $order['qty'] = 0;
    
    // 德国和爱尔兰
    #if (in_array($order_list[0]['country'], array(4017, 4054))) {
    #	$_admin_compress_max = 350;
    #}
    
    foreach ($order_list as $k => $order_info) {
        $order_info['goods_amount_usd'] = 0;
        $order_info['goods_amount_usd_compress'] = 0;
        if ($order_info['goods_list']) {
            foreach ($order_info['goods_list'] as $k => $v) {
                $sql = "SELECT value FROM ecshop.order_goods_attribute where order_goods_id = '{$v['rec_id']}' and name = 'shop_price' ";
                $v['shop_price_usd'] = $v['shop_price_usd_original'] = $db->getOne($sql);
                if (!($v['shop_price_usd'] > 0)) {
                    // 如果价格为 0，设置为 1 USD
                    $v['shop_price_usd'] = 1 / $compress_rate;
                }
                //$v['shop_price_usd'] = number_format($v['shop_price_usd'], 2, '.', '');
                $v['goods_total_usd'] = number_format($v['shop_price_usd'] * $v['goods_number'], 2, '.', '');
                ##$v['goods_total_usd'] = number_format($v['shop_price_usd'] * 1, 2, '.', '');
                $order_info['goods_amount_usd'] += $v['goods_total_usd'];
    
                $v['shop_price_compress'] = $v['shop_price_usd'] * $compress_rate;
                $v['shop_price_compress'] = number_format($v['shop_price_compress'], 2, '.', '');
                $v['goods_total_compress'] = number_format($v['shop_price_compress'] * $v['goods_number'], 2, '.', '');
                ###$v['goods_total_compress'] = number_format($v['shop_price_compress'] * 1, 2, '.', '');
                $order_info['goods_amount_usd_compress']+= $v['goods_total_compress'];

                $v['goods_name_x'] = preg_replace('/\(.*|Size=.*|Bust=.*|Waist=.*|Hips=.*|Hollow to Floor=.*|Color=.*|Sash Color=.*/is', '', $v['goods_name']);
                
                $order['qty'] += $v['goods_number'];
                
                $order_info['order_goods'][$k] = $v;
                $goods_list[] = $v;
            }
            
            #if ($order_info['goods_amount_usd'] > $_admin_compress_max) {
            #	// 大于 600 美金需要拆单
            #	die('大于 600 美金需要拆单：' . $order_info['goods_amount_usd']);
            #}
        }
        $order['goods_amount_usd'] += $order_info['goods_amount_usd'];
        $order['goods_amount_usd_compress'] += $order_info['goods_amount_usd_compress'];
    }
    
    //$order['shipping_fee_x'] = number_format($order['shipping_fee'] * $compress_rate, 2, '.', '');
    $shipping_fee = $order['shipping_fee'];
    if (isset($order['order_attributes']['shipping_fee'][0]['attr_value'])) {
    	$shipping_fee = $order['order_attributes']['shipping_fee'][0]['attr_value'];
    }
    $order['shipping_fee_x'] = number_format($shipping_fee * $compress_rate, 2, '.', '');
    
    $order['goods_amount_usd'] = number_format($order['goods_amount_usd'], 2, '.', '');
    $order['goods_amount_usd_compress'] = number_format($order['goods_amount_usd_compress'], 2, '.', '');
    #if ($order['goods_amount_usd_compress'] > $_admin_compress_max) {
    #    $order['goods_amount_usd_compress'] = '0.00';
    #}
    //$order['admin_compress_rate'] = number_format($_admin_compress_rate * 100, 0, '.', '');
    //$order_attributes = get_order_attribute_list($order['order_id'], null);
    //$order['order_attributes'] = $order_attributes;
    #$smarty->assign('admin_compress_max', $_admin_compress_max);
    $smarty->assign("goods_list", $goods_list);
    $smarty->assign("order_info", $order);
    // }}}
    
    if ($_SESSION['party_id'] == 65554 || $_SESSION['party_id'] == 65570) {
        // amormoda
        $smarty->display("shipment/invoice_print_amormoda.htm");
    } elseif ($_SESSION['party_id'] == 65560) {
        // faucetland
        // @FIXME 现在水龙头放到东莞发货，修改为东莞联系方式，更好的做法是按仓库来 by Zandy 20120612
        $smarty->display("shipment/invoice_print_faucetland_dg.htm");
    } elseif ($_SESSION['party_id'] == 65564) {
        // jenjenhouse
        $smarty->display("shipment/invoice_print_jenjenhouse.htm");
    } elseif ($_SESSION['party_id'] == 65567) {
        // jennyjoseph
        $smarty->display("shipment/invoice_print_jennyjoseph.htm");
    } else {
        $smarty->display("shipment/invoice_print_jjshouse.htm");
    }
}

return;
///////////////////////////////////////////////////////////////////////////////////////////// 2011-12-20
if (is_jjshouse($order_info['party_id'])) {
    $Quantity = trim($_REQUEST['Quantity']);
    $UnitPrice = trim($_REQUEST['UnitPrice']);
    $carrier_code = trim($_REQUEST['carrier_code']);
    $hscode_id = trim($_REQUEST['hscode_id']);
    $compress_rate = 1*trim($_REQUEST['compress_rate']) / 100;
    if (!array_key_exists($hscode_id, $pinming_array)) {
    	$hscode_id = 3;
    }
    $pinming = $pinming_array[$hscode_id];
    $smarty->assign("Quantity", $Quantity);
    $smarty->assign("UnitPrice", $UnitPrice);
    $smarty->assign("order_info", $order_info);
    $smarty->assign("carrier_code", $carrier_code);
    $smarty->assign("pinming", $pinming);
    $smarty->assign("compress_rate", $compress_rate);
    //一份适用用欧洲,其他国家（除欧洲）用一份形式发票
    $sql = "select region_id from ecs_region where region_name in (
            'Finland','Sweden','Norway','Iceland','Denmark','Norfolk Island',
            'Estonia','Latvia','Lithuania','Belarus','Russian Federation','Ukraine', 'Moldova, Republic of',
            'Poland','Czech Republic','Slovakia (Slovak Republic)','Hungary','Germany','Austria','Switzerland','Liechtenstein',
            'United Kingdom','Ireland','Netherlands','Belgium','Luxembourg','France','Monaco',
            'Romania','Bulgaria','Serbia','Macedonia, The Former Yugoslav Republic of','Albania','Greece','Slovenia','Croatia','Bosnia and Herzegowina',
            'Italy','Vatican City State (Holy See)','San Marino','Malta','Spain','Portugal','Andorra'
            )";
    global $db;
    $region_lists = $db->getCol($sql);
    
    // {{{ 订单信息
    $order = $order_info;
    $order['goods_amount_usd_compress'] = 0;
	$order['qty'] = 0;
    foreach ($order['order_goods'] as $k => $goods) {
        $sql = "SELECT value FROM ecshop.order_goods_attribute where order_goods_id = '{$goods['rec_id']}' and name = 'shop_price' ";
        $goods['shop_price_usd'] = $goods['shop_price_usd_original'] = $db->getOne($sql);
    	if (!($goods['shop_price_usd'] > 0)) {
            // 如果价格为 0，设置为 1 USD
    		$goods['shop_price_usd'] = 1 / $compress_rate;
    	}
    	$goods['shop_price_compress'] = $goods['shop_price_usd'] * $compress_rate;
    	$goods['shop_price_compress'] = number_format($goods['shop_price_compress'], 2, '.', '');
    	$goods['goods_total_compress'] = number_format($goods['shop_price_compress'] * $goods['goods_number'], 2, '.', '');
    	
    	$order['goods_amount_usd_compress'] += $goods['goods_total_compress'];
    	$order['qty'] += $goods['goods_number'];
    	
    	$goods['goods_name_x'] = preg_replace('/\(.*|Size=.*|Bust=.*|Waist=.*|Hips=.*|Hollow to Floor=.*|Color=.*|Sash Color=.*/is', '', $goods['goods_name']);
    	
    	$order['goods_list'][$k] = $goods;
    }
	$order['goods_amount_usd_compress'] = number_format($order['goods_amount_usd_compress'], 2, '.', '');
	//$order['shipping_fee_x'] = number_format($order['shipping_fee'] * $compress_rate, 2, '.', '');
 	$shipping_fee = $order['shipping_fee'];
    if (isset($order['order_attributes']['shipping_fee'][0]['attr_value'])) {
    	$shipping_fee = $order['order_attributes']['shipping_fee'][0]['attr_value'];
    }
	$order['shipping_fee_x'] = number_format($shipping_fee * $compress_rate, 2, '.', '');
    $smarty->assign("order", $order);
    // }}}
    
    if ( !empty($order_info) && in_array($order_info['country'],$region_lists)) {
        $smarty->display("shipment/invoice_print_jjshouse_UnitedKingdom.htm");
    } elseif ($_SESSION['party_id'] == 65554) {
        $smarty->display("shipment/invoice_print_amormoda.htm");
    } else {
        $smarty->display("shipment/invoice_print_jjshouse.htm");
    }
} else {
    die();
}