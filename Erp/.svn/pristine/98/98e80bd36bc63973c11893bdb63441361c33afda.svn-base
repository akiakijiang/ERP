<?php
    // excel批量导入发票号
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
	global $db;	

    do{
    	Qlog::log('batch_in_invoice_sn start');
	    $json = new JSON();
		$fileElementName = 'fileToUpload';

		$final = array();
		$final['message'] = '';	
        $final['data'] = array();
        
		$uploader = new Helper_Uploader ();

		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
				
		$config = array('发票号码汇总'  =>
					array(
                      'order_sn'=>'ERP订单号',
					  'shipping_invoice_number'=>'发票号码'
					)
				 );

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
		
		//获取excel中发票号		
		if($final['message'] == ""){
			$order_invoice_sns = $result ['发票号码汇总'];
			if (empty ( $order_invoice_sns )) {
				$final['message'] = "excel文件中没有数据,请检查文件";
				break;
			}
		}			
		$order_sns = Helper_Array::getCols ( $order_invoice_sns, 'order_sn');
		$shipping_invoice_numbers = Helper_Array::getCols ( $order_invoice_sns, 'shipping_invoice_number');
		
		$order_sns = array_unique($order_sns);

	    $order_id_sns = array();
		//从数据库中获取 order_sn的order_id
		if (!empty($order_sns)) {		
			$sql = "select o.order_id, o.order_sn from ecshop.ecs_order_info o where  o.order_sn ".db_create_in($order_sns) ;
	        $order_id_sns = $db->getAll($sql);
   		}
   		
		foreach($order_invoice_sns as $order_invoice_sn) {
			foreach($order_id_sns as $order_id_sn) {
	        	if($order_invoice_sn['order_sn'] == $order_id_sn['order_sn']) {
	        		$data = array();
					$data['order_id'] = $order_id_sn['order_id'];
					$data['shipping_invoice_number'] = $order_invoice_sn['shipping_invoice_number'];
					$final['data'][] = $data;
					break;
	        	}
	        	
	        }
			
		}
				
    } while(false);

	Qlog::log('batch_in_invoice_sn end');
	echo $json->encode($final);
?>