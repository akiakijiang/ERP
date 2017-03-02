<?php
define('IN_ECS', true);
require_once('../includes/init.php');

require_once(ROOT_PATH. "/RomeoApi/lib_inventory.php");
require(ROOT_PATH . "/includes/lib_order.php");

define('VARIANCE_ADD', VARIANCE_ADD);//'-v 盘盈'
define('VARIANCE_MINUS', VARIANCE_MINUS);//'-v 盘亏'

//if (!in_array($_SESSION['admin_name'], array('lchen', 'ychen', 'xlhong', 'hbai','qdi'))) {
//    exit('access denied');
//}

if (!party_explicit($_SESSION['party_id'])) {
    exit('请选择分公司的party_id，再进行操作');
}

admin_priv('physicalInventoryAdjust');

if ($_REQUEST['act'] == 'out') {
    //    pp($_POST);
    //    die();
    // 数据的整理工作
    $orderGoodsId = trim($_REQUEST['orderGoodsId']);
    if(empty($orderGoodsId)){
    	die('没有要调整的商品');
    }

    $serialNumber = trim($_REQUEST['serialNumber']);
    if(!empty($serialNumber)){
    	$sql = "select order_type_id from ecshop.ecs_order_info oi 
    				inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
    						where og.rec_id = '{$orderGoodsId}'";
    	$order_type_id = $db->getOne($sql);
    	$sql = " select 1 from romeo.inventory_item 
    			 where serial_number = '{$serialNumber}'
    			 and quantity_on_hand_total > 0 ";
	    $exit_serial_number = $db->getOne($sql);
    	if($order_type_id == 'VARIANCE_MINUS'){
    		$sql = "select og.goods_number+ifnull(sum(iid.quantity_on_hand_diff),0)
    				from ecshop.ecs_order_goods og
    				left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8) 
    				where og.rec_id = '{$orderGoodsId}'
    				group by og.rec_id";
    		$still_need_out_number = $db->getOne($sql);
    		
    		$sql_re = "SELECT oir.STATUS as reserve_status from ecshop.ecs_order_goods og 
            LEFT JOIN romeo.order_inv_reserved oir on og.order_id = oir.ORDER_ID
            where og.rec_id = '{$orderGoodsId}' limit 1";
       
		    $reserve_status = $db->getOne($sql_re); 
		    if($reserve_status != 'Y'){
		    	die($orderGoodsId.'该商品对应的订单未预定');
		    }
		    
		    if($still_need_out_number <= 0){
		    	die($orderGoodsId.'已经出库');
		    }
	    	if(!$exit_serial_number){
	    		die($serialNumber.'串号有误');
	    	}
    	}else if($order_type_id == 'VARIANCE_ADD'){
    		$sql = "select og.goods_number-ifnull(sum(iid.quantity_on_hand_diff),0)
    				from ecshop.ecs_order_goods og
    				left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8) 
    				where og.rec_id = '{$orderGoodsId}'
    				group by og.rec_id";
    		$still_need_out_number = $db->getOne($sql);
		    if($still_need_out_number <= 0){
		    	die($orderGoodsId.'已经出库');
		    }
    		if($exit_serial_number){
	    		die($serialNumber.'串号已存在');
	    	}
    	}else{
    		die($orderGoodsId.'订单商品号有误');	
    	}
    }else{
    	$sql = "select 1 from  romeo.inventory_item_detail 
					where order_goods_id = '{$orderGoodsId}' ";
	    $is_already_out = $db->getOne($sql);
	    if($is_already_out){
	    	die('已经出库');
	    }
    }
    
    $sql = "select order_type_id from ecshop.ecs_order_info oi 
    				inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
    						where og.rec_id = '{$orderGoodsId}'";
    $order_type_id = $db->getOne($sql);
    
    $sql_re = "SELECT oir.STATUS as reserve_status from ecshop.ecs_order_goods og 
            LEFT JOIN romeo.order_inv_reserved oir on og.order_id = oir.ORDER_ID
            where og.rec_id = '{$orderGoodsId}'  limit 1";
       
    $reserve_status = $db->getOne($sql_re); 
    if( $reserve_status != 'Y' && $order_type_id == 'VARIANCE_MINUS'){
    	die($orderGoodsId.'该商品对应的订单未预定');
    }
    $result = deliver_inventory_virance_order_inventory($orderGoodsId,$serialNumber);
    if($result->get("status")->stringValue != 'OK'){
    	die('盘点错误 orderGoodsId:'.$orderGoodsId);
    }
}else if ($_REQUEST['act'] == 'del') {
	$orderGoodsId = trim($_REQUEST['orderGoodsId']);
    if(empty($orderGoodsId)){
    	die('没有要调整的商品');
    }
    
    $sqlo = " select order_id from ecshop.ecs_order_goods
    		  where rec_id = '{$orderGoodsId}'
    		";
    $order_id = $db->getOne($sqlo);
    $result = cancelOrderInventoryReservation($order_id);
    
    $sql3 ="select oir.status as reserve_status 
    		from ecshop.ecs_order_goods og
    		inner join ecshop.ecs_order_info oi on oi.order_id = og.order_id   		 
			left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
			where rec_id = '{$orderGoodsId}'";
	$reserve_status = $db -> getOne($sql3);
    
    $sql = "delete from ecshop.ecs_order_goods
    				where rec_id = '{$orderGoodsId}'";
    if(empty($reserve_status)){
    	 $db->query($sql);
    }  
    
}
$already_apply_goods = get_virance_order_info();
$already_out_goods = array();
$not_out_goods = array();
foreach($already_apply_goods as $goods){
	if($goods['is_all_out']){
		$already_out_goods[] = $goods;
	}else{
		$not_out_goods[] = $goods;
	}
}
//pp($not_out_goods);
//pp($already_out_goods);
$smarty->assign('not_out_goods', $not_out_goods);
$smarty->assign('already_out_goods', $already_out_goods);
$smarty->assign('user_current_party_name', party_mapping($_SESSION['party_id']));

if($_REQUEST['export']){
			header("Content-type:application/vnd.ms-excel");
		    header("Content-Disposition:filename=" .iconv("UTF-8", "GB18030", "已经出库商品列表") . ".csv");
		    $out = $smarty->fetch('virance_inventory/physical_inventory_out_csv.htm');
		    echo iconv("UTF-8", "GB18030", $out);        
}else{
	$smarty->display('virance_inventory/physical_inventory_out_inventory.htm');
}
function get_virance_order_info(){
	global $db;
	$condition = getCondition();
	$sql = "select pm.product_id,oi.facility_id,og.rec_id as order_goods_id,og.goods_name,og.goods_number, goods_price, status_id,
			oi.order_sn,
			oi.order_type_id,
			ifnull(sum(iid.quantity_on_hand_diff),0) as out_num,
			f.facility_name,
			if(egs.barcode is NULL or egs.barcode = '',eg.barcode,egs.barcode) as barcode,
			oi.order_time,oir.status as reserve_status
			from ecshop.ecs_order_info oi
			left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id 
			inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
			inner join romeo.facility f on oi.facility_id = f.FACILITY_ID
			inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
			inner join ecshop.ecs_goods eg on pm.ecs_goods_id = eg.goods_id
			left join ecshop.ecs_goods_style egs on egs.goods_id = pm.ecs_goods_id and egs.style_id = pm.ecs_style_id and egs.is_delete=0
			left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
			where order_type_id in ('VARIANCE_ADD','VARIANCE_MINUS') and oi.party_id = '{$_SESSION['party_id']}'
			$condition
			group by og.rec_id 
			order by oi.facility_id,oi.order_type_id";

	$goods = $db->getAll($sql);
	$result = array();
	foreach($goods as $good){
		$diff_num = $good['goods_number'] + $good['out_num'];
		if($good['order_type_id'] == 'VARIANCE_ADD'){
			$diff_num = $good['goods_number'] - $good['out_num'];
		}
		$good['is_all_out'] = true;
		if($diff_num > 0){
			$good['is_all_out'] = false;
		}
		$is_serial = is_serial_number_type($good['product_id']);
		$good['is_serial'] = $is_serial;
		if($is_serial){
			$serial_numbers = get_serial_number($good['order_goods_id']);
			$good['serial_numbers'] = $serial_numbers;
		}
		$result[] = $good;
	}
	return  $result;
}
function is_serial_number_type($productId){
	global $db;
	$sql = "select INVENTORY_ITEM_TYPE_ID from romeo.inventory_item where product_id = '{$productId}'";
	$item_type_id = $db->getOne($sql);
	if($item_type_id == 'SERIALIZED'){
		return true;
	}else{
		return false;
	}
}
function get_serial_number($order_goods_id){
	global $db;
	$sql = "select serial_number from romeo.inventory_item_detail iid 
			inner join romeo.inventory_item ii on ii.inventory_item_id = iid.inventory_item_id
			where iid.order_goods_id = '{$order_goods_id}'
			";
	return $db->getCol($sql);
}

function deliver_inventory_virance_order_inventory($orderGoodsId,$serialNumber){
	global $db;
	$sql = "select oi.order_id,oi.facility_id,og.status_id,pm.product_id,og.goods_number,og.goods_number,og.goods_price,oi.postscript,oi.order_type_id
			from ecshop.ecs_order_goods og
			inner join ecshop.ecs_order_info oi on og.order_id = oi.order_id
			inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
			where og.rec_id = '{$orderGoodsId}'";
	$row = $db->getRow($sql);
	
	$productId = $row['product_id'];
	$facilityId = $row['facility_id'];
	$statusId = $row['status_id'];
	$unitCost = $row['goods_price'];
	
	$comment = $row['postscript'];
	$order_type_id = $row['order_type_id'];
	$orderId = $row['order_id'];
	$quantityOnHandVar = $row['goods_number'];
	
	$sql = " select physical_inventory_id 
			from romeo.inventory_item_detail where order_goods_id = '{$orderGoodsId}'";
	$physicalInventoryId = $db->getOne($sql);
	if(empty($physicalInventoryId)){
		$physicalInventoryId = createPhysicalInventory($comment);
	}
	if(empty($physicalInventoryId)){
		die("创建physicalInventoryId不成功 ");
	}
	
	$sql = "select INVENTORY_ITEM_ACCT_TYPE_ID,INVENTORY_ITEM_TYPE_ID 
				from romeo.inventory_item 
			where product_id = '{$productId}' and facility_id = '{$facilityId}' 
			";
	$row = $db->getRow($sql);
	if(empty($row)){
		die("该商品不存在过, ".$sql);
	}
	$inventoryItemTypeName = $row['INVENTORY_ITEM_TYPE_ID'];// SERIALIZED, NON-SERIALIZED
	$inventoryItemAcctTypeName = $row['INVENTORY_ITEM_ACCT_TYPE_ID'];// B2C, C2C
	if($inventoryItemTypeName == 'SERIALIZED'){
		$quantityOnHandVar = 1;
	}
	if($order_type_id == VARIANCE_MINUS){
		$quantityOnHandVar = -$quantityOnHandVar;
	}
	$availableToPromiseVar = $quantityOnHandVar;
	$result = createInventoryItemVarianceByProductId(
            $productId, $inventoryItemAcctTypeName, $inventoryItemTypeName, 
            $statusId, $serialNumber, $quantityOnHandVar, 
            $availableToPromiseVar, $physicalInventoryId,
            $unitCost, $facilityId,$comment,$orderId,$orderGoodsId);
    return $result;
}
function getCondition(){
	$condition = "";
	$starttime = $_REQUEST['startCalendar'];
	$endtime = $_REQUEST['endCalendar'];
	if($starttime){
		$condition .=" AND oi.order_time >= '$starttime' ";
	}
	if($endtime){
		$condition .=" AND oi.order_time <= '$endtime' ";
	}
	return $condition;
}