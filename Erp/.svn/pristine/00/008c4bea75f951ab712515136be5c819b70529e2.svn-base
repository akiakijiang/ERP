<?php
/**
 * 昨日遗留订单
 */
define('IN_ECS', true);
require_once('includes/init.php');
require_once ('includes/debug/lib_log.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'includes/cls_page.php');

$party_id = isset($_REQUEST['party_id'])?trim($_REQUEST['party_id']):false;
$yesterday = isset($_REQUEST['yesterday'])?trim($_REQUEST['yesterday']):date('Y-m-d',strtotime('-1 day'));
$startDay = isset($_REQUEST['startDay'])?trim($_REQUEST['startDay']):date('Y-m-d',strtotime('-7 day'));
$facility = isset($_REQUEST['facility'])?$_REQUEST['facility']:0;

// 构造分页
$size = 10;
$page = $_REQUEST['page'] ? $_REQUEST['page'] : 1;
$start = ($page - 1) * $size;

// 取得库存同步情况列表
$sql = "select 
        p.name pName, f.FACILITY_NAME, o.order_sn, o.order_time, from_unixtime(o.pay_time) pay_time, if(o.reserved_time = 0, '', from_unixtime(o.reserved_time)) reserved_time, 
		CONCAT_WS(',',case o.order_status when 0 then '未确认' 
									      when 1 then '已确认' 
									      when 2 then '取消' 
										  when 4 then '拒收' 
										  when 8 then '超卖' 
										  when 11 then '外包发货' end, 
						case o.pay_status when 0 then '未付款' 
										  when 1 then '付款中' 
										  when 2 then '已付款' 
										  when 3 then '待退款' 
										  when 4 then '已退款' end, 
						case o.shipping_status when 0 then '待配货' 
											   when 1 then '已发货' 
											   when 2 then '收货确认' 
											   when 3 then '拒收退回' 
											   when 4 then '已发往自提点' 
											   when 5 then '等待用户自提' 
											   when 6 then '已自提' 
											   when 7 then '自提取消' 
											   when 8 then '已出库/复核，待发货' 
											   when 9 then '已配货，待出库' 
											   when 10 then '已配货，但商品改变' 
											   when 11 then '已追回' 
											   when 12 then '已拣货出库,待复核' 
											   when 13 then '批拣中' end) as status
      from 
        ecshop.ecs_order_info o 
        left join romeo.party p on convert(o.party_id using utf8) = p.party_id 
        left join romeo.facility f on o.facility_id = f.facility_id 
      where 
        o.order_time >= '{$startDay}' and
        o.order_type_id = 'SALE' and
        o.order_status != 2 and
        o.party_id in ($party_id) and 
        o.pay_time <= unix_timestamp('".$yesterday." 16:00:00') and 
        o.shipping_status not in (1, 2, 3, 11) and 
        o.facility_id in(". $facility .") 
	 order by o.facility_id, o.party_id ";
//Qlog::log($sql);
$orders = $slave_db->getAll($sql);
$overStock_orders = array_slice($orders, $start, $size);

$count = count($orders);
$pager = setPager($count, $size, $page,"overstock_orders_list.php?facility=".$facility."&party_id=".$party_id.'&startDay='.$startDay.'&yesterday='.$yesterday);
       
$smarty->assign('pager',$pager);
$smarty->assign('overStock_orders', $overStock_orders);
$smarty->display('overstock_orders_list.htm');

/*
 * 分页
 */
function setPager($total, $offset = 9, $page = null, $url = null, $back = 3, $label = 'page'){
    $page = null == $page ? 1 : $page;
    $page = $page < 1 ? 1 : $page;

    $pages = ceil($total/$offset);
    $pages = $pages > 0 ? $pages : 1;
    $page = $page > $pages ? $pages : $page;
    
    $url = null == $url ? $_SERVER['REQUEST_URI'] : $url;
    $url = preg_replace("/([?|&])$label\=[0-9]*/", "\\1", $url);
    $url = str_replace(array("&&", "?&"), array('&', '?'), $url);

    $url .= strstr($url, '?')
    ? (substr($url, -1) == '?' ? '' : (substr($url, -1) == '&' ? '' : '&'))
    : '?';

    $ppp = '';
    #$ppp .= '<a href="'.$url.$label.'=1" target="" title="First Page 1" class="Pager">&#171</a> ';
    $ppp .= '<a href="'.$url.$label.'=1" target="" title="First Page 1" class="Pager">[首页]</a> ';
    if ($pages <= ($back*2 + 1))
    {
        for ($i=1; $i<=$pages; $i++)
        {
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= '<a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">['.$i.']</a>';
            }
        }
    }else{
        $b = $back + 2;
        if ($page <= $b)
        {
            $fromfrom = 1;
            $toto = $back * 2 + 1;
        }elseif ($page > $pages - $b){
            $c = $back*2;
            $fromfrom = $pages - $c;
            $toto = $pages;
        }else{
            $fromfrom = $page - $back;
            $toto = $page + $back;
        }
        for ($i=$fromfrom; $i<=$toto; $i++)
        {
            if ($page == $i) {
                $ppp .= $i;
            } else {
                $ppp .= ' <a href="'.$url.$label.'='.$i.'" target="" title="Page '.$i.'" class="Pager">['.$i.']</a> ';
            }
        }
    }
    #$ppp .= ' <a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">&#187</a>';
    $ppp .= ' <a href="'.$url.$label.'='.$pages.'" target="" title="Last Page '.$pages.'" class="Pager">[尾页]</a>';
    $ppp .= ' <input type="text" class="pagerInput" name="page" value="'.$page.'" size="5" onFocus="this.select();" onBlur="if(this.value != '.$page.' && this.value >= 1 && this.value <= '.$pages.'){location.href=\''.$url.$label.'=\' + this.value;}else{this.value = '.$page.';}" title=" 跳转 ">';
    $ppp .= ' ( 页数/记录数 :  '.$pages.'/'.$total.')';
    return $ppp;
}
?>
