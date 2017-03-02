<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
/**
 * 淘宝红包数据备份
 * 
 * @author ljzhou 2012.9.18
 * @version $Id$
 * @package application.commands
 */
class SaveTaobaoTradeAmountCommand extends CConsoleCommand {
	private $slave; // Slave数据库
	
	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex() {

        $this->run ( array ('SaveTradeAmount' ) );
        
	}
	
	/**
	 * 同步红包数据   该调度线上已注释 update by qyyao 2015-12-29
	 */
	public function actionSaveTradeAmount($appkey = null, $day = 1) {
		$startTimeTotal = microtime ( true );
		// 不启用红包数据同步的列表
		$exclude_list = array ()// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
		// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		;
		
        $startDate = date('Y-m-d',time()-3600*24*$day);
        $endDate = date('Y-m-d',time());
        
		// 远程服务
		$client = Yii::app ()->getComponent ( 'romeo' )->TradeAmountService;
		foreach ( $this->getTaobaoShopList () as $taobaoShop ) {
			$start=microtime(true);
			if (in_array ( $taobaoShop ['application_key'], $exclude_list ))
				continue;
			
			if ($appkey !== null && $appkey != $taobaoShop ['application_key'])
				continue;
			
			echo ("[" . date ( 'c' ) . "] " . $taobaoShop ['nick'] . " save tradeAmount start \n");
			
			try {
				$request = array ("startDate" => $startDate,"endDate" => $endDate, "applicationKey" => $taobaoShop ['application_key'] );
				$response = $client->saveTradeAmount ( $request );
			} catch ( Exception $e ) {
				echo ("|  Exception: " . $e->getMessage () . "\n");
			}
			echo "本次tradeAmount ". $taobaoShop ['nick'] ." 耗时：".(microtime(true)-$start)."\n";
			
		}
		echo "[" . date ( 'c' ) . "] " . "本次SaveTradeAmount备份 "." 耗时：".(microtime(true)-$startTimeTotal)."\n";
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
