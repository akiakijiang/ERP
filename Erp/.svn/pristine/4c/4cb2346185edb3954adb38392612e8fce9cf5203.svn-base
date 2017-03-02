
<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2013-9-24
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncJdCommand extends CConsoleCommand
{
	
    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
    
    
     /**
     * 同步京东库存
     * 
     * @param string $appkey 执行店铺的应用编号
     */
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
    	$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
			
			if($taobaoShop['is_stock_update']!='Y')
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncjditem start \n");
    		$start = microtime(true);
    
    		// 同步京东库存
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncJdGoodsStock($request);
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
     * 同步京东商品
     *
     * @param string $appkey 执行店铺的应用编号
     * 
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
    	$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncjditem start \n");
    		$start = microtime(true);
    
    		// 同步京东库存
    		try
    		{
    			$request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncJdProduct($request);
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
     * 同步京东订单
     *
     * @param string $appkey 执行店铺的应用编号
     * 
     */
    public function actionSyncOrder($appkey=null,$hours=6  )
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncJdOrder start \n");
    		$start = microtime(true);
    
    		// 同步京东订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
    			$response=$client->SyncJdOrder($request);
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
     * 转换京东订单
     *
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
     public function actionJdOrderTransfer($appkey=null,$days=1 , $group=0)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    	foreach($this->getTaobaoShopList($group) as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." JdOrderTransfer start \n");
			$start = microtime(true);
    
    		//转换京东订单
    		try
    		{
    			$request=array("days"=>$days,"applicationKey"=>$taobaoShop['application_key']);
				$response=$client->JdOrderTransfer($request);
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
     * @param int months 订单确认时间距离当前时间几个months内
     */
     public function actionSyncJdOrderSendDelivery($appkey=null)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncJdOrderSendDelivery start \n");
			$start = microtime(true);
    
    		//同步发货
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
				$response=$client->SyncJdOrderSendDelivery($request);
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
     public function actionSyncJdOrderFinished($appkey=null,$hours=1)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncJdOrderFinished start \n");
			$start = microtime(true);
    
    		//收货确认
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
				$response=$client->SyncJdOrderFinished($request);
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
     * 调用接口获取面单号（京东限制最多100）
     */
    public function actionSyncBillCode($appkey=null,$num=100)
    {
    	// 不启用的店铺列表
		$exclude_list=array
		(
		);
		// 启用京东货到付款的京东店铺列表
		$jd_cod_list=array
		(
		'9128b5447cea10309a21003048df78e2', //ECCO男鞋_京东
		'5c9cc832ef36103189d9003048df78e2', //ECCO女鞋_京东
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncJdService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
				
			if(!in_array($taobaoShop['application_key'],$jd_cod_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncBillCode start \n");
    		
    		if(empty($taobaoShop['params']['customer_code'])){
    			echo("[".date('c')."] ".$taobaoShop['nick']." customerCode is null ! please update taobao_shop_conf \n");
    			continue;
    		}
    		$start = microtime(true);
    
    		// 同步京东订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"preNum"=>$num,"customerCode"=>$taobaoShop['params']['customer_code']);
    			$response=$client->SyncJdBillCode($request);
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
     * 取得启用的京东店铺
     *
     * @return array
     */
    protected function getTaobaoShopList($group=0)
    {
    	static $list;
    	if(!isset($list))
    	{
    		//360buy_overseas 表示京东境外店
    		$sql="select * from taobao_shop_conf where status='OK' and shop_type in ('360buy', '360buy_overseas')";
    		
    		if($group == 0 ){   	// 全部 不做处理
            }
            elseif ($group == 1) {      //国内
                echo " 国内\n";
            	$sql .= " and shop_type in ('360buy') ";
            }
            elseif ($group == 2) {      //海外
                echo " 海外\n";
            	$sql .= " and shop_type in ('360buy_overseas') ";
            }
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
    
}
?>
