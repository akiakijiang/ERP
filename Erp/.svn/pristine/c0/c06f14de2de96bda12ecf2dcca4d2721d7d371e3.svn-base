<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');

require_once ROOT_PATH . 'includes/helper/mail.php';
require_once (ROOT_PATH . 'includes/helper/array.php');

require_once(ROOT_PATH . 'admin/includes/lib_express_arata.php');

/**
 * 称重发货时扫描的耗材，放在这里自动出库
 * 
 * @author ljzhou 2013.03.26
 * @version $Id$
 * @package application.commands
 */
class AutoSynThermalOrderCommand extends CConsoleCommand {
	private $db;  // db数据库
	private $soapclient;
	private $slave;
	/**
	 * 取得slave数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getSlave()
    {
        if(!$this->slave)
        {
            if(($this->slave=Yii::app()->getComponent('slave'))===null)
            $this->slave=Yii::app()->getDb();
            $this->slave->setActive(true);
        }
        return $this->slave;
    }

    public function actionIndex()
    {
    	/*
        $currentTime=microtime(true);
        // 同步
        $this->run(array('ZTOOrderApply'));
        $this->run(array('ZTOOrderSubmit'));

        //$this->run(array('WasteStat'));
        */
        echo "All Green ~".PHP_EOL;
    }
        
    public function actionZTOOrderApply(){
    	//中通
    	$this->log('中通 上海 申请开始');
    	$done_zto_sh=zto_mail_applys('SH');
//    	$this->log('中通 北京 申请开始');
//    	$done_zto_bj=zto_mail_applys('BJ');
		$this->log('中通 成都 申请开始');
    	$done_zto_cd=zto_mail_applys('CD');
//    	$this->log('中通 上海水果 申请开始');
//    	$done_zto_bj=zto_mail_applys('SHSG');
//    	$this->log('中通 嘉兴水果 申请开始');
//    	$done_zto_sh=zto_mail_applys('JXSG');
    	$this->log('中通 苏州 申请开始');
    	$done_zto_szsg=zto_mail_applys('SZSG');
//    	$this->log('中通 成都水果 申请开始');
//    	$done_zto_bj=zto_mail_applys('SHSG');
//    	$this->log('中通 武汉水果 申请开始');
//    	$done_zto_sh=zto_mail_applys('WHSG');
//    	$this->log('中通 北京水果 申请开始');
//    	$done_zto_bj=zto_mail_applys('BJSG');
//    	$this->log('中通 深圳水果 申请开始');
//    	$done_zto_bj=zto_mail_applys('SHZSG');
//    	$this->log('中通 嘉兴 申请开始'); //与水果仓冲突，没有改账号就不开启
//    	$done_zto_jx=zto_mail_applys('JX');
    	//申通
//    	$this->log('申通 北京 申请开始');
//    	$done_sto_bj=sto_mail_applys('BJ');
    	$this->log('申通 东莞 申请开始');
    	$done_sto_dg=sto_mail_applys('DG');
    	//汇通
    	$this->log('汇通 上海 申请开始');
    	$done_ht_sh=ht_auto_apply_mailno('SH');
    	$this->log('汇通 北京 申请开始');
    	$done_ht_bj=ht_auto_apply_mailno('BJ');
    	$this->log('汇通 嘉兴水果 申请开始');
    	$done_ht_jxsg=ht_auto_apply_mailno('JXSG');
    	$this->log('汇通 上海水果 申请开始');
    	$done_ht_shsg=ht_auto_apply_mailno('SHSG');
    	$this->log('汇通 万霖北京 申请开始');
    	$done_ht_wlbjc=ht_auto_apply_mailno('WLBJC');
//    	$this->log('汇通 武汉水果 申请开始');
//    	$done_ht_sh=ht_auto_apply_mailno('WHSG');
//    	$this->log('汇通 北京水果 申请开始');
//    	$done_ht_bj=ht_auto_apply_mailno('BJSG');
//    	$this->log('汇通 康贝奉贤 申请开始');
//    	$done_ht_sh=ht_auto_apply_mailno('KBFX');
    	//圆通
    	/**圆通已经改头换面使用单独获取方式了
    	$this->log('圆通 武汉 申请开始');
    	$done_yto_wh=yto_applyNewBillCodes('WH');
    	$this->log('圆通 北京水果 申请开始');
    	$done_yto_wh=yto_applyNewBillCodes('BJSG');
    	$this->log('圆通 深圳水果 申请开始');
    	$done_yto_wh=yto_applyNewBillCodes('SHZSG');
    	$this->log('圆通 嘉兴水果 申请开始');
    	$done_yto_wh=yto_applyNewBillCodes('JXSG');
    	$this->log('圆通 上海水果 申请开始');
    	$done_yto_wh=yto_applyNewBillCodes('SHSG');
    	$this->log('圆通 苏州水果 申请开始');
    	$done_yto_wh=yto_applyNewBillCodes('SZSG');
    	$this->log('圆通 武汉水果 申请开始');
    	$done_yto_wh=yto_applyNewBillCodes('WHSG');
    	*/

    	if($done_zto_cd && $done_zto_sh && $done_sto_dg && $done_zto_szsg && $done_ht_sh && $done_ht_bj && $done_ht_shsg && $done_ht_wlbjc && $done_ht_jxsg){
    		//God is in His Heaven, all is right with the world
    	}else{
    		$body="具体状况：
    		";
    		if(!$done_zto_cd){
    			$body.="中通 成都 无法获取更多面单号。请在储备用完之前采购！
    			";
    		}
    		if(!$done_zto_sh){
    			$body.="中通 上海 无法获取更多面单号。请在储备用完之前采购！
    			";
    		}
    		if(!$done_zto_szsg){
    			$body.="中通 苏州 无法获取更多面单号。请在储备用完之前采购！
    			";
    		}
    		if(!$done_sto_dg){
    			$body.="申通 东莞 无法获取更多面单号。请在储备用完之前采购！
    			";
    		}
    		if(!$done_ht_sh){
    			$body.="汇通 上海 无法获取更多面单号。请在储备用完之前采购！
    			";
    		}
    		if(!$done_ht_bj){
				$body.="汇通 北京 无法获取更多面单号。请在储备用完之前采购！
				";
			}
			if(!$done_ht_shsg){
				$body.="汇通 上海水果 无法获取更多面单号。请在储备用完之前采购！
				";
			}
			if(!$done_ht_wlbjc){
				$body.="汇通 万霖北京 无法获取更多面单号。请在储备用完之前采购！
				";
			}
			if(!$done_ht_jxsg){
				$body.="汇通 嘉兴水果 无法获取更多面单号。请在储备用完之前采购！
				";
			}
    		// $this->sendMail('乐其仓 热敏面单资源调度请求报告', $body);
    		echo $body.PHP_EOL;
    	}

    }

    public function actionWasteStat(){
    	// $hour=date("G");
    	// $minute=date("i");
    	// $hm=$hour*100+$minute;
    	// if(($hm>1952 && $hm<2008) || ($hm>752 && $hm<808)){
	    	$stat_sql="SELECT
				tem.shipping_id,
				tem.branch,
				tem. STATUS,
				count(tem.tracking_number)
			FROM
				ecshop.thermal_express_mailnos tem
			LEFT JOIN romeo.shipment s ON s.TRACKING_NUMBER = tem.tracking_number
			AND s.SHIPMENT_TYPE_ID = tem.shipping_id
			WHERE
				tem.`status` != 'N'
			AND(
				s.TRACKING_NUMBER IS NULL
				OR s.`STATUS` = 'SHIPMENT_CANCELLED'
			)
			GROUP BY
				tem.shipping_id,
				tem.branch,
				tem.`STATUS`";
			$result = $this->getDB()->createCommand($stat_sql)->queryAll();
			if($result && count($result)>0){
				$subject='WASTED Arata Bills - '.date(DATE_RSS);
				$body='The current status of wasted tracking numbers is shown as the followin json: 
				'.json_encode($result). ' 
				'.
				"If you need check, please use the following sql: 
				".$stat_sql."

				All Hail Sinri Edogawa!";
				$this->sendMail($subject, $body);
			}else
			{
				$this->log('No wasted ones...wuso!');
			}
		// }else{
		// 	$this->log('Not near 20:00, do not stat...');
		// }
    }

    public function actionTest(){
    	echo "ALL GREEN";
    	//echo "~".getLocalBranchWithFacilityId('12768420');
    }

    public function actionZTOOrderSubmit($limit=100,$shipping_id=115,$branch=JXSG)
    {
    	$start_time = $this->microtime_float();

    	$sql = "SELECT
			tem.shipping_id,
			tem.tracking_number,
			s.shipment_id
		FROM
			ecshop.thermal_express_mailnos tem
		INNER JOIN romeo.shipment s ON tem.tracking_number = s.tracking_number  
		INNER JOIN ecshop.ecs_order_info oi ON oi.order_id = cast(s.primary_order_id AS UNSIGNED)  
		WHERE
			tem. STATUS = 'Y' AND tem.shipping_id = {$shipping_id} and tem.branch='{$branch}'
		AND oi.order_status = 1
		AND oi.shipping_status IN (8, 1, 2) 
		-- group by s.shipment_id
		LIMIT {$limit}
		"; 
	 	$shipments = $this->getSlave()->createCommand($sql)->queryAll();

	 	$sql_time=$this->microtime_float();

		foreach($shipments as $shipment){
			$this->submitOrder($shipment);
	 	}

	 	$final_time=$this->microtime_float();

	 	$this->log('ActionZTOOrderSubmit_End limit='.$limit.' SQL:'.
	 		number_format(($sql_time-$start_time), 4).'s'.
	 		' RUN:'.number_format(($final_time-$start_time), 4).'s'.
	 		' COUNT:'.count($shipments)
	 	);
    }

	private function submitOrder($shipment){
		$start_time = $this->microtime_float();

		$result=false;

		//中通
		if($shipment['shipping_id'] == 115){
			$return = $this->sendZTOOrder($shipment);
			if($return!="true"){
				$this->log('中通回传发货单失败：'.$return);
				$this->log($shipment);
				error_arata_shipment_mailno($shipment['shipping_id'],$shipment['tracking_number']);
				$result= false;
			}else{
				finish_arata_shipment_mailno($shipment['shipping_id'],$shipment['tracking_number']);
				$result= true;
			}
		}
		else if($shipment['shipping_id'] == 89){
			if(!$this->sendSTOOrder($shipment)){
				$this->log('申通回传发货单失败：');
				$this->log($shipment);
				error_arata_shipment_mailno($shipment['shipping_id'],$shipment['tracking_number']);
				$result= false;
			}else{
			 	finish_arata_shipment_mailno($shipment['shipping_id'],$shipment['tracking_number']);
			 	$result= true;
			}
		}
		else if($shipment['shipping_id'] == 99){
			if(!$this->sendHTOrder($shipment)){
				$this->log('汇通回传发货单失败：');
				$this->log($shipment);
				error_arata_shipment_mailno($shipment['shipping_id'],$shipment['tracking_number']);
				$result= false;
			}else{
			 	finish_arata_shipment_mailno($shipment['shipping_id'],$shipment['tracking_number']);
			 	$result= true;
			}
		}
		else if($shipment['shipping_id'] == 85){
			if(!$this->sendYTOrder($shipment)){
				$this->log('圆通回传发货单失败：');
				$this->log($shipment);
				error_arata_shipment_mailno($shipment['shipping_id'],$shipment['tracking_number']);
				$result= false;
			}else{
			 	finish_arata_shipment_mailno($shipment['shipping_id'],$shipment['tracking_number']);
			 	$result= true;
			}
		}
		
		$this->log('SubmitOrderEnd {'.$shipment['shipping_id'].'} ['.$shipment['tracking_number'].'] time='.number_format(($this->microtime_float()-$start_time), 4).'s result='.($result?'Y':'N'));

		return $result;
	}
	private function sendZTOOrder($shipment){
		/* //Ready to use zto_order_submit_simple(SID) for simplification
		$sender_name =  "乐其";
		$sender_city = "上海市,上海市,青浦区";
		$sender_address = "青浦城区已验视";
		
		$receiver_name = $shipment['consignee'];
		$receiver_city = $shipment['province'].','.$shipment['city'].','.$shipment['region'];
		$receiver_address = $shipment['address'];
//回传可以根据订单自动选仓库所在的site
		$result = zto_order_submit($shipment['shipment_id'],$shipment['tracking_number'],
					$sender_name,$sender_city,$sender_address,
					$receiver_name,$receiver_city,$receiver_address);
		*/
		$result=zto_order_submit_simple($shipment['shipment_id']);
		
		if($result->result){
			return "true";
		}else{
			return $result->remark;
		}
	}
	private function sendSTOOrder($shipment){
		return report_sto_arata_used_shipment($shipment['shipment_id']);
	}
	private function sendHTOrder($shipment){
		return ht_reportPrintedBillInfo($shipment['shipment_id']);
	}
	private function sendYTOrder($shipment){
		return yto_reportPrintedBillInfo($shipment['shipment_id']);
	}
	
	/**
	 * 获取中通大头笔信息
	 */
	public function actionZTOOrderBigPen(){
		
		$sql = "select * from ecshop.ecs_region_mark  ";
		$region_names = $this->getDB()->createCommand($sql)->queryAll();
		foreach($region_names as $receive){
			$mark_id = $receive['mark_id'];
			if(in_array($receive['province_id'],array('2','3','10','23'))){
				$receive['district_name'] = $receive['city_name'];
				$receive['city_name'] = $receive['province_name'];
			}
			if($receive['district_name'] =='其他区'){
				$receive['district_name']='';
			}
			$receive['branch'] ='SH'; //接口测试显示：发货仓与大头笔最终反馈结果无相关性
			$bigPen = get_zto_bigPen($receive);
			if($bigPen!='false'){
				$sql = "update ecshop.ecs_region_mark set zto_mark_name='{$bigPen}',update_time = now() where mark_id = {$mark_id} ";
				$this->getDB()->createCommand($sql)->execute();
			}
		}
	} 
	
	
	public function actionZTOOrderSingleMark(){
		$sql = "select oi.order_id,oi.facility_id,r1.region_name as province_name,r2.region_name as city_name,r3.region_name as district_name,oi.address
			 from ecshop.ecs_order_info oi
			 inner join romeo.facility_shipping pfs on pfs.facility_id = oi.facility_id and pfs.shipping_id = oi.shipping_id and pfs.is_delete=0 
			 left join ecshop.order_attribute oa on oa.order_id = oi.order_id and oa.attr_name='ztoBigPen' 
			 left join ecshop.ecs_region r1 on r1.region_id = oi.province 
			 left join ecshop.ecs_region r2 on r2.region_id = oi.city
			 left join ecshop.ecs_region r3 on r3.region_id = oi.district 
			 where oi.shipping_id = 115 and oi.order_type_id in ('SALE','RMA_EXCHANGE') AND oi.order_time > date_sub(NOW(),interval 1 day) 
			 and oa.attribute_id is null limit 2000 ";
		$orderSingleInfo = $this->getDB()->createCommand($sql)->queryAll();
		print_r("begin get zto mark count:".count($orderSingleInfo));
		foreach($orderSingleInfo as $orderSingle){
			get_zto_mark_single_order($orderSingle);
		}
		print_r("end get zto mark count:".count($orderSingleInfo));
	}

     /**
	 * 取得master数据库连接
	 * 
	 * @return CDbConnection
	 */ 
    protected function getDB()
    {
        if(!$this->db)
        {
            $this->db=Yii::app()->getDb();
            $this->db->setActive(true);
        }
        return $this->db;
    }
    public function getOneBySql($sql,$columnName){
		$results = $this->getDB()->createCommand($sql)->queryAll();
		if(empty($results)){
			return null;
		}else{
			return $results[0][$columnName];
		}
	}
  	protected function getSoapClient()
	{
		if(!$this->soapclient)
		{
			$this->soapclient = Yii::app()->getComponent('romeo')->InventoryService;
		}
		return $this->soapclient;
	}

	private function microtime_float()
	{
	    list($usec, $sec) = explode(" ", microtime());
	    return ((float)$usec + (float)$sec);
	}

	private function log($m) {
		if(is_array($m) || is_object($m)){
			print date("Y-m-d H:i:s") . " " . $m . PHP_EOL;
			// foreach ($m as $key => $value) {
			// 	print "Array[".$key."]=".$value." \r\n";
			// }
			print "Json of mono is " . json_encode($m) . PHP_EOL;
		}else{
			print date("Y-m-d H:i:s") . " " . $m . PHP_EOL;
		}
	}

	protected function sendMail($subject, $body, $path=null, $file_name=null) {
        require_once(ROOT_PATH. 'includes/helper/mail.php');
        $mail=Helper_Mail::smtp();
        $mail->IsSMTP();                 // 启用SMTP
        $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
        $mail->SMTPAuth = true;         //启用smtp认证
        $mail->Username =$GLOBALS['emailUsername'];   // 你的邮箱地址
        $mail->Password = $GLOBALS['emailPassword'];      //你的邮箱密码  */
        $mail->CharSet='UTF-8';
        $mail->Subject=$subject;
        $mail->SetFrom('erp-report@leqee.com', '乐其网络科技');
        $mail->AddAddress('ljni@leqee.com', '倪李俊');
        $mail->AddAddress('jche@leqee.com', '何建成');
        $mail->AddAddress('ytchen@leqee.com', '陈艳婷');
        $mail->Body = date("Y-m-d H:i:s") . " " . $body;
        if($path != null && $file_name != null){
            $mail->AddAttachment($path, $file_name);
        }
        try {
        	//TEST
        	// $mail->Username='ljni@leqee.com';
        	// $mail->Password='(╯‵□′)╯︵┻━┻';


            if ($mail->Send()) {
                // LogRecord('mail send sucess ');
                $this->log('mail send sucess ');
            } else {
                // LogRecord('mail send fail ');
                $this->log('mail send fail ');
            }
        } catch(Exception $e) {
        	$this->log('发邮件有情况发送 '.$e);
            // 屏蔽PHP邮箱 版本错误
            //Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475  Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1471 Deprecated: Function set_magic_quotes_runtime() is deprecated in /mnt/hgfs/www/erpbrand/includes/phpmailer/class.phpmailer.php on line 1475 
        }
    }
}
?>