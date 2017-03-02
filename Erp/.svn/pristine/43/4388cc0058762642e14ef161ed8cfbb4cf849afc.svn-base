<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('includes/cls_pagination.php');
require_once ('function.php');
// include_once ('includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once ROOT_PATH . 'includes/cls_page.php';
require_once ROOT_PATH . 'includes/debug/lib_log.php';
if(!in_array($_SESSION['party_id'],array('65586','65609'))  || !in_array($_SESSION['admin_name'] ,array('ljxu','ytchen'))){
	die("此页面仅用于亨氏与康贝 云擎测试");
}
//var_dump($_REQUEST);
if ($_REQUEST['act'] == 'list') {
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$shipping_id = $_REQUEST['shipping_id'];
	
	$condition = "";
	$url = "yq_out_ship_order.php?act=list";
	if ($start) {
		$condition .= " and eoi.order_time >= '{$start}'";	
		$url .= "&start={$start}";
	}
	if ($end) {
		$condition .= " and eoi.order_time < '{$end}'";	
		$url .= "&end={$end}";
	}
	
	if($shipping_id && $shipping_id != -1){
		$condition .= " and eoi.shipping_id = '{$shipping_id}' ";
		$url .= "&shipping_id={$shipping_id}";
	}
	$condition .=" and eoi.facility_id = '149849257' ";
	$sql = "select shipping_name,oi.order_id,order_sn " .
			" from ecshop.ecs_order_info oi " .
			" left join ecshop.ecs_out_ship_order_task ot on ot.outer_id =oi.order_id " .
			" where oi.party_id = '{$_SESSION['party_id']}' and  order_type_id = 'SALE' 
		and  order_status =1 and  pay_status = 2 and  oi.facility_id in ('149849257') and ot.outer_id is null ";
	global $db;
	$orders =$db->getAll($sql);
	$total = count($orders);
	$page_size = 100;
	$page = isset($_REQUEST['page']) && (is_numeric($_REQUEST['page']) > 0) ? $_REQUEST['page'] : 1 ;
	$offset = ($page - 1) * $page_size;
	$orders = $db->getAll($sql . " limit {$page_size} offset {$offset} ");
	$pagination = new Pagination($total, $page_size, $page ,'page' ,$url);
	
	
	$smarty->assign("start", $start);
	$smarty->assign("end", $end);
	$smarty->assign("shipping_id", $shipping_id);
	$smarty->assign('pagination',$pagination->get_simple_output());
    $smarty->assign('list',$orders);
}
if ($_REQUEST['act'] == 'search_task') {
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];	
	$condition = "";
	if ($start) {
		$condition .= " and t.start_time >= '{$start}'";	
	}
	if ($end) {
		$condition .= " and t.start_time < '{$end}'";	
	}
	
	$condition .=" and t.facility_id = '149849257' ";
	$tasks = showTask($condition);
	$smarty->assign("start", $start);
	$smarty->assign("end", $end);
	$smarty->assign("list", $tasks);
	$smarty->display('order/yq_out_ship_order_task.htm');
	exit();
}

if ($_REQUEST['act'] == 'showTask') {
	$smarty->assign("list", showTask());
	$smarty->display('order/yq_out_ship_order_task.htm');
	exit();
}

if ($_REQUEST['act'] == 'addTask') {
	if (checkTask()) {
		$smarty->assign("message", "打标任务创建失败，有人抢先一步了，请等待");
	}
	$order_ids = $_REQUEST['chk'];
	$start = $_REQUEST['hidStart'];
	$end = $_REQUEST['hidEnd'];
	global $db;
	$facility_id = '149849257';
	if(!empty($order_ids)){
		$error_orders = "";
		foreach($order_ids as $order_id){
			$shipping_id = $db->getOne("select shipping_id from ecshop.ecs_order_info where order_id = '{$order_id}' limit 1");
			$sql = "
				insert into ecshop.ecs_out_ship_order_task
					(party_id, outer_id, start_time, end_time, facility_id,shipping_id, out_ship_number, status, create_user, create_time, last_update_time)
				values 
					('{$_SESSION['party_id']}', '{$order_id}', '{$start}', '{$end}',  '{$facility_id}', '{$shipping_id}','1', 'INIT', '{$_SESSION['admin_name']}', now(), now())
			";
			if(!$db->query($sql)){
				$error_orders .= "".$order_id.",";
			}
		}
	}
	if($error_orders != ""){
		$smarty->assign("message", $error_orders."创建任务失败！其他打标任务已创建，请等待任务完成后再进行操作");
	}else{
		$smarty->assign("message", "打标任务已创建，请等待任务完成后再进行打标操作");
	}
	$smarty->assign("list", showTask());
	$smarty->display('order/yq_out_ship_order_task.htm');
	exit();
}

if($_REQUEST['act'] == 'export'){
	$task_id = isset($_REQUEST['task_id'])?$_REQUEST['task_id']:0;
	$sql = "
		select eoi.taobao_order_sn,consignee,mobile,r1.region_name province,r2.region_name city,r3.region_name district,eoi.address, 
		IF(gs.style_id IS NOT NULL,CONCAT_WS(',',g.goods_name,s.color),g.goods_name) gName,sum(og.goods_number) gNumber,
		IF(og.style_id != '0',CONCAT_WS('_',og.goods_id,og.style_id),og.goods_id) outer_id,IFnull(g.barcode,gs.barcode) barcode
		from  ecshop.ecs_out_ship_order eoso
		inner join ecshop.ecs_order_info eoi on eoi.order_id = eoso.order_id
		INNER JOIN ecshop.ecs_order_goods og on og.order_id = eoi.order_id
		inner join ecshop.ecs_out_ship_order_task t on t.task_id = eoso.task_id and t.facility_id = eoi.facility_id and t.shipping_id = eoi.shipping_id
		LEFT JOIN ecshop.ecs_goods g ON  g.goods_id = og.goods_id
		LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id  and gs.style_id = og.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id
		left join ecshop.ecs_region r1 on r1.region_id = eoi.province
		left join ecshop.ecs_region r2 on r2.region_id = eoi.city
		left join ecshop.ecs_region r3 on r3.region_id = eoi.district
		where t.task_id = '{$task_id}'
		and eoi.order_status = '1'
		and eoi.pay_status = '2'
		GROUP BY eoi.order_id,og.goods_id,og.style_id,g.barcode";
	$orders = $db->getAll($sql);
	$count = count($db->getAll("select * from ecshop.ecs_out_ship_order so 
		INNER JOIN ecshop.ecs_order_goods og on so.order_id = og.order_id 
		where task_id = '{$task_id}'  
		group by so.task_id,og.goods_id,og.style_id"));
	if(count($orders)."" != $count){
		$smarty->assign("message", "导出的数量为：".count($orders).",应该导出的数量为：".$count."。请联系ERP！");
		$smarty->assign("list", showTask());
		$smarty->display('order/yq_out_ship_order_task.htm');
		exit();
	}
	set_include_path(get_include_path() . 'admin/includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    
	$title = array(0 => array(
        '订单号', '收件人', '手机号码', '省', '市', '区', '地址',
		'ERP商品名称','商品数量','商家编码','条码'
    ));
    $type = array(0 => 'string',);
    
	$filename .= date("Y-m-d").".xlsx";
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $orders = array_map('array_values', $orders);
    if (!empty($orders)) {
        $sheet->fromArray($orders, null, 1, $type);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    exit();
}

if($_REQUEST['act'] == 'batch_export'){	
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$condition = "";
	if ($start) {
		$condition .= " and t.start_time >= '{$start}'";	
	}
	if ($end) {
		$condition .= " and t.start_time < '{$end}'";	
	}
	$tasks = showTask($condition);
	$task_ids=array();
	foreach ($tasks as $key => $oneline) {
        $task_ids[]=$oneline['task_id'];
    }
	$sql ="select eoi.taobao_order_sn,consignee,mobile,r1.region_name province,r2.region_name city,r3.region_name district,eoi.address, 
		IF(gs.style_id IS NOT NULL,CONCAT_WS(',',g.goods_name,s.color),g.goods_name) gName,sum(og.goods_number) gNumber,
		IF(og.style_id != '0',CONCAT_WS('_',og.goods_id,og.style_id),og.goods_id) outer_id,IFnull(g.barcode,gs.barcode) barcode
		from  ecshop.ecs_out_ship_order eoso
		inner join ecshop.ecs_order_info eoi on eoi.order_id = eoso.order_id
		INNER JOIN ecshop.ecs_order_goods og on og.order_id = eoi.order_id
		inner join ecshop.ecs_out_ship_order_task t on t.task_id = eoso.task_id and t.facility_id = eoi.facility_id and t.shipping_id = eoi.shipping_id
		LEFT JOIN ecshop.ecs_goods g ON  g.goods_id = og.goods_id
		LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id  and gs.style_id = og.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id
		left join ecshop.ecs_region r1 on r1.region_id = eoi.province
		left join ecshop.ecs_region r2 on r2.region_id = eoi.city
		left join ecshop.ecs_region r3 on r3.region_id = eoi.district
		where t.task_id  ".db_create_in($task_ids)."
		and eoi.order_status = '1'
		and eoi.pay_status = '2'
		GROUP BY eoi.order_id,og.goods_id,og.style_id,g.barcode";
	$orders = $db->getAll($sql);
	$count = count($db->getAll("select * from ecshop.ecs_out_ship_order  so 
		INNER JOIN ecshop.ecs_order_goods og on so.order_id = og.order_id
		where task_id ".db_create_in($task_ids)."
		GROUP BY so.task_id,og.goods_id,og.style_id"));
	if(count($orders)."" != $count){
		$smarty->assign("message", "导出的数量为：".count($orders).",应该导出的数量为：".$count."。请联系ERP！");
		$smarty->assign("list", $tasks);
		$smarty->assign("start", $start);
		$smarty->assign("end", $end);
		$smarty->assign("outer_id", $outer_id);
		$smarty->display('order/yq_out_ship_order_task.htm');
		exit();
	}
	set_include_path(get_include_path() . 'admin/includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    
	$title = array(0 => array(
        '订单号', '收件人', '手机号码', '省', '市', '区', '地址',
		'ERP商品名称','商品数量','商家编码','条码'
    ));
    $type = array(0 => 'string',);
    
	$filename .= date("Y-m-d").".xlsx";
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $orders = array_map('array_values', $orders);
    if (!empty($orders)) {
        $sheet->fromArray($orders, null, 1, $type);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    exit();
}


$smarty->assign("checkTask", checkTask());
$smarty->assign('shipping_list', get_shipping_list());
$smarty->display('order/yq_out_ship_order.htm');

function checkTask() {
	global $db;
	$sql = "select task_id from ecshop.ecs_out_ship_order_task where party_id = '{$_SESSION['party_id']}' and status in ('INIT', 'EXCUTE') and facility_id='149849257' limit 1 ";
	return $db->getOne($sql);
}

function showTask($condition = null) {
	global $db;
	$sql = "
		select t.task_id, t.outer_id, t.start_time, t.end_time, t.out_ship_number, t.status, t.create_user, t.create_time, t.last_update_time,f.facility_name,es.shipping_name, count(o.order_id) as marked_count
		from ecshop.ecs_out_ship_order_task t 
		left join ecshop.ecs_out_ship_order o on t.task_id = o.task_id
		left join romeo.facility f on t.facility_id = f.facility_id 
		left join ecshop.ecs_shipping es on es.shipping_id = t.shipping_id
		where t.party_id = '{$_SESSION['party_id']}' {$condition} and t.facility_id = '149849257' 
		group by t.task_id 
	";
	$tasks = $db->getAll($sql);
	if ($tasks) {
		foreach($tasks as $key => $task) {
			$sql = "select concat(goods_id,'_',style_id) from ecshop.ecs_order_goods where order_id = '{$task['outer_id']}' ";
			$goods_style_id = $db->getCol($sql);
			$goods_style_str = implode("|",$goods_style_id);
			if(strlen($goods_style_str)>10){
				$tasks[$key]['outer_goods_name'] = substr($goods_style_str,0,8)."...";
			}else{
				$tasks[$key]['outer_goods_name'] = $goods_style_str;
			}
		}
	}
	return $tasks;
}

/*
 * 检索正在使用的所有的快递 根据COD、与非COD区分
 */ 
 function get_shipping_list () {
 	global $db ;
 	
 	$sql = "select shipping_id, shipping_name from ecshop.ecs_shipping where support_no_cod = 1 and support_cod = 0 and enabled = 1 
             UNION ALL
            select shipping_id, shipping_name from ecshop.ecs_shipping where support_no_cod = 0 and support_cod = 1 and enabled = 1  ";
 	
 	$shipping_list = $db->getAll($sql) ;
 	
 	return $shipping_list ;
 }

?>