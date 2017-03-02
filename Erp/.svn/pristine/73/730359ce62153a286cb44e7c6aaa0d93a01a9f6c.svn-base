<?php
define('IN_ECS', true);
require_once('includes/init.php');

admin_priv('finance_order');
require_once("function.php");

$act = $_POST['act'];
$shipping_id = $_POST['shipping_id'];
$type = $_POST['type']; 
$bill_time = $_POST['bill_time'];
$times = $_POST['times'];
$bill_type = $_POST['bill_type']; // 0=>所有;1=>代收货款;2=>邮费

/* 取得快递公司结算的数据库 */
$finance_sf_db = new cls_mysql($finance_sf_db_host, 
							   $finance_sf_db_user, 
							   $finance_sf_db_pass, 
							   $finance_sf_db_name);

$error = $_FILES['file']['error'];
$insert_count = 0;
switch ($error) {
	case 0:
		break;
	case 1:		//上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值
	case 2:		//上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值
		$info[] = '上传的文件过大';
		break;
	case 3:
		$info[] = '文件只有部分被上传';
		break;
	case 4:
		$info[] = '没有文件被上传';
		break;
	case 6:
		$info[] = '找不到临时文件夹';
		break;
	case 7:
		$info[] = '文件写入失败';
		break;
}

//是否成功导入的标识
$success_import = false;
$success_num = 0;
$summary_order_amount = 0;
$summary_proxy_amount = 0;
$summary_shipping_fee = 0;

if (!$error) {
	switch ($act) {
		case 'batch_add':
			if (is_uploaded_file($_FILES['file']['tmp_name'])) {
				$fsock = fopen($_FILES['file']['tmp_name'], 'r');
				
				// 处理第一行 标题行
				$head = fgetcsv_reg($fsock);
				if ($bill_type == 0){
					if (iconv('gbk', 'utf-8', $head[0]) != "运单号" || 
					iconv('gbk', 'utf-8', $head[1]) != "实收金额" ||
					iconv('gbk', 'utf-8', $head[2]) != "手续费" ||
					iconv('gbk', 'utf-8', $head[3]) != "快递费") {
						$info[] = "格式不对，应该是'运单号'|'实收金额'|'手续费'|'快递费'";
						break;
					}
				} else if ($bill_type == 1) {
					if (iconv('gbk', 'utf-8', $head[0]) != "运单号" || 
					iconv('gbk', 'utf-8', $head[1]) != "实收金额" ||
					iconv('gbk', 'utf-8', $head[2]) != "手续费") {
						$info[] = "格式不对，应该是'运单号'|'实收金额'|'手续费'";
						break;
					}
				} else if ($bill_type == 2) {
					if (iconv('gbk', 'utf-8', $head[0]) != "运单号" || 
					iconv('gbk', 'utf-8', $head[1]) != "快递费") {
						$info[] = "格式不对，应该是'运单号'|'快递费'";
						break;
					}
				} else {
					$info[] = "没有选择将要导入CSV文件的格式";
					break;
				}
				
				// 处理数据
				while ($row = fgetcsv_reg($fsock)) {
				    // 0=>所有;1=>代收货款;2=>邮费 0 比1多出shipping_fee 1 比 2多出订单实收金额				    
					if ($bill_type == 0){
						$sql = "insert into ouku_order_amount
								(bill_no, order_amount, proxy_amount, shipping_fee, 
								bill_time, times, shipping_id)
								values('$row[0]', $row[1], $row[2], $row[3], 
								$bill_time, $times, $shipping_id)";
						$summary_order_amount += $row[1];
						$summary_proxy_amount += $row[2];
                        $summary_shipping_fee += $row[3];
					} 
					if ($bill_type == 1) {
						$sql = "insert into order_amount(bill_no, order_amount, proxy_amount, bill_time, times, shipping_id)
								values('$row[0]', $row[1], $row[2], $bill_time, $times, $shipping_id)";
						$summary_order_amount += $row[1];
						$summary_proxy_amount += $row[2];
					}
					
					if ($bill_type == 2) {
						$sql = "insert into real_shipping_fee(bill_no, fee, bill_time, times, shipping_id)
								values('$row[0]', $row[1], $bill_time, $times, $shipping_id)";						
                        $summary_shipping_fee += $row[1];
					}
					$result = $finance_sf_db->query($sql, 'SILENT');
					if (!$result) {
						$info[] = $finance_sf_db->error();
					} else {
					    $success_import = true;
					    $success_num ++;
					}
				}
				if ($bill_type == 0){
				    // 所有的费用的情况 status = 1
					$sql = "insert into job_schedule(shipping_id, type, bill_time, times, status)
						values($shipping_id, $type, $bill_time, $times, 1)";
					$result = $finance_sf_db->query($sql, 'SILENT');
					if (!$result) {
						$info[] = $finance_sf_db->error();
					}
				}
				if ($bill_type == 1 || $bill_type == 2) {
					$sql = "select type, status from job_schedule where shipping_id = $shipping_id 
							and bill_time = $bill_time and times = $times";
					$type_status = $finance_sf_db->getRow($sql);
					if ($type_status == null) {//如果数据库里面原本没有数据
					    // status 对应的值 准备齐了=>1；少邮费=>2；少代货款=>3
						if ($bill_type == 1){
							$sql = "insert into job_schedule(shipping_id, type, bill_time, times, status)
									values($shipping_id, $type, $bill_time, $times, 2)";
						} else if($bill_type == 2){
							$sql = "insert into job_schedule(shipping_id, type, bill_time, times, status)
									values($shipping_id, $type, $bill_time, $times, 3)";
						} else {
							$info[] = "条件选得不对";
							break;
						}
						$result = $finance_sf_db->query($sql, 'SILENT');
						if (!$result) {
							$info[] = $finance_sf_db->error();
						}
					} else if ($type_status["status"] == 1 || $type_status["type"] == $type) {//数据本来就有了
						break;
					} else if (($type_status["status"] == 2 && $bill_type == 2) ||
							 ($type_status["status"] == 3 && $bill_type == 1)){
						$sql = "update job_schedule set status = 1 
								where shipping_id = $shipping_id 
								  and bill_time = $bill_time
								  and times = $times";
					} else {
						$info[] = "条件选得不对";
						break;
					}
				}
			}
			break;
		default:
			$info[] = '非法操作';
	}
}

$smarty->assign('summary_order_amount', $summary_order_amount);
$smarty->assign('summary_proxy_amount', $summary_proxy_amount);
$smarty->assign('summary_shipping_fee', $summary_shipping_fee);
$smarty->assign('success_num', $success_num);
$smarty->assign('info', $info);
$smarty->display('oukooext/finance_dshk_import.htm');

?>