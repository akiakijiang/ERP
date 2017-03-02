<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
include_once ROOT_PATH . 'admin/function.php';
define('restful_order_URL', $restful_bdf_order_api);
define('restful_ordergoods_URL', $restful_bdf_ordergoods_api);

//$restful_bdf_order_api = "http://localhost/bdfclub/api/rest_test/order_list";
//$restful_bdf_ordergoods_api = "http://localhost/bdfclub/api/rest_test/order_goods";
class AutoBdfClubCommand extends CConsoleCommand
{
	/**
	 * 将ERP里面ecs_order_info表里面满足要求的订单传递到bdf系统中去
	 * by hzhang1 2015-09-07
	 */
	public function actionSendOrders()
    {
    	$db = Yii::app()->getDb();
		ini_set('default_socket_timeout', 1200);
		
		foreach($this->getTaobaoShopList() as $taobaoShop)
    	{
    		echo("[".date('c')."] ".$taobaoShop['nick']." SendOrdersToBdf start \n");
    		$start = microtime(true);
	    	$sql = "SELECT eoi.*,koi.buyer_nick from ecshop.ecs_order_info eoi
					inner JOIN ecshop.sync_kdt_order_info koi on eoi.taobao_order_sn = koi.trade_id 
					where eoi.distributor_id = '{$taobaoShop['distributor_id']}' and eoi.shipping_status = '1'";
	    	$orders = $db->createCommand($sql)->queryAll();
	    	foreach ($orders as $order) {
	    		$restResult=$this->post_getData(restful_order_URL,$order);
	    		echo "[Send OrderId".$order['order_id']." Response] ".$restResult['http_code']."\n";
	    		//echo $restResult['result'];
	    		$sql = "SELECT * from ecshop.ecs_order_goods where order_id ='{$order['order_id']}'";
	    		$ordergoods = $db->createCommand($sql)->queryAll();
	    		foreach ($ordergoods as $ordergood) {
	    			$restResult=$this->post_getData(restful_ordergoods_URL,$ordergood);
	    			echo "[Send OrderGoods Response] ".$restResult['http_code']."\n";
	    		}
	    	}
	    	echo "[". date('c'). "]  耗时：".(microtime(true)-$start)."\n";
	    	//usleep(50000);
    	}
    }
    
    
    /**
     * 通过restful curl发送订单方法
     * by hzhang1 2015-09-07
     */
    private function post_getData($URL, $request)
	{
		$ch = curl_init();  
        curl_setopt($ch, CURLOPT_POST, 1);  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $URL);  
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));  
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(  
            'Content-Type: application/json; charset=utf-8',  
            'Content-Length: ' . strlen(json_encode($request)))  
        );  
        ob_start();  
        $result =  curl_exec($ch);  
        $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        return array("http_code" => $http_code, "result" => $result);
	}
	
	
	// GET data by CURL
    private function get_getData($url)
    {
    	$ch = curl_init($url);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ;
    	$data = curl_exec($ch);
    	curl_close($ch);
    	return $data;
    }
    
    
    /**
     * 获取店铺信息
     */
    protected function getTaobaoShopList()
    {
    	static $list;
    	if(!isset($list))
    	{
    		$sql="select * from taobao_shop_conf where status='OK' and application_key in('79533af938c4a3cdef58b27dd7395eca','4303ff20e57e11e38c3b90b11c4a1865','f9f7e28ae69610329078003048df78e2')";
    		$list=Yii::app()->getDb()->createCommand($sql)->queryAll();
    		$command=Yii::app()->getDb()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
    		foreach($list as $key=>$item)
    			$list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
    	}
    	return $list;
    }    
}
?>
