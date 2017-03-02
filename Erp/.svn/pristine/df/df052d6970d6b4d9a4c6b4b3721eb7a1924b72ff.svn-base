<?php
/*
 * Created on 2014-08-07 by qdi
 * 耗材出库设置
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);

require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('consumablePartyFacility');

require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

$party_id = $_SESSION['party_id'];
// 修改订单状态
if($_REQUEST['act']=='add'){
	$facility_id = $_REQUEST['facility_id'];
	add_party_facility($party_id,$facility_id,$_SESSION['admin_name']);
}else if($_REQUEST['act']=='del'){
	$facility_id = $_REQUEST['facility_id'];
	del_party_facility($party_id,$facility_id,$_SESSION['admin_name']);
}

$sql = " select pf.facility_id,pf.is_delete,pf.action_user,pf.last_update_stamp,f.facility_name
		 	from  romeo.party_facility_for_consumable pf
			inner join romeo.facility f on f.facility_id = pf.facility_id 
			WHERE pf.party_id = '{$party_id}' ";
$party_facility_list = $db->getAll($sql);

$sql = "select f.facility_id,f.facility_name
	from romeo.party_relation pr
	inner join romeo.facility f ON (pr.PARENT_PARTY_ID = f.OWNER_PARTY_ID OR f.OWNER_PARTY_ID='65535')
	where pr.party_id = '{$party_id}'
	  and NOT EXISTS (select 1 from romeo.party_facility_for_consumable WHERE party_id = '{$party_id}' and facility_id = f.facility_id)";
$facility_list = $db->getAll($sql);

$smarty->assign('party_facility_list', $party_facility_list);  // 曾使用过的仓库
$smarty->assign('facility_list', $facility_list);  // 未使用过的仓库
$smarty->display('consumable_party_facility.htm');

function add_party_facility($party_id,$facility_id,$action_user){
	global $db;
	$sql = "
			select 1 from  romeo.party_facility_for_consumable
			WHERE party_id = '{$party_id}' and facility_id = '{$facility_id}'
				";
	$already_exit = $db->getRow($sql);
	if($already_exit){
		$sql = "
			update romeo.party_facility_for_consumable
			SET is_delete        = 0,
	        	last_update_stamp = NOW(), 
	        	action_user      = '{$action_user}'
			WHERE party_id = '{$party_id}' and facility_id = '{$facility_id}'
				";
	}else{
		$sql = "
		insert into romeo.party_facility_for_consumable
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
		update romeo.party_facility_for_consumable
		SET is_delete        = 1,
        	last_update_stamp = NOW(), 
        	action_user      = '{$action_user}'
		WHERE party_id = '{$party_id}' and facility_id = '{$facility_id}' 
    		";
	$db->query($sql);
}
?>
