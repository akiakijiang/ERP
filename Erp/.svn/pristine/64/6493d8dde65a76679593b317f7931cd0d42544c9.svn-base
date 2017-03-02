<?php

/**
 * 查询出入库明细
 */
define('IN_ECS', true);
require_once ('includes/init.php');
require (ROOT_PATH . 'admin/function.php');
require_once (ROOT_PATH . "RomeoApi/lib_inventory.php");

admin_priv('inventoryItemDetail');

$goods_name = trim($_REQUEST['goods_name']);
$goods_id1 = trim($_REQUEST['goods_id']);
$barcode = trim($_REQUEST['barcode']);
$providerSN = trim($_REQUEST['providerSN']);
$start_time = trim($_REQUEST['start_time']);
$end_time = trim($_REQUEST['end_time']);
$available_facility = $_REQUEST['available_facility'];
$party_id = $_SESSION['party_id'];
$in_out = $_REQUEST['in_out'];



$smarty->assign('goods_name', $goods_name);
$smarty->assign('goods_id', $goods_id1);
$smarty->assign('barcode', $barcode);
$smarty->assign('providerSN', $providerSN);
$smarty->assign('start_time', $start_time);
$smarty->assign('end_time', $end_time);
$smarty->assign ( 'available_facility', array_intersect_assoc ( get_available_facility (), get_user_facility () ) );
$message = "";
global $condition;


$goods_id = 0;
$style_id = -1;
if ( !empty($goods_id1) && ! empty($goods_name)) {
	$goods_id = $goods_id1;
}



if ($providerSN != null && $providerSN != "") {
	if (strstr($providerSN, "_")) {
		$array = explode("_", $providerSN);
		$goods_id = $array[0];
		$style_id = $array[1];
	} else {
		$goods_id = $providerSN;
		$style_id = 0;
	}
}

if ($available_facility != -1 && $available_facility != '') {
	$condition .= " AND ii.facility_id = '{$available_facility}' ";
}
if($in_out != 'all'){
	$sign = $in_out == 'in' ? " > " : " < ";
	$condition .= " AND iid.QUANTITY_ON_HAND_DIFF $sign 0";
}
if (!empty($barcode)) {
	$sql = "SELECT goods_id,style_id FROM ecshop.ecs_goods_style WHERE barcode = '{$barcode}' and is_delete=0 ";
	$result = $db->getRow($sql);
	if($result){
		$goods_id = $result['goods_id'];
		$style_id = $result['style_id'];
	}else{
		$sql = "SELECT goods_id FROM ecshop.ecs_goods WHERE barcode = '{$barcode}' and goods_party_id = $party_id ";
		$result = $db->getRow($sql);
		$goods_id = $result['goods_id'];
	}
}
if($goods_id != 0){
  $condition .= " and pm.ecs_goods_id = {$goods_id} ";
}
if($style_id != -1){
	$condition .= " and pm.ecs_style_id = {$style_id} ";
}


$smarty->assign('orders_len', 0);
if(empty($barcode) && $goods_id == 0 && $style_id == -1){
	$message = "若要查询，请务必先输入商品；导出不限制";
}else{
	$inventory_sum = 0;
	if (isset ($_POST['searchDetails'])) {
		$message = validateText($goods_name, $goods_id1, $barcode, $providerSN, $start_time, $end_time);
		if ($message == "") {
			global $slave_db;
			$sql = "select SUM(iid.QUANTITY_ON_HAND_DIFF) 
				from romeo.product_mapping as pm  
				INNER JOIN romeo.inventory_item as ii on ii.product_id = pm.product_id 
				INNER JOIN romeo.inventory_item_detail as iid on iid.inventory_item_id = ii.inventory_item_id 
				INNER JOIN ecshop.ecs_order_info as oi on oi.order_id = cast(iid.ORDER_ID as unsigned) -- and ii.facility_id = oi.facility_id		
				where oi.party_id = {$party_id} $condition 
				and ii.status_id in ('INV_STTS_USED', 'INV_STTS_AVAILABLE')" ;
			$inventory_sum = $slave_db->getOne($sql);
			$inventory_sum = $inventory_sum ? $inventory_sum :0; 
			$goods_orders = searchDetails($party_id, $condition);
			
			$smarty->assign('goods_orders', $goods_orders);
			$smarty->assign('orders_len', count($goods_orders));
		}
	}
}

if (isset ($_POST['exportToExcel'])) {
	$message = validateText($goods_name, $goods_id1, $barcode, $providerSN, $start_time, $end_time);
	if ($message == "") {
		$goods_orders = searchDetails($party_id, $condition);
		$smarty->assign('goods_orders', $goods_orders);
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:filename=" . iconv("UTF-8", "GB18030", "新库存出入库明细") . ".csv");
		$out = $smarty->fetch('oukooext/export_details_csv.htm');
		echo iconv("UTF-8", "GB18030", $out);
		exit;
	}
}

$smarty->assign('message', $message);
$smarty->assign('inventory_sum' , $inventory_sum);
function validateText($goods_name, $goods_id1, $barcode, $providerSN, $start_time, $end_time) {
	$message = "";
	global $condition,$db;
	if (!empty($barcode)) {
		$sql = "SELECT goods_id,style_id FROM ecshop.ecs_goods_style WHERE barcode = '{$barcode}' and is_delete=0 ";
		$result = $db->getRow($sql);
		if(!$result){
			$sql = "SELECT goods_id FROM ecshop.ecs_goods WHERE barcode = '{$barcode}' and goods_party_id = {$_SESSION['party_id']} ";
			$result = $db->getRow($sql);
			if(!$result){
				$message = "提示：输入的商品条码不存在！";
				return $message;
			}
		}
	}

	if ($start_time == null || $end_time == null || $start_time == "" || $end_time == "") {
		$message = "提示：请输入起止日期！";
	}
	elseif (strtotime($end_time) - strtotime($start_time) > 3600 * 24 * 30 * 6) {
		$message = "提示：日期间隔不能超过180天！";
	} else {
		$end_time = date('Y-m-d', strtotime("+1 days", strtotime($end_time)));
		$condition .= " and iid.created_stamp >= '{$start_time}' and iid.created_stamp < '{$end_time}' ";
	}
	return $message;
}

function searchDetails($party_id) {
	
	$order_type = array('SALE' => '销售',
						'PURCHASE' => '采购',
						'SUPPLIER_EXCHANGE' => '供应商换货',
						'BORROW' => '借机',
						'SUPPLIER_SALE' => '供应商销售',
						''
	);
	global $condition;
	/**
	 * 通过商品名称，条码，商家编码查询出新旧状态，订单号，订单时间，订单类型
	 */
	$sql = "select iid.created_stamp,f.facility_name,
			CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, '')))as goods_name, g.barcode,iid.QUANTITY_ON_HAND_DIFF ,
			if(ii.status_id = 'INV_STTS_AVAILABLE','全新','二手') as status_id ,
			oi.order_sn, oi.order_time , IFNULL(eboi.batch_order_sn, ebgm.batch_gt_sn) as batch_order_sn,
			case 
			when oi.order_type_id='SALE' then '销售'
			when oi.order_type_id='PURCHASE' then '采购'
			when oi.order_type_id='SUPPLIER_EXCHANGE' then '供应商换货'
			when oi.order_type_id='BORROW' then '借机'
			when oi.order_type_id='SUPPLIER_SALE' then '供应商销售'
			when oi.order_type_id='SUPPLIER_RETURN' then '供应商退货'
			when oi.order_type_id='RMA_RETURN' then '客户退货'
			when oi.order_type_id='RMA_EXCHANGE' then '客户换货'            
			when oi.order_type_id='SHIP_ONLY' then '补寄'
			when oi.order_type_id='VARIANCE_MINUS' then '盘亏'
			when oi.order_type_id='VARIANCE_ADD' then '盘盈' end
			as order_type_id
		from romeo.inventory_item_detail as iid force index(CREATED_STAMP)
			INNER JOIN romeo.inventory_item as ii on iid.inventory_item_id = ii.inventory_item_id
			INNER JOIN romeo.product as p on ii.product_id = p.product_id
			INNER JOIN romeo.product_mapping as pm on p.product_id = pm.product_id 
			INNER JOIN romeo.facility as f on ii.facility_id = f.facility_id
			INNER JOIN ecshop.ecs_order_info as oi on oi.order_id = cast(iid.ORDER_ID as unsigned)
			LEFT JOIN ecshop.ecs_goods as g on pm.ECS_GOODS_ID = g.goods_id
			LEFT JOIN ecshop.ecs_goods_style gs on pm.ECS_GOODS_ID = gs.goods_id and pm.ECS_STYLE_ID = gs.style_id  and gs.is_delete=0
			LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id
			LEFT JOIN ecshop.ecs_batch_order_mapping ebom ON ebom.order_id = oi.order_id
			LEFT JOIN ecshop.ecs_batch_order_info eboi ON eboi.batch_order_id = ebom.batch_order_id
			LEFT JOIN ecshop.ecs_batch_gt_mapping ebgm ON ebgm.order_id = oi.order_id
		where oi.party_id = {$party_id} " . $condition .
		" and ii.status_id in ('INV_STTS_USED', 'INV_STTS_AVAILABLE')" .
		" order by iid.created_stamp";
	
	$goods_orders = $GLOBALS['db']->getAll($sql);
	return $goods_orders;
}

$smarty->display('oukooext/inventory_item_detail.htm');
?>