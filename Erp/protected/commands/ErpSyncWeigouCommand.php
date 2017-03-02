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
class ErpSyncWeigouCommand extends CConsoleCommand
{	
	
	/**
	 * 同步微购物商品
	 */
	public function actionSyncWeigouItems($appkey=null,$hours=1)
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
		echo("[".date('c')."] "." sync weigou items start endDate:".$endDate."\n");
	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncWeigouService;
		foreach($this->getWeigouShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncWeigouItems start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
				print_r($request);
				$response=$client->SyncWeigouItems($request);
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
	 * 同步微购物订单
	 */
	public function actionSyncWeigouOrder($appkey=null,$hours=1)
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
		echo("[".date('c')."] "." sync weigou order start endDate:".$endDate."\n");
	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncWeigouService;
		foreach($this->getWeigouShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." SyncWeigouOrder start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
				print_r($request);
				$response=$client->SyncWeigouOrder($request);
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
	public function actionWeigouOrderTransfer($appkey=null,$days=1)
	{
		// 不启用商品同步的列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
		
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncWeigouService;

		foreach($this->getWeigouShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." WeigouOrderTranfer start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
				print_r($request);
				$response=$client->WeigouOrderTransfer($request);
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
	protected function getWeigouShopList()
	{
		static $list;
		if(!isset($list))
		{
			$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'weigou' ";
			$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
			$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
			foreach($list as $key=>$item)
				$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
		}
		return $list;
	}
	
}


