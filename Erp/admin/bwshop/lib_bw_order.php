<?php
/**
天国的保税仓。
据说叫宁波保达仓。
乐其跨境帝国驻ERP土著居民保留地订单事务镇压派遣军军火管理局。
**/

require_once(__DIR__.'/../includes/init.php');
require_once __DIR__.'/lib_bw_party.php';

/**
* 
*/
class BWOrderAgent
{

// For Single Order

	public static function getBWOrderInfoByErpOrderId($erp_order_id){
		global $db;
		$bw_parties_sql=implode(',', BWPartyAgent::getAllBWParties());
		$bw_facility_id=BWPartyAgent::getBWFacilityId();
		$sql="SELECT boi.*,
				eoi.order_id erp_order_id,
				eoi.order_sn erp_order_sn,
				d.distributor_id,
				d.`name` distributor_name,
				eoi.order_status erp_order_status,
				eoi.pay_status erp_pay_status,
				eoi.shipping_status erp_shipping_status
			FROM ecshop.ecs_order_info eoi
			INNER JOIN ecshop.bw_order_info boi ON eoi.taobao_order_sn=boi.outer_order_sn AND eoi.party_id in ({$bw_parties_sql}) AND eoi.order_type_id='SALE' AND eoi.facility_id='{$bw_facility_id}'
			LEFT JOIN ecshop.bw_shop bs ON boi.shop_id = bs.shop_id
			LEFT JOIN ecshop.distributor d ON bs.ecs_distributor_id = d.distributor_id
			WHERE eoi.order_id='{$erp_order_id}'";
		$line=$db->getRow($sql);
		return $line;
	}

	public static function getBWOrderInfoByErpOrderSn($erp_order_sn){
		global $db;
		$bw_parties_sql=implode(',', BWPartyAgent::getAllBWParties());
		$bw_facility_id=BWPartyAgent::getBWFacilityId();
		$sql="SELECT boi.*,
				eoi.order_id erp_order_id,
				eoi.order_sn erp_order_sn,
				d.distributor_id,
				d.`name` distributor_name,
				eoi.order_status erp_order_status,
				eoi.pay_status erp_pay_status,
				eoi.shipping_status erp_shipping_status
			FROM ecshop.ecs_order_info eoi
			INNER JOIN ecshop.bw_order_info boi ON eoi.taobao_order_sn=boi.outer_order_sn AND eoi.party_id in ({$bw_parties_sql}) AND eoi.order_type_id='SALE' AND eoi.facility_id='{$bw_facility_id}'
			LEFT JOIN ecshop.bw_shop bs ON boi.shop_id = bs.shop_id
			LEFT JOIN ecshop.distributor d ON bs.ecs_distributor_id = d.distributor_id
			WHERE eoi.order_sn='{$erp_order_sn}'";
		$line=$db->getRow($sql);
		return $line;
	}

	public static function getBWOrderInfoByBwOrderId($bw_order_id){
		global $db;
		$bw_parties_sql=implode(',', BWPartyAgent::getAllBWParties());
		$bw_facility_id=BWPartyAgent::getBWFacilityId();
		$sql="SELECT boi.*,
				eoi.order_id erp_order_id,
				eoi.order_sn erp_order_sn,
				d.distributor_id,
				d.`name` distributor_name,
				eoi.order_status erp_order_status,
				eoi.pay_status erp_pay_status,
				eoi.shipping_status erp_shipping_status
			FROM ecshop.bw_order_info boi
			LEFT JOIN ecshop.ecs_order_info eoi ON eoi.taobao_order_sn=boi.outer_order_sn AND eoi.party_id in ({$bw_parties_sql}) AND eoi.order_type_id='SALE' AND eoi.facility_id='{$bw_facility_id}'
			LEFT JOIN ecshop.bw_shop bs ON boi.shop_id = bs.shop_id
			LEFT JOIN ecshop.distributor d ON bs.ecs_distributor_id = d.distributor_id
			WHERE boi.order_id='{$bw_order_id}'";
		$line=$db->getRow($sql);
		return $line;
	}

	public static function getBWOrderInfoListByBwOrderSnAndShopId($bw_order_sn,$shop_id=null){
		global $db;
		$bw_parties_sql=implode(',', BWPartyAgent::getAllBWParties());
		$bw_facility_id=BWPartyAgent::getBWFacilityId();
		$sql="SELECT boi.*,
				eoi.order_id erp_order_id,
				eoi.order_sn erp_order_sn,
				d.distributor_id,
				d.`name` distributor_name,
				eoi.order_status erp_order_status,
				eoi.pay_status erp_pay_status,
				eoi.shipping_status erp_shipping_status
			FROM ecshop.bw_order_info boi
			LEFT JOIN ecshop.ecs_order_info eoi ON eoi.taobao_order_sn=boi.outer_order_sn AND eoi.party_id in ({$bw_parties_sql}) AND eoi.order_type_id='SALE' AND eoi.facility_id='{$bw_facility_id}'
			LEFT JOIN ecshop.bw_shop bs ON boi.shop_id = bs.shop_id
			LEFT JOIN ecshop.distributor d ON bs.ecs_distributor_id = d.distributor_id
			WHERE boi.outer_order_sn='{$bw_order_sn}'
		";
		if(!empty($shop_id)){
			$sql.=" AND boi.shop_id='{$shop_id}' ";
		}
		$lines=$db->getAll($sql);
		return $lines;
	}

// For Listing Orders

	public static function getBWOrderList($limit=20,$offset=0,&$total,$params=array()){
		global $db;
		// print_r($params);
		$cond="";
		if(!empty($params)){
			if(isset($params['apply_status'])){
				$cond.=" AND boi.apply_status='{$params['apply_status']}' ";
			}
			if(isset($params['shipping_status'])){
				$cond.=" AND boi.shipping_status='{$params['shipping_status']}' ";
			}
			if(isset($params['shop_id'])){
				$cond.=" AND boi.shop_id='{$params['shop_id']}' ";
			}
		}

		$sql="SELECT count(1) FROM ecshop.bw_order_info boi WHERE 1 ".$cond;
		$total=$db->getOne($sql);

		$sql="SELECT
				boi.*, 
				-- eoi.order_id erp_order_id,
				-- eoi.order_sn erp_order_sn,
				-- eoi.order_status erp_order_status,
				-- eoi.pay_status erp_pay_status,
				-- eoi.shipping_status erp_shipping_status,
				d.distributor_id,
				d.`name` distributor_name
			FROM
				ecshop.bw_order_info boi
			-- LEFT JOIN ecshop.ecs_order_info eoi ON eoi.taobao_order_sn=boi.outer_order_sn AND eoi.party_id=65638 AND eoi.order_type_id='SALE'
			LEFT JOIN ecshop.bw_shop bs ON boi.shop_id = bs.shop_id
			LEFT JOIN ecshop.distributor d ON bs.ecs_distributor_id = d.distributor_id
			WHERE 1 {$cond}
			ORDER BY
				boi.order_id DESC
			LIMIT $limit OFFSET $offset
		";
		// echo "<pre>".$sql."</pre>".PHP_EOL;
		$list=$db->getAll($sql);
		// print_r($list);

		foreach ($list as $key => $value) {
			$sql="SELECT 
					eoi.order_id erp_order_id,
					eoi.order_sn erp_order_sn,
					eoi.order_status erp_order_status,
					eoi.pay_status erp_pay_status,
					eoi.shipping_status erp_shipping_status
				FROM ecshop.ecs_order_info eoi
				WHERE eoi.taobao_order_sn='{$value['outer_order_sn']}'
				LIMIT 1
			";
			$row=$db->getRow($sql);
			$list[$key]['erp_order_id']=$row['erp_order_id'];
			$list[$key]['erp_order_sn']=$row['erp_order_sn'];
			$list[$key]['erp_order_status']=$row['erp_order_status'];
			$list[$key]['erp_pay_status']=$row['erp_pay_status'];
			$list[$key]['erp_shipping_status']=$row['erp_shipping_status'];
		}

		return $list;
	}

// For kill order(s)

	public static function killOrders($orders){
		global $db;
		$afx=false;
		$db->start_transaction();
		try {
			$sql="DELETE FROM ecshop.bw_order_info WHERE order_id in (".implode(',', $orders).")";
			$afx=$db->exec($sql);
			$db->commit();
		} catch (Exception $e) {
			$db->rollback();
		}
		return $afx;
	}

// For status of order

	public static function explainApplyStatus($apply_status){
		static $mapping=array(
			'INIT'=>'未推送',
			'READY'=>'进入推送队列',
			'ACCEPT'=>'已推送',
			'REFUSED'=>'保税仓拒收',
			'CANCEL'=>'撤单拦截',
		);
		return $mapping[$apply_status];
	}

	public static function explainShippingStatus($shipping_status){
		static $mapping=array(
			'00'=>'未申报',
			'01'=>'库存不足',
			'11'=>'已报国检',
			'12'=>'国检放行',
			'13'=>'国检审核未过',
			'14'=>'国检抽检',
			'21'=>'已报海关',
			'22'=>'海关单证放行',
			'23'=>'海关单证审核未过',
			'24'=>'海关货物放行',
			'25'=>'海关查验未过',
			'99'=>'已关闭',
		);
		return $mapping[$shipping_status];
	}

	public static function explainPaymentCode($paymentCode){
		global $db;
		$sql="SELECT payment_name FROM ecshop.bw_payment WHERE payment_code='{$paymentCode}'";
		$payment_name=$db->getOne($sql);
		return $payment_name;
	}

// Order Goods 

	public static function getBWOrderGoods($bw_order_id){
		global $db;
		$sql="SELECT * FROM ecshop.bw_order_goods WHERE order_id='{$bw_order_id}'";
		$list=$db->getAll($sql);
		return $list;
	}

// Order RMA

	public static function getBWOrderCancelStatus($bw_order_id){
		global $db;
		$sql="SELECT refund_status FROM ecshop.bw_order_refund WHERE order_id='{$bw_order_id}'";
		$status=$db->getOne($sql);
		return (empty($status)?'N':$status);
	}

	public static function getBWOrderCancelLine($bw_order_id){
		global $db;
		$sql="SELECT * FROM ecshop.bw_order_refund WHERE order_id='{$bw_order_id}'";
		$line=$db->getRow($sql);
		return $line;
	}

	public static function getBWOrderReturnStatus($bw_order_id){
		global $db;
		$sql="SELECT return_status FROM ecshop.bw_order_return WHERE order_id='{$bw_order_id}'";
		$status=$db->getOne($sql);
		return (empty($status)?'N':$status);
	}

	public static function getBWOrderReturnLine($bw_order_id){
		global $db;
		$sql="SELECT * FROM ecshop.bw_order_return WHERE order_id='{$bw_order_id}'";
		$line=$db->getRow($sql);
		return $line;
	}

// for order edit page confirm warning
	public static function warningForConfirmErpOrder($erp_order_id){
		global $db;
		$bw_parties_sql=implode(',', BWPartyAgent::getAllBWParties());
		$bw_facility_id=BWPartyAgent::getBWFacilityId();
		$sql="SELECT 
			IFNULL(eoi.order_amount,0) as order_amount, 
			IFNULL(eoi.shipping_fee,0) as shipping_fee
		FROM 
			ecshop.ecs_order_info eoi
		WHERE 
			eoi.order_id='{$erp_order_id}'
		AND eoi.party_id in ({$bw_parties_sql})
		AND eoi.facility_id='{$bw_facility_id}'
		LIMIT 1
		";
		$price=$db->getRow($sql);
		$price=$price['order_amount']-$price['shipping_fee'];
		if($price>=500.0){
			return "正正发货订单超了500元要交行邮税。如果未申报，请考虑拆分订单以避税。如果已经申报请忽略。";
		}else{
			return "";
		}
	}
}

?>