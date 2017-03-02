<?php
define('IN_ECS', true);
require_once('../includes/init.php');

require_once(ROOT_PATH. "/RomeoApi/lib_inventory.php");
require_once(ROOT_PATH . "/includes/lib_order.php");
require_once('../function.php');
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
    $orderGoodsIds = $_REQUEST['orderGoodsId'];
    if(empty($orderGoodsIds)){
    	die('没有要调整的商品');
    }
    foreach($orderGoodsIds as $key=>$orderGoodsId){
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
    		
    		$sql = "select ifnull(oir.STATUS,'N') as reserve_status
					from ecshop.ecs_order_goods og
					LEFT JOIN romeo.order_inv_reserved oir on oir.order_id = og.order_id
					where og.rec_id = '{$orderGoodsId}'";
    		
    		$reserve_status = $db->getOne($sql);
    		
    		if($reserve_status == 'N'){
		    	die($orderGoodsId.'订单未被预定');
		    }
    		
    		$sql = "select og.goods_number+ifnull(sum(iid.quantity_on_hand_diff),0)
    				from ecshop.ecs_order_goods og
    				left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8) 
    				where og.rec_id = '{$orderGoodsId}'
    				group by og.rec_id";
    		$still_need_out_number = $db->getOne($sql);
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
    	$sql = "select order_type_id from ecshop.ecs_order_info oi 
    				inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
    						where og.rec_id = '{$orderGoodsId}'";
    	$order_type_id = $db->getOne($sql);
    	if($order_type_id == 'VARIANCE_MINUS'){
    	
	    	$sql = "select ifnull(oir.STATUS,'N') as reserve_status
						from ecshop.ecs_order_goods og
						LEFT JOIN romeo.order_inv_reserved oir on oir.order_id = og.order_id
						where og.rec_id = '{$orderGoodsId}'";
	    		
			$reserve_status = $db->getOne($sql);
			if($reserve_status == 'N'){
		    	die($orderGoodsId.'订单未被预定');
		    }
    	}   	
    	$sql = "select 1 from  romeo.inventory_item_detail 
					where order_goods_id = '{$orderGoodsId}' ";
	    $is_already_out = $db->getOne($sql);
	    if($is_already_out){
	    	die($orderGoodsId.'已经出库');
	    }
    }
    $result = deliver_inventory_virance_order_inventory($orderGoodsId,$serialNumber);
	    if($result->get("status")->stringValue != 'OK'){
	    	die('盘点错误 orderGoodsId:'.$orderGoodsId);
	    }
    }
}
$smarty->assign('user_current_party_name', party_mapping($_SESSION['party_id']));
$tpl =  array ('批量-v调整' =>  array (
	        		'barcode' => '商品条码', 
	                'order_sn' => '订单号', 
	                'status_id' => '库存状态',
	                'facility_name' => '仓库名称',
	                'goods_number' => '申请调整数量'));
if($_REQUEST['export']){
			header("Content-type:application/vnd.ms-excel");
		    header("Content-Disposition:filename=" .iconv("UTF-8", "GB18030", "已经出库商品列表") . ".csv");
		    $out = $smarty->fetch('virance_inventory/physical_inventory_out_csv.htm');
		    echo iconv("UTF-8", "GB18030", $out);
		    die(); 
}else if($_REQUEST['import']){
	$already_apply_goods = get_virance_order_info($tpl);
	$already_out_goods = array();
	$not_out_goods = array();
	foreach($already_apply_goods['result'] as $goods){
		if($goods['is_all_out']){
			$already_out_goods[] = $goods;
		}else{
			$not_out_goods[] = $goods;
		}
	}
	$smarty->assign('message', $already_apply_goods['message']);
	$smarty->assign('not_out_goods', $not_out_goods);
	$smarty->assign('already_out_goods', $already_out_goods);
}else if($_REQUEST['download_template']){
	export_excel_template($tpl);
}
$smarty->display('virance_inventory/batch_inventory_out.htm');
	
	
function get_virance_order_info($tpl){
	$info_result = array();
	$result = before_upload_exam($tpl);
	$message = $result['message'] ? $result['message'] : '';
	$rowset = $result['result']['批量-v调整'];
	$status = array( '正式库' => 'INV_STTS_AVAILABLE',  '二手库' => 'INV_STTS_USED');
	global $db;
	if($rowset){
		foreach($rowset as $key=>$row){
			
			$barcode = trim($row['barcode']);
			$order_sn = trim($row['order_sn']);
			$facility_name = trim($row['facility_name']);
			$goods_number = trim($row['goods_number']);
			$order_type_id = $goods_number > 0 ? 'VARIANCE_ADD' : 'VARIANCE_MINUS';
			$goods_number = abs($goods_number);
			$status_id = $status[trim($row['status_id'])];
			$sql = "select pm.product_id,oi.facility_id,og.rec_id as order_goods_id,og.goods_name,og.goods_number, goods_price, status_id,
				oi.order_sn,
				oi.order_type_id,
				ifnull(sum(iid.quantity_on_hand_diff),0) as out_num,
				f.facility_name,
				if(egs.barcode is NULL or egs.barcode = '',eg.barcode,egs.barcode) as barcode,
				oi.order_time
				from ecshop.ecs_order_info oi
				inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
				inner join romeo.facility f on oi.facility_id = f.FACILITY_ID
				inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
				inner join ecshop.ecs_goods eg on pm.ecs_goods_id = eg.goods_id
				left join ecshop.ecs_goods_style egs on egs.goods_id = pm.ecs_goods_id and egs.style_id = pm.ecs_style_id and egs.is_delete=0
				left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
				where  oi.party_id = '{$_SESSION['party_id']}' 
				AND oi.order_sn = '$order_sn' AND IFNULL(egs.barcode,eg.barcode) = '$barcode'
				group by og.rec_id ";
				
	$goods = $db->getAll($sql);
	$common = "第".($key + 1)."行 订单号：$order_sn, 商品编码：$barcode ";
	if(count($goods) === 0){
		$message .= $common."查不到相关记录<br/>";
		continue;
	}
	if(count($goods) > 1){
		$message .= $common."有多条记录<br/>";
		continue;
	}
	
	$good = $goods[0];
	if($good['facility_name'] != $facility_name){
		$message .= "$common 仓库名应为{$good['facility_name']}<br/>";
		continue;
	}
	if($good['order_type_id'] != $order_type_id){
		$order_type = $good['order_type_id'] == 'VARIANCE_ADD' ? '盘盈' : '盘亏';
		$message .= "$common -v类型应为$order_type<br/>";
		continue;
	}
	if($good['status_id'] != $status_id){
		$status_cn = $good['status_id'] == 'INV_STTS_AVAILABLE' ? '正式库' : '二手库';
		$message .= "$common -v库存应为$status_cn<br/>";
		continue;
	}
	if($good['goods_number'] != $goods_number){
		$message .= $common." 商品数量应为{$good['goods_number']}<br/>";
		continue;
	}
	
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
	$info_result[] = $good;
	
	}
		
	}
	$return['result'] = $info_result;
	$return['message'] = $message;
	return  $return;
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
