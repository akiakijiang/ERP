<?php

/**
 * ECSHOP 插件管理程序
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
 * $Date: 2007-04-19 18:11:35 +0800 (星期四, 19 四月 2007) $
 * $Id: plugins.php 8272 2007-04-19 10:11:35Z paulgao $
*/

define('IN_ECS', true);

require('includes/init.php');

$plugins_dir = ROOT_PATH.'plugins/';

/*------------------------------------------------------ */
//-- 插件列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    $modules        = array();
    $set_modules    = true;

    /* 遍历plugins目录，读取可用的插件 */
    $folder = opendir($plugins_dir);

    while ($dir = readdir($folder))
    {
        if (file_exists($plugins_dir.$dir.'/'.$dir.'_inc.php') && $dir != 'group_buy')
        {
            include_once($plugins_dir.$dir.'/'.$dir.'_inc.php');
            /* 读出常规语言项 */
            if (file_exists($plugins_dir.$dir.'/languages/common_'.$_CFG['lang'].'.php'))
            {
                include_once($plugins_dir.$dir.'/languages/common_'.$_CFG['lang'].'.php');
            }
        }
    }

    /* 遍历插件信息的数组，转换插件名称，描述并检查是否已经安装 */
    foreach ($modules AS $key => $val)
    {
        $modules[$key]['name']      = $_LANG[$val['code']];
        $modules[$key]['desc']      = $_LANG[$val['desc']];
        $modules[$key]['version']   = number_format($val['version'], 1);
        $modules[$key]['setup']     = -1;   // -1 表示没有安装，0 已经安装， 1 可以升级

        /* 检查该插件是否已经安装 */
        $sql = "SELECT version FROM ".$ecs->table('plugins'). " WHERE code='$val[code]'";
        if (($ver = $db->getOne($sql)) != false)
        {
            /* 该插件已经安装，检查版本是否相符 */
            $modules[$key]['setup'] = ($ver < floatval($val['version'])) ? 1 : 0;
        }
    }
    assign_query_info();

    $smarty->assign('modules',  $modules);
    $smarty->display('plugins.htm');
}

/*------------------------------------------------------ */
//-- 安装/卸载插件
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'install' || $_REQUEST['act'] == 'uninstall' ||
$_REQUEST['act'] == 'upgrade' || $_REQUEST['act'] == 'remove')
{
    /* 检查插件文件是否存在 */
    $plugins_file   = $plugins_dir.$_REQUEST['code'].'/'.$_REQUEST['code'].'_inc.php';
    $link_back[]    = array('text' => $_LANG['go_back'], 'href'=>'plugins.php?act=list');

    if (!file_exists($plugins_file))
    {
        /* 文件不存在 */
        sys_msg($_LANG['plugin_not_exists'], 0, $link_back);
    }
    else
    {
        clear_cache_files();

        /* 插件文件存在，包含该插件 */
        include_once($plugins_file);

        /* 检查类是否存在 */
        if (class_exists($_REQUEST['code']))
        {
            $plugin = new $_REQUEST['code'];

            if ($_REQUEST['act'] == 'install')
            {
                /* 安装插件 */
                if ($plugin->install())
                {
                    /* 记录插件信息 */
                    $db->query("INSERT INTO ".$ecs->table('plugins')."(code, version, library, assign, install_date)".
                                "VALUES('".$plugin->code."','".floatval($plugin->version)."','".$plugin->library."','".
                                            $plugin->assign."',".time().")");

                    /* 安装成功 */
                    $msg = $_LANG['install_success'].
                        "<script type='text/javascript'>window.top.frames['menu-frame'].document.location.reload();</script>";

                    sys_msg($msg, 1, $link_back);
                }
                else
                {
                    sys_msg($plugin->error, 0, $link_back);
                }
            }
            elseif ($_REQUEST['act'] == 'uninstall')
            {
                /* 卸载插件 */
                if ($plugin->uninstall())
                {
                    /* 删除插件信息 */
                    $sql = 'DELETE FROM ' .$ecs->table('plugins'). " WHERE code='".$plugin->code."'";
                    $db->query($sql);

                    /* 卸载成功 */
                    sys_msg($_LANG['uninstall_success'], 1, $link_back);
                }
                else
                {
                    sys_msg($plugin->error, 0, $link_back);
                }
            }
            /* 删除插件的商品 */
            elseif ($_REQUEST['act'] == 'remove')
            {
                /* 确认删除商品 */
                if ($_REQUEST['confirm'] == 1 && isset($_REQUEST['code']))
                {
                    $sql = 'DELETE FROM ' .$ecs->table('goods').
                           " WHERE is_real = 0 AND extension_code = '$_REQUEST[code]'";
                    $db->query($sql);
                }

                /* 删除插件信息 */
                $sql = 'DELETE FROM ' .$ecs->table('plugins'). " WHERE code='".$plugin->code."'";
                $db->query($sql);

                /* 卸载成功 */
                sys_msg($_LANG['uninstall_success'], 1, $link_back);
            }
            else
            {
                /* 升级插件 */
                if ($plugin->upgrade())
                {
                    /* 更新插件信息 */
                    $db->query("UPDATE ".$ecs->table('plugins').
                                " SET version='" .$plugin->version. "', install_date='".time()."' ".
                                " WHERE code='".$plugin->code."'");

                    sys_msg($_LANG['upgrade_success'], 1, $link_back);
                }
                else
                {
                    sys_msg($plugin->error, 0, $link_back);
                }
            }
        }
        else
        {
            /* 插件类不存在 */
            sys_msg($_LANG['class_not_exists'], 0, $link_back);
        }
    }
}

?>