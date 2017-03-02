<?php
/**
 * ouku[欧酷网]
 * 售前留言函数库
 * @author :zwsun<zwsun@ouku.com>
 * @copyright ouku inc
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}


/**
 * 获得用户的留言
 *
 * @param int $goods_id 商品id
 * @param int $limit 一次获取的条数
 * @param int $offset 记录开始的位置
 * @param int $type 留言类型
 * @param int $all 是否为该用户全部数据，仅仅在我的欧酷里面调用
 * @param int $reply 0：全部；1：已回复；2：未回复；3：已退回；4：已删除
 * @return array 包含留言内容，和总数
 */
function get_user_bjcomments($goods_id = 0, $limit = 10, $offset = 0, $type = '', $all = 0, $reply = 0) {
  global $userInfo, $db;
  $goods_id = intval($goods_id);
  $userId = $userInfo['userId'];
  $condition = "";
  if ($reply == 1) {
  	$condition = " AND reply != '' AND status = 'OK' ";
  } elseif ($reply == 2) {
  	$condition = " AND (reply IS NULL OR reply = '') AND status = 'OK' ";
  } elseif ($reply == 3) {
  	$condition = " AND status = 'REJECTED' ";
  } elseif ($reply == 4) {
  	$condition = " AND status = 'DELETED' ";
  }
  $sql = "SELECT comment_id AS commentId, store_goods_id AS storeGoodsId, user_id AS userId, nick AS nick, comment AS comment, status AS status, post_datetime AS postDatetime, reply AS reply, replied_by AS repliedBy, replied_nick AS repliedNick, IFNULL(replied_datetime,'') AS repliedDatetime, type ".
  "FROM bj_comment WHERE user_id = '$userId' AND store_goods_id = $goods_id ".( $type ? " AND type = '{$type}' " : " AND type != 'complaint'" )." AND store_id = 0 $condition ORDER BY postDatetime DESC LIMIT $limit OFFSET $offset ";
  if($all) {
    $sql = "SELECT comment_id AS commentId, store_goods_id AS storeGoodsId, user_id AS userId, nick AS nick, comment AS comment, status AS status, post_datetime AS postDatetime, reply AS reply, replied_by AS repliedBy, replied_nick AS repliedNick, IFNULL(replied_datetime,'') AS repliedDatetime, type ".
  "FROM bj_comment WHERE user_id = '$userId'  AND store_id = 0 $condition ORDER BY postDatetime DESC LIMIT $limit OFFSET $offset ";
  }
  $comments = $db->getAll($sql);
  $comments = array_map('filter_bjcomment', $comments);
  $comments = array_map('add_bjcomment_info', $comments);
  $comments = array_map('add_bjcomment_rank', $comments);
  $is_tip = false;
  foreach ($comments as $key => $comment) {
    if (!$is_tip && !$comment['reply']) {
      $is_tip = true;
      break;
    }
  }
  //ncchen 081220
  if ($all) {
  	$total = $db->getOne("SELECT COUNT(*) FROM bj_comment WHERE user_id = '$userId' AND store_id = 0 $condition ");
  } else {
  	$total = $db->getOne("SELECT COUNT(*) FROM bj_comment WHERE user_id = '$userId' AND store_goods_id = $goods_id AND store_id = 0 $condition ");
  }
  return array('comments'=>$comments, 'total'=>$total, 'is_tip'=>$is_tip);
}

/**
 * 获得留言
 *
 * @param string $type 类型，默认是所有
 * @param int $goods_id 商品id
 * @param int $limit 一次获取的条数
 * @param int $offset 记录开始的位置
 * @return array 包含留言内容，和总数
 */
function get_bjcomments($type, $goods_id = 0, $limit = 10, $offset = 0) {
  global $db, $userInfo;
  $goods_id = intval($goods_id);
  $condition = " AND store_goods_id = $goods_id ";
  if (!in_array($type, array('goods', 'shipping', 'payment', 'postsale', 'complaint'))) {  $type = ''; }
  
  if ($type) { $condition .= " AND type = '$type' "; }
  else { $condition .= " AND type != 'complaint'"; }
  //if ($userInfo['userId']) $condition .= " AND user_id != '{$userInfo['userId']}' "; 取消全部留言里面不显示用户自己的留言
  
  $sql = "SELECT comment_id AS commentId, store_goods_id AS storeGoodsId, user_id AS userId, nick AS nick, comment AS comment, status AS status, post_datetime AS postDatetime, reply AS reply, replied_by AS repliedBy, replied_nick AS repliedNick, IFNULL(replied_datetime,'') AS repliedDatetime, type ".
  "FROM bj_comment WHERE store_id = 0 {$condition} 	AND replied_by is not null AND status = 'OK' ORDER BY post_datetime DESC LIMIT $limit OFFSET $offset ";
  $comments = $db->getAll($sql);
//  pp($sql);
  $comments = array_map('filter_bjcomment', $comments);
  $comments = array_map('add_bjcomment_info', $comments);
  $comments = array_map('add_bjcomment_rank', $comments);
  $sql_c = "SELECT COUNT(*) FROM bj_comment WHERE store_id = 0 {$condition}	AND replied_by is not null AND status = 'OK'";
  $total = $db->getOne($sql_c);
  return array('comments' => $comments, 'total'=>$total);
}

/**
 * 获得用户咨询的次数
 *
 * @param string $userId 用户32位的id
 * @return int 咨询的次数
 */
function get_user_bjcomment_times($userId) {
  global $db;
  static $times_array;
  if(isset($times_array[$userId])) return $times_array[$userId]; //同一个用户会连续问多次，这样暂时可以优化，减少一个查询次数。
  $sql = "SELECT COUNT(*) FROM bj_comment WHERE user_id = '{$userId}' AND status = 'OK'";
  $times = $db->getOne($sql);
  $times_array[$userId] = $times;
  return $times;
}

/**
 * 添加一些留言对应的信息，比如把type转换一次
 *
 * @param array $comment 评论条目
 * @return array $comment 含用户咨询的次数
 */
function add_bjcomment_info($comment) {
  static $typemap = array('goods'=>'商品咨询', 'shipping'=>'物流配送', 'payment'=>'支付问题', 'postsale'=>'保修及发票', 'complaint'=>'投诉建议');
  $comment['talk_count'] = get_user_bjcomment_times($comment['userId']);
  $comment['type_code'] = $comment['type'];	
  $comment['type'] = @$typemap[$comment['type']];
  return $comment;
}

/**
 * 过滤留言中的关键词
 *
 * @param array $comment 要过滤的评论
 * @return array $comment 过滤后的评论
 */
function filter_bjcomment($comment) {
  $comment['comment'] = filter_word($comment['comment']);
  return $comment;
}

/**
 * 添加用户对留言的满意度
 *
 * @param array $comment 要添加满意度的评论
 * @return array $comment 返回含满意度信息的评论
 */
function add_bjcomment_rank($comment) {
  global $db, $ecs, $userInfo;
  if (!$comment['userId'] || $comment['userId'] != $userInfo['userId']) {
  	return $comment;
  }
  $sql = "SELECT rank_time, rank FROM {$ecs->table('satisfied')} WHERE comment_id = {$comment['commentId']} ORDER BY sid DESC LIMIT 1";
  $rank = $db->getRow($sql);
  $rank_time_to = strtotime($rank['rank_time']);
  $comment_time_to = strtotime($comment['repliedDatetime']);

  if(!$rank || $rank_time_to < $comment_time_to){
    $comment['show_rank'] = true;
  } else {
    $comment['show_rank'] = false;
  }
  $comment['rank'] = $rank['rank'];
  return $comment;
}

/**
 * 获得指定时间的提示语
 *
 * @return string 提示语
 */
function get_bjcomment_time_tip() {
  $now_time = strftime("%H%M");
  static $time_tip;
  if ($time_tip) {
  	return $time_tip;
  }
  $day_time = strftime('%w'); 
  if($day_time == 6 || $day_time == 0){
    $m_time = 1000;
    $n_time = 1900;
  } else {
    $m_time = 0900;
    $n_time = 2100;
  }
  if($now_time > $n_time || $now_time <= 0600){
    $time_tip = '您好，这么晚还来欧酷网！您的留言咨询已提交成功，很抱歉现在欧酷客服已经下班哦！我们会在上班后及时为您回复问题，您的留言可以在我的欧酷"留言回复"中看到。';
  }
  if($now_time > 0600 && $now_time < $m_time){
    $time_tip = '您好，这么早就来欧酷网！您的留言咨询已提交成功，不过欧酷客服还没上班哦！我们会在上班后及时为您回复问题，您的留言可以在我的欧酷"留言回复"中看到。';
  }
  if($now_time >= $m_time && $now_time <= $n_time){
    $time_tip = '您好，您的留言咨询已提交成功，欧酷客服会在10-20分钟内为您回复问题，若您急需解答可拨打客服电话4008-206-206。您的留言可以在我的欧酷"留言回复"中看到。';
  }
  if(($now_time > 1200 && $now_time <1230) || ($now_time > 1800 && $now_time < 1900)){
    $time_tip = '您好，您的留言咨询已提交成功，不过欧酷客服正在食堂吃饭哦！我们会在吃饱后为您及时回复问题。您的留言可以在我的欧酷"留言回复"中看到。';
  }
  return $time_tip;
}