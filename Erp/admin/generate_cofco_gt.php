<?php
/*
 * Created on 2011-8-27
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 define('IN_ECS', true);

 require_once('includes/init.php');
 require_once('function.php');
 require_once('includes/cls_json.php');
 require_once("RomeoApi/lib_inventory.php");
 require_once('RomeoApi/lib_supplier_return.php');
 require_once ('includes/debug/lib_log.php');
 require_once(ROOT_PATH.'admin/supplier_return/orderGoodsAmountForGt.php');

admin_priv('zhongliang_B2B_out');



if($_SESSION['party_id'] != '65625') {
	die('目前只有中粮支持B2B出库，请切换到中粮组织后再操作！');
}

 QLog::log('in');
 
 $request = $_REQUEST['request'];
 if (!empty($request) && $request == 'ajax'){
 	$json = new JSON;
 	$act = $_REQUEST['act'];
 	switch ($act) {	
 		case 'search_goods' :
  	        $limit = 40 ;   // 每次最大显示40行
            print $json->encode(get_goods_list_like($_POST['q'], $limit));
              		
 		    break ;
 		case 'search_providers':
 		    $limit = 40 ;
            print $json->encode(get_providers_list_like('中粮', $limit));
            
            break ;
        case 'search_goods_storage' :
         	// 检查当前商品库存
 		    $order_goods_id = $_REQUEST['goods_id'] ;
            $order_style_id = $_REQUEST['style_id'] ;
            $original_provider_id = $_REQUEST['original_provider_id'];
            $facility_id = $_REQUEST['facility_id'] ;
            $barcode = trim($_REQUEST['bar_code']);
            $status_id = $_REQUEST['status_id'] ;
            if(empty($order_style_id)){
            	$order_style_id=0;
            }
            // 检查商品是否串号控制
            if (!function_exists('get_goods_item_type')){
     	        require_once("admin/includes/lib_goods.php");	
             }
            // 根据 barcode 找到 goods_id 
            if( !empty($barcode) ){
            	$sql = "select g.goods_id ,ifnull(gs.style_id,0) as style_id from ecshop.ecs_goods g
						left join ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
						where g.goods_party_id = '65625' and ifnull(gs.barcode,g.barcode) = '{$barcode}'";
			    $result = $db -> getRow($sql);
			    $order_goods_id = $result['goods_id'];
			    $order_style_id = $result['style_id'];
            } 
            $goods_item_type = get_goods_item_type($order_goods_id);
            $product_id = getProductId($order_goods_id,$order_style_id);
            $ret_items =  cofco_getInventoryBy_can_request($product_id,null,$facility_id,$_SESSION['party_id'],$original_provider_id);
            $array_ret_item = array();
 			if (!empty($ret_items)) {
            	foreach($ret_items as $item){
            		$item['order_goods_id'] =$order_goods_id; 
            		$item['order_style_id'] =$order_style_id; 
            		$array_ret_item[] = $item;
            	}
	            
            	$array_ret_item[] = $goods_item_type ;
            }
            print $json->encode($array_ret_item) ;
            break ;         // 检索结果 
        case 'search_serialized_goods' : 
            // 检查当前商品库存
 		    $order_goods_id = $_REQUEST['goods_id'] ;
            $order_style_id = $_REQUEST['style_id'] ;
            $original_provider_id = $_REQUEST['original_provider_id'];
            $facility_id = $_REQUEST['facility_id'] ;
            $status_id = $_REQUEST['status_id'];
            $purchase_paid_amount = $_REQUEST['purchase_paid_amount'] ;
            $product_id = getProductId($order_goods_id,$order_style_id);
            $serial_goods = get_serialized_goods($facility_id, $original_provider_id, $status_id , $purchase_paid_amount, $product_id);
            
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
 $act = $_REQUEST['act'];
 if ('create_item' == $act) {
 	
 	global $db;
 	$db->start_transaction();
 	// 新建一条退货申请记录 OR 更新退货申请记录
 	
	$ret_orginal_id = $_POST['ret_original_id'];
	if(empty($ret_orginal_id)){
		return false;
	}
 	$batch_sn = post_generate_gt($_POST);
 	$sys_message = '';
 	if( is_array($batch_sn) && !empty($batch_sn['err_no']) ){
 		$sys_message.= $batch_sn['message'];
 	}
 	foreach($ret_orginal_id as $key=>$item){
 		$obj_data = new stdClass();
	 	$obj_data->original_provider_id = $_POST['ret_original_id'][$key] ;
	 	$obj_data->facility_id = $_POST['ret_facility_id'][$key] ;
	 	$obj_data->status_id = $_POST['ret_status_id'][$key] ;
	 	$obj_data->goods_item_type = $_POST['goods_item_type'][$key] ;
	 	$obj_data->ret_provider_id = $_POST['ret_provider_id'][$key] ;
	 	$obj_data->order_type_id = $_POST['order_type_id'][$key] ;
	 	$obj_data->goods_price = $_POST['goods_price'][$key] ;
	 	$obj_data->currency = $_POST['currency'][$key] ;
	 	$obj_data->purchase_unit_price = $_POST['purchase_paid_amount'][$key] ;
	 	$obj_data->tax_rate = $_POST['goods_rate'][$key] ;
	 	$obj_data->ret_amount = $_POST['ret_amount'][$key] ;
	 	$obj_data->purchase_paid_type = $_POST['purchase_paid_types'][$key] ;
	 	$obj_data->remark = $_POST['remark'][$key] ;
	 	$obj_data->chequeNo = $_POST['cheque'][$key];
	 	$m_product_id = getProductId($_POST['ret_goods_id'][$key], $_POST['ret_style_id'][$key]);
	 	if(empty($m_product_id)){
	 		$sys_message .= "productId 为空";
	 		break;
	 	}
	 	$obj_data->productId = $m_product_id;
	 	$supRetReqId = '';
 		$supRetReqId = apply_ret_req($obj_data,$supRetReqId);
 		 
	 	//生成-gt订单
	 	$order_id = '';
 		$message =  create_supplier_return_order ($supRetReqId, $_SESSION['admin_name'], $order_id);
 		if(empty($order_id)){
 			$sys_message .= " order_id 为空";
 			break;
 		}
 		$sys_message .= $message;
		$sql = "INSERT INTO ecshop.ecs_batch_gt_mapping VALUES('$batch_sn', $order_id)";
		$db->query($sql);
	}
	
	if(!empty($_POST['need_change_facility']) && $_POST['need_change_facility']==1) {
		// 采购入库
		require_once('includes/lib_purchase.php');
	 	// 格式化中粮的采购入库数据
	 	format_cofco_purchase_params($_POST);

	 	$c_result = genereate_c();
	 	if(empty($c_result['message'])) {
	 		$batch_purchase_sn = $c_result['batch_order_id'];
		 	
		    // 记录批次映射表
			$sql = "INSERT INTO ecshop.ecs_change_facility_order_mapping VALUES('$batch_sn', '$batch_purchase_sn')";
			$db->query($sql);
	 	} else {
	 		$sys_message .= $c_result['message'];
	 	}
	}	
	
	if(empty($sys_message)) {
		$db->commit();
	} else {
		$batch_sn = '';
		$batch_purchase_sn = '';
		$db->rollback();
	}

 	$smarty->assign('batch_gt_sn', $batch_sn);
 	$smarty->assign('batch_purchase_sn', $batch_purchase_sn);
 	$smarty->assign('sys_message', $sys_message);
 } 

   
   
 if ('export_goods_storage' == $act) {    
 	// 检查当前商品库存
	$order_goods_id = $_REQUEST['goods_id'] ;
    $order_style_id = $_REQUEST['style_id'] ;
    $barcode = $_REQUEST['bar_code'] ;
	$original_provider_id = $_REQUEST['original_provider_id'];
    $facility_id = $_REQUEST['facility_id'] ;
            
    $status_id = $_REQUEST['status_id'] ;
    $purchase_unit_price = $_REQUEST['purchase_paid_amount'] ;
    // 检查商品是否串号控制
    if (!function_exists('get_goods_item_type')){
     	require_once("admin/includes/lib_goods.php");
    }
    $product_id = getProductId($order_goods_id,$order_style_id);
    if (!empty($purchase_unit_price)) {
       $cond = $cond . ' and ii.unit_cost = ' . $purchase_unit_price ;
    }
    $temp_party_id = $_SESSION['party_id'];
    $ret_items =  cofco_getInventoryBy_can_request($product_id,null,$facility_id,$temp_party_id,$original_provider_id);
    $status_map = array('INV_STTS_AVAILABLE' => '全新', 'INV_STTS_USED' => '	二手', 'INV_STTS_DEFECTIVE' => '次品') ;
    $array_ret_item = array();
 	 if (!empty($ret_items)) {
         $array_ret_items = array();
         //将搜索出来的汇总下
         foreach($ret_items as $item){
            if (!array_key_exists($item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id'].$item['product_id'].$item['facility_id'],$array_ret_items)){
            	$array_ret_items[$item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id'].$item['product_id'].$item['facility_id']] = $item;
            }else{
            	$array_ret_items[$item['purchase_paid_amount'].$item['is_new'].$item['order_type'].$item['provider_id'].$item['product_id'].$item['facility_id']]['storage_amount'] += $item['storage_amount'];
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
            	$array_item['goods_item_type'] = get_goods_item_type($array_item['goods_id']);
            	$array_ret_item[] = $array_item;
		 	}else if($return_list['status'] == 'EXECUTING' && $temp_party_id != '65574'){
		 		continue;
		 	}else if($return_list['status'] == 'CREATED' && $return_list['check_status'] == 'PASS' && $temp_party_id != '65574'){
		 		continue;
		 	}else if($return_list['status'] == 'CREATED' && $return_list['check_status'] == 'INIT' && $temp_party_id != '65574'){
		 		continue;
		 	}else{
		 		$array_item['is_new'] = $status_map[$array_item['is_new']];
            	$array_item['goods_item_type'] = get_goods_item_type($array_item['goods_id']);
            	$array_ret_item[] = $array_item;
		 	}
    	}
    }
    
	export_supplier_return_list_excel($array_ret_item);
	exit();
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
     } elseif ('edit' == $act){
      //   admin_priv('cg_supplier_return_request');
         $status_map = array('INV_STTS_AVAILABLE' => 'NEW', 'INV_STTS_USED' => 'SECOND_HAND', 'INV_STTS_DEFECTIVE' => 'DISCARD') ;    
         // 仓库
         $facilities = get_available_facility();
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
     		 $sql = "update romeo.supplier_return_request 
     	            set check_status = '{$check_status}',check_user = '{$_SESSION['admin_name']}'
     	            where supplier_return_id = '{$supRetReqId}'
     	       ";
     	     $db -> query($sql);
     	}
        
        header("Location: supplier_return_goods_request_list.php?view=purchase&act=search&message=".$message); 
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
     	header("Location: supplier_return_goods_request_list.php?view=purchase&act=search&message=".$message);
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
			 $sql = "update romeo.supplier_return_request 
				set check_status = '{$check_status}',check_user = '{$_SESSION['admin_name']}'
				where supplier_return_id = '{$value}'
				";
			 $db -> query($sql);
 		 }
 	}
       
   return ;
 //header("Location: supplier_return_goods_request_list.php?view=purchase&act=search"); 
   exit();
} else if ('init_group' == $act){
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
 
function post_generate_gt($args){
	global $db;
//	var_dump($args);
//	$document_id =$args['document_id'];
	$plan_out_time = $args['plan_out_time'];
//	$actual_out_time = $args['actual_out_time'];
	$other_reason = $args['other_reason'];
	$shipping_company = $args['shipping_company'];
	$tracking_number = $args['tracking_number'];
	$consignee = $args['consignee'];
	$tel = $args['tel'];
	$mobile = $args['mobile'];
	$zipcode = $args['zipcode'];
	$outer_remain_sn = $args['outer_remain_sn'];
	$outer_out_sn  = $args['outer_out_sn'];
	$distribution_name = $args['distribution_name'];
	$out_type = $args['out_type'];
	$address = $args['address'];
	$postscript = $args['postscript'];
	$batch_sn = '';
	do{
		$batch_sn = get_cofco_batch_sn();
		$sql = "INSERT INTO ecshop.ecs_batch_gt_info VALUES 
				('$batch_sn', {$_SESSION['party_id']}, ' ', '$plan_out_time', ' ',
				 '$other_reason', '$out_type','$shipping_company', '$tracking_number', '$consignee','$tel', '$mobile','$zipcode',
				 '$outer_remain_sn', '$outer_out_sn','$distribution_name', '$address', '$postscript', '{$_SESSION['admin_name']}', NOW(), NOW()) ";	
		$db->query($sql, 'SILENT');
		$error_no = $db->errno ();
		if ($error_no > 0 && $error_no != 1062) {
            $db->rollback();
            $result['err_no'] = 3;
            $result['message'] = "-gt-batch订单生成失败";
            return $result;
		}
	}while($error_no == 1062);
	
	return $batch_sn;
		
}

function get_cofco_batch_sn(){
	global $db;
	$day = date('Y-m-d');
	$batch_sn = date('Ymd');
	$sql = "SELECT COUNT(*) FROM ecshop.ecs_batch_gt_info WHERE created_stamp >= '$day'";
	$num = $db->getOne($sql);
	return $batch_sn.'00'.($num+1);
}
 
 $is_oversea_sales=is_oversea_sales();
 // 退款方式
 $purchasePaidTypes = getPurchasePaidTypes();
 // 检索对应party下可用仓库
 if (!function_exists('get_available_facility')) {
     require_once("admin/includes/lib_main.php");	
 }
 
 function get_out_types() {
 	$out_types = array('B2B','样品出库','其他');
 	
 	return $out_types;
 }
 
 function get_distribution_names() {
 	$distribution_names = array(
		'中粮天猫旗舰店',
		'中粮天猫悦活旗舰店',
		'中粮天猫金帝旗舰店',
		'中粮天猫酒类旗舰店',
		'中粮天猫五谷道场旗舰店',
		'中粮天猫海外甄选旗舰店',
		'中粮淘宝品牌直销店',
		'中粮1号商城旗舰店',
		'中粮京东商城旗舰店',
		'中粮京东商城海外甄选旗舰店',
		'中粮食品1号店分销平台',
		'中粮食品京东分销平台',
		'中粮食品我买网分销平台',
		'其他'
    );
    
    return $distribution_names;
 }

 // 个人 组织 仓库 的交集 
 $available_facilitys = array_intersect_assoc(get_available_facility(),get_user_facility(),get_best_facility());
 
 $smarty->assign('facilitys',$available_facilitys); // get_user_party_facility_ids()
 $smarty->assign('best_facilitys', $available_facilitys);      // 上传文件时选择的仓库 
 $smarty->assign('out_types', get_out_types());
 $smarty->assign('distribution_names',get_distribution_names());
 $smarty->assign('purchasePaidTypes', $purchasePaidTypes);
 $smarty->assign('is_oversea_sales',$is_oversea_sales);
 //$smarty->assign('status_id', $status_id);
 $smarty->display('oukooext/generate_cofco_gt.htm');
 QLog::log('out');
 function get_best_facility(){
 	global $db;
 	$sql = "select facility_id,facility_name from  ecshop.express_best_facility_warehouse_mapping ";
 	$result =  $db->getAll($sql);
 	$dataMapping = array();
 	foreach($result as  $value){
 		$facility_id = $value['facility_id'];
 		$facility_name = $value['facility_name'];
 		$dataMapping[$facility_id] = $facility_name;
 	}
 	return $dataMapping;
 }
 
?>
