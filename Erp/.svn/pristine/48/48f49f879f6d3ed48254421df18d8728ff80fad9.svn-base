<?php

/**
 * 电教销售明细管理
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('cg_edu_sale_report');
require_once('distribution.inc.php');
require_once(ROOT_PATH . 'includes/helper/array.php');


// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('查询', '导出')) 
    ? $_REQUEST['act'] 
    : null ;
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;
// 期初时间
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start'])
    ? $_REQUEST['start']
    : date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));
// 期末时间
$end = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end'])
    ? $_REQUEST['end']
    : date('Y-m-d') ;
// 分销商ID
$distributor_id =
    isset($_REQUEST['distributor_id']) && $_REQUEST['distributor_id'] > 0
    ? $_REQUEST['distributor_id']
    : null;

// 过滤条件
$filter = array('start' => $start, 'end' => $end, 'distributor_id' => $distributor_id);


// 分销店铺列表 (取得所有的)
$sql = "SELECT distributor_id, name FROM distributor";
$distributor_list = Helper_Array::toHashmap((array)$slave_db->getAll($sql), 'distributor_id', 'name');

// 分销店铺下拉选择
$distributor_select = Helper_Array::toHashmap((array)distribution_get_distributor_list(), 'distributor_id', 'name');

// 默认不查询
if ($act) {
    $conds = _get_conditions($filter);

    // 销向订单和退回的订单
    $order_list=$return_list=array();
    
    // 销向部分
    // 销售出库商品列表， RMA_EXCHANGE类型的订单不需要在这里体现了
    $sql = "
        SELECT 
            o.order_id, o.order_sn, o.order_amount, o.distributor_id, 
            o.distribution_purchase_order_sn, o.taobao_order_sn, o.shipping_fee, o.shipping_time,
            og.goods_id, og.style_id, og.goods_price, og.goods_name, og.rec_id, 
            SUM(- d.quantity_on_hand_diff) AS goods_number,  
            GROUP_CONCAT(ii.serial_number) as serial_number, ii.product_id,
            CONCAT_WS('_',o.order_id,og.goods_id,og.style_id) as idx
        FROM
            ecshop.ecs_order_info AS o
            INNER JOIN ecshop.ecs_order_goods AS og ON og.order_id = o.order_id
            LEFT JOIN ecshop.ecs_goods AS g ON g.goods_id = og.goods_id
            LEFT JOIN romeo.inventory_item_detail d ON cast(d.order_id as unsigned) = o.order_id AND cast(d.order_goods_id as unsigned) = og.rec_id
            LEFT JOIN romeo.inventory_item ii ON ii.inventory_item_id = d.inventory_item_id
        WHERE
            g.goods_party_id = " . $_SESSION['party_id'] . " AND
            o.order_type_id = 'SALE' AND
            d.quantity_on_hand_diff < 0 AND d.cancellation_flag <> 'Y' AND 
            ii.status_id IN ( 'INV_STTS_AVAILABLE', 'INV_STTS_USED', 'INV_STTS_DEFECTIVE' ) 
            {$conds}
        GROUP BY
            o.order_id, og.rec_id
    ";
    $ref_sales_order_fields = $ref_sales_order_rowset = array();
    $sales_goods_rowset = $slave_db->getAllRefby($sql, array('order_id','idx'), $ref_sales_order_fields, $ref_sales_order_rowset, false);
    if ($sales_goods_rowset) {
    	// 组合成订单
        foreach ($ref_sales_order_rowset['order_id'] as $order_id => $item_list) {
            $order=reset($item_list);
            
            // 订单信息
            $order_list[$order_id]=$order;
            
            // 订单的商品项
            $order_list[$order_id]['item_list'] = &$ref_sales_order_rowset['order_id'][$order_id];
            
            // 订单的运费作为一项（如果有运费的话）
            $order_list[$order_id]['item_list']['shipping_item']=array(
                'goods_number'  => 1,
                'goods_price'   => $order['shipping_fee'], 
                'goods_name'    => '订单运费', 
                'serial_number' => '',
                'total_amount'  => $order['shipping_fee'],
            );
        }
    	
    	// 通过订单查询商品调价记录
    	$sql="
            SELECT 
                sum(num) as num, sum(amount) as amount,
                CONCAT_WS('_', order_id,goods_id,style_id) as idx
            FROM
                distribution_order_adjustment
            WHERE
                type='GOODS_ADJUSTMENT' AND status='CONSUMED' AND 
                order_id ". db_create_in($ref_sales_order_fields['order_id']) ."
            GROUP BY
                order_id, goods_id, style_id, group_id
        ";
        $ref_fields1=$ref_rowset1=array();
        $result=$slave_db->getAllRefby($sql, array('idx'), $ref_fields1, $ref_rowset1);
        if ($result) {
            foreach ($sales_goods_rowset as &$row) {
                // 商品总金额
                $row['total_amount'] = $row['goods_number'] * $row['goods_price'];
                
                if (isset($ref_rowset1['idx'][$row['idx']])) {
                    $row['total_amount'] += $ref_rowset1['idx'][$row['idx']][0]['amount'];
                    
                    $row['adjustment_number'] = $ref_rowset1['idx'][$row['idx']][0]['num'];
                    $row['adjustment_amount'] = $ref_rowset1['idx'][$row['idx']][0]['amount'];
                }
            }
        }
    	
        // 通过订单查询运费调价记录
        $sql="
            SELECT
                order_id,SUM(amount) as amount
            FROM
                distribution_order_adjustment
            WHERE
                type='SHIPPING_ADJUSTMENT' AND status='CONSUMED' AND
                order_id ". db_create_in($ref_sales_order_fields['order_id']) ."
            GROUP BY
                order_id
        ";
        $ref_fields2=$ref_rowset2=array();
        $result=$slave_db->getAllRefby($sql, array('order_id'), $ref_fields2, $ref_rowset2);
        if ($result) {
            foreach($ref_rowset2['order_id'] as $order_id=>$item) {
                if  (isset($order_list[$order_id])) {
                    $order_list[$order_id]['item_list']['shipping_item']['adjustment_amount'] = $item[0]['amount'];
                    $order_list[$order_id]['item_list']['shipping_item']['adjustment_number'] = 1;
                    $order_list[$order_id]['item_list']['shipping_item']['total_amount']     += $item[0]['amount'];
                }
            }
        }
    }
    
    
    // 销退部分
    // 销退入库商品列表
    $sql = "
        SELECT 
            o.order_id, o.order_sn, o.order_amount, o.distributor_id, 
            o.distribution_purchase_order_sn, o.taobao_order_sn, o.shipping_fee, o.shipping_time,
            o2.order_id as original_order_id, o2.order_sn as original_order_sn,
            og.goods_id, og.style_id, og.goods_price, og.goods_name, og.rec_id, 
            SUM(d.quantity_on_hand_diff) as goods_number, d.created_stamp,
            GROUP_CONCAT(ii.serial_number) as serial_number, ii.product_id,
            CONCAT_WS('_',o2.order_id,og.goods_id,og.style_id) as idx
        FROM
            ecshop.ecs_order_info AS o
            LEFT JOIN ecshop.order_relation r ON r.order_id = o.order_id
            LEFT JOIN ecshop.ecs_order_info as o2 ON o2.order_id = r.parent_order_id
            INNER JOIN ecshop.ecs_order_goods AS og ON og.order_id = o.order_id
            LEFT JOIN ecshop.ecs_goods AS g ON g.goods_id = og.goods_id
            LEFT JOIN romeo.inventory_item_detail d ON cast(d.order_id as unsigned) = o.order_id AND cast(d.order_goods_id as unsigned) = og.rec_id
            LEFT JOIN romeo.inventory_item ii ON ii.inventory_item_id = d.inventory_item_id
        WHERE
            g.goods_party_id = " . $_SESSION['party_id'] . " AND
            o.order_type_id = 'RMA_RETURN' AND
            d.quantity_on_hand_diff > 0 AND d.cancellation_flag <> 'Y' AND 
            ii.status_id IN ( 'INV_STTS_AVAILABLE', 'INV_STTS_USED', 'INV_STTS_DEFECTIVE' )
            {$conds}
        GROUP BY 
            o.order_id, og.rec_id
    ";
    $ref_return_order_fields = $ref_return_order_rowset = array();
    $return_goods_rowset = $slave_db->getAllRefby($sql, array('original_order_id','idx'), $ref_return_order_fields, $ref_return_order_rowset, false);
    if ($return_goods_rowset) {
        // 组合成订单
        foreach ($ref_return_order_rowset['original_order_id'] as $original_order_id=>$item_list) {
            $order=reset($item_list);
        	
            // 订单信息
            $return_list[$original_order_id]=$order;
        	
            // 订单明细项
            $return_list[$original_order_id]['item_list'] = &$ref_return_order_rowset['original_order_id'][$original_order_id];
        	
            // 将订单运费作为一项
            $return_list[$original_order_id]['item_list']['shipping_item'] = array(
                'goods_number'  => 1,
                'goods_price'   => $order['shipping_fee'], 
                'goods_name'    => '订单运费', 
                'serial_number' => ''
            );
        }
    	
        // 通过订单查询商品调价记录
        // 返还调价记录是与-t订单的原订单关联的
        $sql="
            SELECT
                sum(num) as num, sum(amount) as amount,
                CONCAT_WS('_', order_id,goods_id,style_id) as idx
            FROM
                distribution_order_adjustment
            WHERE
                type='GOODS_ADJUSTMENT' AND status='RETURNED' AND 
                order_id ". db_create_in($ref_return_order_fields['original_order_id']) ."
            GROUP BY
                order_id, goods_id, style_id, group_id
        ";
        $ref_fields3=$ref_rowset3=array();
        $result=$slave_db->getAllRefby($sql, array('idx'), $ref_fields3, $ref_rowset3);
        if ($result) {
            foreach ($return_goods_rowset as &$row) {
                if (isset($ref_rowset3['idx'][$row['idx']])) {
                    $row['adjustment_number'] = $ref_rowset3['idx'][$row['idx']][0]['num'];
                    $row['adjustment_amount'] = $ref_rowset3['idx'][$row['idx']][0]['amount'];
                }
            }
        }
        
        // 通过订单查询运费调价记录
        $sql="
            SELECT
                order_id, SUM(amount) as amount
            FROM
                distribution_order_adjustment
            WHERE
                type='SHIPPING_ADJUSTMENT' AND status='RETURNED' AND
                order_id ". db_create_in($ref_return_order_fields['original_order_id']) ."
            GROUP BY
                order_id
        ";
        $ref_fields4=$ref_rowset4=array();
        $result=$slave_db->getAllRefby($sql, array('order_id'), $ref_fields4, $ref_rowset4);
        if ($result) {
            foreach($ref_rowset4['order_id'] as $order_id=>$item) {
                if  (isset($return_list[$order_id])) {
                    $return_list[$order_id]['item_list']['shipping_item']['adjustment_amount'] = $item[0]['amount'];
                    $return_list[$order_id]['item_list']['shipping_item']['adjustment_number'] = 1;
                }
            }
        }
    }
    
    $smarty->assign('order_list', $order_list);    // 销售出库的订单列表
    $smarty->assign('return_list', $return_list);  // 销退入库的订单列表
}

// 导出
if ($act == '导出') {
    edu_sale_item_export($order_list, $return_list);
}

$smarty->assign('distributor_select', $distributor_select);
$smarty->assign('distributor_list', $distributor_list);
$smarty->assign('filter', $filter);
$smarty->display('oukooext/edu_sale_item_yy.htm');


/**
 * 查询条件
 * 
 * @return string
 */
function _get_conditions(& $filter)
{   
    $conds = " AND (d.created_stamp BETWEEN '{$filter['start']}' AND DATE_ADD('{$filter['end']}', INTERVAL 1 DAY))";
    if ($filter['distributor_id']) {
        $conds .= " AND o.distributor_id = '{$filter['distributor_id']}'";
    }
    return $conds;
}


/**
 * 导出
 * 
 * @param array $order_list  销售订单
 * @param array $return_list 销退订单
 */
function edu_sale_item_export($order_list, $return_list)
{
    global $distributor_list; 
    
    $filename = "电教销售明细";

    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require_once 'PHPExcel.php';
    require_once 'PHPExcel/IOFactory.php';
    $excel = new PHPExcel();
    $excel->getProperties()->setTitle($filename);

    // 销售明细
    if (!empty($order_list)) {
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle('销售明细');
        
        $sheet->setCellValue('A1', "订单号");
        $sheet->setCellValue('B1', "订单金额");
        $sheet->setCellValue('C1', "发货时间");
        $sheet->setCellValue('D1', "分销采购订单号");
        $sheet->setCellValue('E1', "淘宝订单号");
        $sheet->setCellValue('F1', "分销商");
        
        $sheet->setCellValue('G1', "品名");
        $sheet->setCellValue('H1', "串号");
        $sheet->setCellValue('I1', "数量");
        $sheet->setCellValue('J1', "单价");
        $sheet->setCellValue('K1', "调价数量");
        $sheet->setCellValue('L1', "调价金额");
        $sheet->setCellValue('M1', "单项总金额");
        
        $i = 2;
        foreach ($order_list as $order) {
        foreach ($order['item_list'] as $goods) {
            $sheet->setCellValueExplicit("A{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue("B{$i}", $order['order_amount']);
            $sheet->setCellValue("C{$i}", date('Y-m-d H:i:s', $order['shipping_time']));
            $sheet->setCellValueExplicit("D{$i}", $order['distribution_purchase_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("E{$i}", $order['taobao_order_sn'],  PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue("F{$i}", $distributor_list[$order['distributor_id']]);
            
            $sheet->setCellValue("G{$i}", $goods['goods_name']);
            $sheet->setCellValueExplicit("H{$i}", $goods['serial_number']);
            $sheet->setCellValue("I{$i}", $goods['goods_number']);
            $sheet->setCellValue("J{$i}", $goods['goods_price']);
            $sheet->setCellValue("K{$i}", $goods['adjustment_number']);
            $sheet->setCellValue("L{$i}", $goods['adjustment_amount']);
            $sheet->setCellValue("M{$i}", $goods['total_amount']);
            
            $i++;
        }}
    }
    
    // 销退明细
    if (!empty($return_list)) {
        $sheet2 = $excel->createSheet();
        $sheet2->setTitle('销退明细');
        
        $sheet2->setCellValue('A1', "订单号");
        $sheet2->setCellValue('B1', "原订单");
        $sheet2->setCellValue('C1', "订单金额");
        $sheet2->setCellValue('D1', "分销商");
        
        $sheet2->setCellValue('E1', "品名");
        $sheet2->setCellValue('F1', "串号");
        $sheet2->setCellValue('G1', "数量");
        $sheet2->setCellValue('H1', "单价");
        $sheet2->setCellValue('I1', "返还调价数量");
        $sheet2->setCellValue('J1', "返还调价金额");
        $i = 2;
        foreach ($return_list as $order) {
        foreach ($order['item_list'] as $goods) { 
            $sheet2->setCellValueExplicit("A{$i}", $order['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet2->setCellValueExplicit("B{$i}", $order['original_order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet2->setCellValue("C{$i}", $order['order_amount']);
            $sheet2->setCellValue("D{$i}", $distributor_list[$order['distributor_id']]);
            
            $sheet2->setCellValue("E{$i}", $goods['goods_name']);
            $sheet2->setCellValueExplicit("F{$i}", $goods['serial_number']);
            $sheet2->setCellValue("G{$i}", $goods['goods_number']);
            $sheet2->setCellValue("H{$i}", $goods['goods_price']);
            $sheet2->setCellValue("I{$i}", $goods['adjustment_number']);
            $sheet2->setCellValue("J{$i}", $goods['adjustment_amount']);
            
            $i++;
        }}
    }
    
    // 输出
    if (!headers_sent()) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
        $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $output->save('php://output');
        exit;
    }
}

?>