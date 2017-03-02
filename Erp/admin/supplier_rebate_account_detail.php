<?php
define('IN_ECS', true);

set_time_limit(1000);
require('includes/init.php');
admin_priv('view_supplier_rebate');
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
include('function.php');



$queryParam = new stdClass();

$keys = array('supplierId', 'createdStampMin', 'createdStampMax');
foreach ($keys as $key) {
    if($_REQUEST[$key]) {
        $queryParam->$key = $_REQUEST[$key];
        if ($key == 'createdStampMax') { //如果是结束时间，加上一天，这样统计出来的数据包含当天的
            $queryParam->$key = date("Y-m-d", strtotime($_REQUEST['createdStampMax']) + 24 * 3600 );
        }
    }
}

$providers = getProviders();

$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$offset = ($page - 1) * $size;
$queryParam->offset = $offset;
$queryParam->limit = $size;
//pp($queryParam);
if($_REQUEST['type'] == 'cash') {
    $type = 'cash';
    $supplierId = $_REQUEST['supplierId'];
    if(!$supplierId) exit();
    $queryParam->supplierId =$supplierId;

    $result = __SupplierRebateCall('querySupplierRebateCashReceipt', $queryParam);
    $SupplierRebateCashReceipts = $result->resultList->anyType;
    //  pp($SupplierRebateCashReceipts);
    $count = $result->total;
    $pager = Pager($count, $size, $page);
    if(is_object($SupplierRebateCashReceipts)) { $SupplierRebateCashReceipts = array($SupplierRebateCashReceipts); }
    if (is_array($SupplierRebateCashReceipts)) {
        foreach ($SupplierRebateCashReceipts as $key => $SupplierRebateCashReceipt) {
            $SupplierRebateCashReceipt->createdStamp = date('Y-m-d H:i:s', strtotime($SupplierRebateCashReceipt->createdStamp));
            $SupplierRebateCashReceipts[$key] = $SupplierRebateCashReceipt;
        }
    }
    $smarty->assign('endAmount', $result->endAmount);
    $smarty->assign('initialAmount', $result->initialAmount);
    $smarty->assign('netAmount', $result->netAmount);
    $smarty->assign('SupplierRebateCashReceipts', $SupplierRebateCashReceipts);
} else {
    $type = 'account';
    $supplierRebateAccountId = $_REQUEST['supplierRebateAccountId'];
    $supplierId = $_REQUEST['supplierId'];
    if(!$supplierRebateAccountId && !$supplierId) exit();
    if($supplierRebateAccountId) $queryParam->supplierRebateAccountId = $supplierRebateAccountId;
    if($supplierId) $queryParam->supplierId = $supplierId;

    //  pp($queryParam);
    $result = __SupplierRebateCall('querySupplierRebateAccountDetail', $queryParam);
    $SupplierRebateAccountDetails = $result->resultList->anyType;
    $count = $result->total;
    $pager = Pager($count, $size, $page);
    if(is_object($SupplierRebateAccountDetails)) { $SupplierRebateAccountDetails = array($SupplierRebateAccountDetails); }
    //  if (is_array($SupplierRebateCashReceipts)) {
    //      foreach ($SupplierRebateCashReceipts as $key => $SupplierRebateCashReceipt) {
    //          $SupplierRebateCashReceipt->createdStamp = date('Y-m-d H:i:s', strtotime($SupplierRebateCashReceipt->createdStamp));
    //          $SupplierRebateCashReceipts[$key] = $SupplierRebateCashReceipt;
    //      }
    //  }
    $smarty->assign('endAmount', $result->endAmount);
    $smarty->assign('initialAmount', $result->initialAmount);
    $smarty->assign('netAmount', $result->netAmount);
    $smarty->assign('SupplierRebateAccountDetails', $SupplierRebateAccountDetails);
}


$smarty->assign('supplierName', $providers[$_REQUEST['supplierId']]['provider_name']);
$smarty->assign('pager', $pager);
$smarty->assign('type', $type);
$smarty->assign('providers', $providers);
$smarty->display('oukooext/supplier_rebate_account_detail.htm');