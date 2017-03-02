<?php
/**
 * 实时库存余额查询
 * 
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require ("function.php");
admin_priv('current_inventory_balance_query');

//必须选定特定业务组
$session_party = $_SESSION['party_id'];
$sql = "select IS_LEAF from romeo.party where party_id = '{$session_party}' limit 1";
$is_leaf = $db->getOne($sql);
if($is_leaf == 'N'){
	die("请先选择具体业务组后再查询库存");
}
//获取当前业务组可操作的仓库
$facility_user_list = array_intersect_assoc(get_available_facility(),get_user_facility());
$facility_user_str = implode("','",array_keys($facility_user_list));
//仓库搜索
$select_facility = "";
$available_facility = input('available_facility');
if ($available_facility != - 1 && $available_facility != '') {
	$select_facility = $available_facility;
}else{
	$select_facility = $facility_user_str;
}
//全新/二手 搜索  
$status_id = "";
$is_new = input('is_new');
if ($is_new != - 1 && $is_new !== null) {
	$status_id .= " AND ii.status_id = '{$is_new}'";
}else{
	$status_id .= " AND (ii.STATUS_ID = 'INV_STTS_AVAILABLE' or ii.STATUS_ID = 'INV_STTS_USED')";
}
//商家编码搜索,商品编码搜索和商品名称搜索
$barcode = input('barcode');
$goods_name =input('goods_name');
$productCode =input('productCode');
if ($barcode != '') {
	$condition_2 .= " AND if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) = '{$barcode}' ";
}
if ($goods_name != '') {
	$condition_2 .= " AND p.product_name LIKE '%{$goods_name}%'";
}
if ($productCode !== '') {
	$ref = explode("_",$productCode);
    if($ref[1]){
    	$condition_2 .=" AND CONCAT_WS('_', g.goods_id, ifnull(gs.style_id,0)) = '{$productCode}' ";
    }else{
    	$condition_2 .=" AND g.goods_id = {$productCode} ";
    }
}
if($condition_2!=""){
	$sql=" SELECT p.product_name,f.facility_name,ii.status_id,sum(ii.quantity_on_hand_total) as quantity_total,sum(ii.quantity_on_hand_total*ii.unit_cost ) as inventory_balance,CONCAT_WS('_', g.goods_id, ifnull(gs.style_id,0)) AS goods_style_id "
		." FROM ecshop.ecs_goods g "
		." LEFT JOIN ecshop.ecs_goods_style gs on gs.goods_id = g.goods_id and gs.is_delete=0 "
		." LEFT JOIN romeo.product_mapping AS pm ON pm.ecs_goods_id = g.goods_id and pm.ecs_style_id = ifnull(gs.style_id,0)"
		." INNER JOIN romeo.product  AS p on pm.product_id = p.product_id "
		." INNER JOIN romeo.inventory_item AS ii on ii.product_id = pm.product_id "
		." INNER JOIN romeo.facility AS f on f.facility_id = ii.facility_id "
		." where pm.PRODUCT_MAPPING_ID is not null and g.goods_party_id = {$session_party} and g.is_delete = 0 AND f.facility_id in ('{$select_facility}')"
		.$status_id.$condition_2
		." group by ii.status_id,ii.product_id,f.facility_id";
	$goods_list = $db->getAll($sql);
	$smarty->assign('goods_list', $goods_list); 
}
$smarty->assign ( 'available_facility', $facility_user_list);
$smarty->display ( 'oukooext/current_inventory_balance_query.htm' );

function input($data){
	$data = isset($_REQUEST[$data]) ? trim($_REQUEST[$data]) : '';
	return $data;
}
?>
