<?php
/**
 * 批量入库操作
 * @author ljzhou 2012.11.27
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
$rec_ids = $_REQUEST ['rec_ids'];
$batch_order_sns = $_REQUEST ['batch_order_sns'];
$goods_item_types = $_REQUEST ['goods_item_types'];
$not_in_counts = $_REQUEST ['not_in_counts'];
$is_maintain_warrantys = $_REQUEST ['is_maintain_warrantys'];
$validity_type = $_REQUEST ['validity_type'];

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
	if ( count ( $batch_order_sns ) != $check_number || count ( $goods_item_types ) != $check_number || count ( $is_maintain_warrantys ) != $check_number) {
		$result['error'] .= '表单提交数据到php发生异常,请联系erp组';
		break;
	}
	for($i = 0; $i < $check_number; $i ++) {
		try {
			$order_id = $order_ids [$i];
			$batch_order_sn = $batch_order_sns [$i];
			$not_in_count = intval ( $not_in_counts [$i] );
			$rec_id = $rec_ids [$i];
			$goods_item_type = $goods_item_types [$i];
			$is_maintain_warranty = $is_maintain_warrantys[$i];
			
			$validitys = $_REQUEST ['search_in_times_'.$rec_id];
			$input_numbers = $_REQUEST ['input_numbers_'.$rec_id];
			if (count ( $validitys ) != count ( $input_numbers )) {
				$result['error'] .= '表单提交数据到php发生异常(日期和数量不等),请联系erp组';
				break 2;
			}
			$lock_name = "{$rec_id}"; // 文件锁
			if (! wait_file_lock ( $lock_name, 5 )) {
				$result['error'] .= '该订单可能有人在进行入库操作，请核实入库数量';
				continue;
			}
			create_file_lock ( $lock_name );
			for($j = 0; $j < count ( $validitys ); $j ++) {
				$validity = trim ( $validitys [$j] );
				$input_number = trim ( $input_numbers [$j] );
				if ($input_number == 0) {
					continue;
				}
				if ($goods_item_type == 'SERIALIZED') {
					$result['error'] .=  '批量入库不支持有串号的商品，请使用普通入库';
					continue;
				}
				
				$is_print = isset ( $_POST ["print_sn"] ) && $_POST ["print_sn"] == '1' ? true : false;
				$info = check_in_fittings_v2 ( $order_id,$rec_id, $input_number, $is_print, null );
				if ($info ['res'] != 'success') {
					$result['error'] .= "批量入库失败:" .$info['back']. " batch_order_sn:" . $batch_order_sn . " order_id:" . $order_id . " goods_item_type:" . $goods_item_type . " input_number:" . $input_number ." validity:" . $validity ;
					break;
				}
				$not_in_count = $not_in_count - $input_number;
				if ($not_in_count == 0) {
					$sql = "update {$ecs->table('batch_order_mapping')} set is_in_storage = 'Y' where order_id = {$order_id} limit 1";
					$db->query ( $sql );
				}
				if ($info ['product_code_info'] == "打印条码数量为0") {
					$product_code_info .=  $info ['product_code_info'];
				}
			}
		} catch (Exception $single_e) {
			$result['error'] .= '批量入库单个异常：'.$single_e;
		}
		release_file_lock ( $lock_name );
	}
} while(false);


$product_code_info .= "该产品已打印条码";

if (check_all_in($batch_order_sns[0])) {
	$sql = "update {$ecs->table('batch_order_info')} set is_in_storage= 'Y',in_time = now(),in_storage_user = '{$_SESSION['admin_name']}' where is_cancelled = 'N' and batch_order_sn = '{$batch_order_sns[0]}' limit 1";
	$db->query($sql);
}

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
$back = add_param_in_url ( "in_storage.php?label=batch_in_storage", 'info', "修改成功！" . $back_message . " 耗时" .$cost_time);

Header ( "Location: $back" );


