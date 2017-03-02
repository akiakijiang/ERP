<?php
/**
 * 查看库存总表
 * 
 * 页面参数：
 * 商品id
 */

define('IN_ECS', true);
require('includes/init.php');
admin_priv("ck_inventory_summary");
require(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

$inventory_soapclient = soap_get_client('InventoryService');

$condition = get_condition();
if (!is_null($condition['facilityId'])) {
    $result = $inventory_soapclient->getInventorySummaryList($condition);
    $inventory_summary_list = $result->return->InventorySummary;	
}

$smarty->assign('facility_list', get_available_facility());
$smarty->assign('inventory_status_mapping', $_CFG['adminvars']['inventory_status_id']);
$smarty->assign('inventory_summary_list', $inventory_summary_list);
$smarty->display('oukooext/inventory_summary.htm');

function get_condition() {
    extract($_POST);
    $condition = array(
        "productId" => $product_id,
        "statusId" => $status_id,
        "facilityId" => $facility_id,
        "containerId" => $container_id,
        "goodsPartyId" => $_SESSION['party_id'],
       );
    return $condition;
}
?>