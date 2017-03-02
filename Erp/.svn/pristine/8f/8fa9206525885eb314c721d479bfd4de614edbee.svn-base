<?php

/**
 * 乐其商品 批量导入功能
 * 
 * @author qxu@i9i8.com
 * @copyright 2013 leqee.com 
 */

define ( 'IN_ECS', true );
require_once ('includes/init.php');
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
isset ( $_REQUEST ['act'] ) && in_array ( $_REQUEST ['act'], array ('upload') ) ? $_REQUEST ['act'] : null;

// 当前时间 
$now = date ( 'Y-m-d H:i:s' );

//判断组织是否为具体的业务单位
if(!party_explicit($_SESSION['party_id'])) {
    sys_msg("请选择具体的组织后再来录入订单");
}


QLog::log ( "分销订单导入开始：{$act} " );

$get_payments = getPayments();
$get_pay_id = array_keys($get_payments);
$get_facility = get_available_facility($_SESSION['party_id']);
$get_facility_id = array_keys($get_facility);
$get_shipping = getShippingTypes();
$get_shipping_id = array_keys($get_shipping);
if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
	switch ($act) {
		case 'upload':
			//$pay_id = isset($_REQUEST['pay_id']) ? trim($_REQUEST['pay_id']):false ;
			//$facility_id = isset($_REQUEST['facility_id']) ? trim($_REQUEST['facility_id']):false ;
			
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
					  'shipping_fee'=>'快递费用',
					  'goods_amount'=>'商品金额',
					  'bonus'=>'订单红包',	
					  'order_amount'=>'订单金额',
					  //'goods_barcode'=>'商品条码',
					  //'style_barcode'=>'样式条码',
					  'price'=>'单价',		
					  'quantity'=>'数量',
					  //'tc_code'=>'套餐编码',
					  'shop_code'=>'商家编码',
					  'pay_id'=>'支付方式ID',
					  'facility_id'=>'发货仓库ID'
				
				));
                
			
			QLog::log ( '订单导入：' );
			/* 文件上传并读取 */
			$uploader = new Helper_Uploader ();
			$max_size = $uploader->allowedUploadSize (); // 允许上传的最大值
			
			if (! $uploader->existsFile ( 'excel' )) {
				$smarty->assign ( 'message', '没有选择上传文件，或者文件上传失败' );
				break;
			}
			
			// 取得要上传的文件句柄
			$file = $uploader->file ( 'excel' );
			
			// 检查上传文件
			if (! $file->isValid ( 'xls, xlsx', $max_size )) {
				$smarty->assign ( 'message', '非法的文件! 请检查文件类型类型(xls, xlsx), 并且系统限制的上传大小为' . $max_size / 1024 / 1024 . 'MB' );
				break;
			}
			
			// 读取excel
			$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed ,true);
			if (! empty ( $failed )) {
				$smarty->assign ( 'message', reset ( $failed ) );
				break;
			}
			
			/* 检查数据  */
			$rowset = $result ['分销订单导入'];

			// 订单数据读取失败
			if (empty ( $rowset )) {
				$smarty->assign ( 'message', 'excel文件中没有数据,请检查文件' );
				break;
			}
			// var_dump($rowset);

			//限制上传订单数不能超过500
			$exist = 'false';
			$taobao_order_sn=array();
			foreach ($rowset as $key => $value) {	
				$taobao_order_sn1[0] = $value['taobao_order_sn'];
				if($key === 0){
					array_push($taobao_order_sn,$value['taobao_order_sn']);
					continue;
				}
				foreach ($taobao_order_sn as $key => $value1) {
					if($value1 === $taobao_order_sn1[0]){
						$exist = 'true';
						break;
					}				
				}
				if($exist === 'false'){
					array_push($taobao_order_sn,$value['taobao_order_sn']);
				}		
			}			
			$count=count($taobao_order_sn);			
			if($count > 500){
				$smarty->assign ( 'message', '每次上传订单数不能超过500条,请将文件拆分后分别上传' );
				break;
			}
			
			
			//检测必填数据是否为空
			$order_check_array = array('temp_order_sn','distribution_id','outer_type','consignee','province','city','address',
						'shipping_type','shipping_fee','goods_amount','order_amount','price','bonus','quantity','shop_code','pay_id','facility_id');
			foreach ($order_check_array as $check_column) {
				$region_array = Helper_Array::getCols($rowset, $check_column);
				$region_size = count($region_array);
				Helper_Array::removeEmpty ($region_array);
				if($region_size > count($region_array)){
					$smarty->assign('message','【'.$tpl['分销订单导入'][$check_column].'】为必填数据，请检查该数据是否填写完整！');
					break 2;
				}
			}
			

			//验证淘宝订单号在ecs_order_info中是否已经存在，防止订单重复
			foreach (Helper_Array::getCols($rowset, 'taobao_order_sn') as $item_value) {
				$item_value = trim($item_value);
				if(!empty($item_value)) {
					$sql = "select 1 from ecshop.ecs_order_info where taobao_order_sn = '{$item_value}' ";
					$exists = $db->getOne($sql, true);
		            if ($exists) {
		                $smarty->assign('message',"该淘宝订单号已经存在：{$item_value}");
						break 2;
		            }
				}
			}
			
			foreach(Helper_Array::getCols($rowset, 'consignee') as $item_value) {
				if(preg_match('/[#&\'\"]+/',$item_value)) {
					$smarty->assign('message',"【收货人】中不能含有标点符号等非法字符：{$item_value}");
					break 2;
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'shipping_type') as $item_value) {
				if(!in_array($item_value,$get_shipping_id)){
					$smarty->assign('message',"系统中不存在该【配送方式ID】：{$item_value}");
					break 2;
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'facility_id') as $item_value) {
				if(!in_array($item_value,$get_facility_id)){
					$smarty->assign('message',"系统中不存在该【发货仓库ID】：{$item_value}");
					break 2;
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'pay_id') as $item_value) {
				if(!in_array($item_value,$get_pay_id)){
					$smarty->assign('message',"系统中不存在该【支付方式ID】：{$item_value}");
					break 2;
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'shipping_fee') as $item_value) {
				if($item_value < 0){
					$smarty->assign('message',"【快递费用】不能小于0：{$item_value}");
					break 2;
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'goods_amount') as $item_value) {
				if($item_value < 0){
					$smarty->assign('message',"【商品金额】不能小于0：{$item_value}");
					break 2;
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'order_amount') as $item_value) {
				if($item_value < 0){
					$smarty->assign('message',"【订单金额】不能小于0：{$item_value}");
					break 2;
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'price') as $item_value) {
				if($item_value < 0){
					$smarty->assign('message',"【单价】不能小于0：{$item_value}");
					break 2;
				}
			}
						
			foreach (Helper_Array::getCols($rowset, 'bonus') as $item_value) {
				if($item_value > 0){
					$smarty->assign('message',"【红包】不能大于0：{$item_value}");
					break 2;
				}
			}
			
			foreach (Helper_Array::getCols($rowset, 'quantity') as $item_value) {
				if(!(preg_match ('/^[1-9]([0-9]+)?$/', $item_value) && $item_value > 0)){
					$smarty->assign('message',"【商品数量】必须为正整数：{$item_value}");
					break 2;
				}
			}
			

			//验证分销商是不是都存在
			foreach (array_unique(Helper_Array::getCols($rowset, 'distribution_id')) as $item_value) {
				$sql = "select count(*) from ecshop.distributor where name = '{$item_value}' and party_id = '{$_SESSION['party_id']}'";
				if(!$db->getOne($sql)){
					$smarty->assign('message',"【分销商名称】输入错误，请检查是否有此分销商：{$item_value}");
					break 2;
				}
			}
			
			//验证外部订单类型是不是都存在
			foreach (array_unique(Helper_Array::getCols($rowset, 'outer_type')) as $item_value) {
				if(!in_array($item_value,$_CFG['adminvars']['outer_type']) && $item_value != '无'){
					$smarty->assign('message',"【外部订单类型】输入错误：{$item_value}");
					break 2;
				}
			}
			
			//验证商家编码是否已填
			foreach (array_unique(Helper_Array::getCols($rowset, 'shop_code')) as $item_value) {
				if(!(preg_match('/^TC-[0-9]+$/',$item_value) || preg_match('/^[0-9]+_[0-9]+$/',$item_value) || preg_match('/^[0-9]+$/',$item_value))) {
					$smarty->assign('message',"【商家编码】输入错误，请查询后再导入：{$item_value}");
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
					$smarty->assign('message',"系统中找不到该导入文件中所有的该【商家编码】（套餐编码），请检查(确认是否切对组织)");
					break;
				}				
				if(!empty($diff_tc_codes)){
					$smarty->assign('message',"系统中找不到该【商家编码】（套餐编码），请检查(确认是否切对组织)：".implode(',',$diff_tc_codes));
					break;
				}
			}
			
			$telephone = array();
			$mobile = array();
			foreach($rowset as $row) {
				if(empty($row['telephone']) && empty($row['mobile'])) {
					$smarty->assign('message',"【联系电话】和【手机】必须填一个");
					break 2;
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
				$smarty->assign('message',"以下订单的快递费+商品金额-订单红包与订单费用不相同，请检查：".$keys);
				break;
			}
			if($keys_goods){
				$smarty->assign('message',"以下订单的商品金额之和与订单商品金额不相同，请检查：".$keys_goods);
				break;
			}
			if($tc_price_error){
				$smarty->assign('message',"以下订单的erp套餐金额和单价不相同，请检查：".$tc_price_error);
				break;
			}
			//$order_check_array = array('order_sn','distribution_id','consignee','province','city',
			//			'shipping_type','shipping_fee','goods_amount','order_amount','price','quantity');
			 
			 
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
				$order['city'] = get_region_by_name_type($order_attr[0]['city'],2);
				$order['district'] = get_region_by_name_type($order_attr[0]['district'],3);
				$order['address'] = $order_attr[0]['address'];
				$order['shipping_id'] = $order_attr[0]['shipping_type'];
				$order['shipping_fee'] = $order_attr[0]['shipping_fee'];
				$order['bonus'] = $order_attr[0]['bonus'];
				$order['goods_amount'] = $order_attr[0]['goods_amount'];
				$order['order_amount'] = $order_attr[0]['order_amount'];
				$order['pay_id'] = $order_attr[0]['pay_id'];
				$order['facility_id'] = $order_attr[0]['facility_id'];
				$order['shop_code'] = $order_attr[0]['shop_code'];
				$order['nick_name'] = $order_attr[0]['taobao_id'];
								
//				var_dump('$order_attr');var_dump($order_attr);
								
				$order_goods = array();
				foreach ($order_attr as $order_goods_attr) {
					// 普通商品
					if(preg_match('/^[0-9]+_[0-9]+$/',$order_goods_attr['shop_code']) || preg_match('/^[0-9]+$/',$order_goods_attr['shop_code'])) {
						$order_goods_item = array();
						if(preg_match('/^[0-9]+_[0-9]+$/',$order_goods_attr['shop_code']) && !preg_match('/^[0-9]+_0$/',$order_goods_attr['shop_code'])){
							$goods_style_id = explode("_",$order_goods_attr['shop_code']);
							$order_goods_item['goods_id'] = $goods_style_id[0];
							$order_goods_item['style_id'] = $goods_style_id[1];
							$sql = "select count(*) from ecshop.ecs_goods_style where goods_id = '{$order_goods_item['goods_id']}' and style_id = '{$order_goods_item['style_id']}' and is_delete=0";
						}else if(preg_match('/^[0-9]+$/',$order_goods_attr['shop_code']) || preg_match('/^[0-9]+_0$/',$order_goods_attr['shop_code'])){
							$order_goods_item['goods_id'] = str_replace('_0','',$order_goods_attr['shop_code']);
							$order_goods_item['style_id'] = 0;
							$sql = "select count(*) from ecshop.ecs_goods where goods_id = '{$order_goods_item['goods_id']}'";
						}
						$count = $db->getROw($sql);
						if($count['count(*)'] == 0) {
							$smarty->assign('message',"系统异常，下面商家编码找不到对应商品，请检查后重新导入：".$order_goods_attr['shop_code']);
							break 3;
						} else {
						$order_goods_item['price'] = $order_goods_attr['price'];
						$order_goods_item['goods_number'] = $order_goods_attr['quantity'];
						$order_goods[] = $order_goods_item;
						}
					} 
					// 套餐
					else if(preg_match('/^TC-[0-9]+$/',$order_goods_attr['shop_code'])) {
						if(isset($group_order_goods[$order_goods_attr['shop_code']]) && !empty($group_order_goods[$order_goods_attr['shop_code']])) {
							$group_order_goods_temps = $group_order_goods[$order_goods_attr['shop_code']];
							$tc_number = $order_goods_attr['quantity'];							
							$order_goods_temps = get_tc_order_goods($group_order_goods_temps,$tc_number);			
							$order_goods = array_merge($order_goods,$order_goods_temps);
						} 
						else 
						{
						   $smarty->assign('message',"系统异常，下面商家编码找不到对应商品：".$order_goods_attr['shop_code']);
				           break 3;
						}
					}
				}
				$return_result = create_distribution_order($order,$order_goods);
				if($return_result['osn']){
					$return_message .= $key."对应订单号：".$return_result['osn'].";";
					if($return_result['message']){
						$return_message .= "但{$return_result['message']};";
					}
				}else{
					$return_message .= $key."创建失败，原因为：".$return_result['message'].";";
				}
			}
			
		$file->unlink ();
		$smarty->assign ('message', "导入完毕！<br/>".$return_message );
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
$smarty->display ('distributor/batchDisOrder.htm' );


/**
 * 根据区域名称 返回区域
 */
function get_region_by_name_type($region_name,$type)
{
    $sql = sprintf("SELECT region_id FROM %s WHERE region_name = '%s' and region_type='{$type}'" , 
        $GLOBALS['ecs']->table('region'),
        $GLOBALS['db']->escape_string(trim($region_name))   
    );
    //var_dump($sql);
    return $GLOBALS['db']->GetOne($sql);
}

function create_distribution_order($order,$order_goods){
	global  $db, $ecs;
	do{
		// 取得分销商信息
		$distributor = $db->getRow("SELECT * FROM distributor WHERE distributor_id = {$order['distributor_id']}");

		if ($distributor['party_id'] != $_SESSION['party_id']) {
			$message = '您选择的分销商的业务形态与操作人员的业务形态不一致, 请选择所属组织';
			break;
		}

		// 取得分销类型，
		$distribution_type = $db->getOne("SELECT type FROM main_distributor WHERE main_distributor_id = '{$distributor['main_distributor_id']}' LIMIT 1");

		// 检查淘宝订单号是否存在了
		if (!empty($order['taobao_order_sn'])) {
			require_once("ajax.php");
			
			$result = ajax_search_order_sn_by_taobao($order);
			
			$message = $result['message'];
			if(!$result['isSubmit']){
				break;
			}
			// 电教的分销业务需要扣预付款，预付款需要根据原始导入记录扣除，所以不能录入
			// 欧酷没有预付款，允许录入
			else if ($distribution_type=='fenxiao' && ($distributor['party_id']==16 || $distributor['party_id']==65548)&& $distributor['main_distributor_id']!=25) {
				$message = '请使用导入订单的方式生成订单，否则将不能抵扣分销商的预付款';
				break;
			}
		}
		
		//检查该订单的地址是否正确 yjchen
		if(empty($order['province'])) {
			$message = '省填写错误，请参考【省市区参考】';
			break;
		}
		if(empty($order['city'])) {
			$message = '市填写错误，请参考【省市区参考】';
			break;
		}
		

		/* 取得配送方式和承运方式 */
		$shipping_id = $order['shipping_id'];
		$shipping = $db->getRow("select support_no_cod,support_cod,default_carrier_id from ecshop.ecs_shipping where shipping_id = '{$shipping_id}'");
		if($shipping['support_no_cod'] == '1' and $shipping['support_cod'] == '0'){
			$order_type = 'NON-COD';
		}elseif ($shipping['support_no_cod'] == '0' and $shipping['support_cod'] == '1'){
			$order_type = 'COD';
		}else{
			$order_type = '';
		}
		$pay_id = $order['pay_id'];
		if($shipping_id == '36' && $distributor['party_id'] == PARTY_LEQEE_EDU){
			$message = '电教品不能选择EMS货到付款';
			break;
		}else{
			$sql = "select default_carrier_id from ecshop.ecs_shipping where shipping_id = {$shipping_id} limit 1";
			$carrier_id = $db->getOne($sql);
		}
		
		// 违禁品不能发的快递
		$contraband_shipping = get_contraband_shipping();
		if(in_array($shipping_id,$contraband_shipping)) {
			if( check_contraband($order_goods) ) {
				$message = '该订单含有违禁品，请修改快递方式';
				break;
			}
		}

		$order['shipping_proxy_fee'] = 0;            // 手续费都为0
		$order['pack_fee'] = 0;                      // 包装费为0
		$order['user_id']  = 1;                      // 指定用户
		$order['party_id'] = $_SESSION['party_id'];  // 订单类型 （乐其分手机业务和电教业务）
		$order['order_status'] = 1;                  // 默认为确认状态
		$order['facility_id'] = $order['facility_id']; // 仓库id
		$order['currency'] = $_POST['currency'];        // 货币
		$taobao_user_id = $order['nick_name']; 
		$outer_type = $order['outer_type'];
		//var_dump($order);
		$osn = distribution_generate_sale_order($order, $order_goods, $carrier_id, $order_type, $message);
		if(empty($osn['error'])) {
        	$order_sn = $osn['order_sn'];
        	$order_id = getOrderIdBySN($order_sn);
        } else {
        	$order_sn = false;
        }

		if ($order_sn !== false) {

			  // 在 order_attribute 中标记该订单为手工录单 
            $db->query("insert into ecshop.order_attribute(order_id, attr_name, attr_value) values ({$order_id}, 'ORDER_BY_HAND', '1')");
				
			if ($taobao_user_id) {
				//插入淘宝订单用户id
				$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order_id}, 'TAOBAO_USER_ID', '{$taobao_user_id}')");
			}
			if($outer_type) {
				//插入外部订单类型
				$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order_id}, 'OUTER_TYPE', '{$outer_type}')");			
			}
		}
	}while (false);
	return array("message"=>$message,"osn"=>$order_sn);
}

function check_contraband($order_goods){
	global $db;
	if (count($order_goods) > 0){
		foreach ($order_goods as $item) {
		 	$sql = "select is_contraband from ecshop.ecs_goods where goods_id = {$item['goods_id']}";
		 	$is_contraband = $db->getRow($sql);
		 	if($is_contraband['is_contraband']){
		 		return true;
		 	}
		 }
     }
}

/**
 * 去除单引号
 * */
function remove_single_quote($value){
	return str_replace("'", "", $value);
}
