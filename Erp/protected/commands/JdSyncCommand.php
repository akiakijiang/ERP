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
class JdSyncCommand extends LockedCommand
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
        // 不启用订单同步的列表
        $exclude_list=array
        (
        );

        // 远程服务
        $client=Yii::app()->getComponent('romeo')->JdOrderService;
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if(in_array($taobaoShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            echo("[".date('c')."] ".$taobaoShop['nick']." sync order start \n");

            // 同步生成订单
            try
            {
                $request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
                $response=$client->synchronizeOrder($request);
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }
            
            usleep(500000);
            
            // 同步发货订单
            try
            {
                $request=array("applicationKey"=>$taobaoShop['application_key']);
                $response=$client->synchronizeOrderDelivered($request);
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }

            usleep(500000);
            // 同步完成订单
            try
            {
                $request=array("hours"=>$hours,"applicationKey"=>$taobaoShop['application_key']);
                $response=$client->synchronizeOrderFinished($request);
                print_r($response);
            }
            catch(Exception $e)
            {
                echo("|  Exception: ".$e->getMessage()."\n");
            }
            
            usleep(500000);
        }
    }
    
    /**
	 * 同步京东上的商品
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
        $client=Yii::app()->getComponent('romeo')->JdProductService;
        foreach($this->getTaobaoShopList() as $jdShop)
        {
        	if(in_array($jdShop['application_key'],$exclude_list))
            continue;

            if($appkey!==null&&$appkey!=$jdShop['application_key'])
            continue;

            echo("[".date('c')."] ".$jdShop['nick']." sync order start \n");
            $start = microtime(true);
            // 同步生成订单
            try
            {
                $request=array("hours"=>$hours,"applicationKey"=>$jdShop['application_key']);
                $response=$client->syncJdProduct($request);
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
     * 同步京东库存
     * 
     * @param string $appkey 执行店铺的应用编号
     * @param integer $seconds 修改时间
     */
    public function actionSyncItemStock($appkey=null,$seconds=null)
    {
        // 不启用库存同步的店铺列表
        $include_list = array
        (
        );
        
        //获取七天内系统中未预定成功订单中指定商品的使用库存
        $start_time = date("Y-m-d H:i:s", time()-3600*24*7);
        $end_time = date("Y-m-d H:i:s", time());
        $need_to_reserved_list = $this->getPorductNeedToReservedList($start_time, $end_time);
        $need_to_reserved_hashamp = Helper_Array::toHashmap((array)$need_to_reserved_list, 'product_id', 'pending_count');

        $start = microtime(true);
        $sql="
			SELECT jg.jd_goods_id,jg.goods_id,jg.style_id,jg.ware_id,jg.sku_id,jg.outer_id, jg.quantity, jg.is_auto_reserve,
                   jg.reserve_quantity, jg.is_use_reserve, jg.status , CONCAT_WS(',', g.goods_name, 
                IF(gs.goods_color = '', s.color, gs.goods_color)) AS goods_name
			FROM ecs_jd_goods jg LEFT JOIN ecs_goods g on g.goods_id = jg.goods_id
			     LEFT JOIN ecs_goods_style gs ON g.goods_id = gs.goods_id AND gs.style_id = jg.style_id
                 LEFT JOIN ecs_style s ON gs.style_id = s.style_id
			WHERE jg.status = 'OK' AND jg.application_key=:appkey
		";
        if($seconds!==null)  // 只同步京东、淘宝上近期有修改的商品（比如卖出的商品）
        {
            $start_time=date('Y-m-d H:i:s',time()-$seconds);
            $ended_time=date('Y-m-d H:i:s');
            $sql.=" and ((jg.last_update_stamp between '$start_time' and '$ended_time') or 
                           exists (select 1 from ecshop.ecs_taobao_goods tg 
                              where tg.status = 'OK' and tg.status = 'OK' 
                                AND tg.approve_status = 'onsale' AND tg.outer_id = jg.outer_id
                                AND tg.last_modify between '$start_time' and '$ended_time')
                         )";
        }
        
        $select=Yii::app()->getDb()->createCommand($sql); 
        foreach($this->getTaobaoShopList() as $taobaoShop)
        {
            if(in_array($taobaoShop['application_key'], $include_list))
            continue;

            if($appkey!==null&&$appkey!=$taobaoShop['application_key'])
            continue;

            // 取得要同步库存的商品和SKU
            $items=$select->bindValue(':appkey', $taobaoShop['application_key'])->queryAll();
            $calc_count = count($items);
            $calc_start = microtime(true);
            
            //同步库存时检查预警库存数量，发送邮件
            $from = array(
                'email' => 'erp@leqee.com', 'name' => '商品同步库存预警列表',
            );
            $sql = "
                select user_name as name, user_email as email from ecshop.ecs_party_assign_email 
                where party_id = '{$taobaoShop['party_id']}' and warning_id = ''
            ";
            $to_email = $this->getSlave()->createCommand($sql)->queryAll();
            $html = '';  //异常商品邮件发送内容
            $html_a = array();
            foreach($items as $idx => $item)
            {
                // 先定义预添加的数组属性
                $items[$idx]['available_to_reserved'] = 0;
                $items[$idx]['stock_quantity'] = 0;
                //仓库库存是有有异常
                $need_to_reserved = 0;  //默认系统中待预定商品使用库存为0
                
            	$productId = ProductServices::getProductId($item['goods_id'],$item['style_id']);
            	if (empty($productId)) {
            	    echo("[" .date('c') ."] " . "row (goods_id: ".$item['goods_id'].", style_id: ".$item['style_id'].") productId not exists ! \n");	
            	} else {
            		// 取得库存
            		$facilities = FacilityServices::getFacilityByPartyId($taobaoShop['party_id']);  // 可用仓库
        			$facilities = $this->getTaobaoShopFacility ($facilities, $taobaoShop['taobao_shop_conf_id']) ;
            		
            		$inventorySummaryAssoc = InventoryServices::getInventorySummaryAssocByProduct('INV_STTS_AVAILABLE', array_keys($facilities), $productId, null);
            		if (!empty($inventorySummaryAssoc)) {
            			foreach ($inventorySummaryAssoc as $assoc) {
            				foreach ($assoc as $productStock) {
            				    $items[$idx]['available_to_reserved'] += $productStock->availableToReserved;
                                $items[$idx]['stock_quantity'] += $productStock->stockQuantity;	
                                //检查每个仓中商品库存数量是否异常
                                if ($productStock->availableToReserved > $productStock->stockQuantity) {
                                    echo 'taobaoshop ' .$taobaoShop['nick'].' outer_id '. $item['outer_id'] .' facilityId ' . $productStock->facilityId
                                        .' availableToReserved ' . $productStock->availableToReserved . ' stockQuantity ' . $productStock->stockQuantity;
                                    $items[$idx]['available_to_reserved'] = $items[$idx]['stock_quantity'];
                                }
                            }
                        }
                        //实际可预定商品库存 - ERP里已有订单且未预定成功的预存
                        $need_to_reserved = isset($need_to_reserved_hashamp[$productId])?(($need_to_reserved_hashamp[$productId] != NULL) ? (int)$need_to_reserved_hashamp[$productId] : 0) : 0; 
                        $items[$idx]['available_to_reserved'] = max(0, $items[$idx]['available_to_reserved'] - $need_to_reserved);
                        //检查是否出现库存异常  
                        if ($item['reserve_quantity'] > $items[$idx]['available_to_reserved']) {
                            
                            //检查商品同步库存小于预警库存时邮件预警 发送给店长
                            $html_a[$item['ware_id']][] = array('goods_name' => $item['goods_name'], 'outer_id' =>  $item['outer_id'], 'quantity' =>  $item['quantity'],
                            'available_to_reserved' => $items[$idx]['available_to_reserved'], 'reserve_quantity' => $item['reserve_quantity']);                              
                            unset($items[$idx]);
                            continue;
                        }
                        if($item['is_use_reserve'] == 1 ) {
                            $items[$idx]['available_to_reserved'] = max(0, $items[$idx]['available_to_reserved'] - $item['reserve_quantity']);
                            echo 'taobaoshop ' .$taobaoShop['nick'].' outer_id '. $item['outer_id'].' ware_id '. $item['ware_id']." subtraction quantity:".$item['reserve_quantity']."\n";
                        } 
            		}
            		
            	}
            }
            foreach ($html_a as $num_item) {
                foreach($num_item as $item){
                     $html .= "
                        <tr>
                            <td>". $item['goods_name'] ."</td>
                            <td>". $item['outer_id'] ."</td>
                            <td>". $item['available_to_reserved'] ."</td>
                            <td>". $item['reserve_quantity'] ."</td>
                        </tr>
                    ";
                }
            }


            // 库存预警计算所需时间
            echo "[".date('c')."] " . $taobaoShop['nick'] . " 库存预警计算库存项数：" . $calc_count ." 耗时：".(microtime(true)-$calc_start)."\n";
            
            if ($html) {
                $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
                    <html xmlns="http://www.w3.org/1999/xhtml">
                    <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                    <style type="text/css">
                        table, table tr, table th, table td{
                            border:1px solid #000;
                            border-collapse: collapse;
                        }
                    </style>
                    </head>
                    <body style="font-size:12px;color:#000;">
                    <div style="margin:auto;width:800px;">
                    <h3>店铺名称：'.$taobaoShop['nick'] .' 请店长注意该商品在京东上库存。</h3>
                    <table width="100%">
                        <tr>
                            <th>商品名称</th>
                            <th>商家编码</th>
                            <th>可预订库存</th>  
                            <th>预警库存</th>
                        </tr>' . $html . "</table></div></body></html>";
                $to = array_merge($to_email,array(
                    array('name' => 'ERP', 'email' => 'erp@i9i8.com'),
                    array('name' => '流程组', 'email' => 'liucheng@i9i8.com'),
                    ));
                $subject = "店铺名称：".$taobaoShop['nick'] ."京东库存同步商品预警列表";
                $this->sendMail($subject, $from, $to, $html, $taobaoShop['nick']);
            }
            // 已上架但库存不足
            // 已下架但有库存
            $sync_start = microtime(true);
            $sync_count = 0;
            foreach($items as $item)
            {
            	if ($item['available_to_reserved'] != $item['quantity']) {
	            	$sync_count++;
	                $this->updateItemStock($taobaoShop, $item);
            	}
            }     
            echo "[".date('c')."] " . $taobaoShop['nick'] . " 库存同步库存项数：" . $sync_count ." 耗时：".(microtime(true)-$sync_start)."\n";
            sleep(1);
        }
        
         echo "[".date('c')."] " . "库存同步耗时：".(microtime(true)-$start)."\n";
    
    }
    
    /**
	 * 更新京东商品库存
	 * 
	 * @param array $taobaoShop 淘宝店铺 
	 * @param array $item       商品
	 * 
	 * @return boolean
	 */
    protected function updateItemStock($taobaoShop, $item)
    {

		$client=Yii::app()->getComponent('romeo')->JdProductService;
		$request=array("skuId"=>$item['sku_id'],"quantity"=> (int)$item['available_to_reserved'],"applicationKey"=>$taobaoShop['application_key']);
		// 库存同步更新
    	try {
			$res = $client->updateSkuStock($request);
			if ($res->return->code != "SUCCEED") {
				echo("[" . date('c') . "] " . "failed: (sku_id = {$item['sku_id']} outer_id = {$item['outer_id']}" . ") " . $res->return->msg . " \n");
			} else {
				echo("[" . date('c') . "] " . "succeed: (sku_id = {$item['sku_id']} outer_id = {$item['outer_id']}" . ") update quantity: {$item['available_to_reserved']} \n");
				$sql = "update ecshop.ecs_jd_goods set quantity = " . (int)$item['available_to_reserved'] . " where jd_goods_id = " . $item['jd_goods_id'] . " limit 1 ;";
                Yii::app()->getDb()->createCommand($sql)->execute();
			}
      	} catch(Exception $e) {
      		echo("[" . date('c') . "] " . "failed: (sku_id = {$item['sku_id']} outer_id = {$item['outer_id']}" . ") " . $e->getMessage(). " \n");
      	}
    }
    
    /**
	 * 取得启用的京东店铺的列表
	 * 
	 * @return array
	 */
    protected function getTaobaoShopList()
    {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK' and shop_type = '360buy'";
            $list=$this->getSlave()->createCommand($sql)->queryAll();
            $command=$this->getSlave()->createCommand("select * from taobao_api_params where taobao_api_params_id=:id");
            foreach($list as $key=>$item)
            $list[$key]['params']=$command->bindValue(':id',$item['taobao_api_params_id'])->queryRow();
        }
        return $list;
    }
    
    /**
     * 
     * 获取店铺同步库存的仓库列表
     */
    function getTaobaoShopFacility ($facilities, $taobao_shop_conf_id) {
    	if (isset($facilities['69897656'])) {
    		unset($facilities['69897656']);//天猫超市
    	}
    	if ($taobao_shop_conf_id == 14) {
    		//电商服务商2
    		if (isset($facilities['76065524'])) {
    			unset($facilities['76065524']);
    		}
    	}
        if ($taobao_shop_conf_id == 22) {
        	//金佰利旗舰店
        	if (isset($facilities['19568548'])) {
        		unset($facilities['19568548']);//电商服务东莞仓
        	}
        }
        return $facilities;
    }
    
    /**
     * 获取所有店铺七天内未预订单成功订单所使用商品库存
     */
 	protected function getPorductNeedToReservedList($startTime, $endTime){
    	$sql = "
    	    SELECT 		p.product_id, sum(IF (r.goods_number is null, og.goods_number, (r.goods_number - r.reserved_quantity))) as pending_count
    		FROM		ecshop.ecs_order_info o 
            LEFT JOIN ecshop.ecs_order_goods og on o.order_id = og.order_id
            LEFT JOIN romeo.product_mapping p on og.goods_id = p.ecs_goods_id and og.style_id = p.ecs_style_id
    		LEFT JOIN	romeo.order_inv_reserved_detail r on convert(og.rec_id using utf8) = r.order_item_id
    		WHERE 	 	o.order_status in(0, 1) 
    		AND 		o.shipping_status IN (0, 10)
    		AND 		o.facility_id is not null
    		AND 		o.facility_id != ''  
    		AND			( (o.order_type_id = 'SALE' AND (o.pay_id = 1 OR o.pay_status = 2) ) 
						OR o.order_type_id IN ('RMA_EXCHANGE', 'SHIP_ONLY'))
		    AND 		o.order_time <= '{$endTime}' 
    		AND 		o.order_time > '{$startTime}'
    		AND 		p.product_id IS NOT NULL and o.facility_id not in ('77451244', '69897656')
    		GROUP BY 	p.product_id
    		HAVING		pending_count > 0
    	";
        $need_to_reserved_list = $this->getSlave()->createCommand($sql)->queryAll();
	    return $need_to_reserved_list;
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

    /**
     * 发送邮件
     * @param string $subject
     * @param $array $from  $from = array ('email' => 'erp@leqee.com', 'name' => 'ERP定时库存警报任务');
     * @param $array $to $to = array (array('email' => 'bbgfx@i9i8.com', 'name' => '步步高电教分销组'));
     */
    private function sendMail($subject, $from, $to, $html, $shop_name) {
        if (empty($subject)) {
            echo "{$shop_name}邮件主题为空。\n";
            return ;
        }
        if (empty($from)) {
            echo "{$shop_name}邮件发送者信息为空。\n";
            return ;
        }
        if (empty($to)) {
            echo "{$shop_name}邮件接收者信息为空。\n";
            return ;
        }
        $mail = Helper_Mail::smtp();
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->SetFrom("{$from['email']}", "{$from['name']}");
        $mail->IsHTML(true);
        $mail->ClearAddresses();
//        $mail->AddAddress("zgliu@leqee.com", "刘志刚");
//        $mail->AddAddress("lwlei@leqee.com", "雷林伟");
//        $mail->AddAddress("bfan@leqee.com", "樊斌");
        foreach ($to as $item) {
            $item['email'] = trim($item['email']);
            $mail->AddAddress("{$item['email']}", "{$item['name']}");
        }
        
        $mail->Body = $html;
        if ($mail->send()){
            echo "send email to {$shop_name} successful \n";
        } else {
            echo "send email to {$shop_name} failed \n";
        }
    }
}
?>
