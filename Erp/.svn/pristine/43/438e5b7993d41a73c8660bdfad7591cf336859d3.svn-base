<?php
/**
 * 收货入库
 */
define('IN_ECS', true);
require('includes/init.php');
admin_priv('ck_in_storage', 'wl_in_storage');
require_once('includes/lib_goods.php');
require_once('includes/lib_product_code.php');

$order_id = intval($_REQUEST['order_id']);
$rec_id = intval($_REQUEST['rec_id']);

$sql = "SELECT oi.order_id,oi.order_sn,oi.facility_id FROM ecshop.ecs_order_info oi " .
		" inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id " .
		" WHERE oi.order_id = {$order_id} AND og.rec_id = {$rec_id} AND " . party_sql('party_id');
$order = $db->getRow($sql, true);
if (empty($order)) {
    die("没有查到相关订单信息");
}

$sort_validity_list= array(
	'start_validity' => '生产日期',
	'end_validity' => '到期日期'
);
// 排序方式
$sort_validity =
	isset($_REQUEST['sort_validity']) && trim($_REQUEST['sort_validity'])
    ? $_REQUEST['sort_validity']
    : 'start_validity';    

if($sort_validity == 'start_validity') {
	$validity_info = "生产日期";
} else {
	$validity_info = "到期日期";
}

// 取得供应商名
$sql = "
    SELECT p.provider_name,g.is_maintain_warranty,og.goods_name,g.goods_id,og.goods_number,
    (select ifnull(sum(iid.quantity_on_hand_diff),0) from romeo.inventory_item_detail iid where convert(og.order_id using utf8) = iid.order_id and convert(og.rec_id using utf8) = iid.order_goods_id) as in_number
    FROM
    `ecshop`.`ecs_order_goods` og
    LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
    LEFT JOIN ecshop.ecs_batch_order_mapping om ON og.order_id = om.order_id
    LEFT JOIN ecshop.ecs_batch_order_info boi ON om.batch_order_id = boi.batch_order_id
    LEFT JOIN `ecshop`.`ecs_provider` p on p.provider_id = boi.provider_id
    WHERE og.order_id = {$order_id} and og.rec_id = {$rec_id}
    GROUP BY og.rec_id
";
$goods_info = $db->getRow($sql);
$is_maintain_warranty = $goods_info['is_maintain_warranty'];
$goods_count = $goods_info['goods_number'];
$goods_number = $goods_info['goods_number'];
$input_count = $goods_info['goods_number'] - $goods_info['in_number']; // 未入库数量
$goods_item_type = get_goods_item_type($goods_info['goods_id']);
if($goods_item_type == 'SERIALIZED') {
	$sql = "select ii.serial_number from romeo.inventory_item ii 
	left join romeo.inventory_item_detail iid ON ii.inventory_item_id = iid.inventory_item_id
    where iid.order_id = '{$order_id}' and iid.order_goods_id = '{$rec_id}' ";
    $serial_numbers = $db->getCol($sql);
    $serial_in_count = count($serial_numbers);
    for($i=0;$i<$goods_number-$serial_in_count;$i++) {
    	$serial_numbers[] = '';
    }
}

// 增加仓库名
$order['facility_name'] = facility_mapping($order['facility_id']);


$url = "sn_inputV3.php?order_id={$order_id}&rec_id={$rec_id}";
$smarty->assign('url', $url);
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('goods_info', $goods_info);
$smarty->assign('serial_numbers', $serial_numbers);
$smarty->assign('serial_length', count($serial_numbers));
$smarty->assign('is_maintain_warranty', $is_maintain_warranty);
$smarty->assign('sort_validity', $sort_validity);  // 日期录入列表
$smarty->assign('sort_validity_list', $sort_validity_list);  // 日期录入列表
$smarty->assign('validity_info', $validity_info);  // 日期显示方式
$smarty->assign('goods_count', $goods_count);
$smarty->assign('input_count', $input_count);
$smarty->assign('order', $order);
$smarty->assign('rec_id',$rec_id);
$smarty->assign('provider_name', $provider_name);
$smarty->assign('goods_item_type', $goods_item_type);
$smarty->assign('printers', get_serial_printers());
$smarty->display('oukooext/sn_inputV3.htm');

?>