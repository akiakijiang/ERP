<?php
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
require("includes/lib_cps.php");
party_priv(PARTY_OUKU);
admin_priv('cps_rebate');

$act = $_REQUEST['act'];
//pp($_REQUEST);die();
if ($act == "add") {
    $rebate = convert_rebate($_REQUEST);
    $db->autoExecute('cps_rebate_rule', $rebate);
    $cps_rebate_rule_id = $db->getOne("SELECT LAST_INSERT_ID(); ");
    $rebate_details = convert_rebate_detail($_REQUEST);
    foreach ($rebate_details as $rebate_detail) {
        $rebate_detail['cps_rebate_rule_id'] = $cps_rebate_rule_id;
        $db->autoExecute('cps_rebate_rule_detail', $rebate_detail);
    }
    $smarty->assign("message", "添加成功");
} elseif ($act == "edit") {
    $rebate = convert_rebate($_REQUEST);
    $db->autoExecute('cps_rebate_rule', $rebate, "UPDATE",
         " cps_rebate_rule_id = '{$_REQUEST['cps_rebate_rule_id']}' ");
    $rebate_details = convert_rebate_detail($_REQUEST);
    foreach ($rebate_details as $key =>$rebate_detail) {
        $db->autoExecute('cps_rebate_rule_detail', $rebate_detail, "UPDATE",
            " cps_rebate_rule_detail_id = '{$_REQUEST['cps_rebate_rule_detail_id'][$key]}' ");
    }
    $smarty->assign("message", "修改成功");
}

//cps列表
$cps_rebate_rules = get_cps_rebate_rules(null, null);
$rebate_rules = $cps_rebate_rules['cps_rebate_rule_id'];
foreach ($rebate_rules as $key => $rebate_rule) {
    foreach ($rebate_rule as $rule_key => $rule) {
        $tmp = unserialize($rule['rebate_detail']);
        $rules = array();
        foreach ($tmp as $t) {
        	$rules[] = join(":", $t);
        }
        $rebate_rules[$key][$rule_key]['rules'] = join(";", $rules);
    }
}

$cps_name_mapping = array(
    'egou'      =>      '易购',
    'netease'   =>      '有道',
);
$calc_type_mapping = array(
    'multiply'  =>      '百分比',
    'add'       =>      '累加'
);
$smarty->assign('rebate_rules', $rebate_rules);
$smarty->assign('cps_name_mapping', $cps_name_mapping);
$smarty->assign('is_delete_mapping', array('Y' => '禁用', 'N' => '启用'));
$smarty->assign('calc_type_mapping', $calc_type_mapping);
$smarty->assign('party_id_mapping', array(1 => '欧酷', 4 => '欧酷派'));
$smarty->display('oukooext/cps_rebate.htm');

/**
 * 设置返现主体
 *
 * @param unknown_type $req
 * @return unknown
 */
function convert_rebate($req) {
    $rebate = array();
    $rebate['cps_name'] = $req['cps_name'];
    $rebate['startdate'] = $req['startdate'];
    $rebate['enddate'] = $req['enddate'];
    $rebate['is_delete'] = $req['is_delete'];
    $rebate['party_id'] = $req['party_id'];
    return $rebate;
}

/**
 * 设置返现规则细节
 *
 * @param unknown_type $req
 * @return unknown
 */
function convert_rebate_detail($req) {
    $rebate_details = array();
    foreach ($req['rebate_type'] as $key => $rebate_type) {
        $rebate_detail = array();
        $tmp = split(';', $req['rebate_detail'][$key]);
        if ($tmp) {
            foreach ($tmp as $tmp_key => $detail) {
                $rebate_detail[] = split(":", $detail);
            }
        }
    	$rebate_details[] = array(
            'cps_rebate_rule_id' => $req['cps_rebate_rule_id'],
            'rebate_type'   => $req['rebate_type'][$key],
            'rebate_detail' => serialize($rebate_detail),
            'calc_type'     => $req['calc_type'][$key],
        );
    }
    return $rebate_details;
}
function getCondition() {
    global $smarty;
    $start = $_REQUEST['start'];
    $end = $_REQUEST['end'];

    if (!strtotime($start)) {
        $start = date('Y-m-d');
    }
    if (!strtotime($end)) {
        $end = date('Y-m-d');
    }

    $smarty->assign('start', $start);
    $smarty->assign('end', $end);
    $end_t = date("Y-m-d", strtotime($end) + 24 * 3600);

    $condition = " AND oi.order_time >= '{$start}' AND oi.order_time < '{$end_t}' ";
    if ($_REQUEST['order_status']) {
        $condition .= " AND oi.order_status = '{$_REQUEST['order_status']}' ";
    }

    return $condition;
}
