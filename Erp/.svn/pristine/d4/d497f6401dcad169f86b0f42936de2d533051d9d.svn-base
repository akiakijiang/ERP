<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('distribution_order');

$act =  // 请求动作
    isset($_REQUEST['act']) && trim($_REQUEST['act']) 
    ? trim($_REQUEST['act'])
    : null;
$page =  // 当前页码
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
$message = isset($_REQUEST['message']) && trim($_REQUEST['message']) 
    ? trim($_REQUEST['message'])
    : '';
$distributor_id = isset($_REQUEST['distributor_id']) && trim($_REQUEST['distributor_id']) 
    ? trim($_REQUEST['distributor_id'])
    : '';
    
global $db;

$cond = $cond1 = "";
if ($act == "select_total") {
	//返点总查询
	if (!empty($distributor_id)) {
		$cond .= " and ri.distributor_id = '{$distributor_id}' ";
		$cond1 .= " and b.distributor_id = '{$distributor_id}' ";
	}
	// 按分页取得列表
	$total = $db->getOne("select count(*)
		from romeo.rebate_balance b
		left join ecshop.distributor d on b.distributor_id = d.distributor_id and b.party_id = d.party_id
		where b.party_id = '{$_SESSION['party_id']}' " . $cond1 . "
		group by b.distributor_id, b.party_id 
	");
	$page_size = 20;  // 每页数量
	$total_page = ceil($total/$page_size);  // 总页数
	if ($page > $total_page) $page = $total_page;
	if ($page < 1) $page = 1;
	$offset = ($page - 1) * $page_size;
	$limit = $page_size;
	
	$distributor_list = $dirtirbutor_ids = array();
	//当前剩余总金额
	$sql1 = "
		select sum(b.amount) as total_amount, b.distributor_id, d.name
		from romeo.rebate_balance b
		left join ecshop.distributor d on b.distributor_id = d.distributor_id and b.party_id = d.party_id
		where b.party_id = '{$_SESSION['party_id']}' " . $cond1 . "
		group by b.distributor_id, b.party_id
		order by b.distributor_id
		limit $limit OFFSET $offset
	";
	$distributor_list = $total_list = Helper_Array::toHashmap((array)$db->getAll($sql1), 'distributor_id');
	$distributor_ids = Helper_Array::getCols((array)$total_list, 'distributor_id');
	$distributor_ids_str = implode(",",$distributor_ids);
	if (!empty($distributor_ids_str)) {
		$cond .= " and  ri.distributor_id in ($distributor_ids_str) ";
	}
	$start_time = date("Y-m-01", strtotime("now -1 month"));
	//上月剩余总金额
	$sql4 = "
		select sum(ri.amount) as last_month_total_amount, ri.distributor_id, d.name
		from romeo.rebate_item ri
		left join ecshop.distributor d on ri.distributor_id = d.distributor_id and ri.party_id = d.party_id
		where  ri.status = 'AUDITED' and ri.party_id = '{$_SESSION['party_id']}' 
			and ri.date < '{$start_time}' " . $cond . "
		group by ri.distributor_id, ri.party_id 
		order by ri.distributor_id
		limit $limit OFFSET $offset
	";
	$last_month_total_amount_list = Helper_Array::toHashmap((array)$db->getAll($sql4), 'distributor_id');
	foreach ($last_month_total_amount_list as $item) {
		if (in_array($item['distributor_id'], $distributor_ids)) {
			$distributor_list[$item['distributor_id']]['last_month_total_amount'] = $item['last_month_total_amount'];
		}
	}
	
	//上月核算总金额 手工录入金额及核算总金额
	$sql2 = "
		select sum(ri.amount) as last_month_amount, ri.distributor_id, d.name
		from romeo.rebate_item ri
		left join ecshop.distributor d on ri.distributor_id = d.distributor_id and ri.party_id = d.party_id
		where ri.item_type in('MANUALLY_ADD', 'ORDER_REBATE') and ri.status = 'AUDITED' and ri.party_id = '{$_SESSION['party_id']}' 
			and ri.date = '{$start_time}' " . $cond . " 
		group by ri.distributor_id, ri.party_id 
		order by ri.distributor_id
		limit $limit OFFSET $offset
	";
	$last_month_amount_list = Helper_Array::toHashmap((array)$db->getAll($sql2), 'distributor_id');
	foreach ($last_month_amount_list as $item) {
		if (in_array($item['distributor_id'], $distributor_ids)) {
			$distributor_list[$item['distributor_id']]['last_month_amount'] = $item['last_month_amount'];
		}
	}
	$distributor_ids = array_unique(array_merge((array)$distributor_ids, (array)Helper_Array::getCols((array)$last_month_amount_list, 'distributor_id')));
	//当月使用返点金额
	$sql3 = "
		select sum(ri.amount) as used_amount, ri.distributor_id, d.name
		from romeo.rebate_item ri
		left join ecshop.distributor d on ri.distributor_id = d.distributor_id and ri.party_id = d.party_id
		where ri.item_type in('ORDER_DEDUCT', 'ORDER_CANCEL') and ri.status = 'AUDITED' and ri.party_id = '{$_SESSION['party_id']}' 
			and ri.date = '{$start_time}' " . $cond . "
		group by ri.distributor_id, ri.party_id 
		order by ri.distributor_id
		limit $limit OFFSET $offset
	";
	$used_list = Helper_Array::toHashmap((array)$db->getAll($sql3), 'distributor_id');
	foreach ($used_list as $item) {
		$id = $item['distributor_id'];
		if (in_array($id, $distributor_ids)) {
			$distributor_list[$id]['used_amount'] = $item['used_amount'];
		} 
	}

	$smarty->assign('distributor_list', $distributor_list);
	// 分页
	$pagination = new Pagination(
	    $total, $page_size, $page, 'page', $url = 'rebate.php?act=select_total', null, $extra_params
	);
	$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
} else if ($act == "insert") {
	$sql = "
		select p.party_id, p.name
		from romeo.party p
		left join romeo.party_relation pr on p.party_id = pr.party_id 
		where pr.status = 'OK'
	";
	$party_list = $db->getAll($sql); 
	$start =  date("Y-m-01", strtotime(trim($_REQUEST['start'])));
	$distributor_id = trim($_REQUEST['distributor_id']);
	$amount = trim($_REQUEST['amount']);
	$party_id = trim($_REQUEST['party_id']);
	$note = trim($_REQUEST['note']);
	if (!empty($distributor_id)) {
		$sql = "
			select distributor_id, name from ecshop.distributor where distributor_id = '{$distributor_id}' limit 1;
		";
		$distributor = $db->getRow($sql);
	}

	if (!empty($start)  && !empty($distributor_id) && !empty($amount) && !empty($party_id) && !empty($note)) {
	    $handle = soap_get_client('DistributorRebateService');
//		$wsdl = "http://172.16.1.40:8080/romeo/DistributorRebateService?wsdl";
//		$handle = new SoapClient($wsdl);
		$c = new stdClass();
		$c->partyId = $party_id;
		$c->distributorId = $distributor_id;
		$c->date = $start." 00:00:00";
		$c->amount = $amount;
		$c->itemType = "MANUALLY_ADD";
		$c->applyUser = $_SESSION['admin_name'];
		$c->approveUser = $_SESSION['admin_name'];
		$c->note = $note;
		$c->status = "AUDITED";
		$res = $handle->addRebateBalance($c);
		
		if ($res->return->code == "FAIL") {
			$message = "返点金额录入失败<br/>".$res->return->msg;
		} else {
			$message = "返点金额录入成功，请勿重复操作";
		}	
    }
    $smarty->assign('distributor', $distributor);
    $smarty->assign('party_list', $party_list);
} else if ($act == "select_detail") {

	$item_type_list = array("MANUALLY_ADD"=>"录入返点类型", "ORDER_DEDUCT"=>"订单使用返点类型", "ORDER_REBATE"=>"订单核算返点类型");
	$type_list = array("order_sn"=>"ERP订单号", "taobao_order_sn"=>"淘宝订单号", "purchase_order_sn"=>"采购订单号");
	$startTime = trim($_REQUEST['startTime']);
	$endTime = trim($_REQUEST['endTime']);
	$item_type = trim($_REQUEST['item_type']);
	$type = trim($_REQUEST['type']);
	$key = trim($_REQUEST['key']);
	$detail_type = trim($_REQUEST['detail_type']);
	
	$cond1 = "";
	
	if (!empty($distributor_id)) {
		$cond .= " and i.distributor_id = '{$distributor_id}' ";
	}
	
	if (!empty($_REQUEST['party_id'])) {
		$cond .= " and i.party_id = '{$_REQUEST['party_id']}' ";
	}
	if (!empty($item_type) && ($item_type == "MANUALLY_ADD")) {
		$cond .= " and i.item_type = '{$item_type}' ";
		if (!empty($startTime)) {
			$cond .= " and i.date >= '".date("Y-m-01", strtotime($startTime))."' ";
		}
		if (!empty($endTime)) {
			$cond .= " and i.date < '".date("Y-m-01",  strtotime("{$endTime} +1 month "))."' ";
		}
	} elseif (!empty($item_type)) {
		if (!empty($type) && !empty($key)) {
			$cond .= " and o.$type like '%{$key}%' ";
		}
		if (!empty($startTime)) {
			$cond .= " and o.order_time >= '{$startTime}' ";
		}
		if (!empty($endTime)) {
			$cond .= " and o.order_time <= '{$endTime}' ";
		}
		if ($item_type == "ORDER_DEDUCT") {
			$cond .= " and i.item_type in ('ORDER_DEDUCT', 'ORDER_CANCEL') ";
		} else {
			$cond .= " and i.item_type = '{$item_type}' ";
		}
	}
	
	// 按分页取得列表
	$total = $db->getOne("select count(*)
			from romeo.rebate_item i
			left join ecshop.distributor d on d.distributor_id = i.distributor_id and i.party_id = d.party_id
			left join ecshop.ecs_order_info o on o.order_id = i.order_id and o.party_id = i.party_id and o.distributor_id = i.distributor_id
			where 1 " .$cond); 
	$page_size = 10;  // 每页数量
	$total_page = ceil($total/$page_size);  // 总页数
	if ($page > $total_page) $page = $total_page;
	if ($page < 1) $page = 1;
	$offset = ($page - 1) * $page_size;
	$limit = $page_size;
	if (!empty($detail_type)) {
		if ($detail_type != "export") {
			$cond1 = " limit $limit OFFSET $offset ";
		}
		if ($item_type == "ORDER_DEDUCT") {
			$sql = "
				select  o.order_status, o.shipping_status, o.pay_status,o.goods_amount, o.order_amount, o.taobao_order_sn, 
					o.distribution_purchase_order_sn, o.order_sn, o.order_time, d.name, i.approve_time, i.date, i.item_type, i.note,
					i.amount, i.approve_user
				from romeo.rebate_item i
				left join ecshop.distributor d on d.distributor_id = i.distributor_id and i.party_id = d.party_id 
				left join ecshop.ecs_order_info o on i.order_id = o.order_id and i.party_id= o.party_id and i.distributor_id = o.distributor_id
				where 1 " .$cond. "
				order by i.rebate_item_id desc
				" . $cond1;
			$sql1 = "
				select sum(i.amount) as amount, sum(o.order_amount) as order_amount, sum(o.goods_amount) as goods_amount
				from romeo.rebate_item i
				left join ecshop.distributor d on d.distributor_id = i.distributor_id and i.party_id = d.party_id 
				left join ecshop.ecs_order_info o on i.order_id = o.order_id and i.party_id= o.party_id and i.distributor_id = o.distributor_id
				where 1 " .$cond. "
			";
		} else if ($item_type == "MANUALLY_ADD") {
			$sql = "
				select d.name, i.approve_time, i.date, i.item_type, i.note,  i.amount, i.approve_user
				from romeo.rebate_item i
				left join ecshop.distributor d on d.distributor_id = i.distributor_id and i.party_id = d.party_id 
				where 1 and i.status = 'AUDITED' " .$cond. "
				order by i.rebate_item_id desc
				" . $cond1;
			$sql1 = "
				select sum(i.amount) as amount
				from romeo.rebate_item i
				left join ecshop.distributor d on d.distributor_id = i.distributor_id and i.party_id = d.party_id 
				where 1 and i.status = 'AUDITED' " .$cond. "
			";
		} else if ($item_type == "ORDER_REBATE") {

			$sql = "
				select  o.order_status, o.shipping_status, o.pay_status,o.goods_amount, o.order_amount, o.taobao_order_sn, 
					o.distribution_purchase_order_sn, o.order_sn, o.order_time, d.name, i.approve_time, i.date, i.item_type, i.note,
					i.amount, i.approve_user, (select attr_value from ecshop.order_attribute oa 
							where oa.order_id = i.order_id and oa.attr_name = 'BUYER_PAYMENT' limit 1) as buyer_payment,
					CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), 
				ifnull(gs.goods_color, ''))) as goods_number, id.product_number, id.amount as d_amount, i.note
				from romeo.rebate_item i
				left join ecshop.ecs_order_info o on i.order_id = o.order_id and i.party_id= o.party_id and i.distributor_id = o.distributor_id
				left join romeo.rebate_item_detail id on i.rebate_item_id = id.rebate_item_id 
				left join ecshop.distributor d on d.distributor_id = i.distributor_id and i.party_id = d.party_id 
				left join romeo.product_mapping pm on pm.product_id = id.product_id
				left join ecshop.ecs_goods g on g.goods_id = pm.ecs_goods_id
				left join ecshop.ecs_goods_style gs on gs.goods_id = pm.ecs_goods_id and gs.style_id = pm.ecs_style_id and gs.is_delete=0
				left join ecshop.ecs_style s on gs.style_id = s.style_id
				where 1 and IF(id.rebate_item_detail_id is not null , 
					exists( select 1 from romeo.rebate_item_detail id1 
					 where id1.rebate_item_id = i.rebate_item_id and (id.gifts_product_id is null or id.gifts_product_id = '' )),  1)
				" .$cond. "
				order by i.rebate_item_id desc
				" . $cond1;
			$sql1= "select sum(i.amount) as total_amount
				from romeo.rebate_item i
				left join ecshop.ecs_order_info o on i.order_id = o.order_id and i.party_id= o.party_id and i.distributor_id = o.distributor_id
				left join ecshop.distributor d on d.distributor_id = i.distributor_id and i.party_id = d.party_id 
				where 1 " .$cond. "
				order by i.rebate_item_id desc
				" . $cond1;
		} else {
			$sql = $sql1 = "";
		}
		$detail_list = $db->getAll($sql);
		$detail_total = $db->getRow($sql1);
	}
	$sql = "
		select distributor_id, name from ecshop.distributor where party_id = '{$_REQUEST['party_id']}' 
			and distributor_id = '{$distributor_id}' limit 1;
	";
	$distributor = $db->getRow($sql);
	$url = 'rebate.php?act=select_detail&startTime='.$startTime."&endTime=".$endTime."&item_type=".$item_type."&type=".$type
		."&key=".$key."&detail_type=".$detail_type."&distributor_id=".$distributor['distributor_id'];
	// 分页
	$pagination = new Pagination(
	    $total, $page_size, $page, 'page', $url, null, null
	);
	$smarty->assign('distributor', $distributor);
	$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
	$smarty->assign("item_type_list", $item_type_list);
	$smarty->assign("type_list", $type_list);
	$smarty->assign("detail_total", $detail_total);
	$smarty->assign("detail_list", $detail_list);
	if ($detail_type == "export") {
		header("Content-type:application/vnd.ms-excel");
		header("Content-Disposition:filename=" . iconv("UTF-8","GB18030",$name."返点金额明细") . ".csv");	
		$out = $smarty->fetch('distributor/rebate_csv.htm');
		echo iconv("UTF-8","GB18030", $out);
		exit();	
		die();
	}
} 

$smarty->assign('act', $act);
$smarty->assign('message', $message);

$smarty->display("distributor/rebate.htm");
?>
