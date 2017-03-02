<?php 
/**
 * 仓库库位管理
 */
define('IN_ECS', true);

require_once('includes/init.php');
admin_priv('facility_location_manage');
require_once('facility_location_manage_tools.php');


// 操作
$act = 
    !empty($_REQUEST['act']) && in_array($_REQUEST['act'], array('add'))
    ? $_REQUEST['act'] 
    : null ;

// 仓库列表
$facility_list = array_intersect_assoc ( facility_list(), get_user_facility () );


// 当前仓库
$facility_id = 
    !empty($_REQUEST['facility_id']) && array_key_exists($_REQUEST['facility_id'], $facility_list)
    ? $_REQUEST['facility_id']
    : key($facility_list) ;

$filter = array('facility_id'=>$facility_id);

// 当前所选仓库的库位
if ($facility_id) {
	$facility_location_list = facility_location_list_by_facility($facility_id);
	//pp(count($facility_location_list));
	$facility_location_list = FacilityOperation::SortFacilitiesById($facility_location_list);
	//pp(count($facility_location_list));
}

//导入库位Excel by wjzhu 2012-06-07
$product_import = $_REQUEST ['product_import'];
if ($product_import == "import") {
	$config = array(
    	'仓库库位' => array(
      		'facility_name' => '仓库名称',
        	'location_seq_id' => '库位标识',
		//	'area_id' => '区域标识',
		//	'aisle_id' => '过道标识',
		//	'section_id' => '仓间标识',
		//	'level_id' => '层次标识',
		//	'positionId' => '位置名称',
		  // 	'goods_name' => '商品名称',
		    'barcode' => '条码',
		//  'style_id' => '商品样式',
		//	'min_quantity' => '最小数量',
		//	'move_quantity' => '移动数量',
    	),
	);

	$message = '库位导入成功';
	do {
		if (!lock_acquire('product_import-upload')) {
			$message = '导入操作正在被执行，请稍后执行';
			break;
		}
		
		@set_time_limit(300);
		$uploader = new Helper_Uploader();
		$max_size = $uploader->allowedUploadSize();  // 允许上传的最大值
		if (!$uploader->existsFile('excel')) {
			$message = '没有选择上传文件，或者文件上传失败';
			break;
		}
	
		// 取得要上传的文件句柄,检查上传文件
		$file = $uploader->file('excel');
		if (!$file->isValid('xls, xlsx', $max_size)) {
			$message = '非法的文件0! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为'. $max_size/1024/1024 .'MB';
			break;
		}
	
		// 读取数据
		$data = excel_read($file->filepath(), $config, $file->extname(), $failed);
		if (!empty($failed)) {
			$message = reset($failed);
			break;
		}
		
		$product_facility_locations = array();
	 	foreach ($data['仓库库位'] as $key => $row) {
	 		// 检查商品名称
	 		$sql = '';
	 		if(empty( $row['location_seq_id']) || preg_match("/[\'.,:;*?~`!@#%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$row['location_seq_id'])){
	 			$message = "仓库库位不能为空 且不能包含特殊字符";
	 			break 2;
	 		}

	 		if(!empty($row['barcode']) ) {
	 		  $sql = " SELECT g.goods_id,ifnull(gs.style_id,0) style_id 
	 		             FROM ecshop.ecs_goods g left join ecshop.ecs_goods_style gs on g.goods_id=gs.goods_id  and gs.is_delete=0
	 		             WHERE g.barcode = '{$row['barcode']}' or gs.barcode = '{$row['barcode']}' ";	
			  $msg = "ERP不存在Excel中库位：". $row['location_seq_id'] . "对应的条码 ". $row['barcode'];
	 		}
	 		else {
	 		  $message = "条码 不能为空 " ;
	 		  break 2;
	 		}
	 		
	 		$goods_style = ( array ) $db->getAll ( $sql ); 
	 		if(!$goods_style) {	
	 			$message = $msg; 			
	 			break 2;
	 		}
	 		
	 		if(count($goods_style)>1) {
	 			// 同组织下商品条码重复才报错 ljzhou 2013-10-24
	 			$sql = " SELECT count(distinct(g.goods_id)) as party_num
	 		             FROM ecshop.ecs_goods g left join ecshop.ecs_goods_style gs on g.goods_id=gs.goods_id and gs.is_delete=0
	 		             WHERE g.barcode = '{$row['barcode']}' or gs.barcode = '{$row['barcode']}' 
                         group by g.goods_party_id
                         having party_num > 1";	
//                QLog::log('sql:'.$sql);
                $party_num = $db->getOne($sql);
                if(!empty($party_num)) {
                	$message= "Excel中库位". $row['location_seq_id'] . "对应的条码 ". $row['barcode'].",在系统中商品条码重复,同一组织中至少有2个相同的条码";
	 			    break 2;
                }
	 		}
	 			 		
       		$product_facility_locations[$key] = $row;
       		$product_facility_locations[$key]['goods_id'] = $goods_style[0]['goods_id'];
       		$product_facility_locations[$key]['style_id'] = $goods_style[0]['style_id'];
	 	}
		// 取得仓库名称
     	$facility_name_list = Helper_Array::getCols($product_facility_locations, 'facility_name');
   		if (count(array_unique($facility_name_list)) > 1 ) {
   			$message = '[仓库库位]表中仓库不一致';
    		break;                
   		}
   		
		if (facility_mapping($facility_id) != $facility_name_list[0]) {
   			$message = '[仓库库位]表中仓库和当前选中导入仓库不一致';
    		break;                
   		}
	 	
	 	try {
	 		$failed = array();
		 	$handle = soap_get_client('FacilityService', 'ROMEO');
		 	foreach ($product_facility_locations as $pfl){
				// 库位号全部转大写 ljzhou 2013.01.16
				$pfl['location_seq_id'] = strtoupper($pfl['location_seq_id']);	
				$result = $handle->findLocationByPrimaryKey(array("facilityId"=>$facility_id,"locationSeqId"=>$pfl['location_seq_id']));
		 		if ($result && is_object($result->return)) {
			 		// 库位已创建
		        } else {
		        	// 先创建库位
		        	$facilityLocation = new stdClass();
		        	$facilityLocation->facilityId = $facility_id;
		        	$facilityLocation->locationSeqId = $pfl['location_seq_id'];
		        	$facilityLocation->areaId = $pfl['area_id'];
		        	$facilityLocation->aisleId = $pfl['aisle_id'];
		        	$facilityLocation->sectionId = $pfl['section_id'];
		        	$facilityLocation->levelId = $pfl['level_id'];
		        	$facilityLocation->positionId = $pfl['positionId'];
			        $result = facility_location_save((object)$facilityLocation, $failed);
		        }
		        
		        // 库位上添加产品
		        $productFacilityLocation = new stdClass();
		        $productFacilityLocation->facilityId = $facility_id;
		        $productFacilityLocation->locationSeqId = $pfl['location_seq_id'];
				$productFacilityLocation->goodsId = $pfl['goods_id'];
				$productFacilityLocation->styleId = $pfl['style_id'];
				$productFacilityLocation->minQuantity = $pfl['min_quantity'];
				$productFacilityLocation->moveQuantity = $pfl['move_quantity'];
				$result = product_facility_location_save((object)$productFacilityLocation, $failed);
		 	}
		 	
		 	if (!empty($failed)) {
		 		$message = reset($failed);
		 		require_once(ROOT_PATH.'includes/debug/lib_log.php');
		 		QLog::log(reset($failed));
		 	}
		 	
		} catch (SoapFault $e) {
			$message = $e->getMessage();
			break;
		}
	}while (false);
	header("Location: facility_location_manage.php?facility_id={$facility_id}&message={$message}");
	exit();
}

//导出该库位的商品   by jrpei 2011-7-15
$product_export = $_REQUEST ['product_export'];
if ($product_export == "export") {
	FacilityOperation::LoadFacilityInfoToCSV(
		$smarty, $slave_db, $facility_location_list, $facility_id, $facility_list, $ref_fields, $ref_rowset);
}

$smarty->assign ('message', $_REQUEST['message']);
$smarty->assign ( 'facility_location_list', $facility_location_list );
$smarty->assign ( 'facility_list', $facility_list );
$smarty->assign ( 'filter', $filter );
$smarty->display ( 'oukooext/facility_location_manage.htm' );