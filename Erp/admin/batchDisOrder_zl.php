<?php

/**
 * 中粮商品 批量导入功能
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
if($_SESSION['party_id'] != '65625') {
    sys_msg("请选择中粮组织后再来录入订单，其他组织请到【批量录单】页面录单。");
}


QLog::log ( "分销订单导入开始：{$act} " );
if ($_SERVER ['REQUEST_METHOD'] == 'POST' && $act) {
	switch ($act) {
		case 'upload':
			$pay_id = isset($_REQUEST['pay_id']) ? trim($_REQUEST['pay_id']):false ;
			$shipping_id = isset($_REQUEST['shipping_id']) ? trim($_REQUEST['shipping_id']):false ;
			// excel读取设置
			$tpl = array('中粮订单导入'  =>
				array('temp_order_sn'=>'临时订单号',
					  'distribution_id'=>'分销商名称',
					  'consignee'=>'收货人',
					  'taobao_order_sn'=>'外部订单号',
					  'telephone'=>'联系电话',
					  'mobile'=>'手机',
					  'province'=>'省',
					  'city'=>'市',
					  'district'=>'区',
					  'address'=>'详细地址',
					  //'shipping_type'=>'配送方式ID',
					  'shipping_fee'=>'快递费用',
					  'goods_amount'=>'商品金额',
					  'bonus'=>'订单红包',	
					  'order_amount'=>'订单金额',
					  'goods_barcode'=>'商品条码',
					  //'style_barcode'=>'样式条码',
					  'price'=>'单价',		
					  'quantity'=>'数量',
					  //'tc_code'=>'套餐编码',
					  //'shop_code'=>'商家编码',
					  //'pay_id'=>'支付方式ID',
					  'facility_name'=>'发货仓库'
				
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
			$result = excel_read ( $file->filepath (), $tpl, $file->extname (), $failed );
			if (! empty ( $failed )) {
				$smarty->assign ( 'message', reset ( $failed ) );
				break;
			}
			
			/* 检查数据  */
			$rowset = $result ['中粮订单导入'];

			// 订单数据读取失败
			if (empty ( $rowset )) {
				$smarty->assign ( 'message', 'excel文件中没有数据,请检查文件' );
				break;
			}
			

			$order_check_array = array('temp_order_sn','distribution_id','consignee','province','city','address',
					            'shipping_fee','goods_amount','order_amount','price','bonus','quantity','facility_name','goods_barcode');
			foreach ($order_check_array as $check_column) {
				$region_array = Helper_Array::getCols($rowset, $check_column);
				$region_size = count($region_array);
				Helper_Array::removeEmpty ($region_array);
				if($region_size > count($region_array)){
					$smarty->assign('message','【'.$tpl['中粮订单导入'][$check_column].'】为必填数据，请检查该数据是否填写完整！');
					break 2;
				}
			}
			
			foreach(Helper_Array::getCols($rowset, 'consignee') as $item_value) {
				if(preg_match('/[#&\'\"]+/',$item_value)) {
					$smarty->assign('message',"【收货人】中不能含有标点符号等非法字符：{$item_value}");
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
			
			foreach(Helper_Array::getCols($rowset, 'facility_name') as $item_value) {
				if(!(getFacilityIdbyName($item_value))){
					$smarty->assign('message',"该【发货仓库】不存在：{$item_value}");
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
			


			//验证订单商品BARCODE和商品样式的BARCODE是不是都存在并且匹配
			$goods_barcodes = array();
			$SKU_barcodes = array();
			$goods_sku = array();
			
			
			foreach ($rowset as $row) {
				$sql = "
						select count(*)
						from ecshop.ecs_goods_style egs 
						inner join ecshop.ecs_goods eg on eg.goods_id = egs.goods_id
						inner join ecshop.ecs_style es on es.style_id = egs.style_id
						where egs.barcode = '{$row['style_barcode']}' and egs.is_delete=0
						and eg.goods_party_id = {$_SESSION['party_id']}
					    ";
				$result_num = $db->getOne($sql);
					    
				if($result_num = "0") {
					$sql = "select count(*) 
							from ecshop.ecs_goods eg
							left join ecshop.ecs_goods_style egs on eg.goods_id = egs.goods_id and egs.is_delete=0
							where eg.goods_party_id = {$_SESSION['party_id']}
							and egs.barcode is null 
							and eg.barcode = '{$row['goods_barcode']}'";
					$result_num = $db->getOne($sql);
					
					if($result_num != "1") {
						array_push($goods_barcodes,$row['goods_barcode']);
					}
							
				} else {
					
				}	
			}
			if(count($goods_barcodes) > 0){
				$message = "根据以下商品条码找不到商品：";
				foreach($goods_barcodes as $good_barcode){
					$message .= $good_barcode.",";
				}
				$smarty->assign('message',$message);
				break;
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
//			var_dump('$order_items');var_dump($order_items);die();
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
					
					// 判断套餐金和单价
					
					
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
			
			//$order_check_array = array('order_sn','distribution_id','consignee','province','city',
			//			'shipping_type','shipping_fee','goods_amount','order_amount','price','quantity');
			 
			 
			foreach ($order_items as $key=>$order_attr) {
				//批量去除单引号
				foreach($order_attr[0] as $k => $v){
					$order_attr[0][$k] =remove_single_quote($v);
				}
				$order = array();
				$order['distributor_id'] = $db->getOne("select distributor_id from ecshop.distributor where name = '{$order_attr[0]['distribution_id']}' and party_id = '{$_SESSION['party_id']}'");
				$order['consignee'] = $order_attr[0]['consignee'];
				$order['taobao_order_sn'] = trim($order_attr[0]['taobao_order_sn']);
				$order['outer_type'] = $order_attr[0]['outer_type'];
				$order['mobile'] = $order_attr[0]['mobile'];
				$order['tel'] = $order_attr[0]['telephone'];
				$order['province'] = get_region_by_name_type($order_attr[0]['province'],1);
				$order['city'] = get_region_by_name_type($order_attr[0]['city'],2);
				$order['district'] = get_region_by_name_type($order_attr[0]['district'],3);
				$order['address'] = $order_attr[0]['address'];
				$order['shipping_id'] = $shipping_id;
				$order['shipping_fee'] = $order_attr[0]['shipping_fee'];
				$order['bonus'] = $order_attr[0]['bonus'];
				$order['goods_amount'] = $order_attr[0]['goods_amount'];
				$order['order_amount'] = $order_attr[0]['order_amount'];
				$order['pay_id'] = $pay_id;
				$order['facility_id'] = getFacilityIdbyName($order_attr[0]['facility_name']);
				//$order['shop_code'] = $order_attr[0]['shop_code'];

								
				//var_dump($order_attr);var_dump($order['facility_id']);
				
								
				$order_goods = array();
				foreach ($order_attr as $order_goods_attr) {
					// 普通商品
					if($order_goods_attr['goods_barcode']) {
						$order_goods_item = array();
						$sql = "
								select egs.goods_id,egs.style_id 
								from ecshop.ecs_goods_style egs
								inner join ecshop.ecs_goods eg on eg.goods_id = egs.goods_id
								where egs.barcode = '{$order_goods_attr['goods_barcode']}'  and egs.is_delete=0
								and eg.goods_party_id = '{$_SESSION['party_id']}'
							";
						$goods_style_id = $db->getROw($sql);
						if(!$goods_style_id) {
							$sql = "
								select goods_id,0 style_id from ecshop.ecs_goods
								where barcode = '{$order_goods_attr['goods_barcode']}' 
								and goods_party_id = '{$_SESSION['party_id']}'
							";
							$goods_style_id = $db->getROw($sql);
						}
						
						$order_goods_item['goods_id'] = $goods_style_id['goods_id'];
						$order_goods_item['style_id'] = $goods_style_id['style_id'];
						$order_goods_item['price'] = $order_goods_attr['price'];
						$order_goods_item['goods_number'] = $order_goods_attr['quantity'];
						$order_goods[] = $order_goods_item;
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
$get_payments = getPayments();
//所有可用的支付方式
//$smarty->assign('get_payments', $get_payments);

//根据业务组织来选择发货仓库
//$smarty->assign('available_facility', get_available_facility($_SESSION['party_id']));

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
$smarty->display ('distributor/batchDisOrder_zl.htm' );


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
		$trimed_tbsn=trim($order['taobao_order_sn']);
		if (!empty($trimed_tbsn)) {
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
		//var_dump($order);
		$osn = distribution_generate_sale_order($order, $order_goods, $carrier_id, $order_type, $message);
		if(empty($osn['error'])) {
        	$order_sn = $osn['order_sn'];
        	$order_id = getOrderIdBySN($order_sn);
        } else {
        	$order_sn = false;
        }
        

		if ($order_sn !== false) {
				
			if ($taobao_user_id) {
				//插入淘宝订单用户id
				$db->query("insert into ecshop.order_attribute (order_id, attr_name, attr_value) values ({$order_id}, 'TAOBAO_USER_ID', '{$taobao_user_id}')");
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

function getFacilityIdbyName ($facility_name) {
	global $db;
	$sql = "select facility_id from romeo.facility where facility_name='{$facility_name}'";
	$facility_id = $db -> getOne($sql);
	if($facility_id != null) {
		return $db -> getOne($sql);
	} else {
		//$message = '发货仓填写错误，请检查后再提交'.$facility_name;
		return false;
	}
}
/**
 * 去除单引号
 * */
function remove_single_quote($value){
	return str_replace("'", "", $value);
}
