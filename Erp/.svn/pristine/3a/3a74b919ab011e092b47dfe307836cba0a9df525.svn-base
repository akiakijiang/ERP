<?php 

/**
 * 按Shipment打印拣货单
 * 
 * @author yxiang@leqee.com
 * @copyright 2010 leqee.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once('function.php');
admin_priv('inventory_picking');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');


// 所处仓库
$facility_list = get_user_facility();
if (empty($facility_list)) {
	die('没有仓库权限');
}
//当前所在组织
$party_id = $_SESSION ['party_id'];

// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('split')) 
    ? $_REQUEST['act'] 
    : NULL ;
// 库存预定状态
$reserved_status =
	isset($_REQUEST['reserved_status']) && in_array($_REQUEST['reserved_status'], array('Y','N'))
	? $_REQUEST['reserved_status']
	: 'Y';
// 配送状态
$shipment_status =
	isset($_REQUEST['shipment_status']) && in_array($_REQUEST['shipment_status'], $shipment_status_list)
	? $_REQUEST['shipment_status']
	: 'SHIPMENT_INPUT';
// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
// 每页多少记录数
$page_size = 65535;
// 排序方式
$sort_method =
	isset($_REQUEST['sort']) && trim($_REQUEST['sort'])
    ? $_REQUEST['sort']
    : ‘order_time’;    
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;
//商品id
$goods_id =  isset($_REQUEST['goods_id']) && trim($_REQUEST['goods_id'])
    ? $_REQUEST['goods_id']
    : false;
$style_id = isset($_REQUEST['style_id']) && trim($_REQUEST['style_id'])
	? trim($_REQUEST['style_id'])
	: 0;
//套餐商家
$code = isset($_REQUEST['code']) && trim($_REQUEST['code'])
	? trim($_REQUEST['code'])
	: false;    
// 取消合并发货
if ($act=='split') {
	admin_priv('shipment_split');
	$shipment_id=$_REQUEST['shipment_id'];
	if ($shipment_id) {
		try {
			$handle=soap_get_client('ShipmentService');
			$handle->splitShipmentByShipmentId(array('shipmentId'=>$shipment_id));
		}
		catch (Exception $e) {
			$smarty->assign('message','取消合并发货异常：'. $e->getMessage());
		}
	}
	else {
		$smarty->assign('message','没有发货单号');
	}
}

// 过滤条件
$filter = array(
    // 每页多少条记录
    'size' => 
        $page_size,
	// 组织
	'party_id' =>
        isset($_REQUEST['party_id']) && trim($_REQUEST['party_id'])
        ? $_REQUEST['party_id']
        : NULL,
    // 仓库
    'facility_id' => 
        isset($_REQUEST['facility_id']) && isset($facility_list[$_REQUEST['facility_id']]) 
        ? $_REQUEST['facility_id'] 
        : NULL ,
    // 配送方式
    'carrier_id' =>
        isset($_REQUEST['carrier_id']) && isset($carrier_list[$_REQUEST['carrier_id']]) 
        ? $_REQUEST['carrier_id'] 
        : NULL ,
    // 分销商
	'distributor_id' =>
        isset($_REQUEST['distributor_id']) && isset($distributor_list[$_REQUEST['distributor_id']])
        ? $_REQUEST['distributor_id']
        : NULL,
    // 配送状态
	'shipment_status'=>
        	$shipment_status,
    // 是否已预定库存
    'reserved_status'=>
            $reserved_status,
    'goods_id' => $goods_id,
    'style_id' => $style_id,
    'code' => $code,
);
/**
All Hail Sinri Edogawa!
**/
if(isset($Sinri_SM_FILTER)){
    $filter['SM_FILTER']=$Sinri_SM_FILTER;
}


//$batch_no = $db->getOne("select batch_no from romeo.shipment_picklist order by id desc limit 1");
//$batch_no = empty($batch_no) ? 0 : $batch_no;
//$sql_shipment_pick = "
//	select id, goods_id, style_id, code, name, number, created_time 
//	from romeo.shipment_picklist
//	where ". party_sql('party_id') ."
//	    and batch_no = {$batch_no}
//	group by goods_id, style_id, code
//	order by id, number desc
//	limit 20 ";
//$goods_mapping = $db->getAll($sql_shipment_pick);
//$smarty->assign('goods_mapping', $goods_mapping);
// 链接
$url = 'shipment_list.php';
$url = add_param_in_url($url, 'size', $filter['size']);
$goods_sql = "";
if (!empty($filter['goods_id'])) {
	$sql_s = "";
	if (!empty($filter['style_id'])) {
		$goods_sql = " and not exists (select 1 from ecshop.ecs_order_goods og 
			where oi.order_id = og.order_id and og.goods_id is not null and (og.goods_id, og.style_id) 
				not in (({$filter['goods_id']}, {$filter['style_id']})) limit 1)";
	} else {
		$goods_sql = " and not exists (select 1 from ecshop.ecs_order_goods og
            where oi.order_id = og.order_id and og.goods_id is not null and og.goods_id <> '{$filter['goods_id']}' limit 1)";
	}
	
} elseif (!empty($filter['code'])) {
	$goods_sql = " and exists (
        select 1 from ecshop.order_attribute oa 
        where oi.order_id = oa.order_id 
			and oa.attr_name = 'TAOBAO_ITEM_MEAL_NAME_EX' 
			and oa.attr_value REGEXP '".$filter['code']. ';'."') 
	";
}
// 从所有预订成功的订单中取得待发货列表
$sql_from = "SELECT
        s.SHIPMENT_ID,s.CARRIER_ID,s.PARTY_ID,s.PRIMARY_ORDER_ID
    from
        romeo.order_inv_reserved r
        inner join romeo.order_shipment m on m.ORDER_ID=CONVERT(r.ORDER_ID using utf8)
        inner join romeo.shipment s on s.SHIPMENT_ID=m.SHIPMENT_ID
        inner join ecshop.ecs_order_info oi on oi.order_id = r.order_id
    where
        r.STATUS = '$reserved_status' and s.STATUS = 'SHIPMENT_INPUT' and s.shipping_category = 'SHIPPING_SEND' 
        and oi.shipping_status = '0' and oi.order_status = '1'
        and ". facility_sql('r.FACILITY_ID') ." and ". party_sql('r.PARTY_ID') 
		.$goods_sql.
    "group by 
        s.SHIPMENT_ID
    limit 6000
";
$ref_fields=$ref_rowset=array();
$list=$db->getAllRefby($sql_from,array('SHIPMENT_ID'),$ref_fields,$ref_rowset);
if ($list) {
    // 统计总数
    $taxonomy = array();
    
    // 排序用
    $sort=array(
        'printed'=>array(), 		// 是否已经打印了
    	'order_time'=>array(),		// 下单时间
    	'confirm_time'=>array(),	// 确认时间
    	'reserved_time'=>array(),	// 预定时间
    );

    // 取得Shipment对应的订单列表
    // 判断Shipment对应的订单，是不是每个订单都已经预订上了
    // 如果Shipemt中有一个订单没有预定库存（合并发货），说明这个Shipment不能发货
    $taobao_order_sn_list_string = $_REQUEST['taobao_order_sn_list'];
    $taobao_order_sn_list = preg_split("/[\s,]+/", $taobao_order_sn_list_string);
    $taobao_order_sn_list = implode(',', $taobao_order_sn_list);
    if(!$taobao_order_sn_list)
    {
        $smarty->display('shipment/shipment_list_for_input_shipment.htm');
        die();
    }

    $sql="SELECT
            o.order_id,o.order_status,o.pay_status,o.shipping_status,o.facility_id,
            o.order_time,o.order_sn,o.taobao_order_sn,o.consignee,o.distributor_id, if(o.handle_time = 0, o.order_time, FROM_UNIXTIME(o.handle_time)) handle_time,
        	IF( o.order_type_id = 'SALE', 
        		(	SELECT 	a.action_time
					FROM 	ecshop.ecs_order_action a
					WHERE 	a.order_id =  o.order_id
					AND 	a.order_status = '1'
					AND NOT EXISTS (
						SELECT 		b.action_time
						FROM 		ecshop.ecs_order_action b
						WHERE 		b.order_id = o.order_id
						AND 		b.order_status =  '2'
						AND 		b.action_time > a.action_time
						ORDER BY 	b.action_time DESC 
						LIMIT 1
					)
					ORDER BY a.action_time ASC 
					LIMIT 1
        		), 
        		o.order_time ) AS confirm_time,
            -- IF( (SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND pick_list_status = 'printed' AND created_by_user_class = 'worker' LIMIT 1), 1, 0) AS printed,
            0 AS printed,
            r.STATUS as RESERVED_STATUS, r.RESERVED_TIME AS reserved_time, m.SHIPMENT_ID
        from
            romeo.order_shipment m
            left join romeo.order_inv_reserved r on r.ORDER_ID = m.ORDER_ID
            left join ecshop.ecs_order_info as o on o.order_id = m.ORDER_ID
        where
            (o.handle_time = 0 or o.handle_time < UNIX_TIMESTAMP()) and m.SHIPMENT_ID ".db_create_in($ref_fields['SHIPMENT_ID']).
            "and o.taobao_order_sn in ({$taobao_order_sn_list})";
    $result=$db->getAllRefby($sql,array('SHIPMENT_ID'),$ref_fields1,$ref_rowset1);
    if ($result) {
        $unset=array();
        foreach($list as $key=>$item) {
        	$list[$key]['printed']=1;
        	$sort['printed'][$key]=1;
              
            $shipment_id=$item['SHIPMENT_ID'];
            if (isset($ref_rowset1['SHIPMENT_ID'][$shipment_id])) {
	            // 发货单打印状态（由订单打印状态判断）
	            $order_list = &$ref_rowset1['SHIPMENT_ID'][$shipment_id];
	            
	           
	            foreach($order_list as $order_item) {
	                //如果是jjshouse组织,在打拣货单之前要选择一次快递方式,如果没有选择这种记录就会显示出来
	                if(is_jjshouse($party_id)){
	                    $check_sql = "
	                        select count(*) as num from ecshop.ecs_order_action 
	                        where order_id = {$order_item['order_id']} and action_note like 'jjhouse分拣%' ";
	                     $res = mysql_fetch_array($GLOBALS ['db']->query($check_sql));
	                     if($res['num'] == 0){
	                        $unset[] = $key;
						    continue 2;
	                     }
	                }
	                // 该Shipment不是所有的订单都预订了
	               
	                if ($order_item['RESERVED_STATUS']!='Y') {
	                    $unset[]=$key;
						continue 2;
	                }
	                // 判断该Shipment是不是已打印发货单
	                if (!$order_item['printed']) {
	                    $list[$key]['printed']=0;
	                    $sort['printed'][$key]=0;
	                }
	                
	             	// 该shipment订单下单时间
	             	$sort[$sort_method][$key]=$order_item[$sort_method];
	                if (!isset($sort[$sort_method][$key])){
	                	$sort[$sort_method][$key]=$order_item[$sort_method];
	                }
	                
	                // 判断配送的主订单
	                if ($item['PRIMARY_ORDER_ID']==$order_item['order_id']) {
	                	$list[$key]['FACILITY_ID']=$order_item['facility_id'];
	                	$list[$key]['DISTRIBUTOR_ID']=$order_item['distributor_id'];
	                }
	            }
	            
	            // 是否合并发货
	            if(count($order_list)>1){
	                $list[$key]['is_merge_shipment']=true;
	            }

	            // 订单列表
	            $list[$key]['order_list']=$order_list;

                // 按仓库分
				if (isset($facility_list[$list[$key]['FACILITY_ID']])) {
					$taxonomy['facility'][$list[$key]['FACILITY_ID']]++;
        		}
				// 按快递分
				if (isset($carrier_list[$list[$key]['CARRIER_ID']])) {
					$taxonomy['carrier'][$list[$key]['CARRIER_ID']]++;
        		}
        		// 按分销商分
        		if (isset($distributor_list[$list[$key]['DISTRIBUTOR_ID']])) {
					$taxonomy['distributor'][$list[$key]['DISTRIBUTOR_ID']]++;
        		}
        		// 按组织分
        		$taxonomy['party'][$list[$key]['PARTY_ID']]++;
            }
            else {
				$unset[]=$key;
            }
        }

        // 从列表中去掉有未预订上的
        if($unset!==array()){
            foreach($unset as $k) {
                unset($list[$k]);
                unset($sort['printed'][$k]);
                unset($sort[$sort_method][$k]);
            }
        }
    }



    // 排序
    // 1. 按是否已打印拣货单
    if (!empty($sort['printed']) || !empty($sort[$sort_method])) {
        array_multisort($sort['printed'], SORT_ASC, $sort[$sort_method], SORT_ASC, $list);
    }
	
    // 构造分页
    $total=count($list); // 总记录数
    $total_page=ceil($total/$page_size);  // 总页数
    $page=max(1, min($page, $total_page));
    $offset=($page-1)*$page_size;
    $limit=$page_size;

    // 分页
    if($page_size<65535){
		$list=array_splice($list, $offset, $limit);
    }

    // 分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $filter
    );
  
	$smarty->assign('total', $total);  // 总数
	$smarty->assign('list', $list);  // 当前页列表
	$smarty->assign('taxonomy', $taxonomy);  // 分类统计
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页	
}

/**
All Hail Sinri Edogawa
**/
$smarty->assign('now_party_id',$party_id);

$smarty->assign('url', $url);
$smarty->assign('filter', $filter);                          // 筛选条件
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('sort_method', $sort_method);      			 // 默认排序方式
$smarty->assign('sort_method_list', $sort_method_list);      // 排序方式列表
$smarty->assign('facility_list', $facility_list);            // 用户所处仓库列表
$smarty->assign('carrier_list', $carrier_list);              // 配送方式列表 
$smarty->assign('distributor_list',$distributor_list);       // 分销商
$smarty->display('shipment/shipment_list_for_input_shipment.htm');
