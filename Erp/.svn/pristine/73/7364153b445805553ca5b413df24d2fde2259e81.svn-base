<?php
define('IN_ECS', true);

set_time_limit(1000);
require('includes/init.php');
require_once(ROOT_PATH. "RomeoApi/lib_inventory.php");
include('function.php');
if(!check_admin_priv('view_supplier_rebate')) make_json_error('你没有查看供应商规则相应的权限');
$act = $_REQUEST['act'];
if($act == 'querySupplierRebateRulesBySupplierId') { //查询供应商用户的返利rule, modulePageURL在这一步也做了
    $supplierId = $_REQUEST['supplierId'];
    $queryParam = new stdClass();
    $queryParam->supplierId = $supplierId;
    $SupplierRebateRules = __SupplierRebateCall('querySupplierRebateRulesBySupplierId', $queryParam)->resultList->anyType;
    //  pp($SupplierRebateRules);
    if(is_object($SupplierRebateRules)) $SupplierRebateRules = array($SupplierRebateRules);
    if(!is_array($SupplierRebateRules)) make_json_error('没有相应的返利规则');
    $tmp = array();
    foreach ($SupplierRebateRules as $SupplierRebateRule) {
        $SupplierRebateRule->modulePageURL = __SupplierRebateCall('getSupplierRebateRuleStatById', $SupplierRebateRule->supplierRebateRuleStatId)->modulePageURL;
        $tmp[] = array('supplierRebateRuleId' => $SupplierRebateRule->supplierRebateRuleId,
        'description' => $SupplierRebateRule->description,
        'modulePageURL' => $SupplierRebateRule->modulePageURL);
    }
    //  pp($tmp);
    //  $o = new stdClass();
    //  $o->description = '按商品串号查询';
    //  $o->modulePageURL = 'supplier_rebate_rule_stat.php?act=getmodulePageURL&queryBy=serialNumber';
    //  $tmp[] = $o ;
    //  $o = new stdClass();
    //  $o->description = '按supplierId设置';
    //  $o->modulePageURL = 'supplier_rebate_rule_stat.php?act=getmodulePageURL&queryBy=supplierId';
    //  $tmp[] = $o;
    make_json_result('OK', '', array('SupplierRebateRules' => $tmp) ); //返回返利规则
    exit();
} elseif ($act == 'getmodulePageURL') { //给出查询的条件
    $smarty->assign('act', $act);
    $smarty->assign('queryBy', $_REQUEST['queryBy']);
    $html = $smarty->fetch("oukooext/supplier_rebate_rule_stat.htm");
    make_json_result($html);
    exit();
} elseif ($act == 'querySupplierRebate') {
    $supplierId = $_REQUEST['supplierId'];
    $supplierRebateRuleId = $_REQUEST['supplierRebateRuleId'];
    $queryParam = new stdClass();
    $queryParam->supplierId = $supplierId;
    if($supplierRebateRuleId) {
        $queryParam->supplierRebateRuleId = $supplierRebateRuleId;
    }

    $queryParam->offset = 0;
    $queryParam->limit = 20;
    //pp($queryParam);
    $result = __SupplierRebateCall('querySupplierRebate', $queryParam);
    $SupplierRebates = $result->resultList->anyType;
    if(is_object($SupplierRebates)) $SupplierRebates = array($SupplierRebates);
    foreach ($SupplierRebates as $key => $SupplierRebate) {
        $SupplierRebates[$key] = formatSupplierRebate($SupplierRebate);
    }
    make_json_result('', '', array('SupplierRebates'=>$SupplierRebates));
}
