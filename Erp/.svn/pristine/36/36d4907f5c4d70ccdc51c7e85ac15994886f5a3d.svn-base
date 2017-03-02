<?php

/**
 * 客服绩效 
 */
define('IN_ECS', true);
set_time_limit(0);

require_once('../includes/init.php');
if (!in_array($_SESSION['admin_name'], array('ygu','shyuan','zwsun','pjun','fzou','jjpan','xlhong'))) {
    die('not valid!');    
}

require_once('../includes/lib_common.php');
require_once('../function.php');
require_once('../distribution.inc.php');
require_once(ROOT_PATH . 'includes/lib_common.php');
require_once(ROOT_PATH . 'includes/debug/lib_debug.php');
#require_once('distribution_order_address_analyze.php');
require_once(ROOT_PATH . 'includes/lib_order.php');
#require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
#require_once(ROOT_PATH . 'RomeoApi/cls_HashMap.php');
#require_once(ROOT_PATH . 'RomeoApi/cls_GenericValue.php');
require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH . 'includes/helper/image.php');
require_once(ROOT_PATH . 'admin/distribution_order_address_analyze.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'RomeoApi/lib_facility.php');
require_once(ROOT_PATH . 'RomeoApi/lib_refund.php');

// excel库
set_time_limit(0);
set_include_path(get_include_path() . PATH_SEPARATOR . '../includes/Classes/');
require_once 'PHPExcel.php';
require_once 'PHPExcel/IOFactory.php';

// 默认的时间
$start = date('Y-m-').'01';
$ended = date('Y-m-d',strtotime('last day',strtotime('next month', strtotime($start))));


/**
 * 导出数据
 */
if (isset($_POST['export']) && !empty($_POST['export']))
{

// 修改统计时间
$s = $_POST['start'] .' 00:00:01';
$e = $_POST['ended'] .' 23:59:59';

// 店铺
$sql = "SELECT taobao_shop_conf_id,nick FROM taobao_shop_conf WHERE taobao_shop_conf_id > 3";
$rowset = $slave_db->getAll($sql);

// 回复条数，按个人分组
$sql1="
select
	count(*) as num, s.owner
from ecshop.taobao_consulting_section s
	inner join ecshop.taobao_consulting_content c on c.section_id = s.section_id and c.type = 'REPLY'
where
	s.taobao_shop_id = %d and c.time between '%s' and '%s'
group by s.owner
";
// 个人回复超过3分钟回复数
$sql2="
select
	count(*)
from ecshop.taobao_consulting_section s
	inner join ecshop.taobao_consulting_content c on c.section_id = s.section_id and c.type = 'REPLY'
where
	s.taobao_shop_id = %d and c.time between '%s' and '%s' and s.owner='%s'
    and c.interval > 180 and c.interval < 7200
";
// 个人自动回复数
$sql3="
select
	count(*)
from ecshop.taobao_consulting_section s
	inner join ecshop.taobao_consulting_content c on c.section_id = s.section_id and c.type = 'REPLY'
where
	s.taobao_shop_id = %d and c.time between '%s' and '%s' and s.owner='%s'
    and c.interval <= 1
";

// 个人自动回复明细
$sql4="
select
	taobao_shop_id, s.owner, s.referee, c.replier, c.time, c.interval, c.type, c.content
from
	taobao_consulting_section s 
	left join taobao_consulting_content c on c.section_id=s.section_id
where
	s.taobao_shop_id = %d and c.time between '%s' and '%s'
	and c.interval <=1
";

$excel=new PHPExcel;
$excel->getProperties()->setTitle("淘宝咨询");

foreach($rowset as $row)
{
    $sheet=$excel->createSheet();
    $sheet->setTitle($row['nick']);
	
    $sheet->setCellValue("A1", "{$row['nick']}");
	
    // 统计
    $i=2;
    $sheet->setCellValue("A1", "{$row['nick']}");
    $sheet->setCellValue("B1", "咨询回复条数");
    $sheet->setCellValue("C1", "超过3分钟咨询回复条数");
    $sheet->setCellValue("D1", "自动复条数");
    
    $result1=$slave_db->getAll(sprintf($sql1,$row['taobao_shop_conf_id'],$s,$e));
    if($result1) {
        foreach($result1 as $r) {
           $sheet->setCellValue("A$i", $r['owner']);
           $sheet->setCellValue("B$i", $r['num']);
           $sheet->setCellValue("C$i", $slave_db->getOne(sprintf($sql2,$row['taobao_shop_conf_id'],$s,$e,$r['owner'])));
           $sheet->setCellValue("D$i", $slave_db->getOne(sprintf($sql3,$row['taobao_shop_conf_id'],$s,$e,$r['owner'])));
           $i++;
        }
    }
    $i++;

    // 自动回复明细
	if (isset($_POST['checked'])) {
	    $sheet->setCellValue("A".$i++, "自动回复明细");
	    
	    $sheet->setCellValue("A".$i, "客服人员");
	    $sheet->setCellValue("B".$i, "咨询员人");
	    $sheet->setCellValue("C".$i, "回复人");
	    $sheet->setCellValue("D".$i, "回复时间");
	    $sheet->setCellValue("E".$i, "回复间隔秒");
	    $sheet->setCellValue("F".$i, "回复内容");
	    $i++;
	
	    $result4=$slave_db->getAll(sprintf($sql4,$row['taobao_shop_conf_id'],$s,$e));
	    foreach($result4 as $r4) {
	        $sheet->setCellValue("A$i", $r4['owner']);
	        $sheet->setCellValue("B$i", $r4['referee']);
	        $sheet->setCellValue("C$i", $r4['replier']);
	        $sheet->setCellValue("D$i", $r4['time']);
	        $sheet->setCellValue("E$i", $r4['interval']);
	        $sheet->setCellValue("F$i", $r4['content']);
	        $i++;
	    }
	}
}

// 订单确认情况
$sql6="
select 
	if(o.party_id=16,
		case o.pay_id
			when 67 then '淘宝乐其数码专营店'
        	when 77 then '乐其电教当当旗舰店'
        	when 105 then '易智付科技（北京）有限公司'
		else '电教分销或其它'
		end,
		case o.party_id
		    when o.party_id then p.name
		end) as party_name,
	o.order_sn,
	o.order_time,
	(select action_time from ecs_order_action where order_status=1 and order_id=o.order_id order by action_time asc limit 1) as confirm_time,
	(select action_user from ecs_order_action where order_status=1 and order_id=o.order_id order by action_time asc limit 1) as confirm_user,
	o.order_status
from
	ecs_order_info as o
	left join ecs_order_action as a on o.order_id=a.order_id
	left join romeo.party as p on p.party_id = convert(o.party_id using utf8)
where
	o.order_type_id='SALE' and ( (o.party_id IN (64,128,65536,65539,65540,65546,65547,65548,65550,65551,65552,65553,65555,65558,65559,65562,65571,65572,65569,65578,65576,65568,65577,65557,65574,65556)) or (o.party_id=16 and o.pay_id IN (67,77,105))) and
	a.order_status = 1 and a.action_time between '%s' and '%s' and
	o.order_time > '%s'
group by
	a.order_id;
";
$result6=$slave_db->getAll(sprintf($sql6,$s,$e,$s));
if($result6){

    $sheet=$excel->createSheet();
    $sheet->setTitle('订单确认数据');
	
    $sheet->setCellValue("A1", "订单确认数据");
    
    $i=2;
    $sheet->setCellValue("A$i", "组织");
    $sheet->setCellValue("B$i", "订单号");
    $sheet->setCellValue("C$i", "下单时间");
    $sheet->setCellValue("D$i", "确认时间");
    $sheet->setCellValue("E$i", "确认操作人");
    $sheet->setCellValue("F$i", "订单状态");
    $i++;
    
    foreach($result6 as $r6) {
           $sheet->setCellValue("A$i", $r6['party_name']);
           $sheet->setCellValueExplicit("B$i", $r6['order_sn']);
           $sheet->setCellValue("C$i", $r6['order_time']);
           $sheet->setCellValue("D$i", $r6['confirm_time']);
           $sheet->setCellValue("E$i", $r6['confirm_user']);
           $sheet->setCellValue("F$i", $r6['order_status']);
           $i++;
    }
}


// 售后申请情况
$sql7="
select
	if(o.party_id=16,
		case o.pay_id
			when 67 then '淘宝乐其数码专营店'
			when 77 then '乐其电教当当旗舰店'
			when 105 then '易智付科技（北京）有限公司'
		else '电教分销或其它'
		end,
		case o.party_id
		   when o.party_id then p.name
	end) as party_name,
	o.order_sn,
	case s.service_type
		  when 1 then '换货申请'
			when 2 then '退货申请'
			when 5 then '保价申请'
			when 6 then '漏寄申请'
			when 7 then '返修申请'
	end as service_type,
	s.apply_datetime,
	(select log_username from service_log where service_id=s.service_id order by log_datetime ASC limit 1) as log_username,
	s.apply_reason
from 
	service as s
  left join ecs_order_info as o on o.order_id=s.order_id
  left join romeo.party as p on p.party_id = convert(o.party_id using utf8)
where
	s.apply_datetime between '%s' and '%s'
";
$result7=$slave_db->getAll(sprintf($sql7,$s,$e));
if($result7){

    $sheet=$excel->createSheet();
    $sheet->setTitle('售后数据');
	

    $sheet->setCellValue("A1", "售后数据");
    

    $i=2;
    $sheet->setCellValue("A$i", "组织");
    $sheet->setCellValue("B$i", "订单号");
    $sheet->setCellValue("C$i", "售后类型");
    $sheet->setCellValue("D$i", "售后申请时间");
    $sheet->setCellValue("E$i", "操作人");
    $sheet->setCellValue("F$i", "申请原因");
    $i++;
    
    foreach($result7 as $r7) {
        $sheet->setCellValue("A$i", $r7['party_name']);
        $sheet->setCellValueExplicit("B$i", $r7['order_sn']);
        $sheet->setCellValue("C$i", $r7['service_type']);
        $sheet->setCellValue("D$i", $r7['apply_datetime']);
        $sheet->setCellValue("E$i", $r7['log_username']);
        $sheet->setCellValue("F$i", $r7['apply_reason']);
        $i++;
    }
}



// 下载
if (!headers_sent()) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="DIS-PREPAYMENT.xls"');
    header('Cache-Control: max-age=0');
    $output = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
    $output->save('php://output');
    exit;
}

}  // end if ($_POST)





/**
 * 导出订单
 */
if (isset($_POST['export2']) && !empty($_POST['export2']))
{
	
// 期初时间
if(!empty($_REQUEST['orders1']))
{
    $orders=$_REQUEST['orders1'];
    $field='taobao_order_sn';
}
else if(!empty($_REQUEST['orders2']))
{
    $orders=$_REQUEST['orders2'];
    $field='order_sn';
}

// 提交查询
if($orders) 
{
    $orders=preg_split('/[\s,]+/',$orders,-1,PREG_SPLIT_NO_EMPTY);
    if(!empty($orders))
    {
    	$filename="客服绩效导出（".str_replace('-','',$_POST['start'])."-".str_replace('-','',$_POST['ended'])."）";
    	$excel=new PHPExcel;
    	$excel->getProperties()->setTitle($filename);
        $sheet=$excel->getActiveSheet();
        $sheet->setTitle('订单列表');
    	
        $i=1;
        $sheet->setCellValue("A{$i}", "订单号");
        $sheet->setCellValue("B{$i}", "订单状态");
        $sheet->setCellValue("C{$i}", "下单时间");
        $sheet->setCellValue("D{$i}", "支付方式");
        $sheet->setCellValue("E{$i}", "订单金额");
        $sheet->setCellValue("F{$i}", "商品");
        $i++;
    
    	$sql="
            SELECT
                o.order_sn, o.order_status,o.order_time, o.pay_name, o.order_amount, (o.order_amount-o.shipping_fee) as goods_amount, group_concat(og.goods_name separator '/') as goods_name
            FROM ecs_order_info o 
            left join ecs_order_goods og on o.order_id = og.order_id
            WHERE o.order_type_id='SALE' and o.{$field} %s
            GROUP BY o.order_id;
    	";
    	foreach(array_chunk($orders, 100) as $group){
            $query=$slave_db->query(sprintf($sql,db_create_in($group)));
            if($result!==false){
            	while($row=$slave_db->fetchRow($query)){
		            $sheet->setCellValueExplicit("A{$i}", $row['order_sn']);
		            $sheet->setCellValue("B{$i}", $row['order_status']);
		            $sheet->setCellValue("C{$i}", $row['order_time']);
		            $sheet->setCellValue("D{$i}", $row['pay_name']);
		            $sheet->setCellValue("E{$i}", $row['goods_amount']);
		            $sheet->setCellValue("F{$i}", $row['goods_name']);
            		$i++;
            	}
            }
    	}
    	
        // 输出
        if(!headers_sent()){
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="'.$filename.'.xls"');
            header('Cache-Control: max-age=0');
            $output=PHPExcel_IOFactory::createWriter($excel, 'Excel5');
            $output->save('php://output');
            exit;
        }
    }
}
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>客服绩效导出</title>
</head>
<body>

	<b>客服绩效导出</b>
	<br><br>
	<form method="post" traget="result">
	<table border="1" cellpadding="6">
	    <tr>
	        <td>时间间隔（不要太长了）：</td>
	        <td>
	            <input type="text" name="start" value="<?php if(isset($_POST['start'])&&!empty($_POST['start'])): echo $_POST['start']; ;else: echo $start; endif; ?>" size="10" maxlength="10" />
	            -
	            <input type="text" name="ended" value="<?php if(isset($_POST['ended'])&&!empty($_POST['ended'])): echo $_POST['ended']; ;else: echo $ended; endif; ?>" size="10" maxlength="10" />
	        </td>
	    </tr>
	    <tr>
	        <td>要不要自动回复内容的明细？</td>
	        <td>
				<input type="checkbox" value="1" name="checked" id="xy" /><label for="xy">要就打钩，不过要是明细太多的话怕导不出来</label>
	        </td>
	    </tr>
	    <tr>
	    	<td><input type="hidden" value="1" name="export" /></td>
	        <td><input type="submit" value="少废话快给爷导出" /> </td>
	    </tr>
	</table>
	</form>


    <br/><br/><br/><br/>
    <p><b>订单导出</b></p>
	<b>请输入淘宝订单号，用逗号分隔</b>
	<form method="post">
	    <textarea rows="10" cols="50" name="orders1"></textarea>
	    <input type="hidden" name="export2" value="1" />
	    <input type="submit" value="导出" />
	</form>
	
	
	<br/><br/><br/><br/>
	<b>请输入ERP订单号，用逗号分隔</b>
	<form method="post">
	    <textarea rows="10" cols="50" name="orders2"></textarea>
	    <input type="hidden" name="export2" value="1" />
	    <input type="submit" value="导出" />
	</form>
	
</body>
</html>
