<?php
/**
 批量追加热敏面单，仅适用于批次批捡单全部一起添加 
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');
require_once('admin/batch_add_order_shipment_arata_new.php');

if(isset($_REQUEST['BPSN']) && $_REQUEST['BPSN']!='0'){
	$BPSN=$_REQUEST['BPSN'];
}


$is_thermal=false;
if($_REQUEST['is_thermal']=="thermal")
	$is_thermal=true;	

if($is_thermal && isset($BPSN)){
	//快递方式和仓库的组合是热敏打印方式时用热敏面单追加
	
	$isAddTrack = false;
	$ifUpdateDone = false;
	$message = $_POST['message'];
	
	if(isset($_REQUEST['act'])){
		if ($_REQUEST['act']=="query"){
			$sql = "select distinct SHIPMENT_TYPE_ID from romeo.shipment s " .
				" inner join romeo.batch_pick_mapping bpm on bpm.shipment_id = s.shipment_id " .
				" where bpm.batch_pick_sn = '{$BPSN}' ";
			$SHIPMENT_TYPE_ID = $db->getCol($sql);
			if(!(is_thermal_print($BPSN)&& count($SHIPMENT_TYPE_ID)==1 && in_array($SHIPMENT_TYPE_ID[0],array('85','89','99','100','115','12','117','44')))){//from lib_sinri_DealPrint.php
				$is_thermal=false;				
				$message = "(部分快递)热敏批次追加专用，{$BPSN}去【批量追加普通面单】打印";
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
	
}else if(isset($BPSN)){
	$message = "(部分快递)热敏批次追加专用，{$BPSN}去【追加普通面单->切换到批量追加】打印";
	$smarty->assign('message',$message);
	$BPSN = null;
}
$smarty->display('shipment/batch_add_order_shipment_arata_new.htm');


?>