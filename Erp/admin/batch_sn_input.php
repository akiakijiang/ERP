<?php
	 // 香港平世批量导入串号 zxcheng 2013.07.10
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
	do{
		$json = new JSON();
		$fileElementName = 'fileToUpload';
		$final = array();
		$final['message'] = '';
		$uploader = new Helper_Uploader ();
		$order_id = $_REQUEST['order_id'];
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		$config = array('串号汇总'  =>
					array('serial_number'=>'串号'
					));
		if (!$uploader->existsFile ( 'fileToUpload' )) {
			$final['message'] =  '没有选择上传文件，或者文件上传失败';
			break;
		}	
		//取得要上传的文件句柄
		$file = $uploader->file ( 'fileToUpload' );
		// 检查上传文件
		if ($final['message'] == "" && ! $file->isValid ( 'xls, xlsx', $max_size )) {
			$final['message'] = "非法的文件! 请检查文件类型(xls, xlsx), 并且系统限制的上传大小为". $max_size/1024/1024 . "MB";
			break;
		}
		// 读取excel
		if($final['message'] == ""){
			$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
			if (! empty ( $failed )) {
				$final['message'] = reset ( $failed );
				break;
			}
		}
		if($final['message'] == ""){
			$rowset = $result ['串号汇总'];
			if (empty ( $rowset )) {
				$final['message'] = "excel文件中没有数据,请检查文件";
				break;
			}
		}
		//获取excel中串号
		$serial_numbers = Helper_Array::getCols ( $rowset, 'serial_number');
	    //移除数组中重复的值
	    $unique_serial_numbers = array_unique($serial_numbers);
	    $new_serial_numbers = array();
	     foreach ($unique_serial_numbers as $key => $value) {
	     	 $new_serial_numbers[] = $value;
	     }
	    //验证串号是否已经存在
	    $error_serial_numbers = array();
	    $error_serial_numbers = get_error_serial_numbers($new_serial_numbers);
		if (!empty($error_serial_numbers)) {
			$final['message'] = "串号异常，导入串号已存在:".implode(',',$error_serial_numbers);
			break;
		}
		
		$not_in_number = get_order_not_in_number($order_id);
		Qlog::log('$not_in_number:'.$not_in_number.' count($serial_numbers):'.count($new_serial_numbers));
		//判断订单商品数量和串号数量
		if($not_in_number < count($new_serial_numbers)){
			$final['message'] = "串号异常，导入串号个数大于订单未入库商品数，请重新导入";
			break;
		}	
		
		foreach($new_serial_numbers as $serial_number) {
			Qlog::log('$serial_number:'.$serial_number);
		}
		
		$final['serial_numbers'] = $new_serial_numbers;
		
	}while(false);
	echo $json->encode($final);
?>