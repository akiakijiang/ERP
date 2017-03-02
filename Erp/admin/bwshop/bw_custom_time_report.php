<?php
define('IN_ECS', true);
require_once(__DIR__.'/../includes/init.php');

date_default_timezone_set("Asia/Shanghai");

$list=getData(160,true);
print_r($list);

function getData($days=60,$is_debug=false){

	global $db;
	global $slave_db;

	$sql="SELECT
		boi.order_id bw_order_id,
		boi.order_sn bw_order_sn,
		boi.outer_order_sn,
		bs.shop_id,
		bs.ecs_distributor_id,
		bs.shop_name,
		boi.apply_status,
		boi.shipping_status,
		boi.custom_history
	FROM
		ecshop.bw_order_info boi
	LEFT JOIN ecshop.bw_shop bs ON bs.shop_id = boi.shop_id
	WHERE
		boi.shipping_status IN('22', '24')
	AND boi.tracking_number != ''
	AND boi.tracking_number IS NOT NULL
	AND boi.update_time >= DATE_SUB(now(), INTERVAL {$days} DAY)
	";

	if($is_debug){
		$list=$db->getAll($sql);
	}else{
		$list=$slave_db->getAll($sql);
	}

	foreach ($list as $key => $bo) {
		$ch=json_decode($bo['custom_history'],true);
		// if($is_debug)echo "[".$bo['bw_order_sn']."]:".PHP_EOL;
		try {
			// if($is_debug)print_r($ch);
			$list[$key]['doc_go']=date_format(date_create("now"), 'Y-m-d H:i:s');
			$list[$key]['goods_go']=date_format(date_create("now"), 'Y-m-d H:i:s');
			$list[$key]['days_inter']=5;
			foreach ($ch['mft']['history'] as $h) {
				if($h['Status']=='22'){
					$list[$key]['doc_go']=$h['CreateTime'];
				}elseif($h['Status']=='24'){
					$list[$key]['goods_go']=$h['CreateTime'];
				}
			}
			$days_inter=date_diff(date_create($list[$key]['doc_go']),date_create($list[$key]['goods_go']))->format('%R%a');
			$list[$key]['days_inter']=0+$days_inter;

			unset($list[$key]['custom_history']);
		} catch (Exception $e) {
			
		}
		
		// if($is_debug)echo "Doc: ". date_format(date_create($list[$key]['doc_go']), 'Y-m-d H:i:s'). PHP_EOL;
		// if($is_debug)echo "Goods: ". date_format(date_create($list[$key]['goods_go']), 'Y-m-d H:i:s'). PHP_EOL;
		// if($is_debug)echo "INTERVAL: ".$list[$key]['days_inter'].PHP_EOL;
	}

	$group=array(
		'1'=>array(),
		'2'=>array(),
		'3'=>array(),
		'4'=>array(),
		'5'=>array(),
		);

	foreach ($list as $key => $bo) {
		if($bo['days_inter']>=1){
			$sql="SELECT
				eoi.order_sn erp_order_sn,
				f.FACILITY_NAME,
				eoi.order_time,
				eoi.confirm_time
			FROM
				ecshop.bw_order_info boi
			LEFT JOIN ecshop.bw_shop bs ON bs.shop_id = boi.shop_id
			LEFT JOIN ecshop.ecs_order_info eoi ON eoi.taobao_order_sn = boi.outer_order_sn
			LEFT JOIN romeo.facility f ON f.facility_id = eoi.facility_id
			WHERE
				eoi.taobao_order_sn = '{$bo['outer_order_sn']}'
			";
			if($is_debug){
				$eo=$db->getAll($sql);
			}else{
				$eo=$slave_db->getAll($sql);
			}
			$bo=array_merge($bo,array(
					'erp_order_sn'=>$eo[0]['erp_order_sn'],
					'facility_name'=>$eo[0]['FACILITY_NAME'],
					'order_time'=>$eo[0]['order_time'],
					'confirm_time'=>$eo[0]['confirm_time'],
				));
		}
		if($bo['days_inter']>=5){
			$group['5'][]=$bo;
		}elseif($bo['days_inter']>=4){
			$group['4'][]=$bo;
		}elseif($bo['days_inter']>=3){
			$group['3'][]=$bo;
		}elseif($bo['days_inter']>=2){
			$group['2'][]=$bo;
		}elseif($bo['days_inter']>=1){
			$group['1'][]=$bo;
		}
	}

	return $group;

}
?>