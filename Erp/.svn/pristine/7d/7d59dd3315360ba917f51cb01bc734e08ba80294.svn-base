<?php
define('IN_ECS', true);

set_time_limit(1000);
require('includes/init.php');
admin_priv('view_supplier_rebate');
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
include('function.php');

$queryParam = new stdClass();
$keys = array('supplierId', 'balanceAtpMin', 'balanceAtpMax', 'balanceQohMin', 'balanceQohMax');
foreach ($keys as $key) {
    if($_REQUEST[$key]) $queryParam->$key = $_REQUEST[$key];
}

//    - DescBalanceQoh
//    - DescBalanceAtp

$size = 200;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$offset = ($page - 1) * $size;
$queryParam->offset = $offset;
$queryParam->limit = $size;
$queryParam->descBalanceQoh = true;

//$result = querySupplierAccount($queryParam);
$result = __SupplierRebateCall('querySupplierRebateAccount', $queryParam);
//pp($result);
$SupplierRebateAccounts = $result->resultList->anyType;
$sql = "SELECT * FROM {$ecs->table('provider')} ORDER BY `order` DESC, provider_id";
$rs = $db->query($sql);
$providers = Array();
while ($row = $db->fetchRow($rs)) {
    $providers[$row['provider_id']] = $row;
}
$count = $result->total;
$pager = Pager($count, $size, $page);
if(is_object($SupplierRebateAccounts)) { $SupplierRebateAccounts = array($SupplierRebateAccounts); }
if(is_array($SupplierRebateAccounts)) {
    foreach ($SupplierRebateAccounts as $key => $SupplierRebateAccount) {
        $SupplierRebateAccount->supplierName = $providers[$SupplierRebateAccount->supplierId]['provider_name'];
        $SupplierRebateAccount->balanceQoh = number_format($SupplierRebateAccount->balanceQoh, 2, '.', '');
        $SupplierRebateAccount->balanceAtp = number_format($SupplierRebateAccount->balanceAtp, 2, '.', '');

        $expectedAmounttotal = 0;
        $queryParam = new stdClass();
        $queryParam->supplierId = $SupplierRebateAccount->supplierId;
        $queryParam->supplierRebateStatusId = 'UNPAID';
        $queryParam->supplierRebateModeId = 'DEDUCTED';
        $queryParam->limit = 300;
        $result = __SupplierRebateCall('querySupplierRebate', $queryParam);
        $SupplierRebates = $result->resultList->anyType;

        if (is_object($SupplierRebates)) $SupplierRebates = array($SupplierRebates);
        if(is_array($SupplierRebates)) {
            foreach ($SupplierRebates as $SupplierRebate) {
                $expectedAmounttotal += ((float) $SupplierRebate->expectedAmount);
            }
        }
        $SupplierRebateAccount->expectedAmounttotal = number_format($expectedAmounttotal, 2, '.', '');
        $SupplierRebateAccounts[$key] = $SupplierRebateAccount;
    }
}
if($_REQUEST['supplierId']) {
    $queryParam = new stdClass();
    $queryParam->supplierId = $_REQUEST['supplierId'];
    $result = __SupplierRebateCall('querySupplierRebateCashReceipt', $queryParam);
    $SupplierRebateCashReceipts = $result->resultList->anyType;

    if(is_object($SupplierRebateCashReceipts)) $SupplierRebateCashReceipts = array($SupplierRebateCashReceipts);
    $cashtotal = 0;
    if (is_array($SupplierRebateCashReceipts)) {
        foreach ($SupplierRebateCashReceipts as $SupplierRebateCashReceipt) {
            $cashtotal += (float) $SupplierRebateCashReceipt->valueDiff;
        }
    }



    $cashtotal = number_format($cashtotal, 2, '.', '');
    $smarty->assign('cashtotal', $cashtotal);
}




$smarty->assign('providers', $providers);
$smarty->assign('pager', $pager);
$smarty->assign('SupplierRebateAccounts', $SupplierRebateAccounts);
$smarty->display('oukooext/supplier_rebate_account.htm');

