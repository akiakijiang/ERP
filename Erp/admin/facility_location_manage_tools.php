<?php
/**
 * 仓库库位管理相关类
 */
define ( 'IN_ECS', true );

require_once ('includes/init.php');
admin_priv ( 'facility_location_manage' );
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once (ROOT_PATH . 'RomeoApi/lib_facility.php');
require_once ('function.php');

class MarkedFacility {
	public $id_arr_;
	public $val_;
	static public function CompareFunc($a, $b)
	{
		$id_arr_count = min(count($a->id_arr_), count($b->id_arr_));
		
		for ($i = 0; $i < $id_arr_count; $i++)
		{
			if(is_numeric($a->id_arr_[$i]) & is_numeric($b->id_arr_[$i]))
			{
				$aa = intval($a->id_arr_[$i], 10);
				$bb = intval($b->id_arr_[$i], 10);
			}
			else 
			{
				$aa = $a->id_arr_[$i];
				$bb = $b->id_arr_[$i];		
			}

			if ($aa == $bb)
			{
				continue;
			}
			else if ($aa > $bb)
			{
				return 1;
			}
			else 
			{
				return -1;
			}
		}
		return 0;
	}
}

class FacilityOperation {

	static public function SortFacilitiesById($facility_location_list) {
		// 根据库位id排序
		$facility_id_vector = array ();
		$facility_location_list_temp = $facility_location_list;
		
		foreach ( $facility_location_list_temp as $facility_location_list_id ) {
			$location_seq_id = $facility_location_list_id->locationSeqId;
			$id_real_arr = explode('-', $location_seq_id);
			
			$curr_instant = new MarkedFacility();
			$curr_instant->id_arr_ = $id_real_arr;
			$curr_instant->val_ = $facility_location_list_id;
			array_push($facility_id_vector, $curr_instant);				
		}
		
		uasort($facility_id_vector, array('MarkedFacility', 'CompareFunc'));
		
		$facility_location_list = array ();
		foreach ($facility_id_vector as $vector_value) {
			array_push($facility_location_list, $vector_value->val_);
		}
		return $facility_location_list;
	}
	
	static public function LoadFacilityInfoToCSV(
		$smarty, $slave_db, $facility_location_list, $facility_id, $facility_list, $ref_fields, $ref_rowset) {
		
		// 按id对库位进行排序
		//显示时已排序，无需再次排序
		//$facility_location_list = FacilityOperation::SortFacilitiesById($facility_location_list);
		
		// 取得库位信息
		$product_faclity_location_lists = FacilityOperation::GetFacilityInfo($slave_db, $facility_id, $facility_location_list, $ref_fields, $ref_rowset);

		//获取组织的名字(
		$party_name = FacilityOperation::GetPartyNameByID($facility_id);
		
		$smarty->assign ( 'party_name', $party_name );
		$smarty->assign ( 'facility_name', $facility_list [$facility_id] );
		$smarty->assign ( 'product_faclity_location_lists', $product_faclity_location_lists [$facility_id] );
		
		header ( "Content-type:application/vnd.ms-excel" );
		header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "仓库库位" ) . ".csv" );
		$out = $smarty->fetch ( 'oukooext/facility_location_manage_csv.htm' );
		echo iconv ( "UTF-8", "GB18030", $out );
		exit ();
	}
	
	static private function GetFacilityInfo($slave_db, $facility_id, $facility_location_list, $ref_fields, $ref_rowset){
		$product_faclity_location_lists = array ();
		foreach ( $facility_location_list as $facility_location_list_id ) {
			$location_seq_id = $facility_location_list_id->locationSeqId;
			$product_faclity_location_list = null;
			
			if ((isset ( $facility_id ) && isset ( $location_seq_id )) && $facility_location = facility_location_get_by_pk ( $facility_id, $location_seq_id )) {
				$handle = soap_get_client ( 'FacilityService', 'ROMEO' );
				$result = $handle->findAllProductFacilityLocation ( array ("facilityId" => $facility_id, "locationSeqId" => $location_seq_id ) );
				if (isset ( $result ) && isset ( $result->return ) && isset ( $result->return->ProductFacilityLocation )) {
					$product_faclity_location_list = is_array ( $result->return->ProductFacilityLocation ) ? $result->return->ProductFacilityLocation : array ($result->return->ProductFacilityLocation );
				}
				
				// 取得产品名
				if ($product_faclity_location_list) {
					$productIds = array ();
					foreach ( $product_faclity_location_list as $product ) {
						array_push ( $productIds, $product->productId );
					}
					
					$sql = "SELECT 
                          pm.PRODUCT_ID,p.PRODUCT_NAME,ifnull(gs.barcode,g.barcode) as barcode
                      FROM         romeo.product p
                      LEFT JOIN    romeo.product_mapping pm ON p.product_id = pm.product_id
                      LEFT JOIN    ecshop.ecs_goods g ON pm.ecs_goods_id = g.goods_id
                      LEFT JOIN    ecshop.ecs_goods_style gs ON pm.ecs_goods_id = gs.goods_id and pm.ecs_style_id = gs.style_id and gs.is_delete=0
                      WHERE 
                      pm.product_id " . db_create_in ( $productIds );
					
					$result = $slave_db->getAllRefby ( $sql, array ('PRODUCT_ID' ), $ref_fields, $ref_rowset );
					foreach ( $product_faclity_location_list as $key => $product ) {
						$product_faclity_location_list [$key]->productName = $ref_rowset ['PRODUCT_ID'] [$product->productId] [0] ['PRODUCT_NAME'];
						$product_faclity_location_list [$key]->barcode = $ref_rowset ['PRODUCT_ID'] [$product->productId] [0] ['barcode'];
					}
				}
			}
			//库位下如果没有商品
			if (empty ( $product_faclity_location_list )) {
				$product_faclity_location_list [] = ( object ) array ("locationSeqId" => $location_seq_id );
			}
			$product_faclity_location_lists [$facility_id] [$location_seq_id] [] = $product_faclity_location_list;
		}
		
		return $product_faclity_location_lists;
	}
	
	static function GetPartyNameByID($facility_id){
		//获取组织的名字(先获取该仓库的所属的组织id,然后再查找组织名字)
		$facility_lists = facility_get_all_list ();
		$party_id = $facility_lists [$facility_id]->ownerPartyId;
		$party_list = party_get_all_list ();
		$party_name = $party_list [$party_id]->name;
		return $party_name;
	}
	
}