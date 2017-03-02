<?php
define ( 'IN_ECS', true );

require ('../includes/init.php');
require_once ('../function.php');
require_once (ROOT_PATH . 'includes/cls_json.php');
require_once ("RomeoApi/lib_inventory.php");
require_once (ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
require_once ('../includes/lib_main.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');

/*admin_priv('kf_sale_support');*/

$request = //请求
     isset($_REQUEST['request']) &&
     in_array($_REQUEST['request'], array('ajax'))
     ? $_REQUEST['request']
     : null ;
$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('search_facility','search_payment','search_support_list','indistinct_search','finish')) 
    ? $_REQUEST['act'] 
    : null ;
$info =    // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;
    
//查看类型
$check_type = array (
    0 => '全部', 
    1 => '待我方回复', 
    2 => '待对方回复', 
    3 => '已完结' 
);

//售后沟通类型
$sale_support_type_map = array(
    1 => '错发/漏发',
	2 => '破损',
	3 => '未收到货',
	4 => '质量问题',
	5 => '7天无理由退货',
	6 => '未按约定时间/缺货',
	7 => '发票问题',
	8 => '退运费',
	9 => '其他',
);
    
if($request == 'ajax'){
	$json = new JSON;
	
	
	switch($act){
		//可用仓库搜索
		case 'search_facility':
			
			$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
			$facility = get_available_facility();
			$facility_list = array();
			//将facility处理下,以便json转化
			foreach($facility as $key => $value){
				//将其转化为字符串类型，以免报错
				$key = $key."";
				array_push($facility_list,array('facility_id' => $key,'facility_name' => $value));
			}

			print $json->encode($facility_list);
	    break;
	    
	    //搜索支付方式
		case 'search_payment':
			$limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 30 ;
			$payment = payment_get($_POST['q'], $limit);
			
			print $json->encode($payment);
		break;

		//进行符合条件搜索
		case 'search_support_list':
			$conf = "";
			$conf = get_condition();
			$support_list_tmp = get_support_list($conf);
			$support_list = array();
			$admin_name = $_SESSION['admin_name'];
			
			//这里处理是对查看类型的信息选择
			$check_type = $_REQUEST['check_type'];
			
			//如果为空，则赋值成全部
			if(empty($check_type)){
				$check_type = 0;
			}
			$support_list = get_support_list_by_check_type($check_type,$support_list_tmp,$admin_name);
			
			
			print $json->encode($support_list);
		break;
		
		case 'indistinct_search':
			$keyword = trim($_REQUEST['keyword']);
			$support_list = array();
			$conf = "";
			//下面进行关键字的智能区分
			
			//以数字开头，数字字母或中文结尾的判定为者淘宝订单号或订单号
			$preg = "/^[0-9]+[0-9-a-zA-Z]*[\x{4e00}-\x{9af5}]*$/u";
			if(preg_match($preg,$keyword) && strlen($keyword)>8){
				$conf = " and (oi.order_sn = '{$keyword}' or oi.taobao_order_sn = '{$keyword}') ";
				$support_list = get_support_list($conf);
			}
			
			
			//英文字母开头，英文或数字结尾的为发件人
			if(!$support_list){
				$preg = "/^[A-Za-z]+[A-Za-z0-9]*$/";
				if(preg_match($preg,$keyword)){
					$conf = " and sm.send_by = '{$keyword}' ";
					$support_list = get_support_list($conf);
				}
			}
			
	        //含有中文，判定为收货人
			if(!$support_list){
				$preg = "/^[0-9-a-zA-Z]*[\x{4e00}-\x{9af5}]*[0-9-a-zA-Z]*$/u";
				if(preg_match($preg,$keyword)){
					//用like感觉消耗太大，还是相等的好
					$conf = " and oi.consignee = '{$keyword}' ";
					$support_list = get_support_list($conf);
				}
			}
			
	        foreach($support_list as $key => $support){
			    //处理咨询类型
			    $support_list[$key]['support_type_name'] = $sale_support_type_map[$support['support_type']];
			}
			
			print $json->encode($support_list);
	}
	
	exit;
}

//添加完结记录
if($act == 'finish'){
	$order_sn = trim($_POST['order_sn']);
	$res_person = $_POST['res_person'];
	$message = $_POST['message'];
	$order_id = order_id_get($order_sn);
	$send_by = $_SESSION['admin_name'];
	$now = date("Y-m-d H:i:s");
	//将其设为9 做沟通结束的标志
	$support_type = 9;
	if(check_finish($order_id)){
		$tip = "该订单已经完结";
		$smarty -> assign('tip', $tip);
	}else{
		//先将沟通状态改成FINISHED，之后再插入一条完结数据
		$sql = "update ecshop.sale_support_message set status = 'FINISHED' where order_id = '{$order_id}'";
		$db -> query($sql);
		$sql = "
		    insert into ecshop.sale_support_message 
			(created_stamp,send_by,order_id,support_type,status,message,responsible_person)
			VALUES
			('{$now}','{$send_by}','{$order_id}','{$support_type}','FINISHED','{$message}','{$res_person}')
		";
		$db -> query($sql);
		$tip = "已经成功执行";
		$smarty -> assign('tip', $tip);
		//载入时直接显示此订单信息
		$smarty -> assign('showOrderSn', $order_sn);
	}
}


$smarty->assign ( 'facilitys', get_possible_facility () );
$smarty->assign ( 'pay_type', get_possible_payment () );
$smarty->assign ( 'check_type', $check_type );
//$smarty->assign ( 'support_type_list', $_CFG ['adminvars'] ['support_type'] );
$smarty->assign ( 'support_type_list', $sale_support_type_map );
$smarty->display ( 'sale_support/sale_support_list.htm' );

function get_possible_payment($party_id = null) {
	global $db;
	if ($party_id == null) {
		$party_id = $_SESSION ['party_id'];
	}
	$sql = "
 	    select pay_id,pay_name
		from ecshop.taobao_shop_conf 
		where party_id = '{$party_id}'
 	";
	$result = $db->getAll ( $sql );
	return $result;
}

function get_possible_facility($party_id = null) {
	global $db;
	if ($party_id == null) {
		$party_id = $_SESSION ['party_id'];
	}
	$sql = "
 	    select sc.facility_id,f.facility_name 
		from ecshop.taobao_shop_conf sc
		inner join romeo.facility f on f.facility_id = sc.facility_id
		where sc.party_id = '{$party_id}' 
 	";
	$result = $db->getAll ( $sql );
	return $result;
}

function payment_get($keyword,$limit){
	global $db;
	$keyword = trim($keyword);
	$conf = "";
	if(strlen($keyword) > 0){
		$conf = " and pay_name like '%{$keyword}%' ";
	}
	$sql = "
	   select pay_id,pay_name 
	   from ecshop.ecs_payment 
	   where enabled = '1' $conf
	   limit {$limit}
	";
	$result = $db->getAll($sql);
	return $result;
}

//综合查询条件
function get_condition(){

	$conf = "";
	$support_type = stripslashes($_REQUEST['support_type']);
	$check_type = stripslashes($_REQUEST['check_type']);
	$facility_id = stripslashes($_REQUEST['facility_id']);
	$pay_id = stripslashes($_REQUEST['pay_id']);
	$start_time = stripslashes($_REQUEST['start_time']);
	$end_time = stripslashes($_REQUEST['end_time']);
	$check_type = stripslashes($_REQUEST['check_type']);
		
	//将合理的搜索条件写入$conf
	if($support_type > 0 && $support_type < 9){
		$conf .= " and sm.support_type = '{$support_type}' ";
	}
	
	if(isset($facility_id) && !empty($facility_id)){
		$conf .= " and oi.facility_id = '{$facility_id}' ";
	}
	
	if(isset($pay_id) && !empty($pay_id)){
		$conf .= " and oi.pay_id = '{$pay_id}' ";
	}
	
	if(isset($start_time) && !empty($start_time)){
		$conf .= " and oi.order_time >= '{$start_time}' ";
	}
	
	if(isset($end_time) && !empty($end_time)){
		$conf .= " and oi.order_time <= '{$end_time}' ";
	}
	
	//查看类型已完结的可以在这里做限制
	if(isset($check_type) && !empty($check_type) && $check_type == 3){
		$conf .=" and sm.status = 'FINISHED' ";
	}
	
	//待我方回复和对方回复的要限制成未完结状态
	if(isset($check_type) && !empty($check_type) && ($check_type == 1 || $check_type ==2)){
		$conf .=" and sm.status = 'OK' ";
	}
	
	//每个order_sn只展示一条记录
	$conf .=" and sm.sale_support_message_id =(select msm.sale_support_message_id from ecshop.sale_support_message msm where msm.order_id = sm.order_id order by msm.created_stamp desc limit 1) ";
	return $conf;
}

//查询数据
function get_support_list($conf = null){
	global $db;
	
	$sql = "
	    select oi.order_sn,oi.order_id,sm.created_stamp,sm.support_type,sm.message,p.pay_name,
	           f.facility_name,sm.recieve_member_ids,sm.recieve_group_ids,sm.status,sm.send_by
		from ecshop.sale_support_message sm
		inner join ecshop.ecs_order_info oi on oi.order_id = sm.order_id
		inner join ecshop.ecs_payment p on p.pay_id = oi.pay_id
		inner join romeo.facility f on f.facility_id = oi.facility_id
		where oi.party_id = '{$_SESSION['party_id']}' and oi.order_type_id = 'SALE'
		$conf
		order by oi.order_sn,oi.order_time desc
	";
	Qlog::log($sql);
	$support_list = $db -> getAll($sql);
	return $support_list;
}

//判断该成员是否为接收人员
function is_reciever($order_id,$user_id = null){
	global $db;
	if($user_id == null){
		$user_id = $_SESSION['admin_id'];
	}
	//取到该订单的发送的最后一条售后沟通
	$sql = "
	     select recieve_member_ids,recieve_group_ids,send_by
		 from ecshop.sale_support_message 
		 where order_id = '{$order_id}' and status = 'OK'
		 order by created_stamp DESC
		 limit 1
	";
	$tmp = $db -> getRow($sql);
	
	//先判断是否为个人发送
	if(isset($tmp['recieve_member_ids']) && !empty($tmp['recieve_member_ids'])){
		$recieve_memeber = explode(",",$tmp['recieve_member_ids']);
	    array_pop($recieve_memeber);
		if(in_array($user_id,$recieve_memeber)){
			return true;
		}
	}
	//判断是否在小组中
	if(isset($tmp['recieve_group_ids']) && !empty($tmp['recieve_group_ids'])){
		$recieve_group = explode(",",$tmp['recieve_group_ids']);
		array_pop($recieve_group);
		if(is_group_reciever($user_id,$recieve_group,$tmp['send_by'])){
			return true;
		}
	}
	return false;
}

//判断是否为小组成员
function is_group_reciever($user_id,$recieve_group,$send_by = null){
	global $db;
	if(!empty($recieve_group)){
		$conf = "";
		//搜索出发送者所在的小组,屏蔽之
		if($send_by != null){
			$sql = "
			   select group_id
			   from ecshop.sale_support_group_member
			   where member_name = '{$send_by}'
			";
			$fields_value = $ref = array();
			$rowset = $db -> getAllRefby($sql,array("group_id"),$fields_value,$ref);
			if(!empty($fields_value['group_id']) && is_array($fields_value['group_id'])){
				$conf = " and group_id not".db_create_in($fields_value['group_id'], '');
			}
		}
		
		$sql = "
		    select distinct user_id
			from ecshop.sale_support_group_member
			where ".db_create_in($recieve_group, 'group_id').$conf;
		$recieve_group_member = $db -> getAll($sql);
		$fields_value = $ref = array();
		$rowset = $db -> getAllRefby($sql, array('user_id'),$fields_value,$ref);
        if(!empty($fields_value['user_id']) && in_array($user_id, $fields_value['user_id'])){
        	return true;
        }else{
        	return false;
        }
	}
	return false;
}

//根据订单的最后一条售后沟通记录判断是否为发送者
function is_sender($order_id,$user_name = null){
	global $db;
	if($user_name == null){
		$user_name = $_SESSION['admin_name'];
	}
	//取到改订单的最后一条售后沟通记录
	$sql = "
	     select send_by
		 from ecshop.sale_support_message 
		 where order_id = '{$order_id}' and status = 'OK'
		 order by created_stamp DESC
		 limit 1
	";
	$send_by = $db -> getOne($sql);
	if($send_by == $user_name){
		return true;
	}else{
		return false;
	}
}

function order_id_get($order_sn){
	global $db;
	$sql = "select order_id from ecshop.ecs_order_info where order_sn = '{$order_sn}'";
	$result = $db -> getOne($sql);
	if(!$result){
		Sys_msg("订单搜索有误");
	}
	return $result;
}

//检测该订单的售后沟通是否完结
function check_finish($order_id){
	global $db;
	$sql = "select 1 from ecshop.sale_support_message where status = 'FINISHED' and order_id = '{$order_id}' limit 1";
	$result = $db -> getOne($sql);
	if($result){
		//已经完结
		return true;
	}else{
		//未完结
		return false;
	}
}

//对查看类型进行信息筛选
function get_support_list_by_check_type($check_type,$support_list_tmp,$admin_name){
	//售后沟通类型
	$sale_support_type_map = array(
	    1 => '错发/漏发',
		2 => '破损',
		3 => '未收到货',
		4 => '质量问题',
		5 => '7天无理由退货',
		6 => '未按约定时间/缺货',
		7 => '发票问题',
		8 => '退运费',
		9 => '其他',
	);
	
	//如果超出查看类型范围，自动默认为0
	if($check_type > 2 || $check_type <0){
		$check_type = 0;
	}
	if(empty($support_list_tmp)){
		return null;
	}
	$tmp_order_id = "";
	//-1则表示发送人，1则表示接收人
	$flag = 0;
	$support_list = array();
	switch($check_type){
		case 0:case 3:default: //全部的信息就不用任何筛选
			foreach($support_list_tmp as $support){
				$support['support_type_name'] = $sale_support_type_map[$support['support_type']];
				array_push($support_list,$support);
			}
			return $support_list;
			break;
		case 1: //待我方回复
			foreach($support_list_tmp as $support){
				//以order_id为单位进行一次查询
				if($tmp_order_id != $support['order_id']){
				    if(is_reciever($support['order_id'])){
						$flag = 1;
					}else{
						$flag = -1;
					}
				}
				//找出发给登录者的售后沟通记录
				if($flag == 1){
					//处理咨询类型
					$support['support_type_name'] = $sale_support_type_map[$support['support_type']];
					array_push($support_list,$support);
				}
				$tmp_order_id = $support['order_id'];
			}
			return $support_list;
			break;
		case 2: //待对方回复
			foreach($support_list_tmp as $support){
				//匹配发送人
				if($tmp_order_id != $support['order_id']){
					if(is_sender($support['order_id'])){
						$flag = -1;
					}else{
						$flag = 1;
					}
				}
				if($flag == -1){
					$support['support_type_name'] = $sale_support_type_map[$support['support_type']];
					array_push($support_list,$support);
				}
				$tmp_order_id = $support['order_id'];
			}
			return $support_list;
			break;
	}
}
?>