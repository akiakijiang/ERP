<?php
/*
 * 发货单查询、打印
 * zxcheng 2013-10-10
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('config.vars.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once ('includes/debug/lib_log.php');
if (isset($_REQUEST['sugu_print']) && $_REQUEST['sugu_print']){
	$sp=$_REQUEST['sugu_print'];
} else $sp=0;
if(isset($_REQUEST['size']) && $_REQUEST['size']){
    $page_size=$_REQUEST['size'];
}else $page_size=20;
if(isset($_REQUEST['page']) && $_REQUEST['page']){
	$sp=0;
    $page=$_REQUEST['page'];
}else $page=1;

//查询每页显示数列表
$page_size_list = array(
	'20' => '20',
	'50' => '50', 
	'100' => '100',
	'65535' => '不分页'
);
//发货单状态列表
$shipping_status_list = array(
	'-1' => '请选择发货单状态',
	'0' => '待配货', 
	'1' => '已发货',
	'2' => '收货确认',
	'11' => '已追回',
	'12' => '已拣货,待复核', 
	'13' => '拣货中', 
);
//快递运单类型
$shipping_category_list = array(
	'-1' => '请选择快递运单类型',
	'SHIPPING_SEND' => '正常销售订单运单', 
	'SHIPPING_RETURN' => '返回单运单',
	'SHIPPING_INVOICE' => '补寄发票运单',
);
//筛选时间列表 sinri:这种然并卵的排序把sql搞得那么慢
$sort_time_list = array(
	'-1' => '请选择排序时间',
	// '0' => '预定时间', 
	// '1' => '确认时间',
	'2' => '下单时间',
	// '3' => '批拣单生成时间',
);
$shipment_id = $_REQUEST['shipment_id'];
$condition = getCondition ();
if($condition==''){
	$shipment_bills = array();
}else{
	$shipment_bills = get_shipment_bill($condition);
}

$total=sizeof($shipment_bills);// 总记录数

//构造分页
$total_page=ceil($total/$page_size);  // 总页数
$page=max(1, min($page, $total_page));
$offset=($page-1)*$page_size;
$limit=$page_size;
//分页
if($page_size<65535){
	$shipment_bills=array_splice($shipment_bills, $offset, $limit);
}
//分页
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url, null, $filter
);

$start_validity_time = ($_REQUEST ['start_validity_time']?$_REQUEST ['start_validity_time']:date("Y-m-d", time()-3600*24*15)); //15 days ago;
$end_validity_time = $_REQUEST ['end_validity_time'];

$smarty->assign('start_validity_time',$start_validity_time);
$smarty->assign('end_validity_time',$end_validity_time);

$smarty->assign('shipping_status_list', $shipping_status_list); //发货单状态列表
$smarty->assign('shipping_category_list', $shipping_category_list); //快递运单类型
$smarty->assign('sort_time_list', $sort_time_list);          //筛选时间列表
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('shipment_bills', $shipment_bills);          //发货单列表
$smarty->assign('pagination', $pagination->get_simple_output()); // 分页	
$smarty->assign('total', $total); //总记录数

/**
 *初始筛选条件：除了以发货的，订单状态为已确认的，预定成功的。
 * and s.STATUS = 'SHIPMENT_INPUT' and s.shipping_category = 'SHIPPING_SEND' 
 */
function get_shipment_bill($condition){
	global $db;
	//pp($condition);exit();
	//初始化的情况
	//添加筛选条件的情况
	if($_REQUEST['TRACKING_NUMBER']!= '' || $_REQUEST['SHIPMENT_ID']!= ''){
		$join_tables="romeo.order_shipment m
		inner join ecshop.ecs_order_info oi on cast(m.ORDER_ID  as UNSIGNED)= oi.ORDER_ID
		";
	}else{
		$join_tables="ecshop.ecs_order_info oi use index (order_sn,order_info_multi_index)
		inner join romeo.order_shipment m on m.ORDER_ID=convert(oi.ORDER_ID using utf8)
		";
	}
   $sql ="SELECT 
   			es.shipping_name, 
   			s.SHIPMENT_ID,
   			bpm.batch_pick_sn,
   			bp.is_pick,
   			bpm.grid_id,
   			oi.order_id,
   			oi.order_sn,
   			s.TRACKING_NUMBER,
   			oi.shipping_status,
   			oi.facility_id,
   			oi.party_id
   			-- , (SELECT oa.action_time from ecshop.ecs_order_action oa  WHERE  oa.order_id = oi.order_id AND oa.order_status ='1' ORDER BY oa.action_time LIMIT 1) AS action_time -- 订单确认时间
		from 
		{$join_tables}
		inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID 
		inner join ecshop.ecs_shipping es on es.shipping_id=oi.shipping_id
		LEFT  JOIN romeo.batch_pick_mapping bpm ON bpm.shipment_id = s.shipment_id 
	   	LEFT JOIN romeo.batch_pick  bp ON bp.batch_pick_sn=bpm.batch_pick_sn
		where  ". facility_sql('oi.FACILITY_ID') ." and ". party_sql('oi.PARTY_ID')."
        {$condition}
        limit 600
        ";
        // die($sql);
//    QLog::log("get_shipment_bill SQL=$sql");
    $results=$db->getAll($sql);
    //仓库和组织转换成中文名称
    foreach ($results as $no => $result) {
    	$results[$no]['shipping_status_name'] = get_shipping_status($result['shipping_status']);
    	$results[$no]['party_name'] = party_mapping($result['party_id']);
    	$results[$no]['facility_name'] = facility_mapping($result['facility_id']);
    	$results[$no]['is_pick'] = ($result['is_pick']=='Y')?'已批拣':(($result['is_pick']=='N')?'未批拣':(($result['is_pick']=='S')?'批拣有问题':''));
    }
    return $results;
}
/**
 * 获取筛选条件
 */
function getCondition() {
	$condition = "";
	$sort_time = $_REQUEST ['sort_method'];
	$SHIPMENT_ID = $_REQUEST ['SHIPMENT_ID'];
	$order_sn = $_REQUEST ['order_sn'];
	$TRACKING_NUMBER = $_REQUEST ['TRACKING_NUMBER'];
	$shipping_status = $_REQUEST ['shipping_status'];
	$shipping_category = $_REQUEST ['shipping_category'];
	$start_validity_time = ($_REQUEST ['start_validity_time']?$_REQUEST ['start_validity_time']:date("Y-m-d", time()-3600*24*15)); //15 days ago;
	$end_validity_time = $_REQUEST ['end_validity_time'];
	if ($start_validity_time || $end_validity_time) {
		// $end_validity_time++;
		if ($start_validity_time) {
			$condition .= " AND oi.order_time >= '{$start_validity_time}' ";
		}
		if ($end_validity_time) {
			$condition .= " AND oi.order_time <= '{$end_validity_time}' ";
		}
	}
	if ($SHIPMENT_ID != '') {
		$condition .= " AND s.SHIPMENT_ID = '{$SHIPMENT_ID}' ";
	}
	if ($order_sn != '') {
		$condition .= " AND oi.order_sn = '{$order_sn}' ";
	}
	if ($TRACKING_NUMBER != '') {
		$condition .= " AND s.TRACKING_NUMBER = '{$TRACKING_NUMBER}' ";
	}
	if ($shipping_status != - 1 && $shipping_status !== null) {
		$condition .= " AND oi.shipping_status = '{$shipping_status}'";
	}
	if ($shipping_category != - 1 && $shipping_category !== null) {
		$condition .= " AND s.shipping_category = '{$shipping_category}'";
	}
	switch($sort_time){
        case "0" :
            // $condition .= " ORDER BY oi.RESERVED_TIME desc";
            break;
        case "1" :
            // $condition .= "  ORDER BY action_time desc";
            break;
        case "2" :
            $condition .= "  ORDER BY oi.order_time desc";
            break;
        case "3" :
            // $condition .= "  ORDER BY bpm.created_stamp desc";
            break;  
        default:
            break;
	}
	return $condition;
}
/**
 * 打印发货单
 */
 if($sp == 1){
 	require_once('includes/lib_print_action.php');
	LibPrintAction::addPrintRecord('SHIPMENT',$shipment_id);
 	 $smarty->assign('sp', $sp);
 	 $smarty->assign('shipment_id', $shipment_id);
 }
 $smarty->display('oukooext/search_shipment_bill.htm');
?>
