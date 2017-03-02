<?php

define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
/**
 * @author wjzhu@i9i8.com
 * @copyright Copyright &copy; 2012 leqee.com
 */

/**
 * 判断TMALL物流宝与ERP仓库是否重复发货（若有可能则发送邮件至wjzhu@i9i8.com）
 * 
 * @author wjzhu@i9i8.com
 * @version $Id$
 * @package protected.commands
 */
class TmallLogisticsCommand extends CConsoleCommand {
    private $slave; // Slave数据库    
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {
        $this->run ( array ('CheckLogistic' ) );
    }
    
    /**
     * 检查淘宝物流，调用发送邮件的函数
     */
    public function actionCheckLogistic() {
        $include_list  = array (
//            'f2c6d0dacf32102aa822001d0907b75a', //乐其数码专营店
//            'd1ac25f28f324361a9a1ea634d52dfc0', //怀轩名品专营店
//            'fd42e8aeb24b4b9295b32055391e9dd2', //oppo乐其专卖店
//            '239133b81b0b4f0ca086fba086fec6d5', //贝亲官方旗舰店
//            '11b038f042054e27bbb427dfce973307', //多美滋官方旗舰店
//            'ee0daa3431074905faf68cddf9869895', //accessorize旗舰店
//            'ee6a834daa61d3a7d8c7011e482d3de5', //金奇仕官方旗舰店
//            'fba27c5113229aa0062b826c998796c6', //方广官方旗舰店
//            'f38958a9b99df8f806646dc393fdaff4', //阳光豆坊旗舰店
//            '7f83e72fde61caba008bad0d21234104', //nutricia官方旗舰店
//            '62f6bb9e07d14157b8fa75824400981f', //雀巢官方旗舰店
//            '753980cc6efb478f8ee22a0ff1113538', //gallo官方旗舰店
//            '589e7a67c0f94fb686a9287aaa9107db', //yukiwenzi-分销
//            'fe1441b38d4742008bd9929291927e9e', //好奇官方旗舰店
              'f1cfc3f7859f47fa8e7c150c2be35bfc', //金佰利官方旗舰店
//            'dccd25640ed712229d50e48f2170f7fd', //ecco爱步官方旗舰店
//            '9f6ca417106894739e99ebcbf511e82f', //每伴旗舰店
//            'd2c716db4c9444ebad50aa63d9ac342e' //皇冠巧克力
        );        
        
        //循环淘宝店铺，根据每个店铺的套餐来做判断
        foreach ( $this->getTaobaoShopList () as $taobaoShop ) {      
            if (! in_array($taobaoShop['application_key'], $include_list)) {
                continue;
            }

        	$start_time = date ( 'Y-m-d H:i:s', time() - 24 * 3600);
        	$ended_time = date ( 'Y-m-d H:i:s', time() - 60);
            $request = array (
            	'fields' => ' num_iid,  
            				  tid, 
            				  is_brand_sale, 
            				  is_force_wlb, 
            				  orders.oid, 
            				  orders.title, 
            				  orders.outer_iid', 
            	'start_modified' => $start_time,
                'end_modified' => $ended_time,
            );         
            
            $this->checkLogistic($taobaoShop, $request, $start_time, $ended_time);
        }
    }
        
   
    /**
     * 返回请求对象
     *
     * @param array $taobaoShop
     * @return TaobaoClient
     */
    protected function getTaobaoClient($taobaoShop) {
        static $clients = array ();
        $key = $taobaoShop ['taobao_shop_conf_id'];
        if (! isset ( $clients [$key] ))
            $clients [$key] = new TaobaoClient ( $taobaoShop ['params'] ['app_key'], $taobaoShop ['params'] ['app_secret'], $taobaoShop ['params'] ['session_id'], ($taobaoShop ['params'] ['is_sandbox'] == 'Y' ? true : false) );
        return $clients [$key];
    }
    
    /**
     * 取得启用的淘宝店铺的列表
     * 
     * @return array
     */
    protected function getTaobaoShopList() {
        static $list;
        if (! isset ( $list )) {
            $sql = "select * from taobao_shop_conf where status='OK'";
            $list = $this->getSlave ()->createCommand ( $sql )->queryAll ();
            $command = $this->getSlave ()->createCommand ( "select * from taobao_api_params where taobao_api_params_id=:id" );
            foreach ( $list as $key => $item )
                $list [$key] ['params'] = $command->bindValue ( ':id', $item ['taobao_api_params_id'] )->queryRow ();
        }
        return $list;
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
    
    protected function checkLogistic($taobaoShop, $request, $start_time, $ended_time){
    	try {
    		$control = false;
            $body = "";
        
            $response = $this->getTaobaoClient($taobaoShop)->execute('taobao.trades.sold.increment.get', $request);
           	while(true) {
           		if(!$response->isSuccess()) {
           			$body .= "Failed to invoke Taobao API: taobao.trades.sold.increment.get";
                	$control = true;
                	break;
                }
                	
	            if (!isset($response->trades) || count($response->trades) <= 0) {
	            	$body .= "Failed to retrieve Taobao trades after invoking taobao.trades.sold.increment.get";
	              	$control = true;
	                break;
	            }
	                
	           	if (!isset($response->trades->trade) || count($response->trades->trade) <= 0){
	           		$body .= "Taobao trades Zero trade after invoking taobao.trades.sold.increment.get";
	             	$control = true;
	                break;
	          	}
	                
	            foreach ( $response->trades->trade as $trade ) {
	            	if($trade->is_brand_sale != $trade->is_force_wlb) {
		       			// do not break ,since the error comes from taobao
		       			$body .= "淘宝订单号: " . $trade.tid . "\n";
		       			$body .= "品牌特卖: " . $trade->is_brand_sale . ", 物流宝: " .$trade->is_force_wlb . "\n";
		       			$control = true;
		       		}
		       			
		       		if (!isset($trade->orders) || count($trade->orders) <= 0) {
		       			$body .= "Taobao trades-trade Zero orders after invoking taobao.trades.sold.increment.get";
		       			$control = true;
	                	break;
		       		}
		       		
	            	if (!isset($trade->orders->order) || count($trade->orders->order) <= 0) {
		       			$body .= "Taobao trades-trade-orders Zero order after invoking taobao.trades.sold.increment.get";
		       			$control = true;
	                	break;
		       		}
		       		
		       		foreach ( $trade->orders->order as $order ) {
		       			if ($trade->is_brand_sale == $trade->is_force_wlb && $trade->is_force_wlb == true) {
		       				$row = $this->getSlave()->createCommand("select * from ecshop.ecs_order_info where taobao_order_sn = :oid")
                    								->bindValue(':oid',$order->oid)
                    								->queryRow();		
                    		if ($row['order_status'] == 1){
                    			$body .= $start_time . " — " . $ended_time . "\n";
                    			$body .= "ERP OrderSn: " . $row['order_id'] . "	";
                    			$body .= "TAOBAO OrderSn: " . $row['taobao_order_sn'] . "	";
                    			$body .= "TAOBAO outer_iid: " . $row['taobao_order_sn'] . "	";
                    			$body .= "https://ecadmin.i9i8.com/admin/order_edit.php?order_id=" . $row['order_id'] . "\n";
                    			$control = true;
                    			break;
                    		}
		       			}
		       		}
               	}
               	
               	break;
           	}

           	if($control == true){
            	var_dump($body);
            	$subject = "淘宝店铺【" . $taobaoShop ['nick'] . "】可能存在重复发货，请及时在ERP系统和淘宝后台确认。\n";
            	$this->SendMail($subject, $body);
           	}
           	
    	} catch ( Exception $e ) {
       		var_dump ("|  - has exception: " . $e->getMessage () . "\n");
    	}
    }
    
	/**
     * 发邮件
     */
    protected function sendMail($subject, $body) {
        try {
        	$mail = Helper_Mail::smtp();
            $mail->CharSet='UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->SetFrom('noreply@i9i8.com', '物流宝报警');
            $mail->AddAddress ( 'wjzhu@i9i8.com', '朱伟晋' );
            $mail->send ();
        } catch ( Exception $e ) {
            var_dump ( "发送邮件异常" );
        } 
    }
}
