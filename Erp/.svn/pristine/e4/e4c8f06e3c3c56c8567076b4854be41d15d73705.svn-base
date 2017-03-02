<?php 

/**
 * 查询已录入顾客信息
 * 
 * @author sfyuan@leqee.com
 * @copyright 2012 leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
admin_priv('carriage_manage');


// 分页
$page_size_list =
	array('20'=>'20','50'=>'50','100'=>'100');	

// 期初时间
$start = 
	isset($_REQUEST['start']) && !empty($_REQUEST['start']) && strtotime($_REQUEST['start'])!==false
	? $_REQUEST['start']
	: date('Y-m-d');
	
// 期末时间
$ended =
	isset($_REQUEST['ended']) && !empty($_REQUEST['ended']) && strtotime($_REQUEST['ended'])!==false
	? $_REQUEST['ended']
	: date('Y-m-d');

// 每页数据量
$page_size = 
    is_numeric($_REQUEST['size']) && in_array($_REQUEST['size'], $page_size_list)
    ? $_REQUEST['size']
    : 20;
// 页码
$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$start)){
	$start_datetime=$start.' 00:00:01';
}
else{
	$start_datetime=$start;
}
if(preg_match("/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/",$ended)){
	$ended_datetime=$ended.' 23:59:59';
}
else{
	$ended_datetime=$ended;
}


$username = $_SESSION['admin_name'];
$act =
isset($_REQUEST['act'])
? $_REQUEST['act']
: null;
$fenqu_id = $_REQUEST['fenqu_id'];
$sql = "select carrier_id from ecshop.ecs_inter_carrier_partition where fenqu_id = '{$fenqu_id}'";
$carrier_id = $db->getOne($sql);
$handle_time = strtotime(date("Y-m-d H:i:s"));
$first_weight = trim($_POST['first_weight']);
$continue_weight = trim($_POST['continue_weight']);
$first_fee = trim($_POST['first_fee']);
$continue_fee = trim($_POST['continue_fee']);
$type = trim($_POST['type']);
if('update'==$act)
{
	$info_id = $_REQUEST['info_id'];
	$sql = "select distinct id, ci.region_id as fenqu_id, type, first_weight,continue_weight,first_fee,continue_fee,carrier_id,from_unixtime(date) as date,from_unixtime(update_time) as update_time,operator, re.region_name
			from
			ecshop.ecs_inter_carriage ci
			left join ecshop.ecs_region re on re.region_id = ci.region_id
			where  ci.id = '{$info_id}'";
	$edit_info = $db->getRow($sql);
	$smarty->assign('edit_info',$edit_info);
}
else if('update_submit'==$act)
{
	if(empty($fenqu_id)){
		header("Location:carriage_search.php?message=".urlencode("分区未选择"));
		exit();
	}
	$date = strtotime(trim($_POST['date']));
	
	$info_id = trim($_POST['info_id']);
	$sql = "update ecshop.ecs_inter_carriage 
			set type='{$type}', first_weight = '{$first_weight}', continue_weight = '{$continue_weight}', first_fee = '{$first_fee}', continue_fee = '{$continue_fee}', date = '{$date}',region_id = '{$fenqu_id}',carrier_id = '{$carrier_id}',update_time = '{$handle_time}',operator = '{$username}'
			where id = '{$info_id}'
	";
	$db->query($sql);
	header("Location:carriage_search.php?message=".urlencode("更新成功"));
	exit();
}


$start_datetime = strtotime($start_datetime);//translate into unixtime
$ended_datetime = strtotime($ended_datetime);
	

find_carriage( $page, $page_size, $start_datetime, $ended_datetime, $fenqu_id);
	

$sql = "select * from ecs_inter_carrier_partition where 1";
$fenqu_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'fenqu_id','fenqu_name');
foreach ($fenqu_list as $key => $fenqu){
	$sql = "select carrier_id from ecshop.ecs_inter_carrier_partition where fenqu_id = '{$key}'";
	$carrier_id_temp = $db->getOne($sql);
	if($carrier_id_temp==27||$carrier_id_temp==25){
		$temp_fenqu_list[$key] = $fenqu;
	}

}
$fenqu_list = $temp_fenqu_list;

$smarty ->assign('fenqu_list', $fenqu_list);	
	



$smarty->assign("page_size_list",$page_size_list);

$smarty->assign("start",$start);
$smarty->assign("ended",$ended);

$smarty->display('carriage_search.htm');


function find_carriage( $page, $page_size, $start_datetime, $ended_datetime, $fenqu_id){
	global $db;
	global $smarty;
	$sql = "select count(distinct ci.id)
			from
			ecs_inter_carriage ci
			where
			ci.date >= '{$start_datetime}' and ci.date <= '{$ended_datetime}' and ci.region_id = '{$fenqu_id}'
	";
	$total = $db->getOne($sql);
	//分页     @param $total 一共多少页   $page_size 一页显示多少行  $page 当前第几页
	
	if($total)
	{
		$Pager = Pager($total, $page_size, $page);
		$page = ($page -1)*$page_size;
	
		$sql = "select distinct ci.region_id, id,type, first_weight,continue_weight,first_fee,continue_fee,carrier_id,from_unixtime(date) as date,from_unixtime(update_time) as update_time,operator, re.region_name
				from
				ecshop.ecs_inter_carriage ci
				left join ecshop.ecs_region re on re.region_id = ci.region_id
				where
				ci.date > '{$start_datetime}'
				and ci.date <'{$ended_datetime}'
				and ci.region_id = '{$fenqu_id}'
				limit {$page} , {$page_size}
		";
		$carriage_list = $db->getAll($sql);
		$smarty->assign('carriage_list',$carriage_list);
		$smarty->assign("Pager",$Pager);
	}
		
	
}



?>


