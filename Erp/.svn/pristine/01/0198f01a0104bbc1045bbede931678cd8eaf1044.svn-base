<?php

define('IN_ECS', true);
require_once 'includes/init.php';
include_once 'function.php';
require_once 'includes/helper/array.php';
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
if($_SESSION['admin_name']!='ljni')admin_priv('carriage_manage');
$exc = new exchange($ecs->table('region'), $db, 'region_id', 'region_name');

/* act操作项的初始化 */
if (empty($_REQUEST['act'])) {
    $_REQUEST['act'] = 'list';
} else {
    $_REQUEST['act'] = trim($_REQUEST['act']);
}
//var_dump($_REQUEST['act']);
/*------------------------------------------------------ */
//-- 列出某地区下的所有地区列表
/*------------------------------------------------------ */
if ($_REQUEST['act'] == 'list')
{
    if($_SESSION['admin_name']!='ljni')admin_priv('area_manage','office_area_manage');
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
//    if(!empty($_REQUEST['message'])){
//    	$smarty->assign('message', $_REQUEST['message']);
//    }
//    if(!empty($_REQUEST['facility_id'])){
//    	$smarty->assign('facility_excel', $_REQUEST['facility_id']);
//    }
//    if(!empty($_REQUEST['shipping_id'])){
//    	$smarty->assign('shipping_id', $_REQUEST['shipping_id']);
//    }
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
        $action_link = array('text' => $_LANG['back_page'], 'href' => 'area_manage_hakobiya.php?act=list&&pid=' . $parent_id);
    }
    else
    {
        $action_link = '';
    }
    $smarty->assign('action_link',  $action_link);
    $smarty->assign('message',$_REQUEST['message']);

    /* 赋值模板显示 */
    $smarty->assign('ur_here',$_LANG['05_area_list_hakobiya']);
    $smarty->assign('full_page', 1);
    assign_query_info();
    $smarty->display('area_list_hakobiya.htm');
}

/*------------------------------------------------------ */
//-- 添加新的地区 X
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'add_area')
{
    die('请前往地区列表设置新地区');
    if($_SESSION['admin_name']!='ljni')check_authz_json('area_manage');

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

        make_json_result($smarty->fetch('area_list_hakobiya.htm'));
    }
    else
    {
        make_json_error($_LANG['add_area_error']);
    }
}

/*------------------------------------------------------ */
//-- 编辑区域名称 X
/*------------------------------------------------------ */

elseif ($_REQUEST['act'] == 'edit_area_name')
{   
    die('请前往地区列表设置新地区名称');
    if($_SESSION['admin_name']!='ljni') check_authz_json('area_manage');
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
//-- 删除区域 X
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'drop_area')
{
    die('请前往地区列表删除地区');
    if($_SESSION['admin_name']!='ljni')check_authz_json('area_manage');

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

        make_json_result($smarty->fetch('area_list_hakobiya.htm'));
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
    
    $list['first_weight'] = empty($_REQUEST['first_weight']) ? '0' : trim($_REQUEST['first_weight']);
    
    $list['first_fee'] = empty($_REQUEST['first_fee']) ? '0':trim($_REQUEST['first_fee']);
    
    // 时效权重
    $list['time_arrived_weight'] = empty($_REQUEST['time_arrived_weight']) ? '0':trim($_REQUEST['time_arrived_weight']);
    // 售后权重
    $list['service_weight'] = empty($_REQUEST['service_weight']) ? '0':trim($_REQUEST['service_weight']);
    // 可达性权重
    $list['arrived_weight'] = empty($_REQUEST['arrived_weight']) ? '0':trim($_REQUEST['arrived_weight']);
    // 临界值重量
    $list['critical_weight'] = empty($_REQUEST['critical_weight']) ? '0':trim($_REQUEST['critical_weight']);

    //续费
    $list['continued_fee'] = empty($_REQUEST['continued_fee']) ? '0':trim($_REQUEST['continued_fee']);
    //面单费
	$list['tracking_fee'] = empty($_REQUEST['tracking_fee']) ? '0':trim($_REQUEST['tracking_fee']);
	//操作费
	$list['operation_fee'] = empty($_REQUEST['operation_fee']) ? '0':trim($_REQUEST['operation_fee']);
	//过磅费
	$list['weighing_fee'] = empty($_REQUEST['weighing_fee']) ? '0':trim($_REQUEST['weighing_fee']);
	//中转费
	$list['transit_fee'] = empty($_REQUEST['transit_fee']) ? '0':trim($_REQUEST['transit_fee']);
	//最低中转费
	$list['lowest_transit_fee'] = empty($_REQUEST['lowest_transit_fee']) ? '0':trim($_REQUEST['lowest_transit_fee']);
    
    //插入或更新运费 
    //QLog::log('UPDATE CARRIAGE FEE CHECK FID='.$list['facility_id']." CID=".$list['carrier_id']." FF=".$list['first_fee']." CF=".$list['continued_fee']);
    //使用empty会把0置为不可，故用isset代替之 BY LJNI 20140404
    //if (!empty($list['facility_id']) && !empty($list['carrier_id']) && !empty($list['first_fee']) &&!empty($list['continued_fee'])) {
    if(isset($list['facility_id']) && isset($list['carrier_id']) && isset($list['first_fee']) && isset($list['continued_fee'])){
    	$message = freight_management($list);
    } else {
        $message ='快递方式、仓库、价格不符合要求';
    } 
    
    if ($region_id == 1) {
        $url = $_SERVER['PHP_SELF']."?act=list&pid=".$region_id."&type=1";
    } else {
        $url = $_SERVER['PHP_SELF']."?act=list&pid=".$region_id;
    }
//    if(isset($_REQUEST['facility'])){
//    	$url .="&facility_id=".$_REQUEST['facility'];
//    }
//    if(isset($_REQUEST['shipping'])){
//    	$url .="&shipping_id=".$_REQUEST['shipping'];
//    }
    if($message != "true"){
    	$url.="&message=".$message;
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
		$sql = "SELECT region_id,  carrier_id, facility_id,first_weight,first_fee, continued_fee,tracking_fee,operation_fee,weighing_fee,transit_fee,lowest_transit_fee
			 FROM ecshop.ecs_express_fee WHERE facility_id = '{$facility_id}' 
    		AND carrier_id = '{$shipping_id}' AND ". db_create_in($region_list['region_id'], 'region_id');		
	} elseif ($type == 'start_address') {
		if($_SESSION['admin_name']!='ljni')admin_priv("office_area_manage");
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
		$sql = "SELECT region_id, carrier_id, facility_id, first_weight,first_fee, continued_fee, from_region_id,tracking_fee,operation_fee,weighing_fee,transit_fee,lowest_transit_fee 
				 FROM ecshop.ecs_express_fee WHERE
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
        $action_link = array('text' => $_LANG['back_page'], 'href' => 'area_manage_hakobiya.php?act=list&&pid=' . $parent_id);
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

/*------------------------------------------------------ */
//-- 导出地址列表csv
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == '导出地址列表') {
    $parent_region_id = trim($_REQUEST['parent_id']);
    $facility_id = trim($_REQUEST['facility_excel']);
    $shipping_id = trim($_REQUEST['shipping_excel']);

    export_region_list_fees($parent_region_id,$facility_id,$shipping_id);
    exit();
}
/*------------------------------------------------------ */
//-- 导出地址列表csv
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == '导入地址列表') {
	
	$result = import_region_fees();
    $message = !empty($result['message'])?$result['message']:'导入成功！';
//    $message = json_encode($message);
    $region_id = trim($_REQUEST['parent_id']);
//    die();
    if ($region_id == 1) {
        $url = $_SERVER['PHP_SELF']."?act=list&pid=".$region_id."&type=1&message=".$message;
    } else {
        $url = $_SERVER['PHP_SELF']."?act=list&pid=".$region_id."&message=".$message;
    }
    header("Location: {$url}"); 
}


/**
 * 运费设置
 * @param unknown_type $carrier_id
 * @param unknown_type $facility_id
 * @param unknown_type $item_list
 * @param unknown_type $price
 */
function freight_management($list){//QLog::log('freight_management');
    global $db;
    $length = count($list['region_ids']);
    if ($list['facility_id'] == -1) {
	    if ($length == 1) {
	        $region_id = $list['region_ids'][0];
	        $message = update_start_address_carriage($region_id, $list);
	    } else {
	        foreach ($list['region_ids'] as $region_id) {
	        	$message = update_start_address_carriage($region_id, $list);
	        }
	    }	
    } else {
	    if ($length == 1) {
	        $region_id = $list['region_ids'][0];
	         $message = update_carriage($region_id, $list);
	    } else {
	        foreach ($list['region_ids'] as $region_id) {
	            $message =  update_carriage($region_id, $list);
	        }
	    }
    }
    return  $message;
}
/**
 * 更新一个起始地址及其子区域的费用信息
 */
function update_start_address_carriage ($region_id, $list) {//QLog::log('update_start_address_carriage');
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
    	$message = update_carriage($region_id, $list, $value);
    	if($message != "true"){
    		return $message;
    	}
    }
    return "true";
}
/**
 * 更新一个地区以及其子区的费用信息
 * @param string $region_id 目的区域
 * @param array $list
 * @param int $from_region_id 起始地区
 */
function update_carriage($region_id, $list, $from_region_id = null) {//QLog::log('update_carriage');
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
	   	SELECT c.carriage_id, c.region_id, c.first_weight, c.first_fee, c.continued_fee,c.tracking_fee,
	   	c.operation_fee,c.weighing_fee,c.transit_fee,c.lowest_transit_fee,
	   	c.time_arrived_weight,c.service_weight,c.arrived_weight,c.critical_weight
	   	FROM ecshop.ecs_express_fee c 
	   	LEFT JOIN ecshop.ecs_region r1 ON c.region_id = r1.region_id
	   	LEFT JOIN ecshop.ecs_region r ON r1.parent_id = r.region_id 
	 	WHERE c.carrier_id = '{$list['carrier_id']}' AND ". $str ."
	  	AND (r1.parent_id = '{$region_id}' OR " .db_create_in($region_list, 'r1.region_id'). ")
	";
//	QLog::log("region_list_c_sql : ".$sql_c);
	$region_list_c = $db->getAll($sql_c);
	$region_list_d = Helper_Array::getCols($region_list_c, 'region_id'); // 待更新列表
	$region_list_d_n = array_diff($region_list, $region_list_d);  //初次设置快递费地区列表
	foreach ($region_list_c as $item) {
		$message = update_region_fee($list,$item);
		if($message != "true"){
			return $message;
		}
	}
	foreach ($region_list_d_n as $item) {
		$list['region_id'] = $item;
		$message = insert_region_fee($list);
		if($message != "true"){
			return $message;
		}
	}
	return "true";
}

/**
 * 更新区域费用
 */
function update_region_fee($new_item,$origin_item) {
	global $db;
	if(empty($new_item) || empty($origin_item)) return "更新区域为空，请注意选取范围";
//	var_dump('update_region_fee');
//	var_dump($new_item);
//	var_dump($origin_item);
	
	$sql_update =" 
       	UPDATE ecshop.ecs_express_fee SET first_weight = '{$new_item['first_weight']}', first_fee = '{$new_item['first_fee']}',
     	continued_fee = '{$new_item['continued_fee']}',tracking_fee= '{$new_item['tracking_fee']}',operation_fee= '{$new_item['operation_fee']}',
      	weighing_fee= '{$new_item['weighing_fee']}',transit_fee= '{$new_item['transit_fee']}',lowest_transit_fee= '{$new_item['lowest_transit_fee']}',
      	time_arrived_weight= '{$new_item['time_arrived_weight']}',
      	service_weight= '{$new_item['service_weight']}',
      	arrived_weight= '{$new_item['arrived_weight']}',
      	critical_weight= '{$new_item['critical_weight']}'
    	WHERE carriage_id = '{$origin_item['carriage_id']}' limit 1
  	";
  	//die($sql_update);
  	Qlog::log("update_region_fee_sql:".$sql_update." \n ");
   	if($db->query($sql_update)){
   		$origin_item['tracking_fee']=empty($origin_item['tracking_fee']) ? '0':$origin_item['tracking_fee'];
	   	$origin_item['operation_fee']=empty($origin_item['operation_fee']) ? '0':$origin_item['operation_fee'];
	   	$origin_item['weighing_fee']=empty($origin_item['weighing_fee']) ? '0':$origin_item['weighing_fee'];
	 	$origin_item['transit_fee']=empty($origin_item['transit_fee']) ? '0':$origin_item['transit_fee'];  
	 	$origin_item['lowest_transit_fee']=empty($origin_item['lowest_transit_fee']) ? '0':$origin_item['lowest_transit_fee'];
	  	$sql_i = "
	   		INSERT INTO ecshop.ecs_express_fee_history (carriage_id, update_time, last_first_weight, new_first_weight, 
	        last_first_fee, new_first_fee, last_continued_fee, new_continued_fee,last_tracking_fee,new_tracking_fee,
	        last_operation_fee,new_operation_fee,last_weighing_fee,new_weighing_fee,last_transit_fee,new_transit_fee,
	       	last_lowest_transit_fee,new_lowest_transit_fee, user_name,
	       	last_time_arrived_weight,new_time_arrived_weight,last_service_weight,new_service_weight,
	       	last_arrived_weight,new_arrived_weight,last_critical_weight,new_critical_weight
	       	) VALUES ('{$origin_item['carriage_id']}', 
	      	now(), '{$origin_item['first_weight']}', '{$new_item['first_weight']}', '{$origin_item['first_fee']}', '{$new_item['first_fee']}', '{$origin_item['continued_fee']}', '{$new_item['continued_fee']}', 
	      	'{$origin_item['tracking_fee']}', '{$new_item['tracking_fee']}','{$origin_item['operation_fee']}', '{$new_item['operation_fee']}',
	      	'{$origin_item['weighing_fee']}', '{$new_item['weighing_fee']}','{$origin_item['transit_fee']}', '{$new_item['transit_fee']}',
	     	'{$origin_item['lowest_transit_fee']}', '{$new_item['lowest_transit_fee']}', '{$_SESSION['admin_name']}',
	      	'{$origin_item['time_arrived_weight']}',
	      	'{$new_item['time_arrived_weight']}',
	      	'{$origin_item['service_weight']}',
	      	'{$new_item['service_weight']}',
	      	'{$origin_item['arrived_weight']}',
	      	'{$new_item['arrived_weight']}',
	      	'{$origin_item['critical_weight']}',
	      	'{$new_item['critical_weight']}')
	  	";
		if($db->query($sql_i)){
			return "true";
		}else{
			return "更新历史表数据出错，请重试~";
		}
   	}else{
   		return "更新表数据失败，请重试~";
   	}
   	
}

/**
 * 新增区域费用
 */
function insert_region_fee($new_item) {
	global $db;
	if(empty($new_item)) return "新增区域为空，请核对后重试~";
//	var_dump('insert_region_fee');
//	var_dump($new_item);

	$sql_insert = "
	  	INSERT INTO ecshop.ecs_express_fee (region_id, carrier_id, facility_id, first_weight, first_fee, continued_fee,
       	tracking_fee,operation_fee,weighing_fee,transit_fee,lowest_transit_fee,
       	time_arrived_weight,service_weight,arrived_weight,critical_weight) 
	   	VALUES ('{$new_item['region_id']}', '{$new_item['carrier_id']}', '{$new_item['facility_id']}','{$new_item['first_weight']}','{$new_item['first_fee']}',
	  	'{$new_item['continued_fee']}','{$new_item['tracking_fee']}','{$new_item['operation_fee']}','{$new_item['weighing_fee']}',
      	'{$new_item['transit_fee']}','{$new_item['lowest_transit_fee']}',
      	'{$new_item['time_arrived_weight']}',
      	'{$new_item['service_weight']}',
      	'{$new_item['arrived_weight']}',
      	'{$new_item['critical_weight']}'
      	)
	";
   	if($db->query($sql_insert)){
   		$result = $db->insert_id();
	  	$sql_i = "
	        INSERT INTO ecshop.ecs_express_fee_history (carriage_id, update_time, last_first_weight, new_first_weight, 
	        last_first_fee, new_first_fee, last_continued_fee, new_continued_fee,last_tracking_fee,new_tracking_fee,
	      	last_operation_fee,new_operation_fee,last_weighing_fee,new_weighing_fee,last_transit_fee,new_transit_fee,
	      	last_lowest_transit_fee,new_lowest_transit_fee, user_name,
	       	last_time_arrived_weight,new_time_arrived_weight,last_service_weight,new_service_weight,
	       	last_arrived_weight,new_arrived_weight,last_critical_weight,new_critical_weight
	       	) VALUES ('{$result}', 
	      	now(), 0, '{$new_item['first_weight']}', 0, '{$new_item['first_fee']}', 0, '{$new_item['continued_fee']}', 
	      	0, '{$new_item['tracking_fee']}',0, '{$new_item['operation_fee']}',
	      	0, '{$new_item['weighing_fee']}',0, '{$new_item['transit_fee']}',
	     	0, '{$new_item['lowest_transit_fee']}','{$_SESSION['admin_name']}',
	      	0,'{$new_item['time_arrived_weight']}',
	      	0,'{$new_item['service_weight']}',
	      	0,'{$new_item['arrived_weight']}',
	      	0,'{$new_item['critical_weight']}'
	      	)
		";
		if($db->query($sql_i)){
			return "true";
		}else{
			return "插入历史表数据出错，请重试~";
		}
	}else{
		return "插入表数据出错，请重试~";
	}
  	
}

/**
 * 导出对应地区，仓库，快递的 费用信息
 */
function export_region_list_fees($parent_region_id,$facility_id,$shipping_id) {
	global $db;
	
//	var_dump($parent_region_id);var_dump($facility_id);var_dump($shipping_id);
	$cond = " and ef.facility_id is not null and ef.facility_id <>'' ";
	if(!empty($facility_id) && $facility_id !=-1) {
		$facility_convert_id = facility_convert($facility_id) ;  
		$cond .= " and ef.facility_id='{$facility_convert_id}' ";
	}
	if(!empty($shipping_id) && $shipping_id !=-1) {
		$cond .= " and ef.carrier_id='{$shipping_id}' ";
	}
	
	$sql = "select facility_name from romeo.facility where facility_id = '{$facility_id}' ";
	$facility_name = $db->getOne($sql);
	
	$sql = "select region_id,parent_id from ecshop.ecs_region where region_id = '{$parent_region_id}' ";
	$region_parent_id = $db->getRow($sql);
	
	// 直辖市
	$zhixiashi =get_zhixiashi_region_ids();
	if(in_array($region_parent_id['region_id'],$zhixiashi) || in_array($region_parent_id['parent_id'],$zhixiashi)) {
		$sql = "select region_id from ecshop.ecs_region where (region_id = '{$parent_region_id}' or parent_id = '{$parent_region_id}') and region_type=2 ";
	    $region_ids = $db->getCol($sql);
    	$sql = "select ef.*,pr.region_name as province_name,r.region_name as city_name,'' as district_name,
    	f.facility_name,s.shipping_name,r.region_name
	    from 
	    ecshop.ecs_region r
	    inner join ecshop.ecs_express_fee ef ON r.region_id = ef.region_id
		left join romeo.facility f ON ef.facility_id = f.facility_id
		left join ecshop.ecs_shipping s ON ef.carrier_id = s.shipping_id
		left join ecs_region pr ON r.parent_id = pr.region_id
		where r.region_id ".db_create_in($region_ids).$cond;

	} 
	// 非直辖市
	else 
	{
		$region_ids = get_child_region_ids($parent_region_id);
	
		$sql = "select ef.*,pr.region_name as province_name,cr.region_name as city_name,r.region_name as district_name,
		f.facility_name,s.shipping_name,r.region_name
	    from 
	    ecshop.ecs_region r
	    inner join ecshop.ecs_express_fee ef ON r.region_id = ef.region_id
		left join romeo.facility f ON ef.facility_id = f.facility_id
		left join ecshop.ecs_shipping s ON ef.carrier_id = s.shipping_id
		left join ecs_region cr ON r.parent_id = cr.region_id
		left join ecs_region pr ON cr.parent_id = pr.region_id
		where r.region_id ".db_create_in($region_ids).$cond;
		
	}
//	Qlog::log("export_region_list_fees_sql:".$sql."\n");		
	$region_list = $db->getAll($sql);

	// xls,xlsx格式的
	$title = array(0=>array('仓库','快递','省','市','区','首重','首重费','续重费','面单费','操作费','过磅费','中转费','最低中转费','时效权重','售后权重','可达性权重','临界重量'));

	$data = array();              
	foreach($region_list as $region) {
		$row = array();
//		$row[] = $region['facility_name'];
		$row[] = $facility_name;
		$row[] = $region['shipping_name'];
		$row[] = $region['province_name'];
		$row[] = $region['city_name'];
		$row[] = $region['district_name'];
		$row[] = $region['first_weight'];
		$row[] = $region['first_fee'];
		$row[] = $region['continued_fee'];
		$row[] = $region['tracking_fee'];
		$row[] = $region['operation_fee'];
		$row[] = $region['weighing_fee'];
		$row[] = $region['transit_fee'];
		$row[] = $region['lowest_transit_fee'];
		$row[] = $region['time_arrived_weight'];
		$row[] = $region['service_weight'];
		$row[] = $region['arrived_weight'];
		$row[] = $region['critical_weight'];
		$data[] = $row;
	}
//	var_dump($data);
//	die();
	
	$file_name = '地区费用列表.xlsx';
	$type = array();
	for($i=0;$i<count($data[0]);$i++) {
		$type[] = 'string';
	}
	excel_export_model($title,$file_name,$data,$type,'地区费用列表');

}

/**
 * 得到子区域的region_id
 */
function get_child_region_ids($parent_region_id) {
	global $db;
    $sql = "select region_type from ecshop.ecs_region where region_id = '{$parent_region_id}' ";
	$region_type = $db->getOne($sql);
	if(!in_array($region_type,array(0,1,2,3))) {
		return null;
	}
	
	if($region_type == 0) {
		$sql = "select region_id from ecshop.ecs_region ";
	} else if($region_type == 1) {
		$sql = "select r3.region_id from ecshop.ecs_region r 
				left join ecshop.ecs_region r2 ON r.region_id = r2.parent_id
				left join ecshop.ecs_region r3 ON r2.region_id = r3.parent_id 
                where r.region_id = '{$parent_region_id}' ";
         
	} else if($region_type == 2) {
		$sql = "select r2.region_id from ecshop.ecs_region r 
			left join ecshop.ecs_region r2 ON r.region_id = r2.parent_id
            where r.region_id = '{$parent_region_id}' ";
	} else if($region_type == 3) {
	    $sql = "select region_id from ecshop.ecs_region where region_id = '{$parent_region_id}' ";
	}
//	var_dump('child regions:');var_dump($sql);
	$region_ids = $db->getCol($sql);
	
	return $region_ids;
}

function import_region_fees() {
	global $db;
	require_once (ROOT_PATH . 'includes/helper/array.php');
	require_once (ROOT_PATH . 'includes/helper/uploader.php');
	do{
		$fileElementName = 'fileToUpload';

		$final = array();
		$final['message'] = '';

		$uploader = new Helper_Uploader ();
		
		$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
		
		$config = array('地区费用列表'  =>
					array(
                      'facility_name'=>'仓库',
                      'shipping_name'=>'快递',
                      'province_name'=>'省',
                      'city_name'=>'市',
                      'district_name'=>'区',
                      'first_weight'=>'首重',
                      'first_fee'=>'首重费',
                      'continued_fee'=>'续重费',
                      'tracking_fee'=>'面单费',
                      'operation_fee'=>'操作费',
                      'weighing_fee'=>'过磅费',
                      'transit_fee'=>'中转费',
                      'lowest_transit_fee'=>'最低中转费',
                      'time_arrived_weight'=>'时效权重',
                      'service_weight'=>'售后权重',
                      'arrived_weight'=>'可达性权重',
                      'critical_weight'=>'临界重量'
					));
		
		if (!$uploader->existsFile ( 'fileToUpload' )) {
			$final['message'] =  '没有选择上传文件，或者文件上传失败';
			break;
		}	
	
		//取得要上传的文件句柄
		$file = $uploader->file ( 'fileToUpload' );

		// 检查上传文件
		if ($final['message'] == "" && ! $file->isValid ( 'xls, xlsx', $max_size )) {
			$final['message'] = "非法的文件! 请检查文件类型(xls, xlsx), 并且系统限制的上传大小为". $max_size/1024/1024 . "MB";
			break;
		}
		
		// 读取excel
		if($final['message'] == ""){
			$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
			if (! empty ( $failed )) {
				$final['message'] = reset ( $failed );
				break;
			}
		}
		
		if($final['message'] == ""){
			$rowset = $result ['地区费用列表'];
			if (empty ( $rowset )) {
				$final['message'] = "excel文件中没有数据,请检查文件";
				break;
			}
		}
//		var_dump($rowset);
		//检测仓库
		$facility_names = Helper_Array::getCols ( $rowset, 'facility_name');
		$sql = "select facility_id,facility_name from romeo.facility where facility_name ".db_create_in($facility_names);
		$facility_id_names = $db->getAll($sql);
		$facilitys = array();
		foreach($facility_id_names as $facility_id_name) {
			$facilitys[$facility_id_name['facility_name']] = facility_convert($facility_id_name['facility_id']);
		}
		
		//检测快递
		$shipping_names = Helper_Array::getCols ( $rowset, 'shipping_name');
		$sql = "select shipping_id,shipping_name from ecshop.ecs_shipping where shipping_name ".db_create_in($shipping_names);
		$shipping_id_names = $db->getAll($sql);
		$shippings = array();
		foreach($shipping_id_names as $shipping_id_name) {
			$shippings[$shipping_id_name['shipping_name']] = $shipping_id_name['shipping_id'];
		}
		
		//检测省
		$province_names = Helper_Array::getCols ( $rowset, 'province_name');
		$sql = "select region_id,region_name from ecshop.ecs_region where region_name ".db_create_in($province_names);
		$province_id_names = $db->getAll($sql);
		$provinces = array();
		foreach($province_id_names as $province_id_name) {
			$provinces[$province_id_name['region_name']] = $province_id_name['region_id'];
		}
		
		//检测市
		$city_names = Helper_Array::getCols ( $rowset, 'city_name');
		$sql = "select r.region_id,r.region_name,r.parent_id
		 from ecshop.ecs_region r 
		 inner join ecshop.ecs_region pr ON r.parent_id = pr.region_id
		 where r.region_name ".db_create_in($city_names)." and pr.region_name ".db_create_in($province_names);
//		var_dump($sql);
		$city_id_names = $db->getAll($sql);
		$citys = array();
		foreach($city_id_names as $city_id_name) {
			$citys[$city_id_name['region_name']] = $city_id_name['region_id'];
		}

		//检测区,加上城市名称限制，免得区名称一样
		$district_names = Helper_Array::getCols ( $rowset, 'district_name');
		$sql = "select r.region_id,r.region_name,r.parent_id,pr.region_name as name
		 from ecshop.ecs_region r 
		 inner join ecshop.ecs_region pr ON r.parent_id = pr.region_id
		 where r.region_name ".db_create_in($district_names)." and pr.region_name ".db_create_in($city_names);
//		 var_dump($sql);
		$district_id_names = $db->getAll($sql);
		$districts = array();
		foreach($district_id_names as $district_id_name) {
			$districts[$district_id_name['name'].$district_id_name['region_name']] = $district_id_name['region_id'];
		}
//		
//		var_dump($facilitys);
//		var_dump($shippings);
		
		$region_fees = $rowset;
		$zhixiashi = get_zhixiashi_region_ids();
		
		$message = '';
		// 检测数据格式和准确性
		foreach($region_fees as $key=>$region_fee) {
			$hang = ($key+2);
			if(empty($facilitys[$region_fee['facility_name']])) {
				$message .= "第".$hang."行仓库有误：".$region_fee['facility_name']." ";
			}
			if(empty($shippings[$region_fee['shipping_name']])) {
				$message .= "第".$hang."行快递有误：".$region_fee['shipping_name']." ";
			}
			if(empty($provinces[$region_fee['province_name']])) {
				$message .= "第".$hang."行省有误：".$region_fee['province_name']." ";
			}
			if(empty($citys[$region_fee['city_name']])) {
				$message .= "第".$hang."行市有误：".$region_fee['city_name']." ";
			}

			// 直辖市只到市
			if(!in_array($provinces[$region_fee['province_name']],$zhixiashi)) {
				if(empty($districts[$region_fee['city_name'].$region_fee['district_name']])) {
					$message .= "第".$hang."行区有误：".$region_fee['city_name'].$region_fee['district_name']." ";
				}
			}
			
			//费用价格格式
            $reg_price = "/^\d+$|^\d+\.\d+$/";// ^[0-9]+(\.[0-9]+)?$
            
            if(!empty($region_fee['first_weight'])) {
            	preg_match($reg_price,$region_fee['first_weight'],$first_weight);
            	if(empty($first_weight)) {
            		$message .= "第".$hang."行首重有误：".$region_fee['first_weight']." ";
            	}
            }
            if(!empty($region_fee['first_fee'])) {
            	preg_match($reg_price,$region_fee['first_fee'],$first_fee);
            	if(empty($first_fee)) {
            		$message .= "第".$hang."行首重费有误：".$region_fee['first_fee']." ";
            	}
            }
            if(!empty($region_fee['continued_fee'])) {
            	preg_match($reg_price,$region_fee['continued_fee'],$continued_fee);
            	if(empty($continued_fee)) {
            		$message .= "第".$hang."行续重费有误：".$region_fee['continued_fee']." ";
            	}
            }
            if(!empty($region_fee['tracking_fee'])) {
            	preg_match($reg_price,$region_fee['tracking_fee'],$tracking_fee);
            	if(empty($tracking_fee)) {
            		$message .= "第".$hang."行面单费有误：".$region_fee['tracking_fee']." ";
            	}
            }
            if(!empty($region_fee['operation_fee'])) {
            	preg_match($reg_price,$region_fee['operation_fee'],$operation_fee);
            	if(empty($operation_fee)) {
            		$message .= "第".$hang."行操作费有误：".$region_fee['operation_fee']." ";
            	}
            }
            if(!empty($region_fee['weighing_fee'])) {
            	preg_match($reg_price,$region_fee['weighing_fee'],$weighing_fee);
            	if(empty($weighing_fee)) {
            		$message .= "第".$hang."行过磅费有误：".$region_fee['weighing_fee']." ";
            	}
            }
            if(!empty($region_fee['transit_fee'])) {
            	preg_match($reg_price,$region_fee['transit_fee'],$transit_fee);
            	if(empty($transit_fee)) {
            		$message .= "第".$hang."行中转费有误：".$region_fee['transit_fee']." ";
            	}
            }
            if(!empty($region_fee['lowest_transit_fee'])) {
            	preg_match($reg_price,$region_fee['lowest_transit_fee'],$lowest_transit_fee);
            	if(empty($lowest_transit_fee)) {
            		$message .= "第".$hang."行最低中转费有误：".$region_fee['lowest_transit_fee']." ";
            	}
            }
            
            if(!empty($region_fee['critical_weight'])) {
            	preg_match($reg_price,$region_fee['critical_weight'],$critical_weight);
            	if(empty($critical_weight)) {
            		$message .= "第".$hang."行临界重量有误：".$region_fee['critical_weight']." ";
            	}
            }
            
            //权重数字格式	
            $preg_number = "/^\d+$/";
            if(!empty($region_fee['time_arrived_weight'])) {
            	preg_match($preg_number,$region_fee['time_arrived_weight'],$time_arrived_weight);
            	if(empty($time_arrived_weight)) {
            		$message .= "第".$hang."行时效权重有误：".$region_fee['time_arrived_weight']." ";
            	}
            }
            if(!empty($region_fee['service_weight'])) {
            	preg_match($preg_number,$region_fee['service_weight'],$service_weight);
            	if(empty($service_weight)) {
            		$message .= "第".$hang."行售后权重有误：".$region_fee['service_weight']." ";
            	}
            }
            if(!empty($region_fee['arrived_weight'])) {
            	preg_match($preg_number,$region_fee['arrived_weight'],$arrived_weight);
            	if(empty($arrived_weight)) {
            		$message .= "第".$hang."行可达性权重有误：".$region_fee['arrived_weight']." ";
            	}
            }
            
		}
		
		if(!empty($message)) {
			$final['message'] .= $message;
			break;
		}
//		var_dump($region_fees);
//		die();
		foreach($region_fees as $region_fee) {
			$facility_id = $facilitys[$region_fee['facility_name']];
			$shipping_id = $shippings[$region_fee['shipping_name']];
			if(!in_array($provinces[$region_fee['province_name']],$zhixiashi)) {
				$region_id = $districts[$region_fee['city_name'].$region_fee['district_name']];
			} else {
				$region_id = $citys[$region_fee['city_name']];
			}
			
			
			$region_fee['facility_id'] = $facility_id;
			$region_fee['carrier_id'] = $shipping_id;
			$region_fee['region_id'] = $region_id;
			
			$sql = "select * from ecshop.ecs_express_fee where facility_id = '{$facility_id}' and carrier_id='{$shipping_id}' and region_id='{$region_id}' limit 1";
			Qlog::log("select_ecs_express_fee:".$sql." \n ");
			$origin_region_fee = $db->getRow($sql);
			if(!empty($origin_region_fee)) {
//				var_dump('update');var_dump($region_fee);var_dump($origin_region_fee);
				update_region_fee($region_fee,$origin_region_fee);
			} else {
//				var_dump('insert');var_dump($region_fee);
				insert_region_fee($region_fee);
			}
		}
    } while(false);
//    die();
//    var_dump($final);
    return $final;
}
?>