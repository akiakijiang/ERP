<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
//require_once(ROOT_PATH . 'includes/helper/array.php');
/*
 * Created on 2015-7-20
 *
 * to check OR's order_discount_fee + goods_discount_fee  = -bonus  or not
 * 
 */
 
class CheckDiscountFeeCommand extends CConsoleCommand {
	
 	private $db;  // db数据库
 	
 	public function actionCheckDiscount()
 	{
 		//获取所有order_discount_fee + goods_discount_fee  ！= -bonus  的订单
 		$sql = "select eoi.order_id, eoi.bonus,ifnull(oa.attr_value,0) as order_discount,sum(ifnull(oga.value,0)) as goods_discount from ecshop.ecs_order_info eoi
				left join ecshop.order_attribute oa on oa.order_id = eoi.order_id and oa.attr_name = 'DISCOUNT_FEE'
				left join ecshop.ecs_order_goods eog on eog.order_id = eoi.order_id 
				left join ecshop.order_goods_attribute oga on oga.order_goods_id = eog.rec_id and oga.name = 'DISCOUNT_FEE'
				where eoi.order_time > date_sub(curdate(),interval 1 day) and eoi.party_id = '65619' and eoi.order_status = '0'
				group by eoi.order_id
				having bonus != -(order_discount+goods_discount)";
		$orders = $this->getDB()->createCommand($sql)->queryAll();
		
		foreach($orders as $order) {
			$temp = -1*($order['order_discount']+$order['goods_discount']+$order['bonus'])/100;	
			if((intval($temp) == $temp) && ($temp != 0)) {//获取需要增加商品级别的商品
				$sql = "select eog.order_id,eog.rec_id,eog.goods_number from ecshop.ecs_order_goods eog
						inner join ecshop.order_goods_attribute oga on oga.order_goods_id  = eog.rec_id and oga.`name` = 'OUTER_IID'
						left join ecshop.order_goods_attribute oga1 on oga1.order_goods_id  = eog.rec_id and oga1.name = 'DISCOUNT_FEE'
						where eog.order_id = '{$order['order_id']}' and eog.goods_id = '196993' and (oga1.value = 0 or oga1.value is null)";
				$order_goods = $this->getDB()->createCommand($sql)->queryAll();
				if(!empty($order_goods)){
					$total_count = 0; // 商品总数量
					foreach($order_goods as $order_good) {
						$total_count += $order_good['goods_number'];
					}
					if($total_count == $temp) {
						$sql = "select * from ecshop.order_attribute where order_id = '{$order['order_id']}' and attr_name = 'DISCOUNT_FEE'";
						$order_discount = $this->getDB()->createCommand($sql)->queryRow();
						if(empty($order_discount)) {
							$modify_discount = "insert into ecshop.order_attribute (order_id,attr_name,attr_value) value ('{$order['order_id']}','DISCOUNT_FEE','" . (100*$total_count) . "')";
						} else {
							$modify_discount = "update ecshop.order_attribute set attr_value = '" . ($order_discount['attr_value'] + (100*$total_count)) . "' where order_id = '{$order['order_id']}' and attr_name = 'DISCOUNT_FEE'";
						}
						$this->getDB()->createCommand($modify_discount)->execute();
						echo("[". date('c'). "] CheckDiscountFeeCommand  order_id:".$order['order_id']." success! \n");
					}
				}				
			}
		}
 	}
 	
 	 /**
	 * 取得master数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getDB()
    {
        if(!$this->db)
        {
            $this->db=Yii::app()->getDb();
            $this->db->setActive(true);
        }
        return $this->db;
    }
}

?>