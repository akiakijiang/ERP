<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2016-2-18
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
class ErpSyncKaolaCommand extends CConsoleCommand{
	
	public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
      
      
     /**
     * 同步网易考拉商品库存
     * 
     * @param string $appkey 执行店铺的应用编号
     */
     public function actionSyncKaolaGoodsStock($appkey=null)
    {
        // 不启用库存同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		
		ini_set('default_socket_timeout', 1200);
        
        // 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKaolaService;//标记
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
			
			if($taobaoShop['is_stock_update']!='Y')
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." synckaolaitem start \n");
    		$start = microtime(true);
    
    		// 同步网易考拉商品库存
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncKaolaGoodsStock($request);//标记
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
     * 同步网易考拉商品
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
    	$client=Yii::app()->getComponent('erpsync')->SyncKaolaService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncKaolaitem start \n");
    		$start = microtime(true);
    
    		// 同步网易考拉商品
    		try
    		{
    			$request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncKaolaProduct($request);//标记
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
     * 同步网易考拉订单
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
    public function actionKaolaSyncOrder($appkey=null,$hours=8)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKaolaService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncKaolaOrder start \n");
    		$start = microtime(true);
    
    		// 同步网易考拉订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
    			$response=$client->SyncKaolaOrder($request);//标记
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
     * 转换网易考拉订单
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
     public function actionKaolaOrderTransfer($appkey=null,$days=1)
    {      	 		
    	$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKaolaService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." KaolaOrderTransfer start \n");
			$start = microtime(true);
    
    		//转换网易考拉订单
    		try
    		{
    			$request=array("days"=>$days,"applicationKey"=>$taobaoShop['application_key']);
				$response=$client->KaolaOrderTransfer($request);//标记
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
     * 网易考拉同步发货
     * @param string $appkey 执行店铺的应用编号
     */
     public function actionSyncKaolaOrderSendDelivery($appkey=null)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKaolaService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncKaolaOrderSendDelivery start \n");
			$start = microtime(true);
    
    		//网易考拉同步发货
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
				$response=$client->SyncKaolaOrderSendDelivery($request);//标记
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
     * 取得启用的网易考拉店铺
     *
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		$sql="select * from taobao_shop_conf where status='OK' and shop_type ='kaola'";//标记
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
    
}
?>
