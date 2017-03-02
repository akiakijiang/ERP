<?php

/**
 * FRESTA业务组发货订单报表
 */

define('IN_ECS', true);
require_once('includes/init.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
//require_once (ROOT_PATH . 'includes/debug/lib_log.php');

// 请求
$act = 
    isset($_REQUEST['act']) && in_array($_REQUEST['act'], array('导出')) 
    ? $_REQUEST['act'] 
    : null ;
// 期初时间
$start = 
    isset($_REQUEST['start']) && strtotime($_REQUEST['start'])
    ? $_REQUEST['start']
    : date('Y-m-d 00:00:00', strtotime('-1 day'));
// 期末时间
$end = 
    isset($_REQUEST['end']) && strtotime($_REQUEST['end'])
    ? $_REQUEST['end']
    : date('Y-m-d 00:00:00') ;

// 过滤条件
$filter = array('start' => $start, 'end' => $end);

//Qlog::log('1:'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
// 默认不查询
if ($act) {
    $conds = _get_conditions($filter);

    // 销向订单和退回的订单
    $order_list=array();
    
    // 销向部分  已付款未取消未发货
    $sql = "
        select o.order_id, o.taobao_order_sn, o.consignee, CONCAT(' ',if(o.zipcode = '', '000000', o.zipcode)) as zipcode, concat(ifnull(p.region_name, ''), ' ', ifnull(c.region_name, ''), ' ', ifnull(d.region_name, '')) as addr1, 
    		left(o.address, 30) addr2, substring(o.address, 31) addr3, if(o.mobile='', o.tel, o.mobile) phone, 'CN' country, 4 country_code, 
    		cat.cat_name, og.goods_price, og.goods_number, g.goods_weight, og.goods_id, 
    		g.barcode, og.goods_name 
		from ecshop.ecs_order_info o
			left join ecshop.ecs_order_goods og on o.order_id = og.order_id
			left join ecshop.ecs_goods g on og.goods_id = g.goods_id
			left join ecshop.ecs_category cat on g.cat_id = cat.cat_id
			left join ecshop.ecs_region p on o.province = p.region_id
			left join ecshop.ecs_region c on o.city = c.region_id
			left join ecshop.ecs_region d on o.district = d.region_id
		where 1 {$conds}  
    ";
//    Qlog::log($sql);
    $ref_sales_order_fields = $ref_sales_order_rowset = array();
    $sales_goods_rowset = $slave_db->getAllRefby($sql, array('order_id'), $ref_sales_order_fields, $ref_sales_order_rowset, false);
    
    //Qlog::log('2.'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
    if ($sales_goods_rowset) {
    	// 组合成订单
        foreach ($ref_sales_order_rowset['order_id'] as $order_id => $item_list) {
            // 订单信息
            $order['taobao_order_sn'] = $item_list[0]['taobao_order_sn'];
            $order['consignee'] = $item_list[0]['consignee'];
            $order['zipcode'] = $item_list[0]['zipcode'];
            $order['addr1'] = $item_list[0]['addr1'];
            $order['addr2'] = $item_list[0]['addr2'];
            $order['addr3'] = $item_list[0]['addr3'];
            $order['phone'] = $item_list[0]['phone'];
            $order['country'] = 'CN';
            $order['country_code'] = '4';
            
            
            for ( $index = 0; $index < 20; $index++ ) {
            	$i = $index+1;
				if(isset($item_list[$index])){
					$cat['cat_name_'.$i] = $item_list[$index]['cat_name'];
					$cat['goods_price_'.$i] = $item_list[$index]['goods_price'];
					$cat['cat_number_'.$i] = $item_list[$index]['goods_number'];
					$cat['goods_weight_'.$i] = $item_list[$index]['goods_weight'];
				}else{
					$cat['cat_name_'.$i] = '';
					$cat['goods_price_'.$i] = '';
					$cat['cat_number_'.$i] = '';
					$cat['goods_weight_'.$i] = '';
				}
			}
            
            //商品信息
            $order_wieght = 0;
            for ( $index = 0; $index < 30; $index++ ) {
            	$i = $index+1;
				if(isset($item_list[$index])){
					$goods['goods_id_'.$i] = $item_list[$index]['goods_id'];
					$goods['barcode_'.$i] = $item_list[$index]['barcode'];
					$goods['goods_name_'.$i] = $item_list[$index]['goods_name'];
					$goods['goods_number_'.$i] = $item_list[$index]['goods_number'];
					
					$order_wieght += $item_list[$index]['goods_number']*$item_list[$index]['goods_weight'];
				}else{
					$goods['goods_id_'.$i] = '';
					$goods['barcode_'.$i] = '';
					$goods['goods_name_'.$i] = '';
					$goods['goods_number_'.$i] = '';
				}
			}
            
            $order_list[$order_id]= array_merge($order, array('order_weight'=>$order_wieght), $cat, array('ems'=>'', 'note'=>''), $goods);
            
        }
    }
}

//Qlog::log('3.'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());

unset($sales_goods_rowset);
unset($ref_sales_order_fields);
unset($ref_sales_order_rowset);

//Qlog::log('4.'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
// 导出
if ($act == '导出') {
    edu_sale_item_export($order_list);
}

//Qlog::log('5.'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());

$smarty->assign('filter', $filter);
$smarty->display('oukooext/fresta_order_list.htm');

/**
 * 查询条件
 * 
 * @return string
 */
function _get_conditions(& $filter)
{
    $conds = " AND o.pay_time >= UNIX_TIMESTAMP('{$filter['start']}') 
               AND o.pay_time < UNIX_TIMESTAMP('{$filter['end']}') ";
               
    //增加组织的限定
    $conds .= " AND o.party_id = 65642";
    
    //订单状态限制
    $conds .= " AND o.order_type_id = 'SALE' AND o.order_status != 2 AND o.pay_status = 2 AND o.shipping_time = 0 ";

    return $conds;
}


/**
 * 导出
 * 
 */
function edu_sale_item_export($order_list)
{
    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
	require_once 'PHPExcel.php';
	require_once 'PHPExcel/IOFactory.php';
//    ini_set("memory_limit","1024M");
    //Qlog::log('PHPExcel-1:'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
    
	$title = array(0 => array(
        '注文番号', '受取人名称', '郵便番号', '送付先住所1', '送付先住所2', '送付先住所3', '送付先電話番号', '発送先国コード', '内容品種別コード', '内容品総重量', 
		'商品名1', '価格1', '購入数1', '重量1', '商品名2', '価格2', '購入数2', '重量2', '商品名3', '価格3', '購入数3', '重量3', '商品名4', '価格4', '購入数4', '重量4', '商品名5', '価格5', '購入数5', '重量5', 
		'商品名6', '価格6', '購入数6', '重量6', '商品名7', '価格7', '購入数7', '重量7', '商品名8', '価格8', '購入数8', '重量8', '商品名9', '価格9', '購入数9', '重量9', '商品名10', '価格10', '購入数10', '重量10', 
		'商品名11', '価格11', '購入数11', '重量11', '商品名12', '価格12', '購入数12', '重量12', '商品名13', '価格13', '購入数13', '重量13', '商品名14', '価格14', '購入数14', '重量14', '商品名15', '価格15', '購入数15', '重量15', 
		'商品名16', '価格16', '購入数16', '重量16', '商品名17', '価格17', '購入数17', '重量17', '商品名18', '価格18', '購入数18', '重量18', '商品名19', '価格19', '購入数19', '重量19', '商品名20', '価格20', '購入数20', '重量20', 'ems番号', '備考欄_倉庫用', 
		'商品番号_1', 'JAN_1', '商品名_1日本語', '購入数_1', '商品番号_2', 'JAN_2', '商品名_2日本語', '購入数_2', '商品番号_3', 'JAN_3', '商品名_3日本語', '購入数_3', '商品番号_4', 'JAN_4', '商品名_4日本語', '購入数_4', '商品番号_5', 'JAN_5', '商品名_5日本語', '購入数_5', 
		'商品番号_6', 'JAN_6', '商品名_6日本語', '購入数_6', '商品番号_7', 'JAN_7', '商品名_7日本語', '購入数_7', '商品番号_8', 'JAN_8', '商品名_8日本語', '購入数_8', '商品番号_9', 'JAN_9', '商品名_9日本語', '購入数_9', '商品番号_10', 'JAN_10', '商品名_10日本語', '購入数_10', 
		'商品番号_11', 'JAN_11', '商品名_11日本語', '購入数_11', '商品番号_12', 'JAN_12', '商品名_12日本語', '購入数_12', '商品番号_13', 'JAN_13', '商品名_13日本語', '購入数_13', '商品番号_14', 'JAN_14', '商品名_14日本語', '購入数_14', '商品番号_15', 'JAN_15', '商品名_15日本語', '購入数_15', 
		'商品番号_16', 'JAN_16', '商品名_16日本語', '購入数_16', '商品番号_17', 'JAN_17', '商品名_17日本語', '購入数_17', '商品番号_18', 'JAN_18', '商品名_18日本語', '購入数_18', '商品番号_19', 'JAN_19', '商品名_19日本語', '購入数_19', '商品番号_20', 'JAN_20', '商品名_20日本語', '購入数_20',
		'商品番号_21', 'JAN_21', '商品名_21日本語', '購入数_21', '商品番号_22', 'JAN_22', '商品名_22日本語', '購入数_22', '商品番号_23', 'JAN_23', '商品名_23日本語', '購入数_23', '商品番号_24', 'JAN_24', '商品名_24日本語', '購入数_24', '商品番号_25', 'JAN_25', '商品名_25日本語', '購入数_25', 
		'商品番号_26', 'JAN_26', '商品名_26日本語', '購入数_26', '商品番号_27', 'JAN_27', '商品名_27日本語', '購入数_27', '商品番号_28', 'JAN_28', '商品名_28日本語', '購入数_28', '商品番号_29', 'JAN_29', '商品名_29日本語', '購入数_29', '商品番号_30', 'JAN_30', '商品名_30日本語', '購入数_30'
    ));
    $type = array(0 => 'string',);
    
	$filename = "FRESTA业务组发货订单报表.xlsx";
    
    //Qlog::log('PHPExcel-2:'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
    
    $excel = new PHPExcel();
    $sheet = $excel->getActiveSheet();
    $sheet->fromArray($title);
    $csv_orders = array_map('array_values', $order_list);
    
    //Qlog::log('PHPExcel-3:'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
    
//    unset($order_list);
    
    if (!empty($csv_orders)) {
        $sheet->fromArray($csv_orders, null, 1, $type);
    }
    //Qlog::log('PHPExcel-4:'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
    
    unset($order_list);
    unset($csv_orders);
    //Qlog::log('PHPExcel-5:'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
    
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
    $output->setOffice2003Compatibility(true);
    
//   //Qlog::log('PHPExcel-6:'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
    
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $output->save('php://output');
    
    //Qlog::log('PHPExcel-7:'.date('Y-m-d H:i:s',time()).'--'.memory_get_usage());
    exit();
}

?>
