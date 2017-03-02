<?php 

/**
 * 销售发票编辑
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/validator.php');

$pkv = 
    isset($_REQUEST['sales_invoice_id']) && is_numeric($_REQUEST['sales_invoice_id'])
    ? $_REQUEST['sales_invoice_id']
    : false;
    
$view =
    isset($_REQUEST['view']) && in_array($_REQUEST['view'], array('collect', 'disperse'))
    ? $_REQUEST['view']
    : 'collect';
    
$act =
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('export', 'print'))
    ? $_REQUEST['act']
    : 'list';
     
if (!$pkv) {
    header("Location: sales_invoice_list.php");
    exit;
}


// 用于跳回到原来的页面
// 期初时间
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start'])
    ? $_REQUEST['start']
    : date('Y-m-d', mktime(0, 0, 0, date('m'), 1, date('Y')));  // 当月第一天 
// 期末时间
$end = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end'])
    ? $_REQUEST['end']
    : date('Y-m-d') ;
// 分销商ID
$main_distributor_id =
    isset($_REQUEST['main_distributor_id']) && $_REQUEST['main_distributor_id'] > 0
    ? $_REQUEST['main_distributor_id']
    : null;
// 状态
$invoice_status = 
    isset($_REQUEST['invoice_status']) && trim($_REQUEST['invoice_status'])
    ? $_REQUEST['invoice_status']
    : null;
// 每页尺寸    
$size = 
    isset($_REQUEST['size']) && is_numeric($_REQUEST['size'])
    ? $_REQUEST['size']
    : null;
// 页面跳转的参数
$referer = array(
    'start' => $start, 'end' => $end, 'size' => $size,
    'main_distributor_id' => $main_distributor_id, 'invoice_status' => $invoice_status
);


// 发票头
$sales_invoice = $db->getRow("SELECT * FROM sales_invoice WHERE sales_invoice_id = '{$pkv}'", true);

if (!$sales_invoice) {
    header("Location: sales_invoice_list.php?message=".urlencode('错误的参数，找不到该发票'));
    exit;
}


// 提交处理
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
    do {
        // 验证发票号
        $ret = Helper_Validator::validate(trim($_POST['invoice_no']), 'not_empty');
        if (!$ret) {
            $message = '请填写正确的发票号';
            break;
        }
        
        // 验证开票日期
        $ret = Helper_Validator::validate(trim($_POST['invoice_date']), 'is_date');
        if (!$ret) {
            $message = '请填写正确的开票日期';
            break;            
        }
        
        // 验证配送方式名
        /*
        $ret = Helper_Validator::validate(trim($_POST['shipping_name']), 'not_empty');
        if (!$ret) {
            $message = '请填写配送方式';
            break;            
        }
        */
        
        // 验证支付方式名
        /*
        $ret = Helper_Validator::validate(trim($_POST['pay_name']), 'not_empty');
        if (!$ret) {
            $message = '请填写支付方式';
            break;            
        }
        */

        // 更新发票
        $sql = "
            UPDATE sales_invoice 
            SET invoice_no = '{$_POST['invoice_no']}', invoice_date = '{$_POST['invoice_date']}', shipping_name = '{$_POST['shipping_name']}', pay_name = '{$_POST['pay_name']}', remark = '{$_POST['remark']}', updated_stamp = NOW() %s 
            WHERE sales_invoice_id = '{$pkv}' LIMIT 1
        ";
        if (isset($_POST['status']) && $_POST['status'] == 'CONFIRMED') {
            $sql = sprintf($sql, ", status = 'CONFIRMED'");
        } else {
            $sql = sprintf($sql, '');
        }
        if ($db->query($sql, 'SILENT')) {
            $sales_invoice = $db->getRow("SELECT * FROM sales_invoice WHERE sales_invoice_id = '{$pkv}'", true);
            $message = '更改已保存';
        }
    } while (false) ;
}


// 发票明细
if ($view == 'collect') { // 汇总显示
    // 同一种商品（折扣）要求汇总显示
    $sql = "
        SELECT
            order_id, item_name, serial_number, AVG(unit_price) AS unit_price, 
            AVG(unit_net_price) AS unit_net_price, SUM(quantity) AS quantity, 
            (SUM(prepayment_amount)/SUM(quantity)) as prepayment_amount
        FROM sales_invoice_item 
        WHERE sales_invoice_id = '{$pkv}' AND item_type IN ('GOODS', 'DISCOUNT')  -- 同一个商品的单价和折扣价都是一样的 
        GROUP BY item_type, product_id
    UNION ALL
        SELECT
            order_id, item_name, serial_number, unit_price, unit_net_price, quantity, prepayment_amount
        FROM sales_invoice_item
        WHERE sales_invoice_id = '{$pkv}' AND item_type NOT IN ('GOODS', 'DISCOUNT')
    ";
    $sales_invoice_item = $db->getAll($sql);
    
    // 取得库存交易明细
    $inventory_transaction_id_array = $db->getCol("
    	SELECT inventory_transaction_id FROM sales_invoice_item WHERE sales_invoice_id = '{$pkv}'
	");
}
else {// 明细显示
    $sql = "
        SELECT order_id, item_name, serial_number, unit_price, unit_net_price, quantity, inventory_transaction_id
        FROM sales_invoice_item WHERE sales_invoice_id = '{$pkv}' ORDER BY item_type, product_id
    ";
    // 取得关联的订单信息
    $links = array(
        array(
            'sql' => "SELECT order_id, order_sn FROM {$ecs->table('order_info')} WHERE :in",
            'source_key' => 'order_id',
            'target_key' => 'order_id',
            'mapping_name' => 'order',
            'type' => 'BELONGS_TO',
        ),
    );
    $sales_invoice_item = $db->findAll($sql, $links);
    
    // 取得库存交易明细
    require_once(ROOT_PATH . 'includes/helper/array.php');
    $inventory_transaction_id_array = Helper_Array::getCols((array)$sales_invoice_item, 'inventory_transaction_id');
}

// 取得发票的开票区间
if ($inventory_transaction_id_array) {
    $sql = "
    	SELECT MIN(created_stamp) AS transaction_begin_date, MAX(created_stamp) AS transaction_end_date 
    	FROM romeo.inventory_transaction WHERE inventory_transaction_id 
    	". db_create_in($inventory_transaction_id_array);
    $interval = $db->getRow($sql);
    if ($interval) {
        $sales_invoice['transaction_begin_date'] = strtotime($interval['transaction_begin_date']);  // 交易开始时间
        $sales_invoice['transaction_end_date'] = strtotime($interval['transaction_end_date']);      // 交易结束时间
    }
}

// 导出
if ($act == 'export') {
    sales_invoice_item_export($sales_invoice_item, $view);
}

//获得系统可以使用的出库单号
$sql = "select value from ecs_shop_config where code = 'leqee_sales_invoice_begin_no'";
$begin_no = $db->getOne($sql,true);

// 发票状态列表
$invoice_status_list = array('INIT' => '未确认', 'CONFIRMED' => '已确认', 'COMPLETED' => '已结算', 'CLOSED' => '已关闭');

// 消息
if (!empty($message)) {
    $smarty->assign('message', $message);
}

$smarty->assign('referer', $referer);
$smarty->assign('view', $view);
$smarty->assign('begin_no', $begin_no);
$smarty->assign('invoice_status_list', $invoice_status_list);
$smarty->assign('sales_invoice', $sales_invoice);
$smarty->assign('sales_invoice_item', $sales_invoice_item);
$smarty->display('sales_invoice/sales_invoice_edit.htm');


/**
 * 导出销售发票明细
 * 
 * @param array $sales_order_list  销售订单
 * @param array $return_order_list 销退订单
 */
function sales_invoice_item_export($sales_invoice_item, $view)
{
    $filename = "销售发票明细";

    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require_once 'PHPExcel.php';
    require_once 'PHPExcel/IOFactory.php';
    $excel = new PHPExcel();
    $excel->getProperties()->setTitle($filename);

    // 销售明细
    if (!empty($sales_invoice_item)) {
        $sheet = $excel->getActiveSheet();
        $sheet->setTitle('销售发票明细');
        
        if ($view == 'collect') {
            $sheet->setCellValue('A1', "发票项");
            $sheet->setCellValue('B1', "数量");
            $sheet->setCellValue('C1', "去税平均单价");
            $sheet->setCellValue('D1', "含税平均单价");
            
            $i = 2;
            foreach ($sales_invoice_item as $item) {
                $sheet->setCellValue("A{$i}", $item['item_name']);
                $sheet->setCellValue("B{$i}", $item['quantity']);
                $sheet->setCellValue("C{$i}", $item['unit_net_price']);
                $sheet->setCellValue("D{$i}", $item['unit_price']);
                $i++;
            }
        } 
        else {
            $sheet->setCellValue('A1', "发票项");
            $sheet->setCellValue('B1', "所属订单");
            $sheet->setCellValue('C1', "串号");
            $sheet->setCellValue('D1', "数量");
            $sheet->setCellValue('E1', "去税单价");
            $sheet->setCellValue('F1', "含税单价");
            
            $i = 2;
            foreach ($sales_invoice_item as $item) {
                $sheet->setCellValue("A{$i}", $item['item_name']);
                $sheet->setCellValue("B{$i}", $item['order']['order_sn']);
                $sheet->setCellValue("C{$i}", $item['serial_number']);
                $sheet->setCellValue("D{$i}", $item['quantity']);
                $sheet->setCellValue("E{$i}", $item['unit_net_price']);
                $sheet->setCellValue("F{$i}", $item['unit_price']);
                $i++;
            }
        }
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
