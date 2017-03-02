<?php
/**
 * 用户订购记录查询
 * $Id: search_user_order_info.php 61886 2014-02-19 09:54:23Z qxu $
 * @author yzhang@leqee.com
 * @copyright 2010.12 leqee.com
 */

define('IN_ECS', true);
require ('includes/init.php');
admin_priv('customer_service_manage_order');
require ("function.php");
include_once ('includes/lib_order.php');

// 请求
$request = isset($_REQUEST['request']) && in_array($_REQUEST['request'], array(
    'ajax'
)) ? $_REQUEST['request'] : null;

$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array(
    'search'
)) ? $_REQUEST['act'] : null;

$TAOBAO_USER_ID = isset($_REQUEST['TAOBAO_USER_ID']) ? trim($_REQUEST['TAOBAO_USER_ID']) : '';
// {{{ stripslashes
$o = array(
    $TAOBAO_USER_ID
);
array_walk_recursive($o, create_function('&$item', '$item = stripslashes($item);'));
$TAOBAO_USER_ID = $o[0];
// }}}


/*
 * 处理ajax请求
 */
if ($request == 'ajax') {
    switch ($act) {
        case 'search':
            $TAOBAO_USER_ID_quote = $db->quote($TAOBAO_USER_ID);
//            $sql = "SELECT eoi.order_id FROM ecshop.order_attribute oa inner join ecshop.ecs_order_info eoi on eoi.order_id = oa.order_id WHERE oa.attr_name = 'TAOBAO_USER_ID' and oa.attr_value = '$TAOBAO_USER_ID_quote' and eoi. party_id = '{$_SESSION['party_id']}'";
            $sql = "SELECT order_id FROM ecshop.order_attribute WHERE attr_name = 'TAOBAO_USER_ID' and attr_value = '$TAOBAO_USER_ID_quote' ";
            $orders_id = $db->getAll($sql);
            if (empty($orders_id)) {
                $r = array(
                    'data' => '系统找不到该淘宝ID。', 
                    'code' => '1'
                );
            } else {
                $_orders_id = array();
                foreach ($orders_id as $v) {
                    $_orders_id[] = $v['order_id'];
                }
                $sql = "select order_id,order_sn,CONCAT('[',ifnull(r1.region_name,''),'][',ifnull(r2.region_name,''),'][',ifnull(r3.region_name,''),']') province_city_district,order_status,address,order_time,order_amount,shipping_fee,bonus,consignee
						from ecshop.ecs_order_info eoi
						left join ecshop.ecs_region r1 on eoi.province = r1.region_id
						left join ecshop.ecs_region r2 on eoi.city = r2.region_id
						left join ecshop.ecs_region r3 on eoi.district = r3.region_id " .
                		"WHERE order_id IN ('" . join("', '", $_orders_id) . "') ORDER BY order_id DESC ";
                
                $orders = $db->getAll($sql);
                if (empty($orders)) {
                    $r = array(
                        'data' => '系统找不到该淘宝ID对应的订单信息。', 
                        'code' => '2'
                    );
                } else {
                    $rdata = array();
                    foreach ($orders as $k => $order) {
                        // {{{ 查询省、城市
//                        $provinceSQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['province']}'";
//                        $order['province'] = $db->getOne($provinceSQL);
//                        
//                        $citySQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['city']}'";
//                        $order['city'] = $db->getOne($citySQL);
//                        
//                        $districtSQL = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['district']}'";
//                        $order['district'] = $db->getOne($districtSQL);
//                        // }}}
//                        $order['province_city_district'] = ($order['province'] ? '[' . $order['province'] . ']' : '') . ($order['city'] ? '[' . $order['city'] . ']' : '') . ($order['district'] ? '[' . $order['district'] . ']' : '');
                        $sql = "SELECT * FROM ecshop.ecs_order_goods WHERE order_id = '{$order['order_id']}' ";
                        $order_goods = $db->getAll($sql);
                        $_goods = array();
                        $_goods[] = '<table border="1" style="border-collapse:collapse;border-color:#666;">';
                        foreach ($order_goods as $goods) {
                            $_goods[] = "<tr><td>{$goods['goods_name']}</td><td>{$goods['goods_number']}</td></tr>";
                        }
                        $_goods[] = '</table>';
                        $_goods = join("", $_goods);
                        $kk = $k + 1;
                        $order_status = get_order_status($order['order_status']);
                        $rdata[] = <<<aaa
                <tr>
                	<td>{$kk}</td>
                	<td><a href="order_edit.php?order_id={$order['order_id']}" target="_blank">{$order['order_sn']}</a></td>
                	<td>{$order['order_time']}</td>
                	<td>{$order_status}</td>
                	<td>{$order['order_amount']}</td>
                	<td>{$order['shipping_fee']}</td>
                	<td>{$order['bonus']}</td>
                	<td>{$order['consignee']}</td>
                	<td>{$order['province_city_district']}{$order['address']}</td>
                	<td>{$_goods}</td>
                </tr>
aaa;
                    }
                    
                    $r = array(
                        'data' => join("", $rdata), 
                        'code' => 0
                    );
                }
            }
            echo json_encode($r);
            break;
    
    }
    
    die();
}

// 显示界面
$smarty->display('oukooext/search_user_order_info.htm');
