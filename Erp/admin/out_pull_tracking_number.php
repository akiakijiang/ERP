<?php
/*
 * 外包订单运单号导入ERP
 */
define('IN_ECS', true);
require('includes/init.php');
require('function.php');
include_once('../RomeoApi/lib_currency.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

admin_priv('ck_out_ship_pull_tn');
$act = $_REQUEST['act'];
$flag = true;
if (!empty($act) && $act == 'action_out') {
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	require('ajax.php');
 	$final_out = '';
 	$error_out = array();
 	do{
 		/* 文件上传并读取 */
		@set_time_limit ( 300 );
		$uploader = new Helper_Uploader ();
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		if (!$uploader->existsFile ( 'excel' )) {
			$final_out .=  '没有选择上传文件，或者文件上传失败';
			$flag = false;
			break;
		}
		// 取得要上传的文件句柄
		$file = $uploader->file ( 'excel' );
		
		// 检查上传文件
		if (! $file->isValid ( 'xls, xlsx', $max_size )) {
			$final_out .= "   " . '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
			$flag = false;
			break;
		}
		$tpl = 
		array ('外包仓同步运单号' => 
		         array ('taobao_order_sn' => '淘宝订单编号',
		                'tracking_number' => '运单编号',
		                'shipping_id' => '发货快递编号'
		       ) );
		// 读取excel
		$record = excel_read ($file->filepath (), $tpl, $file->extname (), $failed );
		//判断是否符合条件
		if (sizeof($record['外包仓同步运单号']) == 0) {
			$final_out .= " 导入的数据为空";
			$flag = false;
			break;
		}else if(sizeof($record['外包仓同步运单号']) > 1000){
			$final_out .= " 导入的数据行数不能超过1000行，请成两个文件分别导入";
			$flag = false;
			break;
		}

		$i = 1;
		$j = 0;
		foreach ( $record['外包仓同步运单号'] as $key => $rec ) {
			global $db;
			$taobao_order_sn = trim($rec['taobao_order_sn']);
			$tracking_number = trim($rec['tracking_number']);
			$shipping_id = trim($rec['shipping_id']);

			$sql = "SELECT 
					oi.order_id , 
					oi.shipping_id, 
					es.default_carrier_id carrier_id,
					oi.facility_id 
				from ecshop.ecs_order_info oi 
				left join ecshop.ecs_shipping es on es.shipping_id=oi.shipping_id
				where oi.taobao_order_sn = '{$taobao_order_sn}' 
			";
			$order = $db->getRow($sql);
			$sql = "SELECT count(1) from romeo.shipment where tracking_number = '{$tracking_number}' ";
			$is_exists = $db->getOne($sql); 
			
			$sql="SELECT shipping_name, default_carrier_id from ecshop.ecs_shipping where shipping_id = {$shipping_id} limit 1";
			$actual_shipping = $db->getRow($sql);
			
			//允许面单导入的仓库：
			// 第三方外包仓流程操作的订单  跨境菜鸟集货仓
			// 百威英博菜鸟仓（华南花都云时仓,华北空港云时仓,华东奉贤云时仓,成都越海龙泉仓）
			// 乐其跨境 虚拟发货2：杭州菜鸟仓,上海菜鸟仓,杭州空港仓,宁波百世仓,菜鸟嘉里大通宁波保税仓,嘉里大通宁波保税仓 ,香港中外运直邮仓,广州白云机场保税仓,香港GPN直邮仓,跨境品牌直邮仓 订单
			$facility_array = array('251234673','92718101','119603091','119603092','119603093','224734292','149849263','149849264','149849265',
				'149849266','178036539','181093102','181093103','184099370','185963143','185963144','237191026','231292324','222187981','237191027');
			if(empty($order['order_id'])){
				$error_out[$j] = 'EXCEL 第'.$i.' 行'.', 淘宝订单号：'.$rec['taobao_order_sn'].' 未找到对应ERP系统内订单号.';
			}else if(empty($order['carrier_id'])){
				$error_out[$j] = 'EXCEL 第'.$i.' 行'.', 淘宝订单号：'.$rec['taobao_order_sn'].' 未找到对应快递编号，无法核实';
			}else if(!in_array($order['facility_id'], $facility_array) ){
				$error_out[$j] = 'EXCEL 第'.$i.' 行'.', 淘宝订单号：'.$rec['taobao_order_sn'].' 对应ERP系统仓不允许通过该页面导入面单';
			}else if($is_exists!=0){
				$error_out[$j] = 'EXCEL 第'.$i.' 行'.', 淘宝订单号：'.$rec['taobao_order_sn'].' 要维护的运单编号在系统中已存在';
			}else if(empty($actual_shipping)){
				$error_out[$j] = 'EXCEL 第'.$i.' 行'.', 淘宝订单号：'.$rec['taobao_order_sn'].' 导入快递编号不存在';
			}else{
				$carrier_id = $actual_shipping['default_carrier_id'];
				$result = ajax_check_tracking_number(array("carrier_id"=>$carrier_id,"tracking_number"=>$tracking_number));
				if($result && $order['shipping_id'] == $shipping_id){
					$update_sql = "UPDATE romeo.shipment s 
						left join romeo.order_shipment os on os.shipment_id = s.shipment_id 
						set s.tracking_number = '{$tracking_number}' 
						where os.order_id = '{$order['order_id']}' 
					";
					if($db->query($update_sql)){
						Qlog::log('----SyncSuccess-----'.'---taobao_order_sn='.$rec['taobao_order_sn'].'--tracking_number='.$rec['tracking_number']);
					}else{
						$error_out[$j] = 'EXCEL 第'.$i.' 行'.', 淘宝订单号：'.$rec['taobao_order_sn'].' 更新出错 ';
					}
				}else if($result && $order['shipping_id'] != $shipping_id){
					$update_sql = "UPDATE ecshop.ecs_order_info o 
						left join romeo.order_shipment os on convert(o.order_id using utf8)=os.order_id
						left join romeo.shipment s on os.shipment_id = s.shipment_id 
						set o.shipping_id = {$shipping_id}, 
						o.shipping_name = '{$actual_shipping['shipping_name']}', 
						s.shipment_type_id = {$shipping_id}, 
						s.carrier_id = {$carrier_id}, 
						s.tracking_number = '{$tracking_number}' 
						where o.order_id = {$order['order_id']} 
					";
					if($db->query($update_sql)){
						Qlog::log('----SyncSuccess-----'.'---taobao_order_sn='.$rec['taobao_order_sn'].'--tracking_number='.$rec['tracking_number']);
					}else{
						$error_out[$j] = 'EXCEL 第'.$i.' 行'.', 淘宝订单号：'.$rec['taobao_order_sn'].' 更新出错 ';
					}
				}else{
					$error_out[$j] = 'EXCEL 第'.$i.' 行'.', 淘宝订单号：'.$rec['taobao_order_sn'].' 运单编号与导入快递运单号规则不符';
				}
			}
			$flag = false;
			$j++;$i++;	
        }
 	}while(false);
 	$smarty->assign('final_out',$final_out);
	$smarty->assign('error_out',$error_out);
}
$smarty->assign('final',$final);
$smarty->display('out_pull_tracking_number.htm');

?>
