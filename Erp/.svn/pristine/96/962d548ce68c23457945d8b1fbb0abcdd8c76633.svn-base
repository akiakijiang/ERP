<?php
define ( 'IN_ECS', true );
require_once ('../includes/init.php');
include_once (ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . "/RomeoApi/lib_inventory.php");
require_once ("../includes/lib_function_inventory.php");
require (ROOT_PATH . "/includes/lib_order.php");

$pageindex = 1;

//检查是否具有审核权限
admin_priv("VOrderCheckZhuguan","VOrderCheckWL");

$_priv = get_check_priv(); // 获得审核权限类型
$search_facility = 0;
$json = new JSON(); //维护全局json对象，减少内存使用
$not_out_order = array();
if (! party_explicit ( $_SESSION ['party_id'] ) && !check_allparty_priv() ) {
	exit ( '请选择分公司的party_id，再进行操作' );
}

/* 
 * 错误消息 格式
 * */
$error_message_array_smarty = array (
		status => 0,
		title => "操作完结",
		msg => array ()
);

/*
 * 单订单审核
 * */
if ($_POST ["act"] == "singlecheck") {
	/*
	 * key是订单id
	 * note是添加的备注
	* */
	$key = $_POST ["key"];
	$note = $_POST ["note"];
	$result = singlecheck ( $_priv, $key, $note );
	echo $json->encode($result);
	exit();
	
} 
/*
 * 订单批审核
 * */
else if ($_POST ["act"] == "batchcheck") {
	orderbatchcheck ( $_priv );
	exit ();
	
} 
/*
 * 单订单拒绝
 * */
 else if ($_POST ["act"] == "singlerefuse") {
	$key = $_POST ["key"];
	$note = $_POST ["note"];
	$result = singlerefuse ( $_priv,$key, $note);
	echo $json->encode($result);
	exit();
	
} 
/*
 * 批量进行拒绝
 * */
else if ($_POST ["act"] == "batchrefuse") {
	batchrefuse ( $_priv );
	exit ();
}
else if ($_POST['act'] == "facility") {
	$facility = $_REQUEST['facility_id'];
	if (!empty($facility)) {
		$not_out_order = find_not_out_order_v( $_priv,$facility,1 );
	}
	$search_facility = $facility;
}else if($_REQUEST['act'] == "page"){
	$facility = $_REQUEST['facility'];
	$pageindex = $_REQUEST['pageindex'];
	if (!empty($facility) && !empty($pageindex)) {
		$not_out_order = find_not_out_order_v( $_priv,$facility,$pageindex );
	}
}


/*
 * not_out_order格式： $not_out_order = array( $vorder_id => array( array(goods_name,...), array(goods_name,...), ... ), ... )
 */

if (check_apply_priv()) {
	$showall = 1;
}else{
	$showall = 0;
}//不同的审核者显示不同


$user_facility = get_user_facility();
$available_facility = get_available_facility ();
$available_facility = array_intersect($user_facility,$available_facility);
$available_facility['0'] = '请选择仓库';
$smarty->assign ( 'available_facility', $available_facility );
$smarty->assign ( 'search_facility', $search_facility );

$smarty->assign ( "pageindex", $pageindex ); //
$smarty->assign ( "showall", $showall ); //
$smarty->assign ( 'not_out_order', $not_out_order );
$smarty->assign ( 'user_current_party_name', party_mapping ( $_SESSION ['party_id'] ) );
// $smarty->display ( 'virance_inventory/physical_inventory_out_inventory_v3.htm' );
$smarty->display ( 'virance_inventory/inventory_adjust_out_v3.html' );

function orderbatchcheck($priv) {
	$json = new JSON ();
	/*
	 * key是订单id
	 * role是请求的角色
	 * note是添加的备注
	 * */
	$keys = $_POST ["keys"];
	$note = $_POST ["note"];
	
	$result = array (
			msg => "已经全部审核通过",
	);
	
	if (!check_check_priv()) {
		$result ['msg'] = "您不具有审核权限";
		echo $json->encode ( $result );
		exit ();
	}
	if ($priv != get_check_priv()) {
		$result ['msg'] = "请求权限和所具有权限不一致";
		echo $json->encode ( $result );
		exit ();
	}

	$keys_array = explode ( ",", $keys );
	
	foreach ( $keys_array as $key => $item ) {
		if (! empty ( $priv ) && ! empty ( $item )) {
			/*
			 * 对每个订单调用单独审核函数
			 * */
			$tmp = singlecheck( $priv, $item, $note );
			if (strcmp ( $tmp ["msg"], "ok" ) != 0) {
				$result ['msg'] .= "订单" . $item . "出现问题 " . $tmp ['msg'] . "\n";
			}
		} else {
			$result ['msg'] .= "订单" . $item . "参数出现问题 \n";
		}
	}
	echo $json->encode ( $result );
	exit ();
}

/*
 * 批拒绝
* */
function batchrefuse($priv) {
	$json = new JSON ();
	/*
	 * role 请求的角色
	 * keys，用逗号分隔，审核的所有订单id
	 * note是添加的备注
	* */
	$keys = $_POST ["keys"];
	$note = $_POST ["note"];
	
	$result = array (
			msg => "已经全部否决",
	);
	if (!check_check_priv()) {
		$result ['msg'] = "您不具有审核权限";
		echo $json->encode ( $result );
		exit ();
	}
	if ($priv != get_check_priv()) {
		$result ['msg'] = "请求权限和所具有权限不一致";
		echo $json->encode ( $result );
		exit ();
	}
	
	$keys_array = explode ( ",", $keys );
	
	foreach ( $keys_array as $key => $item ) {
		if (! empty ( $priv ) && ! empty ( $item ) ) {
			/* 
			 * 调用单独拒绝的函数，实现函数复用
			 *  */
			$tmp = singlerefuse ( $priv, $item, $note );
			if (strcmp ( $tmp ["msg"], "ok" ) != 0) {
				$result ['msg'] .= "订单" . $item . "出现问题 " . $tmp ['msg'] . "\n";
			}
		} else {
			$result ['msg'] .= "订单" . $item . "参数出现问题 \n";
		}
	}
	echo $json->encode ( $result );
	exit ();
}

/* 
 * 单独拒绝订单
 * @param key  订单id
 * @param role 请求的角色
 * @param note 备注
 *  */
function singlerefuse($role,$key,  $note) {
	global $db;

	$result = array (
			msg => "该单已经拒绝" 
	);
	
	if (!check_check_priv()) {
		$result ['msg'] = "您不具有审核权限";
		return $result;
	}
	if ($role != get_check_priv()) {
		$result ['msg'] = "请求权限和所具有权限不一致";
		return $result;
	}
	
	if (! isset ( $key )) {
		$result ["msg"] = "请求参数key不正确";
		return $result;
	}
	
	/* 确保上一步骤已经审核完毕 */
	$pre_step = $role - 1;
	$sql = "select step{$pre_step}_status from ecshop.ecs_vorder_request_info where vorder_request_id = '{$key}'";
	$pre_check = $db->getOne ( $sql );
	if ($pre_check == 0) {
		$result ["msg"] = "上一步骤还没审核";
		return $result;
	}
	
	$user_id = $_SESSION ["admin_id"];
	$next_role = $role + 1;
	
	$db->start_transaction (); // start transaction
	try {
		$sql = "update ecshop.ecs_vorder_request_info set step{$role}_status = 1, step{$role}_user_id = {$user_id},
		step{$role}_comment = '{$note}', step{$role}_time = NOW(), check_status = {$next_role}, vorder_status = 'REFUSE'
		where vorder_request_id = {$key}";
		$db->query ( $sql );
		$error_no = $db->errno ();
		if ($error_no > 0) {
			$result ["msg"] = "未调度成功，请联系ERP " . $error_no;
			$db->rollback();
			return $result;
		}
		$db->commit();
		return $result;
	} catch ( Exception $e ) {
		$db->rollback();
		$result ["msg"] = strval($e);
		return $result;
	}
}

/*
 * 单独审核订单
* @param key  订单id
* @param role 请求的角色 1-2（后台验证）
* @param note 备注
*  */
function singlecheck($role, $key, $note) {
	global $db, $limit_amount;

	$result = array (
			msg => "该单已经同意" 
	);
	
	if(!check_check_priv()){
		$result ["msg"] = "您不具有审核权限";
		return $result;
	}
	
	if (! isset ( $key )) {
		$result ["msg"] = "请求参数key不正确";
		return $result;
	}
	
	if ($role != get_check_priv()) {
		$result ["msg"] = "角色错误，请联系ERP";
		return $result;
	}
	
	$pre_step = $role - 1;
	$sql = "select step{$pre_step}_status as step,v_category from ecshop.ecs_vorder_request_info where vorder_request_id = '{$key}'";
	$tmp3 = $db->getAll ( $sql );
	$pre_check = $tmp3[0]['step'];
	$cat = $tmp3[0]['v_category'];
	if ($pre_check == 0) {
		$result ["msg"] = "上一步骤还没审核";
		return $result;
	}
	$user_id = $_SESSION ["admin_id"];
	$next_role = $role + 1;
	$db->start_transaction (); // 开始事务
	try {
		//确保没有双引号等其他符号
		$note = addslashes($note);
		$sql = "update ecshop.ecs_vorder_request_info set step{$role}_status = 1, step{$role}_user_id = {$user_id},
				step{$role}_comment = '{$note}', step{$role}_time = NOW(), check_status = {$next_role}
				where vorder_request_id = {$key}";
		$db->query ( $sql );
		$error_no = $db->errno ();
		if ($error_no > 0) {
			$result ["msg"] = "未调度成功，请联系ERP " . $error_no;
			$db->rollback ();
			return $result;
		}
		
		/* 
		 * 如果当前是物流审核，那么就应该出库了 
		 * */
		if (check_success()) {
			$msg = order_success ( $key ,$cat);
			if (strcmp ( $msg ["msg"], "ok" ) == 0) {
				$db->commit ();
				return $result;
			} else {
				/* 订单完结操作出错，那么就进行回滚，包括回滚审核，需要重新审核了 */
				$db->rollback ();
				$result ['msg'] = $msg ["error"] ;
				return $result;
			}
		}
		$db->commit ();
		return $result;
	} catch ( Exception $e ) {
		$db->rollback ();
		$result ['msg'] = strval($e) ;
		return $result;
	}
}

/*
 * 订单完结 
 * @param $vorder_request_id 订单号
 */
function order_success($vorder_request_id,$cat) {
	/* 
	 * 创建ecs_order_info条目，创建ecs_vorder_request_mapping条目。执行ecs_order_info里面新创建的条目
	 * */
	global $db;
	$result = array (
			msg => "ok",
			error => "" 
	);
	
	if (! check_success()) {
		$result ["msg"] = "wrong";
		$result ["error"] = "订单已审核，但是您没有完结订单的权限";
		return $result;
	}
	$sql = "update ecshop.ecs_vorder_request_info set vorder_status = 'COMPLETE' where vorder_request_id = '{$vorder_request_id}'";
	$db->query ( $sql );
	$error_no = $db->errno ();
	if ($error_no > 0) {
		$result ["msg"] = "wrong";
		$result ["error"] = $db->errorMsg ();
		return $result;
	}
	return $result;
}

/*
 * 查找没有审核的订单
*/


function find_not_out_order_v($priv,$facility_id = 0,$page=1,$range = 15){
	global $db;
	global $smarty;
	if ($facility_id == 0) {
		return null;
	}
	if (!check_check_priv() ) {
		die("抱歉，您没有查看的权限");
	}
	$check_status = intval ( $priv );
	$pre_step = $check_status - 1;
	$party_id = $_SESSION ["party_id"];
	if (empty($party_id)) {
		die("请选择业务组织");
	}
	$start = ($page-1)*$range;
	$end = $page*$range;
/*  
 *  运营的将这段需求删除，但是不确定以后不要
	$sql_facility = " ";
	if (check_apply_priv()) {
		$with_facility = false;
	}else{
		$with_facility = true;
	}
	if ($with_facility) {
		$facility = get_user_facility();
		if (empty($facility)) {
			$sql_facility = " ";
		}else{
			$facility_ids = array_keys($facility);
			$sql_facility .= " and ( ";
			for ($i = 0; $i < count($facility_ids)-1 ; $i++){
				$sql_facility .= " info.facility_id = '{$facility_ids[$i]}' or ";
			}
			$sql_facility .= " info.facility_id =  '{$facility_ids[count($facility_ids) - 1]}' ) ";
		}
	
	}else{
		$sql_facility = " ";
	} */
	
	
	$sql_base = "select info.vorder_request_id as vorder_id	from ecshop.ecs_vorder_request_info info where info.facility_id = '{$facility_id}' and ";
	$sql_count= "select count(info.vorder_request_id) as num from ecshop.ecs_vorder_request_info info where info.facility_id = '{$facility_id}' and ";
	if (!check_allparty_priv()) {
		$sql = $sql_base."  info.check_status = '{$check_status}' and info.vorder_status = 'APPLY' 
				and info.party_id = '{$party_id}' 
				order by create_stmp DESC limit {$start},{$end}";
		$sql_count .= "  info.check_status = '{$check_status}' and info.vorder_status = 'APPLY' 
				and info.party_id = '{$party_id}' 
				order by create_stmp DESC";
	} else {
		$child_parties = get_child_party($_SESSION['party_id']);
		$sql_party = " ( ";
		for ($i = 0; $i < count($child_parties)-1 ; $i++){
			$sql_party .= " info.party_id = '{$child_parties[$i]}' or ";
		}
		$sql_party .= " info.party_id = '{$child_parties[count($child_parties) - 1]}' ) ";
		$sql = $sql_base."  info.check_status = '{$check_status}'
				and info.vorder_status = 'APPLY' and ".$sql_party."   
				order by create_stmp DESC limit {$start},{$end}";
		$sql_count .=  "  info.check_status = '{$check_status}'
				and info.vorder_status = 'APPLY' and ".$sql_party."   
				order by create_stmp DESC ";
	}
	

	$result = $db->getAll ( $sql );
	if ($db->errno ()) {
		die ( "查找待审批列表数据库出错，请联系ERP组解决。" );
	}
	$count = $db->getOne( $sql_count );
	$pagecount = (int)($count/$range);
	if ($count%$range > 0) {
		$pagecount ++ ;
	}
	$smarty->assign("pagecount",$pagecount);
	$smarty->assign("recordcount",$count);
	$page_array = array();
	for ( $i = 1; $i <= $pagecount; $i++){
		$page_array ["{$i}"] = $i;
	}
	$smarty->assign("page_array",$page_array);
	
	$vorder_ids = array();
	if (empty($result)) {
		return null;
	}
	foreach ($result as $result_item){
		$vorder_ids [] = $result_item ['vorder_id'];
	}
	if (check_apply_priv()) {
		$not_out_order = get_just_now_order($vorder_ids,0,true);
	}else{
		$not_out_order = get_just_now_order($vorder_ids,0,false);
	}
	return $not_out_order;
}
