<?php
/**
 * 库存清单
 * 
 * @author last modified by ncchen@oukoo.com 2009/07/03
 */
define ( 'IN_ECS', true );
require ('../includes/init.php');
require ("../function.php");
require_once ('includes/debug/lib_log.php');


if(isset($_REQUEST['to_print']) && $_REQUEST['to_print']=='1'){
	$smarty->assign('to_print','1');
}

// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if (check_goods_common_party ()) {
	admin_priv ( 'cg_storage_common' );
} else {
	admin_priv ( 'cw_finance_storage_main', 'cg_storage', 'purchase_order' );
}

$can_view_storage_number = false;
if( check_admin_priv ( 'ck_view_storage_number' )) {
	$can_view_storage_number = true;
}

if (trim ( $_REQUEST ['storage'] ) == 'menu') {
	$_REQUEST ['label'] = 'storage_list_summary';
}

// 消息
$info = $_REQUEST ['info'];
$label = $_REQUEST ['label'];
$labels = array (
	'storage_list' => '库存清单', 
	'storage_list_summary' => '库存汇总'
);

//$type = $_REQUEST ['type'];
//if ($type == '库存清单CSV' || $type == '库存汇总CSV' || $type == '类别汇总CSV') {
//	admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );
//	$label = '';
//}

$mtime = explode ( ' ', microtime () );
$start_time = $mtime [1] + $mtime [0];
$condition = getCondition ();


if ($label == 'storage_list' && trim ( $_REQUEST ['act'] ) == 'search') { //|| $type == '库存清单CSV'
	
	$sql = "
		SELECT g.goods_id, ii.product_id,g.goods_party_id as party_id,ii.currency,ii.inventory_item_acct_type_id as order_type,f.facility_id,f.facility_name,
			CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, '')))as goods_name,
			ii.status_id, g.goods_warranty,ii.inventory_item_type_id as is_serial,
			concat_ws('-',pm.product_id,ii.status_id,ii.facility_id) as product_key,
			if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode, 
			group_concat(distinct ii.serial_number) as serial_numbers,
			sum(ii.QUANTITY_ON_HAND_TOTAL) as storage_count
		FROM ecshop.ecs_goods AS g
			LEFT JOIN romeo.product_mapping AS pm ON g.goods_id = pm.ecs_goods_id
			LEFT JOIN ecshop.ecs_goods_style AS gs ON gs.style_id = pm.ecs_style_id and gs.goods_id = pm.ecs_goods_id and gs.is_delete=0
			LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id 
			LEFT JOIN romeo.inventory_item AS ii ON pm.product_id = ii.product_id
			LEFT JOIN romeo.facility f ON ii.facility_id = f.facility_id
		WHERE " . party_sql ( 'g.goods_party_id' ) . "
			AND " . facility_sql ( 'ii.facility_id' ) . "
			AND ii.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') 
			AND ii.QUANTITY_ON_HAND_TOTAL > 0
			{$condition}
		GROUP BY product_key 
		ORDER BY g.goods_id
	";

	$storage_list_value = $storage_list_key = array ();
	$storage_list = $db->getAllRefBy ( $sql, array ('product_key' ), $storage_list_key, $storage_list_value );
	
	$sql = "
		SELECT 
			concat_ws('-',il.product_id,il.status_id,il.facility_id) as product_key,
			group_concat(distinct il.location_barcode) as location_barcode,
			sum(il.goods_number) as storage_count
		FROM romeo.inventory_location il 
			inner join romeo.location l on il.location_id = l.location_id
		WHERE " . party_sql ( 'il.party_id' ) . "
			AND " . facility_sql ( 'il.facility_id' ) . "
			AND il.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') 
			AND il.goods_number > 0
			AND l.location_type = 'IL_LOCATION'
		group by product_key
	";

	$validity_list_value = $validity_list_key = array ();
	$validity_list = $db->getAllRefBy ( $sql, array ('product_key' ), $validity_list_key, $validity_list_value );
	foreach ( $storage_list as $key => $storage ) {
		$storage_list [$key] ['product_key'] = $validity_list_value ['product_key'] [$storage ['product_key']];
		// 屏蔽数量,连串号一起屏蔽
		if(!$can_view_storage_number) {
			$storage_list [$key] ['storage_count'] = '***';
			$storage_list [$key] ['serial_numbers'] = null;
		} 
		else 
		{
			if(!empty($storage['serial_numbers'])) {
				$storage_list [$key] ['serial_numbers_str'] = $storage['serial_numbers'];
				$storage_list [$key] ['serial_numbers'] = explode(',',$storage['serial_numbers']);
			}
		}
	}

	$smarty->assign ( 'info', $info );
} else if ($label == 'storage_list_summary' && trim ( $_REQUEST ['act'] ) == 'search') { //|| $type == '库存汇总CSV'
	$sql = "
		SELECT g.goods_id, ii.product_id,g.goods_party_id as party_id,ii.currency,ii.inventory_item_acct_type_id as order_type,f.facility_id,f.facility_name,
			CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, '')))as goods_name,
			ii.status_id, g.goods_warranty,ii.inventory_item_type_id as is_serial,
			concat_ws('-',pm.product_id,ii.status_id,ii.facility_id) as product_key,
			if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode, 
			group_concat(distinct ii.serial_number) as serial_numbers,
			sum(ii.QUANTITY_ON_HAND_TOTAL) as storage_count
		FROM ecshop.ecs_goods AS g
			LEFT JOIN romeo.product_mapping AS pm ON g.goods_id = pm.ecs_goods_id
			LEFT JOIN ecshop.ecs_goods_style AS gs ON gs.style_id = pm.ecs_style_id and gs.goods_id = pm.ecs_goods_id and gs.is_delete=0
			LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id 
			LEFT JOIN romeo.inventory_item AS ii ON pm.product_id = ii.product_id
			LEFT JOIN romeo.facility f ON ii.facility_id = f.facility_id
		WHERE " . party_sql ( 'g.goods_party_id' ) . "
			AND " . facility_sql ( 'ii.facility_id' ) . "
			AND ii.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') 
			AND ii.QUANTITY_ON_HAND_TOTAL > 0
			{$condition}
		GROUP BY product_key 
		ORDER BY g.goods_id
	";

	$storage_list_value = $storage_list_key = array ();
	$storage_list = $db->getAllRefBy ( $sql, array ('product_key' ), $storage_list_key, $storage_list_value );
	$sql = "
		SELECT 
			concat_ws('-',il.product_id,il.status_id,il.facility_id) as product_key,
			group_concat(distinct il.location_barcode) as location_barcode,
			sum(il.goods_number) as storage_count
		FROM romeo.inventory_location il 
			inner join romeo.location l on il.location_id = l.location_id
		WHERE " . party_sql ( 'il.party_id' ) . "
			AND " . facility_sql ( 'il.facility_id' ) . "
			AND il.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') 
			AND il.goods_number > 0
			AND l.location_type = 'IL_LOCATION'
		group by product_key
	";

	$validity_list_value = $validity_list_key = array ();
	$validity_list = $db->getAllRefBy ( $sql, array ('product_key' ), $validity_list_key, $validity_list_value );
	foreach ( $storage_list as $key => $storage ) {
		$storage_list [$key] ['product_key'] = $validity_list_value ['product_key'] [$storage ['product_key']];
		// 屏蔽数量,连串号一起屏蔽
		if(!$can_view_storage_number) {
			$storage_list [$key] ['storage_count'] = '***';
			$storage_list [$key] ['serial_numbers'] = null;
		} 
		else 
		{
			if(!empty($storage['serial_numbers'])) {
				$storage_list [$key] ['serial_numbers_str'] = $storage['serial_numbers'];
				$storage_list [$key] ['serial_numbers'] = explode(',',$storage['serial_numbers']);
			}
		}
	}
	
}

$mtime = explode ( ' ', microtime () );
$end_time = $mtime [1] + $mtime [0];
$cost_time = round ( $end_time - $start_time, 2 );

$smarty->assign ( 'storage_list', $storage_list );
$smarty->assign('label', $label);

// 第三方仓库要屏蔽商品分类搜索 ljzhou 2013.04.23
$is_third_party_warehouse = false;
if (check_admin_priv ( 'third_party_warehouse' ) && ($_SESSION ['action_list'] != 'all')) {
	$is_third_party_warehouse = true;
}
$smarty->assign ( 'is_third_party_warehouse', $is_third_party_warehouse );

$smarty->assign ( 'labels', $labels );
$smarty->assign ( 'cost_time', $cost_time );
//$smarty->assign ( 'available_facility', array_intersect_assoc ( get_available_facility (), get_user_facility () ) );

//if ($type == '库存汇总CSV') {
//	admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );
//	header ( "Content-type:application/vnd.ms-excel" );
//	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "库存汇总清单" ) . ".csv" );
//	$out = $smarty->fetch ( 'oukooext/inventory_list_csv.htm' );
//	echo iconv ( "UTF-8", "GB18030", $out );
//	exit ();
//} elseif ($type == '库存清单CSV') {
//	admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );
//	header ( "Content-type:application/vnd.ms-excel" );
//	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "库存清单" ) . ".csv" );
//	$out = $smarty->fetch ( 'oukooext/inventory_list_detail_csv.htm' );
//	QLog::log ( $out );
//	echo iconv ( "UTF-8", "GB18030", $out );
//	exit ();
//}else {
	$smarty->assign ( 'facility_name', facility_mapping ( $_SESSION ['facility_id'] ) );
	$smarty->display ( 'sale_support/customerService_inventory_list.htm' );
//}

function getCondition() {
	global $ecs;
	
	$condition = "";
	$barcode = trim ( $_REQUEST ['barcode'] );
	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$is_new = $_REQUEST ['is_new'];
//	$available_facility = $_REQUEST ['available_facility'];
//	$start_validity_time = $_REQUEST ['start_validity_time'];
//	$end_validity_time = $_REQUEST ['end_validity_time'];
	
	// 到期时间搜索
	//date_format(il.validity,'%Y-%m-%d') as validity, date_format(DATE_ADD(il.validity,INTERVAL goods.goods_warranty month),'%Y-%m-%d') as expire
//	if ($start_validity_time || $end_validity_time) {
//		if ($start_validity_time) {
//			$condition .= " AND date_format(DATE_ADD(il.validity,INTERVAL g.goods_warranty month),'%Y-%m-%d') >= '{$start_validity_time}' ";
//		}
//		if ($end_validity_time) {
//			$condition .= " AND date_format(DATE_ADD(il.validity,INTERVAL g.goods_warranty month),'%Y-%m-%d') < '{$end_validity_time}' ";
//		}
//	}
	
	if ($barcode != '') {
		$condition .= " AND if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) LIKE '%{$barcode}%' ";
	}
	if ($goods_name != '') {
		$condition .= " AND CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, ''))) LIKE '%{$goods_name}%'";
	}
	if ($is_new != - 1 && $is_new !== null) {
		$condition .= " AND ii.status_id = '{$is_new}'";
	}

//	//仓库
//	if ($available_facility != - 1 && $available_facility != '') {
//		$condition .= " AND ii.facility_id = '{$available_facility}' ";
//	}
	
	return $condition;
}

?>
