<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'admin/includes/lib_common.php';

class CreatePatchShipmentPickCommand extends CConsoleCommand{
    public function actionIndex(){
    	//创建可以批拣的商品信息
        $this->run(array('ShipmentPickList'));
    }
	public function actionShipmentPickList(){
		//起始时间
		$start_time = microtime(true); 
		//单个商品的查询
		$sql_g = "
			select sum(og.goods_number) as number, og.goods_id, og.style_id, og.goods_name as name, o.party_id
			from romeo.order_inv_reserved r
			left join ecshop.ecs_order_info as o on o.order_id = r.ORDER_ID
			left join ecshop.ecs_payment p on p.pay_id = o.pay_id
			left join ecshop.ecs_order_goods og on o.order_id = og.order_id
			WHERE (o.handle_time = 0 or o.handle_time < UNIX_TIMESTAMP()) and o.order_type_id in ('SHIP_ONLY', 'SALE', 'RMA_EXCHANGE')
				and if(p.pay_code = 'code', o.pay_status = 0, o.pay_status = 2) and o.order_status = 1 and o.shipping_status = 0
				and not exists (select 1  from ecshop.order_attribute oa where  oa.order_id = o.order_id and oa.attr_name = 'TAOBAO_ITEM_MEAL_NAME_EX' LIMIT 1)
				and not exists (SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND pick_list_status = 'printed' AND created_by_user_class = 'worker' LIMIT 1)
				and og.goods_id is not null and r.status = 'Y' 
				and not exists (select 1 from ecshop.ecs_order_goods og1
            		where o.order_id = og1.order_id and og1.goods_id is not null 
            		and (og1.goods_id, og1.style_id) not in ((og.goods_id, og.style_id)) limit 1 )
			group by og.goods_id, og.style_id, o.party_id
			having number > 20
			order by number desc
			limit 300
	    ";
		//套餐查询
		$sql_TC = "
			select sum(round(og.goods_number/gi.goods_number, 0)) as number, gg.code, o.party_id, gg.name
			from romeo.order_inv_reserved r
			left join  ecshop.ecs_order_info as o on o.order_id = r.ORDER_ID
			left join ecshop.ecs_payment p on p.pay_id = o.pay_id
			left join ecshop.ecs_order_goods og on o.order_id = og.order_id
			left join ecshop.distribution_group_goods_item gi on gi.goods_id = og.goods_id and gi.style_id = og.style_id
			left join ecshop.distribution_group_goods gg on gg.group_id = gi.group_id
			left join ecshop.order_goods_attribute oga1 on oga1.order_goods_id = og.rec_id 
			WHERE (o.handle_time = 0 or o.handle_time < UNIX_TIMESTAMP()) and o.order_type_id in ('SHIP_ONLY', 'SALE', 'RMA_EXCHANGE')
			and if(p.pay_code = 'code', o.pay_status = 0, o.pay_status = 2) and o.order_status = 1 and o.shipping_status = 0 
			and gg.status = 'OK' AND r.status = 'Y' and oga1.name = 'OUTER_IID' and oga1.value = gg.code and oga1.order_goods_id is not null
			and og.goods_number >= gi.goods_number
			and not exists (SELECT 1 FROM order_mixed_status_history WHERE order_id = o.order_id AND pick_list_status = 'printed' AND created_by_user_class = 'worker' LIMIT 1)
			and not exists (select 1 from  ecshop.order_goods_attribute oga  
				left join ecshop.distribution_group_goods gg1 on oga.name = 'OUTER_IID'  and oga.value = gg1.code
				where oga.order_goods_id = og.rec_id and gg1.party_id = o.party_id and gg1.code is null limit 1)
			group by gg.code, o.party_id
			having number > 20
			order by number desc
			limit 300
		";
		$db = Yii::app ()->getDb ();
        $goods_style_list = $db->createCommand($sql_g)->queryAll();
        //单品查询时间
        $goods_end_time = microtime(true) ;
        echo "[". date('c'). "] 单个商品销量查询耗时：" .($goods_end_time - $start_time)."\n";;
        $TC_list = $db->createCommand($sql_TC)->queryAll();
        $TC_end_time =  microtime(true) ;
        echo "[". date('c'). "] 套餐销量查询耗时：" .($TC_end_time - $goods_end_time)."\n";
        $sql_batch_no = "select batch_no from romeo.shipment_picklist order by id desc limit 1 ";
        $batch_no = $db->createCommand($sql_batch_no)->queryScalar();
        $batch_no =(empty($batch_no) ? 0 : $batch_no)+ 1;
        $TC_party = array();
        if ($TC_list) {
        	$sql = " insert into romeo.shipment_picklist (`goods_id`, `style_id`, `code`, `name`, `number`, `party_id`, `created_time`, `batch_no`) values ";
        	$sql_v = "";
        	$TC_size = count($TC_list) - 1;
        	foreach ($TC_list as $key => $item) {
        		$TC_party[$item['party_id']] = (isset($TC_party[$item['party_id']]) ? $TC_party[$item['party_id']] : 0) + 1;
        		if ($key == $TC_size) {
        			$sql_v .= " (null, null, '{$item['code']}', '{$item['name']}', '{$item['number']}', '{$item['party_id']}', '". date("Y-m-d H:i:s", time())."' , {$batch_no});" ;
        		} else {
        			$sql_v .= " (null, null, '{$item['code']}', '{$item['name']}', '{$item['number']}', '{$item['party_id']}', '". date("Y-m-d H:i:s", time())."', {$batch_no})," ;        		
        		}
        	}
        	$sql = $sql.$sql_v;
        	$result = $db->createCommand($sql)->execute();
        	echo "[". date('c'). "] 批次号：".$batch_no." 套餐数据插入记录：".$result."条\n";
        	$TC_insert_time = microtime(true);
        	echo "[". date('c'). "] 批次号：".$batch_no." 套餐数据插入耗时：" .($TC_insert_time- $TC_end_time)."\n";
        }
		foreach ($TC_party as $key => $item) {
			echo "[". date('c'). "] 批次号：" . $batch_no. " 套餐  party_id: ". $key ." total: ".$item."\n";
        }
        $goods_party = array();
        if ($goods_style_list) {
        	$goods_size = count($goods_style_list) - 1;
        	$sql = " insert into romeo.shipment_picklist (`goods_id`, `style_id`, `code`, `name`, `number`, `party_id`, `created_time`, `batch_no`) values ";
        	$sql_c = "";
        	foreach ($goods_style_list as $key=>$item) {
        		$goods_party[$item['party_id']] = (isset($goods_party[$item['party_id']]) ? $goods_party[$item['party_id']] : 0) + 1;
        		if ($key == $goods_size) {
        			$sql_c .= " ({$item['goods_id']}, {$item['style_id']}, null, '{$item['name']}', '{$item['number']}', '{$item['party_id']}', '". date("Y-m-d H:i:s", time())."', {$batch_no});" ;
        		} else {
        			$sql_c .= " ({$item['goods_id']}, {$item['style_id']}, null, '{$item['name']}', '{$item['number']}', '{$item['party_id']}', '". date("Y-m-d H:i:s", time())."', {$batch_no})," ;
        		}
        	}
        	$sql = $sql.$sql_c;
        	$result = $db->createCommand($sql)->execute();
        	echo "[". date('c'). "] 批次号：".$batch_no." 商品数据插入记录：".$result."条\n";
        	echo "[". date('c'). "] 批次号：".$batch_no." 商品数据插入耗时：" .(microtime(true) - (isset($TC_insert_time)?$TC_insert_time:$TC_end_time)) ."\n";
        }
        foreach ($goods_party as $key => $item) {
        	echo "[". date('c'). "] 批次号：" . $batch_no. " 商品  party_id: ". $key ." total: ".$item . "\n";
        }
	}
}
