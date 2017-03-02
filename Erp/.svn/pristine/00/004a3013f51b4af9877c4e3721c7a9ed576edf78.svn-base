<?php
define('IN_ECS', true);
require_once ('includes/init.php');
require_once ('includes/debug/lib_log.php');

//验证权限
admin_priv('erp_database_status');

$status_res=$db->getAll("show full processlist");
$status_res_slave=$slave_db->getAll("show full processlist");

$smarty->assign("status_res",$status_res);
$smarty->assign("status_res_slave",$status_res_slave);
$smarty->display ( "database_status.dwt" );