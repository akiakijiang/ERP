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
    
// 面单号
$tracking_number = 
    isset($_REQUEST['tracking_number']) && trim($_REQUEST['tracking_number']) 
    ? $_REQUEST['tracking_number'] 
    : false ;
    
//贺卡号（发货单号） 
$card_shipment_id = 
    isset($_REQUEST['card_shipment_id']) && trim($_REQUEST['card_shipment_id']) 
    ? $_REQUEST['card_shipment_id'] 
    : false ;


$smarty->assign('tracking_number', $tracking_number);

// 消息
$message = 
    isset($_REQUEST['message']) && trim($_REQUEST['message']) 
    ? $_REQUEST['message'] 
    : false;

// 是否展示商品条码框
$show_scan_goods_barcode = 
    isset($_REQUEST['show_scan_goods_barcode']) && trim($_REQUEST['show_scan_goods_barcode']) 
    ? $_REQUEST['show_scan_goods_barcode'] 
    : false ;
    
// 当前页的url,构造url用
$url = 'shipment_recheck.php';
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
	$smarty->assign('show_scan_goods_barcode', $show_scan_goods_barcode);
}


if ($message) {
    // 如果传递过来了消息则显示消息
    $smarty->assign('message', $message);
}


if($show_scan_goods_barcode){
	$sql = "select 1 from romeo.shipment where shipment_id = '{$shipment_id}' and tracking_number = '{$tracking_number}'";
	$is_match = $db->getAll($sql);
	if(empty($is_match)){
		header("Location: ".add_param_in_url($url, 'message', "发货单号'{$shipment_id}'和面单号'{$tracking_number}'不匹配！"));
		exit;
	}
	$order_list  = get_goods_item_by_shipment($shipment_id);
	foreach ($order_list as $order) {
        
        // 获取重要备注——后来贯钢说备注都显示出来
        $sql = "select action_note from ecs_order_action where order_id = '{$order['order_id']}' ";
        $important_note = $db->getCol($sql);
        if (is_array($important_note)) {
            $order['important_note'] = join("；<br>", $important_note);
        }
    }
	$smarty->assign('order_list',$order_list); 
}
$show_order_cards = false;
$video = false;
$greetings = false;
if($_SESSION['party_id'] == '65628' && $shipment_id && $tracking_number){
	$sql = "select blg.greetings,blg.video_name from ecshop.brand_lamer_gift blg
			INNER JOIN  ecshop.ecs_order_info eoi ON blg.taobao_order_sn = eoi.taobao_order_sn
			INNER JOIN romeo.shipment s on s.primary_order_id = eoi.order_id
			WHERE s.shipment_id = '{$shipment_id}' and s.tracking_number='{$tracking_number}'";
	$card_info = $db->getAll($sql);
	if(!empty($card_info) && ($card_info['greetings'] != '' || $card_info['video_name'] != 'no_video')){
		$show_order_cards = true;
		$smarty->assign('show_order_cards',$show_order_cards); 	
	} 	
}

	
$show_order_cards_info = false;
if($card_shipment_id && $card_shipment_id == $shipment_id) {
	$sql = "select blg.taobao_order_sn,blg.greetings,blg.video_name from ecshop.brand_lamer_gift blg
			INNER JOIN  ecshop.ecs_order_info eoi ON blg.taobao_order_sn = eoi.taobao_order_sn
			INNER JOIN romeo.shipment s on s.primary_order_id = eoi.order_id
			WHERE s.shipment_id = '{$card_shipment_id}'";
//	$sql = "select taobao_order_sn,greetings,video_name from ecshop.brand_lamer_gift where taobao_order_sn = '{$taobao_order_sn}'";
	$card_info = $db->getRow($sql);
	if(!empty($card_info) && ($card_info['greetings'] != '' || $card_info['video_name'] != 'no_video')) {
		$show_order_cards_info = true;
		if($card_info['video_name'] != 'no_video') {
			$video = true;
		}
		if($card_info['greetings'] != '') {
			$greetings = true;
		}

	}	
}
$smarty->assign('greetings',$greetings); 
$smarty->assign('video',$video); 
$smarty->assign('show_order_cards_info',$show_order_cards_info);
$smarty->assign('card_shipment_id',$card_shipment_id);
$smarty->assign('card_info',$card_info);

//$smarty->assign('big_goods_number',get_big_goods_number());  // 得到大订单中大商品的界线数值
$smarty->display('shipment/shipment_recheck.htm');

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
			where os.shipment_id = '{$shipment_id}' AND ". party_sql("oi.party_id") ;
    $orders = $db->getAll($sql);
    if(!$orders){
    	$result['status'] = false;
	    $result['error'] = "该发货单（shipmentId:{$shipment->shipmentId}）不存在，可能是您没切换组织！";
	    return $result;
    }
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

function get_goods_item_by_shipment($shipment_id){
	global $db;
	require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
	$list = array();
	$order_list = array();
	$sql = "select order_id from romeo.order_shipment where shipment_id = '{$shipment_id}'";
	$order_ids = $db->getAll($sql);
	foreach($order_ids as $key=>$order_id){
		$order_list[$key] = get_core_order_info_base('', $order_id['order_id']);
	}
	// 格式化订单的配货商品结构,将erp记录按order_goods_id分组
	foreach($order_list as $order_key=>$order_item)
	{
		$item_list=array();
		foreach($order_item['order_goods'] as $goods_key=>$order_goods)
		{
			$key=$order_goods['rec_id'];
			
			$item_list[$key] = $order_goods;
			$sql = "
			    select 1 from ecshop.ecs_goods g, ecshop.ecs_category c
			    where g.cat_id = c.cat_id and c.cat_name = '虚拟商品' and g.goods_id = '{$order_goods['goods_id']}'
				limit 1
			";
			$item_list[$key]['is_productcode'] = false;
			if($db->getOne($sql)){
				$item_list[$key]['is_productcode'] = true;
			}
			
			$item_list[$key]['productcode']=encode_goods_id($order_goods['goods_id'], $order_goods['style_id']);
			$item_list[$key]['goods_type']=getInventoryItemType($order_goods['goods_id']);
			$item_list[$key]['status_id']=$order_goods['status_id'] == 'INV_STTS_AVAILABLE' ? '全新' : ( $order_goods['status_id'] == 'INV_STTS_USED' ? '二手' : $order_goods['status_id']) ;
			$item_list[$key]['text_array'] =  array();
			for($i = 1; $i <= $order_goods['goods_number']; $i++){
				$item_list[$key]['text_array'][] = $order_goods['rec_id'].'-'.$i;
			}

			//获取商品条码
	        $sql_barcode = "SELECT if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode " .
	        		"FROM ecshop.ecs_goods AS g " .
	        		"LEFT JOIN ecshop.ecs_goods_style AS gs ON gs.goods_id = g.goods_id and gs.is_delete=0 " . 
					"LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id " .
					"WHERE g.goods_id = '{$order_goods['goods_id']}' ";
					
			if($order_goods['style_id'] > 0){
        		 $sql_barcode =  $sql_barcode  . "and gs.style_id = '{$order_goods['style_id']}'" ;
			}
			$item_list[$key]['goods_barcode'] = $db->getOne($sql_barcode);
		}
		$order_list[$order_key]['item_list']=$item_list;
	}
	return $order_list;
}
