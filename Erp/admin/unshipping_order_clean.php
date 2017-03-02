<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once (ROOT_PATH . 'admin/function.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('unshipping_order_clean');

$facility_list = array(
	0 => '不选',
	1 => '上海仓',
	2 => '东莞仓',
	3 => '北京仓',
	4 => '外包仓',
	5 => '品牌商直货仓',
	6 => '嘉善仓',
	7 => '其他仓',
	8 => '苏州仓',
	107742558 => 'Blackmores虚拟上海仓'	
);
$smarty->assign('facility_list', $facility_list);

$shipping_status_list = array(
	1 => '已预定未发货',
	0 => '待配货',
	13 => '批拣中',
	12 => '已出库待复核',
	8 => '已复核待称重',
	5 => '已称重待发货'
);

//配送方式
//$carrier_list = Helper_Array::toHashmap(getCarriers(), 'carrier_id', 'name');

$condition = get_condition();	
$sql = "
SELECT p.name pName, o.order_sn, 
	CONCAT_WS(',',case o.order_status when 0 then '未确认' 
								      when 1 then '已确认' 
								      when 2 then '取消' 
									  when 4 then '拒收' 
									  when 8 then '超卖' 
									  when 11 then '外包发货' end, 
					case o.pay_status when 0 then '未付款' 
									  when 1 then '付款中' 
									  when 2 then '已付款' 
									  when 3 then '待退款' 
									  when 4 then '已退款' end, 
					case o.shipping_status when 0 then '待配货' 
										   when 1 then '已发货' 
										   when 2 then '收货确认' 
										   when 3 then '拒收退回' 
										   when 4 then '已发往自提点' 
										   when 5 then '等待用户自提' 
										   when 6 then '已自提' 
										   when 7 then '自提取消' 
										   when 8 then '已出库/复核，待发货' 
										   when 9 then '已配货，待出库' 
										   when 10 then '已配货，但商品改变' 
										   when 11 then '已追回' 
										   when 12 then '已拣货出库,待复核' 
										   when 13 then '批拣中' end) as status, 
	o.order_time, r.RESERVED_TIME, o.consignee, s.tracking_number, f.facility_name
FROM ecshop.ecs_order_info o use index(order_time,party_id,order_status)
	inner join romeo.order_inv_reserved r on o.order_id = r.order_id
	LEFT JOIN romeo.party p ON convert(o.party_id using utf8) = p.PARTY_ID
	LEFT JOIN romeo.facility f ON o.facility_id = f.FACILITY_ID
	LEFT JOIN romeo.order_shipment os ON convert(o.order_id using utf8) = os.order_id
	LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
WHERE o.order_time >= '2016-01-01' AND ". party_sql('o.party_id') ." AND ". facility_sql('o.facility_id') ." AND
	o.order_status = 1 AND 
	o.order_type_id IN ('SALE', 'RMA_RETURN', 'RMA_EXCHANGE', 'SHIP_ONLY') AND
	r.status <> 'N' AND 
	r.status is not null AND
	r.reserved_time < DATE_FORMAT(concat(DATE_FORMAT(now(), '%Y-%m-%d'), ' 16:30:00'), '%Y-%m-%d %H:%i:%s')
	{$condition}
ORDER BY o.party_id, o.facility_id, o.order_time
";
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):'';
if($act == 'search'){
	Qlog::log($sql);
	$orders = $slave_db->getAll($sql);
	$count = count($orders);
	$smarty->assign('orders' ,$orders);
	$smarty->assign('count', $count);
}else if($act == 'export'){
	set_include_path ( get_include_path () . PATH_SEPARATOR . './includes/Classes/' );
	require 'PHPExcel.php';
	require 'PHPExcel/IOFactory.php';
    
    $orders = $slave_db->getAll($sql);
   
	$title = array(0 => array(
        '业务组', '订单号', '订单状态', '下单时间', 
        '预定时间', '顾客名字', '运单号','仓库名'
    ));
    $type = array(0 => 'string',);
    
	$filename = "已预订未发货订单.xlsx";
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $orders = array_map('array_values', $orders);
    if (!empty($orders)) {
        $sheet->fromArray($orders, null, 1, $type);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    exit();
}

$smarty->assign('shipping_status', $_REQUEST['shipping_status']);
$smarty->assign('facility', $_REQUEST['facility']);
$smarty->assign('shipping_status_list', $shipping_status_list);
//$smarty->assign('carrier_list', $carrier_list);
$smarty->display('oukooext/unshipping_order_clean.htm');

function get_condition(){
	$condition = "";
	$shipping_status = $_REQUEST['shipping_status'];
	$facility = $_REQUEST['facility'];
	
	if($shipping_status == 5){ //已称重未发货
		$condition .= " AND o.shipping_status not in (1, 2, 3, 11, 8) AND s.shipping_leqee_weight is not null";
	}else if($shipping_status == 1){ //已预订未发货
		$condition .= " AND o.shipping_status not in (1, 2, 3, 11)";
//		$condition .= " AND not exists (select 1 from ecshop.ecs_order_action oa where o.order_id = oa.order_id AND oa.shipping_status in (1, 2, 3, 11) limit 1)";
	}else {
		$condition .= " AND o.shipping_status = {$shipping_status}";
	}
	
	if($facility == 1){
		$condition .= " AND o.facility_id in ('19568549','22143846','22143847','24196974','81569822','81569823','83972713','83972714')";
	}else if($facility == 2){
		$condition .= " AND o.facility_id in ('19568548','49858449','76065524','3580047')";
	}else if($facility == 3){
		$condition .= " AND o.facility_id in ('79256821')";
	}else if($facility == 4){
		$condition .= " AND o.facility_id in ('76161272','92718101','100170592')";
	}else if($facility == 5){
		$condition .= " AND o.facility_id in ('100170590','100170591','108341690')";
	}else if($facility == 6){//电商服务嘉善仓
		$condition .= " AND o.facility_id in ('194788297')";
	}else if($facility == 7){//其他仓
		$condition .= " AND o.facility_id not in ('19568549','22143846','22143847','24196974','81569822','81569823','83972713',
'83972714','19568548','49858449','76065524','3580047','79256821','76161272','92718101','100170592','100170590','100170591',
'108341690','194788297','185963138')";
	}else if($facility==8){ //苏州仓
		$condition .= " AND o.facility_id in ('185963138') ";
	}else if($facility != 0){
		$condition .= " AND o.facility_id in ({$facility})";
	}else {
		$condition .= " AND o.facility_id in ('19568549','22143846','22143847','24196974','81569822','81569823','83972713','83972714',
'19568548','49858449','76065524','3580047','79256821','76161272','92718101','100170592','100170590','100170591','108341690','107742558')";
	}
	return $condition;
}

?>
