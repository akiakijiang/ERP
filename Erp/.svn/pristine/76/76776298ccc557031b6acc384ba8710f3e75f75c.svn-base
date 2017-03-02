<?php
define('IN_ECS', true);

require('includes/init.php');

admin_priv('cw_finance_yingshou_main');
require_once("function.php");

$yishou[] = array('cond'=>' and '. party_sql('party_id', PARTY_OUKU) . ' and pay_id = 1 and shipping_id =36 and biaoju_store_id in(0, 5, 8, 9)', 'seq'=>3, 'start','end'=>date("Ymd"), 'name'=>'上海EMS', 'real_paid'=>0, 'bill'=>'60', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and '. party_sql('party_id', PARTY_LEQEE). ' and pay_id = 1 and shipping_id =36 and biaoju_store_id in(0, 5, 8, 9)', 'seq'=>3, 'start','end'=>date("Ymd"), 'name'=>'深圳EMS', 'real_paid'=>0, 'bill'=>'60', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 1 and shipping_id in (44, 49)', 'seq'=>5, 'start','end'=>date("Ymd"), 'name'=>'顺丰', 'real_paid'=>0, 'bill'=>'15', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 1 and shipping_id =48 ', 'seq'=>6, 'start','end'=>date("Ymd"), 'name'=>'风火', 'real_paid'=>0, 'bill'=>'10', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 5', 'start','end'=>date("Ymd"), 'seq'=>12, 'name'=>'支付宝', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 20', 'start','end'=>date("Ymd"), 'seq'=>52, 'name'=>'快钱', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 21', 'start','end'=>date("Ymd"), 'seq'=>53, 'name'=>'快钱的工行网银', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 22', 'start','end'=>date("Ymd"), 'seq'=>54, 'name'=>'快钱的招行网银', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 23', 'start','end'=>date("Ymd"), 'seq'=>55, 'name'=>'快钱的建行网银', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 24', 'start','end'=>date("Ymd"), 'seq'=>56, 'name'=>'快钱的农行网银', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 25', 'start','end'=>date("Ymd"), 'seq'=>57, 'name'=>'快钱的广发网银', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 19', 'start','end'=>date("Ymd"), 'seq'=>30, 'name'=>'财付通', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);


$yishou[] = array('cond'=>' and pay_id = 2', 'start','end'=>date("Ymd"), 'seq'=>20, 'name'=>'邮局汇款', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 13', 'start','end'=>date("Ymd"), 'seq'=>13, 'name'=>'招商银行/转账', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 15', 'start','end'=>date("Ymd"), 'seq'=>14, 'name'=>'工商银行/转账', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 10', 'start','end'=>date("Ymd"), 'seq'=>15, 'name'=>'农业银行/转账', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 16', 'start','end'=>date("Ymd"), 'seq'=>16, 'name'=>'建设银行/转账', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 11', 'start','end'=>date("Ymd"), 'seq'=>18, 'name'=>'交通银行/转账', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 14', 'start','end'=>date("Ymd"), 'seq'=>19, 'name'=>'中国银行/转账', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 26', 'start','end'=>date("Ymd"), 'seq'=>58, 'name'=>'浦发银行/转账', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);

// 已废弃列表
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =19 ', 'seq'=>7, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]张江自提点', 'real_paid'=>0, 'bill'=>'7', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(10,24,51,81) ', 'seq'=>8, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]徐汇|中山北路自提点', 'real_paid'=>0, 'bill'=>'30', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id = 39 ', 'seq'=>23, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]广东深圳自提点', 'real_paid'=>0, 'bill'=>'40', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id = 58 ', 'seq'=>59, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]广东广州自提点', 'real_paid'=>0, 'bill'=>'40', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id = 59 ', 'seq'=>60, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]广东东莞自提点', 'real_paid'=>0, 'bill'=>'40', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(40,52,54) ', 'seq'=>24, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]东营|济南|青岛自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(41,42)', 'seq'=>25, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]东城|海淀自提点', 'real_paid'=>0, 'bill'=>'40', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(50,61)', 'seq'=>27, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]大连|沈阳自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =56 ', 'seq'=>31, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]江苏南京自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(57, 68, 72) ', 'seq'=>32, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]杭州|宁波|温州自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =55 ', 'seq'=>33, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]四川成都自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(60,75) ', 'seq'=>34, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]武汉洪山区|江汉区自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =62 ', 'seq'=>35, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]陕西西安自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =63 ', 'seq'=>36, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]黑龙江哈尔滨自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =64 ', 'seq'=>37, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]天津和平自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =65 ', 'seq'=>38, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]重庆高新自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =66 ', 'seq'=>39, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]吉林长春自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(67, 69) ', 'seq'=>40, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]福州|厦门自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =70 ', 'seq'=>41, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]湖南长沙自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =71 ', 'seq'=>42, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]河南郑州自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =73 ', 'seq'=>43, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]河北石家庄自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =74 ', 'seq'=>44, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]山西太原自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =76 ', 'seq'=>45, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]广西南宁自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =77 ', 'seq'=>46, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]江西南昌自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =78 ', 'seq'=>47, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]贵州贵阳自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =79 ', 'seq'=>48, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]云南昆明自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =80 ', 'seq'=>49, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]海南海口自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id =82 ', 'seq'=>50, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]安徽合肥自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(83,84) ', 'seq'=>51, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]呼和浩特|包头自提点', 'real_paid'=>0, 'bill'=>'50', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id in (1,17,18) and shipping_id in(23) ', 'seq'=>8, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]张杨自提点', 'real_paid'=>0, 'bill'=>'30', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 1 and shipping_id =36 and biaoju_store_id not in(0, 5, 8, 9)', 'seq'=>4, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]深圳EMS', 'real_paid'=>0, 'bill'=>'60', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 1 and shipping_id =11 and biaoju_store_id in(0, 5, 8, 9)', 'seq'=>1, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]上海宅急送', 'real_paid'=>0, 'bill'=>'45', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 1 and shipping_id =11 and biaoju_store_id not in(0, 5, 8, 9)', 'seq'=>2, 'start','end'=>date("Ymd"), 'name'=>'[已废弃]深圳宅急送', 'real_paid'=>0, 'bill'=>'45', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 3', 'start','end'=>date("Ymd"), 'seq'=>28, 'name'=>'[已废弃]银行汇款', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 12', 'start','end'=>date("Ymd"), 'seq'=>17, 'name'=>'[已废弃]浦发银行/转账', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and shipping_name like "%代理%"', 'start','end'=>date("Ymd"), 'seq'=>29, 'name'=>'[已废弃]校园代理', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 4 ', 'start','end'=>date("Ymd"), 'seq'=>9, 'name'=>'[已废弃]网银在线', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 8', 'start','end'=>date("Ymd"), 'seq'=>10, 'name'=>'[已废弃]招行网银', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);
$yishou[] = array('cond'=>' and pay_id = 9', 'start','end'=>date("Ymd"), 'seq'=>11, 'name'=>'[已废弃]工行网银', 'real_paid'=>0, 'bill'=>'0', 'gq_weishou'=>0, 'dq_weishou'=>0);

$end = $_REQUEST['end'];
if (strtotime($end) <= 0) {
	$end = 'NOW()';
}

// 获取60天已收金额
$sql = "select shipping_id, sum(real_paid) AS total_real_paid from ecs_order_info 
	where shipping_status in (1,2,4,6) and pay_status = 2 
	and pay_id = 1
	and ((shipping_time != 0 AND FROM_UNIXTIME(shipping_time) >= DATE_SUB(NOW(), INTERVAL 2 MONTH)) 
	or (shipping_time = 0 AND order_time >= DATE_SUB(NOW(), INTERVAL 2 MONTH))) 
	group by shipping_id";
$yishou_lms = $db->getAll($sql); //先货后款

$sql = "select pay_id, sum(real_paid) AS total_real_paid from ecs_order_info 
	where shipping_status in (1,2,4,6) and pay_status = 2 
	and pay_id != 1 
	and ((shipping_time != 0 AND (FROM_UNIXTIME(shipping_time) >= DATE_SUB(NOW(), INTERVAL 2 MONTH))) 
	or   (shipping_time = 0  AND (order_time >= DATE_SUB(NOW(), INTERVAL 2 MONTH))))
	group by pay_id";
$yishou_fms = $db->getAll($sql); //先款后货

$sum_yishou = 0;
$sum_gqws = 0;
$sum_dqws = 0;
foreach ($yishou as $key => $one_yishou) {
	// 设置60天已收款金额
	$sql = "select sum(real_paid) AS total_real_paid from ecs_order_info info
			where pay_status = 2 
			  AND (order_type_id IN ('SALE', 'SUPPLIER_SALE', 'SUPPLIER_RETURN'))".$one_yishou["cond"]."
			  AND ((shipping_time != 0 AND FROM_UNIXTIME(shipping_time) >= DATE_SUB(NOW(), INTERVAL 2 MONTH)) 
			  or   (shipping_time = 0  AND order_time >= DATE_SUB(NOW(), INTERVAL 2 MONTH)))";
	$tmp = $db->getOne($sql);
	$yishou[$key]["real_paid"] = ($tmp !== null?$tmp:0);
	$sum_yishou += ($tmp !== null?$tmp:0);
	// 获取过期未收
	$sql = "select SUM(IF(pay_status in (2, 4), order_amount-real_paid, order_amount)) AS total_real_paid 
			from ecs_order_info info 
			where order_status != 4
			  AND shipping_status in (1,2,4,5,6) and (pay_status not in (2, 4) OR order_amount > real_paid) 
			  AND is_finance_clear != 1 and (order_type_id IN ('SALE', 'SUPPLIER_SALE', 'SUPPLIER_RETURN'))
			  AND ((shipping_time != 0 AND (TO_DAYS(NOW()) - TO_DAYS(FROM_UNIXTIME(shipping_time)) >=  ".$one_yishou["bill"].$one_yishou["cond"]."))
			  OR (shipping_time = 0 AND (TO_DAYS(NOW()) - TO_DAYS(order_time) >=  ".$one_yishou["bill"].$one_yishou["cond"].")))";
	$tmp = $db->getOne($sql);
	$yishou[$key]["gq_weishou"] = ($tmp !== null?$tmp:0); 
	$sum_gqws += ($tmp !== null?$tmp:0);
	// 获取当期未收
	$sql = "select sum(order_amount) AS total_real_paid 
			from ecs_order_info info 
			where order_status != 4 
			  AND shipping_status in (1,2,4,5,6) and (pay_status != 2 OR order_amount > real_paid) 
			  AND is_finance_clear != 1 and (order_type_id IN ('SALE', 'SUPPLIER_SALE', 'SUPPLIER_RETURN'))".$one_yishou["cond"]."
			  AND ((shipping_time != 0 AND (TO_DAYS(NOW()) - TO_DAYS(FROM_UNIXTIME(shipping_time)) < ".$one_yishou["bill"].")) 
			  or   (shipping_time = 0  AND (TO_DAYS(NOW()) - TO_DAYS(order_time) < ".$one_yishou["bill"].")))";
	$tmp = $db->getOne($sql);
	$yishou[$key]["dq_weishou"] = ($tmp !== null?$tmp:0); 
	$sum_dqws += ($tmp !== null?$tmp:0);
	$Y=date(Y); $m=date(m); $d=date(d);
	$yishou[$key]["start"] = date("Ymd", mktime(0,0,0,$m,$d-$one_yishou["bill"],$Y) ); 
	
}

// 清算分类
$sql = "select sum(order_amount-real_paid) AS amount, a.finance_clear_type, b.description, b.type_name
		from ecs_order_info a, ecs_oukoo_finance_clear b
		where is_finance_clear = 1 
		  and biaoju_store_id = 0 
		  and order_amount > real_paid
		  and a.finance_clear_type = b.finance_clear_type
		  and b.is_written_of = 0 
		group by a.finance_clear_type";
$qss['not_ys'] = $db->getAll($sql);
$sql = "select sum(order_amount-real_paid) AS amount
		from ecs_order_info a, ecs_oukoo_finance_clear b
		where is_finance_clear = 1 
		  and biaoju_store_id = 0 
		  and order_amount > real_paid
		  and a.finance_clear_type = b.finance_clear_type
		  and b.is_written_of = 0";
$qss['not_ys_sum'] = $db->getOne($sql);
$sql = "select sum(order_amount-real_paid) AS amount, a.finance_clear_type, b.description, b.type_name
		from ecs_order_info a, ecs_oukoo_finance_clear b
		where is_finance_clear = 1 
		  and biaoju_store_id = 0 
		  and order_amount > real_paid
		  and a.finance_clear_type = b.finance_clear_type
		  and b.is_written_of = 1 
		group by finance_clear_type";
$qss['ys'] = $db->getAll($sql);
$sql = "select sum(order_amount-real_paid) AS amount
		from ecs_order_info a, ecs_oukoo_finance_clear b
		where is_finance_clear = 1 
		  and biaoju_store_id = 0 
		  and order_amount > real_paid
		  and a.finance_clear_type = b.finance_clear_type
		  and b.is_written_of = 1";
$qss['ys_sum'] = $db->getOne($sql);
$qss['sum'] = $qss['not_ys_sum'] + $qss['ys_sum'];

$smarty->assign('finance_clear', $finance_clear);
$smarty->assign('yishous', $yishou);
$smarty->assign('sum_yishou', $sum_yishou);
$smarty->assign('qss', $qss);
$smarty->assign('sum_gqws', $sum_gqws);
$smarty->assign('sum_dqws', $sum_dqws);
$smarty->display('oukooext/finance_ysws_all.htm');


?>