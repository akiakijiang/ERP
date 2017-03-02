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
class ErpSyncAmazonCommand extends CConsoleCommand
{
	private $slave;  // Slave数据库
	
	/**
	 * 同步亚马逊订单
	 */
	public function actionSyncAmazonOrder($appkey=null,$hours=6,$group=0,$endDate=null)
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
		echo("[".date('c')."] "." sync amazon order start endDate:".$endDate."\n");
	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncAmazonService;
		foreach($this->getAmazonShopList($group) as $taobaoShop)
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
				$response=$client->syncAmazonOrder($request);
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
	public function actionAmazonOrderTransfer($appkey=null,$days=1,$group=0)
	{
		// 不启用商品同步的列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
		
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncAmazonService;

		foreach($this->getAmazonShopList($group) as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." amazonordertransfer start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
				print_r($request);
				$response=$client->amazonOrderTransfer($request);
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
	 * 同步亚马逊面单
	 */
	public function actionSyncAmazonSendDelivery($appkey=null,$group=0)
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
		echo("[".date('c')."] "." sync amazon delivery start endDate:".$endDate."\n");
	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncAmazonService;
		foreach($this->getAmazonShopList($group) as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." sync delivery start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key']);
				print_r($request);
				$response=$client->syncAmazonSendDelivery($request);
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
	 * 同步亚马逊商品
	 */
	public function actionSyncAmazonProduct($appkey=null,$hours=70)
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
		echo("[".date('c')."] "." sync amazon items start endDate:".$endDate."\n");
	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncAmazonService;
		foreach($this->getAmazonShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." sync items start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
				print_r($request);
				$response=$client->SyncAmazonProduct($request);
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
	 * 同步亚马逊商品库存
	 */
	public function actionSyncAmazonStock($appkey=null)
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
		echo("[".date('c')."] "." sync amazon stock start endDate:".$endDate."\n");
	
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncAmazonService;
		foreach($this->getAmazonShopList() as $taobaoShop)
		{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." sync stock start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key']);
				print_r($request);
				$response=$client->SyncAmazonProductStock($request);
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
	 * 取得启用的亚马逊店铺
	 *
	 * @return array
	 */
	protected function getAmazonShopList($group = 0)
	{
		static $list;
		if(!isset($list))
		{
			$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'amazon'";
	
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
	
}