<?php

/**
 * ECSHOP 会员管理程序
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
 * $Author: wj $
 * $Date: 2007-05-24 15:53:51 +0800 (星期四, 24 五月 2007) $
 * $Id: users.php 8725 2007-05-24 07:53:51Z wj $
*/

define('IN_ECS', true);

require('includes/init.php');
admin_priv('users_manage');

/*------------------------------------------------------ */
//-- 用户帐号列表
/*------------------------------------------------------ */

if ($_REQUEST['act'] == 'list')
{
    $sql = "SELECT rank_id, rank_name, min_points FROM ".$ecs->table('user_rank')." ORDER BY min_points ASC ";
    $rs = $db->query($sql);

    $ranks = array();
    while ($row = $db->FetchRow($rs))
    {
        $ranks[$row['rank_id']] = $row['rank_name'];
    }

    $smarty->assign('user_ranks',   $ranks);
    $smarty->assign('ur_here',      $_LANG['03_users_list']);
    $smarty->assign('action_link',  array('text' => $_LANG['04_users_add'], 'href'=>'users.php?act=add'));

    $user_list = user_list();

    $smarty->assign('user_list',    $user_list['user_list']);
    $smarty->assign('filter',       $user_list['filter']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('page_count',   $user_list['page_count']);
    $smarty->assign('full_page',    1);
    $smarty->assign('sort_field_id', '<img src="images/sort_desc.gif">');

    assign_query_info();
    $smarty->display('users_list.htm');
}

/*------------------------------------------------------ */
//-- ajax返回用户列表
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'query')
{
    $user_list = user_list();
    
    $smarty->assign('user_list',    $user_list['user_list']);
    $smarty->assign('filter',       $user_list['filter']);
    $smarty->assign('record_count', $user_list['record_count']);
    $smarty->assign('page_count',   $user_list['page_count']);

    $sort_flag  = sort_flag($user_list['filter']);
    $smarty->assign($sort_flag['tag'], $sort_flag['img']);

    make_json_result($smarty->fetch('users_list.htm'), '', array('filter' => $user_list['filter'], 'page_count' => $user_list['page_count']));
}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
	die('功能已被禁用'); // add by Zandy

    /* 检查权限 */
    admin_priv('users_manage');

    $user = array(  'rank_points'   => $_CFG['register_points'],
                    'pay_points'    => $_CFG['register_points'],
                    'sex'           => 0
                    );

    $smarty->assign('ur_here',          $_LANG['04_users_add']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list'));
    $smarty->assign('form_action',      'insert');
    $smarty->assign('user',             $user);
    $smarty->assign('special_ranks',    get_rank_list(true));

    assign_query_info();
    $smarty->display('user_info.htm');
}

/*------------------------------------------------------ */
//-- 添加会员帐号
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $users =& init_users();

    $newid = $users->add_user($_POST['username'], $_POST['password'], $_POST['email']);

    if ($newid == 0)
    {
        /* 插入会员数据失败 */
        if ($users->error == ERR_USERNAME_EXISTS)
        {
            $msg = $_LANG['username_exists'];
        }
        elseif ($users->error == ERR_EMAIL_EXISTS)
        {
            $msg = $_LANG['email_exists'];
        }
        else
        {
            die('Error:'.$users->error_msg());
        }
        sys_msg($msg, 1);
    }
    else
    {
        if ($_CFG['integrate_code'] != 'ecshop')
        {
            /* 插入会员数据到ecshop数据库 */
            $sql = "INSERT INTO " .$ecs->table("users"). " (user_id, user_name, password, email) ".
                    "VALUES ('$newid', '$_POST[username]', '" .$ecs->compile_password($_POST['password']). "',".
                            " '$_POST[email]')";
            $db->query($sql);
        }

        /* 更新会员的其它信息 */
        $sql = "UPDATE " .$ecs->table('users') . " SET ".
                "sex        = '$_POST[sex]', ".
                //"question   = '$_POST[question]', ".
                //"answer     = '$_POST[answer]', ".
                "user_rank  = '$_POST[user_rank]', ".
               // "user_money = '$_POST[user_money]', ".
                "pay_points = '$_POST[pay_points]',".
                "rank_points= '$_POST[rank_points]',".
                "birthday   = '$_POST[birthdayYear]-$_POST[birthdayMonth]-$_POST[birthdayDay]'".
            "WHERE user_id = '$newid'";
        $db->query($sql);

        /* 记录管理员操作 */
        admin_log($_POST['username'], 'add', 'users');

        /* 提示信息 */
        $link[] = array('text' => $_LANG['go_back'], 'href'=>'users.php?act=list');
        sys_msg(sprintf($_LANG['add_success'], htmlspecialchars(stripslashes($_POST['username']))), 0, $link);
    }
}

/*------------------------------------------------------ */
//-- 编辑用户帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $users  =& init_users();
    $user   = $users->get_user_info($_GET['id']);

    $sql = "SELECT sex, birthday, pay_points, rank_points, user_rank ,user_money FROM " .$ecs->table('users'). " WHERE user_id='$_GET[id]'";

    $row = $db->GetRow($sql);

    if ($row)
    {
        $user['sex']            = $row['sex'];
        $user['birthday']       = date($row['birthday']);
        $user['pay_points']     = $row['pay_points'];
        $user['rank_points']    = $row['rank_points'];
        $user['user_rank']      = $row['user_rank'];
        $user['user_money']     = $row['user_money'];
        $user['formated_user_money'] = price_format($row['user_money']);
    }
    else
    {
        $user['sex']            = 0;
        $user['pay_points']     = 0;
        $user['rank_points']    = 0;
        $user['user_money']     = 0;
        $user['formated_user_money'] = price_format(0);
     }

    assign_query_info();
    $smarty->assign('ur_here',          $_LANG['users_edit']);
    $smarty->assign('action_link',      array('text' => $_LANG['03_users_list'], 'href'=>'users.php?act=list'));
    $smarty->assign('user',             $user);
    $smarty->assign('form_action',      'update');
    $smarty->assign('special_ranks',    get_rank_list(true));
    $smarty->display('user_info.htm');
}

/*------------------------------------------------------ */
//-- 更新用户帐号
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'update')
{
    /* 检查权限 */
    admin_priv('users_manage');

    $users  =& init_users();
    $updated = $users->edit_user($_POST['id'], $_POST['username'], $_POST['email']);

    if (!$updated)
    {
        if ($users->error == ERR_USERNAME_EXISTS)
        {
            sys_msg($_LANG['username_exists'], 1);
        }
        elseif ($users->error == ERR_EMAIL_EXISTS)
        {
            sys_msg($_LANG['email_exists'], 1);
        }
        else
        {
            sys_msg($_LANG['edit_user_failed'], 1);
        }
    }
    else
    {
        /* 检查ecshop会员表中有没有该用户数据 */
        $sql = "SELECT COUNT(*) FROM " .$ecs->table('users'). " WHERE user_id='$_POST[id]'";
        $num = $db->getOne($sql);

        if ( $num == 0)
        {
            /* 该用户数据不存在插入用户数据 */
            $sql = "INSERT INTO " .$ecs->table('users'). " (".
                        "user_id, user_name, email, sex, birthday, user_rank ".
                    ") VALUES (".
                        "'$_POST[id]', '$_POST[username]', '$_POST[email]', '$_POST[sex]', ".
                        "'$_POST[birthdayYear]-$_POST[birthdayMonth]-$_POST[birthdayDay]', '$_POST[user_rank]')";
        }
        else
        {
            /* 该用户数据已经存在，更新用户数据 */
            if (!isset($_POST['sex']))
            {
                $_POST['sex'] = 0;
            }

            $sql = "UPDATE " .$ecs->table('users'). " SET ".
                        "user_name  ='$_POST[username]', ".
                        "email      ='$_POST[email]', ".
                        //"question   ='$_POST[question]', ".
                        //"answer     ='$_POST[answer]', ".
                        "sex        ='$_POST[sex]', ".
                        "user_rank  ='$_POST[user_rank]', ".
                        "rank_points='$_POST[rank_points]', ".
                        "pay_points ='$_POST[pay_points]', ".
                       //"user_money ='$_POST[user_money]',  ".
                        "birthday   ='$_POST[birthdayYear]-$_POST[birthdayMonth]-$_POST[birthdayDay]'".
                    " WHERE user_id='$_POST[id]'";
        }

        $db->query($sql);
        /* 记录管理员操作 */
        admin_log($_POST['username'], 'edit', 'users');

        /* 提示信息 */
        $links[0]['text']    = $_LANG['goto_list'];
        $links[0]['href']    = 'users.php?act=list';
        $links[1]['text']    = $_LANG['go_back'];
        $links[1]['href']    = 'javascript:history.back()';

        sys_msg($_LANG['update_success'], 0, $links);
    }

}

/* 编辑用户名 */
elseif ($_REQUEST['act'] == 'edit_username')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $username = empty($_REQUEST['val']) ? '' : trim($_REQUEST['val']);
    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);

    if ($id == 0)
    {
        make_json_error('NO USER ID');
        return;
    }

    if ($username == '')
    {
        make_json_error($GLOBALS['_LANG']['username_empty']);
        return;
    }

    $users =& init_users();

    if ($users->edit_user($id, $username))
    {
        if ($_CFG['integrate_code'] != 'ecshop')
        {
            /* 更新商城会员表 */
            $db->query('UPDATE ' .$ecs->table('users'). " SET user_name = '$username' WHERE user_id = '$id'");
        }

        admin_log(addslashes($username), 'edit', 'users');
        make_json_result(stripcslashes($username));
    }
    else
    {
        $msg = ($users->error == ERR_USERNAME_EXISTS) ? $GLOBALS['_LANG']['username_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
        make_json_error($msg);
    }
}

/*------------------------------------------------------ */
//-- 编辑email
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_email')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $email = empty($_REQUEST['val']) ? '' : trim($_REQUEST['val']);
    $users =& init_users();

    if (is_email($email))
    {
        if ($users->edit_user($id, '', $email))
        {
            if ($_CFG['integrate_code'] != 'ecshop')
            {
                /* 同时更新商城会员表 */
                $db->query('UPDATE ' .$ecs->table('users'). " SET email = '$email' WHERE user_id='$id'");
            }

            $arr = $users->get_user_info($id);
            admin_log(addslashes($arr['username']), 'edit', 'users');

            make_json_result(stripcslashes($email));
        }
        else
        {
            $msg = ($users->error == ERR_EMAIL_EXISTS) ? $GLOBALS['_LANG']['email_exists'] : $GLOBALS['_LANG']['edit_user_failed'];
            make_json_error($msg);
        }
    }
    else
    {
        make_json_error($GLOBALS['_LANG']['invalid_email']);
    }
}

/*------------------------------------------------------ */
//-- 编辑user_mobile
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit_user_mobile')
{
    /* 检查权限 */
    check_authz_json('users_manage');

    $id = empty($_REQUEST['id']) ? 0 : intval($_REQUEST['id']);
    $user_mobile = empty($_REQUEST['val']) ? '' : trim($_REQUEST['val']);
    $users =& init_users();

	$db->query('UPDATE ' .$ecs->table('users'). " SET user_mobile = '$user_mobile' WHERE user_id='$id'");

    $arr = $users->get_user_info($id);
    admin_log(addslashes($arr['username']), 'edit', 'users');

    make_json_result(stripcslashes($user_mobile));
}

/**
 *  返回用户列表数据
 *
 * @access  public
 * @param
 *
 * @return void
 */
function user_list()
{
    $users   =& init_users();
    $user_map = get_class_vars(get_class($users));

    /* 过滤条件 */
    $filter['keywords'] = empty($_REQUEST['keywords']) ? '' : trim($_REQUEST['keywords']);
    $filter['rank'] = empty($_REQUEST['rank']) ? 0 : intval($_REQUEST['rank']);
    $filter['pay_points_gt'] = empty($_REQUEST['pay_points_gt']) ? 0 : intval($_REQUEST['pay_points_gt']);
    $filter['pay_points_lt'] = empty($_REQUEST['pay_points_lt']) ? 0 : intval($_REQUEST['pay_points_lt']);

    $filter['sort_by']    = empty($_REQUEST['sort_by'])    ? 'field_id' : trim($_REQUEST['sort_by']);
    $filter['sort_order'] = empty($_REQUEST['sort_order']) ? 'DESC'     : trim($_REQUEST['sort_order']);

    $ex_where = ' WHERE 1 ';
    if ($filter['rank'])
    {
        $sql = "SELECT min_points, max_points, special_rank FROM ".$GLOBALS['ecs']->table('user_rank')." WHERE rank_id = '$filter[rank]'";
        $row = $GLOBALS['db']->getRow($sql);
        if ($row['special_rank'] > 0)
        {
            /* 特殊等级 */
            $ex_where .= " AND user_rank = '$filter[rank]' ";
        }
        else
        {
            $ex_where .= " AND rank_points >= " . intval($row['min_points']) . " AND rank_points < " . intval($row['max_points']);
        }
    }
    if ($filter['pay_points_gt'])
    {
         $ex_where .=" AND pay_points >= '$filter[pay_points_gt]' ";
    }
    if ($filter['pay_points_lt'])
    {
        $ex_where .=" AND pay_points < '$filter[pay_points_lt]' ";
    }

    if ($ex_where == ' WHERE 1 ' )
    {
        $extention = '';
    }
    else
    {
        $sql = "SELECT user_id FROM ".$GLOBALS['ecs']->table('users').$ex_where;
        $ids = $GLOBALS['db']->getCol($sql);
        $extention = db_create_in($ids);
    }

    $filter['record_count'] = $users->get_user_count(mysql_like_quote($filter['keywords']), $extention);

    /* 分页大小 */
    $filter = page_and_size($filter);
    $user_list = & $users->get_users($filter['page'], $filter['page_size'], $user_map[$filter['sort_by']], $filter['sort_order'], mysql_like_quote($filter['keywords']), $extention);
    
    foreach ($user_list AS $key => $value)
    {
        $user_list[$key]['field_id'] = $value[$user_map['field_id']];
        $user_list[$key]['field_name'] = $value[$user_map['field_name']];
        $user_list[$key]['field_email'] = $value[$user_map['field_email']];
        $user_list[$key]['field_user_mobile'] = $value[$user_map['field_user_mobile']];
        $user_list[$key]['field_reg_date'] = date($GLOBALS['_CFG']['date_format'] , $value[$user_map['field_reg_date']]);
    }

    $filter['keywords'] = stripslashes($filter['keywords']);
    $arr = array('user_list' => $user_list, 'filter' => $filter, 'page_count' => $filter['page_count'], 'record_count' => $filter['record_count']);

    return $arr;
}

?>