<?php
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
include_once(ROOT_PATH . 'includes/cls_json.php');


$act = $_REQUEST['act'];
$party_id = $_SESSION['party_id'];
$party_name = party_mapping($party_id);
$flag = false;
$message = '';
$location_type_name_list = array('IL_LOCATION' => '库位', 'IL_GROUDING' => '上架容器', 'IL_VARIANCE' =>'库存调整虚拟库位' );
//搜索
if(!empty($act) && $act == 'search') {
	$location_barcode = trim(isset($_REQUEST['location_barcode']) ? $_REQUEST['location_barcode'] : null);
	$goods_name = trim(isset($_REQUEST['goods_name']) ? $_REQUEST['goods_name'] : null);
	$goods_barcode = trim(isset($_REQUEST['goods_barcode']) ? $_REQUEST['goods_barcode'] : null);
	$location_status = isset($_REQUEST['location_status']) ? $_REQUEST['location_status'] : null;
	$location_type = isset($_REQUEST['location_type']) ? $_REQUEST['location_type'] : null;
	$action_user = trim($_REQUEST ['action_user']);
	$start_validity_time = trim($_REQUEST ['start_validity_time']);
	$end_validity_time = trim($_REQUEST ['end_validity_time']);
	$smarty->assign("location_barcode",$location_barcode);
	$smarty->assign("is_delete",$is_delete);
	$smarty->assign("location_type",$location_type);
	if ($start_validity_time || $end_validity_time){
		if ($start_validity_time) {
			$condition .= "  l.created_stamp >= '{$start_validity_time}' and ";
		}
		if ($end_validity_time) {
			$end_validity_time++;
			$condition .= " l.created_stamp <= '{$end_validity_time}' and ";
		}
	}
	if (!empty($action_user)) {
		$condition .= 'l.action_user like \'%' . $action_user . '%\' and ';
	}
	if (!empty($location_barcode)) {
		$condition .= 'l.location_barcode like \'%' . $location_barcode . '%\' and ';
	}
	if (!empty($goods_name)) {
		$condition .= 'g.goods_name like \'%' . $goods_name . '%\' and ';
	}
	if (!empty($goods_barcode)) {
		$condition .= "il.goods_barcode = '{$goods_barcode}' and ";
	}
	if ($location_status == 'EMPTY') {
		$condition .= ' il.inventory_location_id is null  and ';
	} else if ($location_status == 'NOT_EMPTY'){
		$condition .= ' il.inventory_location_id is not null and ';
	} 
	if (!empty($location_type) && $location_type != 'ALL') {
		$condition .= 'l.location_type = \'' . $location_type . '\' and ';
	}
	$condition .= " l.location_type <> 'IL_VARIANCE' ";
	$sql = "SELECT l.location_barcode,l.action_user,l.created_stamp,p.name as party_name,
			if(l.location_type = 'IL_GROUDING', '上架容器', '库位') as location_type,
			if(il.inventory_location_id is null, '空', '非空') as location_status
			    from romeo.location l
				LEFT JOIN romeo.inventory_location il ON l.location_id = il.location_id
				LEFT JOIN romeo.party p ON p.party_id = l.party_id
				left join romeo.product_mapping pm ON il.product_id = pm.product_id
				left join ecshop.ecs_goods g on pm.ECS_GOODS_ID = g.goods_id
				left join ecshop.ecs_goods_style gs on pm.ECS_STYLE_ID = gs.style_id and pm.ECS_GOODS_ID = gs.goods_id and gs.is_delete=0
				WHERE l.is_delete = 0 and ".party_sql('l.party_id').' and '. $condition . '  group by l.location_barcode order by l.location_barcode';
	// Qlog::log("搜索成功=".$sql);
	$result = $db->getAll($sql);
	$size = sizeof($result);
	if(empty($result)){
		$message .= "  业务组(".$party_name.")中没有你要搜索的容器 ";
	}else{
		$flag = true;
	    $message .= "  成功搜索".$size."条记录";
	    $smarty->assign("locations", $result);
	}
	/**
	All Hail Sinri Edogawa!
	For printing
	**/
	$sinri_barcodes="";
	$sinri_location_list=array();
	foreach ($result as $key => $value) {
		if($sinri_barcodes!="")$sinri_barcodes.=",";
		$sinri_barcodes.=$value['location_barcode'];
		$sinri_location_list[]=$value['location_barcode'];
	}
	$smarty->assign('sinri_all_print',$sinri_barcodes);
	$smarty->assign('sinri_all_post',$sinri_location_list);
} else if (!empty($act) && $act == 'delete') {
	$location_barcode = isset($_REQUEST['location_barcode']) ? $_REQUEST['location_barcode'] : null;
	$_REQUEST['location_barcode'] = null;
	if (deleteLocation($location_barcode)) {
		$flag = true;
		$message .= "  删除成功";
	} else {
		$message .= "  删除失败";
	}
} else if (!empty($act) && $act == 'start') {
	$location_barcode = isset($_REQUEST['location_barcode']) ? $_REQUEST['location_barcode'] : null;
	$location_type = isset($_REQUEST['location_type']) ? $_REQUEST['location_type'] : null;
	$_REQUEST['location_barcode'] = null;
	if ($location_type == '上架容器') {
		$location_type = 'IL_GROUDING';
	} else {
		$location_type = 'IL_LOCATION';
	}
	//因仓库删除是为了新建，所以重启直接调新建的api就行了
	if (createLocation($party_id,$location_barcode,$location_type)) {
	    $flag = true;
		$message .= "重启成功!";
	} else {
		$message .= "重启失败！";
	}
}else if (!empty($act) && $act == 'add') {
	$barcode = trim(isset($_REQUEST['barcode']) ? $_REQUEST['barcode'] : null);
	$sql = "
		select 1 from romeo.location l
		where l.location_barcode = '{$barcode}' and l.is_delete='0'
	";
	$result = $db->getRow($sql);
	if (empty($result)) {
		$type = 'IL_LOCATION';
		if (createLocation($party_id, $barcode,$type)) {
			$message .= "新建成功!";
			$flag = true;
			$sql = "
				SELECT l.location_barcode,'库位' as location_type, p.name as party_name,l.action_user,l.created_stamp,'空' as location_status
				   FROM romeo.location l
				   left join romeo.party p on l.party_id = p.party_id
				   WHERE l.CREATED_stamp = (SELECT MAX(created_stamp)from romeo.location)
			  ";
		    $results = $db->getAll($sql);
            $smarty->assign("locations", $results);
		} else {
			$message .= "新建失败！";
		}
	} else {
		$message .= '该条码已存在';
	}
} else if (!empty($act) && $act == 'group_add') {
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	//先找到location_id的最后一个值
	$sql = "SELECT MAX(location_id) from romeo.location";
	$max_location_id = $db->getOne($sql);
	$i = 0;
	$j = 0;
	$prompt ='';
	$success = true;
	$barcode_is_exist = false;
	while(1) {
		// excel读取设置
		$tpl =
		array ('容器导入' =>
		         array ('barcode' => '库位条码'
		                 ) );

		/* 文件上传并读取 */
		@set_time_limit ( 300 );
		$uploader = new Helper_Uploader ();
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值


		if (! $uploader->existsFile ( 'excel' )) {
			$smarty->assign ( 'message', '没有选择上传文件，或者文件上传失败' );
			break;
		}


		// 取得要上传的文件句柄
		$file = $uploader->file ( 'excel' );

		// 检查上传文件
		if (! $file->isValid ( 'xls, xlsx', $max_size )) {
			$message .= "   " . '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
			break;
		}
		// 读取excel
		$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
		if (! empty ( $failed )) {
			$message .= "   " . reset ( $failed );
			break;
		}

		//判断是否符合条件
		if (sizeof($result['容器导入']) == 0) {
			$message .= "   导入的数据为空";
			break;
		}
		foreach ($result['容器导入'] as $value) {
			$j++;
			$barcode = $value['barcode'];
			$type = 'IL_LOCATION';
			//判断条码是否错误

			if (!preg_match("/^[A-Z1-9][A-Z]-[A-Z]-\d\d-\d\d$/",$barcode) && !preg_match("/^[A-Z1-9][A-Z]-[A-Z][A-Z]-\d\d-\d\d$/",$barcode)) {
				$message .= "   导入的库位条码有误（第".$j."行）".":".$barcode;
				break 2;
			}
			//判断条码是否存在
			$sql = "select 1 from romeo.location l
		            where l.location_barcode = '{$barcode}' and l.is_delete='0' ";
			$exist = $db->getRow($sql);
			if (!empty($exist)) {
				$barcode_is_exist = true;
				$prompt .= $barcode .'、';
				continue;
			}
			$i++;
			$res = createLocation($party_id,$barcode,$type);
			if (!$res) {
				$success = false;
				$i--;
			}
		}
		if (!$success) {
			$message .= '新建失败';
			break;
		}
		$message .= "   成功导入" . $i . "个容器";
		if($barcode_is_exist){
		  $message .='，提示：'.$prompt.'库位已经存在';
		}
		break;
	}
	//将批量导入的库位信息通过列表显示出来
	$sql = "SELECT l.location_barcode,'库位' as location_type, p.name as party_name,l.action_user,l.created_stamp,'空' as location_status
				   FROM romeo.location l
				   left join romeo.party p on l.party_id = p.party_id
				   WHERE l.location_id > $max_location_id ";
	$results = $db->getAll($sql);
	$smarty->assign("locations", $results);
} else if (!empty($act) && $act == 'show_list') {

	$location_barcode = isset($_REQUEST['location_barcode']) ? $_REQUEST['location_barcode'] : null;

	$sql = "
		select goods.goods_id, il.location_barcode, il.is_serial,
		CONCAT(goods.goods_name,' ',IF (goods_style.goods_color = '' or goods_style.goods_color is null , ifnull(style.color, ''), ifnull(goods_style.goods_color, '')))as goods_name,
		il.goods_barcode,  il.available_to_reserved, il.validity, p.NAME as party_name, f.facility_name, il.status_id,
		loc.location_type, il.action_user, il.created_stamp, il.last_updated_stamp
		from romeo.inventory_location il
		inner join romeo.facility as f on f.facility_id = il.facility_id
		inner join romeo.party as p on p.party_id = il.party_id
		inner join romeo.product_mapping as mapping on mapping.product_id = il.product_id
		LEFT JOIN ecshop.ecs_goods AS goods ON goods.goods_id = mapping.ECS_GOODS_ID
		LEFT JOIN ecshop.ecs_goods_style AS goods_style ON goods_style.style_id = mapping.ECS_STYLE_ID and goods_style.goods_id = mapping.ECS_GOODS_ID and goods_style.is_delete=0
		LEFT JOIN ecshop.ecs_style AS style on style.style_id = mapping.ECS_STYLE_ID
		LEFT JOIN romeo.location AS loc on loc.location_barcode = il.location_barcode
		where il.location_barcode='{$location_barcode}' and il.is_serial = 0 and il.goods_number > 0

		union all

		select goods.goods_id, serial.location_barcode, serial.serial_number,
		CONCAT(goods.goods_name,' ',IF (goods_style.goods_color = '' or goods_style.goods_color is null , ifnull(style.color, ''), ifnull(goods_style.goods_color, '')))as goods_name,
		il.goods_barcode,  '' as available_to_reserved, il.validity, p.NAME as party_name, f.facility_name, il.status_id,
		loc.location_type, il.action_user, il.created_stamp, il.last_updated_stamp
		from romeo.inventory_location il
		inner join romeo.facility as f on f.facility_id = il.facility_id
		inner join romeo.party as p on p.party_id = il.party_id
		inner join romeo.product_mapping as mapping on mapping.product_id = il.product_id
		INNER JOIN romeo.location_barcode_serial_mapping AS serial ON serial.location_barcode = il.location_barcode and serial.product_id = il.product_id
		LEFT JOIN ecshop.ecs_goods AS goods ON goods.goods_id = mapping.ECS_GOODS_ID
		LEFT JOIN ecshop.ecs_goods_style AS goods_style ON goods_style.style_id = mapping.ECS_STYLE_ID and goods_style.goods_id = mapping.ECS_GOODS_ID and goods_style.is_delete=0
		LEFT JOIN ecshop.ecs_style AS style on style.style_id = mapping.ECS_STYLE_ID
		LEFT JOIN romeo.location AS loc on loc.location_barcode = il.location_barcode
		where il.location_barcode='{$location_barcode}' and il.is_serial = 1 and serial.goods_number > 0
		";
	$locations = $db->getAll($sql);
    // Qlog::log('locations='.$sql);
	usort($locations, 'LocationSort');

   $sql = "SELECT goods_barcode,sum(goods_number) as total_number ,sum(available_to_reserved) as atr_number
     		from romeo.inventory_location
     		where location_barcode='{$location_barcode}' and goods_number > 0 group by goods_barcode";
   // Qlog::log('count_goods_number='.$sql);
   $count_goods_number = $db->getAll($sql);

 foreach ( $locations as $key => $location ) {
   	$locations[$key]['location_type'] = $location_type_name_list[$locations[$key]['location_type']];
    foreach ( $count_goods_number as $key1 => $cg_number ) {
	   	if($count_goods_number[$key1]['goods_barcode'] == $locations[$key]['goods_barcode']){
	   		$locations[$key]['goods_number'] = $count_goods_number[$key1]['total_number'];
	   		$locations[$key]['available_to_reserved'] = $count_goods_number[$key1]['atr_number'];
	    }
    }
 }

 //在容器商品清单页显示容器条码、容器类型
 $sql = "SELECT location_barcode, if(location_type = 'IL_GROUDING', '上架容器', '库位') as location_type
     		from romeo.location
     		where location_barcode='{$location_barcode}' ";
 $location = $db->getRow($sql);
 

 $smarty->assign("location", $location);
 $smarty->assign("count_goods_number", $count_goods_number);
 $smarty->assign("locations", $locations);
 $smarty->display("oukooext/location_product_list.htm");
 exit();
}
$smarty->assign("message", $message);//提示信息
$smarty->assign("flag", $flag);//标注提示信息颜色
$smarty->assign("party_name", $party_name);
$smarty->display("oukooext/inventory_location.htm");


function LocationSort($a, $b)
{
	return ($a['goods_id'] > $b['goods_id']);
}