<?php

/**
 * 乐其商品 批量导入功能
 * 
 * @author qxu@i9i8.com
 * @copyright 2013 leqee.com 
 */

define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

$act = // 动作
isset ( $_REQUEST ['act'] ) && in_array ( $_REQUEST ['act'], array ('upload') ) ? $_REQUEST ['act'] : null;

// 当前时间 
$now = date ( 'Y-m-d H:i:s' );

// excel读取设置
$tpl = 
array ('下载资料导入' => 
         array ('cat1' => '项目1', 
                'cat2' => '项目2', 
                'cat3' => '项目3', 
                'cat4' => '项目4', 
                'cat5' => '项目5', 
                'cat_name' => '资料名称', 
                'cat_size' => '资料大小'
                ) );
                
QLog::log ( "商品导入[更新]：{$act} " );
if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
	switch ($act) {
		case 'upload':
			QLog::log ( '商品导入：' );
			/* 文件上传并读取 */
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
				$smarty->assign ( 'message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB' );
				break;
			}
			
			// 读取excel
			$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
			if (! empty ( $failed )) {
				$smarty->assign ( 'message', reset ( $failed ) );
				break;
			}
			
			/* 检查数据  */
			$rowset = $result ['下载资料导入'];
			
			// 订单数据读取失败
			if (empty ( $rowset )) {
				$smarty->assign ( 'message', 'excel文件中没有数据,请检查文件' );
				break;
			}
			
			$cat_names = Helper_Array::getCols ( $rowset, 'cat_name' ); // 商品名称
			$cat_sizes = Helper_Array::getCols ( $rowset, 'cat_size' ); // 商品名称
			
			// 检验数据，主要是检验没一行 的cat_name和cat_size 不能为空
			$cat_names_in_len = count ($cat_names);
			Helper_Array::removeEmpty ($cat_names);
			if (empty ( $cat_names ) || $cat_names_in_len > count ( $cat_names )) {
				$smarty->assign ( 'message', "文件中存在空的资料名称，请确保资料名称都是完整的" );
				break;
			}
			
			$cat_sizes_in_len = count ($cat_sizes);
			Helper_Array::removeEmpty ($cat_sizes);
			if (empty ( $cat_sizes ) || $cat_sizes_in_len > count ( $cat_sizes )) {
				$smarty->assign ( 'message', "文件中存在空的资料大小，请确保资料大小都是完整的" );
				break;
			}
			//数据校验成功，开始往数据库里面插入内容
			foreach ($rowset as $row) {
				if (isset( $row ['cat1']) && $row ['cat1'] != '') {
					if(check_download_exist($row ['cat1'],'2820')){
						$insert_id = check_download_exist($row ['cat1'],'2820');
					}else{
						$insert_id = add_download_cat($row ['cat1'],'2820');
					}
				}
				
				if(isset($row ['cat2']) && $row ['cat2'] != ''){
					if(check_download_exist($row ['cat2'],$insert_id)){
						$insert_id = check_download_exist($row ['cat2'],$insert_id);
					}else{
						$insert_id = add_download_cat($row ['cat2'],$insert_id);
					}
				}
				
				if(isset($row ['cat3']) && $row ['cat3'] != ''){
					if(check_download_exist($row ['cat3'],$insert_id)){
						$insert_id = check_download_exist($row ['cat3'],$insert_id);
					}else{
						$insert_id = add_download_cat($row ['cat3'],$insert_id);
					}
				}
				
				if(isset($row ['cat4']) && $row ['cat4'] != ''){
					if(check_download_exist($row ['cat4'],$insert_id)){
						$insert_id = check_download_exist($row ['cat4'],$insert_id);
					}else{
						$insert_id = add_download_cat($row ['cat4'],$insert_id);
					}
				}
				
				if(isset($row ['cat5']) && $row ['cat5'] != ''){
					if(check_download_exist($row ['cat5'],$insert_id)){
						$insert_id = check_download_exist($row ['cat5'],$insert_id);
					}else{
						$insert_id = add_download_cat($row ['cat5'],$insert_id);
					}
				}
				if(isset($row ['cat_name']) && $row ['cat_name'] != '' && isset($row ['cat_size']) && $row ['cat_size'] != '' && !check_download_data($row ['cat_name'],$insert_id,$row ['cat_size'])){
					if(preg_match("/^([1-9]+[0-9]*|0)(\\.[\\d]+)?$/",$row ['cat_size'])){
						$sql = "
							INSERT INTO `ecshop`.`ecs_download_data` (`data_id`, `data_party_id`, `cat_id`, `data_name`, `data_size`, `add_time`, `top_cat_id`) VALUES
							(null, 16, '{$insert_id}', '{$row ['cat_name']}', '{$row ['cat_size']}', 0, 1496)
						";
						$db->query($sql);
					}else{
						QLog::log($row ['cat_size']);
						QLog::log("kkk");
					}
				}
			}
		// 删除上传的文件
		$file->unlink ();
		$smarty->assign ('message', "导入完毕" );
	}
}

/**
 * 显示
 */
$smarty->assign ( 'party_id', $_SESSION ['party_id'] );
$smarty->display ( 'goods_style_import.htm' );


function add_download_cat($cat_name,$parent_id){
	global  $db;
	$sql = "insert into ecshop.ecs_category values(null,'{$cat_name}','0','','','{$parent_id}','0','0','','','0','0','0','','','','','','','','16','0','');";
	$db->query($sql);
	return $db->insert_id();
}

function check_download_exist($cat_name,$parent_id){
	global  $db;
	$sql = "select cat_id from ecshop.ecs_category where parent_id = '{$parent_id}' and cat_name = '{$cat_name}'";
	return $db->getOne($sql);
}

function check_download_data($data_name,$cat_id,$data_size){
	global  $db;
	$sql = "select data_id from ecshop.ecs_download_data where data_name = '{$data_name}' and data_size = '{$data_size}' and cat_id = '{$cat_id}' ";
	return $db->getOne($sql);
}