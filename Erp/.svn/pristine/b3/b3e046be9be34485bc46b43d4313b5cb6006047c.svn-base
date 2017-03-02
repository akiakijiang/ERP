<?php
/**
 * 自动出库发货
 * @author jrpei
 *
 */
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'admin/includes/lib_filelock.php';
require_once ROOT_PATH . 'RomeoApi/lib_inventory.php';
require_once ROOT_PATH . "admin/includes/lib_order_mixed_status.php";
require_once ROOT_PATH . 'includes/helper/mail.php';

class AutoDeliveryCommand extends CConsoleCommand {
    private $slave;  // Slave数据库
    public function actionIndex(){
		$this->run(array('getVirtualOrder'));
		$this->run(array('getTmallFacilityOrder'));
		$this->run(array('syncDeliveryStatus'));
		$this->run(array('syncDeliveryStatusNew'));
    }
    /**
     * 对虚拟商品需要自动出库自动发货订单查看
     */
    public function actiongetVirtualOrder($start_time = null, $end_time = null) {
    	if ($start_time == null) {
    		$start_time = date("Y-m-d H:i:s", time() - 3600*24);
    	}
    	if ($end_time == null) {
    		$end_time = date("Y-m-d H:i:s", time());
    	}
    	$sql = "
    		select distinct o.order_id, s.shipment_id
            from ecshop.ecs_order_info o
            inner join romeo.party p ON convert(o.party_id using utf8) = p.party_id and p.system_mode in(2,3)
            left join romeo.order_shipment os on os.order_id = convert(o.order_id using utf8)
            left join romeo.shipment s on s.shipment_id = os.shipment_id
            left join romeo.order_inv_reserved r on r.order_id = convert(o.order_id using utf8)
						left join ecshop.ecs_payment p on p.pay_id = o.pay_id
            where o.order_type_id = 'sale' and o.order_status = 1 and IF(p.is_cod = 1,o.pay_status = 0 ,o.pay_status = 2 )
                and o.shipping_status = 0 and r.reserved_time >= '{$start_time}' and r.reserved_time < '{$end_time}'
                and r.status = 'Y'
                and not exists (select 1 from  ecshop.ecs_order_goods og, ecshop.ecs_goods g, ecshop.ecs_category c
                 where o.order_id = og.order_id and og.goods_id = g.goods_id and g.goods_party_id = o.party_id 
                  and g.cat_id = c.cat_id and c.cat_name != '虚拟商品' 
                  limit 1)  
			group by os.shipment_id
            having
            count(os.order_id) = 1
            limit 200
        ";
    	$db=Yii::app()->getDb();
        $order_list = $db->createCommand($sql)->queryAll();
        if (!empty($order_list)) {
        	$this->deliveryOrderList($order_list);
        	
        	$this->syncDeliveryStatus();
        	$this->syncDeliveryStatusNew();
        } else {
        	echo date('c') . " order_list empty \n";
        }
    }
    public function actionSYNCDELIVERYSTATUS ($appkey = null)  {
    	$this->syncDeliveryStatus($appkey);
    	$this->syncDeliveryStatusNew($appkey);
    }
    /**
     * 可以单独调用
     * 将虚拟商品的发货状态同步到淘宝中
     * @param string appkey appkey为空时同步所有启用淘宝订单，否则同步知道appkey店铺
     * 
     */
    public function syncDeliveryStatus ($appkey = null)  {
    	
        $db=Yii::app()->getDb();
        //查询虚拟商品已发货未同步订单
        $sql1 = "
			select
				mp.mapping_id, mp.taobao_order_sn as mp_taobao_order_sn, mp.shipping_status as mp_shipping_status, mp.type,
				o.shipping_name, o.order_sn, o.distribution_purchase_order_sn, o.taobao_order_sn,o.facility_id, o.party_id
			from
				ecs_taobao_order_mapping as mp
				inner join ecs_order_info as o ON o.order_id = mp.order_id AND o.order_status = 1 AND o.pay_status = 2 AND o.shipping_status = 1
			where
				mp.shipping_status='' and mp.application_key=:key 
				and not exists (select 1 
					from ecshop.ecs_order_goods og, ecshop.ecs_goods g, ecshop.ecs_category c
                    where o.order_id = og.order_id and og.goods_id = g.goods_id and g.goods_party_id = o.party_id 
                        and g.cat_id = c.cat_id and c.cat_name != '虚拟商品' 
                    limit 1) 
		";
		
		
		
		// 更新发货状态
        $sql2="update ecs_taobao_order_mapping set shipping_status='WAIT_BUYER_CONFIRM_GOODS' where mapping_id = :id";
        
        // 淘宝上找不到的订单
        $sql3="update ecs_taobao_order_mapping set shipping_status='TRADE_FINISHED' where mapping_id = :id";
        
        $taobaoShopList = $this->getTaobaoShopList();
        if (!empty($taobaoShopList)){
        	foreach ($taobaoShopList as $taobaoShop) {
        		if ($appkey != null && $appkey != $taobaoShop['application_key']) {
        			continue;
        		}
        		echo date('c') . $taobaoShop['nick'] . " VirtuanlOrder delivery start \n";
				$order_list=$db->createCommand($sql1)->bindParam(':key',$taobaoShop['application_key'])->queryAll();
				foreach($order_list as $order) {
	                // 分销订单, 用分销采购订单号发货
					$taobaoSn = $order['type']=='fenxiao' ? $order['distribution_purchase_order_sn'] : $order['taobao_order_sn'];
					if (empty($taobaoSn)) {
						echo date('c') . " taobaoSn is empty. order_id: ". $order['order_id']; 
						continue;
					}
	                $request = array('tid'=>$taobaoSn);
	                // 请求淘宝发货
	                try {
	                    $response = null ;
	                    $response=$this->getTaobaoClient($taobaoShop)->execute('taobao.logistics.dummy.send',$request);
	                    if($response->isSuccess()) {
	                        $update=$db->createCommand($sql2)->bindValue(':id',$order['mapping_id'])->execute();
							echo  date('c') ." taobaoSn: " . $taobaoSn ." VirtualOrder update  WAIT_BUYER_CONFIRM_GOODS result: ". $update ." \n";
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:B01') {
	                    	 // 淘宝上面已经不存在的订单
	                    	$update=$db->createCommand($sql3)->bindValue(':id',$order['mapping_id'])->execute();
	                    	echo  date('c') ." taobaoSn: " . $taobaoSn ." 物流订单不存在  update TRADE_FINISHED result: ".$update." \n";
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:B02') {
	                    	echo  date('c') ." taobaoSn: " . $taobaoSn ." 没有权限进行发货\n";
	                    } else if($response->sub_code == 'isv.logistics-dummy-service-error:B04' ||
	                    	$response->sub_code == 'isv.logistics-dummy-service-error:B55' || 
	                    	$response->sub_code == 'isv.logistics-dummy-service-error:B27'
	                    ) {
	                    	// 已经手动发货了
	                        $update=$db->createCommand($sql2)->bindValue(':id',$order['mapping_id'])->execute();
	                        echo  date('c') ." taobaoSn: " . $taobaoSn ." VirtualOrder has been send. update  WAIT_BUYER_CONFIRM_GOODS result: ". $update ." \n";
	                    } else if ($response->sub_code == 'isv.invalid-parameter') {
	                    	print  date('c') ." taobaoSn: " . $taobaoSn ." 参数无效，格式不对、非法值、越界等 ".$request."\n";
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:B98') {
	                    	echo  date('c') ." taobaoSn: " . $taobaoSn ." 非虚拟物品，不能用虚拟发货。 \n";
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:P03') {
	                    	//session过期 需要发送邮件
	                    	try {
								$mail=Yii::app()->getComponent('mail');
								$mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." Session过期";
								$mail->Body="
									该淘宝店铺的session过期了，不能进行同步动作，请按下面的链接取得session后更新到erp中。
									http://auth.open.taobao.com/?appkey=".$taobaoShop['params']['app_key']. " \n
									http://container.open.taobao.com/container?authcode={授权码}
									";
								$mail->AddAddress('jrpei@i9i8.com', '裴君蕊');
								$mail->send();
							} catch (Exception $e){
							}
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:S01') {
	                    	echo  date('c') ." taobaoSn: " . $taobaoSn ." 系统异常 \n";
	                    } else {
	                    	// 其他错误
	                        echo(" has error: ".$response->code.", ".$response->msg." \n");
	                    }
	                } catch (Exception $e) {
	                    echo(" has exception: ". $e->getMessage() . "\n");
	                }
	                usleep(500000);
	            }
			}
        }
    }
    
    
    /**
     * 可以单独调用
     * 将虚拟商品的发货状态同步到淘宝中
     * 添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
     * @param string appkey appkey为空时同步所有启用淘宝订单，否则同步知道appkey店铺
     * 
     */
    public function syncDeliveryStatusNew ($appkey = null)  {
    	
        $db=Yii::app()->getDb();
		
		//添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用，后面用来取代$sql1
		$sql1 = "
			select
				mp.mapping_id, mp.outer_order_sn as mp_taobao_order_sn, mp.shipping_status as mp_shipping_status, mp.platform as type,
				o.shipping_name, o.order_sn, o.distribution_purchase_order_sn, o.taobao_order_sn,o.facility_id, o.party_id
			from
				ecs_order_mapping as mp
				inner join ecs_order_info as o ON o.order_id = mp.erp_order_id AND o.order_status = 1 AND o.pay_status = 2 AND o.shipping_status = 1
			where
				mp.shipping_status='' and mp.application_key=:key 
				and not exists (select 1 
					from ecshop.ecs_order_goods og, ecshop.ecs_goods g, ecshop.ecs_category c
                    where o.order_id = og.order_id and og.goods_id = g.goods_id and g.goods_party_id = o.party_id 
                        and g.cat_id = c.cat_id and c.cat_name != '虚拟商品' 
                    limit 1) 
		";
		
		// 更新发货状态
        $sql2 ="update ecs_order_mapping set shipping_status='WAIT_BUYER_CONFIRM_GOODS' where mapping_id = :id"; //添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
        
        // 淘宝上找不到的订单
        $sql3 ="update ecs_order_mapping set shipping_status='TRADE_FINISHED' where mapping_id = :id";  //添加ecs_order_mapping中间表，废除ecs_taobao_order_mapping用
        
        $taobaoShopList = $this->getTaobaoShopList();
        if (!empty($taobaoShopList)){
        	foreach ($taobaoShopList as $taobaoShop) {
        		if ($appkey != null && $appkey != $taobaoShop['application_key']) {
        			continue;
        		}
        		echo date('c') . $taobaoShop['nick'] . " VirtuanlOrder delivery start \n";
				$order_list=$db->createCommand($sql1)->bindParam(':key',$taobaoShop['application_key'])->queryAll();
				foreach($order_list as $order) {
	                // 分销订单, 用分销采购订单号发货
					$taobaoSn = $order['type']=='fenxiao' ? $order['distribution_purchase_order_sn'] : $order['taobao_order_sn'];
					if (empty($taobaoSn)) {
						echo date('c') . " taobaoSn is empty. order_id: ". $order['order_id']; 
						continue;
					}
	                $request = array('tid'=>$taobaoSn);
	                // 请求淘宝发货
	                try {
	                    $response = null ;
	                    $response=$this->getTaobaoClient($taobaoShop)->execute('taobao.logistics.dummy.send',$request);
	                    if($response->isSuccess()) {
	                        $update=$db->createCommand($sql2)->bindValue(':id',$order['mapping_id'])->execute();
							echo  date('c') ." taobaoSn: " . $taobaoSn ." VirtualOrder update  WAIT_BUYER_CONFIRM_GOODS result: ". $update ." \n";
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:B01') {
	                    	 // 淘宝上面已经不存在的订单
	                    	$update=$db->createCommand($sql3)->bindValue(':id',$order['mapping_id'])->execute();
	                    	echo  date('c') ." taobaoSn: " . $taobaoSn ." 物流订单不存在  update TRADE_FINISHED result: ".$update." \n";
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:B02') {
	                    	echo  date('c') ." taobaoSn: " . $taobaoSn ." 没有权限进行发货\n";
	                    } else if($response->sub_code == 'isv.logistics-dummy-service-error:B04' ||
	                    	$response->sub_code == 'isv.logistics-dummy-service-error:B55' || 
	                    	$response->sub_code == 'isv.logistics-dummy-service-error:B27'
	                    ) {
	                    	// 已经手动发货了
	                        $update=$db->createCommand($sql2)->bindValue(':id',$order['mapping_id'])->execute();
	                        echo  date('c') ." taobaoSn: " . $taobaoSn ." VirtualOrder has been send. update  WAIT_BUYER_CONFIRM_GOODS result: ". $update ." \n";
	                    } else if ($response->sub_code == 'isv.invalid-parameter') {
	                    	print  date('c') ." taobaoSn: " . $taobaoSn ." 参数无效，格式不对、非法值、越界等 ".$request."\n";
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:B98') {
	                    	echo  date('c') ." taobaoSn: " . $taobaoSn ." 非虚拟物品，不能用虚拟发货。 \n";
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:P03') {
	                    	//session过期 需要发送邮件
	                    	try {
								$mail=Yii::app()->getComponent('mail');
								$mail->Subject="淘宝店铺 ". $taobaoShop['nick'] ." Session过期";
								$mail->Body="
									该淘宝店铺的session过期了，不能进行同步动作，请按下面的链接取得session后更新到erp中。
									http://auth.open.taobao.com/?appkey=".$taobaoShop['params']['app_key']. " \n
									http://container.open.taobao.com/container?authcode={授权码}
									";
								$mail->AddAddress('jrpei@i9i8.com', '裴君蕊');
								$mail->send();
							} catch (Exception $e){
							}
	                    } else if ($response->sub_code == 'isv.logistics-dummy-service-error:S01') {
	                    	echo  date('c') ." taobaoSn: " . $taobaoSn ." 系统异常 \n";
	                    } else {
	                    	// 其他错误
	                        echo(" has error: ".$response->code.", ".$response->msg." \n");
	                    }
	                } catch (Exception $e) {
	                    echo(" has exception: ". $e->getMessage() . "\n");
	                }
	                usleep(500000);
	            }
			}
        }
    }
    
    
    /**
	 * 返回请求对象
	 *
	 * @param array $taobaoShop
	 * @return TaobaoClient
	 */
    protected function getTaobaoShopList() {
        static $list;
        if(!isset($list))
        {
            $sql="select * from taobao_shop_conf where status='OK'";
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
    protected function getTaobaoClient($taobaoShop){
        static $clients=array();
        $key=$taobaoShop['taobao_shop_conf_id'];
        if(!isset($clients[$key])) {
        	require_once ROOT_PATH . 'protected/components/TaobaoClient.php';
	        $clients[$key]=new TaobaoClient($taobaoShop['params']['app_key'],$taobaoShop['params']['app_secret'],$taobaoShop['params']['session_id'],($taobaoShop['params']['is_sandbox']=='Y'?true:false));
        }
        return $clients[$key];
    }
    /**
     * 对天猫超市订单自动出库自动发货
     * 目前自对金佰利使用
     */
    public function actiongetTmallFacilityOrder($start_time = null, $end_time = null) {
    	if ($start_time == null) {
    		$start_time = date("Y-m-d", time() - 3600*24);
    	}
    	if ($end_time == null) {
    		$end_time = date("Y-m-d H:i:s", time());
    	}
    	$sql = "
            select distinct o.order_id, s.shipment_id
            from ecshop.ecs_order_info o
            inner join  romeo.party p ON convert(o.party_id using utf8) = p.party_id and p.system_mode in(2,3)
            left join romeo.order_shipment os on os.order_id = convert(o.order_id using utf8)
            left join romeo.shipment s on s.shipment_id = os.shipment_id
            left join romeo.order_inv_reserved r on r.order_id = convert(o.order_id using utf8)
						left join ecshop.ecs_payment p on p.pay_id = o.pay_id
            where o.order_type_id = 'sale' and o.order_status = 1 and IF(p.is_cod = 1,o.pay_status = 0 ,o.pay_status = 2 )
                and o.shipping_status = 0 and r.reserved_time >= '{$start_time}' and r.reserved_time < '{$end_time}'
                and r.status = 'Y'
                and o.facility_id in('77451244', '69897656') and o.party_id in('65558', '65578', '65569', '65547', '65581', '65559')
			group by os.shipment_id
            having
            count(os.order_id) = 1
            limit 50
        ";
        $db=Yii::app()->getDb();
        $order_list = $db->createCommand($sql)->queryAll();
        if (!empty($order_list)) {
        	$this->deliveryOrderList($order_list);
        } else {
        	echo date('c') . " order_list empty \n";
        }
    }
    /**
     * 对订单集合自动出库
     * @param array $order_list
     */
     function deliveryOrderList($order_list){
        global $db,$ecs;
        foreach ($order_list as $order ) {
            $order_info = get_core_order_info('', $order['order_id']);
            print_r ('order_id:');echo $order['order_id'].' </br>';
            // 开始组装数据
            $product_goods_id = array();
            foreach($order_info['order_goods'] as $order_goods) {
            	$product_goods_id[$order_goods['product_id']] = $order_goods['goods_id'];
            }
            $real_out_goods_numbers = array();
            $serial_numbers = array();
            $bad_order = false;
        
            // 得到商品未出库的数量数组
            $real_out_goods_numbers = get_order_no_out_goods_numbers($order['order_id']);
            print_r ('$real_out_goods_numbers:');print_r($real_out_goods_numbers);
            // 如果串号控制，得到未出库数量的串号
            foreach($real_out_goods_numbers as $product_id=>$no_out_number) {
            	if($no_out_number == 0) continue;
            	
            	$inventoryItemType = getInventoryItemType($product_goods_id[$product_id]);
			    if($inventoryItemType == 'SERIALIZED') {
			     	$result = get_no_out_serial_numbers($product_id,$order_info['facility_id'],$no_out_number);
			     	if(!empty($result['error'])) {
			     		echo '订单：'.$order['order_id'].' 商品：'.$order_goods['goods_id'].' 有问题：'; echo ($result['error']);
			     		$bad_order = true;
			     		break;
			     	}
			     	$serial_numbers[$product_id] = $result['serial_numbers'];
			    } 
            }
            if($bad_order) {
            	continue;
            }
            print_r('$serial_numbers');
            print_r($serial_numbers);
            $unmatched_notfound  = array();  // 输入的串号没有找到或已出库 
            $transfer_exception  = array();  // 出库异常
            $outsn_lock_exception = array(); // 获取出库单号锁异常
            $out_order_list = array();
            $out_order_list[] = $order_info;

            // 配货出库
            require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
            $result = stock_delivery($out_order_list,$real_out_goods_numbers,$serial_numbers);
            print_r('stock_delivery result:');pp($result);
            $unmatched_notfound = $result['unmatched_notfound'];
            $transfer_exception = $result['transfer_exception'];
            $outsn_lock_exception = $result['outsn_lock_exception'];
            $stock_delivery_error = $result['error'];
            // 所有商品都已成功出库了， 更新订单的状态， 提示配货成功
            if (empty($stock_delivery_error) && empty($no_scan_goods_name) && empty($unmatched_notfound) && empty($transfer_exception) && empty($outsn_lock_exception)) {
                    if ($order_info['shipping_status'] == 0 || $order_info['shipping_status'] == 10) {
						// 更改订单状态
                        $sql="UPDATE {$ecs->table('order_info')} SET shipping_status=9 WHERE order_id='%d' LIMIT 1";
                        $result=$db->query(sprintf($sql, $order_info['order_id']));
                        if ($result) {
                            // 记录订单操作历史
                            $sql = "
                                INSERT INTO {$ecs->table('order_action')} 
                                (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
                                ('{$order_info['order_id']}', '{$order_info['order_status']}', 9, '{$order_info['pay_status']}', NOW(), '%s', '{$_SESSION['admin_name']}')
                            ";
                            if ($order['shipping_status'] == 10) {
                                $db->query(sprintf($sql, '重新配货出库'));
                                //update_order_mixed_status($order_item['order_id'], array('warehouse_status'=>'re-picked'), 'worker');
                            } elseif ($order['shipping_status'] == 0) {
                                $db->query(sprintf($sql, '配货出库'));
                                //update_order_mixed_status($order_item['order_id'], array('warehouse_status'=>'picked'), 'worker');
                            }
                        }
                }
                
				//操作发货 
				$this->shipment($order_info);
            }
            // 有部分商品出库失败， 提示错误消息
            else {
                $info = '';
                if (!empty($stock_delivery_error)) {
                    $info .= '出库异常，请重新配货出库：' . implode(' ， ', $stock_delivery_error);
                }
                if (!empty($no_scan_goods_name)) {
                    $info .= '商品：' . implode(' ， ', $no_scan_goods_name) . '还没出库完.';
                }
                if (!empty($outsn_lock_exception)) {
                    $info .= "并发锁异常，请联系ERP组:" . implode(' ， ', $outsn_lock_exception) .".";
                }
                if (!empty($unmatched_notfound)) {
                    $info .= '串号' . implode(' ， ', $unmatched_notfound) . '不在系统中或已经出库了.';
                }
                if (!empty($transfer_exception)) {
                    $info .= "串号" . implode(' ， ', $transfer_exception) . '出库失败，请联系ERP组.';
                }
            }
            
            echo $info.'end';
		}
    }
    
	/**
	 * 指定订单操作发货
	 */
    function shipment($order_info) {
    	$db = Yii::app()->getDb();

    	// 更改订单状态
        $db->createCommand(sprintf("UPDATE ecshop.ecs_order_info SET shipping_time=UNIX_TIMESTAMP(), shipping_status=1 WHERE order_id='%d'",$order_info['order_id']))->execute();

        // 记录订单状态
        orderActionLog(array('order_id'=>$order_info['order_id'], 'order_status'=>$order_info['order_status'], 'shipping_status'=>1, 'pay_status'=>$order_info['pay_status'], 'action_note'=>'操作发货'));
        update_order_mixed_status($order_info['order_id'], array('shipping_status' => 'shipped'), 'worker');
		$handle = soap_get_client('ShipmentService');
		$response = $handle->getShipmentByOrderId(array('orderId' => $order_info['order_id']));
		if (!empty($response->return->Shipment)) {
			$shipment = $response->return->Shipment;
			try {
					$handle=soap_get_client('ShipmentService');
					$handle->updateShipment(array(
						'shipmentId'=>$shipment->shipmentId,
						'status'=>'SHIPMENT_SHIPPED',
						'lastModifiedByUserLogin'=>'system',
					));
				}
				catch (Exception $e) {
					echo 'message';
					var_dump($e->getMessage());
					break;
			 }
		} else {
			echo date('c'). " order_id: ".$order_info['order_id'] ." shipment_id is not exists \n";
	    } 
    }

	  /**
	 * 取得slave数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getSlave() {
        if(!$this->slave) {
            if(($this->slave=Yii::app()->getComponent('slave'))===null) {
	            $this->slave=Yii::app()->getDb();
	            $this->slave->setActive(true);
            }
        }
        return $this->slave;
    }		
}