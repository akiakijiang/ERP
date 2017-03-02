<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('includes/cls_pagination.php');
require_once ('function.php');
// include_once ('includes/lib_order_mixed_status.php');//By Sinri
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once ROOT_PATH . 'includes/cls_page.php';
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
//require_once(ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv ('ck_out_ship_order');
if($_REQUEST['act'] == 'search_outer_id') {
	$json = new JSON;
	$sql = "select if(gs.style_id is null, g.goods_id, concat(g.goods_id, '_', gs.style_id)) as outer_id, 1 as is_tc
			from ecshop.ecs_goods g
			left join ecshop.ecs_goods_style gs on g.goods_id = gs.goods_id and gs.is_delete=0
			where g.goods_id like '%{$_POST['q']}%' and 
			g.goods_party_id = {$_SESSION['party_id']} and 
			g.is_delete = 0 
			union all 
			select g.code as outer_id, 2 as is_tc
			from ecshop.distribution_group_goods g 
			where g.code like '%{$_POST['q']}%' and
			g.party_id = {$_SESSION['party_id']} and 
			g.status = 'OK'"; 
	print $json->encode($db->getAll($sql));
	exit();
}

//当前用户在当前业务组下的外包仓
$facility_list = get_available_outShip_facility();

if ($_REQUEST['act'] == 'list') {
	global $db;
	
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$facility_id = $_REQUEST['facility_id'];
	$outer_id = $_REQUEST['outer_id'];
	$shipping_id = $_REQUEST['shipping_id'];
	$goods_number = $_REQUEST['goods_number'];
	$provinces=$_REQUEST['provinces'];
	$cities=$_REQUEST['cities'];
	
	$condition = "";
	$url = "out_ship_order.php?act=list";
	if ($start) {
		$condition .= " and eoi.order_time >= '{$start}'";	
		$url .= "&start={$start}";
	}else{
		$start=date('Y-m-d', strtotime('-7 day'));
		$condition .= " and eoi.order_time >= '{$start}'";	
		$url .= "&start={$start}";
	}
	if ($end) {
		$condition .= " and eoi.order_time < '{$end}'";	
		$url .= "&end={$end}";
	}else{
		$end = date('Y-m-d',strtotime('1 day',time()));
		$condition .= " and eoi.order_time < '{$end}'";	
		$url .= "&end={$end}";
	}
	if($facility_id && $facility_id != -1) {
		$condition .= " and eoi.facility_id = '{$facility_id}'";
		$url .= "&facility_id={$facility_id}";
	}else{
		foreach ( $facility_list as $key => $value ) {
       		$facility .= "'{$key}', ";
		}
		
		$condition .= " and eoi.facility_id in (".substr($facility, 0, strlen($facility)-2).")";
	}
	if($shipping_id && $shipping_id != -1){
		$condition .= " and eoi.shipping_id = '{$shipping_id}' ";
		$url .= "&shipping_id={$shipping_id}";
	}
	
	$province_name='';
	$city_name='';
	if($provinces && $provinces != ''){
		$url .= "&provinces={$provinces}";
		$province = substr($provinces, 0, strlen($provinces)-1);
		$condition .= " and eoi.province in ({$province}) ";
		$Psql = "select region_name from ecshop.ecs_region where region_id in ({$province})";
		$province_name = implode(',',$db->getCol($Psql));
	}
	
	if($cities && $cities != ''){
		$url .= "&cities={$cities}";
		$city = substr($cities, 0, strlen($cities)-1);
		$condition .= " and eoi.city in ({$city}) ";
		$Csql = "select region_name from ecshop.ecs_region where region_id in ({$city})";
		$city_name = implode(',',$db->getCol($Csql));
	}
	
	$sql="";
	if($outer_id){
		if(strpos($outer_id, 'TC-') !== false){
			$TCsql="SELECT GROUP_CONCAT(CONCAT(dggi.goods_id, '_',dggi.style_id, '_',dggi.goods_number) order by goods_id, style_id, goods_number)  tc_group_concat
					FROM ecshop.distribution_group_goods dgg
					INNER JOIN ecshop.distribution_group_goods_item dggi ON dgg.group_id = dggi.group_id
					WHERE dgg.`code` = '{$outer_id}'
					group by dgg.group_id";
			$tc_group_concat=$db->getOne($TCsql);
			$sql="select t.facility_id, 
					(select facility_name from romeo.facility f where f.facility_id = t.facility_id) facility_name, 
					(select shipping_name from ecshop.ecs_shipping es where es.shipping_id = t.shipping_id) shipping_name, 
					t.shipping_id, t.outer_id, '{$province_name}' as province_name, '{$city_name}' as city_name, 
					SUM(t.unconfirm_count) as unconfirm_count,
					SUM(t.unreserved_count) as unreserved_count,
					SUM(t.unmark_count) as unmark_count,
					SUM(t.marked_count) as marked_count
				from (
					select eoi.facility_id, eoi.shipping_id, eog.group_code as outer_id,
						if(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 0, 1, 0) unconfirm_count,
						IF(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 1 AND (oir.`STATUS` = 'N' OR oir.ORDER_ID IS NULL), 1, 0) unreserved_count,
						if(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 1 AND oir.`STATUS` = 'Y', 1, 0) unmark_count,
						if(outo.order_id is null, 0, 1) marked_count
					from ecshop.ecs_order_info eoi force index(order_info_multi_index)
					inner join ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id AND eog.group_code = '{$outer_id}' 
					LEFT JOIN ecshop.ecs_order_goods eog1 ON eoi.order_id = eog1.order_id AND eog1.group_code != '{$outer_id}'
					LEFT JOIN romeo.order_inv_reserved oir ON eoi.order_id = oir.ORDER_ID
					left join ecshop.ecs_out_ship_order outo on eoi.order_id = outo.order_id
					where eoi.party_id = {$_SESSION['party_id']} and eoi.order_type_id = 'SALE' 
					and eoi.order_status in (0,1) and eoi.pay_status = 2 
					AND eog.group_number = 1 AND eog1.rec_id IS NULL
					{$condition}
					group by eoi.order_id 
					having group_concat(concat(eog.goods_id, '_', eog.style_id, '_', eog.goods_number) order by eog.goods_id, eog.style_id, eog.goods_number) = '{$tc_group_concat}'
				) as t
				group by t.facility_id, t.outer_id, t.shipping_id
				";
		}else{ //单品的group_code为空
			$goods = explode('_', $outer_id);
			$goods_id = $goods[0];
			$style_id = isset($goods[1])?$goods[1]:0;
			$sql="SELECT t.facility_id,  
					(select facility_name from romeo.facility f where f.facility_id = t.facility_id) facility_name, 
					(select shipping_name from ecshop.ecs_shipping es where es.shipping_id = t.shipping_id) shipping_name, 
					t.shipping_id, t.outer_id, '{$province_name}' as province_name, '{$city_name}' as city_name, 
					SUM(t.unconfirm_count) as unconfirm_count,
					SUM(t.unreserved_count) as unreserved_count,
					SUM(t.unmark_count) as unmark_count,
					SUM(t.marked_count) as marked_count
				from (
					select eoi.facility_id, eoi.shipping_id,if(eog.style_id=0,eog.goods_id,concat(eog.goods_id,'_',eog.style_id)) outer_id,
						if(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 0, 1, 0) unconfirm_count,
						IF(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 1 AND (oir.`STATUS` = 'N' OR oir.ORDER_ID IS NULL), 1, 0) unreserved_count,
						if(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 1 AND oir.`STATUS` = 'Y', 1, 0) unmark_count,
						if(outo.order_id is null, 0, 1) marked_count
					from ecshop.ecs_order_info eoi force INDEX(order_info_multi_index)
					inner join ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id AND eog.goods_id = {$goods_id} AND eog.style_id = {$style_id} 
					LEFT JOIN ecshop.ecs_order_goods eog1 ON eoi.order_id = eog1.order_id AND (eog1.goods_id != {$goods_id} OR eog1.style_id != {$style_id})
					LEFT JOIN romeo.order_inv_reserved oir ON eoi.order_id = oir.ORDER_ID
					left join ecshop.ecs_out_ship_order outo on eoi.order_id = outo.order_id
					where eoi.party_id = {$_SESSION['party_id']} and eoi.order_type_id = 'SALE' 
					and eoi.order_status in (0,1) and eoi.pay_status = 2 
					AND eog.goods_number = 1 AND eog.group_code = '' AND eog1.rec_id IS NULL  
					{$condition}
					group by eoi.order_id
					having sum(eog.goods_number) = 1) as t 
				group by t.facility_id, t.outer_id, t.shipping_id
				";
		}
		$url .= "&outer_id={$outer_id}";
	}else{
		$sql="select t.facility_id,  
					(select facility_name from romeo.facility f where f.facility_id = t.facility_id) facility_name, 
					(select shipping_name from ecshop.ecs_shipping es where es.shipping_id = t.shipping_id) shipping_name, 
					t.shipping_id, t.outer_id, '{$province_name}' as province_name, '{$city_name}' as city_name, CONCAT(t.outer_id,'_',t.facility_id,'_',t.shipping_id) as k,
					SUM(t.unconfirm_count) as unconfirm_count,
					SUM(t.unreserved_count) as unreserved_count,
					SUM(t.unmark_count) as unmark_count,
					SUM(t.marked_count) as marked_count
			FROM (
				select eoi.facility_id, eoi.shipping_id,if(eog.group_code != '', eog.group_code, if(eog.style_id=0,eog.goods_id,concat(eog.goods_id,'_',eog.style_id))) outer_id,
					if(eog.group_code != '', eog.group_number, sum(eog.goods_number)) as outer_number,  
					if(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 0, 1, 0) unconfirm_count,
					IF(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 1 AND (oir.`STATUS` = 'N' OR oir.ORDER_ID IS NULL), 1, 0) unreserved_count,
					if(outo.order_id is null and eoi.shipping_status = 0 and eoi.order_status = 1 AND oir.`STATUS` = 'Y', 1, 0) unmark_count,
					if(outo.order_id is null, 0, 1) marked_count
				from ecshop.ecs_order_info eoi force INDEX(order_info_multi_index)
				inner join ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id 
				LEFT JOIN romeo.order_inv_reserved oir ON eoi.order_id = oir.ORDER_ID
				left join ecshop.ecs_out_ship_order outo on eoi.order_id = outo.order_id
				where eoi.party_id = {$_SESSION['party_id']} and eoi.order_type_id = 'SALE' 
				and eoi.order_status in (0,1) and eoi.pay_status = 2 
				{$condition}
				group by eoi.order_id 
				having count(distinct outer_id) = 1 AND outer_number = 1
			) as t
			group by t.facility_id, t.outer_id, t.shipping_id
			";
	}
//	Qlog::log($sql);
	$orders = $db->getAll($sql);
	$total = count($orders);
	
	$page_size = 200;
	$page = isset($_REQUEST['page']) && (is_numeric($_REQUEST['page']) > 0) ? $_REQUEST['page'] : 1 ;
	
	if($total > 200){
		$offset = ($page - 1) * $page_size;
		$orders = $db->getAll($sql . " limit {$page_size} offset {$offset} ");
	}
	$pagination = new Pagination($total, $page_size, $page ,'page' ,$url);
	
	if ($orders) {
		foreach($orders as $key => $order) {
			$orders[$key]['outer_goods_name'] = getGoodsName($order['outer_id']);
		}
	}
	
	$sql="SELECT CONCAT(outer_id,'_',facility_id,'_',shipping_id) as k FROM ecshop.ecs_out_ship_order_task WHERE party_id = {$_SESSION['party_id']}  AND status = 'INIT' ";
	$smarty->assign('iTask',$db->getCol($sql));
	
	$smarty->assign("start", $start);
	$smarty->assign("end", $end);
	$smarty->assign("facility_id", $facility_id);
	$smarty->assign("shipping_id", $shipping_id);
	$smarty->assign("outer_id", $outer_id);
	$smarty->assign("is_tc", $ic_tc);
	$smarty->assign("goods_number", $goods_number);
	$smarty->assign("province", $province);
	$smarty->assign("city", $city);
	$smarty->assign("party_id", $_SESSION['party_id']);
	$smarty->assign('pagination',$pagination->get_simple_output());
    $smarty->assign('list',$orders);
}
if ($_REQUEST['act'] == 'search_task') {
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];	
	$outer_id = $_REQUEST['outer_id'];
	$facility_id = $_REQUEST['facility_id'];
	$condition = "";
	if ($start) {
		$condition .= " and t.start_time >= '{$start}'";	
	}
	if ($end) {
		$condition .= " and t.start_time < '{$end}'";	
	}
	if ($outer_id) {
		$condition .= " and t.outer_id = '{$outer_id}'";
	}
	$condition .=" and t.facility_id = '{$facility_id}' ";
	$tasks = showTask($condition);
	$smarty->assign("outer_id", $outer_id);
	$smarty->assign("start", $start);
	$smarty->assign("end", $end);
	$smarty->assign("facility_id", $facility_id);
	$smarty->assign('facility_list',$facility_list);
	$smarty->assign("list", $tasks);
	$smarty->display('order/out_ship_order_task.htm');
	exit();
}

if ($_REQUEST['act'] == 'showTask') {
	$end = isset($start) ? $end : date('Y-m-d H:i:s', time());
	$start = isset($start) ? $start : date('Y-m-d H:i:s', strtotime("-5 day",time()));
	$smarty->assign('end', $end);
	$smarty->assign('start', $start);
	$smarty->assign('facility_list',$facility_list);
//	$smarty->assign("list", showTask( " and t.facility_id != '149849257' "));
	$smarty->display('order/out_ship_order_task.htm');
	exit();
}

if ($_REQUEST['act'] == 'addTask') {
	if (checkTask()) {
		$smarty->assign("message", "打标任务创建失败，有人抢先一步了，请等待");
	}
	
	global $db;
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$facility_id = $_REQUEST['facility_id'];
	$outer_id = $_REQUEST['outer_id'];
	$count = $_REQUEST['count'];
	$shipping_id = $_REQUEST['shipping_id'];
	$goods_number = $_REQUEST['goods_number'];
	$province = $_REQUEST['province'];
	$city = $_REQUEST['city'];
	$party_id = $_REQUEST['party_id'];
	$sql = "
		insert into ecshop.ecs_out_ship_order_task
			(party_id, outer_id, start_time, end_time, facility_id, province, city, shipping_id, out_ship_number, goods_number, status, create_user, create_time, last_update_time)
		values 
			('{$party_id}', '{$outer_id}', '{$start}', '{$end}', '{$facility_id}', '{$province}', '{$city}', '{$shipping_id}','{$count}', '{$goods_number}', 'INIT', '{$_SESSION['admin_name']}', now(), now())
	";
	$db->query($sql);
	$smarty->assign("message", "打标任务已创建，请等待任务完成后再进行打标操作");
	$smarty->assign("start", $start);
	$smarty->assign("end", $end);
	$smarty->assign("facility_id", $facility_id);
	$smarty->assign("facility_list",$facility_list);
	$smarty->assign("outer_id", $outer_id);
	$condition = "";
	if ($outer_id && $outer_id != '') {
		$condition .= " AND t.outer_id = '{$outer_id}'";
	}
	if ($facility_id && $facility_id != '') {
		$condition .= " AND t.facility_id = '{$facility_id}' ";
	}
	$smarty->assign("list", showTask(" AND t.start_time >= '{$start}' AND t.start_time < '{$end}'".$condition));
	$smarty->display('order/out_ship_order_task.htm');
//	header("Location: out_ship_order.php"); 
	exit();
}

if($_REQUEST['act'] == 'export'){
	$task_id = isset($_REQUEST['task_id'])?$_REQUEST['task_id']:0;
		
	$sql="select eoi.taobao_order_sn,'' as from_name,'' as from_tel,'' as from_province,'' as from_city,'' as from_district,'' as from_address,'' as from_zipcode,consignee,mobile,r1.region_name province,r2.region_name city,r3.region_name district,eoi.address,'' as datoubi,
		zipcode,IF(eog.group_code != '','',g.goods_weight) gWeight,stog.title,IF(eog.group_code != '',eog.group_number,eog.goods_number) gNum,
		order_time,IF(eog.group_code != '',eog.group_name,IF(gs.style_id IS NOT NULL,CONCAT_WS(',',g.goods_name,s.color),g.goods_name)) gName,t.outer_id, IF(eog.group_code != '','',IF(gs.style_id IS NULL,g.barcode,gs.barcode)) barcode,eoi.nick_name, 
		tel,FROM_UNIXTIME( pay_time, '%Y-%m-%d %h:%i:%s' )  pay_time
		from ecshop.ecs_out_ship_order_task t 
		inner join ecshop.ecs_out_ship_order eoso on t.task_id = eoso.task_id
		inner join ecshop.ecs_order_info eoi use index(PRIMARY) on eoi.order_id = eoso.order_id and t.facility_id = eoi.facility_id and t.shipping_id = eoi.shipping_id
		INNER JOIN ecshop.ecs_order_goods eog ON eoi.order_id = eog.order_id
		LEFT JOIN ecshop.ecs_goods g ON  g.goods_id = eog.goods_id
		LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.style_id=eog.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id
		LEFT JOIN ecshop.sync_taobao_order_goods stog on stog.tid = SUBSTRING_INDEX(taobao_order_sn,'-',1) and IF(stog.outer_sku_id != '',stog.outer_sku_id,stog.outer_iid) = t.outer_id
		left join ecshop.ecs_region r1 on r1.region_id = eoi.province
		left join ecshop.ecs_region r2 on r2.region_id = eoi.city
		left join ecshop.ecs_region r3 on r3.region_id = eoi.district
		where t.task_id = {$task_id}
		and eoi.order_status = '1'
		and eoi.pay_status = '2'
		and eoi.party_id = {$_SESSION['party_id']} 
		group by eoso.order_id";
//	Qlog::log($sql);
	$orders = $db->getAll($sql);
	$count = count($db->getAll("select * from ecshop.ecs_out_ship_order where task_id = '{$task_id}'"));
	if(count($orders)."" != $count){
		$smarty->assign("message", "导出的数量为：".count($orders).",应该导出的数量为：".$count."。请联系ERP！");
		$smarty->assign("list", showTask());
		$smarty->assign("facility_list", $facility_list);
		$smarty->display('order/out_ship_order_task.htm');
		exit();
	}
	set_include_path(get_include_path() . 'admin/includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    
	$title = array(0 => array(
        '订单号','寄件人姓名','寄件人电话','寄件省','寄件市','寄件区/县','寄件人地址','寄件邮编', '收件人', '手机号码', '省', '市', '区', '地址','大头笔','邮编','重量',
		'淘宝商品名称','数量','订单时间','ERP商品名称','商家编码','条码','旺旺号', '座机号码', '支付时间'
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
	$outer_id = $_REQUEST['outer_id'];	
	$facility_id = $_REQUEST['facility_id'];	
	$condition = "";
	if ($start) {
		$condition .= " and t.start_time >= '{$start}'";	
	}
	if ($end) {
		$condition .= " and t.start_time < '{$end}'";	
	}
	if ($outer_id) {
		$condition .= " and t.outer_id = '{$outer_id}'";
	}
	if($facility_id){
		$condition .=" and t.facility_id = '{$facility_id}' ";
	}
	$tasks = showTask($condition);
	$task_ids=array();
	foreach ($tasks as $key => $oneline) {
        $task_ids[]=$oneline['task_id'];
    }
	
	$sql = "select eoi.taobao_order_sn,'' as from_name,'' as from_tel,'' as from_province,'' as from_city,'' as from_district,'' as from_address,'' as from_zipcode,consignee,mobile,r1.region_name province,r2.region_name city,r3.region_name district,eoi.address,'' as datoubi,
		zipcode,IF(eog.group_code != '','',g.goods_weight) gWeight,stog.title,IF(eog.group_code != '',eog.group_number,eog.goods_number) gNum,
		order_time,IF(eog.group_code != '',eog.group_name,IF(gs.style_id IS NOT NULL,CONCAT_WS(',',g.goods_name,s.color),g.goods_name)) gName,t.outer_id, IF(eog.group_code != '','',IF(gs.style_id IS NULL,g.barcode,gs.barcode)) barcode,eoi.nick_name, 
		tel,FROM_UNIXTIME( pay_time, '%Y-%m-%d %h:%i:%s' )  pay_time
		from ecshop.ecs_out_ship_order_task t 
		inner join ecshop.ecs_out_ship_order eoso on t.task_id = eoso.task_id
		inner join ecshop.ecs_order_info eoi use index(PRIMARY) on eoi.order_id = eoso.order_id and t.facility_id = eoi.facility_id and t.shipping_id = eoi.shipping_id
		INNER JOIN ecshop.ecs_order_goods eog ON eoi.order_id = eog.order_id
		LEFT JOIN ecshop.ecs_goods g ON  g.goods_id = eog.goods_id
		LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.style_id=eog.style_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_style s ON gs.style_id = s.style_id
		LEFT JOIN ecshop.sync_taobao_order_goods stog on stog.tid = SUBSTRING_INDEX(taobao_order_sn,'-',1) and IF(stog.outer_sku_id != '',stog.outer_sku_id,stog.outer_iid) = t.outer_id
		left join ecshop.ecs_region r1 on r1.region_id = eoi.province
		left join ecshop.ecs_region r2 on r2.region_id = eoi.city
		left join ecshop.ecs_region r3 on r3.region_id = eoi.district
		where t.task_id ".db_create_in($task_ids)."
		and eoi.order_status = '1'
		and eoi.pay_status = '2'
		and eoi.party_id = {$_SESSION['party_id']} 
		group by eoso.order_id";
//	Qlog::log($sql);
	
	$orders = $db->getAll($sql);
	$count = count($db->getAll("select * from ecshop.ecs_out_ship_order where task_id ".db_create_in($task_ids)));
	if(count($orders)."" != $count){
		$smarty->assign("message", "导出的数量为：".count($orders).",应该导出的数量为：".$count."。请联系ERP！");
		$smarty->assign("list", $tasks);
		$smarty->assign("start", $start);
		$smarty->assign("end", $end);
		$smarty->assign("outer_id", $outer_id);
		$smarty->assign("facility_id", $facility_id);
		$smarty->assign("facility_list", $facility_list);
		$smarty->display('order/out_ship_order_task.htm');
		exit();
	}
	set_include_path(get_include_path() . 'admin/includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    
	$title = array(0 => array(
        '订单号','寄件人姓名','寄件人电话','寄件省','寄件市','寄件区/县','寄件人地址','寄件邮编', '收件人', '手机号码', '省', '市', '区', '地址','大头笔','邮编','重量',
		'淘宝商品名称','数量','订单时间','ERP商品名称','商家编码','条码','旺旺号', '座机号码', '支付时间'
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

//省
$province_list_tmp=$slave_db->getAll("select region_id,region_name from ecshop.ecs_region where region_type=1 and parent_id=1");
foreach ($province_list_tmp as $row) {
	$province_list[$row['region_id']]=$row['region_name'];
}

$smarty->assign('end',isset($end) ? $end : date('Y-m-d H:i:s', time()));
$smarty->assign('start',isset($start) ? $start : date('Y-m-d H:i:s', strtotime("-5 day",time())));
$smarty->assign('facility_list',$facility_list);
$smarty->assign('shipping_list', get_shipping_list());
$smarty->assign('province_list', $province_list);
$smarty->display('order/out_ship_order.htm');

function checkTask() {
	global $db;
	$sql = "select task_id from ecshop.ecs_out_ship_order_task where party_id = '{$_SESSION['party_id']}' and status in ('INIT', 'EXCUTE') and facility_id != '149849257' limit 1 ";
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
		where t.party_id = '{$_SESSION['party_id']}' {$condition} 
		group by t.task_id 
	";
//	Qlog::log($sql);
	$tasks = $db->getAll($sql);
	if ($tasks) {
		foreach($tasks as $key => $task) {
			$tasks[$key]['outer_goods_name'] = getGoodsName($task['outer_id']);
		}
	}
	return $tasks;
}

function getGoodsName($outer_id) {
	global $db;
	$goods_name = "";
	if (strstr($outer_id,'TC')) {
		$sql = "select dgg.name from ecshop.distribution_group_goods dgg where dgg.code = '{$outer_id}' limit 1 ";
		$goods_name = $db->getOne($sql);
	} else if (strstr($outer_id,'_')){
		$ary = preg_split('/_/', $outer_id);
		$sql = "select g.goods_name from ecshop.ecs_goods g where g.goods_id = '{$ary[0]}' limit 1 ";
		$goods_name = $db->getOne($sql);
		if ($ary[1]) {
			$sql = "select s.color from ecshop.ecs_style s where s.style_id = '{$ary[1]}' limit 1";
			$goods_name .= " " . $db->getOne($sql);
		}
	} else {
		$sql = "select g.goods_name from ecshop.ecs_goods g where g.goods_id = '{$outer_id}' limit 1 ";
		$goods_name = $db->getOne($sql);
	}
	return $goods_name;
	
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