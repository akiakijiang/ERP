<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once ('function.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'admin/distribution.inc.php');
require_once (ROOT_PATH . 'includes/lib_common.php');

$platform_list= array(
	'360buy' => '京东',
	'miya' => '蜜芽',
	'fenxiao' => '申报系统分销订单'
);


global $db;
$sql_party_ids = "select party_id from romeo.party where PARENT_PARTY_ID = '65658'";
$party_ids = $db -> getCol($sql_party_ids);

if(!in_array($_SESSION['party_id'],$party_ids)){
	die('没有权限');
}

$act = isset ( $_REQUEST ['act'] ) ? $_REQUEST ['act'] : null;
$platform = isset ( $_REQUEST ['platform'] ) ? $_REQUEST ['platform'] : "360buy";

//判断组织是否为具体的业务单位
if(!party_explicit($_SESSION['party_id'])) {
    sys_msg("请选择具体的组织后再来录入订单");
}


QLog::log ( "跨境购支付信息导入开始：{$act} " );
if ($act) {
	switch ($act) {
		case 'upload':
			
			// excel读取设置
			$tpl = array('跨境购支付信息导入'  =>
				array('taobao_order_sn'=>'外部订单号',
					  'pay_type'=>'支付方式',
					  'pay_number'=>'支付流水号'
				));
                
			
			QLog::log ( '订单导入：' );
			/* 文件上传并读取 */
			$uploader = new Helper_Uploader ();
			$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
			
			if (! $uploader->existsFile ( 'excel' )) {
				$smarty->assign ( 'message', '没有选择上传文件，或者文件上传失败' );
				break;
			}
			
			// 取得要上传的文件句柄
			$file = $uploader->file ( 'excel' );
			
			// 检查上传文件
			if (! $file->isValid ( 'xls, xlsx', $max_size )) {
				$smarty->assign ( 'message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB' );
				break;
			}
			
			// 读取excel
			$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
			if (! empty ( $failed )) {
				$smarty->assign ( 'message', reset ( $failed ) );
				break;
			}
			
			/* 检查数据  */
			$rowset = $result ['跨境购支付信息导入'];

			// 订单数据读取失败
			if (empty ( $rowset )) {
				$smarty->assign ( 'message', 'excel文件中没有数据,请检查文件' );
				break;
			}
			$order_check_array = array('taobao_order_sn','pay_name','pay_number');
			
			$party_id = $_SESSION['party_id'];
			$order_items = Helper_Array::groupBy($rowset, 'taobao_order_sn');
			$index=0;
			$update_num=0;
			try{
				if($platform == "360buy"){
					foreach ($order_items as $key=>$order_attr) {
						$count = count($order_attr);
						global $db;
						for($index = 0 ; $index < $count ; $index ++){
							$taobao_order_sn = $order_attr[$index]['taobao_order_sn'];
							$pay_type = $order_attr[$index]['pay_type'];
							$pay_number =  $order_attr[$index]['pay_number'];
							$sql = "update ecshop.sync_jd_order_info set jd_pay_type= '{$pay_type}',jd_pay_number='{$pay_number}',last_updated_stamp=now() where order_id='{$taobao_order_sn}'";
							$db->query($sql);
							$update_num++;
						}
					}
				}else if($platform == "miya"){
					foreach ($order_items as $key=>$order_attr) {
						$count = count($order_attr);
						global $db;
						for($index = 0 ; $index < $count ; $index ++){
							$taobao_order_sn = $order_attr[$index]['taobao_order_sn'];
							$pay_type = $order_attr[$index]['pay_type'];
							$pay_number =  $order_attr[$index]['pay_number'];
							$sql = "update ecshop.sync_miya_order_info set miya_pay_type= '{$pay_type}',miya_pay_number='{$pay_number}',last_updated_stamp=now() where miya_order_id='{$taobao_order_sn}'";
							$db->query($sql);
							$update_num++;
						}
					}
				
				} else if($platform == "fenxiao"){
					foreach ($order_items as $key=>$order_attr) {
						$count = count($order_attr);
						global $db;
						for($index = 0 ; $index < $count ; $index ++){
							$taobao_order_sn = $order_attr[$index]['taobao_order_sn'];
							$pay_type = $order_attr[$index]['pay_type'];
							$pay_number =  $order_attr[$index]['pay_number'];
							$sql = "update ecshop.haiguan_order_info set payment_code = '{$pay_type}',trade_trans_no='{$pay_number}',last_updated_stamp=now() where tid='{$taobao_order_sn}'";
							$db->query($sql);
							$update_num++;
						}
					}
				}
				$smarty->assign ('message', "导入完毕！共更新{$update_num}条信息<br/>".$return_message );
			}catch(Exception $e){
				$return_message = $e->getMessage();
				$smarty->assign ('message', "导入失败！<br/>".$return_message );
			}
			$file->unlink ();
			break;
			exit();
		case 'update_pay_info':
			$platform = trim ( $_REQUEST ['platform'] );
			$taobao_order_sn = trim ( $_REQUEST ['taobao_order_sn'] );
			$pay_type = trim ( $_REQUEST ['pay_type'] );
			$pay_number = trim ( $_REQUEST ['pay_number'] );
			if($platform == "360buy"){
				$sql = "update ecshop.sync_jd_order_info set jd_pay_type= '{$pay_type}',jd_pay_number='{$pay_number}',last_updated_stamp=now() where order_id='{$taobao_order_sn}'";
			}else if($platform == "miya"){
				$sql = "update ecshop.sync_miya_order_info set jd_pay_type= '{$pay_type}',jd_pay_number='{$pay_number}',last_updated_stamp=now() where miya_order_id='{$taobao_order_sn}'";
			}else if($platform == "fenxiao"){
				$sql = "update ecshop.haiguan_order_info set payment_code = '{$pay_type}',trade_trans_no='{$pay_number}',last_updated_stamp=now() where tid='{$taobao_order_sn}'";			
			}
			$db->query($sql);
			break;
			exit();
			
	}
	
}

if($platform == "360buy"){
	$result = call_user_func('search_jd_orders',$_GET);
}else if($platform == "miya"){
	$result = call_user_func('search_miya_orders',$_GET);
}else if($platform == "fenxiao"){
	$result = call_user_func('search_fenxiao_orders',$_GET);
}
$result['startCalendar'] = isset($_REQUEST ['start_time'])?$_REQUEST ['start_time']:date("Y-m-d",strtotime("-10 days",time()));
$smarty->assign('order_list',$result['order_list']);
$smarty->assign('Pager',$result['Pager']);
$smarty->assign ( 'platform', $platform);
$smarty->assign('result',$result);
$smarty->assign ( 'party_id', $_SESSION ['party_id'] );
$smarty->assign('platform_list',$platform_list);
$smarty->display ('payInfoImport.htm' );

function getJdCondition() {
	global $ecs;
	$result = array();
	$condition = "";
	$appkey = trim ( $_REQUEST ['appkey'] );
	$taobao_order_sn = trim ( $_REQUEST ['taobao_order_sn'] );
	$pay_type = trim ( $_REQUEST ['pay_type'] );
	$pay_number = trim ( $_REQUEST ['pay_number'] );
	$start_time = $_REQUEST ['start_time'];
	$end_time = $_REQUEST ['end_time'];

	if($appkey != ""){
		$condition .= " AND tsc.nick like '%{$appkey}%' ";
	}
	if ($taobao_order_sn != '') {
		$condition .= " AND joi.order_id = '{$taobao_order_sn}' ";
	}else{
		$condition .= " AND joi.jd_pay_number ='' ";
	}
	if ($pay_type != '') {
		$condition .= " AND joi.jd_pay_type = '{$pay_type}' ";
	}
	if ($pay_number != '') {
		$condition .= " AND joi.jd_pay_number = '{$pay_number}' ";
	}
	if ($start_time != '') {
		$condition .= " AND joi.last_updated_stamp > '{$start_time}' ";
	}else{
		$time_ = date("Y-m-d",strtotime("-10 days",time()));
		$condition .= " AND joi.last_updated_stamp > '{$time_}'";
	}
	if ($end_time != '') {
		$condition .= " AND joi.last_updated_stamp < '{$end_time}' ";
	}
	$condition .= " AND tsc.shop_type='360buy_overseas'";
	$result['simple_cond'] = $condition;
	return $result;
}

function getMiyaCondition() {
	global $ecs;
	$result = array();
	$condition = "";
	$appkey = trim ( $_REQUEST ['appkey'] );
	$taobao_order_sn = trim ( $_REQUEST ['taobao_order_sn'] );
	$pay_type = trim ( $_REQUEST ['pay_type'] );
	$pay_number = trim ( $_REQUEST ['pay_number'] );
	$start_time = $_REQUEST ['start_time'];
	$end_time = $_REQUEST ['end_time'];

	if($appkey != ""){
		$condition .= " AND tsc.nick like '%{$appkey}%' ";
	}
	if ($taobao_order_sn != '') {
		$condition .= " AND moi.miya_order_id = '{$taobao_order_sn}' ";
	}else{
		$condition .= " AND moi.miya_pay_number ='' ";
	}
	if ($pay_type != '') {
		$condition .= " AND moi.miya_pay_type = '{$pay_type}' ";
	}
	if ($pay_number != '') {
		$condition .= " AND moi.miya_pay_number = '{$pay_number}' ";
	}
	if ($start_time != '') {
		$condition .= " AND moi.last_updated_stamp > '{$start_time}' ";
	}else{
		$time_ = date("Y-m-d",strtotime("-10 days",time()));
		$condition .= " AND moi.last_updated_stamp > '{$time_}' ";
	}
	if ($end_time != '') {
		$condition .= " AND moi.last_updated_stamp < '{$end_time}' ";
	}
	//$condition .= " AND moi.party_id='{$_SESSION['party_id']}'";
	$result['simple_cond'] = $condition;
	
	return $result;
}

function getFenxiaoCondition() {
	global $ecs;
	$result = array();
	$condition = "";
	$appkey = trim ( $_REQUEST ['appkey'] );
	$taobao_order_sn = trim ( $_REQUEST ['taobao_order_sn'] );
	$pay_type = trim ( $_REQUEST ['pay_type'] );
	$pay_number = trim ( $_REQUEST ['pay_number'] );
	$start_time = $_REQUEST ['start_time'];
	$end_time = $_REQUEST ['end_time'];

	if($appkey != ""){
		$condition .= " AND hap.nick like '%{$appkey}%' ";
	}
	if ($taobao_order_sn != '') {
		$condition .= " AND hoi.tid = '{$taobao_order_sn}' ";
	}
	if ($pay_type != '') {
		$condition .= " AND hoi.payment_code = '{$pay_type}' ";
	}
	if ($pay_number != '') {
		$condition .= " AND hoi.trade_trans_no = '{$pay_number}' ";
	}
	if ($start_time != '') {
		$condition .= " AND hoi.last_updated_stamp > '{$start_time}' ";
	}else{
		$time_ = date("Y-m-d",strtotime("-10 days",time()));
		$condition .= " AND hoi.last_updated_stamp > '{$time_}' ";
	}
	if ($end_time != '') {
		$condition .= " AND hoi.last_updated_stamp < '{$end_time}' ";
	}
	//$condition .= " AND moi.party_id='{$_SESSION['party_id']}'";
	$result['simple_cond'] = $condition;
	
	return $result;
}

function search_jd_orders($args) {
	global $db;
	$cond = getJdCondition();
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 10;
	$offset = $limit * ($page-1);
	$goods_list = array();
    
	$sqlc = "select count(1) from ecshop.sync_jd_order_info  joi
			inner join ecshop.taobao_shop_conf tsc on joi.application_key = tsc.application_key
		where 1 {$cond['simple_cond']} ";	
	$total = $db ->getOne($sqlc);
	
	$sql = "select tsc.nick,joi.order_id as taobao_order_sn,joi.jd_pay_type as pay_type,joi.jd_pay_number as pay_number,joi.last_updated_stamp 
			from ecshop.sync_jd_order_info joi
			inner join ecshop.taobao_shop_conf tsc on joi.application_key = tsc.application_key
			where 1 {$cond['simple_cond']} order by joi.last_updated_stamp desc LIMIT {$limit} OFFSET {$offset}";
	$simple_goods_list = $db->getAll($sql);
	$args['Pager'] = Pager($total,$limit,$page);
	$args['order_list'] = $simple_goods_list;
	return $args;
}

function search_miya_orders($args) {
	global $db;
	$cond = getMiyaCondition();
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 10;
	$offset = $limit * ($page-1);
	$goods_list = array();
    
	$sqlc = "select count(1) from ecshop.sync_miya_order_info moi
			inner join ecshop.taobao_shop_conf tsc on moi.application_key = tsc.application_key
		where 1 {$cond['simple_cond']} ";	
	$total = $db ->getOne($sqlc);
	
	$sql = "select tsc.nick,moi.miya_order_id as taobao_order_sn,moi.miya_pay_type as pay_type,moi.miya_pay_number as pay_number,moi.last_updated_stamp 
			from ecshop.sync_miya_order_info moi
			inner join ecshop.taobao_shop_conf tsc on moi.application_key = tsc.application_key
			where 1 {$cond['simple_cond']} order by moi.last_updated_stamp desc LIMIT {$limit} OFFSET {$offset}";
	$simple_goods_list = $db->getAll($sql);
	$args['Pager'] = Pager($total,$limit,$page);
	$args['order_list'] = $simple_goods_list;
	return $args;
}

function search_fenxiao_orders($args) {
	global $db;
	$cond = getFenxiaoCondition();
	$page = intval($args['page']);
	$page = max(1, $page);
	$limit = 10;
	$offset = $limit * ($page-1);
	$goods_list = array();
    
	$sqlc = "select count(1) from ecshop.haiguan_order_info  hoi
			inner join ecshop.haiguan_api_params hap on hap.application_key = hoi.application_key
			where 1 {$cond['simple_cond']} ";	
	$total = $db ->getOne($sqlc);
	
	$sql = "select hap.nick,hoi.tid as taobao_order_sn,hoi.payment_code as pay_type,hoi.trade_trans_no as pay_number,hoi.last_updated_stamp 
			from ecshop.haiguan_order_info hoi
			inner join ecshop.haiguan_api_params hap on hoi.application_key = hap.application_key
			where 1 {$cond['simple_cond']} order by hoi.last_updated_stamp desc LIMIT {$limit} OFFSET {$offset}";
	$simple_goods_list = $db->getAll($sql);
	$args['Pager'] = Pager($total,$limit,$page);
	$args['order_list'] = $simple_goods_list;
	return $args;
}