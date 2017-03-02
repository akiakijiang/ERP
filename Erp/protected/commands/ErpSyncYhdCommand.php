<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once ROOT_PATH . 'admin/includes/init.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
class ErpSyncYhdCommand extends CConsoleCommand{
	
	public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
    
    /** 同步一号店订单
     *
     * @param string $appkey 执行店铺的应用编号
     * 
     */
    public function actionSyncOrder($appkey=null,$hours=6)
    {	
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    	echo("get service");
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncYhdService;
    	echo("get service success");
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncYhdOrder start \n");
    		$start = microtime(true);
    
    		// 同步一号店订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
    			$response=$client->SyncYhdOrders($request);
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
    
   /** 转换一号店订单
     *
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
     public function actionYhdOrderTransfer($appkey=null,$days=1)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	echo("get service");
    	$client=Yii::app()->getComponent('erpsync')->SyncYhdService;
    	echo("get service success");
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." YhdOrderTransfer start \n");
			$start = microtime(true);
    
    		//转换一号店订单
    		try
    		{
    			$request=array("days"=>$days,"applicationKey"=>$taobaoShop['application_key']);
				$response=$client->YhdOrdersTransfer($request);
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
    
    public function actionSyncItemStock($appkey=null)
    {
        // 不启用库存同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		
		ini_set('default_socket_timeout', 1200);
        
        	// 远程服务
        echo("get service");
    	$client=Yii::app()->getComponent('erpsync')->SyncYhdService;
    	echo("get service success");
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
			
			if($taobaoShop['is_stock_update']!='Y')
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncYhditem start \n");
    		$start = microtime(true);
    
    		// 同步一号店库存
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncYhdGoodsStock($request);
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
     * 同步一号店商品信息
     * */
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
    	echo("get service");
    	$client=Yii::app()->getComponent('erpsync')->SyncYhdService;
    	echo("get service success");
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncyhditem start \n");
    		$start = microtime(true);
    
    		// 同步一号店库存
    		try
    		{
    			$request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncYhdProduct($request);
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
    
    /**将ERP的发货订单状态同步到一号店*/
     public function actionSyncYhdOrderSendDelivery($appkey=null, $days = 1)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	echo("get service");
    	$client=Yii::app()->getComponent('erpsync')->SyncYhdService;
    	echo("get service success");
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncYhdOrderSendDelivery start \n");
			$start = microtime(true);
    
    		//同步发货
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
				$response=$client->SyncYhdOrderSendDelivery($request);
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
    
   /** 收货确认
     * @param string $appkey 执行店铺的应用编号
     * @param float hours 
     * @param String endDate
     * 订单更新时间在endDate-hours到endDate之内
     */
     public function actionSyncYhdOrderFinished($appkey=null,$hours=1)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	echo("get service");
    	$client=Yii::app()->getComponent('erpsync')->SyncYhdService;
    	echo("get service success");
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncYhdOrderFinished start \n");
			$start = microtime(true);
    
    		//收货确认
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
				$response=$client->SyncYhdOrderFinished($request);
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
    
    
   /** 取得启用的一号店店铺
     *
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'yhd'";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
}
?>
