<?php 

/**
 * 库存预定状态表
 * 
 * @author yxiang@oukoo.com
 * @copyright 2010 ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('inventory_reserved');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');


// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('search','cancel')) 
    ? $_REQUEST['act'] 
    : NULL ;
// 状态
$status =
	isset($_REQUEST['status']) && in_array($_REQUEST['status'], array('Y','N'))
	? $_REQUEST['status']
	: 'Y';
// 当前页码
$page = 
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
// 每页多少记录数
$page_size =
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 20 ;
    
// 消息
$message =
    isset($_REQUEST['message']) && trim($_REQUEST['message'])
    ? $_REQUEST['message']
    : false;

// 取消预订
if ($act=='cancel') {
    admin_priv('inventory_reserved_cancel');
    $order_id=$_REQUEST['order_id'];
    if($order_id){
        try {
            $handle=soap_get_client('InventoryService');
            $response = $handle->cancelOrderInventoryReservation(array('orderId'=>$order_id));
            if (isset($response->return)) {
                $smarty->assign('message','已取消预订');
            }
        } catch (Exception $e) {
            $smarty->assign("message",$e->getMessage()); 
        }
    }
}
    
// 所处仓库
$facility_list = get_user_facility();
if (empty($facility_list)) {
	die('没有仓库权限'); 
}

// 订单类型
$order_type_list = array('SALE' => '销售订单', 'RMA_EXCHANGE' => '换货订单', 'SHIP_ONLY' => '补寄订单');

// 配送方式
$shipping_type_list = Helper_Array::toHashmap(getShippingTypes(), 'shipping_id', 'shipping_name');
    
// 查询每页显示数列表
$page_size_list = array('20' => '20', '50' => '50', '100' => '100', '65535' => '不分页');

// 过滤条件
$filter = array(
    // 每页多少条记录
    'size' => 
        $page_size,
    // 仓库
    'facility_id' => 
        isset($_REQUEST['facility_id']) && isset($facility_list[$_REQUEST['facility_id']]) 
        ? $_REQUEST['facility_id'] 
        : NULL ,
    // 订单类型
    'order_type_id' => 
        isset($_REQUEST['order_type_id']) && isset($order_type_list[$_REQUEST['order_type_id']]) 
        ? $_REQUEST['order_type_id'] 
        : NULL ,
    // 配送方式
    'shipping_id' =>
        isset($_REQUEST['shipping_id']) && isset($shipping_type_list[$_REQUEST['shipping_id']]) 
        ? $_REQUEST['shipping_id'] 
        : NULL ,
    // 是否已预订成功
	'status'=>$status, 
);

// 链接
$url = 'inventory_reserved_order.php';
foreach ($filter as $k => $v) {
	if ($v) {
		$url = add_param_in_url($url, $k, $v);
	}
}


$condition = '';
if ($filter['facility_id']) {
	$condition .= " AND o.facility_id = '{$filter['facility_id']}'";
} else {
	$condition .= " AND ". facility_sql('o.facility_id');
}
if ($filter['order_type_id']) {
	$condition .= " AND o.order_type_id = '{$filter['order_type_id']}'";
}
if ($filter['shipping_id']) {
	$condition .= " AND o.shipping_id = '{$filter['shipping_id']}'";
}
$sql_from = "
	from 
		romeo.order_inv_reserved r
		inner join ecshop.ecs_order_info o on o.order_id=r.ORDER_ID
	where
		".party_sql('r.PARTY_ID')." and r.STATUS = '{$status}' {$condition} 
";

// 取得预订信息
$total = $db->getOne("select count(*) $sql_from"); // 总记录数
$total_page = ceil($total/$page_size);  // 总页数
$page = max(1, min($page, $total_page));
$offset = ($page-1)*$page_size;
$limit = $page_size;
$sql = "select
	r.*,
	o.order_id, o.order_sn, o.consignee, o.order_time, o.facility_id,
	o.order_type_id, o.shipping_id, o.distributor_id,
	IF( o.order_type_id = 'SALE', 
		(SELECT action_time FROM ecshop.ecs_order_action WHERE order_id = o.order_id AND order_status = 1 LIMIT 1), 
		o.order_time ) AS confirm_time
	{$sql_from} limit {$offset}, {$limit}
";
$links = array(
	array(
		'sql' => '
			SELECT r.*, og.goods_id,og.style_id,og.goods_name,og.goods_number
			FROM ecshop.ecs_order_goods og
				left join romeo.order_inv_reserved_detail r on r.ORDER_ITEM_ID=og.rec_id
			WHERE :in
		',
		'source_key' => 'ORDER_INV_RESERVED_ID',
		'target_key' => 'ORDER_INV_RESERVED_ID',
		'mapping_name' => 'goods_list',
		'type' => 'HAS_MANY',
	),
);
$list=$db->findAll($sql,$links);
if ($list) {
	// 取得要查询库存总表的产品
	$productIds=array();
	foreach($list as $key1=>$item) {
		if(!empty($item['goods_list'])) {
			foreach($item['goods_list'] as $key2=>$goods) {
				$productId=getProductId($goods['goods_id'],$goods['style_id']);
				if($productId){
					$productIds[$productId]=true;
				}
				$list[$key1]['goods_list'][$key2]['productId']=$productId;
			}
		}
	}
	
	// 取得库存总表
	$request=array(
		'statusId'=>array('INV_STTS_AVAILABLE','INV_STTS_USED'),
		'facilityId'=>array_keys($facility_list),
		'productId'=>array_keys($productIds),
	);
	$handle=soap_get_client('InventoryService');
	$response=$handle->getInventorySummaryByCondition($request);
	$inventorySummaryAssoc=array();
	if(is_object($response->return->InventorySummary)) {
		$obj=$response->return->InventorySummary;
		$primaryKey = $obj->statusId.'_'.$obj->productId.'_'.$obj->facilityId.'_'.$obj->containerId;
		$inventorySummaryAssoc[$primaryKey]=$response->return->InventorySummary;
	}
	else if (is_array($response->return->InventorySummary)) {
		foreach($response->return->InventorySummary as $obj) {
			$primaryKey = $obj->statusId.'_'.$obj->productId.'_'.$obj->facilityId.'_'.$obj->containerId;
			$inventorySummaryAssoc[$primaryKey]=$obj;
		}
	}
	
	// 取得每个商品的库存总表记录
	foreach($list as $key1=>$item) {
		if(!empty($item['goods_list'])) {
			foreach($item['goods_list'] as $key2=>$goods) {
				$primaryKey = $goods['STATUS_ID'].'_'.$goods['productId'].'_'.$goods['FACILITY_ID'].'_'.$goods['CONTAINER_ID'];
				if(isset($inventorySummaryAssoc[$primaryKey])) {
					$list[$key1]['goods_list'][$key2]['availableToReserved'] = $inventorySummaryAssoc[$primaryKey]->availableToReserved;
					$list[$key1]['goods_list'][$key2]['stockQuantity'] = $inventorySummaryAssoc[$primaryKey]->stockQuantity;
				}
			}
		}
	}
	
    // 分页
    $pagination = new Pagination(
        $total, $page_size, $page, 'page', $url, null, $filter
    );

	$smarty->assign('total', $total);  // 总数
	$smarty->assign('list', $list);    // 当前页列表
    $smarty->assign('pagination', $pagination->get_simple_output());  // 分页	
}

$smarty->assign('url', $url);
$smarty->assign('filter', $filter);                          // 筛选条件
$smarty->assign('page_size_list', $page_size_list);          // 每页显示数列表
$smarty->assign('facility_list', $facility_list);            // 用户所处仓库列表
$smarty->assign('order_type_list', $order_type_list);        // 订单类型列表
$smarty->assign('shipping_type_list', $shipping_type_list);  // 配送方式列表 

$smarty->display('oukooext/inventory_reserved_order.htm');

