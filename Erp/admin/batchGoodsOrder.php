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
require_once (ROOT_PATH . 'admin/distribution.inc.php');
require_once (ROOT_PATH . 'includes/lib_common.php');

//验证权限
admin_priv("batchGoodsOrder");

$act = // 动作
isset ( $_REQUEST ['act'] ) && in_array ( $_REQUEST ['act'], array ('upload') ) ? $_REQUEST ['act'] : null;

// 当前时间 
$now = date ( 'Y-m-d H:i:s' );

//判断组织是否为具体的业务单位
if(!party_explicit($_SESSION['party_id'])) {
    sys_msg("请选择具体的组织后再来录入订单");
}


QLog::log ( "跨境购商品导入开始：{$act} " );
if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
	switch ($act) {
		case 'upload':
			
			// excel读取设置
			$tpl = array('跨境购商品导入'  =>
				array('nick'=>'分销商店铺',
					  'product_id'=>'跨境商品ID',
					  'goods_name'=>'商品名称',
					  'outer_id'=>'商家编码',
					  'unit'=>'计量',
					  'price'=>'价格',
					  'rate'=>'消费税率',
					  'vat_rate'=>'增值税率',
					  'declaration_facility'=>'申报系统仓库',
					  'package_flag'=>'是否组合'
				));
                
			
			QLog::log ( '订单导入：' );
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
			$rowset = $result ['跨境购商品导入'];

			// 订单数据读取失败
			if (empty ( $rowset )) {
				$smarty->assign ( 'message', 'excel文件中没有数据,请检查文件' );
				break;
			}
			$order_check_array = array('nick','product_id','goods_name','outer_id','unit','price','rate','vat_rate','package_flag');
			
			$declaration_facility = $_CFG['adminvars']['declaration_facility'];
			
//			foreach(Helper_Array::getCols($rowset, 'product_id') as $item_value) {
//				if(!(preg_match ('/^[1-9]([0-9]+)?$/', $item_value) && $item_value > 0)){
//					$smarty->assign('message',"【跨境购商品ID】必须为数字：{$item_value}");
//					break 2;
//				}
//			}
//			foreach(Helper_Array::getCols($rowset, 'outer_id') as $item_value) {
//				if(!(preg_match ('/^[1-9]([0-9]+)?$/', $item_value) && $item_value > 0)){
//					$smarty->assign('message',"【商家编码】必须为数字：{$item_value}");
//					break 2;
//				}
//			}
//			foreach(Helper_Array::getCols($rowset, 'price') as $item_value) {
//				if($item_value < 0){
//					$smarty->assign('message',"【价格】不能小于0：{$item_value}");
//					break 2;
//				}
//			}
			foreach(Helper_Array::getCols($rowset, 'declaration_facility') as $item_value) {
				if(!in_array($item_value,$declaration_facility)){
					$smarty->assign('message',"【实际仓】填写有误：{$item_value}");
					break 2;
				} 
				
			}			
			
			$party_id = $_SESSION['party_id'];
			$order_items = Helper_Array::groupBy($rowset, 'product_id');
			$index=0;
			foreach ($order_items as $key=>$order_attr) {
				$count = count($order_attr);
				global $db;
				for($index = 0 ; $index < $count ; $index ++){
					$nick= $order_attr[$index]['nick'];
					$sql = "select application_key from ecshop.taobao_shop_conf where nick = '{$nick}' limit 1";
					$app_key=$db->getOne($sql);
					if($app_key){
						$product_id = $order_attr[$index]['product_id'];
						$sql = "select * from ecshop.haiguan_goods where application_key ='{$app_key}' and product_id ='{$product_id}'";
						if($db->getOne($sql))
						{
							echo "<br /><font color='red'>导入失败！有商品记录重复</font><br/>";
							echo "<font color='red'>店铺名称:".$nick."  跨境购商品ID:".$product_id."</font><br/>";
							return ;
						}
						$goods_name = $order_attr[$index]['goods_name'];
						$outer_id =  $order_attr[$index]['outer_id'];
						$unit =  $order_attr[$index]['unit'];
						$price =  $order_attr[$index]['price'];
						$rate = $order_attr[$index]['rate'];
						$vat_rate = $order_attr[$index]['vat_rate'];
						$package_flag = $order_attr[$index]['package_flag'];
						$declaration_facility = $order_attr[$index]['declaration_facility'];

						$client = new SoapClient($erpsync_webservice_url.'SyncKuajinggouService?wsdl');
						$request=array("productId"=>$product_id);
						$response=$client->SearchKuajinggouProduct($request);
		//				var_dump($response->return);
						if($response->return == $declaration_facility) {
							$sql = "insert into ecshop.haiguan_goods(party_id,application_key,outer_id,product_id,goods_name,unit,price,created_stamp,last_updated_stamp,rate,vat_rate,package_flag,declaration_facility)
									values('{$party_id}','{$app_key}','{$outer_id}','{$product_id}','{$goods_name}','{$unit}','{$price}',now(),now(),'{$rate}','{$vat_rate}','{$package_flag}','{$declaration_facility}')";
							$db->query($sql);						
						} else {
							$smarty->assign ('message', "货号：" . $product_id . " 申报系统错误，该商品之后的商品均导入失败！请重试！<br /> 具体信息：".$response->return);
							break 3;
						}
						
					}else{
						$smarty->assign ('message', "导入失败！店铺信息有误！<br/>");
						break 3;
					}
				}
			}
			
		$file->unlink ();
		$smarty->assign ('message', "导入完毕！<br/>".$return_message );
	}
}


$smarty->assign ( 'party_id', $_SESSION ['party_id'] );
$smarty->display ('distributor/batchGoodsOrder.htm' );

