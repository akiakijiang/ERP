<?php
/**
 * 工单管理
 */
define('IN_ECS', true);

require_once('../includes/init.php');
require_once('../../RomeoApi/lib_dispatchlist.php');
require_once('../function.php');

admin_priv('dispatch_list_purchase', 'dispatch_list_customer_service', 'order_edit');

$dispatchListId = $_REQUEST['dispatchListId'];
if (!$dispatchListId) {
    print "no dispatchId";
    exit();
}

$dispatchList = getDispatchList($dispatchListId);
$attributes = getDispatchListAttributes($dispatchListId);

$orderTime = $db->getOne("select order_time from {$ecs->table('order_info')} where order_id = '{$dispatchList->orderId}' ");

$sql = "select attr_value from ecshop.order_attribute 
        where attr_name = 'important_day' and 
        order_id = {$dispatchList->orderId} 
        limit 1";
$importantDay = $db->getOne($sql);

$imgAttributes = array();
foreach ($attributes as $name => $value) {
    if (preg_match('/.*\d_original$/', $name)) {
        $imgAttributes[] = $value;
    }
}

// 以前存的工单，图片的属性不太一样
if (!$imgAttributes) {
    foreach ($attributes as $name => $value) {
        if (preg_match('/.*\d_o$/', $name)) {
            $imgAttributes[] = $value;
        }
    }
}

$providers = getProviders();
$providerName = $providers[$dispatchList->providerId]['provider_name'];

if ($dispatchList->dueDate) {
    $dispatchList->dueDate = date("Y-m-d", strtotime($dispatchList->dueDate));
}

// 分析出以前的goods id
if (preg_match('/g(\d+)/', $dispatchList->goodsSn, $matches)) {
    $goodsId = $matches[1];
    $smarty->assign('goodsId', $goodsId);
}

// 工单的品类
$sql = "select
c.cat_name
from
ecshop.ecs_order_goods og 
left join ecshop.ecs_goods g on og.goods_id = g.goods_id
left join ecshop.ecs_category c on g.cat_id = c.cat_id
where og.rec_id = '{$dispatchList->orderGoodsId}'
";
$cat_name = $db->getOne($sql);



$attributes['note'] = str_replace("\n", '<br />', $attributes['note']);

$smarty->assign('providerName', $providerName);
$smarty->assign('orderTime', $orderTime);
$smarty->assign('dispatchList', $dispatchList);
$smarty->assign('attributes', $attributes);
$smarty->assign('imgAttributes', $imgAttributes);
$smarty->assign('importantDay', $importantDay);
$smarty->assign('cat_name', $cat_name);

$smarty->display('dispatchlist/print.htm');

