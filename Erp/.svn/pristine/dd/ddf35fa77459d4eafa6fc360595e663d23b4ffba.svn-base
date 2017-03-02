<?php
/**
 * 入库操作
 */
define('IN_ECS', true);
require('includes/init.php');
admin_priv('ck_in_storage', 'wl_in_storage');
require_once(ROOT_PATH.'includes/debug/lib_log.php');
QLog::log("sn_input_actionV2 start");
require_once('includes/lib_filelock.php');
require_once('includes/lib_product_code.php');
require('function.php');
QLog::log("sn_input_actionV2 begin romeo api ".time());
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
$sqls = array();
QLog::log("init ok".time());
$order_id = intval($_POST['order_id']);
$order_sn = $_POST['order_sn'];
$back     = remove_param_in_url($_REQUEST['back'] !== null ? $_REQUEST['back'] : '/', 'info');
$validity_type = $_REQUEST ['validity_type'];

$sql = "select party_id,facility_id from {$ecs->table('order_info')} where order_id = {$order_id} ";
$order = $db->getRow($sql);
$facility_id = $order['facility_id'];
$party_id = $order['party_id'];
$goods_item_type = $_POST['goods_item_type'];
// 是否维护有效期
$is_maintain_warrantys = $_REQUEST['is_maintain_warrantys'];
$is_maintain_warranty = $is_maintain_warrantys[0];

$lock_name = "{$order_sn}"; // 文件锁
if (!wait_file_lock($lock_name, 5)) {
    die('操作超时，请重试，请核实是否有人也在进行同一订单的入库操作。如长时间出现该界面，请联系erp组');
}
create_file_lock($lock_name);
QLog::log("query begin");


try {
	
$result=array();
if ($goods_item_type == 'SERIALIZED') {
	do {
		$is_print = isset ( $_POST ["print_sn"] ) && $_POST ["print_sn"] == '1' ? true : false;
	    $serial_numbers = $_POST['serial_numbers'];
	    $serail_numbers_new = array();
	    // 去掉未输入的空白框
	    foreach($serial_numbers as $key=>$serial_number) {
	    	if(empty($serial_number)){
	    		unset($serial_numbers[$key]);
	    	}
	    }
	    Qlog::log('$order_id:'.$order_id.' $is_print:'.$is_print.' before_check $serial_numbers:'.implode(',',$serial_numbers));
	    foreach($serial_numbers as $key=>$serial_number) {
	    	$serial_number_exist = check_serial_number_exist($serial_number);
	    	Qlog::log('$serial_number_exist serial_has_in:'.$serial_number_exist['serial_has_in'].' $serial_number:'.$serial_number);
	    	if($serial_number_exist['serial_has_in']){
	    		$result['error'] = $serial_number_exist['error'];
	    		unset($serial_numbers[$key]);
	    		continue;
	    	}
	    }
	    Qlog::log('$order_id:'.$order_id.' $is_print:'.$is_print.' after_check $serial_numbers:'.implode(',',$serial_numbers));
	    foreach($serial_numbers as $serial_number) {
	    	$serail_numbers_new[] = $serial_number;
	    }
		$info = check_in_fittings ( $order_id, 1, $is_print, $serail_numbers_new);
		if ($info ['res'] != 'success') {
			$result['error'] = $info ['back'];
		}
		$product_code_info = $info ['product_code_info'];
	} while(false);

    
} else {
	do {
		
		$validitys = $_REQUEST ['search_in_times'];
		$input_numbers = $_REQUEST ['input_numbers'];
		if (count ( $validitys ) != count ( $input_numbers )) {
			QLog::log ( "common_in_storage 表单提交数据到php发生异常(日期和数量不等):" . $order_sn );
			$result['error'] = '普通入库表单提交数据到php发生异常(日期和数量不等),请联系erp组';
	    	break;
		}
		for($j = 0; $j < count ( $validitys ); $j ++) {
			$validity = trim ( $validitys [$j] );
			$input_number = intval(trim ( $input_numbers [$j] ));
			if ($input_number == 0) {
				continue;
			}
			$is_print = isset ( $_POST ["print_sn"] ) && $_POST ["print_sn"] == '1' ? true : false;
			Qlog::log('$order_id:'.$order_id.' $is_print:'.$is_print.' $input_number:'.$input_number);
			$info = check_in_fittings ( $order_id, $input_number, $is_print, null );
			if ($info ['res'] != 'success') {
				$result['error'] = $info ['back'];
	    		break 2;
			}
			$product_code_info = $info ['product_code_info'];
		}
	   
	} while(false);
}

// 如果普通入库该订单入库完了，需要更新批量入库的映射表
if (check_order_all_in_storage ( $order_id )) {
	$sql = "update {$ecs->table('batch_order_mapping')} set is_in_storage = 'Y' where order_id = {$order_id} limit 1";
	$db->query ( $sql );
	QLog::log ( "update batch_order_mapping(by common in storage)order_sn:" . $order_sn );
	$sql = "select batch_order_sn from {$ecs->table('batch_order_info')} boi
	        left join {$ecs->table('batch_order_mapping')} om ON boi.batch_order_id = om.batch_order_id
	        where om.order_id = {$order_id} limit 1";
	$batch_order_sn = $db->getOne ( $sql );
	if ( !empty ( $batch_order_sn )) {
		if (check_all_in ( $batch_order_sn )) {
			$sql = "update {$ecs->table('batch_order_info')} set is_in_storage= 'Y',in_time = now(),in_storage_user = '{$_SESSION['admin_name']}' where is_cancelled = 'N' and batch_order_sn = '{$batch_order_sn}' limit 1";
			$db->query ( $sql );
			QLog::log ( "batch_order_sn all_in_storage(by common in storage)batch_order_sn:" . $batch_order_sn );
		}
	}
}
		
} catch (Exception $e) {
	$result['error'] .= $e->getMessage();
}

Qlog::log('in_storage error:'.$result['error']);
if(empty($result['error'])) {
	$back = add_param_in_url("in_storage.php?label=common_in_storage", 'info', "修改成功！".$product_code_info);
} else {
	$back = add_param_in_url ( $back, 'info', $result['error'] );
}

release_file_lock ( $lock_name );
QLog::log("release_file_lock end ");
QLog::log("sn_input_actionV2 end");
Header("Location: $back");




