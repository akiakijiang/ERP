<?php
/**
 * 条码打印
 * 
 * @author ljzhou 2013.08.07
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once("function.php");

$party_id = $_SESSION['party_id'];
$party_name = party_mapping($party_id);
$smarty->assign("party_name", $party_name);

$smarty->display('oukooext/print_barcodeV5.htm');


?>