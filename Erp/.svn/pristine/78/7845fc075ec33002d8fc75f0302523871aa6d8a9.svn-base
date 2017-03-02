<?php
/**
 * 查询商品库位(无商品数量)
 * 
 * @author created by cywang@leqee.com 2013/12/19
 */
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ("function.php");
require_once ('includes/debug/lib_log.php');


// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if (check_goods_common_party ()) {
	admin_priv ( 'cg_storage_common' );
} else {
	admin_priv ( 'cw_finance_storage_main', 'cg_storage', 'purchase_order' );
}

// 消息
$info = $_REQUEST ['info'];
$type = $_REQUEST ['type'];


$mtime = explode ( ' ', microtime () );
$start_time = $mtime [1] + $mtime [0];
$condition = getCondition ();

if($_REQUEST ['act'] == 'search')
{
	$sql = "SELECT f.facility_name, il.location_barcode, " .
			"CONCAT(goods.goods_name,' ',IF (goods_style.goods_color = '' or goods_style.goods_color is null , ifnull(style.color, ''), ifnull(goods_style.goods_color, '')))as goods_name, " .
			"goods_barcode, " .
			"if(status_id = 'INV_STTS_AVAILABLE', '全新', if(status_id = 'INV_STTS_USED','二手',status_id)) as status " .
			"from romeo.inventory_location AS il " .
			"LEFT JOIN romeo.location l on il.location_barcode = l.location_barcode " .
			"LEFT JOIN romeo.facility f on il.facility_id = f.facility_id " .
			"LEFT JOIN romeo.product_mapping AS mapping ON mapping.PRODUCT_ID = il.PRODUCT_ID " .
			"LEFT JOIN ecshop.ecs_goods AS goods ON goods.goods_id = mapping.ECS_GOODS_ID " .
			"LEFT JOIN ecshop.ecs_goods_style AS goods_style ON goods_style.style_id = mapping.ECS_STYLE_ID and goods_style.goods_id = mapping.ECS_GOODS_ID and goods_style.is_delete=0 " .
			"LEFT JOIN ecshop.ecs_style AS style on style.style_id = mapping.ECS_STYLE_ID " .
			" where " . party_sql ( 'il.party_id' ) . 
			" AND " . facility_sql ( 'il.facility_id' ) . 
			" AND l.location_type = 'IL_LOCATION' " .
			" AND il.goods_number > 0 {$condition}" .
			" ORDER BY il.location_barcode";
	// Qlog::log('Get inventory_list_without_goods_number SQL: '.$sql);
	$goods_list = $db->getAll($sql);
	$smarty->assign('goods_list', $goods_list);
	
	
	if ($type == '导出库存文件CSV') {
		admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );
		header ( "Content-type:application/vnd.ms-excel" );
		header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "库存汇总" ) . ".csv" );
		$out = $smarty->fetch ( 'oukooext/inventory_list_without_goods_number_csv.htm' );
		echo iconv ( "UTF-8", "GB18030", $out );
		exit ();
	}
	
	if ($type == '库存明细（含有效期）CSV') {
		admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );
		
		if(in_array($_SESSION['admin_name'],array('xlhong','hluo1','ljzhou','mjzhou','lnyan'))) {
			$download_path = ROOT_PATH.'admin/download/';
			$filename2 = "库存明细（含有效期）.xls"; 
			$filename = "test.xls"; 
			$file =fopen($download_path.$filename,"r"); 
			Header("Content-type:application/octet-stream"); 
			Header("Accept-Ranges:bytes"); 
			header("Content-Type:application/msexcel"); 
			Header("Accept-Length:".filesize($download_path.$filename)); 
			Header("Content-Disposition:attachment;filename=".$filename2); 
			echo 
			fread($file,filesize($download_path.$filename)); 
			fclose($file);
			exit ();
		} else {
			die('你没有权限！');
		}
		
	}
}

$is_third_party_warehouse = check_admin_priv ( 'third_party_warehouse' ) && ($_SESSION ['action_list'] != 'all');// 第三方仓库要屏蔽商品分类搜索 ljzhou 2013.04.23
$smarty->assign ('is_third_party_warehouse', $is_third_party_warehouse );
$smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
$smarty->assign( 'available_facility', array_intersect_assoc ( get_available_facility (), get_user_facility () ) );
$smarty->display('oukooext/inventory_list_without_goods_number.htm');
exit();

function getCondition() {
	global $ecs;
	
	$condition = "";
	$goods_cagetory = $_REQUEST ['goods_cagetory'];
	$location_type = $_REQUEST ['location_type'];
	$other_condition = $_REQUEST ['other_condition'];
	$barcode = trim ( $_REQUEST ['barcode'] );
	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$is_new = $_REQUEST ['is_new'];
	$available_facility = $_REQUEST ['available_facility'];
	$start_validity_time = $_REQUEST ['start_validity_time'];
	$end_validity_time = $_REQUEST ['end_validity_time'];
	
	/*if ($location_type != -1) {
		$condition .= "AND l.location_type = '{$location_type}' ";
	}*/
	
	if ($goods_cagetory != - 1 && $goods_cagetory !== null) {
		switch ($goods_cagetory) {
			case 1 : // 手机
				$condition .= " AND goods.top_cat_id = 1";
				break;
			case 2 : // 配件
				$condition .= " AND goods.top_cat_id = 597";
				break;
			case 3 : // 小家电
				$condition .= " AND goods.top_cat_id NOT IN (1, 597, 1109) AND goods.cat_id != 1157 ";
				break;
			// 1157是OPPO DVD, 1109是特殊商品
			case 4 : // DVD
				$condition .= " AND goods.cat_id = 1157 ";
				break;
			case 5 : // 电教品
				$condition .= " AND goods.top_cat_id = 1458 ";
				break;
			case 6 : // 礼品
				$condition .= " AND goods.top_cat_id = 1367 ";
				break;
			case 7 : //
				$condition .= " AND goods.top_cat_id = 1515 ";
				break;
			case 8 :
				$condition .= " AND goods.top_cat_id = 1516 ";
				break;
		}
	}
	
	if ($barcode != '') {
		$condition .= " AND if(goods_style.barcode is NULL or goods_style.barcode = '',goods.barcode,goods_style.barcode) LIKE '%{$barcode}%' ";
	}
	if ($goods_name != '') {
		$condition .= " AND CONCAT(goods.goods_name,' ',IF (goods_style.goods_color = '' or goods_style.goods_color is null , ifnull(style.color, ''), ifnull(goods_style.goods_color, ''))) LIKE '%{$goods_name}%'";
	}
	if ($is_new != - 1 && $is_new !== null) {
		$condition .= " AND il.status_id = '{$is_new}'";
	}
	//1."未上架，但库存有货"、2."已上架，有库存非在售商品"
	if ($other_condition == 1) {
		$condition .= " AND goods.is_on_sale = 0 ";
	} elseif ($other_condition == 2) {
		$condition .= " AND goods.is_on_sale = 1 AND goods.sale_status != 'normal'";
	}
	//仓库
	if ($available_facility != - 1 && $available_facility != '') {
		$condition .= " AND il.facility_id = '{$available_facility}' ";
	}
	
	return $condition;
}

?>
