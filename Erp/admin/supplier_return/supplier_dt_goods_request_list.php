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
 if (!function_exists('get_available_facility')) {
     require_once("admin/includes/lib_main.php");	
 } 

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
 
//$outShip_facility_list = $db->getCol("select facility_id from romeo.facility where is_out_ship = 'Y' ");
//$outShip_facility = implode("','", $outShip_facility_list);
 
 
 $view = $_REQUEST['view'];
 $smarty -> assign('view', $view);
 if ('purchase' == $view) {
     admin_priv('cg_supplier_return_request','cg_supplier_return_list');
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
 	 admin_priv('ck_supplier_return_request_action');
 } else {
 	 die();
 }
 $supRetReqId = $_REQUEST['supRetReqId'];
 $act = $_REQUEST['act'];  
 $csv = $_REQUEST['csv'];  
 
 // 取消操作
 if ('cancle' == $act && !empty($supRetReqId)) { 
    // 直接更新状态
    global $db;
    $status = 'CANCELLATION' ;
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
		update_supplier_return_request_status($supRetReqId, $status,$order_id);
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
 	 $input_supplier_return_id = trim( $_REQUEST['input_supplier_return_id'] );
 	 $dt_batch_sn = trim( $_REQUEST['dt_batch_sn'] );
 	 
 	 $input_order_sn = trim( $_REQUEST['input_order_sn'] );
 	 $input_order_sn_dc = trim( $_REQUEST['input_order_sn_dc'] );
 	 $input_facility_id = trim( $_REQUEST['facility'] ); 
     $ret_supplier_name = $_REQUEST['original_provider_select'] ; 
 	 $ret_supplier_id= $_REQUEST['provider_id'] ;
 	 $ret_status= $_REQUEST['status'] ;
 	 $ret_check_status = $_REQUEST['check_status'];
 	 $from_date = $_REQUEST['from_date'];
 	 $to_date = $_REQUEST['to_date'];
     $request_id_excel = $_REQUEST['request_id_excel'];
     // 检索条件处理下
     
     if (empty($goods_name)) {
     	 $goods_id = '' ;
     	 $style_id = '' ;
     }
     if (empty($ret_supplier_name)) {
     	 $ret_supplier_id = '' ;
     }
     // 转化成PRODUCT_ID
    if (!empty($goods_id)) {
        $productId = getProductId($goods_id, $style_id);	
    }
	   // $_SESSION['facility_id']
    $error = ''; 
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
    $countSQl = "select count(*) from romeo.supplier_return_request rt where 1";
    $count_cond = ""; 
	if($productId){
		$count_cond .= " and rt.product_id = '$productId'";
	} 
	if($ret_supplier_id){
		 	$count_cond .= " and rt.original_supplier_id = '$ret_supplier_id'";
    } 
	if($ret_status){
		$count_cond .= " and rt.status = '$ret_status'";
	} 
	if($input_supplier_return_id ){
		$count_cond .=" and rt.supplier_return_id = '{$input_supplier_return_id}' "; 
	}
	if($input_order_sn ){
		$countSQl = " SELECT count(1) FROM  romeo.supplier_return_request_gt gt  
        LEFT JOIN romeo.supplier_return_request rt ON gt.supplier_return_id = rt.supplier_return_id 
        WHERE  1  ";
        $count_cond .= " and gt.supplier_return_gt_sn ='{$input_order_sn}' "; 
	}	
	if($input_facility_id){
		$count_cond .= " and rt.facility_id = '{$input_facility_id}' "; 
	}
	if($_SESSION['party_id']){
		$count_cond .= " and rt.party_id = '".$_SESSION['party_id']."' ";
    } 
    if($ret_check_status){
		$count_cond .= " and rt.check_status = '$ret_check_status'";
	} 
	if( $request_id_excel){
		$count_cond = " and rt.supplier_return_id ='{$request_id_excel}' "; 
	}
    $count_cond .=  " and rt.created_stamp >= '".$startDate."' and rt.created_stamp < '".$endDate."'"; 
    $sql = $countSQl.$count_cond;  
    if($input_order_sn_dc ){		
        $count_cond .= " and eoi.order_sn ='{$input_order_sn_dc}' "; 
	}
	if($dt_batch_sn ){		
        $count_cond .= " and stbi.batch_order_sn ='{$dt_batch_sn}' "; 
	}		
    $gtCount = $db->getOne($sql);
	$return_requests = getReturnRequest($count_cond,($pageNo -1)*$pageSize,$pageSize);
    $order_ids = array();  // 计算调整价格 
    foreach ($return_requests as $key => $request) {
//    	if(in_array($request['facility_id'],$outShip_facility_list)){
//		 		continue;
//		}
    	if($request['order_id']){
    		$order_ids[$request['order_id']] = $request['supplier_return_id'] ; 
    	}
    }
    $c_unit_cost = getPurchaseUnitCostByOrderId(array_keys($order_ids));
    $resultList = array(); 
    foreach ($return_requests as $key => $request) {
//    	if(in_array($request['facility_id'],$outShip_facility_list)){
//		 		continue;
//		}
		$request['return_order_amount'] = intval($request['return_order_amount']); 
		$request['price_diff'] = ""; 
		if($request['order_id']){
			$gt_order_id = $request['order_id']; 
			if(isset($c_unit_cost[$gt_order_id])){
				$request['price_diff'] = floatval($c_unit_cost[$gt_order_id]['unit_cost']) - floatval($request['unit_price']); 
				$request['c_order_sn'] = $c_unit_cost[$gt_order_id]['c_order_sn']; 
				$request['c_order_id'] = $c_unit_cost[$gt_order_id]['c_order_id']; 
			}
    	}
    	$resultList[$request['supplier_return_id']] = $request; 
    }
    unset($return_requests);          	      	      
	if(isset($_REQUEST['message'])){
	    $smarty->assign('message', $_REQUEST['message']);
	}   
	$pager = Pager($gtCount, $pageSize, $pageNo);
	$smarty->assign('pager', $pager);
 }
 
 if(isset($csv) && !empty($csv)){
 	$party_name = get_party_name();
 	header("Content-type:application/vnd.ms-excel;charset=utf-8");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","{$party_name}批量Gt导出") . ".csv");
    ob_start();
    $header_str = iconv("UTF-8",'GB18030',"gt申请号,gt订单号,商品名,条码,箱规,仓库,库存状态,类型,预出库数,已退数量,退货价格,审核状态,审核人,操作状态,申请时间,出库时间,退货原因,申请人\n");
    $file_str = "";
    foreach($resultList as $picihao => $req){
    	$operate_status = ""; 
    	if($req['check_status'] == 'PASS'){
    		if($req['status'] == 'CREATED'){
    			$operate_status = '开始退还';
    		}else if($req['status'] == 'EXECUTING'){
    			$operate_status = '已部分退还';
    		}else if($req['status'] == 'COMPLETION'){
    			$operate_status = '已全部退还';
    		}else{
    			$operate_status = '已取消';
    		}
    	}else if($req['check_status'] =='INIT'){
    		if($req['status'] == 'CANCELLATION'){
    			$operate_status = '已取消';
    		}else{
    			$operate_status = '未开始操作';
    		}
    	}
 
    	$file_str .= '="'.str_replace(",","",$picihao).'"'.",";
    	$file_str .= '="'.str_replace(",","",$req['order_sn']).'"'.","; 
    	$file_str .= str_replace(","," ",$req['product_name']).",";
    	$file_str .= '="'.str_replace(","," ",$req['barcode']).'"'.",";
    	$file_str .= str_replace(","," ",$req['spec']).",";
    	$file_str .= str_replace(","," ",$req['facility_name']).",";
    	$file_str .= str_replace(","," ",$status_map[$req['status_id']]).",";
    	$file_str .= str_replace(","," ",$req['order_type_id']).",";
    	$file_str .= str_replace(","," ",$req['return_order_amount']).",";
    	$file_str .= str_replace(","," ",$req['new_out_num']).",";
    	$file_str .= str_replace(","," ",$req['unit_price']).",";
    	$file_str .= str_replace(","," ",$check_status_map[$req['check_status']]).",";
    	$file_str .= str_replace(","," ",$req['check_user']).",";
    	$file_str .= str_replace(","," ",$operate_status).",";
    	$file_str .= str_replace(","," ",$req['created_stamp']).",";
    	$file_str .= str_replace(","," ",$req['new_out_time']).",";
    	$file_str .= str_replace(","," ",$req['note']).",";
    	$file_str .= str_replace(","," ",$req['created_user_by_login'])."\n";
    }
    $file_str = iconv("UTF-8",'gbk',$file_str);
    ob_end_clean();
    echo $header_str;
    echo $file_str;
    exit();
 }
 
 $row_span= array();
if(isset($resultList)){
	 foreach ( $resultList as $key => $order ) {
	 	$supplier_batch_order_sn = $resultList[$key]['supplier_batch_order_sn'];
		if(isset($row_span[$supplier_batch_order_sn])){
			$row_span[$supplier_batch_order_sn] = $row_span[$supplier_batch_order_sn] + 1; 
		}else{
			$row_span[$supplier_batch_order_sn] =  1;  
		}
	}		
}
 $smarty->assign('view', $view);
 $smarty->assign('error', $error);
 $smarty->assign('input_batch_gt_sn', $input_batch_gt_sn);
 // 保留检索条件
 $smarty->assign('goods_name', $goods_name);
 $smarty->assign('goods_id', $goods_id);
 $smarty->assign('style_id', $style_id);
 $smarty->assign('input_supplier_return_id', $input_supplier_return_id); 
 $smarty->assign('input_order_sn', $input_order_sn); 
 $smarty->assign('input_facility_id', $input_facility_id); 
 $smarty->assign('from_date', $from_date);
 $smarty->assign('to_date', $to_date);
 $smarty->assign('status', $ret_status);
 $smarty->assign('provider_name', $ret_supplier_name);
 $smarty->assign('provider_id', $ret_supplier_id);
 $smarty->assign('check_status', $ret_check_status);
$smarty->assign('row_span', $row_span);

 $smarty->assign('requestList', $resultList);
 $smarty->assign('facilitys', get_user_facility()); 
 $smarty->display('supplier_return/supplier_dt_goods_request_list.htm');
 
 function get_party_name ($party_id = null){
 	if(empty($party_id)){
 		$party_id = $_SESSION['party_id'];
 	}
 	global $db;
 	$sql = "select name from romeo.party where party_id = '{$party_id}'";
 	$result = $db -> getOne($sql);
 	return $result;
 }
 
 // 获取-dt 申请列表 
function getReturnRequest($sql_cond,$offset,$size){
	global $db;
	$sql = " select rt.supplier_return_id, rt.created_stamp,rt.last_update_tx_stamp,
		   rt.note, rt.party_id, rt.created_user_by_login , 
			rt.return_supplier_id, rt.original_supplier_id, rt.facility_id, 
		  rt.return_order_amount, rt.product_id, rt.container_id, 
			rt.inventory_item_type_id, rt.order_type_id, rt.check_no,
		  rt.tax_rate, rt.payment_type_id, rt.unit_price, 
		  rt.purchase_unit_price, 
			rt.status_id, p.product_name,rt.check_user, rt.check_status, rt.status, rt.batch_sn,
			f.facility_name  , 
		  IFNULL(gs.barcode,g.barcode) as barcode,g.spec ,
		  oi.order_id, oi.order_sn ,
		  ifnull(sum(-iid.quantity_on_hand_diff),0) as new_out_num ,
		   iid.created_stamp as new_out_time,oir.STATUS as reserve_status,eoi.order_sn as dc_order_sn,ebom.is_in_storage,
		   dcf.facility_name as dc_facility_name,stbi.batch_order_sn as supplier_batch_order_sn,
		   stbi.batch_order_id as supplier_batch_order_id,sum(ifnull(iidc.QUANTITY_ON_HAND_DIFF,0)) as dc_in_quantity,eboidc.batch_order_sn as dc_batch_order_sn
 		from romeo.supplier_return_request rt    
		left JOIN romeo.facility f ON rt.facility_id = f.FACILITY_ID    
		left join romeo.product p on rt.product_id = p.product_id 
		LEFT JOIN romeo.product_mapping pm on p.product_id = pm.product_id 
		LEFT JOIN ecshop.ecs_goods  g ON pm.ecs_goods_id = g.goods_id 
		left join ecshop.ecs_goods_style  gs on  gs.goods_id = pm.ecs_goods_id and  gs.style_id = pm.ecs_style_id and gs.is_delete=0 
		left JOIN romeo.supplier_return_request_gt gt ON rt.supplier_return_id = gt.supplier_return_id 
		left JOIN ecshop.ecs_order_info oi on  gt.supplier_return_gt_sn = oi.order_sn
    left JOIN ecshop.supplier_transfer_mapping stm on stm.dt_order_id = oi.order_id
    left join romeo.inventory_item_detail iidc on convert(stm.dc_order_id using utf8) = iidc.order_id
    left join ecshop.supplier_return_batch_info stbi on stm.supplier_return_batch_id = stbi.batch_order_id
    left join ecshop.ecs_order_info eoi on eoi.order_id = stm.dc_order_id
    left join ecshop.ecs_batch_order_mapping ebom on eoi.order_id = ebom.order_id
    left join ecshop.ecs_batch_order_info eboidc on eboidc.batch_order_id = ebom.batch_order_id
    left join romeo.facility dcf on dcf.facility_id = eoi.facility_id
		left JOIN romeo.order_inv_reserved oir on oir.order_id = oi.order_id
		LEFT JOIN romeo.inventory_item_detail iid ON convert(oi.order_id using utf8)=iid.order_id 
		where  1 {$sql_cond} and rt.order_type_id ='SUPPLIER_TRANSFER' 
		group by rt.supplier_return_id order by supplier_batch_order_sn desc, rt.created_stamp desc limit {$offset},{$size} "; 
	
	QLog::log("THHHHHHHHHHHHHHHHHHHHH : ".$sql);  
	$result = $db -> getAll($sql);
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
					WHERE " .Helper::db_create_in('oi1.order_id',$order_id).
				    " GROUP BY oi1.order_id ";
	$c_sns=$db->getAll($c_sql);
	return $c_sns;
 }
 
 // 根据 order_id 在库存表中查询 采购订单的信息  unit_cost order_id order_sn 
 function getPurchaseUnitCostByOrderId($order_ids){
 	  global $db;
 	  $sql = "SELECT rm.RETURN_ORDER_ID as gt_order_id,
 	  				ii.unit_cost,
				 oi.order_id as c_order_id , oi.order_sn as c_order_sn 
				from romeo.purchase_return_map rm 
				INNER JOIN romeo.inventory_item_detail iid on iid.order_goods_id = rm.PURCHASE_ORDER_GOODS_ID
				INNER JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id 
				INNER JOIN ecshop.ecs_order_info  oi ON rm.purchase_order_id =  oi.order_id  
				WHERE  ".Helper::db_create_in('rm.RETURN_ORDER_ID',$order_ids);
	$gt_costs = $db->getAll($sql);
	$c_order_price = array(); 
	foreach ($gt_costs as $key => $value) {
		$c_order_price[$value['gt_order_id']][$value['c_order_id']] = $value; 
	}
	$result = array();
	foreach($c_order_price as $key => $value){
		$c_order_ids = array(); 
		$unit_cost = ""; 
		foreach ($value as   $item) {
			 $c_order_ids[] = $item['c_order_sn']; 
			 $unit_cost = $item['unit_cost']; 
		}
		$result[$key] = array("unit_cost"=>$unit_cost,"c_order_sn"=>implode(",",$c_order_ids)); 
	}
	return $result;
 }
 
 class BatchGt{
	public static function get_batch_gt_info($batch_gt_sn) {
		global $db;
		$sql = "SELECT * FROM ecshop.ecs_batch_gt_info WHERE batch_gt_sn ='{$batch_gt_sn}' AND " . party_sql ( 'party_id' );
		$batch_order_info = $db->getRow ($sql);
		return $batch_order_info;
	}
	
	public static function get_supplier_return_list($batch_gt_sn,$fields =null,&$ref_fields) {
		global $db;
		$sql = "select f.facility_name as facilityName ,og.goods_name as productName,  
		ifnull(gs.barcode,g.barcode) as barcode,og.goods_number,zp.spec,
		gt.supplier_return_id as supplierReturnRequestId,  r.status_id as statusId,r.supplier_return_id, 
		r.check_user as checkUser, r.check_status as checkStatus, 
		r.unit_price,r.return_order_amount as returnOrderAmount,r.storage_amount as excutedAmount,
		r.original_supplier_id,r.return_supplier_id,r.created_user_by_login,
		r.note,r.unit_price as unitPrice, r.check_status as checkStatus,r.status as status, 
		r.created_stamp as 	createdStamp, r.note as note, r.created_user_by_login as createdUserByLogin,
		r.created_stamp,r.last_update_stamp,  r.batch_sn,
		oi.order_sn as supplierReturnGtSn,oi.order_id, 
		bm.batch_gt_sn,boi.created_stamp,p.provider_name 
		from ecshop.ecs_batch_gt_info boi 
		inner join ecshop.ecs_batch_gt_mapping bm ON boi.batch_gt_sn = bm.batch_gt_sn
		inner join ecshop.ecs_order_info oi ON bm.order_id = oi.order_id
		inner join ecshop.ecs_order_goods og ON oi.order_id = og.order_id   
		left join ecshop.ecs_goods g ON og.goods_id = g.goods_id
		left join ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and og.style_id = gs.style_id and gs.is_delete=0
		inner join ecshop.brand_zhongliang_product zp ON zp.barcode = ifnull(gs.barcode,g.barcode)
		inner join romeo.facility f ON oi.facility_id = f.facility_id
		inner join romeo.supplier_return_request_gt gt ON oi.order_sn = gt.SUPPLIER_RETURN_GT_SN
		inner join romeo.supplier_return_request r ON gt.supplier_return_id = r.supplier_return_id
		left join ecshop.ecs_provider p ON r.return_supplier_id = p.provider_id 
		where boi.batch_gt_sn = '{$batch_gt_sn}' ";
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
			 bm.batch_gt_sn,boi.created_stamp,p.provider_name 
			from romeo.supplier_return_request r 
			LEFT JOIN  romeo.supplier_return_request_gt gt   ON r.supplier_return_id = gt.supplier_return_id   
			LEFT JOIN  ecshop.ecs_order_info oi ON  gt.SUPPLIER_RETURN_GT_SN = oi.order_sn  
			LEFT JOIN  ecshop.ecs_batch_gt_mapping bm ON  oi.order_id = bm.order_id 
			LEFT JOIN ecshop.ecs_batch_gt_info boi on bm.batch_gt_sn = boi.batch_gt_sn
			INNER JOIN  ecshop.ecs_order_goods og ON oi.order_id = og.order_id 
			LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id 
			LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id 
			and og.style_id = gs.style_id and gs.is_delete=0
			LEFT JOIN ecshop.brand_zhongliang_product zp ON  zp.barcode = ifnull(gs.barcode,g.barcode) 
			LEFT JOIN romeo.facility f ON oi.facility_id = f.facility_id  
			LEFT JOIN ecshop.ecs_provider p ON r.return_supplier_id = p.provider_id 
			where r.supplier_return_id = '{$requestId}'";
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
