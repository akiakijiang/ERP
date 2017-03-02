<?php
//顺丰热敏保价
//海蓝之谜的需求是在热敏面单上按照订单的价格显示在面单的声明价格处，作为保价。
//ECCO的需求为一双[鞋子]1000元的保价在热敏面单的声明价格处，作为保价。
//芭比波朗不保价 屏蔽顺丰热敏单上声明价格处价格。

/**
* 
*/
class SFArataInsure
{
	
	function __construct()
	{
		# code...
	}

	public static function getInsuranceForOrder($order_id){
		global $db;
		$sql="SELECT shipment_id FROM romeo.order_shipment WHERE order_id='{$order_id}'";
		$shipment_id=$db->getOne($sql);
		return SFArataInsure::getInsuranceForShipment($shipment_id);
	}

	/**
	 * @param shipment_id
	 */
	public static function getInsuranceForShipment($shipment_id){
		global $db;


		$sql="SELECT 
		    o.party_id
		FROM
		    romeo.shipment s
		        INNER JOIN
		    ecshop.ecs_order_info o ON s.primary_order_id = o.order_id
		WHERE
		    s.shipment_id = '{$shipment_id}'
		LIMIT 1";
		$party_id=$db->getOne($sql);

		

		if($party_id=='65628'){
			//LA MER海蓝之谜 65628
			return SFArataInsure::getShipmentOrderAmount($shipment_id);
		}
		if($party_id=='65562'){
			//ecco 65562
			return SFArataInsure::getEccoShoesInsurance($shipment_id);
		}
		if($party_id=='65639'){
			//Bobbi Brown 65639
			return 0;
		}
		if($party_id=='16'){
			return SFArataInsure::getLeqeeElecEduInsurance($shipment_id);
		}
		if($party_id=='65653'){
			//资生堂/SHISEIDO 65653
			return SFArataInsure::getShipmentOrderAmount($shipment_id);
		}
		if($party_id=='65670'){//65670
			return SFArataInsure::getShipmentOrderAmount($shipment_id);
		}

		return 0;
	}

	public static function getShipmentOrderAmount($shipment_id){
		global $db;

		$sql="SELECT sum(oi.order_amount) FROM romeo.order_shipment os 
		INNER JOIN ecshop.ecs_order_info oi ON cast(os.order_id as unsigned)=oi.order_id
		WHERE os.shipment_id='{$shipment_id}'";
		$insure=$db->getOne($sql);

		return $insure;
	}

	public static function getEccoShoesInsurance($shipment_id){
		global $db;
		$sql="SELECT 
		    SUM(og.goods_number) * 1000 insurance
		FROM
		    romeo.shipment s
		        INNER JOIN
		    romeo.order_shipment os ON os.shipment_id = s.shipment_id
		        INNER JOIN
		    ecshop.ecs_order_goods og ON og.order_id = CAST(os.order_id AS UNSIGNED)
		        INNER JOIN
		    ecshop.ecs_goods g ON og.goods_id = g.goods_id
		WHERE
		    s.shipment_id = '{$shipment_id}'
		        AND g.cat_id = 2404
		";
		$insure=$db->getOne($sql);

		return $insure;

	}

	public static function getLeqeeElecEduInsurance($shipment_id){
		global $db;
		static $price_mapping=array(
			'170147'=>	1800,//	lq0543153	平板电脑H7[纯色]
			'185306'=>	1500,//	lq2497708	点读机T2[浅绿色]
			'185307'=>	1200,//	lq4155985	平板电脑H8[白色]
			'188618'=>	2700,//	444525755	平板电脑H9[绿色皮套]
			'190345'=>	1500,//	444312842	点读机T2[粉色]
			'190820'=>	600,//	6935632820167 	点读机T500S[果绿]
			'191249'=>	2100,//	444384561 	家教机H8S
			'193547'=>	3100,//	6935632850201 	家教机S1[香槟金]
			'196943'=>	3600,//	6935632850249 	家教机S2
			'32196'=>	600,//	6935632840127 	学习机H2
			'32197'=>	400,//	lq5855239	学习机S1
			'32200'=>	800,//	lq6441893	点读机T800[蓝色]
			'44623'=>	1000,//	lq2183098	外语通E50[绿色]
			// '42632'=> 1,//test//金士顿 16G TF卡（不带卡套）
		);
		$sql="SELECT
			eog.goods_id
		FROM
			romeo.order_shipment os
		INNER JOIN ecshop.ecs_order_goods eog ON CAST(os.ORDER_ID AS UNSIGNED) = eog.order_id
		WHERE
			os.SHIPMENT_ID = '$shipment_id'
		";
		$list=$db->getCol($sql);
		$sum=0;
		foreach ($list as $goods_id) {
			if(isset($price_mapping[$goods_id])){
				$sum+=$price_mapping[$goods_id];
			}
		}
		return $sum;
	}
}

?>