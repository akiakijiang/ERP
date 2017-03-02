<?php
define('IN_ECS', true);
require('includes/init.php');
check_party();
admin_priv('brand_integration_monitor');

if (in_array($_SESSION['party_id'], array(/*'65619', */'65628','65639'))) { //雅诗兰黛旗下，暂时只用Lamer
	header("Location:brand_integration_monitor_estee.php?act=search");
} else {
	header("Location:brand_integration_monitor_".$_SESSION['party_id'].".php?act=search");
}



function check_party(){
	if(!in_array($_SESSION['party_id'], array('65622','65619', '65628','65625','65639'))){	//	建议囤满3个后与‘品牌商商品维护’功能一起维护到数据库
		$sql = "select name from romeo.party where party_id = {$_SESSION['party_id']}";
		global $db;
		$party_name = $db->getOne($sql);
		sys_msg("当前组织[".$party_name."]无需对接监控");
	}
}
?>