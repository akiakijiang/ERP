<?php

/**
 * 公用函数库
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 获得所有模块的名称以及链接地址
 *
 * @access      public
 * @param       string      $directory      插件存放的目录
 * @return      array
 */
function read_modules($directory = '.')
{
    global $_LANG, $db, $ecs;

    $dir         = @opendir($directory);
    $set_modules = true;
    $modules     = array();

    while ($file = @readdir($dir))
    {
        if (preg_match("/^.*?\.php$/", $file))
        {
            include_once($directory. '/' .$file);
        }
    }
    @closedir($dir);
    unset($set_modules);
    //    foreach ($modules AS $key => $value)
    //    {
    //        ksort($modules[$key]);
    //    }
    //    ksort($modules);

    return $modules;
}

/**
 * 系统提示信息
 *
 * @access      public
 * @param       string      msg_detail      消息内容
 * @param       int         msg_type        消息类型， 0消息，1错误，2询问
 * @param       array       links           可选的链接
 * @param       boolen      $auto_redirect  是否需要自动跳转
 * @return      void
 */
function sys_msg($msg_detail, $msg_type = 0, $links = array(), $auto_redirect = true)
{
    if (count($links) == 0)
    {
        $links[0]['text'] = $GLOBALS['_LANG']['go_back'];
        $links[0]['href'] = 'javascript:history.go(-1)';
    }

    assign_query_info();

    $GLOBALS['smarty']->assign('ur_here',     $GLOBALS['_LANG']['system_message']);
    $GLOBALS['smarty']->assign('msg_detail',  $msg_detail);
    $GLOBALS['smarty']->assign('msg_type',    $msg_type);
    $GLOBALS['smarty']->assign('links',       $links);
    $GLOBALS['smarty']->assign('default_url', $links[0]['href']);
    $GLOBALS['smarty']->assign('auto_redirect', $auto_redirect);

    $GLOBALS['smarty']->display('message.htm');

    exit;
}

/**
 * 记录管理员的操作内容
 *
 * @access  public
 * @param   string      $sn         数据的唯一值
 * @param   string      $action     操作的类型
 * @param   string      $content    操作的内容
 * @return  void
 */
function admin_log($sn = '', $action, $content)
{
    if ($GLOBALS['_LANG']['log_action'][$action]) {
        $action = $GLOBALS['_LANG']['log_action'][$action];
    }
    if ($GLOBALS['_LANG']['log_action'][$content]) {
        $content = $GLOBALS['_LANG']['log_action'][$content];
    }
    $log_info = $action . mysql_real_escape_string($content) .': '. addslashes($sn);

    $sql = 'INSERT INTO ' . $GLOBALS['ecs']->table('admin_log') . ' (log_time, user_id, log_info, ip_address) ' .
    " VALUES ('" . time() . "', $_SESSION[admin_id], '" . $log_info . "', '" . real_ip() . "')";
    $GLOBALS['db']->query($sql);
}

/**
 * 将通过表单提交过来的年月日变量合成为"2004-05-10"的格式。
 *
 * 此函数适用于通过smarty函数html_select_date生成的下拉日期。
 *
 * @param  string $prefix      年月日变量的共同的前缀。
 * @return date                日期变量。
 */
function sys_joindate($prefix)
{
    /* 返回年-月-日的日期格式 */
    $year  = empty($_POST[$prefix . 'Year']) ? '0' :  $_POST[$prefix . 'Year'];
    $month = empty($_POST[$prefix . 'Month']) ? '0' : $_POST[$prefix . 'Month'];
    $day   = empty($_POST[$prefix . 'Day']) ? '0' : $_POST[$prefix . 'Day'];

    return $year . '-' . $month . '-' . $day;
}

/**
 * 设置管理员的session内容
 *
 * @access  public
 * @param   integer $user_id        管理员编号
 * @param   string  $username       管理员姓名
 * @param   string  $action_list    权限列表
 * @param   string  $last_time      最后登录时间
 * @param   string  $allowedip_type IP访问策略
 * @param   string  $allowedip_list IP访问列表
 * @param   int     $party          标示属于哪一个party
 * 
 * @return  void
 */
function set_admin_session($user_id, $party_id, $username, $action_list, $last_time, 
                           $allowedip_type, $allowedip_list = null, $facility_id = null, $roles = null)
{
    $_SESSION['admin_id']    = $user_id;
    $_SESSION['party_id'] = $party_id;
    $_SESSION['admin_name']  = $username;
    $_SESSION['action_list'] = trim($action_list);
    $_SESSION['last_check']  = $last_time; // 用于保存最后一次检查订单的时间
    $_SESSION['allowedip_type']  = $allowedip_type; // 用于保存IP访问策略
    $_SESSION['allowedip_list']  = $allowedip_list; // 用于保存IP访问列表
    $_SESSION['facility_id'] = $facility_id;
    $_SESSION['roles'] = $roles;
}

/**
 * 插入一个配置信息
 *
 * @access  public
 * @param   string      $parent     分组的code
 * @param   string      $code       该配置信息的唯一标识
 * @param   string      $value      该配置信息值
 * @return  void
 */
function insert_config($parent, $code, $value)
{
    global $ecs, $db, $_LANG;

    $sql = 'SELECT id FROM ' . $ecs->table('shop_config') . " WHERE code = '$parent' AND type = 1";
    $parent_id = $db->getOne($sql);

    $sql = 'INSERT INTO ' . $ecs->table('shop_config') . ' (parent_id, code, value) ' .
    "VALUES('$parent_id', '$code', '$value')";
    $db->query($sql);
}

/**
 * 可输入一系列权限，如果用户拥有至少一种权限即返回true，若一种权限都不拥有则返回false，页面会跳转到无权限列表
 *
 * @return boolean
 */
function admin_priv()
{
    if ($_SESSION['action_list']=='all' || func_num_args()==0) {
        return true;
    }
    
    // 检查用户权限
    $args = func_get_args();
    array_unshift($args, $_SESSION['roles'], $_SESSION['action_list']);
    if(call_user_func_array('check_priv',$args)) {
    	return true;
    }

    // 没有权限则显示提示信息
    global $_LANG, $_CFG;
    $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');

    // 错误信息
    include_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/priv_action.php');
    $message = '<ul>';
    foreach (func_get_args() as $priv) {
        $message .= '<li>' . $_LANG[$priv] . '['. $priv .']</li>';
    }
    $message .= '</ul>';
    sys_msg($_LANG['priv_error'] . ' &nbsp&nbsp&nbsp 缺少下列权限：<br />  ' . $message, 0, $link, false);
}

/**
 * 返回数组：
 * 	array('is_allow', 'priv_list');
 * 可输入一系列权限，如果用户拥有至少一种权限is_allow值即为true，若一种权限都不拥有则为false，err_msg内为缺少的权限列表提示
 *
 * @return boolean
 */
function check_admin_priv_with_feedback()
{
	$result = array('is_allow' => false, 'err_msg' => array());
    if ($_SESSION['action_list']=='all' || func_num_args()==0) {
    	$result['is_allow'] = true;
    	return $result;
    }
    
    // 检查用户权限
    $args = func_get_args();
    array_unshift($args, $_SESSION['roles'], $_SESSION['action_list']);
    if(call_user_func_array('check_priv',$args)) {
    	$result['is_allow'] = true;
    	return $result;
    }

    // 没有权限则显示提示信息
    global $_LANG, $_CFG;
    $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');

    // 错误信息
   	$result['is_allow'] = false;
    include_once(ROOT_PATH . 'languages/' .$_CFG['lang']. '/admin/priv_action.php');
    $priv_list = array();
    foreach (func_get_args() as $priv) {
    	$priv_list[] = $_LANG[$priv] . '['. $priv .']';
    }
    $result['err_msg'] = "当前用户缺少以下权限：".implode(',', $priv_list);
   	return $result;
}

/**
 * 可传入权限列表，如果用户拥有至少一种权限即返回true，若一种权限都不拥有则返回false
 *
 */
function check_admin_priv() {
    if ($_SESSION['action_list']=='all' || func_num_args()==0) {
        return true; 
    }
    
    $args = func_get_args();
    array_unshift($args, $_SESSION['roles'], $_SESSION['action_list']);
    return call_user_func_array('check_priv',$args);
}

/**
 * 检查用户是否拥有该权限，传入用户，权限列表，如果用户拥有至少一种权限即返回true，若一种权限都不拥有则返回false
 *
 */
function check_admin_user_priv($user_name) {
    $priv = func_get_args();

    unset($priv[0]);
    $sql = "SELECT roles, action_list FROM ecs_admin_user WHERE user_name = '{$user_name}' ";
    $admin_user = $GLOBALS['db']->getRow($sql);
    if($admin_user) {
        array_unshift($priv, $admin_user['roles'], $admin_user['action_list']);
        return $admin_user['action_list'] == 'all' || call_user_func_array('check_priv', $priv);
    } else {
        return false;
    }
}

/**
 * 检查管理员权限
 *
 * @access  public
 * @param   string  $authz
 * @return  boolean
 */
function check_authz($authz)
{
    return $_SESSION['action_list'] == 'all' || check_priv($_SESSION['roles'], $_SESSION['action_list'], $authz);
}

/**
 * 检查管理员权限，返回JSON格式数剧
 *
 * @access  public
 * @param   string  $authz
 * @return  void
 */
function check_authz_json($authz)
{
    if (!check_authz($authz))
    {
        make_json_error($GLOBALS['_LANG']['priv_error']);
    }
}

/**
 * 权限检查
 *
 * @param array|string $roles
 * @param array|string $privs
 * @param string $priv
 * @param string ....
 * @return boolean
 */
function check_priv($roles = NULL, $action_list = NULL)
{
    static $role_list = array();
    
    $priv = func_get_args();
    unset($priv[0], $priv[1]);
    if (empty($priv)) {
        return true; 
    }
    
    // 检查角色
    if (!empty($roles)) {
        // 判断用户的角色
        if (!is_array($roles)) {
            $roles = array_filter(array_map('trim', explode(',', $roles)), 'strlen');
        }
        foreach ($roles as $role_id) {
            if (!isset($role_list[$role_id])) {
                $role = $GLOBALS['db']->getRow("SELECT role_id, action_list FROM {$GLOBALS['ecs']->table('admin_role')} WHERE role_id = '{$role_id}' LIMIT 1");
                $role_list[$role['role_id']] = $role ? array_filter(array_map('trim', explode(',', $role['action_list'])), 'strlen') : array() ;
            }
            if (isset($role_list[$role_id]) && !empty($role_list[$role_id]) && ($intersect = array_intersect($role_list[$role_id], $priv)) && !empty($intersect)) {
                return true;
            }
        }
    }
    
    // 检查权限
    if (!empty($action_list)) {
        if (!is_array($action_list)) {
            $action_list = array_filter(array_map('trim', explode(',', $action_list)), 'strlen');
        } 
        $intersect = array_intersect($action_list, $priv);
        return !empty($intersect);
    }
    
    return false;
}

/**
 * 取得红包类型数组（用于生成下拉列表）
 *
 * @return  array       分类数组 bonus_typeid => bonus_type_name
 * 
 * TODO 废弃的函数
 */
function get_bonus_type()
{
    $bonus = array();
    $sql = 'SELECT type_id, type_name, type_money FROM ' . $GLOBALS['ecs']->table('bonus_type') .
    ' WHERE send_type = 3';
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $bonus[$row['type_id']] = $row['type_name'].' [' .sprintf($GLOBALS['_CFG']['currency_format'], $row['type_money']).']';
    }

    return $bonus;
}

/**
 * 取得用户等级数组,按用户级别排序
 * @param   bool      $is_special      是否只显示特殊会员组
 * @return  array     rank_id=>rank_name
 */
function get_rank_list($is_special = false)
{
    $rank_list = array();
    $sql = 'SELECT rank_id, rank_name, min_points FROM ' . $GLOBALS['ecs']->table('user_rank');
    if ($is_special)
    {
        $sql .= ' WHERE special_rank = 1';
    }
    $sql .= ' ORDER BY min_points';

    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $rank_list[$row['rank_id']] = $row['rank_name'];
    }

    return $rank_list;
}

/**
 * 按等级取得用户列表（用于生成下拉列表）
 *
 * @return  array       分类数组 user_id => user_name
 */
function get_user_rank($rankid, $where)
{
    $user_list = array();
    $sql = 'SELECT user_id, user_name FROM ' . $GLOBALS['ecs']->table('users') . $where.
    ' ORDER BY user_id DESC';
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $user_list[$row['user_id']] = $row['user_name'];
    }

    return $user_list;
}

/**
 * 取得广告位置数组（用于生成下拉列表）
 *
 * @return  array       分类数组 position_id => position_name
 */
function get_position_list()
{
    $position_list = array();
    $sql = 'SELECT position_id, position_name, ad_width, ad_height '.
    'FROM ' . $GLOBALS['ecs']->table('ad_position');
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $position_list[$row['position_id']] = $row['position_name']. ' [' .$row['ad_width']. 'x' .$row['ad_height']. ']';
    }

    return $position_list;
}

/**
 * 生成编辑器
 * @param   string  input_name  输入框名称
 * @param   string  input_value 输入框值
 */
function create_html_editor($input_name, $input_value = '')
{
    global $smarty;

    $smarty->assign('FCKeditor', 'FCK编辑器在ERP中已经废除');
}

/**
 * 返回是否
 * @param   int     $var    变量 1, 0
 */
function get_yes_no($var)
{
    return empty($var) ? '<img src="images/no.gif" border="0" />' : '<img src="images/yes.gif" border="0" />';
}

/**
 * 获取地区列表的函数。
 *
 * @access  public
 * @param   int     $region_id  上级地区id
 * @return  void
 */
function area_list($region_id)
{
    $area_arr = array();

    $sql = 'SELECT * FROM ' . $GLOBALS['ecs']->table('region').
    " WHERE parent_id = '$region_id' ORDER BY region_id";
    $res = $GLOBALS['db']->query($sql);
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $row['type']  = ($row['region_type'] == 0) ? $GLOBALS['_LANG']['country']  : '';
        $row['type'] .= ($row['region_type'] == 1) ? $GLOBALS['_LANG']['province'] : '';
        $row['type'] .= ($row['region_type'] == 2) ? $GLOBALS['_LANG']['city']     : '';
        $row['type'] .= ($row['region_type'] == 3) ? $GLOBALS['_LANG']['cantonal'] : '';

        $area_arr[] = $row;
    }

    return $area_arr;
}

/**
 * 取得图表颜色
 *
 * @access  public
 * @param   integer $n  颜色顺序
 * @return  void
 */
function chart_color($n)
{
    /* 随机显示颜色代码 */
    $arr = array('33FF66', 'FF6600', '3399FF', '009966', 'CC3399', 'FFCC33', '6699CC', 'CC3366', '33FF66', 'FF6600', '3399FF');

    if ($n > 8)
    {
        $n = $n % 8;
    }

    return $arr[$n];
}

/**
 * 获得商品类型的列表
 *
 * @access  public
 * @param   integer     $selected   选定的类型编号
 * @return  string
 */
function goods_type_list($selected)
{
    $sql = 'SELECT cat_id, cat_name FROM ' . $GLOBALS['ecs']->table('goods_type') . ' WHERE enabled = 1';
    $res = $GLOBALS['db']->query($sql);

    $lst = '';
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $lst .= "<option value='$row[cat_id]'";
        $lst .= ($selected == $row['cat_id']) ? ' selected="true"' : '';
        $lst .= '>' . htmlspecialchars($row['cat_name']). '</option>';
    }

    return $lst;
}

/**
 * 取得货到付款和非货到付款的支付方式
 * @return  array('is_cod' => '', 'is_not_cod' => '')
 */
function get_pay_ids()
{
    $ids = array('is_cod' => '0', 'is_not_cod' => '0');
    $sql = 'SELECT pay_id, is_cod FROM ' .$GLOBALS['ecs']->table('payment'). ' WHERE enabled = 1';
    $res = $GLOBALS['db']->query($sql);

    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        if ($row['is_cod'])
        {
            $ids['is_cod'] .= ',' . $row['pay_id'];
        }
        else
        {
            $ids['is_not_cod'] .= ',' . $row['pay_id'];
        }
    }

    return $ids;
}

/**
 * 清空表数据
 * @param   string  $table_name 表名称
 */
function truncate_table($table_name)
{
    $sql = 'TRUNCATE TABLE ' .$GLOBALS['ecs']->table($table_name);

    return $GLOBALS['db']->query($sql);
}

/**
 *  返回字符集列表数组
 *
 * @access  public
 * @param
 *
 * @return void
 */
function get_charset_list()
{
    return array(
    'UTF8'   => 'UTF-8',
    'GB2312' => 'GB2312/GBK',
    'BIG5'   => 'BIG5',
    );
}

/**
 * 获得文章分类列表
 *
 * @access  public
 * @param   integer $selected   选定的分类ID
 * @param   bool    $re_type    返回的类型，如果为true则返回完整的select，否则返回数组
 * @param   string  $sel_name   返回的select的名称
 * @param   string  $cat_type   分类类型
 * @return  mix
 */
function article_cat_list($selected = 0, $re_type = true, $sel_name = 'article_cat', $cat_type = 1, $ext = '')
{
    $sql = 'SELECT cat_id, cat_name FROM ' .$GLOBALS['ecs']->table('article_cat') .
    ' WHERE cat_type = ' .$cat_type.' ORDER BY sort_order';
    $row = $GLOBALS['db']->getAll($sql);

    if ($re_type)
    {
        $sel = '<select name="' .$sel_name. '" '.$ext.' >';

        if (empty($row))
        {
            $sel .= '<option value="0">select please</option>';
        }

        foreach ($row AS $key => $val)
        {
            $sel .= '<option value="' .$val['cat_id'].'" ';
            $sel .= ($selected == $val['cat_id']) ? 'selected="true"' : '';
            $sel .= '>' .nl2br(htmlspecialchars($val['cat_name'])). '</option>';
        }

        $sel .= '</select>';

        return $sel;
    }
    else
    {
        $arr = array();

        foreach ($row AS $key => $val)
        {
            $arr[$val['cat_id']] = htmlspecialchars($val['cat_name']);
        }

        return $arr;
    }
}

/**
 * 创建一个JSON格式的数据
 *
 * @access  public
 * @param   string      $content
 * @param   integer     $error
 * @param   string      $message
 * @param   array       $append
 * @return  void
 */
function make_json_response($content='', $error="0", $message='', $append=array())
{
    include_once('../includes/cls_json.php');

    $json = new JSON;

    $res = array('error' => $error, 'message' => $message, 'content' => $content);

    if (!empty($append))
    {
        foreach ($append AS $key => $val)
        {
            $res[$key] = $val;
        }
    }

    $val = $json->encode($res);

    exit($val);
}

/**
 *
 *
 * @access  public
 * @param
 * @return  void
 */
function make_json_result($content, $message='', $append=array())
{
    make_json_response($content, 0, $message, $append);
}

/**
 * 创建一个JSON格式的错误信息
 *
 * @access  public
 * @param   string  $msg
 * @return  void
 */
function make_json_error($msg)
{
    make_json_response('', 1, $msg);
}

/**
 * 根据过滤条件获得排序的标记
 *
 * @access  public
 * @param   array   $filter
 * @return  array
 */
function sort_flag($filter)
{
    $flag['tag']    = 'sort_' . preg_replace('/^.*\./', '', $filter['sort_by']);
    $flag['img']    = '<img src="images/' . ($filter['sort_order'] == "DESC" ? 'sort_desc.gif' : 'sort_asc.gif') . '"/>';

    return $flag;
}

/**
 * 分页的信息加入条件的数组
 *
 * @access  public
 * @return  array
 */
function page_and_size($filter)
{
    if (isset($_REQUEST['page_size']) && intval($_REQUEST['page_size']) > 0)
    {
        $filter['page_size'] = intval($_REQUEST['page_size']);
    }
    elseif (isset($_COOKIE['ECSCP']['page_size']) && intval($_COOKIE['ECSCP']['page_size']) > 0)
    {
        $filter['page_size'] = intval($_COOKIE['ECSCP']['page_size']);
    }
    else
    {
        $filter['page_size'] = 15;
    }

    /* 每页显示 */
    $filter['page'] = (empty($_REQUEST['page']) || intval($_REQUEST['page']) <= 0) ? 1 : intval($_REQUEST['page']);

    /* page 总数 */
    $filter['page_count'] = (!empty($filter['record_count']) && $filter['record_count'] > 0) ? ceil($filter['record_count'] / $filter['page_size']) : 1;

    /* 边界处理 */
    if ($filter['page'] > $filter['page_count'])
    {
        $filter['page'] = $filter['page_count'];
    }

    $filter['start'] = ($filter['page'] - 1) * $filter['page_size'];

    return $filter;
}

/**
 *  将含有单位的数字转成字节
 *
 * @access  public
 * @param   string      $val        带单位的数字
 *
 * @return  int         $val
 */
function return_bytes($val)
{
    $val = trim($val);
    $last = strtolower($val{strlen($val)-1});
    switch($last)
    {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }

    return $val;
}


/**
 * 登录SSO
 *
 * @param string $user_name
 * @param string $password
 * @return OKUserContext 返回登录用户对象
 */
function sso_login($user_name, $password) { // RPC关联，遗弃
    global $sso_client, $application_key;
    include_once(ROOT_PATH . "admin/includes/rpc/sso/UniUserServiceClient.php");
    $login_user = new OKLoginUser();
    $login_user->setClientIp(getRealIp());
    $login_user->setUserInput($user_name);
    $login_user->setPassword($password);

    $user_context = $sso_client->loginUser($login_user, $application_key);
    return $user_context;
}

/**
 * get real ip
 *
 * @author Zandy<yzhang@oukoo.com>
 * @return ip or null
 */
function getRealIp(){
    if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $IP = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }elseif(!empty($_SERVER['HTTP_CLIENT_IP'])){
        $IP = getenv('HTTP_CLIENT_IP');
    }elseif(!empty($_SERVER['REMOTE_ADDR'])){
        $IP = $_SERVER['REMOTE_ADDR'];
    }elseif($_SERVER['HTTP_VIA']){
        $IP = $_SERVER['HTTP_VIA'];
    }else{
        $IP = null;
    }

    return trim(substr($IP,strpos($IP," ")));
}

/**
 * 可输入一系列权限，如果用户拥有至少一种权限即返回true，若一种权限都不拥有则返回false，页面会跳转到无权限列表
 *
 * @return boolean
 */
function party_priv()
{
    global $_LANG;
    require_once(ROOT_PATH . 'RomeoApi/lib_party.php');
    
    $list = func_get_args();
    foreach ($list as $party_id) {
    	$party_id = intval($party_id);
        if (party_check($party_id, $_SESSION['party_id'])) {
            return true;
        }
    }

    $link[] = array('text' => $_LANG['go_back'], 'href' => 'javascript:history.back(-1)');
    sys_msg($_LANG['priv_error'] . ' &nbsp&nbsp&nbsp 缺少Party权限', 0, $link);
    return false;
}

/**
 * 生成有PARTY条件限制的sql
 *
 * @param string $field 数据库字段
 * @param int $aro_party_id 访问请求对象所属的party
 * @param int $aco_party_id 访问控制对象所属的party
 * 
 * <code>
 *   比如我要查乐其下, 当前用户能看到的订单，(因为我不确定该用户在乐其电教还是在欧酷下)
 *   那可以这样构造sql：
 *     $sql = "SELECT * FROM orders as o WHERE ". payty_sql('o.party_id', NULL, PARTY_LEQEE);
 *   如果用户是在乐其电教的PARTY下，那他应该是看不到乐其手机的订单的
 * </code>
 * 
 * <code>
 *   只显示欧酷手机组的订单
 *     $sql = "SELECT * FROM orders WHERE ". party_sql('party_id', PARTY_OUKU_MOBILE);
 * </code>
 * 
 * @return string
 */
function party_sql($field, $aro_party_id = NULL, $aco_party_id = PARTY_ALL)
{
    if (is_null($aro_party_id)) {
        $aro_party_id = $_SESSION['party_id'];
    }
    require_once(ROOT_PATH . 'RomeoApi/lib_party.php');
    
    $check1 = party_check($aco_party_id, $aro_party_id);
    $check2 = party_check($aro_party_id, $aco_party_id);

    // 两个组织间存在包含关系
    if ($check1 || $check2) {
        $party_list = 
            $check1 ?
            party_children_list_new($aro_party_id) :
            party_children_list_new($aco_party_id) ;

        return 
            count($party_list) > 1 ? 
            db_create_in(array_keys($party_list), $field) : 
            " $field = " . key($party_list)." ";
    }
    // 两者间不存在包含关系, 这样返回的肯定是FALSE
    else {
    	if ($aro_party_id == null){
    		return " false ";
    	}
    	else {
    		return " $aro_party_id = $aco_party_id ";
    	}      
    }
}

/**
 * 取得party列表
 *
 * @param int $parent_party_id 父级party_id
 *    如果不指定，则返回所有的明确的party
 *    如果指定了，则返回该party下的子party
 * 
 * @return array
 */
function party_list($parent_party_id = NULL) {
    $list = array();
    
   	// 不指定父PARTY, 则返回所有明确的PARTY
    if (is_null($parent_party_id)) {
        foreach (party_mapping() as $party_id => $party_name) {
            if (party_explicit($party_id)) {
                $list[$party_id] = $party_name;    	
            }
        }
    }
    // 返回子PARTY
    else if (!party_explicit($parent_party_id)) {
        $list = party_children_list($parent_party_id, false);
    }
    // 返回当前的 
    else {
        $list = array($parent_party_id => party_mapping($parent_party_id));
    }

    return $list;
}

/**
 * 取得party名
 *
 * @param int $party_id 如果为NULL,则返回mapping表   party_id => name
 */
function party_mapping($party_id = null) {
	static $mapping;
	
	if (!isset($mapping)) {
		require_once(ROOT_PATH . 'RomeoApi/lib_party.php');
		
		$party_list = party_get_all_list();
		foreach ($party_list as $obj) {
            $mapping[$obj->partyId] = $obj->name;	
		}
	}
	
	// null
    if (is_null($party_id)) {
        return $mapping;
    }
    // array
    else if (is_array($party_id)) {
        foreach ($party_id as $key) {
            $names[] = isset($mapping[$key]) ? $mapping[$key] : 'UNKNOW';
        }
        return !empty($names) ? implode(', ', $names) : '' ;
    }
    // int|string
    else {
        return isset($mapping[$party_id]) ? $mapping[$party_id] : 'UNKNOW'; 
    }
}


/**
 * 检查faiclity_id限制
 *
 * @param string $field 字段
 * @param string $facility_id 仓库id
 * @return string
 */
function facility_sql($field, $faility_id = null) {
    if ($faility_id == null) {
        $faility_id = $_SESSION['facility_id'];
    }
    return db_create_in($faility_id, $field);    
}

/**
 * 返回facilityId对应的facilityname
 *
 * @param string $facility_id
 * @return string
 */
function facility_mapping($facility_id) {    
    require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
    $facility_list = facility_get_all_list();
    
    if (!empty($facility_id)) {
        $facility_id = array_filter(array_map('trim', explode(',', $facility_id)), 'strlen');
        foreach ($facility_id as $key) {
            $facility_names[] = isset($facility_list[$key]) ? $facility_list[$key]->facilityName : "" ;
        }
        return ( isset($facility_names) ? implode(",", $facility_names) : null );
    }
    
    return null;
}

/**
 * 获得当前用户的facility mapping
 *
 * @return array
 */
function get_user_facility() {
    require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
    $facility_list = facility_get_all_list();
    
    $facility_id = trim($_SESSION['facility_id']);
    if (!empty($facility_id)) {
        $facility_id = array_filter(array_map('trim', explode(',', $facility_id)), 'strlen');
        foreach ($facility_id as $key) {
            $result[$key] = isset($facility_list[$key]) ? $facility_list[$key]->facilityName : "" ;
        }
        return $result;
    }
    
    return array();
}

/**
 * 获得指定PARTY下的可用仓库列表，如果不指定PARTY，则默认为当前用户的PARTY
 *
 * @param mixed $party_list
 * @return array
 */
function get_available_facility($party_list = null) {
    if (is_null($party_list)) {
        $party_list = $_SESSION['party_id'];
    }
    if (!is_array($party_list)) {
        $party_list = array_filter(array_map('trim', explode(',', $party_list)), 'strlen');
    }
    require_once(ROOT_PATH . 'RomeoApi/lib_party.php');
    require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
    $facility_list = facility_get_all_list();
    $list = array();
    foreach ($party_list as $party_id) {
        foreach ($facility_list as $facility) {
	        if (party_check($party_id, PARTY_ALL) || party_check($facility->ownerPartyId, $party_id)) {
                $list[$facility->facilityId] = $facility->facilityName;
	        }
	    }
    }
    return $list;
}

/**
 * 取得所有外包仓
 */
function get_out_facility(){
	global $db;
	$sql = "select facility_id,facility_name from romeo.facility where is_closed='N' and is_out_ship ='Y' ";
	$out_facility_list = $db->getAll ( $sql );
	$list = array();
	foreach($out_facility_list as $out_facility){
		$list[$out_facility['facility_id']] = $out_facility['facility_name'];
	}
	return $list;
} 

/**
 * 取得所有非外包仓
 */
function get_un_out_facility(){
	global $db;
	$sql = "select facility_id,facility_name from romeo.facility where is_closed='N' and (is_out_ship != 'Y' or IS_OUT_SHIP is null or IS_OUT_SHIP = '' ) ";
	$out_facility_list = $db->getAll ( $sql );
	$list = array();
	foreach($out_facility_list as $out_facility){
		$list[$out_facility['facility_id']] = $out_facility['facility_name'];
	}
	return $list;
}

/**
 * 获取币种数组
 * 
 * @param
 * @param
 */
function get_currency_style($default_currency = 'USD') {
    global $db;
    $sql = "SELECT currency_code, description FROM romeo.currency";
    $currency = $db->getAll ( $sql );
    $arr = array ();
    $temp = array ();
    //返回数组,默认币种为$default_currency
    foreach ( $currency as $key => $cur ) {
        if ($cur ['currency_code'] != $default_currency) {
           $arr [$cur ['currency_code']] = $cur ['description'];
        } else {
           $temp [$cur ['currency_code']] = $cur ['description'];
           $arr = array_merge($temp, $arr);
        }
    }
    return $arr;
}

/**
 * 分配订单的facility_id
 *
 * @param array $order          订单信息
 * @param array $order_goods    array 订单商品，需要的键为： goods_id， style_id
 * @return string $facility_id  仓库id
 */
function assign_order_facility($order, $order_goods = null) {
    $facility_list = get_available_facility(!empty($order['party_id'])?$order['party_id']:NULL);
    return $facility_list ? key($facility_list) : 0;    
}


/**
 * 判断是否为赠品
 */
function is_gift($goods_id){
	global $db;
	
	$sql = "
	    SELECT 1 
        FROM ecshop.ecs_goods g
        INNER JOIN ecshop.ecs_category c on c.cat_id = g.cat_id and c.cat_name like '%赠品%'
        WHERE g.goods_id = '{$goods_id}'
        ";
	$result = $db -> getOne($sql);
	return $result;
}
?>