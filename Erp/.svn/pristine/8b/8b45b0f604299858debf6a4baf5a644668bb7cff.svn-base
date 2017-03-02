<?php
/**
 * 工单管理
 */
define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../function.php');
party_priv('65542');
admin_priv('dispatch_list_purchase', 'dispatch_list_customer_service');

$jjshouse_goods_id = intval($_REQUEST['jjshouse_goods_id']);

if ($jjshouse_goods_id) {
    $sql = "
select
    p.provider_name, d.price, d.submit_date, pog.goods_name, d.IMAGE_URL,
    d.DISPATCH_STATUS_ID, d.dispatch_sn, d.external_order_sn,
    (
    select min(dh.CREATED_STAMP) from 
    romeo.dispatch_status_history dh
    where 
    dh.dispatch_list_id = d.dispatch_list_id and
    STATUS = 'FINISHED'
    ) as finished_date
from
    romeo.dispatch_list d 
    inner join ecshop.ecs_order_goods pog on d.order_goods_id = cast(pog.rec_id as char)
    inner join ecshop.ecs_order_goods sog on pog.goods_id = sog.goods_id and pog.style_id = sog.style_id
    inner join ecshop.order_goods_attribute oa on sog.rec_id = oa.order_goods_id
    left join ecshop.ecs_provider p on p.provider_id = cast(d.provider_id as unsigned)
where 
    oa.name = 'goods_id'
    and oa.value='{$jjshouse_goods_id}' and
    d.dispatch_status_id = 'FINISHED'
group by 
    /*d.dispatch_sn*/
    d.submit_date DESC
    ";
    
    $list = $slave_db->getAll($sql);
    if ($list) {
        $total_price = 0;
        $total_time_cost = 0;
        foreach ($list as &$item) {
            $total_price += $item['price'];
            $time_cost = strtotime($item['finished_date']) - strtotime($item['submit_date']);
            $item['time_cost'] = $time_cost;
            $total_time_cost += $time_cost;
            $item['time_cost_str'] = convert_interval($time_cost);
        }
        
        $count = count($list);
        
        $avg_price = $total_price / $count;
        $avg_time_cost = $total_time_cost / $count;
        
        
        foreach ($list as &$item) {
            if ($item['price'] > $avg_price) {
                $item['price_alert'] = true;
            }
            
            if ($item['time_cost'] > $avg_time_cost) {
                $item['time_cost_alert'] = true;
            }
        }
        
        
        $smarty->assign('count', $count);
        $smarty->assign("avg_price", round($avg_price, 2));
        $smarty->assign("avg_time_cost_str", convert_interval($avg_time_cost));
        $smarty->assign("list", $list);
    }
}


$smarty->display('dispatchlist/stat.htm');



