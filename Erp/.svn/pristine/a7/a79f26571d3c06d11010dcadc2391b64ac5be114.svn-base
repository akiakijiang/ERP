<?php

/**
 * 电教销量报表
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('cg_edu_sale_report');
require_once("function.php");
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

//  8888
die("电教销量页面查询已关闭，请前往报表统计页面拉取");
//die('2013年8月维修，请联系ERP');

list($y, $m, $d) = explode('-', date('Y-m-d'));  // 当前月日年

// 当前页码
$page = 
	is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ;
// 每页多少记录数
$page_size =
    is_numeric($_REQUEST['size']) && $_REQUEST['size'] > 0
    ? $_REQUEST['size']
    : 20 ;  
// 期初时间  (默认是当天的)
$start = 
    isset($_GET['start']) && strtotime($_GET['start']) > 0
    ? $_GET['start']
    : date('Y-m-d') ;
// 期末时间
$end = 
    isset($_GET['end']) && strtotime($_GET['end']) > 0
    ? $_GET['end']
    : date('Y-m-d') ;  
// 地区
$region = 
    isset($_GET['region']) && trim($_GET['region'])
    ? $_GET['region']
    : null ;
// 商品名
$goods_name = 
    isset($_GET['goods_name']) && trim($_GET['goods_name'])
    ? $_GET['goods_name']
    : null ;
// 关键字
$keyword = 
    isset($_GET['keyword']) && trim($_GET['keyword'])
    ? $_GET['keyword']
    : null ;
// 销售渠道
$channel = 
    isset($_GET['channel']) && trim($_GET['channel'])
    ? $_GET['channel']
    : null ;
// 查询方式
$view = 
    isset($_GET['view']) && in_array($_GET['view'], array('day', 'month'))
    ? $_GET['view']
    : null ;

// 过滤条件
$filter = array(
	'start' => $start, 'end' => $end, 'region' => $region, 'view' => $view, 
	'goods_name' => $goods_name, 'size' => $page_size, 'keyword' => $keyword, 'channel' => $channel,
);

if ($end) { 
    $end = date('Y-m-d', strtotime('+1 day', strtotime($end))); 
}
if ($view == 'month') { 
    $method = 'getEduSaleMonthInfo'; 
} else {
    $method = 'getEduSaleDailyInfo'; 
}

// 取得汇总记录
try {
    $useMaster=($_REQUEST['useMaster'] == 'YES');
    if($useMaster){
        // OLD IMPL: use Romeo Mater
        $handle = soap_get_client('EduSaleService', 'ROMEO', 'Soap_Client');
        $response = $handle->$method($_SESSION['party_id'], $start, $end, $region, $goods_name, $keyword, $channel, 0, -1);
        $total = is_numeric($response->total) ? $response->total : 0 ;
        $list = wrap_object_to_array($response->result->EduSaleRet); 
    }else{
        // New : ERP +Slave
        require_once("includes/lib_edu_sale_info.php");

        $handle=new EduSaleInfoWorker();
        $array=$handle->$method($_SESSION['party_id'], $start, $end, $region, $goods_name, $keyword, $channel, 0, -1);
        $list=array();
        foreach ($array as $array_item) {
            /*
            [0] => stdClass Object ( 
                [city] => 234 
                [date] => 2013-03-23 
                [endDate] => 2013-03-24 
                [goodsName] => 学习机9588 
                [province] => 20 
                [returnQuantity] => 0 
                [saleQuantity] => 1 
                [startDate] => 2013-03-23 
            )
            */
            $obj = new EduSaleInfoWorkerItem();
            $obj->city = $array_item['_city'];
            $obj->province = $array_item["_province"];
            $obj->date = $array_item["_order_time"];
            $obj->goodsName = $array_item['goods_name'];
            $obj->saleQuantity = $array_item['quantity'];
            $obj->returnQuantity = $array_item['return_quantity'];
            $obj->startDate=$start;
            $obj->endDate=$end;
            $list[]=$obj;
        }
        $total=count($list);
    }
} catch (SoapFault $e) {
    # print $e->faultstring;
    $total = 0;
    $list  = array();
}

//print_r($list);

// 组合地区名数据
if (!empty($list)) {
	array_walk($list, '_assec_region_name');
}

// 默认有act时 才查询数据
if(isset($_REQUEST['act'])){
	// 取得明细记录
	try {
		$handle = soap_get_client('EduSaleService', 'ROMEO');
		$args = array(
        'partyId' => $_SESSION['party_id'],
        'start' => $start,
        'end' => $end,
        'province' => $region,
        'goodsName' => $goods_name,
        'condition' => $keyword,
        'channel' => $channel,
		);
		$response = $handle->getEduSaleDetail($args);
		$items = isset($response->return->EduSaleRetDetail)
		? wrap_object_to_array($response->return->EduSaleRetDetail)
		: array() ;
	} catch (SoapFault $e) {
		# print $e->faultstring;
		$items = array();
	}
}

if (!empty($items)) {
    // 组合地区名数据
    array_walk($items, '_assec_region_name');
    
    // 取得地区名
    $_region_ids = array();
    foreach ($items as $item) {
        $_region_ids[] = $item->city;
        $_region_ids[] = $item->district;    
    }
    
    $sql = "SELECT region_id, region_name FROM {$ecs->table('region')} WHERE region_id " . db_create_in($_region_ids);
    $_region_list = Helper_Array::toHashmap((array)$db->getAll($sql), 'region_id', 'region_name');
}

// 导出
if (isset($_REQUEST['act']) && $_REQUEST['act'] == '导出') {    
    
    $filename = "电教销量报表";
	
    set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
    require_once 'PHPExcel.php';
    require_once 'PHPExcel/IOFactory.php';   	
    $excel = new PHPExcel();
    $excel->getProperties()->setTitle($filename);
    
    if (!empty($list)) {
        set_time_limit(300);   	
   	
    	// 汇总表
    	$sheet = $excel->getActiveSheet();
    	$sheet->setTitle('汇总');
    	$sheet->setCellValue('A1', "日期");
    	$sheet->setCellValue('B1', "商品名称");
    	$sheet->setCellValue('C1', "发往地");
    	$sheet->setCellValue('D1', "地区代码");
    	$sheet->setCellValue('E1', "数量/台");
    	
    	$i = 2;
    	foreach ($list as $item) {
    	    $sheet->setCellValue("A{$i}", $item->date);
    	    $sheet->setCellValue("B{$i}", $item->goodsName);
    	    $sheet->setCellValue("C{$i}", $item->region['region_name']);
    	    $sheet->setCellValue("D{$i}", $item->region['region_code']);
    	    $sheet->setCellValue("E{$i}", $item->saleQuantity);
    	    $i++;
    	}
    	
    	if (!empty($items)) {
            // 明细表
            $sheet2 = $excel->createSheet();
            $sheet2->setTitle('明细');
            $sheet2->setCellValue('A1', "下单日期");
            $sheet2->setCellValue('B1', "订单号");
            $sheet2->setCellValue('C1', "用户");
            $sheet2->setCellValue('D1', "电话");
            $sheet2->setCellValue('E1', "手机号");
            $sheet2->setCellValue('F1', "商品名称");
            $sheet2->setCellValue('G1', "数量/台");
            $sheet2->setCellValue('H1', "串号");
            
            $sheet2->setCellValue('I1', "发往地");
            $sheet2->setCellValue('J1', "地区代码");
            $sheet2->setCellValue('K1', "详细发往地（市区）");
            #$sheet2->setCellValue('L1', "地区代码");
            #$sheet2->setCellValue('M1', "地区代码");
            $sheet2->mergeCells('K1:M1');
            
            $i = 2;
            foreach ($items as $item) {
                $sheet2->setCellValue("A{$i}", $item->orderTime);
                $sheet2->setCellValueExplicit("B{$i}", $item->orderSn);
                $sheet2->setCellValueExplicit("C{$i}", $item->consignee);
                $sheet2->setCellValueExplicit("D{$i}", $item->tel);
                $sheet2->setCellValueExplicit("E{$i}", $item->mobile);
                $sheet2->setCellValueExplicit("F{$i}", $item->goodsName);
                $sheet2->setCellValue("G{$i}", $item->saleQuantity);
                $sheet2->setCellValueExplicit("H{$i}", $item->serialNumber);
                $sheet2->setCellValue("I{$i}", $item->region['region_name']);
                $sheet2->setCellValue("J{$i}", $item->region['region_code']);
                $sheet2->setCellValue("K{$i}", $_region_list[$item->city]);
                $sheet2->setCellValue("L{$i}", $_region_list[$item->district]);
                $sheet2->setCellValue("M{$i}", $item->address);
                $i++;
            }
    	} 	   	
    }
		// 输出
    	if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $output->save('php://output');
            exit;
    	}
}

// 构造分页
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size; 

// 构造分类列表数据
if (!empty($list)) {
    $list = array_slice($list, $offset, $limit);
}

// 构造分页
$pagination = new Pagination(
    $total, $page_size, $page, 'page', $url = 'edu_sale_report.php', null, $filter
);

// 视图显示方式
$view_list = array('day' => '按天', 'month' => '按月');

// 查询每页显示数列表
$page_size_list = array('20' => '20', '50' => '50', '100' => '100', '65535' => '不分页');

// 省份列表
$province_list = Helper_Array::toHashmap((array)get_regions(1, $GLOBALS['_CFG']['shop_country']), 'region_id', 'region_name');
    
// 销售渠道 
$sales_channel_list = array("DISTRIBUTION" => "分销", "TAOBAO" => "淘宝商城", "DANGDANG" => "当当网");


$smarty->assign('sales_channel_list', $sales_channel_list);  // 销售渠道
$smarty->assign('page_size_list', $page_size_list);  // 每页显示数列表
$smarty->assign('view_list', $view_list);  // 查询方式列表
$smarty->assign('filter', $filter);  // 过滤条件
$smarty->assign('list', $list);  // 数据列表
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->assign('province_list', $province_list);  // 地区列表

$smarty->display('oukooext/edu_sale_report.htm');

/**
 * 组合地域名
 */
function _assec_region_name(& $item)
{
    $region_hashmap = array(
        '2' => array('region_name' => '北京', 'region_code' => 'M08B'),
        '3' => array('region_name' => '天津', 'region_code' => 'M08F'),
        '4' => array('region_name' => '河北', 'region_code' => 'M00A'),
        '5' => array('region_name' => '山西', 'region_code' => 'M19A'),
        '6' => array('region_name' => '内蒙古', 'region_code' => 'M10D'),
        '7' => array('region_name' => '辽宁', 'region_code' => 'M16A'),
        '8' => array('region_name' => '吉林', 'region_code' => 'M18B'),
        '9' => array('region_name' => '黑龙江', 'region_code' => 'M11C'),
        '10' => array('region_name' => '上海', 'region_code' => 'M06A'),
        '11' => array('region_name' => '江苏', 'region_code' => 'M05A'),
        '12' => array('region_name' => '浙江', 'region_code' => 'M03A'),
        '13' => array('region_name' => '安徽', 'region_code' => 'M05C'),
        '14' => array('region_name' => '福州', 'region_code' => 'M12A'),
        '15' => array('region_name' => '江西', 'region_code' => 'M13A'),
        '16' => array('region_name' => '山东', 'region_code' => 'M00C'),
        '17' => array('region_name' => '河南', 'region_code' => 'M17A'),
        '18' => array('region_name' => '湖北', 'region_code' => 'M09A'),
        '19' => array('region_name' => '湖南', 'region_code' => 'M07A'),
        '20' => array('region_name' => '广东', 'region_code' => 'M02A'),
        '21' => array('region_name' => '广西', 'region_code' => 'M15C'),
        '22' => array('region_name' => '海南', 'region_code' => 'M22A'),
        '23' => array('region_name' => '重庆', 'region_code' => 'M01B'),
        '24' => array('region_name' => '四川', 'region_code' => 'M01A'),
        '25' => array('region_name' => '贵州', 'region_code' => 'M01C'),
        '26' => array('region_name' => '云南', 'region_code' => 'M02C'),
        '27' => array('region_name' => '西藏', 'region_code' => 'M01A'),
        '28' => array('region_name' => '陕西', 'region_code' => 'M10A'),
        '29' => array('region_name' => '甘肃', 'region_code' => 'M14A'),
        '30' => array('region_name' => '青海', 'region_code' => 'M14A'),
        '31' => array('region_name' => '宁夏', 'region_code' => 'M14A'),
        '32' => array('region_name' => '新疆', 'region_code' => 'M26A'),
        '3689' => array('region_name' => '台湾', 'region_code' => ''),
        '3688' => array('region_name' => '香港', 'region_code' => ''),
    );
    $city_hashmap = array(
        '115' => array('region_name' => '苏州', 'region_code' => 'M06G'),
        '111' => array('region_name' => '南京', 'region_code' => 'M05A'),
        
        '234' => array('region_name' => '深圳', 'region_code' => 'M02D'),
        '75' => array('region_name' => '大连', 'region_code' => 'M16J00'),

        '38'  => array('region_name' => '唐山',   'region_code' => 'M00E00'),
        '39'  => array('region_name' => '秦皇岛', 'region_code' => 'M00E00'),
        '44'  => array('region_name' => '承德',   'region_code' => 'M00E00'),
        '43'  => array('region_name' => '张家口', 'region_code' => 'M00E00'),
    
        '335' => array('region_name' => '兰州', 'region_code' => 'M14A'),
        '272' => array('region_name' => '成都', 'region_code' => 'M01A'),
        '153' => array('region_name' => '厦门', 'region_code' => 'M12B'),
    
        /* 把内蒙古下面的通辽，调到吉林去，吉林  代码：M18B */
        '66' => array('region_name' => '吉林', 'region_code' => 'M18B'),
    
        /* 把内蒙古下面的赤峰，调到辽宁去，辽宁  代码：M16A */
        '65' => array('region_name' => '辽宁', 'region_code' => 'M16A'),
    
         /* 把内蒙古下面的乌海，调到甘肃去，甘肃  代码：M14A */
        '64' => array('region_name' => '甘肃', 'region_code' => 'M14A'),
        
    );
    $district_hashmap = array(
        /* 把苏州下面的张家港和常熟，调到南京去，南京  代码：M05A */
        '957' => array('region_name' => '南京', 'region_code' => 'M05A'),
        '956' => array('region_name' => '南京', 'region_code' => 'M05A'),
       
        /* 把内蒙古下面的满洲里，调到黑龙江去，黑龙江  代码：M11C */
        '2112' => array('region_name' => '黑龙江', 'region_code' => 'M11C'),
    
        /* 把内蒙古下面的乌兰浩特市，调到吉林去，吉林  代码：M18B */
        '2149' => array('region_name' => '吉林', 'region_code' => 'M18B'),
        
    );

    if(isset($item->district) && array_key_exists($item->district, $district_hashmap)){
    	$item->region = $district_hashmap[$item->district];
    }elseif(array_key_exists($item->city, $city_hashmap)){
        $item->region = $city_hashmap[$item->city];
    }else{
    	$item->region = (isset($region_hashmap[$item->province]) ? $region_hashmap[$item->province] : '') ;
    }
    
    // $item->region =
    //     array_key_exists($item->city, $city_hashmap) 
    //     ? $city_hashmap[$item->city]
    //     : (isset($region_hashmap[$item->province]) ? $region_hashmap[$item->province] : '') ;
}

?>
