<?php

/**
 * 汇率管理
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('currency_scale');
require_once(ROOT_PATH . 'includes/cls_page.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');


$act =  // 动作
    isset($_REQUEST['act']) && 
    in_array($_REQUEST['act'], array('add', 'disable', 'create', 'filter')) 
    ? $_REQUEST['act'] 
    : null ;
$info =  // 信息
    isset($_REQUEST['info']) && trim($_REQUEST['info']) 
    ? urldecode($_REQUEST['info']) 
    : false ;


// SOAP句柄
$handle = soap_get_client('CurrencyConversionService', 'ROMEO');

// 处理提交请求
if ($act) {
    
    switch ($act) {
        // 创建币种
        case 'create':
            if (!empty($_POST['currency'])) {
	            try {
	               $obj = $_POST['currency'];
	               $obj['code'] = strtoupper($obj['code']);
	               $result = $handle->createCurrency($obj)->return;
	            } catch (SoapFault $e) {
                    $result = false;
                    $message = '币种添加失败。错误原因：'. $e->faultstring;   
	            }
	            if (!$result) {
	                $smarty->assign('message', $message);
	                break;
	            }
	            header("Location: currency_scale.php?info=". urlencode('操作成功'));
	            exit;
            } else {
                $smarty->assign('message', '没有输入完整的信息');
                break;
            }
        break;
        
        // 添加汇率
        case 'add':
            if (!empty($_POST['currencyConversion'])) {
                try {
	                $obj = $_POST['currencyConversion'];
		            $obj['createdUserByLogin'] = $_SESSION['admin_name'];
		            $result = $handle->createCurrencyConversion($obj)->return;
                } catch (SoapFault $e) {
                    $result = false;
                    $message = '汇率添加失败，错误原因: '. $e->faultstring;
                }
                if (!$result) {
                    $smarty->assign('message', $message);
                    break;
                }
                header("Location: currency_scale.php?info=". urlencode('操作成功'));
                exit;
            }
            
            break;

        // 作废汇率
        case 'disable':
            if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
                try {
                    $result = $handle->deleteCurrencyConversion(array('currencyConversionId' => $_REQUEST['id']))->return;
                } catch (SoapFault $e) {
                    $result = false;
                }
                if ($result) {
                    header("Location: currency_scale.php?info=". urlencode('已停用') );
                    exit;
                } else {
                    $smarty->assign('message', '操作失败，错误原因'. $e->faultstring);
                    break;
                }
            }
            break;   
    }    
}


/**
 * 显示
 */

$page =  // 当前页码
    is_numeric($_REQUEST['page']) && $_REQUEST['page'] > 0
    ? $_REQUEST['page'] 
    : 1 ; 

$from_code =  // 本币币种 
    isset($_REQUEST['from_code']) 
    ? $_REQUEST['from_code'] : NULL ;
    
$to_code =  // 外币币种
    isset($_REQUEST['to_code']) 
    ? $_REQUEST['to_code'] : NULL ;
    
$begin =  // 期初时间 
    isset($_REQUEST['begin']) && strtotime($_REQUEST['begin']) !== false
    ? $_REQUEST['begin'] : NULL ;
    
$end =  // 期末时间
    isset($_REQUEST['end']) && strtotime($_REQUEST['end']) !== false 
    ? $_REQUEST['end'] : NULL ;
    
$filter = array(
    'fromCurrencyCode' => $from_code,
    'toCurrencyCode' => $to_code,
    'begin' => $begin,
    'end' => $end,
);

// 信息
if ($info) {
    $smarty->assign('message', $info);
}


// 查询条件
$conditions = $filter;

// 取得已存在的汇率表
try {
    $args = array_merge($conditions, array('offset' => 0, 'limit' => 1));
    $total = $handle->getCurrencyConversionByCondition($args)->return->total;
} catch (SoapFault $e) {
    $total = 0;
}

// 构造分页
$size = 20;
$total_page = ceil($total/$size);  // 总页数
if ($page > $total_page) $page = $total_page;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $size;
$limit = $size;

try {
    $args = array_merge($conditions, array('offset' => $offset, 'limit' => $limit));
    $response = $handle->getCurrencyConversionByCondition($args)->return->resultList->CurrencyConversion;
    $currency_conversion_list = wrap_object_to_array($response);
} catch (SoapFault $e) {
    $currency_conversion_list = array();
}

// 构造分页
$pagination = new Pagination(
    $total, $size, $page, 'page', $url = 'currency_scale.php', null, $filter
);


// 取得货币列表
try {
    $currency_type_list = wrap_object_to_array($handle->getCurrency()->return->Currency);   
} catch (SoapFault $e) {
    $currency_type_list = array();
}

$smarty->assign('filter', $filter);  // 过滤条件
$smarty->assign('total', $total);  // 总数
$smarty->assign('currency_conversion_list', $currency_conversion_list);  // 汇率表
$smarty->assign('pagination', $pagination->get_simple_output());  // 分页
$smarty->assign('currency_type_list', $currency_type_list);  // 货币列表

$smarty->display('oukooext/currency_scale.htm');


