<?php
/*
 * Created on 2011-8-27
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 define('IN_ECS', true);
 
 require_once('../includes/init.php');
 require_once('../function.php');
 require_once(ROOT_PATH . 'includes/cls_json.php');
 require_once("RomeoApi/lib_inventory.php");
 require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
 require_once (ROOT_PATH . 'includes/debug/lib_log.php');
 require_once(ROOT_PATH.'admin/supplier_return/orderGoodsAmountForGt.php');
 

 QLog::log('in'); 
 $request = $_REQUEST['request'];
 if (!empty($request) && $request == 'ajax'){
 	$json = new JSON;
 	$act = $_REQUEST['act'];
 	switch ($act) {
 		case 'search_goods' :
  	        $limit = 40 ;   // 每次最大显示40行
 		    require_once('../function.php');
            print $json->encode(get_goods_list_like($_POST['q'], $limit));
              		
 		    break ;
 		case 'search_providers':
 		    $limit = 40 ;
 		    require_once('../function.php');
            print $json->encode(get_providers_list_like($_POST['q'], $limit));
            
            break ;
        case 'search_goods_storage' :
         	// 检查当前商品库存
            $array_ret_item = getSearchResult(); 
            print $json->encode($array_ret_item);
            break ;
        case 'search_serialized_goods' : 
            // 检查当前商品库存
 		    $order_goods_id = $_REQUEST['goods_id'] ;
            $order_style_id = $_REQUEST['style_id'] ;
            $original_provider_id = $_REQUEST['original_provider_id'];
            $facility_id = $_REQUEST['facility_id'] ;
            $status_id = $_REQUEST['status_id'];
            $purchase_paid_amount = $_REQUEST['purchase_paid_amount'] ;
            $product_id = getProductId($order_goods_id,$order_style_id);
            $serial_goods = get_serialized_goods_drop_used($facility_id, $original_provider_id, $status_id , $purchase_paid_amount, $product_id);
            print $json -> encode($serial_goods);
            break ;  
        case 'check_repeat_item' :
        	//select_Return_Goods
            $goods_id = $_REQUEST['goods_id'] ;
            $style_id = $_REQUEST['style_id'] ;
            $original_provider_id = $_REQUEST['original_provider_id'];
            $facility_id = $_REQUEST['facility_id'] ;
            $status_id = $_REQUEST['status_id'];
            $purchase_paid_amount = $_REQUEST['purchase_paid_amount'] ;
            
            $productId = getProductId($goods_id, $style_id);
            $status_map = array('NEW' => 'INV_STTS_AVAILABLE', 'SECOND_HAND' => 'INV_STTS_USED', 'DISCARD' => 'INV_STTS_DEFECTIVE') ; 
            
            $sql = "select supplier_return_id, status, check_status
                       from romeo.supplier_return_request
                       where party_id = '%s' and product_id = '%s' and facility_id = '%s' 
                         and original_supplier_id = '%s' and status_id = '%s' and purchase_unit_price = '%s' 
                         and status not in ('CANCELLATION', 'COMPLETION') 
                         and check_status in ('INIT','PASS') limit 1 " ;
                         
            $return_list = $db -> getRow(sprintf($sql,
                                                          $_SESSION['party_id'], $productId, $facility_id, 
                                                          $original_provider_id, $status_map[$status_id], $purchase_paid_amount
                                        )) ;

            $result = array('supplierReturnId' => '',  'mayApply' => false) ;
            if (empty($return_list)) {
            	// 没有记录
            	$result['supplierReturnId'] = '' ;
            	$result['mayApply'] = true ;
            	$result['mayCheck'] = false;
            } elseif ($return_list['status'] == 'CREATED' && $return_list['check_status'] == 'PASS') {
            	// 已经有申请过， 但还没有开始操作退仓
            	$result['supplierReturnId'] = $return_list['supplier_return_id'] ;
            	$result['mayApply'] = true ;
            	$result['mayCheck'] = true;
            } elseif ($return_list['status'] == 'CREATED' && $return_list['check_status'] == 'INIT') {
            	$result['supplierReturnId'] = $return_list['supplier_return_id'] ;
            	$result['mayApply'] = true ;
            	$result['mayCheck'] = false;
            } elseif ($return_list['status'] == 'EXECUTING') {
            	// 已经操作过， 但还没有完  要操作完的
            	$result['supplierReturnId'] = $return_list['supplier_return_id'] ;
            	$result['mayApply'] = false ;
            	$result['mayCheck'] = true;
            }
            print $json -> encode($result);
            break ;
 	}
	exit;
 }
 $act = $_POST['act'];
 $sys_message = '';
 if ('create_item' == $act) {
 	 
 	// 新建一条退货申请记录 OR 更新退货申请记录
 	$obj_data = new stdClass();
 	$obj_data->supplierReturnRequestId = $_POST['supplierReturnRequestId'];
 	$obj_data->original_provider_id = $_POST['ret_original_id'] ;
 	$obj_data->facility_id = $_POST['ret_facility_id'] ;
 	$obj_data->status_id = $_POST['ret_status_id'] ;
 	$obj_data->goods_item_type = $_POST['goods_item_type'] ;
 	$obj_data->ret_provider_id = $_POST['ret_provider_id'] ;
 	$obj_data->order_type_id = $_POST['order_type_id'] ;
 	$obj_data->goods_price = $_POST['goods_price'] ;
 	$obj_data->currency = $_POST['currency'] ;
 	$obj_data->purchase_unit_price = $_POST['purchase_paid_amount'] ;
 	$obj_data->tax_rate = $_POST['goods_rate'] ;
 	$obj_data->ret_amount = $_POST['ret_amount'] ;
 	$obj_data->purchase_paid_type = $_POST['purchase_paid_type'] ;
 	$obj_data->remark = $_POST['remark'] ;
 	$obj_data->chequeNo = $_POST['cheque'];
 	$obj_data->serial_number = $_POST['serial_number'];
 	$obj_data->serial_number = str_replace('\\', '', $obj_data->serial_number);
 	$obj_data->fchr_warehouse_id = $_POST['fchr_warehouse_id'];
 	$obj_data->productId = getProductId($_POST['ret_goods_id'], $_POST['ret_style_id']);
 	$obj_data->ret_goods_id = $_POST['ret_goods_id'];
 	
 	QLog::log("create_supplier_return_item :  productId:".$obj_data->productId." facility_id:".$obj_data->facility_id);
 	
 	$sql = "select AVAILABLE_TO_RESERVED from romeo.inventory_summary where  product_id = '{$obj_data->productId}' and facility_id = '{$obj_data->facility_id}' 
             and  status_id = '{$obj_data->status_id}';
	        ";
 	$atp = $db -> getOne($sql);
 	if($atp < $obj_data->ret_amount ){
 	  $sys_message .=" 申请失败 申请量大于可预订量  库存总表可预定量为：".$atp;
 	}
 	else{
      $sys_message .="【". apply_ret_req($obj_data)."】  ";
 	}
 	
 	sys_msg("{$sys_message}似乎大事已成，准备跳转",0,array(array('href'=>'./supplier_return_goods_request.php')),true);
 } 
 $act = $_REQUEST['act'];
 if ('realTimeReserveNum' == $act) {
 	    set_include_path(ROOT_PATH.'admin/includes/Classes/');
        require_once ('PHPExcel.php');
        require_once ('PHPExcel/IOFactory.php');
	       	       
        $filename = "业务组可用库存清单";
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);
        $sheet = $excel->getActiveSheet();
        $sheet->setCellValue('A1', "erp专用id");
        $sheet->setCellValue('B1', "商品名称");
        $sheet->setCellValue('C1', "商品类型");
        $sheet->setCellValue('D1', "商家编码");
        $sheet->setCellValue('E1', "库存状态");
        $sheet->setCellValue('F1', "批次号");
        $sheet->setCellValue('G1', "税率");
        $sheet->setCellValue('H1', "货币");
        $sheet->setCellValue('I1', "供应商");        
        $sheet->setCellValue('J1', "商品条码");
        $sheet->setCellValue('K1', "仓库");
        $sheet->setCellValue('L1', "仓库编码");
        $sheet->setCellValue('M1', "供应商条码");
        $sheet->setCellValue('N1', "库存数量");
        $sheet->setCellValue('O1', "可申请数量");
        $sheet->setCellValue('P1', "业务组编码");
        $sheet->setCellValue('Q1', "单价");
        $sheet->setCellValue('R1', "分类");
        
        $array_ret_item = getSearchInventoryResult(); 
        $i=2;
        
	     foreach ($array_ret_item as $item) {
	     	$sheet->setCellValue("A{$i}", $item['product_id']);
	        $sheet->setCellValue("B{$i}", $item['goods_name']);
	        $sheet->setCellValue("C{$i}", $item['goods_item_type']);
	        $sheet->setCellValue("D{$i}", $item['goods_id']);
	        if($item['is_new'] == 'INV_STTS_AVAILABLE'){
	        	$sheet->setCellValue("E{$i}", "全新");
	        }else{
	        	$sheet->setCellValue("E{$i}", "二手");
	        }	        
	        $sheet->setCellValue("F{$i}", $item['batch_sn']);
	        $sheet->setCellValue("G{$i}", $item['goods_rate']);
	        $sheet->setCellValue("H{$i}", $item['currency']);
	        $sheet->setCellValue("I{$i}", $item['provider_name']);
	        $sheet->setCellValue("J{$i}", $item['barcode']);
	        $sheet->setCellValue("K{$i}", $item['facility_name']);
	        $sheet->setCellValue("L{$i}", $item['facility_id']);
	        $sheet->setCellValue("M{$i}", $item['provider_id']);
	        $sheet->setCellValue("N{$i}", $item['storage_amount']);
	        $sheet->setCellValue("O{$i}", $item['can_request']);
	        $sheet->setCellValue("P{$i}", $item['party_id']);
	        $sheet->setCellValue("Q{$i}", $item['purchase_paid_amount']);
	        $sheet->setCellValue("R{$i}", $item['cat_name']);
	        $i++;
	     }
        
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        } 
 } 
 
 $act = $_REQUEST['act'];
 if ('export_goods_storage' == $act) {    
    $status_map = array('INV_STTS_AVAILABLE' => '全新', 'INV_STTS_USED' => '	二手', 'INV_STTS_DEFECTIVE' => '次品') ;
    $ret_items = getSearchResult();
 	if (!empty($ret_items)) {
         $array_ret_items = array();
         //将搜索出来的汇总下
         foreach($ret_items as $item){
            if (!array_key_exists($item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id'].$item['product_id'].$item['facility_id'],$array_ret_items)){
            	$item['can_request'] = $item['can_request'];
            	$array_ret_items[$item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id'].$item['product_id'].$item['facility_id']] = $item;
            }else{
            	$array_ret_items[$item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id'].$item['product_id'].$item['facility_id']]['storage_amount'] += $item['storage_amount'];
            	$array_ret_items[$item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id'].$item['product_id'].$item['facility_id']]['can_request'] += $item['can_request'];
            }
         }
         foreach($array_ret_items as $array_item){
            //是否申请过-gt  
			$sql = "select supplier_return_id
                       from romeo.supplier_return_request
                       where party_id = '%s' and product_id = '%s' and facility_id = '%s' 
                         and original_supplier_id = '%s' and status_id = '%s' and purchase_unit_price = '%s' 
                         and status not in ('CANCELLATION', 'COMPLETION') 
                         and check_status in ('INIT','PASS') limit 1 " ;
            $return_list = $db -> getRow(sprintf($sql,
                                                          $_SESSION['party_id'], $array_item['product_id'], $array_item['facility_id'], 
                                                          $array_item['provider_id'], $array_item['is_new'], $array_item['purchase_paid_amount']
                                        )) ;
          	if (empty($return_list)) {
 				//true;
 				$array_item['is_new'] = $status_map[$array_item['is_new']];
            	$array_item['goods_item_type'] = $array_item['is_serial'];
            	$array_ret_item[] = $array_item;
		 	}else if($return_list['status'] == 'EXECUTING' && $temp_party_id != '65574'){
		 		continue;
		 	}else if($return_list['status'] == 'CREATED' && $return_list['check_status'] == 'PASS' && $temp_party_id != '65574'){
		 		continue;
		 	}else if($return_list['status'] == 'CREATED' && $return_list['check_status'] == 'INIT' && $temp_party_id != '65574'){
		 		continue;
		 	}else{
		 		$array_item['is_new'] = $status_map[$array_item['is_new']];
            	$array_item['goods_item_type'] = $array_item['is_serial'];
            	$array_ret_item[] = $array_item;
		 	}
    	}
    }
	export_supplier_return_list_excel_template($array_ret_item);
	exit();
 }
			
 // 若组织是金宝贝，添加退货到金宝贝仓库的操作
 if('65574' == $_SESSION['party_id']){
 	$sql="select fchrWhName,fchrWarehouseID 
 	      from ecshop.brand_gymboree_warehouse";
 	$fchrWareHouses=$db->getAll($sql);
 	foreach($fchrWareHouses as $item){
 		$fchrWareHouse[$item['fchrWarehouseID']]=$item['fchrWhName'];
 	}
 	$smarty->assign('fchrWareHouse', $fchrWareHouse);
 	$smarty->assign('partyId',$_SESSION['party_id']);
 }
 
 // 仓库操作退货编辑
 $act = $_REQUEST['act'];
 $supRetReqId = trim($_REQUEST['requestId']);
 if (!empty($supRetReqId)){
     // 根据ID 取得记录值
 	 $request = new stdClass();
     $request -> supplierReturnRequestId = $supRetReqId;
     $request -> partyId = $_SESSION['party_id'];
 	
     $supplier_return_request = get_supplier_return_request($request);
     $supRetReq = $supplier_return_request[$supRetReqId]; 
        
     // 预请求明细
     $supplier_return_request_items = get_supplier_return_request_item($supRetReqId);
     $supRetReqItems = array();
     foreach($supplier_return_request_items as $key => $item){
         $supRetReqItems[$item['serialNumber']] = $item ;
     }
    
     if ('return' == $act) {
     //    admin_priv('ck_supplier_return_request_action');
        // 订单类型
         if ($supRetReq['orderTypeId'] == 'SUPPLIER_SALE' && is_oversea_sales()) {
             $supRetReq['orderTypeId'] = '销售给供应商（不扣原采购发票）';
         }elseif($supRetReq['orderTypeId'] == 'SUPPLIER_SALE' && !is_oversea_sales()){
         	 $supRetReq['orderTypeId'] = '二手商品销售（不扣原采购发票）';
         }elseif ($supRetReq['orderTypeId'] == 'SUPPLIER_RETURN') {
             $supRetReq['orderTypeId'] = '退给供应商（扣原采购发票）';
         } else {
             $supRetReq['orderTypeId'] = '未知' ;
         }
 
         // 采购供应商列表
         $providers = getProviders();
         $supRetReq['returnSupplierId'] = $providers[$supRetReq['returnSupplierId']]['provider_name']; 
         $supRetReq['originalSupplierId'] = $providers[$supRetReq['originalSupplierId']]['provider_name']; 
         $supRetReq['returnOrderAmount'] = $supRetReq['returnOrderAmount'] - $supRetReq['excutedAmount'];
         

         $smarty->assign('supRetReq', $supRetReq);
         $smarty->assign('supRetReqItems', $supRetReqItems);
        
         $smarty->display('supplier_return/supplier_return_goods_edit.htm');
         exit();
     }elseif('returndt' == $act){
     	 if ($supRetReq['orderTypeId'] == 'SUPPLIER_SALE' && is_oversea_sales()) {
             $supRetReq['orderTypeId'] = '销售给供应商（不扣原采购发票）';
         }elseif($supRetReq['orderTypeId'] == 'SUPPLIER_SALE' && !is_oversea_sales()){
         	 $supRetReq['orderTypeId'] = '二手商品销售（不扣原采购发票）';
         }elseif ($supRetReq['orderTypeId'] == 'SUPPLIER_RETURN') {
             $supRetReq['orderTypeId'] = '退给供应商（扣原采购发票）';
         } else {
             $supRetReq['orderTypeId'] = '未知' ;
         }
 
         // 采购供应商列表
         $providers = getProviders();
         $supRetReq['returnSupplierId'] = $providers[$supRetReq['returnSupplierId']]['provider_name']; 
         $supRetReq['originalSupplierId'] = $providers[$supRetReq['originalSupplierId']]['provider_name']; 
         $supRetReq['returnOrderAmount'] = $supRetReq['returnOrderAmount'] - $supRetReq['excutedAmount'];
         

         $smarty->assign('supRetReq', $supRetReq);
         $smarty->assign('supRetReqItems', $supRetReqItems);
        
         $smarty->display('supplier_return/supplier_dt_goods_edit.htm');
         exit();
     }elseif ('edit' == $act){
      //   admin_priv('cg_supplier_return_request');
         $status_map = array('INV_STTS_AVAILABLE' => 'NEW', 'INV_STTS_USED' => 'SECOND_HAND', 'INV_STTS_DEFECTIVE' => 'DISCARD') ;    
         // 仓库
         $facilities = array_intersect_assoc(get_available_facility(),get_user_facility());
         // 供应商
         $providers = getProviders();
         $origialProviderName = $providers[$supRetReq['originalSupplierId']]['provider_name'] ;
         $returnProviderName = $providers[$supRetReq['returnSupplierId']]['provider_name'] ;
         // 商品名称
         $productMapping = getGoodsIdStyleIdByProductId($supRetReq['productId']) ;
         
         // 退款方式
         $purchasePaidTypes = getPurchasePaidTypes();
 
         // 重新检索商品库存
         //搜索库存以新库存为准
         $product_id = getProductId($productMapping['goods_id'],$productMapping['style_id']);
         $sql = "
		       select ifnull(og.goods_name,p.product_name) as goods_name, ifnull(og.goods_id,pm.ecs_goods_id) as goods_id, 
		              ifnull(og.style_id,pm.ecs_style_id) as style_id, ii.unit_cost as purchase_paid_amount, 
		              ii.status_id as is_new, ii.inventory_item_acct_type_id as order_type, ifnull(ii.provider_id,'432') as provider_id, ifnull(pr.provider_name,'自己库存') as provider_name,
		              f.facility_name, ii.quantity_on_hand_total as storage_amount
		       from romeo.inventory_item_detail iid
			   inner join romeo.inventory_item ii on ii.inventory_item_id = iid.INVENTORY_ITEM_ID
			   left join ecshop.ecs_order_goods og on og.order_id = convert(iid.order_id,unsigned)
			   left join ecshop.ecs_provider pr on pr.provider_id = ii.provider_id
			   inner join romeo.facility f on f.facility_id = ii.facility_id
			   left join romeo.product_mapping pm on pm.product_id = ii.product_id
               left join romeo.product p on p.product_id = pm.product_id
			   where iid.quantity_on_hand_diff > 0 and ii.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED','INV_STTS_DEFECTIVE')
			         and ii.quantity_on_hand_total > 0 
			         and ii.facility_id = '{$supRetReq['facilityId']}' and ii.product_id = '{$product_id}'
			         and ii.unit_cost = '{$supRetReq['purchaseUnitPrice']}' and ii.status_id = '{$supRetReq['statusId']}'
			   group by iid.inventory_item_id, ii.product_id, ii.unit_cost, ii.status_id, inventory_item_type_id
	        ";
         $ret_items = $db -> getAll($sql);
         
         $storageAmount = 0;
 		 if (!empty($ret_items)) {
         	foreach($ret_items as $item){
         		$storageAmount += $item['storage_amount'];
         	}
         }

         // 检索已经勾选过的出库串号
         $storage_erps = array() ;
         if ('SERIALIZED' == $supRetReq['inventoryItemTypeId']) {
			$sql = "
	            select distinct ii.serial_number as erp_goods_sn
				from romeo.inventory_item ii
				where ii.quantity_on_hand_total > 0 
	      		and ii.facility_id = '%s' and e.provider_id = %d and ii.status_id = '%s'
	   			and ii.inventory_item_type_id = 'SERIALIZED' and ii.unit_cost = '%s'
	     		and ii.product_id = '%s' 
            ";
			
			$serials_in_erps = $db->getAll ( sprintf ( $sql, $supRetReq ['facilityId'], intval ( $supRetReq ['originalSupplierId'] ), $status_map [$supRetReq ['statusId']], $supRetReq ['purchaseUnitPrice'], $product_id ) );
			
			// 
			foreach ( $serials_in_erps as $erp ) {
				$storage_erps [$erp ['erp_goods_sn']] = $erp;
				$storage_erps [$erp ['erp_goods_sn']] ['checked'] = false;
				if (array_key_exists ( $erp ['erp_goods_sn'], $supRetReqItems )) {
					$storage_erps [$erp ['erp_goods_sn']] ['checked'] = true;
				}
			}
         	 
         	 
         }
         
         
         $smarty->assign('productMapping', $productMapping);
         $smarty->assign('facilityName', $facilities[$supRetReq['facilityId']]);
         $smarty->assign('returnProviderName', $returnProviderName);
         $smarty->assign('origialProviderName', $origialProviderName);
         $smarty->assign('purchasePaidTypes', $purchasePaidTypes);
         $smarty->assign('is_oversea_sales', is_oversea_sales($_SESSION['party_id']));
         
         // 还有库存数量
         $smarty->assign('storageAmount', $storageAmount);
         
         $smarty->assign('supRetReq', $supRetReq);
         $smarty->assign('storage_erps', $storage_erps);
         $smarty->display('supplier_return/supplier_return_goods_editor_request.htm');
         exit();
     }else if('update' == $act){
     	$check_status = $_REQUEST['status'];

     	if($check_status == 'PASS') {
     		 $message = create_supplier_return_order ($supRetReqId, $_SESSION['admin_name']);
     	} 
     	else if ($check_status == 'DENY') {
     		    		
     		$sql = "SELECT order_id 
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					where srr.SUPPLIER_RETURN_ID = {$supRetReqId}";
            $order_id  = $db -> getOne($sql); 
	
	        $result = cancelOrderInventoryReservation($order_id);
	  
	        $sql3 ="SELECT oir.status as reserve_status,dc_order_id
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					left join ecshop.supplier_transfer_mapping stm on stm.dt_order_id = oi.order_id
					left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
					where srr.SUPPLIER_RETURN_ID ={$supRetReqId}";
	       $item_info = $db -> getRow($sql3);
	       if(empty($item_info['reserve_status'])){	    		
	     		 $sql = "update romeo.supplier_return_request 
	     	            set check_status = '{$check_status}',check_user = '{$_SESSION['admin_name']}'
	     	            where supplier_return_id = '{$supRetReqId}'
	     	       ";
	     	     $db -> query($sql);
	       }
	       if(!empty($item_info['dc_order_id'])){
		
				$sql = "UPDATE ecshop.ecs_batch_order_mapping SET is_cancelled = 'Y' where order_id = '{$item_info['dc_order_id']}' limit 1";
				$db->query($sql);
				$sql = "UPDATE romeo.purchase_order_info SET cancel_time = now() where order_id = '{$item_info['dc_order_id']}' limit 1";
				$db->query($sql);
				$sql = "SELECT om.batch_order_id from ecshop.ecs_batch_order_mapping om where om.order_id = {$item_info['dc_order_id']} limit 1";
				$batch_order_id = $db->getOne($sql);
				$sql = "UPDATE ecshop.ecs_batch_order_info set is_cancelled = 'Y' where batch_order_id = {$batch_order_id} limit 1";
				$db->query($sql);
		   }
     	}
     	if(isset($_REQUEST['outShip']) && $_REQUEST['outShip']=1){
     		header("Location: out_supplier_return_goods_request_list.php?view=purchase&act=search&message=".urlencode($message)); 
     	}else{
     		header("Location: supplier_return_goods_request_list.php?view=purchase&act=search&message=".urlencode($message)); 
     	}
        
        exit();
     }else if('updatedt' == $act){
     	$check_status = $_REQUEST['status'];

     	if($check_status == 'PASS') {
     		 $message = create_supplier_return_order ($supRetReqId, $_SESSION['admin_name']);
     	} 
     	else if ($check_status == 'DENY') {
     		    		
     		$sql = "SELECT order_id 
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					where srr.SUPPLIER_RETURN_ID = {$supRetReqId}";
            $order_id  = $db -> getOne($sql); 
	
	        $result = cancelOrderInventoryReservation($order_id);
	  
	        $sql3 ="SELECT oir.status as reserve_status,dc_order_id
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					left join ecshop.supplier_transfer_mapping stm on stm.dt_order_id = oi.order_id
					left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
					where srr.SUPPLIER_RETURN_ID ={$supRetReqId}";
	       $item_info = $db -> getRow($sql3);
	       if(empty($item_info['reserve_status'])){	    		
	     		 $sql = "update romeo.supplier_return_request 
	     	            set check_status = '{$check_status}',check_user = '{$_SESSION['admin_name']}'
	     	            where supplier_return_id = '{$supRetReqId}'
	     	       ";
	     	     $db -> query($sql);
	       }
	       if(!empty($item_info['dc_order_id'])){
		
				$sql = "UPDATE ecshop.ecs_batch_order_mapping SET is_cancelled = 'Y' where order_id = '{$item_info['dc_order_id']}' limit 1";
				$db->query($sql);
				$sql = "UPDATE romeo.purchase_order_info SET cancel_time = now() where order_id = '{$item_info['dc_order_id']}' limit 1";
				$db->query($sql);
				$sql = "SELECT om.batch_order_id from ecshop.ecs_batch_order_mapping om where om.order_id = {$item_info['dc_order_id']} limit 1";
				$batch_order_id = $db->getOne($sql);
				$sql = "UPDATE ecshop.ecs_batch_order_info set is_cancelled = 'Y' where batch_order_id = {$batch_order_id} limit 1";
				$db->query($sql);
		   }
     	}
     	if(isset($_REQUEST['outShip']) && $_REQUEST['outShip']=1){
     		header("Location: out_supplier_return_goods_request_list.php?view=purchase&act=search&message=".urlencode($message)); 
     	}else{
     		header("Location: supplier_dt_goods_request_list.php?view=purchase&act=search&message=".urlencode($message)); 
     	}
        
        exit();
     }else if('init' == $act){
     	
     	$sql = "
     		select supplier_return_id,status,check_status
     		from romeo.supplier_return_request
     		where supplier_return_id = '{$supRetReqId}'
     	";
     	$check = $db -> getRow($sql);
     	if(!$check){
     		$message = "该供应商退货申请未找到（{$supRetReqId}），请联系erp。。。";
     	}else{
     		if($check['status'] == 'CREATED' && $check['check_status'] == 'PASS'){
     			$sql = "
     				update romeo.supplier_return_request 
     				set CHECK_STATUS = 'INIT', CHECK_USER = '' 
     				where supplier_return_id = '{$supRetReqId}'
     			";
     			$result = $db -> query($sql);
     			if($result){
     				$message = "审核初始化成功";
     			}else{
     				$message = "审核初始化失败";
     			}
     		}else{
     			$message = "已审核并且仓库还未操作的申请才能进行弃审的操作。。。";
     		}
     	}
     	if(isset($_REQUEST['outShip']) && $_REQUEST['outShip']=1){
     		header("Location: out_supplier_return_goods_request_list.php?view=purchase&act=search&message=".urlencode($message));
     	}else{
     		header("Location: supplier_return_goods_request_list.php?view=purchase&act=search&message=".urlencode($message));
     	}
     	exit();
     }else if('initdt' == $act){
     	
     	$sql = "
     		select supplier_return_id,status,check_status
     		from romeo.supplier_return_request
     		where supplier_return_id = '{$supRetReqId}'
     	";
     	$check = $db -> getRow($sql);
     	if(!$check){
     		$message = "该供应商退货申请未找到（{$supRetReqId}），请联系erp。。。";
     	}else{
     		if($check['status'] == 'CREATED' && $check['check_status'] == 'PASS'){
     			$sql = "
     				update romeo.supplier_return_request 
     				set CHECK_STATUS = 'INIT', CHECK_USER = '' 
     				where supplier_return_id = '{$supRetReqId}'
     			";
     			$result = $db -> query($sql);
     			if($result){
     				$message = "审核初始化成功";
     			}else{
     				$message = "审核初始化失败";
     			}
     		}else{
     			$message = "已审核并且仓库还未操作的申请才能进行弃审的操作。。。";
     		}
     	}
     	if(isset($_REQUEST['outShip']) && $_REQUEST['outShip']=1){
     		header("Location: out_supplier_return_goods_request_list.php?view=purchase&act=search&message=".urlencode($message));
     	}else{
     		header("Location: supplier_dt_goods_request_list.php?view=purchase&act=search&message=".urlencode($message));
     	}
     	exit();
     }else if('complate' == $act){
     	
     	$sql = "
     		select SUPPLIER_RETURN_ID,STATUS,RETURN_ORDER_AMOUNT,STORAGE_AMOUNT,CHECK_STATUS
     		from romeo.supplier_return_request
     		where supplier_return_id = '{$supRetReqId}'
     	";
     	$check = $db -> getRow($sql);
     	if(!$check){
     		$message = "该供应商退货申请未找到（{$supRetReqId}），请联系erp。。。";
     	}else{
     		if($check['STATUS'] == 'EXECUTING' && $check['CHECK_STATUS'] == 'PASS'){
     			$sql = "
     				update romeo.supplier_return_request 
     				set RETURN_ORDER_AMOUNT = '{$check['STORAGE_AMOUNT']}', STATUS = 'COMPLETION' 
     				where supplier_return_id = '{$supRetReqId}'
     			";
     			$result = $db -> query($sql);
     			if($result){
     				$message = "部分完结成功";
     			}else{
     				$message = "部分完结失败！！";
     			}
     		}else{
     			$message = "已审核通过并部分退还，仓库反馈无货可退的申请才可以进行 “部分完结”操作！";
     		}
     	}
     	if(isset($_REQUEST['outShip']) && $_REQUEST['outShip']=1){
     		header("Location: out_supplier_return_goods_request_list.php?view=purchase&act=search&message=".urlencode($message));
     	}else{
     		header("Location: supplier_return_goods_request_list.php?view=purchase&act=search&message=".urlencode($message));
     	}
     	exit();
     }
 }

//批量处理请求
if ('update_group' == $act) {
    $check_status = $_REQUEST['status'];
    $action = $_REQUEST['action'];
    $arr=explode(";",$action);
    // 批量审核
    if($check_status == 'PASS') {
	    foreach ($arr as $value) {
	    	$result = create_supplier_return_order ($value, $_SESSION['admin_name']);
	   }
 	} 
 	// 批量否决
 	else if ($check_status == 'DENY') {
 		 foreach ($arr as $value) {
 		 	
 		 	$sql = "SELECT order_id 
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					where srr.SUPPLIER_RETURN_ID = {$value}";
            $order_id  = $db -> getOne($sql); 
	
	        $result = cancelOrderInventoryReservation($order_id);
	  
	        $sql3 ="SELECT oir.status as reserve_status,dc_order_id
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					left join ecshop.supplier_transfer_mapping stm on stm.dt_order_id = oi.order_id
					left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
					where srr.SUPPLIER_RETURN_ID ={$value}";
	       $item_info = $db -> getRow($sql3);
	       if(empty($item_info['reserve_status'])){
			 $sql = "update romeo.supplier_return_request 
				set check_status = '{$check_status}',check_user = '{$_SESSION['admin_name']}'
				where supplier_return_id = '{$value}'
				";
			 $db -> query($sql);
	       }
 		 }
 	}
       
   return ;
 //header("Location: supplier_return_goods_request_list.php?view=purchase&act=search"); 
   exit();
}elseif ('update_groupdt' == $act) {
    $check_status = $_REQUEST['status'];
    $action = $_REQUEST['action'];
    $arr=explode(";",$action);
    // 批量审核
    if($check_status == 'PASS') {
	    foreach ($arr as $value) {
	    	$result = create_supplier_return_order ($value, $_SESSION['admin_name']);
	   }
 	} 
 	// 批量否决
 	else if ($check_status == 'DENY') {
 		 foreach ($arr as $value) {
 		 	
 		 	$sql = "SELECT order_id 
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					where srr.SUPPLIER_RETURN_ID = {$value}";
            $order_id  = $db -> getOne($sql); 
	
	        $result = cancelOrderInventoryReservation($order_id);
	  
	        $sql3 ="SELECT oir.status as reserve_status,dc_order_id
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					left join ecshop.supplier_transfer_mapping stm on stm.dt_order_id = oi.order_id
					left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
					where srr.SUPPLIER_RETURN_ID ={$value}";
	       $item_info = $db -> getRow($sql3);
	       if(empty($item_info['reserve_status'])){
			 $sql = "update romeo.supplier_return_request 
				set check_status = '{$check_status}',check_user = '{$_SESSION['admin_name']}'
				where supplier_return_id = '{$value}'
				";
			 $db -> query($sql);
	       }
 		 }
 	}
       
   return ;
 //header("Location: supplier_return_goods_request_list.php?view=purchase&act=search"); 
   exit();
}elseif ('batch_update_groupdt' == $act) {
    $check_status = $_REQUEST['status'];
    $batch_id = $_REQUEST['batch_id'];
    
    $batch_sql = "SELECT srrg.SUPPLIER_RETURN_ID from ecshop.supplier_return_batch_info srbi
					inner join ecshop.supplier_transfer_mapping stm on stm.supplier_return_batch_id = srbi.batch_order_id
					INNER JOIN ecshop.ecs_order_info oi on oi.order_id = stm.dt_order_id
					INNER JOIN romeo.supplier_return_request_gt srrg on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					inner join romeo.supplier_return_request srr on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					where srbi.batch_order_id ='{$batch_id}'";
    
    $arr=$db ->getCol($batch_sql);
    // 批量审核
    if($check_status == 'PASS') {
	    foreach ($arr as $value) {
	    	$result = create_supplier_return_order ($value, $_SESSION['admin_name']);
	    }
 	} 
 	// 批量否决
 	else if ($check_status == 'DENY') {
 		 foreach ($arr as $value) {
 		 	QLog::log("TRTRRTRTRTRTRTTRTRTR: ".$value);
 		 	$sql = "SELECT order_id 
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					where srr.SUPPLIER_RETURN_ID = {$value}";
            $order_id  = $db -> getOne($sql); 
	
	        $result = cancelOrderInventoryReservation($order_id);
	  
	        $sql3 ="SELECT oir.status as reserve_status,dc_order_id
					from romeo.supplier_return_request srr
					INNER JOIN romeo.supplier_return_request_gt srrg on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					inner join ecshop.ecs_order_info oi on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					left join ecshop.supplier_transfer_mapping stm on stm.dt_order_id = oi.order_id
					left join romeo.order_inv_reserved oir on oir.order_id = oi.order_id
					where srr.SUPPLIER_RETURN_ID ={$value}";
	       $item_info = $db -> getRow($sql3);
	       if(empty($item_info['reserve_status'])){
	       	QLog::log("RRRRRRRRRRRRRRRRRRRRRR: ".$value);
			 $sql = "update romeo.supplier_return_request 
				set check_status = '{$check_status}',check_user = '{$_SESSION['admin_name']}'
				where supplier_return_id = '{$value}'
				";
			 $db -> query($sql);
	       }
 		 }
 	}
       
   return ;
 //header("Location: supplier_return_goods_request_list.php?view=purchase&act=search"); 
   exit();
}else if ('init_group' == $act){
	$check_status = $_REQUEST['status'];
    $action = $_REQUEST['action'];
    $arr=explode(";",$action);
    $message = "";
    foreach ($arr as $value) {
		$sql = "
	     	select supplier_return_id,status,check_status
	     	from romeo.supplier_return_request
	     	where supplier_return_id = '{$value}'
	     ";
		$check = $db->getRow ( $sql );
		if (! $check) {
			$message += "{$value} ";
		} else {
			if ($check ['status'] == 'CREATED' && $check ['check_status'] == 'PASS') {
				$sql = "
	     			update romeo.supplier_return_request 
	     			set CHECK_STATUS = 'INIT', CHECK_USER = '' 
	     			where supplier_return_id = '{$value}'
	     		";
				$result = $db->query ( $sql );
				if ($result) {
				} else {
					$message += "{$value} ";
				}
			} else {
				$message += "{$value} ";
			}
		}
    }
    if($message == "") {
    	$message = "批量弃审成功！";
    } else {
    	$message += "弃审错误，请重试！";
    }
    return ;
	//header ( "Location: supplier_return_goods_request_list.php?view=purchase&act=search&message=" . $message );
	exit ();
}else if ('init_groupdt' == $act){
	$check_status = $_REQUEST['status'];
    $action = $_REQUEST['action'];
    $arr=explode(";",$action);
    $message = "";
    foreach ($arr as $value) {
		$sql = "
	     	select supplier_return_id,status,check_status
	     	from romeo.supplier_return_request
	     	where supplier_return_id = '{$value}'
	     ";
		$check = $db->getRow ( $sql );
		if (! $check) {
			$message += "{$value} ";
		} else {
			if ($check ['status'] == 'CREATED' && $check ['check_status'] == 'PASS') {
				$sql = "
	     			update romeo.supplier_return_request 
	     			set CHECK_STATUS = 'INIT', CHECK_USER = '' 
	     			where supplier_return_id = '{$value}'
	     		";
				$result = $db->query ( $sql );
				if ($result) {
				} else {
					$message += "{$value} ";
				}
			} else {
				$message += "{$value} ";
			}
		}
    }
    if($message == "") {
    	$message = "批量弃审成功！";
    } else {
    	$message += "弃审错误，请重试！";
    }
    return ;
	//header ( "Location: supplier_return_goods_request_list.php?view=purchase&act=search&message=" . $message );
	exit ();
}else if ('batch_init_groupdt' == $act){
	$check_status = $_REQUEST['status'];
    $batch_id = $_REQUEST['batch_id'];
    
    $batch_sql = "SELECT srrg.SUPPLIER_RETURN_ID from ecshop.supplier_return_batch_info srbi
					inner join ecshop.supplier_transfer_mapping stm on stm.supplier_return_batch_id = srbi.batch_order_id
					INNER JOIN ecshop.ecs_order_info oi on oi.order_id = stm.dt_order_id
					INNER JOIN romeo.supplier_return_request_gt srrg on srrg.SUPPLIER_RETURN_GT_SN = oi.order_sn
					inner join romeo.supplier_return_request srr on srr.SUPPLIER_RETURN_ID = srrg.SUPPLIER_RETURN_ID
					where srbi.batch_order_id ='{$batch_id}'";
    
    $arr=$db ->getCol($batch_sql);
    $message = "";
    foreach ($arr as $value) {
		$sql = "
	     	select supplier_return_id,status,check_status
	     	from romeo.supplier_return_request
	     	where supplier_return_id = '{$value}'
	     ";
		$check = $db->getRow ( $sql );
		if (! $check) {
			$message += "{$value} ";
		} else {
			if ($check ['status'] == 'CREATED' && $check ['check_status'] == 'PASS') {
				$sql = "
	     			update romeo.supplier_return_request 
	     			set CHECK_STATUS = 'INIT', CHECK_USER = '' 
	     			where supplier_return_id = '{$value}'
	     		";
				$result = $db->query ( $sql );
				if ($result) {
				} else {
					$message += "{$value} ";
				}
			} else {
				$message += "{$value} ";
			}
		}
    }
    if($message == "") {
    	$message = "批量弃审成功！";
    } else {
    	$message += "弃审错误，请重试！";
    }
    return ;
	//header ( "Location: supplier_return_goods_request_list.php?view=purchase&act=search&message=" . $message );
	exit ();
}
 
 //判断是否为海外业务，加上Dragonfly、香港oppo、香港平世、乐其蓝光
 function is_oversea_sales($party_id=null){
 	if(empty($party_id)){
 		$party_id=$_SESSION['party_id'];
 	}
 	$array_party_id=array('65543','65536','65566','64');
 	if(in_array($party_id,$array_party_id)){
 		return true;
 	}
 	return false;
 }
 
 $is_oversea_sales=is_oversea_sales();
 // 退款方式
 $purchasePaidTypes = getPurchasePaidTypes();
 // 检索对应party下可用仓库
 if (!function_exists('get_available_facility')) {
     require_once("admin/includes/lib_main.php");	
 }
 
 // 可预订数量的限制，为了一些走生产日期的组织能顺利-gt掉所有库存，特此不需要检测数量，做完后这个应该改回来
 $need_check_number = true;
 $not_check_number_party_ids = array('65617');
 if(in_array($_SESSION['party_id'],$not_check_number_party_ids)) {
 	$need_check_number = false;
 }
 
 $smarty->assign('need_check_number', $need_check_number);
 $smarty->assign('sys_message', $sys_message);
 $smarty->assign('facilitys', array_intersect_assoc(get_available_facility(),get_user_facility()));
 $smarty->assign('purchasePaidTypes', $purchasePaidTypes);
 $smarty->assign('is_oversea_sales',$is_oversea_sales);
// $smarty->assign('category_list',getCategory());
 //$smarty->assign('status_id', $status_id);
 $smarty->display('supplier_return/supplier_return_goods_request.htm');
 QLog::log('out');

function getSearchResult(){
    global $db; 
    $goods_id = null;
    $style_id = null; 
    $facility_id = null;
    $is_new = null;
    $category_id = null;
    $original_provider_id = null; 
    $barcodes = null; 
    if(isset($_REQUEST['goods_id'])){
        $goods_id = $_REQUEST['goods_id']; 
        if(isset($_REQUEST['style_id']) && $_REQUEST['style_id'] !=""){
            $style_id = $_REQUEST['style_id']; 
        }else{
            $style_id = 0; 
        } 
    } 
    if(isset($_REQUEST['barcode'])){
        $barcodes = $_REQUEST['barcode']; 
        $barcodes = trim($barcodes); 
    } 

    if(isset($_REQUEST['original_provider_id'])){
        $original_provider_id = $_REQUEST['original_provider_id']; 
        $original_provider_id = trim($original_provider_id); 
    } 

    $facility_id =  $_REQUEST['facility_id'] ;
    $is_new =  $_REQUEST['is_new'] ;
    if($is_new == -1){
        $is_new = null; 
    }
    if(!isset($goods_id) && !isset($barcodes) ){
        $category_id = $_REQUEST['category_id'];
        if($category_id == -1){
            $category_id = null; 
        }
    }
    $array_ret_item = array(); 
        // 按具体的商品查询 goods_id 或 barcodes 
    if(!isset($category_id)){
        if(isset($goods_id)){
            //转为以新库存为标准
            $product_id = getProductId($goods_id,$style_id);
            $array_ret_item = getInventoryBy_can_request($product_id,null,$facility_id,null,$original_provider_id,null,null,$is_new);
        }else {
            if(isset($barcodes)){
                $barcodes = explode(",",$barcodes); 
                $barcodes = array_unique($barcodes); 
                $sql = "  SELECT pm.product_id 
                        from romeo.product_mapping pm 
                        LEFT JOIN ecshop.ecs_goods_style gs ON pm.ECS_GOODS_ID = gs.goods_id AND pm.ECS_STYLE_ID = gs.style_id 
                        INNER JOIN ecshop.ecs_goods g ON pm.ECS_GOODS_ID = g.goods_id 
                        WHERE IFNULL(gs.barcode,g.barcode) ".db_create_in($barcodes);
                $productIds = $db ->getAll($sql); 
                $product_ids = array(); 
                foreach ($productIds as $key => $value) {
                   $product_ids[] = $value['product_id']; 
                }
                unset($productIds);                 
                $array_ret_item = getInventoryBy_can_request($product_ids,null,$facility_id,null,$original_provider_id,null,null,$is_new);
            } 
        }
    }else{
                // 根据类别  暂不考虑 
    }
    return $array_ret_item; 
}
//修改getInventoryBy_can_request的逻辑 用于支持业务组的可用库存导出
function getSearchInventoryResult(){

    $array_ret_item = getInventoryBy_can_request_new();
//    pp($array_ret_item);
//    die();
    return $array_ret_item; 
}

function getCategory(){
    // 根据用户party_id取得非欧酷（1）和欧酷派（4）下的所有子分类
    require_once ROOT_PATH . 'includes/helper/array.php';
    global $db; 
    global $ecs; 
    $categorys = $db->getAllCached("SELECT  cat_id, cat_name, parent_id FROM {$ecs->table('category')} WHERE party_id not in (1, 4) and is_delete = 0 and sort_order < 50 and " . party_sql('party_id'));// 取得所有分类
    $refs = array();
    Helper_Array::toTree($categorys, 'cat_id', 'parent_id', 'childrens', $refs);
    $category_list = array();
    foreach ($refs as $ref) {
        $categorys = Helper_Array::treeToArray($ref, 'childrens');
        foreach ($categorys as $category) {
            if ($category['_is_leaf']) {
                $category_list[$category['cat_id']] = $category['cat_name'];
            }
        }
    }
    return $category_list; 
}
   
   // 导出 excel 
  function export_supplier_return_list_excel_template($array_ret_item){
    set_include_path ( ROOT_PATH . 'admin/includes/Classes/' );
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    $excel = new PHPExcel ();
    $sheet = $excel->getActiveSheet();
    
    $name = '供应商退货申请（-gt）清单';
    $sheet->setTitle($name);
    $config_title = array(
                      'A'=>'商品名称',
                      'B'=>'商品类型',
                      'C'=>'商家编码',
                      'D'=>'采购供应商',
                      'E'=>'库存类型',
                      'F'=>'采购单价',
                      'G'=>'仓库',
                      'H'=>'退还商品单价',
                      'I'=>'税率',
                      'J'=>'退还给供应商',
                      'K'=>'可申请数量',
                      'L'=>'退货数量',
                      'M'=>'退款方式',
                      'N' =>'退货订单类型',
                      'O' =>'支票号',
                      'P'=>'备注',
                      'Q'=>'金宝贝退货仓库',
                      'R'=>'币种'
                );
    foreach ($config_title as $cell_name => $cell_value){
        $sheet->setCellValue ( $cell_name.'1', $cell_value );
    }
    $goods_item_type_map = array(
        "NON-SERIALIZED"=>"1",
        "SERIALIZED"=>"0"
        ); 
    
    $config_value = array('A'=>'goods_name',
                          'D'=>'provider_name',
                          'F'=>'purchase_paid_amount',
                          'G'=>'facility_name',
                          'H'=>'purchase_paid_amount',
                          'J'=>'provider_name',
                          'K'=>'can_request'
                );
    $party_id = $_SESSION['party_id'];
    if($party_id == 65638 ){ // 乐其跨境 采购和 -gt税率都默认为1
    	$config_default_value = array(
                      'R'=>'RMB',
                      'I'=>'1',
                      'M'=>'1' ,
                      'N'=>'0'
                );
    }else{
    	$config_default_value = array(
                      'R'=>'RMB',
                      'I'=>'1.17',
                      'M'=>'1' ,
                      'N'=>'0'
                );
    }

    $j = 2;
    foreach ($array_ret_item as $item){

        if($item['can_request'] <=0 ) continue; 

        foreach ($config_value as $cell_name => $cell_value){
            $sheet->setCellValue ( $cell_name.$j, $item[$cell_value]);
        }
        
        $sheet->setCellValue ( 'B'.$j,$goods_item_type_map[$item['goods_item_type']]);
        if($item['style_id'] !='0'){
            $sheet->setCellValue ( 'C'.$j, $item['goods_id']."_".$item['style_id']);
        }else{
            $sheet->setCellValue ( 'C'.$j, $item['goods_id']);
        }
        $sheet->setCellValue ('E'.$j,$item['is_new']);

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
 
?>
