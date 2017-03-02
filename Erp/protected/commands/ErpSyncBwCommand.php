<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
//require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2015-7-2
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

class ErpSyncBwCommand extends CConsoleCommand
{
	/**
     * 转换保税仓订单
     *
     * @param string $shopId 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
     public function actionBwOrderTransfer($shopId=null,$days=1)
    {
    	// 不启用商品同步的店铺列表
		$exclude_list=array
		(
				// 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
				// 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncBwService;
    	foreach($this->getBwShopList() as $bwShop)
    	{
			if(in_array($bwShop['shop_id'],$exclude_list))
				continue;
	
			if($shopId!==null&&$shopId!=$bwShop['shop_id'])
				continue;
    
    		echo("[".date('c')."] ".$bwShop['shop_name']." BwOrderTransfer start \n");
			$start = microtime(true);
    
    		//转换保税仓订单
    		try
    		{
    			$request=array("days"=>$days,"shopId"=>$bwShop['shop_id']);
				$response=$client->BwOrderTransfer($request);
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
     * 取得启用的保税仓店铺
     *
     * @return array
     */
    protected function getBwShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		$sql="select * from bw_shop";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    	}
    	return $list;
    }
} 

?>
