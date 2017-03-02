<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");

/**
 * 抵用券统计
 * 
 * @author yxiang@oukoo.com 
 * @copyright ouku.com 
 */
define('IN_ECS', true);

require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/includes/lib_bonus.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
party_priv(PARTY_OUKU);
admin_priv('analyze_gift_ticket');
set_time_limit(300);


// 抵用券配置类型列表
$gtc_type_list = bonus_type_list();
// 抵用券配置状态列表
$gtc_state_list = $GLOBALS['_CFG']['ms']['gtc_state'];


// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
// 开始时间
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start']) 
    ? $_REQUEST['start'] 
    : date('Y-m-d', strtotime('-' . (date('N')-1) .' day')) ;  // 默认本周一
// 结束时间
$end = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end']) 
    ? $_REQUEST['end'] 
    : date('Y-m-d') ;
// 抵用券配置类型
$gtc_type_id = 
    isset($_REQUEST['gtc_type_id']) && array_key_exists($_REQUEST['gtc_type_id'], $gtc_type_list)
    ? $_REQUEST['gtc_type_id']
    : null ; 
// 抵用券配置状态
$gtc_state_id = 
    isset($_REQUEST['gtc_state_id']) && array_key_exists($_REQUEST['gtc_state_id'], $gtc_state_list)
    ? $_REQUEST['gtc_state_id']
    : null ;
     
// 过滤条件
$filter = array('start' => $start, 'end' => $end, 'gtc_type_id' => $gtc_type_id, 'gtc_state_id' => $gtc_state_id);


$cond = "(gtc_state = 3 OR (gtc_state =2 AND gtc_stime >= UNIX_TIMESTAMP('{$start}') AND gtc_stime <= UNIX_TIMESTAMP('{$end}')))";
if (!is_null($gtc_type_id)) { $cond .= " AND gtc_type_id = '{$gtc_type_id}'"; }
/*
if (!is_null($gtc_state_id)) { $cond .= " AND gtc_state = '{$gtc_state_id}'"; }
*/

// 总数
$sql = "SELECT COUNT(gtc_id) FROM membership.ok_gift_ticket_config WHERE %s";
$total = $slave_db->getOne(sprintf($sql, $cond));

// 构造分页
$page_size = 15;
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size; 

// 查询抵用券配置
$sql = "SELECT gtc_id, gtc_value, gtc_state, gtc_stime, gtc_etime, gtc_type_id FROM membership.ok_gift_ticket_config WHERE %s LIMIT {$offset}, {$limit}";
$ref_gtc_fields = $ref_gtc_rowset = array();
$gtc_list = $slave_db->getAllRefby(sprintf($sql, $cond), array('gtc_id'), $ref_gtc_fields, $ref_gtc_rowset, false);


if (!empty($gtc_list)) {
	// 查询抵用券配置下对应的抵用券
	$sql = "SELECT gt_id, gtc_id, gt_code, gt_state, user_id, refer_id, used_timestamp, used_user_id, used_order_id FROM membership.ok_gift_ticket WHERE gtc_id " . db_create_in($ref_gtc_fields['gtc_id']);
	$ref_gt_fields = $ref_gt_rowset = $order_id_array = array();
	$gt_list = $slave_db->getAllRefby($sql, array('gtc_id'), $ref_gt_fields, $ref_gt_rowset, false);
	foreach ($ref_gt_rowset['gtc_id'] as $gtc_id => $group) {
		$ref_gtc_rowset['gtc_id'][$gtc_id][0]['gt_count'] = count($group);
		$ref_gtc_rowset['gtc_id'][$gtc_id][0]['gt_used_count'] = 0;
		foreach ($group as $gt) {
			if ($gt['gt_state'] == 4) {
				$ref_gtc_rowset['gtc_id'][$gtc_id][0]['gt_used_count']++;
				$order_id_array[] = $gt['used_order_id'];
			}
		}
	}
}

if (!empty($order_id_array)) {
	// 查询订单使用红包的情况
	$sql = "
	    SELECT
	        o.order_id, o.order_time, o.order_amount, o.goods_amount, o.order_sn, 
	        gt.gt_code, gtc_value, gt_state, gtc_stime, gtc_etime
	    FROM
	        ecshop.ecs_order_info as o
	        LEFT JOIN membership.ok_gift_ticket AS gt ON gt.gt_code = o.bonus_id
	    WHERE o.order_id ". db_create_in($order_id_array);
	$orders = $slave_db->getAll($sql);
}

// 构造分页
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url = 'analyze_gt.php', null, $filter
);


$smarty->assign('orders', $orders);
$smarty->assign('pagination', $pagination->get_simple_output());
$smarty->assign('gtc_list', $gtc_list);
$smarty->assign('gtc_state_list', $gtc_state_list);
$smarty->assign('gtc_type_list', $gtc_type_list);
$smarty->assign('filter', $filter);
$smarty->display('oukooext/analyze_gt.htm');

