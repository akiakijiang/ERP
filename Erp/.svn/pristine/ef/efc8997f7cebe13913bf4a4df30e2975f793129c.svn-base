<?php

/**
 * 串号产品跟踪
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');

// 请求
$act = 
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('filter', 'search')) 
    ? $_REQUEST['act'] 
    : null ;
    
switch ($act) {
    case 'search' : 
        $serial_number = isset($_REQUEST['serial_number']) ? trim($_REQUEST['serial_number']) : false ;
        if (empty($serial_number)) {
            $smarty->assign('message', '请输入串号查询');
            break;
        }
      
        // 查询出入库的ERP记录
        $sql = "
            SELECT
                iid.order_goods_id, ii.serial_number, iid.created_stamp,iid.quantity_on_hand_diff, 
                o.order_sn, o.province, o.city, o.district, o.address, o.consignee,
                o.mobile, o.tel, o.shipping_time,og.goods_name,
                u.user_name, u.user_id
            FROM 
                {$ecs->table('order_info')} AS o  
                LEFT JOIN {$ecs->table('order_goods')} AS og ON og.order_id = o.order_id
                LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id
                LEFT JOIN {$ecs->table('users')} AS u ON u.user_id = o.user_id 
                LEFT JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = og.rec_id
                LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
            WHERE ii.serial_number = '{$serial_number}'
            GROUP by og.rec_id
            ORDER BY iid.created_stamp
        ";
//        pp($sql);
        $items = $db->getAll($sql);
        if (empty($items)) {
            $smarty->assign('message', '没有该串号的出入库记录');
            break;
        }
        $smarty->assign('items', $items);
    break;
}


$smarty->display('distributor/distribution_product_track.htm');

