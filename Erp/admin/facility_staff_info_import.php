<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once("function.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH.'/includes/helper/array.php');
require_once(ROOT_PATH.'/includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');

global $db;

/* 检查权限 */
admin_priv('facility_staff_info_import');

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
$exist='0'; //初始化，0为不重复，1为重复
$json = new JSON;    
switch ($act) 
{ 
	case 'insert':
		$staff_name = trim ( $_REQUEST ['staff_name'] );
		$staff_number = trim ( $_REQUEST ['staff_number'] );
		$facilities = trim ( $_REQUEST ['facility'] );
		switch ($facilities) {
			case 'shanghai':
				$facility="上海仓";
				break;
			case 'jiashan':
				$facility="嘉善仓";
				break;
			case 'wuhan':
				$facility="武汉仓";
				break;
			case 'chengdu':
				$facility="成都仓";
				break;
			case 'beijing':
				$facility="北京仓";
				break;
			case 'dongguan':
				$facility="东莞仓";
				break;
			case 'shenzhen':
				$facility="深圳仓";
				break;
			default:
				$facility="";
				break;
		}
		$created_user=$_SESSION['admin_name'];
		$now_time=date('y-m-d h:i:s',time()); 
		// echo $staff_name.$staff_number.$facilities;

		// 判断员工编码是否重复，重复则不插入数据
		$sqlc = "select 1 from romeo.batch_pick_employee 
		    where employee_no = '{$staff_number}' limit 1";
		$is_exists = $db->getOne($sqlc);
		if($is_exists){
			$return['flag'] = 'exist';
			print $json->encode($return);
		}else{
			$sql = "INSERT INTO romeo.batch_pick_employee(employee_name,employee_no,physical_address,created_user,created_stamp) VALUES ('{$staff_name}','{$staff_number}','{$facility}','{$created_user}','{$now_time}')";
			$result = $db -> query($sql);
			if($result == true) {
				$response['result'] = 1;
			}
			echo json_encode($response);
			exit();
		}
		// $names = array();
		// foreach ($name_list as $key => $value) {
		// 	$names[] = $value;//取键值对中的值放在数组里
		// };
		// for ($i=0;$i < count($names);$i++) { 
		// 	// var_dump($names[$i]['employee_no']);
		// 	if($staff_number==$names[$i]['employee_no']){
		// 		$return['flag'] = 'exist';
		// 		print $json->encode($return);
		// 		$exist = '1';
		// 		break;
		// 	}
		// };
		// $return['flag'] = 'SUCCESS';
		// $return['message'] = '成功';
		// print $json->encode($return);
		exit;
		break;
	}

$result = call_user_func('add_staff_info',$_GET);
$smarty->assign('result',$result);
$smarty->assign('Pager',$result[-1]['pager']);
$smarty->display('shipment/facility_staff_info_import.htm');
	
function add_staff_info($args){
	global $db;
	$index = 0;
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 15;
	$offset = $limit * ($page-1);
	$sqlc = "SELECT count(1) FROM romeo.batch_pick_employee WHERE 1";	
	$total = $db ->getOne($sqlc);//符合条件的数据总条数
	$sql =  "select * from romeo.batch_pick_employee bpe 
		    where  1  order by bpe.employee_id desc limit {$limit} offset {$offset}";   
	// print_r($sql.'SQL03');
	$staff_list = $db->getAll($sql);
	if(!empty($staff_list)){
		foreach($staff_list as $staff){
			$result[$index]['staff_name']=$staff['employee_name'];
			$result[$index]['staff_number']=$staff['employee_no'];
			$result[$index]['facility']=$staff['physical_address'];
			$result[$index]['created_user']=$staff['created_user'];
			$result[$index]['now_time']=$staff['created_stamp'];
			$index++;
		}
	}
	$result[-1]['pager'] = pager($total,$limit,$page);
	// print_r($result);
	return $result;
}
?>