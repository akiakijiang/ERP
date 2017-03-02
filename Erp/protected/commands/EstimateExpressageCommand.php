<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
include_once ROOT_PATH . 'admin/function.php';
 
 
/**
 * @author wjzhu@i9i8.com
 * @copyright Copyright &copy; 2012 leqee.com
 */

class EstimateExpressageCommand extends CConsoleCommand {
    private $master; // Master数据库    
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {
        $this->run ( array ('EstimateExpressage' ) );
    }
    
    /**
     * 检查淘宝物流，调用发送邮件的函数
     */
    public function actionEstimateExpressage($days=3) {
    	$start = microtime(true);
        $shipment_count = 0;
        $start_time = time()-3600*24*$days; 
        $use_cache = true;
        $use_all_party = true;
        if ($use_all_party) {
        	$party_sql = "1";
        } else {
        	$party_sql = "oi.party_id in (65558)";
        }
        $sql = "
        		SELECT 		oi.party_id as party_id,
        					s.shipment_id as shipment_id,
        					'' as status
        		FROM		ecshop.ecs_order_info oi
        		INNER JOIN 	romeo.order_shipment os on convert(oi.order_id using utf8) = os.order_id
        		INNER JOIN 	romeo.shipment s on os.shipment_id = s.shipment_id
        		WHERE		oi.shipping_status in (1, 2, 3, 11)
                AND         {$party_sql} 
        		AND			oi.order_type_id IN ('SALE', 'RMA_EXCHANGE', 'SHIP_ONLY', 'SUPPLIER_SALE')
        	    AND 		oi.shipping_time >= {$start_time} 
        	    AND			s.shipping_category = 'SHIPPING_SEND'
        	    AND 		(oi.facility_id = '79256821' or (s.shipping_leqee_weight > 0 AND s.shipping_leqee_weight is not null))
        	    AND 		not exists (select 1 from romeo.shipment_estimated_expressage_history e where  s.shipment_id = e.shipment_id )
        	    GROUP BY	oi.party_id, s.shipment_id
        		LIMIT 10000
        ";		
        $sql2 = "
        		select party_id, shipment_id, status from romeo.shipment_estimated_expressage_history where status = 'UPDATE' order by last_update_stamp asc limit 10000
        		";
        ;
        $shipment_list = $this->getMaster()->createCommand ($sql)->queryAll();
        $shipment_list2 = $this->getMaster()->createCommand ($sql2)->queryAll();
        $shipment_list = array_merge($shipment_list,$shipment_list2);
	    foreach ($shipment_list as $shipment) {
	    	if ($shipment['shipment_id']== '88804557') {
	    		var_dump($shipment);
	    		print "88804557\n";
	    	}
			try {
				$response=Yii::app()->getComponent('romeo')->ShipmentService->getEstimatedExpressage(
		    		array('shipmentId'=>$shipment['shipment_id'], 'useCache'=>$use_cache)
		    	);
		    	$code = $response->return->code;
		    	$msg = $response->return->msg;
		    	if ($code == "SUCCEED") {
		    		$shipment_count++;
		    		$result = $response->return->result;
		    		echo "[" . date('c'). "] ". $shipment['shipment_id']. "预估快递费 succeed" . $result . " \n";
		    	} else {
		    		echo "[" . date('c'). "] ". $shipment['shipment_id']. "预估快递费 fail" . $msg . " \n";
		    	}
		    	
		    	if ($shipment['status']) {
		    		$this->update_estimated_expressage_history($shipment['party_id'], $shipment['shipment_id'], $code, $msg);
		    	} else {
		    		$this->add_estimated_expressage_history($shipment['party_id'], $shipment['shipment_id'], $code, $msg);
		    	}
			} catch (Exception $e) {
				echo "[" . date('c'). "] ". $shipment['shipment_id']. "预估失败:" . $e->getMessage()."\n";
			}
		}
        
        echo "[". date('c'). "]预估快递费：共" . count($shipment_list) . " 成功" . $shipment_count . " 耗时：".(microtime(true)-$start)."\n";
    }
        
    
    /**
     * 取得启用的淘宝店铺的列表
     * 
     * @return array
     */
    protected function getTaobaoShopList() {
        static $list;
        if (! isset ( $list )) {
            $sql = "select * from taobao_shop_conf where status='OK'";
            $list = $this->getMaster ()->createCommand ( $sql )->queryAll ();
            $command = $this->getMaster ()->createCommand ( "select * from taobao_api_params where taobao_api_params_id=:id" );
            foreach ( $list as $key => $item )
                $list [$key] ['params'] = $command->bindValue ( ':id', $item ['taobao_api_params_id'] )->queryRow ();
        }
        return $list;
    }
    
    /**
     * 取得master数据库连接
     * 
     * @return CDbConnection
     */
    protected function getMaster() {
        if (! $this->master) {
            $this->master = Yii::app ()->getDb ();
            $this->master->setActive ( true );
        }
        return $this->master;
    } 
    
      
    /**
     * 记录预估快递费计算历史
     */
    protected function add_estimated_expressage_history($party_id, $shipment_id, $status, $msg) {
    	$db = $this->getMaster();
    	$builder = $db->getCommandBuilder();
        $data = array(
            'party_id' => $party_id,
            'shipment_id' => $shipment_id,
            'status' => $status,
            'msg' => $msg,
            'created_stamp' => date("Y-m-d H:i:s"),
            'last_update_stamp' => date("Y-m-d H:i:s")
        );
	    $table = $db->getSchema()->getTable('romeo.shipment_estimated_expressage_history');
	    $builder->createInsertCommand($table, $data)->execute();
    }
    
    /**
     * 修改预估快递费计算记录
     */
    
    protected function update_estimated_expressage_history($party_id, $shipment_id, $status, $msg) {
    	$sql = "update romeo.shipment_estimated_expressage_history " .
    			"set status = '{$status}', msg = '{$msg}', last_update_stamp = now() " .
    			"where shipment_id = '{$shipment_id}' ";
    	$this->getMaster()->createCommand($sql)->execute();
    }
}
