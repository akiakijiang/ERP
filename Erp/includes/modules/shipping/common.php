<?php

/**
 * 基本配送方式，与地域和重量无关，费用从配置中读
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$code = 'common';

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $sql = "SELECT * FROM {$ecs->table('shipping')} s 
            LEFT JOIN {$ecs->table('carrier')} c ON s.default_carrier_id = c.carrier_id 
            WHERE shipping_code = '{$code}' ";
            
   $shippings = $db->getAll($sql);
    foreach ($shippings as $key=>$shipping) {
    	if($shipping['support_cod'] == 1 && $shipping['support_no_cod'] == 1){
	      $sql = "SELECT r.region_name,r.region_id,r.parent_id
	        FROM {$ecs->table('region')} AS r,
	             {$ecs->table('area_region')} AS a,
	             {$ecs->table('shipping_area')} AS sa
	        WHERE a.region_id = r.region_id 
	        AND sa.shipping_area_id = a.shipping_area_id
	        AND sa.shipping_id = '{$shipping['shipping_id']}'
	       ";  
        $region = $db->getRow($sql);     
      }
    	$modules[$shipping['shipping_id']] = $shipping;
    	$modules[$shipping['shipping_id']]['region_name'] = $region['region_name'];
    	$modules[$shipping['shipping_id']]['region_id']   = $region['region_id'];
    	$modules[$shipping['shipping_id']]['parent_id']   = $region['parent_id'];
    	$modules[$shipping['shipping_id']]['configure'] = array();
    	$modules[$shipping['shipping_id']]['configure'][] = array('name' => 'basic_fee', 'value' => 0, 'label' => '固定费用');
    	$modules[$shipping['shipping_id']]['configure'][] = array('name' => 'basic_weight', 'value' => 0, 'label' => '起始重量(kg)');
    	$modules[$shipping['shipping_id']]['configure'][] = array('name' => 'extra_weight', 'value' => 0, 'label' => '单位续重(kg)');
    	$modules[$shipping['shipping_id']]['configure'][] = array('name' => 'extra_fee', 'value' => 0, 'label' => '续重资费(元)');
    	$modules[$shipping['shipping_id']]['configure'][] = array('name' => 'delivery_time', 'value' => 0, 'label' => '送货时间');
    }
    return;
}

class common {
    var $configure;
    function common($cfg = array()) {
        foreach ($cfg AS $key=>$val) {
            $this->configure[$val['name']] = $val['value'];
        }
    }

    /**
     * 计算订单的配送费用的函数
     * 无论多少，返回基本费用
     * @param   float   $goods_weight   商品重量
     * @param   float   $goods_amount   商品金额
     * @return  decimal
     */
    function calculate($goods_weight, $goods_amount) {
    	if (isset($this->configure['basic_fee'])){
    		$extra_weight_fee = 0;
    		if (isset($this->configure['extra_weight']) and $this->configure['extra_weight'] and $goods_weight>$this->configure['basic_weight'])
    		  $extra_weight_fee = ceil(($goods_weight-$this->configure['basic_weight'])/$this->configure['extra_weight'])*$this->configure['extra_fee'];
			return $this->configure['basic_fee']+$extra_weight_fee;
    	}
		else
			return 0;
    }

    function query($invoice_sn) {
      return false;
    }

    function calc_proxy_fee($goods_amount){
        return 0;
    }
}
?>
