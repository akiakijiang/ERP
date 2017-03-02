<?php

/*
 * 万象物流订单运单号导入ERP
 */
define('IN_ECS', true);
require ('includes/init.php');
require ('function.php');
//include_once('../RomeoApi/lib_currency.php');
//require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
//require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

//admin_priv('wx_out_ship_pull_tn');
$act = $_REQUEST['act'];
$flag = true;
if (!empty ($act) && $act == 'action_out') {
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	$final_out = '';
	$error_out = array ();
	do {
		/* 文件上传并读取 */
		@ set_time_limit(300);
		$uploader = new Helper_Uploader();
		$max_size = $uploader->allowedUploadSize(); // 允许上传的最大值
		if (!$uploader->existsFile('excel')) {
			$final_out .= '没有选择上传文件，或者文件上传失败';
			$flag = false;
			break;
		}
		// 取得要上传的文件句柄
		$file = $uploader->file('excel');

		// 检查上传文件
		if (!$file->isValid('xls, xlsx', $max_size)) {
			$final_out .= "   " . '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB';
			$flag = false;
			break;
		}
		$tpl = array (
			'万象物流运单号导入' => array (
				'taobao_order_sn' => '淘宝订单号',
				'tracking_number' => '运单号'
			)
		);
		// 读取excel
		$record = excel_read($file->filepath(), $tpl, $file->extname(), $failed);
		//判断是否符合条件
		if (sizeof($record['万象物流运单号导入']) == 0) {
			$final_out .= " 导入的数据为空";
			$flag = false;
			break;
		} else
			if (sizeof($record['万象物流运单号导入']) > 1000) {
				$final_out .= " 导入的数据行数不能超过1000行，请成两个文件分别导入";
				$flag = false;
				break;
			}

		$i = 1;
		$j = 0;
		foreach ($record['万象物流运单号导入'] as $key => $rec) {
			global $db;
			$taobao_order_sn = trim($rec['taobao_order_sn']);
			$tracking_number = trim($rec['tracking_number']);
			//判断订单是否满足（百事，桂格，依云，媛本 + 电商服务上海仓 + 万象物流  + 运单号为空）
			$sql = "select oi.order_sn,oi.taobao_order_sn,s.shipment_id,s.tracking_number,oi.shipping_id," .
				" oi.shipping_name,oi.facility_id,f.facility_name,p.name,oi.party_id " .
				" from ecshop.ecs_order_info oi" .
				" inner join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8) " .
				" inner join romeo.shipment s on s.shipment_id = os.shipment_id " .
				" inner join romeo.party p on p.party_id = oi.party_id " .
				" inner join romeo.facility f on f.facility_id = oi.facility_id" .
				" where oi.taobao_order_sn = '{$taobao_order_sn}' limit 1 ";
			//oi.party_id in (65558,65636,65606,65608,65632) and oi.facility_id = '19568549' and oi.shipping_id =51 and (s.tracking_number is null or s.tracking_number = '' ) 
			$order = $db->getRow($sql);
			
			$sql = "SELECT count(1) from romeo.shipment where tracking_number = '{$tracking_number}' ";
			$is_exists = $db->getOne($sql);

			if (empty ($order['order_sn'])) {
				$error_out[$j] = 'EXCEL 第' . $i . ' 行' . ', 淘宝订单号：' . $rec['taobao_order_sn'] . ' 未找到对应ERP系统内订单号.';
			} elseif (!in_array($order['party_id'],array(65636,65606,65608,65632))) {
				$error_out[$j] = 'EXCEL 第' . $i . ' 行' . ', 淘宝订单号：' . $rec['taobao_order_sn'] . ' 对应业务组为“'.$order['name'].'”不允许通过该页面导入运单';
			} elseif (!empty($order['tracking_number'])) {
				$error_out[$j] = 'EXCEL 第' . $i . ' 行' . ', 淘宝订单号：' . $rec['taobao_order_sn'] . ' 已经存在运单号“'.$order['tracking_number'].'"';
			} elseif ($order['facility_id'] != '19568549') {
				$error_out[$j] = 'EXCEL 第' . $i . ' 行' . ', 淘宝订单号：' . $rec['taobao_order_sn'] . ' 对应ERP系统仓为“'.$order['facility_name'].'”不允许通过该页面导入运单';
			} elseif ($order['shipping_id'] != 51) {
				$error_out[$j] = 'EXCEL 第' . $i . ' 行' . ', 淘宝订单号：' . $rec['taobao_order_sn'] . ' 对应系统快递方式为“'.$order['shipping_name'].'”不允许通过该页面导入运单';
			} elseif ($is_exists != 0) {
				$error_out[$j] = 'EXCEL 第' . $i . ' 行' . ', 淘宝订单号：' . $rec['taobao_order_sn'] . ' 要维护的运单号在系统中已存在';
			} else {
				$update_sql = "UPDATE romeo.shipment set tracking_number = '{$tracking_number}' where shipment_id = '{$order['shipment_id']}' ";
				if (!$db->query($update_sql)) {
					$error_out[$j] = 'EXCEL 第' . $i . ' 行' . ', 淘宝订单号：' . $rec['taobao_order_sn'] . ' 更新出错 ';
				}
			}
			$flag = false;
			$j++;
			$i++;
		}
	} while (false);
	$smarty->assign('final_out', $final_out);
	$smarty->assign('error_out', $error_out);
}
$smarty->display('wxgrd_tracking_number.htm');
?>
