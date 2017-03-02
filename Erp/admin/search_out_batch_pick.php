<?php
/*
 * 批拣单查询、打印
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once ('includes/debug/lib_log.php');

admin_priv('ck_out_shipment_print');


//当前用户在当前业务组下的外包仓
$facility_list = get_available_outShip_facility();

if (isset($_REQUEST['sn']) && $_REQUEST['sn']){
	$sn=$_REQUEST['sn'];
} else {
	$sn=false;
}

// 批拣单
$batch_pick_sn = 
    isset($_REQUEST['batch_pick_sn']) && trim($_REQUEST['batch_pick_sn']) 
    ? trim($_REQUEST['batch_pick_sn']) 
    : null ;



//sp=0界面初始化 sp=1,2单打 sp=3，4批打
if (isset($_REQUEST['sugu_print']) && $_REQUEST['sugu_print']){
	$sp=$_REQUEST['sugu_print'];
} else $sp=0;
if(isset($_REQUEST['page']) && $_REQUEST['page']){
	$sp= 5;//翻页的时候屏蔽打印
    $page=$_REQUEST['page'];
}else $page=1;
//分页设置40条记录
$page_size=40;

if(empty($facility_list)){
	die('没有外包仓权限');
}
$facility_id = 
    isset($_REQUEST['facility_id']) && trim($_REQUEST['facility_id']) 
    ? trim($_REQUEST['facility_id']) 
    : null ;
if(empty($facility_id)){
	$facility_id = array_rand($facility_list);
}

$smarty->assign('available_facility', $facility_list);
$smarty->assign('facility_id', $facility_id);

//消息
$message = 
    isset($_REQUEST['message']) && trim($_REQUEST['message']) 
    ? $_REQUEST['message'] 
    : false;
    
//批拣状态列表
$is_pick_list = array(
	'N'  => '未打印',
	'Y'  => '已打印，未复核',
	'F'  => '已复核',
);

$flag = true;
//1.避免批拣单量大 ，界面初始化时不需要显示查询结果
//2.搜索条件为空也不可以

  $batch_pick_lists = getBatchPickList($facility_id);
  
  $total = sizeof($batch_pick_lists);
  $total_message ="共计{$total}条批拣记录"; 
  //构造分页
  $total_page=ceil($total/$page_size);  // 总页数
  $page=max(1, min($page, $total_page));
  $offset=($page-1)*$page_size;
  $limit=$page_size;
  //分页
  if($page_size < 65535){
	$batch_pick_lists=array_splice($batch_pick_lists, $offset, $limit);
  }
  //分页
  $pagination = new Pagination(
    $total, $page_size, $page, 'page', $url, null, $filter
  );
  $smarty->assign('batch_pick_lists', $batch_pick_lists);//批拣单列表
  $smarty->assign('pagination', $pagination->get_simple_output());  //分页	
 $smarty->assign('message', $message);


 $smarty->assign('total_message', $total_message);//红色中文提示
 $smarty->assign('is_pick_list', $is_pick_list);//批拣状态
 
 $smarty->display('oukooext/search_out_batch_pick.htm');
 
 /**
 * 获取筛选条件
 */
function getBatchPickList($facility_id) {
	global $db;
	$condition = "";
	// 批拣单
	$batch_pick_sn = 
	    isset($_REQUEST['batch_pick_sn']) && trim($_REQUEST['batch_pick_sn']) 
	    ? trim($_REQUEST['batch_pick_sn']) 
	    : null ;
	if (empty($batch_pick_sn)) {
		$start_validity_time = $_REQUEST ['start_validity_time'];
		$end_validity_time = $_REQUEST ['end_validity_time'];
		if ($start_validity_time || $end_validity_time) {
			if ($start_validity_time) {
				$condition .= " AND bpm.created_stamp >= '{$start_validity_time}' ";
			}
			if ($end_validity_time) {
				$end_validity_time++;
				$condition .= " AND bpm.created_stamp <= '{$end_validity_time}' ";
			}
		}
		$is_pick = $_REQUEST ['is_pick'];
		if($is_pick == 'N'){
			$condition .= " AND bp.print_number = 0 ";
		}else if($is_pick == 'Y'){
			$condition .= " AND bp.print_number > 0 AND bp.check_status = 'N' ";
		}else if($is_pick == 'F'){
			$condition .= " AND bp.print_number > 0 AND bp.check_status = 'F' ";
		}else{
			$condition .= " AND bp.print_number = 0 ";
		}
	}else{
		$condition .= " AND bp.batch_pick_sn = '{$batch_pick_sn}' ";
	}

	$sql = "SELECT bp.batch_pick_sn,bp.goods_name,bp.print_number,bp.print_note,bp.facility_id,bp.shipping_id,
				bp.created_stamp,if(bp.check_status='F','复核成功','未复核') as check_status,count(bpm.shipment_id) as shipment_number,t.task_id," .
						"t.province,t.city,sum(if(s.tracking_number is null or s.TRACKING_NUMBER ='',0,1)) as tracking_numbers
    		  from  romeo.out_batch_pick  bp
    		  	inner join romeo.out_batch_pick_mapping bpm on bp.batch_pick_id = bpm.batch_pick_id
    		  	inner join romeo.shipment s on bpm.shipment_id = s.shipment_id 
				inner join romeo.order_shipment os on s.shipment_id = os.shipment_id 
    		  	inner join ecshop.ecs_out_ship_order outo on cast(os.order_id as unsigned) = outo.order_id 
    		  	inner join ecshop.ecs_out_ship_order_task t  on t.task_id = outo.task_id
			   WHERE bp.facility_id = '{$facility_id}' and bp.party_id = '{$_SESSION['party_id']}' 
			  {$condition}
              GROUP BY bp.batch_pick_sn
              having shipment_number > 0 and shipment_number = tracking_numbers
              ORDER BY bp.created_stamp desc
              limit 4000 ";
    $result=$db->getAll($sql);
    foreach($result as $key=>$batch){
    	if(!empty($batch['province'])){
    		$province_sql = "select region_name from ecshop.ecs_region where region_id in ({$batch['province']})";
    		$province = $db->getCol($province_sql);
    		$result[$key]['province'] = implode(",",$province);
    	}
    	if(!empty($batch['city'])){
    		$city_sql = "select region_name from ecshop.ecs_region where region_id in ({$batch['city']})";
    		$city = $db->getCol($city_sql);
    		$result[$key]['city'] = implode(",",$city);
    	}
    }
	return $result;
}
?>
