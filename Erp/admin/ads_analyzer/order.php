<?php
/**
 * 显示不同用户的订单统计
 */
define('IN_ECS', true);

require_once('../includes/init.php');

admin_priv("jjshouse_ads_analyzer");

$act = $_REQUEST['act'];
if ($act == 'query') {
    $adsdb = new cls_mysql($ads_db_host, $ads_db_user, $ads_db_pass, $ads_db_name);
    
    $row = $_REQUEST['row'];
    $begin = $row['begin'];
    $end = $row['end'];
    
    $sql = "
        select 
        vu.user_type, 
        count(*)  as user_count,
        sum(if(ot.order_sn is null, 0, 1)) as order_count,
        sum(if(o.pay_status = 2, 1, 0)) as paied_order_count,
        sum(if(o.pay_status != 2 and (o.pay_status is not null), 1, 0)) as no_pay_order_count
        from visited_user vu
        left join order_track ot on vu.track_id = ot.track_id
        left join jjshouse.order_info o on ot.order_sn = o.order_sn
        where vu.first_visit_time >= '{$begin}' and vu.first_visit_time < '{$end}' 
        group by vu.user_type";
    $user_type_count = $adsdb->getAll($sql);

    $smarty->assign('user_type_count', $user_type_count);
}

$smarty->display("ads_analyzer/order.htm");