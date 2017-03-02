<?php

/**
 * @author qxu@leqee.com
 * @copyright Copyright &copy; 2012 leqee.com
 */

/**
 * 判断亨氏CallCenter传递过来的订单  是否已经签收，如果签收，则需要在ERP上 自动售货确认，并添加记录
 * 
 * @author qxu@leqee.com
 * @version $Id$
 * @package protected.commands
 */
class ExpressTrackCommand extends CConsoleCommand {
    private $slave; // Slave数据库    

    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {
        // 检查直销价格是否一致
        $this->run ( array ('CheckExpress' ) );
    }
    
    /**
     * 获取顺丰和中通的30天内的快递，然后解析这些快递方式，
     * 如果已经签收成功，那么需要将这些订单状态改为
     */
    public function actionCheckExpress($day = null) {
    	$db = Yii::app ()->getDb ();
    	
    	if(empty($day)){
    		$day = 15;
    	}
    	
    	$startTime = date('Y-m-d H:i:s',time()-$day*24*60*60);
    	//获取亨氏官方商城和CallCenter的订单以及中通、顺丰快递方式
    	$sql = "
    		SELECT eoi.order_id,s.TRACKING_NUMBER,eoi.shipping_id
			from romeo.shipment s 
			inner join romeo.order_shipment os on os.shipment_id = s.shipment_id
			inner join ecshop.ecs_order_info eoi on convert(eoi.order_id,char(30)) = os.order_id
			where eoi.shipping_status = '1'
			and eoi.distributor_id in ('1797','1900')
			and shipping_id in (115,44,126,127)
			and eoi.order_time > '$startTime'
    	";
    	
    	$expressOrder = $db->createCommand ($sql)->queryAll();
    	foreach ($expressOrder as $order) {
    		if($order['shipping_id'] == '115' || $order['shipping_id'] == '126'){//中通的快递单，需要调用中通的接口
    			$xml = simplexml_load_file("http://api.zto.cn/TrackBill.aspx?Userid=LEQEE&Pwd=LEQEEW89K&SrtjobNo=".$order['TRACKING_NUMBER']);
    			if(isset($xml->track->detail) && count($xml->track->detail)){
    				foreach ($xml->track->detail as $detail) {
    					if(strstr($detail->Process,'签收人')){
    						var_dump($order['order_id'].'签收成功，需要做ERP状态变换处理');
    						$this->changeStatus($order['order_id']);
    					}
    				}
    			}
    		}elseif($order['shipping_id'] == '44' || $order['shipping_id'] == '127'){//顺丰的快递单。亨氏暂时不发顺丰订单了，不用调度顺丰的接口
//    			$is_sign = $this->checkSFExpress($order['TRACKING_NUMBER']);
//    			if($is_sign){
//    				var_dump($order['order_id'].'签收成功，需要做ERP状态变换处理');
//    				$this->changeStatus($order['order_id']);
//    			}
    		}
    	}
    }
        
    
    protected function  changeStatus($order_id){
    	$db = Yii::app ()->getDb ();
    	
    	$order = $db->createCommand("select * from ecshop.ecs_order_info where order_id = '$order_id'")->queryRow();
    	if($order['shipping_status'] == '2'){//当一个订单有多个面单时，第一个面单将订单状态修改之后，第二个面单就不修改订单装备和添加order_action了
    		return;
    	}
    	
    	$sql = "
    		update ecshop.ecs_order_info set shipping_status = '2' where order_id = '$order_id'
    	";
    	$db->createCommand ($sql)->query();
    	
    	$sql = "
    		insert into ecshop.ecs_order_action values(null,'$order_id','SYSTEM','{$order['order_status']}','2','{$order['shipping_status']}',now(),'快递已经签收，系统同步收货确认','0','0','')
    	";
    	$db->createCommand ($sql)->query();
    }
    
    protected function checkSFExpress($tracking_number){
    	$url ="http://bsp-ois.sf-express.com/bsp-ois/ws/expressService?wsdl";
		$client = new SoapClient($url,array('soap_version' => SOAP_1_1,'trace' => true));
		
		$xmlstring = <<<XML
		<arg0>
		<![CDATA[
		<Request service='RouteService' lang='zh-CN'>
		<Head> 7698041295,}b{cKi8rCkoUQi}5zL9OR5AQ2FIAOwb; </Head>
		<Body>
		<RouteRequest tracking_type='1'  method_type='1' tracking_number='$tracking_number' />
		</Body>
		</Request>
		]]>
		</arg0>
XML;
		try{
			$result= $client->sfexpressService(array('arg0'=>new SoapVar($xmlstring, XSD_ANYXML)));
			$xml = new SimpleXMLElement($result->return);
			if(isset($xml->Body->RouteResponse->Route) && count($xml->Body->RouteResponse->Route)){
				foreach ($xml->Body->RouteResponse->Route as $route){
					if(in_array($route['opcode'],array('80','8000'))){
						return true;
					}
				}
			}
		}catch (SOAPFault $e){
			var_dump("调用顺丰接口异常");
		}
		return false;
    }
    
    /**
     * 取得slave数据库连接
     * 
     * @return CDbConnection
     */
    protected function getSlave() {
        if (! $this->slave) {
            if (($this->slave = Yii::app ()->getComponent ( 'slave' )) === null)
                $this->slave = Yii::app ()->getDb ();
            $this->slave->setActive ( true );
        }
        return $this->slave;
    }
}
