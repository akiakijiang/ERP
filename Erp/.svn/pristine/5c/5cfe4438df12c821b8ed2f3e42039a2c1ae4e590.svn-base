<?php
define('IN_ECS', true);

set_time_limit(1000);
require('includes/init.php');
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
include('function.php');

$act = $_REQUEST['act'];
$supplierRebateId = $_REQUEST['supplierRebateId'];
$supplierRebateTypeId_enum = array('SERIAL_NUMBER' => '单个机器返利', 'ORDER' => '针对单个订单', 'PRODUCT' => '针对某个供应商的某种商品', 'SUPPLIER' => '针对供应商');
$supplierRebateStatusId_enum = array('UNPAID' => '返利未确认', 'CONFIRMED' => '返利已经确认', 'USED' => '返利已使用', 'DISCARD'=> '供应商取消',  'CANCEL' => '取消返利');
$supplierRebateModeId_enum = array('INVOICEPAID' => '返现金不扣发票', 'DEDUCTED' => '抵扣货款', 'GIFTED' => '返实物');

if($act != 'create' && $act != 'create_submit' ) {
  $queryParam = new stdClass();
  $queryParam->supplierRebateId = $supplierRebateId;
  $SupplierRebate = __SupplierRebateCall('querySupplierRebate', $queryParam)->resultList->anyType;
  if(!$SupplierRebate->supplierRebateId) { if($act == 'edit_submit') { make_json_error("no supplier rebate"); } else { die('no supplier rebate'); } }
  
  $SupplierRebate->createdStamp = $SupplierRebate->createdStamp ? date('Y-m-d H:i:s', strtotime($SupplierRebate->createdStamp)) : '';
  $SupplierRebate->lastUpdatedStamp = $SupplierRebate->lastUpdatedStamp ? date('Y-m-d H:i:s', strtotime($SupplierRebate->lastUpdatedStamp)) : '';
  
  $supplierRebateItems = __SupplierRebateCall('queryRebateItemByRebateId', $SupplierRebate->supplierRebateId)->resultList->anyType;
  if(is_object($supplierRebateItems)) $supplierRebateItems = array($supplierRebateItems);
  if(is_array($supplierRebateItems)) {
    foreach ($supplierRebateItems as $key => $supplierRebateItem) {
      if($supplierRebateItem->productId) {
        $goods_id_style_id = getGoodsIdStyleIdByProductId($supplierRebateItem->productId);
        $supplierRebateItems[$key]->productName = $goods_id_style_id['goods_name'];
      }
      if($supplierRebateItem->purchaseOrderId) {
        $supplierRebateItems[$key]->purchaseOrderSn = getOrderSnByOrderId($supplierRebateItem->purchaseOrderId);
      }
    }
    $SupplierRebate->supplierRebateItems = $supplierRebateItems;
  }
}
$providers = getProviders();

$smarty->assign('supplierRebateTypeId_enum', $supplierRebateTypeId_enum);
$smarty->assign('supplierRebateStatusId_enum', $supplierRebateStatusId_enum);
$smarty->assign('supplierRebateModeId_enum', $supplierRebateModeId_enum);
$smarty->assign('providers', $providers);
$smarty->assign('act', $act);
$smarty->assign('edit_by', $_REQUEST['edit_by']);
$smarty->assign('SupplierRebate', $SupplierRebate);

//pp($SupplierRebate);

if ($act == 'create') {
  admin_priv('create_supplier_rebate');
  $tpl_name = 'supplier_rebate_detail_create.htm';
} elseif ($act == 'edit') {
  if($_REQUEST['edit_by'] == 'purchase') {
    admin_priv('edit_supplier_rebate_purchase');
    if($SupplierRebate->supplierRebateStatusId != 'UNPAID')  sys_msg("该返利无法编辑");
    $tpl_name = 'supplier_rebate_detail_edit_purchase.htm';
  } elseif($_REQUEST['edit_by'] == 'finance') {
    admin_priv('edit_supplier_rebate_finance');
    if($SupplierRebate->supplierRebateStatusId != 'CONFIRMED')  sys_msg("该返利无法使用");
    $tpl_name = 'supplier_rebate_detail_edit_finance.htm';
  }
} elseif ($act == 'create_submit') {
  admin_priv('create_supplier_rebate');
  extract($_POST);
  
  $RebateModeMappping = array('INVOICEPAID' => 'expectedAmount', 'DEDUCTED' => 'expectedAmount', 'GIFTED' => 'expectedGiftAmount' );
  $expectefield = $RebateModeMappping[$supplierRebateModeId];
  if(!$expectefield) sys_msg('请填写正确的返利形式');
  if(!is_numeric(${$expectefield})) sys_msg('请填写正确的数字');
  if(!$supplierRebateRuleId) sys_msg('请选择返利规则');
  
  $SupplierRebate = new stdClass();
  $SupplierRebate->$expectefield = ${$expectefield};
  $SupplierRebate->supplierRebateModeId = $supplierRebateModeId;
  $SupplierRebate->supplierRebateTypeId = $supplierRebateTypeId;
  $SupplierRebate->supplierRebateRuleId = $supplierRebateRuleId;
  $SupplierRebate->supplierId = $supplierId;
  $SupplierRebate->description = $description;
  $SupplierRebate->createdByUserLogin = $_SESSION['admin_name'];
  
  $RebateTypeMappping = array(
    'SERIAL_NUMBER' => array('supplierId', 'serialNumber', ),
    'ORDER' => array('supplierId', 'purchaseOrderId', ),
    'PRODUCT' => array('supplierId', 'productId', ),
    'SUPPLIER' => array('supplierId', ),
  );
  
  $field_array = $RebateTypeMappping[$supplierRebateTypeId];
  if(!$field_array) sys_msg("错误的返利类型");
  
  //开始准备数据
  if(in_array('productId', $field_array)) {
    if(!$goods_id) sys_msg("请填写选择相应的商品");
    $productId = getProductId($goods_id, $style_id);
  }
  
  $SupplierRebateItemArray = new stdClass();
  if(in_array($supplierRebateTypeId, array('SERIAL_NUMBER', 'ORDER', 'PRODUCT' )) ) {
    $purchaseOrderIds = explode("\n", $purchaseOrderId);
    $serialNumbers = explode("\n", $serialNumber);
    
    $tempArray = array();
    $items_array = explode("\n", $items);
    foreach ($items_array as $item) {
      if(trim($item) == '') continue;
      if($supplierRebateTypeId == 'SERIAL_NUMBER') $serialNumber = $item;
      if($supplierRebateTypeId == 'ORDER') $purchaseOrderId = $item;
      if($supplierRebateTypeId == 'PRODUCT') {
        $productId = $item;
      }
      $SupplierRebateItem = new stdClass();
      foreach ($field_array as $field) {
        if(${$field} == null || ${$field} == '') {
          sys_msg("请填写相应{$field}的数据");
        }
        $SupplierRebateItem->$field = ${$field};
      }
      $tempArray[] = $SupplierRebateItem;
    }
    $SupplierRebateItemArray->SupplierRebateItem = $tempArray;
  }
  $newSupplierRebate = __SupplierRebateCall('createSupplierRebate', $SupplierRebate, $SupplierRebateItemArray);
//  $newSupplierRebate = createSupplierRebate($SupplierRebate);
  if($newSupplierRebate->supplierRebateId) {
    header("Location:supplier_rebate_detail.php?act=create&supplierRebateModeId={$supplierRebateModeId}&supplierRebateTypeId={$supplierRebateTypeId}&supplierId={$supplierId}&info=".urldecode(" 返利 {$newSupplierRebate->supplierRebateId} 创建成功"));
    exit();
//    sys_msg('新建返利成功', 0, array(array('text'=>'查看该返利', 'href'=>"supplier_rebate_detail.php?supplierRebateId={$newSupplierRebate->supplierRebateId}")));
  } else {
    sys_msg('新建返利失败', 0 );
  }

} elseif ($act == 'edit_submit') {
  if(!check_admin_priv('edit_supplier_rebate_purchase')) make_json_error('没有权限操作');
  extract($_POST);
  
  if(!$SupplierRebate->supplierRebateId)  make_json_error("该返利不存在，无法编辑");
  
  $act_detail = $_REQUEST['act_detail'];
  $confirmedAmount = $_REQUEST['confirmedAmount'];
  if($act_detail == 'confirm' || $act_detail == 'receive') {
    if(!is_numeric($confirmedAmount)) make_json_error('请填写正确的数字');
    if($confirmedAmount < 0 ) make_json_error('不能输入负数');
  }
  
  if($act_detail == 'confirm') { //确认返利
    if($SupplierRebate->supplierRebateStatusId != 'UNPAID')  make_json_error("该返利无法编辑");
    if(__SupplierRebateCall('supplierConfirmRebate', $supplierRebateId, $confirmedAmount)) {
      make_json_result("确认返利成功");
    } else {
      make_json_error("确认返利失败");
    }
  } elseif ($act_detail == 'receive') { //收到返利金额
    if(__SupplierRebateCall('receiveRebateFund', $supplierRebateId, $confirmedAmount, $_SESSION['admin_name']) == 'OK') make_json_result("收到返利 {$confirmedAmount} ");
    else make_json_error("收到返利失败");
  } elseif ($act_detail == 'cancel') { //供应商取消返利
    if($SupplierRebate->supplierRebateStatusId != 'UNPAID')  make_json_error("该返利无法取消");
    if (__SupplierRebateCall('supplierCancelRebate', $supplierRebateId) == 'OK') make_json_result("该返利取消成功");
    else make_json_error("该返利无法取消");
  } elseif ($act_detail == 'discard') { //废弃返利
    if($SupplierRebate->supplierRebateStatusId != 'UNPAID')  make_json_error("该返利无法废弃");
    if(__SupplierRebateCall('discardRebate', $supplierRebateId) == 'OK') make_json_result("该返利废弃成功");
    else make_json_error("该返利无法废弃");
  }
  exit();
} else {
  admin_priv('view_supplier_rebate');
  $tpl_name = 'supplier_rebate_detail.htm';
//  $SupplierRebate->supplierRebateTypeId_description = $supplierRebateTypeId_enum[$SupplierRebate->supplierRebateTypeId];
//  $SupplierRebate->supplierRebateStatusId_description = $supplierRebateStatusId_enum[$SupplierRebate->supplierRebateStatusId];
//  $SupplierRebate->supplierRebateModeId_description = $supplierRebateModeId_enum[$SupplierRebate->supplierRebateModeId];
  $SupplierRebate = formatSupplierRebate($SupplierRebate);
  $SupplierRebate->supplierName = $providers[$SupplierRebate->supplierId]['provider_name'];
  //是否可以收到返利
    $smarty->assign('can_receive_rebate_cash', check_admin_priv("receive_rebate_cash"));
    $smarty->assign('can_receive_rebate_deducted', check_admin_priv("receive_rebate_deducted") );

//  if(!$SupplierRebate->supplierRebateId) sys_msg("该返利不存在，无法编辑",0,array(),false);
}

$smarty->display("oukooext/{$tpl_name}");
