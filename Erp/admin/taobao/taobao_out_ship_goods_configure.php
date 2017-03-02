<?php 
/**
 * 淘宝外包发货管理
 * 
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

//var_dump(get_group_list(0,3)); die();

$request = // 请求 
    isset($_REQUEST['request']) ? trim($_REQUEST['request']) : null ;
$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add', 'update', 'delete','check','province','check_add_appkey_outerId','check_tc')) 
    ? $_REQUEST['act'] 
    : null ;
$info =    // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;
$page =    // 分页
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
/*
 * 处理ajax请求
 */
if ($request == 'ajax')
{
	
    $json = new JSON;
    switch ($act) 
    {
    	// 获取省份列表 
    	case 'province':
    	    $sql = "SELECT region_id,region_name from ecshop.ecs_region WHERE region_type = 1 and parent_id = 1 "; 
    	    $result = array();
    	    $result['result'] = 'ok'; 
    	    $result['data'] = $db->getAll($sql);
    	    header('Content-type: text/json');
    	    break; 
        // 验证是否为套餐
        case 'check':
        	$outer_id = $_POST['outer_id'];
			if(strcasecmp(substr($outer_id,0,3),'TC-') == '0'){
				$sql = "select count(*) from ecshop.distribution_group_goods where code = '".$outer_id."' and party_id = '".$_SESSION['party_id']."'";
			}else{
				
				$array = explode("_",$outer_id);
				if(count($array) == 1 ){
					$sql = "select count(*) from ecshop.ecs_goods where goods_id = '".current($array)."' and goods_party_id = '".$_SESSION['party_id']."'";
					
				}else if(next($array)=='0'){
					$sql = "select count(*) from ecshop.ecs_goods where goods_id = '".prev($array)."' and goods_party_id = '".$_SESSION['party_id']."'";
				}else{
					$sql = "select count(*) from ecshop.ecs_goods_style where goods_id = '".prev($array)."' and style_id = '".next($array)."' and is_delete=0";
				}
			}	
            $count = $db->getOne($sql);
            if ($count == 0) {
            	$result = false;
            }else {
            	$result = true;
            }
        break;
        case 'check_add_appkey_outerId':
        	$outer_id = $_POST['outer_id'];
        	$application_key = $_POST['application_key'];
        	
			$sql = " select count(1) from ecshop.ecs_out_ship_goods_configure where outer_id = '{$outer_id}' and application_key = '{$application_key}' and end_time >now() and status = 'OK' ";
            $count = $db->getOne($sql);
            if ($count == 0) {
            	$result = true;
            }else {
            	$result = false;
            }
        break;
        case 'check_goods':
        	$outer_id = $_POST['outer_id'];
        	$application_key = $_POST['application_key'];
        	$facility_id = $_POST['facility_id'];
        	$sql = "select count(1) from ecshop.ecs_out_ship_goods_configure where outer_id = '{$outer_id}' and application_key = '{$application_key}' and facility_id = '{$facility_id}' and status = 'OK' limit 1";
        	$count = $db->getOne($sql);
//            var_dump($count);
            if ($count == 0) {
            	$result = true;
            }else {
            	$result = false;
            }
			$result = "数据传送";
        break;
        case 'check_tc':
        	$outer_id = $_POST['outer_id'];
        	$sql="select sum(dggi.goods_number) 
        		  from ecshop.distribution_group_goods dgg 
        			inner join ecshop.distribution_group_goods_item dggi on dgg.group_id = dggi.group_id 
        		  where dgg.code = '{$outer_id}' and dgg.party_id = {$_SESSION['party_id']} 
        		  limit 1
        		 ";
        	$goods_num = $db->getOne($sql);
        	if($goods_num == 2){
        		$result = true;
        	}else{
        		$result = false;
        	}
        break;
    }
    print $json->encode($result);
    exit;
}

// 编辑模式
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	$group =  $db->getRow("SELECT eosgc.*,GROUP_CONCAT(r.region_name) region_name 
		 FROM ecshop.ecs_out_ship_goods_configure eosgc
		 left join ecshop.ecs_region r on find_in_set(r.region_id,eosgc.province_list)  
		 WHERE out_ship_goods_id = ".$_GET['id']." 
		 group by out_ship_goods_id ");
  	$smarty->assign('update', $group);
	//当开始时间<当前时间，即活动已经开始时。限制部分字段不允许修改
	$smarty->assign("start_disabled",$group['start_time']<date('Y-m-d H:i:s', time()));
}

/*
 * 处理post请求
 */
if ($act) {
    switch ($act) {  
     	/*添加商品*/
        case 'add' :
	        $create_user = $_SESSION['admin_name'];
	        $update_user = $_SESSION['admin_name'];
	        $item = $_REQUEST['item'];
	        
	        $sql = "select out_ship_goods_id from ecshop.ecs_out_ship_goods_configure where outer_id = '{$item[outer_id]}' and application_key = '{$item[application_key]}' and facility_id = '{$item[facility_id]}' and end_time>now() and status = 'OK' limit 1";
	        $out_ship_goods_id = $db->getOne($sql);
	        
	        if(!$out_ship_goods_id){
	        	$sql = "
					insert into ecshop.ecs_out_ship_goods_configure (
						out_ship_goods_id,party_id,application_key,start_time,end_time,outer_id,goods_number,out_goods_number, tc_is_split, out_number,transfer_num,facility_id,
						out_ship_id,province_list,consumables,create_time,update_time,create_user,update_user,status
					) values(
						null,'{$_SESSION[party_id]}','{$item[application_key]}','{$item[start_time]}','{$item[end_time]}','{$item[outer_id]}',1,{$item[out_goods_number]},'{$item['tc_is_split_value']}','{$item[out_number]}',
						0,'{$item[facility_id]}','{$item[out_ship_id]}','{$item[out_province]}','{$item[consumables]}',now(),now(),'$create_user','$update_user', 'OK'
					)
				";
				$db->query($sql);
				//执行插入之后，需要在risk表里面添加一条历史记录
				$add_sql = "
					INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) 
					VALUES ('{$create_user}', 'add', NOW(), 'taobao_out_ship_goods_onfigure.php', 'add_form', '".mysql_real_escape_string($sql)."', '添加')
				";
				$db->query($add_sql);
	        }else {
	        	$sql = "
					update ecshop.ecs_out_ship_goods_configure 
					set 
					start_time = '{$item[start_time]}',
					end_time = '{$item[end_time]}',
					out_goods_number = {$item[out_goods_number]},
					tc_is_split = '{$item[tc_is_split_value]}',
					out_number = '{$item[out_number]}',
					province_list = '{$item[out_province]}', 
					out_ship_id = '{$item[out_ship_id]}',
					consumables = '{$item[consumables]}',
					update_time = now(),
					update_user = '$update_user'
					where out_ship_goods_id = {$out_ship_goods_id}
				";
				$db->query($sql);
				//执行update之后，需要在risk表上添加一条记录
				$update_sql = "
					INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) 
					VALUES ('{$update_user}', 'update', NOW(), 'taobao_out_ship_goods_onfigure.php', 'update_form', '".mysql_real_escape_string($sql)."', '更新')
				";
				$db->query($update_sql);
	        }
        break;
        
        /* 编辑商品 */
        case 'update' :
	        $create_user = $_SESSION['admin_name'];
	        $update_user = $_SESSION['admin_name'];
	        $item = $_REQUEST['item'];
	        $sql = "
				update ecshop.ecs_out_ship_goods_configure 
				set  
				end_time = '{$item[end_time]}',
				out_goods_number = {$item[out_goods_number]},
				tc_is_split = '{$item[tc_is_split_value]}',
				out_number = '{$item[out_number]}',
				province_list = '{$item[out_province]}', 
				facility_id = '{$item[facility_id]}',
				out_ship_id = '{$item[out_ship_id]}',
				consumables = '{$item[consumables]}',
				update_time = now(),
				update_user = '$update_user'
				where out_ship_goods_id = '{$item[out_ship_goods_id]}'
			";
			$db->query($sql);
			//执行update之后，需要在risk表上添加一条记录
			$update_sql = "
				INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) 
				VALUES ('{$update_user}', 'update', NOW(), 'taobao_out_ship_goods_onfigure.php', 'update_form', '".mysql_real_escape_string($sql)."', '编辑')
			";
			$db->query($update_sql);
        break; 
        case 'delete' :
        	$out_ship_goods_id = $_REQUEST['out_ship_goods_id'];
        	$sql="update ecshop.ecs_out_ship_goods_configure set status = 'DELETE', update_time = now(), update_user = '{$_SESSION['admin_name']}' where out_ship_goods_id = '{$out_ship_goods_id}' limit 1";
        	$db->query($sql);
        	
        	//执行delete之后，需要在risk表上添加一条记录
			$delete_sql = "
				INSERT INTO ecshop.risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) 
				VALUES ('{$_SESSION['admin_name']}', 'delete', NOW(), 'taobao_out_ship_goods_onfigure.php', 'delete_form', '".mysql_real_escape_string($sql)."', '删除')
			";
			$db->query($delete_sql);
        break;
    }  
    header('location:taobao_out_ship_goods_configure.php');
}

$application_list = get_taobao_shop_nicks();
$smarty->assign('application_list', $application_list);

$smarty->assign('application_nicks',trim($_REQUEST['application_nicks']));
/**
 * 取得淘宝店铺信息
 * 
 */
function get_taobao_shop_nicks() {
    $application_list = get_taobao_shop_list(); //取得taobao店铺信息
    $application_nicks = array();
    foreach ($application_list as $application) {
        $application_nicks[$application['application_key']] = $application['nick'];
    }
    return $application_nicks;
}

function get_taobao_shop_list() {
	// 外包设置商品支持拼多多店铺 added by qyyao at 2016-06-16
    global $db;
    $sql = "SELECT nick,application_key FROM ecshop.taobao_shop_conf " .
    		" WHERE shop_type in ('taobao' , 'pinduoduo' )  and party_id = '".$_SESSION['party_id']."'";
    $application_list = $db->getAll($sql);
    return $application_list;
}

function get_group_list($offset=0,$limit=0) {
	global  $db;
	$sql = "
			select eosgc.*,tsc.nick,es.shipping_name,f.facility_name,GROUP_CONCAT(r.region_name) region_name,now() as nowstamp
			from ecshop.ecs_out_ship_goods_configure eosgc
			inner join ecshop.taobao_shop_conf tsc on tsc.application_key = eosgc.application_key
			inner join ecshop.ecs_shipping es on es.shipping_id = eosgc.out_ship_id
			inner join romeo.facility f on f.facility_id = eosgc.facility_id
			left join ecshop.ecs_region r on find_in_set(r.region_id,eosgc.province_list)
			where eosgc.party_id = '{$_SESSION['party_id']}' and eosgc.status = 'OK'
			group by eosgc.out_ship_goods_id  order by eosgc.update_time desc limit $offset,$limit  
		";	
	
	$group_list = $db->getAll($sql);
	
	return $db->getAll($sql);
}

function get_group_total() {
	global  $db;
	$sql = "select count(*) from ecshop.ecs_out_ship_goods_configure  eosgc
			inner join ecshop.taobao_shop_conf tsc on tsc.application_key = eosgc.application_key where eosgc.party_id = '{$_SESSION['party_id']}' and eosgc.status = 'OK' "; 
	return $db->getOne($sql);
}

//添加选择快递方式的选项。   by qxu 2013-6-25
$get_shippings = getShippingTypes();
//所有可用的快递方式
$smarty->assign('get_shippings', $get_shippings);
//所有该用户权限下的外包仓
$get_facilities = get_available_outShip_facility();

$smarty->assign('get_facilities', $get_facilities);


//总记录数
$total = get_group_total();
// 分页 
$page_size = 50;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;
//商品列表
$group_list = get_group_list($offset,$limit);

$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'taobao_out_ship_goods_configure.php', null);
// $Pager = Pager($total, $page_size, $page,'taobao_out_ship_goods_configure.php'); 
$smarty->assign('total', $total);  // 总数
$smarty->assign('group_list', $group_list);  //商品列表
 $smarty->assign('pagination', $pagination->get_simple_output());  // 分页
// $smarty->assign('Pager', $Pager);  // 分页 
$smarty->assign('party_id', $_SESSION['party_id']);
$smarty->display("taobao/taobao_out_ship_goods_configure.htm");
?>