<?php
define('IN_ECS', true);

set_time_limit(1000);
require('includes/init.php');
admin_priv('view_supplier_rebate','rebate_manage');
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
include('function.php');

//$SupplierRebateId = $_REQUEST['SupplierRebateId'];
//$supplierRebateTypeId_enum = array('SINGLEPRODUCT' => '单个机器返利', 'ORDERPRODUCT' => '针对单个订单', 'PROVIDERPRODUCT' => '针对某个供应商的某种商品', 'PROVIDER' => '针对供应商');
$supplierRebateTypeId_enum = array('SERIAL_NUMBER' => '单个机器返利', 'ORDER' => '针对单个订单', 'PRODUCT' => '针对某个供应商的某种商品', 'SUPPLIER' => '针对供应商');
$supplierRebateStatusId_enum = array('UNPAID' => '返利未确认', 'CONFIRMED' => '返利已经确认', 'DISCARD'=> '采购废弃', 'CANCEL' => '供应商取消');
$supplierRebateModeId_enum = array('INVOICEPAID' => '返现金不扣发票', 'DEDUCTED' => '抵扣货款', 'GIFTED' => '返实物');
$sort_enum = array('descFlagReceivable' => '按预期返利金额从高到低', 'descFlagReceivable_false' => '按预期返利金额从低到高', 
                   'descFlagReceivableRatio' => '按预期返利金额百分比从高到低', 'descFlagReceivableRatio_false' => '按预期返利金额百分比从低到高', 
                   'descFlagActualPaid' => '按实际返利金额从高到低', 'descFlagActualPaid_false' => '按实际返利金额从低到高', 
                   'descFlagCreatedStamp' => '按建立时间从晚到早', 'descFlagCreatedStamp_false' => '按建立时间从早到晚');
                   
$queryParam = new stdClass();
if(intval($_REQUEST['goods_id'])) {
  $productId = getProductId($_REQUEST['goods_id'], $_REQUEST['style_id']);
  $queryParam->productId = ($productId);
}

if(intval($_REQUEST['supplierId'])) {
  $queryParam->supplierId = intval($_REQUEST['supplierId']);
}

if($_REQUEST['createdByUserLogin']) {
    $queryParam->createdByUserLogin = trim($_REQUEST['createdByUserLogin']);
}

if($_REQUEST['supplierRebateTypeId']) {
  $queryParam->supplierRebateTypeId = $_REQUEST['supplierRebateTypeId'];
}

if($_REQUEST['supplierRebateModeId']) {
  $queryParam->supplierRebateModeId = $_REQUEST['supplierRebateModeId'];
}

if(($_REQUEST['supplierRebateStatusId'])) {
  $queryParam->supplierRebateStatusId = $_REQUEST['supplierRebateStatusId'];
}

if (strtotime($_REQUEST['createdTimeMin'])) {
  $queryParam->createdTimeMin = $_REQUEST['createdTimeMin'];
}
if (strtotime($_REQUEST['createdTimeMax'])) {
  $queryParam->createdTimeMax = date("Y-m-d", strtotime($_REQUEST['createdTimeMax']) + 24 * 3600 );
}


if($_REQUEST['sort']) {
  switch ($_REQUEST['sort']) {
    case 'descFlagReceivable':
      $queryParam->descFlagReceivable = true;
      break;
    case 'descFlagReceivable_false':
       $queryParam->descFlagReceivable = false;
      break;
    case 'descFlagReceivableRatio':
      $queryParam->descFlagReceivableRatio = true;
      break;
    case 'descFlagReceivableRatio_false':
      $queryParam->descFlagReceivableRatio = false;
      break;
    case 'descFlagActualPaid':
      $queryParam->descFlagActualPaid = true;
      break;
    case 'descFlagActualPaid_false':
      $queryParam->descFlagActualPaid = false;
      break;
    case 'descFlagCreatedStamp':
      $queryParam->descFlagCreatedStamp = true;
      break;
    case 'descFlagCreatedStamp_false':
      $queryParam->descFlagCreatedStamp = false;
      break;
  }
}

$size = 200;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$offset = ($page - 1) * $size;
$queryParam->offset = $offset;
$queryParam->limit = $size;
//pp($queryParam);
$result = __SupplierRebateCall('querySupplierRebate', $queryParam);
//pp($result);
$SupplierRebates = $result->resultList->anyType;
$count = $result->total;
$pager = Pager($count, $size, $page);
if(is_object($SupplierRebates)) { $SupplierRebates = array($SupplierRebates); }
//pp($SupplierRebates);
$providers = getProviders();
$sumDeductedExpectedAmount = 0;
$sumDeductedReceivedAmount = 0;
$sumDeductedConfirmedAmount = 0;

$sumInvoicepaidExpectedAmount = 0;
$sumInvoicepaidReceivedAmount = 0;
$sumInvoicepaidConfirmedAmount = 0;
if(is_array($SupplierRebates)) {
  foreach ($SupplierRebates as $key => $SupplierRebate) {
    $SupplierRebate->supplierName = $providers[$SupplierRebate->supplierId]['provider_name'];
    $SupplierRebates[$key] = formatSupplierRebate($SupplierRebate);
    if ($SupplierRebate->supplierRebateModeId == 'DEDUCTED') {
        $sumDeductedExpectedAmount += $SupplierRebate->expectedAmount;
        $sumDeductedReceivedAmount += $SupplierRebate->receivedAmount;
        $sumDeductedConfirmedAmount += $SupplierRebate->confirmedAmount;
    }
    if ($SupplierRebate->supplierRebateModeId == 'INVOICEPAID') {
        $sumInvoicepaidExpectedAmount += $SupplierRebate->expectedAmount;
        $sumInvoicepaidReceivedAmount += $SupplierRebate->receivedAmount;
        $sumInvoicepaidConfirmedAmount += $SupplierRebate->confirmedAmount;
    }
  }
}


$smarty->assign('supplierRebateTypeId_enum', $supplierRebateTypeId_enum);
$smarty->assign('supplierRebateStatusId_enum', $supplierRebateStatusId_enum);
$smarty->assign('supplierRebateModeId_enum', $supplierRebateModeId_enum);
$smarty->assign('sort_enum', $sort_enum);
$smarty->assign('pager', $pager);
$smarty->assign('providers', $providers);
$smarty->assign('SupplierRebates', $SupplierRebates);

//各种金额的总计
$smarty->assign('sumDeductedExpectedAmount', $sumDeductedExpectedAmount);
$smarty->assign('sumDeductedReceivedAmount', $sumDeductedReceivedAmount);
$smarty->assign('sumDeductedConfirmedAmount', $sumDeductedConfirmedAmount);
$smarty->assign('sumInvoicepaidExpectedAmount', $sumInvoicepaidExpectedAmount);
$smarty->assign('sumInvoicepaidReceivedAmount', $sumInvoicepaidReceivedAmount);
$smarty->assign('sumInvoicepaidConfirmedAmount', $sumInvoicepaidConfirmedAmount);

//是否可以收到返利
$smarty->assign('can_receive_rebate_cash', check_admin_priv("receive_rebate_cash"));
$smarty->assign('can_receive_rebate_deducted', check_admin_priv("receive_rebate_deducted") );


$smarty->display('oukooext/supplier_rebate_list.htm');