<?php
/**
 * 工单管理
 */
 
//insert into 
//   romeo.dispatch_list
//(dispatch_status_id, dispatch_priority_id, order_id, order_sn, external_order_sn, goods_name, order_goods_id)
//from 
//select 
//    "FINISHED", "RUSH", eo.order_id, eo.order_sn, eo.taobao_order_sn,
//    g.goods_name,
//    og.rec_id
//from
//    ecshop.ecs_order_info eo,
//    ecshiop.ecs_order_goods og,
//    ecshop.ecs_goods g
//where
//    eo.order_id = og.order_id and
//    og.goods_id = g.goods_id and
//    eo.order_id in (
//    )

function jjsHouseUpdateDispatchList($row = null, $attributes = null) {
	$dispatchList = new stdClass();
    $row = $row ? $row : $_REQUEST['row'];
    
	foreach ($row as $key => $value) {
	    // 如果没有设置dispatchSn,就不要设置了，否则会报duplicate key错
	    if ($key == 'dispatchSn' && !$value) { 
	        continue;
	    }
        $dispatchList->$key = $value;
    }
    
    $oldDispatchList = getDispatchList($dispatchList->dispatchListId);
    $dispatchList->dispatchStatusId = $oldDispatchList->dispatchStatusId;
    $dispatchList->submitDate  = $oldDispatchList->submitDate ;
    $dispatchList->dispatchSequenceNo = $oldDispatchList->dispatchSequenceNo;
    $dispatchList->currency = 'RMB';
    
    $attributes = $attributes ? $attributes : $_REQUEST['attributes'];
    //var_dump($attributes);die();
    
    #p($row, $attributes);die();
    
    // print_r($dispatchList);
    //限制工单价格   by jrpei 2011-7-6
    $latestPurchasePrices = getLatestPurchasePrices($dispatchList->orderGoodsId, $dispatchList->dispatchSn);
    if ($_SESSION['admin_name'] != 'qzhong'){
        if (!empty($latestPurchasePrices)) {
            if ($dispatchList->price > ($latestPurchasePrices[0]['price']*1.1)){
                sys_msg("价格不超过前一次同一件商品采购价格的10%,如有特殊情况，请联系charles@jjshouse.com");
            }
        }
        if ($dispatchList->price > 900){
            sys_msg("价格不能超过900元,如有特殊情况，请联系charles@jjshouse.com");   
        }
    }
    updateDispatchList($dispatchList, $attributes);
}
 
define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../../RomeoApi/lib_dispatchlist.php');
include_once('../function.php');

admin_priv('dispatch_list_purchase', 'dispatch_list_customer_service');
party_priv('65542');

$act = $_REQUEST['act'];
$dispatchListId = $_REQUEST['dispatchListId'];
$showForm = false;

$smarty->assign('dispatchPriorityCandidates', 
	array('RUSH' => '加急（RUSH）', 'NORMAL' => '正常(NORMAL)'));
    
if ($act == 'cancel') { // 取消工单
    cancelDispatchList($dispatchListId);
    $smarty->assign('message', "取消工单成功");
} elseif ($act == "confirm" ) { // 工单完成
    $dispatchList = new stdClass();
    $dispatchList->dispatchListId = $dispatchListId;
    confirmDispatchList($dispatchList);
    $smarty->assign('message', "工单完成");
} elseif ($act == 'updateSubmitAjax') { // 在列表页通过ajax确认工单
	
	$dispatchListId = trim($_REQUEST['dispatchListId']);
	
    $dispatchList = getDispatchList($dispatchListId);
    $attributes = getDispatchListAttributes($dispatchListId);
    
    list($styleAttributes, $imgAttributes) = splitStyleImgAttributes($attributes, true);
    
    $latestPurchasePrices = getLatestPurchasePrices($dispatchList->orderGoodsId, $dispatchList->dispatchSn);
    
	if ($_SESSION['admin_name'] != 'qzhong') {
		if (!empty($latestPurchasePrices)) {
			if ($_REQUEST['price'] > ($latestPurchasePrices[0]['price'] * 1.1)) {
				$r = array(
					'code' => 1,
					'msg' => "价格不超过前一次同一件商品采购价格的10%,如有特殊情况，请联系charles@jjshouse.com",
				);
				echo json_encode($r);
				die();
			}
		}
		if ($_REQUEST['price'] > 900) {
			$r = array(
				'code' => 1, 
				'msg' => "价格不能超过900元,如有特殊情况，请联系charles@jjshouse.com"
			);
			echo json_encode($r);
			die();
		}
	}

    $toAssignAttrs = array(
        'orderId', 'orderSn', 'orderGoodsId', 'externalOrderSn',
        'productId', 'goodsName', 'goodsSn',
        'dispatchPriorityId', 'expectedDeliverDate', 'partyId', 
        'imageUrl', 'externalGoodsId',
    );
    // {{{ 构造数据
    $row = array();
    foreach ($toAssignAttrs as $toAssignAttr) {
        $row[$toAssignAttr] = $dispatchList->$toAssignAttr;
    }
    $row['dueDate'] = date("Y-m-d", strtotime($dispatchList->dueDate));
    $row['shippingDate'] = date("Y-m-d", strtotime($dispatchList->shippingDate));
    
    $row['price'] = trim($_REQUEST['price']);
    $row['providerId'] = trim($_REQUEST['providerId']);
    $row['dispatchListId'] = $dispatchListId;
    $row['dispatchSn'] = $dispatchList->dispatchSn;
    
    $_tmp = $styleAttributes;
    foreach (jjshouseGoodsAttributeName() as $v) {
    	if ($v && !isset($_tmp[$v])) {
    		$_tmp[$v] = '';
    	}
    }
    $styleAttributes = array();
    foreach ($_tmp as $k => $v) {
    	$styleAttributes["goodsStyle_" . $k] = $v;
    }
    
    $attrs = array();
    $attrs = $attrs + $styleAttributes;
    $attrs['note'] = $attributes['note'];
    $attrs = $attrs + $imgAttributes;
    
    jjsHouseUpdateDispatchList($row, $attrs);
	
	// }}}
	$dispatchListId = $row['dispatchListId'];
	$dispatchList = new stdClass();
	$dispatchList->dispatchListId = $dispatchListId;
	submitDispatchList($dispatchList);
    $r = array(
		'code' => 0, 
		'msg' => "$dispatchListId 的工单已生成"
	);
	echo json_encode($r);
	die();
} elseif ($act == 'updateSubmit') { // 提交更新好的工单
    $dispatchList = getDispatchList($dispatchListId);
    if ($dispatchList->purchaseOrderId) {
        $smarty->assign('message', "工单已经生成采购订单无法修改");
    } elseif (in_array($dispatchList->dispatchStatusId, array('FINISHED', 'OK', 'CANCELLED'))) {
        $smarty->assign('message', "工单在当前状态无法修改");
    } else {
        jjsHouseUpdateDispatchList();
        $row = $_REQUEST['row'];
        
        if ($_REQUEST['subAct'] == '确认工单') {
        	$dispatchListId = $row['dispatchListId'];
            $dispatchList = new stdClass();
            $dispatchList->dispatchListId = $dispatchListId;
            submitDispatchList($dispatchList);
            $smarty->assign('message', 
    			"确认工单{$row['dispatchSn']}成功。" .
    			"<a href='/admin/dispatchlist/print.php?dispatchListId=" . $dispatchListId . "'>打印工单</a>");
    	} elseif ($_REQUEST['subAct'] == '重新修订工单') {
        	$dispatchListId = $row['dispatchListId'];
            $dispatchList = new stdClass();
            $dispatchList->dispatchListId = $dispatchListId;
            reviseDispatchList($dispatchList);
            $smarty->assign('message', "工单{$row['dispatchSn']}已经被退回修订。");
        } elseif ($_REQUEST['subAct'] == '重新提交工单') {
        	$dispatchListId = $row['dispatchListId'];
            $dispatchList = new stdClass();
            $dispatchList->dispatchListId = $dispatchListId;
            prepareDispatchList($dispatchList);
            $smarty->assign('message', "工单{$row['dispatchSn']}已经被修订完毕，重新提交了。");
        } else {
            $smarty->assign('message', "更新工单{$dispatchList->dispatchSn}成功");
        }
    }

} elseif ($act == "createSubmit") { // 创建工单
    $row = $_REQUEST['row'];
    $dispatchList = new stdClass();
    foreach ($row as $key => $value) {
        $dispatchList->$key = $value;
    }
    $dispatchList->currency = 'RMB';
    
	// var_dump($dispatchList); die();
    
    // 工单属性
    $attributes = $_REQUEST['attributes'];
    
    // $dispatchList->submitDate = date("Y-m-d H:i:s");
    $dispatchList->providerId = 0;
    $dispatchList->price = 0;
    try {
        $dispatchListId = createDispatchList($dispatchList, $attributes);
    } catch (SoapFault $e) {
        sys_msg("创建工单失败：" . $e->faultstring);
    }
    
    //$dispatchList->dispatchListId = $dispatchListId;
    //submitDispatchList($dispatchList);
    $smarty->assign('message', "创建工单成功，相关单号{$dispatchListId}");
    
    // {{{ create_update.php?act=createPre&goodsSn={$item->goodsSn}&externalOrderSn={$item->externalOrderSn}&orderSn={$item->orderSn}&partyId={$item->partyId}&orderGoodsId={$item->orderGoodsId}&defaultDispatchPriority={$item->defaultDispatchPriority}&expectedDeliverDate={$item->expectedDeliverDate|urlencode}
    $criteria = new stdClass();
	$criteria->offset = 0;
	$criteria->count = 500;
	$criteria->partyId = $_SESSION['party_id'];
    $candidates = searchDispatchCandidates($criteria);
	$candidates = addFinishedCancelledCount($candidates);
	if ($candidates) {
		
    	$index = (int) $_REQUEST['index'];
    
		foreach ($candidates as $k => $item) {
			if ($item->cancelledCount != 0 || $k < $index) {
				// 相同订单有取消的工单 的则跳过
				continue;
			}
	    	$url = WEB_ROOT . "admin/dispatchlist/create_update.php?index=$k&act=createPre&goodsSn={$item->goodsSn}&externalOrderSn={$item->externalOrderSn}&orderSn={$item->orderSn}&partyId={$item->partyId}&orderGoodsId={$item->orderGoodsId}&defaultDispatchPriority={$item->defaultDispatchPriority}&expectedDeliverDate=" . urlencode($item->expectedDeliverDate);
	    	//alert_back("创建工单成功，相关单号{$dispatchListId}", $url);
	    	header("refresh: 3; url=$url");
	    	echo "创建工单成功，相关单号{$dispatchListId}";
	    	die();
	    	break;
		}
	}
    // }}}
    
} elseif ($act == 'redirect') { // 跳转
    // {{{ create_update.php?act=createPre&goodsSn={$item->goodsSn}&externalOrderSn={$item->externalOrderSn}&orderSn={$item->orderSn}&partyId={$item->partyId}&orderGoodsId={$item->orderGoodsId}&defaultDispatchPriority={$item->defaultDispatchPriority}&expectedDeliverDate={$item->expectedDeliverDate|urlencode}
    $criteria = new stdClass();
	$criteria->offset = 0;
	$criteria->count = 500;
	$criteria->partyId = $_SESSION['party_id'];
    $candidates = searchDispatchCandidates($criteria);
	$candidates = addFinishedCancelledCount($candidates);
	if ($candidates) {
		
    	$index = (int) $_GET['index'];
    
		foreach ($candidates as $k => $item) {
			if ($k == $index) {
				if ($item->cancelledCount != 0) {
					$index++;
					continue;
				}
	    		$url = WEB_ROOT . "admin/dispatchlist/create_update.php?index=$index&act=createPre&goodsSn={$item->goodsSn}&externalOrderSn={$item->externalOrderSn}&orderSn={$item->orderSn}&partyId={$item->partyId}&orderGoodsId={$item->orderGoodsId}&defaultDispatchPriority={$item->defaultDispatchPriority}&expectedDeliverDate=" . urlencode($item->expectedDeliverDate);
	    		//alert_back("跳到下一个", $url);
	    		header("location: $url");
	    		break;
			}
		}
	}
    // }}}
} elseif ($act == 'updatePre') { // 更新工单的界面

    $dispatchList = getDispatchList($dispatchListId);
    $attributes = getDispatchListAttributes($dispatchListId);
    
    $dispatchList->dueDate = date("Y-m-d", strtotime($dispatchList->dueDate));
    $dispatchList->shippingDate = date("Y-m-d", strtotime($dispatchList->shippingDate));
    
    $providers = getProvidersByCat(2334);
    
    //var_dump($attributes);
    
    list($styleAttributes, $imgAttributes) = splitStyleImgAttributes($attributes, true);
    
    // 获取原图的地址
    $imgUrls = array();
    $pattern = '/^goodsImage[0-9]_original$/';
    foreach ($imgAttributes as $key => $url) {
    	if (preg_match($pattern, $key, $matches)) {
    		$imgUrls[] = $url;
    	}
    }
    
    // 以前存的工单，图片的属性不太一样
    if (!$imgUrls) {
        $pattern = '/^goodsImage[0-9]_o$/';
        foreach ($imgAttributes as $key => $url) {
            if (preg_match($pattern, $key, $matches)) {
                $imgUrls[] = $url;
            }
        }
    }
    
    $smarty->assign('imageUrls', $imgUrls);
    $smarty->assign('dispatchList', $dispatchList);
    $smarty->assign('providerName', $providers[$dispatchList->providerId]['provider_name']);
    $smarty->assign('styleAttributes', $styleAttributes);
    $smarty->assign('note', $attributes['note']);
    
    $latestPurchasePrices = getLatestPurchasePrices($dispatchList->orderGoodsId, $dispatchList->dispatchSn);
    $smarty->assign('latestPurchasePrices', $latestPurchasePrices);
    
    $smarty->assign('imgAttributes', $imgAttributes);
    $smarty->assign('attrNames', jjshouseGoodsAttributeName());

    $toAssignAttrs = array(
        'orderId', 'orderSn', 'orderGoodsId', 'externalOrderSn',
        'productId', 'goodsName', 'goodsSn',
        'dispatchPriorityId', 'expectedDeliverDate', 'partyId', 
        'imageUrl', 'externalGoodsId',
    );
    foreach ($toAssignAttrs as $toAssignAttr) {
        $smarty->assign($toAssignAttr, $dispatchList->$toAssignAttr);
    }
    
    $sql = "select o.shipping_fee as shipping_fee, o.shipping_name " .
            " from ecshop.ecs_order_info o " .
            " where order_sn = {$dispatchList->orderSn} ";
    $result = $db->getRow($sql);
    $shippingFee = $result['shipping_fee'];
    $smarty->assign('shippingFee', $shippingFee);
    $smarty->assign('shippingName', $result['shipping_name']);
    
    //var_dump(getOrderActionNote($dispatchList->orderId));
    $smarty->assign('actionNotes', getOrderActionNote($dispatchList->orderId));
    
    $smarty->assign("rush", $dispatchList->dispatchPriorityId);
    
    $act = "updateSubmit";
    $showForm = true;
    
} elseif ($act == "createPre") { // 创建工单的界面

    // 大部分属性使用candidate的就行了 
    $productId = $_REQUEST['productId'];
    $defaultDispatchPriority = $_REQUEST['defaultDispatchPriority'];
    $partyId = $_REQUEST['partyId'];
    $orderSn = $_REQUEST['orderSn'];
    $goodsSn = $_REQUEST['goodsSn'];
    $externalOrderSn = $_REQUEST['externalOrderSn'];
    $expectedDeliverDate = $_REQUEST['expectedDeliverDate'];
    // 修正java那边的返回  0002-11-30T00:00:00+08:00是不合法的
    if (strpos($expectedDeliverDate, '0002-11-30') === 0) { 
        $expectedDeliverDate = null;
    } else {
        $expectedDeliverDate = date("Y-m-d", strtotime($expectedDeliverDate));
    }
    $orderGoodsId = intval($_REQUEST['orderGoodsId']);
    
    $sql = "select * from {$ecs->table('order_goods')} where rec_id = {$orderGoodsId} ";
    $orderGoods = $db->getRow($sql);
    
    
    $sql = " select a.attr_name, ga.attr_value " .
           "  from ecs_goods_attr ga " .
           "  left join ecs_attribute a on ga.attr_id = a.attr_id " .
           " where ga.goods_id = {$orderGoods['goods_id']} ";
    $_attributes = $db->getAll($sql);
    
    $sql = "select count(*) from romeo.dispatch_list 
            where order_goods_id = '{$orderGoodsId}' and 
                  dispatch_status_id != 'CANCELLED' ";
    $dispatch_list_count = $db->getOne($sql);
    
    // 此处先判断能否创建新的工单，romeo 在创建工单的时候还会判断一次 
    if ($dispatch_list_count >= $orderGoods['goods_number']) {
        sys_msg("该订单中的 {$orderGoods['goods_name']} 不能创建新的工单了， 请刷新“未制作的工单列表”后进行操作");
    }
    
    $_texturetext = $orderGoods['goods_name'];
    
    $externalGoodsId = 0;
    
    foreach ($_attributes as $_attribute) {
        /*if ('goodsStyle_fabric' == $_attribute['attr_name']) {
            $_texturetext = $_attribute['attr_value'];
        }*/
        
        if ('goods_id' == $_attribute['attr_name']) {
            $externalGoodsId = $_attribute['attr_value'];
        }
    }
    
    if (empty($externalGoodsId) && preg_match('/g(\d+)/', $goodsSn, $matches)) {
        $externalGoodsId = $matches[1];
    }
    //获得订单邮寄地址的国家信息
    $sql = "SELECT region_name, region_id FROM ecs_order_info o 
         LEFT JOIN ecs_region r ON o.country = r.region_id
         WHERE order_sn = {$orderSn}"; 
    $result_c = $db->getRow($sql);
    $country = $result_c['region_name'];
    $region_id = $result_c['region_id'];
    $smarty->assign('country',$country);
    
    // 分析商品名，加上材质属性
    $_attributes[] = array(
        'attr_name' => 'goodsStyle_textures', 
        'attr_value' => parsejjshouseGoodsTexture($_texturetext),
        );
    
    list($styleAttributes, $imgAttributes) = splitStyleImgAttributes($_attributes);   
    
    $sql = "select o.shipping_fee as shipping_fee, postscript, shipping_name " .
            " from ecshop.ecs_order_info o " .
            " where order_sn = {$orderSn} ";
    $result = $db->getRow($sql);
    
    $shippingFee = $result['shipping_fee'];
    $note = $result['postscript'];
    $smarty->assign('shippingFee', $shippingFee);
    $smarty->assign('shippingName', $result['shipping_name']);
    $smarty->assign('note', $note);
    //var_dump($sql, $styleAttributes);
    
    $smarty->assign('orderGoods', $orderGoods);
    $smarty->assign('styleAttributes', $styleAttributes);
    $smarty->assign('imgAttributes', $imgAttributes);
    $smarty->assign('attrNames', jjshouseGoodsAttributeName());
    
    // 将第一张小图设置为工单的默认图片
    $smarty->assign('imageUrl', $imgAttributes['goodsImage0_m']);
    $smarty->assign('orderId', $orderGoods['order_id']);
    $smarty->assign('orderSn', $orderSn);
    $smarty->assign('goodsSn', $goodsSn);
    $smarty->assign('externalOrderSn', $externalOrderSn);
    $smarty->assign('orderGoodsId', $orderGoodsId);
    $smarty->assign('productId', $productId);
    $smarty->assign('goodsName', $orderGoods['goods_name']);
    $smarty->assign('partyId', $partyId);
    $smarty->assign('dispatchPriorityId', $defaultDispatchPriority);
    $smarty->assign('expectedDeliverDate', $expectedDeliverDate);
    $smarty->assign('externalGoodsId', $externalGoodsId);
    
    $smarty->assign('actionNotes', getOrderActionNote($orderGoods['order_id']));
    $smarty->assign('importantNotes', getOrderActionImportantNote($orderGoods['order_id']));
    
    $act = 'createSubmit';
    $showForm = true;
    
	// 婚期
	$important_day = $expectedDeliverDate;
	// 订单确认时间
	$sql = "SELECT action_time 
			FROM `ecs_order_action` 
			where order_id = '{$orderGoods['order_id']}' 
				and (action_note like '订单确认，%' or action_note = '自动确认订单') ";
	$action_time = $GLOBALS['db']->getOne($sql);
    
	/**
春节之后，由于工厂产能的不足，因此默认的交期会变长，但是有一部分人交了加急费，因此这些单子会优先处理，总的原则是：婚期第一，默认交期其次。算法如下：


1，平均制作周期（请定义为变量，因为后面会慢慢调整的。最好有一个界面可以设置的。）
所有的配件跟以前一样，婚纱为30天，礼服的为30天 还有配件？

2，最短制作周期（请定义为变量，因为后面会慢慢调整的。最好有一个界面可以设置的。）
配件=2天，婚纱=5天，礼服=5天 还有配件？

3，运输时间
小包=15天，快递=5天

4，所有工单的逻辑是：
a, 如果有备注的，需要手工做工单

b, 如果没有备注的，自动做工单
X=订单确认日期（注意是ERP订单确认的日期）+ 平均制作周期
Y=订单婚期-运输时间（根据顾客选的快递方式来定）
Z=订单确认日期（注意是ERP订单确认的日期）+ 最短制作周期

逻辑：
如果 Y<Z，那么需要手工做单
如果 Y>=Z，那么工单的交期就是X和Y里面更早的那个
	 */
    $average_period_of_production = array(
    	'wedding_dress' => 15,
    	'dress' => 10,
    	'accessory' => 6,
    	'default' => 15,
    );
    
    $min_period_of_production = array(
    	'wedding_dress' => 13,
    	'dress' => 8,
    	'accessory' => 6,
    	'default' => 13,
    );
	
	// {{{ 时间计算、RUSH or not
	// 俄罗斯、乌克兰只发ems，因为快递不收个人件。日本、泰国、马来西亚、韩国、朝鲜、葡萄牙、巴西、希腊发ems快递。
	// 4124 4245 4058 4234 4080 4064 4063 4120 3962 4036
	$only_ems = in_array($region_id, array(4124,4245,4058,4234,4080,4064,4063,4120,3962,4036));
	###$shipping_time = $only_ems ? 15 : 6;
	$shipping_time = $only_ems ? 15 : 5;
	$goods_id = (int) $orderGoods['goods_id'];
	$sql = "SELECT cat_id FROM ecshop.ecs_goods g
    		WHERE g.goods_id = $goods_id 
    		LIMIT 1 ";
	$cat_id = $db->getOne($sql);
	// 最短制作时间
	switch ($cat_id)
	{
		case 2335 : // jjshouse
		case 2380 : // amormoda
		case 2417 : // jenjenhouse
		case 2445 : // jennyjoseph
			// 婚纱
			#$make_min_time = 18;
			$_time_X = $average_period_of_production['wedding_dress'];
			$_time_Y = $important_day ? intval((strtotime($important_day) - strtotime($shipping_time))/(60*60*24)) : 0;
			$_time_Z = $min_period_of_production['wedding_dress'];
	
			break;
				
		case 2336 : // jjshouse
		case 2378 : // amormoda
		case 2418 : // jenjenhouse
		case 2446 : // jennyjoseph
			// 伴娘装、礼服
			#$make_min_time = 15;
			$_time_X = $average_period_of_production['dress'];
			$_time_Y = $important_day ? intval((strtotime($important_day) - strtotime($shipping_time))/(60*60*24)) : 0;
			$_time_Z = $min_period_of_production['dress'];
	
			break;
				
		case 2337 : // jjshouse
		case 2379 : // amormoda
		case 2419 : // jenjenhouse
		case 2444 : // jennyjoseph
			// 配件
			#$make_min_time = 4;
			$_time_X = $average_period_of_production['accessory'];
			$_time_Y = $important_day ? intval((strtotime($important_day) - strtotime($shipping_time))/(60*60*24)) : 0;
			$_time_Z = $min_period_of_production['accessory'];
	
			break;
				
		default :
			// 默认按最长的品类的最短制作时间
			#$make_min_time = 18;
			$_time_X = $average_period_of_production['default'];
			$_time_Y = $important_day ? intval((strtotime($important_day) - strtotime($shipping_time))/(60*60*24)) : 0;
			$_time_Z = $min_period_of_production['default'];
	
			break;
	}
	
	if ($_time_Y < $_time_Z)
	{
		echo "不能自动做工单";
		$this->log("自动创建工单失败：不能自动做工单(orderSn: {$orderSn} , goodsId: {$goods_id} , $_time_Y < $_time_Z)");
		return false;
	}
	else
	{
		$_time_Y = $_time_Y ? $_time_Y : $_time_X;
		$make_min_time = min($_time_X, $_time_Y);
	}
	
	// 制作时间
	$make_time = $make_min_time;
	$time = time();
	// 厂家交货时间
	$dueDate = date("Y-m-d", mktime(date("H", $time), date("i", $time), date("s", $time), date("m", $time), date("d", $time) + $make_min_time, date("Y", $time)));
	// 有婚期要求
	if ($important_day)
	{
		$important_day_time = strtotime($important_day);
		// 婚期 - 运输时间
		$important_day__shipping_day = date("Y-m-d", mktime(date("H", $important_day_time), date("i", $important_day_time), date("s", $important_day_time), date("m", $important_day_time), date("d", $important_day_time) - $shipping_time, date("Y", $important_day_time)));
		// 婚期
		if ($dueDate <= $important_day__shipping_day)
		{
			$smarty->assign("dueDate", $dueDate);
			$dueDate_time = strtotime($dueDate);
			$shippingDate = date("Y-m-d", mktime(date("H", $dueDate_time), date("i", $dueDate_time), date("s", $dueDate_time), date("m", $dueDate_time), date("d", $dueDate_time) + 1, date("Y", $dueDate_time)));
			$smarty->assign("shippingDate", $shippingDate);
			if ((strtotime($important_day__shipping_day) - strtotime($dueDate)) / (60 * 60 * 24) < 5)
			{
				$smarty->assign("rush", 'RUSH');
			}
			else
			{
				$smarty->assign("rush", 'NORMAL');
			}
		}
		else
		{
			$smarty->assign("rush", 'RUSH');
		}
	}
	// 没有婚期要求
	else
	{
		$smarty->assign("dueDate", $dueDate);
		$dueDate_time = strtotime($dueDate);
		$shippingDate = date("Y-m-d", mktime(date("H", $dueDate_time), date("i", $dueDate_time), date("s", $dueDate_time), date("m", $dueDate_time), date("d", $dueDate_time) + 1, date("Y", $dueDate_time)));
		$smarty->assign("shippingDate", $shippingDate);
		$smarty->assign("rush", 'NORMAL');
	}
    // }}}
    // {{{ 跳到下一个用到
    $_get_array = $_GET;
	$_get_array['index'] = isset($_get_array['index']) ? $_get_array['index'] + 1 : 1;
	$_get_array['act'] = 'redirect';
	$url_next = '?' . http_build_query($_get_array);
	$smarty->assign("url_next", $url_next);
	// }}}
}

$smarty->assign('showForm', $showForm);
$smarty->assign('act', $act);
$smarty->display('dispatchlist/create_update.htm');
