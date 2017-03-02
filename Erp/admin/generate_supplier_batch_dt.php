<?php
/*
 * Created on 2015-1-21
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 define('IN_ECS', true);

 require_once('includes/init.php');
 require_once('function.php');
  admin_priv ( 'supplier_tranfser_batch');
 require_once('includes/cls_json.php');
 require_once("RomeoApi/lib_inventory.php");
 require_once('RomeoApi/lib_supplier_return.php');
 require_once ('includes/debug/lib_log.php');
 require_once(ROOT_PATH.'admin/supplier_return/orderGoodsAmountForGt.php');


$sql = "select IN_STORAGE_MODE FROM romeo.party where party_id = '{$_SESSION['party_id']}' limit 1 ";
$IN_STORAGE_MODE = $db->getOne($sql);
if($IN_STORAGE_MODE!=3){
	die("仅支持批次号/生产日期维护业务组出库，请切换到该组织后再操作！");
}

 if(!function_exists('getInventoryBy')){
            	 require_once('admin/supplier_return/orderGoodsAmountForGt.php');
            	
}

 QLog::log('in');
 
 $request = $_REQUEST['request'];
 if (!empty($request) && $request == 'ajax'){
 	$json = new JSON;
 	$act = $_REQUEST['act'];
 	switch ($act) {	
 		case 'search_batch_sns' :
  	        $limit = 40 ;   // 每次最大显示40行   搜索为精确搜索
            print $json->encode(get_batch_sns_like($_POST['q'], $limit));
            		
 		    break ;
 		case 'search_goods' :
  	        $limit = 40 ;   // 每次最大显示40行
            print $json->encode(get_goods_list_like($_POST['q'], $limit));
              		
 		    break ;
 		case 'search_providers':
 		    $limit = 40 ;
            print $json->encode(get_providers_list_like($_POST['q'], $limit));
            
            break ;
        case 'search_goods_storage' :
            // 检查当前商品库存
	        $batch_sn = $_REQUEST['batch_sn'] ;
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
						where ".party_sql('g.goods_party_id') ." and ifnull(gs.barcode,g.barcode) = '{$barcode}' ";
			    $result = $db -> getRow($sql);
			    $order_goods_id = $result['goods_id'];
			    $order_style_id = $result['style_id'];
            } 
            $goods_item_type = get_goods_item_type($order_goods_id);
            
             //转为以新库存为标准
            $product_id = getProductId($order_goods_id,$order_style_id);   
            $ret_items = getInventoryBy_can_request($product_id,null,$facility_id,$_SESSION['party_id'],$original_provider_id,null,$batch_sn);       
            $array_ret_item = array();
 			if (!empty($ret_items)) {
            	//将搜索出来的汇总下
            	foreach($ret_items as $item){
            		$item['order_goods_id'] =$order_goods_id; 
            		$item['order_style_id'] =$order_style_id; 
            		$array_ret_item[] = $item;
            	}	            
            	$array_ret_item[] = $goods_item_type ;
            }
            print $json->encode($array_ret_item) ;
//            pp($json);             
            break ;         // 检索结果 
        case 'search_serialized_goods' : 
            // 检查当前商品库存
            $batch_sn = $_REQUEST['batch_sn'];
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
        	$batch_sn = $_REQUEST['batch_sn'] ;
            $goods_id = $_REQUEST['goods_id'] ;
            $style_id = $_REQUEST['style_id'] ;
            $barcode = trim($_REQUEST['bar_code']);
            $original_provider_id = $_REQUEST['original_provider_id'];
            $facility_id = $_REQUEST['facility_id'] ;
            $status_id = $_REQUEST['status_id'];
            $purchase_paid_amount = $_REQUEST['purchase_paid_amount'] ;                                
            $status_map = array('NEW' => 'INV_STTS_AVAILABLE', 'SECOND_HAND' => 'INV_STTS_USED', 'DISCARD' => 'INV_STTS_DEFECTIVE') ; 
            
            // 根据 barcode 找到 goods_id 
            if( !empty($barcode) && empty($goods_id)){
            	 // 如果输入的是商品的条形码 则获取 goods_id 和 style_id 
          		  $sql = "select goods_id,style_id,goods_color,barcode from ecshop.ecs_goods_style where barcode={$barcode} and is_delete=0";
            	  $goodsidandstylelist = $db -> getRow($sql);
            	  if( empty($goodsidandstylelist) ){
            	  	$sql = "select goods_id from ecshop.ecs_goods where barcode = '{$barcode}'";
            	  	$goodsidandstylelist = $db -> getRow($sql);
            	  }    
            	  if(!empty($goodsidandstylelist)){
            	  	$goods_id = $goodsidandstylelist['goods_id'];
            	  	$style_id = $goodsidandstylelist['style_id'];
            	  }
            }
            $productId = getProductId($goods_id, $style_id);           
            
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
 $sys_message = '';
 $act = $_REQUEST['act'];
 if ('create_item' == $act) {	
	$ret_orginal_id = $_POST['ret_original_id'];
		if(empty($ret_orginal_id)){
			return false;
	}

 	// 新建退货申请记录 OR 更新退货申请记录	
 	foreach($ret_orginal_id as $key=>$item){		 		 		
 		$obj_data = new stdClass();
// 		$obj_data->supplierReturnRequestId = $_POST['supplierReturnRequestId'][$key];
 		$obj_data->batch_sn = $_POST['ret_batch_sn'][$key] ;
	 	$obj_data->original_provider_id = $_POST['ret_original_id'][$key] ;
	 	$obj_data->facility_id = $_POST['ret_facility_id'][$key] ;
	 	$obj_data->facility_id_dt = $_POST['ret_facility_id_dt'][$key] ;
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
	 	$obj_data->productId = getProductId($_POST['ret_goods_id'][$key], $_POST['ret_style_id'][$key]);
	 	$obj_data->fchr_warehouse_id = $_POST['fchr_warehouse_id'][$key];
	 	$obj_data->ret_facility_id_dt = $_POST['ret_facility_id_dt'][$key];
	 	$obj_data->ret_goods_id = $_POST['ret_goods_id'][$key];
	 	$obj_data->ret_style_id = $_POST['ret_style_id'][$key];
	 	$obj_data->goods_name = $_POST['goods_name'][$key];
	 	$obj_data->arrive_time = $_POST['arrive_times'][$key];
        
        
     	$sql = "select AVAILABLE_TO_RESERVED from romeo.inventory_summary where  product_id = '{$obj_data->productId}' and facility_id = '{$obj_data->facility_id}' 
             and  status_id = '{$obj_data->status_id}';
	        ";
	 	$atp = $db -> getOne($sql);
	 	if($atp < $obj_data->ret_amount ){
	 	    $sys_message .=" 申请失败 申请量大于可预订量  库存总表可预定量为：".$atp;
	 	}
	 	else{
	        $supplier_request_id = apply_ret_req($obj_data);
	        $obj_data->supplierReturnRequestId = $supplier_request_id;
	        
	        $batch_order_id =get_supplier_transfer_batch_sn();
	        QLog::log("import_dt_list get batch_order_id: ".$batch_order_id);	                    
			$purchase_batch_id = get_purchase_batch_sn($obj_data,$batch_order_id);
			QLog::log("import_dt_list get get_purchase_batch_sn: ".$purchase_batch_id);
	        
	 	    if(!empty($supplier_request_id) && !empty($purchase_batch_id) && $purchase_batch_id!=0){
		 		$result = transfer_dc($obj_data,$batch_order_id,$purchase_batch_id);
		 		if($result['err_no'] != 0){			
					update_supplier_return_request_status($supplier_request_id, 'CANCELLATION','');
					$sys_message = "生成调拨失败";
		 	    }
	 	    
	 	    }else{
	 	    	update_supplier_return_request_status($supplier_request_id, 'CANCELLATION','');
	 	    }	 	
         $sys_message .="【调拨申请号". $supplier_request_id."】  ";  
 	    } 
 	}
 	sys_msg("{$sys_message}",0,array(array('href'=>'./generate_supplier_batch_dt.php')),true);
 }   
   
 if ('export_goods_storage' == $act) {    
    // 检查当前商品库存
    $batch_sn = trim($_REQUEST['batch_sn']) ;
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
				where ".party_sql('g.goods_party_id') ." and ifnull(gs.barcode,g.barcode) = '{$barcode}' ";
	    $result = $db -> getRow($sql);
	    $order_goods_id = $result['goods_id'];
	    $order_style_id = $result['style_id'];
    } 
    $goods_item_type = get_goods_item_type($order_goods_id);
     //转为以新库存为标准
    $product_id = getProductId($order_goods_id,$order_style_id);   
    $temp_party_id = $_SESSION['party_id'];
    $ret_items = getInventoryBy_can_request($product_id,null,$facility_id,$temp_party_id,$original_provider_id,$purchase_unit_price,null);
    $status_map_r = array('INV_STTS_AVAILABLE'=>'全新','INV_STTS_USED'=>'二手', 'INV_STTS_DEFECTIVE'=> '次品') ;
    $excel_result = array(); 
    foreach($ret_items as $item){
        if($item['can_request'] <= 0 ){
            continue; 
        }
    	$item['is_new'] = $status_map_r[$item['is_new']];
        $excel_result[] = $item; 
    }
    unset($ret_items); 
	export_batch_dt_list_excel($excel_result);
	exit();
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

 // 个人 组织 仓库 的交集 
 $available_facilitys = array_intersect_assoc(get_available_facility(),get_user_facility());
 $smarty->assign('sys_message', $sys_message);
 $smarty->assign('facilitys',$available_facilitys); 
 $smarty->assign('best_facilitys', $available_facilitys);      // 上传文件时选择的仓库 
 $smarty->assign('purchasePaidTypes', $purchasePaidTypes);
 $smarty->assign('is_oversea_sales',$is_oversea_sales);
 //$smarty->assign('status_id', $status_id);
 $smarty->display('oukooext/generate_supplier_batch_dt.htm');
 QLog::log('out');
?>
