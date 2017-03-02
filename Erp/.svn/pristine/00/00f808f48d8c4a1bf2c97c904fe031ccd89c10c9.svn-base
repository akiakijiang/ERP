<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2015-12-21
 * by hzhang1 webservice接口调用录单&批量录单
 */
class ErpSyncGenerateOrderCommand extends CConsoleCommand
{
	
    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
    
      
    
   /**
    * 单个录单调度
    */
    public function actionGenerateSaleOrder($order)
    {
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->GenerateSaleOrderService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
    		echo("[".date('c')."] ".$taobaoShop['nick']." syncKdtOrder start \n");
    		$start = microtime(true);
    
    		// 同步口袋通订单
    		try
    		{
    			$request=array("order"=>$order);
    			$response=$client->GenerateSaleOrder($request);
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
     * 转换口袋通订单
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
     public function actionTransferSaleOrder($distributor_id=null,$party_id=null,$file_id="")
    {
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->GenerateSaleOrderService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if($distributor_id!==null&&$distributor_id!=$taobaoShop['distributor_id'])
				continue;
    
    		if($party_id!==null&&$party_id!=$taobaoShop['party_id'])
				continue;
				
    		echo("[".date('c')."] 店铺distributor_id：".$taobaoShop['distributor_id']." TransferSaleOrder start \n");
			$start = microtime(true);
    
    		//转换批量录单调度
    		try
    		{
    			$request=array("distributor_id"=>$taobaoShop['distributor_id'],"party_id"=>$taobaoShop['party_id'],"fileId"=>$file_id);
				$response=$client->TransferSaleOrder($request);
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
     * 取得启用的口袋通店铺
     *
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		$sql="
				select distinct eioi.distributor_id,eioi.party_id from ecshop.ecs_import_order_file eiof
				inner join ecshop.ecs_import_order_info eioi on eiof.file_id = eioi.file_id
				where eiof.status = 'N' and eioi.status = 'N'";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    	}
    	return $list;
    }
}
?>
