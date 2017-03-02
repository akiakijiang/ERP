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
class ErpSyncBudweiserCommand extends CConsoleCommand
{	
	/**
	 * 同步微信人头马订单
	 */
	public function actionSyncBudweiserOrder($appkey=null,$days=6,$pageNo=0,$orderNo="")
	{
		ini_set('default_socket_timeout', 600);
		if(empty($endDate)) {
			$endDate = date('Y-m-d H:i:s',time());
		}
		else {
			$endDate = $endDate." 00:00:00";
		}
		echo("[".date('c')."] "." sync budweiser order start endDate:".$endDate."\n");
	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncBudweiserService;
		foreach($this->getBudweiserShopList() as $taobaoShop)
		{
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncBudweiserOrder start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days,"endDate"=>$endDate,"pageNo"=>$pageNo,"orderNo"=>$orderNo);
				print_r($request);
				$response=$client->SyncBudweiserOrder($request);
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
	public function actionBudweiserOrderTransfer($appkey=null,$days=1)
	{
		ini_set('default_socket_timeout', 1200);
		
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncBudweiserService;

		foreach($this->getBudweiserShopList() as $taobaoShop)
		{
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." BudweiserOrderTranfer start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
				print_r($request);
				$response=$client->BudweiserOrderTransfer($request);
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
		// 不启用商品同步的列表

		ini_set('default_socket_timeout', 1200);
		$client=Yii::app()->getComponent('erpsync')->SyncBudweiserService;
		foreach($this->getBudweiserShopList() as $taobaoShop)
		{
		
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
		
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncBudweiserSendDelivery start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key']);
				print_r($request);
				$response=$client->SyncBudweiserSendDelivery($request);
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
	 * 取得启用的微信店铺的列表
	 *
	 * @return array
	 */
	protected function getBudweiserShopList()
	{
		static $list;
		if(!isset($list))
		{
			$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'budweiser' ";
			$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
			$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
			foreach($list as $key=>$item)
				$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
		}
		return $list;
	}
	
}


