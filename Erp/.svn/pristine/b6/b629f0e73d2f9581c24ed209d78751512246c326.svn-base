<?php
/**
 * 花王数据处理
 * stsun 2015-12-16
 */
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
	
function logRecord ($m) {
//	var_dump($m); return;
	if(is_array($m)) {
		print date("Y-m-d H:i:s"). "\r\n";
	} else {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
	}
}

function kaoActualDoAction($deal_type) {
	logRecord("kaoActualDoAction begin");
	$indicate_types = array('DEALDATA', 'SENDEMAIL');
	$_SESSION['admin_name'] = isset($_SESSION['admin_name'])?$_SESSION['admin_name']:'system';
	
	if(!in_array($deal_type,$indicate_types)) {
		logRecord('deal_type is error:'.$deal_type);
		return false;
	}
	global $db;

	$start = microtime(true);
	$countAll = 0;
	$countIndicateFinish = 0;
	$countActualFinish = 0;
	$countActualError = 0;
	$countActualDetailFinish = 0;
	$countActualDetailError = 0;
	
	ini_set('default_socket_timeout', 2400);
	
	// 加锁
	$lock_name = $deal_type;

    $lock_file_name = get_file_lock_path($lock_name, 'kaoActualDoAction');
    $lock_file_point = fopen($lock_file_name, "w+");
    $would_block = false;
    if(!flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
    	fclose($lock_file_point);
    	logRecord("上次操作还在进行，请稍后 deal_type:".$lock_name);
    	return false;
    }
	    
	try {
	
		// 处理数据
		if('DEALDATA' == $deal_type) {
			$orders = get_actual_todo_orders();
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
						
						if(empty($val) && !in_array($key,array('goods_num','service_fee','service_fee_category','created_stamp','last_updated_stamp','action_user','fee','erp_order_sn'))) {
							$message .= " 结汇数据不完整，无法处理！  淘宝订单号： {$taobao_order_sn}! 空值字段：{$key}!  " ."\r\n";	
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
			
					//   1/rate取5位小数
					$order_result['rate'] = round(1 / $order['rate'], 5);
					
					//should_pay_postage * rate 4舍5入取整
					$order_result['shipping_fee'] = round($order['should_pay_postage'] * $order_result['rate'], 0);  //RMB--日元
					
					//商品合计金额（日元） ： 商品代合計金額（日本円） = order_amount- shipping_fee
					$order_result['goods_amount'] = $order['jps_amount'] - $order_result['shipping_fee'];
					
					//日元结算金额
	 				$order_result['jps_settlement_amount'] = $order['settlement_amount'];
	 	
					//RMB结算金额
	 				$order_result['rmb_settlement_amount'] = $order['rmb_settlement_amount'];
					
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
			if($is_send_email) {
				send_indicate_mail("【KaoDataSendCommand】【ERROR】",$message);
			}
		}
		
		else if('SENDEMAIL' == $deal_type) {			
			die;
			
			$settlement_date = date("Ymd",strtotime("-0 days",time()));
			$sql = "select * from ecshop.brand_kao_order_result_info where is_send = 'N' and settlement_date>='{$settlement_date}' ";
			$result = $db->getAll($sql);
			
			/*
				kao_create_and_send_excel($result);
			*/
		}
		
	} catch(Exception $e) {
		$exception_mssage = "$inventory_type exception:".$e->getMessage();
		send_indicate_mail("【KaoDataSendCommand】【EXCEPTION】",$exception_mssage);
		logRecord($exception_mssage);
	}
	
	flock($lock_file_point, LOCK_UN);
    fclose($lock_file_point);
	    
	logRecord("处理个数: {$countAll},成功个数: {$countSuccess}" . 
	          "耗时: " . (microtime(true)-$start));
	logRecord("kaoActualDoAction end");
	
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


/*
function kao_create_and_send_excel($results) {
	
	// 载入excel库
	set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
	require 'PHPExcel.php';
	require 'PHPExcel/IOFactory.php';
	
	$filename = "花王结汇数据.xlsx";
	$excel = new PHPExcel();
	var_dump("sss");die;
	
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
	$rate = 0;
	foreach ($results as $res)
	{
		$settlement_amount_sum_rmb += $res['rmb_settlement_amount'];
		$settlement_amount_sum_jps += $res['jps_settlement_amount'];
		$rate += $res['rate'];
	}
	$rate = round($rate / count($results), 5);
	
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

	// 发送到邮件
	if (!headers_sent())
	{
		$output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$output->setOffice2003Compatibility(true);
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
 		$output->save('php://output');
	}

}

*/

function send_indicate_mail($subject, $body = null, $path = null, $file_name = null) {

	require_once(ROOT_PATH . 'includes/helper/mail.php');

	$mail=Helper_Mail::smtp();
	$mail->IsSMTP();                 // 启用SMTP
	$mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
	$mail->SMTPAuth = true;         //启用smtp认证
	$mail->Username = 'stsun@leqee.com';   // 你的邮箱地址
	$mail->Password = '283963625*s';      //你的邮箱密码  */
	$mail->CharSet='UTF-8';
	$mail->Subject="【花王数据处理错误】" . $subject;
	$mail->SetFrom('stsun@leqee.com', 'GOD');
	$mail->AddAddress("stsun@leqee.com", "孙书通");
	$mail->AddAddress("xyliang@leqee.com", "梁心怡");
	$mail->AddAddress("ljni@leqee.com", "Sinri");
	$mail->Body = date("Y-m-d H:i:s") . " " . $body;

	if($path != null && $file_name != null){
		$mail->AddAttachment($path, $file_name);
	}
	try {
		if ($mail->Send()) {
			logRecord('mail send success');
		} else {
			logRecord('mail send fail');
		}
	} catch(Exception $e) {
		logRecord('mail send exception ' . $e->getMessage());
	}
}

 ?>
 