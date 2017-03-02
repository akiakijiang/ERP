<?php
/**
天国的保税仓。
据说叫宁波保达仓。
乐其跨境帝国驻ERP土著居民保留地订单事务镇压派遣军傀儡政府管理局。
**/

require_once(__DIR__.'/../includes/init.php');

/**
* 
*/
class BWShopAgent
{
	
	public static function createShop($params){
		global $db;
		if(!isset($params['free_shipping'])){
			$params['free_shipping']='N';
		}
		if(!isset($params['credit_shipping'])){
			$params['credit_shipping']='N';
		}

		$mdid_sql="SELECT `name`, main_distributor_id,party_id from ecshop.distributor where distributor_id='{$params['distributor_id']}'";
		$row=$db->getRow($mdid_sql);
		$params['main_distributor_id']=$row['main_distributor_id'];
		$params['shop_name']=$row['name'];
		$params['party_id']=$row['party_id'];

		if(empty($params['distributor_id'])){
			return false;
		}
		if(empty($params['main_distributor_id'])){
			return false;
		}
		if(empty($params['shop_code'])){
			return false;
		}
		if(empty($params['shop_name'])){
			return false;
		}
		if(empty($params['shop_key'])){
			return false;
		}
		if(empty($params['zz_code'])){
			return false;
		}
		if(empty($params['party_id'])){
			return false;
		}
		if(empty($params['free_shipping'])){
			return false;
		}
		if(empty($params['credit_shipping'])){
			return false;
		}

		foreach ($params as $key => $value) {
			$params[$key]=mysql_escape_string($value);
		}	
		
		$sql="INSERT INTO ecshop.bw_shop (
				`shop_id`,
				`ecs_distributor_id`,
				`ecs_main_distributor_id`,
				`shop_code`,
				`shop_name`,
				`shop_key`,
				`zz_code`,
				`create_time`,
				`update_time`,
				`party_id`,
				`free_shipping`,
				`credit_shipping`
			) VALUES (
				NULL,
				'{$params['distributor_id']}',
				'{$params['main_distributor_id']}',
				'{$params['shop_code']}',
				'{$params['shop_name']}',
				'{$params['shop_key']}',
				'{$params['zz_code']}',
				NOW(),
				NOW(),
				'{$params['party_id']}',
				'{$params['free_shipping']}',
				'{$params['credit_shipping']}'
			)
		";

		try {
			$db->start_transaction();

			$shop_id=$db->exec($sql);


			$db->commit();
		} catch (Exception $e) {
			$db->rollback();
			return false;
		}

		return $shop_id;
	}

	public static function distributorList(){
		global $db;

		require_once __DIR__.'/lib_bw_party.php';
		$bw_parties_sql=implode(',', BWPartyAgent::getAllBWParties());
		$bw_facility_id=BWPartyAgent::getBWFacilityId();

		$sql="SELECT
				d.distributor_id,
				d.`name`
			FROM
				ecshop.distributor d
			LEFT JOIN ecshop.bw_shop bs ON d.distributor_id = bs.ecs_distributor_id
			WHERE
				d.party_id in ({$bw_parties_sql})
			AND bs.ecs_distributor_id IS NULL
		";
		$list=$db->getAll($sql);
		return $list;
	}

	public static function shopList($params=array()){
		global $db;
		$sql = "SELECT
				bs.*, d.`name` distributor_name,
				md.`name` main_distributor_name,
				p.`NAME` party_name
			FROM
				ecshop.bw_shop bs
			LEFT JOIN ecshop.distributor d ON bs.ecs_distributor_id = d.distributor_id
			LEFT JOIN ecshop.main_distributor md ON bs.ecs_main_distributor_id = md.main_distributor_id
			LEFT JOIN romeo.party p ON bs.party_id = p.PARTY_ID
			WHERE
				1
		";
		foreach ($params as $key => $value) {
			$sql .= " AND $key='".mysql_escape_string($value)."' ";
		}
		$list=$db->getAll($sql);
		return $list;
	}

	public static function updateShop($shop_id,$params){
		global $db;
		
		$sets=array();
		foreach ($params as $key => $value) {
			$sets[]=$key."='".mysql_escape_string($value)."'";
		}
		$sql="UPDATE ecshop.bw_shop SET ".implode(',', $sets)." WHERE shop_id='{$shop_id}'";

		try {
			$db->start_transaction();

			$afx=$db->exec($sql);

			$db->commit();
		} catch (Exception $e) {
			$db->rollback();
			return false;
		}

		return $afx;
	}
}

?>