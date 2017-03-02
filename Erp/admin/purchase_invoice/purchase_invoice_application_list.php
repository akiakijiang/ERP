<?php

/**
 * 供应商发票使用一览
 */
 
define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

// 请求
$start = isset($_REQUEST['start']) && strtotime($_REQUEST['start']) ? $_REQUEST['start'] : date("Y-m-d");
$end   = isset($_REQUEST['end']) && strtotime($_REQUEST['end']) ? $_REQUEST['end'] : date('Y-m-d');
$provider_id = isset($_REQUEST['provider_id']) && is_numeric($_REQUEST['provider_id']) ? $_REQUEST['provider_id'] : NULL ;

// 通过服务取得商品列表
try 
{
	$_soapclient = soap_get_client('purchaseInvoiceService', 'REPORT');
	$lock_number = $_soapclient->start()->return;
	$result = $_soapclient->getProviderInvoiceSheet(array(
		'arg0' => $lock_number,
		'arg1' => $provider_id,
		'arg2' => $start,
		'arg3' => $end,
	));
	$result->return->ProviderInvoiceElement = wrap_object_to_array($result->return->ProviderInvoiceElement);
	if($result && is_array($result->return->ProviderInvoiceElement))
	{
		$total_cost = 0;
		$goods_list = $result->return->ProviderInvoiceElement;
		foreach ($goods_list as $key => $item)
		{
			// 取得总价格
			$total_cost += (float)$item->totalCost;
		}
		$total_cost = sprintf("%01.2f", $total_cost);
	}
	else
	{
		$info = '没有数据';
	}
	$_soapclient->end(array('arg0' => $lock_number));
}
catch (SoapFault $ex)
{
	// 出错则尝试解锁
	if ($_soapclient && $lock_number)
	{
		$_soapclient->end($lock_number);	
	}
	
	$info = $ex->faultstring;
}

// 取得供应商
if (!empty($provider_id))
{
	$sql = "
		SELECT `provider_name`,`provider_code`,`provider_order_type` FROM {$ecs->table('provider')} WHERE `provider_id` = '{$provider_id}'
	";
	$provider = $db->getRow($sql, true);
}

// 生成报表
if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'export' && !empty($goods_list))
{
	$filename = "供应商发票使用一览({$start}-{$end}).xlsx";
	$title = "供应商发票使用一览";
	
	set_include_path(get_include_path() . PATH_SEPARATOR . ROOT_PATH.'admin/includes/Classes/');
	require 'PHPExcel.php';
	require 'PHPExcel/IOFactory.php';
	
	$excel = new PHPExcel();
	$excel->getProperties()->setTitle($title);
	$excel->getProperties()->setSubject($title);
	$sheet = $excel->getActiveSheet();
	$sheet->getColumnDimension('A')->setWidth(20);
	$sheet->getColumnDimension('B')->setWidth(60);
	$sheet->getColumnDimension('C')->setWidth(20);
	$sheet->getColumnDimension('D')->setWidth(20);
	$sheet->getColumnDimension('E')->setWidth(15);
	$sheet->getColumnDimension('F')->setWidth(10);
	$sheet->getColumnDimension('G')->setWidth(10);
	$sheet->getColumnDimension('H')->setWidth(10);
	
	// 报表标题
	$i = 1;
	$sheet->mergeCells("A{$i}:H{$i}");
	$sheet->setCellValue("A{$i}", $title);
	$sheet->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle("A1")->getFont()->setBold(true);
	
	// 供应商信息
	$i = 2;
	$sheet->mergeCells("A{$i}:H{$i}");
	$sheet->setCellValue("A{$i}", "供应商代码:{$provider['provider_code']}, 供应商名称:{$provider['provider_name']}, 开始日期:{$start}, 结束日期: {$end}");
	
	// 表头项
	$i = 3;
	$sheet->setCellValue("A{$i}", '发票号');
	$sheet->setCellValue("B{$i}", '发票时间');
	$sheet->setCellValue("C{$i}", '商品名');
	$sheet->setCellValue("D{$i}", '数量');
	$sheet->setCellValue("E{$i}", '单价');
	$sheet->setCellValue("F{$i}", '金额');
	$sheet->setCellValue("G{$i}", '已使用数量');
	$sheet->setCellValue("H{$i}", '未使用数量');
	$i++;
	
	// 商品列表
	if (is_array($goods_list))
	{
		foreach ($goods_list as $item)
		{
			$sheet->getStyle("D{$i}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
			$sheet->getStyle("E{$i}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
			
			$sheet->setCellValue("A{$i}", $item->invoiceNo);
			$sheet->setCellValue("B{$i}", $item->invoiceDate);
			$sheet->setCellValue("C{$i}", $item->goodsName);
			$sheet->setCellValue("D{$i}", $item->amount);
			$sheet->setCellValue("E{$i}", $item->unitCost);
			$sheet->setCellValue("F{$i}", $item->totalCost);
			$sheet->setCellValue("G{$i}", $item->matchedQuantity);
			$sheet->setCellValue("H{$i}", $item->unmatchedQuantity);
			$i++;
		}
		
		// 总金额
		$sheet->getStyle("G{$i}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
		$sheet->setCellValue("G{$i}", '合计金额');
		$sheet->setCellValue("H{$i}", $total_cost);
		$i++;	
	}	
	
	// 输出
	if (!headers_sent())
	{
		$output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$output->setOffice2003Compatibility(true);
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'.iconv('utf-8', 'gbk', $filename).'"');
		header('Cache-Control: max-age=0');
		$output->save('php://output');
	}
}
else
{
	$smarty->assign('info',  $info);
	$smarty->assign('start', $start);
	$smarty->assign('end',   $end);
	
	$smarty->assign('total', $total_cost);
	$smarty->assign('list',  $goods_list);   // 商品列表
	$smarty->assign('total', $total_cost);   // 合计金额
	$smarty->assign('provider', $provider);  // 供应商信息
	$smarty->display('oukooext/purchase_invoice/purchase_invoice_application_list.htm');		
}

?>