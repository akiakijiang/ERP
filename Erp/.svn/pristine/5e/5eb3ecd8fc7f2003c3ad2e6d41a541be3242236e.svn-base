<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2016-04-22
 * by hzhang1
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncGzHaiguanCommand extends CConsoleCommand
{	  
   /**
	 * 上传订单至广州海关
	 * @param applicationKey  执行店铺的应用编号
	 * @param days  ERP中订单生成时间距离当前时间的天数
	 */
    public function actionUploadGzHaiguanOrders($appkey=null,$days=10)
    {
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncGzHaiguanService;
    	$start = microtime(true);
		try
		{
			$request=array("days"=>$days);
			$response=$client->uploadGzHaiguanOrders($request);
			print_r($response);
		}
		catch(Exception $e)
		{
			echo("|  Exception: ".$e->getMessage()."\n");
		}

		echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
		usleep(500000);
    
    }
    
     
     
    /**
	 * 从广州海关平台获取订单回执
	 * @param string $appkey 执行店铺的应用编号
	 * @param int hours 同步距离当前时间的hours小时内的订单状态
	 */
     public function actionDownloadGzHaiguanOrders($appkey=null,$hours=20,$endDate=null)
    {    	
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncGzHaiguanService;
    	
		$start = microtime(true);

		try
		{
			$request=array("hours"=>$hours,"endDate"=>$endDate);
			$response=$client->getGzHaiguanOrders($request);
			print_r($response);
		}
		catch(Exception $e)
		{
			echo("|  Exception: ".$e->getMessage()."\n");
		}

		echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
		usleep(500000);
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
    		$sql="select * from taobao_shop_conf where status='OK' and facility_id in ('246317386')";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
//    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
//    		foreach($list as $key=>$item)
//    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }
    
    
    
}
?>
