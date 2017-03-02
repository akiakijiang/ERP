<?php
define('IN_ECS',true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
// Yii::import('application.commands.LockedCommand', true);

//php yiic BWShopRMA >bwshoprma.txt
//php yiic BWShopRMA cancelAndRefund >bwshoprma.txt
//php yiic BWShopRMA orderReturn >bwshoprma.txt

/**
 * BWSHOP RMA Request Processing
 * 
 * @author sinri
 *
 */
class BWShopRMACommand extends CConsoleCommand
{
	private $soapclient;
	private $db; // db数据库

	protected function getDB() {
		if (! $this->db) {
			$this->db = Yii::app ()->getDb ();
			$this->db->setActive ( true );
		}
		return $this->db;
	}

	protected function getRomeo(){
		if(!$this->soapclient){
			$this->soapclient=Yii::app()->getComponent('romeo');
		}
		return $this->soapclient;
	}

	protected function log($str){
		$dt=date("Y-m-d H:i:s");
		echo "[".$dt."]".$str.PHP_EOL;
	}
	
	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex()
	{
		// What to do?
		echo "All Green... Vanishment this world!";
	}

/**
 撤单退款。
**/

	public function actionCancelAndRefund(){
		// find all refunding requests from bw_order_refund
		$this->log('CANCEL GO = Ningen ha kyuumu nari');

		$sql = "SELECT * 
			FROM ecshop.bw_order_refund
			WHERE refund_status='Y'
			AND create_time < date_add(now(), interval - 2 minute)
		";
		$result = $this->getDB()->createCommand($sql)->queryAll();

		// foreach, create refund
		
		if(!empty($result)){
			foreach ($result as $result_line) {
				$bw_order_id=$result_line['order_id'];
				$this->log('CANCEL bw_order_id '.$bw_order_id." begins ... ");
				try {
					$done=$this->bwOrderCancelAndRefund($bw_order_id);

					$sql="UPDATE bw_order_refund SET refund_status='F' where order_id='{$result_line['order_id']}'";
					$afx = $this->getDB()->createCommand ( $sql )->execute();

					$this->log('CANCEL bw_order_id '.$bw_order_id." end with ".($done?'OK':'KO')." AFX=".$afx);
				} catch (Exception $e) {
					$this->log('CANCEL bw_order_id '.$bw_order_id." exception: ".$e->getMessage());
				}
			}
		}

	}

	public function bwOrderCancelAndRefund($bw_order_id){
		// get the erp order

		$sql = "SELECT eoi.* 
			FROM ecshop.bw_order_info boi
			INNER JOIN ecshop.ecs_order_info eoi ON boi.outer_order_sn=eoi.taobao_order_sn AND eoi.party_id=65638
			WHERE boi.order_id='{$bw_order_id}'
		";
		$erp_order_info = $this->getDB()->createCommand($sql)->queryRow();

		if(empty($erp_order_info)){
			throw new Exception("No mapped order in ERP for bw_order_id ".$bw_order_id, 1);
		}

		/*
		{
		    "info": {
		        "refund_id": "",
		        "refund_type_id": "6",
		        "order_sn": "1843315516",
		        "applicant": "高华凤",
		        "currency": "RMB",
		        "total_amount": "0",
		        "customer_user_id": "105087",
		        "order_id": "4908417",
		        "party_id": "65553",
		        "payment_type_id": "1174702",
		        "bank_name": "",
		        "bank_account_no": "",
		        "account_user_login": "",
		        "bank_province": "",
		        "bank_city": "",
		        "alipay_account_user_login": "",
		        "alipay_bank_account_no": "",
		        "qqtenpay_account_user_login": "",
		        "qqtenpay_account_no": "",
		        "note": "",
		        "created_by_user_login": "ljni"
		    }
		}

		====

		{
		    "info": {
		        "refund_id": "",
		        "refund_type_id": "6",
		        "order_sn": "4455494613",
		        "applicant": "刘雁",
		        "currency": "",
		        "total_amount": "100",
		        "customer_user_id": "0",
		        "order_id": "7194369",
		        "party_id": "65638",
		        "payment_type_id": "1174708",
		        "bank_name": "",
		        "bank_account_no": "",
		        "account_user_login": "",
		        "bank_province": "",
		        "bank_city": "",
		        "alipay_account_user_login": "",
		        "alipay_bank_account_no": "",
		        "qqtenpay_account_user_login": "",
		        "qqtenpay_account_no": "",
		        "note": "",
		        "created_by_user_login": "ljni"
		    },
		    "detail": {
		        "others": [
		            {
		                "reason_id": "1174706",
		                "note": "We die with honor!",
		                "detail_type_id": "2",
		                "cost": "100"
		            }
		        ]
		    }
		}
		*/

		$data=array(
			'info'=>array(
				"refund_id"=>"",
		        "refund_type_id"=>"6",
		        "order_sn"=>$erp_order_info['order_sn'],
		        "applicant"=>$erp_order_info['consignee'],
		        "currency"=>"RMB",
		        "total_amount"=>$erp_order_info['order_amount'],
		        "customer_user_id"=>$erp_order_info['user_id'],
		        "order_id"=>$erp_order_info['order_id'],
		        "party_id"=>$erp_order_info['party_id'],
		        "payment_type_id"=>"1174708",// FROM romeo.refund_payment_type, Use 'Taobao Shop' as Ms Lao Saith
		        "bank_name"=>"",
		        "bank_account_no"=>"",
		        "account_user_login"=>"",
		        "bank_province"=>"",
		        "bank_city"=>"",
		        "alipay_account_user_login"=>"",
		        "alipay_bank_account_no"=>"",
		        "qqtenpay_account_user_login"=>"",
		        "qqtenpay_account_no"=>"",
		        "note"=>"",
		        "created_by_user_login"=>"bwshop"
			),
			'detail'=>array(
				'others'=>array(
					array(
						'reason_id'=>'1174706',
						"note"=>"BWSHOP订单撤单退款申请",
		                "detail_type_id"=>"2",
		                "cost"=>$erp_order_info['order_amount'],
					)
				),
			),
		);

		$data=stripslashes_deep($data);
		$this->log('send to romeo:'.json_encode($data));

		$result = $this->getRomeo()->RefundService->createRefund(array('arg0' => json_encode($data)));
		$refund_id=$result->return;

		$this->log('returned from romeo:');
		print_r($result);

        // 退款申请成功后添加log到订单
        $line=$this->order_action(
        	$erp_order_info['order_sn'], 
        	$erp_order_info['order_status'], 
        	$erp_order_info['shipping_status'], 
        	$erp_order_info['pay_status'], 
        	'新建BWSHOP订单撤单退款申请', 
        	'bwshop'
        );

        $this->log('order action inserted? line='.$line);

        return true;
	}

/**
 退货
**/

	public function actionOrderReturn(){
		// find all refunding requests from bw_order_refund
		$this->log('RETURN GO = Ningen ha kyuumu nari');

		$sql = "SELECT * 
			FROM ecshop.bw_order_return
			WHERE return_status='Y'
			AND create_time < date_add(now(), interval - 2 minute)
		";
		$result = $this->getDB()->createCommand($sql)->queryAll();

		// foreach, create refund
		
		if(!empty($result)){
			foreach ($result as $result_line) {
				$bw_order_id=$result_line['order_id'];
				$this->log('RETURN bw_order_id '.$bw_order_id." begins ... ");
				try {
					$done=$this->bwOrderReturn($bw_order_id);
					$this->log('RETURN bw_order_id '.$bw_order_id." end with ".($done?'OK':'KO'));
				} catch (Exception $e) {
					$this->log('RETURN bw_order_id '.$bw_order_id." exception: ".$e->getMessage());
				}
			}
		}

	}

	function bwOrderReturn($bw_order_id){
		//service basic data
		$sql = "SELECT
				eoi.party_id,
				eoi.facility_id,
				eoi.user_id,
				eoi.order_id,
				eu.user_name AS apply_username
			FROM
				ecshop.bw_order_info boi
			INNER JOIN ecshop.ecs_order_info eoi ON eoi.taobao_order_sn = boi.outer_order_sn AND eoi.party_id=65638
			LEFT JOIN ecshop.ecs_users eu ON eoi.user_id = eu.user_id
			WHERE
				boi.order_id = '{$bw_order_id}'
		";
		$service = $this->getDB()->createCommand($sql)->queryRow();

		$erp_order_id=$service['order_id'];

		$service['service_type'] = 2;		//退货
	    $service['service_status'] = 0;
	    $service['apply_reason'] = 'BWSHOP退货';
	    $service['apply_datetime'] = date("Y-m-d H:i:s");
	    $service['responsible_party'] = 2;	//todo	'1'=>'乐其','2'=>'厂家','3'=>'顾客','4'=>'快递公司','5'=>'乐麦',
	    $service['dispose_method'] = 1;		//退货
	    $service['dispose_description'] = 'BWSHOP退货';

	    //Goods
	    $sql="SELECT
				eog.rec_id AS order_goods_id,
				eog.goods_id,
				eog.style_id,
				eog.goods_number - sum(ifNULL(sog.amount, 0))AS amount
			FROM
				ecshop.ecs_order_goods eog
			LEFT JOIN ecshop.service_order_goods sog ON sog.order_goods_id = eog.rec_id
			WHERE
				eog.order_id = '{$erp_order_id}'
			GROUP BY
				eog.rec_id
		";
		$left_order_goods_list=$this->getDB()->createCommand($sql)->queryAll();

		$sql="SELECT
				bogr.outer_id AS goods_id,
				0 AS style_id,
				sum(bogr.quantity)AS amount
			FROM
				bw_order_return bor
			LEFT JOIN bw_order_goods_return bogr ON bor.return_id = bogr.return_id
			WHERE
				bor.order_id = '{$bw_order_id}'
			GROUP BY
				outer_id
		";
		$return_order_goods_list = $this->getDB()->createCommand($sql)->queryAll();
//var_dump("return_order_goods_list :");
//var_dump($return_order_goods_list);
		//插入售后的商品记录 service_order_goods
	    $service_goods_list = array ();
	    foreach ($return_order_goods_list as $return_order_goods) {
	    	$remain_amount = $return_order_goods['amount'];
	    	foreach ($left_order_goods_list as $left_order_goods) {
	    		if($remain_amount > 0){
	    			$ele=explode('_', $return_order_goods['goods_id']);
					$goods_id=$ele[0];
					$style_id=(isset($ele[1])?$ele[1]:0);

		    		if($goods_id == $left_order_goods['goods_id'] 
		    			&& $style_id === $left_order_goods['style_id'] 
		    			&& $left_order_goods['amount']>0
		    		){
	    				$record_amount = $remain_amount > $left_order_goods['amount'] ? $left_order_goods['amount'] : $remain_amount;
	    				$remain_amount -= $record_amount;
			    		$service_goods_list[] = array(
			    			'user_id' => $service['user_id'],
			    			'order_id' => $erp_order_id,
			    			'order_goods_id' => $left_order_goods['order_goods_id'],
			    			'amount' => $record_amount
			    			);
		    		}
	    		}
	    	}
	    	assert($remain_amount === 0);// is it to be deleted for partly return
	    }
//	    var_dump(" service_goods_list : ");
//var_dump($service_goods_list);

	    //Service Log
	    $service_log = array (
	        'service_status' => 0,
	        //'service_type' => 'SERVICE_TYPE_BACK',
	        'log_note' =>  "BWSHOP退货",
	        'log_type' => 'CUSTOMER_SERVICE',
	        'is_remark' => 0
	    );

	    $transaction=$this->getDB()->beginTransaction();
		try{
			//insert ecshop.service

			$sql="INSERT INTO ecshop.service (
					service_id,
					party_id,
					facility_id,
					user_id,
					order_id,
					apply_username,
					service_type,
					service_status,
					apply_reason,
					apply_datetime,
					responsible_party,
					dispose_method,
					dispose_description
				) VALUES (
					NULL,
					'{$service['party_id']}',
					'{$service['facility_id']}',
					'{$service['user_id']}',
					'{$service['order_id']}',
					'{$service['apply_username']}',
					'{$service['service_type']}',
					'{$service['service_status']}',
					'{$service['apply_reason']}',
					'{$service['apply_datetime']}',
					'{$service['responsible_party']}',
					'{$service['dispose_method']}',
					'{$service['dispose_description']}'
				)
			";
			$afx = $this->getDB()->createCommand ( $sql )->execute();
			if ($afx != 1) {
				throw new Exception("Failed to insert service ... ", 1);
				
				//$transaction->rollBack();
				//return false;
			}
			$service_id = $this->getDB()->getLastInsertID();

			//insert into ecshop.service_order_goods

			if(empty($service_goods_list)){
				throw new Exception("Empty service_goods_list", 1);
				
			}

			foreach ($service_goods_list as $line) {
				$sql="INSERT INTO ecshop.service_order_goods (
						service_order_goods_id,
						service_id,
						user_id,
			    		order_id,
			    		order_goods_id,
			    		amount
					)VALUES(
						NULL,
						'{$service_id}',
						'{$line['user_id']}',
						'{$line['order_id']}',
						'{$line['order_goods_id']}',
						'{$line['amount']}'
					)
				";
				$afx = $this->getDB()->createCommand ( $sql )->execute();
				if ($afx != 1) {
					throw new Exception("Failed to insert into service_order_goods ...", 1);
					
					// $transaction->rollBack();
					// return false;
				}
			}

			//insert into service_log

			$sql="INSERT INTO service_log (
					service_log_id,
					service_id,
					service_status,
	        		log_note,
	        		log_type,
	        		is_remark,
	        		type_name,
	        		status_name,
	        		log_username,
	        		log_datetime
				)VALUES(
					NULL,
					'{$service_id}',
					'{$service_log['service_status']}',
					'{$service_log['log_note']}',
					'{$service_log['log_type']}',
					'{$service_log['is_remark']}',
					'退货申请',
					'退货申请,待审核',
					'BWSHOP RMA',
					now()
				)
			";
			$afx = $this->getDB()->createCommand ( $sql )->execute();
			if ($afx != 1) {
				throw new Exception("Failed to insert into service_log ...", 1);
					
				// $transaction->rollBack();
				// return false;
			}

			$sql="UPDATE bw_order_return SET return_status='F' where order_id='{$bw_order_id}'";
			$afx = $this->getDB()->createCommand ( $sql )->execute();

			if ($afx != 1) {
				throw new Exception("Failed to insert into service_log ...", 1);
					
				// $transaction->rollBack();
				// return false;
			}

			$transaction->commit();
			return true;
		}catch(Exception $e){
			$transaction->rollBack();
			$this->log($e->getMessage());
			return false;
		}
	}

/**
咕嘿嘿
**/

	/**
	 * 记录订单操作记录
	 *
	 * @access  public
	 * @param   string  $order_sn           订单编号
	 * @param   integer $order_status       订单状态
	 * @param   integer $shipping_status    配送状态
	 * @param   integer $pay_status         付款状态
	 * @param   string  $note               备注
	 * @param   string  $username           用户名，用户自己的操作则为 buyer
	 * @return  void
	 */
	function order_action($order_sn, $order_status, $shipping_status, $pay_status, $note = '', $username = 'bwshop')
	{
	    $sql = "INSERT INTO ecshop.ecs_order_action (
	    		order_id, 
	    		action_user, 
	    		order_status, 
	    		shipping_status, 
	    		pay_status, 
	    		action_note, 
	    		action_time
	    	) SELECT 
				order_id, 
				'$username', 
				'$order_status', 
				'$shipping_status', 
				'$pay_status', 
				'$note', 
				now() 
			FROM ecshop.ecs_order_info 
			WHERE order_sn = '$order_sn'
		";
	    $line = $this->getDB()->createCommand ( $sql )->execute();
	    return $line;
	}

	
}
/**
 * 递归方式的对变量中的特殊字符去除转义
 *
 * @access  public
 * @param   mix     $value
 *
 * @return  mix
 */
function stripslashes_deep($value)
{
    if (empty($value))
    {
        return $value;
    }
    else
    {
        return is_array($value) ? array_map('stripslashes_deep', $value) : stripslashes($value);
    }
}