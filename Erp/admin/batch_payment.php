<?php
/**
 * 财务批量付款
 * 
 * @author zxcheng 2014.02.11
 */
define('IN_ECS', true);
require_once('includes/init.php');
require("function.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

admin_priv('batch_payment_status');
$act = $_REQUEST['act'];
$order_ids  = $_REQUEST['order_ids'];
//Qlog::log('--act--'.$act);
$action_time = date("Y-m-d H:i:s");
if($act == 'search'){
	$condition = getCondition();
	$condition .= " and " . party_sql('oi.party_id');
	$sql = "SELECT 
			oi.order_id, oi.order_sn,  oi.order_time, 
			oi.taobao_order_sn,oi.order_status, oi.order_amount, 
			oi.goods_amount, oi.shipping_fee, oi.shipping_status, oi.shipping_name, 
			oi.consignee, oi.pay_status, oi.currency,
			-- cb.bill_no
			s.tracking_number bill_no
		FROM ecshop.ecs_order_info AS oi 
		-- LEFT JOIN ecshop.ecs_carrier_bill cb ON cb.bill_id = oi.carrier_bill_id 
		LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (oi.order_id USING utf8)
		LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
		WHERE 
			oi.order_type_id = 'SALE' 
		AND s. STATUS != 'SHIPMENT_CANCELLED'
		{$condition}
		GROUP BY oi.order_id
		ORDER BY oi.order_time DESC 
	";
	//Qlog::log('search——sql--'.$sql);
	$refs_value_validity = $refs_validity = array ();
	$orders = $db->getAllRefBy ( $sql, array ('order_id' ), $refs_value_validity, $refs_validity );
	// 查询出订单商品
    $sql = "SELECT order_id,goods_price,goods_name,goods_number FROM ecshop.ecs_order_goods 
		WHERE ". db_create_in($refs_value_validity['order_id'], "order_id");
	//Qlog::log('order-info-sql--'.$sql);
    $goods_lists = $db->getAll($sql);
	foreach ($orders as $key => $order) {
		//订单状态
		$orders[$key]['order_status_name'] = get_order_status($order['order_status']);
	    $orders[$key]['shipping_status_name'] = get_shipping_status($order['shipping_status']);
	    $orders[$key]['pay_status_name'] = get_pay_status($order['pay_status']);
	    $goods_list_a = array();
	    foreach ($goods_lists as $goods_list) {
	    	if($goods_list['order_id'] == $order['order_id']){
	    		array_push($goods_list_a,$goods_list);
	    	}
	    }
	    $orders[$key]['goods_list'] = $goods_list_a;
	}
	$smarty->assign('orders', $orders);
}else if($act == 'finance' && is_array($order_ids)){
	$message = "未付款成功订单号：";
	$orderids = array(); 
	foreach ($order_ids AS $key=> $order_id) {
		$orderids[$key] = $order_id;
		$pay_status = $_REQUEST["pay_status_$order_id"];
		$order_sn = $_REQUEST["order_sn_$order_id"];
		Qlog::log('--pay_status--'.$_REQUEST["pay_status_$order_id"]);
		if($pay_status != 0 ){
			$message .= $order_sn.",";
		}else{
			$note_status = ". 付款状态从未付款改成已付款";
		    $action_sql = "insert into ecshop.ecs_order_action (order_id, pay_status, action_time, action_note, action_user)  VALUES('$order_id', '2', '$action_time', '$note_status', '{$_SESSION['admin_name']}')";
		    //Qlog::log('--action_sql--'.$action_sql);
		    $db->query($action_sql);
			$sql = "UPDATE ecshop.ecs_order_info SET pay_status = '2'  WHERE order_id = '{$order_id}'";
			//Qlog::log('--sql--'.$sql);
		    $db->query($sql);
		}
	}
	if($message == "未付款成功订单号："){
		$message = "所有搜索订单付款成功！";
	}else{
		$message .= " 不是未付款状态，不能进行批量付款操作！";
	}
	$sql = "SELECT oi.order_id, oi.order_sn,  oi.order_time, oi.taobao_order_sn,oi.order_status, oi.order_amount, 
			   oi.goods_amount, oi.shipping_fee, oi.shipping_status, oi.shipping_name, 
			   oi.consignee, oi.pay_status, oi.currency,
			   -- cb.bill_no
			   s.tracking_number bill_no
		FROM ecshop.ecs_order_info AS oi 
		-- LEFT JOIN ecshop.ecs_carrier_bill cb ON cb.bill_id = oi.carrier_bill_id 
		LEFT JOIN romeo.order_shipment os ON os.order_id = CONVERT (oi.order_id USING utf8)
		LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
		WHERE 
			s. STATUS != 'SHIPMENT_CANCELLED' 
			and ". db_create_in($orderids, "oi.order_id").
		' GROUP BY oi.order_id ORDER BY oi.order_time DESC ';
	//Qlog::log('---order_infos--'.$sql);
	$orders = $db->getAll($sql);
	// 查询出订单商品
    $sql = "SELECT order_id,goods_price,goods_name,goods_number FROM ecshop.ecs_order_goods 
		WHERE ". db_create_in($orderids, "order_id");
	//Qlog::log('order-info-sql--'.$sql);
    $goods_lists = $db->getAll($sql);
	foreach ($orders as $key => $order) {
		//订单状态
		$orders[$key]['order_status_name'] = get_order_status($order['order_status']);
	    $orders[$key]['shipping_status_name'] = get_shipping_status($order['shipping_status']);
	    $orders[$key]['pay_status_name'] = get_pay_status($order['pay_status']);
	    $goods_list_a = array();
	    foreach ($goods_lists as $goods_list) {
	    	if($goods_list['order_id'] == $order['order_id']){
	    		array_push($goods_list_a,$goods_list);
	    	}
	    }
	    $orders[$key]['goods_list'] = $goods_list_a;
	    
	}
	$smarty->assign('orders', $orders);
	$smarty->assign('message', $message);
}
$smarty->display('finance/batch_payment.htm');

function getCondition() {
   $order_condition = "";
   $batch_order_sn = trim($_REQUEST['batch_order_sn']);
   $batch_taobao_sn = trim($_REQUEST['batch_taobao_sn']);
   if (!empty($batch_order_sn)) {
        $order_sns = preg_split('/[\s]+/', $batch_order_sn);
        foreach ($order_sns as $key => $order_sn) {
            if (trim($order_sn) == '') {
                unset($order_sns[$key]);
            }
        }
        $order_condition .= " AND " . db_create_in($order_sns, "oi.order_sn");
   }
   if (!empty($batch_taobao_sn)) {
        $taobao_sns = preg_split('/[\s]+/', $batch_taobao_sn);
        foreach ($taobao_sns as $key => $taobao_sn) {
            if (trim($taobao_sn) == '') {
                unset($taobao_sns[$key]);
            }
        }
        $order_condition .= " AND " . db_create_in($taobao_sns, "oi.taobao_order_sn");
   }
   
   if(empty($order_condition)){
   		$order_condition .= " AND oi.order_sn = '' ";
   }
   
   return $order_condition;
}

?>
