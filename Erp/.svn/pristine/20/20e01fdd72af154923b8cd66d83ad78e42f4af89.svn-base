<?php

define('SERVICE_TYPE_CHANGE', 1);
define('SERVICE_TYPE_BACK', 2);
define('SERVICE_TYPE_PRICE_PROTECTED', 5);
define('SERVICE_TYPE_RE_SEND', 6);

//售后服务
$service_type_mapping = array(
    SERVICE_TYPE_CHANGE          => '换货申请',
    SERVICE_TYPE_BACK            => '退货申请',
    SERVICE_TYPE_PRICE_PROTECTED => '保价申请',
    SERVICE_TYPE_RE_SEND   => '漏寄申请',
);

//退货状态
$back_shipping_status_mapping = array(
  12 => "货已收到,待验货",
);

//检测 要么是检测，要么是验货
$outer_check_status_mapping = array(
  21 => "货物待检测",
  22 => "货物检测中",
  23 => "检测完成,有质量问题",
  24 => "检测完成,无质量问题",  
);

//验货 要么是检测，要么是验货
$inner_check_status_mapping = array(
  32 => "验货通过",
  33 => "验货未通过",
);

//换货 或者原样寄回的
$change_shipping_status_mapping = array(
  
  42 => "已配货,待出库",
  43 => "已出库,待发货",
  44 => "已发货,待签收", 
  45 => "已签收,申请结束",
  
  //原样寄回的
  52 => "退回货物已经寄走,待用户签收",
  53 => "退回货物用户已签收,申请结束",
  
  62 => "漏寄货物已寄走,待签收",
  63 => "漏寄货物用户已签收,申请结束",
);

define('SERVICE_STATUS_PENDING', 0);
define('SERVICE_STATUS_REVIEWING', 1);
define('SERVICE_STATUS_OK', 2);
define('SERVICE_STATUS_DENIED', 3);

//审核状态
$service_status_mapping = array(
	SERVICE_STATUS_PENDING => "待审核",
	SERVICE_STATUS_REVIEWING => "已审核",
	SERVICE_STATUS_DENIED=> "审核未通过",
	SERVICE_STATUS_OK => "审核通过", 
);

$service_pay_status_mapping = array(
  2 => "已退款,待用户确认",
  4 => "用户确认收款,申请结束",
);

define('SERVICE_CALL_STATUS_NEEDCALL', 1);
define('SERVICE_CALL_STATUS_CALLED', 2);

$service_call_status_mapping = array(
  SERVICE_CALL_STATUS_NEEDCALL => "待回访",
  SERVICE_CALL_STATUS_CALLED => "已回访",
);

$service_return_key_mapping = array(
  'bank_info' => array(
    'account_number'=>'开户帐号',
    'open_bank'=>'开户行',
    'account_name'=>'开户名', 
    'account_province'=>'所在省',
    'account_city'=>'所在市',
    'alipay_account'=>'支付宝帐号',
    'tenpay_account'=>'财付通帐号', 
  ),
  
  'carrier_info' => array(
    'deliver_company'=>'快递公司',
    'deliver_number'=>'快递单号',
    'deliver_fee'=>'快递费用',
  ),
);


/**
 * 根据价保售后服务返回状态名
 *
 * @param array service数组
 * @return string 状态名
 */
function service_status_price_protected($service) {
  extract($service);
  global $service_type_mapping, $service_status_mapping, $service_call_status_mapping,
         $back_shipping_status_mapping, $outer_check_status_mapping, $inner_check_status_mapping, $change_shipping_status_mapping,
         $service_pay_status_mapping;
  if ($service_type != SERVICE_TYPE_PRICE_PROTECTED) {
  	return "";
  }
  
  $status_name = "";
  if ($service_status == SERVICE_STATUS_PENDING) { //如果是待审核的，很好给出状态名
  	$status_name = $service_type_mapping[$service_type].",".$service_status_mapping[$service_status];
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


/**
 * 根据漏寄售后服务返回状态名
 *
 * @param array service数组
 * @return string 状态名
 */
function service_status_re_send($service) {
  extract($service);
  global $service_type_mapping, $service_status_mapping, $service_call_status_mapping,
         $back_shipping_status_mapping, $outer_check_status_mapping, $inner_check_status_mapping, $change_shipping_status_mapping,
         $service_pay_status_mapping;

  if ($service_type != SERVICE_TYPE_RE_SEND) {
  	return "";
  }
  
  if ($service_status == SERVICE_STATUS_PENDING) {
  	$status_name = "漏寄申请,待回访";
  }
  
  elseif ($service_status == SERVICE_STATUS_OK) {
    $status_name = "已回访,待补寄";
    if ($change_shipping_status) {
      $status_name = $change_shipping_status_mapping[$change_shipping_status];
    }
  }

  elseif ($service_status == SERVICE_STATUS_DENIED) {
    $status_name = "已回访,审核未通过,申请结束";
  }
  return $status_name;
}



/**
 * 根据换货售后服务返回状态名
 *
 * @param array service数组
 * @return string 状态名
 */
function service_status_change($service) {
  return service_status_change_or_back($service);
}



/**
 * 根据退货售后服务返回状态名
 *
 * @param array service数组
 * @return string 状态名
 */
function service_status_back($service) {
   return service_status_change_or_back($service);
}



/**
 * 根据退换货的售后服务返回状态名， 这个是换货和退货公用的函数
 *
 * @param array service数组
 * @return string 状态名
 */
function service_status_change_or_back($service) {
  extract($service);
  global $service_type_mapping, $service_status_mapping, $service_call_status_mapping,
         $back_shipping_status_mapping, $outer_check_status_mapping, $inner_check_status_mapping, $change_shipping_status_mapping,
         $service_pay_status_mapping;
  $status_name = "";
  
  if ($service_type != SERVICE_TYPE_CHANGE && $service_type != SERVICE_TYPE_BACK) {
    return "";
  }
  
  if ($service_status == SERVICE_STATUS_PENDING) { //待审核
    $status_name = $service_type_mapping[$service_type].",".$service_status_mapping[$service_status];
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
  }

  elseif ($service_status == SERVICE_STATUS_OK) {
    if ($service_call_status == SERVICE_CALL_STATUS_NEEDCALL) { //待回访
      
      $service_tmp = $service_status_mapping[SERVICE_STATUS_OK]; //默认是 审核通过
      $tipstr = $service_call_status_mapping[SERVICE_CALL_STATUS_NEEDCALL]; //默认是 待回访
      
      if ($inner_check_status) {   //如果能够显示相关检测状态就更好
      	$service_tmp = $inner_check_status_mapping[$inner_check_status];
      } elseif ($outer_check_status) { //如果能够显示相关验货状态就更好
        $service_tmp = $outer_check_status_mapping[$outer_check_status];
      }
      
      if ($service_type == SERVICE_TYPE_CHANGE && $inner_check_status) { // 如果是我们自己检查，形容词将是具体的
      	$tipstr = "待换货";
      } elseif ($service_type == SERVICE_TYPE_BACK && $inner_check_status) { // 如果是我们自己检查，形容词将是具体的
      	$tipstr = "待确认退款信息";
      }
      
      $status_name = $service_tmp.",".$tipstr;
      
    } elseif ($service_call_status == SERVICE_CALL_STATUS_CALLED) { //已经回访过了

      if ($service_type == SERVICE_TYPE_CHANGE) {
      	$status_name = "换货信息已确认,待配货";
      } elseif ($service_type == SERVICE_TYPE_BACK) {
        $status_name = "退款信息已确认,待退款";
      }
      if ($outer_check_status) { //如果是送到外面检测的， 在前面加上已回访
      	$status_name = $service_call_status_mapping[SERVICE_CALL_STATUS_CALLED].",".$status_name;
      }
    }
    
    if ($change_shipping_status && !$service_pay_status) { //如果开始换货了
    	$status_name = "换货".$change_shipping_status_mapping[$change_shipping_status];
    } elseif ($service_pay_status) { //如果是有退款信息
    	$status_name = $service_pay_status_mapping[$service_pay_status];
    }
  }
  
  elseif ($service_status == SERVICE_STATUS_DENIED) { //审核被拒绝
    $status_name = $service_status_mapping[SERVICE_STATUS_DENIED];
    if ($service_call_status == SERVICE_CALL_STATUS_NEEDCALL) {
      if ($inner_check_status) {
      	$status_name = $inner_check_status_mapping[$inner_check_status];
      } elseif ($outer_check_status) {
        $status_name = $outer_check_status_mapping[$outer_check_status];
      } else {
        $status_name = $service_status_mapping[SERVICE_STATUS_DENIED];
      }
    	$status_name .= ",待回访";
    } elseif ($service_call_status == SERVICE_CALL_STATUS_CALLED) {
    	$status_name = "已回访,申请未通过,待货物原样寄回";
    } else { //直接拒绝的情形
      $status_name = "已回访,审核未通过,申请结束";
    }
    
    if($change_shipping_status) { //货物寄回去的状态
      $status_name = $change_shipping_status_mapping[$change_shipping_status];
    }
  }
  
  return $status_name;
}


/**
 * 根据service返回售后服务名字
 *
 * @param array service 售后服务的数组
 * @return string 售后服务对应的状态名
 */
function service_status($service) {
  $status_name = "";
  switch ($service['service_type']) {
  	case SERVICE_TYPE_CHANGE:
  		$status_name = service_status_change($service);
  		break;
  	case SERVICE_TYPE_BACK:
  	  $status_name = service_status_back($service);
  	  break;
  	case SERVICE_TYPE_PRICE_PROTECTED:
  	  $status_name = service_status_price_protected($service);
  	  break;
  	case SERVICE_TYPE_RE_SEND:
  	  $status_name = service_status_re_send($service);
  	default:
  		break;
  }
  return $status_name;
}

/**
 * 根据order_id返回订单的售后服务
 *
 * @param int order_id 订单id
 * @return array 该订单申请过的售后服务
 */
function get_service_byorderid($order_id) {
  global $db, $ecs, $service_type_mapping, $service_return_key_mapping;
  static $services_array;
  if ($services_array[$order_id]) {
  	return $services_array[$order_id];
  }
  $sql = " SELECT s.*, o.* FROM service s 
           INNER JOIN {$ecs->table('order_info')} o ON s.order_id = o.order_id 
           WHERE s.order_id = '{$order_id}'
           ";
  $services = $db->getAll($sql);
  foreach ($services as $service_key => $service) {
    $service_id = $service['service_id'];
    $sql = "SELECT sog.*, og.goods_name, og.goods_id, IF(g.top_cat_id = 1, e.erp_goods_sn, '') as erp_goods_sn FROM service_order_goods sog 
                     INNER JOIN {$ecs->table('order_goods')} og ON sog.order_goods_id = og.rec_id  
                     INNER JOIN {$ecs->table('goods')} g ON og.goods_id = g.goods_id
                     INNER JOIN {$ecs->table('oukoo_erp')} e ON e.order_goods_id = og.rec_id
                     WHERE sog.service_id = '{$service_id}' ";
  	$goods_list = $db->getAll($sql);
  	$services[$service_key]['goods_list'] = $goods_list;
  	
  	//获得用户返回信息
  	$sql = "SELECT * FROM service_return WHERE service_id = '{$service_id}' ORDER BY service_return_id ASC ";
  	$temps = $db->getAll($sql);
  	$return_infos = array();
  	foreach ($temps as $return_info) {
  	  $return_type = $return_info['return_type'];
  	  $return_name = $service_return_key_mapping[$return_type][$return_info['return_name']];
      $return_infos[$return_type][$return_name] = $return_info['return_value'];
    }
  	$services[$service_key]['return_info'] = $return_infos;
  	
  	//获得换货订单的快递公司以及快递单号信息
  	if ($service['change_order_id']) { 
      // 消灭ECB 20151202 邪恶的大鲵 
  	  // $sql = "SELECT cb.bill_no AS carrier_no, c.*, c.name AS carrier_name FROM {$ecs->table('carrier_bill')} cb
  	  //         INNER JOIN {$ecs->table('order_info')} o ON o.carrier_bill_id = cb.bill_id 
  	  //         INNER JOIN {$ecs->table('carrier')} c ON cb.carrier_id = c.carrier_id
  	  //         WHERE o.order_id = '{$service['change_order_id']}' ";
      $sql="SELECT
          s.tracking_number as carrier_no,
          c.*, c. NAME AS carrier_name
        FROM
        ecshop.ecs_order_info o
        inner JOIN romeo.order_shipment os ON os.order_id = CONVERT (o.order_id USING utf8)
        inner JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
        INNER JOIN ecshop.ecs_carrier c ON s.carrier_id = c.carrier_id
        WHERE
          o.order_id = '{$service['change_order_id']}'
        AND s. STATUS != 'SHIPMENT_CANCELLED'
        GROUP BY o.order_id
      ";
  	  $carrier = $db->getRow($sql);
  	} else { //获得原样寄回以及补寄的快递公司以及快递单号
  	  $sql = "SELECT  carrier_no, carrier_name  FROM service_goods_shipping WHERE service_id = '{$service_id}' LIMIT 1  " ;
    	$carrier = $db->getRow($sql);
  	}
  	$services[$service_key]['carrier_info'] = $carrier; //获得售后返回相关的快递信息
  	
  	//获得相关的咨询
  	$sql = "SELECT * FROM service_comment WHERE service_id = '{$service_id}' ORDER BY service_comment_id ASC";
  	$services[$service_key]['service_comment'] = $db->getAll($sql);
  	
  	//获得状态及类型
  	$services[$service_key]['status_name'] = service_status($service);
  	$services[$service_key]['type_name'] = $service_type_mapping[$service['service_type']];
  }
  $services_array[$order_id] = $services;
  return $services;
}


/**
 * 根据order_id返回售后服务的评论
 *
 * @param int order_id 订单id
 * @param int service_id service_id，为0时
 * @return array
 */
function get_servicecomment_byorderid($order_id, $service_id = 0 ) {
  global $db;
  $sql = "SELECT * FROM  service_comment WHERE order_id = '{$order_id}' AND service_id = '{$service_id}' ";
  return $db->getAll($sql);
}


/**
 * 根据order_id返回售后服务的日志
 *
 * @param int order_id 订单id
 * @return array
 */
function get_servicelog_byorderid($order_id) {
  include_once(ROOT_PATH . 'includes/lib_service_tips.php');
  global $db, $ecs;
  static $service_logs_array;
  
  if($service_logs_array[$order_id]) {
    return $service_logs_array[$order_id];
  }
  $sql = " SELECT service_id, apply_datetime FROM service WHERE order_id = '{$order_id}' ORDER BY service_id DESC LIMIT 1 ";
  $last_service = $db->getRow($sql);
  $sql = "SELECT status_name, service_data FROM service_log
         WHERE service_id = '{$last_service['service_id']}'
         ORDER BY service_log_id ASC "; 
  //获得最新的售后服务

  $service_logs = array();
  $temp = $db->getAll($sql);

  $pre_status_name = "";
  foreach ($temp as $service_log) {
    $service = @unserialize($service_log['service_data']);
    $status_name = service_status($service);
    $status_name = $status_name ? $status_name : $service_log['status_name'];
    if ($status_name != $pre_status_name) {
    	$service_logs[] = $status_name;
    	$pre_status_name = $status_name;
    }
  }
  
  $service_logs_array[$order_id]['service_logs'] = $service_logs;
  $service_logs_array[$order_id]['last_apply_datetime'] = $last_service['apply_datetime'];
  $service_logs_array[$order_id]['last_status_name'] = $pre_status_name;
  $service_logs_array[$order_id]['service_tips'] = service_tips($service, $pre_status_name);
  return $service_logs_array[$order_id];
}

/**
 * 根据order_sn返回订单的商品条目（按串号）
 *
 * @param string order_sn 订单号
 * @return array
 */
function get_order_goods_item($order_sn) {
  global $db, $ecs;
  static $order_goods_item_array;
  
  if ($order_goods_item_array[$order_sn]) {
  	return $order_goods_item_array[$order_sn];
  }
  $sql = "SELECT og.rec_id, og.goods_name, og.parent_id, g.goods_id, og.goods_price, o.order_sn, IF(g.top_cat_id = 1, e.erp_goods_sn,'') AS goods_sn, e.erp_id as sid
	         FROM {$ecs->table('order_info')} o
        	 INNER JOIN {$ecs->table('order_goods')} og ON og.order_id = o.order_id 
        	 INNER JOIN {$ecs->table('goods')} g ON g.goods_id = og.goods_id
        	 INNER JOIN {$ecs->table('oukoo_erp')} e ON e.order_goods_id = og.rec_id
        	 WHERE o.order_sn = '{$order_sn}' ";
  $order_goods_item = $db->getAll($sql);
  $order_goods_item = sort_array_tree($order_goods_item, "rec_id", "parent_id") ;
  $order_goods_item_array[$order_sn] = $order_goods_item;
  
  return $order_goods_item;
}


/**
 * 判断订单是否能申请售后服务
 *
 * @param array order 订单数组
 * @return boolean 返回是否能申请售后服务的布尔值
 */
function is_can_apply_service($order) {
  if ( !($order['order_status'] == 1 && ($order['shipping_status'] == 2 || $order['shipping_status'] == 6)) ) { // 首先看是否确认收货
  	return false;
  }
  
  $services = get_service_byorderid($order['order_id']);
  if (!$services) { //没有售后服务，当然可以申请售后服务
  	return  true;
  }
  
  foreach ($services as $service) {
  	if (!$service['is_complete']) { //如果还有没有完成的售后服务不能申请售后服务
  		return false;
  		break;
  	}
  }
  
  return true;
}


/**
 * 申请售后服务，参数通过$_POST传递
 */
function apply_service() {
  global $db, $ecs, $userInfo, $service_return_key_mapping, $service_type_mapping;
  
  $submit_time = date("Y-m-d H:i:s");
  if(empty($_POST['order_goods_id'])) {
      show_message("请选择商品");
      exit();
  }
  
  if ( in_array($_POST['type'], array(SERVICE_TYPE_BACK, SERVICE_TYPE_CHANGE, SERVICE_TYPE_PRICE_PROTECTED, SERVICE_TYPE_RE_SEND)) ) {
    $service_type = $_POST['type'];
  } else {
    show_message("您的申请的售后服务类型不正确"); 
  }
  
  $sql = "SELECT COUNT(*) FROM service s INNER JOIN {$ecs->table('order_info')} o ON s.order_id = o.order_id WHERE o.order_sn = '{$_POST['order_sn']}' AND ( s.is_complete = 0 OR s.service_type = '$service_type' )";
  if ($db->getOne($sql)) {
    show_message("您已申请过 {$service_type_mapping[$service_type]} ");
    exit();
  }
   
  //check order_sn here
  $sql = sprintf("SELECT o.order_id, og.rec_id AS order_goods_id, e.erp_goods_sn, e.erp_id FROM %s o 
                  INNER JOIN %s og ON o.order_id = og.order_id 
                  INNER JOIN %s e ON og.rec_id = e.order_goods_id 
                  WHERE `order_sn` = '%s' AND `user_id` = '%s'",
              $GLOBALS['ecs']->table('order_info'),
              $GLOBALS['ecs']->table('order_goods'),
              $GLOBALS['ecs']->table('oukoo_erp'),
              $GLOBALS['db']->escape_string($_POST['order_sn']),
              $userInfo['user_id']);
  $order = $GLOBALS['db']->getAll($sql);
  if(!$order) {
    show_message("对不起,您输入的订单号错误。请检查后重试.");
    exit();
  }
  
  $service_order_goods = array();
  $order_id = "";
  foreach ($order as $order_goods) {
    $erp_id = $order_goods['erp_id'];
    if ( in_array($order_goods['order_goods_id'], $_POST['order_goods_id']) && $_POST["sid_{$erp_id}"] == 'on' ) {
    	$service_order_goods[] = $order_goods;
    }
    if (!$order_id) {
    	$order_id = $order_goods['order_id'];
    }
  }
//  pp($_POST);die();
  if (!count($service_order_goods)) {
  	show_message("对不起,请选择订单中的商品后重试。");
    exit();
  }
  
  //开始记录
  $service = array(
    'user_id' => $userInfo['user_id'],
    'order_id' => $order_id,
    'service_type' => $service_type,
    'service_status' => 0,
    'apply_username' => $userInfo['username'],
    'apply_reason' => $_POST['apply_reason'],
    'apply_datetime' => $submit_time,
  	'responsible_party' => $_POST['responsible_party'],
  	'dispose_method' => $_POST['dispose_method'],
    'dispose_description' => $_POST['dispose_description'],
  );
  $db->autoExecute('service', $service);
  $service_id = $db->insert_id();
  
  //插入售后的商品
  $sql_values = array();
  foreach ($service_order_goods as $order_goods) {
    $is_checkedreport = $_POST["is_checkedreport_".$order_goods['erp_id']] ? 1 : 0;
    $sql_values[] = sprintf("('%d', '%d', '%d', '%d', '%d', '%s')", 
                       $service_id, 
                       $userInfo['user_id'],
                       $order_id,
                       $order_goods['order_goods_id'], 
                       $is_checkedreport,
                       $order_goods['erp_goods_sn']
                       );
  }
  $sql = sprintf("INSERT INTO service_order_goods(service_id, user_id, order_id, order_goods_id, is_checkedreport, erp_goods_sn)
                         VALUES %s", join(",", $sql_values) );
  $db->query($sql);
  
  //用户返回的相关信息
  $service_return = array();
  foreach ($_POST as $key => $val) {//将用户返回的信息做一次转换
    if ($val && array_key_exists($key, $service_return_key_mapping['bank_info']) ) {
      $service_return[] = "('{$service_id}', '{$key}', '{$val}','bank_info')";
    }
    if ($val && array_key_exists($key, $service_return_key_mapping['carrier_info']) ) { 
      $service_return[] = "('{$service_id}', '{$key}', '{$val}','carrier_info')";
    }
  }
  if (count($service_return)) {
    $sql = "INSERT INTO service_return( service_id, return_name, return_value, return_type) VALUES " . join(",", $service_return);
    $result = $db->query($sql);
  }
  
  //记录售后的日志
  $service = array(
    'service_id' => $service_id,
    'service_status' => 0,
    'service_type' => $service_type
  );
  $type_name = $service_type_mapping[$service_type];
  $status_name = service_status($service);
  
  $service_log = array(
    'service_id' => $service_id,
    'type_name' => $type_name,
    'status_name' => $status_name,
    'log_username' => $userInfo['username'],
    'log_note' => '用户申请售后服务',
    'log_datetime' => $submit_time,
    'log_type' => 'USER',
    'is_remark' => 0,
    'service_data' => serialize($service),
  );
  $result = $db->autoExecute("service_log", $service_log);    
  
  if($result) {
    if(isset($_POST['back_url'])) {
      header("Location: " . $_POST['back_url']);
    } else {
      header("Location: ".$WEB_ROOT."afterService.php#newservice");
    }
  }
}


/**
 * 添加售后咨询，可以是对某一售后服务发起的，或者是新的一次售后咨询
 */
function add_service_comment() {
  global $db, $ecs, $userInfo;
  
  $submit_time = date("Y-m-d H:i:s");
  if (empty($_POST['apply_reason']) || ( empty($_POST['order_sn']) && empty($_POST['service_id']) ) ) {
  	show_message("您的输入不完整"); 
  }
  
  //检查是不是用户的订单 
  $sql = "SELECT order_id FROM {$ecs->table('order_info')} WHERE order_sn = '{$_POST['order_sn']}' AND user_id = '{$userInfo['user_id']}' ";
  $order_id = $db->getOne($sql);
  
  if (!$order_id) {
  	show_message("您的输入非法"); 
  	exit();
  }
  
  //检查是不是用户的售后服务
  $service_id = intval($_POST['service_id']);
  if ($service_id) {
  	$sql = "select service_id from service where service_id = '{$service_id}' ";
  	$service_id = $db->getOne($sql);
  	if (!$service_id) {
  		show_message("您的输入不正确"); //不是用户的售后服务
  		exit();
  	}
  }
  
  $post_comment = htmlentities($_POST['apply_reason'], ENT_QUOTES, 'UTF-8');
  $sql = "INSERT INTO service_comment(service_id, order_id, user_id, post_username, post_comment, post_datetime)
          VALUES('{$service_id}', '{$order_id}', '{$userInfo['user_id']}', '{$userInfo['username']}', '{$post_comment}', '{$submit_time}' ) ";
  $db->query($sql);

  $back_url = $_POST['back_url'] ? $_POST['back_url'] : $_SERVER['HTTP_REFERER'];
  show_message("您的咨询提交成功", "返回订单", $back_url); 
  
}

/**
 * 用户确认售后服务
 *
 * @param int order_id 订单id
 * @return array
 */
function confirm_service() {
  global  $db, $ecs, $userInfo, $service_type_mapping;
  
  $submit_time = date("Y-m-d H:i:s");
  if ($userInfo) {
    $service_id = intval($_REQUEST['service_id']);
    $sql = "SELECT * FROM service WHERE service_id = '{$service_id}' AND user_id = '{$userInfo['user_id']}' LIMIT 1 ";
    $service = $db->getRow($sql);
    if ($service) {
      $sql = "";
      if($service['service_pay_status'] == 2) {
        $service['service_pay_status'] = 4;
        $sql = "UPDATE service SET is_complete = 1, service_pay_status = 4 where service_id = '{$service_id}' ";
      }
      if ($service['change_shipping_status'] == 44) {
        $sql = "UPDATE {$ecs->table('order_info')} SET shipping_status = 2 WHERE order_id = '{$service['change_order_id']}' LIMIT 1 ";
				$result = $db->query($sql);
				if ($result) {
					$sql = "INSERT INTO {$ecs->table('order_action')} (order_id, action_user, shipping_status, action_time, action_note) VALUES ('{$service['change_order_id']}', '{$userInfo['username']}', '2', '{$submit_time}' , '用户确认收货')";
					$db->query($sql);
				}
        $service['change_shipping_status'] = 45;
      	$sql = "UPDATE service SET is_complete = 1, change_shipping_status = 45 where service_id = '{$service_id}' ";
      }
      if ($service['change_shipping_status'] == 52) {
        $service['change_shipping_status'] = 53;
      	$sql = "UPDATE service SET is_complete = 1, change_shipping_status = 53 where service_id = '{$service_id}' ";
      }
      if (intval($service['change_shipping_status']) == 62) {
        $service['change_shipping_status'] = 63;
      	$sql = "UPDATE service SET is_complete = 1, change_shipping_status = 63 where service_id = '{$service_id}' ";
      }
      if ($sql) {
      	$db->query($sql);
      	$type_name = $service_type_mapping[$service['service_type']];
        $status_name = service_status($service);
      	$service_log = array(
          'service_id' => $service_id,
          'type_name' => $type_name,
          'status_name' => $status_name,
          'log_username' => $userInfo['username'],
          'log_note' => '用户确认售后服务完成',
          'log_datetime' => $submit_time,
          'log_type' => 'USER',
          'is_remark' => 0,
          'service_data' => serialize($service),
        );
        $db->autoExecute("service_log", $service_log);
      } else {
        show_message("售后服务已确认");
      }
      
    } else {
      show_message("提交错误的信息");
    }
    
    if(isset($_REQUEST['back_url'])) {
      header("Location: " . $_REQUEST['back_url']);
    } elseif (isset($_SERVER['HTTP_REFERER'])) {
      header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
      show_message("提交成功");
    }
  } else {
  	show_message("你还没有登录！");
  }
}


/**
 * 用户提供相关售后服务的信息，如退款帐号
 *
 * @param int order_id 订单id
 * @return array
 */
function add_user_return_info() {
  global $db, $ecs, $userInfo, $service_return_key_mapping;
  
  $submit_time = date("Y-m-d H:i:s");  
  $service_id = intval($_POST['service_id']);
  if(!$service_id) {
    show_message("您的输入不完整"); 
  }
  
  //检查一下是不是用户的服务
  if ($db->getOne("SELECT service_id FROM service WHERE service_id = '$service_id' and user_id = '{$userInfo['user_id']}'")) 
  {
    if ($_REQUEST['return_info_type'] == 'bank') {
      
    } elseif ($_REQUEST['return_info_type'] == 'alipay') {
      if (empty($_POST['alipay_account']) || empty($_POST['alipay_account_confirm'])) {
      	show_message("您输入的支付宝信息不完整"); 
      }
      
      if ($_POST['alipay_account'] != $_POST['alipay_account_confirm']) {
      	show_message("您输入的支付宝信息不一致"); 
      }
    } elseif ($_REQUEST['return_info_type'] == 'carrier') {
       if(empty($_POST['deliver_company']) || empty($_POST['deliver_number']) || empty($_POST['deliver_fee'])) {
         show_message("请输入完整的快递信息"); 
       }
    }
          
    //用户返回的相关信息
    $service_return = array();
    foreach ($_POST as $key => $val) {//将用户返回的信息做一次转换
      if ( array_key_exists($key, $service_return_key_mapping['bank_info']) ) {
        $service_return[] = "('{$service_id}', '{$key}', '{$val}','bank_info')";
      }
      if ( array_key_exists($key, $service_return_key_mapping['carrier_info']) ) { 
        $service_return[] = "('{$service_id}', '{$key}', '{$val}','carrier_info')";
      }
    }
    if (count($service_return)) {
      $sql = "INSERT INTO service_return( service_id, return_name, return_value, return_type) VALUES " . join(",", $service_return);
      $result = $db->query($sql);
    }
  }

  
  if($result) {
    $back_url = isset($_REQUEST['back_url']) ? $_REQUEST['back_url'] : $_SERVER['HTTP_REFERER'];
    if($back_url) {
      header("Location: " . $back_url);
    } else {
      show_message("提交成功");
    }
  } else {
    show_message("提交失败");
  }
}












/**
 * 
 * 以下为旧的售后服务相关函数
 * 
 */

















/**
 * 将tag 转换成描述
 */
function tag2desc($item)
{
    global $status_mapping, $service_type_mapping;
    //$item['status_desc'] = $status_mapping[$item['status']];
	$item['type_desc'] = $service_type_mapping[$item['type']] ? $service_type_mapping[$item['type']] : '其他问题';
    return $item;
}

function get_sub_order_ids($order_sn)
{
	$sql = sprintf("SELECT `order_id` FROM `ecs_order_info` WHERE `order_sn` = '%s'", $GLOBALS['db']->escape_string($order_sn));
    $row = $GLOBALS['db']->getRow($sql);

    if(!$row)
    {
    	return array();
    }
    else
    {
    	$order_id = $row['order_id'];
    }
    $sql = sprintf("SELECT `order_id` FROM `ecs_order_info` WHERE `parent_order_id` = %s", $order_id);
    $rows = $GLOBALS['db']->getAll($sql);
    if(count($rows) == 0)
    {
    	return array($order_id);
    }
    $res = array();
    foreach($rows as $row)
    {
    	$res[] = $row['order_id'];
    }

    return $res;
}

function get_order_id($order_sn, $goods_id)
{
    $sql = sprintf("SELECT `order_id` FROM `ecs_order_info` WHERE `order_sn` = '%s'", $GLOBALS['db']->escape_string($order_sn));
    $order_id = $GLOBALS['db']->getOne($sql);

    $order_ids = get_sub_order_ids($order_sn);

    if($order_ids[0] != $order_id)
    {
    	$order_ids[] = $order_id;
    }
    $sql = sprintf("SELECT `order_id` FROM {$GLOBALS['ecs']->table('order_goods')} WHERE `rec_id` = '$goods_id' AND `order_id` in (%s) " , join(',', $order_ids));
    $row = $GLOBALS['db']->getRow($sql);
    if(!$row)
    {
        #这个情况可能出现补寄 - 其他商品
        if($_POST['type'] == 6)
        {
            return array($order_id, $order_id);
        }
        else
        {
            return false;
        }
    }
    return array($order_id, $row['order_id']);
}

function get_service_return_info($service_id)
{
    global $ecs,$db;
    $sql = sprintf("SELECT * FROM %s WHERE service_id='%s'", $ecs->table('service_return'), $service_id);
    return $db->getRow($sql);
}

function add_return_info($item)
{
    $info = get_service_return_info($item['service_id']);
    $item['return_info'] = $info;
    return $item;
}

function get_service($service_id)
{
    global $ecs,$db;
    $sql = sprintf("SELECT * FROM %s WHERE service_id='%s'", $ecs->table('service'), $service_id);
    $service = $db->getRow($sql);
    return $service;
}

function add_status_name($service)
{
    global $ecs, $db;
    $order_sn = $db->getOne("select order_sn from {$ecs->table('order_info')} where order_id = '{$service['order_id']}'");
    $sql = sprintf("SELECT status FROM %s WHERE order_goods_id = %s and order_sn='%s'" , $ecs->table('oukoo_sale_service'), $service['goods_id'], $order_sn);
    $ouku_service =  $db->getRow($sql);
    if($service['status'] == 0){
        if($service['status'] == 0)
        {
          $service['status_name'] = $GLOBALS['service_type_mapping'][$service['type']]; 
          if ($service['status_name']) {
          	$service['status_name'] .= '，';
          }
          $service['status_name'] .= '审核中';
        }
    }
    elseif ($service['status'] == 5) {
        $sql = "
            SELECT tho.*, thog.*, the.* FROM {$ecs->table('oukoo_erp')} e, {$ecs->table('order_info')} tho, {$ecs->table('order_goods')} thog, {$ecs->table('oukoo_erp')} the
            WHERE
                tho.order_id = thog.order_id
                AND thog.rec_id = the.order_goods_id
                AND tho.order_sn = CONCAT(e.out_sn, '-h')
                AND e.order_goods_id = '{$service['goods_id']}'
        ";
        $h_order = $db->getRow($sql);
        $sql = "
            SELECT tho.*, thog.*, the.* FROM {$ecs->table('oukoo_erp')} e, {$ecs->table('order_info')} tho, {$ecs->table('order_goods')} thog, {$ecs->table('oukoo_erp')} the
            WHERE
                tho.order_id = thog.order_id
                AND thog.rec_id = the.order_goods_id
                AND tho.order_sn = CONCAT(e.out_sn, '-t')
                AND e.order_goods_id = '{$service['goods_id']}'
        ";
        $t_order = $db->getRow($sql);

        if ($t_order == null) {
            $service['status_name'] = "审核中，待退回货物";
        } elseif ($t_order['in_sn'] == '') {
            $service['status_name'] = "货已收到，正在审核";
        } elseif ($t_order['in_sn'] != '' && $h_order['order_status'] == 0) {
            $service['status_name'] = "货已收到，即将配货";
        } elseif ($h_order['order_status'] == 1 && $h_order['shipping_status'] == 0) {
            $service['status_name'] = "货已收到，即将配货";
        } elseif ($h_order['order_status'] == 1 && $h_order['shipping_status'] == 9) {
            $service['status_name'] = "已配货，待出库";
        } elseif ($h_order['order_status'] == 1 && $h_order['shipping_status'] == 8) {
            $service['status_name'] = "已出库，待发货";
        } elseif ($h_order['order_status'] == 1 && $h_order['shipping_status'] == 1) {
            $service['status_name'] = "已发货，待签收";
        } elseif ($h_order['order_status'] == 1 && $h_order['shipping_status'] == 2) {
            $service['status_name'] = "已签收，换货申请结束";
        } else {
            $service['status_name'] = "审核中";
        }
    }
    elseif ($service['status'] == 6) {
        $sql = "
            SELECT tho.*, thog.*, the.* FROM {$ecs->table('oukoo_erp')} e, {$ecs->table('order_info')} tho, {$ecs->table('order_goods')} thog, {$ecs->table('oukoo_erp')} the
            WHERE
                tho.order_id = thog.order_id
                AND thog.rec_id = the.order_goods_id
                AND tho.order_sn = CONCAT(e.out_sn, '-t')
                AND e.order_goods_id = '{$service['goods_id']}'
        ";
        $t_order = $db->getRow($sql);
        if ($t_order == null) {
            $service['status_name'] = "审核中，待用户退回货物";
        } elseif ($t_order['in_sn'] == '') {
            $service['status_name'] = "货已收到，正在审核";
        } elseif ($t_order['in_sn'] != '' && $t_order['pay_status'] != 2) {
            $service['status_name'] = "审核通过，待退款";
        } elseif ($t_order['pay_status'] == 2) {
            $service['status_name'] = "已退款，退货申请结束";
        } else {
            $service['status_name'] = "审核中";
        }
    }
    else{
        if($ouku_service)
        {
            $service['status_name'] = $GLOBALS['oukoo_service_status_mapping'][$ouku_service['status']];
        }
        else
        {
            $service['status_name'] = $GLOBALS['status_mapping'][$service['status']];
        }
        //return false;#$services[$service_key]['status_name'] = $_CFG['adminvars']['status_mapping'][$service['status']];
    }
    return $service;
}

/**
 *增加操作历史
 *不用了这个函数
 */
function add_service_actions($service)
{
    $service['actions'] = array();
    if($service['status'] != 0)
    {
        $service['actions'][] = array(
            'time' => $service['review_time'],
            'desc' => "客服回复: " . $service['review_remark'] . "并将审核状态设置为" . $service['status_desc'] . "\n"
        );
        //换货
        if($service['type'] == 1 || $service['type'] == 2)
        {
            if($service['return_info'])
            {
                $service['actions'][] = array(
                    'time' => $service['return_info']['submit_time'],
                    'desc' => "用户提交相关信息(银行信息,快递公司等)"
                );
                if($service['type'] == 2)
                {
                    $t_order = get_tuihuo_order($service['goods_id']);
                    if($t_order)
                    {
                        $service['actions'][] = array(
                            'time' => $t_order['order_time'], 
                            'desc' => "欧酷已经收到您的退货,正在为你处理."
                        );

                        $order_actions = get_order_action($t_order['order_sn'], array('pay_status' => 2), 1);
                        $order_action = $order_actions[0];
                        $service['actions'][] = array(
                           'time' => $order_action['action_time'],
                           'desc' => "欧酷已经为您退款,请查收."
                        );
                    }
                }
                elseif($service['type'] == 1)
                {
                    $h_order = get_huanhuo_order($service['goods_id']);
                    if($h_order)
                    {
                        $service['actions'][] = array(
                            'time' => $h_order['order_time'], 
                            'desc' => "欧酷已经收到您的退货,正在为你处理."
                        );

                        $order_actions = get_order_action($h_order['order_sn'], array(), 10);
                        foreach($order_actions as $order_action)
                        {
                            if($order_action['shipping_status'] == 1 || $order_action['shipping_status'] == 8)  
                            {
                                if($order_action['shipping_status'] == 8)
                                {
                                    $desc = "您的货物的状态变更为" . "已出库待发货";
                                }
                                else
                                {
                                    $desc = "您更换的货物的状态变更为" . "已发货.<br/>";
                                    if($h_order['carrier_info'])
                                    {
                                        $desc .= "快递公司" . $h_order['carrier_info']['name'] . "<br/>";
                                        $desc .= "快递单号" . $h_order['carrier_bill']['bill_no'] . "<br/>";
                                    }
                                }
                                $service['actions'][] = array(
                                    'time' => $order_action['action_time'], 
                                    'desc' => $desc
                                );
                            }
                        }
                        
                    }

                }
            }
        }
    }
    return $service;

}

function get_service_history($order_id)
{
    global $ecs,$db;
    $sql = sprintf("SELECT s.*, og.goods_id AS shop_goods_id , og.goods_name FROM %s AS s,%s AS og WHERE s.order_id='%s' AND og.rec_id = s.goods_id ", 
        $ecs->table('service'), 
        $ecs->table('order_goods'),
        $order_id
    );
    $all = $db->getAll($sql);
    $all = array_map("tag2desc", $all);
    $all = array_map("add_return_info", $all);
    $all = array_map("add_status_name", $all);
    return $all;
}

/**
 * 根据订单商品获得退货订单
 */
function get_tuihuo_order($order_goods_id)
{
    global $ecs,$db,$userInfo;
    $sql = sprintf("SELECT out_sn FROM %s WHERE order_goods_id = '%s' ", $ecs->table('oukoo_erp'), $order_goods_id);
    $out_sn = $db->getOne($sql);
    #return get_order_detail_by_sn($out_sn, $userInfo['user_id']);
    $sql2 = sprintf("SELECT * FROM %s WHERE order_sn = '%s'", $ecs->table("order_info"), $out_sn . "-t");
    $order = $db->getRow($sql2);
    return $order;
    #return add_carrier_info($order);
}

/**
 * 根据订单商品获得换货订单
 */
function get_huanhuo_order($order_goods_id)
{
    global $ecs,$db,$userInfo;
    $sql = sprintf("SELECT out_sn FROM %s WHERE order_goods_id = '%s' ", $ecs->table('oukoo_erp'), $order_goods_id);
    $out_sn = $db->getOne($sql);
    #return get_order_detail_by_sn($out_sn, $userInfo['user_id']);
    $sql2 = sprintf("SELECT * FROM %s WHERE order_sn = '%s'", $ecs->table("order_info"), $out_sn . "-h");
    $order = $db->getRow($sql2);
    return $order;
    #return add_carrier_info($order);
}

function group_by($ar, $key)
{
    global $db,$ecs;
    $m = array();
    foreach($ar as $item)
    {
        $sql = sprintf("SELECT * FROM {$ecs->table('order_goods')} WHERE rec_id in (%s)" , $item['goods_id']);
        $item['goods_info'] =  $db->getRow($sql);
        $sql = "SELECT cb.bill_no, c.name
                FROM {$ecs->table('order_info')} o
                INNER JOIN {$ecs->table('carrier_bill')} cb ON o.carrier_bill_id = cb.bill_id
                INNER JOIN {$ecs->table('carrier')} c ON cb.carrier_id = c.carrier_id
                WHERE o.`order_sn` = (SELECT tho.order_sn
                                      FROM {$ecs->table('oukoo_erp')} e, {$ecs->table('order_info')} tho
                                      WHERE tho.order_sn = CONCAT( e.out_sn, '-h' ) 
                                      AND e.order_goods_id = '{$item['goods_id']}' LIMIT 1
                                     ) ";
        $item['h_order_info'] = $db->getRow($sql);
        $m[$item[$key]]['service_list'][] = $item;
    }
    foreach($m as $key=>$value)
    {
        $m[$key]['goods_list'] = get_service_goods_list($m[$key]['service_list']);
        $m[$key]['common_info'] = $m[$key]['service_list'][0];
    }
    return $m;
}
function get_service_goods_list($ar)
{
    global $db,$ecs;
    $rec_ids= array();
    foreach($ar as $item)
    {
        $rec_ids[] = $item['goods_id'];
    }
    $sql = sprintf("SELECT * FROM {$ecs->table('order_goods')} WHERE rec_id in (%s)" , join(',', $rec_ids));
    return $db->getAll($sql);
}

?>
