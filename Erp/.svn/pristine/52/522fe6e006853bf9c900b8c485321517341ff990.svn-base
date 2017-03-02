<?php
/**
 * 中粮库存同步查询
 * */
define('IN_ECS', true);
require('includes/init.php');
require_once('function.php');
//查看权限
admin_priv('zhongliang_sync_info');

$act = isset($_REQUEST['act']) ? $_REQUEST['act'] : null;

$inventories =array();

//分页信息
$page = intval($_REQUEST['page']);
$page = $page > 1 ? $page :1;
$limit = 50;
$offset = ($page-1)*$limit;
$total = 0;

if($act == 'search') {
	
	$date = date('Y-m-d');
	//条件判断
	$condition = get_condition($_REQUEST);

	//sql主体
	$sql_body = " FROM ecshop.express_best_product ebp
	LEFT JOIN express_best_facility_warehouse_mapping ebfwm ON ebfwm.warehouse_code = ebp.warehouse_code
	LEFT JOIN ecshop.ecs_goods eg ON eg.goods_id = substring_index(ebp.sku_code,'_',1)
	LEFT JOIN romeo.product_mapping pm ON pm.ecs_goods_id = eg.goods_id AND concat(pm.ecs_goods_id,'_',pm.ecs_style_id) = ebp.sku_code
	LEFT JOIN romeo.product p ON p.product_id = pm.product_id
	LEFT JOIN romeo.inventory_item ii ON ii.PRODUCT_ID = p.product_id AND ii.status_id = 'INV_STTS_AVAILABLE' AND ii.facility_id = ebfwm.facility_id
	LEFT JOIN romeo.inventory_item_detail iid ON ii.INVENTORY_ITEM_ID = iid.INVENTORY_ITEM_ID
	AND iid.CREATED_STAMP < '$date'
	WHERE ebp.party_id = 65625   {$condition['normal']}
	and  ebp.warehouse_code !='EC_XM_WH'
	group by p.PRODUCT_ID, ii.facility_id {$condition['having']}
	";
	//查询sql
	
	$sql = "SELECT ebp.sku_code, ebp.normal_quantity as cofco_inventory ,p.product_name,
	IFNULL(SUM(iid.quantity_on_hand_diff),0) as ERP_inventory,
	ebfwm.facility_name,ebp.normal_quantity-IFNULL(SUM(iid.quantity_on_hand_diff),0) as diff_number
	$sql_body limit $limit offset $offset ";
	//查询个数sql
	$sqlc = "SELECT count(*) FROM (SELECT SUM(iid.quantity_on_hand_diff), ebp.normal_quantity $sql_body) s ";
	//查询中粮仓库映射关系sql
	
	global $db;
	//结果展示
	$inventories = $db->getAll($sql);
	//总数，分页所需
	$total = $db->getOne($sqlc);

}

//分页属性
$Pager = Pager($total,$limit,$page);
	
//仓库列表展示
$sql_facility = "SELECT facility_id, facility_name,warehouse_code FROM ecshop.express_best_facility_warehouse_mapping";
$facility_result = $db->getAll($sql_facility);
$facility = array();
foreach($facility_result as $item){
	$facility[$item['facility_id']] = $item['facility_name'];
}

$smarty->assign('Pager',$Pager);
$smarty->assign('inventories',$inventories);
$smarty->assign('facility',$facility);
$smarty->display('oukooext/inventory_cofco_sync.htm');

/**
 * 页面的筛选条件
 * */
function get_condition($args){
	$condition['normal'] = '';
	
	if(isset($args['facility']) && $args['facility'] != -1){
		$condition['normal'] .= " AND ebfwm.facility_id = '{$args['facility']}' ";
	}
	$name = trim($args['name']);
	if($name){
		$condition['normal'] .= " AND p.product_name like '$name%' ";
	}
	$goods_out_id = trim($args['goods_out_id']);
	
	if($goods_out_id){
		$condition['normal'] .=" AND ebp.sku_code like '$goods_out_id%' ";
	}
	if($args['show_unequal']){
		$condition['having']= " HAVING ifnull(SUM(iid.quantity_on_hand_diff),0) <> ebp.normal_quantity";
	}
	return $condition;
}
?>
