<?php

/**
 * ECSHOP 配送方式管理程序
 * ============================================================================
 * 版权所有 (C) 2005-2007 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.1
 * ---------------------------------------------
 * $Author: paulgao $
 * $Date: 2007-05-08 19:51:39 +0800 (星期二, 08 五月 2007) $
 * $Id: shipping.php 8519 2007-05-08 11:51:39Z paulgao $
*/

define('IN_ECS', true);

require('includes/init.php');
require('function.php');
$exc = new exchange($ecs->table('shipping'), $db, 'shipping_id', 'shipping_name');

/*------------------------------------------------------ */
//-- 配送方式列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    $support_cod = isset($_REQUEST['support_cod']) ? $_REQUEST['support_cod'] : '';
    $support_no_cod = isset($_REQUEST['support_no_cod']) ? $_REQUEST['support_no_cod'] : '';
    $self_list = $_REQUEST['self_list'];
    $region_id = $_REQUEST['region_id'];
    $parent_id = $_REQUEST['parent_id'];
    $modules = read_modules('../includes/modules/shipping');

    $carriers = $db->getAll("SELECT * FROM {$ecs->table('carrier')}");
    foreach ($carriers as $key => $carrier) {
        $carrier_ids[] = $carrier['carrier_id'];
        $carrier_names[] = $carrier['name'];
    }
    usort($modules, shipping_sort);
    //    pp($modules);
    //   if($self_list){
    //   $sql = "SELECT r.region_name,s.shipping_id,r.region_id,r.parent_id
    //              FROM {$ecs->table('region')} AS r,
    //                   {$ecs->table('area_region')} AS a,
    //                   {$ecs->table('shipping_area')} AS sa,
    //                   {$ecs->table('shipping')} AS s
    //              WHERE a.region_id = r.region_id
    //              AND sa.shipping_area_id = a.shipping_area_id
    //              AND s.shipping_id = sa.shipping_id
    //              AND (s.support_cod = 1 AND s.support_no_cod = 1)
    //             ";
    //   $sql = "SELECT r.region_name,s.shipping_id,s.shipping_id,r.region_id,r.parent_id
    //              FROM {$ecs->table('region')} AS r,
    //                   {$ecs->table('area_region')} AS a,
    //                   {$ecs->table('shipping_area')} AS sa,
    //                   {$ecs->table('shipping')} AS s
    //              WHERE a.region_id = r.region_id
    //              AND sa.shipping_area_id = a.shipping_area_id
    //              AND s.shipping_id = sa.shipping_id
    //              AND (s.support_cod = 1 AND s.support_no_cod = 1)
    //             ";
    //   $sql_province = "SELECT * FROM {$ecs->table('region')} WHERE parent_id = 1";
    //   $self_areas = $db->getAll($sql);
    //   $provinces = $db->getAll($sql_province);
    //
    //    foreach($self_areas as $key => $self){
    //      foreach ($provinces as $province){
    //         if($self['parent_id'] == 1){
    //           $self_areas[$key]['parent_name'] = '直辖市';
    //         }
    //         if($self['parent_id'] == $province['region_id']){
    //           $self_areas[$key]['parent_name'] = $province['region_name'];
    //         }
    //      }
    //    }
    //    foreach ($self_areas as $key=> $self){
    //    	$parents[$self['region_id']][$key] = $self;
    //    }
    //    pp($parents);
    //    foreach($parents as $key=> $parent){
    //    	$num = count($parent);
    //    	$shipping_id = array();
    //    	foreach($parent as $p){
    //    		//$shipping_id .= strval($p['shipping_id']). ',' ;
    //    		array_push($shipping_id,$p['shipping_id']);
    //        $tops[$p['parent_name']][$p['region_id']]['region_id'] = $p['region_id'];
    //        $tops[$p['parent_name']][$p['region_id']]['region_name'] = $p['region_name'];
    //        $tops[$p['parent_name']][$p['region_id']]['shipping_id'] = $shipping_id;
    //        $tops[$p['parent_name']][$p['region_id']]['num'] = $num;
    //    	}
    //    }
    //   pp($tops);
    //   $selfs = $tops;
    //   }

    foreach ($modules as $key =>$module){
        $module['party_name']  = party_mapping($module['party_id']);
        if($support_cod != '' && $support_no_cod != ''){
            if($module['support_cod'] == $support_cod && $module['support_no_cod'] == $support_no_cod){
                $m[$key] = $module;
            }
        }else{
            $m[$key] = $module;
        }
        if($module['support_cod'] == 1 && $module['support_no_cod'] == 1){
            if($module['parent_id'] == 1){
                //$m[$key]['parent_name'] = '直辖市';
                $provinces[$module['parent_id']]['parent_name'] = '直辖市';
                $provinces[$module['parent_id']]['parent_id'] = 1;
                $provinces[$module['parent_id']]['region_name'][$module['region_id']] = $module['region_name'];
            }elseif($module['parent_id']){
                $sql_province = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$module['parent_id']}'";
                $p = $db->getOne($sql_province);
                //$m[$key]['parent_name'] = $p;
                $provinces[$module['parent_id']]['parent_name'] = $p;
                $provinces[$module['parent_id']]['parent_id'] = $module['parent_id'];
                $provinces[$module['parent_id']]['region_name'][$module['region_id']] = $module['region_name'];
            }else{
                $add_module[$key] = $module;
            }
        }
    }
    $modules = $m;
    //  pp($modules);
    if($self_list){
        foreach ($provinces as $key => $province){
            $provinces[$key]['total'] = count($province['region_name']);
        }
        $self_lists = $provinces;
        $modules = $add_module;
    }
    if(isset($parent_id)){
        foreach ($modules as $key =>$module){
            if($module['parent_id'] == $parent_id && ($module['support_cod'] == 1 && $module['support_no_cod'] == 1)){
                $citys[$module['region_name']]['region_name'] = $module['region_name'];
                $citys[$module['region_name']]['region_id'] = $module['region_id'];
                $citys[$module['region_name']]['shipping_id'][]= $module['shipping_id'];
            }
        }
        foreach ($citys as $key => $city){
            $citys[$key]['total'] = count($city['shipping_id']);
        }
        foreach ($provinces as $key => $province){
            if($province['parent_id'] == $parent_id){
                $nav['parent_id'] = $parent_id;
                $nav['parent_name'] = $province['parent_name'];
                $nav['parent_total'] = count($province['region_name']);
            }
        }
        $modules = $add_module;
    }

    if(isset($region_id)){
        foreach ($modules as $key =>$module){
            if($module['region_id'] == $region_id && ($module['support_cod'] == 1 && $module['support_no_cod'] == 1)){
                $selfs[$region_id][$key]=$module;
                $nav['parent_id'] = $module['parent_id'];
                $nav['region_id'] = $module['region_id'];
                $nav['region_name'] = $module['region_name'];
            }
        }
        foreach ($provinces as $key => $province){
            if($province['parent_id'] == $nav['parent_id']){
                $nav['parent_name'] = $province['parent_name'];
                $nav['parent_total'] = count($province['region_name']);
            }
        }
        $modules = $selfs[$region_id];
        $nav['this_total'] = count($modules);
    }
    
    $party_ids = array();
    $party_names = array();
    foreach (party_mapping() as $party_id => $party_name) {
        $party_ids[] = $party_id;
        $party_names[] = "'{$party_name}'";
    }
    
    $smarty->assign('party_ids', join(',', $party_ids));
    $smarty->assign('party_names', join(',', $party_names));
    $smarty->assign('ur_here', $_LANG['04_shipping_list']);
    $smarty->assign('modules', $modules);
    $smarty->assign('carrier_ids', $carrier_ids);
    $smarty->assign('carrier_names', $carrier_names);
    $smarty->assign('add', $add_module);
    $smarty->assign('self_lists', $self_lists);
    $smarty->assign('citys', $citys);
    $smarty->assign('nav',$nav);
    assign_query_info();
    $smarty->display('shipping_list.htm');
}

/*------------------------------------------------------ */
//-- 安装配送方式
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'install')
{
    admin_priv('ship_manage');

    //    $set_modules = true;
    //    include_once(ROOT_PATH . 'includes/modules/shipping/' . $_GET['code'] . '.php');

    /* 检查该配送方式是否已经安装 */
    //    $sql = "SELECT shipping_id FROM " .$ecs->table('shipping'). " WHERE shipping_code = '$_GET[id]'";
    //    $id = $db->getOne($sql);

    //    if ($id > 0)
    //    {
    /*
    该配送方式已经安装过, 将该配送方式的状态设置为 enable
    */
    $db->query("UPDATE " .$ecs->table('shipping'). " SET enabled = 1 WHERE shipping_id = '$_GET[id]' LIMIT 1");
    //    }
    //    else
    //    {
    //        /*
    //         该配送方式没有安装过, 将该配送方式的信息添加到数据库
    //         */
    //        $insure = empty($modules[0]['insure']) ? 0 : $modules[0]['insure'];
    //        $sql = "INSERT INTO " . $ecs->table('shipping') . " (" .
    //                    "shipping_code, shipping_name, shipping_desc, insure, support_cod, enabled" .
    //                ") VALUES (" .
    //                    "'" . addslashes($modules[0]['code']). "', '" . addslashes($_LANG[$modules[0]['code']]) . "', '" .
    //                    addslashes($_LANG[$modules[0]['desc']]) . "', '$insure', '" . intval($modules[0]['cod']) . "', 1)";
    //        $db->query($sql);
    //        $id = $db->insert_Id();
    //    }

    /* 记录管理员操作 */
    admin_log(addslashes($_LANG[$modules[0]['code']]), 'install', 'shipping');
    /* 提示信息 */
    $lnk[] = array('text' => $_LANG['add_shipping_area'], 'href' => 'shipping_area.php?act=add&shipping=' . $id);
    $lnk[] = array('text' => $_LANG['go_back'], 'href' => 'shipping.php?act=list');
    sys_msg(sprintf($_LANG['install_succeess'], $_LANG[$modules[0]['code']]), 0, $lnk);
}

/*------------------------------------------------------ */
//-- 卸载配送方式
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'uninstall')
{
    global $ecs, $_LANG;

    admin_priv('ship_manage');

    /* 获得该配送方式的ID */
    $row = $db->GetRow("SELECT shipping_id, shipping_name FROM " .$ecs->table('shipping'). " WHERE shipping_id='$_GET[id]'");
    $shipping_id = $row['shipping_id'];
    $shipping_name = $row['shipping_name'];
    $db->query("UPDATE {$ecs->table('shipping')} SET enabled = 0 WHERE shipping_id='$_GET[id]'");

    /* 删除 shipping_fee 以及 shipping 表中的数据 */
    if ($row)
    {
        //        $all = $db->getCol("SELECT shipping_area_id FROM " .$ecs->table('shipping_area'). " WHERE shipping_id='$shipping_id'");
        //        $in  = db_create_in(join(',', $all));

        //        $db->query("DELETE FROM " .$ecs->table('area_region'). " WHERE shipping_area_id $in");
        //        $db->query("DELETE FROM " .$ecs->table('shipping_area'). " WHERE shipping_id='$shipping_id'");
        //        $db->query("DELETE FROM " .$ecs->table('shipping'). " WHERE shipping_id='$shipping_id'");

        /* 记录管理员操作 */
        admin_log(addslashes($shipping_id), 'uninstall', 'shipping');

        $lnk[] = array('text' => $_LANG['go_back'], 'href'=>'shipping.php?act=list');
        sys_msg(sprintf($_LANG['uninstall_success'], $shipping_name), 0, $lnk);
    }
}

/*------------------------------------------------------ */
//-- 编辑配送方式名称
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_name')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id  = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 检查名称是否为空 */
    if (empty($val))
    {
        make_json_error($_LANG['no_shipping_name']);
    }

    /* 检查名称是否重复 */
    if (!$exc->is_only('shipping_name', $val, $id, " support_cod = (SELECT support_cod FROM {$ecs->table('shipping')} WHERE shipping_id = $id LIMIT 1) AND support_no_cod = (SELECT support_no_cod FROM {$ecs->table('shipping')} WHERE shipping_id = $id LIMIT 1)"))
    {
        make_json_error($_LANG['repeat_shipping_name']);
    }

    /* 更新支付方式名称 */
    $exc->edit("shipping_name = '$val'", $id);
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 编辑配送方式的组织
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_party_id')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id  = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 检查名称是否为空 */
    if (!array_key_exists($val, party_mapping()))
    {
        make_json_error("请输入正确的组织id");
    }

    /* 更新支付方式名称 */
    $exc->edit("party_id = '$val'", $id);
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 编辑配送方式描述
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_desc')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    $exc->edit("shipping_desc = '$val'", $id);
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 编辑配送方式挂起时的描述
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_disabled_desc')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    $exc->edit("shipping_disabled_desc = '$val'", $id);
    make_json_result(stripcslashes($val));
}
/*------------------------------------------------------ */
//-- 编辑中转地址
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_midway_address')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    $exc->edit("midway_address = '$val'", $id);
    make_json_result(stripcslashes($val));
}
elseif ($_REQUEST['act'] == 'edit_contact_name')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    if ($val != "未填") {
        $exc->edit("contact_name = '$val'", $id);
    }
    make_json_result(stripcslashes($val));
}
elseif ($_REQUEST['act'] == 'edit_contact_email')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    if ($val != "未填") {
        $exc->edit("contact_email = '$val'", $id);
    }

    make_json_result(stripcslashes($val));
}
elseif ($_REQUEST['act'] == 'edit_contact_phone')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    if ($val != "未填") {
        $exc->edit("contact_phone = '$val'", $id);
    }

    make_json_result(stripcslashes($val));
}
elseif ($_REQUEST['act'] == 'edit_self_work_time')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    if ($val != "未填") {
        $exc->edit("self_work_time = '$val'", $id);
    }

    make_json_result(stripcslashes($val));
}
/*------------------------------------------------------ */
//-- 编辑是否支持货到付款
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_support_cod')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    $exc->edit("support_cod = '$val'", $id);
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 编辑是否支持非货到付款
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_support_no_cod')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    $exc->edit("support_no_cod = '$val'", $id);
    make_json_result(stripcslashes($val));
}


/*------------------------------------------------------ */
//-- 编辑是否支持镖局
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_support_biaoju')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    $exc->edit("support_biaoju = '$val'", $id);
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 编辑默认快递公司
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_default_carrier')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);

    /* 更新描述 */
    $exc->edit("default_carrier_id = '$val'", $id);
    make_json_result(stripcslashes($val));
}
/*------------------------------------------------------ */
//-- 修改配送方式保价费
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_insure')
{
    /* 检查权限 */
    check_authz_json('ship_manage');

    /* 取得参数 */
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);
    if (empty($val))
    {
        $val = 0;
    }
    else
    {
        $val = make_semiangle($val); //全角转半角
        if (strpos($val, '%') === false)
        {
            $val = floatval($val);
        }
        else
        {
            $val = floatval($val) . '%';
        }
    }

    /* 检查该插件是否支持保价 */
    //    $set_modules = true;
    //    include_once(ROOT_PATH . 'includes/modules/shipping/' .$id. '.php');
    //    if (isset($modules[0]['insure']) && $modules[0]['insure'] === false)
    //    {
    //        make_json_error($_LANG['not_support_insure']);
    //    }

    /* 更新保价费用 */
    $exc->edit("insure = '$val'", $id);
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 修改显示顺序
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_order')
{
    $id = trim($_POST['id']);
    $val = trim($_POST['val']);
    $exc->edit("shipping_order = '$val'", $id);
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 增加新的货运方式
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add') {
    /* 检查权限 */
    admin_priv('ship_manage');

    $shipping['shipping_code'] = strval($_REQUEST['shipping_code']);
    $shipping['shipping_name'] = strval($_REQUEST['shipping_name']);
    $shipping['shipping_desc'] = strval($_REQUEST['shipping_desc']);
    $shipping['insure'] = strval($_REQUEST['insure']) == '' ? '0' : strval($_REQUEST['insure']);
    $shipping['support_cod'] = strval($_REQUEST['support_cod']);
    $shipping['support_no_cod'] = strval($_REQUEST['support_no_cod']);
    $shipping['support_biaoju'] = strval($_REQUEST['support_biaoju']);
    $shipping['midway_address'] = strval($_REQUEST['midway_address']);
    $shipping['default_carrier_id'] = intval($_REQUEST['default_carrier_id']);
    $shipping['shipping_order'] = strval($_REQUEST['shipping_order']);
    $back_url = $_REQUEST['back_url'];
    $db->autoExecute($ecs->table('shipping'), $shipping, 'INSERT');

    $id = $db->insert_id();

    /* 记录管理员操作 */
    admin_log(addslashes($shipping_name), 'install', 'shipping');
    /* 提示信息 */
    $lnk[] = array('text' => $_LANG['add_shipping_area'], 'href' => "shipping_area.php?act=list&shipping={$id}");
    $lnk[] = array('text' => $_LANG['go_back'], 'href' => $back_url);
    sys_msg(sprintf($_LANG['install_succeess'], $shipping_name), 0, $lnk);
}

/*------------------------------------------------------ */
//-- 删除货运方式
/*------------------------------------------------------ */

//elseif ($_REQUEST['act'] == 'delete') {
/* 检查权限 */
//    admin_priv('ship_manage');

//    $shipping_id = intval($_REQUEST['id']);

//    $sql = "DELETE FROM {$ecs->table('shipping')} WHERE shipping_id = '{$shipping_id}' LIMIT 1";
//    $db->query($sql);

/* 记录管理员操作 */
//    admin_log(addslashes($shipping_id), 'delete', 'shipping');
/* 提示信息 */
//    $lnk[] = array('text' => $_LANG['go_back'], 'href' => 'shipping.php?act=list');
//	sys_msg(sprintf($_LANG['install_succeess'], $shipping_name), 0, $lnk);
//}


function shipping_sort($a, $b) {
    if ($a['shipping_order'] == $b['shipping_order']) {
        if ($a['shipping_id'] < $b['shipping_id'])
        return -1;
        else
        return 1;
    }
    if ($a['shipping_order'] < $b['shipping_order']) {
        return -1;
    } else {
        return 1;
    }
}
?>