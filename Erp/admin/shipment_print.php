<?php

/**
 * 发货单打印
 * 
 * @author yxiang@leqee.com
 * @param from $_REQUEST ['shipment_id']
 */

define('IN_ECS', true);
require('includes/init.php');
include_once 'function.php';
require_once('includes/lib_order.php');
// include_once('includes/lib_order_mixed_status.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');

class GoodsInfoForPick
{
    var $goods_id_;
    var $goods_barcode_;
    var $goods_name_;
    var $main_location_resorted_ = array();    //'1J-A-01-02' -> '1J','A','02','01'
    var $location_list_ = array();
    var $pick_info_list_ = array();
    var $pick_info_list_size_;

    static public function CompareFunc($a, $b)
    {
        if(count($a->main_location_resorted_) != 4)
            return 1;
        if(count($b->main_location_resorted_) != 4)
            return -1;
        
        for ($i = 0; $i < 4; $i++)
        {
            $aa = $a->main_location_resorted_[$i];
            $bb = $b->main_location_resorted_[$i];  

            if ($aa == $bb)
            {
                continue;
            }
            else if ($aa > $bb)
            {
                return 1;
            }
            else 
            {
                return -1;
            }
        }
        return 0;
    }
}

class PickInfoOfGoods
{
    var $shipment_id_;
    var $shipment_count_id_;
    var $goods_number_;
}

if(isset($_REQUEST['shipment_id']) && is_string($_REQUEST['shipment_id'])){
    $shipment_id = preg_split('/\s*,\s*/',$_REQUEST['shipment_id'],-1,PREG_SPLIT_NO_EMPTY);
}
else {
    die("参数错误");
}
$flag = 0;


// 查询shipment
$sql = "
    select 
        o.order_id,o.order_sn,o.consignee,o.order_time,o.taobao_order_sn,o.shipping_id,o.email,
        o.order_amount,o.shipping_fee,o.goods_amount,o.bonus,o.inv_payee, o.pay_name,
        o.country,o.province,o.city,o.district,o.address,o.tel,o.mobile,o.party_id,o.facility_id,
    	IF( o.order_type_id = 'SALE', 
    		(SELECT action_time FROM ecshop.ecs_order_action WHERE order_id = o.order_id AND order_status = 1 LIMIT 1), 
    		o.order_time ) AS confirm_time,
        s.SHIPMENT_ID,s.SHIPMENT_TYPE_ID,s.CARRIER_ID,s.PARTY_ID, p.pay_code, o.zipcode
    from
        romeo.shipment s
        left join romeo.order_shipment os on os.SHIPMENT_ID=s.SHIPMENT_ID
        left join ecshop.ecs_order_info as o on o.order_id=os.ORDER_ID 
        left join ecshop.ecs_payment p on o.pay_id = p.pay_id
    where s.shipping_category = 'SHIPPING_SEND' and 
        s.SHIPMENT_ID ". db_create_in($shipment_id);
$ref_fields=$ref_rowset=array();
$result=$db->getAllRefby($sql,array('SHIPMENT_ID','order_id','facility_id','party_id'),$ref_fields,$ref_rowset);
if($result)
{
	// 同一个仓库
    $facility_id=reset($ref_fields['facility_id']);
    $party_id=reset($ref_fields['party_id']);

    // 取得订单的商品明细
    $sql2="
		select
            og.order_id,og.goods_name,og.goods_number,og.goods_price,og.goods_id,og.style_id,g.uniq_sku,
            og.goods_price,og.goods_number,
            og.goods_price * og.goods_number as goods_amount, og.rec_id, ifnull(g.barcode, '') as barcode, ifnull(sg.barcode, '') as style_barcode,g.cat_id, og.goods_price 
        from
            ecshop.ecs_order_goods og 
            left join ecshop.ecs_goods g on og.goods_id = g.goods_id
            left join ecshop.ecs_goods_style sg on sg.goods_id = g.goods_id and og.style_id = sg.style_id and sg.is_delete=0
        where
            og.order_id ". db_create_in($ref_fields['order_id']);
    $ref_goods_fields=$ref_goods_rowset=array();
    $result2=$db->getAllRefby($sql2,array('order_id','rec_id'),$ref_goods_fields,$ref_goods_rowset);
 
    // 取得订单的属性
    $sql3="
    	select * from ecshop.order_attribute where order_id " . db_create_in($ref_fields['order_id']);
    $ref_attribute_fields=$ref_attribute_rowset=array();
    $result3=$db->getAllRefby($sql3,array('order_id'),$ref_attribute_fields,$ref_attribute_rowset);
    
    // 组装数据
    if($result2)
    {
    	// 取得订单商品的属性
    	$sql4="
    		select * from ecshop.order_goods_attribute where order_goods_id ". db_create_in($ref_goods_fields['rec_id']);
    	$ref_og_attribute_fields=$ref_og_attribute_rowset=array();
    	$result4=$db->getAllRefby($sql4,array('order_goods_id'),$ref_og_attribute_fields,$ref_og_attribute_rowset);
    	
    	// 组装数据
        foreach($ref_rowset['order_id'] as $order_id=>$order_rowset)
        {
        	$order=&$ref_rowset['order_id'][$order_id][0];
        	
            // 更新订单的状态为已经打印
            // update_order_mixed_status($order_id, array('pick_list_status'=>'printed'), 'worker');
            
            // 取得订单的商品
            if(isset($ref_goods_rowset['order_id'][$order_id]))
            {
            	// 取得商品库位并按库位排序
            	$sort=array();
                foreach ($ref_goods_rowset['order_id'][$order_id] as $goods_key => $goods_item)
                {
					$facility_location_list=facility_location_list_by_product(getProductId($goods_item['goods_id'],$goods_item['style_id']),$facility_id);
					$facility_location=reset($facility_location_list);
					if($facility_location!==false){
						$ref_goods_rowset['order_id'][$order_id][$goods_key]['location_seq_id']=$facility_location->locationSeqId;
						// 用于按库位排序
						$sort[$goods_key]=$facility_location->locationSeqId;
					}
					else{
						$sort[$goods_key]='0';
					}
                        
					// 取得商品编码
					if (!empty($goods_item['barcode'])) {
						$ref_goods_rowset['order_id'][$order_id][$goods_key]['product_code'] = $goods_item['barcode'];
					}
					if (!empty($goods_item['style_barcode'])) {
						$ref_goods_rowset['order_id'][$order_id][$goods_key]['product_code'] = $goods_item['style_barcode'];
					}
					if (empty($ref_goods_rowset['order_id'][$order_id][$goods_key]['product_code'])) {
						$ref_goods_rowset['order_id'][$order_id][$goods_key]['product_code'] = encode_goods_id($goods_item['goods_id'],$goods_item['style_id']);
					}
				
					if(65574 == $party_id){
						$ref_goods_rowset['order_id'][$order_id][$goods_key]['product_code']=$goods_item['style_barcode'] ;						
						if(empty($ref_goods_rowset['order_id'][$order_id][$goods_key]['product_code']) && $goods_item['cat_id'] != 2549 && $goods_item['cat_id'] != 2550  ){
							$flag = 1;
						}
					}
					
					// 取得商品的属性
					if(isset($ref_og_attribute_rowset['order_goods_id'][$goods_item['rec_id']]))
					{
						foreach($ref_og_attribute_rowset['order_goods_id'][$goods_item['rec_id']] as $og_attribute_key=>$og_attribute_item)
						{
							$ref_goods_rowset['order_id'][$order_id][$goods_key]['order_goods_attribute'][$og_attribute_item['name']]=$og_attribute_item['value'];		
						}
					}
                }
                // 按库位排序
				if (!empty($sort)) {
					@array_multisort($sort, SORT_ASC, SORT_STRING, $ref_goods_rowset['order_id'][$order_id]);
        		}
        		
                $order['goods_list']=$ref_goods_rowset['order_id'][$order_id];
            }
            
            // 取得订单的属性
            if(isset($ref_attribute_rowset['order_id'][$order_id]))
            {
            	foreach ($ref_attribute_rowset['order_id'][$order_id] as $attribute_key => $attribute_item)
            	{
					$order['order_attribute'][$attribute_item['attr_name']] = $attribute_item['attr_value'];	
            	}
            }
            
            // 获取重要备注
            $sql = "
                select action_note from ecs_order_action where order_id = '{$order['order_id']}' and note_type = 'SHIPPING'
            ";
            $important_note = $db->getCol($sql);
            if (is_array($important_note)) {
                $order['important_note'] = join("；", $important_note);
            }
        }
    }

    // 取得显示的Shipment列表 
    $list=array();
    foreach($ref_rowset['SHIPMENT_ID'] as $shipment_key => $shipment_rowset)
    {
        $reset=reset($shipment_rowset);
        $list[$shipment_key]=array(
            'facilityId'=>$facility_id,
            'shipmentId'=>$reset['SHIPMENT_ID'],
            'shipmentTypeId'=>$reset['SHIPMENT_TYPE_ID'],
            'carrierId'=>$reset['CARRIER_ID'],
            'partyId'=>$reset['PARTY_ID'],
            'mobile'=>$reset['mobile'],
            'tel'=>$reset['tel'],
            'province'=>$reset['province'],
            'city'=>$reset['city'],
            'district'=>$reset['district'],
            'address'=>$reset['address'],
            'consignee'=>$reset['consignee'],
            'pay_code' => $reset['pay_code'],
			'zipcode' => $reset['zipcode'],
        );
        $list[$shipment_key]['order_list']=$shipment_rowset;
    }
}

if ($_SESSION['party_id'] == '65540') {
    if(!empty($list) && is_array($list)){
        foreach ($list as $key=>$goods_lists){
            if(!empty($goods_lists['order_list']) && is_array($goods_lists['order_list'])){
                foreach ($goods_lists['order_list'] as $key1=>$goods_list){
                    if(!empty($goods_list['goods_list']) && is_array($goods_list['goods_list'])){
                        foreach($goods_list['goods_list'] as $key2=>$v){
                            $name = array('罐','盒','g','克');
                            $name = string_replace($name,18,$v['goods_name']);
                            $list[$key]['order_list'][$key1]['goods_list'][$key2]['goods_name'] = $name;
                        }
                    }
                }
            }
        }
    }
}
if($flag){
	alert_back('请联系店长，维护SKU编码', 'shipment_list') ;
}
$smarty->assign('facility_id', $facility_id);
$smarty->assign('list', $list);

//准备批拣数据
$goods_info_list = array();
$shipment_count_id = 0;
foreach ($list as $shipment_id => $shipment_info) {
    # code...
    $shipment_count_id++;
    foreach ($shipment_info['order_list'] as $key => $order_info) {
        # code...
        foreach ($order_info['goods_list'] as $key => $goods_info) {
            # code...
            $goods_id = $goods_info['goods_id'];
            if(!in_array($goods_id, $goods_info_list))
            {
                $goods_info_list[$goods_id] = new GoodsInfoForPick;
                $goods_info_list[$goods_id]->goods_id_ = $goods_id;
                $goods_info_list[$goods_id]->goods_name_ = $goods_info['goods_name'];
                if($goods_info['style_barcode'])
                    $goods_info_list[$goods_id]->goods_barcode_ = $goods_info['style_barcode'];
                else
                    $goods_info_list[$goods_id]->goods_barcode_ = $goods_info['barcode'];
//                $goods_info_list[$goods_id]->goods_barcode_ = if($goods_info['style_barcode']) ? $goods_info['style_barcode'] : $goods_info['barcode'] ;

                if($goods_info['location_seq_id'])
                {
                    array_push($goods_info_list[$goods_id]->location_list_, $goods_info['location_seq_id']);
                    $goods_info_list[$goods_id]->main_location_resorted_ = explode('-', $goods_info_list[$goods_id]->location_list_[0]);
                    $temp_var = $goods_info_list[$goods_id]->main_location_resorted_['3'];
                    $goods_info_list[$goods_id]->main_location_resorted_['3'] = $goods_info_list[$goods_id]->main_location_resorted_['2'];
                    $goods_info_list[$goods_id]->main_location_resorted_['2'] = $temp_var;
                }
            }
            $pick_info_list = new PickInfoOfGoods;
            $pick_info_list->shipment_id_ = $shipment_id;
            $pick_info_list->shipment_count_id_ = $shipment_count_id;
            $pick_info_list->goods_number_ = $goods_info['goods_number'];
            array_push($goods_info_list[$goods_id]->pick_info_list_, $pick_info_list);
            $goods_info_list[$goods_id]->pick_info_list_size_ = count($goods_info_list[$goods_id]->pick_info_list_);
        }
    }
}

uasort($goods_info_list, array('GoodsInfoForPick', 'CompareFunc'));
$smarty->assign('goods_info_list', $goods_info_list);
$smarty->assign('party_id', $party_id);


if('16' == $party_id){
	$sql = "select download_info from ecshop.ecs_guest_info gi
				left join ecshop.ecs_order_info oi on oi.taobao_order_sn = gi.taobao_order_sn
				left join romeo.order_shipment os on CONVERT( oi.order_id USING utf8 ) = os.order_id
				left join romeo.shipment s on s.shipment_id = os.shipment_id
				where s.shipment_id = '{$_REQUEST['shipment_id']}'
	";
	$download_info = $db->getOne($sql);
	$smarty->assign('download_info',$download_info);
}
if ('65545' == $party_id) {
	$smarty->display('shipment/shipment_jjshouse.htm');
} elseif ('65554' == $party_id || '65570' == $party_id) {
	$smarty->display('shipment/shipment_amormoda.htm');
} elseif ('65560' == $party_id) {
	$smarty->display('shipment/shipment_faucetland.htm');
} elseif ('65564' == $party_id) {
	$smarty->display('shipment/shipment_jenjenhouse.htm');
} elseif ('65567' == $party_id) {
	$smarty->display('shipment/shipment_jennyjoseph.htm');
}
/* All Hail Sinri Edogawa ! */
 elseif ('65574' == $party_id){
	$smarty->display('shipment/shipment_gymboree.htm');
}
else {
	$smarty->display('shipment/shipment_print.htm');
}

/**
 * 字符替换
 * @param array $name
 * @param int $size
 * @param string $str
 */
function string_replace($name,$size,$str){
    $str = preg_replace('/(\d+)/',"<B><span style=\"font-size:{$size}px;\">$1</span></B>",$str);
    if(is_array($name)){
        foreach ($name as $str_name){
            $str = preg_replace("/{$str_name}/","<span style=\"font-size:{$size}px;\">{$str_name}</span>",$str);
        }
    }else{
        $str = preg_replace("/{$name}/","<span style=\"font-size:{$size}px;\">{$name}</span>",$str);
    }
    
    return $str;
}

