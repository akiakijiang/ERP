<?php

define('IN_ECS', true);
require('includes/init.php');
require('function.php');
include_once 'search_order.php';
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('customer_service_manage_order','kf_order_search');


$act = $_GET['act'];
$csv = $_GET['csv'];
$result = array();
$result['startCalendar'] = date("Y-m-d H:i:s",strtotime("-30 days",time()));
if(!$csv&&$act&&function_exists('fun_'.$act)){
	 $result = call_user_func('fun_'.$act, $_GET);
	 $smarty->assign('type','search');
}else if($csv){

	 set_include_path(ROOT_PATH.'admin/includes/Classes/');
    require_once ('PHPExcel.php');
    require_once ('PHPExcel/IOFactory.php');
       	       
    $filename = "历史订单下载";
    $excel = new PHPExcel();
    $excel->getProperties()->setTitle($filename);
    $sheet = $excel->getActiveSheet();
    $sheet->setCellValue('A1', "订单号");
    $sheet->setCellValue('B1', "淘宝订单号");
    $sheet->setCellValue('C1', "订单时间");
    $sheet->setCellValue('D1', "确认时间");
    $sheet->setCellValue('E1', "预定时间");
    $sheet->setCellValue('F1', "发货时间");
    $sheet->setCellValue('G1', "收货人");
    $sheet->setCellValue('H1', "电话");
    $sheet->setCellValue('I1', "仓库");
    $sheet->setCellValue('J1', "发货单");
    $sheet->setCellValue('K1', "店铺");
    $i=2;
    $result = call_user_func('fun_search_csv', $_GET);
    $order_list = $result['order_list'];

    foreach($order_list as $key=>$order){
	    $sheet->setCellValue("A{$i}", $order['order_sn']);
        $sheet->setCellValue("B{$i}", '’'.$order['taobao_order_sn']);
        $sheet->setCellValue("C{$i}", $order['order_time']);
        $sheet->setCellValue("D{$i}", $order['goods_name']);
        $sheet->setCellValue("E{$i}", $order['confirm_time']);
        $sheet->setCellValue("F{$i}", $order['reserved_time']);
        $sheet->setCellValue("G{$i}", $order['consignee']);
        $sheet->setCellValue("H{$i}", $order['tel']);
        $sheet->setCellValue("I{$i}", $order['facility_name']);
        $sheet->setCellValue("J{$i}", $order['tracking_number']);
        $sheet->setCellValue("K{$i}", $order['name']);
        $i++;
    }

    if (!headers_sent()) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
        header('Cache-Control: max-age=0');
        $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $output->save('php://output');
        exit;
    }   
    
    $smarty->assign('type','search');
	 
}

if( $_REQUEST['do'] == "search_history_shipping"){
	 $json = new JSON;
     $limit = 20 ;
     print $json->encode(get_shipping_types($_POST['q'], $limit)); 
 	 exit;
} else if($_REQUEST['do'] == "search_history_pay_type"){
	 $json = new JSON;
   	 $limit = 20 ;
   	 print $json->encode(get_pay_type($_POST['q'], $limit)); 
   	 exit;
}
$order_list_result_check = "";
if(!empty($result[order_list])){
	$order_list_result_check = "result[order_list] is not empty";
}

$smarty->assign('order_list_result_check',$order_list_result_check);
$smarty->assign('result',$result);
$smarty->assign('facilitys',array_intersect_assoc(get_available_facility(),get_user_facility()));
$smarty->assign('shippings', getShippingTypes());
$smarty->assign('payments',getPayments());
$smarty->assign('adminvars', $_CFG['adminvars']);
$smarty->assign('Pager',$result['Pager']);
$smarty->display('search_history_order.htm');




function fun_search($args){
	global $slave_db;
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 30;
	$offset = $limit * ($page-1);
	
	// $search_type = trim($_REQUEST['search_type']);
 //    $search_text = trim($_REQUEST['search_text']);

	$user_name = trim($_REQUEST['user_name']);
    $tel_mobile = trim($_REQUEST['tel_mobile']);
    $consignee = trim($_REQUEST['consignee']);
    $tracking_number = trim($_REQUEST['tracking_number']);

    $erp_search_text = trim ( $_REQUEST ['erp_order_sn'] );
    $taobao_search_text = trim ( $_REQUEST ['taobao_order_sn'] );
    $start_time = trim ( $_REQUEST ['startCalendar'] );
    if($start_time == null || $start_time == ''){
        exit('<font color="red">起始日期不能为空，请填写起始日期！</font><a href=\'javascript:history.go(-1)\'>返回</a>' );
    }
    if(!empty($erp_search_text)){
        $sql_by_hand_table  ='FROM ecshop.ecs_order_info info use index(order_sn) ';
    }elseif(!empty($taobao_search_text)){
        $sql_by_hand_table  ='FROM ecshop.ecs_order_info info use index(taobao_order_sn) ';
    }else if(!empty($user_name)){
        $sql_by_hand_table  ='FROM ecshop.ecs_order_info info use index(nick_name) ';
    }elseif(!empty($tel_mobile) && empty($erp_search_text) && empty($taobao_search_text) && empty($user_name) && empty($consignee) &&empty($tracking_number)){
        $sql_by_hand_table = 'FROM ecshop.ecs_order_info info ';
        $sql_tel_table = 'FROM ecshop.ecs_order_info info force index(order_time,order_info_multi_index) ';
    }elseif(!empty($tracking_number) && empty($erp_search_text) && empty($taobao_search_text)){
        $sql_by_hand_table = 'FROM ecshop.ecs_order_info info ';
    }else{
        $sql_by_hand_table  ='FROM ecshop.ecs_order_info info force index(order_time,order_info_multi_index) ';
    }
    
  
    $sql_sinri_b =" LEFT JOIN romeo.order_shipment s on convert(info.ORDER_ID using utf8) = s.ORDER_ID 
    		  LEFT JOIN romeo.shipment sp on s.shipment_id = sp.shipment_id";

	

//	$condition = getCondition($args);
	$condition = getHistoryorderCondition($args);
	//面单号、电话号码  先查找出order_id 再通过order_id查找相关信息
	if(!empty($tracking_number) && empty($erp_search_text) && empty($taobao_search_text)){
	    $sql_trackingNum = "SELECT order_id from romeo.shipment s use index(tracking_number)" .
    				" left join romeo.order_shipment os on s.shipment_id = os.shipment_id " .
    				" where s.tracking_number = '{$tracking_number}'";
	    // $res_tracking = $GLOBALS['db']->getAll($sql_trackingNum);
    				$res_tracking = $slave_db->getAll($sql_trackingNum);
	    $sql_orderId = " AND info.order_id = '{$res_tracking[0]['order_id']}' ";
	    }elseif(!empty($tel_mobile) && empty($erp_search_text) && empty($taobao_search_text) && empty($user_name) && empty($consignee) && empty($tracking_number)){
	       $sql_telMobile = "SELECT  info.order_id
	                       {$sql_tel_table}
	                       WHERE info.party_id = {$_SESSION['party_id']} {$condition} AND (info.tel LIKE '{$tel_mobile}%' OR info.mobile LIKE '{$tel_mobile}%' )";
	       // $res_tel = $GLOBALS['db']->getAll($sql_telMobile);
	                       $res_tel = $slave_db->getAll($sql_telMobile);
	       $in_tel = '(';
	       for($i = 0;$i < count($res_tel);$i++){
	           if($i < count($res_tel) - 1){
	               $in_tel .= "'".$res_tel[$i]['order_id']."'".",";
	           }else{
	               $in_tel .= "'".$res_tel[$i]['order_id']."'";
	           }
	       }
	       $in_tel .= ')';
	     if(!empty($res_tel)){
	           $sql_orderId = "AND info.order_id in $in_tel";
	       }else{
	           $sql_orderId = 'AND 1<>1';
	       }                                 
	    }else{
	    $sql_orderId = '';
	        }
	
	$sqlc = "SELECT COUNT(1)  
			{$sql_by_hand_table} 
			{$sql_sinri_b}
        	LEFT JOIN ecshop.distributor d ON d.distributor_id = info.distributor_id
            LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
			WHERE info.party_id = {$_SESSION['party_id']} {$condition} $sql_orderId
	".' -- SearchHistoryOrder '.__LINE__.PHP_EOL;
    

	$sql = "SELECT info.order_sn, info.taobao_order_sn, info.order_time, info.confirm_time,
			info.reserved_time, info.shipping_time, info.consignee, info.tel, info.mobile, 
			info.pay_name, info.order_status, info.pay_status, info.shipping_status, info.order_id,
			 f.facility_name,s.shipment_id, sp.tracking_number
      		{$sql_by_hand_table}
      		INNER JOIN romeo.facility f on info.FACILITY_ID = f.FACILITY_ID
        	{$sql_sinri_b}
        	WHERE info.party_id = {$_SESSION['party_id']} {$condition} {$sql_orderId}
        	ORDER BY info.order_id DESC LIMIT {$limit} OFFSET {$offset} 
    ".' -- SearchHistoryOrder '.__LINE__.PHP_EOL;
        //Qlog::log($sql_trackingNum);
        //Qlog::log("查询语句1:".$sql);

	$order_list = $slave_db->getAll($sql);
    $count = count($order_list);
	if($count < 30) {
	    $total = $count;
	}else{
	    $total = $slave_db ->getOne($sqlc);
	}
	
	foreach($order_list as $key=>$order){
		if($order['confirm_time']){
			$order_list[$key]['confirm_time'] = date("Y-m-d H:i:s",$order['confirm_time']);
		}
		if($order['reserved_time']){
			$order_list[$key]['reserved_time'] = date("Y-m-d H:i:s",$order['reserved_time']);
		}
		if($order['shipping_time']){
			$order_list[$key]['shipping_time'] = date("Y-m-d H:i:s",$order['shipping_time']);
		}
		
	}
	$args['Pager'] = Pager($total,$limit,$page);
	$args['order_list'] = $order_list;
	return $args;
}

function fun_search_csv($args){
	global $slave_db;
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 30;
	$offset = $limit * ($page-1);
	
	// $search_type = trim($_REQUEST['search_type']);
 //    $search_text = trim($_REQUEST['search_text']);

	$user_name = trim($_REQUEST['user_name']);
    $tel_mobile = trim($_REQUEST['tel_mobile']);
    $consignee = trim($_REQUEST['consignee']);
    $tracking_number = trim($_REQUEST['tracking_number']);

    $erp_search_text = trim ( $_REQUEST ['erp_order_sn'] );
    $taobao_search_text = trim ( $_REQUEST ['taobao_order_sn'] );
    $start_time = trim ( $_REQUEST ['startCalendar'] );
    if($start_time == null || $start_time == ''){
        exit('<font color="red">起始日期不能为空，请填写起始日期！</font><a href=\'javascript:history.go(-1)\'>返回</a>' );
    }
    if(!empty($erp_search_text)){
        $sql_by_hand_table  ='FROM ecshop.ecs_order_info info use index(order_sn) ';
    }elseif(!empty($taobao_search_text)){
        $sql_by_hand_table  ='FROM ecshop.ecs_order_info info use index(taobao_order_sn) ';
    }else if(!empty($user_name)){
        $sql_by_hand_table  ='FROM ecshop.ecs_order_info info use index(nick_name) ';
    }elseif(!empty($tel_mobile) && empty($erp_search_text) && empty($taobao_search_text) && empty($user_name) && empty($consignee) &&empty($tracking_number)){
        $sql_by_hand_table = 'FROM ecshop.ecs_order_info info ';
        $sql_tel_table = 'FROM ecshop.ecs_order_info info force index(order_time,order_info_multi_index) ';
    }elseif(!empty($tracking_number) && empty($erp_search_text) && empty($taobao_search_text)){
        $sql_by_hand_table = 'FROM ecshop.ecs_order_info info ';
    }else{
        $sql_by_hand_table  ='FROM ecshop.ecs_order_info info force index(order_time,order_info_multi_index) ';
    }
    
  
    $sql_sinri_b =" LEFT JOIN romeo.order_shipment s on convert(info.ORDER_ID using utf8) = s.ORDER_ID 
    		  LEFT JOIN romeo.shipment sp on s.shipment_id = sp.shipment_id";

	

//	$condition = getCondition($args);
	$condition = getHistoryorderCondition($args);
	//面单号、电话号码  先查找出order_id 再通过order_id查找相关信息
	if(!empty($tracking_number) && empty($erp_search_text) && empty($taobao_search_text)){
	    $sql_trackingNum = "SELECT order_id from romeo.shipment s use index(tracking_number)" .
    				" left join romeo.order_shipment os on s.shipment_id = os.shipment_id " .
    				" where s.tracking_number = '{$tracking_number}'";
	    // $res_tracking = $GLOBALS['db']->getAll($sql_trackingNum);
    				$res_tracking = $slave_db->getAll($sql_trackingNum);
	    $sql_orderId = " AND info.order_id = '{$res_tracking[0]['order_id']}' ";
	    }elseif(!empty($tel_mobile) && empty($erp_search_text) && empty($taobao_search_text) && empty($user_name) && empty($consignee) && empty($tracking_number)){
	       $sql_telMobile = "SELECT  info.order_id
	                       {$sql_tel_table}
	                       WHERE info.party_id = {$_SESSION['party_id']} {$condition} AND (info.tel LIKE '{$tel_mobile}%' OR info.mobile LIKE '{$tel_mobile}%' )";
	        $res_tel = $slave_db->getAll($sql_telMobile);
	       $in_tel = '(';
	       for($i = 0;$i < count($res_tel);$i++){
	           if($i < count($res_tel) - 1){
	               $in_tel .= "'".$res_tel[$i]['order_id']."'".",";
	           }else{
	               $in_tel .= "'".$res_tel[$i]['order_id']."'";
	           }
	       }
	       $in_tel .= ')';
	     if(!empty($res_tel)){
	           $sql_orderId = "AND info.order_id in $in_tel";
	       }else{
	           $sql_orderId = 'AND 1<>1';
	       }                                 
	    }else{
	    $sql_orderId = '';
	        }
	
	$sqlc = "SELECT COUNT(1)  
			{$sql_by_hand_table} 
			{$sql_sinri_b}
        	LEFT JOIN ecshop.distributor d ON d.distributor_id = info.distributor_id
            LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
			WHERE info.party_id = {$_SESSION['party_id']} {$condition} $sql_orderId
	".' -- SearchHistoryOrder '.__LINE__.PHP_EOL;
    

	$sql = "SELECT dist.name, info.order_sn, info.taobao_order_sn, info.order_time, info.confirm_time,
			info.reserved_time, info.shipping_time, info.consignee, info.tel, info.mobile, 
			info.pay_name, info.order_status, info.pay_status, info.shipping_status, info.order_id,
			 f.facility_name,s.shipment_id, sp.tracking_number
      		{$sql_by_hand_table}
      		INNER JOIN romeo.facility f on info.FACILITY_ID = f.FACILITY_ID
      		inner join ecshop.distributor dist on dist.distributor_id = info.distributor_id
        	{$sql_sinri_b}
        	WHERE info.party_id = {$_SESSION['party_id']} {$condition} {$sql_orderId}
        	ORDER BY info.order_id DESC 
    ".' -- SearchHistoryOrder '.__LINE__.PHP_EOL;
        //Qlog::log($sql_trackingNum);
       Qlog::log("查询语句csv1:".$sql);

	$order_list = $slave_db->getAll($sql);
	  Qlog::log("查询语句csv1:".count($order_list));
	foreach($order_list as $key=>$order){
		if($order['confirm_time']){
			$order_list[$key]['confirm_time'] = date("Y-m-d H:i:s",$order['confirm_time']);
		}
		if($order['reserved_time']){
			$order_list[$key]['reserved_time'] = date("Y-m-d H:i:s",$order['reserved_time']);
		}
		if($order['shipping_time']){
			$order_list[$key]['shipping_time'] = date("Y-m-d H:i:s",$order['shipping_time']);
		}
		
	}
	$args['order_list'] = $order_list;
	return $args;
}

/**
 * 根据查询条件获得对应的SQL语句
 * */
function getCondition($args){
	$condition = "";
	 
    $search_text = trim($args['search_text']);
    $search_type = trim($args['search_type']);
    
    $startCalendar = sqlSafe($args['startCalendar']);
    $endCalendar = sqlSafe($args['endCalendar']);
    $shipping_id = sqlSafe($args['shipping_id']);
    $facility_id = sqlSafe($args['facility_id']);
    $order_status = sqlSafe($args['order_status']);//订单状态
    $pay_status = sqlSafe($args['pay_status']);//收款状态
    $shipping_status = sqlSafe($args['shipping_status']);//发货状态
    $pay_id = sqlSafe($args['pay_id']);//收款方式
	if(!empty($search_text)){
		switch($search_type){
			case 'taobao_order_sn': 
				$condition .= "AND info.taobao_order_sn like '{$search_text}%'";
				break;
			case 'tracking_number':
				$condition .= " AND sp.tracking_number = '{$search_text}'  ";
                break;
			case 'tel_mobile':
                $condition .= " AND (info.tel LIKE '{$search_text}%' OR info.mobile LIKE '{$search_text}%' )";
                break;
            case 'nick_name':
            	$condition .= " AND info.nick_name LIKE '{$search_text}%'";
            	break;
            case 'consignee' :
                $condition .= " AND info.consignee LIKE '{$search_text}%' ";
                break;    
            default :
            	$condition .= preg_match('/^[0-9\.\-tbch]+$/', $search_text) ?
            			" AND info.order_sn LIKE '$search_text%' " :
            			" AND info.consignee LIKE '{$search_text}%'";
            	break;
		}
	    
	}
		$pay_status = sqlSafe($args['pay_status']);//收款状态
	    $shipping_status = sqlSafe($args['shipping_status']);//发货状态
	    $pay_id = sqlSafe($args['pay_id']);//收款方式
    	if (strtotime($startCalendar) > 0) {
	        $startCalendar = date("Y-m-d H:i:s", strtotime($startCalendar));
	        $condition .= " AND info.order_time >= '$startCalendar'";
		}
	    if (strtotime($endCalendar) > 0) {
	        $endCalendar = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($endCalendar)));
	        $condition .= " AND info.order_time <= '$endCalendar'";
	    }
	    if ($shipping_id) {
	        $condition .= " AND info.shipping_id = '$shipping_id'";
	    }
	    if ($facility_id) {
	        $condition .= " AND info.facility_id = '$facility_id'";
	    }
	    
	    if ($order_status != -1 && $order_status !== null) {
	        $condition .= " AND info.order_status = '$order_status'";
	    }
	   	
	   	if($shipping_status != -1 && $shipping_status !== null){
	   		$condition .= $shipping_status == 15 ? 
	   			" AND info.order_status = 1 AND info.shipping_status = 0 " .
	   			" AND EXISTS (SELECT 1 FROM romeo.order_inv_reserved r where r.order_id = info.order_id AND r.status = 'N')"
	   				:
				" AND info.shipping_status = '{$shipping_status}'";
	   	}
	   	if ($pay_status != -1 && $pay_status !== null) {
        	$condition .= " AND pay_status = '$pay_status'";
    	}
   		if ($pay_id != -1 && $pay_id !== null) {
        	$condition .= " AND pay_id = '$pay_id'";
   		}
	return $condition;
	
}
?>
