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
class ErpSyncPinduoduoCommand extends CConsoleCommand
{
   
    /**
     * 订单同步
     */
	public function actionSyncPinduoduoOrder($appkey=null)
	{
		 
		ini_set('default_socket_timeout', 600);
		if(empty($endDate)) {
			$endDate = date('Y-m-d H:i:s',time());
		}
		else {
			$endDate = $endDate." 00:00:00";
		}
		echo("[".date('c')."] "." sync pinduoduo order start endDate:".$endDate."\n");
		
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncPinduoduoService;
		foreach($this->getPinduoduoShopList() as $taobaoShop)
		{
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncPinduoduoOrder start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key']);
				print_r($request);
				$response=$client->SyncPinduoduoOrder($request);
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
	 * 转换拼多多订单
	 */
 	public function actionPinduoduoOrderTransfer($appkey=null,$days=1)
	{

		ini_set('default_socket_timeout', 1200);
		
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncPinduoduoService;

		foreach($this->getPinduoduoShopList() as $taobaoShop)
		{
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." PinduoduoOrderTranfer start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
				print_r($request);
				$response=$client->PinduoduoOrderTransfer($request);
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
	 */
	public function actionSyncDeliverySend($appkey=null)
	{
		// 晚上不需要同步发货
		$h=date('H');
		if($h<9 || $h>22) { return; }
		ini_set('default_socket_timeout', 1200);
		$client=Yii::app()->getComponent('erpsync')->SyncPinduoduoService;
		foreach($this->getPinduoduoShopList() as $taobaoShop)
		{
		
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
		
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncPinduoduoSendDelivery start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key']);
				print_r($request);
				$response=$client->SyncPinduoduoSendDelivery($request);
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
     * 取得启用的拼多多店铺
     *
     * @return array
     */
    protected function getPinduoduoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
			$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'pinduoduo' ";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
}
?>
