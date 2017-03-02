<?php die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
/**
 * 积分统计
 * 
 * @author yxiang@oukoo.com 
 * @copyright ouku.com 
 */
define('IN_ECS', true);

require_once('includes/init.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
party_priv(PARTY_OUKU);
admin_priv('analyze_point');
set_time_limit(300);


// 期初时间
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start'])
    ? $_REQUEST['start']
    : ( date('w') == 1 ? date('Y-m-d') : date('Y-m-d', strtotime('-'.(date('N') - 1).' day')) ) ;   // 默认本周一

// 期末时间
$end  = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end'])
    ? $_REQUEST['end']
    : date('Y-m-d');
$filter = array('start' => $start, 'end' => $end);

    
// 取得积分来源
$point_source_list = (array)$slave_db->getAll("SELECT source_id, source_name, source_code FROM membership.ps_point_source");
$point_source = Helper_Array::toHashmap($point_source_list, 'source_id', 'source_name');

/**
 * 按照不同的积分来源，取得不同的对比数据
 */
$point_stat = array();
foreach ($point_source_list as $source) {
    if ($source['source_code'] == 'ORDER_COMMENT') {
        // 查询订单总数
        $total = $slave_db->getOne("SELECT COUNT(order_id) FROM ecs_order_info WHERE order_status = 1 AND order_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND ". party_sql('party_id'));
        $point_stat['add'][$source['source_id']]['total_order'] = $total; 
    }
    else if ($source['source_code'] == 'GOODS_COMMENT_EXCELLENT') {
        // 商品数量
        $total = $slave_db->getOne("
            SELECT COUNT(og.goods_id) FROM ecs_order_info as o 
                LEFT JOIN ecs_order_goods as og ON og.order_id = o.order_id
                LEFT JOIN ecs_goods as g ON g.goods_id = og.goods_id
            WHERE o.order_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND og.goods_id
                AND g.top_cat_id != 1367  -- 排除礼品
                AND ". party_sql('o.party_id') ."
        ");
        $point_stat['add'][$source['source_id']]['total_goods'] = $total;
    }
    else if ($source['source_code'] == 'EMAIL_VALIDATE') {
        // 注册的用户数
        $total = $slave_db->getOne("
           SELECT COUNT(user_id) FROM ecs_users WHERE reg_time > 0 AND ( reg_time BETWEEN UNIX_TIMESTAMP('{$start}') AND UNIX_TIMESTAMP(DATE_ADD('{$end}', INTERVAL 1 DAY)) )
        ");
        $point_stat['add'][$source['source_id']]['total_register_user'] = $total;
        // 注册并通过邮件验证的
        $total = $slave_db->getOne("
            SELECT COUNT(user_id) FROM ecs_users WHERE reg_time > 0 AND ( reg_time BETWEEN UNIX_TIMESTAMP('{$start}') AND UNIX_TIMESTAMP(DATE_ADD('{$end}', INTERVAL 1 DAY)) ) AND email_valid = 1
        ");
        $point_stat['add'][$source['source_id']]['total_register_emailvalid_user'] = $total;
    }
    else if ($source['source_code'] == 'DAILY_LOGIN') {
        // 登录的用户数
        $total = $slave_db->getOne("
           SELECT COUNT( DISTINCT user_id) FROM ecs_users WHERE last_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) 
        ");
        $point_stat['add'][$source['source_id']]['total_login_user'] = $total;
    }
}


/** 
 * 积分生成情况 
 */

// 取得按来源分组的积分总记录
$sql = "
    SELECT SUM(point_on_hand) as total, source_id 
    FROM membership.ps_point
    WHERE create_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND " . party_sql('party_id') ." 
    GROUP BY source_id
";
$ref_fields = $ref_rowset = array();
$slave_db->getAllRefby($sql, array('source_id'), $ref_fields, $ref_rowset, false);
if (!empty($ref_rowset)) {
    foreach ($ref_rowset['source_id'] as $source_id => $group) {
        $point_stat['add'][$source_id]['total_point'] = $group[0]['total'];
    }
}


// 取得参与生成积分的用户总数
$sql = "
    SELECT COUNT(DISTINCT user_id) as total, source_id 
    FROM membership.ps_point 
    WHERE create_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND " . party_sql('party_id') ."
    GROUP BY source_id
";
$ref_fields = $ref_rowset = array();
$slave_db->getAllRefby($sql, array('source_id'), $ref_fields, $ref_rowset, false);
if (!empty($ref_rowset)) {
    foreach ($ref_rowset['source_id'] as $source_id => $group) {
        $point_stat['add'][$source_id]['total_user'] = $group[0]['total'];
    }
}


/**
 * 积分兑换情况 
 */

// 消耗积分
$sql = "
	SELECT SUM(exchange_point) 
	FROM membership.ps_point_exchange 
	WHERE exchange_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND " . party_sql('party_id') ."
";
$point_stat['exchange']['total_point'] = $slave_db->getOne($sql);

// 兑换红包数
$sql = "
	SELECT COUNT(gt_id)
	FROM membership.ok_gift_ticket
	WHERE gt_ctime BETWEEN UNIX_TIMESTAMP('{$start}') AND UNIX_TIMESTAMP(DATE_ADD('{$end}', INTERVAL 1 DAY)) AND 
		". party_sql('party_id') ." AND gtc_type_id = 'POINT_EXCHANGE'
";
$point_stat['exchange']['total_bonus_number'] = $slave_db->getOne($sql);

// 兑换红包的总金额
$sql = "
	SELECT SUM(gtc_value)
	FROM membership.ok_gift_ticket
	WHERE gt_ctime BETWEEN UNIX_TIMESTAMP('{$start}') AND UNIX_TIMESTAMP(DATE_ADD('{$end}', INTERVAL 1 DAY)) AND 
		". party_sql('party_id') ." AND gtc_type_id = 'POINT_EXCHANGE'
";
$point_stat['exchange']['total_bonus_value'] = $slave_db->getOne($sql);



/**
 * 积分兑换的红包使用情况
 */

// 订单使用数
$sql = "
	SELECT COUNT(o.order_id)
	FROM 
	    membership.ok_gift_ticket AS t
	    INNER JOIN ecshop.ecs_order_info AS o ON o.bonus_id = t.gt_code
	WHERE 
        t.gt_ctime BETWEEN UNIX_TIMESTAMP('{$start}') AND UNIX_TIMESTAMP(DATE_ADD('{$end}', INTERVAL 1 DAY)) AND 
		t.gtc_type_id = 'POINT_EXCHANGE' AND ". party_sql('t.party_id') ." AND
		o.order_status = 1 
";
$point_stat['used']['total_order_number'] = $slave_db->getOne($sql);

	
$smarty->assign('filter', $filter);
$smarty->assign('point_source', $point_source);
$smarty->assign('point_stat', $point_stat);
$smarty->display('oukooext/analyze_point.htm');

?>
