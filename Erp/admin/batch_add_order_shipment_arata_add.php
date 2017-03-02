<?php
/**
 再次打印已经追加过的热敏面单，包括批量打印和单张打印
**/
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once('includes/lib_sinri_DealPrint.php');

admin_priv('batch_add_order_shipment_arata_add');



	if(isset($_REQUEST['BPSN']) && $_REQUEST['BPSN']!='0'){
		$BPSN=$_REQUEST['BPSN'];
	} else $BPSN = null;



	/*
	 * 拼接一批订单的原始热敏单号和追加热敏单号信息
	 */
	function mergeOriginalAndAdditionalTrackNum($shipments){
		$result = array();
		foreach($shipments as $shipment){		
			$mainOrderId = $shipment['main_order_id'];
			$additionalTrackNum = getAllTrackingNumberByMainOrderId($mainOrderId);			
			$trackNum = sizeof($additionalTrackNum);	
			$record = $shipment;
			$record['add_tracking_number']=array();
			if($trackNum>0){
				$record['add_tracking_number']=$additionalTrackNum;
			}
			array_push($result, $record);
	    }
	    return $result;
	}


	/*
	 * 根据主订单号查询该订单的所有追加运单号记录
	 */
	 function getAllTrackingNumberByMainOrderId($mainOrderId){
	 	global $db;
	 	$sql="select
			  	s.shipment_id as SHIPMENT_ID, s.tracking_number	as TRACKING_NUMBER		
				from
					ecshop.ecs_order_info as o  
					inner join romeo.order_shipment as os ON os.ORDER_ID = convert(o.order_id using utf8)
					inner join romeo.shipment as s ON s.shipment_id = os.shipment_id              
				where
					o.order_id='{$mainOrderId}' and s.SHIPPING_CATEGORY='SHIPPING_SEND' and s.tracking_number is not null 
				ORDER BY s.CREATED_STAMP
				";
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
	 * 根据热敏快递运单号或者批拣单号和批次数查询表add_thermal_tracking_number_print_record，得到该运单号的打印历史记录信息
	 */
	function getPrintRecordsByTrackingNumberAndBatch($batch_pick_sn, $pici, $tracking_number){
		global $db;
	    $sql="SELECT PRINT_USER, PRINT_TIME FROM romeo.add_thermal_tracking_number_print_record
				where ((BATCH_PICK_SN='' and TRACKING_NUMBER='$tracking_number') 
				OR (BATCH_PICK_SN='$batch_pick_sn' and PICI=$pici and TRACKING_NUMBER='') )
				ORDER BY PRINT_TIME DESC;";
	    $result=$db->getAll($sql);
	    return $result;
	}

 
	/*
	 * 插入表add_thermal_tracking_number_print_record，插入指定追加批次的批拣单号的或热敏运单号的打印历史记录信息
	 */
	function insertAddPrintRecords($type,$TN,$pici){
		global $db;
		if($type=="batch_print"){
		//为批量打印，则按批拣单号和追加的批次数添加打印记录	
			$arr=array();
			$arr=explode('-', $TN);
			$batch_sn=$arr[0].'-'.$arr[1];
			$sql = "
					INSERT INTO romeo.add_thermal_tracking_number_print_record
		    		(BATCH_PICK_SN, PICI, TRACKING_NUMBER, PRINT_USER, PRINT_TIME) VALUES 
		    		('{$batch_sn}', $pici, '', '{$_SESSION['admin_name']}', NOW())";
		}
		else{
		//为单张打印，则按运单号添加打印记录		
			$sql = "
					INSERT INTO romeo.add_thermal_tracking_number_print_record
	        		(BATCH_PICK_SN, TRACKING_NUMBER, PRINT_USER, PRINT_TIME) VALUES 
	        		('', '$TN', '{$_SESSION['admin_name']}', NOW())";
		}
		$db->query($sql);
	    return true;
	}



	$is_thermal=true;	
	$ifUpdateDone = false;
	$ifSinglePrint = false;
	$message = $_POST['message'];
	$pici=0;
	
	if(isset($_REQUEST['act'])){
		if ($_REQUEST['act']=="query"){
			if(!is_thermal_print($BPSN)){//from lib_sinri_DealPrint.php
				$is_thermal=false;				
				$message = "热敏专用，{$BPSN}不允许进行追加面单的再次打印";
				$smarty->assign('message',$message);
				$BPSN = null;
			}
		} 
		else if ($_REQUEST['act']=="single_print"){
			$OIDSel='';
			if(isset($_REQUEST['SOID']) && $_REQUEST['SOID']!='0'){
				$OIDSel=$_REQUEST['SOID'];
			} 
			$pici=intval($_REQUEST['SPC']);
			$ifUpdateDone = true;
			$ifSinglePrint = true;
			require_once ('includes/lib_print_action.php');
			LibPrintAction::addPrintRecord('ADD_THERMAL',$_REQUEST['STN']);
			insertAddPrintRecords("single_print",$_REQUEST['STN'],$pici);
		}
		else if ($_REQUEST['act']=="batch_print"){
			$OID=array();
			if(isset($_REQUEST['OID']) && $_REQUEST['OID']!='0'){
				$OID=$_REQUEST['OID'];
			} 
			$pici=intval($_REQUEST['SPC']);
			$ifUpdateDone = true;
			require_once ('includes/lib_print_action.php');
			LibPrintAction::addPrintRecord('ADD_BATCH_THERMAL',$BPSN);
			insertAddPrintRecords("batch_print",$BPSN,$pici);
		}
		else if ($_REQUEST['act']=="selectRecord"){
			$printRecords = getPrintRecordsByTrackingNumberAndBatch($_REQUEST['batch_pick_sn'],intval($_REQUEST['SPC']),$_REQUEST['STN']);
			echo(json_encode($printRecords));
			exit();
		}
	}


	$setBPSN = false;
	$trackNum = 0;
	$trackNumArr = array();
	$colspan=intval(5);
	if(isset($BPSN)){
		$setBPSN = true;
		$singleShipments=getShipmentsfromBPSN($BPSN);
		$shipmentsNum = sizeof($singleShipments);
		if($shipmentsNum>0){
			$trackRecord=getAllTrackingNumberByMainOrderId($singleShipments[0]['main_order_id']);
			$trackNum=sizeof($trackRecord);
		}
		
		if($trackNum==0){
			$message = "没有追加热敏面单的记录，不能对追加的面单进行再次打印操作！";
			$BPSN = null;
		}else{
			$shipments=mergeOriginalAndAdditionalTrackNum($singleShipments);
			$shipmentsNum = sizeof($shipments);
			for($i=0;$i<$trackNum;){ 
				$trackNumArr[]=$i;
				$i=intval(intval($i)+1);
			} 
			$colspan=intval(5+intval($trackNum)*3);
		}
		
//		echo $shipments[0]['add_tracking_number'][0]['TRACKING_NUMBER'];
//		echo $shipments[0]['add_tracking_number'][1]['TRACKING_NUMBER'];
		
		if(isset($ifUpdateDone) && $ifUpdateDone) {
			if($ifSinglePrint){
				$smarty->assign('order_ids',$OIDSel);
			}else{
				$smarty->assign('order_ids',implode(',', $OID));
			}
			$smarty->assign('pici',$pici);
		}
		
		$smarty->assign('shipmentsNum',$shipmentsNum);
		$smarty->assign('shipments',$shipments);
		$smarty->assign('BPSN',$BPSN);
		$smarty->assign('colspan',$colspan);
		$smarty->assign('trackNumArr',$trackNumArr);
		$smarty->assign('trackNum',$trackNum);
	}
	
	$smarty->assign('ifUpdateDone',$ifUpdateDone);
	$smarty->assign('setBPSN',$setBPSN);
	$smarty->assign('message',$message);
	if($is_thermal)
		$smarty->display('shipment/batch_add_order_shipment_arata_add.htm');
	else
		$smarty->display('shipment/batch_add_order_shipment.htm');
		
		
		



?>