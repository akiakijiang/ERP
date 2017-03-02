<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2013-2-23
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class AmazonSyncCommand extends LockedCommand
{
	
	private $slave;  // Slave数据库

    public $betweenSyncItem=1800;           // 执行商品同步的时间
    public $betweenSyncItemStock=5400;      // 执行时间
    
        /**
	 * 当不指定ActionName时的默认调用
	 */
    public function actionIndex()
    {
        $currentTime=microtime(true);

        // 商品同步
        if($currentTime-$this->getLastExecuteTime('SyncItem')>=$this->betweenSyncItem) {
            $this->run(array('SyncItem','--hours='.$this->betweenSyncItem));    
        }
        
        // 库存同步
        if($currentTime-$this->getLastExecuteTime('SyncItemStock')>=$this->betweenSyncItemStock)
        {
            // 同步库存前先更新商品
            $this->run(array('SyncItem','--seconds='.$this->betweenSyncItem));
            $this->run(array('SyncItemStock','--seconds='.$this->betweenSyncItemStock));
        }
        
        // 同步订单
        if($currentTime-$this->getLastExecuteTime('SyncOrder')>=$this->betweenSyncOrder) {
        	$this->run(array('SyncOrder'));
        }
    }
    
    /**
	 * 同步订单
	 */
    public function actionSyncOrder($appkey=null,$hours=51)
    {
 
    }
    
    /**
	 * 同步亚马逊上的商品
	 *
	 * @param string  $appkey
	 * @param integer $seconds
	 */
    public function actionSyncItem($appkey=null,$hours=70)
    {
            // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        
        // 远程服务
        $client=Yii::app()->getComponent('romeo')->AmazonProductService;
        foreach($this->getTaobaoShopList() as $amazonShop)
        {
        	if(in_array($amazonShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$amazonShop['application_key'])
            continue;

            echo("[".date('c')."] ".$amazonShop['nick']." sync item start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("hours"=>$hours,"applicationKey"=>$amazonShop['application_key']);
                $response=$client->syncAmazonProduct($request);
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
     * 同步亚马逊库存
     * 
     * @param string $appkey 执行店铺的应用编号
     * @param integer $seconds 修改时间
     */
    public function actionSyncItemStock($appkey=null,$seconds=null)
    {
             // 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        
        // 远程服务
        $client=Yii::app()->getComponent('romeo')->AmazonProductService;
        foreach($this->getTaobaoShopList() as $amazonShop)
        {
        	if(in_array($amazonShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$amazonShop['application_key'])
            continue;

            echo("[".date('c')."] ".$amazonShop['nick']." sync itemstock start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("applicationKey"=>$amazonShop['application_key']);
                $response=$client->syncAmazonSkuStock($request);
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
    protected function getTaobaoShopList()
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = 'amazon'";
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
?>
