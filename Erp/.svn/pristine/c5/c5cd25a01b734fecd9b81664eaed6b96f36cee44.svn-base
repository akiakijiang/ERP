<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH.'/includes/helper/array.php');
require_once(ROOT_PATH.'/includes/lib_order.php');
require_once(ROOT_PATH.'admin/distribution.inc.php');

//验证权限
admin_priv('order_split');
////只有以下业务组开通此功能：中粮、亨氏、雀巢 、金佰利
//$limitParty = array(65625,65553,65558,65609,65614);
//if(!in_array($_SESSION['party_id'],$limitParty)){
//	die("暂时只有部分限制的业务组才能使用，其他业务组请等待后续开放~~");
//}

define('ERPSYNC_WEBSERVICE_URL',$erpsync_webservice_url);
$order_id = $_REQUEST['order_id'];
global $db;

if(!isset($_REQUEST['order_id'])){
	sys_msg("订单信息错误");
}

$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
//假如act为拆分订单，就要生成订单
if($act == 'split_order' && isset($_REQUEST['check'])){
	//判断原始订单跟提交的订单里面的所有商品之和的商品是否相等，不能发生部分拆分成新订单，部分在原订单中被取消了
	$parent_order_id = $_REQUEST['order_id'];
	$parent_order_sn = $_REQUEST['order_sn'];
	//判断拆分之后的订单商品数量之和与原始订单的订单商品数量之和是否相等
		
	$sql_order_status = "select order_status from ecshop.ecs_order_info where order_id = {$parent_order_id}";
	$order_status = $db->getOne($sql_order_status);	
	if(!check_data($_REQUEST['product_list'],$parent_order_id)){
		$msg = "提交订单商品数量之和与原订单商品数量之和不等，不能拆分";
	}else if($order_status == 2){
		$msg = "订单已经被拆分 作废，不能拆分";
	}else{
		$index = 0;
		$db->start_transaction();
		try{
			$first_child_order = true;
			$osn_list= array();
			$parent_order = $db->getRow("SELECT eoi.*,oa.attr_value DISCOUNT_FEE from ecshop.ecs_order_info eoi left join ecshop.order_attribute oa on eoi.order_id = oa.order_id and oa.attr_name = 'DISCOUNT_FEE' where eoi.order_id = $parent_order_id");
			$child_order_bonus_sum = 0;
			$goods_number = 0;
			$parent_goods_number = $db->getOne("SELECT sum(goods_number) from ecshop.ecs_order_goods where order_id = '{$order_id}'");
			
			//通过bonus-sum（good[discount_fee]）来求order[discount_fee]
			$sql_goods_discount_fee_amount = "SELECT sum(ifnull(oga.value,0)) from ecshop.ecs_order_goods eog
												left join ecshop.order_goods_attribute oga on oga.order_goods_id = eog.rec_id and oga.name = 'DISCOUNT_FEE'
												where eog.order_id = '{$order_id}'
												group by eog.order_id";
			$goods_discoung_fee_amount = $db->getOne($sql_goods_discount_fee_amount);			
			foreach ($_REQUEST['check'] as $order_facility_id => $facility_goods) {				
				//check下面的为一个个的仓库，每个仓库如果有一个以上check=on，就需要生成一个订单
				$need_create_order = false;
				$split_order_goods = array();
				$child_order_goods_amount = 0;
				foreach ($facility_goods as $goods) {
					if(in_array($order_facility_id."_".$goods['product_id']."_".$goods['order_goods_id'],$_REQUEST['product_list'])){
						$sql_goods = "SELECT oga.value tc_code,oga1.value discount_fee,eog.*
								from ecshop.ecs_order_goods eog
								left join ecshop.order_goods_attribute oga on oga.order_goods_id = eog.rec_id and oga.name = 'OUTER_IID'
								left join ecshop.order_goods_attribute oga1 on oga1.order_goods_id = eog.rec_id and oga1.name = 'DISCOUNT_FEE'
								where  eog.rec_id = '{$goods['order_goods_id']}'";
						$tc_discountfee = $db->getRow($sql_goods);
						$goods['tc_code'] = $tc_discountfee['tc_code'];
						$goods['discount_fee'] = $tc_discountfee['discount_fee'];
						$goods['group_code'] = $tc_discountfee['group_code'];
						$goods['group_name'] = $tc_discountfee['group_name'];
						$goods['group_number'] = $tc_discountfee['group_number'];
						//$goods['discount_fee'] = $tc_discountfee['discount_fee'];
						$goods['pay_amount'] = $tc_discountfee['pay_amount'];
						$goods['out_order_goods_id'] = $tc_discountfee['out_order_goods_id'];
						$split_order_goods[] = $goods;
						$child_order_goods_amount += $goods['need_number'] * $goods['goods_price'];
						$goods_number = $goods_number + $goods['goods_number'];
					}
				}
				//通过bonus-sum（good[discount_fee]）来求order[discount_fee]
				$parent_order['DISCOUNT_FEE'] = 0 - $parent_order['bonus'] - $goods_discoung_fee_amount;							
				if($parent_order['goods_amount'] > 0){
					$child_order_bonus = number_format($child_order_goods_amount/$parent_order['goods_amount']*$parent_order['DISCOUNT_FEE'],2,'.','');
				}else{
					$child_order_bonus = 0;
				}
				$child_order_bonus_sum += $child_order_bonus;
				//处理除不尽的情况
				if($goods_amount == $parent_goods_number && $child_order_bonus+$child_order_bonus_sum < $parent_order['DISCOUNT_FEE']){
					$child_order_bonus = $parent_order['DISCOUNT_FEE'] - $child_order_bonus_sum;
				}
				//根据传入的订单信息，生成子订单
				if(!empty($split_order_goods)) {
					if($parent_order['taobao_order_sn'] != ''){
						$sql = "select count(*) from ecshop.ecs_order_info where taobao_order_sn like '{$parent_order['taobao_order_sn']}%'";
						if($_SESSION['party_id'] == "65609" || $_SESSION['party_id'] == "65553" || $_SESSION['party_id'] == "65558" || $_SESSION['party_id'] == "65625"){
							$taobao_order_sn_ = $parent_order['taobao_order_sn']."-".($db->getOne($sql)+$index);	
						}else{
							$taobao_order_sn_ = $parent_order['taobao_order_sn']."-".($db->getOne($sql));
						}
					}
					$osn_list[] = create_split_order($order_facility_id,$parent_order_id,$split_order_goods,$first_child_order,$child_order_bonus,$taobao_order_sn_);					
					$index ++;
					foreach($osn_list as $key=>$item) {
						if($item['message']) {
							$msg=$item['message'];
							break;
						}
						$order_sn_list[] = $item['order_sn'];
					}
					$first_child_order = false;
				}				
			}

			//自订单完全生成之后，原订单取消，并且添加order_action
			$action_list = "原订单已被取消，并被拆分为：". implode(',', array_unique($order_sn_list));
			deal_split_org_order($parent_order_id,$action_list);
			$db->commit();
	//		$msg="已拆分成了如下几个订单：".db_create_in($osn_list);
		}catch(Exception $e){
			$db->rollback();
			$msg="订单拆分失败,失败原因为".$e->getMessage();
		}		
		$sql = "SELECT eoi.*,f.facility_name
			from ecshop.ecs_order_info eoi
			inner join romeo.facility f on f.facility_id = eoi.facility_id
			where eoi.order_sn".db_create_in($order_sn_list);
		$orders = $db->getAll($sql);
		
		if(!empty($orders)) {
			if(!check_number($order_sn_list,$order_id)) {
				$db->rollback();
				$msg="订单拆分失败,失败原因为：拆分前后商品数量不等";
			} else {
				$smarty->assign("orders",$orders);
			}
		}		
	}
	$smarty->assign("parent_order_sn",$parent_order_sn);
	$smarty->assign("parent_order_id",$parent_order_id);
	$smarty->assign("message",$msg);
	$smarty->display("order/order_split_result.htm");
	exit ;
}

if(!$act && isset($_REQUEST['order_id'])){
	$sql = "SELECT eoi.order_id,eoi.order_sn, eoi.taobao_order_sn, eoi.facility_id, eoi.order_status, eoi.shipping_status, f.facility_name
		from ecshop.ecs_order_info eoi
		inner join romeo.facility f on f.facility_id = eoi.facility_id
		where eoi.order_id = '{$order_id}'";
	$row = $db -> getRow($sql);
	
	$smarty->assign('order_sn',$row['order_sn']);
	$smarty->assign('taobao_order_sn',$row['taobao_order_sn']);
	$smarty->assign('org_facility_id',$row['facility_id']);
	$smarty->assign('facility_name',$row['facility_name']);
	$smarty->assign('order_id',$row['order_id']);
	
	//查询该订单是否已经被拆单过了，如果拆单过了，就不能显示拆单界面了
	$sql = "SELECT eor.order_sn,eor.order_id
		from ecshop.order_relation eor
		inner join ecshop.ecs_order_info eoi on eoi.order_id = eor.parent_order_id
		where eoi.order_type_id = 'SALE'
		and eoi.order_id = '{$order_id}'
	";
	$child_orders = $db->getAll($sql);
	
	$sql_goods_discount_fee_amount = "SELECT sum(ifnull(oga.value,0)) from ecshop.ecs_order_goods eog
												left join ecshop.order_goods_attribute oga on oga.order_goods_id = eog.rec_id and oga.name = 'DISCOUNT_FEE'
												where eog.order_id = '{$order_id}'
												group by eog.order_id";
	$goods_discoung_fee_amount = $db->getOne($sql_goods_discount_fee_amount);
	$sql_order_bonus = "SELECT eoi.bonus,oa.attr_value from ecshop.ecs_order_info eoi " .
			"left join ecshop.order_attribute oa on oa.order_id = eoi.order_id and oa.attr_name = 'DISCOUNT_FEE' " .
			"where eoi.order_id = {$order_id}";	
	$order_bonus = $db->getRow($sql_order_bonus);
	$bonus = $order_bonus['bonus'];
	$order_discount = $order_bonus['attr_value'];
	$check_discount = true;
	if(round(-$bonus,6) != round($order_discount + $goods_discoung_fee_amount,6)) {
//		exit("商品级别优惠券 + 订单级别优惠券 ！= -1*抵用券，请修改优惠券之后再拆分！");
//		$check_discount = "商品级别优惠券 + 订单级别优惠券 ！= -1*抵用券，请修改优惠券之后再拆分！";
		$check_discount = false;
	}
	$smarty->assign('check_discount',$check_discount);
	
	if(count($child_orders)>1){
		$smarty->assign('can_split',$row['order_status'] == 0 ? true:false);//original as 2(Cancelled) now to be 0(Unconfirmed)
		$smarty->assign('child_orders',$child_orders);
		$smarty->assign('have_child_order',true);
	}else{
		$smarty->assign('can_split',$row['order_status'] == 0 ? true:false);//original as 2(Cancelled) now to be 0(Unconfirmed)
		$smarty->assign('have_child_order',false);
	}
	
	$sql = "SELECT pm.product_id,eog.goods_number need_number,pm.product_id,eog.goods_id,eog.style_id,eog.goods_price,eoi.facility_id,eog.rec_id order_goods_id,CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, '')))as goods_name
		from ecshop.ecs_order_info eoi
		inner join ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
		left join romeo.product_mapping pm on pm.ecs_goods_id = eog.goods_id and pm.ecs_style_id = eog.style_id
		left join ecshop.ecs_goods g on g.goods_id = eog.goods_id
		LEFT JOIN ecshop.ecs_goods_style AS gs ON gs.style_id = pm.ecs_style_id and gs.goods_id = pm.ecs_goods_id and gs.is_delete=0
		LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id 
		where eoi.order_id = {$order_id}
	";
//	pp($sql);
	$product_number = $db->getAll($sql);
	$products = Helper_Array::getCols($product_number, 'product_id');
	$product_number = Helper_Array::groupBy($product_number, 'product_id');
	

	//获取该商品的实际库存
	$sql = "SELECT g.goods_id, ii.product_id,g.goods_party_id as party_id,
	        IFNULL(gs.style_id, 0) AS style_id,f.facility_name,f.facility_id,
			CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, '')))as goods_name,
			concat_ws('_',g.goods_id,IFNULL(gs.style_id, 0),ii.facility_id) as goods_style_facility_id,
			sum(ii.QUANTITY_ON_HAND_TOTAL) as storage_count
		FROM ecshop.ecs_goods AS g
		    left join ecshop.ecs_category c on g.cat_id = c.cat_id
			LEFT JOIN romeo.product_mapping AS pm ON g.goods_id = pm.ecs_goods_id
			LEFT JOIN ecshop.ecs_goods_style AS gs ON gs.style_id = pm.ecs_style_id and gs.goods_id = pm.ecs_goods_id and gs.is_delete=0
			LEFT JOIN ecshop.ecs_style AS s on s.style_id = gs.style_id 
			LEFT JOIN romeo.inventory_item AS ii ON pm.product_id = ii.product_id
			LEFT JOIN romeo.facility f ON ii.facility_id = f.facility_id
		WHERE " . facility_sql ( 'ii.facility_id' ) . "
			and g.is_delete = 0
			and g.goods_party_id = '{$_SESSION['party_id']}'
			AND ii.STATUS_ID IN ('INV_STTS_AVAILABLE') 
			AND ii.product_id " . db_create_in($products)."
		GROUP BY goods_style_facility_id
	";
//	pp($sql);
    $refs	= array();	
//    $goods_list = $db->getAllRefby($sql,array('goods_style_facility_id'),$ref_fields, $refs, false); 
	$refs = Helper_Array::groupBy($db->getAll($sql), 'goods_style_facility_id');
//  pp($refs);
    // 查询出符合条件的每一种商品已确定订单总数量和未确定订单总数
	$sql = "SELECT 
	        CONCAT_WS('_', og.goods_id, og.style_id, o.facility_id) AS goods_style_facility_id, 
	        sum(og.goods_number) as order_number
		FROM
	       ecshop.ecs_order_info o force index(PRIMARY,order_info_multi_index) 
	       inner join ecshop.ecs_order_goods AS og ON o.order_id = og.order_id
	       STRAIGHT_JOIN romeo.product_mapping pm on pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id    
		   left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
		WHERE
			o.party_id = {$_SESSION['party_id']} and o.order_time  >=date_add(now(),interval -1 month)
			AND o.order_status in (0,1) AND o.shipping_status IN (0,13)                                       -- 还未发货的订单
            AND (
            	   ( o.order_type_id = 'SALE' AND  o.pay_status = 2)                                      -- 销售订单 (已确认|已支付|先款后货)
                    OR o.order_type_id = 'RMA_EXCHANGE' OR o.order_type_id = 'SHIP_ONLY'                  -- 或者为换货订单，补寄订单 
                )
            AND ". facility_sql("o.facility_id") ."
            AND pm.product_id " . db_create_in($products)." AND iid.inventory_item_detail_id is null -- 不存在出库记录
			AND og.goods_id != 27080                         -- 排除自己库存和测试商品
		group BY goods_style_facility_id
	";
//	pp($sql);
	$order_value_list = array();
//	$db->getAllRefby($sql, array('goods_style_facility_id'), $order_key_list, $order_value_list, false);
	$order_value_list = Helper_Array::groupBy($db->getAll($sql), 'goods_style_facility_id');
//	pp($order_value_list);
	
	//取得每一种商品的-gt需出库数
    $sql = "SELECT 
              sum(temp.num) as supplier_return_number,
              CONCAT_WS('_', temp.goods_id, temp.style_id,temp.facility_id) AS goods_style_facility_id
        from 
		    (  select 
		             eog.goods_number + sum(ifnull(iid.quantity_on_hand_diff,0)) as num,
		             eog.goods_id,eog.style_id,eoi.facility_id
    		 	FROM  
    		 		romeo.supplier_return_request srr
			    STRAIGHT_JOIN romeo.supplier_return_request_gt srrg ON srr.supplier_return_id = srrg.supplier_return_id
			    STRAIGHT_JOIN ecshop.ecs_order_info AS eoi ON srrg.SUPPLIER_RETURN_GT_SN = eoi.order_sn
			    INNER JOIN ecshop.ecs_order_goods AS eog ON eoi.order_id = eog.order_id
    		 	--	romeo.supplier_return_request srr force index(party_id)
				-- inner join  romeo.supplier_return_request_gt srrg   on srr.supplier_return_id = srrg.supplier_return_id 
				-- inner join  ecshop.ecs_order_info AS eoi on srrg.SUPPLIER_RETURN_GT_SN = eoi.order_sn
		   		-- inner join ecshop.ecs_order_goods AS eog ON eoi.order_id = eog.order_id 
		   		 inner join romeo.product_mapping pm on pm.ecs_goods_id = eog.goods_id and pm.ecs_style_id = eog.style_id and pm.product_id  ".db_create_in($products)."
		    	 left  join  romeo.inventory_item_detail iid on iid.order_goods_id = convert(eog.rec_id using utf8)
		        where 
		        	srrg.SUPPLIER_RETURN_GT_SN is not null 
	        	and " . facility_sql ( 'eoi.facility_id' ) . "
		        AND eoi.party_id = '{$_SESSION['party_id']}'
		        AND srr.party_id='{$_SESSION['party_id']}'
	            AND   eoi.order_type_id in ('SUPPLIER_RETURN','SUPPLIER_TRANSFER' ) 
                AND   srr.status in ('EXECUTING','CREATED')
                and   srr.CHECK_STATUS != 'DENY'
                AND   eog.STATUS_ID IN ('INV_STTS_AVAILABLE') 
			   GROUP BY eog.rec_id,eog.status_id
		     )  as temp    
        GROUP BY goods_style_facility_id 
    ";
//   pp($sql);
    $supplier_return_number_value_list = array();
//    $db->getAllRefby($sql, array('goods_style_facility_id'), $supplier_return_number_key, $supplier_return_number_value_list,false);
	$supplier_return_number_value_list = Helper_Array::groupBy($db->getAll($sql), 'goods_style_facility_id');
//    pp($supplier_return_number_value_list);
    //数据组合
    $goods_list = array();
    $goods_no_inventory_list = array(); 
    $goods_no_inventory_list_already = array(); 
	$delete_product_array=array();
	$goods_no_inventory_list=array();
	foreach ($refs as $goods_style_facility_id=>$goods) {
//		var_dump($goods);
		foreach ($product_number[$goods[0]['product_id']] as $product_good) {			
			$good['goods_id'] = $goods[0]['goods_id'];
			$good['product_id'] = $goods[0]['product_id'];
			$good['party_id'] = $goods[0]['party_id'];
			$good['style_id'] = $goods[0]['style_id'];
			$good['facility_name'] = $goods[0]['facility_name'];
			$good['facility_id'] = $goods[0]['facility_id'];
			$good['goods_name'] = $goods[0]['goods_name'];
			$good['goods_style_facility_id'] = $goods[0]['goods_style_facility_id'];
			$good['goods_price'] = $product_good['goods_price'];
			$good['need_number'] = $product_good['need_number'];
			$good['order_goods_id'] = $product_good['order_goods_id'];
		    $good['available'] = $goods[0]['storage_count'] - $order_value_list[$goods_style_facility_id][0]['order_number'] - $supplier_return_number_value_list[$goods_style_facility_id][0]['supplier_return_number'];
		    $goods_list[] = $good;
			
		    if($good['available'] > $good['need_number']){
		    	$delete_product_array[] = $good['product_id'];
//		    	unset($product_number[$good['product_id']]);
	    	}else{
	    		$goods_no_inventory_list[] = $good;
	    	}
		}
		
	}
	//删除订单商品
	foreach ($delete_product_array as $delete_product) {
		unset($product_number[$delete_product]);
	}

	$goods_list_new = array();
	foreach ($goods_list as $key => $value) {
		if(in_array($value['product_id'],$delete_product_array) ){
			$goods_list_new[] = $value;
		}
	}
	$goods_list = $goods_list_new;
	unset($goods_list_new); 
	$goods_no_inventory_list_new = array();
	foreach ($goods_no_inventory_list as $key => $value) {
		if(!in_array($value['product_id'],$delete_product_array)){
			$goods_no_inventory_list_new[] = $value; 
		}
	}
	$goods_no_inventory_list = $goods_no_inventory_list_new;
	unset($goods_no_inventory_list_new); 

//	if(count($product_number) > 0){
	$smarty->assign('product_number',$product_number);
//	}
	$goods_list = Helper_Array::groupBy($goods_list,'order_goods_id');
	$goods_no_inventory_list = Helper_Array::groupBy($goods_no_inventory_list,'order_goods_id'); 
	$smarty->assign('goods_list',$goods_list);
	$smarty->assign('goods_no_inventory_list',$goods_no_inventory_list);
	$smarty->display('order/order_split.htm');

}

function create_split_order($facility_id,$parent_order_id,$split_order_goods,$first_child_order,$child_order_bonus,$taobao_order_sn_){
	global $db;
	$results = array();
	$message = "";
	$parent_order = $db->getRow("select eoi.*,oa.attr_value from ecshop.ecs_order_info eoi left join ecshop.order_attribute oa on oa.order_id = eoi.order_id and oa.attr_name = 'discount_fee' where eoi.order_id = $parent_order_id");
	//整合order信息，大部分值取自parent_order.
	$order['distributor_id'] = $parent_order['distributor_id'];
	$order['consignee'] = $parent_order['consignee'];
	$order['tel'] = $parent_order['tel'];
	$order['mobile'] = $parent_order['mobile'];
	$order['province'] = $parent_order['province'];
	$order['city'] = $parent_order['city'];
	$order['district'] = $parent_order['district'];
	$order['address'] = $parent_order['address'];
	$order['postscript'] = $parent_order['postscript'];
	$order['pay_name'] = $parent_order['pay_name'];
	$order['pay_id'] = $parent_order['pay_id'];
	$order['shipping_id'] = $parent_order['shipping_id'];
	$order['shipping_proxy_fee'] = $parent_order['shipping_proxy_fee'];
	$order['pack_fee'] = $parent_order['pack_fee'];
	$order['user_id'] = $parent_order['user_id'];
	$order['party_id'] = $parent_order['party_id'];
	$order['currency'] = $parent_order['currency'];
	$order['fenxiao_type'] = $parent_order['fenxiao_type'];
	$order['discount_fee'] = $child_order_bonus;
	$order['source_type'] = $order['outer_type'] =  $parent_order['source_type'];
	$order['pay_time'] = $parent_order['pay_time'];
	
	//部分信息需要特殊设置
	$order['pay_status'] = $parent_order['pay_status']; // 订单的付款状态 与 原订单相同
	$order['order_status'] = 0; //待确认
	$order['shipping_status'] = 0; //待发货
	$order['facility_id'] = $facility_id;
	//当父订单的淘宝订单号为空时，也为空处理
	if($parent_order['taobao_order_sn'] == ''){
		$order['taobao_order_sn'] = '';
	}else{
//		$sql = "select count(*) from ecshop.ecs_order_info where taobao_order_sn like '{$parent_order['taobao_order_sn']}%'";
//		$order['taobao_order_sn'] = $parent_order['taobao_order_sn']."-".($db->getOne($sql)+$index);	
		$order['taobao_order_sn'] = $taobao_order_sn_;
	}
	
	//将运费设置到第一个拆的订单上
	$order['shipping_fee'] = $first_child_order?$parent_order['shipping_fee']:0;
	$order_goods = array();
	$child_goods_bonus = 0;
	foreach ($split_order_goods as $goods) {
//		$order_goods[$goods['goods_id']."_".$goods['style_id']]['goods_id'] = $goods['goods_id'];
//		$order_goods[$goods['goods_id']."_".$goods['style_id']]['style_id'] = $goods['style_id'];
//		$order_goods[$goods['goods_id']."_".$goods['style_id']]['goods_number'] = $goods['need_number'];
//		$order_goods[$goods['goods_id']."_".$goods['style_id']]['price'] = $goods['goods_price'];
		$order_goods[$goods['order_goods_id']]['goods_id'] = $goods['goods_id'];
		$order_goods[$goods['order_goods_id']]['style_id'] = $goods['style_id'];
		$order_goods[$goods['order_goods_id']]['goods_number'] = $goods['need_number'];
		$order_goods[$goods['order_goods_id']]['price'] = $goods['goods_price'];
		$order_goods[$goods['order_goods_id']]['tc_code'] = $goods['tc_code'];
		$order_goods[$goods['order_goods_id']]['discount_fee'] = $goods['discount_fee'];
		
		$order_goods[$goods['order_goods_id']]['group_code'] = $goods['group_code'];
		$order_goods[$goods['order_goods_id']]['group_name'] = $goods['group_name'];
		$order_goods[$goods['order_goods_id']]['group_number'] = $goods['group_number'];
		$order_goods[$goods['order_goods_id']]['pay_amount'] = $goods['pay_amount'];
		$order_goods[$goods['order_goods_id']]['out_order_goods_id'] = $goods['out_order_goods_id'];
		$child_goods_bonus = $child_goods_bonus + $goods['discount_fee'];
	}
	$order['bonus'] = -($child_order_bonus + $child_goods_bonus);

	$goods = "";
	$order['order_goods_num'] = count($order_goods);
	$num = 0;
	if(!empty($order_goods)){
		foreach($order_goods as $key=>$value){
			$temp="{'rec_id':'".$key."',";
			foreach($value as $key=> $val){
				if($key == 'out_order_goods_id'){
					$temp =$temp."'".$key."':'".$val."'";
				}else{
					if($key == 'discount_fee' && empty($val)){
						$temp =$temp."'".$key."':'0',";
					}else{
						$temp =$temp."'".$key."':'".$val."',";
					}
				}
				
			}
			$num ++;
			if($num == count($order_goods))
				$goods=$goods.$temp.'}';
			else{
				$goods=$goods.$temp.'},';
			}
		}
	}
	
	
 	
 	if($order['party_id'] == "65609" || $_SESSION['party_id'] == "65553" || $_SESSION['party_id'] == "65558" || $_SESSION['party_id'] == "65625"){
 		$order['order_goods'] = '['.$goods.']';
		$order['party_id'] = $_SESSION['party_id'];
		$order['admin_name'] = "admin";
		$order['taobao_user_id'] = $parent_order['nick_name'];
		$order_json = json_encode($order);
 		Qlog::log("order_json:".$order_json);
//		print_r($order);
		try{						  
			$client = new SoapClient(ERPSYNC_WEBSERVICE_URL.'GenerateSaleOrderService?wsdl');
			$response = $client->GenerateSaleOrder(array("order"=>$order_json));
			$res = (array)$response;
			$osn = (array)$res['return'];
//			print_r($response);
		}catch(Exception $e){
			$message = 'Create Order Exception,请联系ERP！<br/>'.$e->getMessage();
		}
 	}else{
		//准备好order和order_goods信息之后，准备生成订单
		$osn = distribution_generate_sale_order($order, $order_goods, $parent_order['carrier_id'], $parent_order['order_type'], $message);
 	}
 	
 	if($osn['error']) {
 		$results['message'] = $osn['error'];
 	} else {
 		if($order['party_id'] == "65609" || $_SESSION['party_id'] == "65553" || $_SESSION['party_id'] == "65558" || $_SESSION['party_id'] == "65625"){
 			$order_id = $osn['order_id'];
 		}else{
 			$order_id = $db->getOne("select order_id from ecshop.ecs_order_info where order_sn = '{$osn['order_sn']}'");
 		}
 		
	    //原订单生成逻辑，可能报表系统要用
	    if ($osn['order_sn']) {
	    	$sql_fenxiao_type = "select attr_value from ecshop.order_attribute where order_id = '{$order_id}' and attr_name = 'FENXIAO_TYPE'";
	    	$fenxiao_type = $db->getOne($sql_fenxiao_type);
	        if($fenxiao_type && $fenxiao_type != 'NONE'){
	            $sql = "INSERT INTO ecshop.order_attribute(order_id, attr_name, attr_value) values({$order_id}, 'FENXIAO_TYPE', '{$fenxiao_type}')";
	        		$db->query($sql);
	        }
	    }
	    
	    if($osn['order_sn']){
		    //生成订单之后，插入部分附属信息表
		    //1.order_action表
		    $action['order_id']        = $order_id;
		    $action['order_status']    = $order['order_status'];
		    $action['pay_status']      = $order['pay_status'];
		    $action['shipping_status'] = $order['shipping_status'];
		    $action['action_time']     = date("Y-m-d H:i:s");
		    $action['action_note']     = '由原订单'.$parent_order['order_sn']."拆分而成的订单";
		    $action['action_user']     = $_SESSION['admin_name'];
		    $db->autoExecute("ecshop.ecs_order_action", $action);
		    //2.order_relation表
		    $order_relation = $db->getRow("select * from ecshop.order_relation where order_id = $parent_order_id limit 1");
		    if($order_relation){
		    	$root_order_id = $order_relation['root_order_id'];
		    	$root_order_sn = $order_relation['root_order_sn'];
		    }else{
		    	$root_order_id = $parent_order['order_id'];
		    	$root_order_sn = $parent_order['order_sn'];
		    }
		    //插入数据库
		    $db->query("insert into ecshop.order_relation values (null,$order_id,'{$osn['order_sn']}',{$parent_order_id},'{$parent_order['order_sn']}',$root_order_id,'{$root_order_sn}')");
	    }
	    $results['order_sn'] = $osn['order_sn'];
 	}
    return $results;
}

//拆分完成之后，处理原订单，即取消原订单并且添加order_action
function deal_split_org_order($order_id,$action_list){
	global $db;
	//1.取消原订单 //Sinri:这里直接把原订单作废掉了
	$db->query("update ecshop.ecs_order_info set order_status = '2' where order_id = $order_id");
	//2.原订单添加order_action
	$order = $db->getRow("select * from ecshop.ecs_order_info where order_id = $order_id");
	$action['order_id']        = $order_id;
    $action['order_status']    = 2;
    $action['pay_status']      = $order['pay_status'];
    $action['shipping_status'] = $order['shipping_status'];
    $action['action_time']     = date("Y-m-d H:i:s");
    $action['action_note']     = $action_list;
    $action['action_user']     = $_SESSION['admin_name'];
    $db->autoExecute("ecshop.ecs_order_action", $action);
}

function check_data($child_goods,$order_id){
	global $db;
	$child_goods_count = count($child_goods);
	$parent_goods_count = $db->getOne("select count(*) from ecshop.ecs_order_goods where order_id = {$order_id}");
	return $child_goods_count == $parent_goods_count;
}

function check_number($order_sn_list,$order_id){
	global $db;
	$sql_children_goods_count = "select count(eog.goods_number) from ecshop.ecs_order_goods eog
								left join ecshop.ecs_order_info eoi on eoi.order_id = eog.order_id 
								where eoi.order_sn ".db_create_in($order_sn_list);
	$children_goods_count = $db->getOne($sql_children_goods_count);
	
	$parent_goods_count = $db->getOne("select count(*) from ecshop.ecs_order_goods where order_id = {$order_id}");
	return $parent_goods_count == $parent_goods_count;
}
?>