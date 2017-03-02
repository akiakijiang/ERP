<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');
require_once('includes/lib_print_action.php');

if(isset($_REQUEST['BPSN']) && $_REQUEST['BPSN']!='0'){
	$BPSN=$_REQUEST['BPSN'];
} else $BPSN = null;

if(isset($_REQUEST['act'])){
	if ($_REQUEST['act']=="query"){
		if(is_thermal_print($BPSN)){
			$message = "该批次{$BPSN}去【热敏快递单】打印";
			$smarty->assign('message',$message);
			$BPSN = null;
			$update_done=false;
		}
	} else if ($_REQUEST['act']=="batch_print"){
		$TNS=array();
		$SID=array();
		$OID=array();
		if(isset($_REQUEST['TNS']) && $_REQUEST['TNS']!='0'){
			$TNS=$_REQUEST['TNS'];
		} 
		if(isset($_REQUEST['SID']) && $_REQUEST['SID']!='0'){
			$SID=$_REQUEST['SID'];
		} 
		if(isset($_REQUEST['OID']) && $_REQUEST['OID']!='0'){
			$OID=$_REQUEST['OID'];
		} 
		$update_done=update_shipment_tracking_number($SID,$TNS);

		LibPrintAction::addPrintRecord('BATCH_BILL',$BPSN);
	} else if ($_REQUEST['act']=="print"){
		$TNS=array();
		$SID=array();
		$OID=array();
		if(isset($_REQUEST['SSID']) && $_REQUEST['SSID']!='0'){
			$SID=array($_REQUEST['SSID']);
		} 
		if(isset($_REQUEST['SOID']) && $_REQUEST['SOID']!='0'){
			$OID=array($_REQUEST['SOID']);
		} 
		if(isset($_REQUEST['STN']) && $_REQUEST['STN']!='0'){
			$TNS=array($_REQUEST['STN']);
		} 
		$update_done=update_shipment_tracking_number($SID,$TNS);

		foreach ($TNS as $TN) {
			LibPrintAction::addPrintRecord('BILL',$TN);
		}
	}
}
$setBPSN = false;
$ifUpdateDone = false;
if(isset($BPSN)){
	$setBPSN = true;
	$shipments=getShipmentsfromBPSN($BPSN);
	$shipmentsNum = sizeof($shipments);
	if(isset($update_done) && $update_done) {
		$ifUpdateDone = true;
		$smarty->assign('order_ids',implode(',', $OID));
		//READY TO KILL by Sinri
		$src = "print_shipping_orders.php?print=1&order_id=".join(',',$OID);
		$smarty->assign('src',$src);
		
	}
	
	$smarty->assign('shipmentsNum',$shipmentsNum);
	$smarty->assign('shipments',$shipments);
	$smarty->assign('BPSN',$BPSN);
}
$smarty->assign('ifUpdateDone',$ifUpdateDone);
$smarty->assign('setBPSN',$setBPSN);
$smarty->display('Deal_CarrierBill_Print.htm');
?>
