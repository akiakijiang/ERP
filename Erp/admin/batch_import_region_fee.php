<?php
    // 批量导入区域费用
	define('IN_ECS', true);
	require('includes/init.php');
	require('function.php');
	include_once(ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
    do{
	    $json = new JSON();
		$fileElementName = 'fileToUpload';

		$final = array();
		$final['message'] = '';

		$uploader = new Helper_Uploader ();
		
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		
		$config = array('地区费用列表'  =>
					array(
                      'facility_name'=>'仓库',
                      'shipping_name'=>'快递',
                      'province_name'=>'省',
                      'city_name'=>'市',
                      'district_name'=>'区',
                      'first_weight'=>'首重',
                      'first_fee'=>'首重费',
                      'continued_fee'=>'续重费',
                      'tracking_fee'=>'面单费',
                      'operation_fee'=>'操作费',
                      'weighing_fee'=>'过磅费',
                      'transit_fee'=>'中转费',
                      'lowest_transit_fee'=>'最低中转费',
                      'time_arrived_weight'=>'时效权重',
                      'service_weight'=>'售后权重',
                      'arrived_weight'=>'可达性权重'
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
			$rowset = $result ['区域费用'];
			if (empty ( $rowset )) {
				$final['message'] = "excel文件中没有数据,请检查文件";
				break;
			}
		}
		var_dump($rowset);
		//检测仓库
		$facility_names = Helper_Array::getCols ( $rowset, 'facility_name');
		$sql = "select facility_id,facility_name from romeo.facility where facility_name ".db_create_in($facility_names);
		$facility_id_names = $db->getAll($sql);
		$facilitys = array();
		foreach($facility_id_names as $facility_id_name) {
			$facilitys[$facility_id_name['facility_name']] = $facility_id_name['facility_id'];
		}
		
		//检测快递
		$shipping_names = Helper_Array::getCols ( $rowset, 'shipping_name');
		$sql = "select shipping_id,shipping_name from ecshop.ecs_shipping where shipping_name ".db_create_in($shipping_names);
		$shipping_id_names = $db->getAll($sql);
		$shippings = array();
		foreach($shipping_id_names as $shipping_id_name) {
			$shippings[$shipping_id_name['shipping_name']] = $shipping_id_name['shipping_id'];
		}
		
		//检测省
		$province_names = Helper_Array::getCols ( $rowset, 'province_name');
		$sql = "select region_id,region_name from ecshop.ecs_region where region_name ".db_create_in($province_names);
		$province_id_names = $db->getAll($sql);
		$provinces = array();
		foreach($province_id_names as $province_id_name) {
			$provinces[$province_id_name['region_name']] = $province_id_name['region_id'];
		}
		
		//检测市
		$city_names = Helper_Array::getCols ( $rowset, 'city_name');
		$sql = "select region_id,region_name from ecshop.ecs_region where region_name ".db_create_in($city_names);
		$city_id_names = $db->getAll($sql);
		$citys = array();
		foreach($city_id_names as $city_id_name) {
			$citys[$city_id_name['region_name']] = $city_id_name['region_id'];
		}

		//检测区
		$district_names = Helper_Array::getCols ( $rowset, 'district_name');
		$sql = "select region_id,region_name from ecshop.ecs_region where region_name ".db_create_in($district_names);
		$district_id_names = $db->getAll($sql);
		$districts = array();
		foreach($district_id_names as $district_id_name) {
			$districts[$district_id_name['region_name']] = $district_id_name['region_id'];
		}
		
		$region_fees = $rowset;
		
		$message = '';
		foreach($region_fees as $region_fee) {
			if(empty($facilitys[$region_fee['facility_name']])) {
				$message .= "仓库有误：".$region_fee['facility_name']." \n";
			}
			if(empty($shippings[$region_fee['shipping_name']])) {
				$message .= "快递有误：".$region_fee['shipping_name']." \n";
			}
			if(empty($provinces[$region_fee['province_name']])) {
				$message .= "省有误：".$region_fee['province_name']." \n";
			}
			if(empty($citys[$region_fee['city_name']])) {
				$message .= "市有误：".$region_fee['city_name']." \n";
			}

			// 直辖市只到市
			if(!in_array($provinces[$region_fee['province_name']],$zhixiashi)) {
				if(empty($districts[$region_fee['district_name']])) {
					$message .= "区有误：".$region_fee['district_name']." \n";
				}
			}	
		}
		
		if(!empty($message)) {
			
		}
		var_dump($region_fees);
		die();
		foreach($region_fees as $region_fee) {
			$facility_id = $facilitys[$region_fee['facility_name']];
			$shipping_id = $shippings[$region_fee['shipping_name']];
			if(!in_array($provinces[$region_fee['province_name']],$zhixiashi)) {
				$region_id = $districts[$region_fee['district_name']];
			} else {
				$region_id = $citys[$region_fee['city_name']];
			}
			
			$region_fee['facility_id'] = $facility_id;
			$region_fee['shipping_id'] = $shipping_id;
			$region_fee['region_id'] = $region_id;
			
			$sql = "select 1 from ecshop.ecs_express_fee where facility_id = '{$facility_id}' and shipping_id='{$shipping_id}' and region_id='{$region_id}' limit 1";
			$is_exist = $db->getOne($sql);
			if($is_exist) {
				update_region_fee($region_fee);
			} else {
				insert_region_fee($region_fee);
			}
		}
		
    } while(false);
	
	Qlog::log('batch_import_region_fee end');
	
	echo $json->encode($final);
?>