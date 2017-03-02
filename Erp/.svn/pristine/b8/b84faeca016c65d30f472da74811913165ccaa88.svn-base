<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');

$batch_pick_sn = 
    isset($_REQUEST['BPSN']) && trim($_REQUEST['BPSN']) 
    ? trim($_REQUEST['BPSN']) 
    : null ;
if(!empty($batch_pick_sn)){
	if($_REQUEST['forceBind']==1){
		bind_out_bpsn_tracking_number($batch_pick_sn);//from lib_sinri_DealPrint.php
	}else{
		//自动调度绑定
		// php ../yiic carrierBillArataBinding bindAll 
	}
}else{
	die('请输入批次号！');
}
$act = 
    isset($_REQUEST['act']) && trim($_REQUEST['act']) 
    ? trim($_REQUEST['act']) 
    : null ;
if(!empty($act)){
	if ($act == "batch_print"){
		$sql = "select distinct os.order_id
				from romeo.out_batch_pick bp 
				inner join romeo.out_batch_pick_mapping bpm on bp.batch_pick_id = bpm.batch_pick_id
				inner join romeo.order_shipment os on os.shipment_id = bpm.shipment_id
				where bp.batch_pick_sn = '{$batch_pick_sn}'
				order by bpm.batch_pick_mapping_id";
		$OID = $db->getCol($sql);
		$smarty->assign('order_ids',implode(',', $OID));
		$print_note = $_SESSION['admin_name']."于".date("Y-m-d H:i:s")."打印！";
		$sql = "update romeo.out_batch_pick 
			set print_number = print_number + 1,print_note  = CONCAT(print_note,'{$print_note}') 
			where batch_pick_sn = '{$batch_pick_sn}' ";
		$db->query($sql);
	} else if ($act == "print"){
		$smarty->assign('order_ids',$_REQUEST['selected_order_id']);
	}
}

$sql = "select print_number from romeo.out_batch_pick where batch_pick_sn = '{$batch_pick_sn}' ";
$isPrint = $db->getOne($sql);
$shipments = getShipmentsfromOutBPSN($batch_pick_sn);
$shipmentsNum = sizeof($shipments);

$isAllBound=true;
foreach ($shipments as $shipment) {
	if(empty($shipment['tracking_number'])){
		$isAllBound=false;
	}
}

$smarty->assign('isAllBound',$isAllBound);
$smarty->assign('isPrint',$isPrint);
$smarty->assign('shipmentsNum',$shipmentsNum);
$smarty->assign('shipments',$shipments);
$smarty->assign('BPSN',$batch_pick_sn);


$smarty->display('deal_out_batch_print.htm');
?>
