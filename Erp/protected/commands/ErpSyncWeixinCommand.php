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
class ErpSyncWeixinCommand extends CConsoleCommand
{	
	/**
	 * 同步亚马逊订单
	 */
	public function actionSyncWeixinOrder($appkey=null,$hours=1)
	{
		 
		// 不启用订单同步的列表
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
		echo("[".date('c')."] "." sync weixin order start endDate:".$endDate."\n");
	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncWeixinService;
		foreach($this->getWeixinShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncWeixinOrder start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours,"endDate"=>$endDate);
				print_r($request);
				$response=$client->SyncWeixinOrder($request);
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
	public function actionWeixinOrderTransfer($appkey=null,$days=1)
	{
		// 不启用商品同步的列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
		
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncWeixinService;

		foreach($this->getWeixinShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." WeixinOrderTranfer start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
				print_r($request);
				$response=$client->WeixinOrderTransfer($request);
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
	 * 尿裤宝 退货通知接口
	 */
	public function actionWeixinNKBDReturnOrder($appkey=null,$hours=1)
	{
		// 不启用商品同步的列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
		
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncWeixinService;

		foreach($this->getWeixinShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." WeixinNKBDReturnOrder start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
				print_r($request);
				$response=$client->SyncWeixinNKBDReturnOrder($request);
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
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
		$client=Yii::app()->getComponent('erpsync')->SyncWeixinService;
		foreach($this->getWeixinShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
		
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
		
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncWeixinSendDelivery start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key']);
				print_r($request);
				$response=$client->SyncWeixinSendDelivery($request);
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
	 * 尿裤宝同步发货
	 */
	public function actionSyncNKBDeliverySend($appkey=null)
	{
		// 晚上不需要同步发货
		$h=date('H');
		if($h<9 || $h>22) { return; }
		// 不启用商品同步的列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
		$client=Yii::app()->getComponent('erpsync')->SyncWeixinService;
		foreach($this->getWeixinShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
		
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
		
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncWeixinNKBSendDelivery start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key']);
				print_r($request);
				$response=$client->SyncWeixinNKBSendDelivery($request);
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
	protected function getWeixinShopList()
	{
		static $list;
		if(!isset($list))
		{
			$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'weixin' ";
			$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
			$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
			foreach($list as $key=>$item)
				$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
		}
		return $list;
	}
	
}


