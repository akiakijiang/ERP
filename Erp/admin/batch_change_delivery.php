<?php
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require("ajax.php");
global $db;
$sql_for_shipping = "SELECT shipping_name,shipping_id from ecshop.ecs_shipping 
WHERE enabled = 1
AND shipping_name IN('宅急送快递','顺丰快递','顺丰（陆运）','汇通快递','中通快递','EMS快递','EMS经济快递','申通快递','韵达快递');";
$shipping_from_Mysql = $db->getAll($sql_for_shipping);
$sql_for_party = "SELECT `NAME`,PARTY_ID FROM romeo.party ;";
$party_from_Mysql = $db->getAll($sql_for_party);
$sql_for_facility = "SELECT FACILITY_NAME,FACILITY_ID from romeo.facility;";
$facility_from_Mysql = $db->getAll($sql_for_facility);
$smarty->assign('shipping_from_Mysql',$shipping_from_Mysql);
$smarty->assign('party_from_Mysql',$party_from_Mysql);
$smarty->assign('facility_from_Mysql',$facility_from_Mysql);
$smarty->display('oukooext/batch_change_delivery.htm');

?>