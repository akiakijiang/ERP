<?php 
define('IN_ECS', true);

define('ROOT_PATH', preg_replace('/admin(.*)/i', '', str_replace('\\', '/', __FILE__)));
require(ROOT_PATH. 'includes/master_init.php');
$p = $_REQUEST['p'];

if ($p != 'OUKOO_EMSCODCONV_PASS') {
    header("Status: 404 Not Found",false,404);
    die();
}
$party_id = $_REQUEST['party_id'];
if (!isset($party_id)) {
    $party_id = 1;
}

$city_map = array(
    '黄山'                        =>        '黄山市',
    '遵义'                        =>        '遵义市',
    '荷泽'                        =>        '荷 泽',
    '百色'                        =>        '百色市',
);
$district_map = array(
    '芜湖'                        =>        '芜湖县',
    '铜陵'                        =>        '铜陵县',
    '承德'                        =>        '承德县',
    '井陉县'                      =>        '井陉',
    '衡阳'                        =>        '衡阳县',
    '邵阳'                        =>        '邵阳县',
    '湘潭'                        =>        '湘潭县',
    '株洲'                        =>        '株洲县',
    '安阳'                        =>        '安阳县',
    '开封'                        =>        '开封县',
    '许昌'                        =>        '许昌县',
    '通化'                        =>        '通化县',
    '上饶'                        =>        '上饶县',
    '南昌'                        =>        '南昌县',
    '九江'                        =>        '九江县',
    '本溪满族自治县'              =>        '本溪县',
    '朝阳'                        =>        '朝阳县',
    '抚顺'                        =>        '抚顺县',
    '阜新蒙古族自治县'            =>        '阜新县',
    '辽阳'                        =>        '辽阳县',
    '东乡族自治县'                =>        '东 乡',
    '商南县'                      =>        '商南',
);
$sql =
    "SELECT o.order_sn, o.consignee, o.address, o.tel, o.mobile, o.order_amount, 
        cb.bill_no, o.zipcode, o.province, o.city, o.district,
        (SELECT mp.mask_phone_no FROM callcenter_mask_phone mp
         WHERE mp.order_sn = o.order_sn AND mp.no_status != 'S' AND o.tel = mp.cus_phone_no_all
          AND mp.type = 'tel' LIMIT 1) AS mask_tel,
        (SELECT mp.mask_phone_no FROM callcenter_mask_phone mp
         WHERE mp.order_sn = o.order_sn AND mp.no_status != 'S' AND o.mobile = mp.cus_phone_no_all
          AND mp.type = 'mobile' LIMIT 1) AS mask_mobile
      FROM {$ecs->table('order_info')} AS o 
      LEFT JOIN {$ecs->table('carrier_bill')} AS cb ON o.carrier_bill_id = cb.bill_id 
      LEFT JOIN {$ecs->table('payment')} p ON p.pay_id = o.pay_id 
      WHERE order_status = 1 AND ". party_sql('o.party_id', $party_id) ."
        AND NOT order_sn like '%-t' 
        AND (pay_code = 'cod' OR pay_status = 2 OR order_sn LIKE '%-h') 
        AND carrier_id = '14' AND shipping_status = '8' 
      GROUP BY o.order_sn ";
$orders = $db->getAll($sql);
$ems_list = array();
foreach ($orders as $key => $order) {
    $sql = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['province']}'";
    $order['province'] = $db->getOne($sql);
    $sql = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['city']}'";
    $order['city'] = $db->getOne($sql);
    if (key_exists($order['city'], $city_map)) {
        $order['city'] = $city_map[$order['city']];
    }
    $sql = "SELECT region_name FROM {$ecs->table('region')} WHERE region_id = '{$order['district']}'";
    $order['district'] = $db->getOne($sql);
    if (key_exists($order['district'], $district_map)) {
        $order['district'] = $district_map[$order['district']];
    }
    // 获取屏蔽号码
    get_mask_phone($order);
    
    $ems_list[] = join("\t", $order);
}
$ems = join("\n", $ems_list);
echo $ems;

?>