<?php
/**
 * 设计打印快递面单的模板
 * $Id: carrier_bill_template.php 25513 2010-12-28 10:13:35Z yzhang $
 * @author yzhang@leqee.com
 * @copyright 2010.12 leqee.com
 */

define('IN_ECS', true);
require ('includes/init.php');
admin_priv('carrier_bill_template');
require ("function.php");
include_once ('includes/lib_order.php');

// 请求
$request = isset($_REQUEST['request']) && in_array($_REQUEST['request'], array(
    'ajax'
)) ? $_REQUEST['request'] : null;

$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array(
    'design', 
    'delete_cbt', 
    'insert_cbt', 
    'preview', 
    'update_cbt', 
    'updatePF', 
    'updateC', 
    'clone_cbt', 
    'cbt_log'
)) ? $_REQUEST['act'] : null;

$cbt_id = isset($_REQUEST['cbt_id']) ? intval($_REQUEST['cbt_id']) : 0;

/*
 * 处理ajax请求
 */
if ($request == 'ajax') {
    switch ($act) {
        case 'design':
            $cbt_data = isset($_REQUEST['cbt_data']) ? $_REQUEST['cbt_data'] : '';
            //$cbt_data = ZY_stripSlashes($cbt_data);
            // {{{ stripslashes
            $o = array(
                $cbt_data
            );
            array_walk_recursive($o, create_function('&$item', '$item = stripslashes($item);'));
            $cbt_data = $o[0];
            // }}}
            $data = array(
                'cbt_data' => $cbt_data
            );
            //if ($cbt_id) {
            $dr = update_cbt($cbt_id, $data);
            //} else {
            //    $dr = insert_cbt($data);
            //}
            $r = array(
                'msg' => $dr > 0 ? '数据保存成功。' : '数据保存失败。', 
                'code' => intval(!($dr > 0))
            );
            echo json_encode($r);
            // {{{ 记录下操作日志
            if ($dr > 0) {
                $logdata = array(
                    'cbt_id' => $cbt_id, 
                    'cbt_action' => $act, 
                    'admin_id' => $_SESSION['admin_id'], 
                    'admin_name' => $_SESSION['admin_name'], 
                    'cbt_data' => $cbt_data
                );
                cbt_log($logdata);
            }
            // }}}
            break;
        
        case 'update_cbt':
            $cbt_title = isset($_REQUEST['cbt_title']) ? $_REQUEST['cbt_title'] : '';
            //$cbt_title = ZY_stripSlashes($cbt_title);
            // {{{ stripslashes
            $o = array(
                $cbt_title
            );
            array_walk_recursive($o, create_function('&$item', '$item = stripslashes($item);'));
            $cbt_title = $o[0];
            // }}}
            $data = array(
                'cbt_title' => $cbt_title
            );
            //if ($cbt_id) {
            $dr = update_cbt($cbt_id, $data);
            //} else {
            //    $dr = insert_cbt($data);
            //}
            $r = array(
                'msg' => $dr > 0 ? '数据保存成功。' : '数据保存失败。', 
                'code' => intval(!($dr > 0))
            );
            echo json_encode($r);
            break;
        
        case 'delete_cbt':
            $cbt_id = isset($_REQUEST['cbt_id']) ? intval($_REQUEST['cbt_id']) : '';
            $dr = delete_cbt($cbt_id);
            $r = array(
                'msg' => $dr > 0 ? '数据删除成功。' : '数据删除失败。', 
                'code' => intval(!($dr > 0))
            );
            echo json_encode($r);
            break;
        
        case 'insert_cbt':
            $data = array(
                'cbt_data' => '[]'
            );
            $dr = insert_cbt($data);
            $r = array(
                'msg' => $dr > 0 ? '数据保存成功。' : '数据保存失败。', 
                'code' => intval(!($dr > 0)), 
                'cbt_id' => $dr > 0 ? $dr : 0
            );
            echo json_encode($r);
            // {{{ 记录下操作日志
            if ($dr > 0) {
                $logdata = array(
                    'cbt_id' => $dr, 
                    'cbt_action' => $act, 
                    'admin_id' => $_SESSION['admin_id'], 
                    'admin_name' => $_SESSION['admin_name'], 
                    'cbt_data' => '[]'
                );
                cbt_log($logdata);
            }
            // }}}
            break;
        
        // 复制一个模板
        case 'clone_cbt':
            $getone_cbt = getone_cbt($cbt_id);
            $data = array(
                'cbt_data' => $getone_cbt['cbt_data']
            );
            $dr = insert_cbt($data);
            $r = array(
                'msg' => $dr > 0 ? '数据保存成功。' : '数据保存失败。', 
                'code' => intval(!($dr > 0)), 
                'cbt_id' => $dr > 0 ? $dr : 0, 
                'cbt_data' => htmlspecialchars($getone_cbt['cbt_data'])
            );
            echo json_encode($r);
            // {{{ 记录下操作日志
            if ($dr > 0) {
                $logdata = array(
                    'cbt_id' => $dr, 
                    'cbt_action' => $act, 
                    'admin_id' => $_SESSION['admin_id'], 
                    'admin_name' => $_SESSION['admin_name'], 
                    'cbt_data' => '[]'
                );
                cbt_log($logdata);
            }
            // }}}
            break;
        
        // 更新 party_id 和 facility_id
        case 'updatePF':
            $party_id = isset($_REQUEST['party_id']) ? intval($_REQUEST['party_id']) : 0;
            $facility_id = isset($_REQUEST['facility_id']) ? intval($_REQUEST['facility_id']) : 0;
            $data = array(
                'romeo_party_id' => $party_id, 
                'facility_id' => $facility_id
            );
            $dr = update_cbt($cbt_id, $data);
            $r = array(
                'msg' => $dr > 0 ? '数据保存成功。' : '数据保存失败。', 
                'code' => intval(!($dr > 0))
            );
            echo json_encode($r);
            break;
        
        // 更新 carrier
        case 'updateC':
            $carrier_id = isset($_REQUEST['carrier_id']) ? intval($_REQUEST['carrier_id']) : 0;
            $data = array(
                'carrier_id' => $carrier_id
            );
            $dr = update_cbt($cbt_id, $data);
            $r = array(
                'msg' => $dr > 0 ? '数据保存成功。' : '数据保存失败。', 
                'code' => intval(!($dr > 0))
            );
            echo json_encode($r);
            break;
    }
    
    die();
} else {
    if ($act == 'design') {
        if ($cbt_id > 0) {
            $cbt = getone_cbt($cbt_id);
            $smarty->assign('cbt', $cbt);
            $cbt_data = json_decode($cbt['cbt_data']);
            //$cbt_data = null === $cbt_data ? '{}' : $cbt_data;
            $smarty->assign('cbt_data', $cbt_data);
        }
    } elseif ($act == 'preview') {
        if ($cbt_id > 0) {
            $cbt = getone_cbt($cbt_id);
            $smarty->assign('cbt', $cbt);
            $cbt_data = json_decode($cbt['cbt_data']);
            //$cbt_data = null === $cbt_data ? '{}' : $cbt_data;
            $smarty->assign('cbt_data', $cbt_data);
        }
    } elseif ($act == 'cbt_log') {
        // 查看操作日志
        $sql = "SELECT count(*) as cc FROM ecshop.carrier_bill_template_log WHERE cbt_id = '$cbt_id' ";
        $total = $GLOBALS['db']->getOne($sql);
        //
        $sql = "SELECT * FROM ecshop.carrier_bill_template_log WHERE cbt_id = '$cbt_id' ORDER BY log_id DESC ";
        $lpage = isset($_GET['lpage']) && $_GET['lpage'] > 0 ? intval($_GET['lpage']) : 1;
        $loffset = 5;
        $lfrom = ($lpage - 1) * $loffset;
        $sql .= " LIMIT $loffset OFFSET $lfrom ";
        $loglist = $GLOBALS['db']->getAll($sql);
        
        $s = '<!doctype html><html dir="ltr" lang="zh-CN" ><head><meta charset="utf-8"/>';
        $s .= '<style type="text/css">body{margin:6px;font-family: "Microsoft YaHei", monospace;}td,div,span,input,a,th{font-size:12px;}</style><base target="_self" />';
        $s .= '</head><body>';
        $s .= '<h5 style="margin-top: 6px;margin-bottom: 6px;color: blue;">当前模板操作日志：</h5>';
        $s .= '<table border="1" style="border-collapse:collapse;border-color:#999;">';
        $s .= "<tr><th>log_id</th><th>操作</th><th>操作人</th><th>日志时间</th><th>模板格式数据（JSON）</th></tr>";
        foreach ($loglist as $v) {
            $s .= "<tr><td>{$v['log_id']}</td><td>{$v['cbt_action']}</td>";
            $s .= "<td>{$v['admin_name']}</td><td>{$v['log_time']}</td>";
            $s .= "<td><div style=\"height:60px;width: 350px;overflow:auto;\">{$v['cbt_data']}</div></td></tr>";
        }
        $s .= '</table>';
        
        //lpager($total, $offset = 9, $page = null, $url = null, $back = 3, $label = 'page')
        $lpager = Pager($total, $loffset, $page = null, $url = null, $back = 3, $label = 'lpage');
        $s .= "<div>$lpager</div>";
        $s .= '</body></html>';
        echo $s;
        die();
    }
}

$getlist_cbt = getlist_cbt();
$get_carrier_list = get_carrier_list();
foreach ($getlist_cbt as $k => $v) {
    $getlist_cbt[$k]['cbt_data'] = htmlspecialchars($v['cbt_data']);
    // {{{
    $tmp = array();
    $tmp[] = '<option value="">--选择--</option>';
    foreach ($get_carrier_list as $vv) {
        $tmp[] = '<option value="' . $vv['carrier_id'] . '"' . ($v['carrier_id'] == $vv['carrier_id'] ? ' selected' : '') . '>' . $vv['name'] . '</option>';
    }
    $getlist_cbt[$k]['carrier'] = join("", $tmp);

    // }}}
}
$smarty->assign('list', $getlist_cbt);

// {{{
$tmp = array();
$tmp[] = '<option value="">--选择--</option>';
foreach ($get_carrier_list as $vv) {
    $tmp[] = '<option value="' . $vv['carrier_id'] . '"' . ($v['carrier_id'] == $vv['carrier_id'] ? ' selected' : '') . '>' . $vv['name'] . '</option>';
}
$carrier_opt = join("", $tmp);
$smarty->assign('carrier_opt', $carrier_opt);
// }}}


// {{{ 组织的选项
$party_options_list = party_options_list();
$pol = array();
foreach ($party_options_list as $k => $v) {
    $pol[] = '<option value="' . $k . '">' . $v . '</option>';
}
$smarty->assign('pol', join("", $pol));
$smarty->assign('party_options_list', $party_options_list);
//$party_get_all_list = party_get_all_list();
//p($party_get_all_list);


// 组织和仓库
$dts = array();
foreach ($party_options_list as $k => $v) {
    $af = get_available_facility($k);
    $dts[$k] = array(
        'value' => $v, 
        'facility' => $af
    );
}
$smarty->assign('dts', json_encode($dts));
// }}}
// 组织-仓库-配送方式-tpl
// 复制模板功能


// 显示界面
$smarty->display('oukooext/carrier_bill_template.htm');

/**
 * 取得列表
 */
function getlist_cbt()
{
    $db = $GLOBALS['db'];
    $r = array();
    $sql = "SELECT * FROM ecshop.carrier_bill_template WHERE 1 ";
    $query = $db->query($sql);
    if ($query !== false) {
        while ($row = mysql_fetch_assoc($query)) {
            $r[] = $row;
        }
    }
    return $r;
}

/**
 * 取得一个
 */
function getone_cbt($cbt_id)
{
    $db = $GLOBALS['db'];
    $row = array();
    $sql = "SELECT * FROM ecshop.carrier_bill_template WHERE cbt_id = '$cbt_id' ";
    $query = $db->query($sql);
    if ($query !== false) {
        while ($row = mysql_fetch_assoc($query)) {
            return $row;
        }
    }
    return $row;
}

/**
 * 删除一个
 */
function delete_cbt($cbt_id)
{
    $db = $GLOBALS['db'];
    $sql = "SELECT * FROM ecshop.carrier_bill_template WHERE cbt_id = '$cbt_id' ";
    $row = $db->getRow($sql);
    $sql = "DELETE FROM ecshop.carrier_bill_template WHERE cbt_id = '$cbt_id' LIMIT 1 ";
    $query = $db->query($sql);
    if ($query) {
        $r = $db->affected_rows($query);
        // {{{ 记录下操作日志
        if ($r > 0) {
            $logdata = array(
                'cbt_id' => $r, 
                'cbt_action' => $_REQUEST['act'], 
                'admin_id' => $_SESSION['admin_id'], 
                'admin_name' => $_SESSION['admin_name'], 
                'cbt_data' => $row['cbt_data']
            );
            cbt_log($logdata);
        }
        // }}}
        return $r;
    }
    return -1000001;
}

/**
 * 更新一个
 */
function update_cbt($cbt_id, array $data)
{
    $db = $GLOBALS['db'];
    $sql = "SELECT * FROM ecshop.carrier_bill_template WHERE cbt_id = '$cbt_id' ";
    $query = $db->query($sql);
    if ($query !== false) {
        while ($row = mysql_fetch_assoc($query)) {
            $sqladd = array();
            foreach ($data as $fd => $val) {
                $sqladd[] = " $fd = '" . $db->quote($val) . "' ";
            }
            if (!empty($sqladd)) {
                $sql = "UPDATE ecshop.carrier_bill_template SET " . join(", ", $sqladd) . " WHERE cbt_id = '$cbt_id' LIMIT 1 ";
                //p($sql, $sqladd, $data);die();
                $r = $db->query($sql);
                if ($r) {
                    return $db->affected_rows($r);
                }
                return -1000001;
            }
            return -1000002;
        }
        return -1000003;
    }
    return -1000004;
}

/**
 * 插入一个
 */
function insert_cbt(array $data)
{
    $db = $GLOBALS['db'];
    $sqladd = array();
    foreach ($data as $fd => $val) {
        $sqladd[] = " $fd = '" . $db->quote($val) . "' ";
    }
    if ($sqladd) {
        $sql = "INSERT INTO ecshop.carrier_bill_template SET " . join(", ", $sqladd) . " ";
        $r = $db->query($sql);
        if ($r) {
            return $db->insert_id();
        }
        return -1000001;
    }
    return -1000002;
}

/**
 * 记录操作日志
 * @param array $data
 */
function cbt_log(array $data)
{
    $db = $GLOBALS['db'];
    $sqladd = array();
    foreach ($data as $fd => $val) {
        $sqladd[] = " $fd = '" . $db->quote($val) . "' ";
    }
    if ($sqladd) {
        $sql = "INSERT INTO ecshop.carrier_bill_template_log SET " . join(", ", $sqladd) . " ";
        $r = $db->query($sql);
        if ($r) {
            return $db->insert_id();
        }
        return -1000001;
    }
    return -1000002;
}
