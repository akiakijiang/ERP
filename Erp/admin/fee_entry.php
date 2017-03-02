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
$username = $_SESSION['admin_name'];
$fenqu_id = $_POST['fenqu_id'];

$sql = "select carrier_id from ecshop.ecs_inter_carrier_partition where fenqu_id = '{$fenqu_id}'";
$carrier_id = $db->getOne($sql);
$weight = trim($_POST['weight']);
$fee = trim($_POST['fee']);
$type = trim($_POST['type']);
$handle_time = strtotime(date("Y-m-d H:i:s"));
$today = strtotime(date("Y-m-d"));
$message='';

$date = strtotime(trim($_REQUEST['region_time']));

if('update'==$act)
{
	$info_id = $_REQUEST['info_id'];
	$sql = "SELECT id,fenqu_id, weight,type,fee,carrier_id,from_unixtime(date) as date,from_unixtime(update_time) as update_time,operator
			 FROM ecshop.ecs_inter_fee 
			WHERE id = '{$info_id}'";
	$edit_info = $db->getRow($sql);
	$smarty->assign('edit_info',$edit_info);	
}
else if('update_submit'==$act)
{	
	if(empty($fenqu_id)){
		header("Location:fee_entry.php?message=".urlencode("分区未选择"));
		exit();
	}
	$sql = "update ecshop.ecs_inter_fee 
			set fenqu_id = '{$fenqu_id}', carrier_id = '{$carrier_id}', weight = '{$weight}',type = '{$type}',fee = '{$fee}',date = '{$date}',update_time = '{$handle_time}',operator = '{$username}'
		    where id = '{$info_id}'";
	$db->query($sql);	
	header("Location:fee_entry.php?message=".urlencode("更新成功"));
	exit();
}
else if('admit'==$act){
	fee_insert($fenqu_id,$weight,$type,$fee,$carrier_id,$date,$handle_time,$username);
}
else if('info_delete'==$act)
{
	if(empty($_POST['checked'])){
			header("Location:fee_entry.php?message=".urlencode("没有选中要删除的信息"));
			exit();
	}		
	try{
		$sql="delete from ecshop.ecs_inter_fee where id".db_create_in($_POST['checked']);
		$GLOBALS ['db']->query($sql);
		header("Location:fee_entry.php?message=".urlencode("删除成功"));
		exit();
	}
	catch (Exception $e)
	{
		header("Location:fee_entry.php?message=".urlencode("删除发生异常"));
		exit();
	}	
}

$sql = "select * from ecs_inter_carrier_partition where 1";
$fenqu_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'fenqu_id','fenqu_name');
foreach ($fenqu_list as $key => $fenqu){
	$sql = "select carrier_id from ecshop.ecs_inter_carrier_partition where fenqu_id = '{$key}'";
	$carrier_id_temp = $db->getOne($sql);
	if($carrier_id_temp!=27&&$carrier_id_temp!=30){
		$temp_fenqu_list[$key] = $fenqu;
	}
	
}
$fenqu_list = $temp_fenqu_list;
$smarty ->assign('fenqu_list', $fenqu_list);


$sql = "select distinct
		id,fenqu_id,type,weight,fee,carrier_id,from_unixtime(date) as date,from_unixtime(update_time) as update_time,operator,re.region_name as fenqu_name
		from
		ecshop.ecs_inter_fee fi
		left join ecshop.ecs_region re on re.region_id = fi.fenqu_id
		where
		fi.update_time >='{$today}' 
		
";
$fee_list = $db->getAll($sql);
$smarty->assign('fee_list',$fee_list);
$smarty->display('fee_entry.htm');



//插入顾客资料
function fee_insert($fenqu_id,$weight,$type,$fee,$carrier_id,$date,$handle_time,$operator)
{
	if(empty($fenqu_id)){
		header("Location:fee_entry.php?message=".urlencode("分区未选择"));
		exit();
	}
	try{
		
		$sql = "insert into ecshop.ecs_inter_fee (fenqu_id, weight, type, fee, carrier_id, date, update_time,operator)
				values
				('{$fenqu_id}','{$weight}','{$type}','{$fee}','{$carrier_id}','{$date}','{$handle_time}','{$operator}')
		";
		$GLOBALS ['db']->query($sql);
		header("Location:fee_entry.php?message=".urlencode("录入成功"));
        exit();
	}
	catch (Exception $e)
	{
		header("Location:fee_entry.php?message=".urlencode("分区信息录入发生异常,请联系技术部门"));
        exit();
	}
	
}


?>