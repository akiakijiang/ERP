<?php
/**
 * 换货|退货  申请
 */
define('IN_ECS', true);

require('includes/init.php');
require_once('includes/lib_service.php');
require_once('config.vars.php');
require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");
//require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
require_once(ROOT_PATH.'includes/debug/lib_log.php');
include_once 'function.php';
//权限控制
$service_type = $_REQUEST['service_type'] ? $_REQUEST['service_type'] : 1;
admin_priv('kf_sale_serviceV3_type'.$service_type);

//$timer_start = microtime(TRUE);
// 责任方
$responsible_party_list = array(
	'1'=>'乐其',
	'2'=>'厂家',
	'3'=>'顾客',
	'4'=>'快递公司',
    '5'=>'乐麦',
);

// 处理方式
$dispose_method_list = array(
	'1'=>'退货',
	'2'=>'换货',
	//'3'=>'异货换货',
	'5'=>'错发',
	'6'=>'漏发',
	'7'=>'虚拟入库',
	'8'=>'追回',
	'9'=>'拒收',
	'4'=>'其他'
); 

// 查询条件
$condition = getCondition();
$time_condi = "AND s.apply_datetime > '". date("Y-m-d",strtotime("-6 months")). "'";

// 售后服务导出
if (isset($_REQUEST['act']) && $_REQUEST['act'] == 'csv' ) {
	admin_priv('kf_sale_serviceV3_export');
	
	/*$sql_export = " SELECT s.*, o.taobao_order_sn, o.order_sn, o.address,
                o.shipping_time, o.order_id, d.name as distributor_name, f.facility_name,
                pro.region_name as province_name, ci.region_name as city_name, 
                cb.bill_no, cb.carrier_id, s.facility_id, o.facility_id as origin_facility_id
        		FROM ecshop.service s
        		INNER JOIN ecshop.ecs_order_info o ON s.order_id = o.order_id
     		    INNER JOIN ecshop.ecs_carrier_bill cb ON o.carrier_bill_id = cb.bill_id  
     		    INNER JOIN ecshop.distributor d on d.distributor_id = o.distributor_id
     		    INNER JOIN romeo.facility f on f.facility_id = o.facility_id
     		    LEFT JOIN ecshop.ecs_region pro on pro.region_id = o.province
				LEFT JOIN ecshop.ecs_region ci on ci.region_id = o.city
     		    WHERE 1 $time_condi $condition
     		  ";*/
    $sql_export = " SELECT s.*, o.taobao_order_sn, o.order_sn, 
					o.shipping_time, o.order_id, d.name as distributor_name, f.facility_name,
					pro.region_name as province_name, ci.region_name as city_name, 
					-- cb.bill_no, cb.carrier_id, 
                    rs.tracking_number bill_no, rs.carrier_id,
                    s.facility_id, o.facility_id as origin_facility_id, 
					og.goods_name, IF(sog.order_goods_id IS NOT NULL,og.goods_name, '') back_goods_name, sum(IFNULL(sog.amount,0)) back_goods_num 
				FROM ecshop.service s
					INNER JOIN ecshop.ecs_order_info o ON s.order_id = o.order_id
				  	INNER JOIN ecshop.ecs_order_goods og ON og.order_id = o.order_id 
					INNER JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
					LEFT JOIN ecshop.service_order_goods sog on og.order_id = sog.order_id AND s.service_id = sog.service_id and og.rec_id = sog.order_goods_id and sog.is_approved = 1
					-- INNER JOIN ecshop.ecs_carrier_bill cb ON o.carrier_bill_id = cb.bill_id  
                    INNER JOIN romeo.order_shipment ros ON ros.order_id=convert(o.order_id using utf8)
                    INNER JOIN romeo.shipment rs ON ros.shipment_id=rs.shipment_id
					INNER JOIN ecshop.distributor d on d.distributor_id = o.distributor_id
					INNER JOIN romeo.facility f on f.facility_id = o.facility_id
					LEFT JOIN ecshop.ecs_region pro on pro.region_id = o.province
					LEFT JOIN ecshop.ecs_region ci on ci.region_id = o.city 
	    		WHERE 1 
                AND rs.status != 'SHIPMENT_CANCELLED'  
                $time_condi $condition
		        GROUP BY s.service_id, s.order_id, og.rec_id
		        ORDER BY s.service_id DESC 
	          ";
	     
	$services_export = $db->getAllRefby($sql_export, array('service_id'), $service_ids, $service_id_ref);
	
	//专为导出使用
//	foreach ($services_export as $service_key => $service){
//		/*//申请售后的商品
//	    $sql = "
//	    	SELECT
//	    		og.goods_name
//			FROM 
//				{$ecs->table('order_info')} o
//				INNER JOIN {$ecs->table('order_goods')} og ON og.order_id = o.order_id 
//				INNER JOIN {$ecs->table('goods')} g ON g.goods_id = og.goods_id
//			WHERE o.order_id = {$service['order_id']}
//		";
//	    $goods_list = $db->getAll($sql);
//	    
//	    $sql = "
//	        select concat_ws(' ',og.goods_name, sum(sog.amount)) as goods_name
//			from ecshop.service_order_goods sog
//			inner join ecshop.ecs_order_goods og on og.order_id = sog.order_id and og.rec_id = sog.order_goods_id
//			where sog.order_id = {$service['order_id']} and sog.service_id = {$service['service_id']} and sog.is_approved = 1
//			group by sog.order_goods_id
//			";
//	    $back_goods_list = $db->getAll($sql);
//	    
//	    //申请退货的商品
//	    $services_export[$service_key]['goods_list'] = $goods_list;  // 售后商品列表
//	    $services_export[$service_key]['back_goods_list'] = $back_goods_list; */
//	    //售后的状态
//	    $services_export[$service_key]['status_name'] = service_status($service);
//	    //售后日志
//	    $services_export[$service_key]['service_log'] = get_servicelog($service['service_id']);
//	}
	
	header("Content-type:application/vnd.ms-excel;charset=utf-8");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","售后服务") . ".csv");
    ob_start();
    $header_str =  iconv("UTF-8",'GB18030',"订单号,淘宝订单号,售后申请时间,省,市,具体地址,分销商,（原订单）发货时间,（原订单）发货仓库,退货商品名称,退货商品数量,订购商品,问题描述,售后状态,备注内容,责任方\n");
    $file_str = "";
	foreach ($services_export as $service) {
            /*$goods_name = '';
            foreach ($service['goods_list'] as $goods) {
                $goods_name .= $goods['goods_name'] . " ";
            }
            
            $back_goods_name = '';
            foreach ($service['back_goods_list'] as $back_goods){
            	$back_goods_name .= $back_goods['goods_name'] . "   ";
            }*/
            
            //售后的状态
		    $service['status_name'] = service_status($service);
		    //售后日志
		    $service['service_log'] = get_servicelog($service['service_id']);
            $log = '';
            foreach ($service['service_log'] as $slog) {
                if ($slog['is_remark']) {
                    $log = $slog['log_note'];
                }
            }
            $service['shipping_time'] = date ("Y-m-d H:i:s",$service['shipping_time']);
            //将status_name里面的逗号换成空格，以免造成
            $service['status_name'] = str_replace(","," ",$service['status_name']);
            $file_str.= "=\"".$service['order_sn']."\",";
            $file_str.= "=\"".$service['taobao_order_sn']."\",";
            $file_str.= "=\"".$service['apply_datetime']."\",";
            $file_str.= $service['province_name'].",";
            $file_str.= $service['city_name'].",";
            $file_str.= "\"".$service['address']."\",";
            $file_str.= $service['distributor_name'].",";
            $file_str.= "=\"".$service['shipping_time']."\",";
            $file_str.= $service['facility_name'].",";
//            $file_str.= "\"".$back_goods_name."\",";
//            $file_str.= "\"".$goods_name."\",";
			$file_str.= $service['back_goods_name'].",";
            $file_str.= "=\"".$service['back_goods_num']."\",";
            $file_str.= $service['goods_name'].",";
            $file_str.= "\"".$service['apply_reason']."\",";
            $file_str.= $service['status_name'].",";
            $file_str.= "\"".$log."\",";
            $file_str.= $responsible_party_list[$service['responsible_party']]."\n";
	}
	// 特殊字符“®”会导致断篇，因此添加了IGNORE参数  by zjli
	$file_str = iconv("utf-8",'gbk//TRANSLIT',$file_str);
	ob_end_clean();
	echo $header_str;
	echo $file_str;
    exit();
}

global $slave_db;
$size = 3;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
$act = $_REQUEST['act'];
$user_id = $_REQUEST['user_id'];

$limit = "LIMIT $size";
$offset = "OFFSET $start";
$datetime = date("Y-m-d H:i:s", time());

$sql = "SELECT s.*, o.*, 
    (SELECT 
    rs.tracking_number
    FROM
    romeo.order_shipment ros
    LEFT JOIN
    romeo.shipment rs ON ros.shipment_id = rs.shipment_id
    WHERE
    ros.order_id = CONVERT( o.order_id USING UTF8)
    AND (rs.status IS NULL
    OR rs.status != 'SHIPMENT_CANCELLED')
    LIMIT 1) AS bill_no,
    (SELECT 
    rs.carrier_id
    FROM
    romeo.order_shipment ros
    LEFT JOIN
    romeo.shipment rs ON ros.shipment_id = rs.shipment_id
    WHERE
    ros.order_id = CONVERT( o.order_id USING UTF8)
    AND (rs.status IS NULL
    OR rs.status != 'SHIPMENT_CANCELLED')
    LIMIT 1) AS carrier_id,
    s.facility_id, o.facility_id as origin_facility_id
    FROM service s
    INNER JOIN {$ecs->table('order_info')} o ON s.order_id = o.order_id
    WHERE 1 
    $time_condi $condition
    GROUP BY s.service_id,o.order_id
    $limit $offset
";
//QLog::log("查询第".$page."页");
$sql_c = "SELECT COUNT(distinct s.service_id,o.order_id) FROM service s
    INNER JOIN {$ecs->table('order_info')} o ON s.order_id = o.order_id
    -- INNER JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
    LEFT JOIN romeo.order_shipment ros ON ros.order_id=convert(o.order_id using utf8)
    LEFT JOIN romeo.shipment rs ON ros.shipment_id=rs.shipment_id
    WHERE 1 
    AND (rs.status is null or rs.status != 'SHIPMENT_CANCELLED')
    $time_condi $condition
    -- GROUP BY s.service_id,o.order_id
";
$services = $db->getAllRefby($sql, array('service_id'), $service_ids, $service_id_ref);

// 批量查找一些信息
if(!empty($service_ids['service_id'])){
    if(isRMATrackNeeded()){
	   $rma_tracks = getTrackByServiceIdArray($service_ids['service_id']);
	}
	$service_id_str = implode(",",$service_ids['service_id']);
	
	// 批量查找订单确认时间及发货时间
	$sql = "SELECT s.service_id, oa.action_time, oa.action_user, oa.order_status, oa.shipping_status FROM ecshop.service s
		LEFT JOIN ecshop.ecs_order_action oa ON s.order_id = oa.order_id
		WHERE s.service_id IN ({$service_id_str})";
	$order_actions = $db->getAllRefby($sql, array('service_id'), $order_actions_service_ids, $order_action_ref);
	
	// 批量查找换货订单信息
	$sql = "SELECT oi.*, 
            -- cb.bill_no, cb.carrier_id 
            rs.tracking_number bill_no, rs.carrier_id
        FROM ecshop.service s
		INNER JOIN ecshop.ecs_order_info oi ON oi.order_id = s.change_order_id
		-- INNER JOIN ecshop.ecs_carrier_bill cb ON oi.carrier_bill_id = cb.bill_id
        LEFT JOIN romeo.order_shipment ros ON ros.order_id=convert(oi.order_id using utf8)
        LEFT JOIN romeo.shipment rs ON ros.shipment_id=rs.shipment_id
		WHERE s.service_id IN ({$service_id_str})
        AND (rs.status is null or rs.status != 'SHIPMENT_CANCELLED')
        group by oi.order_id
    ";
	$change_orders = $db->getAllRefby($sql, array('order_id'), $change_order_ids, $change_order_ref);
	
	// 批量查找退货订单信息
	$sql = "SELECT oi.* FROM ecshop.service s 
			INNER JOIN ecshop.ecs_order_info oi ON oi.order_id = s.back_order_id
			WHERE s.service_id IN ({$service_id_str})";
	$back_orders = $db->getAllRefby($sql, array('order_id'), $back_order_ids, $back_order_ref);
	
	// 批量查找售后的商品信息
	$sql = "SELECT
    		og.*, og.rec_id AS order_goods_id, o.shipping_id, ogsi.shipping_invoice, s.service_id, c.cat_name
		FROM 
			ecshop.service s
			INNER JOIN ecshop.ecs_order_info o ON s.order_id = o.order_id
			INNER JOIN ecshop.ecs_order_goods og ON og.order_id = o.order_id 
			INNER JOIN ecshop.ecs_goods g ON g.goods_id = og.goods_id
			LEFT JOIN ecshop.ecs_category c ON c.cat_id = g.cat_id
			LEFT JOIN romeo.order_shipping_invoice ogsi ON o.order_id = ogsi.order_id
		WHERE s.service_id IN ({$service_id_str})";
	$back_goods = $db->getAllRefby($sql, array('service_id'), $back_goods_ids, $back_good_ref);
}

$count = $slave_db->getOne($sql_c);

//该类所有
$count_var['service_type'] = getCount('');
////未回复
//$count_var['no_reply'] = getCount(" AND EXISTS ( SELECT 1 FROM service_comment sc WHERE sc.service_id = s.service_id  AND sc.reply = '' ) ");
//已申请、待审核
$count_var['service_status_0'] = getCount(" AND s.service_status = '".SERVICE_STATUS_PENDING."'");

//退换货
if ($service_type == 1 || $service_type == 2){
    if($service_type == 2){
        //退货
        //退款信息已确认,待退款
        $count_var['outer_check_status_23_called_back'] = getCount(" AND s.inner_check_status = '32' AND s.service_call_status = '".SERVICE_CALL_STATUS_CALLED."' AND s.service_pay_status = 0"); //已回访，待配货
    }

    //service_status
    //已审核,待退货
    $count_var['service_status_1'] = getCount(" AND s.service_status = '".SERVICE_STATUS_REVIEWING."' AND s.back_shipping_status = '0'");
    // 除了审核未通过以外所有
    $count_var['service_status_4'] = getCount(" AND s.service_status != '".SERVICE_STATUS_DENIED."'");
    //已回访,审核未通过,申请结束
    $count_var['service_status_3_nocall'] = getCount(" AND s.service_status = '".SERVICE_STATUS_DENIED."' AND s.service_call_status=0");
    //货已收到，待验货
    $count_var['inner_check_status_0'] = getCount("AND s.service_status = '".SERVICE_STATUS_REVIEWING."' AND s.back_shipping_status = '12' AND outer_check_status = '0' ");//货已收到，待验货
    //已入库，待确认退款信息?
    $count_var['confirm_change_order'] = getCount(" AND (s.outer_check_status = '23' OR s.inner_check_status = '32') AND s.service_call_status = '".SERVICE_CALL_STATUS_NEEDCALL."'");
}

//SINRI 20140512 POST SALE
$fast_apply=(empty($_REQUEST['fast_apply'])?'0':trim($_REQUEST['fast_apply']));
$smarty->assign('fast_apply',$fast_apply);
$order_sn=(empty($_REQUEST['order_sn'])?'0':trim($_REQUEST['order_sn']));
$smarty->assign('order_sn',$order_sn);

$smarty->assign('count_var', $count_var);

$pager = Pager($count, $size, $page);

$carrier = getCarriers();

$facility_list=get_available_facility($_SESSION['party_id']);

//$timer_start4 = microtime(TRUE);
foreach ($services as $service_key => $service) {
	$services[$service_key]['check'] = 1;
	
    //售后的状态
    $services[$service_key]['status_name'] = service_status($service);

    //用户返回的账户信息
    $services[$service_key]['return_info'] = get_servicereturn($service['service_id']);
    
    //申请售后的商品
	$goods_list = $back_good_ref['service_id'][$service['service_id']];

    /* updated by zjli begin at 2014.1.21: 获取订单中的所有商品数量信息（废老库存）*/
    foreach ($goods_list as $goods_key => $goods) {
        // 此售后服务申请退换货的商品数量
        $append = "";
        if($service['service_status'] == 1 || $service['service_status'] == 2){
        	$append = " AND is_approved = 1";
        }
        $sql = "SELECT IFNULL(SUM(amount), 0) AS sumAmount FROM ecshop.service_order_goods sog WHERE sog.order_id = {$service['order_id']} AND sog.service_id = {$service['service_id']} AND sog.order_goods_id = {$goods['order_goods_id']}".$append;
		$service_amount = $db->getOne($sql);
		$goods_list[$goods_key]['service_amount'] = $service_amount;
		
		// 该订单中该商品已建立过售后服务申请的数量 (Still Improvements Needed)
		$sql = "SELECT SUM(amount) FROM ecshop.service_order_goods sog
				    INNER JOIN ecshop.service s ON s.service_id = sog.service_id
				    WHERE sog.order_goods_id = {$goods['order_goods_id']}
						 AND sog.service_id != {$service['service_id']}
				         AND ((s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 1 AND sog.is_approved = 1)
				            OR (s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 0 AND sog.is_approved = 0)
				            OR (s.is_complete = 0 AND s.inner_check_status = 32 AND s.service_status = 2 AND sog.is_approved = 1))";
		$amount_in_service = $db->getOne($sql);
		
		// 该订单中该商品还可以建立售后服务申请的数量
		$service_amount_available = $goods['goods_number'] - $amount_in_service;
		$goods_list[$goods_key]['service_amount_available'] =  $service_amount_available;
		
    }
    /* end of update at 2014.1.21 */
    $services[$service_key]['goods_list'] = $goods_list;  // 售后商品列表

    //售后日志
    $services[$service_key]['service_log'] = get_servicelog($service['service_id']);
	
    //换货订单的信息
	$services[$service_key]['change_order_info'] = $change_order_ref['order_id'][$service['change_order_id']][0];

    //退货订单的信息
    $services[$service_key]['back_order_info'] = $back_order_ref['order_id'][$service['back_order_id']][0];
    $services[$service_key]['back_order_info']['order_amount'] = abs($services[$service_key]['back_order_info']['order_amount']);
    
    $sql_refund = "select count(1) from romeo.refund where order_id = '{$back_order_ref['order_id'][$service['back_order_id']][0]['order_id']}'";
    $services[$service_key]['back_order_info']['refund_apply_disable'] = 0;
    if($db->getOne($sql_refund) >= 1) {
    	$services[$service_key]['back_order_info']['refund_apply_disable'] = 1;
    }
	// 获取订单确认时间及发货时间
	$confirm_time="";$confirm_user="";
	$shipping_time="";$shipping_user="";
	foreach($order_action_ref['service_id'][$service['service_id']] as $order_action){
		// 获取订单确认时间
		if($confirm_time=="" && $order_action['order_status'] == 1){
			$confirm_time = $order_action['action_time'];
			$confirm_user = $order_action['action_user'];
		}
		// 获取发货时间
		if($shipping_time=="" && $order_action['shipping_status'] == 1){
			$shipping_time = $order_action['action_time'];
			$shipping_user = $order_action['action_user'];
		}
	}
    $services[$service_key]['confirm_time'] = $confirm_time;
    $services[$service_key]['confirm_user'] = $confirm_user;
    $services[$service_key]['shipping_time'] = $shipping_time;
    $services[$service_key]['shipping_user'] = $shipping_user;
	
    // 能够修改售后服务商品的条件 ： 
    // 1 非保价， 
    // 2 不是初始状态和已拒绝申请状态(初始状态可以通过审核按钮修改) 
    // 3 还没有退货入库即还没有生成退货订单
    if ($service['service_status'] != intval(SERVICE_STATUS_DENIED)
        && $service['service_status'] != intval(SERVICE_STATUS_OK)
         && !$service['back_order_id'] && !$service['change_order_id']
         && $service['back_shipping_status'] != 12) {
        $services[$service_key]['can_edit_service_order_goods'] = true;
    } else {
        $services[$service_key]['can_edit_service_order_goods'] = false;
    }
    
    $services[$service_key]['prompt'] = '';
    
    // 售后档案相关
    if(isRMATrackNeeded()){
        if (count($rma_tracks) > 0) {
            foreach ($rma_tracks as $track) {
                //找到当前售后的档案
                if ($track->serviceId == $service['service_id']) {
                    // 两个属性的值要特殊处理成数组，方便smarty使用
                    $track->trackAttribute =  getTrackAttributeArrayByTrackId($track->trackId);
                    // 标配是否齐全这个属性单独处理
                    if ($track->trackAttribute['ALL_RETURNED']->value != 'Y') {
                        $services[$service_key]['prompt'] .= "标配不齐全";
                    }
                    $services[$service_key]['rma_tracks'][] = $track;
                }
            }
        }
    }

    // 显示可以处理该售后的仓库
    $services[$service_key]['available_facility'] = array('' => '未指定仓库') + $facility_list;
    $services[$service_key]['origin_facility_name'] = facility_mapping($service['origin_facility_id']);
}
//$timer_end4 = microtime(TRUE);
//$time_cost4 = $timer_end4 - $timer_start4;
//QLog::log("各种查询结束，耗时：".$time_cost4);
//$timer_end = microtime(TRUE);
//$time_cost = $timer_end - $timer_start;
//QLog::log("售后服务查询结束，耗时：".$time_cost);

$smarty->assign('party_id', $_SESSION['party_id']);
$smarty->assign('responsible_party_list', $responsible_party_list);
$smarty->assign('dispose_method_list', $dispose_method_list);
$smarty->assign('can_edit_track_attribute', false);
$smarty->assign('can_edit_track', true);
$smarty->assign('carrier', $carrier);
$smarty->assign('services', $services);
$smarty->assign('types_array', getTrackAttributeTypeOptions());
$smarty->assign('pager', $pager);
$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->display('v3/sale_service.dwt');

function getCondition() {
    global $db, $ecs;
    $act = $_REQUEST['act'];
    $service_status = $_REQUEST['service_status'];
    $service_call_status = $_REQUEST['service_call_status'];
    $inner_check_status = $_REQUEST['inner_check_status'];
    $outer_check_status = $_REQUEST['outer_check_status'];
    $back_shipping_status	= $_REQUEST['back_shipping_status'];
    $change_shipping_status = $_REQUEST['change_shipping_status'];
    $service_type = $_REQUEST['service_type'];
    $start_date = strtotime($_REQUEST['start']) > 0 ? $_REQUEST['start'] : '';
    $end_date = strtotime($_REQUEST['end']) > 0 ? $_REQUEST['end'] : '';
    $search_text = trim($_REQUEST['search_text']);
    $is_backbonus = $_REQUEST['backbonus'];
    $service_pay_status = $_REQUEST['service_pay_status'];
    $is_reply = $_REQUEST['is_reply'];
    $condition = '';
    if ($start_date || $end_date) {
        //将所有的搜索时间范围改为创建时间   by jrpei 2011-7-4
        $datetime = " s.apply_datetime ";
        if ($start_date && $end_date) {
            $condition .= " AND {$datetime} BETWEEN '{$start_date}' "
                         ." AND DATE_ADD('{$end_date}',INTERVAL 1 DAY) " ;
        } elseif ($start_date) {
            $condition .= " AND {$datetime} > '{$start_date}' " ;
        } else {
            $condition .= " AND {$datetime} <= DATE_ADD('{$end_date}',INTERVAL 1 DAY) ";
        }
    }
    
    if ($service_type) {
        $condition .= " AND s.service_type = '{$service_type}' ";
    }

    if (isset($service_status)) {
        if($service_status == 'all_denied'){
            $condition .= " AND s.service_status != '3' ";
        }else{
            $condition .= " AND s.service_status = '{$service_status}' ";
        }
    }
    
    if (isset($service_call_status)){
        $condition .= " AND s.service_call_status = '{$service_call_status}' ";
    }
    if($inner_check_status && $outer_check_status){
        $condition .= " AND (s.inner_check_status = '{$inner_check_status}' OR s.outer_check_status = '{$outer_check_status}')";
    }else{
        if (isset($inner_check_status)) {
            $condition .= " AND s.inner_check_status = '{$inner_check_status}' ";
        }

        if (isset($outer_check_status)) {
            $condition .= " AND s.outer_check_status = '{$outer_check_status}' ";
        }
    }
    if(isset($back_shipping_status)){
        $condition .= " AND s.back_shipping_status = '{$back_shipping_status}'";
    }
    if(isset($change_shipping_status)){
        $condition .= " AND s.change_shipping_status = '{$change_shipping_status}'";
    }
    if(isset($is_backbonus)){
        $condition .= " AND s.is_backbonus = '{$is_backbonus}'";
    }
    if(isset($service_pay_status)){
        $condition .= " AND s.service_pay_status= '{$service_pay_status}'";
    }
    if($is_reply){
        $condition .= " AND EXISTS ( SELECT 1 FROM service_comment sc WHERE sc.service_id = s.service_id  AND sc.reply = '' ) ";
    }
    if ($act == 'search' && $search_text) {
        $condition .= " AND (s.apply_username = '{$search_text}%' OR o.consignee like '{$search_text}%' OR o.order_sn LIKE '{$search_text}%'
        		OR EXISTS ( SELECT 1 FROM service_return r WHERE s.service_id = r.service_id AND r.return_value = '{$search_text}' AND r.return_name = 'deliver_number' AND r.return_type='carrier_info'  )
        		)";
    }
    
    // 特殊订单会导致系统资源耗尽,直接过滤掉  ljzhou 2013.05.07
	$condition .= " AND s.order_id not in('2073137') ";
	
    # 添加party条件判断 2009/08/06 yxiang
	$condition .= " AND " . party_sql('s.party_id');

    # 逆序排序 ncchen 090520
//    $condition .= " ORDER BY service_id DESC ";
    return $condition;
}

function getCount($condition) {
    global $db, $ecs, $slave_db;
    $service_type = $_REQUEST['service_type'] ? $_REQUEST['service_type'] : 1;
    $is_history_service = false;
    $time_condi = "AND s.apply_datetime > '". date("Y-m-d",strtotime("-6 months")). "'";
    # 添加party条件判断 2009/08/06 yxiang
    $sql_c = "SELECT COUNT(DISTINCT s.service_id,o.order_id) FROM service s
		INNER JOIN {$ecs->table('order_info')} o ON s.order_id = o.order_id
		-- INNER JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
		INNER JOIN romeo.order_shipment ros ON ros.order_id=convert(o.order_id using utf8)
        INNER JOIN romeo.shipment rs ON ros.shipment_id=rs.shipment_id
        WHERE 1 
        AND rs.status!='SHIPMENT_CANCELLED'
        $time_condi $condition AND ". party_sql('o.party_id') . " AND s.service_type = $service_type
        -- GROUP BY s.service_id,o.order_id    
	";
    $count = $slave_db->getOne($sql_c);
    return $count;
}
// 总的来说，这个方法经过ECB团灭之后就会有副作用了
function getCountByGroup($condition, $group_by_array_str) {
    global $db, $ecs, $slave_db;
    $service_type = $_REQUEST['service_type'] ? $_REQUEST['service_type'] : 1;
    $is_history_service = false;
    $time_condi = "AND s.apply_datetime > '". date("Y-m-d",strtotime("-6 months")). "'";
    # 添加party条件判断 2009/08/06 yxiang
    $sql_c = "SELECT {$group_by_array_str}, COUNT(*) as count FROM service s
        INNER JOIN {$ecs->table('order_info')} o ON s.order_id = o.order_id
        -- INNER JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
        LEFT JOIN romeo.order_shipment ros ON ros.order_id=convert(o.order_id using utf8)
        LEFT JOIN romeo.shipment rs ON ros.shipment_id=rs.shipment_id
        WHERE 1
        AND (rs.status is null or rs.status != 'SHIPMENT_CANCELLED') 
        $time_condi $condition AND ". party_sql('o.party_id') . " AND s.service_type = $service_type 
        GROUP BY {$group_by_array_str}
    ";
    return $slave_db->getAll($sql_c);
}