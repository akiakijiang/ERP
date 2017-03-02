<?php
/**
 * 销售订单预定、出库追踪
 *
 * @author created by cywang 2013/11/13
 */

define('IN_ECS', true);
require_once('includes/init.php');
$code='';
$msg='';

$start_time = microtime(true);

if(isset($_REQUEST['nazo_number'])){
	$nazo_number=$_REQUEST['nazo_number'];
	$order_id = classifyToOrderID($nazo_number,$code);
}else{
	$order_id = CheckOrderInputInfo($_REQUEST['order_id'], $_REQUEST['order_sn'], $msg);
	$nazo_number=empty($order_id)?'':$order_id;
}

if($order_id){
	$order_info = new OrderInfo();
	$order_info->order_id_ = $order_id;
	$order_info->GetOrderInfo();
	$smarty->assign('order_result', $order_info->order_result_);
	$smarty->assign('order_goods_result', $order_info->order_goods_result_);
	$smarty->assign('order_goods_new_stock', $order_info->order_goods_new_stock_);
}else{
	$smarty->assign('order_id', $_REQUEST['order_id']);
	$smarty->assign('order_sn', $_REQUEST['order_sn']);
}

$cost_time = microtime(true)-$start_time;
$smarty->assign('cost_time',$cost_time);
$smarty->assign('msg', $msg);
$smarty->assign('nazo_number',$nazo_number);
$smarty->assign('code',$code);
$smarty->display('order_check.htm');


class OrderInfo
{   
	public $order_id_;
	public $order_result_;
	public $order_goods_result_;
	public $order_goods_new_stock_;
	
	public function GetOrderInfo()
	{
		global $db;
		// 订单级别预订情况
		$sql_order = "SELECT 
		eoi.order_id, 
		eoi.order_sn, 
		eoi.taobao_order_sn,
		eoi.party_id,
		p.name, 
		eoi.facility_id,
		f.FACILITY_NAME, 
		eoi.order_time, 
		eoi.order_status, 
		eoi.shipping_status, 
		oir.STATUS, 
		oir.RESERVED_TIME 
		from ecshop.ecs_order_info eoi 
		LEFT JOIN romeo.order_inv_reserved oir on eoi.order_id = oir.ORDER_ID 
		LEFT JOIN romeo.party p on convert(eoi.party_id using utf8)= p.PARTY_ID 
		LEFT JOIN romeo.facility f on eoi.facility_id = f.FACILITY_ID 
		where eoi.order_id = {$this->order_id_}
		";
		$this->order_result_ = $db->getRow($sql_order);
		
		
		// 订单商品级别预定情况
		$sql_order_goods = "SELECT 
			eog.rec_id, eog.goods_id, eog.goods_name, eog.goods_number,eog.status_id, 
			oird.product_id, oird.reserved_quantity, oird.status, oird.RESERVED_TIME, 
			isum.stock_quantity, isum.available_to_reserved 
		from ecshop.ecs_order_info eoi 
		LEFT JOIN ecs_order_goods eog on eoi.order_id = eog.order_id 
		LEFT JOIN romeo.order_inv_reserved_detail oird on convert(eog.rec_id using utf8) = oird.order_item_id 
		LEFT JOIN romeo.inventory_summary isum on isum.product_id = oird.product_id and isum.facility_id = oird.facility_id and isum.status_id = eog.status_id 
		where eoi.order_id = {$this->order_id_}
		";
		$this->order_goods_result_ = $db->getAll($sql_order_goods);
		
		// 订单商品级别新库存出库情况
		$sql_order_goods_new_stock = "SELECT eog.rec_id, eog.goods_id, eog.goods_name, eog.goods_number,eog.status_id as eog_status_id, 
		iid.inventory_item_detail_id, iid.quantity_on_hand_diff, iid.available_to_promise_diff, iid.cancellation_flag, iid.created_stamp, 
		ii.serial_number, ii.status_id, ii.inventory_item_acct_type_id, ii.inventory_item_type_id, f.facility_name, ii.facility_id, 
		ii.quantity_on_hand, ii.available_to_promise, ii.quantity_on_hand_total, ii.available_to_promise_total 
		from ecshop.ecs_order_info eoi 
		LEFT JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id 
		LEFT JOIN romeo.inventory_item_detail iid on iid.order_goods_id = convert(eog.rec_id using utf8)
		LEFT JOIN romeo.inventory_item ii on ii.inventory_item_id = iid.inventory_item_id 
		LEFT JOIN romeo.facility f on ii.facility_id = f.FACILITY_ID 
		where eoi.order_id = {$this->order_id_}
		";
		$this->order_goods_new_stock_ = $db->getAll($sql_order_goods_new_stock);

	}
   
}

function CheckOrderInputInfo($order_id, $order_sn, &$msg)
{
	global $db;
	if(!$order_id && !$order_sn){
		$msg = '未输入正确的order_id或order_sn';
		return 0;
	}else if($order_id && $order_sn){
		if($db->getOne("SELECT 1 from ecshop.ecs_order_info where order_id = {$order_id} and order_sn = '{$order_sn}' limit 1")){
			return $order_id;
		}else{
			$msg = 'order_id与order_sn不符';
			return 0;
		}
	}else if($order_id){
		return $order_id;
	}else{
		$order_id = $db->getOne("SELECT order_id from ecshop.ecs_order_info where order_sn = '{$order_sn}' limit 1");
		if($order_id){
			return $order_id;
		}else{
			$msg = '无此order_sn';
			return 0;
		}
	}
}

function classifyToOrderID($nazo_number,&$code){
	global $db;
	if(empty($nazo_number)){
		$code='<p style="color:red">无订单信息</p>';
		return 0;
	}
	$sql_A="SELECT order_id 
	FROM ecshop.ecs_order_info 
	WHERE 
		order_id='{$nazo_number}'
	";
	$sql_B="SELECT order_id 
	FROM ecshop.ecs_order_info 
	WHERE 
		order_sn='{$nazo_number}'
	";
	$sql_C="SELECT order_id 
	FROM ecshop.ecs_order_info 
	WHERE 
		taobao_order_sn='{$nazo_number}'
	";
	$sql="({$sql_A})union({$sql_B})union({$sql_C})";
	$order_ids=$db->getCol($sql);
	if($order_ids){
		if(1==count($order_ids)){
			return $order_ids[0];
		}else{
			$code="<div>共计发现 ".count($order_ids)." 个目标</div>";
			foreach ($order_ids as $order_id) {
				$code.="<div>
					<p>
						[ $order_id ]
      					打开<a href=\"order_edit.php?order_id={$order_id}\" target=\"_blank\">详情页</a>
      					打开<a href=\"sale_support/postsale_order_info_box.php?order_id={$order_id}\" target=\"_blank\">售后情报</a>
    				</p>
				</div>";
			}
			return 0;
		}
	}else{
		//FALSE
		$code='<p style="color:red">订单搜查舰沉默</p>';
		return 0;
	}
}

?>