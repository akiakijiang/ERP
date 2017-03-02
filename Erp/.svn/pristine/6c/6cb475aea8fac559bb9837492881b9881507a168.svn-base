<?php
/**
 * 淘宝店铺数据统计 
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
die();  //先暂时关掉

define('IN_ECS', true);
require_once('../includes/init.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'admin/includes/lib_taobao.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
admin_priv('taobao_consult_shop_statistics');

$start = 
    isset($_REQUEST['start'])  && strtotime($_REQUEST['start']) > 0 
    ? $_REQUEST['start'] 
    : date('Y-m-d', strtotime("-1 day"));
$end = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end']) > 0
    ? $_REQUEST['end']
    : date('Y-m-d', strtotime("-1 day"));
$day =
    isset($_REQUEST['day']) && is_numeric($_REQUEST['day'])
    ? intval($_REQUEST['day'])
    : 0 ;

if ($day > 0) {
    // 后一天
    $start = $end = date('Y-m-d', strtotime("{$day} day", strtotime("{$end}")));
}
else if ($day < 0) {
    // 前一天
    $start = $end = date('Y-m-d', strtotime("{$day} day", strtotime("{$start}")));
}

$filter = array('start' => $start, 'end' => $end, 'day' => $day);

// 店铺列表
$taobao_shop_list = get_taobao_application_list(" AND is_erp_display = 'Y' ");
$taobao_shop_list = Helper_Array::toHashmap($taobao_shop_list, 'taobao_shop_conf_id');

if (isset($_REQUEST['act']) && $_REQUEST['act'] == '筛选') {
    $statistics = array(
        'summarize' => array (
            'total_consulting' => 0,    // 总咨询条数
            'total_referee' => 0,       // 总咨询人数
            'total_respond' => 0,       // 平均总响应时间
            'long_respond' => 0,        // 响应时间大于3分钟的回复数
            'max_respond' => 0,         // 最大响应时间
            'paid_order' => 0,          // 已付款订单
            'total_reply_count' => 0,   // 总回复数
            'total_reply_length' => 0,  // 总回复字数
        )
    );


    // 咨询数量
    $sql = "
        SELECT COUNT(*) as count, s.taobao_shop_id
        FROM taobao_consulting_content c
            INNER JOIN taobao_consulting_section s ON s.section_id = c.section_id
        WHERE c.`type` = 'CONSULT' AND (c.`time` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY s.taobao_shop_id
    ";
    $r = $slave_db->query($sql);
    if ($r) {
        while ($row = $slave_db->fetchRow($r)) {
            $statistics[$row['taobao_shop_id']]['total_consulting'] = $row['count'];
            $statistics['summarize']['total_consulting'] += $row['count'];
        }
    }


    // 咨询人数
    $sql = "
        SELECT COUNT(DISTINCT(referee)) as count, taobao_shop_id
        FROM taobao_consulting_section
        WHERE (`start` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') OR (`end` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY taobao_shop_id 
    ";
    $r = $slave_db->query($sql);
    if ($r) {
        while ($row = $slave_db->fetchRow($r)) {
            $statistics[$row['taobao_shop_id']]['total_referee'] = $row['count'];
            $statistics['summarize']['total_referee'] += $row['count'];
        }
    }

    
    // 回复数量
    $sql = "
        SELECT COUNT(*) as count, s.taobao_shop_id
        FROM taobao_consulting_content c
            INNER JOIN taobao_consulting_section s ON s.section_id = c.section_id
        WHERE c.`type` = 'REPLY' AND (c.`time` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY s.taobao_shop_id
    ";
    $r = $slave_db->query($sql);
    if ($r) {
        while ($row = $slave_db->fetchRow($r)) {
            $statistics[$row['taobao_shop_id']]['total_reply_count'] = $row['count'];
            $statistics['summarize']['total_reply_count'] += $row['count'];
        }
    }
    
    
    // 回复字数
    $sql = "
        SELECT SUM(reply_length) as length, taobao_shop_id
        FROM taobao_consulting_section
        WHERE (`start` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') OR (`end` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY taobao_shop_id
    ";
    $r = $slave_db->query($sql);
    if ($r) {
        while ($row = $slave_db->fetchRow($r)) {
            $statistics[$row['taobao_shop_id']]['total_reply_length'] = $row['length'];
            $statistics['summarize']['total_reply_length'] += $row['length'];
        }
    }
    
    // 平均响应时间
    $sql = "
        SELECT AVG(avg_respond_time) as avg, taobao_shop_id
        FROM taobao_consulting_section
        WHERE avg_respond_time > 0 AND ( (`start` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') OR (`end` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') )
        GROUP BY taobao_shop_id
    ";
    $r = $slave_db->query($sql);
    if ($r) {
        while ($row = $slave_db->fetchRow($r)) {
            $statistics[$row['taobao_shop_id']]['avg_respond'] = $row['avg'] > 0 ? round($row['avg']) : 0;
            $statistics['summarize']['total_respond'] += $row['avg'];
        }
    }


    // 最长响应时间
    $sql = "
        SELECT MAX(c.`interval`) as max, s.taobao_shop_id 
        FROM taobao_consulting_content c
            INNER JOIN taobao_consulting_section s ON s.section_id = c.section_id
        WHERE c.`type` = 'REPLY' AND (c.`time` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59')
        GROUP BY s.taobao_shop_id
    ";
    $r = $slave_db->query($sql);
    if ($r) {
        while ($row = $slave_db->fetchRow($r)) {
            $statistics[$row['taobao_shop_id']]['max_respond'] = $row['max'] > 0 ? round($row['max']/60) : 0;
            $statistics['summarize']['max_respond'] = max(
                $statistics['summarize']['max_respond'], $statistics[$row['taobao_shop_id']]['max_respond']);
        }
    }

    // 响应时间大于3分钟的记录数
    $sql = "
        SELECT COUNT(c.section_id) as count, s.taobao_shop_id
        FROM taobao_consulting_content c
            INNER JOIN taobao_consulting_section s ON s.section_id = c.section_id  
        WHERE c.`type` = 'REPLY' AND (c.`time` BETWEEN '{$start} 00:00:01' AND '{$end} 23:59:59') AND 
            c.`interval` > 180  -- 响应时间大于3分钟的
        GROUP BY s.taobao_shop_id
    ";
    $r = $slave_db->query($sql);
    if ($r) {
    	while ($row = $slave_db->fetchRow($r)) {
    		$statistics[$row['taobao_shop_id']]['long_respond'] = $row['count'];
    		$statistics['summarize']['long_respond'] += $row['count']; 
    	}
    }
    
    //已付款订单，添加ecs_order_mapping中间表
    $sql = "
        SELECT COUNT(o.order_id) as count, s.taobao_shop_conf_id
        FROM ecs_order_info o 
            INNER JOIN ecs_order_mapping m ON m.erp_order_id = o.order_id AND m.status = 'OK'
            INNER JOIN taobao_shop_conf s ON s.application_key = m.application_key AND s.status = 'OK'
        WHERE (o.pay_time BETWEEN UNIX_TIMESTAMP('{$start} 00:00:01') AND UNIX_TIMESTAMP('{$end} 23:59:59')) AND o.order_type_id = 'SALE' AND o.order_status = 1 AND o.pay_status = 2 
        GROUP BY s.taobao_shop_conf_id
    ";
    
    
    
    $r = $slave_db->query($sql);
    if ($r) {
        while ($row = $slave_db->fetchRow($r)) {
            $statistics[$row['taobao_shop_conf_id']]['paid_order'] = $row['count'];
            $statistics['summarize']['paid_order'] += $row['count'];
        }
    }


    // 16点前付款的未发货订单个数
    // 16点前付款且仓库有货未发货的订单数
    $sql = "
        SELECT SUM(not_shipped_count) as count1, SUM(in_stock_count) as count2
        FROM taobao_not_shipped_statistics 
        WHERE day BETWEEN '{$start}' AND '{$end}'
        GROUP BY taobao_shop_id
    ";
    $r = $slave_db->query($sql);
    if ($r) {
        while ($row = $slave_db->fetchRow($r)) {
            $statistics[$row['taobao_shop_id']]['not_shipped_count'] = $row['count1'];
            $statistics[$row['taobao_shop_id']]['in_stock_count'] = $row['count2'];

            $statistics['summarize']['not_shipped_count'] += $row['count1'];
            $statistics['summarize']['in_stock_count'] += $row['count2'];
        }
    }
    

    // 退款超过24小时未备注的订单数
    // 付款超过48小时未发货备注的订单数
    $taobaoAnalyze = new stdClass();
    $taobaoAnalyze->start = date("Y-m-d H:i:s", strtotime($start));
    $taobaoAnalyze->end = date("Y-m-d H:i:s", strtotime($end) + 24*3600);
    $taobaoAnalyze->hours = 48;

    foreach ($taobao_shop_list as $shop) {
        $taobaoAnalyze->applicationKey = $shop['application_key'];

        // 付款超过48小时未发货且未备注的订单
        try {
            $taobaoAnalyze->hours = 48;
            $taobaoOrderService = soap_get_client('TaobaoOrderService'); 
            $result = $taobaoOrderService->listTaobaoOrderUnnoted(array('taobaoAnalyze' => $taobaoAnalyze));
            if (isset($result->return->TaobaoOrderMapping)) {
                $result->return->TaobaoOrderMapping = wrap_object_to_array($result->return->TaobaoOrderMapping);
                $statistics[$shop['taobao_shop_conf_id']]['unfilled'] = count($result->return->TaobaoOrderMapping);
                // 取得订单列表
                $oIds = array();
                foreach ($result->return->TaobaoOrderMapping as $item) {
                    $oIds[] = $item->orderId;
                }
                $order_list = $db->getAll("SELECT order_id, order_sn FROM {$ecs->table('order_info')} WHERE order_id " . db_create_in($oIds)); 
                $statistics[$shop['taobao_shop_conf_id']]['unfilled_order_list'] = $order_list; 
            } else {
                $statistics[$shop['taobao_shop_conf_id']]['unfilled'] = 0 ;
                $statistics[$shop['taobao_shop_conf_id']]['unfilled_order_list'] = array(); 
            }
        } catch (SoapFault $e) {
            $statistics[$shop['taobao_shop_conf_id']]['unfilled'] = 0;
        }

        // 退款申请响应时间超过24小时的未备注的订单数
        try {
            $taobaoAnalyze->hours = 24;
            $taobaoRefundService = soap_get_client('TaobaoRefundService');
            $result = $taobaoRefundService->listTaobaoRefundUnnoted(array('taobaoAnalyze' => $taobaoAnalyze));
            if (isset($result->return->TaobaoRefund)) {
            	$result->return->TaobaoRefund = wrap_object_to_array($result->return->TaobaoRefund);
            	$statistics[$shop['taobao_shop_conf_id']]['unrefund'] = count($result->return->TaobaoRefund);
            	// 取得订单列表
            	$oIds = array();
            	foreach ($result->return->TaobaoRefund as $item) {
            		$oIds[] = $item->orderId;
            	}
            	$order_list = $db->getAll("SELECT order_id, order_sn FROM {$ecs->table('order_info')} WHERE order_id ". db_create_in($oIds));
            	$statistics[$shop['taobao_shop_conf_id']]['unrefund_order_list'] = $order_list;
            } else {
	            $statistics[$shop['taobao_shop_conf_id']]['unrefund'] = 0;
            	$statistics[$shop['taobao_shop_conf_id']]['unrefund_order_list'] = array();
            }
        } catch (SoapFault $e) {
            $statistics[$shop['taobao_shop_conf_id']]['unrefund'] = 0;
        }

        $statistics['summarize']['unfilled'] += $statistics[$shop['taobao_shop_conf_id']]['unfilled'];
        $statistics['summarize']['unrefund'] += $statistics[$shop['taobao_shop_conf_id']]['unrefund'];
    }

    
    // 计算汇总的平均响应时间
    if (count($statistics) > 1) {
        $statistics['summarize']['avg_respond'] = round($statistics['summarize']['total_respond']/count($taobao_shop_list)/60) ;
    } else {
        // 页面不显示
        unset($statistics);
    }
}





// 店铺
$taobao_shop_list = array('summarize' => array('nick' => '汇总')) + $taobao_shop_list;
$smarty->assign('taobao_shop_list', $taobao_shop_list);
// 统计数据
$smarty->assign('statistics', $statistics);
$smarty->assign('filter', $filter);

$smarty->display('taobao/taobao_statistics.htm'); 




