<?php

/**
 * 店长报表数据导出
 */
define('IN_ECS', true);
set_time_limit(3000);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
admin_priv('report_TaoBaoStoreManager');

die("<h1>此页面已关闭，请到常用报表里导店长报表！</h1>");
//@ini_set('memory_limit','4092M');
@ini_set('max_execution_time','3600');

$act = $_REQUEST['act'];

// 期初时间
$start_date = 
    ! empty($_REQUEST['start_date'])
    ? $_REQUEST['start_date']
    : null;

// 期末时间
$end_date =
    ! empty($_REQUEST['end_date'])
    ? date('Y-m-d', strtotime('+1 day', strtotime($_REQUEST['end_date'])))
    : null;

if($act == 'order'){
	$sql1 = "
		SELECT 
		  oi.order_sn,
		  p.NAME,
		  f.facility_name,
		  d.name pName,
		  oi.order_time, 
		  cast(case oi.order_status
		    when 0 then _utf8'未确认'
		    when 1 then _utf8'已确认'
		    when 2 then _utf8'已取消'
		    when 4 then _utf8'已拒收'
		    else oi.order_status
		  end as char(64)) as order_status, 
		  oi.order_amount,
		  sum(if(doa.amount is null, 0, doa.amount)) as prepayment_amount,
		  oi.shipping_fee,
		  oi.taobao_order_sn,
		  ifnull(oa.attr_value, ''),
		  IFNULL(pr.region_name, '') province,
		  IFNULL(ci.region_name, '') city,
		  s.shipping_name,
		  d.name distributor_id,
		  tta.pay_time, 
		  if (oi.order_status = 2, 'Y', 'N') as is_trade_closed_by_taobao,
		  if (oa3.attr_value = '360buy', '360buy', 'taobao') outer_type,
		  ifnull(oa4.attr_value,'') buyer_payment,
		  ifnull(oa5.attr_value,'') alipay_no
		FROM ecshop.ecs_order_info oi
		LEFT JOIN romeo.party p ON convert(oi.party_id using utf8) = p.PARTY_ID
		LEFT JOIN ecshop.order_attribute oa on oi.order_id = oa.order_id and oa.attr_name = 'TAOBAO_USER_ID'
		left join ecshop.order_attribute oa3 on oi.order_id = oa3.order_id and oa3.attr_name = 'OUTER_TYPE'
		left join ecshop.order_attribute oa4 on oi.order_id = oa4.order_id and oa4.attr_name = 'BUYER_PAYMENT'
		left join ecshop.order_attribute oa5 on oi.order_id = oa5.order_id and oa5.attr_name = 'ALIPAY_NO'
		LEFT JOIN romeo.facility f on oi.facility_id = f.facility_id
		LEFT JOIN ecshop.distributor d on oi.distributor_id = d.distributor_id
		left join ecshop.ecs_region pr on oi.province = pr.region_id
		left join ecshop.ecs_region ci on oi.city = ci.region_id
		left join ecshop.ecs_shipping s on oi.shipping_id=s.shipping_id
		left join ecshop.ecs_taobao_trade_amount tta on oi.order_id=tta.order_id
		left join ecshop.distribution_order_adjustment doa on doa.order_id = oi.order_id and doa.status = 'CONSUMED'
		WHERE 
		    oi.order_time >= '{$start_date}'
		    AND oi.order_time < '{$end_date}'
		    AND oi.party_id = {$_SESSION['party_id']}
		    AND oi.order_type_id = 'SALE'
		GROUP BY oi.order_id
		ORDER BY oi.order_time
		";
	ini_set('memory_limit','2048M');
	$csv_orders = $slave_db->getAll($sql1);
    
    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
	require_once 'PHPExcel.php';
	require_once 'PHPExcel/IOFactory.php';
    
	$title = array(0 => array(
        'ERP订单号', '组织', '仓库名称', '业务', '订单时间',
        '订单状态', '订单金额', '预存款金额','运费', '淘宝订单号',
        '顾客淘宝ID', '省', '市', '配送方式', '分销商ID',
        '淘宝付款时间', '是否订单取消', '外部订单类型','分销买家实付金额', '支付宝交易号'
    ));
    $type = array(0 => 'string',);
    
	$filename .= "销售订单信息_".$start_date."-".$_REQUEST['end_date'].".xlsx";
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $csv_orders = array_map('array_values', $csv_orders);
    if (!empty($csv_orders)) {
        $sheet->fromArray($csv_orders, null, 1, $type);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    exit();
}else if($act == 'goods'){
	$sql1 = "SELECT 
			  o.order_sn,
			  p.NAME,
			  d.name,
			  ifnull(oa1.attr_value, ''),
			  o.order_time, 
			  o.order_amount,
			  o.goods_amount,
			  ec.cat_name,
			  og.goods_name,
			  ifnull(bog.goods_type, ''),
			  if(eg.barcode='','',eg.barcode),
			  gs.barcode ,
			  og.goods_price ,
			  og.goods_number,
			  og.goods_price*if(o.goods_amount=0,0,(o.goods_amount+o.bonus)/o.goods_amount),
			  og.goods_number*og.goods_price*if(o.goods_amount=0,0,(o.goods_amount+o.bonus)/o.goods_amount),
			  o.taobao_order_sn,
			  f.facility_name,
			  IFNULL(pr.region_name, '') province,
			  IFNULL(ci.region_name, '') city,
			  if (o.order_status = 2, 'Y', 'N') as is_trade_closed_by_taobao,
			  if (oa2.attr_value = '360buy', '360buy', 'taobao') outer_type,
			  concat_ws('_',cast(eg.goods_id as char(10)),cast(es.style_id as char(10))) as outer_id,
			  ifnull(cast(oga.value as char(30)),'') as buyer_payment,
			  ifnull(oa5.attr_value, '') alipay_no
			FROM ecshop.ecs_order_info o
			INNER JOIN ecshop.ecs_order_goods og ON o.order_id = og.order_id
			LEFT JOIN ecshop.order_attribute oa1 on CAST(o.order_id AS UNSIGNED) = oa1.order_id and oa1.attr_name = 'TAOBAO_USER_ID'
			left join ecshop.order_attribute oa2 on CAST(o.order_id AS UNSIGNED) = oa2.order_id and oa2.attr_name = 'OUTER_TYPE'
			left join ecshop.order_goods_attribute oga on CAST(og.rec_id AS UNSIGNED) = oga.order_goods_id and oga.name = 'BUYER_PAYMENT'
			left join ecshop.order_attribute oa5 on CAST(o.order_id AS UNSIGNED) = oa5.order_id and oa5.attr_name = 'ALIPAY_NO'
			LEFT JOIN romeo.party p ON o.party_id = p.PARTY_ID				 
			left join ecshop.ecs_goods eg on og.goods_id = eg.goods_id  
			left join ecshop.ecs_style es on og.style_id = es.style_id 
			left join ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id and og.style_id = gs.style_id and gs.is_delete=0
			left join romeo.facility f on o.facility_id = f.facility_id
			left join ecshop.distributor d on o.distributor_id = d.distributor_id 
			left join ecshop.ecs_category ec on eg.cat_id = ec.cat_id 
			left join ecshop.ecs_region pr on o.province = pr.region_id
			left join ecshop.ecs_region ci on o.city = ci.region_id
			left join ecshop.brand_or_order_goods bog ON bog.erp_order_goods_id = cast(og.rec_id as unsigned)
			WHERE 
			    o.order_time >= '{$start_date}'
			    AND o.order_time < '{$end_date}'
			    AND o.party_id = {$_SESSION['party_id']}
			    AND o.order_type_id = 'SALE'
			ORDER BY o.order_time
		";
	ini_set('memory_limit','2048M');
	$csv_goods = $slave_db->getAll($sql1);
    
    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
	require_once 'PHPExcel.php';
	require_once 'PHPExcel/IOFactory.php';
    
	$title = array(0 => array(
        'ERP订单号', '组织', '分销商', '顾客淘宝ID', '订单时间',
        '订单金额', '商品金额', '种类','商品名称', 'goodsType(Origins悦木之源专用)',
        '款号', 'sku条码', '商品单价', '商品数量', '折扣单价',
        '折扣总价', '淘宝订单号', '仓库名称','省', '市',
        '是否订单取消', '外部订单类型', '商家编码','分销买家实付单价', '支付宝交易号'
    ));
    $type = array(0 => 'string',);
    
	$filename = "销售订单商品明细_".$start_date."-".$_REQUEST['end_date'].".xlsx";
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $csv_goods = array_map('array_values', $csv_goods);
    if (!empty($csv_goods)) {
        $sheet->fromArray($csv_goods, null, 1, $type);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    exit();
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>
<body>

<b>销售订单信息</b>
<form method="post">
    期初时间 ：<input name="start_date" type="text" size="10" maxlength="10" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" />
   期末时间：  <input name="end_date" type="text" size="10" maxlength="10" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" />
  <input type="hidden" name="act" value="order" />
  <input type="submit" value="提交" />
</form>
<b>销售订单商品明细</b>
<form method="post">
    期初时间 ：<input name="start_date" type="text" size="10" maxlength="10" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" />
   期末时间：  <input name="end_date" type="text" size="10" maxlength="10" value="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" />
   <input type="hidden" name="act" value="goods" />
   <input type="submit" value="提交" />
</form>

</body>
</html>