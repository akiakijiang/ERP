<?php
/**
 * 工单列表
 */
define('IN_ECS', true);
require_once('../includes/init.php');
require_once('../../RomeoApi/lib_dispatchlist.php');
include_once('../function.php');

admin_priv('dispatch_list_purchase', 'dispatch_list_customer_service');
party_priv('65542');

function getProviderList($list) {
	global $db;
	$providerIds = array();
	foreach ($list as &$dispatchList) {
		$providerIds[] = (int)$dispatchList->providerId;
	}
	$providerIds = array_unique($providerIds);
	
	$sql = "select provider_id, provider_name " .
			" from ecshop.ecs_provider " .
			" where provider_id in " .
			"('" . implode("','", $providerIds) . "')";
	
	// print $sql;
	
	$providerNames = array();
	$result=$db->getAll($sql);
	// print_r($result);
	foreach ($result as $item) {
		$providerNames[$item['provider_id']] = $item['provider_name'];
	}
	return $providerNames;
}

function get_provider_jjs($args)
{
    if (!empty($args)) extract($args);

    if (!is_null($q))
    {
        // 如果关键字为字母, 则表示按code搜索
        if (preg_match("/^[a-z0-9]+$/i", $q))
        {
            $keyword = mysql_like_quote($q);
            $conditions = "AND `provider_code` LIKE '%{$keyword}%'";
        }
        // 否则按供应商名搜索
        else
        {
            $keyword = mysql_like_quote($q);
            $conditions = "AND `provider_name` LIKE '%{$keyword}%'";
        }
    }

    $sql = "
        SELECT p.`provider_id`, p.`provider_name`, p.`provider_code`, p.`provider_order_type` 
        FROM {$GLOBALS['ecs']->table('provider')} p
        	inner join ecshop.ecs_provider_category pc on pc.provider_id = p.provider_id
        WHERE pc.cat_id = 2334 and p.`provider_status` = 1 {$conditions} 
        ORDER BY p.`order` DESC, p.`provider_id`
    ";    

    return $GLOBALS['db']->getAll($sql);
}

$act = $_REQUEST['act'];
if ($act == 'pickup') {
    admin_priv('dispatch_list_purchase');
	$providerId = $_REQUEST['providerId'];
	if ($providerId) {
		$list = getPickupList($_SESSION['party_id'], $providerId);	
	} else {
		$list = getPickupList($_SESSION['party_id']);
	}
} elseif ($act == 'notice') {
    admin_priv('dispatch_list_purchase');
	$providerId = $_REQUEST['providerId'];
	if ($providerId) {
		$list = getNoticeList($_SESSION['party_id'], $providerId);
	} else {
		$list = getNoticeList($_SESSION['party_id']);
	}
} elseif ($act == 'current') {
    $providerId = $_REQUEST['providerId'];
    $criteria = new stdClass();

    $criteria->beginSubmitDate = date('Y-m-d');
    $criteria->endSubmitDate = date('Y-m-d', strtotime('+1 day'));
    $criteria->partyId = $_SESSION['party_id'];
    if ($providerId) {
        $criteria->providerId = intval($providerId);
    }
    
    $list = searchDispatchLists($criteria);
} elseif ($act == 'shipped_no_confirmed') {
    $sql = "SELECT
            o.order_sn,
            d.dispatch_sn
        from
            ecshop.ecs_order_info o,
            romeo.dispatch_list d
        where
            o.order_sn = d.order_sn and
            o.shipping_status = 1 and
            d.DISPATCH_STATUS_ID = 'OK' and
            o.party_id = '%d' ";
    
    $lists = $db->getAll(sprintf($sql, intval($_SESSION['party_id'])));
    $smarty->assign('lists', $lists);
    $smarty->display('dispatchlist/list_other.htm');
    exit();
} elseif ($act == 'search' || isset($_GET['row'])) {
	//@FIXME 需要先显示500个，然后点“显示全部”再显示全部
	
    $row = $_REQUEST['row'];
    if ($row['beginOrderTime'] || $row['endOrderTime']) {
        if (!($row['beginOrderTime'] && $row['endOrderTime'])) {
            print "既然想按订单时间查询，订单起始时间都选择一下吧，不然我不好找呢";
            exit();
        }
        
        if (abs(strtotime($row['endOrderTime']) - strtotime($row['beginOrderTime']) ) / 86400 > 31 ) {
            print "选择的订单时间范围超过一个月了，这么多数据，我想你也看不过来那，为了保护你的视力，建议选择的时间范围小点点:)";
            exit();
        }
    }

    $criteria = new stdClass();
    // $criteria->partyId = PARTY_JJSHOUSE;
    $criteria->partyId = $_SESSION['party_id'];
    
    if ($row) {
        foreach ($row as $key=> $value) {
            if (!$value) {
                continue;
            }
    
            if (in_array($key, array('providerId'))) {
                $criteria->$key = intval($value);
            } else {
                $criteria->$key = $value;
            }
        }
    }

    //v($criteria); die();
    //$criteria->offset = 0;
    //$criteria->count = 5;
    $list = searchDispatchLists($criteria);
}

$statusMap = array(
    'PREPARE' => '已创建',
    'OK' => '已确认',
    'CANCELLED' => '已取消',
    'FINISHED' => '已完成',
);

$providerId = $_REQUEST['providerId'];
if (!$providerId) {$providerId = $_REQUEST['row']['providerId'];}
if ($providerId) {
	$sql = "select provider_name from ecshop.ecs_provider where provider_id = {$providerId} ";
    $result = $db->getRow($sql);
    $selectProviderName = $result['provider_name'];
    $smarty->assign('selectProviderName', $selectProviderName);
}

if ($list) {
	// 获取所有相关供应商的名字
	$providers = getProviderList($list);
	
    $orderIds = array();
    $temp_list = array();
    $sort = $_REQUEST['sort'];
    
    $sortField = '';
    if ($sort == 'dressnumber') {
        $sortField = "jjshouseGoodsId";
    } elseif ($_GET['row']['dispatchStatusId'] == 'PREPARE') { // prepare 的默认按orderId排序
        $sortField = "orderId";
    }
    
    foreach ($list as $key => $dispatchList) {
        $list[$key]->providerName = $providers[(int)$dispatchList->providerId];
        $list[$key]->dueDate = date("Y-m-d", strtotime($dispatchList->dueDate));
        $list[$key]->statusName = $statusMap[$dispatchList->dispatchStatusId];
        //if ($dispatchList->dueDate <= date("Y-m-d")) { // 当天到期的工单，要红色显示
        if ($dispatchList->dispatchPriorityId == 'RUSH') {
            $list[$key]->bgColor = "red";
        }
        $list[$key]->jjshouseGoodsId = getjjshouseGoodsId($dispatchList->goodsSn);
        
        $orderIds[] = $dispatchList->orderId;
        
        if ($sortField) {
            $temp_list[$dispatchList->$sortField][] = $dispatchList;
        }

        // {{{ 是否有马甲费用
        $sql = "SELECT oga.value
        FROM romeo.dispatch_list dl
        	left join ecshop.order_goods_attribute oga on oga.order_goods_id = dl.order_goods_id and oga.name = 'wrap_price'
        WHERE dl.dispatch_list_id = '{$dispatchList->dispatchListId}'
	        and oga.value > 0
        ";
        $wrap_price = $db->getOne($sql);
        $list[$key]->wrapPrice = $wrap_price;
        // }}}
        // {{{ 最近一次价格
        /*
         * 2012-07-27 Zandy 注释掉，还下面的sql
        $sql = "select price 
        		from romeo.dispatch_list 
        		where external_goods_id = '{$dispatchList->externalGoodsId}' and dispatch_sn != '' 
        		order by submit_date desc 
        		limit 1";
        */
        $addsql = " and (oga.value <= 0 OR oga.value is null) ";
        if ($wrap_price > 0) {
        	$addsql = " and oga.value > 0 ";
        }
        $sql = "SELECT dl.price 
        FROM romeo.dispatch_list dl 
        	left join ecshop.order_goods_attribute oga on oga.order_goods_id = dl.order_goods_id and oga.name = 'wrap_price'  
        WHERE dl.external_goods_id = '{$dispatchList->externalGoodsId}' and dl.dispatch_sn != '' 
	        $addsql
	    ORDER BY dl.submit_date desc 
	    LIMIT 1 
        ";
        $list[$key]->latestPrice = $GLOBALS['db']->getOne($sql);
        // }}}
        // {{{最近三次供应商
        $latestPurchasePrices = getLatestPurchasePrices($dispatchList->orderGoodsId, $dispatchList->dispatchSn);
        #$list[$key]->latestPrice = isset($latestPurchasePrices[0]['price']) ? $latestPurchasePrices[0]['price'] : 0;
        $provider_names = array();
        if ($latestPurchasePrices) {
        	foreach ($latestPurchasePrices as $lpp) {
        		$provider_names[] = $lpp['provider_name'];
        	}
        }
        $list[$key]->latestProvider = sizeof($provider_names) > 0 ? join(", ", $provider_names) : '';
        if (sizeof($provider_names) == 3 && sizeof(array_unique($provider_names)) == 1) {
        	$list[$key]->defaultProviderId = $latestPurchasePrices[0]['provider_id'];
        }
        // }}}
        
        /*
        // {{{ 最近三次供应商
        $sql = "select group_concat(provider_name SEPARATOR ', ') as provider_name from
			(select provider_name
        		from romeo.dispatch_list d
        			left join ecshop.ecs_provider p on p.provider_id = cast(d.provider_id as unsigned)
        		where external_goods_id = '$dispatchList->externalGoodsId' and dispatch_sn != '' 
        		order by submit_date desc 
        		limit 3
			) as xxx";
        $list[$key]->latestProvider = $GLOBALS['db']->getOne($sql);
        // {{{ 如果三次都一样，则直接默认设置为该供应商
        $_tmp_x = explode(", ", $list[$key]->latestProvider);
        if (sizeof($_tmp_x) == 3 && sizeof(array_unique($_tmp_x)) == 1) {
	        $sql = "select d.provider_id
	        		from romeo.dispatch_list d
	        		where external_goods_id = '$dispatchList->externalGoodsId' and dispatch_sn != '' 
	        		order by submit_date desc 
	        		limit 1";
        	$list[$key]->defaultProviderId = $GLOBALS['db']->getOne($sql);
        }
        // }}}
        // }}}
        */
    }
    
    // 如果是按jjshouseGoodsId重排的话，需要按jjshouseGoodsId排序
    if ($sortField == 'jjshouseGoodsId') {
        ksort($temp_list);    
    }
    
    // 如果需要重排的
    if ($sortField) {
        $list = array();
        foreach ($temp_list as $sortFieldValue => $dispatchLists) {
            foreach ($dispatchLists as $dispatchList) {
                $list[] = $dispatchList;
            }
        }
    }
    
    // 加上婚期的信息
    $sql = "select order_id, attr_value from order_attribute" .
            " where attr_name = 'important_day' and attr_value != '0000-00-00'" .
            " and " . db_create_in($orderIds, 'order_id');
    $rows = $db->getAll($sql);
    if ($rows) {
        $importantDay = array();
        foreach ($rows as $row) {
            $importantDay[$row['order_id']] = $row['attr_value'];
        }
        
        foreach ($list as $key => $dispatchList) {
            $list[$key]->importantDay = $importantDay[$dispatchList->orderId];
        }
    }
}

if ($_REQUEST['subAct'] == 'export' || $_REQUEST['subAct'] == 'print') {
    if ($list) {
        $content = <<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>工单列表</title>
<style>
table {background:#666;}
td{background:#fff;}
</style>
</head>
<body>
<table cellspacing="1" cellpadding="5">
<tr>
<td>工单号</td>
<td>状态</td>
<td>外部订单号</td>
<td>图片</td>
<td>sku</td>
<td>供应商</td>
<td>工单交货时间</td>
<td>优先级</td>
</tr>
EOF;

        foreach ($list as $dispatchList) {
            $content .= "<tr>" .
                    "<td>{$dispatchList->dispatchSn}</td>" .
                    "<td>{$dispatchList->statusName}</td>" .
                    "<td>{$dispatchList->externalOrderSn}</td>" .
                    "<td><img height=\"60\" src=\"{$dispatchList->imageUrl}\" /></td>" .
                    "<td>{$dispatchList->jjshouseGoodsId}</td>".
                    "<td>{$dispatchList->providerName}</td>".
                    // "<td>{$dispatchList->importantDay}</td>".
                    "<td>{$dispatchList->dueDate}</td>".
                    "<td>{$dispatchList->dispatchPriorityId}</td>".
                    "</tr>";
        }
        
        $content .= "</table></body></html>";
        
        if ($_REQUEST['subAct'] == 'export') {
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            header("Content-Description: File Transfer");
            header("Content-Type: application/zip");
            header('Content-disposition: attachment; filename='.date("Y-m-d_H_i_s").'.htm; charset=utf-8');
            header("Content-Transfer-Encoding: binary");
            header('Content-Length: '. strlen($content));
        }
        
        print $content;
        exit();
    } else {
        print "没有数据可以导出";
        exit();
    }
}


$dispatchStatusIds = array(
    '' => '--不限--',
    'PREPARE' => '待提交',
    'OK' => '处理中',
    'CANCELLED' => '取 消',
    'FINISHED' => '已完成',
    'REVISION' => '待修订',
);

$prioritys = array(
    '' => '--不限--',
    'RUSH' => '加 急',
    'NORMAL' => '正 常',
);


/**
 * 分析出cms中的goods id
 */
function getjjshouseGoodsId($goodsSn) {
    
    if (preg_match('/g(\d+)/', $goodsSn, $matches)) {
        return $matches[1];
    }
    return "";
}
$list = addFinishedCancelledCount($list);


$smarty->assign('dispatchStatusIds', $dispatchStatusIds);
$smarty->assign('prioritys', $prioritys);
$smarty->assign('act', $act);
$smarty->assign('list', $list);
$smarty->assign('count', count($list));
$smarty->display('dispatchlist/list.htm');
