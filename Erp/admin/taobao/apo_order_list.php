<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . 'includes/helper/uploader.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('taobao_zhixiao_order_list');

$status_list = array(
	'ALL' => 'ALL',
	'NULL' => '未初始化',
	'INIT' => '已初始化',
	'WRITTEN' => '已推送',
	'SHIPPED' => '已发货',
	'CANCEL' => '已取消',
	'ERROR' => '错误'
);

$order_status = array(
		0 => '未确认',
		1 => '已确认',
		2 => '取消',
);

$shipping_status = array(
		0 => '待配货',
		1 => '已发货',
		2 => '收货确认',
		15 => '已确认，未预定'
);

$smarty->assign('status_list', $status_list);
	
$request = isset($_REQUEST['request']) ? trim($_REQUEST['request']) : null ;
$act = $_REQUEST['act'] != '' ? $_REQUEST['act']:null;			

if($act == "search"){
	$condition = get_condition();

	//apo ecshop.brand_apo_order_info
	$sql = "SELECT o.taobao_order_sn, o.order_time, o.order_status, o.shipping_status, b.status, b.barcode_id, o.consignee, o.mobile,o.postscript
			FROM ecshop.ecs_order_info o 
			LEFT JOIN ecshop.brand_apo_order_info b ON b.order_id =o.order_id  and o.taobao_order_sn = b.taobao_order_sn
			WHERE o.taobao_order_sn <> '' ".$condition." order by o.order_time desc";
	$result_list = $db->getAll($sql);
	$order_list = array();
	foreach($result_list as $index=>$result) {
		if($result['status']=='ERROR') {
			$advice = '请联系ERP';
		} 
		$result['status'] = $status_list[$result['status']];
		if(empty($result['status'])) {
			$result['status'] = $status_list['NULL'];
		}
		
		$result['order_status'] = $order_status[$result['order_status']];
		$result['shipping_status'] = $shipping_status[$result['shipping_status']];
		
		$result['advice'] = $advice;
		$order_list[$index] = $result;
	}
} else if($act == "apo_upload") {
	$return_message = insert_apo_info();
	$smarty->assign ('message', $return_message );
	
} else if($act == "status_return") {
	$change_order_sn = $_POST['change_taobao_order_sn'];

	$sql = "select s.tid 
			from ecshop.sync_taobao_order_info s
			left join ecshop.ecs_order_info o on o.taobao_order_sn = s.tid
			where s.party_id=65666 and s.status='WAIT_BUYER_CONFIRM_GOODS' and o.order_id is null 
				and s.tid='$change_order_sn'";
	$exist = $db->getAll($sql);
	
	if(!empty($exist)) {
		$res = false;
		$sql = "update ecshop.sync_taobao_order_info set status='WAIT_SELLER_SEND_GOODS' where tid='$change_order_sn' limit 1";
		$res = $db->query($sql);
		if($res) {
			$message = "淘宝订单回退状态成功！ $change_order_sn";
		} else {
			$message = "淘宝订单回退状态失败 !";
		}
	} else {
		$message = "淘宝订单回退状态失败，请确认系统中是否存在: $change_order_sn";
	}
	$smarty->assign ('message', $message );
	
} else if($act == "change_pzn") {
	$original_pzn = trim($_POST['original_pzn']);
	$new_pzn 	  = trim($_POST['new_pzn']);
	
	$sql = "select pzn 	from ecshop.brand_apo_product where pzn='$original_pzn'";
	$exist = $db->getAll($sql);

	if(!empty($exist) && !empty($new_pzn)) {
		$res = false;
		$sql = "update ecshop.brand_apo_product set pzn='$new_pzn' where pzn='$original_pzn' limit 1";
		$res = $db->query($sql);
		if($res) {
			$message = "pzn成功！ $new_pzn";
		} else {
			$message = "pzn修改失败，请确认系统中是否存在pzn: $original_pzn";
		}
	} else {
		$message = "pzn修改失败，请确认系统中是否存在pzn: $original_pzn";
	}
	$smarty->assign ('message', $message );
} else if($act == "download") {
// 	download_orders();
}



$smarty->assign('valide_date',date('Y-m-d H:i:s',strtotime('-5 day')));
$smarty->assign('taobao_order_sn',trim($_REQUEST['taobao_order_sn']));
$smarty->assign('wlb_order_sn',trim($_REQUEST['wlb_order_sn']));
$smarty->assign('status',trim($_REQUEST['status']));
$smarty->assign('order_list',$order_list);
$smarty->display('taobao/apo_order_list.htm');

function get_condition(){
	$startTime = isset($_REQUEST['startTime']) ? $_REQUEST['startTime'] : '';
	$endTime = isset($_REQUEST['endTime']) ? $_REQUEST['endTime'] : '';
	$condition = "";
	if( trim($_REQUEST['taobao_order_sn']) != '' ){
		$condition .= " AND o.taobao_order_sn='".trim($_REQUEST['taobao_order_sn'])."'";
	}
	if( trim($_REQUEST['wlb_order_sn']) != '' ){
		$condition .= " AND b.barcode_id = '".trim($_REQUEST['wlb_order_sn'])."'";
	}
	if( trim($_REQUEST['status']) != 'ALL' ){
		$condition .= " AND b.status = '".trim($_REQUEST['status'])."'";
	}
	if($startTime && $endTime){
		$condition .= " and o.order_time > '".trim($_REQUEST['startTime'])."' and o.order_time <= '".trim($_REQUEST['endTime'])."'";
	}
	$condition .= " AND o.party_id = 65666 ";

	
	return $condition;
}


function insert_apo_info () {
	global $db;
	// excel读取设置
	$tpl = array('apo'  =>
			array('taobao_order_sn'=>'交易订单号',
					'barcode_id'=>'物流订单号'
			));
	/* 文件上传并读取 */
	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值

	if (! $uploader->existsFile ( 'excel' )) {
		$message = '没有选择上传文件，或者文件上传失败';
		return $message;
	}

	// 取得要上传的文件句柄
	$file = $uploader->file ( 'excel' );

	// 检查上传文件
	if (! $file->isValid ( 'xls, xlsx', $max_size )) {
		$message = '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
		return $message;
	}

	// 读取excel
	$result = excel_read ( $file->filepath(), $tpl, $file->extname (), $failed, true);
	if (! empty ( $failed )) {
		$message = reset ( $failed );
		return $message;
	}

	/* 检查数据  */
	$rowset = $result ['apo'];

	// 订单数据读取失败
	if (empty ( $rowset )) {
		$message = 'excel文件中没有数据,请检查文件';
		return $message;
	}

	$order_check_array = array('taobao_order_sn','barcode_id'	);
	foreach ($order_check_array as $check_column) {
		$region_array = Helper_Array::getCols($rowset, $check_column);
		$region_size = count($region_array);
		Helper_Array::removeEmpty ($region_array);
		if($region_size > count($region_array)){
			$message = '【'.$tpl['apo订单导入'][$check_column].'】为必填数据，请检查该数据是否填写完整！';
			return $message;
		}
	}

	$insert_count = 0;
	$update_count = 0;
	foreach ($rowset as $row) {
		$taobao_order_sn =$row['taobao_order_sn'];
		$barcode_id  	 =$row['barcode_id'];

		// 在 brand_apo_order_info 中插入数据
		$sql = "select taobao_order_sn
		from ecshop.brand_apo_order_info
		where taobao_order_sn='$taobao_order_sn' ";
		$is_exist = $db->getAll($sql);

		if(empty($is_exist)) {
			$sql = "insert into ecshop.brand_apo_order_info
			(taobao_order_sn,barcode_id)
			values
			('{$taobao_order_sn}','{$barcode_id}')
			";
			$insert_count++;
		} else {
			$sql = "update  ecshop.brand_apo_order_info
			set barcode_id='{$barcode_id}' where taobao_order_sn ='{$taobao_order_sn}'
			";
			$update_count++;
		}

		$res = $db->query($sql);
	}

	$return_message = "导入成功!  <br/> 插入" .$insert_count ."条数据！ <br/>  更新：".$update_count ."条数据！";

	$file->unlink ();
	return $return_message;
}

function download_orders() {
	global $db;
	$condition = get_condition();
	$sql = "SELECT b.apo_order_id
			FROM ecshop.brand_apo_order_info b
			LEFT JOIN ecshop.ecs_order_info o ON b.order_id =o.order_id  and o.taobao_order_sn = b.taobao_order_sn
			WHERE b.apo_order_id <> '' ".$condition." order by o.order_time desc";
	
	$apo_order_ids = $db->getAll($sql);
	
}

?>

