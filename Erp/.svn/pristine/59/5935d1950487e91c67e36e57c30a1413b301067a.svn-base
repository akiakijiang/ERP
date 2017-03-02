<?php
define('IN_ECS', true);

require('includes/init.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('carriage_manage');

$act =
isset($_REQUEST['act'])
? $_REQUEST['act']
: null;
$info_id = trim($_POST['info_id']);

$fenqu_id = $_POST['fenqu_id'];
$sql = "select carrier_id from ecshop.ecs_inter_carrier_partition where fenqu_id = '{$fenqu_id}'";
$carrier_id = $db->getOne($sql);
$username = $_SESSION['admin_name'];
$handle_time = strtotime(date("Y-m-d H:i:s"));
$today = strtotime(date("Y-m-d"));
$message='';

$date = strtotime(trim($_REQUEST['region_time']));
$name_chs = trim($_REQUEST['region_name_chs']);
$partition_en = $_POST['partition'];

$sql = "select region_name 
		from ecshop.ecs_region
		where region_id = '{$partition_en['country']}'
";
$name_en = $db->getOne($sql);
if('update'==$act)
{
	$info_id = $_REQUEST['info_id'];
	$sql = "SELECT id,pi.region_id,pi.fenqu_id,pi.region_name_en,pi.region_name_chs,pi.carrier_id,from_unixtime(pi.date) as date,from_unixtime(pi.update_time) as update_time,pi.operator,re.region_name as fenqu_name
			 FROM ecshop.ecs_inter_partition pi
			 left join ecshop.ecs_region re on re.region_id = pi.fenqu_id 
			WHERE id = '{$info_id}'";
	$edit_info = $db->getRow($sql);
	$smarty->assign('edit_info',$edit_info);	
}
else if('update_submit'==$act)
{	
	if(empty($fenqu_id)){
		header("Location:partition_entry.php?message=".urlencode("分区未选择"));
		exit();
	}
	$sql = "update ecshop.ecs_inter_partition 
			set fenqu_id = '{$fenqu_id}', carrier_id = '{$carrier_id}', region_id = '{$partition_en['country']}', region_name_chs = '{$name_chs}',region_name_en = '{$name_en}',date = '{$date}',update_time = '{$handle_time}',operator = '{$username}'
		    where id = '{$info_id}'";
	$db->query($sql);	
	header("Location:partition_entry.php?message=".urlencode("更新成功"));
	exit();
}
else if('admit'==$act){
	partition_insert($partition_en['country'],$fenqu_id,$name_en,$name_chs,$carrier_id,$date,$handle_time,$username);
}
else if('info_delete'==$act)
{
	if(empty($_POST['checked'])){
			header("Location:partition_entry.php?message=".urlencode("没有选中要删除的信息"));
			exit();
	}		
	try{
		$sql="delete from ecshop.ecs_inter_partition where id".db_create_in($_POST['checked']);
		$GLOBALS ['db']->query($sql);
		header("Location:partition_entry.php?message=".urlencode("删除成功"));
		exit();
	}
	catch (Exception $e)
	{
		header("Location:partition_entry.php?message=".urlencode("删除发生异常"));
		exit();
	}	
}

$sql = "select * from ecs_region where region_type = 0";
$country_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'region_id','region_name');
foreach ($country_list as $key => $country){
	$temp = explode('_', $country);
	if(count($temp)>1){
		continue;
	}
	$temp_country_list[$key] = $country;
}
$country_list = $temp_country_list;
$sql = "select * from ecs_inter_carrier_partition where 1";
$fenqu_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'fenqu_id','fenqu_name');
$smarty ->assign('fenqu_list', $fenqu_list);


$sql = "select distinct
		id,pi.region_id,fenqu_id,region_name_en,region_name_chs,carrier_id,from_unixtime(date) as date,from_unixtime(update_time) as update_time,operator,re.region_name as fenqu_name
		from
		ecshop.ecs_inter_partition pi
		left join ecshop.ecs_region re on re.region_id = pi.fenqu_id
		where
		pi.update_time >='{$today}' 
		
";
$partition_list = $db->getAll($sql);
$smarty->assign('country_list', $country_list);

$smarty->assign('partition_list',$partition_list);
$smarty->display('partition_entry.htm');



//插入顾客资料
function partition_insert($region_id,$fenqu_id,$name_en,$name_chs,$carrier_id,$date,$handle_time,$operator)
{
	if(empty($fenqu_id)){
		header("Location:partition_entry.php?message=".urlencode("分区未选择"));
		exit();
	}
	try{
		
		$sql = "insert into ecshop.ecs_inter_partition (region_id, fenqu_id, region_name_en, region_name_chs, carrier_id, date, update_time,Operator)
				values
				('{$region_id}','{$fenqu_id}','{$name_en}','{$name_chs}','{$carrier_id}','{$date}','{$handle_time}','{$operator}')
		";
		$GLOBALS ['db']->query($sql);
		header("Location:partition_entry.php?message=".urlencode("录入成功"));
        exit();
	}
	catch (Exception $e)
	{
		header("Location:partition_entry.php?message=".urlencode("分区信息录入发生异常,请联系技术部门"));
        exit();
	}
	
}


?>