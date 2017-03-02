<?php

define ( 'IN_ECS', true );
require ('includes/init.php');


/**
	*中粮喜宴套餐设置
		INSERT INTO ecshop.brand_cofco_tc_mapping (`cofco_code`, `tc_code`, `create_time`, `update_time`, `create_user`, `update_user`) 
		VALUES('site1043', 'TC-1510113152879', '2015-10-16', '2015-10-16', '...', '...');
**/

//组织控制
if($_SESSION['party_id'] !='65625'){
	die("此页面仅供中粮喜宴使用");
}

$act = isset($_POST['act']) ? $_POST['act'] : null;

if($act == 'search') {
	global $db;
	
	$cofco_code = isset($_POST['cofco_code']) ? $_POST['cofco_code'] : null;
	$tc_code = isset($_POST['tc_code']) ? $_POST['tc_code'] : null;
	$startTime = isset($_POST['startTime']) ? $_POST['startTime'] : null;
	$endTime = isset($_POST['endTime']) ? $_POST['endTime'] : null;
	
	$sql_condition = "";
	if(!empty($cofco_code)) {
		$sql_condition .= " and cofco_code = '{$cofco_code}' ";
	}
	if(!empty($tc_code)) {
		$sql_condition .= " and tc_code = '{$tc_code}' ";
	}
	if(!empty($startTime)) {
		$sql_condition .= " and create_time > '{$startTime}' ";
	}
	if(!empty($endTime)) {
		$sql_condition .= " and create_time < '{$endTime}' ";
	}	
	
	$sql = "select * from ecshop.brand_cofco_tc_mapping 
			where tc_mapping_id>1 ". $sql_condition;
	$tclists = $db->getAll($sql);
} else if($act == 'insert') {
	global $db;
	$flag = false;
	$cofco_code_insert = isset($_POST['cofco_code_insert']) ? $_POST['cofco_code_insert'] : null;
	$tc_code_insert = isset($_POST['tc_code_insert']) ? $_POST['tc_code_insert'] : null;
	
	$cofco_code_insert = trim($cofco_code_insert);
	$tc_code_insert = trim($tc_code_insert);
	
	$create_user = $_SESSION['admin_name'];

	if(strpos($tc_code_insert, 'C-') != 1) {
		$message = "输入的ERP餐编号有误，请检查输入！";
		
	} else if(strpos($cofco_code_insert, 'ite') != 1) {
		$message = "输入的喜宴套餐编号有误，请检查输入！";
		
	}  else {
		
		$sql_check = "select 1 from ecshop.brand_cofco_tc_mapping where cofco_code = '{$cofco_code_insert}' or tc_code = '{$tc_code_insert}' limit 1";
		$rows = $db->getAll($sql_check);
		
		if(count($rows) == 1) {
			$message = "输入的喜宴套餐编号、喜宴套餐编号已存在，请检查输入！";
		} else {
			$sql_insert = "INSERT INTO ecshop.brand_cofco_tc_mapping (cofco_code, tc_code, create_time, update_time, create_user, update_user)
						   VALUES('{$cofco_code_insert}', '{$tc_code_insert}', now(), now(), '{$create_user}', '{$create_user}') ";
			$flag = $db->query($sql_insert);
			if($flag) {
				$message = "套餐成功插入！";
			} else {
				$message = "套餐插入失败，请联系ERP组！";
			}
		}
		
	}
}



$smarty->assign ( 'message', $message);
$smarty->assign ( 'tclists', $tclists);
$smarty->display ( 'oukooext/zhongliang_dealer_tc_mappping.htm' );
