<?php

/**
 * 未开具采购发票商品报表
 */
 
define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');

// 请求
$start = isset($_REQUEST['start']) && strtotime($_REQUEST['start']) ? $_REQUEST['start'] : date("Y-m-d");
$end   = isset($_REQUEST['end']) && strtotime($_REQUEST['end']) ? $_REQUEST['end'] : date('Y-m-d');
$provider_id = isset($_REQUEST['provider_id']) && is_numeric($_REQUEST['provider_id']) ? $_REQUEST['provider_id'] : NULL ;

// 通过服务取得商品列表
try 
{
	$_soapclient = soap_get_client('purchaseInvoiceService', 'REPORT');
	$lock_number = $_soapclient->start()->return;  // 创建锁
	
	$result = $_soapclient->getProviderUninvoicedSheet(array(
		'arg0' => $lock_number,
		'arg1' => $provider_id,
		'arg2' => $start,
		'arg3' => $end,
	));
	if($result && isset($result->return->ProviderUninvoicedElement))
	{
        $goods_list = wrap_object_to_array($result->return->ProviderUninvoicedElement);  // 商品列表
	}
	else
	{
		$info = '没有数据';
	}

	$_soapclient->end(array('arg0' => $lock_number));  // 解锁
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

if ($goods_list)
{
    $total_cost = 0;  // 总金额
    $oSns = array();  // 订单号
        
    foreach ($goods_list as $key => $item)
    {
        $goods_info = getGoodsIdStyleIdByProductId($item->productId);  // 取得商品代码
        $goods_list[$key]->goodsCode = encode_goods_id($goods_info['goods_id'], $goods_info['style_id']);
        if (!empty($item->orderSn))
        {
            $orders = array_filter(array_map('trim', explode(',', $item->orderSn)), 'strlen');
            $goods_list[$key]->formatedOrderSn = $orders;  // 格式化后的order_sn
            if (!empty($orders)) { $oSns = array_merge($oSns, $orders); }  
        }

        $total_cost += (float)$item->totalCost;
    }
    $total_cost = sprintf("%01.2f", $total_cost);
    
    // 构造订单对应的url
    if (!empty($oSns))
    {
        $sql = "SELECT order_id, order_sn FROM {$ecs->table('order_info')} WHERE ". db_create_in($oSns, 'order_sn');
        $db->getAllRefby($sql, array('order_sn'), $ref_fields, $ref);
        foreach ($goods_list as $key => $item)
        {
            if (!empty($item->formatedOrderSn))
            {
                $url = array();
                foreach ($item->formatedOrderSn as $order_sn)
                {
                    $order_id = $ref['order_sn'][$order_sn][0]['order_id'];
                    $url[] = "<a href=\"../detail_info.php?old=1&order_id={$order_id}\" target=\"_blank\">{$order_sn}</a>";        
                }
                $goods_list[$key]->orderSnUrl = implode('<br />', $url);
            }
        }
    }
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
	$filename = "供应商未开票商品列表({$start}-{$end}).xlsx";
	$title = "供应商未开票商品列表";
	
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
	$sheet->setCellValue("A{$i}", '入库时间');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	
	$sheet->setCellValue("B{$i}", '商品名称');
	$sheet->setCellValue("C{$i}", '待开票清单号');
	$sheet->setCellValue("D{$i}", '入库单号');
	$sheet->setCellValue("E{$i}", '串号');
	$sheet->setCellValue("F{$i}", '数量');
	$sheet->setCellValue("G{$i}", '单价');
	$sheet->setCellValue("H{$i}", '金额');
	$i++;
	
	// 商品列表
	if (is_array($goods_list))
	{
		foreach ($goods_list as $item)
		{
			$sheet->setCellValue("A{$i}", $item->inTime);
			$sheet->setCellValue("B{$i}", $item->goodsName);
			$sheet->setCellValue("C{$i}", $item->purchaseInvoiceRequestId);
			$sheet->setCellValue("D{$i}", $item->orderSn);
			$sheet->setCellValue("E{$i}", $item->serialNumber);
			$sheet->setCellValue("F{$i}", $item->amount);
			$sheet->setCellValue("G{$i}", $item->unitCost);
			$sheet->setCellValue("H{$i}", $item->totalCost);
			$sheet->getStyle("H{$i}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
			$i++;
		}
		
		// 总金额
		$sheet->setCellValue("G{$i}", '合计金额');
		$sheet->setCellValue("H{$i}", $total_cost);
		$sheet->getStyle("H{$i}")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_00);
		$i++;	
	}	
	
	// 输出
	if (!headers_sent())
	{
		$output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$output->setOffice2003Compatibility(true);
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'. iconv("utf-8", "gbk", $filename) .'"');
		header('Cache-Control: max-age=0');
		$output->save('php://output');
	}
}
else
{
	$smarty->assign('info',  $info);
	$smarty->assign('start', $start);
	$smarty->assign('end',   $end);
	
	$smarty->assign('list',  $goods_list);   // 商品列表
	$smarty->assign('total', $total_cost);   // 合计金额
	$smarty->assign('provider', $provider);  // 供应商信息
	$smarty->display('oukooext/purchase_invoice/purchase_uninvoiced_product.htm');		
}

?>