<?php

define('IN_ECS', true);
require('../includes/init.php');
require_once('../function.php');
//admin_priv('customer_service_manage_order');


//require(ROOT_PATH . 'includes/cls_mssql.php');
//$call_center_db = new cls_mssql($call_center_db_host, $call_center_db_user, $call_center_db_pass, $call_center_db_name);

//$sql = "SELECT   Name   From   sysobjects";
//pp($call_center_db->getAll($sql));

$tel = $_REQUEST['callerid'];
// 列出销售订单、销售退回订单（-t订单）、销售换货订单 （-h订单）、补寄订单（-b）
$sql_order_type = " order_type_id IN ('SALE', 'RMA_RETURN', 'RMA_EXCHANGE', 'SHIP_ONLY') ";
if ($tel == null) {
    die("非法参数");
}
$sql = "
    SELECT * FROM {$ecs->table('order_info')} o, {$ecs->table('users')} u
    WHERE
       {$sql_order_type}
        AND o.user_id = u.user_id
        AND o.tel = '{$tel}'
    LIMIT 1
";
$user = $db->getRow($sql);


if ($user == null) {
    $sql = "
        SELECT * FROM {$ecs->table('order_info')} o, {$ecs->table('users')} u
        WHERE
            {$sql_order_type}
            AND o.user_id = u.user_id
            AND o.mobile = '{$tel}'
        LIMIT 1
    ";
    $user = $db->getRow($sql);
}

if ($user != null) {
    $sql = "
        SELECT * FROM {$ecs->table('order_info')}
        WHERE 
          {$sql_order_type}
          AND user_id = '{$user['user_id']}'
        ORDER BY order_time DESC
    ";
    $orders = $db->getAll($sql);

    $conditions_call_in =array();
    $conditions_call_out =array();
    foreach ($orders AS $order) {
        $order['mobile_formatted'] = make_semiangle($order['mobile']);
        $order['tel_formatted'] = make_semiangle($order['tel']);
        if ($order['mobile_formatted']) {
            $conditions_call_in[] = " CallerID = '{$order['mobile_formatted']}' ";
            $conditions_call_out[] = " PhoneNO = '{$order['mobile_formatted']}' ";
            $conditions_call_out[] = " PhoneNO = '0{$order['mobile_formatted']}' ";
            $conditions_call_out[] = " PhoneNO = '17951,0{$order['mobile_formatted']}' ";
        }
        if ($order['tel_formatted']) {
            $conditions_call_in[] = " CallerID = '{$order['tel_formatted']}' ";
            $conditions_call_out[] = " PhoneNO = '{$order['tel_formatted']}' ";
            $conditions_call_out[] = " PhoneNO = '0{$order['tel_formatted']}' ";
            $conditions_call_out[] = " PhoneNO = '17951,0{$order['tel_formatted']}' ";
        }
    }
    $conditions_call_in_sql = join(' OR ', $conditions_call_in);
    $conditions_call_out_sql = join(' OR ', $conditions_call_out);
    if ($conditions_call_in_sql) {
        $sql = "SELECT COUNT(*) FROM call_log WHERE $conditions_call_in_sql ";
        //$user['call_in_times'] = $db->getOne($sql);
    }
    if ($conditions_call_out_sql) {
        $sql = "SELECT COUNT(*) FROM call_log WHERE $conditions_call_out_sql " ;
        //$user['call_out_times'] = $db->getOne($sql);
    }
}




$smarty->assign('user', $user);
$smarty->assign('orders', $orders);

$smarty->display('call_center/index.htm');
?>