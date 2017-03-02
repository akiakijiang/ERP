<?php
/**
 批量追加面单，仅适用于批次批捡单全部一起添加
 ljzhou 2013-10-19
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');
require_once('admin/batch_add_order_shipment_arata.php');

if(isset($_REQUEST['BPSN']) && $_REQUEST['BPSN']!='0'){
	$BPSN=$_REQUEST['BPSN'];
} else $BPSN = null;


$is_thermal=false;
if(isset($_REQUEST['act'])){
	if ($_REQUEST['act']=="query"){
		if(is_thermal_print($BPSN)){//from lib_sinri_DealPrint.php
			$is_thermal=true;
		}else{
			$is_thermal=false;			
		}
	}
}
if($_REQUEST['is_thermal']=="thermal")
	$is_thermal=true;	


//快递方式和仓库的组合不是热敏打印方式时用普通面单追加
if(!$is_thermal){
	
	$shipments=getShipmentsfromBPSN($BPSN);

	$message = $_POST['message'];
	
	if(isset($_REQUEST['act'])){
		if ($_REQUEST['act']=="query"){
			
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
			update_shipment_tracking_number($SID,$TNS);
			$update_done = true;
		}
	}
	if($OID) {
		$order_ids = join(',',$OID);
	}
	//pp($order_ids);
	$smarty->assign('message',$message);
	$smarty->assign('shipments',$shipments);
	$smarty->assign('shipment_size',count($shipments));
	$smarty->assign('BPSN',$BPSN);
	$smarty->assign('update_done',$update_done);
	$smarty->assign('order_ids',$order_ids);
	$smarty->display('shipment/batch_add_order_shipment.htm');
	
}else{
	//快递方式和仓库的组合是热敏打印方式时用热敏面单追加
	
	$isAddTrack = false;
	$ifUpdateDone = false;
	$message = $_POST['message'];
	
	if(isset($_REQUEST['act'])){
		if ($_REQUEST['act']=="query"){
			if(!is_thermal_print($BPSN)){//from lib_sinri_DealPrint.php
				$is_thermal=false;				
				$message = "热敏专用，{$BPSN}去【批量追加普通面单】打印";
				$smarty->assign('message',$message);
				$BPSN = null;
			}
		} 
		else if($_REQUEST['act']=="addNewTrackingNum"){
			$isAddTrack = true;			
		}
		else if ($_REQUEST['act']=="batch_add_shipment"){
			$OID=array();
			$SID=array();
			$ADDTNS=array();
			if(isset($_REQUEST['OID']) && $_REQUEST['OID']!='0'){
				$OID=$_REQUEST['OID'];
			} 
			if(isset($_REQUEST['SID']) && $_REQUEST['SID']!='0'){
				$SID=$_REQUEST['SID'];
			} 
			if(isset($_REQUEST['ADDTNS']) && $_REQUEST['ADDTNS']!='0'){
				$ADDTNS=$_REQUEST['ADDTNS'];
			} 
			$message = batch_add_tracking_number_arata($BPSN, $SID, $ADDTNS);
			$ifUpdateDone = true;
		}		
		else if ($_REQUEST['act']=="selectRecord"){
			$printRecords = getAllTrackingNumberByMainOrderId($_REQUEST['SelMainOrderId']);
			echo(json_encode($printRecords));
			exit();
		}
	}


	$setBPSN = false;
	$pici = 0;
	if(isset($BPSN)){
		$setBPSN = true;
		$isAllBound = true;
		$shipments=getShipmentsfromBPSN($BPSN);
		$shipmentsNum = sizeof($shipments);
		$pici=intval(intval(getPICIByBatch($BPSN))+1);
		if(!$isAddTrack){
			foreach ($shipments as $shipment) {
				$shipment['add_tracking_number']='';
			}
			$isAllBound = false;
		}else{
			$shipments=batch_add_thermal_tracking_number($shipments);
			foreach ($shipments as $shipment) {
				if($shipment['add_tracking_number']=='')
				   $isAllBound=false;
			}
		}
		if(isset($ifUpdateDone) && $ifUpdateDone) {
			insertBatchAddPrintRecords($BPSN,$pici);
			$smarty->assign('order_ids',implode(',', $OID));
		}
		
		$smarty->assign('isAllBound',$isAllBound);
		$smarty->assign('shipmentsNum',$shipmentsNum);
		$smarty->assign('shipments',$shipments);
		$smarty->assign('BPSN',$BPSN);
	}
	
	$smarty->assign('ifUpdateDone',$ifUpdateDone);
	$smarty->assign('setBPSN',$setBPSN);
	$smarty->assign('message',$message);
	if($is_thermal)
		$smarty->display('shipment/batch_add_order_shipment_arata.htm');
	else
		$smarty->display('shipment/batch_add_order_shipment.htm');
}



?>