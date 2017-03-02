<?php
/*
 * Created on 2014-8-21
 */
define('IN_ECS', true);
require('includes/init.php'); 

$act=isset($_REQUEST['act'])?$_REQUEST['act']:'';
$purchase_order = isset($_REQUEST['purchase_order'])?trim($_REQUEST['purchase_order']):'';
$supplier_order = isset($_REQUEST['supplier_order'])?trim($_REQUEST['supplier_order']):'';

if($act == 'search'){
	if($purchase_order != ''){
		$sql="select po.order_id pOrder_id,
					po.order_sn pOrder, 
					(select sum(pid.quantity_on_hand_diff) from romeo.inventory_item_detail pid where pid.order_id = iid.order_id) pQuantity, 
					min(iid.created_stamp) pTime,
					go.order_id sOrder_id, 
					go.order_sn sOrder,
	                if(go.order_type_id = 'SUPPLIER_RETURN', '供应商退货',if(go.order_type_id = 'SUPPLIER_TRANSFER', '转仓', '二次销售')) order_type,
					sum(iid1.quantity_on_hand_diff) sQuantity, 
					min(iid1.created_stamp) sTime
				from ecshop.ecs_order_info po
					inner join romeo.inventory_item_detail iid on convert(po.order_id using utf8) = iid.order_id and iid.quantity_on_hand_diff > 0
					left join romeo.inventory_item ii on iid.inventory_item_id = ii.root_inventory_item_id
					left join romeo.inventory_item_detail iid1 on ii.inventory_item_id = iid1.inventory_item_id
					left join ecshop.ecs_order_info go on cast(iid1.order_id as unsigned) = go.order_id
				where po.order_sn = '{$purchase_order}' and go.order_type_id in ('SUPPLIER_SALE', 'SUPPLIER_RETURN','SUPPLIER_TRANSFER')
				group by go.order_id, po.order_id
			  ";
		$order=$db->getAll($sql);
	}else if($supplier_order != ''){
		$sql="select po.order_id pOrder_id,
					po.order_sn pOrder, 
					(select sum(pid.quantity_on_hand_diff) from romeo.inventory_item_detail pid where pid.order_id = iid1.order_id) pQuantity, 
					min(iid1.created_stamp) pTime,
					go.order_id sOrder_id, 
					go.order_sn sOrder,
	                if(go.order_type_id = 'SUPPLIER_RETURN', '供应商退货', '二次销售') order_type,
					sum(iid.quantity_on_hand_diff) sQuantity, 
					min(iid.created_stamp) sTime
				from ecshop.ecs_order_info go
					inner join romeo.inventory_item_detail iid on convert(go.order_id using utf8) = iid.order_id
					left join romeo.inventory_item ii on iid.inventory_item_id = ii.inventory_item_id
					left join romeo.inventory_item_detail iid1 on ii.root_inventory_item_id = iid1.inventory_item_id and iid1.quantity_on_hand_diff > 0
					left join ecshop.ecs_order_info po on cast(iid1.order_id as unsigned) = po.order_id
				where go.order_sn = '{$supplier_order}' and po.order_type_id in ('PURCHASE', 'VARIANCE_ADD')
				group by go.order_id, po.order_id 
			  ";
		$order=$db->getAll($sql);
	}
	$smarty->assign('orders', $order);
}

$smarty->display("oukooext/query_purchase_supplier_order.htm");
?>
