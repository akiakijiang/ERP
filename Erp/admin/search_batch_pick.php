<?php
/*
 * 批拣单查询、打印
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once('includes/lib_sinri_DealPrint.php');
require_once ('includes/debug/lib_log.php');
if (isset($_REQUEST['sn']) && $_REQUEST['sn']){
	$sn=$_REQUEST['sn'];
} else {
	$sn=false;
}
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

//消息
$message = 
    isset($_REQUEST['message']) && trim($_REQUEST['message']) 
    ? $_REQUEST['message'] 
    : false;
    
//批拣状态列表
$is_pick_list = array(
	'-1' => '所有状态',
	'Y'  => '已批拣',
	'N'  => '未批拣',
	'S'  => '批拣有问题',
);

$flag = true;
//1.避免批拣单量大 ，界面初始化时不需要显示查询结果
//2.搜索条件为空也不可以
//发货单状态列表
$shipping_status_list = $_CFG['adminvars']['shipping_status'];
$shipping_status_list['-1'] = '所有状态';
ksort($shipping_status_list);
$condition = getCondition ();
if($sp==0 ||$condition == ''){
	$flag = false;
	$total_message = "如果长时间处于批拣中,请链接进入'批拣单号',核实是否由于缺货导致,将信息反馈运营";
}else{
	if(empty($_REQUEST ['start_validity_time'])){
		$start_validity_time = date("Y-m-d",strtotime("-7 days",time()));
		$condition .= " AND bp.created_stamp >= '{$start_validity_time}' ";
		$_REQUEST ['start_validity_time'] = $start_validity_time;
	}else{
		$start_validity_time = $_REQUEST ['start_validity_time'];
	}
	$smarty->assign('start_validity_time',$start_validity_time);
  $lists = list_recent_BPs($condition);
  $batch_pick_sn = '';
  //对查询出来的记录重新组装，便于清楚的显示
  //将订单发货单状态的数量进行统计
  
  // 统计批捡单的sku数
  $bpsn_skus = $bpsn_all = array();
  foreach ( $lists as $key => $list ){
  	 $bpsn_all[] = $list['batch_pick_sn'];
  }
  $bpsn_skus = get_bpsn_sku_num($bpsn_all);
  
  $batch_pick_lists = array();
  foreach ( $lists as $key => $list ){
  	$bpsn = $list['batch_pick_sn'];
  	if(!array_key_exists($bpsn, $batch_pick_lists))
  	{
  		 $batch_pick_lists[$bpsn]['sku_num'] = $bpsn_skus[$bpsn];
     	 $batch_pick_lists[$bpsn]['batch_pick_sn'] = $bpsn;
     	 $is_printed = check_batch_pick_carrier_bill_all_printed($list['batch_pick_sn']);//批拣单是否打印
     	 $batch_pick_lists[$bpsn]['is_printed'] = $is_printed['success'] ? "面单已打印" : "面单未打印";
       	 $batch_pick_lists[$bpsn]['action_user'] = $list['action_user'];
       	 $batch_pick_lists[$bpsn]['employee_name'] = $list['employee_name'];
       	 $batch_pick_lists[$bpsn]['employee_no'] = $list['employee_no'];
       	 $batch_pick_lists[$bpsn]['employee_bind_stamp'] = $list['employee_bind_stamp'];
       	 $batch_pick_lists[$bpsn]['is_pick'] = $list['is_pick'];
       	 $batch_pick_lists[$bpsn]['pick_status'] = $list['pick_status']; // 是否批捡
       	 $batch_pick_lists[$bpsn]['created_stamp'] = $list['created_stamp'];
       	 $batch_pick_lists[$bpsn]['last_updated_stamp'] = $list['last_updated_stamp'];
       	 $batch_pick_lists[$bpsn]['status'] = array();
  	}
  	$shipping_status = get_shipping_status($list['shipping_status']);
  	$shipping_status_list[$list['shipping_status']] = $shipping_status;
  	$batch_pick_lists[$bpsn]['status'][$shipping_status] = $list['count'];
  }
  
  $total = sizeof($batch_pick_lists);
  $total_message ="<div style='color:red;'>如果长时间处于批拣中,请链接进入'批拣单号',核实是否由于缺货导致,将信息反馈运营</div>共计{$total}条批拣记录"; 
  //构造分页
  $total_page=ceil($total/$page_size);  // 总页数
  $page=max(1, min($page, $total_page));
  $offset=($page-1)*$page_size;
  $limit=$page_size;
  //分页
  if($page_size<65535){
	$batch_pick_lists=array_splice($batch_pick_lists, $offset, $limit);
  }
  //分页
  $pagination = new Pagination(
    $total, $page_size, $page, 'page', $url, null, $filter
  );
  $smarty->assign('batch_pick_lists', $batch_pick_lists);//批拣单列表
  $smarty->assign('pagination', $pagination->get_simple_output());  //分页	
}
$smarty->assign('message', $message);

/**
 * 单个批拣单打印
 */
 if($sp == 1){
 	$results = get_batch_pick_path_merged($sn);
 	$location_total=sizeof($results);
 	$smarty->assign('batch_bill_info', $results);//批拣单打印内容
 	$smarty->assign('location_count', $location_total);
 }
 $smarty->assign('flag', $flag);//是否初始进入界面判断
 $smarty->assign('total_message', $total_message);//红色中文提示
 $smarty->assign('is_pick_list', $is_pick_list);//批拣状态
 $smarty->assign('shipping_status_list', $shipping_status_list);//发货单状态
 $smarty->assign('sp', $sp); //打印
 $smarty->assign('sn', $sn); //batch_pick_sn
 

 $smarty->display('oukooext/search_batch_pick.htm');
 
 
 
 /**
 * 获取筛选条件
 */
function getCondition() {
	$condition = "";
	$batch_pick_sn = $_REQUEST ['batch_pick_sn'];
	$is_pick = $_REQUEST ['is_pick'];
	$shipping_status = $_REQUEST ['shipping_status'];
	$action_user = $_REQUEST ['action_user'];
	$employee_no = trim($_REQUEST ['employee_no']);
	$start_validity_time = $_REQUEST ['start_validity_time'];
	$end_validity_time = $_REQUEST ['end_validity_time'];
	if ($start_validity_time || $end_validity_time) {
		if ($start_validity_time) {
			$condition .= " AND bp.created_stamp >= '{$start_validity_time}' ";
		}
		if ($end_validity_time) {
			$end_validity_time++;
			$condition .= " AND bp.created_stamp <= '{$end_validity_time}' ";
		}
	}
	if ($batch_pick_sn != '') {
		$condition .= " AND bp.batch_pick_sn = '{$batch_pick_sn}' ";
	}
	if ($action_user != '') {
		$condition .= " AND bp.action_user LIKE '%{$action_user}%' ";
	}
	if(trim($employee_no) !='' ){
		$condition .= " AND bpe.employee_no = '{$employee_no}' ";
	}
	if($is_pick =='-1' && $is_pick !=null){
		$condition .= " AND 1";
	}
	else
	{
		$condition .= " AND bp.is_pick= '{$is_pick}'";		
	}
	if($shipping_status !='-1' && $shipping_status !=null){
		$condition .= " AND oi.shipping_status = '{$shipping_status}' ";
	}
	return $condition;
}
?>
