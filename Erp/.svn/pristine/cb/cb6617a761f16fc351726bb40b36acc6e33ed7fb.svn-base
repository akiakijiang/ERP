<?php
/*
在公元2013年8月，乐其ERP杭州的一伙人折腾了一下仓库。
关于封装的大鲵写的的所有php功能函数接口和实现全部在这里。
为证明大鲵干了一大堆预期外的活，保留此最早的打印的文件名

All Hail Sinri Edogawa!

@author Sinri Edogawa ljni@i9i8.com
@version Koiato
@updated 20130802
@updated 20130809
@updated 20130904

CHECKED AND CLEANED 20130911 TERRISTED
*/
require_once('init.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');
require_once(ROOT_PATH . 'admin/function.php');
// require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
require_once(ROOT_PATH . 'admin/includes/lib_express_arata.php');
/**
列出批拣单，默认40张
批拣单下订单的发货单状态的统计
CHECKED ON 20130911

print_batch_pick.php
**/
function list_recent_BPs($condition){
  global $db;
    $sql = "SELECT bp.batch_pick_sn,bp.action_user,bp.is_pick as pick_status,if(bp.is_pick='Y','已批拣',if(bp.is_pick='N','未批拣','批拣有问题')) as is_pick,
    			bp.created_stamp,bp.last_updated_stamp,bpe.employee_name,bpe.employee_no,bp.employee_bind_stamp,
      		  os.SHIPMENT_ID,oi.shipping_status,count(DISTINCT(oi.order_id)) as count
    		  from  romeo.batch_pick  bp
			   LEFT JOIN romeo.batch_pick_mapping bpm ON bp.batch_pick_sn=bpm.batch_pick_sn
			   LEFT JOIN romeo.batch_pick_employee bpe ON bpe.employee_id = bp.batch_pick_employee_id
			   LEFT JOIN romeo.order_shipment os ON os.SHIPMENT_ID=bpm.SHIPMENT_ID 
			   LEFT JOIN ecshop.ecs_order_info oi ON oi.order_id = cast(os.order_id as unsigned)
			   WHERE oi.party_id = {$_SESSION['party_id']}
			  {$condition}
              GROUP BY bp.batch_pick_sn,oi.shipping_status -- 统计发货单shipping_status
              ORDER BY bp.created_stamp desc
              limit 4000 ";
    $result=$db->getAll($sql);
    return $result;
}
/**
给一批Shipment，检查其是否已经被批拣登记。
if $shipment_id_array is not all free, return 0; or false;
shipment[]中没有被登记的shipment_id，则生成一个batch_pick_sn，于batch_pick表立项,并将相应batch_pick_sn和shipment_id写入batch_pick_mapping
接口定义中间件实现 ljni@i9i8.com
监工幕府的WebService接口，已经按照返回值示例填满，但是还没有在ROMEO里测过
CHECKED ON 20130911
USED IN
Deal_Batch_Pick.php
**/
function record_shipments_to_batch_pick($shipment_id_array,$AU){
  QLog::log("record_shipments_to_batch_pick($shipment_id_array,$AU) called");

  $msg3="";

  $p=array();
  $p['actionUser']=$AU;
  $p['shipmentIds']=array();
  foreach ($shipment_id_array as $key => $value) {
     $p['shipmentIds'][]=$value;
  }
  if(isset($handle))unset($handle);
  $handle=soap_get_client('InventoryService');
  //同步锁orderIds
  $all_orderIds_block = true;
  $order_ids = array();
  $order_ids = get_order_ids_by_shipment_ids($shipment_id_array);
  $lock_order_ids = array();
  $lock_files = array();
  for($i=0;$i<count($order_ids);$i++){
  	$lock_order_ids[$i] = get_file_lock_path($order_ids[$i],"pick_merge");
  	$lock_files[$i] = fopen($lock_order_ids[$i],"w+");
  	if(!flock($lock_files[$i], LOCK_EX|LOCK_NB, $all_orderIds_block_ref)){
      $msg3="Order [ID ".$order_ids[$i].'] LOCKED.';
  	  for($j=0;$j<=$i;$j++) fclose($lock_files[$j]);
  	  $all_orderIds_block = false;
  	  break;
  	}
  }

  $answer = '';	
  if($all_orderIds_block){
    global $db;
    $sql="SELECT party_id,facility_id FROM ecshop.ecs_order_info where order_id=".$order_ids[0];
    $rrr=$db->getRow($sql);

	  /*文件锁*/
	  $lock_file_name = get_file_lock_path( 'mapping.batch',$rrr['party_id'].'_'.$rrr['facility_id']);
	  $lock_file_point = fopen($lock_file_name, "w+");
	  $would_block = false;
	  if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
		  $result=$handle->createBatchPickMapping($p);
      //sleep(20);
		  flock($lock_file_point, LOCK_UN);
	    fclose($lock_file_point);
		  if(isset($result->return->entry)){
		    $entries=$result->return->entry;
		    foreach ($entries as $no => $entry) {
		      if ($entry->key=="batchPickSn"){
		        $sn=$entry->value->stringValue;
		        if(isset($sn)){
		          QLog::log("record_shipments_to_batch_pick($shipment_id_array,$AU) OK BPSN=$sn");
              if($sn!=''){
  		          // foreach ($shipment_id_array as $key => $shipment_id){
  		          	// 更新订单的状态为已经打印
  		          	// $order_ids_list = get_order_ids_by_shipment_id($shipment_id);
  		          	// foreach ($order_ids_list as $order_id){
  		          	// 	update_order_mixed_status($order_id['order_id'], array('pick_list_status'=>'printed'), 'worker');
  		          	// }
  		          // }
              }else{
                QLog::log("record_shipments_to_batch_pick($shipment_id_array,$AU) EMPTY BPSN=$sn");
              }
		          $answer =  $sn;
		        }
		      } else if ($entry->key=="error"){
		        $msg1=$entry->value->stringValue;
		      } else if($entry->key=="goodsNotEnoughList"){
		        $msg2=$entry->value->stringValue;
		      }
		    }
		  }
	  }else{
		  fclose($lock_file_point);
		  $msg1='其他业务组有人正在操作,请稍后。。。';
	  }
	  
	  for($i=0;$i<count($order_ids);$i++){
	  	flock($lock_files[$i], LOCK_UN);
	  	fclose($lock_files[$i]);
	  	unlink($lock_order_ids[$i]);
	  	if(file_exists($lock_order_ids[$i])){
	  		QLog::log("pick_merge lock for order_id = ".$order_ids[$i]." failed to release ");
	  	}
	  	 
	  }	
  }
  
  if($answer=='') return array('bpsn'=>0,'error'=>$msg1.'<br>'.$msg2.'<br>'.$msg3);
  else return array('bpsn'=>$answer);
}
/**
给一个BPSN求发货单号数组
ljni@i9i8.com
很早之前定义的后来各种外包跳票。
于是在平成二十五年八月十九日自力更生 T_T
CHECKED ON 20130911
USED IN
Deal_Shipment_Print.php
**/
function getShipmentIDsfromBPSN($BPSN){
    global $db;
    $sql="SELECT shipment_id FROM romeo.batch_pick_mapping WHERE batch_pick_sn='$BPSN';";
    $result=$db->getAll($sql);
    // QLog::log("getShipmentIDsfromBPSN($BPSN) SQL=$sql");
    $shipment_ids=array();
    foreach ($result as $key => $oneline) {
        $shipment_ids[]=$oneline['shipment_id'];
        QLog::log("getShipmentIDsfromBPSN($BPSN) GET shipment_id ".$oneline['shipment_id']);
    }
    return $shipment_ids;
}


/*增加热敏面单打印提醒之前的备份
 * 给一个BPSN求发货单信息数组
接口定义 ljni@i9i8.com
CHECKED ON 20130911
USED IN
Deal_CarrierBill_Print.php
*/
function getShipmentsfromBPSN($BPSN){
    global $db;
    $sql="SELECT  
    		bpm.grid_id as grid_id,
            s.SHIPMENT_ID as shipment_id,
            s.PRIMARY_ORDER_ID as main_order_id,
            s.CARRIER_ID as carrier_id,
            eoi.shipping_name as carrier_name,
            if(oa.attr_value!=100 and oa.attr_value is not null,'ERROR',s.TRACKING_NUMBER) as tracking_number,
            eoi.shipping_id as shipping_id,
            eoi.order_sn,
            eoi.taobao_order_sn 
		FROM romeo.shipment s
		INNER JOIN romeo.order_shipment os on os.SHIPMENT_ID = s.SHIPMENT_ID and s.PRIMARY_ORDER_ID = os.ORDER_ID
		INNER JOIN romeo.batch_pick_mapping bpm on bpm.shipment_id = s.SHIPMENT_ID 
		INNER JOIN ecshop.ecs_order_info eoi on eoi.order_id = CAST(s.primary_order_id as UNSIGNED) and eoi.order_status=1 
		LEFT JOIN ecshop.order_attribute oa on oa.order_id = eoi.order_id and oa.attr_name = 'JDsendCode' AND eoi.shipping_id=146
		where bpm.batch_pick_sn = '{$BPSN}'";
    $result=$db->getAll($sql);
    // QLog::log("getShipmentsfromBPSN($BPSN) SQL=$sql");
    return $result;
}




/*
 * 拼接发货单和运单打印信息
 */
 function mergeShippingAndTrackPrintRecord($BPSN,$shipments){
 	$trackNumArr=array();
 	foreach ($shipments as $shipment) {
 		$trackNum="'".$shipment['tracking_number']."'";
 		array_push($trackNumArr, $trackNum);
 	}
 	$trackNumStr = (string)implode(',',$trackNumArr);
 	
 	global $db;
 	//获取每个热敏运单号的最近一条打印历史记录
 	$allBatchSnRecords=getPrintRecordsByBatchSn($BPSN);
 	$batchNum=sizeof($allBatchSnRecords);
 	$batchSnRecords=getLastPrintRecordsByBatchSn($BPSN);
 	$trackNumRecords=getLastPrintRecordsByTrackingNumber($trackNumStr);
 	$track2printRecords=array();
	if($batchNum>0){
		$batchSnRecords['countNum']=$batchNum;
		$track2printRecords[$BPSN]=$batchSnRecords;
 		//该批拣单号有打印历史记录时，需要比较该批拣单号和各个热敏单号的最近一条打印记录
 		foreach ($trackNumRecords as $trackNumRecord) {
 			if($trackNumRecord['TRACKING_NUMBER_PRINT_ID']>$batchSnRecords['TRACKING_NUMBER_PRINT_ID']){
 				$printRecord=$trackNumRecord;
 			}else{
 				$printRecord=$batchSnRecords;
 			}
 			$printRecord['countNum']=intval(intval($trackNumRecord['countNum'])+intval($batchNum));
 			$key=$trackNumRecord['TRACKING_NUMBER'];
 			$track2printRecords[$key]=$printRecord;
 		}
 	}else{
 		//该批拣单号无打印历史记录时，获取各个热敏单号的最近一条打印记录即可
 		foreach ($trackNumRecords as $trackNumRecord) {
			$key=$trackNumRecord['TRACKING_NUMBER'];
 			$track2printRecords[$key]=$trackNumRecord;
 		}
 	}
 	
 	//拼接发货单数组和热敏运单打印历史记录数组
 	$result = array();
 	$batch=$track2printRecords[$BPSN];
 	foreach ($shipments as $shipment) {
 		$record = array();
 		$tracking_number=$shipment['tracking_number'];
 		$track=$track2printRecords[$tracking_number];
 		$new=array('countNum'=>0,'PRINT_USER'=>'','PRINT_TIME'=>'');
 		if(sizeof($track)>0){
 			$new=$track;
 		}else{
 			if(sizeof($batch)>0){
 				$new=$batch;
 			}
 		}
		$record['countNum']=$new['countNum'];
		$record['PRINT_USER']=$new['PRINT_USER'];
		$record['PRINT_TIME']=$new['PRINT_TIME'];
		$newRecord = array_merge($shipment, $record); 
		array_push($result, $newRecord);
 	}
 	
 	return $result;
 }


/*
 * 根据批拣单号查询表thermal_tracking_number_print_record，得到该批拣单号的最近的一条打印历史记录信息
 */
function getLastPrintRecordsByBatchSn($batch_pick_sn1){
	global $db;
    $sql="SELECT TRACKING_NUMBER_PRINT_ID, BATCH_PICK_SN, TRACKING_NUMBER, PRINT_USER, PRINT_TIME  
			FROM romeo.thermal_tracking_number_print_record
			WHERE BATCH_PICK_SN = '$batch_pick_sn1'
			ORDER BY PRINT_TIME DESC;";
    $result=$db->getRow($sql);
    return $result;
}

/*
 * 根据批拣单号查询表thermal_tracking_number_print_record，得到该批拣单号的所有打印历史记录信息
 */
function getPrintRecordsByBatchSn($batch_pick_sn1){
	global $db;
    $sql="SELECT TRACKING_NUMBER_PRINT_ID, BATCH_PICK_SN, TRACKING_NUMBER, PRINT_USER, PRINT_TIME
			FROM romeo.thermal_tracking_number_print_record
			WHERE BATCH_PICK_SN = '$batch_pick_sn1'
			ORDER BY PRINT_TIME DESC;";
    $result=$db->getAll($sql);
    return $result;
}

/*
 * 根据热敏单号列表查询表thermal_tracking_number_print_record，得到该热敏运单号列表中每个运单的最近的一条打印历史记录信息
 */
function getLastPrintRecordsByTrackingNumber($trackNumStr){
	global $db;
    $sql="SELECT TRACKING_NUMBER_PRINT_ID, BATCH_PICK_SN, TRACKING_NUMBER, PRINT_USER, PRINT_TIME 
			FROM romeo.thermal_tracking_number_print_record
			WHERE BATCH_PICK_SN = '' AND TRACKING_NUMBER IN ($trackNumStr)
			ORDER BY PRINT_TIME DESC;";
    $results=$db->getAll($sql);
    $records = array();
    $trackArr = array();
    $trackResult = array();
    foreach ($results as $result) {
    	$tracking_number=$result['TRACKING_NUMBER'];
    	if(in_array($tracking_number,$trackArr)){
    		$num=$trackResult[$tracking_number]['countNum'];
    		if($trackResult[$tracking_number]['TRACKING_NUMBER_PRINT_ID']<$result['TRACKING_NUMBER_PRINT_ID']){
    			$trackResult[$tracking_number]=$result;
    		}
    		$trackResult[$tracking_number]['countNum']=intval(intval($num)+1);
    	}else{
    		$trackResult[$tracking_number]=$result;
    		$trackResult[$tracking_number]['countNum']=1;
    		array_push($trackArr, $tracking_number);
    	}
    }
    
    return $trackResult;
}

/*
 * 根据热敏快递运单号或者批拣单号查询表thermal_tracking_number_print_record，得到该运单号的打印历史记录信息
 */
function getPrintRecordsByTrackingNumber($batch_pick_sn1, $tracking_number1){
	global $db;
    $sql="SELECT PRINT_USER, PRINT_TIME FROM romeo.thermal_tracking_number_print_record
			where ((BATCH_PICK_SN='' and TRACKING_NUMBER='$tracking_number1') 
			OR (BATCH_PICK_SN='$batch_pick_sn1' and TRACKING_NUMBER='') )
			ORDER BY PRINT_TIME DESC;";
    $result=$db->getAll($sql);
    return $result;
}

/*
 * 插入表thermal_tracking_number_print_record，插入指定批拣单号或热敏快递运单号的打印历史记录信息
 */
function insertPrintRecords($type,$TN){
	global $db;
	if($type=="batch_print"){
	//为批量打印，则按批拣单号添加打印记录	
		$arr=array();
		$arr=explode('-', $TN);
		$batch_sn=$arr[0].'-'.$arr[1];
		$sql = "
				INSERT INTO romeo.thermal_tracking_number_print_record
        		(BATCH_PICK_SN, TRACKING_NUMBER, PRINT_USER, PRINT_TIME) VALUES 
        		('{$batch_sn}','', '{$_SESSION['admin_name']}', NOW())";
	}
	else{
	//为单张打印，则按运单号添加打印记录		
		$sql = "
				INSERT INTO romeo.thermal_tracking_number_print_record
        		(BATCH_PICK_SN, TRACKING_NUMBER, PRINT_USER, PRINT_TIME) VALUES 
        		('', '{$TN[0]}', '{$_SESSION['admin_name']}', NOW())";
	}
	$db->query($sql);
    return true;
}






function getShipmentsfromOutBPSN($BPSN){
    global $db;
    $sql="select os.shipment_id,os.order_id,oi.shipping_name as carrier_name,s.TRACKING_NUMBER as tracking_number
			from romeo.out_batch_pick bp 
			inner join romeo.out_batch_pick_mapping bpm on bp.batch_pick_id = bpm.batch_pick_id
			inner join romeo.order_shipment os on os.shipment_id = bpm.shipment_id
			inner join romeo.shipment s on os.shipment_id = s.shipment_id
			inner join ecshop.ecs_order_info oi on os.order_id = oi.order_id 
			where bp.batch_pick_sn = '{$BPSN}'
			order by bpm.batch_pick_mapping_id";
    $result=$db->getAll($sql);
    return $result;
}
/**
给出Shipment_id的数组和TrackingNumber的数组。。登记之。
接口定义 ljni@i9i8.com
实现 ljni@i9i8.com
CHECKED ON 20130911
USED IN
Deal_CarrierBill_Print.php
**/
function update_shipment_tracking_number($SID,$TNS){
  QLog::log("update_shipment_tracking_number($SID,$TNS) called");
  for ($i=0; $i < sizeof($SID); $i++) {
      update_shipment_tracking_number_single($SID[$i],$TNS[$i]);
  }
  QLog::log("update_shipment_tracking_number($SID,$TNS) processed $i");
  return $i;
}
/**
给出Shipment_id的数组和TrackingNumber。。登记之。
顺便刷新ecs_carrier_bill，呜呼，什么破表。// It has been killed, Hallelujah
接口定义 ljni@i9i8.com
实现 ljni@i9i8.com
SQL单元测试PASS ljni@i9i8.com
CHECKED ON 20130911
USED BY update_shipment_tracking_number
IN
Deal_CarrierBill_Print.php
**/
function update_shipment_tracking_number_single($oneSID,$oneTNS){
  QLog::log("update_shipment_tracking_number_single($oneSID,$oneTNS) called");
  $oneSID=trim($oneSID);
  $oneTNS=trim($oneTNS);
  global $db;
  $sql_find_bills="SELECT
      oi.carrier_bill_id,oi.order_status,oi.shipping_status,oi.pay_status,oi.order_id,s.tracking_number
      FROM
      ecshop.ecs_order_info oi
      left join romeo.order_shipment os ON oi.order_id = cast(os.order_id as unsigned)
      left join romeo.shipment s ON os.shipment_id = s.shipment_id
      where os.shipment_id = '{$oneSID}'
      ";
  $ori_bills=$db->getAll($sql_find_bills);
  QLog::log("update_shipment_tracking_number_single($oneSID,$oneTNS) SQL=$sql_find_bills");
  
  if(isset($handle))unset($handle);
  $handle=soap_get_client('ShipmentService');
  $handle->updateShipment(array(
      'shipmentId'=>$oneSID,
      'trackingNumber'=>$oneTNS,
      'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
  ));
  QLog::log("update_shipment_tracking_number_single($oneSID,$oneTNS) updateShipment(array(
      'shipmentId'=>$oneSID,
      'trackingNumber'=>$oneTNS,
      'lastModifiedByUserLogin'=>".$_SESSION['admin_name'].",
  ));");
  
  
  foreach ($ori_bills as $k1 => $line) {
    // ECB has been killed , RIP. Sinri 20160105
    // $bill=$line['carrier_bill_id'];
    // QLog::log("update_shipment_tracking_number_single($oneSID,$oneTNS) Found carrier_bill_id ".$line['carrier_bill_id']);
    // $sql=sprintf("UPDATE ecshop.ecs_carrier_bill SET bill_no = '%s' WHERE bill_id = '%d' LIMIT 1",$oneTNS,$bill);
    // $db->query($sql);
    // QLog::log("update_shipment_tracking_number_single($oneSID,$oneTNS) UPDATED SQL=$sql");
    
    if(!empty($ori_bills[$k1]['tracking_number'])) {
    	 $note = "批拣扫描快递面单, 面单号从{$ori_bills[$k1]['tracking_number']}改为{$oneTNS}";
    } else {
    	 $note = "批拣扫描快递面单, 面单号为：{$oneTNS}";
    }
    
    // 记录订单备注
    $sql = "
        INSERT INTO ecshop.ecs_order_action 
        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
        ('{$ori_bills[$k1]['order_id']}', '{$ori_bills[$k1]['order_status']}', '{$ori_bills[$k1]['shipping_status']}', '{$ori_bills[$k1]['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
    ";
    // QLog::log("update_shipment_tracking_number_single action_note ($oneSID,$oneTNS) UPDATED SQL=$sql");
    
    $db->query($sql);
    	
  } 
  return 1;
}

/**
 * 判断输入批次是否为热敏
 * 2015.10.10
 * 1. romeo.facility_shipping  组合中is_delete=0 的仓库快递组合 ：包含四通一达 + 速达快递
 * 2. romeo.distributor_shipping  组合中is_delete=0 的店铺+（京东COD/京东配送）
 */
function is_thermal_print($bpsn){
	 global $db;
	 $sql="SELECT 1
		FROM romeo.batch_pick_mapping  bpm
		inner join romeo.order_shipment os on bpm.shipment_id = os.shipment_id
		inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id
		inner join romeo.distributor_shipping pds on  pds.distributor_id = oi.distributor_id 
				and pds.shipping_id = oi.shipping_id
		where bpm.batch_pick_sn = '{$bpsn}' and pds.is_delete = 0 limit 1";
	$is_thermal_print = $db->getOne($sql);
	if(!empty($is_thermal_print)){
		return true;
	}
	$sql="SELECT 1
		FROM romeo.batch_pick_mapping  bpm
		inner join romeo.order_shipment os on bpm.shipment_id = os.shipment_id
		inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id
		inner join romeo.facility_shipping pfs on  pfs.facility_id = oi.facility_id 
				and pfs.shipping_id = oi.shipping_id
		where bpm.batch_pick_sn = '{$bpsn}' and pfs.is_delete = 0 limit 1";
	$is_thermal_print = $db->getOne($sql);
	if(empty($is_thermal_print)){
		return false;
	}else{
		return true;
	}
}

/**
 * 为批次绑定运单
 */
function bind_bpsn_tracking_number($bpsn){
	global $db;
	$sql="SELECT bpm.shipment_id,s.SHIPMENT_TYPE_ID
			FROM romeo.batch_pick_mapping  bpm
			inner join romeo.shipment s on bpm.shipment_id = s.shipment_id 
			inner join romeo.order_shipment os on os.order_id = s.primary_order_id and s.shipment_id = os.shipment_id
			where bpm.batch_pick_sn = '{$bpsn}' and (s.tracking_number = ''  or s.tracking_number is null)  ";
    $shipments = $db->getAll($sql);
    foreach($shipments as $shipment){
    	if(!add_thermal_tracking_number($shipment['shipment_id'])){//next function
    		return false;
    	}
    }
    if($shipments[0]['SHIPMENT_TYPE_ID']=='115'){
		$sql = "select oi.order_id,oi.facility_id,r1.region_name as province_name,r2.region_name as city_name,r3.region_name as district_name,oi.address
			from romeo.batch_pick_mapping  bpm 
			inner join romeo.order_shipment os on bpm.shipment_id = os.shipment_id 
			inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id 
			left join ecshop.order_attribute oa on oa.order_id = oi.order_id and oa.attr_name='ztoBigPen' 
			left join ecshop.ecs_region r1 on r1.region_id = oi.province 
			left join ecshop.ecs_region r2 on r2.region_id = oi.city
			left join ecshop.ecs_region r3 on r3.region_id = oi.district 
			where bpm.batch_pick_sn = '{$bpsn}' and oa.attribute_id is null AND oi.shipping_id = 115 ";
		$applyZtoMarkOrders = $db->getAll($sql);
		if(count($applyZtoMarkOrders)>0){
	//		qlog::log("test_tn_mark: ".date('y-m-d h:i:s',time())."start count:".count($applyZtoMarkOrders)."\n");
			foreach($applyZtoMarkOrders as $applyZtoMarkOrder){
				get_zto_mark_single_order($applyZtoMarkOrder);
			}
	//		qlog::log("test_tn_mark: ".date('y-m-d h:i:s',time())."end count:".count($applyZtoMarkOrders)."\n");
		}
    }
    return true;
}
function bind_out_bpsn_tracking_number($bpsn){
	global $db;
	$sql="SELECT bpm.shipment_id 
			FROM romeo.out_batch_pick bp 
			inner join romeo.out_batch_pick_mapping  bpm on bp.batch_pick_id = bpm.batch_pick_id
			inner join romeo.shipment s on bpm.shipment_id = s.shipment_id
			where bp.batch_pick_sn = '{$bpsn}' and (s.tracking_number = ''  or s.tracking_number is null)  ";
    $shipments = $db->getAll($sql);
    foreach($shipments as $shipment){
    	if(!add_thermal_tracking_number($shipment['shipment_id'])){//next function
    		return false;
    	}
    }
    return true;
}

/**
 * 根据发货单号绑定运单号
 * 1. 圆通单独通过接口拉取（同时获取大头笔信息，插入到ecshop.order_attribute中attr_name='bigPen';运单号插入到thermal_express_mailnos-'R'）
 * 2. 韵达单独通过接口拉取（同时获取大头笔等信息，插入到ecshop.ecs_order_yunda_mailno_apply;运单号插入到thermal_express_mailnos-'R'） 
 * *** 韵达获取方式有两种： 1. 贝亲账户只用于贝亲青浦仓使用加密的pdf_info原方式纸张打印  2. 其他账户使用非加密的pdf_info通用面单纸打印
 * 3. 申通（批量拉取绑定模式，通过ecshop.thermal_express_mailnos ）
 * 4. 京东COD,京东配送（批量拉取绑定模式，通过ecshop.jd_bill_code）   -- 如果“不能京配”，也允许打印，不过打印格式上会有所改变
 * 5. 速达快递 SD.shipment_id   ---并未投入使用
 * 6. 宅急送快递 (暂未进行接口对接，手动存储在ecshop.thermal_express_mailnos，可直接匹配)
 * 7. 中通快递绑单接口（使用批量绑单接口（最多50单，不能根本解决一个批次一次绑定）进行单独绑单）
 * 8. 汇通快递绑单接口（使用批量绑单接口（最多100单，不能根本解决一个批次一次绑定）进行单独绑单）
 * 9. 顺丰快递、陆运 单独拉取
 */
function add_thermal_tracking_number($shipmentId){
	global $db;
	$sql="SELECT
	      oi.order_id,oi.order_sn,oi.shipping_id,oi.distributor_id,
        oi.carrier_bill_id,oi.order_status,oi.shipping_status,oi.pay_status,
        s.tracking_number,oi.facility_id
	      FROM
	      ecshop.ecs_order_info oi
	      inner join romeo.order_shipment os ON oi.order_id = cast(os.order_id as unsigned)
	      inner join romeo.shipment s ON os.shipment_id = s.shipment_id
	      where os.shipment_id = '{$shipmentId}' and oi.order_status = 1 limit 1
	";
	$order=$db->getRow($sql);
	
	if(empty($order)){
		echo('<div style="color:red;font-weight:bolder;">发货单'.$shipmentId.'对应订单已被取消！</div>');
		return false;
	}
	// 这个IF用于解决已经有面单的单子不再添加面单，减少浪费面单
	if(!empty($order['tracking_number'])){
    	return true;
	}
   
   	//85	圆通快递 ; 89	申通快递 ; 99	汇通快递 ; 100	韵达快递 ; 115	中通快递 ;12 宅急送快递; 145	速达快递 ; 146	京东COD ; 149	京东配送 ; 117 顺丰陆运 ; 44顺丰快递
	$arata_shipping_ids=array('85','89','99','100','115','12','117','44');//涉及不同仓库划分
	$arata_distributor_ids=array('146','149'); //涉及店铺不同的划分

	for($i=0;$i<3;$i++){
		$branch='';
		if(in_array($order['shipping_id'],$arata_shipping_ids)){ //四通一达  lib_express_arata.php
		  	$branch=getBranchWithFacilityId($order['facility_id']);  
		  	if($order['shipping_id'] =='85'){//圆通快递
		  		$tracking_number = get_yto_thermal_mailno($order['order_id'],$shipmentId,$branch);  
		  	}elseif($order['shipping_id'] =='100' && in_array($order['facility_id'],array('24196974','137059426'))){//贝亲青浦仓,上海精品仓 的韵达快递
		  		$tracking_number = get_yunda_thermal_mailno_by_order($order['order_id'],$shipmentId,'BQ');  
		  	}elseif($order['shipping_id'] =='100' && $order['facility_id'] !='24196974'){//其他仓的韵达快递
		  		$tracking_number = get_yunda_thermal_mailno_by_order($order['order_id'],$shipmentId,$branch);  
//		  	}elseif($order['shipping_id'] =='115'){//中通
//		  		$tracking_number = get_zto_thermal_mailno($order['order_id'],$shipmentId,$branch);
//		  	}elseif($order['shipping_id'] =='99'){//汇通
//		  		$tracking_number = get_ht_thermal_mailno($order['order_id'],$shipmentId,$branch);
		  	}elseif(
				($order['shipping_id'] == '44' || $order['shipping_id']=='117') 
				&& in_array($order['facility_id'], array(
		        '137059426','120801050','137059424','176053000',// in SHanghai
				'19568549','194788297', //jiashan
		        '3580047',// LQ DG C
		        '185963138',//SZSGZC
            '79256821',//DSFWBJC
            '185963128', // 水果北京仓
			'253372943', //水果深圳仓
			'185963134',  //水果上海仓
		      ))
	    	){//顺丰快递
	    	 if(in_array($order['facility_id'], array('19568549','194788297'))){$branch='JS';}
				$tracking_number = get_sf_thermal_mailno_for_shipment($shipmentId,$branch);  
			}else{//申通 
		  		$tracking_number = get_thermal_mailno($order['shipping_id'],$branch); 
		  	}
		}elseif(in_array($order['shipping_id'],$arata_distributor_ids)){//京东COD+京东配送   //后期再着手修改京东运单存储打印方式
			//使用distributor_id作为区别符
			$tracking_number = get_jd_bill_code($order['distributor_id'],$order['order_id']);
		}elseif($order['shipping_id'] == '145'){//速达快递
			//速达快递面单为SD+发货单号生成
			$tracking_number = 'SD'.$shipmentId;
		}
		if($tracking_number!=-1){
		  	break;
		}else{
			Qlog::log('SINRI_WARNING add_thermal_tracking_number for '.$shipmentId.' LOCKED by others, try again?');
			usleep(200000);//0.2 second waiting
		}
  	}
  	if($tracking_number=='0'){
    	echo('<div style="color:red;font-weight:bolder;">热敏面单不足！请联系快递网点！</div>');
    	Qlog::log('SINRI_WARNING 发货单 '.$shipmentId.' 尝试竞拍热敏面单号时发现热敏面单库空了！');
    	return false;
  	}else if($tracking_number==-1){
    	echo "<div style='color:red;font-weight:bolder;'>发货单{$shipmentId} 尝试竞拍热敏面单号3次均告失败！请联系ERP！</div>";
    	Qlog::log('SINRI_WARNING 发货单 '.$shipmentId.' 尝试竞拍热敏面单号3次均告失败！');
    	return false;
  	}elseif($tracking_number==2){
  		echo "<div style='color:red;font-weight:bolder;'>发货单{$shipmentId}没有找到京东店铺信息</div>";
    	Qlog::log('SINRI_WARNING 发货单 '.$shipmentId.' 没有找到京东店铺信息');
    	return false;
  	}elseif($tracking_number==3){
  		echo "<div style='color:red;font-weight:bolder;'>发货单{$shipmentId}申请调用京东“是否京配”接口不能连接，稍后再试！</div>";
    	Qlog::log('SINRI_WARNING 发货单 '.$shipmentId.' 申请调用京东“是否京配”接口不能连接，稍后再试！');
    	return false;
  	}elseif($tracking_number==5){
  		echo "<div style='color:red;font-weight:bolder;'>发货单{$shipmentId}申请调用京东“是否京配”返回信息：人工预分拣中，请15分钟后再尝试！</div>";
    	Qlog::log('SINRI_WARNING 发货单 '.$shipmentId.' 申请调用京东“是否京配”返回信息：人工预分拣中，请15分钟后再尝试！');
    	return false;
  	}elseif($tracking_number==6){
  		$sql = "select attr_value from ecshop.order_attribute where order_id = {$order['order_id']} and attr_name='JDsendMsg' order by attribute_id desc ";
		$JDsendMsg = $db->getOne($sql);
  		echo "<div style='color:red;font-weight:bolder;'>发货单{$shipmentId}申请调用京东“是否京配”返回错误：{$JDsendMsg}</div>";
    	Qlog::log('SINRI_WARNING 发货单 '.$shipmentId.' 申请调用京东“是否京配”返回错误：'.$JDsendMsg);
    	return false;
  	}
 	$sql = "update romeo.shipment set tracking_number = '{$tracking_number}',last_modified_by_user_login='{$_SESSION['admin_name']}' where shipment_id = '{$shipmentId}'  ";
  	if($tracking_number!=4){
  		$db->query($sql);
  	}
  	if(in_array($order['shipping_id'],array('89','115','12'))){ // R->Y
  		bind_arata_shipment_mailno($order['shipping_id'],$tracking_number); 
  	}elseif(in_array($order['shipping_id'],array('100','85','99','117','44'))){// R->F
  		finish_arata_shipment_mailno($order['shipping_id'],$tracking_number); 
 	}elseif(in_array($order['shipping_id'],array('146','149')) && $tracking_number!='4'){ // R->Y
  		bind_jd_bill_code($order['distributor_id'],$tracking_number);
  	}
  	$note = "批拣扫描！热敏！快递面单, 面单号为：{$tracking_number}";
  	$sql = " INSERT INTO ecshop.ecs_order_action 
        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
        ('{$order['order_id']}', '{$order['order_status']}', '{$order['shipping_status']}', '{$order['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
  	";
  	if($tracking_number!=4) {
  		$db->query($sql);
  	}
  	return true;
}

/**
 找到要拣货的位置。调用get_dynamic_batch_pick_path实现
 return :	false - 无下一个目标
 			true - 有下一目标
 cywang
 CHECKED ON 20131015
 USED IN
 batch_pick_over_action.php
 **/
function get_dynamic_batch_pick_path_cywang($batch_pick_sn, &$next_lb, $pre_lb=''){
	$next_lb = get_dynamic_batch_pick_path($batch_pick_sn, $pre_lb);
	return null != $next_lb;
}

/**
得到批捡的路线
ljni@i9i8.com 20130821 -- 这一个版本被聪颖幕府拖出去砍死了
于是新版本是20130824 cywang大神搞的。。于是前端全线崩溃，默默重写
All Hail Congying Wang!
**///refraction by cywang

class RecordInfoOnBP
{
  var $location_barcode;
  var $goods_barcode;
  var $goods_name;
  var $details;
  var $product_id;
  var $total_number;
}
class RecordInfoOnBPDetail{
  var $shipment_id;
  var $grid_id;
  var $number;
}
/**
 （2）获取location上待拣列表
 qdi
CHECKED ON 20130911
USED BY 
IN
batch_pick_rf_scan_n1.php
batch_pick_rf_scan_vainity.php
**/
function get_location_duty_list($batch_pick_sn) {
  if(isset($handle))unset($handle);
  $result = array();
  try{
  	  QLog::log("get_location_pick_list($batch_pick_sn,$location_barcode) called");
      $handle=soap_get_client('InventoryService');
  	  $response=$handle->getPickListByLocationBarcode(array(
  	  'batchPickSn' => $batch_pick_sn,
  	  'locationBarcode' => '123')); 
      $result['success'] = true;
   	  $result['duties'] = array();
	  if(isset($response->return->entry)){
	  	$result['duties'] = extract_duties_from_json($response->return->entry);
	  }
  	  return $result;
  }catch (Exception $e) {
      $result['success'] = false;
      $result['error'] = "shipmentsInventoryEnough soap call exception:".$e->getMessage();
      return $result;
  }
}
function extract_duties_from_json($entries){     
  foreach ($entries as $no => $entry) {
    if ($entry->key=="itemList"){
      $json=$entry->value->stringValue;
      break;
    }
  }
  if(!isset($json)) return false;
  QLog::log("get_location_pick_list($batch_pick_sn,$location_barcode) START JSON ANALYZE [$json]");
  $jsoned=json_decode($json,true);
  $res=array();
  $record_count=0;
  $BP_records = array();
  foreach ($jsoned as $lb => $lb_box) {
    $location_barcode = $lb_box['locationBarcode'];
    $goods_info_list = $lb_box['goodsInfo'];
    //遍历goods_info_list
    foreach ($goods_info_list as $goods_info) {
      // for single goods
      //1. basic info
        $goods_sn = $goods_info['barcode'];
        $goods_name = $goods_info['goodsName'];
        $goods_product_id=$goods_info['productId'];
        $BP_record = array();
        $BP_record['location_barcode'] = $location_barcode;
        $BP_record['goods_barcode'] = $goods_sn;
        $BP_record['goods_name'] = $goods_name;
        $BP_record['product_id'] = $goods_product_id;
        $BP_record['total_number'] = 0;
        $BP_record['details'] = array();
      //2. shipment
      foreach ($goods_info['shipmentNum'] as $shipment) {
        $BP_record_detail = array();
        $BP_record_detail['shipment_id'] = $shipment['shipmentId'];
        $BP_record_detail['grid_id'] = $shipment['gridId'];
        $BP_record_detail['number'] = $shipment['gridNum'];
        $BP_record['total_number'] += $shipment['gridNum'];
        $BP_record['details'][] = $BP_record_detail;
      }
      $BP_records[] = $BP_record;
    }
  }
  return $BP_records;
}
/**
专用于打印的批拣路径生成获取
CHECKED ON 20130911
USED IN
ajax.php UNKNOWN REASON!!
lib_sinri_DealPrint.php
print_batch_pick.php
**/
function get_batch_pick_path($batch_pick_sn) {
  if(isset($handle))unset($handle);
  $handle=soap_get_client('InventoryService');
  $result=$handle->getDynamicBatchPickPathAndReserve(array('batchPickSn' => $batch_pick_sn));
  QLog::log("get_batch_pick_path($batch_pick_sn) called");

  //get data
  if(isset($result->return->entry)){
      $entries=$result->return->entry;
      foreach ($entries as $no => $entry) {
        if ($entry->key=="itemList"){
          $json=$entry->value->stringValue;
          break;
        }
      }
    }

  if(!isset($json)) return false;
  QLog::log("get_batch_pick_path($batch_pick_sn) START JSON ANALYZE [$json]");

  $jsoned=json_decode($json,true);
  # print_r($jsoned);
  $res=array();
  $record_count=0;
  $BP_records = array();
  foreach ($jsoned as $lb => $lb_box) {
    $location_barcode = $lb_box['locationBarcode'];
    $goods_info_list = $lb_box['goodsInfo'];
    //遍历goods_info_list
    foreach ($goods_info_list as $goods_info) {
      // for single goods
      //1. basic info
        $goods_sn = $goods_info['barcode'];
        $goods_name = $goods_info['goodsName'];
        $goods_product_id=$goods_info['productId'];
      //2. shipment
      foreach ($goods_info['shipmentNum'] as $shipment) {
        # code...
        $BP_record = new RecordInfoOnBP;
        $BP_record->location_barcode_ = $location_barcode;
        $BP_record->goods_sn_ = $goods_sn;
        $BP_record->goods_name_ = $goods_name;
        $BP_record->shipment_id_ = $shipment['shipmentId'];
        $BP_record->grid_id_ = $shipment['gridId'];
        $BP_record->number_ = $shipment['gridNum'];
        $BP_record->product_id_ = $goods_product_id;
        $BP_records[] = $BP_record;
      }
    }
  }

  return $BP_records;
}

/**
专用于打印的批拣路径生成获取,按商品汇总
CHECKED ON 20131026
USED IN
print_batch_pick.php
**/
function get_batch_pick_path_merged($batch_pick_sn) {
  if(isset($handle))unset($handle);
  $handle=soap_get_client('InventoryService');
  $result=$handle->getDynamicBatchPickPathAndReserve(array('batchPickSn' => $batch_pick_sn));
  QLog::log("get_batch_pick_path_merged($batch_pick_sn) called");

  //get data
  if(isset($result->return->entry)){
      $entries=$result->return->entry;
      foreach ($entries as $no => $entry) {
        if ($entry->key=="itemList"){
          $json=$entry->value->stringValue;
          break;
        }
      }
    }

  if(!isset($json)) return false;
  QLog::log("get_batch_pick_path($batch_pick_sn) START JSON ANALYZE [$json]");

  $jsoned=json_decode($json,true);
  
  $res=array();
  $record_count=0;
  $BP_records_new = array();
  foreach ($jsoned as $lb => $lb_box) {
  	$location_barcode = $lb_box['locationBarcode'];
  	if(!array_key_exists($location_barcode,$BP_records_new))
  	{  		
  		$BP_records_new[$location_barcode]['location_barcode'] = $location_barcode;
	    //$BP_records_new[$location_barcode]['row_span'] = 0;
  		$BP_records_new[$location_barcode]['goods_list'] = array();
  	}
  	
  	$goods_info_list = $lb_box['goodsInfo'];
    //遍历goods_info_list
    foreach ($goods_info_list as $goods_info) {
    	$product_id = $goods_info['productId'];
    	if(!array_key_exists($product_id,$BP_records_new[$location_barcode]['goods_list']))
    	{
	      //1. basic info
	        //$BP_records_new[$location_barcode]['goods_list'][$product_id]['row_span'] = 0;
	        $BP_records_new[$location_barcode]['goods_list'][$product_id]['productId'] = $product_id;
	        $BP_records_new[$location_barcode]['goods_list'][$product_id]['barcode'] = $goods_info['barcode'];
    	    $BP_records_new[$location_barcode]['goods_list'][$product_id]['goodsName'] = $goods_info['goodsName'];
    	    $BP_records_new[$location_barcode]['goods_list'][$product_id]['goodsNumber'] = 0;
    		$BP_records_new[$location_barcode]['goods_list'][$product_id]['grids'] = array();
    		$BP_records_new[$location_barcode]['goods_list'][$product_id]['validity_batch_sn'] = array();    		
    	}
	  		
	  	//2. shipment
      	foreach ($goods_info['shipmentNum'] as $shipment) {
        	# code...
        	$BP_records_new[$location_barcode]['goods_list'][$product_id]['grids'][$shipment['gridId']]
        		= array('shipment_id' => $shipment['shipmentId'], 
        		'number' => $shipment['gridNum']);
        		
        	$BP_records_new[$location_barcode]['goods_list'][$product_id]['validity_batch_sn'][$shipment['gridId']]
        	    = get_shipment_validity_batch_sn($product_id,$shipment['shipmentId']);
       		$BP_records_new[$location_barcode]['goods_list'][$product_id]['goodsNumber'] += $shipment['gridNum'];
       		//$BP_records_new[$location_barcode]['row_span']++;
       		//$BP_records_new[$location_barcode]['goods_list'][$product_id]['row_span']++;
       		$record_count++;
      	}
      	
      	ksort($BP_records_new[$location_barcode]['goods_list'][$product_id]['grids']);
      	ksort($BP_records_new[$location_barcode]['goods_list'][$product_id]['validity_batch_sn']);
      	
    }
  }
  return $BP_records_new;
}

// 取得生产日期/批次号
function get_shipment_validity_batch_sn($product_id,$shipmentId) {
	global $db;
	$sql = "select oid.product_id,
		        ifnull(left(ii.validity,10),'1970-01-01') as validity,
		        ifnull(ii.batch_sn,'') as batch_sn,
		        ifnull(sum(im.quantity),sum(oid.GOODS_NUMBER)) as quantity,os.shipment_id
				from 
				romeo.order_shipment os 
                inner join romeo.order_inv_reserved_detail oid ON os.order_id = oid.order_id
                left join romeo.order_inv_reserved_inventory_mapping im ON oid.order_inv_reserved_detail_id = im.order_inv_reserved_detail_id
                left join romeo.inventory_item ii ON im.inventory_item_id = ii.inventory_item_id
                where os.shipment_id = '{$shipmentId}' and oid.product_id = '{$product_id}'
                group by ii.validity,ii.batch_sn
	";
//	var_dump('get_shipment_validity_batch_sn sql;');var_dump($sql);
	$validity_quantitys = $db->getAll($sql);
//	var_dump('$validity_quantitys ;');var_dump($validity_quantitys);

	return $validity_quantitys;
}


/**
解析本文件中定义的class RecordInfoOnBP 为array
CHECKED ON 20130911
NEVER BE CALLED
**/
function decodeRecordInfoOnBP($value){
  $list=array(
    'location_barcode'=>$value->location_barcode_,
    'goods_barcode'=>$value->goods_sn_,
    'goods_name'=>$value->goods_name_,
    'shipment_id'=>$value->shipment_id_,
    'grid_id'=>$value->grid_id_,
    'grid_number'=>$value->number_,
    'product_id'=>$value->product_id_
  );
  QLog::log("decodeRecordInfoOnBP($value) 这个理论上应该没有地方用到的");
  return $list;
}
/**
哪一个BPSN导出扫荡向批拣数组
ljni@i9i8.com
20130821
CHECKED ON 20130911
USED IN
batch_pick_rf_scan.php
batch_pick_rf_scan_n1.php
batch_pick_rf_scan_vainity.php
**/
function sinri_get_picking_mapping_list($BPSN){
  $ori=get_batch_pick_path($BPSN);
  QLog::log("sinri_get_picking_mapping_list($BPSN) called");
  return sinri_test_get_picking_mapping_list($ori);
}

/**
检查发货单中某一商品条码是否和某串号对应
依赖于function.php
ljni@i9i8.com
20130822
CHECKED ON 20130911
USED IN
batch_pick_rf_scan.php
batch_pick_rf_scan_n1.php
batch_pick_rf_scan_vainity.php
**/
function sinri_check_goods_barcode_SN($shipment_id,$goods_barcode,$SN){
  global $db;
  $sql="SELECT
          count(*)
        FROM
          romeo.location_barcode_serial_mapping
        WHERE
          romeo.location_barcode_serial_mapping.goods_barcode = '$goods_barcode'
        AND romeo.location_barcode_serial_mapping.serial_number = '$SN'
        AND romeo.location_barcode_serial_mapping.goods_number > 0;";
  $count=$db->getOne($sql);
  Qlog::log("sinri_check_goods_barcode_SN($shipment_id,$goods_barcode,$SN) count=$count SQL=".$sql);
  return $count;
}
/**
用来执行拣货最终检查的
其实还没有定 T_T
聪颖幕府不在的星期四……
CHECKED ON 20130911
USED IN
batch_pick_rf_scan.php
batch_pick_rf_scan_n1.php
batch_pick_rf_scan_vainity.php
**/
function sinri_fianl_picking_check($bpsn){
  $result=terminal_batch_pick($bpsn);
  //pp($result);
  Qlog::log("sinri_fianl_picking_check($bpsn)");
  if($result['success']){
    Qlog::log("sinri_fianl_picking_check($bpsn) success");
    return array();
  } else {
    Qlog::log("sinri_fianl_picking_check($bpsn) fail ". $result['shipmentIds']);
    return $result['shipmentIds'];
  }
}
/**
扫货中串号类型检查
蔵のデータベースに関するのみ
ljni@i9i8.com
20130826
CHECKED ON 20130911
USED IN
batch_pick_rf_scan.php
batch_pick_rf_scan_n1.php
batch_pick_rf_scan_vainity.php
**/
function check_goods_is_serial_by_sinri($location_barcode,$party_id,$goods_barcode,$product_id){
    global $db;
    $sql="SELECT is_serial FROM romeo.inventory_location
        where
        location_barcode='$location_barcode' and 
        product_id='$product_id'
        LIMIT 1
        ;";
    $res=$db->getOne($sql);
    Qlog::log("check_goods_is_serial_by_sinri LB=".$location_barcode.", party_id=".$party_id.", GB=".$goods_barcode.", product_id=".$product_id.", GET $res SQL=".$sql);
    
    if($res==null || $res==''){
      die("数据库中找不到货物的记录。请检查组织、位置、产品等信息。以下信息为向ERP投诉之用：check_goods_is_serial_by_sinri LB=".$location_barcode.", party_id=".$party_id.", GB=".$goods_barcode.", product_id=".$product_id.", SQL=".$sql);
    }
    //print_r($res);
    if($res=='0' || $res=='N' || $res=='n' || $res=='f' || $res=='F'){
        return false;
    } else {
        return true;
    }
}
/**
蔵に予定された品物はまことに棚に用意されていてそして十分足りるかと判断すること。
見よ、この世の罪びとを一掃し裁きを下さるものは、その方の足音はもう聞こえる。
ljni@i9i8.com
20130826
CHECKED ON 20130911
USED IN
shipment_listV5.php
------
ljni@leqee.com
20150924
UPDATED CODE COMPLEX
**/
function check_shipments_tariruka($shipment_ids,$PARTY_ID,$facility_ids){
  $result=get_shipments_takiruka_info_single($shipment_ids,$PARTY_ID,$facility_ids);
  // result looks like 
  // [
  //   {
  //     "barcode":"300550300309",
  //     "goods_name":"\u8d1d\u4eb24\u53f7\u7eb8\u7bb1300550300309",
  //     "ordergoodsnumber":"1",
  //     "locationgoodsnumber":"0",
  //     "SIDS":"144190655"
  //   }
  // ]
 QLog::log("check_shipments_tariruka called with shipment_ids: ".json_encode($shipment_ids)." result: ".json_encode($result));
  $err_sids=array();
  //pp($result);
  foreach ($result as $pi => $tarinu_sinamono) {
    //print_r($tarinu_hinamono);

    $sids = preg_split("/[\s,]+/", $tarinu_sinamono['SIDS']);
    foreach ($sids as $k => $sid) {
      $err_sids[]=$sid;
      // QLog::log("check_shipments_tariruka($shipment_ids) err_sids APPEND $sid");
    }
/*
    foreach ($tarinu_sinamono as $key => $value) {
      if($key=='SIDS'){
        $sids = preg_split("/[\s,]+/", $value);
        foreach ($sids as $k => $sid) {
          $err_sids[]=$sid;
//          QLog::log("check_shipments_tariruka($shipment_ids) err_sids APPEND $sid");
        }
      }
    }
*/
  }
  return $err_sids;
}

/**
 * 精确到单个shipment_id级别
品物の予定要請を出した配送リストの対応する品物が不足かどうか、ここでチェックしよう。
返すのは、品物番号に組み合わせた情報リストです。もちろん、不足に困った配送リストの番号も提供いたします。
ljni@i9i8.com
20130826
CHECKED ON 20130911 UPDATED ON 20150929
USED BY check_shipments_tariruka
IN
shipment_listV5.php
**/
function get_shipments_takiruka_info_single($shipment_ids,$PARTY_ID,$facility_ids){
  global $db;  
  $fids=array();
  foreach ($facility_ids as $key => $value) {
    $fids[]="'".$value."'";
  }
  $sql00=join(',',$fids);
  $sql0="SELECT
          -- if(gs.barcode is null or gs.barcode ='',g.barcode,gs.barcode) as barcode,
          -- g.goods_name,
          -- og.goods_name,
          sum(og.goods_number) ordergoodsnumber,
          ifnull(
            (
              SELECT
                sum(available_to_reserved)
              FROM
                romeo.inventory_location il
              LEFT JOIN romeo.location AS loc ON loc.location_barcode = il.location_barcode
              WHERE
                il.status_id = 'INV_STTS_AVAILABLE'
              AND loc.location_type = 'IL_LOCATION'
              AND il.product_id = pm.product_id  
              ".
              ((isset($PARTY_ID) && $PARTY_ID!=null)?"AND il.party_id='$PARTY_ID'":"")
              ." 
              AND il.facility_id IN ($sql00)
            ),
            0
          ) locationgoodsnumber,
          group_concat(os.shipment_id) AS SIDS
        FROM
         -- romeo.shipment s LEFT JOIN 
        romeo.order_shipment os -- ON s.shipment_id = os.shipment_id
        LEFT JOIN ecshop.ecs_order_goods og ON convert(os.order_id using utf8) = og.order_id
        LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id AND og.style_id = pm.ecs_style_id
        -- LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
        -- LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and og.style_id = gs.style_id and gs.is_delete=0
        WHERE
          os.shipment_id IN (";
  $sids=array();
  foreach ($shipment_ids as $key => $value) {
    $sids[]="'".$value."'";
  }
  $sql1=join(',',$sids);
  $sql2=")
        group by pm.product_id,os.shipment_id
    having ordergoodsnumber > locationgoodsnumber;";
  $sql=$sql0.$sql1.$sql2;
// Qlog::log('get_shipments_takiruka_info_sql:'.$sql);
  $result=$db->getAll($sql);
// Qlog::log("get_shipments_takiruka_info_result:".json_encode($result));
  return $result;
}

/**
品物の予定要請を出した配送リストの対応する品物が不足かどうか、ここでチェックしよう。
返すのは、品物番号に組み合わせた情報リストです。もちろん、不足に困った配送リストの番号も提供いたします。
ljni@i9i8.com
20130826
CHECKED ON 20130911
USED BY check_shipments_tariruka
IN
shipment_listV5.php
**/
function get_shipments_takiruka_info($shipment_ids,$PARTY_ID,$facility_ids){
  global $db;  
  $fids=array();
  foreach ($facility_ids as $key => $value) {
    $fids[]="'".$value."'";
  }
  $sql00=join(',',$fids);
  $sql0="SELECT
          if(gs.barcode is null or gs.barcode ='',g.barcode,gs.barcode) as barcode,
          g.goods_name,
          sum(og.goods_number) ordergoodsnumber,
          ifnull(
            (
              SELECT
                sum(available_to_reserved)
              FROM
                romeo.inventory_location il
              LEFT JOIN romeo.location AS loc ON loc.location_barcode = il.location_barcode
              WHERE
                il.status_id = 'INV_STTS_AVAILABLE'
              AND loc.location_type = 'IL_LOCATION'
              AND il.product_id = pm.product_id  
              ".
              ((isset($PARTY_ID) && $PARTY_ID!=null)?"AND il.party_id='$PARTY_ID'":"")
              ." 
              AND il.facility_id IN ($sql00)
            ),
            0
          ) locationgoodsnumber,
          pm.product_id,
          group_concat(s.shipment_id) AS SIDS
        FROM
          romeo.shipment s
        LEFT JOIN romeo.order_shipment os ON s.shipment_id = os.shipment_id
        LEFT JOIN ecshop.ecs_order_goods og ON os.order_id = og.order_id
        LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id
        AND og.style_id = pm.ecs_style_id
        LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
        LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and og.style_id = gs.style_id and gs.is_delete=0
        WHERE
          s.shipment_id IN (";
  $sids=array();
  foreach ($shipment_ids as $key => $value) {
    $sids[]="'".$value."'";
  }
  $sql1=join(',',$sids);
  $sql2=")
        group by pm.product_id
    having ordergoodsnumber > locationgoodsnumber;";
  $sql=$sql0.$sql1.$sql2;
  Qlog::log('get_shipments_takiruka_info:'.$sql);
  $result=$db->getAll($sql);
  return $result;
}

/**
上の関数`check_shipments_tariruka`を代わって効率改善のためのお試しの一つである。
原理は、一つの大きなSQLを二つに分けて、また、合計機能を一時的に削除する。
20150930 Sinri Edogawa  
*/
function checkShipmentsProductsOnLocation($shipment_ids=array('0'),$party_id=0,$facility_ids=array(0)){
  global $db;
  $sids="'".implode("','", $shipment_ids)."'";
  $sql="SELECT 
          os.shipment_id,
          pm.product_id
        FROM
          romeo.order_shipment os 
        LEFT JOIN ecshop.ecs_order_goods og on cast(os.order_id as UNSIGNED) = og.order_id
        LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id
          AND og.style_id = pm.ecs_style_id
        WHERE os.shipment_id in ({$sids})
  ";
  Qlog::log("checkShipmentsProductsOnLocation[SP SQL] ".$sql);
  $sp_mapping_r=$db->getAll($sql);
  if(!empty($sp_mapping_r)){
    foreach ($sp_mapping_r as $item) {
      if(!isset($sp_mapping[$item['product_id']])){
        $sp_mapping[$item['product_id']]=array($item['shipment_id']=>$item['shipment_id']);
      }else{
        $sp_mapping[$item['product_id']][$item['shipment_id']]=$item['shipment_id'];
      }
    }
  }
  $pids="'".implode("','", array_keys($sp_mapping))."'";
  $facility_ids="'".implode("','", ($facility_ids))."'";
  $sql="SELECT
          il.product_id,sum(available_to_reserved) atr
        FROM
          romeo.inventory_location il
        LEFT JOIN romeo.location AS loc ON loc.location_barcode = il.location_barcode
        WHERE
          il.status_id = 'INV_STTS_AVAILABLE'
        AND loc.location_type = 'IL_LOCATION'
        AND il.product_id in ({$pids}) 
        AND il.party_id='{$party_id}'
        AND il.facility_id IN ({$facility_ids})
        group by il.product_id
  ";
  Qlog::log("checkShipmentsProductsOnLocation[PS SQL] ".$sql);
  $p_aru_mapping=$db->getAll($sql);
  if(!empty($p_aru_mapping)){
    foreach ($p_aru_mapping as $item) {
      if($item['atr']>0){
        unset($sp_mapping[$item['product_id']]);
      }
    }
  }
  // print_r($sp_mapping);
  // echo PHP_EOL."<hr>".PHP_EOL;
  // print_r($p_aru_mapping);
  // echo PHP_EOL."<hr>".PHP_EOL;
  // print_r($sp_mapping);
  $shipments=array();
  if(!empty($sp_mapping)){
    foreach ($sp_mapping as $pid => $sids) {
      foreach ($sids as $sid) {
        $shipments[$sid]=$sid;
      }
    }
  }
  return $shipments;
}

/**
收货入库中获取对应物品信息
CHECKED ON 20130911
USED IN
MonoHaire.php
**/
function MonoHaire_getGoodsInfo($batch_order_sn,$goods_barcode){
  $res = get_receive_goods_info($batch_order_sn,$goods_barcode);
  $result['goods_name'] = $res['goods_name'];
  $result['goods_id'] = $res['goods_id'];
  if($res['success']){
    $result['success'] = true;    
    require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
    $goods_item_type = getInventoryItemType ($res['goods_id']);
    //是否非串号控制
    if($goods_item_type == 'SERIALIZED'){
      $result['is_serial'] = true; 
    } else {
      $result['is_serial'] = false; 
    }

  }else{
    $result['success'] = false;
    $result['error'] = $res['error'];
  }
  return $result;
}
/**
收货入库查询是否是保质期维护商品
CHECKED ON 20130911
USED IN
MonoHaire.php
**/
function MonoHaire_isNeedWarranty($MHSN,$barcode){
  $party=get_party_by_batch_order_sn($MHSN);
  if($party['success']){
    $party_id=$party['res'];
    return check_maintain_warranty($barcode,$party_id);
  }
  return false;
}
/**
收货入库中真正的收货功能封装
CHECKED ON 20130911
USED BY
MonoHaire_Hairu_SN_MW
MonoHaire_Hairu_NSN_MW
MonoHaire_Hairu_SN
MonoHaire_Hairu_NSN
IN
MonoHaire.php
**/
function MonoHaire_hairu($batch_order_sn,
    $location_barcode,
    $goods_barcode,
    $serial_number,
    $input_number,
    $validity, 
    $validity_type='start_validity')
{
  $res=purchase_accept_and_location_transaction(
    $batch_order_sn,
    $location_barcode,
    $goods_barcode,
    $serial_number,
    $input_number,
    $validity, 
    $validity_type
  );
  /*
  if($res['success']){
    return true;
  }
  return false;
  */
  return $res;
}
/**
收货入库 有串号 有保质期
CHECKED ON 20130911
USED IN
MonoHaire.php
**/
function MonoHaire_Hairu_SN_MW($MHSN,$location_barcode,$goods_barcode,$serial_number,$validity,$validity_type='start_validity'){
  return MonoHaire_hairu($MHSN,
    $location_barcode,
    $goods_barcode,
    $serial_number,
    1,
    $validity, 
    $validity_type='start_validity');
}
/**
收货入库 有串号 没有保质期
CHECKED ON 20130911
USED IN
MonoHaire.php
**/
function MonoHaire_Hairu_SN($MHSN,$location_barcode,$goods_barcode,$serial_number){
  return MonoHaire_hairu($MHSN,
    $location_barcode,
    $goods_barcode,
    $serial_number,
    1,
    '1970-01-01 00:00:00', 
    'start_validity');
}
/**
收货入库 没有串号 有保质期
CHECKED ON 20130911
USED IN
MonoHaire.php
**/
function MonoHaire_Hairu_NSN_MW($MHSN,$location_barcode,$goods_barcode,$goods_number,$validity,$validity_type='start_validity'){
  return MonoHaire_hairu($MHSN,
    $location_barcode,
    $goods_barcode,
    'NOSN',
    $goods_number,
    $validity, 
    $validity_type='start_validity');
}
/**
收货入库 没有串号 没有保质期
CHECKED ON 20130911
USED IN
MonoHaire.php
**/
function MonoHaire_Hairu_NSN($MHSN,$location_barcode,$goods_barcode,$goods_number){
  return MonoHaire_hairu($MHSN,
    $location_barcode,
    $goods_barcode,
    'NOSN',
    $goods_number,
    '1970-01-01 00:00:00', 
    'start_validity');
}
/**
批拣出库时写下此库位到此一游
CHECKED ON 20130911
USED IN
batch_pick_rf_scan.php
batch_pick_rf_scan_n1.php
batch_pick_rf_scan_vainity.php
**/
function PickerHadComeHere($BPSN,$location_barcode){
  global $db;
  $sql_list_ILR_ID="SELECT ilr.inventory_location_reserve_id
        -- ,ilr.status_id
        FROM romeo.inventory_location_reserve as ilr
        left join romeo.inventory_location as il         
        on ilr.inventory_location_id=il.inventory_location_id 
        where
        ilr.batch_pick_sn='$BPSN'
        AND
        il.location_barcode='$location_barcode'
        and 
        ilr.status_id='N'
        ;";
  QLog::log("PickerHadComeHere:BPSN=".$BPSN." and $location_barcode=".$location_barcode." SQL=$sql_list_ILR_ID");
  $ids_array=$db->getAll($sql_list_ILR_ID);
  //print_r($ids_array);
  $ids=array();
  foreach ($ids_array as $k1 => $v1) {
    $ids[]=$v1['inventory_location_reserve_id'];
  }
  //print_r($ids);
  foreach ($ids as $key => $id) {
    $sql_update="UPDATE romeo.inventory_location_reserve
                SET romeo.inventory_location_reserve.status_id='S'
                WHERE
                romeo.inventory_location_reserve.inventory_location_reserve_id = $id;";
    $db->query($sql_update);
    QLog::log("PickerHadComeHere UPDATE AND SET:".$sql_update);
  }  
}
/**
测试用擦掉此库位到此一游，非常危险，非紧急情况请勿使用
CHECKED ON 20130911
NEVER BE USED
CAUSION DANGEROUS!
**/
function RevertPickerHadComeHere($BPSN,$location_barcode){
  QLog::log("RevertPickerHadComeHere($BPSN,$location_barcode) 同志们看到这条要不就是在debug，要不就等着debug吧，哇哈哈哈");
  global $db;
  $sql_list_ILR_ID="SELECT ilr.inventory_location_reserve_id
        -- ,ilr.status_id
        FROM romeo.inventory_location_reserve as ilr
        left join romeo.inventory_location as il         
        on ilr.inventory_location_id=il.inventory_location_id 
        where
        ilr.batch_pick_sn='$BPSN'
        AND
        il.location_barcode='$location_barcode'
        and 
        ilr.status_id='S'
        ;";
  $ids_array=$db->getAll($sql_list_ILR_ID);
  QLog::log("RevertPickerHadComeHere($BPSN,$location_barcode) SQL=$sql_list_ILR_ID");
  $ids=array();
  foreach ($ids_array as $k1 => $v1) {
    $ids[]=$v1['inventory_location_reserve_id'];
  }
  //print_r($ids);
  foreach ($ids as $key => $id) {
    $sql_update="UPDATE romeo.inventory_location_reserve
                SET romeo.inventory_location_reserve.status_id='N'
                WHERE
                romeo.inventory_location_reserve.inventory_location_reserve_id = $id;";
    $db->query($sql_update);
  }
  QLog::log("PickerHadComeHere:BPSN=".$BPSN." and $location_barcode=".$location_barcode);
}
/**
病单号查询
CHECKED ON 20130911
USED IN
query_sick_shipment.php
**/
function getSickSIDs($facility_ids,$party_id){
  global $db;
  $sql="SELECT
          DISTINCT sick_shipment.shipment_id as sid
        FROM
          romeo.sick_shipment
        WHERE
          romeo.sick_shipment.lack_number > 0";
  if(sizeof($facility_ids)>0){
    $sql.=" and sick_shipment.facility_id in (".join(',',$facility_ids).")";
  }
  if($party_id!=null){
    $sql.=" and sick_shipment.party_id=".$party_id;
  }
  $sql.=" GROUP BY
          sick_shipment.shipment_id
        ORDER BY
          sick_shipment.created_stamp;";
  QLog::log("getSickSIDs($facility_ids,$party_id) SQL=$sql");
  $res=$db->getAll($sql);
  $result=array();
  foreach ($res as $key => $value) {
    $result[]=$value['sid'];
    QLog::log("getSickSIDs($facility_ids,$party_id) SICK APPEND ".$value['sid']);
  }
  //print_r($result);
  return $result;
}
/**
单个病单内容查询
CHECKED ON 20130911
USED IN
RF_sick_shipment.php
**/
function getSicknessBySIDinALL($shipment_id){
  global $db;
  $sql="SELECT
        sick_shipment.sick_shipment_id,
        sick_shipment.shipment_id,
        sick_shipment.batch_pick_sn,
        sick_shipment.is_serial,
        sick_shipment.goods_barcode,
        sick_shipment.product_id,
        sick_shipment.lack_number,
        sick_shipment.party_id,
        sick_shipment.facility_id,
        sick_shipment.action_user,
        sick_shipment.created_stamp,
        sick_shipment.last_updated_stamp,
        sick_shipment.status_id
        FROM
        romeo.sick_shipment
        where 
        romeo.sick_shipment.shipment_id='$shipment_id'
        and
        romeo.sick_shipment.lack_number>0;";
  $result=$db->getAll($sql);
  QLog::log("getSicknessBySIDinALL($shipment_id) SQL=$sql");
  return $result;
}
/**
CHECKED ON 20130911
USED IN
print_sick_shipments.php
query_sick_shipment.php
RF_sick_shipment.php
**/
function Sinri_GetInfoForSickness($shipment_id){
  QLog::log("Sinri_GetInfoForSickness($shipment_id) called");
  $res1=getSicknessBySIDinALL($shipment_id);
  $res=array();
  foreach ($res1 as $ssno => $ss_line) {
    $res_line=array();
    foreach ($ss_line as $key => $value) {
      $res_line[$key]=$value;
    }
    $res2=Sinri_GetInfoByProductID($ss_line['product_id']);
    
    $res_line['goods_id']=$res2[0]['goods_id'];
    $res_line['style_id']=$res2[0]['style_id'];
    $res_line['cat_id']=$res2[0]['cat_id'];
    $res_line['goods_sn']=$res2[0]['goods_sn'];
    $res_line['goods_name']=$res2[0]['goods_name'];
    $res_line['is_maintain_warranty']=$res2[0]['is_maintain_warranty'];
    //pp($res_line);
    $res[]=$res_line;
  }
  return $res;
}

/**
CHECKED ON 20130911
USED IN
print_sick_shipments.php
query_sick_shipment.php
**/
function Sinri_GetFacilityName($facility_id){
  global $db;
  $sql="SELECT  
        if(count(romeo.facility.FACILITY_NAME)=0, '查无此库',romeo.facility.FACILITY_NAME) as F_NAME
        FROM romeo.facility
        where romeo.facility.FACILITY_ID='$facility_id'
        limit 1;";
  $result=$db->getOne($sql);
  return $result;
}
/**
CHECKED ON 20130911
USED IN
print_sick_shipments.php
query_sick_shipment.php
**/
function Sinri_GetPartyName($party_id){
  global $db;
  $sql="SELECT if(count(romeo.party.NAME)=0,'非法组织',romeo.party.NAME) as P_NAME
        FROM romeo.party
        WHERE romeo.party.PARTY_ID='$party_id'
        LIMIT 1
        ;";
  $result=$db->getOne($sql);
  //print_r($result);
  return $result;
}
/**
CHECKED ON 20130911
USED BY Sinri_GetInfoForSickness
IN
print_sick_shipments.php
query_sick_shipment.php
RF_sick_shipment.php
**/
function Sinri_GetInfoByProductID($product_id){
  global $db;
  $sql="SELECT
          t2.goods_id,
          t2.style_id,
          t2.cat_id,
          t2.goods_sn,
          t2.goods_name,

        IF (
          ecshop.ecs_goods_style.barcode IS NULL
          OR ecshop.ecs_goods_style.barcode = '',
          t2.barcode,
          ecshop.ecs_goods_style.barcode
        ) AS barcode,
         t2.is_maintain_warranty
        FROM
          (
            SELECT
              t1.goods_id,
              style_id,
              cat_id,
              goods_sn,
              goods_name,
              barcode,
              is_maintain_warranty
            FROM
              (
                SELECT
                  romeo.product_mapping.ECS_GOODS_ID AS goods_id,
                  romeo.product_mapping.ECS_STYLE_ID AS style_id
                FROM
                  romeo.product_mapping
                WHERE
                  romeo.product_mapping.PRODUCT_ID = '$product_id'
                LIMIT 1
              ) AS t1
            LEFT JOIN ecshop.ecs_goods ON ecshop.ecs_goods.goods_id = t1.goods_id
          ) AS t2
        LEFT JOIN ecshop.ecs_goods_style ON ecshop.ecs_goods_style.style_id = t2.style_id and ecshop.ecs_goods_style.is_delete=0
        LIMIT 1;";
  $result=$db->getAll($sql);
  QLog::log("Sinri_GetInfoByProductID($product_id)");
  return $result;
}
/**
CHECKED ON 20130911
USED IN
query_sick_shipment.php
**/
function Sinri_GetUserFacilityInfo(){
  $facility_list = get_user_facility();
  $result=array();
  foreach ($facility_list as $key => $value) {
    global $db;
    $sql="SELECT
          IF (
            facility_id IS NULL
            OR FACILITY_ID = '',
            0,
            FACILITY_ID
          ) AS fid
          FROM
            romeo.facility
          WHERE
            romeo.facility.FACILITY_NAME = '$value'
          LIMIT 1;";
    $res=$db->getOne($sql);
    $result[]=array(
      'facility_id'=>$res,
      'facility_name'=>$value
    );
    QLog::log("Sinri_GetUserFacilityInfo() APPEND $res/$value");
  }
  return $result;
}

/**
姑且为病单列出某物可能出现的位置。排除了被预定的量。
CHECKED ON 20130911
USED IN
print_sick_shipments.php
**/
function Sinri_GetSicknessMedicine($facility_id,$party_id,$product_id){
  global $db;
  $sql="SELECT il.location_barcode 
        FROM 
        romeo.inventory_location as il
        where
        il.facility_id='$facility_id'
        AND
        il.party_id='$party_id'
        AND
        il.product_id='$product_id'
        AND
        il.available_to_reserved>0;";
  $res=$db->getAll($sql);
  QLog::log("Sinri_GetSicknessMedicine($facility_id,$party_id,$product_id) SQL=$sql");
  $result=array();
  foreach ($res as $key => $value) {
    $result[]=$value['location_barcode'];
    QLog::log("Sinri_GetSicknessMedicine($facility_id,$party_id,$product_id) APPEND ".$value['location_barcode']);
  }
  return $result;
}

/**
检查一个位置是否是合法的位置，也就是登记有效的位置
CHECKED ON 20130911
USED IN
RF_sick_shipment.php
**/
function Sinri_CheckLocationForSickness($location_barcode){
  global $db;
  $sql="SELECT
          count(*)
        FROM
          romeo.location
        WHERE
          romeo.location.location_barcode = '$location_barcode' -- '1Z-Z-99-99' -- '1B-B-04-55'
        AND romeo.location.is_delete = 0
        AND romeo.location.location_type IN (
          'IL_GROUDING',
          'IL_LOCATION'
        );";
  $count=$db->getOne($sql);
  QLog::log("Sinri_CheckLocationForSickness($location_barcode) GET $count SQL=$sql");
  if($count==1){
    return true;
  } else {
    return false;
  }
}


/**
检查一个货物ID是否在某病单的缺货表中
CHECKED ON 20130911
USED IN
RF_sick_shipment.php
**/
function Sinri_CheckSickGoods($SSID,$goods_id){
  //return true;
  global $db;
  $sql="SELECT
          romeo.product_mapping.ECS_GOODS_ID
        FROM
          (
            SELECT
              product_id
            FROM
              romeo.sick_shipment
            WHERE
              romeo.sick_shipment.shipment_id = '$SSID'
            AND romeo.sick_shipment.lack_number > 0
          ) AS t1
        LEFT JOIN romeo.product_mapping ON t1.product_id = romeo.product_mapping.PRODUCT_ID";
  $result=$db->getAll($sql);
  QLog::log("Sinri_CheckSickGoods($SSID,$goods_id) SQL=$sql");
  foreach ($result as $key => $value) {
    QLog::log("Sinri_CheckSickGoods($SSID,$goods_id) CHECKING ".$value['ECS_GOODS_ID']);
    if($value['ECS_GOODS_ID']==$goods_id){
      QLog::log("Sinri_CheckSickGoods($SSID,$goods_id) OK");
      return true;
    }
  }
  QLog::log("Sinri_CheckSickGoods($SSID,$goods_id) FAIL");
  return false;
}

/**
拿一个SN去找Barcode
CHECKED ON 20130911
USED BY RF_sick_shipment.php
IN
**/
function getGoodsBarcodeWithSN($SN){
  global $db;
  $sql="SELECT romeo.location_barcode_serial_mapping.goods_barcode
        FROM romeo.location_barcode_serial_mapping
        WHERE romeo.location_barcode_serial_mapping.serial_number='$SN'
        LIMIT 1;";
  $res=$db->getOne($sql);
  QLog::log("getGoodsBarcodeWithSN($SN) Get $res SQL=$sql");
  return $res;
}

/**
尝试进行病单的完结
CHECKED ON 20130911
USED IN
RF_sick_shipment.php
**/
function tryCloseSickness($SSID){
  global $db;
  $sql="SELECT
          count(*)
        FROM
          romeo.sick_shipment
        WHERE
          romeo.sick_shipment.lack_number > 0
        AND romeo.sick_shipment.shipment_id = '$SSID';";
  $count=$db->getOne($sql);
  QLog::log("tryCloseSickness($SSID) GET $count SQL=$sql");
  if($count==0){
    //FINAL
    return true;
  } else {
    //NOT OVER
    return false;
  }
}

/**
CHECKED ON 20130911
USED IN
print_barcode.php
**/
function getGoodsNameByBarcode($goods_barcode){
  global $db;
  $sql="SELECT goods_name
        FROM
        (
        SELECT goods_id 
        FROM ecshop.ecs_goods_style
        WHERE ecshop.ecs_goods_style.barcode='$goods_barcode' and ecshop.ecs_goods_style.is_delete=0
        LIMIT 1
        ) as K1
        LEFT JOIN ecshop.ecs_goods ON ecshop.ecs_goods.goods_id=K1.goods_id
        LIMIT 1;
        ";
  $name=$db->getOne($sql);
  Qlog::log("getGoodsNameByBarcode($goods_barcode) GET in ecs_goods_style $name sql=".$sql);
  if($name==null || trim($name)==''){
    $sql="SELECT
            goods_name
          FROM
            ecshop.ecs_goods
          WHERE
            ecshop.ecs_goods.barcode = '$goods_barcode';";
    $name=$db->getOne($sql);
    Qlog::log("getGoodsNameByBarcode($goods_barcode) GET in ecs_goods $name sql=".$sql);
  }
  return $name;
}

?>
