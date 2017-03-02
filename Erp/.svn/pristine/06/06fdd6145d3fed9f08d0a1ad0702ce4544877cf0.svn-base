<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
set_include_path(get_include_path() . PATH_SEPARATOR . ROOT_PATH . PATH_SEPARATOR . ROOT_PATH . 'RomeoApi/');
require_once ROOT_PATH . 'admin/includes/init.php';
include_once ROOT_PATH . 'RomeoApi/lib_dispatchlist.php';
include_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'admin/includes/lib_common.php';

Yii::import('application.commands.LockedCommand', true);

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

/**
 * 自动制作工单
 * 
 * @author Zandy
 * 
 * 

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


另外订单确认一小时后才开始自动做工单

 *
 */
class DispatchListCommand extends LockedCommand
{
	static private $start_time = 0;

	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex()
	{
		self::$start_time = time();
		
		echo "start actionIndex " . date("Y-md- H:i:s");
		$partyIds = array(65545, 65554, 65564, 65567, 65570);
		foreach ($partyIds as $partyId)
		{
			echo "start actionIndex " . date("Y-md- H:i:s") . " partyId: $partyId";
			$this->autoDispatch($partyId);
			echo "start actionIndex " . date("Y-md- H:i:s") . " partyId: $partyId";
		}
		echo "start actionIndex " . date("Y-md- H:i:s");
	}
	
	/**
	 * 供 actionIndex 调用
	 * @param int $partyId
	 */
	public function autoDispatch($partyId = 65545)
	{
		self::$start_time = self::$start_time > 0 ? self::$start_time : time();
		
		$criteria = new stdClass();
		$criteria->offset = 0;
		$criteria->count = 2000;
		$criteria->partyId = $partyId;
		
		//var_dump($criteria);
		$candidates = searchDispatchCandidates($criteria);
		$candidates = addFinishedCancelledCount($candidates);
		
		foreach ($candidates as $item)
		{
			echo date("Y-m-d H:i:s") . "\r\n";
			if ($item->cancelledCount != 0)
			{
				echo $item->orderSn . ' - $item->cancelledCount != 0' . "\r\n";
				continue;
			}
			$orderId = (int) $item->orderId;
			// 订单确认时间
			$sql = "SELECT action_time 
					FROM `ecs_order_action` 
					where order_id = $orderId 
						and (action_note like '订单确认，%' or action_note = '自动确认订单') ";
			$action_time = $GLOBALS['db']->getOne($sql);
			// 确认后1个小时才能制作
			if ($action_time > '2011' && ((time() - strtotime($action_time)) > 60 * 30))
			{
				$params = "goodsSn={$item->goodsSn}&externalOrderSn={$item->externalOrderSn}&orderSn={$item->orderSn}&partyId={$item->partyId}&orderGoodsId={$item->orderGoodsId}&dispatchPriorityId={$item->defaultDispatchPriority}&expectedDeliverDate=" . urlencode($item->expectedDeliverDate) . "&action_time=" . urlencode($action_time);
				print_r($params . "\r\n");
				parse_str($params, $row);
				$this->createPre($row);
				
				if (time() - self::$start_time > 600) {
					break;
				}
			}
			else
			{
				echo '一小时内不能制作。$action_time: ' . $action_time . "\r\n";
			}
		}
	}

	function createPre($row)
	{
		$db = Yii::app()->getDb();
		
		#$reader = $db->createCommand("select @party_id=:party")->bindValue(':party_id', 65545)->query();
		

		// 大部分属性使用candidate的就行了 
		$productId = $row['productId'];
		$row['productId'] = null;
		$defaultDispatchPriority = $row['dispatchPriorityId'];
		$partyId = $row['partyId'];
		$orderSn = $row['orderSn'];
		$goodsSn = $row['goodsSn'];
		$externalOrderSn = $row['externalOrderSn'];
		$expectedDeliverDate = $row['expectedDeliverDate'];
		// 修正java那边的返回  0002-11-30T00:00:00+08:00是不合法的
		if (strpos($expectedDeliverDate, '0002-11-30') === 0)
		{
			$expectedDeliverDate = null;
		}
		else
		{
			$expectedDeliverDate = date("Y-m-d", strtotime($expectedDeliverDate));
		}
		$row['expectedDeliverDate'] = $expectedDeliverDate;
		
		$orderGoodsId = intval($row['orderGoodsId']);
		
		$action_time = $row['action_time'];
		
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
		
		// 婚期
		$important_day = $expectedDeliverDate;
		
		$sql = "select * from ecs_order_goods where rec_id = {$orderGoodsId} ";
		$orderGoods = $db->createCommand($sql)->queryRow();
		
		$sql = "select a.attr_name, ga.attr_value 
				from ecs_goods_attr ga 
					left join ecs_attribute a on ga.attr_id = a.attr_id 
				where ga.goods_id = {$orderGoods['goods_id']} ";
		$_attributes = $db->createCommand($sql)->queryAll();
		
		$sql = "select count(*) from romeo.dispatch_list 
            	where order_goods_id = '{$orderGoodsId}' 
					and dispatch_status_id != 'CANCELLED' ";
		$dispatch_list_count = $db->createCommand($sql)->queryColumn();
		$dispatch_list_count = $dispatch_list_count[0];
		
		// 此处先判断能否创建新的工单，romeo 在创建工单的时候还会判断一次 
		if ($dispatch_list_count >= $orderGoods['goods_number'])
		{
			$this->log("该订单中的 {$orderGoods['goods_name']} 不能创建新的工单了， 请刷新“未制作的工单列表”后进行操作");
			return false;
		}
		
		$_texturetext = $orderGoods['goods_name'];
		
		$externalGoodsId = 0;
		
		foreach ($_attributes as $_attribute)
		{
			if ('goods_id' == $_attribute['attr_name'])
			{
				$externalGoodsId = $_attribute['attr_value'];
			}
		}
		
		if (empty($externalGoodsId) && preg_match('/g(\d+)/', $goodsSn, $matches))
		{
			$externalGoodsId = $matches[1];
		}
		$row['externalGoodsId'] = $externalGoodsId;
		//获得订单邮寄地址的国家信息
		$sql = "SELECT region_name, region_id 
				FROM ecs_order_info o 
        			LEFT JOIN ecs_region r ON o.country = r.region_id
        		WHERE order_sn = '{$orderSn}' ";
		$result_c = $db->createCommand($sql)->queryRow();
		$country = $result_c['region_name'];
		$region_id = $result_c['region_id'];
		
		// 分析商品名，加上材质属性
		$_attributes[] = array(
			'attr_name' => 'goodsStyle_textures', 
			'attr_value' => parsejjshouseGoodsTexture($_texturetext)
		);
		
		list($styleAttributes, $imgAttributes) = splitStyleImgAttributes($_attributes);
		
		$sql = "select o.shipping_fee as shipping_fee, postscript, shipping_name from ecshop.ecs_order_info o where order_sn = '{$orderSn}' ";
		$result = $db->createCommand($sql)->queryRow();
		
		$shippingFee = $result['shipping_fee'];
		$note = $result['postscript'];
		$shippingName = $result['shipping_name'];
		$attrNames = jjshouseGoodsAttributeName();
		
		// 将第一张小图设置为工单的默认图片
		$imageUrl = $imgAttributes['goodsImage0_m'] ? $imgAttributes['goodsImage0_m'] : $imgAttributes['goodsImage1_m'];
		$row['imageUrl'] = $imageUrl;
		$orderId = $orderGoods['order_id'];
		$row['orderId'] = $orderId;
		$goodsName = $orderGoods['goods_name'];
		$row['goodsName'] = $goodsName;
		$getOrderActionNote = getOrderActionNote($orderGoods['order_id'], " AND action_user != 'webService' ");
		$importantNotes = getOrderActionImportantNote($orderGoods['order_id']);
		
		$note .= $importantNotes ? ' ' . $importantNotes : '';
		
		$attributes = array();
		foreach ($attrNames as $description => $attrKey)
		{
			if ($attrKey)
			{
				$attributes["goodsStyle_{$attrKey}"] = $styleAttributes[$attrKey];
			}
		}
		$attributes['note'] = $note;
		$attributes = $attributes + $imgAttributes;
		
		// {{{ 时间计算、RUSH or not
		// 俄罗斯、乌克兰只发ems，因为快递不收个人件。日本、泰国、马来西亚、韩国、朝鲜、葡萄牙、巴西、希腊发ems快递。
		// 4124 4245 4058 4234 4080 4064 4063 4120 3962 4036
		$only_ems = in_array($region_id, array(
			4124, 
			4245, 
			4058, 
			4234, 
			4080, 
			4064, 
			4063, 
			4120, 
			3962, 
			4036
		));
		###$shipping_time = $only_ems ? 15 : 6;
		$shipping_time = $only_ems ? 15 : 5;
		$goods_id = (int) $orderGoods['goods_id'];
		$sql = "SELECT cat_id 
				FROM ecshop.ecs_goods g
    			WHERE g.goods_id = $goods_id 
    			LIMIT 1 ";
		$cat_id = $db->createCommand($sql)->queryColumn();
		$cat_id = $cat_id[0];
		
		// 最短制作时间
		switch ($cat_id)
		{
			case 2335 : // jjshouse
			case 2380 : // amormoda
			case 2417 : // jenjenhouse
			case 2445 : // jennyjoseph
			case 2460 : // amormoda2
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
			case 2461 : // amormoda2
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
			case 2462 : // amormoda2
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

		// {{{ 制作周期跟网站保持一致
		$sql = "SELECT g.cat_id, uniq_sku, c.cat_id, c.config
				FROM ecshop.ecs_goods g
					left join ecshop.category c on g.uniq_sku like concat('%c', c.cat_id, 'g%')
				WHERE g.goods_id = $goods_id
				LIMIT 1";
		$row = $db->createCommand($sql)->queryRow(); 
		$config = json_decode($row['config'], true);
		$_time_Z = $config['min'];
		// }}}
		
		if ($_time_Y < $_time_Z)
		{
			echo "不能自动做工单";
			$this->log("自动创建工单失败：不能自动做工单[orderSn: {$orderSn} , goodsId: {$goods_id} , \$_time_Y($_time_Y) < \$_time_Z($_time_Z)]");
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
		$shippingDate = null;
		// 有婚期要求
		if ($important_day)
		{
			$important_day_time = strtotime($important_day);
			// 婚期 - 运输时间
			$important_day__shipping_day = date("Y-m-d", mktime(date("H", $important_day_time), date("i", $important_day_time), date("s", $important_day_time), date("m", $important_day_time), date("d", $important_day_time) - $shipping_time, date("Y", $important_day_time)));
			// 婚期
			if ($dueDate <= $important_day__shipping_day)
			{
				$dueDate_time = strtotime($dueDate);
				$shippingDate = date("Y-m-d", mktime(date("H", $dueDate_time), date("i", $dueDate_time), date("s", $dueDate_time), date("m", $dueDate_time), date("d", $dueDate_time) + 1, date("Y", $dueDate_time)));
				if ((strtotime($important_day__shipping_day) - strtotime($dueDate)) / (60 * 60 * 24) < 5)
				{
					$rush = 'RUSH';
				}
				else
				{
					$rush = 'NORMAL';
				}
			}
			else
			{
				$rush = 'RUSH';
			}
		}
		// 没有婚期要求
		else
		{
			$dueDate_time = strtotime($dueDate);
			$shippingDate = date("Y-m-d", mktime(date("H", $dueDate_time), date("i", $dueDate_time), date("s", $dueDate_time), date("m", $dueDate_time), date("d", $dueDate_time) + 1, date("Y", $dueDate_time)));
			$rush = 'NORMAL';
		}
		// }}}
		$row['shippingDate'] = $shippingDate;
		$row['dueDate'] = $dueDate;
		$row['dispatchPriorityId'] = $rush;
		
		$dispatchList = new stdClass();
		foreach ($row as $key => $value)
		{
			$dispatchList->$key = $value;
		}
		$dispatchList->currency = 'RMB';
		
		// $dispatchList->submitDate = date("Y-m-d H:i:s");
		$dispatchList->providerId = 0;
		$dispatchList->price = 0;
		
		// {{{
		// 如果有备注则不能自动
		$autoit = true;
		$confirm_string = '订单确认，';
		foreach ($getOrderActionNote as $k => $v)
		{
			foreach ($v as $kk => $vv)
			{
				//if ($vv['action_note'] != '' && $vv['action_note'] != $confirm_string)
			    // 每个订单都有“批量收款”这个备注了 2012-01-02 
				if ($vv['action_note'] != '' && $vv['action_note'] != $confirm_string && $vv['action_note'] != '批量收款' && $vv['action_note'] != '自动确认订单')
				{
					$autoit = false;
					break 2;
				}
			}
		}
		if (!$autoit)
		{
			$this->log("自动创建工单失败：除了“订单确认，”、“批量收款”、“自动确认订单”外还有其他备注信息(orderSn: {$orderSn} , goodsId: {$goods_id})");
			return false;
		}
		if (!$dispatchList->shippingDate)
		{
			$this->log("自动创建工单失败：不能计算出正确的发货时间(orderSn: {$orderSn} , goodsId: {$goods_id})");
			return false;
		}
		// }}}
		

		#print_r($dispatchList);
		#print_r($getOrderActionNote);
		#die();
		

		try
		{
			$dispatchListId = createDispatchList($dispatchList, $attributes);
		}
		catch (SoapFault $e)
		{
			$this->log("自动创建工单失败：" . $e->faultstring . "(orderSn: {$orderSn} , goodsId: {$goods_id})");
			return false;
		}
		
		var_dump($dispatchListId);
		echo "\r\n";
		$this->log("自动创建工单成功：" . $dispatchListId . "(orderSn: {$orderSn} , goodsId: {$goods_id})");
		return $dispatchListId;
	}

	public function log($m)
	{
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
	}

}