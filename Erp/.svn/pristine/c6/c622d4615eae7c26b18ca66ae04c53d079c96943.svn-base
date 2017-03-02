<?php
/**
 * $Author: Zandy $
 * $Date: 2007-05-15 16:52:17 +0800 (星期二, 15 五月 2007) $
 * $Id$
 * @see : ecshop\admin\includes\inc_menu.php
 * @see : ecshop\languages\zh_cn\admin\common.php
 * @see : ecshop\languages\zh_cn\admin\priv_action.php
*/
if (!isset($_REQUEST['old']) || !$_REQUEST['old']) {
	include('order_edit.php');
  die();
}











define('IN_ECS', true);
require('includes/init.php');
require('includes/lib_order.php');
// require('includes/lib_order_mixed_status.php');
require_once("function.php");
admin_priv('order_view');
$order_id = $_REQUEST["order_id"];
if ($order_id === null) {
	die("没有order_id");
}



$order = getOrderInfo($order_id);

$goodsList = $order['goods_list'];
foreach($goodsList AS $goodsKey => $goods){
	 // 获取商品退换货入库的数量
    $sql = "SELECT SUM(it.quantity_on_hand) FROM ecshop.order_relation orl
    		INNER JOIN ecshop.ecs_order_info oi ON oi.order_id = orl.order_id AND oi.order_type_id = 'RMA_RETURN'
    		INNER JOIN ecshop.ecs_order_goods og ON og.order_id = oi.order_id AND og.goods_id = {$goods['goods_id']} AND og.style_id = {$goods['style_id']}
    		INNER JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id AND iid.QUANTITY_ON_HAND_DIFF > 0
    		INNER JOIN romeo.inventory_transaction it ON iid.inventory_transaction_id = it.inventory_transaction_id
    			AND it.TO_STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED','INV_STTS_DEFECTIVE')
    		WHERE orl.parent_order_id = {$order_id}";
    $returned_number = $db->getOne($sql); 
    if(empty($returned_number)){
    	$returned_number = 0;
    }
    $goodsList[$goodsKey]['returned_number'] = $returned_number;
}
$order['goods_list'] = $goodsList;

//销售订单增值税发票
$sql = "select * from order_vat_invoice where order_id = '{$order['order_id']}' ";
$vat_invoice = $db->getRow($sql);
$order['have_vat_invoice'] = $vat_invoice ? true : false;
$order['vat_invoice'] = $vat_invoice;

// 订单的组织名称
$order['party_name'] = party_mapping($order['party_id']);

$order['facility_name'] = facility_mapping($order['facility_id']);
//pp($order);

//获得订单状态的历史数据

$order_mixed_status_history = array(); //get_order_mixed_status_history($order_id);
$smarty->assign('order_mixed_status_history', $order_mixed_status_history);
$smarty->assign('order_mixed_status_mapping', $order_mixed_status_mapping);

if ($order['sub_orders']) {
	$smarty->assign("orders", $order['sub_orders']);	
} else {
	$smarty->assign("orders", array($order));
}
//显示b2c金额，c2c金额
$b2c_amount = 0;
$c2c_amount = 0;
$goods_amount = 0;
if (!empty($order['goods_list']) && is_array($order['goods_list'])) {
    foreach($order['goods_list'] as $goods){
    	if($goods['order_types'][0] == 'B2C'){
		    $goods_amount +=  $goods['goods_price'] * $goods['goods_number'];
	    }
    }
}
$b2c_amount = $goods_amount + $order['pack_fee'] + $order['bonus'];
$shipping_amount = $order['shipping_fee'];
//EMS、同城的运费不计入运费应收里面
if ($shipping_amount != 0 && ($order['shipping_id'] == 36 || $order['shipping_id'] == 47 || $order['shipping_id'] == 48 || $order['shipping_id'] == 51)) {
	$shipping_amount = 0;
}
if($b2c_amount <= 0){
	$b2c_amount = 0;
}
$c2c_amount = $order['order_amount'] - $b2c_amount - $shipping_amount;

// 获得屏蔽号码
$order_mask = $order;
convert_mask_phone($order_mask, 'get');
$smarty->assign('order_mask', $order_mask);

$smarty->assign("order", $order);
$smarty->assign('b2c_amount',$b2c_amount);
$smarty->assign('c2c_amount',$c2c_amount);
$smarty->assign('shipping_amount',$shipping_amount);
//pp($order);
$smarty->display("oukooext/detail_info.htm");
?>
