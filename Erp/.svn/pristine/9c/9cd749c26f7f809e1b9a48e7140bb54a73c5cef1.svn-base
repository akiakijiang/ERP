<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2013-9-23
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncTaobaoCommand extends CConsoleCommand
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
	 * 同步淘宝上的商品
	 *
	 * @param string  $appkey
	 * @param integer $seconds
	 */
    public function actionSyncTaobaoItem($appkey=null,$group=0)
    {
            // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 600);
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync item start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key']);
                print_r($request);
                $response=$client->SyncTaobaoItem($request);
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
	 * 同步淘宝直销订单
	 */
    public function actionSyncTaobaoOrder($appkey=null,$hours=1,$group=0,$endDate=null)
    {
    	
            // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
      echo("[".date('c')."] "." sync order start endDate:".$endDate."\n");
        
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync order start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate);
                print_r($request);
                $response=$client->SyncTaobaoOrder($request);
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
	 * 订单转换(只转换7天内的订单)
	 */
    public function actionTaobaoOrderTransfer($appkey=null,$group=0,$days=15)
    {
            // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 1200);
        
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync ordertransfer start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
                print_r($request);
                $response=$client->TaobaoOrderTransfer($request);
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
	 * 订单转换(只转换7天内的订单)
	 */
    public function actionTaobaoOrderMutilDistributorTransfer($appkey=null,$group=0,$days=1)
    {
            // 不启用商品同步的列表
        $exclude_list=array
        (
       
        );
        ini_set('default_socket_timeout', 1200);
        
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
        foreach($this->getCunTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync ordertransfer start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
                print_r($request);
                $response=$client->TaobaoOrderMutilDistributorTransfer($request);
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
     * 收货确认  根据同步下来的订单信息，将
     * 
     * @param string $appkey 执行店铺的应用编号
     * @param float $hours 多少小时内 订单时间
     * @param float $group 店铺分组
     * @param float $hours 截止日期
     */
    
    public function actionSyncFinishedOrder($appkey=null,$hours=1,$group=0,$endDate=null){
    	  // 不启用商品同步的列表
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
     	echo("[".date('c')."] "." sync order start endDate:".$endDate."\n");
     	
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync ordertransfer start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate);
                print_r($request);
                $response=$client->SyncFinishedOrder($request);
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
     * 淘宝库存
     * 
     * @param string $appkey 执行店铺的应用编号
     * @param integer $seconds 修改时间
     */
    public function actionSyncTaobaoItemStock($appkey=null)
    {
		// 获取启用商品库存同步的店铺列表
    	$sql="select application_key from taobao_shop_conf where status='OK' and shop_type = 'taobao' and is_stock_update='Y' ";
    	$result = Yii::app()->getDb()->createCommand($sql)->queryAll();
		if(empty($result)) {
			return;
		}
		$include_list = array();
		foreach($result as $k=>$v) {
			array_push($include_list,$v['application_key']);
		}
    	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
		foreach($this->getTaobaoShopList() as $taobaoShop)
		{
			if(!in_array($taobaoShop['application_key'],$include_list))
				continue;
		
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
		
			echo("[".date('c')."] ".$taobaoShop['nick']." syncitemstock start \n");
			$start = microtime(true);
		
			// 同步库存
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key']);
				$response=$client->SyncTaobaoItemStock($request);
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
     * 淘宝库存（聚石塔）
     *
     * @param string $appkey 执行店铺的应用编号
     * @param integer $seconds 修改时间
     */
    public function actionSyncTaobaoItemStockFromJushita($appkey=null)
    {
    	// 获取启用商品库存同步的店铺列表
    	$sql="select application_key from taobao_shop_conf where status='OK' and shop_type = 'taobao' and is_stock_update='Y' ";
    	$result = Yii::app()->getDb()->createCommand($sql)->queryAll();
    	if(empty($result)) {
    		return;
    	}
    	$include_list = array();
    	foreach($result as $k=>$v) {
    		array_push($include_list,$v['application_key']);
    	}
    	 
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
    		if(!in_array($taobaoShop['application_key'],$include_list))
    			continue;
    
    		if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
    			continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncitemstockfromjushita start \n");
    		$start = microtime(true);
    
    		// 同步库存
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncTaobaoItemStockFromJushita($request);
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
     * 创建shipment
     */
    public function actionCreateShipment($appkey=null,$group=0)
   {
        // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 1200);   	
     	$db=Yii::app()->getDb(); 
     	$startsync = microtime(true);
     	echo("[".date('c')."] syncCreateShipment start \n");
     	  	
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']."-{$taobaoShop['taobao_shop_conf_id']} sync createshipment start \n");
            $start = microtime(true);
            $selectsize=0;
            $createsize=0;
            // 创建shipment
            try
            {
            	$sql = "
            		SELECT order_sn
            		FROM ecshop.ecs_order_info oi use index(order_time)
            		LEFT JOIN romeo.order_shipment os ON CONVERT( oi.order_id
            		USING utf8 ) = os.order_id
            		LEFT JOIN romeo.shipment s ON os.shipment_id = s.shipment_id
            		WHERE s.shipment_id IS NULL and oi.order_type_id in ('SHIP_ONLY', 'SALE', 'RMA_EXCHANGE')
            		AND oi.order_time >=  date_add(now(), interval -10 day)
            		and oi.party_id ='{$taobaoShop['party_id']}'	
            		limit 500			
            	";
                $order_sn_list = $db->createCommand($sql)->queryAll($sql);
                $selectsize = count($order_sn_list);
                
                $startcreate = microtime(true);
            
                foreach ($order_sn_list as $order_sn) {
                    require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
                    $sql = "select o.order_id, o.shipping_id, o.party_id, s.default_carrier_id as carrier_id
                              from ecshop.ecs_order_info o
                                   left join ecshop.ecs_shipping s on o.shipping_id = s.shipping_id
                             where o.order_sn = '{$order_sn['order_sn']}'
                          ";
                    $order = $db->createCommand($sql)->queryAll($sql);
                    $order=$order[0];  //处理数组
                    
                    $sqlshipment="select * from romeo.order_shipment where order_id={$order['order_id']}";
                    $shipment = $db->createCommand($sqlshipment)->queryAll($sqlshipment);
                    if(!empty($shipment)) {
                    	print("[". date('c'). "] {$taobaoShop['nick']}  订单 {$order_sn['order_sn']} 已创建过shipment\n");
                    	continue;
                    }
                                        
                    if (!$order) {
                        print("[". date('c'). "] 找不到订单 {$taobaoShop['nick']}  {$order_sn}\n");
                    } else {
                        try {
                            $handle=soap_get_client('ShipmentService');
                            $handle->createShipmentForOrder(array(
                                'orderId'=>$order['order_id'],
                                'carrierId'=>$order['carrier_id'],
                                'shipmentTypeId'=>$order['shipping_id'],
                                'partyId'=>$order['party_id'],
                                'createdByUserLogin'=>'webService',
                            ));
                         $createsize++;
                         print("[". date('c'). "] {$taobaoShop['nick']} 订单 {$order_sn['order_sn']} shipment 创建成功\n");
                       } 
                        catch (Exception $e) {
                           print("[". date('c'). "] {$taobaoShop['nick']} 订单 {$order_sn['order_sn']} shipment 创建失败\n");
                            print_r($e);
                        }
                    } 
                  }
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "] {$taobaoShop['nick']}-{$taobaoShop['taobao_shop_conf_id']} createshipment end".
                 " 总耗时：".(microtime(true)-$start).
                 " 创建耗时：".(microtime(true)-$startcreate).
                 "  总数: {$selectsize},创建数:{$createsize}\n";
            usleep(500000);
        }    	
 	echo("[".date('c')."] syncCreateShipment end "." 总耗时：".(microtime(true)-$startsync)." \n");
 
 }
 
    /**
	 * 批量创建shipment
	 */
    public function actionCreateShipmentByParty($appkey=null,$group=0)
    {
            // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 600);
        // 远程服务   
         $client=Yii::app()->getComponent('romeo')->ShipmentService;     
        foreach($this->getPartysbyGroup($group) as $PartyId)
        {
            echo("[".date('c')."] ".$PartyId." sync CreateShipmentByParty start \n");
            $start = microtime(true);
            // 同步生成shipment
            try
            {   require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
            	
                $handle=soap_get_client('ShipmentService');                 
                $request=array("partyId"=>$PartyId );
                print_r($request);
                $response=$handle->syncCreateShipmentForOrder($request);
                
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "]  sync CreateShipmentByParty end ：".$PartyId." ".(microtime(true)-$start)."\n";
			usleep(500000);
        }    	
    	
    }
    protected function getPartysbyGroup($group){
    	$partys = array();
    	
        if ($group == 1) {      // 金佰利
        	$partys[] = '65558';
        }
        elseif ($group == 2) {     //雀巢
        	$partys[] = '65553';
        }
        elseif ($group == 3) {   	//康贝、中粮、百事、桂格
        	$partys[] = '65586';
			$partys[] = '65625';
			$partys[] = '65608';
			$partys[] = '65632';
        }
        elseif ($group == 4) {      //除了金佰利 雀巢  康贝  中粮  百事  桂格 乐其跨境的其它组织
        	//外贸组织除外
		    $sql = "
				select party_id from romeo.party where is_leaf='Y' and  status = 'ok' and parent_party_id != '65542' 
						and party_id not in ('65558','65553','65586','65625','65608','65632','65638')
			";
			$partyList = $this->getSlave()->createCommand($sql)->queryAll();
			foreach($partyList as $key=>$item){
				$partys[] = $item['party_id'];
			}
        }
        elseif($group == 5 ){      //乐其跨境
			$partys[] = '65638';
        }
        
        return $partys;
    }
    
    /**
     * 淘宝直销订单数量校验
     */
    public function actionCheckTaobaoOrderConsistency($appkey=null)
    {
    	
    	
    	//不启用淘宝直销订单数量校验的店铺列表
    	$exclude_list=array
    	(
    			// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
    			// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
    	);
		
		$endTime = date("Y-m-d H:m:s",time() - 30*60); 
		$beginTime = date("Y-m-d H:m:s",strtotime($endTime) - 60*60*3 - 30*60);
    	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
		foreach($this->getTaobaoShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
		
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
		
			echo("[".date('c')."] ".$taobaoShop['nick']." CheckTaobaoOrderConsistency start \n");
			$start = microtime(true);
			echo "beginTime=" . $beginTime . " endTime=" . $endTime . "\n";
			// 淘宝直销订单数量校验
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"beginTime"=>$beginTime,"endTime"=>$endTime);
				$response=$client->CheckTaobaoOrderConsistency($request);
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
	 * 同步发货
	 */
    public function actionSyncDeliverySendNew($appkey=null,$group=0)
    {  
        echo('该发货同步已被报废');
    }
 
    /**
     * 逻辑仓库转换为实际仓库
     * @param string $facilityId
     */
    private function facility_convert($facilityId) {
         $facility_mapping = array (
             '12768420' =>  '12768420',    //  怀轩上海仓
             '19568548' =>  '19568548',    //  电商服务东莞仓
             '3580047'  =>  '19568548',    //  乐其东莞仓
             '19568549' =>  '19568549',    //  电商服务上海仓
             '3633071'  =>  '19568549',    //  乐其上海仓
             '22143846' =>  '19568549',    //  乐其杭州仓
             '22143847' =>  '19568549',    //  电商服务杭州仓
             '24196974' =>  '19568549',    //  贝亲青浦仓
             '42741887' =>  '42741887',    //  乐其北京仓
         );
         if (array_key_exists($facilityId, $facility_mapping)) {
             return $facility_mapping[$facilityId] ;
         } else {
             return $facilityId ;
         }
    }
    
    
    
    /**
	 * 取得启用的亚马逊店铺
	 * 
	 * @return array
	 */
    protected function getCunTaobaoShopList($group = 0)
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = 'cuntao'";
            
            if($group == 0 ){   	// 全部业务组  不做处理
            }
           
            $list=$this->getSlave()->createCommand($sql)->queryAll();
            $command=$this->getSlave()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
            foreach($list as $key=>$item)
            $list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
        }
        return $list;
    }
    
    /**
	 * 取得启用的亚马逊店铺
	 * 
	 * @return array
	 */
    protected function getTaobaoShopList($group = 0)
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = 'taobao'";
            
            if($group == 0 ){   	// 全部业务组  不做处理
            }
            elseif ($group == 1) {      //金佰利
                //echo " 金佰利\n";
            	$sql .= " and party_id in ('65558') ";
            }
            elseif ($group == 2) {     //雀巢  
                //echo " 雀巢   \n";
            	$sql .= " and party_id in ('65553') ";
            }
             elseif ($group == 3) {   	// 康贝、中粮、百事、桂格
                echo " 康贝、中粮、百事、桂格\n";
            	$sql .= " and party_id in ('65586','65625','65608','65632') ";
            }
            elseif ($group == 4) {      //除了金佰利 雀巢  康贝  中粮  百事  桂格 乐其跨境的其它组织
               echo "除了金佰利 雀巢  康贝  中粮  百事  桂格 乐其跨境的其它组织\n";
            	$sql .= " and party_id not in ('65558','65553','65586','65625','65608','65632','65638') ";
            }
            elseif ($group == 5) {      //乐其跨境
               echo "乐其跨境\n";
            	$sql .= " and party_id in ('65638') ";
            }
            else {            	// 非法参数
            	//echo 'invad $group='.$group."\n";
            	$sql .= " and  1=0 ";
            }
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
	/**
	 * 每个命令执行前执行
	 *
	 * @param string $action
	 * @param array $params
	 * @return boolean
	 */
	protected function beforeAction($action, $params)
	{
		if(strnatcasecmp($action,'index')==0)
			return true;
		
		// 加锁
		$lockName="commands.".$this->getName().".".$action;
		if(!empty($params)){
			foreach($params as $key=>$param) {
				$lockName = $lockName.$param ;
			}
		}
		
		echo "lockName:".$lockName."\n";
		
		if(($lock=Yii::app()->getComponent('lock'))!==null && $lock->acquire($lockName,60*10))
		{
			// 记录命令的最后一次执行的开始时间
			$key='commands.'.$this->getName().'.'.strtolower($action).':start';
			Yii::app()->setGlobalState($key,microtime(true));
			return true;	
		}
		else
		{
			echo "[".date('Y-m-d H:i:s')."] 命令{$action}正在被执行，或上次执行异常导致独占锁没有被释放，请稍候再试。\n";
			return false;
		}
	}
	
	/**
	 * 执行完毕后执行
	 * 
	 * @param string $action the action name
	 * @param array $params the parameters to be passed to the action method.
	 */
	protected function afterAction($action,$params)
	{	
		if(strnatcasecmp($action,'index')==0)
			return;

		// 记录命令的最后一次执行的完毕时间
		$key='commands.'.$this->getName().'.'.strtolower($action).':done';
		Yii::app()->setGlobalState($key,microtime(true));
		
		// 释放锁
		$lockName="commands.".$this->getName().".".$action.$params[1];
		$lock=Yii::app()->getComponent('lock');
		$lock->release($lockName);
	}
	
	/**
	 * 取得最后一次执行完毕的时间
	 *
	 * @param string $action
	 */
	protected function getLastExecuteTime($action)
	{
		$key='commands.'.$this->getName().'.'.strtolower($action).':done';
		return Yii::app()->getGlobalState($key,0);
	}
}
?>
