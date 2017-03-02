<?php
define('IN_ECS',true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once ROOT_PATH . 'admin/includes/lib_service.php';
include_once ROOT_PATH . 'admin/function.php';
require_once ROOT_PATH . 'data/master_config.php';

// Yii::import('application.commands.LockedCommand', true);

//php yiic KuajingBwshop SyncOrder

/**
 * Sync orders to bwshop
 *
 */
 class KuajingBwshopCommand extends CConsoleCommand
{
	private $db; // db数据库
//	private $base_url = "http://testbwshop.leqee.com";
//	private $base_url = "https://testerpbrand.leqee.com/bwshop";
	private $base_url = "https://erpbrand.leqee.com/bwshop";
	private $createOrder = "/order/createNewOrderForErp";
	private $validate_checksum = "/validate_checksum";
	
	function sendMail($subject, $body = null, $path = null, $file_name = null) {
		$mail=Helper_Mail::smtp();
		$mail->IsSMTP();                 // 启用SMTP
	    $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
	    $mail->SMTPAuth = true;         //启用smtp认证
	    $mail->Username =$GLOBALS['emailUsername'];   // 你的邮箱地址
	    $mail->Password = $GLOBALS['emailPassword'];      //你的邮箱密码  */
	    $mail->CharSet='UTF-8';
		$mail->Subject="【KuajingBwshopCommand】" . $subject;
		$mail->SetFrom($GLOBALS['emailUsername'], '乐其网络科技');
		
		$mail->AddAddress('zjli@leqee.com', '李志杰');
		$mail->AddAddress('yjchen@leqee.com', '陈越佳');
		$mail->AddAddress('ljni@leqee.com', '倪李俊');
		$mail->AddAddress('kj-sh@leqee.com', '跨境售后组');
		
		$mail->Body = date("Y-m-d H:i:s") . " " . $body;
		if($path != null && $file_name != null){
			$mail->AddAttachment($path, $file_name);
		}
		try {
			if ($mail->Send()) {
				$this->log('mail send success');
		    } else {
		    	$this->log('mail send fail');
		    }
		} catch(Exception $e) {
			$this->log('mail send exception ' . $e->getMessage());
			// 屏蔽PHP邮箱 版本错误
			//Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475  Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475 
		}
	}

	protected function getDB() {
		if (! $this->db) {
			$this->db = Yii::app ()->getDb ();
			$this->db->setActive ( true );
		}
		return $this->db;
	}

	protected function log($str){
		$dt=date("Y-m-d H:i:s");
		echo "[".$dt."]"." KuajingBwshopCommand ".$str.PHP_EOL;
	}
	
	/**
	 * 订单同步
	 * 
	 **/
	 public function actionSyncOrder($hours = 6) {
	 	$this->log('actionSyncOrder start');
	 	//一号店  京东上的店铺 以及非对接小店铺
	 	require_once(ROOT_PATH.'includes/lib_common.php');

		$sql_distributor_ids = "select bs.ecs_distributor_id,bs.shop_name,bs.shop_code,bs.shop_key,ifnull(tsc.shop_type,'none') as shop_type  
								from ecshop.bw_shop bs 
								left join ecshop.taobao_shop_conf tsc on bs.ecs_distributor_id = tsc.distributor_id
								where bs.is_sync = 1 ";					
		$distributor_ids = $this->getDB()->createCommand($sql_distributor_ids)->queryAll();
		
		foreach($distributor_ids as $distributor_id) {
			$shop_code = $distributor_id['shop_code'];
		 	$shop_key = $distributor_id['shop_key'];
		 	$shop_type = $distributor_id['shop_type'];
		 	$shop_name = $distributor_id['shop_name'];
	 		$sql_orders = "select *
							from ecshop.ecs_order_info 
							where facility_id = '149849262' and order_status = '1' and pay_status = '2' and shipping_status = '0' and distributor_id = '{$distributor_id['ecs_distributor_id']}' and (FROM_UNIXTIME(pay_time)  > date_sub(NOW(),interval {$hours} hour) or FROM_UNIXTIME(confirm_time)  > date_sub(NOW(),interval {$hours} hour))";
			$orders = $this->getDB()->createCommand($sql_orders)->queryAll();
			switch($shop_type)  {
				case 'yhd':
				foreach($orders as $order) {
					$order_info = new YhdOrders($order['order_id']);
					$data = $order_info->getAllData();
//					echo 'data:';print_r($data);echo PHP_EOL;				
					$result = $this->execute($this->createOrder,$data,$shop_code,$shop_key); 
					if($result == false) {
						$this->sendMail("【ERROR】E2B订单转化错误","actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", ERP订单号:".$order['order_sn'].", 支付方式或交易流水号为空. "." \n" );
					} else {
						$obj = json_decode($result);	
	//					print_r($obj);								
						$arr = is_object($obj) ? get_object_vars($obj) : $obj;
						if($arr['result'] == 'ok' ) {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' . $order['order_id'] . ', Sync Success');
							$this->insertAction($order);
						} else {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' .$order['order_id'] . ', Sync Fail. '.$arr['error_info']);
							if($arr['error_code'] != '51003') {
								$this->sendMail("【ERROR】E2B订单转化错误", BwshopErrorMsg::code2Chinese($arr['error_code']).PHP_EOL."actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", order_id:".$order['order_id'] . ", ERP订单号:".$order['order_sn'].", 转化到BWSHOP失败. ".$arr['error_info']." \n" );
							}
						}	
					}
				}
				break;
				case '360buy_overseas':
				case '360buy':
				foreach($orders as $order) {
					$order_info = new JdOrders($order['order_id']);
					$data = $order_info->getAllData();				
//					echo "data： ";print_r($data); echo PHP_EOL;
					$result = $this->execute($this->createOrder,$data,$shop_code,$shop_key); 
					if($result == false) {
						$this->sendMail("【ERROR】E2B订单转化错误","actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", ERP订单号:".$order['order_sn'].", 支付方式或交易流水号为空. "." \n" );
					} else {
						$obj = json_decode($result);
						$arr = is_object($obj) ? get_object_vars($obj) : $obj;
	//					print_r($arr);
						if($arr['result'] == 'ok' ) {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' . $order['order_id'] . ', Sync Success');
							$this->insertAction($order);
						} else {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' .$order['order_id'] . ', Sync Fail. '.$arr['error_info']);
							if($arr['error_code'] != '51003') {
								$this->sendMail("【ERROR】E2B订单转化错误",BwshopErrorMsg::code2Chinese($arr['error_code']).PHP_EOL. "actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", order_id:".$order['order_id'] .  ", ERP订单号:".$order['order_sn']. ", 同步到BWSHOP失败. ".$arr['error_info']." \n" );
							}
						}
					}
				}
				break;
				case 'taobao':
				foreach($orders as $order) {
					$order_info = new TaobaoOrders($order['order_id']);
					$data = $order_info->getAllData();				
//					echo "data： ";print_r($data); echo PHP_EOL;
					$result = $this->execute($this->createOrder,$data,$shop_code,$shop_key); 
					if($result == false) {
						$this->sendMail("【ERROR】E2B订单转化错误","actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", ERP订单号:".$order['order_sn'].", 支付方式或交易流水号为空. "." \n" );
					} else {
						$obj = json_decode($result);
						$arr = is_object($obj) ? get_object_vars($obj) : $obj;
	//					print_r($arr);
						if($arr['result'] == 'ok' ) {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' . $order['order_id'] . ', Sync Success');
							$this->insertAction($order);
						} else  {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' .$order['order_id'] . ', Sync Fail.  '.$arr['error_info']);
							if($arr['error_code'] != '51003') {
								$this->sendMail("【ERROR】E2B订单转化错误",BwshopErrorMsg::code2Chinese($arr['error_code']).PHP_EOL. "actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", order_id:".$order['order_id'] .  ", ERP订单号:".$order['order_sn'].", 同步到BWSHOP失败. ".$arr['error_info']." \n" );
							}
						}	
					}
				}
				break;
				case 'miya':
				foreach($orders as $order) {
					$order_info = new MiyaOrders($order['order_id']);
					$data = $order_info->getAllData();				
//					echo "data： ";print_r($data); echo PHP_EOL;
					$result = $this->execute($this->createOrder,$data,$shop_code,$shop_key);
					if($result == false) {
						$this->sendMail("【ERROR】E2B订单转化错误","actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", ERP订单号:".$order['order_sn'].", 支付方式或交易流水号为空. "." \n" );
					} else { 
						$obj = json_decode($result);
						$arr = is_object($obj) ? get_object_vars($obj) : $obj;
	//					print_r($arr);
						if($arr['result'] == 'ok' ) {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' . $order['order_id'] . ', Sync Success');
							$this->insertAction($order);
						} else  {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' .$order['order_id'] . ', Sync Fail. '.$arr['error_info']);
							if($arr['error_code'] != '51003') {
								$this->sendMail("【ERROR】E2B订单转化错误",BwshopErrorMsg::code2Chinese($arr['error_code']).PHP_EOL. "actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", order_id:".$order['order_id'] .  ", ERP订单号:".$order['order_sn'].", 同步到BWSHOP失败. ".$arr['error_info']." \n" );
							}
						}
					}
				}
				break;	
				case 'suning':
				foreach($orders as $order) {
					$order_info = new SuningOrders($order['order_id']);
					$data = $order_info->getAllData();				
//					echo "data： ";print_r($data); echo PHP_EOL;
					$result = $this->execute($this->createOrder,$data,$shop_code,$shop_key); 
					if($result == false) {
						$this->sendMail("【ERROR】E2B订单转化错误","actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", ERP订单号:".$order['order_sn'].", 支付方式或交易流水号为空. "." \n" );
					} else {
						$obj = json_decode($result);
						$arr = is_object($obj) ? get_object_vars($obj) : $obj;
	//					print_r($arr);
						if($arr['result'] == 'ok' ) {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' . $order['order_id'] . ', Sync Success');
							$this->insertAction($order);
						} else  {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' .$order['order_id'] . ', Sync Fail.  '.$arr['error_info']);
							if($arr['error_code'] != '51003') {
								$this->sendMail("【ERROR】E2B订单转化错误",BwshopErrorMsg::code2Chinese($arr['error_code']).PHP_EOL. "actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", order_id:".$order['order_id'] .  ", ERP订单号:".$order['order_sn'].", 同步到BWSHOP失败. ".$arr['error_info']." \n" );
							}
						} 
					}
				}
				break;	
				case 'pinduoduo':
				foreach($orders as $order) {
					$order_info = new PinduoduoOrders($order['order_id']);
					$data = $order_info->getAllData();				
//					echo "data： ";print_r($data); echo PHP_EOL;
					$result = $this->execute($this->createOrder,$data,$shop_code,$shop_key); 
					if($result == false) {
						$this->sendMail("【ERROR】E2B订单转化错误","actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", ERP订单号:".$order['order_sn'].", 支付方式或交易流水号为空. "." \n" );
					} else {
						$obj = json_decode($result);
						$arr = is_object($obj) ? get_object_vars($obj) : $obj;
	//					print_r($arr);
						if($arr['result'] == 'ok' ) {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' . $order['order_id'] . ', Sync Success');
							$this->insertAction($order);
						} else  {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' .$order['order_id'] . ', Sync Fail.  '.$arr['error_info']);
							if($arr['error_code'] != '51003') {
								$this->sendMail("【ERROR】E2B订单转化错误",BwshopErrorMsg::code2Chinese($arr['error_code']).PHP_EOL. "actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", order_id:".$order['order_id'] .  ", ERP订单号:".$order['order_sn'].", 同步到BWSHOP失败. ".$arr['error_info']." \n" );
							}
						} 
					}
				}
				break;			
				case 'none':
				foreach($orders as $order) {
					$order_info = new Orders($order['order_id']);
					$data = $order_info->getBaseData();
//					echo 'data:';print_r($data);echo PHP_EOL;					
					$result = $this->execute($this->createOrder,$data,$shop_code,$shop_key);
					if($result == false) {
						$this->sendMail("【ERROR】E2B订单转化错误","actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", ERP订单号:".$order['order_sn'].", 支付方式或交易流水号为空. "." \n" );
					} else {
						$obj = json_decode($result);	
	//					print_r($obj);					
						$arr = is_object($obj) ? get_object_vars($obj) : $obj;
						if($arr['result'] == 'ok' ) {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' .$order['order_id'] . ', Sync Success');
							$this->insertAction($order);
						} else  {
							$this->log('actionSyncOrder shop_type:'. $shop_type .', order_id:' .$order['order_id'] . ', Sync Fail.  '.$arr['error_info']);
							if($arr['error_code'] != '51003') {
								$this->sendMail("【ERROR】E2B订单转化错误", BwshopErrorMsg::code2Chinese($arr['error_code']).PHP_EOL."actionSyncOrder shop_type:". $shop_type .", 店铺名称".$shop_name.", order_id:".$order['order_id'] .  ", ERP订单号:".$order['order_sn'].", 同步到BWSHOP失败. ".$arr['error_info']." \n" );							
							}
						} 	
					}
						
				}
				break;
				default:
				$this->log('no this shop_type:'.$shop_type);
				$this->sendMail("【ERROR】SyncOrder", "no this shop_type:".$shop_type." \n" );
				break;
		
			}
		}
		$this->log('actionSyncOrder end');
	 } 
	 
	 function insertAction($order) {
	 	
	 	$action['order_id']        = $order['order_id'];
	    $action['order_status']    = $order['order_status'];
	    $action['pay_status']      = $order['pay_status'];
	    $action['shipping_status'] = $order['shipping_status'];
	    $action['action_time']     = date("Y-m-d H:i:s");
	    $action['action_note']     = "该订单已推送至bwshop";
	    $action['action_user']     = 'WebService';
	    $sql = "insert into ecshop.ecs_order_action 
	    		set order_id = '{$action['order_id']}',order_status = '{$action['order_status']}',pay_status = '{$action['pay_status']}',shipping_status = '{$action['shipping_status']}',
	    		action_time = now(),action_note = '{$action['action_note']}',action_user='{$action['action_user']}'";
		$this->getDB()->createCommand($sql)->execute();
	 }
	 
	 function execute($url,$data,$client_id,$client_key) {
	 	if($data['payment_code'] == '' || $data['trade_trans_no'] == '') {
	 		return false;
	 	}
	 	$data = json_encode($data);
	 	$this->log('params:' . $data);
	 	$checksum = md5('client_id='.$client_id.'&data='.$data.'&client_key='.$client_key);
	 	$body = json_encode(array('client_id' => $client_id,'data' => $data,'checksum' => $checksum));
	 	$ch = curl_init();//echo "【".$this->base_url.$url."】";
	 	curl_setopt($ch,CURLOPT_URL,$this->base_url.$url);
	 	curl_setopt($ch,CURLOPT_HTTPHEADER,array(
	 		'Content-Type:application/json'
	 	));
	 	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	 	curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
	 	curl_setopt($ch,CURLOPT_POSTFIELDS,$body);
	 	
	 	$res = curl_exec($ch);
	 	curl_close($ch);
	 	
	 	return $res;
	 }
	 
}

class Orders {
	private $db; // db数据库
	function __construct($order_id){	
		$this->order_id = $order_id;	
	}
	
	function getBaseData() {
		$sql_order = "select ifnull(eoi.taobao_order_sn,'') as order_sn,eoi.goods_amount+eoi.bonus as amount,eoi.shipping_fee as post_fee,(eoi.goods_amount + eoi.shipping_fee + eoi.bonus) as goods_amount,eoi.order_amount as payment,
						eoi.postscript as remark,eoi.order_time,if(eoi.pay_status != '0',FROM_UNIXTIME(eoi.pay_time),'') as pay_time,if(eoi.pay_number != null,eoi.pay_number,'') as trade_trans_no,
						eoi.consignee,r1.region_name as province,r2.region_name as city,ifnull(r3.region_name,' ') as district,eoi.address,if(eoi.mobile='',eoi.tel,eoi.mobile) as receiver_phone
						from ecshop.ecs_order_info eoi
						left join ecshop.ecs_region r1 on r1.region_id = eoi.province
						left join ecshop.ecs_region r2 on r2.region_id = eoi.city
						left join ecshop.ecs_region r3 on r3.region_id = eoi.district
						where eoi.order_id = '{$this->order_id}'";
		$order = $this->getDB()->createCommand($sql_order)->queryRow();
//		$sql_order_goods = "select goods_id as product_id,if(style_id=0,goods_id,concat(goods_id,'_',style_id)) as outer_id,sum(goods_number) as quantity, sum(goods_number * goods_price) as amount 
//							from ecshop.ecs_order_goods where order_id = {$this->order_id} group by goods_id,style_id";
		$sql_order_goods = "select eog.goods_id as product_id,if(eog.style_id=0,eog.goods_id,concat(eog.goods_id,'_',eog.style_id)) as outer_id,
							sum(eog.goods_number) as quantity, sum(eog.goods_number * eog.goods_price) as amount,sum(IFNULL(oga.value,'0')) as goods_discount,
							sum(eog.goods_number * eog.goods_price) - sum(IFNULL(oga.value,'0')) as goods_pay_amount
							from ecshop.ecs_order_goods eog
							left join ecshop.order_goods_attribute oga on oga.order_goods_id = eog.rec_id and oga.name = 'DISCOUNT_FEE'
							where eog.order_id = {$this->order_id} group by eog.goods_id,eog.style_id";
		$order_goods = $this->getDB()->createCommand($sql_order_goods)->queryAll();
	                   		
		$total_goods_counts = 0;
		$total_goods_amount = 0;
		$total_goods_discount = 0;

		//红包按公式均摊
		foreach($order_goods as $order_good) {			
			$total_goods_counts += $order_good['quantity'];
			$total_goods_amount += $order_good['amount'];
			$total_goods_pay_amount += $order_good['goods_pay_amount'];
			$total_goods_discount += $order_good['goods_discount'];
		}
		//总红包		
		$sql = "select goods_amount,bonus from ecshop.ecs_order_info where order_id = '{$this->order_id}'";
		$order_bonus = $this->getDB()->createCommand($sql)->queryRow();
		if($total_goods_discount > -$order_bonus['bonus']) {
			$total_goods_counts_tmp = 0;
			$order_bonus_temp = 0;
			$order_goods_amount_temp = 0;
			foreach($order_goods as $key=>$order_good) {
				$total_goods_counts_tmp += $order_good['quantity'];
				if($total_goods_counts == $total_goods_counts_tmp){
					$order_goods[$key]['amount'] = number_format($order['amount'] - $order_goods_amount_temp,2,'.','');
				} else {
					$order_goods[$key]['amount'] = number_format($order_good['amount'] + round($order_bonus['bonus']*$order_good['amount']/$total_goods_amount,2),2,'.','');
					$order_bonus_temp += number_format($order_bonus['bonus']*$order_good['amount']/$total_goods_amount,2,'.','');
					$order_goods_amount_temp += $order_goods[$key]['amount'];
				}
				$order['goods'][$key]['product_id'] = $order_goods[$key]['product_id'];
				$order['goods'][$key]['outer_id'] = $order_goods[$key]['outer_id'];
				$order['goods'][$key]['quantity'] = $order_goods[$key]['quantity'];
				$order['goods'][$key]['amount'] = $order_goods[$key]['amount'];					
			}
		} else {
			$total_goods_counts_tmp = 0;
			$order_bonus_temp = 0;
			$order_goods_amount_temp = 0;
			$order_discount_fee = -$order_bonus['bonus'] - $total_goods_discount;
			foreach($order_goods as $key=>$order_good) {
				$total_goods_counts_tmp += $order_good['quantity'];
				if($total_goods_counts == $total_goods_counts_tmp){
					$order_goods[$key]['amount'] = number_format($order['amount'] - $order_goods_amount_temp,2,'.','');
				} else {
					$order_goods[$key]['amount'] = number_format($order_good['amount'] - $order_good['goods_discount'] - number_format($order_discount_fee*$order_good['goods_pay_amount']/$total_goods_pay_amount,2),2,'.','');
					$order_bonus_temp += number_format($order_good['goods_discount'] + number_format($order_discount_fee*($order_good['amount']-$order_good['goods_discount'])/($total_goods_amount-$total_goods_discount),2),2,'.','');
					$order_goods_amount_temp += $order_goods[$key]['amount'];
				}
				$order['goods'][$key]['product_id'] = $order_goods[$key]['product_id'];	
				$order['goods'][$key]['outer_id'] = $order_goods[$key]['outer_id'];
				$order['goods'][$key]['quantity'] = $order_goods[$key]['quantity'];
				$order['goods'][$key]['amount'] = $order_goods[$key]['amount'];
			}
		}

		$order['amount'] = number_format($order['amount'], 2, '.', '');
		$order['goods_amount'] = number_format($order['goods_amount'], 2, '.', '');
		
		$order['address'] = str_replace(PHP_EOL, '', $order['address']); 
		$order['title'] = '';		
//		$order['goods'] = $order_goods;
		//支付信息
		$order['payment_code'] = '';
//		$order['trade_trans_no'] = '';
		//顾客信息
		$order['mibun_number'] = '';
		$order['name'] = '';
		$order['email'] = '';		
		$order['phone'] = '';
		$order['account'] = '';
		if(substr($order['remark'],0,2) == 'ID') {                //ID[名字|身份证号]
			$arr = explode('|',substr($order['remark'],3,-1));
			if(strlen($arr[1]) == 18) {
				$order['name'] = $arr[0];
				$order['mibun_number'] = $arr[1];
				$order['remark'] = '';
			}			
		}
		return $order;
	}
	
	protected function getDB() {
		if (! $this->db) {
			$this->db = Yii::app ()->getDb ();
			$this->db->setActive ( true );
		}
		return $this->db;
	}
}

//一号店同步订单
class YhdOrders extends Orders{
	public function __construct($order_id){
		parent::__construct($order_id);
	}
	
	function getAllData() {
		$order = parent::getBaseData();
		$sql_customer_info = "select ifnull(syoi.user_identity,'') as mibun_number,ifnull(user_realname,'') as name,ifnull(user_email,'') as email,ifnull(user_phone,'') as phone,ifnull(user_nickname,'') as account,order_create_time as order_time,order_paymentConfirm_date as pay_time
						from ecshop.ecs_order_info eoi
						left join ecshop.sync_yhd_order_info syoi on syoi.yhd_order_code = eoi.taobao_order_sn
						where eoi.order_id = '{$this->order_id}'";
		$customer_info = parent::getDB()->createCommand($sql_customer_info)->queryRow();
		//顾客信息
		if(preg_match('/^([0-9]{17}[0-9X])$/', trim($customer_info['mibun_number']))) {
			$order['mibun_number'] = trim($customer_info['mibun_number']);
			$order['name'] = trim($customer_info['name']);			
		} else if (preg_match('/^([0-9]{17}[0-9x])$/', trim($customer_info['mibun_number']))){
			$order['mibun_number'] = str_replace('x', 'X', trim($customer_info['mibun_number'])); 
			$order['name'] = trim($customer_info['name']);
		} else {
			$order['mibun_number'] = '';
			$order['name'] = '';
		}
		if(preg_match('/^([^@]+@[^@]+\.[^@]+)$/',trim($customer_info['email']))) {
			$order['email'] = trim($customer_info['email']);
		}else{
			$order['email'] = '';
		}		
		$order['phone'] = trim($customer_info['phone']);
		$order['account'] = trim($customer_info['account']);
		//下单时间
		if($customer_info['order_time'] != null) {
			$order['order_time'] = $customer_info['order_time'];
		}		
		//支付时间
		if($customer_info['pay_time'] != null) {
			$order['pay_time'] = $customer_info['pay_time'];
		}		
		return $order;
	}
}

//京东同步订单
class JdOrders extends Orders{
	public function __construct($order_id){
		parent::__construct($order_id);
	}
	
	function getAllData() {
		$order = parent::getBaseData();
		$sql_payment_info = "select sjoi.order_start_time as order_time,sjoi.payment_confirm_time as pay_time,ifnull(sjoi.jd_pay_number,'') as trade_trans_no,sjoi.jd_pay_type
							from ecshop.ecs_order_info eoi 
							left join ecshop.sync_jd_order_info sjoi on sjoi.order_id = eoi.taobao_order_sn
							where eoi.order_id = {$this->order_id}";
		$payment_info = parent::getDB()->createCommand($sql_payment_info)->queryRow();
//		下单时间    支付时间
		if($payment_info['order_time'] != null) {
			$order['order_time'] = $payment_info['order_time'];
		}
		if($payment_info['pay_time'] != null) {
			$order['pay_time'] = $payment_info['pay_time'];
		}	
		//交易流水号	
		if($payment_info['jd_pay_type'] == '京东网银在线') {
			$order['payment_code'] = '08';//京东网银在线
			$order['trade_trans_no'] = $payment_info['trade_trans_no'];
		} else if($payment_info['jd_pay_type'] == '财付通') {
			$order['payment_code'] = '13';//财付通
			$order['trade_trans_no'] = $payment_info['trade_trans_no'];
		}	
		return $order;
	}
}

//淘宝同步订单
class TaobaoOrders extends Orders{
	public function __construct($order_id){
		parent::__construct($order_id);
	}
	
	function getAllData() {
//		$distributor_ids = array('2573');//payment_code天猫国际
		$order = parent::getBaseData();
		$sql_payment_info = "select eoi.distributor_id,bs.ecs_main_distributor_id,stoi.created as order_time,stoi.pay_time,ifnull(stoi.alipay_no,'') as trade_trans_no,ifnull(stoi.title,'') as title, ifnull(stoi.buyer_nick,'') as account,ifnull(stoi.buyer_email,'') as email
							from ecshop.ecs_order_info eoi 
							inner join ecshop.sync_taobao_order_info stoi on stoi.tid = eoi.taobao_order_sn
							inner join ecshop.bw_shop bs on eoi.distributor_id = bs.ecs_distributor_id
							where eoi.order_id = '{$this->order_id}'";
		$payment_info = parent::getDB()->createCommand($sql_payment_info)->queryRow();
//		下单时间    支付时间
		if($payment_info['order_time'] != null) {
			$order['order_time'] = $payment_info['order_time'];
		}
		if($payment_info['pay_time'] != null) {
			$order['pay_time'] = $payment_info['pay_time'];
		}	
		$order['trade_trans_no'] = $payment_info['trade_trans_no'] ? trim($payment_info['trade_trans_no']) : '';	
		$order['title'] = trim($payment_info['title']);
		$order['account'] = trim($payment_info['account']);
		$order['email'] = trim($payment_info['email']);
		
		$sql_main_distributor_ids = "select main_distributor_id from ecshop.main_distributor where name like '天猫国际%' and status = 'NORMAL'";
		$main_distributor_ids = parent::getDB() -> createCommand($sql_main_distributor_ids) -> queryColumn();
		
		if(in_array($payment_info['ecs_main_distributor_id'],$main_distributor_ids)) {
			$order['payment_code'] = '09';//国际支付宝
		} else {
			$order['payment_code'] = '02';
		}
		return $order;
	}
}

//蜜牙同步订单
class MiyaOrders extends Orders{
	public function __construct($order_id){
		parent::__construct($order_id);
	}
	
	function getAllData() {
		$order = parent::getBaseData();
		$sql_payment_info = "select smoi.order_time,smoi.confirm_time as pay_time,smoi.miya_pay_type,ifnull(smoi.miya_pay_number,'') as trade_trans_no 
							from ecshop.ecs_order_info eoi 
							left join ecshop.sync_miya_order_info smoi on smoi.miya_order_id = eoi.taobao_order_sn
							where eoi.order_id = {$this->order_id}";
		$payment_info = parent::getDB()->createCommand($sql_payment_info)->queryRow();
//		下单时间    支付时间
		if($payment_info['order_time'] != null) {
			$order['order_time'] = $payment_info['order_time'];
		}
		if($payment_info['pay_time'] != null) {
			$order['pay_time'] = $payment_info['pay_time'];
		}
		$order['trade_trans_no'] = $payment_info['trade_trans_no'];
		if(in_array($payment_info['miya_pay_type'], array(3,4,23,28))) {
			$order['payment_code'] = '02';//支付宝
		} else if(in_array($payment_info['miya_pay_type'], array(20,22,27))){
			$order['payment_code'] = '13';//财付通
		}
		return $order;
	}
}

//苏宁同步订单
class SuningOrders extends Orders{
	public function __construct($order_id){
		parent::__construct($order_id);
	}
	
	function getAllData() {
		$order = parent::getBaseData();
		$sql_payment_info = "select ssoi.order_saletime  as pay_time,ssog.payorderid as trade_trans_no,
							if(ssoi.order_saletime = null, '', SUBDATE(ssoi.order_saletime, INTERVAL 1 SECOND)) as order_time
							from ecshop.ecs_order_info eoi 
							inner join ecshop.sync_suning_order_info ssoi on ssoi.suning_order_id = eoi.taobao_order_sn
							inner join ecshop.sync_suning_order_goods ssog on ssoi.suning_order_id = ssog.suning_order_id
							where eoi.order_id = '{$this->order_id}'
							group by eoi.taobao_order_sn";
		$payment_info = parent::getDB()->createCommand($sql_payment_info)->queryRow();
		if($payment_info['pay_time'] != null && $payment_info['pay_time'] != '0000-00-00 00:00:00') {
			$order['pay_time'] = $payment_info['pay_time'];
			$order['order_time'] = $payment_info['order_time'];
		}	
		if($payment_info['trade_trans_no'] != null) {
			$order['trade_trans_no'] = $payment_info['trade_trans_no'];
		}
		$order['payment_code'] = '06';
		return $order;
	}
}

//拼多多同步订单
class PinduoduoOrders extends Orders{
	public function __construct($order_id){
		parent::__construct($order_id);
	}
	
	function getAllData() {
		$order = parent::getBaseData();
		$sql_payment_info = "select spoi.date_time  as pay_time,ifnull(spoi.pay_id,'') as trade_trans_no,pay_account,
							if(spoi.date_time = null, '', SUBDATE(spoi.date_time, INTERVAL 1 SECOND)) as order_time
							from ecshop.ecs_order_info eoi 
							inner join ecshop.sync_pinduoduo_order_info spoi on spoi.order_no = eoi.taobao_order_sn
							where eoi.order_id = '{$this->order_id}'
							group by eoi.taobao_order_sn";
		$payment_info = parent::getDB()->createCommand($sql_payment_info)->queryRow();
		if($payment_info['pay_time'] != null && $payment_info['pay_time'] != '0000-00-00 00:00:00') {
			$order['pay_time'] = $payment_info['pay_time'];
			$order['order_time'] = $payment_info['order_time'];
		}	
		
		$order['trade_trans_no'] = $payment_info['trade_trans_no'];
		if($payment_info['pay_account'] == 'WEIXIN') {
			$order['payment_code'] = '13';
		}else if($payment_info['pay_account'] == 'ALIPAY') {
			$order['payment_code'] = '02';
		}
		return $order;
	}
}

/**
* 
*/
class BwshopErrorMsg
{
	
	public static function code2Chinese($code){
		static $error_config_error_list=array(
			/* Normal */
			'20000'=>'什么问题都没有',
			
			/* Security Issue */
			'40000'=>'黑客你好',
			'40001'=>'你掉进了陷阱哈哈哈',
			// POST Checksum
			'40002'=>'良民证校验出错',
			'40003'=>'咕嘿嘿嘿嘿嘿嘿',

			'42000'=>'参数格式错误。检查如手机号、身份证、邮箱等带格式要求的字段是否出错。',
			'42001'=>'数据库参数出错',
			
			/* Functional Issue */
			'50000'=>'人工智能傻掉了',
			'50001'=>'数据库连接挂了',
			
			// Order
			'51001'=>'订单不包含商品',
			'51002'=>'不存在的订单',
			'51003'=>"新建订单失败", 
			'51004'=>"新建订单商品失败", 
			'51005'=>"发货方式格式错误",
			'51006'=>'商品信息混乱',
			'51007'=>'顾客信息错误',
			'51008'=>'支付信息错误',

			// Payment
			'52001'=>'支付方式不存在',
			// Shipping
			'53001'=>'运送方式不存在',

			//Cancel order for refund
			'54001'=>'无法发起撤单退款',
			'54002'=>'无法为其注册撤单',

			//Return order goods for refund
			'55001'=>'退货订单条件不符',
			'55002'=>'退货申请子虚乌有',
			'55003'=>"退货申请创建失败",
			'55004'=>"退货商品信息创建失败",
			'55005'=>"退货数量过多",
		);
		if(isset($error_config_error_list[$code])){
			return $error_config_error_list[$code];
		}else{
			return $code;
		}
	}
}
 
 ?>