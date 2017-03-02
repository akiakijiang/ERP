<?php
define('IN_ECS', true);

require('includes/init.php');
require("function.php");
party_priv(PARTY_OUKU);
admin_priv('tj_analyze_aw');
$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

$start = $_REQUEST['start'] ? $_REQUEST['start'] : date("Y-m-d");
$end = $_REQUEST['end'] ? $_REQUEST['end'] : date('Y-m-d', strtotime('+1 day'));

$condition = " log_date >= '{$start}' AND log_date < '{$end}' ";

//外部关键词
$sql = " SELECT words, SUM( words_count ) AS c FROM `log_searchwords` 
WHERE {$condition} GROUP BY words HAVING c > 1 ORDER BY c DESC LIMIT 30 ";
$outer_words = $db->getAll($sql);
$smarty->assign('outer_words', $outer_words);

//内部关键词
$sql = " SELECT val1 AS words, SUM( val2 ) AS c 
FROM `log_awstats` WHERE item_name = 'EXTRA_2' AND {$condition} GROUP BY val1 HAVING c > 1 ORDER BY c DESC LIMIT 30 ";
$inner_words = $db->getAll($sql);
$smarty->assign('inner_words', $inner_words);

//搜索带来的流量
$sql = " SELECT search_name, SUM( pages ) AS pages_count, SUM( hits ) AS hits_count
FROM `log_sereferrals` WHERE {$condition} GROUP BY search_name ORDER BY pages_count DESC , hits_count DESC ";
$search_ref = $db->getAll($sql);
$smarty->assign('search_ref', $search_ref);
$ref_count = count($search_ref) > 10 ? count($search_ref) : 10;

//外部链接带来的流量
$sql = " SELECT page_ref, SUM( pages ) AS pages_count, SUM( hits ) AS hits_count
FROM `log_pagerefs` WHERE {$condition} GROUP BY page_ref HAVING pages_count > 1 ORDER BY pages_count DESC , hits_count DESC LIMIT $ref_count";
$page_ref = $db->getAll($sql);
$smarty->assign('page_ref', $page_ref);

//停留时间  
$sql = " SELECT time_range, AVG(session_count ) AS avg_count
FROM `log_session` WHERE {$condition} GROUP BY time_range ";
$temp_session_time = $db->getAll($sql);
$session_time = array();
$time_order = array(0=>'0s-30s', 1=>'30s-2mn',2=>'2mn-5mn',3=>'5mn-15mn',4=>'15mn-30mn',5=>'30mn-1h',6=>'1h+');
foreach ($time_order as $ks=>$vs) {
  foreach ($temp_session_time as $v) {
    if ($vs == $v['time_range']) {
    	$session_time[] = array('time_range'=>$v['time_range'],'avg_count' => intval($v['avg_count']));
    	break;
    }
  }
}
$smarty->assign('session_time', $session_time);

//浏览的最多的商品
$sql = " SELECT aw.val1 AS goods_id, g.goods_name, SUM( aw.val2 ) AS pages_count
          FROM `log_awstats` aw LEFT JOIN {$ecs->table('goods')} g ON aw.val1 = g.goods_id
          WHERE aw.item_name = 'EXTRA_1' AND {$condition} GROUP BY aw.val1 HAVING pages_count > 1 ORDER BY pages_count DESC LIMIT 30 ";

$goods_viewed = $db->getAll($sql);
$smarty->assign('goods_viewed', $goods_viewed);

//$start = '2007-10-10';
//$end = '2007-10-20';
//
////注册人数
//$sql = " SELECT count(user_id) FROM oukoo_universal.ok_user WHERE created_datetime >= '{$start}' AND created_datetime < '{$end}' ";
//$reg_users_count = $db->getOne($sql);
//
////注册取消订单人数
//$sql = " SELECT ou.user_name FROM oukoo_universal.ok_user ou
//        INNER JOIN {$ecs->table('users')} u ON ou.user_id = u.userId
//        INNER JOIN {$ecs->table('order_info')} o  ON u.user_id =  o.user_id 
//        WHERE ou.created_datetime >= '{$start}' AND ou.created_datetime < '{$end}' AND o.order_status = 2 ";
//
//$order_cancel_users =  $db->getAll($sql);
//$order_cancel_users_count =  count($order_cancel_users);
//
////注册下单时间
//$sql = " SELECT AVG(UNIX_TIMESTAMP(o.order_time) - UNIX_TIMESTAMP(ou.created_datetime)) FROM oukoo_universal.ok_user ou
//        INNER JOIN {$ecs->table('users')} u ON ou.user_id = u.userId
//        INNER JOIN {$ecs->table('order_info')} o  ON u.user_id =  o.user_id 
//        WHERE ou.created_datetime >= '{$start}' AND ou.created_datetime < '{$end}' ";
//
////注册咨询用户
//$sql = " SELECT DISTINCT ou.user_name FROM oukoo_universal.ok_user ou
//        INNER JOIN bj_comment c ON ou.user_id = c.user_id 
//        WHERE ou.created_datetime >= '{$start}' AND ou.created_datetime < '{$end}' ";
//
////注册咨询并且下单用户
//$sql = " SELECT DISTINCT ou.user_name FROM oukoo_universal.ok_user ou
//        INNER JOIN bj_comment c ON ou.user_id = c.user_id 
//        INNER JOIN {$ecs->table('users')} u ON ou.user_id = u.userId
//        INNER JOIN {$ecs->table('order_info')} o  ON u.user_id =  o.user_id 
//        WHERE ou.created_datetime >= '{$start}' AND ou.created_datetime < '{$end}' ";
//
////注册咨询未下单用户
//$sql = " SELECT DISTINCT ou.user_name FROM oukoo_universal.ok_user ou
//        INNER JOIN bj_comment c ON ou.user_id = c.user_id 
//        INNER JOIN {$ecs->table('users')} u ON ou.user_id = u.userId
//        WHERE ou.created_datetime >= '{$start}' AND ou.created_datetime < '{$end}' 
//        AND NOT EXISTS (SELECT order_id from {$ecs->table('order_info')} o WHERE o.user_id  = u.user_id)
//        ";
//pp($users);


$smarty->assign('start', $start);
$smarty->assign('end', $end);

$smarty->display('oukooext/analyze_aw.htm');