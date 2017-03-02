<?php
	define('IN_ECS', true);
	require('../includes/init.php');
	require('../function.php');
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
    require_once(ROOT_PATH.'admin/supplier_return/orderGoodsAmountForGt.php');

	$json = new JSON();
    $best_facility_id = $_REQUEST['best_facility_id'];
    
	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	$final = array(message => "",data => array());
	$config = array('B2B出库清单'  =>
				array(
					  'barcode'=>'商品条码',
				      'goods_number'=>'出库数量',
				      'is_new'=>'商品状态'
				));  //  'facility_name' =>'仓库名称',
	if (!$uploader->existsFile ( 'fileToUpload' )) {
		$final['message'] .=  '没有选择上传文件，或者文件上传失败';
	}
	//取得要上传的文件句柄
	if($final['message'] == ""){
		$file = $uploader->file ( 'fileToUpload' );
	}
	// 检查上传文件
	if ($final['message'] == "" && ! $file->isValid ( 'xls, xlsx', $max_size )) {
		$final['message'] .= "非法的文件! 请检查文件类型(xls, xlsx), 并且系统限制的上传大小为". $max_size/1024/1024 . "MB";
	}
	// 读取excel
	if($final['message'] == ""){
		$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$final['message'] .= reset ( $failed );
		}
	}
	
	if($final['message'] == ""){
		$rowset = $result ['B2B出库清单'];
		if (empty ( $rowset )) {
			$final['message'] .= "excel文件中没有数据,请检查文件";
		}
	}
	if($final['message'] != ""){
		echo $json->encode($final);
		exit();
	}
    $check_result = check_import_data($rowset,$best_facility_id);
	 if( $check_result['res'] == 'success'){
	 	$final['message']='';
	 	$final['data'] = $check_result['data'];
	 }else{
	 	 $final['message'] .= $check_result['error'];
	 }
	 echo $json->encode($final);
 
		
function check_import_data($rowset,$best_facility_id){
		global $db;
		$result = array();
		$result['res'] = 'fail';
		$result['error'] = '';
		$num = 0;
		$display_index = array();
		$display_message = '';
		$successFlag = true;
		do {

			$sql = "select facility_id ,facility_name from  ecshop.express_best_facility_warehouse_mapping where facility_id ='{$best_facility_id}' ";
		    $result = $db->getRow($sql);
		    $facility_id = $result['facility_id'];
		    $facility_name = $result['facility_name'];
		    // var_dump($facility_id );var_dump($facility_name);
			// 检查仓库存在性
			if(empty($facility_id)) {
				$display_message .= '仓库名称有误，请检查';
				$successFlag = false;
			}
			
            // 检查商品状态
			$is_news = array_unique(Helper_Array::getCols($rowset,'is_new'));
			$index = 0;
			$reg = '/^[1-9]([0-9])*$/';
			$rowset_barcodes = array();
			foreach($rowset as $r){
				$index++;
				$rowset_barcodes[] = $r['barcode'];
				if(!in_array($r['is_new'],array('良品','不良品'))) {
					$successFlag = false;
					$display_index[$index] .="商品状态只能为良品或不良品  \n";
				}
				$goods_number = $r['goods_number'];
				// 数量 填写是否为正数 
				if(!preg_match($reg,$goods_number)) {
					$successFlag = false;
					$display_index[$index] = '商品数量【'.$goods_number.'】有误，请填入正整数'."\n";
				}
			}
			 
			// 检查条码重复性
		    $check_data = array();
		    $index = 0;
			foreach ($rowset as $row) {	
				$index++;
				if(isset($check_data[$row['barcode'].'-'.$row['is_new']])) {
					$successFlag = false;
					$display_index[$index].= '  相同条码【'.$row['barcode'].'】情况不要出现多行条码'."\n";
				} else {
					$check_data[$row['barcode'].'-'.$row['is_new']] = $row['barcode'].'-'.$row['is_new'];
				}
			}
			
			// 检查条码存在性
			$sql = "select ifnull(gs.barcode,g.barcode) from ecshop.ecs_goods g 
			left join ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
			where ifnull(gs.barcode,g.barcode) ".db_create_in($rowset_barcodes);
			$rightful_barcodes = $db->getCol($sql);
			$index = 0;
			foreach($rowset_barcodes as $b){
				$index++;
				if( !in_array($b,$rightful_barcodes)){
					$successFlag = false;
					$display_index[$index] .="  条码不存在请查证\n";
				}
			}
			
			// 检查库存
			/*
			$goods_storage = batch_search_goods_storage($rowset_barcodes,null,$facility_id,null);
			$goods_array = array( );
			foreach($goods_storage as $items){
				if(is_array($items)) {
					foreach($items as $item){
						$goods_array[] = $item;
					}
				}
			}
			
			$request =  cofco_getReturnRequest(null,$rowset_barcodes,$facility_id,null);
	        $sale =  getOrderSaling(null,$rowset_barcodes,$facility_id);
	        $reserve = getInventoryReserve(null,$rowset_barcodes,$facility_id,$_SESSION['party_id']);
	        $goods_array = getCanRequest_step($goods_array,$request,$sale,$reserve);
	        */
	        $goods_array =  cofco_getInventoryBy_can_request(null,$rowset_barcodes,$facility_id,$_SESSION['party_id'],null);
	        $goods_storage = array();
	        if(count($goods_array) > 0 )
			foreach($goods_array as $item){
				$k = $item['goods_status'];
				$goods_storage[$k][] = $item;
			}
		    
		    $index = 0;
            foreach ($rowset as $row) {			
				$status_id = $row['barcode'].'-'.$row['is_new'];
				$goods_number = $row['goods_number'];
				$system_amount = 0;
				$can_request = 0; 
				$index++;
				if(!isset($goods_storage[$status_id])) {
					$successFlag = false;
					$display_index[$index] .= "  在系统中不存在该商品\n";
				}
				$items = $goods_storage[$status_id];	
				
           			// 获取该类型商品的库存
           		if(is_array($items)){
					foreach($items as $item) {
						$system_amount += $item['storage_amount'];
						$can_request += $item['can_request'];
					}
           		}
			   
				if( ( $can_request ) < $goods_number ) {
					$successFlag = false;
					$display_index[$index] .=  "  系统里面只有：".$system_amount."个;可申请".$can_request."个; 您申请的个数:".$goods_number."不能申请 \n";
					$amount_error_flag = true;
				}
				if(  $goods_number > 30000  ){
					$successFlag = false;
					$display_index[$index] .= "  您申请的个数:".$goods_number."不能申请;申请的个数不能大于30000\n";
				}
			}


			$datas = array();
			if( $successFlag ) {
			foreach ($rowset as $row) {			
				$status_id = $row['barcode'].'-'.$row['is_new'];
				$goods_number = $row['goods_number'];

				$items = $goods_storage[$status_id];		
				
				foreach($items as $item) {
					$data = array();
					if($goods_number <=0 ) break;
					if( $item['can_request'] <= 0 ) continue;
					if( $item['can_request'] < $goods_number) {
						$ret_amount = $item['can_request'];
					} else {
						$ret_amount = $goods_number;
					}
					
					$data['goods_flag'] = $status_id;
					$data['order_goods_name'] = $item['goods_name'];
					$data['ret_goods_id'] = $item['goods_id'];
					$data['ret_style_id'] = $item['style_id'];
					$data['ret_original_id'] = $item['provider_id'];
					$data['ret_facility_id'] = $item['facility_id'];
					$data['ret_status_id'] = $item['is_new'];
					$data['goods_item_type'] = $item['goods_item_type'];
					$data['purchase_paid_amount'] = $item['purchase_paid_amount'];
					$data['ret_provider_id'] = $item['provider_id'];
					$data['order_type_id'] = 'SUPPLIER_RETURN';
					$data['goods_price'] = $item['purchase_paid_amount'];
					$data['currency'] = $item['currency'];
					$data['goods_rate'] = $item['goods_rate'];
					$data['ret_amount'] = $ret_amount;
					$data['ret_provider'] = $item['provider_name'];
					$data['facility_name'] = $facility_name;
					
					$datas[] = $data;
					
					$goods_number = $goods_number-$ret_amount;
				}
			  }
			}
		} while(false);
		
		
		if($successFlag) {
			$result['res'] = 'success';
			$result['data'] = $datas;
		}else{
			$result['error'].= $display_message."\n";
			ksort($display_index);
			foreach($display_index as $k => $d){
				$result['error'].= "第".($k+1)."行：  "."条码【".$rowset[$k-1]['barcode']."】状态".$rowset[$k-1]['is_new']."\n".$display_index[$k]."\n";
			}
		}
		return $result;
	}

?>