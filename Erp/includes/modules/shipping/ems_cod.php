<?php

/**
 * EMS配送方式，货到付款：30元 + 订单总价1%
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$code = 'ems_cod';

/* 模块的基本信息 */
if (isset($set_modules) && $set_modules == TRUE)
{
    $sql = "SELECT * FROM {$ecs->table('shipping')} s
            LEFT JOIN {$ecs->table('carrier')} c ON s.default_carrier_id = c.carrier_id 
            WHERE shipping_code = '{$code}' ";
    $shippings = $db->getAll($sql);
    foreach ($shippings as $key=>$shipping) {
        $modules[$shipping['shipping_id']] = $shipping;
        $modules[$shipping['shipping_id']]['configure'] = array();
        $modules[$shipping['shipping_id']]['configure'][] = array('name' => 'basic_fee', 'value' => 0, 'label' => '固定费用');
        $modules[$shipping['shipping_id']]['configure'][] = array('name' => 'percent', 'value' => 0, 'label' => '手续费百分比');
        $modules[$shipping['shipping_id']]['configure'][] = array('name' => 'delivery_time', 'value' => 0, 'label' => '送货时间');
        $modules[$shipping['shipping_id']]['configure'][] = array('name' => 'max_proxy', 'value' => 0, 'label' => '最大手续费');
        $modules[$shipping['shipping_id']]['configure'][] = array('name' => 'basic_weight', 'value' => 0, 'label' => '起始重量(kg)');
        $modules[$shipping['shipping_id']]['configure'][] = array('name' => 'extra_weight', 'value' => 0, 'label' => '单位续重(kg)');
        $modules[$shipping['shipping_id']]['configure'][] = array('name' => 'extra_fee', 'value' => 0, 'label' => '续重资费(元)');
    }
    return;
}

class ems_cod {
    var $configure;
    function ems_cod($cfg = array()) {
        foreach ($cfg AS $key=>$val) {
            $this->configure[$val['name']] = $val['value'];
        }
    }


    /**
     * 计算订单的配送费用的函数
     * 货到付款：30元 + 订单总价1%
     * @param   float   $goods_weight   商品重量
     * @param   float   $goods_amount   商品金额
     * @return  decimal
     */
    function calculate($goods_weight, $goods_amount) {
        $basic_fee = 30;
        $pct = 1;
        $max_proxy = 0;
        if (isset($this->configure['basic_fee']))
        $basic_fee = $this->configure['basic_fee'];
        if (isset($this->configure['percent']))
        $pct = $this->configure['percent'];
        if (isset($this->configure['max_proxy']))
        $max_proxy = $this->configure['max_proxy'];

        if ($max_proxy > 0) {
            $proxy_fee = min(intval($goods_amount * $pct / 100), $max_proxy);
        } else {
            $proxy_fee = intval($goods_amount * $pct / 100);
        }
        /* 计算超重费用 */
        $extra_weight_fee = 0;
        if (isset($this->configure['extra_weight']) and $this->configure['extra_weight'] and $goods_weight>$this->configure['basic_weight'])
        $extra_weight_fee = ceil(($goods_weight-$this->configure['basic_weight'])/$this->configure['extra_weight'])*$this->configure['extra_fee'];

        return $basic_fee + $proxy_fee + $extra_weight_fee;
    }

    function query($invoice_sn) {
        return false;
    }

    function calc_proxy_fee($goods_amount){
        if (isset($this->configure['percent']))
        $pct = $this->configure['percent'];
        if (isset($this->configure['max_proxy']))
        $max_proxy = $this->configure['max_proxy'];
        if ($max_proxy > 0) {
            $proxy_fee = min(intval($goods_amount * $pct / 100), $max_proxy);
        } else {
            $proxy_fee = intval($goods_amount * $pct / 100);
        }
        return $proxy_fee;
    }
}
?>
