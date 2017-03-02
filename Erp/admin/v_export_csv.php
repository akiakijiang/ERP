<?php


/**
 * -v结果导出
 * @author 杨成勇
 */

define('IN_ECS', true);
require_once ('includes/init.php');
require (ROOT_PATH . 'admin/function.php');

$start_time = trim($_REQUEST['start_time']);
$end_time = trim($_REQUEST['end_time']);
$party_id = $_SESSION['party_id'];

if ($start_time != null && $end_time != null) {
	$sql = "select og.goods_name,og.goods_number, if(status_id = 'INV_STTS_AVAILABLE','全新','二手') as status_id,
						oi.order_sn,
						if(oi.order_type_id='VARIANCE_ADD','盘盈','盘亏') as order_type_id,
						ifnull(sum(iid.quantity_on_hand_diff),0) as out_num,
						f.facility_name,
						if(egs.barcode is NULL or egs.barcode = '',eg.barcode,egs.barcode) as barcode,
						oi.order_time
						from ecshop.ecs_order_info oi
						inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
						inner join romeo.facility f on oi.facility_id = f.FACILITY_ID
						inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
						inner join ecshop.ecs_goods eg on pm.ecs_goods_id = eg.goods_id
						left join ecshop.ecs_goods_style egs on egs.goods_id = pm.ecs_goods_id and egs.style_id = pm.ecs_style_id and egs.is_delete=0
						left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
						where order_type_id in ('VARIANCE_ADD','VARIANCE_MINUS') and oi.party_id = '{$party_id}'
						group by og.rec_id 
						order by oi.facility_id,oi.order_type_id";

	$v_res = $GLOBALS['db']->getAll($sql);
}
$smarty->assign('v_res', $v_res);

if (isset ($_POST['export-v'])) {
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8", "GB18030", "v结果") . ".csv");
	$out = $smarty->fetch('oukooext/export_v_csv.htm');
	echo iconv("UTF-8", "GB18030", $out);
	exit;
}

$smarty->display('oukooext/export_v.htm');
?>