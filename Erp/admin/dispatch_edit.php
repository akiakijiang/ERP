<?php
/**
 * 对订单编辑发货单
 */
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
$act = $_REQUEST['act'];
$admin_name = $_SESSION['admin_name'];
admin_priv('dispatch_edit');
switch ($act) {
    case 'search' :
        $order_sn = trim($_REQUEST['order_sn']);
        $sql = "SELECT o.order_id, s.tracking_number, s.shipment_id 
            FROM ecshop.ecs_order_info o
            LEFT JOIN romeo.order_shipment os ON os.order_id = cast(o.order_id as char(15))
            LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
            WHERE ". party_sql('o.party_id', $_SESSION['party_id']) ." AND o.order_sn = '{$order_sn}'
        ";
        $shipment_list = $db->getAll($sql);
        foreach ($shipment_list as $k => $item) {
            //shipment_id是否有多个订单
            $sql = "SELECT o.order_id, o.order_sn, s.tracking_number, s.shipment_id, es.shipping_name, o.shipping_id, s.shipment_type_id, 
                    s.shipping_category, o.order_status, o.shipping_status, o.pay_status, 
                    o.carrier_bill_id, 
                    es.default_carrier_id
                FROM ecshop.ecs_order_info o
                LEFT JOIN romeo.order_shipment os ON cast(os.order_id as decimal(15,0)) = o.order_id
                LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
                LEFT JOIN ecshop.ecs_shipping es ON es.shipping_id = s.shipment_type_id
                WHERE ". party_sql('o.party_id', $_SESSION['party_id']) ." AND os.shipment_id = '{$item['shipment_id']}'
            ";
            $order_list = $db->getAll($sql);
            foreach ($order_list as $key => $order) {
                if ($order_sn == $order['order_sn']) {
                    $order_list[$key]['order_status_name'] = get_order_status($order['order_status']);
                    $order_list[$key]['shipping_status_name'] = get_shipping_status($order['shipping_status']);
                    $order_list[$key]['pay_status_name'] = get_pay_status($order['pay_status']);
                    $order_list[$key]['type_name'] = $_CFG['adminvars']['shipping_category'][$order['shipping_category']];
                    $order_lists[] = $order_list[$key];
                    $order_id = $order['order_id'];
                } else {
                    $order_sn_relation[$order['order_id']] = $order['order_sn'];
                    unset($order_list[$key]);
                }
            }
        }
        $smarty->assign('order_sn_relation', $order_sn_relation);
        $smarty->assign('order_lists', $order_lists);
        $smarty->assign('order_id', $order_id);
        $smarty->assign('flag', 'search');
        break;
    case 'edit' :
        $order_id = intval($_REQUEST['order_id']);
        $condition = "";
        $add = trim($_REQUEST['add']);
        if($admin_name!='zhyan'){
        	if(check_admin_priv('third_party_warehouse') && ($_SESSION['action_list']!='all')){
        	sys_msg("第三方仓库不允许追加快递单号");
            }
        }
    	
        if ($add == 'add') {
            $sql = "SELECT shipping_status 
                FROM ecshop.ecs_order_info 
                WHERE ". party_sql('party_id', $_SESSION['party_id']) ." AND order_id = '{$order_id}' 
            ";
            $shipping_status = $db->getOne($sql);
            $smarty->assign('flag', 'add_edit');
        } else {
            $shipment_id = trim($_REQUEST['shipment_id']);
            $condition = "  AND s.shipment_id = '{$shipment_id}' ";
            $shipping_status = intval($_REQUEST['shipping_status']);
            $smarty->assign('flag', 'edit');
        }
        $shipping_status_list = array(1, 2, 3);
        if (in_array($shipping_status, $shipping_status_list)) {
            //显示
            $sql ="SELECT o.order_id, o.order_sn, s.tracking_number, s.shipment_id, es.shipping_name, s.shipment_type_id, s.shipping_category, 
                    o.order_status, o.shipping_status, o.pay_status, 
                    o.carrier_bill_id, 
                    es.default_carrier_id
                FROM ecshop.ecs_order_info o
                LEFT JOIN romeo.order_shipment os ON os.order_id = cast(o.order_id as char(15))
                LEFT JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
                LEFT JOIN ecshop.ecs_shipping es ON es.shipping_id = s.shipment_type_id
                WHERE ". party_sql('o.party_id', $_SESSION['party_id']) ." AND o.order_id = '{$order_id}' 
                    AND o.shipping_status = '{$shipping_status}'
            ". $condition ;
            $order_list = $db->getRow($sql);
            $order_list['order_status_name'] = get_order_status($order_list['order_status']);
            $order_list['shipping_status_name'] = get_shipping_status($order_list['shipping_status']);
            $order_list['pay_status_name'] = get_pay_status($order_list['pay_status']);
        } else {
            sys_msg($message);
        }
	    // 第三方仓库的 快递
	    if(check_admin_priv('third_party_warehouse') && ($_SESSION['action_list']!='all')){
	    	$shipping_lists = get_third_ShippingList();
	    } else {
	    	$shipping_lists = getShippingTypes();
	    }
	    
        $smarty->assign('order_list', $order_list);
        $smarty->assign('shipping_lists', $shipping_lists);
        $smarty->assign('order_id', $order_id);
        break;
    case 'update' :
        $order_id = intval($_REQUEST['order_id']);
        $order_sn = trim($_REQUEST['order_sn']);
        $shipment_id = trim($_REQUEST['shipment_id']);
        $tracking_num = trim($_REQUEST['tracking_number']);
        $shipping_id = trim($_REQUEST['shipping_name']);
        $carrier_id = intval($_REQUEST['carrier_id']);
        $old_tracking_num = trim($_REQUEST['old_tracking_num']);
        //检查面单号 页面已经判断过了，无需2次判断 ljzhou 2013.06.06
        //check_tracking_number($shipping_id, $tracking_num);
        $sql = "SELECT order_id, order_status, shipping_status, pay_status 
            FROM ecshop.ecs_order_info 
            WHERE ". party_sql('party_id', $_SESSION['party_id']) ." AND order_id = '{$order_id}' 
        ";
        $order = $db->getRow($sql);
        $shipping_status_list = array(1, 2, 3);
        if (in_array($order['shipping_status'], $shipping_status_list)) {
            $shipment_list = array(
                'shipmentId' => $shipment_id, 
			    'partyId' => $_SESSION['party_id'],
			    'shipmentTypeId' => $shipping_id,
			    'carrierId' => $carrier_id,  
			    'trackingNumber' => $tracking_num,
			    'status' => null,  
	            'lastModifiedByUserLogin' => $_SESSION['admin_name'],
            );
            $handle = soap_get_client('ShipmentService');
            $result = $handle->updateShipment($shipment_list);
            orderActionLog(array('order_id'=>$order['order_id'], 'order_status'=>$order['order_status'], 
                'shipping_status'=>$order['shipping_status'], 'pay_status'=>$order['pay_status'], 
                'action_note'=>"面单号{$old_tracking_num}修改为：{$tracking_num}"));
        } else {
            sys_msg($message);
        }
        $smarty->assign('order_id', $order_id);
        $url = $_SERVER['PHP_SELF']. "?act=search&order_sn=". $order_sn;
        header("Location: {$url}"); 
        break;
    case 'add_insert' :
        $order_id = intval($_REQUEST['order_id']);
        $add_tracking_number = trim($_REQUEST['add_tracking_number']);
        $shipping_id = trim($_REQUEST['add_shipping_name']);
        $shipping_note = trim($_REQUEST['shipping_note']);
        $type = trim($_REQUEST['type']);
        $carrier_bill_id = intval($_REQUEST['carrier_bill_id']);
        $carrier_id = intval($_REQUEST['carrier_id']);
        //检查面单号 页面已经判断过了，无需2次判断,以后只维护ajax的ajax_check_tracking_number规则 ljzhou 2013.06.06
        //check_tracking_number($shipping_id, $add_tracking_number);
        $sql = "SELECT shipping_status, order_sn, order_id, order_status, pay_status
            FROM ecshop.ecs_order_info 
            WHERE ". party_sql('party_id', $_SESSION['party_id']) ." AND order_id = '{$order_id}' 
        ";
        $info = $db->getRow($sql);
        $shipping_status_list = array(1, 2, 3);
        if (in_array($info['shipping_status'], $shipping_status_list)) {
            $shipment = array(
                'orderId' => $order_id,
		        'partyId' => $_SESSION['party_id'],
			    'shipmentTypeId' => $shipping_id,
	            'carrierId' => $carrier_id, 
			    'trackingNumber' => $add_tracking_number,
		        'shippingCategory' => $type,   
			    'shippingCost' => '0',
			    'shippingNote' => $shipping_note,
		        'createdByUserLogin' => $_SESSION['admin_name'],
            );
            $handle = soap_get_client('ShipmentService');
            $result = $handle->createShipment_v2($shipment);
            $shipment_id = $result->return;
            if ($shipment_id) {
                orderActionLog(array('order_id'=>$info['order_id'], 'order_status'=>$info['order_status'], 
                'shipping_status'=>$info['shipping_status'], 'pay_status'=>$info['pay_status'], 
                'action_note'=>"追加面单号：{$add_tracking_number}"));
            }
        }
        $url = $_SERVER['PHP_SELF']. "?act=search&order_sn=". $info['order_sn'];
        header("Location: {$url}"); 
        break;
    case 'ajax':
        $shpping_id = intval($_POST['shipping_id']);
        $tracking_num = trim($_POST['tracking_number']);
        $sql = "SELECT default_carrier_id FROM ecshop.ecs_shipping WHERE shipping_id = '{$shpping_id}';
        ";
        $data['carrier_id'] = $db->getOne($sql);
        $sql_s = "SELECT shipment_id FROM romeo.shipment WHERE tracking_number = '{$tracking_num}'";
        $data['shipment_id'] = $db->getOne($sql_s);
        print_r(urldecode(json_encode($data)));
        exit();
        break;
}
$smarty->assign('message', $message);
$smarty->display('dispatch_edit.htm');


