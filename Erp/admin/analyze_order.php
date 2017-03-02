<?php 
/**
 * 订单统计 
 */
define('IN_ECS', true);

require_once('includes/init.php');
admin_priv('analyze_order');
require_once(ROOT_PATH . 'includes/helper/array.php');
set_time_limit(600);

// 期初时间
$start = 
    !empty($_REQUEST['start']) && strtotime($_REQUEST['start']) > 0
    ? $_REQUEST['start'] 
    : date('Y-m-d') ;
    
// 期末时间
$end =
    !empty($_REQUEST['end']) && strtotime($_REQUEST['end']) > 0
    ? $_REQUEST['end']
    : date('Y-m-d') ;

// 天
$day =
    !empty($_REQUEST['day']) && is_numeric($_REQUEST['day'])
    ? $_REQUEST['day']
    : 0 ;

if ($day != 0) {
    $start = $end = date('Y-m-d', strtotime("{$day} day"));
}
    
$filter = array(
    'start' => $start,
    'end'   => $end,
    'day'   => $day,
);

$order = array();
// 欧酷官网
$order['OUKU_MOBILE']['name'] = '欧酷手机订单(含Z101)';
$order['OUKU_MOBILE_Z101']['name'] = '欧酷手机Z101订单';
$order['OUKU_EDU']['name'] = '欧酷电教(含步步高)';
$order['OUKU_EDU_BBK']['name'] = '欧酷电教步步高';
$order['OUKU_DVD']['name'] = '欧酷DVD';
// 欧酷数码专营店
$order['OUKU_TAOBAO_B_MOBILE']['name'] = '淘宝 - 欧酷数码专营店';
$order['OUKU_TAOBAO_B_EDU']['name'] = '淘宝 - 欧酷数码专营店';
$order['OUKU_TAOBAO_B_DVD']['name'] = '淘宝 - 欧酷数码专营店';
// 欧酷网手机数码
$order['OUKU_TAOBAO_C_MOBILE']['name'] = '淘宝 - 欧酷网手机数码';
$order['OUKU_TAOBAO_C_EDU']['name'] = '淘宝 - 欧酷网手机数码';
$order['OUKU_TAOBAO_C_DVD']['name'] = '淘宝 - 欧酷网手机数码';
// 惠普笔记本专供店
$order['OUKU_TAOBAO_C_NOTEBOOK']['name'] = '淘宝 - 惠普笔记本专供店';
// 香港卖DVD的专营店
$order['OUKU_HK_DVD']['name'] = '香港蓝光网站';
// 乐其
$order['LEQEE_TAOBAO_B_EDU']['name'] = '淘宝 - 乐其数码专营店';
$order['LEQEE_MOBILE_DIST']['name'] = '乐其手机分销';
$order['LEQEE_EDU_DIST']['name'] = '乐其电教分销';
$order['LEQEE_OPPO_Z101']['name'] = 'OPPO官网(Z101)';
// 有啊
$order['OUKU_YOUA_MOBILE']['name'] = '百度有啊店';

$ec_party = 32640;
$party_list = party_children_list($ec_party, false);
foreach ($party_list as $key => $party) {
    $order[$key] = array('name'=>$party);
    $ec_order[] =& $order[$key];
}

// 下单量
$sql = "
    SELECT
        o.order_id, o.order_sn, o.order_time, o.order_type_id, o.party_id, o.pay_id, o.distributor_id
    FROM
        {$ecs->table('order_info')} AS o 
    WHERE 
        o.order_type_id = 'SALE' AND o.order_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY)
";
$request_order_list = analyze_order_get_order_list($sql);
$request_order_taxo = analyze_order_taxology($request_order_list);
foreach ($order as $key => $v) {
    $order[$key]['request_order_count'] = isset($request_order_taxo[$key]) ? count($request_order_taxo[$key]) : 0 ;
    $order[$key]['request_order_list']  = isset($request_order_taxo[$key]) ? $request_order_taxo[$key] : array() ;
}


// 确认订单量
$sql = "
    SELECT
        o.order_id, o.order_sn, o.order_time, o.order_type_id, o.party_id, o.pay_id, o.distributor_id
    FROM
        {$ecs->table('order_info')} AS o 
        LEFT JOIN {$ecs->table('order_action')} AS a ON a.order_id = o.order_id AND
            a.order_status = 1 AND a.action_time < DATE_ADD('{$end}', INTERVAL 1 DAY) AND
            NOT EXISTS (  -- 在之前没有确认订单操作
                SELECT 1 FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND 
                order_status = 1 AND action_time < '{$start}'
            ) AND
            NOT EXISTS (  -- 在之后没有取消订单的操作
                SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND 
                order_status = 'canceled' AND created_stamp BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND created_stamp > a.action_time                
            )
    WHERE 
        o.order_type_id = 'SALE' AND a.order_id IS NOT NULL
    GROUP BY o.order_id
";
$confirmed_order_list = analyze_order_get_order_list($sql);
$confirmed_order_taxo = analyze_order_taxology($confirmed_order_list);
foreach ($order as $key => $v) {
    $order[$key]['confirmed_order_count'] = isset($confirmed_order_taxo[$key]) ? count($confirmed_order_taxo[$key]) : 0 ;
    $order[$key]['confirmed_order_list']  = isset($confirmed_order_taxo[$key]) ? $confirmed_order_taxo[$key] : array() ;
}


// 发货量
$sql = "
    SELECT
        o.order_id, o.order_sn, o.order_time, o.order_type_id, o.party_id, o.pay_id, o.distributor_id
    FROM
        {$ecs->table('order_info')} AS o 
        LEFT JOIN order_mixed_status_history AS sh ON sh.order_id = o.order_id AND
            sh.shipping_status = 'shipped' AND sh.created_stamp < DATE_ADD('{$end}', INTERVAL 1 DAY) AND
            NOT EXISTS (
                SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND 
                shipping_status = 'shipped' AND created_stamp < '{$start}'
            )
    WHERE 
        o.order_type_id IN ('SALE', 'RMA_EXCHANGE', 'SHIP_ONLY') AND sh.order_id IS NOT NULL
    GROUP BY o.order_id
";
$shipped_order_list = analyze_order_get_order_list($sql);
$shipped_order_taxo = analyze_order_taxology($shipped_order_list);
foreach ($order as $key => $v) {
    $order[$key]['shipped_order_count'] = isset($shipped_order_taxo[$key]) ? count($shipped_order_taxo[$key]) : 0 ;
    $order[$key]['shipped_order_list']  = isset($shipped_order_taxo[$key]) ? $shipped_order_taxo[$key] : array() ;
}



// 取消量
$sql = "
    SELECT
        o.order_id, o.order_sn, o.order_time, o.order_type_id, o.party_id, o.pay_id, o.distributor_id
    FROM
        {$ecs->table('order_info')} AS o 
        LEFT JOIN order_mixed_status_history AS sh ON sh.order_id = o.order_id AND
            sh.order_status = 'canceled' AND sh.created_stamp < DATE_ADD('{$end}', INTERVAL 1 DAY) AND
            NOT EXISTS (  -- 在之前没有取消操作
                SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND 
                order_status = 'canceled' AND created_stamp < '{$start}'  
            ) AND
            NOT EXISTS (  -- 在之后没有恢复订单的操作
                SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND 
                order_status = 'confirmed' AND created_stamp BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND created_stamp > sh.created_stamp                
            )
    WHERE 
        o.order_type_id = 'SALE' AND sh.order_id IS NOT NULL
    GROUP BY o.order_id
";
$canceled_order_list = analyze_order_get_order_list($sql);
$canceled_order_taxo = analyze_order_taxology($canceled_order_list);
foreach ($order as $key => $v) {
    $order[$key]['canceled_order_count'] = isset($canceled_order_taxo[$key]) ? count($canceled_order_taxo[$key]) : 0 ;
    $order[$key]['canceled_order_list']  = isset($canceled_order_taxo[$key]) ? $canceled_order_taxo[$key] : array() ;
}


// 拒收量
$sql = "
    SELECT
        o.order_id, o.order_sn, o.order_time, o.order_type_id, o.party_id, o.pay_id, o.distributor_id
    FROM
        {$ecs->table('order_info')} AS o 
        LEFT JOIN order_mixed_status_history AS sh ON sh.order_id = o.order_id AND
            sh.order_status = 'rejected' AND sh.created_stamp < DATE_ADD('{$end}', INTERVAL 1 DAY) AND
            NOT EXISTS (  -- 在之前没有取消或拒收操作
                SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND 
                order_status = 'rejected' AND created_stamp < '{$start}'  
            ) AND
            NOT EXISTS (  -- 在之后没有恢复订单的操作
                SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND 
                order_status = 'confirmed' AND created_stamp BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) AND created_stamp > sh.created_stamp                
            )
    WHERE 
        o.order_type_id = 'SALE' AND sh.order_id IS NOT NULL
    GROUP BY o.order_id
";
$rejected_order_list = analyze_order_get_order_list($sql);
$rejected_order_taxo = analyze_order_taxology($rejected_order_list);
foreach ($order as $key => $v) {
    $order[$key]['rejected_order_count'] = isset($rejected_order_taxo[$key]) ? count($rejected_order_taxo[$key]) : 0 ;
    $order[$key]['rejected_order_list']  = isset($rejected_order_taxo[$key]) ? $rejected_order_taxo[$key] : array() ;
}


// 退换货入库 (排除掉拒收的)
if (!empty($rejected_order_list)) {
    $in = Helper_Array::getCols($rejected_order_list, 'order_id');
    if (!empty($in)) { $cond = " AND r.root_order_id NOT ". db_create_in($in); }
}
$sql = "
    SELECT
        o.order_id, o.order_sn, o.order_time, o.order_type_id, o.party_id, o.pay_id, o.distributor_id
    FROM
        {$ecs->table('order_info')} AS o 
        LEFT JOIN service AS s ON s.back_order_id = o.order_id
        LEFT JOIN order_relation r ON o.order_id = r.order_id 
    WHERE 
        o.order_type_id = 'RMA_RETURN' AND o.order_time BETWEEN '{$start}' AND DATE_ADD('{$end}', INTERVAL 1 DAY) {$cond}
    GROUP BY o.order_id
";
$returned_order_list = analyze_order_get_order_list($sql);
$returned_order_taxo = analyze_order_taxology($returned_order_list);
foreach ($order as $key => $v) {
    $order[$key]['returned_order_count'] = isset($returned_order_taxo[$key]) ? count($returned_order_taxo[$key]) : 0 ;
    $order[$key]['returned_order_list']  = isset($returned_order_taxo[$key]) ? $returned_order_taxo[$key] : array() ;
}


// 分组
$group = array(
    '手机' => array(
        & $order['OUKU_MOBILE'], & $order['OUKU_MOBILE_Z101'], & $order['OUKU_TAOBAO_B_MOBILE'], & $order['OUKU_TAOBAO_C_MOBILE'],
        & $order['LEQEE_MOBILE_DIST'], & $order['LEQEE_OPPO_Z101'], & $order['OUKU_YOUA_MOBILE']
    ),
    '电教品' => array(
        & $order['OUKU_EDU'], & $order['OUKU_EDU_BBK'], & $order['OUKU_TAOBAO_B_EDU'], & $order['OUKU_TAOBAO_C_EDU'],
        & $order['LEQEE_EDU_DIST'], & $order['LEQEE_TAOBAO_B_EDU']
    ),
    'DVD' => array(
        & $order['OUKU_DVD'], & $order['OUKU_TAOBAO_B_DVD'], & $order['OUKU_TAOBAO_C_DVD'], & $order['OUKU_HK_DVD'], 
    ),
    '笔记本' => array(& $order['OUKU_TAOBAO_C_NOTEBOOK']),
    '电商平台' => &$ec_order,
);

$smarty->assign('group', $group);
$smarty->assign('filter', $filter);
#$smarty->assign('order', $order);
$smarty->display('oukooext/analyze_order.htm');



/**
 * 按照规则将订单分类
 * 
 * @return 分类后的订单数据
 */
function analyze_order_taxology(& $order_list)
{
    static $t;
    
    if (!isset($t)) { $t = strtotime('2009-11-03 00:00:00'); }
    
    $taxonomy = array();
    if (empty($order_list)) return $taxonomy;
    
    foreach ($order_list as $k => $o) {
        if ($o['party_id'] == 1) {
            if ($o['pay_id'] == 35) {
                // 乐其淘宝 (支付方式为“支付宝－乐其代收货款”)  11月3号前
                $taxonomy['LEQEE_TAOBAO_B_EDU'][] =& $order_list[$k];
            }
            // 百度有啊 - 欧酷数码专营店  兼容之前的数据，也可以通过支付方式来判断 (支付方式为“百付宝”)
            else if ($o['pay_id'] == 69 || $o['attrs']['OUTER_TYPE'] == 'youa') {
                $taxonomy['OUKU_YOUA_MOBILE'][] =& $order_list[$k];            	
            }
            // 淘宝B店 - 欧酷数码专营店   兼容以前的，可以用支付方式来判断 (支付方式为支付宝-淘宝商城)
            else if ($o['pay_id'] == 65 || ($o['attrs']['OUTER_TYPE'] == 'taobao') && $o['attrs']['SUB_OUTER_TYPE'] == 'jmhu@oukoo.com') {
                // DVD
                if (isset($o['category']['DVD'])) {
                    $taxonomy['OUKU_TAOBAO_B_DVD'][] =& $order_list[$k];
                }
                // 手机
                if (isset($o['category']['手机'])) {
                    $taxonomy['OUKU_TAOBAO_B_MOBILE'][] =& $order_list[$k];
                }
                // 电教 
                if (isset($o['category']['电教产品'])) {
                    $taxonomy['OUKU_TAOBAO_B_EDU'][] =& $order_list[$k];
                }
            }
            // 香港DVD专卖网
            else if ($o['attrs']['OUTER_TYPE'] == 'oukuhk') {
                $taxonomy['OUKU_HK_DVD'][] =& $order_list[$k];
            }
            // 淘宝C店 - 惠普笔记本专供店 
            else if ($o['attrs']['OUTER_TYPE'] == 'taobao' && $o['attrs']['SUB_OUTER_TYPE'] == 'zhuang_tb1@163.com') {
                $taxonomy['OUKU_TAOBAO_C_NOTEBOOK'][] =& $order_list[$k];
            }
            // 欧酷C店 - 欧酷网手机数码
            else if ($o['attrs']['OUTER_TYPE'] == 'taobao' && $o['attrs']['SUB_OUTER_TYPE'] == 'erpchenlei@163.com') {    
                // DVD
                if (isset($o['category']['DVD'])) {
                    $taxonomy['OUKU_TAOBAO_C_DVD'][] =& $order_list[$k];
                }
                // 手机
                if (isset($o['category']['手机'])) {
                    $taxonomy['OUKU_TAOBAO_C_MOBILE'][] =& $order_list[$k];
                }
                // 电教 
                if (isset($o['category']['电教产品'])) {
                    $taxonomy['OUKU_TAOBAO_C_EDU'][] =& $order_list[$k];
                }
            }
            // 欧酷官网 
            else {
                // 手机（含Z101）
                if (isset($o['category']['手机']) || isset($o['category']['配件'])) {
                    $taxonomy['OUKU_MOBILE'][] =& $order_list[$k]; 
                }
                // Z101
                if (isset($o['category']['OPPO手机'])) {
                    $taxonomy['OUKU_MOBILE_Z101'][] =& $order_list[$k];
                }
                // 电教 (含步步高)
                if (isset($o['category']['电教产品'])) {
                    $taxonomy['OUKU_EDU'][] =& $order_list[$k];
                }
                // 电教步步高
                if (isset($o['cat_id'][1468]) || isset($o['cat_id'][1465]) || isset($o['cat_id'][1464]) ||
                    isset($o['cat_id'][1467]) || isset($o['cat_id'][1466])) {
                    $taxonomy['OUKU_EDU_BBK'][] =& $order_list[$k];
                }
                // DVD
                if (isset($o['category']['DVD'])) {
                    $taxonomy['OUKU_DVD'][] =& $order_list[$k];
                }
            }
        }
        /*
        else if ($o['party_id'] == 4) {
            // 欧酷派
            $taxonomy['OUKUPAI'][] =& $order_list[$k];
        }
        */
        else if ($o['party_id'] == 8) {
            // OPPOZ101官网
            if ($o['distributor_id'] == 20) {
                $taxonomy['LEQEE_OPPO_Z101'][] =& $order_list[$k];
            }
            // 乐其手机分销
            else {
                $taxonomy['LEQEE_MOBILE_DIST'][] =& $order_list[$k];
            }
        }
        else if ($o['party_id'] == 16) {
            // 乐其淘宝（电教直销）  11月3号后, 乐其数码专营店 的
            if ($o['distributor_id'] == 31 && strtotime($o['order_time']) > $t) {
                $taxonomy['LEQEE_TAOBAO_B_EDU'][] =& $order_list[$k];
            }
            // 乐其电教分销
            else {
                $taxonomy['LEQEE_EDU_DIST'][] =& $order_list[$k];
            }
        } else {
            $taxonomy[$o['party_id']][] = & $order_list[$k];
        }
    }
    
    return $taxonomy;
}

/**
 * 通过sql取得需要统计的订单列表
 * 
 * @param string $sql
 * 
 * @return 返回格式化后的数据
 */
function analyze_order_get_order_list($sql)
{
    global $ecs, $slave_db;
    
    // 关联
    $links = array(
        // 商品
        array(    
            'sql' => "SELECT og.order_id, g.cat_id, g.top_cat_id, func_get_goods_category_detail(g.top_cat_id, g.cat_id, g.goods_id, 'Y') AS category FROM {$ecs->table('order_goods')} AS og LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id WHERE :in",
            'source_key' => 'order_id',
            'target_key' => 'order_id',
            'mapping_name' => 'goods_list',
            'type' => 'HAS_MANY',
        ),
        // 订单属性
        array(
            'sql' => "SELECT order_id, attr_name, attr_value FROM order_attribute WHERE :in ",
            'source_key' => 'order_id',
            'target_key' => 'order_id',
            'mapping_name' => 'order_attrs',
            'type' => 'HAS_MANY',
        ),
    );
    $order_list = $slave_db->findAll($sql, $links);
    if (empty($order_list)) { return array(); }

    foreach ($order_list as $key => $order) {
        // 取得该订单商品的分类名，子分类ID, 顶级分类ID的数组
        $order_list[$key]['category'] = $order_list[$key]['cat_id'] = $order_list[$key]['top_cat_id'] = array();
        if (!empty($order_list[$key]['goods_list'])) {
            foreach ($order_list[$key]['goods_list'] as $g) {
                $order_list[$key]['category'][$g['category']] = 1;
                $order_list[$key]['cat_id'][$g['cat_id']] = 1;
                $order_list[$key]['top_cat_id'][$g['top_cat_id']] = 1;
            }
        } else {
            $order_list[$key]['category'] = array();
            $order_list[$key]['cat_id'] = array();
            $order_list[$key]['top_cat_id'] = array();
        }
        
        // 取得订单属性
        $order_list[$key]['attrs'] = array();
        if (!empty($order_list[$key]['order_attrs'])) {
            foreach ($order_list[$key]['order_attrs'] as $attr) {
                $order_list[$key]['attrs'][$attr['attr_name']] = $attr['attr_value'];
            }
        }
    }

    return $order_list;
}

