<?php
define('IN_ECS', true);
require_once(__DIR__.'/../includes/init.php');

/**
* 
*/
class BWTaxAgent
{
	
	public static function query($params,$limit=20,$offset=0,&$count){
		global $db;
		$cond="";
		if(!empty($params)){
			foreach ($params as $key => $value) {
				if($key!='search'){
					$cond.=" AND ".$key." = '".$value."' ";
				}else{
					$cond = " AND (
						bgt.product_id like '%{$value}%' 
					OR bgt.outer_id like '%{$value}%'
					OR eg.goods_name like '%{$value}%'
					)";
				}
			}
		}

		$sql="SELECT
			bgt.*, eg.goods_name,
			es.*
		FROM
			ecshop.bw_goods_tax bgt
		LEFT JOIN ecshop.ecs_goods eg ON bgt.product_id = eg.goods_id
		LEFT JOIN ecshop.ecs_goods_style egs ON eg.goods_id = egs.goods_id and egs.is_delete=0
		AND bgt.outer_id = CONCAT(
			egs.goods_id,
			'_',
			egs.style_id
		)
		LEFT JOIN ecshop.ecs_style es ON egs.style_id = es.style_id
		WHERE
			1 {$cond}
		LIMIT {$limit} OFFSET {$offset}
		";
		$sql_count="SELECT
			count(1)
		FROM
			ecshop.bw_goods_tax bgt
		LEFT JOIN ecshop.ecs_goods eg ON bgt.product_id = eg.goods_id
		LEFT JOIN ecshop.ecs_goods_style egs ON eg.goods_id = egs.goods_id and egs.is_delete=0
		AND bgt.outer_id = CONCAT(
			egs.goods_id,
			'_',
			egs.style_id
		)
		LEFT JOIN ecshop.ecs_style es ON egs.style_id = es.style_id
		WHERE
			1 {$cond}
		";
		$count=$db->getOne($sql_count);
		$list=$db->getAll($sql);
		if(empty($list)){
			$list=array();
		}
		return $list;
	}

	public static function addOneRecord($outer_id,$tax_rate){
		global $db;
		$gs=explode('_', $outer_id);
		if(count($gs)>2){
			return false;
		}
		$product_id=$gs[0];
		$style_id=(count($gs)>1?$gs[1]:0);
		$tax=$tax_rate / 100.0;
		
		if(BWTaxAgent::outerIdExists($product_id,$style_id)){
			$sql="INSERT INTO ecshop.bw_goods_tax (
				product_id,outer_id,tax_rate
			) values (
				'{$product_id}','{$outer_id}','{$tax}'
			) ON DUPLICATE KEY UPDATE tax_rate=VALUES(tax_rate)
			";
			$done = $db->exec($sql);
			return $done;
		}else{
			return false;
		}

	}

	private static function outerIdExists($goods_id,$style_id=0){
		global $db;
		if(empty($style_id)){
			$sql="SELECT 1 FROM ecshop.ecs_goods WHERE goods_id='{$goods_id}' LIMIT 1";
		}else{
			$sql="SELECT 1 FROM ecshop.ecs_goods_style WHERE goods_id='{$goods_id}' AND style_id='{$style_id}' and is_delete=0 LIMIT 1";
		}
		$r=$db->getOne($sql);
		return (empty($r)?false:true);
	}

}

?>