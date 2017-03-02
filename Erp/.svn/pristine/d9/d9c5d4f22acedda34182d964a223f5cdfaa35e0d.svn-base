
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
class ErpSyncJushitaFenxiaoCommand extends CConsoleCommand
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
    /*
     * 同步淘宝分销上的商品
	 *
	 * @param string  $appkey
	 * @param integer $seconds
	 */
    public function actionSyncTaobaoFenxiaoItem($appkey=null)
    {
    	// 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 600);
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoFenxiaoShopList() as $taobaoShop)
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
                $response=$client->SyncTaobaoFenxiaoItem($request);
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
    
    
    public function actionSyncJushitaFenxiaoItems($appkey=null,$group=0){
    	// 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        ini_set('default_socket_timeout', 600);
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoFenxiaoShopList($group) as $taobaoShop)
        {
        	if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync item start \n");
            $start = microtime(true);
            // 从聚石塔上同步分销商品商品
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],'username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getCountFenxiaoNumIids($request);
               // $response=$client->SyncTaobaoItem('f1cfc3f7859f47fa8e7c150c2be35bfc');
               //得到num_iid的数量
                print_r($response);
                for ($i=1 ;$i <= $response->return/100+1; $i++){
       				$getPidsRequest = array("applicationKey"=>$taobaoShop['application_key'],'username'=>JSTUsername,'password'=>md5(JSTPassword),'page'=>$i);
					$getPidsResponse=$client->getPidsByPage($getPidsRequest);
					var_dump($getPidsResponse);
					$pids = is_array($getPidsResponse->return)?$getPidsResponse->return:array($getPidsResponse->return);
					foreach ( $pids as $pid ) {
       					$getItemRequest = array('pid'=>$pid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
       					$getItemResponse = $client->getTaobaoFenxiaoItemsByPid($getItemRequest);
//       					var_dump($getItemResponse);
       					if(empty($getItemResponse->return)){
       						var_dump($pid+"为空");
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
	 * 
	 */
    protected function insertOrUpdateTaobaoItem($fenxiaoItem){
    	try{
    		$this->getDb()->createCommand("START TRANSACTION")->execute();
	    	if(isset($fenxiaoItem->created)) $fenxiaoItem->created = $this->getPHPDate($fenxiaoItem->created);
	    	if(isset($fenxiaoItem->upshelf_time)) $fenxiaoItem->upshelf_time = $this->getPHPDate($fenxiaoItem->upshelf_time);
	    	if(isset($fenxiaoItem->modified)) $fenxiaoItem->modified = $this->getPHPDate($fenxiaoItem->modified);
	    	if(isset($fenxiaoItem->create_timestamp)) $fenxiaoItem->create_timestamp = $this->getPHPDate($fenxiaoItem->create_timestamp);
	    	if(isset($fenxiaoItem->last_update_timestamp)) $fenxiaoItem->last_update_timestamp = $this->getPHPDate($fenxiaoItem->last_update_timestamp);
	    	
	    	$builder = $this->getDb()->getCommandBuilder ();
			$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_fenxiao_items');
			var_dump($fenxiaoItem->pid);
			if(!$this->getDb()->createCommand("select * from ecshop.sync_taobao_fenxiao_items where pid = ".$fenxiaoItem->pid)->queryRow()){
				//假如原来不存在num_iid的数据，则需要使用插入语句
				$builder->createInsertCommand($table, $fenxiaoItem)->execute();
			}else{
				//否则使用更新语句
				$builder->createUpdateCommand($table, $fenxiaoItem, new CDbCriteria(array(
                    "condition" => "pid = :pid" , 
                    "params" => array(
                        "pid"=>$fenxiaoItem->pid
                    )
                )))->execute();
			}
			$fenxiaoItemSkus = isset($fenxiaoItem->taobaoFenxiaoItemsSkus)?$fenxiaoItem->taobaoFenxiaoItemsSkus:array();
			$fenxiaoItemSkus = is_array($fenxiaoItemSkus)?$fenxiaoItemSkus:array($fenxiaoItemSkus);
			
			//删除聚石塔上没有而本地有的sku
			$existSkus = $this->getDb()->createCommand("select sku_id from ecshop.sync_taobao_fenxiao_items_sku where pid = '".$fenxiaoItem->pid."'")->queryAll();
			if($existSkus){
				if(count($fenxiaoItemSkus) == 0){
					var_dump('delete sync_taobao_fenxiao_items_sku pid:'.$fenxiaoItem->pid);
					$this->getDb()->createCommand("delete from ecshop.sync_taobao_fenxiao_items_sku where pid = '".$fenxiaoItem->pid."'")->execute();
				}else{
					foreach ( $existSkus as $existSku ) {
						$flag = false;
//						var_dump('existSku:'.$existSku['sku_id']);
       					foreach ( $fenxiaoItemSkus as $fenxiaoItemSku) {
//       						var_dump('fenxiaoItemSku'.$fenxiaoItemSku['sku_id']);
       						if(strcmp($existSku['sku_id'], $fenxiaoItemSku->sku_id) == 0){
       							$flag = true;
       							break;
       						}
       					}
       					if(!$flag){
							var_dump('delete sync_taobao_fenxiao_items_sku sku_id:'.$existSku['sku_id']);
       						$this->getDb()->createCommand("delete from ecshop.sync_taobao_fenxiao_items_sku where sku_id = '".$existSku['sku_id']."'")->execute();
						}
					}
				}
			}
			
			foreach ( $fenxiaoItemSkus as $fenxiaoItemSku) {
       			if(isset($fenxiaoItemSku->create_timestamp)) $fenxiaoItemSku->create_timestamp = $this->getPHPDate($fenxiaoItemSku->create_timestamp);
       			if(isset($fenxiaoItemSku->last_update_timestamp)) $fenxiaoItemSku->last_update_timestamp = $this->getPHPDate($fenxiaoItemSku->last_update_timestamp);
				$builder = $this->getDb()->getCommandBuilder ();
				$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_fenxiao_items_sku');
				if(!$this->getDb()->createCommand("select * from ecshop.sync_taobao_fenxiao_items_sku where sku_id = ".$fenxiaoItemSku->sku_id)->queryRow()){
					//假如原来不存在sku_iid的数据，则需要使用插入语句
					$builder->createInsertCommand($table, $fenxiaoItemSku)->execute();
				}else{
					//否则使用更新语句
					$builder->createUpdateCommand($table, $fenxiaoItemSku, new CDbCriteria(array(
	                    "condition" => "sku_id = :sku_id" , 
	                    "params" => array(
	                        "sku_id"=>$fenxiaoItemSku->sku_id
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
    
      
    /**
	 * 从淘宝平台同步淘宝分销订单
	 */
    public function actionSyncTaobaoFenxiaoOrder($appkey=null,$hours=1)
    {
    	
            // 启用同步的列表
        $include_list = array
        (
//            'f2c6d0dacf32102aa822001d0907b75a' ,         // 乐其数码专营店
//            // 'd1ac25f28f324361a9a1ea634d52dfc0' ,         // 怀轩名品专营店
//            // 'fd42e8aeb24b4b9295b32055391e9dd2' ,         // oppo乐其专卖店
//            // '239133b81b0b4f0ca086fba086fec6d5' ,         // 贝亲官方旗舰店
//            // '11b038f042054e27bbb427dfce973307' ,         // 多美滋官方旗舰店
//            // 'ee0daa3431074905faf68cddf9869895' ,         // accessorize旗舰店
//            // 'ee6a834daa61d3a7d8c7011e482d3de5' ,         // 金奇仕官方旗舰店
//            // 'fba27c5113229aa0062b826c998796c6' ,         // 方广官方旗舰店
//            // 'f38958a9b99df8f806646dc393fdaff4' ,         // 阳光豆坊旗舰店
//            // '7f83e72fde61caba008bad0d21234104' ,         // nutricia官方旗舰店
//            // '62f6bb9e07d14157b8fa75824400981f',          // 雀巢官方旗舰店
//            'f1cfc3f7859f47fa8e7c150c2be35bfc',          // 金佰利官方旗舰店
//            '753980cc6efb478f8ee22a0ff1113538',          //gallo官方旗舰店
//            '85b1cf4b507b497e844c639733788480',          //安满官方旗舰店
//            '573d454e82ff408297d56fbe1145cfb9',          //金宝贝
//            'ee6a834daa61d3a7d8c7011e482d3de5',          //金奇仕
//            'dc7e418627d249ecb5295ee471a2152a',          //新百伦
//            '7626299ed42c46b0b2ef44a68083d49a',          //blackmores
//            '159f1daf405445eca885a4f7811a56b8',          //康贝
//            '923ec15fa8b34e4a8f30e5dd8230cdef',          //安怡
        );
        ini_set('default_socket_timeout', 600);
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoFenxiaoShopList() as $taobaoShop)
        {
//        	if(!in_array($taobaoShop['application_key'],$include_list))
//            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

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
	 * 从淘宝平台 同步淘宝分销 经销订单
	 */
    public function actionSyncTaobaoFenxiaoDealerOrder($appkey=null,$hours=3)
    {
    	
            // 启用同步的列表
        $include_list = array
        (
//            'f2c6d0dacf32102aa822001d0907b75a' ,         // 乐其数码专营店
//            // 'd1ac25f28f324361a9a1ea634d52dfc0' ,         // 怀轩名品专营店
//            // 'fd42e8aeb24b4b9295b32055391e9dd2' ,         // oppo乐其专卖店
//            // '239133b81b0b4f0ca086fba086fec6d5' ,         // 贝亲官方旗舰店
//            // '11b038f042054e27bbb427dfce973307' ,         // 多美滋官方旗舰店
//            // 'ee0daa3431074905faf68cddf9869895' ,         // accessorize旗舰店
//            // 'ee6a834daa61d3a7d8c7011e482d3de5' ,         // 金奇仕官方旗舰店
//            // 'fba27c5113229aa0062b826c998796c6' ,         // 方广官方旗舰店
//            // 'f38958a9b99df8f806646dc393fdaff4' ,         // 阳光豆坊旗舰店
//            // '7f83e72fde61caba008bad0d21234104' ,         // nutricia官方旗舰店
//            // '62f6bb9e07d14157b8fa75824400981f',          // 雀巢官方旗舰店
//            'f1cfc3f7859f47fa8e7c150c2be35bfc',          // 金佰利官方旗舰店
//            '753980cc6efb478f8ee22a0ff1113538',          //gallo官方旗舰店
//            '85b1cf4b507b497e844c639733788480',          //安满官方旗舰店
//            '573d454e82ff408297d56fbe1145cfb9',          //金宝贝
//            'ee6a834daa61d3a7d8c7011e482d3de5',          //金奇仕
//            'dc7e418627d249ecb5295ee471a2152a',          //新百伦
//            '7626299ed42c46b0b2ef44a68083d49a',          //blackmores
//            '159f1daf405445eca885a4f7811a56b8',          //康贝
//            '923ec15fa8b34e4a8f30e5dd8230cdef',          //安怡
        );
        ini_set('default_socket_timeout', 600);
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoFenxiaoShopList() as $taobaoShop)
        {

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

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
	 * 从聚石塔同步淘宝分销订单
	 */
    public function actionSyncJushitaFenxiaoOrder($appkey=null,$hours=1,$group=0,$endDate=null)
    {
        // 不启用订单同步的列表
        $exclude_list=array
        (
        );
        ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
     	echo("[".date('c')."] "." sync Fenxiao order start endDate:".$endDate."\n");
        
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoFenxiaoShopList($group) as $taobaoShop)
        {

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync order start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,"status"=>'','username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getFXTidsByAppKeyTime($request);
                print_r($response);
                $syncTids = array();
                //获取出没有收获确认的订单，并且与本地数据做匹配。去掉已经下载过的数据
                if(isset($response->return) && !empty($response->return)){
	                $allTids = is_array($response->return)?$response->return:array($response->return);
					$sql = "select fenxiao_id from ecshop.sync_taobao_fenxiao_order_info where fenxiao_id in (";
	                foreach ($allTids as $tid) {
	       				$sql .= "'$tid',";
					}
					$sql = substr($sql,0,strlen($sql)-1);
					$sql .= ")";
					var_dump($sql);
					$existTids =  $this->getDb()->createCommand($sql)->queryColumn();
					if(!(array_diff($allTids,$existTids))){
	                	var_dump("订单都已经同步");
	                }
	                $syncTids = array_diff($allTids,$existTids);
	                
                }else{
                	continue;
                }
                
                //获取出收获确认的订单。
                //将收获确认的订单和未收获确认且过滤过的订单meger在一起，做下面的update或者insert的操作
	               
                foreach ($syncTids as $tid){
                	$tidRequest= array("tid"=>$tid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                	var_dump($tidRequest);
                	$tidResponse=$client->getTaobaoFXOrdersByTid($tidRequest);
                	$this->insertTaobaoFenxiaoOrders($tidResponse->return,"insert");
                }
                
                $updateTids = array();
                
                //获取聚石塔上已经完结的订单号
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,"status"=>'FINISHED','username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getFXTidsByAppKeyTime($request);
                print_r($response);
                
                if(!isset($response->return) || empty($response->return)){
                	continue; 
                }
                
                $finishTids = is_array($response->return)?$response->return:array($response->return);
                //再将聚石塔上已经完结，但是线上没有完结的订单筛选出来。  这些订单是需要做更新的
                if($finishTids){
                	$sql = "select fenxiao_id from ecshop.sync_taobao_fenxiao_order_info where fenxiao_id in (";
	                foreach ( $finishTids as $tid ) {
	      				$sql .= "'$tid',";
					}
					$sql = substr($sql,0,strlen($sql)-1);
					$sql .= ") and status not in ('TRADE_CLOSED','TRADE_CLOSED_BY_TAOBAO','TRADE_FINISHED')";
					var_dump($sql);
					$updateTids =  $this->getDb()->createCommand($sql)->queryColumn();
	                if(!$updateTids){
	                	var_dump("订单都已经处于完结状态");
	                }
	                 //$updateTids保存所有需要更新的订单号
                }
                
                foreach ( $updateTids as $updateTid) {
       				if(empty($updateTid))  continue;  //不知为啥finishedTids会为空
	                	$tidRequest= array("tid"=>$updateTid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
	                	var_dump($tidRequest);
	                	$tidResponse=$client->getTaobaoFXOrdersByTid($tidRequest);
						$this->insertTaobaoFenxiaoOrders($tidResponse->return,"update");
//					}
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
	 * 将获取出来的分销订单插入到数据库中
	 * 
	 * 怎么保证PHP的事务性
	 * @return array
	 */
    protected function insertTaobaoFenxiaoOrders($taobaoFenxiaoOrders,$act){
    	try{
    		$this->getDb()->createCommand("START TRANSACTION")->execute();
	    	if(isset($taobaoFenxiaoOrders->created)) $taobaoFenxiaoOrders->created = $this->getPHPDate($taobaoFenxiaoOrders->created);
	    	if(isset($taobaoFenxiaoOrders->pay_time)) $taobaoFenxiaoOrders->pay_time = $this->getPHPDate($taobaoFenxiaoOrders->pay_time);
	    	if(isset($taobaoFenxiaoOrders->modified)) $taobaoFenxiaoOrders->modified = $this->getPHPDate($taobaoFenxiaoOrders->modified);
	    	if(isset($taobaoFenxiaoOrders->end_time)) $taobaoFenxiaoOrders->end_time = $this->getPHPDate($taobaoFenxiaoOrders->end_time);
	    	if(isset($taobaoFenxiaoOrders->consign_time)) $taobaoFenxiaoOrders->consign_time = $this->getPHPDate($taobaoFenxiaoOrders->consign_time);
	    	if(isset($taobaoFenxiaoOrders->create_timestamp)) $taobaoFenxiaoOrders->create_timestamp = $this->getPHPDate($taobaoFenxiaoOrders->create_timestamp);
	    	if(isset($taobaoFenxiaoOrders->last_update_timestamp)) $taobaoFenxiaoOrders->last_update_timestamp = $this->getPHPDate($taobaoFenxiaoOrders->last_update_timestamp);
	    	$builder = $this->getDb()->getCommandBuilder ();
			$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_fenxiao_order_info');
			if($act == 'update'){
				$builder->createUpdateCommand($table, $taobaoFenxiaoOrders, new CDbCriteria(array(
                    "condition" => "fenxiao_id = :fenxiao_id" , 
                    "params" => array(
                        "fenxiao_id"=>$taobaoFenxiaoOrders->fenxiao_id
                    )
                )))->execute();
			}else{
				$builder->createInsertCommand($table, $taobaoFenxiaoOrders)->execute();
			}
			
			$fenxiao_suborders = is_array($taobaoFenxiaoOrders->fenxiao_suborders)?$taobaoFenxiaoOrders->fenxiao_suborders:array($taobaoFenxiaoOrders->fenxiao_suborders);
			//插入taobaoOrderGoods
			foreach ($fenxiao_suborders as $suborder ) {
	       		if(isset($suborder->created)) $suborder->created = $this->getPHPDate($suborder->created);
				if(isset($suborder->create_timestamp)) $suborder->create_timestamp = $this->getPHPDate($suborder->create_timestamp);
				if(isset($suborder->last_update_timestamp)) $suborder->last_update_timestamp = $this->getPHPDate($suborder->last_update_timestamp);
				$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_fenxiao_order_goods');
				if($act == 'update'){
					$builder->createUpdateCommand($table, $suborder, new CDbCriteria(array(
	                    "condition" => "fenxiao_sub_id = :fenxiao_sub_id" , 
	                    "params" => array(
	                        "fenxiao_sub_id"=>$suborder->fenxiao_sub_id
	                    )
	                )))->execute();
				}else{
					$builder->createInsertCommand($table, $suborder)->execute();
				}
			}
			
			$this->getDb()->createCommand("COMMIT")->execute();
    	}catch(Exception $e){
    		$this->getDb()->createCommand("rollback")->execute();
    		echo 'Message: ' .$e->getMessage();
    	}
    }
    
    /**
	 * 从聚石塔同步淘宝经销订单
	 */
    public function actionSyncJushitaFenxiaoDealerOrder($appkey=null,$hours=1,$group=0,$endDate=null)
    {
        // 不启用订单同步的列表
        $exclude_list=array
        (
        );
        ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
     	echo("[".date('c')."] "." sync Fenxiao Dealer order start endDate:".$endDate."\n");
        
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoFenxiaoShopList($group) as $taobaoShop)
        {

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync order start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,"status"=>'','username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                var_dump($request);
                $response=$client->getFXDealerTidsByAppKeyTime($request);
                print_r($response);
                if(!isset($response->return) || empty($response->return)){
                	continue; 
                }
             	$allTids = is_array($response->return)?$response->return:array($response->return);
				$sql = "select dealer_order_id from ecshop.sync_taobao_fenxiao_dealer_order where dealer_order_id in (";
                foreach ( $allTids as $tid) {
       				$sql .= "'$tid',";
				}
				$sql = substr($sql,0,strlen($sql)-1);
				$sql .= ")";
				$existTids =  $this->getDb()->createCommand($sql)->queryColumn();
				if(!(array_diff($allTids,$existTids))){
                	var_dump("订单都已经同步");
                }
                $syncTids = array_diff($allTids,$existTids);
                
                foreach ($syncTids as $tid){
                	$tidRequest= array("tid"=>$tid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                	$tidResponse=$client->getFXDealerOrderByTid($tidRequest);
                	$this->insertTaobaoFenxiaoDealerOrders($tidResponse->return,"insert");
                }
                $updateTids = array();
                var_dump($existTids);
                
                //获取在jushita上的已经完结的经销订单号
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,"status"=>'FINISHED','username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getFXDealerTidsByAppKeyTime($request);
                print_r($response);
                if(!isset($response->return) || empty($response->return)){
                	continue; 
                }
                
                $finishTids = is_array($response->return)?$response->return:array($response->return);
                
                //处理经销Dealer订单中的终结状态的
                if($finishTids){
                	$sql = "select dealer_order_id from ecshop.sync_taobao_fenxiao_dealer_order where dealer_order_id in (";
	                foreach ( $finishTids as $tid ) {
	      				$sql .= "'$tid',";
					}
					$sql = substr($sql,0,strlen($sql)-1);
					$sql .= ") and order_status not in ('TRADE_CLOSED','TRADE_CLOSED_BY_TAOBAO','TRADE_FINISHED')";
					var_dump($sql);
					$updateTids =  $this->getDb()->createCommand($sql)->queryColumn();
	                if(!$updateTids){
	                	var_dump("订单都已经处于完结状态");
	                }
//	                //$updateTids保存所有需要更新的订单号
                }
               
                foreach ( $updateTids as $updateTid) {
       				if(empty($updateTid))  continue;  //不知为啥finishedTids会为空
                	$tidRequest= array("tid"=>$updateTid,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                	var_dump($tidRequest);
                	$tidResponse=$client->getFXDealerOrderByTid($tidRequest);
						$this->insertTaobaoFenxiaoDealerOrders($tidResponse->return,"update");
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
	 * 将获取出来的分销订单插入到数据库中
	 * 
	 * 怎么保证PHP的事务性
	 * @return array
	 */
    protected function insertTaobaoFenxiaoDealerOrders($taobaoFenxiaoDealerOrders,$act){
    	try{
    		$this->getDb()->createCommand("START TRANSACTION")->execute();
	    	if(isset($taobaoFenxiaoDealerOrders->applied_time)) $taobaoFenxiaoDealerOrders->applied_time = $this->getPHPDate($taobaoFenxiaoDealerOrders->applied_time);
	    	if(isset($taobaoFenxiaoDealerOrders->modified_time)) $taobaoFenxiaoDealerOrders->modified_time = $this->getPHPDate($taobaoFenxiaoDealerOrders->modified_time);
	    	if(isset($taobaoFenxiaoDealerOrders->audit_time_supplier)) $taobaoFenxiaoDealerOrders->audit_time_supplier = $this->getPHPDate($taobaoFenxiaoDealerOrders->audit_time_supplier);
	    	if(isset($taobaoFenxiaoDealerOrders->audit_time_applier)) $taobaoFenxiaoDealerOrders->audit_time_applier = $this->getPHPDate($taobaoFenxiaoDealerOrders->audit_time_applier);
	    	if(isset($taobaoFenxiaoDealerOrders->create_timestamp)) $taobaoFenxiaoDealerOrders->create_timestamp = $this->getPHPDate($taobaoFenxiaoDealerOrders->create_timestamp);
	    	if(isset($taobaoFenxiaoDealerOrders->last_update_timestamp)) $taobaoFenxiaoDealerOrders->last_update_timestamp = $this->getPHPDate($taobaoFenxiaoDealerOrders->last_update_timestamp);
	    	$builder = $this->getDb()->getCommandBuilder ();
			$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_fenxiao_dealer_order');
			if($act == "insert"){
				$builder->createInsertCommand($table, $taobaoFenxiaoDealerOrders)->execute();
			}else{
				$builder->createUpdateCommand($table, $taobaoFenxiaoDealerOrders, new CDbCriteria(array(
                    "condition" => "dealer_order_id = :dealer_order_id" , 
                    "params" => array(
                        "dealer_order_id"=>$taobaoFenxiaoDealerOrders->dealer_order_id
                    )
                )))->execute();
			}
			
			$taobaoFenxiaoDealerOrderGoodsList = is_array($taobaoFenxiaoDealerOrders->taobaoFenxiaoDealerOrderGoodsList)?$taobaoFenxiaoDealerOrders->taobaoFenxiaoDealerOrderGoodsList:array($taobaoFenxiaoDealerOrders->taobaoFenxiaoDealerOrderGoodsList);
			
				foreach ($taobaoFenxiaoDealerOrderGoodsList  as $taobaoFenxiaoDealerOrderGoods ) {
					if(isset($taobaoFenxiaoDealerOrderGoods->create_timestamp)) $taobaoFenxiaoDealerOrderGoods->create_timestamp = $this->getPHPDate($taobaoFenxiaoDealerOrderGoods->create_timestamp);
					if(isset($taobaoFenxiaoDealerOrderGoods->last_update_timestamp)) $taobaoFenxiaoDealerOrderGoods->last_update_timestamp = $this->getPHPDate($taobaoFenxiaoDealerOrderGoods->last_update_timestamp);
					$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_fenxiao_dealer_order_goods');
					if($act == "insert"){
						$builder->createInsertCommand($table, $taobaoFenxiaoDealerOrderGoods)->execute();
					}else{
						$builder->createUpdateCommand($table, $taobaoFenxiaoDealerOrderGoods, new CDbCriteria(array(
		                    "condition" => "dealer_detail_id = :dealer_detail_id" , 
		                    "params" => array(
		                        "dealer_detail_id"=>$taobaoFenxiaoDealerOrderGoods->dealer_detail_id
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
    
    public function actionSyncTaobaoFenxiaoRefund($appkey = null,$days=1,$endDate=null){
    	 // 不启用订单同步的列表
        ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
     	echo("[".date('c')."] "." sync Fenxiao Dealer order start endDate:".$endDate."\n");
        
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
         foreach($this->getTaobaoFenxiaoShopList() as $taobaoShop)
        {

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync fenxiaoRefund start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days,"endDate"=>$endDate);
                print_r($request);
                $response=$client->SyncTaobaoFenxiaoRefund($request);
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
    
    public function actionSyncJushitaFenxiaoRefund($appkey=null,$endDate=null,$days=1){
    	 // 不启用订单同步的列表
        ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
     	echo("[".date('c')."] "." sync Fenxiao refund start endDate:".$endDate."\n");
        
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoFenxiaoShopList() as $taobaoShop)
        {

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync jushita fenxiaoRefund start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days,"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
                print_r($request);
                $response=$client->getTaobaoFenxiaoRefundIds($request);
                print_r($response);
                $subOrderIds = null;
                $subOrderIds =  isset($response->return)?$response->return:array();
                $subOrderIds = is_array($subOrderIds)?$subOrderIds:array($subOrderIds);
                if(empty($subOrderIds)){
                	continue;
                }
                $sql = "select sub_order_id from ecshop.sync_taobao_fenxiao_refund where sub_order_id in (";
                foreach ( $subOrderIds as $subOrderId ) {
      				$sql .= "'$subOrderId',";
				}
				$sql = substr($sql,0,strlen($sql)-1);
                $sql .=	")";
                $existedsubOrderIds = $this->getDb()->createCommand($sql)->queryColumn();
                foreach ( $existedsubOrderIds as $subOrderId ) {
       				$refundRequest = array("subOrderId"=>$subOrderId,'username'=>JSTUsername,'password'=>md5(JSTPassword));
					print_r($refundRequest);
					$refundResponse = $client->getTaobaoFenxiaoRefundById($refundRequest);
//					print_r($refundResponse);
					$fenxiaoRefund = isset($refundResponse->return)?$refundResponse->return:null;
					if(!empty($fenxiaoRefund)){
						$this->insertOrUpdateFenxiaoRefund($fenxiaoRefund,$existedsubOrderIds);
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
    
    protected function insertOrUpdateFenxiaoRefund($fenxiaoRefund,$existedsubOrderIds){
    	if(isset($fenxiaoRefund->refund_create_time)) $fenxiaoRefund->refund_create_time = $this->getPHPDate($fenxiaoRefund->refund_create_time);
    	if(isset($fenxiaoRefund->modified)) $fenxiaoRefund->modified = $this->getPHPDate($fenxiaoRefund->modified);
    	if(isset($fenxiaoRefund->timeout)) $fenxiaoRefund->timeout = $this->getPHPDate($fenxiaoRefund->timeout);
    	if(isset($fenxiaoRefund->created_time)) $fenxiaoRefund->created_time = $this->getPHPDate($fenxiaoRefund->created_time);
    	if(isset($fenxiaoRefund->last_update_time)) $fenxiaoRefund->last_update_time = $this->getPHPDate($fenxiaoRefund->last_update_time);
    	
    	$builder = $this->getDb()->getCommandBuilder ();
    	$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_fenxiao_refund');
    	if(in_array($fenxiaoRefund->sub_order_id,$existedsubOrderIds)){
    		$builder->createUpdateCommand($table, $fenxiaoRefund, new CDbCriteria(array(
                    "condition" => "sub_order_id = :sub_order_id" , 
                    "params" => array(
                        "sub_order_id"=>$fenxiaoRefund->sub_order_id
                    )
               )))->execute();	
    	}else{
    		$builder->createInsertCommand($table, $fenxiaoRefund)->execute();
    	}
    	
    	if(isset($fenxiaoRefund->taobaoFenxiaoBuyerRefund)){
    		$taobaoFenxiaoBuyerRefunds = is_array($fenxiaoRefund->taobaoFenxiaoBuyerRefund)?$fenxiaoRefund->taobaoFenxiaoBuyerRefund:array($fenxiaoRefund->taobaoFenxiaoBuyerRefund);
    		foreach ($taobaoFenxiaoBuyerRefunds as $taobaoFenxiaoBuyerRefund) {
       			if(isset($taobaoFenxiaoBuyerRefund->refund_create_time)) $taobaoFenxiaoBuyerRefund->refund_create_time = $this->getPHPDate($taobaoFenxiaoBuyerRefund->refund_create_time);
		    	if(isset($taobaoFenxiaoBuyerRefund->modified)) $taobaoFenxiaoBuyerRefund->modified = $this->getPHPDate($taobaoFenxiaoBuyerRefund->modified);
		    	if(isset($taobaoFenxiaoBuyerRefund->created_time)) $taobaoFenxiaoBuyerRefund->created_time = $this->getPHPDate($taobaoFenxiaoBuyerRefund->created_time);
		    	if(isset($taobaoFenxiaoBuyerRefund->last_update_time)) $taobaoFenxiaoBuyerRefund->last_update_time = $this->getPHPDate($taobaoFenxiaoBuyerRefund->last_update_time);
				
				$builder = $this->getDb()->getCommandBuilder ();
				$table = $this->getDb()->getSchema()->getTable('ecshop.sync_taobao_fenxiao_buyer_refund');
		    	if(in_array($taobaoFenxiaoBuyerRefund->sub_order_id,$existedsubOrderIds)){
		    		$builder->createUpdateCommand($table, $taobaoFenxiaoBuyerRefund, new CDbCriteria(array(
		                    "condition" => "sub_order_id = :sub_order_id" , 
		                    "params" => array(
		                        "sub_order_id"=>$taobaoFenxiaoBuyerRefund->sub_order_id
		                    )
		               )))->execute();	
		    	}else{
		    		$builder->createInsertCommand($table, $taobaoFenxiaoBuyerRefund)->execute();
		    	}
			}
    	}
    }
    /**
	 * 分销业务 经销商商品监控--从淘宝同步到聚石塔
	 */
	public function actionSyncFenxiaoTrademonitorOrder($appkey=null,$hours=3){
		$start_time = time();
		echo("[".date('c')."] SyncFenxiaoTrademonitorOrder start \n");
		$include_list = array(
//        	'f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
			'f1cfc3f7859f47fa8e7c150c2be35bfc', //金佰利
//			'bd497325e76542d0b5b88a4e8ddacc9f', //nb品牌站
        );

        // 远程服务
//		$client=Yii::app()->getComponent('romeo')->TaobaoOrderService;
		$client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach ($this->getTaobaoFenxiaoShopList() as $taobaoShop) {
            if(!in_array($taobaoShop['application_key'],$include_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." SyncFenxiaoTrademonitorOrder start \n");
            try {
                $request = array("hours" => $hours,"applicationKey" => $taobaoShop['application_key']);
                $response= $client->syncDistributionTrademonitor($request);
                print_r($response);
            } catch(Exception $e) {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            usleep(500000);
        }
		echo("[".date('c')."] SyncFenxiaoTrademonitorOrder end. total_time: " .(time()-$start_time) . " \n");
	}
	/**
	 * 从聚石塔同步到本地ecshop.sync_distribution_trade_monitor
	 */
	 public function actionSyncJushitaTrademonitorOrder($appkey=null,$hours=1,$endDate=null){
    	ini_set('default_socket_timeout', 600);
        if(empty($endDate)) {
        	$endDate = date('Y-m-d H:i:s',time());
        }
        else {
        	$endDate = $endDate." 00:00:00";
        }
      	echo("[".date('c')."] "." sync trade monitor start endDate:".$endDate."\n");
        // 远程服务
        $client=Yii::app()->getComponent('syncJushita')->SyncTaobaoFenxiaoService;
        foreach($this->getTaobaoFenxiaoShopList() as $taobaoShop)
        {
            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync trade monitor start \n");
            $start = microtime(true);
            try
            {
				$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate,'username'=>JSTUsername,'password'=>md5(JSTPassword));
				print_r($request);
                $response=$client->getTrademonitorIdsByAppkey($request);
                print_r($response);
                $ids = null;
                $ids = isset($response->return)?$response->return:array();
                $ids = is_array($ids)?$ids:array($ids);
                if(empty($ids)){
                	continue;
                }
                $sql = "select trade_monitor_id from ecshop.sync_distribution_trade_monitor where trade_monitor_id in (";
                foreach ( $ids as $id ) {
       				$sql .= "'$id',";
				}
				$sql = substr($sql,0,strlen($sql)-1);
                $sql .=	")";
                $existedIds = $this->getDb()->createCommand($sql)->queryColumn();
                
                foreach ( $ids as $id ) {
       				$tradeMonitorRequest = array("tradeMonitorId"=>$id,'username'=>JSTUsername,'password'=>md5(JSTPassword));
					print_r($tradeMonitorRequest);
					$tradeMonitorResponse = $client->getTradeMonitorById($tradeMonitorRequest);
					print_r($tradeMonitorResponse);
					$tradeMonitors = isset($tradeMonitorResponse->return)?$tradeMonitorResponse->return:null;
					if(!empty($tradeMonitors)){
						$this->insertOrUpdateTradeMonitor($tradeMonitors,$existedIds);
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
     protected function insertOrUpdateTradeMonitor($tradeMonitors,$existedIds){
    	if(isset($tradeMonitors->created)) $tradeMonitors->created = $this->getPHPDate($tradeMonitors->created);
    	
    	$builder = $this->getDb()->getCommandBuilder ();
    	$table = $this->getDb()->getSchema()->getTable('ecshop.sync_distribution_trade_monitor');
    	
    	if(in_array($tradeMonitors->trade_monitor_id,$existedIds)){
    		$builder->createUpdateCommand($table, $tradeMonitors, new CDbCriteria(array(
                    "condition" => "trade_monitor_id = :trade_monitor_id" , 
                    "params" => array(
                        "trade_monitor_id"=>$tradeMonitors->trade_monitor_id
                    )
               )))->execute();
    	}else{
    		$builder->createInsertCommand($table, $tradeMonitors)->execute();
    	}
    }
    /**
     * 将sync_distribution_trade_monitor的数据转移到
     * 	distribution_trademonitor_order,
     * 	distribution_trademonitor_order_goods,
     * 	distribution_trademonitor_order_action
     * 表中
     */
     public function actionTrademonitorOrderGoodsAction($appkey=null,$hours=1){
    	$start_time = time();
		echo("[".date('c')."] TrademonitorOrderGoodsAction start \n");
		$include_list = array(
//        	'f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
			'f1cfc3f7859f47fa8e7c150c2be35bfc', //金佰利
//			'bd497325e76542d0b5b88a4e8ddacc9f', //nb品牌站
        );

        // 远程服务
		$client=Yii::app()->getComponent('romeo')->TaobaoOrderService;
        foreach ($this->getTaobaoFenxiaoShopList() as $taobaoShop) {
            if(!in_array($taobaoShop['application_key'],$include_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." turn to order,goods,action start \n");
            try {
			$request = array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
			$response= $client->syncToOrderGoodsAction($request);

	            print_r($response);
            } catch(Exception $e) {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            usleep(500000);
        }
		echo("[".date('c')."] TrademonitorOrderGoodsAction end. total_time: " .(time()-$start_time) . " \n");
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
	 * 取得启用的淘宝分销店铺列表
	 * 
	 * @return array
	 */
    protected function getTaobaoFenxiaoShopList($group = 0)
    {
        static $list;
        if(!isset($list))
        {
            $sql="select tsc.*
				from ecshop.taobao_fenxiao_shop_conf tfsc
				inner join ecshop.taobao_shop_conf tsc on tsc.taobao_shop_conf_id = tfsc.taobao_fenxiao_shop_conf_id
				where tfsc.status='OK'";
            
            if($group == 0 ){   	// 全部业务组  不做处理
            }
            elseif ($group == 1) {      // 康贝
                echo " 康贝\n";
            	$sql .= " and party_id in ('65586') ";
            }
            elseif ($group == 2) {     //雀巢  保乐力加
                echo " 雀巢  保乐力加\n";
            	$sql .= " and party_id in ('65553','65551') ";
            }
            elseif ($group == 3) {   	//金佰利  金宝贝
                echo " 金佰利  金宝贝\n";
            	$sql .= " and party_id in ('65558','65574') ";
            }
            elseif ($group == 4) {      //除了康贝,雀巢,保乐力加,金佰利,金宝贝的其它组织
               echo "除了康贝,雀巢,保乐力加,金佰利,金宝贝的其它组织\n";
            	$sql .= " and party_id not in ('65586','65553','65551','65558','65574') ";
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
}
?>