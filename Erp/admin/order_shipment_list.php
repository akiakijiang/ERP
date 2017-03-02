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

//合并发货的订单信息
$sql = "select distinct os1.shipment_id from romeo.order_shipment os1, romeo.order_shipment os2, ecshop.ecs_order_info o1
        where os1.order_id != os2.order_id
        and os1.shipment_id = os2.shipment_id
        and convert(o1.order_id using utf8) = os1.order_id
        and ( ". party_sql('o1.party_id'). " )
        and ((o1.order_time >= '{$start_datetime}' and o1.order_time <= '{$ended_datetime}'))";  
//        and o1.order_status in (0,1) and o1.shipping_status=0 and o1.order_type_id in ('SALE','SHIP_ONLY','RMA_EXCHANGE')
$shipment = $db->getCol($sql);
$total = count($shipment);

if ($total != 0) {
    //分页     @param $total 一共多少页   $page_size 一页显示多少行  $page 当前第几页
    $Pager = Pager($total, $page_size, $page);
    $page = ($page -1)*$page_size;
    $sql = "select oi.order_id, oi.order_sn, oi.taobao_order_sn, oi.order_amount, os.shipment_id
    	    from ecshop.ecs_order_info oi
           left join romeo.order_shipment os 
           on cast(os.order_id as unsigned) = oi.order_id 
           where ". db_create_in($shipment,'os.shipment_id')."
            limit {$page},{$page_size} ";
    $lists = $db->getAll($sql);
    if(!empty($lists)){
        foreach ($lists as $key => $list){
            $order_list[$list['shipment_id']][$list['order_id']]['shipment_id'] .= $list['shipment_id'];
            $order_list[$list['shipment_id']][$list['order_id']]['order_sn']  .= $list['order_sn'];
            $order_list[$list['shipment_id']][$list['order_id']]['taobao_order_sn'] .= $list['taobao_order_sn'];
            $order_list[$list['shipment_id']][$list['order_id']]['order_amount'] .= $list['order_amount'];
            $order_list[$list['shipment_id']]['total_amount'] += $list['order_amount'];
            $order_list[$list['shipment_id']]['total_amount'] = erp_price_format($order_list[$list['shipment_id']]['total_amount']);
        }
    }
    $smarty->assign("order_list",$order_list);
}

$smarty->assign("page_size_list",$page_size_list);
$smarty->assign("Pager",$Pager);
$smarty->assign("start",$start);
$smarty->assign("ended",$ended);

$smarty->display('order/order_shipment_list.htm');
