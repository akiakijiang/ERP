<?php
/**
 * 批量入库并且收货到上架容器
 * @author ljzhou 2014-6-9
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
admin_priv ( 'ck_in_storage', 'wl_in_storage' );
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once ('includes/lib_filelock.php');
require_once ('includes/lib_product_code.php');
require ('function.php');
require_once (ROOT_PATH . "RomeoApi/lib_inventory.php");

$start_time = microtime(true);
$order_ids = $_REQUEST ['order_ids'];
$order_sns = $_REQUEST ['order_sns'];
$batch_order_sns = $_REQUEST ['batch_order_sns'];
$goods_item_types = $_REQUEST ['goods_item_types'];
$not_in_counts = $_REQUEST ['not_in_counts'];
$is_maintain_warrantys = $_REQUEST ['is_maintain_warrantys'];
$validity_type = $_REQUEST ['validity_type'];
$location_barcode = $_REQUEST ['location_barcode'];
$goods_barcodes = $_REQUEST ['goods_barcodes'];

$back = remove_param_in_url ( $_REQUEST ['back'] !== null ? $_REQUEST ['back'] : '/', 'info' );
$sql = "select facility_id from {$ecs->table('order_info')} where order_id = {$order_ids[0]} limit 1";
$facility_id = $db->getOne($sql);

$result = array();
try {
	do {
		$batch_lock_name = "{$batch_order_sns[0]}"; // 文件锁
		if (! wait_file_lock ( $batch_lock_name, 5 )) {
			die ( '操作超时，请重试，请核实是否有人也在进行批量入库操作。如长时间出现该界面，请联系erp组' );
		}
		create_file_lock ( $batch_lock_name );
		
		$check_number = count ( $order_ids );
		if (count ( $order_sns ) != $check_number || count ( $batch_order_sns ) != $check_number || count ( $goods_item_types ) != $check_number || count ( $is_maintain_warrantys ) != $check_number || count ( $goods_barcodes ) != $check_number) {
			$result['error'] .= '表单提交数据到php发生异常,请联系erp组';
			break;
		}
		for($i = 0; $i < $check_number; $i ++) {
			try {
				$order_id = $order_ids [$i];
				$batch_order_sn = $batch_order_sns [$i];
				$not_in_count = intval ( $not_in_counts [$i] );
				$order_sn = $order_sns [$i];
				$goods_item_type = $goods_item_types [$i];
				$is_maintain_warranty = $is_maintain_warrantys[$i];
				$goods_barcode = $goods_barcodes[$i];
				$serial_number = null;
				$validitys = $_REQUEST ['search_in_times_'.$order_sn];
				$input_numbers = $_REQUEST ['input_numbers_'.$order_sn];
				
				if ($goods_item_type == 'SERIALIZED') {
					$result['error'] .=  '批量入库不支持有串号的商品，请使用普通入库，订单号：' . $order_sn;
					continue;
				}
					
				if (count ( $validitys ) != count ( $input_numbers )) {
					$result['error'] .= '表单提交数据到php发生异常(日期和数量不等),请联系erp组';
					break 2;
				}
				$lock_name = "{$order_sn}"; // 文件锁
				if (! wait_file_lock ( $lock_name, 5 )) {
					$result['error'] .= '该订单可能有人在进行入库操作，请核实入库数量：' . $order_sn . " ";
					continue;
				}
				create_file_lock ( $lock_name );
				for($j = 0; $j < count ( $validitys ); $j ++) {
					$validity = trim ( $validitys [$j] );
					if(empty($validity)) {
						$validity = '1970-01-01';
					}
					$validity = $validity.' 00:00:00';
					// 生产日期没管起来，目前统一用默认值
					//$validity = '1970-01-01 00:00:00';
					$input_number = trim ( $input_numbers [$j] );
					if ($input_number == 0) {
						continue;
					}
					
					$info = one_order_purchase_accept_and_location_transaction($order_id,$location_barcode,$goods_barcode,$serial_number,
	                                                           $input_number,$validity, $validity_type='start_validity');
	                
					if (!$info ['success']) {
						$result['error'] .= " 批量入库失败:" .$info['back']. " batch_order_sn:" . $batch_order_sn . " order_id:" . $order_id . " order_sn:" . $order_sn . " goods_item_type:" . $goods_item_type . " input_number:" . $input_number ." validity:" . $validity ;
						$result['error'] .= " 错误信息：".$info['error'];
						break;
					}

				}
			} catch (Exception $single_e) {
				$result['error'] .= '批量入库单个异常：'.$single_e;
			}
			release_file_lock ( $lock_name );
		}
	} while(false);

} catch( Excetion $batch_e) {
	$result['error'] .= '批量入库批量异常：'.$batch_e;
}

release_file_lock ( $batch_lock_name );

$end_time = microtime(true);
$cost_time = $end_time-$start_time;
QLog::log("本批次入库:".$batch_order_sns[0]." 采购订单个数：".$check_number." 耗时：".$cost_time);
if(empty($result['error'])) {
	$back_message = "修改成功：" . $message . $product_code_info;
} else {
	$back_message = "修改失败：" . $result['error'];
}

$back = add_param_in_url ( "batch_in_storage.php?", 'info', $back_message . " 耗时" .$cost_time);

Header ( "Location: $back" );


