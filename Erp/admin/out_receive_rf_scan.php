<?php
/**
 * 外包收货RF枪扫描
 * 
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once("function.php");

admin_priv('ck_out_facility_storage');

// 判断入库模式
check_in_storage_mode(0);

//查询仓库权限
check_user_in_facility();

$smarty->display('oukooext/out_receive_rf_scan.htm');

?>