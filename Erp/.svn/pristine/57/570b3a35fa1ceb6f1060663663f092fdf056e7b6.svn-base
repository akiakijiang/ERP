<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
require_once (ROOT_PATH . 'includes/debug/lib_log.php');


$shop_list= array(
	'all' => '所有',
	'jinzhuang' => '天猫金装',
	'qifu' => '天猫启赋',
	'bozhen' => '天猫铂臻'
);
$shop_type=
	isset($_REQUEST['shop']) && trim($_REQUEST['shop'])
    ? $_REQUEST['shop']
    : '所有';
    
    
$cond = getCondition();
$result = search_user($cond); 


$smarty->assign('user_list',$result['user_list']);
$smarty->assign('shop_list',$shop_list);
$smarty->assign('message_error',$message_error);
$smarty->assign('message_success',$message_success);

if ($_REQUEST['type'] == '数据导出CSV') {
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . '惠氏报表导出CSV' . ".csv" );
	$out = $smarty->fetch ( 'oukooext/wyeth_report_csv.htm' );
//	echo iconv ( "UTF-8", "GB18030", $out );
	echo $out;
	exit ();
}


$smarty->display ( 'wyeth_report.html' );


function getCondition() {
	global $ecs;
	$result = array();
	$condition = "";
	$start_time = $_REQUEST ['start_time'];
	$end_time = $_REQUEST ['end_time'];
	$user_level = $_REQUEST ['user_level'];
	$flag = $_REQUEST ['flag'];
	$shop = trim ( $_REQUEST ['shop'] );
	if ($start_time != '') {
		$condition .= " AND last_updated_stamp > '{$start_time}' ";
	}
	if ($end_time != '') {
		$condition .= " AND last_updated_stamp < '{$end_time}' ";
	}
	if($user_level != ""){
		$condition .= " AND user_level = '{$user_level}' ";
	}
	if($flag != "" && $start_time=="" && $end_time==""){
		$condition .= " AND last_updated_stamp >date_add(now(),interval - 30 day) ";
	}else if($flag == ""){
		//$condition .= " AND last_updated_stamp >date_add(now(),interval - 30 day) ";
	}
	if($shop == "jinzhuang"){
		$condition .=" and platform = '天猫金装'";
	}else if($shop == "qifu"){
		$condition .=" and platform = '天猫启赋'";
	}else if($shop == "bozhen"){
		$condition .=" and platform = '天猫铂臻'";
	}
	
	$result['simple_cond'] = $condition;
	return $result;
}


function search_user($cond) {
	global $db;
	$sql = "select date(sign_time) as date,wyeth_user_id,To_Days(sign_time)-To_Days(edc) as times,
			date(date_add(last_updated_stamp, interval -1 day)) as last_updated_stamp from ecshop.brand_wyeth_user_info 
			where 1 {$cond['simple_cond']} and user_level !='0' order by last_updated_stamp desc";
	$user_list = $db->getAll($sql);
	$pregnant_days = 0;
	$pregnant_0_6 = 0;
	$pregnant_7_12 = 0;
	$pregnant_13_36 = 0;
	$pregnant_others = 0;
	$now_time = "";
	$last_time = "";
	$index = 0;
	foreach($user_list as $key => $value){
			$now_time = $value['last_updated_stamp'];
			$sperate = (strtotime($last_time)-strtotime($now_time))/(24*3600);
			if($sperate > 1)
			{
				$total = $pregnant_days+$pregnant_0_6+$pregnant_7_12+$pregnant_13_36+$pregnant_others;
				$days_list[$index]['last_updated_stamp'] = $last_time ;
				$days_list[$index]['total'] = $total;
				$days_list[$index]['pregnant_days']=$pregnant_days;
				$days_list[$index]['pregnant_0_6']=$pregnant_0_6;
				$days_list[$index]['pregnant_7_12']=$pregnant_7_12;
				$days_list[$index]['pregnant_13_36']=$pregnant_13_36;
				$days_list[$index]['pregnant_others']=$pregnant_others;
				$index++;
				for($i=1;$i<$sperate;$i++){
					$days_list[$index]['last_updated_stamp'] = date('Y-m-d', strtotime($last_time."-".$i." day")) ;
					$days_list[$index]['total'] = 0;
					$days_list[$index]['pregnant_days']=0;
					$days_list[$index]['pregnant_0_6']=0;
					$days_list[$index]['pregnant_7_12']=0;
					$days_list[$index]['pregnant_13_36']=0;
					$days_list[$index]['pregnant_others']=0;
					$index++;
					$pregnant_days = 0;
					$pregnant_0_6 = 0;
					$pregnant_7_12 = 0;
					$pregnant_13_36 = 0;
					$pregnant_others = 0;
					//continue;
				}
				$last_time = $now_time;
				continue;
			}
			if($last_time != "" && $now_time != $last_time){
				$total = $pregnant_days+$pregnant_0_6+$pregnant_7_12+$pregnant_13_36+$pregnant_others;
				$days_list[$index]['last_updated_stamp'] = $last_time ;
				$days_list[$index]['total'] = $total;
				$days_list[$index]['pregnant_days']=$pregnant_days;
				$days_list[$index]['pregnant_0_6']=$pregnant_0_6;
				$days_list[$index]['pregnant_7_12']=$pregnant_7_12;
				$days_list[$index]['pregnant_13_36']=$pregnant_13_36;
				$days_list[$index]['pregnant_others']=$pregnant_others;
				$index++;
				$pregnant_days = 0;
				$pregnant_0_6 = 0;
				$pregnant_7_12 = 0;
				$pregnant_13_36 = 0;
				$pregnant_others = 0;
			}
			$last_time = $now_time;
		
			$num = $value['times'];
			if($num < 0 && $num >-300){
				$pregnant_days++;
			}
			else if($num >0 && $num <=180){
				$pregnant_0_6++;
			}
			else if($num >180 && $num <=360){
				$pregnant_7_12++;
			}
			else if($num >360 && $num <=1080){
				$pregnant_13_36++;
			}else{
				$pregnant_others++;
			}
		
	}
	$total = $pregnant_days+$pregnant_0_6+$pregnant_7_12+$pregnant_13_36+$pregnant_others;
	$days_list[$index]['last_updated_stamp'] = $last_time ;
	$days_list[$index]['total'] = $total;
	$days_list[$index]['pregnant_days']=$pregnant_days;
	$days_list[$index]['pregnant_0_6']=$pregnant_0_6;
	$days_list[$index]['pregnant_7_12']=$pregnant_7_12;
	$days_list[$index]['pregnant_13_36']=$pregnant_13_36;
	$days_list[$index]['pregnant_others']=$pregnant_others;
	$result['user_list']=$days_list;
//	print_r($days_list);
	return $result;
}

?>
