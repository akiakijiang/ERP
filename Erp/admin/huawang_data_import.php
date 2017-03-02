<?php

/**
 * 花王 数据导入功能
 * 
 * @author stsun 2015-12-15
 * @copyright stsun@leqee.com
 */

define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');

//花王数据导入权限：kao_data_import
admin_priv('kao_data_import');

$act = isset ( $_REQUEST ['act'] )  ? $_REQUEST ['act'] : null;
$request = isset ( $_REQUEST ['request'] )  ? $_REQUEST ['request'] : null;
$request2 = isset ( $_REQUEST ['request2'] )  ? $_REQUEST ['request2'] : null;

// 当前时间 
$now = date ( 'Y-m-d H:i:s' );

if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
	switch ($act) {
		//结汇明细导入
		case 'settlement_upload':
			$return_message = insert_settlement_info();
			$smarty->assign ('message', $return_message );
			break;
			
		//天猫交易流水导入 	
		case 'tianmao_upload':
			$return_message = insert_tianmao_info();
			$smarty->assign ('message', $return_message );
			break;
			
		//库存流水明细导入	
		case 'shipping_upload':
			$return_message = insert_shipping_info();
			$smarty->assign ('message', $return_message );
			break;
			
		//淘宝订单导入
		case 'taobao_order_upload':
			$return_message = insert_taobao_info();
			$smarty->assign ('message', $return_message );
			break;	
		//账单明细导入
		case 'service_fee_upload':
			$return_message = insert_service_fee_info();
			$smarty->assign ('message', $return_message );
			break;	
			
		//apo数据
		case 'apo_upload':
			$return_message = insert_apo_info();
			$smarty->assign ('message', $return_message );
			break;

	}
}

if($request == 'search' || $request == 'download' ||  $request == 'search2' ||  $request == 'dealData' ) {
	global $db;

	$taobao_order_sn = isset($_REQUEST['taobao_order_sn']) ? $_REQUEST['taobao_order_sn'] : null;
	$settlement_time = isset($_REQUEST['settlement_time']) ? $_REQUEST['settlement_time'] : null;
	$kao_order_status = isset($_REQUEST['kao_order_status']) ? $_REQUEST['kao_order_status'] : null;
	$settlement_time_end = isset($_REQUEST['settlement_time_end']) ? $_REQUEST['settlement_time_end'] : null;
	
	$condition = '';
	$condition_import = '';
	if($taobao_order_sn != '') {
		$condition .= " and taobao_order_sn = '{$taobao_order_sn}' ";
		$condition_import .= " and o.taobao_order_sn = '{$taobao_order_sn}' ";
	}
	if($settlement_time != '') {
		$settlement_date = date('Ymd', strtotime($settlement_time));
		$condition .= " and settlement_date >= '{$settlement_date}' ";
		$condition_import .= " and o.settlement_time >= '{$settlement_time}' ";
	}
	if($settlement_time_end != '') {
		$settlement_date = date('Ymd', strtotime($settlement_time_end));
		$condition .= " and settlement_date <= '{$settlement_date}' ";
		$condition_import .= " and o.settlement_time <= '{$settlement_time}' ";
	}
	if($kao_order_status != '' && $kao_order_status != 'null') {
		$condition .= " and type = '{$kao_order_status}' ";
		$condition_import .= " and o.type = '{$kao_order_status}' ";
	}
	
	if($request == 'search') {
		$sql = "select * from ecshop.brand_kao_order_result_info
			where taobao_order_sn is not null
			". $condition;
		
		$kao_result_orders = $db->getAll($sql);
		
		$kao_order_list = array();
		foreach($kao_result_orders as $order) {
			$kao_order = array();
			$kao_order['taobao_order_sn'] = $order['taobao_order_sn'];
			$kao_order['transaction_sn'] = $order['transaction_sn'];
			$kao_order['settlement_date'] = $order['settlement_date'];
			$kao_order['shipping_date'] = $order['shipping_date'];
			$kao_order['shipping_fee'] = $order['shipping_fee'];
			$kao_order['goods_amount'] = $order['goods_amount'];		//商品合计金额（日元）
			$kao_order['fee'] = $order['alipay_fee'] + $order['tianmao_pay_fee'];		//手续费（日）
			$kao_order['rate'] = $order['rate'];
			$kao_order['goods_sn'] = $order['goods_sn'];				//商品品目
			$kao_order['goods_num'] = $order['goods_num'];				//商品数量
			$kao_order['goods_price_jps'] = $order['goods_price_jps'];	//商品合计原价（日元）
			$kao_order['bonus'] = $order['bonus'];						//优惠金额（日）
			$kao_order['order_amount'] = $order['order_amount'];		//订单金额（日）
			$kao_order['rmb_settlement_amount'] = $order['rmb_settlement_amount'];		//订单金额（RMB）
			if($order['type'] == 'P') {
				$kao_order['type'] = '销售';
			} else if($order['type'] == 'R') {
				$kao_order['type'] = '退货';
			}
		
			$kao_order_list[] = $kao_order;
		}
		
		$smarty->assign('request', $request);
		$smarty->assign('kao_order_list',$kao_order_list);
	}
	
	else if($request == 'search2') {
		$sql = "select o.* ,kg.goods_sn
				from ecshop.brand_kao_order_info  o
				left join ecshop.brand_kao_goods_info kg on o.goods_barcode = kg.barcode 
				where o.status in( 'init','finished') 
				" .$condition_import;
		$import_orders = $db->getAll($sql);
		
		$import_orders_list = array();
		foreach($import_orders as $import_order) {
			$order = array();
			$order['taobao_order_sn'] = $import_order['taobao_order_sn'];
			$order['transaction_sn'] = $import_order['transaction_sn'];
			$order['create_time'] = $import_order['create_time'];
			$order['settlement_time'] = $import_order['settlement_time'];
			$order['shipping_time'] = $import_order['shipping_time'];
			$order['fee'] = $import_order['fee'];
			$order['should_pay_postage'] = $import_order['should_pay_postage'];
			$order['jps_amount'] = $import_order['jps_amount'];
			$order['rmb_amount'] = $import_order['rmb_amount'];
			
			$order['settlement_amount'] = $import_order['settlement_amount'];
			$order['rmb_settlement_amount'] = $import_order['rmb_settlement_amount'];
			$order['should_pay_amount'] = $import_order['should_pay_amount'];
			$order['rate'] = $import_order['rate'];
			$order['goods_sn'] = $import_order['goods_sn'];
			$order['goods_num'] = $import_order['goods_num'];
			
			if($import_order['type'] == 'P') {
				$order['type'] = '销售';
			} else if($import_order['type'] == 'R') {
				$order['type'] = '退货';
			}
			$import_orders_list[] = $order;
		}
		$smarty->assign('request', $request);
		$smarty->assign('import_orders_list',$import_orders_list);
	}
	
	else if($request == 'download') {
		$kao_orders = array();
		$sql = "select * from ecshop.brand_kao_order_result_info where is_send = 'N' ". $condition;
		$results = $db->getAll($sql);
		
		kao_download_excel($results);
	} 
	
	else if($request == 'dealData') {
		$orders = get_actual_todo_orders();
		$return_message = deal_data_actual($orders);
		
		$smarty->assign ('message', $return_message );
	}
}


if(in_array($request2, array('form2_search1', 'form2_search2', 'form2_download1', 'form2_download2'))) {
	global $db;
	
	$taobao_order_sn = isset($_REQUEST['taobao_order_sn2']) ? $_REQUEST['taobao_order_sn2'] : null;
	$shipping_time2 = isset($_REQUEST['shipping_time2']) ? $_REQUEST['shipping_time2'] : null;
	$shipping_time2_end = isset($_REQUEST['shipping_time2_end']) ? $_REQUEST['shipping_time2_end'] : null;

	if($request2 == 'form2_search1') {
		$condition = '';
		if(!empty($taobao_order_sn)) {
			$condition .=" and o.taobao_order_sn='{$taobao_order_sn}' ";
		}
		if(!empty($shipping_time2)) {
			$condition .=" and o.shipping_time>'{$shipping_time2}' ";
		}
		if(!empty($shipping_time2_end)) {
			$condition .=" and o.shipping_time<'{$shipping_time2_end}' ";
		}
		$sql = "select g.goods_sn,o.goods_barcode,p.item_code, p.quantity, o.payment_time,o.create_time,o.shipping_time,
				o.taobao_order_sn,o.goods_num,o.rate,o.should_pay_postage,o.should_pay_amount,o.service_fee
					from brand_kao_order_info o
					left join brand_kao_goods_info g on o.goods_barcode = g.barcode
					left join kuajing_bird_product p on o.goods_barcode = p.item_code
					where o.type='P' " .$condition;
		$order_datas = $db->getAll($sql);

		$inventory_out_list = array();
		foreach($order_datas as $order_data) {
			
			$taobao_order_sn = $order_data['taobao_order_sn'];
			$null_value = false;
			//保证待处理数据字段不为空
			foreach($order_data as $key=>$val) {
				if(empty($val)) {
					$message .= " 结汇数据不完整！  淘宝订单号： {$taobao_order_sn}! 空值字段：{$key}!  " ."\r\n";
					$null_value = true;
				}
				if($key == 'rate' && $val == 0) {
					$message .= " 结汇数据不完整！ 汇率未导入！  " ."\r\n";
					die;
				}
			}
			if($null_value) {
				continue;
			}
			
			
			$inventory_out_data = array();
			
			$payment_time = str_ireplace(' ', '', $order_data['payment_time']);
			$payment_time = str_ireplace('-', '', $payment_time);
			$payment_time = substr($payment_time, 0, 8);
			
			$create_time = str_ireplace(' ', '', $order_data['create_time']);
			$create_time = str_ireplace('-', '', $create_time);
			$create_time = substr($create_time, 0, 8);
			
			$shipping_time = str_ireplace(' ', '', $order_data['shipping_time']);
			$shipping_time = str_ireplace('-', '', $shipping_time);
			$shipping_time = substr($shipping_time, 0, 8);
			$shipment_data = $shipping_time;
			
			$inventory_out_data['taobao_order_sn'] = $order_data['taobao_order_sn'];
			$inventory_out_data['create_time'] = $create_time;
			$inventory_out_data['payment_time'] = $payment_time;
			$inventory_out_data['shipping_time'] = $shipping_time;
			$inventory_out_data['goods_sn'] = $order_data['goods_sn'];
			$inventory_out_data['goods_num'] = -$order_data['goods_num'] .'.00';
			$inventory_out_data['rate'] = $order_data['rate'];
			$should_pay_postage = round($order_data['should_pay_postage'] / $order_data['rate'], 3);
			$should_pay_amount = round($order_data['should_pay_amount'] / $order_data['rate'], 3);
			$inventory_out_data['should_pay_postage'] = $should_pay_postage;
			$inventory_out_data['should_pay_amount'] = $should_pay_amount;
			$inventory_out_data['goods_amount'] = round(158 * $inventory_out_data['goods_num'] / $order_data['rate'], 3) ;    //商品金額（日元）=商品单价*数量/导入的订单对应汇率
			$inventory_out_data['bonus'] = round(($inventory_out_data['goods_amount'] + $inventory_out_data['should_pay_postage'] -  $inventory_out_data['should_pay_amount']) , 3);		//优惠金额 =（商品金额+订单运费-实际付款金额）
			$inventory_out_data['service_fee'] = $order_data['service_fee'];		//服务费（RMB？）
			$inventory_out_data['alipay_service_fee'] = round($should_pay_amount * 0.01, 3); //支付宝手续费
			$inventory_out_data['tmgj_service_fee'] = round($should_pay_amount * 0.02, 3);	 //天猫国际手续费
			$inventory_out_data['kao_income'] = round($should_pay_amount - $inventory_out_data['alipay_service_fee'] - $inventory_out_data['tmgj_service_fee'],0);  						 //实际付款金额 - 支付宝手续费 - 天猫国际手续费
			
			$inventory_out_list[] = $inventory_out_data;
		}

		$smarty->assign('message', $message);
		$smarty->assign('inventory_out_list', $inventory_out_list);
	}
	else if($request2 == 'form2_search2' || $request2 == 'form2_download2'){

		$condition = '';
		if(!empty($shipping_time2)) {
			$condition .=" and p.last_updated_stamp>'{$shipping_time2}' ";
		}
		if(!empty($shipping_time2_end)) {
			$condition .=" and p.last_updated_stamp<'{$shipping_time2_end}' ";
		}
		
		$sql ="select p.last_updated_stamp,g.goods_sn,g.barcode,p.item_code, p.quantity
		from ecshop.brand_kao_goods_info g
		left join ecshop.kuajing_bird_product p on g.barcode = p.item_code
		where  p.last_updated_stamp> '2015-12-01' " .$condition;
		$inventory_list = $db->getAll($sql);

		if($request2 == 'form2_search2') {
			$smarty->assign('inventory_list', $inventory_list);
		} 
		else if($request2 == 'form2_download2') {
			if(!empty($inventory_list)) {
				kao_download_inventory_txt($inventory_list);	
			}
		}
	}
	else if($request2 == 'form2_download1') {
			
		$condition = '';
		if(!empty($shipping_time2)) {
			$condition .=" and shipping_time>'{$shipping_time2}' ";
		}
		if(!empty($shipping_time2_end)) {
			$condition .=" and shipping_time<'{$shipping_time2_end}' ";
		}
	
		$sql = "select taobao_order_sn
					from brand_kao_order_info
					where type='P' " .$condition;
		$taobao_order_sns = $db->getCol($sql);
		if(!empty($taobao_order_sns)) {
			kao_download_inventory_data_txt($taobao_order_sns);
		} 
	}
}


$smarty->display ('huawang_data_import.htm' );


function insert_settlement_info () {
	global $db;
	
	// excel读取设置
	$tpl = array('结汇明细'  =>
			array('taobao_order_sn'=>'Partner_transaction_id',
					'jps_amount'=>'Amount',
					'rmb_amount'=>'Rmb_amount',
					'fee'=>'Fee',
					'settlement_amount'=>'Settlement',
					'rmb_settlement_amount'=>'Rmb_settlement',
					'currency'=>'Currency',
					'rate'=>'Rate',
					'payment_time'=>'Payment_time',
					'settlement_time'=>'Settlement_time',
					'type'=>'Type',
					'goods_name'=>'Remarks'
			));
	/* 文件上传并读取 */
	$uploader = new Helper_Uploader ();
	$max_size = 2 * $uploader->allowedUploadSize (); // 允许上传的最大值
		
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
	$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed,true );
	if (! empty ( $failed )) {
		$message = reset ( $failed );
		return $message;
	}
	
	/* 检查数据  */
	$rowset = $result ['结汇明细'];
	
	// 订单数据读取失败
	if (empty ( $rowset )) {
		$message = 'excel文件中没有数据,请检查文件';
		return $message;
	}
		
	$order_check_array = array('taobao_order_sn','jps_amount','rmb_amount','fee','settlement_amount','rmb_settlement_amount',
			'currency','rate','settlement_time','type','goods_name');
	foreach ($order_check_array as $check_column) {
		$region_array = Helper_Array::getCols($rowset, $check_column);
		$region_size = count($region_array);
		Helper_Array::removeEmpty ($region_array);
		if($region_size > count($region_array)){
			$message = '【'.$tpl['结汇明细导入'][$check_column].'】为必填数据，请检查该数据是否填写完整！';
			return $message;
		}
	}
		
	//验证订类型：P（销售订单），R（退货订单）
	foreach (Helper_Array::getCols($rowset, 'type') as $item_value) {
		if(!in_array($item_value,array('R', 'P'))){
			$message = "系统中不存在【Type】= '{$item_value}' 的订单";
			return $message;
		}
	}
	
	//验证汇率
	foreach (Helper_Array::getCols($rowset, 'rate') as $item_value) {
		if($item_value < 0 || $item_value > 0.06){
			$message = "rate信息错误：{$item_value}";
			return $message;
		}
	}
	
	
	try {
		$db->start_transaction();
		$update_count = 0;
		$insert_count = 0;
		foreach ($rowset as $row) {
			$order = array();
			$order['taobao_order_sn'] =  $row['taobao_order_sn'];
// 			$order['status'] =  'init';    //订单状态设置为init
			$order['jps_amount'] =  $row['jps_amount'];
			$order['rmb_amount'] =  $row['rmb_amount'];
			$order['fee'] =  $row['fee'];
			$order['settlement_amount'] =  $row['settlement_amount'];
			$order['rmb_settlement_amount'] =  $row['rmb_settlement_amount'];
			$order['currency'] =  'JPY';
			$order['rate'] =  $row['rate'];
			$order['payment_time'] =  $row['payment_time'];
			$order['settlement_time'] =  $row['settlement_time'];
			$order['type'] =  $row['type'];
			$order['goods_name'] =  $row['goods_name'];
			$order['action_user'] =  $_SESSION['admin_name'];
		

			//验证淘宝订单号在brand_kao_order_info中是否已经存在type的订单信息
			$sql = "select taobao_order_sn,rmb_amount from ecshop.brand_kao_order_info where taobao_order_sn = '{$row['taobao_order_sn']}' and type='{$row['type']}' ";
			$res = $db->getAll($sql, true);
			if (empty($res)) {
				// 在 brand_kao_order_info 中插入部分数据
				$sql = "
				insert into ecshop.brand_kao_order_info(
				taobao_order_sn, status,jps_amount, rmb_amount,fee,settlement_amount,rmb_settlement_amount,
				currency,rate,payment_time,settlement_time,type,goods_name,action_user)
				values (
				'{$order['taobao_order_sn']}','init','{$order['jps_amount']}','{$order['rmb_amount']}',
				'{$order['fee']}','{$order['settlement_amount']}','{$order['rmb_settlement_amount']}',
				'{$order['currency']}','{$order['rate']}','{$order['payment_time']}',
				'{$order['settlement_time']}','{$order['type']}','{$order['goods_name']}','{$order['action_user']}')
				";
				$db->query($sql);
				$insert_count++;
			} else {
				$sql = "
				update ecshop.brand_kao_order_info set 
				status = 'init',
				jps_amount = '{$order['jps_amount']}',
				rmb_amount = '{$order['rmb_amount']}',
				fee = '{$order['fee']}',
				settlement_amount = '{$order['settlement_amount']}',
				rmb_settlement_amount = '{$order['rmb_settlement_amount']}',
				currency = '{$order['currency']}',
				rate ='{$order['rate']}', 
				payment_time ='{$order['payment_time']}',
				settlement_time ='{$order['settlement_time']}',
				goods_name ='{$order['goods_name']}',
				action_user ='{$order['action_user']}'
				where taobao_order_sn = '{$order['taobao_order_sn']}' and type ='{$order['type']}'
				";
				$db->query($sql);
				$update_count++;
			}

		}
		$db->commit();
	} catch(Exception $e) {
			$db->rollback();
	}

	$return_message = "导入成功!  <br/> 插入" .$insert_count ."条数据！ <br/>  更新：".$update_count ."条数据！";
	
	$file->unlink ();
	return $return_message;
}

function insert_tianmao_info() {
	global $db;
	
	// excel读取设置
	$tpl = array('天猫交易流水'  =>
			array('taobao_order_sn'=>'Partner_transaction_id',
					'transaction_sn'=>'Transaction_id',
					'rate'=>'Rate',
					'type'=>'Type',
					'payment_time'=>'Payment_time'
			));
	/* 文件上传并读取 */
	$uploader = new Helper_Uploader ();
	$max_size = 2 * $uploader->allowedUploadSize (); // 允许上传的最大值

	
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
	$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed,true);
	if (! empty ( $failed )) {
		$message = reset ( $failed );
		return $message;
	}
	
	/* 检查数据  */
	$rowset = $result ['天猫交易流水'];
	
	// 订单数据读取失败
	if (empty ( $rowset )) {
		$message = 'excel文件中没有数据,请检查文件';
		return $message;
	}
	
	$order_check_array = array('taobao_order_sn','transaction_sn','type','rate');
	foreach ($order_check_array as $check_column) {
		$region_array = Helper_Array::getCols($rowset, $check_column);
		$region_size = count($region_array);
		Helper_Array::removeEmpty ($region_array);
		if($region_size > count($region_array)){
			$message = '【'.$tpl['天猫交易流水导入'][$check_column].'】为必填数据，请检查该数据是否填写完整！';
			return $message;
		}
	}
	
	//验证订类型：P（销售订单），R（退货订单）
	foreach (Helper_Array::getCols($rowset, 'type') as $item_value) {
		if(!in_array($item_value,array('R', 'P'))){
			$message = "系统中不存在【Type】= '{$item_value}' 的订单";
			return $message;
		}
	}
	
	try {
		$db->start_transaction();
		
		$insert_count = 0;
		$update_count = 0;
		foreach ($rowset as $row) {
			$order = array();
			$order['taobao_order_sn'] =  $row['taobao_order_sn'];
			$order['transaction_sn'] =  $row['transaction_sn'];
			$order['rate'] =  $row['rate'];
			$order['type'] =  $row['type'];
			$order['payment_time'] =  $row['payment_time'];
			// 在 brand_kao_order_info 中更新部分数据
			
			$sql = "select taobao_order_sn,transaction_sn from ecshop.brand_kao_order_info
					where taobao_order_sn='{$order['taobao_order_sn']}' and type='{$order['type']}' ";
			$res = $db->getAll($sql);
			if(empty($res)) {
				$sql_insert = "
						insert into ecshop.brand_kao_order_info (taobao_order_sn,status,transaction_sn,payment_time,rate,type)
						values ('{$order['taobao_order_sn']}','wait_init','{$order['transaction_sn']}','{$order['payment_time']}','{$order['rate']}', '{$order['type']}') ";
				$db->query($sql_insert);
				$insert_count++;
				continue;
			} else if ($res['transaction_sn'] == '') {
				$sql_update = "
					update ecshop.brand_kao_order_info set transaction_sn = '{$order['transaction_sn']}',payment_time='{$order['payment_time']}', rate = '{$order['rate']}'
					where taobao_order_sn = '{$order['taobao_order_sn']}' and type = '{$order['type']}'";
				$db->query($sql_update);
				$update_count++;
			} 
		}
		$db->commit();
		
	} catch (Exception $e) {
			$db->rollback();
	}
	

	$return_message = "导入成功!  <br/> 插入" .$insert_count ."条数据！ <br/>  更新：".$update_count ."条数据！";
	
	
	$file->unlink ();
	return $return_message;
}

function insert_shipping_info() {
	global $db;
	
	// excel读取设置
	$tpl = array('库存流水明细'  =>
			array('taobao_order_sn'=>'外部流水号',
				 'goods_barcode'=>'货品编码',
				 'goods_num'=>'出入数量',
				 'erp_order_sn'=>'ERP订单号',
				 'shipping_time'=>'出入库时间'
					
			));
	/* 文件上传并读取 */
	$uploader = new Helper_Uploader ();
	$max_size = 2 * $uploader->allowedUploadSize (); // 允许上传的最大值
	
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
	$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed ,true);
	if (! empty ( $failed )) {
		$message = reset ( $failed );
		return $message;
	}
	
	/* 检查数据  */
	$rowset = $result ['库存流水明细'];
	
	// 订单数据读取失败
	if (empty ( $rowset )) {
		$message = 'excel文件中没有数据,请检查文件';
		return $message;
	}
	
	$order_check_array = array('taobao_order_sn','goods_barcode','goods_num','shipping_time','erp_order_sn');
	foreach ($order_check_array as $check_column) {
		$region_array = Helper_Array::getCols($rowset, $check_column);
		$region_size = count($region_array);
		Helper_Array::removeEmpty ($region_array);
		if($region_size > count($region_array)){
			$message = '【'.$tpl['库存流水明细导入'][$check_column].'】为必填数据，请检查该数据是否填写完整！';
			return $message;
		}
	}
	
	try {
		$db->start_transaction();
	
		$update_count = 0;
		$insert_count = 0;
		foreach ($rowset as $row) {
			
			//数量若大于0，则忽略   (只记录销售订单)
			if($row['goods_num'] > 0) {
				continue;
			}
			$order = array();
			$order['taobao_order_sn'] =  $row['taobao_order_sn'];
			$order['goods_barcode'] =  $row['goods_barcode'];
			$order['goods_num'] =  $row['goods_num'];
			$order['shipping_time'] =  $row['shipping_time'];
			$order['type'] =  'P';
			$order['erp_order_sn'] =  $row['erp_order_sn'];
	
			// 在 brand_kao_order_info 中更新部分数据goods_barcode、goods_num
			$sql = "select taobao_order_sn,goods_barcode from ecshop.brand_kao_order_info
			where taobao_order_sn='{$order['taobao_order_sn']}' and type='{$order['type']}' ";
			$res = $db->getAll($sql);
			if(empty($res)) {
				$sql_insert = "
				insert into ecshop.brand_kao_order_info (taobao_order_sn,status,goods_barcode,goods_num,shipping_time,type,erp_order_sn)
				values ('{$order['taobao_order_sn']}','wait_init','{$order['goods_barcode']}', '{$order['goods_num']}','{$order['shipping_time']}','{$order['type']}','{$order['erp_order_sn']}') ";
				$db->query($sql_insert);
				$insert_count++;

			} else if ($res['goods_barcode'] == '') {
				$sql_update = "
				update ecshop.brand_kao_order_info set goods_barcode = '{$order['goods_barcode']}', goods_num = '{$row['goods_num']}',shipping_time= '{$row['shipping_time']}' ,erp_order_sn='{$row['erp_order_sn']}'  
				where taobao_order_sn = '{$order['taobao_order_sn']}' and type = '{$order['type']}'";
				$db->query($sql_update);
				
				$update_count++;
			}
		}
		$db->commit();
	
	} catch (Exception $e) {
		$db->rollback();
	}
	
	
	$return_message = "导入成功!  <br/> 插入" .$insert_count ."条数据！ <br/>  更新：".$update_count ."条数据！";
	
	
	$file->unlink ();
	return $return_message;
}


function insert_taobao_info () {
	global $db;
	
	// excel读取设置
	$tpl = array('淘宝订单'  =>
			array('taobao_order_sn'=>'订单编号',
					'should_pay_amount'=>'买家应付货款',
					'should_pay_postage'=>'买家应付邮费',
					'create_time'=>'订单创建时间'
			));
	/* 文件上传并读取 */
	$uploader = new Helper_Uploader ();
	$max_size = 2 * $uploader->allowedUploadSize (); // 允许上传的最大值

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
	$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed ,true);
	if (! empty ( $failed )) {
		$message = reset ( $failed );
		return $message;
	}
	
	/* 检查数据  */
	$rowset = $result ['淘宝订单'];

	// 订单数据读取失败
	if (empty ( $rowset )) {
		$message = 'excel文件中没有数据,请检查文件';
		return $message;
	}
	
	$order_check_array = array('taobao_order_sn','should_pay_amount','should_pay_postage','create_time');
	foreach ($order_check_array as $check_column) {
		$region_array = Helper_Array::getCols($rowset, $check_column);
		$region_size = count($region_array);
		Helper_Array::removeEmpty ($region_array);
		if($region_size > count($region_array)){
			$message = '【'.$tpl['淘宝订单导入'][$check_column].'】为必填数据，请检查该数据是否填写完整！';
			return $message;
		}
	}
	
	try {
		$db->start_transaction();
	
		$update_count = 0;
		$insert_count = 0;
		foreach ($rowset as $row) {
			$order = array();
// 			$taobao_order_sn = findNum($row['taobao_order_sn']);
			$taobao_order_sn = str_ireplace('=', '', $row['taobao_order_sn']);
			$taobao_order_sn = str_ireplace('"', '', $taobao_order_sn);
			$order['taobao_order_sn'] =  $taobao_order_sn;
			$order['should_pay_amount'] =  $row['should_pay_amount'];
			$order['should_pay_postage'] =  $row['should_pay_postage'];
			$order['create_time'] =  $row['create_time'];

			// 在 brand_kao_order_info 中更新部分数据goods_barcode、goods_num
			$sql = "select taobao_order_sn,should_pay_amount from ecshop.brand_kao_order_info
			where taobao_order_sn='{$order['taobao_order_sn']}' and type='P' ";
			$res = $db->getAll($sql);
			if(empty($res)) {
				$sql_insert = "
				insert into ecshop.brand_kao_order_info (taobao_order_sn,status,should_pay_amount,should_pay_postage,create_time, type)
				values ('{$order['taobao_order_sn']}','wait_init','{$order['should_pay_amount']}', '{$order['should_pay_postage']}','{$order['create_time']}','P') ";
				$db->query($sql_insert);
				$insert_count++;
				continue;
			} else if ($res['should_pay_amount'] == '') {
				$sql_update = "
				update ecshop.brand_kao_order_info set should_pay_amount = '{$order['should_pay_amount']}', should_pay_postage = '{$row['should_pay_postage']}', create_time = '{$row['create_time']}'
				where taobao_order_sn = '{$order['taobao_order_sn']}' and type = 'P'";
				$db->query($sql_update);
				$update_count++;
			}
		}
		$db->commit();
	
	} catch (Exception $e) {
		$db->rollback();
	}
	
	$return_message = "导入成功!  <br/> 插入" .$insert_count ."条数据！ <br/>  更新：".$update_count ."条数据！";
	
	$file->unlink ();
	return $return_message;
}

function insert_service_fee_info () {
	global $db;
	
	// excel读取设置
	$tpl = array('账单明细'  =>
			array('erp_order_sn'=>'物流宝订单号',
					'service_fee_category'=>'费用项目',
					'service_fee'=>'金额' 
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
	$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed, true);
	if (! empty ( $failed )) {
		$message = reset ( $failed );
		return $message;
	}
	
	/* 检查数据  */
	$rowset = $result ['账单明细'];
	

	// 订单数据读取失败
	if (empty ( $rowset )) {
		$message = 'excel文件中没有数据,请检查文件';
		return $message;
	}
	
	$order_check_array = array('erp_order_sn','service_fee_category','service_fee');
	foreach ($order_check_array as $check_column) {
		$region_array = Helper_Array::getCols($rowset, $check_column);
		$region_size = count($region_array);
		Helper_Array::removeEmpty ($region_array );
		if($region_size > count($region_array)){
			$message = '【'.$tpl['账单明细'][$check_column].'】为必填数据，请检查该数据是否填写完整！';
			return $message;
		}
	}
	
	try {
		$db->start_transaction();
	
		$update_count = 0;
		$insert_count = 0;
		foreach ($rowset as $row) {
			$order = array();
			$erp_order_sn = $row['erp_order_sn'];
			$order['erp_order_sn'] =  $erp_order_sn;
			$order['service_fee_category'] =  $row['service_fee_category'];
			$order['service_fee'] =  $row['service_fee'];
			$order['action_user'] =  $_SESSION['admin_name'];
			
			// 在 brand_kao_order_info 中更新部分数据goods_barcode、goods_num
			$sql = "select taobao_order_sn,erp_order_sn,service_fee_category from ecshop.brand_kao_order_info
			where erp_order_sn='{$order['erp_order_sn']}' and type='P' ";
			$res = $db->getAll($sql);
			if(empty($res)) {
				$sql_insert = "
				insert into ecshop.brand_kao_order_info (status,service_fee_category,service_fee , action_user , type , created_stamp, last_updated_stamp,erp_order_sn )
				values ('wait_init','{$order['service_fee_category']}', '{$order['service_fee']}', '{$order['action_user']}'  ,'P' , now(), now(), '{$order['erp_order_sn']}') ";
				$db->query($sql_insert);
				$insert_count++;
				continue;
			} else if ($res['service_fee_category'] == '') {
				$sql_update = "
				update ecshop.brand_kao_order_info set service_fee_category = '{$order['service_fee_category']}', service_fee = '{$order['service_fee']}' , last_updated_stamp = now(),erp_order_sn='{$order['erp_order_sn']}'
				where erp_order_sn = '{$order['erp_order_sn']}' and type = 'P'";
				$db->query($sql_update);
				$update_count++;
			}
		}
		$db->commit();
	
	} catch (Exception $e) {
		$db->rollback();
	}
	
	$return_message = "导入成功!  <br/> 插入" .$insert_count ."条数据！ <br/>  更新：".$update_count ."条数据！";
	
	$file->unlink ();
	return $return_message;
}


function findNum($str=''){
	$str=trim($str);
	if(empty($str)){return '';}
	$temp=array('1','2','3','4','5','6','7','8','9','0');
	$result='';
	for($i=0;$i<strlen($str);$i++){
		if(in_array($str[$i],$temp)){
			$result.=$str[$i];
		}
	}
	return $result;
}

//导出库存数据
function kao_download_inventory_txt($inventory_list) {
	$datas = array();
	$line = array();
	foreach ($inventory_list as  $goods_data) {
			
		$send_data = date("Ymd", strtotime("-0 days", time()));
		$line['send_data'] = $send_data;
		$line['time'] = '100001';
		$line['facility_sn'] = '49025140';		//仓库编码
		$line['utf1'] = '';
		$line['utf2'] = '';
		$line['goods_sn'] = $goods_data['goods_sn'];
		$line['utf3'] = '';
		$line['quantity'] = $goods_data['quantity'] .'.000';
		$line['PCS'] = 'PCS';
		$datas[] = $line;
	}
		
	$file_contents = '';
	foreach($datas as $data) {
		foreach($data as $key=>$val) {
			$file_contents .= $val;
			$file_contents .= '	';
		}
		$file_contents = trim($file_contents);
		$file_contents .= "\r\n";
	}
	$time = date("YmdHis", strtotime("-0 days", time()));
	$xml_file_name = 'ZMMT314_03078_01_BKAA3_' .$time .'221.TXT';
	$xml_file_path = ROOT_PATH .'Kao/txtfile/' .$xml_file_name;
		
	downloadFile($xml_file_name, $file_contents);
}

//导出出库数据
function kao_download_inventory_data_txt($taobao_order_sns) {
	global $db;
	$sql ="
			select g.goods_sn,o.goods_barcode,p.item_code, p.quantity, o.payment_time,o.create_time,o.shipping_time,
				o.taobao_order_sn,o.goods_num,o.rate,o.should_pay_postage,o.should_pay_amount,o.service_fee, g.goods_name
			from brand_kao_order_info o
			left join brand_kao_goods_info g on o.goods_barcode = g.barcode
			left join kuajing_bird_product p on o.goods_barcode = p.item_code
			where o.taobao_order_sn " .db_create_in($taobao_order_sns) ;
	$order_datas = $db->getAll($sql);
	
	
	$datas = array();
	$first_line = $second_line = array();
	foreach ($order_datas as  $order_data) {
		
		$null_value = false;
		//保证待处理数据字段不为空
		foreach($order_data as $key=>$val) {
			if(empty($val)) {
				$message .= " 结汇数据不完整，无法处理！  淘宝订单号： {$taobao_order_sn}! 空值字段：{$key}!  " ."\r\n";
				$null_value = true;
			}
		}
		if($null_value) {
			continue;
		}

		$payment_time = str_ireplace(' ', '', $order_data['payment_time']);
		$payment_time = str_ireplace('-', '', $payment_time);
		$payment_time = substr($payment_time, 0, 8);
		
		$create_time = str_ireplace(' ', '', $order_data['create_time']);
		$create_time = str_ireplace('-', '', $create_time);
		$create_time = substr($create_time, 0, 8);
		
		$shipping_time = str_ireplace(' ', '', $order_data['shipping_time']);
		$shipping_time = str_ireplace('-', '', $shipping_time);
		$shipping_time = substr($shipping_time, 0, 8);
		$shipment_data = $shipping_time;
		
		$first_line['coefficient'] = '0049025139';
		$first_line['taobao_order_sn'] = $order_data['taobao_order_sn'];
		$first_line['line'] = '0';
		$first_line['create_time'] = $create_time;
		$first_line['payment_time'] = $payment_time;
		$first_line['shipping_time'] = $shipping_time;
// 				$first_line['currency'] = 'CNY';
		$first_line['currency'] = 'JPY';
		$first_line['goods_sn'] = '';
// 				$first_line['goods_name'] = '';
		$first_line['goods_num'] =  -$order_data['goods_num'] .'.000';
		$first_line['units'] = 'pcs';
// 				$first_line['goods_price'] = '158.00';
		$should_pay_postage = round($order_data['should_pay_postage'] / $order_data['rate'], 2);
		$should_pay_amount = round($order_data['should_pay_amount'] / $order_data['rate'], 2);
		$first_line['goods_amount'] = round(158 * $first_line['goods_num'] / $order_data['rate'], 2) ;    //商品金額（日元）=商品单价*数量/导入的订单对应汇率			
		$first_line['goods_amount2'] = $should_pay_amount - $should_pay_postage;
		
		$first_line['bonus'] = round(($first_line['goods_amount'] + $should_pay_postage -  $should_pay_amount), 2);		//优惠金额 =（商品金额+订单运费-实际付款金额）/导入的订单对应汇率
		$first_line['should_pay_postage'] = $should_pay_postage;		//实际邮费金额 = 应付邮费 /汇率
		$first_line['should_pay_amount'] = $should_pay_amount;		//实际付款金额= 应付金额 /汇率
// 				$first_line['service_fee'] = $order_data['service_fee'];		//服务费（RMB）
		$first_line['alipay_service_fee'] = round($should_pay_amount * 0.01, 2); //支付宝手续费
		$first_line['tmgj_service_fee'] = round($should_pay_amount * 0.02, 2);	 //天猫国际手续费
// 				$first_line['service_fee2'] = $order_data['service_fee'];		//服务费（RMB）
// 				$first_line['kao_income'] = round($should_pay_amount - $first_line['alipay_service_fee'] - $first_line['tmgj_service_fee'], 0) ;  						 //实际付款金额 - 支付宝手续费 - 天猫国际手续费			
				
		$second_line['coefficient'] = '0049025139';
		$second_line['taobao_order_sn'] = $order_data['taobao_order_sn'];
		$second_line['line'] = '10';
		$second_line['create_time'] = $create_time;
		$second_line['payment_time'] = $payment_time;
		$second_line['shipping_time'] = $shipping_time;
		$second_line['currency'] = '';
		$second_line['goods_sn'] = $order_data['goods_sn'];
// 				$second_line['goods_name'] = $order_data['goods_name'];
		$second_line['goods_num'] =  -$order_data['goods_num'] .'.000';
		$second_line['units'] = 'pcs';
// 				$second_line['goods_price'] = '158.00';
		$second_line['goods_amount'] = round(158 * $second_line['goods_num'] / $order_data['rate'], 2) ;    //商品金額（日元）=商品单价*数量/导入的订单对应汇率
		$second_line['goods_amount2'] =  $should_pay_amount - $should_pay_postage;    
		$second_line['bonus'] = round(($second_line['goods_amount'] + $should_pay_postage -  $should_pay_amount), 2);		//优惠金额 =（商品金额+订单运费-实际付款金额）/导入的订单对应汇率
		$second_line['should_pay_postage'] = $should_pay_postage;		//实际邮费金额 = 应付邮费 /汇率
		$second_line['should_pay_amount'] = $should_pay_amount;		//实际付款金额= 应付金额 /汇率				
		
		$datas[] = $first_line;
		$datas[] = $second_line;
	}
	
	$file_contents = '';
	foreach($datas as $data) {
		foreach($data as $key=>$val) {
			$file_contents .= $val;
			$file_contents .= '	';
		}
		$file_contents = trim($file_contents);
		$file_contents .= "\r\n";
	}
		
	$txt_file_name = 'ZSDT000_00000_01.TXT';
	
	downloadFile($txt_file_name, $file_contents);
}

function kao_download_excel($results) {

	// 载入excel库
	set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
	require 'PHPExcel.php';
	require 'PHPExcel/IOFactory.php';

	$filename = "花王结汇数据.xlsx";
	$excel = new PHPExcel();

	//表1 ： 入金明細レイアウト（集約 ｰ１）

	$title1 = '入金明細レイアウト（集約 ｰ１）';
	$excel->setActiveSheetIndex(0);
	$sheet = $excel->getActiveSheet();
	$sheet->setTitle($title1);

	//标题行
	$r1 = 1;
	$sheet->setCellValue("A{$r1}", '入金管理番号');
	$sheet->setCellValue("B{$r1}", '入金日');
	$sheet->setCellValue("C{$r1}",'入金金額（人民元）');
	$sheet->setCellValue("D{$r1}", '入金金額（日本円）');
	$sheet->setCellValue("E{$r1}", '為替レート');
	$r1++;

	$settlement_amount_sum_rmb = 0;
	$settlement_amount_sum_jps = 0;
	foreach ($results as $res)
	{
		$settlement_amount_sum_rmb += $res['rmb_settlement_amount'];
		$settlement_amount_sum_jps += $res['jps_settlement_amount'];
	}
	$rate = round($settlement_amount_sum_jps / $settlement_amount_sum_rmb, 5);

	//第一行
	$sheet->setCellValue("A{$r1}", $results[0]['alipay_order_sn']);
	$sheet->setCellValue("B{$r1}", $results[0]['settlement_date']);
	$sheet->setCellValue("C{$r1}", $settlement_amount_sum_rmb);
	$sheet->setCellValue("D{$r1}", $settlement_amount_sum_jps);
	$sheet->setCellValue("E{$r1}", $rate);

	// 表2 ： 入金明細レイアウト（集約 ｰ２）

	$excel->createSheet();
	$excel->setactivesheetindex(1);

	$title2 = ' 入金明細レイアウト（集約 ｰ２）';
	$sheet2 = $excel->getActiveSheet();
	$sheet2->setTitle($title2);

	//标题行
	$r2 = 1;
	$sheet2->setCellValue("A{$r2}", '入金管理番号');
	$sheet2->setCellValue("B{$r2}", '入金日');
	$sheet2->setCellValue("C{$r2}",	'取引先コード');
	$sheet2->setCellValue("D{$r2}", '発注番号');
	$sheet2->setCellValue("E{$r2}", '入金金額（人民元）');
	$sheet2->setCellValue("F{$r2}", '入金金額（日本円）');
	$sheet2->setCellValue("G{$r2}", '入金日為替レート');
	$r2++;

	foreach($results as $res){
		$sheet2->setCellValue("A{$r2}", $res['alipay_order_sn']);
		$sheet2->setCellValue("B{$r2}", $res['settlement_date']);
		$sheet2->setCellValue("C{$r2}", $res['coefficient']);
		$sheet2->setCellValueExplicit("D{$r2}", $res['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet2->setCellValue("E{$r2}", $res['rmb_settlement_amount']);
		$sheet2->setCellValue("F{$r2}", $res['jps_settlement_amount']);
		$sheet2->setCellValue("G{$r2}", $res['rate']);
		$r2++;
	}

	// 表3 ： 入金明細レイアウト（明細 ｰ１）

	$excel->createSheet();
	$excel->setactivesheetindex(2);

	$title3 = ' 入金明細レイアウト（明細 ｰ１）';
	$sheet3 = $excel->getActiveSheet();
	$sheet3->setTitle($title3);

	//标题行
	$r3 = 1;
	$sheet3->setCellValue("A{$r3}", '入金管理番号');
	$sheet3->setCellValue("B{$r3}", '入金日');
	$sheet3->setCellValue("C{$r3}", '取引先コード');
	$sheet3->setCellValue("D{$r3}", '発注番号');
	$sheet3->setCellValue("E{$r3}", '注文日');
	$sheet3->setCellValue("F{$r3}", '消費者入金確認日');
	$sheet3->setCellValue("G{$r3}", '出荷日');
	$sheet3->setCellValue("H{$r3}", '決済日');
	$sheet3->setCellValue("I{$r3}", '商品代合計金額（日本円）');
	$sheet3->setCellValue("J{$r3}", '配送料（消費者負担分）（日本円）');
	$sheet3->setCellValue("K{$r3}", '合計請求金額（消費者）（日本円）');
	$sheet3->setCellValue("L{$r3}", '支払手数料（アリペイ） 　（日本円）');
	$sheet3->setCellValue("M{$r3}", '成約手数料（天猫国際）（日本円）');
	$sheet3->setCellValue("N{$r3}", '入金合計金額（人民元）');
	$sheet3->setCellValue("O{$r3}", '入金合計金額（日本円）');
	$sheet3->setCellValue("P{$r3}", '入金日為替レート');
	$r3++;

	foreach($results as $res){
		$sheet3->setCellValueExplicit("A{$r3}", $res['transaction_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet3->setCellValue("B{$r3}", $res['settlement_date']);
		$sheet3->setCellValue("C{$r3}", $res['coefficient']);
		$sheet3->setCellValueExplicit("D{$r3}", $res['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet3->setCellValue("E{$r3}", $res['create_date']);
		$sheet3->setCellValue("F{$r3}", $res['payment_date']);
		$sheet3->setCellValue("G{$r3}", $res['shipping_date']);
		$sheet3->setCellValue("H{$r3}", $res['settlement_date']);
		$sheet3->setCellValue("I{$r3}", $res['goods_amount']);
		$sheet3->setCellValue("J{$r3}", $res['shipping_fee']);
		$sheet3->setCellValue("K{$r3}", $res['order_amount']);
		$sheet3->setCellValue("L{$r3}", $res['alipay_fee']);
		$sheet3->setCellValue("M{$r3}", $res['tianmao_pay_fee']);
		$sheet3->setCellValue("N{$r3}", $res['rmb_settlement_amount']);
		$sheet3->setCellValue("O{$r3}", $res['jps_settlement_amount']);
		$sheet3->setCellValue("P{$r3}", $res['rate']);
		$r3++;
	}


	// 表4 ：入金明細レイアウト（明細 ｰ２）

	$excel->createSheet();
	$excel->setactivesheetindex(3);

	$title4 = '入金明細レイアウト（明細 ｰ２）';
	$sheet4 = $excel->getActiveSheet();
	$sheet4->setTitle($title4);

	//标题行
	$r4 = 1;
	$sheet4->setCellValue("A{$r4}", '入金管理番号');
	$sheet4->setCellValue("B{$r4}", '入金日');
	$sheet4->setCellValue("C{$r4}", '取引先コード');
	$sheet4->setCellValue("D{$r4}", '発注番号');
	$sheet4->setCellValue("E{$r4}", '注文日');
	$sheet4->setCellValue("F{$r4}", '消費者入金確認日');
	$sheet4->setCellValue("G{$r4}", '出荷日');
	$sheet4->setCellValue("H{$r4}", '決済日');
	$sheet4->setCellValue("I{$r4}", '明細番号');
	$sheet4->setCellValue("J{$r4}", '品目');
	$sheet4->setCellValue("K{$r4}", '品目名称');
	$sheet4->setCellValue("L{$r4}", '数量');
	$sheet4->setCellValue("M{$r4}", '数量単位');
	$sheet4->setCellValue("N{$r4}", '伝票通貨');
	$sheet4->setCellValue("O{$r4}", '商品単価（人民元）');
	$sheet4->setCellValue("P{$r4}", '商品金額（日本円）');
	$sheet4->setCellValue("Q{$r4}", '値引き金額（日本円）');
	$sheet4->setCellValue("R{$r4}", '商品代合計金額（日本円）');
	$sheet4->setCellValue("S{$r4}", '配送料');
	$sheet4->setCellValue("T{$r4}", '合計請求金額（消費者）（日本円）');
	$r4++;

	foreach($results as $res){
		$sheet4->setCellValueExplicit("A{$r4}", $res['transaction_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet4->setCellValue("B{$r4}", $res['settlement_date']);
		$sheet4->setCellValue("C{$r4}", $res['coefficient']);
		$sheet4->setCellValueExplicit("D{$r4}", $res['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
		$sheet4->setCellValue("E{$r4}", $res['create_date']);
		$sheet4->setCellValue("F{$r4}", $res['payment_date']);
		$sheet4->setCellValue("G{$r4}", $res['shipping_date']);
		$sheet4->setCellValue("H{$r4}", $res['settlement_date']);
		$sheet4->setCellValue("I{$r4}", $res['coefficient_sn']);
		$sheet4->setCellValue("J{$r4}", $res['goods_sn']);
		$sheet4->setCellValue("K{$r4}", $res['goods_name']);
		$sheet4->setCellValue("L{$r4}", $res['goods_num']);
		$sheet4->setCellValue("M{$r4}", $res['num_unit']);
		$sheet4->setCellValue("N{$r4}", $res['summons_money']);
		$sheet4->setCellValue("O{$r4}", $res['goods_price_rmb']);
		$sheet4->setCellValue("P{$r4}", $res['goods_price_jps']);
		$sheet4->setCellValue("Q{$r4}", $res['bonus']);
		$sheet4->setCellValue("R{$r4}", $res['goods_amount']);
		$sheet4->setCellValue("S{$r4}", $res['shipping_fee']);
		$sheet4->setCellValue("T{$r4}", $res['order_amount']);
		$r4++;

	}

	if (!headers_sent())
	{
		$output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$output->setOffice2003Compatibility(true);
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		$output->save('php://output');
		
		exit();
	}

}

function downloadFile($file, $contents){
	$file_name = $file;
	Header( "Content-type:   application/octet-stream ");
	Header( "Accept-Ranges:   bytes ");
	header( 'Content-Disposition:   attachment;   filename= "' .$file_name .'"');
	header( "Expires:   0 ");
	header( "Cache-Control:   must-revalidate,   post-check=0,   pre-check=0 ");
	header( "Pragma:   public ");
	echo $contents;
	exit();
}

function get_actual_todo_orders() {
	global $db;

	$settlement_time = date("Y-m-d",strtotime("-0 days",time()));

	$sql = "select ko.*,kg.goods_sn
	from ecshop.brand_kao_order_info ko
	left join ecshop.brand_kao_goods_info kg on ko.goods_barcode = kg.barcode
	left join ecshop.brand_kao_order_result_info kro on kro.taobao_order_sn = ko.taobao_order_sn and kro.type= ko.type
	where ko.status = 'init' and ko.settlement_time>'{$settlement_time}'  and kro.order_result_id is null";
	$orders = $db->getAll($sql);

	return $orders;
}

function deal_data_actual($orders) {
	global $db;
	
	$countAll = count($orders);
	$coefficient_sn = 0;
	$message ="\r\n";
	$is_send_email = false;
	$countSuccess = 0;
	try {
		$db->start_transaction();
		foreach($orders as $order) {
			$null_value = false;
			$order_result = array();
				
			$taobao_order_sn = $order['taobao_order_sn'];
	
			//如果是退货订单  字段值  == 销售订单值  ，出入库数量/商品编码/出入库时间 /买家应付金额/买家应付邮费/订单时间
			if($order['type'] == 'R') {
				$sql = "select o.transaction_sn,o.goods_barcode,o.shipping_time,o.should_pay_amount,o.should_pay_postage,o.create_time,g.goods_sn
				from ecshop.brand_kao_order_info o
				left join ecshop.brand_kao_goods_info g	on o.goods_barcode = g.barcode
				where o.taobao_order_sn='{$taobao_order_sn}' and o.type='P' limit 1
				";
				$sale_order = $db->getAll($sql);
	
				if(!empty($sale_order)) {
					if(!empty($sale_order[0]['transaction_sn'])) {
						$order['transaction_sn'] = $sale_order[0]['transaction_sn'];
					}
					$order['goods_barcode'] = $sale_order[0]['goods_barcode'];
					$order['shipping_time'] = $sale_order[0]['shipping_time'];
					$order['should_pay_amount'] = $sale_order[0]['should_pay_amount'];
					$order['should_pay_postage'] = $sale_order[0]['should_pay_postage'];
					$order['fee'] = 0;
					$order['create_time'] = $sale_order[0]['create_time'];
					$order['goods_sn'] = $sale_order[0]['goods_sn'];
				} else {
					$message .= " 退货订单缺少原订单信息！  淘宝订单号： {$taobao_order_sn}!  ";
					$null_value = true;
					$is_send_email = true;
				}
			}
	
			//保证待处理数据字段不为空
			foreach($order as $key=>$val) {
				if(empty($val) && !in_array($key,array('goods_num','service_fee','service_fee_category','created_stamp','last_updated_stamp','action_user','fee'))) {
					$message .= " 结汇数据不完整，无法处理！  淘宝订单号： {$taobao_order_sn}! 空值字段：{$key}!  " ."<br />";
					$null_value = true;
					$is_send_email = true;
				}
			}
			
			if($null_value) {
				continue;
			}
	
			$order_result['taobao_order_sn'] = $taobao_order_sn;
			$order_result['transaction_sn'] = $order['transaction_sn'];
			$order_result['type'] = $order['type'];
			$order_result['coefficient'] = '49025139';
			$order_result['num_unit'] = 'PCS';
			$order_result['summons_money'] = 'JPY';
			$order_result['is_send'] = 'N';
			$order_result['goods_sn'] = $order['goods_sn'];
			$order_result['goods_name'] = $order['goods_name'];
				
			//对时间格式化
			if(!empty($order['create_time'])) {
				$order_result['create_date'] = date("Ymd",strtotime($order['create_time']));
			}
			if(!empty($order['payment_time'])) {
				$order_result['payment_date'] = date("Ymd",strtotime($order['payment_time']));
			}
			if(!empty($order['settlement_time'])) {
				$order_result['settlement_date'] = date("Ymd",strtotime($order['settlement_time']));
			}
			if(!empty($order['shipping_time'])) {
				$order_result['shipping_date']= date("Ymd",strtotime($order['shipping_time']));
			}
	
			//订单合计金额（日元） ：合計請求金額（消費者）（日本円）
			$order_result['order_amount'] = $order['jps_amount'];
				
			//should_pay_postage * rate 取整
			$order_result['shipping_fee'] = intval($order['should_pay_postage'] * $order_result['rate']);  //RMB--日元
				
			//商品合计金额（日元） ： 商品代合計金額（日本円） = order_amount- shipping_fee
			$order_result['goods_amount'] = $order['jps_amount'] - $order_result['shipping_fee'];
				
			//日元结算金额
			$order_result['jps_settlement_amount'] = $order['settlement_amount'];
			 
			//RMB结算金额
			$order_result['rmb_settlement_amount'] = $order['rmb_settlement_amount'];
				
			//   1/rate取5位小数
			$order_result['rate'] = round(1 / $order['rate'], 5);
	
			// 阿里支付手续费（日元）: 1/3fee  	; 天猫国际支付手续费（日元）2/3fee
			$order_result['alipay_fee'] = round($order['fee'] / 3, 0);
			$order_result['tianmao_pay_fee'] = $order['fee'] - $order_result['alipay_fee'];
	
			//入款管理号码，ali+ settlement_date
			$order_result['alipay_order_sn'] = 'ali' .$order_result['settlement_date'];
				
			//等差数列，10开始，每次递增10
			$coefficient_sn += 10;
			$order_result['coefficient_sn'] = $coefficient_sn;
	
			//商品单价RMB（158）
			$order_result['goods_price_rmb'] = 158;
	
			//商品数量（销售单：取负； 退货单：计算）； 算法：向下取整（商品合计金额（日元） / 158 / 汇率）
			if('P' == $order_result['type']) {
				$order_result['goods_num'] = -$order['goods_num'];
			} else if('R' == $order_result['type']) {
				//商品单价：158
				$order_result['goods_num'] = floor($order_result['goods_amount'] / 158 / $order_result['rate']);
			}
	
			//商品合计原价（日元） ： 商品单价*数量  * 汇率
			$order_result['goods_price_jps'] = round(158 * $order_result['goods_num'] * $order_result['rate'], 0);
				
			//优惠金额（日元）
			$order_result['bonus'] = $order_result['goods_price_jps']  - $order_result['goods_amount'];
	
	
			$sql = "insert into ecshop.brand_kao_order_result_info (
			taobao_order_sn,transaction_sn,type,coefficient,num_unit,summons_money,is_send,goods_sn,goods_name,
			create_date,payment_date,settlement_date,shipping_date,order_amount,shipping_fee,goods_amount,
			jps_settlement_amount,rmb_settlement_amount,rate,alipay_fee,tianmao_pay_fee,alipay_order_sn,
			coefficient_sn,goods_price_rmb,goods_num,goods_price_jps,bonus)
			values(
			'{$order_result['taobao_order_sn']}','{$order_result['transaction_sn']}','{$order_result['type']}',
			{$order_result['coefficient']},'{$order_result['num_unit']}','{$order_result['summons_money']}',
			'{$order_result['is_send']}',{$order_result['goods_sn']},'{$order_result['goods_name']}',
			'{$order_result['create_date']}','{$order_result['payment_date']}','{$order_result['settlement_date']}',
			'{$order_result['shipping_date']}','{$order_result['order_amount']}',{$order_result['shipping_fee']},
			{$order_result['goods_amount']},{$order_result['jps_settlement_amount']},{$order_result['rmb_settlement_amount']},
			{$order_result['rate']},{$order_result['alipay_fee']},{$order_result['tianmao_pay_fee']},
			'{$order_result['alipay_order_sn']}',{$order_result['coefficient_sn']},{$order_result['goods_price_rmb']},
			'{$order_result['goods_num']}',{$order_result['goods_price_jps']},{$order_result['bonus']}
			)
			";
			$db->query($sql);

			$sql_update = "update ecshop.brand_kao_order_info set status = 'finished' where taobao_order_sn = '{$order_result['taobao_order_sn']}'  and type='{$order_result['type']}' ";
			$db->query($sql_update);
			$countSuccess++;
		}
	
		$db->commit();
	} catch (Exception $e) {
		$db->rollback();
	}

	return $message;
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