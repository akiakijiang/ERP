<?php
	define('IN_ECS', true);
	require('../includes/init.php');
	require('../function.php');
	include_once(ROOT_PATH . 'RomeoApi/lib_currency.php');
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_facility.php');
	require_once("RomeoApi/lib_inventory.php");
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
    require_once(ROOT_PATH.'admin/supplier_return/orderGoodsAmountForGt.php');



	$supplier_order_type_r = array(
		'0' => 'SUPPLIER_TRANSFER',
	);
	$goods_type_r = array(
		'0' =>'SERIALIZED',
		'1' =>'NON-SERIALIZED',
	);
	$json = new JSON();

	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	$final = array(message => "",content => array());
	$config = array('供应商退货申请（-gt）清单'  =>
				array('goods_item_type'=>'商品类型',
					  'goods_style'=>'商家编码',
					  'provider_name'=>'采购供应商',
					  'is_new'=>'库存类型',
				      'purchase_paid_amount'=>'采购单价',
				      'facility_name'=>'出货仓库',
				      'facility_name_in'=>'入货仓库',
					  'goods_price'=>'退还商品单价',
					  'goods_rate'=>'税率',
				      'ret_provider'=>'退还给供应商',
				      'ret_amount'=>'退货数量',
				      'purchase_paid_type'=>'退款方式',
				      'order_type_id'=>'退货订单类型',
					  'chequeNo'=>'支票号',
				      'remark'=>'备注',
				      'jbb_ret_facility'=>'金宝贝退货仓库',
				      'currency'=>'币种',
				      'arrive_time'=>'到货时间'
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
		$rowset = $result ['供应商退货申请（-gt）清单'];
		if (empty ( $rowset )) {
			$final['message'] = "excel文件中没有数据,请检查文件";
		}
	}
	

	$err_array = null;
	if($final['message'] == ""){
		$err_array = check_import_data($rowset,$supplier_order_type_r);
	}

	if(count($err_array) > 0){
		foreach($err_array as $k=>$v){
			$final['message'] .= "第".($k+1)."行"."  ".$v."\n";
		}
	}
	$status_map_r = array('全新' => 'INV_STTS_AVAILABLE', '二手' => 'INV_STTS_USED', '次品' => 'INV_STTS_DEFECTIVE') ;
      
    $providers =  array();
    $purchase_batch_ids = array();
    $supplier_batch_ids = array();
    
	if($final['message'] == ""){
		$index = 1;
		foreach ($rowset as $row) {
			$index++;
			$obj_data = new stdClass();
			$obj_data->supplierReturnRequestId ='';//
			$ret_amount = intval($row['ret_amount']);
			$facility_id_out  = get_facility_id_by_name(trim($row['facility_name'])); 
			$facility_id_in  = get_facility_id_by_name(trim($row['facility_name_in'])); 
			$provider_id  = get_provider_id_by_name(trim($row['provider_name'])); 
			require_once ROOT_PATH .'admin/includes/lib_goods.php';
			$goods_style = explode("_",trim($row['goods_style']));
			if(count($goods_style)>1 ){
				$style_id = $goods_style[1];
			}else{
				$style_id = 0;
			}
        	$product_id = getProductId($goods_style[0], $style_id);
			$success_flag = false;
			$goods_item_type = empty($row['goods_item_type'])?'1':$row['goods_item_type'];
			$arrive_time =  trim($row['arrive_time']);
			if( $goods_type_r[$goods_item_type] == 'SERIALIZED'){
				$success_flag = false;
				
			    $data_from_sql = get_serialized_goods_drop_used($facility_id, $provider_id, $status_map_r[$row['is_new']], $row['purchase_paid_amount'], $product_id);
			 	$serial_str = '[';
			 	$is_first = true;
			 	$index_serial = 0;
			 	foreach ($data_from_sql as $item){
			 		if($index_serial == $ret_amount){
			 			$success_flag = true;
			 			 break;
			 		}
			 		$index_serial++;
	          		if($is_first){
	          			$serial_str .='{"erp_goods_sn":"'.$item['erp_goods_sn'].'"}';
	              		$is_first = false;
	          		}else{
	              		$serial_str .= ', {"erp_goods_sn":"'. $item['erp_goods_sn'].'"'.'} ';
	          		}
	        	}
	        	$serial_str .= ']';
	        	$obj_data->serial_number = $serial_str;
			}
			if( $goods_type_r[$goods_item_type] != 'SERIALIZED'){
				$success_flag = true;
			}
			if($success_flag == false){
				$final['show'].= "第 【".$index."】 行,商品串号不足 请检查该商品的串号是否与商品的数量相匹配";
				break;
			}else{
				//查看资料：
				 	$obj_data->productId = $product_id;
				 	$obj_data->ret_goods_id = $goods_style[0];
				 	$obj_data->ret_style_id = $style_id;
				 	$obj_data->original_provider_id = $provider_id;
				 	$obj_data->facility_id = $facility_id_out;
				 	$obj_data->facility_id_in = $facility_id_in;
				 	$obj_data->purchase_unit_price = $row['purchase_paid_amount'];
				 	$obj_data->status_id = $status_map_r[$row['is_new']];
				 	$obj_data->goods_item_type = $goods_type_r[$goods_item_type];
				 	//申请填写：
				 	$obj_data->ret_provider_id = get_provider_id_by_name($row['ret_provider']);//退还供应商id
				 	$obj_data->order_type_id = $supplier_order_type_r[$row['order_type_id']] ;//退还订单类型
				 	$obj_data->goods_price = $row['goods_price'] ;//退还商品单价
                    $party_id = $_SESSION['party_id'];
                    if($party_id == 65638 ){ // 乐其跨境 采购和 -gt税率都默认为1
                    	$obj_data->tax_rate = empty($row['goods_rate'])?'1':$row['goods_rate'] ;//税率
                    }else{
                    	$obj_data->tax_rate = empty($row['goods_rate'])?'1.17':$row['goods_rate'] ;//税率
                    }

				 	$obj_data->ret_amount = $ret_amount ; //退货数量
				 	$obj_data->currency = empty($row['currency']) ? 'RMB':$row['currency'] ; //币种
					if('65574' == $_SESSION['party_id']){
						$obj_data->fchr_warehouse_id = get_gymboree_warehouse_id($row['jbb_ret_facility']);//金宝贝退货库ID
					}
				 	$obj_data->purchase_paid_type = $row['purchase_paid_type'] ;//退款方式
				 	$obj_data->chequeNo = $row['cheque'];//支票号
				 	$obj_data->remark = empty($row['remark'])?"空" :$row['remark'];//备注 
					$obj_data->arrive_time = $arrive_time; //到货时间 
				    //生成 -dt订单
					$supplier_request_id = apply_ret_req($obj_data);
			        $obj_data->supplierReturnRequestId = $supplier_request_id;
			        $obj_data->ret_facility_id_dt = $facility_id_in;
			        
			        //下面用于不同供应商的产生不同的批次
			        $key = $obj_data->ret_provider_id.$obj_data->facility_id;
			        if(in_array($key,$providers)){
				        $batch_order_id = $supplier_batch_ids[$key];
				        $purchase_batch_id = $purchase_batch_ids[$key];
			        }else{
                    	$batch_order_id =get_supplier_transfer_batch_sn();
	                    QLog::log("import_dt_list get batch_order_id: ".$batch_order_id);	                    
				        $purchase_batch_id = get_purchase_batch_sn($obj_data,$batch_order_id);
				        QLog::log("import_dt_list get get_purchase_batch_sn: ".$purchase_batch_id);
				        $providers[] = $key;
				        $purchase_batch_ids[$key] =  $purchase_batch_id;
				        $supplier_batch_ids[$key] = $batch_order_id;
                    }
			        
			 	    if(!empty($supplier_request_id) && !empty($purchase_batch_id) && $purchase_batch_id !=0){
			 	    	//如果生成 -dt订单正常  则开始产生 -c订单
				 		$result = transfer_dc($obj_data,$batch_order_id,$purchase_batch_id);
				 		//如果生成  -dc订单异常 则将-dt订单取消
				 		if($result['err_no'] != 0){			
							update_supplier_return_request_status($supplier_request_id, 'CANCELLATION','');
							$final['show'] .= "生成调拨失败";
				 	    }
			 	    
			 	    }else{
			 	    	update_supplier_return_request_status($supplier_request_id, 'CANCELLATION','');
						$final['show'] .= "生成调拨失败";
			 	    }	 						
					$final['show'] .= "【".$supplier_request_id."】  ";
			}
		 	
		}
	}
	//判断日期类型是否正确 例如：  2016-01-01
	function is_date($date)
	{
	 if($date == date('Y-m-d',strtotime($date))){
	  return true;
	 }else{
	  return false;
	 }
	}
	
	function check_import_data($rowset,$supplier_order_type_r){
		 global $db, $ecs;
		$num = 0;
	  
	    //用于限制批量导入仓库只能为一个
//	    $facilitys= array();

		$display_error = array();
		if(count($rowset) > 0 )
		foreach ($rowset as $row) {	
			
//			if($num == 0){
//				$facilitys[] = get_facility_id_by_name(trim($row['facility_name']));
//			}elseif(!in_array(get_facility_id_by_name(trim($row['facility_name'])),$facilitys)){
//				$display_error[$num] .="批量导入生成批次，请保持仓库一致";
//			}						
			$num += 1;
			if(!in_array($row['goods_item_type'],array('0','1'))){
				$display_error[$num] .="商品类型  请填写0或1,0表示串号商品";
			}
		 	$ret_provider_id  = get_provider_id_by_name(trim($row['ret_provider'])); 
		 	 
			if( empty($ret_provider_id) ){
				$display_error[$num] .="退还给供应商  不能为空  或者该供应商不存在 请查证";
			}
	  		$facility_id  = get_facility_id_by_name(trim($row['facility_name'])); 
			if( empty($facility_id) ){
				 $display_error[$num] .="出货仓库 　不能为空";
			}
			$facility_id_in  = get_facility_id_by_name(trim($row['facility_name_in'])); 
			if( empty($facility_id_in) ){
				 $display_error[$num] .="入货仓库 　不能为空";
			}
			$provider_id  = get_provider_id_by_name(trim($row['provider_name'])); 
		    if(  empty($provider_id) ){
				 $display_error[$num] .="采购供应商 不能为空";
			}
			
			if( empty( $row['is_new'] ) ){
				 $display_error[$num] .="库存类型　不能为空";
			}
			
			if( empty( $row['arrive_time'] )){
				 $display_error[$num] .="到货时间　不能为空";
			}
			if(!empty( $row['arrive_time']) && !  is_date(trim($row['arrive_time']))){
				$display_error[$num] .="到货时间格式错误";
			}
			
			if(empty($row['goods_style'])){
				$display_error[$num] .="商家编码 不能为空";
			}
			require_once ROOT_PATH .'admin/includes/lib_goods.php';
			$goods_style = explode("_",trim($row['goods_style']));
			if(count($goods_style)>1 ){
				$style_id = $goods_style[1];
			}else{
				$style_id = 0;
			}
        	
        	$product_id = getProductId($goods_style[0], $style_id);

        	if(empty($product_id)){
        		$display_error[$num].=" product_id 不存在 可能是没有该商品"; 
        	}
			
			//价格和税款数值检查
			$goods_rate = empty($row['goods_rate'])?'1.17':$row['goods_rate'];
			if(!(is_int_float($row['purchase_paid_amount']) 
				&& is_int_float($row['goods_price']) 
					&& is_int_float($goods_rate))){
				$display_error[$num].="采购价格，退货价格，税率只能是数字，请检查";
			}
			//订单类型检查
			if($row['order_type_id'] != '0' && $row['order_type_id'] != '1'){
				$display_error[$num].="退货订单类型 只能为 0 或 1";
			}
			//退款方式检查 银行付款-1\现金-2\支票-4
			if($row['purchase_paid_type'] != '1' 
				&& $row['purchase_paid_type'] != '2'
				&& $row['purchase_paid_type'] != '4'){
				$display_error[$num].="退款方式  只能为 1 或  2或 4"; 
			}
			//不退发票的订单，退回的供应商必须和采购进来的供应商一致
			if($supplier_order_type_r[$row['order_type_id']] == 'SUPPLIER_RETURN' &&
				(!(in_array($provider_id,array('432','78')) &&in_array($ret_provider_id,array('432','78'))))	&&
				 ($provider_id != $ret_provider_id) ){
				$display_error[$num].="不退发票的订单，退回的供应商必须和采购进来的供应商一致"; 
			}
			
			//金宝贝退货仓
			if('65574' == $_SESSION['party_id'] && empty($row['jbb_ret_facility'])){
				$display_error[$num].="您所在的组织 需要 填写 金宝贝退货仓";
			}else if('65574' == $_SESSION['party_id']){
				$facility_name = trim($row['jbb_ret_facility']);
				$sql = "select fchrWarehouseID
				from ecshop.brand_gymboree_warehouse
				where fchrWhName = '{$facility_name}'";
				if($db->getOne($sql)==''){
					$display_error[$num].="金宝贝退货仓库请不要随意填写，不清楚可咨询ERP";
				}
			} 
			$reg = '/^[1-9]([0-9])*$/';
			// 数量 填写是否为正数 
		    if(!preg_match($reg, $row['ret_amount'])) {
					$display_error[$num] = '商品数量 有误，请填入正整数' ;
			}
			
			//退货仓库+商品id+采购单价+退货数量+库存类型+采购供应商 共同决定库存是否足够
			$status_map_r = array('全新' => 'INV_STTS_AVAILABLE', '二手' => 'INV_STTS_USED', '次品' => 'INV_STTS_DEFECTIVE') ;
			// 查询 商品的库存 在这里不能扣除为预定已确定的订单商品的数量  销售订单中无供应商的信息 
            $can_request =  getInventoryBy($product_id,null,$facility_id,null,$provider_id,$row['purchase_paid_amount'],null);
            $can_request =  formatInventory($can_request); 
         
            $can_request_number = 0;
            if(!empty($can_request)){
	            foreach ($can_request as $can) {
	            	if($can['is_new'] == $status_map_r[$row['is_new']]){
	            		$can_request_number += $can['can_request'];
	            	}
	            }
	        } 
			if( $can_request_number < intval($row['ret_amount']) ){
				$display_error[$num] = '退货仓库+商品+采购单价+退货数量+库存类型+采购供应商 共同决定的系统库存不够 无已确定未预定订单时最多可申请:'.$can_request_number;
			}
			
			$sql = "select AVAILABLE_TO_RESERVED from romeo.inventory_summary where  product_id = '{$product_id}' and facility_id = '{$facility_id}' 
	             and  status_id = '{$status_map_r[$row['is_new']]}';
		        ";
		     QLog::log("WWWWWWWWWWWWWWWWWWWWWW: ".$sql);   
		 	$atp = $db -> getOne($sql);
		 	if($atp < intval($row['ret_amount']) ){
		 	    $display_error[$num] =" 申请失败 申请量大于可预订量  库存总表可预定量为：".$atp;
		 	}
		}
		ksort($display_error);
		return $display_error;
	}	
    
	echo $json->encode($final);
?>