<?php

define('IN_ECS', true);
require_once('../includes/init.php');

set_include_path("../includes/Classes/");
require_once("PHPExcel.php");
require_once("PHPExcel/IOFactory.php");
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['act'])){
    if($_REQUEST['act'] == 'upload_data'){
        $application_key = $_POST['shop_name'];
        $facility_id = $_POST['facility_name'];
        $tmp_name = $_FILES['upload_excel']['tmp_name'];
        $tpl = array('tid',
            'amount',
            'post_fee',
            'goods_amount',
            'payment',
            'order_time',
            'trade_trans_no',
            'pay_time',
            'payment_code',
            'shipping_id',
            'shipping_name',
            'mibun_number',
            'name',
            'email',
            'phone',
            'account',
            'consignee',
            'province',
            'city',
            'district',
            'address',
            'receiver_phone'
            );

        $excel_goods_detail_data = array(
            'product_id',
            'quantity',
            'outer_id',
            'amount'
            );

//        $bwshop = new BWSHOP_API_AGENT($client_id,$client_key);

        /*
        *   以下部分实现的功能是读取订单信息中除了goods信息之外的
        *
        */
        $objReader = PHPExcel_IOFactory::createReader('Excel2007');
        $objPHPExcel = $objReader->load($tmp_name);
        $sheet = $objPHPExcel->getSheet(0);
        $Row = $sheet->getHighestRow();
        $Column = $sheet->getHighestColumn();
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($Column);//总列数

        $excel_first_data = array();         //存储前面的几列
        $combine_first_data =array();        
        $finally_first_data = array();     
        $combine_goods_zanshi_data = array();   //暂时存放goods里面的信息
        $combine_goods_data = array();          //  最后存放商品里面的信息
        $good_in_array = array();
        $good_array = array();                  //excel中读出数据的存放点
        $order_sn_array = array();              //存放order_sn的数组
        $order_sn_goods_array = array();
        $output_data = array();
        try{
            header("content-type:text/html; charset=utf-8");
            echo "<h2>上傳訂單結果</h2>";
            if($Row>=2 && $sheet->getCellByColumnAndRow(0, 2)->getValue() != ''){
                for($m=2;$m<=$Row+1;$m++){
                    $order_sn=$sheet->getCellByColumnAndRow(0, $m)->getValue();
                    if($order_sn == ""){
                        if(is_array($combine_goods_data)&& is_array($order_sn_goods_array)){
                            foreach ($order_sn_goods_array as $key => $value) {
                                foreach ($combine_goods_data as $name => $zhi){
                                    if($key == $name){
                                        foreach ($value as $value_key => $value_name) {
                                            $value_name['goods'] = $zhi;
//                                            $value_name = json_encode($value_name);
//                                            var_dump($value_name);
                                            $res = createOrders($value_name,$application_key,$facility_id);
//                                            var_dump($res);
//                                            $resul = json_decode($res);
                                            if($res['error'] == 0){
                                                echo "<h3>数据上传成功,如需继续上传,请点击下面的按钮</h3>";
                                                echo "<h4><a href='index.php' style='text-decoration: none;'>继续导单</a></h4>";
                                                echo "<p>haiguan_order_id:".$res['order_id']."</p>";
                                            }else{
                                                echo "<h3>数据上传失败,请点击按钮返回<h3>";
                                                echo "<h4><a href='javascript :;' onClick='javascript :history.back(-1);' style='text-decoration: none;'>返回上一页</a></h4><br>";
//                                                $resul = json_encode($resul);
                                                echo "错误代码：";print_r($res['message']);
                                                echo "<br><br>未上传成功的订单号：".$name;
                                            }
                                        }
                                    }
                                }   
                            }
                        }else{
                            $output = "这不是一个数组";
                            echo "<script>console.log('".$output."')</script>";
                        }
                        exit();
                    }
                    if (in_array($order_sn,$order_sn_array)){
                        for($n=22;$n<26;$n++){
                            $name = $sheet->getCellByColumnAndRow($n, $m)->getValue();                                   
                            $good_array[$order_sn][$n]=$sheet->getCellByColumnAndRow($n, $m)->getValue();                                   
                        }
                        $combine_goods_zanshi_data= array_combine($excel_goods_detail_data, $good_array[$order_sn]);
                        $combine_goods_data[$order_sn][] = $combine_goods_zanshi_data;
                    }else{
                        $order_sn_array[] = $order_sn;
                        for($i=0;$i<22;$i++){
                            $excel_first_data[$i]=$sheet->getCellByColumnAndRow($i, $m)->getValue();
                        }
                        for($n=22;$n<26;$n++){
                            $good_array[$order_sn][$n]=$sheet->getCellByColumnAndRow($n, $m)->getValue();
                        }

                        /*
                        *   这里是将前面的商品信息进行整合
                        */
                        $combine_first_data = array_combine($tpl, $excel_first_data);
                        $order_sn_goods_array[$order_sn][]=$combine_first_data;

                        /*
                        *   这里是将一个对应多个商品进行整合
                        */
                        $combine_goods_zanshi_data= array_combine($excel_goods_detail_data, $good_array[$order_sn]);
                        $combine_goods_data[$order_sn][] = $combine_goods_zanshi_data;
                    }

                        // @$combine_data = array_combine($tpl, $excel_data);         //加一个@符号是为了防止错误显示在客户端
                }
            }else{
                echo "excel文件中未输入任何数据";
                exit;
            }
        }catch(Exception $e){
            print $e->getMessage();
            exit;
        }
    }
}


function createOrders($params,$application_key,$facility_id) {
	
		global $deploy_level; 
		global $db;
		
		$sql_platform = "select platform from ecshop.haiguan_api_params where application_key = '{$application_key}'";
		$platform_code = $db -> getOne($sql_platform);
//		$platform_code = str_pad($platform_code,4,"0",STR_PAD_LEFT);
		$platform_code = sprintf("%04d",trim($platform_code));
		if($platform_code == '' || $platform_code == null) {
			$result['error'] = 1;
			$result['message'] = '请维护相关店铺后再提交！';
			return $result;
		} else {
			$params['platform_code'] = $platform_code;
		}

		$db->start_transaction();

		try {

			$mapping=array(
				'tid'=>('/^[0-9a-zA-Z-]+$/'),
				'amount'=>'/^[0-9]+(\.[0-9]+)?$/',
				'post_fee'=>'/^[0-9]+(\.[0-9]+)?$/',
				'goods_amount'=>'/^[0-9]+(\.[0-9]+)?$/',
				'payment'=>'/^[0-9]+(\.[0-9]+)?$/',
				'order_time'=>'/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',

				//支付流水号 支付方式			
				'trade_trans_no'=>'/^(()|(.+))$/',
				'pay_time'=>'/^(()|(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}))$/',
				'payment_code'=>'/^(()|(\d\d))$/',
				
				//配送方式
				 'shipping_id'=>'/^[0-9]+$/',
				 'shipping_name'=>'/^.+$/',
				 
				//买家留言 和 订单备注  希望留空  或者按照身份证 空格 姓名的格式填写
				// 'remark'=>'/^.*$/',
				// 'title'=>'/^.*$/',

				//身份信息 				
				'mibun_number'=>'/^(()|([0-9]{17}[0-9X]))$/',
				'name'=>'/^(()|(.+))$/',
				'email'=>'/^(()|([^@]+@[^@]+\.[^@]+))$/',
				'phone'=>'/^(()|(1[0-9]{10}))$/',
				'account'=>'/^(()|(.+))$/',

				'consignee'=>'/^.+$/',
				'province'=>'/^.+$/',
				'city'=>'/^.+$/',
				'district'=>'/^.+$/',
				'address'=>'/^.+$/',
				'receiver_phone'=>'/^[0-9\ \-\+\#]+$/',
			);

			foreach ($mapping as $field => $format_regex) {
				if(!preg_match($format_regex,$params[$field]) && $params[$field] != '') {
					$result['error'] = 1;
					$result['message'] = $params[$field] . ' 格式有误，请检查后再提交！';
					return $result;
				}
			}
			
			if(empty($facility_id) || empty($facility_id)) {
				$result['error'] = 1;
				$result['message'] = '仓库为必选字段，请检查后再提交！';
				return $result;
			}
			if(!checkFacility($facility_id)){
				$result['error'] = 1;
				$result['message'] = '仓库ID有误，请检查后再提交！';
				return $result;
			}

			$params['facility_id'] = $facility_id;
			$params['tid']=trim($params['tid']);

			$params['remark']='';
			$params['title']='';


			if(empty($params['shipping_id']) || empty($params['shipping_name'])) {
				$result['error'] = 1;
				$result['message'] = '配送方式ID和配送方式名称为必填字段，请检查后再提交！';
				return $result;
			} 
			if(!checkShipping($params['shipping_id'],$params['shipping_name'])){
				$result['error'] = 1;
				$result['message'] = '配送方式ID和配送方式名称有误，请检查后再提交！';
				return $result;
			}

			$params['transfer_status']='NORMAL';
			$params['transfer_note']='';


			if(empty($params['payment_code']) || empty($params['trade_trans_no']) || empty($params['pay_time'])){
				$result['error'] = 1;
				$result['message'] = '支付code，交易流水号和支付时间为必填字段，请检查后再提交！';
				return $result;
			}
			
			if(empty($params['account']) || empty($params['phone'])){
				$result['error'] = 1;
				$result['message'] = '昵称和电话号码为必填字段，请检查后再提交！';
				return $result;
			}
			
//			var_dump(checkTid($params['tid']));
			if(checkTid($params['tid'])) {
				$order_id = insertHaiguanOrder($params,$application_key);
			} else {
				$result['error'] = 1;
				$result['message'] = '订单号重复，请复查！';
				return $result;
			}
			
			$province_id = get_region_by_name_type($params['province'],1);
			$city_id = get_region_by_name_type($params['city'],2);
			if(empty($province_id)) {
				$result['error'] = 1;
				$result['message'] = $params['province'] . ' 省填写错误！';
				$db->rollback();
				return $result;
			}
			if(empty($city_id)) {
				$result['error'] = 1;
				$result['message'] = $params['city'] . ' 市填写错误！';
				$db->rollback();
				return $result;
			}


			$goods = $params['goods'];
			
			$should_pay=0;

			if($goods && is_array($goods)){
				$goods_lol=array();
				$goods_tc=array();
				foreach ($goods as $goods_record) {
					$product_id=$goods_record['product_id'];// ERP [goods_id]
					$outer_id=$goods_record['outer_id'];// ERP [goods_id](_[style_id])
					$quantity=$goods_record['quantity'];
					$amount=$goods_record['amount'];
					
					if(!preg_match('/^\d+$/',$product_id) || !preg_match('/^\d+(_\d+)?$/',$outer_id) 
						|| !preg_match('/^\d+$/',$quantity) || !preg_match('/^\d+(\.\d+)?$/',$amount)) {
						$result['error'] = 1;
						$result['message'] = '商品信息格式填写有误，请检查！';
						$db->rollback();
						return $result;
					}

					if(strpos($product_id, 'TC-')===0) {
						$result['error'] = 1;
						$result['message'] = '商品不能是套餐，请检查！';
						$db->rollback();
						return $result;
					}

					$goods_lines = goods_check($product_id,$outer_id);
					if(empty($goods_lines)){
						$result['error'] = 1;
						$result['message'] = '商品信息填写有误，请检查！';
						$db->rollback();
						return $result;
					}

					$goods_lol[$outer_id]=$goods_lines[0];

				}
				foreach ($goods as $goods_record) {
					$product_id=$goods_record['product_id'];
					$outer_id=$goods_record['outer_id'];
					$quantity=$goods_record['quantity'];
					$amount=$goods_record['amount'];

					$goods_line=$goods_lol[$outer_id];
					
					$sql_haiguan_goods = "select * from ecshop.haiguan_goods where outer_id = '{$outer_id}' and party_id = '{$_SESSION['party_id']}'";
					$haiguan_goods = $db -> getRow($sql_haiguan_goods);
					if(empty($haiguan_goods)){
						$result['error'] = 1;
						$result['message'] = '该商家编码未维护,请检查:'.$outer_id;
						$db->rollback();
						return $result;
					} 
//					var_dump($haiguan_goods);
					$rate = $haiguan_goods['rate'];
					$vat_rate = $haiguan_goods['vat_rate'];
					if($rate!=null && strpos($rate, '%') != null){
						$rate=str_replace("%", "",$rate);
						$rate = "0.".$rate;
					}	
					if($vat_rate!=null && strpos($vat_rate, '%') != null){
						$vat_rate=str_replace("%", "",$vat_rate);
						$vat_rate = "0.".$vat_rate;
					}
			
			
					$consumption_duty_amount = number_format(($amount/$params['amount']*$params['post_fee']+$amount)/(1-$rate)*$rate*0.7,3);
					$added_value_tax_amount = number_format(($amount/$params['amount']*$params['post_fee']+$amount )*$vat_rate*0.7+$consumption_duty_amount*$vat_rate,3);

					$goods_param=array();
					$goods_param['haiguan_order_id']=$order_id;
					$goods_param['product_id']=$product_id;//$goods_line['barcode'];
					$goods_param['outer_id']=$outer_id;//$goods_line['goods_id'];
					$goods_param['goods_name']=$goods_line['goods_name'];
					$goods_param['quantity']=$quantity;
					$goods_param['amount']=$amount;
					$goods_param['added_value_tax_amount']=$added_value_tax_amount;
					$goods_param['consumption_duty_amount']=$consumption_duty_amount;
					$goods_param['created_stamp']=date('Y-m-d G:i:s');
					$goods_param['last_updateds_stamp']=date('Y-m-d G:i:s');

					$should_pay+=$goods_param['amount'];

					$order_goods_id= $db -> insert('ecshop.haiguan_order_goods', $goods_param);
					if($order_goods_id===false){
						$result['error'] = 1;
						$result['message'] = '订单商品中间信息插入失败，请重试！';
						$db->rollback();
						return $result;
					}
				}
			}else{
				$result['error'] = 1;
				$result['message'] = '订单商品有误，请检查！';
				$db->rollback();
				return $result;
			}

			$epsilon = 0.00001;

			if(abs($should_pay-(1.0*$params['amount']))>$epsilon){
				$result['error'] = 1;
				$result['message'] = "订单商品有误，请检查！"." should_pay[".$should_pay."] != params.amount[".$params['amount']."]";
				$db->rollback();
				return $result;
			}
			$should_pay+=$params['post_fee'];
			if(abs($should_pay-$params['goods_amount'])>$epsilon){
				$result['error'] = 1;
				$result['message'] = "订单商品有误，请检查！"." should_pay[".$should_pay."] != params.goods_amount[".$params['amount']."]";
				$db->rollback();
				return $result;
			}
			if($params['goods_amount']<$params['payment']){
				$result['error'] = 1;
				$result['message'] = "订单商品有误，请检查！"." params.goods_amount[".$params['goods_amount']."] != params.payment[".$params['payment']."]";
				$db->rollback();
				return $result;
			}


			$db->commit();
			return array('order_id'=>$order_id,'error'=>0,'message'=>'');
		} catch (Exception $e) {
			$result['error'] = 1;
			$result['message'] = "Receive Order exception: " . $e->getMessage()." stack: ".$e->getTraceAsString()." sql error: ".json_encode(Flight::db()->errorInfo());
			$db->rollback();
			return $result;
		}
	
}

function checkTid($tid) {
	global $db;
	$sql = "select count(*) from ecshop.haiguan_order_info where tid = '{$tid}' ";
	$count = $db->getOne($sql);
	if($count == 0) {
		return true;
	} else {
		return false;
	}
}

function checkShipping($shipping_id,$shipping_name) {
	global $db;
	$sql="SELECT count(*) 
		FROM ecshop.ecs_shipping 
		WHERE 1
		AND shipping_id='{$shipping_id}'
		AND shipping_name='{$shipping_name}'
		LIMIT 1
	";
	
	$result = $db -> getOne($sql);
	
	if($result == 0){
		return false;
	}else{
		return true;
	}
}

function checkFacility($facility_id) {
	global $db;
	$sql="SELECT count(*) 
		FROM romeo.facility 
		WHERE 1
		AND facility_id='{$facility_id}'
		LIMIT 1
	";
	
	$result = $db -> getOne($sql);
	if($result == 0){
		return false;
	}else{
		return true;
	}
}

function goods_check($product_id,$outer_id){
	global $db;
//	if(strpos($product_id, 'TC-')===0 || strpos($outer_id, 'TC-')===0){
//		return TC_check($product_id,$outer_id);
//	}
	if($product_id==$outer_id){
		$goods_id=$product_id;
		$style_id=false;
	}else{
		$ele=explode('_', $outer_id);
		if(empty($ele) || count($ele)!=2){
			return false;
		}
		$goods_id=$ele[0];
		$style_id=$ele[1];

		if($goods_id!=$product_id){
			return false;
		}
	}
	$sql="SELECT g.goods_id,g.goods_name, gs.style_id
		FROM ecs_goods g
		LEFT JOIN ecs_goods_style gs ON g.goods_id=gs.goods_id
		WHERE g.goods_id='{$goods_id}'
	";
	$params['goods_id']=$goods_id;
	if($style_id!==false){
		$sql.=" AND gs.style_id = '{$style_id}' ";
		$params['style_id']=$style_id;
	}
	// die($sql);
	$result = $db -> getAll($sql);
	return $result;
}

function TC_check($product_id,$outer_id) {
	global $db;
	if($product_id != $outer_id){
		return false;
	}
	$sql="SELECT
		dggi.goods_id,dggi.style_id,dggi.goods_name
	FROM
		ecshop.distribution_group_goods dgg
	LEFT JOIN ecshop.distribution_group_goods_item dggi ON dgg.group_id = dggi.group_id
	WHERE
		dgg.code = '{$outer_id}'
	";
	$result = $db -> getRow($sql);
	return $result;
}

function insertHaiguanOrder($params,$application_key) {
	global $db;
	$sql = "INSERT INTO ecshop.haiguan_order_info(
			application_key,facility_id,platform_code,tid,amount,post_fee,goods_amount,payment,
			remark,title,order_time,trade_trans_no,pay_time,payment_code,mibun_number,
			name,email,phone,account,apply_status,shipping_status,tracking_number,
			shipping_id,shipping_name,consignee,province,city,district,address,
			receiver_phone,created_stamp,last_updated_stamp,transfer_status,transfer_note)
			VALUES(
			'{$application_key}','{$params['facility_id']}','{$params['platform_code']}','{$params['tid']}','{$params['amount']}','{$params['post_fee']}','{$params['goods_amount']}','{$params['payment']}',
			'{$params['remark']}','{$params['title']}','{$params['order_time']}','{$params['trade_trans_no']}','{$params['pay_time']}','{$params['payment_code']}','{$params['mibun_number']}',
			'{$params['name']}','{$params['email']}','{$params['phone']}','{$params['account']}','INIT','00','',
			'{$params['shipping_id']}','{$params['shipping_name']}','{$params['consignee']}','{$params['province']}','{$params['city']}','{$params['district']}','{$params['address']}',
			'{$params['receiver_phone']}',NOW(),NOW(),'{$params['transfer_status']}','{$params['transfer_note']}'
			)";

	$result = $db -> query($sql);
	return $db->insert_id();	
}

// // /**
//  * 根据区域名称 返回区域
//  */
function get_region_by_name_type($region_name,$type)
{
	global $db;
    $sql = "SELECT region_id FROM ecshop.ecs_region WHERE region_name = '{$region_name}' and region_type='{$type}'" ;
//     var_dump($sql);
    return $db->GetOne($sql);
}


?>