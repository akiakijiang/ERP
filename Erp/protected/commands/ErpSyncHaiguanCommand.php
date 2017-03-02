<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2016-1-26
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncHaiguanCommand extends CConsoleCommand
{	
	
	/**
	 * 订单转换(只转换7天内的订单)
	 */
	public function actionHaiguanOrderTransfer($appkey=null,$days=1)
	{
		ini_set('default_socket_timeout', 1200);
		
		// 远程服务
		$client=Yii::app()->getComponent('erpsync')->SyncHaiguanService;

		foreach($this->getHaiguanShopList() as $taobaoShop)
		{
//			var_dump($taobaoShop['application_key']);
//			die;
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
	
			echo("[".date('c')."] ".$taobaoShop['nick']." HaiguanOrderTransfer start \n");
			$start = microtime(true);
			// 同步生成订单
			try
			{
				$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
				print_r($request);
//				die;
				$response=$client->HaiguanOrderTransfer($request);
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



	protected function getHaiguanShopList()
	{
		static $list;
		if(!isset($list))
		{
			$sql="select * from haiguan_api_params where 1 ";
			$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
//			foreach($list as $key=>$item)
//				$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
		}
//		var_dump($list);
		return $list;
	}
	
	protected function getWeixinShopList()
	{
		static $list;
		if(!isset($list))
		{
			$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'taobao' ";
			$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
			$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
			foreach($list as $key=>$item)
				$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
		}
		var_dump($list);
		return $list;
	}
}


