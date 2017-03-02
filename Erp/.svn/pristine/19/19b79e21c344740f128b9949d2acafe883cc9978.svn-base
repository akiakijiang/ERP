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
	$query_OK_goods = array();
	
	$party_id = $_REQUEST['party_id'];
	
	
	$uploader = new Helper_Uploader();
	
	$max_size = $uploader -> allowedUploadSize(); // 允许上传的最大值
	
	$final =array(message =>"", content =>array());
	
	$config = array('销售项目' =>
	            array('goods_number'=>'数量',
	                  'price'=>'价格',
	                  'barcode'=>'商品条码', //对应ecs_goods中的barcode
	                  'SKU'=>'样式条码' //对应ecs_goods_style中的barcode
	          ));
	          
	if(!$uploader->existsFile ('fileToUpload')){
		$final['message'] = '没有选择上传文件，或者文件上传失败';
	}
	
	//取得要上传的文件句柄
	if($final['message'] == ""){
		$file = $uploader->file ('fileToUpload');
	}
	
	//检查上传文件
	if($final['message'] == "" && !$file->isValid ('xls,xlsx', $max_size)){
		$final['message'] = "非法文件！请检查文件类型(xls,xlsx),并且系统限制的上传大小为'. $max_size /1024/1024 . 'MB'";
	}
	
	//读取excel
	if($final['message'] == ""){
	    $result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$final['message'] = reset ( $failed );
		}
	}
	
	if($final['message'] == ""){
		$rowset = $result ['销售项目'];
		if(empty($rowset)){
			$final['message'] = "excel文件中没有数据，请检查文件";
		}
	}
	
	if($final['message'] == ""){
		$in_goods_number = Helper_Array::getCols ($rowset,'goods_number');
		$in_price = Helper_Array::getCols($rowset, 'price');
		$in_barcode = Helper_Array::getCols ($rowset,'barcode');
		$in_sku = Helper_Array::getCols ($rowset,'SKU');
		
		$check_value_arr = array('goods_number'=>'数量',
		                         'price'=>'价格'
		                   );
		                   
	    foreach ( array_keys ( $check_value_arr ) as $val ) {
			$in_val = Helper_Array::getCols ( $rowset, $val );
			$in_len = count ( $in_val );
			Helper_Array::removeEmpty ( $in_val );
			if (empty ( $in_val ) || $in_len > count ( $in_val )) {
				$empty_col = true;
				$final['message'] = "文件中存在空的{$check_value_arr[$val]}，请确保前两列每一行都有数据";
			}
		}
	}
	
	if($final['message'] == ""){
		foreach ($in_goods_number as $item_value){
			if(!preg_match('/^[1-9]\d*$/', $item_value)){
				$final['message'] = "数量必须为正整数";
			}
		}
	}
	
	if($final['messgae'] == ""){
		foreach($in_price as $item_value){
			if($item_value<0){
				$final['message'] = "单价不能小于0";
			}
		}
	}
	
	if($final['message'] == ""){
		$goods_barcodes = array();
		$SKU_barcodes = array();
		$goods_sku = array();
		
		foreach ($rowset as $row){
			if($row['barcode'] == '' && $row['SKU'] == ''){
				$final['message'] = "商品条码和样式条码至少输入一项，请检查文件再导入";
				break;
			}
			
			if($row['barcode'] != '' && $row['SKU'] == ''){
				$sql = "select count(*)
				        from ecshop.ecs_goods eg
				        left join ecshop.ecs_goods_style egs on eg.goods_id = egs.goods_id and egs.is_delete=0
				        where eg.goods_party_id = '{$party_id}'
				        and egs.barcode is null 
				        and eg.barcode = '{$row['barcode']}'";
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
				    where egs.barcode = {$row['SKU']} and egs.is_delete=0
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
				    where egs.barcode = '{$row['SKU']}' and egs.is_delete=0
				    and eg.barcode = '{$row['barcode']}'
				    and eg.goods_party_id = {$party_id}
				 ";
				$result_num = $db->getOne($sql);
				if($result_num != "1"){
					$goods_sku[$row['barcode']] = $row['SKU'];
				}
				array_push($query_SKUS,$row['SKU']);
			}
			
			if(count($goods_barcode) > 0){
				$final['message'] = "根据以下商品条码找不到商品:";
				foreach($goods_barcodes as $goods_barcode){
					$final['message'] .= $goods_barcode.",";
				}
			}
			
			if(count($SKU_barcodes) > 0){
				$final['message'] .= "根据以下样式条码找不到样式：";
				foreach($SKU_barcodes as $SKU_barcode){
					$final['message'] .= $SKU_barcode.",";
				}
			}
			
			if(count($goods_sku) > 0){
				$final['message'] .= "以下商品条码和样式条码不匹配：";
				foreach(array_keys($goods_sku) as $key){
					$final['message'] .= "商品,".$key.",样式,".$goods_sku[$key].";";
				}
			}
		}
		
		if($final['message'] == ""){
			$sql = "
				select eg.goods_id,es.style_id,egs.barcode
				from ecshop.ecs_goods_style egs 
				inner join ecshop.ecs_goods eg on eg.goods_id = egs.goods_id
				inner join ecshop.ecs_style es on es.style_id = egs.style_id
				where goods_party_id ={$party_id} and egs.is_delete=0
				and egs.barcode " . db_create_in ( array_unique ( $query_SKUS ) );
			
			$SKUS = $db->getAll ( $sql );
			
			$sql = "
				select goods_id,0 style_id,barcode
				from ecshop.ecs_goods
				where goods_party_id = {$party_id}
				and barcode ".db_create_in($query_goods);
			$goods = $db->getAll ( $sql );
			
			$barcodes = Helper_Array::toHashmap ( array_merge($SKUS,$goods), 'barcode' );
		}
		
		if($final['message'] == ""){
			foreach($rowset as $row){
				$added_good = array();
				if($row['SKU'] != ''){
					$barcode = $row['SKU'];
				}else{
					$barcode = $row['barcode'];
				}
				$added_good[0] = $barcodes[$barcode]['goods_id'];
				$added_good[1] = $barcodes[$barcode]['style_id'];
				$added_good[2] = $row['goods_number'];
				$added_good[3] = $row['price'];
				
				array_push($final['content'],$added_good);
			}
		}
	 
	}
	
	QLog::log($final['message']);
	print 	$json->encode($final);

?>