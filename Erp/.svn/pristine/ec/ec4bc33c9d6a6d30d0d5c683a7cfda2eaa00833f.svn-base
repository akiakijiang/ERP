<?php
/*
 * Created on 2013-12-16 by qdi
 * 天猫超市自动出库管理
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
if(!in_array($_SESSION['admin_name'],array('ljni','jjhe','ychen','shyuan','wjzhu','lchen', 'lwang', 'zwzheng', 'jrpei','hli','ljzhou','mjzhou','xlhong','qdi','hbai','cywang','jwang','yfhu','xfning')))
{
	die('没有权限');
}

require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

//$party_id = $_SESSION['party_id'];

$startDate = $_REQUEST ['start_validity_time'];
if(empty($startDate)){
	$tmp_forwardmonth = mktime(0,0,0,substr(date("Ym"),4,2)-1,1,substr(date("Ym"),0,4));
	$startDate = date("Y-m-d H:i:s",$tmp_forwardmonth);
}
$endDate = $_REQUEST ['end_validity_time'];
if(empty($endDate)){
	$endDate = date("Y-m-d H:i:s");
}

$list = get_diff_list($startDate,$endDate);
$smarty->assign('lists', $list);  // 曾使用过的仓库

$smarty->display('oukooext/search_v_inventory_result.htm');

function get_diff_list($startDate,$endDate){
	global $db;
	$sql = "
			select concat(eg.goods_name,ifnull(es.value,'')) as name,result1.* from 
				(
					select result.product_id,result.facility_id,result.status,
					sum(new_in_num) as new_in,
					sum(old_in_num) as old_in,
					sum(new_in_num)-sum(old_in_num) as in_diff,
					sum(new_out_num) as new_out,
					sum(old_out_num) as old_out,
					sum(new_out_num)-sum(old_out_num) as out_diff
					from 
					(
						select ii.product_id,ii.facility_id,if(ii.STATUS_ID='INV_STTS_AVAILABLE','NEW','SECOND_HAND')  as status,
						  				ifnull(sum(if(iv.QUANTITY_ON_HAND_VAR > 0,iv.QUANTITY_ON_HAND_VAR,0)),0) as new_in_num,
									  	ifnull(sum(if(iv.QUANTITY_ON_HAND_VAR < 0,-iv.QUANTITY_ON_HAND_VAR,0)),0) as new_out_num,
											0 as old_in_num,
						          0 as old_out_num
									from romeo.inventory_item_variance iv
									inner join romeo.inventory_item ii on iv.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID
									 where iv.CREATED_STAMP > '{$startDate}' and iv.CREATED_STAMP < '{$endDate}'
									group by ii.PRODUCT_ID,ii.facility_id,ii.STATUS_ID
						UNION ALL
						select pm.product_id,oi.facility_id,oe.is_new as status,
											0 as new_in_num,
						          0 as new_out_num,
									  	ifnull(sum(if(oe.in_sn !='' and oe.in_sn is not null,1,0)),0) as old_in_num,
									  	ifnull(sum(if(oe.out_sn !='' and oe.out_sn is not null,1,0)),0) as old_out_num
									 from ecshop.ecs_order_info oi
									inner join ecshop.ecs_order_goods  og 
									on oi.order_id = og.order_id
									inner join romeo.product_mapping pm 
									on pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
									left join ecshop.ecs_oukoo_erp oe on og.rec_id = oe.order_goods_id
									where oi.order_type_id = 'VARIANCE' and oi.order_time > '{$startDate}' and oi.order_time < '{$endDate}'
									group by pm.product_id,oe.facility_id,oe.is_new
									having oe.is_new <> ''
					) as result
					group by result.product_id,result.facility_id,result.status
					having (in_diff-out_diff) != 0
				)as result1
				inner join romeo.product_mapping pm on pm.product_id = result1.product_id
				inner join ecshop.ecs_goods eg on pm.ecs_goods_id = eg.goods_id
				left join ecshop.ecs_style es on pm.ecs_style_id = es.style_id
				";
	return $db->getAll($sql);
}
?>