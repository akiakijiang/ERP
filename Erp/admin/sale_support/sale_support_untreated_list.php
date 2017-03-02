<?php
define('IN_ECS', true);

require('../includes/init.php');
require('../function.php');
require_once ('../includes/lib_main.php');

require_once (ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

admin_priv( 'sale_support_untreated_check' );

$request = //请求
     isset($_REQUEST['request']) &&
     in_array($_REQUEST['request'], array('ajax'))
     ? $_REQUEST['request']
     : null ;
$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('search_facility','search_party','search')) 
    ? $_REQUEST['act'] 
    : null ;
$info =    // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;

    if($request == 'ajax'){
	$json = new JSON;
	
	
	switch($act){
		//可用仓库搜索
		case 'search_facility':
			
			$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
			$facility_list = get_all_facility($_POST['q'],$limit);

			print $json -> encode($facility_list);
	    break;
	    
	    //搜索所有的组织
		case 'search_party':
			$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
			$party_list = party_get($_POST['q'],$limit);
			
			print $json -> encode($party_list);
		break;
	}
	
	exit;
}    


if($act == 'search'){
	
	//取得搜索条件
	$conf = get_condition();
	//先搜索超2小时未处理
	$last_untreated_orders = search_untreated_orders($conf,2,'desc');
	
	//取得处理不及时汇总信息
	$untreated_orders_summary = array();
	foreach($last_untreated_orders as $untreated_orders){
		if(!array_key_exists($untreated_orders['party_name'],$untreated_orders_summary)){
			$untreated_orders_summary[$untreated_orders['party_name']]['party_name'] = $untreated_orders['party_name'];
			//订单数量初始化成1
			$untreated_orders_summary[$untreated_orders['party_name']]['orders_num'] = 1;
		    //取得各个组织的咨询量
		    $untreated_orders_summary[$untreated_orders['party_name']]['message_num'] = search_orders_num($untreated_orders['party_id'],$conf);
		    //初始化其开始时间和结束时间
		    $untreated_orders_summary[$untreated_orders['party_name']]['min_time'] = $untreated_orders['min_send_time'];
		    $untreated_orders_summary[$untreated_orders['party_name']]['max_time'] = $untreated_orders['max_send_time'];
		}else{
			$untreated_orders_summary[$untreated_orders['party_name']]['orders_num'] += 1;
			//将较小的时间写入
			$untreated_orders_summary[$untreated_orders['party_name']]['min_time'] = get_time($untreated_orders_summary[$untreated_orders['party_name']]['min_time'],$untreated_orders['min_send_time'],'min');
			//将较大的时间写入
			$untreated_orders_summary[$untreated_orders['party_name']]['max_time'] = get_time($untreated_orders_summary[$untreated_orders['party_name']]['max_time'],$untreated_orders['max_send_time'],'max');
		}
	}
	
	//取得24小时未处理订单
	$serious_untreated_orders = search_untreated_orders($conf,24,'asc');

	$untreated_serious_orders_summary = array();
	foreach($serious_untreated_orders as $untreated_orders){
		if(!array_key_exists($untreated_orders['party_name'],$untreated_serious_orders_summary)){
			$untreated_serious_orders_summary[$untreated_orders['party_name']]['party_name'] = $untreated_orders['party_name'];
			//订单数量初始化成1
			$untreated_serious_orders_summary[$untreated_orders['party_name']]['orders_num'] = 1;
		    //取得各个组织的咨询量
		    $untreated_serious_orders_summary[$untreated_orders['party_name']]['message_num'] = search_orders_num($untreated_orders['party_id'],$conf);
		    //初始化其开始时间和结束时间
		    $untreated_serious_orders_summary[$untreated_orders['party_name']]['min_time'] = $untreated_orders['min_send_time'];
		    $untreated_serious_orders_summary[$untreated_orders['party_name']]['max_time'] = $untreated_orders['max_send_time'];
		}else{
			$untreated_serious_orders_summary[$untreated_orders['party_name']]['orders_num'] += 1;
			//将较小的时间写入
			$untreated_serious_orders_summary[$untreated_orders['party_name']]['min_time'] = get_time($untreated_serious_orders_summary[$untreated_orders['party_name']]['min_time'],$untreated_orders['min_send_time'],'min');
			//将较大的时间写入
			$untreated_serious_orders_summary[$untreated_orders['party_name']]['max_time'] = get_time($untreated_serious_orders_summary[$untreated_orders['party_name']]['max_time'],$untreated_orders['max_send_time'],'max');
		}
	}
	
	$smarty->assign('last_untreated_orders',$last_untreated_orders);
	$smarty->assign('untreated_orders_summary',$untreated_orders_summary);
	$smarty->assign('searious_untreated_orders',$serious_untreated_orders);
	$smarty->assign('untreated_serious_orders_summary',$untreated_serious_orders_summary);

}

$smarty->display('sale_support/sale_support_untreated_list.htm');

//得到所有的仓库
function get_all_facility($keyword,$limit){
	global $db;
	$keyword = trim($keyword);
	$sql = "select facility_id,facility_name from romeo.facility where facility_name like '%{$keyword}%' limit {$limit}";
	$result = $db -> getAll($sql);
	return $result;
}

//取得所有叶子类型的组织
function party_get($keyword,$limit){
	global $db;
	$keyword = trim($keyword);
	$sql = "select name,party_id from romeo.party where status = 'ok' and name like '%{$keyword}%' and is_leaf = 'Y' limit {$limit} ";
	$result = $db->getAll($sql);
	return $result;
}

//取得查询条件
function get_condition(){
	
	$conf = "";
	$party_id = $_REQUEST['party_id'];
	$facility_id = $_REQUEST['facility_id'];
	$start = $_REQUEST['start'];
	$end = $_REQUEST['end'];
	$order_sn = $_REQUEST['order_sn'];
	
	if(isset($party_id) && !empty($party_id)){
		$conf .= " and oi.party_id = '{$party_id}' ";
	}
	
	if(isset($facility_id) && !empty($facility_id)){
		$conf .= " and oi.facility_id = '{$facility_id}' ";
	}
	
	if(isset($start) && !empty($start)){
		$conf .= " and oi.order_time >= '{$start}' ";
	}
	
	if(isset($end) && !empty($end)){
		$conf .= " and oi.order_time <= '{$end}' ";
	}
	
	if(isset($order_sn) && !empty($order_sn)){
		$conf .= " and oi.order_sn = '{$order_sn}' ";
	}
	
	return $conf;
}

/*
 * 处理不及时销售订单，看其是否超过规定的时间
 * 根据$sort判定 是根据第一次售后发起的时间进行判定还是最后一次售后发起的时间进行判定
 */
function search_untreated_orders($conf = null,$hours,$sort){
	global $db;
	$sql = "
	    select sm.order_id,sm.created_stamp as send_time,sm.send_by,sm.message,p.party_id,
               oi.order_sn,p.name as party_name,pm.pay_name,f.facility_name,sm.status,
               (select min(mi.created_stamp) from ecshop.sale_support_message mi where mi.order_id = sm.order_id and status = 'OK') as min_send_time,
       		   (select max(mx.created_stamp) from ecshop.sale_support_message mx where mx.order_id = sm.order_id and status = 'OK') as max_send_time
		from ecshop.sale_support_message sm
		inner join ecshop.ecs_order_info oi on oi.order_id = sm.order_id
		inner join romeo.party p on p.party_id = convert(oi.party_id using utf8)
		inner join ecshop.ecs_payment pm on pm.pay_id = oi.pay_id
		inner join romeo.facility f on f.facility_id = oi.facility_id
		where sm.status = 'OK'
		      $conf
		      and sm.sale_support_message_id = ( select m.sale_support_message_id from ecshop.sale_support_message m where m.order_id = sm.order_id and m.status = 'OK' order by m.sale_support_message_id {$sort} limit 1)
		group by sm.order_id having send_time < (now() - INTERVAL {$hours} HOUR) 
		order by oi.party_id,oi.order_time
	";
	$result = $db -> getAll($sql);
	return $result;
}

//根据每个组织得到其总咨询数
function search_orders_num($party_id,$conf){
	global $db;
	
	//查看$_REQUEST中是否有party_id信息，没有则取用传入的$party_id
	if(empty($_REQUEST['party_id'])){
		$conf .= " and oi.party_id = {$party_id} ";
	}
	
	$sql = "
	    select count(m.sale_support_message_id)
		from ecshop.ecs_order_info oi
		inner join ecshop.sale_support_message m on m.order_id = oi.order_id
		where m.support_type <> 9 
		      $conf
	";
    $result = $db -> getOne($sql);
    return $result;
}

//比较两个时间得出其符合条件的值
//$sort 只有两种 max 和 min
function get_time($time1,$time2,$sort){
	if($sort == 'max'){
		if(strtotime($time1) >= strtotime($time2)){
			return $time1;
		}else{
			return $time2;
		}
	}else{
	    if(strtotime($time1) <= strtotime($time2)){
			return $time1;
		}else{
			return $time2;
		}
	}
}
?>