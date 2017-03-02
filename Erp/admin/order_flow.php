<?php
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once('includes/lib_order.php');
include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

$action_user = $_SESSION['admin_name'];
$action_time = date("Y-m-d H:i:s");

//$order_id = intval($_REQUEST['order_id']);
//if(!$order_id) die('no order');

$order_sn = $_REQUEST['order_sn'];
$sql = "select * from {$ecs->table('order_info')} where order_sn = '$order_sn' LIMIT 1 ";
$order = $db->getRow($sql);
if(!$order) die('no order');
$action =  $_REQUEST['action'];
$order_id = $order['order_id'];
$action_array = array('order_confirm', 'dph', 'dc', 'shipping', 'user_confirm');
foreach($action_array as $action_item) {
    print $action_item." ok <br />";
    if($action_item == 'order_confirm') {
        $sql = "select support_cod from {$ecs->table('shipping')} where shipping_id = {$order['shipping_id']}";
        $support_cod = $db->getOne($sql);
        if(!$support_cod) {
            $sql = "UPDATE {$ecs->table('pay_log')} SET is_paid = '1' where order_id = {$order_id}";
            $db->query($sql);
            $sql = "update {$ecs->table('order_info')} set order_status = 1, pay_status = 2 where order_id = '$order_id' limit 1  ";
            $db->query($sql);
            orderActionLog(array('order_id' => $order['order_id'], 'order_status' => 1, 'pay_status' => 2, 'action_note'=>'用户支付操作'));
        } else {
            $sql = "update {$ecs->table('order_info')} set order_status = 1 where order_id = '$order_id' limit 1  ";
            $db->query($sql);
            orderActionLog(array('order_id' => $order['order_id'], 'order_status' => 1, 'shipping_status' => 0, 'pay_status' => 0, 'action_note'=>'订单确认，'));
        }
    }

    if($action_item == 'dph') {
//        $sql = "update {$ecs->table('order_info')} set shipping_status = 9 where order_id = '$order_id' limit 1  ";
//        $db->query($sql);
//        
//
//        orderActionLog(array('order_id' => $order['order_id'], 'order_status' => 1, 'shipping_status' => 9, ));
    }

    if($action_item == 'dc') {

        $sql = "update {$ecs->table('order_info')} set shipping_status = 8 where order_id = '$order_id' limit 1  ";
        $db->query($sql);
        orderActionLog(array('order_id' => $order['order_id'], 'shipping_status' => 8, ));
        // killed by Sinri 20160105
        // $sql = "select default_carrier_id  from {$ecs->table('shipping')} where shipping_id = {$order['shipping_id']} ";
        // $carrier_id = $db->getOne($sql);
        // $sql = "UPDATE {$ecs->table('carrier_bill')} SET carrier_id = '$carrier_id', bill_no = '{$order['order_sn']}' WHERE bill_id = '{$order['carrier_bill_id']}'";
        // $db->query($sql);
    }

    if($action_item == 'shipping') {
        $sql = "update {$ecs->table('order_info')} set shipping_time = UNIX_TIMESTAMP(), shipping_status = 1 where order_id = '$order_id' limit 1  ";
        $db->query($sql);
        orderActionLog(array('order_id' => $order['order_id'], 'shipping_status' => 1));
    }

    if($action_item == 'user_confirm') {
        $sql = "update {$ecs->table('order_info')} set shipping_status = 2 where order_id = '$order_id' limit 1  ";
        $db->query($sql);

        $sql = "INSERT INTO {$ecs->table('order_action')} (order_id, action_user, shipping_status, action_time, action_note) VALUES ('{$order['order_id']}', '{$_SESSION['admin_name']}', '2', NOW() , '用户确认收货')";
        $db->query($sql);
    }

    if($action == $action_item) break;
}




//$smarty->assign('back', $_SERVER['REQUEST_URI']);
//$smarty->assign('carriers', $carriers);
//$smarty->display('v3/order_edit.dwt');

