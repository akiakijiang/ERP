<?php
/**
 * 万人坑
 * ========
 * 大日本帝国万岁
**/

require_once(__DIR__.'/../includes/init.php');

/**
* 
*/
class BWMibunPool
{
	
	public static function mibunList($limit=20,$offset=0,&$total,$block_type=''){
		global $db;

		$cond="";
		if($block_type=='BLOCK'){
			$cond.=" AND blocked_since is not null ";
		}elseif($block_type=='FREE'){
			$cond.=" AND blocked_since is null ";
		}

		$sql="SELECT count(1) FROM ecshop.bw_mibun_pool WHERE 1 {$cond}";
		$total=$db->getOne($sql);

		$sql="SELECT
				*
			FROM
				ecshop.bw_mibun_pool
			WHERE 1 
				{$cond}
			ORDER BY 
				ikenie_id DESC
			LIMIT $limit OFFSET $offset
		";
		$list=$db->getAll($sql);
		return $list;
	}

	public static function seekSomebody($keyword){
		global $db;
		$sql="(SELECT * FROM ecshop.bw_mibun_pool WHERE  mibun_number = '{$keyword}') 
				UNION 
			(SELECT * FROM ecshop.bw_mibun_pool WHERE name = '{$keyword}')
		";
		$list=$db->getAll($sql);
		return $list;
	}

	public static function getSomeIkenie($count,$month_usage_limit=4,$year_usage_limit=0){
		global $db;
		$sql="SELECT
				*
			FROM
				ecshop.bw_mibun_pool
			WHERE
				month_usage<{$month_usage_limit} and (
					{$year_usage_limit}<=0 OR ({$year_usage_limit}>0 AND year_usage<{$year_usage_limit})
				)
			ORDER BY
				month_usage
			LIMIT $count
		";
		$list=$db->getAll($sql);
		return $list;
	}

	public static function addIkenie($mibun_number,$name){
		global $db;
		$sql="INSERT IGNORE INTO ecshop.bw_mibun_pool (
				`ikenie_id`,
				`mibun_number`,
				`name`,
				`month_usage`,
				`month_usage_since`,
				`year_usage`,
				`year_usage_since`,
				`update_time`
			)VALUES(
				NULL,
				'{$mibun_number}',
				'{$name}',
				0,
				now(),
				0,
				now(),
				now()
			)
		";
		$r=$db->exec($sql);
		return $r;
	}

	public static function resetMonthUsage($since){
		global $db;
		$sql="UPDATE ecshop.bw_mibun_pool 
			SET month_usage=0, month_usage_since=now(),update_time=NOW()
			WHERE month_usage_since<='{$since}'
		";
		$r=$db->exec($sql);
		return $r;
	}
	public static function resetYearUsage($since){
		global $db;
		$sql="UPDATE ecshop.bw_mibun_pool 
			SET year_usage=0, year_usage_since=now(),update_time=NOW()
			WHERE year_usage_since<='{$since}'
		";
		$r=$db->exec($sql);
		return $r;
	}

	// 税関に禁じられたものに関すること

	public static function checkListForBlock($list){
		global $db;
		$sql="SELECT mibun_number FROM ecshop.bw_mibun_pool WHERE mibun_number in ('".implode("','", $list)."')";
		$exist_list=$db->getCol($sql);
		$nokori=array_diff($list, $exist_list);
		return $nokori;
	}

	public static function switchBlockStatusForList($list,$toStatus){
		global $db;
		if($toStatus=='BLOCK'){
			$sql="UPDATE ecshop.bw_mibun_pool SET blocked_since=now(),update_time=NOW() WHERE mibun_number in ('".implode("','", $list)."')";
		}elseif($toStatus=='FREE'){
			$sql="UPDATE ecshop.bw_mibun_pool SET blocked_since=NULL,update_time=NOW() WHERE mibun_number in ('".implode("','", $list)."')";
		}else{
			return false;
		}
		$afx=$db->exec($sql);
		return $afx;
	}
}

?>