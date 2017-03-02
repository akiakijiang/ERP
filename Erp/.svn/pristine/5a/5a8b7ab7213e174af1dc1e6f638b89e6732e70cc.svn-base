<?php
define('IN_ECS', true);
require('includes/init.php');
check_party();
admin_priv('brand_goods_maintain');

if (in_array($_SESSION['party_id'], array(/*'65619', */'65628','65639'))) { //雅诗兰黛旗下，暂时只用Lamer Bobbi 65639
	header("Location:brand_goods_maintain_estee.php?act=search");
} else {
	header("Location:brand_goods_maintain_".$_SESSION['party_id'].".php?act=search");
}


function check_party(){
	if(!in_array($_SESSION['party_id'], array('65619','65628','65639'))){	//	建议囤满3个需要维护的组织建数据表来存  //bobbi 65639
		$sql = "select name from romeo.party where party_id = {$_SESSION['party_id']}";
		global $db;
		$party_name = $db->getOne($sql);
		sys_msg("当前组织[".$party_name."]无需额外维护品牌商商品");
	}
}
?>