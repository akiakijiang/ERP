<?php
/**
 * ouku[欧酷网]
 * 售前商品评价函数库
 * @author :zwsun<zwsun@ouku.com>
 * @copyright ouku inc
*/

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/**
 * 添加用户对商品的评价
 * 目前没有用，通过论坛里面发帖发表 
 *
 * @return boolean 提交是否成功
 */
function add_goods_comment() {
  global $db, $userInfo;
  $goods_id = intval($goods_id);
  $goods_rank = intval($goods_rank);
  $goods_rank = $goods_rank % 10;
  $advantage = str_replace("　", " ", $advantage);
  $fault = str_replace("　", " ", $fault);

  $sql = "SELECT COUNT(*) FROM goods_comment WHERE goods_id = $goods_id AND user_id = '{$userInfo['userId']}' ";
  if ($db->getOne($sql)) return false;
  $goods_comment = array(
    'goods_id' => $goods_id,
    'user_id' => $userInfo['userId'],
    'nick' => $userInfo['user_name'],
    'comment' => $comment,
    'advantage' => $advantage,
    'fault' => $fault,
    'post_datetime' => date("Y-m-d H:i:s"),
    'goods_rank' => $goods_rank,
  );
  
  $db->autoExecute('goods_comment', $goods_comment);
  $gc_id = $db->insert_id();
  $advantage_tags = explode(" ", $advantage);
  $fault_tags = explode(" ", $fault);
 
  $value = array();
  foreach ($advantage_tags as $tag) {
    $sql = "SELECT tid FROM user_goods_tag_term WHERE name = '$tag'";
    $tid = $db->getOne($sql);
    if (!$tid) {
      $sql = "INSERT INTO user_goods_tag_term(name) VALUES('{$tag}')";
      $db->query($sql);
      $tid = $db->insert_id();
    }
    
    $value[] = "('$gc_id', $tid, '{$userInfo['userId']}', 'ADVANTAGE')";
  }
  foreach ($fault_tags as $tag) {
    $sql = "SELECT tid FROM user_goods_tag_term WHERE name = '$tag'";
    $tid = $db->getOne($sql);
    if (!$tid) {
      $sql = "INSERT INTO user_goods_tag_term(name) VALUES('{$tag}')";
      $db->query($sql);
      $tid = $db->insert_id();
    }
    $value[] = "('$gc_id', $tid, '{$userInfo['userId']}', 'FAULT')";
  }
  if (count($value)) {
    $sql = "INSERT INTO user_tag_goods(gc_id, tid, user_id, term_type) VALUES".join(",", $value);
    $db->query($sql);
  }
    
  return ;

}

/**
 * 对商品的评论发表评价
 * 目前没有用，通过论坛里面发帖发表
 *
 */
function add_goods_comment_post() {
  global $db, $userInfo;
  $gc_id = intval($gc_id);
  $goods_comment_post = array(
    'gc_id' => $gc_id,
    'user_id' => $userInfo['userId'],
    'nick' => $userInfo['user_name'],
    'comment_post' => $comment_post,
    'post_datetime' => date("Y-m-d H:i:s"),
  );
  if ($db->autoExecute('goods_comment_post', $goods_comment_post)) {
  	$sql = "UPDATE replies = replies + 1 WHERE gc_id = $gc_id LIMIT 1";
    $db->query($sql);
  }
  return ;
}

/**
 * 其他用户对用户的评论认定有用没有用
 * 目前没有用，通过论坛里面评定
 *
 * @return 更新是否成功
 */
function add_comment_rank($gc_id) {
  global $db, $userInfo;
  $gc_id = intval($gc_id); //处理商品评论的主键
  $result = false;
  $sql = "SELECT COUNT(*) FROM user_id = '{$userInfo['userId']}' AND gc_id = $gc_id ";
  $useful_sql = $useful ? "useful_ranktimes = useful_ranktimes + 1, " : ""; //如果用户认为是有用的，那么更新有用的数据，否则只更新次数
  $sql = "UPDATE goods_comment SET $useful_sql ranktimes = ranktimes + 1  WHERE gc_id = $gc_id ";
  if ($db->query($sql)) {
    $goods_comment_rank = array(
      'gc_id' => $gc_id,
      'user_id' => $userInfo['userId'],
      'rank_datetime' => date("Y-m-d H:i:s"),
    );
    $db->autoExecute('goods_comment_rank',$goods_comment_rank);
    $result = true;
  }
  return $result;
}

/**
 * 返回用户的标签
 *
 * @param int $goods_id 商品的id
 * @param int $limit 限制的数量
 * @return array 标签及数量
 */
function get_goods_user_tag($goods_id, $type = '', $limit = 5) {
  global $db;
  $goods_id = intval($goods_id);
  $limit = intval($limit);
  $tags = array();
  
  $sql = "SELECT tt.name, COUNT(*) AS c FROM user_tag_goods tg INNER JOIN user_goods_tag_term tt ON tg.tag_id = tt.tag_id  WHERE tg.goods_id = $goods_id ".($type ? " AND tg.term_type = '$type' " : "")." AND tt.status = 'OK'  GROUP BY tt.tag_id  ORDER BY c DESC LIMIT $limit ";
  $tags = $db->getAll($sql);

  return $tags;
}


/**
 * 获取商品的评论
 *
 * @param int $goods_id 商品的id号
 * @param int $limit 一次返回的商品评论数
 * @param int $offset 开始的位置
 * @return array 含有商品评论，总数，以及打分的一个数组
 */
function get_goods_comment($goods_id, $limit = 8, $offset = 0,$condition='') {
  global $db, $ecs;
  $goods_id = intval($goods_id); //不要相信用户的数据
  $limit = intval($limit);
  $offset = intval($offset);
  $sql_new = "SELECT *  FROM goods_comment WHERE goods_id = $goods_id AND status = 'OK' ORDER BY post_datetime DESC LIMIT $limit OFFSET $offset ";
  if($condition == 'useful'){
  	$sql = "SELECT *, (useful_ranktimes/ranktimes) AS best FROM goods_comment WHERE goods_id = $goods_id AND status = 'OK' AND ranktimes > 0 ORDER BY best DESC LIMIT $limit OFFSET $offset";
  	$arr2 = $db->getAll($sql);	
  }
  $goods_comments = $arr2 ? $arr2 : $db->getAll($sql_new);
  foreach($goods_comments as $k => $comments){
  	$goods_comments[$k]['rank'] = '0%';
	  if($comments['ranktimes']){
	 		$goods_comments[$k]['rank'] = $comments['useful_ranktimes']/$comments['ranktimes'] * 100 .'%';
		}  	
  }


  $row = $db->getRow("SELECT COUNT(*) AS total, AVG(score) AS score FROM goods_comment WHERE goods_id = $goods_id AND status = 'OK' ");
  $total = $row['total'];
  $score = sprintf("%.2f", $row['score']);
  $star_width = $score/5*100;
  $star_width = $star_width.'%';
  $goods_tags_advantage  = get_goods_user_tag($goods_id, 'ADVANTAGE');
  $goods_tags_fault  = get_goods_user_tag($goods_id, 'FAULT');
  $sql = "SELECT fid FROM forum_goods WHERE goods_id = $goods_id LIMIT 1 ";
  $fid = $db->getOne($sql);
  $sql = "SELECT COUNT(DISTINCT(user_id)) FROM goods_comment WHERE goods_id = $goods_id AND status = 'OK'";
  $people = $db->getOne($sql);
  
  return array('comments' => $goods_comments, 'total' => $total, 'score' => $score, 'goods_tags_advantage' => $goods_tags_advantage, 'goods_tags_fault' => $goods_tags_fault, 'fid'=>$fid, 'star_width' => $star_width, 'people' => $people);
}

