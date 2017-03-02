<?php

/**
 * 返修操作
 * 
 * @author ncchen
 * @copyright ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
//admin_priv('warranty_action'); // 返修申请权限
require_once('includes/lib_service.php');

$datetime = date("Y-m-d H:i:s");
/*
* 新建返修申请
*/
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST))
{
    $action = $_POST['act'];
    $service_id = $_POST['service_id']; // 取得返修售后
    if ($action == 'confirm') {
        $goods_amount = $_POST['goods_amount_1'];
        $shipping_fee = $_POST['shipping_fee_1'];
        $misc_fee = $_POST['misc_fee_1'];
        $pack_fee = $_POST['pack_fee_1'];
        $amount = $goods_amount + $shipping_fee + $misc_fee + $pack_fee;
        $pay_status = "N";
        //添加日志
        $log_note = " {$_SESSION['admin_name']} 于 $datetime 联系用户，确认付款维修 ";
        // 收款为0
        if ($amount < 0.01) {
            $log_note = " {$_SESSION['admin_name']} 于 $datetime 联系用户，确认免费保修 ";
            $pay_status = "Y";
        }
        $sql = "INSERT INTO service_warranty_pay
            (goods_amount, shipping_fee, misc_fee, pack_fee, total_amount, service_id, pay_status) 
            VALUES ('{$goods_amount}', '{$shipping_fee}', '{$misc_fee}',
             '{$pack_fee}', '{$amount}', '{$service_id}', '{$pay_status}' )";
        $db->query($sql);
        //修改售后状态
        if ($goods_amount < 0.01) {
            $warranty_check_status = "";
        } else {
            $warranty_check_status = "warranty_check_status = 24, ";
        }
        $sql = "UPDATE service SET {$warranty_check_status} service_call_status = 2 
            WHERE service_id = '{$service_id}' LIMIT 1";
        $db->query($sql);
        
        $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' LIMIT 1 ";
        $service = $db->getRow($sql);
        $service['log_note'] = $log_note;
        $service['log_type'] = 'CUSTOMER_SERVICE';
        service_log($service);
    } else if ($action == 'pay') {
        $sql = "UPDATE service_warranty_pay
            SET pay_status = 'Y'
            WHERE service_id = '{$service_id}'
            ";
        $db->query($sql);
        $log_note = " {$_SESSION['admin_name']} 于 $datetime 收到用户返修付款 ";
        $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' LIMIT 1 ";
        $service = $db->getRow($sql);
        $service['log_note'] = $log_note;
        $service['log_type'] = 'FINANCE';
        service_log($service);
    }
}
else
{
    $action = $_GET['act'];
    // 该订单未执行的退款单
    $service_id = $_GET['service_id']; // 取得返修售后
    if ($action == 'refuse') {
        // 更新状态，用户放弃维修
        $sql = "UPDATE service SET warranty_check_status = 28, service_call_status = 2 
            WHERE service_id = '{$service_id}' LIMIT 1";
        $db->query($sql);
        
        $log_note = " {$_SESSION['admin_name']} 于 $datetime 联系用户，放弃维修 ";
        $sql = " SELECT * FROM service WHERE service_id = '{$service_id}' LIMIT 1 ";
        $service = $db->getRow($sql);
        $service['log_note'] = $log_note;
        $service['log_type'] = 'CUSTOMER_SERVICE';
        service_log($service);
    }
}

$back = $_REQUEST['back'];
Header("Location:{$back}");