<?php
define('IN_ECS', true);

require('includes/init.php');
require('includes/lib_product_code.php');

admin_priv('cw_finance_storage_main', 'cg_storage', 'purchase_order');
//print_r($_REQUEST);
$code = trim($_REQUEST['code']);
$order_id = intval($_REQUEST['order_id']);
$amount = intval($_REQUEST['amount']);
$amount = $amount > 0 ? $amount : 1;
$goods_id = intval($_REQUEST['goods_id']);
$printer_id = $_REQUEST['printer_id'];
$label = $_REQUEST['label'];


if(!$code) {
    die('没有要打印的条码');
}
if(substr($code,0,2) == 'SN') {
    die('以SN开头的条码不能打印');
}


$sql = "SELECT code FROM print_serial_number where code = '$code' AND status = 'PENDING' LIMIT 1";
$exist = $db->getOne($sql);
if($exist) {
    print $code." 已添加，请等待打印";;
} else {
    print_product_code($order_id, $code, $amount, $goods_id, $printer_id, $label);
    print $code." 添加成功";
}
