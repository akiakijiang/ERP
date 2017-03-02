<?php
/**
 * 短信发送
 */
define ( 'IN_ECS', true );

require ('includes/init.php');
require ('function.php');
require ('includes/lib_csv.php');
require_once(ROOT_PATH.'admin/includes/cls_message2.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

die("有限开放，如需使用请联系ERP。");

admin_priv('sendmessage_managebatch');

$source_type = $_REQUEST ['source_type'];
$content = stripslashes ( $_REQUEST ['content'] );
$server_name = isset($_REQUEST['server_name']) ? trim($_REQUEST['server_name']) : "yidu";

$yidu_message_serialnumber = trim(YD_MESSAGE_SERIALNUMBER);
$emay_message_serialnumber = trim(MESSAGE_SERIALNUMBER);

if($server_name=='yidu') {
	$message_serialnumber = $yidu_message_serialnumber;
} else if($server_name=='emay') {
	$message_serialnumber = $emay_message_serialnumber;
}

// 组织ID
$party_id = isset ( $_SESSION ['party_id'] ) && is_numeric ( $_SESSION ['party_id'] ) ? $_SESSION ['party_id'] : NULL;

$show_send_report = false;
$show_query_result = false;

//查询余额
$sql = "select balance from message.message_balance where serial_number = '{$message_serialnumber}' order by created_stamp desc limit 1";
$balance = $db->getOne($sql);

// 处理ajax请求
$request = isset($_REQUEST['request']) && in_array($_REQUEST['request'], array('ajax'))? $_REQUEST['request']: null;   
$act = isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('search_goods')) ? $_REQUEST['act']: null;
if ($request == 'ajax')
{
    $json = new JSON;   
    switch ($act) {
    	case 'search_goods': 
    			$sql = "
    				select goods_id,goods_name,shop_price,goods_number
    				from ecshop.ecs_goods
    				where goods_name like '%{$_POST['q']}%'
    				  and goods_party_id = {$party_id}
    			";
    			$search_goods = $db->getAll($sql);
    		print $json->encode($search_goods);
    		break;    	
    }

    exit;
}

//客户筛选
if($_REQUEST['customerquery'] ){
	$show_query_result = true;
	$begin_date = $_REQUEST['begin_date'];
	$end_date = $_REQUEST['end_date'];
	$order_amount = $_REQUEST['order_amount'];
	$goods_id = $_REQUEST['goods_id'];
	$address = $_REQUEST['address'];
	
    //生成sql
    $sql = generate_form_sql("customerquery");
            
    QLog::log("customerquery:".$sql);

    if($sql) {
    	$query_result = $slave_db->getOne($sql);
    }
    else {
       print "输入条件生成的执行语句有误"; 
       die();	
    } 
    
    $smarty->assign("begin_date", $begin_date);
    $smarty->assign("end_date", $end_date);
    $smarty->assign("order_amount", $order_amount);
    $smarty->assign("goods_name", $_REQUEST['goods_name']);
    $smarty->assign("goods_id", $goods_id);
    $smarty->assign("province", $address['province']);
    $smarty->assign("city", $address['city']);
    $smarty->assign("district", $address['district']);
      
    $smarty->assign("query_result", $query_result);
    
}
 
// 
if ($_REQUEST['sendmessage']) {
	$subject = $_REQUEST['subject'];
    $content = $_REQUEST['content'];
    $send_number = (int)$_REQUEST['send_number'];
    $send_time = $_REQUEST['send_time'];  
    
    //生成sql
    $sql = generate_form_sql("sendmessage");  
  
    $sql_msg = "insert into ecshop.ecs_msg_templates_batch
                   (party_id,server_name,template_subject,template_content,status,create_user,create_time,last_update_user,last_update_time)
                 values
                 ( {$party_id},'{$server_name}','{$subject}','{$content}','USED','{$_SESSION['admin_name']}',now(),'{$_SESSION['admin_name']}',now()) 
                   "; 
        
    QLog::log("sendmessage_msg:".$sql_msg);
    
    $msg_id = $db->exec($sql_msg);
    if(!$msg_id) {
    	print "短信内容信息生成有误";
    	die();
    }       
    
    //生成短信数据 
    $offset = 0;
    $MAXSIZE_PERWHILE = 5000;  
    $list = array();
    do 
    {
     $size_while = ($send_number - $offset)>=$MAXSIZE_PERWHILE ? $MAXSIZE_PERWHILE : ($send_number - $offset);	
     $sqlwhile = $sql." order by mobile limit {$size_while} offset {$offset} ";	
     QLog::log("sendmessage_mobile:".$sqlwhile); 
     $list = $slave_db -> getAll($sqlwhile);
     $offset += count($list);
     
     $sql_value = "";
     foreach($list as $value) {
     	$sql_value .="('{$GLOBALS['party_id']}','{$value['mobile']}','{$send_time}',now(),{$msg_id},-1,'{$_SESSION['admin_name']}',now()),";
     }
     if($sql_value != "") {
     $sql_value = rtrim($sql_value,","); 
     $sql_mobile = "insert into ecshop.ecs_msg_send_detail
            (party_id,dest_mobile,start_time,send_time,template_id,send_result,create_user,create_time) values ".$sql_value;       
     QLog::log("sendmessage_mobile:".$sql_mobile);  
      
     $mobile_id = $db->exec($sql_mobile);
     if(!$mobile_id) {
    	print "短信客户信息生成有误";
    	die();
      }          
     }
     
     unset($sql_value);	
    } while ($offset < $send_number && count($list) != 0);
    
    $smarty->assign("affected_rows", (string)$offset);
}

if ($_REQUEST['sendReport']) {
        $show_send_report = true;
    
    //查询数据库
    $begin_date = trim($_REQUEST['begin_date']);
    $end_date = trim($_REQUEST['end_date']);
    $subject = trim($_REQUEST['subject']);
    $content = trim($_REQUEST['content']);
 
    $sql = "select msd.start_time,mtb.server_name,mtb.template_subject,mtb.template_content,sum(if(send_result=-1,1,0)) as wait,sum(if(send_result=0,1,0)) as suc,sum(if(send_result not in (0,-1),1,0)) as fail
              from ecshop.ecs_msg_send_detail msd 
              inner join ecshop.ecs_msg_templates_batch mtb
                on msd.template_id = mtb.template_id
              where msd.party_id = {$party_id}  
             ";
    if($begin_date) {
        $sql .= " and msd.start_time >= '{$begin_date}'";
    }
    if($end_date) {
        $end_date_res = date("Y-m-d", strtotime($end_date) + 3600 * 24);
        $sql .= " and msd.start_time < '{$end_date_res}'";    
    }
    if($subject) {
        $sql .= " and mtb.template_subject like '%{$subject}%'";
    }
    if($content) {
        $sql .= " and mtb.template_content like '%{$content}%'";
    }
    if($server_name) {
    	$sql .= " and mtb.server_name = '{$server_name}'";  
    }
    $sql .= " group by msd.start_time,mtb.template_id";

    QLog::log("sendReport:".$sql);
    $list = $db->getAll($sql);
    
    $smarty->assign("list", $list);
  
    $smarty->assign("content", $content);
    $smarty->assign("subject", $subject); 
    $smarty->assign("begin_date", $begin_date);
    $smarty->assign("end_date", $end_date);
 
}


// 短信提供商
$server_name_list = array('yidu'=>'亿度','emay'=>'亿美');

$smarty->assign("server_name", $server_name);
$smarty->assign("server_name_list", $server_name_list);
$smarty->assign("show_send_report", $show_send_report);
$smarty->assign("show_query_result", $show_query_result);
$smarty->assign("balance", $balance);
$province_list = Helper_Array::toHashmap((array)get_regions(1, 1), 'region_id', 'region_name');
$smarty->assign('province_list', $province_list);
$smarty->display ( "oukooext/sendmessagebatch.htm" );

function generate_form_sql($type = "")
{
	$begin_date = $_REQUEST['begin_date'];
	$end_date = $_REQUEST['end_date'];
	$order_amount = $_REQUEST['order_amount'];
	$goods_id = $_REQUEST['goods_id'];
	$address = $_REQUEST['address'];
	
	$sql = false;
	if("customerquery" == $type) {
		$sql_select = " select count(distinct trim(oi.mobile)) ";
	}
	else if("sendmessage" == $type) {
		$sql_select = " select distinct trim(oi.mobile) as mobile";
	}
	else {
		$sql = false;
	}
	
    $sql_from = " from ecshop.ecs_order_info oi ";
	$sql_where = " where oi.party_id = {$GLOBALS['party_id']} and oi.mobile regexp '^ *1[0-9]{10} *$' and oi.order_type_id='SALE' ";	
    if($begin_date) {
        $sql_where .= " and oi.order_time >= '{$begin_date}' ";
        $sql = false;
    }
    if($end_date) {
        $end_date_res = date("Y-m-d", strtotime($end_date) + 3600 * 24);
        $sql_where .= " and oi.order_time < '{$end_date_res}' ";  
        $sql = false;  
    }
    if($order_amount) {
       $sql_where .= " and oi.order_amount >= {$order_amount} ";	
    }
    if($address) {
       if($address['province']) { 
       	$sql_where .= " and oi.province = {$address['province']} ";
       }
       if($address['city'])	{
       	$sql_where .= " and oi.city = {$address['city']}";       	
       }       
       if($address['district'])	{
       	$sql_where .= " and oi.district = {$address['district']} ";       	
       }      
    }
    if($goods_id) {
    	$sql_from .= "inner join ecshop.ecs_order_goods og on oi.order_id=og.order_id ";
    	$sql_where .= " and og.goods_id = {$goods_id} ";
    }
    $sql = $sql_select.$sql_from.$sql_where;
	
	return $sql;
}



