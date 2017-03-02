<?php

/**
 * 短信模版管理
 */

define('IN_ECS', true);

require('includes/init.php');
admin_priv('msg_template');

/*------------------------------------------------------ */
//-- 模版列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    // 组织
    $party_id = isset($_REQUEST['party_id']) && in_array($_REQUEST['party_id'], array_keys(party_mapping())) 
        ? $_REQUEST['party_id'] 
        : key(party_mapping()) ;
        
    /* 包含插件语言项 */
    $sql = "SELECT code FROM ".$ecs->table('plugins');
    $rs = $db->query($sql);
    while ($row = $db->FetchRow($rs))
    {
        /* 取得语言项 */
        if (file_exists('../plugins/'.$row['code'].'/languages/common_'.$_CFG['lang'].'.php'))
        {
            include_once(ROOT_PATH.'plugins/'.$row['code'].'/languages/common_'.$_CFG['lang'].'.php');
        }
    }
    
    /* 获得所有邮件模板 */
    $sql = "SELECT template_id, template_code FROM " .$ecs->table('msg_templates') ." WHERE ". party_sql('party_id', $party_id);
    $res = $db->query($sql);
    $cur = null;

    while ($row = $db->FetchRow($res))
    {
        if ($cur == null)
        {
            $cur = $row['template_id'];
        }

        $len = strlen($_LANG[$row['template_code']]);
        $templates[$row['template_id']] = $len < 18 ?
            $_LANG[$row['template_code']].str_repeat('&nbsp;', (18-$len)/2) ." [$row[template_code]]" :
            $_LANG[$row['template_code']] . " [$row[template_code]]";
    }

    assign_query_info();

    $smarty->assign('ur_here',      $_LANG['msg_template_manage']);
    $smarty->assign('templates',    $templates);
    $smarty->assign('template',     load_template($cur));
    $smarty->assign('party_list',   party_mapping());  // 组织列表
    $smarty->display('msg_template.htm');
}

/*------------------------------------------------------ */
//-- 载入指定模版
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'loat_template')
{
    $tpl = intval($_GET['tpl']);

    make_json_result(load_template($tpl));
}

/*------------------------------------------------------ */
//-- 保存模板内容
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'save_template')
{
    if (empty($_POST['subject']))
    {
        make_json_error('短信标题不能为空');
    }
    else
    {
        $subject = trim($_POST['subject']);
    }

    if (empty($_POST['content']))
    {
        make_json_result('短信内容不能为空');
    }
    else
    {
        $content = trim($_POST['content']);
    }

    $server_name   = $_POST['server_name'];
    $is_send = $_POST['is_send'];
    $tpl_id = intval($_POST['tpl']);
	admin_log("修改短信模板 template_id=$tpl_id", "", "");
    $sql = "UPDATE " .$ecs->table('msg_templates'). " SET ".
                "template_subject = '" .str_replace('\\\'\\\'', '\\\'', $subject). "', ".
                "template_content = '" .str_replace('\\\'\\\'', '\\\'', $content). "', ".
                "server_name = '$server_name', ".
                "is_send = '$is_send', ".
                "last_modify = '" .time(). "' ".
            "WHERE template_id='$tpl_id'";
		
    if ($db->query($sql, "SILENT"))
    {
        make_json_result('', '短信模板保存成功');
    }
    else
    {
        make_json_error('短信模板保存失败' ."\n". $GLOBALS['db']->error());
    }
}

/**
 * 加载指定的模板内容
 *
 * @access  public
 * @param   string  $temp   短信模板的ID
 * @return  array
 */
function load_template($temp_id)
{
    $sql = "SELECT template_subject, template_content, server_name, is_send ".
            "FROM " .$GLOBALS['ecs']->table('msg_templates'). " WHERE template_id='$temp_id'";
    $row = $GLOBALS['db']->GetRow($sql);

    return $row;
}

?>