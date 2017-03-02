<?php
/**
 * 库存有效期查询
 * 
 * @author last modified  2015/1/10
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require ("function.php");
require_once ('includes/debug/lib_log.php');
require_once(ROOT_PATH. 'includes/lib_order.php');
require_once(ROOT_PATH. 'RomeoApi/lib_inventory.php');
require_once('includes/lib_product_code.php');
require_once('includes/debug/lib_log.php');

$sql = "select IN_STORAGE_MODE FROM romeo.party where party_id = '{$_SESSION['party_id']}' limit 1 ";
$IN_STORAGE_MODE = $db->getOne($sql);
if($IN_STORAGE_MODE!=3){
	die("该页面暂时只对生产日期/批次维护 的业务组开放！");
}

// 根据用户party_id取得非欧酷（1）和欧酷派（4）下的所有子分类
require_once ROOT_PATH . 'includes/helper/array.php';
$categorys = $db->getAllCached("SELECT  cat_id, cat_name, parent_id FROM {$ecs->table('category')} WHERE party_id not in (1, 4) and is_delete = 0 and sort_order < 50 and " . party_sql('party_id'));// 取得所有分类
$refs = array();
Helper_Array::toTree($categorys, 'cat_id', 'parent_id', 'childrens', $refs);
$category_list = array();
foreach ($refs as $ref) {
	$categorys = Helper_Array::treeToArray($ref, 'childrens');
	foreach ($categorys as $category) {
    	if ($category['_is_leaf']) {
    	    $category_list[$category['cat_id']] = $category['cat_name'];
    	}
    }
}

// 消息
$info = $_REQUEST ['info'];
$type = $_REQUEST ['type'];

$mtime = explode ( ' ', microtime () );
$start_time = $mtime [1] + $mtime [0];
$condition = getCondition ();

$is_show_urikitamono = empty($_REQUEST['is_show_urikitamono'])?0:1;

if (($_REQUEST ['act']  == '搜索' && trim ( $_REQUEST ['act'] ) == '搜索') || ($type=="导出库存清单") ) {
	$sql = "
	 SELECT ifnull(il.location_barcode,(select ilg.location_barcode from romeo.inventory_location ilg 
			inner join romeo.location lg ON ilg.location_id = lg.location_id
			where ilg.product_id=ii.product_id and ilg.validity=ii.validity and ilg.facility_id=ii.facility_id 
			and ilg.goods_number>0 and lg.location_type='IL_GROUDING' limit 1)) as location_barcode,
 			ii.validity,ii.batch_sn,g.goods_id, ii.product_id,g.goods_party_id as party_id,ii.currency,c.cat_name,
	        IFNULL(gs.style_id, 0) AS style_id,
		    ii.inventory_item_acct_type_id as order_type,f.facility_id,f.facility_name,
			CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, '')))as goods_name,
			ii.status_id, ii.inventory_item_type_id as is_serial,
			concat_ws('_',g.goods_id,IFNULL(gs.style_id, 0),ii.facility_id) as goods_style_facility_id,
			if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode, 
			g.goods_warranty, 
			IF(pm.ecs_style_id ='' OR pm.ecs_style_id = NULL,pm.ecs_goods_id,concat_ws('_',pm.ecs_goods_id,pm.ecs_style_id)) as productCode,
			sum(ii.QUANTITY_ON_HAND_TOTAL) as storage_count,bzp.item_number,bzp.is_fragile,bzp.spec, egir.reserve_number 
		FROM ecshop.ecs_goods AS g
			LEFT JOIN ecshop.ecs_goods_style AS gs ON g.goods_id = gs.goods_id and gs.is_delete=0
			LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id 
			left join ecshop.ecs_category c on g.cat_id = c.cat_id 
			LEFT JOIN romeo.product_mapping AS pm ON g.goods_id = pm.ecs_goods_id and ifnull(gs.style_id,0) = pm.ecs_style_id
			LEFT JOIN romeo.inventory_item AS ii ON ii.product_id = pm.product_id
			LEFT JOIN romeo.inventory_location il ON ii.product_id = il.product_id
			   AND il.validity = ii.validity and il.facility_id = ii.facility_id and ii.status_id = il.status_id
			   AND il.location_barcode like '%-%'
			LEFT join romeo.location l on il.location_id = l.location_id 
			LEFT JOIN romeo.facility f ON ii.facility_id = f.facility_id 
			LEFT JOIN ecshop.ecs_goods_inventory_reserved egir ON egir.goods_id = g.goods_id AND egir.style_id = IFNULL(gs.style_id,0) AND egir.facility_id = f.facility_id AND g.goods_party_id = egir.party_id AND egir.status = 'OK' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE' 
			left join ecshop.brand_zhongliang_product as bzp on bzp.barcode = if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) 
		WHERE " . party_sql ( 'g.goods_party_id' ) . "
			AND " . facility_sql ( 'ii.facility_id' ) . "
			and g.is_delete = 0
			AND ii.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') 
			".($is_show_urikitamono==1?"":" AND ii.QUANTITY_ON_HAND_TOTAL > 0 " )."
			{$condition}
		GROUP BY goods_style_facility_id,ii.STATUS_ID,location_barcode,ii.validity 
		ORDER BY g.goods_id
	";
	
  $refs	= array();	
  $goods_list = $db->getAllRefby($sql,array('goods_style_facility_id'),$ref_fields, $refs, false); 
  if ($goods_list)
  {
    // 待查询商品ID
    $gIds = array();
    foreach ($goods_list as $item) {
        $gIds[] = $item['goods_id'];
    }
	

    //数据组合
    $goods_list = array();
	foreach ($refs['goods_style_facility_id'] as $goods_style_facility_id=>$goods) {
		foreach ($goods as $good) {
			$goods_warranty =  $good['goods_warranty'] ;
			$good['validity'] = substr($good['validity'],0,10);
			if($good['validity'] != ''){
				$good['1_3validity'] = date("Y-m-d",strtotime("{$good['validity']} +".($goods_warranty/3)." month"));
				$good['1_2validity'] = date("Y-m-d",strtotime("{$good['validity']} +".($goods_warranty/2)." month"));
			}
			$good['goods_last_day'] = date("Y-m-d",strtotime("{$good['validity']} +".($goods_warranty)." month"));
			$goods_list[] = $good;
		}
	}
	unset($order_value_list); 
	unset($purchase_value_list);
	unset($location_barcode_value_list);
	unset($supplier_return_number_value_list);
	unset($refs);
 }
}
$smarty->assign('goods_list', $goods_list);
$smarty->assign ( 'consumer_goods_list', $consumer_goods_list );

$smarty->assign('is_show_urikitamono',empty($is_show_urikitamono)?0:1);

// 第三方仓库要屏蔽商品分类搜索 ljzhou 2013.04.23
$is_third_party_warehouse = false;
if (check_admin_priv ( 'third_party_warehouse' ) && ($_SESSION ['action_list'] != 'all')) {
	$is_third_party_warehouse = true;
}
$smarty->assign ( 'is_third_party_warehouse', $is_third_party_warehouse );
$smarty->assign ( 'category_list',$category_list);
$smarty->assign ( 'available_facility', array_intersect_assoc ( get_available_facility (), get_user_facility () ) );
$smarty->assign ( 'party_id',$_SESSION['party_id'] );
if ($type == '导出库存清单') {
	// 生成Excel文档
        set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
        require_once 'PHPExcel.php';
        require_once 'PHPExcel/IOFactory.php';
        $filename = "库存清单".(empty($is_show_urikitamono)?"(仅有货)":"(有货和无货)");
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);        
        $sheet = $excel->getActiveSheet();
        $sheet->setCellValue('A1', "分类名称");
        $sheet->setCellValue('B1', "商品名称");
        $sheet->setCellValue('C1', "商家编码");
        $sheet->setCellValue('D1', "商品编码");
        $sheet->setCellValue('E1', "新旧");
        $sheet->setCellValue('F1', "仓库名称");
        $sheet->setCellValue('G1', "库位");
        $sheet->setCellValue('H1', "生产日期");
        $sheet->setCellValue('I1', "库存");
        $sheet->setCellValue('J1', "过1/3效期时间");
        $sheet->setCellValue('K1', "过1/2效期时间");
        $sheet->setCellValue('L1', "保质期");
        $sheet->setCellValue('M1', "截止日期");
        $sheet->setCellValue('N1', "批次号");
        $i=2;
        foreach ($goods_list as $item) {   
            $sheet->setCellValue("A{$i}", $item['cat_name']);
            $sheet->setCellValue("B{$i}", $item['goods_name']);
            $sheet->setCellValue("C{$i}", $item['productCode']);
            $sheet->setCellValue("D{$i}", $item['barcode']);
            if($item['status_id'] == INV_STTS_AVAILABLE){
               $sheet->setCellValue("E{$i}", '全新');
            }else {
               $sheet->setCellValue("E{$i}", '二手');
            }
            $sheet->setCellValue("F{$i}", $item['facility_name']);
            $sheet->setCellValue("G{$i}", $item['location_barcode']);
            $sheet->setCellValue("H{$i}", $item['validity']);
            $sheet->setCellValue("I{$i}", $item['storage_count']);
            $sheet->setCellValue("J{$i}", $item['1_3validity']);
            $sheet->setCellValue("K{$i}", $item['1_2validity']);
            $sheet->setCellValue("L{$i}", $item['goods_warranty']);
            $sheet->setCellValue("M{$i}", $item['goods_last_day']);
            $sheet->setCellValue("N{$i}", $item['batch_sn']);
            $i++;
        }
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        }
} else {

	$smarty->assign ( 'facility_name', facility_mapping ( $_SESSION ['facility_id'] ) );
	$smarty->display ( 'oukooext/inventory_validity_check.htm' );
}
function getCondition() {
	global $ecs;
	$condition = "";
	$category_id = trim ($_REQUEST ['category_id']);
	$barcode = trim ( $_REQUEST ['barcode'] );
	$goods_name = trim ( $_REQUEST ['goods_name'] );
	$is_new = $_REQUEST ['is_new'];
	$productCode =trim($_REQUEST['productCode']);
	$startCalendar = trim($_REQUEST['startCalendar']);
	$endCalendar = trim($_REQUEST['endCalendar']);
	$available_facility = $_REQUEST ['available_facility'];
	$goods_id = 0;
	$style_id = 0;
    if ($category_id != -1 && $category_id !== null ) {
		$condition .= " AND g.cat_id= '{$category_id}'";
	}
	if ($barcode != '') {
		$condition .= " AND if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) = '{$barcode}' ";
	}
	if ($goods_name != '') {
		$condition .= " AND CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, ''))) LIKE '%{$goods_name}%'";
	}
	if ($is_new != - 1 && $is_new !== null) {
		$condition .= " AND ii.status_id = '{$is_new}'";
	}
    if ($productCode !== '') {
    	$ref = explode("_",$productCode);
        $goods_id = $ref[0];
        $style_id = $ref[1] ? $ref[1] : 0;
		$condition .= " AND pm.ecs_style_id =$style_id AND pm.ecs_goods_id = $goods_id ";
	}
	if ($startCalendar !== '') {
		$condition .= " AND ii.validity > '{$startCalendar}'";
	}
	if ($endCalendar !== '') {
		$condition .= " AND ii.validity < '{$endCalendar}'";
	}
	//仓库
	if ($available_facility != - 1 && $available_facility != '') {
		$condition .= " AND ii.facility_id = '{$available_facility}' ";
	}
	return $condition;
}
?>
