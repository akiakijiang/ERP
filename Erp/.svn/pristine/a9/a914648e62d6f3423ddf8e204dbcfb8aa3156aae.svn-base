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


$act =
isset($_REQUEST['act'])
? $_REQUEST['act']
: null;
$fenqu_id = $_REQUEST['fenqu_id'];
$sql = "select carrier_id from ecshop.ecs_inter_carrier_partition where fenqu_id = '{$fenqu_id}'";
$carrier_id = $db->getOne($sql);
$handle_time = strtotime(date("Y-m-d H:i:s"));
$username = $_SESSION['admin_name'];
if('update'==$act)
{
	$info_id = $_REQUEST['info_id'];
	$sql = "select distinct
			id,fenqu_id,fuel,registration_fee,price_discounts,registration_fee_discounts,declaration_charges,from_unixtime(date) as date,from_unixtime(update_time) as update_time,operator,re.region_name as fenqu_name
			from
			ecshop.ecs_inter_discount di
			left join ecshop.ecs_region re on re.region_id = di.fenqu_id
			where  di.id = '{$info_id}'";
	$edit_info = $db->getRow($sql);
	$smarty->assign('edit_info',$edit_info);
}
else if('update_submit'==$act)
{
	if(empty($fenqu_id)){
		header("Location:discounts_search.php?message=".urlencode("分区未选择"));
		exit();
	}
	$date = strtotime(trim($_POST['date']));
	$fuel = trim($_POST['region_fuel']);
	$registration_fee = trim($_POST['registration_fee']);
	$price_discounts = trim($_POST['price_discounts']);
	$registration_fee_discounts = trim($_POST['registration_fee_discounts']);
	$declaration_charges = trim($_POST['declaration_charges']);
	$info_id = trim($_POST['info_id']);
	$sql = "update ecshop.ecs_inter_discount 
			set fenqu_id = '{$fenqu_id}', carrier_id = '{$carrier_id}', fuel = '{$fuel}', registration_fee = '{$registration_fee}',price_discounts = '{$price_discounts}',registration_fee_discounts = '{$registration_fee_discounts}',declaration_charges = '{$declaration_charges}',date = '{$date}',update_time = '{$handle_time}',operator = '{$username}'
		    where id = '{$info_id}'
	";
	$db->query($sql);
	header("Location:discounts_search.php?message=".urlencode("更新成功"));
	exit();
}


$start_datetime = strtotime($start_datetime);//translate into unixtime
$ended_datetime = strtotime($ended_datetime);


	
find_partition($page, $page_size, $start_datetime, $ended_datetime, $fenqu_id);
	

$sql = "select * from ecs_inter_carrier_partition where 1";
$fenqu_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'fenqu_id','fenqu_name');
$smarty ->assign('fenqu_list', $fenqu_list);	
$smarty->assign("page_size_list",$page_size_list);
$smarty->assign("start",$start);
$smarty->assign("ended",$ended);
$smarty->display('discounts_search.htm');


function find_partition($page, $page_size, $start_datetime, $ended_datetime, $fenqu_id){
	global $db;
	global $smarty;
	
	$sql = "select count(distinct di.id)
			from
			ecs_inter_discount di
			where
			di.date >= '{$start_datetime}' and di.date <= '{$ended_datetime}' and di.fenqu_id = '{$fenqu_id}'
	";
	$total = $db->getOne($sql);
	//分页     @param $total 一共多少页   $page_size 一页显示多少行  $page 当前第几页
	
	if($total)
	{
		$Pager = Pager($total, $page_size, $page);
		$page = ($page -1)*$page_size;
	
		$sql = "select distinct
				id,fenqu_id,fuel,registration_fee,price_discounts,registration_fee_discounts,declaration_charges,from_unixtime(date) as date,from_unixtime(update_time) as update_time,operator,re.region_name as fenqu_name, re.region_name
				from
				ecshop.ecs_inter_discount di
				left join ecshop.ecs_region re on re.region_id = di.fenqu_id
				where
				di.date > '{$start_datetime}'
				and di.date <'{$ended_datetime}'
				and di.fenqu_id = '{$fenqu_id}'
				limit {$page} , {$page_size}
		";
		$discounts_list = $db->getAll($sql);
		$smarty->assign('discounts_list',$discounts_list);
		$smarty->assign("Pager",$Pager);
	}
}



?>


