<?php

/**
 * 短信定投
 * @author yxiang
 *
 */
class TmpCommand extends CConsoleCommand
{
	public function actionInit($beginSend = false)
	{
		$list = array();
		if(!$beginSend){
            $list =array(0 => array('consignee'=>'测试用户', 'mobile'=>'15906654347'));			
		}else{
		    // 购买过贝乐嘉系列的用户，商家编码（35703，35704，35705，35716，35717，35718，38202，38203）
		    $sql="
SELECT oi.consignee, oi.mobile from ecshop.ecs_order_info oi
  inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id
where oi.order_time >= '2011-04-01' and oi.order_time < '2011-05-01' and
      oi.order_type_id = 'SALE' and 
      oi.mobile is not null and oi.mobile <> '' and LENGTH(oi.mobile) = 11 and 
      og.goods_id in (38202, 38203)
 group by oi.mobile ;
		    	";
            
            $list=Yii::app()->getDb()->createCommand($sql)->queryAll();
		}
		
		var_dump($list);
		
		if($list!==array())
		{
			Yii::app()->getCache()->set('__list__',$list);
			Yii::app()->setGlobalState('_page',0);
			Yii::app()->setGlobalState('_mobiles',array());
			echo "page:". Yii::app()->getGlobalState('_page') ." \n";
			echo "list count:". count($list) ." \n";
			echo "send:". implode(',', Yii::app()->getGlobalState('_mobiles',array())) ." \n";
		}
		else 
			echo "no date! \n";
	}
	
	public function actionView()
	{
		$list=Yii::app()->getCache()->get('__list__');
		echo "page:". Yii::app()->getGlobalState('_page') ." \n";
		echo "list count:". count($list) ." \n";
		echo "send:". implode(',', Yii::app()->getGlobalState('_mobiles',array())) ." \n";
	}
	
	/**
	 * 短信发送
	 */
	public function actionSend()
	{
		$page=Yii::app()->getGlobalState('_page',0);
		$static=Yii::app()->getGlobalState('_mobiles',array());
		
		// $limit = 100;
		// $offset = $limit * $page;
		
		$message = "多美滋贝乐嘉7天体验宝宝健康便便，2阶段/3阶段盒装7折限时抢购；即日起至7月25日登录淘宝多美滋官方旗舰店抢购(淘宝网搜索“多美滋”)";
		$list=Yii::app()->getCache()->get('__list__');
		// $order_list=array_slice($list, $offset, $limit);
        $order_list = $list ;
        
		foreach($order_list as $order_item)
		{
			// 没有电话号码或已经发过了则不发
			if(empty($order_item['mobile']))
				continue;
						
			$success=Yii::app()->getComponent('msg')->send($message,$order_item['mobile'],"239133b81b0b4f0ca086fba086fec6d5");
			echo("|  send message to user (".$order_item['mobile'].") with result ". var_export($success,true) ." \n");
				
			// 发送过了记录下
			$static[]=$order_item['mobile'];
		}
		
		if($order_list!==array())
		{
		    Yii::app()->setGlobalState('_page',++$page);
		    Yii::app()->setGlobalState('_mobiles',$static);
		}
	}
	
	public function actionSendByPhoneNumber($beginSend = false){
		if(!$beginSend){
		    $mobileList = array(
		         '15906654347',
		    );
		}else{
			$mobileList = array(

			 );
			
		}
		
		var_dump($mobileList);
		
		$message = "7月8日怀轩名品专营店BV/宝缇嘉钱夹参加淘宝聚划算，专柜价3700现价2280包邮！赶快登录【淘宝聚划算】抢购，更多惊喜详见店铺海报！";
		
		foreach($mobileList as $item){
			if (empty($item))
			    continue ;
			    
			$success=Yii::app()->getComponent('msg')->send($message, $item, "239133b81b0b4f0ca086fba086fec6d5");
			echo("|  send message to user (".$item.") with result ". var_export($success,true) ." \n");
				
			// 发送过了记录下
			$static[]=$item;
			
			
		}
		
		
	}
	
	// 
	//
	//
	// 添加赠品
	public function actionFuck($args)
	{
		$page=Yii::app()->getGlobalState('page',0);
	
		$limit  = 100;
		$offset = $page*$limit;
		
		$sql="
select order_id,order_sn,order_amount,shipping_fee,order_time,order_status,shipping_status,pay_status,facility_id from ecs_order_info 
where party_id = 65538 and order_type_id='SALE' and shipping_status=0 and order_time>'2010-11-10' and order_time < '2010-11-12 13:50' and order_status!=2 and order_amount>100
order by order_time ASC
limit $offset, $limit
";
		$sql1="select goods_id,style_id,goods_name from ecs_order_goods where order_id = :order_id";
		$command=Yii::app()->getDb()->createCommand($sql1);
		
		$builder=Yii::app()->getDb()->getCommandBuilder();
		$table1=Yii::app()->getDb()->getSchema()->getTable('ecs_order_goods');  // 订单商品表
		$table2=Yii::app()->getDb()->getSchema()->getTable('ecs_oukoo_erp');  // ERP表
		$table3=Yii::app()->getDb()->getSchema()->getTable('ecs_order_action');  // ERP表
			
		$orders=Yii::app()->getDb()->createCommand($sql)->queryAll();
		
		$i=1;
		foreach($orders as $order)
		{
			echo "|- index : ".$i++.", page: ".$page.", orderId: ".$order['order_id']." orderSn: ".$order['order_sn']." act ". date('Y-m-d H:i:s') ."\n";
			 
			$orderItems=$command->bindValue(':order_id',$order['order_id'])->queryAll();
			if($orderItems===array())
				continue;
				
			foreach($orderItems as $item)
			{
				if($item['goods_id']==34530 && $item['style_id']==411)
				{
					echo("  have been added! \n");
					continue 2;
				}
			}
			
			// 插入该赠品
			$og=array(
				'order_id'=>$order['order_id'],
				'goods_id'=>34530,
				'style_id'=>411,
				'goods_name'=>'透明肩带 对',
				'goods_number'=>1,
				'goods_price'=>0,
				'status_id'=>'INV_STTS_AVAILABLE',
				'added_fee'=>1.17,
				'customized'=>'not-applicable',
			);
			$result1=(boolean)$builder->createInsertCommand($table1,$og)->execute();
			if($result1)
			{
				$order_goods_id=$builder->getLastInsertID($table1);
				echo("  add success! \n");
				
				// ERP
				$erp=array(
					'order_goods_id'=>$order_goods_id,
					'facility_id'=>$order['facility_id'],
					'last_update_time'=>date('Y-m-d H:i:s'),
					'is_new'=>'NEW',
					'provider_id'=>186,
					'order_type'=>'NONE',
					'action_user'=>'system',
				);
				$builder->createInsertCommand($table2,$erp)->execute();
				
				// 备注
				$act=array(
					'order_id'=>$order['order_id'],
					'order_status'=>$order['order_status'],
					'shipping_status'=>$order['shipping_status'],
					'pay_status'=>$order['pay_status'],
					'action_time'=>date('Y-m-d H:i:s'),
					'action_user'=>'system',
					'action_note'=>'满100赠送透明肩带',
				);
				$builder->createInsertCommand($table3,$act)->execute();
			}
		}
		
		// 不为空则继续
		if($orders!==array())
			Yii::app()->setGlobalState('page', ++$page);
	}
}
	