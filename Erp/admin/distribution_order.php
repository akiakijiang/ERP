<?php

/**
 * 分销商下单
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_order');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

$kf_batch_order_entry = false;
if( check_admin_user_priv($_SESSION['admin_name'], 'kf_batch_order_entry')){
	$kf_batch_order_entry = true;
	$smarty->assign('kf_batch_order_entry', $kf_batch_order_entry);
}

//判断组织是否为具体的业务单位
if(!party_explicit($_SESSION['party_id'])) {
    sys_msg("请选择具体的组织后再来下分销订单");
}

/**
 * 根据原订单自动添加客户信息,原订单
 */
$order_sn = isset($_REQUEST['order_sn']) ? trim($_REQUEST['order_sn']) : false ;

$after_sales_type_list = array(
    '1' => '无责破损(外包装完好/本人签收的内物破损)',
    '2' => '无责漏发(外包装完好/本人签收仓库核实无果的漏发)',
    '3' => '无责错发(外包装完好/本人签收仓库核实无果的错发)',
    '4' => '正常退款(未发货退款  正常的退货退款等)',
    '5' => '恶意售后(顾客恶意申请退款、恶意威胁)',
    '6' => '退差价(活动差价或优惠券差价、好评返现、半价活动、免单)',
    '7' => '商品问题(顾客认为是质量问题/描述不符，品牌商不予承担)',
    '8' => '质量问题(顾客对商品质量提出质疑或明显的质量问题，核实过后定为品牌商承担)',
    '9' => '原单退回(原单退回破损，但快递和仓库不承认)',
    '10' => '顾客退货(顾客退货和仓库收到实物不符，必须以顾客的为准)',
    '11' => '液体商品(液体商品破损快递不赔/液体破损直接弃件)',
    '12' => '急速退款(急速退款，顾客填写订单号无效，时间将至，联系无果)',
    '13' => '投诉举报(工商投诉赔款、举报处理)',
    '14' => '特殊业务(品牌商故意或者失误导致的售后)',
    '15' => '责任明确(已经明确责任人，以定责的选项为准)',
    '16' => '其他平台(由于平台/仓库产生的售后)'
);

// 请求
$request = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
$act = isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add_goods', 'generate_sale_order', 'get_distributor', 'get_select_distributor', 'get_select_shipping', 'search_goods', 'search_payment')) 
    ? $_REQUEST['act'] 
    : null;

/*
 * 处理ajax请求
 */
if ($request == 'ajax')
{
    $json = new JSON;
   
    switch ($act) 
    {
        // 添加商品
        case 'add_goods':
            $goods = distribution_get_goods($_POST['goods_id'], $_POST['style_id']);
            $goods['goods_number'] = $_POST['goods_number'];
            if($_POST['price'] != 0){
            	$goods['shop_price'] = $_POST['price'];
            }
            if ($goods) 
                print $json->encode($goods);
                
            else
                print $json->encode(array('error' => '商品不存在,或该颜色已经下架'));
                
            break;
        
        // 取得分销商信息
        case 'get_distributor':
            $distributor = distribution_get_distributor_list($_POST['distributor_id']);
            if ($distributor)
                print $json->encode($distributor);
            else
                print $json->encode(array('error' => '分销商不存在'));
            break;
         // 关键字取得分销商信息
         case 'get_select_distributor':
         	$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 20 ;
            $distributor = distribution_get_select_distributor_list($_POST['q'],$limit);
            if ($distributor)
            	print $json->encode($distributor);
            else
            	print $json->encode(array('error' => '分销商不存在'));
            break;
        
        // 搜索商品
        case 'search_goods':
            $limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 40 ;
            print $json->encode(distribution_get_goods_list(NULL, NULL, $_POST['q'], $limit));  
        break;
        
        // 搜索支付方式cyj
        case 'search_payment':
        	//echo "test";
       		$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 40 ;
       		//print "test";
        	print $json->encode(distribution_get_payments_list($_POST['q'], $limit)); 
        break;
        
        // 关键字取得配送方式
        case 'get_select_shipping':
        	$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 40 ;
        	print $json->encode(distribution_get_select_shipping_list($_POST['q'], $limit));
        	break;
    }

    exit;
}

/*
 * 生成销售订单
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act == 'generate_sale_order') {
    $order = $_POST['order'];
  
    $responsible_party = $_REQUEST['responsible_party'];
    $compensation_amount = $_REQUEST['compensation_amount'];
    $after_sales_type = $_REQUEST['after_sales'];
    $note = $after_sales_type_list[$after_sales_type];//售后类型详细（和备注一样，只是客服不想手动填- -!）
//  $order = array_filter(array_map('trim', $order), 'strlen');  // 删除空白的订单属性
//  $shipping = $_POST['shipping'];  // 配送方式
    $order_goods = $_POST['order_goods'];  // 订单商品
    $outer_type = $_POST['outer_type_key']; //外部订单类型
    $taobao_user_id = $order['taobao_user_id']; //淘宝订单用户id
    $fenxiao_type = $_REQUEST['fenxiao_type']; //订单类型
    Qlog::log('fenxiao_type='.$fenxiao_type);
    $group_order_goods = $_POST['group_order_goods'];  // 套餐商品

	$order['responsible_party'] = $responsible_party;
	$order['compensation_amount'] = $compensation_amount;
	$order['after_sales_type'] = $after_sales_type;
	$order['note'] = note;
	
	$goods = "";
	$order['order_goods_num'] = count($order_goods);
	$num = 0;
	if(!empty($order_goods)){
		foreach($order_goods as $key=>$value){
			$temp="{'rec_id':'".$key."',";
			foreach($value as $key=> $val){
				if($key == 'style_id'){
					$temp =$temp."'".$key."':'".$val."'";
				}else{
					if($key == 'discount_fee' && empty($val)){
						$temp =$temp."'".$key."':'0',";
					}else{
						$temp =$temp."'".$key."':'".$val."',";
					}
				}
				
			}
			$num ++;
			if($num == count($order_goods))
				$goods=$goods.$temp.'}';
			else{
				$goods=$goods.$temp.'},';
			}
		}
	}
	
	$tc_goods = "";
	$order['group_order_goods_num'] = count($group_order_goods);
	$num = 0;
	if(!empty($group_order_goods)){
		foreach($group_order_goods as $key=>$value){
			$temp="{'tc_rec_id':'".$key."',";
			foreach($value as $key=> $val){
				$temp =$temp."'".$key."':'".$val."'";
			}
			$num ++;
			if($num == count($group_order_goods))
				$tc_goods=$tc_goods.$temp.'}';
			else{
				$tc_goods=$tc_goods.$temp.'},';
			}
		}
	}
	
	$order['facility_id'] = $_POST['facility_id'];
	$order['currency'] = $_POST['currency'];
	$order['root_order_sn'] = $order_sn;
	$order['order_goods'] = '['.$goods.']';
	$order['outer_type'] = $outer_type;
	$order['taobao_user_id'] = $taobao_user_id;
	$order['fenxiao_type'] = $fenxiao_type;
	$order['group_order_goods'] = '['.$tc_goods.']';
	$order['party_id'] = $_SESSION['party_id'];
	$order['admin_name'] = $_SESSION['admin_name'] == "" ?"system":$_SESSION['admin_name'];
	$order['kjg_pay_id'] = $_POST['kjg_pay_id'];
	
	$order_json = json_encode($order);
//	print_r($order_json);
	Qlog::log("order_json:".$order_json);
	try{						  
		$client = new SoapClient($erpsync_webservice_url.'GenerateSaleOrderService?wsdl');
		$response = $client->GenerateSaleOrder(array("order"=>$order_json));
		$res = (array)$response;
		$res = (array)$res['return'];
		$order_sn = $res['order_sn'];
		$order_id = $res['order_id'];
		$code = $res['code'];
		$message = $res['msg'];
		if($code=="00000"){
	  		$message = '订单成功生成，订单号为：'."<a href=order_edit.php?order_id={$order_id} target=\"_blank\">{$order_sn}</a>";
		}
	}catch(Exception $e){
		$message = 'Create Order Exception,请联系ERP！<br/>'.$e->getMessage();
	}
    $smarty->assign('message', $message);  // 错误消息
    $smarty->assign('order', $order);      // 失败要持有订单数据
    $smarty->assign("taobao_user_id", $taobao_user_id);
	//$smarty->display('distributor/distribution_order.htm'); 
    //die();
    
//    $bonus = 0 ; // 总的优惠 
//    if(!empty($order_goods)){
//    	// 商品优惠金额 
//    	foreach ($order_goods as $key => $goods) {
//    		$bonus += -1*abs($goods['discount_fee']); 
//    	}
//    }
//    $order['bonus'] = $bonus; 
//
//    if(!empty($group_order_goods)) {
//    	foreach($group_order_goods as $tc_code=>$item) {
//    		 $group_order_goods_temps = get_group_order_goods(array($tc_code),$_SESSION['party_id']);
//    		 $group_order_goods_temps = $group_order_goods_temps['group_order_goods']['code'][$tc_code];
//    		 $tc_order_goods = get_tc_order_goods($group_order_goods_temps,$item['goods_number']);
//    		 if($order_goods == null) {
//    		 	$order_goods = array();
//    		 }
//    		 $order_goods = array_merge($order_goods,$tc_order_goods);
//    	}
//    	
//    }
//
//    do {
//        // 取得分销商信息
//        $distributor = $db->getRow("SELECT * FROM distributor WHERE distributor_id = {$order['distributor_id']}", true);
//        if ($distributor['party_id'] != $_SESSION['party_id']) {
//            $message = '您选择的分销商的业务形态与操作人员的业务形态不一致, 请选择所属组织';
//            break;    
//        }
//        
//        // 取得分销类型，
//        $distribution_type = $db->getOne("SELECT type FROM main_distributor WHERE main_distributor_id = '{$distributor['main_distributor_id']}' LIMIT 1");
//        
//        //手动录单之前先按照淘宝订单号进行查询，防止订单重复
//         if (!empty($order['taobao_order_sn'])) {
//         	$sql = "SELECT 1 FROM {$ecs->table('order_info')} WHERE taobao_order_sn = '". $db->escape_string($order['taobao_order_sn']) ."'";
//            $exists = $db->getOne($sql, true);
//            if ($exists) {
//                $message = '该淘宝订单号已经存在';
//                break;
//            }
//        }
//       
//        // 电教的分销业务需要扣预付款，预付款需要根据原始导入记录扣除，所以不能录入
//        // 欧酷没有预付款，允许录入
//        if (!empty($order['taobao_order_sn']) && $distribution_type=='fenxiao' && ($distributor['party_id']==16 || $distributor['party_id']==65548)&& $distributor['main_distributor_id']!=25) {
//            $message = '请使用导入订单的方式生成订单，否则将不能抵扣分销商的预付款';
//            break;
//        }
//        
//        // 检查分销采购单号是否存在了
//        if (!empty($order['distribution_purchase_order_sn'])) {
//            $sql = "SELECT 1 FROM {$ecs->table('order_info')} WHERE distribution_purchase_order_sn = '". $db->escape_string($order['distribution_purchase_order_sn']) ."' and party_id = {$_SESSION['party_id']} and order_time >date_sub(NOW(),interval 3 month) ";
//            $exists = $db->getOne($sql, true);
//            if ($exists) {
//                $message = '该分销采购订单号已经存在了';
//                break;
//            } else if ($distribution_type=='fenxiao' && ($distributor['party_id']==16 || $distributor['party_id']==65548)&& $distributor['main_distributor_id']!=25) {
//                $message = '请使用导入订单的方式生成订单，不然不能抵扣分销商的预付款';
//                break;
//            }
//        }
//
//        /* 取得配送方式和承运方式 */
//        $shipping_id = $order['shipping_id'];
//        $shipping = $db->getRow("select support_no_cod,support_cod,default_carrier_id from ecshop.ecs_shipping where shipping_id = '{$shipping_id}'");
//        if($shipping['support_no_cod'] == '1' and $shipping['support_cod'] == '0'){
//        	$order_type = 'NON-COD';
//        }elseif ($shipping['support_no_cod'] == '0' and $shipping['support_cod'] == '1'){
//       		$order_type = 'COD';
//        }else{
//       	 	$order_type = '';
//        }
//        
//        $pay_id = $order['pay_id'];
//        
//        if($shipping_id == '36' && $distributor['party_id'] == PARTY_LEQEE_EDU){
//			$message = '电教品不能选择EMS货到付款';
//			break;
//		}else{
//			$sql = "select default_carrier_id from ecshop.ecs_shipping where shipping_id = {$shipping_id} limit 1";
//			$carrier_id = $db->getOne($sql);
//		}
//        
//		// 违禁品不能发的快递
//		$contraband_shipping = get_contraband_shipping();
//		if(in_array($shipping_id,$contraband_shipping)) {
//		    if( check_contraband($order_goods) ) {
//		    	$message = '该订单含有违禁品，请修改快递方式';
//			    break;
//		    }
//		}       
//        $order['shipping_proxy_fee'] = 0;            // 手续费都为0
//        $order['pack_fee'] = 0;                      // 包装费为0
//        $order['user_id']  = 1;                      // 指定用户 
//        $order['party_id'] = $_SESSION['party_id'];  // 订单类型 （乐其分手机业务和电教业务）
//        $order['order_status'] = 0;                  // 默认为确认状态
//        $order['facility_id'] = $_POST['facility_id']; // 仓库id
//        $order['currency'] = $_POST['currency'];        // 货币
//
//        $order['outer_type']=$outer_type; //source_type, Sinri Edogawa, 20150921
//        
//        $order['root_order_sn'] = $order_sn; //原订单， 用于补寄订单
//        if(empty($order['currency'])) {
//        	$order['currency'] = 'RMB';
//        }
//        $osn = distribution_generate_sale_order($order, $order_goods, $carrier_id, $order_type, $message);
//        if(empty($osn['error'])) {
//        	$order_sn = $osn['order_sn'];
//        	$order_id = getOrderIdBySN($order_sn);
//        } else {
//        	$order_sn = false;
//        }        
//        if ($order_sn !== false) {
//            
//            // 在 order_attribute 中标记该订单为手工录单 
//            $sql = "INSERT INTO ecshop.order_attribute(order_id, attr_name, attr_value) values({$order_id}, 'ORDER_BY_HAND', '1')";
//            $db->query($sql);
//
//        	//插入补寄订单理赔信息
//        if($order['type'] == 'SHIP_ONLY'){
//	        	for($i = 0;$i < count($responsible_party); $i++){
//	        		$sql = "INSERT INTO ecshop.claims_settlement(order_id, responsible_party, compensation_type, compensation_amount, note, freight, is_claim, is_delete, created_stamp, last_updated_stamp) 
//						VALUES({$order_id}, '{$responsible_party[$i]}', 'SHIP_ONLY', {$compensation_amount[$i]}, '{$note}', 0, 0, 0, now(), now())";
//	        		$db->query($sql);
//	        	}
//	        }
//        	
//        	if ($taobao_user_id) {
//	        	//插入淘宝订单用户id
//        	}
//        	if($fenxiao_type && $fenxiao_type != 'NONE'){
//        		$sql = "INSERT INTO ecshop.order_attribute(order_id, attr_name, attr_value) values({$order_id}, 'FENXIAO_TYPE', '{$fenxiao_type}')";
//        		$db->query($sql);
//        	}
//        	if($outer_type && $outer_type != '-1') {
//        		$sql = "INSERT INTO ecshop.order_attribute(order_id, attr_name, attr_value) values({$order_id}, 'OUTER_TYPE', '{$outer_type}')";
//        		$db->query($sql);
//        	}
//        	$message = '订单成功生成，订单号为：'."<a href=order_edit.php?order_id={$order_id} target=\"_blank\">{$order_sn}</a>";
//			break;
//        }
//    }
//    while (false);		
    $smarty->assign('message', $message);  // 错误消息
    $smarty->assign('order', $order);      // 失败要持有订单数据
    $smarty->assign("taobao_user_id", $taobao_user_id);
}

//添加选择快递方式的选项。   by qxu 2013-6-25
$get_shippings = getShippingTypes();

//foreach ($get_shippings as $get_shipping){
//    if(($get_shipping['shipping_id'] == $order['shipping_id']) && $get_shipping['shipping_name'] != $order['shipping_name']){
//      $shipping_boolean = true;
//    }
//    if ($get_shipping['shipping_id'] == $order['shipping_id']) {
//        $shipping_exist = true;
//    }
//}

//所有可用的快递方式
$smarty->assign('get_shippings', $get_shippings);

//添加支付方式的选项  by  qxu   2013-6-26
$get_payments = getPayments();
//所有可用的支付方式
$smarty->assign('get_payments', $get_payments);
//外部订单类型
$smarty->assign('outer_type', $_CFG['adminvars']['outer_type']);


// 订单录入币种选择
$currencies = array('HKD' => '港币', 'USD' => '美元', 'RMB' => '人民币');

$smarty->assign('currency', $currencies);
$smarty->assign('currencys', get_currency_style()); //币种数组

$is_kjg = false;
//判断是否为跨境业务组
$sql_party_ids = "select party_id from romeo.party where PARENT_PARTY_ID = '65658'";
$fields_value = array();
$ref = array();
$party_ids = $db -> getAllRefby($sql_party_ids,array('party_id'), $fields_value, $ref);	
if(in_array($_SESSION['party_id'],$fields_value['party_id'])) {
	$is_kjg = true;
}
$smarty->assign('is_kjg', $is_kjg);

/**
 * 根据原订单自动添加客户信息
 */
if (!empty($order_sn )) {
    // 如果传递了订单号则查询相关信息	
     $exists  = getOrderIdBySN($order_sn);
     if (!empty($exists) && $exists != 0 && $exists != '') {
		$orderSQL = "select info.*," .
				"(select attr_value from ecshop.order_attribute oa1 where oa1.order_id = info.order_id and attr_name = 'TAOBAO_USER_ID' limit 1) as taobao_user_id," .
				"(select attr_value from ecshop.order_attribute oa2 where oa2.order_id = info.order_id and attr_name = 'TAOBAO_POINT_FEE' limit 1) as TAOBAO_POINT_FEE," .
				"if((select attr_value from ecshop.order_attribute oa3 where oa3.order_id = info.order_id and attr_name = 'FENXIAO_TYPE' limit 1) is null, " .
				    "'NONE', (select attr_value from ecshop.order_attribute oa3 where oa3.order_id = info.order_id and attr_name = 'FENXIAO_TYPE' limit 1)) as fenxiao_type, ".
				"(select attr_value from ecshop.order_attribute oa4 where oa4.order_id = info.order_id and attr_name = 'OUTER_TYPE' limit 1) as outer_type_key, ".
				"(select attr_value from ecshop.order_attribute oa5 where oa5.order_id = info.order_id and attr_name = 'KJG_PAY_ID' limit 1) as kjg_pay_id".
				"  FROM {$ecs->table('order_info')} AS info where  info.order_id = $exists";
        $order_before = $db->getRow($orderSQL);
		$orderGoodsSQL = "select goods_name,goods_number,goods_price,goods_id,style_id," .
				"concat(goods_id,'_',style_id) as goods_style_id,oga.value as discount_fee FROM {$ecs->table('order_goods')} og " .
				" left join ecshop.order_goods_attribute oga on oga.name = 'DISCOUNT_FEE' and oga.order_goods_id = og.rec_id ".
				"where order_id = $exists group by goods_style_id,goods_price";

        $ref_fields=$order_goods_before=array();
	    $db->getAllRefby($orderGoodsSQL,array('goods_style_id'),$ref_fields,$order_goods_before);
	    $order_before['taobao_user_id'] = $order_before['nick_name'];
//        var_dump($order_goods_before);
		$smarty->assign('responsible_party_list',  $_CFG['adminvars']['responsible_party']);
		$smarty->assign('after_sales_type_list', $after_sales_type_list); //售后类型
        $smarty->assign('order_before', $order_before);
		$smarty->assign('order_goods_before', $order_goods_before['goods_style_id']);
		
	}else{
		$message = "原订单号不存在，请查证后重试";
	}
}

$smarty->assign('distributor_list', distribution_get_distributor_list());  // 分销商列表 
$smarty->assign('province_list', get_regions(1, $GLOBALS['_CFG']['shop_country']));  // 省份列表
//业务组的默认发货仓
if($_SESSION['party_id']) {
	$sql = "select DISTINCT facility_id from ecshop.taobao_shop_conf where party_id='{$_SESSION['party_id']}'";
	$facility_id = $db->getAll($sql);
	if(count($facility_id) != 1) {
		$facility_id = '0';
	} else {
		$facility_id = $facility_id[0]['facility_id'];
	}
}
$smarty->assign('facility_id', $facility_id);  // 默认发货仓



// 如果选择了订单省份，则持有城市数据
if ($order_before['province'] > 0) {
    $smarty->assign('city_list', get_regions(2, $order_before['province']));    
}
if ($order_before['city'] > 0 && !empty($order_before['district'])) {
    $smarty->assign('district_list', get_regions(3, $order_before['city']));
}

$fenxiao_type_list = array(
array('key'=>'NONE',  'value'=>'不选'),
array('key'=>'AGENT', 'value'=>'代销'),
array('key'=>'DEALER','value'=>'经销')
);

//$available_facility = get_available_facility($order['party_id']);
$available_facility  = array_intersect_assoc(get_available_facility(),get_user_facility());

$available_facility[0] = " -- 请选择发货仓库 -- ";

$smarty->assign('kjg_payment',$_CFG['adminvars']['kjg_payment']);
$smarty->assign('fenxiao_type_list',$fenxiao_type_list);

$smarty->assign('available_facility', $available_facility);
$smarty->assign('party_id', $_SESSION['party_id']);
$smarty->assign('message', $message);  
$smarty->display('distributor/distribution_order.htm');

function check_contraband($order_goods){
	global $db;
	if (count($order_goods) > 0){
		foreach ($order_goods as $item) {
		 	$sql = "select is_contraband from ecshop.ecs_goods where goods_id = {$item['goods_id']}";
		 	$is_contraband = $db->getRow($sql);
		 	if($is_contraband['is_contraband']){
		 		return true;
		 	}
		 }
     }
}
?>