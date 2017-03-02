<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once('distribution.inc.php');
admin_priv('haiguan_shop_info');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
include_once(ROOT_PATH . 'admin/function.php'); 
require_once (ROOT_PATH . 'includes/debug/lib_log.php');


/*
 * 跨境购店铺信息维护（涉及到店铺的appkey,appsecret,uin）
 * by hzhang1 2015-07-24
 */
$act = $_REQUEST ['act'];
$req = isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null;
$shop_flag_list= array(
	'-1' => '所有',
	'0001' => '淘宝网',
	'0002' => '天猫',
	'0003' => '天猫国际',
	'0006' => '苏宁易购',
	'0018' => '京东全球购',
	'1050' => '蜜芽宝贝',
	'1058' => '云猴网',
	'1134' => '拼多多',
	'1128' => '海带天下',
	'1081' => '母婴之家',
	'0008' => '洋码头',
	'0000' => '电商自有平台'
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
        case 'get_select_shop':
            $nick = $_REQUEST['q'];
            $sql = "
			select nick,party_id,application_key from ecshop.taobao_shop_conf
			WHERE party_id = '{$_SESSION['party_id']}' and
			nick like '%{$nick}%' and status = 'OK' limit 20
			";
			$result=$GLOBALS['db']->getAll($sql);	
            if ($result)
                print $json->encode($result);
            else{
            	$sql = "select name as nick,party_id,distributor_id as application_key from ecshop.distributor where party_id = '{$_SESSION['party_id']}' and name like '%{$nick}%' and status = 'NORMAL' limit 20";
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

if ($_REQUEST['type'] == '店铺导出CSV') {
	$shop_lists = search_shops($session_party_id,$cond,null,null);
	$shop_list = $shop_lists['shops_list'];
	$smarty->assign('shop_list', $shop_list);
	header ( "Content-type:application/vnd.ms-excel" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "店铺导出CSV" ) . ".csv" );
	$out = $smarty->fetch ( 'oukooext/haiguan_shop_csv.htm' );
	echo iconv ( "UTF-8", "GB18030", $out );
	exit ();
	
} else if($_REQUEST['type'] == '批量打开同步') {
	global $db;
	$shop_lists = search_shops($session_party_id,$cond,null,null);
	$shop_list = $shop_lists['shops_list'];
	$application_keys = array();
	foreach($shop_list as $key => $shop) {
		$application_keys[] = $shop['application_key'];
	}
	$open_sync_sql = "update ecshop.haiguan_api_params set status = '1' where ".db_create_in($application_keys,'application_key');
	if($db->query($open_sync_sql)) {
		$message_success = "批量打开同步成功！";
		$smarty->assign('message_success',$message_success);	
	} else {
		$message_error = "批量打开遇到了点问题，请重试！";
		$smarty->assign('message_error',$message_error);
	}
	$smarty->assign('message_success',$message_success);
} else if($_REQUEST['type'] == '批量关闭同步') {
	global $db;
	$shop_lists = search_shops($session_party_id,$cond,null,null);
	$shop_list = $shop_lists['shops_list'];
	$application_keys = array();
	foreach($shop_list as $key => $shop) {
		$application_keys[] = $shop['application_key'];
	}
	$close_sync_sql = "update ecshop.haiguan_api_params set status = '0' where ".db_create_in($application_keys,'application_key');
	if($db->query($close_sync_sql)) {
		$message_success = "批量关闭同步成功！";	
		$smarty->assign('message_success',$message_success);	
	} else {
		$message_error = "批量关闭遇到了点问题，请重试！";
		$smarty->assign('message_error',$message_error);
	}
}

$page = intval($_REQUEST['page']);
$page = max(1, $page);
$limit = 10;
$offset = $limit * ($page-1);

$cond = getCondition();
$session_party_id = $_SESSION['party_id'];
$result = search_shops($session_party_id,$cond,$limit,$offset);

$smarty->assign('shop_list',$result['shops_list']);
$smarty->assign('message_error',$message_error);
$smarty->assign('message_success',$message_success);
$smarty->assign('shop_flag_list',$shop_flag_list);
$smarty->assign('Pager',pager($result['total'],$limit,$page));

$smarty->display ( 'haiguan_shop_info.html' );


function getCondition() {
	global $ecs;
	$result = array();
	$condition = "";
	$searchnick = trim ( $_REQUEST ['searchnick'] );
	$start_time = $_REQUEST ['start_time'];
	$end_time = $_REQUEST ['end_time'];

	if ($searchnick != '') {
		$condition .= " AND nick LIKE '%{$searchnick}%' ";
	}
	if ($start_time != '') {
		$condition .= " AND created_stamp > '{$start_time}' ";
	}
	if ($end_time != '') {
		$condition .= " AND created_stamp < '{$end_time}' ";
	}

	$result['simple_cond'] = $condition;
	
	return $result;
}


function search_shops($party_id,$cond,$limit,$offset) {
	global $db;
	$sql = "select * from ecshop.haiguan_api_params
			where 
			party_id = '{$_SESSION['party_id']}'  {$cond['simple_cond']} order by created_stamp desc";
	if($limit != null && $offset != null) {
		$sql .= " LIMIT {$limit} OFFSET {$offset}";
		
		$sql_count="select count(*) from ecshop.haiguan_api_params where party_id = '{$_SESSION['party_id']}' {$cond['simple_cond']}";
		$total = $db->getOne($sql_count);
		$result['total'] = $total;
	}
	$simple_shops_list = $db->getAll($sql);
	$result['shops_list'] = $simple_shops_list;
	
	return $result;
}

?>




























