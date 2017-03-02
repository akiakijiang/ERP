<?php
/**
Deal with the orders existed in ERP and need transfering into the BWSHOP
**/
require_once(__DIR__.'/../includes/init.php');

/**
* 
*/
class BWIssueOrderAgent
{
	/*
	2549,2550,2552,2555,2570,2571,2573,2575,2574
	*/
	public static function getE2BSyncDistributorList(){
		global $db;
		$sql="SELECT
				ecs_distributor_id
			FROM
				ecshop.bw_shop bs
			WHERE
				bs.is_sync = 1
		";
		$list=$db->getCol($sql);
		return $list;
	}

	public static function getE2BSyncDistributorListNames(){
		global $db;
		$sql="SELECT
				shop_name
			FROM
				ecshop.bw_shop bs
			WHERE
				bs.is_sync = 1
		";
		$list=$db->getCol($sql);
		return $list;
	}

	public static function getE2BIssueOrders($start_days_ago=7,$end_days_ago=0,$limit=10,$offset=0,&$count){
		global $db;
		$distributor_list=BWIssueOrderAgent::getE2BSyncDistributorList();
		$distributor_list_sql=implode(',', $distributor_list);
		$sql_count="SELECT count(1)
			FROM
				ecshop.ecs_order_info eoi
			LEFT JOIN ecshop.bw_order_info boi ON eoi.taobao_order_sn = boi.outer_order_sn
			WHERE
				eoi.distributor_id IN ({$distributor_list_sql})
			AND eoi.order_status = 1
			AND eoi.pay_status = 2
			AND eoi.order_time >= date_sub(NOW(),interval {$start_days_ago} day)
			AND eoi.order_time <= date_sub(NOW(),interval {$end_days_ago} day)
			AND eoi.order_type_id = 'SALE'
			AND boi.order_sn IS NULL
			AND eoi.facility_id='149849262'
		";
		$sql="SELECT
				eoi.order_id,
				eoi.order_sn,
				eoi.taobao_order_sn,
				eoi.order_status,
				eoi.pay_status,
				eoi.shipping_status,
				eoi.order_time,
				FROM_UNIXTIME(eoi.confirm_time) confirm_time,
				FROM_UNIXTIME(eoi.pay_time) pay_time,
				boi.order_id bw_order_id,
				d.name distributor_name
			FROM
				ecshop.ecs_order_info eoi
			LEFT JOIN ecshop.bw_order_info boi ON eoi.taobao_order_sn = boi.outer_order_sn
			LEFT JOIN ecshop.distributor d ON eoi.distributor_id = d.distributor_id
			WHERE
				eoi.distributor_id IN ({$distributor_list_sql})
			AND eoi.order_status = 1
			AND eoi.pay_status = 2
			AND eoi.order_time >= date_sub(NOW(),interval {$start_days_ago} day)
			AND eoi.order_time <= date_sub(NOW(),interval {$end_days_ago} day)
			AND eoi.order_type_id = 'SALE'
			AND boi.order_sn IS NULL
			AND eoi.facility_id='149849262'
			LIMIT {$limit} OFFSET {$offset}
		";
		$count=$db->getOne($sql_count);
		$list=$db->getAll($sql);
		return $list;
	}
}

?>