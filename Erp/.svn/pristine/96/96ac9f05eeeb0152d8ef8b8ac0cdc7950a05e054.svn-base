<?php
define ( 'IN_ECS', true );
require_once ('../includes/init.php');
require_once ('../includes/lib_invoice.php');
require_once ('../distribution.inc.php');
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once ('../includes/lib_print_action.php');
admin_priv ( 'print_invoice' );
if ($_REQUEST ['action'] == 'exportOrders') {
   $startCalendar   = trim($_POST ['startCalendar']); 
   $endCalendar   = trim($_POST ['endCalendar']); 
   
   $time['start']=$startCalendar;
   $time['end']=$endCalendar;
   $list = getInvoiceOrderIist($time);
   
   	$smarty->assign('invoiceOrderList',$list);
    header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "财务对账补寄发票订单列表" ) . ".csv" );
	$out = $smarty->fetch ( 'invoice_manage/invoiceOrderList.dwt' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
}
if ($_REQUEST ['action'] == 'print') {
	$id=$_REQUEST['id'];
	$admin_name = $_SESSION['admin_name'];
	$itemId = array('id' => $id,
					'admin_name' => $admin_name
					);
	$invoicePrint = getInvoicePrint($itemId);
	/*
	65553	雀巢
	65559	每伴
	65568	欧世蒙牛
	65574	金宝贝
	*/
	/*
	$party_list = array('65553','65574','65559','65568');
	if (in_array($invoicePrint[0]['party_id'], $party_list)) {
		$start_addr['p'] = "浙江省";
		$start_addr['c'] = "杭州市";
		$start_addr['d'] = "滨江区";
		$start_addr['addr'] = "滨兴路301号A3B区5楼";
	} else {
		$start_addr['p'] = "广东省";
		$start_addr['c'] = "东莞市";
		$start_addr['d']= "长安镇";
		$start_addr['addr'] = "乌沙步步高大道126号一楼101室";
	}
	$start_addr['tel'] = "0571-28189801";
	*/
	/**
	需求内容：
1.  请ERP同学在财务管理-销售发票管理-打印补寄发票界面，业务组ECCO，调整下顺丰面单打印信息，
	寄件地址改为杭州市滨江区江虹路611号1号楼402室，
	电话改为0571-28189826，
	收件人的详细信息，地区代码目的地处为空，
	付款方式改为第三方付，
	月结账号处改为7698041295 ，
	第三方付款地区改为转769FF；
2.  业务组金宝贝，雀巢，雀巢母婴，保乐力加，百事，大王，每伴，百威，支付方式为blackmores-京东，调整下圆通面单打印信息，
	单位名称改为上海乐麦网络科技有限公司，
	寄件地址改为杭州市滨江区江虹路611号1号楼402室，
	电话改为0571-28189826；
	**/
	$party_list = array(
		'65617','65618','65619','65614','65586','65587',
        '65609','65608','65600','65553','65634','65639',
        '65562','65563','65569','65623','65624','65629',
        '65622','65628','65621','65620','65574','65631','65633'
	);
	if (in_array($invoicePrint[0]['party_id'], $party_list)) {
		$start_addr['p'] = "浙江省";
		$start_addr['c'] = "杭州市";
		$start_addr['d'] = "滨江区";
		$start_addr['addr'] = "江虹路611号上峰电商产业园1号楼402室";
		$SINRI_FROM_COMPANY="HZCGP";
		$start_addr['tel'] = "0571-28189826";
	} else {
		$start_addr['p'] = "广东省";
		$start_addr['c'] = "东莞市";
		$start_addr['d']= "长安镇";
		$start_addr['addr'] = "乌沙步步高大道126号一楼101室";
		$SINRI_FROM_COMPANY="GDDG";
		$start_addr['tel'] = "0769-82283658";
	}

	// print_r($invoicePrint);die();
	foreach ($invoicePrint as $invoice_item) {
		LibPrintAction::addPrintRecord('INVOICE',$invoice_item['tracking_number'],$invoice_item['order_sn']);
	}
	
	
	$smarty->assign("start_addr", $start_addr);
	$smarty->assign('SINRI_FROM_COMPANY',$SINRI_FROM_COMPANY);
	if($invoicePrint[0]['carrier_id']==3)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/yt.htm' );
	}
	if($invoicePrint[0]['carrier_id']==9)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/ems.htm' );
	}
	if($invoicePrint[0]['carrier_id']==38)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/ghx.htm' );
	}	
	if($invoicePrint[0]['carrier_id']==20)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/sto.htm' );
	}
	if($invoicePrint[0]['carrier_id']==10)
	{
		$smarty->assign ('invoicePrint', $invoicePrint);
		$smarty->display ('invoice_manage/sf.htm' );
	}
	if($invoicePrint[0]['carrier_id']==28)
	{
	    $smarty->assign ('invoicePrint', $invoicePrint);
	    $smarty->display ('invoice_manage/ht.htm' );
	}
}
if ($_REQUEST ['action'] == 'edit') {
	$shipment_id   = trim($_POST ['shipment_id']);
	$tracking_num  = trim($_POST ['track_num']);
	
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
	print "<script>window.location.href='print_invoice.php?action=list&&notnull=true';</script>";
}
if ($_REQUEST ['action'] == 'list') {
	
	$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('list', 'search')) ? $_REQUEST['act'] : 'list'; 
	$page = is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) ? $_REQUEST['page'] : 1;
	$state		   =$_REQUEST['state'];
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
    
	if($notnull!='')
	{
		$state=1;
	}
	if($act=='search')
	{
		$state=$_POST['conditionSearch'];
	}
	
	if($state!='')
	{
		$conditionSearch=$state;
	}else
	{
		$conditionSearch = $_POST['conditionSearch'];
	}
	$order_sn2 = trim($_POST ['orderSn']);
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
	$extra_params=$conditionSearch;
	// 构造分页参数
	$total = getInvoiceCount($parameter); // 总记录数

	$page_size = 20;  // 每页数量
	$total_page = ceil($total/$page_size);  // 总页数
	if ($page > $total_page) $page = $total_page;
	if ($page < 1) $page = 1;
	$parameter['offset'] = ($page - 1) * $page_size;
	$parameter['limit'] = $page_size;
	
	// 获取列表数据
	$invoiceList = getInvoiceList($parameter);
	
	// 分页
	$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'print_invoice.php?action=list&startTime='.$start_time.'&endTime='.$end_time.'&state='.$state, null, $extra_params);
	
	$smarty->assign ('invoiceList', $invoiceList);
	$smarty->assign ('condition', $conditionSearch);
	$smarty->assign('pagination', $pagination->get_simple_output());
	$smarty->assign('startTime',$start_time);
	$smarty->assign('endTime',$end_time);
	$smarty->display ( 'invoice_manage/print_invoice_list.htm' );
}
?>