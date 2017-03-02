<?php
/*
 * 乐其跨境业务组商品同步&库存同步
 * by hzhang1 2015-12-14
 */
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);

class ErpSyncKuajingCommand extends CConsoleCommand
{	  
   
   /**
	 * 更新跨境维护商品
	 * @param applicationKey  执行店铺的应用编号
	 */
    public function actionSyncKuajingProduct($appkey=null)
    {
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKuajingService;
    	foreach($this->getTaobaoShopList() as $key)
    	{
    		echo("[".date('c')."] ".$key['applicationkey']." SyncKuajingProduct start \n");
    		$start = microtime(true);
    
    		// 上传跨境购订单到跨境平台 
    		try
    		{
    			$request=array("applicationkey"=>$key['applicationkey']);
    			$response=$client->SyncLqkjProductInit($request);
    			print_r($response);
    		}
    		catch(Exception $e)
    		{
    			echo("|  Exception: ".$e->getMessage()."\n");
    		}
    
    		echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
    		usleep(500000);
    	}
    
    }
    
     
     
    /**
	 * 从跨境购平台同步订单状态到跨境购订单状态表，从而同步状态到ERP
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 同步距离当前时间的hours小时内的订单状态
	 */
     public function actionSyncKuajingGoodsStock($appkey=null)
    {    	
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKuajingService;
    	foreach($this->getTaobaoShopList() as $key)
    	{
    		echo("[".date('c')."] ".$key['applicationkey']." SyncKuajingStock start \n");
			$start = microtime(true);
    
    		//从跨境购平台同步订单状态到跨境购订单状态表，从而同步状态到ERP
    		try
    		{
    			$request=array("applicationkey"=>$key['applicationkey']);
				$response=$client->SyncLqkjGoodsStock($request);
    			print_r($response);
    		}
    		catch(Exception $e)
    		{
    			echo("|  Exception: ".$e->getMessage()."\n");
    		}
    
    		echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
    		usleep(500000);
    	}
    }
    
    /**
     * 从跨境购平台同步订单发货单号到ERP
     * @param string $appkey 执行店铺的应用编号
	 * @param int hours 同步距离当前时间的hours小时内的订单状态
	 * @param string endDate 同步的结束时间
     */
    public function actionSyncLqkjTrackingNumber($appkey=null, $hours=10, $endDate=null) {
    	ini_set('default_socket_timeout', 1200);

    	//远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKuajingService;
    	foreach($this->getTaobaoShopList() as $key) {
    		if($appkey!==null&&$appkey!=$key['applicationkey']) 
    			continue;

    		echo('[' .date('c') ."]  " .$key['applicationkey'] ."  SyncLqkjTrackingNumber start \n");
    		$start = microtime(true);
   		
    		//从跨境购平台同步发货单号到ERP
    		try{    		
    			$request=array("applicationKey"=>$key['applicationkey'],"hours"=>$hours,"endDate"=>$endDate);
    			$response = $client->SyncLqkjTrackingNumber($request);
    			print_r($response);
    		} catch(Exception $e) {
    			echo("| Exception: " .$e->getMessage() ."\n");
    		}
   		
    		echo "[" .date('c') ."] 耗时：" .(microtime(true)-$start) ."\n";
    		usleep(500000);
    	}
    } 
    
       
    
    /**
     * 取得启用的需要上传订单到跨境购平台的店铺
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		//$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'kuajinggou'";
    		$sql="select distinct applicationkey from ecshop.kuajing_bird_product";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand($sql);
    	}
    	return $list;
    }
}
?>
