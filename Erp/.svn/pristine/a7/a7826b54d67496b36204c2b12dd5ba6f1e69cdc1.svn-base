<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'admin/function.php');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once(ROOT_PATH . "/includes/lib_order.php");
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH.'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH.'RomeoApi/lib_RMATrack.php');
require_once ROOT_PATH . 'admin/includes/lib_service.php';
require_once ROOT_PATH . 'admin/includes/lib_bird_indicate.php';

Yii::import('application.commands.LockedCommand', true);


/*
 * Created on 2015-05-18
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncBirdExpressCommand extends CConsoleCommand
{
	
	
	/**
	 * 将物流宝商品初始化到express_brid_product表中
	 * @param string $appkey 执行店铺的应用编号
	 */
    public function actionSyncProductInit($appkey=null)
    {
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			 
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncProductInit start \n");
    		$start = microtime(true);
    
    		// 将物流宝商品初始化到express_brid_product表中
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncProductInit($request);
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
	 * 计算ERP中对应商品的库存和物流宝商品的库存，存到库存中间表，并进行比对
	 * @param string $appkey 执行店铺的应用编号
	 */
    public function actionSyncBirdGoodsStock($appkey=null)
    {
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			 
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncBirdGoodsStock start \n");
    		$start = microtime(true);
    
    		// 计算ERP中对应商品的库存和物流宝商品的库存，存到库存中间表，并进行比对
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
    			$response=$client->SyncBirdGoodsStock($request);
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
	
	
	public function actionCreateTempletOrder($taobaoOrderSn="453159157503751",$number=500)
    {
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync2')->SyncTaobaoService;
    		echo("[".date('c')."]  SyncTaobaoService start \n");
    		$start = microtime(true);
    
    		// 计算ERP中对应商品的库存和物流宝商品的库存，存到库存中间表，并进行比对
    		try
    		{
    			$request=array("taobaoOrderSn"=>"453159157503751","number"=>500);
    			$response=$client->CreateTempletOrder($request);
    			print_r($response);
    		}
    		catch(Exception $e)
    		{
    			echo("|  Exception: ".$e->getMessage()."\n");
    		}
    
    		echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
    		usleep(5000);
    
    }
	
    
    /**
	 * 获取ERP中订单产生时间距当前时间hours之内的订单，转换为BirdExpressOrder的订单，并插入到express_bird_indicate表中
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours ERP中订单产生时间距离当前时间的小时数
	 */
    public function actionGenerateBirdExpressOrder($appkey=null,$hours=6 , $endDate=null )
    {
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			 
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
				
			if(empty($endDate)) {
        		$endDate = date('Y-m-d H:i:s',time());
	        }
	        else {
	        	$endDate = $endDate." 00:00:00";
	        }    
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." GenerateBirdExpressOrder start ! endDate:".$endDate."\n");
    		$start = microtime(true);
    
    		// 获取ERP中订单产生时间距当前时间hours之内的订单，转换为BirdExpressOrder的订单，并插入到express_bird_indicate表中
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours , "endDate"=>$endDate );
    			$response=$client->GenerateBirdExpressOrder($request);
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
	 * 从express_bird_indicate表中将状态为“未推送”的订单推送给物流宝
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 物流宝中间表中订单产生时间距离当前时间的小时数
	 */
    public function actionSendBirdExpressOrder($appkey=null,$hours=6,$flag=1)
    {
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			 
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SendBirdExpressOrder start \n");
    		$start = microtime(true);
    
    		// 从express_bird_indicate表中将状态为“未推送”的订单推送给物流宝
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"flag"=>$flag);
    			$response=$client->SendBirdExpressOrder($request);
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
    
//    public function actionSendBirdExpressOrder_test($appkey=null,$hours=6,$flag=1)
//    {
//    	// 不启用商品同步的店铺列表
//		ini_set('default_socket_timeout', 1200);
//    
//    	// 远程服务
//    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
//    	foreach($this->getTaobaoShopList() as $taobaoShop)
//    	{
//			 
//			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
//				continue;
//    
//    		echo("[".date('c')."] ".$taobaoShop['nick']." SendBirdExpressOrder_test start \n");
//    		$start = microtime(true);
//    
//    		// 从express_bird_indicate表中将状态为“未推送”的订单推送给物流宝
//    		try
//    		{
//    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"flag"=>$flag);
//    			$response=$client->SendBirdExpressOrder_test($request);
//    			print_r($response);
//    		}
//    		catch(Exception $e)
//    		{
//    			echo("|  Exception: ".$e->getMessage()."\n");
//    		}
//    
//    		echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
//    		usleep(500000);
//    	}
//    
//    }
     
     
     /**
	 * 从ERP中获取要取消的订单，调用菜鸟的接口取消已经推送给菜鸟的订单, 获取订单的条件为：ERP中状态为“已取消”，且订单产生时间距离当前时间hours小时内
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 订单产生时间距离当前时间hours小时
	 */
//    public function actionCancelBirdExpressOrder($appkey=null,$hours=6)
//    {
//    	// 不启用商品同步的店铺列表
//		ini_set('default_socket_timeout', 1200);
//    
//    	// 远程服务
//    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
//    	foreach($this->getTaobaoShopList() as $taobaoShop)
//    	{
//			 
//			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
//				continue;
//    
//    		echo("[".date('c')."] ".$taobaoShop['nick']." CancelBirdExpressOrder start \n");
//    		$start = microtime(true);
//    
//    		// 从ERP中获取要取消的订单，调用菜鸟的接口取消已经推送给菜鸟的订单, 获取订单的条件为：ERP中状态为“已取消”，且订单产生时间距离当前时间hours小时内
//    		try
//    		{
//    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
//    			$response=$client->CancelBirdExpressOrder($request);
//    			print_r($response);
//    		}
//    		catch(Exception $e)
//    		{
//    			echo("|  Exception: ".$e->getMessage()."\n");
//    		}
//    
//    		echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
//    		usleep(500000);
//    	}
//    
//    }
    
    
    
    
     /**
	 * 按订单状态和时间来查询物流宝订单的状态（包括销售订单、退货订单）
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 查询当前时间hours小时之内的订单状态
	 */
    public function actionSyncBirdExpressActual($appkey=null,$hours=6 , $endDate=null )
    {
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			echo ("appkey: ".$appkey);
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    		if(empty($endDate)) {
        		$endDate = date('Y-m-d H:i:s',time());
	        }
	        else {
	        	$endDate = $endDate." 00:00:00";
	        }
	        
	        
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncBirdExpressActual start ! endDate:".$endDate."\n");
    		$start = microtime(true);
    		
    
    		//按订单状态和时间来查询物流宝订单的状态（包括销售订单、退货订单）
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours , "endDate"=>$endDate );
    			$response=$client->SyncBirdExpressActual($request);
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
	 *  获取ERP中的退货订单订单产生时间距当前时间hours之内的订单，转换为BirdExpressOrder的订单，并插入到express_bird_indicate表中
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 查询当前时间hours小时之内的订单状态
	 */
    public function actionGenerateBirdExpressReturnOrder($appkey=null,$hours=6, $endDate=null)
    {   	
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			 
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    	
    		if(empty($endDate)) {
        		$endDate = date('Y-m-d H:i:s',time());
	        }
	        else {
	        	$endDate = $endDate." 00:00:00";
	        }
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." GenerateBirdExpressReturnOrder start ! endDate:".$endDate."\n" );
    		$start = microtime(true);
    
    		// 获取ERP中的退货订单订单产生时间距当前时间hours之内的订单，转换为BirdExpressOrder的订单，并插入到express_bird_indicate表中
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours , "endDate"=>$endDate );
    			$response=$client->GenerateBirdExpressReturnOrder($request);
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
     * 对于退货订单，菜鸟仓库验货入库之后，ERP进行虚拟入库
     */
    public function actionReturnBirdExpressReturnGoodsToFacility($appkey=null)
    { 
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			 
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." 
" .
		" start \n");
    		$start = microtime(true);
    		
    		//先从实绩表中获取已收货入库的淘宝订单号（带-t），再在ecs_order_info中找到该淘宝订单号对应的退货订单的order_id,
	    	//用该order_id去关联service表，找到对应的service记录    
	    	$sql = "SELECT out_biz_code from ecshop.express_bird_indicate where logistics_status='已收货入库' and application_key='{$taobaoShop['application_key']}'";
	    	$taobao_order_sns = Yii::app()->getDb()->createCommand($sql)->queryAll();
	    	$original2Return = array();
	    	$condition = ' ( ';
	    	foreach($taobao_order_sns as $order_sn){
	    		$return_sn=$order_sn['out_biz_code'];
	    		$pos = strpos($return_sn, '-');
	    		$new_order_sn=substr($return_sn,0,$pos); //原订单的taobao_order_sn
	    		$condition .= "'{$new_order_sn}',";
	    		//新建一个原订单taobao_order_sn到退货订单taobao_order_sn的对应关系
	    		if(!array_key_exists($new_order_sn,$original2Return)){
	    			$original2Return[$new_order_sn]=array();
	    		}
	    		$original2Return[$new_order_sn][]=$return_sn;
	    	}
	    	$condition = substr($condition,0,strlen($condition)-1);
			$condition .=" ) ";
			
			if(count($taobao_order_sns)>0){
			
				//获取要进行虚拟入库的退货订单的order_id
				$sql = "
						SELECT order_id,order_sn,taobao_order_sn 
						from ecshop.ecs_order_info 
						where taobao_order_sn in $condition and order_type_id='RMA_RETURN';";
				$orders = Yii::app()->getDb()->createCommand($sql)->queryAll();
				$orderCondition = ' ( ';
				$returnOrderId2TaobaoSnArr = array();
				foreach($orders as $order){
					$order_id=$order['order_id'];
					$taobaoSn = $order['taobao_order_sn'];
					$new_taobao_order_sn = $taobaoSn.stristr($order['order_sn'], '-');
					if(array_key_exists($taobaoSn,$original2Return) && in_array($new_taobao_order_sn,$original2Return[$taobaoSn])){
						$returnOrderId2TaobaoSnArr[$order_id]=$new_taobao_order_sn;
						$orderCondition.= "'{$order_id}',";
					}
				}
				$orderCondition = substr($orderCondition,0,strlen($orderCondition)-1);
				$orderCondition .=" ) ";
				
			
				//对退货订单进行虚拟入库
				
				$sql = "
					SELECT se.service_id,se.back_order_id 
					FROM ecshop.service se					
					where se.back_order_id in $orderCondition";				
				
		    	$serviceArr = Yii::app()->getDb()->createCommand($sql)->queryAll();
		    	foreach($serviceArr as $service){
		    		echo "service_id: ".$service['service_id']."\n";
		    		echo "back_order_id: ".$service['back_order_id']."\n";
		    		$service_id = $service['service_id'];
		    		$back_order_id = $service['back_order_id'];
		    		$info = auto_service($service_id);
		    		if($info['res']=='success'){
						$transfer_note = "erp已虚拟入库成功，service_id：".$service['service_id'];
						//修改express_bird_indicate表中的收货状态
						$out_biz_code = $returnOrderId2TaobaoSnArr[$back_order_id];
				    	$sql = "update ecshop.express_bird_indicate set logistics_status='ERP中已经虚拟入库',last_updated_stamp=now()
								where out_biz_code='{$out_biz_code}' limit 1";
						$update=Yii::app()->getDb()->createCommand($sql)->execute();
						echo $transfer_note + "\n";
					}else{
						$transfer_note = "erp虚拟入库失败，service_id：".$service['service_id'];
						echo $transfer_note + "\n";
					}
		    	}
	    	
			}
			
	    	echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
    		usleep(500000);
    		
    	}
    	
    } 
    
    
    
    
    /**
	 *  获取ERP中的采购订单订单产生时间距当前时间hours之内的订单，转换为BirdExpressOrder的订单，并插入到express_bird_indicate表中
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 查询当前时间hours小时之内的订单 
	 */
    public function actionGenerateBirdExpressPurchaseOrder($appkey=null,$hours=6  , $endDate=null )
    {   	
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    	
    	
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
    	
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
    		echo $appkey;
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    		
    		if(empty($endDate)) {
        		$endDate = date('Y-m-d H:i:s',time());
	        }
	        else {
	        	$endDate = $endDate." 00:00:00";
	        }
	        
    		echo("[".date('c')."] ".$taobaoShop['nick']." GenerateBirdExpressPurchaseOrder start ! endDate:".$endDate."\n");
    		$start = microtime(true);
    
    		//获取ERP中的采购订单订单产生时间距当前时间hours之内的订单，转换为BirdExpressOrder的订单，并插入到express_bird_indicate表中
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours , "endDate"=>$endDate);
    			$response=$client->GenerateBirdExpressPurchaseOrder($request);
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
	 *  获取ERP中的-gt订单订单产生时间距当前时间hours之内的订单，转换为BirdExpressOrder的订单，并插入到express_bird_indicate表中
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 查询当前时间hours小时之内的订单状态
	 */
    public function actionGenerateBirdExpressSupplierReturnOrder($appkey=null,$hours=6  , $endDate=null )
    {   	
    	// 不启用商品同步的店铺列表
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBirdExpressService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			 
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    		
    		if(empty($endDate)) {
        		$endDate = date('Y-m-d H:i:s',time());
	        }
	        else {
	        	$endDate = $endDate." 00:00:00";
	        }
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." GenerateBirdExpressSupplierReturnOrder start ! endDate:".$endDate."\n");
    		$start = microtime(true);
    
    		//  获取ERP中的-gt订单订单产生时间距当前时间hours之内的订单，转换为BirdExpressOrder的订单，并插入到express_bird_indicate表中
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours  , "endDate"=>$endDate );
    			$response=$client->GenerateBirdExpressSupplierReturnOrder($request);
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
	 *  处理ERP中的采购订单的入库实绩：菜鸟收货入库之后，ERP中进行虚拟入库
	 */
    public function actionPurchaseInventoryInActual()
    {
    	
    	$partyArr = array(65614,65558 ,65632, 65553);  //需要进行采购订单虚拟入库的业务组织
    	foreach($partyArr as $party)
    	{
    		echo("[".date('c')."] partyId: ".$party.", PurchaseInventoryInActual start \n");
			$start = microtime(true);
				
	        birdExpressActualDoAction('PURCHASE INVENTORY_IN',$party);
	        
	        echo("[".date('c')."] partyId: ".$party.", PurchaseInventoryInActual end \n");
	        
	        echo "耗时：".(microtime(true)-$start)."\n";
    	}
    	
    }
    
    
      
    /**
	 *  处理ERP中的-gt订单的出库实绩：菜鸟发货出库之后，ERP中进行虚拟出库
	 */
    public function actionSupplierReturnInventoryOutActual()
    {
    	
    	$partyArr = array(65614,65558,65632,65553 );  //需要进行-gt订单虚拟出库的业务组织
    	foreach($partyArr as $party)
    	{
    		echo("[".date('c')."] partyId: ".$party.", SupplierReturnInventoryOutActual start \n");
			$start = microtime(true);
				
	        birdExpressActualDoAction('SUPPLIER_RETURN',$party);
	        
	        echo("[".date('c')."] partyId: ".$party.", SupplierReturnInventoryOutActual end \n");
	        
	        echo "耗时：".(microtime(true)-$start)."\n";
    	}
    	
    }
    
    
    
    
    
    
    /**
     * 取得启用的需要对接菜鸟物流的店铺
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
//    		$sql="select * from taobao_shop_conf where status='OK' and nick in ('百威英博官方旗舰店','百威啤酒官方旗舰店','huggies好奇旗舰店')"; //测试店铺
//    		$sql="select * from taobao_shop_conf where status='OK' and nick in ('百威英博官方旗舰店','百威啤酒官方旗舰店','Huggies好奇官方旗舰店')"; //线上店铺
    		$sql="select * from taobao_shop_conf where status='OK' and is_bird_facility_available = 'Y' "; //线上店铺
    		
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
    
    
    
}
?>
