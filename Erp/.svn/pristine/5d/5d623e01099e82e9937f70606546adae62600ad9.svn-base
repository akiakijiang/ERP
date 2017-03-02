<?php
	define('IN_ECS', true);
	require('../includes/init.php');
	require('../function.php');
	include_once(ROOT_PATH . 'RomeoApi/lib_currency.php');
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_facility.php');
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
    
	$supplier_order_type_r = array(
		'0' => 'SUPPLIER_RETURN',
		'1' => 'SUPPLIER_SALE',
	);

	$json = new JSON();
	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	$final = array(message => "",content => array());
	$config = array('供应商批次号退货申请（-gt）清单'  =>
				array('batch_sn'=>'批次号',
					  'cat_name'=>'分类',
					  'goods_name'=>'商品名称',
					  'product_id'=>'erp专用id',
					  'goods_item_type'=>'商品类型',
					  'good_sn'=>'商品条码',
					  'style_sn'=>'样式条码',
					  'provider_id'=>'供应商条码',
					  'provider_name'=>'供应商',
					  'is_new'=>'库存类型',
				      'order_type'=>'业务类型',
				      'purchase_paid_amount'=>'采购单价',
				      'storage_amount'=>'库存数量',
				      'can_request'=>'可申请数量',
					  'facility_id' =>'仓库编码',
				      'facility_name'=>'仓库',
					  'goods_price'=>'退还商品单价',
					  'goods_rate'=>'税率',
				      'ret_provider'=>'退还给供应商',
				      'ret_amount'=>'退货数量',
				      'purchase_paid_type'=>'退款方式(银行付款-1\现金-2\支票-4)',
				      'order_type_id'=>'退货订单类型(退给供应商（扣原采购发票）-0\二手商品销售（不扣原采购发票）-1)',
					  'chequeNo'=>'支票号',
				      'remark'=>'备注',
				      'currency'=>'币种'
				));
	if (!$uploader->existsFile ( 'fileToUpload' )) {
		$final['message'] =  '没有选择上传文件，或者文件上传失败';
	}
	//取得要上传的文件句柄
	if($final['message'] == ""){
		$file = $uploader->file ( 'fileToUpload' );
	}
	// 检查上传文件
	if ($final['message'] == "" && ! $file->isValid ( 'xls, xlsx, csv', $max_size )) {
		$final['message'] = "非法的文件! 请检查文件类型(xls, xlsx, csv), 并且系统限制的上传大小为". $max_size/1024/1024 . "MB";
	}
	// 读取excel
	if($final['message'] == ""){
		$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$final['message'] = reset ( $failed );
		}
	}
	if($final['message'] == ""){
		$rowset = $result ['供应商批次号退货申请（-gt）清单'];
		if (empty ( $rowset )) {
			$final['message'] = "excel文件中没有数据,请检查文件";
		}
	}
	$err_array = null;
	if($final['message'] == ""){
		$err_array = check_batch_sn_import_data($rowset,$supplier_order_type_r) ;// check_batch_sn_import_data($rowset);
	}
	if(count($err_array) > 0){
		foreach($err_array as $k=>$v){
			$final['message'] .= "第".($k)."行"."  ".$v."\n";
		}
	}
	$status_map_r = array('全新' => 'INV_STTS_AVAILABLE', '二手' => 'INV_STTS_USED', '次品' => 'INV_STTS_DEFECTIVE') ;

	if($final['message'] == ""){
		foreach ($rowset as $row) {
			$obj_data = new stdClass();
			$obj_data->supplierReturnRequestId ='';//
		 	$data_from_sql = get_serialized_goods($row['facility_id'], 
		 										$row['provider_id'], 
		 										$row['is_new'], 
		 										$row['purchase_paid_amount'], 
		 										$row['product_id']);
		 	$serial_str = '[';
		 	$is_first = true;
		 	foreach ($data_from_sql as $item){
          		if($is_first){
              		$serial_str += '{"erp_goods_sn":"'+ $item['erp_goods_sn'] + '", "in_sn":"' + $item['in_sn'] + '"}';
              		$is_first = false;
          		}else{
              		$serial_str += ', {"erp_goods_sn":"'+ $item['erp_goods_sn'] + '", "in_sn":"' + $item['in_sn'] + '"} ';
          		}
        	}
        	$serial_str += ']';
        	$obj_data->serial_number = $serial_str;
		 	//查看资料：
		 	$obj_data->productId = $row['product_id'];
		 	$sql = "
			   SELECT pm.ecs_goods_id
	                FROM  ecshop.ecs_goods AS g
                  inner join romeo.product_mapping pm on pm.ecs_goods_id = g.goods_id
	                WHERE pm.product_id= '{$row['product_id']}'
			 ";
		 	$obj_data->ret_goods_id = $db -> getOne($sql);
		 	$obj_data->original_provider_id = $row['provider_id'];
		 	$obj_data->facility_id = $row['facility_id'];
		 	$obj_data->purchase_unit_price = $row['purchase_paid_amount'];
		 	$obj_data->status_id = $status_map_r[$row['is_new']];
		 	$obj_data->goods_item_type = $row['goods_item_type'];
		 	//申请填写：
		 	$obj_data->batch_sn = $row['batch_sn']; //退还批次号
		 	if(is_numeric($row['ret_provider'])){
		 		$obj_data->ret_provider_id = $row['ret_provider'] ;//退还供应商id
		 	}else{
		 		$obj_data->ret_provider_id = get_provider_id_by_name($row['ret_provider']);//退还供应商id
		 	}
		 	$obj_data->order_type_id = $supplier_order_type_r[$row['order_type_id']] ;//退还订单类型
		 	$obj_data->goods_price = $row['goods_price'] ;//退还商品单价
		 	$obj_data->tax_rate = $row['goods_rate'] ;//税率
		 	$obj_data->ret_amount = $row['ret_amount'] ; //退货数量
		 	$obj_data->currency = empty($row['currency']) ? $row['currency'] : 'RMB' ; //币种
			
		 	$obj_data->purchase_paid_type = $row['purchase_paid_type'] ;//退款方式
		 	$obj_data->chequeNo = $row['cheque'];//支票号
		 	$obj_data->remark = $row['remark'] ;//备注 
			$final['show'] .= "【".apply_ret_req($obj_data)."】  "; 
		}
	}

 function check_batch_sn_import_data($rowset,$supplier_order_type_r){
		 global $db, $ecs;
		$err_array = array();
		$num = 1;		
        $display_error = array();
		foreach ($rowset as $row) {							
			$num += 1;
			if(is_numeric($row['ret_provider'])){
		 		$ret_provider_id = $row['ret_provider'] ; 
		 		$sql = "SELECT provider_id FROM {$ecs->table('provider')} WHERE provider_id = '{$ret_provider_id}'";
                $ret_provider_id  = $db->getOne($sql);
		 	}else{
		 		$ret_provider_id  = get_provider_id_by_name($row['ret_provider']); 
		 	}
		 	
			if( empty($ret_provider_id) ){
				$display_error[$num] .="退还给供应商  不能为空 ";
			}
	  
			if( empty($row['facility_id']) ){
				 $display_error[$num] .="仓库编码　不能为空";
			}
		    if(  empty($row['provider_id']) ){
				 $display_error[$num] .="供应商条码　不能为空";
			}
			
			if( empty( $row['is_new'] ) ){
				 $display_error[$num] .="库存类型　不能为空";
			}
			
			if(empty($row['product_id'])){
				$display_error[$num] .="erp专用id 不能为空";
			}
			
			if( empty($row['remark'])){
				$display_error[$num] .="备注 不能为空";
			}
			
			//价格和税款数值检查
			if(!(is_int_float($row['purchase_paid_amount']) 
				&& is_int_float($row['goods_price']) 
					&& is_int_float($row['goods_rate']))){
				$err_array['error'] =$num .'行价格和税款数值输入有误，请检查';
				$display_error[$num] .="价格和税款数值输入有误，请检查";
			}
			//订单类型检查
			if($row['order_type_id'] != '0' && $row['order_type_id'] != '1'){
				$err_array['error'] =$num .'行订单类型有误，请检查';
				$display_error[$num] .="订单类型有误";
			}
			//退款方式检查 银行付款-1\现金-2\支票-4
			if($row['purchase_paid_type'] != '1' 
				&& $row['purchase_paid_type'] != '2'
				&& $row['purchase_paid_type'] != '4'){
				$err_array['error'] = $num .'行退款方式有误，请检查';
				$display_error[$num] .="退款方式有误";
			}
			//不退发票的订单，退回的供应商必须和采购进来的供应商一致
			if($supplier_order_type_r[$row['order_type_id']] == 'SUPPLIER_RETURN' 
					&& $row['provider_id'] !=  $ret_provider_id ){
				$err_array['error'] =$num .'行不退发票的订单，退回的供应商必须和采购进来的供应商一致！';
				$display_error[$num] .=" 不退发票的订单，退回的供应商必须和采购进来的供应商一致！";
			}

			//检测新库存是否有足够的数量
			$condition = '';
			$batch_sn=trim($row['batch_sn']);
			$facilityId=$row['facility_id'];
			$purchaseUnitPrice = $row['goods_price'] ;
			$product_id = $row['product_id'];
			if(!empty($batch_sn)) {
				$condition = " and ii.batch_sn = '{$batch_sn}' ";
			} else {
				$condition = " and ii.batch_sn = '' ";
			}	
			if(!empty($returnSupplierId)) {
				$condition = " and ii.provider_id = '{$returnSupplierId}' ";
			}	
			
			$sql = "
			    select ifnull(sum(ii.quantity_on_hand_total),0)
					from romeo.inventory_item ii 
				where ii.quantity_on_hand_total > 0 
				      and ii.facility_id = '{$facilityId}' and ii.product_id = '{$product_id}'
				      and ii.unit_cost = '{$purchaseUnitPrice}'
			 " .$condition;
			$amount = $db -> getOne($sql);	
            $reg = '/^[1-9]([0-9])*$/';
			// 数量 填写是否为正数 
		    if(!preg_match($reg, $row['ret_amount'])) {
					$display_error[$num] = '商品数量有误，请填入正整数' ;
			}
		 	//退货数量不能大于库存数量
		 	if( $row['can_request'] < $row['ret_amount'])
		 	{
		 		$display_error[$num] = '退货数量:'.$row['ret_amount'].' 不能大于可申请数量:'. $row['can_request'].' ';
		 	}
		}		
		ksort($display_error);
		return $display_error;
	}
	
	echo $json->encode($final);
?>