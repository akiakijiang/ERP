<?php

/**
 * 打印销售出库单
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/validator.php');

/**
 * 1 打印的条目只能是商品，所有的出库记录按商品进行汇总
 * 2 运费和折扣要分摊到对应订单所对应的商品上
 * 3 每张出库单只能打印20条记录
 */

$pkv = 
    isset($_REQUEST['sales_invoice_id']) && is_numeric($_REQUEST['sales_invoice_id'])
    ? $_REQUEST['sales_invoice_id']
    : false;
$act =
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('export', 'print'))
    ? $_REQUEST['act']
    : 'list';
     
if (!$pkv) {
    header("Location: sales_invoice_list.php");
    exit;
}
$begin_no = $_REQUEST['begin_no'];


// 发票头
$sales_invoice = $db->getRow("SELECT * FROM sales_invoice WHERE sales_invoice_id = '{$pkv}'", true);

if (!$sales_invoice) {
    header("Location: sales_invoice_list.php?message=".urlencode('错误的参数，找不到该发票'));
    exit;
}

// 查询所有的单价
$db->query("SET SESSION group_concat_max_len = 3072");
$sql = "
    SELECT
        order_id, g.goods_name as item_name, g.goods_name as item_model, 
        SUM(IF(item_type = 'GOODS', quantity, 0)) AS quantity, 
        SUM(quantity * unit_price) AS goods_amount,
        GROUP_CONCAT(distinct(order_id) separator ',') AS order_ids,
        CASE
            WHEN (g.cat_id IN (1512, 1508) OR g.top_cat_id IN (1)) then '台'
            WHEN (g.cat_id IN (1509, 1862) OR g.top_cat_id IN (597)) then '个'
            WHEN (g.cat_id IN (1517)) then '本'
        ELSE '个'
        END AS uom
    FROM sales_invoice_item si
        lEFT JOIN ecs_goods g on si.goods_id = g.goods_id
    WHERE sales_invoice_id = '{$pkv}' AND item_type IN ('GOODS', 'DISCOUNT')  -- 同一个商品的单价和折扣价都是一样的 
    GROUP BY product_id
";
$sales_invoice_item = $db->getAll($sql);
    
// 查询其他的费用，以便分摊
$sql = "
    select order_id, unit_price * quantity as shipping_fee
    from sales_invoice_item 
    where sales_invoice_id = '{$pkv}'
    and item_type = 'FEE' 
";
$_fee = $db->getAll($sql);
$shiping_fee_array = array();

foreach ($_fee as $shipping_fee) {
    $shipping_fee_array[$shipping_fee['order_id']] = $shipping_fee['shipping_fee'];
}
    
// 将运费加到相同订单对应的goods_amount上
foreach ($sales_invoice_item as &$item) {
    $_tmp = explode(',', $item['order_ids']);
    foreach ($_tmp as $order_id) {
        $item['goods_amount'] += $shipping_fee_array[$order_id];
        // 清掉shipping_fee，一个订单的shipping_fee只能算一次
        $shipping_fee_array[$order_id] = 0;
    }
}
    
$quantity_sum = 0;
$_total_amount = 0; // 这个变量用来计算明细项金额之和和头信息是否一致
// 重新运费分摊后的计算单价
foreach ($sales_invoice_item as &$item) {
    //如果商品的个数为0，那么不显示
    if ($item['quantity'] == 0) {
        unset($item);
    } else {
        $item['unit_price'] = $item['goods_amount'] / $item['quantity'];
    }
    $quantity_sum += $item['quantity'];
    $_total_amount += $item['goods_amount'];
}
if (abs($_total_amount - $sales_invoice['total_amount']) > 1) {
    sys_msg("数据出现不一致，请联系erp组解决");
}

// debug code：
//$sales_invoice_item = array_pad($sales_invoice_item, 30, $sales_invoice_item[0]);

if ($act == 'export') {
    sales_invoice_print_export($sales_invoice_item);
}


// 将明细切成10一页
$sales_invoice_items = array();    
$i = 0;
$size = 10;
do {
    $temp = array_slice($sales_invoice_item, $i * $size, $size);
    if (!empty($temp)) {
        $sales_invoice_items[] = array_pad($temp, $size, array());
        $i++;
    }        
} while (!empty($temp));
   
if ($i > 1) {
    $tips = "打印对应的号码为{$begin_no} 至 ". ($begin_no + $i -1);
} else {
    $tips = "打印对应的号码为{$begin_no}";
}

// 在备注中记录相关打印号码
$sql = "update sales_invoice 
        set description = concat_ws('\n', description, '$tips') 
        where sales_invoice_id = $pkv ";
$db->query($sql);
//最新的可以使用的号码
$begin_no = $begin_no + $i;
$sql = "update ecs_shop_config set value = '$begin_no' 
        where code = 'leqee_sales_invoice_begin_no' ";
$db->query($sql);
    
$smarty->assign('sales_invoice', $sales_invoice);
$smarty->assign('sales_invoice_item', $sales_invoice_item);
$smarty->assign('sales_invoice_items', $sales_invoice_items);
$smarty->assign('today', date("Y-m-d", time()));
$smarty->display('sales_invoice/sales_invoice_edit_print.htm');

/**
 * 导出需要打印的内容
 */
function sales_invoice_print_export($sales_invoice_item) {
    $filename = "销售出库单";

    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require_once 'PHPExcel.php';
    require_once 'PHPExcel/IOFactory.php';
    $excel = new PHPExcel();
    $excel->getProperties()->setTitle($filename);

    $sheet = $excel->getActiveSheet();
    $sheet->setTitle('销售出库明细');

    $sheet->setCellValue('A1', "商品描述");
    $sheet->setCellValue('B1', "单位");
    $sheet->setCellValue('C1', "数量");
    $sheet->setCellValue('D1', "单价");
    $sheet->setCellValue('E1', "金额");

    $i = 2;
    foreach ($sales_invoice_item as $item) {
        $sheet->setCellValue("A{$i}", $item['item_name']);
        $sheet->setCellValue("B{$i}", $item['uom']);
        $sheet->setCellValue("C{$i}", $item['quantity']);

        $sheet->setCellValue("D{$i}", $item['item_name'] ? sprintf($item['unit_price'], '%.4f') : '');
        $style = $sheet->getStyle("D{$i}");
        $style->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);    
        
        $sheet->setCellValue("E{$i}", $item['item_name'] ? sprintf($item['goods_amount'], '%.2f') : '');
        $style = $sheet->getStyle("E{$i}");
        $style->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);    

        $i++;
    }

    if (!headers_sent()) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
        header('Cache-Control: max-age=0');
        $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $output->save('php://output');
        exit;
    }
}

