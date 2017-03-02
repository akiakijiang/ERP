<?php
/**
 * 根据扫描信息生成条形码
 */
define('IN_ECS', true);
require_once('includes/init.php');

$smarty->display('oukooext/print_after_scan_code.htm');
?>