<?php
/**
 * 菜鸟物流百威库存同步查询
 * */
define('IN_ECS', true);
require('includes/init.php');
require_once('function.php');

//查看权限
admin_priv('bird_inventory_sync_info');
$date = date('Y-m-d');

//条件判断
$condition = get_condition($_REQUEST);

//分页信息
$page = intval($_REQUEST['page']);
$page = $page > 1 ? $page :1;
$limit = 50;
$offset = ($page-1)*$limit;
$session_party_id = $_SESSION['party_id'];
global $db;
$sql = "select facility_id,outer_id,goods_name,leqee_quantity,bird_quantity,defective_quantity from ecshop.express_bird_inventory" .
		" where party_id={$session_party_id}  {$condition['normal']}";
$sqlc = "SELECT count(*) FROM ecshop.express_bird_inventory  where party_id={$session_party_id}  {$condition['normal']} ";
//结果展示
$inventories = $db->getAll($sql);
//总数，分页所需
$total = $db->getOne($sqlc);
//分页属性
$Pager = Pager($total,$limit,$page);

//仓库列表展示  
$sql_facility='';
if($session_party_id==65614){
	//测试环境
//	$sql_facility = "SELECT FACILITY_ID, FACILITY_NAME from romeo.facility  where FACILITY_ID in ('144624934','144624935','144624936','144624937')";
	//正式环境
	$sql_facility = "SELECT FACILITY_ID, FACILITY_NAME from romeo.facility  where FACILITY_ID in ('224734292','149849263','149849264','149849265','149849266')";
}
else if($session_party_id==65558){
	//测试环境
//	$sql_facility = "SELECT FACILITY_ID, FACILITY_NAME from romeo.facility  where FACILITY_ID in ('144676339')";
	//正式环境
	$sql_facility = "SELECT FACILITY_ID, FACILITY_NAME from romeo.facility  where FACILITY_ID in ('173433261','173433262','173433263','173433264','173433265')";
}
else if($session_party_id==65632){
	$sql_facility = "SELECT FACILITY_ID, FACILITY_NAME from romeo.facility  where FACILITY_ID in ('149849265')";
	
}
else if($session_party_id==65553) {
	$sql_facility = "SELECT FACILITY_ID, FACILITY_NAME from romeo.facility  where FACILITY_ID in ('224734292','149849263','149849264','149849265','149849266')";	
}
$facility_result = $db->getAll($sql_facility);

$facility = array();
foreach($facility_result as $item){
	$facility[$item['FACILITY_ID']] = $item['FACILITY_NAME'];
}


$smarty->assign('inventories',$inventories);
$smarty->assign('facility',$facility);
$smarty->assign('Pager',$Pager);
$smarty->display('oukooext/bird_wlb_items_update.htm');

/**
 * 页面的筛选条件
 * */
function get_condition($args){
	$condition['normal'] = '';
	if(isset($args['facility']) && $args['facility'] != -1){
		$condition['normal'] .= " AND facility_id = '{$args['facility']}' ";
	}
	$name = trim($args['name']);
	if($name){
		$condition['normal'] .= " AND goods_name like '%$name%' ";
	}
	$goods_out_id = trim($args['goods_out_id']);
	if($goods_out_id){
		$condition['normal'] .=" AND outer_id like '$goods_out_id%' ";
	}
	if($args['show_unequal']){
		$condition['normal'].= " and bird_quantity <> leqee_quantity";
	}
	return $condition;
}
?>
