<?php

/**
 * ECSHOP 配送区域管理程序
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
 * $Id: shipping_area.php 8272 2007-04-19 10:11:35Z paulgao $
*/

define('IN_ECS', true);

require('includes/init.php');
$exc = new exchange($ecs->table('shipping_area'), $db, 'shipping_area_id', 'shipping_area_name');

/*------------------------------------------------------ */
//-- 配送区域列表
/*------------------------------------------------------ */

// 设置配送方式在该区域是否启用
if($_REQUEST['act'] == 'changestatus') {
  $db->query("update {$ecs->table('shipping_area')} set enabled = 1 - enabled where shipping_area_id = '{$_REQUEST['id']}'");
//  $lnk[] = array('text' => $_LANG['back_list'], 'href'=>$_SERVER['HTTP_REFERER']);
//  sys_msg('修改成功', 0, $lnk);
  $back_url =  $_REQUEST['shipping'] ? "shipping_area.php?act=list&shipping={$_REQUEST['shipping']}" : $_SERVER['HTTP_REFERER'];
  header("Location: " . $back_url);
}

if ($_REQUEST['act'] == 'list')
{
    $shipping_id = intval($_REQUEST['shipping']);

    $list = get_shipping_area_list($shipping_id);
    $smarty->assign('areas',    $list);

    $shipping_name = $db->getOne("SELECT shipping_name FROM {$ecs->table('shipping')} WHERE shipping_id = '$shipping_id'");
    
//    $shipping_name = $exc->get_name($shipping_id);
    
    $smarty->assign('ur_here',  '<a href="shipping.php?act=list">'.
        $_LANG['04_shipping_list'].'</a> - ' . $_LANG['shipping_area_list'] . '[' . $shipping_name.']' . '</a>');
    $smarty->assign('action_link', array('href'=>'shipping_area.php?act=add&shipping='.$shipping_id,
        'text' => $_LANG['new_area']));
    $smarty->assign('full_page', 1);

    assign_query_info();
    $smarty->display('shipping_area_list.htm');
}

/*------------------------------------------------------ */
//-- 新建配送区域
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add' && !empty($_REQUEST['shipping']))
{
    admin_priv('shiparea_manage');
    
    $shipping = $db->getRow("SELECT shipping_id, shipping_name, shipping_code FROM " .$ecs->table('shipping'). " WHERE shipping_id='$_REQUEST[shipping]'");
    
    $set_modules = 1;
    include_once(ROOT_PATH.'includes/modules/shipping/'.$shipping['shipping_code'].'.php');
    
    
    $fields = array();
    foreach ($modules[$shipping['shipping_id']]['configure'] AS $key => $val)
    {
        $fields[$key]['name']   = $val['name'];
        $fields[$key]['value']  = $val['value'];
        $fields[$key]['label']  = $val['label'];
    }
    
    /*
    $count = count($fields);
    $fields[$count]['name']     = "free_money";
    $fields[$count]['value']    = "0";
    $fields[$count]['label']    = $_LANG["free_money"];
	*/
    /* 如果支持货到付款，则允许设置货到付款支付费用 */
    /*
    if ($modules[$shipping['shipping_id']]['cod'])
    {
        $count++;
        $fields[$count]['name']     = "pay_fee";
        $fields[$count]['value']    = "0";
        $fields[$count]['label']    = $_LANG['pay_fee'];
    }
    $shipping_area['shipping_id']   = 0;
    $shipping_area['free_money']    = 0;
	*/
    
    $smarty->assign('ur_here',          $shipping['shipping_name'] .' - '. $_LANG['new_area']);
    $smarty->assign('shipping_area',    array('shipping_id' => $_REQUEST['shipping']));
    $smarty->assign('fields',           $fields);
    $smarty->assign('form_action',      'insert');
    $smarty->assign('countries',        get_regions());
    $smarty->assign('default_country',  $_CFG['shop_country']);
    assign_query_info();
    $smarty->display('shipping_area_info.htm');
}

elseif ($_REQUEST['act'] == 'insert')
{
    admin_priv('shiparea_manage');

    /* 检查同类型的配送方式下有没有重名的配送区域 */
    $sql = "SELECT COUNT(*) FROM " .$ecs->table("shipping_area").
            " WHERE shipping_id='$_POST[shipping]' AND shipping_area_name='$_POST[shipping_area_name]'";
    if ($db->getOne($sql) > 0)
    {
        sys_msg($_LANG['repeat_area_name'], 1);
    }
    else
    {
        $shipping = $db->getRow("SELECT * FROM {$ecs->table('shipping')} WHERE shipping_id='$_POST[shipping]'");
        $plugin        = '../includes/modules/shipping/'. $shipping['shipping_code']. ".php";

        if (!file_exists($plugin))
        {
            sys_msg($_LANG['not_find_plugin'], 1);
        }
        else
        {
            $set_modules = 1;
            include_once($plugin);
        }

        $config = array();
        foreach ($modules[$shipping['shipping_id']]['configure'] AS $key => $val)
        {
            $config[$key]['name']   = $val['name'];
            $config[$key]['value']  = $_POST[$val['name']];
        }

        /*
        $count = count($config);
        $config[$count]['name']     = 'free_money';
        $config[$count]['value']    = $_POST['free_money'];
		*/
        
        /* 如果支持货到付款，则允许设置货到付款支付费用 */
        /*
        if ($modules[0]['cod'])
        {
            $count++;
            $config[$count]['name']     = 'pay_fee';
            $config[$count]['value']    = make_semiangle($_POST['pay_fee']);
        }
		*/
        $sql = "INSERT INTO " .$ecs->table('shipping_area').
                " (shipping_area_name, shipping_id, configure) ".
                "VALUES".
                " ('$_POST[shipping_area_name]', '$_POST[shipping]', '" .serialize($config). "')";

        $db->query($sql);

        $new_id = $db->insert_Id();

        /* 添加选定的城市和地区 */
        if (isset($_POST['regions']) && is_array($_POST['regions']))
        {
            foreach ($_POST['regions'] AS $key => $val)
            {
                $sql = "INSERT INTO ".$ecs->table('area_region')." (shipping_area_id, region_id) VALUES ('$new_id', '$val')";
                $db->query($sql);
            }
        }

        admin_log($_POST['shipping_area_name'], 'add', 'shipping_area');

        //$lnk[] = array('text' => $_LANG['add_area_region'], 'href'=>'shipping_area.php?act=region&id='.$new_id);
        $lnk[] = array('text' => $_LANG['back_list'], 'href'=>'shipping_area.php?act=list&shipping='.$_POST['shipping']);
        $lnk[] = array('text' => $_LANG['add_continue'], 'href'=>'shipping_area.php?act=add&shipping='.$_POST['shipping']);
        sys_msg($_LANG['add_area_success'], 0, $lnk);
    }
}

/*------------------------------------------------------ */
//-- 编辑配送区域
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit')
{
    admin_priv('shiparea_manage');

    $sql = "SELECT a.shipping_name, a.shipping_code, a.support_cod, b.* ".
            "FROM " .$ecs->table('shipping'). " AS a, " .$ecs->table('shipping_area'). " AS b ".
            "WHERE b.shipping_id=a.shipping_id AND b.shipping_area_id='$_REQUEST[id]'";
    $row = $db->getRow($sql);
    $set_modules = 1;
    include_once(ROOT_PATH.'includes/modules/shipping/'.$row['shipping_code'].'.php');
    $real_fields = unserialize($row['configure']);

    // 先从common.php文件中读取fields数组
    foreach ($modules[$shipping['shipping_id']]['configure'] AS $key => $val)
    {
        $fields[$key]['name']   = $val['name'];
        $fields[$key]['value']  = $val['value'];
        $fields[$key]['label']  = $val['label'];
    }
    
    // 再将数据库中的数据填入
    foreach ($fields as $key => $field) {
    	foreach ($real_fields as $real_key => $real_field) {
    		if ($real_field['name'] == $field['name']) {
    			$fields[$key]['value'] = $real_field['value'];
    		}
    	}
    }
    
    /* 如果配送方式支持货到付款并且没有设置货到付款支付费用，则加入货到付款费用 */
    /*
    if ($row['support_cod'] && $fields[count($fields)-1]['name'] != 'pay_fee')
    {
        $fields[] = array('name'=>'pay_fee', 'value'=>0);
    }
	*/
    
    /* 获得该区域下的所有地区 */
    $regions = array();

    $sql = "
		SELECT a.region_id,a.arrived, r.* 
		FROM {$ecs->table('area_region')} AS a, {$ecs->table('region')} AS r 
		WHERE r.region_id=a.region_id AND a.shipping_area_id = '$_REQUEST[id]'
	";
    $res = $db->query($sql);
	$r = new stdclass();
	$r->region_name = '';
	$r->region_id = '';
	$r->arrived = '';
	$r->city_list = array();
	
	//把取出的数组层级化成紧凑的父子级关系
    while ($arr = $db->fetchRow($res))
    {
		switch ($arr['region_type']) {
			case 1:
				$r->region_name = $arr['region_name'];
				$r->region_id = $arr['region_id'];
				$r->arrived = $arr['arrived'];
				break;
			case 2:
				$tmp = new stdclass();
				$tmp->region_name = $arr['region_name'];
				$tmp->region_id = $arr['region_id'];
				$tmp->arrived = $arr['arrived'];
				$tmp->town_list = array();
				$r->city_list[$arr['region_id']] = $tmp;
				break;
			case 3:
				$tmp = new stdclass();
				$tmp->region_name = $arr['region_name'];
				$tmp->region_id = $arr['region_id'];
				$tmp->arrived = $arr['arrived'];
				$parent_id = $arr['parent_id'];
				$r->city_list[$parent_id]->town_list[$arr['region_id']] = $tmp;
				break;

		}
    }
//    pp($regions);
    assign_query_info();
    $smarty->assign('ur_here',          $row['shipping_name'] .' - '. $_LANG['edit_area']);
    $smarty->assign('id',               $_REQUEST['id']);
    $smarty->assign('fields',           $fields);
    $smarty->assign('shipping_area',    $row);
    $smarty->assign('regions',          $regions);
    $smarty->assign('r',                $r);
    $smarty->assign('form_action',      'update');
    $smarty->assign('countries',        get_regions());
    $smarty->assign('default_country',  1);
    $smarty->display('shipping_area_info.htm');
}

elseif ($_REQUEST['act'] == 'update')
{
    admin_priv('shiparea_manage');

    /* 检查同类型的配送方式下有没有重名的配送区域 */
        $sql = "SELECT COUNT(*) FROM " .$ecs->table("shipping_area").
            " WHERE shipping_id='$_POST[shipping]' AND ".
                    "shipping_area_name='$_POST[shipping_area_name]' AND ".
                    "shipping_area_id<>'$_POST[id]'";
    if ($db->getOne($sql) > 0)
    {
        sys_msg($_LANG['repeat_area_name'], 1);
    }
    else
    {
        $shipping = $db->getRow("SELECT * FROM " .$ecs->table('shipping'). " WHERE shipping_id='$_POST[shipping]'");
        $plugin        = '../includes/modules/shipping/'. $shipping['shipping_code']. ".php";

        if (!file_exists($plugin))
        {
            sys_msg($_LANG['not_find_plugin'], 1);
        }
        else
        {
            $set_modules = 1;
            include_once($plugin);
        }
        
        $config = array();
        foreach ($modules[$shipping['shipping_id']]['configure'] AS $key => $val)
        {
            $config[$key]['name']   = $val['name'];
            $config[$key]['value']  = $_POST[$val['name']];
	}
	$sql = "UPDATE " .$ecs->table('shipping_area').
			" SET shipping_area_name='$_POST[shipping_area_name]', ".
				"configure='" .serialize($config). "' ".
			"WHERE shipping_area_id='$_POST[id]'";

	$db->query($sql);
	admin_log($_POST['shipping_area_name'], 'edit', 'shipping_area');

	//更新到达情况
	$req = $_REQUEST;
	$area_region_id = $_REQUEST['id'];
	
	//正则匹配式
	$p_area_input_name = '~area_(\d+)_([2|3])_(\d+)~';
	$sql_available_change = array();
	
	$change_array = array();
	
	//形成结构化层级化的数组
	foreach ($req as $key => $val) {
		if (preg_match($p_area_input_name, $key, $match)) {

			unset($match[0]);
			$match['region_id'] = $match[1];
			$match['type'] = $match[2];
			$match['parent_id'] = $match[3];
			$match['arrived'] = $_REQUEST["{$key}"];
			$match['child'] = array();
			unset($match[0]);
			unset($match[1]);
			unset($match[2]);
			unset($match[3]);
			//var_export($match);

			if ($match['type'] == 2) {
				$change_array[$match['region_id']] = $match;
			} elseif ($match['type'] == 3) {
				unset($match['child']);
				$change_array[$match['parent_id']]['child'][] = $match;
			}

		}
	}
	//pp($change_array);
	//修正arrived的错误输入，主要思想是回溯，即若子孙全部可达，则父辈全部可达，若子孙全部不可达，则父辈不可达
	foreach ($change_array as $k => $v) {
		if ($v['type'] == 2 && !empty($v['child'])) {
			$if_all_{$k} = true;//是否全部可达
			$if_none_{$k} = true;//是否不到达
			foreach ($v['child'] as $key => $val) {
				if ($val['arrived'] != 'ALL') {
					$if_all_{$k} = false;
					break;
				}
			}

			foreach ($v['child'] as $key => $val) {
				if ($val['arrived'] != 'NONE') {
					$if_none_{$k} = false;
					break;
				}
			}

			if ($if_all_{$k} == true) {
				$change_array[$k]['arrived'] = 'ALL';
			} elseif ($if_none_{$k} == true) {
				$change_array[$k]['arrived'] = 'NONE';
			} else {
				$change_array[$k]['arrived'] = 'PARTLY';
			}
		}
	}
	
	//pp($change_array);
	//exit;
	//更新数据库
	foreach ($change_array as $key => $val) {
		$arrived = $val['arrived'];
		$region_id = $val['region_id'];
		$arrived == '' ? $arrived = 'PARTLY': $arrived;
		$sql = "UPDATE {$ecs->table('area_region')} AS A SET `arrived` = '{$arrived}' WHERE A.`shipping_area_id` = '{$area_region_id}' AND A.`region_id` = '{$region_id}';";
		//echo $sql."\n";
		$db->query($sql);
		if ($val['type'] == 2) {
			foreach ($val['child'] as $k => $v) {
				//var_export($v);
				$arrived1 = $v['arrived'];
				$arrived1 == '' ? $arrived1 = 'PARTLY': $arrived1;
				$region_id1 = $v['region_id'];
				$sql1 = "UPDATE {$ecs->table('area_region')} AS A SET `arrived` = '{$arrived1}' WHERE A.`shipping_area_id` = '{$area_region_id}' AND A.`region_id` = '{$region_id1}';";
				//echo $sql1."\n";
				$db->query($sql1);
			}
		}
	}
        /* 过滤掉重复的region */
        $selected_regions = array();
        if (isset($_POST['regions']))
        {
            foreach ($_POST['regions'] AS $region_id)
            {
                $selected_regions[$region_id] = $region_id;
            }
        }

        $sql = "SELECT * FROM {$ecs->table('area_region')} WHERE shipping_area_id = '{$_POST['id']}'";

        $region_list = $db->getAll($sql);
        foreach ($region_list as $k => $v){
            $id = $v['region_id'];
            if (isset($selected_regions[$id])){
                unset($selected_regions[$id]);
            }
        }

//         查询所有区域 region_id => parent_id
//        $sql = "SELECT region_id, parent_id FROM " . $ecs->table('region');
//        $res = $db->query($sql);
//        while ($row = $db->fetchRow($res))
//        {
//            $region_list[$row['region_id']] = $row['parent_id'];
//        }

//         过滤掉上级存在的区域
//        foreach ($selected_regions AS $region_id)
//        {
//            $id = $region_id;
//            while ($region_list[$id] != 0)
//            {
//                $id = $region_list[$id];
//                if (isset($selected_regions[$id]))
//                {
//                    unset($selected_regions[$region_id]);
//                    break;
//                }
//            }
//        }
//        /* 清除原有的城市和地区 */
//        $db->query("DELETE FROM ".$ecs->table("area_region")." WHERE shipping_area_id='$_POST[id]'");

        /* 添加选定的城市和地区 */
        if(empty($selected_regions)){
            sys_msg('所选择的地区已经添加', 0, $lnk);
            exit();
        }else{
            foreach ($selected_regions AS $key => $val)
            {
				$region_id = $val;
				$p_region = get_parent_region($region_id);
				if ($p_region['region_type'] == 0) {
					//parent区域的type为0（中国），添加的为省级地区，不合理添加，不插入
					//修复操作
					$sql = "SELECT * FROM ".$ecs->table('area_region')." WHERE shipping_area_id = '{$_POST[id]}' AND region_id = '{$region_id}';";
					$in_table = $db->getRow($sql);
					if (empty($in_table)){
						$sql = "INSERT INTO " .$ecs->table('area_region')." (shipping_area_id, region_id) VALUES('{$_POST[id]}','{$region_id}');";
						$db->query($sql);
					}
				} elseif ($p_region['region_type'] == 1) {
					//添加的为市级地区
					$sql = "INSERT INTO ".$ecs->table('area_region')." (shipping_area_id, region_id) VALUES ('$_POST[id]', '$val')";
					$db->query($sql);
				} elseif ($p_region['region_type'] == 2) {
					//添加的为区县级地区，判断是否其父级地区已经添加入 area_region 中
					$p_id = $p_region['region_id'];
					$sql = "SELECT * FROM " . $ecs->table('area_region') . " WHERE shipping_area_id = " . $_POST['id'] . " AND region_id = {$p_id};";

					$re = $db->getRow($sql);
					if (!empty($re)) {
						//父级已经插入，执行插入操作
						$sql = "INSERT INTO ".$ecs->table('area_region')." (shipping_area_id, region_id) VALUES ('$_POST[id]', '$val')";
						$db->query($sql);
					}
				}
            }
    
	        $lnk[] = array('text' => $_LANG['back_list'], 'href'=>'shipping_area.php?act=edit&id='.$_POST['id']);
            sys_msg($_LANG['edit_area_success'], 0, $lnk);
        }
    }
}

/*------------------------------------------------------ */
//-- 批量删除配送区域
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'multi_remove')
{
    admin_priv('shiparea_manage');

    if (isset($_POST['areas']) && count($_POST['areas']) > 0)
    {
        $i = 0;
        foreach ($_POST['areas'] AS $v)
        {
            $db->query("DELETE FROM " .$ecs->table('shipping_area'). " WHERE shipping_area_id='$v'");
            $i++;
        }

        /* 记录管理员操作 */
        admin_log('', 'batch_remove', 'shipping_area');
    }
    /* 返回 */
    $links[0] = array('href'=>'shipping_area.php?act=list&shipping=' . intval($_REQUEST['shipping']), 'text' => $_LANG['go_back']);
    sys_msg($_LANG['remove_success'], 0, $links);
}

/*------------------------------------------------------ */
//-- 编辑配送区域名称
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_area')
{
    /* 检查权限 */
    check_authz_json('shiparea_manage');

    /* 取得参数 */
    $id  = intval($_POST['id']);
    $val = trim($_POST['val']);

    /* 取得该区域所属的配送id */
    $shipping_id = $exc->get_name($id, 'shipping_id');

    /* 检查是否有重复的配送区域名称 */
    if (!$exc->is_only('shipping_area_name', $val, $id, "shipping_id = '$shipping_id'"))
    {
        make_json_error($_LANG['repeat_area_name']);
    }

    /* 更新名称 */
    $exc->edit("shipping_area_name = '$val'", $id);

    /* 记录日志 */
    admin_log($val, 'edit', 'shipping_area');

    /* 返回 */
    make_json_result(stripcslashes($val));
}

/*------------------------------------------------------ */
//-- 删除配送区域
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'remove_area')
{
    check_authz_json('shiparea_manage');

    $id = intval($_GET['id']);
    $name = $exc->get_name($id);
    $shipping_id = $exc->get_name($id, 'shipping_id');

    $exc->drop($id);

    admin_log($name, 'remove', 'shipping_area');

    $list = get_shipping_area_list($shipping_id);
    $smarty->assign('areas', $list);
    make_json_result($smarty->fetch('shipping_area_list.htm'));
}

/*------------------------------------------------------ */
//-- 搜索地区区域
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'search')
{
	$region_name = trim($_REQUEST['region_name']);
	$sql = "SELECT * FROM {$ecs->table('region')} WHERE region_name LIKE '%$region_name%'";
	$region = $db->getRow($sql);
	if ($region == null)
		die("没有搜索到$region_name");
	$result[] = $region['region_name'];
	while ($region['parent_id'] > 0) {
		$sql = "SELECT * FROM {$ecs->table('region')} WHERE region_id = '{$region['parent_id']}'";
		$region = $db->getRow($sql);
		$result[] = $region['region_name'];
	}
	$result = array_reverse($result);
	echo join(' => ', $result);
	exit();
}

/**
 * 取得配送区域列表
 * @param   int     $shipping_id    配送id
 */
function get_shipping_area_list($shipping_id)
{
    $sql = "SELECT * FROM " . $GLOBALS['ecs']->table('shipping_area');
    if ($shipping_id > 0)
    {
        $sql .= " WHERE shipping_id = '$shipping_id'";
    }
    $res = $GLOBALS['db']->query($sql);
    $list = array();
    while ($row = $GLOBALS['db']->fetchRow($res))
    {
        $sql = "SELECT r.region_name " .
                "FROM " . $GLOBALS['ecs']->table('area_region'). " AS a, " .
                    $GLOBALS['ecs']->table('region') . " AS r ".
                "WHERE a.region_id = r.region_id ".
                "AND a.shipping_area_id = '$row[shipping_area_id]'";
        $regions = join(', ', $GLOBALS['db']->getCol($sql));

        $row['shipping_area_regions'] = empty($regions) ?
            '<a href="shipping_area.php?act=region&amp;id=' .$row['shipping_area_id'].
            '" style="color:red">' .$GLOBALS['_LANG']['empty_regions']. '</a>': $regions;
        $list[] = $row;
    }
    return $list;
}

/**
 * 获取父地区
 * @param int $region_id
 */
function get_parent_region($region_id) {
	$sql = "select * from " . $GLOBALS['ecs']->table('region') . " where region_id = (select parent_id from " . $GLOBALS['ecs']->table('region') . " where region_id={$region_id});";
	$region = $GLOBALS['db']->getRow($sql);
	return $region;
}
?>
