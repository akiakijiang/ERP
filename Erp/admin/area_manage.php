<?php

define('IN_ECS', true);
require_once 'includes/init.php';
include_once 'function.php';
require_once 'includes/helper/array.php';
admin_priv('carriage_manage');
$exc = new exchange($ecs->table('region'), $db, 'region_id', 'region_name');

/* act操作项的初始化 */
if (empty($_REQUEST['act'])) {
    $_REQUEST['act'] = 'list';
} else {
    $_REQUEST['act'] = trim($_REQUEST['act']);
}

/*------------------------------------------------------ */
//-- 列出某地区下的所有地区列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    admin_priv('area_manage','office_area_manage');
    /* 取得参数：上级地区id */
    $region_id = empty($_REQUEST['pid']) ? 0 : intval($_REQUEST['pid']);

    /* 取得列表显示的地区的类型 */
    if ($region_id == 0) {
        $region_type = 0;
    } else {
        $region_type = $exc->get_name($region_id, 'region_type') + 1;
    }
    $smarty->assign('parent_id', $region_id);
    $smarty->assign('region_type',  $region_type);
    /*起始地址*/
    if ($region_id > 0) {
    	$start_address['province'] = $db->getAll("select region_id, region_name from ecshop.ecs_region where parent_id = 1 ");
    	$smarty->assign('start_address', $start_address);
    }
    
    /* 获取地区列表 */
    $arr = array();
    $region_arr = area_list($region_id);
    $arr = array();
    $north_region = array(2,3,4,5,6);
    $northeast_region  = array(7,8,9);
    $east_region  = array(10,11,12,13,14,15,16);
    $centersouth_region  = array(17,18,19,20,21,22);
    $southwest_region  = array(23,24,25,26,27);
    $northwest_region  = array(28,29,30,31,32);
    foreach($region_arr as $k => $v){
      if(in_array($v['region_id'],$north_region )){
        $arr['north'][] = $v;    
      }
      else if(in_array($v['region_id'],$northeast_region )){
        $arr['northeast'][] = $v; 
      }
      else if(in_array($v['region_id'],$east_region )){
        $arr['east'][] = $v;    
      }             
      else if(in_array($v['region_id'],$centersouth_region )){
        $arr['centersouth'][] = $v;     
      }             
      else if(in_array($v['region_id'],$southwest_region )){
        $arr['southwest'][] = $v;    
      }             
      else if(in_array($v['region_id'],$northwest_region )){
        $arr['northwest'][]= $v;      
      }
      else {
        $arr['others'][] = $v;
      }             
    }    

    if($_REQUEST['type'] == 1){
    	$region_one = $arr;
    }
    $smarty->assign('region_arr',   $region_arr);
    $smarty->assign('north',$region_one['north']);
    $smarty->assign('northeast',$region_one['northeast']);
    $smarty->assign('northwest',$region_one['northwest']);
    $smarty->assign('east',$region_one['east']);
    $smarty->assign('centersouth',$region_one['centersouth']);
    $smarty->assign('southwest',$region_one['southwest']); 
    $smarty->assign('others', $region_one['others']);
    //添加仓库和快递方式列表  
    $smarty->assign('shipping_lists',getShippingTypes());
    $smarty->assign('available_facility',get_user_facility());
    /* 当前的地区名称 */
    if ($region_id > 0)
    {
        $area_name = $exc->get_name($region_id);
        $area = '[ '. $area_name .$_LANG['area'];
        if ($region_arr)
        {
            $area .= $_LANG['area_next']. ' '.$region_arr[0]['type'];
        }
        $area .= ' ]';
    }
    else
    {
        $area = $_LANG['country']. $_LANG['area'];
    }
    $smarty->assign('area_here',    $area);

    /* 返回上一级的链接 */
    if ($region_id > 0)
    {
        $parent_id = $exc->get_name($region_id, 'parent_id');
        $action_link = array('text' => $_LANG['back_page'], 'href' => 'area_manage.php?act=list&&pid=' . $parent_id);
    }
    else
    {
        $action_link = '';
    }
    $smarty->assign('action_link',  $action_link);

    /* 赋值模板显示 */
    $smarty->assign('ur_here',$_LANG['05_area_list']);
    $smarty->assign('full_page', 1);
    assign_query_info();
    $smarty->display('area_list.htm');
}

/*------------------------------------------------------ */
//-- 添加新的地区
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add_area')
{
    check_authz_json('area_manage');

    $parent_id      = intval($_POST['parent_id']);
    $region_name    = trim($_POST['region_name']);
    $region_type    = intval($_POST['region_type']);
    if (empty($region_name))
    {
        make_json_error($_LANG['region_name_empty']);
    }

    /* 查看区域是否重复 */
    if (!$exc->is_only('region_name', $region_name, 0, "parent_id = '$parent_id'"))
    {
        make_json_error($_LANG['region_name_exist']);
    }

    $sql = "INSERT INTO " . $ecs->table('region') . " (parent_id, region_name, region_type) ".
           "VALUES ('$parent_id', '$region_name', '$region_type')";
    if ($GLOBALS['db']->query($sql, 'SILENT'))
    {
        admin_log($region_name, 'add','area');

        /* 获取地区列表 */
        $region_arr = area_list($parent_id);
        $smarty->assign('region_arr',   $region_arr);

        make_json_result($smarty->fetch('area_list.htm'));
    }
    else
    {
        make_json_error($_LANG['add_area_error']);
    }
}

/*------------------------------------------------------ */
//-- 编辑区域名称
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_area_name')
{    check_authz_json('area_manage');
    $id = intval($_REQUEST['id']);
    $region_name = trim($_REQUEST['val']);
    if (empty($region_name))
    {
        make_json_error($_LANG['region_name_empty']);
    }
    $msg = '';

    /* 查看区域是否重复 */
    $parent_id = $exc->get_name($id, 'parent_id');
    if (!$exc->is_only('region_name', $region_name, $id, "parent_id = '$parent_id'"))
    {
        make_json_error($_LANG['region_name_exist']);
    }

    if ($exc->edit("region_name = '$region_name'", $id))
    {
        admin_log($region_name, 'edit', 'area');
        make_json_result(stripslashes($region_name));
    }
    else
    {
        make_json_error($db->error());
    }
}

/*------------------------------------------------------ */
//-- 删除区域
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_area')
{
    check_authz_json('area_manage');

    $id = intval($_REQUEST['id']);

    $sql = "SELECT * FROM " . $ecs->table('region') . " WHERE region_id = '$id'";
    $region = $db->getRow($sql);

    /* 如果底下有下级区域,不能删除 */
    $sql = "SELECT COUNT(*) FROM " . $ecs->table('region') . " WHERE parent_id = '$id'";
    if ($db->getOne($sql) > 0)
    {
        make_json_error($_LANG['parent_id_exist']);
    }

    if ($exc->drop($id))
    {
        admin_log(addslashes($region['region_name']), 'remove', 'area');

        /* 获取地区列表 */
        $region_arr = area_list($region['parent_id']);
        $smarty->assign('region_arr',   $region_arr);

        make_json_result($smarty->fetch('area_list.htm'));
    }
    else
    {
        make_json_error($db->error());
    }
}
/*---------------------------------*/
//运费设置
/*---------------------------------*/
elseif ($_REQUEST['act'] == 'update') {
    /* 是否为运费维护 */
   // $list = array();
    $list['facility_id'] = facility_convert($_REQUEST['facility']) ;     //仓库
    $list['start_address'] = $_REQUEST['start_address'];
    $list['carrier_id'] = $_REQUEST['shipping']; //快递方式
    $region_id = $_REQUEST['act_type'];
    //如果为运费修改，提交后仍然显示当前页面
    $list['region_ids'] = $_REQUEST['chk'];
    
    $list['first_weight'] = empty($_REQUEST['first_weight']) ? '' : trim($_REQUEST['first_weight']);
    //为零许可 BY LJNI 20140404
    if($_REQUEST['first_weight']==0)$list['first_weight']='0';
    
    $list['first_fee'] = empty($_REQUEST['first_fee']) ? '':trim($_REQUEST['first_fee']);
    //为零许可 BY LJNI 20140404
    if($_REQUEST['first_fee']==0)$list['first_fee']='0';
    
    //续费
    $list['continued_fee'] = empty($_REQUEST['continued_fee']) ? '':trim($_REQUEST['continued_fee']);
    //为零许可 BY LJNI 20140404
    if($_REQUEST['continued_fee']==0)$list['continued_fee']='0';
    
    //插入或更新运费 
    //QLog::log('UPDATE CARRIAGE FEE CHECK FID='.$list['facility_id']." CID=".$list['carrier_id']." FF=".$list['first_fee']." CF=".$list['continued_fee']);
    //使用empty会把0置为不可，故用isset代替之 BY LJNI 20140404
    //if (!empty($list['facility_id']) && !empty($list['carrier_id']) && !empty($list['first_fee']) &&!empty($list['continued_fee'])) {
    if (isset($list['facility_id']) && isset($list['carrier_id']) && isset($list['first_fee']) && isset($list['continued_fee'])) {
        //QLog::log('UPDATE CARRIAGE FEE ACT '.implode(",", $list));
        freight_management($list);
    } else {
        $smarty->assign('message', '快递方式、仓库、价格不符合要求');
    } 
    if ($region_id == 1) {
        $url = $_SERVER['PHP_SELF']."?act=list&pid=".$region_id."&type=1";
    } else {
        $url = $_SERVER['PHP_SELF']."?act=list&pid=".$region_id;
    }
    header("Location: {$url}"); 
}
/*------------------------------------------------------ */
//-- 运费查看
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'fee_check') {
	$type = trim($_REQUEST['type']);
    $region_id = $_REQUEST['pid'];
    $region_name = $exc->get_name($region_id,'region_name');
    $region_lists =  area_list($region_id);
    //限制仓库和快递方式
    $facility_id = facility_convert($_REQUEST['facility_id']);
    $shipping_id = $_REQUEST['shipping'];
    foreach ($region_lists as $list) {
        $region_list['region_id'][] = $list['region_id']; 
    }
    $region_list['region_id'][] = $region_id;
	if ($type == 'start_facility') {
		$sql = "SELECT region_id, first_weight,first_fee, carrier_id, facility_id, continued_fee FROM ecshop.ecs_carriage WHERE facility_id = '{$facility_id}' 
    		AND carrier_id = '{$shipping_id}' AND ". db_create_in($region_list['region_id'], 'region_id');
	} elseif ($type == 'start_address') {
		admin_priv("office_area_manage");
		$from_province_id = trim($_REQUEST['from_province_id']);
		$from_city_id = trim($_REQUEST['from_city_id']);
		$from_district_id = trim($_REQUEST['from_district_id']);
		if ($from_province_id != "null" && !empty($from_province_id)) {
			$from_region_id = $from_province_id;
			
		}
		if ($from_city_id != "null" && !empty($from_city_id)) {
			$from_region_id = $from_city_id;
			
		}
		if ($from_district_id != "null" && !empty($from_district_id)) {
			$from_region_id = $from_district_id;
		
		}
		$from_province_name = $exc->get_name($from_province_id, 'region_name');
		$smarty->assign('from_province_name', $from_province_name);
		$from_city_name = $exc->get_name($from_city_id, 'region_name');
		$smarty->assign('from_city_name', $from_city_name);
		$from_district_name = $exc->get_name($from_district_id, 'region_name');
		$smarty->assign('from_district_name', $from_district_name);
		$sql = "SELECT region_id, first_weight,first_fee, carrier_id, facility_id, continued_fee, from_region_id FROM ecshop.ecs_carriage WHERE
    		from_region_id = '{$from_region_id}' and carrier_id = '{$shipping_id}' AND ". db_create_in($region_list['region_id'], 'region_id');
	}
    $result = $db->getAll($sql);
    
    foreach ($result as $k=>$item) {
        foreach ($region_lists as $list) {
            if ($item['region_id'] == $list['region_id']) {
                $result[$k]['region_name'] = $list['region_name'];
            } else {
                $result[$k]['region_name'] = $exc->get_name($item['region_id'],'region_name');
            }
        }
    }
    $smarty->assign('type', $type);
    if(!empty($result)){
        $smarty->assign('fee_lists',$result);
    }
    //返回上一页
    if ($region_id > 0) {
        $parent_id = $exc->get_name($region_id, 'parent_id');
        $action_link = array('text' => $_LANG['back_page'], 'href' => 'area_manage.php?act=list&&pid=' . $parent_id);
    } else {
        $action_link = '';
    }
    /* 当前的地区名称 */
    if ($region_id > 0){
        $area_name = $exc->get_name($region_id);
        $area = '[ '. $area_name .$_LANG['area'];
        if ($region_lists){
            $area .= $_LANG['area_next']. ' '.$region_arr[0]['type'];
        }
            $area .= ' ]';
    }else{
        $area = $_LANG['country']. $_LANG['area'];
    }
    $smarty->assign('area_here',    $area);
    $smarty->assign('action_link',  $action_link);
    $smarty->assign('region_name',$region_name);
    $smarty->assign('shipping_lists',getShippingTypes());
    $smarty->assign('available_facility',get_available_facility($_SESSION['party_id']));
    $smarty->assign('full_page',    1);
    $smarty->display('freight.htm');
} 
/*------------------------------------------------------ */
//-- 起始地址
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'get_regions' && trim($_REQUEST['request'] == 'ajax')) {
    $type = trim($_REQUEST['type']);
    $parent_id = trim($_REQUEST['parent']);
    $sql = "select * from ecshop.ecs_region where parent_id = '{$parent_id}' and region_type = '{$type}' ";
    $region_list = $db->getAll($sql);
    print json_encode($region_list);
    exit();
}

/**
 * 运费设置
 * @param unknown_type $carrier_id
 * @param unknown_type $facility_id
 * @param unknown_type $item_list
 * @param unknown_type $price
 */
function freight_management($list){
    global $db;
    $length = count($list['region_ids']);
    if ($list['facility_id'] == -1) {
	    if ($length == 1) {
	        $region_id = $list['region_ids'][0];
	        update_start_address_carriage($region_id, $list);
	    } else {
	        foreach ($list['region_ids'] as $region_id) {
	        	update_start_address_carriage($region_id, $list);
	        }
	    }	
    } else {
	    if ($length == 1) {
	        $region_id = $list['region_ids'][0];
	        update_carriage($region_id, $list);
	    } else {
	        foreach ($list['region_ids'] as $region_id) {
	            update_carriage($region_id, $list);
	        }
	    }
    }
    
}
/**
 * 更新一个起始地址及其子区域的费用信息
 */
function update_start_address_carriage ($region_id, $list) {
	global $db;
	if ($list['start_address']['province'] != -1 && !empty($list['start_address']['province'])) {
    	$from_region_id = $list['start_address']['province'];
    } 
    if ($list['start_address']['city'] != -1 && !empty($list['start_address']['city'])) {
    	$from_region_id = $list['start_address']['city'];
    }
    if ($list['start_address']['district'] != -1 && !empty($list['start_address']['district'])) {
    	$from_region_id = $list['start_address']['district'];
    }
    $sql_f = "
        SELECT r1.region_id FROM ecshop.ecs_region r1
        WHERE r1.parent_id 
            IN (SELECT r.region_id FROM ecs_region r WHERE r.parent_id = '{$from_region_id}' OR r.region_id = '{$from_region_id}') 
            OR r1.region_id = '{$from_region_id}'
    ";
    $from_region_list = $db->getCol($sql_f);
    foreach ($from_region_list as $value) {
    	update_carriage($region_id, $list, $value);
    }
}
/**
 * 更新一个地区以及其子区的费用信息
 * @param string $region_id 目的区域
 * @param array $list
 * @param int $from_region_id 起始地区
 */
function update_carriage($region_id, $list, $from_region_id = null) {
    global $db;
    $sql_all = "
        SELECT r1.region_id FROM ecshop.ecs_region r1
        WHERE r1.parent_id 
            IN (SELECT r.region_id FROM ecs_region r WHERE r.parent_id = '{$region_id}' OR r.region_id = '{$region_id}') 
            OR r1.region_id = '{$region_id}'
    ";
    $region_list = $db->getCol($sql_all);
    $str = "";
    if (!empty($from_region_id) && $list['facility_id'] == -1) {
    	$str = " c.from_region_id = {$from_region_id} ";
    } else {
    	$str = " c.facility_id = '{$list['facility_id']}' ";
    }
    $sql_c = "
        SELECT c.carriage_id, c.region_id, c.first_weight, c.first_fee, c.continued_fee
        FROM ecshop.ecs_carriage c 
        LEFT JOIN ecshop.ecs_region r1 ON c.region_id = r1.region_id
        LEFT JOIN ecshop.ecs_region r ON r1.parent_id = r.region_id 
        WHERE c.carrier_id = '{$list['carrier_id']}' AND ". $str ."
            AND (r1.parent_id = '{$region_id}' OR " .db_create_in($region_list, 'r1.region_id'). ")
    ";
    $region_list_c = $db->getAll($sql_c);
    $region_list_d = Helper_Array::getCols($region_list_c, 'region_id'); // 待更新列表
    $region_list_d_n = array_diff($region_list, $region_list_d);  //初次设置快递费地区列表
    foreach ($region_list_c as $item) {
        $sql_update = "
            UPDATE ecshop.ecs_carriage SET first_weight = '{$list['first_weight']}', first_fee = '{$list['first_fee']}',
            continued_fee = '{$list['continued_fee']}' WHERE carriage_id = '{$item['carriage_id']}' 
        ";
        $db->query($sql_update);
        $sql_i = "
            INSERT INTO ecshop.ecs_carriage_history (carriage_id, update_time, last_first_weight, new_first_weight, 
            last_first_fee, new_first_fee, last_continued_fee, new_continued_fee, user_name) VALUES ('{$item['carriage_id']}', 
            now(), '{$item['first_weight']}', '{$list['first_weight']}', '{$item['first_fee']}', '{$list['first_fee']}', 
            '{$item['continued_fee']}', '{$list['continued_fee']}', '{$_SESSION['admin_name']}')
        ";
        $db->query($sql_i);
    }
    foreach ($region_list_d_n as $item) {
    	if (!empty($from_region_id) && $list['facility_id'] == -1) {
	    	$sql_insert = "
	            INSERT INTO ecshop.ecs_carriage (region_id, carrier_id, from_region_id, first_weight, first_fee, continued_fee) 
	            VALUES ('{$item}', '{$list['carrier_id']}', {$from_region_id},'{$list['first_weight']}','{$list['first_fee']}',
	                '{$list['continued_fee']}')
	        ";
	    } else {
	    	$sql_insert = "
	            INSERT INTO ecshop.ecs_carriage (region_id, carrier_id, facility_id, first_weight, first_fee, continued_fee) 
	            VALUES ('{$item}', '{$list['carrier_id']}', '{$list['facility_id']}','{$list['first_weight']}','{$list['first_fee']}',
	                '{$list['continued_fee']}')
	        ";
	    }
        $db->query($sql_insert);
        $result = $db->insert_id();
        $sql_i = "
            INSERT INTO ecshop.ecs_carriage_history (carriage_id, update_time, last_first_weight, new_first_weight, 
            last_first_fee, new_first_fee, last_continued_fee, new_continued_fee, user_name) VALUES ('{$result}', 
            now(), 0, '{$list['first_weight']}', 0, '{$list['first_fee']}', 0, '{$list['continued_fee']}', 
            '{$_SESSION['admin_name']}')
        ";
        $db->query($sql_i);
    }
}

?>