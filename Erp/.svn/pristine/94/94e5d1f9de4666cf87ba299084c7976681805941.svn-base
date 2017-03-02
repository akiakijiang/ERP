<?php
define ( 'IN_ECS', true );
require_once ('../includes/init.php');
include_once (ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . "/RomeoApi/lib_inventory.php");
require_once ("../includes/lib_function_lcji.php");
require (ROOT_PATH . "/includes/lib_order.php");


if (! party_explicit ( $_SESSION ['party_id'] )) {
	exit ( '请选择分公司的party_id，再进行操作' );
}

// 获得用户角色类型 0,1,2,3,4,5
$_priv = showRole();

/* 
 * 现在容许每个人查看该页，但是只有相应权限的可以进行搜索
 */
if (intval ( $_priv ) == - 1) {
	die ( "对不起，您没有查看该页的权限T_T" . $_priv );
}
/*
 * 返回信息区域
*/
$returnmessage = "Just for test";

/*
 * 该用户申请过的所有订单、或者审核过的所有订单
*/
$already_check_order = find_already_check_order ( $_priv ); // 仅仅显示最近的几个

/*
 * 最近该用户审核过，但是出现问题的所有订单
*/
$refuse_order = find_refuse_order ( $_priv );

/*
 * 搜索订单，搜索出来的，仅仅是和当前用户相关，即他审核过，或者申请过的
*/
if ($_POST ["act"] == "searchorder") {
	$search_order = find_search_order ( $_priv );
	;
} else {
	$search_order = null;
}

/*
 * 搜索订单，搜索出来后，进行下载csv文件
*/
if ($_POST ["act"] == "downcsv") {
	downcheckcsv ();
	exit ();
}

/*
 * 取消订单，仅仅申请者可以操作
*/
if ($_POST ["act"] == "cancelorder"){
	cancelorder();
	exit();
}

/*
 * 取消审核，仅仅审核者可以操作
*/
if ($_POST ["act"] == "cancelcheck"){
	cancelcheck();
	exit();
}

$smarty->assign("admin_id",$_SESSION["admin_id"]);
$smarty->assign ( "search_order", $search_order );
$smarty->assign ( "priv", $_priv ); //
$smarty->assign ( 'returnmessage', $returnmessage );
$smarty->assign ( 'already_check_order', $already_check_order );
$smarty->assign ( 'refuse_order', $refuse_order );
$smarty->assign ( 'user_current_party_name', party_mapping ( $_SESSION ['party_id'] ) );
$smarty->display ( 'virance_inventory/physical_inventory_out_inventory_search.htm' );


/*
 * 取消审核，仅仅审核者可以操作
*/
function cancelcheck(){
	global $db;
	$json = new JSON();
	/*
	 * 需要获得两个参数：key和role，分别是需要取消的订单号和操作者申请的角色，如果申请的角色和他当前的角色不符合，就会失败
	*/
	$vorder_id = $_POST ["key"];
	$role = intval($_POST ["role"]);
	if (empty($vorder_id) || empty($role)){
		echo $json->encode(array(flag => 1,msg => "vorder_is is null or role is 0"));
		return;
	}
	if ($role  != intval ( showRole() )) {
		echo $json->encode(array(flag => 1,msg => "请求的角色不对"));
		return;
	}
	$sql = "select check_status from ecshop.ecs_vorder_request_info where vorder_request_id = '{$vorder_id}' ";
	$check_status = $db->getOne($sql);
	if (empty($check_status) ) {
		echo $json->encode(array(flag => 1,msg => "获取订单状态失败"));
		return;
	}
	$cancel_step = $check_status - 1;
	$sql = "select 1 from ecshop.ecs_vorder_request_info where vorder_request_id = '{$vorder_id}' 
			and step{$cancel_step}_user_id = '{$_SESSION["admin_id"]}'
			and step{$check_status}_status = '0'";
	$tmp = $db->getOne($sql);
	if (!empty($tmp)) {
		$db->start_transaction();
		$sql = "update ecshop.ecs_vorder_request_info set check_status = '{$cancel_step}', 
				step{$cancel_step}_status = '0',step{$check_status}_status = '0' where vorder_request_id = '{$vorder_id}' 
				";
		$db->query($sql);
		$error_no = $db->errno();
		if ($error_no) {
			$db->rollback();
			echo $json->encode(array(flag => 1,msg => "修改数据库失败"));
			return;
		}else{
			$db->commit();
			echo $json->encode(array(flag => 0,msg => "取消成果"));
			return;
		}
	}else{
		echo $json->encode(array(flag => 1,msg => "后续已经审核，无法取消，请联系申请者取消订单"));
		return;
	}
	
}

/*
 * 取消订单，仅仅申请者者可以操作
*/
function cancelorder(){
	global $db;
	$json = new JSON();
	/*
	 * 需要获得两个参数：key和role，分别是需要取消的订单号和操作者申请的角色，如果申请的角色和他当前的角色不符合，就会失败
	*/
	$vorder_id = $_POST ["key"];
	$role = intval($_POST ["role"]);
	if (empty($vorder_id) || $role!= 0){
		echo $json->encode(array(flag => 1,msg => "vorder_is is null or role is not 0"));
		return;
	}
	if (!checkVApply()) {
		echo $json->encode(array(flag => 1,msg => "您的角色不对"));
		return;
	}
	$sql = "update ecshop.ecs_vorder_request_info set vorder_status = 'CANCEL' where vorder_request_id = '{$vorder_id}' and step0_user_id = '{$_SESSION["admin_id"]}'";
	$db->query($sql);
	$sql = "select vorder_status from ecshop.ecs_vorder_request_info where vorder_request_id = '{$vorder_id}'";
	$tmp = $db->getOne($sql);
	if (empty($tmp) || $tmp != "CANCEL" ) {
		echo $json->encode(array(flag => 1, msg => "NO CANCEL"));
		return;
	}else{
		echo $json->encode(array(flag => 0, msg => "OK"));
		return;
	}
}

/*
 * 下载订单 
 * 
 */
function downcheckcsv() {
	global $db;
	
	$start_str = $_POST ["start_date"];
	$end_str = $_POST ["end_date"];
	if (empty ( $start_str ) || empty ( $end_str )) {
		die ( "请输入时间" );
	}
	
	if (empty ( $_POST ["ordinary"] )) {
		$reverse = " DESC ";
	} else {
		$reverse = "  asc ";
	}
	if (empty($_POST['allparty'])){
		$allparty = false;
	}else{
		$allparty = true;
	}
	
	header ( "Content-type:application/vnd.ms-excel;charset=utf-8" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "{$_SESSION["admin_id"]}审核过的-v申请单导出" ) . ".csv" );
	ob_start ();
	$header_str = iconv ( "UTF-8", 'GB18030', "订单号,申请时间,商品名,样式,调整类型,库存状态,仓库名称,调整数量,调整金额,序列号类型,序列号,申请原因,是否删除,订单状态\n" );
	
	if($allparty == true){
		$sql = "select info.vorder_request_id,info.create_stmp,item.goods_name,style.color,pm.ecs_style_id,
		item.v_category,item.goods_status,info.facility_id,item.goods_number,item.goods_amount,item.reason,
		item.is_delete,info.vorder_status,facility.facility_name,item.goods_type_id,item.serial_number 
		from ecshop.ecs_vorder_request_info info
		left join ecs_vorder_request_item item on info.vorder_request_id = item.vorder_request_id
		left join romeo.product_mapping pm on pm.product_id = item.product_id
		left join ecshop.ecs_style style on style.style_id = pm.ecs_style_id
		left join romeo.facility on facility.facility_id = info.facility_id
		where to_days(info.create_stmp) >= to_days('{$start_str}')
		and to_days(info.create_stmp) <= to_days('{$end_str}')
		and (step0_user_id = '{$_SESSION["admin_id"]}'
		or step1_user_id = '{$_SESSION["admin_id"]}'
		or step2_user_id = '{$_SESSION["admin_id"]}'
		or step3_user_id = '{$_SESSION["admin_id"]}'
		or step4_user_id = '{$_SESSION["admin_id"]}'
		or step5_user_id = '{$_SESSION["admin_id"]}')
		order by info.create_stmp ".$reverse;
	}else{
		$sql = "select info.vorder_request_id,info.create_stmp,item.goods_name,style.color,pm.ecs_style_id,
		item.v_category,item.goods_status,info.facility_id,item.goods_number,item.goods_amount,item.reason,
		item.is_delete,info.vorder_status,facility.facility_name,item.goods_type_id,item.serial_number 
		from ecshop.ecs_vorder_request_info info
		left join ecs_vorder_request_item item on info.vorder_request_id = item.vorder_request_id
		left join romeo.product_mapping pm on pm.product_id = item.product_id
		left join ecshop.ecs_style style on style.style_id = pm.ecs_style_id
		left join romeo.facility on facility.facility_id = info.facility_id
		where to_days(info.create_stmp) >= to_days('{$start_str}') 
		and info.party_id = '{$_SESSION["party_id"]}' 
		and to_days(info.create_stmp) <= to_days('{$end_str}')
		and (step0_user_id = '{$_SESSION["admin_id"]}'
		or step1_user_id = '{$_SESSION["admin_id"]}'
		or step2_user_id = '{$_SESSION["admin_id"]}'
		or step3_user_id = '{$_SESSION["admin_id"]}'
		or step4_user_id = '{$_SESSION["admin_id"]}'
		or step5_user_id = '{$_SESSION["admin_id"]}')
		order by info.create_stmp ".$reverse;
	}
	
	$result = $db->getAll ( $sql );
	$file_str = "";
	foreach ( $result as $key => $item ) {
		$file_str .= str_replace ( ",", " ", $item ['vorder_request_id'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['create_stmp'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['goods_name'] ) . ",";
		
		if (! isset ( $item ["color"] ) || empty ( $item ["color"] )) {
			$file_str .= "无颜色,";
		} else {
			$file_str .= str_replace ( ",", " ", $item ['color'] ) . ",";
		}
		
		if (strcmp ( $item ["v_category"], "ADD" ) == 0) {
			$file_str .= "盘赢ADD,";
		} elseif (strcmp ( $item ["v_category"], "MINUS" ) == 0) {
			$file_str .= "盘亏MINUS,";
		} else {
			$file_str .= str_replace ( ",", " ", $item ['v_category'] ) . ",";
		}
		
		if (strcmp ( $item ["goods_status"], "INV_STTS_AVAILABLE" ) == 0) {
			$file_str .= "全新库,";
		} elseif (strcmp ( $item ["goods_status"], "INV_STTS_USED" ) == 0) {
			$file_str .= "二手库,";
		} else {
			$file_str .= str_replace ( ",", " ", $item ['goods_status'] ) . ",";
		}
		
		$file_str .= str_replace ( ",", " ", $item ['facility_name'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['goods_number'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['goods_amount'] ) . ",";
		if ($item ['goods_type_id'] == 'NON-SERIALIZED') {
			$file_str .= "无,";
		}else{
			$file_str .= "有,";
		}
		$file_str .= $item ['serial_number'] . ",";
		
		$file_str .= str_replace ( ",", " ", $item ['reason'] ) . ",";
		if (intval ( $item ["is_delete"] ) == 1)
			$file_str .= "删除,";
		else
			$file_str .= "未删除,";
		$file_str .= str_replace ( ",", " ", $item ['vorder_status'] ) . "\n";
	}
	$file_str = iconv ( "UTF-8", 'gbk', $file_str );
	ob_end_clean ();
	echo $header_str;
	echo $file_str;
	exit ();
}

/*
 * 搜索订单
 */
function find_search_order($priv) {
	global $db;
	global $smarty;
	
	$start_str = $_POST ["start_display"];
	$end_str = $_POST ["end_display"];
	if (empty ( $start_str ) || empty ( $end_str )) {
		die ( "请输入时间" );
	}
	
	if (empty ( $_POST ["ordinary"] )) {
		$reverse = " DESC ";
	} else {
		$reverse = "  asc ";
	}
	
	if (empty($_POST['allparty'])){
		$allparty = false;
	}else{
		$allparty = true;
	}
	
	$smarty->assign("start_date",$start_str);
	$smarty->assign("end_date",$end_str);
	$smarty->assign("ordinary",$_POST ["ordinary"]);
	$smarty->assign("allparty",$_POST['allparty']);
	
	if ($allparty == true){
		$sql = "select vorder_status,vorder_request_id,check_status,create_stmp,last_update_stmp,step0_user_id,
		party_id,info.facility_id,goods_amount,goods_count, notes,comments,facility.facility_name ,info.inventory_adjust as out_flag 
		from ecshop.ecs_vorder_request_info info 
		inner join romeo.facility facility on facility.facility_id = info.facility_id 
		where step0_status = 1
		and to_days(create_stmp) >= to_days('{$start_str}')
		and to_days(create_stmp) <= to_days('{$end_str}')
		and (step0_user_id = '{$_SESSION["admin_id"]}' 
		or step1_user_id = '{$_SESSION["admin_id"]}' 
		or step2_user_id = '{$_SESSION["admin_id"]}' 
		or step3_user_id = '{$_SESSION["admin_id"]}' 
		or step4_user_id = '{$_SESSION["admin_id"]}' 
		or step5_user_id = '{$_SESSION["admin_id"]}')
		order by create_stmp 
		" . $reverse;
	}else{
		$sql = "select vorder_status,vorder_request_id,check_status,create_stmp,last_update_stmp,step0_user_id,
		party_id,info.facility_id,goods_amount,goods_count, notes,comments,facility.facility_name ,info.inventory_adjust as out_flag
		from ecshop.ecs_vorder_request_info info
		inner join romeo.facility facility on facility.facility_id = info.facility_id
		where step0_status = 1 and info.party_id = '{$_SESSION["party_id"]}' 
		and to_days(create_stmp) >= to_days('{$start_str}')
		and to_days(create_stmp) <= to_days('{$end_str}')
		and (step0_user_id = '{$_SESSION["admin_id"]}'
		or step1_user_id = '{$_SESSION["admin_id"]}'
		or step2_user_id = '{$_SESSION["admin_id"]}'
		or step3_user_id = '{$_SESSION["admin_id"]}'
		or step4_user_id = '{$_SESSION["admin_id"]}'
		or step5_user_id = '{$_SESSION["admin_id"]}')
		order by create_stmp
			" . $reverse;
	}
	$result = $db->getAll ( $sql );
	if ($db->errno ()) {
		die ( "查找待审批列表数据库出错，请联系ERP组解决。" );
	}
	if (empty ( $result )) {
		return null;
	}
	if (count ( $result ) > 60) {
		die ( "查询数量过多，请缩小时间。" );
	}
	
	$search_order = array ();
	foreach ( $result as $key => $item ) {
		
		$search_order_item = array ();
		$search_order_item ["vorder_request_id"] = $item ["vorder_request_id"];
		$search_order_item ["check_status"] = $item ["check_status"];
		$search_order_item ["create_stmp"] = $item ["create_stmp"];
		$search_order_item ["last_update_stmp"] = $item ["last_update_stmp"];
		$search_order_item ["party_id"] = $item ["party_id"];
		$search_order_item ["facility_id"] = $item ["facility_id"];
		$search_order_item ["facility_name"] = $item ["facility_name"];
		$search_order_item ["goods_amount"] = $item ["goods_amount"];
		$search_order_item ["goods_count"] = $item ["goods_count"];
		$search_order_item ["notes"] = get_vorder_review_note($item ["vorder_request_id"]);
		$search_order_item ["comments"] = $item ["comments"];
		$search_order_item ["vorder_status"] = $item ["vorder_status"];
		$search_order_item ["admin_id"] = $item ["step0_user_id"];
		$search_order_item ["out_flag"] = $item ["out_flag"];
		
		$sql = "
		select is_delete, product_id,v_category,goods_status,goods_name,
		goods_type_id,goods_number,goods_price,goods_amount,reason,serial_number 
		from ecshop.ecs_vorder_request_item
		where vorder_request_id = '{$search_order_item["vorder_request_id"]}'
				";
		$goodsresut = $db->getAll ( $sql );
		if ($db->errno ()) {
			die ( "查找待审批列表商品数据库出错，请联系ERP组解决。" );
		}
		$goodsarr = array ();
		foreach ( $goodsresut as $goods ) {
			/* if ($goods ["is_delete"] == 0) 去掉了这个判断条件，那些被删除的商品，也要展示出来，注明被删除 */
			if (strcmp ( $goods ["goods_status"], "INV_STTS_AVAILABLE" ) == 0) {
				$goods ["goods_status"] = "全新库";
			} elseif (strcmp ( $goods ["goods_status"], "INV_STTS_USED" ) == 0) {
				$goods ["goods_status"] = "二手库";
			}
			$goodsarr [] = $goods;
		}
		$search_order_item ["goods"] = $goodsarr;
		$search_order [] = $search_order_item;
	}
	return $search_order;
}

/*
 * 该用户审核过的正在流程中订单并且没有被拒绝的，都会显示在这里 
*/
function find_already_check_order($priv) {
	
	global $db;
	$check_status = intval ( $priv );
	$party_id = $_SESSION ["party_id"];
	$admin_id = $_SESSION ["admin_id"];
	
	$sql = "select vorder_request_id,check_status,create_stmp,last_update_stmp,
	party_id,facility_id,goods_amount,goods_count, notes,comments 
	from ecshop.ecs_vorder_request_info 
	where   vorder_status = 'APPLY'  
	and (step0_user_id = '{$_SESSION["admin_id"]}' 
	or step1_user_id = '{$_SESSION["admin_id"]}' 
	or step2_user_id = '{$_SESSION["admin_id"]}' 
	or step3_user_id = '{$_SESSION["admin_id"]}' 
	or step4_user_id = '{$_SESSION["admin_id"]}' 
	or step5_user_id = '{$_SESSION["admin_id"]}')
	order by create_stmp DESC";
	
	$result = $db->getAll ( $sql );
	if ($db->errno ()) {
		die ( "查找待审批列表数据库出错，请联系ERP组解决。" );
	}
	if (empty ( $result )) {
		return null;
	}
	$already_check_order = array ();
	foreach ( $result as $key => $item ) {
		
		$already_check_order_item = array ();
		$already_check_order_item ["vorder_request_id"] = $item ["vorder_request_id"];
		$already_check_order_item ["check_status"] = $item ["check_status"];
		$already_check_order_item ["create_stmp"] = $item ["create_stmp"];
		$already_check_order_item ["last_update_stmp"] = $item ["last_update_stmp"];
		$already_check_order_item ["party_id"] = $item ["party_id"];
		$already_check_order_item ["facility_id"] = $item ["facility_id"];
		$already_check_order_item ["goods_amount"] = $item ["goods_amount"];
		$already_check_order_item ["goods_count"] = $item ["goods_count"];
		$already_check_order_item ["notes"] = get_vorder_review_note($item ["vorder_request_id"]);
		$already_check_order_item ["comments"] = $item ["comments"];
		
		$sql = "
				select is_delete, product_id,v_category,goods_status,goods_name,
				goods_type_id,goods_number,goods_price,goods_amount,reason 
				from ecshop.ecs_vorder_request_item 
				where vorder_request_id = '{$already_check_order_item["vorder_request_id"]}'
				";
		$goodsresut = $db->getAll ( $sql );
		if ($db->errno ()) {
			die ( "查找待审批列表商品数据库出错，请联系ERP组解决。" );
		}
		$goodsarr = array ();
		foreach ( $goodsresut as $goods ) {
			/* if ($goods ["is_delete"] == 0) 去掉了这个判断条件，那些被删除的商品，也要展示出来，注明被删除 */
			$goodsarr [] = $goods;
		}
		$already_check_order_item ["goods"] = $goodsarr;
		$already_check_order [] = $already_check_order_item;
	}
	return $already_check_order;
}

/*
 * 该用户审核过的但是被后面审核人拒绝的，会显示最近的一些在这里
*/
function find_refuse_order($priv) {

	global $db;
	$check_status = intval ( $priv );
	$party_id = $_SESSION ["party_id"];
	$admin_id = $_SESSION ["admin_id"];
	
	$sql = "select vorder_request_id,vorder_status,check_status,create_stmp,last_update_stmp,
	party_id,facility_id,goods_amount,goods_count, notes,comments 
	from ecshop.ecs_vorder_request_info 
	where  vorder_status not in ('APPLY','COMPLETE') 
	and 
	(step0_user_id = '{$_SESSION["admin_id"]}' 
	or step1_user_id = '{$_SESSION["admin_id"]}' 
	or step2_user_id = '{$_SESSION["admin_id"]}' 
	or step3_user_id = '{$_SESSION["admin_id"]}' 
	or step4_user_id = '{$_SESSION["admin_id"]}' 
	or step5_user_id = '{$_SESSION["admin_id"]}')
	order by create_stmp DESC limit 15";
	
	$result = $db->getAll ( $sql );
	if ($db->errno ()) {
		die ( "查找待审批列表数据库出错，请联系ERP组解决。" );
	}
	if (empty ( $result )) {
		return null;
	}
	$refuse_order = array ();
	foreach ( $result as $key => $item ) {
		
		$refuse_order_item = array ();
		$refuse_order_item ["vorder_request_id"] = $item ["vorder_request_id"];
		$refuse_order_item ["vorder_status"] = $item ["vorder_status"];
		$refuse_order_item ["check_status"] = $item ["check_status"];
		$refuse_order_item ["create_stmp"] = $item ["create_stmp"];
		$refuse_order_item ["last_update_stmp"] = $item ["last_update_stmp"];
		$refuse_order_item ["party_id"] = $item ["party_id"];
		$refuse_order_item ["facility_id"] = $item ["facility_id"];
		$refuse_order_item ["goods_amount"] = $item ["goods_amount"];
		$refuse_order_item ["goods_count"] = $item ["goods_count"];
		$refuse_order_item ["notes"] = get_vorder_review_note($item ["vorder_request_id"]);
		$refuse_order_item ["comments"] = $item ["comments"];
		
		$sql = "
				select is_delete, product_id,v_category,goods_status,goods_name,
				goods_type_id,goods_number,goods_price,goods_amount,reason 
				from ecshop.ecs_vorder_request_item 
				where vorder_request_id = '{$refuse_order_item["vorder_request_id"]}'
				";
		$goodsresut = $db->getAll ( $sql );
		if ($db->errno ()) {
			die ( "查找待审批列表商品数据库出错，请联系ERP组解决。" );
		}
		$goodsarr = array ();
		foreach ( $goodsresut as $goods ) {
			/* if ($goods ["is_delete"] == 0) 去掉了这个判断条件，那些被删除的商品，也要展示出来，注明被删除 */
			$goodsarr [] = $goods;
		}
		$refuse_order_item ["goods"] = $goodsarr;
		$refuse_order [] = $refuse_order_item;
	}
	return $refuse_order;
}


