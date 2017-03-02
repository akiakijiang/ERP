<?php

/**
 * 库存同步预警页面设置
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('inventory_syn_warning');

/*
 * 添加
 * 
 * */
if($_REQUEST['act']=='add_users'){
		$item = $_REQUEST['item'];
		$reg='/^[0-9a-zA-Z]+@(([0-9a-zA-Z]+)[.])+[a-z]{2,4}$/'; 
		if(empty($item['userName']) || empty($item['userEmail']))
		{
			$smarty->assign('message', '用户名和邮箱均不能为空');
		}else if(!preg_match($reg,$item['userEmail'])) {
    		$smarty->assign('message', '邮箱格式错误，请查证后再试');
		}else{
			$sql = "select count(*) from ecshop.ecs_party_assign_email where user_name = '".$item['userName']."' and party_id = '".$_SESSION['party_id']."'";
			$user_ids=$db->getOne($sql);
			// 存在则提醒
			if($user_ids){
				$smarty->assign('message', '已经添加过该用户，请检查后重试');
			}else{
				$sql="insert into ecshop.ecs_party_assign_email (user_name,user_email,party_id,warning_id,create_time,last_modify,action_user) values ('%s', '%s', '%s',%d, NOW(),NOW(),'%s')";
				$db->query(sprintf($sql,$item['userName'],$item['userEmail'],$_SESSION['party_id'],$item['warningId'],$_SESSION['admin_name']));
			}	
		}
}
 
/*
 * 更新
 * 
 * */
if($_REQUEST['act']=='update_users'){
		$item = $_REQUEST['item'];
		$reg='/^[0-9a-zA-Z]+@(([0-9a-zA-Z]+)[.])+[a-z]{2,4}$/'; 
		if(empty($item['userName']) || empty($item['userEmail']))
		{
			$smarty->assign('message', '用户名和邮箱均不能为空');
		}else if(!preg_match($reg,$item['userEmail'])) {
    		$smarty->assign('message', '邮箱格式错误，请查证后再试');
    	}else{
	       	$update_user = $_SESSION['admin_name'];
	       	$sql = "update ecshop.ecs_party_assign_email set user_name = '".$item['userName']."'" .
	        		",user_email = '".$item['userEmail']."',warning_id = ".$item['warningId']."," .
	        				" last_modify = now() ,action_user = '".$update_user."'" .
	        						" where party_assign_email_id = ".$item['party_assign_email_id'];
	        $db->query($sql);   
		}
		header("Location: inventory_syn_warning.php");exit;
}

//修改
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$group =  $db->getRow("SELECT * FROM ecshop.ecs_party_assign_email WHERE party_assign_email_id = ".$_GET['id']);
  	$smarty->assign('update', $group); 	 
}

function get_users_list() {
    global $db;
    $sql = "SELECT * from ecshop.ecs_party_assign_email WHERE party_id = '".$_SESSION['party_id']."'";
    $application_list = $db->getAll($sql);
    return $application_list;
}

$warning_list = array('0'=>'直销','1'=>'分销');
$smarty->assign('warning_list',$warning_list);
$user_names = get_users_list();
$smarty->assign('user_names',$user_names);
$smarty->display("taobao/inventory_syn_warning.htm");
?>
