<?php
/**
 * 中通热敏打印
 * 
 * @author last modified by qdi
 */
define('IN_ECS', true);
require_once('includes/lib_zto_express.php');

$admin_list=array('wjzhu','ljni','jche');

$isAdmin=false;

if(in_array($_SESSION['admin_name'],$admin_list)){
	$isAdmin=true;
}

//var_dump(apply_zto_thermal(100));
if($isAdmin && $_REQUEST['act'] == 'apply'){
	zto_mail_applys();
}

$sql = "select count(tracking_number) as count,status from ecshop.thermal_express_mailnos where shipping_id = 115 group by status";
$numbers = $db->getAll($sql);
$not_used_count = 0;
$using_count = 0;
$used_count = 0;
foreach($numbers as $number){
	$status = $number['status'];
	$count = $number['count'];
	if($status == 'N'){//init
		$not_used_count = $count;
	}elseif($status == 'Y'){//ing
		$using_count = $count;
	}elseif($status == 'F'){//finish
		$used_count = $count;
	}
}

$smarty->assign('isAdmin',($isAdmin?'Y':'N'));
$smarty->assign('not_used_count',$not_used_count);
$smarty->assign('using_count',$using_count);
$smarty->assign('used_count',$used_count);
$smarty->display('zto_thermal_manage.htm');

