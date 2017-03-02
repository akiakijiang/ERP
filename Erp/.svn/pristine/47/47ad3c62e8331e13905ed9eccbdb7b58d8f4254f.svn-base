<?php
define('IN_ECS', true);
require('includes/init.php');
require_once ('includes/debug/lib_log.php');
require("function.php");

$act = $_REQUEST['act'] ;
$batch_pick_sn = isset($_REQUEST['batch_pick_sn']) ? $_REQUEST['batch_pick_sn'] : null;

if($act == 'shipment'){
	//合并发货单号
	$sql = "
	select reserve.shipment_id,CONCAT(goods.goods_name,' ',IF (goods_style.goods_color = '' or goods_style.goods_color is null , ifnull(style.color, ''), ifnull(goods_style.goods_color, '')))as goods_name, 
	il.location_barcode,reserve.reserved_quantity, reserve.out_quantity, reserve.status_id,
	(select count(*) from  romeo.inventory_location_reserve reserve2  where reserve2.shipment_id = reserve.shipment_id limit 1) as count_shipment
	from romeo.inventory_location_reserve as reserve 
	LEFT JOIN romeo.inventory_location as il on il.inventory_location_id=reserve.inventory_location_id
	LEFT JOIN romeo.product_mapping AS mapping ON mapping.PRODUCT_ID = il.PRODUCT_ID
	LEFT JOIN ecshop.ecs_goods AS goods ON goods.goods_id = mapping.ECS_GOODS_ID
	LEFT JOIN ecshop.ecs_goods_style AS goods_style ON goods_style.style_id = mapping.ECS_STYLE_ID and goods_style.goods_id = mapping.ECS_GOODS_ID and goods_style.is_delete=0
	LEFT JOIN ecshop.ecs_style AS style on style.style_id = mapping.ECS_STYLE_ID
	where batch_pick_sn = '{$batch_pick_sn}'
    order BY shipment_id	
   ";
   $result = $db->getAll($sql);
   $smarty->assign("type", $act);
}else if($act == 'grouding_barcode'){
	//合并库位条码
	$sql = "
	select reserve.shipment_id,CONCAT(goods.goods_name,' ',IF (goods_style.goods_color = '' or goods_style.goods_color is null , ifnull(style.color, ''), ifnull(goods_style.goods_color, '')))as goods_name, 
	il.location_barcode,reserve.reserved_quantity, reserve.out_quantity, reserve.status_id
	from romeo.inventory_location_reserve as reserve 
	LEFT JOIN romeo.inventory_location as il on il.inventory_location_id=reserve.inventory_location_id
	LEFT JOIN romeo.product_mapping AS mapping ON mapping.PRODUCT_ID = il.PRODUCT_ID
	LEFT JOIN ecshop.ecs_goods AS goods ON goods.goods_id = mapping.ECS_GOODS_ID
	LEFT JOIN ecshop.ecs_goods_style AS goods_style ON goods_style.style_id = mapping.ECS_STYLE_ID and goods_style.goods_id = mapping.ECS_GOODS_ID and goods_style.is_delete=0
	LEFT JOIN ecshop.ecs_style AS style on style.style_id = mapping.ECS_STYLE_ID
	where batch_pick_sn = '{$batch_pick_sn}'
    ORDER  BY il.location_barcode
   ";
   $result = $db->getAll($sql);
   $sql = "SELECT il.location_barcode,COUNT(il.location_barcode) as barcode_number from romeo.inventory_location_reserve as reserve 
				LEFT JOIN romeo.inventory_location as il on il.inventory_location_id=reserve.inventory_location_id
				where reserve.batch_pick_sn = '{$batch_pick_sn}'
 			GROUP BY  il.location_barcode";
   $barcode_number = $db->getAll($sql);		
   foreach ( $result as $key => $res ) {
       foreach ( $barcode_number as $key1 => $b_number ) {
       	if($barcode_number[$key1]['location_barcode'] == $result[$key]['location_barcode']){
       		$result[$key]['barcode_number'] = $barcode_number[$key1]['barcode_number'];
       	 }
       }
   }
   $smarty->assign("type", $act);
}else{
	$sql = "
	select reserve.shipment_id,CONCAT(goods.goods_name,' ',IF (goods_style.goods_color = '' or goods_style.goods_color is null , ifnull(style.color, ''), ifnull(goods_style.goods_color, '')))as goods_name, 
	il.location_barcode,reserve.reserved_quantity, reserve.out_quantity, reserve.status_id
	from romeo.inventory_location_reserve as reserve 
	LEFT JOIN romeo.inventory_location as il on il.inventory_location_id=reserve.inventory_location_id
	LEFT JOIN romeo.product_mapping AS mapping ON mapping.PRODUCT_ID = il.PRODUCT_ID
	LEFT JOIN ecshop.ecs_goods AS goods ON goods.goods_id = mapping.ECS_GOODS_ID
	LEFT JOIN ecshop.ecs_goods_style AS goods_style ON goods_style.style_id = mapping.ECS_STYLE_ID and goods_style.goods_id = mapping.ECS_GOODS_ID and goods_style.is_delete=0
	LEFT JOIN ecshop.ecs_style AS style on style.style_id = mapping.ECS_STYLE_ID
	where batch_pick_sn = '{$batch_pick_sn}'
	";
	$smarty->assign("type", 'default');
	$result = $db->getAll($sql);
}

$smarty->assign("shipment_list", $result);
$smarty->assign("batch_pick_sn", $batch_pick_sn);


$smarty->display("oukooext/batch_pick_detail.htm");