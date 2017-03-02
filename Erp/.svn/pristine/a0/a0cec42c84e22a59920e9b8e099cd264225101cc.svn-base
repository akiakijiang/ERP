<?php

/**
 * 初始化文件
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

if(version_compare(PHP_VERSION, '5.5.0', '>=')){
    error_reporting(E_ALL ^ E_DEPRECATED  ^ E_NOTICE);
}else{
    //error_reporting(E_ALL);
    error_reporting(E_ALL ^ E_NOTICE);
}

if (__FILE__ == '')
{
    die('Fatal error code: 0');
}

/* 取得当前ecshop所在的根目录 */
define('ROOT_PATH', str_replace('admin/includes/init.php', '', str_replace('\\', '/', __FILE__)));

$WEB_ROOT = substr(realpath(dirname(__FILE__).'/../../'), strlen(realpath($_SERVER['DOCUMENT_ROOT'])));

if (trim($WEB_ROOT, '/\\')) {
	$WEB_ROOT = '/'.trim($WEB_ROOT, '/\\').'/';
} else {
	$WEB_ROOT = '/';
}

$WEB_ROOT = str_replace("\\", "/", $WEB_ROOT);

define('WEB_ROOT', $WEB_ROOT);

/* 初始化设置 */
// @ini_set('memory_limit',          '1536M');
@ini_set('session.cache_expire',  180);
@ini_set('session.use_trans_sid', 0);
@ini_set('session.use_cookies',   1);
@ini_set('session.auto_start',    0);
@ini_set('display_errors',        1);
if (DIRECTORY_SEPARATOR == '\\')
{
    @ini_set('include_path',      '.;' . ROOT_PATH);
}
else
{
    @ini_set('include_path',      '.:' . ROOT_PATH);
}

if (file_exists(ROOT_PATH . 'data/master_config.php'))
{
    include(ROOT_PATH . 'data/master_config.php');
}
else
{
    include(ROOT_PATH . 'includes/config.php');
}

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
require(ROOT_PATH.  'includes/lib_lock.php');
require(ROOT_PATH . 'admin/includes/lib_main.php');
require(ROOT_PATH . 'admin/includes/cls_exchange.php');
require(ROOT_PATH . 'RomeoApi/lib_party.php');

if(version_compare(PHP_VERSION, '5.4.0', '<')){
    define("COOKIE_DOMAIN", ".leqee.com");
}

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
global $ecs;
$ecs = new ECS($db_name, $prefix);

/* 初始化数据库类 */
require(ROOT_PATH . 'includes/cls_mysql.php');
global $db ;
$db = new cls_mysql($db_host, $db_user, $db_pass, $db_name);
$slave_db = new cls_mysql($slave_db_host, $slave_db_user, $slave_db_pass, $slave_db_name, false);


/* 创建发送短信rpc对象 */
// require(ROOT_PATH . 'admin/includes/rpc/message/MessageApplicationServiceClient.php');
// $message_context = new RpcContext($message_rpc_host, $message_rpc_path, $message_rpc_port);
// global $message_client;
// $message_client = new MessageApplicationServiceClient($message_context);

/* 创建单点登录rpc对象 */
// require(ROOT_PATH . 'admin/includes/rpc/sso/UniUserServiceClient.php');
// $sso_context = new RpcContext($sso_rpc_host, $sso_rpc_path, $sso_rpc_port);
// $sso_client = new UniUserServiceClient($sso_context);

/* 创建shoprpc对象*/
// require(ROOT_PATH . 'admin/includes/rpc/shopapi/OrderServiceClient.php');
// $shopapi_context = new RpcContext($sso_rpc_host, $sso_rpc_path, $sso_rpc_port);
// global $shopapi_client;
// $shopapi_client = new OrderServiceClient($shopapi_context);

/* 创建错误处理对象 */
global $err;
$err = new ecs_error('message.htm');

/* 初始化session */
require(ROOT_PATH . 'includes/cls_session.php');
$sess = new cls_session($db, $ecs->table('sessions'));
$GLOBALS['sess'] = $sess;

/* 初始化 action */
if (!isset($_REQUEST['act']))
{
    $_REQUEST['act'] = '';
}
elseif (($_REQUEST['act'] == 'login' || $_REQUEST['act'] == 'logout' || $_REQUEST['act'] == 'signin') && strpos($_SERVER['PHP_SELF'], '/privilege.php') === false)
{
    $_REQUEST['act'] = '';
}
elseif (($_REQUEST['act'] == 'forget_pwd' || $_REQUEST['act'] == 'reset_pwd' || $_REQUEST['act'] == 'get_pwd') && strpos($_SERVER['PHP_SELF'], '/get_password.php') === false)
{
    $_REQUEST['act'] = '';
}

/* 初始化roles */
//temp..太恶心了, 一定要改...
$sql = "SELECT role_id FROM ecshop.ecs_admin_role WHERE role_name like '%wuliu%' limit 1";
$wuliu_id_temp = $db->getOne($sql);
if($wuliu_id_temp)
	$wuliu_id = $wuliu_id_temp;
else
	$wuliu_id = 0;


/* 载入系统参数 */
$_CFG = load_config();

if ($_REQUEST['act'] == 'captcha' && $_CFG['enable_captcha'] == 1)
{
    include('../includes/cls_captcha.php');

    $img = new captcha('../data/captcha/');
    $img->generate_image();

    exit;
}

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
global $smarty;
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


// 验证管理员身份 
if (!is_cli() && (!isset($_SESSION['admin_id']) || intval($_SESSION['admin_id']) <= 0) && !is_login_page()) {
    if (!empty($_COOKIE['AUTH']['admin_id']) && !empty($_COOKIE['AUTH']['admin_pass'])) {
        // 找到了cookie, 验证cookie信息
        $sql = 'SELECT user_id, user_name, password, action_list, last_time, 
                allowedip_type, allowedip_list, facility_id, roles ' .
                ' FROM ' .$ecs->table('admin_user') .
                " WHERE user_id = '" . intval($_COOKIE['AUTH']['admin_id']) . "'";
        $row = $db->GetRow($sql);

        if (!$row)
        {
            // 没有找到这个记录
            setcookie($_COOKIE['AUTH']['admin_id'],   '', time()+36000, '/', COOKIE_DOMAIN);
            setcookie($_COOKIE['AUTH']['admin_pass'], '', time()+36000, '/', COOKIE_DOMAIN);

            if (!empty($_REQUEST['is_ajax']))
            {
                make_json_error($_LANG['priv_error']);
            }
            else
            {
                header("Location: {$WEB_ROOT}admin/privilege.php?act=login&back={$_SERVER['REQUEST_URI']}\n");
            }

            exit;
        }
        else
        {
            // 检查密码是否正确
            if (md5($row['password'] . $_CFG['hash_code']) == $_COOKIE['AUTH']['admin_pass'])
            {
                if(!empty($row['roles'])){
                    $ROLES_pure_action_list=array();
                    $ROLES_action_list=explode(',',$row['action_list']);
                    foreach ($row['action_list'] as $ROLES_action_item) {
                        $ROLES_pure_action_list[$ROLES_action_item]=$ROLES_action_item;
                    }
                    
                    $ROLES_roles=explode(',',$row['roles']);
                    if(!empty($ROLES_roles)){
                        $ROLES_action_list_groups=$db->getCol("SELECT action_list FROM ecshop.ecs_admin_role WHERE role_id in (".implode(',',$ROLES_roles).")");
                        foreach ($ROLES_action_list_groups as  $ROLES_action_list_group) {
                            $ROLES_action_list_items=explode(',',$ROLES_action_list_group);
                            if(!empty($ROLES_action_list_items)){
                                foreach ($ROLES_action_list_items as $value) {
                                    $ROLES_pure_action_list[$value]=$value;
                                }
                            }
                        }   
                    }
                    
                    $ROLES_pure_action_list=array_values($ROLES_pure_action_list);
                    $row['roles']=implode(',',$ROLES_pure_action_list);
                }
                
                
                set_admin_session($row['user_id'], party_get_user_default_party($row['user_id']), $row['user_name'], 
                                  $row['action_list'], $row['last_time'], $row['allowedip_type'], 
                                  $row['allowedip_list'], $row['facility_id'], $row['roles']);

                // 更新最后登录时间和IP
                $db->query('UPDATE ' . $ecs->table('admin_user') .
                            " SET last_time = '" . date('Y-m-d H:i:s', time()) . "', last_ip = '" . real_ip() . "'" .
                            " WHERE user_id = '" . $_SESSION['admin_id'] . "'");
            }
            else
            {
                setcookie($_COOKIE['AUTH']['admin_id'],   '', time()+36000, '/', COOKIE_DOMAIN);
                setcookie($_COOKIE['AUTH']['admin_pass'], '', time()+36000, '/', COOKIE_DOMAIN);

                if (!empty($_REQUEST['is_ajax']))
                {
                    make_json_error($_LANG['priv_error']);
                }
                else
                {
                    header("Location: {$WEB_ROOT}admin/privilege.php?act=login&back={$_SERVER['REQUEST_URI']}\n");
                }

                exit;
            }
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
            header("Location: {$WEB_ROOT}admin/privilege.php?act=login&back={$_SERVER['REQUEST_URI']}\n");
        }

        exit;
    }
}

// 确保有party_id
if ($_SESSION['admin_id'] && !$_SESSION['party_id']) {
    $_SESSION['party_id'] = party_get_user_default_party($_SESSION['admin_id']); 
    $sess->update_session();
}

/*
if ($_REQUEST['act'] != 'login' && $_REQUEST['act'] != 'signin' &&
    $_REQUEST['act'] != 'forget_pwd' && $_REQUEST['act'] != 'reset_pwd' && $_REQUEST['act'] != 'check_order')
{
    $admin_path = preg_replace('/:\d+/', '', $ecs->url()) . 'admin';

    if (!empty($_SERVER['HTTP_REFERER']) &&
        strpos(preg_replace('/:\d+/', '', $_SERVER['HTTP_REFERER']), $admin_path) === false)
    {
        if (!empty($_REQUEST['is_ajax']))
        {
            make_json_error($_LANG['priv_error']);
        }
        else
        {
            header("Location: {$WEB_ROOT}admin/privilege.php?act=login&back={$_SERVER['REQUEST_URI']}\n");
        }

        exit;
    }
}*/

/* 管理员登录后可在任何页面使用 act=phpinfo 显示 phpinfo() 信息 */
if ($_REQUEST['act'] == 'phpinfo' && function_exists('phpinfo'))
{
    phpinfo();

    exit;
}

//header('Cache-control: private');
header('content-type: text/html; charset=utf-8');
header('Expires: Fri, 14 Mar 1980 20:53:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

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
    include('../includes/lib.debug.php');
}

/* 判断是否支持gzip模式 */
if (gzip_enabled())
{
    ob_start('ob_gzhandler');
}

// 检测ip地址限制
if(!is_cli() && !is_login_page()) {
    $real_ip = real_ip();
    // 本地开发不用检验 by Zandy 2010.12
    //if (!in_array(substr($real_ip, 0, 4), array('127.', '192.', '172.'))) {
        
    //}

    if ($_SESSION['allowedip_type'] != 'ANYWHERE') { // 非任意ip访问
        
        $visit_allowed = false;
        if( $_SESSION['allowedip_type'] == 'SELECTED') { // 在指定的ip地址
            $allowedip_list = explode("\n", $_SESSION['allowedip_list']);
            // 过滤掉换行等空白信息
            $allowedip_list = array_map('trim', $allowedip_list);
            if (in_array($real_ip, $allowedip_list)) {
                $visit_allowed = true;
            }
        }
        
        if (!$visit_allowed) {
            $company_ip_list = explode("\n", $_CFG['company_ip']);
            $company_ip_list[] = $_CFG['test_ip'];
            $company_ip_list = array_map('trim', $company_ip_list);
            foreach($company_ip_list as $key=>$ip) {
                if (!preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/',$ip)) {
                    if (($ips=gethostbynamel($ip))!==false) {
                        unset($company_ip_list[$key]);
                        $company_ip_list=array_merge($company_ip_list, $ips);
                        if (in_array($real_ip, $ips)) {
                            $visit_allowed = true;
                            break;
                        }
                    }
                } elseif ($ip == $real_ip) {
                    $visit_allowed = true;
                    break;
                }
            }
        }
        
        if (!$visit_allowed) {
            sys_msg("该IP {$real_ip} 无法使用后台",0,null,false);
            exit();
        }
    }
}

function p() {
	$argvs = func_get_args();
	echo '<div style="text-align: left;">';
	foreach ($argvs as $k => $v) {
		echo "<xmp>";
		print_r($v);
		echo "</xmp>";
	}
	echo '</div>';
}
function v() {
	$argvs = func_get_args();
	echo '<div style="text-align: left;">';
	foreach ($argvs as $k => $v) {
		echo "<xmp>";
		var_dump($v);
		echo "</xmp>";
	}
	echo '</div>';
}

/**
 * 检测当前脚本是否在命令行环境中运行
 *
 * @return boolean
 */
function is_cli() {
    return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
}

/**
 * 检测是否在登录页面
 *
 * @return boolean
 */
function is_login_page() {
    return basename($_SERVER['SCRIPT_FILENAME'])=='privilege.php' && in_array($_REQUEST['act'],array('login','logout','signin'));
}

/**
 * 自动为查询的SQL加上LOG，并调用$db的指定方法
 * All Hail Sinri Edogawa!
 * @param String $action 方法名，cls_mysql里定义
 * @param String Parameter For Reference &$sql   变量，内容为sql
 */
function CallDBWithLog($action,&$sql,$database='master'){
    global $db;
    global $slave_db;

    if($database=='master'){
        $theDB=$db;
    }else{
        $theDB=$slave_db;
    }

    $trace = debug_backtrace();
    if(count($trace)==1){
        $caller=$trace[0];
    }else{
        $caller=$trace[1];
    }

    $sql=$sql.PHP_EOL."-- ".$caller['file'].":".$caller['line']."(".$caller['function'].")".PHP_EOL;

    if(method_exists($theDB,$action)){
        $result=$theDB->$action($sql);
        return $result;
    }else{
        throw new Exception("Incorrect Method [$action] Called In SinriCallDBWithLog[$database]", 1);
    }
}

set_time_limit(0);
