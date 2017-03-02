<?php
define('IN_ECS', true);

require('includes/init.php');

$order_sn = $_REQUEST['order_sn'];

$sql = "SELECT oi.order_id, oi.order_sn, oi.consignee, oi.mobile, oi.zipcode, oi.address, oi.party_id, oi.distributor_id,oi.currency,
               oi.pay_name, oi.shipping_name, oi.goods_amount, oi.shipping_fee, oi.bonus, integral_money,
               if(oi.shipping_time = 0, oi.order_time, FROM_UNIXTIME(oi.shipping_time)) as order_time,
               if((oi.inv_payee is NULL or oi.inv_payee = ''), oi.consignee, oi.inv_payee) as inv_payee,
               (oi.goods_amount + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee) AS total_fee,
               (oi.goods_amount + oi.shipping_fee + oi.insure_fee + oi.pay_fee + oi.pack_fee + oi.card_fee + oi.bonus + oi.integral_money) AS final_fee ,
               ifnull(max(iid.created_stamp), oi.order_time) as send_time
        FROM ecshop.ecs_order_info oi 
		left join romeo.inventory_item_detail iid on convert(oi.order_id using utf8) = iid.order_id
        left join romeo.inventory_item ii on ii.inventory_item_id=iid.inventory_item_id
        WHERE oi.order_sn = '%s' AND ii.status_id in('INV_STTS_AVAILABLE','INV_STTS_USED','INV_STTS_DEFECTIVE')
        GROUP BY oi.order_id ";

$order = $db->getRow(sprintf($sql, $order_sn));

//若是外贸组+香港oppo+香港平世则选择主分销商名
if($order['party_id']=='65536' || $order['party_id']=='65566' || is_jjshouse($order['party_id'])){
	$distibutor_id=$order['distributor_id'];
    $sql="SELECT md.type,md.name
      FROM ecshop.main_distributor md,ecshop.distributor d
      WHERE 
      md.main_distributor_id = d.main_distributor_id
      AND d.distributor_id =  '{$distibutor_id}'";
    $tmp=$db->getRow($sql);
    if($tmp['type']=='fenxiao')
	  $order['inv_payee']=$tmp['name'];
}


switch($order['currency']){
	case 'EUR':$currency='€';break;
	case 'USD':$currency='$';break;
	case 'GBP':$currency='￡';break;
	case 'CHF':$currency='SF';break;
	case 'NZD':$currency='$NZ';break;
	case 'HKD':$currency='HK$';break;
	case 'CAD':$currency='C$';break;
	case 'AUD':$currency='$A';break;
	case 'DKK':case 'SEK':$currency='kr';break;
	case 'JPY':$currency='￥';break;
	case 'NOK':$currency='Nkr';break;
	case 'SGD':$currency='S$';break;
	default:$currency='¥';
}

function getNotFirst($str){
	$str=substr($str,3,strlen($str));
	return $str;
}

$sql = "
	SELECT og.*, c.cat_name 
	FROM {$ecs->table('order_goods')} og 
	LEFT JOIN {$ecs->table('goods')} g ON og.goods_id = g.goods_id 
	LEFT JOIN {$ecs->table('category')} c ON g.cat_id = c.cat_id
	WHERE order_id = {$order['order_id']}";

$goods_list = $db->getAll($sql);
foreach ($goods_list as $key=>$goods) {
	$goods_list[$key]['goods_amount'] = $goods['goods_number'] * $goods['goods_price'];
	$goods_list[$key]['format_goods_amount'] = price_format($goods['goods_number'] * $goods['goods_price']);
	$goods_list[$key]['format_goods_price'] = price_format($goods['goods_price']);
	
	$goods_list[$key]['format_goods_amount']=$currency.getNotFirst($goods_list[$key]['format_goods_amount']);
	$goods_list[$key]['format_goods_price']=$currency.getNotFirst($goods_list[$key]['format_goods_price']);
	
	// 过虑C2C商品,外贸组+香港oppo+香港平世的除外
	if(!($order['party_id']=='65536' || $order['party_id']=='65566' || is_jjshouse($order['party_id']))){
		$sql = "select ii.UNIT_COST * (-iid.QUANTITY_ON_HAND_DIFF) as cost 
				from romeo.inventory_item_detail iid 
				 inner join romeo.inventory_item ii 
				  on iid.INVENTORY_ITEM_ID = ii.INVENTORY_ITEM_ID 
				where iid.ORDER_GOODS_ID =  '{$goods['rec_id']}'";
		$cost = $db->getOne($sql);
		$order['goods_amount'] -= $cost;
		$order['total_fee'] -= $cost;
		$order['final_fee'] -= $cost;
		unset($goods_list[$key]);
	}
}

$order['shipping_time'] = $order['shipping_time'] ? date("Y-m-d H:i:s", $order['shipping_time']) : '';
$order['confirm_time'] = $order['confirm_time'] ? date("Y-m-d H:i:s", $order['confirm_time']) : '';
$order['format_goods_amount'] = price_format($order['goods_amount']);
$order['format_shipping_fee'] = price_format($order['shipping_fee']);
$order['format_total_fee'] = price_format($order['total_fee']);
$order['format_bonus'] = price_format(abs($order['bonus']));
$order['format_integral_money'] = price_format(abs($order['integral_money']));
$order['format_final_fee'] = $order['final_fee'] > 0 ? price_format($order['final_fee']) : price_format(0);

$order['format_goods_amount']=$currency.getNotFirst($order['format_goods_amount']);
$order['format_shipping_fee']=$currency.getNotFirst($order['format_shipping_fee']);
$order['format_total_fee']=$currency.getNotFirst($order['format_total_fee']);
$order['format_bonus']=$currency.getNotFirst($order['format_bonus']);
$order['format_integral_money']='€'.getNotFirst($order['format_integral_money']);
$order['format_final_fee']=$currency.getNotFirst($order['format_final_fee']);

$smarty->assign('order', $order);
$smarty->assign('goods_list', $goods_list);
$smarty->display('oukooext/print_sale_bill.htm');
?>