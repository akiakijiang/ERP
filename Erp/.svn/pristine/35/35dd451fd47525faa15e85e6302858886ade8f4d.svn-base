<?php
define('IN_ECS', true);
require('includes/init.php');

admin_priv('cw_invoice_main', 'cg_no_shipping_invoice'); // 权限名为 发票相关 
require("function.php");

$back = $_REQUEST['back'];
$act = $_REQUEST['act_type'];

// 编辑发票号
if ($act == 'edit_shipping_invoice') {
	$order_id = $_POST['order_id'];
	$order_shipping_invoice = $_POST['order_shipping_invoice'];
	
	if (!empty($order_id)) {
	   update_order_shipping_invoice($order_id,$order_shipping_invoice);
	}	
}
// 批量编辑发票号
else if ($act == 'batch_edit_shipping_invoice') {
	$order_ids = $_POST['order_ids'];
	$order_shipping_invoices = $_POST['order_shipping_invoices'];
	if (!empty($order_ids)) {
	    foreach ($order_ids as $key=>$order_id) {
	        $order_id = intval($order_id);
	        $order_shipping_invoice = $order_shipping_invoices[$key];

	        update_order_shipping_invoice($order_id,$order_shipping_invoice);
	    }
	}	
}

// 编辑出库单打印时间
else if ($act == 'edit_print_time') {
	$order_id = $_POST['order_id'];
    if (!empty($order_id)) {
        $print_time = $_POST['print_time'];

        $sql = "UPDATE order_attribute SET attr_value = '{$print_time}' WHERE order_id = '{$order_id}' AND attr_name = 'SHIPPING_INVOICE_PRINT_TIME' LIMIT 1";
        $db->query($sql);
        if (!$db->affected_rows()) {
        	$db->query("INSERT INTO order_attribute (order_id, attr_name, attr_value) VALUES ('{$order_id}', 'SHIPPING_INVOICE_PRINT_TIME', '{$print_time}')", 'SILENT');
        }
    }
}

//Header("Location: $back"); 
