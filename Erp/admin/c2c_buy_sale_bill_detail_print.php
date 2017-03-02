<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('finance_order', 'purchase_order');
require("function.php");

$type = $_REQUEST['type'];
$bill_id = intval($_REQUEST['bill_id']);
if (empty($bill_id)) {
	die("非法输入");
}

$purchase_bill_id = $bill_id;
$bill_type = $_REQUEST['bill_type'];
if ($bill_type == 'purchase') {
	$purchase_bill_id = $bill_id;
	$sql = "
	    SELECT date,purchaser,amount FROM {$ecs->table('purchase_bill')} 
	    WHERE purchase_bill_id = '{$bill_id}';
	";
} 
if ($bill_type == 'finance') {
	$sql = "select purchase_bill_id from ecshop.order_bill_mapping where oukoo_inside_c2c_bill_id = '{$bill_id}' ";
	$purchase_bill_id = $db->getOne($sql);
	$sql = "
		SELECT date,purchaser,amount FROM {$ecs->table('oukoo_inside_c2c_bill')} 
		WHERE bill_id = '{$bill_id}';
	";
}  
$bill = $db->getRow($sql);

$sql = "SELECT distinct ii.provider_id
	    FROM 
			ecshop.order_bill_mapping obm 
	        inner join ecshop.ecs_order_info oi on oi.order_id = obm.order_id
	        inner join ecshop.ecs_order_goods og on obm.order_goods_id = og.rec_id
		    inner join romeo.inventory_item_detail iid on iid.ORDER_GOODS_ID = convert(og.rec_id using utf8)
	        inner join romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
	    WHERE
	    	obm.purchase_bill_id = '{$purchase_bill_id}'";
$provider_ids = $db->getAll($sql);

foreach ($provider_ids as $key=>$provider_id) {
	$sql = "
		SELECT a.* FROM (
	    SELECT ii.UNIT_COST as purchase_paid_amount, 
	           og.goods_price,
	    	   ii.CREATED_STAMP as in_time,
	    	   iid.quantity_on_hand_diff,
	    	   poi.cheque,
	    	   og.goods_name,
	    	   oi.order_sn,
	    	   oi.order_id,
	    	   poi.purchase_paid_type
	    FROM 
			ecshop.order_bill_mapping obm 
           	inner join ecshop.ecs_order_info oi on oi.order_id = obm.order_id
			inner join romeo.purchase_order_info poi on obm.order_goods_id = poi.order_goods_id
           	inner join ecshop.ecs_order_goods og on obm.order_goods_id = og.rec_id
	    	inner join romeo.inventory_item_detail iid on iid.ORDER_GOODS_ID = convert(og.rec_id using utf8)
           	inner join romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
	    WHERE
	    	obm.purchase_bill_id = '{$purchase_bill_id}' and ii.provider_id = '{$provider_id["provider_id"]}'
			AND oi.order_type_id in ('PURCHASE')
		UNION ALL
		SELECT -ii.UNIT_COST AS purchase_paid_amount,
			   -og.goods_price,
			   iid.CREATED_STAMP as in_time,
			   -iid.quantity_on_hand_diff,
			   sroi.cheque,
			   og.goods_name,
			   oi.order_sn,
			   oi.order_id,
			   sroi.purchase_paid_type
		FROM 
			 ecshop.order_bill_mapping obm 
			 inner join ecshop.ecs_order_info oi on oi.order_id = obm.order_id
			 inner join ecshop.supplier_return_order_info sroi on obm.order_goods_id = sroi.order_goods_id
			 inner join ecshop.ecs_order_goods og on obm.order_goods_id = og.rec_id
			 inner join romeo.inventory_item_detail iid on iid.ORDER_GOODS_ID = convert(og.rec_id using utf8)
			 inner join romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
		WHERE
		  obm.purchase_bill_id = '{$purchase_bill_id}' and ii.provider_id = '{$provider_id["provider_id"]}'
		  AND oi.order_type_id in ('SUPPLIER_SALE','SUPPLIER_RETURN')
	) a
	";
	
	$provider_ids[$key]["data"] = $db->getALL($sql);
	$provider_ids[$key]["sum_purchase_paid_amount"] = 0;
	foreach ($provider_ids[$key]["data"] as $goods) {
	    $provider_ids[$key]["sum_purchase_paid_amount"] += $goods['purchase_paid_amount'] * $goods['quantity_on_hand_diff'];
	}
}

$smarty->assign('bill', $bill);
$smarty->assign('provider_ids', $provider_ids);
if ($type == "b2c") {
	$smarty->display('oukooext/c2c_buy_sale_bill_detail_print_b2c.htm');
} 
if ($type == "c2c") {
	$smarty->display('oukooext/c2c_buy_sale_bill_detail_print_c2c.htm');
}

?>