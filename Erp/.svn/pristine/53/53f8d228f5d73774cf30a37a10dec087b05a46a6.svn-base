<?php

/**
 * 设置
 */

define('IN_ECS', true);

/* 代码 */
require('includes/init.php');

/*------------------------------------------------------ */
//-- 列表编辑 ?act=list_edit
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list_edit')
{
    /* 检查权限 */
    admin_priv('shop_config');

    /* 取出全部数据：分组和变量 */
    $sql = "SELECT * FROM " . $ecs->table('shop_config') . " WHERE type<>'hidden' ORDER BY id";
    $item_list = $db->getAll($sql);

    /* 整理数据 */
    $group_list = array();
    foreach ($item_list AS $key => $item)
    {
        $pid = $item['parent_id'];
        $item['name'] = $item['title'] ? $item['title'] : ($_LANG['cfg_name'][$item['code']] ? $_LANG['cfg_name'][$item['code']] : $item['code']);
        $item['desc'] = isset($_LANG['cfg_desc'][$item['code']]) ? $_LANG['cfg_desc'][$item['code']] : '';

        if ($pid == 0)
        {
            /* 分组 */
            if ($item['type'] == 'group')
            {
                $group_list[$item['id']] = $item;
            }
        }
        else
        {
            /* 变量 */
            if (isset($group_list[$pid]))
            {
                // 为了屏蔽电话的配置不要因为业务变化而修改数据库，故特殊处理其数据
                if (strpos($item['code'], 'callcenter_exclude') !== false) {
                    switch ($item['code']) {
                        case "callcenter_exclude_facility":
                        	require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
                            $item['store_options'] = facility_list();
                            break;
                        case "callcenter_exclude_party":
                            $item['store_options'] = party_mapping();
                            break;
                        case "callcenter_exclude_province":
                            $sql = "SELECT region_id, region_name 
                                FROM ecs_region
                                WHERE region_type = 1 AND parent_id = 1";
                            $res = $db->getAll($sql);
                            $region_mapping = array();
                            foreach ($res as $r) {
                                $region_mapping[$r['region_id']] = $r['region_name'];
                            }
                            $item['store_options'] = $region_mapping;
                            break;
                        case "callcenter_exclude_shipping":
                            $sql = "SELECT carrier_id, name 
                                FROM ecs_carrier ";
                            $res = $db->getAll($sql);
                            $shipping_mapping = array();
                            foreach ($res as $r) {
                                $shipping_mapping[$r['carrier_id']] = $r['name'];
                            }
                            $item['store_options'] = $shipping_mapping;
                            break;
                        case "callcenter_exclude_channel":                            
                            $sub_outer_type_mapping = $_CFG['adminvars']['sub_outer_type']['taobao'];
                            $sub_outer_type_mapping['ouku'] = '欧酷官网';
                            $item['store_options'] = $sub_outer_type_mapping;
                            break;
                        default:
                            break;
                    }
                    $item['value'] = explode(",", $item['value']);
                    $item['display_options'] = $item['store_options'];
                } else if ($item['store_range']) {
                    $item['store_options'] = explode(',', $item['store_range']);

                    foreach ($item['store_options'] AS $k => $v)
                    {
                        $item['display_options'][$k] = $_LANG['cfg_range'][$item['code']][$v];
                    }
                }
                $group_list[$pid]['vars'][] = $item;
            }
        }

    }

    /* 可选语言 */
    $dir = opendir('../languages');
    $lang_list = array();
    while (@$file = readdir($dir))
    {
        if ($file != '.' && $file != '..' &&  $file != '.svn' && $file != '_svn' && is_dir('../languages/' .$file))
        {
            $lang_list[] = $file;
        }
    }
    @closedir($dir);

    $smarty->assign('ur_here', $_LANG['02_shop_config']);
    $smarty->assign('group_list', $group_list);
    $smarty->assign('countries', get_regions());
    if ($_CFG['shop_country'] > 0)
    {
        $smarty->assign('provinces', get_regions(1, $_CFG['shop_country']));
        if ($_CFG['shop_province'])
        {
            $smarty->assign('cities', get_regions(2, $_CFG['shop_province']));
        }
    }
    $smarty->assign('cfg', $_CFG);
    $smarty->assign('lang_list', $lang_list);

    assign_query_info();
    $smarty->display('shop_config.htm');
}

/*------------------------------------------------------ */
//-- 提交   ?act=post
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'post')
{
    /* 检查权限 */
    admin_priv('shop_config');

    /* 允许上传的文件类型 */
    $allow_file_types = '|GIF|JPG|PNG|BMP|SWF|DOC|XLS|PPT|MID|WAV|ZIP|RAR|PDF|CHM|RM|TXT|CERT|';

    /* 保存变量值 */
    $count = count($_POST['value']);
    foreach ($_POST['value'] AS $key => $val)
    {
        if (is_array($val)) {
            $val = implode(",", $val);
        }
        $sql = "UPDATE " . $ecs->table('shop_config') . " SET value = '" . trim($val) . "' WHERE id = '" . $key . "'";
        $db->query($sql);
    }
    foreach ($_POST['checkbox'] as $key => $val) {
        if (!isset($_POST['value'][$val])) {
            $sql = "UPDATE " . $ecs->table('shop_config') . " SET value = '' WHERE id = '{$val}'";
            $db->query($sql);
        }
    }

    /* 新增退回留言模板 */
    if (trim($_POST['bjcomment_rejectreply_area_name']) && trim($_POST['bjcomment_rejectreply_area_value'])) {
    	$bjcomment_rejectreply_area_name = trim($_POST['bjcomment_rejectreply_area_name']);
    	$bjcomment_rejectreply_area_value = trim($_POST['bjcomment_rejectreply_area_value']);
    	$insert_id = $db->getRow("SELECT parent_id, code FROM {$ecs->table('shop_config')} WHERE code LIKE '%bjcomment_reject_reply_%' ORDER BY id DESC LIMIT 1 ");
    	$parent_id = $insert_id['parent_id'];
    	$next_id = substr($insert_id['code'], 23)+1;
    	$sql = sprintf("INSERT INTO {$ecs->table('shop_config')} (parent_id, code, type, value, title) VALUES ($parent_id, '%s', 'textarea', '%s', '%s') ", 
    						'bjcomment_reject_reply_'.$next_id, $bjcomment_rejectreply_area_value, $bjcomment_rejectreply_area_name);
    	$db->query($sql);
    }
    
    /* 处理上传文件 */
    $file_var_list = array();
    $sql = "SELECT * FROM " . $ecs->table('shop_config') . " WHERE parent_id > 0 AND type = 'file'";
    $res = $db->query($sql);
    while ($row = $db->fetchRow($res))
    {
        $file_var_list[$row['code']] = $row;
    }
    
    /* 网店信息编辑，因涉及文件操作屏蔽 by ychen 2009/07/31 */
/*
    foreach ($_FILES AS $code => $file)
    {
        // 判断用户是否选择了文件 
        if ((isset($file['error']) && $file['error'] == 0) || (!isset($file['error']) && $file['tmp_name'] != 'none'))
        {
            // 检查上传的文件类型是否合法 
            if (!check_file_type($file['tmp_name'], $file['name'], $allow_file_types))
            {
                sys_msg(sprintf($_LANG['msg_invalid_file'], $file['name']));
            }
            else
            {
                // 判断是否上传成功 
                $file_name = str_replace('{$template}', $_CFG['template'], $file_var_list[$code]['store_dir']) . $file['name'];

                if (move_uploaded_file($file['tmp_name'], $file_name))
                {
                    $sql = "UPDATE " . $ecs->table('shop_config') . " SET value = '$file_name' WHERE code = '$code'";
                    $db->query($sql);
                }
                else
                {
                    sys_msg(sprintf($_LANG['msg_upload_failed'], $file['name'], $file_var_list[$code]['store_dir']));
                }
            }
        }
    }
*/
    /* 记录日志 */
    admin_log('', 'edit', 'shop_config');

    /* 清除缓存 */
    clear_all_files();
/*
    $_CFG = load_config();

    $shop_country   = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='$_CFG[shop_country]'");
    $shop_province  = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='$_CFG[shop_province]'");
    $shop_city      = $db->getOne("SELECT region_name FROM ".$ecs->table('region')." WHERE region_id='$_CFG[shop_city]'");

    $spt = '<script type="text/javascript" src="http://api.ecshop.com/record.php?';
    $spt .= "url=" .urlencode($ecs->url());
    $spt .= "&shop_name=" .urlencode($_CFG['shop_name']);
    $spt .= "&shop_title=".urlencode($_CFG['shop_title']);
    $spt .= "&shop_desc=" .urlencode($_CFG['shop_desc']);
    $spt .= "&shop_keywords=" .urlencode($_CFG['shop_keywords']);
    $spt .= "&country=".urlencode($shop_country)."&province=".urlencode($shop_province)."&city=".urlencode($shop_city);
    $spt .= "&address=" .urlencode($_CFG['shop_address']);
    $spt .= "&qq=$_CFG[qq]&ww=$_CFG[ww]&ym=$_CFG[ym]&msn=$_CFG[msn]";
    $spt .= "&email=$_CFG[service_email]&phone=$_CFG[service_phone]&icp=".urlencode($_CFG['icp_number']);
    $spt .= "&version=".VERSION."&language=$_CFG[lang]";
    $spt .= '"></script>';
*/
    sys_msg($_LANG['save_success'].$spt, 0, array(array('href' => 'shop_config.php?act=list_edit', 'text' => $_LANG['02_shop_config'])));
}

/*------------------------------------------------------ */
//-- 发送测试邮件
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'send_test_email')
{
    /* 检查权限 */
    check_authz_json('shop_config');

    /* 取得参数 */
    $email          = trim($_POST['email']);
    $smtp_host      = trim($_POST['smtp_host']);
    $smtp_port      = trim($_POST['smtp_port']);
    $smtp_user      = trim($_POST['smtp_user']);
    $smtp_pass      = trim($_POST['smtp_pass']);
    $reply_email    = trim($_POST['reply_email']);
    $mail_charset   = trim($_POST['mail_charset']);

    /* 更新配置 */
    $_CFG['smtp_host']    = $smtp_host;
    $_CFG['smtp_port']    = $smtp_port;
    $_CFG['smtp_user']    = $smtp_user;
    $_CFG['smtp_pass']    = $smtp_pass;
    $_CFG['smtp_mail']    = $reply_email;
    $_CFG['mail_charset'] = $mail_charset;

    if (send_mail('', $email, $_LANG['test_mail_title'], $_LANG['cfg_name']['email_content'], 0))
    {
        make_json_result('', $_LANG['sendemail_success'] . $email);
    }
    else
    {
        make_json_error(join("\n", $err->_message));
    }
}
/**
 * 删除退回留言模板
 */
elseif ($_REQUEST['act'] == 'delete_bjcomment_rejectreply')
{
    /* 检查权限 */
    admin_priv('shop_config');
    $sql = "UPDATE {$ecs->table('shop_config')} SET type = 'hidden' WHERE id = {$_REQUEST['id']} AND code LIKE '%bjcomment_reject_reply_%' ";
    $db->query($sql);
    sys_msg($_LANG['save_success'].$spt, 0, array(array('href' => 'shop_config.php?act=list_edit', 'text' => $_LANG['02_shop_config'])));
}

?>