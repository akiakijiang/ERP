<?php
/**
 * 耗材库存汇总
 * 
 * @author zxcheng 2014.01.02
 */
define('IN_ECS', true);
require_once('includes/init.php');
require("function.php");
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
$type = $_REQUEST['csv'];
Qlog::log('act='.$_REQUEST ['act']);
Qlog::log('type='.$type);
if ($_REQUEST ['act'] == 'consumable_goods_storage_export') {
	$facility_id = trim ( $_REQUEST ['facility'] );
	//所有上海仓的仓库
	if($facility_id == 1) { 
		$sql = "select facility_id from romeo.facility where facility_name like '%上海%' and is_closed = 'N'";
		$facility_ids = $db->getCol($sql);
		$facility_ids = array_merge($facility_ids, array(24196974, 33147796));
		$facility_id = '"' . implode(',', $facility_ids) . '"';
	}
	//耗材商品库存清单导出
	$sql = "SELECT 
			f.facility_name,
			p.name as party_name, 
			CONCAT(eg.goods_name,' ',IF (egs.goods_color = '' or egs.goods_color is null , ifnull(es.color, ''), ifnull(egs.goods_color, ''))) as goods_name,
			ec.cat_name,
			if(ii.status_id = 'INV_STTS_AVAILABLE', '全新', if(ii.status_id = 'INV_STTS_USED', '二手', ii.status_id)) as is_new,
			ii.status_id as inv_status_id,
			if(egs.barcode is NULL or egs.barcode = '',eg.barcode,egs.barcode) as goods_barcode, 
			sum(ii.quantity_on_hand) as count,
			group_concat(il.location_barcode) as location_barcode
			FROM romeo.inventory_item ii
			LEFT JOIN romeo.facility f on ii.facility_id = f.facility_id
			LEFT JOIN romeo.inventory_location il on ii.product_id = il.product_id and ii.facility_id = il.facility_id
			LEFT JOIN romeo.product_mapping pm on ii.product_id = pm.product_id
			LEFT JOIN ecshop.ecs_goods eg on pm.ecs_goods_id = eg.goods_id
			LEFT JOIN ecshop.ecs_style es on pm.ecs_style_id = es.style_id
			INNER JOIN ecshop.ecs_goods_style AS egs ON egs.goods_id = eg.goods_id and egs.style_id = es.style_id and egs.is_delete=0
			LEFT JOIN ecshop.ecs_category ec on eg.cat_id = ec.cat_id
			LEFT JOIN romeo.party p on eg.goods_party_id = p.party_id
	       	WHERE
	       		ii.facility_id " . db_create_in($facility_id) . " 
                 and ec.cat_name LIKE '%耗材%'
			GROUP BY ii.PRODUCT_ID, ii.facility_id, ii.status_id limit 5000";
	// Qlog::log('consumable_goods_storage='.$sql);
	$storage_list = $db->getAll ( $sql );
}else if ($_REQUEST ['act'] == 'export_best_shipping'){
	$batch_order_id = trim($_REQUEST['batch_order_id']);
	$begin_time = trim($_REQUEST['begin_time']);
	$end_time = trim($_REQUEST['end_time']);
	if ($batch_order_id != '') {
        $order_ids = preg_split('/[\s]+/', $batch_order_id);
        $orders = array();
        //直接准备数据
        $sql = "SELECT order_id from ecshop.ecs_order_info oi
				LEFT JOIN ecshop.distributor d ON oi.distributor_id = d.distributor_id
				LEFT JOIN ecshop.main_distributor md ON d.main_distributor_id = md.main_distributor_id
				WHERE md.type = 'zhixiao' 
				AND oi.order_time >'{$begin_time}' and oi.order_time <'{$end_time}' and oi.order_type_id='sale' ";
	   // Qlog::log($sql);
	   $order_ids = $slave_db->getCol ( $sql );
        foreach ($order_ids as $key => $order_id) {
        	$order = getOrderInfo($order_id);
        	$shippingFeeShowInfo = get_order_shipping_fee($order);
        	//pp($shippingFeeShowInfo);die;
        	if(!empty($shippingFeeShowInfo)){
        		foreach($shippingFeeShowInfo as $key=> $sfsi){
        		$orders[$order['order_sn']][$key]['shipping_name'] = $sfsi['shipping_name'];
        		$orders[$order['order_sn']][$key]['order_sn'] = $order['order_sn'];
        		$orders[$order['order_sn']][$key]['province'] = get_region_names($order['province']);
        		$orders[$order['order_sn']][$key]['party_name'] = party_mapping($order['party_id']);
        		$orders[$order['order_sn']][$key]['facility_name'] = $sfsi['facility_name'];
        		$orders[$order['order_sn']][$key]['shipping_fee'] = $sfsi['shipping_fee'];
        		$orders[$order['order_sn']][$key]['arrived'] = $sfsi['arrived'];
        	    }
        	}
        }
    }
}else if($_REQUEST ['act'] == 'export_new_detail'){
	//新存出入库明细
	$product_id = trim ( $_REQUEST ['new_product_id'] );
	$facility_id = trim ( $_REQUEST ['new_facility'] );
	Qlog::log('product_id='.$product_id);
	Qlog::log('facility_id='.$facility_id);
	//所有上海仓的仓库
	if($facility_id == 1) { 
		$facility_id = "19568549,22143847,22143846,3633071,24196974,33147796";
	}
	$sql = "select oi.order_sn,oi.taobao_order_sn,oi.order_id,og.rec_id,oi.order_type_id,s.shipping_name,f.facility_name,pm.product_id,ifnull(gs.barcode,g.barcode) as barcode,
			oi.order_status,oi.shipping_status,iid.last_updated_stamp,
			oi.order_time,og.goods_name,og.goods_number,ii.status_id,
			ifnull(sum(if(iid.QUANTITY_ON_HAND_DIFF is not null and iid.QUANTITY_ON_HAND_DIFF >0,iid.QUANTITY_ON_HAND_DIFF,0)),0) as in_num,
			ifnull(sum(if(iid.QUANTITY_ON_HAND_DIFF is not null and iid.QUANTITY_ON_HAND_DIFF <0,-iid.QUANTITY_ON_HAND_DIFF,0)),0) as out_num
			from ecshop.ecs_order_info oi
			left join ecshop.ecs_order_goods og ON oi.order_id = og.order_id
			left join ecshop.ecs_shipping s ON oi.shipping_id = s.shipping_id
			left join romeo.facility f ON oi.facility_id = f.facility_id
			left join ecshop.ecs_goods g ON og.goods_id = g.goods_id
			left join ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id and gs.style_id = og.style_id and gs.is_delete=0
			left join romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
			left join romeo.inventory_item_detail iid ON convert(og.rec_id using utf8)=iid.order_goods_id
			left join romeo.inventory_item ii ON iid.inventory_item_id = ii.inventory_item_id
			where pm.product_id = '{$product_id}'
			and oi.facility_id " . db_create_in($facility_id) . " 
			and ii.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED')
			group by og.rec_id,ii.status_id
			order by oi.order_time ";
   // Qlog::log('export_new_detail='.$sql);
   $new_details = $db->getAll ($sql);
}else if($_REQUEST ['act'] == 'search_atp_detail'){
	//查看实时atp
	$product_id = trim ( $_REQUEST ['atp_product_id'] );
	$facility_id = trim ( $_REQUEST ['atp_facility'] );
	$sql = "select im.product_id,im.facility_id,  im.inventory_summary_id,  im.stock_quantity,im.available_to_reserved,
		sum(if(ird.status = 'Y',ird.reserved_quantity,0)) as reserved,( im.available_to_reserved + sum(if(ird.status = 'Y',ird.reserved_quantity,0)) -  im.stock_quantity) as diff
		from romeo.inventory_summary im
		left join romeo.order_inv_reserved_detail ird ON  im.product_id = ird.product_id 
		left join ecshop.ecs_order_info oi ON ird.order_id = oi.order_id
		and im.facility_id = ird.facility_id and  im.status_id = ird.status_id
		where im.facility_id = '{$facility_id}' and im.status_id = 'INV_STTS_AVAILABLE' and im.product_id = '{$product_id}'
		-- 排除发货完但是还没还原预定的订单
	    and oi.shipping_status not in(1,2,8,9,12)
		group by im.product_id";
   // Qlog::log('search_atp_detail='.$sql);
   $atp_detail = $db->getAll ($sql);
   $smarty->assign ( 'atp_detail', $atp_detail);
}else if($_REQUEST ['act'] == 'export_reserved_detail'){
	//导出实时已预订量库存
	$product_id = trim ( $_REQUEST ['reserved_product_id'] );
	$facility_id = trim ( $_REQUEST ['reserved_facility'] );
	$sql = "SELECT oi.shipping_status,ird.status,oi.order_id,oi.order_sn,ird.product_id,ird.facility_id,ird.reserved_quantity,ird.reserved_time 
		from romeo.order_inv_reserved_detail ird
		LEFT JOIN ecshop.ecs_order_info oi ON ird.order_id = oi.order_id
		where ird.status = 'Y'
	    and oi.shipping_status not in(1,2,8,9,12)
		AND ird.product_id = '{$product_id}'
		AND ird.facility_id = '{$facility_id}' ";
   // Qlog::log('export_reserved_detail='.$sql);
   $reserved_detail = $db->getAll ($sql);
}else if($_REQUEST ['act'] == 'inv_num'){
	$product_id = trim ( $_REQUEST ['num_product_id'] );
	$facility_id = trim ( $_REQUEST ['num_facility'] );
   $sql = "select 
		   ifnull(g.goods_name,'') as goods_name,ii.product_id,sum(ii.quantity_on_hand_total) as new_num,ii.facility_id,ii.product_id
		FROM
		romeo.inventory_item ii
		left join romeo.product_mapping pm ON ii.product_id = pm.PRODUCT_ID
		left join ecshop.ecs_goods g ON pm.ecs_goods_id = g.goods_id
		where ii.facility_id in('{$facility_id}')
		and ii.product_id in(
		'{$product_id}'
		)
		and ii.status_id = 'INV_STTS_AVAILABLE'
		group by ii.product_id ";
   // Qlog::log('new_num='.$sql);
   $inv_count = $db->getAll ($sql);
   $smarty->assign ( 'inv_count', $inv_count);
}
if($type == '库存汇总CSV'){
	$smarty->assign('storage_list', $storage_list);
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","耗材商品库存汇总清单") . ".csv");
	$out = $smarty->fetch('oukooext/storage_consumable_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
}else if($type == '老库存销售明细'){
	$smarty->assign('old_details', $old_details);
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","老库存出入库明细") . ".csv");
	$out = $smarty->fetch('oukooext/old_detail_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
}else if($type == '新库存销售明细'){
	$smarty->assign('new_details', $new_details);
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","新库存出入库明细") . ".csv");
	$out = $smarty->fetch('oukooext/new_detail_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
}else if($type == '已预订量库存'){
	$smarty->assign('reserved_detail', $reserved_detail);
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","已预订量库存") . ".csv");
	$out = $smarty->fetch('oukooext/reserved_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
}else if($type == '最优快递列表'){
	$smarty->assign('orders', $orders);
	header("Content-type:application/vnd.ms-excel");
	header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","最优快递列表") . ".csv");
	$out = $smarty->fetch('oukooext/best_shipping_csv.htm');
	echo iconv("UTF-8","GB18030", $out);
}else{
    $smarty->assign ( 'facility', get_user_facility ());
    //pp(get_user_facility ());die();
    $smarty->display('oukooext/storage_toolkit.htm');
}
?>