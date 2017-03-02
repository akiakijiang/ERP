<?php
/**
 * 快递交接单导出
 */
die("快递交接单导出 使用报表");
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once (ROOT_PATH . 'admin/function.php');
admin_priv ( 'export_bill_no' );

$start = isset ( $_REQUEST ['start'] ) && ! empty ( $_REQUEST ['start'] ) && strtotime ( $_REQUEST ['start'] ) !== false ? $_REQUEST ['start'] : date ( "Y-m-d" );
$end = isset ( $_REQUEST ['end'] ) && ! empty ( $_REQUEST ['end'] ) && strtotime ( $_REQUEST ['end'] ) !== false ? $_REQUEST ['end'] : date ( "Y-m-d" );
$start_time = strtotime ( $start );
$end_time = strtotime ( $end );

$shipping_list = getShippingTypes ();

if ($_REQUEST ['act'] == 'export') {
	if ($start_time == $end_time) {
		sys_msg ( "发货起始时间与终止时间相同，请重新选择时间范围。" );
	} elseif ($start_time > $end_time) {
		sys_msg ( "发货起始时间大于终止时间，请重新选择时间范围。" );
	}
	$facility_id = trim ( $_REQUEST ['facility'] );
	$shipping_id = trim ( $_REQUEST ['shipping'] );
	foreach ( $shipping_list as $item ) {
		$list [$item ['shipping_id']] = $item ['shipping_name'];
	}

	$sql = "SELECT 
		o.order_id,o.order_sn,o.taobao_order_sn,o.shipping_time, o.shipping_id, o.facility_id,o.consignee, o.mobile,o.order_amount,o.address,o.party_id,o.city,o.province,o.district,
		s.tracking_number, p.name,s.shipping_leqee_weight,r1.region_name AS pro, r2.region_name AS cit, r3.region_name AS dis
	FROM ecshop.ecs_order_info o force index(shipping_time)
	INNER JOIN romeo.order_shipment os ON os.order_id = convert(o.order_id using utf8)
	INNER JOIN romeo.shipment s ON s.shipment_id = os.shipment_id
	INNER JOIN romeo.party p ON p.party_id = convert(o.party_id using utf8)
	LEFT JOIN ecshop.ecs_region r1 ON r1.region_id = o.province
	LEFT JOIN ecshop.ecs_region r2 ON r2.region_id = o.city
	LEFT JOIN ecshop.ecs_region r3 ON r3.region_id = o.district
	WHERE s.status = 'SHIPMENT_SHIPPED' AND o.shipping_time >= '{$start_time}' AND o.shipping_time <= '{$end_time}'
	AND o.facility_id ='{$facility_id}' AND o.shipping_id ={$shipping_id} " ;
//	Qlog::log('export_bill_no_sql='.$sql);
	$result = $db->getAll ( $sql );
	foreach ( $result as $item ) {
		// 判断运单号是否为空
		if (empty ( $item ['tracking_number'] )) {
			$key = $item ['order_sn'];
		} else {
			$key = $item ['tracking_number'];
		}
		$order_list [$item ['shipping_id']] [$key] = $item;
	}
	$size = count ( $order_list );
	export_excel_bill_no ( $size, $list, $start, $end, $order_list );
	exit ();
}

$smarty->assign ( 'start', $start );
$smarty->assign ( 'end', $end );
$smarty->assign ( 'facility', get_user_facility () );
$smarty->assign ( 'shipping', $shipping_list );
$smarty->display ( "oukooext/export_bill_no.htm" );

/**
 *
 *
 *
 * 导出快递交接单
 *
 * @param $size int
 *       	 选择快递方式的个数
 * @param $shipping_list array
 *       	 快递方式列表
 * @param $start date
 *       	 起始时间
 * @param $end date
 *       	 终止时间
 * @param $order_lists array
 *       	 订单列表
 */
function export_excel_bill_no($size, $shipping_list, $start, $end, $order_lists) {
	set_include_path ( get_include_path () . PATH_SEPARATOR . './includes/Classes/' );
	require 'PHPExcel.php';
	require 'PHPExcel/IOFactory.php';
	$excel = new PHPExcel ();
	$i = 1;
	$ems = array() ;
	
	if (! empty ( $order_lists )) {
		foreach ( $order_lists as $key => $order_list ) {
            export_sheet ( $excel, $i, $shipping_list [$key], $start, $end, $order_list );
            $i ++;
       }
    } else {
		export_sheet ( $excel, $i = 1, '快递交接单', $start, $end, $order_list );
	}
	if (! headers_sent ()) {
		header ( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
		header ( 'Content-Disposition: attachment; filename="快递交接单.xlsx"' );
		header ( 'Cache-Control: max-age=0' );
		$output = PHPExcel_IOFactory::createWriter ( $excel, 'Excel2007' );
		$output->setOffice2003Compatibility ( true );
		$output->save ( 'php://output' );
	}
}
/**
 * 一个快递信息一个sheet
 */
function export_sheet($excel, $i , $name, $start, $end, $order_list) {
	if ($i == 1) {
		$sheet = $excel->getActiveSheet ();
	} else {
		$sheet = '$sheet' . $i;
		$sheet = $excel->createSheet ();
	}
	$sheet->setTitle ( $name );
	$sheet->mergeCells ( 'A1:K2' );
	$sheet->getStyle ( 'A1' )->getFont ()->setSize ( 18 );
	if ($start == $end) {
		$sheet->setCellValue ( "A1", $name . $start . "发货数据" );
	} else {
		$sheet->setCellValue ( "A1", $name . $start . " ~ " . $end . "发货数据" );
	}
    $sheet->setCellValue ( 'A3', "淘宝订单号" );
	$sheet->setCellValue ( 'B3', "ERP订单号" );
	$sheet->setCellValue ( 'C3', "运单号" );	
	$sheet->setCellValue ( 'D3', "快递公司" );
	$sheet->setCellValue ( 'E3', "重量(kg)" );
	$sheet->setCellValue ( 'F3', "组织" );  
	$sheet->setCellValue ( 'G3', "收件人姓名" );
	$sheet->setCellValue ( 'H3', "联系方式" );
	$sheet->setCellValue ( 'I3', "收件人地址" );
    $sheet->setCellValue ( 'J3', " 应收货款" );
    $sheet->setCellValue ( 'K3', "发货时间" );
	$j = 4;
	if (! empty ( $order_list )) {
		foreach ( $order_list as $order ) {
			$leqee_weight = null;
			if ($order ['shipping_leqee_weight']) {
				$leqee_weight = $order ['shipping_leqee_weight'] / 1000;
			}
			$sheet->setCellValueExplicit ( "A{$j}", $order ['taobao_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING );
			$sheet->setCellValueExplicit ( "B{$j}", $order ['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING );
			$sheet->setCellValueExplicit ( "C{$j}", $order ['tracking_number'], PHPExcel_Cell_DataType::TYPE_STRING );
			$sheet->setCellValue ("D{$j}", $name, PHPExcel_Cell_DataType::TYPE_STRING );
			$sheet->setCellValue ( "E{$j}", $leqee_weight, PHPExcel_Cell_DataType::TYPE_STRING );
			$sheet->setCellValueExplicit ( "F{$j}", $order ['name'], PHPExcel_Cell_DataType::TYPE_STRING );
			$sheet->setCellValueExplicit ( "G{$j}", $order ['consignee'], PHPExcel_Cell_DataType::TYPE_STRING );
			$sheet->setCellValueExplicit ( "H{$j}", $order ['mobile'], PHPExcel_Cell_DataType::TYPE_STRING );
			$sheet->setCellValueExplicit ( "I{$j}", $order ['pro'] . $order ['cit'].$order ['dis'].$order ['address'], PHPExcel_Cell_DataType::TYPE_STRING );
			if( $order['shipping_id'] == 11 || $order['shipping_id'] == 36){
				$sheet->setCellValue ( "J{$j}", $order ['order_amount'], PHPExcel_Cell_DataType::TYPE_STRING );
			}else{
				$sheet->setCellValue ( "J{$j}", "0.00",PHPExcel_Cell_DataType::TYPE_STRING);
			}
			$sheet->setCellValueExplicit ( "K{$j}", date ( "Y-m-d H:i:s", $order ['shipping_time'] ), PHPExcel_Cell_DataType::TYPE_STRING );
			$j ++;
		}
	}
}
