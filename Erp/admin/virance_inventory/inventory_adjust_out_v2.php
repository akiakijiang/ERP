<?php
define ( 'IN_ECS', true );
require_once ('../includes/init.php');
include_once (ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . "/RomeoApi/lib_inventory.php");
require_once ("../includes/lib_function_lcji.php");
require (ROOT_PATH . "/includes/lib_order.php");


// 获得用户角色类型
$_priv = showRole ();

$json = new JSON();
if (! party_explicit ( $_SESSION ['party_id'] ) && $_priv !=4 && $_priv != 5) {
	exit ( '请选择分公司的party_id，再进行操作' );
}

if (!checkVWatch ()) {
	die ( "对不起，您没有查看该页的权限" );
}

if (intval ( $_priv ) == - 1) {
	die ( "对不起，您没有查看该页的权限--   " . $_priv );
}

$returnmessage = "返回消息区！";

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
	 * role是请求的角色
	 * note是添加的备注
	* */
	$key = $_POST ["key"];
	$role = $_POST ["role"];
	$note = $_POST ["note"];
	$result = singlecheck ( $_priv, $key, $role, $note );
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
	$role = $_POST ["role"];
	$note = $_POST ["note"];
	$result = singlerefuse ( $_priv,$key, $role, $note);
	echo $json->encode($result);
	exit();
	
} 
/*
 * 删除某个商品
 * 目前这个功能虽然开发，但是还不对外提供
 * 原因：审核过程中不允许进行删除，只有申请者可以删除。
* */
else if ($_POST ["act"] == "deletegoods") {
	deletegoods ( $_priv );
	
} 
/*
 * 取消删除某个商品
 * 目前这个功能虽然开发，但是还不对外提供
 * 原因：审核过程中不允许进行取消删除，只有申请者可以取消删除。
 * */
else if ($_POST ["act"] == "undodelete") {
	undodelete ( $_priv );
	
} 
/*
 * 批量进行拒绝
 * */
else if ($_POST ["act"] == "batchrefuse") {
	batchrefuse ( $_priv );
	exit ();
}

/*
 * not_out_order格式： $not_out_order = array( $vorder_id => array( array(goods_name,...), array(goods_name,...), ... ), ... )
 */
$not_out_order = find_not_out_order ( $_priv );
$returnmessage = "Hello World";

$smarty->assign ( "priv", $_priv ); //
$smarty->assign ( 'returnmessage', $returnmessage );
$smarty->assign ( 'not_out_order', $not_out_order );
$smarty->assign ( 'user_current_party_name', party_mapping ( $_SESSION ['party_id'] ) );
$smarty->display ( 'virance_inventory/physical_inventory_out_inventory_v3.htm' );

function orderbatchcheck($priv) {
	$json = new JSON ();
	/*
	 * key是订单id
	 * role是请求的角色
	 * note是添加的备注
	 * */
	$role = $_POST ["role"];
	$keys = $_POST ["keys"];
	$note = $_POST ["note"];
	
	$result = array (
			msg => "ok",
			error => "" 
	);
	
	if (intval ( $role ) != intval ( $priv )) {
		$result ["error"] = "请求的role和您的角色不一致";
		$result ["msg"] = "wrong";
		echo $json->encode ( $result );
		exit ();
	}
	$keys_array = explode ( ",", $keys );
	
	foreach ( $keys_array as $key => $item ) {
		if (! empty ( $priv ) && ! empty ( $item ) && ! empty ( $role )) {
			/*
			 * 对每个订单调用单独审核函数
			 * */
			$tmp = singlecheck( $priv, $item, $role, $note );
			if (strcmp ( $tmp ["msg"], "ok" ) != 0) {
				$result ['error'] .= "订单" . $item . "出现问题 " . $tmp ['msg'] . "\n";
			}
		} else {
			$result ['error'] .= "订单" . $item . "参数出现问题 \n";
		}
	}
	
	if (strcmp ( $result ["error"], "" ) != 0) {
		$result ["msg"] = "wrong";
	}
	echo $json->encode ( $result );
	exit ();
}

/* 
 * 删除商品，标准时item表中的recid
 * */
function deletegoods($user_priv) {
	global $db;
	$json = new JSON ();
	/*
	 * recid是item表中的primary key
	 * role是请求的角色
	 * key是订单号
	* */
	$rec_id = $_POST ["recid"];
	$key = $_POST ["key"];
	$role = $_POST ["role"];
	$result = array (
			msg => "ok",
			id => $rec_id 
	);
	if (! isset ( $rec_id )) {
		$result ["msg"] = "请求参数recid有问题";
		echo $json->encode ( $result );
		exit ();
	}
	
	if (intval ( $role ) != intval ( $user_priv )) {
		$result ["msg"] = "请求的role和您的角色不一致";
		echo $json->encode ( $result );
		exit ();
	}
	
	$sql = "select vorder_status from ecshop.ecs_vorder_request_info where vorder_request_id = '{$key}'";
	$status_order = $db->getOne ( $sql );
	if (strcmp ( trim ( $status_order ), "APPLY" ) != 0) {
		$result ["msg"] = "订单已经完结，如法更改";
		echo $json->encode ( $result );
		exit ();
	}
	
	$db->start_transaction (); // 开始事务
	try {
		$sql = "update ecshop.ecs_vorder_request_item set is_delete = 1, deletebyuser = '{$_SESSION["admin_id"]}' where rec_id = '{$rec_id}'";
		$db->query ( $sql );
		$error_no = $db->errno ();
		if ($error_no > 0) {
			$result ["msg"] = "未调度成功，请联系ERP " . $error_no;
			$db->rollback ();
			echo $json->encode ( $result );
			exit ();
		}
		$db->commit ();
		echo $json->encode ( $result );
	} catch ( Exception $e ) {
		$db->rollback ();
		die ( $e );
	}
	exit ();
}

/*
 * 取消删除商品，标准时item表中的recid
* */
function undodelete($user_priv) {
	global $db;
	$json = new JSON ();
	/*
	 * recid是item表中的primary key
	 * role是请求的角色
	 * key是订单号
	* */
	$rec_id = $_POST ["recid"];
	$key = $_POST ["key"];
	$role = $_POST ["role"];
	$result = array (
			msg => "ok",
			id => $rec_id 
	);
	
	if (! isset ( $rec_id )) {
		$result ["msg"] = "请求参数recid有问题";
		echo $json->encode ( $result );
		exit ();
	}
	
	if (intval ( $role ) != intval ( $user_priv )) {
		$result ["msg"] = "请求的role和您的角色不一致";
		echo $json->encode ( $result );
		exit ();
	}
	
	/* 已经完结的订单，无法进行删除 */
	$sql = "select vorder_status from ecshop.ecs_vorder_request_info where vorder_request_id = '{$key}'";
	$status_order = $db->getOne ( $sql );
	if (strcmp ( trim ( $status_order ), "APPLY" ) != 0) {
		$result ["msg"] = "订单已经完结，如法更改";
		echo $json->encode ( $result );
		exit ();
	}
	
	$db->start_transaction (); // 开始事务
	try {
		$sql = "update ecshop.ecs_vorder_request_item set is_delete = 0, deletebyuser = '{$_SESSION["admin_id"]}' where rec_id = '{$rec_id}'";
		$db->query ( $sql );
		$error_no = $db->errno ();
		if ($error_no > 0) {
			$result ["msg"] = "未调度成功，请联系ERP " . $error_no;
			$db->rollback ();
			echo $json->encode ( $result );
			exit ();
		}
		$db->commit ();
		echo $json->encode ( $result );
	} catch ( Exception $e ) {
		$db->rollback ();
		die ( $e );
	}
	
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
	$role = $_POST ["role"];
	$keys = $_POST ["keys"];
	$note = $_POST ["note"];
	
	$result = array (
			msg => "ok",
			error => "" 
	);
	
	if (intval ( $role ) != intval ( $priv )) {
		$result ["error"] = "请求的role和您的角色不一致";
		$result ["msg"] = "wrong";
		echo $json->encode ( $result );
		exit ();
	}
	$keys_array = explode ( ",", $keys );
	
	foreach ( $keys_array as $key => $item ) {
		if (! empty ( $priv ) && ! empty ( $item ) && ! empty ( $role )) {
			/* 
			 * 调用单独拒绝的函数，实现函数复用
			 *  */
			$tmp = singlerefuse ( $priv, $item, $role, $note );
			if (strcmp ( $tmp ["msg"], "ok" ) != 0) {
				$result ['error'] .= "订单" . $item . "出现问题 " . $tmp ['msg'] . "\n";
			}
		} else {
			$result ['error'] .= "订单" . $item . "参数出现问题 \n";
		}
	}
	
	if (strcmp ( $result ["error"], "" ) != 0) {
		$result ["msg"] = "wrong";
	}
	echo $json->encode ( $result );
	exit ();
}

/* 
 * 单独拒绝订单
 * @param user_priv 请求的角色 0-5（后台验证）
 * @param key  订单id
 * @param role 请求的角色（前台传来） role 和 user_priv应该一致，否则出错
 * @param note 备注
 *  */
function singlerefuse($user_priv,$key, $role, $note) {
	global $db;

	$result = array (
			msg => "ok" 
	);
	if (! isset ( $role ) || ! isset ( $key )) {
		$result ["msg"] = "请求参数role、key";
		return $result;
	}
	if ($role < 1 || $role > 5) {
		$result ["msg"] = "role参数错误，请联系ERP";
		return $result;
	}
	
	if (intval ( $role ) != intval ( $user_priv )) {
		$result ["msg"] = "请求的role和您的角色不一致";
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
* @param user_priv 请求的角色 0-5（后台验证）
* @param key  订单id
* @param role 请求的角色（前台传来） role 和 user_priv应该一致，否则出错
* @param note 备注
*  */
function singlecheck($user_priv, $key, $role, $note) {
	global $db, $limit_amount;
	$result = array (
			msg => "ok" 
	);
	if (! isset ( $role ) || ! isset ( $key )) {
		$result ["msg"] = "请求参数role、key不正确";
		return $result;
	}
	
	if ($role < 1 || $role > 5) {
		$result ["msg"] = "role参数错误，请联系ERP";
		return $result;
	}
	
	if (intval ( $role ) != intval ( $user_priv )) {
		$result ["msg"] = "请求的role和您的角色不一致";
		return $result;
	}
	
	$pre_step = $role - 1;
	$sql = "select step{$pre_step}_status from ecshop.ecs_vorder_request_info where vorder_request_id = '{$key}'";
	$pre_check = $db->getOne ( $sql );
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
		 * 如果当前是执行CEO审核，那么就应该算总价了，如果总价不超过阈值，就要调度了 
		 * */
		if (checkVCEO1 ()) {
			$sql = "select goods_amount from ecshop.ecs_vorder_request_item where vorder_request_id = '{$key}' and is_delete = '0'";
			$result_amount = $db->getAll ( $sql );
			$total_amount = 0.0;
			foreach ( $result_amount as $item ) {
				$total_amount = $total_amount + abs ( floatval ( trim ( $item ["goods_amount"] ) ) );
			}
			if ($total_amount < $limit_amount) {
				
				/* 订单金额不超过阈值，进行订单完结操作 */
				$msg = order_success ( $key );
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
		}
		
		/*
		 * 如果当前是CEO审核，那么就要调度了
		* */
		if (checkVCEO0 ()) {
			
			/* 进行订单完结操作 */
			$msg = order_success ( $key );
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
	$db->commit ();
	return $result;
}

/*
 * 订单完结 
 * @param $vorder_request_id 订单号
 */
function order_success($vorder_request_id) {
	/* 
	 * 创建ecs_order_info条目，创建ecs_vorder_request_mapping条目。执行ecs_order_info里面新创建的条目
	 * */
	global $db;
	$result = array (
			msg => "ok",
			error => "" 
	);
	
	if (! checkVCEO0 () && ! checkVCEO1 ()) {
		$result ["msg"] = "wrong";
		$result ["error"] = "订单已审核，但是您没有完结订单的权限";
		return $result;
	}
	$sql = "select v_category from ecshop.ecs_vorder_request_info where vorder_request_id = '{$vorder_request_id}'";
	$cat = $db->getOne($sql);
	if ($cat != "ADD" && $cat != "MINUS") {
		$result ["msg"] = "wrong";
		$result ["error"] = "订单类型错误";
		return $result;
	}
	
	/*
	 * 查看是否有商品被删除，如果没有被删除，就是COMPLETE，如果有商品被删除，就是PARTCOMPLETE
	 * */
	$sql = "select 1 from ecshop.ecs_vorder_request_info info 
			inner join ecshop.ecs_vorder_request_item item on item.vorder_request_id = info.vorder_request_id 
			where item.is_delete = '1' and info.vorder_request_id = '{$vorder_request_id}'
		";
	$sqlResult = $db->getAll ( $sql );
	
	if (empty ( $sqlResult )) {
		$error_no = 0;
		$sql = "update ecshop.ecs_vorder_request_info set vorder_status = 'COMPLETE' where vorder_request_id = '{$vorder_request_id}'";
		$db->query ( $sql );
		$error_no = $db->errno ();
		if ($error_no > 0) {
			$result ["msg"] = "wrong";
			$result ["error"] = $db->errorMsg ();
			return $result;
		}
	} else {
		$error_no = 0;
		$sql = "update ecshop.ecs_vorder_request_info set vorder_status = 'PARTCOMPLETE' where vorder_request_id = '{$vorder_request_id}'";
		$db->query ( $sql );
		$error_no = $db->errno ();
		if ($error_no > 0) {
			$result ["msg"] = "wrong";
			$result ["error"] = $db->errorMsg ();
			return $result;
		}
	}
	
	/*
	 * 查看订单是否已经映射到ecshop.ecs_order_info表，如果映射过了，不应该再映射，说明出错了
	* */
	$sql = "select 1 from ecshop.ecs_vorder_request_mapping where vorder_request_id = '{$vorder_request_id}'";
	$has_map = $db->getOne($sql);
	if ($has_map) {
		$result ["msg"] = "wrong";
		$result ["error"] = "已经有了映射";
		return $result;
	}
	
	// 更新完了，创建ecs_order_info的新条目,ecs_vorder_request_mapping新条目
	$sql = "select step0_user_id,vorder_request_id,facility_id,comments from ecshop.ecs_vorder_request_info
			where vorder_request_id = '{$vorder_request_id}'";
	$info = $db->getAll ( $sql );
	$admin_id = $info [0] ["step0_user_id"];
	$facility_id = $info [0] ["facility_id"];
	$comments = $info [0] ["comments"];
	
	$error_no = 0;
	do {
		$order_sn = get_order_sn () . "-v";
		$sql = "INSERT INTO ecshop.ecs_order_info
                (order_sn, order_time, order_status, shipping_status , pay_status, user_id, postscript, 
                order_type_id, party_id, facility_id)
                VALUES('{$order_sn}', NOW(), 2, 0, 0, '{$admin_id}',
                         '库存调整订单  {$comments}', 'VARIANCE_{$cat}', '{$_SESSION['party_id']}', '{$facility_id}')";
		$db->query ( $sql, 'SILENT' );
		$error_no = $db->errno ();
		if ($error_no > 0 && $error_no != 1062) {
			$result ["msg"] = "wrong";
			$result ["error"] = $db->errorMsg ();
			return $result;
		}
	} while ( $error_no == 1062 );
	$order_id = $db->insert_id ();
	
	// 创建ecs_order_goods条目
	$sql = "select item.product_id,item.v_category,item.goods_status,item.goods_name,item.goods_type_id,item.goods_sn,
				item.goods_number,item.goods_price,item.goods_amount,item.reason,pm.ecs_goods_id,pm.ecs_style_id,item.serial_number 
				from ecshop.ecs_vorder_request_item item 
				left join romeo.product_mapping pm on pm.product_id = item.product_id
    			where  item.vorder_request_id = '{$vorder_request_id}' and is_delete = '0'
    ";
	$vorder_request_items = $db->getAll ( $sql );
	foreach ( $vorder_request_items as $good ) {
		$goods_id = trim ( $good ["ecs_goods_id"] );
		$style_id = trim ( $good ["ecs_style_id"] );
		$goods_name = $good ["goods_name"];
		$goods_count =intval($good ["goods_number"]);
		$goods_price = $good ["goods_price"];
		$goods_status = trim ( $good ["goods_status"] );
		$goods_reason = $good ["reason"];
		$goods_type_id = $good ["goods_type_id"];
		$goods_serial = $good ["serial_number"] ;
		$v_category = trim ( $good ["v_category"] );
		
		// 插入对应的记录到order_goods表
		
		$sql = "INSERT INTO ecshop.ecs_order_goods
                            (order_id, goods_id, style_id, goods_name, goods_number, goods_price, status_id,action_note) 
                      VALUES('{$order_id}', '{$goods_id}', '{$style_id}', '{$goods_name}', 
                               '{$goods_count}', '{$goods_price}','{$goods_status}','{$goods_reason}')";
		$db->query ( $sql );
		$error_no = $db->errno ();
		$order_goods_id = $db->insert_id();
		if ($goods_type_id == "SERIALIZED") {
			$sql = "insert into ecshop.ecs_order_goods_serial (order_goods_id,serial_number) values ('{$order_goods_id}','{$goods_serial}')";
			$db->query($sql);
			$error_no = $db->errno ();
			if ($error_no > 0) {
				$result ["msg"] = "wrong";
				$result ["error"] = $db->errorMsg ();
				return $result;
			}
		}
	}
	
	$sql = "insert into ecshop.ecs_vorder_request_mapping (vorder_request_id,order_id) values('{$vorder_request_id}','{$order_id}')";
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
//1：店长；2：BD；3：财务；4：执行CEO；5：CEO
function find_not_out_order($priv) {

	if ($priv < 1 || $priv > 5) {
		die("抱歉，您没有查看的权限");
	}
	global $db;
	$check_status = intval ( $priv );
	$pre_step = $check_status - 1;
	$party_id = $_SESSION ["party_id"];
	
	/* 
	 * 展示原则，针对店长、BD、财务只展示该party_id下的，而CEO展示所有party_id的,申请人没有权利查看 
	 * */
	if ($check_status == 1 || $check_status == 2 || $check_status == 3) {
		$sql = "select vorder_request_id,vorder_status,check_status,create_stmp,
		party_id,info.facility_id,goods_amount,goods_count,step{$pre_step}_status,
		step{$pre_step}_user_id, notes,comments,facility_name,info.v_category  
		from ecshop.ecs_vorder_request_info info
		inner join romeo.facility facility on facility.facility_id = info.facility_id
		where check_status = '{$check_status}' and vorder_status = 'APPLY' and party_id = '{$party_id}' 
		order by create_stmp DESC";
	} else {
		if ($party_id == '65535' || empty($party_id)) {
			$sql = "select vorder_request_id,vorder_status,check_status,create_stmp,
				info.party_id,info.facility_id,goods_amount,goods_count,step{$pre_step}_status,
				step{$pre_step}_user_id, notes,comments,facility_name ,info.v_category,party.name as party_name   
				from ecshop.ecs_vorder_request_info info 
				inner join romeo.facility facility on facility.facility_id = info.facility_id
				inner join romeo.party party on party.party_id = info.party_id 
				where check_status = '{$check_status}' and vorder_status = 'APPLY'  
				order by create_stmp DESC";
		}else{
			$sql = "select vorder_request_id,vorder_status,check_status,create_stmp,
					info.party_id,info.facility_id,goods_amount,goods_count,step{$pre_step}_status,
					step{$pre_step}_user_id, notes,comments,facility_name,info.v_category
					from ecshop.ecs_vorder_request_info info
					inner join romeo.facility facility on facility.facility_id = info.facility_id 
					inner join romeo.party party on party.party_id = info.party_id  
					where check_status = '{$check_status}' and vorder_status = 'APPLY' and info.party_id = '{$party_id}'
					order by create_stmp DESC";
		}
		
	}
	$result = $db->getAll ( $sql );
	if ($db->errno ()) {
		die ( "查找待审批列表数据库出错，请联系ERP组解决。" );
	}
	if (empty ( $result )) {
		return null;
	}
	
	$not_out_order = array ();
	
	foreach ( $result as $key => $item ) {
		$not_out_order_item = array ();
		$not_out_order_item ["vorder_request_id"] = $item ["vorder_request_id"];
		$not_out_order_item ["vorder_status"] = $item ["vorder_status"];
		$not_out_order_item ["check_status"] = $item ["check_status"];
		$not_out_order_item ["create_stmp"] = $item ["create_stmp"];
		$not_out_order_item ["party_id"] = $item ["party_id"];
		$not_out_order_item ["facility_id"] = $item ["facility_id"];
		$not_out_order_item ["facility_name"] = $item ["facility_name"];
		$not_out_order_item ["goods_amount"] = $item ["goods_amount"];
		$not_out_order_item ["goods_count"] = $item ["goods_count"];
		$not_out_order_item ["step_status"] = $item ["step{$pre_step}_status"];
		$not_out_order_item ["step_user"] = $item ["step{$pre_step}_user_id"];
		$not_out_order_item ["notes"] = get_vorder_review_note($item ["vorder_request_id"]);
		$not_out_order_item ["comments"] = $item ["comments"];
		$not_out_order_item ["category"] = $item ["v_category"];
		$not_out_order_item ["party_name"] = $item ["party_name"];

		$sql = "
				select rec_id,is_delete, product_id,v_category,goods_status,goods_name,reason,serial_number,
				goods_type_id,goods_number,goods_price,goods_amount,serial_number,deletebyuser  
				from ecshop.ecs_vorder_request_item 
				where vorder_request_id = '{$not_out_order_item["vorder_request_id"]}'
				";
		$goodsresut = $db->getAll ( $sql );
		if ($db->errno ()) {
			die ( "查找待审批列表商品数据库出错，请联系ERP组解决。" );
		}
		$goodsarr = array ();
		foreach ( $goodsresut as $goods ) {
			if (intval ( $goods ["is_delete"] ) == 0 ){
				$goodsarr [] = $goods;
			}
		}
		$not_out_order_item ["goods"] = $goodsarr;
		$not_out_order [$not_out_order_item ["vorder_request_id"]] = $not_out_order_item;
	}
	return $not_out_order;
}

