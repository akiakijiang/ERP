<?php

/**
 * 拣货单打印
 * 
 * @author wjzhu@i9i8.com
 */

define('IN_ECS', true);
require('includes/init.php');
include_once 'function.php';
require_once('includes/lib_order.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');


if(isset($_REQUEST['picking_id']) && is_string($_REQUEST['picking_id'])){
    $picking_id_list = preg_split('/\s*,\s*/',$_REQUEST['picking_id'],-1,PREG_SPLIT_NO_EMPTY);
    $picking_id = $picking_id_list[0];
}
else {
    die("参数错误");
}

$order_id = getOneOrderID($picking_id);

if($order_id) {
	// 仓库、重复订单数
	$picking = getPicking($picking_id);
	$facility_id = $picking['FACILITY_ID'];
	$repeat_order_count = $picking['REPEAT_ORDER_COUNT'];
	if ($picking['BATCH_PICKING']) {
		//die("该批次订单已批拣");
	}
	
	// 更新批拣标志
	updateBatchPickingStatue($picking_id);
	
	// 获取批拣物品清单, 组装数据, 获取GoodList列表
	$goods_list = array();
	$sql = getGoodListSQL($order_id);
	$ref_goods_fields = $ref_goods_rowset = array();
	$result=$db->getAllRefby($sql, array('rec_id'), $ref_goods_fields, $ref_goods_rowset);
	if ($result) {
		foreach ($ref_goods_rowset['rec_id'] as $rec_id => $goods_item) { 
			// 取得商品编码
			if(empty($goods_item[0]['barcode'])){
				$ref_goods_rowset['rec_id'][$rec_id][0]['product_code']=encode_goods_id($goods_item[0]['goods_id'],$goods_item[0]['style_id']);	
			}
			else{
				$ref_goods_rowset['rec_id'][$rec_id][0]['product_code']=$goods_item[0]['barcode'] ;
			}
			
			$goods_list[$rec_id]=$goods_item[0];
			$goods_list[$rec_id]['goods_number']=$goods_item[0]['goods_number'] * $repeat_order_count;
		}
	}
}
else {
    die("参数错误");
}

$smarty->assign('picking_id', $picking_id);
$smarty->assign('facility_id', $facility_id);
$smarty->assign('goods_list', $goods_list);
$smarty->display('shipment/picking_print.htm');


function updateBatchPickingStatue($picking_id) {
	global $db;
	$sql = "UPDATE romeo.picking SET batch_picking = true where PICKING_ID = '{$picking_id}' ";
    return $db->query($sql);
}

function getOneOrderID($picking_id) {
    global $db;
	$sql = "SELECT order_id FROM romeo.order_picking WHERE picking_id = '{$picking_id}' ";
	return $db->getOne($sql);
}

function getPicking($picking_id) {
	global $db;
	$sql = "SELECT * FROM romeo.picking where picking_id = '{$picking_id}'";
	return $db->getRow($sql);
}

function getGoodListSQL($order_id) {
	$sql = "
		SELECT 		og.order_id, 
					og.goods_name,
					og.goods_number,
					og.goods_price,
					og.goods_id,
					og.style_id,
					og.goods_price * og.goods_number as goods_amount,
					og.rec_id,
					ifnull(g.barcode, '') as barcode,
					g.uniq_sku
		FROM		ecshop.ecs_order_goods og	
		LEFT JOIN 	ecshop.ecs_goods g on og.goods_id = g.goods_id
		WHERE		og.order_id = '{$order_id}'
	";
	return $sql;
}
