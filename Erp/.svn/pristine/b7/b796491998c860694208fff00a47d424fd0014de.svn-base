<?php
/**
 * 财务管理销售发票，对销售发票查询，报税等操作
 * 
 * @author zwsun
 * $date 2009-12-1 10:24:47$
 *
 */
define('IN_ECS', true);
require('includes/init.php');
require('includes/lib_sales_invoice.php');
admin_priv("sales_invoice_manage");
require("function.php");

$act = $_REQUEST['act'];
$csv = $_REQUEST['csv'];

$size = $_REQUEST['size'] ? $_REQUEST['size'] : 15;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;
$condition = get_condition();

if (empty($csv)) {
    $limit = "LIMIT $size";
    $offset = "OFFSET $start";
} else {
    admin_priv("sales_invoice_manage_csv");
    @set_time_limit(300);
    $invoicetitle = array(0 => array(
        '序号', '发票号', '订单号', '发票抬头', '开票时间', 
        '含税总价', '不含税总价', '税额', '税率', 
        '支付方式', '快递方式', '红冲标识', '作废标识', '原始发票', '是否报税'
    ));
    $headertype = array(
        1 => 'string',
        2 => 'string',
        13 => 'string',        
    );
    
    // 如果是csv的话，sql的查询不一样，需要单独的sql
    $sql = "SELECT s.sales_invoice_id, s.invoice_no, s.root_order_sn , s.partner_name, s.invoice_date,  
        s.total_amount, s.total_net_amount ,s.total_tax, s.tax_rate ,
        s.pay_name, s.shipping_name, 
        IF(s.red_flag = 'Y', '红冲', '正常') as red_flag, 
        case 
            when s.print_status = 'PRINTED'
            then '正常打印'
            WHEN s.print_status = 'DISCARDED'
            then '已作废'
        end as print_status, s2.invoice_no as original_invoice_no,
        IF(s.tax_returned = 'Y', '已报税', '未报税') as  tax_returned
        FROM sales_invoice s	
        LEFT JOIN sales_invoice s2 on s.related_invoice_id = s2.sales_invoice_id
        WHERE  s.invoice_issuer = 'OUKU' {$condition}
	    ORDER BY s.sales_invoice_id DESC
	";
    $invoices = $db->getAll($sql);
    
    $detailtitle = array(0 => array('发票号' , '税务名称' , '规格' , '分类' , 
                         '含税单价' , '不含税单价' , '税额' , '数量', 
                         '税率', '支付方式', '快递方式', '作废标识', '订单组别')
                         );
    $detailtype = array(0 => 'string',);
    
    $sql = "SELECT s.invoice_no, si.item_name,si.item_model,
            if(si.item_type = 'GOODS',
            func_get_goods_category_detail(g.top_cat_id, g.cat_id, g.goods_id, 'Y'), 
            si.item_name
            ) as cate,
            si.unit_price, si.unit_net_price, si.unit_tax, si.quantity,
            s.tax_rate, s.pay_name, s.shipping_name, 
            case 
                when s.print_status = 'PRINTED'
                then '正常打印'
                WHEN s.print_status = 'DISCARDED'
                then '已作废'
            end as print_status,
            if(si.item_type = 'GOODS',
               case 
                 when s.party_id = 4 
                    then '鞋子组'
                 when g.cat_id in (1157, 1502, 1442, 1504) 
                    then 'dvd组'
                 when (g.top_cat_id = 1458 OR g.cat_id = 1868)
                    then '电教'
                 else '手机组'
               end,
               '') as order_group,
            si.item_type
            FROM sales_invoice s	
            INNER JOIN sales_invoice_item si on s.sales_invoice_id = si.sales_invoice_id
            LEFT JOIN ecshop.ecs_goods g on si.goods_id = g.goods_id
            where s.invoice_issuer = 'OUKU' {$condition}
            ORDER BY s.sales_invoice_id DESC
    ";
    $invoice_items = $db->getAll($sql);
    
    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';

    $filename = "欧酷销售发票_" .date("Y-m-d").".xlsx";
    $excel = new PHPExcel();

    // 发票
    $sheet = $excel->getActiveSheet();
    $sheet->setTitle("发票");
    $sheet->fromArray($invoicetitle);
    $invoices = array_map('array_values', $invoices);
    if (!empty($invoices)) {
        $sheet->fromArray($invoices, null, 1, $headertype);
    }

    // 发票明细
    $detailsheet = $excel->createSheet();
    $detailsheet->setTitle("发票明细");
    
    $detailsheet->fromArray($detailtitle);    
    
    // 补齐明细中运费，红包的订单组别
    // 其为订单中最贵商品的订单组别
    if (!empty($invoice_items)) {
        $last_invoice_no = '';  // 当前发票号
        $last_order_group = ''; // 订单组别
        $last_amount = 0;       // 单张发票中商品金额最大的项目
        
        foreach ($invoice_items as &$item) {
            $sub_amount = ($item['unit_price'] * $item['quantity']);
            if ($item['invoice_no'] != $last_invoice_no) {
                $last_invoice_no = $item['invoice_no'];
                $last_order_group = $item['order_group'];
                $last_amount = $sub_amount;
            } else {
                if ($item['item_type'] == 'GOODS' && ($sub_amount > $last_amount) ) {
                    $last_order_group = $item['order_group'];
                    $last_amount = $sub_amount;
                } elseif (empty($item['order_group'])) {
            	    $item['order_group'] = $last_order_group;
                }
            }
        }
        $invoice_items = array_map('array_values', $invoice_items);
        $detailsheet->fromArray($invoice_items, null, 1, $detailtype);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    exit();
}


if ( in_array($act, array("tax_returned"))) {
    if ($act == 'tax_returned') {
        $invoice_nos = $_POST['invoice_no'];
        $result = tax_return_sales_invoice($invoice_nos);
        if ($result === true) {
            sys_msg('报税成功', 
            0, 
            array(array('text' => '继续报税', 'href'=>'sales_invoice_manage.php')), 
            false);
        } else {
            sys_msg($result, 0, array(), false);
        }
    }
}



$sql = "
	SELECT s.*, s2.invoice_no as original_invoice_no
	FROM sales_invoice s	
	LEFT JOIN sales_invoice s2 on s.related_invoice_id = s2.sales_invoice_id
	WHERE s.invoice_issuer = 'OUKU' {$condition}
	ORDER BY s.sales_invoice_id DESC
	$limit $offset ";
$sqlc = " SELECT COUNT(*) FROM sales_invoice s 	
	WHERE invoice_issuer = 'OUKU' {$condition} ";

$sales_invoices = $db->findAll(
$sql, array(array(
              'sql' => 'SELECT si.*, o.order_sn FROM sales_invoice_item si 
                        LEFT JOIN ecs_order_info o ON si.order_id = o.order_id
                        WHERE :in',
              'source_key' => 'sales_invoice_id',
              'target_key' => 'sales_invoice_id',
              'mapping_name' => 'sales_invoice_items',
              'type' => 'HAS_MANY',
          ))
);
//pp($sales_invoices);
$count = $db->getOne($sqlc);
$pager = Pager($count, $size, $page);

$print_status_array = array(
    '-1'        => '所有',
    'PENDING'   => '等待打印',
    'CANCELED'  => '已取消',
    'PRINTED'   => '已打印',
    'DISCARDED' => '已作废',    
);

$red_flag_array = array(
    ''  => '所有',
    'Y' => '红冲发票',
    'N' => '正常发票',
);

$tax_returned_array = array(
    'N' => '未报税',
    'Y' => '已报税', 
    ''  => '所有',   
);

$search_type_array = array(
    'invoice_no' => '按发票号搜索',
    'order_sn'   => '按订单号搜索',
);


$smarty->assign('back', $_SERVER['REQUEST_URI']);
$smarty->assign('sales_invoices', $sales_invoices);
//pp($sales_invoices);
$smarty->assign('pager', $pager);

$smarty->assign('print_status_array',       $print_status_array);
$smarty->assign('red_flag_array',           $red_flag_array);
$smarty->assign('tax_returned_array',       $tax_returned_array);
$smarty->assign('search_type_array',        $search_type_array);
$smarty->assign('search_type_array',        $search_type_array);
$smarty->assign('can_update_invoice_no',    check_admin_priv('sales_invoice_update_invoice_no'));
$smarty->display('sales_invoice/sales_invoice_manage.htm');


function get_condition() {
	$condition     = "";
	$print_status  = trim($_REQUEST['print_status']);
	$keyword       = trim($_REQUEST['keyword']);
	$tax_returned  = trim($_REQUEST['tax_returned']);
	$red_flag      = trim($_REQUEST['red_flag']);
	
	$act = $_REQUEST['act'];
	
	if (!empty($tax_returned)) {
	    $condition .= " AND s.tax_returned = '{$tax_returned}' ";
	}
	
	if (!empty($red_flag)) {
	    $condition .= " AND s.red_flag = '{$red_flag}' ";
	}
	
	if ($print_status != null && $print_status != -1) {
		$condition .= " AND s.print_status = '$print_status' ";
	}
	
	if ($keyword != '') {
	    $search_type = $_REQUEST['search_type'];
	    if ($search_type == 'order_sn') {
	        $condition .= " AND s.sales_invoice_id iN (
	                       SELECT DISTINCT(si.sales_invoice_id) 
	                       FROM sales_invoice_item si 
	                       INNER JOIN ecs_order_info o ON si.order_id = o.order_id
	                       WHERE o.order_sn = '{$keyword}' ) ";
	    } else {
	        $condition .= " AND s.invoice_no = '{$keyword}'";
	    }
	}
	
	$startDate = $_REQUEST['startDate'];
	if ($startDate) {
	    $condition .= " AND s.invoice_date >= '{$startDate}' ";
	}
	
	$endDate = $_REQUEST['endDate'];
	if ($endDate) {
	    $condition .= " AND s.invoice_date <= '{$endDate}' ";
	}
	
	if ($act != 'search') {
		$condition .= " AND s.tax_returned = 'N' ";
	}
	
	# 添加party_id
	$condition .= " AND ". party_sql('s.party_id');

	return $condition;
}
