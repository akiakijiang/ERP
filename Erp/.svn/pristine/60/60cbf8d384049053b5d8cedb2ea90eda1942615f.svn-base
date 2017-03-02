<?php
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require("ajax.php");
global $db;
$facility = $_REQUEST['facility'] ? trim($_REQUEST['facility']) : false;
$party = $_REQUEST['party'] ? trim($_REQUEST['party']) : false;
$goods_barcode = $_REQUEST['goods_barcode'] ? trim($_REQUEST['goods_barcode']) : false;
pp($facility);
pp($party);
pp($goods_barcode);


$sql_for_party = "SELECT `NAME`,PARTY_ID FROM romeo.party;";
$party_from_Mysql = $db->getAll($sql_for_party);
$sql_for_facility = "SELECT FACILITY_NAME,FACILITY_ID from romeo.facility;";
$facility_from_Mysql = $db->getAll($sql_for_facility);



$smarty->assign('facility_from_Mysql',$facility_from_Mysql);
$smarty->assign('party_from_Mysql',$party_from_Mysql);
$smarty->display("oukooext/reserve_lack_item.htm");
?>