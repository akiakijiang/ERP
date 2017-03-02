<?php
/*
 * Coder: Brook Ji Date: 2014-07-11 Note: 根据qdi原始代码修改 利用excel导入的时候，一定要记得检查party_id对不对
 */
define ( "IN_ECS", true );
require_once ('../includes/init.php');
require_once (ROOT_PATH . "/RomeoApi/lib_inventory.php");
require (ROOT_PATH . "/includes/lib_order.php");
include_once (ROOT_PATH . 'includes/cls_json.php');
include_once ('../includes/lib_function_lcji.php');
require_once ('../function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');


search_order(10);
exit();
/* 
 * 申请页必须在有分公司的情况下才可以查看 
 * 
 * */
if (! party_explicit ( $_SESSION ['party_id'] )) {
	die ( "页面加载失败，请选择正确的分公司！" );
}

/* 
 * 现在容许每个人查看该页，但是只有店员和店长才可以提交申请
 * 
if (!checkVWatch ()) {
	die ( "非常抱歉，您没有权限查看该页" );
} */


/* 
 * 创建订单 
 * $error_message_array_smarty 将作为返回信息显示到页面上
 * 
 * */
if ($_POST ["act"] == "create") {
	if (! checkVApply ( )) {
		die ( "对不起，您没有-v申请权限" );
	}
	$result = create_application (); // 返回的数据时没有执行成功的
	$return_just_now_order = array();
	if (count($result) == 2) {
		$return_just_now_order[] = $result[1][0];
	}else{
		$return_just_now_order[] = $result[1][0];
		$return_just_now_order[] = $result[1][1];
	}
	
	$error_message_array_smarty = $result[0];
}




/* 用户下载csv文件，csv文件是查询出的申请订单
 * 该功能整合到search页面中，目前本页面已经不再使用，统一使用search页面的downcsv方法

if ($_POST ["act"] == "downcsv") {
	downcsv ();
	exit ();
} 
*/

/* 
 * 搜索商品 ，前端使用autocomplete，传来的参数名字是q
 * 功能是根据用户输入的关键字，自动搜索出商品全称，同时还有goods_id style_id
 * 
 * */

if ($_REQUEST ["act"] == "search_goods") {
	$json = new JSON ();
	require_once ('../function.php');
	$limit = 40;
	print $json->encode ( get_goods_list_like_lcji ( $_REQUEST ['q'], $limit ) );
	exit ();
}

/*
 * 搜索价格，在给定的商品goods_id style_id的情况下，搜索价格
 * 
 * */

if ($_GET ["act"] == "search_price") {
	searchprice ();
	exit ();
}

/*
 * 搜索序列号，在给定的商品goods_id style_id的情况下，搜索序列号
 *
 * */

if ($_GET ["act"] == "searchserialnumber") {
	$limit = 20;
	search_serial_number ( $_REQUEST ['q'], $limit );
	exit ();
}

/*
 * 搜索是否是序列号商品，在给定的商品goods_id style_id的情况下，搜索是否是序列号商品
 * 返回 SERIALIZE   NON-SERIALIZED
 *
 * */

if ($_GET ["act"] == "isserial") {
	$json = new JSON ();
	$sql = "select inventory_item_type_id from romeo.inventory_item ii 
			inner join romeo.product_mapping pm on pm.product_id = ii.product_id 
			where pm.ecs_goods_id = '{$_REQUEST["goods_id"]}' 
			and pm.ecs_style_id = '{$_REQUEST["style_id"]}' limit 3";
	echo $json->encode ( $db->getOne ( $sql ) );
	exit ();
}

/*
 * 批量导入功能，用户通过excel文件批量导入到待审核列表中
 * 
 * */

if ($_REQUEST["act"] == "batchupload"){
	$return_array = apply_batch_upload();
	$json = new JSON ();
	echo $json->encode ( $return_array );
	exit();
}

/*
 * 创建订单中返回的操作信息，刚打开页面时，这个信息是空的
 *
 * */
$smarty->assign ( 'error_message_array', $error_message_array_smarty );

/*
 * 当前用户所在party_id
 */
$smarty->assign ( 'user_current_party_id', $_SESSION ['party_id'] );

/*
 * 当前用户已经申请的订单，如果没有按照时间查询，则返回最近申请的10个，如果按照时间查询，则返回时间段内的订单
 * 因为添加了搜索页面，该功能已经移交到搜索页面中处理
 * 
$smarty->assign ( 'already_apply_order', get_virance_order_info () );
*/

/*
 * 当前用户所在party_id对应的party_name
 */
$smarty->assign ( 'user_current_party_name', party_mapping ( $_SESSION ['party_id'] ) );

/*
 * 当前用户可以使用的仓库
 */
$smarty->assign ( 'available_facility', get_available_facility () );

/*
 * 所有订单状态列表
*/
$all_order_status = array(
		'APPLY' 	=>	'审核中',
		'COMPLETE'	=>	'审核完成',
		'PARTCOMPLETE'=>'审核完成，部分商品删除',
		'CANCEL'	=>	'已取消',
		'REFUSE'	=>	'已拒绝'
);
$smarty->assign ( 'all_order_status', $all_order_status );

/*
 * 所有审核状态列表
*/
$all_order_status = array(
		'0' 	=>	'申请中',
		'1'		=>	'店长审核中',
		'2'		=>	'BD审核中',
		'3'		=>	'财务审核中',
		'4'		=>	'执行CEO审核中',
		'5'		=>	'CEO审核中'
);
$smarty->assign ( 'all_order_status', $all_order_status );


/*
 * 返回刚刚审核成功的订单
*/
$smarty->assign( '$return_just_now_order', $return_just_now_order);

/*
 * 设定smarty模板
*/
$smarty->display ( 'virance_inventory/inventory_adjust_apply_v2.html' );


/*
 * 创建订单函数
 * @return array $error_message_array  创建订单过程中的操作信息
 * 
 * 这里的逻辑是，创建订单后，会重新刷新页面，$error_message_array会展示给用户
 */

function create_application() {
	global $db;
	
	/* 	
	 * status表示操作状态，0表示操作成功，没有错误，1表示操作过程中出现错误
	 * title显示操作的概述，“操作完结”表示没有错误，如果有错，会具体通知 
	 * msg是具体哪个商品操作出现了哪种错误
	 */	
	$error_message_array = array (
			status => 0,
			title => "操作完结",
			msg => array () 
	);
	
	/*
	 * 下面获得前台传来的具体商品参数
	 */	
	// 以下是字符串
	$facility_id = trim ( $_POST ['facility_id'] );
	$note = trim ( $_POST ['comment'] );
	$party_id = $_SESSION ["party_id"];
	
	// 以下是数组
	$goods_ids = $_POST ['goods_id']; //
	$style_ids = $_POST ['style_id']; //
	$counts = $_POST ['count'];
	$prices = $_POST ['price'];
	$status_ids = $_POST ['status']; //
	$types = $_POST ['type'];
	$serialnumbers = $_POST ['serialnumber'];
	$comments = $_POST ['reason'];
	
	/*
	 * added_cat是进行-v ADD操作的商品数组，cat是category的缩写
	 * minus_cat与之相对
	*/
	$added_cat = array ();
	$minus_cat = array ();
	
	/*
	 * 检查传来的参数是否有不合格的
	 */
	
	if (empty ( $goods_ids ) || empty ( $status_ids ) || ! isset ( $style_ids ) || empty( $facility_id ) || ! isset ( $prices ) || ! isset ( $counts ) || empty ( $types ) || empty ( $comments )) {
		$error_message_array ["title"] = "抱歉，请求参数存在空，请重新申请，如仍无法解决，请联系ERP。";
		return $error_message_array;
	}
	
	/*
	 * 传来多个商品-v，循环查看每个商品-v申请，ADD的加入到added_cat  MINUS加入到minus_cat数组中，然后调用insert_order进行创建vorder订单
	*/
	foreach ( $goods_ids as $key => $item ) {
		$goods_id = $goods_ids[$key]; 
		$style_id = $style_ids[$key]; 
		$count = $counts[$key];
		$price = $prices[$key];
		$status_id = $status_ids[$key]; 
		$type = $types[$key];
		$serialnumber = $serialnumbers[$key];
		$comment = $comments[$key];
		$amount = floatval ( $count ) * floatval ( $price );
		
		/*
		 * 获得商品名称，CONCAT_WS可以将goods_name和style_name进行组合
		*/
		$sql = "select CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color) ) as goods_name
				from ecshop.ecs_goods As g
				inner join romeo.product_mapping As pm on pm.ecs_goods_id = g.goods_id
				left join ecshop.ecs_goods_style AS gs ON gs.goods_id = g.goods_id and gs.is_delete=0
				left join ecshop.ecs_style As s on gs.style_id = s.style_id
				where pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}'";
		$goods_name = $db->getOne ( $sql );
		
		/*
		 * 检测每个-v商品参数是否正确，如果不正确，并不影响整体申请，只是该商品无法申请成功，其他商品正常走入申请流程，所以使用continue
		*/
		if (empty ( $goods_id ) || empty ( $status_id ) || ! isset ( $style_id ) || ! isset ( $facility_id ) || ! isset ( $price ) || ! isset ( $count ) || empty ( $type ) || empty ( $comment )) {
			$error_message_array ["title"] = "执行过程中如下商品出现错误";
			$error_message_array ["msg"] [] = "商品： “  " . $goods_name ."   ”申请过程中出现参数错误";
			continue;
		}
		
		/*
		 * 检测每个-v序列号商品，如果是序列号商品，那么count只能是-1和1，并且序列号必须指定，如果不是，就提示该商品申请出错，但是其他商品仍然进入正常申请流程
		*/
		if ($type == "SERIALIZED") {
			if ($count != - 1 && $count != 1) {
				$error_message_array ["title"] = "执行过程中如下商品信息出现错误";
				$error_message_array ["msg"] [] = "商品： “  " . $goods_name . "  ” 价格：“" . $price . "  ” 序列号商品，数量仅能-1 or 1";
				continue;
			}
			if (empty ( $serialnumber )) {
				$error_message_array ["title"] = "执行过程中如下商品信息出现错误";
				$error_message_array ["msg"] [] = "商品： “  " . $goods_name . "  ” 价格：“" . $price . "  ” 序列号商品，必须制定序列号";
				continue;
			}
		}
		
		/*
		 * -v ADD的非序列号商品，检测条件是必须按照指定的条件，在库存中存在，没有存在过的商品，没法进行ADD -v
		*/
		if ($count > 0 && $type == "NON-SERIALIZED") {
			$sql = "
			select count(ii.inventory_item_id) from romeo.inventory_item ii
			inner join romeo.product_mapping pm on pm.product_id = ii.product_id
			inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
			where ii.facility_id = '{$facility_id}'
			and pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}'
			and ii.unit_cost = '{$price}' and ii.inventory_item_type_id = '{$type}'
			and eg.goods_party_id = '{$party_id}'";
			if ($db->getOne ( $sql ) == 0) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品： “  " . $goods_name . "  ” 价格：“" . $price . "  ” 数据库中没有相关库存记录，请重试";
				continue;
			}
		} 
		
		/*
		 * -v ADD的序列号商品，检测条件是必须按照指定的条件，在库存中存在，并且总库存量必须是0，否则没法进行ADD -v
		*/
		elseif ($count > 0 && $type == "SERIALIZED") {
			$sql = "select sum(ii.quantity_on_hand_total ) as goods_count 
					from romeo.inventory_item ii 
					inner join romeo.product_mapping pm on pm.product_id = ii.product_id
					where ii.serial_number = '{$serialnumber}' 
					and pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}' 
					";
			$total_serial_count =$db->getOne($sql);
			if (!isset($total_serial_count) || $total_serial_count > 0) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品： “  " .$goods_name . "  ” 价格：“" . $price . "  ” (1)数据库中该序列号不存在或者序列号商品有库存" . $serialnumber . "";
				continue;
			}
			$sql = "
					select sum(ii.quantity_on_hand_total) as goods_count
					from romeo.inventory_item ii
					inner join romeo.product_mapping pm on pm.product_id = ii.product_id
					inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
					where ii.facility_id = '{$facility_id}'
					and pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}'
					and ii.unit_cost = '{$price}' and ii.inventory_item_type_id = '{$type}'
					and ii.serial_number = '{$serialnumber}'
					and ii.quantity_on_hand_total = 0
					and eg.goods_party_id = '{$party_id}'";
			$tmp = $db->getOne ( $sql );
			if (!isset($tmp) || $tmp > 0) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品： “  " .$goods_name . "  ” 价格：“" . $price . "  ” (2)数据库中该序列号存在，但是不满足您选定的条件" . $serialnumber . "有库存，请重试";
				continue;
			}
		} 
		
		/*
		 * -v MINUS的非序列号商品，检测条件是必须按照指定的条件，在库存中存在，并且总库存量必须大于申请的数量，否则没法进行MINUS -v
		*/
		elseif ($count < 0 && $type == "NON-SERIALIZED") {
			$sql = "select sum(ii.quantity_on_hand_total ) from romeo.inventory_item ii
					inner join romeo.product_mapping pm on pm.product_id = ii.product_id
					inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
					where ii.status_id = '{$status_id}' and ii.facility_id = '{$facility_id}'
					and pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}'
					and ii.unit_cost = '{$price}' and ii.inventory_item_type_id = '{$type}'
					and ii.quantity_on_hand_total > 0
					and eg.goods_party_id = '{$party_id}'
							";
			$tmp = $db->getOne ( $sql );
			if ($tmp < abs($count)) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品： “  " . $goods_name . "  ” 价格：“" . $price . "  ” 库存数量不足，请重试";
				continue;
			}
		} 
		
		/*
		 * -v MINUS的序列号商品，检测条件是必须按照指定的条件，在库存中存在，并且总库存量是1，否则没法进行MINUS -v
		*/
		elseif ($count < 0 && $type == "SERIALIZED") {
			$sql = "
					select sum(ii.quantity_on_hand_total ) as goods_count
					from romeo.inventory_item ii
					inner join romeo.product_mapping pm on pm.product_id = ii.product_id
					inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
					where ii.status_id = '{$status_id}' and ii.facility_id = '{$facility_id}'
					and pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}'
					and ii.unit_cost = '{$price}' and ii.inventory_item_type_id = '{$type}'
					and ii.serial_number = '{$serialnumber}'
					and ii.quantity_on_hand_total > 0
					and eg.goods_party_id = '{$party_id}' ";
			$tmp = $db->getOne ( $sql );
			if ($tmp != 1) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品： “  " . $goods_name . "  ” 价格：“" . $price . "  ” 数据库中没有相关库存记录或者该序列号" . $serialnumber . "无库存，请重试";
				continue;
			}
		} 
		
		/*
		 * -v 出现错误
		*/
		else {
			$error_message_array ["title"] = "执行过程中如下商品出现错误";
			$error_message_array ["msg"] [] = "商品： “  " . $goods_name . "  ” 价格：“" . $price . "  ” 判断数据库数量过程出现错误";
			continue;
		}
		
		/*
		 * 为每个商品组成一个array，记录该商品的-v信息，同时按照类型，存入不同的-v category数组，以便统一 -v
		*/
		$goods_array = array (
				goods_id => $goods_id, //
				style_id => $style_id, //
				count => $count,
				price => $price,
				status_id => $status_id, //
				type => $type,
				serialnumber => $serialnumber,
				comment => $comment,
				amount => $amount,
				facility_id => $facility_id,
				note => $note,
				party_id => $party_id 
		);
		
		if ($count > 0) {
			$added_cat [] = $goods_array;
		} elseif ($count < 0) {
			$minus_cat [] = $goods_array;
		}
	}
	$vorder_ids = array();
	if(count($added_cat) > 0)
		$vorder_ids[] = insert_vorder ( "ADD", $added_cat ); // return 1 表示类型检查失败
	if(count($minus_cat) > 0)
		$vorder_ids[] = insert_vorder ( "MINUS", $minus_cat );
	$return_just_now_order = get_just_now_order($vorder_ids);
	return array($error_message_array,$return_just_now_order);
}

/*
 * 原始用处：获得刚刚申请成功的订单
 * 现在用处：除了获得刚刚申请成功的订单，还可以获得查询出来的所有订单
 */
function get_just_now_order($vorder_ids){
	global $db;
	if (count($vorder_ids) == 0) {
		return null;
	}
	$return_just_now_order_array = array();
	foreach ( $vorder_ids as $vorder_id){
		$sql = "select info.vorder_request_id as vorder_id,info.vorder_status,info.check_status,info.create_stmp,info.party_id,party.name as party_name,info.facility_id,facility.facility_name,
				info.comments,info.inventory_adjust,info.v_category,item.goods_status,item.goods_name,item.goods_type_id,item.goods_number,item.goods_amount,item.reason,item.serial_number,
				if(info.step0_user_id = users.user_id,'1','0') as del
				from ecshop.ecs_vorder_request_info info 
				inner join ecshop.ecs_vorder_request_item item on info.vorder_request_id = item.vorder_request_id
				inner join romeo.party party on party.party_id = info.party_id
				inner join romeo.facility facility on facility.facility_id = info.facility_id
				left  join ecshop.ecs_admin_user users on users.user_id = info.step0_user_id
				where info.vorder_request_id = '{$vorder_id}'";
		$result = $db->getAll($sql);
		$result_item = array();
		if (!empty($result)) {
			$result_item ['vorder_id'] = $result[0]['vorder_id'];
			switch ($result[0]['vorder_status']){
				case "APPLY":
					$result_item ['vorder_status'] = '审核中';
					break;
				case "COMPLETE":
					$result_item ['vorder_status'] = '审核完成';
					break;
				case "PARTCOMPLETE":
					$result_item ['vorder_status'] = '审核完，部分商品删除';
					break;
				case "REFUSE":
					$result_item ['vorder_status'] = '审核失败';
					break;
				case "CANSEL":
					$result_item ['vorder_status'] = '已取消';
					break;
				default:
					$result_item ['vorder_status'] = $result [0]['vorder_status'];
			}
			switch ($result [0]['check_status']){
				case "0":
					$result_item ['check_status'] = '申请中';
					break;
				case "1":
					$result_item ['check_status'] = '店长审核中';
					break;
				case "2":
					$result_item ['check_status'] = 'BD审核中';
					break;
				case "3":
					$result_item ['check_status'] = '财务审核中';
					break;
				case "4":
					$result_item ['check_status'] = '执行CEO审核中';
					break;
				case "5":
					$result_item ['check_status'] = 'CEO审核中';
					break;
				default:
					$result_item ['check_status'] = $result [0]['check_status'];
			}
			$result_item ['create_stmp'] = $result [0]['create_stmp'];
			$result_item ['party_name'] = $result [0]['party_name'];
			$result_item ['facilit_name'] = $result [0]['facility_name'];
			$result_item ['comments'] = $result [0]['comments'];
			if ($result [0]['inventory_adjust'] == 0) {
				$result_item ['inventory_adjust'] = '未出库';
			}else{
				$result_item ['inventory_adjust'] = '已出库';
			}
			if ($result [0]['v_category'] == 'ADD') {
				$result_item ['v_category'] = '-v盘盈ADD';
			}else{
				$result_item ['v_category'] = '-v盘亏MINUS';
			}
			$result_item ['delete'] = $result [0]['del'];
			$goods_array = array();
			foreach ($result as $goods){
				$goods_array_item = array();
				$goods_array_item['goos_name'] = $goods ['goods_name'];
				$goods_array_item['goods_status'] = $goods ['goods_status'];
				$goods_array_item['goods_type_id'] = $goods ['goods_type_id'];
				$goods_array_item['goods_number'] = $goods ['goods_number'];
				$goods_array_item['goods_amount'] = $goods ['goods_amount'];
				$goods_array_item['reason'] = $goods ['reason'];
				$goods_array_item['serial_number'] = $goods ['serial_number'];
				$goods_array [] = $goods_array_item;
			}
			$result_item ['goods_detail'] = $goods_array;
			
			$result_item ['notes'] = get_order_check_comment($vorder_id);
			$return_just_now_order_array[] = $result_item;
		}
	}
	return $return_just_now_order_array;
}

/*
 * 搜索订单
 * @param $limit 一共搜索多少条
 */
function search_order($limit){
	global $db,$smarty;
	
	/*开始时间与结束时间必须同时选择*/
	$start_display = $_REQUEST['start_display'];
	$end_display = $_REQUEST['end_display'];
	if ((empty($start_display) && !empty($end_display)) || (!empty($start_display) && empty($end_display))) {
		die("开始时间和结束时间必须同时选择");
	}
	$order_status = $_REQUEST['order_status'];
	$allparty = $_REQUEST['allparty'];
	
	/*goods 和 style 必须同时选择*/
	$goods_id = $_REQUEST['goods_id'];
	$style_id = $_REQUEST['style_id'];
	if ((empty($goods_id) && !empty($style_id)) || (!empty($goods_id) && empty($style_id))) {
		die("商品选择参数出现错误");
	}
	
	$facility_id = $_REQUEST['facility_id'];
	$page = $_REQUEST['page'];
	if (empty($page)) {
		$page = 1;
	}
	
	/*
	 * 要把参数返回页面
	*/
	$smarty->assign("start_display",$start_display);
	$smarty->assign("end_display",$end_display);
	$smarty->assign("order_status",$order_status);
	$smarty->assign("allparty",$allparty);
	$smarty->assign("goods_id",$goods_id);
	$smarty->assign("style_id",$style_id);
	$smarty->assign("facility_id",$facility_id);
	
	/*
	 * 因为上面对共同出现的选项做了检测，这里仅仅判断其中之一就可以了
	 */
	$start = ($page-1) * $limit;
	if (empty($start_display) && empty($order_status) && empty($allparty) && empty($goods_id) && empty($facility_id) ) {
		//说明没有添加任何搜索条件
		$sql="select COUNT(vorder_request_id) as number from ecshop.ecs_vorder_request_info";
		//命中总数
		$count = $db->getOne($sql);
		$smarty->assign("pagecount",$count);
		$sql="select vorder_request_id from ecshop.ecs_vorder_request_info limit {$start},{$limit}";
		$result = $db->getAll($sql);
		$vorder_ids = array();
		if (empty($result)) {
			return null;
		}
		foreach ($result as $result_item){
			$vorder_ids [] = $result_item ['vorder_request_id'];
		}
		return get_just_now_order($vorder_ids);
	}
	/*
	 * 当并不是全部为空的订单相关搜索（不指定goods 和 style）
	 */
	elseif(empty($goods_id)){
		$sql_count = "select COUNT(info.vorder_request_id) as number from ecshop.ecs_vorder_request_info info ";
		$sql_result = "select distinct(vorder_request_id) as vorder_id from ecshop.ecs_vorder_request_info info ";
		$sql_tmp = "";
		if (!empty($goods_id)) {
			$sql = "select product_id from romeo.product_mapping pm where pm.ecs_goods_id = '{$goods_id}' and ecs_style_id = '{$style_id}'";
			$product_id = $db->getOne($sql);
			$sql_tmp .= "inner join ecshop.ecs_vorder_request_item item on item.vorder_request_id = info.vorder_request_id ";
			$sql_tmp .= "where item.product_id = '{$product_id}' ";
			
		}
		
		if (!empty($start_display)) {
			if ($sql_tmp == "") {
				$sql_tmp .= " where to_days(info.create_stmp) >= to_days('{$start_display}')
							and to_days(info.create_stmp) <= to_days('{$end_display}') ";
			}else{
				$sql_tmp .= " and to_days(info.create_stmp) >= to_days('{$start_display}')
						and to_days(info.create_stmp) <= to_days('{$end_display}') ";
			}
		}
		if (!empty($facility_id)) {
			if ($sql_tmp == "") {
				$sql_tmp .= " where info.facility_id = '{$facility_id}' ";;
			}else{
				$sql_tmp .= " and info.facility_id = '{$facility_id}' ";
			}
		}
		if (!empty($order_status)) {
			if ($sql_tmp == "") {
				$sql_tmp .= " where info.vorder_status = '{$order_status}' ";;
			}else{
				$sql_tmp .= " and info.vorder_status = '{$order_status}' ";
			}
		}
		if (empty($allparty)) {
			if ($sql_tmp == "") {
				$sql_tmp .= " where info.party_id = '{$_SESSION["party_id"]}' ";;
			}else{
				$sql_tmp .= " and info.party_id = '{$_SESSION["party_id"]}' ";
			};
		}
		$sql_count .= $sql_tmp;
		$sql_result .= $sql_tmp." limit {$start},{$limit}";
		$count = $db->getOne($sql_count);
		$result = $db->getAll($sql_result);
		$vorder_ids = array();
		if (empty($result)) {
			return null;
		}
		foreach ($result as $result_item){
			$vorder_ids [] = $result_item ['vorder_request_id'];
		}
		return get_just_now_order($vorder_ids);
		$smarty->assign("pagecount",$count);
	}
	/*
	 * 当指定goods 和 style
	*/
	elseif(!empty($goods_id)){
		
	}
	exit();
}


/*
 * 获得check过程过，各个环节对order的意见
 */
function get_order_check_comment($vorder_id){
	global $db;
	$sql = "select info.step0_status,users0.user_name as step0_name,info.step0_time,info.step0_comment,
			info.step1_status,users1.user_name as step1_name,info.step1_time,info.step1_comment,
			info.step2_status,users2.user_name as step2_name,info.step2_time,info.step2_comment,
			info.step3_status,users3.user_name as step3_name,info.step3_time,info.step3_comment,
			info.step4_status,users4.user_name as step4_name,info.step4_time,info.step4_comment,
			info.step5_status,users5.user_name as step5_name,info.step5_time,info.step5_comment
			from ecshop.ecs_vorder_request_info info
			INNER JOIN ecshop.ecs_admin_user users0 on users0.user_id = info.step0_user_id
			left  join ecshop.ecs_admin_user users1 on users1.user_id = info.step1_user_id
			left  join ecshop.ecs_admin_user users2 on users2.user_id = info.step2_user_id
			left  join ecshop.ecs_admin_user users3 on users3.user_id = info.step3_user_id
			left  join ecshop.ecs_admin_user users4 on users4.user_id = info.step4_user_id
			left  join ecshop.ecs_admin_user users5 on users5.user_id = info.step5_user_id
			where info.vorder_request_id = '{$vorder_id}'";
	
	$search_comments = $db->getAll ( $sql );
	$return_comments = array ();
	$role_title = array (
			'店员  ',
			'店长  ',
			'BD  ',
			'财务  ',
			'执行CEO  ',
			'CEO  ' 
	);
	for($i = 0; $i <= 5; $i ++) {
		$step = "step" . $i;
		if ($search_comments [0] [$step . '_status'] == '1') {
			$user_comment = array ();
			$user_comment ['username'] = $role_title [$i] . '' . $search_comments [0] [$step . '_name'];
			$user_comment ['time'] = $search_comments [0] [$step . '_time'];
			$user_comment ['comment'] = $search_comments [0] [$step . '_comment'];
			$return_comments [] = $user_comment;
		}
	}
	return $return_comments;
}

/*
 * create_application调用的函数，作用是插入-v订单到数据库表  ecs_vorder_request_info ecs_vorder_request_item
 * @param $cat -v类型，ADD 或者 MINUS
 * @param $goods_array 记录需要-v的商品数组，包含多个商品
 * @return 如果成功返回0，如果失败直接die页面，因为这是系统错误，不是人为的了。
 * 
 * */
function insert_vorder($cat, $goods_array) {
	global $db;
	if ($cat == "ADD") {
		foreach ( $goods_array as $item ) {
			if ($item ["count"] <= 0) {
				die("-v ADD 建表过程中出现申请数量为负的情况");
			}
		}
	}
	if ($cat == "MINUS") {
		foreach ( $goods_array as $item ) {
			if ($item ["count"] >= 0) {
				die("-v MINUS 建表过程中出现申请数量为正的情况");
			}
		}
	}
	
	/*
	 * 开始数据库事务操作
	*/
	$db->start_transaction ();
	try {
		/*
		 *  将订单详情插入到ecs_vorder_request_info
		*/
		$sql = "insert into ecshop.ecs_vorder_request_info (vorder_status, check_status,create_stmp, last_update_stmp,
			party_id , facility_id,	goods_amount, goods_count, step0_status,step0_user_id,step0_time,step0_comment,
			comments,inventory_adjust,v_category)
			values ('APPLY','1',NOW(),NOW(),'{$_SESSION["party_id"]}','{$goods_array[0]["facility_id"]}',0,0,
			'1','{$_SESSION["admin_id"]}',NOW(),'{$goods_array[0]["note"]}','{$goods_array[0]["note"]}',
			'0','{$cat}'
			)";
		$db->query($sql);
		$error_no = $db->errno ();
		if ($error_no > 0 ) {
			$db->rollback();
			die("建表出现错误");
		}
		$vorder_id = $db->insert_id ();
		/*
		 *  将订单所含的商品详情插入到ecs_vorder_request_item
		*/
		foreach ( $goods_array as $item ) {
			$goods_id = $item ['goods_id']; 
			$style_id = $item ['style_id']; 
			$count = abs($item ['count']);
			$price = $item ['price'];
			$status_id = $item ['status_id']; //
			$type = $item ['type'];
			$serialnumber = $item ['serialnumber'];
			$comment = $item ['comment'];
			$amount = abs($item ["amount"]);
			$facility_id = $item ["facility_id"];
			$note = $item ["note"];
			$party_id = $item ["party_id"];
			
			$sql = "select product_id, CONCAT_WS(' ', eg.goods_name, IFNULL( es.color, '') ) as goods_name
					from romeo.product_mapping pm
					inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
					left  join ecshop.ecs_style es on es.style_id = pm.ecs_style_id
					where pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}'";
			$tmp = $db->getAll($sql);
			$product_id = $tmp[0]["product_id"];
			$goods_name = $tmp[0]["goods_name"];
			if (empty($product_id) || empty($goods_name)) {
				$db->rollback();
				die("建item表出现错误");
			}
			$sql = "INSERT INTO ecshop.ecs_vorder_request_item
				(is_delete, vorder_request_id, product_id, v_category,
				goods_status, goods_name, goods_type_id,serial_number,goods_number,goods_price,goods_amount,reason)
				values ('0','{$vorder_id}','{$product_id}','{$cat}','{$status_id}','{$goods_name}','{$type}',
				'{$serialnumber}','{$count}','{$price}','{$amount}','{$comment}'
				)";
			$db->query($sql);
			$error_no = $db->errno ();
			if ($error_no > 0 ) {
				$db->rollback();
				die("建item表出现错误");
			}
		}
		$db->commit();
		return $vorder_id;
	}catch (Exception $e){
		$db->rollback();
		die("try-catch错误");
	}
	return -1;
}

/*
 * 查询序列号
 * @param $keyword 用户输入的关键字
 * @param $limit 限定查询多少个
 * @return 使用json返回，然后exit
 *
 * */
function search_serial_number($keyword, $limit) {
	global $db;
	$json = new JSON ();
	$goods_id = $_REQUEST ["goods_id"];
	$status_id = $_REQUEST ["status_id"];
	
	/*
	 * 类似的查询，记得sql添加distinct条件
	 */
	$sql = "select DISTINCT(ii.serial_number) from romeo.inventory_item ii
	inner join romeo.product_mapping pm on pm.product_id = ii.product_id
	where pm.ecs_goods_id = '{$goods_id}'  and ii.status_id = '{$status_id}'
	and ii.serial_number like '%{$keyword}%' limit  {$limit}";
	echo $json->encode ( $db->getAll ( $sql ) );
	exit ();
}

/*
 * 查询价格
 * 现在返回信息还没有统一，和前端合作中，现在改了会影响前端工作，等完毕后尽量将返回信息格式进行统一
 * */
function searchprice() {
	include_once (ROOT_PATH . 'includes/cls_json.php');
	require_once (ROOT_PATH . "RomeoApi/lib_inventory.php");
	$json = new JSON ();
	$result = array (
			'error' => 0,
			'message' => '',
			'content' => '' 
	);
	global $db;
	
	$goods_id = $_GET ["goods_id"];
	$style_id = $_GET ["style_id"];
	$party_id = $_SESSION ["party_id"];
	if (isset ( $goods_id ) && isset ( $style_id )) {
		$sql = "select DISTINCT(unit_cost) from romeo.inventory_item ii
				inner join romeo.product_mapping pm on pm.product_id = ii.product_id
				inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
				where pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}'
				and eg.goods_party_id ='{$party_id}'";
		$price_list = $db->getAll ( $sql );
		if (! isset ( $price_list )) {
			$result ["message"] = "根据goods_id和style_id找不到价格，请手工填写价格";
			echo $json->encode ( $result );
			exit ();
		}
		
		$data = array ();
		foreach ( $price_list as $key => $item ) {
			$data [] = $item ["unit_cost"];
		}
		$data = array_unique ( $data );
		$data_return = array ();
		foreach ( $data as $key => $item ) {
			$data_return [] = $item;
		}
		$result ["goods_price_list"] = $data_return;
		echo $json->encode ( $result );
	}
	;
}

/*
 * 将查询到的订单放在csv文件中
 * 目前已经废弃，统一使用search页面中的downcheckorder函数，目前开发还未完成，暂时不删除这个函数，但是已经没有和任何功能关联
 * 如果需要阅读代码，请到downcheckorder函数
* */
function downcsv() {
	global $db;
	
	$start_str = $_POST ["start_date"];
	$end_str = $_POST ["end_date"];
	if (empty ( $start_str ) || empty ( $end_str )) {
		die ( "请输入时间" );
	}
	
	header ( "Content-type:application/vnd.ms-excel;charset=utf-8" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "{$_SESSION["admin_id"]}批量申请单导出" ) . ".csv" );
	ob_start ();
	$header_str = iconv ( "UTF-8", 'GB18030', "订单号,申请时间,商品名,样式,调整类型,库存状态,仓库名称,调整数量,调整金额,申请原因,是否删除,订单状态\n" );
	
	$sql = "select info.vorder_request_id,info.create_stmp,item.goods_name,style.color,pm.ecs_style_id,
				item.v_category,item.goods_status,info.facility_id,item.goods_number,item.goods_amount,item.reason,
				item.is_delete,info.vorder_status,facility.facility_name   
				from ecshop.ecs_vorder_request_info info 
        		left join ecs_vorder_request_item item on info.vorder_request_id = item.vorder_request_id 
				left join romeo.product_mapping pm on pm.product_id = item.product_id 
				left join ecshop.ecs_style style on style.style_id = pm.ecs_style_id
				left join romeo.facility on facility.facility_id = info.facility_id 
        		where to_days(info.create_stmp) >= to_days('{$start_str}') 
				and to_days(info.create_stmp) <= to_days('{$end_str}')	
				and info.step0_user_id = '{$_SESSION["admin_id"]}';";
	$result = $db->getAll ( $sql );
	$file_str = "";
	foreach ( $result as $key => $item ) {
		$file_str .= str_replace ( ",", " ", $item ['vorder_request_id'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['create_stmp'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['goods_name'] ) . ",";
		
		if (! isset ( $item ["color"] ) || empty ( $item ["color"] )) {
			$file_str .= "无颜色,";
		} else {
			$file_str .= str_replace ( ",", " ", $item ['color'] ) . ",";
		}
		
		if (strcmp ( $item ["v_category"], "ADD" ) == 0) {
			$file_str .= "盘赢ADD,";
		} elseif (strcmp ( $item ["v_category"], "MINUS" ) == 0) {
			$file_str .= "盘亏MINUS,";
		} else {
			$file_str .= str_replace ( ",", " ", $item ['v_category'] ) . ",";
		}
		
		if (strcmp ( $item ["goods_status"], "INV_STTS_AVAILABLE" ) == 0) {
			$file_str .= "全新库,";
		} elseif (strcmp ( $item ["goods_status"], "INV_STTS_USED" ) == 0) {
			$file_str .= "二手库,";
		} else {
			$file_str .= str_replace ( ",", " ", $item ['goods_status'] ) . ",";
		}
		
		$file_str .= str_replace ( ",", " ", $item ['facility_name'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['goods_number'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['goods_amount'] ) . ",";
		$file_str .= str_replace ( ",", " ", $item ['reason'] ) . ",";
		if (intval ( $item ["is_delete"] ) == 1)
			$file_str .= "删除,";
		else
			$file_str .= "未删除,";
		$file_str .= str_replace ( ",", " ", $item ['vorder_status'] ) . "\n";
	}
	$file_str = iconv ( "UTF-8", 'gbk', $file_str );
	ob_end_clean ();
	echo $header_str;
	echo $file_str;
	exit ();
}

/*
 * 打印信息，不推荐使用
* */
function echome($message) {
	echo "<pre>";
	print_r ( $message );
	echo "</pre>";
}

/*
 * 批量excel申请的订单
 * 前台将申请的订单放到待申请订单列表中
 * */
function apply_batch_upload() {
	global $db;
	$error_message_array = array (
			statuscode => 0,
			title => "操作完结",
			msg => array () 
	);
	
	//在上传中的文件域的name属性，如果input的那么属性一样，不是文件本身的名字
	$fileElementName = 'fileToUpload'; // _FILE中的文件索引
	$party_id = $_SESSION ['party_id'];
	if (! isset ( $party_id )) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = "没有选择party";
		return array(msg => $error_message_array);
	}
	$uploader = new Helper_Uploader ();
	$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
	$config = array (
			'-v订单' => array (
					'goods_name' => '商品名称(goods_name)',
					'barcode' => '商品条码(barcode)',
					'count' => '数量(count)',
					'price' => '单价(price)',
					'amount' => '总价',
					'status' => '库存状态(正式库、二手库)',
					'isserial' => '是否有序列号',
					'serialnumber' => '序列号',
					'party_name' => '业务组织',
					'facility' => '仓库',
					'reason' => '原因' 
			) 
	);
	if (! $uploader->existsFile ( $fileElementName )) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = "没有上传文件，或者上传失败";
		return array(msg => $error_message_array);
	}
	// 取得要上传的文件句柄
	$file = $uploader->file ( $fileElementName );
	// 检查上传文件
	if (! $file->isValid ( 'xls, xlsx', $max_size )) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = "非法的文件! 请检查文件类型(xls, xlsx), 并且系统限制的上传大小为" . $max_size / 1024 / 1024 . "MB";
		return array(msg => $error_message_array);
	}
	// 读取excel
	$result = excel_read ( $file->filepath (), $config, $file->extname (), $failed );
	if (! empty ( $failed )) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = reset ( $failed );
		return array(msg => $error_message_array);
	}
	$rowset = $result ['-v订单'];
	if (empty ( $rowset )) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = "excel文件中没有数据,请检查文件";
		return array(msg => $error_message_array);
	}
	
	$in_goods_name = Helper_Array::getCols ( $rowset, 'goods_name' );
	$in_barcode = Helper_Array::getCols ( $rowset, 'barcode' );
	$in_count = Helper_Array::getCols ( $rowset, 'count' );
	$in_price = Helper_Array::getCols ( $rowset, 'price' );
	$in_amount = Helper_Array::getCols ( $rowset, 'amount' );
	$in_status = Helper_Array::getCols ( $rowset, 'status' );
	$in_issearial = Helper_Array::getCols ( $rowset, 'isserial' );
	$in_serialnumber = Helper_Array::getCols ( $rowset, 'serialnumber' );
	$in_party_name = Helper_Array::getCols ( $rowset, 'party_name' );
	$in_facility = Helper_Array::getCols ( $rowset, 'facility' );
	$in_reason = Helper_Array::getCols ( $rowset, 'reason' );
	$check_value_arr = array (
			'barcode' => '商品条码(barcode)',
			'count' => '数量(count)',
			'price' => '单价(price)',
			'amount' => '总价',
			'status' => '库存状态(正式库、二手库)',
			'isserial' => '是否有序列号',
			'serialnumber' => '序列号',
			'party_name' => '业务组织',
			'facility' => '仓库',
			'reason' => '原因' 
	);
	
	foreach ( array_keys ( $check_value_arr ) as $val ) {
		$in_val = Helper_Array::getCols ( $rowset, $val );
		$in_len = count ( $in_val );
		Helper_Array::removeEmpty ( $in_val );
		if (empty ( $in_val ) || $in_len > count ( $in_val )) {
			$error_message_array ["statuscode"] = 1;
			$error_message_array ["title"] = "文件中存在空的{$check_value_arr[$val]}，请确保后10列每一行都有数据";
			return array(msg => $error_message_array);
		}
	}
	$in_party_name = array_unique ( $in_party_name );
	if (count ( $in_party_name ) != 1) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = "业务组织均需一致";
		return array(msg => $error_message_array);
	}
	$flag = false;
	foreach ( $in_count as $val_count ) {
		if (! preg_match ( '/^[1-9\-\+]([0-9]+)?$/', $val_count )) {
			$error_message_array ["msg"] [] = "商品数量必须为整数";
			$flag = true;
			break;
		}
	}
	foreach ( $in_price as $val_price ) {
		if (! preg_match ( '/^[0-9]([0-9]*\.*[0-9]+)?$/', $val_price )) {
			$error_message_array ["msg"] [] = "商品价格必须是正浮点数";
			$flag = true;
			break;
		}
	}
	foreach ( $in_status as $val_status ) {
		if (strcmp ( $val_status, "正式库" ) && strcmp ( $val_status, "二手库" )) {
			$error_message_array ["msg"] [] = "库存状态必须是正式库或者二手库";
			$flag = true;
			break;
		}
	}

	if ($flag == true) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = "文件中存在以下错误";
		return array(msg => $error_message_array);
	}
	$sql = "select party_id from romeo.party where name = '{$in_party_name[0]}'";
	$file_party_id = $db->getOne ( $sql );
	if (! isset ( $file_party_id )) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = "文件中业务组织数据库中无记录" . $in_parity_name [0];
		return array(msg => $error_message_array);
	} else {
		if ($file_party_id != $_SESSION ["party_id"]) {
			$error_message_array ["statuscode"] = 1;
			$error_message_array ["title"] = "文件中业务组织和您当前所处组织不同";
			return array(msg => $error_message_array);
		}
	}
	
	$return_array = array (
			msg => array (),
			content => array () 
	); // 两部分
	
	foreach ( $rowset as $row ) {
		$status_id = "";
		if (strcmp ( "正式库", $row ['status'] ) == 0 || strcmp ( "INV_STTS_AVAILABLE", $row ['status'] ) == 0) {
			$status_id = "INV_STTS_AVAILABLE";
		} elseif (strcmp ( "二手库", $row ['status'] ) == 0 || strcmp ( "INV_STTS_USED", $row ['status'] ) == 0) {
			$status_id = "INV_STTS_USED";
		}
		$isserial = "";
		if ($row ["isserial"] == "是") {
			$isserial = "SERIALIZED";
		} else {
			$isserial = "NON-SERIALIZED";
		}
		$row ["facility"] = trim ( $row ["facility"] );
		$sql = "select facility_id from romeo.facility where facility_name = '{$row["facility"]}'";
		$facility_id = $db->getOne ( $sql );
		if (empty ( $facility_id )) {
			$error_message_array ["statuscode"] = 1;
			$error_message_array ["title"] = "以下商品申请出错";
			$error_message_array ["msg"] = "barcode:" . $row ["barcode"] . "  的商品，所选库存不存在";
			continue;
		}
		$cat = '';
		if (intval ( $row ["count"] ) < 0) {
			$cat = 'MINUS';
		} else {
			$cat = 'ADD';
		}
		// 以下要考虑到ADD 还是 MINUS
		
		/*
		 * 注意 ecs_goods表和ecs_goods_style表中，都有barcode，两个barcode可能不一致，以egs中为准，如果egs中没有，那么就用eg中的
		 */
		$return_goods_item = array ();
		$sql = "select barcode from ecshop.ecs_goods_style where barcode = '{$row ['barcode']}' and is_delete=0 ";
		$barcodecheck = $db->getOne ( $sql );
		
		if (isset ( $barcodecheck )) {
			$sql = "
			select CONCAT_WS(' ', eg.goods_name, IF( egs.goods_color = '', es.color , egs.goods_color)) as goods_name, pm.ecs_goods_id as goods_id,
			pm.ecs_style_id as style_id, ii.facility_id, ii.provider_id, ii.inventory_item_type_id, ii.serial_number
			from romeo.inventory_item ii
			inner join romeo.product_mapping pm on pm.product_id = ii.product_id
			inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
			left join ecshop.ecs_style es on es.style_id = pm.ecs_style_id
			left join ecshop.ecs_goods_style egs on eg.goods_id = egs.goods_id and pm.ecs_style_id = egs.style_id and egs.is_delete=0
			where egs.barcode = '{$row["barcode"]}' and ii.unit_cost = '{$row["price"]}'
			and ii.status_id = '{$status_id}' and ii.inventory_item_type_id = '{$isserial}'
			and ii.facility_id = '{$facility_id}'
			and eg.goods_party_id = '{$_SESSION["party_id"]}'
			";
		} else {
			$sql = "select barcode from ecshop.ecs_goods where barcode = '{$row ['barcode']}'";
			$barcodecheck = $db->getOne ( $sql );
			if (! isset ( $barcodecheck )) {
				$error_message_array ["statuscode"] = 1;
				$error_message_array ["title"] = "以下商品申请出错";
				$error_message_array ["msg"] = "barcode:" . $row ["barcode"] . "  的商品，数据库找不到barcode";
				continue;
			}
			$sql = "
			select CONCAT_WS(' ', eg.goods_name, IF( egs.goods_color = '', es.color , egs.goods_color)) as goods_name, pm.ecs_goods_id as goods_id,
			pm.ecs_style_id as style_id, ii.facility_id, ii.provider_id, ii.inventory_item_type_id, ii.serial_number
			from romeo.inventory_item ii
			inner join romeo.product_mapping pm on pm.product_id = ii.product_id
			inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
			left join ecshop.ecs_style es on es.style_id = pm.ecs_style_id
			left join ecshop.ecs_goods_style egs on eg.goods_id = egs.goods_id and pm.ecs_style_id = egs.style_id and egs.is_delete=0
			where eg.barcode = '{$row["barcode"]}' and ii.unit_cost = '{$row["price"]}'
			and ii.status_id = '{$status_id}' and ii.inventory_item_type_id = '{$isserial}'
			and ii.facility_id = '{$facility_id}'
			and eg.goods_party_id = '{$_SESSION["party_id"]}'

			";
		}
		
		$tmp = $db->getAll ( $sql );
		if (empty ( $tmp )) {
			$error_message_array ["statuscode"] = 1;
			$error_message_array ["title"] = "以下商品申请出错";
			$error_message_array ["msg"] = "barcode:" . $row ["barcode"] . "  的商品，填写信息有误，请仔细检查";
			continue;
		}

		$return_goods_item = array (
				cat => $cat,
				goods_name => trim ( $tmp [0] ["goods_name"] ),
				goods_id => $tmp [0] ["goods_id"],
				style_id => $tmp [0] ["style_id"],
				facility_id => $tmp [0] ["facility_id"],
				facility_name => $row ["facility"],
				price => $row ["price"],
				status_id => $status_id,
				status_name => $row ["status"],
				goods_number => $row ["count"],
				goods_item_type_id => $tmp [0] ["inventory_item_type_id"],
				serialnumber => $tmp [0] ["serial_number"],
				comment => $row ["reason"] 
		);
		$return_array ["content"] [] = $return_goods_item;
	}
	$return_array ["msg"] = $error_message_array;
	return $return_array;
}
