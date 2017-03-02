<?php
/**
 * 转仓（新）
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once (ROOT_PATH . 'admin/function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('distribution_order_manage');
global $db;
//限制业务组必须具体
$session_party = $_SESSION['party_id'];
$sql = "select IS_LEAF from romeo.party where party_id = '{$session_party}' limit 1";
$is_leaf = $db->getOne($sql);
if($is_leaf == 'N'){
	die("请先选择具体业务组后再查询库存");
}

$page_size_list = array('30'=>'30');
$distributor_list = $db->getAll("SELECT distributor_id,name FROM ecshop.distributor  WHERE party_id = {$session_party} and status='NORMAL' ");

$facility_allow_list = array(
	'137059427'=>'电商服务武汉仓',
	'137059428'=>'电商服务成都仓',
	'185963147'=>'电商服务深圳仓',
	'194788297'=>'电商服务嘉善仓',
	'19568548'=>'电商服务东莞仓',
	'19568549'=>'电商服务上海仓',
	'79256821'=>'电商服务北京仓',
	'185963138'=>'电商服务苏州仓',
	'137059426'=>'上海精品仓',
	'22143846'=>'乐其上海仓_2（原乐其杭州仓）',
	'24196974'=>'贝亲青浦仓',
	'3580047'=>'乐其东莞仓',
	'253372944'=>'华庆武汉仓',
	'253372945'=>'万霖北京仓',
	'185963128'=>'水果北京仓',
	'185963134'=>'水果上海仓',
	'253372943'=>'水果深圳仓',
);
$facility_user_list = array_intersect_assoc(get_available_facility(),get_user_facility(),$facility_allow_list);
$province_query = $db->getAll("select region_id,region_name from ecshop.ecs_region where region_type=1 and parent_id = 1");

$order_reserve_status_list = array('0'=>'未确认',	'1'=>'已确认,预定失败','2'=>'已确认,预定成功');// 订单状态列表
$pay_status_list = array('0'=>'未付款','2'=>'已付款');    // 支付状态
$shipping_status_list = array('0'=>'待配货'); // 发货状态 
$shipping_list = $db->getAll("SELECT shipping_id,shipping_name FROM ecshop.ecs_shipping WHERE enabled = 1 ORDER BY shipping_code, support_cod");
// 请求
$act =  isset($_REQUEST['act']) &&  in_array($_REQUEST['act'], array('search',  'transfer'))  ? $_REQUEST['act'] : null ;
$request_order_time = $_REQUEST['order_time'];
$request_order_time_end = $_REQUEST['order_time_end'];
if (isset($request_order_time) && strtotime($request_order_time) > 0){
    if(!(isset($request_order_time_end) && strtotime($request_order_time_end) > 0)){
    	$request_order_time_end = date('Y-m-d H:i:s', strtotime("+3 days", strtotime($request_order_time)));
    }
}else{
	if(isset($request_order_time_end) && strtotime($request_order_time_end) > 0){
		$request_order_time = date('Y-m-d H:i:s', strtotime("-3 days", strtotime($request_order_time_end)));
    }else{
    	$request_order_time = date('Y-m-d H:i:s', strtotime("-3 days"));
    	$request_order_time_end = date('Y-m-d H:i:s');
    }  
}

$condition = "";

if ( $_POST['act']=='search') {
	$_REQUEST['exchange'] = array (
       'distributor_id' => (isset($_REQUEST ['distributor_id']) && $_REQUEST ['distributor_id'] != '-1')? $_REQUEST ['distributor_id']:'', // 分销商
       'facility_id' => (isset($_REQUEST ['facility_id']) && $_REQUEST ['facility_id'] != '-1')? $_REQUEST ['facility_id']:'', // 仓库
	   'shipping_id' => (isset($_REQUEST ['shipping_id']) && $_REQUEST ['shipping_id'] != '-1')? $_REQUEST ['shipping_id']:'', // 快递
       'order_reserve_status' => (isset($_REQUEST ['order_reserve_status']) && $_REQUEST ['order_reserve_status'] != '-1')? $_REQUEST ['order_reserve_status']:'', //订单状态(0,1+null/N,1+Y)
       'pay_status' => (isset($_REQUEST ['pay_status']) && $_REQUEST ['pay_status'] != '-1')? $_REQUEST ['pay_status']:'', // 付款状态(0,2)
	   'shipping_status' => 0, //只有“待配货”才可以转仓转快递
	   'order_time'        => $request_order_time,  //订单开始时间
	   'order_time_end'        => $request_order_time_end   ,                     // 订单截止时间
	   'size' => isset($_REQUEST ['size'])? $_REQUEST ['size']:30, 
	   'goods_style_id' => isset($_REQUEST ['goods_style_id']) ? trim($_REQUEST ['goods_style_id']):'',
	   'province_region_id' => (isset($_REQUEST ['province_region_id']) && $_REQUEST ['province_region_id'] != '-1')? $_REQUEST ['province_region_id']:'', 
	);
}elseif($_POST['act']=='transfer' && !empty($_POST['checked'])){
	// 转仓使用
    admin_priv('change_facility_shipment');
    require_once (ROOT_PATH . 'admin/update_facility_express.php');
	$new_facility_id = $_POST['facility_id_new'];
	$new_shipping_id = $_POST['shipping_id_new'];
	if(!empty($_POST['checked'])){
		$classBatch = new UpdateFacilityExpress();
		//检查订单是否与其他订单合并，合并订单不允许操作修改快递与转仓
		$result = $classBatch->check_data($_POST['checked'],$new_facility_id,$new_shipping_id);
		$classBatch->export_data_excel('订单转仓/改快递明细');
	}
	$_REQUEST['exchange'] = array (
       'distributor_id' => (isset($_REQUEST ['distributor_id_hidden_form']) && $_REQUEST ['distributor_id_hidden_form'] != '-1')? $_REQUEST ['distributor_id_hidden_form']:'', // 分销商
       'facility_id' => (isset($_REQUEST ['facility_id_hidden_form']) && $_REQUEST ['facility_id_hidden_form'] != '-1')? $_REQUEST ['facility_id_hidden_form']:'', // 仓库
	   'shipping_id' => (isset($_REQUEST ['shipping_id_hidden_form']) && $_REQUEST ['shipping_id_hidden_form'] != '-1')? $_REQUEST ['shipping_id_hidden_form']:'', // 快递
       'order_reserve_status' => (isset($_REQUEST ['order_reserve_status_hidden_form']) && $_REQUEST ['order_reserve_status'] != '-1')? $_REQUEST ['order_reserve_status_hidden_form']:'', //订单状态(0,1+null/N,1+Y)
       'pay_status' => (isset($_REQUEST ['pay_status_hidden_form']) && $_REQUEST ['pay_status_hidden_form'] != '-1')? $_REQUEST ['pay_status_hidden_form']:'', // 付款状态(0,2)
	   'shipping_status' => 0, //只有“待配货”才可以转仓转快递
	   'order_time'        => $request_order_time,  //订单开始时间
	   'order_time_end'        => $request_order_time_end   ,                     // 订单截止时间
	   'size' => isset($_REQUEST ['size_hidden_form'])? $_REQUEST ['size_hidden_form']:30, 
	   'goods_style_id' => isset($_REQUEST ['goods_style_id_hidden_form']) ? trim($_REQUEST ['goods_style_id_hidden_form']):'',
	   'province_region_id' => (isset($_REQUEST ['province_region_id_hidden_form']) && $_REQUEST ['province_region_id_hidden_form'] != '-1')? $_REQUEST ['province_region_id_hidden_form']:'', 
	);
}

if(isset($_REQUEST['exchange'])){
	$condition = _get_exchange_condition();	
	$size = $_REQUEST['exchange']['size'];
}else{
	$condition = " AND 1=0 ";
	$size = 1;
}

$use_index = " force index (order_info_multi_index) "; 
if(isset($_REQUEST['exchange']['distributor_id']) && $_REQUEST['exchange']['distributor_id'] != ''){
	$use_index = " ";
}
if(((isset($_REQUEST['exchange']['order_time']) && $_REQUEST['exchange']['order_time'] != '')
    || (isset($_REQUEST['exchange']['order_time_end']) && $_REQUEST['exchange']['order_time_end'] != ''))
    // && (isset($_REQUEST['exchange']['shipping_status']) && $_REQUEST['exchange']['shipping_status'] != '')
){
    $use_index = " force index (order_info_multi_index) "; 
}
// 查询订单
$sql = "SELECT 
        oi.order_id, oi.order_sn, oi.order_time, oi.taobao_order_sn, oi.shipping_name, f.facility_name,p.pay_name, 
        oi.order_status, oi.shipping_status,oi.pay_status, ir.status as ir_status,
        d.name AS distributor_name,e.region_name
    FROM 
        ecshop.ecs_order_info AS oi {$use_index}
        INNER JOIN romeo.shipment s on s.primary_order_id = convert(oi.order_id using utf8) and s.shipping_category = 'SHIPPING_SEND'
        LEFT JOIN ecshop.ecs_region AS  e ON e.region_id = oi.province      
        LEFT JOIN ecshop.distributor AS d ON d.distributor_id = oi.distributor_id
        LEFT JOIN ecshop.ecs_payment AS p ON p.pay_id = oi.pay_id
        LEFT JOIN romeo.facility f on f.facility_id = oi.facility_id
        LEFT JOIN romeo.order_inv_reserved ir ON ir.order_id = oi.order_id
    WHERE oi.party_id ={$session_party} {$condition} AND IF((SELECT count(*) FROM romeo.order_shipment os WHERE os.shipment_id = s.shipment_id) <> 1,FALSE,TRUE)
    	limit {$size}
".'-- '.__FILE__.' Line '.__LINE__.PHP_EOL;
//QLOG::LOG("exchange_facility_express_sql:".$sql);
$sqlc = " SELECT 
        count(distinct oi.order_id)
    FROM 
        ecshop.ecs_order_info AS oi force index (order_info_multi_index)
        INNER JOIN romeo.shipment s ON s.primary_order_id = convert(oi.order_id using utf8) and s.shipping_category = 'SHIPPING_SEND'
        LEFT JOIN romeo.order_inv_reserved ir ON ir.order_id = oi.order_id
    WHERE oi.party_id ={$session_party} {$condition}  
    AND IF((SELECT count(*) FROM romeo.order_shipment os WHERE os.shipment_id = s.shipment_id) <> 1,FALSE,TRUE)
		".'-- '.__FILE__.' Line '.__LINE__.PHP_EOL;
$count = $db->getOne($sqlc);
//QLOG::LOG("exchange_facility_express_sql:".$sqlc);
$ref_field = $ref_orders = array();
$order_list = $db->getAllRefby($sql, array('order_id','facility_id','shipping_id'), $ref_field, $ref_orders, false);
//var_dump($order_list);
foreach($order_list as $key=>$value){
	if($value['ir_status']=='Y'){
		$order_list[$key]['reserve_status'] = "预定成功";
	}elseif($value['ir_status']=='N' && empty($value['ir_status'])){
		$order_list[$key]['reserve_status'] = "预定失败";
	}	
}

$order_status_list = array("0"=>"未确认","1"=>"已确认");   
$smarty->assign('order_list',     $order_list);  // 订单列表
$smarty->assign('count',$count);
$smarty->assign('order_status_list', $order_status_list);
$smarty->assign('province_query',       $province_query);         //省份列表
$smarty->assign('order_reserve_status_list',    $order_reserve_status_list);      // 订单状态+预定状态
$smarty->assign('shipping_status_list', $shipping_status_list);   // 发货状态 
$smarty->assign('pay_status_list',      $pay_status_list);        // 支付状态列表
$smarty->assign('shipping_list',        $shipping_list);          // 配送方式列表
$smarty->assign('page_size_list',       $page_size_list);         // 每页分页数
$smarty->assign('distributor_list',$distributor_list);       // 分销商列表
$smarty->assign('available_facility',$facility_user_list);   //仓库
$smarty->display('order/exchange_facility_express.htm');


function _get_exchange_condition(){
	$condition = "";
    $distributor_id = $_REQUEST['exchange']['distributor_id'];
    $facility_id = $_REQUEST['exchange']['facility_id'];
    $shipping_id = $_REQUEST['exchange']['shipping_id'];
    $order_reserve_status = $_REQUEST['exchange']['order_reserve_status'];
    $pay_status = $_REQUEST['exchange']['pay_status'];
    $shipping_status  = $_REQUEST['exchange']['shipping_status'];
    $order_time     = $_REQUEST['exchange']['order_time'];
    $order_time_end = $_REQUEST['exchange']['order_time_end'];
    $goods_style_id = $_REQUEST['exchange']['goods_style_id'];
    $province_region_id = $_REQUEST['exchange']['province_region_id'];
    
    if($distributor_id !=''){
    	$condition .= " AND oi.distributor_id = {$distributor_id} ";
    }
    if($facility_id !=''){
    	$condition .= " AND oi.facility_id ='{$facility_id}' ";
    }else{
    	
		$facility_allow_list = array(
		'137059427'=>'电商服务武汉仓',
		'137059428'=>'电商服务成都仓',
		'185963147'=>'电商服务深圳仓',
		'194788297'=>'电商服务嘉善仓',
		'19568548'=>'电商服务东莞仓',
		'19568549'=>'电商服务上海仓',
		'79256821'=>'电商服务北京仓',
		'185963138'=>'电商服务苏州仓',
		'137059426'=>'上海精品仓',
		'22143846'=>'乐其上海仓_2（原乐其杭州仓）',
		'24196974'=>'贝亲青浦仓',
		'3580047'=>'乐其东莞仓',
		'253372944'=>'华庆武汉仓',
		'253372945'=>'万霖北京仓',
		'185963128'=>'水果北京仓',
		'185963134'=>'水果上海仓',
		'253372943'=>'水果深圳仓',
		);
		$facility_user_list = array_intersect_assoc(get_available_facility(),get_user_facility(),$facility_allow_list);
    	$facility_user_str = implode("','",array_keys($facility_user_list));
    	$condition .= " AND oi.facility_id in ('{$facility_user_str}') ";
    }
    if($shipping_id !=''){
    	$condition .= " AND oi.shipping_id = {$shipping_id} ";
    }
    if($order_reserve_status !=''){
    	if($order_reserve_status == 0){ //未确认
    		$condition .= " AND oi.order_status = 0 ";
    	}elseif($order_reserve_status == 1){//已确认，预定失败
    		$condition .= " AND oi.order_status = 1 AND (ir.status = 'N' or ir.status is null) ";
    	}else{ //已确认，预定成功
    		$condition .= " AND oi.order_status = 1 AND ir.status = 'Y' ";
    	}
    }else{
    	$condition .= " AND oi.order_status in (0,1) ";
    }
    if($pay_status !=''){
    	$condition .= " AND oi.pay_status = {$pay_status} ";
    }else{
    	$condition .= " AND oi.pay_status in (0,2) ";
    }
    if($shipping_status !=''){//当前仅有 待配货一种选择，后期可延伸
    	$condition .= " AND oi.shipping_status = {$shipping_status} ";
    }else{
    	$condition .= " AND oi.shipping_status = 0 ";
    }
    if($order_time !=''){
    	$condition .= " AND oi.order_time >='{$order_time}' ";
    }
    if($order_time_end !=''){
    	$condition .= " AND oi.order_time <='{$order_time_end}' ";
    }
    
    if($goods_style_id !=''){
    	if(strpos($goods_style_id, 'TC-') !== false){
    		$condition .= " AND exists (select 1 from ecshop.ecs_order_goods og where oi.order_id = og.order_id and og.group_code = '{$goods_style_id}') ";
    	}else{
    		$goods = explode('_', $goods_style_id);
			$goods_id = $goods[0];
			$style_id = isset($goods[1])?$goods[1]:0;
			$condition .= " AND exists (select 1 from ecshop.ecs_order_goods og where oi.order_id = og.order_id and og.goods_id = {$goods_id} AND og.style_id = {$style_id} ) ";
    	}
    }
    if($province_region_id !=''){
    	$condition .= " AND oi.province = {$province_region_id} ";
    }
    //现状：补寄订单与销售订单状态均为 SALE,唯一区别:order_sn(-b)
    $condition .= " AND oi.order_type_id in ('SALE','RMA_EXCHANGE') ";  
    return $condition;	
    
} 
?>
