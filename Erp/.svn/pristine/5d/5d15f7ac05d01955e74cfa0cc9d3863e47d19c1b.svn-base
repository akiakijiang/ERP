<?php
/**
 * 批拣单详情
 * 列出库位预定信息、发货单信息
 * @author  zxcheng 20131024
 */
define('IN_ECS', true);
require('includes/init.php');
require_once ('includes/debug/lib_log.php');
require("function.php");

$act = $_REQUEST['act']!=null ? $_REQUEST['act'] :'location_barcode';
Qlog::log('act='.$act);
$batch_pick_sn = isset($_REQUEST['batch_pick_sn']) ? $_REQUEST['batch_pick_sn'] : null;
Qlog::log('batch_pick_sn='.$batch_pick_sn);
class DisplayOption
{
	//tab id：0=库位信息；1=发货单信息
	public $display_tab_id_;
}
$display_option = new DisplayOption;
Qlog::log('display_tab_id='. $_REQUEST['display_tab_id']);
$display_option->display_tab_id_= (array_key_exists('display_tab_id', $_REQUEST) && $_REQUEST['display_tab_id']!='')?  $_REQUEST['display_tab_id'] :'0';
 
if($act== 'location_barcode'){
	$sql = "
	select il.location_barcode,CONCAT(goods.goods_name,' ',IF (goods_style.goods_color = '' or goods_style.goods_color is null , ifnull(style.color, ''), ifnull(goods_style.goods_color, '')))as goods_name, 
	reserve.shipment_id,reserve.reserved_quantity, reserve.out_quantity, reserve.status_id
	from romeo.inventory_location_reserve as reserve 
	LEFT JOIN romeo.inventory_location as il on il.inventory_location_id=reserve.inventory_location_id
	LEFT JOIN romeo.product_mapping AS mapping ON mapping.PRODUCT_ID = il.PRODUCT_ID
	LEFT JOIN ecshop.ecs_goods AS goods ON goods.goods_id = mapping.ECS_GOODS_ID
	LEFT JOIN ecshop.ecs_goods_style AS goods_style ON goods_style.style_id = mapping.ECS_STYLE_ID and goods_style.goods_id = mapping.ECS_GOODS_ID and goods_style.is_delete=0
	LEFT JOIN ecshop.ecs_style AS style on style.style_id = mapping.ECS_STYLE_ID
	where reserve.batch_pick_sn = '{$batch_pick_sn}'
    ORDER  BY il.location_barcode, goods.goods_name,style.color,reserve.shipment_id
   ";
   $result = $db->getAll($sql);
   //PP($result);die();
   //统计相同库位条码数量
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
}else if($act == 'dispatch_bill'){
	//发货单信息
	require_once('includes/lib_sinri_DataBasic.php');
	$BPSN_MAPPINGS=get_BPSN_mapping($batch_pick_sn);
	foreach ($BPSN_MAPPINGS as $no => $BPSN_MAPPING) {
    	$BPSN_MAPPINGS[$no]['shipping_status_name'] = get_shipping_status($BPSN_MAPPING['shipping_status']);
    }
    $smarty->assign('BPSN_MAPPINGS', $BPSN_MAPPINGS);
}
$smarty->assign('display_option', $display_option);
$smarty->assign("shipment_list", $result);
$smarty->assign("batch_pick_sn", $batch_pick_sn);


$smarty->display("oukooext/display_batch_pick_detail.htm");