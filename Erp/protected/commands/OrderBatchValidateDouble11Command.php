<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
include_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once (ROOT_PATH . 'includes/helper/array.php');
include_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
Yii :: import('application.commands.LockedCommand', true);
/*
 * Created on 2013-2-23
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class OrderBatchValidateDouble11Command extends CConsoleCommand {

	private $master; // Master数据库    

	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {
		//生成出库指示数据
		$this->run(array (
			'Validate'
		));
	}

	/**
	* 创建出库指示
	*/
	public function actionValidate($partyId = null, $seconds = null) {
		ini_set('max_execution_time', '0');
		
		$lock_name = "party_";
	    $lock_file_name = $this->get_file_lock_path($lock_name, 'lock_for_order_batch_validate');
	    
		$fp = fopen($lock_file_name,'a');
		$would_block = false;
		$locked = false;
		if(!flock($fp,LOCK_EX|LOCK_NB,$would_block))
		{
			$this->log("前一批批量确认订单未结束");
			fclose($fp);
			return;
		}
		try {
		$start = microtime(true);
		$count = 0;
		$success_count = 0;
		$fail_count = 0;
		$this->log("OrderBatchValidate begin ");
		// 不启用自动确认的列表
		$exclude_list = array ();
		// 违禁品快递
		$contraband_shipping = get_contraband_shipping();
		foreach ($this->getPartyList() as $party) {
			if (in_array($party['party_id'], $exclude_list))
				continue;

			if ($partyId !== null && $partyId != $party['party_id'])
				continue;

			$this->log($party['party_id'] . " " . $party['party_name'] . " ready go ");
			$party_start = microtime(true);
			$party_count = 0;
			$party_success_count = 0;
			$party_fail_count = 0;
			// 上海仓不发圆通和韵达
			$condition = " AND ( o.facility_id not in('19568549', '3633071', '22143846', '22143847', '24196974') or o.shipping_id not in('85','100') ) ";
			
			$party_ary = array('16','65558','65569','65581','65571','65547');
			if (in_array($party['party_id'], $party_ary)) {
				$condition .= " AND md.type ='zhixiao' ";
			}
			// 增加过滤条件 已付款，待配货才能批量确认 ，配件添加，品牌特卖 ，员工自提，活动提醒，ecco不能超卖， ljzhou 2012.11.9
			$sql = "
				    select 
			        o.*,pr.region_name as province_name,cr.region_name as city_name,dr.region_name as district_name
			        from `ecshop`.`ecs_order_info` as o 
			        left join `ecshop`.`ecs_order_goods` og ON o.order_id = og.order_id
			        left join `ecshop`.`order_attribute` as oa on o.order_id = oa.order_id
			        left join `ecshop`.`order_attribute` as oa1 on o.order_id = oa1.order_id and oa1.attr_name = 'CANNOT_BATCH_VALIDATE_DOUBLE11' and oa1.attr_value = '-1111'
			        left join `ecshop`.`order_attribute` as oa2 on o.order_id = oa2.order_id and oa2.attr_name = 'BATCH_VALIDATE_DOUBLE11'
			        left join `ecshop`.`ecs_region` as pr on pr.region_id = o.province   
			        left join `ecshop`.`ecs_region` as cr on cr.region_id = o.city 
			        left join `ecshop`.`ecs_region` as dr on dr.region_id = o.district
			        LEFT JOIN ecshop.distributor d ON d.distributor_id = o.distributor_id
			        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
			        where o.order_time >= '2013-10-17' and o.order_time < '2013-11-15' and 
			        o.order_type_id = 'sale' {$condition} 
			        and o.party_id = {$party['party_id']}
			        and o.province != 0 and o.city != 0 and o.address != '' 
			        and o.order_status = 0 and o.pay_status = 2 and o.shipping_status = 0
			        and o.shipping_id != '86'
			        and oa1.attr_name is null 
			        and oa2.attr_name is null 
			        -- 排除赠品和品牌特卖
			        and not exists (select * from ecshop.ecs_order_goods ogg where (ogg.goods_name LIKE '%品牌特卖%') and ogg.order_id = o.order_id limit 1)
					-- 除了ecco,金宝贝外，其余都可以超卖
					and 
					(
					  o.party_id not in('65562','65574')
					  or (o.party_id in('65562','65574') and
						     not EXISTS
						     (
				              SELECT im.available_to_reserved,og3.goods_number FROM ecshop.ecs_order_info oi2
						         left join ecshop.ecs_order_goods og3 ON oi2.order_id = og3.order_id
						         left join romeo.product_mapping pm ON pm.ecs_goods_id = og3.goods_id and pm.ecs_style_id = og3.style_id
						         left join romeo.inventory_summary im ON pm.product_id = im.product_id and oi2.facility_id = im.facility_id
						         where im.status_id = 'INV_STTS_AVAILABLE' and oi2.order_id  = o.order_id
				               group by og3.rec_id 
				               having im.available_to_reserved < og3.goods_number
				               limit 1
				              )
			              )
			         )
			         
			         -- 没有退款信息
			         and
			         (
			         	not EXISTS 
			         	(
			         		SELECT order_id 
			         		FROM ecshop.taobao_refund
			         		WHERE status !='CLOSED'
			         		and order_id = o.order_id
			         	)
			         )
			         -- 要求没有违禁品对应的快递
			         and
			         (
			         	not EXISTS 
			         	(
			         		SELECT 1 
			         		FROM ecshop.ecs_order_goods og 
			         		LEFT JOIN ecshop.ecs_goods g ON og.goods_id = g.goods_id
			         		WHERE 
			         		og.order_id = o.order_id and o.shipping_id " . db_create_in($contraband_shipping) . " and g.is_contraband = 1
			         		limit 1
			         	)
			         )
			        group by o.order_id
			        order by o.order_time asc 
			        ";
			$orders = $this->getMaster()->createCommand($sql)->queryAll();
			$party_query_time = (microtime(true) - $party_start);
			
			$party_count = count($orders);
			$message = '';
			$shippings = getShippingTypes();
			$result = array ();
			if (!empty ($orders)) {
				foreach ($orders as $order) {
					// 批量确认
					global $db;
					$can_sure_order = true;
//					$erp_price_sql = "select sum(goods_price * goods_number) p FROM ecshop.ecs_order_goods WHERE order_id = " . $order['order_id'];
//					$erp_price = $db->getAll($erp_price_sql);
//					$erp_price = floatval($erp_price[0][p]);
//					$taobao_price = floatval($order['goods_amount']);
					//if ($order['order_type_id'] != 'SALE' || is_jjshouse($order['party_id'])) {
					//	$can_sure_order = true;
					//}
					// 邮政小包 > 3.3kg 则不让确认  ljzhou 2013.03.14
					$can_sure_xiaobao = true;
					if (isset ($order['shipping_id'])) {
						if ($order['shipping_id'] == '119') {
							$order_new = getOrderInfo($order['order_id']);
							$package_weight = get_package_weight($order_new);
							$order_weight = get_order_weight($order_new);
							$total_weight = $package_weight + $order_weight;
							if ($total_weight > 3300) {
								$can_sure_xiaobao = false;
							}
						}
					}

					if ($order['order_status'] != 0 || !$can_sure_order) {
						$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order['order_id']}, 'BATCH_VALIDATE_DOUBLE11', '-1')");
						$this->log("order_id {$order['order_id']} FAIL");
						$party_fail_count++;
					} else
						if (!$can_sure_xiaobao) {
							$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order['order_id']}, 'BATCH_VALIDATE_DOUBLE11', '-2')");
							$this->log("order_id {$order['order_id']} XIAOBAO 33KG FAIL");
							$party_fail_count++;
						} else {
							$this->updateOrder($order['order_id']);
							update_order_mixed_status($order['order_id'], array ('order_status' => 'confirmed'), 'worker', '双十一批量确认订单');
							$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order['order_id']}, 'BATCH_VALIDATE_DOUBLE11', '1')");
							$this->log("order_id {$order['order_id']} SUCCESS");
							$party_success_count++;
						}
				}
			}
			$this->log($party['party_id'] . " " . $party['party_name'] . " game over ! time: " . (microtime(true) - $party_start) . " party_query_time: " . $party_query_time . " party_count: " . $party_count . " party_success_count: " . $party_success_count . " party_fail_count: " . $party_fail_count);
			$count += $party_count;
			$success_count += $party_success_count;
			$fail_count += $party_fail_count;
			
		}
		$this->log("OrderBatchValidate end time：" . (microtime(true) - $start)  . " count: " . $count . " success_count: " . $success_count . " fail_count: " . $fail_count);
		} catch (Exception $e){
			$this->log("OrderBatchValidate error ");
			var_dump($e->getMessage());
		}
		flock($fp,LOCK_UN);
		fclose($fp);

	}
	
	/**
	 * 获得文件锁路径
	 *
	 * @param string $file_name
	 * @return string
	 */
	protected function get_file_lock_path($file_name = '', $namespace = null) {
		if (!defined('ROOT_PATH')) {
			define('ROOT_PATH', preg_replace('/admin(.*)/', '', str_replace('\\', '/', __FILE__)));
		}
	    if ($namespace == null) {
	    	preg_match('/\/([^\/]*)\.php/', $_SERVER['SCRIPT_URL'], $matches);
	        $namespace = $matches[1];
	    }
		return ROOT_PATH. "admin/filelock/{$namespace}.{$file_name}";
	}

	/**
	 * 取得master数据库连接
	 * 
	 * @return CDbConnection
	 */
	protected function getMaster() {
		if (!$this->master) {
			$this->master = Yii :: app()->getDb();
			$this->master->setActive(true);
		}
		return $this->master;
	}

	private function log($m) {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
	}


	/**
	* 取得启用同步的组织列表
	* 
	* @return array
	*/
	protected function getPartyList() {
		static $list;
		if (!isset ($list)) {
			$sql = "select distinct p.party_id, p.name party_name from taobao_shop_conf c 
			            inner join romeo.party p on c.party_id = p.party_id 
			            where c.status='OK' and c.party_id not in (65597,65569,65539) ";
			$list = $this->getMaster()->createCommand($sql)->queryAll();
		}
		return $list;
	}
	
	function updateOrder($orderId) {
		global $db;
		$db->query("update ecshop.ecs_order_info set order_status = 1,confirm_time = unix_timestamp(now()) where order_id = " . $orderId);
		$sql = "insert into ecshop.ecs_order_action 
					(order_id, action_user, order_status, shipping_status, pay_status, action_time, action_note,invoice_status,shortage_status,note_type)
				values 
				  ({$orderId}, 'System', 1, 0, 2, now(), '双十一批量确认订单', 0, 0, '')
				";
		$db->query($sql);
	}
}
?>
