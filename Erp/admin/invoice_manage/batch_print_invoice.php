<?php
define ( 'IN_ECS', true );
require_once ('../includes/init.php');
require_once ('../includes/lib_invoice.php');
require_once ('../distribution.inc.php');
require_once('../function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
admin_priv ( 'print_invoice' );

if ($_REQUEST ['action'] == 'exportOrders') {	
   
   $startCalendar   = trim($_POST ['startCalendar']); 
   $endCalendar   = trim($_POST ['endCalendar']);  
 
   $time['start']=$startCalendar;
   $time['end']=$endCalendar;
   $list = getInvoiceOrderIist($time);
//var_dump($list);
   	$smarty->assign('invoiceOrderList',$list);
    header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "财务对账补寄发票订单列表" ) . ".csv" );
	$out = $smarty->fetch ( 'invoice_manage/invoiceOrderList.dwt' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}

function update_invoice_tracking_number($SID, $TNS) {

	$shipment_id   = $SID;
	$tracking_num  = $TNS;
	
	$party_id         = $_SESSION ['party_id'];
	$admin_name       = $_SESSION['admin_name'];
	$shipmentInfo     = getShipmentInfo($shipment_id);
	$shippingInfo     = getShippingInfo($shipmentInfo['SHIPMENT_TYPE_ID']);
	
	
	$shipment=array('shipmentId'=>$shipment_id,
					   'partyId'=>$party_id,
				'shipmentTypeId'=>$shipmentInfo['SHIPMENT_TYPE_ID'],
					 'carrierId'=>$shippingInfo['default_carrier_id'],
				'trackingNumber'=>$tracking_num,
					    'status'=>null,
	   'lastModifiedByUserLogin'=>$admin_name,
	);

	$handle=soap_get_client('ShipmentService');
	$response=$handle->updateShipment($shipment);
	
	// 增加操作记录
	$note = "补寄发票扫描快递面单, 面单号为：{$tracking_num}";
    
    global $db;
    // 记录订单备注
    $sql = "select oi.order_id,oi.order_status,oi.pay_status,oi.shipping_status 
            from ecshop.ecs_order_info oi
		    inner join romeo.order_shipment os ON oi.order_id  = cast(os.order_id as unsigned)
		    where os.shipment_id ='{$shipment_id}' limit 1";
    $order_info = $db->getRow($sql);
    
    $sql = "
        INSERT INTO ecshop.ecs_order_action 
        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
        ('{$order_info['order_id']}', '{$order_info['order_status']}', '{$order_info['shipping_status']}', '{$order_info['pay_status']}', NOW(), '{$note}', '{$_SESSION['admin_name']}')
    ";
   
    $db->query($sql);

	//print_r($shipment);
	//print "<script>window.location.href='print_invoice.php?action=list&&notnull=true';</script>";
}

function update_invoice_batch_tracking_number($SID,$TNS){

  for ($i=0; $i < sizeof($SID); $i++) {
      update_invoice_tracking_number($SID[$i],$TNS[$i]);
  }

  return $i;
}

if ($_REQUEST ['action'] == 'list') {
	$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('list', 'search')) ? $_REQUEST['act'] : 'list'; 
	
	$page = is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) ? $_REQUEST['page'] : 1;
	$state =$_REQUEST['state'];
	$notnull  =$_REQUEST ['notnull'];
	$start_time = $_REQUEST ['startTime'];
	$end_time   = $_REQUEST ['endTime'];
	if($start_time == '' || $start_time == null){
	    $start_time = date("Y-m-d H:i:s",strtotime("-3 Months",time()));
	}
	if($end_time == '' || $end_time == null){
	    $end_time = date("Y-m-d H:i:s",time());
	}
	$parameter['startTime'] = $start_time;
	$parameter['endTime'] = $end_time;
	
	//每页数量
	$page_size = $_REQUEST['page_size'];
	if($page_size==null) {
		$page_size = 20;
	}
	$page_size = (int)$page_size; 

	if($notnull!='')
	{
		$state=1;
	}
	
	if($state!='')
	{
		$conditionSearch=$state;
	}else
	{
		$conditionSearch = $_POST['conditionSearch'];
	}
	$order_sn2 = trim($_POST['orderSn']);
	if($conditionSearch==0)
	{
		$parameter['is_null']=$conditionSearch;
	}elseif($conditionSearch==1){
		$parameter['not_null']=$conditionSearch;
	}elseif($conditionSearch==2){
		$parameter['is_all']=$conditionSearch;
	}
	if($order_sn2!='')
	{
		$parameter['orderSn']=$order_sn2;
	}
				
	// 构造分页参数
	$total = getInvoiceCount($parameter); // 总记录数
	
	// 对所有订单计数
	$parameter['offset'] = 0;
	$parameter['limit'] = $total; 
	$invoiceTempList = getInvoiceList($parameter,false);	
	
	$shipping_id_name_list = array();
	//$facility_id_name_list = array();
	if(!empty($invoiceTempList)) {
		/*
		foreach($invoiceTempList as $invoice) {
			if(empty($invoice['facility_id'])) continue;
			$facility_id_name_list[$invoice['facility_id']]['facility_id'] = $invoice['facility_id'];
			$facility_id_name_list[$invoice['facility_id']]['facility_name'] = $invoice['facility_name'];
			if(isset($facility_id_name_list[$invoice['facility_id']]['count'])) {
				$facility_id_name_list[$invoice['facility_id']]['count'] += 1;
			} else {
				$facility_id_name_list[$invoice['facility_id']]['count'] = 1;
			}
		}
		*/									
		foreach($invoiceTempList as $invoice) {
			if(empty($invoice['shipping_id'])) continue;
			$shipping_id_name_list[$invoice['shipping_id']]['shipping_id'] = $invoice['shipping_id'];
			$shipping_id_name_list[$invoice['shipping_id']]['shipping_name'] = $invoice['shipping_name'];
			if(isset($shipping_id_name_list[$invoice['shipping_id']]['count'])) {
				$shipping_id_name_list[$invoice['shipping_id']]['count'] += 1;
			} else {
				$shipping_id_name_list[$invoice['shipping_id']]['count'] = 1;
			}
		}
	}
	
	
										
	$shipping_id=isset($_REQUEST['checkbox_shipping'])
			? $_REQUEST['checkbox_shipping']
			: NULL;
	
	/*$facility_id=isset($_REQUEST['checkbox_facility'])
			? $_REQUEST['checkbox_facility'] 
			: NULL;
	*/
	if($act == 'search') {
		/*
		if(!empty($facility_id)) {	
				$parameter['facility_id'] = $facility_id;					
		}
		*/
		if(!empty($shipping_id)) {	
				$parameter['shipping_id'] = $shipping_id;							
		}  	
		$total_page = ceil($total/$page_size);  // 总页数
		if ($page > $total_page) $page = $total_page;
		if ($page < 1) $page = 1;
		$parameter['offset'] = ($page - 1) * $page_size;
		$parameter['limit'] = $page_size; 
	   
		// 获取列表数据
		$invoiceList = getInvoiceList($parameter);
	// 分页	(点击下一页出现bug，应该是$state问题)
	//$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'batch_print_invoice.php?action=list&act=search&state='.$state, null, $conditionSearch);	
		
		$smarty->assign ('invoiceList', $invoiceList);	
		$smarty->assign ('condition', $conditionSearch);
	//$smarty->assign('pagination', $pagination->get_simple_output());	
	}
	
	$smarty->assign('sinri_best_shipping_id',$shipping_id);
	//$smarty->assign('sinri_test_facility_id',$facility_id);	
	$smarty->assign('page_size', $page_size);				
	//$smarty->assign('facility_id_name_list', $facility_id_name_list);
	$smarty->assign('shipping_id_name_list', $shipping_id_name_list);
	$smarty->assign ('total', $total);

	$smarty->display ( 'invoice_manage/batch_print_invoice.htm' );	
}

$ifUpdateDone = false;

if ($_REQUEST ['action'] == 'print') {

	if(isset($_REQUEST['SSID']) && $_REQUEST['SSID']!='0'){
			$SID=$_REQUEST['SSID'];
	} 
	if(isset($_REQUEST['track_num']) && $_REQUEST['track_num']!='0'){
			$TNS=$_REQUEST['track_num'];
	} 
	$update_done=update_invoice_tracking_number($SID, $TNS);
	
	// do update
	$ifUpdateDone = true; 
	
	if(isset($_REQUEST['id']) && $_REQUEST['id']!='0'){
		$id=$_REQUEST['id'];		
		$ids = explode(' ', $id);
		
		$smarty->assign('ids', implode(',', $ids));
		$src = "batch_print_invoice.php?print=1&id=" .join(',' ,$ids);
		$smarty->assign('src',$src);
/*		
		$smarty->assign('id',  $id);
		$src = "print_invoice_list.php?print=1&id=" .$id;
		$smarty->assign('src',$src);
*/
	}

	if(isset($_REQUEST['order_id']) && $_REQUEST['order_id']!='0'){
		$order_id=$_REQUEST['order_id'];
	} 

	$smarty->assign('ifUpdateDone', $ifUpdateDone);
	$smarty->display ( 'invoice_manage/batch_print_invoice.htm' );
}

if ($_REQUEST ['action'] == 'batch_print') {
	
	$TNS=array();
	$SID=array();
	if(isset($_REQUEST['shipment_id']) && $_REQUEST['shipment_id']!='0'){
		$SID=$_REQUEST['shipment_id'];
	} 
	if(isset($_REQUEST['TNS']) && $_REQUEST['TNS']!='0'){
		$TNS=$_REQUEST['TNS'];
	} 

	$update_done=update_invoice_batch_tracking_number($SID, $TNS);

	// do update
	$ifUpdateDone = true;
	$order_ids=array();
	$ids=array();
	if(isset($_REQUEST['order_ids']) && $_REQUEST['order_ids']!='0'){
		$order_ids=$_REQUEST['order_ids'];
	} 
	if(isset($_REQUEST['ids']) && $_REQUEST['ids']!='0'){
		$ids=$_REQUEST['ids'];
	} 
	$smarty->assign('ids', implode(',', $ids));
	$src = "batch_print_invoice.php?print=1&id=" .join(',' ,$ids);
	$smarty->assign('src',$src);
	
	$smarty->assign('ifUpdateDone', $ifUpdateDone);
	$smarty->display ( 'invoice_manage/batch_print_invoice.htm' );
}
?>