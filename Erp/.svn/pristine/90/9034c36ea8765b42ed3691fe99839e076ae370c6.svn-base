<?php
define ( 'IN_ECS', true );
require_once ('../includes/init.php');
require_once ('../includes/lib_invoice.php');

admin_priv ( 'invoice_order_export' );
$start_time = trim($_POST['startCalendar']);
$end_time = trim($_POST['endCalendar']);
$shipping_status = trim($_POST['shipping_status']);
$order_sn = trim($_POST['order_sn']);

if($start_time != ''){
    $parameter['start_time'] = $start_time;
}
if($end_time != ''){
    $parameter['end_time'] = $end_time;
}
if($shipping_status != ''){
    $parameter['shipping_status'] = $shipping_status;
}
if($order_sn != ''){
    $parameter['order_sn'] = $order_sn;
}

$invoice_order_list = getCodOrder($parameter);

$smarty->assign('invoice_order_list',$invoice_order_list);
header ( "Content-type:application/vnd.ms-excel" );
header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "多美滋COD未添加补寄发票订单列表" ) . ".csv" );
$out = $smarty->fetch ( 'invoice_manage/invoice_order_list.dwt' );
echo iconv ( "UTF-8", "GB18030", $out );
exit ();
?>