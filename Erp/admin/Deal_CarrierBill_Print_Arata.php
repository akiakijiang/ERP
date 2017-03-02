<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');
require_once('includes/lib_print_action.php');

//config
$useLPAtoShowCount=true;

if(isset($_REQUEST['BPSN']) && $_REQUEST['BPSN']!='0'){
	$BPSN=$_REQUEST['BPSN'];
} else $BPSN = null;
if($BPSN && in_array($BPSN,array(
'141108-0098',
'141108-0087',
'141108-0109',
'141108-0103',
'141108-0086',
'141108-0110',
'141108-0079',
'141108-0101',
'141108-0077',
'141108-0084',
'141108-0091',
'141108-0115',
'141108-0093',
'141108-0081',
'141108-0083',
'141108-0104',
'141108-0092',
'141108-0102',
'141108-0107',
'141108-0095',
'141108-0112',
'141108-0094',
'141108-0099',
'141108-0114',
'141108-0115',
'141108-0076',
'141108-0136',
'141108-0103',
'141108-0077',
'141108-0133',
'141108-0103',
'141108-0077',
'141108-0095',
'141108-0148',
'141108-0141',
'141108-0143',
))) die('临时限制重新打印');


if(isset($_REQUEST['act'])){
	if ($_REQUEST['act']=="query"){
		if(is_thermal_print($BPSN)){//from lib_sinri_DealPrint.php
			if(true || $_REQUEST['forceBind']==1){
				bind_bpsn_tracking_number($BPSN);//from lib_sinri_DealPrint.php
			}else{
				//自动调度绑定
			}
		}else{
			$message = "热敏专用，{$BPSN}去普通【打印批件面单】打印";
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
		//$update_done=update_shipment_tracking_number($SID,$TNS);
		$update_done=true;
		insertPrintRecords("batch_print",$BPSN);

		LibPrintAction::addPrintRecord('BATCH_THERMAL',$BPSN);
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
		//$update_done=update_shipment_tracking_number($SID,$TNS);
		$update_done=true;
		insertPrintRecords("print",$TNS);

		foreach ($TNS as $TN) {
			LibPrintAction::addPrintRecord('THERMAL',$TN);
		}
	} 
	else if ($_REQUEST['act']=="selectRecord"){
		if(!$useLPAtoShowCount){
			$printRecords = getPrintRecordsByTrackingNumber($_REQUEST['batch_pick_sn'],$_REQUEST['SelTrackNum']);
		}else{
			$printRecords_batch = LibPrintAction::getRecordsForItemOfType('BATCH_THERMAL',$_REQUEST['batch_pick_sn']);
			$printRecords_single = LibPrintAction::getRecordsForItemOfType('THERMAL',$_REQUEST['SelTrackNum']);

			$printRecords=array();

			foreach ($printRecords_batch as $key => $value) {
				$printRecords[$value['create_time']]=array(
					'PRINT_TIME'=>$value['create_time'],
					'PRINT_USER'=>$value['create_user'],
				);
			}
			foreach ($printRecords_single as $key => $value) {
				$printRecords[$value['create_time']]=array(
					'PRINT_TIME'=>$value['create_time'],
					'PRINT_USER'=>$value['create_user'],
				);
			}

			$printRecords=array_values($printRecords);
		}

		echo(json_encode($printRecords));
		exit();
	}
}



$setBPSN = false;
$ifUpdateDone = false;
if(isset($BPSN)){
	$setBPSN = true;
	$singleShipments=getShipmentsfromBPSN($BPSN);

	if(!$useLPAtoShowCount){
		$shipments=mergeShippingAndTrackPrintRecord($BPSN,$singleShipments); // OLD
		// print_r($shipments);
	}else{
		$shipments=LibPrintAction::mergeRecordsForArataShipments($BPSN,$singleShipments);// NEW
		// print_r($shipments);
	}
	
	$shipmentsNum = sizeof($shipments);
	if(isset($update_done) && $update_done) {
		$ifUpdateDone = true;
		$smarty->assign('order_ids',implode(',', $OID));
		//READY TO KILL by Sinri
		// $src = "print_shipping_orders.php?print=1&arata=1&order_id=".join(',',$OID);
		// $smarty->assign('src',$src);
		
		if(!$useLPAtoShowCount){
			$shipments=mergeShippingAndTrackPrintRecord($BPSN,$singleShipments); // OLD
		}else{
			$shipments=LibPrintAction::mergeRecordsForArataShipments($BPSN,$singleShipments);// NEW
		}
	}
	//print_r($shipments);

	$isAllBound=true;
	$printMessage = "";
	$printArr = array();
	$printMutipleStr = "";
	foreach ($shipments as $key=>$shipment) {
		if(empty($shipment['tracking_number'])){
			$shipments[$key]['isAllBound']=false;
			$isAllBound=false;
		}else{
			$shipments[$key]['isAllBound']=true;
		}
		 
		if($shipment['countNum']>0){
			$printMessage = "此批次有运单已打印";
			array_push($printArr, $shipment['tracking_number']);
		} 
	}
	$hasMutiplyCount = sizeof($printArr);
	$printMutipleStr = (string)implode(',',$printArr);
	
	$batch_print_url=getDivCarrierBillPrintURL($OID[0]);//'print_shipping_orders_arata.php';
	$smarty->assign('batch_print_url',$batch_print_url);
	
	$smarty->assign('isAllBound',$isAllBound);
	$smarty->assign('shipmentsNum',$shipmentsNum);
	$smarty->assign('shipments',$shipments);
	$smarty->assign('BPSN',$BPSN);
	$smarty->assign('printMessage',$printMessage);
	$smarty->assign('hasMutiplyCount',$hasMutiplyCount);
	$smarty->assign('printMutipleStr',$printMutipleStr);
}


$smarty->assign('ifUpdateDone',$ifUpdateDone);


$smarty->assign('setBPSN',$setBPSN);
$smarty->display('Deal_CarrierBill_Print_Arata.htm');

function getDivCarrierBillPrintURL($order_id){
	global $db;

	// 触手を伸ばすか。
	$tantacle_go=true;

	if($tantacle_go){
		$sql="SELECT party_id,facility_id,shipping_id FROM ecshop.ecs_order_info WHERE order_id='{$order_id}'";
		$info=$db->getRow($sql);
		if(!empty($info)){
			if( 
				//$info['party_id']=='65569' && // - 安满 + 
				!in_array($info['facility_id'],array('24196974','137059426')) // 非贝亲青浦仓,上海精品仓
			){
				// 首先成为DIV版打印的小白鼠
				return 'print_shipping_orders_divs.php';
			}
		}
	}
	return 'print_shipping_orders_arata.php';
}
?>
