<?php
/**
 * 计算理论运费
 * @author :zwsun
 * @copyright oukoo
*/
define('IN_ECS', true);

require('includes/init.php');
require(ROOT_PATH. 'includes/lib_order.php');
admin_priv('analyze_theoretical_shipping_fee');
party_priv(PARTY_OUKU);
$act = $_REQUEST['act'];

if ($act == 'export') {
    $start = $_REQUEST['startDate'];
    $end = $_REQUEST['endDate'];
    
    if ((strtotime($start) <= 0) || (strtotime($start) <= 0)) {
        sys_msg('请选择正确的起始结束日期');
    }
    @set_time_limit(300);
    $end = date('Y-m-d', strtotime('+1 day', strtotime($end)));
    
    $sql = "
    select 
        o.order_sn, province.region_name as provice_name, city.region_name as city_name, 
        district.region_name as district_name,
        o.shipping_fee, o.shipping_proxy_fee, o.shipping_name, o.goods_amount,
        o.order_time, group_concat(og.goods_name separator '/' ) as goods_name, 
        o.order_amount, o.order_status, 0 as receivable_shipping_fee,
        sum(og.goods_number * ifnull(g.goods_weight, 0)) as weight, o.shipping_id,
        o.country, o.province, o.city, o.district
    from
       ecs_order_info o left join ecs_region province on o.province = province.region_id
       left join ecs_region city on o.city = city.region_id
       left join ecs_region district on o.district = district.region_id     
       left join ecs_order_goods og on o.order_id = og.order_id       
       left join ecs_goods g on og.goods_id = g.goods_id   
    where
       o.party_id in (1, 4) and 
       o.order_type_id = 'SALE' and
       (o.shipping_fee = '0' or o.shipping_fee is null) and 
       (o.order_time >= '{$start}' and o.order_time <= '{$end}') 
    group by o.order_id;
    ";
    
    $orders = $slave_db->getAll($sql);
    
    if ($orders) {
        // 构造查询区域对应运费配置的条件
        $_conditions = array();
        foreach ($orders as $order) {
            $shipping_id = $order['shipping_id'];
            $_conditions[$shipping_id][] = $order['country'];
            $_conditions[$shipping_id][] = $order['province'];
            $_conditions[$shipping_id][] = $order['city'];
            $_conditions[$shipping_id][] = $order['district'];
        }
        
        $conditions = array();
        foreach ($_conditions as $shipping_id => $regions) {
            $regions_condition = db_create_in(array_unique($regions), "r.region_id");
            $conditions[] = "( s.shipping_id = $shipping_id AND $regions_condition )";
        }
        $conditions_str = join(' OR ', $conditions);
        
        $sql = "SELECT r.region_id, s.shipping_id, s.shipping_code, s.shipping_name, a.configure
                FROM {$GLOBALS['ecs']->table('shipping')}  AS s, 
                     {$GLOBALS['ecs']->table('shipping_area')}  AS a, 
                     {$GLOBALS['ecs']->table('area_region')}  AS r 
                WHERE r.shipping_area_id = a.shipping_area_id
                    AND a.shipping_id = s.shipping_id 
                    AND ($conditions_str)
                ";
        // 统一查询出来。方便后面订单使用
        $_shipping_configures = $slave_db->getAll($sql);
        $shipping_configures = array();
        foreach ($_shipping_configures as $configure) {
            $shipping_configures[$configure['shipping_id']][$configure['region_id']] = 
                array('configure' =>$configure['configure'], 
                      'shipping_code' => $configure['shipping_code']
                     );
        }
        
        foreach ($orders as $key => $order) {
            $country = $order['country'];
            $province = $order['province'];
            $city = $order['city'];
            $district = $order['district'];
            $shipping_id = $order['shipping_id'];
            
            $orders[$key]['order_status'] = 
                $_CFG['adminvars']['order_status'][$order['order_status']];
            
            // 按照 $district $city $province $country 的优先次序获得配置
            $shipping_configure = $shipping_configures[$shipping_id][$district];
            if (empty($shipping_configure)) {
                $shipping_configure = $shipping_configures[$shipping_id][$city];
            }
            
            if (empty($shipping_configure)) {
                $shipping_configure = $shipping_configures[$shipping_id][$province];
            }
            
            if (empty($shipping_configure)) {
                $shipping_configure = $shipping_configures[$shipping_id][$country];
            }
            
            if (!$shipping_configure) {
                $orders[$key]['receivable_shipping_fee'] = "can't cal";
            } else {
                $shipping_fee = shipping_fee(
                    $shipping_configure['shipping_code'], unserialize($shipping_configure['configure']), 
                    $order['weight'], $order['goods_amount']
                );
                $orders[$key]['receivable_shipping_fee'] = $shipping_fee;
            }
                
            /*$sql = 'SELECT s.shipping_id, s.shipping_code, s.shipping_name, a.configure ' .
                      'FROM ' . $GLOBALS['ecs']->table('shipping') . ' AS s, ' .
                      $GLOBALS['ecs']->table('shipping_area') . ' AS a, ' .
                      $GLOBALS['ecs']->table('area_region') . ' AS r ' .
                      'WHERE r.shipping_area_id = a.shipping_area_id
                        AND a.shipping_id = s.shipping_id 
                        AND r.region_id ' . db_create_in(array($country, $province, $city, $district)) .
                      " AND s.shipping_id=$shipping_id ";
            
            $shipping = $slave_db->getRow($sql);
            
            if (!$shipping) {
                $orders[$key]['receivable_shipping_fee'] = "can't cal";
            } else {
                $shipping_fee = shipping_fee(
                    $shipping['shipping_code'], unserialize($shipping['configure']), 
                    $order['weight'], $order['goods_amount']
                );
                $orders[$key]['receivable_shipping_fee'] = $shipping_fee;
            }
            */
            
        }
    }
    
    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require 'PHPExcel.php';
    require 'PHPExcel/IOFactory.php';
    
    $title = array(0 => array(
        '订单号', '省', '市', '区',
        '运费', '手续费', '快递方式', '商品金额', 
        '订单时间', '订单商品','订单金额', '订单状态', '理论运费',
        '商品重量', 'shipping_id', 'country', 'province', 'city', 'district',
    ));
    $type = array(0 => 'string',);
    
    $filename = "{$start}至{$_REQUEST['endDate']}_理论运费_" .date("Y-m-d").".xlsx";
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    //$sheet->setTitle("发票");
    
    $sheet->fromArray($title);
    $orders = array_map('array_values', $orders);
    if (!empty($orders)) {
        $sheet->fromArray($orders, null, 1, $type);
    }
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    exit();
} {
    $smarty->display('oukooext/analyze_theoretical_shipping_fee.htm');

}











