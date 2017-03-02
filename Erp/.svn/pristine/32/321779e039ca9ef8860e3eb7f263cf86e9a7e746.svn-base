<?php
define('IN_ECS', true);
require ('../includes/init.php');
require_once (ROOT_PATH . "admin/function.php");
require_once (ROOT_PATH . 'includes/cls_json.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
//admin_priv('home');

$bird_authority = "";
if (!in_array($_SESSION['admin_name'], array (
		'mjzhou',
		'zjli',
		'wlchen',
		'hzhang1',
		'qyyao',
		'stsun'
	))) {
	$bird_authority = "no_authority";
} else {
	$bird_authority = "have_authority";
}

$request = isset ($_REQUEST['request']) ? trim($_REQUEST['request']) : null;
$act = $_REQUEST['act'] != '' ? $_REQUEST['act'] : null;


/*转化单个订单*/
if ($request == 'ajax') {
	$json = new JSON;
	switch ($act) {
		case 'retro_order' :
			$out_biz_code = trim($_REQUEST['out_biz_code']);
			$out_biz_codes = explode(',', $out_biz_code);
			$sql = "update ecshop.sync_taobao_order_info set transfer_status='NORMAL',transfer_note = concat('转化失败，自动回退状态，原错误类型:' ,transfer_note )  where tid = '{$out_biz_codes[1]}'";
			$result = $GLOBALS['db']->query($sql);

			/*调用转化类，对当前订单时间前面一天的订单进行重新转化*/
			$str1 = strtotime(trim($out_biz_codes[2]));
			$currentime = strtotime(date('y-m-d h:i:s', time()));
			$days = ceil(($currentime - $str1) / (24 * 60 * 60) + 1);

			if ($out_biz_codes[0] != "") {
				$handle = soap_get_client('SyncTaobaoService', 'ERPSYNC');
				$object = $handle->TaobaoOrderTransfer(array (
					'applicationKey' => $out_biz_codes[0],
					'days' => $days,
				));
			}

			$return['flag'] = 'SUCCESS';
			$return['message'] = '取消成功';

			print $json->encode($return);
			exit;
			break;
		case 'party_select' :
			$party_id = $_REQUEST['party_id'] != '' ? $_REQUEST['party_id'] : null;
			if ($party_id != null) {
				$sql_shop_conf = "select application_key,nick from ecshop.taobao_shop_conf where party_id='{$party_id}' and shop_type='taobao' order by nick";
				$result_shop_conf = $db->getAll($sql_shop_conf);
				print json_encode($result_shop_conf);
			} else {
				$return['flag'] = 'ERROR';
				print $json->encode($return);
			}
			exit;
			break;

		case 'sync_order' :
			//同步订单，将聚石塔的订单同步到ERP、将淘宝的订单同步到Jushita
			$party_id = $_REQUEST['party_id'];
			$shop_conf_appkey = $_REQUEST['shop_conf_appkey'];
			$end_date = $_REQUEST['end_date'];
			$sync_tids = $_REQUEST['sync_tids'];
			$sync_scope = $_REQUEST['sync_scope'];
			$is_exist_not_jushita = 0;

			//判断淘宝订单是否存在Jushita上面
			$sync_tids_array = explode(',', $sync_tids);

			foreach ($sync_tids_array as $value) {
				if (trim($value) != "") {
					$handle = soap_get_client('SyncTaobaoService', 'ERPTAOBAOSYNC');
					$object = $handle->getTaobaoOrdersByTid(array (
						'tid' => trim($value),
						'username' => JSTUsername,
						'password' => md5(JSTPassword),

						
					));
					print $object;
					if ($object != null) {
						$is_exist_not_jushita++;
					}
				}
			}

			//如果Jushita上存在有订单没有同步到聚石塔，那么从新调度一次,目前不会执行
			if ($is_exist_not_jushita != 0) {
				$handle = soap_get_client('SyncTaobaoService', 'ERPTAOBAOSYNC');
				$object = $handle->SyncTaobaoOrder(array (
					'applicationKey' => trim($shop_conf_appkey),
					'hours' => $sync_scope,
					'endDate' => $end_date,

					
				));
			}

			//将Jushita上的taobao_order 转化到ERP Taobao Order
			if ($shop_conf_appkey != "") {
				$command = "php " . __DIR__ . "/../protected/yiic ErpSyncJushita SyncJushitaOrder ";

				if ($shop_conf_appkey != "") {
					$command .= " --appkey=" . $shop_conf_appkey;
				}
				if ($sync_scope != "") {
					$command .= " --hours=" . $sync_scope;
				}
				if ($end_date != "") {
					$command .= " --endDate=" . $end_date;
				}

				$result = exec($command, $out); //<h3>'.$result.'</h3>
				print $command; //'<h3>' . $command . '</h3>' . implode('<br>', $out);
			}
			exit;
			break;
		case 'check_not_sync_order':
			$shop_conf_appkey = $_REQUEST['shop_conf_appkey'];
			$sync_tids = explode(',', $_REQUEST['sync_tids']);
			$get_open_taobao_infos=array();  //保存从淘宝获取的订单
			foreach ( $sync_tids as $value ) {
       			if(trim($value)!=""){
       				/*这里是该订单没有在Jushita的情况，需要从淘宝获取订单*/
					$handle = soap_get_client('SyncTaobaoService', 'ERPTAOBAOSYNC');
					$object = $handle->geTaobaoOrdersFromOpenTaobao(array (
						'applicationKey'=>trim($shop_conf_appkey),
						'tid' => trim($value),
						'username' => JSTUsername,
						'password' => md5(JSTPassword),
					
					));
					/*从淘宝获取订单结束*/
					
					if($object->return!=null){
						array_push($get_open_taobao_infos,$object->return);
					}
       			}
			}
			print $json->encode($get_open_taobao_infos);
			exit;
			break;
				
	}
	exit;
}

/*批量转化订单*/
if ($act == "batch_retro_order") {
	$orders_list = $_POST['checked'];
	$order_count = count($orders_list);
	$params = "";

	for ($i = 0; $i < $order_count -1; $i++) {
		$order_infos = explode(',', $orders_list[$i]);
		$params .= "'" . trim($order_infos[1]) . "',";
	}
	$order_infos = explode(',', $orders_list[$order_count -1]);
	$params .= "'" . trim($order_infos[1]) . "'";

	/*将所有选中订单的状态更改为NORMAL*/
	$sql = "update ecshop.sync_taobao_order_info set transfer_status='NORMAL',transfer_note = concat('转化失败，自动回退状态，原错误类型:' ,transfer_note )  where tid in ({$params})";
	$result_1 = $GLOBALS['db']->query($sql);

	for ($i = 0; $i < $order_count; $i++) {
		$value = $orders_list[$i];
		$infos = explode(',', $value);
		for ($j = 0; $j < $order_count - $i; $j++) {
			$value_1 = $orders_list[$j];
			$infos_1 = explode(',', $value_1);
			if ($infos[0] < $infos_1[0]) {
				$temp = $infos;
				$infos = $infos_1;
				$infos_1 = $temp;
			}
		}
	}

	/*将applicationKey进行归类，防止重复调度*/
	$result = array ();
	$min_date_app_key = 0;
	for ($i = 1; $i < $order_count; $i++) {
		$order_infos = explode(',', $orders_list[$i]);
		$order_infos_1 = explode(',', $orders_list[$min_date_app_key]);
		if ($order_infos[0] != $order_infos_1[0]) {
			array_push($result, $order_list[$min_date_app_key]);
			$min_date_app_key = $i;
		} else {
			$order_infos_date = strtotime(trim($order_infos[2]));
			$order_infos_1_date = strtotime(trim($order_infos_1[2]));
			if ($order_infos_1_date > $order_infos_date) {
				$min_date_app_key = $i;
			}
		}
	}
	array_push($result, $orders_list[$min_date_app_key]);

	echo count($result);

	/*进行分批调度*/
	foreach ($result as $value) {
		$info = explode(',', $result[0]);
		$start_date = strtotime(trim($info[2]));
		$currentime = strtotime(date('y-m-d h:i:s', time()));
		$days = ceil(($currentime - $start_date) / (24 * 60 * 60) + 1);
		if ($info[0] != "") {
			$handle = soap_get_client('SyncTaobaoService', 'ERPSYNC');
			$object = $handle->TaobaoOrderTransfer(array (
				'applicationKey' => $info[0],
				'days' => $days,

				
			));
		}
	}

	$batch_retro_order_msg = '批量转换结束' . '共重新转化' . $order_count . '个订单，如果状态为NORMAL则转化成功，否则失败!';

	$smarty->assign('batch_retro_order_msg', $batch_retro_order_msg);
	$smarty->display('exception_handle/ordertrace.htm');
} else
	if ($act == "search") {
		$tids = $_REQUEST['tids'] != '' ? $_REQUEST['tids'] : null;
		if ($tids != null) {
			$tidarg = explode(',', $tids);
			$order_count = count($tidarg);
			$params = "";

			for ($i = 0; $i < $order_count -1; $i++) {
				$params .= "'" . trim($tidarg[$i]) . "',";
			}
			$params .= "'" . trim($tidarg[$order_count -1]) . "'";
			$sql = "SELECT title, tid,application_key,modified, created, pay_time, transfer_status, transfer_note, receiver_state, 
																																																						receiver_city, receiver_district, IF(SHIP_CODE = 'OUT_SHIP','外包发货','自己发货') AS ship_type 
																																																					FROM ecshop.sync_taobao_order_info
																																																					WHERE status not in ('TRADE_CLOSED_BY_TAOBAO','TRADE_CLOSED') and tid in (" . $params . ") order by pay_time desc";
			$result_list = $db->getAll($sql);

			$order_list = array ();
			$is_exist_order_tids = array (); //用来保存已查询出来结果订单的订单编号，from table sync_taobao_order_info
			foreach ($result_list as $index => $result) {
				array_push($is_exist_order_tids, $result['tid']);
				if ($result['transfer_status'] == 'ERROR') {
					if (strstr($result['transfer_note'], "未找到匹配商品")) {
						$advice = '请手工录单,ERP不能解决此问题';
						$result['transfer_status'] = 'NOT_FOUND_GOODS';
					} else {
						if (strstr($result['transfer_note'], "套餐淘宝后台价格大于ERP价格")) {
							$advice = '先修改订单价格,使得erp价格>=淘宝后台价格,再重新转化';
							$result['transfer_status'] = 'ERP_LESS_THAN_TAOBAO';
						} else if(strstr($result['transfer_note'], "淘宝后台维护商家编码为空")){
							$advice = '请手动录单，ERP不能解决此问题';
							$result['transfer_status'] = 'SHOP_ID_IS_NULL';
						} else
							if (strstr($result['transfer_note'], "ERP中已存在，不能重复转换")) {
								$advice = '订单在ERP中已经存在，请前往历史订单查询';
								$result['transfer_status'] = 'ALREADY EXIST';
							} else {
								$advice = "请重新转化订单";
							}
					}
					$result['advice'] = $advice;

				}
				$order_list[$index] = $result;
			}

			/*查询的订单中可能会出现订单没有进入erp的情况*/
			$not_exist_order_tids=null; //没有查询出来结果的订单编号集合，淘宝的订单我们将到聚石塔中进行查询，其他的订单我们将重新调度
			$jushita_exist_order = array ();
			$not_jushita_exist_order=array(); //Jushita中没有的订单，我们需要充淘宝平台查询
			if (count($is_exist_order_tids) != count($tidarg)) {
				foreach ($tidarg as $value) {
					$isin = in_array($value, $is_exist_order_tids); //判断订单不在中间表的订单集合中

					if (!$isin) {
						/*sync_taobao_order_info表中没有的订单信息，我们将从聚石塔获取*/
						if ($value != "") {
							$handle = soap_get_client('SyncTaobaoService', 'ERPTAOBAOSYNC');
							$object = $handle->getTaobaoOrdersByTid(array (
								'tid' => trim($value),
								'username' => JSTUsername,
								'password' => md5(JSTPassword),
								
							));

							if ($object->return != null) {
								array_push($jushita_exist_order, $object->return);
							} else {
								$not_exist_order_tids.=",".$value;
							}
						}
					}
				}
			}

			/*加载业务组*/
			$sql_1 = "select party_id,name from romeo.party where status='ok' ORDER BY name";
			$result_partys = $db->getAll($sql_1);
			
			$sql_2="select party_id,name from romeo.party where status='ok' and party_id='{$_SESSION['party_id']}'";
			$default_party = $db->getAll($sql_2);
			
			$sql_shop_conf_1 = "select application_key,nick from ecshop.taobao_shop_conf where party_id='{$_SESSION['party_id']}' and shop_type='taobao' order by nick";
			$result_shop_confs = $db->getAll($sql_shop_conf_1);

			$smarty->assign("party_id",$_SESSION['party_id']);
			$smarty->assign('jushita_exist_order', $jushita_exist_order);
			$smarty->assign('result_party', $result_partys);
			$smarty->assign('default_party',$default_party);
			$smarty->assign('result_shop_confs',$result_shop_confs);
			$smarty->assign('tids', $not_exist_order_tids);
			$smarty->assign('not_exist_order_tids', explode(',', $not_exist_order_tids));
			$smarty->assign('order_list', $order_list);
			$smarty->display('exception_handle/ordertrace.htm');
		}

	} else
		$smarty->display('exception_handle/ordertrace.htm');
?>
