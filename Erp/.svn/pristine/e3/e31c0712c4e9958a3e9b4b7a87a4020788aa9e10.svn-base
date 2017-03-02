<?php
define('IN_ECS', true);
require('../includes/init.php');
require("../function.php");
require_once('../includes/lib_service.php');
// require_once('../includes/lib_order_mixed_status.php');//By Sinri
// require_once('../includes/lib_bonus.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_mssql.php');
require_once(ROOT_PATH . 'conf/member.config.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_refund.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');

require_once('../includes/lib_postsale_cache.php');

admin_priv('customer_service_manage_order', 'order_view');

$sale_support_type_map = array(
	array(
	    10 => '当天未发货',
		11 => '缺货',
		2 => '破损',
		6 => '未按约定时间发货',
		12 => '错发',
		1 => '漏发',
		13 => '顾客不要，找件追回',
//		14 => '找件追回',
		15 => '商品缺货，找件追回',
		5 => '7天无理由退换货',
		16 => '原单退回',
		17 => '仓库收到退件'),
	array(
		18 => '实物与描述不符',
		19 => '生产日期问题',
		4 => '质量问题',
		20 => '使用后过敏',
		21 => '怀疑假货'),
	array(
		3 => '快递延误',
		22 => '虚假签收',
		23 => '原单退回破损',
		24 => '快递运输',
		25 => '乱收费',
		26 => '不送货上门',
		27 => '服务态度',
		30 => '快递破损',
		31 => '遗失',
		32 => '双面单',
		33 => '重复面单',
		34 => '无揽件信息'),
	array(
		28 => '活动差价',
		8 => '退运费',
		7 => '发票问题',
		29 => '恶意售后',
		9 => '其他')
);

$plan_list = array(
	//'th' => '退货不退款',
	'tk' => '仅退款',
	'thtk' => '退货退款',
	'hh' => '换货',
	'zh' => '追回',
	'bj' => '录单补寄',
	'ms' => '驳回/无需处理'
);

 /* 权限对应 */
$user_priv_list = array(
	'LCZ'  => array('priv' => 'lcz_sale_support',    'value' => '售后巡查'),
	'KF'   => array('priv' => 'kf_postsale_support', 'value' => '客服'),
	'FXKF'   => array('priv' => 'kf_postsale_support_fenxiao', 'value' => '分销客服'),
	'BJWL' => array('priv' => 'bjwl_sale_support',   'value' => '北京物流'),
	'SHWL' => array('priv' => 'shwl_sale_support',   'value' => '上海物流'),
	'DGWL' => array('priv' => 'dgwl_sale_support',   'value' => '东莞物流'),
	'WBWL' => array('priv' => 'wbwl_sale_support',	'value' => '外包物流'),
	'WHWL' => array('priv' => 'whwl_sale_support',   'value' => '武汉物流'),
	'CDWL' => array('priv' => 'cdwl_sale_support',   'value' => '成都物流'),
	'JPWL' => array('priv' => 'jpwl_sale_support',   'value' => '精品物流'),
	'SZWL' => array('priv' => 'szwl_sale_support',   'value' => '苏州物流'),
	'CW'   => array('priv' => 'cw_sale_support',     'value' => '财务'),
	'DZ'   => array('priv' => 'dz_sale_support',     'value' => '店长'),
	'CG'   => array('priv' => 'cg_sale_support',     'value' => '快递理赔客服')
);

 $request = //请求
     isset($_REQUEST['request']) &&
     in_array($_REQUEST['request'], array('ajax'))
     ? $_REQUEST['request']
     : null ;
 $ajax = isset($_REQUEST['ajax'])?$_REQUEST['ajax']:'';
 $act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add','finish', 'cancel', 'implement', 'pending')) 
    ? $_REQUEST['act'] 
    : null ;
$info =    // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;


 /*
 * 处理post请求
 */
if (!empty($_POST)) {
	
	switch ($act) {
		/* 发送信息 */
	    case 'add' :
	    	$order_id = $_REQUEST['order_id'];
	    	if(!$order_id){
	    		Sys_msg("订单信息有误");
	    	}
	    	//防止缓存造成的数据错误,要查到最新的数据
	    	$last_message = $db->getRow("select * from ecshop.sale_support_message where order_id = '{$order_id}' order by created_stamp desc limit 1");
	    	$now = date("Y-m-d H:i:s");
	    	$send_by = $_SESSION['admin_name'];
	    	$next_process_group = $_REQUEST['next_process_group'];
            $support_type = $_REQUEST['support_type'];
            $status = "OK"; 
            $message = trim($_REQUEST['message']);
            $program = isset($last_message['program'])?$last_message['program']:trim($_REQUEST['program']);
            $send_type = $_REQUEST['send_type'];
            $last_ststus = $_REQUEST['status'];
            if($send_type == 'send_message' ){ //等待状态下，仅发送也为等待状态
            	if($last_ststus == 'PENDING'){
            		$status = "PENDING"; 
            	}else if($last_message['status'] == 'FINISHED'){  
            		$next_process_group = '';
            	}
            }
			$file0 = $_FILES['file0'];
            if($file0['error']==1 || $_FILES['file1']['error']==1||$_FILES['file2']['error']==1||$_FILES['file3']['error']==1||$_FILES['file4']['error']==1){
            	$tip="存在5M以上大小的插入内容，消息插入失败！请检查后重试";
            }else{
	            $sql = "
	               insert into ecshop.sale_support_message (created_stamp,send_by,order_id,support_type,status,message,next_process_group, program)
	               values('{$now}','{$send_by}','{$order_id}','{$support_type}','{$status}','{$message}','{$next_process_group}','{$program}')
	               ";  
	            $result = $db->query($sql);  
	            
	            if($file0['error']==0 || $_FILES['file1']['error']==0||$_FILES['file2']['error']==0||$_FILES['file3']['error']==0||$_FILES['file4']['error']==0){
	            	
	            	$message_id = $db->getOne("select sale_support_message_id from ecshop.sale_support_message order by sale_support_message_id desc limit 1");
	            	upload_pic($message_id);
	            }
	            $tip = "发送成功";
            }
            $smarty -> assign('tip', $tip);
        break;
        
        /* 完结售后沟通 */
	    case 'finish' :
	    	$order_id = $_POST['res_order_id'];
			$res_person = $_POST['res_person'];
			$message = $_POST['res_message'];
			$support_type = $_REQUEST['support_type'];
			$plan = $_POST['plan'];
			$send_by = $_SESSION['admin_name'];
			$now = date("Y-m-d H:i:s");
//			Qlog::log('support_type='.$support_type);
			$plans = explode(',',$plan);
			$program = "";
			if(!empty($plans)){
				foreach ( $plans as $key => $plan ) {
	            	if ( $plan_list[$plan] != '' ) {
		            	$program .= $plan_list[$plan].',';
	                }
				}
			}
			$program = substr($program, 0, strlen($program)-1);
			
			$sql = "
			    insert into ecshop.sale_support_message 
				(created_stamp,send_by,order_id,support_type,status,message,program,responsible_person)
				VALUES
				('{$now}','{$send_by}','{$order_id}','{$support_type}','FINISHED','{$message}','{$program}','{$res_person}')
			";
			$db -> query($sql);
			$tip = "已经成功确认方案";
			$smarty -> assign('tip', $tip);
		break;
//		case  'pending':
//		    $last_message = $_REQUEST['last_message'];
//			$send_by = $_SESSION['admin_name'];
//			$order_id = $_REQUEST['order_id'];
//			$support_type = $_REQUEST['support_type'];
//			$status = 'PENDING';
//			$program = $_REQUEST['program'];
//			$message = '请'.$user_priv_list[$_REQUEST['next_process_group']]['value'].'注意查收消费者退回货物';
//			$next_process_group = $_REQUEST['next_process_group'];
//			$now = date("Y-m-d H:i:s");
////			Qlog::log($order_id.'-'.$support_type.'-'.$message.'-'.$next_process_group.'-'.$program);
//			$sql = "
//				insert into ecshop.sale_support_message 
//					(created_stamp,send_by,order_id,support_type,status,message,next_process_group, program)
//                values
//                	('{$now}','{$send_by}','{$order_id}','{$support_type}','{$status}','{$message}','{$next_process_group}','{$program}')";
////            Qlog::log($sql);
//            $db -> query($sql); 
//            $tip = "操作成功";
//            $smarty -> assign('tip', $tip);
//		break;
	}	
	

	//SINRI UPDATE CACHE
	POSTSALE_CACHE_updateMessages(null,180,$order_id);
}
if($ajax == 'change_support_type') {
	$key = $_REQUEST['support_type_key'];
	$json = new JSON;
	$support_type = $sale_support_type_map[$key];
//	var_dump($support_type);
	print $json->encode($support_type);
	exit();
}

$order_id = $_REQUEST['order_id'];
$order = order_get($order_id);

if(isset($_REQUEST['info']) && !empty($_REQUEST['info']) && empty($act)){
	$smarty -> assign('tip', $_REQUEST['info']);
}

$sql = " select m.sale_support_message_id,m.created_stamp,m.send_by,m.support_type,m.status,m.message, program, next_process_group , group_concat(p.path) path, ea.real_name, ea.department
	    from ecshop.sale_support_message m 
	    left join ecshop.sale_support_message_pic p on m.sale_support_message_id = p.sale_support_message_id 
	    left join ecshop.ecs_admin_user ea on m.send_by = ea.user_name
	    where order_id = '{$order_id}' 
	    group by m.sale_support_message_id
	    order by created_stamp desc";
$message_list = $db -> getAll($sql);

if($message_list){
	$sale_support_type_list = $sale_support_type_map[0] + $sale_support_type_map[1] + $sale_support_type_map[2] + $sale_support_type_map[3];
	foreach($message_list as $key => $item){
		$message_list[$key]['support_type_detail'] = $sale_support_type_list[$item['support_type']];
		$message_list[$key]['next_process_name'] = $user_priv_list[$item['next_process_group']]['value'];
		if($item['path']=="" || $item['path']==null)$message_list[$key]['pic_num']=0;
		else	$message_list[$key]['pic_num']=count(explode(",",$item['path']));
	}	
}

//最后一条记录,判断回复权限
$sql="select order_id, sale_support_message_id, next_process_group, program, support_type, status
	  from ecshop.sale_support_message 
	  where order_id = '{$order_id}' order by created_stamp desc limit 1";
$last_message = $db->getRow($sql);
if($last_message['status'] == 'FINISHED'){
	$last_message['support_type'] = 0;
}

$fSQL="SELECT 1 FROM ecshop.sale_support_message WHERE order_id = '{$order_id}' AND program != '' LIMIT 1";
$exist_program = $db->getRow($fSQL); 

//检查是否可以发送信息
if( empty($last_message) ||  //还没有售后沟通信息
	(
		check_admin_user_priv($_SESSION['admin_name'], $user_priv_list[$last_message['next_process_group']]['priv']) ||
		($last_message['next_process_group']=='KF' && check_admin_user_priv($_SESSION['admin_name'],$user_priv_list['FXKF']['priv']))
	) || //有处理权限
	 $last_message['next_process_group'] == ''){ //下一个处理团队为空（已确认方案）
	$smarty->assign('admin_action',true);
}else{
	$smarty->assign('admin_action',false);
}

//检查是否可以确认方案
if( empty($exist_program) && 
	(check_admin_user_priv($_SESSION['admin_name'], 'kf_postsale_support') || check_admin_user_priv($_SESSION['admin_name'], 'kf_postsale_support_fenxiao')) ){ //没有确认过方案 且拥有客服权限
//	Qlog::log('kf_admin=true');
	$smarty->assign('kf_admin',true);
}else {
//	Qlog::log('kf_admin=false');
	$smarty->assign('kf_admin',false);
}

//检查是否可以仅发送信息
if( empty($last_message) ){ //还没有售后沟通信息
	$smarty->assign('send_message',false);
}else{
	$smarty->assign('send_message',true);
}


////检查是否有等待追回权限
//if ( check_admin_user_priv($_SESSION['admin_name'], 'shwl_sale_support') || check_admin_user_priv($_SESSION['admin_name'], 'dgwl_sale_support') ) {
//	$smarty->assign('wl_admin', true);
//}
//else {
//	$smarty->assign('wl_admin', false);
//}
//检查现在是否可进入PENDING状态
if ( ($last_message['next_process_group'] == 'SHWL' || $last_message['next_process_group'] == 'DGWL'||
$last_message['next_process_group'] == 'WHWL' || $last_message['next_process_group'] == 'CDWL'|| 
$last_message['next_process_group'] == 'JPWL' || $last_message['next_process_group'] == 'SZWL'
) && $last_message['status'] !== 'PENDING') {
	$smarty->assign('pending', true);
}
else {
	$smarty->assign('pending', false);
}

if($last_message['support_type'] != null) {
	foreach($sale_support_type_map as $key => $sale_support_types){
		foreach($sale_support_types as $key1 => $sale_support_type){
			if($key1 == $last_message['support_type']) {
				$last_message['support_types'] = $key;
				break 2;
			}
		}		
	}
}else{
	$last_message['support_types'] = 0;
	$last_message['support_type'] = '13';
}



$smarty->assign('last_message',$last_message);


//看看是否有物流组收到件后，反馈信息
//$sql = " select 1 from ecshop.sale_support_message where support_type = '7' and order_id = '{$order_id}' ";
//$flag = $db -> getOne($sql);
//if($flag){
//判断该订单是否有物流组收到件后，反馈信息的类型
$sql = "
    select s.service_id ,count(sog.order_goods_id) as goods_amount,og.goods_name,s.apply_reason,s.service_status,
           dc.return_value as deliver_company,dn.return_value as deliver_number,oi.consignee,ifnull(oi.mobile,oi.tel) as mobile
	from ecshop.service s
	inner join ecshop.service_order_goods sog on sog.service_id = s.service_id
	inner join ecshop.ecs_order_goods og on og.rec_id = sog.order_goods_id
	inner join ecshop.ecs_order_info oi on oi.order_id = s.order_id
	left join ecshop.service_return dc on dc.service_id = s.service_id and dc.return_name = 'deliver_company'
	left join ecshop.service_return dn on dn.service_id = s.service_id and dn.return_name = 'deliver_number'
	where s.service_status <> 3 and sog.is_approved = 1 and s.service_type in ('1','2','6') and s.order_id = '{$order_id}'
	group by s.service_id,sog.order_goods_id
	order by s.apply_datetime desc
";

$return_detail_tmp = $db -> getAll($sql);
$return_detail = array();
//待整合
foreach($return_detail_tmp as $item){
	if(!array_key_exists($item['serivice_id'],$return_detail)){
		$return_detail[$item['service_id']] = $item;
		$return_detail[$item['service_id']]['goods_detail'][] = array(goods_name => $item['goods_name'],goods_amount => $item['goods_amount']);
	}else{
		array_push($return_detail[$item['service_id']]['goods_detail'][],array(goods_name => $item['goods_name'],goods_name => $item['goods_amount']));
	}
}
$smarty->assign('return_detail', $return_detail);
//}

//根据order_id找到refund_id对应的淘宝下载图片信息
$sql = "select strm.* from ecshop.sync_taobao_refund_message strm" .
		" left join romeo.refund r on strm.refund_id = convert(r.refund_id using utf8) " .
		" where r.order_id = '{$order_id}' order by strm.created desc";
$sync_refund_m = $db->getAll($sql);	
if(count($sync_refund_m)!=0){	
	foreach($sync_refund_m as $key => $refund_m){
		if($refund_m['pic_urls'] != null){
			$sync_refund_m[$key]['pic_urls'] = explode(";",$refund_m['pic_urls']);
		}
	}
}

$facility_name = getFacility($_REQUEST['order_id']);
$smarty->assign('sync_refund_m',$sync_refund_m);		

$smarty->assign('facility_name',$facility_name);
$smarty->assign('message_list',$message_list);
$smarty->assign('order',$order);
$smarty->assign('party_name',$_REQUEST['party_name']);
$smarty->assign('support_type_list',$sale_support_type_map);
$smarty->display('sale_support/sale_support.htm');

function order_get($order_id){
	global $db;
	$sql = "
	   select oi.order_sn,oi.order_id,p.name as party_name
	   from ecshop.ecs_order_info oi 
	   inner join romeo.party p on p.party_id = oi.party_id
	   where oi.order_id = '{$order_id}'
	";
	$result = $db -> getRow($sql);
	return $result;
}

//上传图片
function upload_pic($message_id){
	global $db;
	$flag = true;
	$tip = "";
	//图片保存目录
	$path = "../../assets/upload/";
	//允许上传的文件类型
	$type = array ("jpg","gif","bmp","jpeg","png");
	$file_arr = array($_FILES['file0'],$_FILES['file1'],$_FILES['file2'],$_FILES['file3'],$_FILES['file4']);
	if(!is_numeric($message_id)){
		$tip = "message_id错误:".$message_id;
		$flag = false;
	}
	$file_attr = array();
	foreach($file_arr as $file){
		if($file['error']==4) continue;
		if($file['error']>0 ){
			$tip = "上传文件有误：".$file['error'];
			$file_attr = array();
			$flag = false;
			break;
		}
		if(!in_array(strtolower(fileext($file['name'])),$type)){
			$text = implode(",",$type);
			$tip = "您只能上传以下类型的文件：".$text;
			$file_attr = array();
			$flag = false;
			break;
		}
		//以时间来命名图片，防止重名
		$name = $message_id;
		$file_name ="";
		$tmp_name = $file['tmp_name'];
		$times = 0;
		do{
			$name .= time();
			$name .= rand(1,1000);
			$name .= strrchr($file['name'],".");
			$file_name = $path.$name;
			$times = 1+$times;
		}while($times<3&&file_exists($file_name));
		//如果文件路径不存在，就创建一个
		if(!is_dir($path)){
			mkdir($path);
		}
		//为该路径附权限
		@chmod($path,0777);
		if(file_exists($file_name)){
			$tip = "执行冲突，请稍后再试";
			$file_attr = array();
			$flag = false;
			break;
		}else if($flag){
			$file_ok = array();
			move_uploaded_file($tmp_name,$file_name);
			$pic_desc = trim($_REQUEST['desc']);
			$pic_desc = addslashes($pic_desc);
			$file_ok['name'] = $name;
			$file_ok['file_name'] = $file_name;
			$file_ok['pic_desc'] = $pic_desc;
			$file_attr[]=$file_ok;
		}	
	}
	
	foreach($file_attr as $file){
		$sql = "
			   insert into ecshop.sale_support_message_pic
			   (sale_support_message_id,pic_name,path,pic_status,pic_desc)
			   values
			   ('{$message_id}','{$file['name']}','{$file['file_name']}','OK','{$file['pic_desc']}')
			";
			QLog::log("add_file_message_pic :".$sql);
		$db -> query($sql);
	}
	
	if(!$flag) {
		$sql = "delete m,mp " .
				" from ecshop.sale_support_message m " .
				" left join ecshop.sale_support_message_pic mp on m.sale_support_message_id = mp.sale_support_message_id" .
				"  where m.sale_support_message_id = ".$message_id;
//		QLog::log("delete_message_pic_sql : ".$sql);		
		$db->query($sql);
		header("Location: sale_support.php?order_id=".$_REQUEST['order_id']."&info=".urlencode($tip)); exit;
	}
}

//获取文件后缀名函数
function fileext($filename)
{
	return substr(strrchr($filename, '.'),1);
}


//获取发货仓库
function getFacility($order_id){
    $sql = "select f.facility_name from ecshop.ecs_order_info o
        left join romeo.facility f on f.facility_id = o.facility_id
        where o.order_id = '{$order_id}'";
    $res = $GLOBALS['db']->getAll($sql);
    return $res[0]['facility_name'];
}
?>