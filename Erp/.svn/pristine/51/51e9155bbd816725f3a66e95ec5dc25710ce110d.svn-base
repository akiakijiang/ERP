<?php
/**
 * 待配货提交处理
 * 待发货提交处理
 */
define('IN_ECS', true);
require('includes/init.php');
require('function.php');
include_once("includes/lib_common.php");
include_once("includes/lib_order.php");
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
include_once("includes/lib_service.php");

$action_time = date("Y-m-d H:i:s");
$itime = strtotime($action_time);

$back = $_REQUEST['back'] !== null ? $_REQUEST['back'] : "/";
$back = remove_param_in_url($back, 'info');
$sqls = array();

$party_id   = $_SESSION ['party_id'];
$admin_name = $_SESSION['admin_name'];

$action = $_REQUEST['action'];
/**
 *  更新已经发货订单的快递
 */
 if($action == 'UPDATESHIPMENT'){
 	$order_sn = $_REQUEST['order_sn'];
 	$bill_no = $_REQUEST['bill_no'];
 	$carrier_id = intval($_REQUEST['carrier_id']);
 	$shipping_id = intval($_REQUEST['shipping_id']);
 	
 	// 获取原订单相关信息 做check
 	$order_sql = "select oi.order_id, oi.order_sn, oi.pay_id, p.pay_code, p.is_cod, oi.shipping_id, oi.shipping_name, s.support_cod, s.support_no_cod, oi.order_status, oi.shipping_status, oi.pay_status
                     from ecshop.ecs_order_info oi
                          left join ecshop.ecs_payment p on oi.pay_id = p.pay_id
                          left join ecshop.ecs_shipping s on oi.shipping_id = s.shipping_id
                   where oi.order_sn = '%s' limit 1; ";
 	$order_info = $db->getRow(sprintf($order_sql, $order_sn));

 	// 先判断是否支持一种付款方式
 	$shipping_sql = "select shipping_id, shipping_name, support_cod, support_no_cod from ecshop.ecs_shipping where shipping_id = %d limit 1" ;
 	$shipping = $db->getRow(sprintf($shipping_sql, $shipping_id));
 	
 	// 如果支持的支付方式不统一， 则要返回
 	if($order_info['support_cod'] != $shipping['support_cod'] || $order_info['support_no_cod'] != $shipping['support_no_cod']){
 	    $back = add_param_in_url($back, 'info', "修改的快递公司收款方式，与原订单中收款方式不一样  要当心了。");
        Header("Location: $back");
        return;	
 	}
 	
 	//判断订单信息是否完整
 	if($order_info['order_id']==''){
 	    $back = add_param_in_url($back, 'info', "订单信息不完整,请查找订单信息");
        Header("Location: $back");
        return;	
 	}
 	
 	// 查看合并订单
 	$merge_order_sql = "select s2.shipment_id, group_concat(s2.order_id) orderids 
                           from romeo.order_shipment s1
                               inner join romeo.order_shipment s2 on s1.shipment_id = s2.shipment_id  
                               inner join romeo.shipment s on s2.shipment_id = s.shipment_id
                          where s1.order_id = '%s' 
                            and s.shipping_category = 'SHIPPING_SEND'
                            and s.status != 'SHIPMENT_CANCELLED'
                        group by s2.shipment_id" ;
 	$merge_orders = $db->getRow(sprintf($merge_order_sql, $order_info['order_id']));
	
 	// 更新快递 kill ECB and replaced by the following one. By Sinri 20160105
 	// $sql = "update ecshop.ecs_order_info oi, ecshop.ecs_carrier_bill b
  //               set oi.shipping_id = %d , oi.shipping_name = '%s' , b.bill_no = '%s' , b.carrier_id = %d
  //          where oi.carrier_bill_id = b.bill_id and oi.order_id in %s ;";
  //   $db->query(sprintf($sql,  intval($shipping['shipping_id']), $shipping['shipping_name'], $bill_no, $carrier_id, '('.$merge_orders['orderids'].')'));

    $sql="UPDATE ecshop.ecs_order_info SET shipping_id=%d, shipping_name='%s' where order_id in %s";
    $db->query(sprintf($sql,  intval($shipping['shipping_id']), $shipping['shipping_name'],  '('.$merge_orders['orderids'].')'));
    
    $shipment_sql = "update romeo.shipment set tracking_number = '%s', carrier_id = '%s', shipment_type_id = '%s' where shipment_id = '%s' " ;
 	$db->query(sprintf($shipment_sql,  $bill_no, $carrier_id, $shipping_id, $merge_orders['shipment_id'])) ;
 	
 	// 记录保存到
 	$orderids = split(',', $merge_orders['orderids']);
 	$order_attribute_sql = "insert into ecshop.order_attribute (order_id, attr_name, attr_value) values (%d, 'SHIPMENT', '%s'); " ;
 	$order_action_sql = "insert into ecshop.ecs_order_action (order_id, action_user, order_status, shipping_status, pay_status, action_time, action_note) values (%d, '%s', %d, %d, %d, now(), '%s')";
 	
 	$shippings = getShippingTypes();
 	$origin_shippingname = $shippings[$order_info['shipping_id']]['shipping_name'];
 	$new_shippingname = $shippings[$shipping_id]['shipping_name'];
 	$action_hide = "物流修改快递 : 从 $origin_shippingname 修改为  $new_shippingname $bill_no";
 	$action_note = $action_hide . $_REQUEST['action_note'];
 	foreach($orderids as $item){
 		$db->query(sprintf($order_attribute_sql, intval($item), $bill_no)) ;
 		$db->query(sprintf($order_action_sql,  intval($item), $_SESSION['admin_name'], 
 		                        intval($order_info['order_status']), intval($order_info['shipping_status']), intval($order_info['pay_status']), $action_note));
 	}
 }else if($action == 'finance'){
	$order_ids = $_POST['order_id'];
	foreach($order_ids as $order_id){
		$order = getOrderInfo($order_id);
		$order_sn = $order['order_sn'];
		
		$note         = trim($_POST["note_{$order_sn}"]);
		$pay_id = intval($_POST["pay_id_{$order_sn}"]);
		
        $pay_status = intval($_POST["pay_status_{$order_sn}"]);
        $pay_method = $_POST["pay_method_$order_sn"];
        $real_paid = $_POST["real_paid_{$order_sn}"];
        $real_shipping_fee = $_POST["real_shipping_fee_$order_sn"];
        $proxy_amount = $_POST["proxy_amount_$order_sn"];

        if ($pay_status != $order['pay_status']) {
            $note_status = " 付款状态从".$_CFG['adminvars']['pay_status'][$order['pay_status']]."改成".$_CFG['adminvars']['pay_status'][$pay_status];
            $action_sql = "insert into " . $ecs->table('order_action') . "(order_id, pay_status, action_time, action_note, action_user) VALUES('{$order['order_id']}', '$pay_status', '$action_time', '$note.$note_status', '{$_SESSION['admin_name']}')";
            $db->query($action_sql);
            $sqls[] = $sql;

            $sql = " SELECT IF(mobile IS NULL OR mobile = '', tel, mobile) AS mobile FROM {$ecs->table('order_info')} WHERE order_sn = '{$order_sn}' LIMIT 1";
            $mobile = $db->getOne($sql);

            if ($pay_status == 2) {
                $sql = "UPDATE {$ecs->table('order_info')} SET pay_time = UNIX_TIMESTAMP(NOW()) where order_sn = '{$order_sn}'";
                $db->query($sql);
                
                # 短信事件 20090822
                if (!$db->getOne("SELECT * FROM order_attribute WHERE order_id = '{$order['order_id']}' AND attr_name = 'outer_type'")) {
                    $msg_vars = array('msg_order_sn' => $order_sn);
                    erp_send_message('pay_confirm', $msg_vars, $order['party_id'], $order['distributor_id'], $mobile);
                }
                
                // update order mixed status 
                // include_once('includes/lib_order_mixed_status.php');
                // update_order_mixed_status($order['order_id'], array('pay_status' => 'paid'), 'worker');
            }
        }
        
        if ($pay_id) {
            require_once(ROOT_PATH . 'includes/lib_order.php');
            $payment = payment_info($pay_id);
            if ($payment) {
                $pay_sql = " pay_id = '{$payment['pay_id']}', pay_name = '{$payment['pay_name']}', ";	
            }
        }
        
        // 不能修改实收金额 modified by yxiang@oukoo.com 20090626 
        // $sql = "UPDATE {$ecs->table('order_info')} SET {$pay_sql} pay_status = '{$pay_status}', real_paid = '{$real_paid}', real_shipping_fee = '{$real_shipping_fee}', proxy_amount = '{$proxy_amount}', pay_method = '{$pay_method}' WHERE order_sn = '{$order_sn}'";
		$sql = "UPDATE {$ecs->table('order_info')} SET {$pay_sql} pay_status = '{$pay_status}', real_shipping_fee = '{$real_shipping_fee}', proxy_amount = '{$proxy_amount}', pay_method = '{$pay_method}' WHERE order_sn = '{$order_sn}'";
        $db->query($sql);
        $sqls[] = $sql;
        
        // 记录备注
        if ($note != '') {
            orderActionLog(array('order_id' => $order['order_id'], 'action_note' => $note));   
        }
	}
}

$back = remove_param_in_url($back, 'delivery_order_sn');
Header("Location: $back");
?>
