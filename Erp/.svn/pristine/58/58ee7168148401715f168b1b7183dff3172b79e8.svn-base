<?php 

/**
 * 分销商套餐管理
 * 
 * @author yxiang@oukoo.com
 * @copyright 2009 ouku.com 
 */
 
define('IN_ECS', true);
require_once('includes/init.php');
require_once('distribution.inc.php');
admin_priv('distribution_group_goods');
require_once(ROOT_PATH . 'includes/lib_order.php');
require_once(ROOT_PATH . 'includes/cls_json.php');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');

require_once(ROOT_PATH. 'includes/debug/lib_log.php');
$request = // 请求 
    isset($_REQUEST['request']) && 
    in_array($_REQUEST['request'], array('ajax')) 
    ? $_REQUEST['request'] 
    : null ;
$act =     // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add_goods', 'search_goods', 'add', 'update', 'search','exportTC')) 
    ? $_REQUEST['act'] 
    : null ;
$info =    // 返回的信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;
$page =    // 分页
    is_numeric($_REQUEST['page']) && ($_REQUEST['page'] > 0) 
    ? $_REQUEST['page'] 
    : 1 ;
$keyword = // 查询关键字
    isset($_REQUEST['keyword']) && trim($_REQUEST['keyword']) 
    ? urldecode($_REQUEST['keyword']) 
    : '' ;

/*
 * 处理ajax请求
 */
if ($request == 'ajax')
{
    $json = new JSON;
    switch ($act) 
    {
        // 添加商品
        case 'add_goods':
            $goods = distribution_get_goods($_POST['goods_id'], $_POST['style_id']);
            if ($goods) 
                print $json->encode($goods);
            else
                print $json->encode(array('error' => '商品不存在,或该颜色已经下架'));
        break;
        
        // 搜索商品
        case 'search_goods':
            $limit = (isset($_POST['limit']) && is_numeric($_POST['limit'])) ? $_POST['limit'] : 40 ;
            print $json->encode(distribution_get_goods_list(NULL, NULL, $_POST['q'], $limit));  
        break;
    }

    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'GET' && !empty($_GET) && $act == 'exportTC') {

        set_include_path(ROOT_PATH.'admin/includes/Classes/');
        require_once ('PHPExcel.php');
        require_once ('PHPExcel/IOFactory.php');
	       	       
        $filename = "套餐";
        $excel = new PHPExcel();
        $excel->getProperties()->setTitle($filename);
        $sheet = $excel->getActiveSheet();
        $sheet->setCellValue('A1', "套餐编号");
        $sheet->setCellValue('B1', "套餐名");
        $sheet->setCellValue('C1', "套餐价");
        $sheet->setCellValue('D1', "商品名称");
        $sheet->setCellValue('E1', "商家编码");
        $sheet->setCellValue('F1', "商品单价");
        $sheet->setCellValue('G1', "运费");
        $sheet->setCellValue('H1', "商品数量");
        $i=2;
        $group_list = $db->getAll("SELECT * FROM ecshop.distribution_group_goods  dgg
                                   LEFT JOIN ecshop.distribution_group_goods_item dggi on dgg.group_id = dggi.group_id
                                   WHERE party_id = '{$_SESSION['party_id']}' AND STATUS = 'OK' ");
        
         foreach ($group_list as $item) {
         	$sheet->setCellValue("A{$i}", $item['code']);
            $sheet->setCellValue("B{$i}", $item['name']);
            $sheet->setCellValue("C{$i}", $item['amount']);
            $sheet->setCellValue("D{$i}", $item['goods_name']);
            if(!empty($item['style_id'])){
            	$sheet->setCellValue("E{$i}", $item['goods_id'].'-'.$item['style_id']);
            }else{
             	$sheet->setCellValue("E{$i}", $item['goods_id']);           	
            }
            $sheet->setCellValue("F{$i}", $item['price']);
            $sheet->setCellValue("G{$i}", $item['shipping_fee']);
            $sheet->setCellValue("H{$i}", $item['goods_number']);
            $i++;
         }
        
        
        if (!headers_sent()) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"');
            header('Cache-Control: max-age=0');
            $output = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
            $output->save('php://output');
            exit;
        }   
}


/*
 * 处理post请求
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST) && $act) {

    switch ($act) {        
        /* 生成套餐 */
        case 'add' :
            // 检查是否选择了套餐商品
            if (empty($_POST['goods']) || !is_array($_POST['goods'])) {
                $smarty->assign('message', '没有选择套餐商品');
                break;
            }
            
            // 保存套餐
            $group = $_POST['group'];
            $total_price = $group['amount']*1;
            
            // 检查提交的套餐商品金额
            $goods_list = array();
            $price_total = 0;

            foreach ($_POST['goods'] as $item) {
                if (floatval($item['price'] < 0)) {
                    $smarty->assign('message', '输入的套餐金额不合理');
                    break 2;
                }
                
            	if(floatval($item['price'] == 0)&& $group['auto'] == 'yes'){
                	$smarty->assign('message', '自动分配商品单价不能为0');
                    break 2;
                }
                
                $g = distribution_get_goods($item['goods_id'], $item['style_id']);
                if ($g) {
                    $goods_list[] = array_merge($g, $item);
                } else {
                    $smarty->assign('message', '有商品不存在');
                    break 2;
                }
                $price_total += $item['price'] * $item['number'];
            }
            
            if(bccomp($total_price, $price_total, 2) != 0 && $group['auto'] == 'no')
            {
            	$smarty->assign('message','商品单价之和与套餐价格不等');
            	break;
           	} 


            
            $group['created'] = $group['updated'] = date('Y-m-d H:i:s');  // 更新创建时间
            $group['party_id'] = $_SESSION['party_id'];
            do {
                $group['code'] = 'TC-'.get_order_sn();  // 生成套餐编码
                $db->autoExecute('distribution_group_goods', $group, 'INSERT', '', 'SILENT');
                $error_no = $GLOBALS['db']->errno();
                if ($error_no > 0 && $error_no != 1062) { die($db->errorMsg()); }
            } while ($error_no == 1062);
            $group_id = $db->insert_id();
            
            if($group['auto'] == 'no'){   
             	  
	            // 保存套餐明细
	            $segment = array();

	            foreach ($goods_list as $item) {
	            	$item['goods_name'] = mysql_real_escape_string($item['goods_name']);
	                $segment[] = "('{$group_id}', '{$item['goods_id']}', '{$item['style_id']}', '{$item['goods_name']}', '{$item['number']}', '{$item['price']}')";
	            }
            }else if($group['auto'] == 'yes'){
            	$segment = array();
				$total_amount = 0;
           		foreach ($goods_list as $item) {
					$total_amount = $total_amount + $item['number'] * $item['price'];
           		}	
           		$goods_list = Helper_Array::sortByMultiCols($goods_list,array('goods_id' => SORT_ASC,'style_id' => SORT_ASC));    
           		$item_last = array_pop($goods_list);
           		$item_temp = 0;
           		foreach ($goods_list as $item){
           			//total_price:用户输入的套餐价格， total_amount：由商品原价算得的总价格
           			//这里按商品原价的比例求得按现在的套餐价格每个商品的单价
           			$item_total_price = $total_price *  $item['price'] * $item['number'] / $total_amount;
           			$item_total_price = round($item_total_price , 2);
           			$item_temp = $item_temp + $item_total_price;
           			$item_price =  $total_price *  $item['price'] / $total_amount;
           			$item_price = round($item_price , 2);
           			for($a = 1 ; $a < $item['number'] ; $a ++){
           				$segment[] = "('{$group_id}', '{$item['goods_id']}', '{$item['style_id']}',  '{$item['goods_name']}', '1','$item_price')";
           			}
           			$item_price = $item_total_price - ($item['number'] - 1) * $item_price;
           			$segment[] = "('{$group_id}', '{$item['goods_id']}', '{$item['style_id']}', '{$item['goods_name']}', '1', '{$item_price}')";
           		}
           		$item_total_price = $total_price - $item_temp;
           		$item_price = round($item_total_price / $item_last['number'] , 2);
           		for($a = 1 ; $a < $item_last['number'] ; $a ++){
           			$segment[] = "('{$group_id}', '{$item_last['goods_id']}', '{$item_last['style_id']}', '{$item_last['goods_name']}', '1', '{$item_price}')";
           		}
           		$item_price = $item_total_price - ($item_last['number'] - 1) * $item_price;
           		$segment[] = "('{$group_id}', '{$item_last['goods_id']}', '{$item_last['style_id']}', '{$item_last['goods_name']}', '1', '{$item_price}')";
            }
            
            
            $sql = "INSERT INTO distribution_group_goods_item (group_id, goods_id, style_id, goods_name, goods_number, price) VALUES " . join(', ', $segment);
            $db->query($sql);
            //记录操作
            $record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            			"VALUES ('{$_SESSION['admin_name']}', 'add', NOW(), 'distribution_group_goods.php', 'group_detail_form', '".mysql_real_escape_string($sql)."', '{$_POST['comment']}')";
            $db->query($record_sql);
	      
	        header("Location: distribution_group_goods.php?info=". urlencode('添加套餐成功')); exit;
         
            
        break;
        
        
        /* 编辑套餐 */
        case 'update' :
        
//            admin_priv('distribution_group_goods_edit');
            // 检查是否选择了套餐商品
            if (empty($_POST['goods']) || !is_array($_POST['goods'])) {
                $smarty->assign('message', '没有选择套餐商品');
                break;
            }
            
            // 检查提交的套餐商品金额
            $goods_list = array();
            $amount = 0;
            $n = 0;
            foreach ($_POST['goods'] as $item) {
                if (floatval($item['price'] < 0)) {
                    $smarty->assign('message', '输入的套餐金额不合理');
                    break 2;
                }
                
                if (floatval($item['shipping_fee'] < 0)) {
                    $smarty->assign('message', '输入的运费不合理');
                    break 2;
                }
                
                $g = distribution_get_goods($item['goods_id'], $item['style_id']);
               
                if ($g) {
                    $goods_list["{$g['goods_id']}_{$g['style_id']}_{$n}"] = array_merge($g, $item);
					$n++;
                } else {
                    $smarty->assign('message', '有商品不存在');
                    break 2;
                }
                $amount += $item['price'] * $item['number'];
            }
            
            $group = $_POST['group'];
            $total_price = $group['amount']*1;
            if(bccomp($amount, $total_price, 2) != 0)
            {
	           	$smarty->assign('message','商品单价之和与套餐价格不等');
	           	break;
         	}       
            
   
            // 检查提交的套餐是否存在
            $group = distribution_get_group_goods($_POST['group']['group_id']);
            if (!$group) {
                $smarty->assign('message', '要编辑的套餐不存在');
                break;                
            }

            // 套餐的总金额
            //$amount = array_sum(array_map('floatval', Helper_Array::getCols($_POST['goods'], 'price')));  // 套餐价
            
            // 更新套餐
            $update_sql = "UPDATE distribution_group_goods SET name = '{$_POST['group']['name']}', amount = '{$amount}', valid_from= '{$_POST['group']['valid_from']}', updated = NOW() WHERE group_id = {$group['group_id']}";
            $db->query($update_sql);
            
            // 更新套餐明细 
            $delete_sql = "DELETE FROM distribution_group_goods_item WHERE group_id = '{$group['group_id']}'";
            $db->query($delete_sql);
            $segment = array();
            foreach ($goods_list as $item) {
                $item['goods_name'] = mysql_real_escape_string($item['goods_name']); 
                $segment[] = "('{$group['group_id']}', '{$item['goods_id']}', '{$item['style_id']}', 
                               '{$item['goods_name']}', '{$item['number']}', '{$item['price']}', 
                                '{$item['shipping_fee']}')";
            }
            $sql = "INSERT INTO distribution_group_goods_item "
                  ." (group_id, goods_id, style_id, goods_name, goods_number, price, shipping_fee) "
                  ." VALUES " . join(', ', $segment);
            $db->query($sql);
            //记录操作
            $record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            			"VALUES ('{$_SESSION['admin_name']}', 'update', NOW(), 'distribution_group_goods.php', 'group_detail_form', 'update_sql:".mysql_real_escape_string($update_sql)."     delete_sql:".mysql_real_escape_string($delete_sql)."     insert_sql:".mysql_real_escape_string($sql)."', '{$_POST['comment']}')";
            $db->query($record_sql);
            header("Location: distribution_group_goods.php?info=". urlencode('更新套餐成功')); exit;
            
        break;
              
    }  
}

// 信息
if ($info) {
    $smarty->assign('message', $info);
}

// 编辑或者删除模式
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
	
	
	if(empty($_GET['work'])){
	    $group = distribution_get_group_goods($_GET['id']);
        if ($group) {
            $smarty->assign('update', $group);  
        } else {
            $smarty->assign('message', '选择的套餐不存在');
        }
	}else if ($_GET['work']  == 'delete'){
//		 admin_priv('distribution_group_goods_edit');
		 $sql = "UPDATE distribution_group_goods SET STATUS = 'DELETE',updated = NOW() WHERE group_id = ".$_GET['id'];
		 
         $db->query($sql);            
         //记录操作
         $record_sql = "INSERT INTO risky_actions (admin_user, type, timestamp, php_location, form_name, `sql`, comment) " .
            			"VALUES ('{$_SESSION['admin_name']}', 'delete', NOW(), 'distribution_group_goods.php', 'groups_table', '".mysql_real_escape_string($sql)."', '{$_GET['comment']}')";
         $db->query($record_sql);
         header("Location: distribution_group_goods.php?info=". urlencode('套餐删除成功')); exit;
	}
   
}

// 查询条件
$conditions = NULL ;
$extra_params = array();
$filter = array();
if ($keyword) {
    $filter['keyword'] = $keyword;
    $extra_params['keyword'] = urlencode($keyword);
    $keyword = mysql_like_quote($keyword);
    $conditions = " AS g LEFT JOIN distribution_group_goods_item as i ON i.group_id = g.group_id 
    	WHERE (g.code LIKE '%{$keyword}%' OR g.name LIKE '%{$keyword}%' OR i.goods_name LIKE '%{$keyword}%') AND status = 'OK' and ".party_sql('g.party_id')." GROUP BY g.group_id
	";
}

// 总记录数
if($conditions == NULL){
    $total = $db->getOne("SELECT COUNT(*) FROM distribution_group_goods WHERE STATUS = 'OK' and " . party_sql('party_id'). "{$conditions}");
} else {
    $total = $db->getOne("select count(*) from (SELECT COUNT(*) FROM distribution_group_goods {$conditions}) t");
}

// 分页 
$page_size = 15;  // 每页数量
$total_page = ceil($total/$page_size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $page_size;
$limit = $page_size;

// 套餐列表
if($conditions == NULL){
	$group_list = $db->getAll("SELECT * FROM distribution_group_goods WHERE ".party_sql('distribution_group_goods.party_id')."AND STATUS = 'OK'"." LIMIT {$offset}, {$limit}");
}
else{
	$group_list = $db->getAll("SELECT * FROM distribution_group_goods {$conditions}"." LIMIT {$offset}, {$limit}");
	}
$pagination = new Pagination($total, $page_size, $page, 'page', $url = 'distribution_group_goods.php', null, $extra_params);
$smarty->assign('total', $total);  // 总数
$smarty->assign('group_list', $group_list);  // 套餐列表
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->assign('filter', $filter);  // 查询条件
$smarty->display('distributor/distribution_group_goods.htm');
