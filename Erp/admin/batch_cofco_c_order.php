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
	
	$uploader = new Helper_Uploader ();
	
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	
	$final = array(message => "",content => array());
	
	$config = array('采购项目'  =>
				array(
				  	'barcode'=>'商品条码',
				  	'goods_number'=>'数量',
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
		$in_barcode = Helper_Array::getCols ( $rowset, 'barcode');
		
		$check_value_arr = array('goods_number'=>'数量',
							);
		
		foreach ( array_keys ( $check_value_arr ) as $val ) {
			$in_val = Helper_Array::getCols ( $rowset, $val );
			$in_len = count ( $in_val );
			Helper_Array::removeEmpty ( $in_val );
			if (empty ( $in_val ) || $in_len > count ( $in_val )) {
				$empty_col = true;
				$final['message'] = "文件中存在空的{$check_value_arr[$val]}，请确保前一列每一行都有数据";
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
		$goods_barcodes = array();
		$SKU_barcodes = array();
		$goods_sku = array();
		
		foreach ($rowset as $row) {
			if($row['barcode'] == '') {
				$final['message'] = "商品条码必须输入,请检查文件再导入";
				break;
			}
			
			
			if($row['barcode'] != ''){
				$sql = "
					select count(*) from ecshop.ecs_goods g 
					left join ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id
					left join ecshop.ecs_style s ON s.style_id = gs.style_id
					WHERE g.goods_party_id = {$party_id} AND (gs.barcode='{$row['barcode']}' OR g.barcode = '{$row['barcode']}') and gs.is_delete=0;
				";
				$result_num = $db->getOne($sql);
				if($result_num != "1"){
					$goods_barcodes[] =  $row['barcode'];
				}
				array_push($query_goods,$row['barcode']);
			}
		}
		if(count($goods_barcodes) > 0){
			$final['message'] = "根据以下商品条码找不到商品：";
			foreach($goods_barcodes as $good_barcode){
				$final['message'] .= $good_barcode.",";
			}
		}
		
	}
	
	if($final['message'] == ""){
		
		$sql = " SELECT g.goods_id, IFNULL(s.style_id,0) as style_id , g.goods_name, IFNULL(s.value,'无颜色') as value, 
				IFNULL(gs.barcode, g.barcode) as barcode
				FROM ecshop.ecs_goods g 
				LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
				LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id 
				WHERE g.goods_party_id = {$party_id} AND (gs.barcode ".db_create_in($query_goods)." OR g.barcode ".db_create_in($query_goods).") ";
		$goods = ( array ) $db->getAll ( $sql );		
		$barcodes = Helper_Array::toHashmap ($goods, 'barcode' );
	}

	if($final['message'] == ""){
		foreach ($rowset as $row) {
			$added_good = array();
			
			$barcode = $row['barcode'];
			
			$added_good[0] = $barcodes[$barcode]['goods_name'];
			$added_good[1] = $barcodes[$barcode]['goods_id'];
			$added_good[2] = $barcodes[$barcode]['value'];
			$added_good[3] = $barcodes[$barcode]['style_id'];
			$added_good[4] = $row['goods_number'];
			$added_good[5] = 0;
			$added_good[6] = 'false'; 
			$added_good[7] = '无';
			$added_good[8] = round($added_good[5] * 100 * $added_good[4]) / 100;
			$added_good[9] = 0;
			$added_good[10] = 1.17;
			$added_good[11] = '';
			array_push($final['content'],$added_good);
		}
	}
	
	QLog::log($final['message']);
	echo $json->encode($final);
?>