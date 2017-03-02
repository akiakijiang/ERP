<?php
/**
 * 留言统计
 * 
 * @author yxiang@oukoo.com 05/06/2009 
 * @author last modified by yxiang@oukoo.con 05/06/2010
 * 
 * 该页面用来统计2010年五一期间的数据。
 * 五一期间的 工作时间为 10：00-19：00 
 */
define('IN_ECS', true);

require_once('includes/init.php');
require_once('function.php');
party_priv(PARTY_OUKU);
admin_priv('analyze_comment');
set_time_limit(300);

// 期初时间
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start']) ? 
    $_REQUEST['start'] : date("Y-m-d");

// 期末时间
if (isset($_REQUEST['end']) && strtotime($_REQUEST['end']) !== false)
{
    $original_end = $_REQUEST['end'];
    $end = date('Y-m-d', strtotime('+1 day', strtotime($_REQUEST['end'])));  
}
else
{
    $original_end = date('Y-m-d');
    $end = date('Y-m-d', strtotime('+1 day'));   
}

// 回复人
$repliedby = 
    isset($_REQUEST['repliedby']) && !empty($_REQUEST['repliedby']) ? 
    $_REQUEST['repliedby'] : '';

$filter = array('start' => $start, 'end' => $original_end, 'repliedby' => $repliedby);

// 周末工作时间为 10:00 - 19:00
$worktime_mapping[0] = array('10', '11', '12', '13', '14', '15', '16', '17', '18');
$worktime_start[0] = 10;
$worktime_end[0] = 18;

// 周一到周五工作时间为 10:00 - 19:00
$worktime_mapping[1] = array('10', '11', '12', '13', '14', '15', '16', '17', '18');
$worktime_start[1] = 10;
$worktime_end[1] = 18;


// 时间段和时间的mapping，用于显示
$time_mapping = array
(
	'0'  => '00:00 - 01:00',
	'1'  => '01:00 - 02:00',
	'2'  => '02:00 - 03:00',
	'3'  => '03:00 - 04:00',
	'4'  => '04:00 - 05:00',
	'5'  => '05:00 - 06:00',
	'6'  => '06:00 - 07:00',
	'7'  => '07:00 - 08:00',
	'8'  => '08:00 - 09:00',
	'9'  => '09:00 - 10:00',
	'10' => '10:00 - 11:00',
	'11' => '11:00 - 12:00',
	'12' => '12:00 - 13:00',
	'13' => '13:00 - 14:00',
	'14' => '14:00 - 15:00',
	'15' => '15:00 - 16:00',
	'16' => '16:00 - 17:00',
	'17' => '17:00 - 18:00',
	'18' => '18:00 - 19:00',
	'19' => '19:00 - 20:00',
	'20' => '20:00 - 21:00',
	'21' => '21:00 - 22:00',
	'22' => '22:00 - 23:00',
	'23' => '23:00 - 24:00'
);


/*
 * 售前留言
 * @{+ 
 */
// 售前留言类型
$comment_types = array
(
	'goods'    => '商品咨询',
	'shipping' => '物流配送',
	'payment'  => '支付问题',
	'postsale' => '保修及发票',
	'function' => '使用功能'
);
// 留言回复统计
$before_comment = getBeforeOrderCommentStatData($start, $end, $repliedby);
$smarty->assign('comment_types',     $comment_types);
$smarty->assign('comment_total',     $before_comment['total']);  // 总回复数
$smarty->assign('comment_time',      $before_comment['time']);   // 总回复时间
$smarty->assign('comment_avg',       $before_comment['avg']);    // 总平均回复时间
$smarty->assign('comment_stat',      $before_comment['item']);   // 各售后留言类型的统计信息
$smarty->assign('comment_timeslice', $before_comment['timeslice_stat']); // 时间段的统计信息
/*
 * 售前留言
 * -}@
 */


/*
 * 售中留言统计
 * @{+
 */
$order_comment_types = $_CFG['adminvars']['comment_cat']; // 售中留言类型
unset($order_comment_types['4']);  // 不统计订单确认的
$order_comment = getOrderCommentStatData($start, $end, $repliedby);
$smarty->assign('order_comment_types',     $order_comment_types);
$smarty->assign('order_comment_total',     $order_comment['total']);  // 总回复数
$smarty->assign('order_comment_time',      $order_comment['time']);   // 总回复时间
$smarty->assign('order_comment_avg',       $order_comment['avg']);    // 总平均回复时间
$smarty->assign('order_comment_stat',      $order_comment['item']);   // 各售后留言类型的统计信息
$smarty->assign('order_comment_timeslice', $order_comment['timeslice_stat']); // 时间段的统计信息
/*
 * 售中留言统计
 * -}@
 */

/*
 * 售后留言统计部分
 * @+{
 */
$service_types = $_CFG['adminvars']['service_type_mapping']; // 售后留言类型
$service_types['consultation'] = '售后咨询';
$service_types['appraise'] = '售后评价';
$service_comment = getAfterOrderCommentStatData($start, $end, $repliedby);
$smarty->assign('service_types',     $service_types);
$smarty->assign('service_total',     $service_comment['total']);  // 总回复数
$smarty->assign('service_time',      $service_comment['time']);   // 总回复时间
$smarty->assign('service_avg',       $service_comment['avg']);    // 总平均回复时间
$smarty->assign('service_stat',      $service_comment['item']);   // 各售后留言类型的统计信息
$smarty->assign('service_timeslice', $service_comment['timeslice_stat']); // 时间段的统计信息
/*
 * 售后留言部分
 * }-@
 */

/*
 * 售后订单
 * @{+ 
 */
$service_order = getAfterOrderStatData($start, $end, $_CFG['adminvars']['service_type_mapping'], $repliedby);
$smarty->assign('service_order_types',     $service_types);
$smarty->assign('service_order_total',     $service_order['order']);  // 总订单处理数
$smarty->assign('service_order_stat',      $service_order['item']);   // 各售后留言类型的订单处理统计信息
$smarty->assign('service_order_timeslice', $service_order['timeslice_stat']); // 时间段的统计信息
/*
 * 售后订单
 * -}@ 
 */
 
// 留言转换率统计
$comment_to_order = getCommentToOrderRateData($start, $end, $before_comment['total'], $repliedby);
$smarty->assign('c_to_o_stat', $comment_to_order); // 留言转换率

// 确认订单时间统计
$order_confirm_time_stat = getOrderConfirmAvgTime($start, $end, $repliedby);
$smarty->assign('order_confirm_time_stat', $order_confirm_time_stat); 



/* 导出统计数据 */
if (isset($_REQUEST['act']) && $_REQUEST['act'] == '导出')
{
	$filename = "从{$start}到{$original_end}的留言统计数据";
	if ($repliedby) { $filename .= "($repliedby)"; }
	$filename .= '.xlsx';
	
	set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
	require 'PHPExcel.php';
	require 'PHPExcel/IOFactory.php';
	
	$excel = new PHPExcel();
	
	// 设置属性
	$excel->getProperties()->setTitle($title);
	$excel->getProperties()->setSubject($title);
	
	$sheet = $excel->getActiveSheet();
	$sheet->getColumnDimension('B')->setWidth(50); 
	//$sheet->getStyle('A1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
	$sheet->setCellValue('A1', "留言回复情况统计[{$start}到{$original_end}]");
	$sheet->mergeCells('A1:C1');
	$sheet->getStyle("A1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	$sheet->getStyle("A1")->getFont()->setBold(true); 
	
	// 数据
	// 售前
	$i = 2;
	$sheet->setCellValue("A{$i}", '售前');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);  
	foreach ($comment_types as $k=>$v)
	{
		$sheet->setCellValue("B{$i}", $v);
		$sheet->setCellValue("C{$i}", $before_comment['item'][$k]['total']);
		$i++;	
	}	
	$sheet->setCellValue("B{$i}", '售前留言数量小计');
	$sheet->getStyle("B{$i}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
	$sheet->setCellValue("C{$i}", $before_comment['total']);
	$i++;
	
	$sheet->setCellValue("B{$i}", '留言转化率');
	$sheet->setCellValue("C{$i}", $comment_to_order['rate']);
	$i++;
	
	$sheet->setCellValue("B{$i}", '平均回复时间（分钟）');
	$sheet->setCellValue("C{$i}", $before_comment['avg']);
	$sheet->mergeCells("A2:A{$i}");
	$i++;
	
	// 售中
	$flag = $i;
	$sheet->setCellValue("A{$i}", '售中');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); 
	foreach ($order_comment_types as $k=>$v)
	{
		$sheet->setCellValue("B{$i}", $v);
		$sheet->setCellValue("C{$i}", $order_comment['item'][$k]['total']);
		$i++;
	}
	$sheet->setCellValue("B{$i}", '售中留言数量小计');
	$sheet->getStyle("B{$i}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
	$sheet->setCellValue("C{$i}", $order_comment['total']);
	$i++;
	
	$sheet->setCellValue("B{$i}", '平均回复时间（分钟）');
	$sheet->setCellValue("C{$i}", $order_comment['avg']);
	$i++;

	$sheet->setCellValue("B{$i}", '确认订单平均时间（分钟）');
	$sheet->setCellValue("C{$i}", $order_confirm_time_stat['rate']);
	$sheet->mergeCells("A{$flag}:A{$i}");
	$i++;
	
	// 售后
	$flag = $i;
	$sheet->setCellValue("A{$i}", '售后');
	$sheet->getStyle("A{$i}")->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
	foreach ($service_types as $k=>$v)
	{
		$sheet->setCellValue("B{$i}", $v);
		$sheet->setCellValue("C{$i}", $service_comment['item'][$k]['total']);
		$i++;	
	}
	$sheet->setCellValue("B{$i}", '售后留言数量小计');
	$sheet->getStyle("B{$i}")->getFont()->getColor()->setARGB(PHPExcel_Style_Color::COLOR_RED);
	$sheet->setCellValue("C{$i}", $service_comment['total']);
	$i++;
	
	$sheet->setCellValue("B{$i}", '平均回复时间（分钟）');
	$sheet->setCellValue("C{$i}", $service_comment['avg']);
	$sheet->mergeCells("A{$flag}:A{$i}");
	
	// 输出
	if (!headers_sent())
	{
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header('Cache-Control: max-age=0');
		$output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
		$output->setOffice2003Compatibility(true);
		$output->save('php://output');
	}
}
/* 导出留言转换率详情 */
else if (isset($_REQUEST['act']) && $_REQUEST['act'] == '导出留言转换率详情') {
    $filename = "从{$start}到{$original_end}的留言转换率数据";
    if ($repliedby) { $filename .= "($repliedby)"; }
    $filename .= '.xlsx';
    
    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    
    $excel = new PHPExcel();
    
    // 设置属性
    $excel->getProperties()->setTitle($title);
    $excel->getProperties()->setSubject($title);
    
    $sheet = $excel->getActiveSheet();
    
    $sheet->setCellValue('A1', '留言回复ID');
    $sheet->setCellValue('B1', '回复时间');
    $sheet->setCellValue('C1', '订单号');
    $sheet->setCellValue('D1', '订单类型');
    $sheet->setCellValue('E1', '订单金额');
    $sheet->setCellValue('F1', '订单状态');
    $sheet->setCellValue('G1', '商品');
    $sheet->setCellValue('H1', '数量');
    $sheet->setCellValue('I1', '金额');
    
    // 数据
    $i = 2;  
    foreach ($comment_to_order['orders'] as $order)
    {
        foreach ($order['goods'] as $goods) {
            $sheet->setCellValue("A{$i}", $order['replied_nick']);
            $sheet->setCellValue("B{$i}", $order['replied_point']);
            $sheet->setCellValue("C{$i}", $order['order_sn']);
            $sheet->setCellValue("D{$i}", $order['category']);
            $sheet->setCellValue("E{$i}", $order['order_amount']);
            $sheet->setCellValue("F{$i}", get_order_status($order['order_status']) .'，'. get_pay_status($order['pay_status']) . '，' . get_shipping_status($order['shipping_status'])) ;
            $sheet->setCellValue("G{$i}", $goods['goods_name']);
            $sheet->setCellValue("H{$i}", $goods['goods_number']);
            $sheet->setCellValue("I{$i}", $goods['total_price']);
            
            $i++;
        }
    }
    
    // 输出
    if (!headers_sent())
    {
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        header('Cache-Control: max-age=0');
        $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
        $output->setOffice2003Compatibility(true);
        $output->save('php://output');
    }
}
/* 显示 */
else
{
    $smarty->assign('filter', $filter);  // 查询条件
	$smarty->assign('repliers', getReplierList());  // 回复人下拉列表
	$smarty->display('oukooext/analyze_commentV2.htm');
}

/**
 * 取得售前留言统计数据
 * 
 * @param string $start 查询限制开始时间
 * @param string $end   查询限制结束时间
 * @param string $repliedby 查询限制回复人
 * 
 * @return array
 */
function getBeforeOrderCommentStatData($start, $end, $repliedby = null)
{
	global $slave_db;
	
	$timeslice_stat = array();	// 按时间段统计的结果	
	$item  = array();           // 每个留言类型的统计结果
	$total = 0;                 // 留言总数
	$time  = 0;                 // 总回复时间
	$avg   = 0;                 // 平均回复时间 

	if ($repliedby && $userId = getUserIdByNickname($repliedby)) { $condition = "AND `replied_by` = '{$userId}'"; }	
	$sql = "
        SELECT 
            `post_datetime`, `replied_point`, `type`, 
            HOUR(`replied_point`) AS timeslice, WEEKDAY(`replied_point`) AS w
        FROM `bj_comment` 
        WHERE 
            (`replied_point` IS NOT NULL AND `replied_point` != 0 AND `status` = 'OK' AND `store_id` = 0) 
            AND (`replied_point` BETWEEN '{$start}' AND '{$end}') AND ". party_sql('party_id') ." {$condition}
	";
	if ($result = $slave_db->query($sql))
	{
		_fetch_all_ref_by($result, $item, $timeslice_stat, $total, $time, $avg, true);
	}
	
	return compact('total', 'time', 'avg', 'item', 'timeslice_stat');
}

/**
 * 获取售前订单转换率数据
 * 
 * @param string $start 起始时间
 * @param string $end   终止时间
 * @param int    $comment_total  售前回复数量
 * 
 * @return array
 */
function getCommentToOrderRateData($start, $end, $comment_total, $repliedby = null)
{
	global $slave_db, $ecs;
	
	if ($comment_total <= 0)
	{
		return ;
	}	
	
	// 留言转换率
	// 即在留言被回复后的24小时内下的销售订单
	// 订单要被确认过
	// 留言的状态status不包括 'DELETED' 和 'REJECTED'
	if ($repliedby && $userId = getUserIdByNickname($repliedby)) { $condition = "AND c.`replied_by` = '{$userId}' "; }
	$sql = "
        SELECT 
            o.`order_id`, o.`order_sn`, o.`order_amount`,
            o.`order_status`, o.`pay_status`, o.`shipping_status`, 
            c.`replied_nick`, c.`replied_point`, UNIX_TIMESTAMP(c.`replied_point`) AS sort 
        FROM 
            {$ecs->table('order_info')} o 
            INNER JOIN {$ecs->table('users')} AS u ON u.`user_id` = o.`user_id` 
            INNER JOIN `bj_comment` AS c ON c.`user_id` = u.`userId` 
                AND (o.`order_time` BETWEEN c.`replied_point` AND DATE_ADD(c.`replied_point`, INTERVAL 1 DAY))  
        WHERE 
            o.order_time >= '{$start}' and o.order_time  <= DATE_ADD('{$end}', INTERVAL 1 DAY) 
            AND o.order_type_id = 'SALE' AND o.`order_status` = 1 AND o.special_type_id <> 'PRESELL' AND ". party_sql('o.party_id') ."
            AND (c.`replied_point` IS NOT NULL AND c.`replied_point` != 0 AND c.`status` = 'OK' AND c.`store_id` = 0) 
            AND (c.`replied_point` BETWEEN '{$start}' AND '{$end}') {$condition}
        GROUP BY
            o.order_id, c.replied_nick
	";
    $ref_field = $ref_rowset = array();
    $result = $slave_db->getAllRefby($sql, array('order_id'), $ref_field, $ref_rowset, false);
    $orders = array();

    // 取得每个订单的商品
	if ($result) {
        $sql = "
            SELECT 
                og.order_id, og.goods_name, og.goods_number, 
                g.top_cat_id, g.cat_id, og.goods_number * og.goods_price as total_price,
                func_get_goods_category_detail(g.top_cat_id, g.cat_id, g.goods_id, 'Y') AS category
            FROM 
                {$ecs->table('order_goods')} AS og
                LEFT JOIN {$ecs->table('goods')} g ON og.goods_id = g.goods_id
            WHERE 
                og.order_id " . db_create_in($ref_field['order_id']);
        $tmp = $order_goods = array();
        $slave_db->getAllRefby($sql, array('order_id'), $tmp, $order_goods, false);
        
        // 因为一个订单的留言可能有多个留言，所以只需要列出最先被回复的那条留言记录就好了
        foreach ($ref_rowset['order_id'] as $order_id => & $group) {
            // 取得最先回复的那条记录
            $len = count($group);
            if ($len == 1) {
                $order = & $group[0];
            } else {
                $t = $group[0]['sort'];
                $m = 0;
                for ($i = 1; $i < $len; $i++) {
                    if ($group[i]['sort'] < $t) {
                        $t = $group[i]['sort'];
                        $m = $i; 
                    }
                }
                $order = & $group[$m];
            }
            
            // 取得订单的商品和订单类型
            if (isset($order_goods['order_id'][$order_id])) {
                $goods_list = & $order_goods['order_id'][$order_id];
                $order['goods']    = $goods_list; 
                $order['category'] = $goods_list[0]['category']; 
            }
            
            $orders[] = $order;
        }
	}
	
	$count = count($orders);
	$rate = $count > 0 ? sprintf("%.2f", $count / $comment_total * 100) .'%' : '无订单数据';
	
	return compact('orders', 'count', 'rate');	
}

/**
 * 取得售中留言统计数据
 * 
 * @param string $start 查询限制开始时间
 * @param string $end   查询限制结束时间
 * @param string $repliedby 限制回复人
 * 
 * @return array
 */
function getOrderCommentStatData($start, $end, $repliedby = null)
{
	global $slave_db, $ecs;
	
	$timeslice_stat = array();  // 按时间段统计的结果
	$item  = array();           // 每个留言类型的统计结果
	$total = 0;                 // 留言总数
	$time  = 0;                 // 总回复时间
	$avg   = 0;                 // 总平均时间
	
	if ($repliedby) { $condition = "AND c.`replied_by` = '{$repliedby}'"; }
	$sql = "
        SELECT 
            c.`post_datetime`, c.`reply_point` AS replied_point, c.`comment_cat` AS type, 
            HOUR(c.`reply_point`) AS timeslice, WEEKDAY(c.`reply_point`) AS w
        FROM 
            {$ecs->table('order_comment')} AS c
            LEFT JOIN {$ecs->table('order_info')} AS o ON o.order_id = c.order_id 
        WHERE
            (c.`reply_point` IS NOT NULL AND c.`reply_point` != 0) AND ". party_sql('o.party_id') ." {$condition} 
            AND (c.`reply_point` BETWEEN '{$start}' AND '{$end}') AND c.comment_cat <> 4
    ";
	if ($result = $slave_db->query($sql))
	{
		_fetch_all_ref_by($result, $item, $timeslice_stat, $total, $time, $avg, true);
	}	
	
	return compact('total', 'time', 'avg', 'item', 'timeslice_stat');
}

/**
 * 取得售后留言统计数据(包括处理的订单)
 * 
 * @param string $start 查询限制开始时间
 * @param string $end   查询限制结束时间
 * @param array  $types 售后留言类型
 * @param string $repliedby 限制回复人
 * 
 * @return array
 */
function getAfterOrderCommentStatData($start, $end, $repliedby = null)
{
	/* 由于售后评价和售后咨询也属于售后留言，所以需要组合这三个表的数据 */

	global $slave_db, $ecs;
	
	$timeslice_stat = array();	// 按时间段统计的结果
	$item  = array();           // 每个留言类型的统计结果
	$total = 0;                 // 总数
	$time  = 0;                 // 总回复时间
	$avg   = 0;

	/*
	 * 查询出售后留言
	 */
	if ($repliedby) { $condition1 = "AND s.`review_username` = '{$repliedby}'"; }
	$sql = "
        SELECT 
            s.`apply_datetime` AS post_datetime, s.`service_type` AS type, s.`review_point` AS replied_point, 
            HOUR(s.`review_point`) AS timeslice, WEEKDAY(s.`review_point`) AS w
        FROM
            `service` AS s
            LEFT JOIN {$ecs->table('order_info')} AS o ON o.order_id = s.order_id
        WHERE
            (s.`review_point` IS NOT NULL AND s.`review_point` != 0) AND ". party_sql('o.party_id') ." {$condition1} 
            AND (s.`review_point` BETWEEN '{$start}' AND '{$end}') 
    ";
	if ($result = $slave_db->query($sql))
	{
		_fetch_all_ref_by($result, $item, $timeslice_stat, $total, $time, $avg, false);	
	}

	/*
	 * 查询出售后咨询留言回复
	 */
	if ($repliedby && $username = getUsernameByNickname($repliedby)) { $condition2 = "AND s.`replied_username` = '{$username}'"; }
	$sql = "
        SELECT 
            s.`post_datetime`, 'consultation' AS type, s.`replied_point`, 
            HOUR(s.`replied_point`) AS timeslice, WEEKDAY(s.`replied_point`) AS w
        FROM
            `service_comment` AS s
            LEFT JOIN {$ecs->table('order_info')} AS o ON o.order_id = s.order_id
        WHERE 
            (s.`replied_point` IS NOT NULL AND s.`replied_point` != 0) AND " . party_sql('o.party_id') ." {$condition2} 
            AND (s.`replied_point` BETWEEN '{$start}' AND '{$end}')
    ";
	if ($result = $slave_db->query($sql))
	{
		_fetch_all_ref_by($result, $item, $timeslice_stat, $total, $time, $avg, false);	
	}	
		
	/*
	 * 查询出售后评价留言回复
	 */
	if ($repliedby && $userId = getUserIdByNickname($repliedby)) { $condition3 = "AND r.`user_id` = '{$userId}'"; }
	$sql = "
        SELECT
            c.`post_time` AS post_datetime, 'appraise' AS type, r.`created` AS replied_point, 
            HOUR(r.`created`) AS timeslice, WEEKDAY(r.`created`) AS w
        FROM 
            {$ecs->table('after_order_comment')} AS r
            LEFT JOIN {$ecs->table('after_order_comment')} AS c ON c.`comment_id` = r.`reply_to`
            LEFT JOIN {$ecs->table('order_info')} AS o ON o.order_id = r.order_id
        WHERE 
            c.`comment_id` = r.`reply_to` AND (r.`user_type` = 2 AND r.`reply_to` != 0 AND r.`post_time` != 0) 
            AND (r.`created` BETWEEN '{$start}' AND '{$end}') AND " . party_sql('o.party_id') . " {$condition3}		
	"; // r = 回复  c = 评论 
	if ($result = $slave_db->query($sql))
	{
		_fetch_all_ref_by($result, $item, $timeslice_stat, $total, $time, $avg, true);	
	}

	// 总回复时间和平均回复时间不计入售后评价
	$time = $time - $item['appraise']['time_total'];
	$avg  =	@round($time/($total - $item['appraise']['total']));
	
	return compact('total', 'time', 'avg', 'item', 'timeslice_stat');
}

/**
 * 取得售后订单处理情况统计
 * 
 * @param string $start 查询限制开始时间
 * @param string $end   查询限制结束时间
 * @param array  $types 售后留言类型
 * @param string $repliedby 限制回复人
 * 
 * @return array
 */
function getAfterOrderStatData($start, $end, $types, $repliedby = null)
{
	/* 由于售后评价和售后咨询也属于售后留言，所以需要组合这三个表的数据 */

	global $time_mapping, $worktime_mapping;
	
	$timeslice_stat = array();
	$item  = array();           // 每个留言类型的统计结果
	$order = 0;                 // 总订单处理数
	
	/*
	 * 查询出【已回复】的售后留言
	 */
	foreach ($types as $tid => $tname)
	{
		$item[$tid] = array
		( 
			'order_total' => 0, 'order_worktime' => 0, 'order_outworktime' => 0
		);
		// 按时间段分组查询，得出该段时间内的回复总数，回复时间总数
		if ($repliedby) { $condition1 = "AND s.`review_username` = '{$repliedby}'"; }
		$sql = "
            SELECT 
                COUNT(DISTINCT s.`order_id`) AS order_num, 
                HOUR(s.`review_point`) AS timeslice, WEEKDAY(s.`review_point`) AS w
            FROM 
                `service` AS s
                LEFT JOIN {$GLOBALS['ecs']->table('order_info')} AS o ON o.order_id = s.order_id
            WHERE 
                s.`service_type` = '{$tid}' {$condition1}
                AND (s.`review_point` IS NOT NULL AND s.`review_point` != 0) 
                AND (s.`review_point` BETWEEN '{$start}' AND '{$end}') 
                AND " . party_sql('o.party_id') . "
            GROUP BY timeslice
		";
		$result = $GLOBALS['db']->query($sql);
	    if ($result !== false)
	    {
	        $rows = array();
	        while ($row = $GLOBALS['db']->fetchRow($result))
	        {				
				if ($row['w'] == 5 || $row['w'] == 6)  
				{
					// 周末的工作时间
					$worktime = $worktime_mapping[0];	
				}
				else
				{
					// 工作日的工作时间
					$worktime = $worktime_mapping[1];
				}
	        
	        	if (in_array($row['timeslice'], $worktime))
	        	{
	        		$item[$tid]['order_worktime']  += $row['order_num'];    // 属于该留言类型的工作时间订单处理数递加
	        	}
	        	else
	        	{
					$item[$tid]['order_outworktime']  += $row['order_num'];  // 属于该留言类型的非工作时间订单处理数递加
	        	}
	        	$item[$tid]['order_total'] += $row['order_num'];  // 属于该留言类型的订单处理数递加
	        	$timeslice_stat[$time_mapping[$row['timeslice']]][$tid] = $row;    // 按时间段的统计
	        	$order += $row['order_num'];                // 订单处理数总数递加
	        }
	        $GLOBALS['db']->free_result($result);
	    }
	}

	/*
	 * 查询出售后咨询留言回复
	 */
	$item['consultation'] = array
	(
  		'order_total' => 0, 'order_worktime' => 0, 'order_outworktime' => 0
  	);
	// 按时间段分组查询，得出该段时间内的售后咨询总数，回复时间总数
	if ($repliedby && $username = getUsernameByNickname($repliedby))
	{
		$condition2 = "AND s.`replied_username` = '{$username}'";
	}	 
	$sql = "
        SELECT 
            COUNT(DISTINCT s.`order_id`) AS order_num, 
            HOUR(s.`replied_point`) AS timeslice, WEEKDAY(s.`replied_point`) AS w
        FROM
            `service_comment` AS s
            LEFT JOIN {$GLOBALS['ecs']->table('order_info')} AS o ON o.order_id = s.order_id 
        WHERE 
            (s.`replied_point` IS NOT NULL AND s.`replied_point` != 0) {$condition2} 
            AND (s.`replied_point` BETWEEN '{$start}' AND '{$end}')
            AND " . party_sql('o.party_id') . " 
        GROUP BY timeslice
	";
	$result = $GLOBALS['db']->query($sql);
	if ($result !== false)
	{
		$rows = array();
		while ($row = $GLOBALS['db']->fetchRow($result))
		{
			if ($row['w'] == 5 || $row['w'] == 6)  
			{
				// 周末的工作时间
				$worktime = $worktime_mapping[0];
			}
			else
			{
				// 工作日的工作时间
				$worktime = $worktime_mapping[1];
			}
							
        	if (in_array($row['timeslice'], $worktime))
        	{
        		$item['consultation']['order_worktime']  += $row['order_num'];  // 属于该留言类型的工作时间订单处理数递加
        	}
        	else
        	{
        		$item['consultation']['order_outworktime']  += $row['order_num'];  // 属于该留言类型的非工作时间订单处理数递加
        	}
        	$item['consultation']['order_total'] += $row['order_num'];  // 属于该留言类型的订单处理总数递加
        	$timeslice_stat[$time_mapping[$row['timeslice']]]['consultation'] = $row;    // 按时间段的统计
        	$order += $row['order_num'];  // 处理订单总数
		}
		$GLOBALS['db']->free_result($result);	
	}
		
	/*
	 * 查询出售后评价留言回复
	 */
	$item['appraise'] = array
	(
  		'order_total' => 0, 'order_worktime' => 0, 'order_outworktime' => 0 
  	);
	// 按时间段分组查询，得出该段时间内的回复总数，回复时间总数
	if ($repliedby && $userId = getUserIdByNickname($repliedby)) { $condition3 = "AND r.`user_id` = '{$userId}'"; }
	$sql = "
        SELECT 
            COUNT(DISTINCT r.`order_id`) AS order_num, 
            HOUR(r.`created`) AS timeslice, WEEKDAY(r.`created`) AS w	
        FROM 
            {$GLOBALS['ecs']->table('after_order_comment')} AS r
            LEFT JOIN {$GLOBALS['ecs']->table('after_order_comment')} AS c ON c.`comment_id` = r.`reply_to`
            LEFT JOIN {$GLOBALS['ecs']->table('order_info')} AS o ON o.order_id = r.order_id
        WHERE
            c.`comment_id` = r.`reply_to` {$condition3}
            AND (r.`user_type` = 2 AND r.`reply_to` != 0 AND r.`post_time` != 0)
            AND (r.`created` BETWEEN '{$start}' AND '{$end}')
            AND " . party_sql('o.party_id') . "
        GROUP BY timeslice
	"; // r = 回复  c = 评论 
	$result = $GLOBALS['db']->query($sql);
	if ($result !== false)
	{
		$rows = array();
		while ($row = $GLOBALS['db']->fetchRow($result))
		{
			if ($row['w'] == 5 || $row['w'] == 6)  
			{
				// 周末的工作时间
				$worktime = $worktime_mapping[0];	
			}
			else
			{
				// 工作日的工作时间
				$worktime = $worktime_mapping[1];
			}
						
        	if (in_array($row['timeslice'], $worktime))
        	{
        		$item['appraise']['order_worktime']  += $row['order_num'];   // 工作时间订单处理数递加
        	}
        	else
        	{
				$item['appraise']['order_outworktime']  += $row['order_num']; // 工作时间订单处理数递加	
        	}
        	$item['appraise']['order_total'] += $row['order_num'];  // 属于该留言类型的订单处理总数递加
        	$timeslice_stat[$time_mapping[$row['timeslice']]]['appraise'] = $row;    // 按时间段的统计
        	$order += $row['order_num'];  // 订单处理总数
		}
		$GLOBALS['db']->free_result($result);
	}
		
	return compact('order', 'item', 'timeslice_stat');
}

/**
 * 取得确认订单的平均时间
 * 
 * @return int
 */
function getOrderConfirmAvgTime($start, $end, $repliedby = null)
{ 
    $total = 0;  // 确认订单花费时间时间
    $count = 0;  // 总共处理订单数
    
    if ($repliedby && $username = getUsernameByNickname($repliedby))
        $condition = "AND created_by_user_login = '{$username}'";
	
	// 先款后货的订单以收到款项的时间为下单时间计算
	// 订单确认时间按：客户的确认订单操作必然有action动作，以客服处理的第一时间来算
    $sql = "
        SELECT 
            o.order_id, o.order_sn,
            IF( 
                p.pay_code = 'cod', 
                o.order_time,
                IFNULL((SELECT MIN(action_time) FROM {$GLOBALS['ecs']->table('order_action')} 
                        WHERE order_id = o.order_id AND pay_status = 2), 
                        o.order_time)
            ) AS order_time,
            LEAST(
                IFNULL((SELECT MIN(created_stamp) FROM order_mixed_status_history 
                        WHERE order_status = 'confirmed' AND order_id = o.order_id {$condition}), 
                        '2010-10-10 10:10:10'),  -- 取得订单最先确认的时间
                IFNULL((SELECT MIN(created_stamp) FROM order_mixed_status_note 
                        WHERE created_by_user_class = 'worker' AND order_id = o.order_id {$condition}),
                        '2010-10-10 10:10:10')   -- 取得订单最先备注的时间
            ) AS confirm_time
        FROM
            {$GLOBALS['ecs']->table('order_info')} AS o
            LEFT JOIN {$GLOBALS['ecs']->table('payment')} AS p ON p.pay_id = o.pay_id        
        WHERE
            o.order_type_id = 'SALE' AND o.order_status = 1 AND
            o.special_type_id <> 'PRESELL' AND ". party_sql('o.party_id') ." AND
            o.pay_id NOT IN ('35', '65', '69') AND NOT EXISTS (SELECT 1 FROM order_attribute WHERE order_id = o.order_id AND attr_name = 'OUTER_TYPE') AND 
            o.order_time >= DATE_SUB('{$start}', INTERVAL 10 DAY) AND o.order_time <= '{$end}' -- 假设10天前的数据是可能被操作过的
        HAVING order_time BETWEEN '{$start}' AND '{$end}'
        ORDER BY order_time ASC 
    ";
    $result = $GLOBALS['db']->query($sql);

    if ($result !== false)
    {
        while ($row = $GLOBALS['db']->fetchRow($result))
        {
            $row['confirm_dist'] = _get_reply_spent_time($row['order_time'], $row['confirm_time']);
            if ($row['confirm_dist'] > 10800)  // 特殊需求：大于三小时的不计入统计 
            {
                $row['confirm_dist'] = 0;
                $row['ignore'] = 1;
            }
            $row['formated_confirm_dist'] = $row['confirm_dist'] > 0 ? round($row['confirm_dist']/60) : $row['confirm_dist'] ;
            $orders[] = $row;
            
            $count++;
            $total += $row['confirm_dist'];
        }
        $GLOBALS['db']->free_result($result);
    }
    
    $rate = $count > 0 ? round($total/$count/60) : 0 ;
    
    return compact('orders', 'count', 'rate');
}

/**
 * 通过传入的musql resource 遍历记录集，返回按时间段，按类型分组的统计数据
 * 需要传入字段：w, type,timeslice, post_datetime, replied_point
 * 
 * @param boolean $calculate 是否计算平均数
 */
function _fetch_all_ref_by($resource, & $item, & $stat, &$total, &$spent, &$avg, $calculate = true)
{
	global $slave_db, $time_mapping, $worktime_mapping;

	while ($row = $slave_db->fetchRow($resource))
	{	
		if ($row['w'] == 5 || $row['w'] == 6)  
		{
			// 周末的工作时间
			$worktime = $worktime_mapping[0];	
		}
		else
		{
			// 工作日的工作时间
			$worktime = $worktime_mapping[1];
		}
		// 留言的类型		
		$type = $row['type'];
		// 回复的时段
		$timeslice = $row['timeslice'];
		// 回复留言花费时间(秒)
		$row['time'] = _get_reply_spent_time($row['post_datetime'], $row['replied_point']);
		
		/**
		 * 总数
		 */	
    	$total++;                // 总留言数
    	$spent += $row['time'];  // 总回复花费时间
			
		/**
		 * 按类型的统计
		 */
		$item[$type]['total']++;                     // 属于该留言类型的总数递加
    	$item[$type]['time_total'] += $row['time'];  // 属于该留言类型的回复时间递加

    	if (in_array($timeslice, $worktime))
    	{
    		$item[$type]['total_worktime']++;               // 属于该留言类型的工作时间留言数递加
    		$item[$type]['time_worktime'] += $row['time'];  // 属于该留言类型的工作时间留言数递加
    	}
    	else
    	{
    		$item[$type]['total_outworktime']++;               // 属于该留言类型的非工作时间留言数递加
			$item[$type]['time_outworktime'] += $row['time'];  // 属于该留言类型的非工作时间留言时间递加
    	}
    	
    	/**
		 * 按时间段的统计
		 */
    	$stat[$time_mapping[$timeslice]][$type]['num']++;
    	$stat[$time_mapping[$timeslice]][$type]['time'] += $row['time'];
	}
	$slave_db->free_result($resource);
	
	/**
	 * 计算平均数
	 */	
 	if ($calculate)
 	{
 		// 总时间平均数
		$avg = @round($spent/$total/60);
	
		// 总时间转换为分钟 
		$spent = @round($spent/60);
		
		// 按类型平均 
		foreach ($item as $t => $v)
		{		
			$item[$t]['avg_total'] = @round($v['time_total']/$v['total']/60);
			$item[$t]['time_total'] = @round($v['time_total']/60);
			
			$item[$t]['avg_worktime'] = @round($v['time_worktime']/$v['total_worktime']/60);
			$item[$t]['time_worktime'] = @round($v['time_worktime']/60);
			
			$item[$t]['avg_outworktime'] = @round($v['time_outworktime']/$v['total_outworktime']/60);
			$item[$t]['time_outworktime'] = @round($v['time_outworktime']/60);
		}
		
		// 按时间段的平均
		foreach ($stat as $k => $_item)
		{
			if (is_array($_item))
			{
				foreach ($_item as $key => $value)
				{
					$stat[$k][$key]['avg'] = @round($value['time']/$value['num']/60);
					$stat[$k][$key]['time'] = @round($value['time']/60);	
				}			
			}
		}	
 	}
}

/**
 * 取得回复花费的时间
 * 
 * @param string $post_datetime  留言时间
 * @param string $reply_point    回复时间
 * 
 * @return 返回回复该留言花费的秒数
 */
function _get_reply_spent_time($post_point, $reply_point)
{
	global $worktime_start, $worktime_end;
	
	$_timestamp = strtotime($post_point);
	if ($_timestamp === false) return 0;
	
	// 留言发布时的星期和小时 
	list($_dayofweek, $_hour, $_days) = explode('#', @date('w#G#z', $_timestamp));
			
	// 周五
	if ($_dayofweek == 5)
	{
		if ($_hour < $worktime_start[1])
			$_timestamp = strtotime( date("Y-m-d {$worktime_start[1]}:00:00", $_timestamp) );
		elseif ($_hour > $worktime_end[1])
			$_timestamp = strtotime( '+1 day', strtotime(date("Y-m-d {$worktime_start[0]}:00:00", $_timestamp)) );
	}
	// 周六
	elseif ($_dayofweek == 6)
	{
		if ($_hour < $worktime_start[0])
			$_timestamp = strtotime( date("Y-m-d {$worktime_start[0]}:00:00", $_timestamp) );
		elseif ($_hour > $worktime_end[0])
			$_timestamp = strtotime( '+1 day', strtotime(date("Y-m-d {$worktime_start[0]}:00:00", $_timestamp)) );	
	}
	// 周日
	elseif ($_dayofweek == 0)
	{
		if ($_hour < $worktime_start[0])
			$_timestamp = strtotime( date("Y-m-d {$worktime_start[0]}:00:00", $_timestamp) );
		elseif ($_hour > $worktime_end[0])
			$_timestamp = strtotime( '+1 day', strtotime(date("Y-m-d {$worktime_start[1]}:00:00", $_timestamp)) );				
	}
	// 周一到周五
	else
	{
		if ($_hour < $worktime_start[1])
			$_timestamp = strtotime( date("Y-m-d {$worktime_start[1]}:00:00", $_timestamp) );
		elseif ($_hour > $worktime_end[1])
			$_timestamp = strtotime( '+1 day', strtotime(date("Y-m-d {$worktime_start[1]}:00:00", $_timestamp)) );		
	}
	
	$_timestamp2 = strtotime($reply_point);
	$spent = $_timestamp2 - $_timestamp;
	$date_diff = date('z', $_timestamp2) - $_days;
	if ($date_diff > 1)  // 隔天的,每天按12小时算 
    {
        $spent -= $date_diff * 43200;
	}
	return $spent > 0 ? $spent : 0 ;
}

/**
 * 通过用户昵称获得32位的userid
 * 
 * @return string 
 */
function getUserIdByNickname($nickname)
{
	$username = getUsernameByNickname($nickname);
    $sql = "SELECT `userId` FROM {$GLOBALS['ecs']->table('users')} WHERE `user_name` = '{$username}'";
	$userId = $GLOBALS['db']->getOne($sql, true);
	if ($userId)
	{
		return $userId;
	}
	else
	{
		sys_msg('该用户不存在');
	}
}

/**
 * 通过用户昵称获得用户名
 * 
 * @return string 
 */
function getUsernameByNickname($nickname)
{
	global $_CFG;
	
	// 用户名在ecs_admin_user和ecs_users表中的对应关系
	static $mapping = null;
	if (is_null($mapping))
	{
		$mapping = array();
		$lines = explode("\n", $_CFG['comment_users_convert']);
		foreach ($lines as $line)
		{
			$line = trim($line);
			$tmp = explode("=", $line);
			$mapping[$tmp[1]] = $tmp[0];
		}		
	}
	
	if (isset($mapping[$nickname]))
	{
		return $mapping[$nickname]	;
	}
	else
	{
		return $nickname;
	}	
}

/**
 * 取得回复人列表
 * 
 * @return array
 */
function getReplierList()
{
	global $_CFG;
	
	$repliers[''] = '回复人';
	$comment_users = explode("\n", $_CFG['comment_users']);
	foreach ($comment_users as $user)
	{
		$user = trim($user);
		$repliers[$user] = trim($user);
	}
	return $repliers;
}
