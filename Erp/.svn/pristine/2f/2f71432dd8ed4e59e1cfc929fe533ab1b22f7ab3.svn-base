<?php
/*
 * Coder: Brook Ji Date: 2014-07-11 Note: 根据qdi原始代码修改 利用excel导入的时候，一定要记得检查party_id对不对
 */
define ( "IN_ECS", true );
require_once ('../includes/init.php');
require_once (ROOT_PATH . "/RomeoApi/lib_inventory.php");
require (ROOT_PATH . "/includes/lib_order.php");
include_once (ROOT_PATH . 'includes/cls_json.php');
include_once ('../includes/lib_function_inventory.php');
require_once ('../function.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');

/* if (!check_vorder_priv()) {
	die("您不具有查看的权限");
} */
admin_priv("VOrderApply","VOrderCheckZhuguan","VOrderCheckWL");
$json = new JSON ();

$_priv = get_apply_priv();

/* limit_per_order 每个订单很多商品，这个针对搜索，仅仅只展示其中的$limit_per_order个商品，当$limit_per_order = 0时，表示搜索出来的订单展示所有商品*/
$limit_per_order = 15;

/*
 * 申请页必须在有分公司的情况下才可以查看
*
* */
if (! party_explicit ( $_SESSION ['party_id'] )) {
	die ( "页面加载失败，请选择正确的分公司！" );
}

/*
 * 删除订单里的某个商品，这个注意删除的权限设计
 */
if ($_POST ["act"] == "deletegoods") {
	deletegoods ( $_priv );
}

if ($_POST['act'] == 'searchorder') {
	/*
	 * $limit设置每页显示的订单数目，可以根据前端用户选择，动态改变limit，目前暂不提供用户更改。
	 */
	$limit = 10;
	if (check_apply_priv()) {
		$search_role = 1;//运营发起的搜索
	}else{
		$search_role = 2;//物流发起的搜索
	}
	/* limit_per_order 每个订单很多商品，这个针对搜索，仅仅只展示其中的$limit_per_order个商品，当$limit_per_order = 0时，表示搜索出来的订单展示所有商品*/
	$return_just_now_order = search_order($limit,'search',$limit_per_order,$search_role);
}else {
	//默认显示所有仓库
	$smarty->assign ( 'search_facility', "0" );
}

//搜索出订单后，进行下载
if ($_POST ["act"] == "downcsv") {
	if (check_apply_priv()) {
		$search_role = 1;//运营发起的搜索
	}else{
		$search_role = 2;//物流发起的搜索
	}
	downcsv ($search_role);
	exit ();
}


if ($_REQUEST['act'] == 'cancelorder') {
	cancelorder();
	exit();
}

/* 
 * 创建订单 
 * $error_message_array_smarty 将作为返回信息显示到页面上
 * 
 * */
if ($_POST ["act"] == "create") {
	if (! check_apply_priv()) {
		die ( "对不起，您没有-v申请权限" );
	}
	$result = create_application ($limit_per_order); // 返回的数据时没有执行成功的
	$return_just_now_order = array();
	$return_just_now_order = array_slice($result[1], 0,count($result[1]));//可能会产生两个订单，-v add  minus
	$error_message_array_smarty = $result[0];
}

/* 
 * 搜索商品 ，前端使用autocomplete，传来的参数名字是q
 * 功能是根据用户输入的关键字，自动搜索出商品全称，同时还有goods_id style_id barcode  商品名称 由商家编码和名字拼接成
 * 
 * */

if ($_REQUEST ["act"] == "search_goods") {
	require_once ('../function.php');
	$limit = 20;
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
	$return_array = new_apply_batch_upload();
	echo $json->encode ( $return_array );
	exit();
}

if (check_apply_priv()) {
	$showall = 1;
}else{
	$showall = 0;
}//不同的审核者显示不同
$smarty->assign ( "showall", $showall ); //

$smarty->assign ( 'limit_per_order', $limit_per_order );
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
$user_facility = get_user_facility();
$available_facility = get_available_facility ();
$available_facility = array_intersect($user_facility,$available_facility);
$available_facility['0'] = '全部';
$smarty->assign ( 'available_facility', $available_facility );

/*
 * 所有订单状态列表
*/
$all_order_status = array(
		'0'			=>	'全部',
		'APPLY'		=>	'审核中',
		'SHOP'		=>	'店长待审核',
		'WULIU'		=>	'物流待审核',
		'OUT'		=>	'审核完待出库',
		'COMPLETE'	=>	'执行过出库',
		'REFUSE'	=>	'审核未通过',
		'CANCEL'	=>	'订单取消',
		'OVER'		=>	'订单完结'
);
$smarty->assign ( 'all_order_status', $all_order_status );

/*
 * 返回刚刚审核成功的订单
 * 同时也是搜索到的订单，用同一个变量是因为两种结果是排他的，同时方便前台写代码
*/
$smarty->assign( 'return_just_now_order', $return_just_now_order);

/*
 * 设定smarty模板
*/
$smarty->display ( 'virance_inventory/inventory_adjust_apply_v3.html' );


/*
 * 创建订单函数
 * @return array $error_message_array  创建订单过程中的操作信息
 * 
 * 这里的逻辑是，创建订单后，会重新刷新页面，$error_message_array会展示给用户
 */

function create_application($limit_per_order) {
	global $db;
	//如果订单量较大，那么时间可能长一点，最长不能超过2分钟
	set_time_limit(120);
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
	/*
	 * 传来多个商品-v，循环查看每个商品-v申请，ADD的加入到added_cat  MINUS加入到minus_cat数组中，然后调用insert_order进行创建vorder订单
	*/
	$added_cat = array ();
	$minus_cat = array ();
	
	/*
	 * 检查传来的参数是否有不合格的
	 */
	
	if (empty ( $goods_ids ) || empty ( $status_ids ) || ! isset ( $style_ids ) || empty( $facility_id ) || ! isset ( $prices ) || ! isset ( $counts ) || empty ( $types ) ) {
		$error_message_array ["msg"] ="抱歉，请求参数存在空，请重新申请，如仍无法解决，请联系ERP。";
		$error_message_array ["title"] = "抱歉，请求参数存在空，请重新申请，如仍无法解决，请联系ERP。";
		return array($error_message_array,array());
	}

	/* 关键函数，进行申请商品的库存监测*/
	$check_result = check_item($goods_ids,$style_ids,$counts,$prices,$status_ids,$types,$serialnumbers,$comments,$facility_id,$party_id,$note);

	if ($check_result[0] == 1) {
		return array($check_result[1],$check_result[2]);
	}else{
		$added_cat = $check_result[1];
		$minus_cat = $check_result[2];
	}
		
	$vorder_ids = array();
	if(count($added_cat) > 0)
		$vorder_ids[] = insert_vorder ( "ADD", $added_cat ); // return 1 表示类型检查失败
	if(count($minus_cat) > 0)
		$vorder_ids[] = insert_vorder ( "MINUS", $minus_cat );
	$return_just_now_order = get_just_now_order($vorder_ids,0,true);
	return array($error_message_array,$return_just_now_order);
}

/*
 * 对申请的商品进行检测
 * 
 */

function check_item($goods_ids,$style_ids,$counts,$prices,$status_ids,$types,$serialnumbers,$comments,$facility_id,$party_id,$note){
	/*
	 * 有四种类型的商品
	 * 
	 * 1 ADD NON-SERIALIED
	 * 2 ADD SERIALIED
	 * 3 MINUS NON-SERIALIED
	 * 4 MINUS SERIALIED
	 */
	global $db;
	$sql_merge = "
			select sum(ii.quantity_on_hand_total) as goods_count,pm.ECS_STYLE_ID, pm.ECS_GOODS_ID, ii.status_id, ii.unit_cost, ii.serial_number 
			from romeo.inventory_item ii
			inner join romeo.product_mapping pm on pm.product_id = ii.product_id
			inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id 
			 where ii.facility_id = '{$facility_id}' and eg.goods_party_id = '{$party_id}' ";
	$sql_condition = "(";
	for ($key = 0; $key < count($goods_ids); $key++){
		$goods_id = $goods_ids[$key];
		$style_id = $style_ids[$key];
		$count = $counts[$key];
		$price = $prices[$key];
		$status_id = $status_ids[$key];
		$type = $types[$key];
		$serialnumber = $serialnumbers[$key];
		$comment = $comments[$key];
		/*
		 * 检测每个-v商品参数是否正确，如果不正确，并不影响整体申请，只是该商品无法申请成功，其他商品正常走入申请流程，所以使用continue
		*/
		if (empty ( $goods_id ) || empty ( $status_id ) || ! isset ( $style_id ) || ! isset ( $facility_id ) || ! isset ( $price ) || ! isset ( $count ) || empty ( $type ) ) {
			$error_message_array ["title"] = "执行过程中如下商品出现错误";
			$error_message_array ["msg"] [] = "商品： “  " . $goods_name ."   ”申请过程中出现参数错误";
			return array(1,$error_message_array,array());
		}
		
		/*
		 * 检测每个-v序列号商品，如果是序列号商品，那么count只能是-1和1，并且序列号必须指定，如果不是，就提示该商品申请出错，但是其他商品仍然进入正常申请流程
		*/
		if ($type == "SERIALIZED") {
			if ($count != - 1 && $count != 1) {
				$error_message_array ["title"] = "执行过程中如下商品信息出现错误";
				$error_message_array ["msg"] [] = "商品： “  " . $goods_id . "  ” 价格：“" . $price . "  ” 序列号商品，数量仅能-1 or 1";
				return array(1,$error_message_array,array());
			}
			if (empty ( $serialnumber )) {
				$error_message_array ["title"] = "执行过程中如下商品信息出现错误";
				$error_message_array ["msg"] [] = "商品： “  " . $goods_id . "  ” 价格：“" . $price . "  ” 序列号商品，必须制定序列号";
				return array(1,$error_message_array,array());
			}
		}

		$serial_tmp = "";
		if ($type == "SERIALIZED") {
			$serial_tmp = " and ii.serial_number = '{$serialnumber}' ";
		}
		if ($key < count($goods_ids)-1) {
			$sql_condition .= "( pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}' 
		 					and ii.status_id = '{$status_id}' and ii.unit_cost = '{$price}' ".$serial_tmp.") or ";
		}else{
			$sql_condition .= "( pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}'
						and ii.status_id = '{$status_id}' and ii.unit_cost = '{$price}' ".$serial_tmp." ) ";
		}
		
	}
	$sql_condition .= " ) ";
	/* 
	 * 从group可以看到，查询标准时商品id，样式id，库存状态id，单价，序列号,具体标准是运营给的，如果修改条件，需要和运营核实好，这个会影响到出库
	 * */
	$sql_merge .= " and ".$sql_condition." group by pm.ECS_STYLE_ID, pm.ECS_GOODS_ID, ii.status_id, ii.unit_cost, ii.serial_number";
	$standard_array_tmp = $db->getAll($sql_merge);
	//Qlog::log("sinri debug check -v product sql : ".$sql_merge);
	$standard_array = array();
	/*
	 * 为了方便，制作散列表，提高申请的同样商品的检索
	 */
	foreach ($standard_array_tmp as $record){
		$key = "".$record['ECS_GOODS_ID']."#";
		$key .= $record['ECS_STYLE_ID']."#";
		$key .= $record['status_id']."#";
		$key .= number_format($record['unit_cost'],6)."#";
		$key .= $record['serial_number'];
		$standard_array[$key] = $record['goods_count'];
	}
	$added_cat = array ();
	$minus_cat = array ();

	/*
	 * 从上面的散列表中选择,并且分析错误的原因
	 */
	foreach ($goods_ids as $key => $item){
		$map = "".$goods_ids[$key]."#";
		$map .= $style_ids[$key]."#";
		$map .= $status_ids[$key]."#";
		$map .= number_format($prices[$key],6)."#";
		$map .= $serialnumbers[$key];

		$count = $counts[$key];
		$type = $types[$key];
		$serialnumber = $serialnumbers[$key];
		
		if ($count > 0 && $type == "NON-SERIALIZED"){
			if (!isset($standard_array[$map])) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品(商品id#样式id#库存id#价格#序列号)错误：{$map} 数据库无记录";
				return array(1,$error_message_array,array());
			}
		}elseif($count > 0 && $type == "SERIALIZED"){
			if (!isset($standard_array[$map]) || $standard_array[$map] > 0) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品(商品id#样式id#库存id#价格#序列号)错误：{$map} 序列号商品没有入库记录或者序列号已经有库存";
				return array(1,$error_message_array,array());
			}
		}elseif($count < 0 && $type == "NON-SERIALIZED"){
			if ($standard_array[$map] < abs($count) ) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品(商品id#样式id#库存id#价格#序列号)错误：{$map} 库存数量不能满足";
				return array(1,$error_message_array,array());
			}
		}elseif($count < 0 && $type == "SERIALIZED"){
			if (!isset($standard_array[$map]) || $standard_array[$map] !=1  ) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品(商品id#样式id#库存id#价格#序列号)错误：{$map}序列号库存数量不能满足";
				return array(1,$error_message_array,array());
			}
		}else{
			if (!isset($standard_array[$map]) || $standard_array[$map] !=1  ) {
				$error_message_array ["title"] = "执行过程中如下商品出现错误";
				$error_message_array ["msg"] [] = "商品(商品id#样式id#库存id#价格#序列号)错误：{$map}信息有误,检查数量和序列号";
				return array(1,$error_message_array,array());
			}
		}
		$goods_array = array (
				goods_id => $goods_ids[$key], //
				style_id => $style_ids[$key], //
				count => $count,
				price => $prices[$key],
				status_id => $status_ids[$key], //
				type => $type,
				serialnumber => $serialnumbers[$key],
				comment => $comments[$key],
				amount => floatval ( $count ) * floatval ( $prices[$key] ),
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
	return array(0,$added_cat,$minus_cat);
}

/*
 * 搜索订单
 * @param $limit 一共搜索多少条。csv文件现在设计300，分页搜索现在设计10
 * @param $action 选取两个值 search downcsv
 * @param $limit_per_order 每个订单搜索出多少商品
 * @param $search_role 针对运营和物流，运营搜索是1，物流搜索是2
 * @return 搜索出的订单和商品，注意数组格式
 */
function search_order($limit,$action = "search",$limit_per_order = 0,$search_role = 1){
	global $db,$smarty;
	//重要参数: csv下载，最多下载$limit个订单
	$csvlimit = $limit;
	
	/*开始时间与结束时间必须同时选择*/
	$start_display = $_REQUEST['start_display'];
	$end_display = $_REQUEST['end_display'];
	if ((empty($start_display) && !empty($end_display)) || (!empty($start_display) && empty($end_display))) {
		die("开始时间和结束时间必须同时选择");
	}
	$order_status = $_REQUEST['order_status'];
	$allparty = $_REQUEST['allparty'];
	
	/*goods 和 style 必须同时选择*/
	$goods_id = $_REQUEST['search_goods_id'];
	$style_id = $_REQUEST['search_style_id'];
	if ((isset($goods_id) && !isset($style_id)) || (!isset($goods_id) && isset($style_id))) {
		die("商品选择参数出现错误");
	}
	
	$facility_id = $_REQUEST['search_facility'];
	$page = $_REQUEST['page'];
	$aboutme = $_REQUEST['aboutme'];
	$barcode = $_REQUEST['search_barcode'];
	
	if (empty($page)) {
		$page = 1;
	}
	
	/*
	 * 要把参数返回页面
	*/
	$search_orderid = $_REQUEST['search_orderid'];
	
	$smarty->assign("search_orderid",$search_orderid);
	$smarty->assign("start_display",$start_display);
	$smarty->assign("end_display",$end_display);
	$smarty->assign("order_status",$order_status);
	if (!empty($allparty)) {
		$smarty->assign("allparty","checked");;
	}
	$smarty->assign("search_goods_id",$goods_id);
	$smarty->assign("search_style_id",$style_id);
	$smarty->assign("search_barcode",$barcode);
	$smarty->assign("search_facility",$facility_id);
	$smarty->assign("goods_name",$goods_name);
	$smarty->assign("aboutme",$aboutme);
	$smarty->assign("page",$page);
	
	if (!empty($search_orderid)){
		$restr = "/^[1-9]([0-9]+)?$/";
		if (preg_match($restr, $search_orderid)){
			$smarty->assign("pagecount",1);
			if ($search_role == 1) {
				return get_just_now_order(array($search_orderid),$limit_per_order,true);
			}else{
				return get_just_now_order(array($search_orderid),$limit_per_order,false);
			}
		}else{
			return null;
		}
	}
	
	$sql_about = " (step0_user_id = '{$_SESSION["admin_id"]}' 
					or step1_user_id = '{$_SESSION["admin_id"]}' 
					or step2_user_id = '{$_SESSION["admin_id"]}' ) ";
	/*
	 * 因为上面对共同出现的选项做了检测，这里仅仅判断其中之一就可以了
	 */
	$start = ($page-1) * $limit;
	if (empty($start_display) && empty($order_status) && !empty($allparty) && empty($goods_id) && empty($facility_id) && empty($aboutme) && empty($barcode)) {
		//说明没有添加任何搜索条件(除了allparty)
		$sql_count = "select COUNT(info.vorder_request_id) as number from ecshop.ecs_vorder_request_info info ";
		$sql_result = "select distinct(info.vorder_request_id) as vorder_id from ecshop.ecs_vorder_request_info info limit {$start},{$limit}";
		//命中总数
		$count = $db->getOne($sql_count);
		$result = $db->getAll($sql_result);
		$vorder_ids = array();
		if (empty($result)) {
			return null;
		}
		foreach ($result as $result_item){
			$vorder_ids [] = $result_item ['vorder_id'];
		}
		$pagecount = (int)($count/$limit);
		if ($count%$limit > 0) {
			$pagecount ++ ;
		}
		$smarty->assign("pagecount",$pagecount);
		$smarty->assign("recordcount",$count);
		$page_array = array();
		for ( $i = 1; $i <= $pagecount; $i++){
			$page_array ["{$i}"] = $i;
		}
		$smarty->assign("page_array",$page_array);
		if ($search_role == 1) {
			return get_just_now_order($vorder_ids,$limit_per_order,true);
		}else{
			return get_just_now_order($vorder_ids,$limit_per_order,false);
		}
		
	}
	/*
	 * 当并不是全部为空的订单相关搜索（不指定goods 和 style）
	 */
	else{
		$sql_count = "select COUNT(DISTINCT(info.vorder_request_id)) as number from ecshop.ecs_vorder_request_info info ";
		$sql_result = "select distinct(info.vorder_request_id) as vorder_id from ecshop.ecs_vorder_request_info info ";
		$sql_tmp = "";
		if (!empty($goods_id)) {
			$sql = "select product_id from romeo.product_mapping pm where pm.ecs_goods_id = '{$goods_id}' and ecs_style_id = '{$style_id}'";
			$product_id = $db->getOne($sql);
			$sql_tmp .= "inner join ecshop.ecs_vorder_request_item item on item.vorder_request_id = info.vorder_request_id ";
			$sql_tmp .= "where item.product_id = '{$product_id}' ";
			
		}elseif (!empty($barcode)){
			$sql = "select IFNULL((select pm.product_id from romeo.product_mapping pm inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
				left join ecshop.ecs_goods_style egs on egs.goods_id = pm.ecs_goods_id and egs.style_id = pm.ecs_style_id and egs.is_delete=0 where  egs.barcode = '{$barcode}' ),(select pm.product_id from romeo.product_mapping pm inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
				left join ecshop.ecs_goods_style egs on egs.goods_id = pm.ecs_goods_id and egs.style_id = pm.ecs_style_id and egs.is_delete=0 where  eg.barcode = '{$barcode}' )) as product_id ";
			$product_id = $db->getOne($sql);
			if (empty($product_id)) {
				die("根据barcode找不到商品");
			}
			$sql_tmp .= "inner join ecshop.ecs_vorder_request_item item on item.vorder_request_id = info.vorder_request_id ";
			if ($sql_tmp == "") {
				$sql_tmp .= "where item.product_id = '{$product_id}' ";
			}else{
				$sql_tmp .= " and item.product_id = '{$product_id}' ";
			}
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
		$all_order_status = array(
				'0'			=>	'全部',
				'APPLY'		=>	'申请待审核',
				'SHOP'		=>	'店长待审核',
				'WULIU'		=>	'物流待审核',
				'OUT'		=>	'审核完待出库',
				'COMPLETE'	=>	'执行过出库',
				'REFUSE'	=>	'审核未通过',
				'CANCEL'	=>	'订单取消',
				'OVER'		=>	'订单完结'
		);
		if (!empty($order_status)) {
			$sql_status = "";
			switch ($order_status) {
				case "APPLY" :
					$sql_status = " info.vorder_status = 'APPLY'  ";
					break;
				case "SHOP" :
					$sql_status = " info.vorder_status = 'APPLY' and info.check_status = '1' ";
					break;
				case "WULIU" :
					$sql_status = " info.vorder_status = 'APPLY' and info.check_status = '2' ";
					break;
				case "OUT" :
					$sql_status = " info.vorder_status = 'COMPLETE' and info.inventory_adjust = '0' ";
					break;
				case "COMPLETE" :
					$sql_status = " info.vorder_status = 'COMPLETE' and info.inventory_adjust = '1' ";
					break;
				case "REFUSE" :
					$sql_status = " info.vorder_status = 'REFUSE'   ";
					break;
				case "CANCEL" :
					$sql_status = " info.vorder_status = 'CANCEL'   ";
					break;
				case "OVER" :
					$sql_status = " info.vorder_status = 'OVER'   ";
					break;
				default :
					$sql_status = "  ";
			}
			if ($sql_tmp == "") {
				$sql_tmp .= " where  ".$sql_status;
			}else{
				$sql_tmp .= " and  ".$sql_status;
			}
		}
		if (empty($allparty)) {
			if ($sql_tmp == "") {
				$sql_tmp .= " where info.party_id = '{$_SESSION["party_id"]}' ";;
			}else{
				$sql_tmp .= " and info.party_id = '{$_SESSION["party_id"]}' ";
			};
		}
		if (!empty($aboutme)) {
			if ($sql_tmp == "") {
				$sql_tmp .= " where ".$sql_about;
			}else{
				$sql_tmp .= " and ".$sql_about;
			};
		}
		//汇总结果  sql_count 是计算搜索出多少订单 sql_result，仅仅搜出当前页需要的订单		
		$sql_count .= $sql_tmp;
		$sql_result .= $sql_tmp." order by info.create_stmp desc limit {$start},{$limit}";
/* 		echo $sql_count;
		exit(); */
		$count = $db->getOne($sql_count);
		if ($action == "csvdown" && $count > $limit){
			return "wrong";
		}
		$result = $db->getAll($sql_result);
		$vorder_ids = array();
		if (empty($result)) {
			return null;
		}
		foreach ($result as $result_item){
			$vorder_ids [] = $result_item ['vorder_id'];
		}
		$pagecount = (int)($count/$limit);
		if ($count%$limit > 0) {
			$pagecount ++ ;
		}
		$smarty->assign("pagecount",$pagecount);
		$smarty->assign("recordcount",$count);
		$page_array = array();
		for ( $i = 1; $i <= $pagecount; $i++){
			$page_array ["{$i}"] = $i;
		}
		$smarty->assign("page_array",$page_array);
		if ($search_role == 1) {
			return get_just_now_order($vorder_ids,$limit_per_order,true);
		}else{
			return get_just_now_order($vorder_ids,$limit_per_order,false);
		}
		
	}
}
/* 
 * $limit_order_count最多下载的订单数量，超过这个数量，不可以下载，为了防止下载过多。
 * 下载的其他参数，需要前台传输进来，和搜索一样，下面就调用了搜索的函数search_order 
 * */
function downcsv($search_role){
	$limit_order_count = 80;
	$order_array = search_order($limit_order_count,'csvdown',0,$search_role);
	if ($order_array == "wrong") {
		die("请缩小搜索范围，最多下载{$limit_order_count}个订单，具体订单数参见搜索最下方记录数");
	}
	$date = strval(date('Y-m-d'));
	header ( "Content-type:application/vnd.ms-excel;charset=utf-8" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "{$date}-v搜索导出" ) . ".csv" );
	ob_start ();
	if ($search_role == 1) {
		$header_str = iconv ( "UTF-8", 'GB18030', "订单号,申请时间,商家编码,barcode,商品名,仓库,类型,组织,序列号,数量,单价,现在库存量,原因,是否删除,订单流程状态,审核记录\n" );
	}else{
		$header_str = iconv ( "UTF-8", 'GB18030', "订单号,申请时间,商家编码,barcode,商品名,仓库,类型,组织,序列号,数量,原因,是否删除,订单流程状态,审核记录\n" );
	}
	
	$file_str = "";
	foreach ($order_array as $key => $order){
		$str = "";
		foreach ($order['notes'] as $key0 => $record){
			$str .= $record['username']." 在 ".$record['time']." 备注： ".$record['comment']." || "; 
		}
		foreach ($order['goods_detail'] as $key1 => $goods){
			$file_str .= $order['vorder_id'].",";
			$file_str .= $order['create_stmp'].",";
			$file_str .= $goods['shopcode'].",";
			$file_str .= $goods['barcode'].",";
			$file_str .= $goods['goods_name'].",";
			$file_str .= $order['facility_name'].",";
			$file_str .= " ".$order['v_category'].",";
			$file_str .= $order['party_name'].",";
			if ($goods['goods_type_id'] == 'NON-SERIALIZED'){
				$file_str .= "无序列号,";
			}else{
				$file_str .= $goods['serial_number'].",";
			}
			$file_str .= $goods['goods_number'].",";
			if ($search_role == 1) {
				$file_str .= $goods['goods_price'].",";
				$file_str .= $goods['quantity_price'].",";
			}			
			$file_str .= $goods['reason'].",";
			if ($goods['is_delete'] == '0'){
				$file_str .= "否,";
			}else{
				$file_str .= "是,";
			}
			$file_str .= $order['vorder_status'].",";
			$file_str .= $str."\n";
		}
	}
	$file_str = iconv ( "UTF-8", 'gbk', $file_str );
	ob_end_clean ();
	echo $header_str;
	echo $file_str;
	exit ();
	
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
				die ( "-v ADD 建表过程中出现申请数量为负的情况" );
			}
		}
	}
	if ($cat == "MINUS") {
		foreach ( $goods_array as $item ) {
			if ($item ["count"] >= 0) {
				die ( "-v MINUS 建表过程中出现申请数量为正的情况" );
			}
		}
	}
	$goods_name_array = array ();
	foreach ( $goods_array as $item ) {
		$goods_id = $item ['goods_id'];
		$style_id = $item ['style_id'];
		if (empty ( $goods_name_array [$goods_id . "#" . $style_id] )) {
			$sql = "select product_id, CONCAT_WS(' ', eg.goods_name, IFNULL( es.color, '') ) as goods_name
					from romeo.product_mapping pm
					inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
					left  join ecshop.ecs_style es on es.style_id = pm.ecs_style_id
					where pm.ecs_goods_id = '{$goods_id}' and pm.ecs_style_id = '{$style_id}'";
			$tmp = $db->getAll ( $sql );
			if (empty ( $tmp )) {
				die ( "建表过程中错误，找不到商家编码对应的product_id goods_name" );
			}
			$goods_name_array [$goods_id . "#" . $style_id] = $tmp [0];
		}
	}
	
	/*
	 * 开始数据库事务操作
	 */
	$db->start_transaction ();

	try {
		/*
		 * 将订单详情插入到ecs_vorder_request_info
		 */
		$sql = "insert into ecshop.ecs_vorder_request_info (vorder_status, check_status,create_stmp, last_update_stmp,
			party_id , facility_id,	step0_status,step0_user_id,step0_time,step0_comment,
			comments,inventory_adjust,v_category,preprocess)
			values ('APPLY','1',NOW(),NOW(),'{$_SESSION["party_id"]}','{$goods_array[0]["facility_id"]}',
			'1','{$_SESSION["admin_id"]}',NOW(),'{$goods_array[0]["note"]}','{$goods_array[0]["note"]}',
			'0','{$cat}',0
			)";
		$db->query ( $sql );
		$error_no = $db->errno ();
		if ($error_no > 0) {
			$db->rollback ();
			die ( "建表出现错误" );
		}
		$vorder_id = $db->insert_id ();
		/*
		 * 将订单所含的商品详情插入到ecs_vorder_request_item
		 */
		$sql_merge = "INSERT INTO ecshop.ecs_vorder_request_item
					(is_delete, vorder_request_id, product_id, v_category,
					goods_status, goods_name, goods_type_id,serial_number,goods_number,goods_price,goods_amount,reason,adjustment)
					values  ";
		for ($i = 0 ; $i < count($goods_array) ; $i++){
			$item = $goods_array[$i];
			$goods_id = $item ['goods_id'];
			$style_id = $item ['style_id'];
			$count = abs ( $item ['count'] );
			$price = $item ['price'];
			$status_id = $item ['status_id']; //
			$type = $item ['type'];
			$serialnumber = $item ['serialnumber'];
			$comment = $item ['comment'];
			$amount = abs ( $item ["amount"] );
			$facility_id = $item ["facility_id"];
			$note = $item ["note"];
			$party_id = $item ["party_id"];
			$product_id = $goods_name_array [$goods_id . "#" . $style_id] ['product_id'];
			$goods_name = addslashes ( $goods_name_array [$goods_id . "#" . $style_id] ['goods_name'] );
			if ($i != count($goods_array)-1){
				$sql_merge .= " ('0','{$vorder_id}','{$product_id}','{$cat}','{$status_id}','{$goods_name}','{$type}',
				'{$serialnumber}','{$count}','{$price}','{$amount}','{$comment}','0'
				), ";
			}else{
				$sql_merge .= " ('0','{$vorder_id}','{$product_id}','{$cat}','{$status_id}','{$goods_name}','{$type}',
				'{$serialnumber}','{$count}','{$price}','{$amount}','{$comment}','0'
				) ";
			}
		}

		$db->query ( $sql_merge );
		$error_no = $db->errno ();
		if ($error_no > 0) {
			$db->rollback ();
			die ( "建item表出现错误" );
		}
		$db->commit ();
		return $vorder_id;
	} catch ( Exception $e ) {
		$db->rollback ();
		die ( "try-catch错误" );
	}

	return - 1;
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
	and ii.serial_number like '{$keyword}%' limit  {$limit}";
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
		$data_return = array ();
		foreach ( $price_list as $key => $item ) {
			$data_return [] = $item ["unit_cost"];
		}
		$result ["goods_price_list"] = $data_return;
		echo $json->encode ( $result );
	}
	;
}

/*
 * 下载订单 
 * 
 */
function downcheckcsv() {
	global $db;
	
	$start_str = $_POST ["start_date"];
	$end_str = $_POST ["end_date"];
	if (empty ( $start_str ) || empty ( $end_str )) {
		die ( "请输入时间" );
	}
	
	if (empty ( $_POST ["ordinary"] )) {
		$reverse = " DESC ";
	} else {
		$reverse = "  asc ";
	}
	if (empty($_POST['allparty'])){
		$allparty = false;
	}else{
		$allparty = true;
	}
	
	header ( "Content-type:application/vnd.ms-excel;charset=utf-8" );
	header ( "Content-Disposition:filename=" . iconv ( "UTF-8", "GB18030", "{$_SESSION["admin_id"]}审核过的-v申请单导出" ) . ".csv" );
	ob_start ();
	$header_str = iconv ( "UTF-8", 'GB18030', "订单号,申请时间,商品名,样式,调整类型,库存状态,仓库名称,调整数量,调整金额,序列号类型,序列号,申请原因,是否删除,订单状态\n" );
	
	if($allparty == true){
		$sql = "select info.vorder_request_id,info.create_stmp,item.goods_name,style.color,pm.ecs_style_id,
		item.v_category,item.goods_status,info.facility_id,item.goods_number,item.goods_amount,item.reason,
		item.is_delete,info.vorder_status,facility.facility_name,item.goods_type_id,item.serial_number 
		from ecshop.ecs_vorder_request_info info
		left join ecs_vorder_request_item item on info.vorder_request_id = item.vorder_request_id
		left join romeo.product_mapping pm on pm.product_id = item.product_id
		left join ecshop.ecs_style style on style.style_id = pm.ecs_style_id
		left join romeo.facility on facility.facility_id = info.facility_id
		where to_days(info.create_stmp) >= to_days('{$start_str}')
		and to_days(info.create_stmp) <= to_days('{$end_str}')
		and (step0_user_id = '{$_SESSION["admin_id"]}'
		or step1_user_id = '{$_SESSION["admin_id"]}'
		or step2_user_id = '{$_SESSION["admin_id"]}'
		or step3_user_id = '{$_SESSION["admin_id"]}'
		or step4_user_id = '{$_SESSION["admin_id"]}'
		or step5_user_id = '{$_SESSION["admin_id"]}')
		order by info.create_stmp ".$reverse;
	}else{
		$sql = "select info.vorder_request_id,info.create_stmp,item.goods_name,style.color,pm.ecs_style_id,
		item.v_category,item.goods_status,info.facility_id,item.goods_number,item.goods_amount,item.reason,
		item.is_delete,info.vorder_status,facility.facility_name,item.goods_type_id,item.serial_number 
		from ecshop.ecs_vorder_request_info info
		left join ecs_vorder_request_item item on info.vorder_request_id = item.vorder_request_id
		left join romeo.product_mapping pm on pm.product_id = item.product_id
		left join ecshop.ecs_style style on style.style_id = pm.ecs_style_id
		left join romeo.facility on facility.facility_id = info.facility_id
		where to_days(info.create_stmp) >= to_days('{$start_str}') 
		and info.party_id = '{$_SESSION["party_id"]}' 
		and to_days(info.create_stmp) <= to_days('{$end_str}')
		and (step0_user_id = '{$_SESSION["admin_id"]}'
		or step1_user_id = '{$_SESSION["admin_id"]}'
		or step2_user_id = '{$_SESSION["admin_id"]}'
		or step3_user_id = '{$_SESSION["admin_id"]}'
		or step4_user_id = '{$_SESSION["admin_id"]}'
		or step5_user_id = '{$_SESSION["admin_id"]}')
		order by info.create_stmp ".$reverse;
	}
	
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
		if ($item ['goods_type_id'] == 'NON-SERIALIZED') {
			$file_str .= "无,";
		}else{
			$file_str .= "有,";
		}
		$file_str .= $item ['serial_number'] . ",";
		
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
		$sql = "select barcode from ecshop.ecs_goods_style where barcode = '{$row ['barcode']}' and is_delete=0";
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

/*
 * 取消订单，仅仅申请者者可以操作
*/
function cancelorder(){
	global $db;
	$msg = "";
	/*
	 * 需要获得一个参数：key，是需要取消的订单号，如果申请的角色和他当前的角色不符合，就会失败
	*/
	$vorder_id = $_REQUEST ["key"];
	if (empty($vorder_id) ){
		$msg =  "{\"msg\":\"key参数为空\"}";
		echo $msg;
		return;
	}
	$sql = "update ecshop.ecs_vorder_request_info set vorder_status = 'CANCEL' where vorder_request_id = '{$vorder_id}' and step0_user_id = '{$_SESSION["admin_id"]}' and vorder_status = 'APPLY' ";
	$line = $db->exec($sql);
	if ($line != 1 ) {
		$msg =  "{\"msg\":\"删除失败，只有订单未完成审核，订单申请者才可以删除，请核实重试\"}";
		echo $msg;
		return;
	}else{
		$msg =  "{\"msg\":\"ok\"}";
		echo $msg;
		return;
	}
}

/*
 * 删除商品，标准是item表中的recid
 * 
* */
function deletegoods($user_priv) {
	global $db;
	$json = new JSON ();
	/*
	 * recid是item表中的primary key
	* 
	* key是订单号
	* */
	$rec_id = $_POST ["key"];
	$result = array (
			msg => "ok",
	);
	
	if (!check_apply_priv()) {
		$result ["msg"] = "您不具有删除权限";
		echo $json->encode ( $result );
		exit ();
	}
	
	if (! isset ( $rec_id )) {
		$result ["msg"] = "请求参数recid有问题";
		echo $json->encode ( $result );
		exit ();
	}
	/*
	 * 删除标准：只有当前用户创建的订单，并且没有被任何人审核过，才可以被删除
	 */
	$sql = "select item.is_delete from ecshop.ecs_vorder_request_info info 
			inner join ecshop.ecs_vorder_request_item item on item.vorder_request_id = info.vorder_request_id 
			where rec_id = '{$rec_id}' and info.step0_user_id = '{$_SESSION["admin_id"]}' and info.vorder_status = 'APPLY' and info.check_status = 1 ";
	$is_delete = $db->getOne ( $sql );
	if (!isset($is_delete)) {
		$result ["msg"] = "只有（未经过审核的）（当前用户创建的）商品可以删除，";
		echo $json->encode ( $result );
		exit ();
	}elseif($is_delete == 1){
		$result ["msg"] = "已经被删除，请勿重复删除";
		echo $json->encode ( $result );
		exit ();
	}

	$db->start_transaction (); // 开始事务
	try {
		$sql = "update ecshop.ecs_vorder_request_item set is_delete = 1, deletebyuser = '{$_SESSION["admin_id"]}' where rec_id = '{$rec_id}' 
				";
		$line = $db->exec ( $sql );
		if ($line != 1) {
			$result ["msg"] = "未删除成功，请联系ERP " ;
			$db->rollback ();
			echo $json->encode ( $result );
			exit ();
		}
		$db->commit ();
		echo $json->encode ( $result );
	} catch ( Exception $e ) {
		$db->rollback ();
		die ( $e );
	}
	exit ();
}


function new_apply_batch_upload() {
	set_time_limit(120);
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
					'party_name' => '业务组织',
					'facility' => '仓库',
					'shopcode'	=> '商家编码',
					'barcode'	=> 'barcode(选填)',
					'name'	=> '商品名称(选填)',
					'count' => '数量(count)',
					'price' => '单价(price)',
					'status' => '库存状态(正式库、二手库)',
					'isserial' => '是否有序列号',
					'serialnumber' => '序列号',
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

	$in_party_name = Helper_Array::getCols ( $rowset, 'party_name' );
	$in_facility = Helper_Array::getCols ( $rowset, 'facility' );
	$in_shopcode = Helper_Array::getCols ( $rowset, 'shopcode' );
	$in_count = Helper_Array::getCols ( $rowset, 'count' );
	$in_price = Helper_Array::getCols ( $rowset, 'price' );
	$in_status = Helper_Array::getCols ( $rowset, 'status' );
	$in_issearial = Helper_Array::getCols ( $rowset, 'isserial' );
	$in_serialnumber = Helper_Array::getCols ( $rowset, 'serialnumber' );
	$in_reason = Helper_Array::getCols ( $rowset, 'reason' );
	$check_value_arr = array (
					'party_name' => '业务组织',
					'facility' => '仓库',
					'shopcode'	=> '商家编码',
					'count' => '数量(count)',
					'price' => '单价(price)',
					'status' => '库存状态(正式库、二手库)',
					'isserial' => '是否有序列号',
					'reason' => '原因'
	);

	foreach ( array_keys ( $check_value_arr ) as $val ) {
		$in_val = Helper_Array::getCols ( $rowset, $val );
		$in_len = count ( $in_val );
		Helper_Array::removeEmpty ( $in_val );
		if (empty ( $in_val ) || $in_len > count ( $in_val )) {
			$error_message_array ["statuscode"] = 1;
			$error_message_array ["title"] = "文件中存在空的{$check_value_arr[$val]}，请确保每一行都有数据";
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
		if (! preg_match ( '/^[0-9\-\+]([0-9]+)?$/', $val_count )) {
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
	$sql = "select facility_id from romeo.facility where facility_name = '{$in_facility [0]}'";
	$facility_id = $db->getOne ( $sql );
	if (! isset ( $facility_id )) {
		$error_message_array ["statuscode"] = 1;
		$error_message_array ["title"] = "文件中所写仓库数据库中无记录" . $in_facility [0];
		return array(msg => $error_message_array);
	}

	$return_array = array (
			msg => array (),
			content => array ()
	); // 两部分
	foreach ( $rowset as $row ) {
		//status_id isserial  cat 
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
		// 以下要考虑到ADD 还是 MINUS

		/*
		 * 注意 ecs_goods表和ecs_goods_style表中，都有barcode，两个barcode可能不一致，以egs中为准，如果egs中没有，那么就用eg中的
		*/
		$shopcode = $row['shopcode'];
		$str_array = explode("#", $shopcode);
		$goods_id = $str_array[0];
		if (count($str_array) == 1) {
			$style_id = 0;
		}elseif(count($str_array) == 2){
			$style_id = $str_array[1];
		}else{
			$error_message_array ["statuscode"] = 1;
			$error_message_array ["title"] = "文件中{$shopcode}有错";
			return array(msg => $error_message_array);
		}
/* 		$sql = "select CONCAT_WS(' ',CONCAT_WS('#',eg.goods_id,es.style_id),CONCAT_WS(' ', eg.goods_name, IF( egs.goods_color = '', es.color, egs.goods_color) )) as goods_name,
				ifnull(egs.barcode,eg.barcode) as barcode 
				from ecshop.ecs_goods eg 
				left join ecshop.ecs_goods_style egs on egs.goods_id = eg.goods_id
				left join ecshop.ecs_style es on es.style_id = egs.style_id 
				where eg.goods_id = '{$goods_id}' ";
		if (!empty($style_id)){
			$sql .= " and egs.style_id = '{$style_id}'";
		}
		$name_barcode = $db->getAll($sql); */
		$name_barcode = array(goods_name=>$row['name'],barcode=>$row['barcode']);
/* 		if (empty($name_barcode)) {
			$error_message_array ["statuscode"] = 1;
			$error_message_array ["title"] = "文件中{$shopcode}找不到对应的barcode";
			return array(msg => $error_message_array);
		} */
		$return_goods_item = array (
				goods_name => trim ( $name_barcode ["goods_name"] ),
				goods_id => $goods_id,
				style_id => $style_id,
				status_id => $status_id,
				goods_number => $row ["count"],
				price => $row ["price"],
				goods_item_type_id => $isserial,
				serialnumber => $row['serialnumber'],
				comment => $row ["reason"],
				barcode => trim ( $name_barcode ["barcode"] ),
		);
		$return_array ["content"] [] = $return_goods_item;
	}

	$return_array ["msg"] = $error_message_array;
	return $return_array;
}