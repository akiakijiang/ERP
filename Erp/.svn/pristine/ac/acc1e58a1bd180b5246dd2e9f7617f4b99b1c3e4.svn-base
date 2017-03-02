<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once ROOT_PATH .'admin/includes/init.php';
/**
 * 淘宝快递时效性数据备份
 * 
 * @author ljzhou 2012.9.18
 * @version $Id$
 * @package application.commands
 */
class SaveTaobaoLogisticsTraceCommand extends CConsoleCommand {
	private $slave; // Slave数据库
	
	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {

        $this->run ( array ('SaveLogisticsTrace' ) );
        
	}
	
    /**
	 * 同步快递时效性数据
	 */
	public function actionSaveLogisticsTrace($appkey = null, $day = 7) {
		$startTimeTotal = microtime ( true );
		// 不启用红包数据同步的列表
		$exclude_list = array ()// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
		// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		;

        $startDate = date('Y-m-d',time()-3600*24*$day);
        $endDate = date('Y-m-d',time());
        global $db;
        $sql = "select count(*) from ecshop.ecs_taobao_logistics_trace";
		$count_start_all = $db->getOne($sql);
		// 远程服务
		$client = Yii::app ()->getComponent ( 'romeo' )->LogisticsTraceService;
		foreach ( $this->getTaobaoShopList () as $taobaoShop ) {
			$start=microtime(true);
			if (in_array ( $taobaoShop ['application_key'], $exclude_list ))
				continue;
			
			if ($appkey !== null && $appkey != $taobaoShop ['application_key'])
				continue;
			
			echo ("[" . date ( 'c' ) . "] " . $taobaoShop ['nick'] . " save logisticsTrace start \n");
			$sql = "select count(*)
                    from ecshop.ecs_taobao_logistics_trace lt
                    inner join ecshop.ecs_order_info oi ON oi.order_id = lt.order_id
                    inner join ecshop.taobao_shop_conf c ON oi.party_id = c.party_id
                    where c.application_key = '{$taobaoShop ['application_key']}'";
			$count_start = $db->getOne($sql);
			try {
				$request = array ("startDate" => $startDate,"endDate" => $endDate, "applicationKey" => $taobaoShop ['application_key'] );
				$response = $client->saveLogisticsTrace ( $request );
			} catch ( Exception $e ) {
				echo ("|  Exception: " . $e->getMessage () . "\n");
			}
			$sql = "select count(*)
                    from ecshop.ecs_taobao_logistics_trace lt
                    inner join ecshop.ecs_order_info oi ON oi.order_id = lt.order_id
                    inner join ecshop.taobao_shop_conf c ON oi.party_id = c.party_id
                    where c.application_key = '{$taobaoShop ['application_key']}'";
			$count_end = $db->getOne($sql);
			
			echo "[" . date ( 'c' ) . "] " ."本次logisticsTrace ". $taobaoShop ['nick'] ." 耗时：".(microtime(true)-$start)."备份条数：".($count_end-$count_start)."\n";
			
		}
		$sql = "select count(*) from ecshop.ecs_taobao_logistics_trace";
		$count_end_all = $db->getOne($sql);
		echo "[" . date ( 'c' ) . "] " . "本次备份 "." 耗时：".(microtime(true)-$startTimeTotal)."备份条数：".($count_end_all-$count_start_all)."\n";
	}

    /**
	 * 取得启用的淘宝店铺的列表
	 * 
	 * @return array
	 */
    protected function getTaobaoShopList()
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK'";
            $list=$this->getSlave()->createCommand($sql)->queryAll();
            $command=$this->getSlave()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
            foreach($list as $key=>$item)
            $list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
        }
        return $list;
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
