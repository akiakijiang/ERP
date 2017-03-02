<?php 

/**
 * 批次号复核
 * 
 * 
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('inventory_picking','ck_distribution_warehousing');
admin_priv('shipment_batch_pick_recheck');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
//需要屏蔽发货按钮的仓库列表
$screened_shipment_facility_list = array('22143846', '22143847', '19568549', '24196974','19568548','3580047');

//上海仓除了金佰利外屏蔽条码
$screened_barcode_facility_list = array('22143846', '22143847', '19568549', '24196974');

//商家
$party_id=
 	isset($_SESSION['party_id']) && trim($_SESSION['party_id']) 
    ? $_SESSION['party_id']
    : false ;

// 批次号
$batch_pick_sn= 
    isset($_REQUEST['batch_pick_sn']) && trim($_REQUEST['batch_pick_sn']) 
    ? $_REQUEST['batch_pick_sn'] 
    : false ;
// 消息
$message = 
    isset($_REQUEST['message']) && trim($_REQUEST['message']) 
    ? $_REQUEST['message'] 
    : false;
   
// 当前页的url,构造url用
$url = 'shipment_batch_pick_recheck.php';
if ($batch_pick_sn) {
	//判断是不是亨氏，亨氏不允许使用批次号复核
	if($party_id == '65609' && need_heinz_logistics_codes($batch_pick_sn)){
		header("Location: ".add_param_in_url($url, 'message', '该亨氏批次中存在需要扫描物流码的商品，不能使用此页面操作'));
		exit;
	}
	//判断批次号是不是当前商家的产品
	$get_batch_party_id = get_batch_party_id($batch_pick_sn);
	if(empty($get_batch_party_id)){
		header("Location: ".add_param_in_url($url, 'message', '该批次号中没有发货单'));
		exit;
	}else if($get_batch_party_id != $party_id){
		header("Location: ".add_param_in_url($url, 'message', '该批次号订单和当前业务组不符'));
		exit;
	}
	$shipment_ids=get_shipment_ids($batch_pick_sn);
	if($shipment_ids){
		$orders=array();
		foreach ($shipment_ids as $key => $shipment_id) {
			$can_recheck_result = is_shipment_can_recheck($shipment_id);
			if(!$can_recheck_result['status']){
				header("Location: ".add_param_in_url($url, 'message', $can_recheck_result['error']));
				exit;
			}

			$url = add_param_in_url($url, 'shipment_id', $shipment_id);

	    
		    // 取得发货单的主订单信息
		    $order = get_primary_order($shipment_id);
		    $order['shipment_id'] = $shipment_id;
		    array_push($orders,$order);	

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
		      
		}

		//检查批次号的商品是不是一样
		$is_order_in_the_same = check_order_in_the_same($batch_pick_sn);
		// var_dump($is_order_in_the_same);
		if(!$is_order_in_the_same['status']){
			header("Location: ".add_param_in_url($url, 'message', $is_order_in_the_same['error']));
			exit;
		}
		$smarty->assign('show_scan_tracking_number', true);	  
	}
	else{
		$message="该批次号没有发货单信息";
		header("Location: ".add_param_in_url($url, 'message', $message));
		exit;
	}
	
}

//显示批次号
$smarty->assign('batch_pick_sn',$batch_pick_sn);  
// 显示配送信息
$smarty->assign('shipment_ids',$shipment_ids);  
// 订单列表
$smarty->assign('orders',$orders);                  // 主订单
$smarty->assign('screened_shipment_flag', $screened_shipment_flag);
$smarty->assign('screened_barcode_flag', $screened_barcode_flag);

if ($message) {
    // 如果传递过来了消息则显示消息
    $smarty->assign('message', $message);
}

$smarty->display('shipment/shipment_batch_pick_recheck.htm');

function need_heinz_logistics_codes($batch_pick_sn){
	global $db;
	$sql = "SELECT 1 from romeo.batch_pick_mapping bpm 
		INNER JOIN romeo.order_shipment os on os.SHIPMENT_ID = bpm.shipment_id
		INNER JOIN ecshop.ecs_order_info o ON o.order_id = CAST(os.order_id as UNSIGNED)
    	INNER JOIN ecshop.ecs_order_goods og ON og.order_id = o.order_id
    	INNER JOIN ecshop.brand_heinz_goods bh ON bh.goods_outer_id = og.goods_id
		where bh.is_activity = 1 AND o.distributor_id in ('1797','1900','1953','2333')
		AND o.party_id = 65609 AND o.order_type_id in ('SALE','RMA_EXCHANGE')
		AND bpm.batch_pick_sn = '{$batch_pick_sn}' and bh.heinz_goods_sn like 'H%' ";
	$exist = $db->getOne($sql);	
	if(empty($exist)){
		return false;
	}else{
		return true;
	}
}
function is_shipment_can_recheck($shipment_id){
	global $db;
	// 如果传递了发货单号则查询相关信息
	$result = array();
	$result['status'] = true;
    $sql = "select * from romeo.shipment where shipment_id = '{$shipment_id}' ";
    $shipment = $db->getRow($sql);
    if (!$shipment){
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
		$result['error'] = "该发货单（shipmentId:{$shipment['SHIPMENT_ID']}）对应的订单未预订成功（orderId:{$no_reserve_order}）";
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
	        $result['error'] = $orderSn."无法在当前仓库发货（你没有订单仓库【{$order['facility_id']}】的权限）";
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

//获取批次号的所有发货单
function get_shipment_ids($batch_pick_sn){
	global $db;
	$sql="select shipment_id from romeo.batch_pick_mapping where batch_pick_sn='{$batch_pick_sn}'";
	$shipment_ids = $db->getCol($sql);
	return $shipment_ids;
}

//判断批次号中订单商品的种类和数量是否一样
function check_order_in_the_same($batch_pick_sn){
	global $db;
	$result = array();
	//取得第一个订单号
	$sql1 = "SELECT shipment_id from romeo.batch_pick_mapping
			WHERE batch_pick_sn='{$batch_pick_sn}'";
	$shipment_ids = $db->getCol($sql1);
	$count=count($shipment_ids,COUNT_NORMAL);
	if($count==1){
		$result['error'] = "该批次号中只有一个发货单，请使用发货单复核页面";
		$result['status'] = false;
		return $result;
	}
	if($shipment_ids){		
		$shipment_id = $shipment_ids[0];
		
		//取得第一个订单的商品总类，商品总个数
			$sql2 = "SELECT SUM(goods_number) as goods_number,COUNT(DISTINCT concat_ws('_',goods_id,style_id))as goods_style 
					FROM ecshop.ecs_order_goods og
					inner join romeo.order_shipment os on og.order_id =CAST(os.order_id as UNSIGNED) 
					WHERE os.shipment_id ='{$shipment_id}' ";
			$order_info = $db->getAll($sql2);
			if($order_info[0]['goods_number'] == 0){
				$result['error'] = "没有找到对应商品或商品件数为0";
				$result['status'] = false;
				return $result;
			}else{
				$goods_number = $order_info[0]['goods_number'];
				$goods_style = $order_info[0]['goods_style'];
				//var_dump($order_info);
				//取得所有的发货单号
				unset($shipment_ids[0]);
				//循环比较所有的订单的商品总类和个数是否和第一个订单相同
				foreach($shipment_ids as $key=>$value) { 
					$sql4 = "SELECT SUM(goods_number) as goods_number,COUNT(DISTINCT concat_ws('_',goods_id,style_id))as goods_style 
					FROM ecshop.ecs_order_goods og
					inner join romeo.order_shipment os on og.order_id =CAST(os.order_id as UNSIGNED) 
					WHERE os.shipment_id ='{$value}' ";
					$order_info_compared = $db->getAll($sql4);
					//var_dump($order_info_compared);
					if($order_info_compared[0]['goods_style'] != $goods_style){
						$result['error'] = "订单商品总类不一致。肇事发货单号：".$value;
						$result['status'] = false;
						return $result;
					}
					if($order_info_compared[0]['goods_number'] != $goods_number){
						$result['error'] = "订单商品总数不一致。肇事发货单号：".$value;
						$result['status'] = false;
						return $result;
					}
				}
				$check_order_details_in_the_same = check_order_details_in_the_same($batch_pick_sn);
				if($check_order_details_in_the_same){
					$result['status'] = true;
					return $result;
				}else{
					$result['error'] = "该批次号订单中商品详情不一致";
					$result['status'] = false;
					return $result;
				}				
			}
		}else{
			$result['error'] = "没有找到相关的订单号或批次号输入错误";
			$result['status'] = false;
			return $result;
		}

}

//判断商品中的详细信息是否一样
function check_order_details_in_the_same($batch_pick_sn){
	global $db;
	$sql = "SELECT shipment_id from romeo.batch_pick_mapping 
			WHERE batch_pick_sn='{$batch_pick_sn}'";
	$shipment_ids = $db->getCol($sql);
	$length = count($shipment_ids,COUNT_NORMAL);
	//var_dump($length);
	$shipment_details_array =array();
	$first_shipment_details=array();
	$count = 0;
	foreach ($shipment_ids as $key => $value) {
		$sql2 =	"SELECT CONCAT_WS('_',goods_id,style_id,sum(goods_number) ) as goods_details
				from ecshop.ecs_order_goods og 
				inner join romeo.order_shipment os on og.order_id =CAST(os.order_id as UNSIGNED) 
					WHERE os.shipment_id= '{$value}' 
				GROUP BY goods_id,style_id
				ORDER BY goods_details DESC";
		$shipment_details = $db->getCol($sql2);
		if($key == 0){
			$first_shipment_details[0] = $shipment_details;
		}
		//var_dump($first_shipment_details);
		//var_dump($shipment_details);
		if(!in_array($shipment_details,$first_shipment_details)){
			return false;
			break;
		}
		else{
			$count++;
			//var_dump($count);
		}		
	}
	if($count == $length){
		return true;
	}
}

function get_batch_party_id($batch_pick_sn){
	global $db;
	$sql = "SELECT party_id from ecshop.ecs_order_info eoi
			LEFT JOIN romeo.order_shipment oi on eoi.order_id=oi.order_id
			INNER JOIN romeo.batch_pick_mapping bpm on oi.shipment_id=bpm.shipment_id
			WHERE batch_pick_sn='{$batch_pick_sn}'";
	$party_id = $db->getOne($sql);
	return $party_id;
}