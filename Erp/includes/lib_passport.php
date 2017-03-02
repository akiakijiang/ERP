<?php

/**
 * ECSHOP 用户帐号相关函数库
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
 * $Date: 2007-05-17 11:44:15 +0800 (星期四, 17 五月 2007) $
 * $Id: lib_passport.php 8651 2007-05-17 03:44:15Z paulgao $
*/

if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

/**
 * 用户注册，登录函数
 *
 * @access  public
 * @param   string       $username          注册用户名
 * @param   string       $password          用户密码
 * @param   string       $email             注册email
 *
 * @return  bool         $bool
 */
function register($username, $password, $email)
{
	/* 
	if (empty($username))
	{
		$GLOBALS['err']->add($GLOBALS['_LANG']['username_empty']);
	}
	else
	{
		if (preg_match('/\'\/^\\s*$|^c:\\\\con\\\\con$|[%,\\*\\"\\s\\t\\<\\>\\&\'\\\\]/', $username))
		{
			$GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['username_invalid'], htmlspecialchars($username)));
		}
	}

	
	if (empty($email))
	{
		$GLOBALS['err']->add($GLOBALS['_LANG']['email_empty']);
	}
	else
	{
		if (!is_email($email))
		{
			$GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], htmlspecialchars($email)));
		}
	}

	if ($GLOBALS['err']->error_no > 0)
	{
		echo('asdf');
		return false;
	}
	*/

	if (empty($email))
	{
		$GLOBALS['err']->add($GLOBALS['_LANG']['email_empty']);
	}
	else
	{
		if (!is_email($email))
		{
			$GLOBALS['err']->add(sprintf($GLOBALS['_LANG']['email_invalid'], htmlspecialchars($email)));
		}
	}
	
	
	$newid = $GLOBALS['user']->add_user($username, $password, $email);
	if ($newid=== false){
		return false;
	}
	elseif($newid['reString']){
		return $newid['reString'];
	}else{
		$cur_date = date('Y-m-d H:i:s');
		/* 更新会员注册积分 */
		$sql = 'UPDATE ' . $GLOBALS['ecs']->table('users') .
		" SET pay_points ='" . $GLOBALS['_CFG']['register_points'] ."'," .
		" rank_points = '" . $GLOBALS['_CFG']['register_points'] ."' ," .
		" birthday = '1970-01-01'," .
		" last_time = '$cur_date'," .
		" user_rank = 1" .
		" WHERE user_id = '$newid' ";
		
		$GLOBALS['db']->query($sql);

		/* 设置session */
		$_SESSION['user_id']   = $newid;
		$_SESSION['user_name'] = stripslashes($username);
		$_SESSION['email']     = $email;
		$_SESSION['rank_id']   = 1;

		update_user_info();      // 更新用户信息
		recalculate_price();     // 重新计算购物车中的商品价格
		return true;
	}
}

/**
 *
 *
 * @access  public
 * @param
 *
 * @return void
 */
function logout()
{
	/* todo */
}

/**
 *  将指定user_id的密码修改为new_password。可以通过旧密码和验证字串验证修改。
 *
 * @access  public
 * @param   int     $user_id        用户ID
 * @param   string  $new_password   用户新密码
 * @param   string  $old_password   用户旧密码
 * @param   string  $code           验证码（md5($user_id . md5($password))）
 *
 * @return  boolen  $bool
 */
function edit_password($user_id, $old_password, $new_password='', $code ='')
{
	if (empty($user_id)) $GLOBALS['err']->add($GLOBALS['_LANG']['not_login']);

	if ($GLOBALS['user']->edit_password($user_id, $old_password, $new_password, $code))
	{
		return true;
	}
	else
	{
		$GLOBALS['err']->add($GLOBALS['_LANG']['edit_password_failure']);

		return false;
	}
}

/**
 *  会员找回密码时，对输入的用户名和邮件地址匹配
 *
 * @access  public
 * @param   string  $user_name    用户帐号
 * @param   string  $email        用户Email
 *
 * @return  boolen
 */
function check_userinfo($user_name, $email)
{
	if (empty($user_name) || empty($email))
	{
		header("Location: user.php?act=get_password\n");

		exit;
	}

	/* 检测用户名和邮件地址是否匹配 */
	$user_info = $GLOBALS['user']->check_pwd_info($user_name, $email);
	if (!empty($user_info))
	{
		return $user_info;
	}
	else
	{
		return false;
	}
}

/**
 * 找回密码
 * 
 * @access  public
 * @param   string $userId  may be UserName, idNo, Mobile, Email
 * @return   boolean $result
 */

function find_password($userId) {
	if (empty($userId) || empty($user_name) || empty($email) || empty($code))
	{
		header("Location: findPassWord.php");
		die();
	}
}

/**
 *  用户进行密码找回操作时，发送一封确认邮件
 *
 * @access  public
 * @param   string  $uid          用户ID
 * @param   string  $user_name    用户帐号
 * @param   string  $email        用户Email
 * @param   string  $code         key
 *
 * @return  boolen  $result;
 */
function send_pwd_email($uid, $user_name, $email, $code)
{
	if (empty($uid) || empty($user_name) || empty($email) || empty($code))
	{
		header("Location: user.php?act=get_password\n");

		exit;
	}

	/* 设置重置邮件模板所需要的内容信息 */
	$template    = get_mail_template('send_password');
	$reset_email = $GLOBALS['ecs']->url() . 'user.php?act=get_password&uid=' . $uid . '&code=' . $code;

	$GLOBALS['smarty']->assign('user_name',   $user_name);
	$GLOBALS['smarty']->assign('reset_email', $reset_email);
	$GLOBALS['smarty']->assign('shop_name',   $GLOBALS['_CFG']['shop_name']);
	$GLOBALS['smarty']->assign('send_date',   date('Y-m-d'));
	$GLOBALS['smarty']->assign('sent_date',   date('Y-m-d'));

	$content = $GLOBALS['smarty']->fetch('db:send_password');

	/* 发送确认重置密码的确认邮件 */
	if (send_mail($user_name, $email, $template['template_subject'], $content, $template['is_html']))
	{
		return true;
	}
	else
	{
		return false;
	}
}

?>