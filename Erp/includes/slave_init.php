<?php

/**
 * ECSHOP 前台公用文件
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
 * $Date: 2007-05-22 12:10:36 +0800 (星期二, 22 五月 2007) $
 * $Id: init.php 8678 2007-05-22 04:10:36Z paulgao $
*/
//session_start();
if (!defined('IN_ECS'))
{
	die('Hacking attempt');
}

if (__FILE__ == '')
{
	die('Fatal error code: 0');
}

error_reporting(E_ALL ^ E_NOTICE);

/* 取得当前ecshop所在的根目录 */
define('ROOT_PATH', str_replace('includes/slave_init.php', '', str_replace('\\', '/', __FILE__)));

$WEB_ROOT = substr(realpath(dirname(__FILE__).'/../'), strlen(realpath($_SERVER['DOCUMENT_ROOT'])));
if (trim($WEB_ROOT, '/\\')) {
	$WEB_ROOT = '/'.trim($WEB_ROOT, '/\\').'/';
} else {
	$WEB_ROOT = '/';
}

$WEB_ROOT = str_replace("\\", "/", $WEB_ROOT);

define('WEB_ROOT', $WEB_ROOT);

//define('SEARCH_SIZE',10);

/* 初始化设置 */
@ini_set('memory_limit',          '16M');
@ini_set('session.cache_expire',  180);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies',   1);
@ini_set('session.auto_start',    0);
@ini_set('display_errors',        1);


/*if (DIRECTORY_SEPARATOR == '\\')
{
	@ini_set('include_path', '.;' . ROOT_PATH);
}
else
{
	@ini_set('include_path', '.:' . ROOT_PATH);
}*/
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/../'.PATH_SEPARATOR.dirname(__FILE__).'/../RpcApi');

require(ROOT_PATH . 'data/slave_config.php');

if (defined('DEBUG_MODE') == false)
{
	define('DEBUG_MODE', 0);
}

if (PHP_VERSION >= '5.1' && !empty($timezone))
{
	date_default_timezone_set($timezone);
}

require(ROOT_PATH . 'includes/inc_constant.php');
require(ROOT_PATH . 'includes/cls_ecshop.php');
require(ROOT_PATH . 'includes/cls_error.php');
require(ROOT_PATH . 'includes/lib_common.php');
require(ROOT_PATH . 'includes/lib_main.php');
require(ROOT_PATH . 'includes/lib_insert.php');
require(ROOT_PATH . 'includes/lib_goods.php');
require(ROOT_PATH . 'includes/lib_article.php');
include_once(ROOT_PATH . 'includes/lib_clips.php');

/* 对用户传入的变量进行转义操作。*/
if (!get_magic_quotes_gpc())
{
	if (!empty($_GET))
	{
		$_GET  = addslashes_deep($_GET);
	}
	if (!empty($_POST))
	{
		$_POST = addslashes_deep($_POST);
	}

	$_COOKIE   = addslashes_deep($_COOKIE);
	$_REQUEST  = addslashes_deep($_REQUEST);
}

/* 创建 ECSHOP 对象 */
$ecs = new ECS($db_name, $prefix);

/* 初始化数据库类 */
require(ROOT_PATH . 'includes/cls_mysql.php');
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$db->set_disable_cache_tables(array($ecs->table('sessions'), $ecs->table('cart')));


include_once(ROOT_PATH . 'includes/init.inc.php');
require_once(ROOT_PATH . 'includes/cls_session.php');
$GLOBALS['sess'] = new cls_session($db, $ecs->table('sessions'));

/*
$Oukoo_ecs = new ECS($oukoo_db_name, $oukoo_prefix);

require_once(ROOT_PATH . 'data/Oukoo_config.php');
$Oukoo_db = new cls_mysql($oukoo_db_host, $oukoo_db_user, $oukoo_db_pass, $oukoo_db_name);
$Oukoo_db->set_disable_cache_tables(array($Oukoo_ecs->table('sessions'), $Oukoo_ecs->table('cart')));
初始化主数据库数据库类*/

/* 创建错误处理对象 */
$err = new ecs_error('message.dwt');

/*
if (!defined('INIT_NO_USERS'))
{
	include(ROOT_PATH . 'includes/cls_session.php');

	$sess = new cls_session($db, $ecs->table('sessions'));

	define('SESS_ID', $sess->get_session_id());
}
*/

/* 载入系统参数 */
$_CFG = load_config();

/* 载入语言文件 */
require_once(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/common.php');
/* 载入语言文件 */
require_once('languages/' . $_CFG['lang'] . '/user.php');
/* 载入OUKOO特有的语言包 */
require_once(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/oukoo_common.php');
/* 载入OUKOOMAP的语言包 */
require_once(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/oukoo_map.php');



if ($_CFG['shop_closed'] == 1)
{
	/* 商店关闭了，输出关闭的消息 */
	header('Content-type: text/html; charset=utf-8');

	die('<div style="margin: 150px; text-align: center; font-size: 14px"><p>' . $_LANG['shop_closed'] . '</p><p>' . $_CFG['close_comment'] . '</p></div>');
}



if (!defined('INIT_NO_SMARTY'))
{
	// header('Cache-control: private');
	header('content-type: text/html; charset=utf-8');

	if (!file_exists(ROOT_PATH . 'templates/caches'))
	{
		/* 如果不存在caches目录，则创建它 */
		@mkdir(ROOT_PATH . 'templates/caches', 0777);
		@chmod(ROOT_PATH . 'templates/caches', 0777);
	}
	clearstatcache();

	require(ROOT_PATH . 'includes/smarty/Smarty.class.php');

	/* 创建 Smarty 对象。*/
	$smarty = new Smarty;

	$smarty->cache_lifetime = $_CFG['cache_time'];
	// $smarty->template_dir   = ROOT_PATH . 'themes/ouku' . $_CFG['template'];
	$smarty->template_dir   = ROOT_PATH . 'themes/ouku';

	$smarty->cache_dir      = ROOT_PATH . 'templates/caches';
	$smarty->compile_dir    = ROOT_PATH . 'templates/compiled';

	$smarty->plugins_dir    = ROOT_PATH . 'includes/smarty/plugins';
	$smarty->compile_check  = true;

	/*路径变量输出*/
	$sPageSize	=	8;
	$spath	=	'';
	$path	=	$WEB_ROOT.'themes/ouku/';
	$versionLastTime = '20080107';//为css和js路径添加时间变量
	#$spath	=	WEB_ROOT;
	#$path	=	WEB_ROOT.'themes/ouku/';
	$smarty->assign('dir',ROOT_PATH);
	$smarty->assign('WEB_ROOT', $WEB_ROOT);
	$smarty->assign('path', $path);
	$smarty->assign('spath', $WEB_ROOT);
	$smarty->assign('vLastTime',$versionLastTime);
	$smarty->load_filter('pre', 'preCompile'); // 载入预编译函数
	$smarty->register_resource('db',  array('db_get_template',  'db_get_timestamp',  'db_get_secure',  'db_get_trusted'));
	$smarty->register_resource('str', array('str_get_template', 'str_get_timestamp', 'str_get_secure', 'str_get_trusted'));
	$smarty->register_function('insert_scripts', 'smarty_insert_scripts');
	$smarty->register_function("to_goods_path", "to_goods_path");
	$smarty->register_function("to_bj_goods_path", "to_bj_goods_path");
	$smarty->register_function("to_bj_store_path", "to_bj_store_path");	

	if ((DEBUG_MODE & 2) == 2)
	{
		$smarty->force_compile = true;
	}
	else
	{
		$smarty->force_compile = false;
	}

	$smarty->assign('OuKoolang', $_OuKooLang);


	$smarty->assign('lang', $_LANG);
}

if (!defined('INIT_NO_USERS'))
{
	/* 会员信息 */

	$user =& init_users();

	if (!isset($_SESSION['user_id']))
	{
		/* 获取投放站点的名称 */
		$site_name = isset($_GET['from'])   ? $_GET['from'] : addslashes($_LANG['self_site']);
		$from_ad   = !empty($_GET['ad_id']) ? intval($_GET['ad_id']) : 0;

		$_SESSION['from_ad'] = $from_ad; // 用户点击的广告ID
		$_SESSION['referer'] = stripslashes($site_name); // 用户来源

		unset($site_name);

//		visit_stats(); 危险！这个代码不适合我们的网站！！！！！
	}

	if (empty($_SESSION['user_id']))
	{
		if ($user->get_cookie())
		{
			//print_r($user->get_cookie());
			/* 如果会员已经登录并且还没有获得会员的帐户余额、积分以及优惠券 */
			if ($_SESSION['user_id'] > 0 && !isset($_SESSION['user_money']))
			{
				update_user_info();
			}
		}
		else
		{
			$_SESSION['user_id']     = 0;
			$_SESSION['user_name']   = '';
			$_SESSION['email']       = '';
			$_SESSION['user_rank']   = 0;
			$_SESSION['user_money']  = 0;
			$_SESSION['user_points'] = 0;
			$_SESSION['user_bonus']  = 0;
			$_SESSION['discount']    = 1.00;
		}
	}

}


$userInfo	=	'';
/* SSO单点登陆 */

$cookie_user_id = @$_COOKIE['OKSID'];

if($cookie_user_id){
	// 远程去取数据
	$arContext['sessionKey']	=	$cookie_user_id;

	if($arContext['sessionKey']){
		$CONFIG	=	'ouku';
		require_once(ROOT_PATH .'Sso/Sso.Controller.Method.php');
		$objuserController	=	new userController($CONFIG);
		$GLOBALS['SSO']		=	$objuserController;
		$arError	=	$GLOBALS['SSO']->UserContext($arContext);
		if($arError['errorCode']){
			//break;
		}else{
			// 本地去取数据
			$userSessionKeyInfo	=	get_userSessionkey_info($arError['reInfo']->userId);
			if($userSessionKeyInfo){
				$userInfo	= get_user_default($userSessionKeyInfo);
				$_SESSION['user_id']   			= $userInfo['user_id'];
				$_SESSION['userId']   			= $userInfo['userId'];
				$_SESSION['user_name'] 			= $userInfo['username'];
				$_SESSION['rank_id']			= $userInfo['rank_id']?$userInfo['rank_id']:0;
				$_SESSION['rank_point']			= $userInfo['rank_point'];
				$_SESSION['pay_point']			= $userInfo['pay_point'];
				// SSO 的 sessionKey
				$_SESSION['session_key'] 		= $arContext['sessionKey'];
				$_SESSION['email']     			= $userInfo['email'];
				//exit;
			}else{
				if($arError['reInfo']->userName&&$arError['reInfo']->email&&$arError['reInfo']->userId){
					/* 插入数据库 */
					$sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('users') . '(user_name
				 ,  email , reg_time,userId ) VALUES ("'.
					$arError['reInfo']->userName.'","'.$arError['reInfo']->email.'","' . time() . '","'.$arError['reInfo']->userId.'")';
					$res = $db->query($sql);
					if($res){
						$_SESSION['user_id']   			= $db->Insert_ID();
						$_SESSION['userId']   			= $arError['reInfo']->userId;
						$_SESSION['user_name'] 			= $arError['reInfo']->userName;
						
						//等级最初级别就是1
						$_SESSION['rank_id']			= 1;
						// SSO 的 sessionKey
						$_SESSION['session_key'] 		= $arContext['sessionKey'];
						$_SESSION['email']     			= $arError['reInfo']->email;
					}
					$userInfo	= get_user_default($db->insert_id());
				}
			}
		}
	}

}

if (!defined('INIT_NO_SMARTY'))
{
    //下部导航栏
    require_once(ROOT_PATH . "includes/lib_help.php");
    $smarty->assign("footer_help_cats", get_footer_help_cats());
}

if ((DEBUG_MODE & 1) == 1)
{
	error_reporting(E_ALL);
}
else
{
	error_reporting(E_ALL ^ E_NOTICE);
}
if ((DEBUG_MODE & 4) == 4)
{
	include('./includes/lib.debug.php');
}

/* 判断是否支持 Gzip 模式 */
if (gzip_enabled())
{
	ob_start('ob_gzhandler');
}

?>
