<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2015-07-01
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncLqCommand extends CConsoleCommand
{
	
    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
    
      
    
   /**
     * 同步乐其微商城订单（by hzhang1 2015-06-30）
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
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
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncLqService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncLqOrder start \n");
    		$start = microtime(true);
    
    		// 同步乐其订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
    			$response=$client->SyncLqOrder($request);
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
     * 转换乐其订单（by hzhang1 2015-06-30）
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
     public function actionLqOrderTransfer($appkey=null,$days=1)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncLqService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." LqOrderTransfer start \n");
			$start = microtime(true);
    
    		//转换乐其订单
    		try
    		{
    			$request=array("days"=>$days,"applicationKey"=>$taobaoShop['application_key']);
				$response=$client->LqOrderTransfer($request);
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
     * 同步发货（by hzhang1 2015-06-30）
     * @param string $appkey 执行店铺的应用编号
     */
     public function actionSyncLqOrderSendDelivery($appkey=null)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncLqService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncLqOrderSendDelivery start \n");
			$start = microtime(true);
    
    		//同步发货
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
				$response=$client->SyncLqOrderSendDelivery($request);
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
     * 收货确认（by hzhang1 2015-06-30）
     * @param string $appkey 执行店铺的应用编号
     * @param float hours 
     */
     public function actionSyncLqOrderFinished($appkey=null,$hours=1)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncLqService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncLqOrderFinished start \n");
			$start = microtime(true);
    
    		//收货确认
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
				$response=$client->SyncLqOrderFinished($request);
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
     * 取得启用的乐其店铺
     *
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'wxleqee'";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
    
    
    
}
?>
