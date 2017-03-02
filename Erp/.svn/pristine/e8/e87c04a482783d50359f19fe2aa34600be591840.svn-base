<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once ROOT_PATH .'admin/includes/init.php';
/**
 * 取消订单未还原预订订单将其还原预订
 * 
 * @author ljzhou 2013.03.09
 * @version $Id$
 * @package application.commands
 */
class FinishCancelOrderRestoreAtpCommand extends CConsoleCommand {
	private $slave; // Slave数据库
	
	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {

        $this->run ( array ('FinishCancelOrderRestoreAtp' ) );
        
	}
	
	/**
	 * 取消订单未还原预订订单将其还原预订
	 */
	public function actionFinishCancelOrderRestoreAtp() {
		$start_time = microtime ( true );
		echo ("[" . date ( 'c' ) . "] "  . " FinishCancelOrderRestoreAtp start \n");
		
		$sql = "select 
                             oi.order_id 
                from         ecshop.ecs_order_info oi 
                left join    romeo.order_inv_reserved oir ON convert(oi.order_id using utf8) = oir.order_id
                where 
                             oi.order_status = 2 and oir.order_inv_reserved_id is not null
                ";
		$order_ids = $this->getSlave ()->createCommand ( $sql )->queryAll ();
		
		foreach ( $order_ids as $order_id ) {
			echo "[" . date ( 'c' ) . "] " . "FinishCancelOrderRestoreAtp  " . " order_id：" . $order_id['order_id'] . "\n";
			try {
				$handle = soap_get_client ( "InventoryService" );
				$handle->cancelOrderInventoryReservation ( array ('orderId' => $order_id['order_id'] ) );
			} catch ( Exception $e ) {
				echo "[" . date ( 'c' ) . "] " . "FinishCancelOrderRestoreAtp exception " . $e . " order_id：" . $order_id['order_id'] ."\n";
			}
		}
		echo "[" . date ( 'c' ) . "] " . "FinishCancelOrderRestoreAtp  " . " cost：" . (microtime ( true ) - $start_time) . " order_count：" . (count($order_ids)) . "\n";
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
