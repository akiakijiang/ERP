<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2015-05-04
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncKuajinggouCommand extends CConsoleCommand
{	  
   
   /**
	 * 上传手动录制跨境购订单到跨境平台 
	 * @param applicationKey  执行店铺的应用编号
	 * @param days  ERP中订单生成时间距离当前时间的天数
	 */
    public function actionSyncKuajinggouOrderByHand($distributor_id=null,$days=10)
    {
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKuajinggouService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if($distributor_id!==null&&$distributor_id!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncKuajinggouOrderByHand start \n");
    		$start = microtime(true);
    
    		// 上传跨境购订单到跨境平台 
    		try
    		{
    			$request=array("distributor_id"=>$taobaoShop['application_key'],"days"=>$days);
    			$response=$client->SyncKuajinggouOrderByHand($request);
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
	 * 上传跨境购订单到跨境平台 
	 * @param applicationKey  执行店铺的应用编号
	 * @param days  ERP中订单生成时间距离当前时间的天数
	 */
    public function actionSyncKuajinggouOrder($appkey=null,$days=10)
    {
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKuajinggouService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncKuajinggouOrder start \n");
    		$start = microtime(true);
    
    		// 上传跨境购订单到跨境平台 
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key'],"days"=>$days);
    			$response=$client->SyncKuajinggouOrder($request);
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
	 * 从跨境购平台同步订单状态到跨境购订单状态表，从而同步状态到ERP
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 同步距离当前时间的hours小时内的订单状态
	 */
     public function actionSyncKuajinggouOrderStatus($appkey=null,$hours=20,$endDate=null)
    {    	
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKuajinggouService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncKuajinggouOrderStatus start \n");
			$start = microtime(true);
    
    		//从跨境购平台同步订单状态到跨境购订单状态表，从而同步状态到ERP
    		try
    		{
    			$request=array("hours"=>$hours,"endDate"=>$endDate,"applicationKey"=>$taobaoShop['application_key']);
				$response=$client->SyncKuajinggouOrderStatus($request);
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
	  * 对于审核状态为“国检审核未过”、“海关单证审核未过”的订单进行关闭，对于审核不通过的订单按描述信息相应修改之后，再次申报
	  * @param string $appkey 执行店铺的应用编号
	  * @param int hours 获取距离当前时间hours小时内上传到跨境平台的、且状态为审核未通过的订单
	 */
     public function actionCloseKuajinggouOrder($appkey=null,$hours=6,$mft_no="")
    {    	
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncKuajinggouService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." CloseKuajinggouOrder start \n");
			$start = microtime(true);
    
    		//对于审核状态为“国检审核未过”、“海关单证审核未过”的订单进行关闭，对于审核不通过的订单按描述信息相应修改之后，再次申报
    		try
    		{
    			$request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key'],"mft_no"=>$mft_no);
				$response=$client->CloseKuajinggouOrder($request);
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
     * 取得启用的需要上传订单到跨境购平台的店铺
     * @return array
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		//$sql="select * from taobao_shop_conf where status='OK' and shop_type = 'kuajinggou'";
    		$sql="select * from  ecshop.haiguan_api_params where status = 1";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
//    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
//    		foreach($list as $key=>$item)
//    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
    
    
    
}
?>
