<?php
/**
 * 短信发送
 */
define ( 'IN_ECS', true );

require ('includes/init.php');
require ('function.php');
require ('includes/lib_csv.php');
require_once(ROOT_PATH.'admin/includes/cls_yidu_post_message.php');
require_once(ROOT_PATH.'admin/includes/cls_message2.php');

die("有限开放，如需使用请联系ERP。");


admin_priv('sendmessage_manage');
$source_type = $_REQUEST ['source_type'];
$content = stripslashes ( $_REQUEST ['content'] );
$server_name = isset($_REQUEST['server_name']) ? trim($_REQUEST['server_name']) : "yidu";

//初始化亿度短信客户端
$yidu_message_url = trim(YD_MESSAGE_URL);
$yidu_message_serialnumber = trim(YD_MESSAGE_SERIALNUMBER);
$yidu_message_password = trim(YD_MESSAGE_PASSWORD);
$yidu_message_sessionkey = trim(YD_MESSAGE_SESSIONKEY);
$yidu_message_client = new YiduPostMessageClient($yidu_message_url,$yidu_message_serialnumber,$yidu_message_password,$yidu_message_sessionkey);


//初始化亿度单发短信客户端
$yidu_single_message_url = trim(YD_SINGLE_MESSAGE_URL);
$yidu_single_message_serialnumber = trim(YD_SINGLE_MESSAGE_SERIALNUMBER);
$yidu_single_message_password = trim(YD_SINGLE_MESSAGE_PASSWORD);
$yidu_single_message_sessionkey = trim(YD_SINGLE_MESSAGE_SESSIONKEY);
$yidu_single_message_client = new YiduPostMessageClient($yidu_single_message_url,$yidu_single_message_serialnumber,$yidu_single_message_password,$yidu_single_message_sessionkey);


//初始化亿美短信客户端
$emay_message_url = trim(MESSAGE_URL);
$emay_message_serialnumber = trim(MESSAGE_SERIALNUMBER);
$emay_message_password = trim(MESSAGE_PASSWORD);
$emay_message_sessionkey = trim(MESSAGE_SESSIONKEY);
$emay_message_client = new Client($emay_message_url,$emay_message_serialnumber,$emay_message_password,$emay_message_sessionkey);
$emay_message_client->setOutgoingEncoding("UTF-8");

if($server_name=='yidu') {
//	$MessageClient = $yidu_message_client;
//	$message_serialnumber = $yidu_message_serialnumber;
	$MessageClient = $yidu_single_message_client;
	$message_serialnumber = $yidu_single_message_serialnumber;
} else if($server_name=='emay') {
	$MessageClient = $emay_message_client;
	$message_serialnumber = $emay_message_serialnumber;
}


// 组织ID
$party_id = isset ( $_SESSION ['party_id'] ) && is_numeric ( $_SESSION ['party_id'] ) ? $_SESSION ['party_id'] : NULL;

$show_status_report = false;

//查询余额
$sql = "select balance from message.message_balance where serial_number = '{$message_serialnumber}' order by created_stamp desc limit 1";

$balance = $db->getOne($sql);

if($_REQUEST['preview']){
    if ($source_type == 'csv') {
        $csv_str = stripslashes ( $_REQUEST ['csv_str'] );
        $csv_str = preg_replace("/\s+/i", "", $csv_str);
        if ($csv_str) {
            $target_phones = csv_parse ( $csv_str, ";" );
        }
    } elseif ($source_type == 'file') {
        if (is_uploaded_file ( $_FILES ['csvfile'] ['tmp_name'] )) {
            $csv_str = file_get_contents ( $_FILES ['csvfile'] ['tmp_name'] );
            $csv_str = iconv ( "GB18030", "UTF-8", $csv_str );
            $csv_str = preg_replace("/\s+/i", ";", $csv_str);
            $target_phones = csv_parse ( $csv_str, ";" );
        } else {
            print "no file";
            die ();
        }
    }

    if (! count ( $target_phones )) {
        die ( 'no phone' );
    }

     
    foreach ($target_phones as $target_phone){
        $size = count($target_phone);
        echo "共计".$size."条短信,下面最多显示前一百条<br />";
        echo "发送内容：".$content."<br/>";
        echo "手机号码：<br/>";
        $count = min($size,100);
        for($i=0;$i<$count;$i++){
            echo  "&nbsp;&nbsp;&nbsp;&nbsp;".$target_phone[$i]."<br />";
        }
    }
    die();
}

if ($_REQUEST['sendmessage']) {
	
    //得到用户
    $sql = "select user_id from message.message_user where user_key = '{$application_key[$party_id]}' limit 1";
    $user_id = $db->getOne($sql);
    if (is_null($user_id)) $user_id = 1;
    
    $autograph_left = strpos($content, '【');
    $autograph_right = strpos($content, '】');
    if ($autograph_left === false || $autograph_right === false || ($autograph_right + 3) !== strlen($content)) {
        print "请在短信内容中加上后缀签名";
        die();
    }
    $content = str_replace("移动", "移 动", $content);
    $content = str_replace("付款", "付 款", $content);

    //处理文件
    if ($source_type == 'csv') {
        $csv_str = stripslashes ( $_REQUEST ['csv_str'] );
		$csv_str = preg_replace("/\s+/i", "", $csv_str);
        if ($csv_str) {
            $target_phones = csv_parse ( $csv_str, ";" );
        }
    } elseif ($source_type == 'file') {
    	
        if (is_uploaded_file ( $_FILES ['csvfile'] ['tmp_name'] )) {
            $csv_str = file_get_contents ( $_FILES ['csvfile'] ['tmp_name'] );
            $csv_str = iconv ( "GB18030", "UTF-8", $csv_str );
            $csv_str = preg_replace("/\s+/i", ";", $csv_str);
            $target_phones = csv_parse ( $csv_str, ";" );
        } else {
            print "no file";
            die ();
        }
    }

    if (! count ( $target_phones )) {
        die ( 'no phone' );
    }
    $mobiles = array();
    $i = 1;
    $idx = 1;
    
    $target_phones = $target_phones[0];
    
    
    foreach ( $target_phones as $target_phone ) {
        // 检查手机号码
        $dest_mobile_array = array();
        $dest_mobile = is_array($target_phone) ? $target_phone : array($target_phone);
        foreach ($dest_mobile as $mobile) {
            if(! preg_match("/^1[0-9]{10}/", $mobile)) {
                continue;
            } 
            if (check_phonenumber($mobile)) {
                $dest_mobile_array[] = cleanup_phonenumber($mobile); //对号码进行一次处理
            }
        }
        $mobiles = array_merge($mobiles, $dest_mobile_array);
        
        if ($i % 10000 == 0 || $i == count($target_phones )) {
            //发送并记录message_history
            $send_result = $MessageClient->sendSMS($mobiles, $content);
            if($send_result<>0) $send_result = 1;
            foreach ($mobiles as $key => $mobile) {
                $sql = "insert into message.message_history
                             (result, type, send_time, dest_mobile, user_id, content, server_name) 
                        values 
                             ({$send_result}, 'BATCH', now(), '{$mobile}', {$user_id}, '{$content}', '{$server_name}')   
                ";
                $db->query($sql);
                print "send to {$mobile} "  . ($send_result == 0 ? "ok" : "fail" ) . " {$idx}<br>";
                $idx++;
            }
            $mobiles = array();
            $dest_mobile_array = array();
            
        }
        $i++;
    }
    //查询余额
    $balance = $MessageClient->getBalance();
    $sql = "insert into message.message_balance
                   (server_name, serial_number, balance, created_stamp, last_update_stamp)
            values ('{$server_name}', '{$message_serialnumber}', {$balance}, now(), now())";
    $db->query($sql);
    die ();
}

if ($_REQUEST['statusReport']) {
    $show_status_report = true;
    
    $sql = "select name from romeo.party where party_id = '{$party_id}'";
    $party_name = $db->getOne($sql);
    $sql = "select nick from ecshop.taobao_shop_conf where party_id = {$party_id}";
    $shop_name = $db->getOne($sql);
    $sql = "select user_id from message.message_user where user_key = '{$application_key[$party_id]}' limit 1";
    $user_id = $db->getOne($sql);
    if (is_null($user_id)) $user_id = 1;
    
    //查询数据库
    $begin_time = trim($_REQUEST['begin_time']);
    $end_time = trim($_REQUEST['end_time']);
    $dest_mobile = trim($_REQUEST['dest_mobile']);
    $content = trim($_REQUEST['content']);
    $is_succeed = $_REQUEST['is_succeed'];
    $page = $_REQUEST['page'];
    $page_size = $_REQUEST['page_size'] ? $_REQUEST['page_size'] : 30;
    $offset = ($page-1) * $page_size;
    $sql = "
                select h.history_id, r.report_id, h.dest_mobile, h.send_time, h.server_name, ifnull(r.receive_time, '') as receive_time, 
                if(r.report_status = '0', '成功', '') as report_status, r.status_code, h.content
                from message.message_history h 
                left join message.message_report r on h.dest_mobile = r.dest_mobile and r.receive_time >= h.send_time 
                where (h.content like '%{$party_name}%' or h.content like '%{$shop_name}%' or h.user_id = {$user_id}) ";
    if($begin_time) {
        $sql .= " and h.send_time >= '{$begin_time}'";
    }
    if($end_time) {
        $end_time = date("Y-m-d", strtotime($end_time) + 3600 * 24);
        $sql .= " and h.send_time < '{$end_time}'";    
    }
    if($dest_mobile) {
        $dest_mobile = trim($dest_mobile);
        $sql .= " and h.dest_mobile = '{$dest_mobile}'";
    }
    if($content) {
        $sql .= " and h.content like '%{$content}%'";
    }
    if($server_name) {
    	$sql .= " and h.server_name = '{$server_name}'";
    }
    $sql .= " order by h.send_time desc";
    if($is_succeed == "y") {
        $sql .= " where t.report_id is not null ";    
    } elseif ($is_succeed == "n") {
        $sql .= " where t.report_id is null ";
    }
    $query_sql = "{$sql} limit {$page_size} offset {$offset}";
    $total_sql = "select count(*) from ({$sql})t";
    $list = $db->getAll($query_sql);
    $total = $db->getOne($total_sql);
    
    //构造分页
     $url = "{$_SERVER['PHP_SELF']}?statusReport=1&server_name=emay&begin_time={$begin_time}&end_time={$end_time}"
             ."&content={$content}&is_succeed={$is_succeed}&page={$page}&page_size={$page_size}";
    $Pager = Pager($total, $page_size, $page, $url);
    
    $smarty->assign("list", $list);
    $smarty->assign("Pager", $Pager);
    $smarty->assign("content", $content);
    $smarty->assign("dest_mobile", $dest_mobile);
    $smarty->assign("begin_time", $begin_time);
    $smarty->assign("end_time", $end_time);
    $smarty->assign("is_succeed", $is_succeed);
    $smarty->assign("page_size", $page_size);
}

// 短信提供商
$server_name_list = array('yidu'=>'亿度','emay'=>'亿美');

$smarty->assign("server_name",$server_name);
$smarty->assign("server_name_list", $server_name_list);
$smarty->assign("show_status_report", $show_status_report);
$smarty->assign("balance", $balance);
$smarty->display ( "oukooext/sendmessage.dwt" );



