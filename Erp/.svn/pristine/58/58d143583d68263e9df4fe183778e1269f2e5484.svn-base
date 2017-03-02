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
class ErpSyncQsWeixinCommand extends CConsoleCommand
{

    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间

    /**
     * 同步惠氏微信订单
     *
     * @param string $appkey 执行店铺的应用编号
     *
     */
    public function actionSyncWyethOrder($appkey=null,$hours=6)
    {
        // 不启用商品同步的店铺列表
        $exclude_list=array
        (
    
        );
        ini_set('default_socket_timeout', 1200);
    
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncWeixinWyethService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if(in_array($taobaoShop['application_key'],$exclude_list))
                continue;
    
            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
                continue;
    
            echo("[".date('c')."] ".$taobaoShop['nick']." syncJdOrder start \n");
            $start = microtime(true);
    
            // 同步惠氏微信订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key'],"hours"=>$hours);
                $response=$client->SyncWeixinWyethOrder($request);
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
     * 转换惠氏微信订单
     *
     * @param string $appkey 执行店铺的应用编号
     * @param int hours 订单同步时间距离当前时间的小时数
     */
    public function actionWyethOrderTransfer($appkey=null)
    {
        // 不启用商品同步的店铺列表
        $exclude_list=array
        (
    
        );
        ini_set('default_socket_timeout', 1200);
    
        // 远程服务
        $client=Yii::app()->getComponent('erpsync')->SyncWeixinWyethService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if(in_array($taobaoShop['application_key'],$exclude_list))
                continue;
    
            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
                continue;
    
            echo("[".date('c')."] ".$taobaoShop['nick']." JdOrderTransfer start \n");
            $start = microtime(true);
    
            //转换微信惠氏订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key']);
                $response=$client->WeixinWyethOrderTransfer($request);
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
     * 惠氏微信物流
     *
     * @param string $appkey 执行店铺的应用编号
     *
     */
    public function actionSyncWyethOrderSendDelivery($appkey=null)
    {
    // 不启用商品同步的店铺列表
		$exclude_list=array
		(
		);
		ini_set('default_socket_timeout', 1200);
    
    	// 远程服务
    	$client=Yii::app()->getComponent('erpsync')->SyncWeixinWyethService;
    	foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
			if(in_array($taobaoShop['application_key'],$exclude_list))
				continue;
	
			if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
				continue;
    
    		echo("[".date('c')."] ".$taobaoShop['nick']." SyncWeixinWyethOrderSendDelivery start \n");
			$start = microtime(true);
    
    		//同步发货
    		try
    		{
    			$request=array("applicationKey"=>$taobaoShop['application_key']);
				$response=$client->SyncWeixinWyethOrderSendDelivery($request);
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
     * 取得惠氏微信店铺
     *
     * @return array
     */
    protected function getTaobaoShopList()
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = 'weixinqs'";
            $list=Yii::app()->getDb()->createCommand($sql)->queryAll();
            $command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
            foreach($list as $key=>$item)
                $list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
        }
        return $list;
    }
}
?>