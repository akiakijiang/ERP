<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('finance_order', 'purchase_order');
require("function.php");

$type = $_REQUEST['type'];
$bill_id = intval($_REQUEST['bill_id']);
if ($bill_id <= 0) {
    die("非法输入");
}
$purchase_bill_id = $bill_id;
if ($type == 'p') {
	$sql = "
	    SELECT * FROM {$ecs->table('purchase_bill')} 
	    WHERE purchase_bill_id = '{$bill_id}';
	";
} 
if ($type == 'f') {
	$sql = "select purchase_bill_id from ecshop.order_bill_mapping where oukoo_inside_c2c_bill_id = '{$bill_id}' ";
	$purchase_bill_id = $db->getOne($sql);
	$sql = "
	    SELECT * FROM {$ecs->table('oukoo_inside_c2c_bill')} 
	    WHERE bill_id = '{$bill_id}';
	";
}  
$bill = $db->getRow($sql);
$bill['bill_type'] = 'purchase';
if($type == 'f'){
	$bill['bill_type'] = 'finance';
}

if (!empty($purchase_bill_id)) {
	$sql = "
		SELECT a.* FROM (
	    SELECT ii.UNIT_COST as purchase_paid_amount, 
	           og.goods_price,
	    	   ii.CREATED_STAMP as in_time,
	    	   iid.quantity_on_hand_diff,
	    	   poi.cheque,
	    	   og.goods_name,
	    	   oi.order_sn,
	    	   ii.provider_id,
	    	   oi.order_id
	    	FROM 
				ecshop.order_bill_mapping obm 
           		inner join ecshop.ecs_order_info oi on oi.order_id = obm.order_id
				inner join romeo.purchase_order_info poi on obm.order_goods_id = poi.order_goods_id
           		inner join ecshop.ecs_order_goods og on obm.order_goods_id = og.rec_id
	    		inner join romeo.inventory_item_detail iid on iid.ORDER_GOODS_ID = convert(og.rec_id using utf8)
           		inner join romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
	    WHERE
	    	obm.purchase_bill_id = '{$purchase_bill_id}'
			  AND oi.order_type_id in ('PURCHASE')
		UNION ALL
		SELECT -ii.UNIT_COST AS purchase_paid_amount,
			   -og.goods_price,
			   iid.CREATED_STAMP as in_time,
			   -iid.quantity_on_hand_diff,
			   sroi.cheque,
			   og.goods_name,
			   oi.order_sn,
			   ii.provider_id,
			   oi.order_id
		FROM 
			 ecshop.order_bill_mapping obm 
			 inner join ecshop.ecs_order_info oi on oi.order_id = obm.order_id
			 inner join ecshop.supplier_return_order_info sroi on obm.order_goods_id = sroi.order_goods_id
			 inner join ecshop.ecs_order_goods og on obm.order_goods_id = og.rec_id
			 inner join romeo.inventory_item_detail iid on iid.ORDER_GOODS_ID = convert(og.rec_id using utf8)
			 inner join romeo.inventory_item ii on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
		WHERE
		  obm.purchase_bill_id = '{$purchase_bill_id}'
		  AND oi.order_type_id in ('SUPPLIER_SALE','SUPPLIER_RETURN')
	) a
	";
}

$goods_list = $db->getAll($sql);

$sum = array();
$sum['goods_price'] = 0;
$sum['purchase_paid_amount'] = 0;

foreach ($goods_list as $goods) {
    $sum['goods_price'] += $goods['goods_price'] * $goods['quantity_on_hand_diff'];
    $sum['purchase_paid_amount'] += $goods['purchase_paid_amount'] * $goods['quantity_on_hand_diff'];
}

//zlh
if ($bill['voucher_date'] != '0000-00-00 00:00:00'){
    $bill['voucher_year'] = date('Y', strtotime($bill['voucher_date']));
    $bill['voucher_month'] = date('m', strtotime($bill['voucher_date']));
} else {
    $bill['voucher_year'] = date('Y', strtotime($bill['date']));
    $bill['voucher_month'] = date('m', strtotime($bill['date']));
}
$smarty->assign('next_year', ( (int)date('Y', time()) + 1));
$smarty->assign('bill', $bill);
$smarty->assign('goods_list', $goods_list);
$smarty->assign('sum', $sum);
$smarty->display('oukooext/c2c_buy_sale_bill_detail.htm');
?>
