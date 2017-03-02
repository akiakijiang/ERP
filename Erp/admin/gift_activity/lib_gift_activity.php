<?php
/**
啦啦啦
全新贈品邏輯
**/

require_once(__DIR__.'/../includes/init.php');

/**
* 
*/
class GiftActivity
{
	/**
	Parameteres Example
	$params={
		gift_activity_name:XXX,
		begin_time,end_time:XXX,
		gift_first:XXX,
		gift_second:XXX,
		gift_third:XXX,
		gift_limit_first:XXX,
		gift_limit_second:XXX,
		gift_limit_third:XXX,
		gift_number_once:XXX,
		gift_number_once_max:XXX,
		least_number:XXX,
		least_payment:XXX,
		repeat_type:XXX,
		party_id:XXX,
		activity_type:XXX
	};
	$lists={
		'DISTRIBUTOR':[XXX,YYY],//this kind to be a set
		'FACILITY':[], // this kind to be an empty set
		'REGION':[0], // this kind to be an NEGLECT SIGNAL as this condition could be neglected, such as for all region
		'GOODS_INCLUDED':[],
		'GOODS_EXCLUDED':[],
		'GOODS_CAT_INCLUDED':[],
		'GOODS_CAT_EXCLUDED':[],
		'GOODS_NECESSARY':[],
		'ANGOU':[]
	};
	**/
	public static function createGiftActivity($params,$lists,&$message){
		global $db;
		$db->start_transaction();
		try {
			$GA_ID=GiftActivityModel::insert($params);
			if(empty($GA_ID)){
				//總之沒創建成功
				throw new Exception("創建贈品活動失敗", 1);
			}

			if($params['level']){
				foreach ($params['level_data'] as $params_num => $params_value){
					$afx = GiftActivityLevelModel::insert($GA_ID,$params_value);

				// var_dump($params['level_data']);
				// $afx = GiftActivityLevelModel::insert($GA_ID,);
				// var_dump($params['level_data']);
				// die();
					if(empty($afx)){
						throw new Exception("等级活动赠品为插入", 2);
					}
				}
			}

			//以下列表項的映射，如果只有一個0映射，表示這個條件有豁免；一個映射都沒有表示範圍為空
			//分銷商 
			$distributor_list=$lists['DISTRIBUTOR'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'DISTRIBUTOR',$distributor_list);
			if($afx!=count($distributor_list)){
				throw new Exception("創建分銷商列表明細似乎數量不對", 2);
			}
			//倉庫
			$facility_list=$lists['FACILITY'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'FACILITY',$facility_list);
			if($afx!=count($facility_list)){
				throw new Exception("創建倉庫列表明細似乎數量不對", 2);
			}
			//省份
			$region_list=$lists['REGION'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'REGION',$region_list);
			if($afx!=count($region_list)){
				throw new Exception("創建省份列表明細似乎數量不對", 2);
			}
			//商品限定
			$included_mono_list=$lists['GOODS_INCLUDED'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'GOODS_INCLUDED',$included_mono_list);
			if($afx!=count($included_mono_list)){
				throw new Exception("創建商品限定列表明細似乎數量不對", 2);
			}
			//商品排除
			$excluded_mono_list=$lists['GOODS_EXCLUDED'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'GOODS_EXCLUDED',$excluded_mono_list);
			if($afx!=count($excluded_mono_list)){
				throw new Exception("創建商品排除列表明細似乎數量不對", 2);
			}
			//類目限定
			$included_cat_list=$lists['GOODS_CAT_INCLUDED'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'GOODS_CAT_INCLUDED',$included_cat_list);
			if($afx!=count($included_cat_list)){
				throw new Exception("創建類目限定列表明細似乎數量不對", 2);
			}
			//類目排除
			$excluded_cat_list=$lists['GOODS_CAT_EXCLUDED'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'GOODS_CAT_EXCLUDED',$excluded_cat_list);
			if($afx!=count($excluded_cat_list)){
				throw new Exception("創建類目排除列表明細似乎數量不對", 2);
			}
			//必须包含的商品
			$necessary_mono_list=$lists['GOODS_NECESSARY'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'GOODS_NECESSARY',$necessary_mono_list);
			if($afx!=count($necessary_mono_list)){
				throw new Exception("创建必须包含的商品似乎数量不对", 2);
			}
			//暗号
			$angou_list=$lists['ANGOU'];
			$afx=GiftActivityDetailMapping::insertList($GA_ID,'ANGOU',$angou_list);
			if($afx!=count($angou_list)){
				throw new Exception("暗号没能写进去", 2);
			}
			
			
			$db->commit();
			return $GA_ID;
		} catch (Exception $e) {
			$db->rollback();
			$message=$e->getMessage();
			return false;
		}
	}

	/**
	Parameters look like create;
	**/
	public static function updateGiftActivity($gift_activity_id,$params,$lists,&$message){
		global $db;
		$db->start_transaction();
		try {
			$afx=GiftActivityModel::update($gift_activity_id,$params);
			if($afx!=1){
				//總之沒創建成功
				throw new Exception("更新贈品活動失敗", 1);
			}

			//以下列表項的映射，如果只有一個0映射，表示這個條件有豁免；一個映射都沒有表示範圍為空

			//更新時要先幹掉原來的
			$afx=GiftActivityDetailMapping::deleteByGAID($gift_activity_id);

			//分銷商 

			$distributor_list=$lists['DISTRIBUTOR'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'DISTRIBUTOR',$distributor_list);
			if($afx!=count($distributor_list)){
				throw new Exception("創建分銷商列表明細似乎數量不對", 2);
			}
			//倉庫
			$facility_list=$lists['FACILITY'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'FACILITY',$facility_list);
			if($afx!=count($facility_list)){
				throw new Exception("創建倉庫列表明細似乎數量不對", 2);
			}
			//省份
			$region_list=$lists['REGION'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'REGION',$region_list);
			if($afx!=count($region_list)){
				throw new Exception("創建省份列表明細似乎數量不對", 2);
			}
			//商品限定
			$included_mono_list=$lists['GOODS_INCLUDED'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'GOODS_INCLUDED',$included_mono_list);
			if($afx!=count($included_mono_list)){
				throw new Exception("創建商品限定列表明細似乎數量不對", 2);
			}
			//商品排除
			$excluded_mono_list=$lists['GOODS_EXCLUDED'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'GOODS_EXCLUDED',$excluded_mono_list);
			if($afx!=count($excluded_mono_list)){
				throw new Exception("創建商品排除列表明細似乎數量不對", 2);
			}
			//類目限定
			$included_cat_list=$lists['GOODS_CAT_INCLUDED'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'GOODS_CAT_INCLUDED',$included_cat_list);
			if($afx!=count($included_cat_list)){
				throw new Exception("創建類目限定列表明細似乎數量不對", 2);
			}
			//類目排除
			$excluded_cat_list=$lists['GOODS_CAT_EXCLUDED'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'GOODS_CAT_EXCLUDED',$excluded_cat_list);
			if($afx!=count($excluded_cat_list)){
				throw new Exception("創建類目排除列表明細似乎數量不對", 2);
			}
			//必须包含的商品
			$necessary_mono_list=$lists['GOODS_NECESSARY'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'GOODS_NECESSARY',$necessary_mono_list);
			if($afx!=count($necessary_mono_list)){
				throw new Exception("创建必须包含的商品似乎数量不对", 2);
			}
			//必须包含的商品
			$angou_list=$lists['ANGOU'];
			$afx=GiftActivityDetailMapping::insertList($gift_activity_id,'ANGOU',$angou_list);
			if($afx!=count($angou_list)){
				throw new Exception("暗号似乎数量不对", 2);
			}

			//level表中更新数据
			if($params['level']){
				// var_dump($params['level_data']);
				$class_activity_add_string_finally_array = $params['level_data'];
				for($m=0;$m<count($class_activity_add_string_finally_array);$m++){
					// var_dump($class_activity_add_string_finally_array[$m]);
					if(is_array($class_activity_add_string_finally_array[$m])){
						if($class_activity_add_string_finally_array[$m][5] != undefined){
									// var_dump($class_activity_add_update_final_key);
							$class_activity_add_params['gift'] = $class_activity_add_string_finally_array[$m][0];
							$class_activity_add_params['gift_limit'] = $class_activity_add_string_finally_array[$m][1];
							$class_activity_add_params['least_payment'] = $class_activity_add_string_finally_array[$m][2];
							$class_activity_add_params['least_number'] = $class_activity_add_string_finally_array[$m][3];
							$class_activity_add_params['gift_number'] = $class_activity_add_string_finally_array[$m][4];
							$change_update_result = GiftActivityLevelModel::update($class_activity_add_string_finally_array[$m][5],$gift_activity_id,$class_activity_add_params);
							if($change_update_result != 1){
								throw new Exception("更新失败", 1);
							}
						}else{
									// $class_activity_add_params['gift_activity_id'] = $gift_activity_id;
							$class_activity_add_params['gift'] = $class_activity_add_string_finally_array[$m][0];
							$class_activity_add_params['gift_limit'] = $class_activity_add_string_finally_array[$m][1];
							$class_activity_add_params['least_payment'] = $class_activity_add_string_finally_array[$m][2];
							$class_activity_add_params['least_number'] = $class_activity_add_string_finally_array[$m][3];
							$class_activity_add_params['gift_number'] = $class_activity_add_string_finally_array[$m][4];
							$change_insert_result = GiftActivityLevelModel::insert($gift_activity_id,$class_activity_add_params);
							if(empty($change_insert_result)){
								throw new Exception("添加新赠品失败", 1);									
							}
						}
					}
				}
			}
			
			$db->commit();
			return $gift_activity_id;
		} catch (Exception $e) {
			$db->rollback();
			$message=$e->getMessage();
			return false;
		}
	}

	public static function removeGiftActivity($gift_activity_id){
		return GiftActivityModel::update($gift_activity_id,array('status'=>'DELETE'));
	}

	public static function listGiftActivity($params,$limit=10,$offset=0,&$count,$form_search){
		global $db;

		$form_search_active_name = "";
		$form_search_start_time = "";
		$form_search_end_time = "";
		$form_search_distributor_name = "";
		$form_search_activity_cue = "";
		if(!empty($form_search['active_name'])){
			$form_search_active_name = " AND ga.gift_activity_name LIKE '%".$form_search['active_name']."%'";
		}
		if(!empty($form_search['start_time'])){
			$form_search_start_time = " AND ga.begin_time >= '".$form_search['start_time']."'";
		}
		if(!empty($form_search['end_time'])){
			$form_search_end_time = " AND ga.end_time <= '".$form_search['end_time']."'";
		}
		if(!empty($form_search['distributor_name'])){
			$form_search_distributor_name = " AND (gadm.mapping_type = 'DISTRIBUTOR' AND gadm.mapping_value IN (".$form_search['distributor_name'].",0))";//这里加0是把分销商不限制的活动也加上去
		}
		if(!empty($form_search['activity_cue'])){
			$form_search_activity_cue = " AND (gadm.mapping_type = 'ANGOU' AND gadm.mapping_value = '".$form_search['activity_cue']."')";
		}
		$count=-1;
		$conditions="";
		$exists_sql="";
		if(!empty($params)){
			$exists_sql="AND exists(";
				$exists_sql.="SELECT
				gadm.*
				FROM
				ecshop.ecs_gift_activity_detail_mapping gadm
				WHERE
				gadm.gift_activity_id = ga.gift_activity_id 
				AND gadm.`status` = 'OK'
				AND (1 
					";
					$exists_sql_parts=array();
					foreach ($params as $key => $value) {
						if(in_array($key, array(
							'DISTRIBUTOR',
							'FACILITY',
							'REGION',
							'GOODS_INCLUDED',
							'GOODS_EXCLUDED',
							'GOODS_CAT_INCLUDED',
							'GOODS_CAT_EXCLUDED',
							'GOODS_NECESSARY',
							'ANGOU',
							))){
							$exists_sql_parts[]=" OR (gadm.mapping_type = '{$key}' AND gadm.mapping_value = '{$value}') ";
					}else{
						$conditions.="  AND ga.`{$key}`='{$value}' ";
					}
				}
				if(!empty($exists_sql_parts)){
					$exists_sql=$exists_sql.implode(' ', $exists_sql_parts)."))";
					// 上面这里是不是也要修改的？
				}else{
					$exists_sql_final =$exists_sql.")".$form_search_distributor_name.")";
					$exists_sql = $exists_sql_final.$exists_sql.")".$form_search_activity_cue.")";
					// 这里应该是有问题的,不能这么来写啊
				}

		}
		// var_dump($exists_sql);
		$conditions .= $form_search_active_name.$form_search_start_time.$form_search_end_time;

		$sql_count="SELECT
		count(ga.gift_activity_id)
		FROM
		ecshop.ecs_gift_activity ga
		WHERE 1
		AND	ga.`status` = 'OK'
		{$conditions}
		{$exists_sql}
		";
		$count=$db->getOne($sql_count);

		$sql="SELECT
		ga.gift_activity_id
		FROM
		ecshop.ecs_gift_activity ga
		WHERE 1
		AND	ga.`status` = 'OK'
		{$conditions}
		{$exists_sql}
		LIMIT {$limit} OFFSET {$offset} 
		";
		// var_dump($sql);
		$ga_ids = $db->getCol($sql);

				// echo "COUNT SQL: ".PHP_EOL.$sql_count.PHP_EOL;
				// echo "LIST SQL: ".PHP_EOL.$sql.PHP_EOL;

		$ga_list=array();
		if(!empty($ga_ids)){
			foreach ($ga_ids as $ga_id) {
				$sql="SELECT
				ga.`gift_activity_id`,
				ga.`gift_activity_name`,
				ga.`party_id`,
				ga.`begin_time`,
				ga.`end_time`,
				ga.`gift_first`,
				ga.`gift_second`,
				ga.`gift_third`,
				ga.`gift_limit_first`,
				ga.`gift_limit_second`,
				ga.`gift_limit_third`,
				ga.`gift_number_once`,
				ga.`gift_number_once_max`,
				ga.`least_number`,
				ga.`least_payment`,
				ga.`repeat_type`,
				ga.`status`,
				ga.`activity_type`,
				ga.`necessary_all`,
				gadm.mapping_type,
				gadm.mapping_value
				FROM
				ecshop.ecs_gift_activity ga
				LEFT JOIN ecshop.ecs_gift_activity_detail_mapping gadm ON ga.gift_activity_id = gadm.gift_activity_id
				AND gadm.`status` = 'OK'
				WHERE
				ga.gift_activity_id = '{$ga_id}'
				";
				$one_ga_list=$db->getAll($sql);
				foreach ($one_ga_list as $key => $one_ga_item) {
					$ga_list[$ga_id]['gift_activity_id']=$one_ga_item['gift_activity_id'];
					$ga_list[$ga_id]['gift_activity_name']=$one_ga_item['gift_activity_name'];
					$ga_list[$ga_id]['party_id']=$one_ga_item['party_id'];
					$ga_list[$ga_id]['begin_time']=$one_ga_item['begin_time'];
					$ga_list[$ga_id]['end_time']=$one_ga_item['end_time'];
					$ga_list[$ga_id]['gift_first']=$one_ga_item['gift_first'];
					$ga_list[$ga_id]['gift_second']=$one_ga_item['gift_second'];
					$ga_list[$ga_id]['gift_third']=$one_ga_item['gift_third'];
					$ga_list[$ga_id]['gift_limit_first']=$one_ga_item['gift_limit_first'];
					$ga_list[$ga_id]['gift_limit_second']=$one_ga_item['gift_limit_second'];
					$ga_list[$ga_id]['gift_limit_third']=$one_ga_item['gift_limit_third'];
					$ga_list[$ga_id]['gift_number_once']=$one_ga_item['gift_number_once'];
					$ga_list[$ga_id]['gift_number_once_max']=$one_ga_item['gift_number_once_max'];
					$ga_list[$ga_id]['least_number']=$one_ga_item['least_number'];
					$ga_list[$ga_id]['least_payment']=$one_ga_item['least_payment'];
					$ga_list[$ga_id]['repeat_type']=$one_ga_item['repeat_type'];
					$ga_list[$ga_id]['status']=$one_ga_item['status'];
					$ga_list[$ga_id]['activity_type']=$one_ga_item['activity_type'];
					$ga_list[$ga_id]['necessary_all']=$one_ga_item['necessary_all'];
					if(!empty($one_ga_item['mapping_type'])){
						$ga_list[$ga_id][$one_ga_item['mapping_type']][]=$one_ga_item['mapping_value'];
					}
				}

			}
		}

		foreach ($ga_list as $key => $value) {

			if(strpos($value['gift_first'], 'TC')===0){
				$sql="SELECT
				NAME
				FROM
				ecshop.distribution_group_goods
				WHERE
				CODE = '{$value['gift_first']}'
				";
			}else{
				$gs=explode('_', $value['gift_first']);
				$goods_id=$gs[0];
				if(count($gs)>1){
					$style_id=$gs[1];
				}else{
					$style_id=0;
				}
				$sql="SELECT
				g.goods_name
				FROM
				ecshop.ecs_goods g
				LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
				WHERE
				g.goods_id = '{$goods_id}'
				AND IFNULL(gs.style_id, 0)= '{$style_id}'
				LIMIT 1
				";
			}
			$name=$db->getOne($sql);
			$ga_list[$key]['_gift_first']="[".$value['gift_first']."]".$name;

			if(strpos($value['gift_second'], 'TC')===0){
				$sql="SELECT
				NAME
				FROM
				ecshop.distribution_group_goods
				WHERE
				CODE = '{$value['gift_second']}'
				";
			}else{
				$gs=explode('_', $value['gift_second']);
				$goods_id=$gs[0];
				if(count($gs)>1){
					$style_id=$gs[1];
				}else{
					$style_id=0;
				}
				$sql="SELECT
				g.goods_name
				FROM
				ecshop.ecs_goods g
				LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
				WHERE
				g.goods_id = '{$goods_id}'
				AND IFNULL(gs.style_id, 0)= '{$style_id}'
				LIMIT 1
				";
			}
			$name=$db->getOne($sql);
			$ga_list[$key]['_gift_second']="[".$value['gift_second']."]".$name;

			if(strpos($value['gift_third'], 'TC')===0){
				$sql="SELECT
				NAME
				FROM
				ecshop.distribution_group_goods
				WHERE
				CODE = '{$value['gift_third']}'
				";
			}else{
				$gs=explode('_', $value['gift_third']);
				$goods_id=$gs[0];
				if(count($gs)>1){
					$style_id=$gs[1];
				}else{
					$style_id=0;
				}
				$sql="SELECT
				g.goods_name
				FROM
				ecshop.ecs_goods g
				LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
				WHERE
				g.goods_id = '{$goods_id}'
				AND IFNULL(gs.style_id, 0)= '{$style_id}'
				LIMIT 1
				";
			}
			$name=$db->getOne($sql);
			$ga_list[$key]['_gift_third']="[".$value['gift_third']."]".$name;

			if(!empty($value['DISTRIBUTOR'])){
				foreach ($value['DISTRIBUTOR'] as $distributor_id) {
					$sql="SELECT NAME FROM ecshop.distributor WHERE distributor_id = '{$distributor_id}' LIMIT 1;";
					$name=$db->getOne($sql);
					$ga_list[$key]['_DISTRIBUTOR'][]=$name;
				}
			}
			if(!empty($value['FACILITY'])){
				foreach ($value['FACILITY'] as $facility_id) {
					$sql="SELECT FACILITY_NAME FROM romeo.facility WHERE FACILITY_ID = '{$facility_id}' LIMIT 1;";
					$name=$db->getOne($sql);
					$ga_list[$key]['_FACILITY'][]=$name;
				}
			}
			if(!empty($value['REGION'])){
				foreach ($value['REGION'] as $region_id) {
					$sql="SELECT region_name FROM ecshop.ecs_region WHERE region_id = '{$region_id}' LIMIT 1;";
					$name=$db->getOne($sql);
					$ga_list[$key]['_REGION'][]=$name;
				}
			}
			if(!empty($value['GOODS_NECESSARY'])){
				foreach ($value['GOODS_NECESSARY'] as $out_id) {
					if(strpos($out_id, 'TC')===0){
						$sql="SELECT
						NAME
						FROM
						ecshop.distribution_group_goods
						WHERE
						CODE = '{$out_id}'
						";
					}else{
						$gs=explode('_', $out_id);
						$goods_id=$gs[0];
						if(count($gs)>1){
							$style_id=$gs[1];
						}else{
							$style_id=0;
						}
						$sql="SELECT
						g.goods_name
						FROM
						ecshop.ecs_goods g
						LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
						WHERE
						g.goods_id = '{$goods_id}'
						AND IFNULL(gs.style_id, 0)= '{$style_id}'
						LIMIT 1
						";
					}
					$name=$db->getOne($sql);
					$ga_list[$key]['_GOODS_NECESSARY'][]="[".$out_id."]".$name;
				}
			}
			if(!empty($value['GOODS_INCLUDED'])){
				foreach ($value['GOODS_INCLUDED'] as $out_id) {
					if(strpos($out_id, 'TC')===0){
						$sql="SELECT
						NAME
						FROM
						ecshop.distribution_group_goods
						WHERE
						CODE = '{$out_id}'
						";
					}else{
						$gs=explode('_', $out_id);
						$goods_id=$gs[0];
						if(count($gs)>1){
							$style_id=$gs[1];
						}else{
							$style_id=0;
						}
						$sql="SELECT
						g.goods_name
						FROM
						ecshop.ecs_goods g
						LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
						WHERE
						g.goods_id = '{$goods_id}'
						AND IFNULL(gs.style_id, 0)= '{$style_id}'
						LIMIT 1
						";
					}
					$name=$db->getOne($sql);
					$ga_list[$key]['_GOODS_INCLUDED'][]="[".$out_id."]".$name;
				}
			}
			if(!empty($value['GOODS_EXCLUDED'])){
				foreach ($value['GOODS_EXCLUDED'] as $out_id) {
					if(strpos($out_id, 'TC')===0){
						$sql="SELECT
						NAME
						FROM
						ecshop.distribution_group_goods
						WHERE
						CODE = '{$out_id}'
						";
					}else{
						$gs=explode('_', $out_id);
						$goods_id=$gs[0];
						if(count($gs)>1){
							$style_id=$gs[1];
						}else{
							$style_id=0;
						}
						$sql="SELECT
						g.goods_name
						FROM
						ecshop.ecs_goods g
						LEFT JOIN ecshop.ecs_goods_style gs ON g.goods_id = gs.goods_id and gs.is_delete=0
						WHERE
						g.goods_id = '{$goods_id}'
						AND IFNULL(gs.style_id, 0)= '{$style_id}'
						LIMIT 1
						";
					}
					$name=$db->getOne($sql);
					$ga_list[$key]['_GOODS_EXCLUDED'][]="[".$out_id."]".$name;
				}
			}
			if(!empty($value['GOODS_CAT_INCLUDED'])){
				foreach ($value['GOODS_CAT_INCLUDED'] as $cat_id) {
					$gs=explode('_', $out_id);
					$goods_id=$gs[0];
					if(count($gs)>1){
						$style_id=$gs[1];
					}else{
						$style_id=0;
					}
					$sql="SELECT
					cat_name
					FROM
					ecshop.ecs_category
					WHERE
					cat_id = '{$cat_id}'
					LIMIT 1
					";
					$name=$db->getOne($sql);
					$ga_list[$key]['_GOODS_CAT_INCLUDED'][]="[".$cat_id."]".$name;
				}
			}
			if(!empty($value['GOODS_CAT_EXCLUDED'])){
				foreach ($value['GOODS_CAT_EXCLUDED'] as $cat_id) {
					$gs=explode('_', $out_id);
					$goods_id=$gs[0];
					if(count($gs)>1){
						$style_id=$gs[1];
					}else{
						$style_id=0;
					}
					$sql="SELECT
					cat_name
					FROM
					ecshop.ecs_category
					WHERE
					cat_id = '{$cat_id}'
					LIMIT 1
					";
					$name=$db->getOne($sql);
					$ga_list[$key]['_GOODS_CAT_EXCLUDED'][]="[".$cat_id."]".$name;
				}
			}
		}

		return $ga_list;
	}

public static function descOfGiftActivity($GA_ID){
	$count=0;
	$list=GiftActivity::listGiftActivity(array("gift_activity_id"=>$GA_ID),1,0,$count);
	if($list){
		$ga=$list[$GA_ID];
	}else{
		$ga=null;
	}
	if($ga){
			//desc
		$desc="买赠活动“".$ga['gift_activity_name']."”的详情如此如此："."在".$ga['begin_time']."到".$ga['end_time']."期间，";
			//region
		if(count($ga['REGION'])==1 && $ga['REGION'][0]=='0'){
			$desc.="任意地区的";
		}else{
			$desc.="窝藏在[".implode('、', $ga['_REGION'])."]的";
		}
		$desc.="愚蠢的钱包";
			//distributor
		if(count($ga['DISTRIBUTOR'])==1 && $ga['DISTRIBUTOR'][0]=='0'){
			$desc.="在任意分销商处，";
		}else{
			$desc.="在分销商[".implode('、', $ga['_DISTRIBUTOR'])."]处，";
		}
			//mono
		$desc.="购买符合以下条件的商品：";
		if(count($ga['GOODS_INCLUDED'])==1 && $ga['GOODS_INCLUDED'][0]=='0'){
			$desc.="";
		}else{
			$desc.="商品在[".implode('、', $ga['_GOODS_INCLUDED'])."]之范围内、";
		}
		if(count($ga['GOODS_EXCLUDED'])==1 && $ga['GOODS_EXCLUDED'][0]=='0'){
			$desc.="";
		}else{
			$desc.="商品不在[".implode('、', $ga['_GOODS_EXCLUDED'])."]之范围内、";
		}
		if(count($ga['GOODS_CAT_INCLUDED'])==1 && $ga['GOODS_CAT_INCLUDED'][0]=='0'){
			$desc.="";
		}else{
			$desc.="商品类目在[".implode('、', $ga['_GOODS_CAT_INCLUDED'])."]之范围内、";
		}
		if(count($ga['GOODS_CAT_EXCLUDED'])==1 && $ga['GOODS_CAT_EXCLUDED'][0]=='0'){
			$desc.="";
		}else{
			$desc.="商品类目不在[".implode('、', $ga['_GOODS_CAT_EXCLUDED'])."]之范围内、";
		}
			//Necessary Goods
		if(count($ga['GOODS_NECESSARY'])==1 && $ga['GOODS_NECESSARY'][0]=='0'){
			$desc.="";
		}else{
			$desc.="必须包含这些商品[".implode('、', $ga['_GOODS_NECESSARY'])."]之范围内、";
		}
			//facility
		if(count($ga['FACILITY'])==1 && $ga['FACILITY'][0]=='0'){
			$desc.="经由任意仓库发货的，";
		}else{
			$desc.="经由仓库[".implode('、', $ga['_FACILITY'])."]发货的，";
		}
			//gift
		$desc.="买满".$ga['least_number']."件且金额达到".$ga['least_payment']."元则赠送相应赠品".$ga['gift_number_once']."件，";
			//repeat
		if($ga['repeat_type']=='BY_NUMBER'){
			$desc.="每满".$ga['least_number']."件再送赠品".$ga['gift_number_once']."件，";
		}elseif($ga['repeat_type']=='BY_PAYMENT'){
			$desc.="每满".$ga['least_payment']."元再送赠品".$ga['gift_number_once']."件，";
		}else{
			$desc.="不可叠加，";
		}
			//limitation
		$desc.="每单最多赠送".$ga['gift_number_once_max']."件。";
		$desc.="赠品按照下列次序赠送，送完为止：".$ga['_gift_first']."（还有".$ga['gift_limit_first']."件）";
		if(!empty($ga['gift_second'])){
			$desc.="、".$ga['_gift_second']."（还有".$ga['gift_limit_second']."件）";
		}
		if(!empty($ga['gift_third'])){
			$desc.="、".$ga['_gift_third']."（还有".$ga['gift_limit_third']."件）";
		}
		$desc.="。";
		return $desc;
	}else{
		return "什么都没有！";
	}

	return json_encode($ga);
}
}

/**
* 
*/
class GiftActivityModel
{
	
	public static function insert($params){
		global $db;

		if(empty($params['gift_activity_name'])){
			return false;
		}
		if(empty($params['party_id'])){
			return false;
		}
		if(empty($params['begin_time'])){
			return false;
		}
		if(empty($params['end_time'])){
			return false;
		}
		if(empty($params['activity_type'])){
			return false;
		}
		if($params['activity_type'] == 'NOMAL'){
			if(empty($params['gift_first'])){
				return false;
			}
			if(empty($params['gift_limit_first']) || $params['gift_limit_first']<0){
				return false;
			}
			if(empty($params['gift_number_once'])){
				return false;
			}
		}
		if($params['repeat_type']=='BY_NUMBER' && empty($params['least_number'])){
			return false;
		}
		if($params['repeat_type']=='BY_PAYMENT' && empty($params['least_payment'])){
			return false;
		}

		

		$sql="INSERT INTO ecshop.ecs_gift_activity (
			`gift_activity_id`,
			`gift_activity_name`,
			`party_id`,
			`begin_time`,
			`end_time`,
			`gift_first`,
			`gift_second`,
			`gift_third`,
			`gift_limit_first`,
			`gift_limit_second`,
			`gift_limit_third`,
			`gift_number_once`,
			`gift_number_once_max`,
			`least_number`,
			`least_payment`,
			`repeat_type`,
			`status`,
			`create_time`,
			`update_time`,
			`create_user`,
			`update_user`,
			`activity_type`,
			`necessary_all`
			)VALUES(
			NULL,
			'{$params['gift_activity_name']}',
			'{$params['party_id']}',
			'{$params['begin_time']}',
			'{$params['end_time']}',
			'{$params['gift_first']}',
			'{$params['gift_second']}',
			'{$params['gift_third']}',
			'{$params['gift_limit_first']}',
			'{$params['gift_limit_second']}',
			'{$params['gift_limit_third']}',
			'{$params['gift_number_once']}',
			'{$params['gift_number_once_max']}',
			'{$params['least_number']}',
			'{$params['least_payment']}',
			'{$params['repeat_type']}',
			'OK',
			NOW(),
			NOW(),
			'{$_SESSION['admin_name']}',
			'{$_SESSION['admin_name']}',
			'{$params['activity_type']}',
			'{$params['necessary_all']}'
			)
";
$newId=$db->exec($sql);


		//添加Action
		$comment = "添加赠品活动：gift_activity_id=".$newId ;  //详细comment信息后续添加
		$form_name = 'gift_form';
		GiftActivityActions::addInsertAction($sql,$comment,$form_name );
		
		return $newId;
	}

	public static function update($gift_activity_id,$pairs){
		global $db;
		$sql="UPDATE ecshop.ecs_gift_activity
		SET ";
		foreach ($pairs as $key => $value) {
			if($key != 'level' && $key != 'level_data'){
				$sql .= $key."='".$value."', ";
			}
		}
		$sql.=" update_time=now() 
		WHERE gift_activity_id='{$gift_activity_id}'
		";
		$afx=$db->exec($sql);
		
		//添加Action
		$comment = "更新赠品活动：gift_activity_id=".$gift_activity_id ;  //详细comment信息后续添加
		$form_name = 'gift_form';
		GiftActivityActions::addUpdateAction($sql,  $comment ,$form_name );
		
		return $afx;
	}

	public static function delete($gift_activity_id){
		global $db;
		$sql="DELETE FROM ecshop.ecs_gift_activity
		WHERE gift_activity_id='{$gift_activity_id}'
		";
		$afx=$db->exec($sql);
		
		//添加Action
		$comment = "删除赠品活动：gift_activity_id=".$gift_activity_id ;  //详细comment信息后续添加
		$form_name = 'gift_form';
		GiftActivityActions::addDeleteAction($sql,  $comment ,$form_name );
		
		return $afx;
	}

	public static function select($params,$limit=0 ,$offset=0){
		global $db;
		$sql="SELECT * FROM ecshop.ecs_gift_activity
		WHERE 1 ";
		foreach ($params as $key => $value) {
			$sql.=" AND $key='{$value}' ";
		}
		if($limit>0){
			$sql.=" limit ".$limit;
			if($offset>=0){
				$sql.=" offset ".$offset;
			}
		}

		return $db->getAll($sql);
	}
}

/**
* 
*/
class GiftActivityDetailMapping
{
	public static function insertList($gift_activity_id,$type,$list){
		$afx_sum=0;
		if(empty($list)){
			return 0;
		}
		foreach ($list as $key => $value) {
			$params=array("gift_activity_id"=>$gift_activity_id,"mapping_type"=>$type,"mapping_value"=>$value);
			$afx=GiftActivityDetailMapping::insert($params);
			$afx_sum+=($afx?1:0);
		}
		
		
		
		return $afx_sum;
	}
	
	public static function insert($params)
	{
		global $db;
		$sql="INSERT INTO ecshop.ecs_gift_activity_detail_mapping (
			`gift_activity_detail_mapping_id`,
			`gift_activity_id`,
			`mapping_type`,
			`mapping_value`,
			`status`,
			`create_time`,
			`update_time`
			)VALUES(
			NULL,
			'{$params['gift_activity_id']}',
			'{$params['mapping_type']}',
			'{$params['mapping_value']}',
			'OK',
			NOW(),
			NOW()
			)
";
$newId=$db->exec($sql);

		//添加Action
$comment = "添加赠品活动详情映射：gift_activity_id=".$params['gift_activity_id']." mapping_type=" .$params['mapping_type']
			." mapping_value=".$params['mapping_value']. " gift_activity_detail_mapping_id=".$newId ;  //详细comment信息后续添加
			$form_name = 'gift_mapping_form';
			GiftActivityActions::addInsertAction($sql,  $comment , $form_name );

			return $newId;
		}

		public static function update($gift_activity_detail_mapping_id,$pairs){
			global $db;
			$sql="UPDATE ecshop.ecs_gift_activity_detail_mapping
			SET ";
			foreach ($pairs as $key => $value) {
				$sql .= $key."='".$value."', ";
			}
			$sql.=" update_time=now() 
			WHERE gift_activity_detail_mapping_id='{$gift_activity_detail_mapping_id}'
			";
			$afx=$db->exec($sql);

		//添加Action
		$comment = "更新赠品活动详情映射：gift_activity_detail_mapping_id=".$gift_activity_detail_mapping_id ;  //详细comment信息后续添加
		$form_name = 'gift_mapping_form';
		GiftActivityActions::addUpdateAction($sql,  $comment , $form_name  );
		
		return $afx;
	}

	public static function delete($gift_activity_detail_mapping_id){
		global $db;
		$sql="DELETE FROM ecshop.ecs_gift_activity_detail_mapping
		WHERE gift_activity_detail_mapping_id='{$gift_activity_detail_mapping_id}'
		";
		$afx=$db->exec($sql);
		
		//添加Action
		$comment = "删除赠品活动详情映射：gift_activity_detail_mapping_id=".$gift_activity_detail_mapping_id ;  //详细comment信息后续添加
		$form_name = 'gift_mapping_form';
		GiftActivityActions::addDeleteAction($sql,  $comment , $form_name  );
		return $afx;
	}

	public static function deleteByGAID($gift_activity_id){
		global $db;
		$sql="DELETE FROM ecshop.ecs_gift_activity_detail_mapping
		WHERE gift_activity_id='{$gift_activity_id}'
		";
		$afx=$db->exec($sql);
		
		//添加Action
		$comment = "删除赠品活动所有详情映射：gift_activity_id=".$gift_activity_id ;  //详细comment信息后续添加
		$form_name = 'gift_mapping_form';
		GiftActivityActions::addDeleteAction($sql,  $comment , $form_name );
		return $afx;
	}

	public static function select($params,$limit=0 ,$offset=0){
		global $db;
		$sql="SELECT * FROM ecshop.ecs_gift_activity_detail_mapping
		WHERE 1 ";
		foreach ($params as $key => $value) {
			$sql.=" AND $key='{$value}' ";
		}
		if($limit>0){
			$sql.=" limit ".$limit;
			if($offset>=0){
				$sql.=" offset ".$offset;
			}
		}
		return $db->getAll($sql);
	}
}

/**
 *
 */
class GiftActivityLevelModel
{

	public static function insert($activity_id,$params){
		global $db;

		if(empty($activity_id)){
			return false;
		}
		if(empty($params['gift'])){
			return false;
		}
		if(empty($params['gift_limit'])){
			return false;
		}
		if(empty($params['least_payment'])){
			return false;
		}
		if(empty($params['least_number'])){
			return false;
		}
		if(empty($params['gift_number'])){
			return false;
		}
		$sql="INSERT INTO ecshop.ecs_gift_activity_level (
			`gift_activity_level_id`,
			`gift_activity_id`,
			`gift`,
			`gift_limit`,
			`least_payment`,
			`least_number`,
			`gift_number`,
			`created_stamp`,
			`update_stamp`
			)VALUES(
			NULL,
			'{$activity_id}',
			'{$params['gift']}',
			'{$params['gift_limit']}',
			'{$params['least_payment']}',
			'{$params['least_number']}',
			'{$params['gift_number']}',
			NOW(),
			NOW())";
$newId=$db->exec($sql);

       //添加Action
	   $comment = "添加赠品活动等级详情：gift_activity_id=".$activity_id." gift_activity_level_id=".$newId ;  //详细comment信息后续添加
	   $form_name = 'gift_level_form';
	   GiftActivityActions::addInsertAction($sql,  $comment  , $form_name);

	   return $newId;
	}

	public static function update($gift_activity_level_id,$gift_activity_id,$pairs){
		global $db;
		$sql="UPDATE ecshop.ecs_gift_activity_level
		SET ";
		foreach ($pairs as $key => $value) {
			$sql .= $key."='".$value."', ";
		}
		$sql.=" update_stamp=now() 
		WHERE gift_activity_level_id='$gift_activity_level_id' AND gift_activity_id='{$gift_activity_id}'
		";
		$afx=$db->exec($sql);
		
	   //添加Action
	   $comment = "更新赠品活动等级详情：gift_activity_id=".$gift_activity_id." gift_activity_level_id=".$gift_activity_level_id ;  //详细comment信息后续添加
	   $form_name = 'gift_level_form';
	   GiftActivityActions::addUpdateAction($sql,  $comment , $form_name );

	   return $afx;
	}
	
	public static function delete($gift_activity_id){
		global $db;
		$sql="DELETE FROM ecshop.ecs_gift_activity_level
		WHERE gift_activity_id='{$gift_activity_id}'
		";
		$afx=$db->exec($sql);
	    //添加Action
	   $comment = "删除赠品活动所有等级详情：gift_activity_id=".$gift_activity_id ;  //详细comment信息后续添加
	   $form_name = 'gift_level_form';
	   GiftActivityActions::addDeleteAction($sql,  $form_name );
	   return $afx;
	}
	
	public static function select($gift_activity_id){
		global $db;
		$sql="SELECT * FROM ecshop.ecs_gift_activity_level
		WHERE 1 AND gift_activity_id= '{$gift_activity_id}'
		ORDER BY least_payment
		";
		return $db->getAll($sql);
	}
}


/**
 * 记录赠品操作的风险操作，risky_actions
 */
class GiftActivityActions
{
	
	/**
	 * 添加insert操作Action
	 */
	public static function addInsertAction($sql, $comment , $form_name ){	
		global $db;
		//记录操作
		$record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
		"VALUES ('{$_SESSION['admin_name']}', 'add', NOW(), 'lib_gift_activity.php', '$form_name', '".mysql_real_escape_string($sql)."' , '$comment'  )";
		$db->exec($record_sql);

	}
	
	/**
	 * 添加update操作Action
	 */
	public static function addUpdateAction($sql, $comment  , $form_name){	
		global $db;
		$record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
		"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'lib_gift_activity.php', '$form_name', '".mysql_real_escape_string($sql)."', '$comment')";
		$db->exec($record_sql);

	}
	
	/**
	 * 添加update操作Action
	 */
	public static function addDeleteAction($sql, $comment  , $form_name){	
		global $db;
		$record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
		"VALUES ('{$_SESSION['admin_name']}', 'delete', NOW(), 'lib_gift_activity.php', '$form_name', '".mysql_real_escape_string($sql)."', '$comment')";
		$db->exec($record_sql);

	}
}



?>