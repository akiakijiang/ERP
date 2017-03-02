<?php
    // 分销出库批量导入串号 ljzhou 2013.06.14
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
    	Qlog::log('batch_in_sn start');
	    $json = new JSON();
		$fileElementName = 'fileToUpload';

		$final = array();
		$final['message'] = '';
		$final['product_serial_numbers'] = array();
		$order_id = $_REQUEST['order_id'];
		$facility_id = $_REQUEST['facility_id'];
		Qlog::log('batch_in_sn order_id:'.$order_id.' facility_id:'.$facility_id);
		
		// 检查订单是否在当前组织内
        $order_party = check_order_party($order_id);
        if (!$order_party) {
            $final['message'] =  "输入的订单 不存在,有可能是您组织没切换！";
            break;
        }
        
		$uploader = new Helper_Uploader ();
		
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
	     
	    //验证串号是否正常
	    $error_serial_numbers = get_pick_error_serial_numbers($new_serial_numbers,$order_id);
		if (!empty($error_serial_numbers)) {
			$final['message'] = "串号不属于该订单商品或者已经出库或者仓库不匹配：".implode(',',$error_serial_numbers);
			break;
		} else {
			$error_serial_numbers = array('');
		}
		$serial_numbers_right = array_diff($new_serial_numbers,$error_serial_numbers);

		$product_serial_numbers = get_product_serial_numbers($serial_numbers_right,$order_id);
		Qlog::log('$not_in_number:'.$not_in_number.' count($new_serial_numbers):'.count($new_serial_numbers).' count($serail_numbers_right):'.count($serail_numbers_right));
		
		$product_no_out_numbers  = get_order_product_no_out_numbers($order_id);
		$product_ids = array();
		foreach($product_serial_numbers as $key=>$product_serials) {
			Qlog::log('product_id:'.$key.' no_out_numbers:'.$product_no_out_numbers[$key]['not_out_number'].' serial_num:'.count($product_serials['serial_number']));
			
			if( $product_no_out_numbers[$key]['not_out_number'] < count($product_serials['serial_number']) ) {
				$final['message'] = $product_no_out_numbers[$key]['goods_name']."：未出库数".$product_no_out_numbers[$key]['not_out_number'].' 而你excel里面的数量大于它，串号：'.implode(',',$product_serials['serial_number']);
			    break;
			}
			foreach($product_serials['serial_number'] as $key2=>$serial) {
				Qlog::log('$product_id:'.$product_serials['product_id'].' $serial:'.$serial);
			}
			$product_ids[] = $key;
		}
		
		$final['product_serial_numbers'] = $product_serial_numbers;
		$final['product_ids'] = $product_ids;
		
    } while(false);
	
	Qlog::log('batch_in_sn end');
	
	echo $json->encode($final);
?>