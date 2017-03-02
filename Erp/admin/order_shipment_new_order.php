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


if($_SESSION['party_id'] != 65625 ){
	echo "该页面合并订单功能 仅限中粮业务组使用";
	die( ); 
}
admin_priv('merge_order','order_edit');
admin_priv('zhongliang_merge_order');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php'); 
require_once(ROOT_PATH.'/includes/lib_order.php'); 
require_once(ROOT_PATH.'admin/distribution.inc.php'); 

require_once(ROOT_PATH . 'includes/debug/lib_log.php');


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
    isset($_REQUEST['act']) && in_array($_REQUEST['act'],array('order_shipment_merge','one_person_orders'))
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
        $sql="select oi.order_id, oi.order_sn, oi.order_status,oi.pay_status, oi.shipping_status, oi.consignee, oi.tel,
                      oi.mobile, oi.province, oi.city, oi.district, oi.address, oi.shipping_id, oi.party_id,
                      oi.facility_id, IFNULL(a.attr_value, oi.nick_name) as nick_name , 
                      CONCAT(oi.consignee,'-',IFNULL(oi.tel,oi.mobile)) as person 
                 from ecshop.ecs_order_info oi 
                      left join ecshop.order_attribute a on oi.order_id = a.order_id and a.attr_name = 'TAOBAO_USER_ID'
                where oi.order_id ".db_create_in($_POST['checked']). "order by  person";  
        $nick_names = array();
        $list_by_nick_name = array();      
        $merge_list=$db->getAllRefby($sql,array('person'),$nick_names,$list_by_nick_name);
        if(empty($merge_list)){
            $smarty->assign('message','没有查找到订单');
            break;
        }

        foreach($list_by_nick_name['person'] as $key=>$item) {
        	if(count($item)<2){
	            $message .= "用户ID：".$key." 无法合并订单，必须要超过2个或2个订单以上订单才能操作合并发货<br>";
	            continue;
        	}
        	// 检查要合并发货的订单
	        $primary_order=array_shift($item);
	        $orderIdList[$key][] = $primary_order['order_id'];
	        
            // if($primary_order['order_status']!=0 && $primary_order['order_status']!=1) 
	        if($primary_order['order_status']!=0 ) {
	            $message .= "订单{$primary_order['order_sn']}状态已取消或已拒收。只有【未确认】状态的订单才能合并订单 <br>";
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
            $this_merge_flag = true; 
	        foreach($item as $merge_item){      
                // 检查订单状态 付款状态 发货状态 
                if( $merge_item['order_status'] != $primary_order['order_status'] || 
                    $merge_item['pay_status'] != $primary_order['pay_status'] || 
                    $merge_item['shipping_status'] != $primary_order['shipping_status'] ){
                    $message .= "用户ID:".$primary_order['person']."的订单无法合并，原因： 订单状态 付款状态 发货状态必须相同才能合并订单!<br>";
                    $this_merge_flag = false; 
                    break;
                }

                   	
	            // 检查合并发货的订单是否在一个仓库
	            if($merge_item['facility_id']!=$primary_order['facility_id']){
	                $message .= "用户ID:".$primary_order['person']."的订单无法合并，原因： 要合并发货的订单不在一个仓库!<br>";
	                $this_merge_flag = false;  
                    break;
	            }
	            // 检查所属业务
	            if($merge_item['party_id'] != $primary_order['party_id']){
	                $message .= "用户ID:".$primary_order['person']."的订单无法合并，原因：  要合并发货的订单不属于同一业务!<br>";
	                $this_merge_flag = false; 
                    break;
	            }
	            // 检查合并发货的订单是否是同一个快递方式
	            if($merge_item['shipping_id'] != $primary_order['shipping_id']){
	                $message .= "用户ID:".$primary_order['person']."的订单无法合并，原因： 要合并发货的订单配送方式不一致!<br>";
	                $this_merge_flag = false;  
                    break;
	            }
	            // 检查收货人
	            if($merge_item['consignee'] != $primary_order['consignee']){
	                $message .= "用户ID:".$primary_order['person']."的订单无法合并，原因： 要合并发货的订单的收货人不一样!<br>";
	                $this_merge_flag = false;  
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
	                $message .= "用户ID:".$primary_order['person']."的订单无法合并，原因：要合并发货的订单的收货地址不一样!<br>";
	                $this_merge_flag = false;  
                    break;
	            }
	            // 检查电话
	            if($merge_item['mobile']!=$primary_order['mobile']){
	                $message .= "用户ID:".$primary_order['person']."的订单无法合并，原因： 要合并发货的订单手机号不一样!<br>";
	                $this_merge_flag = false;  
                    break;
	            }
	            // 检查座机
	            if($merge_item['tel'] != $primary_order['tel']){
	                $message .= "用户ID:".$primary_order['person']."的订单无法合并，原因： 要合并发货的订单电话号不一样!<br>";
	                $this_merge_flag = false;  
                    break;
	            }	            
	            $orderIdList[$key][] = $merge_item['order_id'];
	        }
	        // 已经批捡的就无法再合并订单 ljzhou 2013-11-20
			$has_pick_orders = check_order_is_batch_pick($orderIdList[$key]);
			if(!empty($has_pick_orders)){
		    	$message .= "订单：".$has_pick_orders." 已经进入批捡，无法合并订单！";
		        $this_merge_flag = false; 
                continue;
		    }
		    if($this_merge_flag){
    			// $db->start_transaction();
    		    // 执行合并操作
    		    try{
                    // 不创建新订单的合并逻辑  把订单的shipment_id改为相同  
                    // 自己的仓库这样 订单就相当于合并了 
    		        $handle=soap_get_client('ShipmentService'); 
                    $handle->ordersMergeToShipment(array('orderIdList'=>$orderIdList[$key], 'username'=>$_SESSION['admin_name']));
                    // 生成新订单 取消原订单 
    		        $message .= combineOrder($orderIdList[$key]);
    		    }catch(Exception $e){
    		    	// $db->rollback();
    		        $message .= "操作失败：".$e->getMessage();
    		        require_once(ROOT_PATH . 'includes/debug/lib_log.php');
    		        QLog::log("订单（orderId: ".$primary_order['order_id']."）合并失败 " . $e->getMessage(),QLog::ERR);
    		        break;
    		    }

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

// 可以合并发货的订单
//添加了淘宝的用户Id,配送方式,邮费和订单金额     by jrpei 2011-7-4
//  and md.type = '{$distributor_type}'".sql_condition($_REQUEST)." 
/*   and exists
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
*/  
$show_tips = "请点击[查询]按钮，查询需要合并的订单"; 
$time_info = ""; 
// 查询需要合并的订单
if($act == "one_person_orders"){
	$sql1 = "select 
	        count(*) as num,  GROUP_CONCAT(oi.order_id) as order_id_str
	    from 
	        ecshop.ecs_order_info oi force index (order_info_multi_index)
	    where
	        order_status=0 and shipping_status=0 and order_time between '$start_datetime' and '$ended_datetime' and 
	        order_type_id in('SALE','SHIP_ONLY','RMA_EXCHANGE') and ". party_sql('oi.party_id').sql_condition($_REQUEST)."
	    group by 
	        oi.consignee,oi.province,oi.city,oi.district,oi.address,oi.mobile,oi.tel,oi.nick_name
	    having 
	        num > 1";
	$order_ids = $db->getAll($sql1);
	if(!empty($order_ids)){
		$order_ids_arr = array();
		foreach($order_ids as $key=>$value){
			array_push($order_ids_arr,$value['order_id_str']);
		}
		$order_ids_str = str_replace(",","','",implode(",",$order_ids_arr));
		$sql2 = "
		select 
		        o.order_id,o.order_sn,o.taobao_order_sn,o.consignee,o.tel,o.mobile,
		        o.order_time,o.taobao_order_sn, 
		        o.nick_name,o.order_amount,o.shipping_name,o.pay_name,
		        o.shipping_fee,
		        f.facility_name,r1.region_name AS province_name,r2.region_name AS city_name, 
		       	o.nick_name AS TAOBAO_USER_ID, o.address
		from ecshop.ecs_order_info o 
		    INNER JOIN romeo.facility f on f.FACILITY_ID = o.facility_id
			INNER JOIN ecshop.ecs_region r1 on r1.region_id = o.province
			INNER JOIN ecshop.ecs_region r2 on r2.region_id = o.city
			LEFT JOIN ecshop.ecs_region r3 on r3.region_id = o.district
	      where o.order_id in ('{$order_ids_str}') 
	      order by o.address  ";
	           // and not exists (select 1 from romeo.picklist_item where order_id = o.order_id and status != 'PICKITEM_CANCELLED')
		$ref_fileds1=$ref_rowset1=array();
		$start = microtime(true);
		$order_list=$db->getAllRefby($sql2,array('order_id'),$ref_fields1,$ref_rowset1);
		$end = microtime(true);
		$time_info = "sql query time --".($end - $start);
		$format_start = microtime(true); 
  
	    $in = db_create_in($ref_fields1['order_id']); 
		// 从这些订单中剔除已经合并发货的 或者是合并后生成的订单  及参与了合并的订单 
		$sql = "SELECT order_id,root_order_id 
				FROM ecshop.order_relation where parent_order_sn='merge' and (
	            order_id $in OR root_order_id $in 
					)"; 

		$order_relation = $db->getAll($sql); 
		$order_ids = array();
		// 参数了合并的订单 
		if(!empty($order_relation)){
			foreach ($order_relation as $order) {
				$order_ids[$order['order_id']] = 1;
				$order_ids[$order['root_order_id']] = 1; 
			}
		}

		// 老逻辑 shipment_id 相同的订单 
		// 剔除 shipment_id 相同的订单 
		$sql2= "select order_id,shipment_id from romeo.order_shipment where order_id {$in}";
		$shipment_ids = array(); 
		$shipments = $db->getAll($sql2);
		if(!empty($shipments)){
			foreach ($shipments as $key => $shipment) {
				if(!isset($shipment_ids[$shipment['shipment_id']])){
					$shipment_ids[$shipment['shipment_id']] = 1; 
				}else{
					$shipment_ids[$shipment['shipment_id']] += 1; 
				}
			}
		} 
		if(!empty($shipment_ids)){
			foreach ($shipments as $key => $shipment) {
				if($shipment_ids[$shipment['shipment_id']] > 1 ){
					$order_ids[$shipment['order_id']] = 1; 
				}
			}
		}



	    $merge_order_num = array(); // 相同地址的 订单数 
		// 剔除参与了合并的订单 
		if(!empty($order_ids)){
			foreach ($order_list as $key=>$order) {
				if( array_key_exists($order['order_id'],$order_ids)){
					unset($order_list[$key]); 
				}else{
					$index = $order['consignee'].$order['address'].$order['tel'].$order['mobile'];
					if(empty($merge_order_num[$index])){
						$merge_order_num[$index] = 1; 
					}else{
						$merge_order_num[$index] += 1;  
					}
				}
			}
		}

		// 去掉相同地址订单数 小于2 的订单 
		foreach ($order_list as $key => $order) {
			$index = $order['consignee'].$order['address'].$order['tel'].$order['mobile'];
			if( isset($merge_order_num[$index])  && $merge_order_num[$index] < 2){
				unset($order_list[$key]); 
			}
		}
	}else{
		$show_tips = "没有查询到记录"; 
	}
    
    // 判断该订单的库存是否足够 
	if($order_list){
		$inventory_order_ids = array(); 
		foreach ($order_list as $key => $value) {
			$inventory_order_ids[] = $value['order_id']; 
		}
		$not_enough_order_ids = isOrderInventoryNotEnough($inventory_order_ids); 
		foreach ($order_list as $key => $order) {
			if(isset($not_enough_order_ids[$order['order_id']])){
				$order_list[$key]['not_enough'] = 1; 
			}
		}
	}

	$format_end = microtime(true);  
    $time_info .="  format data time ".($format_end - $format_start); 
    $smarty->assign('order_list',$order_list);
} 
$smarty->assign('show_tips',$show_tips);
$smarty->assign('message',$message);
$smarty->assign('filter',$filter);
$smarty->assign('time_info',$time_info); 
$smarty->assign('distributor_type_list',$distributor_type_list);
$smarty->display('order/order_shipment_new_order.htm');



 /*
    判断订单的商品库存是否足够
    返回  库存不足会出现在返回的 order_ids中 
 */
function isOrderInventoryNotEnough($order_ids){
	global $db; 
	$sql = " SELECT eoi1.order_id from ecshop.ecs_order_info eoi1
			left join ecshop.ecs_order_goods as og ON eoi1.order_id = og.order_id
			left join romeo.product_mapping as pm ON pm.ecs_goods_id = og.goods_id 
				and pm.ecs_style_id = og.style_id
			left join romeo.inventory_summary as ris ON pm.product_id = ris.product_id 
				and eoi1.facility_id = ris.facility_id and og.status_id = ris.status_id
			LEFT JOIN ecshop.ecs_goods_inventory_reserved gir on gir.goods_id = og.goods_id 
				and gir.style_id = og.style_id and gir.facility_id = eoi1.FACILITY_ID 
				and gir.party_id = eoi1.party_id
		    where eoi1.order_id ".db_create_in($order_ids)." and 
		    	(ris.AVAILABLE_TO_RESERVED is null or ris.AVAILABLE_TO_RESERVED-if(gir.reserve_number is NULL or gir.status != 'OK',0,gir.reserve_number) < og.goods_number) 
			  ";						
    $not_enough = $db->getAll($sql);
    $not_enough_order_ids = array();
    if($not_enough){
    	foreach ($not_enough as $key => $value) {
    		$not_enough_order_ids[$value['order_id']] = 1; 
    	}
    }
    return $not_enough_order_ids; 
}



function sql_condition($arg) {
	$sql_condition = "";
	if(!empty($arg['nick_name'])) {
		$nick_name = mysql_escape_string($arg['nick_name']); 
		$sql_condition = " and oi.nick_name = '{$nick_name}' ";
	}	
	return $sql_condition;
}

/*
  把多个订单合并为一个订单 前期已进行了各种检查 
 */
function combineOrder($orderIds){
	global $db;
	$sql = "SELECT oi.* FROM ecshop.ecs_order_info oi WHERE oi.order_id ".db_create_in($orderIds);
	$order_list = $db->getAll($sql); 
	$order_info = $order_list[0]; 
	unset($order_info['order_id']);
	unset($order_info['order_sn']); 
    unset($order_info['taobao_order_sn']);
    $pay_status = $order_info['pay_status']; 
    $postscript = ''; // 用户备注 
    $sum_fiedls = array(
    	"bonus"=>0, // 订单总红包  
    	"goods_amount"=> 0 ,
    	"order_amount"=> 0 ,
    	"shipping_fee" => 0 , 
    	"shipping_proxy_fee" => 0 , 
    	"insure_fee" => 0 ,
    	"pay_fee" => 0 , 
    	"card_fee" => 0 , 
    	"money_paid"=> 0 , 
    	"surplus"=> 0 , 
    	"integral"=> 0 , 
    	"integral_money" => 0 
    	); 
    $taobao_order_sn = array(); 
    foreach ($order_list as $order) {
    	if(trim($postscript) != $order['postscript']){
    		$postscript .= $order['postscript']." "; 
    	}
    	if(!empty($order['taobao_order_sn'])){
    		$taobao_order_sn[] = $order['taobao_order_sn'];
    	}
    	foreach ($sum_fiedls as $key => $value) {
    	 	 if(is_numeric($order[$key])){
    	 	 	$sum_fiedls[$key] += $order[$key]; 
    	 	 }
    	} 
    }
   
   /*
    if(count($taobao_order_sn) == count($order_list)){
        $taobao_order_sn = implode(",",$taobao_order_sn);
    }else{
        $taobao_order_sn = '';  
    }
    */
    $order_info['taobao_order_sn'] = ''; 
    if(!empty($taobao_order_sn)){
    	$order_info['taobao_order_sn'] = $taobao_order_sn[0]; 
    }

    $order_info['postscript'] = $postscript; 
 
    // 订单级别需要求和的字段 
    foreach ($sum_fiedls as $key => $value) {
    	$order_info[$key] = $value; 
    }
   
    // 订单级别红包 
    $sql = "SELECT attr_value FROM ecshop.order_attribute where attr_name='DISCOUNT_FEE' AND order_id ".db_create_in($orderIds); 
    $order_discount_fee = $db->getCol($sql);

    
    $discount_fee = 0; 
    foreach ($order_discount_fee as $key => $value) {
    	if(is_numeric($value)){
    		$discount_fee += floatval($value);
    	}
    }
    $order_info['discount_fee'] = $discount_fee;  
    // 配送面单记录 
    $carrier_id = ''; 
    // if(isset($order_info['carrier_bill_id'])){
    	// $sql = "SELECT carrier_id from ecshop.ecs_carrier_bill WHERE bill_id = '{$order_info['carrier_bill_id']}' "; 
    	$sql="SELECT
			es.default_carrier_id
		FROM
			ecshop.ecs_shipping es
		WHERE
			es.shipping_id = ".$order_info['shipping_id'];
    	$carrier_id = $db->getOne($sql); 
    // }
 
    $order_type = $order_info['order_type']; 

   
    // 订单商品信息 
    $sql = "SELECT og.*,og.goods_number  as all_goods_number 
    	   from ecshop.ecs_order_goods  og where og.order_id ".db_create_in($orderIds);
    $order_goods = $db->getAll($sql); 
    $order_goods_ids = array( );  
    $order_goods_info = array(); 
    foreach ($order_goods as $goods) {
     	$goods['goods_number'] = $goods['all_goods_number'];
     	unset($goods['all_goods_number']); 
     	$order_goods_ids[] = $goods['rec_id'];
     	$order_goods_id = $goods['rec_id']; 
     	if(isset($goods['goods_price'])){
     		$goods['price'] = $goods['goods_price']; 
     	}
     	unset($goods['rec_id']); 
     	$order_goods_info[$order_goods_id] = $goods; 
    }
    unset($order_goods); 

    // 商品级别红包 
    $sql = "SELECT order_goods_id,value FROM ecshop.order_goods_attribute WHERE  name='DISCOUNT_FEE'  AND order_goods_id ".db_create_in($order_goods_ids);  
    $order_goods_discount = $db->getAll($sql); 
    foreach ($order_goods_discount as $goods) {
    	$order_goods_id = $goods['order_goods_id'];
    	if(is_numeric($goods['value'])){
    		$order_goods_info[$order_goods_id]['discount_fee'] = $goods['value'];
    	}
    }
    
    $message = ""; 
	//准备好order和order_goods信息之后，准备生成订单
    $osn = distribution_generate_sale_order($order_info, $order_goods_info,$carrier_id, $order_type,$message);
    $msg = '创建新订单失败'; 
    if(isset($osn['order_id'])){
        if (!function_exists('add_order_attribute')) {
            include_once("admin/includes/lib_order.php");   
        }
        $order_id = $osn['order_id'];
        $order_sn = $osn['order_sn']; 
        $db->start_transaction(); 
        try {
            // 这时order_shipment表中还没有记录 把这个新订单的shipment_id 改为和原来订单的相同有点不太合适 
           /* $sql = "SELECT shipment_id FROM romeo.order_shipment where order_id = '{$orderIds[0]}' ";
            $shipment_id = $db->getOne($sql);
            $sql = "DELETE FROM romeo.order_shipment where order_id = '{$order_id}' "; 
            $db->query($sql);  
            $sql = "INSERT INTO  romeo.order_shipment(shipment_id,order_id) VALUES ('{$shipment_id}','{$order_id}') ";
            $db->query($sql); 
           */ 

            // 取消原来的订单们 付款状态改为 未付款 
            $db->query("update ecshop.ecs_order_info set order_status = '2' ,pay_status = '0'  where order_id ".db_create_in($orderIds)); 

            // 修改新订单的付款状态为原订单的付款状态
            $db->query("update ecshop.ecs_order_info set pay_status = {$pay_status} where order_id ='{$order_id}' " ); 
            foreach ($orderIds as $key => $value) {

                $tmp_order_sn = get_order_sns($value); 

                 // 添加合并订单关系 
                 // 首先删除合并订单关系 order_id , parent_order_id 为空, root_order_id   
                $sql_relation_del = "delete from ecshop.order_relation where root_order_id = '{$value}' and parent_order_sn ='merge'"; 
                $db->query($sql_relation_del); 
                $sql_relation = "insert into ecshop.order_relation values 
                        (null,{$order_id},'{$order_sn}','123','merge',$value,'{$tmp_order_sn[0]}')";  
                $db->query($sql_relation); 
                // 订单操作记录 
                $action['order_id']        = $value;
                $action['order_status']    = 2;
                $action['action_time']     = date("Y-m-d H:i:s");
                $action['action_note']     = "创建新订单合并订单 ".json_encode($orderIds)." 合并为 ".$order_id;
                $action['action_user']     = $_SESSION['admin_name'];
                $db->autoExecute("ecshop.ecs_order_action", $action);
            }

            // 新订单操作记录 
            $action['order_id']        = $order_id;
            $action['order_status']    = 0;
            $action['action_time']     = date("Y-m-d H:i:s");
            $action['action_note']     = " 订单由 ".json_encode($orderIds)." 合并生成 ";
            $action['action_user']     = $_SESSION['admin_name'];
            $db->autoExecute("ecshop.ecs_order_action", $action);

            // 订单级别属性 
            $order_level_attr  = array(); 
            $sql = "SELECT attr_name,attr_value FROM ecshop.order_attribute where attr_name!='DISCOUNT_FEE' AND order_id ".db_create_in($orderIds); 
            $order_attr = $db->getAll($sql); 

            foreach ($order_attr as $attr) {

            	$attr_name = $attr["attr_name"];
            	$attr_value = $attr["attr_value"];
            	// 是数值型 相加 
            	if(is_numeric($attr_value)){
                    if(isset($order_level_attr[$attr_name])){
                    	$order_level_attr[$attr_name]+=$attr_value; 
                    }else{
                    	$order_level_attr[$attr_name] = $attr_value; 
                    }
            	}else{
            		if(isset($order_level_attr[$attr_name])){
            			if(trim($order_level_attr[$attr_name]) != trim($attr_value)){
            				$order_level_attr[$attr_name] .= $attr_value."";
            			}
            		}else{
            			$order_level_attr[$attr_name] = $attr_value;
            		} 
            	}
            }
            foreach ($order_level_attr as $attr_name => $attr_value) {
            	 add_order_attribute($order_id,$attr_name, $attr_value);
            }

            $db->commit(); 
            $msg = "订单(order_id) " .json_encode($orderIds)." 合并为 ".$osn['order_id']; 
        } catch (Exception $e) {
            $db->rollBack(); 
            // 如果新订单的订单关系 及 原订单取消 失败 则取消该订单 相当于老的合并订单逻辑 
            $sql = " UPDATE ecshop.ecs_order_info set order_status = 2 where order_id = '{$order_id}'"; 
            $db->query($sql);  
        }   
    }
    QLog::log("订单 " .json_encode($orderIds)." 合并为 ".json_encode($osn)); 
    return $msg; 
}