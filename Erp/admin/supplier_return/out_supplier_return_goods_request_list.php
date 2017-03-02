<?php
/*
 * Created on 2011-5-28
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 define('IN_ECS', true);
 
 require('../includes/init.php');
 require_once("../function.php");
 require_once("RomeoApi/lib_inventory.php");
 require_once("RomeoApi/lib_supplier_return.php");
 require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");
 require_once(ROOT_PATH . 'includes/debug/lib_log.php');
 require_once(ROOT_PATH . 'includes/cls_page.php');

//查询仓库权限
check_user_in_facility();
 global $db, $ecs;
 
 //库存状态
 $status_map = array(
 	'INV_STTS_AVAILABLE' => '全新',
    'INV_STTS_USED' => '二手',
    'INV_STTS_DEFECTIVE' => '次品',
 );
 
 //审核状态
 $check_status_map = array(
 	'INIT' => '未审核',
 	'PASS' => '已通过',
 	'DENY' => '已否决',
 );
 $outFacility = $db->getCol("SELECT facility_id FROM romeo.facility WHERE IS_OUT_SHIP = 'Y' ");
$out_facility_list = implode("','",$outFacility);
 $view = $_REQUEST['view'];
 $smarty -> assign('view', $view);
if ('purchase' == $view) {
     admin_priv('cg_supplier_return_request','cg_supplier_return_list','ck_out_facility_supplier_return');
 	 $cg_supplier_return_check = false;
 	 if(check_admin_user_priv($_SESSION['admin_name'], 'cg_supplier_return_check')){
 	 	$cg_supplier_return_check = true;
 	 	$smarty -> assign('cg_supplier_return_check', $cg_supplier_return_check);
 	 }	
 	  if(check_admin_user_priv($_SESSION['admin_name'], 'cg_supplier_return_complete')){
 	 	$cg_supplier_return_complete = true;
 	 	$smarty -> assign('cg_supplier_return_complete', $cg_supplier_return_complete);
 	 }
 } elseif ('facility' == $view) {
 	 admin_priv('ck_supplier_return_request_action','ck_out_facility_supplier_return');
 } else {
 	 die();
 }
 $supRetReqId = $_REQUEST['supRetReqId'];
 $act = $_REQUEST['act'];  
 $csv = $_REQUEST['csv'];  
 
 // 取消操作
 if ('cancle' == $act && !empty($supRetReqId)) { 
    // 直接更新状态
    $status = 'CANCELLATION' ;
     $sql = "SELECT order_id 
			from romeo.supplier_return_request srr
			INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
			inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
			where srr.SUPPLIER_RETURN_ID = {$supRetReqId}";
     $order_id  = $db -> getOne($sql); 
     $sql = "update 
			ecshop.ecs_order_info oi 
			set shipping_status = 7
			where order_id = {$order_id}";
	$db -> query($sql);
	
	$result = cancelOrderInventoryReservation($order_id);
	
	$sql3 ="SELECT oir.status as reserve_status
			from romeo.supplier_return_request srr
			INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
			inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
			left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
			where srr.SUPPLIER_RETURN_ID ={$supRetReqId}";
	$reserve_status = $db -> getOne($sql3);
	if(empty($reserve_status)){	
        update_supplier_return_request_status($supRetReqId, $status,$order_id);
	}
    $act = 'search' ;
 	
 }
 
 // 数据检索
 if ('search' == $act) {
 	 $pageNo = isset($_REQUEST['page']) && is_numeric($_REQUEST['page'])?$_REQUEST['page']:1;
 	 $pageSize = 50;
 	 
 	 if(isset($csv) && !empty($csv)){
 	 	$pageNo = 1;
 	 	$pageSize = 5000;
 	 }
 	 
   
 	 // 查询条件
 	 $goods_name = $_REQUEST['goods_name_select'] ;
 	 $goods_id= $_REQUEST['goods_id'] ;
 	 $style_id= $_REQUEST['style_id'] ;
 	 $input_batch_gt_sn = trim( $_REQUEST['input_batch_gt_sn'] );
     $ret_supplier_name = $_REQUEST['original_provider_select'] ; 
 	 $ret_supplier_id= $_REQUEST['provider_id'] ;
 	 $ret_status= $_REQUEST['status'] ;
 	 $ret_check_status = $_REQUEST['check_status'];
 	 $from_date = $_REQUEST['from_date'];
 	 $to_date = $_REQUEST['to_date'];
     $batch_gt_id_excel = $_REQUEST['batch_gt_id_excel'];
     $request_id_excel = $_REQUEST['request_id_excel'];
     // 检索条件处理下
     if (empty($goods_name)) {
     	 $goods_id = '' ;
     	 $style_id = '' ;
     }
     
     $used_batch_gt_sn = '';
     if ( empty($input_batch_gt_sn)){
     	 $input_batch_gt_sn = '';
     }else{ 
     	$used_batch_gt_sn = $input_batch_gt_sn;
     }
     
    if ( empty($batch_gt_id_excel)){
     	 $batch_gt_id_excel = '';
     }else{ 
     	$used_batch_gt_sn = $batch_gt_id_excel;
     }
    
     
     
     if (empty($ret_supplier_name)) {
     	 $ret_supplier_id = '' ;
     }
     // 转化成PRODUCT_ID
     if (!empty($goods_id)) {
         $productId = getProductId($goods_id, $style_id);	
     }
    
     $error = '';
     if(!empty($used_batch_gt_sn) || !empty($request_id_excel) ){  // 如果输入了 batch_gt_sn 号 
     	 $resultList = array(); // 保存查询结果 
     	 $batch_gt_info = BatchGt::get_batch_gt_info($used_batch_gt_sn);
     	 if(!empty($input_batch_gt_sn) && empty($batch_gt_info)){
     	 	$error .= "批次订单号batch_gt_sn[$input_batch_gt_sn]不存在，请重新输入";
     	 }else{
			  // 按 batch_gt_sn  request_id 查询 
     	   $error .= "按批次号batch_gt_sn [$used_batch_gt_sn]   按B2B订单号 $request_id_excel 查询";
     	   $field_ref = array();
     	   // 得到查询结果并得到order_id 号
     	   if(!empty($batch_gt_info)){
     	   		 $batchResult =  BatchGt::get_supplier_return_list($used_batch_gt_sn,array('order_id','supplierReturnRequestId'), $field_ref);
     	   }else if ($request_id_excel){
     	   		$batchResult = BatchGt::get_supplier_return_list_byRequestId($request_id_excel,array('order_id','supplierReturnRequestId'), $field_ref);
     	   }
     	   $order_ids =  $field_ref['order_id'];
     	   $supplier_return_ids = $field_ref['supplierReturnRequestId'];
     	   
     	     // 获取 订单号对应的 采购订单的 order_sn 
     	   $purchase = getPurchaseOrderSnByReturnOrderId($order_ids);
     	      // 获取 并加入 -c 订单号 
     	   if(isset($purchase) && is_array($purchase)){
     	   	  $item = array();
     	   	  foreach($purchase as $value){
     	   	  	$temp = array(
     	   	  		'purchase_c_order_sn' => $value['purchase_c_order_sn'],
     	   	  		'purchase_c_count' => $value['purchase_c_count'] 
     	   	  	);
     	   	    $item[$value['order_id']][] = $temp;
     	   	  }
     	   	  // 把该数据加入大数据中 
     	   	  foreach($batchResult as &$value){
     	   	  	$order_id = $batchResult['order_id'];
     	   	  	$value['purchaseCOrderSn'] = $item[$order_id];
     	   	  }
     	   }
     	    // -gt 库存    条用函数得到库存 
     	   // 得到新老库存的出库数 把得到的数据加入到大数据中 
	      $gt_news = get_gt_new_deliver_number($supplier_return_ids);
		  foreach($batchResult as &$record){
		  	 $supplier_return_id = $record['supplierReturnRequestId'];
		  	 $record['new_out_num'] = $gt_news[$supplier_return_id];
		  }
		 
		  //  在库存表中查询 unit_cost 
	     $gt_costs = getUnitCostByOrderId($order_ids);
	     if( count($gt_costs) > 0 ){
	     	 $gt_costs_format = array();
	     	 foreach($gt_costs as $cost){
	     		$gt_costs_format[$cost['order_id']] = $cost['unit_cost'];
	     	 }
	     	 foreach($batchResult as &$record){
		  	 	$order_id = $record['order_id'];
		  	 	$record['gt_cost'] = $gt_costs_format[$order_id] - $record['unitPrice'];	     		
		  	 }
	      }

     	   // 重新组织数据格式
     	   $resultList = formateDataByBatchGtsn($batchResult);
     	   
     	 } // 按 batch_gt_sn  request_id 查询  结束 

     }else{    // 没有输入 batch_gt_sn 号 按其他条件查询  
     $userFacilityList = get_user_facility();
     $sql = "select facility_id,facility_name from romeo.facility where is_out_ship = 'Y'";
     $outShipFacilityListSQL = $db->getAll($sql);
     $outShipFacilityList = array();
     foreach($outShipFacilityListSQL as $outShipFacilitySQL){
     	$outShipFacilityList[$outShipFacilitySQL['facility_id']] = $outShipFacilitySQL['facility_name'];
     }
     $facilityList = array_intersect_assoc($userFacilityList,$outShipFacilityList);
     	  // 数据检索条件
	      $request = new stdClass();
          $request -> productId = $productId ;
          $request -> originalSupplierId = $ret_supplier_id;
          $request -> status = $ret_status;
          $request -> facilityId = "'".implode("','",array_keys($facilityList))."'";
          $request -> partyId = $_SESSION['party_id'];
          $request -> checkStatus = $ret_check_status;
		  
	     if (empty($from_date)) {
	       	$startDate =  date("Y-m-d", strtotime("10 days ago"));
	     } else {
	        $startDate = $from_date;	
	     }
	     if (empty($to_date)) {
	        $endDate = date("Y-m-d", strtotime("+1 days")); 
	     } else {
	      	$endDate = $to_date;
	     }
	     $error .=  $startDate." ~~ ".$endDate." 时间段内操作的结果";
	    
		 $countSQl = "select count(*) from romeo.supplier_return_request where 1";
		 if($productId){
		 	$countSQl .= " and product_id = '$productId'";
		 } 
		 if($ret_supplier_id){
		 	$countSQl .= " and original_supplier_id = '$ret_supplier_id'";
		 } 
		 if($ret_status){
		 	$countSQl .= " and status = '$ret_status'";
		 } 
		 if($_SESSION['facility_id']){
		 	$countSQl .= " and facility_id in ('{$out_facility_list}')";
		 } 
		 if($_SESSION['party_id']){
		 	$countSQl .= " and party_id = '".$_SESSION['party_id']."' ";
		 } 
		 if($ret_check_status){
		 	$countSQl .= " and check_status = '$ret_check_status'";
		 }     
		 $countSQl .= " and created_stamp >= '".$startDate."' and created_stamp < '".$endDate."'";
	     $gtCount = $db->getOne($countSQl);
	       
	       // 调用 romeo 服务查询 
	     $requestList = get_supplier_return_request($request, $startDate, $endDate,$pageSize,$pageNo);
         $supplierReturnRequestIds = array();
	     $productIds = array();
		 $facilityIds = array();
		 foreach ( $requestList as $item){
     		$supplierReturnRequestIds[] = $item['supplierReturnRequestId'];
	     	$productIds[$item['supplierReturnRequestId']] = $item['productId'];
	     	$facilityIds[$item['supplierReturnRequestId']] = $item['facilityId'];
		 }
			   
	     $sql="select gt.supplier_return_gt_sn ,gt.supplier_return_id ,oi.order_id
			     	      from  ecshop.ecs_order_info oi 
			     	      left join romeo.supplier_return_request_gt  gt  ON   oi.order_sn = gt.supplier_return_gt_sn  
			     	      where  ".Helper::db_create_in('gt.supplier_return_id',$supplierReturnRequestIds)." and oi.facility_id in ('{$out_facility_list}')";
		 $snsResult = $db->getAll($sql);
			 // 根据 supplierReturnRequestId 组织数据    supplier_return_id => array(数据)
	     $sns_format = array();
		 $order_id_format = array();
		 $order_id_list = array();
		 foreach($snsResult as $value){
			   $sns_format[$value['supplier_return_id']][] = $value['supplier_return_gt_sn'];
			   $order_id_format[$value['supplier_return_id']][] = $value['order_id'];
			   $order_id_list[] = $value['order_id'];
		 }
		 
		         // 根据退货订单的 ecs_order_info 表的 order_id 得到 purchase订单的order_sn 
		 $purchase_order_sn = getPurchaseOrderSnByReturnOrderId($order_id_list);
		 $purchaseCOrderSn_format = array();
		   // 组织 $purchaseCOrderSn 数据 按  order_id 
		 if(count($purchase_order_sn) > 0 ){
		         foreach($purchase_order_sn as $value){
		     	   	 $temp = array(
		     	   	  		'purchase_c_order_sn' => $value['purchase_c_order_sn'],
		     	   	  		'purchase_c_count' => $value['purchase_c_count'] 
		     	   	  	);
		     	   
		     	    $purchaseCOrderSn_format[$value['order_id']][] = $temp;
		     	 }
		  }
		 $sql = " select IFNULL(egs.barcode,eg.barcode) as barcode, pm.product_id 
						from romeo.product_mapping pm
						inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
						left join ecshop.ecs_goods_style egs on egs.goods_id = pm.ecs_goods_id and egs.style_id = pm.ecs_style_id and egs.is_delete=0
						where ".Helper::db_create_in('pm.product_id',$productIds);
		 $barcodeResult = $db->getAll($sql);
			 // 按 product_id 组织 barcode 数据
		 $barcode_format = array();  
		 $barcodes = array();
		 foreach($barcodeResult as $value){
			 	$barcode_format[$value['product_id']] = $value['barcode'];
			 	$barcodes[] = $value['barcode'];
		 }
			  // 获取 barcode 对应的 spec 
		 $sql = "select spec , barcode from ecshop.brand_zhongliang_product WHERE ".Helper::db_create_in('barcode',$barcodes);
		 $specResult = $db->getAll($sql);
			  // 组织 spec  数据   barcode => spec 
		 $spec_format = array();
		 foreach($specResult as $value){
			 	$spec_format[$value['barcode']] = $value['spec'];
		 }	 	 
			//根据 supplierReturnRequestId 获取batch_sn	 
		 $batch_sn_format = array();	 
		 $sql = "select batch_sn,supplier_return_id from romeo.supplier_return_request where ".Helper::db_create_in('supplier_return_id',$supplierReturnRequestIds)." and facility_id in ('{$out_facility_list}') ";
		 $batchsnResult = $db->getAll($sql);
		 foreach($batchsnResult as $value) {
		 	$batch_sn_format[$value['supplier_return_id']] = $value['batch_sn'];
		
		 }		 	 
			  // 仓库名称 
		 $sql="SELECT FACILITY_NAME ,FACILITY_ID FROM romeo.facility where ".Helper::db_create_in('FACILITY_ID',$facilityIds);
		 $facilityNameResult=$db->getAll($sql);
		 $facilityName_format = array();
		 foreach($facilityNameResult as $value){
			  	$facilityName_format[$value['FACILITY_ID']] = $value['FACILITY_NAME'];
		}
	 
	      // 调整价格
		 $gt_cost_result = getUnitCostByOrderId($order_id_list);;
		 $gt_cost_format = array(); // order_id => unit_cost 
		 if( $gt_cost_result && count($gt_cost_result) > 0 ){
			 foreach($gt_cost_result as $value){
			 		$gt_cost_format[$value['order_id']] = $value['unit_cost'];
			 	}
		 }
	     $sql = "select ifnull(gm.batch_gt_sn,'') as batch_gt_sn, gm.order_id
				from  ecshop.ecs_batch_gt_mapping gm WHERE ".Helper::db_create_in('gm.order_id',$order_id_list);
	     	$batch_gt_sn_result = $db->getAll($sql);
	     	$batch_gt_sn_format = array();
	     	
	     	//  batch_gt_sn 组织数据  order_id => batch_gt_sn
	    foreach($batch_gt_sn_result as $value){
	     		$batch_gt_sn_format[$value['order_id']] = $value['batch_gt_sn'];
	    } 
    
	     	   // -gt 库存    条用函数得到库存 
     	   // 得到新老库存的出库数 把得到的数据加入到大数据中 
	   $gt_news = get_gt_new_deliver_number($supplierReturnRequestIds);
		 
	     	 // 把数据加入到大数据中  组织数据 
	   $resultList = array(); // 用于存放数据
	   foreach($requestList as &$item ){
	   	if(!in_array($item['facilityId'],$outFacility)){
	   		continue;
	   	}
	     		$supplierReturnRequestId_one = $item['supplierReturnRequestId'];
	     		$facilityId = $item['facilityId'];
	     		$productId = $item['productId'];
	     		// 加入 -gt 订单号 
	     		$sns_one = $sns_format[$supplierReturnRequestId_one];
	     		if( isset($sns_one) && is_array($sns_one)){
	     			$sn = "";
		     		foreach($sns_one as $gtSn){
		    			$sn .= "'".$gtSn."',";
		    		}
		    		$sn = substr($sn,0,strlen($sn)-1);
		    		$item['supplierReturnGtSn'] =  str_replace("'"," ",$sn);
	     		}
	     		  // 加入商品条码
	            $item['barcode'] = $barcode_format[$productId]; 
	              // 加入箱规 spec
	            $item['spec'] =  $spec_format[$item['barcode']];
	             // 加入货物批次号
	            $item['batch_sn'] = $batch_sn_format[$item['supplierReturnRequestId']];
		        // 加入仓库
	            $item['facilityName'] = $facilityName_format[$facilityId];
	            $tmp_order_id = $order_id_format[$supplierReturnRequestId_one][0];
	              // 调整价格
	            if( count($gt_cost_format)  > 0 ){
	            	 $item['gt_cost'] = $gt_cost_format[$tmp_order_id] -$item['unitPrice'];   
	            }
	            // 批次号 
	            $item['batch_gt_sn'] =   $batch_gt_sn_format[$tmp_order_id];
	     		
	     		// -c 订单号 
	     		$item['purchaseCOrderSn'] = $purchaseCOrderSn_format[$tmp_order_id];
	     		 // 时间格式
	     		$item['createdStamp'] =  date('Y-m-d H:i:s', strtotime($item['createdStamp']));
	     		
	     		$item['new_out_num'] = $gt_news[$supplierReturnRequestId_one];
	     		
	     		$sql4 ="SELECT oir.status as reserve_status
						from romeo.supplier_return_request srr
						INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
						inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
						left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
						where srr.SUPPLIER_RETURN_ID ={$supplierReturnRequestId_one}";
	     		  // 保存每条记录
	     		$item['reserve_status']  = $db -> getOne($sql4);
	     		$resultList[$supplierReturnRequestId_one] = $item;
	  }  
	  		// 把数据添加进大数据结束      	
	     	  // 按 batch_gt_sn 批次号重新组织数据 
	 $format_datas = formateDataByBatchGtsn($resultList);
	 $resultList = $format_datas;
	                        	      	      
	  if(isset($_REQUEST['message'])){
	     	 $smarty->assign('message', $_REQUEST['message']);
	  }
	     
	  $pager = Pager($gtCount, $pageSize, $pageNo);
	  $smarty->assign('pager', $pager);
     }  // 如果没有输入 batch_gt_sn 按 条件查询  结束 
    

 }
 
 if(isset($csv) && !empty($csv)){
 	$party_name = get_party_name();
 	header("Content-type:application/vnd.ms-excel;charset=utf-8");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","{$party_name}批量Gt导出") . ".csv");
    ob_start();
    $header_str = iconv("UTF-8",'GB18030',"批次B2B订单号,商品名,条码,箱规,仓库,库存状态,预出库数,已退数量,退货价格,审核状态,审核人,操作状态,申请时间,Gt订单号,退货原因,申请人\n");
    $file_str = "";
    foreach($resultList as $picihao => $oneList){
    	
    	foreach($oneList['data'] as $req){
    		    	if($req['checkStatus'] == 'PASS'){
    		if($req['status'] == 'CREATED'){
    			$operate_status = '开始退还';
    		}else if($req['status'] == 'EXECUTING'){
    			$operate_status = '已部分退还';
    		}else if($req['status'] == 'COMPLETION'){
    			$operate_status = '已全部退还';
    		}else{
    			$operate_status = '已取消';
    		}
    	}else if($req['checkStatus'] == 'INIT'){
    		if($req['status'] == 'CANCELLATION'){
    			$operate_status = '已取消';
    		}else{
    			$operate_status = '未开始操作';
    		}
    	}else{
    		$operate_status = '未开始操作';
    	}
    	
    	$sn = "";
    	$sns = $req['supplierReturnGtSn'];
    	if(!empty($sns) && is_array($sns)){
    		foreach($sns as $gtSn){
    		$sn .= $gtSn['supplier_return_gt_sn']." ";
    		}
    	}else{
    		$sn = $sns;
    	}
    	$file_str .= '="'.str_replace(",","",$picihao).'"'.",";
    	$file_str .= str_replace(","," ",$req['productName']).",";
    	$file_str .= '="'.str_replace(","," ",$req['barcode']).'"'.",";
    	$file_str .= str_replace(","," ",$req['spec']).",";
    	$file_str .= str_replace(","," ",$req['facilityName']).",";
    	$file_str .= str_replace(","," ",$status_map[$req['statusId']]).",";
    	$file_str .= str_replace(","," ",$req['returnOrderAmount']).",";
    	$file_str .= str_replace(","," ",$req['excutedAmount']).",";
    	$file_str .= str_replace(","," ",$req['unitPrice']).",";
    	$file_str .= str_replace(","," ",$check_status_map[$req['checkStatus']]).",";
    	$file_str .= str_replace(","," ",$req['checkUser']).",";
    	$file_str .= str_replace(","," ",$operate_status).",";
    	$file_str .= str_replace(","," ",$req['createdStamp']).",";
    	$file_str .= str_replace(","," ",$sn).",";
    	$file_str .= str_replace(","," ",$req['note']).",";
    	$file_str .= str_replace(","," ",$req['createdUserByLogin'])."\n";
    	}
    }
    $file_str = iconv("UTF-8",'gbk',$file_str);
    ob_end_clean();
    echo $header_str;
    echo $file_str;
    exit();
 }

 $smarty->assign('view', $view);
 $smarty->assign('error', $error);
 $smarty->assign('input_batch_gt_sn', $input_batch_gt_sn);
 // 保留检索条件
 $smarty->assign('goods_name', $goods_name);
 $smarty->assign('goods_id', $goods_id);
 $smarty->assign('style_id', $style_id);
 
 $smarty->assign('from_date', $from_date);
 $smarty->assign('to_date', $to_date);
 
 $smarty->assign('status', $ret_status);
 $smarty->assign('provider_name', $ret_supplier_name);
 $smarty->assign('provider_id', $ret_supplier_id);
 $smarty->assign('check_status', $ret_check_status);

 $smarty->assign('requestList', $resultList);
 
 $smarty->display('supplier_return/out_supplier_return_goods_request_list.htm');
 
 function get_party_name ($party_id = null){
 	if(empty($party_id)){
 		$party_id = $_SESSION['party_id'];
 	}
 	global $db;
 	$sql = "select name from romeo.party where party_id = '{$party_id}'";
 	$result = $db -> getOne($sql);
 	return $result;
 }
 
 /**
  *    根据 batch_gt_sn 组织数据  数据形式为
  *    batch_gt_sn => array( is_batch,data=>array(),count) 
  */
 function formateDataByBatchGtsn($resultList){
	$format_datas = array();
	if(!is_array($resultList)) return false;
	foreach($resultList as $item) {
		if(empty($item['batch_gt_sn'])) {
	     		$batch_order_sn = $item['supplierReturnRequestId'];
	     		$format_datas[$batch_order_sn]['is_batch'] = false;
	    } else {
	     		$batch_order_sn = $item['batch_gt_sn'];
	     		$format_datas[$batch_order_sn]['is_batch'] = true;
	    }
	    
	   $format_datas[$batch_order_sn]['data'][] = $item;
	   if(isset($format_datas[$batch_order_sn]['count'])) {
	     		$format_datas[$batch_order_sn]['count'] ++;
	     	}else {
	     		$format_datas[$batch_order_sn]['count'] = 1;
	     	}
      }
 	 return $format_datas;
 }
 
 // 根据退货订单的 ecs_order_info 表的 order_id 得到 purchase订单的order_sn 
 function getPurchaseOrderSnByReturnOrderId($order_id){
 	global $db;
 	$c_sql = "SELECT oi2.order_sn AS purchase_c_order_sn, SUM( m.quantity ) AS purchase_c_count , oi1.order_id 
					FROM ecshop.ecs_order_info oi1
					LEFT JOIN romeo.purchase_return_map m ON CONVERT(oi1.order_id USING utf8 ) = m.RETURN_ORDER_ID
					LEFT JOIN ecshop.ecs_order_info oi2 ON oi2.order_id = m.PURCHASE_ORDER_ID
					WHERE " .Helper::db_create_in('oi1.order_id',$order_id)." and oi1.facility_id in ('{$out_facility_list}')
				    GROUP BY oi1.order_id ";
	$c_sns=$db->getAll($c_sql);
	return $c_sns;
 }
 
 // 根据 order_id 在库存表中查询 unit_cost
 function getUnitCostByOrderId($order_ids){
 	 
 	  global $db;
	  $sql = " select ii.unit_cost ,iid.order_id 
				FROM romeo.inventory_item ii  
				inner join  romeo.inventory_item_detail iid on ii.inventory_item_id = iid.inventory_item_id
				where iid.QUANTITY_ON_HAND_DIFF < 0
				and ii.status_id = 'INV_STTS_AVAILABLE'  and ii.facility_id in ('{$out_facility_list}')
				and ".Helper::db_create_in('iid.order_id',$order_ids);
	$gt_costs = $db->getAll($sql);
	return $gt_costs;
 }
 
 class BatchGt{
	public static function get_batch_gt_info($batch_gt_sn) {
		global $db;
		$sql = "SELECT * FROM romeo.supplier_return_request WHERE supplier_return_id  ='{$batch_gt_sn}' AND " . party_sql ( 'party_id' );
		$batch_order_info = $db->getRow ($sql);
		return $batch_order_info;
	}
	
	public static function get_supplier_return_list($batch_gt_sn,$fields =null,&$ref_fields) {
		global $db;
		$sql = "select f.facility_name as facilityName , og.goods_name, p.product_name as productName,  
        ifnull(gs.barcode,g.barcode) as barcode,og.goods_number,
        gt.supplier_return_id ,  r.status_id as statusId,r.supplier_return_id as supplierReturnRequestId,
        r.check_user as checkUser, r.check_status as checkStatus,
        r.unit_price,r.return_order_amount as returnOrderAmount,r.storage_amount as excutedAmount,
        r.original_supplier_id,r.return_supplier_id,r.created_user_by_login,
        r.note,r.unit_price as unitPrice, r.check_status as checkStatus,r.status as status,
        r.created_stamp as createdStamp, r.note as note, r.created_user_by_login as createdUserByLogin,
        r.created_stamp,r.last_update_stamp,  r.batch_sn,
        oi.order_sn as supplierReturnGtSn,oi.order_id,
        bm.batch_gt_sn,boi.created_stamp,p.provider_name,oir.status as reserve_status   
		from romeo.supplier_return_request r    
		INNER JOIN romeo.facility f ON r.facility_id = f.FACILITY_ID    
		inner join romeo.product p on r.product_id = p.product_id 
		LEFT JOIN romeo.product_mapping pm on p.product_id = pm.product_id 
		LEFT JOIN ecshop.ecs_goods  g ON pm.ecs_goods_id = g.goods_id 
		left join ecshop.ecs_goods_style  gs on  gs.goods_id = pm.ecs_goods_id and  gs.style_id = pm.ecs_style_id and gs.is_delete=0 
		LEFT JOIN romeo.supplier_return_request_gt gt ON r.supplier_return_id = gt.supplier_return_id 
		LEFT JOIN ecshop.ecs_order_info oi on  gt.supplier_return_gt_sn = oi.order_sn 
		left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
        LEFT JOIN ecshop.ecs_order_goods og on  og.order_id= oi.order_id 
        left join ecshop.ecs_batch_gt_mapping bm ON bm.order_id = oi.order_id
        left join ecshop.ecs_batch_gt_info boi on boi.batch_gt_sn = bm.batch_gt_sn
		LEFT JOIN romeo.inventory_item_detail iid ON convert(oi.order_id using utf8)=iid.order_id
        left join ecshop.ecs_provider p ON r.return_supplier_id = p.provider_id 
		where r.supplier_return_id = '{$batch_gt_sn}' group by r.supplier_return_id ";
		if( is_array($fields) || is_array($ref_fields)){
			$ref_orders = array();
			$supplier_return_list = $db->getAllRefby($sql,$fields, $ref_fields, $ref_orders, false);
		}else{
			$supplier_return_list = $db->getAll ($sql);
		}
		
		return $supplier_return_list;
	}
	
	public static function get_supplier_return_list_byRequestId($requestId,$fields =null,&$ref_fields) {
		global $db;
		$sql = "select f.facility_name as facilityName ,og.goods_name as productName,
			 ifnull(gs.barcode,g.barcode) as barcode,og.goods_number,zp.spec, 
			gt.supplier_return_id as supplierReturnRequestId, r.status_id as statusId,
			r.supplier_return_id, r.check_user as checkUser, r.check_status as checkStatus,
			 r.unit_price,r.return_order_amount as returnOrderAmount,
			r.storage_amount as excutedAmount, r.original_supplier_id,r.return_supplier_id,
			r.created_user_by_login, r.note,r.unit_price as unitPrice,
			 r.check_status as checkStatus,r.status as status, r.created_stamp as createdStamp,
			 r.note as note, r.created_user_by_login as createdUserByLogin, 
			r.created_stamp,r.last_update_stamp, r.batch_sn, oi.order_sn as supplierReturnGtSn,oi.order_id,
			 bm.batch_gt_sn,boi.created_stamp,p.provider_name,oir.status as reserve_status
			from romeo.supplier_return_request r 
			LEFT JOIN  romeo.supplier_return_request_gt gt   ON r.supplier_return_id = gt.supplier_return_id   
			LEFT JOIN  ecshop.ecs_order_info oi ON  gt.SUPPLIER_RETURN_GT_SN = oi.order_sn
			LEFT JOIN  romeo.order_inv_reserved oir on oir.order_id = oi.order_id		  
			LEFT JOIN  ecshop.ecs_batch_gt_mapping bm ON  oi.order_id = bm.order_id 
			LEFT JOIN ecshop.ecs_batch_gt_info boi on bm.batch_gt_sn = boi.batch_gt_sn
			INNER JOIN  ecshop.ecs_order_goods og ON oi.order_id = og.order_id 
			LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id 
			LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id 
			and og.style_id = gs.style_id and gs.is_delete=0
			LEFT JOIN ecshop.brand_zhongliang_product zp ON  zp.barcode = ifnull(gs.barcode,g.barcode) 
			LEFT JOIN romeo.facility f ON oi.facility_id = f.facility_id  
			LEFT JOIN ecshop.ecs_provider p ON r.return_supplier_id = p.provider_id 
			where r.supplier_return_id = '{$requestId}' and r.facility_id in ('{$out_facility_list}') ";
		if( is_array($fields) || is_array($ref_fields)){
			$ref_orders = array();
			$supplier_return_list = $db->getAllRefby($sql,$fields, $ref_fields, $ref_orders, false);
		}else{
			$supplier_return_list = $db->getAll ($sql);
		}
		return $supplier_return_list;
	}
	 	
}


 class Helper{
 	
	/**
	 *  在查询数据库时 构造 field_name IN ('value1','value2')
	 *  $field_name 字段名 
	 *  $value_list 值 array 或者是 逗号分隔的字符串 
	 */
	public static function db_create_in($field_name = '',$value_list )
	{
	    if (empty($value_list))
	    {
	        return $field_name . " IN ('') ";
	    }
	    else
	    {
	        if (!is_array($value_list))
	        {
	            $value_list = explode(',', $value_list);
	        }
	        $value_list = array_unique($value_list); // 去除重复数值 
	        $value_list_tmp = '';
	        foreach ($value_list AS $item)
	        {
	            $item = trim($item);
	            if ($item !== '')
	            {   
	            	if( $item[0] =="'"){
	            		$value_list_tmp .= $value_list_tmp ? ",$item" : "$item";  // in 的第一个 值 不需要逗号
	            	}else{
	            		$value_list_tmp .= $value_list_tmp ? ",'$item'" : "'$item'";  // in 的第一个 值 不需要逗号
	            	}
	            }
	        }
	        if (empty($value_list_tmp))
	        {
	            return $field_name . " IN ('') ";
	        }
	        else
	        {
	            return $field_name . ' IN (' . $value_list_tmp . ') ';
	        }
	    }
	}
	
}

?>
