<?php

/**
 * 帮用户申请售后服务
 * modified by zjli at 2014.1.22 (废除老库存)
 * @param  n/a 通过post
 * @return array
 */

require_once('lib_postsale_cache.php');


function apply_service() {
    global $db, $ecs, $service_return_key_mapping;
    $submit_time = date("Y-m-d H:i:s");
    
    // 检查提交的商品总数量
    $service_order_goods = $_POST['service_order_goods'];
    $facility_id = $_POST['facility_id'];
    
    if(!empty($service_order_goods)){
    	$amount_sum = 0;
    	foreach($service_order_goods as $order_goods_key => $order_goods){
    		$amount_sum += $order_goods['service_goods_amount'];
    	}
    	if ($amount_sum <= 0 ) {
        	print ("您未填写商品数量或输入非法，请填写数量后重新提交 <a href=\"javascript:history.go(-1)\">返回</a>");
        	exit ();
    	}
    }
	
	// 检查申请的售后服务类型
    if (in_array($_POST['type'], array (SERVICE_TYPE_BACK, SERVICE_TYPE_CHANGE, SERVICE_TYPE_RE_SEND))) {
        $service_type = $_POST['type'];
    } else {
        print ("申请的售后服务类型不正确 <a href=\"javascript:history.go(-1)\">返回</a>");
        exit ();
    }

    // 检查输入的订单号
    $sql = sprintf("SELECT o.order_id, o.party_id, o.user_id, u.user_name, og.rec_id AS order_goods_id, 
                    o.facility_id, o.order_status, o.shipping_status, o.pay_status, o.order_sn
                    FROM %s o
                      INNER JOIN %s og ON o.order_id = og.order_id 
                      INNER JOIN %s u ON o.user_id = u.user_id
                      WHERE `order_sn` = '%s' ", 
            $GLOBALS['ecs']->table('order_info'), $GLOBALS['ecs']->table('order_goods'), 
            $GLOBALS['ecs']->table('users'),$GLOBALS['db']->escape_string($_POST['order_sn'])
            );
    $order_goods = $GLOBALS['db']->getAll($sql);
    if (!$order_goods) {
        print ("对不起,您输入的订单号错误。请检查后重试 <a href=\"javascript:history.go(-1)\">返回</a>");
        exit ();
    }
    
    // 判断order_goods_id与传入的order_id是否相符
    $real_order_goods_ids = array();
    foreach($order_goods as $order_good){
    	array_push($real_order_goods_ids,$order_good['order_goods_id']);
    }
    foreach ($service_order_goods as $order_goods_id => $service_order_good) {
    	if(!in_array($order_goods_id, $real_order_goods_ids)){
    		print ("对不起，您输入的订单号与选择的商品不符！请确定商品属于此订单并重试<a href=\"javascript:history.go(-1)\">返回</a>");
    		exit();
    	}
    }
	
	// 检查订单的状态
    $o = reset($order_goods);
    if ($o['order_status']==0) {
        print ("该订单还未进入处理。请检查后重试 <a href=\"javascript:history.go(-1)\">返回</a>");
        exit ();    	
    }else if($o['order_status']==2){
    	print ("该订单已取消。请检查后重试 <a href=\"javascript:history.go(-1)\">返回</a>");
        exit (); 
    }else if($o['order_status']==4){
    	print ("该订单已拒收。请检查后重试 <a href=\"javascript:history.go(-1)\">返回</a>");
        exit (); 
    }else if (in_array($o['shipping_status'], array(0,8,9,10,12,13))) {
        print ("该订单还未发货。请检查后重试 <a href=\"javascript:history.go(-1)\">返回</a>");
        exit ();
    }
    
    // 检查输入的商品数量是否合法，避免由于客服并发操作引起的问题 added by zjli at 2014.1.23
    foreach ($service_order_goods as $order_goods_id => $service_order_good) {
    	if($service_order_good['service_goods_amount'] > 0){
    		// 该订单中该商品已建立过售后服务申请的数量 (Still Improvements Needed)
			$sql = "SELECT SUM(amount) FROM ecshop.service_order_goods sog" .
				"    INNER JOIN ecshop.service s ON s.service_id = sog.service_id" .
				"    WHERE sog.order_goods_id = '{$order_goods_id}'" .
				"        AND ((s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 1 AND sog.is_approved = 1) " .
				"            OR (s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 0 AND sog.is_approved = 0)" .
				"            OR (s.is_complete = 0 AND s.inner_check_status = 32 AND s.service_status = 2 AND sog.is_approved = 1))";
			$amount_in_service = $db->getOne($sql);
			
			// 该订单中该商品还可以建立售后服务申请的数量
			$service_amount_available = $service_order_good['goods_number'] - $amount_in_service;
			
			// 如果申请售后的商品数量超过可以建立售后服务申请的数量，则拒绝建立申请
			if($service_order_good['service_goods_amount'] > $service_amount_available){
				print ("输入的商品数量超过了可以申请的数量，请稍后重试 <a href=\"javascript:history.go(-1)\">返回</a>");
        		exit ();
			}
    	}
    }
    
    // 获取订单相关信息：order_id, user_id, user_name, facility_id, party_id
    $order_id = "";
    $user_id = "";
    $party_id = "";
    foreach ($order_goods as $order_good) {
        if (!$order_id) {
            $order_id = $order_good['order_id'];
        }
        if (!$user_id) {
            $user_id = $order_good['user_id'];
            $user_name = $order_good['user_name'];
        }
        if ($facility_id == 0) {
            $facility_id = $order_good['facility_id'];
        }
        if (!$party_id) {
            $party_id = $order_good['party_id'];
        }
    }
	
	$db->start_transaction();
	if(in_array($_SESSION['party_id'],array('65619','65628','65639'))) {
		$apply_reason = strip_tags($_POST['apply_return_reason']) . "_" . strip_tags($_POST['apply_reason']);
	} else {
		$apply_reason = strip_tags($_POST['apply_reason']);
	}

	
    // 插入一条售后服务记录 service
    $service = array (
        'user_id' => $user_id,
        'order_id' => $order_id,
        'service_type' => $service_type,
        'service_status' => 1,             //申请退换货时  直接自动审核通过2015-11-24
        'apply_username' => $user_name,
        'apply_reason' => $apply_reason,
        'apply_datetime' => $submit_time,
        'facility_id' => $facility_id,
    	'party_id'=> $party_id,
  		'responsible_party' => $_POST['responsible_party'],
  		'dispose_method' => $_POST['dispose_method'],
    	'dispose_description' => $_POST['dispose_description'],
    	'backfee_paiedby' => $_POST['backfee_paiedby']
    );
    $db->autoExecute('service', $service);
    $service_id = $db->insert_id();
    require_once('lib_main.php');
    if(!$service_id || $service_id <= 0){
    	$db->rollback();
    	sys_msg("对不起，操作失败！请联系ERP组", 1);
    }

    //插入售后的商品记录 service_order_goods
    $sql_values = array ();
    foreach ($service_order_goods as $order_goods_id => $service_order_good) {
    	if($service_order_good['service_goods_amount'] >= 0){
    		$amount = $service_order_good['service_goods_amount'];
    		$sql_values[] = sprintf("('%d', '%d', '%d', '%d', '%d', '%d')", $service_id, $user_id, $order_id, $order_goods_id, $amount,1);
    	}
    }
    $sql = sprintf("INSERT INTO service_order_goods(service_id, user_id, order_id, order_goods_id, amount, is_approved)
                             VALUES %s", join(",", $sql_values));
    if(!$db->query($sql)){
    	$db->rollback();
    	sys_msg("对不起，操作失败！请联系ERP组", 1);
    }

    //用户返回的相关信息（注：原有的逻辑，不确定能不能删除 ） by zjli at 2014.1.22
    $service_return = array ();
    foreach ($_POST as $key => $val) { //将用户返回的信息做一次检查分类
    	//由于strpos返回值为false或对应下标值，而deliver的下表值为0与false正好对应，因此取eliver，令下标值为1
       	if ($val && array_key_exists($key, $service_return_key_mapping) && strpos($key,'eliver')) {
      		$service_return[] = "('{$service_id}', '{$key}', '{$val}','carrier_info')";
    	}
    	elseif ($val && array_key_exists($key, $service_return_key_mapping) ) {
      		$service_return[] = "('{$service_id}', '{$key}', '{$val}','bank_info')";
   		}
    }
    
    if (count($service_return)) {
        $sql = "INSERT INTO service_return( service_id, return_name, return_value, return_type) VALUES " . join(",", $service_return);
        if(!$db->query($sql)){
	    	$db->rollback();
	    	sys_msg("对不起，操作失败！请联系ERP组", 1);
    	}
    }
    
    //记录售后的日志（注：原有的逻辑 ）by zjli at 2014.1.22
    $service = array (
        'service_id' => $service_id,
        'service_status' => 0,
        'service_type' => $service_type,
        'log_note' =>  "客服 {$_SESSION['admin_name']} 帮用户申请售后服务",
        'log_type' => 'CUSTOMER_SERVICE',
        'is_remark' => 0
    );
        
    $result = service_log($service);
        
	//自动同意退回--------start   
	$sql = " SELECT * FROM service WHERE service_id = '{$service_id}' ";
    $service = $db->getRow($sql);
    require_once(ROOT_PATH."RomeoApi/lib_inventory.php");
    require_once(ROOT_PATH."RomeoApi/lib_RMATrack.php");
    $sql = "SELECT og.goods_id, og.style_id, og.goods_name,  g.top_cat_id, 
            o.order_sn, o.consignee, o.address, IF(o.mobile = '', o.tel, o.mobile) AS tel,
            ii.provider_id, p.provider_name, ii.serial_number,
            b.brand_id, b.brand_name
            FROM service_order_goods sog
            INNER JOIN ecshop.ecs_order_info o ON sog.order_id = o.order_id 
            INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
            INNER JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
            LEFT JOIN ecshop.ecs_brand b ON b.brand_id = g.brand_id
            INNER JOIN romeo.inventory_item_detail iid ON convert(og.rec_id using utf8)= iid.order_goods_id
            INNER JOIN romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
            LEFT JOIN ecshop.ecs_provider p ON ii.provider_id = p.provider_id
            WHERE sog.service_id = '{$service_id}' AND sog.is_approved = 1";
    $back_goods = $db->getAll($sql); 
    
    $track = new stdClass();
    
    foreach ($back_goods as $goods) {
        if ($goods['top_cat_id'] == 1) {
            $track->productName = $goods['goods_name'];
            $track->productId = getProductId($goods['goods_id'], $goods['style_id']);
            $track->serialNumber = $goods['serial_number'];
            $track->supplierName = $goods['provider_name'];
            $track->supplierId = $goods['provider_id'];
            $track->brandName = $goods['brand_name'];
            $track->brandId = $goods['brand_id'];
        }
    }
    // rma type
    $track->trackTypeId = "V1";
    $track->createdUserByLogin = $_SESSION['admin_name'];
    
    $track->orderSn = $goods['order_sn'];
    $track->orderId = $service['order_id'];
    $track->customerName = $goods['consignee'];
    $track->contactInfo = $goods['tel'];
    $track->acceptDate = date("Y-m-d H:i:s");
    $track->acceptUser = $_SESSION['admin_name'];
    $track->postScript = $service['review_remark'];
    $track->userComment = $service['apply_reason'];
    $track->serviceId = $service['service_id'];
    
    if (!function_exists('soap_get_client')) {
    	require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
    }
    if(isRMATrackNeeded()){
        try {
            $handle=soap_get_client('RMATrackService', 'ROMEO', 'Soap_Client');
            $handle->createTrack($track);
        }
        catch (Exception $e) {
    		$db->rollback();
    //		die($e);
    		sys_msg("对不起，RMATrackService操作失败！请联系ERP组", 1);
        }
    }
    
     //生成-t订单
    $t_order_id = generate_back_order_new($service_id);
    
    if ($t_order_id > 0) {
		$log_note = $_SESSION['admin_name'] ." 于 {$datetime} 同意退回货物，生成退货订单";
	} else {
		$db->rollback();
		sys_msg("对不起，生成退货订单失败！请联系ERP组", 1);
	}
	
	//同意退回日志	
    $service_log = array (
        'service_id' => $service_id,
        'service_status' => 1,
        'service_type' => $service_type,
        'log_note' =>  $log_note,
        'log_type' => 'CUSTOMER_SERVICE',
        'is_remark' => 0
    );       
    $result = service_log($service_log);	    
  //自动同意退回--------end

    if ($result) {
    	$db->commit();

        //SINRI UPDATE POSTSALE CACHe
        POSTSALE_CACHE_updateService(null,180,$service_id);
        
        if (isset ($_POST['back_url'])) {
            header("Location: " . $_POST['back_url']);
        }
    }else{
    	$db->rollback();
    	sys_msg("对不起，操作失败！请联系ERP组", 1);
    }
}

/**
 * 记录物流售后操作日志
 * ljzhou 2014-6-12
 */
 function update_logistic_service_note($service_id,$action_type) {
 	global $db,$_CFG;
 	$sql = "update ecshop.service set back_shipping_status = {$action_type} where service_id='{$service_id}' limit 1";
 	$db->query($sql);
 	$sql = "select l.service_status,l.is_remark,s.service_type 
	 	from ecshop.service_log l
	 	inner join ecshop.service s ON l.service_id = s.service_id
	 	where s.service_id = '{$service_id}' 
	 	and not exists(select 1 from ecshop.service_log where service_log_id > l.service_log_id and service_id = s.service_id)
	    limit 1
	   ";
 	$service_info = $db->getRow($sql);
 	$service_status = $service_info['service_status'];
 	$is_remark = $service_info['is_remark'];
 	$service_type = $service_info['service_type'];
 	$service = array (
        'service_id' => $service_id,
        'service_status' => $service_status,
        'service_type' => $service_type,
        'log_note' =>  "物流 {$_SESSION['admin_name']} 于 ".date("Y-m-d H:i:s").$_CFG['adminvars']['back_action_type_mapping'][$action_type],
        'log_type' => 'LOGISTIC',
        'is_remark' => $is_remark,
        'action_type' => $action_type
    );
    
    $result = service_log($service);

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);
 }

/**
 * 根据order_sn返回订单的商品条目(废老库存)
 *
 * @author zjli at 2014.1.20
 * @param string order_sn 订单号
 * @return array
 */
function get_order_goods_item($order_sn) {
    global $db, $ecs;
    static $order_goods_item_array;

    if ($order_goods_item_array[$order_sn]) {
        return $order_goods_item_array[$order_sn];
    }
    $sql = "SELECT og.rec_id, concat(og.goods_name, IFNULL(g.uniq_sku,'')) as goods_name, og.goods_number,
    			   og.parent_id, g.goods_id, og.goods_price, og.style_id, o.order_sn, o.order_time, c.cat_name
                 FROM {$ecs->table('order_info')} o
                 INNER JOIN {$ecs->table('order_goods')} og ON og.order_id = o.order_id 
                 INNER JOIN {$ecs->table('goods')} g ON g.goods_id = og.goods_id
                 LEFT JOIN ecshop.ecs_category c ON c.cat_id = g.cat_id
                 WHERE o.order_sn = '{$order_sn}' ";
    $order_goods_item = $db->getAll($sql);
    foreach ($order_goods_item as $goods_key => $goods) {
    	// 该订单中该商品已建立过售后服务申请的数量 (Still Improvements Needed)
		$sql = "SELECT SUM(amount) FROM ecshop.service_order_goods sog" .
				"    INNER JOIN ecshop.service s ON s.service_id = sog.service_id" .
				"    WHERE sog.order_goods_id = '{$goods['rec_id']}'" .
				"        AND ((s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 1 AND sog.is_approved = 1) " .
				"            OR (s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 0 AND sog.is_approved = 0)" .
				"            OR (s.is_complete = 0 AND s.inner_check_status = 32 AND s.service_status = 2 AND sog.is_approved = 1))";
		$amount_in_service = $db->getOne($sql);
		
		// 该订单中该商品还可以建立售后服务申请的数量
		$service_amount_available = $goods['goods_number'] - $amount_in_service;
		$order_goods_item[$goods_key]['service_amount_available'] =  $service_amount_available;
    }
    $order_goods_item_array[$order_sn] = $order_goods_item;
    return $order_goods_item;
}

/**
 * 更新service_order_goods（商品通过售后服务审核）
 * 
 * updated by zjli at 2014.1.23 (废老库存)
 * @param array $service 售后服务
 * @param array $service_order_goods 售后服务的商品
 */
function approve_service_order_goods($service, $service_order_goods) {
    global $db;
    if (!$service) {
        return false;
    }
    //都已经有退货订单或者换货，补寄订单了，还改什么呢。
    if ($service['back_order_id'] || $service['change_order_id'] ) {
        return false;
    }

    foreach ($service_order_goods as $goods) {
        if ($goods['service_order_goods_id']) { //如果service_order_goods_id已经存在了，更新相关数据
            $sql = "UPDATE service_order_goods ".
                   "   SET is_approved = '1', amount='{$goods['service_amount']}'".
                   "   WHERE service_order_goods_id = '{$goods['service_order_goods_id']}' LIMIT 1 ";
            if(!$db->query($sql)){
            	$db->rollback();
            	return false;
            }
        } else { //如果service_order_goods_id不存在了，则先查找是否已经在数据库中有相应的记录
            $sql = "SELECT service_order_goods_id FROM service_order_goods ".
                   " WHERE order_goods_id = '{$goods['order_goods_id']}' ".
                   " AND service_id = '{$service['service_id']}' LIMIT 1 ";
            if ($temp_service_order_goods_id = $db->getOne($sql)) {
                $sql = "UPDATE service_order_goods SET is_approved = '1', amount='{$goods['service_amount']}' ".
                       " WHERE service_order_goods_id = '{$temp_service_order_goods_id}' LIMIT 1 ";
            } else { // 数据库中还没有该条记录，直接插入一条记录
                $sql = "INSERT INTO service_order_goods(service_id, user_id, ".
                       " order_id, order_goods_id, amount, is_approved) ".
                       " VALUES ('{$service['service_id']}', '{$service['user_id']}', ".
                       " '{$service['order_id']}', '{$goods['order_goods_id']}', '{$goods['service_amount']}', '1') ";
            }
            if(!$db->query($sql)){
            	$db->rollback();
            	return false;
            }
        }
    }
    return true;
}

/**
 * 根据service_id返回售后的日志
 *
 * @param int service_id 售后服务
 * @return array
 */
function get_servicelog($service_id, $is_remark = -1) {
    global $db, $ecs;
    $sql = "SELECT * FROM service_log
              WHERE service_id = '{$service_id}' " . 
              ( in_array($is_remark, array (0, 1)) ? " AND is_remark = '{$is_remark}' "  : "" )
           . " ORDER BY service_log_id ";
    $services = $db->getAll($sql);
    $service_count = count($services);
    $remark_count = 0;
    foreach ($services as $key => $val) {
        if ($val['is_remark'] == 1) {
            ++ $remark_count;
        }
    }
    $services[0]['remark_count'] = $remark_count;
    $services[0]['log_count'] = $service_count - $remark_count;
    return $services;
}

/**
 * 根据service_id返回用户返回的信息
 *
 * @param int service 售后服务
 * @return void
 */
function get_servicereturn($service_id) {
	
    global $db, $ecs, $service_return_key_mapping;
    $sql = "SELECT * FROM service_return WHERE service_id = '{$service_id}' ORDER BY service_return_id ASC ";
    $temps = $db->getAll($sql);
    $return_infos = array ();
    foreach ($temps as $return_info) {
        $return_name = $service_return_key_mapping[$return_info['return_name']];
        $return_infos[$return_info['return_type']][$return_name] = $return_info['return_value'];
    }
    return $return_infos;
}

/**
 * 记录售后的日志
 *
 * @param int service 售后服务
 * @return void
 */
function service_log($service) {
    global $ecs, $db, $_CFG;

	$user_name = $_SESSION['admin_name'];
    if (empty ($user_name)) {
        $user_name = 'system';
    }
    $service['is_remark'] = $service['is_remark'] ? 1 : 0;
    $service['action_type'] = $service['action_type'] ? $service['action_type'] : 0;
    $service_data = $db->escape_string(serialize($service));
    $service['service_status'] = $service['service_status'];
    $service['service_data'] = $service_data;
    $service['status_name'] = service_status($service);
    $service['type_name'] = $_CFG['adminvars']['service_type_mapping'][$service['service_type']];
    $service['log_username'] = $user_name;
    $service['log_datetime'] = date("Y-m-d H:i:s");

    return $db->autoExecute('service_log', $service);
}

/**
 * 生成退货-t订单(废除老库存)
 * @author zjli at 2014.2.15
 * 
 * @param int service_id 售后服务service_id
 * @return int 生成-t订单的order_id
 */
function generate_back_order_new($service_id) {
    global $ecs, $db;
    $sqls = array ();

	// ---------------------------- 1.为即将生成的退货订单命名订单号sn -------------------------------
	$back_order_sn = "";  // 初始化即将生成的退货订单的sn号
	$sql = "SELECT o.order_sn
			FROM ecshop.service s
			INNER JOIN ecshop.ecs_order_info o ON s.order_id = o.order_id
			WHERE s.service_id = '{$service_id}' LIMIT 1";
	$sqls[] = $sql;
	$order_sn = $db->getOne($sql);
	if(!$order_sn){
		return false;
	}else{
		$back_order_sn = $order_sn."-t";
	}
	
	while (1) { //生成订单表中没有的订单号。
        $sql = "SELECT COUNT(order_id) FROM {$ecs->table('order_info')} WHERE order_sn = '{$back_order_sn}' ";
        if ($db->getOne($sql) == 0) {
            break;
        } else {
            $back_order_sn .= "-t";
        }
    }
    
    // -------------------------------- 2.获得售后服务的商品信息 ------------------------------------
    $sql = "SELECT sog.*, og.*
           FROM ecshop.service_order_goods sog
           INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
           WHERE sog.is_approved = 1 AND sog.amount <> 0 AND sog.service_id  = '{$service_id}' ";
    $sqls[] = $sql;
    $back_goods = $db->getAll($sql);
	
	// 如果找不到申请售后的商品信息，则返回false 
    if (!$back_goods) {
        return false;
    }
    
	// ---------------------------------- 3.计算商品折扣信息 --------------------------------------
    //初始化变量
    $back_goods_amount = 0;  // 退回商品的总金额
    $back_bonus_tmp = 0;
    $back_goods_bonus = 0;
    $discount_fee_detail = array();

    foreach ($back_goods as $back_item) {
        $back_goods_amount += $back_item['goods_price'] * $back_item['amount']; //累计商品的价格
        
        // 检查该商品是否已经退货，若已经退货的不能再退
        // ...
        
		$single_discount_fee_detail = get_single_discount_fee_detail_new($back_item['service_order_goods_id'], $back_item['rec_id'], $back_item['order_id']);
        if($single_discount_fee_detail == false){
        	return false;
        }
        array_push($discount_fee_detail,$single_discount_fee_detail);
        $back_bonus_tmp += $single_discount_fee_detail['single_discount_fee'];
        $back_goods_bonus += $single_discount_fee_detail['single_item_discount_fee'];
    }
    
	// ------------------------- 4.通过原订单的相关信息计算即将生成的-t订单对应的信息 --------------------------
    // -t订单的facility_id来自service中的facility_id by zwsun 2009-10-22
    $sql = "SELECT o.user_id, o.bonus, o.shipping_fee, o.goods_amount, o.pack_fee, ".
           " pay_id, pay_name, shipping_id, shipping_name, o.order_id, o.taobao_order_sn, ".
           " o.order_sn, o.party_id, o.distributor_id, s.facility_id, o.order_type_id, ".
           " s.inner_check_status, s.backfee_paiedby, ".
           " ( SELECT SUM(og.goods_number) FROM {$ecs->table('order_goods')} og ".
           "    WHERE og.order_id = o.order_id ) AS goods_total_num, o.currency ".
           " FROM {$ecs->table('order_info')} o ".
           " INNER JOIN service s on o.order_id = s.order_id ".
           " WHERE s.service_id = '{$service_id}' ";
    $origin_order = $db->getRow($sql);
    $sqls[] = $sql;
    $facility_id = $origin_order['facility_id'];
    
    require_once('lib_order_relation.php');
    
    //如果是销售订单，则root_order为原订单，否则去订单关系中查询
    if ( $origin_order['order_type_id'] == 'SALE' || strlen($origin_order['order_sn'] == 10) ) {
        $root_order = $origin_order;
    } else {
    	$root_order = get_order_related_root_order($origin_order['order_id']);
    }
    
    // 开始计算是否全部退回   使用退过的商品金额：$related_amount['goods_amount_returned']
    $related_amount = get_order_related_amount($root_order['order_id']);
    $goods_amount_all = $root_order['goods_amount'] + $related_amount['goods_amount_returned']
                                                    - abs($back_goods_amount);
    $all_returned = $goods_amount_all <= 0 ? true : false;
    
    //计算-t订单的各种费用 important!
    $back_shipping_fee = 0;
    $back_pack_fee = 0;
    $back_shipping_fee = $all_returned ? $origin_order['shipping_fee'] : 0;
    $back_pack_fee = $all_returned ? $origin_order['pack_fee'] : 0;
    
    //杂项费用，客服手动更新， 如果是用户支付的快递费，默认要把快递算到杂项费用退给用户
    $misc_fee = 0;
    if ($origin_order['backfee_paiedby'] == 'USER') {
        $sql = "SELECT return_value FROM service_return WHERE service_id = '{$service_id}' AND return_name = 'deliver_fee' ";
        $misc_fee = floatval($db->getOne($sql));
    }
    
    //分销订单和手动订单按原来的逻辑分配红包
    $sql = "
         select 1
         from ecshop.order_attribute
         where attr_name = 'DISCOUNT_FEE' 
         and order_id = '{$origin_order['order_id']}'
         and attr_value is not null
         ";
    $result = $db->getOne($sql);
  	
    // 退款还有多少红包可以抵扣
    $bonus_left = $origin_order['bonus'] - $related_amount['bonus_returned'];
    
    $back_order_amount = 0;
    if(!$result){
    	$back_order_amount = -1 * ( $back_shipping_fee + max($back_goods_amount  + $back_pack_fee + $bonus_left, 0) );
    	$back_bonus = -1 * min($back_goods_amount + $back_pack_fee, abs($bonus_left));
    }else{
    	$back_bonus = sprintf("%01.2f",-1*min($back_bonus_tmp,abs($bonus_left)));
    	$back_order_amount = -1 * ( $back_shipping_fee + max($back_goods_amount  + $back_pack_fee + $back_bonus, 0) );
    }

    $back_order_time = date("Y-m-d H:i:s");
	
	// ----------------------------------- 5.生成-t订单 ------------------------------------
    $sql = "INSERT INTO {$ecs->table('order_info')}
            (order_sn, order_time, user_id, order_status, order_amount, goods_amount, 
             shipping_fee, pack_fee, bonus, misc_fee,
             pay_id, pay_name, shipping_id, shipping_name, 
             party_id, facility_id, distributor_id, currency, order_type_id, taobao_order_sn) 
            VALUES('{$back_order_sn}', '{$back_order_time}', '{$origin_order['user_id']}', 1, 
            '{$back_order_amount}', '{$back_goods_amount}', 
            '{$back_shipping_fee}', '{$back_pack_fee}', '{$back_bonus}',  '{$misc_fee}',
            '{$origin_order['pay_id']}', '{$origin_order['pay_name']}', 
            '{$origin_order['shipping_id']}', '{$origin_order['shipping_name']}', 
            '{$origin_order['party_id']}','{$facility_id}', 
            '{$origin_order['distributor_id']}', '{$origin_order['currency']}', 'RMA_RETURN', '{$origin_order['taobao_order_sn']}') ";
    $sqls[] = $sql;
    if(!$db->query($sql)){
    	$db->rollback();
    	return false;
    } //生成退货订单的order_info记录
    $fromOrderId = $origin_order['order_id'];
    $back_order_id = $db->insert_id();
    
    //添加相关的订单级别的折扣信息
    $back_order_bonus = abs($back_bonus) - $back_goods_bonus;
    $sql = "insert into ecshop.order_attribute
            (order_id,attr_name,attr_value)
            values('{$back_order_id}','DISCOUNT_FEE','{$back_order_bonus}')";
    $sqls[] = $sql;
    if(!$db->query($sql)){
    	$db->rollback();
    	return false;
    } 
    
    //增加记录订单关系 added by zwsun 2009年7月9日10:56:34
    require_once ('lib_order.php');
    if(!add_order_relation($back_order_id, $origin_order['order_id'], '', $back_order_sn, $origin_order['order_sn'])){
    	$db->rollback();
    	return false;
    }
    
    // ---------------------------------- 6.插入订单商品记录、商品级别折扣信息 ----------------------------------
    foreach ($back_goods as $back_item) {
        // 获取一些商品信息
        $goods_id = $back_item['goods_id'];     // goods_id
        $style_id = $back_item['style_id'];     // style_id
        $goods_name = $back_item['goods_name']; // goods_name
		$goods_name = addslashes($goods_name);
		
		// 插入ecs_order_goods记录
        $sql = "INSERT INTO {$ecs->table('order_goods')} 
                  (order_id, goods_id, style_id, goods_name, goods_number, market_price, goods_price, group_code) 
                  VALUES('{$back_order_id}', '{$goods_id}', '{$style_id}', '{$goods_name}', '{$back_item['amount']}', '{$back_item['market_price']}', '{$back_item['goods_price']}','{$back_item['group_code']}')
              ";
        $sqls[] = $sql;
        if(!$db->query($sql)){
        	$db->rollback();
        	return false;
        }
        $order_goods_id = $db->insert_id();

		// 添加商品级别的相关折扣信息（新库存方式）
        foreach($discount_fee_detail as $item_discount_fee_detail){
        	if($item_discount_fee_detail['order_goods_id'] == $back_item['rec_id']){
        		$item_discount_fee = $item_discount_fee_detail['single_item_discount_fee'];
        		//添加商品级别的折扣信息
        		$sql = "insert into ecshop.order_goods_attribute
        		        (order_goods_id,name,value)
        		        values('{$order_goods_id}','DISCOUNT_FEE','{$item_discount_fee}')";
        		$sqls[] = $sql;
        		if(!$db->query($sql)){
        			$db->rollback();
        			return false;
        		}
        	}
        }
    }
    
    if ($back_order_id) {
        //更新售后服务的状态
        $sql = "UPDATE service SET back_order_id = '{$back_order_id}' WHERE service_id = '{$service_id}' LIMIT 1 ";
        if(!$db->query($sql)){
        	$db->rollback();
        	return false;
        }
    }
    return $back_order_id;
}

/**
 * 退换货收货、验货入库(废除老库存)
 * 原先版本串号要有序传入，太恶心了，该函数改造之
 * @author ljzhou at 2014.12.29
 * 
 * @param int service_id 售后服务service_id
 * @return int 生成-t订单的order_id
 */
function actual_back_change_in_stock($service_id, $serialNums, $goodsType) {
	global $db;
	
	// -------------------------------- 1.获得售后服务的商品信息 ------------------------------------
    $sql = "SELECT sog.*, og.*
           FROM ecshop.service_order_goods sog
           INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
           WHERE sog.is_approved = 1 AND sog.service_id  = '{$service_id}' ";
    $sqls[] = $sql;
    $back_goods = $db->getAll($sql);
	
	// 如果找不到申请售后的商品信息，则返回false 
    if (!$back_goods) {
        return false;
    }
    
    // -------------------------------- 2.获得退货订单的相关信息 --------------------------------------
    $sql = "SELECT o.order_id, o.facility_id 
    		FROM ecshop.ecs_order_info o
    		INNER JOIN ecshop.service s ON s.back_order_id = o.order_id
    		WHERE s.service_id = '{$service_id}'";
    $sqls[] = $sql;
    $back_order = $db->getRow($sql);
    
    if(!$back_order){
    	return false;
    }
	
	// ------------------------------------ 3.入新库存 ------------------------------------------
	$back_order_goods_ids= array();   // 记录已经使用过的order_goods_id, 因为同一种商品可能会有多个order_goods_id
    $goodInd = 0;
    foreach ($back_goods as $back_item) {
    	if($back_item['amount'] != 0){
    		// 获取一些商品信息
	        $goods_id = $back_item['goods_id'];         // goods_id
	        $style_id = $back_item['style_id'];         // style_id
	        $goods_name = $back_item['goods_name'];     // goods_name
	        $amount = $back_item['amount'];             // goods_number
	        $market_price = $back_item['market_price']; // market_price
	        $goods_price = $back_item['goods_price'];   // goods_price
			$goods_name = addslashes($goods_name);
			
			// 获取该商品-t订单对应的order_goods_id(rec_id)。注，这里将条件限制得很死，是因为同一种商品可能会有多条ecs_order_goods记录
			$back_order_goods_id = 0;
			$sql = "SELECT *
					FROM ecshop.ecs_order_goods
					WHERE order_id = '{$back_order['order_id']}'
					  AND goods_id = '{$goods_id}'
					  AND style_id = '{$style_id}'
					  AND goods_number = '{$amount}'
					  AND market_price = '{$market_price}'
					  AND goods_price = '{$goods_price}'";
			$back_order_goods = $db->getAll($sql);
			if(!empty($back_order_goods)){
				// 找到一个还没有使用过的order_goods_id
				foreach($back_order_goods as $back_order_good){
					if(!in_array($back_order_good['rec_id'],$back_order_goods_ids)){
						$back_order_goods_id = $back_order_good['rec_id'];
						array_push($back_order_goods_ids, $back_order_goods_id);
						break;
					}
				}
				if($back_order_goods_id == 0){
					return false;
				}
			}else{
				return false;
			}
			
	        // romeo code:
	        // 入库
	        include_once (ROOT_PATH . "RomeoApi/lib_inventory.php");
	        $is_serial = getInventoryItemType($goods_id);  // 判断该商品是否串号控制
	        if($is_serial == 'SERIALIZED'){  // 该商品是串号控制的
	        	foreach($serialNums[$goodInd] as $serialNum){  // 每件商品单独入库
	        		// 判断入的库是二手库还是全新库
	        		if($goodsType[$serialNum][0] == 'new'){
	        			$toStatusId = 'INV_STTS_AVAILABLE';
	        		}else{
	        			$toStatusId = 'INV_STTS_USED';
	        		}
	        		
	        		// 查询商品销售出库时的unit_cost(采购单价), provider_id(供应商)以及inventory_item_acct_type_id(B2C C2C DX etc)信息 .
					// 此sql尚有改进空间，iid.order_goods_id 与 rec_id 类型不匹配, 可能会因此导致性能问题
					$sql = "SELECT ii.inventory_item_acct_type_id, ii.unit_cost, ii.provider_id
	                		FROM romeo.inventory_item ii
	                		INNER JOIN romeo.inventory_item_detail iid ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
							WHERE iid.ORDER_GOODS_ID = '{$back_item['rec_id']}' AND ii.SERIAL_NUMBER = '{$serialNum}' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE';";
					$in_erp_info = $db->getRow($sql);
					if(!$in_erp_info){
						return false;
					}
					
					// 入库
	        		$status = createAcceptInventoryTransactionNew('ITT_SO_RET',
	                                             array('goods_id'=>$goods_id, 'style_id'=>$style_id),
	                                             1, $serialNum, $in_erp_info['inventory_item_acct_type_id'], $back_order['order_id'],
	                                             '', $toStatusId,
	                                             $in_erp_info['unit_cost'], $back_order_goods_id,
	                                             $back_order['facility_id'], $in_erp_info['provider_id']);
	                if($status != 'OK'){
		            	return false;
	            	}
	        	}
	        }else{  // 该商品是非串号控制的
	        	// 获取该商品的商品条码
	        	$sql = "SELECT IFNULL(gs.barcode, g.barcode) as barcode FROM ecshop.ecs_order_goods og
	          			LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id and gs.is_delete=0
	          			LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
	          			WHERE og.rec_id  = '{$back_item['rec_id']}' ";
	          	$barcode = $db->getOne($sql);
	          	if(!$barcode){
	          		return false;
	          	}
	          	
	          	// 统计退回货物的新旧情况
	        	$numOfNew = 0;  // 新的数量
	        	$numOfOld = 0;  // 二手数量
	        	if(!empty($goodsType[$barcode])){
	        		foreach($goodsType[$barcode] as $key=>$goodType){
		        		if($goodType == 'new'){
		        			$numOfNew++;
		        		}elseif($goodType == 'old'){
		        			$numOfOld++;
		        		}
		        		$goodsType[$barcode][$key] = '';
		        		if(($numOfNew + $numOfOld) == $back_item['amount']){
		        			break;
		        		}
		        	}
	        	}
	        	
	        	// 查询商品销售出库时的unit_cost(采购单价), provider_id(供应商)以及inventory_item_acct_type_id(B2C C2C DX etc)信息 .
	        	// 此sql可能会存在问题，因为这里只取了unit_cost(采购单价)的一个值，该字段可能有多个值
	        	$sql = "SELECT ii.inventory_item_acct_type_id, ii.unit_cost, ii.provider_id
	                	FROM romeo.inventory_item ii
	                	INNER JOIN romeo.inventory_item_detail iid ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
						WHERE iid.ORDER_GOODS_ID = '{$back_item['rec_id']}' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE' LIMIT 1";
				$in_erp_info = $db->getRow($sql);
				if(!$in_erp_info){
					return false;
				}
				
				// 全新商品入库(INV_STTS_AVAILABLE)
				if($numOfNew > 0){
					$status = createAcceptInventoryTransactionNew('ITT_SO_RET',
	                                             array('goods_id'=>$goods_id, 'style_id'=>$style_id),
	                                             $numOfNew, '', $in_erp_info['inventory_item_acct_type_id'], $back_order['order_id'],
	                                             '', 'INV_STTS_AVAILABLE',
	                                             $in_erp_info['unit_cost'], $back_order_goods_id,
	                                             $back_order['facility_id'], $in_erp_info['provider_id']);
	                if($status != 'OK'){
		            	return false;
		            }
				}
	        	// 二手商品入库(INV_STTS_USED)
	        	if($numOfOld > 0){
	        		$status = createAcceptInventoryTransactionNew('ITT_SO_RET',
	                                             array('goods_id'=>$goods_id, 'style_id'=>$style_id),
	                                             $numOfOld, '', $in_erp_info['inventory_item_acct_type_id'], $back_order['order_id'],
	                                             '', 'INV_STTS_USED',
	                                             $in_erp_info['unit_cost'], $back_order_goods_id,
	                                             $back_order['facility_id'], $in_erp_info['provider_id']);
	                if($status != 'OK'){
		            	return false;
		            }
	        	}
	        }
    		
    	}
        $goodInd++;
    }

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);

    return $goodInd;
}


/**
 * 退换货收货、验货入库(废除老库存)
 * @author zjli at 2014.2.24
 * 
 * @param int service_id 售后服务service_id
 * @return int 生成-t订单的order_id
 */
function back_change_in_stock($service_id, $serialNums, $goodsType) {
	global $db;
	// -------------------------------- 1.获得售后服务的商品信息 ------------------------------------
    $sql = "SELECT sog.*, og.*
           FROM ecshop.service_order_goods sog
           INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id 
           WHERE sog.is_approved = 1 AND sog.service_id  = '{$service_id}' ";
    $sqls[] = $sql;
    $back_goods = $db->getAll($sql);
	
	// 如果找不到申请售后的商品信息，则返回false 
    if (!$back_goods) {
        return false;
    }
    
    // -------------------------------- 2.获得退货订单的相关信息 --------------------------------------
    $sql = "SELECT o.order_id, o.facility_id 
    		FROM ecshop.ecs_order_info o
    		INNER JOIN ecshop.service s ON s.back_order_id = o.order_id
    		WHERE s.service_id = '{$service_id}'";
    $sqls[] = $sql;
    $back_order = $db->getRow($sql);
    
    if(!$back_order){
    	return false;
    }
	
	// ------------------------------------ 3.入新库存 ------------------------------------------
	$back_order_goods_ids= array();   // 记录已经使用过的order_goods_id, 因为同一种商品可能会有多个order_goods_id
    $goodInd = 0;//大鲵说，其实非常坑，这里都从0开始，但是事实上会有部分入库的，导致index就不是0
    foreach ($back_goods as $back_item) {

    	if($back_item['amount'] != 0){
    		// 获取一些商品信息
	        $goods_id = $back_item['goods_id'];         // goods_id
	        $style_id = $back_item['style_id'];         // style_id
	        $goods_name = $back_item['goods_name'];     // goods_name
	        $amount = $back_item['amount'];             // goods_number
	        $market_price = $back_item['market_price']; // market_price
	        $goods_price = $back_item['goods_price'];   // goods_price
			$goods_name = addslashes($goods_name);
			
			// 获取该商品-t订单对应的order_goods_id(rec_id)。注，这里将条件限制得很死，是因为同一种商品可能会有多条ecs_order_goods记录
			$back_order_goods_id = 0;
			$this_goods_inventory = 0; 
			$sql = "SELECT *
					FROM ecshop.ecs_order_goods
					WHERE order_id = '{$back_order['order_id']}'
					  AND goods_id = '{$goods_id}'
					  AND style_id = '{$style_id}'
					  AND goods_number = '{$amount}'
					  AND market_price = '{$market_price}'
					  AND goods_price = '{$goods_price}'";
			$back_order_goods = $db->getAll($sql);
			if(!empty($back_order_goods)){
				// 找到一个还没有使用过的order_goods_id
				foreach($back_order_goods as $back_order_good){
					if(!in_array($back_order_good['rec_id'],$back_order_goods_ids)){
						$back_order_goods_id = $back_order_good['rec_id'];
						$sql = "SELECT sum( IFNULL(QUANTITY_ON_HAND_DIFF,0) ) 
						   as inventory 
						   from romeo.inventory_item_detail 
						   where order_goods_id = '{$back_order_goods_id}' GROUP BY order_goods_id "; 
						$in = $db->getOne($sql); 
						if(empty($in)) $in = 0; 
						if($in < 0) $in = 0; 
						$this_goods_inventory += $in ; 
						array_push($back_order_goods_ids, $back_order_goods_id);
						break;
					}
				}
				if($back_order_goods_id == 0){
					return false;
				}
			}else{
				return false;
			}
			// 该商品已入库 
			if( $back_item['amount'] <= $this_goods_inventory ) continue; 
	        // romeo code:
	        // 入库
	        include_once (ROOT_PATH . "RomeoApi/lib_inventory.php");
	        $is_serial = getInventoryItemType($goods_id);  // 判断该商品是否串号控制
	        if($is_serial == 'SERIALIZED'){  // 该商品是串号控制的
                QLog::log('SinriDebug serialNums:['.$goodInd.'] '.json_encode($serialNums));
                $serialNums_arrayzed=$serialNums;//array_values($serialNums);//大鲵的修复的修复
	        	foreach($serialNums_arrayzed[$goodInd] as $serialNum){  // 每件商品单独入库
	        		// 判断入的库是二手库还是全新库
	        		if($goodsType[$serialNum][0] == 'new'){
	        			$toStatusId = 'INV_STTS_AVAILABLE';
	        		}else{
	        			$toStatusId = 'INV_STTS_USED';
	        		}
	        		
	        		// 查询商品销售出库时的unit_cost(采购单价), provider_id(供应商)以及inventory_item_acct_type_id(B2C C2C DX etc)信息 .
					// 此sql尚有改进空间，iid.order_goods_id 与 rec_id 类型不匹配, 可能会因此导致性能问题
					$sql = "SELECT ii.inventory_item_acct_type_id, ii.unit_cost, ii.provider_id
	                		FROM romeo.inventory_item ii
	                		INNER JOIN romeo.inventory_item_detail iid ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
							WHERE iid.ORDER_GOODS_ID = '{$back_item['rec_id']}' AND ii.SERIAL_NUMBER = '{$serialNum}'";
					$in_erp_info = $db->getRow($sql);
					if(!$in_erp_info){
						return false;
					}
					
					// 入库
                    QLog::log("SinriDebug createAcceptInventoryTransactionNew('ITT_SO_RET', array('goods_id'=>$goods_id, 'style_id'=>{$style_id}), 1, {$serialNum}, {$in_erp_info['inventory_item_acct_type_id']}, {$back_order['order_id']}, '', {$toStatusId}, {$in_erp_info['unit_cost']}, {$back_order_goods_id}, {$back_order['facility_id']}, {$in_erp_info['provider_id']}); Begins");
	        		$status = createAcceptInventoryTransactionNew('ITT_SO_RET',
	                                             array('goods_id'=>$goods_id, 'style_id'=>$style_id),
	                                             1, $serialNum, $in_erp_info['inventory_item_acct_type_id'], $back_order['order_id'],
	                                             '', $toStatusId,
	                                             $in_erp_info['unit_cost'], $back_order_goods_id,
	                                             $back_order['facility_id'], $in_erp_info['provider_id']);
                    QLog::log('SinriDebug createAcceptInventoryTransactionNew -> status: '.$status);
	                if($status != 'OK'){
		            	return false;
	            	}
	        	}
	        }else{  // 该商品是非串号控制的
	        	// 获取该商品的商品条码
	        	$sql = "SELECT IFNULL(gs.barcode, g.barcode) as barcode FROM ecshop.ecs_order_goods og
	          			LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id and gs.is_delete=0
	          			LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
	          			WHERE og.rec_id  = '{$back_item['rec_id']}' ";
	          	$barcode = $db->getOne($sql);
	          	if(!$barcode){
	          		return false;
	          	}      
	          	
	          	// 统计退回货物的新旧情况
	        	$numOfNew = 0;  // 新的数量
	        	$numOfOld = 0;  // 二手数量
	        	if(!empty($goodsType[$barcode])){
	        		foreach($goodsType[$barcode] as $key=>$goodType){
		        		if($goodType == 'new'){
		        			$numOfNew++;
		        		}elseif($goodType == 'old'){
		        			$numOfOld++;
		        		}
		        		$goodsType[$barcode][$key] = '';
		        		if(($numOfNew + $numOfOld) == $back_item['amount'] - $this_goods_inventory ){
		        			break;
		        		}
		        	}
	        	}
	        	
	        	// 查询商品销售出库时的unit_cost(采购单价), provider_id(供应商)以及inventory_item_acct_type_id(B2C C2C DX etc)信息 .
	        	// 此sql可能会存在问题，因为这里只取了unit_cost(采购单价)的一个值，该字段可能有多个值
	        	$sql = "SELECT ii.inventory_item_acct_type_id, ii.unit_cost, ii.provider_id
	                	FROM romeo.inventory_item ii
	                	INNER JOIN romeo.inventory_item_detail iid ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
						WHERE iid.ORDER_GOODS_ID = '{$back_item['rec_id']}'  LIMIT 1";
				$in_erp_info = $db->getRow($sql);
				if(!$in_erp_info){
					return false;
				}
			 
				// 全新商品入库(INV_STTS_AVAILABLE)
				if($numOfNew > 0){
					 
					$status = createAcceptInventoryTransactionNew('ITT_SO_RET',
	                                             array('goods_id'=>$goods_id, 'style_id'=>$style_id),
	                                             $numOfNew, '', $in_erp_info['inventory_item_acct_type_id'], $back_order['order_id'],
	                                             '', 'INV_STTS_AVAILABLE',
	                                             $in_erp_info['unit_cost'], $back_order_goods_id,
	                                             $back_order['facility_id'], $in_erp_info['provider_id']);
	                if($status != 'OK'){
		            	return false;
		            }
		            
				}
	        	// 二手商品入库(INV_STTS_USED)
	        	if($numOfOld > 0){
	        		$status = createAcceptInventoryTransactionNew('ITT_SO_RET',
	                                             array('goods_id'=>$goods_id, 'style_id'=>$style_id),
	                                             $numOfOld, '', $in_erp_info['inventory_item_acct_type_id'], $back_order['order_id'],
	                                             '', 'INV_STTS_USED',
	                                             $in_erp_info['unit_cost'], $back_order_goods_id,
	                                             $back_order['facility_id'], $in_erp_info['provider_id']);
	        		 
	                if($status != 'OK'){
		            	return false;
		            }
	        	}
	        }
    		
    	}
        $goodInd++;


    }
    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);

    return $goodInd;
}

/**
 * 退换货虚拟入库(ECCO无串号商品)
 * @author ytchen at 2014.12.3
 */
function back_goods_add_inventory($service_id){
	
	global $db;
	
	// -------------------------------- 1.获得售后服务的商品信息 ------------------------------------
    $sql = "SELECT sog.*, og.*
           FROM ecshop.service_order_goods sog
           INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
           WHERE sog.is_approved = 1 AND sog.service_id  = '{$service_id}' ";
    $sqls[] = $sql;
    $back_goods = $db->getAll($sql);
	
	// 如果找不到申请售后的商品信息，则返回false 
    if (!$back_goods) {
        return false;
    }
    
    // -------------------------------- 2.获得退货订单的相关信息 --------------------------------------
    $sql = "SELECT o.order_id, o.facility_id 
    		FROM ecshop.ecs_order_info o
    		INNER JOIN ecshop.service s ON s.back_order_id = o.order_id
    		WHERE s.service_id = '{$service_id}'";
    $sqls[] = $sql;
    $back_order = $db->getRow($sql);
    
    if(!$back_order){
    	return false;
    }
	
	// ------------------------------------ 3.入新库存 ------------------------------------------
	$back_order_goods_ids= array();   // 记录已经使用过的order_goods_id, 因为同一种商品可能会有多个order_goods_id
    foreach ($back_goods as $back_item) {
    	if($back_item['amount'] != 0){
    		// 获取一些商品信息
	        $goods_id = $back_item['goods_id'];         // goods_id
	        $style_id = $back_item['style_id'];         // style_id
	        $goods_name = $back_item['goods_name'];     // goods_name
	        $amount = $back_item['amount'];             // goods_number
	        $market_price = $back_item['market_price']; // market_price
	        $goods_price = $back_item['goods_price'];   // goods_price
			$goods_name = addslashes($goods_name);
			
			// 获取该商品-t订单对应的order_goods_id(rec_id)。注，这里将条件限制得很死，是因为同一种商品可能会有多条ecs_order_goods记录
			$back_order_goods_id = 0;
			$sql = "SELECT *
					FROM ecshop.ecs_order_goods
					WHERE order_id = '{$back_order['order_id']}'
					  AND goods_id = '{$goods_id}'
					  AND style_id = '{$style_id}'
					  AND goods_number = '{$amount}'
					  AND market_price = '{$market_price}'
					  AND goods_price = '{$goods_price}'";
			$back_order_goods = $db->getAll($sql);
			if(!empty($back_order_goods)){
				foreach($back_order_goods as $back_order_good){
					if(!in_array($back_order_good['rec_id'],$back_order_goods_ids)){
						$back_order_goods_id = $back_order_good['rec_id'];
						array_push($back_order_goods_ids, $back_order_goods_id);
						break;
					}
				}
				if($back_order_goods_id == 0){
					return false;
				}
			}else{
				return false;
			}
			
	        // 入库
	        include_once (ROOT_PATH . "RomeoApi/lib_inventory.php");
	       	$sql = "SELECT ii.inventory_item_acct_type_id, ii.unit_cost, ii.provider_id
	             	FROM romeo.inventory_item ii
	              	INNER JOIN romeo.inventory_item_detail iid ON iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
					WHERE iid.ORDER_GOODS_ID = '{$back_item['rec_id']}' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE' LIMIT 1";
			$in_erp_info = $db->getRow($sql);
			if(!$in_erp_info){
				return false;
			}
			$status = createAcceptInventoryTransactionNew('ITT_SO_RET',
	                                             array('goods_id'=>$goods_id, 'style_id'=>$style_id),
	                                             $amount, '', $in_erp_info['inventory_item_acct_type_id'], $back_order['order_id'],
	                                             '', 'INV_STTS_AVAILABLE',
	                                             $in_erp_info['unit_cost'], $back_order_goods_id,
	                                             $back_order['facility_id'], $in_erp_info['provider_id']);
	        if($status != 'OK'){
		    	return false;
			}
	    }
    }

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);

    return true;
	
}
/**
 * 获得商品单品的折扣分配(废老库存)
 * 
 * @author zjli at 2014.2.17
 * 
 * @param int $service_order_goods_id  ecshop.service_order_goods表ID
 * @param int $order_goods_id  ecshop.ecs_order_goods表ID 
 * @param int $order_id  ecshop.ecs_order_info表ID
 * 
 * @return array $discount_fee_detail
 */
function get_single_discount_fee_detail_new($service_order_goods_id,$order_goods_id, $order_id){
	global $ecs, $db;
	
	// 初始化函数最终返回结果-array
	$discount_fee_detail = array();
	
	// 判断参数是否传过来
	if(empty($service_order_goods_id) || empty($order_goods_id) || empty($order_id))
	    return false;
	
	// 获取商品的单价
	$sql = "select og.*
		    from ecshop.ecs_order_goods og
		    where og.rec_id = '{$order_goods_id}'";
    $tmp = $db->getRow($sql);
    if(!$tmp){
    	return false;
    }
	$goods_price = $tmp['goods_price'];
	$goods_id  = $tmp['goods_id'];
	$style_id = $tmp['style_id'];
	
	//取出商品的折扣
	$sql = "
	        select oga.value discount_fee, og.goods_number
			from ecshop.ecs_order_goods og
			left join ecshop.order_goods_attribute oga on oga.order_goods_id = og.rec_id and oga.name = 'DISCOUNT_FEE'
			where og.rec_id = '{$order_goods_id}' and og.order_id = '{$order_id}'
	       ";
    $item_discount = $db->getRow($sql);
    //若商品无折扣，设置折扣为0
    if(empty($item_discount['discount_fee'])){
    	$item_discount['discount_fee'] = 0;
    }
    
    //取出所有的商品折扣
    $sql = "
           select sum(oga.value)
           from ecshop.order_goods_attribute oga 
           inner join ecshop.ecs_order_goods og on oga.order_goods_id = og.rec_id
           where og.order_id = '{$order_id}' and oga.name = 'DISCOUNT_FEE'";
    $total_item_discount = $db -> getOne($sql);
    //商品无折扣，则默认所有商品级别的折扣为0
    if(!$total_item_discount){
    	$total_item_discount = 0;
    }
    
    //取出订单中的折扣
    $sql = "select oa.attr_value discount_fee, oi.goods_amount
            from ecshop.order_attribute oa
            inner join ecshop.ecs_order_info oi on oi.order_id = oa.order_id
            where oa.attr_name = 'DISCOUNT_FEE' 
            and oi.order_id = '{$order_id}'";
    $order_discount = $db->getRow($sql);
    
    // 初始化变量
    $single_discount_fee = 0;
    $single_item_discount_fee = 0;
    $single_order_discount_fee = 0;
	
	// 获取已经操作过-t的该种商品的数量
	$sql = "SELECT sum(sog.amount)
			FROM ecshop.order_relation eor
                        INNER JOIN ecshop.service s on eor.parent_order_id = s.order_id and s.back_order_id = eor.order_id
			INNER JOIN ecshop.service_order_goods sog ON s.service_id = sog.service_id 
			WHERE eor.parent_order_id = '{$order_id}' and sog.order_goods_id = '{$order_goods_id}'";
	$other_service_amount = $db->getOne($sql);
	if(!$other_service_amount){
		$other_service_amount = 0;
	}
	
	// 获取本次售后服务申请的商品数量
	$sql = "SELECT sog.amount
			FROM ecshop.service_order_goods sog
			WHERE sog.service_order_goods_id = '{$service_order_goods_id}' LIMIT 1";
	$this_service_amount = $db->getOne($sql);
	if(!$this_service_amount || $this_service_amount <= 0){
		return false;
	}

	//商品级别的折扣计算
	if($item_discount['discount_fee'] != 0){
   		if($item_discount['goods_number'] == ($other_service_amount + $this_service_amount)){  // 如果这是该种商品最后一批申请售后服务的
    		$single_discount_fee = $item_discount['discount_fee'] - round($item_discount['discount_fee']/$item_discount['goods_number'],2)*($item_discount['goods_number']-$this_service_amount);
    	}else if($item_discount['goods_number'] > ($other_service_amount + $this_service_amount)){
    		$single_discount_fee = round($item_discount['discount_fee']/$item_discount['goods_number'],2)*$this_service_amount;
       	}else{
       		return false;
       	}
    	$single_item_discount_fee = $single_discount_fee;
   	}
   	
   	//订单级别的折扣计算，特殊情况为该订单的最后一批商品，得进行订单级别的折扣修正
   	if($order_discount['discount_fee'] != 0 && $order_discount['goods_amount'] !=0){
        // 获得该订单中商品的总数量
        $sql = "SELECT SUM(goods_number)
        		FROM ecshop.ecs_order_goods
        		WHERE order_id = '{$order_id}'";
        $total_goods_amount = $db->getOne($sql);
        if(!$total_goods_amount || $total_goods_amount <= 0){
        	return false;
        }
        
        // 获得该订单中已经操作过-t的商品数量
        $sql = "SELECT sum(sog.amount)
			FROM ecshop.order_relation eor
			INNER JOIN ecshop.service s on eor.parent_order_id = s.order_id and s.back_order_id = eor.order_id
			INNER JOIN ecshop.service_order_goods sog ON s.service_id = sog.service_id 
			WHERE eor.parent_order_id = '{$order_id}'";
		$total_other_service_goods_amount = $db->getOne($sql);
		if(!$total_other_service_goods_amount){
			$total_other_service_goods_amount = 0;
		}
        
   		if($total_goods_amount == ($total_other_service_goods_amount + $this_service_amount)){
   			$sql = "
   			    select sum(cast(oa.attr_value*((og.goods_price-cast(oga.value/og.goods_number as decimal(17,2)))/(oi.goods_amount-{$total_item_discount})) as decimal(17,2))*og.goods_number)
				from ecshop.ecs_order_info oi
				inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id
				inner join ecshop.order_attribute oa on oa.order_id = oi.order_id and oa.attr_name = 'DISCOUNT_FEE'
				inner join ecshop.order_goods_attribute oga on oga.order_goods_id = og.rec_id and oga.name = 'DISCOUNT_FEE' 
				where oi.order_id = '{$order_id}'";
   			$order_discount_fee = $db->getOne($sql);
   			//查不到，则证明商品删除完了，于是该商品订单级别的折扣就按照一般的商品来算
   			if($order_discount_fee){
   				$single_discount_fee += ($this_service_amount*round($order_discount['discount_fee']*($goods_price-round($item_discount['discount_fee']/$item_discount['goods_number'],2))/($order_discount['goods_amount']-$total_item_discount),2)+$order_discount['discount_fee']-$order_discount_fee);
   			}else{
   				$single_discount_fee += ($this_service_amount*round($order_discount['discount_fee']*($goods_price-round($item_discount['discount_fee']/$item_discount['goods_number'],2))/($order_discount['goods_amount']-$total_item_discount),2));
   			}
   		}else if($total_goods_amount > ($total_other_service_goods_amount + $this_service_amount)){
   			$single_discount_fee += ($this_service_amount*round($order_discount['discount_fee']*($goods_price-round($item_discount['discount_fee']/$item_discount['goods_number'],2))/($order_discount['goods_amount']-$total_item_discount),2));
   		}else{
   			return false;
   		}
   	}
   	$discount_fee_detail = array("order_goods_id"=>$order_goods_id,"single_discount_fee"=>$single_discount_fee,"single_item_discount_fee"=>$single_item_discount_fee);
    return $discount_fee_detail;
}

/**
 * 生成换货-h订单(废除老库存逻辑)
 * 
 * @author zjli at 2014.2.24
 *
 * @param int service_id
 * 
 * @return int 生成-h订单的order_id
 */
function generate_change_order_new($service_id) {
    global $ecs, $db;
    $sqls = array ();
    
    // ---------------------------- 1.获取原始销售订单的相关信息 -------------------------------
	$change_order_sn = "";  // 初始化即将生成的换货订单的sn号
	$sql = "SELECT o.*
			FROM ecshop.service s
			INNER JOIN ecshop.ecs_order_info o ON s.order_id = o.order_id
			WHERE s.service_id = '{$service_id}' LIMIT 1";
	$sqls[] = $sql;
	$origin_order = $db->getRow($sql);
	if(!$origin_order){
		return false;
	}else{
		// 为即将生成的换货订单命名订单号sn
		$change_order_sn = $origin_order['order_sn']."-h";
	}
	
	while (1) { //生成订单中没有的订单号。
        $sql = "SELECT COUNT(order_id) FROM {$ecs->table('order_info')} WHERE order_sn = '{$change_order_sn}' ";
        if ($db->getOne($sql) == 0) {
            break;
        } else {
            $change_order_sn .= "-h";
        }
    }
	
	// -------------------------------- 2.获得售后服务的商品信息 ------------------------------------
    $sql = "SELECT sog.*, og.*,IFNULL(oga.value,0) as goods_discount_fee
           FROM ecshop.service_order_goods sog
           INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
           LEFT JOIN ecshop.order_goods_attribute oga ON oga.order_goods_id = og.rec_id and oga.name = 'DISCOUNT_FEE'
           WHERE sog.is_approved = 1 AND sog.service_id  = '{$service_id}' ";
    $sqls[] = $sql;
    $change_goods_list = $db->getAll($sql);
	
	// 如果找不到申请售后的商品信息，则返回false 
    if (!$change_goods_list) {
        return false;
    }

	// 计算累计商品的价格
    $goods_amount = 0;
    foreach ($change_goods_list as $change_goods) {
        $goods_amount += $change_goods['goods_price'] * $change_goods['amount'];
    }

	// 获取换货订单的生成时间
    $order_time = date("Y-m-d H:i:s");

    // 获得换货订单的shipping_id 和 carrier_id
    $shipping_id = convert_cod_shipping($origin_order['shipping_id']);

    $shipping = $db->getRow("SELECT shipping_name, default_carrier_id
                             FROM {$ecs->table('shipping')}
                             WHERE shipping_id = '$shipping_id' LIMIT 1 ");

    $default_carrier_id  = $shipping['default_carrier_id'];
    $shipping_name  = $shipping['shipping_name'];

    // killed by Sinri 20160105    
    // $sql = " INSERT INTO {$ecs->table('carrier_bill')} (carrier_id) VALUES($default_carrier_id) ";
    // $db->query($sql);
    // if ($db->affected_rows() != 1) {
    //     return false;
    // }
    // $carrier_bill_id = $db->insert_id();

    $carrier_bill_id=0;
    
    //得到-h订单的支付方式，默认是原始订单的pay_id，否则暂时挂在支付宝上
    $pay_id = $origin_order['pay_id'];
    $is_cod = $db->getOne("SELECT is_cod FROM {$ecs->table('payment')} WHERE pay_id = '{$pay_id}' ");
    $pay_id = $is_cod ? 5 : $pay_id;
    $pay_name = $is_cod ? '支付宝' : $origin_order['pay_name'];
    
    // 获得对应退货订单的信息
    $sql = "SELECT o.order_amount, o.goods_amount, o.shipping_fee, o.pack_fee, o.bonus, o.misc_fee, s.facility_id, IFNULL(oa.attr_value, 0.00) as back_order_bonus
            FROM {$ecs->table('order_info')} o 
            INNER JOIN service s ON o.order_id = s.back_order_id 
            LEFT JOIN ecshop.order_attribute oa ON oa.order_id = o.order_id AND oa.attr_name = 'DISCOUNT_FEE'
            WHERE service_id = '$service_id' ";
    $back_order = $db->getRow($sql);
    $facility_id = $back_order['facility_id'];
    
    // -h订单的shipping_fee pack_fee bonus 和-t订单一样
    $shipping_fee = $back_order['shipping_fee'];
    $pack_fee = $back_order['pack_fee'];
    $bonus = $back_order['bonus'];
    // -t订单级别的折扣
    $back_order_bonus = $back_order['back_order_bonus'];
    
    // 杂项费用默认是为0 的，客服可能会修改
    $misc_fee = 0;
    
    // 计算 -h 订单的订单金额
    $order_amount = $shipping_fee + max($goods_amount + $pack_fee + $bonus, 0);
    
    //计算用户还应付的钱= order_amount(-h) + order_amount(-t) 
    $additional_amount = $order_amount + $misc_fee + $back_order['order_amount'] - $back_order['misc_fee'];

    //生成订单
    $sql = "INSERT INTO {$ecs->table('order_info')} ".
           " (order_sn, user_id, order_time, order_status, consignee, ".
           " country, province, city, district, address, zipcode, ".
           " tel, mobile, email, ".
           " shipping_id, pay_id, shipping_name, pay_name, ".
           " shipping_fee, bonus, pack_fee, goods_amount, order_amount, additional_amount, ".
           " biaoju_store_id, inv_payee, inv_address, carrier_bill_id, ".
           " party_id, distributor_id, facility_id, currency, order_type_id) ".
           " VALUES('{$change_order_sn}', '{$origin_order['user_id']}', '$order_time', 0, 
           '{$origin_order['consignee']}', 
           '{$origin_order['country']}', '{$origin_order['province']}', '{$origin_order['city']}', 
           '{$origin_order['district']}', '{$origin_order['address']}', '{$origin_order['zipcode']}', 
           '{$origin_order['tel']}', '{$origin_order['mobile']}', '{$origin_order['email']}', 
           '{$shipping_id}', '{$pay_id}', '{$shipping_name}',  '{$pay_name}',
           '{$shipping_fee}', '{$bonus}', '{$pack_fee}', '{$goods_amount}', '{$order_amount}',
           '{$additional_amount}', 
           '{$origin_order['biaoju_store_id']}', '{$origin_order['inv_payee']}', 
           '{$origin_order['inv_address']}', '{$carrier_bill_id}', 
           '{$origin_order['party_id']}', '{$origin_order['distributor_id']}',
           '{$facility_id}', '{$origin_order['currency']}', 'RMA_EXCHANGE') ";
    $db->query($sql);

    if ($db->affected_rows() != 1) {
        return false;
    }

    $change_order_id = $db->insert_id();
    $sqls[] = $sql;
    
    //添加相关的-h订单级别的折扣信息 added by zjli for OR at 2014.11.07
    $change_order_bonus = abs($back_order_bonus);
    $sql = "insert into ecshop.order_attribute
    (order_id,attr_name,attr_value)
    values('{$change_order_id}','DISCOUNT_FEE','{$change_order_bonus}')";
    $sqls[] = $sql;
    if(!$db->query($sql)){
    	return false;
    }

    //增加记录订单关系 added by zwsun 2009年7月9日10:56:34
    require_once ('lib_order.php');
    if(!add_order_relation($change_order_id, $origin_order['order_id'], '', $change_order_sn, $origin_order['order_sn'])){
    	return false;
    }

    foreach ($change_goods_list as $change_goods) {
        // 插入商品记录
        if($change_goods['amount'] > 0){
        	$change_goods['goods_name'] = addslashes($change_goods['goods_name']);
	        $sql = "INSERT INTO {$ecs->table('order_goods')} (order_id, goods_id, style_id, goods_name, goods_number, market_price, goods_price, biaoju_store_goods_id,discount_fee)
	                      values('{$change_order_id}', '{$change_goods['goods_id']}', '{$change_goods['style_id']}', '{$change_goods['goods_name']}', '{$change_goods['amount']}', '{$change_goods['market_price']}', '{$change_goods['goods_price']}', '{$change_goods['biaoju_store_goods_id']}', '{$change_goods['goods_discount_fee']}')";
	        $db->query($sql);
	        if ($db->affected_rows() != 1) {
	            return false;
	        }
	        $sqls[] = $sql;
	        
	        $order_goods_id = $db->insert_id();
	        if($change_goods['goods_discount_fee'] > 0) {
	        	$sql = "insert into ecshop.order_goods_attribute
        		        (order_goods_id,name,value)
        		        values('{$order_goods_id}','DISCOUNT_FEE','{$change_goods['goods_discount_fee']}')";
        		$sqls[] = $sql;
        		if(!$db->query($sql)){
			    	return false;
			    }
	        }
        }
       
    }
    //}}}生成再次发货订单 end

    // 添加配送 20101217 yxiang
    if (!function_exists('soap_get_client')) {
    	require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
    }
//    try {
//        $handle=soap_get_client('ShipmentService');
//        $handle->createShipmentForOrder(array(
//            'orderId'=>$change_order_id,
//            'carrierId'=>$default_carrier_id,
//            'shipmentTypeId'=>$shipping_id,
//            'partyId'=>$origin_order['party_id'],
//            'createdByUserLogin'=>$_SESSION['admin_name'],
//        ));
//    }
//    catch (Exception $e) {
//		return false;
//    }
        
    if ($change_order_id) {
        $sql = "UPDATE service SET change_order_id = '{$change_order_id}' WHERE service_id = '{$service_id}' LIMIT 1 ";
        if(!$db->query($sql)){
        	return false;
        }
        
        // 取得支付方式名
        $order_type = $is_cod ? 'COD' : 'NON-COD';
        // require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
        // if(!insert_order_mixed_status($change_order_id, $order_type, 'worker')){
        // 	return false;
        // }
    }

    //SINRI UPDATE POSTSALE CACHe
    POSTSALE_CACHE_updateService(null,180,$service_id);

    return $change_order_id;
}

function service_status_price_protected($service) {
    extract($service);
    global $service_type_mapping, $service_status_mapping, $service_call_status_mapping, $back_shipping_status_mapping, $outer_check_status_mapping, $inner_check_status_mapping, $change_shipping_status_mapping, $service_pay_status_mapping;
    if ($service_type != SERVICE_TYPE_PRICE_PROTECTED) {
        return "";
    }

    $status_name = "";
    if ($service_status == SERVICE_STATUS_PENDING) { //如果是待审核的，很好给出状态名
        $status_name = $service_type_mapping[$service_type] . "," . $service_status_mapping[$service_status];
    }

    elseif ($service_status == SERVICE_STATUS_OK) { //如果是审核通过的
        if ($is_backbonus) {
            $status_name = "审核通过,已发红包到用户账号";
        } else {
            $status_name = "审核通过,退款信息已确认,待退款";
            if ($service_pay_status) {
                $status_name = $service_pay_status_mapping[$service_pay_status];
            }
        }
    }

    elseif ($service_status == SERVICE_STATUS_DENIED) {
        $status_name = '已回访,审核未通过,申请结束';
        //    if ($service_call_status == SERVICE_CALL_STATUS_NEEDCALL) { //待回访
        //      $status_name = $service_status_mapping[$service_status].",".$service_call_status_mapping[$service_call_status];
        //    } elseif ($service_call_status == SERVICE_CALL_STATUS_CALLED) { //已回访
        //      $status_name = $service_call_status_mapping[$service_call_status].",".$service_status_mapping[$service_status];
        //    }
    }

    return $status_name;
}

function service_status_re_send($service) {
    extract($service);
    global $service_type_mapping, $service_status_mapping, $service_call_status_mapping, $back_shipping_status_mapping, $outer_check_status_mapping, $inner_check_status_mapping, $change_shipping_status_mapping, $service_pay_status_mapping;

    if ($service_type != SERVICE_TYPE_RE_SEND) {
        return "";
    }

    if ($service_status == SERVICE_STATUS_PENDING) {
        $status_name = "漏寄申请,待回访";
    }

    elseif ($service_status == SERVICE_STATUS_OK) {
        $status_name = "已回访,待补寄";
        //    if ($change_order_id) {
        //        $status_name = "漏寄订单已确认,待配货";
        //    }
        if ($change_shipping_status) {
            $status_name = $change_shipping_status_mapping[$change_shipping_status];
        }
    }

    elseif ($service_status == SERVICE_STATUS_DENIED) {
        $status_name = "已回访,审核未通过,申请结束";
    }
    return $status_name;
}

function service_status_change($service) {
    return service_status_change_or_back($service);
}

function service_status_back($service) {
    return service_status_change_or_back($service);
}
// 返修状态
function service_status_warranty($service) {
    return service_status_change_or_back($service);
}

function service_status_change_or_back($service) {
    extract($service);
    require_once(ROOT_PATH . 'admin/config.vars.php');
    global $service_type_mapping, $service_status_mapping, $service_call_status_mapping, $back_shipping_status_mapping, $outer_check_status_mapping, $inner_check_status_mapping, $change_shipping_status_mapping, $service_pay_status_mapping, $warranty_check_status_mapping;
    $status_name = "";

    
    if (!in_array($service_type, array(
    		SERVICE_TYPE_CHANGE,
    		SERVICE_TYPE_BACK,
    		SERVICE_TYPE_WARRANTY))) {
        return "";
    }

    if ($service_status == SERVICE_STATUS_PENDING) { //待审核
        $status_name = $service_type_mapping[$service_type] . "," . $service_status_mapping[$service_status];
    }

    elseif ($service_status == SERVICE_STATUS_REVIEWING) { //已审核
        $status_name = "已审核,待退货";

        if ($back_shipping_status) {
            $status_name = $back_shipping_status_mapping[$back_shipping_status];
        }
        if ($inner_check_status) {
            $status_name = $inner_check_status_mapping[$inner_check_status];
        }
        if ($outer_check_status) {
            $status_name = $outer_check_status_mapping[$outer_check_status];
        }
        if ($warranty_check_status) {
            $status_name = $warranty_check_status_mapping[$warranty_check_status];
        }
    }

    elseif ($service_status == SERVICE_STATUS_OK) {
        if ($service_call_status == SERVICE_CALL_STATUS_NEEDCALL) { //待回访

            $service_tmp = $service_status_mapping[SERVICE_STATUS_OK]; //默认是 审核通过
            $tipstr = $service_call_status_mapping[SERVICE_CALL_STATUS_NEEDCALL]; //默认是 待回访

            if ($inner_check_status) { //如果能够显示相关检测状态就更好
                $service_tmp = $inner_check_status_mapping[$inner_check_status];
            }
            elseif ($outer_check_status) { //如果能够显示相关验货状态就更好
                $service_tmp = $outer_check_status_mapping[$outer_check_status];
            }

            if ($service_type == SERVICE_TYPE_CHANGE && $inner_check_status) { // 如果是我们自己检查，形容词将是具体的
                $tipstr = "已入库,待确认换货信息";
            }
            elseif ($service_type == SERVICE_TYPE_BACK && $inner_check_status) { // 如果是我们自己检查，形容词将是具体的
                $tipstr = "已入库,待确认退款信息";
            }
            elseif ($service_type == SERVICE_TYPE_WARRANTY && $inner_check_status) {
             // 如果是我们自己检查，形容词将是具体的
                $tipstr = "已入库,待确认保修信息";
            }

            $status_name = $service_tmp . "," . $tipstr;

        }
        elseif ($service_call_status == SERVICE_CALL_STATUS_CALLED) { //已经回访过了

            if ($service_type == SERVICE_TYPE_CHANGE) {
                $status_name = "换货信息已确认,待配货";
            }
            elseif ($service_type == SERVICE_TYPE_BACK) {
                $status_name = "退款信息已确认,待退款";
            }
            elseif ($service_type == SERVICE_TYPE_WARRANTY) {
                /**
                 * 44 => "已发货,待签收", 
                 * 45 => "已签收",
                 */
                $status_name = "返修信息已确认,待配货";
                if ($warranty_shipping_status == 44) {
                    $status_name = "已发货,待签收";
                } elseif ($warranty_shipping_status == 45) {
                    $status_name = "用户已签收";
                }
            }
            if ($outer_check_status) { //如果是送到外面检测的， 在前面加上已回访
                $status_name = $service_call_status_mapping[SERVICE_CALL_STATUS_CALLED] . "," . $status_name;
            }
        }

        if ($change_shipping_status && !$service_pay_status) { //如果开始换货了
            $status_name = $change_shipping_status_mapping[$change_shipping_status];
        }
        elseif ($service_pay_status) { //如果是有退款信息
            $status_name = $service_pay_status_mapping[$service_pay_status];
        }
    }

    elseif ($service_status == SERVICE_STATUS_DENIED) { //审核被拒绝
        $status_name = $service_status_mapping[SERVICE_STATUS_DENIED];
        if ($service_call_status == SERVICE_CALL_STATUS_NEEDCALL) {
            if ($inner_check_status) {
                $status_name = $inner_check_status_mapping[$inner_check_status];
            }
            elseif ($outer_check_status) {
                $status_name = $outer_check_status_mapping[$outer_check_status];
            } else {
                $status_name = $service_status_mapping[SERVICE_STATUS_DENIED];
            }
            $status_name .= ",待回访";
        }
        elseif ($service_call_status == SERVICE_CALL_STATUS_CALLED) {
            $status_name = "已回访,申请未通过,待货物原样寄回";
        } else { //直接拒绝的情形
            $status_name = "已回访,审核未通过,申请结束 ";
        }

        if ($change_shipping_status) { //货物寄回去的状态
            $status_name = $change_shipping_status_mapping[$change_shipping_status];
        }
    }

    return $status_name;

}

function service_status($service) {
    $status_name = "";
    switch ($service['service_type']) {
        case SERVICE_TYPE_CHANGE :
            $status_name = service_status_change($service);
            break;
        case SERVICE_TYPE_BACK :
            $status_name = service_status_back($service);
            break;
        case SERVICE_TYPE_PRICE_PROTECTED :
            $status_name = service_status_price_protected($service);
            break;
        case SERVICE_TYPE_RE_SEND :
            $status_name = service_status_re_send($service);
            break;
        case SERVICE_TYPE_WARRANTY :
            $status_name = service_status_warranty($service);
            break;
        default :
            break;
    }
    return $status_name;
}

/**
 * 将cod的配送方式换成非cod的配送方式
 * 
 * @param $shipping_id
 * @return int 
 */
function convert_cod_shipping($shipping_id) {
    //换货，补货订单统一使用先款后货的发送方式配送
    $shipping_id_mapping = array(
        '36' => '47',
        '47' => '47',
        '44' => '44',
        '49' => '44',
        '48' => '51',
        '51' => '51',
        '85' => '85',
        '11' => '85',
        '88' => '87',
        '102' => '85',
    );
    
    $result = $shipping_id_mapping[$shipping_id];
    return $result ? $result : $shipping_id;    
}

function get_back_order_id ($service_id) {
	global $db;
	if (! $service_id) {
		return 0;
	}
	
	$sql = "select back_order_id from ecshop.service where service_id = $service_id";
	return $db->getOne($sql);
}

/**
 *  通过新库存检查-t订单是否入库完 zjli 2014.03.18
 * 
 *  @param string $order_id
 */
function check_back_order_all_in_storage($order_id) {
	global $db, $ecs;
	$sql = "select og.goods_number,ifnull(sum(iid.quantity_on_hand_diff),0) as in_total
	       from ecshop.ecs_order_goods og
	       left join romeo.inventory_item_detail iid ON convert(og.rec_id using utf8) = iid.order_goods_id
	       where og.order_id = '{$order_id}'  group by og.order_id
	       ";
	$res = $db->getRow($sql);

	if(($res['goods_number'] - $res['in_total']) > 0 ) {
		return false;
	}else {
		return true;
	}
}


/**
 * 通过登记直接验货通过/拒绝
 */
function regist_back_goods_pass_reject($trackingNumber,$innerCheck,$warehouse_service,$orderSn,$scanOrderGoods,$ReturnFacilityId,$applyReturnReason=""){
	global $db;
	// 检查扫描商品数
	$amount_sum = 0;
	foreach($scanOrderGoods as $rec_id => $scanOrderGood){
		if(empty($scanOrderGood['goods_back_num']) || (int)$scanOrderGood['goods_back_num']==0){
			unset($scanOrderGoods[$rec_id]);
			continue;
		}
		if(count($scanOrderGood['goods_type']) != (int)$scanOrderGood['goods_back_num'] || 
			($scanOrderGood['is_serial']=='Y' && count($scanOrderGood['serial_numbers']) != (int)$scanOrderGood['goods_back_num'])
		){
			sys_msg("扫描商品数量异常",1);
		}
		$amount_sum += $scanOrderGood['goods_back_num'];
	}
	if ($amount_sum <= 0 ) {
		sys_msg("扫描获取商品数量<=0,请重新扫描",1);
	}
	$ref_fields_original_goods = $refs_original_goods = array();
    // 检查输入的订单号
    $sql = "SELECT o.order_id, o.party_id, o.user_id, u.user_name, og.rec_id AS order_goods_id, 
			o.facility_id, o.order_status, o.shipping_status, o.pay_status, o.order_sn,og.goods_id,og.style_id, 
			ifnull(gs.barcode,g.barcode) as goods_barcode,og.goods_number,og.goods_name
		FROM ecshop.ecs_order_info o
		INNER JOIN ecshop.ecs_order_goods og ON o.order_id = og.order_id 
		INNER JOIN ecshop.ecs_users u ON o.user_id = u.user_id
		left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0
		left join ecshop.ecs_goods g on g.goods_id = og.goods_id
		WHERE o.order_sn = '{$orderSn}' ";
    $original_order_goods = $db->getAllRefby($sql,array('order_goods_id'),$ref_fields_original_goods, $refs_original_goods, false); 
    
    if (!$original_order_goods) {
		sys_msg("原始销售订单商品信息未查到",1);
    }
    // 检查订单的状态
    $o = reset($original_order_goods);
    $party_id = $o['party_id'];
    if ($o['order_status']!=1 || $o['shipping_status']!=1) {
		sys_msg("原销售订单并非已确认,已发货状态",1);
    }
    
    // 判断order_goods_id与传入的order_id是否相符
    $real_order_goods_ids = array();
    foreach($original_order_goods as $order_good){
		array_push($real_order_goods_ids,$order_good['order_goods_id']);
	}
    foreach ($scanOrderGoods as $order_goods_id => $scanOrderGood) {
    	if(!in_array($order_goods_id, $real_order_goods_ids)){
			sys_msg("扫描商品与原销售订单商品不符",1);
    	}
    	// 检查输入的商品数量是否合法，避免由于客服并发操作引起的问题 
    	if($scanOrderGood['goods_back_num'] > 0){
    		// 该订单中该商品已建立过售后服务申请的数量 (Still Improvements Needed)
			$sql = "SELECT SUM(amount) FROM ecshop.service_order_goods sog
				INNER JOIN ecshop.service s ON s.service_id = sog.service_id
				WHERE sog.order_goods_id = {$order_goods_id}
				    AND ((s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 1 AND sog.is_approved = 1) 
			        OR (s.is_complete = 0 AND s.inner_check_status = 0 AND s.service_status = 0 AND sog.is_approved = 0)
			        OR (s.is_complete = 0 AND s.inner_check_status = 32 AND s.service_status = 2 AND sog.is_approved = 1))";
			$amount_in_service = $db->getOne($sql);
			// 该订单中该商品还可以建立售后服务申请的数量
			$service_amount_available = $refs_original_goods['order_goods_id'][$order_goods_id][0]['goods_number'] - $amount_in_service;
			// 如果申请售后的商品数量超过可以建立售后服务申请的数量，则拒绝建立申请
			if($scanOrderGood['goods_back_num'] > $service_amount_available){
				sys_msg("输入的商品数量超过了可以申请的数量，请刷新页面",1);
			}
    	}
    }
    $user_id =$original_order_goods[0]['user_id'];
    $order_id= $original_order_goods[0]['order_id'];
	$db->start_transaction();
	/**
	 * 1. update ecshop.warehouse_service(INIT->CHECKED)
	 * 2. insert into ecshop.warehouse_service_goods
	 * 3. insert into ecshop.service
	 * 4. insert into ecshop.service_order_goods
	 * 5. insert into ecshop.ecs_order_info 
	 * 6. insert into ecshop.ecs_order_goods
	 */
    // 插入一条售后服务记录 service
    $service = array (
        'user_id' => $user_id,
        'order_id' => $order_id,
        'service_type' => 2,  //退货申请
        'service_status' => 1,           
        'apply_username' => $original_order_goods[0]['user_name'],
        'apply_reason' => '【仓库登记验货】发起售后申请',
        'apply_datetime' => date('Y-m-d H:i:s'),
        'facility_id' => $ReturnFacilityId,
    	'party_id'=> $party_id,
  		'responsible_party' => 1, //责任方：1乐其 ，2厂家 ，3顾客，4快递公司，5乐麦
  		'dispose_method' => 1 , //处理方式：1 退货 ，2换货，5错发，6漏发，7虚拟入库，8追回，9拒收，4 其他
    	'dispose_description' => '', //其他处理方式
    );
    $db->autoExecute('service', $service);
    $service_id = $db->insert_id();
    if(!$service_id || $service_id <= 0){
    	$db->rollback();
		sys_msg("【售后申请】自动创建失败",1);
    }
    
    //插入售后的商品记录 service_order_goods
    $sql_values = array ();
    foreach ($scanOrderGoods as $order_goods_id => $scanOrderGood) {
    	if($scanOrderGood['goods_back_num'] > 0){
    		$amount = $scanOrderGood['goods_back_num'];
    		$sql_values[] = sprintf("('%d', '%d', '%d', '%d', '%d', '%d')", $service_id, $user_id, $order_id, $order_goods_id, $amount,1);
    	}
    }
    $sql = sprintf("INSERT INTO ecshop.service_order_goods(service_id, user_id, order_id, order_goods_id, amount, is_approved)
                             VALUES %s", join(",", $sql_values));
                             
    if(!$db->query($sql)){
    	$db->rollback();
		sys_msg("【售后申请】商品插入失败",1);
    }

    //用户返回的相关信息（登记退回快递信息等）
    $sql = "INSERT INTO ecshop.service_return( service_id, return_name, return_value, return_type) VALUES 
    		({$service_id}, 'deliver_number', '{$trackingNumber}','carrier_info') ";
    if(!$db->query($sql)){
    	$db->rollback();
		sys_msg("【售后申请】退回登记信息插入失败",1);
	}
    
    //记录售后的日志
    $service = array (
        'service_id' => $service_id,
        'service_status' => 0,
        'service_type' => 2,
        'log_note' =>  "【仓库登记验货】系统申请售后服务",
        'log_type' => 'CUSTOMER_SERVICE',
        'is_remark' => 0
    );
        
    $result = service_log($service);
    if(!$result){
    	$db->rollback();
		sys_msg("【售后申请】操作记录插入失败",1);
    }
	
	//自动同意退回--------start   
	//生成-t订单
    $t_order_id = generate_back_order_new($service_id);
    
    $datetime = date('Y-m-d H:i:s');
    if ($t_order_id > 0) {
		$log_note = " 系统自动 {$datetime} 同意退回货物，生成退货订单";
	} else {
		$db->rollback();
		sys_msg("【售后审核】自动创建退货订单失败",1);
	}
	
	//同意退回日志	
    $service_log = array (
        'service_id' => $service_id,
        'service_status' => 1,
        'service_type' => 2,
        'log_note' =>  $log_note,
        'log_type' => 'CUSTOMER_SERVICE',
        'is_remark' => 0
    );       
    $result = service_log($service_log);	
    if(!$result){
    	$db->rollback();
		sys_msg("【售后审核】操作记录插入失败",1);
    }
  	//自动同意退回--------end
	
	
	//插入ecshop.warehouse_service_goods 记录
	$sql_values = array ();
    $warehouse_service_id = $warehouse_service['warehouse_service_id'];
    foreach ($scanOrderGoods as $order_goods_id => $scanOrderGood) {
    	if($scanOrderGood['goods_back_num'] > 0){
    		//
    		$goods_sql = "select gs.goods_id,gs.style_id,g.goods_name from ecshop.ecs_goods_style gs left join ecshop.ecs_goods g on g.goods_id = gs.goods_id where gs.barcode = '{$scanOrderGood['barcode']}' and gs.is_delete=0 limit 1";
    		$goods_info = $db->getRow($goods_sql);
    		if(empty($goods_info)){
    			$goods_sql = "select goods_id,0 as style_id,goods_name from ecshop.ecs_goods where barcode = '{$scanOrderGood['barcode']}' limit 1";
    			$goods_info = $db->getRow($goods_sql);
    		}
    		$amount = $scanOrderGood['goods_back_num'];
    		
    		if($scanOrderGood['is_serial']!='Y'){
    			$old_number = 0;
    			$new_number = 0;
	    		foreach($scanOrderGood['goods_type'] as $goods_type){
	    			if($goods_type == 'INV_STTS_USED'){
	    				$old_number++;
	    			} else{
	    				$new_number++;
	    			}
	    		}
	    		if($old_number>0){
	    			$sql_values[] = sprintf("('%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s')", 
	    			$warehouse_service_id, $order_goods_id,$goods_info['goods_id'],$goods_info['style_id'],$goods_info['goods_name'], $old_number, 'INV_STTS_USED', '');
	    		}
	    		if($new_number >0){
	    			$sql_values[] = sprintf("('%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s')", 
	    			$warehouse_service_id, $order_goods_id,$goods_info['goods_id'],$goods_info['style_id'],$goods_info['goods_name'], $new_number, 'INV_STTS_AVAILABLE', '');
	    		}
    		}else{
    			
    			foreach($scanOrderGood['serial_numbers'] as $key=>$serial_numbers){
    				if($scanOrderGood['goods_type'][$key]=='INV_STTS_USED'){
    					$goods_type= 'INV_STTS_USED';
    				}else{
    					$goods_type= 'INV_STTS_AVAILABLE';
    				}
	    			$sql_values[] = sprintf("('%d', '%d', '%d', '%d', '%s', '%d', '%s', '%s')", 
	    			$warehouse_service_id, $order_goods_id,$goods_info['goods_id'],$goods_info['style_id'],$goods_info['goods_name'], 1, $goods_type, $serial_numbers);
	    		}
    		}
    	}
    }
    $sql = sprintf("INSERT INTO ecshop.warehouse_service_goods(warehouse_service_id,original_order_goods_id,goods_id,style_id,goods_name,goods_number,goods_status,serial_number)
            VALUES %s", join(",", $sql_values));
	if(!$db->query($sql)){
		$db->rollback();
		sys_msg("【登记更新】登记商品插入失败",1);
	}
	//更新 ecshop.warehouse_service 
	$sql = "update ecshop.warehouse_service set service_id={$service_id},facility_id = '{$ReturnFacilityId}',original_order_sn='{$orderSn}',
		warehouse_service_status='CHECKED',checker_name = '{$_SESSION['admin_name']}',check_time=now(),remark='{$applyReturnReason}' 
		where warehouse_service_id = {$warehouse_service_id} ";
	if(!$db->query($sql)){
		$db->rollback();
		sys_msg("【登记更新】登记状态更新失败",1);
	}
	//若为拒绝，更新service成功则commit，否则回退；若为验货入库，直接commit
	$result = "failure";
	if($innerCheck == "refuse"){  // 拒绝入库
		$service = $db->getRow("select * from ecshop.service where service_id = {$service_id} ");
		if($service['inner_check_status'] == 33) {
			$sql = "update ecshop.warehouse_service set warehouse_service_status='REJECT',remark='{$applyReturnReason}' where warehouse_service_id = {$warehouse_service_id} ";
			$db->query($sql);	
			$db->commit();
			//重定向
			$result = "success";
			header("Location:warehouse_service.php");
	    } else if($service['inner_check_status'] == 32) {
	    	$db->rollback();
			sys_msg("【自动验货】退货订单已入库，不能操作拒绝",1);
	    }else{
	    	$sql  = "SELECT 1 
					FROM ecshop.service s 
					LEFT JOIN ecshop.ecs_order_goods og ON s.back_order_id = og.order_id
					INNER JOIN romeo.inventory_item_detail iid ON iid.order_goods_id = convert(og.rec_id using utf8) 
					where s.service_id =  {$service_id} and iid.QUANTITY_ON_HAND_DIFF > 0  LIMIT 1 "; 
		    $r = $db->getOne($sql); 
		    if( !empty($r) ){
	         	$db->rollback();
				sys_msg("【自动验货】退货订单已部分入库，不能操作拒绝",1);
		    }
	    }
		if($result != "success"){
			$log_note = $_SESSION['admin_name'] ." 于 {$datetime} 验货未通过";
			// 更新售后服务service的状态
	        $sql = " UPDATE ecshop.service SET inner_check_status = 33,
		                    service_status = '".SERVICE_STATUS_DENIED."', 
		                    service_call_status = '".SERVICE_CALL_STATUS_NEEDCALL."',
		                    check_result = '{$applyReturnReason}'
		                    WHERE service_id = {$service_id} LIMIT 1 ";
		    if(!$db->query($sql)){
		        $db->rollback();
				sys_msg("【售后更新】售后记录更新失败",1);
		    }
		    
		    $sql = "UPDATE ecshop.service_order_goods SET is_approved = 0, amount = 0 WHERE service_id = {$service_id}";
	       	if(!$db->query($sql)){
		        $db->rollback();
				sys_msg("【售后更新】售后商品更新失败",1);
	        }
	        
	        if(!empty($service['back_order_id']) && $service['back_order_id'] != 0){
	        	$sql = "UPDATE ecshop.order_relation SET parent_order_id = 0, root_order_id = 0, parent_order_sn = '', root_order_sn = '' WHERE order_id = '{$service['back_order_id']}'";
	            if(!$db->query($sql)){
	            	$db->rollback();
					sys_msg("【订单关系】退货与原销售订单关系更新失败",1);
	            }
	        }
	        $service['log_note'] = $log_note;
			$service['log_type'] = 'LOGISTIC';
			if(!service_log($service)){
				$db->rollback();
				sys_msg("【售后记录】操作记录更新失败",1);
			}else{
				$sql = "update ecshop.warehouse_service set warehouse_service_status='REJECT',remark='{$applyReturnReason}' where warehouse_service_id = {$warehouse_service_id} ";
				$db->query($sql);	
				require_once('lib_postsale_cache.php');
		        //SINRI UPDATE POSTSALE CACHe
		        POSTSALE_CACHE_updateService(null,180,$service_id);
		        $db->commit();
		        $result = "success";
		        header("Location:warehouse_service.php");
		    }
		}
	}else{
		$db->commit();
		QLOG::LOG("TRANSACTION 1 IS COMMIT!");
	}
	if($innerCheck == "pass"){
		// 验货入库、拒绝入库添加锁
		if(!lock_acquire('update_'.$service_id)) {
			sys_msg("【自动入库】验货入库正在进行，请稍后重试",1);
		}
		// 获取service记录
		$sql = "SELECT * FROM ecshop.service WHERE service_id = {$service_id} "; 
		$service = $db->getRow($sql);
		$sql_error = " UPDATE ecshop.service SET inner_check_status=31 where service_id = {$service_id}";
	    if ($service['service_status'] == SERVICE_STATUS_DENIED) {
			sys_msg("【自动入库】售后服务申请审核未通过",1);
	    } else if($service['inner_check_status'] == 33) {
			sys_msg("【自动入库】退货订单已被拒绝入库",1);
	    } else if($service['inner_check_status'] == 32) {
	    	$sql1 = "update ecshop.warehouse_service set warehouse_service_status='RECOVER',remark='{$applyReturnReason}' where warehouse_service_id = {$warehouse_service_id} ";
			$db->query($sql1);	  	
			$result = "success";
			header("Location:warehouse_service.php");
	    }else{
		    // 验货入库 
	    	QLog::log("验货入库开始 ");
	    	QLog::log("service_id: ".$service_id);
	    	
	    	$serialNums = array();
	    	$goodsType = array();
	    	$key = 0;
	    	foreach($scanOrderGoods as $scanOrderGood){
	    		if($scanOrderGood['is_serial']=='Y'){
	    			$serialNums[$key] = $scanOrderGood['serial_numbers'];
	    			foreach($scanOrderGood['serial_numbers'] as $key2 => $serial_number){
	    				$goodsType[$serial_number][] = ($scanOrderGood['goods_type'][$key2]=='INV_STTS_AVAILABLE')?'new':'old';
	    			}
	    		}else{
	    			foreach($scanOrderGood['goods_type'] as $key2 => $goods_type){
	    				$goodsType[$scanOrderGood['barcode']][] = ($goods_type=='INV_STTS_AVAILABLE')?'new':'old';
	    			}
	    		}
	    		$key++;
	    	} 
	    	QLOG::LOG("serialNums:".json_encode($serialNums));
			QLog::log("goodsType: ".json_encode($goodsType));
		    $goodsNum = back_change_in_stock($service_id,$serialNums,$goodsType);
		    QLog::log("验货入库结束");
		    
		    if ($goodsNum > 0) {
		    	 // 判断是否全部入库
		    	 $sql = "SELECT sum(wait_inv_in) from (
					select og.rec_id,(og.goods_number- sum(IFNULL(iid.QUANTITY_ON_HAND_DIFF,0) )) as wait_inv_in 
					from ecshop.service s  
					inner join ecshop.ecs_order_goods og on og.order_id = s.back_order_id 
					LEFT JOIN romeo.inventory_item_detail iid on  iid.order_goods_id  = convert(og.rec_id USING utf8) 
					where s.service_id = {$service_id} 
					group by og.rec_id
				) as temp ";		
		         $serviceCount = $db->getOne($sql);
		         if($serviceCount == '0' ){ // 已全部入库 
		         	$log_note = $_SESSION['admin_name'] ." 于 {$datetime} 验货入库成功";
		            $sql = " UPDATE ecshop.service SET back_shipping_status = 12,
		            		    inner_check_status = 32,
				                service_status = '".SERVICE_STATUS_OK."', 
				                service_call_status = '".SERVICE_CALL_STATUS_NEEDCALL."',
				                check_result = '{$applyReturnReason}'
				                WHERE service_id = {$service_id} LIMIT 1 ";
					if(!$db->query($sql)){
						sys_msg("【更新售后】更新售后状态失败",1);
					}
				
					$sql0 = "UPDATE ecshop.ecs_order_info SET shipping_time = UNIX_TIMESTAMP() WHERE order_id = {$service['back_order_id']} LIMIT 1 ";
					$sql1 = "update ecshop.warehouse_service set warehouse_service_status='RECOVER',remark='{$applyReturnReason}' where warehouse_service_id = {$warehouse_service_id} ";
					if(!($db->query($sql0) && $db->query($sql1) )){
						sys_msg("【更新记录】退货订单与登记信息记录更新失败",1);
					}
		         }else{
		         	$db->query($sql_error);
					sys_msg("【自动入库】退货订单入库部分失败",1);
		         }  
	        } else {
		        $db->query($sql_error);
				sys_msg("【自动入库】退货订单入库失败",1);
	        }
			$service['log_note'] = $log_note;
			$service['log_type'] = 'LOGISTIC';
			if(!service_log($service)){
				$db->query($sql_error);
				sys_msg("【售后记录】售后记录更新失败",1);
			}else{
				require_once('lib_postsale_cache.php');
		        //SINRI UPDATE POSTSALE CACHe
		        POSTSALE_CACHE_updateService(null,180,$service_id);
		        $result = "success";
				header("Location:warehouse_service.php");
		    }
		}
	}
}
