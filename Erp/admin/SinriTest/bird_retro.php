<?php
define('IN_ECS', true);
require_once('../includes/init.php');
require_once ('../function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH.'/includes/helper/array.php');
require_once(ROOT_PATH.'/includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
admin_priv('bird_retro');
$bird_authority="";
if(!in_array($_SESSION['admin_name'],array(
	'mjzhou','zjli','hlong','hzhang1','qyyao'
))){
	$bird_authority = "no_authority";
}else{
	$bird_authority = "have_authority";
}

global $db;
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
$bird_status_list= array(
	'bird_wait' => '等待推送',
	'bird_wait_cancel' => '等待推送时取消成功',
	'bird_erp' => '由ERP发货无须推送',
	'bird_success' => '推送成功',
	'bird_cancel' => '推送成功后取消成功',
	'bird_cancel_toerp' => '推送成功后转回ERP发货',
	'bird_fail' => '推送失败'
);

$json = new JSON;    
switch ($act) 
{ 
	case 'retro_order':
		$out_biz_code = trim ( $_REQUEST ['out_biz_code'] );
		$sql = "update ecshop.express_bird_indicate set indicate_status='等待推送',err_message='推送失败手动回退为等待推送' where out_biz_code = '{$out_biz_code}'";
		$result=$GLOBALS['db']->query($sql);
		$return['flag'] = 'SUCCESS';
		$return['message'] = '取消成功';
		print $json->encode($return);
		exit;
		break;
	case 'batch_retro_order':
		$orders_list= $_POST['checked'];
		$order_count = count($orders_list);
		for($i=0;i<$order_count;$i++){
			$sql = "update ecshop.express_bird_indicate set indicate_status='等待推送',err_message='推送失败手动回退为等待推送' where out_biz_code = '{$orders_list[$i]}'";
			$result=$GLOBALS['db']->query($sql);
		}
		$return['flag'] = 'SUCCESS';
		$return['message'] = '取消成功';
		print $json->encode($return);
		exit;
		break;
}

$result = call_user_func('search_bird_order',$_GET);
$smarty->assign('result',$result);
$smarty->assign('bird_status_list',$bird_status_list);
$smarty->assign('Pager',$result[-1]['pager']);
$smarty->assign('bird_authority',$bird_authority);
$smarty->display ( 'bird_retro.htm' );
	
function getCondition() {
	global $db;
	$order_sn = trim ( $_REQUEST ['order_sn'] );
	$start = trim ( $_REQUEST ['start'] );
	$ended = trim ( $_REQUEST ['ended'] );
	$bird_status = trim ( $_REQUEST ['bird_status'] );
	$err_message = trim ( $_REQUEST ['err_message'] );
	
	$condition='';
	if ($order_sn != '') {
		$sql = "select out_biz_code from ecshop.ecs_order_info eoi
				inner join ecshop.express_bird_indicate ebi  on concat_ws('',eoi.taobao_order_sn,substring(eoi.order_sn,locate('-',eoi.order_sn))) = ebi.out_biz_code
				where eoi.order_sn='{$order_sn}'";
		$out_biz_code = $db->getOne($sql);
		$condition .= " and out_biz_code = '{$out_biz_code}' ";
	}

	if ($start != '') {
		$condition .= " and created_stamp > '{$start}' ";
	}
	
	if ($ended != '') {
		$condition .= " and created_stamp < '{$ended}' ";
	}
	if($err_message != ''){
		$condition .= " and err_message like '%{$err_message}%' ";
	}

	if($bird_status != ''){
		$indicate_status="";
		switch ($bird_status){
			case 'bird_wait':
				$indicate_status = "等待推送";
				break;
			case 'bird_wait_cancel':
				$indicate_status = "等待推送时取消成功";
				break;
			case 'bird_erp':
				$indicate_status = "由ERP发货无须推送";
				break;
			case 'bird_success':
				$indicate_status = "推送成功";
				break;
			case 'bird_cancel':
				$indicate_status = "推送成功后取消成功";
				break;
			case 'bird_cancel_toerp':
				$indicate_status = "推送成功后转回ERP发货";
				break;
			case 'bird_fail':
				$indicate_status = "推送失败";
				break;
		}
		$condition .= " and indicate_status = '{$indicate_status}' ";
	}else{
		$condition .= " and indicate_status = '等待推送' ";
	}
	
	return $condition;
}

function search_bird_order($args){
	global $db;
	$cond = getCondition();
	$index = 0;
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 10;
	$offset = $limit * ($page-1);
	$session_party_id = $_SESSION['party_id'];
	$sqlc = "select count(1) from ecshop.express_bird_indicate where 1  {$cond} and party_id={$session_party_id}";	
	$total = $db ->getOne($sqlc);
	$sql = "select * from ecshop.express_bird_indicate ebi
			INNER JOIN ecshop.taobao_shop_conf tsc on ebi.application_key=tsc.application_key
		    where ebi.party_id='{$session_party_id}' {$cond} order by ebi.last_updated_stamp desc LIMIT {$limit} OFFSET {$offset}";
	$bird_error_list = $db->getAll($sql);
	if(!empty($bird_error_list)){
		foreach($bird_error_list as $order){
			$out_biz_code = $order[out_biz_code];
			$pos = strpos($out_biz_code, '-');
			$taobao_order_sn="";
			if($pos === false){
				 $taobao_order_sn= $out_biz_code ;
			}else{
				$taobao_order_sn = substr($out_biz_code,0,$pos);
			}
			
			$sql ="select * from ecshop.express_bird_indicate ebi
					INNER JOIN ecshop.ecs_order_info eoi on concat_ws('',eoi.taobao_order_sn,substring(eoi.order_sn,locate('-',eoi.order_sn))) = ebi.out_biz_code
					where eoi.taobao_order_sn='{$taobao_order_sn}' and ebi.out_biz_code = '{$out_biz_code}' limit 1";
			$ecs_order_info = $db->getRow($sql);
			$result[$index]['nick']=$order['nick'];
			$result[$index]['order_id']=$ecs_order_info['order_id'];
			$result[$index]['order_sn']=$ecs_order_info['order_sn'];
			$result[$index]['out_biz_code']=$order[out_biz_code];
			$order_type="";//（1）NORMAL_OUT ：正常出库 （2）NORMAL_IN：正常入库 （3）RETURN_IN：退货入库 （4）EXCHANGE_OUT：换货出库
			switch($order[order_type]){
				case 'NORMAL_OUT':
					$order_type = '正常出库 (NORMAL_OUT)';
					break;
				case 'NORMAL_IN':
					$order_type = '正常入库 (NORMAL_IN)';
					break;
				case 'RETURN_IN':
					$order_type = '退货入库(RETURN_IN) ';
					break;
				case 'EXCHANGE_OUT':
					$order_type = '换货出库(EXCHANGE_OUT) ';
					break;
			}
			$order_sub_type="";//订单子类型 （1）OTHER： 其他 （2）TAOBAO_TRADE： 淘宝交易 （3）OTHER_TRADE：其他交易 （4）ALLCOATE： 调拨 （5）PURCHASE:采购
			switch($order[order_sub_type]){
				case 'OTHER':
					$order_sub_type = '其他(OTHER) ';
					break;
				case 'TAOBAO_TRADE':
					$order_sub_type = '淘宝交易(TAOBAO_TRADE) ';
					break;
				case 'OTHER_TRADE':
					$order_sub_type = '其他交易(OTHER_TRADE)';
					break;
				case 'ALLCOATE':
					$order_sub_type = '调拨(ALLCOATE)';
					break;
				case 'PURCHASE':
					$order_sub_type = '采购(PURCHASE)';
					break;
			}
			$result[$index]['order_type']=$order_type;
			$result[$index]['order_sub_type']=$order_sub_type;
			$result[$index]['indicate_status']=$order['indicate_status'];
			$result[$index]['created_stamp']=$order['created_stamp'];
			$result[$index]['send_stamp']=$order['sended_stamp'];
			$result[$index]['err_message']=$order['err_message'];
			$index++;
		}
	}
	$result[-1]['pager'] = pager($total,$limit,$page);
	return $result;
}
?>