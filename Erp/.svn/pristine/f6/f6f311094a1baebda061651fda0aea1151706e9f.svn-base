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
class ErpSyncJushitaCommand extends CConsoleCommand
{
	
	private $db;  // master数据库

    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
    
        /**
	 * 当不指定ActionName时的默认调用
	 */
    public function actionIndex()
    {
        $currentTime=microtime(true);
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
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
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
    
    public function actionSyncJushitaItems($appkey=null,$group=0){
    	 // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 600);
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync item start \n");
            $start = microtime(true);
            // 从聚石塔上同步商品
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],'username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getCountIids($request);
               // $response=$client->SyncTaobaoItem('f1cfc3f7859f47fa8e7c150c2be35bfc');
               //得到num_iid的数量
                print_r($response);
                for ($i=1 ;$i <= $response->return/100+1; $i++){
       				$getNumiidsRequest = array("applicationKey"=>$taobaoShop['application_key'],'username'=>JSTUsername,'password'=>md5(JSTPassword),'page'=>$i);
					$getNumiidsResponse=$client->getNumIIdsByPage($getNumiidsRequest);
					$getNumiidsResponse->return = isset($getNumiidsResponse->return)?$getNumiidsResponse->return:array();
					$numIids = is_array($getNumiidsResponse->return)?$getNumiidsResponse->return:array($getNumiidsResponse->return);
					foreach ( $numIids as $numIid ) {
       					$getItemRequest = array('numiids'=>$numIid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
       					$getItemResponse = $client->getTaobaoItemsByNumIid($getItemRequest);
       					if(empty($getItemResponse->return)){
       						var_dump($numIid+"为空");
       						continue;
       					}
       					$this->insertOrUpdateTaobaoItem($getItemResponse->return);
					}
				}
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
	 * 从聚石塔上获取item之后，插入或者更新到数据库中
	 * 
	 * 保证每一个商品 item的插入都是一个事物
	 */
    protected function insertOrUpdateTaobaoItem($item){
    	try{
    		$this->getDb()->createCommand("START TRANSACTION")->execute();
	    	if(isset($item->list_time)) $item->list_time = $this->getPHPDate($item->list_time);
	    	if(isset($item->delist_time)) $item->delist_time = $this->getPHPDate($item->delist_time);
	    	if(isset($item->modified_time)) $item->modified_time = $this->getPHPDate($item->modified_time);
	    	if(isset($item->create_timestamp)) $item->create_timestamp = $this->getPHPDate($item->create_timestamp);
	    	if(isset($item->last_update_timestamp)) $item->last_update_timestamp = $this->getPHPDate($item->last_update_timestamp);
	    	
	    	$builder = $this->getDb()->getCommandBuilder ();
			$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_items');
			var_dump($item->num_iid);
			if(!$this->getDb()->createCommand("select * from ecshop.sync_taobao_items where num_iid = ".$item->num_iid)->queryRow()){
				//假如原来不存在num_iid的数据，则需要使用插入语句
				$builder->createInsertCommand($table, $item)->execute();
			}else{
				//否则使用更新语句
				$builder->createUpdateCommand($table, $item, new CDbCriteria(array(
                    "condition" => "num_iid = :num_iid" , 
                    "params" => array(
                        "num_iid"=>$item->num_iid
                    )
                )))->execute();
			}
			$itemSkus = isset($item->taobaoItemsSkus)?$item->taobaoItemsSkus:array();
			$itemSkus = is_array($itemSkus)?$itemSkus:array($itemSkus);
			
			//删除聚石塔上没有而本地有的sku
			$existSkus = $this->getDb()->createCommand("select sku_id from ecshop.sync_taobao_items_sku where num_iid = '".$item->num_iid."'")->queryAll();
			if($existSkus){
				if(count($itemSkus) == 0){
					var_dump('delete sync_taobao_items_sku num_iid:'.$item->num_iid);
					$this->getDb()->createCommand("delete from ecshop.sync_taobao_items_sku where num_iid = '".$item->num_iid."'")->execute();
				}else{
					foreach ( $existSkus as $existSku ) {
						$flag = false;
       					foreach ( $itemSkus as $itemSku) {
       						if($existSku['sku_id'] == $itemSku->sku_id){
       							$flag = true;
       							break;
       						}
       					}
       					if(!$flag){
							var_dump('delete sync_taobao_items_sku sku_id:'.$existSku['sku_id']);
       						$this->getDb()->createCommand("delete from ecshop.sync_taobao_items_sku where sku_id = '".$existSku['sku_id']."'")->execute();
						}
					}
				}
			}
			
			foreach ( $itemSkus as $itemSku) {
       			if(isset($itemSku->created)) $itemSku->created = $this->getPHPDate($itemSku->created);
       			if(isset($itemSku->modified)) $itemSku->modified = $this->getPHPDate($itemSku->modified);
       			if(isset($itemSku->create_timestamp)) $itemSku->create_timestamp = $this->getPHPDate($itemSku->create_timestamp);
       			if(isset($itemSku->last_update_timestamp)) $itemSku->last_update_timestamp = $this->getPHPDate($itemSku->last_update_timestamp);
				
				$builder = $this->getDb()->getCommandBuilder ();
				$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_items_sku');
				if(!$this->getDb()->createCommand("select * from ecshop.sync_taobao_items_sku where sku_id = ".$itemSku->sku_id)->queryRow()){
					//假如原来不存在sku_iid的数据，则需要使用插入语句
					$builder->createInsertCommand($table, $itemSku)->execute();
				}else{
					//否则使用更新语句
					$builder->createUpdateCommand($table, $itemSku, new CDbCriteria(array(
	                    "condition" => "sku_id = :sku_id" , 
	                    "params" => array(
	                        "sku_id"=>$itemSku->sku_id
	                    )
	                )))->execute();
				}
			}
			$this->getDb()->createCommand("COMMIT")->execute();
    	}catch(Exception $e){
    		$this->getDb()->createCommand("rollback")->execute();
    		echo 'Message: ' .$e->getMessage();
    	}
    }

	/*
	 * 天猫批量退货单查询
	 */
	public function actionSyncTmallReturnBill($appkey=null,$hours=1,$group=0,$endDate=null){
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
      	echo("[".date('c')."] "." sync Tmall refund good return mget start endDate:".$endDate."\n");
        
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync Tmall refund good return mget start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate);
                print_r($request);
                $response=$client->syncTmallReturnBill($request);
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
	 
	 /*
	  * 从聚石塔上同步天猫批量退货单信息
	  */
	public function actionSyncJushitaTmallReturnBill($appkey=null,$hours=1,$group=0,$endDate=null){
     	ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
      	echo("[".date('c')."] "." sync tmall return bill start endDate:".$endDate."\n");
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync tmall return bill start \n");
            $start = microtime(true);
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getTmallReturnBillIdsByAppkey($request);
                print_r($response);
                $refundIds = null;
                $refundIds = isset($response->return)?$response->return:array();
                $refundIds = is_array($refundIds)?$refundIds:array($refundIds);
                if(empty($refundIds)){
                	continue;
                }
                $sql = "select refund_id from ecshop.sync_tmall_return_bill where refund_id in (";
                foreach ( $refundIds as $refundId ) {
       				$sql .= "'$refundId',";
				}
				$sql = substr($sql,0,strlen($sql)-1);
                $sql .=	")";
                
                $existedRefundIds = $this->getDb()->createCommand($sql)->queryColumn();
                
                foreach ( $refundIds as $refundId ) {
       				$refundMesRequest = array("refundId"=>$refundId,'username'=>JSTUsername,'password'=>md5(JSTPassword));
					print_r($refundMesRequest);
					$refundMesResponse = $client->getTmallReturnBillById($refundMesRequest);
					print_r($refundMesResponse);
					$tmallReturnBill = isset($refundMesResponse->return)?$refundMesResponse->return:null;
					if(!empty($tmallReturnBill)){
						$this->insertOrUpdateTmallReturnBill($tmallReturnBill,$existedRefundIds);
					}
				}
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
            usleep(500000);
        }
     }
     
    protected function insertOrUpdateTmallReturnBill($tmallReturnBill,$existedRefundIds){
    	
    	$builder = $this->getDb()->getCommandBuilder ();
    	$table = $this->getDb()->getSchema()->getTable('ecshop.sync_tmall_return_bill');
    	
    	if(in_array($tmallReturnBill->refund_id,$existedRefundIds)){
    		$builder->createUpdateCommand($table, $tmallReturnBill, new CDbCriteria(array(
                    "condition" => "refund_id = :refund_id" , 
                    "params" => array(
                        "refund_id"=>$tmallReturnBill->refund_id
                    )
               )))->execute();
    	}else{
    		$builder->createInsertCommand($table, $tmallReturnBill)->execute();
    	}
    }
    
    /*
	 * 天猫批量退款单查询
	 */
	public function actionSyncTmallRefundBill($appkey=null,$hours=1,$group=0,$endDate=null){
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
      	echo("[".date('c')."] "." sync Tmall refund mget start endDate:".$endDate."\n");
        
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync Tmall refund mget start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate);
                print_r($request);
                $response=$client->syncTmallRefundBill($request);
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
	 
	 /*
	  * 从聚石塔上同步天猫批量退款单信息
	  */
	public function actionSyncJushitaTmallRefundBill($appkey=null,$hours=1,$group=0,$endDate=null){
     	ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
      	echo("[".date('c')."] "." sync tmall refund bill start endDate:".$endDate."\n");
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync tmall refund bill start \n");
            $start = microtime(true);
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getTmallRefundBillIdsByAppkey($request);
                print_r($response);
                $refundIds = null;
                $refundIds = isset($response->return)?$response->return:array();
                $refundIds = is_array($refundIds)?$refundIds:array($refundIds);
                if(empty($refundIds)){
                	continue;
                }
                $sql = "select refund_id from ecshop.sync_tmall_refund_bill where refund_id in (";
                foreach ( $refundIds as $refundId ) {
       				$sql .= "'$refundId',";
				}
				$sql = substr($sql,0,strlen($sql)-1);
                $sql .=	")";
                
                $existedRefundIds = $this->getDb()->createCommand($sql)->queryColumn();
                
                foreach ( $refundIds as $refundId ) {
       				$refundMesRequest = array("refundId"=>$refundId,'username'=>JSTUsername,'password'=>md5(JSTPassword));
					print_r($refundMesRequest);
					$refundMesResponse = $client->getTmallRefundBillById($refundMesRequest);
					print_r($refundMesResponse);
					$tmallRefundBill = isset($refundMesResponse->return)?$refundMesResponse->return:null;
					if(!empty($tmallRefundBill)){
						$this->insertOrUpdateTmallRefundBill($tmallRefundBill,$existedRefundIds);
					}
				}
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
            usleep(500000);
        }
     }
     
    protected function insertOrUpdateTmallRefundBill($tmallRefundBill,$existedRefundIds){
    	
    	$builder = $this->getDb()->getCommandBuilder ();
    	$table = $this->getDb()->getSchema()->getTable('ecshop.sync_tmall_refund_bill');
    	
    	if(in_array($tmallRefundBill->refund_id,$existedRefundIds)){
    		$builder->createUpdateCommand($table, $tmallRefundBill, new CDbCriteria(array(
                    "condition" => "refund_id = :refund_id" , 
                    "params" => array(
                        "refund_id"=>$tmallRefundBill->refund_id
                    )
               )))->execute();
    	}else{
    		$builder->createInsertCommand($table, $tmallRefundBill)->execute();
    	}
    }
    
    
    /**
	 * 从淘宝平台上同步淘宝直销订单
	 */
    public function actionSyncTaobaoOrder($appkey=null,$hours=1,$group=0,$endDate=null)
    {
    	$start_of_action = microtime(true);

        // 不启用订单同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
         // 'fe1441b38d4742008bd9929291927e9e'  // huggies好奇旗舰店（金佰利需要暂时不同步的店铺 ，从2015-09-28 18:00开始）
        );
        ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	if( strstr($endDate, ",") ){
        		$endDates = explode("," , $endDate) ;
        		if(count($endDates)>1){ 
        			$endDate = $endDates[0]. " " . $endDates[1] ;
        		} else{ 
        			$endDate = $endDates[0]." 00:00:00"; ; 
        		} 
        	}
        	else{
        		$endDate = $endDate." 00:00:00";
        	}
        }
        
      	 echo("[".date('c')."] "." sync order start endDate:".$endDate."\n");
        $this->sinri_log('SyncTaobaoOrder(appkey='.$appkey.',hours='.$hours.',group='.$group.',endDate='.$endDate.')');
        
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list)){
                $this->sinri_log('appkey in exclude_list, continue');
                continue;
            }

            if($appkey!==null && $appkey!=$taobaoShop['application_key']){
                $this->sinri_log('appkey is not given one, continue');
                continue;
            }

            $this->sinri_log("BEGIN_SYNC_TAOBAO_ORDER".$taobaoShop['nick']." From Taobao To Jushita.");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate);
                $this->sinri_log("syncJushita->SyncTaobaoService->SyncTaobaoOrder begin, request: ".json_encode($request));
                // print_r($request);
                $response=$client->SyncTaobaoOrder($request);
                $this->sinri_log("syncJushita->SyncTaobaoService->SyncTaobaoOrder end, response: ".json_encode($response));
                // print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            // echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
            $this->sinri_log('Time cost: '.(microtime(true)-$start)." Then sleep 0.5 sec...");
            usleep(500000);
        }    

        $end_of_action = microtime(true);
        $this->sinri_log('SyncTaobaoOrder(appkey='.$appkey.',hours='.$hours.',group='.$group.',endDate='.$endDate.')'.
            ' done in '.($end_of_action-$start_of_action).'s'
        );
 
    }  
    
    /**
	 * 从聚石塔上同步订单同步淘宝直销订单
	 */
    public function actionSyncJushitaOrder($appkey=null,$hours=1,$group=0,$endDate=null)
    {
        $start_of_action = microtime(true);

        // 不启用订单同步的列表
        $exclude_list=array
        (
        );
        ini_set('default_socket_timeout', 600);
        
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	if( strstr($endDate, ",") ){
        		$endDates = explode("," , $endDate) ;
        		if(count($endDates)>1){ 
        			$endDate = $endDates[0]. " " . $endDates[1] ;
        		} else{ 
        			$endDate = $endDates[0]." 00:00:00"; ; 
        		} 
        	}
        	else{
        		$endDate = $endDate." 00:00:00";
        	}
        }
        
     	// echo("[".date('c')."] "." sync order start endDate:".$endDate."\n");
        $this->sinri_log('SyncJushitaOrder(appkey='.$appkey.',hours='.$hours.',group='.$group.',endDate='.$endDate.')');
        
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list)){
                $this->sinri_log('appkey in exclude_list, continue');
                continue;
            }

            if($appkey!==null && $appkey!=$taobaoShop['application_key']){
                $this->sinri_log('appkey is not given one, continue');
                continue;
            }

            $debug_rec=array();

            $this->sinri_log("BEGIN_SYNC_JUSHITA_ORDER".$taobaoShop['nick']." From Jushita To Sync.");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $this->sinri_log('--- Now Begin To Insert ---');

                $t1 = microtime(true);

            	//获取没有收获确认的订单。将没有收获确认的订单插入到数据库中
            	//获取所有的订单，不限制状态
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"status"=>'',"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                $this->sinri_log('syncJushita->SyncTaobaoService->getTidsByKeyTime begin, with request: '.json_encode($request));
                // print_r($request);
                $response=$client->getTidsByKeyTime($request);
                $this->sinri_log('syncJushita->SyncTaobaoService->getTidsByKeyTime end, with response: '.json_encode($response));
                // print_r($response);
                $syncTids = array();
                if(isset($response->return) && !empty($response->return)){
	                $allTids = is_array($response->return)?$response->return:array($response->return);
	                // $this->sinri_log(microtime(true)." before chunk allTids to blocks.");
	                //将所有Tid分割成一段一段的tid数组
	                $allTids_group = array_chunk($allTids,500,true);
	                // var_dump($allTids_group);
	                // $this->sinri_log(microtime(true)." before execute same killer SQL.");
	                $existTids = array();
	                foreach ( $allTids_group as $allTids_item) {
       					$sql = "select tid from ecshop.sync_taobao_order_info where tid in (";
						foreach ( $allTids_item as $tid) {
		       				$sql .= "'$tid',";
						}
						$sql = substr($sql,0,strlen($sql)-1);
						$sql .= ")";
						$this->sinri_log(microtime(true)." Same-Killer SQL: ".$sql);
						$existTidsItem =  $this->getDb()->createCommand($sql)->queryColumn();
						$this->sinri_log("existTidsItem sql get: count = ".count($existTidsItem));
						$existTids = array_merge($existTids,$existTidsItem);
						$this->sinri_log("existTids marge to: count = ".count($existTids));
					}
                    //$syncTids保存所有需要插入的数据
                    $syncTids = array_diff($allTids,$existTids);
					$this->sinri_log("array_diff(allTids,existTids) count = ".count($syncTids));
	                if(!($syncTids)){
	                	$this->sinri_log(//microtime(true).' '.
                            "All Orders Has been Sync."
                        );
	                }
	                
                }else{
                	continue;
                }

                $t2 = microtime(true);
                $debug_rec['I_GetNewOrdersFromJushitaCount']=count($syncTids);
                $debug_rec['I_GetNewOrdersFromJushitaTime']=$t2-$t1;

                
                //获取已经收货确认的订单，将确认订单更新到数据库中
                //将收获确认的订单和未收获确认且过滤过的订单meger在一起，做下面的update或者insert的操作
                foreach ($syncTids as $tid){
                	if(empty($tid))  continue;  //不知为啥finishedTids会为空
	                try {	
	                	$tidRequest= array("tid"=>$tid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
	                	// var_dump(microtime(true)." ".$tid);
                        // $this->sinri_log('Sync Tid='.$tid.' From Jushita to Sync...');
	                	$tidResponse=$client->getTaobaoOrdersByTid($tidRequest);
	                	$this->insertTaobaoOrders($tidResponse->return,"insert");
					}
					catch( Exception $e ) {
						echo("| tid:".$tid."  Exception: ".$e->getMessage()."\n");
					}
                }

                $t3 = microtime(true);
                $debug_rec['II_InsertNewOrdersFromJushitaTime']=$t3-$t2;

                
                $this->sinri_log('--- Now Begin To Update ---');

                $updateTids = array();
                
                
                
                //获取聚石塔上已经完结的订单，再与线上的做匹配，查看哪些没有完结，就将其更新下来
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"status"=>'FINISHED',"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                $this->sinri_log('syncJushita->SyncTaobaoService->getTidsByKeyTime begin, with request: '.json_encode($request));
                // print_r($request);
                $response=$client->getTidsByKeyTime($request);
                $this->sinri_log('syncJushita->SyncTaobaoService->getTidsByKeyTime end, with response: '.json_encode($response));
                // print_r($response);
				
				$updateTids_no_finish = array();
 				if(isset($response->return) && !empty($response->return)){
                	$t4 = microtime(true);
	                $debug_rec['III_CheckFinishedOrdersFromJushitaTime']=$t4-$t3;
					
					$finishTids = is_array($response->return)?$response->return:array($response->return);
	                
	                if($finishTids){
	                	$finishTids_groups = array_chunk($finishTids,500,true);
	                	foreach ( $finishTids_groups as $finishTids_item ) {
		              		$sql = "select tid from ecshop.sync_taobao_order_info where tid in (";
			                foreach ( $finishTids_item as $tid ) {
			      				$sql .= "'$tid',";
							}
							$sql = substr($sql,0,strlen($sql)-1);
							$sql .= ") and status not in ('TRADE_CLOSED','TRADE_CLOSED_BY_TAOBAO','TRADE_FINISHED')";
							$this->sinri_log('sql: '.$sql);
							$updateTids =  $this->getDb()->createCommand($sql)->queryColumn();
							$this->sinri_log("count(updateTids)=".count($updateTids));
							$updateTids_no_finish = array_merge($updateTids_no_finish,$updateTids);
						}
						$this->sinri_log("Array updateTids_no_finish count=".count($updateTids_no_finish));
		                if(!$updateTids_no_finish){
		                	$this->sinri_log("All Orders are in Finished Status.");
		                }
	                }
	
	                $t5 = microtime(true);
	                $debug_rec['IV_CheckFinishedOrdersFromSyncTime']=$t5-$t4;
	               
	                
                }
				
				
				
				$t3_1 = microtime(true);
				//获取聚石塔上状态为WAIT_BUYER_CONFIRM_GOODS的订单
                $request_wbcg=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"status"=>'WAIT_BUYER_CONFIRM_GOODS',"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                $this->sinri_log('syncJushita->SyncTaobaoService->getTidsByKeyTime about WAIT_BUYER_CONFIRM_GOODS begin, with request: '.json_encode($request_wbcg));
                $response_wbcg=$client->getTidsByKeyTime($request_wbcg);
                $this->sinri_log('syncJushita->SyncTaobaoService->getTidsByKeyTime about WAIT_BUYER_CONFIRM_GOODS end, with response: '.json_encode($response_wbcg));
                
                $wbcgTids = array();
                if(isset($response_wbcg->return) && !empty($response_wbcg->return)){
                	$t4_1 = microtime(true);
	                $debug_rec['III_Check WAIT_BUYER_CONFIRM_GOODS OrdersFromJushitaTime']=$t4_1-$t3_1;
                	$tempTids = is_array($response_wbcg->return)?$response_wbcg->return:array($response_wbcg->return);
                	if($tempTids){
	                	$tempTids_groups = array_chunk($tempTids,500,true);
	                	foreach ( $tempTids_groups as $wbcgTids_item ) {
		              		$sql = "select tid from ecshop.sync_taobao_order_info where tid in (";
			                foreach ( $wbcgTids_item as $tid ) {
			      				$sql .= "'$tid',";
							}
							$sql = substr($sql,0,strlen($sql)-1);
							$sql .= ") and status not in ('WAIT_BUYER_CONFIRM_GOODS')";
							$this->sinri_log('sql: '.$sql);
							$updateTids =  $this->getDb()->createCommand($sql)->queryColumn();
							$this->sinri_log("count(updateTids)=".count($updateTids));
							$wbcgTids = array_merge($wbcgTids,$updateTids);
						}
						$this->sinri_log("Array wbcgTids count=".count($wbcgTids));
		                if(!$wbcgTids){
		                	$this->sinri_log("All selected Orders are in WAIT_BUYER_CONFIRM_GOODS Status.");
		                }
	                }
	                
	                $t5_1 = microtime(true);
	                $debug_rec['IV_Check WAIT_BUYER_CONFIRM_GOODS OrdersFromSyncTime']=$t5_1-$t4_1;
	                
	                
                }
				
				$t6_1 = microtime(true);
				$updateTids_no_finish = array_merge($updateTids_no_finish,$wbcgTids);
				if(!empty($updateTids_no_finish)){
					foreach ( $updateTids_no_finish as $updateTid) {
						if(empty($updateTid))  continue;  //不知为啥finishedTids会为空
		                try {	
		                	$tidRequest= array("tid"=>$updateTid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
		                	// var_dump(microtime(true)." ".$tid);
	                        // $this->sinri_log('Sync Tid='.$tid.' From Jushita to Sync as Finished...');
		                	$tidResponse=$client->getTaobaoOrdersByTid($tidRequest);
							$this->insertTaobaoOrders($tidResponse->return,"update");
						}
						catch( Exception $e ) {
							echo("| tid:".$tid."  Exception: ".$e->getMessage()."\n");
						}
					}
				}else{
					$this->sinri_log("There is no orders to be updated in WAIT_BUYER_CONFIRM_GOODS or Finished status");
				}
				
				$t6 = microtime(true);
                $debug_rec['IV_Update Finished and WAIT_BUYER_CONFIRM_GOODS OrdersFromSyncTime']=$t6-$t6_1;
                $debug_rec['IV_Update Finished and WAIT_BUYER_CONFIRM_GOODS OrdersFromSyncCount']=count($updateTids_no_finish);
                
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            // echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
            $this->sinri_log('Time cost: '.(microtime(true)-$start)." Then sleep 0.5 sec...");
            
            usleep(500000);

            $debug_rec['V_AllTimeCost_WithSleep']=(microtime(true)-$start);

            $this->sinri_log('SINRI_DEBUG_150908_ActionSyncJushitaOrder|'.json_encode($debug_rec));
        } 

        $end_of_action = microtime(true);   	
        $this->sinri_log('SyncJushitaOrder(appkey='.$appkey.',hours='.$hours.',group='.$group.',endDate='.$endDate.')'.
            ' done in '.($end_of_action-$start_of_action).'s'
        );
    }  
    
     /**
	 * 将获取出来的订单插入到数据库中
	 * 
	 * 怎么保证PHP的事务性
	 *
	 * 
	 * @return array
	 */
    protected function insertTaobaoOrders($taobaoOrders,$act){
    	try{
    		$this->getDb()->createCommand("START TRANSACTION")->execute();
	    	if(isset($taobaoOrders->created)) $taobaoOrders->created = $this->getPHPDate($taobaoOrders->created);
	    	if(isset($taobaoOrders->pay_time)) $taobaoOrders->pay_time = $this->getPHPDate($taobaoOrders->pay_time);
	    	if(isset($taobaoOrders->modified)) $taobaoOrders->modified = $this->getPHPDate($taobaoOrders->modified);
	    	if(isset($taobaoOrders->end_time)) $taobaoOrders->end_time = $this->getPHPDate($taobaoOrders->end_time);
	    	if(isset($taobaoOrders->consign_time)) $taobaoOrders->consign_time = $this->getPHPDate($taobaoOrders->consign_time);
	    	if(isset($taobaoOrders->timeout_action_time)) $taobaoOrders->timeout_action_time = $this->getPHPDate($taobaoOrders->timeout_action_time);
	    	if(isset($taobaoOrders->created_stamp)) $taobaoOrders->created_stamp = $this->getPHPDate($taobaoOrders->created_stamp);
	    	if(isset($taobaoOrders->last_updated_stamp)) $taobaoOrders->last_updated_stamp = $this->getPHPDate($taobaoOrders->last_updated_stamp);
	    	
	    	$builder = $this->getDb()->getCommandBuilder ();
			$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_order_info');
			$this->sinri_log("tid ".$taobaoOrders->tid." ".$act." -> ".$taobaoOrders->status);
			if($act == "update"){//交易完成的订单，等待订单信息更新完成之后，就直接return掉。不执行下面的订单商品和订单优惠的更新
				$builder->createUpdateCommand($table, $taobaoOrders, new CDbCriteria(array(
                    "condition" => "tid = :tid" , 
                    "params" => array(
                        "tid"=>$taobaoOrders->tid
                    )
                )))->execute();
			}else{
				$builder->createInsertCommand($table, $taobaoOrders)->execute();
			}
			
			$taobaoOrderGoodsList = isset($taobaoOrders->taobaoOrderGoodsList) ? $taobaoOrders->taobaoOrderGoodsList : array();
			$taobaoOrderGoodsList = is_array($taobaoOrderGoodsList)?$taobaoOrderGoodsList:array($taobaoOrderGoodsList);
			
			//插入taobaoOrderGoods
				foreach ($taobaoOrderGoodsList  as $taobaoOrderGoods ) {
		       		if(isset($taobaoOrderGoods->end_time)) $taobaoOrderGoods->end_time = $this->getPHPDate($taobaoOrderGoods->end_time);
					if(isset($taobaoOrderGoods->consign_time)) $taobaoOrderGoods->consign_time = $this->getPHPDate($taobaoOrderGoods->consign_time);
					if(isset($taobaoOrderGoods->timeout_action_time)) $taobaoOrderGoods->timeout_action_time = $this->getPHPDate($taobaoOrderGoods->timeout_action_time);
					if(isset($taobaoOrderGoods->created_stamp)) $taobaoOrderGoods->created_stamp = $this->getPHPDate($taobaoOrderGoods->created_stamp);
					if(isset($taobaoOrderGoods->last_update_stamp)) $taobaoOrderGoods->last_update_stamp = $this->getPHPDate($taobaoOrderGoods->last_update_stamp);
					$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_order_goods');
					if($act == 'update'){
						$builder->createUpdateCommand($table, $taobaoOrderGoods, new CDbCriteria(array(
		                    "condition" => "oid = :oid" , 
		                    "params" => array(
		                        "oid"=>$taobaoOrderGoods->oid
		                    )
                		)))->execute();
					}else{
						$builder->createInsertCommand($table, $taobaoOrderGoods)->execute();
					}
				}
			
			$taobaoOrderPromotions = isset($taobaoOrders->taobaoOrderPromotions)?$taobaoOrders->taobaoOrderPromotions:array();
			$taobaoOrderPromotions = is_array($taobaoOrderPromotions)?$taobaoOrderPromotions:array($taobaoOrderPromotions);
			//当promotions不为空时，才做下面的操作，往promotion表中插入
				//插入taobaoOrderPromotion
					foreach ($taobaoOrderPromotions as $taobaoOrderPromotion) {
	       				if(isset($taobaoOrderPromotion->created_stamp)) $taobaoOrderPromotion->created_stamp = $this->getPHPDate($taobaoOrderPromotion->created_stamp);
						if(isset($taobaoOrderPromotion->last_update_stamp)) $taobaoOrderPromotion->last_update_stamp = $this->getPHPDate($taobaoOrderPromotion->last_update_stamp);
						$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_order_promotion');
						if($act=="update"){
							$builder->createUpdateCommand($table, $taobaoOrderPromotion, new CDbCriteria(array(
			                    "condition" => "id = :id and promotion_name = :promotion_name" , 
			                    "params" => array(
			                        "id"=>$taobaoOrderPromotion->id,
			                        "promotion_name"=>$taobaoOrderPromotion->promotion_name
			                    )
	                		)))->execute();
						}else{
							$builder->createInsertCommand($table, $taobaoOrderPromotion)->execute();
						}
						
					}
			$this->getDb()->createCommand("COMMIT")->execute();
    	}catch(Exception $e){
    		$this->getDb()->createCommand("rollback")->execute();
    		echo 'Message: ' .$e->getMessage();
    	}
    }
    
    /*
     * 从淘宝平台上同步淘宝退款留言/凭证
	 */
    public function actionSyncTaobaoRefundMes($appkey=null,$hours=1,$group=0,$endDate=null)
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
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
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
                $response=$client->syncTaobaoRefundMes($request);
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
    
    /*
     * 从聚石塔上同步淘宝退款留言/凭证 
     */
    public function actionSyncJushitaRefundMes($appkey=null,$hours=1,$group=0,$endDate=null){
     	ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
      	echo("[".date('c')."] "." sync refund message start endDate:".$endDate."\n");
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync refund message start \n");
            $start = microtime(true);
            // 从聚石塔上同步退款留言
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getRefundMesIdsByAppkey($request);
                print_r($response);
                $ids = null;
                $ids = isset($response->return)?$response->return:array();
                $ids = is_array($ids)?$ids:array($ids);
                if(empty($ids)){
                	continue;
                }
                $sql = "select id from ecshop.sync_taobao_refund_message where id in (";
                foreach ( $ids as $id ) {
       				$sql .= "'$id',";
				}
				$sql = substr($sql,0,strlen($sql)-1);
                $sql .=	")";
                
                $existedIds = $this->getDb()->createCommand($sql)->queryColumn();
                
                foreach ( $ids as $id ) {
       				$refundMesRequest = array("id"=>$id,'username'=>JSTUsername,'password'=>md5(JSTPassword));
					print_r($refundMesRequest);
					$refundMesResponse = $client->getRefundMesById($refundMesRequest);
					print_r($refundMesResponse);
					$refundMes = isset($refundMesResponse->return)?$refundMesResponse->return:null;
					if(!empty($refundMes)){
						$this->insertOrUpdateRefundMes($refundMes,$existedIds);
					}
				}
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
            usleep(500000);
        }
     }
     
    protected function insertOrUpdateRefundMes($refundMes,$existedIds){
    	if(isset($refundMes->created)) $refundMes->created = $this->getPHPDate($refundMes->created);
    	
    	$builder = $this->getDb()->getCommandBuilder ();
    	$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_refund_message');
    	
    	if(in_array($refundMes->id,$existedIds)){
    		$builder->createUpdateCommand($table, $refundMes, new CDbCriteria(array(
                    "condition" => "id = :id" , 
                    "params" => array(
                        "id"=>$refundMes->id
                    )
               )))->execute();
    	}else{
    		$builder->createInsertCommand($table, $refundMes)->execute();
    	}
    }
    /*
     * 从淘宝平台上同步淘宝退款
	 */
    public function actionSyncTaobaoRefund($appkey=null,$hours=1,$group=0,$endDate=null)
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
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
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
                $response=$client->syncTaobaoRefund($request);
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
    
    public function actionSyncJushitaRefund($appkey=null,$hours=1,$group=0,$endDate=null){
        ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
      	echo("[".date('c')."] "." sync order start endDate:".$endDate."\n");
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync item start \n");
            $start = microtime(true);
            // 从聚石塔上同步商品
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getRefundIdsByAppkey($request);
               // $response=$client->SyncTaobaoItem('f1cfc3f7859f47fa8e7c150c2be35bfc');
               //得到num_iid的数量
                print_r($response);
                $refundIds = null;
                $refundIds = isset($response->return)?$response->return:array();
                $refundIds = is_array($refundIds)?$refundIds:array($refundIds);
                if(empty($refundIds)){
                	continue;
                }
                $sql = "select refund_id from ecshop.sync_taobao_refund where refund_id in (";
                foreach ( $refundIds as $refund_id ) {
       				$sql .= "'$refund_id',";
				}
				$sql = substr($sql,0,strlen($sql)-1);
                $sql .=	")";
                
                $existedRefundIds = $this->getDb()->createCommand($sql)->queryColumn();
                
                foreach ( $refundIds as $refundId ) {
       				$refundRequest = array("refundId"=>$refundId,'username'=>JSTUsername,'password'=>md5(JSTPassword));
					print_r($refundRequest);
					$refundResponse = $client->getRefundById($refundRequest);
					print_r($refundResponse);
					$refund = isset($refundResponse->return)?$refundResponse->return:null;
					if(!empty($refund)){
						$this->insertOrUpdateRefund($refund,$existedRefundIds);
					}
				}
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
            usleep(500000);
        }
    }
    
    protected function insertOrUpdateRefund($refund,$existedRefundIds){
    	if(isset($refund->created)) $refund->created = $this->getPHPDate($refund->created);
    	if(isset($refund->modified)) $refund->modified = $this->getPHPDate($refund->modified);
    	if(isset($refund->create_timestamp)) $refund->create_timestamp = $this->getPHPDate($refund->create_timestamp);
    	if(isset($refund->last_update_timestamp)) $refund->last_update_timestamp = $this->getPHPDate($refund->last_update_timestamp);
    	
    	$builder = $this->getDb()->getCommandBuilder ();
    	$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_refund');
    	
    	if(in_array($refund->refund_id,$existedRefundIds)){
    		$builder->createUpdateCommand($table, $refund, new CDbCriteria(array(
                    "condition" => "refund_id = :refund_id" , 
                    "params" => array(
                        "refund_id"=>$refund->refund_id
                    )
               )))->execute();
    	}else{
    		$builder->createInsertCommand($table, $refund)->execute();
    	}
    }
    
    //将sync_taobao_refund的数据转移到taobao_refund表中
    public function actionTaobaoRefund($appkey=null,$hours=1,$group=0,$endDate=null){
    	ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        	$startDate = date('Y-m-d H:i:s',time()-$hours*3600);
        }
        else {
        	$endDate = $endDate;
        	$startDate = date('Y-m-d H:i:s',strtotime($endDate)-$hours*3600);
        }
      	echo("[".date('c')."] "." sync order start endDate:".$endDate.",startDate: ".$startDate."\n");
      	foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync item start \n");
            $start = microtime(true);
            // 从sync_taobao_refund同步退款信息到taobao_refund
            
            $sql = "select estr.refund_id sync_refund_id,etr.refund_id refund_id
			from ecshop.sync_taobao_refund estr
			left join ecshop.taobao_refund etr on etr.refund_id = estr.refund_id
			where estr.last_update_timestamp >= '$startDate'
			and estr.last_update_timestamp < '$endDate'
			and estr.application_key = '".$taobaoShop['application_key']."'";
			
			$refundList = $this->getDb()->createCommand($sql)->queryAll();
			
			foreach ( $refundList as $item) {
				if($item['refund_id'] == ''){//插入数据
					$insertsql = "insert into ecshop.taobao_refund 
						select null,estr.refund_id,estr.tid,estr.title,estr.buyer_nick,estr.seller_nick,estr.total_fee,estr.status,estr.created,estr.refund_fee,estr.oid,estr.good_status,estr.company_name,estr.payment,estr.sid,estr.reason,estr.descText,estr.has_good_return,estr.modified,estr.order_status,eoi.order_id,estr.application_key,null,null,null
						from ecshop.sync_taobao_refund estr
						left join ecshop.ecs_order_info eoi on eoi.taobao_order_sn = estr.tid
						where estr.refund_id = '".$item['sync_refund_id']."'";
					$this->getDb()->createCommand($sql)->execute();
					var_dump($item['refund_id']."退款已经插入到taobao_refund");
				}else{//更新数据
					$updatesql = "update ecshop.taobao_refund etr
						inner join ecshop.sync_taobao_refund estr on estr.refund_id = etr.refund_id and estr.refund_id = '".$item['sync_refund_id']."'
						LEFT JOIN ecshop.ecs_order_info eoi on eoi.taobao_order_sn = estr.tid
						set etr.total_fee=estr.total_fee,
						etr.status=estr.status,
						etr.refund_fee=estr.refund_fee,
						etr.goods_status=estr.good_status,
						etr.sid=estr.sid,
						etr.payment=estr.payment,
						etr.reason=estr.reason,
						etr.description=estr.descText,
						etr.has_goods_return=estr.has_good_return,
						etr.modified=estr.modified,
						etr.order_status=estr.order_status,
						etr.order_id=eoi.order_id";
					$this->getDb()->createCommand($sql)->execute();
					var_dump($item['refund_id']."退款已经更新到taobao_refund");
				}
			}
			
            usleep(500000);
        }
    }
    
    //从淘宝下载物流信息
    public function actionSyncTaobaoLogistics($appkey=null,$group=0,$hours=360,$endDate=null)
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
        	$startDate = date('Y-m-d H:i:s',time()-$hours*3600);
        }
        else {
        	$endDate = $endDate;
        	$startDate = date('Y-m-d H:i:s',strtotime($endDate)-$hours*3600);
        }
      	echo("[".date('c')."] "." sync order start endDate:".$endDate.",startDate: ".$startDate."\n");
      	
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync logistics start \n");
            $start = microtime(true);
            // 下载物流信息
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate);
                print_r($request);
                $response=$client->SyncTaobaoTrace($request);
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
    
     //从淘宝下载物流信息
    public function actionSyncJushitaLogistics($appkey=null,$group=0,$hours=24,$endDate=null)
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
        	$startDate = date('Y-m-d H:i:s',time()-$hours*3600);
        }
        else {
        	$endDate = $endDate;
        	$startDate = date('Y-m-d H:i:s',strtotime($endDate)-$hours*3600);
        }
      	echo("[".date('c')."] "." sync order start endDate:".$endDate.",startDate: ".$startDate."\n");
      	
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoService;
        foreach($this->getTaobaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync logistics start \n");
            $start = microtime(true);
            // 下载物流信息
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getLogisticsTraceTids($request);
                print_r($response);
                if(isset($response->return) && count($response->return)){
                	foreach ( $response->return as $tid) {
						$traceRequest=array('tid'=>$tid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
						print_r($traceRequest);
						$traceResponse=$client->getLogisticsTraceByTid($traceRequest);
						if(isset($traceResponse->return)){
							$this->insertLogistics($traceResponse->return);
						}
						var_dump("tid:".$tid." already insert");
					}
                }
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
            usleep(500000);
        }
        
    }
    
    public function insertLogistics($logisticsTrace){
    	if(isset($logisticsTrace->created_stamp)) $logisticsTrace->created_stamp = $this->getPHPDate($logisticsTrace->created_stamp);
    	if(isset($logisticsTrace->last_update_stamp)) $logisticsTrace->last_update_stamp = $this->getPHPDate($logisticsTrace->last_update_stamp);
    	
    	$sql = "insert into ecshop.`sync_taobao_logistics_trace` (
			party_id,application_key,out_sid,status,tid,company_name,created_stamp,last_update_stamp,trace_status,trace_time
		)values (
			'{$logisticsTrace->party_id}','{$logisticsTrace->application_key}','{$logisticsTrace->out_sid}','{$logisticsTrace->status}','{$logisticsTrace->tid}','{$logisticsTrace->company_name}','{$logisticsTrace->created_stamp}','{$logisticsTrace->last_update_stamp}','{$logisticsTrace->trace_status}','{$logisticsTrace->trace_time}'
		)ON DUPLICATE KEY UPDATE 
		last_update_stamp='{$logisticsTrace->last_update_stamp}',trace_status='{$logisticsTrace->trace_status}',trace_time='{$logisticsTrace->trace_time}'";
		$this->getDb()->createCommand($sql)->query();
//		$db->query($sql);
		if(isset($logisticsTrace->taobaoLogisticsTraceLists) && count($logisticsTrace->taobaoLogisticsTraceLists)>0){
			foreach ( $logisticsTrace->taobaoLogisticsTraceLists as $taobaoLogisticsTraceList) {
       			if(isset($taobaoLogisticsTraceList->created_stamp)) $taobaoLogisticsTraceList->created_stamp = $this->getPHPDate($taobaoLogisticsTraceList->created_stamp);
    			if(isset($taobaoLogisticsTraceList->last_update_stamp)) $taobaoLogisticsTraceList->last_update_stamp = $this->getPHPDate($taobaoLogisticsTraceList->last_update_stamp);
    	
       			$sql = "insert into ecshop.`sync_taobao_logistics_trace_list` (
							party_id,application_key,tid,out_sid,status_desc,status_time,created_stamp,last_update_stamp
						)values (
							'{$taobaoLogisticsTraceList->party_id}','{$taobaoLogisticsTraceList->application_key}','{$taobaoLogisticsTraceList->tid}','{$taobaoLogisticsTraceList->out_sid}','{$taobaoLogisticsTraceList->status_desc}','{$taobaoLogisticsTraceList->status_time}','{$taobaoLogisticsTraceList->created_stamp}','{$taobaoLogisticsTraceList->last_update_stamp}'
						)ON DUPLICATE KEY UPDATE 
						last_update_stamp='{$taobaoLogisticsTraceList->last_update_stamp}'";
				$this->getDb()->createCommand($sql)->query();
			}
		}
    }
    
    protected function getPHPDate($javaTime){
    	$phpTime = str_replace('T',' ',$javaTime);
    	$phpTime = str_replace('+08:00','',$phpTime);
    	return $phpTime;
    }
    
    protected function getOrdersProperty($taobaoOrders,$propertyName){
    	if(isset($taobaoOrders->$propertyName)){
    		if($taobaoOrders->$propertyName === 0){
    			return 0;
    		}else{
    			return $taobaoOrders->$propertyName;
    		}
    	}else{
    		return null;
    	}
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
                echo " 金佰利\n";
            	$sql .= " and party_id in ('65558') ";
            }
            elseif ($group == 2) {     //雀巢  
                echo " 雀巢 \n";
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
            elseif ($group == 6) {      //村淘专用(目前仅用于订单同步)
               echo "村淘专用\n";
            	$sql = "select * from taobao_shop_conf where status='OK' and shop_type = 'cuntao'";
            }
            else {            	// 非法参数
            	echo 'invad $group='.$group."\n";
            	$sql .= " and  1=0 ";
            }
            $list=$this->getDb()->createCommand($sql)->queryAll();
            $command=$this->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
            foreach($list as $key=>$item)
            $list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
        }
        return $list;
    }
    
    
    
    
    
    
    /**
	 * 取得master数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getDb()
    {
        if(!$this->db)
        {
            if(($this->db=Yii::app()->getComponent('db'))===null)
            $this->db=Yii::app()->getDb();
            $this->db->setActive(true);
        }
        return $this->db;
    }

    private function sinri_log($str){
        echo("[".date('c')."] ".$str.PHP_EOL);
    }
}

