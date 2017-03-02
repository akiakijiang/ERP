<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
include_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once (ROOT_PATH . 'includes/helper/array.php');
Yii::import('application.commands.LockedCommand', true);

class AutoSplitKuajingOrdersCommand extends CConsoleCommand
{
	
	private function log ($m) {
		print $m . "\r\n";
	}
	
	 /**是否是乐其跨境 做了特殊标记的订单 
       返回 true 是做了特殊标记的订单 该订单不需要自动确认 
    */ 
    function isOrderHaiGuan($order_id){
    	/*过滤掉已经存入haiguan_order_split的订单*/
		$sql = "select 1 from  ecshop.haiguan_order_split where order_id = '{$order_id}' limit 1"; 
		$hai = Yii::app()->getDb()->createCommand($sql)->queryAll();  
		if(!empty($hai)){
			return true; 
		}								 
    	return false; 
    }
    
	public function actionSplitOrder()
    {
    	global $db;
		$start = microtime(true);
 		/*在乐其跨境下组织下面的店铺未确认待发货已付款的订单，并且税额>50的不需要自动确认，需要拆分
        * by hzhang1 2015-08-12*/
		$sql = "select eog.order_id,eoi.order_sn,
				cast(case eoi.source_type when 'taobao' then _utf8'tmall' 
					 else _utf8'ntmall' end as char(64)) as source_type,
				eoi.order_time,eoi.order_amount,eoi.taobao_order_sn,d.name,d.distributor_id,md.type,if(sum(eog.goods_price*eog.goods_number*ifnull(bgt.tax_rate,0.1))>50,sum(eog.goods_price*eog.goods_number*ifnull(bgt.tax_rate,0.1)),0) as total
			from ecshop.ecs_order_info eoi
			left JOIN ecshop.ecs_order_goods eog on eoi.order_id = eog.order_id
			left JOIN ecshop.bw_goods_tax bgt on if(eog.style_id=0,eog.goods_id,concat_ws('_',eog.goods_id,eog.style_id))= bgt.outer_id
			left join ecshop.distributor d on eoi.distributor_id = d.distributor_id
			left join ecshop.main_distributor md on md.main_distributor_id = d.main_distributor_id
			where eoi.order_time > date_sub(NOW(), INTERVAL 7 day) and eoi.party_id = 65638 and eoi.order_status = 0 and eoi.shipping_status = 0 and eoi.pay_status = 2
			group BY eoi.order_id";
		$order_list = Yii::app()->getDb()->createCommand($sql)->queryAll();
		
		if (!empty ($order_list)) {
			foreach ($order_list as $order_) {
				if($order_['total'] > 50){
					$order_id=$order_['order_id'];
					$haiguan = $this->isOrderHaiGuan($order_id); 
					if($haiguan) {
						$this->log($order_['order_id']." is in the table(haiguan_order_split).");
						continue;
					}
						
					$sql = "select goods_amount as total,bonus from ecshop.ecs_order_info where order_id = '{$order_id}'";
					$order_bonus = $db->getRow($sql);
					$this->log($order_bonus['total']);
					$sql = "select * from ecshop.ecs_order_goods where order_id = '{$order_id}'";
					$order_goods = $db->getAll($sql);
					$total_rate = 0;
					foreach($order_goods as $goods){
						$good_price_bonus=$goods['goods_price']+$order_bonus['bonus']*$goods['goods_price']/$order_bonus['total'];
						
						$sql ="select ifnull(tax_rate,0.1) as rate from ecshop.ecs_order_goods eog
								left JOIN ecshop.bw_goods_tax bgt on if(eog.style_id=0,eog.goods_id,concat_ws('_',eog.goods_id,eog.style_id)) = bgt.outer_id 
								where eog.rec_id = '{$goods['rec_id']}' limit 1";
						$rate = $db->getRow($sql);
						$total_rate += $goods['goods_number']*$good_price_bonus*$rate['rate'];
					}
					
					if($total_rate > 50){
						$sql = "insert into ecshop.haiguan_order_split(order_id,order_sn,parent_order_id,order_time,goods_amount,taobao_order_sn,name,type,distributor_id,created_stamp,last_updated_stamp,source_type) 
								values('{$order_['order_id']}','{$order_['order_sn']}',0,'{$order_['order_time']}','{$order_['order_amount']}','{$order_['taobao_order_sn']}','{$order_['name']}','{$order_['type']}','{$order_['distributor_id']}',now(),now(),'{$order_['source_type']}')
								ON DUPLICATE KEY UPDATE order_id='{$order_['order_id']}',order_sn='{$order_['order_sn']}',order_time='{$order_['order_time']}',goods_amount='{$order_['order_amount']}',taobao_order_sn='{$order_['taobao_order_sn']}',name='{$order_['name']}',type='{$order_['type']}',distributor_id='{$order_['distributor_id']}',last_updated_stamp=now(),source_type='{$order_['source_type']}'";
						$db->query($sql);
						$this->log($order_['order_id']." need to split,insert into the table.");
						$this_order_can = false; 
					}else{
						$this->log($order_['order_id'].'step');
					}
				}else{
					$this->log($order_['order_id']." don't need to split,continue.");
				}
			}
		}
		echo "[". date('c'). "]  haiguan_order_split spending time：".(microtime(true)-$start)."\n";
    }  
}
?>
