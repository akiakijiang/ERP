<?php
/**
 * 查看出入库操作记录
 * 
 * 页面参数：
 * orderId: 订单号
 * serialNumber: 串号
 * startDate
 */

define('IN_ECS', true);
require('includes/init.php');
admin_priv("ck_inventory_transaction_details");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

$order_sn = trim($_REQUEST['order_sn']);
$serial_number = null;
if (trim($_REQUEST['serial_number'])) {
    $serial_number = trim($_REQUEST['serial_number']);
}
$start = trim($_REQUEST['start']);
$end = trim($_REQUEST['end']);

$order_id = null;
if ($order_sn != "") {
    $order_id = getOrderIdBySN($order_sn);
}

$inventory_soapclient = soap_get_client('InventoryService');

$result = $inventory_soapclient->getInventoryTransactionDetailsList(
    array(
        "orderId"       =>  $order_id,
        "serialNumber"  =>  $serial_number,
        "startDate"     =>  $start,
        "endDate"       =>  $end
    ));

$inventory_res = wrap_object_to_array($result->return->entry);

foreach ($inventory_res as $res) {
    if ($res->key == 'status') {
        $status = $res->value->stringValue;
    } else if ($res->key == 'itemList') {
        $inventory_list = $res->value->arrayList;
    }
}

$match_list = array();
if (is_array($inventory_list->anyType) && !empty($inventory_list->anyType)) {
    foreach ($inventory_list->anyType as $key => $inventory) {
        $match = array();
        foreach ($inventory->entry as $item) {
            if (in_array($item->key, array('fromStatusId', 'toStatusId'))) {
                $v = $_CFG['adminvars']['inventory_status_id'][$item->value];
            } else if ($item->key == 'inventoryTransactionTypeId') {
                $v = $_CFG['adminvars']['inventory_transaction_type_id'][$item->value];
            } else if ($item->key == 'orderId') {
                $v = getOrderSnByOrderId($item->value);
            } else {
                $v = $item->value;
            }
            $match[$item->key] = $v;
        }
        $match_list[] = $match;
    }
}

$smarty->assign('match_list', $match_list);
$smarty->display('oukooext/inventory_transaction_details.htm');
?>