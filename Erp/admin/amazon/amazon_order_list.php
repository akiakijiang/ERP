<?php
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('amazon_order_list');


$smarty->assign('transfer_status_list',array(
	'ALL' => 'ALL',
	'NORMAL' => 'NORMAL',
	'ERROR' => 'ERROR',
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
    	case("search_county"):
			
			$city = $_POST['city'];
			$state_or_region = $_POST['state_or_region'];
			
			//判断$receiver_state是否以“省”结尾，$receiver_city是否以“市”结尾
			$state_or_region = (mb_substr($state_or_region,-3)== "省")?substr($state_or_region,0,-3):$state_or_region;
			$city= (mb_substr($city,-3)== "市")?substr($city,0,-3):$city;
			
			$arr_replace_receiver = array("香港特别行政区" => "香港","内蒙古自治区" => "内蒙古","广西壮族自治区" => "广西","新疆维吾尔自治区" => "新疆","宁夏回族自治区" => "宁夏",
"西藏自治区" => "西藏","新界" => "新界(東區)","大兴安岭地区" => "大兴安岭","恩施土家族苗族自治州" => "恩施","阿坝藏族羌族自治州" => "阿坝","甘孜藏族自治州" => "甘孜",
"凉山彝族自治州" => "凉山","铜仁地区" => "铜仁","毕节地区" => "毕节","黔西南布依族苗族自治州" => "黔西南","黔东南苗族侗族自治州" => "黔东南",
"黔南布依族苗族自治州" => "黔南","文山壮族苗族自治州" => "文山","昌都地区" => "昌都","山南地区" => "山南","那曲地区" => "那曲","阿里地区" => "阿里","林芝地区" => "林芝","临夏回族自治州" => "临夏",
"甘南藏族自治州" => "甘南","海北藏族自治州" => "海北州","黄南藏族自治州" => "黄南州","海南藏族自治州" => "海南州","果洛藏族自治州" => "果洛州","玉树藏族自治州" => "玉树州","海西蒙古族藏族自治州" => "海西州","吐鲁番地区" => "吐鲁番",
"哈密地区" => "哈密","和田地区" => "和田","喀什地区" => "喀什","克孜勒苏柯尔克孜自治州" => "克孜勒苏","巴音郭楞蒙古自治州" => "巴音郭楞","昌吉回族自治州" => "昌吉",
"博尔塔拉蒙古自治州" => "博尔塔拉","伊犁哈萨克自治州" => "伊犁","塔城地区" => "塔城","阿勒泰地区" => "阿勒泰","麻阳苗族自治县" => "麻阳自治县","积石山保安族东乡族撒拉族自治县" => "积石山保安族东乡",
"崇明县" => "崇明区","江华瑶族自治县" => "江华自治县");
			
			$replace_state_or_region = $state_or_region;
			$replace_city = $city;
			
			while( $now_state_or_region = key($arr_replace_receiver)){
				if($state_or_region == $now_state_or_region){
					$replace_state_or_region = current($arr_replace_receiver);
					break;
				}else{
					next($arr_replace_receiver);
				}
			}
//			QLog::log("replace_state = ".$replace_state);
			reset($arr_replace_receiver);
			while($now_city = key($arr_replace_receiver)){
				if($city == $now_city){
					$replace_city = current($arr_replace_receiver);
					break;
				}else{
					next($arr_replace_receiver);
				}
			}
//			QLog::log("replace_city = ".$replace_city);
			if($replace_state_or_region != '北京'&& $replace_state_or_region != '上海'&& $replace_state_or_region != '天津'&& $replace_state_or_region != '重庆'){
				$sql = "select region_id, region_name from ecshop.ecs_region where region_type= 3 and parent_id in(
		select r1.region_id from ecshop.ecs_region r1 where r1.region_type = 2 and r1.region_name like '%".$replace_city."%' and r1.parent_id in(
		select r0.region_id from ecshop.ecs_region r0 where r0.region_type = 1 and r0.region_name like '%".$replace_state_or_region."%'))";
			}else{
				$sql = "select region_id, region_name from ecshop.ecs_region where region_type= 2 and parent_id in(
		select r0.region_id from ecshop.ecs_region r0 where r0.region_type = 1 and r0.region_name like '%".$replace_state_or_region."%')";
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
	$amazon_order_id = $_GET['amazon_order_id'];
	$sql = "UPDATE ecshop.sync_amazon_order_info SET county = (select " .
			" region_name from ecshop.ecs_region  where region_id ='$region_id'),transfer_status = 'NORMAL'" .
			" WHERE order_number ='$order_number' ";
 	$abc = $db->query($sql);
 	header("Location:amazon_order_list.php");
}			

if($act == "search"){
	$condition = get_condition();
	$sql = "SELECT amazon_order_id, purchase_date, transfer_status, transfer_note, state_or_region, 
				city, county
			FROM ecshop.sync_amazon_order_info
			WHERE".$condition." order by purchase_date desc";
	$result_list = $db->getAll($sql);
	
	$order_list = array();
	foreach($result_list as $index=>$result) {
		unset($advice);
		if($result['transfer_status']=='ERROR') {
			$advice = '手工录单';
		} else if($result['transfer_status']=='WARN') {
			
			if($result['state_or_region']!=null && $result['city']!=null && $result['county']==null) {
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
$smarty->assign('amazon_order_id',trim($_REQUEST['amazon_order_id']));
$smarty->assign('buyer_name',trim($_REQUEST['buyer_name']));
$smarty->assign('transfer_status',trim($_REQUEST['transfer_status']));
$smarty->assign('order_list',$order_list);
$smarty->display('amazon/amazon_order_list.htm');

function get_condition(){
	$startTime = isset($_REQUEST['startDate']) ? $_REQUEST['startDate'] : '';
	$endTime = isset($_REQUEST['endDate']) ? $_REQUEST['endDate'] : '';
	$condition = " party_id = ".$_SESSION['party_id'];
	if( trim($_REQUEST['amazon_order_id']) != '' ){
		$condition .= " AND amazon_order_id='".trim($_REQUEST['amazon_order_id'])."'";
	}
	if( trim($_REQUEST['buyer_name']) != '' ){
		$condition .= " AND buyer_name = '".trim($_REQUEST['buyer_name'])."'";
	}
	if( trim($_REQUEST['transfer_status']) != 'ALL' ){
		$condition .= " AND transfer_status = '".trim($_REQUEST['transfer_status'])."'";
	}
	if($startTime && $endTime){
		$condition .= " AND purchase_date > '".trim($_REQUEST['startDate'])."' AND purchase_date <= '".trim($_REQUEST['endDate'])."'";
	}
	//$condition .= " AND party_id = '".$_SESSION['party_id']."'";
	
	return $condition;
}
?>
