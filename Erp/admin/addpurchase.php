<?php
/**
 * 增加采购订单下商品的库存跟踪
 * Enter description here ...
 * @var unknown_type
 */
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
admin_priv('customer_service_manage_order', 'order_view');
$order_sn = $_REQUEST['order_sn'];

if ($order_sn != NULL){
	$sql = "SELECT order_id FROM ecs_order_info WHERE order_sn = '$order_sn'";
	$order_id = $db->getOne($sql);
	//echo $order_id;
	$sqlg = "SELECT  goods_name, product_id, order_id
	FROM ecshop.ecs_order_goods og
	LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id
	AND og.style_id = pm.ecs_style_id
	WHERE order_id = $order_id";
	$goods_id = $db->getAll($sqlg);
//	pp($goods_id);
}

if(is_array($goods_id) && $order_id != NULL){
	$inventory_list = array ();
	foreach ($goods_id as $key => $goods_id_list){
		$goods = $goods_id[$key]['product_id'];
		$goods_name = $goods_id[$key]['goods_name'];
		if($goods != NULL){
			$sqlq = "SELECT QUANTITY_ON_HAND_DIFF, o.ORDER_ID, status_id, product_id, order_sn
			FROM romeo.`inventory_item_detail` id
			LEFT JOIN romeo.inventory_item ii ON id.inventory_item_id = ii.inventory_item_id
			LEFT JOIN ecshop.ecs_order_info o ON o.order_id = id.order_id
			WHERE  product_id = $goods";
		    $inventory_list[$goods_name]= $db->getAll($sqlq);
	
		}
	}
}
//pp($inventory_list);
	
	$smarty->assign('inventory_list', $inventory_list);
	$smarty->display('oukooext/addpurchase.html');
?>