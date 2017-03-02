<?php
/*亨氏业务组，积亨币订单退换货，物流码填入*/
define('IN_ECS', true);

require_once('includes/init.php');

GLOBAL $db;

$party_id = $_SESSION['party_id'];
if($party_id != '65609') {
	die("哪里来的妖怪，胆敢闯我花果山？");
}

$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : false;
$order_sn = isset($_REQUEST['order_sn']) ? trim($_REQUEST['order_sn']) : false ;

$sql = '';
$start_order_time = date("Y-m-d H:i:s", strtotime("-30 days", time()));
// var_dump($start_order_time);
$sql = "select  eoi.order_sn, s.service_id, bho.shipping_code_id, eoi2.order_id as orderId,eoi2.shipping_status,eoi2.pay_status
		from  service s  -- 退货订单 
   		inner JOIN ecs_order_info eoi on eoi.order_id = s.back_order_id
		inner JOIN ecs_order_info eoi2 on eoi2.order_id = s.order_id   -- 原订单
		LEFT join ecs_order_goods eog on eog.order_id = eoi.order_id
		left join romeo.product_mapping p on eog.goods_id = p.ecs_goods_id and eog.style_id = p.ecs_style_id
		LEFT join brand_heinz_order_shipping_code bho on bho.order_id = eoi.order_id and p.product_id = bho.product_id
		where s.party_id=65609  
			and bho.shipping_code_id is NULL and s.service_id >0 
			and eoi.order_sn LIKE '%-t' and s.apply_datetime > '{$start_order_time}'
			and eog.goods_id in ('189262', '189263', '189264', '189265', '189266', '189267', '189268')
			and eoi2.shipping_status = 2 and eoi2.pay_status= 2
			and eoi2.distributor_id in ('1797','1900','1953','2333')
		group by s.order_id limit 30
		";
$order_sn_list = $db->getAll($sql);


$goods_list = '';
$orderID = '';
$return_goods_list ='';

if($act == 'search') {
	$sql = "select eoi.order_id,eoi2.order_sn, eog.goods_name,eog.goods_id, bho.product_id, bho.shipping_code, eoi2.taobao_order_sn, eoi2.order_time
			from ecs_order_info eoi
			left join service s on s.back_order_id = eoi.order_id
			LEFT join ecs_order_goods eog on eog.order_id = s.order_id
			left join ecs_order_info eoi2 on eoi2.order_id = s.order_id
			left join romeo.product_mapping p on eog.goods_id = p.ecs_goods_id and eog.style_id = p.ecs_style_id
			LEFT join brand_heinz_order_shipping_code bho on bho.order_id = s.order_id and bho.product_id= p.product_id
			where eoi.order_sn= '" .$order_sn . "' and bho.shipping_code is NOT NULL";
	$goods_list = $db->getAll($sql);

	$number = 0;
	foreach($goods_list as $key => $goods) {
		$goods_list[$key]['number'] = 0;
		$sql = "select count(shipping_code_id) from brand_heinz_order_shipping_code where shipping_code = '" .$goods['shipping_code'] ."'";
		$number = $db->getOne($sql);
		$goods_list[$key]['number'] = $number;
	}
	
	$sql = "select eoi.order_id,eoi.order_sn, eog.goods_name, eog.goods_number, eog.goods_id , p.product_id ,eoi.order_time
			from ecs_order_info eoi
			LEFT join ecs_order_goods eog on eog.order_id = eoi.order_id
			left join romeo.product_mapping p on eog.goods_id = p.ecs_goods_id and eog.style_id = p.ecs_style_id
			where eoi.order_sn= '" .$order_sn ."'";
	$return_goods_list = $db->getAll($sql);
	$orderID = $return_goods_list[0]['order_id'];	

}


$message = '';
$order_goods = '';

if($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act == 'insert_shipping_code') {
	$order_goods = $_POST['order_goods'];
	$order_id = $_POST['orderId'];

	$number = 0;
	if(!empty($order_goods)) {
		
		$sql="select sum(goods_number) from ecs_order_goods where order_id = '$order_id'";
		$number = $db->getOne($sql);

		if($number != count($order_goods)) {
			$message = 'Sorry,退货数量和物流码数量不等！';
		} else {
			foreach($order_goods as $order_good) {
				if(!in_array($order_good['product_id'], array('100019658','100019659','100019660','100019661','100019662','100019663','100019664','126411672'))) {
					$order_good['shipping_code'] = ''; 
				}
				$sql = "INSERT INTO ecshop.brand_heinz_order_shipping_code
				(order_id,product_id,shipping_code,action_user,created_stamp,last_updated_stamp)
				VALUES
				({$order_id},'{$order_good['product_id']}','{$order_good['shipping_code']}','{$_SESSION['admin_name']}',now(),now())";
					
				$db->query($sql);
				$message = '物流码添加成功，订单ID为：'."<a href=order_edit.php?order_id={$order_id} target=\"_blank\">{$order_id}</a>";
			}	
		} 		
	}	
	
}

$smarty->assign('order_sn_list', $order_sn_list);
$smarty->assign('message', $message);  
$smarty->assign('orderID', $orderID);
$smarty->assign('order_sn', $order_sn);
$smarty->assign('goods_list', $goods_list);
$smarty->assign('return_goods_list', $return_goods_list);
$smarty->display('oukooext/heinz_return_coin_shipping_code_input.html');
?>