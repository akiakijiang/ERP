
<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once('order/cls_sales_order_header.php');

//admin_priv('view_new_order_edit_page');
admin_priv('customer_service_manage_order', 'order_view');
//pp($_SESSION['facility_id']);
if(isset($_REQUEST['order_id'])){
	if(!isset($_REQUEST['detail_type'])){
		$order_header = new SalesOrderHeader(array('order_id' => $_REQUEST['order_id'], 'action_type'=>'query'));
		$order_header->QueryData();
//		pp('$order_header:');pp($order_header);
		$smarty->assign('order_header', $order_header);
		$smarty->display('order/order_edit_zjq.htm');
	}else{

	}
}else{
	sys_msg("请输入order_id");
}
?>