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
class ErpSyncScnCommand extends CConsoleCommand
{
	
	private $slave;  // Slave数据库
	private $master; // Master数据库    

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
    
    public function actionCreateScnItem() 
    {
    	
    	ini_set('default_socket_timeout', 600);
    	$start = time();
    	$party_id = '65611';
		$this->log("CreateScnItem start");
    	$update_stamp = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	
    	$sql = "select g.goods_id, goods_name,shop_price, barcode
    	        from ecshop.ecs_goods g
    	        left join ecshop.sync_scn_goods sg on g.goods_id = sg.goods_id 
    	        where g.goods_party_id = '{$party_id}' and sg.scn_goods_id is null and
    	        g.is_delete = 0 and g.last_update_stamp >= '{$update_stamp}' ";
    	$goods_list =$this->getMaster()->createCommand($sql)->queryAll();
    	$sql_time = time();
		$this->log("mysql execute time:" . ($sql_time-$start));
		
    	if (!empty($goods_list)) {
    		foreach ($goods_list as $key => $goods_item) {
    		$sql = "insert into ecshop.sync_scn_goods 
    					(goods_id, VendorItemId, ProductNo, ItemName, BrandName, TagPrice, ColorName, party_id, status, created_stamp, last_update_stamp )
    				values 
    					('{$goods_item['goods_id']}', '{$goods_item['goods_id']}', '{$goods_item['barcode']}', '{$goods_item['goods_name']}', '新百伦', '{$goods_item['shop_price']}', '{$goods_item['barcode']}',
    					'{$party_id}', 'INIT', now(), now())"
    				;
    		$this->getMaster()->createCommand ($sql)->execute();
    		$sql = "select gs.goods_id, gs.style_id, c.color
    				from ecshop.ecs_goods_style gs 
    				left join ecshop.ecs_style c on gs.style_id = c.style_id 
    				where gs.goods_id = '{$goods_item['goods_id']}' and gs.is_delete = 0 ";
    		$sku_list =$this->getMaster()->createCommand($sql)->queryAll();
    		foreach ($sku_list as $key => $sku_item) {
    			$VendorSkuId = $sku_item['goods_id'] . "_" . $sku_item['style_id'];
    			$sql = "insert into ecshop.sync_scn_goods_style 
    					(scn_goods_id, goods_id, style_id, VendorSkuId, SizeName, qoh, party_id, status, created_stamp, last_update_stamp )
    				values 
    					('{$sku_item['goods_id']}', '{$sku_item['goods_id']}', '{$sku_item['style_id']}', '{$VendorSkuId}', '{$sku_item['color']}', 0,
    					'{$party_id}', 'INIT', now(), now())"
    				;
    			$this->getMaster()->createCommand ($sql)->execute();
    		}
    		}
    	
    	} else {
			$this->log(" goods_list is empty");
		}
		$this->log(" CreateScnItem end total_time:" .(time()-$start));
    }
    
    /**
	 * 同步名鞋库上的商品
	 *
	 * @param string  $appkey
	 * @param integer $seconds
	 */
    public function actionSyncScnItem($appkey=null,$limit=100)
    {
            // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 600);
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncScnService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
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
                $request=array("applicationKey"=>$taobaoShop['application_key'], "limit" => $limit);
                print_r($request);
                $response=$client->SyncScnItem($request);
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
    public function actionSyncScnOrder($appkey=null,$hours=1,$group=0,$endDate=null)
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
        $client=Yii::app()->getComponent('erpsync')->SyncScnService;
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
                $response=$client->SyncScnOrder($request);
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
            usleep(500000);
            
            // 同步发货订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key']);
                $response=$client->SyncScnOrderDelivered($request);
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }
            usleep(500000);
        }    
 
    }  
    
   /**
	 * 订单转换(只转换7天内的订单)
	 */
    public function actionScnOrderTransfer($appkey=null,$days=1,$group=0)
    {
            // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 1200);
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncScnService;
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
                $request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days );
                print_r($request);
                $response=$client->ScnOrderTransfer($request);
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
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync CreateShipmentByParty start \n");
            $start = microtime(true);
            // 同步生成shipment
            try
            {   require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
            	
                $handle=soap_get_client('ShipmentService');                 
                $request=array("partyId"=>$taobaoShop['party_id'] );
                print_r($request);
                $response=$handle->syncCreateShipmentForOrder($request);
              //  $response=$client->syncCreateShipmentForOrder($request);
                
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "]  sync CreateShipmentByParty end ：".$taobaoShop['nick']." ".(microtime(true)-$start)."\n";
            usleep(500000);
        }    	
    	
    }
    
     /**
     * 库存同步
     * 
     * @param string $appkey 执行店铺的应用编号
     * @param integer $seconds 修改时间
     */
    public function actionSyncScnStock($appkey=null,$group=0,$days=7)  
    {
    // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 1200);
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncScnService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync actionSyncScnStock start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'] );
                print_r($request);
                $response=$client->SyncScnItemStock($request);
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
	 * 取得启用的名鞋库店铺
	 * 
	 * @return array
	 */
    protected function getTaobaoShopList($group = 0)
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = 'scn'";
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
    
    	private function log ($m) {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
    }
    
        /**
     * 取得master数据库连接
     * 
     * @return CDbConnection
     */
    protected function getMaster() {
        if (! $this->master) {
            $this->master = Yii::app ()->getDb ();
            $this->master->setActive ( true );
        }
        return $this->master;
    } 
}
?>
