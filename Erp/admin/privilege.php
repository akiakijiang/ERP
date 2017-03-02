<?php

/**
 * 登录
 */

define('IN_ECS', true);

require('includes/init.php');

/* act操作项的初始化 */
if (empty($_REQUEST['act']))
{
    $_REQUEST['act'] = 'login';
}
else
{
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

// 分组下拉列表
$department_list= array(
    '运营' => '运营',
    '客服' => '客服',
    '仓库' => '仓库',
    '财务' => '财务',
    'ERP' => 'ERP',
    '采购' => '采购',
    'BD' => 'BD',
    '外部' => '外部'
);
$smarty->assign('department_list',$department_list);

/* 初始化 $exc 对象 */
$exc = new exchange($ecs->table("admin_user"), $db, 'user_id', 'user_name');

/*------------------------------------------------------ */
//-- 退出登录
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'logout')
{
    /* 清除cookie */
	foreach ($_COOKIE as $k => $v) {
		if ($k != 'OKTID')
			setcookie($k, '', 1, '/', COOKIE_DOMAIN);
	}
    $sess->destroy_session();

    $_REQUEST['act'] = 'login';
}

/*------------------------------------------------------ */
//-- 登陆界面
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'login')
{
    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    if ($_CFG['enable_captcha'] == 1 && gd_version() > 0)
    {
        $smarty->assign('gd_version', gd_version());
        $smarty->assign('random',     rand());
    }
    $smarty->display('login.htm');
}
/*------------------------------------------------------ */
//-- 验证登陆信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'signin')
{
	/* 去掉验证码
    if (!empty($_SESSION['captcha_word']) && intval($_CFG['enable_captcha']) > 0)
    {
        include_once(ROOT_PATH . 'includes/cls_captcha.php');

        // 检查验证码是否正确 
        $validator = new captcha();
        if (!empty($_POST['captcha']) && !$validator->check_word($_POST['captcha']))
        {
            sys_msg($_LANG['captcha_error'], 1);
        }
    }
	*/
	
    $_POST['username'] = isset($_POST['username']) ? trim($_POST['username']) : '';
    $_POST['password'] = isset($_POST['password']) ? trim($_POST['password']) : '';
    $back = $_POST['back'] == '' ? "indexV2.php" : $_POST['back'];

    /* 检查密码是否正确 */
    $sql = "SELECT user_id, user_name, password, facility_id, roles,
            action_list, last_time, allowedip_type, allowedip_list ".
            " FROM " . $ecs->table('admin_user') .
            " WHERE user_name = '" . $_POST['username']. "' AND password = '" . md5($_POST['password']) . "' AND status != 'DISABLED' ";
    $row = $db->getRow($sql);

    if ($row)
    {
        // 登录成功
        set_admin_session(
            $row['user_id'], party_get_user_default_party($row['user_id']), $row['user_name'], $row['action_list'],
            $row['last_time'], $row['allowedip_type'], $row['allowedip_list'], $row['facility_id'], $row['roles']);
        // 更新最后登录时间和IP
        $db->query("UPDATE " .$ecs->table('admin_user').
                    " SET last_time='" . date('Y-m-d H:i:s', time()) . "', last_ip='" . real_ip() . "'".
                    " WHERE user_id='$_SESSION[admin_id]'");

        if (isset($_POST['remember']) 
            // || $_POST['username']=='ljni'
        )
        {
            $time = time() + 3600 * 24 * 365;
            setcookie('AUTH[admin_id]',   $row['user_id'],                            $time, '/', COOKIE_DOMAIN);
            setcookie('AUTH[admin_pass]', md5($row['password'] . $_CFG['hash_code']), $time, '/', COOKIE_DOMAIN);
        }

        // 虽然密码是正确的，但是监工的旨意是要再看看是不是太简单了。
        if(
            strlen($_POST['password'])<8 ||
            !preg_match('/[A-Za-z]/', $_POST['password']) ||
            !preg_match('/[0-9]/', $_POST['password'])
        ){
            sys_msg('你的密码过于简单，请速速修改密码然后重新登录！',0,array(array('href'=>'./indexV2.php?target_url='.urlencode('privilege.php?act=modif'))),true);
        }else{
            header("Location: {$back}\n");
            exit;
        }
    }
    else
    {
        sys_msg($_LANG['login_faild'], 1);
    }
}

/*------------------------------------------------------ */
//-- 管理员列表页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'list')
{
    admin_priv('admin_manage', 'allot_priv');
    /* 模板赋值 */
    $lists = get_admin_userlist();
    $admin_list = array();
    foreach ($lists as $key=>$list) {
        if ($list['status'] != 'DISABLED') {
             $admin_list[$key]['user_id'] = $list['user_id'];
             $admin_list[$key]['user_name'] = $list['user_name'];
             $admin_list[$key]['department'] = $list['department'];
             $admin_list[$key]['email'] = $list['email'];
             $admin_list[$key]['join_time'] = $list['join_time'];
             $admin_list[$key]['last_time'] = $list['last_time'];
             $admin_list[$key]['real_name'] = $list['real_name'];
        }else{
             $admin_disabled_list[$key]['user_id'] = $list['user_id'];
             $admin_disabled_list[$key]['user_name'] = $list['user_name'];
             $admin_disabled_list[$key]['department'] = $list['department'];
             $admin_disabled_list[$key]['email'] = $list['email'];
             $admin_disabled_list[$key]['join_time'] = $list['join_time'];
             $admin_disabled_list[$key]['last_time'] = $list['last_time'];
             $admin_disabled_list[$key]['real_name'] = $list['real_name'];
        }
       
    }
    $smarty->assign('ur_here',     $_LANG['admin_list']);
    $smarty->assign('action_link', array('href'=>'privilege.php?act=add', 'text' => $_LANG['admin_add']));
    $smarty->assign('full_page',   1);
    $smarty->assign('admin_list',  $admin_list);
    $smarty->assign('admin_disabled_list',  $admin_disabled_list);

    /* 显示页面 */
    assign_query_info();
    $smarty->display('oukooext/privilege_list.htm');
}

/*------------------------------------------------------ */
//-- 添加管理员页面
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'add')
{
    /* 检查权限 */
    admin_priv('admin_manage');

     /* 模板赋值 */
    $smarty->assign('ur_here',     $_LANG['admin_add']);
    $smarty->assign('action_link', array('href'=>'privilege.php?act=list', 'text' => $_LANG['admin_list']));
    $smarty->assign('form_act',    'insert');
    $smarty->assign('action',      'add');

    /* 显示页面 */
    assign_query_info();
    $smarty->display('privilege_info.htm');
}

/*------------------------------------------------------ */
//-- 添加管理员的处理
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'insert')
{
    admin_priv('admin_manage');

    /* 判断管理员是否已经存在 */
    if (!empty($_POST['user_name']))
    {
        $is_only = $exc->is_only('user_name', stripslashes($_POST['user_name']));

        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['user_name_exist'], stripslashes($_POST['user_name'])), 1);
        }
    }

    /* Email地址是否有重复 */
    if (!empty($_POST['email']))
    {
        $is_only = $exc->is_only('email', stripslashes($_POST['email']));

        if (!$is_only)
        {
            sys_msg(sprintf($_LANG['email_exist'], stripslashes($_POST['email'])), 1);
        }
    }

    /* 获取添加日期及密码 */
    $join_time = date("Y-m-d H:i:s");
    $password  = md5($_POST['password']);

    $sql = "INSERT INTO ".$ecs->table('admin_user')." (user_name, email, password, join_time, real_name, status, department) ".
           "VALUES ('".trim($_POST['user_name'])."', '".trim($_POST['email'])."', '$password', '$join_time', '" . trim($_POST['real_name']) ."', 'OK','".trim($_POST['department'])."')";
    $db->query($sql);
    /* 转入权限分配列表 */
    $new_id = $db->Insert_ID();

    /*添加链接*/
    $link[0]['text'] = $_LANG['go_allot_priv'];
    $link[0]['href'] = 'privilege.php?act=allot&id='.$new_id.'&user='.$_POST['user_name'].'';

    $link[1]['text'] = $_LANG['continue_add'];
    $link[1]['href'] = 'privilege.php?act=add';

    sys_msg($_LANG['add'] . "&nbsp;" .$_POST['user_name'] . "&nbsp;" . $_LANG['action_succeed'],0, $link);

    /* 记录管理员操作 */
    admin_log($_POST['user_name'], 'add', 'privilege');
 }

/*------------------------------------------------------ */
//-- 编辑管理员信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'edit')
{
    // 无视规则可以随便控制所有业务组的人
    $is_all_parties_admin=in_array( $_SESSION['admin_name'], array( 'ljni','yxie','mjzhou','ytchen','dwliu','kqx','mzzhuo','wtgu'));

    /* 不能编辑demo这个管理员 */
    if ($_SESSION['admin_name'] == 'demo')
    {
       $link[] = array('text' => $_LANG['back_list'], 'href'=>'privilege.php?act=list');
       sys_msg($_LANG['edit_admininfo_cannot'], 0, $link);
    }

    $_REQUEST['id'] = !empty($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

    /* 查看是否有权限编辑其他管理员的信息 */
    admin_priv('admin_manage');


    /* 获取管理员信息 */
    $sql = "SELECT user_id, user_name, email, password, department ,
            allowedip_type, allowedip_list, real_name, facility_id 
            FROM " .$ecs->table('admin_user').
           " WHERE user_id = '".$_REQUEST['id']."'";
    $user_info = $db->getRow($sql);
    $user_party_list = (array)party_get_user_party_new($user_info['user_id']);
    
    // $privileged_party_list = (array)party_get_user_party_new($_SESSION['admin_id']);

    $privileged_party_list_sinri = (array)party_get_user_party_by_sinri($_SESSION['admin_id']);
    rsort($privileged_party_list_sinri);

    // echo "<pre>";
    // print_r($privileged_party_list);
    // echo "</pre>
    //     <pre>";
    // print_r($privileged_party_list_sinri);
    // echo "</pre>";
    // die();
     
    $privileged_party_list=$privileged_party_list_sinri;
 
    /* 模板赋值 */
    $smarty->assign('ur_here',     $_LANG['admin_edit']);
    $smarty->assign('action_link', array('text' => $_LANG['admin_list'], 'href'=>'privilege.php?act=list'));
    $smarty->assign('user',        $user_info);
    $smarty->assign('form_act',    'update');
    $smarty->assign('action',      'edit');
    
    if($is_all_parties_admin){
        $smarty->assign('party_options_list',  party_options_list());
    }else{           
        $smarty->assign('party_options_list',  party_options_list($privileged_party_list));
    }                  

    $smarty->assign('user_party_list', $user_party_list);
    $smarty->assign('enable_edit_party', check_admin_priv('admin_manage'));  // 是否可以更改自己的party

    $facilitys = get_available_facility($user_party_list);
    $user_facility_id = $user_info['facility_id'].",";
    $available_facility = array();
    foreach ($facilitys as $facility_id => $facility_name) {
        $owner = false;
        if (strpos($user_facility_id, $facility_id.",") !== false) {
            $owner = true;
        }
        $available_facility[$facility_id] = 
            array(
                'facility_id' => $facility_id,
                'facility_name' => $facility_name,
                'owner' => $owner,
                );
    }
    $smarty->assign('available_facility', $available_facility);

    assign_query_info();
    $smarty->display('privilege_info.htm');
}

/*------------------------------------------------------ */
//-- 更新管理员信息
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update')
{
    /* 查看是否有权限编辑其他管理员的信息 */
    if ($_SESSION['admin_id'] != $_REQUEST['id'])
    {
        admin_priv('admin_manage');
    } 

    /* 变量初始化 */
    $admin_id    = !empty($_REQUEST['id'])        ? intval($_REQUEST['id'])      : 0;
    $admin_name  = !empty($_REQUEST['user_name']) ? trim($_REQUEST['user_name']) : '';
    $real_name   = !empty($_REQUEST['real_name']) ? trim($_REQUEST['real_name']) : '';
    $department   = !empty($_REQUEST['department']) ? trim($_REQUEST['department']) : '运营';
    $admin_email = !empty($_REQUEST['email'])     ? trim($_REQUEST['email'])     : '';
    $nav_list = !empty($_POST['nav_list'])     ? ", nav_list = '".@join(",", $_POST['nav_list'])."'" : '';
    $password = !empty($_POST['new_password']) ? ", password = '".md5($_POST['new_password'])."'"    : '';
    $facility_id_sql = '';
    if (check_admin_priv('admin_manage')) {
        $facility_id = $_POST['facility_id'];
        if (is_array($facility_id)) {
            $facility_id_str = join(',', $facility_id);
        } else {
            $facility_id_str = '';
        }
        
        $facility_id_sql = ", facility_id = '{$facility_id_str}' ";
    }

    /* 判断用户名是否有重复 */
    if (!empty($admin_name))
    {
        $is_only = $exc->num('user_name', stripslashes($admin_name), $admin_id);
        if ($is_only == 1)
        {
            sys_msg(sprintf($_LANG['user_name_exist'], stripslashes($admin_name)), 1);
        }
    }

    /* Email地址是否有重复 */
    if (!empty($admin_email))
    {
        $is_only = $exc->num('email', stripslashes($admin_email), $admin_id);

        if ($is_only == 1)
        {
            sys_msg(sprintf($_LANG['email_exist'], stripslashes($admin_email)), 1);
        }
    }

    //如果要修改密码
    $pwd_modified = false;

    if (!empty($_POST['new_password']))
    {
        /* 比较新密码和确认密码是否相同 */
        if ($_POST['new_password'] <> $_POST['pwd_confirm'])
        {
           $link[] = array('text' => $_LANG['go_back'], 'href'=>'javascript:history.back(-1)');
           sys_msg($_LANG['js_languages']['password_error'], 0, $link);
        }
        else
        {
            $pwd_modified = true;
        }
    }
    
    // 如果修改了允许的ip地址
    if (isset($_POST['allowedip_type'])) 
    {
        $allowedip = ", allowedip_type = '{$_POST['allowedip_type']}', allowedip_list = '{$_POST['allowedip_list']}' ";
    }

    //更新管理员信息
    $sql = "UPDATE " .$ecs->table('admin_user'). " SET ".
           "user_name = '$admin_name', ".
           "real_name = '$real_name', ".
           "department = '$department', ".
           "email = '$admin_email'".
           $password.
           $allowedip.
           $nav_list.
           $facility_id_sql.
           "WHERE user_id = '$admin_id'";

   $db->query($sql);
    
   // 更新用户的PARTY
    $party_list = isset($_POST['party_list']) ? $_POST['party_list'] : array(); 
    party_save_user_party($admin_id, $party_list);
    
   /* 记录管理员操作 */
   admin_log($_POST['user_name'], 'edit', $sql);

   /* 如果修改了密码，则需要将session中该管理员的数据清空 */
   if ($pwd_modified)
   {
       $sess->delete_spec_admin_session($_SESSION['admin_id']);
       $msg = $_LANG['edit_password_succeed'];
   }
   else
   {
       $msg = $_LANG['edit_profile_succeed'];
   }

   /* 提示信息 */
   $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'privilege.php?act=modif');
   sys_msg($_LANG['edit'] . "$msg<script>parent.document.getElementById('header-frame').contentWindow.document.location.reload(true);</script>", 0, $link);

}

/*------------------------------------------------------ */
//-- 编辑个人资料
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'modif')
{
    /* 不能编辑demo这个管理员 */
    if ($_SESSION['admin_name'] == 'demo')
    {
       $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'privilege.php?act=list');
       sys_msg($_LANG['edit_admininfo_cannot'], 0, $link);
    }

    include_once('includes/inc_menu.php');

    /* 包含插件菜单语言项 */
    $sql = "SELECT code FROM ".$ecs->table('plugins');
    $rs = $db->query($sql);
    while ($row = $db->FetchRow($rs))
    {
        /* 取得语言项 */
        if (file_exists(ROOT_PATH.'plugins/'.$row['code'].'/languages/common_'.$_CFG['lang'].'.php'))
        {
            include_once(ROOT_PATH.'plugins/'.$row['code'].'/languages/common_'.$_CFG['lang'].'.php');
        }

        /* 插件的菜单项 */
        if (file_exists(ROOT_PATH.'plugins/'.$row['code'].'/languages/inc_menu.php'))
        {
            include_once(ROOT_PATH.'plugins/'.$row['code'].'/languages/inc_menu.php');
        }
    }

    foreach ($modules AS $key => $value)
    {
        ksort($modules[$key]);
    }
    ksort($modules);

    foreach ($modules AS $key => $val)
    {
        $menus[$key]['label'] = $_LANG[$key];
        if (is_array($val))
        {
            foreach ($val AS $k => $v)
            {
                $menus[$key]['children'][$k]['label']  = $_LANG[$k];
                $menus[$key]['children'][$k]['action'] = $v;
            }
        }
        else
        {
            $menus[$key]['action'] = $val;
        }
    }

    /* 获得当前管理员数据信息 */
    $sql = "SELECT user_id, user_name, real_name, email, nav_list ,department ".
           "FROM " .$ecs->table('admin_user'). " WHERE user_id = '".$_SESSION['admin_id']."'";
    $user_info = $db->getRow($sql);

    /* 获取导航条 */
    $nav_arr = (trim($user_info['nav_list']) == '') ? array() : explode(",", $user_info['nav_list']);
    $nav_lst = array();
    foreach ($nav_arr AS $val)
    {
        $arr              = explode('|', $val);
        $nav_lst[$arr[1]] = $arr[0];
    }
    
    /* 模板赋值 */
    $smarty->assign('lang',        $_LANG);
    $smarty->assign('ur_here',     $_LANG['modif_info']);
    $smarty->assign('action_link', array('text' => $_LANG['admin_list'], 'href'=>'privilege.php?act=list'));
    $smarty->assign('user',        $user_info);
    $smarty->assign('menus',       $modules);
    $smarty->assign('nav_arr',     $nav_lst);
    $smarty->assign('form_act',    'update');
    $smarty->assign('action',      'modif');

    /* 显示页面 */
    assign_query_info();
    $smarty->display('privilege_info.htm');
}

/*------------------------------------------------------ */
//-- 分配可分配的权限
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'privilege_manage')
{
    include_once(ROOT_PATH . 'includes/helper/array.php');
    include_once('../languages/' .$_CFG['lang']. '/admin/priv_action.php');    
    admin_priv('privilege_manage');
    
    $user = $db->getRow("SELECT user_id, user_name, action_list, privileged_list FROM {$ecs->table('admin_user')} WHERE user_id = '{$_GET[id]}'");
    if (!$user) {
    	die('找不到该用户');
    }
    if (!empty($user['privileged_list'])) {
        $user['privileged_list'] = Helper_Array::normalize($user['privileged_list']);
    }
    // 权限列表
    $sql = "SELECT action_id, parent_id, action_code FROM " .$ecs->table('admin_action'). " WHERE is_shield = 0 ";
    $action_list = $db->getAllRefby($sql, array('action_code'), $ref_files, $ref_values);
    $ref = & $ref_values['action_code'];
    // 判断用户的权限
    if (is_array($user['privileged_list'])) {
    	// 拥有all权限的管理员
    	if (in_array('all', $user['privileged_list'])) {
            foreach ($action_list as $key => $value) {
                if ($value['parent_id'] > 0) {
                    $action_list[$key]['checked'] = true;  // 权限被选中
                }
            }
    	} else {
            foreach ($user['privileged_list'] as $action_code) {
                if (isset($ref[$action_code])) {
                    foreach ($ref[$action_code] as $key => $value) {
                        if ($value['parent_id'] > 0) {
                            $ref[$action_code][$key]['checked'] = true;  // 权限被选中
                        }
                    }
                }
            }
        }
    }
    
    // 将权限列表分组
    $action_group_list = Helper_Array::toTree($action_list, 'action_id', 'parent_id', 'priv_list');
    $smarty->assign('lang',        $_LANG);
    $smarty->assign('user',        $user);
    $smarty->assign('action_group_list', $action_group_list);

    $smarty->display('oukooext/privilege_manage.htm');
}
/*------------------------------------------------------ */
//-- 为管理员分配权限
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'allot')
{
    include_once(ROOT_PATH . 'includes/helper/array.php');
    include_once('../languages/' .$_CFG['lang']. '/admin/priv_action.php');

    admin_priv('allot_priv');

    // 获得该管理员的权限 
    $user = $db->getRow("SELECT user_id, user_name, roles, action_list, privileged_list FROM " .$ecs->table('admin_user'). " WHERE user_id = '$_GET[id]'");
    if (!$user) {
    	die('找不到该用户');
    }
    if (!empty($user['roles'])) {
    	$user['roles'] = Helper_Array::normalize($user['roles']);
    }
    if (!empty($user['action_list'])) {
        $user['action_list'] = Helper_Array::normalize($user['action_list']);
    }
    
    // 取得操作者可以开启的权限
    $privileged_list = $db->getOne("SELECT privileged_list FROM {$ecs->table('admin_user')} WHERE user_id = '{$_SESSION['admin_id']}'");
    if (!empty($privileged_list)) {
        $privileged_list = Helper_Array::normalize($privileged_list);
    }
    
    // 角色列表
    $role_list = $db->getAll("SELECT role_id, role_name, action_list FROM {$ecs->table('admin_role')}");
    $role_list = Helper_Array::toHashmap((array)$role_list, 'role_id');
    // 权限列表
    $sql = "SELECT action_id, parent_id, action_code FROM " .$ecs->table('admin_action'). " WHERE is_shield = 0 ";
    $action_list = $db->getAllRefby($sql, array('action_code'), $ref_files, $ref_values);
    $ref = & $ref_values['action_code'];
    // 判断用户的权限
    if (is_array($user['action_list'])) {
    	// 拥有all权限的管理员
    	if (in_array('all', $user['action_list'])) {
            foreach ($action_list as $key => $value) {
                if ($value['parent_id'] > 0) {
                    $action_list[$key]['checked'] = true;  // 权限被选中
                }
            }
    	} else {
            foreach ($user['action_list'] as $action_code) {
                if (isset($ref[$action_code])) {
                    foreach ($ref[$action_code] as $key => $value) {
                        if ($value['parent_id'] > 0) {
                            $ref[$action_code][$key]['checked'] = true;  // 权限被选中
                        }
                    }
                }
            }
        }
    }
    
    // 计算可以编辑的权限
    if (is_array($privileged_list)) {
    	// 拥有all权限的管理员
    	if (in_array('all', $privileged_list)) {
            foreach ($action_list as $key => $value) {
                if ($value['parent_id'] > 0) {
                    $action_list[$key]['show'] = true;  // 权限被选中
                }
            }
    	} else {
            foreach ($privileged_list as $action_code) {
                if (isset($ref[$action_code])) {
                    foreach ($ref[$action_code] as $key => $value) {
                        $ref[$action_code][$key]['show'] = true;  // 权限被选中
                    }
                }
            }
        }
    }
        
    // 判断用户的角色
    if (is_array($user['roles'])) {
        foreach ($user['roles'] as $role_id) {
            if (isset($role_list[$role_id])) {
                $role_list[$role_id]['checked'] = true;  // 角色被选中
                foreach (Helper_Array::normalize($role_list[$role_id]['action_list']) as $action_code) {
                    if (isset($ref[$action_code])) {
                        foreach ($ref[$action_code] as $key => $value) {
                            if ($value['parent_id'] > 0) {
                                $ref[$action_code][$key]['checked'] = true;  // 权限被选中
                                $ref[$action_code][$key]['roled'] = true;
                                $ref[$action_code][$key]['role_name'] = $role_list[$role_id]['role_name'];
                            }
                        }
                    }
                }
            }
        }
    }
    
    // 将权限列表分组
    $action_group_list = Helper_Array::toTree($action_list, 'action_id', 'parent_id', 'priv_list');
    $smarty->assign('lang',        $_LANG);
    $smarty->assign('user',        $user);
    $smarty->assign('role_list',   $role_list);
    $smarty->assign('form_act',    'update_allot');
    $smarty->assign('action_group_list', $action_group_list);

    $smarty->display('oukooext/privilege_allot.htm');
}

/*------------------------------------------------------ */
//-- 更新管理员的权限
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update_privilege_manage')
{
    require_once(ROOT_PATH . 'includes/helper/array.php');
    admin_priv('privilege_manage');

    // 取得被操作管理员用户名与原始权限
    $admin_user = $db->getRow("SELECT user_name, privileged_list FROM " .$ecs->table('admin_user'). " WHERE user_id = '$_POST[id]'");
    $admin_name = $admin_user['user_name'];
    $origin_privileged_list = $admin_user['privileged_list'];
    if (!empty($origin_privileged_list)) {
        $origin_privileged_list = Helper_Array::normalize($origin_privileged_list);
    } else {
        $origin_privileged_list = array();
    }
    if (!$admin_name) {
    	die('管理员用户不存在');
    }
    
    // 更新管理员的权限
    $privileged_list =
        ! empty($_POST['user']['privileged_list']) && is_array($_POST['user']['privileged_list']) ? 
        $_POST['user']['privileged_list'] :
        array() ;
    
    // 计算开启的权限
    $open_list = array_diff($privileged_list, $origin_privileged_list);
    // 计算关闭的权限
    $close_list = array_diff($origin_privileged_list, $privileged_list);
    
    if (count(array_diff($open_list, $privileged_list)) > 0) {
        die('非法操作');
    }
    
    // 更新
    $sql = "UPDATE " .$ecs->table('admin_user'). " SET privileged_list = '". implode(',', $privileged_list) ."' "."WHERE user_id = '$_POST[id]'";
    $db->query($sql);

    // 记录管理员操作
    $content = "";
    if (!empty($open_list)) {
        $content .= "增加权限: " . implode(',', $open_list) . "; ";
    }
    if (!empty($close_list)) {
        $content .= "移除权限: " . implode(',', $close_list) . "; ";
    }
    admin_log(addslashes($admin_name), 'privilege manage: ', $content);

    // 提示信息
    $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'privilege.php?act=list');
    sys_msg($_LANG['edit'] . "&nbsp;" . $admin_name . "&nbsp;" . $_LANG['action_succeed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 更新管理员的权限
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'update_allot')
{
    require_once(ROOT_PATH . 'includes/helper/array.php');
    admin_priv('admin_manage');

    // 取得被操作管理员用户名与原始权限
    $admin_user = $db->getRow("SELECT user_name, action_list,roles FROM " .$ecs->table('admin_user'). " WHERE user_id = '$_POST[id]'");
    $admin_name = $admin_user['user_name'];
    $origin_action_list = $admin_user['action_list'];
    if (!empty($origin_action_list)) {
        $origin_action_list = Helper_Array::normalize($origin_action_list);
    } else {
        $origin_action_list = array();
    }
    
    $origin_roles = $admin_user['roles'];
    if(!empty($origin_roles)){
    	$role_action_list = $db->getcol("SELECT action_list FROM " .$ecs->table('admin_role'). " WHERE role_id in ($origin_roles)");
    	foreach ($role_action_list as $role_action_list_item) {
    		$role_action_list_item = Helper_Array::normalize($role_action_list_item);
    		$origin_action_list = array_merge($origin_action_list, $role_action_list_item);
    	}    	 
    	$origin_action_list = array_unique($origin_action_list); 
    }   
    
    if (!$admin_name) {
    	die('管理员用户不存在');
    }
    // 取得操作者可以开启的权限
    $privileged_list = $db->getOne("SELECT privileged_list FROM {$ecs->table('admin_user')} WHERE user_id = '{$_SESSION['admin_id']}'");
    if (!empty($privileged_list)) {
        $privileged_list = Helper_Array::normalize($privileged_list);
    } else {
        $privileged_list = array();
    }
    
    // 更新管理员的权限
    $action_list =
        ! empty($_POST['user']['action_list']) && is_array($_POST['user']['action_list']) ? 
        $_POST['user']['action_list'] :
        array() ;
    // 更新管理员的角色
    $roles =
        !empty($_POST['user']['roles']) && is_array($_POST['user']['roles']) ?
        $_POST['user']['roles'] :
        array() ;
    if ($roles) {
        // 角色列表
        $role_list = $db->getAll("SELECT role_id, action_list FROM {$ecs->table('admin_role')}");
        $role_list = Helper_Array::toHashmap((array)$role_list, 'role_id');
        foreach ($roles as $key => $role_id) {
            if (!isset($role_list[$role_id])) {
                unset($roles[$key]);
                continue; 
            }
             
            // 将角色拥有的权限添加到用户拥有的权限中
        	$action_list = array_merge($action_list, Helper_Array::normalize($role_list[$role_id]['action_list']));
        }  
        
        $action_list = array_unique($action_list);
    }
    
    // 计算开启的权限
    $open_list = array_diff($action_list, $origin_action_list);
    // 计算关闭的权限
    $close_list = array_diff($origin_action_list, $action_list);
    
    if (count(array_diff($open_list, $privileged_list)) > 0 && !in_array("all", $privileged_list)) {
        die('非法操作');
    }
    
    // 更新
    $sql = "UPDATE " .$ecs->table('admin_user'). " SET roles = '". implode(',', $roles) ."', action_list = '". implode(',', $action_list) ."' "."WHERE user_id = '$_POST[id]'";
    $afx_roles=$db->exec($sql);
    // 动态更新管理员的SESSION
    if ($_SESSION["admin_id"] == $_POST['id']) {
        $_SESSION['action_list'] = $action_list;
        $_SESSION['roles'] = implode(',', $roles);
    }

    // 记录管理员操作
    $content = "";
    if (!empty($open_list)) {
        $content .= "增加权限: " . implode(',', $open_list) . "; ";
    }
    if (!empty($close_list)) {
        $content .= "移除权限: " . implode(',', $close_list) . "; ";
    }
    admin_log(addslashes($admin_name), '设置权限: ', $content.' [DEBUG afx_roles='.$afx_roles.']');

    // 提示信息
    $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'privilege.php?act=list');
    sys_msg($_LANG['edit'] . "&nbsp;" . $admin_name . "&nbsp;" . $_LANG['action_succeed'], 0, $link);
}

/*------------------------------------------------------ */
//-- 删除一个管理员
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'remove')
{
    admin_priv('admin_manage');

    $id = intval($_GET['id']);

    /* 获得管理员用户名 */
    $admin_name = $db->getOne('SELECT user_name FROM '.$ecs->table('admin_user')." WHERE user_id='$id'");

    /* demo这个管理员不允许删除 */
    if ($admin_name == 'demo')
    {
        sys_msg($_LANG['edit_remove_cannot'], 1);
    }

//    /* ID为1的不允许删除 */
//    if ($id == 1)
//    {
//        sys_msg($_LANG['remove_cannot'], 1);
//    }

    if ($exc->edit("status = 'DISABLED'",$id)) {
        $sess->delete_spec_admin_session($id); // 删除session中该管理员的记录
        admin_log(addslashes($admin_name), 'remove', 'privilege');
        clear_cache_files();
    }
    /*if ($exc->drop($id))
    {
        $sess->delete_spec_admin_session($id); // 删除session中该管理员的记录
        admin_log(addslashes($admin_name), 'remove', 'privilege');
        clear_cache_files();
    }*/

    $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'privilege.php?act=list');
    sys_msg("删除完毕",0,$link);
}
/*---------------------------------------------------*/
//恢复用户
/*---------------------------------------------------*/
elseif ($_REQUEST['act'] == 'recover') {
    admin_priv('admin_manage');
    $id = trim($_REQUEST['id']);
    $admin_name = $db->getOne('SELECT user_name FROM '.$ecs->table('admin_user')." WHERE user_id='$id'");
    $sql = "UPDATE " .$ecs->table('admin_user') ." SET status = 'OK' WHERE user_id = '$id'";
    $db->query($sql);
    admin_log(addslashes($admin_name), 'recover', "用户权限恢复 ");
    $link[] = array('text' => $_LANG['back_admin_list'], 'href'=>'privilege.php?act=list');
    sys_msg("用户已恢复",0,$link);
}

/* 获取管理员列表 */
function get_admin_userlist()
{
    $list = array();
    $sql  = 'SELECT user_id, user_name, email, join_time, last_time, real_name ,status,department '.
            'FROM ' .$GLOBALS['ecs']->table('admin_user').' ORDER BY user_id DESC';
    $list = $GLOBALS['db']->getAll($sql);

    return $list;
}


?>
