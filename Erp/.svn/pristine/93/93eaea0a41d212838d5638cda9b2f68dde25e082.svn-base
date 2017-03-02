<?php
/*
 * Created on 2011-5-27
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 include_once('lib_soap.php');
 include_once('lib_cache.php');
 

 /**
  * 创建新记录
  */
 function create_supplier_return_request($request, $items){
 	try{
 	    $handle = supplier_return_get_soap_client();
 	    $result = $handle -> createSupplierReturnRequest(
 	                       array('request' => $request, 'items' => $items)
 	                    );
 	}catch(SoapFault $e){ 
 		return "php 调用 romeo 错误";
 	}
 	return $result ;
 }
 
function create_supplier_return_request_V3($request,$items,$requestStatus,$checkStatus,$actionUser){
 	try{
 	    $handle = supplier_return_get_soap_client();
 	    $result = $handle -> createSupplierReturnRequestV3(array('request' => $request, 
														        'items' => $items ,
														        'requestStatus'=>$requestStatus,
														        'checkStatus'=>$checkStatus,
														        'actionUser'=>$actionUser
														     ));
 	}catch(SoapFault $e){ 
 		return "php 调用 romeo 错误";
 	} 
 	return $result->return->entry;
 }
 
 /**
  * 根据条件查询记录
  */	
 function get_supplier_return_request($request, $startDate=null, $endDate=null,$pageSize=100,$pageNo=1){
 	$resultList = array();
    try{
        $handle = supplier_return_get_soap_client();
        $response = $handle -> getSupplierReturnRequest(
 	                       array('request' => $request, 'startDate' => $startDate, 'endDate' => $endDate,'pageSize' => $pageSize,'pageNo' => $pageNo)
 	                   );
        $list = isset($response->return->SupplierReturnRequest) 
    	          ? wrap_object_to_array($response->return->SupplierReturnRequest) : array() ;
 	    foreach($list as $item){
   		    $resultList[$item->supplierReturnRequestId] = (array)$item ;
   	    }
    }catch(SoapFault $e){
    	
    }
    return $resultList ; 
 }
 
 /**
  * 查询item
  */
 function get_supplier_return_request_item($requestId){
     $resultList = array();
     try{
         $handle = supplier_return_get_soap_client();
         $response = $handle -> getSupplierReturnRequestItem(
                            array('requestId' => $requestId)
                       );
         
         $list = isset($response->return->SupplierReturnRequestItem) 
    	              ? wrap_object_to_array($response->return->SupplierReturnRequestItem) : array() ;
         
         foreach($list as $item){
       	     $resultList[$item->supplierReturnRequestItemId] = (array)$item;
         }
     }catch(SoapFault $e){
     	
     }
     return $resultList ;
 }
 
 /**
  * 更新记录状态
  */
 function update_supplier_return_request_status($requestId, $status = '',$order_id){
 	global $soapclient;
 	try{
 		  
// 		 $soap_client = soap_get_client('InventoryService');
//       $resultInv = $soap_client->cancelOrderInventoryReservation(array('orderId'=>$order_id));
                 
 		 $handle = supplier_return_get_soap_client();	    
 	     $result = $handle -> updateSupplierReturnRequestStatus(
 	                       array('requestId' => $requestId, 'status' => $status)
 	                   ); 
          
	      
 	    // var_dump($result);
 	}catch(SoapFault $e){ 
 	
 	}                   
 	return $result ;
 }
 
 /**
  * 更新ITEM记录
  * 
  */
 function supplier_return_item_amount_update($supRetReqId, $serialNumber, $amount, $returnedByUser){
 	try{
 	    $handle = supplier_return_get_soap_client();
 		
 	    $result = $handle -> supplierReturnItemAmountUpdate(
 		                   array('requestId' => $supRetReqId, 'serialNumber' => $serialNumber, 
                                 'amount' => $amount, 'adminUser' => $returnedByUser)
 		              );
 		// var_dump($result);
 	}catch(SoapFault $e){ 
 		
 	}
 	return $result;
 	
 }
 
 function deliver_supplier_return_order_inventory_sn ($supRetReq,  $return_serials){
 	global $db;
 	$message = '';
   	try {
		$supRetReqId = $supRetReq['supplierReturnRequestId']; 
		$product_id = $supRetReq['productId'];

		$is_check_pass = true;
		$return_serials_str =  implode("','",$return_serials);

		//检测新库存是否有足够的数量
		$sql = "
		    select ii.serial_number
				from  romeo.inventory_item ii
				where serial_number in ('{$return_serials_str}') and quantity_on_hand_total > 0
		 ";
	    $ret_items = $db -> getAll($sql);
	    if(empty($ret_items)){
	    	$message .= '以下串号全部没有可用库存，请找店长重新编辑：';
	    	$message .= implode(",",$return_serials);
	    }else if(count($ret_items) < count($return_serials)){
	    	$is_check_pass = false;
	    	$message .= '以下串号新库存没有可用库存，请找店长重新编辑：';
    		$has_inventory_arr = array();
	    	foreach($ret_items as $item){
	    		$has_inventory_arr[] = $item['serial_number'];
	    	}
	        foreach($return_serials as $item){
	        	if(!in_array($item,$has_inventory_arr)){
	        		$message .= $item.',';
	        	}
	        }
	    }

	    // 操作出库
	    if($is_check_pass){
	    	$result = create_supplier_return_order_gtV5 ($supRetReq['supplierReturnRequestId'], $return_serials, $supRetReq['purchaseUnitPrice'],$supRetReq['inventoryItemTypeId'],count($return_serials),null,$supRetReq['returnSupplierId']);
	    	if($result['status']){
	        	$message .= '出库成功supRetReqId:'.$supRetReqId;
	        }else{
	        	$message .= '出库失败supRetReqId：'.$supRetReqId.' '.$result['err'];
	        }
	    }
	 } catch (Exception $e){
   	  	$message .= $e->getMessage();
     }
     return $message;
 }
 function deliver_supplier_return_order_inventory($supRetReq, $out_num){
 	global $db;
 	$message = '';
 	$is_check_pass = true;
   	try {
		$product_id = $supRetReq['productId'];


		//检测新库存是否有足够的数量
		$new_result = new_inventory_is_enough_no_serial($supRetReq['facilityId'],$product_id,
												$supRetReq['purchaseUnitPrice'],$supRetReq['statusId'],$out_num,$supRetReq['batchSn'],$supRetReq['returnSupplierId']);											
		if(!empty($new_result)){
			$message .= $new_result;
	        $is_check_pass = false;
		}

		
	    if($is_check_pass){
	    	// 库存出库
	        $result = create_supplier_return_order_gtV5 ($supRetReq['supplierReturnRequestId'], null, $supRetReq['purchaseUnitPrice'],
	                                                           $supRetReq['inventoryItemTypeId'],$out_num,$supRetReq['batchSn'],$supRetReq['returnSupplierId']) ;
	        if($result['status']){
	        	$message .= '出库成功';
	        }else{
	        	$message .= '出库失败：'.$result['err'];
	        }
	    }
	 } catch (Exception $e){
   	  	$message .= $e->getMessage();
     }
     return $message;
 }
 function new_inventory_is_enough_no_serial($facilityId,$product_id,$purchaseUnitPrice,$statusId,$out_num,$batchSn,$returnSupplierId){
 	global $db;
	$condition = '';
	$batchSn = trim($batchSn);
	if(!empty($batchSn)) {
		$condition = " and ii.batch_sn = '{$batchSn}' ";
	} else {
		$condition = " and ii.batch_sn = '' ";
	}
	
	if(!empty($returnSupplierId)) {
		$condition .= " and ii.provider_id = '{$returnSupplierId}' ";
	}
 	//检测新库存是否有足够的数量
	$sql = "
	    select sum(ii.quantity_on_hand_total)
			from romeo.inventory_item ii 
		where ii.quantity_on_hand_total > 0 
		      and ii.facility_id = '{$facilityId}' and ii.product_id = '{$product_id}'
		      and ii.unit_cost = '{$purchaseUnitPrice}' and ii.status_id = '{$statusId}'
	 " .$condition;
    $sum_new_inventory = $db -> getOne($sql);

    if(empty($sum_new_inventory)){
    	$sum_new_inventory = 0;
    }
    if($sum_new_inventory < $out_num){
    	return "新库存(供应商+商品库存状态+采购单价+(对于批次维护业务组，批次号/生产日期) 均一致的库存)不足，只有'{$sum_new_inventory}',请找店长重新编辑。";
    }
    return null;
 }
  /**
  * -GT订单出库  新版
  */
 function create_supplier_return_order_gtV5 ($supRetRequestId, $in_sn_arr, $purchase_unit_price, $inventoryItemTypeId,$number,$batch_sn,$returnSupplierId){
	$result = array();
 	global $db;
 	try {
 		$actionUser =  $_SESSION['admin_name'];
 		
 		if (! $actionUser) {
 			$actionUser = 'system';
 		}
 		
 		$handle = supplier_return_get_soap_client();
 		
 		$sql = "select oi.order_id 
				from ecshop.ecs_order_info oi
				inner join romeo.supplier_return_request_gt map on oi.order_sn = map.SUPPLIER_RETURN_GT_SN
				where map.supplier_return_id = '{$supRetRequestId}'";
 		// 旧库存出库后 返回-GT订单ID
        	$orderId = $db -> getOne($sql);

 		// 判断是否要更新新库存
 		if (!empty($orderId)) {
 		    $result['status'] = create_supplier_return_order_new($orderId,$in_sn_arr,$purchase_unit_price,$inventoryItemTypeId,$actionUser,$number,$batch_sn,$returnSupplierId);	    

 		    if($result['status']){
 		    	if(empty($in_sn_arr)){
 		    		for($i = 0; $i < $number; $i ++){
 		    			$in_sn_arr[] = $i;
 		    		}
 		    	}
		    	// return map 表
 		    	// 新旧库存出库完成后，更新-GT申请单
 		    	if(!updateStatusAfterGTOutInventory($orderId,$supRetRequestId,$in_sn_arr,$actionUser)){
			    	$result['err'] = '更新-GT申请单失败';
			    	$result['status'] = false;
			    }
 		    }else{
 		    	$result['err'] = '新库存出库失败';
 		    	$result['status'] = false;
 		    }
 		}else{
 			$result['err'] = '老库存出库失败';
 			$result['status'] = false;
 		}
 	} catch (SoapFault $e){
 		$result['status'] = false;
 		$result['err'] = 'romeo 调用bug'.$e->getMessage();
 	}
 	return $result;
 }
 function updateStatusAfterGTOutInventory($orderId,$supRetRequestId,$in_sn_arr,$actionUser){
 	// return map 表
	// 新旧库存出库完成后，更新-GT申请单
	$handle = supplier_return_get_soap_client();
    $request = array(
              'supRetRequestId' => $supRetRequestId ,
              'amount' => count($in_sn_arr) ,
              'actionUser' => $actionUser , 
              'inSnArr' => $in_sn_arr ,
           ) ;
    $result = $handle->supplierReturnRecallUpdate($request);
    
    $request = array(
              'orderId' => $orderId,
              'amount' => count($in_sn_arr), 
           ) ;
    
	$handle->purchaseReturnMap($request);
	
	return $result;
 }
 function purchaseReturnMapForTools($orderId){
 	$amount = 1;
 	$handle = supplier_return_get_soap_client();
 	$request = array(
              'orderId' => $orderId,
              'amount' => $amount, 
           ) ;
    
	$handle->purchaseReturnMap($request);
 }
/*
 * 新库存出库
 * */
 function create_supplier_return_order_new($orderId,$in_sn_arr,$purchaseUnitPrice,$inventoryItemType,$actionUser,$amount,$batchSn,$returnSupplierId){	
 	global $db; 
	 $sql =  "select 
					oi.facility_id,
					og.rec_id,og.status_id,pm.product_id
				from ecshop.ecs_order_info oi
				inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
				inner join romeo.product_mapping pm on pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
				where oi.order_id = '{$orderId}'
			";
	 $order_item = $db->getRow($sql);
	 $orderGoodsId = $order_item['rec_id'];	
	 $fromFacilityId = $order_item['facility_id'];
	 $fromStatusId = $order_item['status_id'];
	 $productId = $order_item['product_id'];
	 $sql = "select INVENTORY_ITEM_ACCT_TYPE_ID
			from romeo.inventory_item
			where PRODUCT_ID = '{$productId}'
			and FACILITY_ID = '{$fromFacilityId}'
			and STATUS_ID = '{$fromStatusId}' limit 1 ";
	 $acctTypeId = $db->getOne($sql);
	 require_once ROOT_PATH . '/RomeoApi/lib_inventory.php';
	 $fromContainerId = facility_get_default_container_id($fromFacilityId);
    $keys = array(
              'productId'		=>'StringValue', 
              'amount'			=>'NumberValue', 
              'inventoryItemType' => 'StringValue',
              'orderId'			=>'StringValue', 
              'fromStatusId'	=>'StringValue', 
              'actionUser'		=>'StringValue', 
              'acctTypeId'		=>'StringValue', 
              'orderGoodsId'	=>'StringValue',
              'fromFacilityId' 	=> 'StringValue',
              'fromContainerId' => 'StringValue',
              'purchaseUnitPrice'=>'NumberValue',
              'batchSn'          =>'StringValue',
              'returnSupplierId' =>'NumberValue'
              );
    $param = new HashMap();
    foreach ($keys as $key => $type) {
        if(${$key} == null) { continue; }
        $gv = new GenericValue();
        $method = 'set'.$type;
        $gv->$method(${$key});
        $param->put($key, $gv->getObject());
    }
    $input = func_get_args();
    return romeo_execute("createSupplierReturnOrderNewGT", $input, $param, $in_sn_arr);
 }
 
  /**
  * -GT生成订单,并审核通过
  * ljzhou 2013-12-13
  */
 function create_supplier_return_order ($supRetRequestId, $actionUser, &$order_id = NULL){
 	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
 	// 生成订单加锁，防止并发
	$lock_name = "supplier_return_create_order_{$supRetRequestId}";
    $lock_file_name = get_file_lock_path($lock_name, 'gt');

    $lock_file_point = fopen($lock_file_name, "w+");
    $would_block = false;
    $message = '';
    if(!flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
    	$message .= '该审核动作有人在操作,请不要重复点击，supplier_return_create_order supplier_requst_id:'.$supRetRequestId;
    }else{
    	try {
	 		$handle = supplier_return_get_soap_client();
	 		
	 		$request = array(
	 		               'supRetRequestId' => $supRetRequestId ,
	                       'actionUser' => $actionUser , 
	 		            ) ;
	 		
	 		$result = $handle->createSupplierReturnOrderNew($request) ;
	 		$order_id = $result->return;
	 	} catch (SoapFault $e){
	 		$message .= 'romeo exception supplier_requst_id:'.$supRetRequestId.' exception:'.$e;
	 	}
	 	flock($lock_file_point, LOCK_UN);
    }
    fclose($lock_file_point);
    unlink($lock_file_name);
	if($message) {
		return $message;
	} else {
		return null;
	}
 }
 
 
 function get_serialized_goods($facility_id, $original_provider_id, $status_id , $purchase_paid_amount, $product_id){
 	global $db;

	$sql = "
            select distinct ii.serial_number as erp_goods_sn , '无维护有效期' as validity
			from romeo.inventory_item ii
			where ii.quantity_on_hand_total > 0
  			and ii.facility_id = '%s' and ii.provider_id = %d and ii.status_id = '%s'
		    and ii.inventory_item_type_id = 'SERIALIZED' and ii.unit_cost = '%s'
 			and ii.product_id = '%s'
 			order by ii.created_stamp
        " ;
     $serial_goods = $db->getAll(sprintf($sql,$facility_id,
     							intval($original_provider_id), $status_id , 
     							$purchase_paid_amount, $product_id));
     return $serial_goods;
 }
 
 // -gt 订单重构 每一行封装的是一个request记录 这些记录放到一个订单中 
 function apply_ret_req_V3($obj_datas,$requestStatus,$checkStatus,$actionUser){
 	global $db;
    $status_map = array('NEW' => 'INV_STTS_AVAILABLE', 'SECOND_HAND' => 'INV_STTS_USED', 'DISCARD' => 'INV_STTS_DEFECTIVE') ; 
    $return_requests = array();  // 封装请求的数据 
    $return_request_items = array();
    
    foreach($obj_datas as $obj_data){
        if (!in_array($obj_data->status_id, $status_map)) {
    		$obj_data->status_id = $status_map[$obj_data->status_id] ;
    	} 
   		$sql = "select fchrWhName from ecshop.brand_gymboree_warehouse where fchrWarehouseID = '{$obj_data->fchr_warehouse_id}'";
 		$fchr_warehouse_name = $db->getOne($sql);
 		    // 退货记录操作
	    $ret_req = new stdClass();
	    $return_items = array() ;
		if ($obj_data->goods_item_type == 'NON-SERIALIZED') {
		    	$ret_req -> inventoryItemTypeId = 'NON-SERIALIZED' ;
		    	$item = new stdClass();
		    	$item -> amount = 0 ; 
		    	$item -> serialNumber = null ;
		    	$item -> supplierReturnOrderSn = null ;
		    	$return_items[] = $item ;
		} elseif ($obj_data->goods_item_type == 'SERIALIZED') {
		    	$ret_req -> inventoryItemTypeId = 'SERIALIZED' ;
		    	$obj_data->serial_number = str_replace('\\', '', $obj_data->serial_number);
		        $serialList = json_decode($obj_data->serial_number, true);
		        foreach($serialList as $serial){
		        	$item = new stdClass();
		    	    $item -> amount = 0 ; 
		    	    $item -> serialNumber = $serial['erp_goods_sn'] ;
		    	    $item -> supplierReturnOrderSn = null ;
		        	$return_items[] = $item ;
		        }     	
		    	$obj_data->ret_amount = count($serialList) ;
	     }
	     
		$ret_req -> supplierReturnRequestId = $obj_data->supplierReturnRequestId;//
   		$ret_req -> taxRate = $obj_data->tax_rate;
    	$ret_req -> excutedAmount = 0 ;
   		$ret_req -> returnOrderAmount = $obj_data->ret_amount;
   		$ret_req -> orderTypeId = $obj_data->order_type_id;
   		$ret_req -> returnSupplierId = $obj_data->ret_provider_id;
    	$ret_req -> batchSn = $obj_data->batch_sn; 									//批次号
    	$ret_req -> originalSupplierId = $obj_data->original_provider_id;
   		$ret_req -> paymentTypeId = $obj_data->purchase_paid_type;
    	$ret_req -> checkNo = $obj_data->chequeNo;//
    	$ret_req -> partyId = $_SESSION['party_id'];
    	$ret_req -> facilityId = $obj_data->facility_id;
    	$ret_req -> productId = $obj_data->productId ;
    	$ret_req -> unitPrice = $obj_data->goods_price ;
    	$ret_req -> currency = $obj_data->currency ;
    	$ret_req -> purchaseUnitPrice = $obj_data->purchase_unit_price ;
   	    $ret_req -> createdUserByLogin = $_SESSION['admin_name'];
    	$ret_req -> statusId = $obj_data->status_id ;//
        $ret_req -> fchrWarehouseID = $obj_data->fchr_warehouse_id; 
        $return_request_items[] = $return_items;
   		 //金宝贝特殊处理，将金宝贝的退货仓库信息记录在supplier_return_request中的note里面
   		 if('65574' == $_SESSION['party_id'] && !empty($obj_data->fchr_warehouse_id)){
    		$ret_req -> note = "金宝贝退货库ID:".$obj_data->fchr_warehouse_id.":".$fchr_warehouse_name.":".$obj_data->remark;
   		 }else{
    		$ret_req -> note = $obj_data->remark;
    	  }
	     $return_requests[] = $ret_req;
    } // end of foreach 
    $result = create_supplier_return_request_V3($return_requests,$return_request_items,$requestStatus,$checkStatus,$actionUser);
    return $result;
 }
 
 function apply_ret_req($obj_data, &$supRetReqId = null){
 	global $db;

 	
    $status_map = array('NEW' => 'INV_STTS_AVAILABLE', 'SECOND_HAND' => 'INV_STTS_USED', 'DISCARD' => 'INV_STTS_DEFECTIVE') ;  
    if (!in_array($obj_data->status_id, $status_map)) {
    	$obj_data->status_id = $status_map[$obj_data->status_id] ;
    }  
    $sql = "select fchrWhName from ecshop.brand_gymboree_warehouse where fchrWarehouseID = '{$obj_data->fchr_warehouse_id}'";
 	$fchr_warehouse_name = $db->getOne($sql);
    // 退货记录操作
    $ret_req = new stdClass();
    $return_items = array() ;
    if ($obj_data->goods_item_type == 'NON-SERIALIZED') {
    	$ret_req -> inventoryItemTypeId = 'NON-SERIALIZED' ;
    	
    	$item = new stdClass();
    	    $item -> amount = 0 ; 
    	    $item -> serialNumber = null ;
    	    $item -> supplierReturnOrderSn = null ;
    	    
    	$return_items[] = $item ;
    	
    } elseif ($obj_data->goods_item_type == 'SERIALIZED') {
    	$ret_req -> inventoryItemTypeId = 'SERIALIZED' ;
        $serialList = json_decode($obj_data->serial_number, true);
        if(is_array($serialList))
        foreach($serialList as $serial){
        	$item = new stdClass();
    	          $item -> amount = 0 ; 
    	          $item -> serialNumber = $serial['erp_goods_sn'] ;
    	          $item -> supplierReturnOrderSn = null ;
        	
        	$return_items[] = $item ;
        }     	
    	
    	$obj_data->ret_amount = count($serialList) ;
    }
    
    $sql="SELECT g.goods_party_id
	                FROM  ecshop.ecs_goods AS g
	                WHERE g.goods_id = '{$obj_data->ret_goods_id}'"; 
    $ret_req -> supplierReturnRequestId = $obj_data->supplierReturnRequestId;//
    $ret_req -> taxRate = $obj_data->tax_rate;
    $ret_req -> excutedAmount = 0 ;
    $ret_req -> returnOrderAmount = $obj_data->ret_amount;
    $ret_req -> orderTypeId = $obj_data->order_type_id;
    $ret_req -> returnSupplierId = $obj_data->ret_provider_id;
    $ret_req -> batchSn = $obj_data->batch_sn; 									//批次号
    $ret_req -> originalSupplierId = $obj_data->original_provider_id;
    $ret_req -> paymentTypeId = $obj_data->purchase_paid_type;
    $ret_req -> checkNo = $obj_data->chequeNo;//
    $ret_req -> partyId = $db->getOne($sql);
    $ret_req -> facilityId = $obj_data->facility_id;
    $ret_req -> productId = $obj_data->productId ;
    $ret_req -> unitPrice = $obj_data->goods_price ;
    $ret_req -> currency = $obj_data->currency ;
    $ret_req -> purchaseUnitPrice = $obj_data->purchase_unit_price ;
    $ret_req -> createdUserByLogin = $_SESSION['admin_name'];
    $ret_req -> statusId = $obj_data->status_id ;//
    $ret_req -> fchrWarehouseID = $obj_data->fchr_warehouse_id;//
    //金宝贝特殊处理，将金宝贝的退货仓库信息记录在supplier_return_request中的note里面
    if('65574' == $_SESSION['party_id'] && !empty($obj_data->fchr_warehouse_id)){
    	$ret_req -> note = "金宝贝退货库ID:".$obj_data->fchr_warehouse_id.":".$fchr_warehouse_name.":".$obj_data->remark;
    }else{
    	$ret_req -> note = $obj_data->remark;
    }

 	// 生成退货订单_预申请
 	require_once(ROOT_PATH. 'includes/debug/lib_log.php');
 	QLog::log("create_supplier_return_item_lib :  productId:".$ret_req -> productId." facility_id:".$ret_req -> facilityId);
    $result = create_supplier_return_request($ret_req, $return_items);
    $supRetReqId = $result->return;
    return $supRetReqId;
 }
 

 function export_batch_sn_return_list_excel($array_ret_item){
 	set_include_path ( ROOT_PATH . 'admin/includes/Classes/' );
 	require 'PHPExcel.php';
 	require 'PHPExcel/IOFactory.php';
	$excel = new PHPExcel ();
	$sheet = $excel->getActiveSheet();
	
	$name = '供应商批次号退货申请（-gt）清单';
	$sheet->setTitle($name);
	
	$config_title = array(
					  'A'=>'批次号',
					  'B'=>'erp专用id',
					  'C'=>'供应商条码',
					  'D'=>'仓库编码',
					  'E'=>'商品类型',
					  'F'=>'分类',
					  'G'=>'商品名称',
					  'H'=>'商品条码',
					  'I'=>'样式条码',
					  'J'=>'供应商',
				      'K'=>'库存类型',
				      'L'=>'业务类型',
				      'M'=>'采购单价',
				      'N'=>'币种',
					  'O' =>'库存数量',
					  'P' =>'可申请数量',
				      'Q' => '仓库',
					  'R'=>'退还商品单价',
					  'S'=>'税率',
				      'T'=>'退还给供应商',
				      'U'=>'退货数量',
				      'V'=>'退款方式(银行付款-1\现金-2\支票-4)',
				      'W'=>'退货订单类型(退给供应商（扣原采购发票）-0\二手商品销售（不扣原采购发票）-1)',
					  'X'=>'支票号',
				      'Y'=>'备注',
				);
	foreach ($config_title as $cell_name => $cell_value){
		$sheet->setCellValue ( $cell_name.'1', $cell_value );
	}

	$config_value = array(
					  'A'=>'batch_sn',
					  'B'=>'product_id',
					  'C'=>'provider_id',
					  'D'=>'facility_id',
					  'E'=>'goods_item_type',
					  'F'=>'cat_name',
					  'G'=>'goods_name',
					  'H'=>'goods_code',
					  'I'=>'style_code',
					  'J'=>'provider_name',
				      'K'=>'is_new',
				      'L'=>'order_type',
				      'M'=>'purchase_paid_amount',
				      'N'=>'currency',
					  'O' =>'storage_amount',
					  'P' => 'can_request',
				      'Q'=>'facility_name',
					  'R'=>'purchase_paid_amount'	
				);
	$config_default_value = array(
					  'N'=>'RMB',
					  'S'=>'1.17',
				      'V'=>'1'	
				);
	$j = 2;
	foreach ($array_ret_item as $item){
		foreach ($config_value as $cell_name => $cell_value){
			$sheet->setCellValue ( $cell_name.$j, $item[$cell_value]);
		}
		foreach ($config_default_value as $cell_name => $cell_value){
			$sheet->setCellValue ( $cell_name.$j, $cell_value);
		}
		$j++;
	}
 	if (! headers_sent ()) {
		header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header ( 'Content-Disposition: attachment; filename="供应商批次号退货申请（-gt）清单.xlsx"' );
		header ( 'Cache-Control: max-age=0' );
		$output = PHPExcel_IOFactory::createWriter ( $excel, 'Excel2007' );
		$output->setOffice2003Compatibility ( true );
		$output->save ( 'php://output' );
	}
 }
 
  function export_batch_dt_list_excel($array_ret_item){
 	set_include_path ( ROOT_PATH . 'admin/includes/Classes/' );
 	require 'PHPExcel.php';
 	require 'PHPExcel/IOFactory.php';
	$excel = new PHPExcel ();
	$sheet = $excel->getActiveSheet();
	
	$name = '供应商批次号退货申请（-gt）清单';
	$sheet->setTitle($name);
	$config_title = array(
					  'A'=>'批次号',
					  'B'=>'erp专用id',
					  'C'=>'供应商条码',
					  'D'=>'仓库编码',
					  'E'=>'商品类型',
					  'F'=>'分类',
					  'G'=>'商品名称',
					  'H'=>'商品条码',
					  'I'=>'样式条码',
					  'J'=>'供应商',
				      'K'=>'库存类型',
				      'L'=>'业务类型',
				      'M'=>'采购单价',
				      'N'=>'币种',
					  'O' =>'库存数量',
					  'P' =>'可申请数量',
				      'Q' => '仓库',
					  'R'=>'退还商品单价',
					  'S'=>'税率',
				      'T'=>'退还给供应商',
				      'U'=>'退货数量',
				      'V'=>'退款方式(银行付款-1\现金-2\支票-4)',
				      'W'=>'退货订单类型(调拨订单-0)',
					  'X'=>'支票号',
				      'Y'=>'备注',
				      'Z'=>'入货仓库编码',
				      'AA'=>'到货时间'
				      
				);
	
	foreach ($config_title as $cell_name => $cell_value){
		$sheet->setCellValue ( $cell_name.'1', $cell_value );
	}
   
    $config_value = array(
					  'A'=>'batch_sn',
					  'B'=>'product_id',
					  'C'=>'provider_id',
					  'D'=>'facility_id',
					  'E'=>'goods_item_type',
					  'F'=>'cat_name',
					  'G'=>'goods_name',
					  'H'=>'goods_code',
					  'I'=>'style_code',
					  'J'=>'provider_name',
				      'K'=>'is_new',
				      'L'=>'order_type',
				      'M'=>'purchase_paid_amount',
				      'N'=>'currency',
					  'O' =>'storage_amount',
					  'P' => 'can_request',
				      'Q'=>'facility_name',
					  'R'=>'purchase_paid_amount'	
				);
	
	$config_default_value = array(
					  'N'=>'RMB',
					  'S'=>'1.17',
				      'V'=>'1'	
				);
	$j = 2;
	foreach ($array_ret_item as $item){
		foreach ($config_value as $cell_name => $cell_value){
			$sheet->setCellValue ( $cell_name.$j, $item[$cell_value]);
		}
		foreach ($config_default_value as $cell_name => $cell_value){
			$sheet->setCellValue ( $cell_name.$j, $cell_value);
		}
		$j++;
	}
 	if (! headers_sent ()) {
		header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header ( 'Content-Disposition: attachment; filename="供应商批次号调拨申请（-dt）清单.xlsx"' );
		header ( 'Cache-Control: max-age=0' );
		$output = PHPExcel_IOFactory::createWriter ( $excel, 'Excel2007' );
		$output->setOffice2003Compatibility ( true );
		$output->save ( 'php://output' );
	}
 }
 
 function export_supplier_return_list_excel($array_ret_item){
 	set_include_path ( ROOT_PATH . 'admin/includes/Classes/' );
 	require 'PHPExcel.php';
 	require 'PHPExcel/IOFactory.php';
	$excel = new PHPExcel ();
	$sheet = $excel->getActiveSheet();
	
	$name = '供应商退货申请（-gt）清单';
	$sheet->setTitle($name);
	$config_title = array('A'=>'erp专用id',
					  'B'=>'供应商条码',
					  'C'=>'仓库编码',
					  'D'=>'商品类型',
					  'E'=>'分类',
					  'F'=>'商品名称',
					  'G'=>'商品条码',
					  'H'=>'样式条码',
					  'I'=>'供应商',
				      'J'=>'库存类型',
				      'K'=>'业务类型',
				      'L'=>'采购单价',
				      'M'=>'币种',
					  'N' =>'库存数量',
					  'O' =>'可申请数量',
				      'P'=>'仓库',
					  'Q'=>'退还商品单价',
					  'R'=>'税率',
				      'S'=>'退还给供应商',
				      'T'=>'退货数量',
				      'U'=>'退款方式(银行付款-1\现金-2\支票-4)',
				      'V'=>'退货订单类型(退给供应商（扣原采购发票）-0\二手商品销售（不扣原采购发票）-1)',
					  'W'=>'支票号',
				      'X'=>'备注',
				      'Y'=>'金宝贝退货仓库'		
				);
	foreach ($config_title as $cell_name => $cell_value){
		$sheet->setCellValue ( $cell_name.'1', $cell_value );
	}

	$config_value = array('A'=>'product_id',
					  'B'=>'provider_id',
					  'C'=>'facility_id',
					  'D'=>'goods_item_type',
					  'E'=>'cat_name',
					  'F'=>'goods_name',
					  'G'=>'goods_code',
					  'H'=>'style_code',
					  'I'=>'provider_name',
				      'J'=>'is_new',
				      'K'=>'order_type',
				      'L'=>'purchase_paid_amount',
				      'M'=>'currency',
					  'N' =>'storage_amount',
					  'O' =>'can_request',
				      'P'=>'facility_name',
					  'Q'=>'purchase_paid_amount'	
				);
	$config_default_value = array(
					  'M'=>'RMB',
					  'R'=>'1.17',
				      'U'=>'1'	
				);
	$j = 2;
	foreach ($array_ret_item as $item){
		foreach ($config_value as $cell_name => $cell_value){
			$sheet->setCellValue ( $cell_name.$j, $item[$cell_value]);
		}
		foreach ($config_default_value as $cell_name => $cell_value){
			$sheet->setCellValue ( $cell_name.$j, $cell_value);
		}
		$j++;
	}
 	if (! headers_sent ()) {
		header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header ( 'Content-Disposition: attachment; filename="供应商退货申请（-gt）清单.xlsx"' );
		header ( 'Cache-Control: max-age=0' );
		$output = PHPExcel_IOFactory::createWriter ( $excel, 'Excel2007' );
		$output->setOffice2003Compatibility ( true );
		$output->save ( 'php://output' );
	}
 }
 function supplier_return_get_soap_client(){
    return soap_get_client('purchaseOrderService');
 }
 
 // 批量检查当前商品库存
 function batch_search_goods_storage($barcodes,$original_provider_id,$facility_id,$status_id) {
 	global $db;
    $cond = '';
    if (!empty($original_provider_id)) {
        $cond = 'and ii.provider_id = ' . $original_provider_id ;
    }
    if (!empty($status_id)) {
        $cond = 'and ii.status_id = ' . $status_id ;
    }
    
    $sql = 
       "select ii.batch_sn,p.product_name as order_goods_name, pm.ecs_goods_id as ret_goods_id,pm.ecs_style_id as ret_style_id, 
              ii.facility_id ret_facility_id,concat(ifnull(gs.barcode,g.barcode),'-',if(ii.status_id='INV_STTS_AVAILABLE','良品','不良品')) as goods_status,
              ii.status_id ret_status_id,ii.inventory_item_type_id as goods_item_type, 
              ii.unit_cost as purchase_paid_amount,ii.unit_cost as goods_price, ii.unit_cost as ret_amount,
              'RMB' as currency,ii.product_id,
              '1.17' as goods_rate,ii.status_id as is_new, 'SUPPLIER_RETURN' order_type_id,
              ii.inventory_item_acct_type_id as order_type, ifnull(ii.provider_id,'432') as ret_original_id, 
              ifnull(ii.provider_id,'432') as ret_provider_id,pr.provider_name as ret_provider,
              ifnull(pr.provider_name,'自己库存') as provider_name, f.facility_name,
              ifnull(sum(ii.quantity_on_hand_total),0) as storage_amount
	   from romeo.inventory_item ii 
	   left join ecshop.ecs_provider pr on pr.provider_id = ii.provider_id 
	   inner join romeo.facility f on f.facility_id = ii.facility_id
	   inner join romeo.product_mapping pm on pm.product_id = ii.product_id
	   inner join ecshop.ecs_goods g ON pm.ecs_goods_id = g.goods_id
	   left join ecshop.ecs_goods_style gs ON pm.ecs_style_id = gs.style_id and pm.ecs_goods_id = gs.goods_id and gs.is_delete=0
	   left join romeo.product p on p.product_id = pm.product_id
	   where ii.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED')
	         and ii.quantity_on_hand_total > 0 
	         and ii.facility_id = '{$facility_id}' and ifnull(gs.barcode,g.barcode) ".db_create_in($barcodes)."
	         $cond
	   group by ii.status_id,ii.inventory_item_acct_type_id,ii.unit_cost,ii.provider_id,pm.product_id
    ";
    
//	        pp($sql);
	$keys = $values = array();
    $db -> getAllRefBy($sql,array('goods_status'),$keys,$values);
    $ret_items = $values['goods_status'];
    return $ret_items;
 }
 
?>
