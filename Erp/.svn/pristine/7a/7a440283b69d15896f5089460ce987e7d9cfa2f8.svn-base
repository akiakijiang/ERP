<?php
/**
 * 库存清单
 * 
 * @author last modified by ncchen@oukoo.com 2009/07/03
 */
define('IN_ECS', true);
require('includes/init.php');
require("function.php");
// 通用商品组织权限特殊判断 ljzhou 2013.07.03
if(check_goods_common_party()) {
	admin_priv('cg_storage_common');
} else {
    admin_priv('cw_finance_storage_main', 'cg_storage', 'purchase_order');
}

if (trim($_REQUEST['storage']) == 'menu') {
    if (is_jjshouse($_SESSION['party_id'])) {
         $_REQUEST['label'] = 'storage_list';
    }else {
         $_REQUEST['label'] = 'storage_list_summary';
    }
} 
// 消息
$info = $_REQUEST['info'];
$label = $_REQUEST['label'];
$labels = array (
'brand_storage_summary' => '类别汇总',
'gh_goods_list' => '正在跟供应商换货清单',
'sh_goods_list' => '内部人员借机清单',
'storage_list' => '库存清单',
'storage_list_summary' => '库存汇总',
'bad_goods_list' => '未入库却出库清单',
);

$type = $_REQUEST['type'];
if ($type == '库存清单CSV' || $type == '库存汇总CSV' || $type == '类别汇总CSV' || $type == '内部人员借机清单CSV') {
    admin_priv('4cw_finance_storage_main_csv', '5cg_storage_csv');
    $label = '';
}

$mtime = explode(' ', microtime());
$start_time = $mtime[1] + $mtime[0];
$condition = getCondition();

//分销-库存清单显示控制
$sql = "  SELECT *  FROM romeo.party   WHERE romeo.party.party_id = {$_SESSION['party_id']}";
$sql_result = $db->getAll($sql);

if ($sql_result && $_SESSION['roles'])
{
	$roles = explode(',', $_SESSION['roles']) ;
	if(in_array($wuliu_id, $roles))
	{
		//如果是物流组的人
		$is_show_storage = $sql_result[0]['IS_SHOW_STORAGE_IN_FX_KCQD'];
	}
	else {
		$is_show_storage = 1;
	}
}
else
{
	$is_show_storage = 1;
}

if ($is_show_storage)
{
	if (in_array($label, array('brand_storage_summary', 'storage_list')) || $type == '库存清单CSV' || $type == '类别汇总CSV') {
	
		if ($label != 'storage_list' || ($_REQUEST['act'] == 'search')) {
	
			//获取库存清单里面的商品
	
			//判断组织是否为jjshouse
			$dispatch_field = "";
			$dispatch_join = "";
			if(is_jjshouse($_SESSION['party_id'])){
				$dispatch_field =", dl.dispatch_sn, dl.image_url";
				$dispatch_join = " LEFT JOIN romeo.dispatch_list dl ON dl.purchase_order_sn = o.order_sn ";
			}
	
			$sql = "
        	SELECT
        	    e.purchase_paid_amount, e.order_type, e.in_sn, e.action_user, e.is_new, e.in_time, e.erp_goods_sn, e.erp_id,
        		o.order_sn, o.order_id, o.order_time, b.*, g.top_cat_id, g.cat_id, gs.internal_sku, ifnull(ev.validity,'') as validity,	if(gs.barcode is null or gs.barcode = '',g.barcode,gs.barcode) as barcode,
        		og.goods_name, og.goods_id, og.customized, og.rec_id, func_get_goods_category_name(g.top_cat_id, g.cat_id) as category, f.facility_name,
				ifnull((select l.location_barcode from romeo.inventory_location l left join romeo.location lc ON l.location_barcode = lc.location_barcode
                where lc.location_type = 'IL_LOCATION' and l.product_id = pm.product_id 
				and l.facility_id = e.facility_id and l.party_id=g.goods_party_id and l.status_id='INV_STTS_AVAILABLE' limit 1),'') as location_seq_id
        		".$dispatch_field."
	        		FROM
	        		{$ecs->table('oukoo_erp')} AS e
	        		LEFT JOIN {$ecs->table('oukoo_erp')} AS oute ON e.in_sn = oute.out_sn
	        		LEFT JOIN {$ecs->table('order_goods')} AS og ON e.order_goods_id = og.rec_id
	        		LEFT JOIN {$ecs->table('order_info')} AS o ON og.order_id = o.order_id
	        		LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id
	        		LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = og.goods_id AND gs.style_id = og.style_id
	        		LEFT JOIN {$ecs->table('brand')} AS b ON b.brand_id = g.brand_id
	        		LEFT JOIN romeo.facility f on e.facility_id = f.facility_id
	        		LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
	        		LEFT JOIN {$ecs->table('erp_validity')} AS ev ON e.erp_id = ev.erp_id
	        				".$dispatch_join."
	        				WHERE
	        				e.in_sn != ''
        		    AND ". party_sql('g.goods_party_id') ."
						AND ". facility_sql('e.facility_id') ."
						{$condition}  -- 限定只能看到欧酷的商品
						ORDER BY g.top_cat_id, e.order_type, o.order_id DESC, e.erp_id
						";
	
	
			$goods_list = $db->getAll($sql);
			$mobile_count = 0;
			$no_mobile_count = 0;
			foreach ($goods_list as $key=>$good) {
				if ($good['customized'] == 'mobile') $mobile_count += 1;
				if ($good['customized'] == 'no_mobile') $no_mobile_count += 1;
				if (is_jjshouse($_SESSION['party_id'])) {
	                if (empty($good['dispatch_sn'])) {
	                	$sql = "
	                	select dl.dispatch_sn
	                	from ecshop.ecs_order_info o
	                	inner join ecshop.ecs_order_goods og on o.order_id = og.order_id and og.rec_id = '{$good['rec_id']}'
	                	inner join ecshop.order_relation r on o.order_id = r.order_id
	                	inner join ecshop.ecs_order_info ro on r.root_order_id = ro.order_id
	                	inner join ecshop.ecs_order_goods rog on ro.order_id = rog.order_id and og.goods_id = rog.goods_id and og.style_id = rog.style_id
	                	inner join romeo.dispatch_list dl on dl.order_id = ro.order_id and dl.order_goods_id = rog.rec_id
	                	where
	                	o.order_id = '{$good['order_id']}'
	                	and ". party_sql('o.party_id')
	                			;
	                			$goods_list[$key]['dispatch_sn'] = $db->getOne($sql);
	                }
	                if(empty($good['image_url'])){
	                $sql = "
	                select dl.image_url
	                		from ecshop.ecs_order_info o
	                		inner join ecshop.ecs_order_goods og on o.order_id = og.order_id and og.rec_id = '{$good['rec_id']}'
	                		inner join ecshop.order_relation r on o.order_id = r.order_id
	                		inner join ecshop.ecs_order_info ro on r.root_order_id = ro.order_id
	                		inner join ecshop.ecs_order_goods rog on ro.order_id = rog.order_id and og.goods_id = rog.goods_id and og.style_id = rog.style_id
	                		inner join romeo.dispatch_list dl on dl.order_id = ro.order_id and dl.order_goods_id = rog.rec_id
	                		where
	                		o.order_id = '{$good['order_id']}'
	                		and ". party_sql('o.party_id')
	                		;
	                		$goods_list[$key]['image_url'] = $db->getOne($sql);
	                }
                }
	
              }
	       }
    		if ($label == 'brand_storage_summary' || $type == '类别汇总CSV') {
    		// 获取原先的供价
    		foreach ($goods_list as $key => $goods) {
    		    $order_sn = $goods['order_sn'];
    			if ($order_sn[strlen($order_sn) - 1] == 't') {
    				$goods_list[$key]['in_type'] = '销售退回';
    			} else {
    				$goods_list[$key]['in_type'] = '外购入库';
    			}
    			$purchase_paid_amount = $goods['purchase_paid_amount'];

				while ($purchase_paid_amount == 0 && $order_sn[strlen($order_sn) - 1] == 't') {
				    $out_sn = substr($order_sn, 0, -2);
					$sql = "
					SELECT * FROM {$ecs->table('oukoo_erp')} e, {$ecs->table('order_goods')} og, {$ecs->table('order_info')} o
					WHERE
						e.order_goods_id = og.rec_id
						AND og.order_id = o.order_id
						AND e.in_sn = '{$out_sn}'
						";
					$erp_info = $db->getRow($sql);
					if ($erp_info == null) {
					  break;
    				}
	                $purchase_paid_amount = $erp_info['purchase_paid_amount'];
	                $order_sn = $erp_info['order_sn'];
                }
				$goods_list[$key]['purchase_paid_amount'] = $purchase_paid_amount;
				$purchase_paid_amount_sum += $purchase_paid_amount;

				if ($goods['top_cat_id'] == 1) {	// 手机
						$brand_storage_list[$goods['brand_name']]['count']++;
						$brand_storage_list[$goods['brand_name']]['purchase_paid_amount'] += $purchase_paid_amount;
					if ($goods['is_new'] == 'NEW') {
						$brand_storage_list[$goods['brand_name']]['new_count']++;
						$brand_storage_list[$goods['brand_name']]['new_purchase_paid_amount'] += $purchase_paid_amount;
					} else {
						$brand_storage_list[$goods['brand_name']]['second_hand_count']++;
						$brand_storage_list[$goods['brand_name']]['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
					}
				} else if ($goods['top_cat_id'] == 597) {	// 配件
						$brand_storage_list['配件']['count']++;
						$brand_storage_list['配件']['purchase_paid_amount'] += $purchase_paid_amount;
						if ($goods['is_new'] == 'NEW') {
							$brand_storage_list['配件']['new_count']++;
							$brand_storage_list['配件']['new_purchase_paid_amount'] += $purchase_paid_amount;
						} else {
						    $brand_storage_list['配件']['second_hand_count']++;
						    $brand_storage_list['配件']['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
						}
				} else if ($goods['cat_id'] == 1157) {	// OPPO DVD
						$brand_storage_list['DVD']['count']++;
						$brand_storage_list['DVD']['purchase_paid_amount'] += $purchase_paid_amount;
						if ($goods['is_new'] == 'NEW') {
							$brand_storage_list['DVD']['new_count']++;
							$brand_storage_list['DVD']['new_purchase_paid_amount'] += $purchase_paid_amount;
						} else {
							$brand_storage_list['DVD']['second_hand_count']++;
							$brand_storage_list['DVD']['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
						}
				} else if ($goods['top_cat_id'] == '1458') {
						$brand_storage_list['电教品']['count']++;
						$brand_storage_list['电教品']['purchase_paid_amount'] += $purchase_paid_amount;
						if ($goods['is_new'] == 'NEW') {
							$brand_storage_list['电教品']['new_count']++;
							$brand_storage_list['电教品']['new_purchase_paid_amount'] += $purchase_paid_amount;
						} else {
							$brand_storage_list['电教品']['second_hand_count']++;
							$brand_storage_list['电教品']['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
						}
				} else if ($goods['top_cat_id'] == 1367) {  // 礼品
						$brand_storage_list['礼品']['count']++;
						$brand_storage_list['礼品']['purchase_paid_amount'] += $purchase_paid_amount;
						if ($goods['is_new'] == 'NEW') {
						    $brand_storage_list['礼品']['new_count']++;
						    $brand_storage_list['礼品']['new_purchase_paid_amount'] += $purchase_paid_amount;
						} else {
							$brand_storage_list['礼品']['second_hand_count']++;
							$brand_storage_list['礼品']['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
        			    }
	            } elseif ($goods['top_cat_id'] == 1515) {
						$brand_storage_list['鞋品']['count']++;
						$brand_storage_list['鞋品']['purchase_paid_amount'] += $purchase_paid_amount;
						if ($goods['is_new'] == 'NEW') {
						    $brand_storage_list['鞋品']['new_count']++;
							$brand_storage_list['鞋品']['new_purchase_paid_amount'] += $purchase_paid_amount;
						} else {
							$brand_storage_list['鞋品']['second_hand_count']++;
							$brand_storage_list['鞋品']['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
						}
	           } elseif ($goods['top_cat_id'] == 1516) {
						$brand_storage_list['运动装备']['count']++;
						$brand_storage_list['运动装备']['purchase_paid_amount'] += $purchase_paid_amount;
						if ($goods['is_new'] == 'NEW') {
							$brand_storage_list['运动装备']['new_count']++;
							$brand_storage_list['运动装备']['new_purchase_paid_amount'] += $purchase_paid_amount;
						} else {
						    $brand_storage_list['运动装备']['second_hand_count']++;
							$brand_storage_list['运动装备']['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
						}
	           } else if (!in_array($goods['top_cat_id'], array(1, 597, 1109)) && $goods['cat_id'] != 1157) { // 小家电
                        $brand_storage_list['小家电']['count']++;
						$brand_storage_list['小家电']['purchase_paid_amount'] += $purchase_paid_amount;
						if ($goods['is_new'] == 'NEW') {
							$brand_storage_list['小家电']['new_count']++;
							$brand_storage_list['小家电']['new_purchase_paid_amount'] += $purchase_paid_amount;
						} else {
							$brand_storage_list['小家电']['second_hand_count']++;
							$brand_storage_list['小家电']['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
	                    }
			   } else {
			           $brand_storage_list['其他']['count']++;
			           $brand_storage_list['其他']['purchase_paid_amount'] += $purchase_paid_amount;
			           if ($goods['is_new'] == 'NEW') {
					        $brand_storage_list['其他']['new_count']++;
							$brand_storage_list['其他']['new_purchase_paid_amount'] += $purchase_paid_amount;
						} else {
							$brand_storage_list['其他']['second_hand_count']++;
							$brand_storage_list['其他']['second_hand_purchase_paid_amount'] += $purchase_paid_amount;
			            }
	          }
	
			if ($goods['is_new'] == 'NEW') {
					$new_count++;
					$new_purchase_paid_amount += $purchase_paid_amount;
			} else {
			        $second_hand_count++;
			        $second_hand_purchase_paid_amount += $purchase_paid_amount;
			}
	     }
	}
	
	} else if ($label == 'gh_goods_list') {
	//获取正在跟供应商换货的商品
    $sql = "
		SELECT
			e.purchase_paid_amount, e.order_type, e.in_sn, e.action_user, e.is_new, e.in_time, e.erp_goods_sn,
			og.goods_name, og.goods_id, func_get_goods_category_name(g.top_cat_id, g.cat_id) as category,
			o.order_sn, o.order_id, o.order_time, e.order_goods_id, o.consignee, o.postscript, oa.attr_value as apply_user
			FROM
			{$ecs->table('oukoo_erp')} e
			LEFT JOIN {$ecs->table('order_goods')} og ON og.rec_id = e.order_goods_id
			LEFT JOIN {$ecs->table('order_info')} o ON o.order_id = og.order_id
			LEFT JOIN {$ecs->table('goods')} g ON g.goods_id = og.goods_id
			LEFT JOIN order_attribute oa on oa.order_id = o.order_id and oa.attr_name = 'APPLY_USER'
			WHERE
					o.order_type_id = 'SUPPLIER_EXCHANGE'
							AND ". party_sql('o.party_id') ."
							AND ". facility_sql('o.facility_id') ."
			GROUP by og.rec_id HAVING count(*) = 1
			";
			$gh_goods_list = $db->getAll($sql);
			$gh_sum_purchase_paid_amount = 0.0;
			foreach ($gh_goods_list as $key => $goods) {
			$gh_sum_purchase_paid_amount += $goods['purchase_paid_amount'];
			}
			} else if ($label == 'sh_goods_list' || $type == '内部人员借机清单CSV') {
	
			//获取内部人员借机的商品
			$sql = "
			SELECT
			e.purchase_paid_amount, e.order_type, e.in_sn, e.action_user, e.is_new, e.in_time, e.erp_goods_sn, e.order_goods_id,
			og.goods_name, og.goods_id, func_get_goods_category_name(g.top_cat_id, g.cat_id) as category,
			o.order_sn, o.order_id, o.order_time, o.consignee, o.postscript, p.provider_name, oa.attr_value as predict_return_time, 
			if (gs.style_id is null, g.barcode, gs.barcode) barcode 
			FROM
			{$ecs->table('oukoo_erp')} e
			LEFT JOIN {$ecs->table('order_goods')} og ON og.rec_id = e.order_goods_id
			LEFT JOIN {$ecs->table('order_info')} o ON o.order_id = og.order_id
    	    LEFT JOIN {$ecs->table('goods')} g ON g.goods_id = og.goods_id
    	    LEFT JOIN {$ecs->table('goods_style')} gs on og.goods_id = gs.goods_id and og.style_id = gs.style_id 
	    	    		LEFT JOIN {$ecs->table('provider')} p ON p.provider_id = e.provider_id
	    	    		LEFT JOIN order_attribute oa ON o.order_id = oa.order_id and oa.attr_name = 'PREDICT_RETURN_TIME'
	    	    		WHERE
	    	    		o.order_type_id = 'BORROW'
	    	    		AND ". party_sql('o.party_id') ."
	    	    		AND ". facility_sql('o.facility_id') ."
	    	    		GROUP by og.rec_id HAVING count(*) = 1
	    	    		";
	    	    		$sh_goods_list = $db->getAll($sql);
	
	    	    		$sh_sum_purchase_paid_amount = 0.0;
	    	    		$goods['return_time_history'] = array();
	    	    		foreach ($sh_goods_list as $key => &$goods) {
	    	    		$sh_sum_purchase_paid_amount += $goods['purchase_paid_amount'];
	    	    		$sql = "
	    	    		select concat_ws('  ',operator,left(predict_return_time, 10)) as operator_time
	    	    		from ecshop.ecs_borrow_history
	    	    		where order_id = '{$goods['order_id']}'
	    	    		order by operate_time asc
	    	    		";
	    	    		$goods['return_time_history'] = $db->getAll($sql);
	
	    	    		//取最近的操作时间
	    	    		$sql = "
	    	    		select max(operate_time)
	    	    		from ecshop.ecs_borrow_history
	    	    		where order_id = '{$goods['order_id']}'
	    	    		";
	    	    		$goods['action_time'] = $db -> getOne($sql);
	
	    	    		//如果取不到数据，取order_attribute中的数据
	    	    		if(!$goods['return_time_history']){
	    	    		$sql = "
	    	    		select concat_ws('  ',e.action_user,ifnull(oa.attr_value,'0000-00-00')) as operator_time
	    	    		from ecshop.ecs_order_info oi
	    	    				left join ecshop.order_attribute oa on oi.order_id = oa.order_id and oa.attr_name = 'PREDICT_RETURN_TIME'
			   inner join ecshop.ecs_order_goods og on og.order_id = oi.order_id
				   inner join ecshop.ecs_oukoo_erp e on e.order_goods_id = og.rec_id
				   where oi.order_id = '{$goods['order_id']}'
				   ";
				   $operator_time = array('operator_time' => $db->getOne($sql));
				   array_push($goods['return_time_history'],$operator_time);
	    	    		$goods['action_time'] = $goods['order_time'];
	    	    		}
	    	    		
	    	    	if ($type == '内部人员借机清单CSV') {
		    	    	$return_time_history_str = "";
		    	    	foreach ($goods['return_time_history'] as $a ) {
		    	    		$return_time_history_str .= $a['operator_time'];
		    	    	}
		    	    	$goods['return_time_history_str'] = $return_time_history_str;
	    	    		
	    	    	}
	    	    		}
	 } else if (($label == 'storage_list_summary' && trim($_REQUEST['act']) == 'search') || $type == '库存汇总CSV') {
		$sql = "
		SELECT
		IF(og.goods_name != CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, ''))),
			CONCAT(og.goods_name,'(最新名字：',
			CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, ''))),
			')'),
			og.goods_name
			) as goods_name,
			f.facility_name,
			concat_ws('-',e.facility_id,og.goods_id,og.style_id,e.is_new) as product_key,
			e.is_new, if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode, g.is_maintain_warranty, COUNT(*) AS storage_count,
			ifnull((select l.location_barcode from romeo.inventory_location l left join romeo.location lc ON l.location_barcode = lc.location_barcode
            where lc.location_type = 'IL_LOCATION' and l.product_id = pm.product_id 
			and l.facility_id = e.facility_id and l.party_id=g.goods_party_id and l.status_id='INV_STTS_AVAILABLE' limit 1),'') as location_seq_id
			FROM {$ecs->table('oukoo_erp')} AS e
			LEFT JOIN {$ecs->table('oukoo_erp')} AS oute ON e.in_sn = oute.out_sn
			LEFT JOIN {$ecs->table('order_goods')} AS og ON e.order_goods_id = og.rec_id
			LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = og.goods_id and gs.style_id = og.style_id
			LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id
    	    LEFT JOIN {$ecs->table('style')} AS s on gs.style_id = s.style_id
	    	LEFT JOIN romeo.product_mapping pm ON og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
	    	LEFT JOIN romeo.facility f ON f.facility_id = e.facility_id
    	    WHERE
	    			e.in_sn != '' AND e.out_sn = ''
	    			AND e.is_new in('NEW','SECOND_HAND') 
	    			AND g.goods_party_id = '{$_SESSION['party_id']}' ". "
	    					AND ". facility_sql('e.facility_id') ."
	    					{$condition}
	    	GROUP BY e.facility_id,og.goods_id, og.style_id, e.is_new
	    	ORDER BY g.cat_id, og.goods_id
	    	";
	    	$refs_value_storage = $refs_storage = array ();
	    	$storage_list = $db->getAllRefBy ( $sql, array ('product_key' ), $refs_value_storage, $refs_storage );
	       
	    	$sql = "
	    	SELECT
	    	concat_ws('-',e.facility_id,og.goods_id,og.style_id,e.is_new) as product_key,
	    	group_concat(e.erp_id) as erp_ids,
	    	ifnull(date(ev.validity),'') as validity,
	    			if(count(distinct(ev.erp_id))=0,count(distinct(e.erp_id)),count(distinct(ev.erp_id))) as validity_num
	    	FROM {$ecs->table('oukoo_erp')} AS e
	    	LEFT JOIN {$ecs->table('oukoo_erp')} AS oute ON e.in_sn = oute.out_sn
	    	LEFT JOIN {$ecs->table('order_goods')} AS og ON e.order_goods_id = og.rec_id
	    	LEFT JOIN {$ecs->table('goods_style')} AS gs ON gs.goods_id = og.goods_id and gs.style_id = og.style_id
	    	LEFT JOIN {$ecs->table('goods')} AS g ON g.goods_id = og.goods_id
	    	LEFT JOIN {$ecs->table('style')} AS s on gs.style_id = s.style_id
	    	LEFT JOIN {$ecs->table('erp_validity')} AS ev on e.erp_id = ev.erp_id	    	
	    	WHERE
	    		e.in_sn != '' AND e.out_sn = ''
	    		AND g.goods_party_id = '{$_SESSION['party_id']}' " ."
	    		AND ". facility_sql('e.facility_id') ."
	    		{$condition}
	    	GROUP BY e.facility_id,og.goods_id, og.style_id, e.is_new, ev.validity
	    	ORDER BY g.cat_id, og.goods_id , ev.validity
	    ";
	   
	    $refs_value_validity = $refs_validity = array ();
		$db->getAllRefBy ( $sql, array ('product_key' ), $refs_value_validity, $refs_validity );
	    
		foreach ( $storage_list as $key => $storage ) {
			$storage_list [$key] ['product_keys'] = $refs_validity ['product_key'] [$storage ['product_key']];
			// 标记是否有未维护有效期的库存
			$has_no_validity = false;
			foreach ( $refs_validity ['product_key'] [$storage['product_key']] as $validity ) {
				if ($validity ['validity'] == '') {
					$has_no_validity = true;
					break;
				}
			}
			// 都维护的，增加未维护的数量为0的一列
			if(!$has_no_validity) {
				$new_validity_num = array('product_key'=>$storage['product_key'],'validity'=>'','validity_num'=>'0');
				$storage['product_keys'] = array_unshift($storage_list [$key]['product_keys'],$new_validity_num);
			}
		}
		$smarty->assign('info', $info);
	
	} else if ($label == 'bad_goods_list') {
	    $sql = "
	    	SELECT a.order_type, a.out_sn, a.in_sn, a.action_user, g.goods_name, o.order_sn, o.order_id, o.order_time
	    	FROM {$ecs->table('oukoo_erp')} AS a
	    	LEFT JOIN {$ecs->table('order_goods')} AS g ON a.order_goods_id = g.rec_id
	    	LEFT JOIN {$ecs->table('order_info')} AS o ON g.order_id = o.order_id
	    	WHERE
	    		out_sn != ''
	    		AND ". party_sql('o.party_id') ."
	    		AND ". facility_sql('a.facility_id') ."
	    		AND NOT EXISTS (SELECT 1 FROM {$ecs->table('oukoo_erp')} AS b WHERE a.out_sn = b.in_sn)
	    	ORDER BY o.order_id desc";
	    $bad_goods_list = $db->getAll($sql);
	}
}



$mtime = explode(' ', microtime());
$end_time = $mtime[1] + $mtime[0];
$cost_time = round($end_time - $start_time, 2);

if ($label == 'brand_storage_summary' || $type == '类别汇总CSV') {
    $smarty->assign('new_count', $new_count);
    $smarty->assign('new_purchase_paid_amount', $new_purchase_paid_amount);
    $smarty->assign('second_hand_count', $second_hand_count);
    $smarty->assign('second_hand_purchase_paid_amount', $second_hand_purchase_paid_amount);
    $smarty->assign('brand_storage_list', $brand_storage_list);
    $smarty->assign('purchase_paid_amount_sum', $purchase_paid_amount_sum);
} else if ($label == 'storage_list' || $type == '库存清单CSV') {
    $smarty->assign('goods_list', $goods_list);
    $smarty->assign('goods_count', count($goods_list));
    $smarty->assign('mobile_count', $mobile_count);
    $smarty->assign('no_mobile_count', $no_mobile_count);
} else if ($label == 'gh_goods_list') {
    $smarty->assign('gh_sum_purchase_paid_amount', $gh_sum_purchase_paid_amount);
    $smarty->assign('gh_goods_list', $gh_goods_list);
} else if ($label == 'sh_goods_list' || $type == '内部人员借机清单CSV') {
    $smarty->assign('sh_sum_purchase_paid_amount', $sh_sum_purchase_paid_amount);
    $smarty->assign('sh_goods_list', $sh_goods_list);
} else if (($label == 'storage_list_summary' && trim($_REQUEST['act']) == 'search') || $type == '库存汇总CSV') {
    $smarty->assign('storage_list', $storage_list);
} else if (($label == 'storage_list_summary' && trim($_REQUEST['act']) == 'search') || $type == '库存汇总(不含有效期)CSV') {
    $smarty->assign('storage_list', $storage_list);
} else if ($label == 'bad_goods_list') {
    $smarty->assign('bad_goods_list', $bad_goods_list);
}

// 第三方仓库要屏蔽商品分类搜索 ljzhou 2013.04.23
$is_third_party_warehouse = false;
if(check_admin_priv('third_party_warehouse') && ($_SESSION['action_list']!='all')){
    $is_third_party_warehouse = true;
}
$smarty->assign('is_third_party_warehouse', $is_third_party_warehouse);

$smarty->assign('labels', $labels);
$smarty->assign('cost_time', $cost_time);
$smarty->assign('available_facility', array_intersect_assoc(get_available_facility(),get_user_facility()));

if ($type == '库存清单CSV') {
    admin_priv('4cw_finance_storage_main_csv', '5cg_storage_csv');
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","库存清单") . ".csv");
    $out = $smarty->fetch('oukooext/storage_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} elseif ($type == '类别汇总CSV') {
    admin_priv('4cw_finance_storage_main_csv', '5cg_storage_csv');
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","品牌库存清单") . ".csv");
    $out = $smarty->fetch('oukooext/storage_brand_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} elseif ($type == '库存汇总CSV')  {
    admin_priv('4cw_finance_storage_main_csv', '5cg_storage_csv');
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","库存汇总清单") . ".csv");
    $out = $smarty->fetch('oukooext/storage_summary_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} elseif ($type == '库存汇总(不含有效期)CSV')  {
    admin_priv('4cw_finance_storage_main_csv', '5cg_storage_csv');
    header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","库存汇总(不含有效期)清单") . ".csv");
    $out = $smarty->fetch('oukooext/storage_no_validity_summary_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
} elseif ($type == '内部人员借机清单CSV') {
	admin_priv('4cw_finance_storage_main_csv', '5cg_storage_csv');
	header("Content-type:application/vnd.ms-excel");
    header("Content-Disposition:filename=" . iconv("UTF-8","GB18030","内部人员借机清单") . ".csv");
    $out = $smarty->fetch('oukooext/storage_sh_goods_list_csv.htm');
    echo iconv("UTF-8","GB18030", $out);
    exit();
}else {
    $smarty->assign('facility_name', facility_mapping($_SESSION['facility_id']));
    $smarty->assign('is_oversea_sales',is_oversea_sales());
    $smarty->display('oukooext/storage.htm');
}

//判断是否为海外业务，加上Dragonfly、香港oppo、香港平世、乐其蓝光
 function is_oversea_sales($party_id=null){
 	if(empty($party_id)){
 		$party_id=$_SESSION['party_id'];
 	}
 	$array_party_id=array('65543','65536','65566','64');
 	if(is_jjshouse($party_id) || in_array($party_id,$array_party_id)){
 		return true;
 	}
 	return false;
 }
 
function getCondition() {
    global $ecs;

    $condition = "";
    $goods_cagetory = $_REQUEST['goods_cagetory'];
    $storage_time = $_REQUEST['storage_time'];
    $other_condition = $_REQUEST['other_condition'];
    $barcode = trim($_REQUEST['barcode']);
    $goods_name = trim($_REQUEST['goods_name']);
    $is_new = $_REQUEST['is_new'];
    $available_facility = $_REQUEST['available_facility'];
    $start_validity_time = $_REQUEST['start_validity_time'];
    $end_validity_time = $_REQUEST['end_validity_time'];

    if (strtotime($storage_time) > 0) {
        $end_storage_time_int = strtotime($storage_time) + 24 * 3600;
        $end_storage_time = strftime('%Y-%m-%d', strtotime("+1 day", strtotime($storage_time)));
        $condition .= " AND e.in_time < '$end_storage_time'";

        $condition .= "
			AND (oute.out_sn IS NULL OR 
				(oute.out_sn IS NOT NULL AND
				 (
				 SELECT tmo.shipping_time FROM {$ecs->table('order_goods')} tmog INNER JOIN {$ecs->table('order_info')} tmo ON tmog.order_id = tmo.order_id
				 WHERE tmog.rec_id = oute.order_goods_id LIMIT 1
				 ) >= '{$end_storage_time_int}'
				)
			)
		";
    } else {
        $condition .= " AND oute.out_sn IS NULL";
    }

    // 到期时间搜索
    if ($start_validity_time || $end_validity_time) {
        if($start_validity_time) {
        	$condition .= " AND ev.validity >= '{$start_validity_time}' ";
        }
        if($end_validity_time) {
        	$condition .= " AND ev.validity < '{$end_validity_time}' ";
        }
    } 
    
    if ($goods_cagetory != -1 && $goods_cagetory !== null) {
        switch ($goods_cagetory) {
            case 1:	// 手机
            $condition .= " AND g.top_cat_id = 1";
            break;
            case 2:	// 配件
            $condition .= " AND g.top_cat_id = 597";
            break;
            case 3:	// 小家电
            $condition .= " AND g.top_cat_id NOT IN (1, 597, 1109) AND g.cat_id != 1157 ";
            break;
            // 1157是OPPO DVD, 1109是特殊商品
            case 4:	// DVD
            $condition .= " AND g.cat_id = 1157 ";
            break;
            case 5: // 电教品
            $condition .= " AND g.top_cat_id = 1458 ";
            break;
            case 6: // 礼品
            $condition .= " AND g.top_cat_id = 1367 ";
            break;
            case 7: //
            $condition .= " AND g.top_cat_id = 1515 ";
            break;
            case 8:
                $condition .= " AND g.top_cat_id = 1516 ";
                break;
        }
    }
    
    if ($barcode != '') {
        $condition .= " AND if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) LIKE '%{$barcode}%' ";
    }
    if ($goods_name != '') {
        $condition .= " AND og.goods_name LIKE '%{$goods_name}%'";
    }
    if ($is_new != -1 && $is_new !== null) {
        $condition .= " AND e.is_new = '{$is_new}'";
    }
    //1."未上架，但库存有货"、2."已上架，有库存非在售商品"

    if ($other_condition == 1) {
        $condition .= " AND g.is_on_sale = 0 ";
    } elseif ($other_condition == 2) {
        $condition .= " AND g.is_on_sale = 1 AND g.sale_status != 'normal'";
    }
    //仓库
    if ($available_facility != -1 && $available_facility != '' ) {
        $condition .= " AND e.facility_id = '{$available_facility}' ";
	}

	return $condition;
}

?>
