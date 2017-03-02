<?php
/*
*
*	新增活动
*
*/

require_once(__DIR__.'/../includes/init.php');

/*
*
*/


class GiftNewAdd
{
	public static function CreateGiftNewAdd_facility(){
		$party = $_SESSION['party_id'];
		global $db;
		$sql = "SELECT 
		fa.FACILITY_ID,fa.FACILITY_NAME
		FROM
		romeo.facility fa
		WHERE
		1 AND fa.IS_CLOSED = 'N'
		";
		$facility_gift_list = $db->getAll($sql);
		foreach ($facility_gift_list as $key => $value) {
			$facility_gift[$value['FACILITY_ID']] = $value['FACILITY_NAME'];
		}

		return empty($facility_gift) ? array():$facility_gift;
	}
	public static function CreateGiftNewAdd_distributor(){
		$party = $_SESSION['party_id'];
		global $db;
		$sql = "SELECT 
		di.distributor_id,di.name
		FROM
		ecshop.distributor di
		WHERE
		1 AND di.party_id = '{$party}'
		";
		$distributor_gift_list = $db->getAll($sql);
		foreach ($distributor_gift_list as $key => $value) {
			$distributor_gift[$value['distributor_id']] = $value['name'];
		}

		return empty($distributor_gift) ? array():$distributor_gift;
	}
	public static function CreateGiftNewAdd_region(){
		global $db;
		$sql = "SELECT 
		re.region_id,re.region_name
		FROM
		ecshop.ecs_region re
		WHERE
		1 AND re.region_type = 1 AND re.parent_id = 1
		";
		$region_gift_list = $db->getAll($sql);
		foreach ($region_gift_list as $key => $value) {
			$region_gift[$value['region_id']] = $value['region_name'];
		}

		return empty($region_gift) ? array():$region_gift;
	}
	public static function CreateGiftNewAdd_Goods_Included_Excluded(){
		$party = $_SESSION['party_id'];
		global $db;
		$sql = "(
		SELECT 
		concat(go.goods_id,'_',IFNULL(sty.style_id,'0')) as goods_id,
		go.goods_name as goods_name
		FROM
		ecshop.ecs_goods go
		LEFT JOIN
		ecshop.ecs_goods_style sty ON go.goods_id = sty.goods_id and sty.is_delete=0
		WHERE
		go.is_delete = 0 
		AND go.goods_party_id = '{$party}'
		)
		";
		// $sql = "SELECT 
		// go.goods_id,go.goods_name
		// FROM
		// ecshop.ecs_goods go 
		// WHERE
		// 1 AND go.goods_party_id = '{$party}'
		// ";
		$goods_gift_list = $db->getAll($sql);
		foreach ($goods_gift_list as $key => $value) {
			$goods_gift[$value['goods_id']] = $value['goods_name'];
		}

		return empty($goods_gift) ? array():$goods_gift;
	}
	public static function CreateGiftNewAdd_Goods_Group(){
		$party = $_SESSION['party_id'];
		global $db;
		$sql = "(
		SELECT 
		concat(go.goods_id,'_',IFNULL(sty.style_id,'0')) as goods_id,
		go.goods_name as goods_name
		FROM
		ecshop.ecs_goods go
		LEFT JOIN
		ecshop.ecs_goods_style sty ON go.goods_id = sty.goods_id and sty.is_delete=0
		WHERE
		go.is_delete = 0 
		AND IFNULL(sty.is_delete,0)=0   
		AND go.goods_party_id = '{$party}'
		)
		UNION 
		(
		SELECT 
		dis.code as goods_id,
		dis.name as goods_name
		FROM
		ecshop.distribution_group_goods dis
		WHERE
		1 AND dis.party_id = '{$party}' AND dis.status = 'OK'
		)
		";
		$goods_gift_list = $db->getAll($sql);
		foreach ($goods_gift_list as $key => $value) {
			if($value['goods_name'] != ""){
				$goods_gift[$value['goods_id']] = "[".$value['goods_id']."]".$value['goods_name'];
			}
		}

		return empty($goods_gift) ? array():$goods_gift;
	}
	public static function CreateGiftNewAdd_Goods_Group_No_Style(){
		$party = $_SESSION['party_id'];
		global $db;
		$sql = "(
		SELECT 
		go.goods_id as goods_id,
		go.goods_name as goods_name
		FROM
		ecshop.ecs_goods go
		WHERE
		go.is_delete = 0 
		AND go.goods_party_id = '{$party}'
		)
		UNION 
		(
		SELECT 
		dis.code as goods_id,
		dis.name as goods_name
		FROM
		ecshop.distribution_group_goods dis
		WHERE
		1 AND dis.party_id = '{$party}' AND dis.status = 'OK'
		)
		";
		$goods_gift_list = $db->getAll($sql);
		foreach ($goods_gift_list as $key => $value) {
			if($value['goods_name'] != ""){
				$goods_gift[$value['goods_id']] = "[".$value['goods_id']."]".$value['goods_name'];
			}
		}

		return empty($goods_gift) ? array():$goods_gift;
	}
	public static function CreateGiftNewAdd_GoodsCat_Included_Excluded(){
		global $db;
		$exclude = array(1 ,119 ,166 ,179 ,260, 336, 341, 613, 616, 615, 414, 597, 1071, 825, 837, 979, 1073, 1158, 1159, 1498, 1515, 1516, 2329);
		$sql = "SELECT c.cat_id, if(pa.cat_name != p.name,c.cat_name,c.cat_name) as name, c.parent_id,  COUNT(s.cat_id) AS has_children " .
		'FROM ' . $GLOBALS ['ecs']->table ( 'category' ) . " AS c " . 
		"LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS s ON s.parent_id=c.cat_id " . 
		"LEFT JOIN " . $GLOBALS ['ecs']->table ( 'category' ) . " AS pa ON c.parent_id=pa.cat_id " . 
		"LEFT JOIN romeo.party p on p.party_id = c.party_id " .
		"where c.is_delete = 0 " . 
		"and c.party_id = ".$_SESSION['party_id']." and c.parent_id not in ('2245','0') " . 
		" GROUP BY c.cat_id HAVING has_children =0  " . 'ORDER BY c.parent_id,c.sort_order ASC ';	
		$cat_list = $db->getAll($sql);
		foreach ($cat_list as $key=>$value) {
			if(in_array($value['cat_id'],$exclude)){
				unset($cat_list[$key]);
			}else{
				if($value['name'] != ""){
					$cat[$value['cat_id']] = "[".$value['cat_id']."]".$value['name'];
				}
			}
		}

		return empty($cat) ? array():$cat;
	}
}


?>