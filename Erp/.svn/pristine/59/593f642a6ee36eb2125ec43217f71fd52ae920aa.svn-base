<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2015-08-07
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncSuningCommand extends CConsoleCommand
{
	
    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
      
      
     /* 同步苏宁海外购库存
     * 
     * @param string $appkey 执行店铺的应用编号
     */
    public function actionSyncSuningGoodsStock($appkey=null)
    {
        // 不启用库存同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
				// '8596c6f8a24a103289d9003048df78e2' // 康贝官方旗舰店-苏宁店
		);
		
		ini_set('default_socket_timeout', 1200);
        
        // 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncSuningService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
			
			if($taobaoShop['is_stock_update']!='Y')
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncSuningGoodsStock start \n");
    		$start = microtime(true);
    
    		// 同步苏宁海外购库存
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncSuningGoodsStock($request);
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
     * 同步苏宁海外购商品
     * @param string $appkey 执行店铺的应用编号
     */
    public function actionSyncItem($appkey=null,$hours=70)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncSuningService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncsuningitem start \n");
    		$start = microtime(true);
    
    		// 同步蜜芽宝贝库存
    		try
    		{
    			$request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncSuningProduct($request);
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
     * 同步
     * 取得启用苏宁网订单
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
    public function actionSuningSyncOrder($appkey=null,$hours=8)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncSuningService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncSuningOrder start \n");
    		$start = microtime(true);
    
    		// 同步口袋通订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
    			$response=$client->SyncSuningOrder($request);
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
     * 转换苏宁订单
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
     public function actionSuningOrderTransfer($appkey=null,$days=1)
    {      	 		
    	$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncSuningService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SuningOrderTransfer start \n");
			$start = microtime(true);
    
    		//转换苏宁订单
    		try
    		{
    			$request=array("days"=>$days,"applicationKey"=>$taobaoShop['application_key']);
				$response=$client->SuningOrderTransfer($request);
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
     * 同步发货
     * @param string $appkey 执行店铺的应用编号
     */
     public function actionSyncSuningOrderSendDelivery($appkey=null)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncSuningService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncSuningOrderSendDelivery start \n");
			$start = microtime(true);
    
    		//同步发货
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
				$response=$client->SyncSuningOrderSendDelivery($request);
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
     * 收货确认
     * @param string $appkey 执行店铺的应用编号
     * @param float hours 
     * @param String endDate
     * 订单更新时间在endDate-hours到endDate之内
     */
     public function actionSyncSuningOrderFinished($appkey=null,$hours=72)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncSuningService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncSuningOrderFinished start \n");
			$start = microtime(true);
    
    		//收货确认
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
				$response=$client->SyncSuningOrderFinished($request);
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
     * 取得启用的苏宁店铺
     *
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		$sql="select * from taobao_shop_conf where status='OK' and shop_type ='suning'";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }    
}
?>
