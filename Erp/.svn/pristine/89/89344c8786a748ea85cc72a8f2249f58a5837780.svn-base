<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2015-04-10
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class ErpSyncColumbuCommand extends CConsoleCommand
{
	
    
     /**
     * 哥伦布同步发货
     */
     public function actionSyncColumbuOrderSendDelivery($partyId=65629)
    {
    	$last_7_date = time() - 7*24*3600;
	$sql = "
    		select oi.taobao_order_sn, s.tracking_number,s.shipment_type_id
            from ecshop.ecs_order_info oi
            inner join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8)
            inner join romeo.shipment s on s.shipment_id = os.shipment_id
            left join  ecshop.sync_columbu_order_info  c on oi.taobao_order_sn = c.columbu_order_id
            where oi.taobao_order_sn is not null and oi.taobao_order_sn <> '' and oi.taobao_order_sn not like 'E%'  and oi.party_id = '{$partyId}' and oi.shipping_time >{$last_7_date} and s.tracking_number != '' and  c.columbu_order_id is null 
            limit 100
        ";
    	echo($sql);
	$db=Yii::app()->getDb();
        $order_list = $db->createCommand($sql)->queryAll();
        foreach ($order_list as $order){
        	echo("[".date('c')."] ".$order['taobao_order_sn']." begin \n");
        	$status = $this->send_shipping_message($order['taobao_order_sn'],$order['tracking_number'],$order['shipment_type_id']);
        	$this->record_shipping($order['taobao_order_sn'],$status);
        	echo("[".date('c')."] ".$order['taobao_order_sn']." end \n");
        }
    }
    function  send_shipping_message($order_sn,$tracking_number,$shipping_id){
		$url = "http://www.yqphh.com/server/order/{$order_sn}";
	    $headers = array ();
	    $headers['N-ACCESS-TOKEN'] = '8dcc077b5c86e7a01cd4963a794c6fda'; 
	    $headers['Content-Type'] = 'application/json';
	    $headers['N-USER-ROLE'] = 'EXTERNAL';
		$headerArr = array(); 
		foreach( $headers as $n => $v ) { 
			$headerArr[] = $n .':' . $v;  
		}
		$param  = array ();
		$param['action'] = 'shippingByOrderSn';
		$param['tracking_number'] = $tracking_number;
		$param['shipping_id'] = $shipping_id;
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url); //设置请求的URL
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER,1); //设为TRUE把curl_exec()结果转化为字串，而不是直接输出 
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT"); //设置请求方式
	     
	    curl_setopt($curl,CURLOPT_HTTPHEADER,array("X-HTTP-Method-Override: PUT"));//设置HTTP头信息
	    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($param));//设置提交的字符串
	    curl_setopt ($curl, CURLOPT_HTTPHEADER , $headerArr); 
	
		$res = curl_exec($curl);
		curl_close($curl);
		$res = json_decode($res,true);
		if($res->code == '200' && $res->data->success){
	    	return 'success';
	    }
	    return 'failed';
	}
    function record_shipping($order_sn,$status){
		$sql = " INSERT INTO ecshop.sync_columbu_order_info (columbu_order_id,status, create_time) VALUES ('{$order_sn}','{$status}',NOW());" ;
		$db=Yii::app()->getDb();
		$result = $db->createCommand($sql)->execute();
	}
}
?>
