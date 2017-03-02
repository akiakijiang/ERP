<?php 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once('distribution.inc.php');
require_once(ROOT_PATH. 'RomeoApi/lib_payment.php');
admin_priv('pallet_delivery');
//码托编码
$pallet_no = 
    isset($_REQUEST['pallet_no']) && trim($_REQUEST['pallet_no']) 
    ? $_REQUEST['pallet_no'] 
    : false ;

// 当前页的url,构造url用
$url = 'pallet_shipment.php';
if ($pallet_no) {
	$sql_time_start1 = microtime(true);
	//码托已发货
	$has_shipped = false;
	//已绑定（且不属于此码托编码）运单
	$other_pallet_list = array();
	//已绑定，订单状态！=1  运单
	$cancel_order_list = array();
	//运单快递方式与码托不符
	$other_shipping_list = array();
	//预存款不足
	$prepayment_consume_fail = array();
	//没有发票的订单
    $noinvoice_orders = array();
    //没有订单对应运单
    $noorder_bill_nos = array();

	//已经发货的运单
	$shipped_tracking_numbers = array();
	//可以操作发货的运单
    $ok_tracking_numbers = array();
    
    $tracking_numbers_list = array();
	
	$sql = "select rp.pallet_id,rp.pallet_no,rp.ship_status,rp.shipping_id as rp_shipping_id,s.tracking_number,group_concat(distinct sm1.pallet_no) as sm1_pallet_no_group,
		s.status as s_shipped_status,group_concat(distinct oi.order_status) as order_status_group,group_concat(distinct oi.shipping_id) as oi_shipping_id_group,
		group_concat(distinct oi.order_id) as order_id_group,es.shipping_name,group_concat(distinct es2.shipping_name) as oi_shipping_name_group
		from romeo.pallet rp
		  inner join romeo.pallet_shipment_mapping sm on sm.pallet_no = rp.pallet_no
		  inner join romeo.shipment s on s.shipment_id = sm.shipment_id 
		  inner join romeo.pallet_shipment_mapping sm1 on sm1.shipment_id = s.shipment_id
		  inner join romeo.order_shipment os on os.shipment_id = s.shipment_id
		  inner join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned)
		  inner join ecshop.ecs_shipping es on es.shipping_id = rp.shipping_id
		  inner join ecshop.ecs_shipping es2 on es2.shipping_id = oi.shipping_id
		  where rp.pallet_no = '{$pallet_no}' and sm.bind_status = 'BINDED' and sm1.bind_status = 'BINDED' 
		  group by s.tracking_number ";
	$bill_nos_info_list = $db->getAllRefby($sql,array('tracking_number'),$bill_nos_infos, $refs_bill_nos_infos, false); 
	$tracking_number_str = implode("','",$bill_nos_infos['tracking_number']);
	
	$sql_time_end1 = microtime(true);
	$sql = "select s.tracking_number from romeo.pallet rp
		  inner join romeo.pallet_shipment_mapping sm on sm.pallet_no = rp.pallet_no  
		  inner join romeo.shipment s on s.shipment_id = sm.shipment_id   
		  left join romeo.order_shipment os on os.shipment_id = s.shipment_id   
		  left join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned)
		  where rp.pallet_no = '{$pallet_no}' and sm.bind_status = 'BINDED' and oi.order_id is null "; 
	$noorder_bill_nos = $db->getCol($sql);	  
	if(empty($bill_nos_info_list)) {
		$message = "码托".$pallet_no."没有搜索到运单,请核实";
		sys_msg($message);
	}else{
		foreach($bill_nos_info_list as $key => $bill_nos_info){
			if($bill_nos_info['ship_status']=='SHIPPED'){
				$has_shipped = true;
				break;
			}
			if(strcasecmp($bill_nos_info['pallet_no'],$bill_nos_info['sm1_pallet_no_group'])!=0){
				$other_pallet_list[] = array('tn'=>$bill_nos_info['tracking_number'],'pallet_nos'=>$bill_nos_info['sm1_pallet_no_group']);
				continue;
			}
			
			if($bill_nos_info['order_status_group'] != '1'){
				$cancel_order_list[] = $bill_nos_info['tracking_number'];
				continue;
			}
			if($bill_nos_info['rp_shipping_id'] != $bill_nos_info['oi_shipping_id_group']){
				$other_shipping_list[] = array('tn'=>$bill_nos_info['tracking_number'],'ship_name'=>$bill_nos_info['oi_shipping_name_group']);;
				continue;
			}
			if($bill_nos_info['s_shipped_status']=='SHIPMENT_SHIPPED'){
				$shipped_tracking_numbers[] = $bill_nos_info['tracking_number'];
				continue;
			}
			$tracking_numbers_list[$bill_nos_info['tracking_number']] = $bill_nos_info['order_id_group']; 
		}
		if(!empty($tracking_numbers_list)){
			$sql_time_start2 = microtime(true);
			$order_ids = implode(",",array_values($tracking_numbers_list));
			$sql = "
	            select d.is_prepayment, md.main_distributor_id, md.name, md.type,oi.order_sn,oi.distributor_id,oi.party_id, 
	            oi.order_id, oi.order_sn, oi.order_time, oi.order_status, oi.shipping_status, oi.pay_status, oi.order_type_id,
	            oi.goods_amount, oi.bonus, oi.distributor_id,  oi.province, oi.shipping_id, oi.taobao_order_sn ,s.tracking_number
	            from  ecshop.ecs_order_info oi 
	            inner join ecshop.distributor d on d.distributor_id = oi.distributor_id
	            inner join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8)
	            inner join romeo.shipment s on s.shipment_id = os.shipment_id and s.Shipping_category = 'SHIPPING_SEND'
	            left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id 
	            where oi.order_id in ($order_ids) and s.tracking_number in ('{$tracking_number_str}') " ;
			$order_infos = $db->getAll($sql);
			$sql_time_end2 = microtime(true);
			foreach($order_infos as $key=>$order){
				/**预存款不足*/
				// 电教或金佰利或亨氏分销订单 康贝代销订单 要抵扣预存款
				//康贝的苏州乐贝母婴专营是自己的店  不用扣预存款, 惠氏的苏州乐贝专营店要扣预存款
	            $edu_adjust_need = ((in_array($order['party_id'],array(PARTY_LEQEE_EDU,65558,65609,65617,65553)))
	                              && $order['order_type_id'] == 'SALE' && $order['distributor_id'] != 1201  
	                              && $order && $order['type']=='fenxiao' && $order['is_prepayment']=='Y') ;
	            if ($edu_adjust_need){
	            	// 分销的销售订单，抵扣预付款
	            	$result = distribution_edu_order_adjustment($order, $order['main_distributor_id']) ;
	            	if (!empty($result)){
	            		$prepayment_consume_fail[] = array('order_id' => $order['order_id'], 'bill_no' => $order['tracking_number'], 'order_sn' => $order['order_sn'],'msg' => $result) ;
	                    unset($tracking_numbers_list[$order['tracking_number']]);
	                    continue ;
	            	}
	            }
				
				/**没有发票的订单*/
				// 如果是直销的销售订单，需要做是否开票检查
	            if ($order['order_type_id'] == 'SALE' && $order['distributor_id'] == 0 ) {
	                // 商品金额被红包抵扣, 不需要开票
	                if (abs($order['bonus']) >= $order['goods_amount']) {
	                	update_order_shipping_invoice($order['order_id'],'BKP');
	                }elseif (party_check(PARTY_EB_PLATFORM, $order['party_id'])) {// 如果订单是电商服务下的，不需要开票
	                	update_order_shipping_invoice($order['order_id'],'BKP');
	                }else {// 如果是B2C的需要检查是否已开票，C2C的不开票
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
	                            $noinvoice_orders[] = array('order_id' => $order['order_id'], 'bill_no' => $order['tracking_number'],'order_sn'=> $order['order_sn']);
	                            unset($tracking_numbers_list[$order['tracking_number']]);
	                            continue;
	                        }
	                    }
	                }
	            }
	            
	            //分销商的直销订单也要做相应的开票检查
	            if ($order['order_type_id'] == 'SALE' && $order['distributor_id'] != 0 ) {
	            	$sql="select md.type from  ecshop.main_distributor md 
							inner join ecshop.distributor d on d.main_distributor_id=md.main_distributor_id
							where d.distributor_id='{$order['distributor_id']}'";
			        $type=$db->getOne($sql);
			        // 电教、乐其蓝光下面的oppo影音官方旗舰店
		            if((in_array($order['party_id'],array(16,65548,64)))  && $order['distributor_id']==	2166 && $type=='zhixiao'){
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
	                        $noinvoice_orders[] = array('order_id' => $order['order_id'], 'bill_no' => $order['tracking_number'],'order_sn'=> $order['order_sn']);
	                        unset($tracking_numbers_list[$order['tracking_number']]);
	                        continue;
	                    }
		            }
	            }
	            
			}
			if(!empty($tracking_numbers_list)) {
				$ok_tracking_numbers = array_keys($tracking_numbers_list);
			}
			$sql_time_end2_2 = microtime(true);
		}
	}
	$can_ship = false;
    if(!$has_shipped&&empty($other_pallet_list)&&empty($cancel_order_list)&&empty($other_shipping_list)&&empty($noinvoice_orders)&&empty($prepayment_consume_fail)){
    	$can_ship = true;
    }
    $sql_time_end1_2 = microtime(true);
    $sql_time_start3 = microtime(true);
	if($can_ship && $_REQUEST['act'] == 'submit'){
		$sql_1 = "update romeo.shipment set status= 'SHIPMENT_SHIPPED',LAST_MODIFIED_BY_USER_LOGIN = '{$_SESSION['admin_name']}',LAST_UPDATE_STAMP=NOW(),LAST_UPDATE_TX_STAMP=NOW() where shipment_id in ('%s'); ";
		$sql_2 = "insert into ecshop.ecs_order_action(order_id,order_status,pay_status,shipping_status,action_user,action_time,action_note) values %s ;";
		$sql_3 = "update romeo.pallet set ship_status = 'SHIPPED',shipped_time=now(),shipped_user='{$_SESSION['admin_name']}' where pallet_id = {$bill_nos_info_list[0]['pallet_id']} ";
		$sql_4 = "select count(s2.tracking_number) as c,os.order_id,oi.order_status,oi.pay_status from romeo.shipment s  
					inner join romeo.order_shipment os on os.shipment_id = s.shipment_id 
					inner join ecshop.ecs_order_info oi on oi.order_id = cast(os.order_id as unsigned)
					left join romeo.order_shipment os2 on os2.order_id = os.order_id and os2.shipment_id != os.shipment_id
					left join romeo.shipment s2 on s2.shipment_id = os2.shipment_id and s2.status != 'SHIPMENT_SHIPPED' 
					WHERE s.tracking_number in ('%s') and oi.order_status = 1 and oi.shipping_status != 1
					group by os.order_id 
					having c =0 ";
		$sql_5 = "update ecshop.ecs_order_info 
				 set  shipping_time=unix_timestamp(), shipping_status=1 where shipping_status != 1 and order_id in (%s)";
		$sql_6 = "insert into ecshop.ecs_order_action(order_id,order_status,pay_status,shipping_status,action_user,action_time,action_note) values %s ;";
		//操作发货
		if(!empty($ok_tracking_numbers)){
			$tracking_numbers = implode("','",$ok_tracking_numbers);
		
			$sql = "select os.order_id,order_status,pay_status,shipping_status,s.shipment_id,s.tracking_number 
				 from romeo.shipment s  
				 inner join romeo.order_shipment os on os.shipment_id = s.shipment_id  
				 inner join ecshop.ecs_order_info oi  on oi.order_id = cast(os.order_id as unsigned)  
				 where s.tracking_number in ('{$tracking_numbers}')  ";
			$order_shipments = $db->getAll($sql);
			$shipment_list = array();
			$order_action_list = array();
			$tn_list = array();
			foreach($order_shipments as $order_shipment){
				if(!in_array($order_shipment['shipment_id'],$shipment_list)){
					$shipment_list[] = $order_shipment['shipment_id'];
				}
				$order_action_list[] = "({$order_shipment['order_id']},{$order_shipment['order_status']},{$order_shipment['pay_status']}," .
						"{$order_shipment['shipping_status']},'{$_SESSION['admin_name']}',now(),'运单{$order_shipment['tracking_number']}已交接发货')";
				if(!in_array($order_shipment['tracking_number'],$tn_list)){
					$tn_list[] = $order_shipment['tracking_number'];
				}
			}
		}
		
		$db->start_transaction();
		try{
			$sql_4_s = "";
			if(!empty($ok_tracking_numbers)){
				$db->query(sprintf($sql_1, implode("','",$shipment_list)));
				$db->query(sprintf($sql_2, implode(",",$order_action_list)));
				$sql_4_s = $sql_4_s.implode("','",$tn_list);
			}
			$db->query($sql_3);
			if(!empty($shipped_tracking_numbers)){
				$sql_4_s = $sql_4_s."','".implode("','",$shipped_tracking_numbers);
			}
			$order_ids = $db->getAll(sprintf($sql_4, $sql_4_s));
			if(!empty($order_ids)){
				$order_id_list = array();
				$order_action_list = array();
				foreach($order_ids as $order_id){
					$order_id_list[] = $order_id['order_id'];
					$order_action_list[] = "({$order_id['order_id']},{$order_id['order_status']},{$order_id['pay_status']}," .
							"1,'{$_SESSION['admin_name']}',now(),'码托发货，订单发货')";
				}
				$db->query(sprintf($sql_5, implode(",",$order_id_list)));
				$db->query(sprintf($sql_6, implode(",",$order_action_list)));
			}
			$db->commit();
			$shipped = true;
		}catch(Exception $e){
			$db->rollback();
			$shipped = false;
		}
			
		$smarty->assign('shipped',$shipped);
	}
	$smarty->assign('shipped_tracking_numbers',implode(',',$shipped_tracking_numbers));
	
	$smarty->assign('has_shipped',$has_shipped);
	$smarty->assign('other_pallet_list',$other_pallet_list);
	$smarty->assign('noorder_bill_nos',implode(',',$noorder_bill_nos));
    $smarty->assign('cancel_order_list', implode(',',$cancel_order_list));
    $smarty->assign('other_shipping_list',$other_shipping_list);
	$smarty->assign('noinvoice_orders', $noinvoice_orders);
    $smarty->assign('prepayment_consume_fail', $prepayment_consume_fail);
    
    $smarty->assign('can_ship',$can_ship);
    $smarty->assign('pallet_shipping_name',$bill_nos_info_list[0]['shipping_name']);
    $smarty->assign('ok_tracking_numbers', $ok_tracking_numbers);
    $smarty->assign('ok_num',count($ok_tracking_numbers));
}
$smarty->assign('pallet_no',$pallet_no);
$smarty->display('shipment/pallet_shipment.htm');
