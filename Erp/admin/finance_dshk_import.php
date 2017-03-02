<?php
define('IN_ECS', true);
require_once('includes/init.php');

admin_priv('finance_order');
require_once("function.php");

$smarty->display('oukooext/finance_dshk_import.htm');
?>