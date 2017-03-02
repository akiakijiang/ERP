<?php

/**
 * 调价报表
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

$datalist = array();
// 按机型汇总
$sql5 = "
    SELECT 
        main_distributor_id, goods_name, 
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
                a.order_sn, a.taobao_order_sn, a.order_time, d.main_distributor_id, a.order_id,
                CASE WHEN b.TYPE = 'SHIPPING_ADJUSTMENT' THEN '运费' ELSE '价差' END AS item,
                CASE WHEN IFNULL( b.group_name, '' ) = '' THEN b.goods_name ELSE b.group_name END AS goods_name, 
                b.amount, num AS quantity
            FROM 
                ecshop.ecs_order_info a, ecshop.distribution_order_adjustment b, ecshop.distributor c, ecshop.main_distributor d , romeo.prepayment_transaction pt
            WHERE 
                a.order_id = b.order_id
                AND a.distributor_id = c.distributor_id
                AND c.main_distributor_id = d.main_distributor_id
                AND b.status = 'CONSUMED'
                AND b.prepayment_transaction_id = pt.prepayment_transaction_id
                AND pt.created_stamp >= '{$start_date}' AND pt.created_stamp <= '{$end_date}'
        ) tp
        GROUP BY main_distributor_id, goods_name
";
$ref_fields1 = $ref_rowset1 = array();
$slave_db->getAllRefby($sql5, array('main_distributor_id'), $ref_fields1, $ref_rowset1);
   
// 对账明细
//        CASE WHEN IFNULL( b.group_name, '' ) = ''
//        THEN b.goods_name
//        ELSE b.group_name
//        END AS goods_name, 
$sql6 = "
    SELECT a.order_sn, a.taobao_order_sn, a.order_time, d.name, a.order_id,
        CASE WHEN b.TYPE = 'SHIPPING_ADJUSTMENT'
        THEN '运费'
        ELSE '价差'
        END AS item,
        b.goods_name, b.amount, num AS quantity, d.main_distributor_id, c.taobao_account
    FROM ecshop.ecs_order_info a, ecshop.distribution_order_adjustment b, ecshop.distributor c, ecshop.main_distributor d, romeo.prepayment_transaction pt
    WHERE a.order_id = b.order_id
        AND a.distributor_id = c.distributor_id
        AND c.main_distributor_id = d.main_distributor_id
        AND b.prepayment_transaction_id = pt.prepayment_transaction_id
        AND b.status = 'CONSUMED'
        AND pt.created_stamp >= '{$start_date}' AND pt.created_stamp <= '{$end_date}'
";
$ref_fields2 = $ref_rowset2 = array();
$slave_db->getAllRefby($sql6, array('main_distributor_id'), $ref_fields2, $ref_rowset2);

// 返还明细
$sql7 = "
    SELECT a.order_sn, a.taobao_order_sn, a.order_time, d.name, a.order_id,
        CASE WHEN b.TYPE = 'SHIPPING_ADJUSTMENT' THEN '运费' ELSE '价差' END AS item,
        CASE WHEN IFNULL( b.group_name, '' ) = '' THEN b.goods_name ELSE b.group_name END AS goods_name, 
        b.amount, num AS quantity, d.main_distributor_id,c.taobao_account,pt.TRANSACTION_TIMESTAMP,pt.AMOUNT
    FROM ecshop.ecs_order_info a, ecshop.distribution_order_adjustment b, ecshop.distributor c, ecshop.main_distributor d, romeo.prepayment_transaction pt
    WHERE a.order_id = b.order_id
        AND a.distributor_id = c.distributor_id
        AND c.main_distributor_id = d.main_distributor_id
        AND b.prepayment_transaction_id = pt.prepayment_transaction_id
        AND b.status = 'RETURNED'
        AND pt.created_stamp >= '{$start_date}' AND pt.created_stamp <= '{$end_date}'
";
$ref_fields3 = $ref_rowset3 = array();
$slave_db->getAllRefby($sql7, array('main_distributor_id'), $ref_fields3, $ref_rowset3);

// 电教的主分销商
//$sql = "SELECT main_distributor_id, name FROM main_distributor as a WHERE type= 'fenxiao' AND EXISTS(SELECT 1 FROM distributor WHERE main_distributor_id = a.main_distributor_id AND party_id = '{$_SESSION['party_id']}')";
$sql = "SELECT main_distributor_id, name, pa.currency
		FROM  main_distributor as md 
			INNER JOIN romeo.prepayment_account pa ON md.main_distributor_id = pa.SUPPLIER_ID AND pa.PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR'
		WHERE md.type= 'fenxiao' AND md.status = 'NORMAL' 
			AND EXISTS(SELECT 1 FROM distributor d WHERE d.main_distributor_id = md.main_distributor_id AND party_id = '{$_SESSION['party_id']}')
		";
$main_distributor_id = $slave_db->getAll($sql);
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
        SELECT pt.AMOUNT, pt.TRANSACTION_TIMESTAMP, pt.NOTE
        FROM romeo.prepayment_account as pa
            LEFT JOIN romeo.prepayment_transaction as pt on pt.PREPAYMENT_ACCOUNT_ID = pa.PREPAYMENT_ACCOUNT_ID  
        WHERE 
            pa.PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' AND pa.SUPPLIER_ID = '{$d['main_distributor_id']}' and
            pt.AMOUNT > 0 and 
            pt.TRANSACTION_TIMESTAMP >= '{$start_date}' AND pt.TRANSACTION_TIMESTAMP <= '{$end_date}' 
    ";
    $add_amount_detail = $slave_db->getAll($sql3);
    $add_amount = 0;
    foreach ($add_amount_detail as $item) {
        $add_amount += $item['AMOUNT'];
    }
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
    if(isset($ref_rowset3['main_distributor_id'][$d['main_distributor_id']])) {
        $return_amount=array_sum(Helper_Array::getCols($ref_rowset3['main_distributor_id'][$d['main_distributor_id']],'amount'));
    } else {
    	$return_amount=0;
    }
    
    //所有金额为0时，不生成账单
    if($start_amount == 0 && $end_amount == 0 && $add_amount == 0 && $dec_amount == 0 && $return_amount == 0) {
    	continue;
    } 
    
    $excel = new PHPExcel();
    $excel->getProperties()->setTitle($d['name']);
    
    // 汇总表
    $sheet1 = $excel->getActiveSheet();
    $sheet1->setTitle('对账汇总');
    
    $sheet1->setCellValue("A1", "分销商名称:");
    $sheet1->setCellValue("B1", $d['name']);

    $sheet1->setCellValue("A2", "结算区间:");
    $sheet1->setCellValue("B2", "{$start_date}~{$end_date}");
    
    $sheet1->setCellValue("A3", "币种:");
    $sheet1->setCellValue("B3", $d['currency']);
    
    $sheet1->setCellValue("A4", "期初账户初额:");
    $sheet1->setCellValue("B4", $start_amount);
    
    $sheet1->setCellValue("A5", "本期打款金额:");
    $sheet1->setCellValue("B5", $add_amount+$return_amount);
    
    $sheet1->setCellValue("D5", "本期返还金额:");
    $sheet1->setCellValue("E5", abs($return_amount));
    
    $sheet1->setCellValue("A6", "本期扣款金额：");
    $sheet1->setCellValue("B6", $dec_amount);
    
    $sheet1->setCellValue("A7", "期末账户余额：");
    $sheet1->setCellValue("B7", $end_amount);
    
    $sheet1->setCellValue("A10", "本期扣款情况：");
    
    // 按机型汇总
    $list1 = isset($ref_rowset1['main_distributor_id'][$d['main_distributor_id']])
        ? $ref_rowset1['main_distributor_id'][$d['main_distributor_id']] : array() ;
    $i = 11;
    $sheet1->setCellValue("A{$i}", "商品类型");
    $sheet1->setCellValue("B{$i}", "运费收取总金额");
    $sheet1->setCellValue("C{$i}", "运费收取次数");
    $sheet1->setCellValue("D{$i}", "商品扣款总金额");
    $sheet1->setCellValue("E{$i}", "数量");
    $i++;
    if (!empty($list1)) {
        foreach ($list1 as $item) {
            $sheet1->setCellValue("A{$i}", $item['goods_name']);
            $sheet1->setCellValue("B{$i}", $item['total_shipping_fee']);
            $sheet1->setCellValue("C{$i}", $item['quantity1']);
            $sheet1->setCellValue("D{$i}", $item['total_adjustment_fee']);
            $sheet1->setCellValue("E{$i}", $item['quantity2']);
            $i++;
        }
    }
    
    /**
     * 对账明细
     */ 
    $sheet2 = $excel->createSheet();
    $sheet2->setTitle('对账明细');
    
    $i = 1;
    $sheet2->setCellValue("A{$i}", "ERP订单号");
    $sheet2->setCellValue("B{$i}", "淘宝订单号");
    $sheet2->setCellValue("C{$i}", "订单录入ERP时间");
    $sheet2->setCellValue("D{$i}", "分销商名称");
    $sheet2->setCellValue("E{$i}", "淘宝账号");
    $sheet2->setCellValue("F{$i}", "项目");
    $sheet2->setCellValue("G{$i}", "品名");
    $sheet2->setCellValue("H{$i}", "金额");
    $sheet2->setCellValue("I{$i}", "数量");
    $i++;
    $list2 = isset($ref_rowset2['main_distributor_id'][$d['main_distributor_id']])
        ? $ref_rowset2['main_distributor_id'][$d['main_distributor_id']] : array();
    foreach ($list2 as $item) {
        $sheet2->setCellValueExplicit("A{$i}", $item['order_sn']);
        $sheet2->setCellValueExplicit("B{$i}", $item['taobao_order_sn']);
        $sheet2->setCellValue("C{$i}", $item['order_time']);
        $sheet2->setCellValue("D{$i}", $item['name']);
        $sheet2->setCellValue("E{$i}", $item['taobao_account']);
        $sheet2->setCellValue("F{$i}", $item['item']);
        $sheet2->setCellValue("G{$i}", $item['goods_name']);
        $sheet2->setCellValue("H{$i}", $item['amount']);
        $sheet2->setCellValue("I{$i}", $item['quantity']);
        $i++;
    }
    
    /**
     * 返还明细
     */
    $sheet3 = $excel->createSheet();
    $sheet3->setTitle('返还明细');
    
    $i = 1;
    $sheet3->setCellValue("A{$i}", "ERP订单号");
    $sheet3->setCellValue("B{$i}", "淘宝订单号");
    $sheet3->setCellValue("C{$i}", "订单录入ERP时间");
    $sheet3->setCellValue("D{$i}", "分销商名称");
    $sheet3->setCellValue("E{$i}", "淘宝账号");
    $sheet3->setCellValue("F{$i}", "项目");
    $sheet3->setCellValue("G{$i}", "品名");
    $sheet3->setCellValue("H{$i}", "金额");
    $sheet3->setCellValue("I{$i}", "数量");
    $i++;
    $list3 = isset($ref_rowset3['main_distributor_id'][$d['main_distributor_id']])
        ? $ref_rowset3['main_distributor_id'][$d['main_distributor_id']] : array();
    foreach ($list3 as $item) {
        $sheet3->setCellValueExplicit("A{$i}", $item['order_sn']);
        $sheet3->setCellValueExplicit("B{$i}", $item['taobao_order_sn']);
        $sheet3->setCellValue("C{$i}", $item['order_time']);
        $sheet3->setCellValue("D{$i}", $item['name']);
        $sheet3->setCellValue("E{$i}", $item['taobao_account']);
        $sheet3->setCellValue("F{$i}", $item['item']);
        $sheet3->setCellValue("G{$i}", $item['goods_name']);
        $sheet3->setCellValue("H{$i}", abs($item['amount']));
        $sheet3->setCellValue("I{$i}", $item['quantity']);
        $i++;
    }
    /*
     * 打款明细
     */
    $sheet4 = $excel->createSheet();
    $sheet4->setTitle('本期打款明细');
    $i = 1;
    $sheet4->setCellValue("A{$i}", "打款日期");
    $sheet4->setCellValue("B{$i}", "打款金额");
    if (is_array($add_amount_detail)) {
        foreach ($add_amount_detail as $item) {
            if (strpos($item['NOTE'], '返还订单') !== false ) {
                continue;
            }
            $i++;
            $sheet4->setCellValue("A{$i}", $item['TRANSACTION_TIMESTAMP']);
            $sheet4->setCellValue("B{$i}", $item['AMOUNT']);
        }
    }

    $filepath = ROOT_PATH .'templates/caches/'.$d['name'].'.xls';
    
    //$filepath = iconv('UTF-8', 'GBK', $filepath);
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
    $output->save($filepath);
    $filelist[] = $filepath;

}

// 将文件压缩
$rand_file = tempnam(ROOT_PATH .'templates/caches', 'EDU-');
$zip_file = $rand_file . '.zip';
setlocale(LC_CTYPE, "UTF8", "en_US.UTF-8");
$cmd = 'zip -j '. escapeshellarg($zip_file) .' '. implode(' ', array_map('escapeshellarg', $filelist));
exec($cmd, $output, $return);
if ($return===0) {
    // 成功
}

// 删除生成的excel文件
foreach ($filelist as $file) {
    @unlink($file);
}

if (!file_exists($zip_file)) {  
    exit("无法找到文件");  
}

/*
$zip = new ZipArchive();
if ($zip->open($zip_file, ZIPARCHIVE::OVERWRITE)===TRUE) {  
    foreach ($datalist as $val) {
        if (file_exists($val['filepath'])){  
            $zip->addFile($val['filepath'],$val['filename']);
            unlink($val['filepath']);
        }
    }
    $zip->close();
}
*/


header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Description: File Transfer");
header("Content-Type: application/zip");
header('Content-disposition: attachment; filename='.basename($zip_file).'; charset=utf-8');
header("Content-Transfer-Encoding: binary");   
header('Content-Length: '. filesize($zip_file));  
@readfile($zip_file);
@unlink($zip_file);
@unlink($rand_file);
exit;

}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>

<b>商品调价报表</b>
<form method="post">
    期初时间 ：<input name="start_date" type="text" size="10" maxlength="10" value="<?php echo date('Y-m-d', strtotime('last month')); ?>" />
   期末时间：  <input name="end_date" type="text" size="10" maxlength="10" value="<?php echo date('Y-m-d'); ?>" />
   <input type="submit" value="提交" />
</form>

</body>
</html>