<?php
define ( 'IN_ECS', true );
require_once ('includes/init.php');
require_once('includes/lib_filelock.php');
require_once ('function.php');
require_once (ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once (ROOT_PATH . 'includes/lib_order.php');
require_once (ROOT_PATH . 'includes/helper/array.php');
require_once (ROOT_PATH . 'includes/helper/uploader.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'admin/distribution.inc.php');
require_once (ROOT_PATH . 'includes/lib_common.php');

//验证权限
admin_priv("batchDisOrder");


$act = // 动作
isset ( $_REQUEST ['act'] ) && in_array ( $_REQUEST ['act'], array ('upload','file_search','order_search','close') ) ? $_REQUEST ['act'] : null;

$Message = 0; //表示正常流程

/*
	处理上一个重定向传来的已经上传成功的文件。
	此文件会分别以file_id走文件查询和订单查询流程。
*/
if(isset($_REQUEST['Message']) && isset($_REQUEST['FileId'])){
	if($_REQUEST['Message'] == 754050 &&!empty($_REQUEST['FileId'])){
		$Message = 754050;
		$act = 'file_search';
		$last_file_id = trim($_REQUEST['FileId']);
		$smarty->assign('last_file_id_',$last_file_id);
		//设置提醒为成功样式
		$smarty->assign('upload_alert_type','alert-success');
		$smarty->assign('message','成功上传文件！（另附文件及订单信息在【查询】两栏）');
	}
	if($_REQUEST['Message'] == 569566 &&!empty($_REQUEST['FileId'])){
		$Message = 569566;
		$act = 'order_search';
		$last_file_id = trim($_REQUEST['FileId']);
		$smarty->assign('last_file_id',$last_file_id);
		$last_file_sql = "f.file_id = '{$last_file_id}'";
	}
	if($_REQUEST['Message'] == 486156 &&!empty($_REQUEST['FileId'])){
		$Message = 486156;
		$act = 'order_search';
		$last_file_id = trim($_REQUEST['FileId']);
		$smarty->assign('last_file_id',$last_file_id);
		$last_file_sql = "f.file_id = '{$last_file_id}'";
	}
}

// 当前时间 
$now = date ( 'Y-m-d H:i:s' );

QLog::log ( "分销订单导入开始：{$act} " );

$get_payments = getPayments();
$get_pay_id = array_keys($get_payments);
$get_facility = get_available_facility($_SESSION['party_id']);
$get_facility_id = array_keys($get_facility);
$get_shipping = getShippingTypes();
$get_shipping_id = array_keys($get_shipping);
$smarty->assign('tab_show',1);
if (isset($act)) {
	if($act == 'upload'){
			//判断组织是否为具体的业务单位
			if(!party_explicit($_SESSION['party_id'])) {
			    sys_msg("请选择具体的组织后再来录入订单");
			}

			$db->start_transaction(); 
			try{			
			$lock_name = "batchDisOrderV2"."{$_SESSION['party_id']}";
			if (!wait_file_lock($lock_name, 5)) {
				release_file_lock ($lock_name);
			    die('操作超时，请稍等10分钟后重试，请核实是否有【相同业务组】进行批量录单操作。如长时间出现该界面，请联系erp组。');
			}
			create_file_lock($lock_name);

			// excel读取设置
			$tpl = array('分销订单导入'  =>
				array('temp_order_sn'=>'临时订单号',
					  'distribution_id'=>'分销商名称',
					  'consignee'=>'收货人',
					  'outer_type'=>'外部订单类型',
					  'taobao_order_sn'=>'外部订单号',
					  'taobao_id'=>'淘宝ID',
					  'telephone'=>'联系电话',
					  'mobile'=>'手机',
					  'province'=>'省',
					  'city'=>'市',
					  'district'=>'区',
					  'address'=>'详细地址',
					  'shipping_type'=>'配送方式ID',
					  'currency'=>'币种',//added by wliu 20160318
					  'shipping_fee'=>'快递费用',
					  'goods_amount'=>'商品金额',
					  'bonus'=>'订单红包',	
					  'order_amount'=>'订单金额',
					  'price'=>'单价',		
					  'quantity'=>'数量',
					  'shop_code'=>'商家编码',
					  'pay_id'=>'支付方式ID',
					  'facility_id'=>'发货仓库ID',
					  'pay_number'=>'支付流水号'
				));
			QLog::log ( '订单开始读取并导入：' );
			/* 文件上传并读取 */
			$uploader = new Helper_Uploader ();
			$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
			
			if (! $uploader->existsFile ( 'excel' )) {
				throw new Exception('没有选择上传文件，或者文件上传失败');
				
			}
			
			// 取得要上传的文件句柄
			$file = $uploader->file ( 'excel' );

			// 检查上传文件
			if (! $file->isValid ( 'xls, xlsx', $max_size )) {
				throw new Exception('非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB' );
			}
			
			//取得文件名
			$file_name = $file -> filename();

			// 读取excel
			$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
			if (! empty ( $failed )) {
				throw new Exception(reset ( $failed ));
			}

			/* 检查数据  */
			$rowset = $result ['分销订单导入'];

			// 订单数据读取失败
			if (empty ( $rowset )) {
				throw new Exception('excel文件中没有数据,请检查文件');
			}
			 // var_dump($rowset);die();		


			$order_check_array = array('temp_order_sn','distribution_id','outer_type','consignee','province','city','address',
						'shipping_type','shipping_fee','goods_amount','order_amount','price','bonus','quantity','shop_code','pay_id','facility_id');
			foreach ($order_check_array as $check_column) {
				$region_array = Helper_Array::getCols($rowset, $check_column);
				$region_size = count($region_array);
				Helper_Array::removeEmpty ($region_array);	
				if($region_size > count($region_array)){
					throw new Exception('【'.$tpl['分销订单导入'][$check_column].'】为必填数据，请检查该数据是否填写完整！');
				}
				if($check_column == 'temp_order_sn'){
					$temp_order_sn = array_unique($region_array);
					$temp_order_sn_count = count($temp_order_sn);
					// var_dump($temp_order_sn_count);
					if($temp_order_sn_count > 500){
						throw new Exception('每次上传订单数不能超过500条,请将文件拆分后分别上传');
					}
				}
			}
			 
			//验证淘宝订单号在ecs_order_info中是否已经存在，防止订单重复
			foreach (Helper_Array::getCols($rowset, 'taobao_order_sn') as $item_value) {
				$item_value = trim($item_value);
				if(!empty($item_value)) {
					$sql = "select 1 from ecshop.ecs_order_info where taobao_order_sn = '{$item_value}'
							union
							select 1 from ecshop.ecs_import_order_info where taobao_order_sn = '{$item_value}' and status <> 'C'";
					$exists = $db->getOne($sql, true);
		            if ($exists) {
						throw new Exception("该淘宝外部订单号已经存在：".$item_value);	
		            }
				}
			}
			
			foreach(Helper_Array::getCols($rowset, 'consignee') as $item_value) {
				if(preg_match('/[#&\'\"]+/',$item_value)) {
					throw new Exception("【收货人】中不能含有标点符号等非法字符：".$item_value);
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'shipping_type') as $item_value) {
				if(!in_array($item_value,$get_shipping_id)){
					throw new Exception("系统中不存在该【配送方式ID】：".$item_value);
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'facility_id') as $item_value) {
				if(!in_array($item_value,$get_facility_id)){
					throw new Exception("系统中不存在该【发货仓库ID】：".$item_value);
				}
			}

			foreach (Helper_Array::getCols($rowset, 'pay_id') as $item_value) {
				if(!in_array($item_value,$get_pay_id)){
					throw new Exception('系统中不存在该【支付方式ID】：'.$item_value);
				}
			}
	
			//added by wliu 20160318 过滤错误的币种填写
			foreach (Helper_Array::getCols($rowset, 'currency') as $item_value) {
				$item_value = trim($item_value);
				if(!empty($item_value)){
					if(!in_array($item_value, array('USD','AUD','CNY','SEK','NOK','EUR','GBP','HKD',
						'JPY','DKK','NZD','RMB','CHF','CAD','TWD'))){
						throw new Exception('不存在该【币种】'.$item_value);
					}
				}
			}

			foreach (Helper_Array::getCols($rowset, 'shipping_fee') as $item_value) {
				if($item_value < 0){
					throw new Exception('【快递费用】不能小于0：'.$item_value);
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'goods_amount') as $item_value) {
				if($item_value < 0){
					throw new Exception('【商品金额】不能小于0：'.$item_value);
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'order_amount') as $item_value) {
				if($item_value < 0){
					throw new Exception('【订单金额】不能小于0：'.$item_value);
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'price') as $item_value) {
				if($item_value < 0){
					throw new Exception('【单价】不能小于0：'.$item_value);
				}
			}
						
			foreach (Helper_Array::getCols($rowset, 'bonus') as $item_value) {
				if($item_value > 0){
					throw new Exception('【红包】不能大于0：'.$item_value);
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'quantity') as $item_value) {
				if(!(preg_match ('/^[1-9]([0-9]+)?$/', $item_value) && $item_value > 0)){
					throw new Exception('【商品数量】必须为正整数：'.$item_value);
				}
			}


			//验证分销商是不是都存在
			foreach (array_unique(Helper_Array::getCols($rowset, 'distribution_id')) as $item_value) {
				$sql = "select count(*) from ecshop.distributor where name = '{$item_value}' and party_id = '{$_SESSION['party_id']}'";
				if(!$db->getOne($sql)){
					throw new Exception('【分销商名称】输入错误，请检查是否有此分销商：'.$item_value.'<br>（检查所处业务组是否正确)');
				}
			}
			
			//验证外部订单类型是不是都存在
			foreach (array_unique(Helper_Array::getCols($rowset, 'outer_type')) as $item_value) {
				if(!in_array($item_value,$_CFG['adminvars']['outer_type']) && $item_value != '无'){
					throw new Exception('【外部订单类型】输入错误：'.$item_value);
				}
			}
			
			//验证商家编码是否已填
			foreach (array_unique(Helper_Array::getCols($rowset, 'shop_code')) as $item_value) {
				if(!(preg_match('/^TC-[0-9]+$/',$item_value) || preg_match('/^[0-9]+_[0-9]+$/',$item_value) || preg_match('/^[0-9]+$/',$item_value))) {
					throw new Exception('【商家编码】输入错误，请查询后再导入：'.$item_value);
				}
			}
				
			// 验证套餐编码存在性
			$tc_codes = array();
			foreach (array_unique(Helper_Array::getCols($rowset, 'shop_code')) as $item_value) {
				if(preg_match('/^TC-[0-9]+$/',$item_value)) {
					$tc_codes[] = $item_value;
				}
			}
			$group_order_goods = array();
			if(!empty($tc_codes)) {
				$tc_codes = array_unique($tc_codes);
				//var_dump('$tc_codes');var_dump($tc_codes);
				$tc_info = get_group_order_goods($tc_codes,$_SESSION['party_id']);	
				//var_dump('$tc_info');var_dump($tc_info);	
				$group_order_goods = $tc_info['group_order_goods']['code'];
				//var_dump('$group_order_goods');var_dump($group_order_goods);
				if(!empty($group_order_goods)) {
					$real_tc_codes = array_unique($tc_info['codes']['code']);
					$diff_tc_codes = array_diff($tc_codes,$real_tc_codes);

				}else{
					throw new Exception('系统中找不到该导入文件中所有的该【商家编码】（套餐编码），请检查(确认是否切对组织)');
				}				
				if(!empty($diff_tc_codes)){
					throw new Exception('系统中找不到该【商家编码】（套餐编码），请检查(确认是否切对组织)：'.implode(',',$diff_tc_codes));
				
				}			
			}

			// // 验证套餐编码存在性
			// $tc_codes = array();
			// foreach (array_unique(Helper_Array::getCols($rowset, 'shop_code')) as $item_value) {
			// 	if(preg_match('/^TC-[0-9]+$/',$item_value)) {
			// 		$tc_codes[] = $item_value;
			// 	}
			// }
			// $group_order_goods = array();
			// if(!empty($tc_codes)) {
			// 	$tc_codes = array_unique($tc_codes);
			// 	$tc_sql = "SELECT dg.code,dg.amount
			// 			   FROM 
			// 			   		ecshop.distribution_group_goods dg 
			// 			   WHERE 
			// 					dg.code ".db_create_in($tc_codes)." and party_id='{$party_id}' and status='OK' ";
			// 	$group_order_goods = array_unique($db -> getAll($tc_sql));
			// 	$real_tc_codes = Helper_Array::getCols($group_order_goods,'code');
			// 	var_dump('group_order_goods');var_dump($group_order_goods);
			// 	var_dump('real_tc_codes');var_dump($real_tc_codes);
			// 	if(!empty($real_tc_codes)) {
			// 		$diff_tc_codes = array_diff($tc_codes,$real_tc_codes);
			// 	}else{
			// 		throw new Exception('系统中找不到该导入文件中所有的该【商家编码】（套餐编码），请检查(确认是否切对组织)');
			// 	}				
			// 	if(!empty($diff_tc_codes)){
			// 		throw new Exception('系统中找不到该【商家编码】（套餐编码），请检查(确认是否切对组织)：'.implode(',',$diff_tc_codes));
			// 	}
			// }
			
			$telephone = array();
			$mobile = array();
			foreach($rowset as $row) {
				if(empty($row['telephone']) && empty($row['mobile'])) {
					throw new Exception('【联系电话】和【手机】必须填一个');
				}
			}
					
			$order_items = Helper_Array::groupBy($rowset, 'temp_order_sn');
			// var_dump('$order_items');var_dump($order_items);
			$keys = "";
			$keys_goods = "";
			
			foreach ($order_items as $key=>$order_attr) {
				
				if(round($order_attr[0]['shipping_fee'] + $order_attr[0]['goods_amount'] + $order_attr[0]['bonus'],6) != $order_attr[0]['order_amount']){
					$keys .= $key.",";
				}else{
					$order_goods_amount = 0;
					foreach ($order_attr as $order_goods_attr) {
						$order_goods_amount += $order_goods_attr['price']*$order_goods_attr['quantity'];
					}
					if(round($order_goods_amount,6) != $order_attr[0]['goods_amount']){
						$keys_goods .= $key.",";
					}
					
					// 判断套餐金额和单价
					foreach ($order_attr as $order_goods_attr) {
						if(preg_match('/^TC-[0-9]+$/',$order_goods_attr['shop_code'])) {
							if(abs($group_order_goods[$order_goods_attr['shop_code']][0]['amount'] - $order_goods_attr['price']) > 0.000001) {
								$tc_price_error .= " 订单：".$key." 套餐价格：".$group_order_goods[$order_goods_attr['shop_code']][0]['amount']." 单价：".$order_goods_attr['price'].",";
								break;
							}
						}
					}
					
				}
			}
			
			if($keys){
				throw new Exception('以下订单的快递费+商品金额-订单红包与订单费用不相同，请检查：'.$keys);
			}
			if($keys_goods){
				throw new Exception('以下订单的商品金额之和与订单商品金额不相同，请检查：'.$keys_goods);
			}
			if($tc_price_error){
				throw new Exception('以下订单的erp套餐金额和单价不相同，请检查：'.$tc_price_error);
			}
			//$order_check_array = array('order_sn','distribution_id','consignee','province','city',
			//			'shipping_type','shipping_fee','goods_amount','order_amount','price','quantity');

			QLog::log ( '订单数据验证完毕，开始插入文件表：' );
			$file_sql = "INSERT INTO ecshop.ecs_import_order_file(
														party_id,
														filename,
														action_user,
														action_time,
														total_count
														)
						VALUES 
								(
									'{$_SESSION['party_id']}',
									'{$file_name}',
									'{$_SESSION['admin_name']}',
									now(),
									'{$temp_order_sn_count}'
								)
						";
			$file_result = $db -> query($file_sql);
			if($file_result == false){
				$db -> rollback();
				throw new Exception('文件上传失败！请检查sql'.$file_sql.' 错误代码：'.$db->errno().' 错误消息：'.$db->ErrorMsg());
			}
			$file_id = $db -> insert_id();
			foreach ($order_items as $key=>$order_attr) {
				//批量去除单引号
				foreach($order_attr[0] as $k => $v){
					$order_attr[0][$k] =remove_single_quote($v);
				}
				$order = array();
				$order['distributor_id'] = $db->getOne("select distributor_id from ecshop.distributor where name = '{$order_attr[0]['distribution_id']}' and party_id = '{$_SESSION['party_id']}'");
				$order['distributor_name']=$order_attr[0]['distribution_id'];//Added by Sinri 20151030
				$order['consignee'] = $order_attr[0]['consignee'];
				$order['taobao_order_sn'] = trim($order_attr[0]['taobao_order_sn']);
				$order['outer_type'] = array_search($order_attr[0]['outer_type'],$_CFG['adminvars']['outer_type']);
				$order['mobile'] = $order_attr[0]['mobile'];
				$order['tel'] = $order_attr[0]['telephone'];
				$order['province'] = get_region_by_name_type($order_attr[0]['province'],1);
				if(empty($order['province'])){
					throw new Exception('省字段填写错误：“'.$order_attr[0]['province']."”<br>(注意该字段不必“省”字结尾，上海等市也填此栏)");
				}
				$order['city'] = get_region_by_name_type($order_attr[0]['city'],2);
				if(empty($order['city'])){
					throw new Exception('市字段填写错误：“'.$order_attr[0]['city']."”<br>(注意该字段不必“市”字结尾，上海等市下区此处需要“区”字结尾)");
				}
				$order['district'] = get_region_by_name_type($order_attr[0]['district'],3);
				$order['address'] = $order_attr[0]['address'];
				$order['shipping_id'] = $order_attr[0]['shipping_type'];
				$order['shipping_fee'] = $order_attr[0]['shipping_fee'];
				$order['bonus'] = $order_attr[0]['bonus'];
				$order['goods_amount'] = $order_attr[0]['goods_amount'];
				$order['order_amount'] = $order_attr[0]['order_amount'];
				$order['pay_id'] = $order_attr[0]['pay_id'];
				// added by wliu 20160318
				if(empty($order_attr[0]['currency'])){
					$order['currency'] = '人民币';
				}else{
					$order['currency'] = $order_attr[0]['currency'];
				}
				$order['facility_id'] = $order_attr[0]['facility_id'];
				$order['shop_code'] = $order_attr[0]['shop_code'];//no effect
				$order['nick_name'] = $order_attr[0]['taobao_id'];
				$order['pay_number'] = $order_attr[0]['pay_number'];
								
				// var_dump('$order_attr');var_dump($order_attr);
								
				$order_goods = array();
				QLog::log ( '订单数据开始插入order表：' );
				foreach ($order_attr as $order_goods_attr) {
					// 普通商品
					if(preg_match('/^[0-9]+_[0-9]+$/',$order_goods_attr['shop_code']) || preg_match('/^[0-9]+$/',$order_goods_attr['shop_code'])) {
						$order_goods_item = array();
						$order_goods_item['shop_code'] = $order_goods_attr['shop_code'];
						if(preg_match('/^[0-9]+_[0-9]+$/',$order_goods_attr['shop_code']) && !preg_match('/^[0-9]+_0$/',$order_goods_attr['shop_code'])){
							$goods_style_id = explode("_",$order_goods_attr['shop_code']);
							$order_goods_item['goods_id'] = $goods_style_id[0];
							$order_goods_item['style_id'] = $goods_style_id[1];
							$sql = "select count(*) from ecshop.ecs_goods_style where goods_id = '{$order_goods_item['goods_id']}' and style_id = '{$order_goods_item['style_id']}' and is_delete=0 ";
						}else if(preg_match('/^[0-9]+$/',$order_goods_attr['shop_code']) || preg_match('/^[0-9]+_0$/',$order_goods_attr['shop_code'])){
							$order_goods_item['goods_id'] = str_replace('_0','',$order_goods_attr['shop_code']);
							$order_goods_item['style_id'] = 0;
							$sql = "select count(*) from ecshop.ecs_goods where goods_id = '{$order_goods_item['goods_id']}'";
						}
						$count = $db->getROw($sql);
						if($count['count(*)'] == 0) {
							throw new Exception('系统异常，下面商家编码找不到对应商品，请检查后重新导入：'.$order_goods_attr['shop_code']);
						} else {
						$order_goods_item['price'] = $order_goods_attr['price'];
						$order_goods_item['goods_number'] = $order_goods_attr['quantity'];
						$order_goods[] = $order_goods_item;
						}
					} 
					// 套餐
					else if(preg_match('/^TC-[0-9]+$/',$order_goods_attr['shop_code'])) {
						$order_goods_item['shop_code'] = $order_goods_attr['shop_code'];
						$order_goods_item['price'] = $order_goods_attr['price'];
						$order_goods_item['goods_number'] = $order_goods_attr['quantity'];
						$order_goods[] = $order_goods_item;
						} 
						else 
						{
				           throw new Exception('系统异常，下面商家编码找不到对应商品：'.$order_goods_attr['shop_code']);
						}
					}
				
			   // var_dump("order");var_dump($order);var_dump("order_goods");var_dump($order_goods);die();
				$order_sql = "INSERT INTO ecshop.ecs_import_order_info(  
																file_id,
																party_id,
																distributor_id,
																consignee,
																outer_type,
																taobao_order_sn,
																taobao_id,
																telephone,
																mobile,
																province,
																city,
																district,
																address,
																shipping_id,
																shipping_fee,
																goods_amount,
																bonus,
																order_amount,
																pay_id,
																currency,
																facility_id,
																pay_number,
																create_time
															)
							VALUES 
									(
										'{$file_id}',
										'{$_SESSION[party_id]}',
										'{$order['distributor_id']}',
										'{$order['consignee']}',
										'{$order['outer_type']}',
										'{$order['taobao_order_sn']}',
										'{$order['nick_name']}',
										'{$order['tel']}',
										'{$order['mobile']}',
										'{$order['province']}',
										'{$order['city']}',
										'{$order['district']}',
										'{$order['address']}',
										'{$order['shipping_id']}',
										'{$order['shipping_fee']}',	
										'{$order['goods_amount']}',
										'{$order['bonus']}',
										'{$order['order_amount']}',
										'{$order['pay_id']}',
										'{$order['currency']}',
										'{$order['facility_id']}',
										'{$order['pay_number']}',
										NOW()
									)
							";
				$order_result = $db -> query($order_sql);
				if($order_result == false){
					$db -> rollback();
					throw new Exception('订单信息写入失败！请检查sql'.$order_sql.' 错误代码：'.$db->errno().' 错误消息：'.$db->ErrorMsg());
				}						
				$order_id = $db -> insert_id();

				foreach ($order_goods as $item) {
					$goods_sql = "INSERT INTO ecshop.ecs_import_order_goods(
																		import_order_id,
																		shop_code,
																		price,
																		quantity,
																		create_time
																		)
								VALUES 
										(
											'{$order_id}',
											'{$item['shop_code']}',
											'{$item['price']}',
											'{$item['goods_number']}',
											NOW()
										)
								";
					$goods_result = $db -> query($goods_sql);
					if($goods_result == false){
						$db -> rollback();
						throw new Exception('商品信息写入失败！请检查sql'.$goods_sql.' 错误代码：'.$db->errno().' 错误消息：'.$db->ErrorMsg());
					}
				}
			}
			//	提交事务
			$db->commit();
			$file->unlink();
			$smarty->assign ('message', "导入完毕！<br/>");
			QLog::log ( '订单数据插入order表完毕：' );
			release_file_lock ($lock_name);
			//重定向以免重复提交、message判断成功上传、FileId以供查出相关文件和订单信息
			header("Location:batchDisOrderV2.php?Message=754050&FileId={$file_id}");
 		    exit();
		}catch(Exception $e){
			QLog::log ( '订单导入报错了，错误原因:'.$e->getMessage());
			$db->rollback();
			release_file_lock ( $lock_name );
			$smarty->assign('tab_show',1);
			//设置提醒为错误样式
			$smarty->assign('upload_alert_type','alert-error');
			$smarty->assign ('message',$e->getMessage());
		}
	}

/*
* 文件处理关闭
*/
	if($act == "close"){
		 $file_id = $_REQUEST['file_id'];
		 $sql = "update ecshop.ecs_import_order_file set status='C' where file_id=".$file_id;
		 $db->query($sql);
		 $sql = "update ecshop.ecs_import_order_info set status='C' where file_id=".$file_id;
		 $db->query($sql);
		 $act = "file_search";
	}

/*
	文件查询	
*/
	if($act == 'file_search'){
	try{
		/*
			以下实现分页操作
		*/
		//每页显示条数
		$file_perNumber = 20;

		$last_file_sql = $file_name_sql = $file_time_sql = $file_user_sql = $file_action_sql = "";

		if(isset($last_file_id)&&!empty($last_file_id)){
			$last_file_sql = "f.file_id = '{$last_file_id}'";
		}else{
			if(!empty($_REQUEST['start_validity_time'])&&!empty($_REQUEST['end_validity_time'])){
				if($_REQUEST['start_validity_time']>$_REQUEST['end_validity_time']){
					throw new Exception("【开始时间】大于【结束时间】！");
				}
				$file_time_sql ="f.action_time >= '{$_REQUEST['start_validity_time']} 00:00:00' 
								and f.action_time <= '{$_REQUEST['end_validity_time']} 23:59:59' ";
			}else{
				throw new Exception("【开始时间】或【结束时间】不能是空！");
			}
			
			// $smarty->assign('start_validity_time', $_REQUEST['start_validity_time']);

			// $smarty->assign('end_validity_time', $_REQUEST['end_validity_time']);
			
			//判断是否存在作为条件的管理员名
			if(isset($_REQUEST['action_user'])){
				if(!empty($_REQUEST['action_user'])){
				$_REQUEST['action_user'] = trim($_REQUEST['action_user']);
				if(!$db->getOne("select 1 from ecshop.ecs_admin_user where user_name = '{$_REQUEST['action_user']}'")){
					$mybe_admin = $db->getCol("select distinct(user_name) from ecshop.ecs_admin_user where user_name 
												like '%{$_REQUEST['action_user']}%' ORDER BY last_time DESC LIMIT 6");
					if($mybe_admin){
						throw new Exception("没有这个【用户名】！<br>可能的【用户名】有：".implode(',',$mybe_admin));
					}else{
						throw new Exception("没有这个【用户名】！");
					}		
				}else{
					$file_user_sql = "AND f.action_user = '{$_REQUEST['action_user']}'";
					// $smarty->assign('action_user', $action_user);
					}
				}
			}
			if(isset($_REQUEST['file_status'])){
				if(!empty($_REQUEST['file_status'])){
					$file_action_sql = "AND f.status = '{$_REQUEST['file_status']}'";
					// $smarty->assign('file_status', $file_status);
				}
			}
			//判断是否存在作为条件的文件名
			if(isset($_REQUEST['file_name'])){
				if(!empty($_REQUEST['file_name'])){
					$_REQUEST['file_name'] = trim($_REQUEST['file_name']);
					if(!$db->getOne("select 1 from ecshop.ecs_import_order_file f where f.filename = '{$_REQUEST['file_name']}'
									and {$file_time_sql}{$file_user_sql}{$file_action_sql}")){
						$mybe_file= $db->getCol("select distinct(f.filename) from ecshop.ecs_import_order_file f where f.filename 
												like '%{$_REQUEST['file_name']}%' and {$file_time_sql}{$file_user_sql}{$file_action_sql}
												ORDER BY action_time DESC LIMIT 6 ");
						if($mybe_file){
							throw new Exception("此【用户名】的该【时间段】内不存在此【文件处理状态】的【文件名】！<br>可能的【文件名】有：".implode(',',$mybe_file));
						}else{
							throw new Exception("此【用户名】的该【时间段】内不存在此【文件处理状态】的【文件名】！");
						}	
					}
					$file_name_sql = "AND f.filename = '{$_REQUEST['file_name']}'";
					// $smarty->assign('file_name', $_REQUEST['file_name']);
				}
			}
			
		}

		$file_count_sql = "SELECT COUNT(*) 
					FROM ecshop.ecs_import_order_file f 
					WHERE {$last_file_sql}{$file_time_sql}
					{$file_name_sql}{$file_user_sql}{$file_action_sql}";

		$file_count = $db -> getOne($file_count_sql);	

		if($file_count == 0){
			throw new Exception("没有取到数据，可能真的没有数据了，可能查询条件有误<br>（注意【开始时间】默认5天前）");	
		}

		//总页数
		$file_totalPage = ceil($file_count/$file_perNumber);

		//当前页数
		$file_page = isset($_REQUEST['file_page'])
					 ?$_REQUEST['file_page']
					 :1;

		$smarty->assign('file_totalPage',$file_totalPage); 

		$smarty->assign('file_page',$file_page); 

		$file_startCount=($file_page-1)*$file_perNumber;

		$file_search_sql = "SELECT 	
									f.file_id,
									f.filename,
									f.action_user,
									f.action_time,
									f.total_count,
									f.done_count,
									f.process_time,
									f.status,
									f.note,
									p.name AS party_name
							FROM ecshop.ecs_import_order_file f 
							LEFT JOIN romeo.party p ON f.party_id = p.party_id
							WHERE {$last_file_sql}{$file_time_sql}
							{$file_name_sql}{$file_user_sql}{$file_action_sql}
							LIMIT {$file_startCount},{$file_perNumber}
							";

		$file_result = $db -> getAll($file_search_sql);
		// var_dump($file_result);
		if($file_result){
			$smarty->assign('file_result', $file_result);
		}else{
			throw new Exception("没有取到数据，可能真的没有数据了，可能查询条件有误<br>（注意【开始时间】默认5天前）");	
		}
		if(isset($last_file_sql)){
			if(!empty($last_file_sql)){
				$act = 'order_search';
				$smarty->assign('file_alert_type','alert-success');
				$smarty->assign('file_message','成功上传文件，以下为上传文件信息：');				
			}
		}
		$smarty->assign('tab_show',2);
		}catch(Exception $e){
			$smarty->assign('tab_show',2);
			$smarty->assign('file_alert_type','alert-error');
			if(isset($file_count)){
				if($file_count == 0){//取不出数据为提醒样式、不是错误样式
					$smarty->assign('file_alert_type','');
				}
			}
			$smarty->assign ('file_message',$e->getMessage());
		}
	}

/*
	订单查询	
*/
	if($act == 'order_search'){
		try{
		$order_perNumber = 20;

		$order_time_sql = $order_file_sql = $order_user_sql = $outer_order_sql = $order_consignee_sql = $order_tel_sql = $order_status_sql = $limit_sql = "";

		if(!(isset($last_file_sql) && !empty($last_file_sql))){
			
			if(!empty($_REQUEST['start_validity_time_'])&&!empty($_REQUEST['end_validity_time_'])){
				if($_REQUEST['start_validity_time_']>$_REQUEST['end_validity_time_']){
					throw new Exception("【开始时间】大于【结束时间】！");
				}
				$order_time_sql ="oi.create_time >= '{$_REQUEST['start_validity_time_']} 00:00:00' 
								and oi.create_time <= '{$_REQUEST['end_validity_time_']} 23:59:59' ";
			}else{
				throw new Exception("【开始时间】或【结束时间】不能是空！");
			}

			// $smarty->assign('start_validity_time_', $_REQUEST['start_validity_time_']);

			// $smarty->assign('end_validity_time_', $_REQUEST['end_validity_time_']);					
			
			//判断是否存在作为条件的管理员名
			if(isset($_REQUEST['action_user_'])){
				if(!empty($_REQUEST['action_user_'])){
				$_REQUEST['action_user_'] = trim($_REQUEST['action_user_']);
				if(!$db->getOne("select 1 from ecshop.ecs_admin_user where user_name = '{$_REQUEST['action_user_']}'")){
					$mybe_admin_ = $db->getCol("select distinct(user_name) from ecshop.ecs_admin_user where user_name 
												like '%{$_REQUEST['action_user_']}%' ORDER BY last_time DESC LIMIT 6");
					if($mybe_admin_){
						throw new Exception("没有这个【用户名】！<br>可能的【用户名】有：".implode(',',$mybe_admin_));
					}else{
						throw new Exception("没有这个【用户名】！");
					}		
				}else{
					$order_user_sql = "AND f.action_user = '{$_REQUEST['action_user_']}'";
					// $smarty->assign('action_user_', $action_user_);
					}
				}
			}
			if(isset($_REQUEST['file_name_'])){	
				if(!empty($_REQUEST['file_name_'])){
				$_REQUEST['file_name_'] = trim($_REQUEST['file_name_']);
					if(!$db->getOne("select 1 from ecshop.ecs_import_order_file f where f.filename = '{$_REQUEST['file_name_']}'{$order_user_sql}")){
						$mybe_file_ = $db->getCol("select distinct(f.filename) from ecshop.ecs_import_order_file f where f.filename 
												like '%{$_REQUEST['file_name_']}%'{$order_user_sql} ORDER BY action_time DESC LIMIT 6");
						if($mybe_file_){
							throw new Exception("此【用户名】下不存在此【文件名】！<br>可能的【文件名】有：".implode(',',$mybe_file_));
						}else{
							throw new Exception("此【用户名】下不存在此【文件名】！");
						}	
					}
				$order_file_sql = "AND f.filename = '{$_REQUEST['file_name_']}'";
				// $smarty->assign('file_name_', $_REQUEST['file_name_']);
				}
			}
			if(isset($_REQUEST['order_status'])){
				if(!empty($_REQUEST['order_status'])){
					$order_status_sql = "AND oi.status = '{$_REQUEST['order_status']}'"; 
					// $smarty->assign('order_status', $_REQUEST['order_status']);
				}
			}
			if(isset($_REQUEST['taobao_order_sn'])){
				if(!empty($_REQUEST['taobao_order_sn'])){
				$_REQUEST['taobao_order_sn'] = trim($_REQUEST['taobao_order_sn']);	
				if(!$db->getOne("select 1 from ecshop.ecs_import_order_info oi INNER JOIN ecshop.ecs_import_order_file f on f.file_id = oi.file_id
								 where oi.taobao_order_sn = '{$_REQUEST['taobao_order_sn']}' and {$order_time_sql}
								 {$order_user_sql}{$order_file_sql}{$order_status_sql}")){
					throw new Exception("在该【时间段】下此【用户名】的【文件名】下不存在此【订单处理状态】的【外部订单号】！");
				}
				$outer_order_sql = "AND oi.taobao_order_sn = '{$_REQUEST['taobao_order_sn']}'";
				// $smarty->assign('taobao_order_sn', $_REQUEST['taobao_order_sn']);
				}
			}
			if(isset($_REQUEST['consignee'])){
				if(!empty($_REQUEST['consignee'])){
					$_REQUEST['consignee'] = trim($_REQUEST['consignee']);
					if(preg_match('/[#&\'\"]+/',$_REQUEST['consignee'])) {
						throw new Exception("【收货人】中不能含有标点符号等非法字符");
					}
					if(!$db->getOne("select 1 from ecshop.ecs_import_order_info oi INNER JOIN ecshop.ecs_import_order_file f on f.file_id = oi.file_id
								 where oi.consignee = '{$_REQUEST['consignee']}' and {$order_time_sql}
								 {$order_user_sql}{$order_file_sql}{$order_status_sql}")){
						$mybe_consignee = $db->getCol("select distinct(consignee) from ecshop.ecs_import_order_info oi 
								INNER JOIN ecshop.ecs_import_order_file f on f.file_id = oi.file_id
								where consignee like '%{$_REQUEST['consignee']}%' and {$order_time_sql}
								 {$order_user_sql}{$order_file_sql}{$order_status_sql} ORDER BY create_time DESC LIMIT 6");
						if($mybe_consignee){
								throw new Exception("在该【时间段】下此【用户名】的【文件名】下不存在此【订单处理状态】的【收货人】！<br>可能的【收货人】有：".implode(',',$mybe_consignee));
						}else{
							throw new Exception("在该【时间段】下此【用户名】的【文件名】下不存在此【订单处理状态】的【收货人】！");
						}
					}
					$order_consignee_sql = "AND oi.consignee = '{$_REQUEST['consignee']}'";
					// $smarty->assign('consignee', $_REQUEST['consignee']);
				}
			}
			if(isset($_REQUEST['tel'])){
				if(!empty($_REQUEST['tel'])){
				$_REQUEST['tel'] = trim($_REQUEST['tel']);
				if(!$db->getOne("select 1 from ecshop.ecs_import_order_info oi INNER JOIN ecshop.ecs_import_order_file f on f.file_id = oi.file_id
								 where (oi.mobile = '{$_REQUEST['tel']}' OR oi.telephone = '{$_REQUEST['tel']}') and {$order_time_sql}
								 {$order_user_sql}{$order_file_sql}{$order_status_sql}")){
					throw new Exception("在该【时间段】下此【用户名】的【文件名】下不存在此【订单处理状态】的【手机/电话】！");
				}
				$order_tel_sql = "AND (oi.mobile = '{$_REQUEST['tel']}' OR oi.telephone = '{$_REQUEST['tel']}')";
				// $smarty->assign('tel', $_REQUEST['tel']);
				}
			}	
		}

		$order_count_sql = "SELECT 
									COUNT(*)
							FROM ecshop.ecs_import_order_info oi 
							INNER JOIN ecshop.ecs_import_order_file f ON f.file_id = oi.file_id
							INNER JOIN ecshop.ecs_import_order_goods og ON og.import_order_id = oi.import_order_id
							WHERE {$last_file_sql}{$order_time_sql}{$order_user_sql}
							{$order_file_sql}{$outer_order_sql}{$order_consignee_sql}{$order_tel_sql}{$order_status_sql}
							";
		//将要取出数据的总条数
		$order_count = $db -> getOne($order_count_sql);
		
		if($order_count == 0){
			throw new Exception("没有取到数据，可能真的没有数据了，可能查询条件有误<br>（注意【开始时间】默认5天前）");	
		}
		//总页数
		$order_totalPage = ceil($order_count/$order_perNumber);

		$order_page = isset($_REQUEST['order_page'])
					 ?$_REQUEST['order_page']
					 :1;

		$smarty->assign('order_totalPage',$order_totalPage); 

		$smarty->assign('order_page',$order_page); 

		$order_startCount=($order_page-1)*$order_perNumber;

		if($Message != 486156){
			$limit_sql = "LIMIT {$order_startCount},{$order_perNumber}";
		}

		$order_search_sql = "SELECT 
									f.filename,
									f.action_user,
									f.status as fstaues,
									p.name AS party_name,
									d.name AS distributor_name,
									oi.consignee,
									oi.outer_type,
									oi.taobao_order_sn,
									oi.taobao_id,
									oi.telephone,
									oi.mobile,
									r1.region_name AS province,
									r2.region_name AS city,
									r3.region_name AS district,
									oi.address,
									oi.shipping_id,
									s.shipping_name,
									oi.shipping_fee,
									oi.goods_amount,
									oi.bonus,
									oi.order_amount,
									oi.pay_id,
									ep.pay_name,
									oi.currency,
									oi.facility_id,
									rf.facility_name,
									oi.pay_number,
									oi.create_time,
									oi.status,
									oi.erp_order_id,
									oi.erp_order_sn,
									oi.note,
									og.shop_code,
									og.price,
									og.quantity
							FROM ecshop.ecs_import_order_info oi 
							INNER JOIN ecshop.ecs_import_order_file f ON f.file_id = oi.file_id
							INNER JOIN ecshop.ecs_import_order_goods og ON og.import_order_id = oi.import_order_id
							LEFT JOIN romeo.party p ON oi.party_id = p.party_id
							LEFT JOIN ecshop.distributor d ON d.distributor_id = oi.distributor_id
							LEFT JOIN ecshop.ecs_region r1 ON oi.province = r1.region_id AND r1.region_type = 1
							LEFT JOIN ecshop.ecs_region r2 ON oi.city = r2.region_id AND r2.region_type = 2
							LEFT JOIN ecshop.ecs_region r3 ON oi.district = r3.region_id AND r3.region_type = 3
							LEFT JOIN romeo.facility rf on oi.facility_id=rf.FACILITY_ID
							LEFT JOIN ecshop.ecs_shipping s ON s.shipping_id = oi.shipping_id
							LEFT JOIN ecshop.ecs_payment ep ON ep.pay_id = oi.pay_id
							WHERE {$last_file_sql}{$order_time_sql}{$order_user_sql}
							{$order_file_sql}{$outer_order_sql}{$order_consignee_sql}{$order_tel_sql}{$order_status_sql}
							{$limit_sql}
							";
		$order_result = $db -> getAll($order_search_sql);
		if($order_result){
			$smarty->assign('order_result', $order_result);
		}else{
			throw new Exception("没有取到数据，可能真的没有数据了，可能查询条件有误<br>（注意【开始时间】默认5天前）");	
		}
		// var_dump($order_result);
		if(isset($last_file_sql)&&isset($Message)){
			if(!empty($last_file_sql)&&!empty($Message)){
				if($Message == 754050){
					$smarty->assign('order_alert_type','alert-success');	
					$smarty->assign('order_message','成功上传文件，以下为上传文件内订单信息（另附文件信息在【文件查询】一栏）：');
				}
				if($Message == 569566){
					$smarty->assign('order_alert_type','alert-success');	
					if($order_result[0]['fstaues'] == 'Y' || $order_result[0]['fstaues'] == 'S'){
						$smarty->assign('order_message',"成功查到文件（".$order_result[0]['action_user']."--".$order_result[0]['filename']."）内相关订单信息：
										<br><a href='batchDisOrderV2.php?Message=486156&FileId=".$last_file_id."'>点我导出文件内订单</a>");
					}else{
						$smarty->assign('order_message','成功查到文件（'.$order_result[0]['action_user'].'--'.$order_result[0]['filename'].'）内相关订单信息：');
					}
				}
				if($Message == 486156){
					$title = array(0=>array('ERP订单号','外部订单号','订单处理状态','备注'));
					$sheet = array();              
					foreach($order_result as $cell){
						$row = array();
						$row[] = $cell['erp_order_sn'];
						if(!empty($cell['taobao_order_sn'])){
							$row[] = $cell['taobao_order_sn'];
						}else{
							$row[] = '无';
				 		}
						if($cell['status'] == 'Y'){
							$row[] = '已完结';
						}elseif($cell['status'] == 'S'){
							$row[] = '出错';
						}					
						if(!empty($cell['note'])&&$cell['status'] == 'S'){
							$row[] = $cell['note'];
						}else{
							$row[] = '无';
						}
						$sheet[] = $row;
					}
					$file_name = '（'.$order_result[0]['action_user'].'--'.$order_result[0]['filename'].'）导出订单.xlsx';
					$type = array();
					for($i=0;$i<count($sheet[0]);$i++){
						$type[] = 'string';
					}
					excel_export_model($title,$file_name,$sheet,$type,'批量录单订单');
				}				
			}
		}
		$smarty->assign('tab_show',3);
		}catch(Exception $e){
		$smarty->assign('tab_show',3);
		$smarty->assign('order_alert_type','alert-error');
		if(isset($order_count)){//取不出数据为提醒样式、不是错误样式
			if($order_count == 0){
				$smarty->assign('order_alert_type','');
			}
		}
		$smarty->assign('order_message',$e -> getMessage());
		}
	}
}
/**
 * 显示
 */
//添加支付方式的选项  by  qxu   2013-6-26

//所有可用的支付方式
$smarty->assign('get_payments', $get_payments);

//根据业务组织来选择发货仓库
$smarty->assign('available_facility', $get_facility);

//省市区参考
$smarty->assign('province_list', get_regions(1, $GLOBALS['_CFG']['shop_country']));  // 省份列表
// 如果选择了订单省份，则持有城市数据
if ($order_before['province'] > 0) {
    $smarty->assign('city_list', get_regions(2, $order_before['province']));    
}
if ($order_before['city'] > 0 && !empty($order_before['district'])) {
    $smarty->assign('district_list', get_regions(3, $order_before['city']));
}

$smarty->assign ( 'party_id', $_SESSION ['party_id'] );

/*
	查询文件	
*/
$start_validity_time=isset($_REQUEST['start_validity_time'])
? $_REQUEST['start_validity_time']
: date("Y-m-d", time()-3600*24*5);//5天前

$end_validity_time=isset($_REQUEST['end_validity_time'])
? $_REQUEST['end_validity_time']
: date("Y-m-d", time()); //now

$action_user = isset($_REQUEST['action_user'])
? $_REQUEST['action_user']
: $_SESSION['admin_name'];//默认本人

$smarty->assign('start_validity_time',$start_validity_time);

$smarty->assign('end_validity_time',$end_validity_time);

$smarty->assign('file_name',$_REQUEST['file_name']);

$smarty->assign('action_user',$action_user);

$smarty->assign('file_status',$_REQUEST['file_status']);

/*
	订单查询
*/
$start_validity_time_=isset($_REQUEST['start_validity_time_'])
? $_REQUEST['start_validity_time_']
: date("Y-m-d", time()-3600*24*5);//5天前

$end_validity_time_=isset($_REQUEST['end_validity_time_'])
? $_REQUEST['end_validity_time_']
: date("Y-m-d", time()); //now

$action_user_ = isset($_REQUEST['action_user_'])
? $_REQUEST['action_user_']
: $_SESSION['admin_name'];//默认本人

$smarty->assign('file_name_',$_REQUEST['file_name_']);

$smarty->assign('action_user_',$action_user_);

$smarty->assign('taobao_order_sn',$_REQUEST['taobao_order_sn']);

$smarty->assign('tel',$_REQUEST['tel']);

$smarty->assign('consignee',$_REQUEST['consignee']);

$smarty->assign('start_validity_time_',$start_validity_time_);

$smarty->assign('end_validity_time_',$end_validity_time_);

$smarty->assign('order_status',$_REQUEST['order_status']);

$smarty->display ('distributor/batchDisOrderV2.htm' );	


// // /**
//  * 根据区域名称 返回区域
//  */
function get_region_by_name_type($region_name,$type)
{
    $sql = sprintf("SELECT region_id FROM %s WHERE region_name = '%s' and region_type='{$type}'" , 
        $GLOBALS['ecs']->table('region'),
        $GLOBALS['db']->escape_string(trim($region_name))   
    );
    // var_dump($sql);
    return $GLOBALS['db']->GetOne($sql);
}

/**
 * 去除单引号
 * */
function remove_single_quote($value){
	return str_replace("'", "", $value);
}

?>