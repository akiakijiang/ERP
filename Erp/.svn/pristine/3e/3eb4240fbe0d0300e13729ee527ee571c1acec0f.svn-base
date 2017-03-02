<?php 
/**
 * 仓库库位 商品管理(暂且只能确定ecco可用，因无法确定库位设计原因：romeo.location，romeo.inventory_location 还是 romeo.facility_location,romeo.product_facility_location???没有开发文档，历史技术数据库申请更改方式与仓库联系人表述不一致，无从着手。)
 */
define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('facility_location_manage');
require_once('facility_location_manage_tools.php');
$url = "location_goods_barcode.php";
// 仓库列表
$facility_list =array_intersect_assoc(get_available_facility(),get_user_facility());
$message = "";
$product_import = $_REQUEST ['product_import'];
if($product_import=="update_location_goods_barcode"){
	$facility_id = $_POST['batch_change_location_facility_id'];
	if(empty($facility_id)){
		$message = "请务必选择系统逻辑仓";
		header("Location: ".add_param_in_url($url, 'message', $message));
		exit;
	}
	$config = array(
		'更新库位商品'=>array(
	  		'location_barcode' => '库位',
	    	'goods_barcode' => '商品条码',
    	),
	);

	if (!lock_acquire('product_import-update_location_goods_barcode')) {
		$message = '批量移库位操作正在被执行，请稍后重试';
		header("Location: ".add_param_in_url($url, 'message', $message));
		exit;
	}
	
	@set_time_limit(300);
	$uploader = new Helper_Uploader();
	$max_size = $uploader->allowedUploadSize();  // 允许上传的最大值
	if (!$uploader->existsFile('excel')) {
		$message = '没有选择上传文件，或者文件上传失败';
		header("Location: ".add_param_in_url($url, 'message', $message));
		exit;
	}

	// 取得要上传的文件句柄,检查上传文件
	$file = $uploader->file('excel');
	if (!$file->isValid('xls, xlsx', $max_size)) {
		$message = '非法的文件！请检查文件类型类型（xls，xlsx），并且系统限制的上传大小为'. $max_size/1024/1024 .'MB';
		header("Location: ".add_param_in_url($url, 'message', $message));
		exit;
	}

	// 读取数据
	$data = excel_read($file->filepath(), $config, $file->extname(), $failed);
	if (!empty($failed)) {
		$message = reset($failed);
		header("Location: ".add_param_in_url($url, 'message', $message));
		exit;
	}
	
	$error_list = array();
	$product_facility_location_array = array();
 	foreach ($data['更新库位商品'] as $key => $row) {
 		// 检查库位是否存在，未删除，业务组是否匹配
 		$sql = '';
 		if(empty( $row['location_barcode']) || preg_match("/[\'.,:;*?~`!@#%^&+=)(<>{}]|\]|\[|\/|\\\|\"|\|/",$row['location_barcode'])){
 			$error_msg = "仓库库位不能为空 且不能包含特殊字符";
 			$error_list[]=array('row_number'=>$key+2,'location_barcode'=>$row['location_barcode'],'goods_barcode'=>$row['goods_barcode'],'error_msg'=>$error_msg);
 			continue;
 		}
 		$location_barcode = trim($row['location_barcode']);
 		$sql = "select location_id from romeo.location where party_id = {$_SESSION['party_id']} and location_barcode = '{$location_barcode}' and is_delete = 0 and location_type = 'IL_LOCATION' ";
		$location_id = $db->getOne($sql);
		if(empty($location_id)){
			$error_msg = "库位并不在这个业务组中，或者已经被删除";
 			$error_list[]=array('row_number'=>$key+2,'location_barcode'=>$row['location_barcode'],'goods_barcode'=>$row['goods_barcode'],'error_msg'=>$error_msg);
 			continue;
		}
		
		
		$goods_barcode = trim($row['goods_barcode']);
		if(empty($goods_barcode)){
			$error_msg = "商品条码不能为空";
 			$error_list[]=array('row_number'=>$key+2,'location_barcode'=>$row['location_barcode'],'goods_barcode'=>$row['goods_barcode'],'error_msg'=>$error_msg);
 			continue;
		}
		// 同组织下商品条码重复才报错 ljzhou 2013-10-24
		$sql = " SELECT count(distinct(g.goods_id)) as party_num
	             FROM ecshop.ecs_goods g left join ecshop.ecs_goods_style gs on g.goods_id=gs.goods_id and gs.is_delete=0
	             WHERE g.barcode = '{$goods_barcode}' or gs.barcode = '{$goods_barcode}' 
                 group by g.goods_party_id
                 having party_num > 1";	
        $party_num = $db->getOne($sql);
        if(!empty($party_num)) {
		    $error_msg = "商品条码重复，同一组织中至少有2个相同的条码";
 			$error_list[]=array('row_number'=>$key+2,'location_barcode'=>$row['location_barcode'],'goods_barcode'=>$row['goods_barcode'],'error_msg'=>$error_msg);
 			continue;
        }
        //检查商品是否存在    
		$sql = "SELECT pm.PRODUCT_ID from ecshop.ecs_goods g 
			INNER JOIN romeo.product_mapping pm on pm.ECS_GOODS_ID = g.goods_id 
			LEFT JOIN ecshop.ecs_goods_style gs on pm.ECS_GOODS_ID = gs.goods_id and pm.ECS_STYLE_ID = gs.style_id and gs.is_delete=0
			where if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) ='{$goods_barcode}' and g.is_delete = 0 and g.goods_party_id = {$_SESSION['party_id']} ";
		$product_id = $db->getOne($sql);
		if(empty($product_id)){
			$error_msg = "系统中不存在此商品条码";
 			$error_list[]=array('row_number'=>$key+2,'location_barcode'=>$row['location_barcode'],'goods_barcode'=>$row['goods_barcode'],'error_msg'=>$error_msg);
 			continue;
		}
		if(!in_array($location_barcode.",".$goods_barcode.",".$location_id.",".$product_id,$product_facility_location_array) && empty($error_list)){
			$product_facility_location_array[] = $location_barcode.",".$goods_barcode.",".$location_id.",".$product_id;
		}
		
 	}
 	
 	if(empty($error_list) && $message ==""){
 		if(empty($product_facility_location_array)){
 			$message = "没有筛选到需要维护的组合";
 			header("Location: ".add_param_in_url($url, 'message', $message));
			exit;
 		}else{
 			//开始处理 库位商品组合
 			// 1. 删除所有party_id,facility_id库位商品信息 romeo.inventory_location
 			$sqla = "delete from romeo.inventory_location where party_id = {$_SESSION['party_id']} and facility_id = '{$facility_id}' and location_barcode like '%-%' ";
 			// 2. 插入信息齐全的库位商品信息
 			$insert_array = array(); 
 			foreach($product_facility_location_array as $location_goods_barcode){
 				$location_goods_barcode_array = explode(",",$location_goods_barcode);
 				$location_barcode = $location_goods_barcode_array[0];
 				$goods_barcode = $location_goods_barcode_array[1];
 				$location_id = $location_goods_barcode_array[2];
 				$product_id = $location_goods_barcode_array[3];
 				$insert_array[] = "('{$location_barcode}','{$goods_barcode}','{$product_id}',100000,100000,{$_SESSION['party_id']},'{$facility_id}','INV_STTS_AVAILABLE','{$_SESSION['admin_name']}',NOW(),NOW(),'{$location_id}')"; 
 			}
 			$sqlb = "INSERT INTO romeo.inventory_location(location_barcode,goods_barcode,product_id,goods_number,available_to_reserved,party_id,facility_id,status_id,action_user,created_stamp,last_updated_stamp,location_id) 
	values".implode(",",$insert_array).";";
 			
 			if($db->query($sqla) && $db->query($sqlb)){
 				$message = "库位商品对应更新成功";
 			}else{
 				$message = "更新失败，恐是与手动操作者冲突，请重试";
 			}
 		}
 	}
 	$smarty->assign('error_list',$error_list);
}elseif($product_import=='location_export'){
	$sql="select location_barcode,is_delete from romeo.location where party_id = {$_SESSION['party_id']} and location_type = 'IL_LOCATION' ";
	$location_export = $db->getAll($sql);
	if(empty($location_export)){
		$message = "没有查到任何库位信息";
	}else{
		set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
        require_once 'PHPExcel.php';
        require_once 'PHPExcel/IOFactory.php';
        $filename = party_mapping($_SESSION['party_id'])."库位信息";
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);        
        $sheet = $excel->getActiveSheet();
        $sheet->setCellValue('A1', "库位");
        $sheet->setCellValue('B1', "是否已停用");
        $i=2;
        foreach ($location_export as $item) {   
            $sheet->setCellValueExplicit("A{$i}", $item['location_barcode'], PHPExcel_Cell_DataType::TYPE_STRING);
            if( $item['is_delete'] == 0){
                 $sheet->setCellValue("B{$i}", '否');
            }else {
            	 $sheet->setCellValue("B{$i}", '是');
            }
            $i++;
        }
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        }
	}
}elseif($product_import=='location_barcode_export'){
	$facility_id = $_POST['select_location_barcode_facility_id'];
	if(empty($facility_id)){
		$message = "请务必选择系统逻辑仓";
		header("Location: ".add_param_in_url($url, 'message', $message));
		exit;
	}
	$sql = "select location_barcode,goods_barcode from romeo.inventory_location where party_id = {$_SESSION['party_id']} and facility_id = '{$facility_id}' and location_barcode like '%-%'";
	$location_barcode_export = $db->getAll($sql);
	if(empty($location_barcode_export)){
		$message = "没有查到任何库位商品对应信息";
	}else{
		set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
        require_once 'PHPExcel.php';
        require_once 'PHPExcel/IOFactory.php';
        $filename = party_mapping($_SESSION['party_id']).facility_mapping($facility_id)."库位商品信息";
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);        
        $sheet = $excel->getActiveSheet();
        $sheet->setCellValue('A1', "库位");
        $sheet->setCellValue('B1', "商品条码");
        $i=2;
        foreach ($location_barcode_export as $item) {   
            $sheet->setCellValueExplicit("A{$i}", $item['location_barcode'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("B{$i}", $item['goods_barcode'], PHPExcel_Cell_DataType::TYPE_STRING);
            $i++;
        }
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        }
	}
}

$smarty->assign ('message', $message);
$smarty->assign('party_name',party_mapping($_SESSION['party_id']));
$smarty->assign ( 'facility_list', $facility_list );
$smarty->display ( 'oukooext/location_goods_barcode.htm' );