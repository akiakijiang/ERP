<?php
/**
 * 首页
 */

define('IN_ECS', true);

require('includes/init.php');

if (PARTY_COLOR === TRUE) {
    $border_color = "style=\"background:". BORDER_COLOR ."\";";
    $padding_color =  "style=\"background:".PADDING_COLOR."\";";
} else {
    $border_color = "style=\"background:#278296;\";";
    $padding_color =  "style=\"background:#80BDCB\";";
}

//特殊权限，改变菜单栏显示，如想添加可在此多加一个字段并在exclude_parties.php中增加一个相应类
$exclude_role = array('third_party_warehouse','zhongliang_ERP_system','ecco_ERP_system');
$my_role = "";
//判断该用户是否拥有中粮或者第三方仓库权限，有则返回相应的role
foreach($exclude_role as $role){
    if(check_admin_priv($role)){
    	$my_role = $role;
    	break;
    }
}
$smarty->assign("border_color", $border_color);
$smarty->assign("padding_color", $padding_color);
/*------------------------------------------------------ */
//-- 框架
/*------------------------------------------------------ */
if ($_REQUEST['act'] == '')
{
    header("Location: ./indexV2.php");
exit;
    $smarty->display('index.htm');
}

/*------------------------------------------------------ */
//-- 顶部框架的内容
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'top')
{
    $user = $db->getRow('SELECT user_id, nav_list FROM ' . $ecs->table('admin_user') . " WHERE user_id = '" . $_SESSION['admin_id'] . "'");

    // 可以切换的party列表
    $user_party_list = party_get_user_party_new($user['user_id']);
    $party_options_list = party_options_list($user_party_list);
    // 获得管理员设置的菜单 
    $lst = array();
    if (!empty($user['nav_list'])) {
        $arr = explode(',', $user['nav_list']);

        foreach ($arr AS $val) {
            $tmp = explode('|', $val);
            $lst[$tmp[1]] = $tmp[0];
        }
    }
    
    // 特殊的权限要屏蔽上面的菜单，如中粮ERP系统权限和北京第三方外包权限
    $is_third_party_warehouse = $my_role && ($_SESSION['action_list']!='all');
    
    $real_name = $db->getOne("select real_name from ecshop.ecs_admin_user where user_name = '".$_SESSION['admin_name']."'");
    
    // 获得管理员ID
    $smarty->assign('nav_list',   $lst);
    $smarty->assign('party_options_list', $party_options_list);
    $smarty->assign('user_name',  $real_name);
    $smarty->assign('user_current_party_id', $_SESSION['party_id']);
    $smarty->assign('user_current_party_name', party_mapping($user_party_list));
    $smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
    $smarty->assign('is_third_party_warehouse', $is_third_party_warehouse);
    
    $smarty->display('top.htm');
}

/*------------------------------------------------------ */
//-- 切换Party
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'party') 
{	
    $user = $db->getRow('SELECT user_id FROM '. $ecs->table('admin_user') ." WHERE user_id = '" . $_SESSION['admin_id'] . "'");
    if ($user) {
	    $user_party_list = (array)party_get_user_party_new($user['user_id']);
	    
		// 切换party需要有相应的权限
		if ($_SESSION['party_id'] && isset($_GET['party_id']) && in_array($_GET['party_id'], $user_party_list)) {
			$_SESSION['party_id'] = $_GET['party_id'];
		}
		
		print $_SESSION['party_id'];
    }
    exit;    
}

/*------------------------------------------------------ */
//-- 计算器
/*------------------------------------------------------ */

/* 
elseif ($_REQUEST['act'] == 'calculator')
{
    $smarty->display('calculator.htm');
}
*/

/*------------------------------------------------------ */
//-- 左边的框架
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'menu')
{
    include_once('includes/inc_menu.php');
	
	
    foreach ($modules AS $key => $value)
    {
        ksort($modules[$key]);
    }
    
    foreach ($modules AS $key => $val)
    {
    	
 
		//判断是否为中粮或第三方仓库权限且不为all，那么就用对应的菜单栏
    	if($my_role && ($_SESSION['action_list']!='all')){
    		require_once('exclude_parties.php');
    		//创建对应类
    		$role = new $my_role();
    		//获取菜单栏
    		$facility_menu_list = $role ->menuList();
    		$facility_menu = array_keys($facility_menu_list);
			if (in_array($key, $facility_menu)) {
				$menus[$key]['label'] = $_LANG[$key];
				if (is_array($val)) {
		            foreach ($val AS $k => $v) {
		            	if (in_array($k, $facility_menu_list[$key])) {
		            		$menus[$key]['children'][$k]['label']  = $_LANG[$k];
		            		if(is_array($v)){
					        	foreach ($v AS $k2 => $v2)
					            {
					                $menus[$key]['children'][$k]['children2'][$k2]['label']  = $_LANG[$k2];
					                $menus[$key]['children'][$k]['children2'][$k2]['action'] = $v2;
					            }
		            		}else{
		            			$menus[$key]['children'][$k]['action'] = $v;
		            		}
		            	}
		            }
		        } else {
		            $menus[$key]['action'] = $val;
		        }
			}
		} else {
			$menus[$key]['label'] = $_LANG[$key];
	        if (is_array($val))
	        {
	            foreach ($val AS $k => $v)
	            {
	                $menus[$key]['children'][$k]['label']  = $_LANG[$k];
			        if (is_array($v))
			        {
			        	foreach ($v AS $k2 => $v2)
			            {
			            	ksort($modules[$key]);
			                $menus[$key]['children'][$k]['children2'][$k2]['label']  = $_LANG[$k2];
			                $menus[$key]['children'][$k]['children2'][$k2]['action'] = $v2;
			            }
			            foreach ($v AS $k2 => $v2)
			            {
			                $menus[$key]['children'][$k]['children2'][$k2]['label']  = $_LANG[$k2];
			                $menus[$key]['children'][$k]['children2'][$k2]['action'] = $v2;
			            }
			        } 
			        else 
			        {
			        	$menus[$key]['children'][$k]['action'] = $v;
			        }
	            }
	        }
	        else
	        {
	            $menus[$key]['action'] = $val;
	        }
		}
    }
    $smarty->assign('menus',     $menus);
    $smarty->assign('no_help',   $_LANG['no_help']);
    $smarty->assign('help_lang', $_CFG['lang']);
    $smarty->display('menu.htm');
}

/*------------------------------------------------------ */
//-- 清除缓存
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'clear_cache')
{
    clear_all_files();

    sys_msg($_LANG['caches_cleared']);
}

/*------------------------------------------------------ */
//-- 主窗口，起始页
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'main')
{
    $smarty->display('start.htm');
}

/*------------------------------------------------------ */
//-- 拖动的帧
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'drag')
{
    $smarty->display('drag.htm');;
}

/*------------------------------------------------------ */
//-- 检查订单
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'check_order')
{
    if (empty($_SESSION['last_check']))
    {
        $_SESSION['last_check'] = date('Y-m-d H:i:s');

        make_json_result('', '', array());
    }

    /* 新订单 */
    $sql = 'SELECT COUNT(*) FROM ' . $ecs->table('order_info').
            " WHERE order_time >= '$_SESSION[last_check]'";
    $arr['new_orders'] = $db->getOne($sql);

    /* 新付款的订单 */
    $sql = 'SELECT COUNT(*) FROM '.$ecs->table('order_info').
            ' WHERE pay_time >= ' . strtotime($_SESSION['last_check']);
    $arr['new_paid'] = $db->getOne($sql);

    $_SESSION['last_check'] = date('Y-m-d H:i:s');

    if (!(is_numeric($arr['new_orders']) && is_numeric($arr['new_paid'])))
    {
        make_json_error($db->error());
    }
    else
    {
        make_json_result('', '', $arr);
    }
}

?>