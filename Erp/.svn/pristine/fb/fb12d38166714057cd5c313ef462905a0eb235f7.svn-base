<?php
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');

if($_SESSION['party_id'] != 65562){
	 die("只有ECCO业务组使用打印商品标识！");
}

admin_priv('goods_identify');

$load_message = '';
$export_message ='';
if($_REQUEST['act'] == 'upload'){
	while(1){
		// excel读取设置
		$tpl =
		array ('商品标识' =>
		         array ( 'goods_barcode' => '商品条码',
		                 'area' => '产地',
		                 'stuff' => '材料',
		                 'type' => '类型',
		                 'craft' => '工艺',
		                 'item_name' => '品名',
		                 'color' => '颜色',
		                 'shape' => '号型',
		                 'carried_standard' => '执行标准'
		                 ) );
		
		/* 文件上传并读取 */
		@set_time_limit ( 300 );
		$uploader = new Helper_Uploader ();
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		
		
		if (! $uploader->existsFile ( 'excel' )) {
			$load_message .= '没有选择上传文件，或者文件上传失败!';
			break;
		}
		
		// 取得要上传的文件句柄
		$file = $uploader->file ( 'excel' );
		
		// 检查上传文件
		if (! $file->isValid ( 'xls, xlsx', $max_size )) {
			$load_message .= "   " . '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
			break;
		}
		// 读取excel
		$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
		
		//判断导入的数据是否为空
		if (sizeof($result['商品标识']) == 0) {
			$load_message .= "导入数据为空!";
			break;
		}
		$i=0;
		$j=0;
		foreach ($result['商品标识'] as $value) {
			$sql = "select goods_barcode from `ecshop`.`ecs_goods_identify` where goods_barcode = '{$value['goods_barcode']}' limit 1 ";
			$goods_barcode = $db->getOne ( $sql );
			if(!empty($goods_barcode)){
				$sql = "update `ecshop`.`ecs_goods_identify` set area = '{$value['area']}',stuff = '{$value['stuff']}', type = '{$value['type']}', craft = '{$value['craft']}', color = '{$value['color']}', item_name = '{$value['item_name']}', shape = '{$value['shape']}', carried_standard = '{$value['carried_standard']}' where goods_barcode = '{$value['goods_barcode']}';";
				// Qlog::log('-update-'.$sql);
				$db->query ( $sql );
				$i++;
			}else{
				$sql ="INSERT INTO  `ecshop`.`ecs_goods_identify` (goods_barcode,area,stuff,type,craft,color,item_name,shape,carried_standard) VALUES('{$value['goods_barcode']}','{$value['area']}','{$value['stuff']}','{$value['type']}','{$value['craft']}','{$value['color']}','{$value['item_name']}','{$value['shape']}','{$value['carried_standard']}');";
				// Qlog::log('-INSERT-'.$sql);
				$db->query ( $sql );
				$j++;
			}
		}
	   $load_message .='成功导入 '.$j.' 条记录, 更新 '.$i.' 记录';
		break;
	}
	
	//pp($result);die;
}else if($_REQUEST['act'] == 'export_print_history'){
	$begin_time = trim($_REQUEST['start_validity_time']);
	$end_time = trim($_REQUEST['end_validity_time']);
	$sql ="SELECT goods_barcode,COUNT(goods_barcode) AS count from `ecshop`.`ecs_print_goods_identify_history` where  action_time> '{$begin_time}' and  action_time < '{$end_time}' GROUP BY goods_barcode ";
	// Qlog::log('export='.$sql);
	$historys = $db->getAll ( $sql );
	if(empty($historys)){
		$export_message .='选择的时间段暂时没有打印记录';
	}
}
if($_REQUEST['csv'] == '历史打印CSV' && !empty($historys)){
	//pp($historys);die;
	$smarty->assign('historys', $historys);
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","历史打印CSV") . ".csv");
	$out = $smarty->fetch('oukooext/print_history_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
}else{
	$smarty->assign("load_message", $load_message);//数据导入提示信息
	$smarty->assign("export_message", $export_message);//导出数据提示信息
    $smarty->display("oukooext/goods_identify.htm");
}

?>
