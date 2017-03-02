<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('yhd_order_list');


$smarty->assign('transfer_status_list',array(
	'ALL' => 'ALL',
	'NORMAL' => 'NORMAL',
	'ERROR' => 'ERROR',
	'SUCCESS' => 'SUCCESS',
	'WARN' => 'WARN'));
	
$request = isset($_REQUEST['request']) ? trim($_REQUEST['request']) : null ;
$act = $_REQUEST['act'] != '' ? $_REQUEST['act']:null;

/*
 * 处理ajax请求
 */
if ($request == 'ajax')
{
	$json = new JSON;
    switch ($act) 
    {
    	case("search_good_receiver_county"):
			
			$good_receiver_city = $_POST['good_receiver_city'];
			$good_receiver_province = $_POST['good_receiver_province'];
			
			//判断$receiver_state是否以“省”结尾，$receiver_good_receiver_city是否以“市”结尾
			$good_receiver_province = (mb_substr($good_receiver_province,-3)== "省")?substr($good_receiver_province,0,-3):$good_receiver_province;
			$good_receiver_city= (mb_substr($good_receiver_city,-3)== "市")?substr($good_receiver_city,0,-3):$good_receiver_city;
			
			$arr_replace_receiver = array("香港特别行政区" => "香港","内蒙古自治区" => "内蒙古","广西壮族自治区" => "广西","新疆维吾尔自治区" => "新疆","宁夏回族自治区" => "宁夏",
"西藏自治区" => "西藏","新界" => "新界(東區)","大兴安岭地区" => "大兴安岭","恩施土家族苗族自治州" => "恩施","阿坝藏族羌族自治州" => "阿坝","甘孜藏族自治州" => "甘孜",
"凉山彝族自治州" => "凉山","铜仁地区" => "铜仁","毕节地区" => "毕节","黔西南布依族苗族自治州" => "黔西南","黔东南苗族侗族自治州" => "黔东南",
"黔南布依族苗族自治州" => "黔南","文山壮族苗族自治州" => "文山","昌都地区" => "昌都","山南地区" => "山南","那曲地区" => "那曲","阿里地区" => "阿里","林芝地区" => "林芝","临夏回族自治州" => "临夏",
"甘南藏族自治州" => "甘南","海北藏族自治州" => "海北州","黄南藏族自治州" => "黄南州","海南藏族自治州" => "海南州","果洛藏族自治州" => "果洛州","玉树藏族自治州" => "玉树州","海西蒙古族藏族自治州" => "海西州","吐鲁番地区" => "吐鲁番",
"哈密地区" => "哈密","和田地区" => "和田","喀什地区" => "喀什","克孜勒苏柯尔克孜自治州" => "克孜勒苏","巴音郭楞蒙古自治州" => "巴音郭楞","昌吉回族自治州" => "昌吉",
"博尔塔拉蒙古自治州" => "博尔塔拉","伊犁哈萨克自治州" => "伊犁","塔城地区" => "塔城","阿勒泰地区" => "阿勒泰","麻阳苗族自治县" => "麻阳自治县","积石山保安族东乡族撒拉族自治县" => "积石山保安族东乡",
"崇明县" => "崇明区","江华瑶族自治县" => "江华自治县");
			
			$replace_good_receiver_province = $good_receiver_province;
			$replace_good_receiver_city = $good_receiver_city;
			
			while( $now_good_receiver_province = key($arr_replace_receiver)){
				if($good_receiver_province == $now_good_receiver_province){
					$replace_good_receiver_province = current($arr_replace_receiver);
					break;
				}else{
					next($arr_replace_receiver);
				}
			}
//			QLog::log("replace_state = ".$replace_state);
			reset($arr_replace_receiver);
			while($now_good_receiver_city = key($arr_replace_receiver)){
				if($good_receiver_city == $now_good_receiver_city){
					$replace_good_receiver_city = current($arr_replace_receiver);
					break;
				}else{
					next($arr_replace_receiver);
				}
			}
//			QLog::log("replace_good_receiver_city = ".$replace_good_receiver_city);
			if($replace_good_receiver_province != '北京'&& $replace_good_receiver_province != '上海'&& $replace_good_receiver_province != '天津'&& $replace_good_receiver_province != '重庆'){
				$sql = "select region_id, region_name from ecshop.ecs_region where region_type= 3 and parent_id in(
		select r1.region_id from ecshop.ecs_region r1 where r1.region_type = 2 and r1.region_name like '%".$replace_good_receiver_city."%' and r1.parent_id in(
		select r0.region_id from ecshop.ecs_region r0 where r0.region_type = 1 and r0.region_name like '%".$replace_good_receiver_province."%'))";
			}else{
				$sql = "select region_id, region_name from ecshop.ecs_region where region_type= 2 and parent_id in(
		select r0.region_id from ecshop.ecs_region r0 where r0.region_type = 1 and r0.region_name like '%".$replace_good_receiver_province."%')";
			}
			$result = $db->getAll($sql);
			if ($result) {
				print $json->encode($result); 
			} else{
            	print $json->encode(array('error' => '该市下不存在区划分'));
			}
		        
		 break;
	
	}
	exit;
}

if (isset($_GET['region_id']) && isset($_GET['order_number'])) {
	$region_id = $_GET['region_id'];
	$yhd_order_id = $_GET['yhd_order_id'];
	$sql = "UPDATE ecshop.sync_yhd_order_info SET good_receiver_county = (select " .
			" region_name from ecshop.ecs_region  where region_id ='$region_id'),transfer_status = 'NORMAL'" .
			" WHERE order_number ='$order_number' ";
 	$abc = $db->query($sql);
 	header("Location:yhd_order_list.php");
}			

if($act == "search"){
	$condition = get_condition();
	$sql = "SELECT yhd_order_id, order_create_time, transfer_status, transfer_note, good_receiver_province, 
				good_receiver_city, good_receiver_county,good_receiver_name
			FROM ecshop.sync_yhd_order_info
			WHERE".$condition." order by order_create_time desc";
	$result_list = $db->getAll($sql);
	
	$order_list = array();
	foreach($result_list as $index=>$result) {
		unset($advice);
		if($result['transfer_status']=='ERROR') {
			$advice = '手工录单';
		} else if($result['transfer_status']=='WARN') {
			
			if($result['good_receiver_province']!=null && $result['good_receiver_city']!=null && $result['good_receiver_county']==null) {
				$advice = '请查询并选择区信息';
			} else {
				$advice = '请联系erp';
			}
		} 
		$result['advice'] = $advice;
		$order_list[$index] = $result;
	}
	
}

$smarty->assign('valide_date',date('Y-m-d H:i:s',strtotime('-5 day')));
$smarty->assign('yhd_order_id',trim($_REQUEST['yhd_order_id']));
$smarty->assign('good_receiver_name',trim($_REQUEST['good_receiver_name']));
$smarty->assign('transfer_status',trim($_REQUEST['transfer_status']));
$smarty->assign('order_list',$order_list);
$smarty->display('yhd/yhd_order_list.htm');

function get_condition(){
	$startTime = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : '';
	$endTime = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : '';
	$condition = " party_id = ".$_SESSION['party_id'];
	if( trim($_REQUEST['yhd_order_id']) != '' ){
		$condition .= " AND yhd_order_id='".trim($_REQUEST['yhd_order_id'])."'";
	}
	if( trim($_REQUEST['good_receiver_name']) != '' ){
		$condition .= " AND good_receiver_name = '".trim($_REQUEST['good_receiver_name'])."'";
	}
	if( trim($_REQUEST['transfer_status']) != 'ALL' ){
		$condition .= " AND transfer_status = '".trim($_REQUEST['transfer_status'])."'";
	}
	if($startTime && $endTime){
		$condition .= " AND order_create_time > '".trim($_REQUEST['startDate'])."' AND order_create_time <= '".trim($_REQUEST['endDate'])."'";
	}
	//$condition .= " AND party_id = '".$_SESSION['party_id']."'";
	
	return $condition;
}
?>
