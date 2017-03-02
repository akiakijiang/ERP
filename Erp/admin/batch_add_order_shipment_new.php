<?php
/**
 批量追加面单，仅适用于批次批捡单全部一起添加
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
if(isset($_REQUEST['act']) && $_REQUEST['act']=="query"){
	if(isset($BPSN) && is_thermal_print($BPSN)){//from lib_sinri_DealPrint.php
		$is_thermal=true;
	}else{
		$is_thermal=false;			
	}
}

//快递方式和仓库的组合不是热敏打印方式时用普通面单追加
if(!$is_thermal && isset($BPSN)){
	
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
	
}else if(isset($BPSN)){
	$message = "普通批次追加专用，{$BPSN}去【追加热敏面单->切换到热敏批量追加】打印";
	$smarty->assign('message',$message);
	$BPSN = null;
}

	$smarty->display('shipment/batch_add_order_shipment_new.htm');


?>