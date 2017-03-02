<?php

/**
 * 初始化文件
 */


if (!defined('IN_ECS')) { die('Hacking attempt'); }
if (__FILE__ == '') { die('Fatal error code: 0'); }


define('ROOT_PATH', realpath(dirname(__FILE__) . '/../') .DIRECTORY_SEPARATOR);
define('WEB_ROOT',  '/');


/* 初始化设置 */
@ini_set('memory_limit',          '1536M');
@ini_set('session.cache_expire',  180);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies',   1);
@ini_set('session.auto_start',    1);
@ini_set('display_errors',        0);
@ini_set('include_path',          '.'. PATH_SEPARATOR . ROOT_PATH); 
session_start();

include(ROOT_PATH . 'data/master_config.php');
defined('DEBUG_MODE') or define('DEBUG_MODE', 0);
/*
if ((DEBUG_MODE & 1) == 1)
{
    #error_reporting(E_ALL);
}
else
{
    error_reporting(0);  
}
*/
error_reporting(0);

if (PHP_VERSION >= '5.1' && !empty($timezone))
{
    date_default_timezone_set($timezone);
}

require(ROOT_PATH . 'includes/inc_constant.php');
require(ROOT_PATH . 'includes/cls_ecshop.php');
require(ROOT_PATH . 'includes/cls_error.php');
require(ROOT_PATH . 'includes/lib_common.php');
require(ROOT_PATH . 'admin/includes/lib_main.php');
require(ROOT_PATH . 'admin/includes/cls_exchange.php');
require(ROOT_PATH . 'RomeoApi/lib_party.php');

define("COOKIE_DOMAIN", ".ouku.com");

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
$slave_db = new cls_mysql($slave_db_host, $slave_db_user, $slave_db_pass, $slave_db_name, false);


/* 创建发送短信rpc对象 */
require(ROOT_PATH . 'admin/includes/rpc/message/MessageApplicationServiceClient.php');
$message_context = new RpcContext($message_rpc_host, $message_rpc_path, $message_rpc_port);
$message_client = new MessageApplicationServiceClient($message_context);

/* 创建单点登录rpc对象 */
require(ROOT_PATH . 'admin/includes/rpc/sso/UniUserServiceClient.php');
$sso_context = new RpcContext($sso_rpc_host, $sso_rpc_path, $sso_rpc_port);
$sso_client = new UniUserServiceClient($sso_context);

/* 创建shoprpc对象*/
require(ROOT_PATH . 'admin/includes/rpc/shopapi/OrderServiceClient.php');
$shopapi_context = new RpcContext($sso_rpc_host, $sso_rpc_path, $sso_rpc_port);
$shopapi_client = new OrderServiceClient($shopapi_context);

/* 创建错误处理对象 */
$err = new ecs_error('message.htm');

/* 初始化 action */
if (!isset($_REQUEST['act']))
{
    $_REQUEST['act'] = '';
}
elseif (($_REQUEST['act'] == 'login' || $_REQUEST['act'] == 'logout' || $_REQUEST['act'] == 'signin') && strpos($_SERVER['PHP_SELF'], '/login.php') === false)
{
    $_REQUEST['act'] = '';
}
elseif (($_REQUEST['act'] == 'forget_pwd' || $_REQUEST['act'] == 'reset_pwd' || $_REQUEST['act'] == 'get_pwd') && strpos($_SERVER['PHP_SELF'], '/get_password.php') === false)
{
    $_REQUEST['act'] = '';
}

/* 载入系统参数 */
$_CFG = load_config();


require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/common.php');
require(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/log_action.php');

if (file_exists(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/' . basename($_SERVER['PHP_SELF'])))
{
    include(ROOT_PATH . 'languages/' . $_CFG['lang'] . '/admin/' . basename($_SERVER['PHP_SELF']));
}

if (!file_exists(ROOT_PATH . 'templates/caches'))
{
    @mkdir(ROOT_PATH . 'templates/caches', 0777);
    @chmod(ROOT_PATH . 'templates/caches', 0777);
}

if (!file_exists(ROOT_PATH . 'templates/compiled/admin'))
{
    @mkdir(ROOT_PATH . 'templates/compiled/admin', 0777);
    @chmod(ROOT_PATH . 'templates/compiled/admin', 0777);
}

clearstatcache();

/* 创建 Smarty 对象。*/
require(ROOT_PATH . 'includes/smarty/Smarty.class.php');
$smarty = new Smarty;

$smarty->template_dir  = ROOT_PATH . 'admin/templates';
$smarty->compile_dir   = ROOT_PATH . 'templates/compiled/admin';
$smarty->plugins_dir   = ROOT_PATH . 'includes/smarty/plugins';
$smarty->caching       = false;
$smarty->compile_force = false;
$smarty->register_resource('db', array('db_get_template', 'db_get_timestamp', 'db_get_secure', 'db_get_trusted'));
$smarty->register_resource('db_msg', array('db_msg_get_template', 'db_msg_get_timestamp', 'db_msg_get_secure', 'db_msg_get_trusted'));

$smarty->register_function('insert_scripts', 'smarty_insert_scripts');
$smarty->register_function('create_pages',   'smarty_create_pages');

$smarty->assign('lang', $_LANG);

require(ROOT_PATH . 'admin/config.vars.php');
$smarty->assign('_CFG', $_CFG);

$smarty->assign('ROOT_PATH', ROOT_PATH);
$smarty->assign('WEB_ROOT', WEB_ROOT);


/* 验证管理员身份 */
if ((!isset($_SESSION['distributor_id']) || intval($_SESSION['distributor_id']) <= 0) &&
    $_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
    $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd')
{
    // session 不存在，检查cookie
    if (!empty($_SESSION['distributor_id']) && !empty($_SESSION['distributor_pass']))
    {
        // 找到了session, 验证session信息
        $sql = "
            SELECT distributor_id, party_id, name, password 
            FROM distributor 
            WHERE 
                party_id = ". PARTY_OUKU_MOBILE ."
                distributor_id = '" . intval($_SESSION['distributor_id']) . "' AND
                password = '". $_SESSION['distributor_pass'] ."'
        ";
        $row = $db->GetRow($sql);

        if (!$row)
        {
            if (!empty($_REQUEST['is_ajax']))
            {
                make_json_error($_LANG['priv_error']);
            }
            else
            {
                header("Location: {$WEB_ROOT}login.php?act=login&back={$_SERVER['REQUEST_URI']}\n");
            }

            exit;
        }
    }
    else
    {
        if (!empty($_REQUEST['is_ajax']))
        {
            make_json_error($_LANG['priv_error']);
        }
        else
        {
            header("Location: {$WEB_ROOT}login.php?act=login&back={$_SERVER['REQUEST_URI']}\n");
        }

        exit;
    }
}

//header('Cache-control: private');
header('content-type: text/html; charset=utf-8');
header('Expires: Fri, 14 Mar 1980 20:53:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

/* 判断是否支持gzip模式 */
if (gzip_enabled())
{
    ob_start('ob_gzhandler');
}



/*
if($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'logout' && $_REQUEST['act'] != 'signin' &&
    $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd') {
  //登录退出不判断，让用户至少能登录啊
  
  $real_ip = real_ip();
  $company_ip_list = explode("\n", $_CFG['company_ip']);
  $company_ip_list[] = $_CFG['test_ip'];
  // 过滤掉换行等空白信息
  $company_ip_list = array_map('trim', $company_ip_list);
  if($_SESSION['allowedip_type'] != 'ANYWHERE' && !in_array($real_ip, $company_ip_list) ) {
    //不在公司范围内
    if($_SESSION['allowedip_type'] == 'SELECTED') {
      $allowedip_list = explode("\n", $_SESSION['allowedip_list']);
      // 过滤掉换行等空白信息
      $allowedip_list = array_map('trim', $allowedip_list);
      if(!in_array($real_ip, $allowedip_list)) {
        sys_msg("该IP {$real_ip} 无法使用后台",0,null,false);
        exit();
      }
    } else {
      sys_msg("该IP {$real_ip} 无法使用后台",0,null,false);
        exit();
    }
  }
} 

*/


?>
