<?php

/**
 * 电教、蓝光、乐贝业务组发货表
 * 
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once ('includes/debug/lib_log.php');
admin_priv('distribution_delivery');
//该串口商品发货只针对乐其蓝光、乐贝蓝光、乐其电教这三个业务组
if(!in_array($_SESSION['party_id'], array('64','65565','16','65668'))){
	echo '<span style="color:red">只有乐其蓝光、乐贝蓝光、乐其电教这三个业务组才使用分销发货！</span>';
	die();	
}
$type =trim($_REQUEST['act']); 
$goods_style_id = $_REQUEST['goods_style_id'];

//ajax处理
if($type == 'search'){
	if($goods_style_id == -1){
		echo json_encode(array('num'=>''));

		exit;
   }
   $sql= "
	    SELECT  sum(og.goods_number)
		FROM    {$ecs->table('order_info')} AS oo use index(party_id,shipping_status)
        LEFT JOIN {$ecs->table('order_goods')} AS og ON oo.order_id = og.order_id 
		WHERE  oo.order_status = 1 AND oo.shipping_status IN (0,13)                       
        AND (
	           ( oo.order_type_id = 'SALE' AND oo.pay_status = 2)                         
	          OR oo.order_type_id = 'RMA_EXCHANGE' OR oo.order_type_id = 'SHIP_ONLY'         
	        )
	    AND ". party_sql('oo.party_id') ."
	    AND ". facility_sql("oo.facility_id") ."
	    AND not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1) 
		AND og.goods_id != 27080                                                         
		AND CONCAT_WS('_', og.goods_id, og.style_id) = '{$goods_style_id}'
	  ";
	echo json_encode(array('num'=>$db->getOne($sql)));
	exit;
 }
  $sql= "
	     SELECT 
	            f.facility_name,og.customized, og.goods_name, og.goods_id, og.style_id, og.goods_number,
	            oo.order_id, oo.order_time, oo.order_sn, oo.facility_id,oo.order_type_id,
	            CONCAT_WS('_', og.goods_id, og.style_id) AS goods_style_id, 
	            CONCAT_WS('_', og.goods_id, og.style_id, oo.facility_id) AS goods_style_facility_id
		FROM    {$ecs->table('order_info')} AS oo use index(party_id,shipping_status)
        LEFT JOIN {$ecs->table('order_goods')} AS og ON oo.order_id = og.order_id 
		LEFT JOIN {$ecs->table('goods')}  AS g ON og.goods_id = g.goods_id 
		left join romeo.facility f on oo.facility_id = f.facility_id
		WHERE  oo.order_status = 1 AND oo.shipping_status IN (0,13)                        -- 已确认还未发货的订单
        AND (
	           ( oo.order_type_id = 'SALE' AND oo.pay_status = 2)                          -- 销售订单 (已确认|已支付|先款后货)
	          OR oo.order_type_id = 'RMA_EXCHANGE' OR oo.order_type_id = 'SHIP_ONLY'       -- 或者为换货订单，补寄订单                
	        )
	    AND ". party_sql('oo.party_id') ."
	    AND ". facility_sql("oo.facility_id") ."
	    AND not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1)  -- 不存在出库记录
		AND og.goods_id != 27080                                                            -- 排除自己库存和测试商品  
		ORDER BY g.goods_name, g.top_cat_id, g.brand_id 
	  ";
  $ref_fields = $refs = array();
  $order_list = $db->getAllRefby($sql, array('goods_style_facility_id','goods_style_id'), $ref_fields, $refs, false); 
  
  if(!empty($order_list)){
     
  	 $goods_style_name_list = array();//所有商品名列表
     foreach ($refs['goods_style_facility_id'] as $groups) {
	    foreach($groups as $group) {
	       $goods_style_name_list[$group['goods_style_id']] = $group['goods_name']; 
	        }
      }
    $goods_style_id = trim ( $_REQUEST ['goods_style_id'] );
    if ($goods_style_id != -1 && $goods_style_id != '') {
  	     $order_list = $refs['goods_style_id'][$goods_style_id];
  	     $goods_style_num = 0;
  	     foreach ($order_list as $order) {
  	  	        $goods_style_num += $order['goods_number'];
  	     }
  	    $smarty->assign("goods_style_num",$goods_style_num);//选择的某一商品总的订购数量goods_style_num
        for ($i=0;$i<count($order_list);++$i){
              if($order_list[$i]['order_type_id'] =='SALE' )
              {
                 if (check_admin_priv('customer_service_manage_order')) {  // 客服
			         $order_list[$i]['url'] = "distribution_delivery.php?order_sn={$order_list[$i]['order_sn']}";	
	             } 
	             else {
			        $order_list[$i]['url'] = "distribution_delivery.php?order_sn={$order_list[$i]['order_sn']}";	
		         }
	          } elseif ($order_list[$i]['order_type_id'] == 'PURCHASE') {
			        $order_list[$i]['url'] = "distribution_purchase_stockin.php?order_sn={$order_list[$i]['order_sn']}";
	          }  else {
		           $order_list[$i]['url'] = '#';
	          }
        }
     } else if($goods_style_id == '' || $goods_style_id == null)  {
     	  unset($order_list);
     }
  }
  
$smarty->assign('order_list',$order_list);
$smarty->assign('goods_style_name_list',$goods_style_name_list);                                                    
$smarty->display('distributor/distribution_delivery_list.htm'); 

?>

