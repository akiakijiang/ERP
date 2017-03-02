<?php

/**
 * 为自动完成提供ajax数据
 * 
 * 该文件用来为常用的ajax请求提供数据， 函数命名规范为 "ajax_函数名"
 * 当请求为 "ajax.php?act=foo" 时，会自动调用 ajax_foo 函数，并将函数返回的结构以josn的形式返回
 * 
 * 函数名的约定：
 *   1). 有ajax_search_XXX 约定的一般是以搜索为目的，为自动完成提供数据的
 *   2). 有ajax_get_XXX 约定的一般是以获取为目的，比如要取得某一个指定商品的属性
 * 
 * 另外ajax请求一般要求迅捷，请避免冗余查询或查询大量数据，比如查出某个表的所有字段
 * 
 * @author yxiang@oukoo.com
 * @category www.ouku.com
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'RomeoApi/lib_refund.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once('config.vars.php');
require_once(ROOT_PATH . 'admin/includes/lib_filelock.php');
require_once ('function.php');
require_once('includes/lib_service.php');

// 通过请求自动调用函数
$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : false;
if ($act && function_exists('ajax_'.$act))
{
    unset($_REQUEST['act']);
    // $_POST的数据会传递给回调函数
    $result = call_user_func('ajax_'.$act, isset($_POST) ? $_POST : null);
    if (!$result) $result = array();
    // 以json格式输出数据
    header('Content-type: text/html; charset=utf-8');
    $json = new JSON;
    print $json->encode($result);
}

 /**
 * 检测工牌号的合法性  jwli 2016.02.24
 */
 function ajax_check_batch_employee_sn($args) {   

    if (!empty($args)) extract($args);
    $batch_employee_sn = trim($batch_employee_sn);
    $result = array();
    if(empty($batch_employee_sn)){
        $result['error'] = "工牌号不存在！";
        $result['success'] = false;
        return $result;
    }
    require_once ("function.php");
    $res = check_batch_employee_sn($batch_employee_sn);

    if($res['success']){
        $result['success'] = true;
        $result['employee_name'] = $res['employee_name'];
        $result['employee_no'] = $res['employee_no'];
    }else{
        $result['success'] = false;
        $result['error'] = $res['error'];
        $result['error_id'] = $res['error_id'];
    }
    return $result;
 }

 /**
 * 检测批拣单号的合法性  jwli 2016.02.24
 */
 function ajax_check_batch_pick_sn($args) {    
    if (!empty($args)) extract($args);
    $batch_pick_sn = trim($batch_pick_sn);
    $employee_no = trim($employee_no);
    $result = array();

    if(empty($batch_pick_sn)){
        $result['error'] = "批拣单号不存在！";
        $result['success'] = false;
        return $result;
    }
    require_once ("function.php");
    $res = check_batch_pick_sn($batch_pick_sn,$employee_no);
    if($res['success']){
        $result['success'] = true;
    }else{
        $result['success'] = false;
        $result['error'] = $res['error'];
        $result['error_id'] = $res['error_id'];
    }
    return $result;
 }

/**
 * 取消订单前判断是否绑定码托
 * 如果绑定码托成功，则订单不允许取消
 * by hzhang1 2016-04-06
 */ 
//function ajax_is_bind_mt_order($args){
//	if (!empty($args)) extract($args); 
//	global $db;
//	$sql = "select oi.order_id,oi.party_id,psp.pallet_no,psp.bind_time from ecshop.ecs_order_info oi
//			INNER JOIN romeo.order_shipment os on convert(os.order_id using utf8) = oi.order_id
//			INNER JOIN romeo.shipment s on os.shipment_id = s.shipment_id
//			inner JOIN romeo.pallet_shipment_mapping psp on psp.shipment_id = s.shipment_id
//			where oi.order_id=$order_id and psp.bind_status = 'BINDED'";
//	$isMtOrder = $db->getAll($sql);
//	$result = array(); 
//	if(!empty($isMtOrder)){
//		$result['result'] = 'failure';
//		$result['note'] = '已绑定码托不能取消订单';
//	}else{
//		$result['result'] = 'success';
//		$result['note'] = 'success';
//	}
//	return $result;
//}

/*  该订单是否是参与创建新订单类型的订单合并
 */
function ajax_is_merged_order($args){
	if (!empty($args)) extract($args); 
	$order_id = $_REQUEST['order_id']; 
	global $db;
	$sql = " select order_id,order_sn,root_order_id,root_order_sn
			 from ecshop.order_relation 
			 where (  order_id = '{$order_id}'  OR root_order_id ='{$order_id}' ) and parent_order_sn = 'merge' ";
    $r = $db->getAll($sql); 
    $result = array(); 
    if(empty($r)){
    	$result['is'] = 0;  // 该订单未参与合并订单   A , B => C 
    }else{
    	if( $r[0]['order_id'] == $order_id ){
    		$result['is'] = 2 ; // 该订单为合并后生成的订单
    	}else{
    		$result['is'] = 1; // 该订单和其他订单一起合并 
    	}
    	$result['order_ids'] = $r;
    }
    return $result; 
}

/**
 * 根据工单号，返回对应的下采购订单的信息
 * @param $args POST过来的数据
 * @return 返回标准的数据
 */


function ajax_search_from_carrier_shipment_number($args){
        if (!empty($args)) extract($args);
        global $db;
        $start_time = microtime(true);
        $result = array();
        $from_shipping_id = isset($_REQUEST['from_data']) ? trim($_REQUEST['from_data']) : false;
        $party_id = isset($_REQUEST['party_id']) ? trim($_REQUEST['party_id']) : false;
        $facility_id = isset($_REQUEST['facility_id']) ? trim($_REQUEST['facility_id']) : false;
        
        $sql_for_shipment_number = "SELECT count( DISTINCT s.SHIPMENT_ID ) AS shipment_number
                                    FROM romeo.shipment s
                                    INNER JOIN romeo.order_shipment os ON os.SHIPMENT_ID = s.SHIPMENT_ID
                                    INNER JOIN ecshop.ecs_order_info oi ON convert(oi.order_id USING utf8) = os.order_id
                                    WHERE oi.shipping_id = '{$from_shipping_id}'
                                    AND oi.shipping_status = 0
                                    AND oi.pay_status = 2
                                    AND oi.order_status != 2
                                    AND oi.order_time > '2013-11-10'
                                    AND oi.party_id = '{$party_id}'
                                    AND oi.facility_id = '{$facility_id}';";
        $shipment_number_from_sql = $db->getOne($sql_for_shipment_number);
        
        $result['shipment_number'] = $shipment_number_from_sql;
        $cost_time = microtime(true)-$start_time;
        $result['cost_time'] = $cost_time;
        return $result;
 }


 function ajax_change_carrier($args){
    if (!empty($args)) extract($args);
    global $db;
    $result = array();
    $start_time = microtime(true);
    
    $from_shipping_id = isset($_REQUEST['from_data']) ? trim($_REQUEST['from_data']) : false;
    $target_shipping_id = isset($_REQUEST['target_shipping_id']) ? trim($_REQUEST['target_shipping_id']) : false;
    $changed_number = isset($_REQUEST['changed_number']) ? trim($_REQUEST['changed_number']) : false;
    $party_id = isset($_REQUEST['party_id']) ? trim($_REQUEST['party_id']) : false;
    $facility_id = isset($_REQUEST['facility_id']) ? trim($_REQUEST['facility_id']) : false;

    $sql_for_get_order_ids = "SELECT order_id
                              FROM ecshop.ecs_order_info 
                              WHERE order_time > '2013-11-10'
                              AND shipping_status = 0
                              AND pay_status = 2
                              AND order_status != 2
                              AND shipping_id = '{$from_shipping_id}'
                              AND party_id = '{$party_id}' 
                              AND facility_id = '{$facility_id}'
                              LIMIT {$changed_number};";
    $order_ids = $db->getCol($sql_for_get_order_ids);
    $result['order_id_affected'] = "涉及到的订单：<br>";
    foreach($order_ids as $value){
        $result['order_id_affected'].=$value.="<br>";
    }

    foreach ($order_ids as $value) {
        $change_shipping_result = change_order_shipping($value,$target_shipping_id,'批量修改订单快递');    
    }
    $result['msg'] = $change_shipping_result['message'];
    $cost_time = microtime(true)-$start_time;
    $result['cost_time'] = $cost_time;
    return $result;
 }






function ajax_get_goods_by_dispatch_list_sn ($args) {
    $dispatchListSn = $args['dispatchListSn'];
    if (!$dispatchListSn) {
        return array('error' => '没有输入工单号');
    }
    
    include_once(dirname(__FILE__) . '/../RomeoApi/lib_dispatchlist.php');
    include_once(dirname(__FILE__) . '/function.php');
    
    $criteria = new stdClass();
    $criteria->dispatchSn = $dispatchListSn;
    $criteria->partyId = $_SESSION['party_id'];
    $list = searchDispatchLists($criteria);
    if (!$list) {
        return array('error' => '没有对应的工单');
    }
    
    $dispatchList = $list[0];
    
    $sql = "select goods_id, style_id, goods_name 
        from ecs_order_goods
        where rec_id = '" . $dispatchList->orderGoodsId . "'";
    $orderGoods = $GLOBALS['db']->getRow($sql);
    $providers = getProviders();
    
    if (!in_array($dispatchList->dispatchStatusId, array('OK', 'FINISHED')) ) {
        return array('error' => "工单的状态({$dispatchList->dispatchStatusId})无法生成采购订单");
    }
    
    $can_new_purchase_order = true;
    // 如果工单已完成，则提示
    if (!empty($dispatchList->purchaseOrderSn)) {
        if (!check_admin_priv('dispatch_finished_new_order')
            // || $_SESSION['admin_name'] == 'zwsun'
            ) {
            $can_new_purchase_order = false;
            $info = "工单已下采购订单，无法再下采购订单，请联系有权限的操作人员（qzhong）操作";
        } else {
            $info = "工单已下采购订单，确认是否继续下采购订单";
        }
    } else {
        $info = null;
    }
    
    // 返回的商品列表，目前只有一条记录
    $row = array(
        'goods_id' => $orderGoods['goods_id'],
        'style_id' => $orderGoods['style_id'],
        'goods_name' => $orderGoods['goods_name'],
        'style_name' => ' ',
        'provider_id' => $dispatchList->providerId,
        'provider_name' => $providers[$dispatchList->providerId]['provider_name'],
        'price' => $dispatchList->price, 
        'goods_number' => 1,
        'dispatchListId' => $dispatchList->dispatchListId,
    );
    
    
    return array('rows' => array($row), 'can_new_purchase_order' => $can_new_purchase_order, 'info' => $info);
}

/**
 * 取得供应商
 * 
 * @param string $q  搜索关键字, 如果关键字为字母，按供应商代码搜索，否则按供应商名搜索
 *                   不提供则默认返回所有供应商
 * 
 * @return array
 */
function ajax_get_provider($args)
{
    if (!empty($args)) extract($args);

    if (!is_null($q))
    {
        // 如果关键字为字母, 则表示按code搜索
        if (preg_match("/^[a-z0-9]+$/i", $q))
        {
            $keyword = mysql_like_quote($q);
            $conditions = "AND `provider_code` LIKE '%{$keyword}%'";
        }
        // 否则按供应商名搜索
        else
        {
            $keyword = mysql_like_quote($q);
            $conditions = "AND `provider_name` LIKE '%{$keyword}%'";
        }
    }
    
    require_once(ROOT_PATH . 'admin/includes/lib_main.php');
    // 第三方供应商
    if(check_admin_priv('third_party_warehouse') && ($_SESSION['action_list']!='all')){
	    $sql = "
	        SELECT `provider_id`,`provider_name`,`provider_code`,`provider_order_type` FROM {$GLOBALS['ecs']->table('provider')} 
	        WHERE `provider_status` = 1 {$conditions} and provider_id = '246' limit 1
	    ";    
    } else {
    	 $sql = "
             SELECT `provider_id`,`provider_name`,`provider_code`,`provider_order_type` FROM {$GLOBALS['ecs']->table('provider')} 
             WHERE `provider_status` = 1 {$conditions} ORDER BY `order` DESC, `provider_id`
        ";   
    }

    return $GLOBALS['db']->getAll($sql);
}

/**
 * 取得供应商
 * 
 * @param string $q  搜索关键字, 如果关键字为字母，按供应商代码搜索，否则按供应商名搜索
 *                   不提供则默认返回所有供应商
 * 
 * @return array
 */
function ajax_get_provider_jjs($args)
{
    if (!empty($args)) extract($args);

    if (!is_null($q))
    {
        // 如果关键字为字母, 则表示按code搜索
        if (preg_match("/^[a-z0-9]+$/i", $q))
        {
            $keyword = mysql_like_quote($q);
            $conditions = "AND `provider_code` LIKE '%{$keyword}%'";
        }
        // 否则按供应商名搜索
        else
        {
            $keyword = mysql_like_quote($q);
            $conditions = "AND `provider_name` LIKE '%{$keyword}%'";
        }
    }

    $sql = "
        SELECT p.`provider_id`, p.`provider_name`, p.`provider_code`, p.`provider_order_type` 
        FROM {$GLOBALS['ecs']->table('provider')} p
        	inner join ecshop.ecs_provider_category pc on pc.provider_id = p.provider_id
        WHERE pc.cat_id = 2334 and p.`provider_status` = 1 {$conditions} 
        ORDER BY p.`order` DESC, p.`provider_id`
    ";    

    return $GLOBALS['db']->getAll($sql);
}
/**
 * 搜索分销商
 */
function ajax_search_distributor ($args) {
	if (!empty($args)) extract($args);
	$conditions = "";
	if (!is_null($q)) {
		$keyword = mysql_like_quote($q);
		$conditions .= " and name like '%{$keyword}%' ";
	}
	if (!is_null($_REQUEST['party_id'])) {
		$conditions .= " and party_id = '{$_REQUEST['party_id']}' ";
	}
	$sql = "
		select distributor_id, name
		from ecshop.distributor where 1 {$conditions}
		order by distributor_id 
	";
	$result = $GLOBALS['db']->getAll($sql);
	return $result;
}
/**
 * 搜索商品
 * 
 * @param string $q        // 搜索关键字
 * @param string $field    // 按哪个字段来检索, 默认是 goods_name
 * @param string $category // 商品分类  mobile|fittings|dvd|shoes|notebook|other
 * @param boolean $color   // 是否要求返回每个商品的颜色
 * @param int $limit       // 限制记录条
 * 
 * @return array
 */
function ajax_search_goods($args)
{
    if (!empty($args)) extract($args);

    if ($q)
    {
        // 按哪个字段来搜索
        $fields = array('goods_name');
        $field = ($field && in_array($field, $fields)) ? $field : reset($fields) ;

        // 关键词
        $keyword = mysql_like_quote($q);

        // limit
        $limit = ($limit && is_numeric($limit)) ? $limit : 30 ;

        // 如果限定了商品类别
        if (!empty($category))
        {
            switch ($category)
            {
                case 'mobile' : // 手机
                $conditions = " AND `top_cat_id` = '1'";
                break;
                case 'fittings' : // 配件
                $conditions = " AND `top_cat_id` = '597'";
                break;
                case 'dvd' : // dvd
                $conditions = " AND `cat_id` = '1157'";
                break;
                case 'education' : // 电教产品
                $conditions = " AND `top_cat_id` = '1458'";
                break;
                /*
                case 'shoes' : // 鞋品
                $conditions = " AND `goods_party_id` = " . PARTY_OUKU_SHOES;
                */
                break;
                case 'notebook' : // 笔记本
                $conditions = " AND `top_cat_id` = '414' ";
                break;
                case 'other' : // 其他
                $conditions = " AND (`top_cat_id` NOT IN (1, 597, 1109, 1458) AND `cat_id` != '1157' )";
                break;
            }
        }
        $sql = "
            SELECT concat(`goods_name`, IF(is_on_sale = 0, '(已下架)', '')) as `goods_name`,
                `goods_id`, `goods_party_id`, `cat_id`, `top_cat_id`, `is_on_sale`
            FROM {$GLOBALS['ecs']->table('goods')}
            WHERE (`is_delete` = 0) {$conditions} AND `{$field}` LIKE '%{$keyword}%' AND ". party_sql('goods_party_id') ."
            LIMIT {$limit}
        ";
        $goods = $GLOBALS['db']->getAll($sql);
        if ($color)
        {
            // TODO: 如果要求返回颜色
        }
        return $goods;
    }
}

/**
 * 搜索商品颜色
 * 
 * @param int $goods_id  // 商品id
 * 
 * @return array
 */
function ajax_search_goods_styles($args)
{
    if (!empty($args)) extract($args);
    if ($goods_id)
    {
        $sql = "
            SELECT 
                gs.internal_sku, s.style_id, s.value, CONCAT(s.color, IF(gs.sale_status != 'normal', '(非在售)', '')) AS color
            FROM 
                {$GLOBALS['ecs']->table('goods_style')} gs
                INNER JOIN {$GLOBALS['ecs']->table('style')} s ON s.style_id = gs.style_id
            WHERE gs.style_id = s.style_id AND gs.goods_id = '{$goods_id}'
        ";
        $styles = $GLOBALS['db']->getAll($sql);
    }
    return $styles;
}

/**
 * 搜索商品的配件
 *
 * @param int $goods_id  // 商品id
 *
 * @return array
 */
function ajax_search_goods_fittings($args)
{
    if (!empty($args)) extract($args);
    if ((int)$goods_id) {
        include_once(ROOT_PATH . 'includes/lib_goods.php');
        $result = get_goods_fittings($goods_id);
    }
    return $result;
}

/**
 * 搜索订单
 * 
 * @param string $q       // 搜索关键字
 * @param string $field   // 按哪个字段来检索
 * @param int $limit      // 限制记录条
 */
function ajax_search_order($args)
{
    if (!empty($args)) extract($args);

    if ($q)
    {
        // 按哪个字段来搜索
        $fields = array('order_sn');
        $field = ($field && in_array($field, $fields)) ? $field : reset($fields) ;

        // 关键词
        $keyword = mysql_like_quote($q);

        // limit
        $limit = ($limit && is_numeric($limit)) ? $limit : 30 ;

        $sql = "
            SELECT `order_id`, `order_sn`, `consignee`, `pay_name`, `order_amount` FROM {$GLOBALS['ecs']->table('order_info')}
            WHERE `{$field}` LIKE '%{$keyword}%'
            LIMIT {$limit}
        ";
        $orders = $GLOBALS['db']->getAll($sql);
        return $orders;
    }
}

/**
 * 搜索用户
 * 
 * @param string $q       // 搜索关键字
 * @param string $field   // 按哪个字段来检索
 * @param int $limit      // 限制记录条
 */
function ajax_search_user($args)
{
    if (!empty($args)) extract($args);

    if ($q) {   
        if ($field) {
            // 如果指定了按哪个字段来搜索
            $fields = array('user_name', 'user_id', 'user_realname');
            $field = in_array($field, $fields) ? $field : reset($fields) ;
        } else {
            // 否则按照特征来匹配
            if (preg_match('/^[a-z0-9]+[._\-\+]*@([a-z0-9]+[-a-z0-9]*\.)+[a-z0-9]+$/i', $q)) {
                $field = 'email';
            } else if (preg_match('/^#([0-9]+)$/', $q, $matches)) {
                $field = 'user_id';
                $q = $matches[1];
            } else {
                $field = 'user_name';
            }
        }
        
        if ($field == 'user_id') {
            $sql = "
                SELECT user_id, user_name FROM {$GLOBALS['ecs']->table('users')}
                WHERE user_id = '{$q}' LIMIT 1
            ";
            $u = $GLOBALS['db']->getRow($sql); 
            if ($u) { $users = array($u); } 
        } else {
            // 关键词
            $keyword = mysql_like_quote($q);
            // limit
            $limit = ($limit && is_numeric($limit)) ? $limit : 30 ;
            $sql = "
                SELECT `user_id`, `user_name` FROM {$GLOBALS['ecs']->table('users')}
                WHERE `{$field}` LIKE '%{$keyword}%'
                LIMIT {$limit}
            ";
            $users = $GLOBALS['db']->getAll($sql);
        }
        
        return $users;
    }
}

/**
 * 搜索admin用户
 * 
 * @param string $q       // 搜索关键字
 * @param string $field   // 按哪个字段来检索
 * @param int $limit      // 限制记录条
 */
function ajax_search_admin_user($args)
{
    if (!empty($args)) extract($args);

    if ($q) {   
        if ($field) {
            // 如果指定了按哪个字段来搜索
            $fields = array('user_name', 'user_id', 'real_name');
            $field = in_array($field, $fields) ? $field : reset($fields) ;
        } else {
            // 否则按照特征来匹配
            if (preg_match('/^([0-9]+)$/', $q, $matches)) {
                $field = 'user_id';
                $q = $matches[1];
            } else if (preg_match("/^[\x{4e00}-\x{9af5}]+$/u", $q, $matches)) {
                $field = 'real_name';
            } else {
                $field = 'user_name';
            }
        }
        
        if ($field == 'user_id') {
            $sql = "
                SELECT user_id, user_name FROM ecshop.ecs_admin_user
                WHERE user_id = '{$q}' LIMIT 1
            ";
            $u = $GLOBALS['db']->getRow($sql); 
            if ($u) { $users = array($u); } 
        } else {
            // 关键词
            $keyword = mysql_like_quote($q);
            // limit
            $limit = ($limit && is_numeric($limit)) ? $limit : 30 ;
            $sql = "
                SELECT `user_id`, `user_name`, `real_name`  FROM ecshop.ecs_admin_user
                WHERE `{$field}` LIKE '%{$keyword}%' and status = 'OK'
                LIMIT {$limit}
            ";
            $users = $GLOBALS['db']->getAll($sql);
        }
        
        return $users;
    }
}


/**
 * 通过发票号取得商品明细
 */
function ajax_search_purchase_invoice_item_to_match($args)
{
    if (!empty($args)) extract($args);
    $invoice_no = trim($invoice_no);

    include_once(ROOT_PATH . "RomeoApi/lib_soap.php");
    include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
    $purchase_invoice_soapclient = soap_get_client("PurchaseInvoiceService");

    if ($invoice_no)
    {
        $result = $purchase_invoice_soapclient->getPurchaseInvoiceItemToMatchByInvoiceNo(array("arg0"=>"$invoice_no"));
        $items = $result->return->result->anyType;
        if (!is_array($items) && is_object($items)) {
            $items = array($items);
        }
        if (is_array($items)) {
            foreach ($items as $key => $item) {
                $items[$key]->product_map = getGoodsIdStyleIdByProductId($item->productId);
            }
        }
        return $items;
    }
}

/**
 * 取得退款明细类型
 * 
 * @param int $id 如果不传入id则返回明细类型列表
 * 
 * @return object|array
 */
function ajax_get_refund_detail_type($args)
{
    if (!empty($args)) extract($args);

    if (!function_exists('refund_get_refund_detail_type'))
    require_once(ROOT_PATH . 'RomeoApi/lib_refund.php');

    if ((int) $id)
    {
        return refund_get_refund_detail_type($id);
    }
    else
    {
        return refund_detail_type_list();
    }
}

/**
 * 取得订单信息
 * 
 * @param int $order_id  订单id
 * @param string $order_sn 订单sn
 * 
 * @return array
 */
function ajax_get_order_info($args)
{
    if (!empty($args)) extract($args);
    if ((int) $order_id)
    {
        if (!function_exists('order_info'))
        require_once(ROOT_PATH . 'includes/lib_order.php');

        return order_info($order_id, $order_sn);
    }
}

/**
 * 通过订单id来创建退款申请
 * 
 * @param int $order_id 订单号
 * @param int $ignore_unexecute 是否忽视未执行的申请单继续创建
 * @param int $refund_type 退款类型
 * 
 * @return array
 */
function ajax_create_refund_apply($args)
{
    if (!empty($args)) extract($args);

    // 返回值
    $result  = false;
    $message = '';

    do
    {
        if (!is_numeric($order_id))
        {
            $message = '没有输入订单号';
            break;
        }

        if (!function_exists('refund_get_soap_client')) {
            require_once(ROOT_PATH . 'RomeoApi/lib_refund.php');
        }

        $handle  = refund_get_soap_client();

        // 当必须检查该订单已存在退款申请
        if ($check_exists)
        {
            try
            {
                $ret = $handle->countRefundByOrderId(array('arg0' => $order_id))->return;
            }
            catch (SoapFault $e)
            {
                $message = "SOAP取得该订单已有的退款单数错误(错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
                break;
            }

            if ($ret > 0)
            {
                $message = "该订单已经存在退款申请单了";
                $result  = 'confirm'; // 需要再次确认
                break;
            }
        }

        // 当必须检查是否已存在未执行的退款申请单
        if ($check_unexecute)
        {
            // 取得该订单未执行的退款单
            try
            {
                $ret = $handle->getUnexecutedRefundInfoByOrderId(array('arg0' => $order_id))->return->UnexecutedRefundInfo;
                if (!empty($ret))
                {
                    // 取得退款单号
                    if (is_object($ret))
                    $reund_id_array[] = $ret->refundId;
                    else if (is_array($ret))
                    {
                        foreach ($ret as $item)
                        $reund_id_array[] = $item->refundId;
                    }
                    $message = "该订单已经存在未处理的退款申请了，您要继续吗？已存在退款单号:". implode(', ', $reund_id_array);
                    $result = 'confirm'; // 需要再次确认
                    break;
                }
            }
            catch (SoapFault $e)
            {
                $message = "SOAP取得该订单未执行的退款单错误(错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
                break;
            }
        }

        // 创建退款单
        try
        {
            $args = array('arg0' => $order_id, 'arg1' => $_SESSION['admin_name'], 'arg2' => $refund_type);
            $ret = $handle->createRefundByOrderId($args)->return;
            $result = $ret;
            $message = "生成退款单成功，退款单号为{$result}";
        }
        catch (SoapFault $e)
        {
            $message = "SOAP创建退款申请失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
        }

    } while (false);

    return compact('result', 'message');
}

/**
 * 查询某个串号是否已经在库存中了
 *
 * @param string $erp_goods_sn 产品串号
 * @return array
 */
function ajax_is_in_storage($args) {
    if (!empty($args)) extract($args);
    $serial_number = explode('|', $serial_number);
    $in_serial_number = db_create_in($serial_number, 'serial_number');
    $sql = "SELECT serial_number from romeo.inventory_item where status_id='INV_STTS_AVAILABLE' and quantity_on_hand_total > 0 and $in_serial_number
            ";
    //Qlog::log('ajax_is_in_storage:'.$sql);       
    $in_storage = $GLOBALS['db']->getCol($sql);
    $in_storage = join(',', $in_storage);
    return compact('in_storage', 'index');
}

/**
 * 使用红包
 *
 * 检测红包是否存在，且未使用（调用service textGiftTicket）
 * 检测红包是否领用，如果领用用户不是红包的拥有者则退出
 * 检查红包限制是否达到要求
 * 使用红包（调用service useGiftTicket）
 * 重新计算订单的金额
 * @param string $code
 * @param string $order_id
 */
function ajax_test_bonus_id($args) {
    global $db, $ecs;
    $code = $args['code'];
    $order_id = $args['order_id'];

    // require_once("includes/lib_bonus.php");
    // return test_bonus_id($code, $order_id);

    return array('info' => '欧币红包系统已被封印', 'result' => false, 'value' => 0);
}

/**
 * 未发货统计增加备注
 *
 * @param array $args
 * @return unknown
 */
function ajax_remark_not_shipped_statistics($args) {
    if (!empty($args)) extract($args);
    if (!function_exists('get_core_order_info')) {
        require_once(ROOT_PATH . 'admin/function.php');
    }
    $order_info = get_core_order_info('', $order_id);

    if ($order_info['order_info_md5'] != $order_info_sign) {
        $error = '订单已经修改，请刷新页面';
        return compact('error', 'order_id');
    }
    global $db, $ecs;
    $action_time = date("Y-m-d H:i:s");
    $action_user = $_SESSION['admin_name'];
    $sql = "INSERT INTO {$ecs->table('shipping_action')}
			(order_id, action_time, action_note, action_user) 
			VALUES('{$order_id}', '{$action_time}', '{$action_note}', '{$action_user}')";
    $db->query($sql);

    //更新未发货列表
    $sql_not_shipped = "SELECT 1 FROM {$ecs->table('not_shipped_statistics')} WHERE order_id = '{$order_id}' ";
    if ($db->getOne($sql_not_shipped)) {
        $sql_not_shipped = "UPDATE {$ecs->table('not_shipped_statistics')} SET `update_purchaser` = NOW() WHERE order_id = '{$order_id}' ";
    } else {
        $sql_confirm_time = "
			SELECT IF(LENGTH(order_sn) = 10, (SELECT action_time FROM {$ecs->table('order_action')} a WHERE a.order_id = o.order_id AND order_status = 1 limit 1), order_time) 
				FROM {$ecs->table('order_info')} AS o 
			WHERE order_id = '{$order_id}' LIMIT 1
				";
        $confirm_time = $db->getOne($sql_confirm_time);
        $sql_not_shipped = "INSERT INTO {$ecs->table('not_shipped_statistics')} (`order_id`, `create`, `update_purchaser`, `confirm_time`) VALUES ('{$order_id}', NOW(), NOW(), '{$confirm_time}') ";
    }
    $db->query($sql_not_shipped);
    return compact('order_id', 'action_note', 'action_user', 'action_time');
}

function ajax_get_goods_cate($args) {
    if (!empty($args)) extract($args);
    global $db, $ecs;

    $cat_id = str_replace(array("\\","\"", "'"), '', $cat_id);
    $cat_name = '';
    $options = '';
    switch ($class) {
        case 'cat_id_0':
            $cat_name = 'cat_id_1';
            switch ($cat_id) {
            	/*
            	case 'mobile'://手机组
                    $options = "<option value='all' > ALL </option>".
                               "<option value='1' >手机</option>".
                               "<option value='597' >手机配件</option>".
                               "<option value='digit_fitting' >数码配件</option>".
                               "<option value='earphone' >耳机/耳麦</option>";
                    break;
                case 'shoe'://鞋子组
                    $options = "<option value='all' > ALL </option>";
                    break;
                case 'customize_phone'://定制手机组
                    $options = "<option value='all' > ALL </option>";
                    break;
				*/
                case 'dvd'://DVD组
                    $options = "<option value='all' > ALL </option>";
                    break;
                case 'education'://电教组
                    $options = "<option value='all' > ALL </option>";
                    break;
                case 'ec': // 电子商务
                    $options = "<option value='all'>ALL</option>".
                               "<option value='2246'>怀轩</option>".
                               "<option value='2264'>森马</option>".
                               "<option value='2265'>夏娃之秀</option>".
                               "<option value='2276'>贝亲</option>".
                    		   "<option value='2284'>多美滋</option>".
                    		   "<option value='2286'>奥普电器</option>";
                    break;
                default:
                    $options = "<option value='all' > ALL </option>";
                    break;
            }
            break;
        case 'cat_id_1':
            $cat_name = 'cat_id_2';
            switch ($cat_id) {
                case 'digit_fitting':
                    $options = "<option value='all' > ALL </option>".
                               "<option value='1086' >存储卡</option>".
                               "<option value='1151' >读卡器</option>";
                    break;
                case '597':
                    $options = "<option value='all' > ALL </option>".
                               "<option value='608' >手机电池</option>".
                               "<option value='1144' >手机贴膜</option>".
                               "<option value='fitting_a' >手机保护套</option>".
                               "<option value='599' >手机充电器</option>".
                               "<option value='603' >手机数据线</option>".
                               "<option value='1122' >车载手机配件</option>".
                               "<option value='609' >蓝牙耳机</option>".
                               "<option value='fitting_other' >其他配件</option>";
                    break;
            	default:
            	    $options = "<option value='all' > ALL </option>";
            		break;
            }
            break;
        default:
            $options = "<option value='all' > ALL </option>";
            break;
    }
    return compact('cat_name', 'options');
}

/**
 * 获取地域列表
 * 
 * @param int $type   地域类型  2为省级，3为市级
 * @param int $parent 父地域的id
 * 
 * @return array
 */
function ajax_get_regions($args)
{
    if (!empty($args)) extract($args);
    
    $result = array();
    $result['regions'] = get_regions($type, $parent);
    if (isset($target)) { 
        $result['target'] = stripslashes(trim($target)); 
    } else {
        $result['target'] = '';
    }
    return $result;
}

/**
 * 获得party_id下的facility
 * @param string $party_id
 */
function ajax_get_available_faciltity($args) {
    if (!empty($args)) extract($args);
    $party_list = array_filter(array_map('trim', explode(',', $party_id)), 'strlen');
    $temp = get_available_facility($party_list);
    $user_facility_id .= ",";
    $result = array();
    foreach ($temp as $facility_id => $facility_name) {
        $owner = false;
        if (strpos($user_facility_id, $facility_id.",") !== false) {
            $owner = true;
        }
        $result[] = array(
            'facility_id' => $facility_id,
            'facility_name' => $facility_name,
            'owner'  => $owner,
        );
    }
    return $result;
}


/**
 * 复核时扫描产品串码
 * 用于出库时扫描串码，返回该订单匹配的Erp记录
 * 
 * @param string $order_sn  订单号，可输入多个，用逗号隔开
 * @param string $barcode   产品串码
 * 
 * ljzhou 2013-10-09
 * @return array(erpid, message, barcode)
 */
function ajax_recheck_scan_barcode($args) {
    if (!empty($args)) extract($args);
    global $db, $slave_db, $ecs;
    
    $result  = false;
    $message = '错误的参数';
    do {
    	// 没有产品串码或订单号
        if (!$barcode || !$order_sn) {
            break;
        }
        
        // 可传入多个order_sn
        $order_sn=preg_split('/\s*,\s*/',$order_sn,-1,PREG_SPLIT_NO_EMPTY);
        if(!is_array($order_sn)) {
            break;
        }
        
        // 查询订单是否存在，并取得订单的所属仓库
        $sql = "
            SELECT order_id, facility_id, party_id
            FROM {$ecs->table('order_info')} WHERE order_sn ". db_create_in($order_sn) ." AND ". party_sql('party_id') ;
        $order_list=$db->getAll($sql);
        //Qlog::log('ajax_recheck_scan_barcode sql:'.$sql);
        if(!$order_list){
            $message = "订单“". implode('“,”',$order_sn)."”不存在，有可能是您没切换组织！";
            break;
        }
        else{
            $facility_array=$party_array=$order_id_array=array();
            foreach($order_list as $order_item){
                $order_id_array[]=$order_item['order_id'];
                $facility_array[]=$order_item['facility_id'];
                $party_array[]=$order_item['party_id'];
            }
            $facility_array=array_unique($facility_array);
            $party_array=array_unique($party_array);
            if(count($facility_array)>1){
                $message = "输入的订单不属于一个仓库";
                break;
            }
            if(count($party_array)>1){
                $message = "输入的订单不属于同一组织";
                break;
            }

            // 取得组织和仓库ID
            $party_id=reset($party_array);
            $facility_id=reset($facility_array);
        }
        
        if (strpos($barcode, '#') !== false) {
        	if (!function_exists('decode_goods_id')) { include_once('function.php'); }
        	list($goods_id, $style_id) = decode_goods_id($barcode);
        	// 检查是否有条码， 要是有 就不让商品编码出库
        	$barcode_check_sql = "select g.goods_id, g.goods_name, ifnull(if(g.barcode is null or trim(g.barcode) = '', gs.barcode, g.barcode), '') as barcode 
                         from ecshop.ecs_goods g left join ecshop.ecs_goods_style gs on g.goods_id = gs.goods_id and gs.is_delete=0
                        where g.goods_id = %d " ;
        	if(intval($style_id) > 0){
        		$barcode_check_sql = $barcode_check_sql . "and gs.style_id = %d" ;
        		$barcode_check = $db->getRow(sprintf($barcode_check_sql, intval($goods_id), intval($style_id)));
        		if(!empty($barcode_check) && !empty($barcode_check['barcode'])){
        			$message = "商品“{$barcode_check['goods_name']}”对应有条码，请根据条码复核。"; 
                    break;
        		}
        	}else{
        		$barcode_check = $db->getRow(sprintf($barcode_check_sql, intval($goods_id)));
        		if(!empty($barcode_check) && !empty($barcode_check['barcode'])){
        			$message = "商品“{$barcode_check['goods_name']}”对应有条码，请根据条码复核。"; 
                    break;
        		}
        	}
        	
            $type = 'NON-SERIALIZED';

        }else{
            // 判断是条码、还是商品串号  先搜条码
        	$goods_style_exists=$db->getRow(sprintf("SELECT gs.goods_id, gs.style_id FROM ecs_goods_style as gs left join ecs_goods as g on g.goods_id = gs.goods_id where gs.barcode='%s' and g.goods_party_id = {$party_id} ", $barcode));
            if ($goods_style_exists) {
            	$goods_id=$goods_style_exists['goods_id'];
            	$style_id=$goods_style_exists['style_id'];
            	$no_serial = true;
            } else {
            	// 判断是否在订单里面
            	$good_sql = "
            		SELECT 		g.goods_id 
            		FROM 		ecs_goods AS g
            		LEFT JOIN 	{$ecs->table('order_goods')} AS og ON og.goods_id = g.goods_id
            		WHERE 		g.barcode = '{$barcode}' 
            		AND 		g.goods_party_id = '{$party_id}' 
            		AND 		og.order_id " . db_create_in($order_id_array)
            	;
            	//Qlog::log('ajax_recheck_scan_barcode 判断是否在订单里面 sql:'.$sql);
            	
                $goods_exists=$db->getRow($good_sql);
                if ($goods_exists) {	
                    $goods_id=$goods_exists['goods_id'];
                    $style_id=0;
                    $no_serial = true;
                }
            }
            
            include_once('function.php'); 
            if($no_serial){
            	// 条码
            	$barcode = encode_goods_id($goods_id,$style_id);
                $type = 'NON-SERIALIZED';
            }
            else{
            	// 串号
            	// 判断是否在订单里面
            	$goods_style = get_goods_style($order_id_array,$barcode);
                if (!empty($goods_style)) {
                    $goods_id = $goods_style['goods_id']; 
                    $style_id = $goods_style['style_id'];
                    
                    $type = 'SERIALIZED';
                } else {
                    $message = "产品条码(串号)“{$barcode}”在订单“". implode('“,”',$order_sn)."”所属仓库中找不到记录，请检查你的条码"; 
                    break;
                }
            	
            }
        	
        }

        // 判断查询出的商品的串号控制类型是否和扫描的串码一致
        include_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
        if ($type != getInventoryItemType($goods_id)) {
            $message = "扫描的串码和该商品串号控制类型不匹配";  
            break;
        }
        // 查询订单商品的未出库的唯一标识id数组，规则为order_goods_id 加上1,2,3...类似于之前的erp_id，唯一标识一个商品库存
        $itemAll = get_one_item_unique_ids($order_id_array,$goods_id,$style_id);
        if(!empty($itemAll)) {
        	$itemid = $itemAll['all_item_id'];
	        foreach($itemid as $id) {
	        	Qlog::log('$id:'.$id);
	        }
        }

        if (empty($itemid)) {
            $message = "产品条码(串号)“{$barcode}”在订单“". implode('“,”',$order_sn)."”找不到对应的itemid" ;  
            break;
        }
        $message = '扫描串码成功';
    }
    while (false);

    return compact('message', 'itemid', 'barcode');
}


/**
 * 扫描产品串码
 * 用于出库时扫描串码，返回该订单匹配的Erp记录
 * 该版本以新库存作为标准
 * 
 * @param string $order_sn  订单号，可输入多个，用逗号隔开
 * @param string $barcode   产品串码
 * 
 * @return array(erpid, message, barcode)
 */
function ajax_scan_barcode_new($args) {
    if (!empty($args)) extract($args);
    global $db, $ecs;
    
    $result  = false;
    $message = '';
    do {
    	// 没有产品串码或订单号
        if (!$barcode || !$order_sn) {
            break;
        }
        
        // 可传入多个order_sn
        $order_sn=preg_split('/\s*,\s*/',$order_sn,-1,PREG_SPLIT_NO_EMPTY);
        if(!is_array($order_sn)) {
            break;
        }
        
        // 查询订单是否存在，并取得订单的所属仓库
        $sql = "
            SELECT order_id, facility_id, party_id
            FROM {$ecs->table('order_info')} WHERE order_sn ". db_create_in($order_sn) ." AND ". party_sql('party_id') ;
        $order_list=$db->getAll($sql);
        if(!$order_list){
            $message = "订单“". implode('“,”',$order_sn)."”不存在,可能是你没切换组织";
            break;
        }
        else{
            $facility_array=$party_array=$order_id_array=array();
            foreach($order_list as $order_item){
                $order_id_array[]=$order_item['order_id'];
                $facility_array[]=$order_item['facility_id'];
                $party_array[]=$order_item['party_id'];
            }
            $facility_array=array_unique($facility_array);
            $party_array=array_unique($party_array);
            if(count($facility_array)>1){
                $message = "输入的订单不属于一个仓库";
                break;
            }
            if(count($party_array)>1){
                $message = "输入的订单不属于同一组织";
                break;
            }

            // 取得组织和仓库ID
            $party_id=reset($party_array);
            $facility_id=reset($facility_array);
        }

        // 搜索串号所属的商品品类
        $sql = "
            SELECT 
                pm.ecs_goods_id as goods_id, pm.ecs_style_id as style_id
            FROM
                romeo.product_mapping pm 
                LEFT JOIN romeo.inventory_item ii ON pm.product_id = ii.product_id and ii.status_id = 'INV_STTS_AVAILABLE'
                LEFT JOIN ecshop.ecs_order_goods og ON pm.ecs_goods_id = og.goods_id and pm.ecs_style_id = og.style_id
            WHERE
                ii.facility_id = '{$facility_id}' AND ii.quantity_on_hand_total > 0 AND og.order_id " . db_create_in($order_id_array) ." 
                 %s
            LIMIT 1                            
        ";
        
        //
        if (strpos($barcode, '#') !== false) {
        	if (!function_exists('decode_goods_id')) { include_once('function.php'); }
        	list($goods_id, $style_id) = decode_goods_id($barcode);
        	// 检查是否有条码， 要是有 就不让商品编码出库
        	$barcode_check_sql = "select g.goods_id, g.goods_name, ifnull(if(gs.barcode is not null and gs.barcode != '', gs.barcode, g.barcode), '') as barcode 
                         from ecshop.ecs_goods g left join ecshop.ecs_goods_style gs on g.goods_id = gs.goods_id and gs.is_delete=0
                        where g.goods_id = %d " ;
        	if(intval($style_id) > 0){
        		$barcode_check_sql = $barcode_check_sql . "and gs.style_id = %d" ;
        		$barcode_check = $db->getRow(sprintf($barcode_check_sql, intval($goods_id), intval($style_id)));
        		if(!empty($barcode_check) && !empty($barcode_check['barcode'])){
        			$message = "商品“{$barcode_check['goods_name']}”对应有条码，请根据条码出库。"; 
                    break;
        		}
        	}else{
        		$barcode_check = $db->getRow(sprintf($barcode_check_sql, intval($goods_id)));
        		if(!empty($barcode_check) && !empty($barcode_check['barcode'])){
        			$message = "商品“{$barcode_check['goods_name']}”对应有条码，请根据条码出库。"; 
                    break;
        		}
        	}

        	$cond = " AND pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}' "; 
            $type = 'NON-SERIALIZED';
            if (!$find = $db->getAll(sprintf($sql, $cond))) {
                $message = "产品“{$barcode}”在该订单所属仓库中无可用库存"; 
                break;
            }
        }else{
            // 判断是条码、还是商品串号  先搜条码
        	$goods_style_exists=$db->getRow(sprintf("SELECT gs.goods_id, gs.style_id FROM ecs_goods_style as gs left join ecs_goods as g on g.goods_id = gs.goods_id where gs.barcode='%s' and g.goods_party_id = {$_SESSION['party_id']} ", $barcode));
            if ($goods_style_exists) {
            	$goods_id=$goods_style_exists['goods_id'];
            	$style_id=$goods_style_exists['style_id'];
            	
                $cond = " AND pm.ecs_goods_id = {$goods_style_exists['goods_id']} AND pm.ecs_style_id = {$goods_style_exists['style_id']} ";            
            } else {
            	$good_sql = "
            		SELECT 		g.goods_id 
            		FROM 		ecs_goods AS g
            		LEFT JOIN 	{$ecs->table('order_goods')} AS og ON og.goods_id = g.goods_id
            		WHERE 		g.barcode = '{$barcode}' 
            		AND 		g.goods_party_id = '{$_SESSION['party_id']}' 
            		AND 		og.order_id " . db_create_in($order_id_array)
            	;
                $goods_exists=$db->getRow($good_sql);
                if ($goods_exists) {	
                    $goods_id=$goods_exists['goods_id'];
                    $style_id=0;
                    $cond = "AND pm.ecs_goods_id = {$goods_exists['goods_id']} AND pm.ecs_style_id=0 ";
                }
            }
            
            if($cond){
            	// 条码
                if (!function_exists('encode_goods_id')) { include_once('function.php'); }
            	$barcode = encode_goods_id($goods_id,$style_id);
                if (!$find = $db->getAll(sprintf($sql, $cond))) {
                    $message = "产品“{$barcode}”在该订单所属仓库中无可用库存"; 
                    break;
                }
                $type = 'NON-SERIALIZED';
            }
            else{
            	include_once('function.php'); 
            	// 串号
                $cond = " AND ii.serial_number = '{$barcode}'";
            	//Qlog::log('scan_barcode serial no cond:sql:'.sprintf($sql, $cond));

                if ($find = $db->getAll(sprintf($sql, $cond))) {
                    if (count($find) > 1) {
                        $message = "根据该产品串号“{$barcode}”搜索出来多条未出库的商品记录，请和ERP组联系"; 
                        break;
                    }
                    $row = reset($find);
                    $goods_id = $row['goods_id']; 
                    $style_id = $row['style_id'];
                    
                    $type = 'SERIALIZED';
                } else {
                    $message = "产品串号“{$barcode}”在订单“". implode('“,”',$order_sn)."”所属仓库中不存在或者已出库"; 
                    break;
                }
            	
            }
        	
        }

        // 判断查询出的商品的串号控制类型是否和扫描的串码一致
        include_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
        if ($type != getInventoryItemType($goods_id)) {
            $message = "扫描的串码和该商品串号控制类型不匹配";  
            break;
        }
        
        // 大订单商品特殊出库
        // 判断订单是否包含大商品,要求是非串号控制
        $big_goods = array(); 
        $has_big_goods = check_has_big_goods($goods_id,$style_id,$order_id_array);
        if($has_big_goods && $type == 'NON-SERIALIZED') {
	        $big_goods['has_big_goods'] = 'Y';   
        }
        if($type == 'SERIALIZED') {
        	$big_goods['is_serial'] = 'SERIALIZED';
        }
        
        $product_id = getProductId($goods_id,$style_id);
        $big_goods['product_id'] = $product_id;
        
        $item_stock_quantity = get_item_stock_quantity($facility_id,$cond);
        $big_goods['item_stock_quantity'] = $item_stock_quantity;
        Qlog::log('scan_barcode product_id:'.$product_id.' item_stock_quantity:'.$item_stock_quantity.' has_big_goods:'.$big_goods['has_big_goods']);
        
    }
    while (false);

    return compact('message','barcode','big_goods');
}
function ajax_unique_trackingNumber($args){
    if (!empty($args)) extract($args);
    global $db;
    $sql = "SELECT shipment_id FROM romeo.shipment WHERE tracking_number = '{$trackingNumber}' limit 1";
    $shipment_id = $db->getOne($sql);
    return $shipment_id;
}

/**
 * 批量验证唯一性
 */
function ajax_unique_trackingNumbers($args){
    if (!empty($args)) extract($args);
    global $db;
    if(empty($trackingNumbers)) return false;
    
    $trackingNumbers = explode(',',$trackingNumbers);
    $sql = "SELECT tracking_number FROM romeo.shipment WHERE tracking_number ".db_create_in($trackingNumbers);
    //Qlog::log('ajax_unique_trackingNumbers:'.$sql);
    $tracking_numbers = $db->getCol($sql);
    if(!empty($tracking_numbers)) {
    	$tracking_numbers = implode(',',$tracking_numbers);
    }
    return $tracking_numbers;
}

/**
 * 是否启用系统计算库存预定
 * @param unknown_type $args
 */
function ajax_update_reserve($args){
    if (!empty($args)) extract($args);
    global $db;
    $sql = "select is_auto_reserve, outer_id from ecshop.ecs_taobao_goods where taobao_goods_id = '{$taobao_goods_id}' ";
    $res = $db->getRow($sql);
    if ($is_auto_reserve == 0) {
        $status = "不使用系统默认预留值";
    } else { 
        $status = "使用系统默认预留值";
    }
    if ($res['is_auto_reserve'] == $is_auto_reserve) {
        
        $message = $res['outer_id']."已经选择".$status."，请重新选择。";
    } else {
        $sql_u = "update ecshop.ecs_taobao_goods set is_auto_reserve = {$is_auto_reserve} where taobao_goods_id = '{$taobao_goods_id}'";
        $result = $db->exec($sql_u);
        if ($result) {
            $message = $res['outer_id']. $status . "修改成功。";
        } else {
            $message = $res['outer_id']. $status . "修改失败。";
        }
    }
    return $message;
}

/**
 * 记录运单的2次重量
 */
function ajax_add_tracking_twice_weight($args) {
    if (!empty($args)) extract($args);
	$tracking_number = trim($tracking_number);
	$first_weight = trim($first_weight);
	$last_weight = trim($last_weight);
	$action_user = $_SESSION['admin_name'];
    if(empty($tracking_number) || empty($first_weight) || empty($last_weight) || empty($action_user)) {
    	QLog::log('$tracking_number:'.$tracking_number.' $first_weight:'.$first_weight.' $last_weight:'.$last_weight.' $action_user:'.$action_user);
    	return false;
    }
    
    global $db;
    $sql = "insert into romeo.tracking_weight (tracking_number,first_weight,last_weight,action_user,created_stamp,last_updated_stamp) values 
           ('{$tracking_number}','{$first_weight}','{$last_weight}','{$action_user}',now(),now())";
    //Qlog::log('ajax_add_tracking_twice_weight:'.$sql);
    
    $result = $db->query($sql);
    return $result;
}

/**
 * 是否启用扣减预警值
 * @param unknown_type $args
 */
function ajax_update_use_reserve($args){
    if (!empty($args)) extract($args);
    global $db;
    $sql = "select is_use_reserve, outer_id from ecshop.ecs_taobao_goods where taobao_goods_id = '{$taobao_goods_id}' ";
    $res = $db->getRow($sql);
    if ($is_auto_reserve == 0) {
        $status = "不扣减系统默认预留值";
    } else { 
        $status = "扣减系统默认预留值";
    }
    if ($res['is_use_reserve'] == $is_use_reserve) {
        
        $message = $res['outer_id']."已经选择".$status."，请重新选择。";
    } else {
        $sql_u = "update ecshop.ecs_taobao_goods set is_use_reserve = {$is_use_reserve} where taobao_goods_id = '{$taobao_goods_id}'";
        $result = $db->exec($sql_u);
        if ($result) {
            $message = $res['outer_id']. $status . "修改成功。";
        } else {
            $message = $res['outer_id']. $status . "修改失败。";
        }
    }
    return $message;
}
/**
 * 更新商品库存预留数量
 * @param unknown_type $args
 */
function ajax_update_reserve_quantity($args){
    if (!empty($args)) extract($args);
    global $db;
    $sql = "select is_auto_reserve from ecshop.ecs_taobao_goods where taobao_goods_id= '{$taobao_goods_id}' limit 1";
    $is_auto_reserve = $db->getOne($sql);
    if ($is_auto_reserve == 1) {
    	return 3;
    }
    $sql = "update ecshop.ecs_taobao_goods set reserve_quantity = '{$reserve_quantity}' where taobao_goods_id= '{$taobao_goods_id}'";
    $result = $db->exec($sql);
    return $result;
}

function ajax_update_leqee_weight($args) {
	if (!empty($args)) extract($args);
	global $db;
	$tracking_number_list = preg_split('/[\s]+/', $tracking_number);
	$in_tracking_number = db_create_in($tracking_number_list, 'tracking_number');
    $sql = "SELECT shipment_id FROM romeo.shipment WHERE {$in_tracking_number} ";
    $shipment_id_list = $db->getCol($sql);
    if(empty($shipment_id_list)) {
    	return false;
    }
    foreach($shipment_id_list as $shipment_id) {
    	$weight = $weight * 1000;
    	$party_id = $db->getOne("select party_id from romeo.shipment where shipment_id = '{$shipment_id}' limit 1");
    	if(!in_array($party_id,array('65644','65650','65581','65617','65652','65653','65569','65622','65645','65661','65668','65646','65539','65670','65619','65628','65639'))){
    		$sql = "update romeo.shipment set status='SHIPMENT_SHIPPED',last_modified_by_user_login='{$_SESSION['admin_name']}',shipping_leqee_weight='{$weight}' where shipment_id = '{$shipment_id}' limit 1";
    	}else{
    		$sql = "update romeo.shipment set last_modified_by_user_login='{$_SESSION['admin_name']}',shipping_leqee_weight='{$weight}' where shipment_id = '{$shipment_id}' limit 1";
    	}
    	
		$sql2 = "select oi.order_id,order_status,pay_status,shipping_status from ecshop.ecs_order_info oi" .
			" inner join romeo.shipment s on cast(s.primary_order_id as UNSIGNED) = oi.order_id  "  .
			" where s.shipment_id  = '{$shipment_id}' limit 1 ";
		$order_status_str = $db->getRow($sql2); 
		$sql3 = "INSERT INTO ecshop.ecs_order_action (order_id, order_status, shipping_status, pay_status, action_time, action_user, action_note) " .
					" values({$order_status_str['order_id']},{$order_status_str['order_status']},{$order_status_str['shipping_status']},{$order_status_str['pay_status']},now(),'{$_SESSION['admin_name']}','快递包裹{$tracking_number}称重{$weight}') ";
		$db->query($sql3);
    	$result = $db->query($sql);
    }
	return $result;
}
/**
 * 根据运单号、耗材条码检查
 */
function ajax_check_consumable_item($args) {
	$result = array();
	$result['message'] = '';
	$condition ='';
	if (!empty($args)) extract($args);
	if (empty($tracking_number) || empty($barcode)) {
		$result['message'] = "条码或运单号为空";
		return $result;
	}
	$flag = strpos($tracking_number, ",");
    if ($flag !== false) {
	    $tracking_number = explode(",", $tracking_number);;
    } else {
    	$tracking_number= array($tracking_number);
    }
    
	global $db;
	require_once ('function.php');
	
	// 耗材商品在通用商品组织和具体组织中有且仅有1个
	$common_partys = get_common_goods_partys($barcode);
	
	if($common_partys['error']) {
		$result['message'] = "耗材商品只能在一个组织中，目前拥有的组织为：".implode(",",$common_partys['party_name'])."，请联系采购组删除不用的组织下的耗材:".$barcode;
		return $result;
	}
	
	 $tysp_party_id = get_tysp_party_id();
	 
	 //安怡和安满共用耗材商品条件
	 if($common_partys['partyId']){
       $condition .= " or ( g.goods_party_id in (65581,65569) and s.party_id in(65581,65569) ) " ;
	 }
    
	$sql = "
		select distinct s.tracking_number
		from romeo.shipment s
	    left join ecshop.ecs_goods g on (s.party_id = convert(g.goods_party_id using utf8)) OR g.goods_party_id = $tysp_party_id  {$condition}
		left join ecshop.ecs_category c on g.cat_id = c.cat_id
		where c.cat_name like '%耗材%' and g.barcode = '{$barcode}' and s.tracking_number ". db_create_in ($tracking_number)
	    ." group by s.tracking_number"
	;
	
	$track_numbers = $db->getCol($sql);
	//返回可用的快递单号
	if (empty($track_numbers)){
		$result['message'] = "条码匹配的运单为空";
	} else {
	   $result['tracking_numbers'] = $track_numbers;
	}
	return $result;
}

/**
 * 耗材扫描时记录条码和运单号的映射 ljzhou 2013.03.19
 */
function ajax_barcode_tracking_mapping($args) {
	if (!empty($args)) extract($args);
	if (empty($tracking_number) || empty($barcode)) {
		return "param empty";
	}
	$flag = strpos($tracking_number, ",");
    if ($flag !== false) {
	    $tracking_number = explode(",", $tracking_number);;
    } else {
    	$tracking_number= array($tracking_number);
    }
    
    global $db;
    foreach ($tracking_number as $tracking_num) {
    	$sql = "select 1 from ecshop.ecs_barcode_tracking_mapping where tracking_number = '{$tracking_num}' limit 1";
    	$result = $db->getOne($sql);
    	if(empty($result)) {
    		$sql = "insert into ecshop.ecs_barcode_tracking_mapping (tracking_number,barcode,is_pick_up,created_stamp,last_updated_stamp) values ('{$tracking_num}','{$barcode}','N',now(),now())";
    		$db->query($sql);
    	}
    }
    return true;
}

/**
 * 批量完结采购订单  ljzhou 2013.04.11
 */
function ajax_batch_over_c($args) {
	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    if(!$batch_order_sn) {
    	return false;
    }

    global $db;
    $sql = "select batch_order_id from ecshop.ecs_batch_order_info where batch_order_sn = '{$batch_order_sn}' limit 1";
    $batch_order_id = $db->getOne($sql);
    if(!empty($batch_order_id)) {
        $db->start_transaction();        //开始事务
    	
        $sql = "update ecshop.ecs_batch_order_info set is_over_c = 'Y', is_in_storage = 'Y', batch_over_time=now() where batch_order_id = '{$batch_order_id}' limit 1";
    	if(!$db->query($sql)){
            $db->rollback();
            return false;
        }
    	
        $sql = "select order_id from ecshop.ecs_batch_order_mapping where batch_order_id = '{$batch_order_id}' ";
    	$orderIds = $db->getCol($sql);
        $order_ids_str = "'".implode($orderIds, "','")."'";
        $sql = "update ecshop.ecs_batch_order_mapping set is_over_c = 'Y' where order_id in ({$order_ids_str})";
        if(!$db->query($sql)){
            $db->rollback();
            return false;
        }
        
        // 批量更新完结时间
        $sql = "update romeo.purchase_order_info set over_time = now() where over_time ='0000-00-00 00:00:00' and order_id in ({$order_ids_str})";
        if(!$db->query($sql)){
            $db->rollback();
            return false;
        }

        $sql = "update ecshop.ecs_order_info set order_status = 5 where order_id in ({$order_ids_str})";
        if(!$db->query($sql)){
            $db->rollback();
            return false;
        }
        $db->commit();
    } else {
    	return false;
    }
    return true;
}


/**
 * 批量审核采购订单  ytchen 2015.04.21
 */
function ajax_batch_check_pass($args) {
	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    if(!$batch_order_sn) {
    	return false;
    }

    global $db;
    $sql = "select batch_order_id from ecshop.ecs_batch_order_info where batch_order_sn = '{$batch_order_sn}' and check_status='INIT' limit 1";
    $batch_order_id = $db->getOne($sql);
    if(!empty($batch_order_id)) {
        $sql = "update ecshop.ecs_batch_order_info set check_status = 'PASS' where batch_order_id = '{$batch_order_id}' limit 1";
    	if(!$db->query($sql)){
            return false;
        }
    } else {
    	return false;
    }
    return true;
}

/**
 * 批量否决采购订单  ytchen 2015.04.21
 */
function ajax_batch_check_deny($args) {
	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    if(!$batch_order_sn) {
    	return false;
    }

    global $db;
    $sql = "select batch_order_id from ecshop.ecs_batch_order_info where batch_order_sn = '{$batch_order_sn}' and check_status='INIT' limit 1";
    $batch_order_id = $db->getOne($sql);
    if(!empty($batch_order_id)) {
        $sql = "update ecshop.ecs_batch_order_info set check_status = 'DENY' where batch_order_id = '{$batch_order_id}' limit 1";
    	if(!$db->query($sql)){
            return false;
        }
    } else {
    	return false;
    }
    return true;
}

/**
 * 根据运单号获取仓库
 */
function ajax_get_facility_id_by_tracking_number ($args) {
	if (!empty($args)) extract($args);
	$flag = strpos($tracking_number, ",");
    if ($flag !== false) {
	    $tracking_number = explode(",", $tracking_number);;
    } else {
    	$tracking_number= array($tracking_number);
    }
	global $db;
	$sql = "select distinct s.tracking_number
		from romeo.shipment s 
		inner join romeo.order_shipment os on s.shipment_id = os.shipment_id
		inner join ecshop.ecs_order_info o on os.order_id = o.order_id
		left join romeo.party_facility_for_consumable pf on o.facility_id = pf.facility_id and  o.party_id = pf.party_id and pf.is_delete = 0 
		where s.tracking_number ". db_create_in ($tracking_number)
	;
	$orders = $db->getAll($sql);
	
	$result = "";
	foreach($orders as $order){
		$result .= trim($order['tracking_number']).",";
	}
	if (!empty($result)) {
		$result = substr($result, 0, -1);
	}
	return $result;
}


function ajax_check_order_status($args) {
    if (!empty($args)) extract($args);
    global $db;
    $sql = "SELECT oi.order_status FROM ecshop.ecs_order_info oi  
            LEFT JOIN romeo.order_shipment os ON oi.order_id = cast(os.order_id as unsigned)
            LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id 
            WHERE s.tracking_number = '{$tracking_number}' and oi.order_status = 2 
            limit 1";
    $order_status = $db->getOne($sql);
    if(!empty($order_status)) {
        return 2;
    }
    
    $sql = "SELECT oi.order_status FROM ecshop.ecs_order_info oi  
            LEFT JOIN romeo.shipment s ON oi.order_id= s.primary_order_id
            WHERE s.tracking_number = '{$tracking_number}'
            limit 1";
    $order_status = $db->getOne($sql);
    if(empty($order_status)) {
        return -1;
    }
    
    if(!check_refund_status_by_tracking_number($tracking_number)){
        return -2;
    }
    return $order_status;
}

/**
 * 得到订单的发货状态
 */
function ajax_check_order_shipping_status($args) {
    if (!empty($args)) extract($args);
    global $db;
    $sql = "SELECT oi.shipping_status FROM ecshop.ecs_order_info oi  
            LEFT JOIN romeo.order_shipment os ON oi.order_id = cast(os.order_id as unsigned)
            LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id 
            WHERE s.tracking_number = '{$tracking_number}' and oi.shipping_status !=8
            limit 1";
    
    $order_status = $db->getOne($sql);
    if(!empty($order_status)) {
        return false;
    }
    
    return true;
}

/**
 * 检查商品是否维护了条码 ljzhou 2013-09-12
 */
function ajax_check_goods_maintain_barcode($args) {
	if (!empty($args)) extract($args);
	global $db;
	$goods_id = trim($goods_id);
	$style_id = trim($style_id);
	if($style_id != 0) {
	   $sql = "SELECT barcode FROM ecshop.ecs_goods_style where goods_id = '{$goods_id}' and style_id = '{$style_id}' and is_delete=0 limit 1";
	} else {
	   $sql = "SELECT barcode FROM ecshop.ecs_goods where goods_id = '{$goods_id}' limit 1";
	}
    $barcode = $db->getOne($sql);
    Qlog::log('barcode:'.$barcode);
    
    if(empty($barcode)) {
    	return false;
    }

	return true;
}

/**
 * 
 * 退款信息校验 qdi 2013.02.04
 * @param $shipment_id
 * @return -1：订单或运单有退款信息      true：正常
 */
function ajax_check_refund_status_by_shipment_id($args) {
	if (!empty($args)) extract($args);
	if(!check_refund_status_by_shipment_id($shipment_id)){
		return -1;
	}
	return true;
}
/**
 * 
 * 退款信息校验 qdi 2013.02.04
 * @param $order_id
 * @return false：订单或运单有退款信息      true：正常
 */
function ajax_check_order_refund_status_by_order_id($args) {
	if (!empty($args)) extract($args);
	if (!check_refund_status($order_id)) { 
    	return -1; 
    }
    return true;
}
/**
 * 
 * 退款信息校验 qdi 2013.02.04
 * @param $order_sn
 * @return false：订单或运单有退款信息      true：正常
 */
function ajax_check_order_refund_status_by_ordersn($args) {
	if (!empty($args)) extract($args);
	if (!check_refund_status_by_order_sn($order_sn)) { 
    	return -1; 
    }
    return true;
}

/**
 * 
 * 退款信息校验
 * @param $order_sn
 * @return false：订单或运单有退款信息      true：正常
 */
function ajax_check_refund_status($args) {
	if (!empty($args)) extract($args);
	$result = array();
	$result['success'] = true;
	if (!check_refund_status($order_id)) { 
		$result['success'] = false;
		$result['error_info'] = '该订单发起退款申请?\n 1、强制确认请点 确认\n 2否则请点 取消';
    	return $result; 
    }
    return $result;
}

/**
 * 检查可预订量，订单是否有某个商品可预订量不够
 * @param $order_id
 */
function ajax_get_order_goods_atp_info($args) {
	if (!empty($args)) extract($args);
	$result = array();
	$check_atp_info  = get_order_goods_atp_info($order_id);
	$result['success'] = true;
    if(!empty($check_atp_info)) {
    	$result['success'] = false;
    	$result['error_info'] =  $check_atp_info['goods_name'].' 需要：'.$check_atp_info['need_number'].' 个，实际可预订量：'.$check_atp_info['atp'].' 将会预定失败！你确定要确认吗？';
    } 
    return $result;
}

/**
 * 
 * 称重校验 ljzhou 2012.10.23
 * @param $weight $tracking_number
 * @return -1：订单或运单异常   -2：称重结果异常  0：正常
 */
function ajax_check_weighing($args) {
	if (! empty ( $args ))
		extract ( $args );
	global $db;
	require_once ("function.php");
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
	$leqee_weight = $weight * 1000;
	
	//一个运单对应 多个订单
	$orderId_list = get_shipment_orders($tracking_number);
	if (empty ( $orderId_list )) {
		return - 1;
	}
	
	//一个订单对应 多个运单
	$shipment_list = get_order_shipments($tracking_number);
	if (empty ( $shipment_list )) {
		return - 1;
	}
	
	//一个运单对应 多个订单（最大一个订单的耗材<称重耗材<所有订单的耗材和）
	if (count ( $orderId_list ) > 1 && count ( $shipment_list ) == 1) {
		$total_weight = $error_weight = 0;
		$package_weight = $package_weight_total = $package_weight_max = 0;
		foreach ( $orderId_list as $orderId ) {
			$order = getBasicOrderInfo ( $orderId );
			// 目前默认合并订单是同组织的
			if ($error_weight == 0) {
				$error_weight = get_error_weight ( $order ['party_id'] );
			}
			$package_weight = get_package_weight ( $order );
			$package_weight_total += $package_weight;
			if ($package_weight > $package_weight_max) {
				$package_weight_max = $package_weight;
			}
			
			$order_weight = get_order_weight ( $order );
			if ($order_weight == 0) {
				return - 1;
			}
			$total_weight += $order_weight;
			$sql = "SELECT 1 FROM ecshop.ecs_order_weight WHERE order_id = {$orderId} limit 1";
			$order_check = $db->getOne ( $sql );
			if (empty ( $order_check )) {
				$sql = "INSERT INTO ecshop.ecs_order_weight (order_id,order_weight,package_weight,shipment_type,is_weight_error) values
				      ({$orderId},{$order_weight},{$package_weight},'2','N')";
				$db->query ( $sql );
			}
		}
		$total_weight_min = $total_weight + $package_weight_max;
		$total_weight_max = $total_weight + $package_weight_total;
		
		if (($total_weight_min - $leqee_weight) > $error_weight || ($leqee_weight - $total_weight_max) > $error_weight) {
			foreach ( $orderId_list as $orderId ) {
				$sql = "UPDATE ecshop.ecs_order_weight SET is_weight_error = 'Y' WHERE order_id = {$orderId} limit 1";
				$db->query ( $sql );
			}
			
			QLog::log ( "weight_check_log:1个运单对应多个订单称重异常:({$tracking_number})" );
			return - 2;
		}
		return 0;
	} 
	//一个订单对应 多个运单
	else if (count ( $orderId_list ) == 1 && count ( $shipment_list ) > 1) {
		$total_weight = $package_weight = $order_weight = $error_weight = 0;
		$num_shipped = 0;
		foreach ( $shipment_list as $shipment ) {
			//订单重量只需计算一次
			if ($total_weight == 0) {
				$order = getBasicOrderInfo ( $shipment ['primary_order_id'] );
				if ($error_weight == 0) {
					$error_weight = get_error_weight ( $order ['party_id'] );
				}
				$order_weight = get_order_weight ( $order );
				if ($order_weight == 0) {
					return - 1;
				}
				$package_weight = get_package_weight ( $order );
				$total_weight = $order_weight + $package_weight;
			}
			if ($shipment ['SHIPPING_LEQEE_WEIGHT'] > 0) {
				$num_shipped = $num_shipped + 1;
				$leqee_weight = $leqee_weight + $shipment ['SHIPPING_LEQEE_WEIGHT'];
			}
		}
		if ($num_shipped == count ( $shipment_list ) - 1) {
			$sql = "SELECT 1 FROM ecshop.ecs_order_weight WHERE order_id = {$shipment_list[0]['primary_order_id']} limit 1";
			$order_check = $db->getOne ( $sql );
			if (empty ( $order_check )) {
				$sql = "INSERT INTO ecshop.ecs_order_weight (order_id,order_weight,package_weight,shipment_type,is_weight_error) values
				           ({$shipment_list[0]['primary_order_id']},{$order_weight},{$package_weight},'0','N')";
				$db->query ( $sql );
			}
		}
		//称最后一个包裹才校验
		if ($num_shipped == count ( $shipment_list ) - 1 && abs ( $leqee_weight - $total_weight ) > $error_weight) {
			$sql = "UPDATE ecshop.ecs_order_weight SET is_weight_error = 'Y' WHERE order_id = {$shipment_list[0]['primary_order_id']} limit 1";
			$db->query ( $sql );
			QLog::log ( "weight_check_log:1个订单对应 多个运单异常:({$tracking_number})" );
			return - 2;
		}
		return 0;
	} 
	//一个运单对应一个订单
	else if (count ( $orderId_list ) == 1 && count ( $shipment_list ) == 1) {
		$order = getBasicOrderInfo ( $orderId_list [0] );
		$error_weight = get_error_weight ( $order ['party_id'] );
		$package_weight = get_package_weight ( $order );
		$order_weight = get_order_weight ( $order );
		if ($order_weight == 0) {
			return - 1;
		}
		$total_weight = $package_weight + $order_weight;
		$sql = "SELECT 1 FROM ecshop.ecs_order_weight WHERE order_id = {$orderId_list [0]} limit 1";
		$order_check = $db->getOne ( $sql );
		if (empty ( $order_check )) {
			$sql = "INSERT INTO ecshop.ecs_order_weight (order_id,order_weight,package_weight,shipment_type,is_weight_error) values
				      ({$orderId_list[0]},{$order_weight},{$package_weight},'1','N')";
			$db->query ( $sql );
		}
		
		if (abs ( $leqee_weight - $total_weight ) > $error_weight) {
			$sql = "UPDATE ecshop.ecs_order_weight SET is_weight_error = 'Y' WHERE order_id = {$orderId_list [0]} limit 1";
			$db->query ( $sql );
			QLog::log ( "weight_check_log:1个订单对应一个运单异常:({$tracking_number})" );
			return - 2;
		}
		return 0;
	} 
	//多个订单对应多个运单
	else if (count ( $orderId_list ) > 1 && count ( $shipment_list ) > 1) {
		$total_weight = $error_weight = 0;
		$package_weight = $package_weight_total=0;
		$package_weight_min = 999999;
		foreach ( $orderId_list as $orderId ) {
			$order = getBasicOrderInfo ( $orderId  );
			// 目前默认合并订单是同组织的
			if ($error_weight == 0) {
				$error_weight = get_error_weight ( $order ['party_id'] );
			}
			$package_weight = get_package_weight ( $order );
			$package_weight_total += $package_weight;
			if ($package_weight < $package_weight_min) {
				$package_weight_min = $package_weight;
			}
			
			$order_weight = get_order_weight ( $order );
			if ($order_weight == 0) {
				return - 1;
			}
			$total_weight += $order_weight;
			$sql = "SELECT 1 FROM ecshop.ecs_order_weight WHERE order_id = {$orderId} limit 1";
			$order_check = $db->getOne ( $sql );
			if (empty ( $order_check )) {
				$sql = "INSERT INTO ecshop.ecs_order_weight (order_id,order_weight,package_weight,shipment_type,is_weight_error) values
				      ({$orderId},{$order_weight},{$package_weight},'3','N')";
				$db->query ( $sql );
			}
		}
		$total_weight_min = $total_weight + $package_weight_min*count($shipment_list);
		$total_weight_max = $total_weight + $package_weight_total;
		$num_shipped = 0;
		foreach ( $shipment_list as $shipment ) {
			if ($shipment ['SHIPPING_LEQEE_WEIGHT'] > 0) {
				$num_shipped = $num_shipped + 1;
				$leqee_weight = $leqee_weight + $shipment ['SHIPPING_LEQEE_WEIGHT'];
			}
		}

		//称最后一个包裹才校验
		if ($num_shipped == count ( $shipment_list ) - 1 && (($total_weight_min - $leqee_weight) > $error_weight || ($leqee_weight - $total_weight_max) > $error_weight)) {
			foreach ( $orderId_list as $orderId ) {
				$sql = "UPDATE ecshop.ecs_order_weight SET is_weight_error = 'Y' WHERE order_id = {$orderId} limit 1";
				$db->query ( $sql );
			}
			QLog::log ( "weight_check_log:多个订单对应 多个运单异常:({$tracking_number})" );
			return - 2;
		}
		
		return 0;
	}
	return 0;
}


/**
 * 根据shipping_id得到carrier_id ljzhou 2013.04.12
 */

function ajax_get_carrier_id($args) {
	if (!empty($args)) extract($args);
    $shipping_id = trim($shipping_id);
    if(!$shipping_id) {
    	return false;
    }
    global $db;
    $sql = "select default_carrier_id from ecshop.ecs_shipping where shipping_id = '{$shipping_id}' limit 1";
    $carrier_id = $db->getOne($sql);
    if(empty($carrier_id)) {
    	return false;
    }
    return $carrier_id;
}


/**
 * 检查库位 ljzhou 2013.04.15
 */

function ajax_check_facility_location($args) {
	if (!empty($args)) extract($args);
    $facility_id = trim($facility_id);
    $party_id = trim($party_id);
    $message = array();
    if(!$facility_id || !$party_id) {
    	$message['false'] .= "仓库或组织获取失败！";
    	return $message;
    }
    global $db;
    $sql = "select 
                 count(distinct(og2.goods_party_id)) as party_num,pf2.location_seq_id 
            from 
            ecshop.ecs_goods og 
            left join romeo.product_facility_location pf ON og.goods_id = pf.goods_id
            left join romeo.product_facility_location pf2 ON pf.location_seq_id = pf2.location_seq_id
            left join ecshop.ecs_goods og2 ON pf2.goods_id = og2.goods_id
            where og.goods_party_id = '{$party_id}' and pf.facility_id = '{$facility_id}'
            and og2.goods_party_id is not null and og2.goods_party_id <>''
            group by pf2.location_seq_id
            having party_num > 1
            limit 1
            ";
    $party_seq_id = $db->getRow($sql);
    if(!empty($party_seq_id)) {
    	$message['error'] .= "该库位有其他组织的商品，请检查，1个库位只有1个组织的商品才能被删除:".$party_seq_id['location_seq_id'];
    } else {
    	$message['success'] = "库位没问题";
    }
    return $message;
}


/**
 * 批量删除某个组织，某个仓库的库位 ljzhou 2013.04.15
 */

function ajax_batch_delete_facility_location($args) {	
	if (!empty($args)) extract($args);
    $facility_id = trim($facility_id);
    $party_id = trim($party_id);
    $message = array();
    if(!$facility_id || !$party_id) {
    	$message['false'] .= "批量删除：仓库或组织获取失败！";
    	return $message;
    }
    global $db;
    $sql = "select 
                  pf.location_seq_id 
            from ecshop.ecs_goods og 
            left join romeo.product_facility_location pf ON og.goods_id = pf.goods_id
            where 
            og.goods_party_id = '{$party_id}' and pf.facility_id = '{$facility_id}'
            and pf.location_seq_id is not null
            group by pf.location_seq_id
            ";
    $location_seq_ids = $db->getCol($sql);
    if(empty($location_seq_ids)) {
    	$message['error'] .= "该组织对应的仓库找不到商品！";
    	return $message;
    }
    
    $sql = "delete from romeo.product_facility_location where location_seq_id ". db_create_in($location_seq_ids);
    $db->query($sql);
    $sql = "delete from romeo.facility_location where location_seq_id ". db_create_in($location_seq_ids);
    $db->query($sql);

    $message['success'] = "批量删除库位成功！";
    return $message;
}

/**
 * 判断目的库位是否存在  ljzhou 2013.04.16
 */

function ajax_check_destination_location($args) {	
	if (!empty($args)) extract($args);
    $location_seq_id = trim($location_seq_id);
    
    $message = array();
    if(!$location_seq_id) {
    	$message['error'] .= "目的库位号为空！";
    	return $message;
    }
    global $db;
    $sql = "select 
                  1
            from romeo.facility_location 
            where 
            location_seq_id='{$location_seq_id}'
            limit 1
            ";
    $res = $db->getOne($sql);
    if(empty($res)) {
    	$message['error'] .= "该目的库位不存在，请先添加库位！";
    	return $message;
    }

    $message['success'] .= "该目的库位存在！";
    return $message;
}


/**
 * 根据条码得到库位，条码是否存在等信息  ljzhou 2013.04.16
 */

function ajax_get_location_seq_id($args) {	
	if (!empty($args)) extract($args);
    $barcode = trim($barcode);
    $party_id = trim($party_id);
    $message = array();
    if(!$barcode || !$party_id) {
    	$message['error'] .= "条码或组织为空！";
    	return $message;
    }
    global $db;
    $sql = "select
                 g.goods_id,ifnull(gs.style_id,0) as style_id,
                 CONCAT(g.goods_name,' ',IF (gs.goods_color = '' or gs.goods_color is null , ifnull(s.color, ''), ifnull(gs.goods_color, ''))) as goods_name
            from ecshop.ecs_goods g 
            left join ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
            left join ecshop.ecs_style s on gs.style_id = s.style_id
            where 
                 g.goods_party_id = '{$party_id}' and (g.barcode = '{$barcode}' OR gs.barcode = '{$barcode}')
            limit 1
            ";
    $item = $db->getRow($sql);
    if(empty($item)) {
    	$message['error'] .= "该条码:".$barcode." 在系统找不到对应的商品，请联系店长看是否维护了！";
    	return $message;
    }
    
    $sql = "select 
                  pf.location_seq_id 
            from romeo.product_facility_location pf
            where 
            pf.goods_id='{$item['goods_id']}' and pf.style_id='{$item['style_id']}'
            ";
    
    $location_seq_ids = $db->getAll($sql);
    if(count($location_seq_ids) > 1) {
    	$message['error'] .= "该条码:".$barcode." 商品:".$item['goods_name']." 在系统原始库位多于1个库位，不能移库！";
    	foreach ($location_seq_ids as $location_seq_id) {
    		$message['error'] .= " \n".$location_seq_id['location_seq_id'];
    	}
    	return $message;
    }

    $message['location_seq_id'] = $location_seq_ids[0]['location_seq_id'];
    return $message;
}


/**
 * 批量修改库位  ljzhou 2013.04.16
 */

function ajax_batch_change_location($args) {	
	require_once (ROOT_PATH . 'includes/debug/lib_log.php');
	
	if (! empty ( $args )) extract ( $args );
	$facility_id = trim($facility_id);
	$party_id = trim($party_id);
	$batch_barcode_nos = trim ( $batch_barcode_nos );
	$message = array ();
	if (!$batch_barcode_nos || !$party_id || !$facility_id) {
		$message ['error'] .= "条码或组织或仓库为空！";
		return $message;
	}
	$barcode_list = preg_split ( '/[\s]+/', $batch_barcode_nos );
	foreach ( $barcode_list as $key => $barcode_no ) {
		
		$barcode_no = trim ( $barcode_no );
		if (strrpos ( $barcode_no, ';' ) !== false) {
            list($barcode, $to_location) = preg_split('/;/', $barcode_no);
            $barcode = trim($barcode);
            $to_location = trim($to_location);
            require_once (ROOT_PATH . 'admin/function.php');
            $res = change_facility_location($party_id,$barcode,$to_location,$facility_id);
            if(!$res) {
            	$message ['error'] .= $res['error'];
            }
        } else {
        	$message ['error'] .= " 该条码库位移库失败：".$barcode_no;
        }
     }
    
    return $message;
}


/**
 * 检查运单号规则，配货出库、分销发货、办公件、订单-发货单编辑、待发货 等检测功能统一在此调用  ljzhou 2013.05.13
 * @param String $carrier_id 承运商id
 * @param string $tracking_number 面单号
 */

function ajax_check_tracking_number($args) {	

	if (! empty ( $args )) extract ( $args );
	$carrier_id = trim($carrier_id);
	$tracking_number = trim($tracking_number);
	
	// 内部员工自提不用检测
	if($carrier_id == 0) {
		return true;
	}
	
	$message = array ();

	if (!$carrier_id || !$tracking_number) {
		$message ['error'] .= "承运商或面单号ajax传值为空！";
		return $message;
	}

    $flag = true;
    switch($carrier_id) {
        case '3'://圆通
          	if (!preg_match("/^(0|1|2|3|4|5|6|7|8|9|S|E|D|F|G|V|W|e|d|f|g|s|v|w)[0-9]{9}([0-9]{2})?([0-9]{6})?$/", $tracking_number)) //Yuantong has now 12 numbers 
            {
                $flag = false;
            }
            break;
        case '5'://宅急送
            if (!preg_match("/^[0-9]{10}$/", $tracking_number)) {
                $flag = false;
            }
            break;
        case '15':// 宅急送COD
        	if (!preg_match("/^[0-9]{10}$/", $tracking_number)) {
                $flag = false;
            }
            break;
        case '44'://顺丰快递（陆运）
        case '10'://顺丰快递
        case '17'://顺丰快递COD
        	if (!preg_match("/^\d{12}$/", $tracking_number)) {
                $flag = false;
            }
            break;
        case '49'://天天快递
           if(!preg_match("/^[0-9]{12}$/",$tracking_number)){
           	 $flag = false;
           }
           break;
        case '9'://邮政EMS13位ER，EQ，ET，EF，开头
    		if (!preg_match("/^[A-Z]{2}[0-9]{9}[A-Z]{2}$|^[0-9]{13}$/", $tracking_number)) {
    			$flag = false;
            }
        	break;
        case '14'://邮政COD
            if (!preg_match("/^[A-Z]{2}[0-9]{9}[A-Z]{2}$|^[0-9]{13}$/", $tracking_number)) {
                $flag = false;
            }
            break;
        case '29':   // 韵达快运13位12,16开头
            if (!preg_match("/^[\s]*[0-9]{13}[\s]*$/", $tracking_number)) {
            	$flag = false;
            }
            break;
        case '28':   // 汇通快运
            if (!preg_match("/^(A|B|C|D|E|H|0)(D|X|[0-9])(A|[0-9])[0-9]{10}$|^(21|22|23|24|25|26|27|28|29|30|31|32|33|34|35|36|37|38|39)[0-9]{10}$|^(50|70)[0-9]{12}$/", $tracking_number)) {
                $flag = false;
            }
        break;
       case '20'://申通快运12位268,368,468,568,668开头
        	if (!preg_match("/^(229|268|888|588|688|368|468|568|668|768|868|968|220|227)[0-9]{9}$|^(229|268|888|588|688|368|468|568|668|768|868|968|220)[0-9]{10}$|^(STO)[0-9]{10}$/", $tracking_number)) {
        		$flag = false;
        	}
        break;
       case '36'://E邮宝快递13位
    	   if (!preg_match("/^[0-9]{13}$/", $tracking_number)) {
    		   $flag = false;
    	   }
    	break;
	   case '40'://上饶运输公司
			if (!preg_match("/^(JBLGX|GALLOGX)[0-9]{10}$/", $tracking_number)) {
				$flag = false;
			}
		break;
	   case '41'://中通快递 
        //Updated by Sinri 140704 +689
            if (!preg_match("/^((618|680|778|688|689|618|828|988|118|888|571|518|010|628|205|880|717|718|719|728|738|761|762|763|701|757|358|359|530)[0-9]{9})$|^((36|37|40)[0-9]{10})$|^((1)[0-9]{12})$|^((2008|2010|8050|7518)[0-9]{8})$/", $tracking_number)) {
		    	$flag = false;
		    }
        break; 
	   case '43'://EMS经济型
	   		if (!preg_match("/^(50|51)[0-9]{11}$/", $tracking_number)) {
	   			$flag = false;
	   		}
	   break;
	   case '45'://邮政小包
	   		if (!preg_match("/^[GA]{2}[0-9]{9}([2-5][0-9]|[1][1-9]|[6][0-5])$|^[99]{2}[0-9]{11}$|^[96]{2}[0-9]{11}$/", $tracking_number)) {
	   			$flag = false;
	   		}
	   break;
	   case '46'://德邦物流
	   		if (!preg_match("/^[0-9]{8,10}$/", $tracking_number)) {
	   			$flag = false;
	   		}
	   break;
                 
    }
    
    if (!$flag) {
        return false;
    }
    
    return true;
}

/**
 * 是否启用系统计算库存预定
 * 是否使用系统默认值，京东，  tjgu  2013-09-05
 * @param unknown_type $args
 */
function ajax_update_reserve_jd($args){
    if (!empty($args)) extract($args);
    global $db;
    $sql = "select is_auto_reserve, outer_id from ecshop.ecs_jd_goods where jd_goods_id = '{$jd_goods_id}' ";
    $res = $db->getRow($sql);
    if ($is_auto_reserve == 0) {
        $status = "不使用系统默认预留值";
    } else { 
        $status = "使用系统默认预留值";
    }
    if ($res['is_auto_reserve'] == $is_auto_reserve) {
        $message = $res['outer_id']."已经选择".$status."，请重新选择。";
    } else {
        $sql_u = "update ecshop.ecs_jd_goods set is_auto_reserve = {$is_auto_reserve} where jd_goods_id = '{$jd_goods_id}'";
        $result = $db->exec($sql_u);
        if ($result) {
            $message = $res['outer_id']. $status . "修改成功。";
        } else {
            $message = $res['outer_id']. $status . "修改失败。";
        }
    }
    return $message;
}

/**
 * 更新商品库存预留数量
 * 更新京东商品库存预存数量  tjgu  2013-09-05
 * @param unknown_type $args
 */
function ajax_update_reserve_quantity_jd($args){
    if (!empty($args)) extract($args);
    global $db;
    $sql = "select is_auto_reserve from ecshop.ecs_jd_goods where jd_goods_id= '{$jd_goods_id}' limit 1";
    $is_auto_reserve = $db->getOne($sql);
    if ($is_auto_reserve == 1) {
        return 3;
    }
    $sql = "update ecshop.ecs_jd_goods set reserve_quantity = '{$reserve_quantity}' where jd_goods_id= '{$jd_goods_id}'";
    $result = $db->exec($sql);
    return $result;
}

/**
 * 是否启用扣减预警值
 * 是否启用扣减预警值 京东  tjgu  2013-09-05
 * @param unknown_type $args
 */
function ajax_update_use_reserve_jd($args){
    if (!empty($args)) extract($args);
    global $db;
    $sql = "select is_use_reserve, outer_id from ecshop.ecs_jd_goods where jd_goods_id = '{$jd_goods_id}' ";
    $res = $db->getRow($sql);
    if ($is_auto_reserve == 0) {
        $status = "不扣减系统默认预留值";
    } else { 
        $status = "扣减系统默认预留值";
    }
    if ($res['is_use_reserve'] == $is_use_reserve) {
        
        $message = $res['outer_id']."已经选择".$status."，请重新选择。";
    } else {
        $sql_u = "update ecshop.ecs_jd_goods set is_use_reserve = {$is_use_reserve} where jd_goods_id = '{$jd_goods_id}'";
        $result = $db->exec($sql_u);
        if ($result) {
            $message = $res['outer_id']. $status . "修改成功。";
        } else {
            $message = $res['outer_id']. $status . "修改失败。";
        }
    }
    return $message;
}

/**
 * 批拣号条码  ljzhou 2013.07.15
 */

function ajax_location_batch_pick_scan($args) {	
	if (!empty($args)) extract($args);
    $barcode = trim($barcode);
    
    $result = array();

    $result['message'] = "扫描成功:".$barcode;
    return $result;
}

/**
 * 验证起始容器条码  ljzhou 2013.07.15
 */

function ajax_from_location_scan($args) {	
	if (!empty($args)) extract($args);
    $barcode = trim($barcode);
    
    $result = array();

    $result['error'] = "扫描错误:".$barcode;
    $result['success'] = "扫描成功:".$barcode;
    return $result;
}

/**
 * 验证结束容器条码  ljzhou 2013.07.15
 */
function ajax_to_location_scan($args) {	
	if (!empty($args)) extract($args);
    $barcode = trim($barcode);
    
    $result = array();

    $result['error'] = "扫描错误:".$barcode;
    $result['success'] = "扫描成功:".$barcode;
    return $result;
}

/**
 * 验证商品条码  ljzhou 2013.07.15
 */
function ajax_goods_barcode_scan($args) {	
	if (!empty($args)) extract($args);
    $barcode = trim($barcode);
    
    $result = array();
 	$party_id = $_SESSION['party_id'];
 	require_once ("function.php");
 	
 	// 不同组织的条码可能相同，所以要切换到具体组织下操作
 	if(!check_leaf_party($party_id)) {
 		$result['message'] = "请切换到具体组织再操作！";
 		return $result;
 	}
    $result['message'] = "扫描成功:".$barcode;
    return $result;
}

/**
 * 验证商品条码是否需要维护有效期  ljzhou 2013.07.15
 */
function ajax_check_maintain_warranty($args) {	
	if (!empty($args)) extract($args);
    $barcode = trim($barcode);
    $party_id = trim($party_id);
    require_once ("function.php");
    $result = check_maintain_warranty($barcode,$party_id);

    return $result;
}

/**
 * 收货入库并且容器转换  
 */
function ajax_purchase_accept_and_location_transaction($args) {	
	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $goods_number = trim($goods_number);
    $serial_number =trim($serial_number);
    $validity = trim($validity);
    $result = array();
    require_once ("function.php");
    $res = purchase_accept_and_location_transaction($batch_order_sn,$location_barcode,$goods_barcode,$serial_number,$goods_number,$validity);

    if($res['success'] == true){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
}

/**
 * 收货入库并且容器转换  
 */
function ajax_purchase_accept_and_location_transaction_v2($args) {	
	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $goods_number = trim($goods_number);
    $serial_number =trim($serial_number);
    $validity = trim($validity);
    $result = array();
    require_once ("function.php");
    $res = purchase_accept_and_location_transaction_v2($batch_order_sn,$location_barcode,$goods_barcode,$serial_number,$goods_number,$validity);

    if($res['success'] == true){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
}

/**
 * 批拣出库并且容器转换  ljzhou 2013.08.15
 */
function ajax_batch_pick_and_location_transaction($args) {	
	if (!empty($args)) extract($args);

    $batch_pick_sn = trim($batch_pick_sn);
    $to_location_barcode = trim($to_location_barcode);
    $goods_barcode = trim($goods_barcode);
    $goods_number = trim($goods_number);
    $result = array();
    $serialNo = '';
    $$shipmentId = '';

		 
    require_once ("function.php");
    $res = batch_pick_and_location_transaction($batch_pick_sn,$to_location_barcode,$goods_barcode,$goods_number,$serialNo,$shipmentId);
    
    if($res['success']) {
    	$result['res'] = $res['res'];
    	$result['success'] = true;
    } else {
    	$result['success'] = false;
    	$result['error'] = $res['res'];
    }
    return $result;
}

/**
 * 上架/移库 容器转换  ljzhou 2013.08.15
 */
function ajax_common_location_transaction($args) {	
	if (!empty($args)) extract($args);
    $from_location_barcode = trim($from_location_barcode);
    $to_location_barcode = trim($to_location_barcode);
    $goods_barcode = trim($goods_barcode);
    $serial_number = trim($serial_number);
    $goods_number = trim($goods_number);
    Qlog::log('from_location_barcode:'.$from_location_barcode.' to_location_barcode:'.$to_location_barcode.' goods_barcode:'.$goods_barcode.' goods_number:'.$goods_number.' serial_number:'.$serial_number);
    $result = array();
    require_once ("function.php");
    $res = common_location_transaction($from_location_barcode,$to_location_barcode,$goods_barcode,$serial_number,$goods_number);
    
    if($res['success']) {
    	$result['res'] = $res['res'];
    	$result['success'] = true;
    } else {
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
}


/**
 * 根据批次订单号得到组织id  ljzhou 2013.08.14
 */
function ajax_get_party_by_batch_order_sn($args) {	
	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $result = array();
    require_once ("function.php");
    $res= get_party_by_batch_order_sn($batch_order_sn);
    if($res['success']) {
    	$result['res'] = $res['res'];
    	$result['success'] = true;
    } else {
    	$result['success'] = false;
    }

    return $result;
}

/**
 * 码托 得到条码数组  
 */
 function ajax_get_tray_barcodes($args) {	
 	if (!empty($args)) extract($args);
    $number = trim($number);
    $result = array();
    require_once ("function.php");
    $res = get_tray_barcodes($number);
    if($res['success']){
    	$result['success'] = $res['success'];
    	$result['res'] = $res['res'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }

 /**
 * 得到上架容器的条码数组  zxcheng 2013.08.05
 */
 function ajax_get_grouding_location_barcodes($args) {  
    if (!empty($args)) extract($args);
    $number = trim($number);
    $result = array();
    require_once ("function.php");
    $res = get_grouding_location_barcodes($number);
    if($res['success']){
        $result['success'] = $res['success'];
        $result['res'] = $res['res'];
    }else{
        $result['success'] = false;
        $result['error'] = $res['error'];
    }
    return $result;
 }

 /**
 * 检测批次订单号的合法性  zxcheng 2013.08.05
 */
 function ajax_check_batch_order_sn($args) {	
 	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    if(!isset($out_ship)){
    	$out_ship=0;
    }
    $result = array();
    if(empty($batch_order_sn)){
    	$result['error'] = "采购批次不存在！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_batch_order_sn($batch_order_sn,$out_ship);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
 * 检测通用上架时订单号的合法性（-t,-h,-gh） ljzhou 2013.10.10
 */
 function ajax_check_common_grouding_order_sn($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    $result = array();
    if(empty($order_sn)){
    	$result['error'] = "订单未获取成功！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_common_grouding_order_sn($order_sn);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
/**
 * 检测通用下架时订单号的合法性（-gt,-gh） ljzhou 2013.10.10
 */
 function ajax_check_common_undercarriage_order_sn($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    $result = array();
    if(empty($order_sn)){
    	$result['error'] = "订单未获取成功！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_common_undercarriage_order_sn($order_sn);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 
 /**
 * 得到批次订单的基本信息  ljzhou 2013-09-06
 */
 function ajax_get_batch_order_sn_info($args) {	
 	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $result = array();
    if(empty($batch_order_sn)){
    	$result['error'] = "得到批次订单的基本信息时批次订单不存在！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = get_batch_order_sn_info($batch_order_sn);
    if($res['success']){
    	$result['success'] = true;
    	$result['facility_name'] = $res['facility_name'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 

 /**
 * 检测收货时商品数量的合法性 
 */
 function ajax_check_receive_goods_number($args) {	
 	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $goods_barcode = trim($goods_barcode);
    $input_number = trim($input_number);
    $result = array();
    if(empty($batch_order_sn)){
    	$result['error'] = "批次订单不存在！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码不存在！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_receive_goods_number($batch_order_sn,$goods_barcode,$input_number);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 /**
 * 检测收货时商品条码的合法性  zxcheng 2013.08.07
 */
 function ajax_check_receive_goods_barcode($args) {	
 	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $goods_barcode = trim($goods_barcode);
    $result = array();
    if(empty($batch_order_sn)){
    	$result['error'] = "批次订单不存在！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码不存在！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = get_receive_goods_info($batch_order_sn,$goods_barcode);
    $result['goods_name'] = $res['goods_name'];
    if($res['success']){
    	$result['success'] = true;
    	
    	require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
		$goods_item_type = getInventoryItemType ($res['goods_id']);
		//是否非串号控制
		if($goods_item_type == 'SERIALIZED'){
			$result['is_serial'] = true; 
		} else {
			$result['is_serial'] = false; 
		}

    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
	Qlog::log('ajax_check_receive_goods_barcode is_serial:'.$result['is_serial'].' success:'.$result['success'].' error:'.$result['error']);
    
    return $result;
 }
 /**
 * 检测收货时商品串号是否存在  cywang 2013.09.13
 */
 function ajax_check_receive_serial_number($args) { 
    if (!empty($args)) extract($args);
    $serial_number = trim($serial_number);
    $result = array();
    if(empty($serial_number)){
        $result['error'] = "商品串号为空！";
        $result['success'] = false;
        return $result;
    }
    require_once ("function.php");
    return check_serial_number_exist($serial_number);
 }
 /**
 * 检测批次订单是否全部入库完成  
 */
 function ajax_check_receive_all_in($args) {	
 	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $result = array();
    if(empty($batch_order_sn)){
    	$result['error'] = "批次订单不存在！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_receive_all_in($batch_order_sn);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    	$result['res'] = $res['res'];
    }
    return $result;
 }
 /**
 * 检测上架容器的任务  zxcheng 2013.08.07
 */
 function ajax_check_grouding_location_task($args) {	
 	if (!empty($args)) extract($args);
    $grouding_location_barcode = trim($grouding_location_barcode);
    $result = array();
    if(empty($grouding_location_barcode)){
    	$result['success'] = false;
    	$result['error'] = "上架容器条码为空！";
    	return $result;
    }
    require_once ("function.php");
    $res = check_grouding_location_task($grouding_location_barcode);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['all_grouding'] = $res['all_grouding'];
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
 *  检测通用上架时候商品条码是否在订单内  ljzhou 2013.10.10
 */
 function ajax_check_common_grouding_goods_barcode($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    $goods_barcode = trim($goods_barcode);
    $result = array();
    if(empty($order_sn)){
    	$result['error'] = "check_common_grouding_goods_barcode订单为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "check_common_grouding_goods_barcode商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_common_grouding_goods_barcode($order_sn,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
 *  检测通用下架时候商品条码是否在订单内  ljzhou 2013.10.10
 */
 function ajax_check_common_undercarriage_goods_barcode($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    $goods_barcode = trim($goods_barcode);
    $result = array();
    if(empty($order_sn)){
    	$result['error'] = "check_common_undercarriage_goods_barcode订单为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "check_common_undercarriage_goods_barcode商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_common_undercarriage_goods_barcode($order_sn,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 
 /**
 *  检测上架时候商品条码是否在容器内  zxcheng 2013.08.07
 */
 function ajax_check_grouding_goods_barcode($args) {	
 	if (!empty($args)) extract($args);
    $grouding_location_barcode = trim($grouding_location_barcode);
    $goods_barcode = trim($goods_barcode);
    $result = array();
    if(empty($grouding_location_barcode)){
    	$result['error'] = "check_grouding_goods_barcode上架容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "check_grouding_goods_barcode商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_location_goods_barcode($grouding_location_barcode,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    	$result['goods_number'] = $res['goods_number'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 /**
 *  检测上架时候商品数量是否合法（不超过上架容器内未上架的数量）  zxcheng 2013.08.07
 */
 function ajax_check_grouding_goods_number($args) {	
 	if (!empty($args)) extract($args);
    $grouding_location_barcode = trim($grouding_location_barcode);
    $goods_barcode = trim($goods_barcode);
    $input_number = trim($input_number);
    $result = array();
    if(empty($grouding_location_barcode)){
    	$result['error'] = "上架容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_grouding_goods_number($grouding_location_barcode,$goods_barcode,$input_number);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 /**
 * 检测目的容器是否存在  zxcheng 2013.08.08
 */
 function ajax_check_to_location_barcode($args) {	
 	if (!empty($args)) extract($args);
    $location_barcode = trim($location_barcode);
    $location_type = trim($location_type);
    $result = array();
    if(empty($location_barcode)){
    	$result['error'] = "目的容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_to_location_barcode($location_barcode,$location_type);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 /**
 * 检测目的容器的存在性,目的容器与起始容器的组织是否一致  zxcheng 2013.12.24
 */
 function ajax_check_to_location_barcode_party($args) {	
 	if (!empty($args)) extract($args);
    $from_location_barcode = trim($from_location_barcode);
    $to_location_barcode = trim($to_location_barcode);
    $location_type = trim($location_type);
    $result = array();
    if(empty($to_location_barcode)){
    	$result['error'] = "目的容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_to_location_barcode_party($from_location_barcode,$to_location_barcode,$location_type);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 /**
 * 检测上架容器合法性，包括订单的组织和容器的组织一致
 */
 function ajax_check_grouding_location_barcode_party($args) {	
 	if (!empty($args)) extract($args);
 	$batch_order_sn = trim($batch_order_sn);
    $location_barcode = trim($location_barcode);
    $location_type = trim($location_type);
    $result = array();
    if(empty($batch_order_sn) || empty($location_barcode) || empty($location_type)){
    	$result['error'] = "目的容器条码或采购批次号或容器类型为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_grouding_location_barcode_party($batch_order_sn,$location_barcode,$location_type);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 
 /**
 *  得到批捡的路线  ljzhou 2013.08.09
 */
 function ajax_get_batch_pick_path($args) {	
 	if (!empty($args)) extract($args);
    $batch_pick_sn = trim($batch_pick_sn);
    $result = array();
    if(empty($batch_pick_sn)){
    	$result['error'] = "批拣号为空！";
    	$result['success'] = false;
    	return $result;
    }

    require_once ("function.php");
    $res = get_batch_pick_path($batch_pick_sn);
    if($res['success']){
    	$result['success'] = true;
    	$result['res'] = $res['res'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 /**
 * 一个库位的1个sku只有一种生产日期检测  zxcheng 2013.08.08
 */
 function ajax_check_goods_barcode_location_validity($args) {	
 	if (!empty($args)) extract($args);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $validity = trim($validity);
    $result = array();
    if(empty($location_barcode)){
    	$result['error'] = "目的容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_goods_barcode_location_validity($location_barcode,$goods_barcode,$validity);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
/**
 * 得到库位上某商品的生产日期  ljzhou 2013-09-14
 */
 function ajax_get_location_barcode_validity($args) {	
 	if (!empty($args)) extract($args);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $result = array();
    if(empty($location_barcode)){
    	$result['error'] = "目的容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = get_location_barcode_validity($location_barcode,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    	$result['validity'] = $res['validity'];
    }else{
    	$result['success'] = false;
    }
    return $result;
 }
 
  
/**
 * 通用上架（-t,-gh）  ljzhou 2013-10-12
 */
 function ajax_common_grouding_and_location_transaction($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $serial_number = trim($serial_number);
    $input_number = trim($input_number);
    $validity = trim($validity);
    Qlog::log('$order_sn:'.$order_sn.' $location_barcode:'.$location_barcode.' $goods_barcode:'.$goods_barcode.' $serial_number:'.$serial_number.' $input_number:'.$input_number.' $validity:'.$validity);
    $result = array();
    if(empty($location_barcode)){
    	$result['error'] = "目的容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($order_sn)){
    	$result['error'] = "订单号为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($input_number)){
    	$result['error'] = "数量为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($validity)){
    	$result['error'] = "生产日期为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res =  create_accept_location($order_sn,$location_barcode,$goods_barcode,$serial_number,$input_number,$validity, $validity_type='start_validity');
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    }
    return $result;
 }
 
 /**
 * 通用下架（-gt,-gh）  ljzhou 2013-10-12
 */
 function ajax_common_undercarriage_and_location_transaction($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $serial_number = trim($serial_number);
    $input_number = trim($input_number);
    Qlog::log('$order_sn:'.$order_sn.' $location_barcode:'.$location_barcode.' $goods_barcode:'.$goods_barcode.' $serial_number:'.$serial_number.' $input_number:'.$input_number);
    $result = array();
    if(empty($location_barcode)){
    	$result['error'] = "目的容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($order_sn)){
    	$result['error'] = "订单号为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($input_number)){
    	$result['error'] = "数量为空！";
    	$result['success'] = false;
    	return $result;
    }

    require_once ("function.php");
    $res =   create_deliver_location($order_sn,$location_barcode,$goods_barcode,$input_number,$serial_number);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 /**
 *  检测批捡号 zxcheng 2013.08.12
 */
 function ajax_check_batch_pick_barcode($args) {	
 	if (!empty($args)) extract($args);
    $batch_pick_sn = trim($batch_pick_sn);
    $result = array();
    if(empty($batch_pick_sn)){
    	$result['error'] = "批拣号为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_batch_pick_barcode($batch_pick_sn);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 /**
 *  简单盘点  ljzhou 2013-10-12
 */
 function ajax_inventory_stock_count($args) {	
 	if (!empty($args)) extract($args);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $serial_number = trim($serial_number);
    $goods_number = trim($goods_number);
    
    $result = array();
    if(empty($location_barcode)){
    	$result['error'] = "库位号为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_number)){
    	$result['error'] = "数量为空！";
    	$result['success'] = false;
    	return $result;
    }

    require_once ("function.php");
    $res = inventory_stock_count($location_barcode,$goods_barcode,$serial_number,$goods_number);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 
 /**
 * 检测批捡某商品的条码   zxcheng 2013.08.12
 */
 function ajax_check_batch_pick_goods_barcode($args) {	
 	if (!empty($args)) extract($args);
    $batch_pick_sn = trim($batch_pick_sn);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $result = array();
    if(empty($batch_pick_sn)){
    	$result['error'] = "批捡号为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($location_barcode)){
    	$result['error'] = "当前库位条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "当前商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_batch_pick_goods_barcode($batch_pick_sn,$location_barcode,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 /**
 *  检测批捡某商品的数量 zxcheng 2013.08.12
 */
 function ajax_check_batch_pick_goods_number($args) {	
 	if (!empty($args)) extract($args);
    $batch_pick_sn = trim($batch_pick_sn);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $goods_number = trim($goods_number);
    $result = array();
    if(empty($batch_pick_sn)){
    	$result['error'] = "批捡条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($location_barcode)){
    	$result['error'] = "当前库位条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "当前商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_batch_pick_goods_number($batch_pick_sn,$location_barcode,$goods_barcode,$goods_number);
    if($res['success']){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 /**
  * 判断是否串号控制
  */
  function ajax_check_goods_is_serial($args) {	
 	if (!empty($args)) extract($args);
    $party_id = trim($party_id);
    $goods_barcode = trim($goods_barcode);
    $result = array();
    require_once ("function.php");
    $res = check_goods_is_serial($party_id,$goods_barcode);
    Qlog::log("success=".$res['success']);
    if($res['success']){
    	$result['success'] = true;
    	$result['res'] = $res['res'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
  * 盘点商品条码检查 ljzhou 2013-09-17
  */
  function ajax_check_take_stock_goods_barcode($args) {	
 	if (!empty($args)) extract($args);
    $goods_barcode = trim($goods_barcode);
    $result = array();
    require_once ("function.php");
    $res = check_take_stock_goods_barcode($goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    	$result['is_serial'] = $res['is_serial'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
  * 根据串号得到商品条码
  */
  function ajax_get_goods_barcode($args) {	
 	if (!empty($args)) extract($args);
    $serial_number = trim($serial_number);
    $result = array();
    require_once ("function.php");
    $res = get_goods_barcode($serial_number);
    if($res['success']){
    	$result['success'] = true;
    	$result['goods_barcode'] = $res['goods_barcode'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 /**
  * 获取未入库商品数
  */
  function ajax_get_goods_not_in_number($args) {	
 	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $goods_barcode = trim($goods_barcode);
    if(empty($batch_order_sn)){
    	$result['error'] = "批次订单号为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    $result = array();
    require_once ("function.php");
    $res = get_goods_not_in_number($batch_order_sn,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    	$result['not_in_number'] = $res['not_in_number'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
  * 获取未入库商品数
  */
  function ajax_get_goods_not_in_number_v2($args) {	
 	if (!empty($args)) extract($args);
    $batch_order_sn = trim($batch_order_sn);
    $goods_barcode = trim($goods_barcode);
    if(empty($batch_order_sn)){
    	$result['error'] = "批次订单号为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    $result = array();
    require_once ("function.php");
    $res = get_goods_not_in_number_v2($batch_order_sn,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    	$result['not_in_number'] = $res['not_in_number'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
  * 获取通用上架未上架商品数 ljzhou 2013-10-10
  */
  function ajax_get_common_grouding_not_in_number($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    $goods_barcode = trim($goods_barcode);
    $serial_number = trim($serial_number);
    
    if(empty($order_sn)){
    	$result['error'] = "获取通用上架未上架商品数订单号为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "获取通用上架未上架商品数商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    
    $result = array();
    require_once ("function.php");
    $res = get_common_grouding_not_in_number($order_sn,$goods_barcode,$serial_number);
    if($res['success']){
    	$result['success'] = true;
    	$result['not_in_number'] = $res['not_in_number'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 /**
  * 获取库位上串号商品的数量
  */
  function ajax_get_location_serail_goods_number($args) {	
 	if (!empty($args)) extract($args);
    $location_barcode = trim($location_barcode);
    $goods_barcode = trim($goods_barcode);
    $serial_number = trim($serial_number);
    $result = array();
    if(empty($location_barcode)){
    	$result['error'] = "容器号为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($serial_number)){
    	$result['error'] = "商品串号为空！";
    	$result['success'] = false;
    	return $result;
    }
    
    require_once ("function.php");
    $res = get_location_serail_goods_number($location_barcode,$goods_barcode,$serial_number);
    if($res['success']){
    	$result['success'] = true;
    	$result['goods_number'] = $res['goods_number'];
    	Qlog::log('goods_number:'.$res['goods_number']);
    }else{
    	Qlog::log('goods_number2:'.$res['goods_number']);
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 
 /**
  * 推荐上架库位$grouding_location_barcode,$goods_barcode,$is_serial,$status_id
  */
 function ajax_recommend_grouding_location($args) {	
 	if (!empty($args)) extract($args);
    $grouding_location_barcode = trim($grouding_location_barcode);
    $goods_barcode = trim($goods_barcode);
    $status_id = trim($status_id);
    if(empty($grouding_location_barcode)){
    	$result['error'] = "推荐上架库位时库位条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "推荐上架库位时商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    $result = array();
    require_once ("function.php");
    $res = recommend_grouding_location($grouding_location_barcode,$goods_barcode,$status_id);
    if($res['success']){
    	$result['success'] = true;
    	$result['location_barcode'] = $res['location_barcode'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
  * 推荐通用上架库位 ljzhou 2013-10-10
  * $order_sn,$goods_barcode,$status_id
  */
 function ajax_recommend_common_grouding_location($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    $goods_barcode = trim($goods_barcode);
    $status_id = trim($status_id);
    if(empty($order_sn)){
    	$result['error'] = "推荐上架库位时order_sn为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "推荐上架库位时商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    $result = array();
    require_once ("function.php");
    $res = recommend_common_grouding_location($order_sn,$goods_barcode,$status_id);
    if($res['success']){
    	$result['success'] = true;
    	$result['location_barcode'] = $res['location_barcode'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 /**
  * 获取组织
  */
  function ajax_get_party_by_location($args) {	
 	if (!empty($args)) extract($args);
    $from_location_barcode = trim($from_location_barcode);
    $goods_barcode = trim($goods_barcode);
    if(empty($from_location_barcode)){
    	$result['error'] = "get_party_by_location容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "get_party_by_location商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    $result = array();
    require_once ("function.php");
    $res = get_party_by_location($from_location_barcode,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    	$result['res'] = $res['res'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
  /**
  * 根据订单获取组织 ljzhou 2013-10-10
  */
  function ajax_get_party_by_order_sn($args) {	
 	if (!empty($args)) extract($args);
    $order_sn = trim($order_sn);
    if(empty($order_sn)){
    	$result['error'] = "ajax_get_party_by_order_sn 订单为空！";
    	$result['success'] = false;
    	return $result;
    }

    $result = array();
    require_once ("function.php");
    $res = get_party_by_order_sn($order_sn);
    if($res['success']){
    	$result['success'] = true;
    	$result['party_id'] = $res['party_id'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 
 /**
  *  检查批拣单的库位总预订数是否足够多 ljzhou 2013-09-15
  */
  function ajax_is_shipments_have_enough_inventory($args) {	
 	if (!empty($args)) extract($args);
    $str_shipment_ids = trim($str_shipment_ids);
    $result = array();
    if(empty($str_shipment_ids)){
    	$result['error'] = "str_shipment_ids为空！";
    	$result['success'] = false;
    	return $result;
    }
    $shipment_id_array = explode(',',$str_shipment_ids);
    Qlog::log('ajax_is_shipments_have_enough_inventory str_shipment_ids'.$str_shipment_ids);
    Qlog::log('ajax_is_shipments_have_enough_inventory shipment_id_array'.implode(',',$shipment_id_array));
    
    //require_once (ROOT_PATH . 'admin/includes/lib_sinri_DealPrint.php');
//    require_once (ROOT_PATH . 'admin/function.php');
    
    $res = is_shipments_have_enough_inventory($shipment_id_array);
    if($res){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    }
    return $result;
 }
 
	
 /**
  *  获取未上架商品数
  */
  function ajax_get_grouding_not_in_number($args) {	
 	if (!empty($args)) extract($args);
    $grouding_location_barcode = trim($grouding_location_barcode);
    $goods_barcode = trim($goods_barcode);
    if(empty($grouding_location_barcode)){
    	$result['error'] = "获取未上架商品数时容器条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($goods_barcode)){
    	$result['error'] = "获取未上架商品数时商品条码为空！";
    	$result['success'] = false;
    	return $result;
    }
    $result = array();
    require_once ("function.php");
    $res = get_grouding_not_in_number($grouding_location_barcode,$goods_barcode);
    if($res['success']){
    	$result['success'] = true;
    	$result['not_in_number'] = $res['not_in_number'];
    }else{
    	$result['success'] = false;
    	$result['error'] = $res['error'];
    }
    return $result;
 }
 
 /**
  *  检测发货单号和运单号是否匹配
  */
  function ajax_check_shipment_id_tracking_number($args) {	
 	if (!empty($args)) extract($args);
    $shipment_id = trim($shipment_id);
    $tracking_number = trim($tracking_number);
    $result = array();
    if(empty($shipment_id)){
    	$result['error'] = "检测发货单号和运单号是否匹配时shipment_id为空！";
    	$result['success'] = false;
    	return $result;
    }
    if(empty($tracking_number)){
    	$result['error'] = "检测发货单号和运单号是否匹配时tracking_number为空！";
    	$result['success'] = false;
    	return $result;
    }
    
    require_once ("function.php");
    $res = check_shipment_id_tracking_number($shipment_id,$tracking_number);
    if($res){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    }
    return $result;
 }
 /**
  *  复核提交时再次确认订单状态
  * ytchen 2014-07-31
  */
  function ajax_can_recheck_pass($args) {	
 	if (!empty($args)) extract($args);
 	global $db;
    $shipment_id = trim($shipment_id); 
    $result = array();
    if(empty($shipment_id)){
    	$result['error'] = "复核提交时再次确认订单状态shipment_id为空！";
    	$result['success'] = false;
    	return $result;
    }
    $res = true;
    $sql =" select  
				oi.order_sn,
				oi.order_type_id,
				ep.pay_code,
				ep.is_cod,
				oi.pay_status,
				oi.order_status,
				oi.shipping_status
			from romeo.order_shipment os
			inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id 
			inner join ecshop.ecs_payment ep on oi.pay_id = ep.pay_id
			where os.shipment_id = '{$shipment_id}' AND ". party_sql("oi.party_id") ;
    $orders = $db->getAll($sql);
    foreach($orders as $order){
		if ($order['order_status'] != 1 || $order['shipping_status']==8  || 
		($order['pay_code'] != 'cod' && $order['is_cod'] == '0' && $order['pay_status'] != 2  && $order['order_type_id'] != 'RMA_EXCHANGE' 
			&& $order['order_type_id'] != 'SHIP_ONLY' ) ){
			$result['error'] = "订单{$order['order_sn']}信息更改，不可复核";
			$result['success'] = false;
			$res = false;
			return $result;
		}
    }
    if($res){
    	$result['success'] = true;

    }else{
    	$result['success'] = false;
    }
    return $result;
 }
 
  /**
  *  复核后更新发货状态
  * ljzhou 2013-10-09
  */
  function ajax_recheck_update_shipping_status($args) {	
 	if (!empty($args)) extract($args);
    $shipment_id = trim($shipment_id);
    $result = array();
    if(empty($shipment_id)){
    	$result['error'] = "复核后更新发货状态时shipment_id为空！";
    	$result['success'] = false;
    	return $result;
    }
    
    $res = terminalShipmentRecheck($shipment_id);
    
    if($res){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    }
    return $result;
 }
 
 /**
  *  检查是否复核过了
  * ljzhou 2013-10-09
  */
  function ajax_check_shipment_recheck($args) {	
 	if (!empty($args)) extract($args);
    $shipment_id = trim($shipment_id);
    $result = array();
    if(empty($shipment_id)){
    	$result['error'] = "检查是否复核过了时shipment_id为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_shipment_recheck($shipment_id);
    
    if($res){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    }
    return $result;
 }
 
 /**
  *  检查是否复核过了
  * ytchen 2015-07-08
  */
  function ajax_check_bpsn_recheck($args) {	
 	if (!empty($args)) extract($args);
    $bpsn = trim($bpsn);
    $result = array();
    if(empty($bpsn)){
    	$result['error'] = "检查是否复核过了时bpsn为空！";
    	$result['success'] = false;
    	return $result;
    }
    require_once ("function.php");
    $res = check_bpsn_recheck($bpsn);
    
    if($res){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    }
    return $result;
 } 
function ajax_check_need_code($args){
	global $db;
	if (!empty($args)) extract($args);
    $shipment_id = trim($shipment_id);
    $result = array();
    if(empty($shipment_id)){
    	$result['error'] = "检查是否需要物流码时shipment_id为空！";
    	$result['success'] = false;
    	return $result;
    }
    
    $sql = "select 1 
    	from romeo.order_shipment os
    	INNER JOIN ecshop.ecs_order_info o ON o.order_id = CAST(os.order_id as UNSIGNED)
    	INNER JOIN ecshop.ecs_order_goods og ON og.order_id = o.order_id
    	INNER JOIN ecshop.brand_heinz_goods bh ON bh.goods_outer_id = og.goods_id
		where bh.is_activity = 1 AND o.distributor_id in ('1797','1900','1953','2333')
		AND o.party_id = '65609' AND o.order_type_id in ('SALE','RMA_EXCHANGE')
		AND os.shipment_id = '$shipment_id' and bh.heinz_goods_sn like 'H%'";
	$need_code = $db->getOne($sql);
	
    $result['success'] = true;
    $result['need_code'] = !empty($need_code);
    return $result;
	}

/**
  * 检查发货单中是否有未预定成功的订单
  * ljzhou 2013-11-19
  */
  function ajax_check_merge_order_no_reserved($args) {	
 	if (!empty($args)) extract($args);
    $shipment_ids = trim($shipment_ids);
    $result = array();
    if(empty($shipment_ids)){
    	$result['error'] = "检查发货单中是否有未预定成功的订单时shipment_id为空！";
    	$result['success'] = false;
    	return $result;
    }
    $shipment_ids_arr = array();
    $shipment_ids_arr = explode(',',$shipment_ids);
    $res = check_merge_order_no_reserved($shipment_ids_arr);
    
    if($res){
    	$result['success'] = true;
    }else{
    	$result['success'] = false;
    }
    return $result;
 }

/**
 * 退换货收货、验货入库 扫描商品
 * 
 * @author zjli at 2014.2.13
 * 
 * @param string $key  // 商品条码（串号）
 * @param int $service_id   // 售后服务ID 
 * @return array
 */
function ajax_back_goods_scan($args){
	if (!empty($args)) extract($args);
	global $db;
	$key = trim($key);  // 用户扫描（输入）的商品条码（串号）
	$service_id = trim($service_id);
	$result = array();
	// 查询该service中包含的商品信息，包括【商品条码】等信息
	$sql = "SELECT sog.*, og.*, IFNULL(gs.barcode, g.barcode) as barcode FROM service_order_goods sog
          INNER JOIN ecshop.ecs_order_goods og ON sog.order_goods_id = og.rec_id
          LEFT JOIN ecshop.ecs_goods_style gs ON og.goods_id = gs.goods_id AND og.style_id = gs.style_id and gs.is_delete =0
          LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
          WHERE sog.is_approved = 1 AND sog.service_id  = '{$service_id}' order by sog.order_goods_id";
    $back_goods_list = $db->getAll($sql);
    
    $goodIndex = -1;  // 标识商品序列号，供前端进行操作，从0开始计数
    $goodIndexArray = array();  // 记录商品序号集合，由于同种商品可能有多条记录
    $keyStatus = 0;  // 标记用户输入的条码（串号）的查找结果    0:未找到用户输入的条码（串号）  1:找到了用户输入的条码   2:找到了用户输入的串号   3:用户输入了商品条码，但该商品为串号控制商品   4:用户输入了正确的串号，但该串号商品已经在库存里
    foreach($back_goods_list as $goods_key => $goods){
    	$goodIndex++; // 标识商品序列号，供前端进行操作，从0开始计数
    	$is_serial = getInventoryItemType($goods['goods_id']);
    	if($is_serial == 'SERIALIZED') {  // 串号控制商品
    		if($key == $goods['barcode']){  // 正确的商品条码，但该商品为串号控制商品，应该输入串号
    			$keyStatus = 3;
    			$goodIndexArray[] = $goodIndex;
//    			break;
    		}else{   // 查找用户输入的编号是否是该商品的串号
    			$serial_numbers = get_out_serial_numbers($goods['rec_id']);   // 得到已经出库的串号
    			if(!empty($serial_numbers) && in_array($key,$serial_numbers)){
    				// 判断该商品是否已经在库存里面，避免相同串号的商品重复入库
    				$sql = "SELECT COUNT(*) FROM romeo.inventory_item
							WHERE serial_number ='{$key}'
	  						AND quantity_on_hand_total > 0
	  						AND inventory_item_type_id='SERIALIZED'
	  						AND status_id IN ('INV_STTS_AVAILABLE', 'INV_STTS_USED')";
	  				$stockNum = $db->getOne($sql);
	  				
	  				if($stockNum > 0){  // 用户输入了正确的串号，但该串号商品已经在库存里
	  					$keyStatus = 4;
	  					$goodIndexArray[] = $goodIndex;
//	  					break;
	  				}else{  // 找到了用户输入的串号
	  					$keyStatus = 2;
	  					$goodIndexArray[] = $goodIndex;
//    					break;
	  				}
    			}
    		}
    	}else{   // 非串号控制商品
    		if($key == $goods['barcode']){  // 输入正确的非串号控制商品条码
    			$keyStatus = 1;
    			$goodIndexArray[] = $goodIndex;
//    			break;
    		}
    	}
    }
    
    $result['keyStatus'] = $keyStatus;
    $result['goodIndex'] = ($keyStatus == 0) ? -1 : $goodIndexArray;
    $result['key'] = $key;
    
	return $result;
}
 
/**
 * 退换货收货、验货入库 条码丢失 查找商品条码
 * 
 * @author zjli at 2014.2.25
 * 
 * @param string $key  // 商品名称
 * 
 * @return array
 */
 function ajax_get_barcode($args){
	if (!empty($args)) extract($args);
	global $db;
	$key = empty($key) ? '' : trim($key);  // 用户输入的商品名称
	$result = array('error'=>0, 'message'=>'', 'content'=>'');
	
	if($key != ''){
		$key = mysql_like_quote($key);
		$conditions = "g.goods_name LIKE '%{$key}%'"; 
		
		// 如果结果需要包含不下架的产品，则指定‘extend’参数
        $sql = "
            SELECT g.goods_id, CONCAT( goods_name, IF(is_on_sale = '0', '(已下架)', '')) AS goods_name, IFNULL(g.barcode,'') AS barcode
            FROM {$GLOBALS['ecs']->table('goods')} g
            WHERE {$conditions} AND g.is_delete = 0 AND " . party_sql("g.goods_party_id");
        $ref_goods_fields = $ref_goods_rowset = array();
        $goods_list = $db->getAllRefby($sql, array('goods_id'), $ref_goods_fields, $ref_goods_rowset, false);

        $result['goodslist'] = array();
        if ($goods_list) 
        {
            // 查询商品的样式
            $sql = "
                SELECT gs.goods_id, CONCAT(IF(gs.goods_color = '', s.color, gs.goods_color), '-', IFNULL(gs.barcode, '')) AS color_barcode
                FROM {$GLOBALS['ecs']->table('goods_style')} AS gs
                    LEFT JOIN {$GLOBALS['ecs']->table('style')} AS s ON gs.style_id = s.style_id  
                WHERE gs.goods_id ". db_create_in($ref_goods_fields['goods_id']) ;
            $ref_style_fields = $ref_styles_rowset = array();
            $db->getAllRefby($sql, array('goods_id'), $ref_style_fields, $ref_styles_rowset, false);

            foreach ($goods_list as $goods) 
            {
                $color_barcode_list = array();
	        	if(isset($ref_styles_rowset['goods_id'][$goods['goods_id']])){
	        		foreach ($ref_styles_rowset['goods_id'][$goods['goods_id']] as $color_barcode){
	        			$color_barcode_list[] = $color_barcode['color_barcode'];
	        		}
	        	}else{
	        		if(!empty($goods['barcode']) && $goods['barcode']!=""){
	        			$color_barcode_list[] = $goods['barcode'];
	        		}else{
	        			$color_barcode_list[] = "无条码记录";
	        		}
	        	}
	            $result['goodslist'][] = array(
	                'goods_id'   => $goods['goods_id'], 
	                'name'       => $goods['goods_name'],
	                'style_list' => $color_barcode_list , 
	            );
            }
        }
	}else{
		$result['error'] = 1;
        $result['message'] = 'NO KEYWORDS';
	}
	
	return $result;
}

/**
 * 得到订单商品
 * 
 * @param int $goods_id     // 通过商品id查询取得该商品
 * @param int $order_id     // 取得属于该订单的所有商品
 * 
 * @return array
 */
function ajax_get_order_goods($args)
{
	global $db;
    if (!empty($args)) extract($args);

    if (!function_exists('getProductId'))
    include_once(ROOT_PATH . "RomeoApi/lib_inventory.php");

    // 需要返回的字段
    $fields = 'rec_id, order_id, goods_id, goods_name, goods_sn, goods_number, goods_price, style_id, customized';

    $result = $db->getRow
    ("
        SELECT {$fields} FROM ecshop.ecs_order_goods WHERE rec_id = {$goods_id} LIMIT 1
    ");
    if ($result)
    {
        // 取得 productId
        $result['productId'] = getProductId($result['goods_id'], $result['style_id']);
    }
    return $result;
}
 function ajax_search_goods_identify($args){
 	include_once 'search_goods_identify.php';
 	if (!empty($args)) extract($args);
 	$barcode = trim($barcode);
 	$result = array();
 	$result = get_identify($barcode);
 	return $result;
 }
 
 /**
  *  电教或金佰利分销订单 判断预存款是否足够 
  */
 function ajax_check_prepayment($args) {
 	global $db;
 	if (!empty($args)) extract($args);
 	$result = array();
	$result['success'] = true;
	if (in_array ( $party_id, array ('16', '65548', '65558' ) )) {
		$sql = "
			SELECT 	   oi.party_id,md.main_distributor_id
			FROM 	   ecshop.ecs_order_info oi
			LEFT JOIN  ecshop.distributor d ON oi.distributor_id = d.distributor_id
		    LEFT JOIN  ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id 
			WHERE	   oi.order_id = '{$order_id}'
			limit 1
		";
		$party_distributor = $db->getRow ( $sql );
		if (! empty ( $party_distributor )) {
			$sql = "
				SELECT 	if(min_amount > available_amount,1,0)
				FROM 	romeo.prepayment_account
				WHERE	party_id = '{$party_distributor['party_id']}' and PREPAYMENT_ACCOUNT_TYPE_ID = 'DISTRIBUTOR' and SUPPLIER_ID = '{$party_distributor['main_distributor_id']}'
				limit 1
	        ";
			$pre_shortage = $db->getOne ( $sql );
			if ($pre_shortage == 1) {
				$result['success'] = false;
				$result['error_info'] = "该订单分销商的预付款账户预估余额（可用预存款 减去 从已确认订单但未发货的订单将要扣的预存款）小于预警值，请通知店长和财务\n一定要确认订单请点确认，否则点取消！";
			}
		}
	}
	return $result;
 }
 
  /**
  *  检查订单金额恒等式 
  */
 function ajax_check_pay_info($args) {
 	global $db;
 	if (!empty($args)) extract($args);
 	$result = array();
	$sql = "
		SELECT 	   oi.order_amount,oi.order_sn,goods_amount,shipping_fee,pack_fee,bonus,sum(goods_price * goods_number) as goods_price_total
		FROM 	   ecshop.ecs_order_info oi
		LEFT JOIN  ecshop.ecs_order_goods og ON oi.order_id = og.order_id
		WHERE	   oi.order_id = '{$order_id}'
		group by og.order_id
	";
	$order = $db->getRow($sql);
	if(empty($order)) {
		$result['success'] = false;
		$result['error_info'] = "ajax_check_pay_info 根据订单查不到对应的支付信息order_id:".$order_id;
	}
//	15.333333*12=183.999996与round(15.33333333*12,6)=184.000000 差距不只0.000001
//	if(abs($order['goods_amount'] - $order['goods_price_total']) > 0.000001) {
	if(abs($order['goods_amount'] - $order['goods_price_total']) > 0.0001) {
		$result['success'] = false;
		$result['error_info'] = "商品金额总和：".$order['goods_price_total']." 不等于订单商品金额:".$order['goods_amount']."，请根据实际调整商品金额。注意套餐金额设置时淘宝后台与ERP相等。";
	    return $result;
	}
	
	$order_amount_real = max(0,$order['shipping_fee'] + $order['goods_amount'] + $order['bonus'] + $order['pack_fee']);
	
	if(abs($order['order_amount'] - $order_amount_real) > 0.000001) {
		$result['success'] = false;
		$result['error_info'] = "订单商品金额+红包+快递费( ".$order_amount_real.") != 订单应付金额(".$order['order_amount'].")";
	    return $result;
	}
	//仅对换货订单而言！
	if(strcasecmp(substr($order['order_sn'],-2,2),'-h') == '0'){
		//先判断（$order['shipping_fee'] + $order['goods_amount'] + $order['bonus'] + $order['pack_fee']<0 ）false，并返回error_info信息
		$res_val=$order['shipping_fee'] + $order['goods_amount'] + $order['bonus'] + $order['pack_fee'];
		if($res_val<0){
			$result['success'] = false;
			$result['error_info'] = "订单商品金额+红包+快递费+包装费( ".$res_val.") < 0 ,不可以确认订单！请查证";
			return $result;
		}
		//添加商品修改判断，如果商品样式&&数量（！=）对应退货订单商品样式&&数量，提示“换货订单商品有修改，请确认红包金额不用修改；否则，请点击“取消”！”
		$sql_back_order_id = "select back_order_id from ecshop.service where change_order_id = '{$order_id}'";
		$back_order_id = $db->getOne($sql_back_order_id);
		//把结果放到两个数组中遍历比较
		$sql_back_arr = "select goods_id,style_id,count(goods_number) goods_number from ecshop.ecs_order_goods where order_id = '{$back_order_id}' group by goods_id,style_id";
		$back_arr = $db->getAll($sql_back_arr);
		$sql_change_arr = "select goods_id,style_id,count(goods_number) goods_number from ecshop.ecs_order_goods where order_id = '{$order_id}' group by goods_id,style_id";
		$change_arr = $db->getAll($sql_change_arr);
		if(count($back_arr)==count($change_arr)){
			foreach($back_arr as $back_good){
				$flag = false;
				foreach($change_arr as $change_good){
					if($back_good['goods_id']==$change_good['goods_id'] && $back_good['style_id']==$change_good['style_id'] && $back_good['goods_number']==$change_good['goods_number']){
						$flag=true;
						break;
					}
				}
				if(!$flag){
					$result['success_info'] = "换货订单商品有修改，如果红包金额不用修改，请“确定”；否则，请点击“取消”！";
					break;
				}
			}
		}else{
			$result['success_info'] = "换货订单商品有修改，如果红包金额不用修改，请“确定”；否则，请点击“取消”！";
		}
	}
	$result['success'] = true;

	return $result;
 }
 
 /**
 * 记录物流售后操作日志
 * ljzhou 2014-6-12
 */
  function ajax_update_logistic_service_note($args) {
 	if (!empty($args)) extract($args);
 	$result = update_logistic_service_note($service_id,$action_type);
 	return $result;
  }
  
  /**
 * 更新OR商品维护
 * ljzhou 2014-8-14
 */
  function ajax_update_or_goods($args) {
 	if (!empty($args)) extract($args);
 	$result = update_OR_goods($code,$material_number,$goods_type);
 	return $result;
  }
  
 /**
 * 更新跨境购商品维护
 * hzhang1 2015-07-22
 */
  
  function ajax_update_gz_haiguan_goods($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	$sql = "select 1 from  ecshop.sync_gz_haiguan_goods where product_id = '{$product_id}' limit 1";
	if($db->getOne($sql)) {
		$sql = "update ecshop.sync_gz_haiguan_goods set outer_id = '{$outer_id}',price='{$price}',rate='{$rate}',last_updated_stamp=now() where product_id='{$product_id}' limit 1";
	}
	return $db->query($sql);
  }
  
  function ajax_update_pay_info($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	if($args['platform'] == "360buy"){
		$sql = "update ecshop.sync_jd_order_info set jd_pay_type= '{$pay_type}',jd_pay_number='{$pay_number}',last_updated_stamp=now() where order_id='{$taobao_order_sn}'";
	}else if($args['platform'] == "miya"){
		$sql = "update ecshop.sync_miya_order_info set miya_pay_type= '{$pay_type}',miya_pay_number='{$pay_number}',last_updated_stamp=now() where miya_order_id='{$taobao_order_sn}'";
	}else if($args['platform'] == "fenxiao"){
			$sql = "update ecshop.haiguan_order_info set payment_code = '{$pay_type}',trade_trans_no='{$pay_number}',last_updated_stamp=now() where tid='{$taobao_order_sn}'";			
		}
	return $db->query($sql);
  }
  
  function ajax_delete_gz_haiguan_goods($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	$sql = "delete from ecshop.sync_gz_haiguan_goods where product_id='{$product_id}' limit 1";
	return $db->query($sql);
  }
  
   function ajax_delete_kuajing_goods($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	$sql = "delete from ecshop.kuajing_bird_product where product_id='{$product_id}' limit 1";
	return $db->query($sql);
  }
  
  
  function ajax_delete_kjg_pay($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	$sql = "delete from ecshop.haiguan_pay where haiguan_pay_id='{$haiguan_pay_id}' limit 1";
	return $db->query($sql);
  }
  
  /*
   *插入跨境购申报系统支付方式维护
   *hzhang1 2015-12-29
   */
   function ajax_insert_kjg_pay($args) {
	 	if (!empty($args)) extract($args);
	 	$session_party_id = $_SESSION['party_id'];
		global $db;
		$sql="select application_key from ecshop.taobao_shop_conf where nick='{$nick}'";
		$appkey=$db->getOne($sql);
		if($appkey){
			$sql = "insert into ecshop.haiguan_pay(nick,application_key,pay_id,pay_name,source,created_stamp) values('{$nick}','{$appkey}','{$pay_id}','{$pay_name}','{$source}',now())";
		}else{
			$sql="select distributor_id from ecshop.distributor where name='{$nick}'";
			$appkey=$db->getOne($sql);
			$sql = "insert into ecshop.haiguan_pay(nick,application_key,pay_id,pay_name,source,created_stamp) values('{$nick}','{$appkey}','{$pay_id}','{$pay_name}','{$source}',now())";
		}
		return $db->query($sql);
  }
  
  /*
   *插入跨境购商品信息
   *hzhang1 2015-07-22
   */
  
  function ajax_insert_gz_haiguan_goods($args) {
 	if (!empty($args)) extract($args);
 	$session_party_id = $_SESSION['party_id'];
	global $db;
	$sql = "insert into ecshop.sync_gz_haiguan_goods(party_id,application_key,product_id,goods_name,outer_id,unit,price,rate,created_stamp,last_updated_stamp) values('{$session_party_id}','{$application_key}','{$product_id}','{$goods_name}','{$outer_id}','{$unit}','{$price}','{$rate}',now(),now())";
	return $db->query($sql);
  }
  
    /*
   *插入跨境商品維護信息
   *hzhang1 2015-12-14
   */
   function ajax_insert_kuajing_goods($args) {
 	if (!empty($args)) extract($args);
 	$session_party_id = $_SESSION['party_id'];
	global $db;
	$sql="select application_key from ecshop.taobao_shop_conf where nick='{$application_key}'";
	$nick=$db->getOne($sql);
	if($nick){
		$sql = "insert into ecshop.kuajing_bird_product(party_id,applicationkey,goods_name,outer_id,item_code,kao_code,quantity,created_stamp,last_updated_stamp) values('{$session_party_id}','{$nick}','{$goods_name}','{$outer_id}','{$item_code}','{$kao_code}',0,now(),now())";
		return $db->query($sql);
	}else{
		return false;
	}
  }
  
 /*
 * 更新跨境购商店
 * hzhang1 2015-07-22
 */
  function ajax_update_kjg_shop($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	$sql = "update ecshop.haiguan_api_params set app_key = '{$app_key}',app_secret='{$app_secret}',uin='{$uin}' where application_key='{$application_key}' limit 1";
	return $db->query($sql);
  }
  
   function ajax_delete_kjg_shop($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	$sql = "delete from ecshop.haiguan_api_params where application_key='{$application_key}' limit 1";
	return $db->query($sql);
  }
  
  function ajax_close_kjg_shop($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	$sql = "update ecshop.haiguan_api_params set status = '0' where application_key='{$application_key}' limit 1";
//	var_dump($sql);
	return $db->query($sql);
  }
  function ajax_open_kjg_shop($args) {
 	if (!empty($args)) extract($args);
 	global $db;
	$sql = "update ecshop.haiguan_api_params set status = '1' where application_key='{$application_key}' limit 1";
//	var_dump($sql);
	return $db->query($sql);
  }
  /*
   *插入跨境购商店
   *hzhang1 2015-07-22 
   */
   function ajax_insert_kjg_shop($args) {
 	if (!empty($args)) extract($args);
 	$session_party_id = $_SESSION['party_id'];
 	global $db;
 	$sql = "insert into ecshop.haiguan_api_params(party_id,nick,application_key,app_key,app_secret,uin,created_stamp,platform,status) values('{$session_party_id}','{$nick}','{$application_key}','{$app_key}','{$app_secret}','{$uin}',now(),'{$shop_flag}','{$shop_status}')";
	return $db->query($sql);
//	$sql="select party_id,application_key from ecshop.taobao_shop_conf where nick='{$nick}' and party_id = '{$session_party_id}'";
//	$shop_conf=$db->getRow($sql);
//	$appkey = $shop_conf['application_key'];
//	$party_id = $shop_conf['party_id'];
//	if(!empty($shop_conf)) {
//		$sql = "insert into ecshop.haiguan_api_params(party_id,nick,application_key,app_key,app_secret,uin,created_stamp,platform,status) values('{$party_id}','{$nick}','{$appkey}','{$app_key}','{$app_secret}','{$uin}',now(),'{$shop_flag}','{$shop_status}')";
//		return $db->query($sql);
//	}else{
//		$sql="select party_id,distributor_id from ecshop.distributor where name='{$nick}'";
//		$shop_conf=$db->getRow($sql);
//		$appkey = $shop_conf['distributor_id'];
//		$party_id = $shop_conf['party_id'];
//		if(!empty($shop_conf)){
//			$sql = "insert into ecshop.haiguan_api_params(party_id,nick,application_key,app_key,app_secret,uin,created_stamp,platform,status) values('{$party_id}','{$nick}','{$appkey}','{$app_key}','{$app_secret}','{$uin}',now(),'{$shop_flag}','{$shop_status}')";
//			return $db->query($sql);
//		}else{
//			return false;
//		}
//	}
  }
  
  /**
 * 更新estee商品维护
 * jwang 2014-11-27
 */
  function ajax_update_estee_goods($args) {
 	if (!empty($args)) extract($args);
 	$result = update_estee_goods($code,$material_number,$goods_type);
 	return $result;
  }
 
 /**
 * 根据outer_sku_id或outer_id查找ecshop.sync_taobao_order_goods表中的goods
 * 
 * @param string outer_id 
 */
function ajax_search_outer_goods($args)
{
	if (!empty($args)) extract($args);
	$party_id = $_SESSION['party_id'];
	$sql = "select title from ecshop.sync_taobao_order_goods where party_id = '{$party_id}' and outer_sku_id = '{$outer_id}' limit 1";
	$good_name = $GLOBALS['db']->getOne($sql);
   	if(empty($good_name)){
   		$good_name = $GLOBALS['db']->getOne("select title from ecshop.sync_taobao_order_goods where party_id = '{$party_id}' and outer_iid = '{$outer_id}' limit 1");
   	}
	if(empty($good_name)) {
		return '';
	}else{
		$arr = explode("】",$good_name);
		$length = count($arr);
		return $arr[$length-1];
	}

}

/**
 * 修改退回单号的数据
 * 
 * 
 * */
function ajax_edit_service_return($args)
{	
	global $service_return_key_mapping;
	global $db;
	if(!empty($args)) extract($args);
	$service_id  = $args['service_id'];
	
    $sql = "UPDATE service SET backfee_paiedby = '{$args['backfee_paiedby']}' WHERE service_id = '{$service_id}' LIMIT 1 ";
    $db->query($sql);
   $service_return = array();
    foreach($service_return_key_mapping as $key => $val){
        if($_POST[$key]) {
            $service_return[] = "('{$service_id}', '{$key}', '$_POST[$key]','carrier_info')";
        }
    }
        $sql = "DELETE FROM service_return WHERE service_id = '{$service_id}' AND return_type = 'carrier_info' ";
        $db->query($sql);
        $sql = "INSERT INTO service_return( service_id, return_name, return_value, return_type) VALUES " . join(",", $service_return);
        $db->query($sql);
}
	
/**
 * 通过淘宝订单号查询是否已经有对应的换货或者退货订单
 * */
function ajax_search_order_sn_by_taobao($args)
{	
	global $db;
	$taobao_order_sn = trim($args['taobao_order_sn']);
	$result =array();
	if (!empty($taobao_order_sn)) {
            $sql = "SELECT order_sn, order_id FROM ecshop.ecs_order_info WHERE taobao_order_sn = '". $db->escape_string($taobao_order_sn) ."'";
            $exists = $db->getRow($sql);
            if (!empty($exists['order_id'])) {
               	$result['message'] = "该淘宝订单号已经存在了，ERP订单号："."<a href=\"order_edit.php?order_id={$exists['order_id']}\" target=\"_blank\">".
                "{$exists['order_sn']}</a> 如有问题，请及时联系ERP组。";
               	return $result;
            }
        }
	
	$taobao_order_sn = explode("-",$args['taobao_order_sn']);
	$taobao_order_sn =  $taobao_order_sn[0];
	$sql = "select r.order_sn from ecshop.ecs_order_info i inner join ecshop.order_relation r".
			" on i.order_id = r.root_order_id".
		    " where i.taobao_order_sn like '{$taobao_order_sn}-%' and r.order_sn like '%-h%'";
	$order_sn = $db -> getOne($sql);
	if(!empty($order_sn)){
		$result['message'] = "已存在淘宝订单相应的ERP订单:{$order_sn}";
	}
	$result['isSubmit'] = 1;
	
	return $result;
}

/**
 * 通过换货或退货订单号查询原订单号是否已经对应的新申请的淘宝订单号
 * */
function ajax_search_taobao_sn_by_order($args)
{
	global $db;
	$result = array();
	$order_sn = explode("-",$args['order_sn']);
	$order_sn =  $order_sn[0];
	$sql = "select taobao_order_sn from ecshop.ecs_order_info 
		     where order_sn = '{$order_sn}' ";
	$taobao_order_sn = $db -> getOne($sql);
	
	if(!empty($taobao_order_sn)){
		$taobao_order_sn_array = explode("-",$taobao_order_sn);
		$taobao_order_sn_new = $taobao_order_sn_array[0];
		if(strlen($taobao_order_sn_array[count($taobao_order_sn_array)-1]>2)){
			$taobao_order_sn_new = $taobao_order_sn;
		}
		if(count($taobao_order_sn_array)==3){
			$taobao_order_sn_new = $taobao_order_sn_array[0]."-".$taobao_order_sn_array[1];
		}
		$sql = "select 1 from ecshop.ecs_order_info where taobao_order_sn like '{$taobao_order_sn_new}-%' AND order_sn <> '{$args['order_sn']}'";
		if($db ->getRow($sql)){
			return $result;
		}
	}
	$result['isSubmit'] = 1;
	return $result;
}

/**
 * 通过订单号查询该订单是否已上传给跨境购平台审核，并根据审核状态来判断该订单是否能取消
 * */
function ajax_search_kgj_by_orderSn($args)
{
	global $db;
	$result = array();
	$order_sn = explode("-",$args['order_sn']);
	$order_sn =  $order_sn[0];
	//按ERP订单号在sync_kjg_order_status中查找最近的一次上传到跨境购平台的订单的订单状态（对ERP中某个订单因为订单信息没通过审核，可能多次上传，只需取最近一次上传信息即可）
	$sql = "
			SELECT 
			  	os.status 
			FROM
				ecshop.ecs_order_info oi 			
				inner JOIN ecshop.sync_kjg_order_info koi on koi.taobao_order_sn=if(oi.order_type_id ='SALE',oi.taobao_order_sn, CONCAT(oi.taobao_order_sn,'-h'))			
				inner JOIN ecshop.sync_kjg_order_status os on koi.taobao_order_sn=os.taobao_order_sn and koi.mft_no=os.mft_no
			where oi.order_sn='{$order_sn}' and oi.order_type_id ='SALE' ORDER BY koi.created_stamp DESC limit 1 ";
	$kjg_status = $db -> getOne($sql);
	
	$result['isSubmit'] = 1;
	if(!empty($kjg_status)){
		$status = $kjg_status['status'];
		//已经上传且状态不为'已关闭'的订单不能取消
		if($status<>'已关闭'){
			$result['isSubmit'] = 0;
		}		
	}
	//未上传，或者已上传但是状态为'已关闭'的订单可以取消
	return $result;
}

/**
*检测订单中是否有该商品
**/
function ajax_check_goods($args)
{
	global $db;
	$result = array();
	$order_id = $args['order_id'];
	$sql = "select 1 from ecshop.ecs_order_goods where order_id = '{$order_id}' and goods_id = '193863';";
	$result = $db ->getAll($sql);
	return $result;
}

/**
 * 判断OR百万增值活动 商品级别红包是否有误
 */
function ajax_check_discountfee($args) 
{
	global $db;
	$result = array();
	$result['success'] = true;
	$order_id = $args['order_id'];
	$sql_tc = "select oga.value 
				from ecshop.ecs_order_info eoi
				inner join ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id and eog.goods_id = '196993'   --  赠送的商品
				inner join ecshop.order_goods_attribute oga on oga.order_goods_id = eog.rec_id and oga.name = 'OUTER_IID'
				where eoi.order_id = '{$order_id}'"; 
	$tc = $db -> getAll($sql_tc);	
	if(!empty($tc))	{
		$sql_discount = "select eoi.order_id, eoi.bonus,ifnull(oa.attr_value,0) as order_discount,sum(ifnull(oga.value,0)) as goods_discount from ecshop.ecs_order_info eoi
							left join ecshop.order_attribute oa on oa.order_id = eoi.order_id and oa.attr_name = 'DISCOUNT_FEE'
							left join ecshop.ecs_order_goods eog on eog.order_id = eoi.order_id
							left join ecshop.order_goods_attribute oga on oga.order_goods_id = eog.rec_id and oga.name = 'DISCOUNT_FEE'
							where eoi.order_id = '{$order_id}'
							group by eoi.order_id";
		$discount_fees = $db->getRow($sql_discount); 
		if(-$discount_fees['bonus'] == ($discount_fees['order_discount'] + $discount_fees['goods_discount'])) {
			$result['success'] = true;
		} else {
			$result['success'] = false;
		}			
	}
	return $result;
}



/**
 * 检测是否已批拣
 */
function ajax_is_picked($args) {
	global $db;
	$shpping_statuses = array('15','0');
	$result = array();
	$result['is_picked'] = true;
	$order_id = $args['order_id'];
	$sql = "select shipping_status from ecshop.ecs_order_info where order_id = '{$order_id}'";
	$shipping_status = $db->getOne($sql);
	if(in_array($shipping_status,$shpping_statuses)) {
		$result['is_picked'] = false;
	} else {
		$result['is_picked'] = true;
	}
	return $result;
}


/*需求变更，验证套餐存在功能弃用
function ajax_check_is_existed($args)
{
    global $db;
    $date=$args['sendData'];
    $slash=stripslashes($date);//这里是用来处理前台传的json数组中的"被转化成\&quot;的问题，如果没有，后台无法通过下标获取值   
    $date2 = json_decode($slash, TRUE);//通过上一步的处理，这里用json_decode将json数组转化成关联数组
    //var_dump($date2);
    //$goods_id = $date2[0]['goods_id'];
    //print_r($goods_id);
    $condition = get_condition($date2);
    $sql = "SELECT distinct group_id, code FROM ecshop.distribution_group_goods dgd WHERE status='OK' ".$condition; 
    //var_dump($sql);
    $result = $db->getAll($sql); 
    // var_dump($result);
    // var_dump($result[0]['group_id']);
    $count=count($result,COUNT_NORMAL);
    $count_create=count($date2,COUNT_NORMAL);
    for ($i=0; $i <$count ; $i++) {
        $group_id=$result[$i]['group_id'];
        $sql2="SELECT count(*) FROM ecshop.distribution_group_goods_item where group_id=".$group_id;
        $count_goods = $db->getCol($sql2);
        if($count_goods[0]==$count_create){
        $code= $result[$i]['code'];
        break;
        }
    }
    if($code){
        return $code;
    }
    else{
        return 1;
    }
}

function get_condition($date2){
        $count=count($date2,COUNT_NORMAL);
        $condition = "";
            for ($i=0; $i <$count ; $i++) { 
                $goods_id = $date2[$i]['goods_id'];
                $style_id = $date2[$i]['style_id'];
                $goods_number = $date2[$i]['goods_number'];
                $price = $date2[$i]['price'];
                $condition .=" and EXISTS (select 1 from ecshop.distribution_group_goods_item where goods_id=".$goods_id." and style_id=".$style_id." and goods_number=".$goods_number." and price=".$price." and group_id=dgd.group_id)";
            }
        return $condition;
    }
    */

/**
  *  批次复核，提交时再次确认订单状态
  * 
  */
  function ajax_can_batch_recheck_pass($args) {   
    if (!empty($args)) extract($args);
    global $db;
    $result = array();
    if(empty($shipment_ids)){
        $result['error'] = "复核提交时再次确认订单状态shipment_id为空！";
        $result['success'] = false;
        return $result;
    }
    $shipment_ids_str = str_replace(',',"','",$shipment_ids);
    $res = false; 
    $sql =" SELECT  
        oi.order_sn,
        oi.order_type_id,
        ep.pay_code,
        ep.is_cod,
        oi.pay_status,
        oi.order_status,
        oi.shipping_status
    from romeo.order_shipment os
    inner join ecshop.ecs_order_info oi on cast(os.order_id as unsigned) = oi.order_id 
    inner join ecshop.ecs_payment ep on oi.pay_id = ep.pay_id
    where os.shipment_id in ('{$shipment_ids_str}') AND ". party_sql("oi.party_id") ;
    //QLog::log("ajax_can_recheck_pass-sql : ".$sql." \n");
    $orders = $db->getAll($sql);
    //Qlog::log("ajax_can_recheck_pass-shipment_id :".$orders." /n");   
    foreach($orders as $order){
        //QLog::log("ajax_can_recheck_pass-order : ".$order['order_sn']." ,shipping_status =: ".$order['shipping_status'].",order_status : ".$order['order_status']." \n ");
        if ($order['order_status'] != 1 || $order['shipping_status']==8  || 
        ($order['pay_code'] != 'cod' && $order['is_cod'] == '0' && $order['pay_status'] != 2  && $order['order_type_id'] != 'RMA_EXCHANGE' 
            && $order['order_type_id'] != 'SHIP_ONLY' ) ){
             //QLog::log("ajax_can_recheck_pass-order : ".$order['order_sn']." error!!!!"); 
            $result['error'] = "订单{$order['order_sn']}信息更改，不可复核";
            $result['success'] = false;
            return $result;
        }else{
            $res = true; 
        }
        //QLog::log("ajax_can_recheck_pass-order : ".$order['order_sn']." end.. ");
    }
    if($res){
        $result['success'] = true;

    }else{
        $result['success'] = false;
    }
    return $result;
 }


/**
*  复核后批量更新发货状态
* 
*/
function ajax_recheck_update_batch_shipping_status($args) { 
if (!empty($args)) extract($args);
global $db;
$result = array();
if(empty($shipment_ids)){
    $result['error'] = "复核后更新发货状态时shipment_id为空！";
    $result['success'] = false;
    return $result;
}
$shipment_ids_str = str_replace(',',"','",$shipment_ids);
$sql = "SELECT order_id from romeo.order_shipment
        WHERE shipment_id in ('{$shipment_ids_str}')";
$order_ids = $db->getCol($sql);
$order_ids = implode(',',$order_ids);   
$res = terminal_Shipment_Recheck($order_ids);
if($res){
    $result['success'] = true;
    add_action($order_ids);
}else{
    $result['success'] = false;
}
return $result;
}

function terminal_Shipment_Recheck($order_ids){
    global $db;
    $sql="UPDATE ecshop.ecs_order_info set shipping_status = '8' where order_id in ($order_ids)";
    $result = $db->query($sql); 
    return $result;
}

function add_action($order_ids){
    global $db;
    $actionUser = $_SESSION['admin_name'];
    $sql1 = "SELECT order_id, order_status, shipping_status, pay_status FROM ecshop.ecs_order_info 
            WHERE order_id in ($order_ids)";
    $order_info = $db->getAll($sql1);
    $length = count($order_info);
    $condition = "values('".$order_info[0]['order_id']."','".$order_info[0]['order_status']."','".$order_info[0]['shipping_status']."','".$order_info[0]['pay_status']."',NOW(),'".$actionUser."','复核成功'),";
    for ($i=1; $i < $length; $i++) {
        if($i == $length-1){
            $condition_temp="('".$order_info[$i]['order_id']."','".$order_info[$i]['order_status']."','".$order_info[$i]['shipping_status']."','".$order_info[$i]['pay_status']."',NOW(),'".$actionUser."','复核成功')";
        }else{ 
        $condition_temp="('".$order_info[$i]['order_id']."','".$order_info[$i]['order_status']."','".$order_info[$i]['shipping_status']."','".$order_info[$i]['pay_status']."',NOW(),'".$actionUser."','复核成功'), ";
        }
        //Qlog::log("condition_temp:".$condition_temp." /n");  
        $condition.=$condition_temp;
    } 
    //Qlog::log("add_action-condition :".$condition." /n");      
    $sql2 = "INSERT INTO ecshop.ecs_order_action (order_id, order_status, shipping_status, pay_status, action_time, action_user, action_note) ".$condition;
    //Qlog::log("add_action-sql2 :".$sql2." /n");
    $result = $db->query($sql2);
}

function ajax_add_print_record_for_carrier_bill($args){
    if (!empty($args)) extract($args);
    // now exists type,order_sn,tracking_number
    require_once('includes/lib_print_action.php');

    $result=array();

    $pr_id=LibPrintAction::addPrintRecord($type,$tracking_number,$order_sn);
    $result['result']=$pr_id;
    if(empty($pr_id)){
        $result['error']='FAILED TO INSERT';
    }

    return $result;
}

function ajax_change_priority($args){
    global $db;
    if (!empty($args)) extract($args);
    $priority = trim($args['priority']);
    $REFUND_ID = trim($args['REFUND_ID']);
    if($priority == ""){
        $sql = "UPDATE romeo.refund set priority = 'HIGH' where REFUND_ID = ".$REFUND_ID;
        $res = $db->query($sql);
        if($res == true){
            $result['success'] = true;
            $result['priority'] = "HIGH";
            $result['REFUND_ID'] = $REFUND_ID;
        }
        else{
            $result['success'] = false;
        }
        return $result;
    }
    elseif ($priority == "HIGH") {
        $sql = "UPDATE romeo.refund set priority = '' where REFUND_ID = ".$REFUND_ID;
        $res = $db->query($sql); 
       if($res == true){
            $result['success'] = true;
            $result['priority'] = "";
            $result['REFUND_ID'] = $REFUND_ID;
       }
       else{
            $result['success'] = false;
       }
        return $result;
    }   
}

function ajax_kjg_order_cancel($args) {
	global $db;
	if (!empty($args)) extract($args);
    $order_id = trim($args['order_id']);
	$sql = "select count(*) 
			from ecshop.ecs_order_info eoi 
			left join ecshop.sync_kjg_order_info skoi on skoi.taobao_order_sn = eoi.taobao_order_sn
			where skoi.status = 'SUCCESS' and eoi.order_id = '{$order_id}'";
	$order_count = $db -> getOne($sql);	
	if($order_count > 0) {
		$result['flag'] = 'ERROR';
		$result['message'] = '请先在【申报系统订单监控页】中关闭该订单，再作废该订单！';
	} else {
		$result['flag'] = 'SUCCESS';
	}
	return $result;
}