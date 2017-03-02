<?php
define('IN_ECS', true);
require_once('includes/init.php');
require_once ('function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH.'/includes/helper/array.php');
require_once(ROOT_PATH.'/includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH.'admin/distribution.inc.php');

check_party();

global $db;
$act = isset($_REQUEST['act'])?trim($_REQUEST['act']):false;
$flag_list= array(
	'weichaifen' => '未拆分',
	'yichaifen' => '已拆分',
	'yiquxiao' => '已取消'
);
$distributor_type_list= array(
	'zhixiao' => '直销',
	'fenxiao' => '分销'
);
$tmall_list= array(
	'tmall' => '天猫',
	'ntmall' => '非天猫'
);

$flag =
	isset($_REQUEST['flag']) && trim($_REQUEST['flag'])
    ? $_REQUEST['flag']
    : 'weichaifen';
$distributor_type=
	isset($_REQUEST['distributor_type']) && trim($_REQUEST['distributor_type'])
    ? $_REQUEST['distributor_type']
    : 'zhixiao';
$tmall_type=
	isset($_REQUEST['tamll']) && trim($_REQUEST['tamll'])
    ? $_REQUEST['tamll']
    : '非天猫';
    
$json = new JSON;    
switch ($act) 
{ 
	case 'order_cancel':
		$order_id = trim ( $_REQUEST ['order_id'] );
		$sql = "update ecshop.haiguan_order_split set flag='2' where order_id = '{$order_id}'";
		$result1=$GLOBALS['db']->query($sql);
		$return['flag'] = 'SUCCESS';
		$return['message'] = '取消成功';
		print $json->encode($return);
		exit;
		break;
	case 'cancel':
		$order_sn = trim ( $_REQUEST ['one_order'] );
		if($order_sn != ''){
			ordersplit_cancel($order_sn);
		}
		break;	
	case 'split_order':
		$order_sn = trim ( $_REQUEST ['one_order'] );
		if(empty($order_sn))
			exit;
		$bool_flag = ordersplit($order_sn);
		break;
	case 'order_shipment_merge':
		$orders_list= $_POST['checked'];
		$order_count = count($orders_list);
		for($i=0;i<$order_count;$i++){
			ordersplit($orders_list[$i]);
		}
		break;
	case 'search':
		$order_sn = trim ( $_REQUEST ['order_sn'] );
		$flag = trim ( $_REQUEST ['flag'] );
		$start = trim ( $_REQUEST ['start'] );
		$ended = trim ( $_REQUEST ['ended'] );
		break;
	case 'shop_select':
		$shop_name = $_GET['name'];
		$sql = "
				select eg.goods_id, eg.cat_id, egs.style_id, 
            concat_ws(' ', eg.goods_name, if( egs.goods_color = '', es.color, egs.goods_color) ) as goods_name
			from ecshop.ecs_goods eg
			left join ecshop.ecs_goods_style egs on eg.goods_id = egs.style_id and egs.is_delete=0
			left join ecshop.ecs_style es on es.style_id = egs.style_id 
			where ( eg.is_on_sale = 1 and eg.is_delete = 0 ) and eg.goods_name like  '%{$shop_name}%'
			";
			$result=$GLOBALS['db']->getAll($sql);
			
        if ($result)
            print $json->encode($result);
        else
            print $json->encode(array('error' => '商品不存在'));
        break;
}


	
	$result = call_user_func('search_split_order',$_GET);
	//print_r($result);
	$smarty->assign('result',$result);
	$smarty->assign('Pager',$result[-1]['pager']);
	$smarty->assign('flag_list',$flag_list);
	$smarty->assign('distributor_type_list',$distributor_type_list);
	$smarty->assign('tmall_list',$tmall_list);
	$smarty->assign ( 'available_distributor', get_distributor_list());
	$smarty->display ( 'order_split_haiwai.htm' );
	
	
	
	function getCondition() {
		$order_sn = trim ( $_REQUEST ['order_sn'] );
		$flag = trim ( $_REQUEST ['flag'] );
		$start = trim ( $_REQUEST ['start'] );
		$ended = trim ( $_REQUEST ['ended'] );
		$distributor_id = trim ( $_REQUEST ['distributor_id'] );
		$distributor_type = trim ( $_REQUEST ['distributor_type'] );
		$tmall = trim ( $_REQUEST ['tmall'] );
		
		$condition='';
		if ($order_sn != '') {
			$condition .= " and order_Sn = '{$order_sn}' ";
		}

		if ($flag == '') {
			$condition .= " and flag = 0 ";
		}else if($flag == 'yichaifen'){
			$condition .= " and flag = 1 ";
		}else if($flag == 'weichaifen'){
			$condition .= " and flag = 0 ";
		}else if($flag == 'yiquxiao'){
			$condition .= " and flag = 2 ";
		}else{
			$condition .= " and flag = 0 ";
		}
		
		if ($start != '') {
			$condition .= " and order_time > '{$start}' ";
		}
		
		if ($ended != '') {
			$condition .= " and order_time < '{$ended}' ";
		}
		if($distributor_id != ''){
			$condition .= " and distributor_id = '{$distributor_id}' ";
		}
		
		if($distributor_type == ''){
			$condition .= " and type = 'zhixiao' ";
		}else if($distributor_type == "zhixiao"){
			$condition .= " and type = 'zhixiao' ";
		}else if($distributor_type == "fenxiao"){
			$condition .= " and type = 'fenxiao' ";
		}else{
			$condition .= " and type = 'zhixiao' ";
		}
		if($tmall == ''){
			$condition .=" and source_type = 'ntmall'";
		}else if($tmall == "tmall"){
			$condition .=" and source_type = 'tmall'";
		}else{
			$condition .=" and source_type = 'ntmall'";
		}
		$condition .=" order by order_time desc ";
		return $condition;
	}
	
	function search_split_order($args){
		global $db;
		$cond = getCondition();
		$index = 0;
		$page = intval($args['page']);
		$page = max(1, $page);
		$limit = 4;
		$offset = $limit * ($page-1);

		$sqlc = "select count(1) from ecshop.haiguan_order_split where 1  {$cond}";	
		$total = $db ->getOne($sqlc);
		$sql = "select * from ecshop.haiguan_order_split where 1 {$cond} LIMIT {$limit} OFFSET {$offset}";
		$order_split_list = $db->getAll($sql);
		if(!empty($order_split_list)){
			foreach($order_split_list as $order_split){
				$order_id = $order_split['order_id'];
				$order_sn = $order_split['order_sn'];
				$result[$index]['goods_split_list']=splitAlgorithm($order_id);
				$result[$index]['goods_list']=originalOrder($order_sn);
				$result[$index]['order_id']=$order_id;
				$result[$index]['order_sn']=$order_split['order_sn'];
				$result[$index]['order_time']=$order_split['order_time'];
				$result[$index]['goods_amount']=$order_split['goods_amount'];
				$result[$index]['name']=$order_split['name'];
				$index++;
			}
			
		}
		$result[-1]['pager'] = pager($total,$limit,$page);
		return $result;
	}
	
	/*
	 * 拆分订单的算法
	 * by hzhang1 2015-08-10
	 */
	function splitAlgorithm($order_id){
		global $db;
		$sql = "select eog.rec_id,hgos.order_id,eog.goods_name,eog.goods_number,eog.goods_price,eog.goods_id,eog.style_id,ifnull(bgt.tax_rate,0.1) as tax_rate,eog.goods_price*ifnull(bgt.tax_rate,0.1) as goods_fee 
			from ecshop.haiguan_order_split hgos
			left JOIN ecshop.ecs_order_goods eog on hgos.order_id = eog.order_id 
			left JOIN ecshop.bw_goods_tax bgt on if(eog.style_id=0,eog.goods_id,concat_ws('_',eog.goods_id,eog.style_id)) = bgt.outer_id
			where hgos.order_id='{$order_id}' order by goods_fee desc ";


			$index = 0;
			$goods_list = $db->getAll($sql);
			
			
			$sql = "select goods_amount as total,bonus from ecshop.ecs_order_info where order_id = '{$order_id}'";
			$order_bonus = $db->getRow($sql);
			//订单级别红包
			$sql = "select attr_value as bonus from ecshop.order_attribute where order_id = '{$order_id}' and attr_name ='DISCOUNT_FEE'";
			$order_=$db->getRow($sql);
			
			foreach($goods_list as $good){
				$goods_num = $good['goods_number'] ;
				$tc_discountfee= getDiscount($good['rec_id']);
				$discount_fee = $tc_discountfee['discount_fee'];
				$discount_fee_sum = 0;
				for($i=0;$i<$goods_num;$i++)
				{
					/*商品级别的红包拆分*/
					if($discount_fee){
						$items_detail[$index]['discount_fee']= $discount_fee;
					}else{
						$items_detail[$index]['discount_fee']=0;
					}
					$items[$index] = ($good['goods_price']-$good['goods_price']*abs($order_['bonus'])/$order_bonus['total']-$discount_fee/$good['goods_number'])*$good['tax_rate'];
					$items_detail[$index]['order_id']= $good['order_id'];
					$items_detail[$index]['goods_name'] = $good['goods_name'];
					$items_detail[$index]['goods_id']= $good['goods_id'];
					$items_detail[$index]['style_id']= $good['style_id'];
					$items_detail[$index]['goods_number']= $good['goods_number'];
					$items_detail[$index]['goods_price']=  $good['goods_price']-$good['goods_price']*abs($order_['bonus'])/$order_bonus['total']-$discount_fee/$good['goods_number'];
					$items_detail[$index]['goods_price2']=  $good['goods_price'];
					$items_detail[$index]['tc_code']= $discount_fee['tc_code'];
					$index ++;
				}
				$goods_num = 0;
			}
			$box_volume_count = 50; //每个盒 子的最大价钱，正式环境最大价钱是50
			$box_count = 0; //共用盒子总数
			$item_count = count( $items );
			$box = array();//盒 子数组
			for ( $itemindex = 0; $itemindex < $item_count; $itemindex++ ) {
				  $_box_index = false;
				  $_box_count = count( $box );
				  for ( $box_index = 0; $box_index < $_box_count; $box_index++ ) {
					  if ( $box[$box_index]['volume'] + $items[$itemindex] <= $box_volume_count ) {
					    $_box_index = $box_index;
					    break;
					  }
			  }
			  
			if ( $_box_index === false ) {
			   $box[$_box_count]['volume'] = $items[$itemindex];
			   $box[$_box_count]['items'][] = $itemindex;
			   $box_count++;
			}else {
			   $box[$_box_index]['volume'] += $items[$itemindex];
			   $box[$_box_index]['items'][] = $itemindex;
			  }
			}
			
			$num =1;
			$total_tax =0;
			
			foreach ($box as $key => $bb){
				$tax_fee = 0;
				$a=array();
				$no=0;
				$split_order_price =0;
				 if($bb['volume']>50){
				 	$tax_fee +=$bb['volume'];
				 	$total_tax += $bb['volume'];
				 }
				 foreach ($bb['items'] as $k2 => $v) {
				  	//echo $items_detail[$v]['goods_name'].$items[$v]."-";
				  	$g_name = $items_detail[$v]['goods_name'];
				  	$goods_result['goods_name'] = $g_name;
				  	$goods_result['goods_id'] = $items_detail[$v]['goods_id'];
					$goods_result['style_id'] = $items_detail[$v]['style_id'];
					$goods_result['goods_price'] = sprintf("%.2f",$items_detail[$v]['goods_price']);
					$goods_result['goods_price2'] = sprintf("%.2f",$items_detail[$v]['goods_price2']);
				  	$split_order_price += $items_detail[$v]['goods_price'];
				  	
					$sku_outer_id = $items_detail[$v]['goods_id'] + $items_detail[$v]['style_id'];
					$need_key=array_search($sku_outer_id,$a);
					if($need_key===false){
				  		array_push($a,$sku_outer_id);
				  		array_push($a,1);
				  	}else{
				  		$pos=array_search($sku_outer_id,$a);
				  		$a[$pos+1]++;
				  		$result[$num]['split_result'][$pos/2]['goods_number'] =$a[$pos+1];
				  		continue;
				  	}
				  	
				  	$goods_result['goods_number'] ="1";
				  	$goods_result['discount_fee'] = $items_detail[$v]['discount_fee'];
				  	$goods_result['tc_code'] = $items_detail[$v]['tc_code'];
				  	$goods_result['goods_fee'] = $items[$v];
				  	$goods_result['goods_tax_fee'] = $tax_fee;
				  	$result[$num]['split_result'][$no] =$goods_result;
				  	//$result[$num]['split_result'][$no]['goods_number'] ="1";
				  	$no++;
				 }
				 $result[$num]['split_order_price']=sprintf("%.2f",$split_order_price);
				 $num ++;
				 //echo "<br >";
			}
			
			$result['goods_boxnum']=count( $box );
			$result['total_tax_fee']=sprintf("%.2f",$total_tax);
			//print_r($result);
			return $result;
			
		
	}
	
	/*
	 * 得到商品级别的优惠信息
	 * by hzhang1 2015-08-11
	 */
	function getDiscount($rec_id){
		global $db;
		$sql = "select oga.value tc_code,oga1.value discount_fee
				from ecshop.ecs_order_goods eog
				left join ecshop.order_goods_attribute oga on oga.order_goods_id = eog.rec_id and oga.name = 'OUTER_IID'
				left join ecshop.order_goods_attribute oga1 on oga1.order_goods_id = eog.rec_id and oga1.name = 'DISCOUNT_FEE'
				where  eog.rec_id = '{$rec_id}'";
		$tc_discountfee = $db->getRow($sql);
		return $tc_discountfee;
	}
	
	
	
	function ordersplit_cancel($order_sn){
		global $db;
		$sql = "update ecshop.haiguan_order_split set flag = 2 where order_sn = '{$order_sn}'";
		$db->query($sql);
	}
	
	/*
	 * 点击拆分订单后的操作
	 * by hzhang1 2015-08-11
	 */
	function ordersplit($order_sn){
		global $db;
		$sql="select * from ecshop.ecs_order_info where order_sn = '{$order_sn}'";
		$parent_order=$db->getRow($sql);
		$order_id = $parent_order['order_id'];
		if(!$order_id){
			return false;
		}
		$sql ="select oa.attr_value DISCOUNT_FEE from ecshop.ecs_order_info eoi left join ecshop.order_attribute oa
			   on eoi.order_id = oa.order_id and oa.attr_name = 'DISCOUNT_FEE' where eoi.order_id = $order_id";
		$order_attr=$db->getRow($sql);
		$DiscountFee = $order_attr['DISCOUNT_FEE'];
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
		$order['discount_fee'] = 0;
		//部分信息需要特殊设置
		$order['pay_status'] = 2; //已付款
		$order['order_status'] = 0; //待确认
		$order['shipping_status'] = 0; //待发货
		$order['facility_id'] = $parent_order['facility_id'];
		$shipping_fee = $parent_order['shipping_fee'];
		$bonus = $parent_order['bonus'];
		$goods_amount = $parent_order['goods_amount'];

		$result=splitAlgorithm($order_id);
		/*订单商品划分*/
		$num_count=$result['goods_boxnum'];
		$index=1;
		$shipping_fee_sum =0;
		$goods_amount_sum = 0;
		$total_tax_fee=$result['total_tax_fee'];
		foreach ($result  as $bb){
			$tt= $bb['split_result'];
			$price = $bb['split_order_price'];
			$order_goods=array();
			if(!empty($tt)){
				 $no=count($tt);
				 //echo "第".$index."个订单:".$no."<br>";
				 
				 //当父订单的淘宝订单号为空时，也为空处理
				 if($parent_order['taobao_order_sn'] == ''){
					$order['taobao_order_sn'] = '';
				 }else{
					$order['taobao_order_sn'] = $parent_order['taobao_order_sn']."-".$index;	
				 }
				 if($index<$num_count){
				 	$order['shipping_fee'] = sprintf("%.2f",$shipping_fee/$num_count);
				 	$shipping_fee_sum += sprintf("%.2f",$shipping_fee/$num_count);
				 }else{
				 	$order['shipping_fee'] = $shipping_fee -$shipping_fee_sum;
				 }
				 $order['parent_order_id'] =$order_id;
				 
				 $goods_split_amount_sum=0;
				 $child_goods_split_sum=0;
				 for($i=0;$i<$no;$i++){
					$order_goods[$i]['goods_id'] = $tt[$i]['goods_id'];
					$order_goods[$i]['style_id'] = $tt[$i]['style_id'];
					$order_goods[$i]['goods_number'] = $tt[$i]['goods_number'];
					$order_goods[$i]['price'] = $tt[$i]['goods_price2'];
					$order_goods[$i]['discount_fee'] = $tt[$i]['discount_fee'];
					$order_goods[$i]['tc_code'] = $tt[$i]['tc_code'];
					$goods_split_amount_sum +=$tt[$i]['goods_price2']*$tt[$i]['goods_number'];
					$child_goods_split_sum +=$tt[$i]['discount_fee'];
				 }
				 if($index<$num_count){
				 	$child_order_bonus=sprintf("%.2f",$goods_split_amount_sum/$goods_amount*$DiscountFee);
				 	//$order['goods_amount']=$goods_split_amount_sum;
				 	$order['bonus'] = $price+$order['shipping_fee']-$goods_split_amount_sum;//-($child_order_bonus+$child_goods_split_sum);
				 	$goods_amount_sum += $order['bonus'];
				 }else{
				 	//$order['goods_amount']=$goods_split_amount_sum;
				 	$order['bonus'] = $price+$order['shipping_fee']-$goods_split_amount_sum;//-$goods_amount_sum+$bonus;
				 }
				 $message = "";
				//准备好order和order_goods信息之后，准备生成订单
			 	$osn = distribution_generate_sale_order($order, $order_goods, $parent_order['carrier_id'], $parent_order['order_type'], $message);
			 	if($osn['error']) {
			 		$results['message'] = $osn['error'];
			 		return false;
				}else{
					 if($osn['order_sn']){
					 	$order_id = $osn['order_id'];
					 	$child_order=abs($order['bonus']);
					 	$db->query("insert into ecshop.order_attribute(order_id,attr_name,attr_value) values('{$order_id}','DISCOUNT_FEE',{$child_order})");
					    //生成订单之后，插入部分附属信息表
					    //0.order_action表原订单生成action
					    $action['order_id']        = $parent_order['order_id'];
					    $action['order_status']    = $parent_order['order_status'];
					    $action['pay_status']      = $parent_order['pay_status'];
					    $action['shipping_status'] = $parent_order['shipping_status'];
					    $action['action_time']     = date("Y-m-d H:i:s");
					    $action['action_note']     = '订单'.$parent_order['order_sn']."被拆分为".$osn['order_sn'];
					    $action['action_user']     = $_SESSION['admin_name'];
					    $db->autoExecute("ecshop.ecs_order_action", $action);
					    
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
					    $order_relation = $db->getRow("select * from ecshop.order_relation where order_id = $order_id limit 1");
					    if($order_relation){
					    	$root_order_id = $order_relation['root_order_id'];
					    	$root_order_sn = $order_relation['root_order_sn'];
					    }else{
					    	$root_order_id = $parent_order['order_id'];
					    	$root_order_sn = $parent_order['order_sn'];
					    }
					    //插入数据库
					    $db->query("insert into ecshop.order_relation values (null,$order_id,'{$osn['order_sn']}','{$parent_order['order_id']}','{$parent_order['order_sn']}',$root_order_id,'{$root_order_sn}')");
				    	
				    	$sql = "update ecshop.haiguan_order_split set flag =1,tax_fee='{$total_tax_fee}',child_order_count='{$num_count}',last_updated_stamp=now() where order_id = '{$parent_order['order_id']}'";
				    	$db->query($sql);
				    	
				    	$db->query("update ecshop.ecs_order_info set order_status='2' where order_id = '{$parent_order['order_id']}' ");
				    	
				    }
				    $results['order_sn'] = $osn['order_sn'];
				}
			}
			$index++;
		}
		return true;
	}


	/*
	 * 传送原订单的商品列表
	 * by hzhang1 2015-08-11
	 */
	 function originalOrder($order_sn){
	 	global $db;
	 	$sql = "select order_id,goods_amount as total,bonus from ecshop.ecs_order_info where order_sn = '{$order_sn}'";
		$order_bonus = $db->getRow($sql);
		//订单级别红包
		$sql = "select attr_value as bonus from ecshop.order_attribute where order_id = '{$order_bonus['order_id']}' and attr_name ='DISCOUNT_FEE'";
		if($db->getRow($sql)){
			$order_=$db->getRow($sql);
		}
		
		$sql = "
				select eog.rec_id,eog.goods_name,eog.goods_price as goods_price,eog.goods_number,eog.goods_id,eog.style_id,ifnull(bgt.tax_rate,0.1) as tax_rate from ecshop.ecs_order_info eoi
				left JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
				left JOIN ecshop.bw_goods_tax bgt on if(eog.style_id=0,eog.goods_id,concat_ws('_',eog.goods_id,eog.style_id)) = bgt.outer_id
				where eoi.order_sn = '{$order_sn}' ";
		
		$goods_list=$db->getAll($sql);
		if(abs($order_bonus['bonus'])==0){
		 	return $goods_list;
		}else{
			$count = count($goods_list);
			
			for($index=0;$index<$count;$index++){
				
				$price=$goods_list[$index]['goods_price'];
				$num = $goods_list[$index]['goods_number'];
				$tc_discountfee= getDiscount($goods_list[$index]['rec_id']);
				$discount_fee = $tc_discountfee['discount_fee'];
				$goods_list[$index]['goods_price']=sprintf("%.2f",$price+$order_['bonus']*$price/$order_bonus['total']-abs($discount_fee)/$num);
				
			}
			return $goods_list;
		}
		
	 }
	 
	  /**
	  * 获得店铺列表
	  */
	  function get_distributor_list(){
	    global $db;
	    $party_id = intval($_SESSION['party_id']);
	    $sql = "
	      select tsc.distributor_id, tsc.nick as distributor_name
	    from   ecshop.taobao_shop_conf as tsc
	    where  tsc.party_id = {$party_id} and tsc.status ='OK';
	    ";
	    
	    $ref_fields=array();
	    $refs = array();
	    $db->getAllRefby($sql,array('distributor_id'),$ref_fields, $refs, false);
	    foreach ($refs['distributor_id'] as $distributor_id => $distributor_list){
	        $result[$distributor_id] = $distributor_list[0]['distributor_name'];
	    }
	    unset($refs);
	    return $result;
	  }
  
  
  
	 /*
	  * 业务组增加权限
	  * by hzhang1 2015-08-14
	  */
	function check_party(){
		if(!in_array($_SESSION['party_id'], array('65640','65638'))){	
			$sql = "select name from romeo.party where party_id = {$_SESSION['party_id']}";
			global $db;
			$party_name = $db->getOne($sql);
			sys_msg("当前组织[".$party_name."]无需额外维护品牌商商品");
	}
}
?>
