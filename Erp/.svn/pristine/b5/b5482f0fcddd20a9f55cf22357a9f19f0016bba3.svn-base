<?php
/**
 * 合并发货查看
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
admin_priv('merge_order','order_edit');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');


// 分页
$page_size_list =
	array('20'=>'20','50'=>'50','100'=>'100');	

$act = isset($_REQUEST['act']) && !empty($_REQUEST['act'])
	? $_REQUEST['act']
	: 'show';
 
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
$msg_list = null; 
$total = 0; // 总记录数 
// 查看短信发送列表 
if($act == 'search'){
	$mobile =  isset($_REQUEST['mobile']) && !empty($_REQUEST['mobile'])  
	? $_REQUEST['mobile']
	: "";
	$limit_offset = ($page - 1)*$page_size;
	$limit_size = $page_size; 
	$cond = ""; // 查询条件 
	if(!empty($mobile)){ // 按手机号查询  
		$cond = " send_time >'{$start_datetime}' and send_time <'{$ended_datetime}' and dest_mobile = '{$mobile}' "; 
	}else{ // 按时间查询 
		$cond = " send_time >'{$start_datetime}' and send_time <'{$ended_datetime}' "; 
	}
	$sql = "select * from message.message_history where {$cond}  order by history_id desc  limit $limit_offset,$limit_size ";  
    $msg_list = $db->getAll($sql);
    $this_total = count($msg_list); 
    if($this_total < $page_size){
    	$total = $this_total; 
    }else{
    	$total = $db->getOne("select count(1) from message.message_history where {$cond} "); 
    }

}

 
//分页     @param $total 一共多少页   $page_size 一页显示多少行  $page 当前第几页
$Pager = Pager($total, $page_size, $page);
$page = ($page -1)*$page_size;

if($total >=  $page_size){
    
}else{
   
}



$smarty->assign("msg_list",$msg_list);
$smarty->assign("mobile",$mobile);
$smarty->assign("page_size_list",$page_size_list);
$smarty->assign("Pager",$Pager);
$smarty->assign("start",$start);
$smarty->assign("ended",$ended);


$smarty->display('message_send_status_list.htm');
