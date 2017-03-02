<?php
/**
 * 淘宝店铺数据统计 
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('taobao_consult_sales_statistics');

$start = 
    isset($_REQUEST['start'])  && strtotime($_REQUEST['start']) > 0 
    ? $_REQUEST['start'] 
    : date('Y-m-d');
$end = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end'])
    ? $_REQUEST['end']
    : date('Y-m-d');

$filter = array('start' => $start, 'end' => $end);


if (isset($_REQUEST['act']) && $_REQUEST['act'] == '筛选') {
    $statistics = array();
    
    // 咨询数量
    $sql = "
        SELECT COUNT(*) as result, s.replier, s.owner, s.taobao_shop_id
        FROM taobao_consulting_content c
            INNER JOIN taobao_consulting_section s ON s.section_id = c.section_id
        WHERE c.`type` = 'CONSULT' AND (c.`time` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY s.replier, s.owner
    ";
    taobao_statistics_sales($sql, 'total_consulting', $statistics);

    // 咨询人数
    $sql = "
        SELECT COUNT(DISTINCT(referee)) as result, replier, owner, taobao_shop_id
        FROM taobao_consulting_section
        WHERE (`start` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') OR (`end` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY replier, owner
    ";
    taobao_statistics_sales($sql, 'total_referee', $statistics);

    // 回复数量
    $sql = "
        SELECT COUNT(*) as result, s.replier, s.owner, s.taobao_shop_id
        FROM taobao_consulting_content c
            INNER JOIN taobao_consulting_section s ON s.section_id = c.section_id
        WHERE c.`type` = 'REPLY' AND (c.`time` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY s.replier, s.owner
    ";
    taobao_statistics_sales($sql, 'total_reply_count', $statistics);

    // 回复字数
    $sql = "
        SELECT SUM(reply_length) as result, replier, owner, taobao_shop_id 
        FROM taobao_consulting_section
        WHERE (`start` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') OR (`end` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY replier, owner
    ";
    taobao_statistics_sales($sql, 'total_reply_length', $statistics);

    
    // 平均响应时间
    $sql = "
        SELECT AVG(avg_respond_time) as result, replier, owner, taobao_shop_id
        FROM taobao_consulting_section
        WHERE avg_respond_time > 0 AND ( (`start` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') OR (`end` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') )
        GROUP BY replier, owner
    ";
    taobao_statistics_sales($sql, 'avg_respond', $statistics);

    // 最长响应时间
    $sql = "
        SELECT MAX(c.`interval`) as result, s.replier, s.owner, s.taobao_shop_id 
        FROM taobao_consulting_content c
            INNER JOIN taobao_consulting_section s ON s.section_id = c.section_id
        WHERE c.`type` = 'REPLY' AND (c.`time` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY s.replier, s.owner
    ";
    taobao_statistics_sales($sql, 'max_respond', $statistics);

    // 转化订单数, 通过淘宝旺旺咨询后24小时内下单的
    $sql = "
        SELECT 
            o.order_id, o.order_sn, s.replier, s.owner, s.taobao_shop_id, MD5(CONCAT_WS('_', s.replier, s.owner)) AS key2
        FROM 
            ecs_order_info o 
            INNER JOIN order_attribute a ON a.order_id = o.order_id AND a.attr_name = 'TAOBAO_USER_ID'
            INNER JOIN taobao_consulting_section s ON s.referee = a.attr_value 
            inner join ecshop.ecs_admin_user u on s.`owner` = u.real_name
        WHERE
        	o.order_time >= s.start AND
            o.order_type_id = 'SALE' AND o.order_status = 1 AND (
                (o.order_time BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') 
            ) AND 
            s.start >= '2012-01-01'
        ORDER BY s.start       
    ";
    $ref_fields = $ref_rowset = array();
    $result = $slave_db->getAllRefby($sql, array('key2'), $ref_fields, $ref_rowset);
    $order_flag = array();
    if($result){
    	foreach ($result as $key => $order){
    		$key2 = md5($order['replier'].'_'.$order['owner']);
    		if(!isset($statistics[$order['taobao_shop_id']][$key2])){
    			$statistics[$order['taobao_shop_id']][$key2] = array(
    			 	'replier' => $order['replier'],
    				'owner' => $order['owner'],);    				 			
    		}
    		if (!in_array($order['order_sn'], $order_flag)){
    			$statistics[$order['taobao_shop_id']][$key2]['total_convert_order']++;
    			$statistics[$order['taobao_shop_id']][$key2]['convert_order_list'][] = $order;
    			$order_flag[] = $order['order_sn'];
    		}
    	}
    }

    if (count($statistics)) {
    	ksort($statistics);
    	
	    foreach ($statistics as $taobao_shop_id => $taobao_sales_list) {
        foreach ($taobao_sales_list as $key => $detail) {
            // 计算转化率 （转化订单数/总咨询数）
            $statistics[$taobao_shop_id][$key]['rate_convert_order'] = 
                $detail['total_convert_order'] > 0 && $detail['total_referee'] > 0
                ? round($detail['total_convert_order']/$detail['total_referee'], 2) * 100 .'%' : 'N/A' ;
            
            // 时间格式化为分钟
            $statistics[$taobao_shop_id][$key]['max_respond'] =
                $statistics[$taobao_shop_id][$key]['max_respond'] > 0
                ? round($statistics[$taobao_shop_id][$key]['max_respond']/60, 1) : 'N/A';     
            $statistics[$taobao_shop_id][$key]['avg_respond'] = 
                $statistics[$taobao_shop_id][$key]['avg_respond'] > 0
                ? round($statistics[$taobao_shop_id][$key]['avg_respond']/60, 1) : 'N/A';    
        }}
    
        // 计算汇总的平均响应时间
        // $statistics['summarize']['avg_respond'] = round($statistics['summarize']['total_respond']/count($taobao_sales_list)/60) ;
    }
    
    $delOwner = array("何西庆", "刘瑞强", "吴军翔", "夏骥赢", "姜冲", "孙峰",
                      "成金诚", "方河", "於袁杰", "朱梦玫", "李健骏", 
                        "来秀婷", "杨琴", "杨瑞凯", "洪书昌", "潘标", "王晓红", "王鹏", 
                      "舒佩", "蓝沿", "许南平", "陈甜甜", "鞠倩", "颜卡平", "魏友俊", 
    );
    if ($statistics) {
        foreach ($statistics as $taobao_shop_id => $taobao_sales_list) {
            foreach ($taobao_sales_list as $key => $detail) {
                if (in_array($detail[owner], $delOwner)) {
                    unset($statistics[$taobao_shop_id][$key]);
                }
            }
        }
    }
}




$taobao_shop_list = $db->getAll("SELECT taobao_shop_conf_id, nick FROM taobao_shop_conf WHERE status = 'OK'");
$taobao_shop_list = Helper_Array::toHashmap($taobao_shop_list, 'taobao_shop_conf_id');  // 店铺列表

$smarty->assign('taobao_shop_list', $taobao_shop_list);        
$smarty->assign('statistics', $statistics);
$smarty->assign('filter', $filter);

$smarty->display('taobao/taobao_statistics_bysales.htm'); 



/**
 * Enter description here...
 *
 * @param unknown_type $sql
 * @param unknown_type $item
 * @param unknown_type $statistics
 */
function taobao_statistics_sales($sql, $item, & $statistics) {
    global $slave_db;
    $result = $slave_db->query($sql);
    if ($result) {
        while ($row = $slave_db->fetchRow($result)) {
            $key1 = $row['taobao_shop_id'];
            $key2 = md5($row['replier'].'_'.$row['owner']);
            
            if (!isset($statistics[$key1][$key2])) {
                $statistics[$key1][$key2] = array(
                    'replier' => $row['replier'],
                    'owner' => $row['owner'],
                    $item => $row['result'],
                );
            }
            else {
                $statistics[$key1][$key2][$item] = $row['result'];
            }
        }
    }
}

