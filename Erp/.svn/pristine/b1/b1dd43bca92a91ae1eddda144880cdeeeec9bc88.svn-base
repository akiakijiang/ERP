<?php
require_once ('init.php');

/*
 * 每个数组中记录的是权限
 */
$inventory_all_priv = array(
		'VOrderApply' => 0,//申请权限
		'VOrderCheckZhuguan' => 1,//主管权限
		'VOrderCheckWL' => 2//物流权限
);

$inventory_apply_priv = array(
		'VOrderApply',//申请权限
		'VOrderCheckZhuguan'//主管权限
);

$inventory_check_priv = array(
		'VOrderCheckZhuguan',//主管权限
		'VOrderCheckWL'//物流权限
);

/*
 * 检查权限原则：
 * （1）如果拥有所有权限all，那么返回VOrderCheckZhuguan权限
 * （2）如果同时拥有VOrderApply与VOrderCheckZhuguan，返回VOrderCheckZhuguan
 * 
 */
function get_inventory_priv(){
	$action_list = $_SESSION ['action_list'];
	if ($action_list == "" || empty ( $action_list )) {
		return "NO";
	}
	if ($action_list == "all") {
		return "VOrderCheckZhuguan";
	}
	$action_list_arr = explode ( ',', $action_list );
	
	if (in_array("VOrderCheckZhuguan", $action_list_arr)) {
		return "VOrderCheckZhuguan";
	} elseif (in_array("VOrderApply", $action_list_arr)) {
		return "VOrderApply";
	} elseif (in_array("VOrderCheckWL", $action_list_arr)) {
		return "VOrderCheckWL";
	} else {
		return "NO";
	}
}

function check_apply_priv(){
	if (get_inventory_priv() == "VOrderApply" || get_inventory_priv() == "VOrderCheckZhuguan") {
		return true;//要有 -v发起 或 运营审核 权限
	}else{
		return false;
	}
}

function get_check_priv(){
	if ( get_inventory_priv() == "VOrderCheckZhuguan") {
		return 1;
	}elseif(get_inventory_priv() == "VOrderCheckWL"){
		return 2;
	}else{
		return 0;
	}
}

function get_apply_priv(){
	if ( get_inventory_priv() == "VOrderApply") {
		return 0;
	}elseif(get_inventory_priv() == "VOrderCheckZhuguan"){
		return 1;
	}else{
		return -1;
	}
}

function check_check_priv(){
	if (get_inventory_priv() == "VOrderCheckWL" || get_inventory_priv() == "VOrderCheckZhuguan") {
		return true;
	}else{
		return false;
	}
}

function check_success(){
	if (get_inventory_priv() == "VOrderCheckWL") {
		return true;
	}else{
		return false;
	}
}

function check_allparty_priv(){
	$child_party = get_child_party($_SESSION['party_id']);
	if( empty( $child_party ) ){
		return false;
	}else{
		return true;
	}
}

function check_vorder_priv(){
	if (check_apply_priv() || check_check_priv()) {
		return true;
	}else{
		return false;
	}
}

function get_user_name($user_id) {
	global $db;
	$sql = "select user_name from ecshop.ecs_admin_user where user_id = '{$user_id}'";
	$tmp = $db->getOne ( $sql );
	return $tmp;
}

function get_goods_list_like_lcji($keyword = '', $limit = 100) {
	global $db;
	$conditions = '';
	
	if (trim ( $keyword )) {
		$keyword = mysql_like_quote ( $keyword );
		$conditions .= " AND g.goods_id like '{$keyword}%' ";
	}
	
	$sql = "select CONCAT_WS('  ',CONCAT_WS('#',g.goods_id,s.style_id),CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color) )) as goods_name,
			g.goods_id,s.style_id, ifnull(gs.barcode,g.barcode) as barcode 
			from ecshop.ecs_goods as g
			left join ecshop.ecs_goods_style as gs on gs.goods_id = g.goods_id and gs.is_delete=0
			left join ecshop.ecs_style as s on gs.style_id = s.style_id
			where ( g.is_on_sale = 1 AND g.is_delete = 0 ) 
			AND g.goods_party_id = '{$_SESSION["party_id"]}' " . $conditions . " LIMIT {$limit}";
	return $db->getAll ( $sql );
}


function get_goods_list_exact_lcji($keyword) {
	global $db;
	//解析出goods_id  style_id
	$str_array = explode("#", $keyword);
	if (empty($str_array)) {
		return;
	}
	$goods_id = $str_array[0];
	if (len($str_array) == 2) {
		$style_id = $str_array[1];
	}
	$conditions = '';

	if (trim ( $keyword )) {
		$keyword = mysql_like_quote ( $keyword );
		if (empty($style_id)) {
			$conditions .= " AND g.goods_id = '{$goods_id}'  ";
		}else{
			$conditions .= " AND g.goods_id = '{$goods_id}' and s.style_id = '{$style_id}' ";
		}
		
	}

	$sql = "select CONCAT_WS(' ',CONCAT_WS('#',g.goods_id,s.style_id),CONCAT_WS(' ', g.goods_name, IF( gs.goods_color = '', s.color, gs.goods_color) )) as goods_name,g.goods_id,s.style_id
	from ecshop.ecs_goods as g
	left join ecshop.ecs_goods_style as gs on gs.goods_id = g.goods_id and gs.is_delete=0
	left join ecshop.ecs_style as s on gs.style_id = s.style_id
	where ( g.is_on_sale = 1 AND g.is_delete = 0 )
	AND g.goods_party_id = '{$_SESSION["party_id"]}' " . $conditions . " LIMIT 1";
	return $db->getAll ( $sql );
}


function createInventoryItemVarianceByProductId_lcji($productId, $inventoryItemAcctTypeName, $inventoryItemTypeName, $statusId, $serialNumber, $quantityOnHandVar, $availableToPromiseVar, $physicalInventoryId, $unitCost, $facilityId, $comments, $orderId, $orderGoodsId, $actionUser) {
	require_once ROOT_PATH . 'RomeoApi/lib_inventory.php';
	global $soapclient;
	$actionUser = 'cronjob';
	$containerId = facility_get_default_container_id ( $facilityId );
	$providerId = get_self_provider_id ();
	$keys = array (
			'productId' => 'StringValue',
			'inventoryItemAcctTypeName' => 'StringValue',
			'inventoryItemTypeName' => 'StringValue',
			'statusId' => 'StringValue',
			'serialNumber' => 'StringValue',
			'quantityOnHandVar' => 'NumberValue',
			'availableToPromiseVar' => 'NumberValue',
			'unitCost' => 'NumberValue',
			'facilityId' => 'StringValue',
			'containerId' => 'StringValue',
			'actionUser' => 'StringValue',
			'physicalInventoryId' => 'StringValue',
			'providerId' => 'StringValue',
			'comments' => 'StringValue',
			'orderId' => 'StringValue',
			'orderGoodsId' => 'StringValue' 
	);
	$param = new HashMap ();
	foreach ( $keys as $key => $type ) {
		if (${$key} == null) {
			continue;
		}
		$gv = new GenericValue ();
		$method = 'set' . $type;
		$gv->$method ( ${$key} );
		$param->put ( $key, $gv->getObject () );
	}
	$result = $soapclient->createInventoryItemVarianceByProductId ( array (
			'arg0' => $param->getObject () 
	) );
	$return_hashmap = new HashMap ();
	$return_hashmap->setObject ( $result->return );
	return $return_hashmap;
}

function createPhysicalInventory_lcji($generalComments = '') {
	require_once ROOT_PATH . 'RomeoApi/lib_inventory.php';
	global $soapclient;
	$keys = array('generalComments'=>'StringValue');
	$param = new HashMap();
	foreach ($keys as $key => $type) {
		if(${$key} == null) { continue; }
		$gv = new GenericValue();
		$method = 'set'.$type;
		$gv->$method(${$key});
		$param->put($key, $gv->getObject());
	}
	$result = $soapclient->createPhysicalInventory(array('arg0'=>$param->getObject()));
	$return_hashmap = new HashMap();
	$return_hashmap->setObject($result->return);
	$physicalInventoryId = $return_hashmap->get("physicalInventoryId")->stringValue;
	return $physicalInventoryId;
}

/*
 * 获得check过程过，各个环节对order的意见
 */
function get_order_check_comment($vorder_id) {
	global $db;
	$sql = "select info.step0_status,users0.user_name as step0_name,info.step0_time,info.step0_comment,
			info.step1_status,users1.user_name as step1_name,info.step1_time,info.step1_comment,
			info.step2_status,users2.user_name as step2_name,info.step2_time,info.step2_comment			
			from ecshop.ecs_vorder_request_info info
			INNER JOIN ecshop.ecs_admin_user users0 on users0.user_id = info.step0_user_id
			left  join ecshop.ecs_admin_user users1 on users1.user_id = info.step1_user_id
			left  join ecshop.ecs_admin_user users2 on users2.user_id = info.step2_user_id
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
	for($i = 0; $i <= 2; $i ++) {
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
 * 原始用处：获得刚刚申请成功的订单 现在用处：除了获得刚刚申请成功的订单，还可以获得查询出来的所有订单
 * @param $order_ids -v订单列表
 * @param $limit_per_order 每个订单搜索出多少记录就可以
 * @param $with_quantity 说明是否搜索出订单商品的价格（运营需要看价格，物流不可以看到价格）
 * @return 订单和商品数组
 */
function get_just_now_order($vorder_ids,$limit_per_order = 0,$with_quantity = false) {
	//设置页面最大访问时间为60秒，主要是为了防止搜索订单较多，比如csv下载的最大极限，具体搜索多少订单，参见参数vorder_ids
	set_time_limit(60);
	global $db;
	if (count ( $vorder_ids ) == 0) {
		return null;
	}
	if (!empty($limit_per_order)) {
		$sql_limit_per_order = " limit {$limit_per_order} ";
	}else{
		$sql_limit_per_order = "  ";
	}
	
	$return_just_now_order_array = array ();
	foreach ( $vorder_ids as $vorder_id ) {
		
		$sql = "select info.vorder_request_id as vorder_id,info.vorder_status,info.check_status,info.create_stmp,party.name as party_name,facility.facility_name,
				info.inventory_adjust,info.v_category,item.goods_status,item.goods_type_id,item.goods_name,item.goods_number,item.goods_amount,item.reason,item.serial_number,item.goods_price,
				if(info.step0_user_id = users.user_id,'1','0') as del,item.is_delete,item.rec_id,CONCAT_WS('#', pm.ecs_goods_id, IF( pm.ecs_style_id = 0, null,pm.ecs_style_id) ) as shopcode,
				IFNULL(egs.barcode,eg.barcode) as barcode,item.adjustment,pm.ecs_goods_id as goods_id,pm.ecs_style_id as style_id,info.facility_id,info.party_id 
				from ecshop.ecs_vorder_request_info info
				inner join ecshop.ecs_vorder_request_item item on info.vorder_request_id = item.vorder_request_id
				inner join romeo.party party on info.party_id = party.party_id   
				inner join romeo.facility facility on facility.facility_id = info.facility_id 
				inner join romeo.product_mapping pm on pm.product_id  = item.product_id 
				inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id 
				left  join ecshop.ecs_goods_style egs on egs.goods_id = pm.ecs_goods_id and egs.style_id = pm.ecs_style_id and egs.is_delete=0
				left  join ecshop.ecs_admin_user users on users.user_id = info.step0_user_id
				where info.vorder_request_id = {$vorder_id} order by adjustment " . $sql_limit_per_order;
		$result = $db->getAll ( $sql );
		$result_item = array ();
		$inventory_quantity = array();
		if ($with_quantity) {
			$goods_ids = array();
			$style_ids = array();
			$prices = array();
			$status_ids = array();
			$types = array();
			$serialnumbers = array();
			foreach ($result as $item){
				$goods_ids[] = $item['goods_id'];
				$style_ids[] = $item['style_id'];
				$prices[] = $item['goods_price'];
				$status_ids[] = $item['goods_status'];
				$types[] = $item['goods_type_id'];
				$serialnumbers[] = $item['serial_number'];
				$facility_id = $item['facility_id'];
				$party_id = $item['party_id'];
			}
//			$inventory_quantity_noprice =  get_inventory_quantity(false,$goods_ids,$style_ids,$prices,$status_ids,$types,$serialnumbers,$facility_id,$party_id) ;
			$inventory_quantity_price =  get_inventory_quantity(true,$goods_ids,$style_ids,$prices,$status_ids,$types,$serialnumbers,$facility_id,$party_id) ;
		}
		if (! empty ( $result )) {
			$result_item ['vorder_id'] = $result [0] ['vorder_id'];
			switch ($result [0] ['vorder_status']) {
				case "APPLY" :
					{
						switch ($result [0] ['check_status']) {
							case "0" :
								$result_item ['vorder_status'] = '申请中';
								break;
							case "1" :
								$result_item ['vorder_status'] = '店长审核中';
								break;
							case "2" :
								$result_item ['vorder_status'] = '物流审核中';
								break;
							default :
								$result_item ['vorder_status'] = $result [0] ['check_status'];
						}
					}
					break;
				case "COMPLETE" :
					if ($result [0] ['inventory_adjust'] == 0) {
						$result_item ['vorder_status'] = '审核完成待出库';
					}else{
						$result_item ['vorder_status'] = '出库中-已调度过'.$result [0] ['inventory_adjust'].'次';
					}
					break;
				case "REFUSE" :
					$result_item ['vorder_status'] = '审核失败';
					break;
				case "CANCEL" :
					$result_item ['vorder_status'] = '已取消';
					break;
				case "OVER" :
					$sinri_over_type_get_sql=
					"SELECT
						count(1)
					FROM
						ecshop.ecs_vorder_request_info info
					LEFT JOIN ecshop.ecs_vorder_request_item item ON info.vorder_request_id = item.vorder_request_id
					WHERE
						item.adjustment = 0
					AND item.is_delete=0
					AND info.vorder_request_id = ".$vorder_id;
					$left_mono_count=$db->getOne($sinri_over_type_get_sql);
					if($left_mono_count==0){
						$result_item ['vorder_status'] = '订单调度成功';
					}else{
						$result_item ['vorder_status'] = '订单调度异常：'.$left_mono_count."条请求失败";
					}
					break;
				default :
					$result_item ['vorder_status'] = $result [0] ['vorder_status'];
			}
			$result_item ['create_stmp'] = $result [0] ['create_stmp'];
			$result_item ['party_name'] = $result [0] ['party_name'];
			$result_item ['facility_name'] = $result [0] ['facility_name'];
			if ($result [0] ['v_category'] == 'ADD') {
				$result_item ['v_category'] = '-v盘盈ADD';
			} else {
				$result_item ['v_category'] = '-v盘亏MINUS';
			}
			$result_item ['delete'] = $result [0] ['del'];
			$goods_array = array ();
			foreach ( $result as $goods ) {
				$goods_array_item = array ();
				$goods_array_item ['goods_name'] = $goods ['goods_name'];
				if ($goods ['goods_status'] == "INV_STTS_AVAILABLE") {
					$goods_array_item ['goods_status'] = "全新库";
				} else {
					$goods_array_item ['goods_status'] = "二手库";
				}
				if ($goods ['goods_type_id']) {
					;
				}
				
				$goods_array_item ['goods_type_id'] = $goods ['goods_type_id'];
				$goods_array_item ['goods_number'] = $goods ['goods_number'];
				$goods_array_item ['goods_amount'] = $goods ['goods_amount'];
				$goods_array_item ['goods_price'] = $goods ['goods_price'];
				$goods_array_item ['reason'] = $goods ['reason'];
				$goods_array_item ['serial_number'] = $goods ['serial_number'];
				$goods_array_item ['is_delete'] = $goods ['is_delete'];
				$goods_array_item ['rec_id'] = $goods ['rec_id'];
				$goods_array_item ['shopcode'] = $goods ['shopcode'];
				$goods_array_item ['barcode'] = $goods ['barcode'];
				$goods_array_item ['adjustment'] = $goods ['adjustment'];
				if ($with_quantity) {
					$map_tmp = "".$goods["goods_id"]."#".$goods["style_id"]."#".$goods["goods_status"]."#";
					$map_price = $map_tmp.$goods['goods_price']."#".$goods['serial_number'];
//					$map_noprice = $map_tmp.$goods['serial_number'];
					$quantity_price = $inventory_quantity_price[$map_price];
//					$quantity_noprice = $inventory_quantity_noprice[$map_noprice];
					$goods_array_item ['quantity_price'] = $quantity_price;
//					$goods_array_item ['quantity_noprice'] = $quantity_noprice;
				}
				$goods_array [] = $goods_array_item;
			}
			$result_item ['goods_detail'] = $goods_array;
			
			$result_item ['notes'] = get_order_check_comment ( $vorder_id );
			$return_just_now_order_array [] = $result_item;
		}
	}
	return $return_just_now_order_array;
}


/*
 * $with_price  说明这个函数是否需要按照price查询库存量
 */
function get_inventory_quantity($with_price,$goods_ids,$style_ids,$prices,$status_ids,$types,$serialnumbers,$facility_id,$party_id){
	global $db;
	$sql_merge = "  select sum(ii.quantity_on_hand_total) as goods_count,pm.ECS_STYLE_ID, pm.ECS_GOODS_ID, ii.status_id, ii.unit_cost, ii.serial_number
					from romeo.inventory_item ii
					inner join romeo.product_mapping pm on pm.product_id = ii.product_id
					inner join ecshop.ecs_goods eg on eg.goods_id = pm.ecs_goods_id
					where ii.facility_id = '{$facility_id}' and ii.party_id = '{$party_id}' ";
	$sql_condition = "(";
	for($key = 0; $key < count ( $goods_ids ); $key ++) {
		$goods_id = $goods_ids [$key];
		$style_id = $style_ids [$key];
		$price = $prices [$key];
		$status_id = $status_ids [$key];
		$type = $types [$key];
		$serialnumber = $serialnumbers [$key];
		/*
		 * 检测每个-v商品参数是否正确，如果不正确，并不影响整体申请，只是该商品无法申请成功，其他商品正常走入申请流程，所以使用continue
		 */
		if (!isset ($with_price )|| empty($goods_id ) || empty ( $status_id ) || ! isset ( $style_id ) || ! isset ( $facility_id ) ||  empty ( $type ) || empty ( $party_id ) ) {
			return "wrong";
		}
		
		$serial_tmp = "";
		if ($type == "SERIALIZED") {
			$serial_tmp = " and ii.serial_number = '{$serialnumber}' ";
		}
		
		if ($with_price == false) {
			$sql_condition .= "( pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}'
						and ii.status_id = '{$status_id}'  " . $serial_tmp . ") ";
		}else{
			$sql_condition .= "( pm.ECS_STYLE_ID= '{$style_id}' and pm.ECS_GOODS_ID = '{$goods_id}'
						and ii.status_id = '{$status_id}' and ii.unit_cost = '{$price}' " . $serial_tmp . ") ";
		}
		
		if ($key < count ( $goods_ids ) - 1) {
			$sql_condition .= " or ";
		} 
	}
	$sql_condition .= " ) ";

	$sql_serial_where = " ii.serial_number ";
	if ($with_price) {
		$sql_merge .= " and " . $sql_condition . " group by pm.ECS_STYLE_ID, pm.ECS_GOODS_ID, ii.status_id, ii.unit_cost, ii.serial_number";
	}else{
		$sql_merge .= " and " . $sql_condition . " group by pm.ECS_STYLE_ID, pm.ECS_GOODS_ID, ii.status_id,  ii.serial_number";
	}
/* 	echo $sql_merge;
	exit(); */
	$standard_array_tmp = $db->getAll($sql_merge);
	$standard_array = array();
	foreach ($standard_array_tmp as $record){
		$key = "".$record['ECS_GOODS_ID']."#";
		$key .= $record['ECS_STYLE_ID']."#";
		$key .= $record['status_id']."#";
		if ($with_price) {
			$key .= $record['unit_cost']."#";
		}
		$key .= $record['serial_number'];
		$standard_array[$key] = $record['goods_count'];
	}
	return $standard_array;
}

function get_child_party($party_id){
	global $db;
	if (empty($party_id)){
		return array();
	}
	$sql = "select party_id from romeo.party where parent_party_id = '{$party_id}'";
	$result = $db->getAll($sql);
	if (empty($result)) {
		return array();
	}
	$party_ids = array();
	foreach ($result as $item){
		$party_ids[] = $item['party_id'];
	}
	$party_available = party_get_user_party_new($_SESSION['admin_id']);
	$party_ids = array_intersect($party_ids, $party_available);
	return $party_ids;
}