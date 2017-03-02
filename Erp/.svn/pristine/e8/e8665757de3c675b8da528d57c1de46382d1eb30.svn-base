<?php

require_once('init.php');

/* 
 * 每个数组中记录的是权限 
 * */
global $inventory_watch_role;
$inventory_watch_role = array('VOrderApply','VOrderCheckZhuguan','VOrderCheckBD','VOrderCheckCaiwu','VOrderCheckCEO1','VOrderCheckCEO0');

global $inventory_apply_role;
$inventory_apply_role = array('VOrderApply','VOrderCheckZhuguan');

global $inventory_shop_role;
$inventory_shop_role = array('VOrderCheckZhuguan');

global $inventory_bd_role;
$inventory_bd_role = array('VOrderCheckBD');

global $inventory_finance_role;
$inventory_finance_role = array('VOrderCheckCaiwu');

global $inventory_ceo1_role;
$inventory_ceo1_role = array('VOrderCheckCEO1');

global $inventory_ceo0_role;
$inventory_ceo0_role = array('VOrderCheckCEO0');

/*
 * 记录阈值，超过阈值的订单，需要CEO审核
* */
global $limit_amount;
$limit_amount = 30000.00;

/*
 * 查询用户审核角色
 * @return 0-5 分别是申请者-审核者对应的角色ID
* */
function showRole(){
	global $inventory_watch_role,$inventory_apply_role,$inventory_shop_role;
	global $inventory_bd_role,$inventory_finance_role,$inventory_ceo1_role;
	global $inventory_ceo0_role;
	$action_list = $_SESSION ['action_list'];
	if ($action_list == "" || empty($action_list) ) {
		return -1;
	}
	if ($action_list == "all") {
		return 5;
	}
	$action_list_arr = explode(',', $action_list);
	
	if( count( array_intersect($action_list_arr, $inventory_ceo0_role) ) > 0 ){
		return 5;
	}elseif (count( array_intersect($action_list_arr, $inventory_ceo1_role) ) > 0){
		return 4;
	}elseif (count( array_intersect($action_list_arr, $inventory_finance_role) ) > 0){
		return 3;
	}elseif (count( array_intersect($action_list_arr, $inventory_bd_role) ) > 0){
		return 2;
	}elseif (count( array_intersect($action_list_arr, $inventory_shop_role) ) > 0){
		return 1;
	}elseif (count( array_intersect($action_list_arr, $inventory_apply_role) ) > 0){
		return 0;
	}else{
		return -1;
	}
	
}

/*
 * 查询用户系统角色，已经弃用
* @return 系统角色ID 在数据库  ecshop.ecs_admin_user中roles字段
* */
function getRole( $admin_id ){
	global $db;
	$admin_id = intval(trim($admin_id));
	$role_array = array();
	$sql = "select roles from ecshop.ecs_admin_user where user_id = '{$admin_id}'";
	$roles_str = $db->getOne($sql);
	$role_array = explode(",", $roles_str);
	return $role_array;
}


function checkVWatch(){
	$role = showRole();
	if( $role != -1 ){
		return true;
	}else{
		return false;
	}
}

function checkVApply(){
	$role = showRole();
	if( $role == 0 || $role == 1 ){
		return true;
	}else{
		return false;
	}
}

function checkVShop(){
	$role = showRole();
	if( $role == 1 ){
		return true;
	}else{
		return false;
	}
}

function checkVBD(){
	$role = showRole();
	if( $role == 2 ){
		return true;
	}else{
		return false;
	}
}

function checkVFinance(){
	$role = showRole();
	if( $role == 3 ){
		return true;
	}else{
		return false;
	}
}

function checkVCEO1(){
	$role = showRole();
	if( $role == 4 ){
		return true;
	}else{
		return false;
	}
}

function checkVCEO0(){
	$role = showRole();
	if( $role == 5 ){
		return true;
	}else{
		return false;
	}
}

/*
 * 系统遗留问题，这里进行了每个步骤评论的封装，显示的应该交给前台，需要修改
* */
function get_vorder_review_note($vorder_id){
  	global $db;
	$sql = "select * from ecshop.ecs_vorder_request_info where vorder_request_id = '{$vorder_id}'";
	$tmp = $db->getAll($sql);
	$title = array("店员","店长","BD","财务","执行CEO","CEO");
	$index = 0;
	$current_user_id = $tmp[0]["step".$index."_user_id"];
	$string = "";
	while(!empty($current_user_id)){
		$string .= $tmp[0]["step".$index."_time"]."&nbsp;&nbsp;&nbsp;&nbsp;".get_user_name($tmp[0]["step".$index."_user_id"])."&nbsp;&nbsp;&nbsp;&nbsp;";
		if ($index == 0) {
			$string .= $title[$index]."发起申请 &nbsp;&nbsp;&nbsp;&nbsp;";
		}elseif($tmp[0]["step".$index."_status"] == "1" ){
			$string .= $title[$index]."审核通过&nbsp;&nbsp;&nbsp;&nbsp; ";
		}/* elseif($tmp[0]["step".$index."_status"] == "0" ){
			$string .= $title[$index]."审核拒绝&nbsp;&nbsp;&nbsp;&nbsp; ";
		} */
		$string .= $tmp[0]["step".$index."_comment"]."<br/>";
		$index++;
		$current_user_id = $tmp[0]["step".$index."_user_id"];
	}
	if ($tmp['vorder_status'] == 'REFUSE'){
		$refuse_user = intval($tmp[0]['check_status']) - 1;
		$string .=  get_user_name($tmp[0]["step".$refuse_user."_user_id"])."&nbsp;&nbsp;&nbsp;&nbsp;拒绝了该审核<br/>";
	}
	return $string;
}

function get_user_name($user_id){
	global $db;
	$sql = "select user_name from ecshop.ecs_admin_user where user_id = '{$user_id}'";
	$tmp = $db->getOne($sql);
	return $tmp;
}



function get_goods_list_like_lcji($keyword = '', $limit = 100)
{
	global $db;
	$conditions = '';

	if (trim($keyword)) {
		$keyword = mysql_like_quote($keyword);
		$conditions .= " AND g.goods_name LIKE '%{$keyword}%'";
	}

	$sql = 
			"select g.goods_id, g.cat_id, gs.style_id, CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color) ) as goods_name
			from ecshop.ecs_goods as g
			left join ecshop.ecs_goods_style as gs on gs.goods_id = g.goods_id and gs.is_delete=0
			left join ecshop.ecs_style as s on gs.style_id = s.style_id
			where ( g.is_on_sale = 1 AND g.is_delete = 0 ) 
			AND g.goods_party_id = '{$_SESSION["party_id"]}' ".$conditions. " LIMIT {$limit}";
	return $db->getAll($sql);
}

function createInventoryItemVarianceByProductId_lcji($productId, $inventoryItemAcctTypeName,
		$inventoryItemTypeName, $statusId, $serialNumber, $quantityOnHandVar,
		$availableToPromiseVar, $physicalInventoryId, $unitCost, $facilityId,$comments,$orderId,$orderGoodsId,$actionUser) {
	require_once ROOT_PATH.'RomeoApi/lib_inventory.php';
	global $soapclient;
	$actionUser = 'cronjob';
	$containerId = facility_get_default_container_id($facilityId);
	$providerId = get_self_provider_id();
	$keys = array('productId'					=>'StringValue',
			'inventoryItemAcctTypeName'	=>'StringValue',
			'inventoryItemTypeName' 		=>'StringValue',
			'statusId'					=>'StringValue',
			'serialNumber'				=>'StringValue',
			'quantityOnHandVar'			=>'NumberValue',
			'availableToPromiseVar'		=>'NumberValue',
			'unitCost'					=>'NumberValue',
			'facilityId'					=>'StringValue',
			'containerId'					=>'StringValue',
			'actionUser'					=>'StringValue',
			'physicalInventoryId' 		=>'StringValue',
			'providerId' 					=>'StringValue',
			'comments'					=>'StringValue',
			'orderId' 					=>'StringValue',
			'orderGoodsId' 				=>'StringValue');
	$param = new HashMap();
	foreach ($keys as $key => $type) {
		if(${$key} == null) { continue; }
		$gv = new GenericValue();
		$method = 'set'.$type;
		$gv->$method(${$key});
		$param->put($key, $gv->getObject());
	}
	$result = $soapclient->createInventoryItemVarianceByProductId(array('arg0'=>$param->getObject()));
	$return_hashmap = new HashMap();
	$return_hashmap->setObject($result->return);
	return $return_hashmap;
}
