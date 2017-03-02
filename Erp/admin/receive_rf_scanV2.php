<?php
/**
 * 收货RF枪扫描
 * 
 * @author ljzhou 2013.07.17
 */
 die("此入口暂不开放");
define('IN_ECS', true);
require_once('includes/init.php');
require_once("function.php");

admin_priv('ck_in_storage', 'wl_in_storage');

// 判断入库模式
check_in_storage_mode(0);

$smarty->display('oukooext/receive_rf_scanV2.htm');

?>