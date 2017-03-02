<?php

/**
 * 分销订单管理 
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */

die('<h1>年久失修，已停用</h1><p>可以到 先款后货未确认 之类的菜单操作。</p>'); 

define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_order_manage');
require_once (ROOT_PATH . 'admin/function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
set_include_path(get_include_path() . PATH_SEPARATOR .ROOT_PATH. 'admin/includes/Classes/');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel.php');
require_once(ROOT_PATH . 'admin/includes/Classes/PHPExcel/IOFactory.php');

$page_size_list = array('20'=>'20','50'=>'50','100'=>'100');
//分销类型
    $distribution_list = array('fenxiao' => '分销', 'zhixiao' => '直销');
// 请求
$act = 
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('filter', 'search', 'xml', 'transfer')) 
    ? $_REQUEST['act'] 
    : null ;
// 每页数据量
$page_size = 
    is_numeric($_REQUEST['size']) && in_array($_REQUEST['size'], $page_size_list)
    ? $_REQUEST['size']
    : 20;
// 页码
$page = 
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
// 是否为导出
$export = 
    (isset($_REQUEST['action']) && $_REQUEST['action'] == '导出') || $act == 'xml' || $act =='transfer'
    ? true : false ;

if(in_array($_SESSION['party_id'],array('32640','65535','120'))){
	die("请先选定具体业务组后再进行查询~");
}
//分销商列表
$distributor_list = distribution_get_distributor_list();
//分销未确认订单
if ($_REQUEST['type'] == 'fenxiao') {
    admin_priv('distribution_unconfirmed_order_search');
    //分销未确认订单中显示的分销商类别为分销
    $sql = "
        SELECT d.distributor_id, d.party_id, d.name, d.tel, d.contact, d.address, d.alipay_account, d.is_taxpayer, d.abt_print_invoice, 
            d.abt_logo_style, d.abt_change_price 
        FROM ecshop.distributor d
        LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id 
        WHERE d.status = 'NORMAL' AND md.type = 'fenxiao' AND ". party_sql('d.party_id');
    $distributor_list = $slave_db->getAll($sql);
}

// 构造查询条件
$extra_params = array();
$cond0 = ' ';
switch ($act) {
    case 'filter' :
    case 'search' :
    case null :
        if (isset($_POST['filter'])) {
            $extra_params = $filter = $_POST['filter'];
        }else{
        	$extra_params = $filter = array(
                'region_id'       => isset($_GET['region_id'])?$_GET['region_id']:null,         //这里好像是提交查询表单才到这边的
	            'distributor_id'  => isset($_GET['distributor_id']) ? $_GET['distributor_id'] : null,
	            'shipping_id'     => isset($_GET['shipping_id']) ? $_GET['shipping_id'] : null,
	            'order_status'    => isset($_GET['order_status']) ? $_GET['order_status'] : 0,  // 默认未确认
	            'pay_status'      => isset($_GET['pay_status']) ? $_GET['pay_status'] : null,
	            'shipping_status' => isset($_GET['shipping_status']) ? $_GET['shipping_status'] : null,
	        	'type'			  => isset($_GET['type']) ? $_GET['type'] : null,
	            'consignee'       => isset($_GET['consignee']) ? $_GET['consignee'] : null,
	            'start'           => isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', mktime(0,0,0, date('m'), 1, date('Y'))),
	            'end'             => isset($_GET['end']) ? $_GET['end'] : null,
	            'pay_id'          => isset($_GET['pay_id']) ? $_GET['pay_id'] : null,
	            'keywords'        => isset($_GET['keywords']) ? $_GET['keywords'] : null,
	            'time_field'      => isset($_GET['time_field']) ? $_GET['time_field'] : 'order_time',
	            'facility_id'     => isset($_GET['facility_id']) ? $_GET['facility_id'] : null,
	            'distribution_type' => isset($_REQUEST['distribution_type']) ? $_REQUEST['distribution_type'] : null,    //分销类型
	            'outer_id' 		=> isset($_GET['outer_id']) ? $_GET['outer_id'] : null,  
	        );
        }
        $start_cont = '';
        if(isset($filter['start']) && strtotime($filter['start']) != false){
        	$start_time = $filter['start'] ;
        }else{
        	$start_time = date('Y-m-d', mktime(0,0,0, date('m'), 1, date('Y')));
        }
        
        $start_cont = " and stog2.created_stamp >='{$start_time}' ";
        if(isset($filter['outer_id']) && trim($filter['outer_id']) != ''){
        	//根据outer_id进而限制商品名+数量1
        	$cond0 = " LEFT JOIN (SELECT sum(stog1.num) num,stog1.tid from ecshop.sync_taobao_order_goods stog2 
				LEFT JOIN ecshop.sync_taobao_order_goods stog1 ON stog1.tid = stog2.tid
				where ". party_sql('stog2.party_id') ." $start_cont and (stog2.outer_sku_id = '{$filter['outer_id']}' or (stog2.outer_sku_id is null and stog2.outer_iid = '{$filter['outer_id']}'))
				GROUP BY stog1.tid having sum(stog1.num)= 1)as t ON t.tid = substring_index(o.taobao_order_sn, '-', 1)  ";
        }
        $cond = _get_conditions($filter, $distribution_list);   
        break;
        
    // 将选中订单导出为制定的XML
    case 'xml' :
    // 批量转仓	
    case 'transfer' :
    	if (!empty($_POST['checked'])) {
    	   $cond = "AND o.order_id ". db_create_in($_POST['checked']);
    	}
}

// 状态列表
$order_status_list    = $GLOBALS['_CFG']['adminvars']['order_status'];    // 订单状态
$pay_status_list      = $GLOBALS['_CFG']['adminvars']['pay_status'];      // 支付状态
$shipping_status_list = $GLOBALS['_CFG']['adminvars']['shipping_status']; // 发货状态 
$shipping_all = getShippingTypes();
$shipping_list =array();
foreach($shipping_all as $key=>$shipping){
	$shipping_list[$key] = $shipping['shipping_name'];
}

$sql_start_time1 = microtime(true);
// 构造分页参数
$sql = "
    SELECT count(o.order_id) 
    FROM 
        {$ecs->table('order_info')} AS o use index (order_time,order_info_multi_index)
        LEFT JOIN distributor AS d ON d.distributor_id = o.distributor_id
        LEFT JOIN main_distributor md ON d.main_distributor_id = md.main_distributor_id 
        {$cond0}
   WHERE ". party_sql('o.party_id') ." AND o.order_type_id = 'SALE' {$cond} 
";
$sql_end_time1 = microtime(true);
$sql_sql1 = $sql;
//qlog::log("count sql = ".$sql);
$total = $slave_db->getOne($sql); // 总记录数
$total_page = ceil($total/$page_size);  // 总页数
$page = max(1, min($page, $total_page));

// 导出不分页
if ($export) {
    $offset = 0;
    $limit  = 65535;
} 
else {
    $offset = ($page - 1) * $page_size;
    $limit = $page_size;	
}

$sql_start_time2 = microtime(true);
// 查询订单
$sql = "
    SELECT 
        o.order_id, o.order_sn, o.order_time, o.order_amount, o.shipping_name, o.order_status, o.shipping_status,
        o.pay_status, o.taobao_order_sn, o.distribution_purchase_order_sn, d.name AS distributor_name,
        e.region_name,
        (SELECT MIN(action_time) FROM {$ecs->table('order_action')} WHERE order_id = o.order_id AND order_status = 1) AS confirm_time,
        o.shipping_time, o.facility_id, o.party_id, p.pay_name, o.shipping_id,
        o.address, o.mobile, o.tel, o.consignee, o.province, o.city, o.district   
    FROM 
        {$ecs->table('order_info')} AS o use index (order_time,order_info_multi_index)
        LEFT JOIN ecs_region AS  e ON e.region_id = o.province      
        LEFT JOIN distributor AS d ON d.distributor_id = o.distributor_id
        LEFT JOIN main_distributor md ON d.main_distributor_id = md.main_distributor_id
        LEFT JOIN ecs_payment AS p ON p.pay_id = o.pay_id 
        {$cond0}
    WHERE ". party_sql('o.party_id') ." AND o.order_type_id = 'SALE' {$cond}
    ORDER BY o.order_id DESC
    LIMIT {$offset}, {$limit}
";
//注意165行的匹配条件
$sql_end_time2 = microtime(true);
$sql_sql2 = $sql;
//qlog::log('查询订单 sql='.$sql);
$ref_field = $ref_orders = array();
$order_list = $slave_db->getAllRefby($sql, array('order_id'), $ref_field, $ref_orders, false);

// 取得订单的party_id
if ($order_list && $party_id = reset($order_list)) {
    $party_id = $party_id['party_id'];
}

if($_SESSION['admin_name'] == 'ytchen' ){
	$time1 = $sql_end_time1-$sql_start_time1;
	$time2 = $sql_end_time2-$sql_start_time2;
	Qlog::log("distribution_order_manage_time1 :".$time1);
	Qlog::log("distribution_order_manage_sql1: ".$sql_sql1);
	Qlog::log("distribution_order_manage_time2 :".$time2);
	Qlog::log("distribution_order_manage_sql2: ".$sql_sql2);
}

// 导出
if ($export) {
	if ($act == 'xml') {
		export_xml($order_list);
	}else if($act == 'transfer'){
        admin_priv('change_facility_shipment');
		$new_facility_id = $_POST['facility_id'];
    	$new_shipping_id = $_POST['shipping_id'];
		$cont = array();
		if(!empty($_POST['checked'])){
			foreach($order_list as $key =>$order){
				$cont[] = array("order_id"=>$order['order_id'],"new_facility_id"=>$new_facility_id,"new_shipping_id"=>$new_shipping_id);
			}
			$classBatch = new OrderBatchShipFacility();
			foreach($cont as $row){
				//检查订单是否与其他订单合并，合并订单不允许操作修改快递与转仓
				$result = $classBatch->check_data($row);
				if($result=="fail"){
					continue;
				}
			}
			$classBatch->export_data_excel('订单转仓/改快递明细');
		}
	} else {
        export_excel($order_list);
	}
} 
//查询省份
$query_sql = "select region_id,region_name from ecshop.ecs_region where region_type=1 and parent_id = 1";
$province_query = $slave_db->getAll($query_sql);

// 分页
$extra_params['size']=$filter['size']=$page_size;
$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'distribution_order_manage.php', null, $extra_params);

// 可选仓库
$facility_list = get_available_facility() + array('0' => '未指定仓库');

$smarty->assign('distribution_list', $distribution_list);   //分销类型
$smarty->assign('order_list',     $order_list);  // 订单列表
$smarty->assign('filter',         $filter);

$smarty->assign('province_query',       $province_query);         //省份列表
$smarty->assign('order_status_list',    $order_status_list);      // 发货状态 
$smarty->assign('shipping_status_list', $shipping_status_list);   // 订单状态列表
$smarty->assign('pay_status_list',      $pay_status_list);        // 支付状态列表
$smarty->assign('distributor_list',     $distributor_list);       // 分销商列表
$smarty->assign('facility_list',        $facility_list);          // 仓库列表
$smarty->assign('shipping_list',        $shipping_list);          // 配送方式列表
$smarty->assign('payment_list',         payment_list());          // 支付方式列表
$smarty->assign('page_size_list',       $page_size_list);         // 每页分页数

$smarty->assign('total', $total);  // 总数
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->display('distributor/distribution_order_manage.htm');


/**
 * 根据请求返回查询条件
 * @param $distribution_list array 分销类型
 * @return string
 */
function _get_conditions($cond, $distribution_list) {
    global $slave_db, $ecs; 
    
    $facility_list = facility_list();
    
    if (is_array($cond) && !empty($cond))
        $cond = array_filter(array_map('trim', $cond), 'strlen');
    if (!empty($cond)) {
        // 按关键字模糊搜索
        if (isset($cond['keywords'])) {
            $keywords = $slave_db->escape_string($cond['keywords']);
            
            if (preg_match("/^(\s*\d{13}[\s,]+)+$/", $keywords)) {
            	$keywords = preg_split("/[\s,]+/", $keywords);
                return " AND o.order_sn " . db_create_in($keywords);
            } 
            else {
	            
	            $order_shipment_sql ="SELECT os.ORDER_ID FROM romeo.order_shipment os " .
	            		" left join romeo.shipment s on s.shipment_id = os.SHIPMENT_ID " .
	            		" where s.tracking_number = '{$keywords}' ";
	            if($order_id = $slave_db->getOne($order_shipment_sql)){
	            	return " AND o.order_id = '{$order_id}' ";
	            }		 

                if($_SESSION['party_id']=='65638'){
                    //保税仓订单优待

                    return " AND (
                            o.order_sn = '{$keywords}'
                            OR o.taobao_order_sn = '{$keywords}'
                            OR o.shipping_name LIKE '{$keywords}%' 
                            OR o.pay_name LIKE '{$keywords}%' 
                            OR o.consignee LIKE '{$keywords}%'
                        ) ";
                }else{
	            
                    // 如果搜索关键字是数字、字母
                    if (preg_match("/^[0-9]+[-0-9a-zA-Z]*$/", $keywords)) {
                    	global $order_sn_len;//die($order_sn_len);
                    	if(strlen($keywords) > $order_sn_len) {
                    	    return " AND ( o.taobao_order_sn = '{$keywords}') ";
                    	} else {
                    	    return " AND ( o.order_sn = '{$keywords}') ";
                    	}
                    }else {
                        return " AND ( o.shipping_name LIKE '{$keywords}%' OR
                            o.pay_name LIKE '{$keywords}%' OR o.consignee LIKE '{$keywords}%' ) ";
                    }
                }
            }
        }
        // 多条件过滤
        else {
            $conditions = array();
            if ($cond['time_field'] == 'order_time') {
                if (isset($cond['start']) && strtotime($cond['start']) !== false) {
                    $conditions[] = "o.`order_time` > '". $slave_db->escape_string($cond['start']) ."'";
                }else{
                	$conditions[] = "o.`order_time` > '". $slave_db->escape_string(date('Y-m-d', mktime(0,0,0, date('m'), 1, date('Y')))) ."'";
                }
                if (isset($cond['end']) && strtotime($cond['end']) !== false) {
                    $conditions[] = "o.`order_time` < DATE_ADD('". $slave_db->escape_string($cond['end']) ."', INTERVAL 1 DAY)";
                }
            } else if ($cond['time_field'] == 'shipping_time') {
                if (isset($cond['start']) && strtotime($cond['start']) !== false) {
                    $conditions[] = "o.`shipping_time` > '". strtotime($cond['start']) ."'";
                }
                if (isset($cond['end']) && strtotime($cond['end']) !== false) {
                    list($_y, $_m, $_d) = explode('-', date('Y-m-d', strtotime($cond['end'])));  // 得到要查询的年月日
                    $_end = mktime(23, 59, 00, $_m, $_d, $_y);
                    $conditions[] = "o.`shipping_time` < '{$_end}'";
                }
            }

            if (isset($cond['distributor_id']) && $cond['distributor_id'] > -1) {
                $conditions[] = "o.`distributor_id` = '". $slave_db->escape_string($cond['distributor_id']) ."'";
            }
            if (isset($cond['shipping_id']) && $cond['shipping_id'] > -1) {
                $conditions[] = "o.`shipping_id` = '". $slave_db->escape_string($cond['shipping_id']) ."'";
            }
            if (isset($cond['order_status']) && $cond['order_status'] > -1) {
                $conditions[] = "o.`order_status` = '". $slave_db->escape_string($cond['order_status']) ."'";
            }
            if (isset($cond['pay_status']) && $cond['pay_status'] > -1) {
                $conditions[] = "o.`pay_status` = '". $slave_db->escape_string($cond['pay_status']) ."'";
            }
            if (isset($cond['shipping_status']) && $cond['shipping_status'] > -1) {
                $conditions[] = "o.`shipping_status` = '". $slave_db->escape_string($cond['shipping_status']) ."'";
            }
            if (isset($cond['consignee'])) {
                $conditions[] = "o.`consignee` = '". $slave_db->escape_string($cond['consignee']) ."'";
            }
            if (isset($cond['pay_id']) && $cond['pay_id'] > -1) {
                $conditions[] = "o.`pay_id` = '". $slave_db->escape_string($cond['pay_id']) ."'";
            }
            if (isset($cond['facility_id']) && (array_key_exists($cond['facility_id'], $facility_list) || $cond['facility_id'] == 0)) {
                $conditions[] = $cond['facility_id'] == 0 ? "o.`facility_id` = ''" : "o.`facility_id` = '{$cond['facility_id']}'";
            }
            if (isset($cond['region_id'])) {
                $conditions[] = $cond['region_id'] == -1 ? "1=1" : "o.`province` = '{$cond['region_id']}'";
            }
            //分销类型  
            if (isset($cond['distribution_type']) && array_key_exists($cond['distribution_type'], $distribution_list)) {
                $conditions[] = " md.type = '{$cond['distribution_type']}' ";
            } elseif (isset($cond['distribution_type']) && $cond['distribution_type'] == -1) {
                
            } elseif ($_REQUEST['type'] == 'fenxiao') {
                //分销未确认订单默认显示分销订单
                $conditions[] = " md.type = 'fenxiao' ";
            }
            if(isset($cond['outer_id']) && $cond['outer_id'] != ''){
            	//根据outer_id进而限制商品名+数量1
            	$conditions[] = " t.num = 1 AND o.taobao_order_sn is not null";
            }
            if (!empty($conditions)){
                return ' AND ( ' . implode(' AND ', $conditions) . ' )';
            }
        }
    }
}

	
 
 /**
 * 生成XML文件
 *
 * @param array
 */
function export_xml($rowset = array()) {
	if (empty($rowset) || !is_array($rowset)) {
		return true;
	}
	
	global $ecs, $slave_db; 
	
	require_once(ROOT_PATH . 'admin/function.php');
	
	// 取得订单的商品
	require_once(ROOT_PATH . 'includes/helper/array.php');
	$order_id_array = Helper_Array::getCols($rowset, 'order_id');
	$sql = "SELECT order_id, goods_id, style_id, goods_number FROM {$ecs->table('order_goods')} WHERE order_id ". db_create_in($order_id_array);
	$ref_fields = $ref_rowset = array();
	$slave_db->getAllRefby($sql, array('order_id'), $ref_fields, $ref_rowset, false);
	
	// 生成XML文档
    $doc = new DOMDocument("1.0", "UTF-8");
    $doc->formatOutput = true;
    
    // 根节点
    $root = $doc->appendChild(new DOMElement("ns:OUKU_SO", NULL, "http://www.ouku.com"));

    foreach ($rowset as $row) {
    	// 取得订单地址
    	require_once('function.php');
    	$address = $row['address'];
    	foreach (array('district', 'city', 'province') as $k) {
	        if ($row[$k]) {
	            $address = '['. get_region_names($row[$k]) . ']' . $address;
	        }
    	}
    	if (in_array($row['shipping_id'], array(36,47))) {
            $address .= "（请发EMS）";
    	}
    	
    	$so = $root->appendChild(new DOMElement("So"));
    	
    	// 订单头
        $so_header = $so->appendChild(new DOMElement("SoHeader")); 
        
        $so_header_elements[1] = $so_header->appendChild(new DOMElement("code"));
        $so_header_elements[1]->appendChild(new DOMText($GLOBALS['party_id']==65538?"XY".$row['order_sn']:$row['order_sn']));  // TODO 夏娃的特殊处理
        
        $so_header_elements[2] = $so_header->appendChild(new DOMElement("warehouseCode"));
        $so_header_elements[2]->appendChild(new DOMText("21"));

        $so_header_elements[3] = $so_header->appendChild(new DOMElement("customerCode"));
        $so_header_elements[3]->appendChild(new DOMText("1003"));
        
        $so_header_elements[4] = $so_header->appendChild(new DOMElement("customerName"));
        $so_header_elements[4]->appendChild(new DOMText($row['consignee']));
        
        $so_header_elements[5] = $so_header->appendChild(new DOMElement("address"));
        $so_header_elements[5]->appendChild(new DOMText($address));
        
        $so_header_elements[6] = $so_header->appendChild(new DOMElement("linkman"));
        $so_header_elements[6]->appendChild(new DOMText($row['consignee']));
        
        $so_header_elements[7] = $so_header->appendChild(new DOMElement("phone"));
        $so_header_elements[7]->appendChild(new DOMText(!empty($row['mobile']) ? $row['mobile'] : $row['tel']));
    	
        // 订单明细
        if (isset($ref_rowset['order_id'][$row['order_id']])) {
        	foreach ($ref_rowset['order_id'][$row['order_id']] as $g) {
        		$so_detail = $so->appendChild(new DOMElement("SoDetail"));
        		
                $so_detail_element[1] = $so_detail->appendChild(new DOMElement("skuCode"));
                $so_detail_element[1]->appendChild(new DOMText(encode_goods_id($g['goods_id'], $g['style_id'])));
                
                $so_detail_element[2] = $so_detail->appendChild(new DOMElement("qty"));
                $so_detail_element[2]->appendChild(new DOMText($g['goods_number']));
        	}
        }
    }

	if (!headers_sent()) {
        header('Content-Type: text/xml');
		header("Content-Disposition: attachment; filename=". date('Y-m-d') .".xml; charset=UTF-8");
		header('Cache-Control: public, must-revalidate, max-age=0');
		$doc->save("php://output");
		exit;
	}
}

/**
 * 生成excel文件
 * 
 * @param array
 */
function export_excel($rowset = array()) {
	global $order_status_list, $pay_status_list, $shipping_status_list;
	
	$facility_list = facility_list();
    $filename = "分销订单列表";
    if (!empty($rowset)) {
        set_include_path(get_include_path() . PATH_SEPARATOR . './includes/Classes/');
        require_once 'PHPExcel.php';
        require_once 'PHPExcel/IOFactory.php';
        
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);
        
        $sheet = $excel->getActiveSheet();
        
        $sheet->setCellValue('A1', "订单号");
        $sheet->setCellValue('B1', "订单金额");
        $sheet->setCellValue('C1', "下单时间");
        $sheet->setCellValue('D1', "分销商");
        $sheet->setCellValue('E1', "承运商");
        $sheet->setCellValue('F1', "订单状态");
        $sheet->setCellValue('G1', "支付方式");
        $sheet->setCellValue('H1', "淘宝订单号");
        $sheet->setCellValue('I1', "仓库");
        $sheet->setCellValue('J1', "分销采购订单号");
        $sheet->setCellValue('K1', "定制图案");
        $sheet->setCellValue('L1', "发货时间");
        
        $i = 2;
        foreach ($rowset as $row) {
            $sheet->setCellValueExplicit("A{$i}", $row['order_sn'], PHPExcel_Cell_DataType::TYPE_STRING);
            $sheet->setCellValue("B{$i}", $row['order_amount']);
            $sheet->setCellValue("C{$i}", $row['order_time']);
            $sheet->setCellValue("D{$i}", $row['distributor_name']);
            $sheet->setCellValue("E{$i}", $row['shipping_name']);
            $sheet->setCellValue("F{$i}", "{$order_status_list[$row['order_status']]}, {$pay_status_list[$row['pay_status']]}, {$shipping_status_list[$row['shipping_status']]}");
            $sheet->setCellValue("G{$i}", $row['pay_name']);
            $sheet->setCellValue("H{$i}", $row['taobao_order_sn']);
            $sheet->setCellValue("I{$i}", $facility_list[$row['facility_id']]);
            $sheet->setCellValue("J{$i}", $row['distribution_purchase_order_sn']);
            $sheet->setCellValue("K{$i}", $row['customize'] ? '是' : '否' );
            $sheet->setCellValue("L{$i}", @date('Y-m-d H:i:s', $row['shipping_time']));
            $i++;
        }
        
        // 输出
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        }
    }
}

class OrderBatchShipFacility{
	protected $success_change = array();
	//问题单
	protected $error_order = array();
	//先转仓成功，无论快递失败/成功都需要说明转仓成功
	protected $success_facility = array();
	/**
	 * 检查订单是否合并，是否待配货
	 */
	function check_data($row){
		$row['order_id'] = trim($row['order_id']);
		global $db;
		$sql = "select oi.order_sn,oi.pay_id,oi.party_id,oi.carrier_bill_id,oi.facility_id,f.FACILITY_NAME,oi.shipping_name,oi.order_status,oi.pay_status,oi.shipping_status " .
				" from ecshop.ecs_order_info oi " .
				" left join romeo.facility f on oi.facility_id = f.FACILITY_ID " .
				" where order_id = '{$row['order_id']}'";
		$order_info = $db->getRow($sql);
		$row['order_sn'] = $order_info['order_sn'];
		$row['party_id'] = $order_info['party_id'];
		$row['pay_id'] = $order_info['pay_id'];
		$row['old_facility_id'] = $order_info['facility_id'];
		$row['old_facility'] = $order_info['FACILITY_NAME'];
		$row['pay_status'] = $order_info['pay_status'];
		$row['order_status'] = $order_info['order_status'];
		$row['shipping_status'] = $order_info['shipping_status'];
		$row['carrier_bill_id'] = $order_info['carrier_bill_id'];
		$row['old_shipping'] = $order_info['shipping_name'];
		$new_shipping_id = trim($row['new_shipping_id']);
		$new_shipping_sql = "select shipping_name from ecshop.ecs_shipping where shipping_id = '{$new_shipping_id}' ";
		$new_shipping_name = $db->getOne($new_shipping_sql); 
		if(!empty($new_shipping_name)){
			$row['new_shipping'] = $new_shipping_name;
		}else{
			$row['new_shipping'] = 'XXXXXXXX';
		}
		$new_facility_id = trim($row['new_facility_id']);
		$new_facility_name = $db->getOne("select facility_name from romeo.facility where facility_id = '{$new_facility_id}'");
		if(!empty($new_facility_name)){
			$row['new_facility'] = $new_facility_name;
		}else{
			$row['new_facility'] = 'XXXXXXXX';
		}
		
		/*===对菜鸟仓的订单进行判断是否能批量转仓===*/
		if($row['pay_status']==2 and $row['order_status']==1 and $row['shipping_status']==0){
			$flag = $this->check_is_sendto_bird($row);
			if($flag=="success"){
				
			}else if($flag=="fail"){
				$row['note'] = "订单".$row['order_sn']."已经推送至菜鸟，请检查后再批量转仓";
	    		$this->error_order[$row['order_sn']] = $row;
	    		return "fail";
			}else if($flag=="not_again"){
				$row['note'] = "订单".$row['order_sn']."已经推送菜鸟并已取消，不能转仓至菜鸟仓，请检查后再操作";
	    		$this->error_order[$row['order_sn']] = $row;
	    		return "fail";
			}
			
		}
		
		if($order_info['shipping_status']!=0){
			$row['note'] = '订单不是待配货状态';
			$this->error_order[$row['order_sn']] = $row;
//			QLog::log("订单不是待配货状态--order_sn = ".$row['order_sn']);
		}else{
			if ($order_info['facility_id']==$row['new_facility_id']){
				$row['new_facility_id']='-1';
			}
			if($order_info['shipping_name']==$row['new_shipping']){
				$row['new_shipping_id']='-1';
			}
			if($row['new_facility_id']=='-1' && $row['new_shipping_id']=='-1'){
				$row['note'] = '仓库和快递均与原数据相同,无需转';
			    $this->error_order[$row['order_sn']] = $row;
//			    QLog::log("仓库和快递均与原数据相同--order_sn = ".$row['order_sn']);
			}else{
				$this->check_merge_order($row);
			}
		}
	}
	
	function check_is_sendto_bird($row){
		global $db;
		/*===测试环境===*/
//		$sql ="select * from ecshop.ecs_order_info eoi
//					INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn))) = ebi.out_biz_code
//					where eoi.facility_id in ('144624934','144624935','144624936','144624937','144676339') 
//					and eoi.order_id='{$row['order_id']}' and ebi.indicate_status in('推送成功','推送成功后取消成功','推送成功后转回ERP发货')";
					
		/*===正式环境===*/
		$sql = "select * from ecshop.ecs_order_info eoi
					INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn))) = ebi.out_biz_code
					where eoi.facility_id in ('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265')
					and eoi.order_id='{$row['order_id']}' and ebi.indicate_status in('推送成功','推送成功后取消成功','推送成功后转回ERP发货')";
		$result = $db->getRow($sql);
		if(!empty($result)){
	    	return "fail";
		}else{
			$sql = "
				SELECT eoi.facility_id,ebi.indicate_status
				from 
				ecshop.ecs_order_info eoi
				inner join ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn)))=ebi.out_biz_code
				where eoi.order_id = '{$row['order_id']}' and ebi.indicate_status not in ('等待推送','等待推送时取消订单','由ERP发货无须推送')";
		        $is_express_bird = $db->getRow($sql);
		        if(empty($is_express_bird)) {
		        	return "success";
		        }
//		        $facility = array('144624934','144624935','144624936','144624937','144676339');//测试环境
		        $facility = array('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265');//正式环境
		  
		  		//状态为【推送成功后转回ERP发货】的订单需要判断转的仓是菜鸟仓还是非菜鸟仓
		        if($is_express_bird['indicate_status']=="推送成功后转回ERP发货" ){
		        	if(in_array($row['new_facility_id'],$facility)){
		        		return "not_again"; //不能转回菜鸟仓
		        	}else{
		        		if(in_array($row['new_facility_id'],$facility)){
		        			return "not_again";  //不能转回菜鸟仓
		        		}else{
		        			return "success";
		        		}
		        	}
		        }
		}
		
		return "success";	
	}
	
	/**
	 * 如果原仓库为外包仓，判断order是否已经打标
	 * 是否为合并订单
	 */
	function check_merge_order($row){
		global $db;
		$is_merge_shipment=false;
		$handle=soap_get_client('ShipmentService');
		$response=$handle->getShipmentByPrimaryOrderId(array('primaryOrderId'=>$row['order_id']));
		if(is_object($response->return)){
			$shipment=$response->return;
			if($shipment->status=='SHIPMENT_CANCELLED'){
				$is_merge_shipment=true;
			}else{
				$response2=$handle->getOrderShipmentByShipmentId(array('shipmentId'=>$shipment->shipmentId));
				if(is_array($response2->return->OrderShipment)){
					$is_merge_shipment=true;
				}
			}
		}
		$sql = "select count(1) from ecshop.ecs_out_ship_order where order_id = '{$row['order_id']}'";
//		qlog::log("check_merge_order_sql = ".$sql."facility_id =".$row['old_facility_id']);
		if($is_merge_shipment){
			$row['note'] = '订单已被合并，发货单号为:'.$shipment->shipmentId;
			$this->error_order[$row['order_sn']] = $row;
//			QLog::log("订单已被合并--order_sn = ".$row['order_sn']);
		}else if(in_array($row['old_facility_id'],array('92718101','119603091','119603092','119603093')) 
			&& ($db->getOne($sql)!=0)){
			$row['note']='订单已打标';
			$this->error_order[$row['order_sn']] = $row;
//			QLog::log("订单在外包仓已打标--order_sn = ".$row['order_sn']);
		}else{
//			die();
			//转仓+改快递同时存在时，先转仓（检查订单是否处于未出库且新仓库库存足够状态），再修改快递（检查快递与支付方式的兼容性）
			if($row['new_facility_id']=='-1'){
				$this->check_pay_shipment($row);
			}else{
				$this->check_facility_outer($row);
			}
		}	
	}
	/**
	 * 检查支付方式与快递的兼容性
	 */
	function check_pay_shipment($row){	
		global $db;
		$sql = "SELECT *, IF(enabled=1, pay_name, CONCAT(pay_name, ' (已挂起)')) AS pay_name FROM ecshop.ecs_payment WHERE (enabled = 1 OR enabled_backend = 'Y') and pay_id = '{$row['pay_id']}' ORDER BY pay_order ";
		$payment = $db ->getRow($sql);
		$sql = "SELECT * FROM ecshop.ecs_shipping WHERE enabled = 1 and shipping_id = '{$row['new_shipping_id']}' ORDER BY shipping_code, support_cod ";
		$new_shipping = $db->getRow($sql);
		if((!$new_shipping['support_cod'] && !$new_shipping['support_no_cod'])||($payment['is_cod'] != $new_shipping['support_cod'])){
			$row['note'] = '支付方式与快递不兼容,'.$payment['pay_name'];
			$this->error_order[$row['order_sn']] = $row;
//			QLog::log("支付方式与快递不兼容--order_sn = ".$row['order_sn']);
		}else{
		   	$row['carrier_id'] = $new_shipping['default_carrier_id'];
		   	//更新快递方式
		   	$result = $this->update_shipment($row); 
		   	if($row['new_facility_id'] == '-1'){
		   		$row['new_facility']='XXXXXXXX';
		   	}	
		   	if(!$result){
		   		$row['note']='更新快递方式失败';
		   		$this->error_order[$row['order_sn']] = $row;
		   	}else{
		   		$this->success_change[$row['order_sn']] = $row;
		   	} 
		}
	} 
	
	/**
	 * 检查订单是否处于未出库状态且新仓库库存是否足够
	 */
	function check_facility_outer($row){
		global $db, $ecs;
		$facility_list = get_available_facility($row['party_id']);
		//$new_facility_id ==  '92718101' 外包仓,进而判断order_id对应淘宝同步商品数是否为1
		if(in_array($row['new_facility_id'],array('92718101','119603091','119603092','119603093'))){
			$sql = "SELECT stog.tid taobao_order_sn,SUM(stog.num) num from ecshop.ecs_order_info oi
				LEFT JOIN ecshop.sync_taobao_order_goods stog ON stog.tid = substring_index(oi.taobao_order_sn, '-', 1)
				where oi.order_id = '{$row['order_id']}' group by stog.tid";
//		qlog::log('转去外包仓查淘宝订单号+商品数sql= '.$sql);
			$stog_ans = $db->getRow($sql);
			if(empty($stog_ans['taobao_order_sn'])){
				$row['note']="未找到对应淘宝订单";
				$this->error_order[$row['order_sn']] = $row;
				$row['new_facility_id'] = '-1';
			}else if($stog_ans['num']!=1){
				$row['note'] = "订单对应淘宝同步商品数不为1";
				$this->error_order[$row['order_sn']] = $row;
				$row['new_facility_id'] = '-1';
			}	
		}
		if ($row['new_facility_id'] != '-1' && !array_key_exists($row['new_facility_id'], $facility_list)){
			$row['note'] = '业务组与仓库不匹配';
	     	$this->error_order[$row['order_sn']] = $row;
	    }else if($row['new_facility_id'] != '-1'){
	   		//获取当前order中goodsName和goodsNumber
	   		$sql = "SELECT concat(PRODUCT_ID, '_', status_id) as product_status_id, sum(goods_number) as goods_number,eog.goods_name
					from ecshop.ecs_order_goods eog
					LEFT JOIN romeo.product_mapping pm on eog.goods_id = pm.ECS_GOODS_ID and eog.style_id = pm.ECS_STYLE_ID
					where order_id = '{$row['order_id']}'
					GROUP BY PRODUCT_ID, status_id";
//			qlog::log("获取当前order中goodsName和goodsNumber sql = ".$sql);
			$order_goods_list = $db->getAll($sql);	
			$order_goods_list_temp = array();
			foreach($order_goods_list as $key => $value){
				$order_goods_list_temp[$value['product_status_id']] = $value['goods_number'];
			}
			$order_goods_list = $order_goods_list_temp;
			
	   		//新仓库goods库存
	   		$sql = "SELECT  concat(ii.product_id, '_', ii.STATUS_ID) as product_status_id, ifnull(sum(ii.QUANTITY_ON_HAND_TOTAL),0) as total_quantity
					FROM romeo.inventory_item ii
					left join romeo.product_mapping pm on ii.PRODUCT_ID = pm.product_id
					left join ecshop.ecs_order_goods eog on pm.ecs_goods_id = eog.goods_id and pm.ecs_style_id = eog.style_id and eog.status_id = ii.STATUS_ID 
					left join romeo.facility f on ii.FACILITY_ID = f.facility_id
					where eog.order_id = '{$row['order_id']}' and f.FACILITY_NAME = '{$row['new_facility']}'
					GROUP BY ii.FACILITY_ID, ii.PRODUCT_ID, ii.STATUS_ID";
//			qlog::log("新仓库goods库存sql=".$sql);
			$new_facility_goods_list = $db->getAll($sql);
			$new_facility_goods_list_temp = array();
			foreach($new_facility_goods_list as $key => $value){
				$new_facility_goods_list_temp[$value['product_status_id']] = $value['total_quantity'];
			}
			$new_facility_goods_list = $new_facility_goods_list_temp;
			//根据product_status_id判断库存是否足够
			$flag = true;
			$count_facility = count($new_facility_goods_list);
			$count_order = count($order_goods_list);
			if($count_order != $count_facility || $count_order==0) {
				$flag = false;
			}else{
				foreach ($order_goods_list as $key =>$value){
					if($value>$new_facility_goods_list[$key]){
						$flag = false;
						break;
					}
				}
			}
			
			//库存足够，进一步判断订单是否预定成功
			if($flag) {
				$this->check_reserve_success($row);
			}else{
				$row['note'] = '新仓库库存不足';
				$this->error_order[$row['order_sn']] = $row;
//				qlog::log('新仓库库存不足，进入error_order列表');
			}
	   	}
	} 
	
	/**
	 * 订单取消预定-无论订单本身是否预定成功
	 */
	function check_reserve_success($row){
		global $db;
		$flag = true;
		//加锁限制
		$lock_file_from = get_file_lock_path($row['order_id'], 'pick_merge');
		$lock_file_point_from = fopen($lock_file_from, "w+");
		if(flock($lock_file_point_from, LOCK_EX|LOCK_NB, $would_block_ref)){
			try{
				$handle=soap_get_client('InventoryService');
				$handle->cancelOrderInventoryReservation(array('orderId'=>$row['order_id']));
			} 
			catch(Exception $e){
			   	$row['note'] = "转仓时取消预定失败";	
			   	$this->error_order[$row['order_sn']] = $row;
			   	$flag = false;
//			   	QLog::log("转仓时取消预定失败，去error_order列表");
			}
	    	flock($lock_file_point_from, LOCK_UN);
	    	fclose($lock_file_point_from);
			unlink($lock_file_from);
			if(file_exists($lock_file_from)){
				QLog::log("order batch change facility lock for order_id = ".$row['order_id']." failed to release ");
			}
	    	if($flag){
//	    		$result = check_is_sendto_bird($row);
//	    		if($result == "success")
	    		{
					$arr_sql[] = "UPDATE ecshop.ecs_order_info SET facility_id = '{$row['new_facility_id']}' WHERE order_id = '{$row['order_id']}' ";
			    	$action_note = " 修改配货仓库 从 {$row['old_facility']} 修改为{$row['new_facility']}(批转)";
					$arr_sql[] = "insert into ecshop.ecs_order_action(order_id,action_user,order_status,shipping_status,pay_status,action_time,action_note) " .
								" values('{$row['order_id']}','{$_SESSION['admin_name']}',{$row['order_status']},{$row['shipping_status']},{$row['pay_status']},now(),'{$action_note}')";
			    	$db->start_transaction();
			    	foreach($arr_sql as $sql){
						if(!$db->query($sql)){
					        $flag = false;
					        break;
					    }
			    	}
					if($flag){
						$db->commit();
						if($row['new_shipping_id'] == '-1'){
					    	$row['new_shipping'] = 'XXXXXXXXX';
					    	$this->success_change[$row['order_sn']] = $row;
	//				    	Qlog::log("转仓成功，快递不用改。");
					    }else if($flag){
					    	$this->success_facility[$row['order_sn']] = $row;
					    	$this->check_pay_shipment($row);
					    }
					    /*===对已确认已付款未发货，并且配送仓为菜鸟仓的订单如果状态为【等待推送】，则需修改成【由ERP发货无须推送】===*/
						$sql = "select * from ecshop.ecs_order_info eoi
							INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn))) = ebi.out_biz_code
							where eoi.order_id='{$row['order_id']}' and ebi.indicate_status in('等待推送')";
						$result = $db->getRow($sql);
						if(!empty($sql)){
							$sql = "UPDATE ecshop.express_bird_indicate SET indicate_status ='由ERP发货无须推送',last_updated_stamp=now() where out_biz_code = '{$result['out_biz_code']}'";
							$db->query($sql);
						}
						 /*===对已确认已付款未发货，并且配送仓为菜鸟仓的订单如果状态为【由ERP发货无须推送】，则需修改成【由ERP发货无须推送】===*/
//						$sql = "select * from ecshop.ecs_order_info eoi
//							INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn))) = ebi.out_biz_code
//							where eoi.order_id='{$row['order_id']}' and ebi.indicate_status in('由ERP发货无须推送') and eoi.facility_id in ('144624934','144624935','144624936','144624937','144676339')";
						/*===正式环境===*/
						$sql = "select * from ecshop.ecs_order_info eoi
							INNER JOIN ecshop.express_bird_indicate ebi on concat_ws('',eoi.taobao_order_sn,substring(order_sn,locate('-',order_sn))) = ebi.out_biz_code
							where eoi.order_id='{$row['order_id']}' and ebi.indicate_status in('由ERP发货无须推送') and eoi.facility_id in ('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265')";
						
						$result = $db->getRow($sql);
						if(!empty($sql)){
							$sql = "UPDATE ecshop.express_bird_indicate SET indicate_status ='等待推送',created_stamp=now(),last_updated_stamp=now() where out_biz_code = '{$result['out_biz_code']}'";
							$db->query($sql);
						}
					}else{
						$db->rollback();
						$row['note'] = "转仓失败";
					    $this->error_order[$row['order_sn']] = $row;
	//				    Qlog::log("转仓失败");
					}  
	    		}
	    	}
//	    	sleep(10);
	    	
		}else{
			$row['note'] = "转仓时遇见合并/批拣";
			$this->error_order[$row['order_sn']] = $row;
			$flag = false;
			//qlog::log("转仓时遇见合并/批拣。。");
			fclose($lock_file_point_from);
		}
		
			
	} 
	/**
	 * 更新快递方式
	 */
	function update_shipment($row){
		global $db;
		$arr_sql[] = "update ecshop.ecs_order_info set shipping_id='{$row['new_shipping_id']}',shipping_name='{$row['new_shipping']}' where order_id = '{$row['order_id']}' ";
		$action_note = " 修改配送方式 从 {$row['old_shipping']} 修改为{$row['new_shipping']}(批改)"; 
		$arr_sql[] = "insert into ecshop.ecs_order_action(order_id,action_user,order_status,shipping_status,pay_status,action_time,action_note) " .
					" values('{$row['order_id']}','{$_SESSION['admin_name']}',{$row['order_status']},{$row['shipping_status']},{$row['pay_status']},now(),'{$action_note}')";
		// 更改承运商 killed by Sinri 20160105
		// $arr_sql[] = "update ecshop.ecs_carrier_bill set carrier_id='{$row['carrier_id']}' where bill_id='{$row['carrier_bill_id']}'";
		//romeo.shipment
		$arr_sql[] = "update romeo.shipment set shipment_type_id='{$row['new_shipping_id']}', carrier_id='{$row['carrier_id']}',LAST_UPDATE_TX_STAMP = NOW(),LAST_MODIFIED_BY_USER_LOGIN ='{$_SESSION['admin_name']}' WHERE PRIMARY_ORDER_ID = '{$row['order_id']}' ";
		
		$db->start_transaction();
		$flag = true;
		foreach($arr_sql as $sql){
			if(!$db->query($sql)){
		        $flag = false;
		        break;
		    }
		}
		if($flag){
			$db->commit();
		}else{
			$db->rollback();
		}  
		return $flag;
	} 
	
	/**
	 * 将异常数据输出
	 * @param string $file_name 导出excel文件名
	 */
	function export_data_excel($file_name) {
		$cell_nos = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','','X','Y','Z');
		$excel = new PHPExcel();
	    $excel->getProperties()->setTitle($file_name);
	    $sheet_no = 1;
	    if(!empty($this->error_order)){
	    	if ($sheet_no == 1 ) {
	        	$name = '$sheet';
	        	$name = $excel->getActiveSheet();
	       	} else {
	        	$name = '$sheet'.$sheet_no;
	        	$name = $excel->createSheet();
	        }
	        $sheet_no++;
	       	$name->setTitle('问题订单列表');
			$name->setCellValue('A1', "订单号");
			$name->setCellValue('B1', "发货状态");
			$name->setCellValue('C1', "原仓库");
			$name->setCellValue('D1', "新仓库");
			$name->setCellValue('E1', "原快递");
			$name->setCellValue('F1', "新快递");
			$name->setCellValue('G1', "备注");
	        $i = 2;
	        foreach ($this->error_order as $order) {
	           	$name->setCellValue("A{$i}", $order['order_sn'] );
	           	$name->setCellValue("B{$i}", $order['shipping_status']);
	           	$name->setCellValue("C{$i}", $order['old_facility']);
	           	$name->setCellValue("D{$i}", $order['new_facility']);
	           	$name->setCellValue("E{$i}", $order['old_shipping']);
	           	$name->setCellValue("F{$i}", $order['new_shipping']);
	           	$name->setCellValue("G{$i}", $order['note']);
	           	
	          	$i++;
	       	}
	    }
	    if(!empty($this->success_facility)){
			if ($sheet_no == 1 ) {
	        	$name = '$sheet';
	        	$name = $excel->getActiveSheet();
	       	} else {
	        	$name = '$sheet'.$sheet_no;
	        	$name = $excel->createSheet();
	        }
	        $sheet_no++;
	       	$name->setTitle('转仓成功，快递未知');
			$name->setCellValue('A1', "订单号");
			$name->setCellValue('B1', "原仓库");
			$name->setCellValue('C1', "新仓库");
	        $i = 2;
	        foreach ($this->success_facility as $order) {
	           	$name->setCellValue("A{$i}", $order['order_sn']);
	           	$name->setCellValue("B{$i}", $order['old_facility']);
	           	$name->setCellValue("C{$i}", $order['new_facility']);
	          	$i++;
	       	}
		}
		if(!empty($this->success_change)){
			if ($sheet_no == 1 ) {
	        	$name = '$sheet';
	        	$name = $excel->getActiveSheet();
	       	} else {
	        	$name = '$sheet'.$sheet_no;
	        	$name = $excel->createSheet();
	        }
	        $sheet_no++;
	       	$name->setTitle('成功列表');
			$name->setCellValue('A1', "订单号");
			$name->setCellValue('B1', "原仓库");
			$name->setCellValue('C1', "新仓库");
			$name->setCellValue('D1', "原快递");
			$name->setCellValue('E1', "新快递");
	        $i = 2;
	        foreach ($this->success_change as $order) {
	           	$name->setCellValue("A{$i}", $order['order_sn']);
	           	$name->setCellValue("B{$i}", $order['old_facility']);
	           	$name->setCellValue("C{$i}", $order['new_facility']);
	           	$name->setCellValue("D{$i}", $order['old_shipping']);
	           	$name->setCellValue("E{$i}", $order['new_shipping']);
	          	$i++;
	       	}
		}
	  	if (!headers_sent()) {
	   		header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$file_name.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->setOffice2003Compatibility(true);
	      	$output->save('php://output');
	      	exit;
	  	}
	}
}    
    

?>
