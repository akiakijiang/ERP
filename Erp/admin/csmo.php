<?php

/**
 * 客服订单列表
 */

define('IN_ECS', true);

require('includes/init.php');

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

$_REQUEST['act'] = $_REQUEST['act'] ? $_REQUEST['act'] : 'list';
$csv = $_REQUEST['csv'];
$action_user = $_SESSION['admin_name'];
$action_time = date("Y-m-d H:i:s");

include_once 'config.vars.php';
include_once 'function.php';
include_once 'search_order.php';
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
/*------------------------------------------------------ */
//-- 支付方式列表 ?act=list
/*------------------------------------------------------ */

admin_priv('customer_service_manage_order','kf_order_search');

// Since 2015 Nov 23 by Sinri Edogawa 


if ($_REQUEST['act'] == 'list')
{
    $other = " info.order_type_id IN ('SALE', 'RMA_EXCHANGE', 'SHIP_ONLY') ";
     
    if(isset($_REQUEST ['startCalendar'])){
    	$start_order_time = sqlSafe ( $_REQUEST ['startCalendar'] );
    }else{
    	$start_order_time = date("Y-m-d H:i:s",strtotime("-7 days",time()));
    }    
    $other .= " AND info.order_time >= '{$start_order_time}' "; 
	
    $sizeof = false;
    $dc_type = trim($_GET['type']);
    
    $dc_types = explode(",", $dc_type);
    if ($dc_type && !in_array($dc_type, array('search', 'task'))) {
        $types = explode("_", $dc_types[1]);
        $types[1] = (int)$types[1];
        $order_status = 1;
        $invoice_status = 1;
        $shipping_status = 1;
		if(in_array($_SESSION['party_id'],array('32640','65535'))){
			$init_32640 = " 1=0 ";
		}else{
			$init_32640 = 1;
			$other .=" AND ". party_sql('info.party_id');
		}
        switch ($dc_types[0]) {
            case 'h':
                // 发货情况
                switch ($types[1]) {
                    case 1:
                        // 已发货
                        $order_status = 'order_status = 1';
                        $invoice_status = 'invoice_status = 3';
                        $shipping_status = 'shipping_status = 1';

                        break;
                    case 2:
                        // 已收货
                        $order_status = 'order_status = 1';
                        $invoice_status = 'invoice_status = 3';
                        $shipping_status = 'shipping_status = 2';

                        break;
                }
                $sqlc = "SELECT count(1) as cc FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index)  WHERE $other AND $order_status AND $invoice_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT * FROM{$ecs->table('order_info')} AS info WHERE $other AND $order_status AND $invoice_status AND $shipping_status ORDER BY order_id DESC ".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'o':
                // 订单已取消
                switch ($types[1]) {
                    case 2:
                        // 已取消
                        $order_status = 'order_status = 2';

                        break;
                }
                $sqlc = "SELECT count(1) as cc FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index)  WHERE $other AND $order_status ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT * FROM{$ecs->table('order_info')} AS info WHERE $other AND $order_status ORDER BY order_id DESC ".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'i':
                // 发票情况
                switch ($types[1]) {
                    case 1:
                        // 发票待修改
                        $invoice_status = 'invoice_status = 1';
                        $shipping_status = 'shipping_status in (0, 4)';

                        break;
                    case 2:
                        // 发票已修改
                        $invoice_status = 'invoice_status = 2';
                        $shipping_status = 'shipping_status in (0, 4)';

                        break;
                }
                $sqlc = "SELECT count(1) as cc FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index)  WHERE $other AND $invoice_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT * FROM {$ecs->table('order_info')} AS info WHERE $other AND $invoice_status AND $shipping_status ORDER BY order_id DESC ".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'f':
                // 发票情况 f
                switch ($types[1]) {
                    case 0:
                        // 发票待修改
                        //						$order_status = 'order_status != 2';
                        //						$invoice_status = 'invoice_status < 3';
                        //						$shipping_status = 'shipping_status in (0, 4)';
                        $order_status = "order_status = 0";
                        break;
                }
                $sqlc = "SELECT count(1) as cc 
					FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index) 
					LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id
					WHERE $other AND $order_status AND $invoice_status AND $shipping_status AND (pay_code = 'cod' OR pay_status in (1, 2)) ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT *
					FROM {$ecs->table('order_info')} AS info 
					LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id
					LEFT JOIN {$ecs->table('shipping')} s ON info.shipping_id = s.shipping_id
					WHERE $other AND $order_status AND $invoice_status AND $shipping_status AND (pay_code = 'cod' OR pay_status in (1, 2)) 
				".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'paid_unconfirmed':
                // 先款后货未确认订单
                $order_status = " order_status = 0 ";
                $pay_status = " (pay_code != 'cod' AND pay_status = 2) ";
                $shipping_status = " shipping_status = 0 ";
                $distributor_type = " (md.type != 'fenxiao' OR md.type IS NULL) ";
                $sqlc = "SELECT count(1) as cc 
					FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index) 
					LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id 
					LEFT JOIN ecshop.distributor d ON d.distributor_id = info.distributor_id
                    LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
					WHERE $other AND $order_status AND $distributor_type AND $pay_status AND  $shipping_status AND $init_32640 ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT  info.order_id,info.order_sn,info.consignee,info.taobao_order_sn,f.facility_name,
					info.order_time,info.confirm_time,info.reserved_time,info.shipping_time,
        			info.order_status,info.shipping_status,info.pay_status,info.tel,info.mobile,info.pay_id,p.pay_name,os.shipment_id,s.shipping_name 
           			FROM {$ecs->table('order_info')} info force index(order_time,order_info_multi_index) 
        			LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id
        			LEFT JOIN {$ecs->table('shipping')} s ON info.shipping_id = s.shipping_id
        			LEFT JOIN ecshop.distributor d ON d.distributor_id = info.distributor_id
                    LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        			LEFT JOIN romeo.facility f on info.FACILITY_ID = f.FACILITY_ID
        			LEFT JOIN romeo.order_shipment os on convert(info.ORDER_ID using utf8) = os.ORDER_ID 
        			WHERE $other AND $order_status AND $distributor_type  AND $pay_status  AND $shipping_status AND $init_32640
				".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'hand_order_search': 
                // 手工录单 订单查询
                $smarty->assign('my_type', 'hand_order_search'); 
                $sqlc = "SELECT count(1) as cc 
                    FROM  ecshop.order_attribute oa1 inner join ecshop.ecs_order_info info on oa1.order_id = info.order_id
                    WHERE oa1.attr_name = 'ORDER_BY_HAND' AND  $other AND $init_32640 ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT  info.order_id,info.order_sn,info.consignee,info.taobao_order_sn,f.facility_name,
                    info.order_time,info.confirm_time,info.reserved_time,info.shipping_time,
                    info.order_status,info.shipping_status,info.pay_status,info.tel,info.mobile,info.pay_id,p.pay_name,os.shipment_id,s.shipping_name 
                   FROM  ecshop.order_attribute oa1 inner join ecshop.ecs_order_info info on oa1.order_id = info.order_id  
                    LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id
                    LEFT JOIN {$ecs->table('shipping')} s ON info.shipping_id = s.shipping_id
                    LEFT JOIN romeo.facility f on info.FACILITY_ID = f.FACILITY_ID
                    LEFT JOIN romeo.order_shipment os on convert(info.ORDER_ID using utf8) = os.ORDER_ID 
                    WHERE oa1.attr_name = 'ORDER_BY_HAND'  AND $other  AND $init_32640
                ".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'cod_unconfirmed':
                // 货到付款未确认的订单
                $order_status = " order_status = 0 ";
                $pay_status = " pay_code = 'cod' ";
                $shipping_status = " shipping_status = 0 ";
                $distributor_type = " (md.type != 'fenxiao' OR md.type IS NULL)";
                $sqlc = "SELECT count(1) as cc 
					FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index) 
					LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id
					LEFT JOIN ecshop.distributor d ON d.distributor_id = info.distributor_id
                    LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        			WHERE $other AND $order_status AND $distributor_type AND $pay_status AND  $shipping_status AND $init_32640 ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT info.order_id,info.order_sn,info.consignee, info.taobao_order_sn,f.facility_name,
					info.order_time,info.confirm_time,info.reserved_time,info.shipping_time,
					info.order_status,info.shipping_status,info.pay_status,info.tel,info.mobile,info.pay_id,p.pay_name,os.shipment_id,s.shipping_name 
					FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index) 
					LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id
					LEFT JOIN {$ecs->table('shipping')} s ON info.shipping_id = s.shipping_id
        			LEFT JOIN ecshop.distributor d ON d.distributor_id = info.distributor_id
                    LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        			LEFT JOIN romeo.facility f on info.FACILITY_ID = f.FACILITY_ID 
        			LEFT JOIN romeo.order_shipment os on convert(info.ORDER_ID using utf8) = os.ORDER_ID 
					WHERE $other AND $order_status AND $distributor_type AND $pay_status AND $shipping_status AND $init_32640
				".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'b':
                // 补货情况
                switch ($types[1]) {
                    case 15:
                        // 补货待审核
                        $order_status = 'order_status = 8';
                        $shipping_status = 'shipping_status = 1';
                        $invoice_status = 'invoice_status = 3';

                        break;
                    case 16:
                        // 补货已审核
                        $order_status = 'order_status = 7';
                        $shipping_status = 'shipping_status = 1';
                        $invoice_status = 'invoice_status = 3';

                        break;
                    case 17:
                        // 补货已批准
                        $order_status = 'order_status = 7';
                        $shipping_status = 'shipping_status = 1';
                        $invoice_status = 'invoice_status = 3';

                        break;
                    case 18:
                        // 补货已发
                        $order_status = 'order_status = 8';
                        $shipping_status = 'shipping_status = 1';
                        $invoice_status = 'invoice_status = 3';

                        break;
                    case 19:
                        // 补货已收到
                        $order_status = 'order_status = 1';
                        $shipping_status = 'shipping_status = 2';
                        $invoice_status = 'invoice_status = 3';

                        break;
                }
                $sizeof = true;
                $sqlc = "SELECT count(1) as cc FROM{$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index)  
                    LEFT JOIN {$ecs->table('order_goods')} AS b ON info.order_id = b.order_id 
                    WHERE b.goods_status = '$types[1]' AND $other AND $order_status AND $invoice_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT * FROM{$ecs->table('order_info')} AS info 
                    LEFT JOIN {$ecs->table('order_goods')} AS b ON info.order_id = b.order_id 
                    WHERE b.goods_status = '$types[1]' AND $other AND $order_status AND $invoice_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'w':
                // 无货
                switch ($types[1]) {
                    case 21:
                        // 无货
                        $order_status = 'order_status = 6';
                        $shipping_status = 'shipping_status in (0, 4)';

                        break;
                    case 22:
                        // 等待
                        $order_status = 'order_status = 6';
                        $shipping_status = 'shipping_status in (0, 4)';

                        break;
                    case 23:
                        // 到货
                        $order_status = 'order_status = 6';
                        $shipping_status = 'shipping_status in (0, 4)';

                        break;
                    case 24:
                        // 断货
                        $order_status = 'order_status = 6';
                        $shipping_status = 'shipping_status in (0, 4)';

                        break;
                }
                $sizeof = true;
                $sqlc = "SELECT count(1) as cc FROM{$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index)  LEFT JOIN {$ecs->table('order_goods')} AS b ON info.order_id = b.order_id WHERE b.goods_status = '$types[1]' AND $other AND $order_status AND $invoice_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT * FROM{$ecs->table('order_info')} AS info LEFT JOIN {$ecs->table('order_goods')} AS b ON info.order_id = b.order_id WHERE b.goods_status = '$types[1]' AND $other AND $order_status AND $invoice_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'j':
                //拒收
                switch ($types[1]) {
                    case 1:
                        // 拒收待确认
                        $order_status = 'order_status = 1';
                        $shipping_status = 'shipping_status = 3';
                        break;
                }
                $sqlc = "SELECT count(1) as cc FROM{$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index)  LEFT JOIN {$ecs->table('order_goods')} AS b ON info.order_id = b.order_id WHERE $other AND $order_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT * FROM{$ecs->table('order_info')} AS info LEFT JOIN {$ecs->table('order_goods')} AS b ON info.order_id = b.order_id WHERE $other AND $order_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                break;
            case 'presell_orders':
                // 支付了，但未确认的订单
                $order_status = " special_type_id  = 'PRESELL' ";

                $sqlc = "SELECT count(1) as cc 
					FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index) 
					LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id
					WHERE $other AND $order_status AND $invoice_status AND $shipping_status ".' -- csmo '.__LINE__.PHP_EOL;
                $sqla = "SELECT *
					FROM{$ecs->table('order_info')} AS info 
					LEFT JOIN {$ecs->table('payment')} p ON info.pay_id = p.pay_id
					LEFT JOIN {$ecs->table('shipping')} s ON info.shipping_id = s.shipping_id
					WHERE $other AND $order_status AND $invoice_status AND $shipping_status  
				".' -- csmo '.__LINE__.PHP_EOL;
                break;
        }
    } elseif($dc_type == 'search') {
    	$search_tracking_number = trim($_REQUEST['tracking_number']);
        $order_type = $_REQUEST['order_type'];
    	if(!in_array($_SESSION['party_id'],array('32640','65535'))){
	        $other2 = " info.order_type_id IN ('SALE', 'RMA_EXCHANGE','SHIP_ONLY') AND ". party_sql('info.party_id');
    	}else{
    		$other2 = " info.order_type_id IN ('SALE', 'RMA_EXCHANGE','SHIP_ONLY') ";
    	}
	    /*
	     * 以快递单号搜索替换原有运单号搜索
		*/

        //ADDED BY SINRI TO CHECK CONDITION
        
        $user_name = trim($_REQUEST['user_name']);
        $tel_mobile = trim($_REQUEST['tel_mobile']);
        $consignee = trim($_REQUEST['consignee']);
        $tracking_number = trim($_REQUEST['tracking_number']);

        if ($user_nam != '' || $tel_mobile != '' || $consignee != '' || $tracking_number != '' ){
            $is_condition_use_shipment=true;
        }

        
        $sql_sinri_b=" LEFT JOIN romeo.order_shipment os on convert(info.ORDER_ID using utf8) = os.ORDER_ID LEFT JOIN romeo.shipment sp on os.shipment_id = sp.shipment_id ";

        
        /**
         * 预定失败方式搜索
         */
        $order_status = $_REQUEST['order_status'];
        $sql_reserved_status ='';
        if($order_status == 12) {
        	$sql_reserved_status = ' LEFT JOIN romeo.order_inv_reserved r ON info.order_id = r.order_id ';
        }
        
        /**
         * 以平台方式搜索
         */
        
        $is_condition_use_platform_select = false;
        $sql_platform_select = '';
        $platform_select = trim($_REQUEST['platform_select']);
		if($platform_select != '' ) {
			$is_condition_use_platform_select = true;
		}
		if($is_condition_use_platform_select) {
			$sql_platform_select = " LEFT JOIN ecshop.order_attribute oa ON info.order_id = oa.order_id ";
		}
        
		/**
		 * 以店铺类型搜索
		 */
		$shop_type = $_REQUEST['shop_type'];
		$sql_shoptype_table = '';
		if($shop_type != ''){
		    $sql_shoptype_table = " LEFT JOIN ecshop.distributor d ON d.distributor_id = info.distributor_id 
		        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id";
		}
		
		/**
		 * 以商品名、数量方式搜索
		 */
		$is_condition_use_goods_table = false;
		$sql_goods_table = '';
		$goods_id = trim($_REQUEST['goods_id']);
		$goods_name = trim($_REQUEST['goods_name']);
		$goods_number = trim($_REQUEST['goods_number']);
		if(($goods_name != '' && goods_id) || $goods_number) {
			$is_condition_use_goods_table = true;
		}
		if($is_condition_use_goods_table) {
			$sql_goods_table = " LEFT JOIN {$ecs->table('order_goods')} eog ON info.order_id = eog.order_id ";
		}
		$erp_search_text = trim ( $_REQUEST ['erp_order_sn'] );
		$taobao_search_text = trim ( $_REQUEST ['taobao_order_sn'] );
        if(!empty($erp_search_text)){
            $sql_by_hand_table  ='FROM ecshop.ecs_order_info info use index(order_sn) ';
        }elseif(!empty($taobao_search_text) && strlen($taobao_search_text)>4){//sinri: 没有至少5位的TBSN还好意思调用索引？
            $sql_by_hand_table  ='FROM ecshop.ecs_order_info info use index(taobao_order_sn) ';
        }else if(!empty($user_name)){ // index too long  not use any more let mysql decide
            $sql_by_hand_table  ='FROM ecshop.ecs_order_info info ';//.' use index(nick_name) ';
        }elseif(!empty($tel_mobile) && empty($erp_search_text) && empty($taobao_search_text) && empty($user_name) && empty($consignee) &&empty($tracking_number)){
            $sql_by_hand_table = 'FROM ecshop.ecs_order_info info ';
            $sql_tel_table = 'FROM ecshop.ecs_order_info info force index(order_info_multi_index) ';
        }elseif(!empty($tracking_number) && empty($erp_search_text) && empty($taobao_search_text)){
            $sql_by_hand_table = 'FROM ecshop.ecs_order_info info ';
        }else{
            $sql_by_hand_table  ='FROM ecshop.ecs_order_info info force index(order_info_multi_index) ';
        }
        
        $sql_by_hand_where = '';

        if( trim($_REQUEST['my_type']) =='hand_order_search'){
            $smarty->assign("my_type",'hand_order_search'); 
            $sql_by_hand_table = 'FROM ecshop.order_attribute oa1 inner join ecshop.ecs_order_info info on oa1.order_id = info.order_id '; 
            $sql_by_hand_where = " oa1.attr_name = 'ORDER_BY_HAND' AND"; 
        }
        $condition = get_searchorder();
        //面单号、电话号码  先查找出order_id 再通过order_id查找相关信息
        if(!empty($tracking_number) && empty($erp_search_text) && empty($taobao_search_text)){
            $sql_trackingNum = "select order_id from romeo.shipment s use index(tracking_number)" .
    				" left join romeo.order_shipment os on s.shipment_id = os.shipment_id " .
    				" where s.tracking_number = '{$search_tracking_number}'";
            $res = $GLOBALS['db']->getAll($sql_trackingNum);
            $sql_orderId = " AND info.order_id = '{$res[0]['order_id']}' ";
        }elseif(!empty($tel_mobile) && empty($erp_search_text) && empty($taobao_search_text) && empty($user_name) && empty($consignee) && empty($tracking_number)){
            $date = strtotime('-3 Months');
            $date = date('Y-m-d',$date);
            $sql_telMobile = "SELECT  info.order_id
	                       {$sql_tel_table}
	                       WHERE $other2 AND info.order_time > '{$date}' {$condition} AND (info.tel LIKE '{$tel_mobile}%' OR info.mobile LIKE '{$tel_mobile}%' )";
            $res_tel = $GLOBALS['db']->getAll($sql_telMobile);
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
	    $date =date ( "Y-m-d H:i:s", strtotime('-3 Months',time()));
		$sqlc = "SELECT count(1) as cc 
        	{$sql_by_hand_table}
        	-- {$sql_sinri_b}
        	{$sql_platform_select}
        	{$sql_goods_table}
        	{$sql_reserved_status}
        	{$sql_shoptype_table}
        	WHERE $sql_by_hand_where info.order_time > '{$date}' 
            -- AND sp.SHIPPING_CATEGORY = 'SHIPPING_SEND' 
            AND $other2 $sql_orderId 
        ".' -- csmo '.__LINE__.PHP_EOL;
		$sqla = "SELECT  dist.name as distri_name,info.order_id,info.order_sn,info.consignee,info.taobao_order_sn,f.facility_name,
					info.order_time,info.confirm_time,info.reserved_time,info.shipping_time,
        			info.order_status,info.shipping_status,info.pay_status,info.tel,info.mobile,info.pay_id,info.pay_name,s.shipping_name,
         --            os.shipment_id,
		 		    -- IFNULL(sp.tracking_number,'') as tracking_number,
         --            sp.shipping_leqee_weight,
                    info.shipping_status as shipping_data_status
            {$sql_by_hand_table}
        	LEFT JOIN romeo.facility f on info.FACILITY_ID = f.FACILITY_ID
        	inner join ecshop.distributor dist on dist.distributor_id = info.distributor_id
        	LEFT JOIN {$ecs->table('shipping')} s ON info.shipping_id = s.shipping_id
        	-- {$sql_sinri_b}
        	{$sql_platform_select}
        	{$sql_goods_table}
        	{$sql_reserved_status}
        	{$sql_shoptype_table}
        	WHERE $sql_by_hand_where info.order_time > '{$date}' 
            -- AND sp.SHIPPING_CATEGORY = 'SHIPPING_SEND' 
            AND $other2 $sql_orderId
		".' -- csmo '.__LINE__.PHP_EOL;
		$sqladd = get_searchorder();
        //var_dump($sqladd);

        $sqlc .= $sqladd;
        $sqla .= $sqladd;
        $sqla .= " ORDER BY info.order_id DESC ";//就为了这句，速度慢了100倍
       // require_once (ROOT_PATH . 'includes/debug/lib_log.php');
        //Qlog::log($sql_telMobile);
        //QLOg::log("csmo_search_sqlc:".$sqlc);
        // QLOg::log("csmo_search_sqla:".$sqla);
    }
    if (!$dc_type || $dc_type == 'task') { // this part would be called if no parameter of type added. by Sinri
        // 待处理订单
        $tasks = array();$block_the_tasks=true; if(!$block_the_tasks){
        // 发票待修改
        $sqlc = "SELECT count(1) as cc FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index) 	WHERE $other and invoice_status = 1 AND shipping_status in (0, 4) ".' -- csmo '.__LINE__.PHP_EOL;
        $res = $db->query($sqlc);
        $row = $db->fetchRow($res);
        $total = intval($row['cc']);
        $tasks[] = array('total'=>$total, 'item'=>'发票待修改', 'type' => 'i,i_1');

        $sqlc = "SELECT count(1) as cc 
			FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index) 
			LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = info.pay_id
			WHERE $other and order_status = 0  AND (pay_code = 'cod' OR pay_status in (1, 2))
		".' -- csmo '.__LINE__.PHP_EOL;
        $row = $db->getRow($sqlc);
        $total = intval($row['cc']);
        $tasks[] = array('total'=>$total, 'item'=>'发票待确认', 'type' => 'f,f_0');


        // 拒收待确认
        $sqlc = "SELECT count(*) as cc FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index)  WHERE $other and order_status = 1 AND shipping_status = 3 ".' -- csmo '.__LINE__.PHP_EOL;
        $total = $db->getOne($sqlc);
        $tasks[] = array('total'=>$total, 'item'=>'拒收待确认', 'type' => 'j,j_1');

        // 无货
        $sqlc = "SELECT count(*) as cc FROM {$ecs->table('order_info')} AS info force index(order_time,order_info_multi_index) 	LEFT JOIN {$ecs->table('order_goods')} b ON info.order_id = b.order_id WHERE $other and order_status = 6 AND shipping_status in(0, 4) AND b.goods_status = '21' GROUP BY info.order_id ".' -- csmo '.__LINE__.PHP_EOL;
        $row = $db->getAll($sqlc);
        $total = sizeof($row);
        $tasks[] = array('total'=>$total, 'item'=>'无货', 'type' => 'w,g_21');

        // 到货
        $sqlc = "SELECT count(*) as cc FROM {$ecs->table('order_info')} AS info	force index(order_time,order_info_multi_index)  LEFT JOIN {$ecs->table('order_goods')} b ON info.order_id = b.order_id WHERE $other and order_status = 6 AND shipping_status in (0, 4) AND b.goods_status = '23' GROUP BY info.order_id ".' -- csmo '.__LINE__.PHP_EOL;
        $row = $db->getAll($sqlc);
        $total = sizeof($row);
        $tasks[] = array('total'=>$total, 'item'=>'无货->到货', 'type' => 'w,g_23');

        // 断货
        $sqlc = "SELECT count(*) as cc FROM {$ecs->table('order_info')} AS info	force index(order_time,order_info_multi_index)  LEFT JOIN {$ecs->table('order_goods')} b ON info.order_id = b.order_id
			WHERE $other and order_status = 6 AND shipping_status = 0 AND b.goods_status = '24' GROUP BY info.order_id ".' -- csmo '.__LINE__.PHP_EOL;
        $row = $db->getAll($sqlc);
        $total = sizeof($row);
        $tasks[] = array('total'=>$total, 'item'=>'无货->断货', 'type' => 'w,g_24');

        // 待确认款项(先款后货)
        $sqlc = "SELECT count(*) as cc FROM " . $ecs->table('order_info') . " FORCE INDEX ( party_id) 
			WHERE order_status in(0, 1) AND pay_status = 0 AND shipping_status in(0, 4) AND pay_id != 1 AND ". party_sql('party_id').' -- csmo '.__LINE__.PHP_EOL;
        $res = $db->query($sqlc);
        $row = $db->fetchRow($res);
        $total = intval($row['cc']);
        $tasks[] = array('total'=>$total, 'item'=>'先款后货', 'type' => 'dq,s_0', 'url' => 'financial_manage.php?type=dq,s_0');
        }
        $smarty->assign('tasks', $tasks);

    } else {
        $offset = 30;
        $page = intval($_GET['page']);
        $page = max(1, $page);
        $from = ($page-1)*$offset;
        $order_list = array();
        if ($csv == null){
            #$sql = "SELECT * ".strstr($sql, "FROM ");
            $limit = " LIMIT $offset OFFSET $from ";
            $sqla .= $limit;
            $payments = getPayments();
        }
        $order_list = $db->getAll($sqla);
        $count = count($order_list);
    if ($sizeof) {
            $row = $db->getAll($sqlc);
            $total = sizeof($row);
        } else {
            if($count < 30){
                $total = $count;
            }else{
                $res = $db->query($sqlc);
                $row = $db->fetchRow($res);
                $total = $row['cc'];
            }
        }

        foreach ($order_list as $k => $v) {
            if($dc_type == 'search'){
                if ($v['order_status'] == 1 && $v['shipping_status'] == 1 && $v['invoice_status'] == 3) {
                    $order_list[$k]['type'] = "h,s_1";
                }elseif ($v['order_status'] == 0 && $v['shipping_status'] == 0 && $v['invoice_status'] == 1) {
                    $order_list[$k]['type'] = "i,i_1";
                }elseif ($v['order_status'] != 2 && in_array($v['shipping_status'],array(0, 4)) && $v['invoice_status'] < 3) {
                    $order_list[$k]['type'] = "f,f_0";
                }elseif ($v['order_status'] == 7 && $v['shipping_status'] == 1 && $v['invoice_status'] == 3 && in_array(16, $getGoodsStatusByOrderId)) {
                    $order_list[$k]['type'] = "b,g_16";
                }elseif ($v['order_status'] == 7 && $v['shipping_status'] == 1 && $v['invoice_status'] == 3 && in_array(18, $getGoodsStatusByOrderId)) {
                    $order_list[$k]['type'] = "b,g_18";
                }elseif ($v['order_status'] == 6 && $v['shipping_status'] == 0 && $v['invoice_status'] == 3 && in_array(21, $getGoodsStatusByOrderId)) {
                    $order_list[$k]['type'] = "w,g_21";
                }elseif ($v['order_status'] == 6 && $v['shipping_status'] == 0 && $v['invoice_status'] == 3 && in_array(23, $getGoodsStatusByOrderId)) {
                    $order_list[$k]['type'] = "w,g_23";
                }elseif ($v['order_status'] == 6 && $v['shipping_status'] == 0 && $v['invoice_status'] == 3 && in_array(24, $getGoodsStatusByOrderId)) {
                    $order_list[$k]['type'] = "w,g_24";
                }else{
                    $order_list[$k]['type'] = "";
                }
            }
            $order_list[$k]['pay_name'] = $payments[$v['pay_id']]['pay_name'];
            if($v['reserved_time']){
            	$order_list[$k]['reserved_time'] = date("Y-m-d H:i:s", $v['reserved_time']);
            }
            if($v['confirm_time']){
            	$order_list[$k]['confirm_time'] = date("Y-m-d H:i:s", $v['confirm_time']);
            }
            if($v['shipping_time']){
            	$order_list[$k]['shipping_time'] = date("Y-m-d H:i:s", $v['shipping_time']);
            }
            
        }
        $order_list_check = "";
        if(!empty($order_list)){
            $order_list_check = "order_list is not empty";
        }

        // echo $_SERVER["QUERY_STRING"];
        // $url_ = 'csmo.php?'.$_SERVER["QUERY_STRING"];
        // echo $url_;
        // $pagination = new Pagination($total, $offset, $page, 'page', $url = $url_, null, $extra_params);
        $Pager = Pager($total, $offset, $page);
        $currentTime = date("Y-m-d H:i:s");

        // $smarty->assign('pagination', $pagination->get_simple_output());  // 分页
        $smarty->assign("order_list_check",json_encode($order_list_check));
        $smarty->assign('currentTime', $currentTime);
        $smarty->assign('Pager', $Pager);
        $smarty->assign('order_list', $order_list);
    }


    // 读取快递
    $shippings = getShippingTypes();

    $smarty->assign('facilitys',array_intersect_assoc(get_available_facility(),get_user_facility()));
    $smarty->assign('shippings', $shippings);
    $smarty->assign('payments', $payments);
    //$smarty->assign('outer_type_options', $_CFG['adminvars']['outer_type']);
    //订单来源改为订单类型
    $smarty->assign('type', $dc_type);
    $smarty->assign('dc_type', $dc_type);
    $smarty->assign('modules', $modules);
    $smarty->assign('adminvars', $_CFG['adminvars']);
    $smarty->assign('startCalendar',$start_order_time);
    $smarty->assign('shop_type',$shop_type);
    $smarty->assign('order_type',$order_type);
    $smarty->assign('outer_type', $_CFG['adminvars']['outer_type']);
	if ($csv){
        $order_list = $db->getAllRefby($sqla,array('order_id'),$ref_fields_orders, $refs_orders, false); 
        $count = count($ref_fields_orders['order_id']);
        $group_num = floor(($count-1)/500)+1;
         for ($i=0; $i < $group_num; $i++) { 
            $order_ids_group = array();
                for ($j=500*$i; $j <= 500*$i+499; $j++) {
                    if($ref_fields_orders['order_id'][$j] != NULL) {
                        $order_ids_group[$j] = $ref_fields_orders['order_id'][$j]; 
                    }    
                }
                
                $order_ids = implode(',',$order_ids_group); 
                $sql_ = "SELECT info.order_id,group_concat(sp.tracking_number SEPARATOR ';') as tracking_number
                FROM `ecshop`.`ecs_order_info` info
                inner JOIN romeo.order_shipment os on convert(info.ORDER_ID using utf8) = os.ORDER_ID
                inner JOIN romeo.shipment sp on os.shipment_id = sp.shipment_id and sp.tracking_number is not null
                WHERE info.order_id in (".$order_ids." ) group by info.order_id ";               
                $shipping_info_group = $db->getAllRefby($sql_,array('order_id'),$ref_fields_shipping_info_groups, $refs_shipping_info_groups, false);
                if(!empty($refs_shipping_info_groups['order_id'])){
                foreach ($refs_shipping_info_groups['order_id'] as $order_id => $value) {
                    $refs_orders['order_id'][$order_id]['0']['tracking_number'] = $value[0]['tracking_number'];
                }
                }     
           } 
            foreach ($refs_orders['order_id'] as $key => $value) {
                 $refs_orders_a[$key] =  $refs_orders['order_id'][$key]['0'];
                    if($refs_orders_a[$key]['reserved_time']){
                        $refs_orders_a[$key]['reserved_time'] = date("Y-m-d H:i:s", $refs_orders_a[$key]['reserved_time']);
                    }
                    if($refs_orders_a[$key]['confirm_time']){
                        $refs_orders_a[$key]['confirm_time'] = date("Y-m-d H:i:s", $refs_orders_a[$key]['confirm_time']);
                    }
                    if($refs_orders_a[$key]['shipping_time']){
                        $refs_orders_a[$key]['shipping_time'] = date("Y-m-d H:i:s", $refs_orders_a[$key]['shipping_time']);
                    }
                } 
        $smarty->assign('refs_orders_a', $refs_orders_a);
	    if(admin_priv('2kf_order_manage_csv')){
		    header("Content-type:application/vnd.ms-excel");
		    header("Content-Disposition:filename=" .iconv("UTF-8", "GB18030", "订单报表") . ".csv");
		    $out = $smarty->fetch('oukooext/csmo_csv.htm');
		    echo iconv("UTF-8", "GB18030", $out);
		    exit();	        
	    }
	    
	}else{
	    $smarty->display('oukooext/customer-service_manage-order.htm');
	}
}

/*------------------------------------------------------ */
//-- ajax post
/*------------------------------------------------------ */
elseif ($_REQUEST['act'] == 'ajax')
{
    // ajax
    admin_priv('customer_service_manage_order');
    $do = $_REQUEST['do'];
    if ($do == 'cancel') { // 取消订单
        $order_sn = $_REQUEST['order_sn'];
        $action_note = $_REQUEST['note'];
        $fetch = $db->getRow("SELECT * FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn' ".' -- csmo '.__LINE__.PHP_EOL);
        $order_id = $fetch['order_id'];

        $sql_u = "UPDATE ".$ecs->table('order_info')." SET order_status = '2' WHERE order_id = '$order_id'".' -- csmo '.__LINE__.PHP_EOL;
        $db->query($sql_u);
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die("error");
        }
        // {{{ 1 退还欧币，如果有
        if (abs($fetch['integral']) > 0)
        {
            // {{{ 1.1 检查是否有该用户，没有则插入
            $sql0 = "SELECT * from ".$ecs->table('users')." where user_id = '{$fetch['user_id']}' limit 1 ".' -- csmo '.__LINE__.PHP_EOL;
            $ft0 = $db->getRow($sql0);
            if (!$ft0['userId']){die('error');}
            $sql = "select * from ".DB_TBL_OK_USER." where user_id = '{$ft0['userId']}' limit 1 ".' -- csmo '.__LINE__.PHP_EOL;
            $ft1 = $db->getRow($sql);

            if (!$ft1['user_id'])
            {
                $sql = "INSERT INTO ".DB_TBL_OK_USER." (`rank_points`, `pay_points`, `rank_price`, `user_id`) VALUES ('', '', '', '{$ft0['userId']}');".' -- csmo '.__LINE__.PHP_EOL;
                $ttt1 = $db->query($sql);
            }
            // }}}
            $nowtime = time();
            // {{{ 1.2 退还加欧币
            $sqlu = "UPDATE ".DB_TBL_OK_USER."
				set pay_points = pay_points-".abs($fetch['integral'])." 
				where user_id = '{$ft0['userId']}' limit 1 ".' -- csmo '.__LINE__.PHP_EOL;
            $ttta = $db->query($sqlu);
            // }}}
            // {{{ 1.3
            $sql_i = "INSERT INTO ".DB_TBL_OK_POINT_LOG."
				(`pl_id`, `user_id`, `site_id`, `pl_utime`, `pl_uip`, `pl_ponits`, `use_mark`, `use_type`, `pl_comment`) 
				VALUES 
				('', '{$ft0['userId']}', 1, $nowtime, '".ip2long(getRealIp())."', ".abs($fetch['integral']).", '{$fetch['order_sn']}', 6, '订单取消退还');".' -- csmo '.__LINE__.PHP_EOL;
            $tttb = $db->query($sql_i);
            // }}}
        }
        // }}}
        $_info = array('order_id' => $order_id, 'order_status' => 2, 'action_note' => $action_note);
        $action_id = orderActionLog($_info);
        die("ok");
    } elseif ($do == 'wait') { // 无货等待
        $order_sn = $_REQUEST['order_sn'];
        $action_note = $_REQUEST['note'];
        $query = $db->query($sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ".' -- csmo '.__LINE__.PHP_EOL);
        $goods_ids = array();
        $order_id = 0;
        while ($fetch = $db->fetch_array($query)) {
            $order_id = $fetch['order_id'];
            if ($fetch['goods_status'] == 21) {
                $goods_ids[] = $fetch['goods_id'];
            }
        }
        $db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = 22 WHERE order_id = '$order_id'".' -- csmo '.__LINE__.PHP_EOL);
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die("error");
        }
        foreach ($goods_ids as $v) {
            $_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 22, 'action_note' => $action_note);
            $action_id = orderGoodsActionLog($_info);
        }
        die("ok");
    } elseif ($do == 'invoice') { // 修改发票
        $order_sn = $_REQUEST['order_sn'];
        $action_note = $_REQUEST['note'];
        $inv_payee_feedback = $_REQUEST['inv_payee_feedback'];
        $query = $db->query("SELECT order_id, postscript FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn' ".' -- csmo '.__LINE__.PHP_EOL);
        $fetch = $db->fetch_array($query);
        $order_id = $fetch['order_id'];

        $action_note = "客服反馈：<br>反馈修改发票抬头为: ".$inv_payee_feedback."<hr>客服备注：".$action_note;

        $sql_u = "UPDATE ".$ecs->table('order_info')." SET invoice_status = '2' WHERE order_id = '$order_id' AND shipping_status in (0, 4) AND invoice_status = 1 ".' -- csmo '.__LINE__.PHP_EOL;
        $db->query($sql_u);
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die("error");
        }

        // 修改发票抬头
        if ($inv_payee_feedback != $fetch['postscript']) {
            $sql = "UPDATE {$ecs->table('order_info')} SET postscript = '$inv_payee_feedback' WHERE order_id = '$order_id'".' -- csmo '.__LINE__.PHP_EOL;
            $db->query($sql);
        }

        $_info = array('order_id' => $order_id, 'invoice_status' => 2, 'action_note' => $action_note);
        $action_id = orderActionLog($_info);
        die('ok');
    } elseif ($do == 'inv_payee_confirm') { // 客服确认发票
        $order_sn = trim($_REQUEST['order_sn']);
        $action_note = trim($_REQUEST['note']);
        $inv_payee_confirm = trim($_REQUEST['inv_payee_confirm']);
        $fetch = $db->getRow("SELECT * FROM {$ecs->table('order_info')} WHERE order_sn = '$order_sn'");
        $order_id = $fetch['order_id'];

        //		$action_note = "客服确认发票抬头为: {$inv_payee_confirm}";
        //		if ($note != '') {
        //			$action_note .= "<hr>客服备注：{$note}";
        //		}

        $now = time();
        $sql_u = "UPDATE ".$ecs->table('order_info')."
			SET invoice_status = '3', order_status = 1, confirm_time = '$now', inv_payee = '$inv_payee_confirm' 
			WHERE order_id = '$order_id' AND shipping_status in (0, 4) AND invoice_status < 3 ".' -- csmo '.__LINE__.PHP_EOL;
        $db->query($sql_u);
        $affected_rows = $db->affected_rows();
        //		if (!$affected_rows) {
        //			die("error");
        //		}
        $_info = array('order_id' => $order_id, 'invoice_status' => 3, 'order_status' => 1, 'action_note' => $action_note);
        $action_id = orderActionLog($_info);

        if ($fetch['pay_id'] == 1 && $fetch['mobile'] != '') {
            $sql = "SELECT goods_name FROM {$ecs->table('order_goods')} WHERE order_id = '$order_id' ".' -- csmo '.__LINE__.PHP_EOL;
            $goods_names = $db->getCol($sql);
            $goods_names_str = join("，", $goods_names);
            $order_amount = $fetch['order_amount'] + 0;
            /* 20090822 yxiang@oukoo.com 废弃
            //			$msg = "您好，您选择货到付款方式购买的共计{$order_amount}元的{$goods_names_str}已确认，欧酷网正在为您备货"; $order_sn
            $msg = "您好！您在欧酷网的订单（订单号：{$order_sn}）已被确认，我们将会在48小时内为您发货。";
            send_message($msg, $fetch['mobile']);
			*/
        }
        die('ok');
    } elseif ($do == 'rejection') { // 拒收
        $order_sn = trim($_REQUEST['order_sn']);
        $action_note = $_REQUEST['note'];
        $query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'".' -- csmo '.__LINE__.PHP_EOL);
        $fetch = $db->fetch_array($query);
        $order_id = $fetch['order_id'];

        $sql_u = "UPDATE ".$ecs->table('order_info')." SET order_status = '4', shipping_status = '3' WHERE order_id = '$order_id' ".' -- csmo '.__LINE__.PHP_EOL;
        $db->query($sql_u);
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die("error");
        }
        $_info = array('order_id' => $order_id, 'order_status' => 4, 'shipping_status' => 3, 'action_note' => $action_note);
        $action_id = orderActionLog($_info);
        die('ok');
    } elseif ($do == 'buhuo') { // 补货
        $order_sn = $_REQUEST['order_sn'];
        $action_note = $_REQUEST['note'];
        $goods_ids = $_REQUEST['goods_ids'];
        $query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'".' -- csmo '.__LINE__.PHP_EOL);
        $fetch = $db->fetch_array($query);
        $order_id = $fetch['order_id'];

        $db->query("UPDATE ".$ecs->table('order_info')." SET order_status = '7' WHERE order_id = '$order_id'".' -- csmo '.__LINE__.PHP_EOL); // 订单状态改为补货
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die('error');
        }
        $_info = array('order_id' => $order_id, 'order_status' => 7, 'action_note' => $action_note);
        $action_id = orderActionLog($_info);

        $db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '15' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) ".' -- csmo '.__LINE__.PHP_EOL); // 已确认的订单才可以收货确认
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die('error');
        }

        $goods_ids = explode(",", $goods_ids);
        foreach ($goods_ids as $v) {
            $_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 15, 'action_note' => $action_note);
            $action_id = orderGoodsActionLog($_info);
        }
        die("ok");
    } elseif ($do == 'fahuo') { // 缺货-到货-发货
        $order_sn = $_REQUEST['order_sn'];
        $action_note = $_REQUEST['note'];
        $goods_ids = $_REQUEST['goods_ids'];
        $query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'".' -- csmo '.__LINE__.PHP_EOL);
        $fetch = $db->fetch_array($query);
        $order_id = $fetch['order_id'];

        $db->query("UPDATE ".$ecs->table('order_info')." SET order_status = '1' WHERE order_id = '$order_id'"); // 订单状态改为 1
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die('error');
        }
        $_info = array('order_id' => $order_id, 'order_status' => 1, 'action_note' => $action_note);
        orderActionLog($_info);

        // 无货-到货-发货-订单正常1，商品正常0
        $sql_u = "UPDATE ".$ecs->table('order_goods')." SET goods_status = '0' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) ".' -- csmo '.__LINE__.PHP_EOL;
        $db->query($sql_u);
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die('error');
        }

        $goods_ids = explode(",", $goods_ids);
        foreach ($goods_ids as $v) {
            $_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 0, 'action_note' => $action_note);
            orderGoodsActionLog($_info);
        }
        die("ok");
    } elseif ($do == 'buhuoshoudao') { // 补货-到货-收到
        $order_sn = $_REQUEST['order_sn'];
        $action_note = $_REQUEST['note'];
        $goods_ids = $_REQUEST['goods_ids'];
        $query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'");
        $fetch = $db->fetch_array($query);
        $order_id = $fetch['order_id'];

        $db->query("UPDATE ".$ecs->table('order_info')."
			SET order_status = '1', shipping_status = '2' 
			WHERE order_id = '$order_id'".' -- csmo '.__LINE__.PHP_EOL); // 订单状态改为 1
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die('error');
        }
        $_info = array('order_id' => $order_id, 'order_status' => 1, 'shipping_status' => 2, 'action_note' => $action_note);
        orderActionLog($_info);

        // 补货-收到-订单正常1，商品19
        $sql_u = "UPDATE ".$ecs->table('order_goods')."
			SET goods_status = '19' 
			WHERE order_id = '$order_id' AND goods_id in ($goods_ids) ".' -- csmo '.__LINE__.PHP_EOL;
        $db->query($sql_u);
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die('error');
        }

        $goods_ids = explode(",", $goods_ids);
        foreach ($goods_ids as $v) {
            $_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 19, 'action_note' => $action_note);
            orderGoodsActionLog($_info);
        }
        die("ok");
    } elseif ($do == 'buhuoConfirm') { // 批准补货
        $order_sn = $_REQUEST['order_sn'];
        $action_note = $_REQUEST['note'];
        $goods_ids = $_REQUEST['goods_ids'];
        $query = $db->query("SELECT order_id FROM ".$ecs->table('order_info')." WHERE order_sn = '$order_sn'".' -- csmo '.__LINE__.PHP_EOL);
        $fetch = $db->fetch_array($query);
        $order_id = $fetch['order_id'];

        $db->query("UPDATE ".$ecs->table('order_goods')." SET goods_status = '17' WHERE order_id = '$order_id' AND goods_id in ($goods_ids) ".' -- csmo '.__LINE__.PHP_EOL); // 已确认的订单才可以收货确认
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die('error');
        }

        $goods_ids = explode(",", $goods_ids);
        foreach ($goods_ids as $v) {
            $_info = array('goods_id' => $v, 'order_id' => $order_id, 'goods_status' => 17, 'action_note' => $action_note);
            orderGoodsActionLog($_info);
        }
        die("ok");
    } elseif ($do == 'receive') { // 收货确认
        $order_sn = $_REQUEST['order_sn'];
        $action_note = $_REQUEST['note'];
        $fetch = $db->getRow("SELECT *, (goods_amount + shipping_fee + insure_fee + pay_fee + pack_fee + card_fee + integral_money + bonus) AS total_fee
			FROM ".$ecs->table('order_info')." 
			WHERE order_sn = '$order_sn'".' -- csmo '.__LINE__.PHP_EOL);
        $order_id = $fetch['order_id'];
        $sql_u = "UPDATE ".$ecs->table('order_info')." SET shipping_status = '2' WHERE order_id = '$order_id' AND order_status = 1 ".' -- csmo '.__LINE__.PHP_EOL; // 已确认的订单才可以收货确认
        $db->query($sql_u);
        $affected_rows = $db->affected_rows();
        if (!$affected_rows) {
            die("error");
        }
        $_info = array('order_id' => $order_id, 'shipping_status' => 2, 'action_note' => $action_note);
        orderActionLog($_info);

        // {{{ 返送欧币，如果有
        $sql_s = "SELECT sum(return_points) as return_points, group_concat(return_bonus) as return_bonus
			from ".$ecs->table('order_goods')."
			where order_id = '$order_id' 
			group by order_id ".' -- csmo '.__LINE__.PHP_EOL;
        $ft = $db->getRow($sql_s);

        // 1 返欧币
        $return_points = $ft['return_points'];
        if ($return_points > 0)
        {
            // {{{ 1.1 检查是否有该用户，没有则插入
            $sql0 = "SELECT * from ".$ecs->table('users')." where user_id = '{$fetch['user_id']}' limit 1 ".' -- csmo '.__LINE__.PHP_EOL;
            $ft0 = $db->getRow($sql0);
            if (!$ft0['userId']){die('error');}
            $sql = "SELECT * from ".DB_TBL_OK_USER." where user_id = '{$ft0['userId']}' limit 1 ".' -- csmo '.__LINE__.PHP_EOL;
            $ft1 = $db->getRow($sql);
            //			die($sql);
            if (!$ft1['user_id'])
            {
                $sql = "INSERT INTO ".DB_TBL_OK_USER." (`rank_points`, `pay_points`, `rank_price`, `user_id`) VALUES ('', '', '', '{$ft0['userId']}');".' -- csmo '.__LINE__.PHP_EOL;
                $ttt1 = $db->query($sql);
            }
            // }}}
            $nowtime = time();
            // {{{ 1.2 加欧币
            $sqlu = "update ".DB_TBL_OK_USER."
				set rank_points = rank_points+$return_points, pay_points = pay_points+".abs($fetch['integral']).", rank_price = rank_price + {$fetch['total_fee']} 
				where user_id = '{$ft0['userId']}' limit 1 ".' -- csmo '.__LINE__.PHP_EOL;
            $ttt2 = $db->query($sqlu);
            // }}}
            // {{{ 1.3 日志
            $sql_i = "INSERT INTO ".DB_TBL_OK_POINT_LOG."
				(`pl_id`, `user_id`, `site_id`, `pl_utime`, `pl_uip`, `pl_ponits`, `use_mark`, `use_type`, `pl_comment`) 
				VALUES 
				('', '{$ft0['userId']}', 1, $nowtime, '".ip2long(getRealIp())."', $return_points, '$order_sn', 7, '订单完成返送'); ".' -- csmo '.__LINE__.PHP_EOL;
            $ttt3 = $db->query($sql_i);
            // }}}
        }

        // {{{ 2 加红包
        $return_bonus = $ft['return_bonus'];
        if (false !== strpos($return_bonus, ","))
        {
            $return_bonus = explode(",", $return_bonus);
        }
        else
        {
            $return_bonus = array(trim($return_bonus, ","));
        }
        foreach ($return_bonus as $k => $v)
        {
            if ('' == $v)
            {
                continue;
            }
            $sqls = "SELECT * from ".DB_TBL_OK_GIFT_TICKET." where gt_code = '$v' and gt_state in (2, 3) ".' -- csmo '.__LINE__.PHP_EOL; // gt_state @see config.vars.php
            $ttt4 = $getRow = $db->getRow($sqls);
            // 如果红包确实存在
            if ($getRow)
            {
                $sql_i = "INSERT INTO ".DB_TBL_OK_GIFT_TICKET_LOG."
					( `gtl_id` , `gtc_id` , `gt_id` , `user_id` , `gtl_utime` , `gtl_uip` , `gtl_fk_id` , `gtl_type` , `gtl_comment` )
					VALUES (
					NULL , '{$getRow['gtc_id']}', '{$getRow['gt_id']}', '{$ft0['userId']}', '$nowtime', '".ip2long(getRealIp())."', '$order_sn', '2', '活动获得'
					);".' -- csmo '.__LINE__.PHP_EOL;
                $ttt5 = $db->query($sql_i);
            }
        }
        // }}}
        // }}}
        die("ok");
        // {{{ get info
    } elseif ($do == 'getWaitGoods') { // 取得某个订单等待到货的商品
        $order_sn = $_REQUEST['order_sn'];
        $sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' AND b.goods_status = 21 ".' -- csmo '.__LINE__.PHP_EOL;
        $query = $db->query($sql);
        $goods_names = array();
        while ($fetch = $db->fetch_array($query)) {
            if ($fetch['goods_status'] == 21) {
                $fetch['goods_name'] && $goods_names[] = $fetch['goods_name'];
            }
        }
        #file_put_contents(dirname(__FILE__).'/sql.txt', $sql);
        die(join("\t", $goods_names));
    } elseif ($do == 'getOrderGoods') { // 取得某个订单所有的商品名字
        $order_sn = $_REQUEST['order_sn'];
        $sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ".' -- csmo '.__LINE__.PHP_EOL;
        $query = $db->query($sql);
        $goods_names = array();
        while ($fetch = $db->fetch_array($query)) {
            $fetch['goods_name'] && $goods_names[] = $fetch['goods_name'];
        }
        die(join("\t", $goods_names));
    } elseif ($do == 'getOrderGoods2') { // 取得某个订单所有的商品名字
        $order_sn = $_REQUEST['order_sn'];
        $sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ".' -- csmo '.__LINE__.PHP_EOL;
        $query = $db->query($sql);
        $goods = array();
        while ($fetch = $db->fetch_array($query)) {
            $goods[] = $fetch['goods_id']."\t".$fetch['goods_name'];
        }
        die(join("\t\t", $goods));
    } elseif ($do == 'getOrderGoods3') { // 取得某个订单需要补货的商品名字
        $order_sn = $_REQUEST['order_sn'];
        $sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ".' -- csmo '.__LINE__.PHP_EOL;
        $query = $db->query($sql);
        $goods = array();
        while ($fetch = $db->fetch_array($query)) {
            $fetch['goods_status'] == 16 && $goods[] = $fetch['goods_id']."\t".$fetch['goods_name'];
        }
        die(join("\t\t", $goods));
    } elseif ($do == 'getOrderGoods4') { // 取得某个订单xx状态的商品id、名字
        $order_sn = $_REQUEST['order_sn'];
        $goods_status = $_REQUEST['goods_status'];
        $sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_goods')." b ON a.order_id = b.order_id WHERE a.order_sn = '$order_sn' ".' -- csmo '.__LINE__.PHP_EOL;
        $query = $db->query($sql);
        $goods = array();
        while ($fetch = $db->fetch_array($query)) {
            $fetch['goods_status'] == $goods_status && $goods[] = $fetch['goods_id']."\t".$fetch['goods_name'];
        }
        die(join("\t\t", $goods));
    } elseif ($do == 'getFinanceInvoiceInfo') { // 取得财务对发票修改的意见
        $order_id = $_REQUEST['order_id'];
        $sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_action')." b ON a.order_id = b.order_id 
			WHERE a.order_id = '$order_id' AND b.order_status = 0 AND b.shipping_status in (0, 4) AND b.pay_status = 0 AND b.invoice_status = 1 
			ORDER BY action_id DESC LIMIT 1 ".' -- csmo '.__LINE__.PHP_EOL;
        $getRow = $db->getRow($sql);
        die(join("\t\t--\t\t", array($getRow['inv_payee'], $getRow['action_note'])));
    } elseif ($do == 'doConfirmInvoice_getInfo') { // 取得财务对发票修改的意见...
        $order_id = $_REQUEST['order_id'];
        $sql = "SELECT * FROM ".$ecs->table('order_info')." a
			LEFT JOIN ".$ecs->table('order_action')." b ON a.order_id = b.order_id 
			WHERE a.order_id = '$order_id' AND a.order_status != 2 AND a.shipping_status in (0, 4) AND a.invoice_status < 3 
			ORDER BY action_id DESC LIMIT 1 ".' -- csmo '.__LINE__.PHP_EOL;
        $getRow = $db->getRow($sql);
        echo join("\t\t--\t\t", array($getRow['inv_payee'], $getRow['postscript'], $getRow['action_note']));
        die();
    } elseif ($do == 'add_note') {	// 增加备注
        $order_sn = trim($_REQUEST['order_sn']);
        $action_note = trim($_REQUEST['note']);
        $sql = "SELECT order_id FROM {$ecs->table('order_info')} WHERE order_sn = '$order_sn' ".' -- csmo '.__LINE__.PHP_EOL;
        $order_id = $db->getOne($sql);
        $_info = array('order_id' => $order_id, 'action_note' => $action_note);
        $action_id = orderActionLog($_info);
        die('ok');
    } elseif ($do == 'search_shipping')	{		//快递方式模糊搜索
    	 $json = new JSON;
    	 $limit = 20 ;
         print $json->encode(get_shipping_types($_POST['q'], $limit)); 
 	     exit;
    } elseif ($do == 'search_pay_type') {		//收款方式模糊搜索
    	 $json = new JSON;
    	 $limit = 20 ;
         print $json->encode(get_pay_type($_POST['q'], $limit)); 
 	     exit;
    } elseif ($do == 'search_shop_name') {
    	$json = new JSON;
    	$limit = 20;
    	print $json->encode(get_shop_name($_POST['q'], $limit));
    } elseif ($do == 'search_goods_name') {
    	$json = new JSON;
    	$limit = 20;
    	print $json->encode(get_goods_name($_POST['q'], $limit));
    }
    else {
        die(__LINE__);
    }
    // }}}

}

elseif ($_REQUEST['act'] == 'miniquery') { // 针对js客户端用的查询功能，目前主要是给jjshouse使用
    $email = trim($_REQUEST['email']);
    
    if (stripos($email, '@i9i8.com') !== false
        || stripos($email, '@jjshouse.com') !== false
        || stripos($email, '@leqee.com') !== false
        || $email == ''
        ) {
        print "内部邮箱无需查询订单.<br />黄金广告位招租";
        exit(0);
    }
    
    $sql = "SELECT
    o.order_id,
    o.order_status, 
    o.pay_status,
    o.shipping_status,
    o.order_sn,  
    ga.attr_value as order_goods_image,
    oa.value as goods_id,
    a.attr_name,
    og.rec_id as order_goods_id
    from
    ecs_order_info o
    left join ecs_order_goods og on o.order_id = og.order_id
    left join ecshop.order_goods_attribute oa on og.rec_id = oa.order_goods_id and oa.name = 'goods_id'
    left join ecs_goods_attr ga on ga.goods_id = og.goods_id
    inner join ecs_attribute a on ga.attr_id = a.attr_id and a.attr_name = 'goodsImage0_s42'
    where
        o.order_type_id IN ('SALE', 'RMA_EXCHANGE', 'SHIP_ONLY') 
        and o.email = '{$email}'
        and ". party_sql('o.party_id') . "
    group by og.rec_id
    order by o.order_time desc ".' -- csmo '.__LINE__.PHP_EOL;
    $ordergoods = $db->getAll($sql);

    $content = "<html><style>*{border:0;}hr{border:1px solid #ccc!important;}</style><body style=\"margin:0;padding:0;border:0;\"><div style=\"font-size:12px;\">";
    if ($ordergoods) {
        $orders = array();
        $order_map = array();
        $content .= "{$email} orders: <br />";
        
        $order_goods_ids = array();
        
        foreach ($ordergoods as $goods) {
            $orders[$goods['order_sn']][] = $goods;
            $order_map[$goods['order_sn']] = $goods;
            $order_goods_ids[] = $goods['order_goods_id'];
        }
        
        $order_goods_ids = array_unique($order_goods_ids);
        
        $sql = "SELECT d.order_goods_id, d.dispatch_sn, d.created_stamp, d.due_date, d.dispatch_status_id
                from romeo.dispatch_list d
                where  " . db_create_in($order_goods_ids, 'order_goods_id').' -- csmo '.__LINE__.PHP_EOL;
        $_dispatch_sns = $db->getAll($sql);
        $dispatch_sns = array();
        foreach ($_dispatch_sns as $dispatch_sn) {
            $dispatch_sns[$dispatch_sn['order_goods_id']][] = $dispatch_sn;
        }
        
        foreach ($orders as $order_sn => $ordergoods) {
            $order_status_name = get_order_status($order_map[$order_sn]['order_status']);
            $pay_status_name = get_pay_status($order_map[$order_sn]['pay_status']);
            $shipping_status_name = get_shipping_status($order_map[$order_sn]['shipping_status']);
            
            $content .= "<hr>
            订单: <a target=\"_blank\" href=\"order_edit.php?order_id={$order_map[$order_sn]['order_id']}\">
            {$order_sn}
            </a> <br />{$order_status_name}, {$pay_status_name}, {$shipping_status_name}";
            foreach ($ordergoods as $goods) {
                $content .= "<br /><a target=\"_blank\"  href=\"http://www.jjshouse.com/common-g{$goods['goods_id']}\">
                <img src=\"{$goods['order_goods_image']}\" />
                </a> {$goods['dispatchlist_info']}";
                if ($dispatch_sns[$goods['order_goods_id']]) {
                    foreach ($dispatch_sns[$goods['order_goods_id']] as $dispatch_sn) {
                        if ($dispatch_sn['dispatch_status_id'] == 'OK' 
                            && strtotime($dispatch_sn['due_date']) < time() 
                            )
                        {
                            $content .= 
                            "<span style=\"color:red;\">"
                            .$dispatch_sn['dispatch_sn'] . " " .$dispatch_sn['created_stamp'] 
                            .'</span>';
                        } else {
                            $content .= $dispatch_sn['dispatch_sn'] . " " .$dispatch_sn['created_stamp'] ;
                        }   
                    }
                }
            }
        }
        
    } else {
        $content .= "no order ({$email})";
    }
    
    $content .= "</div></body></html>";
    
    print $content;
    exit(0);
}

else {
    die(__LINE__);
}

?>