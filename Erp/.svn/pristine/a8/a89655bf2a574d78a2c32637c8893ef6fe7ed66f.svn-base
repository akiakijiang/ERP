<?php
/**
 再次打印已经追加过的热敏面单，单张打印
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');

admin_priv('batch_add_order_shipment_arata_add');

if(isset($_REQUEST['shipment_id']) && $_REQUEST['shipment_id']!='0'){
	$shipment_id=$_REQUEST['shipment_id'];
} else $shipment_id = null;

function getOrderInfoByShipmentId($shipment_id){
	global $db;
	$sql = "SELECT  
            s.SHIPMENT_ID as shipment_id,
            s.PRIMARY_ORDER_ID as main_order_id,
            s.CARRIER_ID as carrier_id,
            eoi.shipping_name as carrier_name,
            s.TRACKING_NUMBER as tracking_number,
            eoi.shipping_id as shipping_id,
            eoi.order_sn,
            eoi.taobao_order_sn  
		FROM romeo.shipment s 
		INNER JOIN ecshop.ecs_order_info eoi on eoi.order_id = CAST(s.primary_order_id as UNSIGNED) and eoi.order_status=1
		where s.SHIPMENT_ID ='{$shipment_id}' limit 1";
	$shipment = $db->getRow($sql);
    return $shipment;
}


/*
 * 根据发货单号查询主订单的所有追加运单号记录
 */
 function getAllTrackingNumberByShipmentId($shipment_id){
 	global $db;
 	$sql="SELECT s1.shipment_id as SHIPMENT_ID, s1.tracking_number	as TRACKING_NUMBER		
		from romeo.shipment s 
		INNER JOIN romeo.order_shipment os on  os.ORDER_ID = s.PRIMARY_ORDER_ID 
		INNER JOIN romeo.shipment s1 on s1.SHIPMENT_ID = os.SHIPMENT_ID 
		where s.SHIPMENT_ID = '{$shipment_id}' and s1.SHIPPING_CATEGORY='SHIPPING_SEND' and s1.tracking_number is not null 
		ORDER BY s1.CREATED_STAMP ";
	$result=$db->getAll($sql);
	$trackArr = array();
	foreach ($result as $re) {
		array_push($trackArr,$re['TRACKING_NUMBER']);
	}
	$trackStr = "'".implode("','", $trackArr)."'";
	$sql1 = "SELECT tracking_number from ecshop.thermal_express_mailnos where tracking_number in ($trackStr)";
	$trackingArr = $db->getAll($sql1);
	$trackArrNew = array();
	foreach ($trackingArr as $tr) {
		array_push($trackArrNew,$tr['tracking_number']);
	}
	
	$resultNew = array();
	$j = 0;
	for($i=1;$i<sizeof($result);++$i){ 
		if(in_array($result[$i]['TRACKING_NUMBER'],$trackArrNew)){
			$resultNew[$j]=$result[$i];
			$j=intval(intval($j)+1);
		}
	} 
    return $resultNew;
 }


/*
 * 根据热敏快递运单号查询表add_thermal_tracking_number_print_record，得到该运单号的打印历史记录信息
 */
function getAddPrintRecordsByTrackingNumber($tracking_number){
	global $db;
    $sql="SELECT PRINT_USER, PRINT_TIME FROM romeo.add_thermal_tracking_number_print_record
		where BATCH_PICK_SN='' and TRACKING_NUMBER='{$tracking_number}'
		ORDER BY PRINT_TIME DESC ";
    $result=$db->getAll($sql);
    return $result;
}

 
/*
 * 插入表add_thermal_tracking_number_print_record，插入指定追加批次的批拣单号的或热敏运单号的打印历史记录信息
 */
function insertAddPrintRecords($TN){
	global $db;
	//为单张打印，则按运单号添加打印记录		
	$sql = "
			INSERT INTO romeo.add_thermal_tracking_number_print_record
    		(BATCH_PICK_SN, TRACKING_NUMBER, PRINT_USER, PRINT_TIME) VALUES 
    		('', '$TN', '{$_SESSION['admin_name']}', NOW())";
	$db->query($sql);
    return true;
}

$act = isset($_REQUEST['act'])?$_REQUEST['act']:(isset($_POST['act'])?$_POST['act']:null);
$is_thermal=true;	
$ifUpdateDone = false;
$ifSinglePrint = false;
$message = $_POST['message'];
$tracking_number = isset($_REQUEST['STN'])?trim($_REQUEST['STN']):'0';
global $db;


if(isset($act)){
	if ($act=="query" && isset($shipment_id)){
		$sql = "select * from romeo.shipment where shipment_id = '{$shipment_id}' ";
	    $shipment = $db->getRow($sql);
	    if(in_array($shipment['shipment_type_id'],array('146','149'))){
			$sql = "SELECT 1
			FROM  ecshop.ecs_order_info oi 
			inner join romeo.distributor_shipping pds on  pds.distributor_id = oi.distributor_id and pds.shipping_id = oi.shipping_id and pds.is_delete = 0
			where oi.order_id = '{$shipment['PRIMARY_ORDER_ID']}' limit 1 ";
		}else{
			$sql = "SELECT 1
			FROM ecshop.ecs_order_info oi 
			inner join romeo.facility_shipping pfs on  pfs.facility_id = oi.facility_id and pfs.shipping_id = oi.shipping_id and pfs.is_delete = 0
			where oi.order_id = '{$shipment['PRIMARY_ORDER_ID']}' limit 1 ";
		}
		$arata = $db->getOne($sql);
		if(empty($arata)){
			$is_thermal=false;				
			$message = "热敏专用，{$shipment_id}不允许进行追加面单的再次打印";
			$smarty->assign('message',$message);
			$shipment_id = null;
		}
	} 
	else if ($act=="single_print"){
		if(isset($_REQUEST['STN']) && $_REQUEST['STN'] !='0'){
			$ifUpdateDone = true;
			$ifSinglePrint = true;
			require_once ('includes/lib_print_action.php');
			LibPrintAction::addPrintRecord('ADD_THERMAL',$_REQUEST['STN']);
			insertAddPrintRecords($_REQUEST['STN']);
		}
	}
	else if ($act=="selectRecord"){
		$printRecords = getAddPrintRecordsByTrackingNumber($_REQUEST['STN']);
		echo(json_encode($printRecords));
		exit();
	}
}


$trackNum = 0;
if(isset($shipment_id)){
	$trackRecord = getAllTrackingNumberByShipmentId($shipment_id);
	$trackNum=sizeof($trackRecord);
	
	if($trackNum==0){
		$message = "没有追加热敏面单的记录，不能对追加的面单进行再次打印操作！";
		$shipment_id = null;
	}else{
		$shipment = getOrderInfoByShipmentId($shipment_id);
	}
	if(isset($ifUpdateDone) && $ifUpdateDone && $ifSinglePrint){
		$smarty->assign('order_id',$shipment['main_order_id']);
		$smarty->assign('tracking_number',$tracking_number);
	}
	
	$smarty->assign('shipment',$shipment);
	$smarty->assign('shipment_id',$shipment_id);
	$smarty->assign('trackRecord',$trackRecord);
	$smarty->assign('trackNum',$trackNum);
}

$smarty->assign('ifUpdateDone',$ifUpdateDone);
$smarty->assign('message',$message);
$smarty->display('shipment/add_order_shipment_arata_add_new.htm');




?>