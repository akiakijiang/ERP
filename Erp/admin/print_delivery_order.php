<?php
/**
 * 库存清单打印
 */
define('IN_ECS', true);
require('includes/init.php');

admin_priv('cg_delivery_order', 'purchase_order');
require("function.php");
require_once("includes/lib_order.php");

$csv = $_REQUEST['csv'];
$index_list = empty($_REQUEST['index'])?array():$_REQUEST['index'];

foreach ($index_list as $index) {
    $goods_number = $_REQUEST["goods_number-{$index}"];
    $goods_number_csv = $_REQUEST["goods_number_csv-{$index}"];
    $storage_number_csv = $_REQUEST["storage_number_csv-{$index}"];
    $customized = $_REQUEST["customized-{$index}"];
    $customized_csv = $_REQUEST["customized_csv-{$index}"];
    $shipping = $_REQUEST["shipping-{$index}"];
    $shipping_csv = $_REQUEST["shipping_csv-{$index}"];
    $action_notes = $_REQUEST["action_notes-{$index}"];
    // ncchen 090205 导出csv 类别,品牌型号,颜色,是否定制,数量,备注
    $goods_id = $_REQUEST["goods_id-{$index}"];
    $style_id = $_REQUEST["style_id-{$index}"];
    $sql = "
			SELECT c.cat_name, g.goods_name, g.sku, g.goods_party_id, IF(gs.goods_color = '', s.color, gs.goods_color) AS color
			FROM {$ecs->table('goods')} g 
				LEFT JOIN {$ecs->table('category')} c ON c.cat_id = g.cat_id 
				LEFT JOIN {$ecs->table('goods_style')} gs ON g.goods_id = gs.goods_id AND gs.style_id = $style_id 
				LEFT JOIN {$ecs->table('style')} s ON s.style_id = gs.style_id
			WHERE g.goods_id = $goods_id 
			";
    $goods = $db->getRow($sql);
    if (!$goods['color']) {
        $goods['color'] = '无';
    }
    $goods["index"] = $index;
    $goods["goods_number"] = $goods_number;
    $goods["goods_number_csv"] = $goods_number_csv;
    $goods["storage_number_csv"] = $storage_number_csv;
    $goods["customized"] = $customized;
    $goods["customized_csv"] = $customized_csv;
    $goods["shipping"] = $shipping;
    $goods["shipping_csv"] = $shipping_csv;
    $goods["action_notes"] = $action_notes;
    $goods_list[] = $goods;
}
$smarty->assign('goods_list', $goods_list);

if ($csv !== null) {
    admin_priv('5cg_delivery_order_csv');
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","采购清单{$extra_info}") . ".csv");
    $out = $smarty->fetch('oukooext/print_delivery_order_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} else {
    $smarty->display('oukooext/print_delivery_order.htm');
}
?>