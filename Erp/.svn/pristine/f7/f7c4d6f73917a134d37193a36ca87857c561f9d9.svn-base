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

// 外部订单号 
$taobao_order_sn = isset($_REQUEST['taobao_order_sn']) && !empty($_REQUEST['taobao_order_sn']) && strlen(trim($_REQUEST['taobao_order_sn'])) > 5 
                ? $_REQUEST['taobao_order_sn']
                : ""; 
// 合并后订单的 order_sn 
$order_sn = isset($_REQUEST['order_sn']) && !empty($_REQUEST['order_sn']) && strlen(trim($_REQUEST['order_sn'])) > 5 
                ? $_REQUEST['order_sn']
                : ""; 
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

if(empty($order_sn) && empty($taobao_order_sn)){
    //合并发货的订单信息
    $sql = " select distinct oi.order_id 
             from  ecshop.order_relation orr  
             inner join ecshop.ecs_order_info oi  on orr.order_id = oi.order_id  and orr.parent_order_sn ='merge'
             where ". party_sql('oi.party_id')."and oi.order_time >= '{$start_datetime}' and oi.order_time <= '{$ended_datetime}' 
             "; 

}else{
    if(!empty($order_sn)){
        $sql = "select distinct oi.order_id 
                FROM ecshop.ecs_order_info oi 
                INNER JOIN ecshop.order_relation orr on orr.order_id = oi.order_id 
                where orr.parent_order_sn = 'merge' and ( orr.root_order_sn = '{$order_sn}'  OR orr.order_sn = '{$order_sn}' ) 
                and ".party_sql('oi.party_id');
        $taobao_order_sn = ""; 
    }else if(!empty($taobao_order_sn)){
            $sql = " select distinct orr.order_id as order_id 
             from ecshop.ecs_order_info oi 
             inner join ecshop.order_relation orr on orr.root_order_id = oi.order_id   
             where orr.parent_order_sn = 'merge'  and oi.taobao_order_sn like '{$taobao_order_sn}%' and ".party_sql('oi.party_id');
    }
}

$merge_to_order = $db->getCol($sql);
$total = count($merge_to_order); 
$db_in_order_id = array(); 
//分页     @param $total 一共多少页   $page_size 一页显示多少行  $page 当前第几页
$Pager = Pager($total, $page_size, $page);
$page = ($page -1)*$page_size;

if($total >=  $page_size){
    $sql = " select distinct oi.order_id 
         from  ecshop.order_relation orr  
         inner join ecshop.ecs_order_info oi  on orr.order_id = oi.order_id  and orr.parent_order_sn ='merge'
         where ". party_sql('oi.party_id')."and oi.order_time >= '{$start_datetime}' and oi.order_time <= '{$ended_datetime}' 
         limit {$page},{$page_size}  ";
    $db_in_order_id = $db->getCol($sql);
}else{
    $db_in_order_id = $merge_to_order; 
}

$order_list = array(); 
if(!empty($db_in_order_id)){
	$sql = "select oi.order_id as root_order_id,oi.order_sn as root_order_sn,
	         oi.consignee, 
		     oi.taobao_order_sn , oi.order_amount, 
		     orr.order_id , orr.order_sn 
		    from ecshop.ecs_order_info oi 
		    left join ecshop.order_relation orr on  oi.order_id = orr.root_order_id 
		    where ".db_create_in($db_in_order_id,'orr.order_id');
    

    $lists = $db->getAll($sql);
    if(!empty($lists)){
        foreach ($lists as $key => $list){
           // $order_list[$list['root_order_id']][$list['order_id']]['shipment_id'] .= $list['shipment_id'];
            $order_list[$list['order_id']][$list['root_order_id']]['root_order_sn']  .= $list['root_order_sn'];
            $order_list[$list['order_id']][$list['root_order_id']]['taobao_order_sn'] .= $list['taobao_order_sn'];
            $order_list[$list['order_id']][$list['root_order_id']]['order_amount'] .= $list['order_amount'];
            $order_list[$list['order_id']]['total_amount'] += $list['order_amount'];
            $order_list[$list['order_id']]['total_amount'] = erp_price_format($order_list[$list['order_id']]['total_amount']);
            $order_list[$list['order_id']]['order_id'] = $list['order_id']; 
            $order_list[$list['order_id']]['order_sn'] = $list['order_sn'];   
            $order_list[$list['order_id']]['consignee'] = $list['consignee'];   

        }
    }
} 

$smarty->assign("order_list",$order_list);
$smarty->assign("page_size_list",$page_size_list);
$smarty->assign("Pager",$Pager);
$smarty->assign("start",$start);
$smarty->assign("ended",$ended);
$smarty->assign("taobao_order_sn",$taobao_order_sn); 
$smarty->assign("order_sn",$order_sn);  

$smarty->display('order/order_shipment_list_new_order.htm');
