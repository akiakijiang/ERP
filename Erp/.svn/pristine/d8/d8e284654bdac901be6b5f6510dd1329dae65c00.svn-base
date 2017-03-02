<?php
/**
根据2014年5月22日的数据库快递公司和运送方式记录
方式由何建成提供

上海：
圆通 中通 汇通 顺丰  EMS  宅急送 优速  宅急便 盛际物流
伊藤忠要发 申通 亚马逊COD－ECCO
北京：
宅急送  申通   优速
东莞：
顺丰 EMS  圆通 中通 申通 
	三雄极光啥的有外包 韵达快递 
	蓝光 跨越速运 德邦物流
**/
$facilities_in_shanghai_not=array(
	'上海伊藤忠SCN' => 100170592,
	'上海伊藤忠' => 76161272,
	'依云HOD上海' =>	100170588,
	'电商服务上海仓（亨氏召回仓）' => 105983744,
	'怀轩上海仓' => 12768420,
	'夏娃上海百世仓' => 21843472,
	'欧酷上海仓' => 74539,
	'上海协同仓' => 89054427,
	'天猫超市（上海）' => 	69897656,
	'乐其上海仓' => 3633071,
);
$facilities_in_shanghai=array(
	'电商服务上海仓' => 19568549,
	'电商服务上海仓_2（原电商服务杭州仓）' => 22143847,
	'乐其上海仓_2（原乐其杭州仓）' =>22143846, 
	'康贝分销上海仓' => 81569822,
	'通用商品上海仓' => 83077348,
	'贝亲青浦仓'=>24196974,
);
$facilities_in_beijing_not=array(
	'依云HOD北京' => 100170589,
);
$facilities_in_chengdu_not=array();
$facilities_in_wuhan_not=array();


$facilities_in_beijing=array(
	'乐其北京仓' => 42741887,
	'电商服务北京仓' => 79256821,
	'通用商品北京仓' => 83077350,
);
$facilities_in_dongguan_not=array(
	'依云HOD东莞' => 105142919,
	'欧酷东莞仓' => 3580046,
	'海外业务东莞仓' => 53316284,
);
$facilities_in_dongguan=array(
	'电商服务东莞仓' => 19568548,
	'乐其东莞仓' => 3580047,
	'东莞乐贝仓' => 49858449,
	'电商服务东莞仓2' => 76065524,
	'通用商品东莞仓' => 83077349,
);
$facilities_in_chengdu=array(
	'电商服务成都仓' => 137059428,
	'通用商品成都仓' => 137059431,
);
$facilities_in_wuhan=array(
	'电商服务武汉仓'=>137059427,
	'通用商品武汉仓'=>137059430,
);

$carrier_for_shanghai=array(
	'圆通'=>	3,
	'邮政EMS'=> 9,
	'顺丰快递'=> 10,
	'顺丰COD-babynes'=>59,
	//'宅急送'=> 5,//20140624 按照何建成说的屏蔽
	'宅急送COD'=> 15,
	'顺丰快递COD'=> 17,
	'汇通'=>	 28,
	'顺丰GAT'=> 34,
	'中通快递'=> 41,
	'EMS经济型'=> 43,
	'顺丰快递（陆运）'=> 44,
	'顺丰陆运—淘宝COD'=> 47,
	'顺丰空运—淘宝COD'=> 48,
	'优速COD'=> 51,
	'中通—到付'=>	 52,
	'顺丰—到付'=>	 53,
	'EMS-到付'=>	 55,
	// '申通'=> 20,
	'申通COD'=> 31,
	'申通—到付'=> 54,
	'宅急便'=> 58,
	'速尔快递'=> 35,
	'韵达'=>29,
	'京东COD'=>62,
	'京东配送'=>63,
	'城市一百COD'=>64,
	'万象物流'=>13,
);
$carrier_for_beijing=array(
	// '宅急送'=> 5,
	// '宅急送COD'=> 15,
	// '申通'=> 20,
	// '申通COD'=> 31,
	// '申通—到付'=> 54,
	// '优速COD'=> 51,
	// '韵达'=>29,
	'汇通'=>	 28,
	'速达快递'=> 61,
	'顺丰快递'=> 10,
	'顺丰（陆运）'=> 44,
);
$carrier_for_dongguan=array(
	'顺丰快递'=> 10,
	'顺丰快递COD'=> 17,
	'顺丰GAT'=> 34,
	'顺丰快递（陆运）'=> 44,
	'顺丰陆运—淘宝COD'=> 47,
	'顺丰空运—淘宝COD'=> 48,
	'顺丰—到付'=>	 53,
	// '邮政EMS'=> 9,
	// 'EMS经济型'=> 43,
	'EMS-到付'=>	 55,
	'圆通'=>	3,
	// '中通快递'=> 41,
	'中通—到付'=>	 52,
	'申通'=> 20,
	'申通COD'=> 31,
	'申通—到付'=> 54,
	'韵达'=>29,
	'德邦快递'=>32,
	'德邦物流'=>46,
	'跨越速运'=>37,
);
$carrier_for_chengdu=array(
	'中通快递'=> 41,
	'邮政国内小包'=>45,
);
$carrier_for_wuhan=array(
	'圆通'=>	3,
);

$shipping_for_shanghai=array(
	//'宅急送快递'=>	12,//20140624 按照何建成说的屏蔽
	'宅急送快递_货到付款'=>	11,
	'EMS快递_货到付款'=>	36,
	'顺丰COD-babynes'=> 134,
	'顺丰COD-惠氏'=> 135,
	'顺丰快递'=>	44,
	'EMS快递'=>	47,
	'顺丰快递_COD	'=>49,
	'圆通快递	'=>85,
	'顺丰快递_COD_2'=>	96,
	'顺丰快递_货到付款'=>	97,
	'汇通快递'=>99,
	'顺丰GAT'=>	106,
	'中通快递'=>	115,
	'顺丰（陆运）'=>	117,
	'EMS经济快递'=>	118,
	'顺丰空运—淘宝COD'=>	121,
	'顺丰陆运—淘宝COD'=>	122,
	'优速COD'=>	125,
	'中通—到付'=>	126,
	'顺丰—到付'=>	127,
	'EMS-到付'=>	129,
	// '申通快递'=>	89,
	'申通快递_货到付款'=>	102,
	'申通—到付'=>	128,
	'亚马逊COD－ECCO'=>130,
	'内部员工自提'=>86,
	'盛际物流' => 131,
	'宅急便' => 132,
	'汇通—到付' => 133,
	'金佰利顺丰' => 136,
	'速尔快递'=> 108,
	'顺丰COD-安满' =>144,
	'韵达快递'=>100,
	'京东COD'=>146,
	'京东配送'=>149,
	'城市一百COD'=>150,
	'万象物流'=>51,
);
$shipping_for_beijing=array(
	// '宅急送快递'=>	12,
	// '宅急送快递_货到付款'=>	11,
	// '申通快递'=>	89,
	// '申通快递_货到付款'=>	102,
	// '优速COD'=>	125,
	// '申通—到付'=>	128,
	// '韵达快递'=> 100,
	'汇通快递'=>99,
	'顺丰快递'=>44,
	'顺丰（陆运）'=>	117,
	// '金佰利顺丰' => 136,
	'速达快递'=> 145,
);
$shipping_for_dongguan=array(
	'EMS快递_货到付款'=>	36,
	'顺丰快递'=>44,
	// 'EMS快递'=>	47,
	'顺丰快递_COD'=>	49,
	'圆通快递'=>	85,
	'申通快递	'=>89,
	'顺丰快递_COD_2'=>	96,
	'顺丰快递_货到付款'=>	97,
	'申通快递_货到付款'=>	102,
	'顺丰GAT'=>	106,
	// '中通快递'=>	115,
	'顺丰（陆运）'=>	117,
	// 'EMS经济快递'=>	118,
	'顺丰空运—淘宝COD'=>	121,
	'顺丰陆运—淘宝COD'=>	122,
	'中通—到付'=>	126,
	'顺丰—到付'=>	127,
	'申通—到付'=>	128,
	'EMS-到付'=>	129,
	'韵达快递'=> 100,
	'跨越速运'=>109,
	'德邦物流'=>120,
	'内部员工自提'=>86,
	'金佰利顺丰' => 136,
);
$shipping_for_chengdu=array(
	'中通快递'		=>	115,
	'邮政国内小包'	=>	119,
);
$shipping_for_wuhan=array(
	'圆通快递'=>	85,
);

function can_facility_use_carrier($facility_id, $carrier_id){
	global $facilities_in_shanghai;
	global $facilities_in_beijing;
	global $facilities_in_dongguan;
	global $facilities_in_chengdu;
	global $facilities_in_wuhan;

	global $facilities_in_shanghai_not;
	global $facilities_in_beijing_not;
	global $facilities_in_dongguan_not;
	global $facilities_in_chengdu_not;
	global $facilities_in_wuhan_not;

	global $carrier_for_shanghai;
	global $carrier_for_beijing;
	global $carrier_for_dongguan;
	global $carrier_for_chengdu;
	global $carrier_for_wuhan;

	if(
		!in_array($facility_id, $facilities_in_shanghai) &&
		!in_array($facility_id, $facilities_in_beijing) &&
		!in_array($facility_id, $facilities_in_dongguan) &&
		!in_array($facility_id, $facilities_in_chengdu) &&
		!in_array($facility_id, $facilities_in_wuhan) 
	)return true;
	
	if(
		(in_array($facility_id, $facilities_in_shanghai) && in_array($carrier_id, $carrier_for_shanghai)) ||
		(in_array($facility_id, $facilities_in_beijing) && in_array($carrier_id, $carrier_for_beijing)) ||
		(in_array($facility_id, $facilities_in_dongguan) && in_array($carrier_id, $carrier_for_dongguan)) ||
		(in_array($facility_id, $facilities_in_chengdu) && in_array($carrier_id, $carrier_for_chengdu)) ||
		(in_array($facility_id, $facilities_in_wuhan) && in_array($carrier_id, $carrier_for_wuhan)) 
	){
		return true;
	}else{
		return false;
	}
}

function can_facility_use_shipping($facility_id, $shipping_id){
	global $facilities_in_shanghai;
	global $facilities_in_beijing;
	global $facilities_in_dongguan;
	global $facilities_in_chengdu;
	global $facilities_in_wuhan;

	global $facilities_in_shanghai_not;
	global $facilities_in_beijing_not;
	global $facilities_in_dongguan_not;
	global $facilities_in_chengdu_not;
	global $facilities_in_wuhan_not;

	global $shipping_for_shanghai;
	global $shipping_for_beijing;
	global $shipping_for_dongguan;
	global $shipping_for_chengdu;
	global $shipping_for_wuhan;

	if(
		!in_array($facility_id, $facilities_in_shanghai) &&
		!in_array($facility_id, $facilities_in_beijing) &&
		!in_array($facility_id, $facilities_in_dongguan) &&
		!in_array($facility_id, $facilities_in_chengdu) &&
		!in_array($facility_id, $facilities_in_wuhan) 
	)return true;
	
	if(
		(in_array($facility_id, $facilities_in_shanghai) && in_array($shipping_id, $shipping_for_shanghai)) ||
		(in_array($facility_id, $facilities_in_beijing) && in_array($shipping_id, $shipping_for_beijing)) ||
		(in_array($facility_id, $facilities_in_dongguan) && in_array($shipping_id, $shipping_for_dongguan)) ||
		(in_array($facility_id, $facilities_in_chengdu) && in_array($shipping_id, $shipping_for_chengdu)) ||
		(in_array($facility_id, $facilities_in_wuhan) && in_array($shipping_id, $shipping_for_wuhan))
	){
		return true;
	}else{
		return false;
	}
}

/*
TEST
*/

if($_REQUEST['act']=='ajax'){
	$fid=(!empty($_REQUEST['facility_id']))?trim($_REQUEST['facility_id']):0;
	$cid=(!empty($_REQUEST['carrier_id']))?trim($_REQUEST['carrier_id']):0;
	$sid=(!empty($_REQUEST['shipping_id']))?trim($_REQUEST['shipping_id']):0;
	/*
	echo "FID=".$fid." cid=".$cid." sid=".$sid."<br>";
	echo (can_facility_use_carrier($fid,$cid))?"FC-YES":"FC-NO";
	echo "<br>"; 
	echo (can_facility_use_shipping($fid,$sid))?"FS-YES":"FS-NO";
	*/
	if(!empty($fid)){
		if(!empty($cid)){
			echo (can_facility_use_carrier($fid,$cid))?"FC-YES":"FC-NO";
		}
		if(!empty($sid)){
			echo (can_facility_use_shipping($fid,$sid))?"FS-YES":"FS-NO";
		}
	}
	die();
}
else if($_REQUEST['act']=='list'){
	echo "
		<h1>订单确认时各仓库支持的快递列表</h1>
		<p>根据2014年5月22日的数据库快递公司和运送方式记录作出以下限制。方式由何建成提供。</p>
		<p>根据2014年5月23日客服的反馈，根据实际情况有所不同，作出更新。</p>
		<table style='border: 1px solid gray;
				border-collapse:collapse;
				font-size: 13px;
				text-align: center;'
				border='1'>
			<tr>
				<th>仓库驻地</th>
				<th>仓库</th>
				<th>运送</th>
				<th>快递</th>
			</tr>
			<tr>
				<td>上海</td>
	";
	echo "<td>";
	foreach ($facilities_in_shanghai as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "<td>";
	foreach ($shipping_for_shanghai as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "<td>";
	foreach ($carrier_for_shanghai as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "
			</tr>
			<tr>
				<td>北京</td>
	";
	echo "<td>";
	foreach ($facilities_in_beijing as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "<td>";
	foreach ($shipping_for_beijing as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "<td>";
	foreach ($carrier_for_beijing as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "
			</tr>
			<tr>
				<td>东莞</td>
	";
	echo "<td>";
	foreach ($facilities_in_dongguan as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "<td>";
	foreach ($shipping_for_dongguan as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "<td>";
	foreach ($carrier_for_dongguan as $key => $value) {
		echo "<p>$key($value)</p>";
	}
	echo "</td>";
	echo "
			</tr>
	";
	echo "
		</table>
	";
}

?>