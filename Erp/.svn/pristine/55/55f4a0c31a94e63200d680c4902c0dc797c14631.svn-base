<?php
define('IN_ECS', true);

require('../includes/init.php');
$bwshop_list = "";
$party = $_SESSION['party_id'];
if($_REQUEST['act']){
	$outer_order_sn = $_REQUEST['outer_order_sn'];
	$transfer_status = $_REQUEST['transfer_status'];
	$bwshop_list = BwshopDo::BwshopSearch($outer_order_sn,$transfer_status);

}
// var_dump($bwshop_list);
$smarty->assign("party",$party);
$smarty->assign("bwshop_list",$bwshop_list);
$smarty->display("bwshop_show.html");

class BwshopDo{
	public static function BwshopSearch($outer_order_sn,$transfer_status){
		if($transfer_status == "ALL"){
			$data = " outer_order_sn = '{$outer_order_sn}'";
		}
		elseif($outer_order_sn == ""){
			$data = " transfer_status = '{$transfer_status}'";
		}else{
			$data = " outer_order_sn = '{$outer_order_sn}' AND transfer_status = '{$transfer_status}'";
		}
		$party = $_SESSION['party_id'];
		// $party = "65640";
		global $db;
		$sql = "SELECT
			bw.outer_order_sn,
			shop.shop_name,
			bw.create_time,
			bw.update_time,
			bw.transfer_status,
			bw.transfer_note
		FROM
			ecshop.bw_order_info bw
		LEFT JOIN ecshop.bw_shop shop ON bw.shop_id = shop.shop_id
		WHERE 1
		AND 
		".$data."
		 AND shop.party_id='{$party}'
		 ";

		 $bwshop_list = $db->getAll($sql);
		 return empty($bwshop_list) ? array():$bwshop_list;
	}
}
?>