<?php

/**
All Hail Sinri Edogawa!
When Leqee changed its place
All the phones dwell as well
Lost their value and meaning
Come Sinri saith Allah to go 
Let there be rightness again
**/

/**
【ERP需求】 以业务组织为出发维度，对该业务组织发出去的快递单上的联系方式进行更新
ljni@leqee.com 20140429
**/

require_once (ROOT_PATH . 'includes/lib_service.php');
require_once('function.php');

$_PARTY_X_TELEPHONE = array(
	"金佰利-分销"=>"0571-28329360",
	"乐其电教-直销"=>"0571-28329360",
	"乐其电教-分销"=>"0571-28329360",
	"新百伦-分销"=>"0571-28329360",
	"康贝-分销"=>"0571-28329360",
	"金奇仕-分销"=>"0571-28329360",
	"安满-分销"=>"0571-28329360",
	"blackmores-分销"=>"0571-28329360",
	"金佰利-直销"=>"0571-28329302",//28280633",
	"康贝-直销"=>"0571-28181301",//28181303",
	"ecco-直销"=>"0571-28181303",//28181301",
	"玛氏-直销"=>"0571-28181301",//28181303",
	"AMEDA 阿美达-直销"=>"0571-28181301",//28181303",
	"皇上皇-直销"=>"0571-28280631",
	"保乐力加-直销"=>"0571-28280631",
	"亨氏-直销"=>"0571-28333569",
	"金奇仕-直销"=>"0571-28280631",
	"百威英博-直销"=>"0571-28280631",
	"雀巢-直销"=>"0571-28181305",
	"百事-直销"=>"0571-28181305",
	"blackmores-直销"=>"0571-28181305",
	"gallo-直销"=>"0571-28181305",
	"金宝贝-直销"=>"0571-28181306",
	"黄色小鸭-直销"=>"0571-28181306",
	"libbey-直销"=>"0571-28329306",
	"依云-直销"=>"0571-28329306",
	"babynes-直销"=>"0571-28333568",
	"babynes-分销"=>"0571-28333568",
	"贝亲-直销"=>"0571-61312050",
	"安满-直销"=>"0571-28189821",
	"安怡-直销"=>"0571-28189821",
	"纷时乐-直销"=>"0571-28329319",
	"yukiwenzi-直销"=>"0571-28189821",
	"人头马-直销"=>"0571-28280632",
	"荷乐-直销"=>"0571-28280632",
	"惠氏-直销"=>"0571-28333568",
	"Origins悦木之源-直销"=>"0571-28329368",
	"LA MER海蓝之谜-直销"=>"400-967-8228",
	"哥伦布计划-直销"=>"0571-283293192",
	"乐其蓝光-直销"=>"0769-81552122  4006-808-393",
	"乐其蓝光-分销"=>"0769-81552122  4006-808-393",
	"Wakodo和光堂-直销"=>"0571-28181305",
	"Milkana百吉福-直销"=>"0571-28329326",
	"BM care葆艾-分销"=>"0571-28329370",
	"BM care葆艾-直销"=>"0571-28329370",
	"Bobbi Brown-直销"=>"400-163-6102",
	"川宁-直销"=>"0571-28333592",
	"媛本-直销"=>"0571-28329303",
	"花王-直销"=>"0571-22338834",
	"汉高-直销"=>"0571-22338834",
	"SOFINA苏菲娜-直销"=>"0571-22338834", 
	"资生堂/SHISEIDO-直销"=>"0571-22338834", 
	"花王海外-直销"=>"0571-28331385", 
	"亨氏海外-直销"=>"0571-28331385", 
	"金佰利海外-直销"=>"0571-28331385", 
	"Blackmores海外-直销"=>"0571-28333581", 
	"卡夫亨氏-直销"=>"0571-28333569", 
	"联合利华海外-直销"=>"0571-28333581", 
	"妮维雅海外-直销"=>"0571-28333581", 
	"kanebo海外-直销"=>"0571-28333581", 
	"La Prairie 莱珀妮-直销"=>"400-853-1616", 
	"RoyalNectar海外-直销"=>"0571-28333581", 
);

$_DISTRIBUTOR_X_TELEPHONE = array(
	"拼好货商城-直销"=>"0571-28329319",
	"惠氏妈妈俱乐部微商城-直销"=>"0571-22338834",
	"huggies好奇旗舰店-直销"=>"0571-28280633",
	"顺丰优选-好奇-直销"=>"0571-28333580",
	"OPPO影音官方旗舰店供销平台-直销"=>"0769-81552122",
	"oppo影音官方旗舰店-直销"=>"0769-81552122",
	"blackmores海外官方旗舰店（苏宁海购）-直销"=>"0571-28181305",
	"blackmores蜜芽宝贝-直销"=>"0571-28181305",
	"blackmores海外专卖店（京东全球购）-直销"=>"0571-28181305",
);

$_DEFAULT_TEL='0571-28329360';

function sinri_get_telephone_for_order_to_print($order_id){
	global $db;
	global $_PARTY_X_TELEPHONE,$_DISTRIBUTOR_X_TELEPHONE;
	global $_DEFAULT_TEL;
	$sql="SELECT
				o.party_id,md.type,
				p. NAME as party_name,d.name as distributor_name
			FROM
				ecshop.ecs_order_info o
			LEFT JOIN ecshop.distributor d ON o.distributor_id = d.distributor_id
			LEFT JOIN ecshop.main_distributor md ON md.main_distributor_id = d.main_distributor_id
			LEFT JOIN romeo.party p ON o.party_id = p.PARTY_ID
			WHERE
				o.order_id = '$order_id';
	";
	$r=$db->getRow($sql);
	$party_name=$r['party_name'];
	$distributor_name = $r['distributor_name'];
	
	$zhi_fen_xiao=($r['type']=='zhixiao'?'直销':'分销');
		
	// 哥伦布计划为分销商级别，默认值也设置下
	if(in_array($r['party_id'],array('65629','65617','65558'))) {
		$tel=$_DISTRIBUTOR_X_TELEPHONE[$distributor_name."-".$zhi_fen_xiao];
	}
	
	if(empty($tel)) {
		$tel=$_PARTY_X_TELEPHONE[$party_name."-".$zhi_fen_xiao];
	}
	
	if(empty($tel))$tel=$_DEFAULT_TEL;
	return $tel;
}

/**
goods_type
**/

$_PARTY_X_GOODS_TYPE = array(
	"金佰利"=>"母婴用品",
	 "乐其电教"=>"点读机",
	 "康贝"=>"母婴用品",
	 "金奇仕"=>"保健品",
	 "blackmores"=>"保健品",
	 "ecco"=>"鞋",
	 "玛氏宠物食品"=>"宠物食品",
	 "AMEDA 阿美达"=>"母婴用品",
	 "皇上皇"=>"食品",
	 "保乐力加"=>"食品",
	 "亨氏"=>"母婴食品",
	 "百威英博"=>"食品",
	 "雀巢"=>"食品",
	 "百事"=>"食品",
	 "金宝贝"=>"衣服",
	 "黄色小鸭"=>"母婴用品",
	 "libbey"=>"杯子",
	 "依云"=>"食品",
	 "贝亲"=>"母婴用品",
	 "安满"=>"奶粉",
	 "安怡"=>"奶粉",
	 "人头马"=>"食品",
	 "荷乐"=>"奶粉",
	 "惠氏"=>"奶粉",
	 "Origins悦木之源"=>"化妆品",
	 "LA MER海蓝之谜"=>"护肤品",
	 "乐其蓝光"=>"机器",
	 "Wakodo和光堂"=>"奶粉",
	 "Milkana百吉福"=>"奶酪",
	 "BM care葆艾"=>"母婴用品",
	 "Bobbi Brown"=>"化妆品",
	 "川宁"=>"食品",
	 "媛本"=>"食品",
	 "安百施"=>"母婴用品",
	 "babynes"=>"母婴用品",
	 "百乐顺"=>"食品",
	 "纷时乐"=>"食品",
	 "香港BLACKMORES"=>"保健品",
	 "乐其蓝光"=>"电子产品",
	 "台湾哆啦"=>"食品",
	 "惠氏海外"=>"奶粉",
	 "Quaker/桂格"=>"食品",
	 "亨氏香港"=>"食品",
	 "Marco"=>"文具",
	 "Dragonfly"=>"母婴用品",
	 "千趣会"=>"食品",
	 "汉高"=>"洗护",
	 "SOFINA苏菲娜"=>"化妆品",
	 "资生堂/SHISEIDO"=>"美妆",
	 "花王海外"=>"乐其跨境  纸尿裤", 
	 "亨氏海外"=>"乐其跨境  食品", 
	 "金佰利海外"=>"乐其跨境  纸尿裤", 
	 "Coop海外"=>"乐其跨境  食品", 
	 "Blackmores海外"=>"乐其跨境   婴幼儿奶粉、孕产妇营养品、婴幼儿营养品",
	 "卡夫亨氏"=>"卡夫亨氏  调味品",
	 "联合利华海外"=>"乐其跨境   洗护类",
	"妮维雅海外"=>"乐其跨境   洗护类", 
	"kanebo海外"=>"乐其跨境",
	"La Prairie 莱珀妮"=>" 化妆品",
	"RoyalNectar海外"=>"乐其跨境",
);

$_DISTRIBUTOR_X_GOODS_TYPE = array(
	"拼好货商城"=>"水果",
	"惠氏妈妈俱乐部微商城"=>"母婴用品",
	"顺丰优选-好奇"=>"母婴（纸尿裤）",
	"huggies好奇海外旗舰店（天猫国际）"=>"乐其跨境  纸尿裤",
	"高洁丝官方海外旗舰店 （天猫国际）"=>"乐其跨境  卫生巾",
	"coop海外旗舰店（天猫国际）"=>"COOP海外旗舰店  食品",
	"blackmores海外官方旗舰店（苏宁海购）"=>"膳食营养补充食品",
	"blackmores蜜芽宝贝"=>"膳食营养补充食品",
	"blackmores海外专卖店（京东全球购）"=>"膳食营养补充食品",
	
);

$_DEFAULT_GOODS_TYPE="商品";

function sinri_get_goods_type_for_party_to_print($party_id,$distributor_id=null){
	global $db;
	global $_PARTY_X_GOODS_TYPE,$_DISTRIBUTOR_X_GOODS_TYPE;
	global $_DEFAULT_GOODS_TYPE;

    // 分销商控制的以分销商为准
    if(in_array($party_id,array('65629','65617','65558')) && !empty($distributor_id)) {
    	$sql="SELECT name FROM ecshop.distributor WHERE distributor_id = '{$distributor_id}' ";
		$distributor_name=$db->getOne($sql);
		if(!empty($distributor_name)){
			$gn=$_DISTRIBUTOR_X_GOODS_TYPE[$distributor_name];
		}
    }
    
    // 分销商映射找不到，从组织中找
    if(empty($gn)) {
    	$sql="SELECT p.NAME FROM romeo.party p WHERE p.PARTY_ID = '{$party_id}' ";
		$pn=$db->getOne($sql);
		if($pn && $pn!=''){
			$gn=$_PARTY_X_GOODS_TYPE[$pn];
		}
    }

	if(!$gn || $gn==''){
		$gn=$_DEFAULT_GOODS_TYPE;
	}
	return $gn;
}

?>