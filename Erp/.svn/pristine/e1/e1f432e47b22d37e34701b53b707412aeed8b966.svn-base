<?php
	define('IN_ECS', true);
	require('includes/init.php');
	require('function.php');
	include_once('../RomeoApi/lib_currency.php');
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
	require_once (ROOT_PATH . 'includes/lib_order.php');
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');

	$json = new JSON();
	$error = "";
	$msg = "";
	$fileElementName = 'fileToUpload';
	$query_SKUS = array();
	$query_goods = array();
	
	$party_id = $_REQUEST['party_id'];
	$order_type = $_REQUEST['order_type'];
	
	$uploader = new Helper_Uploader ();
	
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	
	$final = array(message => "",content => array());
	
	$config = array('采购项目'  =>
				array('goods_number'=>'数量',
					  'price'=>'价格',
					  'tax'=>'税率',
					  'custom'=>'是否定制',
					  'barcode'=>'商品条码',
					  'SKU'=>'样式条码'
				));
	
	if (!$uploader->existsFile ( 'fileToUpload' )) {
		$final['message'] =  '没有选择上传文件，或者文件上传失败';
	}	

	//取得要上传的文件句柄
	if($final['message'] == ""){
		$file = $uploader->file ( 'fileToUpload' );
	}
	
	// 检查上传文件
	if ($final['message'] == "" && ! $file->isValid ( 'xls, xlsx', $max_size )) {
		$final['message'] = "非法的文件! 请检查文件类型(xls, xlsx), 并且系统限制的上传大小为". $max_size/1024/1024 . "MB";
	}
	
	// 读取excel
	if($final['message'] == ""){
		$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$final['message'] = reset ( $failed );
		}
	}
	
	if($final['message'] == ""){
		$rowset = $result ['采购项目'];
		if (empty ( $rowset )) {
			$final['message'] = "excel文件中没有数据,请检查文件";
		}
	}
	
	if($final['message'] == ""){
		$in_goods_number = Helper_Array::getCols ( $rowset, 'goods_number' );
		$in_price = Helper_Array::getCols ( $rowset, 'price' );
		$in_tax = Helper_Array::getCols ( $rowset, 'tax' );
		$in_custom = Helper_Array::getCols ( $rowset, 'custom' );
		$in_barcode = Helper_Array::getCols ( $rowset, 'barcode');
		$in_sku = Helper_Array::getCols($rowset, 'SKU');
		
		$check_value_arr = array('goods_number'=>'数量',
					  			 'price'=>'价格',
//					  			 'tax'=>'税率',
					 			 'custom'=>'是否定制'
							);
		
		foreach ( array_keys ( $check_value_arr ) as $val ) {
			$in_val = Helper_Array::getCols ( $rowset, $val );
			$in_len = count ( $in_val );
			Helper_Array::removeEmpty ( $in_val );
			if (empty ( $in_val ) || $in_len > count ( $in_val )) {
				$empty_col = true;
				$final['message'] = "文件中存在空的{$check_value_arr[$val]}，请确保数量，价格，是否定制列都有数据";
			}
		}
	}
	
	if ($final['message'] == "") {
		if(count(array_unique ( $in_custom )) != 1){
			$final['message'] = "是否定制只能选择无，请完整检查";
		}else{
			$arr_temp = array_unique ( $in_custom );
			if($arr_temp[0] != "无"){
				$final['message'] = "是否定制只能选择无，请完整检查";
			}
		}
	}
	
	if($final['message'] == ""){
		foreach ($in_goods_number as $item_value) {
			if(!(preg_match ('/^[1-9]([0-9]+)?$/', $item_value) && $item_value > 0)){
				$final['message'] = "采购数量必须为正整数";
			}
		}
	}
	
	if($final['message'] == ""){
		foreach ($in_price as $item_value) {
			if($item_value < 0){
				$final['message'] = "采购价格不能小于0";
			}
			if($order_type == 'DX' && $item_value > 0){
				$final['message'] = "若为DX类型，商品采购单价必须为0";
			}
		}
	}
//税率选择从ecs_goods表中获取	
//	if($final['message'] == ""){
//		foreach ($in_tax as $item_value) {
//			if($item_value < 0){
//				$final['message'] = "采购税率不能小于0";
//			}
//		}
//	}
	
	if($final['message'] == ""){
		$goods_barcodes = array();
		$SKU_barcodes = array();
		$goods_sku = array();
		
		foreach ($rowset as $row) {
			if($row['barcode'] == '' && $row['SKU'] == '') {
				$final['message'] = "商品条码和样式条码至少输入一项,请检查文件再导入";
				break;
			}
			
			if($row['barcode'] != '' && $row['SKU'] == ''){
				$sql = "select count(*) 
						from ecshop.ecs_goods eg
						left join ecshop.ecs_goods_style egs on eg.goods_id = egs.goods_id and egs.is_delete=0
						where eg.goods_party_id = '{$party_id}'
						and trim(egs.barcode) is null
						and trim(eg.barcode) = '{$row['barcode']}'";
				$result_num = $db->getOne($sql);
				if($result_num != "1"){
					array_push($goods_barcodes,$row['barcode']);
				}
				array_push($query_goods,$row['barcode']);
			}
			
			if($row['barcode'] == '' && $row['SKU'] != ''){
				$sql = "
					select count(*)
					from ecshop.ecs_goods_style egs 
					inner join ecshop.ecs_goods eg on eg.goods_id = egs.goods_id
					inner join ecshop.ecs_style es on es.style_id = egs.style_id
					where trim(egs.barcode) = '{$row['SKU']}' and egs.is_delete=0
					and eg.goods_party_id = {$party_id}
					";
				$result_num = $db->getOne($sql);
				if($result_num != "1"){
					array_push($SKU_barcodes,$row['SKU']);
				}
				array_push($query_SKUS,$row['SKU']);
			}
			
			if($row['barcode'] != '' && $row['SKU'] != ''){
				$sql = "
					select count(*)
					from ecshop.ecs_goods_style egs 
					inner join ecshop.ecs_goods eg on eg.goods_id = egs.goods_id
					inner join ecshop.ecs_style es on es.style_id = egs.style_id
					where trim(egs.barcode) = '{$row['SKU']}' and egs.is_delete=0
					and trim(eg.barcode) = '{$row['barcode']}' 
					and eg.goods_party_id = {$party_id}
				";
				$result_num = $db->getOne($sql);
				if($result_num != "1"){
					$goods_sku[$row['barcode']] =  $row['SKU'];
				}
				array_push($query_SKUS,$row['SKU']);
			}
		}
		if(count($goods_barcodes) > 0){
			$final['message'] = "根据以下商品条码找不到商品：";
			foreach($goods_barcodes as $good_barcode){
				$final['message'] .= $good_barcode.",";
			}
		}
		
		if(count($SKU_barcodes) > 0){
			$final['message'] .= "根据以下样式条码找不到样式：";
			foreach ($SKU_barcodes as $SKU_barcode){
				$final['message'] .= $SKU_barcode.",";
			}
		}
		
		if(count($goods_sku) > 0){
			$final['message'] .= "以下商品条码和样式条码不匹配：";
			foreach( array_keys ($goods_sku) as $key){
				$final['message'] .= "商品,".$key.",样式,".$goods_sku[$key].";";
			}
		}
	}
	
	if($final['message'] == ""){
		$sql = "
			select eg.goods_id,es.style_id,eg.goods_name,es.value,trim(egs.barcode) as barcode, eg.spec,eg.added_fee
			from ecshop.ecs_goods_style egs 
			inner join ecshop.ecs_goods eg on eg.goods_id = egs.goods_id
			inner join ecshop.ecs_style es on es.style_id = egs.style_id
			where goods_party_id = {$party_id} and egs.is_delete=0
			and trim(egs.barcode) " . db_create_in ( array_unique ( $query_SKUS ) );
		
		$SKUS = ( array ) $db->getAll ( $sql );
		$sql = "
			select goods_id,0 style_id ,goods_name,'无颜色' value,trim(barcode) as barcode,spec,added_fee
			from ecshop.ecs_goods
			where goods_party_id = {$party_id}
			and trim(barcode) ".db_create_in($query_goods);
		
		$goods = ( array ) $db->getAll ( $sql );		
		$barcodes = Helper_Array::toHashmap ( array_merge($SKUS,$goods), 'barcode' );
	}

	if($final['message'] == ""){
		foreach ($rowset as $row) {
			$added_good = array();
			if($row['SKU'] != ''){
				$barcode = $row['SKU'];
			}else{
				$barcode = $row['barcode'];
			}
			$added_good[0] = $barcodes[$barcode]['goods_name'];
			$added_good[1] = $barcodes[$barcode]['goods_id'];
			$added_good[2] = $barcodes[$barcode]['value'];
			$added_good[3] = $barcodes[$barcode]['style_id'];
			$added_good[4] = $row['goods_number'];
			$added_good[5] = $row['price'];
			$added_good[6] = 'false'; 
			$added_good[7] = '无';
			$added_good[8] = round($added_good[5] * 100 * $added_good[4]) / 100;
			$added_good[9] = 0;
			$added_good[10] = $barcodes[$barcode]['added_fee'];
			$added_good[11] = '';
			$added_good[16] = $barcode;
			$added_good[17] = $barcodes[$barcode]['spec'];
			array_push($final['content'],$added_good);
		}
	}
	
	QLog::log($final['message']);
	echo $json->encode($final);
?>