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
class ErpSyncTaobaoFenxiaoCommand extends CConsoleCommand
{
	
	private $slave;  // Slave数据库

    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
    
        /**
	 * 当不指定ActionName时的默认调用
	 */
    public function actionIndex()
    {
        $currentTime=microtime(true);

        // 商品同步
        if($currentTime-$this->getLastExecuteTime('SyncItem')>=$this->betweenSyncItem) {
            $this->run(array('SyncItem','--hours='.$this->betweenSyncItem));    
        }
        
        // 库存同步
        if($currentTime-$this->getLastExecuteTime('SyncItemStock')>=$this->betweenSyncItemStock)
        {
            // 同步库存前先更新商品
            $this->run(array('SyncItem','--seconds='.$this->betweenSyncItem));
            $this->run(array('SyncItemStock','--seconds='.$this->betweenSyncItemStock));
        }
        
        // 同步订单
        if($currentTime-$this->getLastExecuteTime('SyncOrder')>=$this->betweenSyncOrder) {
        	$this->run(array('SyncOrder'));
        }
    }
    
    
    /**
	 * 同步淘宝分销上的商品
	 *
	 * @param string  $appkey
	 * @param integer $seconds
	 */
    public function actionSyncTaobaoFenxiaoItem($appkey=null)
    {
    	// 不启用同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            	continue;
            
            $sql_stock = "select status from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
            $status = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
            if($status != 'OK'){
            	echo "[". date('c') ."]" . " SyncTaobaoFenxiaoItem  店铺:".$taobaoShop['nick']."取消同步\n";
            	continue;
            }

            echo("[".date('c')."] ".$taobaoShop['nick']." sync item start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key']);
                print_r($request);
                $response=$client->SyncTaobaoFenxiaoItem($request);
               // $response=$client->SyncTaobaoItem('f1cfc3f7859f47fa8e7c150c2be35bfc');
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
	 * 同步淘宝分销订单
	 */
    public function actionSyncTaobaoFenxiaoOrder($appkey=null,$hours=1)
    {
    	
    	// 不启用同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            	continue;
            
            $sql_stock = "select status from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
            $status = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
            if($status != 'OK'){
            	echo "[". date('c') ."]" . " SyncTaobaoFenxiaoOrder  店铺:".$taobaoShop['nick']."取消同步\n";
            	continue;
            }

            echo("[".date('c')."] ".$taobaoShop['nick']." sync fenxiaoorder start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
                print_r($request);
                $response=$client->SyncTaobaoFenxiaoOrder($request);
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
	 * 转换淘宝分销订单
	 */
    public function actionTaobaoFenxiaoOrderTransfer($appkey=null,$days=1)
    {
    	
    	// 不启用同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            	continue;
            
            $sql_stock = "select status from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
            $status = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
            if($status != 'OK'){
            	echo "[". date('c') ."]" . " SyncTaobaoFenxiaoOrderTransfer  店铺:".$taobaoShop['nick']."取消同步\n";
            	continue;
            }

            echo("[".date('c')."] ".$taobaoShop['nick']." sync TaobaoFenxiaoOrderTransfer start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
                print_r($request);
                $response=$client->TaobaoFenxiaoOrderTransfer($request);
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
	 * 同步淘宝分销 经销订单
	 */
    public function actionSyncTaobaoFenxiaoDealerOrder($appkey=null,$hours=3)
    {
    	
    	// 不启用同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
           		continue;
            
            $sql_stock = "select status from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
            $status = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
            if($status != 'OK'){
            	echo "[". date('c') ."]" . " SyncTaobaoFenxiaoDealerOrder  店铺:".$taobaoShop['nick']."取消同步\n";
            	continue;
            }

            echo("[".date('c')."] ".$taobaoShop['nick']." sync fenxiaodealerorder start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
                print_r($request);
                $response=$client->SyncTaobaoFenxiaoDealerOrder($request);
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
     * 转换淘宝经销订单
     */
    public function actionTaobaoFenxiaoDealerOrderTransfer($appkey=null,$days=1)
    {
    	 
    	// 不启用同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
    
    		if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
    			continue;
    		
    		$sql_stock = "select status from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
    		$status = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
    		if($status != 'OK'){
    			echo "[". date('c') ."]" . " SyncTaobaoFenxiaoDealerOrderTransfer  店铺:".$taobaoShop['nick']."取消同步\n";
    			continue;
    		}
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." sync TaobaoFenxiaoOrderTransfer start \n");
    		$start = microtime(true);
    		// 同步生成订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
    			print_r($request);
    			$response=$client->TaobaoFenxiaoDealerOrderTransfer($request);
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
     * 同步淘宝经销完成订单
     */
    public function actionTaobaoFenxiaoDealerOrderFinished($appkey=null,$hours=3)
    {
    
    	// 不启用同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
    
    		if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
    			continue;
    		
    		$sql_stock = "select status from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
    		$status = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
    		if($status != 'OK'){
    			echo "[". date('c') ."]" . " SyncTaobaoFenxiaoDealerOrderFinished  店铺:".$taobaoShop['nick']."取消同步\n";
    			continue;
    		}
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." sync SyncFenxiaoDealerOrderFinished start \n");
    		$start = microtime(true);
    		// 同步生成订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
    			print_r($request);
    			$response=$client->SyncFenxiaoDealerOrderFinished($request);
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
      * 同步淘宝经销完成订单
     */
    public function actionTaobaoFenxiaoOrderFinished($appkey=null,$hours=3,$endDate=null)
    {
    
    	// 不启用同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
    	
    	if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
     	echo("[".date('c')."] "." sync Fenxiao order Finished start endDate:".$endDate."\n");
     	
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
    
    		if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
    			continue;
    		
    		$sql_stock = "select status from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
    		$status = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
    		if($status != 'OK'){
    			echo "[". date('c') ."]" . " SyncFenxiaoOrderFinished  店铺:".$taobaoShop['nick']."取消同步\n";
    			continue;
    		}
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." sync SyncFenxiaoDealerOrderFinished start \n");
    		$start = microtime(true);
    		// 同步生成订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate);
    			print_r($request);
    			$response=$client->syncFenxiaoFinishedOrders($request);
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
     * 同步淘宝分销库存
     * 
     * @param string $appkey 执行店铺的应用编号
     * @param integer $seconds 修改时间
     */
    public function actionSyncTaobaoFenxiaoItemStock($appkey=null,$seconds=null)
    {
    	// 不启用商品同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
        
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
           		continue;
            
            $sql_stock = "select is_stock_update from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
            $is_stock_update = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
            if($is_stock_update != 'OK'){
            	echo "[". date('c') ."]" . " SyncFenxiaoItemStock  店铺:".$taobaoShop['nick']."取消同步\n";
            	continue;
            }
            echo("[".date('c')."] ".$taobaoShop['nick']." sync fenxiaoitemstock start \n");
            $start = microtime(true);
            
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'], "days"=>30, "mode"=>"taobao");
                $response=$client->SyncTaobaoFenxiaoItemStock($request);
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
     * 同步淘宝分销库存（聚石塔）
     *
     * @param string $appkey 执行店铺的应用编号
     */
    public function actionSyncTaobaoFenxiaoItemStockFromJushita($appkey=null)
    {
    	// 不启用商品同步的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
    	ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
    
    		if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
    			continue;
    
    		$sql_stock = "select is_stock_update from ecshop.taobao_fenxiao_shop_conf where taobao_fenxiao_shop_conf_id = '". $taobaoShop['taobao_shop_conf_id']."'";
    		$is_stock_update = Yii::app()->getDb()->createCommand($sql_stock)->queryScalar();
    		if($is_stock_update != 'OK'){
    			echo "[". date('c') ."]" . " SyncFenxiaoItemStock  店铺:".$taobaoShop['nick']."取消同步\n";
    			continue;
    		}
    		echo("[".date('c')."] ".$taobaoShop['nick']." sync fenxiaoitemstockfromjushita start \n");
    		$start = microtime(true);
    
    		// 同步生成订单
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncTaobaoFenxiaoItemStockFromJushita($request);
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
     * 淘宝分销订单数量校验
     */
    public function actionCheckTaobaoFenxiaoOrderConsistency($appkey=null)
    {
    	 
    	 
    	//不启用淘宝分销订单数量校验的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);

    
    	$endTime = date("Y-m-d H:m:s",time() - 30*60);
    	$beginTime = date("Y-m-d H:m:s",strtotime($endTime) - 60*60*3 - 30*60);
    	 
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncTaobaoFenxiaoService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
    		if(in_array($taobaoShop['application_key'],$exclude_list))
    			continue;
    
    		if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
    			continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." CheckTaobaoFenxiaoOrderConsistency start \n");
    		$start = microtime(true);
    		echo "beginTime=" . $beginTime . " endTime=" . $endTime . "\n";
    		// 淘宝分销订单数量校验
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"beginTime"=>$beginTime,"endTime"=>$endTime);
    			$response=$client->CheckTaobaoFenxiaoOrderConsistency($request);
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
     * 测试
     * 
     * @param string $appkey 执行店铺的应用编号
     * @param integer $seconds 修改时间
     */
    public function actionSyncTest()  
    {
    	$client=Yii::app()->getComponent('erpsync')->testService;
    	
       try
            {                 
                $response=$client->testTransaction();
           
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }    	
    }  
 
    /**
     * 取得启用的淘宝店铺
     *
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		$sql="select tsc.* from taobao_shop_conf tsc
				  	inner join taobao_fenxiao_shop_conf tfsc on tfsc.taobao_fenxiao_shop_conf_id=tsc.taobao_shop_conf_id
    				where tsc.status='OK' and tsc.shop_type = 'taobao'";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
}
?>
