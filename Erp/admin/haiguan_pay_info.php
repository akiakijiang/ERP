<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once('distribution.inc.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
require_once (ROOT_PATH . 'includes/debug/lib_log.php');


/*
 * 跨境购申报系统支付方式信息维护
 * by hzhang1 2015-12-29
 */
$act = $_REQUEST ['act'];
$req = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
$source_name_list= array(
	'01' => '银联在线',				
	'02' =>	'支付宝',				
	'03' => '盛付通',				
	'04' => '建设银行',				
	'05' => '中国银行',				
	'06' => '易付宝',				
	'07' => '农业银行',				
	'08' => '京东网银在线',	
	'09' => '国际支付宝',				
	'10' => '甬易支付',			
	'11' => '富友支付',			
	'12' => '连连支付',			
	'13' => '财付通（微信支付）',				
	'14' => '快钱	',
	'15' => '网易宝',				
	'16' => '银盈通支付',				
	'17' => '鄞州银行',			
	'18' => '智惠支付'			
);


/*
 * 处理ajax请求，进行模糊搜索
 * by hzhang1 2015-07-24
 */
if ($req == 'ajax')
{
    $json = new JSON;
    switch ($act) 
    { 
        case 'get_select_pay':
            $name = $_REQUEST['q'];
            $sql = "
			select pay_name,pay_id from ecshop.ecs_payment
			WHERE
			pay_name like '%{$name}%' 
			";
			$result=$GLOBALS['db']->getAll($sql);	
            if ($result)
                print $json->encode($result);
            else{
            	print $json->encode(array('error' => '店铺不存在'));
            }
            
            break;
        case 'get_select_shop':
            $nick = $_REQUEST['q'];
            $sql = "
			select nick,party_id,application_key from ecshop.taobao_shop_conf
			WHERE
			nick like '%{$nick}%' limit 20
			";
			$result=$GLOBALS['db']->getAll($sql);	
            if ($result)
                print $json->encode($result);
            else{
            	$sql = "select name as nick,party_id,distributor_id as application_key from ecshop.distributor where name like '%{$nick}%' limit 20";
            	$result=$GLOBALS['db']->getAll($sql);	
            	if ($result)
                	print $json->encode($result);
                else
            		print $json->encode(array('error' => '店铺不存在'));
            }
            break;
    }
    exit;
}

$page = intval($_REQUEST['page']);
$page = max(1, $page);
$limit = 10;
$offset = $limit * ($page-1);

$cond = getCondition();
$session_party_id = $_SESSION['party_id'];
$result = search_pays($session_party_id,$cond,$offset);


$smarty->assign('pay_list',$result['pay_list']);
$smarty->assign('message_error',$message_error);
$smarty->assign('message_success',$message_success);
$smarty->assign('source_name_list',$source_name_list);
$smarty->assign('Pager',pager($result['total'],$limit,$page));


$smarty->display ( 'haiguan_pay_info.html' );


function getCondition() {
	global $ecs;
	$result = array();
	$condition = "";
	$nick = trim ( $_REQUEST ['nick'] );
	$searchpayname = trim ( $_REQUEST ['searchpayname'] );
	$start_time = $_REQUEST ['start_time'];
	$end_time = $_REQUEST ['end_time'];

	if($nick != ''){
		$condition .= " AND hp.nick LIKE '%{$nick}%' ";
	}
	if ($searchpayname != '') {
		$condition .= " AND hp.pay_name LIKE '%{$searchpayname}%' ";
	}
	if ($start_time != '') {
		$condition .= " AND hp.created_stamp > '{$start_time}' ";
	}
	if ($end_time != '') {
		$condition .= " AND hp.created_stamp < '{$end_time}' ";
	}

	$result['simple_cond'] = $condition;
	
	return $result;
}


function search_pays($party_id,$cond,$offset) {
	global $db;
	$goods_list = array();
	$sql = "select hp.*,tsc.nick,case hp.source when '01' then '银联在线'
			 when '02' then '支付宝'
			 when '03' then '盛付通'
			 when '04' then '建设银行'
			 when '05' then '中国银行'
			 when '06' then '易付宝'
			 when '07' then '农业银行'
			 when '08' then '京东网银在线'
			 when '09' then '国际支付宝'
			 when '10' then '甬易支付'
			 when '11' then '富友支付'
			 when '12' then '连连支付'
			 when '13' then '财付通（微信支付）'
			 when '14' then '快钱'
			 when '15' then '网易宝'
			 when '16' then '银盈通支付'
			 when '17' then '鄞州银行'
			 when '18' then '智慧支付'
			 else '其他' end as source_name from ecshop.haiguan_pay hp inner join ecshop.taobao_shop_conf tsc on hp.application_key = tsc.application_key
			where 
			1 {$cond['simple_cond']} order by hp.created_stamp desc LIMIT 10 OFFSET {$offset}";
	$simple_goods_list1 = $db->getAll($sql);
	
	$sql = "select hp.*,d.name,case hp.source when '01' then '银联在线'
			 when '02' then '支付宝'
			 when '03' then '盛付通'
			 when '04' then '建设银行'
			 when '05' then '中国银行'
			 when '06' then '易付宝'
			 when '07' then '农业银行'
			 when '08' then '京东网银在线'
			 when '09' then '国际支付宝'
			 when '10' then '甬易支付'
			 when '11' then '富友支付'
			 when '12' then '连连支付'
			 when '13' then '财付通（微信支付）'
			 when '14' then '快钱'
			 when '15' then '网易宝'
			 when '16' then '银盈通支付'
			 when '17' then '鄞州银行'
			 when '18' then '智慧支付'
			 else '其他' end as source_name from ecshop.haiguan_pay hp inner join ecshop.distributor d on hp.application_key = d.distributor_id
			where 
			1 {$cond['simple_cond']} order by hp.created_stamp desc LIMIT 10 OFFSET {$offset}";
	$simple_goods_list2 = $db->getAll($sql);
	
	$sql="select count(*) from ecshop.haiguan_pay hp where 1 {$cond['simple_cond']}";
	$total = $db->getOne($sql);
	$temp_array = array_merge($simple_goods_list1,$simple_goods_list2);
	$result['pay_list'] = $temp_array;
	$result['total'] = $total;
	return $result;
}

?>




























