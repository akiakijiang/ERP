<?php
/**
 * 商品调价汇总
 */
define('IN_ECS', true);
set_time_limit(3000);
require_once('includes/init.php');
require_once('distribution.inc.php');
require_once('function.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('cg_edu_sale_report');

set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
require_once 'PHPExcel.php';
require_once 'PHPExcel/IOFactory.php';

// 期初时间
$start_date = 
    ! empty($_REQUEST['start_date'])
    ? $_REQUEST['start_date'].' 00:00:00'
    : null;

// 期末时间
$end_date =
    ! empty($_REQUEST['end_date'])
    ? $_REQUEST['end_date'].' 23:59:59'
    : null;

// 提交查询
if ($start_date && $end_date) 
{  
// 电教的主分销商
//$sql = "SELECT main_distributor_id, name FROM main_distributor as a WHERE type= 'fenxiao' AND EXISTS(SELECT 1 FROM distributor WHERE main_distributor_id = a.main_distributor_id AND party_id = '{$_SESSION['party_id']}')";
$sql = "SELECT main_distributor_id, name, pa.currency
		FROM  main_distributor as md 
			INNER JOIN romeo.prepayment_account pa ON md.main_distributor_id = pa.SUPPLIER_ID AND pa.PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR'
		WHERE md.type= 'fenxiao' AND md.status = 'NORMAL' 
			AND EXISTS(SELECT 1 FROM distributor d WHERE d.main_distributor_id = md.main_distributor_id AND party_id = '{$_SESSION['party_id']}')
		";

$main_distributor_id = $slave_db->getAll($sql);

$excel = new PHPExcel();

// 汇总表
$sheet1 = $excel->getActiveSheet();
$sheet1->setTitle('对账汇总');

$main_distributor_list = "";
$i=0;
foreach ($main_distributor_id as $d) {
    $distributor_list = $slave_db->getAll("SELECT distributor_id,name,party_id FROM distributor WHERE main_distributor_id = '{$d['main_distributor_id']}' AND status = 'NORMAL'");
    if (empty($distributor_list)) {
        continue;
    }
    
    // 期初账户初额
    $sql1 = "
        SELECT SUM(pt.AMOUNT)
        FROM romeo.prepayment_account as pa
            LEFT JOIN romeo.prepayment_transaction as pt on pt.PREPAYMENT_ACCOUNT_ID = pa.PREPAYMENT_ACCOUNT_ID  
        WHERE 
            pa.PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' AND pa.SUPPLIER_ID = '{$d['main_distributor_id']}' and 
            pt.TRANSACTION_TIMESTAMP < '{$start_date}'
    ";
    $start_amount = $slave_db->getOne($sql1);
    $start_amount = is_null($start_amount) ? 0 : $start_amount;
            
    // 期末账户初额
    $sql2 = "
        SELECT SUM(pt.AMOUNT)
        FROM romeo.prepayment_account as pa
            LEFT JOIN romeo.prepayment_transaction as pt on pt.PREPAYMENT_ACCOUNT_ID = pa.PREPAYMENT_ACCOUNT_ID  
        WHERE 
            pa.PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' AND pa.SUPPLIER_ID = '{$d['main_distributor_id']}' and 
            pt.TRANSACTION_TIMESTAMP >= '{$start_date}' AND pt.TRANSACTION_TIMESTAMP <= '{$end_date}'
    ";
    $end_amount = $slave_db->getOne($sql2);
    $end_amount = is_null($end_amount) ? 0 : $end_amount;
    $end_amount = $start_amount + $end_amount;
    
    // 本期打款金额
    $sql3 = "
        SELECT SUM(pt.AMOUNT)
        FROM romeo.prepayment_account as pa
            LEFT JOIN romeo.prepayment_transaction as pt on pt.PREPAYMENT_ACCOUNT_ID = pa.PREPAYMENT_ACCOUNT_ID  
        WHERE 
            pa.PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' AND pa.SUPPLIER_ID = '{$d['main_distributor_id']}' and
            pt.AMOUNT > 0 and 
            pt.TRANSACTION_TIMESTAMP >= '{$start_date}' AND pt.TRANSACTION_TIMESTAMP <= '{$end_date}' 
    ";
    $add_amount = $slave_db->getOne($sql3);
    $add_amount = is_null($add_amount) ? 0 : $add_amount;
    
    // 本期扣款金额
    $sql4 = "
        SELECT SUM(pt.AMOUNT)
        FROM romeo.prepayment_account as pa
            LEFT JOIN romeo.prepayment_transaction as pt on pt.PREPAYMENT_ACCOUNT_ID = pa.PREPAYMENT_ACCOUNT_ID  
        WHERE 
            pa.PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' AND pa.SUPPLIER_ID = '{$d['main_distributor_id']}' and
            pt.AMOUNT < 0 and 
            pt.TRANSACTION_TIMESTAMP >= '{$start_date}' AND pt.TRANSACTION_TIMESTAMP <= '{$end_date}' 
    ";
    $dec_amount = $slave_db->getOne($sql4);
    $dec_amount = is_null($dec_amount) ? 0 : $dec_amount;
    
    // 本期返还金额
    $sql9 = "
	    SELECT sum(b.amount)
	    FROM ecshop.ecs_order_info a, ecshop.distribution_order_adjustment b, ecshop.distributor c, ecshop.main_distributor d, romeo.prepayment_transaction pt
	    WHERE a.order_id = b.order_id
	        AND a.distributor_id = c.distributor_id
	        AND c.main_distributor_id = d.main_distributor_id
	        AND b.prepayment_transaction_id = pt.prepayment_transaction_id
	        AND b.status = 'RETURNED'
	        AND pt.created_stamp >= '{$start_date}' AND pt.created_stamp <= '{$end_date}'
	        AND d.main_distributor_id = '{$d['main_distributor_id']}'
	";
    $return_amount = $slave_db->getOne($sql9);
    $return_amount = is_null($return_amount) ? 0 : $return_amount;
    
    //所有金额为0时，不生成账单
    if($start_amount == 0 && $end_amount == 0 && $add_amount == 0 && $dec_amount == 0 && $return_amount == 0) {
    	continue;
    } 
    
    $j = $i*6+1;
	$sheet1->setCellValue("A{$j}", $d['name']);
    $sheet1->setCellValue("B{$j}", "结算区间:");
    $sheet1->setCellValue("C{$j}", "{$start_date}~{$end_date}");
    $j = $i*6+2;
    $sheet1->setCellValue("A{$j}", $d['name']);
    $sheet1->setCellValue("B{$j}", "币种:");
    $sheet1->setCellValue("C{$j}", $d['currency']);
    $j = $i*6+3;
    $sheet1->setCellValue("A{$j}", $d['name']);
    $sheet1->setCellValue("B{$j}", "期初账户初额:");
    $sheet1->setCellValue("C{$j}", $start_amount);
    $j = $i*6+4;
    $sheet1->setCellValue("A{$j}", $d['name']);
    $sheet1->setCellValue("B{$j}", "本期打款金额:");
    $sheet1->setCellValue("C{$j}", $add_amount+$return_amount);
    $sheet1->setCellValue("E{$j}", "本期返还金额:");
    $sheet1->setCellValue("F{$j}", abs($return_amount));
    $j = $i*6+5;
    $sheet1->setCellValue("A{$j}", $d['name']);
    $sheet1->setCellValue("B{$j}", "本期扣款金额：");
    $sheet1->setCellValue("C{$j}", $dec_amount);
    $j = $i*6+6;
    $sheet1->setCellValue("A{$j}", $d['name']);
    $sheet1->setCellValue("B{$j}", "期末账户余额：");
    $sheet1->setCellValue("C{$j}", $end_amount);
    
    $main_distributor_list .=  $d['main_distributor_id'].",";
    $i=$i+1;
}   
 
$main_distributor_ids = substr($main_distributor_list, 0, strlen($main_distributor_list)-1);
$type = array(0 => 'string',);

/**
 * 商品明细汇总
 */
$sql5 = "
    SELECT 
    	name, goods_name, 
        sum(
        CASE WHEN item = '运费'
        THEN amount
        ELSE 0
        END ) total_shipping_fee, 
        
        sum(
        CASE WHEN item = '运费'
        THEN quantity
        ELSE 0
        END ) AS quantity1, 

        sum(
        CASE WHEN item = '价差'
        THEN amount
        ELSE 0
        END ) AS total_adjustment_fee, 

        sum(
        CASE WHEN item = '价差'
        THEN quantity
        ELSE 0
        END ) AS quantity2
        
    FROM (
        SELECT 
            d.name, d.main_distributor_id, 
            CASE WHEN b.TYPE = 'SHIPPING_ADJUSTMENT' THEN '运费' ELSE '价差' END AS item,
            CASE WHEN IFNULL( b.group_name, '' ) = '' THEN b.goods_name ELSE b.group_name END AS goods_name, 
            b.amount, if(b.status = 'CONSUMED', num, -num) AS quantity
        FROM 
            ecshop.ecs_order_info a, ecshop.distribution_order_adjustment b, ecshop.distributor c, ecshop.main_distributor d , romeo.prepayment_transaction pt
        WHERE 
            a.order_id = b.order_id
            AND a.distributor_id = c.distributor_id
            AND c.main_distributor_id = d.main_distributor_id
            AND b.status in ('CONSUMED', 'RETURNED')
            AND b.prepayment_transaction_id = pt.prepayment_transaction_id
            AND pt.created_stamp >= '{$start_date}' AND pt.created_stamp <= '{$end_date}'
            AND d.main_distributor_id in ({$main_distributor_ids})
    ) tp
    GROUP BY main_distributor_id, goods_name
    ORDER BY main_distributor_id
";
$list1 = $slave_db->getAll($sql5);

$sheet2 = $excel->createSheet();
$sheet2->setTitle('商品明细汇总');

$title = array(0 => array('分销商名称','商品类型','运费收取总金额','运费收取次数','商品扣款总金额','数量'));
$sheet2->fromArray($title);
$csv_orders = array_map('array_values', $list1);
if (!empty($csv_orders)) {
	$sheet2->fromArray($csv_orders, null, 1, $type);
}

/**
 * 对账明细
 */ 
$sql6 = "
    SELECT a.order_sn, a.taobao_order_sn, a.order_time, d.name, c.taobao_account, 
        CASE WHEN b.TYPE = 'SHIPPING_ADJUSTMENT'
        THEN '运费'
        ELSE '价差'
        END AS item,
        b.goods_name, b.amount, num AS quantity
    FROM ecshop.ecs_order_info a, ecshop.distribution_order_adjustment b, ecshop.distributor c, ecshop.main_distributor d, romeo.prepayment_transaction pt
    WHERE a.order_id = b.order_id
        AND a.distributor_id = c.distributor_id
        AND c.main_distributor_id = d.main_distributor_id
        AND b.prepayment_transaction_id = pt.prepayment_transaction_id
        AND b.status = 'CONSUMED'
        AND pt.created_stamp >= '{$start_date}' AND pt.created_stamp <= '{$end_date}'
   		AND d.main_distributor_id in ({$main_distributor_ids})
   ORDER BY d.main_distributor_id
";
$list2 = $slave_db->getAll($sql6);
$sheet3 = $excel->createSheet();
$sheet3->setTitle('对账明细');

$title2 = array(0 => array('ERP订单号','淘宝订单号','订单录入ERP时间','分销商名称','淘宝账号','项目','品名','金额','数量'));
$sheet3->fromArray($title2);
$csv_orders2 = array_map('array_values', $list2);
if (!empty($csv_orders2)) {
	$sheet3->fromArray($csv_orders2, null, 1, $type);
}

/**
 * 返还明细
 */
$sql7 = "
    SELECT a.order_sn, a.taobao_order_sn, a.order_time, d.name,c.taobao_account, 
        CASE WHEN b.TYPE = 'SHIPPING_ADJUSTMENT' THEN '运费' ELSE '价差' END AS item,
        CASE WHEN IFNULL( b.group_name, '' ) = '' THEN b.goods_name ELSE b.group_name END AS goods_name, 
        b.amount, num AS quantity
    FROM ecshop.ecs_order_info a, ecshop.distribution_order_adjustment b, ecshop.distributor c, ecshop.main_distributor d, romeo.prepayment_transaction pt
    WHERE a.order_id = b.order_id
        AND a.distributor_id = c.distributor_id
        AND c.main_distributor_id = d.main_distributor_id
        AND b.prepayment_transaction_id = pt.prepayment_transaction_id
        AND b.status = 'RETURNED'
        AND pt.created_stamp >= '{$start_date}' AND pt.created_stamp <= '{$end_date}'
        AND d.main_distributor_id in ({$main_distributor_ids})
   ORDER BY d.main_distributor_id
";
$list3 = $slave_db->getAll($sql7);
$sheet4 = $excel->createSheet();
$sheet4->setTitle('返还明细');

$title3 = array(0 => array('ERP订单号','淘宝订单号','订单录入ERP时间','分销商名称','淘宝账号','项目','品名','金额','数量'));
$sheet4->fromArray($title3);
$csv_orders3 = array_map('array_values', $list3);
if (!empty($csv_orders3)) {
	$sheet4->fromArray($csv_orders3, null, 1, $type);
}

/**
 * 打款明细
 */
$sql8 = "
    SELECT md.name, pt.TRANSACTION_TIMESTAMP, pt.AMOUNT
    FROM ecshop.main_distributor as md 
    	LEFT JOIN romeo.prepayment_account as pa on md.main_distributor_id = pa.supplier_id
        LEFT JOIN romeo.prepayment_transaction as pt on pt.PREPAYMENT_ACCOUNT_ID = pa.PREPAYMENT_ACCOUNT_ID  
    WHERE 
        pa.PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' AND md.main_distributor_id in ({$main_distributor_ids}) and
        pt.AMOUNT > 0 AND pt.NOTE not like '%返还订单%' AND
        pt.TRANSACTION_TIMESTAMP >= '{$start_date}' AND pt.TRANSACTION_TIMESTAMP <= '{$end_date}' 
   ORDER BY md.main_distributor_id
";
$list4 = $slave_db->getAll($sql8);
$sheet5 = $excel->createSheet();
$sheet5->setTitle('本期打款明细');

$title4 = array(0 => array('分销商名称','打款日期','打款金额'));
$sheet5->fromArray($title4);
$csv_orders4 = array_map('array_values', $list4);
if (!empty($csv_orders4)) {
	$sheet5->fromArray($csv_orders4, null, 1, $type);
}

$filename = "预存款汇总表.xlsx";
$output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
$output->setOffice2003Compatibility(true);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$output->save('php://output');
exit;

}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>

<b>商品调价汇总表</b>
<br />
<form method="post">
    期初时间 ：<input name="start_date" type="text" size="10" maxlength="10" value="<?php echo date('Y-m-d', strtotime('last month')); ?>" />
    期末时间：  <input name="end_date" type="text" size="10" maxlength="10" value="<?php echo date('Y-m-d'); ?>" />
  <input type="submit" value="提交" />
</form>

</body>
</html>