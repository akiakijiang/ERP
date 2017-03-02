<?php
define('IN_ECS', true);
require_once('includes/init.php');
require('includes/lib_service.php');
require("function.php");
require_once('distribution.inc.php');
require_once("includes/lib_common.php");
require_once("includes/lib_order.php");
require_once('includes/alipay/alipay.php');
include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
require_once(ROOT_PATH. 'RomeoApi/lib_payment.php');
admin_priv('weight_delivery');

$type = isset($_REQUEST['type']) ? $_REQUEST['type'] : 1;//默认为单个称重

if ($_REQUEST['act'] == 'submit') {
    $batch_bill_no = isset($_REQUEST['batch_bill_no']) ? trim($_REQUEST['batch_bill_no']) : '';
    $bill_list = preg_split('/[\s]+/', $batch_bill_no);
    if ($type == 1) {
        foreach ($bill_list as $key => $value) {
            $value = trim($value);
            list($bill_no, $weight) = preg_split('/-/', $value);
            if (trim($bill_no) == '') {
                unset($bill_list[$key]);
                continue;
            }
            $bill_nos[] = $bill_no;
            $weight_list[$bill_no] = $weight * 1000;
            unset($weight);
        }
    } else {
        foreach ($bill_list as $key => $bill_no) {
            $bill_no = trim($bill_no);
            if (strrpos($bill_no, '-') !== false) {
                list($bill_no, $weight_list) = preg_split('/-/', $bill_no);
                $bill_no = trim($bill_no);
                $weight_list = trim($weight_list) * 1000;
                if (trim($bill_no) == '') {
                    unset($bill_list[$key]);
                }
            }
            $bill_nos[] = $bill_no;
        }
    }
    $smarty->assign('weight', $weight_list);
    $bill_nos = array_unique($bill_nos);
	
	//码托流程运单号
	$pallet_bill_nos = array();

    //成功发货的订单
    $ok_orders = array();
    //有问题的订单
    $badstatus_orders = array();
    //没有订单对应的运单号
    $noorder_bill_nos = array();
    //取消的订单
    $canceled_orders = array();
    //未到发货时间订单
    $best_time_orders = array();
    //没有发票的订单
    $noinvoice_orders = array();
    // 一个订单有多张面单， 传入面单不全的
    $merge_orders = array();
    // 一个订单有多张面单， 传入面单不全的，更新传入面单的重量
    $merge_orders_update_weight = array();
    // 判断已经发货的运单号
    $sendTrackingNums = array();
    
    // 分销预存款抵扣失败订单    
    $prepayment_consume_fail = array();
    
    $ok_bill_num = 0;
    if (!is_array($bill_nos) || count($bill_nos) == 0) {
        sys_msg('沒有运单号要发货');
    }
    $i = 0;
    
    //判断bill_nos中是否存在限制业务组运单号
    $bill_no_str = implode("','",$bill_nos);
    $sql = "select tracking_number from romeo.shipment " .
    		"where tracking_number in ('{$bill_no_str}') and party_id in ('65644','65650','65581','65617','65652','65653','65569','65622','65645','65661','65668','65646','65539','65670','65619','65628','65639')";
    $tns = $db->getCol($sql);
    if(!empty($tns)){
    	$pallet_bill_nos = $tns;
    }		
    
    foreach ($bill_nos as $key => $bill_no) {
    	if(in_array($bill_no, $sendTrackingNums)){
    		continue ;
    	}
    	if(in_array($bill_no,$pallet_bill_nos)){
    		continue;
    	}
    
    	// 先检查同一个订单对应多少面单，
    	$trackingNum_sql = "
            select distinct s2.shipment_id, s2.status, s2.primary_order_id, s2.tracking_number
            from romeo.shipment s 
            inner join romeo.order_shipment os on s.shipment_id = os.shipment_id 
            inner join romeo.order_shipment os2 on os.order_id = os2.order_id 
            inner join romeo.shipment s2 on os2.shipment_id = s2.shipment_id 
            where s.SHIPPING_CATEGORY = 'SHIPPING_SEND' AND s2.SHIPPING_CATEGORY = 'SHIPPING_SEND'
            AND s.TRACKING_NUMBER = '%s' ;" ;
    	$trackingNums = $db->getAll(sprintf($trackingNum_sql, $bill_no));
    	
    	if(empty($trackingNums)){
    		$noorder_bill_nos[] = $bill_no; //没有对应订单的运单号
    		continue ;
    	}else if(count($trackingNums) == 1){
    		// $ok_bill_num ++; 
    	}else if(count($trackingNums) > 1){
    	    $tn_all = true ;
    	    $temp_merge_orders = array();
    	    $handle=soap_get_client('ShipmentService');
    	    foreach($trackingNums as $tn){
    	        $temp_merge_orders[] = strval($tn['tracking_number']);
    	        if(!in_array($tn['tracking_number'], $bill_nos)){
    	            $tn_all = false ;
    	        } 

    	        // 清除大集合中的运单号  减少循环次数
    	        $sendTrackingNums[] = $tn['tracking_number'];
    	    }

    	    // 如果同一个订单有多张面单，传入的面单不全则退出
    	    if(!$tn_all){
    	        $merge_orders[] = implode('; ', $temp_merge_orders);
    	        foreach($trackingNums as $tn){
    	            if(in_array($tn['tracking_number'], $bill_nos)){
    	                //同一个订单对应多个面单时，更新传入运单的重量
    	                $bill_no_one = $tn['tracking_number']; 
    	                if ($type == 2) {
    	                    $merge_orders_weight = $weight_list;
    	                } else {
    	                    $merge_orders_weight = $weight_list[$bill_no_one];
    	                }

    	                try{
    	                    $handle->updateShipment(array(
        			         'shipmentId'=>$tn['shipment_id'],
        			         'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
                             'shippingLeqeeWeight'=>$merge_orders_weight,
    	                    ));
    	                    $merge_orders_update_weight[$i]['tracking_number'] = $tn['tracking_number'];
    	                    $merge_orders_update_weight[$i]['weight'] = $merge_orders_weight;
    	                    $i++;
    	                } catch (Exception $e){}
    	            }
    	        }
    	        continue ;
    	    }
    	}
    	
    	
    	// 检索运单内的订单
    	$order_sql = "
    	    select oi.order_id, oi.order_sn, oi.order_time, oi.order_status, oi.shipping_status, oi.pay_status, oi.order_type_id,
                oi.goods_amount, oi.bonus, oi.distributor_id, oi.party_id, oi.province, oi.shipping_id, oi.taobao_order_sn    
            from romeo.order_shipment os 
            inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id
            where os.shipment_id = '%s' and " . facility_sql('oi.facility_id');
    	
    	$orders = $db->getAll(sprintf($order_sql, $trackingNums[0]['shipment_id']));

        $send_bill = true ;
        foreach ($orders as $order) {
            // 如果是备发货状态则发货,用continue是为了给界面一个发货的入口，免得合并订单其中部分订单无法发货
            if ($order['shipping_status'] != 8) {
                $badstatus_orders[] = array('order' => $order, 'bill_no' => $bill_no);
                $send_bill = false ;
                continue ;
            }
            
            ///如果是取消的订单，则直接跳出
            if ($order['order_status'] == OS_CANCELED) {
                $canceled_orders[] = array('order' => $order, 'bill_no' => $bill_no);
                $send_bill = false ;
                break ;
            }
            
            if ($order['best_time'] && strtotime($order['best_time']) > time()) {
                $best_time_orders[] = array('order'=>$order, 'bill_no' => $bill_no);
                $send_bill = false ;
                break ;
            }
            
            // 电教或金佰利或亨氏分销订单 康贝代销订单 要抵扣预存款
            $sql = "
                select d.is_prepayment, md.main_distributor_id, md.name, md.type from ecshop.distributor d 
                left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id 
                where d.distributor_id = '%d' limit 1 ;" ;
            $main_distributor = $db->getRow(sprintf($sql, intval($order['distributor_id'])));
          
            //康贝的苏州乐贝母婴专营是自己的店  不用扣预存款, 惠氏的苏州乐贝专营店要扣预存款
            $edu_adjust_need = (($order['party_id'] == PARTY_LEQEE_EDU || $order['party_id'] == 65558 || $order['party_id'] == 65609 || 
            					$order['party_id'] == 65617 || $order['party_id'] == 65553)
                              && $order['order_type_id'] == 'SALE' && $order['distributor_id'] != 1201  
                              && $main_distributor && $main_distributor['type']=='fenxiao' && $main_distributor['is_prepayment']=='Y') ;
            
            if ($edu_adjust_need){
            	// 分销的销售订单，抵扣预付款
            	$result = distribution_edu_order_adjustment($order, $main_distributor['main_distributor_id']) ;
            	if (!empty($result)){
            		$prepayment_consume_fail[$bill_no] = array('order' => $order, 'bill_no' => $bill_no, 'msg' => $result) ;
            		$send_bill = false ;
                    break ;
            	}
            }
            
            // 如果是直销的销售订单，需要做是否开票检查
            if ($order['order_type_id'] == 'SALE' && $order['distributor_id'] == 0 ) {
                // 商品金额被红包抵扣, 不需要开票
                if (abs($order['bonus']) >= $order['goods_amount']) {
                	update_order_shipping_invoice($order['order_id'],'BKP');
                }
                // 如果订单是电商服务下的，不需要开票
                elseif (party_check(PARTY_EB_PLATFORM, $order['party_id'])) {
                	update_order_shipping_invoice($order['order_id'],'BKP');
                }
                // 如果是B2C的需要检查是否已开票，C2C的不开票
                else {
                    $sql = "
                        SELECT 
                            ii.inventory_item_acct_type_id as order_type, si.shipping_invoice, og.goods_price
                        FROM 
                            ecs_order_goods AS og 
                            LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8)=iid.order_goods_id
                            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
                            LEFT JOIN romeo.order_shipping_invoice si ON og.order_id = si.order_id
                        WHERE
                            ii.status_id='INV_STTS_AVAILABLE' and og.order_id = '{$order['order_id']}'
                            limit 1
                    ";
                    $item_info = $db->getRow($sql);
                    if ($item_info['order_type'] == 'C2C') {
                        update_order_shipping_invoice($order['order_id'],'BKP');
                    }
                    else {
                        if ($item_info['goods_price'] > 0 && empty($item_info['shipping_invoice'])) {
                            $noinvoice_orders[] = array('order' => $order, 'bill_no' => $bill_no);
                            continue 2;
                        }
                    }
                }
            }
            
            //分销商的直销订单也要做相应的开票检查
            if ($order['order_type_id'] == 'SALE' && $order['distributor_id'] != 0 ) {
            	$sql="select 
            	          md.type 
		              from 
		                  ecshop.main_distributor md 
			              inner join ecshop.distributor d on d.main_distributor_id=md.main_distributor_id
			          where 
			              d.distributor_id='{$order['distributor_id']}'";
		        $type=$db->getOne($sql);
		        // 电教、乐其蓝光下面的oppo影音官方旗舰店
	            if(($order['party_id'] == 16 || $order['party_id'] == 65548 
	                || ($order['party_id'] == 64  && $order['distributor_id']==	2166)) 
	                && $order['order_type_id']=='SALE' && $type=='zhixiao'){
					$sql = "
                        SELECT 
                            ii.inventory_item_acct_type_id 	 as order_type, si.shipping_invoice, og.goods_price
                        FROM 
                            ecs_order_goods AS og 
                            LEFT JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8)=iid.order_goods_id
                            LEFT JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
                            LEFT JOIN romeo.order_shipping_invoice si ON og.order_id = si.order_id
                        WHERE
                            ii.status_id='INV_STTS_AVAILABLE' and og.order_id = '{$order['order_id']}' 
                            AND ii.inventory_item_acct_type_id='B2C' and og.goods_price > 0
                            limit 1
                    ";
                    $item_info = $db->getRow($sql);
                    if (!empty($item_info) && empty($item_info['shipping_invoice'])) {
                        $noinvoice_orders[] = array('order' => $order, 'bill_no' => $bill_no);
                        continue 2;
                    }
	            }
            }
            
            $sql = "UPDATE {$ecs->table('order_info')} SET shipping_time = UNIX_TIMESTAMP(), shipping_status = 1 WHERE order_id = '{$order['order_id']}' ";
            $db->query($sql);
            // 记录订单状态
            orderActionLog(array('order_id'=>$order['order_id'], 'order_status'=>$order['order_status'], 'shipping_status'=>1, 'pay_status'=>$order['pay_status'], 'action_note'=>'称重发货：待发货操作发货'));

            // update order mixed status 
            // include_once('includes/lib_order_mixed_status.php');
            // update_order_mixed_status($order['order_id'], array('shipping_status' => 'shipped'), 'worker');
        }
        $noinvoice_bill_no=array();
        foreach($noinvoice_orders as $noinvoice_order){
        	 array_push($noinvoice_bill_no,$noinvoice_order['bill_no']);
        }
        // 配送发货
        try{
        	// 预存款抵扣有问题， 不发货
        	if(array_key_exists($bill_no, $prepayment_consume_fail) || $send_bill == false || in_array($bill_no,$noinvoice_bill_no)){
        		continue ;
        	}
            $handle=soap_get_client('ShipmentService');
            foreach($trackingNums as $tn){
                if ($type == 2) {
                    $weight = $weight_list;
                } else {
                    $weight = $weight_list[$tn['tracking_number']];
                }
                $handle->updateShipment(array(
        			'shipmentId'=>$tn['shipment_id'],
        			'status'=>'SHIPMENT_SHIPPED',
        			'lastModifiedByUserLogin'=>$_SESSION['admin_name'],
                    'shippingLeqeeWeight'=>$weight,
        		    ));
        		
        		// 计数已发货的运单    
        	    $ok_bill_num ++;    
        	    // 记录发货操作成功的订单 
        	    foreach ($orders as $order) {
        	    	$ok_orders[] = array('order'=>$order, 'bill_no' => $tn['tracking_number']);
        	    }
            }
        } catch (Exception $e) { }
    }

    // $smarty->assign('error_mask_phones', $error_mask_phones);

    $smarty->assign('noinvoice_orders', $noinvoice_orders);
    $smarty->assign('canceled_orders', $canceled_orders);
    $smarty->assign('noorder_bill_nos', $noorder_bill_nos);
    $smarty->assign('pallet_bill_nos',implode(",",$pallet_bill_nos));
    $smarty->assign('badstatus_orders', $badstatus_orders);
    $smarty->assign('best_time_orders', $best_time_orders);
    $smarty->assign('merge_orders', $merge_orders);
    $smarty->assign('ok_orders', $ok_orders);
    $smarty->assign('merge_orders_update_weight', $merge_orders_update_weight);
    
    $smarty->assign('prepayment_consume_fail', $prepayment_consume_fail);
    
    $smarty->assign('bill_no_num', count($bill_nos));
}
$facility_list = get_user_facility();
$facilities = implode(',', $facility_list);
$smarty->assign('facilities',$facilities);
$smarty->assign('type', $type);
$smarty->display("oukooext/shipment.htm");
//$smarty->display("oukooext/test.htm");

