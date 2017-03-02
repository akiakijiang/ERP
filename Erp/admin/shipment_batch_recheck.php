<?php 

/**
 * 发货单复核
 * 
 * @author ljzhou 
 * @copyright 2013.10.08
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('inventory_picking','ck_distribution_warehousing');
admin_priv('shipment_batch_recheck');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
//需要屏蔽发货按钮的仓库列表
$screened_shipment_facility_list = array('22143846', '22143847', '19568549', '24196974','19568548','3580047');

//上海仓除了金佰利外屏蔽条码
$screened_barcode_facility_list = array('22143846', '22143847', '19568549', '24196974');

// 发货单号
$shipment_id = 
    isset($_REQUEST['shipment_id']) && trim($_REQUEST['shipment_id']) 
    ? $_REQUEST['shipment_id'] 
    : false ;

// 消息
$message = 
    isset($_REQUEST['message']) && trim($_REQUEST['message']) 
    ? $_REQUEST['message'] 
    : false;


    
// 当前页的url,构造url用
$url = 'shipment_batch_recheck.php';
if ($shipment_id) {
	$can_recheck_result = is_shipment_can_recheck($shipment_id);
	if(!$can_recheck_result['status']){
		header("Location: ".add_param_in_url($url, 'message', $can_recheck_result['error']));
		exit;
	}
	
    $url = add_param_in_url($url, 'shipment_id', $shipment_id);
    
    // 取得发货单的主订单信息
    $order = get_primary_order($shipment_id);
    
    //需要屏蔽发货按钮的仓库列表
	$screened_shipment_flag = false;
	if (in_array($order['facility_id'], $screened_shipment_facility_list)) {
	    $screened_shipment_flag = true;
	}
	//上海仓除了金佰利外屏蔽条码
	$screened_barcode_flag = false;
	if (in_array($order['facility_id'], $screened_barcode_facility_list) && $_SESSION['party_id'] != '65558') {
		$screened_barcode_flag = true;
	}
	// 显示配送信息
	$smarty->assign('shipment_id',$shipment_id);  
	// 订单列表
	$smarty->assign('order',$order);                  // 主订单
	$smarty->assign('screened_shipment_flag', $screened_shipment_flag);
	$smarty->assign('screened_barcode_flag', $screened_barcode_flag);
	
	$smarty->assign('show_scan_tracking_number', true);
}


if ($message) {
    // 如果传递过来了消息则显示消息
    $smarty->assign('message', $message);
}



//$smarty->assign('big_goods_number',get_big_goods_number());  // 得到大订单中大商品的界线数值
$smarty->display('shipment/shipment_batch_recheck.htm');

function is_shipment_can_recheck($shipment_id){
	global $db;
	// 如果传递了发货单号则查询相关信息
	$result = array();
	$result['status'] = true;
    $sql = "select * from romeo.shipment where shipment_id = '{$shipment_id}' ";
    $shipment = $db->getRow($sql);
    
    if (empty($shipment)){
    	$result['status'] = false;
        $result['error'] = "该发货单（shipmentId:{$shipment_id}）不存在";
        return $result;
    }
    // 没有面单
    if (empty($shipment['TRACKING_NUMBER'])){
        $result['status'] = false;
        $result['error'] = "该发货单（shipmentId:{$shipment_id}）没有面单号！请先打印面单号";
        return $result;
    }
    
    // 取得发货单的所有订单信息
    $sql = "select * from romeo.order_shipment where shipment_id = '{$shipment_id}' ";
    $response2 = $db->getAll($sql);
    if (empty($response2)) {
    	$result['status'] = false;
        $result['error'] = "该发货单（shipmentId:{$shipment_id}）异常，找不到对应的主订单";
        return $result;
    }
    
    
    // 判断是否预定
    $no_reserve_order = check_shipment_all_reserved($shipment_id);
    if(!empty($no_reserve_order)) {
    	$result['status'] = false;
		$result['error'] = "该发货单（shipmentId:{$shipment->shipmentId}）对应的订单未预订成功（orderId:{$no_reserve_order}）";
	    return $result; 
    } 
    
    $sql =" select 
				oi.order_sn,
				oi.facility_id,
				oi.handle_time,
				oi.order_type_id,
				ep.pay_code,
				ep.is_cod,
				oi.pay_status,
				oi.order_status,
				oi.shipping_status
			from romeo.order_shipment os
			inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id 
			inner join ecshop.ecs_payment ep on oi.pay_id = ep.pay_id
			where os.shipment_id = '{$shipment_id}'";
    $orders = $db->getAll($sql);
    foreach($orders as $order){
    	$orderSn = "发货单{$shipment_id} 中 订单 {$order['order_sn']} ";
    	if (!in_array($order['order_type_id'], array('SALE','SHIP_ONLY','RMA_EXCHANGE')) ) {
			$result['status'] = false;
	        $result['error'] = $orderSn."不是可出库的订单类型";
	        return $result;
		}
		if ($order['order_status'] != 1) {
			$result['status'] = false;
	        $result['error'] = $orderSn."不是已确认订单";
	        return $result;
		}
		if ($order['shipping_status']==8 ) {
			$result['status'] = false;
	        $result['error'] = $orderSn."已经复核过了，注意不要重复发货！";
	        return $result;
		}
		if ($order['shipping_status'] != 12 ) {
			$result['status'] = false;
	        $result['error'] = $orderSn."不是待复核状态";
	        return $result;
		}
		if ($order['pay_code'] != 'cod' && $order['is_cod'] == '0'
			&& $order['pay_status'] != 2 
			&& $order['order_type_id'] != 'RMA_EXCHANGE' 
			&& $order['order_type_id'] != 'SHIP_ONLY' ) { // 发货的条件：cod 或者 pay_status = 2 或者 是换货订单，或者是 SHIP_ONLY的订单
			$result['status'] = false;
	        $result['error'] = $orderSn."还没有支付";
	        return $result;
		}
		if ($order['handle_time'] > 0 && time() < $order['handle_time']) {
			$result['status'] = false;
	        $result['error'] = $orderSn."处理时间是： ." .date("Y-m-d" , $order['handle_time']);
	        return $result;
		}
		if (empty($order['facility_id'])) {
			$result['status'] = false;
	        $result['error'] = $orderSn."未指定发货仓库";
	        return $result;
		}
		if (strpos($_SESSION['facility_id'].',', $order['facility_id'].',') === false) {
			$result['status'] = false;
	        $result['error'] = $orderSn."无法在当前仓库发货";
	        return $result;
		}
    }
    return $result;
}


function get_primary_order($shipment_id){
	global $db;
	// 如果传递了发货单号则查询相关信息
	$sql = "select * from romeo.shipment where shipment_id = '{$shipment_id}' ";
    $shipment = $db->getRow($sql);
    // 主订单
    return get_core_order_info_base('', $shipment['PRIMARY_ORDER_ID']);
}
