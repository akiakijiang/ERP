<?php
/*
 * Created on 2011-5-27
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 *
 *
 * romeo 里面的 SupplierReturnRequest的商品只能是一种
 */
 define('IN_ECS', true);
 require('../includes/init.php');
 // require(ROOT_PATH . 'admin/function.php');
 require_once (ROOT_PATH . 'admin/includes/lib_order.php');
 require_once(ROOT_PATH . 'includes/lib_order.php');
 require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
 require_once (ROOT_PATH . 'includes/debug/lib_log.php');
 require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
 

 $purchase_order_sn = $_REQUEST['purchase_order_sn'] ;
 $storage_count = $_REQUEST['storage_count'];
 
 // 生成退货预定请求
 if($_REQUEST['act'] == 'submit'){
 	admin_priv('cg_supplier_return_request');
    
    $supplierReturnRequestId = $_REQUEST['supplierReturnRequestId'];
    $product_id = $_REQUEST['product_id'];
    $goods_price = floatval($_REQUEST['goods_price']);
    $tax_rate = floatval($_REQUEST['tax_rate']);
    $order_type_id = $_REQUEST['order_type_id'];
    $to_vendor_id = intval($_REQUEST['to_vendor_id']);
    $original_provider_id = intval($_REQUEST['original_provider_id']);
    $purchase_paid_type = $_REQUEST['purchase_paid_type'];
    $cheque = $_REQUEST['cheque'];
    $remark = $_REQUEST['remark'];
    $facility_id = $_REQUEST['facility_id'];
    $batch_sn = $_REQUEST['batch_sn'];
    $item_type = $_REQUEST['item_type'];
    $serial_number = $_REQUEST['serial_number'];

    // 需要输入串号的退货
    $ret_req = new stdClass();
    $return_items = array() ;
    if ($item_type == 'SERIALIZED') {
    	// 
    	$ret_req -> inventoryItemTypeId = 'SERIALIZED' ;
        
        $serial_number = str_replace('\\', '', $serial_number);
        $serialList = json_decode($serial_number, true);
        foreach($serialList as $serial){
        	
        	$item = new stdClass();
    	          $item -> amount = 0 ; 
    	          $item -> serialNumber = $serial['erp_goods_sn'] ;
    	          $item -> inSn = $serial['in_sn'] ;
    	          $item -> supplierReturnOrderSn = null ;
        	
        	$return_items[] = $item ;
        }     	
    	
    	$return_amount = count($serialList) ;
    }
    // 配件等非串号退货
    if ($item_type == 'NON-SERIALIZED') {
    	$ret_req -> inventoryItemTypeId = 'NON-SERIALIZED' ;
    	// 退货数量
    	$return_amount = intval($_REQUEST['num_total']);
    	if($return_amount < 0){
    		 sys_msg('请输入正确的退货数量');
    		 return ;
    	}
    	
     	if($return_amount > $storage_count){
    		sys_msg('该采购订单退货数量大于目前库存的数量');
    		return ;
    	}
    	
    	$item = new stdClass();
    	    $item -> amount = 0 ; 
    	    $item -> serialNumber = null ;
    	    $item -> inSn = null ;
    	    $item -> supplierReturnOrderSn = null ;
    	    
    	$return_items[] = $item ;
    }
    

     // $ret_req = new stdClass();
        $ret_req -> supplierReturnRequestId = $supplierReturnRequestId;
    	$ret_req -> purchaseOrderSn = $purchase_order_sn ;
        $ret_req -> taxRate = $tax_rate;
        $ret_req -> storageAmount = $storage_count;
        $ret_req -> returnOrderAmount = $return_amount;
        $ret_req -> orderTypeId = $order_type_id;
        $ret_req -> returnSupplierId = $to_vendor_id;
        $ret_req -> originalSupplierId = $original_provider_id;
        $ret_req -> paymentTypeId = $purchase_paid_type;
        $ret_req -> checkNo = $cheque;
        $ret_req -> note = $remark;
        $ret_req -> partyId = $_SESSION['party_id'];
        $ret_req -> batchSn = $batch_sn;
        $ret_req -> facilityId = $facility_id;
        $ret_req -> containerId = '';
        $ret_req -> productId = $product_id ;
    	// $ret_req -> productName = $product_name ;
    	$ret_req -> unitPrice = $goods_price ;
        $ret_req -> createdUserByLogin = $_SESSION['admin_name'];
	  
     // 生成退货订单_预申请
	 $result = create_supplier_return_request($ret_req, $return_items);
	 
	 header('location:supplier_return_goods_request_list.php?view=purchase&act=search&supRetReqId='.$result->return );
	 exit();
 }
 
 // 操作退货
 if ($_REQUEST['act'] == 'supplier_return') {
	$message = '';
//	$lock_name = "party_{$_SESSION['party_id']}";
//    $lock_file_name = get_file_lock_path($lock_name, 'pick');
//    $lock_file_point = fopen($lock_file_name, "w+");
//    $would_block = false;
//    if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
    	$supRetReqId = $_REQUEST['hid_supRetReqId']; 
    	
		if(empty($supRetReqId)){
			$message .=  'supRetReqId为空';
		}else if(is_enough($supRetReqId)){
			$message .=  '已经有足够的商品退货了';
		}else{
			// 根据requestId获取预定记录
			$ret_req = new stdClass();
			$ret_req -> supplierReturnRequestId = $supRetReqId;
			$ret_req -> partyId = $_SESSION['party_id'];
		
			$supplier_return_request = get_supplier_return_request($ret_req); 
			if(empty($supplier_return_request)){
				$message .=  'get_supplier_return_request调用失败';
			}else{
				$supRetReq = $supplier_return_request[$supRetReqId];	
				//进行串号处理
				if ($supRetReq['inventoryItemTypeId'] == 'SERIALIZED'){
				    // 串号商品
					$serialNumbers = $_REQUEST['hid_serial_number'];
					$serialNumbers = str_replace('\\', '', $serialNumbers);
					$serialList = json_decode($serialNumbers, true);
					$return_serials = array();
					foreach($serialList as $serials){
						$return_serials[] = $serials['erp_goods_sn'] ;
					}
					$supRetReqItems = get_supplier_return_request_item($supRetReq['supplierReturnRequestId']);
		        
					// 提取指定退还
					$request_serials = array();
					foreach($supRetReqItems as $item){
			            $request_serials[] = $item['serialNumber'];
					}
					$is_right = true;
					foreach($return_serials as $item){
			            if(!in_array($item, $request_serials)){
			            	$message .= $item.'不是指定的退还串号.';
			            	$is_right = false;
			            }
					}
					if($is_right){
						// 库存出库
			        	$message .= deliver_supplier_return_order_inventory_sn ($supRetReq,  $return_serials) ;
					}
				}else if($supRetReq['inventoryItemTypeId'] == 'NON-SERIALIZED'){
					// 批量出库逻辑
					$out_num = $_POST['returnOrderAmount'];
					$message .= deliver_supplier_return_order_inventory ($supRetReq, $out_num);
				}
			}
		}
//    	flock($lock_file_point, LOCK_UN);
//		fclose($lock_file_point);
//    }else{
//    	fclose($lock_file_point);
//		$message .= "同业务组有人正在完结,或者正在，请稍后";
//    }
	header('location:supplier_return_goods_request_list.php?view=facility&act=search&supRetReqId='.$supRetReqId .'&message='.$message);
}

//判断是否已经执行了
function is_enough($supRetReqId){
	global $db;
	$sql = "
	   select rr.storage_amount,sum(og.goods_number) excute_number
	   from romeo.supplier_return_request rr
       inner join romeo.supplier_return_request_gt rrg on rrg.supplier_return_id = rr.supplier_return_id
       inner join ecshop.ecs_order_info oi on oi.order_sn = rrg.supplier_return_gt_sn 
	   inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id
	   where rr.supplier_return_id = '{$supRetReqId}' and oi.order_id is not null 
	   group by rr.supplier_return_id having excute_number <= rr.storage_amount
	";
	$result = $db->getRow($sql);
	//如果非空的话，证明已经有足够的数量被执行了
	if(!empty($result)){
		return true;
	}else{
		return false;
	}
}

?>
