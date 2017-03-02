<?php
/**
All Hail Sinri Edogawa!
此页为乐其仓库改造部队的邪恶之大鲵奉聪颖幕府之命所建。
用于根据BPSN打印发货单

@AUTHOR ljni@i9i8.com
@UPDATED 20130814

@PARAM 
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');
require_once('includes/lib_print_action.php');

if(isset($_REQUEST['act']) && $_REQUEST['act']=='ajax_record_print_action_for_single_shipment'){
	//record ajax
	$shipment_id=$_REQUEST['shipment_id'];
	$result='failed';
	if(!empty($shipment_id)){
		$pa_id=LibPrintAction::addPrintRecord('SHIPMENT',$shipment_id);
		if(!empty($pa_id)){
			$result='done';
		}
	}
	echo json_encode(array('result'=>$result));
	exit();
}

if(isset($_REQUEST['BPSN']) && $_REQUEST['BPSN']!=0){
	$BPSN=$_REQUEST['BPSN'];
} else {
	$BPSN=null;
}
$setBPSN = false;
if(isset($BPSN)) { 
	$setBPSN = true;

	//有批量拣货号，但是没有打印的 等于 没有预定库存记录 所以不能被完结 因此要在源头堵住 不能打发货单
	global $db;
	$sql="SELECT count(1) FROM romeo.inventory_location_reserve WHERE romeo.inventory_location_reserve.batch_pick_sn='$BPSN';";
	$rec_count=$db->getOne($sql);
	if($rec_count==0){
		die("没有预定记录无法打印发货单。请确保对应批拣单【".$BPSN."】已经被打印！");
	}

 	$shipment_ids=getShipmentIDsfromBPSN($BPSN);
 	$src = "shipment_print_for_batch_pick_new.php?print=1&shipment_id=".join(',',$shipment_ids);
 	$smarty->assign('sids',join(',',$shipment_ids));
 	$smarty->assign('src',$src);
 	$shipmentNum = sizeof($shipment_ids);

 	LibPrintAction::addPrintRecord('BATCH_SHIPMENT',$BPSN);
}
$smarty->assign('setBPSN',$setBPSN);
$smarty->assign('BPSN',$BPSN);
$smarty->assign('shipmentNum',$shipmentNum);
$smarty->assign('shipment_ids',$shipment_ids);
$smarty->display('Deal_Shipment_Print.htm');
	
	?>