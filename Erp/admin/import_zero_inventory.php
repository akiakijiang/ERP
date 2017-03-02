<?php 
/**
 * 零库存导出，非得让ERP和report搞一块，有意思么
 */
define ( 'IN_ECS', true );
require ('includes/init.php');
require ("function.php");
require_once(ROOT_PATH. 'includes/lib_order.php');

require_once('includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
admin_priv('import_zero');

global $db;

$party_list = get_party_list();
$type = $_REQUEST['type'];
$party_ids = $_REQUEST['party_ids'];
if(!empty($party_ids)){
    $party_ids = array_to_str($party_ids);
}

//获取零库存数据
if($type=="import_zero"){
    $sql = "select pa.name,p.ecs_goods_id,ii.product_id,ii.facility_id,eg.product_importance,concat_ws('_',p.ecs_goods_id,p.ecs_style_id) as outer_id,ec.cat_name,CONCAT_WS('_', p.ecs_goods_id, p.ecs_style_id,ii.facility_id,ii.status_id) AS goods_style_facility_status_id,
    eg.barcode,concat('\"',eg.goods_name,'\"') as goods_name,f.facility_name,(sum(ii.QUANTITY_ON_HAND_TOTAL) - ifnull(egir.reserve_number,0)) as avalable_amount,ec.cat_name,if(eg.is_on_sale=1,'是','否') as is_on_sale 
    from romeo.inventory_item ii
    left join romeo.product_mapping p on p.product_id = ii.product_id
    left join ecshop.ecs_goods eg on eg.goods_id = p.ecs_goods_id
    left join romeo.facility f on f.facility_id = ii.facility_id
    left join romeo.party pa on pa.party_id = convert(eg.goods_party_id using utf8)
    left join ecshop.ecs_category ec on ec.cat_id = eg.cat_id
    LEFT JOIN ecshop.ecs_goods_inventory_reserved egir ON egir.goods_id = p.ecs_goods_id
    AND egir.style_id = p.ecs_style_id AND egir.facility_id = ii.facility_id
    AND egir.status = 'OK' AND ii.STATUS_ID = 'INV_STTS_AVAILABLE'
    where eg.product_importance in ('A','B','C') and ii.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED') and eg.goods_party_id in (".$party_ids.")
    group by goods_style_facility_status_id
    ";
    $res = $db->getAll($sql);
    $products = getProducts($res,'ecs_goods_id');
    $sql_order_amount = "select p.product_id,sum(og.goods_number) as order_amount,oi.facility_id,CONCAT_WS('_', og.goods_id, og.style_id,oi.facility_id,og.status_id) AS goods_style_facility_status_id,
            f.facility_name,pa.name,eg.product_importance,concat_ws('_',og.goods_id,og.style_id) as outer_id,eg.barcode,concat('\"',eg.goods_name,'\"') as goods_name,ec.cat_name,if(eg.is_on_sale=1,'是','否') as is_on_sale
            from ecshop.ecs_order_info oi
            left join ecshop.ecs_order_goods og on og.order_id = oi.order_id
            left join romeo.product_mapping p on p.ecs_goods_id = og.goods_id and p.ecs_style_id = og.style_id
            left join ecshop.ecs_goods eg on eg.goods_id = og.goods_id
            left join romeo.party pa on pa.party_id = convert(oi.party_id using utf8)
            left join romeo.facility f on f.facility_id = oi.facility_id
            left join ecshop.ecs_category ec on ec.cat_id = eg.cat_id
            where oi.order_status in (0,1) AND oi.shipping_status IN (0,13)
	            AND oi.order_type_id in ('SALE','RMA_EXCHANGE','SHIP_ONLY')
	            AND not exists(select 1 from romeo.inventory_item_detail iid where iid.order_goods_id = convert(og.rec_id using utf8) limit 1)
				AND oi.order_time >=date_add(now(),interval -1 month) and og.goods_id in (".$products.")
            group by goods_style_facility_status_id
        ";
        //Qlog::log($sql_order_amount);
    $res_order_amount = $db->getAll($sql_order_amount);
    $sql_gt = "SELECT
    sum(temp.num) as supplier_return_number,
    temp.product_id,temp.facility_id,temp.goods_style_facility_status_id,temp.facility_name,temp.name,temp.product_importance,temp.outer_id,temp.barcode,temp.goods_name,temp.cat_name,temp.is_on_sale
        from
		    (  select
		             eog.goods_number + sum(ifnull(iid.quantity_on_hand_diff,0)) as num,
		             eoi.facility_id,p.product_id,CONCAT_WS('_', eog.goods_id, eog.style_id,eoi.facility_id,eog.status_id) AS goods_style_facility_status_id,
                f.facility_name,pa.name,eg.product_importance,concat_ws('_',eog.goods_id,eog.style_id) as outer_id,eg.barcode,concat('\"',eg.goods_name,'\"') as goods_name,ec.cat_name,if(eg.is_on_sale=1,'是','否') as is_on_sale
		       FROM
		            ecshop.ecs_order_info AS eoi force index (order_info_multi_index)
		   	        INNER JOIN ecshop.ecs_order_goods AS eog ON eoi.order_id = eog.order_id
                    inner join romeo.supplier_return_request_gt srrg on srrg.SUPPLIER_RETURN_GT_SN = eoi.order_sn
                    inner join romeo.supplier_return_request srr on srr.supplier_return_id = srrg.supplier_return_id
                    left join ecshop.ecs_goods eg on eg.goods_id = eog.goods_id
                    left join romeo.party pa on pa.party_id = convert(eoi.party_id using utf8)
                    left join romeo.facility f on f.facility_id = eoi.facility_id
                    left join ecshop.ecs_category ec on ec.cat_id = eg.cat_id
		            LEFT  JOIN  romeo.inventory_item_detail iid on iid.order_goods_id = convert(eog.rec_id using utf8)
                    left join romeo.product_mapping p on p.ecs_goods_id = eog.goods_id and p.ecs_style_id = eog.style_id
                    WHERE eoi.order_time > SUBDATE(now(),INTERVAL 1 MONTH) -- Limited under the command of His Highness Mjzhou.
			        and eog.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED')
		            AND   eoi.order_type_id = 'SUPPLIER_RETURN'
                    AND   srr.status in ('EXECUTING','CREATED')
                    AND  srr.check_status != 'DENY' and eog.goods_id in (".$products.")
			   GROUP BY eog.rec_id,eog.status_id
		     )  as temp
        GROUP BY temp.goods_style_facility_status_id
        ";
    $res_gt = $db->getAll($sql_gt);
    $sql_v = "select p.product_id,CONCAT_WS('_', og.goods_id, og.style_id,eoi.facility_id,og.status_id) AS goods_style_facility_status_id,
			(sum(og.goods_number)+ifnull(sum(iid.quantity_on_hand_diff),0))as variance_num,
            f.facility_name,pa.name,eg.product_importance,concat_ws('_',og.goods_id,og.style_id) as outer_id,eg.barcode,concat('\"',eg.goods_name,'\"') as goods_name,ec.cat_name,if(eg.is_on_sale=1,'是','否') as is_on_sale
			from ecshop.ecs_order_info eoi
			inner join ecshop.ecs_order_goods og on eoi.order_id = og.order_id
			inner join ecshop.ecs_goods eg on og.goods_id = eg.goods_id
			left join ecshop.ecs_goods_style egs on egs.goods_id = og.goods_id and egs.style_id = og.style_id and egs.is_delete=0
			left join romeo.inventory_item_detail iid on iid.order_goods_id = convert(og.rec_id using utf8)
            left join romeo.party pa on pa.party_id = convert(eoi.party_id using utf8)
            left join romeo.facility f on f.facility_id = eoi.facility_id
            left join ecshop.ecs_category ec on ec.cat_id = eg.cat_id
			left join romeo.product_mapping p on p.ecs_goods_id = og.goods_id and p.ecs_style_id = og.style_id
			where order_type_id = 'VARIANCE_MINUS' and og.goods_id in (".$products.")
				and eoi.order_time > SUBDATE(now(),INTERVAL 1 MONTH)
				AND og.STATUS_ID IN ('INV_STTS_AVAILABLE','INV_STTS_USED')
		GROUP BY goods_style_facility_status_id
        HAVING variance_num != 0  ";
    $res_v = $db->getAll($sql_v);
    //合并数组
    $res = getOthers($res,$res_order_amount,'order_amount');
    $res = getOthers($res,$res_gt,'supplier_return_number');
    $res = getOthers($res,$res_v,'variance_num');
    $res = getAvalableInventory($res);
    $zero_inventory_list = getZero($res);
    $smarty->assign('zero_inventory_list',$zero_inventory_list);
    admin_priv ( '4cw_finance_storage_main_csv', '5cg_storage_csv' );
    header ( "Content-type:application/vnd.ms-excel" );
    header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "零库存导出" ) . ".csv" );
    $out = $smarty->fetch ( 'oukooext/zero_inventory.htm' );
    echo iconv ( "UTF-8", "GB18030", $out );
    exit ();
}

function get_party_list(){
    $sql = "select name,party_id from romeo.party where status = 'ok' and party_group <> '' order by party_group";
    $result = $GLOBALS['db']->getAll($sql);
    return $result;
}

function array_to_str($arr){
    foreach($arr as $key => $value){
        $arr[$key] = "'".$arr[$key]."'";
    }
    $data = implode(",",$arr);
    return $data;   
}

//将数组的字段转换成字符串
function getProducts($arr,$keyword){
    $data = array();
    foreach ($arr as $key => $value){
        $data[$key] = "'".$value[$keyword]."'";
    }
    $str = implode(",",$data);
    return $str;
}

//如果是同一种商品则数量相加，否则$arr1添加一条记录
function getOthers($arr1,$arr2,$keyword){
    $result = 0;
    $count = count($arr1);
    $add = 1;
    if(!empty($arr2)){
        foreach($arr2 as $key1 => $value1){
            foreach ($arr1 as $key => $value){
                if($arr2[$key1]['goods_style_facility_status_id'] == $value['goods_style_facility_status_id']){
                    $arr1[$key1][$keyword] = $value1[$keyword];
                    $result = 1;
                }
            }
            if($result == 0){
                $arr1[$count+$add] = $arr2[$key1];
                $add++;
            }
            $result = 0;
        }
    }
    return $arr1;
}


//获取可用库存小于0
function getZero($arr){
    $data = array();
    $i = 0;
    foreach ($arr as $key => $value){
        if($value['available_inventory'] <= 0){
            $data[$i] = $value;
            $i++;
        }
    }
    return $data;
}

//计算可用库存量
function getAvalableInventory($arr){
    foreach($arr as $key=>$value){
        $arr[$key]['available_inventory'] = $arr[$key]['avalable_amount'] - $arr[$key]['order_amount'] - $arr[$key]['supplier_return_number'] - $arr[$key]['variance_num'];
    }
    return $arr;
}

$smarty->assign('party_list',$party_list);

$smarty->display ( 'oukooext/import_zero_inventory.htm' );
?>