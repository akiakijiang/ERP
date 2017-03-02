<?php
define('IN_ECS', true);

require('includes/init.php');
require("function.php");

$start = $_REQUEST['start'] ? $_REQUEST['start'] : date("Y-m-d");
$end = $_REQUEST['end'] ? $_REQUEST['end'] : date('Y-m-d', strtotime('+1 day'));

// 得到一系列的日期
$dates = get_dates($start, $end);

$one_day_seconds = 60 * 60 * 24;
$two_day_seconds = $one_day_seconds * 2;
$ten_day_seconds = $one_day_seconds * 10;



//按类型统计不满意的售前回复
$sql = "SELECT bc.type, COUNT(bc.comment_id) as type_count FROM {$ecs->table('satisfied')} s 
        INNER JOIN bj_comment bc ON bc.comment_id = s.comment_id
        WHERE s.rank = 0 AND s.rank_times = 1  AND bc.replied_datetime >= '{$start}' AND bc.replied_datetime < '{$end}' 
        GROUP BY bc.type ";
$temp_data = $db->getAll($sql);
$unsatisfied_by_type = array();
foreach ($temp_data as $k=>$v) {
  $unsatisfied_by_type[$v['type']] = $v['type_count'];
}

$smarty->assign('unsatisfied_by_type', $unsatisfied_by_type);
//pp($unsatisfied_by_type);

//按类型统计满意的售前回复
$sql = " SELECT bc.type, COUNT(bc.comment_id) as type_count FROM {$ecs->table('satisfied')} s 
        INNER JOIN bj_comment bc ON bc.comment_id = s.comment_id
        WHERE s.rank = 1 AND s.rank_times = 1  AND bc.replied_datetime >= '{$start}' AND bc.replied_datetime < '{$end}' 
        GROUP BY bc.type ";
$temp_data = $db->getAll($sql);
$satisfied_by_type = array();
foreach ($temp_data as $k=>$v) {
  $satisfied_by_type[$v['type']] = $v['type_count'];
}
$smarty->assign('satisfied_by_type', $satisfied_by_type);

$sql = "SELECT bc.type, COUNT(bc.comment_id) AS type_count FROM bj_comment bc 
        WHERE bc.replied_datetime >= '{$start}' AND bc.replied_datetime < '{$end}'
        AND NOT EXISTS (
          SELECT 1 FROM {$ecs->table('satisfied')} s WHERE s.comment_id = bc.comment_id
        )
        GROUP BY bc.type ";
$temp_data = $db->getAll($sql);
$no_rank_by_type = array();
foreach ($temp_data as $k=>$v) {
  $no_rank_by_type[$v['type']] = $v['type_count'];
}
//pp($no_rank_by_type);
$smarty->assign('no_rank_by_type', $no_rank_by_type);

$sql = "SELECT * FROM {$ecs->table('satisfied')} s 
        INNER JOIN bj_comment bc ON bc.comment_id = s.comment_id
        WHERE s.rank = 1 AND s.rank_times = 1  AND bc.replied_datetime >= '{$start}' AND bc.replied_datetime < '{$end}' ";
$comment_satisfied = $db->getAll($sql);

//统计不满意的用户
$sql = "SELECT u.user_name, COUNT(bc.comment_id) AS type_count FROM {$ecs->table('satisfied')} s 
        INNER JOIN bj_comment bc ON bc.comment_id = s.comment_id
        INNER JOIN {$ecs->table('users')} u ON bc.user_id = u.userId
        WHERE s.rank = 0 AND s.rank_times = 1  AND bc.replied_datetime >= '{$start}' AND bc.replied_datetime < '{$end}'
        GROUP BY u.user_name 
        ORDER BY type_count DESC
        ";

$comment_unsatisfied_users = $db->getAll($sql);
$smarty->assign('comment_unsatisfied_users', $comment_unsatisfied_users);



$bjtype_mapping = array('goods'=>'商品咨询', 'shipping'=>'物流配送', 'payment'=>'支付问题', 'postsale'=>'保修及发票', 'complaint'=>'投诉建议');

$smarty->assign('start', $start);
$smarty->assign('end', $end);
$smarty->assign('dates', array_reverse($dates));
$smarty->assign('bjtype_mapping', $bjtype_mapping);


$smarty->display('oukooext/analyze_comment_satisfied.htm');
