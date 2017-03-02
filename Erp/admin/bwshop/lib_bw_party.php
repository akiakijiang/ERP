<?php
/**
天国的保税仓。
据说叫宁波保达仓。
乐其跨境帝国驻ERP土著居民保留地订单事务镇压派遣军军火管理局。
**/

require_once(__DIR__.'/../includes/init.php');

/**
* 
*/
class BWPartyAgent
{
	public static function getBWRootPartyId(){
		// return 65651;//For 2.11
		return 65658;//For 0.22
	}

	public static function getAllBWParties(){
		global $db;
		$sql="SELECT
			PARTY_ID
		FROM
			romeo.party
		WHERE
			PARENT_PARTY_ID = ".BWPartyAgent::getBWRootPartyId();
		$party_id_list=$db->getCol($sql);
		return $party_id_list;
	}

	public static function getAllBWPartiesForSelect(){
		global $db;
		$sql="SELECT
			PARTY_ID as party_id,
			`NAME` as party_name
		FROM
			romeo.party
		WHERE
			PARENT_PARTY_ID = ".BWPartyAgent::getBWRootPartyId();
		$party_list=$db->getAll($sql);
		return $party_list;
	}

	public static function getBWFacilityId(){
		return '149849262';//宁波保达仓
	}


}