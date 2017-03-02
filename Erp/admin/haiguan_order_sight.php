<?php
define('IN_ECS', true);
require('includes/init.php');
require_once ('function.php');

global $db;

$plan = getRequest('plan');
$shop_id = getRequest('shop_id',0);
$days = getRequest('days',10);
$act = $_GET['act'];

$smarty -> assign('days',$days);

//店铺列表
$shop_id_array=array("0"=>"全部店铺",);
$shop_list_sql = "select hap.application_key as shop_id,hap.nick as shop_name
					from ecshop.haiguan_api_params hap
					where hap.party_id = {$_SESSION['party_id']}";				
$shop_list = $db -> getAll($shop_list_sql);

foreach ($shop_list as $shop) {
	$shop_id_array[$shop['shop_id']]=$shop['shop_name'];
}
$smarty -> assign('shop_id_array',$shop_id_array);

//异常列表
$plans=array(
	'reserve_fail' => "预订失败订单",
	'push_fail' => "推送申报系统失败订单",
	'precheck_fail' => "申报系统预校验未通过订单",
	'check_fail' => "申报系统单证审核未通过订单",
	'close_confirm' => "申报系统关闭的已确认订单",
	'release_notship' => "海关货物放行的待配货订单",
	'release_cancel' => "海关货物放行的已取消订单"
);
$smarty -> assign('plans',$plans);

//if($shop_id == 0) {
//	$default_shop_sql = "select application_key from ecshop.haiguan_api_params where party_id = '{$_SESSION['party_id']}' limit 1";
//	$shop_id = $db -> getOne($default_shop_sql);
//}


$sql_base="SELECT skoi.*,skos.check_flg,skos.check_msg,skos.status as check_status,skos.status_dis,
					eoi.order_id,
					eoi.order_sn,
					hap.nick,
					eoi.order_status erp_order_status,
					eoi.pay_status erp_pay_status,
					eoi.shipping_status erp_shipping_status,
					eoi.order_time erp_order_time 
	FROM ecshop.ecs_order_info eoi 
force index (order_info_multi_index)
	LEFT join ecshop.sync_kjg_order_info skoi on eoi.taobao_order_sn=skoi.taobao_order_sn
	LEFT JOIN ecshop.sync_kjg_order_status skos on skos.taobao_order_sn = skoi.taobao_order_sn and skoi.mft_no = skos.mft_no
	LEFT JOIN ecshop.haiguan_api_params hap ON skoi.application_key = hap.application_key
	WHERE
	eoi.party_id = '{$_SESSION['party_id']}' 
	and eoi.facility_id='222187982'
	and eoi.order_time>SUBDATE(now(),INTERVAL {$days} day)
";

$sql_base_count = "SELECT count(1) 
	FROM ecshop.ecs_order_info eoi 
force index (order_info_multi_index)
	INNER join ecshop.sync_kjg_order_info skoi on eoi.taobao_order_sn=skoi.taobao_order_sn
	LEFT JOIN ecshop.sync_kjg_order_status skos on skos.taobao_order_sn = skoi.taobao_order_sn and skoi.mft_no = skos.mft_no
	LEFT JOIN ecshop.haiguan_api_params hap ON skoi.application_key = hap.application_key
	WHERE
	eoi.party_id = '{$_SESSION['party_id']}' 
	and eoi.facility_id='222187982'
	and eoi.order_time>SUBDATE(now(),INTERVAL {$days} day)";


switch($act) {
	case 'change_shop':
	if($shop_id != 0) {
		$sql_base .= " and hap.application_key = '{$shop_id}'";
		$sql_base_count .= " and hap.application_key = '{$shop_id}'";
	} 
	if($plan == null || $plan == '' || $plan == 'undefined') {
		$plan = 'push_fail';
	} 
	$sql_base .= getPlanConiditon($plan);
	$sql_base_count	.= getPlanConiditon($plan);	
	break;
	case 'change_plan':
	$plan = $_REQUEST['plan'];
	if($shop_id != 0){
		$sql_base.=" and hap.application_key = ".intval($shop_id)." ";
		$sql_base_count.=" and hap.application_key = ".intval($shop_id)." ";
	}
	if($plan == null || $plan == '') {
		$smarty -> display('haiguan_order_sight.html');
		exit();
	} else {
		$sql_base .= getPlanConiditon($plan);
		$sql_base_count .= getPlanConiditon($plan);
	}
	break;
	default:	
//	$sql_base.=" and hap.application_key = ".intval($shop_id)." ";
//	$sql_base_count.=" and hap.application_key = ".intval($shop_id)." ";
	
	$plan = 'push_fail';
	$sql_base .= getPlanConiditon($plan);
	$sql_base_count .= getPlanConiditon($plan);
	break;

}


if(!empty($sql_base_count)){
	$total=$db->getOne($sql_base_count);
}else{
	$total=0;
}
$smarty -> assign('total',$total);


$page = isset($_REQUEST['page'])?intval($_REQUEST['page']):1;
$limit = 20;
$offset = $limit * ($page-1);
$sql_base .= " order by skoi.created_stamp  LIMIT {$limit} OFFSET {$offset}";
if(!empty($sql_base)){
	$list=$db->getAll($sql_base);
}else{
	$list=null;
}
$smarty -> assign('Pager',pager($total,$limit,$page));
$smarty -> assign('plan',$plan);
$smarty -> assign('shop_id',$shop_id);
$smarty -> assign('list',$list);
$smarty -> display('haiguan_order_sight.html');


function getPlanConiditon($plan) {
	if($plan == 'reserve_fail') {
		$condition = " and eoi.order_status = '12' ";
	} elseif($plan == 'push_fail') {
		$condition .= " and eoi.order_status = '1' and eoi.pay_status = '2' and skoi.status = 'ERROR' ";
	} elseif($plan == 'precheck_fail') {
		$condition .= " and eoi.order_status = '1' and eoi.pay_status = '2' and skoi.status = 'SUCCESS' and skos.check_flg = '0' ";
	} elseif($plan == 'check_fail') {
		$condition .= " and eoi.order_status = '1' and eoi.pay_status = '2' and skoi.status = 'SUCCESS' and skos.check_flg = '1' and skos.status = '海关单证审核未过'";
	} elseif($plan == 'close_confirm') {
		$condition .= " and eoi.order_status = '1' and eoi.pay_status = '2' and skoi.status = 'SUCCESS' and skos.check_flg = '1' and skos.status = '已关闭' and eoi.order_status = '1'";
	} elseif($plan == 'release_notship') {
		$condition .= " and eoi.order_status = '1' and eoi.pay_status = '2' and skos.status = '海关货物放行' and eoi.shipping_status = '0' ";
	} elseif($plan == 'release_cancel') {
		$condition .= " and skos.status = '海关货物放行' and eoi.order_status = '2' ";
	} else {
		$condition = "";
	}
	return $condition;
}

function getRequest($name,$default=null){
	if(isset($_REQUEST[$name])){
		return $_REQUEST[$name];
	}else{
		return $default;
	}
}

?>
