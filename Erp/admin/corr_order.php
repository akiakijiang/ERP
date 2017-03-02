<?php
define('IN_ECS', true );
require_once('includes/init.php');
require_once("function.php");
party_priv('65540');
admin_priv('kf_corr_order');
$corr_order_id = $_REQUEST['corr_order_id'];
$csv = $_REQUEST['csv'];
$all = $_REQUEST['all'];
if ($corr_order_id  != null) {
    $sql = "
        update ecshop.ecs_corr_order 
        set 
            status = 'FINISHED', 
            action_user = '{$_SESSION['admin_name']}',
            last_update_stamp = now()
        where corr_order_id = {$corr_order_id} ";

        $db->query($sql);
}
if ($all != null) {
    $sql = "
        update ecshop.ecs_corr_order 
        set 
            status = 'FINISHED', 
            action_user = '{$_SESSION['admin_name']}',
            last_update_stamp = now()";
        $db->query($sql);    
}

if ($csv != null) {
    admin_priv('3kf_corr_order_csv');
    $sql = "
        select co.taobao_order_sn, co.consignee, ifnull(co.mobile, co.tel) as mobile, 
               co.province, co.city, co.district, co.address, co.goods_amount, s.tracking_number ,
               concat(co.goods_name, '*', co.goods_number) as goods, co.postscript, 
               '' as payable_coupon, '' as receivable_coupon, '' as user_note, '' as note
        from ecshop.ecs_corr_order co 
        inner join ecshop.ecs_order_info oi on co.taobao_order_sn = oi.taobao_order_sn 
        left join romeo.order_shipment os on convert(oi.order_id using utf8) = os.order_id 
        left join romeo.shipment s on os.shipment_id = s.shipment_id
        where co.status = 'INIT'
        group by co.corr_order_id
    ";
    $corr_order_list = $db->getAll($sql);
    foreach ($corr_order_list as $key => $corr_order) {
        $postscript_arr = explode("】", $corr_order[postscript]);
        $ss =  explode(":", $postscript_arr[0]);
        $user_note_arr = explode(":", $postscript_arr[0]);
        $note = explode(":", $postscript_arr[1]);
        $payable_coupon = explode(":", $postscript_arr[2]);
        $receivable_coupon = explode(":", $postscript_arr[3]);
        $corr_order_list[$key][user_note] = $user_note_arr[1];
        $corr_order_list[$key][note] = $note[1];
        $corr_order_list[$key][payable_coupon] = $payable_coupon[1];
        $corr_order_list[$key][receivable_coupon] = $receivable_coupon [1];
    }
    $smarty->assign('corr_order_list', $corr_order_list);
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","多美滋cod订单修改") . ".csv");
    $out = $smarty->fetch('oukooext/corr_order_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
}
  $sql = "
        select oi.order_id, co.corr_order_id, co.taobao_order_sn, co.created_stamp, 
               r1.region_name as old_province, co.province as new_province,
               r2.region_name as old_city, co.city as new_city, 
               r3.region_name as old_district, co.district as new_district, 
               oi.address as old_address, co.address as new_address, 
               oi.zipcode as old_zipcode, co.zipcode as new_zip_code, 
               oi.mobile as old_mobile, co.mobile as new_mobile,
               oi.tel as old_tel, co.tel as new_tel,
               oi.order_amount as old_order_amount, co.order_amount as new_order_amount,
               oi.goods_amount as old_goods_amount, co.goods_amount as new_goods_amount, 
               oi.shipping_fee as old_shipping_fee, co.shipping_fee as new_shipping_fee,
               oi.bonus as old_bonus, co.bonus as new_bonus,
               co.goods_name as group_goods_name , co.goods_number as group_goods_number,
               sum(og.goods_id) as old_goods, sum(gi.goods_id)*co.goods_number as new_goods,
               group_concat(concat(og.goods_name, ' × ', cast(og.goods_number as char)) separator '<br>') as old_goods_item,
               group_concat(concat(gi.goods_name, ' × ', cast(gi.goods_number * co.goods_number as char)) separator '<br>') as new_goods_item
        from ecshop.ecs_corr_order co 
        inner join ecshop.ecs_order_info oi on co.taobao_order_sn = oi.taobao_order_sn 
        left join ecshop.ecs_order_goods og on oi.order_id = og.order_id
        left join ecshop.distribution_group_goods g on co.goods_id = g.code
        left join ecshop.distribution_group_goods_item gi on g.group_id = gi.group_id
        left join ecshop.ecs_region r1 on oi.province = r1.region_id 
        left join ecshop.ecs_region r2 on oi.city = r2.region_id 
        left join ecshop.ecs_region r3 on oi.district = r3.region_id 
        where co.status = 'INIT'
        group by co.corr_order_id";
        
     $orders = $db->getAll($sql); 
     $smarty->assign('orders', $orders);
     $smarty->display('oukooext/corr_order.htm');


    
?>
