<?php 

/**
 * 热敏添加面单-批量版
 * 
 * @author qhu 2015.2.5
 * @copyright
 */

//define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('inventory_picking','ck_distribution_warehousing');
require_once('function.php');
require_once('includes/lib_order.php');
require_once('includes/lib_common.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
//require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');//Killed by Sinri, 20151216
require_once(ROOT_PATH . 'admin/config.vars.php');
require_once('includes/lib_sinri_DealPrint.php');


/*
 * 批量追加时，为指定的发货单数组绑定新的热敏单号
 */
function batch_add_thermal_tracking_number($shipments){
	$result = array();
	foreach($shipments as $shipment){		
		$tracking_number = add_thermal_trackNum($shipment);
		$record = array();
		$record['add_tracking_number']='';
		if(empty($tracking_number)||$tracking_number==-1){
			
		}else{
			$record['add_tracking_number']=$tracking_number;
		}
		$newRecord = array_merge($shipment, $record); 
		array_push($result, $newRecord);
    }
    return $result;
}
	
/*
 * 为指定的订单绑定新的热敏单号
 */
function add_thermal_trackNum($shipment){
  global $db; 
  $shipmentId=$shipment['shipment_id'];
  $sql="SELECT
	      oi.order_id,oi.shipping_id,
        oi.carrier_bill_id,oi.order_status,oi.shipping_status,oi.pay_status,
        s.tracking_number,oi.distributor_id,
        oi.facility_id
	      FROM
	      ecshop.ecs_order_info oi
	      left join romeo.order_shipment os ON oi.order_id = cast(os.order_id as unsigned)
	      left join romeo.shipment s ON os.shipment_id = s.shipment_id
	      where os.shipment_id = '{$shipmentId}'
  ";
  $order=$db->getRow($sql);

  $arata_shipping_ids=array('89','115','99','85');
	if(in_array($order['shipping_id'],array('100','146','145'))){
    	echo("京东COD，韵达快递，速达快递 不支持批量追加热敏面单功能");
    	return -1;    	 
    }
  for($i=0;$i<3;$i++){
    $branch='';
    if(in_array($order['shipping_id'],$arata_shipping_ids)){
      $branch=getLocalBranchWithFacilityId($order['facility_id'],$order['distributor_id']);
    }
    $tracking_number = get_thermal_mailno($order['shipping_id'],$order['distributor_id'],$branch);//from lib_express_arata.php
    //sleep(2);
    if($tracking_number!=-1){
      break;
    }else{
      Qlog::log('SINRI_WARNING add_thermal_tracking_number for '.$shipmentId.' LOCKED by others, try again?');
      usleep(200000);//0.2 second waiting
    }
  }
  if($tracking_number=='0'){
    Qlog::log('SINRI_WARNING 发货单 '.$shipmentId.' 尝试竞拍热敏面单号时发现热敏面单库空了！');
    //return false;
  }else if($tracking_number==-1){
    Qlog::log('SINRI_WARNING 发货单 '.$shipmentId.' 尝试竞拍热敏面单号3次均告失败！');
    //return false;
  }
 
  bind_arata_shipment_mailno($order['shipping_id'],$tracking_number);//from lib_express_arata.php
  //将新获取的热敏单号赋值给$shipment数组中新运单号字段
  //$shipment['add_tracking_number']=$tracking_number;
 
  return $tracking_number;
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
    return $result;
 }



/*
 * 批量追加面单，更新数据库
 */
function batch_add_tracking_number_arata($BPSN, $shipment_ids, $tracking_numbers){
	
	global $db; 
	global $ecs;
	$message = '';
		foreach($shipment_ids as $key=>$shipment_id) {
		
			if(empty($tracking_numbers[$key])) {
				$message .= "该发货单（shipmentId:{$shipment->shipmentId}）没有对应的面单号;";
		        continue;  
			}
		
		    // 如果传递了发货单号则查询相关信息
		    $handle = soap_get_client('ShipmentService');
		    $response = $handle->getShipment(array('shipmentId' => $shipment_id));
		    $shipment = is_object($response->return) ? $response->return : null;
		    if (!$shipment){
		        $message .= "该发货单（shipmentId:{$shipment->shipmentId}）不存在;";
		        continue;   
		    } 
		    
		    //没有预订的就不让发货
		    $sql =" SELECT oir.status, oir.order_id
		            FROM
		                romeo.order_inv_reserved oir
		            LEFT JOIN romeo.order_shipment os ON oir.order_id = os.order_id
		            WHERE
		                os.shipment_id = '{$shipment_id}'";
		    $status = $db->getAll($sql);
		    $status = $status[0];
		    if ($status['status'] == "N" || $status == null) { 
		        $message .= "该发货单（shipmentId:{$shipment->shipmentId}）对应的订单未预订成功（orderId:{$status['order_id']}）;";
		        continue;   
		    }
		    
		    // 取得发货单的主订单信息
		    // 取得发货单的所有订单信息
		    // 如果是没有合并发货的订单，查找其发货单信息
		    $order = null;
		    $order_list = array();
		    $shipment_list = array($shipment);
		    $order_shipment_list = array();
		    
		    // 取得发货单的所有订单信息
		    $response2 = $handle->getOrderShipmentByShipmentId(array('shipmentId' => $shipment->shipmentId));
		    if (is_array($response2->return->OrderShipment)) {
		    	$order_shipment_list = $response2->return->OrderShipment;
		    } elseif (is_object($response2->return->OrderShipment)){
		    	$order_shipment = $response2->return->OrderShipment;
		    	$order_shipment_list[] = $order_shipment;
		    } else{
		        $message .= "该发货单（shipmentId:{$shipment->shipmentId}）异常，找不到对应的主订单;";
		        continue;   
		    }
		    
			$i = 0;
			foreach($order_shipment_list as $orderShipment) {
		        $order_list[$i] = get_core_order_info('', $orderShipment->orderId);
				if ($shipment->primaryOrderId == $orderShipment->orderId){
					$order = $order_list[$i];
					$response3 = $handle->getShipmentByOrderId(array('orderId' => $order['order_id']));
					if (is_array($response3->return->Shipment)) {
						foreach($response3->return->Shipment as $_shipment){
							if ($_shipment->primaryOrderId != $shipment->primaryOrderId){
								$shipment_list[] = $_shipment;
							} elseif ($_shipment->shipmentId != $shipment->shipmentId) {
								$shipment_list[] = $_shipment;
							}
						}
					}
				}
				$i++;
			}
		
			// 复核过才能添加
		    if ($order['shipping_status']==8){
		    	$trackingNumberFrom=$trackingNumberTo=array();
		    	$trackingNumberChanged=true;
		    	$trackingNumber = $tracking_numbers[$key];
		    	
		    	// 添加面单号（分开多个面单发货）
				foreach($shipment_list as $shipment_item){
		        	$trackingNumberFrom[]=$shipment_item->trackingNumber;
		        	$trackingNumberTo[]=$shipment_item->trackingNumber;
		        }
		        $trackingNumberTo[] = $trackingNumber;
		        
				try{
					$handle=soap_get_client('ShipmentService');
					$object=$handle->createShipment(array(
				        'orderId' => $order['order_id'],
						'partyId' => $shipment->partyId,
						'shipmentTypeId'=>$shipment->shipmentTypeId,
						'carrierId'=>$shipment->carrierId,
						'trackingNumber'=>$trackingNumber,
						'createdByUserLogin'=>$_SESSION['admin_name']
					));
					
					$handle->createOrderShipment(array(
						'orderId'   =>$order['order_id'],
						'shipmentId'=>$object->return,
					));
				}
				catch (Exception $e){
				
				}
		    	
		    	// 面单发生更改则更新
		    	if($trackingNumberChanged){
					$modify = array_diff ( $trackingNumberFrom, $trackingNumberTo );
					$add = array_diff ( $trackingNumberTo, $trackingNumberFrom );
					if (count ( $modify ) > 0) {
						$note = "批量追加：快递面单从" . implode ( ',', $modify ) . "更改为" . implode ( ',', $add );
					} else {
						$note = "批量追加：增加了快递面单" . implode ( ',', $add );
					}
//					echo "trackingNumberFrom[0]:".$trackingNumberFrom[0]."trackingNumberFrom[1]:".$trackingNumberFrom[1]; 
//		    		echo "trackingNumberTo[0]:".$trackingNumberTo[0]."trackingNumberTo[1]:".$trackingNumberTo[1]; 
//					echo $note;
					
					// 更新运单的运单号信息
					foreach($order_list as $order_item) {
						// killed 20160105 by Sinri
						// $db->query(sprintf("UPDATE {$ecs->table('carrier_bill')} SET bill_no = '%s' WHERE bill_id = '%d'",implode(',',$trackingNumberTo),$order_item['carrier_bill_id']));
						// 记录订单备注
						$sql = "
							INSERT INTO {$ecs->table('order_action')} 
							(order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
							('{$order_item['order_id']}', '{$order_item['order_status']}', '{$order_item['shipping_status']}', '{$order_item['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
						";
						$db->query($sql);
					}
		            
		    	}
		    	else{
		    		$message .= "该发货单（shipmentId:{$shipment->shipmentId}）面单号没有改变;";
		            continue;   
		    	}
		    }
		    // 不是可以扫面单的状态了
		    else {
		    	$message .= "该发货单未复核，必须复核过才能添加面单：".$shipment_id.";";
		        continue;   
		    }
		        
		}
		
		Qlog::log('not_success_add_shipments bpsn'.$BPSN.' shipment_ids:'.$message);
		
		if(!empty($message)) {
			$message = $message.' 其余追加成功！未成功的请用单个添加运单功能完成！';
		} else {
			$message = '批量追加热敏面单成功！';
		}
		
		return $message;
	
}
		


/*
 * 根据批拣单号查询add_thermal_tracking_number_print_record表，得到该批拣单号追加了几次
 */
function getPICIByBatch($TN){
	global $db;
	//为批量打印，则按批拣单号和追加的批次数添加打印记录	
	$arr=array();
	$arr=explode('-', $TN);
	$batch_sn=$arr[0].'-'.$arr[1];
	$sql = "
			select PICI FROM romeo.add_thermal_tracking_number_print_record
			where BATCH_PICK_SN='{$batch_sn}' ORDER BY PICI;";
	$result=0;
	if($db->getOne($sql))
		$result=$db->getOne($sql);
    return $result;
}

		

/*
 * 插入表add_thermal_tracking_number_print_record，插入指定追加批次的批拣单号的打印历史记录信息
 */
function insertBatchAddPrintRecords($TN,$pici){
	global $db;
	//为批量打印，则按批拣单号和追加的批次数添加打印记录	
	$arr=array();
	$arr=explode('-', $TN);
	$batch_sn=$arr[0].'-'.$arr[1];
	$sql = "
			INSERT INTO romeo.add_thermal_tracking_number_print_record
    		(BATCH_PICK_SN, PICI, TRACKING_NUMBER, PRINT_USER, PRINT_TIME) VALUES 
    		('{$batch_sn}', $pici, '', '{$_SESSION['admin_name']}', NOW())";
	$db->query($sql);
    return true;
}


?>
