<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
set_include_path(get_include_path() . PATH_SEPARATOR . ROOT_PATH . PATH_SEPARATOR . ROOT_PATH . 'RomeoApi/');
require_once ROOT_PATH . 'admin/includes/init.php';
include_once ROOT_PATH . 'admin/includes/lib_order.php';
include_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'admin/includes/lib_common.php';
require_once ROOT_PATH . 'admin/includes/lib_order_mixed_status.php';

class R {
	private static $_data = array();
	public static function set($key, $value) { self::$_data[$key] = $value; }
	public static function get($key) { return isset(self::$_data[$key]) ? self::$_data[$key] : null; }
}

// R::set('shopapi_client', $shopapi_client);
// R::set('session', $_SESSION);

$GLOBALS['shopapi_client'] = $shopapi_client;
// $GLOBALS['_SESSION'] = $_SESSION;
$_SESSION['admin_name'] = 'webService';

Yii::import('application.commands.LockedCommand', true);

error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

/**
 * 自动确认订单
 * 
 * @author Zandy
 *
 */
class AutoConfirmOrderCommand extends LockedCommand
{

	// jjshouse amormoda jenjenhouse jennyjoseph amormoda2
	private static $_partyIds = array(65545, 65554, 65564, 65567, 65570);
	
	// 每次检查多少条订单
	private static $_limit = 100;

	/**
	 * 当不指定ActionName时的默认调用
	 * 
	 * export LC_ALL="zh_CN.UTF-8" &&  php /var/www/http/erp/protected/yiic AutoConfirmOrder >> /var/log/update_ecshop/cron_AutoConfirmOrder.$(date +\%Y\%m\%d)
	 */
	public function actionIndex()
	{
		echo "start autoConfirmOrder in actionIndex " . date("Y-md- H:i:s") . "\n";
		$this->autoConfirmOrder();
		echo "start autoConfirmOrder in actionIndex " . date("Y-md- H:i:s") . "\n\n";
	}
	
	/**
	 * 供 actionIndex 调用
	 * @param int $partyId
	 * 
	 * 
CREATE TABLE IF NOT EXISTS `auto_confirm_order` (
  `order_id` int(11) NOT NULL DEFAULT '0',
  `order_sn` varchar(100) NOT NULL DEFAULT '',
  `taobao_order_sn` varchar(100) NOT NULL DEFAULT '',
  `confirmable` varchar(3) NOT NULL DEFAULT '' COMMENT 'YES or NO or empty',
  `ctime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `utime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `order_sn` (`order_sn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='自动确认的订单信息';
	 */
	protected function autoConfirmOrder()
	{
		$partyIds = join(", ", self::$_partyIds);
		if (!$partyIds) {
			return false;
		}
		
		// 已付款、未确认、未发货
		$sql = "
				SELECT order_id, order_sn, taobao_order_sn
				FROM ecshop.ecs_order_info a 
				WHERE order_status = 0 AND pay_status = 2 AND shipping_status = 0 AND party_id IN ($partyIds)
					AND NOT EXISTS(SELECT 1 FROM ecshop.auto_confirm_order b WHERE b.order_sn = a.order_sn AND confirmable = 'NO' LIMIT 1)
				LIMIT " . self::$_limit . "
			";
		$yiidb = Yii::app()->getDb();
		$order_list = $yiidb->createCommand($sql)->queryAll();
		
		self::log("\$order_list number: " . sizeof($order_list));
		
		foreach ($order_list as $order)
		{
			$order_id = $order['order_id'];
			
			$confirmable = $this->confirmable($order_id);
			self::log("{$order['order_sn']} confirmable is " . ($confirmable ? 'true' : 'false'));
			if ($confirmable) {
				$this->confirmIt($order_id);
				$sql = "
						INSERT IGNORE INTO ecshop.auto_confirm_order 
							(order_id, order_sn, taobao_order_sn, confirmable, ctime)
						VALUES 
							('$order_id', '{$order['order_sn']}', '{$order['taobao_order_sn']}', 'YES', NOW())
						ON DUPLICATE KEY UPDATE confirmable = 'YES'
					";
				$yiidb->createCommand($sql)->execute();
			} else {
				$sql = "
						INSERT IGNORE INTO ecshop.auto_confirm_order 
							(order_id, order_sn, taobao_order_sn, confirmable, ctime)
						VALUES 
							('$order_id', '{$order['order_sn']}', '{$order['taobao_order_sn']}', 'NO', NOW())
						ON DUPLICATE KEY UPDATE confirmable = 'NO'
					";
				$yiidb->createCommand($sql)->execute();
			}
		}
	}
	
	protected function confirmable($order_id)
	{
		$yiidb = Yii::app()->getDb();
		
		$order = getOrderInfo($order_id);
		
		$all_status = array();
		$pre_status = null;
		foreach ($order['actions'] as $key => $v) {
			$last_action = $v['action_note'];
			$order_shipping_pay = $v['order_status'] . '_' . $v['pay_status'] . '_' . $v['shipping_status'];
			if ($pre_status != $order_shipping_pay)
				$n .= "_";
			$all_status[$order_shipping_pay . $n][] = $v;
			$pre_status = $order_shipping_pay;
		}
		$order['actions'] = $all_status;
		
		$order_attrs = get_order_attribute_list($order_id, null);
		$order_attributes = array();
		if ($order_attrs) {
			foreach ($order_attrs as $attr_name => $attr_value) {
				$order_attributes[$attr_name] = $attr_value[0]['attr_value'];
			}
		}
		
		/*
		国家（英美加澳等）

3859	United States	美国
3858	United Kingdom	英国
3844	Canada	加拿大
3835	Australia	澳大利亚

3937	Austria	奥地利
4203	Switzerland	瑞士
4003	France	法国

4202	Sweden	瑞典
3987	Denmark	丹麦
4017	Germany	德国
4108	Norway	挪威

3859, 3858, 3844, 3835, 3937, 4203, 4003, 4202, 3987, 4017, 4108

		订单金额（<$400）
		地址（google地图上没有问题，不包含PO BOX或者Armed Forces字眼）
		电话（不为空，或者设置位数在5位数以上）
		送货方式（加急或者标准）
		留言（无）
		交期（>=系统可选的latest arrival date） 或者按照钟琪说的婚期-today也可以
		ERP右侧订单操作信息无客服备注
		商品不包括服务费这个特殊商品
		*/
		$auto_confirm_order = array(
				'国家（英美加澳等）' => 0,
				'订单金额（<$400）' => 0,
				'地址（google地图上没有问题，不包含PO BOX或者Armed Forces字眼）' => 0,
				'电话（不为空，或者设置位数在5位数以上）' => 0,
				'送货方式（加急或者标准）' => 0,
				'无留言' => 0,
				'婚期-today不小于9、14、19天' => 0,
				//'ERP右侧订单操作信息无客服备注' => 1,
				'商品不含服务类' => 1, 
				'没有退款过' => 1, 
		);
		// 国家（英美加澳等）
		if (in_array($order['country'], array(3859, 3858, 3844, 3835, 3937, 4203, 4003, 4202, 3987, 4017, 4108))) {
			$auto_confirm_order['国家（英美加澳等）'] = 1;
		}
		// 订单金额（<$400）
		if ($order_attributes['order_amount'] > 0 && $order_attributes['order_amount'] < 400) {
			$auto_confirm_order['订单金额（<$400）'] = 1;
		}
		// 电话（不为空，或者设置位数在5位数以上）
		if (strlen($order['tel']) > 5) {
			$auto_confirm_order['电话（不为空，或者设置位数在5位数以上）'] = 1;
		}
		// 送货方式（加急或者标准）
		if (in_array($order_attributes['sm_id'], array(1, 2))) {
			$auto_confirm_order['送货方式（加急或者标准）'] = 1;
		}
		// 留言（无）
		if (!$order['postscript']) {
			$auto_confirm_order['无留言'] = 1;
		}
		// 交期（>=系统可选的latest arrival date） 或者按照钟琪说的婚期-today也可以
		$mindays = getOrderMindays($order['goods_list']);
		if ($mindays > 0 && (strtotime($order_attributes['important_day']) - strtotime(date("Y-m-d", time()))) >= 60*60*24*$mindays) {
			$auto_confirm_order['婚期-today不小于9、14、19天'] = 1;
		}
		// ERP右侧订单操作信息无客服备注
		/*
		if ($order['actions']) {
			foreach ($order['actions'] as $_k1 => $_v1) {
				foreach ($_v1 as $_k2 => $act) {
					if ($act['action_user'] != 'webService') {
						$auto_confirm_order['ERP右侧订单操作信息无客服备注'] = 0;
						break 2;
					}
				}
			}
		}
		*/
		// 商品不包括服务费这个特殊商品
		foreach($order['goods_list'] as $_k => $_goods) {
			if (stripos($_goods['uniq_sku'], 'c34g') !== false) {
				$auto_confirm_order['商品不含服务类'] = 0;
				break;
			}
		}
		
		// 没有退款过
		$sql = "SELECT 1
			   FROM ecshop.ecs_order_info oi
			   LEFT JOIN romeo.party p ON convert(oi.party_id using utf8) = p.party_id
			   LEFT JOIN romeo.refund r ON convert(oi.order_id using utf8) = r.order_id
			   WHERE r.status = 'RFND_STTS_EXECUTED' and p.parent_party_id = '65542' and p.party_id != '65560'
					AND oi.email = '" . mysql_real_escape_string($order['email']) . "'
				LIMIT 1";
		$auto_confirm_order['没有退款过'] = $yiidb->createCommand($sql)->queryColumn() ? 0 : 1;
		
		// {{{ 地址（google地图上没有问题，不包含PO BOX或者Armed Forces字眼）
		if (sizeof($auto_confirm_order) == array_sum($auto_confirm_order) + 1)
		{
			// 其他都通过了才执行这个google map api的查询（每天2500次限制），免得浪费
			$order_address = array();
			$order_address['address'] = $order['address'];
			$order_address['district_text'] = $order_attributes['district_text'] ? $order_attributes['district_text'] : $order['district_name'];
			$order_address['city_text'] = $order_attributes['city_text'] ? $order_attributes['city_text'] : $order['city_name'];
			$order_address['province_text'] = $order_attributes['province_text'] ? $order_attributes['province_text'] : $order['province_name'];
			$order_address['zipcode'] = $order['zipcode'];
			$order_address['country_name'] = $order['country_name'] ? $order['country_name'] : $order_attributes['country_text'];
			$order_address_string = join(" ", $order_address);
			if (stripos($order_address_string, 'PO BOX') === false && stripos($order_address_string, 'Armed Forces') === false) {
				$google_map_url = "http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($order_address_string) . "&sensor=true";
				// Create a stream
				$opts = array(
						'http' => array(
								'method' => "GET",
								'header' => "Accept-language: en\r\n" . "Cookie: foo=bar\r\n" . "User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.19 (KHTML, like Gecko) Chrome/18.0.1025.168 Safari/535.19\r\n",
								'timeout' => 10
						)
				);
				$context = stream_context_create($opts);
				// Open the file using the HTTP headers set above
				$map_json = json_decode(file_get_contents($google_map_url, false, $context), true);
				if ($map_json && isset($map_json['status'])) {
					if ($map_json && isset($map_json['status']) && $map_json['status'] == 'OK') {
						$auto_confirm_order['地址（google地图上没有问题，不包含PO BOX或者Armed Forces字眼）'] = 1;
					} elseif ($map_json['status'] == 'OVER_QUERY_LIMIT') {
						// 使用次数超过2500次，发个邮件？
						send_mail("Zandy", "yzhang@i9i8.com", "Google Map OVER_QUERY_LIMIT<br>order sn: {$order['order_sn']}", "Google Map OVER_QUERY_LIMIT<br>order sn: {$order['order_sn']}", true);
					}
				}
			}
		} else {
			$auto_confirm_order['地址（google地图上没有问题，不包含PO BOX或者Armed Forces字眼）'] = -1;
		}
		// }}}
		$_auto_confirm_order = false;
		// 上面一共 9 个条件
		#if (sizeof($auto_confirm_order) == 9 && array_sum($auto_confirm_order) == 9
		if (sizeof($auto_confirm_order) == array_sum($auto_confirm_order))
		{
			// 排除水龙头
			// echo '可以自动确认订单';
			$_auto_confirm_order = true;
		}
		else
		{
			echo sizeof($auto_confirm_order) . "!= " . array_sum($auto_confirm_order) . "\n";
		}
		
		if (!$_auto_confirm_order)
		{
			print_r($auto_confirm_order);
		}
		
		return $_auto_confirm_order;
	}
	
	protected function confirmIt($order_id)
	{
		//$shopapi_client = R::get('shopapi_client');
		//$session = R::get('session');
		//$GLOBALS['_SESSION'] = (is_array($GLOBALS['_SESSION']) ? $GLOBALS['_SESSION'] : array()) + $session;
		//v($shopapi_client, $GLOBALS['_SESSION'], gettype($GLOBALS['_SESSION']));
		
		//global $shopapi_client, $_SESSION;
		//$_SESSION['admin_name'] = 'computer';
		//v($_SESSION);die();
		global $shopapi_client;
		
		// {{{ 执行确认订单
		$api_order = $shopapi_client->getOrderById($order_id);
		$api_order->orderStatus = 1;
		$api_order->confirmTime = strtotime(date("Y-m-d H:i:s"));  // 确认订单时间
		$action_user = $_SESSION['admin_name'];
		$api_order->actionUser = $action_user;
		$api_order->actionNote = "自动确认订单";
		
		echo "confirmIt($order_id) start\n";
		//p($api_order, $shopapi_client, $_SESSION);
		$shopapi_client->updateOrder($api_order);
		
		update_order_mixed_status($order_id, array('order_status' => 'confirmed'), 'worker', "自动确认订单");

		echo "confirmIt($order_id) end\n";
		return true;
		// }}}
	}

	protected static function log($m = "")
	{
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
	}

}