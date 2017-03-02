<?php
/**
ALL HAIL SINRI EDOGAWA!
我らをこころみにあわせず、悪より救いいだしたまえ。
**/
define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../function.php');
require_once (ROOT_PATH . 'includes/lib_service.php');

require_once('../includes/lib_postsale_cache.php');

/* DEBUG 在false时开放调试，在true时屏蔽调试，允许正常作业*/
$is_debug_off=true;
//是否开放组织继承 对外宣布不能，怀疑一刷总公司全公司都卡死了，但是没试过也不能一棍子打死
$is_party_contains_children=true;

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

$user_priv_list = array(
    'LCZ'=> array('priv' => 'lcz_sale_support','value'=>'售后巡查'),
    'KF' => array('priv' => 'kf_postsale_support', 'value' => '客服'),
    'FXKF' => array('priv' => 'kf_postsale_support_fenxiao', 'value' => '客服(分销)'),
    'BJWL' => array('priv' => 'bjwl_sale_support', 'value' => '北京物流'),
	'SHWL' => array('priv' => 'shwl_sale_support', 'value' => '上海物流'),
    'DGWL' => array('priv' => 'dgwl_sale_support', 'value' => '东莞物流'),
    'WBWL' => array('priv' => 'wbwl_sale_support',  'value' => '外包物流'),
    'WHWL' => array('priv' => 'whwl_sale_support',   'value' => '武汉物流'),
	'CDWL' => array('priv' => 'cdwl_sale_support',   'value' => '成都物流'),
	'JPWL' => array('priv' => 'jpwl_sale_support',   'value' => '精品物流'),
    'SZWL' => array('priv' => 'szwl_sale_support',   'value' => '苏州物流'),
    'CW' => array('priv' => 'cw_sale_support', 'value' => '财务'),
    'DZ' => array('priv' => 'dz_sale_support', 'value' => '店长'),
    'CG' => array('priv' => 'cg_sale_support', 'value' => '快递理赔客服')
);

$get_sync_taobao_refund_state_map = array(
    'SELLER_REFUSE_BUYER' => '已拒绝',
    'WAIT_SELLER_CONFIRM_GOODS' => '等待验货',
    'CLOSED' => '已关闭',
    'SUCCESS' => '已成功',
    'WAIT_SELLER_AGREE' => '等待审核',
    'WAIT_BUYER_RETURN_GOODS' => '等待退货'
);
$get_sync_taobao_fenxiao_refund_state_map = array(
    '1'=>"买家已经申请退款，等待卖家同意",
    '2'=>"卖家已经同意退款，等待买家退货",
    '3'=>"买家已经退货，等待卖家确认收货", 
    '4'=>"退款关闭",
    '5'=>"退款成功",
    '6'=>"卖家拒绝退款",
    '12'=>"同意退款，待打款",
    '9'=>"没有申请退款",
    '10'=>"卖家拒绝确认收货"
);
//wait_seller_agree ：买家申请，等待卖家同意 seller_refuse：卖家拒绝 goods_returning：退货中 closed：退款失败 success：退款成功
$get_sync_tmall_refund_state_map = array(
    'seller_refuse' => '已拒绝',
    'goods_returning' => '退货中',
    'closed' => '已关闭',
    'success' => '已成功',
    'wait_seller_agree' => '等待审核',
);
// 退款状态
$refund_status_name=array(
    'RFND_STTS_INIT'=>"已生成",
    'RFND_STTS_IN_CHECK'=>"处理中",
    'RFND_STTS_CHECK_OK'=>"已审毕",
    'RFND_STTS_EXECUTED'=>"已完成",
    'RFND_STTS_CANCELED'=>"已取消"
);

$plan_list = array(
    //'th' => '退货不退款',
    'tk' => '仅退款',
    'thtk' => '退货退款',
    'hh' => '换货',
    'zh' => '追回',
    'bj' => '录单补寄',
    'ms' => '无需处理'
);

/**
各种乱来的测试
**/
// echo "test";
// print_r(getTaobaoRefundMessages());

// $time_start = microtime_float();

// $p1=postsale_message_accelerator(0,0,"","all");

// $time_end = microtime_float();
// $time = $time_end - $time_start;
// echo "time for new is ".$time." found ".count($p1);

// echo "<hr>";

// $time_start = microtime_float();

// $p2=postsale_message_accelerator_died(0,0,"","all");

// $time_end = microtime_float();
// $time = $time_end - $time_start;
// echo "time for old is ".$time." found ".count($p2);

// echo "<hr>";

// $arraysAreEqual = ($p1 == $p2);
// if($arraysAreEqual){
//     echo "SAME";
// }else{
//     echo "NOT SMAE";
// }

function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

/**
AJAX START
**/
if("ajax"==$_REQUEST['act']){
    switch ($_REQUEST['call']) {
        case 'insert_order_msg_program':
            echo ajax_insert_order_msg_program($_REQUEST['order_id'],$_REQUEST['program']);
            break;
        case 'fast_terminate_msg':
            echo ajax_fast_terminate_msg($_REQUEST['order_id']);
            break;
        case 'append_memo_to_messages':
            echo ajax_append_memo_to_messages($_REQUEST['order_id'],$_REQUEST['msg']);
            break;
        case 'set_order_pending':
            echo ajax_set_order_pending($_REQUEST['order_id']);
            break;
        case 'cancel_order_pending':
            echo ajax_cancel_order_pending($_REQUEST['order_id']);
            break;
        case 'over_order_pending':
            echo ajax_over_order_pending($_REQUEST['order_id']);
            break;
        case 'get_msg_by_id':
            echo ajax_get_msg_by_id($_REQUEST['msg_id']);
            break;
        default:
            die('未指定AJAX');
            break;
    }
    //SINRI UPDATE CACHE
    POSTSALE_CACHE_updateMessages(null,180,$_REQUEST['order_id']);
    die();
}

function ajax_get_msg_by_id($msg_id){
    global $db;
    if(empty($msg_id)){
        return '缓存数据异常';
    }
    $sql="select message from ecshop.sale_support_message where sale_support_message_id=".$msg_id;
    return $db->getOne($sql);

}

function ajax_set_order_pending($order_id){
    $last_msg=get_sale_support_message_id($order_id);
    if($last_msg && strstr($last_msg['next_process_group'], 'WL') && $last_msg['status']!='PENDING'){
        $next_process_group = $last_msg['next_process_group'];
        $message = "进入待消费者退回货物状态";
        $send_by = $_SESSION['admin_name'];
        $now = date("Y-m-d H:i:s");
        $program=$last_msg['program'];

        global $db;

        $sql = "INSERT INTO ecshop.sale_support_message 
            (created_stamp,send_by,order_id,support_type,status,message,program,next_process_group)
            VALUES
            ('{$now}','{$send_by}','{$order_id}',9,'PENDING','{$message}','{$program}','{$next_process_group}')
        ";
        $result=$db -> query($sql);
        if ($result) {
            return $program;
        }else{
            return "进入等货状态失败";
        }
    } else return "非法设定，被拒绝。";
}

function ajax_cancel_order_pending($order_id){
    $last_msg=get_sale_support_message_id($order_id);
    if($last_msg && strstr($last_msg['next_process_group'], 'WL') && $last_msg['status']=='PENDING'){
        $next_process_group = $last_msg['next_process_group'];
        $message = "待消费者退回货物未果，取消等待状态";
        $send_by = $_SESSION['admin_name'];
        $now = date("Y-m-d H:i:s");
        $program=$last_msg['program'];

        global $db;

        $sql = "INSERT INTO ecshop.sale_support_message 
            (created_stamp,send_by,order_id,support_type,status,message,program,next_process_group)
            VALUES
            ('{$now}','{$send_by}','{$order_id}',9,'OK','{$message}','{$program}','{$next_process_group}')
        ";
        $result=$db -> query($sql);
        if ($result) {
            return $program;
        }else{
            return "取消等货状态失败";
        }
    } else return "非法设定，被拒绝。";
}

function ajax_over_order_pending($order_id){
    $last_msg=get_sale_support_message_id($order_id);
    if($last_msg && strstr($last_msg['next_process_group'], 'WL') && $last_msg['status']=='PENDING'){
        $next_process_group = $last_msg['next_process_group'];
        $message = "收到消费者退回货物，结束等待状态";
        $send_by = $_SESSION['admin_name'];
        $now = date("Y-m-d H:i:s");
        $program=$last_msg['program'];

        global $db;

        $sql = "INSERT INTO ecshop.sale_support_message 
            (created_stamp,send_by,order_id,support_type,status,message,program,next_process_group)
            VALUES
            ('{$now}','{$send_by}','{$order_id}',9,'OK','{$message}','{$program}','{$next_process_group}')
        ";
        $result=$db -> query($sql);
        if ($result) {
            return $program;
        }else{
            return "取消等货状态失败";
        }
    } else return "非法设定，被拒绝。";
}

function ajax_insert_order_msg_program($order_id,$program){
    global $plan_list;
    //$last_msg=get_sale_support_message_id($order_id);
    if(empty($order_id))die('订单号为空，died。');
    if(empty($program) && !in_array($program, $plan_list))die('非合法方案，died。');
    $next_process_group = '';
    global $db;
    $sql="select support_type from ecshop.sale_support_message where order_id = {$order_id} order by created_stamp desc limit 1";
	$support_type = $db->getOne($sql);
	$support_type = isset($support_type) ? $support_type : 9;
    
    if(strstr($program, '无需处理')){
        $message = "快速确定方案为[".$program."]";
    }else{
        $message = "快速确定方案为[".$program."]，准备进行后续操作";
    }
    $send_by = $_SESSION['admin_name'];
    $now = date("Y-m-d H:i:s");

    $sql = "INSERT INTO ecshop.sale_support_message 
        (created_stamp,send_by,order_id,support_type,status,message,program,next_process_group)
        VALUES
        ('{$now}','{$send_by}','{$order_id}',{$support_type},'FINISHED','{$message}','{$program}','{$next_process_group}')
    ";
    //$result=$db -> query($sql);

    $sql_0="START transaction;";
    $sql_2="COMMIT;";
    $sql_3="ROLLBACK";
    $db->query($sql_0);
    $r=$db->query($sql);
    if($r){
        // $sql="SELECT
        //         ssm.program
        //     FROM
        //         (
        //             SELECT
        //                 issm.order_id,
        //                 MAX(
        //                     issm.sale_support_message_id
        //                 )sale_support_message_id
        //             FROM
        //                 ecshop.sale_support_message issm
        //             GROUP BY
        //                 issm.order_id
        //         )AS t1
        //     LEFT JOIN ecshop.sale_support_message ssm ON t1.sale_support_message_id = ssm.sale_support_message_id
        //     WHERE
        //         ssm.order_id = $order_id;";
        $sql="SELECT 
                ssm.program
            FROM
                ecshop.sale_support_message ssm
            WHERE
                ssm.order_id = $order_id
            ORDER BY sale_support_message_id DESC
            LIMIT 1";
        $p=$db->getOne($sql);
        if($p==$program){
            $db->query($sql_2);
            return $program;
        }else{
            $db->query($sql_3);
            return "更新结论失败已回滚";
        }
    }else{
        $db->query($sql_3);
        return "加入结论失败";
    }
}

function ajax_fast_terminate_msg($order_id){
    global $db;
    $sql_0="START transaction;";
    $sql_2="COMMIT;";
    $sql_3="ROLLBACK";
    $last_msg=get_sale_support_message_id($order_id);

    $res="DOING";

    if($last_msg){
        $next_process_group = '';//$last_msg['next_process_group'];
        $message = "沟通完毕";
        $sql="select support_type from ecshop.sale_support_message where order_id = {$order_id} order by created_stamp desc limit 1";
		$support_type = $db->getOne($sql);
		$support_type = isset($support_type) ? $support_type : 9;
        
        $send_by = $_SESSION['admin_name'];
        $now = date("Y-m-d H:i:s");
        $program=$last_msg['program'];
        $sql_1 = "INSERT INTO ecshop.sale_support_message 
            (created_stamp,send_by,order_id,support_type,status,message,program,next_process_group)
            VALUES
            ('{$now}','{$send_by}','{$order_id}',{$support_type},'FINISHED','{$message}','{$program}','{$next_process_group}')
        ";
        $db->query($sql_0);
        $r=$db->query($sql_1);
        if($r){
            $db->query($sql_2);$res="沟通成功完结";
        }else{
            $db->query($sql_3);$res="沟通完结失败";
        }
    }
    return $res;
}

function ajax_append_memo_to_messages($order_id,$message){
    global $db;
    $sql_0="START transaction;";
    $sql_2="COMMIT;";
    $sql_3="ROLLBACK";
    $last_msg=get_sale_support_message_id($order_id);

    $res="DOING";

    if($last_msg){
        $next_process_group = $last_msg['next_process_group'];
        $message="快速备注[".$message."]";
        $send_by = $_SESSION['admin_name'];
        $now = date("Y-m-d H:i:s");
        $program=$last_msg['program'];
        $sql_1 = "INSERT INTO ecshop.sale_support_message 
            (created_stamp,send_by,order_id,support_type,status,message,program,next_process_group)
            VALUES
            ('{$now}','{$send_by}','{$order_id}',1,'OK','{$message}','{$program}','{$next_process_group}')
        ";
        $db->query($sql_0);
        $r=$db->query($sql_1);
        if($r){
            $db->query($sql_2);$res="成功添加备注[$message]";
        }else{
            $db->query($sql_3);$res="添加备注[$message]失败";
        }
    }
    return $res;
}


/**
AJAX OVER
**/

function get_party_online_level($party_id=0){
    if($party_id==0){
        $party_id=$_SESSION['party_id'];
    }
    $trying_parties_1=array(
        '保乐力加'=>65551,
        '百事'=>65608,
        '黄色小鸭'=>65579,
        '贝亲'=>65539,
        '总公司'=>65535,
        '电商服务'=>32640,
    );
    $trying_parties_2=array(
        '金宝贝'=>65574,
        'libbey'=>65603,
        '安满'=>65569,
        '安怡'=>65581,
        '荷乐'=>65601,
        '金奇仕'=>65547,
        '皇上皇'=>65593,
        '康贝'=>65586,
        //'雀巢'=>65553,
    );
    if(in_array($party_id, $trying_parties_1)){
        return 1;
    }else if(in_array($party_id, $trying_parties_2)){
        return 2;
    }else return 3;
}

/**
A sql condition for service table named s
Hide the historic services before April 2014
**/
function it_is_the_glory_of_God_to_conceal_a_thing(){
    global $is_debug_off;
    //return "";
    $level=get_party_online_level();
    if($is_debug_off || !isDevPrivUser($_SESSION['admin_name'])){
        if($level==1)
            return " AND s.apply_datetime>='2014-05-22' ";
        else if($level==2)
            return " AND s.apply_datetime>='2014-05-27' ";
        else if($level==3)
            return " AND s.apply_datetime>='2014-06-04' ";
    }else{
        return " AND s.apply_datetime>='2014-04-01' ";
    }
}
/**
As above, for refund table named r
Proverb 25:2
**/
function but_the_honour_of_kings_is_to_search_out_a_matter(){
    global $is_debug_off;
    //return "";
    $level=get_party_online_level();
    if($is_debug_off || !isDevPrivUser($_SESSION['admin_name'])){
        if($level==1)
            return " AND r.CREATED_STAMP>='2014-05-22' ";
        else if($level==2)
            return " AND r.CREATED_STAMP>='2014-05-27' ";
        else if($level==3)
            return " AND r.CREATED_STAMP>='2014-06-04' ";
    }else{
        return " AND r.CREATED_STAMP>='2014-04-01' ";
    }
}

function just_this_week($fn){
    global $is_debug_off;
    //return "";
    $level=get_party_online_level();
    if($is_debug_off || !isDevPrivUser($_SESSION['admin_name'])){
        if($level==1)
            return " AND DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= (".$fn.") 
                AND $fn>='2014-05-22' ";
        else if($level==2)
            return " AND DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= (".$fn.") 
                AND $fn>='2014-05-27' ";
        else if($level==3)
            return " AND DATE_SUB(CURDATE(), INTERVAL 30 DAY) <= (".$fn.") 
                AND $fn>='2014-06-04' ";
    } else{
        return " AND DATE_SUB(CURDATE(), INTERVAL 120 DAY) <= (".$fn.") 
            AND $fn>='2014-03-01' ";
    }
}

function hide_message_long_age(){
    global $is_debug_off;
    //return "";
    $level=get_party_online_level();
    if($is_debug_off || !isDevPrivUser($_SESSION['admin_name'])){
        if($level==1)
            return " AND ssm.created_stamp>='2014-05-22' ";
        else if($level==2)
            return " AND ssm.created_stamp>='2014-05-27' ";
        else if($level==3)
            return " AND ssm.created_stamp>='2014-06-04' ";
    }else{
        return " AND ssm.created_stamp>='2014-04-01' ";
    }
}

/*
像新百伦这种万恶的业务组织外包得连回访都不做就退款，丧心病狂，搞得系统流程乱七八糟只能写硬代码刷掉，去死吧
*/
function is_require_service_call_party($party_id){
    $list = array(
        '新百伦' => '65585', 
    );
    if(in_array($party_id, $list)){
        return '0';
    } else{
        return '1';
    }
}

/*
苏州乐贝这种奇葩的业务组织有
康贝的distributor_id=1201,main_d_id=1099
金佰利的346-270;1177-1253
在系统里登记着的是分销，但其实应该在售后里视作直销
*/
function nise_bunke_distributor_ids(){
    return " (1201,346,1177) ";
}
function nise_bunke_main_distributor_ids(){
    return " (1099,270,1253) ";
}

/**
GROUPED from here
**/

function get_undone_taobao_refund($party_id){
    global $db;
    $sql="SELECT
            DISTINCT str.tid
        FROM
            ecshop.sync_taobao_refund str
        WHERE
            str. STATUS != 'SELLER_REFUSE_BUYER'
        AND str. STATUS != 'CLOSED'
        AND str. STATUS != 'SUCCESS'
        AND str.party_id = '$party_id'
        ".just_this_week('str.created')."
    ;";
    $r=$db->getCol($sql);
    return $r;
}

function get_undone_order_message($party_id){
    global $db;
    $sql="SELECT
            DISTINCT ssm.order_id
        FROM
            ecshop.sale_support_message ssm
        LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id = o.order_id
        WHERE
            (
                ssm.next_process_group != '' || ssm.program = ''
            )
        AND o.party_id = '$party_id'
        GROUP BY
            ssm.order_id
        HAVING
            max(ssm.created_stamp);
    ";
    $r=$db->getCol($sql);
    return $r;
}

/*
<option value="0">不使用预设模式</option>
<option value="1">客服：待审核的换货申请</option>
<option value="2">客服：待审核的退货申请</option>
<option value="3">客服：验货入库待确认退款</option>
<option value="4">客服：验货入库待确认换货</option>
<option value="5">财务：退款申请待审核</option>
<option value="6">物流：已审核待退货</option>
<option value="7">物流：货已收到待验货</option>
<option value="8">财务：退款信息已确认待退款</option>
<option value="9">客服：申请被拒待回访</option>
<option value="10">客服：处理来自淘宝的未审核退款申请</option>
<option value="11">客服：处理来自淘宝的已同意退款申请</option>
<option value="12">客服：处理售后沟通</option>
<option value="13">店长：处理售后沟通</option>
<option value="14">财务：处理售后沟通</option>
<option value="15">物流：处理售后沟通</option>
<option value="16">采购：处理售后沟通</option>
*/

function get_sync_taobao_refund_mode_condition($mode){
    $con="";
    switch ($mode) {
        case '10':
        case '17':
            $con.=" AND str.status ='WAIT_SELLER_AGREE' ";
            break;
        case '11':
        case '18':
            $con.=" AND str.status in ('WAIT_BUYER_RETURN_GOODS','WAIT_SELLER_CONFIRM_GOODS') ";
            break;
        default:
            $con.=" AND 0 ";
            break;
    }
    return $con;
}

function get_conditions_message_mode($mode){
    $con="";
    switch ($mode) {
        case '12':
            $con.=" AND ssm.next_process_group = 'KF' ";
            break;
        case '13':
            $con.=" AND ssm.next_process_group = 'DZ' ";
            break;
        case '14':
            $con.=" AND ssm.next_process_group = 'CW' ";
            break;
        case '15':
            $con.=" AND ssm.next_process_group like '%WL' ";
            break;
        case '16':
            $con.=" AND ssm.next_process_group = 'CG' ";
            break;
        case '21':
            $con.=" AND (
                (ssm.next_process_group is null or ssm.next_process_group='')
                AND (ssm.program is null or ssm.program='')
            ) ";
            break;
        default:
            $con.=" AND 0 ";
            break;
    }
    return $con;
}

function FGTaobaoRefundSync($party_id){
    global $db;
    $sql="SELECT
                count(1) 
            FROM
                ecshop.sync_taobao_refund str
            WHERE
                (
                    str.status!='SELLER_REFUSE_BUYER' 
                    AND str.status!='CLOSED'
                    AND str.status!='SUCCESS'  
                ) ".($party_id!=0?" AND str.party_id='$party_id' ":" ").
                just_this_week('str.created').";";
    $r=$db->getOne($sql);
    if($r)
        return $r;
    else
        return 0;
}

function FGOrderPostsaleMessage($party_id, $role=''){
    if($role!='')$con_for_role=" AND ssm.next_process_group='$role' ";
    else $con_for_role="";
    global $db;
    $sql_seek_last_ones="SELECT
            count(DISTINCT ssm.order_id)
        FROM
           ecshop.sale_support_message ssm
        LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id = o.order_id
        LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        WHERE
            o.party_id = $party_id
            and ssm.sale_support_message_id IN(
                SELECT
                    MAX(
                        issm.sale_support_message_id
                    )
                FROM
                    ecshop.sale_support_message issm
                WHERE
                    issm.order_id = ssm.order_id
            ) 
            AND (
                (ssm.next_process_group is not null AND ssm.next_process_group != '')
                OR (ssm.program is null OR ssm.program = '')
            )
            ".hide_message_long_age().
            $con_for_role."
            ;";
    $result=$db->getOne($sql_seek_last_ones);
    if($result) return $result; else return 0;
}

/*
用于售后处理中心的统计
此函数已经被废除, RELATED FILE AS WELL
*/
function future_gazer($party_id){
    return array(
        'SYNC'=>FGTaobaoRefundSync($party_id),
        'msg'=>array(
            '售后沟通统计'=>FGOrderPostsaleMessage($party_id),
            '客服'=>FGOrderPostsaleMessage($party_id,'KF'),
            '北京物流'=>FGOrderPostsaleMessage($party_id,'BJWL'),
            '上海物流'=>FGOrderPostsaleMessage($party_id,'SHWL'),
            '东莞物流'=>FGOrderPostsaleMessage($party_id,'DGWL'),
            '外包物流'=>FGOrderPostsaleMessage($party_id,'WBWL'),
            '财务'=>FGOrderPostsaleMessage($party_id,'CW'),
            '店长'=>FGOrderPostsaleMessage($party_id,'DZ'),
            '采购'=>FGOrderPostsaleMessage($party_id,'CG'),
        ),
    );
}

/*
$the_plus_condition['party_id']

$the_plus_condition['order_sn']
$the_plus_condition['order_id']//NO USE
$the_plus_condition['taobao_order_sn']
$the_plus_condition['taobao_refund_id']
$the_plus_condition['taobao_buyer_nick']
$the_plus_condition['buyer_name']
$the_plus_condition['mobile']
$the_plus_condition['track_number']
$the_plus_condition['return_track_number']

$the_plus_condition['date_start']
$the_plus_condition['date_end']
$the_plus_condition['OFFSET']
*/
/*
先不分角色，将未完成的全部获取然后在php里面区分过滤
*/
function only_my_railgun($the_plus_condition,$OFFSET=0,$use_role){
    //extract($the_plus_condition);
    global $db;
    global $user_priv_list;
    global $sale_support_type_map;
    global $get_sync_taobao_refund_state_map;
    global $get_sync_tmall_refund_state_map;
    global $get_sync_taobao_fenxiao_refund_state_map;
    global $refund_status_name;

    $str_user_facilities=$_SESSION['facility_id'];
    $user_facilities=explode(',', $_SESSION['facility_id']);
    //print_r($user_facilities);
    //print_r($the_plus_condition);
    //SYNC
    $conditions_tr=($the_plus_condition['party_id']!=0?" AND str.party_id='".$the_plus_condition['party_id']."' ":" ");
    if($the_plus_condition['mode']!=0){
        $conditions_tr.=get_sync_taobao_refund_mode_condition($the_plus_condition['mode']);
    }
    if($use_role!='viewer' && $use_role!='postsale' && $use_role!='finance'){
        $conditions_tr.=" AND 0 ";
    }
    foreach ($the_plus_condition as $key => $value) {
        switch ($key) {
            case 'order_sn':
                //do filter later
                break;
            case 'taobao_order_sn':
                $conditions_tr.=" AND POSITION('$value' IN str.tid) ";
                break;
            case 'taobao_refund_id':
                $conditions_tr.=" AND str.refund_id = '$value' ";
                break;
            case 'taobao_buyer_nick':
                $conditions_tr.=" AND str.buyer_nick = '$value' ";
                break;
            case 'buyer_name':
                //do filter later
                break;
            case 'mobile':
                //do filter later
                break;
            case 'track_number':
                //do filter later
                break;
            case 'return_track_number':
                //$conditions_tr.=" AND str.sid = '$value' ";
                break;
            case 'date_start':
                $conditions_tr.=" AND str.created>='$value' ";
                break;
            case 'date_end':
                $conditions_tr.=" AND str.created<='$value' ";
                break;
            /*
            case 'is_soon_or_pending':
                if($value=='is_pending'){
                    $conditions_tr.= " AND 0 ";
                }
                break;
            */
        }
    }
    $sync_taobao_refunds=get_sync_taobao_refund_waiting_lines(
        $the_plus_condition['party_id'],
        $the_plus_condition['OFFSET'],
        $the_plus_condition['dist'],
        $conditions_tr
    );
    //TMALL
    $sync_tmall_refunds=get_sync_tmall_refund_waiting_lines(
        $the_plus_condition['party_id'],
        $the_plus_condition['OFFSET'],
        $the_plus_condition['dist'],
        $conditions_tr
    );

    //MSG
    $conditions_tr="";//($the_plus_condition['party_id']!=0?" AND o.party_id='".$the_plus_condition['party_id']."' ":" ");
    //if($use_role=='logistics')$conditions_tr.=" AND (o.facility_id in ($str_user_facilities)) ";
    if($the_plus_condition['mode']!=0){
        $conditions_tr=get_conditions_message_mode($the_plus_condition['mode']);
    }
    foreach ($the_plus_condition as $key => $value) {
        switch ($key) {
            case 'taobao_order_sn':
                $conditions_tr.=" AND POSITION('$value' IN o.taobao_order_sn) ";
                break;
            case 'order_sn':
                $conditions_tr.=" AND o.order_sn = '$value' ";
                break;
            case 'order_id':
                $conditions_tr.=" AND o.order_id = '$value' ";
                break;
            case 'buyer_name':
                $conditions_tr.=" AND o.consignee = '$value' ";
                break;
            case 'mobile':
                $conditions_tr.=" AND ( o.mobile = '$value' OR o.tel = '$value' ) ";
                break;
            case 'date_start':
                $conditions_tr.=" AND ssm.created_stamp>='$value' ";
                break;
            case 'date_end':
                $conditions_tr.=" AND ssm.created_stamp<='$value' ";
                break;
            case 'track_number':
            case 'return_track_number':
                $conditions_tr.=" AND  0 ";
                break;
        }
    }
    $duo_message_array=postsale_message_accelerator(//show_the_sale_support_message_lines(
        $the_plus_condition['party_id'],
        $the_plus_condition['OFFSET'],
        $conditions_tr,
        $the_plus_condition['dist']
    );
    //SERVICE
    $conditions_tr=($the_plus_condition['party_id']!=0?" AND s.party_id='".$the_plus_condition['party_id']."' ":" ");
    if($use_role=='logistics')$conditions_tr.=" AND (o.facility_id in ($str_user_facilities) OR s.facility_id in ($str_user_facilities) ) ";
    if($the_plus_condition['mode']!=0){
        $conditions_tr.=get_conditions_for_mode($the_plus_condition['mode']);
    }
    foreach ($the_plus_condition as $key => $value) {
        switch ($key) {
            case 'taobao_order_sn':
                $conditions_tr.=" AND POSITION('$value' IN o.taobao_order_sn) ";
                break;
            case 'order_sn':
                $conditions_tr.=" AND o.order_sn = '$value' ";
                break;
            case 'order_id':
                $conditions_tr.=" AND o.order_id = '$value' ";
                break;
            case 'buyer_name':
                $conditions_tr.=" AND o.consignee = '$value' ";
                break;
            case 'mobile':
                $conditions_tr.=" AND ( o.mobile = '$value' OR o.tel = '$value' ) ";
                break;
            case 'track_number':
                $conditions_tr.=" AND ( rs.tracking_number = '$value' ) ";
                break;
            case 'date_start':
                $conditions_tr.=" AND s.apply_datetime>='$value' ";
                break;
            case 'date_end':
                $conditions_tr.=" AND s.apply_datetime<='$value' ";
                break;
            case 'return_track_number':
                $conditions_tr.=" AND  0 ";
                break;
            /*
            case 'is_soon_or_pending':
                if($value=='is_pending'){
                    $conditions_tr.= " AND 0 ";
                }
                break;
            */
        }
    }
    /**
    物流部门不需要看到上海伊藤忠[76161272]的仓库的退换货申请
    **/
    if($use_role=='logistics'){
       $conditions_tr.=" AND (s.facility_id not in (76161272)) "; 
    }
    


    $services=seek_uncompleted_services_for_role(
        $use_role,
        $the_plus_condition['OFFSET'],
        $conditions_tr, 
        $the_plus_condition['dist']
    );
    //REFUND
    $conditions_tr=($the_plus_condition['party_id']!=0?" AND r.party_id='".$the_plus_condition['party_id']."' ":" ");
    if($use_role=='logistics')$conditions_tr.=" AND (o.facility_id in ($str_user_facilities)) ";
    if($the_plus_condition['mode']!=0){
        $conditions_tr.=get_uncompleted_refunds_mode_condition($the_plus_condition['mode']);
    }
    foreach ($the_plus_condition as $key => $value) {
        switch ($key) {
            case 'taobao_order_sn':
                $conditions_tr.=" AND POSITION('$value' IN o.taobao_order_sn) ";
                break;
            case 'order_sn':
                $conditions_tr.=" AND o.order_sn = '$value' ";
                break;
            case 'order_id':
                $conditions_tr.=" AND o.order_id = '$value' ";
                break;
            case 'buyer_name':
                $conditions_tr.=" AND o.consignee = '$value' ";
                break;
            case 'mobile':
                $conditions_tr.=" AND ( o.mobile = '$value' OR o.tel = '$value' ) ";
                break;
            case 'date_start':
                $conditions_tr.=" AND r.CREATED_STAMP>='$value' ";
                break;
            case 'date_end':
                $conditions_tr.=" AND r.CREATED_STAMP<='$value' ";
                break;
            case 'track_number':
            case 'return_track_number':
                $conditions_tr.=" AND  0";
                break;
            /*
            case 'is_soon_or_pending':
                if($value=='is_pending'){
                    $conditions_tr.= " AND 0 ";
                }
                break;
            */
        }
    }
    $refunds=get_uncompleted_refunds(
        $conditions_tr,
        $the_plus_condition['party_id'],//其实已经没用了
        $the_plus_condition['OFFSET'],
        $the_plus_condition['dist']
    );

    foreach ($sync_taobao_refunds['zhixiao'] as $key => $value) {
        $sync_taobao_refunds['zhixiao'][$key]['TAOBAO_TASK_TYPE']='taobao';
    }
    foreach ($sync_taobao_refunds['fenxiao'] as $key => $value) {
        $sync_taobao_refunds['fenxiao'][$key]['TAOBAO_TASK_TYPE']='taobao_fenxiao';
    }
    foreach ($sync_tmall_refunds as $key => $value) {
        $sync_tmall_refunds[$key]['TAOBAO_TASK_TYPE']='tmall';
    }

    $taobao_tasks=array_merge($sync_taobao_refunds['zhixiao'],$sync_taobao_refunds['fenxiao'], $sync_tmall_refunds);

    $ori = array(
        'taobao_refunds' => $taobao_tasks,
        //'taobao_refunds' => $sync_taobao_refunds['zhixiao'] ,
        /**
        ATTENTION HERE!
        等分销退款同步进来以后就要把两个array给merge了。
        **/
        //'tmall_refunds' => $sync_tmall_refunds,

        'duo_message_arrays' => $duo_message_array,
        'services' => $services,
        'refunds' => $refunds
    );
    
    echo "<!-- ORI \n";
    print_r($ori);
    echo "\n -->";
    
    $the_group=array();
    $the_group['NONE']=array('taobao_refunds'=>array());
    //get tid from taobao
    foreach ($ori['taobao_refunds'] as $key => $line) {
        $linked_orders=get_orders_by_taobao_order_sn($line['tid']);
        //Special Filter
        if(
            $the_plus_condition['order_sn'] ||
            $the_plus_condition['buyer_name'] ||
            $the_plus_condition['mobile'] ||
            $the_plus_condition['track_number']
        ){
            $left=false;
            foreach ($linked_orders as $no => $order) {
                if($the_plus_condition['order_sn']){
                    if($order['order_sn']==$the_plus_condition['order_sn']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['buyer_name']){
                    if($order['consignee']==$the_plus_condition['buyer_name']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['mobile']){
                    if($order['mobile']==$the_plus_condition['mobile'] ||
                        $order['tel']==$the_plus_condition['mobile']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['track_number']){
                    /*
                    global $db;
                    $sql="SELECT bill_no FROM ecshop.ecs_carrier_bill WHERE bill_id=".$order['carrier_bill_id']." LIMIT 1;";
                    $bill_no=$db->getOne($sql);
                    if(!empty($bill_no) && $bill_no==$the_plus_condition['track_number']){
                        $left=true;
                        break;
                    }
                    */
                    $tns=getTrackingNumbersForOrder($order['order_id']);
                    if(!empty($tns) && in_array($the_plus_condition['track_number'], $tns)){
                        $left=true;
                        break;
                    }
                }

            }
            if(!$left) continue;
        }
        $the_group[$line['tid']]['taobao_refunds'][$line['refund_id']]=$line;
        
        //这货移到外面去
        if(true || $line['status']=='WAIT_SELLER_AGREE'){
            foreach ($linked_orders as $no => $order) {
                $the_group[$line['tid']]['orders'][$order['order_id']]['order_info']=$order;
                $the_group[$line['tid']]['orders'][$order['order_id']]['order_sn']=$order['order_sn'];
            }
        }
        
    }
/*
    //get tid from tmall
    foreach ($ori['tmall_refunds'] as $key => $line) {
        $linked_orders=get_orders_by_taobao_order_sn($line['tid']);
        //Special Filter
        if(
            $the_plus_condition['order_sn'] ||
            $the_plus_condition['buyer_name'] ||
            $the_plus_condition['mobile'] ||
            $the_plus_condition['track_number']
        ){
            $left=false;
            foreach ($linked_orders as $no => $order) {
                if($the_plus_condition['order_sn']){
                    if($order['order_sn']==$the_plus_condition['order_sn']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['buyer_name']){
                    if($order['consignee']==$the_plus_condition['buyer_name']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['mobile']){
                    if($order['mobile']==$the_plus_condition['mobile'] ||
                        $order['tel']==$the_plus_condition['mobile']){
                        $left=true;
                        break;
                    }
                }
                if($the_plus_condition['track_number']){
                    $tns=getTrackingNumbersForOrder($order['order_id']);
                    if(!empty($tns) && in_array($the_plus_condition['track_number'], $tns)){
                        $left=true;
                        break;
                    }
                    
                }

            }
            if(!$left) continue;
        }
        $the_group[$line['tid']]['tmall_refunds'][$line['refund_id']]=$line;
       
        //这货移到外面去
        if(true){
            foreach ($linked_orders as $no => $order) {
                $the_group[$line['tid']]['orders'][$order['order_id']]['order_info']=$order;
                $the_group[$line['tid']]['orders'][$order['order_id']]['order_sn']=$order['order_sn'];
            }
        }
        
    }
*/ 
    //"discussing" "wait_to_do"
    foreach ($ori['duo_message_arrays']['discussing'] as $key => $line) {
        if(empty($line['taobao_order_sn'])){
            $orline=get_order_relation_path($key);
            if($orline){
                $root_order_id=$orline['root_order_id'];
            }else{
                $root_order_id='NONE';
            }
            $the_group["*$root_order_id"]['orders'][$key]['msg']=array(
                'type'=>'discussing',
                'line'=>$line
            );
            $the_group["*$root_order_id"]['orders'][$key]['order_sn']=$line['OSN'];
        } else {
            $the_group[$line['taobao_order_sn']]['orders'][$key]['msg']=array(
                'type'=>'discussing',
                'line'=>$line
            );
            $the_group[$line['taobao_order_sn']]['orders'][$key]['order_sn']=$line['OSN'];
        }
        
    }
    foreach ($ori['duo_message_arrays']['wait_to_do'] as $key => $line) {
        if(empty($line['taobao_order_sn'])){
            $orline=get_order_relation_path($key);
            if($orline){
                $root_order_id=$orline['root_order_id'];
            }else{
                $root_order_id='NONE';
            }
            $the_group["*$root_order_id"]['orders'][$key]['msg']=array(
                'type'=>'wait_to_do',
                'line'=>$line
            );
            $the_group["*$root_order_id"]['orders'][$key]['order_sn']=$line['OSN'];
        }else{
            $the_group[$line['taobao_order_sn']]['orders'][$key]['msg']=array(
                'type'=>'wait_to_do',
                'line'=>$line
            );
            $the_group[$line['taobao_order_sn']]['orders'][$key]['order_sn']=$line['OSN'];
        }
    }
    //service
    foreach ($ori['services'] as $key => $line) {
        if(empty($line['taobao_order_sn'])){
            $orline=get_order_relation_path($line['order_id']);
            if($orline){
                $root_order_id=$orline['root_order_id'];
            }else{
                $root_order_id='NONE';
            }
            $the_group["*$root_order_id"]['orders'][$line['order_id']]['services'][$line['service_id']]=$line;
            $the_group["*$root_order_id"]['orders'][$line['order_id']]['order_sn']=$line['order_sn'];
        } else {
            $the_group[$line['taobao_order_sn']]['orders'][$line['order_id']]['services'][$line['service_id']]=$line;
            $the_group[$line['taobao_order_sn']]['orders'][$line['order_id']]['order_sn']=$line['order_sn'];
        }
    }
    //refund
    foreach ($ori['refunds'] as $key => $line) {
        if(empty($line['taobao_order_sn'])){
            $orline=get_order_relation_path($line['order_id']);
            if($orline){
                $root_order_id=$orline['root_order_id'];
            }else{
                $root_order_id='NONE';
            }
            $the_group["*$root_order_id"]['orders'][$line['order_id']]['refunds'][$line['REFUND_ID']]=$line;
            $the_group["*$root_order_id"]['orders'][$line['order_id']]['order_sn']=$line['order_sn'];
        }else{
            $the_group[$line['taobao_order_sn']]['orders'][$line['order_id']]['refunds'][$line['REFUND_ID']]=$line;
            $the_group[$line['taobao_order_sn']]['orders'][$line['order_id']]['order_sn']=$line['order_sn'];
        }
    }

    foreach ($the_group as $_tid => $_taobao) {
        if($_taobao['orders'] && is_array($_taobao['orders'])){
            foreach ($_taobao['orders'] as $_order_id => $_order) {
                $the_group[$_tid]['orders'][$_order_id]['order_info']=get_order_by_order_id($_order_id);
            }
        }
    }
    
    
    echo "<!-- GROUP \n";
    print_r($the_group);
    echo "\n -->";
    
    $memo="";
    $read_group=array();
    $time_now=time();
    foreach ($the_group as $taobao_order_sn => $group1) {
        $read_group[$taobao_order_sn]['taobao_refunds']=array();
        if($group1['taobao_refunds'] && is_array($group1['taobao_refunds']) && ($use_role=='viewer' || $use_role=='postsale' || $use_role=='finance')){
            foreach ($group1['taobao_refunds'] as $taobao_refund_id => $line) {
                
                $time_c=strtotime($line['created']);
                $time_dif_day=round(($time_now-$time_c)/(3600*24));
                $memo="";
                
                $memo="<!--\n";
                foreach ($line as $key => $value) {
                    $memo.="$key=$value\n";
                }
                $memo.="-->";

                $whodowhat='-';
                $return_shipping_info="";

                $type_label="未知";
                $status_label="喵";
                $return_fee_label="正体不明";
                
                if($line['TAOBAO_TASK_TYPE']=='taobao'){
                    if($line['status']=='WAIT_SELLER_AGREE'){
                        $whodowhat="待客服审查申请";
                    }else if($line['status']=='WAIT_BUYER_RETURN_GOODS'){
                        $whodowhat="物流坐等货物退回";
                    }else if($line['status']=='WAIT_SELLER_CONFIRM_GOODS'){
                        $whodowhat="待客服建立申请<br>待财务处理退款";
                        $return_shipping_info.=$line['company_name'].":".$line['sid'].($line['reason']?'<br>':'');
                    }
                    $type_label="淘宝直销";
                    $status_label=($line['status']=='WAIT_SELLER_AGREE'?"<span class='keikoku'>":'<span>').
                        $get_sync_taobao_refund_state_map[$line['status']].
                        "</span>";
                    $return_fee_label=$line['refund_fee'];
                }else if($line['TAOBAO_TASK_TYPE']=='tmall'){
                    if($line['status']=='wait_seller_agree'){
                        $whodowhat="待客服审查申请";
                    }else if($line['status']=='goods_returning'){
                        $whodowhat="物流坐等货物退回";
                    }
                    $type_label="天猫";
                    $status_label=($line['status']=='wait_seller_agree'?"<span class='keikoku'>":'<span>').
                        $get_sync_tmall_refund_state_map[$line['status']].
                        "</span>";
                    $return_fee_label=($line['refund_fee']/100.0);
                }else if ($line['TAOBAO_TASK_TYPE']=='taobao_fenxiao'){
                    if(in_array($line['status'],array(1)) ){
                        $whodowhat="待客服审查申请";
                    }else if(in_array($line['status'],array(2,3)) ){
                        $whodowhat="物流坐等货物退回";
                    }else if(in_array($line['status'],array(12)) ){
                        $whodowhat="待财务退款";
                    }else if(in_array($line['status'],array(9)) ){
                        $whodowhat="客服核实是否已申请退款";
                    }else if(in_array($line['status'],array(10)) ){
                        $whodowhat="客服处理卖家拒收善后";
                    }
                    $status_label=$get_sync_taobao_fenxiao_refund_state_map[$line['status']];
                    $type_label="淘宝分销";
                    $return_fee_label=$line['refund_fee'];
                }
                $read_group[$taobao_order_sn]['taobao_refunds'][$taobao_refund_id]=array(
                    '类型'=>$type_label,
                    '状态'=>$status_label,
                    '金额'=>$return_fee_label,
                    '顾客'=>$line['buyer_nick'],
                    '原因'=>$line['reason'],
                    '时间'=>'同步于'.$line['modified']."<br><span".
                        ($time_dif_day>5?" class='keikoku'>":">").
                        "发起于".($time_dif_day>0?$time_dif_day."天前":"今天").
                        "</span><!--".$line['created']."-->",
                    '待办'=>$whodowhat,
                    '备注'=>$return_shipping_info.$memo,
                );
            }
        } 
/*
        //TAMLL
        $read_group[$taobao_order_sn]['tmall_refunds']=array();
        if($group1['tmall_refunds'] && is_array($group1['taobao_refunds']) && ($use_role=='viewer' || $use_role=='postsale' || $use_role=='finance')){
            foreach ($group1['tmall_refunds'] as $taobao_refund_id => $line) {
                
                $time_c=strtotime($line['created']);
                $time_dif_day=round(($time_now-$time_c)/(3600*24));
                $memo="";
                
                $memo="<!--\n";
                foreach ($line as $key => $value) {
                    $memo.="$key=$value\n";
                }
                $memo.="-->";

                $whodowhat='-';
                $return_shipping_info="";
                if($line['status']=='wait_seller_agree'){
                    $whodowhat="待客服审查申请";
                }else if($line['status']=='goods_returning'){
                    $whodowhat="物流坐等货物退回";
                }
                $read_group[$taobao_order_sn]['tmall_refunds'][$taobao_refund_id]=array(
                    '类型'=>'天猫退款',
                    '状态'=>($line['status']=='wait_seller_agree'?"<span class='keikoku'>":'<span>').
                        $get_sync_taobao_refund_state_map[$line['status']].
                        "</span>",
                    '金额'=>$line['refund_fee'],
                    '顾客'=>$line['buyer_nick'],
                    '原因'=>$line['reason'],
                    '时间'=>'同步于'.$line['last_update_timestamp']."<br><span".
                        ($time_dif_day>5?" class='keikoku'>":">").
                        "发起于".($time_dif_day>0?$time_dif_day."天前":"今天").
                        "</span><!--".$line['created']."-->",
                    '待办'=>$whodowhat,
                    '备注'=>$return_shipping_info.$memo,
                );
            }
        } 
*/
        $need_pending = false;
		if($the_plus_condition['is_soon_or_pending']=='is_pending'){
        	//IS SEE PENDING $wannapending=true; 
        	if(
                //$use_role=='viewer' ||
                $use_role=='logistics'
             ) {
        		$need_pending = true;
        	}
        }
        
        $read_group[$taobao_order_sn]['orders']=array();
        if($group1['orders'] && is_array($group1['orders'])){
            foreach ($group1['orders'] as $order_id => $group2) {
                //ORDER_SN
                $read_group[$taobao_order_sn]['orders'][$order_id]['order_sn']=$group2['order_sn'];
                //PENDING
                $read_group[$taobao_order_sn]['orders'][$order_id]['pending']='not_pending';
                //MSG
                $read_group[$taobao_order_sn]['orders'][$order_id]['msg']=array();
                
                if($use_role!='logistics' || ($use_role=='logistics' && !$need_pending)) {
                	if($group2['msg'] && is_array($group2['msg'])){
	                    $time_c=strtotime($group2['msg']['line']['created_stamp']);
	                    $time_dif_day=round(($time_now-$time_c)/(3600*24));
	                    $memo="";
	                    
	                    $memo="<!--\n";
	                    foreach ($group2['msg']['line'] as $key => $value) {
	                        $memo.="$key=$value\n";
	                    }
	                    $memo.="-->";
	
	                    if($group2['msg']['line']['STATUS']=='PENDING'){
	                       $read_group[$taobao_order_sn]['orders'][$order_id]['pending']='pending';
	                    }
	
	                    $shall_pass=false;
	                    
	                    if($use_role!='viewer'){
	                        if($the_plus_condition['is_soon_or_pending']=='is_pending'){
	                            if($group2['msg']['line']['STATUS']!='PENDING'){
	                                $shall_pass=true;
	                            }else{
	                                $shall_pass=false;
	                            }
	                        }else {//show soon
	                            if($group2['msg']['line']['STATUS']=='PENDING') $shall_pass=true;
	                            else {
	                                switch ($user_priv_list[$group2['msg']['line']['next_process_group']]['value']) {
	                                    case '客服':
                                         case '客服(分销)':
	                                        if($use_role!="postsale")$shall_pass=true;
	                                        break;
	                                    case '财务':
	                                        if($use_role!="finance")$shall_pass=true;
	                                        break;
	                                    case '店长':
	                                        if($use_role!="shop")$shall_pass=true;
	                                        break;
	                                    case '采购':
	                                        if($use_role!="cg")$shall_pass=true;
	                                        break;
	                                    case '北京物流':
                                            if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'bjwl_sale_support')){
                                                $shall_pass=true;
                                            }
                                            break;
	                                    case '上海物流':
                                            if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'shwl_sale_support')){
                                                $shall_pass=true;
                                            }
                                            break;
	                                    case '东莞物流':
	                                        if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'dgwl_sale_support')){
                                                $shall_pass=true;
                                            }
	                                        break;
                                        case '外包物流':
                                            if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'wbwl_sale_support')){
                                                $shall_pass=true;
                                            }
                                            break;
	                                    case '武汉物流':
                                            if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'whwl_sale_support')){
                                                $shall_pass=true;
                                            }
                                            break;
	                                    case '成都物流':
                                            if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'cdwl_sale_support')){
                                                $shall_pass=true;
                                            }
                                            break;
	                                    case '精品物流':
	                                        if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'jpwl_sale_support')){
                                                $shall_pass=true;
                                            }
                                            break;
                                             case '苏州物流':
                                            if($use_role!="logistics" || !check_admin_user_priv($_SESSION['admin_name'], 'szwl_sale_support')){
                                                $shall_pass=true;
                                            }
	                                        break;
	                                    default:
	                                        if(empty($group2['msg']['line']['program']) && $use_role=='postsale'){
	                                            //do not pass it, saith Sinri
	                                            //but damn it, XLH argued it as original
	                                            $shall_pass=true;
	                                        } else $shall_pass=true;
	                                        break;
	                                }
	                            }
	                        }
	                    }
	                    if(empty($group2['msg']['line']['program']) && empty($group2['msg']['line']['next_process_group'])){
	                        $highlight=true;
	                    }else{
	                        if($use_role=='viewer' && $group2['msg']['line']['STATUS']=='PENDING'){
	                            $highlight=true;
	                        }else{
	                            $highlight=false;
	                        }
	                    }
                        $zhuihui_todo="";
                        $zhuihui_is_wl='goutong';
                        if($group2['msg']['line']['program']=='追回'){
                            //print_r("<p>ZZZZ=");print_r($group2);print_r(" and use_role=".$use_role."</p>");
                            if($group2['order_info']['order_status']!=2 && $group2['order_info']['shipping_status']!=11){
                                //print_r("<p>XXXXX</p>");
                                if($use_role=="postsale")$shall_pass=false;
                                $zhuihui_todo="待客服取消应追回订单";
                            }else if($group2['order_info']['order_status']==2 && $group2['order_info']['shipping_status']!=11){
                                //print_r("<p>WWWWW</p>");
                                if($use_role=="logistics" && strpos($str_user_facilities, $group2['order_info']['facility_id'])!==false){
                                    $shall_pass=false;
                                    $zhuihui_is_wl='zhuihui';
                                }
                                $zhuihui_todo="待物流追回订单";
                            }
                        }
                        //print_r("<p>read group ".$group2['order_sn']." pass?".($shall_pass?'YES':'NO')."</p>");
	                    if(!$shall_pass){
                            $waiting_services_memo="";
                            if($group2['services'] && is_array($group2['services'])){
                                foreach ($group2['services'] as $service_id => $serv_line) {
                                    if($serv_line['back_shipping_status'] == 5 ) {
                                        $waiting_services_memo.=$service_id." ";
                                    }
                                }
                            }
                            if($waiting_services_memo!=""){
                                $waiting_services_memo="等待中的退货申请：".$waiting_services_memo;
                            }

	                        $read_group[$taobao_order_sn]['orders'][$order_id]['msg']=array(
	                            $group2['msg']['line']['sale_support_message_id']=>array(
	                                '类型'=>"沟通",
	                                '状态'=>($group2['msg']['type']=='discussing'?'未定案':'已定案<br>('.$group2['msg']['line']['program'].')').
                                        (($group2['msg']['line']['STATUS']=='PENDING')?"<br>本订单等待退货（已经停止流程许可）":"").
                                        "",
	                                '金额'=>'-',
	                                '顾客'=>$group2['msg']['line']['consignee'],
	                                '原因'=>$group2['msg']['line']['message'],
	                                '时间'=>$group2['msg']['line']['created_stamp']."<br><span".
	                                    ($time_dif_day>5?" class='keikoku'>":">").
	                                    ($time_dif_day>0?$time_dif_day."天前":"今天").
	                                    "</span>",
	                                '待办'=>(empty($group2['msg']['line']['next_process_group'])?"未明确部门":$user_priv_list[$group2['msg']['line']['next_process_group']]['value']).
                                        ($zhuihui_todo!=''?"<br>".$zhuihui_todo:""),
	                                '备注'=>$memo,
	                                'highlight'=>($highlight?'Y':'N'),
                                    'pending_service_list'=>$waiting_services_memo,
                                    'zhuihui_is_wl'=>$zhuihui_is_wl,
	                            )
	                        );
	                    }
	                }
					
					//### }
	
	                // AS MEG
	                
	                //REFUND
	                $read_group[$taobao_order_sn]['orders'][$order_id]['refunds']=array();
	                if($group2['refunds'] && is_array($group2['refunds'])){
	                    foreach ($group2['refunds'] as $refund_id => $line) {
	                        $ww=get_refund_next_responsor($line);
	                        if($use_role!='viewer'){
	                            $shall_show=false;
	                            if(strstr($ww, '客服') && $use_role=="postsale")$shall_show=$shall_show||true;
	                            if(strstr($ww, '财务') && $use_role=="finance")$shall_show=$shall_show||true;
	                            if(strstr($ww, '店长') && $use_role=="shop")$shall_show=$shall_show||true;
	                            if(strstr($ww, '物流') && $use_role=="logistics")$shall_show=$shall_show||true;
	                            if(!$shall_show) continue;
	                        }
	                        $time_c=strtotime($line['CREATED_STAMP']);
	                        $time_dif_day=round(($time_now-$time_c)/(3600*24));
	                        $memo="";
	                        
	                        $memo="<!--\n";
	                        foreach ($line as $key => $value) {
	                            $memo.="$key=$value\n";
	                        }
	                        $memo.="-->";
	                        
	                        $read_group[$taobao_order_sn]['orders'][$order_id]['refunds'][$refund_id]=array(
	                            '类型'=>'退款',
	                            '状态'=>$refund_status_name[$line['STATUS']],
	                            '金额'=>$line['TOTAL_AMOUNT'],
	                            '顾客'=>$line['consignee'],
	                            '原因'=>'',
	                            '时间'=>"申请于".$line['CREATED_STAMP']."<br><span".
	                                ($time_dif_day>5?" class='keikoku'>":">").
	                                ($time_dif_day>0?$time_dif_day."天前":"今天").
	                                "</span>",
	                            '待办'=>get_refund_next_responsor($line),
	                            '备注'=>$memo,
	                        );
	                    }
	                }
                }
                
                //SERVICE
                $read_group[$taobao_order_sn]['orders'][$order_id]['services']=array();
                if($group2['services'] && is_array($group2['services'])){
                    foreach ($group2['services'] as $service_id => $line) {
                    	
                        if($use_role!='viewer'){
                        	if($need_pending) {
                        		if($line['back_shipping_status'] != 5 ) {
                        			continue;
                        		}
                        	} else {
                        		if($line['back_shipping_status'] == 5 ) {
                        			continue;
                        		}
                        	}
                        }
                    	
                        $ww=get_service_next_responsor($line);
                        if($use_role!='viewer'){
                            $shall_show=false;
                            if(strstr($ww, '客服') && $use_role=="postsale")$shall_show=$shall_show||true;
                            if(strstr($ww, '财务') && $use_role=="finance")$shall_show=$shall_show||true;
                            if(strstr($ww, '店长') && $use_role=="shop")$shall_show=$shall_show||true;
                            if((strstr($ww, '物流') || strstr($ww, '消费者')) && $use_role=="logistics")$shall_show=$shall_show||true;
                            if(!$shall_show) continue;
                        }
                        $time_c=strtotime($line['apply_datetime']);
                        $time_dif_day=round(($time_now-$time_c)/(3600*24));
                        $memo="";
                        $memo="<!--\n";
                        foreach ($line as $key => $value) {
                            $memo.="$key=$value\n";
                        }
                        $memo.="-->";
                        
                        $service_info =$line;
                        
                        $return_info=get_service_return_deliver_info($line['service_id']);
                        if($line['origin_facility_name'])$memo.="发出仓库：".$line['origin_facility_name']."<br>";
                        if($line['bill_no'])$memo.="发出运单：".$line['shipping_name']." <input type='text' readonly='readonly' style='border: none;' value='".$line['bill_no']."'><br>";
                        if($line['facility_name'])$memo.="运回仓库：".$line['facility_name']."<br>";
                        if($return_info['deliver_company'])$memo.="运回运单：".$return_info['deliver_company'].$return_info['deliver_number']."<br>";
                        $read_group[$taobao_order_sn]['orders'][$order_id]['services'][$service_id]=array(
                            '类型'=>($line['service_type']==1?'换货':($line['service_type']==2?'退货':'售后')),
                            '状态'=>get_service_line_status_description($line),
                            '金额'=>"-",
                            '顾客'=>$line['consignee'],
                            '原因'=>'',
                            '时间'=>"申请于".$line['apply_datetime']."<br><span".
                                ($time_dif_day>5?" class='keikoku'>":">").
                                ($time_dif_day>0?$time_dif_day."天前":"今天").
                                "</span>",
                            '待办'=>get_service_next_responsor($line),
                            '备注'=>$memo,
                            'service_info'=>$service_info,
                        );
                    }
                } 
                
                
                if(
                    !empty($group2['msg']) ||
                    !empty($group2['services']) ||
                    !empty($group2['refunds'])
                ){
                    //DO NOTHING MORE
                } else {
                    if(true || $use_role=='postsale'){//Let every one see
                        if($group2['order_info'] && count($group2['order_info'])){
                            $line=$group2['order_info'];

                            $time_c=strtotime($line['order_time']);
                            $time_dif_day=round(($time_now-$time_c)/(3600*24));
                            $memo="";
                            
                            $memo="<!--\n";
                            foreach ($group2['order_info'] as $k => $v) {
                                $memo.="$k=$v\n";
                            }
                            $memo.="-->";

                            $msg_line=get_sale_support_message_id($line['order_id']);
                            if($use_role=='postsale' && $msg_line && strstr($msg_line['program'], '无需处理')){
                                //hide
                            }else{

                                if($msg_line){
                                    $memo.=$msg_line['program']."<br>";
                                }else{
                                    $memo.="没有沟通结论"."<br>";
                                }

                                $status_memo="";
                                $linked_refunds=get_refunds_of_one_order($line['order_id']);
                                $linked_services=get_services_of_one_order($line['order_id']);
                                if($linked_services){
                                    foreach ($linked_services as $no => $linked_service) {
                                        $back_refunds=get_refunds_of_one_order($linked_service['back_order_id']);
                                        if($back_refunds)array_merge($linked_refunds,$back_refunds);
                                    }
                                    foreach ($linked_services as $no => $linked_service) {
                                        $status_memo.="售后【".($no)."】<br>".get_service_line_status_description($linked_service)."<br>";
                                    }
                                }
                                if($linked_refunds){
                                    foreach ($linked_refunds as $no => $linked_refund) {
                                        $status_memo.="退款【".($no)."】<br>".$refund_status_name[$linked_refund['STATUS']]."<br>";
                                    }
                                }
                                if(empty($linked_refunds) && empty($linked_services)){
                                    $has_no_applications=true;
                                }else{
                                    $has_no_applications=false;
                                }
                                $is_hide_erp_order=true;
                                if($use_role=='postsale'){
                                    if($has_no_applications){
                                        //Tell KF?
                                        $is_hide_erp_order=false;
                                    } else{
                                        //hide it
                                        $is_hide_erp_order=true;
                                    }
                                }else if($use_role=='viewer'){
                                    $is_hide_erp_order=false;
                                }
                                if($msg_line && strstr($msg_line['program'], '无需处理')){
                                    if($has_no_applications){
                                        $nottodo_word="此单无需处理";
                                    }else{
                                        $nottodo_word="此单结论为无需处理<br>但存在售后操作请核实";
                                    }
                                }else{
                                    if($has_no_applications){
                                        $nottodo_word='供客服选择发起售后';
                                    }else{
                                        $nottodo_word='供客服核查进度';
                                    }
                                }
                                if(!$is_hide_erp_order && $line['order_sn']){
                                    $the_order_type=get_order_type_name($line['order_sn']);
                                    if($the_order_type=='订单')
                                    $read_group[$taobao_order_sn]['orders'][$order_id]['order_info']=array(
                                        '类型'=>$the_order_type,
                                        '状态'=>(empty($status_memo)?'未建申请':'已有申请'.$status_memo),
                                        '金额'=>$line['order_amount'],
                                        '顾客'=>$line['consignee'],
                                        '原因'=>'',
                                        '时间'=>"建立于".$line['order_time']."<br><span".
                                            ($time_dif_day>5?" class='keikoku'>":">").
                                            ($time_dif_day>0?$time_dif_day."天前":"今天").
                                            "</span>",
                                        '待办'=>$nottodo_word,
                                        '备注'=>$memo,
                                    );
                                }

                            }
                        }
                    }
                }
            }
        }
    }
    //COUNT
    foreach ($read_group as $taobao_order_sn => $group1) {
        $read_group[$taobao_order_sn]['count']=count($group1['taobao_refunds'])+count($group1['tmall_refunds']);
        $read_group[$taobao_order_sn]['count2']=count($group1['taobao_refunds'])+count($group1['tmall_refunds']);
        foreach ($group1['orders'] as $order_id => $group2) {
            $plus_pure=(count($group2['msg']))+
                (count($group2['services']))+
                (count($group2['refunds']));

            if(
                $use_role!='viewer' && $plus_pure==0 
                 && get_order_type_name($group2['order_sn'])!='订单'
            ){
                unset($read_group[$taobao_order_sn]['orders'][$order_id]);
                continue;
            }else{
                $plus=$plus_pure+(count($group2['order_info'])>0?1:0);
                $read_group[$taobao_order_sn]['orders'][$order_id]['count']=$plus;
                $read_group[$taobao_order_sn]['count']+=$plus;

                if($plus>0)$plus2=$plus+1;else $plus2=0;
                $read_group[$taobao_order_sn]['orders'][$order_id]['count2']=$plus;
                $read_group[$taobao_order_sn]['count2']+=$plus2;
            }
        }
        if($read_group[$taobao_order_sn]['count']==0)unset($read_group[$taobao_order_sn]);
        if($use_role!='viewer' && $read_group[$taobao_order_sn]['count']==(count($group1['taobao_refunds'])+count($group1['tmall_refunds']))){
            unset($read_group[$taobao_order_sn]);
        }
    }

    return $read_group;
}

function level_5_judgement_light($read_group,$OFFSET=0,$page_limit_count=50){
    $count_for_page=0;
    $the_page_group=array();
    foreach ($read_group as $key => $value) {
        $count_for_page+=1;
        if($OFFSET*$page_limit_count+$page_limit_count>=$count_for_page && $OFFSET*$page_limit_count<$count_for_page){
            $the_page_group[$key]=$value;
        }else if($OFFSET*$page_limit_count+$page_limit_count<$count_for_page){
            break;
        }
    }
    return $the_page_group;
}

//g($_SESSION);

/**
GROUPED over here
**/

/**
SERVICE RELATED CODES BEGIN FROM HERE
**/
/*
为每一种角色计算其售后任务统计
*/
function get_count_of_duties_for_each_roles($party_id=0,$dist='all'){
    global $db;
    if(!isset($conditions)) $conditions="";
    $c_viewer=get_conditions_for_role('viewer',$party_id);
    $c_postsale=get_conditions_for_role('postsale',$party_id);
    $c_shop=get_conditions_for_role('shop',$party_id);
    $c_logistics=get_conditions_for_role('logistics',$party_id);
    $c_finance=get_conditions_for_role('finance',$party_id);
    $sql_0="SELECT
                count(1)
            FROM
                service s
            INNER JOIN ecs_order_info o ON s.order_id = o.order_id
            -- INNER JOIN ecs_carrier_bill cb ON o.carrier_bill_id = cb.bill_id
            INNER JOIN romeo.order_shipment ros ON ros.order_id=convert(o.order_id using utf8)
            INNER JOIN romeo.shipment rs ON rs.shipment_id=ros.shipment_id
            LEFT JOIN romeo.facility rf1 ON s.facility_id=rf1.FACILITY_ID
            LEFT JOIN romeo.facility rf2 ON o.facility_id=rf2.FACILITY_ID
            LEFT JOIN romeo.party rp ON o.party_id=rp.PARTY_ID
            LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
            LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
            LEFT JOIN romeo.refund r ON r.ORDER_ID = CONVERT(s.back_order_id USING utf8)
            WHERE
                (
                    o.pay_status != '4' 
                    AND r.REFUND_ID is NULL
                ) AND
                (
                    (s.inner_check_status=0 AND s.outer_check_status=0) OR 
                    ( ".is_require_service_call_party($party_id)." 
                        AND s.service_call_status!=2)
                ) AND
                s.is_complete = '0' ".
                ($dist=='all'?"":($dist=='fenxiao'?" AND (md.type='fenxiao' AND (d.distributor_id not in ".nise_bunke_distributor_ids().")) ":" AND (md.type='zhixiao' OR d.distributor_id in ".nise_bunke_distributor_ids().") ")).
                it_is_the_glory_of_God_to_conceal_a_thing()."
            and (rs. STATUS is null or rs. STATUS != 'SHIPMENT_CANCELLED')
            group by s.service_id,o.order_id
    ";
    if($party_id) $sql_0.=" AND s.party_id='$party_id' ";
    $re=array(
        'viewer'=> array('name'=>'未完退换统计','value'=>$db->getOne($sql_0.$c_viewer.";")),
        'postsale'=> array('name'=>'客服','value'=>$db->getOne($sql_0.$c_postsale.";")),
        'logistics'=> array('name'=>'物流','value'=>$db->getOne($sql_0.$c_logistics.";")),
        'finance'=> array('name'=>'财务','value'=>$db->getOne($sql_0.$c_finance.";")),
        'shop'=> array('name'=>'店长','value'=>$db->getOne($sql_0.$c_shop.";")),
    );
    return $re;
}

/*
未完结的售后分页搜索
*/
function postsale_search_service_order($conditions="", $OFFSET=0,$dist='all'){
    global $db;
    if(!isset($conditions)) $conditions="";
    $sql="SELECT
                s.*, o.*, 
                -- cb.bill_no,cb.carrier_id,
                rs.tracking_number bill_no,rs.carrier_id,
                s.facility_id,rf1.FACILITY_NAME facility_name,
                o.facility_id AS origin_facility_id,rf2.FACILITY_NAME origin_facility_name,
                rp.NAME party_name
            FROM
                service s
            INNER JOIN ecs_order_info o ON s.order_id = o.order_id
            -- INNER JOIN ecs_carrier_bill cb ON o.carrier_bill_id = cb.bill_id
            INNER JOIN romeo.order_shipment ros ON ros.order_id=convert(o.order_id using utf8)
            INNER JOIN romeo.shipment rs ON rs.shipment_id=ros.shipment_id
            LEFT JOIN romeo.facility rf1 ON s.facility_id=rf1.FACILITY_ID
            LEFT JOIN romeo.facility rf2 ON o.facility_id=rf2.FACILITY_ID
            LEFT JOIN romeo.party rp ON CONVERT(o.party_id USING utf8)=rp.PARTY_ID
            LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
            LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
            LEFT JOIN romeo.refund r ON r.ORDER_ID = CONVERT(s.back_order_id USING utf8)
            WHERE
                (
                    o.pay_status != '4' 
                    AND r.REFUND_ID is NULL
                ) AND
                s.is_complete = '0' 
                AND (
                    (s.inner_check_status=0 AND s.outer_check_status=0) 
                    OR ( ".is_require_service_call_party($party_id)." 
                        AND s.service_call_status!=2)
                )
                ".
                ($dist=='all'?"":($dist=='fenxiao'?" AND (md.type='fenxiao' AND (d.distributor_id not in ".nise_bunke_distributor_ids().")) ":" AND (md.type='zhixiao' OR d.distributor_id in ".nise_bunke_distributor_ids().") ")).
                it_is_the_glory_of_God_to_conceal_a_thing().
                $conditions."
            AND (rs. STATUS is null or rs. STATUS != 'SHIPMENT_CANCELLED')
            GROUP BY s.service_id,o.order_id
            ORDER BY s.apply_datetime desc ".
                //($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
                ";";
    $r=$db->getAll($sql);
    return $r;
}

/*
<option value="0">不使用预设模式</option>
<option value="1">客服：待审核的换货申请</option>
<option value="2">客服：待审核的退货申请</option>
<option value="3">客服：验货入库待确认退款</option>
<option value="4">客服：验货入库待确认换货</option>
<option value="5">财务：退款申请待审核</option>
<option value="6">物流：已审核待退货</option>
<option value="7">物流：货已收到待验货</option>
<option value="8">财务：退款信息已确认待退款</option>
<option value="9">客服：申请被拒待回访</option>
*/

/*
预设模式名称 废掉

function get_name_of_mode($mode){
    switch ($mode) {
        case 0:
            $n="不使用预设模式";
            break;
        case 1:
            $n="客服：待审核的换货申请";
            break;
        case 2:
            $n="客服：待审核的退货申请";
            break;
        case 3:
            $n="客服：验货入库待确认退款";
            break;
        case 4:
            $n="客服：验货入库待确认换货";
            break;
        case 5:
            $n="财务：退款申请待审核";
            break;
        case 6:
            $n="物流：已审核待退货";
            break;
        case 7:
            $n="物流：货已收到待验货";
            break;
        case 8:
            $n="财务：退款信息已确认待退款";
            break;
        case 9:
            $n="客服：申请被拒待回访";
            break;
        case 10:
            $n="物流：退款申请待审核";
            break;
        case 11:
            $n="客服：退款申请待审核";
            break;
        default:
            die("虽然不知道你是何方妖孽但是你看起来很厉害的样子道高一尺魔高一丈冤冤相报何时了于是就死在这里吧呵呵呵呵 —— —— 邪恶的大鲵");
            break;
    }
    return $n;
}
*/
/*
为每个预设模式设定售后搜索条件
*/
function get_conditions_for_mode($mode){
    $conditions="";
    switch ($mode) {
        case 0:
            break;
        case 1:
            $conditions.=" AND (s.service_status=0) AND (s.service_type=1) ";
            break;
        case 2:
            $conditions.=" AND (s.service_status=0) AND (s.service_type=2) ";
            break;
        case 3:
            $conditions.=" AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND s.service_call_status=1 AND (s.service_type=2)) ";
            break;
        case 4:
            $conditions.=" AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND s.service_call_status=1 AND (s.service_type=1)) ";
            break;
        case 5:
            $conditions.=" AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND s.service_call_status=2 AND s.service_pay_status=0 AND s.service_type=2) ";
            break;
        case 6:
            $conditions.=" AND (s.service_status=1 AND s.back_shipping_status=0) ";
            break;
        case 7:
            $conditions.=" AND (s.service_status=1 AND s.back_shipping_status=12 AND s.outer_check_status=0 AND s.inner_check_status=0) ";
            break;
        case 8:
            $conditions.=" AND (s.service_pay_status=2 AND s.service_type=2) ";
            break;
        case 9:
            $conditions.=" AND (s.service_status=3 AND s.service_call_status!=2) ";
            break;
        // 物流：等待消费者寄回货物
        case 22:
            $conditions.=" AND (s.back_shipping_status=5) ";
            break;
        default:
            $conditions.=" AND 0 ";
            //die("虽然不知道你是何方妖孽但是你看起来很厉害的样子道高一尺魔高一丈冤冤相报何时了于是就死在这里吧呵呵呵呵 —— —— 邪恶的大鲵");
            break;
    }
    return $conditions;
}

/*
退货流程的各种售后状态
$type
1.淘宝同步过来的：客服
2.待审核：客服和店长，具体的先后还无法判定
3.已审核，等待接收退货：物流
4.退货已收，等待验货入库：物流
5.验货拒绝而退回：客服
6.退回已收：客服 感觉这个没啥用姑且写上
7.验货入库，待确认：客服，去建立退款申请之类的
8.确认，等待退款：财务
9.已经退款，等待核帐：客服
10.退款已收：客服
11.已经回访，审核未通过：客服 完结向
*/

/*
为每种角色设定售后搜索条件
*/
function get_conditions_for_role($role=0,$party_id=0){
    $conditions='';
    switch ($role) {
        case 'viewer':
            $conditions.="
            AND (
                    (1 ".get_conditions_for_role('shop')." ) OR 
                    (1 ".get_conditions_for_role('postsale')." ) OR 
                    (1 ".get_conditions_for_role('logistics')." ) OR 
                    (1 ".get_conditions_for_role('finance')." )
                )
            ";
            break;
        case 'shop':
            $conditions.="
                AND (
                    (s.service_status=0)
                ) 
            ";
            break;
        case 'postsale':
            $conditions.="
                AND (
                    (s.service_status=0)
                    OR ((s.outer_check_status=23 OR s.inner_check_status=32) AND (".is_require_service_call_party($party_id)." AND s.service_call_status=1))
                    OR (s.service_status=3 AND ( ".is_require_service_call_party($party_id)." AND s.service_call_status!=2))
                ) 
            ";
            break;
        case 'logistics':
            $conditions.="
                AND (
                    (s.service_status=1 AND s.back_shipping_status=0)
                    OR (s.service_status=1 AND s.back_shipping_status=5)
                    OR (s.service_status=1 AND s.back_shipping_status=12 AND s.outer_check_status=0 AND s.inner_check_status=0)
                ) 
            ";
            break;
        case 'finance':
            $conditions.="
                 AND 0 AND ((s.outer_check_status=23 OR s.inner_check_status=32) AND ( ".is_require_service_call_party($party_id)." AND s.service_call_status=2) AND s.service_pay_status=0 AND s.service_type=2)
            ";
            break;
        default:
            break;
    }
    return $conditions;
}

/*
为某种角色搜索未完结售后
*/
function seek_uncompleted_services_for_role($role=0,$page=0,$con=' AND 1 ',$dist){
    $conditions = $con.get_conditions_for_role($role);
    $r=postsale_search_service_order($conditions,$page,$dist);
    return $r;
}

/*
将售后搜索结果按照订单ID归类
*/
function to_services_for_order($uc_services){
    $oucs=array();
    foreach ($uc_services as $key => $line) {
        $oucs[$line['order_id']][$line['service_id']]=$line;
    }
    return $oucs;
}

/*
将退款搜索结果按照订单ID归类
*/
function to_refunds_for_order($uc_refunds){
    $oucr=array();
    foreach ($uc_refunds as $key => $line) {
        $oucr[$line['ORDER_ID']][$line['REFUND_ID']]=$line;
    }
    return $oucr;
}

/**
ROMEO.REFUND BEGINS　HERE
**/

/*
搜索未完成的订单
*/
function get_uncompleted_refunds($conditions="",$party_id=0,$OFFSET=0,$dist='all'){
    global $db;
    $sql="SELECT
                r.*, o.*,rp.NAME party_name
            FROM
                romeo.refund r
            LEFT JOIN ecshop.ecs_order_info o ON r.order_id = o.order_id
            LEFT JOIN romeo.party rp ON o.party_id=rp.PARTY_ID
            LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
            LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
            WHERE
                1 ".but_the_honour_of_kings_is_to_search_out_a_matter().
                "
                AND o.pay_status != '4' 
                AND r.STATUS != 'RFND_STTS_EXECUTED'
                AND r.STATUS != 'RFND_STTS_CANCELED'
                AND r.STATUS != 'RFND_STTS_CHECK_OK'
                ".$conditions.
                ($dist=='all'?"":($dist=='fenxiao'?" AND (md.type='fenxiao' AND (d.distributor_id not in ".nise_bunke_distributor_ids()."))":" AND (md.type='zhixiao' OR d.distributor_id in ".nise_bunke_distributor_ids().") "))."
                ORDER BY r.CREATED_STAMP desc ".
                //($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
                ";";
    $r=$db->getAll($sql);
    return $r;
}

/*
按照预设模式搜索未完结退款申请的条件
*/
function get_uncompleted_refunds_mode_condition($mode){
    $conditions="";
    switch ($mode) {
        case 5:
            //CW
            $conditions.=" AND (r.STATUS='RFND_STTS_INIT' OR r.STATUS='RFND_STTS_IN_CHECK') 
                            AND r.CHECK_DATE_2 is not null AND r.CHECK_DATE_3 is null ";
            break;
        case 8:
            //CW
            $conditions.=" AND (r.STATUS='RFND_STTS_CHECK_OK') AND (r.EXECUTE_DATE is null) ";
            break;
        case 20:
            //WL
            $conditions.=" AND (r.STATUS='RFND_STTS_INIT' OR r.STATUS='RFND_STTS_IN_CHECK') 
                            AND r.CHECK_DATE_1 is not null AND r.CHECK_DATE_2 is null ";
            break;
        case 19:
            //KF
            $conditions.=" AND (r.STATUS='RFND_STTS_INIT' OR r.STATUS='RFND_STTS_IN_CHECK') 
                            AND r.CHECK_DATE_1 is null ";
            break;
        default:
            $conditions.=" AND 0 ";
            break;
    }
    return $conditions;
}

/*
按照预设模式搜索未完结退款申请
*/
function get_uncompleted_refunds_mode($mode,$party_id=0,$page=1){
    return get_uncompleted_refunds(get_uncompleted_refunds_mode_condition($mode),$party_id,$page);
}

/*
为各种角色计算退款任务统计
*/
function get_count_of_refunding_for_each_roles($party_id=0,$dist='all'){
    global $db;
    if(!isset($conditions)) $conditions="";
    $c_viewer="AND (
                (1 ".get_uncompleted_refunds_mode_condition(5).") OR
                (1 ".get_uncompleted_refunds_mode_condition(8).") OR
                (1 ".get_uncompleted_refunds_mode_condition(19).") OR
                (1 ".get_uncompleted_refunds_mode_condition(20).") 
            ) ";
    $c_postsale=get_uncompleted_refunds_mode_condition(19);
    $c_shop=get_uncompleted_refunds_mode_condition(0);
    $c_logistics=get_uncompleted_refunds_mode_condition(20);
    $c_finance="AND (
                    (1 ".get_uncompleted_refunds_mode_condition(5).") OR
                    (1 ".get_uncompleted_refunds_mode_condition(8).") 
                ) ";

    $sql_0="SELECT
                count(1) 
            FROM
                romeo.refund r
            LEFT JOIN ecshop.ecs_order_info o ON r.order_id = o.order_id
            LEFT JOIN romeo.party rp ON convert(o.party_id using utf8)=rp.PARTY_ID
            LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
            LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
            WHERE
                1 ".
                (empty($party_id)?"":" AND o.party_id='$party_id' ")."
                AND o.pay_status != '4' 
                AND r.STATUS != 'RFND_STTS_EXECUTED'
                AND r.STATUS != 'RFND_STTS_CANCELED'
                AND r.STATUS != 'RFND_STTS_CHECK_OK'
                ".$conditions.
                ($dist=='all'?"":($dist=='fenxiao'?" AND (md.type='fenxiao' AND (d.distributor_id not in ".nise_bunke_distributor_ids().")) ":" AND (md.type='zhixiao' OR d.distributor_id in ".nise_bunke_distributor_ids().") ")).
                but_the_honour_of_kings_is_to_search_out_a_matter();
    if($party_id) $sql_0.=" AND o.party_id='$party_id' ";
    $re=array(
        'viewer'=> array('name'=>'退款申请统计','value'=>$db->getOne($sql_0.$c_viewer.";")),
        'postsale'=> array('name'=>'客服','value'=>$db->getOne($sql_0.$c_postsale.";")),
        'logistics'=> array('name'=>'物流','value'=>$db->getOne($sql_0.$c_logistics.";")),
        'finance'=> array('name'=>'财务','value'=>$db->getOne($sql_0.$c_finance.";")),
        //'shop'=> array('name'=>'待店长处理售后案件统计','value'=>$db->getOne($sql_0.$c_shop.";")),
    );
    return $re;
}

/*
为未完结退款申请查找责任方
*/
// function get_refund_next_responsor($line){
//     extract($line);
//     switch($STATUS){
//         case 'RFND_STTS_INIT':
//         case 'RFND_STTS_IN_CHECK':
//             if(empty($CHECK_DATE_1)) return "客服：审核退款申请";
//             else if (empty($CHECK_DATE_2)) return "物流：审核退款申请";
//             else if (empty($CHECK_DATE_3)) return "财务：审核退款申请";
//             break;
//         case 'RFND_STTS_CHECK_OK':
//             if(empty($EXECUTE_DATE))return "财务：执行退款申请";
//             else return "警告：未执行状态有执行时间记录";
//             break;
//         case 'RFND_STTS_EXECUTED':
//             return "此退款申请已经被执行。";
//             break;
//         case 'RFND_STTS_CANCELED':
//             return "此退款申请已经被取消。";
//             break;
//     }
//     return "不知道该找谁干活T_T";
// }

/**
基础工具
**/

function isDevPrivUser($name){
    if($name=="ljni") return true;else return false;
}

/*
根据service_id找出一条service记录
*/
function postsale_search_service($service_id){
    global $db;
    $sql="SELECT * FROM ecshop.service where ecshop.service.service_id='$service_id';";
    $r=$db->getRow($sql);
    //g($r);
    //die("hehe");
    return $r;
}

function postsale_search_service_log_list($service_id){
    global $db;
    $sql="SELECT
            *
        FROM
            ecshop.service_log sl
        WHERE
            sl.service_id = '$service_id';";
    $r=$db->getAll($sql);
    return $r;
}

/*
根据order_id找出order记录
*/
function postsale_search_order_info($order_id){
    global $db;
    $sql="SELECT * FROM ecshop.ecs_order_info WHERE ecshop.ecs_order_info.order_id = '$order_id';";
    $r=$db->getRow($sql);
    return $r;
}

function postsale_search_refund_info($refund_id){
    global $db;
    $sql="SELECT
                r.*
            FROM
                romeo.refund r
            WHERE
                r.REFUND_ID = '$refund_id';";
    $r=$db->getRow($sql);
    return $r;
}

function get_party_name_by_id($party_id){
    global $db;
    $sql="SELECT NAME from romeo.party where PARTY_ID='$party_id';";
    $r=$db->getOne($sql);
    return $r;
}

/**
售后状态人性化工具
**/

/*
*/
function get_order_status_name($v){
    global $_CFG;
    return $_CFG['adminvars']['order_status'][$v];
}

/*
*/
function get_shipping_status_name($v){
    global $_CFG;
    return $_CFG['adminvars']['shipping_status'][$v];
}

/*
*/
function get_pay_status_name($v){
    global $_CFG;
    return $_CFG['adminvars']['pay_status'][$v];
}

/*
*/
function get_invoice_status_name($v){
    if($v==-1)return "不可用";
    global $_CFG;
    return $_CFG['adminvars']['invoice_status'][$v];
}

/*
*/
function get_inventory_status_id_name($v){
    global $_CFG;
    return $_CFG['adminvars']['inventory_status_id'][$v];
}

/*
*/
function get_good_status_name($v){
    global $_CFG;
    return $_CFG['adminvars']['goods_status'][$v];
}

/*
'缺货状态：0有货；1暂缺货；2请等待；3已到货；4取消',
*/
function get_shortage_status_name($v){
    switch ($v) {
        case 0:
            return "有货";
            break;
        case 1:
            return "暂缺货";
            break;
        case 2:
            return "请等待";
            break;
        case 3:
            return "已到货";
            break;
        case 4:
            return "取消";
            break;
    }
}

/*
把service_type转换成中文
*/
// function get_service_item_type_name($v){
//     global $service_type_mapping;
//     return $service_type_mapping[$v];
// }

/*
把back_shipping_status转换成中文
*/
// function get_back_shipping_status_name($v){
//     global $back_shipping_status_mapping;
//     return $back_shipping_status_mapping[$v];
// }

/*
把outer_check_status转换成中文
*/
// function get_outer_check_status_name($v){
//     global $outer_check_status_mapping;
//     return $outer_check_status_mapping[$v];
// }

/*
把inner_check_status转换成中文
*/
// function get_inner_check_status_name($v){
//     global $inner_check_status_mapping;
//     return $inner_check_status_mapping[$v];
// }

/*
把change_shipping_status转换成中文
*/
// function get_change_shipping_status_name($v){
//     global $change_shipping_status_mapping;
//     return $change_shipping_status_mapping[$v];
// }

/*
把service_status转换成中文
*/
// function get_service_status_name($v){
//     global $service_status_mapping;
//     return $service_status_mapping[$v];
// }

/*
把service_pay_status转换成中文
*/
// function get_service_pay_status_name($v){
//     global $service_pay_status_mapping;
//     return $service_pay_status_mapping[$v];
// }

/*
把service_call_status转换成中文
*/
// function get_service_call_status_name($v){
//     global $service_call_status_mapping;
//     return $service_call_status_mapping[$v];
// }

/*
把service_return_key转换成中文
v1: bank_info | carrier_info
*/
// function get_service_return_key_name($v1,$v2){
//     global $service_return_key_mapping;
//     return $service_return_key_mapping[$v1][$v2];
// }

/*
换货申请,待审核
退货申请,待审核
验货通过,已入库,待确认退款信息
验货通过,已入库,待确认换货信息
退款申请，待审核
已审核,待退货
货已收到,待验货
退款信息已确认,待退款
*/

/*
售后服务的状态说明文字
*/
function get_service_line_status_description($line){
    $v=array(
        "service_type"=>get_service_item_type_name($line['service_type']),
        "service_status"=>get_service_status_name($line['service_status']),
        "back_shipping_status"=>get_back_shipping_status_name($line['back_shipping_status']),
        "outer_check_status"=>get_outer_check_status_name($line['outer_check_status']),
        "inner_check_status"=>get_inner_check_status_name($line['inner_check_status']),
        "change_shipping_status"=>get_change_shipping_status_name($line['change_shipping_status']),
        "service_pay_status"=>get_service_pay_status_name($line['service_pay_status']),
        "service_call_status"=>get_service_call_status_name($line['service_call_status'])
    );
    extract($line);
    $r="不明";
    if($service_type==1){
        //Change
        if($service_status==0) $r= "换货申请，待审核";
        if($service_status==1 && $back_shipping_status==0) $r= "已审核，待退货";
        if($service_status==1 && $back_shipping_status==5) $r= "等待消费者寄回货物 ";
        if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) $r= "货已收到，待验货";
        if($outer_check_status==23 || $inner_check_status==32
            && $service_call_status==1) $r= "验货通过，已入库，待确认换货信息";
        /*
        if($outer_check_status==23 || $inner_check_status==32
            && $service_call_status!=1) return "待物流确认<!--物流：请准备换货发货-->";
        if($change_shipping_status==52) return "待客服审核<!--客服：退货被原样退回-->";
        if($change_shipping_status==53) return "待客服审核<!--客服：原样退回件已被顾客查收-->";
        if($change_shipping_status==42) return "待物流确认<!--物流：准备出库-->";
        if($change_shipping_status==43) return "待物流确认<!--物流：准备发货-->";
        if($change_shipping_status==44) return "待客服审核<!--客服：换货已经发货-->";
        if($change_shipping_status==45) return "待客服审核<!--客服：换货已经签收-->";
        */
        if($service_status==2){
            if (empty($change_order_id)) $r= "待确认换货订单";
            else $r="换货订单已确认";
        }
        if($service_status==3 && $service_call_status!=2) $r= "换货申请被拒待客服回访";
    } else if ($service_type==2){
        //Return
        if($service_status==0) return "退货申请，待审核";
        if($service_status==1 && $back_shipping_status==0) $r= "已审核，待退货";
        if($service_status==1 && $back_shipping_status==5) $r= "等待消费者寄回货物 ";
        if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) $r= "货已收到，待验货";
        /*
        if($change_shipping_status==52) return "待客服审核<!--客服：退货被原样退回-->";
        if($change_shipping_status==53) return "待客服审核<!--客服：原样退回件已被顾客查收-->";
        */
        if($outer_check_status==23 || $inner_check_status==32
            && $service_call_status==1 
            ) $r= "验货通过，已入库，待确认退款";
        if($outer_check_status==23 || $inner_check_status==32
            && $service_call_status==2 && $service_pay_status==0) $r= "退款申请待审核";
        if($service_pay_status==2) $r= "退款信息已确认，待退款";
        /*
        if($service_pay_status==4) return "待客服审核<!--客服：退款确认完成-->";
        */
        if($service_status==2) $r= "退货完毕待确认退款申请";
        if($service_status==3 && $service_call_status!=2) $r= "退货申请被拒待客服回访";
        
    }
    $r.="<!-- ";
    foreach ($v as $key => $value) {
        if($value!="")$r.=$value." ";
    }
    $r.=" -->";
    if(1){
        $r.="<!-- 
            service_type=".$line['service_type']."
            service_status=".$line['service_status']."
            back_shipping_status=".$line['back_shipping_status']."
            outer_check_status=".$line['outer_check_status']."
            inner_check_status=".$line['inner_check_status']."
            change_shipping_status=".$line['change_shipping_status']."
            service_pay_status=".$line['service_pay_status']."
            service_call_status=".$line['service_call_status']."
            change_order_id=".$line['change_order_id']."
         -->";
    }
    return $r;
}

/*
待客服审核
待物流确认
待运营确认
待客服建立退款申请
待客服建立退货申请
待客服建立换货申请
待录单/补寄
*/

/*
为售后服务寻找责任方
*/
// function get_service_next_responsor($line){
//     extract($line);
//     $r='';
//     if($service_type==1){
//         //Change
//         if($service_status==0) return "待客服和店长审核";//<!--客服和店长：确认是否同意换货要求-->
//         if($service_status==1 && $back_shipping_status==0) return "待物流确认";//<!--物流：准备接收退回的货物-->
//         if($service_status==1 && $back_shipping_status==5) return "等待消费者寄回货物";//<!--物流：准备接收退回的货物-->
//         if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) return "待物流确认";//<!--物流：准备验货-->
//         if($outer_check_status==23 || $inner_check_status==32
//             && ('1'!=is_require_service_call_party($line['party_id']) || $service_call_status==1)
//             && empty($change_order_id)) return "待客服确认换货订单";//<!--客服：完成退回，待确认意向，可建立换货申请-->
//         if($outer_check_status==23 || $inner_check_status==32
//             && ('1'!=is_require_service_call_party($line['party_id']) || $service_call_status!=1)) return "待物流确认";//<!--物流：请准备换货发货-->
//         if($change_shipping_status==52) return "待客服审核";//<!--客服：退货被原样退回-->
//         if($change_shipping_status==53) return "待客服审核";//<!--客服：原样退回件已被顾客查收-->
//         if($change_shipping_status==42) return "待物流确认";//<!--物流：准备出库-->
//         if($change_shipping_status==43) return "待物流确认";//<!--物流：准备发货-->
//         if($change_shipping_status==44) return "待客服审核";//<!--客服：换货已经发货-->
//         if($change_shipping_status==45) return "待客服审核";//<!--客服：换货已经签收-->
//         if($service_status==3 && ('1'!=is_require_service_call_party($line['party_id']) || $service_call_status!=2)) return "待客服审核";//<!--客服：换货审核未通过，需要回访-->
//     } else if ($service_type==2){
//         //Return
//         if($service_status==0) return "待客服和店长审核";//<!--客服和店长：确认是否同意退货要求-->
//         if($service_status==1 && $back_shipping_status==0) return "待物流确认";//<!--物流：准备接收退回的货物-->
//         if($service_status==1 && $back_shipping_status==5) return "等待消费者寄回货物";//<!--物流：准备接收退回的货物-->
//         if($service_status==1 && $back_shipping_status==12 && $outer_check_status==0 && $inner_check_status==0) return "待物流确认";//<!--物流：准备验货-->
//         if($change_shipping_status==52) return "待客服审核";//<!--客服：退货被原样退回-->
//         if($change_shipping_status==53) return "待客服审核";//<!--客服：原样退回件已被顾客查收-->
//         if($outer_check_status==23 || $inner_check_status==32
//             && ('1'!=is_require_service_call_party($line['party_id']) || $service_call_status==1) 
//             ) return "待客服建立退款申请";//<!--客服：完成退回，待确认意向，可建立退货申请-->
//         if($outer_check_status==23 || $inner_check_status==32
//             && ('1'!=is_require_service_call_party($line['party_id']) || $service_call_status==2) && $service_pay_status==0) return "待物流和财务确认";//<!--物流和财务：请确认退货退款申请-->
//         if($service_pay_status==2) return "待客服审核";//<!--客服：已退款请确认-->
//         if($service_pay_status==4) return "待客服审核";//<!--客服：退款确认完成-->
//         if($service_status==3 && ('1'!=is_require_service_call_party($line['party_id']) || $service_call_status!=2)) return "待客服审核";//<!--客服：退货审核未通过，需要回访-->
//     }
//     return "客服核查流程异常";

// }

/*
为一个订单查找其退款
*/
function get_services_of_one_order($order_id){
    global $db;
    $sql="SELECT
                s.*
            FROM
                ecshop.service s
            WHERE
                s.order_id = $order_id;";
    $r=$db->getAll($sql);
    if($r){
        $p=array();
        foreach ($r as $no => $line) {
            $p['s'.$line['service_id']]=$line;
        }
        return $p;
    }
    else return $r;
}

/**
关于REFUND退款啥的 在这下面
**/

/*
为一个订单查找其退款
*/
function get_refunds_of_one_order($order_id){
    global $db;
    $sql="SELECT
                r.*
            FROM
                romeo.refund r
            WHERE
                r.ORDER_ID = '$order_id';";
    $r=$db->getAll($sql);
    if($r){
        $p=array();
        foreach ($r as $no => $line) {
            $p['r'.$line['REFUND_ID']]=$line;
        }
        return $p;
    }
    else return $r;
}

function get_refunds_of_one_order_deep($order_id){
    global $db;
    $sql="SELECT
            r.*
        FROM
            (
                SELECT DISTINCT
                    eor.order_id
                FROM
                    ecshop.order_relation eor
                WHERE
                    eor.order_id = '$order_id'
                OR eor.root_order_id = '$order_id'
            ) as oo
        INNER JOIN romeo.refund r ON convert(oo.order_id using utf8) = r.ORDER_ID
        WHERE oo.order_id IS NOT NULL
        AND oo.order_id != '';";
    $r=$db->getAll($sql);
    if($r){
        $p=array();
        foreach ($r as $no => $line) {
            $p['r'.$line['REFUND_ID']]=$line;
        }
        return $p;
    }
    else return $r;
}
/*
查找售后的退款BA表 然而并没有什么用
*/
// function get_back_amount($service_id){
//     global $db;
//     $sql="SELECT
//                 ba.*
//             FROM
//                 ecshop.back_amount ba
//             WHERE
//                 ba.service_id = $service_id;";
//     $r=$db->getAll($sql);
//     return $r;
// }
/*
查找售后的退货BG表
*/
function get_back_goods($service_id){
    global $db;
    $sql="SELECT
                bg.*
            FROM
                ecshop.back_goods bg
            WHERE
                bg.service_id = $service_id;";
    $r=$db->getAll($sql);
    return $r;
}


/**
SALE_SUPPORT_MESSAGE
**/

/*
查找订单的售后沟通表
*/
function get_sale_support_message_id($order_id){
    global $db;
    $sql="SELECT
                *
            FROM
                ecshop.sale_support_message ssm
            WHERE
                ssm.order_id = $order_id
            ORDER BY
                ssm.created_stamp DESC
            LIMIT 1;";
    $r=$db->getRow($sql);
    return $r;
}

function postsale_message_accelerator($party_id=0,$OFFSET=0,$conditions_message="",$dist){
    global $db;
    $condition=(($party_id!=0)?"o.PARTY_ID='$party_id' AND ":" ").
        ($dist=='all'?"":($dist=='fenxiao'?" (md.type='fenxiao' AND (d.distributor_id not in ".nise_bunke_distributor_ids().")) AND ":" (md.type='zhixiao' OR d.distributor_id in ".nise_bunke_distributor_ids().") AND "));
    $sql_seek_last_ones="SELECT
            o.taobao_order_sn,
            ssm.order_id,
            o.order_sn OSN,
            ssm.sale_support_message_id,
            ssm.created_stamp,
            ssm.send_by,
            o.consignee,
            ssm.support_type,
            ssm.program,
            ssm.status STATUS,
            ssm.message,
            ssm.next_process_group
        FROM ecshop.sale_support_message ssm
        LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id = o.order_id
        LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        WHERE
            $condition
            1
            $conditions_message
            AND (
                (ssm.next_process_group is not null AND ssm.next_process_group != '')
                OR (ssm.program is null OR ssm.program = ''
                    OR (ssm.program='追回' AND  o.shipping_status!=11) -- 为了建立追回的工单流
                    )
            )
            AND ssm.sale_support_message_id
            IN (
                SELECT MAX( issm.sale_support_message_id ) 
                FROM ecshop.sale_support_message issm
                WHERE issm.order_id = ssm.order_id
            )
            ".hide_message_long_age().
            //($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
            ";";
    $result=$db->getAll($sql_seek_last_ones);
    $discussing=array();
    $wait_to_do=array();
    foreach ($result as $key => $line) {
        if(empty($line['program'])){
            $discussing[$line['order_id']]=$line;
        } else{
            $wait_to_do[$line['order_id']]=$line;
        }
    }
    $array=array(
        "discussing"=>$discussing,
        "wait_to_do"=>$wait_to_do
    );
    //print_r($array);
    return $array;
}
function postsale_message_accelerator_died($party_id=0,$OFFSET=0,$conditions_message="",$dist){
    global $db;
    $condition=(($party_id!=0)?"o.PARTY_ID='$party_id' AND ":" ").
        ($dist=='all'?"":($dist=='fenxiao'?" (md.type='fenxiao' AND (d.distributor_id not in ".nise_bunke_distributor_ids().")) AND ":" (md.type='zhixiao' OR d.distributor_id in ".nise_bunke_distributor_ids().") AND "));
    $sql_seek_last_ones="SELECT
            o.taobao_order_sn,
            ssm.order_id,
            o.order_sn OSN,
            ssm.sale_support_message_id,
            ssm.created_stamp,
            ssm.send_by,
            o.consignee,
            ssm.support_type,
            ssm.program,
            ssm.status STATUS,
            ssm.message,
            ssm.next_process_group
        FROM
            (
                SELECT
                    issm.order_id,MAX(issm.sale_support_message_id) sale_support_message_id
                FROM
                    ecshop.sale_support_message issm
                GROUP BY
                    issm.order_id
            )AS t1
        LEFT JOIN ecshop.sale_support_message ssm ON ssm.sale_support_message_id = t1.sale_support_message_id
        LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id = o.order_id
        LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
        WHERE
            $condition
            1
            $conditions_message
            AND (
                (ssm.next_process_group is not null AND ssm.next_process_group != '')
                OR (ssm.program is null OR ssm.program = ''
                    OR (ssm.program='追回' AND  o.shipping_status!=11) -- 为了建立追回的工单流
                    )
            )
            ".hide_message_long_age().
            //($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
            ";";
    $result=$db->getAll($sql_seek_last_ones);
    $discussing=array();
    $wait_to_do=array();
    foreach ($result as $key => $line) {
        if(empty($line['program'])){
            $discussing[$line['order_id']]=$line;
        } else{
            $wait_to_do[$line['order_id']]=$line;
        }
    }
    $array=array(
        "discussing"=>$discussing,
        "wait_to_do"=>$wait_to_do
    );
    //print_r($array);
    return $array;
}

function show_the_sale_support_message_lines($party_id=0,$conditions_message="",$dist){
    $array=array(
        "discussing"=>get_discussing_sale_support_message_lines($party_id,$conditions_message,$dist),
        "wait_to_do"=>get_determined_sale_support_message_lines($party_id,$conditions_message,$dist)
    );
    //pp($array);
    return $array;
}

function get_discussing_sale_support_message_lines($party_id=0,$conditions_message="",$dist='all'){
    global $db;
    $sql="SELECT
            DISTINCT ssm.order_id
        FROM
            ecshop.sale_support_message ssm
            LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id=o.order_id
        where 
            ".(($party_id!=0)?"o.PARTY_ID='$party_id' AND ":" ")."
            1
            ".$conditions_message."
            AND (ssm.next_process_group is null OR ssm.next_process_group = '')
            AND (ssm.program is not null AND ssm.program != '')
            ;";
    $done_ids=$db->getCol($sql);
    $sql="SELECT
            DISTINCT ssm.order_id
        FROM
            ecshop.sale_support_message ssm
            LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id=o.order_id
        where 
            ".(($party_id!=0)?"o.PARTY_ID='$party_id' AND ":" ")."
            ssm.status!='FINISHED'
            ".hide_message_long_age()."
            ;";
    $ids=$db->getCol($sql);
    //pp($ids);
    $array=array();
    foreach ($ids as $no => $id) {
        if(in_array($id, $done_ids))continue;
        $sql="SELECT
                o.taobao_order_sn,
                ssm.order_id,
                o.order_sn OSN,
                ssm.created_stamp,
                ssm.support_type,
                ssm.program,
                ssm.status,
                ssm.next_process_group,
                ssm.service_id,
                ssm.refund_id,
                ssm.order_sn
            FROM
                ecshop.sale_support_message ssm
                LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id=o.order_id
                LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
                LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
            WHERE
                ssm.order_id = $id
                ".
                ($dist=='all'?"":($dist=='fenxiao'?" AND (md.type='fenxiao' AND (d.distributor_id not in ".nise_bunke_distributor_ids().")) ":" AND (md.type='zhixiao' OR d.distributor_id in ".nise_bunke_distributor_ids().") ")).
                "
            ORDER BY
                ssm.created_stamp DESC
            LIMIT 1;";
        $line=$db->getRow($sql);//Get Most Recent One Line
        if($line && check_sale_support_message_line_should_display($line)){
            if($line['support_type']!=9){
                global $sale_support_type_map;
                $line['support_type']=$sale_support_type_map[$line['support_type']];
            }else {
                //if(preg_match('/^[^-]+(?=-)/', $line['support_type'], $m)) $line['support_type']=$m[0];
                //if(preg_match('/(?<=-).*$/', $line['message'], $h)) $line['message']=$h[0];
            }
            $array[$line['order_id']]=$line;
        }
    }
    return $array;
}

function get_determined_sale_support_message_lines($party_id=0,$conditions_message="",$dist='all'){
    global $db;
    $sql="SELECT
            DISTINCT ssm.order_id
        FROM
            ecshop.sale_support_message ssm
            LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id=o.order_id
        where
            ".(($party_id!=0)?" o.PARTY_ID='$party_id' AND ":" ")." 
            1 
            ".$conditions_message."
            AND (ssm.next_process_group is null OR ssm.next_process_group = '')
            AND (ssm.program is not null AND ssm.program != '')
            ".hide_message_long_age()."
            ;";
    $ids=$db->getCol($sql);
    //pp($ids);
    $array=array();
    foreach ($ids as $no => $id) {
        $sql="SELECT
                o.taobao_order_sn,
                ssm.order_id,
                o.order_sn OSN,
                ssm.created_stamp,
                ssm.support_type,
                ssm.status,
                ssm.program,
                ssm.next_process_group,
                ssm.service_id,
                ssm.refund_id,
                ssm.order_sn
            FROM
                ecshop.sale_support_message ssm
                LEFT JOIN ecshop.ecs_order_info o ON ssm.order_id=o.order_id
                LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
                LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
            WHERE
                ssm.order_id = '$id'
                ".
                ($dist=='all'?"":($dist=='fenxiao'?" AND (md.type='fenxiao' AND (d.distributor_id not in ".nise_bunke_distributor_ids().")) ":" AND (md.type='zhixiao' OR d.distributor_id in ".nise_bunke_distributor_ids().") ")).
                "
            ORDER BY
                ssm.created_stamp DESC
            LIMIT 1;";
        $line=$db->getRow($sql);//Get Most Recent One Line
        if($line && check_sale_support_message_line_should_display($line)){
            if($line['support_type']!=9){
                global $sale_support_type_map;
                $line['support_type']=$sale_support_type_map[$line['support_type']];
            }else {
                //if(preg_match('/^[^-]+(?=-)/', $line['message'], $m)) $line['support_type']=$m[0];
                //if(preg_match('/(?<=-).*$/', $line['message'], $h)) $line['message']=$h[0];
            }
            $array[$line['order_id']]=$line;
        }
    }
    return $array;
}

function check_sale_support_message_line_should_display($line){
    extract($line);
    if($status!="FINISHED"){
        //discussing last record
        return true;
    } else {
        //conclusion
        $method="(ERP系统:把点确认方案的人拖出去)";
        $hanasi="(ERP系统:没啥要说的就这样了)";
        if(preg_match('/^[^-]+(?=-)/', $message, $matches)){
            $method=$matches[0];
        }
        if(preg_match('/(?<=-).*$/', $message, $matches)){
            if(!empty($matches[0]))$hanasi=$matches[0];
        }
        //echo "TRY KNOW $message as $method and $hanasi";
        $r0=$r2=$r3=false;
        if(strstr($method,"款")){
            if(!empty($refund_id))$r0 =  false;else $r0 = true;
        }
        if(strstr($method,"货")){
            if(!empty($service_id))$r1 = false;else $r1 = true;
        }
        if(strstr($method,"补")){//以后还要加上换货什么也可以的样子//追回就不鸟了
            if(!empty($order_sn))$r2 = false;else $r2 = true;
        }
        //echo "$order_id : 0".($r0?"Y":"N").",1".($r1?"Y":"N").",2".($r2?"Y":"N")."- -";
        return $r0 || $r1 || $r2;
    }
}

function get_order_relation_path($order_id){
    global $db;
    $sql="SELECT
                eor.parent_order_id,
                eor.parent_order_sn,
                eor.root_order_id,
                eor.root_order_sn
            FROM
                `order_relation` eor
            WHERE
                eor.order_id = '$order_id'
                OR eor.root_order_id= '$order_id';";
    $r=$db->getRow($sql);
    return $r;
}

/**
SYNC
**/
function get_sync_taobao_refund_lines_of_a_trade($trade_id){
    global $db;
    $sql="SELECT
                *
            FROM
                ecshop.sync_taobao_refund str
            WHERE
                str.tid='$trade_id';";
    $r=$db->getAll($sql);
    return $r;
}

function count_sync_taobao_refund_waiting_lines($party_id=0){
    global $db;
    $sql="SELECT
                count(1)
            FROM
                ecshop.sync_taobao_refund str
            WHERE
                (
                    str.status!='SELLER_REFUSE_BUYER'
                    AND str.status!='CLOSED'
                    AND str.status!='SUCCESS'
                ) ".($party_id!=0?" AND str.party_id='$party_id' ":" ").
                just_this_week('str.created').
            ";";
    /*
     str.status='WAIT_SELLER_AGREE'
                    OR (
                        str.status!='SELLER_REFUSE_BUYER'
                        AND str.status!='CLOSED'
                        AND (str.erp_refund_id IS NULL || str.erp_refund_id='')
                    )
    */
    $r=$db->getOne($sql);
    return $r;
}

function get_sync_taobao_refund_waiting_lines($party_id=0,$OFFSET=-1,$dist='zhi_fen_xiao',$distributor='-1',$refund_type='all',$conditions_tr=""){
    $r_zx=array();
    $r_fx=array();
    $refundSql = "";
    if($refund_type=='mobile' || $refund_type=='notMoble'){
    	$refundSql = "AND str.is_mobile_refund='".$refund_type."' ";
    }
    if($dist=='zhi_fen_xiao' || $dist=='zhixiao'){
        global $db;
        if($distributor == '-1') {
        	$sql="SELECT
                    str.*
                FROM
                    ecshop.sync_taobao_refund str
                WHERE
                    (
                        str.status!='SELLER_REFUSE_BUYER' 
                        AND str.status!='CLOSED'
                        AND str.status!='SUCCESS'  
                        $refundSql
                    ) ".
                    //($party_id!=0?" AND str.party_id='$party_id' ":" ").
                    just_this_week('str.created').
                    $conditions_tr."
                    ORDER BY 
                        str.created desc
                    ".
                    ($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
                    ";";
        } else {
        	$sql="SELECT
                    str.*
                FROM
                    ecshop.sync_taobao_refund str
                LEFT JOIN ecshop.distributor d ON d.name = str.seller_nick  
                WHERE
                	d.distributor_id = '".$distributor."' AND
                    (
                        str.status!='SELLER_REFUSE_BUYER' 
                        AND str.status!='CLOSED'
                        AND str.status!='SUCCESS'  
                        $refundSql
                    ) ".
                    just_this_week('str.created').
                    $conditions_tr."
                    ORDER BY 
                        str.created desc
                    ".
                    ($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
                    ";";
        }
        $r_zx=$db->getAll($sql);
    }
    $r_fx=get_sync_taobao_fenxiao_refund_waiting_lines($party_id,$OFFSET,$dist,$conditions_tr);
    return array('zhixiao'=>$r_zx,'fenxiao'=>$r_fx);
}

function get_orders_by_taobao_order_sn($tid){
    global $db;
    $sql="SELECT
                *
            FROM
                ecshop.ecs_order_info o
            WHERE
                o.taobao_order_sn like '$tid%';";
    $r=$db->getAll($sql);
    return $r;
}

function reorganize_waiting_taobao_refunds_lines($waiting_taobao_refunds,$dist){
    //pp($waiting_taobao_refunds);
    $wtr=array();
    foreach ($waiting_taobao_refunds as $no => $line) {
        $tid=$line['tid'];
        $refund_id=$line['refund_id'];
        unset($line['tid']);
        $wtr[$tid]['refunds'][$refund_id]=$line;
        $orders=get_orders_by_taobao_order_sn($tid);
        if($orders && is_array($orders) && count($orders)>0){
            foreach ($orders as $no => $order_line) {
                if($dist=='all' || sinri_get_is_distribute_order($order_line['order_id'])==$dist){
                    $wtr[$tid]['orders'][$order_line['order_id']]=$order_line;
                    $order_msg_line=get_sale_support_message_id($order_line['order_id']);
                    $message_status="NONE";
                    if($order_msg_line){
                        $message_status=$order_msg_line['status'];
                    }
                    $wtr[$tid]['orders'][$order_line['order_id']]['message_status']=$message_status;
                }
            }
            if(count($wtr[$tid]['orders'])==0){
                unset($wtr[$tid]);
            }
        } else{
            //not logged orders pass
        }
    }
    foreach ($wtr as $tid => $waiting_taobao_refunds_of_one_tid) {
        $wtrs_show=false;
        foreach ($waiting_taobao_refunds_of_one_tid['refunds'] as $refund_id => $refund_line) {
            $wtr_show=false;
            if(is_array($waiting_taobao_refunds_of_one_tid['orders'])){
                foreach ($waiting_taobao_refunds_of_one_tid['orders'] as $order_id => $order_line) {
                    switch ($refund_line['status']) {
                        case 'WAIT_SELLER_AGREE':
                            $wtr_show=true;
                            break;
                        case 'WAIT_BUYER_RETURN_GOODS':
                        case 'WAIT_SELLER_CONFIRM_GOODS':
                            if($order_line['message_status']!='FINISHED')$wtr_show=true;
                            break;
                        case 'SUCCESS':
                            if($order_line['message_status']!='FINISHED')$wtr_show=true;
                            break;
                        case 'SELLER_REFUSE_BUYER':
                            //NOT SHOW
                            break;
                        case 'CLOSED':
                            if($order_line['message_status']=='OK')$wtr_show=true;
                            break;
                    }
                }
                if(!$wtr_show)unset($wtr[$tid]['refunds'][$refund_id]);
            } else {
                $wtr_show=true;
            }
            $wtrs_show=$wtrs_show || $wtr_show;
        }
        if(!$wtrs_show)unset($wtr[$tid]);
    }
    //pp($wtr);
    return $wtr;
}

function decode_the_related_process_ids($decoded_ids){
    $duties=array();
    if(preg_match_all('/(?<=s\[)[^\]]+(?=\])/', $decoded_ids, $matches)){
        foreach ($matches as $mkey => $mv) {
            foreach ($mv as $key => $value) {
                $duties['s'][$value]=$value;
            }
        }
    }
    if(preg_match_all('/(?<=r\[)[^\]]+(?=\])/', $decoded_ids, $matches)){
        foreach ($matches as $mkey => $mv) {
            foreach ($mv as $key => $value) {
                $duties['r'][$value]=$value;
            }
        }
    }
    if(preg_match_all('/(?<=b\[)[^\]]+(?=\])/', $decoded_ids, $matches)){
        foreach ($matches as $mkey => $mv) {
            foreach ($mv as $key => $value) {
                $duties['b'][$value]=$value;
            }
        }
    }
    //pp($duties);
    return $duties;
}

function encode_the_related_process_ids($service_refund_ids=array()){
    $idstr="";
    //pp($service_refund_ids);
    foreach ($service_refund_ids as $type => $ids) {
        switch ($type) {
            case 's':
                foreach ($ids as $key => $value) {
                    $idstr.="s[$value]";
                }
                break;
            case 'r':
                foreach ($ids as $key => $value) {
                    $idstr.="r[$value]";
                }
                break;
            case 'b':
                foreach ($ids as $key => $value) {
                    $idstr.="b[$value]";
                }
                break;
            default:
                foreach ($ids as $key => $value) {
                    $idstr.="x[$value]";
                }
                break;
        }
    }
    return $idstr;
}

function check_return_service_information($order_id){
    global $db;
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
    return $return_detail_tmp;
}

function get_service_return_deliver_info($service_id){
    global $db;
    $sql="SELECT
            s.service_id,
            dc.return_value AS deliver_company,
            dn.return_value AS deliver_number,
            ifnull(oi.mobile, oi.tel) AS mobile
        FROM
            ecshop.service s
        INNER JOIN ecshop.ecs_order_info oi ON oi.order_id = s.order_id
        LEFT JOIN ecshop.service_return dc ON dc.service_id = s.service_id
            AND dc.return_name = 'deliver_company'
        LEFT JOIN ecshop.service_return dn ON dn.service_id = s.service_id
            AND dn.return_name = 'deliver_number'
        WHERE
            s.service_status <> 3
        AND s.service_type IN ('1', '2', '6')
        AND s.service_id = '{$service_id}'
        GROUP BY
            s.service_id
        ORDER BY
            s.apply_datetime DESC
        LIMIT 1;
        ";
    $r=$db->getRow($sql);
    return $r;
}

function sinri_get_is_distribute_order($order_id){
    global $db;
    $sql="SELECT
                md.type
            FROM
                ecshop.ecs_order_info o
            LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
            LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
            WHERE
                o.order_id = '$order_id';
    ";
    $r=$db->getRow($sql);
    $zhi_fen_xiao=$r['type'];//($r['type']=='zhixiao'?'0':'1');
    return $zhi_fen_xiao;
}

function get_order_key_information_by_order_id($order_id){
    return get_order_key_information('',$order_id);
}

function get_order_key_information_by_order_sn($order_sn){
    return get_order_key_information($order_sn,'');
}

/**
 * 返回订单最核心的信息。这部分参照新系统的get_core_order_info的逻辑。
 */
function get_order_key_information($order_sn='',$order_id=''){
    global $db, $ecs;

    if(empty($order_sn) && empty($order_id)){
        return false;
    }

    $cond = party_sql('o.party_id') .' AND ';

    if (trim($order_sn) != '') {
        $cond = "o.order_sn = '{$order_sn}' ";
    } else {
        $order_id = intval($order_id);
        $cond = "o.order_id = '{$order_id}' ";
    }
    $sql = "SELECT rp.NAME party_name, o.*,
                er1.region_name country_name,
                er2.region_name province_name,
                er3.region_name city_name,
                er4.region_name district_name,  
                p.pay_name, p.pay_code, p.is_cod, s.shipping_name,
                d.name distributor_name,f.FACILITY_NAME
            FROM {$ecs->table('order_info')} o
            LEFT JOIN {$ecs->table('payment')} p ON o.pay_id = p.pay_id
            LEFT JOIN {$ecs->table('shipping')} s ON o.shipping_id = s.shipping_id
            LEFT JOIN romeo.party rp ON o.party_id=rp.PARTY_ID
            LEFT JOIN ecshop.ecs_region er1 ON o.country=er1.region_id
            LEFT JOIN ecshop.ecs_region er2 ON o.province=er2.region_id
            LEFT JOIN ecshop.ecs_region er3 ON o.city=er3.region_id
            LEFT JOIN ecshop.ecs_region er4 ON o.district=er4.region_id
            LEFT JOIN ecshop.distributor d ON o.distributor_id=d.distributor_id
            LEFT JOIN romeo.facility f ON o.facility_id=f.FACILITY_ID
            WHERE {$cond} LIMIT 1
            ";
    $order = $db->getRow($sql);

    if(!$order) return false;
    $sql = "SELECT og.*, g.top_cat_id, g.cat_id, gs.internal_sku,si.shipping_invoice,pm.product_id
            FROM {$ecs->table('order_goods')} AS og
              LEFT JOIN {$ecs->table('goods')} g ON og.goods_id = g.goods_id
              LEFT JOIN {$ecs->table('goods_style')} gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id
              LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
              LEFT JOIN romeo.order_shipping_invoice si ON og.order_id = si.order_id
              WHERE og.order_id = '{$order['order_id']}' 
            GROUP BY og.rec_id";
    $order_goods = $db->getAll($sql);
    $order['order_goods'] =$order_goods;
    
    $total_goods_number = 0;
    if(!empty($order_goods)) {
        foreach($order_goods as $order_good) {
            $total_goods_number += $order_good['goods_number'];
        }
    }

    $order['total_goods_number'] = $total_goods_number;
        
    $sql = "SELECT * FROM {$ecs->table('order_action')} WHERE order_id = '{$order['order_id']}' ORDER BY action_time ";
    $order_action = $db->getAll($sql);
    $order['order_action'] = $order_action;
    $order_info_str = @serialize($order);
    $order['order_info_md5'] = md5($order_info_str);
    return $order;
}

function get_order_type_name($order_sn){
    $t1=strrev($order_sn);
    $t2=substr($t1, 0,2);
    switch ($t2) {
        case 't-':
            return '系统建立的退货订单';
            break;
        case 'h-':
            return '系统建立的换货订单';
            break;
        default:
            return '订单';
            break;
    }
}

function get_sale_support_messages_by_order_id($order_id){
    global $db;
    $sql="SELECT
                *
            FROM
                ecshop.sale_support_message ssm
            WHERE
                ssm.order_id = '$order_id'
            ORDER BY
                ssm.created_stamp DESC;";
    $r=$db->getAll($sql);
    return $r;
}

function get_order_by_order_id($order_id){
    global $db;
    $sql="SELECT o.* FROM ecshop.ecs_order_info o WHERE o.order_id=$order_id;";
    $r=$db->getRow($sql);
    return $r;
}

function get_pure_taobao_order_sn($log_taobao_order_sn){
    if(!empty($log_taobao_order_sn) && strlen($log_taobao_order_sn)>=15)
    return substr($log_taobao_order_sn, 0,15);
    else return '';
}

function get_party_children($party_id){
    global $db;
    $sql="SELECT
            *
        FROM
            romeo.party p
        WHERE
            p.PARENT_PARTY_ID = '$party_id'
        ;";
    $r=$db->getAll($sql);
    $party_list=array();
    if($r){
        foreach ($r as $key => $line) {
            $party_list[$line['PARTY_ID']]=$line;
        }
    }
    return $party_list;
}

function get_party_with_all_children($party_id){
    if($party_id==0)return array(0=>0);
    global $is_party_contains_children;
    global $db;
    $sql="SELECT
            *
        FROM
            romeo.party p
        WHERE
            p.PARTY_ID = '$party_id'
        LIMIT 1
        ;";
    $party_list=array();
    $r=$db->getRow($sql);
    if($r && $r['IS_LEAF']=='N'){
        if(!$is_party_contains_children){
            die('您选择的['.get_party_name_by_id($party_id).']可能包含许多下属业务组织。售后操作数据量大，合并查询会引起数据请求拥挤，导致ERP变慢 T_T 请选择具体的业务组织重试。');
        }
        $party_check_list=array($party_id=>$party_id);
        while(!empty($party_check_list)){
            foreach ($party_check_list as $no => $pid) {
                $list=get_party_children($pid);
                foreach ($list as $ppid => $line) {
                    if($line['IS_LEAF']=='Y'){
                        $party_list[]=$ppid;
                    }else{
                        $party_check_list[$ppid]=$ppid;
                    }
                }
                unset($party_check_list[$pid]);
            }
        }
    }else{
        $party_list[]=$party_id;
    }
    return $party_list;
}

function getTrackingNumbersForOrder($order_id){
    global $db;
    $sql="SELECT DISTINCT s.tracking_number from ecshop.ecs_order_info eoi
        LEFT JOIN romeo.order_shipment os on CONVERT(eoi.order_id USING utf8)=os.order_id
        LEFT JOIN romeo.shipment s on os.shipment_id=s.shipment_id
        WHERE eoi.order_id={$order_id};";
    $tns=$db->getCol($sql);
    return $tns;
}

/**
Taobao refund message
**/
function getTaobaoRefundMessages($party_id=null,$taobao_refund_id=null){
    global $db;
    $conditions="1";
    if($party_id){
        $conditions.=" AND party_id={$party_id} ";
    }
    if($taobao_refund_id){
        $conditions.=" AND refund_id={$taobao_refund_id} ";
    }
    $sql="SELECT * FROM ecshop.sync_taobao_refund_message WHERE ".$conditions."  ORDER BY created desc;;";
    $result=$db->getAll($sql);
    return $result;
}
/**
TMALL REFUND
**/
function get_sync_tmall_refund_waiting_lines($party_id=0,$OFFSET=-1,$dist='zhi_fen_xiao',$distributor='-1',$conditions_tr=""){
    // 淘寶的這個API已被廢棄
    return array();
    global $db;   
     if($dist=='zhi_fen_xiao' || $dist=='zhixiao'){
     	if($distributor == '-1') {
	        $sql="SELECT
	                str.*, 
	                strt.`STATUS` return_status,
	                strt.company_name,
	                strt.sid,
	                strt.operation_log
	            FROM
	                ecshop.sync_tmall_refund_bill str
	            LEFT JOIN ecshop.sync_tmall_return_bill strt ON str.refund_id = strt.refund_id
	            WHERE
	                (
	                    str. STATUS != 'seller_refuse'
	                    AND str. STATUS != 'closed'
	                    AND str. STATUS != 'success'
	                )".
	                //($party_id!=0?" AND str.party_id='$party_id' ":" ").
	                just_this_week('str.created').
	                $conditions_tr."
	                ORDER BY 
	                    str.created desc
	                ".
	                ($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
	                ";";     		
     	} else {
 	        $sql="SELECT
	                str.*, 
	                strt.`STATUS` return_status,
	                strt.company_name,
	                strt.sid,
	                strt.operation_log
	            FROM
	                ecshop.sync_tmall_refund_bill str
	            LEFT JOIN ecshop.sync_tmall_return_bill strt ON str.refund_id = strt.refund_id 
	            LEFT JOIN ecshop.distributor d ON d.name = str.seller_nick 
	            WHERE
	            	 d.distributor_id = '".$distributor."' AND 
	                (
	                    str. STATUS != 'seller_refuse'
	                    AND str. STATUS != 'closed'
	                    AND str. STATUS != 'success'
	                )".
	                //($party_id!=0?" AND str.party_id='$party_id' ":" ").
	                just_this_week('str.created').
	                $conditions_tr."
	                ORDER BY 
	                    str.created desc
	                ".
	                ($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
	                ";";      		
     	}
        $r=$db->getAll($sql);
    }else{
        $r=array();
    }
    return $r;
}

function get_sync_tmall_refund_lines_of_a_trade($trade_id){
    // 淘寶的這個API已被廢棄
    return array();
    global $db;
    $sql="SELECT
            str.*, 
            strt. STATUS return_status,
            strt.company_name,
            strt.sid,
            strt.operation_log
        FROM
            ecshop.sync_tmall_refund_bill str
        LEFT JOIN ecshop.sync_tmall_return_bill strt ON str.refund_id = strt.refund_id
            WHERE
                str.tid='$trade_id';";
    $r=$db->getAll($sql);
    return $r;
}

/**
Taobao fenxiao refund
**/
function get_sync_taobao_fenxiao_refund_waiting_lines($party_id=0,$OFFSET=-1,$dist='zhi_fen_xiao',$conditions_tr=""){
    if($dist=='zhixiao')return array();

    global $db;
    $sql="SELECT
        stfr.party_id,
        stfr.is_return_goods,
        stfr.refund_create_time,
        stfr.refund_status,
        stfr.refund_fee,
        stfr.pay_sup_fee,
        stfr.return_reason,
        stfr.refund_desc,
        stfr.supplier_nick,
        stfr.distributor_nick,
        stfr.modified,
        stfr.purchase_order_id,
        stfr.refund_flow_type,
        stfr.timeout,
        stfr.to_type,
        stfbr.refund_create_time created,
        stfbr.refund_status status,
        stfbr.goods_status_desc buyer_goods_status_desc,
        stfbr.need_return_goods buyer_need_return_goods,
        stfbr.return_fee buyer_return_fee,
        stfbr.to_seller_fee buyer_to_seller_fee,
        stfbr.refund_reason buyer_refund_reason,
        stfbr.refund_desc buyer_refund_desc,
        stfbr.refund_id refund_id,
        stfbr.sub_order_id buyer_sub_order_id,
        stfbr.biz_order_id tid,
        stfbr.buyer_nick,
        stfbr.modified buyer_modified
    FROM
        ecshop.sync_taobao_fenxiao_buyer_refund stfbr
    LEFT JOIN ecshop.sync_taobao_fenxiao_refund stfr ON stfbr.sub_order_id = stfr.sub_order_id
    WHERE
        stfbr.refund_status NOT IN(4, 5, 6) AND stfr.party_id in (".$party_id.") ";

    $sql="SELECT
            str.*
        FROM
            (".$sql.") str
        WHERE
            1 ".
            //($party_id!=0?" AND str.party_id='$party_id' ":" ").
            just_this_week('str.created').
            $conditions_tr."
            ORDER BY 
                str.created desc
            ".
            ($OFFSET<0?"":" LIMIT 50 OFFSET ".($OFFSET*50)).
            ";";

    return $db->getAll($sql);
}

function get_sync_taobao_fenxiao_refund_lines_of_a_trade($trade_id){
    global $db;
    $sql="SELECT
        stfr.party_id,
        stfr.is_return_goods,
        stfr.refund_create_time,
        stfr.refund_status,
        stfr.refund_fee,
        stfr.pay_sup_fee,
        stfr.return_reason,
        stfr.refund_desc,
        stfr.supplier_nick,
        stfr.distributor_nick,
        stfr.modified,
        stfr.purchase_order_id,
        stfr.refund_flow_type,
        stfr.timeout,
        stfr.to_type,
        stfbr.refund_create_time created,
        stfbr.refund_status status,
        stfbr.goods_status_desc buyer_goods_status_desc,
        stfbr.need_return_goods buyer_need_return_goods,
        stfbr.return_fee buyer_return_fee,
        stfbr.to_seller_fee buyer_to_seller_fee,
        stfbr.refund_reason buyer_refund_reason,
        stfbr.refund_desc buyer_refund_desc,
        stfbr.refund_id refund_id,
        stfbr.sub_order_id buyer_sub_order_id,
        stfbr.biz_order_id tid,
        stfbr.buyer_nick,
        stfbr.modified buyer_modified
    FROM
        ecshop.sync_taobao_fenxiao_buyer_refund stfbr
    LEFT JOIN ecshop.sync_taobao_fenxiao_refund stfr ON stfbr.sub_order_id = stfr.sub_order_id
    WHERE
        stfbr.biz_order_id='{$trade_id}';";
    $r=$db->getAll($sql);
    return $r;
}
?>