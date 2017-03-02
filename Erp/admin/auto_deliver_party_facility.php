<?php
/*
 * Created on 2013-12-16 by qdi
 * 天猫超市自动出库管理
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
set_time_limit(3600);
require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
if(!in_array($_SESSION['admin_name'],array('ytchen','bjlian','ljni','jjhe','ychen','shyuan','wjzhu','lchen', 'lwang', 'zwzheng', 'jrpei','hli','mjzhou','xlhong','qdi','hbai','jwang')))
{
	die('没有权限');
}

require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

$party_id = $_SESSION['party_id'];
// 修改订单状态
if($_REQUEST['act']=='add'){
	$htm_party_id = $_REQUEST['party_id'];
	$facility_id = $_REQUEST['facility_id'];
	if($htm_party_id != $party_id){
		$message = "请求业务（".party_mapping($htm_party_id)."）与当前选择业务（".party_mapping($party_id)."）不符";
		$_REQUEST['act'] = "";
	}else{
		add_party_facility($party_id,$facility_id,$_SESSION['admin_name']);
	}
	
}else if($_REQUEST['act']=='del'){
	$htm_party_id = $_REQUEST['party_id'];
	$facility_id = $_REQUEST['facility_id'];
	if($htm_party_id != $party_id){
		$message = "请求业务（".party_mapping($htm_party_id)."）与当前选择业务（".party_mapping($party_id)."）不符";
		$_REQUEST['act'] = "";
	}else{
		del_party_facility($party_id,$facility_id,$_SESSION['admin_name']);
	}
}

$sql = "select f.facility_id,f.facility_name,pf.is_delete,pf.action_user,pf.last_update_stamp  
	 from romeo.facility f  
	 left join romeo.party_facility pf on f.facility_id = pf.facility_id and pf.party_id = {$party_id} 
	 where f.IS_CLOSED = 'N' and f.IS_OUT_SHIP = 'N'  
	 order by pf.is_delete  desc  ";
$party_facility_list = $db->getAll($sql);

$smarty->assign('message',$message);
$smarty->assign('party_id',$party_id);
$smarty->assign('party_facility_list', $party_facility_list);  
$smarty->display('auto_deliver_party_facility.htm');

function add_party_facility($party_id,$facility_id,$action_user){
	global $db;
	$sql = "
			select 1 from  romeo.party_facility
			WHERE party_id = '{$party_id}' and facility_id = '{$facility_id}'
				";
	$already_exit = $db->getRow($sql);
	if($already_exit){
		$sql = "
			update romeo.party_facility
			SET is_delete        = 0,
	        	last_update_stamp = NOW(), 
	        	action_user      = '{$action_user}'
			WHERE party_id = '{$party_id}' and facility_id = '{$facility_id}'
				";
	}else{
		$sql = "
		insert into romeo.party_facility
				(party_id,facility_id,action_user,created_stamp,last_update_stamp)
			values
				('{$party_id}','{$facility_id}','{$action_user}',NOW(),NOW())
    		";
	}
	$db->query($sql);
}
function del_party_facility($party_id,$facility_id,$action_user){
	global $db;
	$sql = "
		update romeo.party_facility
		SET is_delete        = 1,
        	last_update_stamp = NOW(), 
        	action_user      = '{$action_user}'
		WHERE party_id = '{$party_id}' and facility_id = '{$facility_id}' 
    		";
	$db->query($sql);
}
?>
