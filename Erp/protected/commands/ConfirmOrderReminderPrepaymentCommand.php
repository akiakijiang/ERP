<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once ROOT_PATH .'admin/includes/init.php';
require_once ROOT_PATH .'admin/distribution.inc.php';
require_once ROOT_PATH .'RomeoApi/lib_payment.php';


/**
 * 确认订单时，分销预存款不足的订单不让确认
 * 
 * @author ljzhou 2013.03.04
 * @version $Id$
 * @package application.commands
 */
class ConfirmOrderReminderPrepaymentCommand extends CConsoleCommand {
	private $slave; // Slave数据库
	
	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {

        $this->run ( array ('ConfirmOrderReminderPrepay' ) );
        
	}
	
	/**
	 * 给预存款足够的订单做标记
	 * 
	 * @return array
	 */
	public function actionConfirmOrderReminderPrepay() {
		$start_time = microtime(true);
		// 电教或金佰利分销订单 要抵扣预存款
		$sql = "
                select 
                     oi.party_id, md.main_distributor_id
                from ecshop.ecs_order_info oi
                left join ecshop.distributor d ON oi.distributor_id = d.distributor_id
                left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id
                where 
                    oi.party_id in(16,65548,65558) and oi.order_type_id = 'SALE' and oi.order_status = 0 
                    and pay_status = 2 and oi.shipping_status =0 and md.type='fenxiao'
                group by oi.party_id,md.main_distributor_id";

		$party_distributors = $this->getSlave ()->createCommand ( $sql )->queryAll ();
		
		echo "[" . date ( 'c' ) . "] " . "confirmOrderReminderPrepayment start\n";
		
		foreach ( $party_distributors as $party_distributor ) {

			echo "[" . date ( 'c' ) . "] " . "confirmOrderReminderPrepayment main_distirbutor_id:" . $party_distributor ['main_distributor_id'] . ' party_id:' . $party_distributor ['party_id'] ."\n";

			// 得到该组织,供应商对应的预存款预警值，预存款可用数
			$available = prepay_get_available_amount ( $party_distributor ['main_distributor_id'], $party_distributor ['party_id'], 'DISTRIBUTOR' );
			if ($available === false) {
				echo "[" . date ( 'c' ) . "] " . '不存在该分销商的预付款账户，请通知财务解决 mian_distirbutor_id:' . $party_distributor ['main_distributor_id'] . ' party_id:' . $party_distributor ['party_id'] ."\n";
				continue;
			}
			
			// 得到从已确认订单到未发货之间订单的将要扣的预存款总额
			$will_amount = $this->prepay_get_will_amount( $party_distributor ['main_distributor_id'], $party_distributor ['party_id'] );
			
			echo "[" . date ( 'c' ) . "] main_distirbutor_id:" . $party_distributor ['main_distributor_id'] . ' party_id:' . $party_distributor ['party_id'] . " available_before:" . $available . ' will_amount:' . $will_amount ."\n";

			$available = $available - $will_amount;
			
			echo "[" . date ( 'c' ) . "] main_distirbutor_id:" . $party_distributor ['main_distributor_id'] . ' party_id:' . $party_distributor ['party_id'] . " available_after:" . $available ."\n";
			
			// 更新当前可用预存款值,更新数据库用master
			global $db;
			$sql = "update romeo.prepayment_account set available_amount = '{$available}' where supplier_id = '{$party_distributor ['main_distributor_id']}' and party_id = '{$party_distributor ['party_id']}' limit 1";
			
			echo "[" . date ( 'c' ) . "] main_distirbutor_id:" . $party_distributor ['main_distributor_id'] . ' party_id:' . $party_distributor ['party_id'] . " sql:" . $sql ."\n";
			
			$db->query($sql);
		}
		echo "[" . date ( 'c' ) . "] " . "confirmOrderReminderPrepayment end cost :" . (microtime ( true ) - $start_time) . "\n";
	}
	
	/**
	 *  得到从已确认订单到未发货之间订单的将要扣的预存款总额
	 * 
	 * @return array
	 */
	function prepay_get_will_amount($main_distributor_id, $party_id) {
		// 电教或金佰利分销订单 要抵扣预存款
		$sql = "
                select 
                      oi.order_id,md.main_distributor_id, md.name, md.type, oi.order_id, oi.order_sn, oi.order_time, oi.order_status, oi.shipping_status, oi.pay_status, 
                      oi.order_type_id,oi.goods_amount, oi.bonus, oi.distributor_id, oi.party_id, oi.province, oi.shipping_id, oi.taobao_order_sn  
                from ecshop.ecs_order_info oi
                left join ecshop.distributor d ON oi.distributor_id = d.distributor_id
                left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id 
                where 
                    oi.party_id = '{$party_id}' and oi.order_type_id = 'SALE' 
                    and oi.order_status = 1 and oi.pay_status = 2 and oi.shipping_status in(0,8,9,10,12,13) and md.type='fenxiao'
                    and md.main_distributor_id = '{$main_distributor_id}'
                ";
		$orders = $this->getSlave ()->createCommand ( $sql )->queryAll ();	
			
		$amount = 0;
		foreach ( $orders as $order ) {
			// 得到分销的销售订单的抵扣预付款
			$adjust = distribution_get_edu_adjust_confirm_order ( $order );
			$amount = $amount + $adjust;
			
			echo "[" . date ( 'c' ) . "] main_distirbutor_id:" . $main_distributor_id . ' party_id:' . $party_id . " order_id:" . $order['order_id'] . " adjust:" . $adjust . ' amount:' . $amount ."\n";
		}
		return $amount;
	}
	
    /**
	 * 取得slave数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getSlave()
    {
        if(!$this->slave)
        {
            if(($this->slave=Yii::app()->getComponent('slave'))===null)
            $this->slave=Yii::app()->getDb();
            $this->slave->setActive(true);
        }
        return $this->slave;
    }
	
}
