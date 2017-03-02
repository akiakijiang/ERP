<?php

/**
 * 操作订单的合并发货
 * 
 * @author yxiang@leqee.com
 * @copyright 2010 leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
admin_priv('merge_order','order_edit');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

if($_SESSION['party_id'] == 65625 ){
	echo " 中粮业务组 请用中粮-合并订单页";
	die( ); 
}


// 期初时间
$start = 
    isset($_REQUEST['start']) && !empty($_REQUEST['start']) && strtotime($_REQUEST['start'])!==false
    ? $_REQUEST['start']
    : date('Y-m-d H:i:s',strtotime('-1 day'));
    
// 期末时间
$ended =
    isset($_REQUEST['ended']) && !empty($_REQUEST['ended']) && strtotime($_REQUEST['ended'])!==false
    ? $_REQUEST['ended']
    : date('Y-m-d H:i:s');
    
//顾客ID
$nick_name = $_REQUEST['nick_name'];

//直分销类型 ljzhou 2012.12.18
$distributor_type_list= array(
	'zhixiao' => '直销',
	'fenxiao' => '分销'
);
// 默认
$distributor_type =
	isset($_REQUEST['distributor_type']) && trim($_REQUEST['distributor_type'])
    ? $_REQUEST['distributor_type']
    : 'zhixiao';
    
// 组织
/*
$party_id = 
    isset($_REQUEST['party_id'])
    ? $_REQUEST['party_id']
    : null;
*/

// 操作
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'],array('order_shipment_merge'))
    ? $_REQUEST['act']
    : null;

$filter=array(
    'start'=>$start,
    'ended'=>$ended,
    'distributor_type'=>$distributor_type,
    'nick_name'=>$nick_name
);


/**
 * 合并订单发货
 */
if($act=='order_shipment_merge')
{
    do 
    {
        if(empty($_POST['checked'])){
            $smarty->assign('message','没有选中要合并发货的订单');
            break;
        }
        $orderIdList = array();
        
        // 查询出要合并发货的订单
        $sql="select oi.order_id, oi.order_sn, oi.order_status, oi.shipping_status, oi.consignee, oi.tel,
                      oi.mobile, oi.province, oi.city, oi.district, oi.address, oi.shipping_id, oi.party_id,
                      oi.facility_id, IFNULL(a.attr_value, oi.nick_name) as nick_name
                 from ecshop.ecs_order_info oi 
                      left join ecshop.order_attribute a on oi.order_id = a.order_id and a.attr_name = 'TAOBAO_USER_ID'
                where oi.order_id ".db_create_in($_POST['checked']). "order by oi.nick_name";  
        $nick_names = array();
        $list_by_nick_name = array();      
        $merge_list=$db->getAllRefby($sql,array('nick_name'),$nick_names,$list_by_nick_name);
        if(empty($merge_list)){
            $smarty->assign('message','没有查找到订单');
            break;
        }
        foreach($list_by_nick_name['nick_name'] as $key=>$item) {
        	if(count($item)<2){
	            $message .= "用户ID：".$key." 无法合并订单，必须要超过2个或2个订单以上订单才能操作合并发货<br>";
	            continue;
        	}
        	// 检查要合并发货的订单
	        $primary_order=array_shift($item);
	        $orderIdList[$key][] = $primary_order['order_id'];
	        
	        if($primary_order['order_status']!=0 && $primary_order['order_status']!=1) {
	            $message .= "订单{$primary_order['order_sn']}状态已取消或已拒收。<br>";
	            continue;
	        }
	        else if($primary_order['shipping_status']!=0) {
	            $message .= "订单{$primary_order['order_sn']}不是待配货状态。<br>";
	            continue;
	        }
	        else if(empty($primary_order['facility_id'])){
	            $message .= "订单{$primarty_order['order_sn']}没有指定配货仓库。<br>";
	            continue;
	        }

	        foreach($item as $merge_item){          	
	            // 检查合并发货的订单是否在一个仓库
	            if($merge_item['facility_id']!=$primary_order['facility_id']){
	                $message .= "用户ID:".$primary_order['nick_name']."的订单无法合并，原因： 要合并发货的订单不在一个仓库!<br>";
	                break;
	            }
	            // 检查所属业务
	            if($merge_item['party_id'] != $primary_order['party_id']){
	                $message .= "用户ID:".$primary_order['nick_name']."的订单无法合并，原因：  要合并发货的订单不属于同一业务!<br>";
	                break;
	            }
	            // 检查合并发货的订单是否是同一个快递方式
	            if($merge_item['shipping_id'] != $primary_order['shipping_id']){
	                $message .= "用户ID:".$primary_order['nick_name']."的订单无法合并，原因： 要合并发货的订单配送方式不一致!<br>";
	                break;
	            }
	            // 检查收货人
	            if($merge_item['consignee'] != $primary_order['consignee']){
	                $message .= "用户ID:".$primary_order['nick_name']."的订单无法合并，原因： 要合并发货的订单的收货人不一样!<br>";
	                break;
	            }          
	            // 检查检查淘宝ID
	//            if($merge_item['taobao_user_id'] != $primary_order['taobao_user_id']){
	//                //$smarty->assign('message',"要合并发货的订单的淘宝ID不一样!");
	//                //break 2;
	//            }
	            // 检查收货地址
	            if($merge_item['province'] != $primary_order['province'] ||
	                    $merge_item['city'] != $primary_order['city'] ||
	                    $merge_item['district'] != $primary_order['district'] || 
	                    $merge_item['address'] != $primary_order['address']){
	                $message .= "用户ID:".$primary_order['nick_name']."的订单无法合并，原因：要合并发货的订单的收货地址不一样!<br>";
	                break;
	            }
	            // 检查电话
	            if($merge_item['mobile']!=$primary_order['mobile']){
	                $message .= "用户ID:".$primary_order['nick_name']."的订单无法合并，原因： 要合并发货的订单手机号不一样!<br>";
	                break;
	            }
	            // 检查座机
	            if($merge_item['tel'] != $primary_order['tel']){
	                $message .= "用户ID:".$primary_order['nick_name']."的订单无法合并，原因： 要合并发货的订单电话号不一样!<br>";
	                break;
	            }	            
	            $orderIdList[$key][] = $merge_item['order_id'];
	        }
	        // 已经批捡的就无法再合并订单 ljzhou 2013-11-20
			$has_pick_orders = check_order_is_batch_pick($orderIdList[$key]);
			if(!empty($has_pick_orders)){
		    	$message .= "订单：".$has_pick_orders." 已经进入批捡，无法合并订单！";
		        continue;
		    }
		    
			$db->start_transaction();
		    // 执行合并操作
		    try{
		    	
		        $handle=soap_get_client('ShipmentService');
		        $handle->ordersMergeToShipment(array('orderIdList'=>$orderIdList[$key], 'username'=>$_SESSION['admin_name']));				    				    						 			       		        		        		        
		        // 增加合并订单操作日志 
		        $orderIds = get_merge_order_ids($primary_order['order_id']);
				$order_ids = implode(',',$orderIds);
				$orderSns = get_order_sns($order_ids);
				$order_sns = implode(',',$orderSns);			
				foreach ($orderIds as $orderId) {
					//1.order_action表
					$sql = "select * from ecshop.ecs_order_info where order_id = ".$orderId;
					$order = $db->getRow($sql);
				    $action['order_id']        = $orderId;
				    $action['order_status']    = $order['order_status'];
				    $action['pay_status']      = $order['pay_status'];
				    $action['shipping_status'] = $order['shipping_status'];
				    $action['action_time']     = date("Y-m-d H:i:s");
				    $action['action_note']     = "合并订单查询页合并订单：" . "{$order_sns}";
				    $action['action_user']     = $_SESSION['admin_name'];
				    $db->autoExecute("ecshop.ecs_order_action", $action);				   		
				}
				$message .= "订单：".$order_sns." 合并成功<br>";	
				$db->commit();
		    }catch(Exception $e){
		    	$db->rollback();
		        $message = "操作失败：".$e->getMessage();
		        require_once(ROOT_PATH . 'includes/debug/lib_log.php');
		        QLog::log("订单（orderId: ".$primary_order['order_id']."）合并失败 " . $e->getMessage(),QLog::ERR);
		        break;
		    }
        }  	      
             
    } while (false);
}

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

if(strtotime($ended_datetime)-strtotime($start_datetime) > 3600*24*5) {
    sys_msg("查询时间间隔过大，系统可能无法处理，请修改后重试");
}

$sqloutfacility = "select facility_id from romeo.facility where IS_OUT_SHIP = 'Y'";
$out_facility_ids = $db->getCol($sqloutfacility);


// 可以合并发货的订单
//添加了淘宝的用户Id,配送方式,邮费和订单金额     by jrpei 2011-7-4
$sql1 = "SELECT 
	        count(*) as num,  GROUP_CONCAT(oi.order_id) as order_id_str
    from 
        ecshop.ecs_order_info oi force index (order_info_multi_index)
        inner join ecshop.distributor d ON oi.distributor_id = d.distributor_id
        inner join ecshop.main_distributor md ON d.main_distributor_id = md.main_distributor_id
    where
        order_status in (0,1) and shipping_status=0 and oi.facility_id not ".db_create_in($out_facility_ids)." and order_time between '$start_datetime' and '$ended_datetime' and 
        order_type_id in('SALE','SHIP_ONLY','RMA_EXCHANGE') and ". party_sql('oi.party_id') ."
        and md.type = '{$distributor_type}'".sql_condition($_REQUEST)."
    group by 
        oi.consignee,oi.province,oi.city,oi.district,oi.address,oi.mobile,oi.tel
    having 
        num > 1";
	$order_ids = $db->getAll($sql1);
	qlog::log("sql1 : ".$sql1);
	if(!empty($order_ids)){
		$order_ids_arr = array();
		foreach($order_ids as $key=>$value){
			array_push($order_ids_arr,$value['order_id_str']);
		}
		$order_ids_str = str_replace(",","','",implode(",",$order_ids_arr));
		$sql2 = "
		select 
	        o.order_id,o.order_sn,o.taobao_order_sn,o.consignee,o.tel,o.mobile,o.order_amount,o.shipping_name,o.pay_name,
	        o.shipping_fee,
	        (select region_name from ecs_region where region_id=o.province limit 1) as province_name,
	        (select region_name from ecs_region where region_id=o.city limit 1) as city_name,
	        (select region_name from ecs_region where region_id=o.district limit 1) as district_name,
	       o.nick_name as TAOBAO_USER_ID,
	        o.address
	from ecshop.ecs_order_info o  
		where  o.order_id in ('{$order_ids_str}') and  exists
	        (
	          select 
	                1 
	          from 
	              ecshop.distributor d2 
	              inner join ecshop.main_distributor md2 ON d2.main_distributor_id = md2.main_distributor_id
	          where 
	              md2.type = '{$distributor_type}' and d2.distributor_id = o.distributor_id
	          limit 1
	        )
	        and not exists (select 1 from romeo.picklist_item where order_id = o.order_id and status != 'PICKITEM_CANCELLED') order by o.address";
	$ref_fileds1=$ref_rowset1=array();
	$order_list=$db->getAllRefby($sql2,array('order_id'),$ref_fields1,$ref_rowset1);
//QLog::log("order_list_sql : ".$sql1);

    // 计算这些订单中哪些是已经合并发货了的
    $sql2= "select ORDER_ID,SHIPMENT_ID from romeo.order_shipment where ORDER_ID ". db_create_in($ref_fields1['order_id']);
    $ref_fileds2=$ref_rowset2=array();
    $order_shipment_list=$db->getAllRefby($sql2,array('SHIPMENT_ID'),$ref_fileds2,$ref_rowset2);
    $shipment_id_list=$exclude_order_id_list=array();
    foreach($order_shipment_list as $order_shipment){
        // 这个配送单是合并发货的
        if (isset($shipment_id_list[$order_shipment['SHIPMENT_ID']])) {
            foreach($ref_rowset2['SHIPMENT_ID'][$order_shipment['SHIPMENT_ID']] as $_item) {
                $exclude_order_id_list[$_item['ORDER_ID']]=1;
            }
        }
        else {
            $shipment_id_list[$order_shipment['SHIPMENT_ID']] = 1;
        }
    }

    // 从这些订单中剔除已经合并发货的
    if($exclude_order_id_list!==array()){
        foreach($order_list as $key=>$item) {
            if (isset($exclude_order_id_list[$item['order_id']])) {
                unset($order_list[$key]);
            }
        }
    }
}
$smarty->assign('message',$message);
$smarty->assign('filter',$filter);
$smarty->assign('distributor_type_list',$distributor_type_list);
$smarty->assign('order_list',$order_list);
$smarty->display('order/order_shipment.htm');

function sql_condition($arg) {
	$sql_condition = "";
	if(!empty($arg['nick_name'])) {
		$sql_condition = " and oi.nick_name = '{$arg['nick_name']}' ";
	}	
	return $sql_condition;
}
