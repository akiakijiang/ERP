<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'admin/includes/lib_common.php';
include_once ROOT_PATH . 'protected/components/TaobaoClient.php';

/**
 * 淘宝报警
 * 
 */
class TaobaoAlertCommand extends CConsoleCommand{
	
	private $mailAddress = array (
        'ea66b158e2fb102ab1b4001d0907b75a' => array ('邵琪' => 'qshao@i9i8.com','劳祥睿' => 'xrlao@i9i8.com' ),   //小树苗
		'f1cfc3f7859f47fa8e7c150c2be35bfc' => array ('李涛' => 'tli2@i9i8.com','劳祥睿' => 'xrlao@i9i8.com'),     //金佰利官方旗舰店
		'60e1863c867610309a21003048df78e2' => array ('李涛' => 'tli2@i9i8.com','劳祥睿' => 'xrlao@i9i8.com'),     //金佰利商用官方旗舰店
		'fe1441b38d4742008bd9929291927e9e' => array ('林超军' => 'cjlin@i9i8.com','周皓燕' => 'hyzhou@i9i8.com'),  //好奇官方旗舰店
    );
	
    public function actionIndex(){
        $this->run(array('ItemZeroStock'));
    }
    
	/**
	 * 商品0库存下架预警
	 */
    public function actionItemZeroStock(){
    	$party_phone_list = array(
    		'62f6bb9e07d14157b8fa75824400981' => array(     //雀巢官方旗舰店
    					   0 => '15109260832',   //李雯
    					   1 => '15067134451',   //蒋世超
    				   ),
    		'9781a6fe164a4193acf195d68c10ddfc' => array(     //保乐力加官方旗舰店
    					   0 => '18600263089',
						   1 => '13588846150',
						   2 => '18688877821',
						   3 => '15858283423',
						   4 => '18668171680',   //陈越
						   5 => '18668125110',   //任晓
						   6 => '13867182484',   //欧阳燕
    				   ),
    		'7c6a0d1d9c4d4b9c9dc163121b318832' => array(     //雀巢母婴官方旗舰店
    					   0 => '18505815929',   //曾梦诗
    					   1 => '15067134451',   //蒋世超
    				   ),
    		'9d70da12970b10309a21003048df78e2' => array(      //三雄极光官方旗舰店
    					   0 => '13456981900',   
    				   ),
    		'fe1441b38d4742008bd9929291927e9e' => array(      //好奇旗舰店
    				       0 => '15267452453',   //王媛
    				       1 => '15967143328',   //邵琪 
    				       2 => '15068865563',   //林超军
    					   3 => '15868413637',   //周皓燕
    				   ),
    	);
    	
    	// 不启用商品同步的列表
        $exclude_list=array
        (
         // 'f2c6d0dacf32102aa822001d0907b75a',  // 乐其数码专营店
         // 'f2c6e386cf32102aa822001d0907b75a',  // 奥普电器旗舰店
        );
        
        //以下组织发送短信
        $include_list=array
        (
        	'9781a6fe164a4193acf195d68c10ddfc',  //保乐力加
        	'62f6bb9e07d14157b8fa75824400981f',  //雀巢官方旗舰店
        	'7c6a0d1d9c4d4b9c9dc163121b318832',  //雀巢母婴官方旗舰店
        	'9d70da12970b10309a21003048df78e2',  //三雄极光官方旗舰店
        	'fe1441b38d4742008bd9929291927e9e',  //好奇官方旗舰店
        );
        
        //以下组织发送邮件
        $email_list=array(
        	'ea66b158e2fb102ab1b4001d0907b75a',  //小树苗
        	'f1cfc3f7859f47fa8e7c150c2be35bfc',  //金佰利官方旗舰店
			'60e1863c867610309a21003048df78e2',  //金佰利商用官方旗舰店
        );
        
        foreach($this->getTaobaoShopList() as $taobaoShop){
        	if(in_array($taobaoShop['application_key'], $exclude_list))
            continue;
            // 指定店铺
            if(!in_array($taobaoShop['application_key'], $include_list) && !in_array($taobaoShop['application_key'], $email_list))
            continue;
//			if(!$this->checkAppKey($taobaoShop['application_key']))
//			continue;
			var_dump( date('Y-m-d H:i:s',time())."  ".$taobaoShop['nick']);
            $client=$this->getTaobaoClient($taobaoShop);
            $request=array(
		        'status' => 'ItemZeroStock',
		        'nick' => $taobaoShop['nick'],
                'start_modified' => date('Y-m-d H:i:s',time()-1*60*60),//请求一个小时以前的数据
        	);
        	$response=$client->execute('taobao.increment.items.get',$request);
        	print_r($response);
//        	var_dump($this->getPhoneList($taobaoShop['application_key']));
        	
            if($response->isSuccess()){
	            if(isset($response->total_results) && $response->total_results>0){
					$message_content = '';
					foreach ($response->notify_items->notify_item as $item){
						$sql = 'select url from ecshop.ecs_taobao_goods where num_iid='.$item->num_iid;
						$db = Yii::app()->getDb();
		       			$url = $db->createCommand($sql)->queryAll();
						$message_content = $message_content.'['.$url[0]['url'].']';
					};
					$message_content = $message_content.'在淘宝平台下架'.$taobaoShop['nick'];
					$mobiles = $party_phone_list[$taobaoShop['application_key']];

//					$mobiles = $this->getPhoneList($taobaoShop['application_key']);
					//在短信发送列表的发送短信
					if(in_array($taobaoShop['application_key'], $include_list)){
						send_message($message_content,$mobiles);
					}
					
					//在邮件发送列表的发送邮件
					if (in_array($taobaoShop['application_key'], $email_list)){
						$this->sendMail($message_content, $taobaoShop);
					}
	            }
    		} else {
	        	echo($response->getMsg().": ".$response->getSubMsg()."\n");
	        }
        }
    }

	/**
	 * 数据库同步预警
	 */
	public function actionSyncAlert($user,$password){
		// 不启用商品同步的列表
		$sql = "mysql -u".$user." -p".$password." -h192.168.2.1 -P33306 -e 'show slave status\G'";
		$result = shell_exec($sql);
		if(!empty($result) && ereg("Slave_IO_Running:",$result) && ereg("Slave_SQL_Running",$result)){
			if((ereg("Slave_IO_Running: No",$result) || ereg("Slave_SQL_Running: No",$result)) && (!ereg("Last_Errno: 0", $result))){
				$message_content = "【主从数据库同步失败】\n";
				preg_match('/Slave_IO_Running: No/',$result,$match);
				if($match){
					$message_content .= $match[0]."\n";
				}
				preg_match('/Slave_SQL_Running: No/',$result,$match);
				if($match){
					$message_content .= $match[0]."\n";
				}
				preg_match('/Last_Errno:.*/',$result,$match);
				if($match){
					$message_content .= $match[0]."\n";
				}
				preg_match('/Last_Error:.*/',$result,$match);
				if($match){
					$message_content .= $match[0];
				}
				$mobiles = array(
					//0 => '13588249735',
					1 => '13738196896'
				);
				send_message($message_content,$mobiles);
			}
		}

	}
	
	/**
	 * 验证这个组织需要在商品下架时是否发送短信
	 * @param string $appkey  传入这个组织的appkey
	 * @return boolean  如果需要发送短信，则返回true，不需要发送短信，返回false
	 */
	protected  function checkAppKey($appkey){
		$sql = "
			select count(*) from ecshop.ecs_zero_stock where app_key = '$appkey'
		";
		$db = Yii::app()->getDb();
		$count = $db->createCommand($sql)->queryScalar();
		if($count > 0){
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * 取出所有某个业务组的发送短信所有号码
	 * @param String $appkey
	 * @return 返回短信号码数组
	 */
	protected function getPhoneList($appkey){
		$sql = "
			select send_phone from ecshop.ecs_zero_stock where app_key = '$appkey'
		";
		$db = Yii::app()->getDb();
		$phoneList = $db->createCommand($sql)->queryColumn();
		return $phoneList;
	}


     /**
	 * 取得启用的淘宝店铺的列表
	 * 
	 * @return array
	 */
    protected function getTaobaoShopList()
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = 'taobao' ";
            $list=$this->getSlave()->createCommand($sql)->queryAll();
            $command=$this->getSlave()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
            foreach($list as $key=>$item)
            $list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
        }
        return $list;
    }
    /**
	 * 返回请求对象
	 *
	 * @param array $taobaoShop
	 * @return TaobaoClient
	 */
    protected function getTaobaoClient($taobaoShop)
    {
        static $clients=array();
        $key=$taobaoShop['taobao_shop_conf_id'];
        if(!isset($clients[$key]))
        $clients[$key]=new TaobaoClient($taobaoShop['params']['app_key'],$taobaoShop['params']['app_secret'],$taobaoShop['params']['session_id'],($taobaoShop['params']['is_sandbox']=='Y'?true:false));
        return $clients[$key];
    }
    
    /**
	 * 取得slave数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    private $slave;  // Slave数据库
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
    public function log($m){
        print date("Y-m-d H:i:s") . " " . $m . "\r\n";
    }
    
/**
     * 发送邮件
     * @param string $subject
     */
    private function sendMail($str, $taobaoShop) {
    	
    	try {
    		$mail = Yii::app ()->getComponent ( 'mail' );

    		$mail->Subject = "淘宝店铺【 " . $taobaoShop ['nick'] . "】的商品在淘宝上已经售完";

    		$mail->Body = $str;

    		$mail->ClearAddresses ();
    		if(!$this->mailAddress [$taobaoShop ['application_key']]){
    			return;
    		}
    		foreach ($this->mailAddress [$taobaoShop ['application_key']] as $keys => $values ) {
    			if (! empty ( $keys )) {
    				$mail->AddAddress ( $values, $keys );
    			}
    		}
    		$mail->send ();
    	} catch ( Exception $e ) {
    		var_dump ( "发送邮件异常" );
    	}
    }
}
