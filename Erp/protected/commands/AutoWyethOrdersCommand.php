<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
register_shutdown_function('ShutdownHandler');
require_once ROOT_PATH . 'includes/helper/mail.php';
ini_set('memory_limit','512M');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'data/master_config.php';
require_once(ROOT_PATH . 'includes/helper/array.php');

Yii::import('application.commands.LockedCommand', true);


	
class AutoWyethOrdersCommand extends CConsoleCommand{	
	/*
	 * 日志打印  update 2015-07-07 by hzhang1
	 */
	private function log ($m) {
		print $m . "\r\n";
	}

	/*
	 * 向中间表插入记录失败时发送邮件 updated 2015-07-07 by hzhang1
	 */
	public function sendMail($subject, $body = null, $path = null, $file_name = null) {
		$mail=Helper_Mail::smtp();
		$mail->IsSMTP();                 // 启用SMTP
	    $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
	    $mail->SMTPAuth = true;         //启用smtp认证
	    $mail->Username =$GLOBALS['emailUsername'];   // 你的邮箱地址
	    $mail->Password = $GLOBALS['emailPassword'];      //你的邮箱密码  */
	    $mail->CharSet='UTF-8';
		$mail->Subject="【Wyeth CRM Generate Orders】" . $subject;
		$mail->SetFrom($GLOBALS['emailUsername'], '乐其网络科技');
		$mail->AddAddress('hzhang1@leqee.com', '张欢');
		$mail->AddAddress('zjli@leqee.com', '李志杰');

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
		}
	}

	
	/*
	 * 修改三个地方，ditributor表线上是在ecshop中，不是在romeo表中，update 2015-07-07 by hzhang1
	 * 查询操作在slave数据库上进行，写入数据库操作在master数据库上进行
	 */
	public function actionGeneralWyethOrders($start_time=null,$end_time=null)
	{
		 //register_shutdown_function(array(AutoWyethOrdersCommand,'ShutdownHandler')); 
		 //register_shutdown_function('ShutdownHandler');
		 $db = Yii::app()->getDb();
		 $slave_db = Yii::app()->getComponent('slave');
		 $start_time = $start_time==null ? date('Y-m-d', strtotime("-2 day")) : $start_time;
		 $end_time = $end_time==null ? date("Y-m-d", time()) : $end_time;
		 echo "start_time:".$start_time;
		 echo "\n";
		 echo "end_time:".$end_time;
		 echo "\n";
		 echo "====================================\n";
		 
			 $date1 = strtotime($start_time);
			 $date2 = strtotime($end_time);
			 $datex = $date1;
			 while ( $datex < $date2) {
			 	if(($date2-$datex)>=86400){
				   $d_time1=date('Y-m-d H:i:s',$datex);
				   $datex = strtotime('+1 day',$datex);
				   $d_time2=date('Y-m-d H:i:s',$datex);
				   $start_time=$d_time1;
				   $end_time=$d_time2;
			 	}else if(($date2-$datex)<86400){
				 		 $d_time1=date('Y-m-d H:i:s',$datex);
					  	 $datex = strtotime('+1 hour',$datex);
					  	 if($datex>$date2){
					  	 	$d_time2=date('Y-m-d H:i:s',$date2);
						     $start_time=$d_time1;
						  	 $end_time=$d_time2;
					  	 }else{
						  	 $d_time2=date('Y-m-d H:i:s',$datex);
						     $start_time=$d_time1;
						  	 $end_time=$d_time2;
					  	 }
			 	}
		 	echo "[".$start_time."~".$end_time."]===>start\n";
		 	
		 	   $sql="SELECT 
				eog.rec_id,
				oi.order_sn,
				d.name,
				ifnull(oa.attr_value, '') taobao_user_id,
				oi.order_time, 
				cast(case oi.order_status
					when 0 then _utf8'未确认'
					when 1 then _utf8'已确认'
					when 2 then _utf8'已取消'
					when 4 then _utf8'已拒收'
					else oi.order_status
				end as char(64)) as order_status, 
				cast(case d.name when '惠氏金宝宝旗舰店' then _utf8'天猫金装' 
					 when '启赋官方旗舰店' then _utf8'天猫启赋'
					 else _utf8'其他' end as char(64)) as shop_name,
				oi.order_amount,
				oi.goods_amount,
				ec.cat_name,
				eog.goods_name,
				oi.shipping_fee, 
				oi.taobao_order_sn,
				if(g.barcode='','',g.barcode) g_barcode,
				gs.barcode s_barcode,
				eog.goods_price,
				eog.goods_number,			
				cast(eog.goods_price*if(oi.goods_amount=0,0,(oi.goods_amount + oi.bonus)/oi.goods_amount) as decimal(15,2)) sale_price,
				cast(eog.goods_number * eog.goods_price*if(oi.goods_amount=0,0,(oi.goods_amount + oi.bonus)/oi.goods_amount) as decimal(15,2)) sale_amount,
				IFNULL(pr.region_name, '') province,
				IFNULL(ci.region_name, '') city,
				s.shipping_name,
				max(eoa.action_time) action_time
				FROM ecshop.ecs_order_info oi
				LEFT JOIN romeo.party p ON convert(oi.party_id using utf8) = p.PARTY_ID
				LEFT JOIN ecshop.order_attribute oa on oi.order_id = oa.order_id and oa.attr_name = 'TAOBAO_USER_ID'
				LEFT JOIN romeo.facility f on oi.facility_id = f.facility_id
				LEFT JOIN ecshop.distributor d on oi.distributor_id = d.distributor_id
				left join ecshop.ecs_region pr on oi.province = pr.region_id
				left join ecshop.ecs_region ci on oi.city = ci.region_id
				left join ecshop.ecs_shipping s on oi.shipping_id=s.shipping_id
				inner join ecshop.ecs_order_goods eog on eog.order_id = oi.order_id
				left join ecshop.ecs_goods g on g.goods_id = eog.goods_id
				left join ecshop.ecs_category ec on g.cat_id = ec.cat_id 
				left join ecshop.ecs_goods_style gs on gs.goods_id = eog.goods_id and gs.style_id = eog.style_id and gs.is_delete=0
				left join ecshop.ecs_order_action eoa on eoa.order_id = oi.order_id  and eoa.action_time >= '$start_time' and  eoa.action_time <'$end_time'
				WHERE 
					-- oi.order_time >= '2015-02-01'
					-- AND oi.order_time < '2015-02-28' AND 
					oi.party_id = '65617'
					
					AND oi.order_type_id = 'SALE'
					-- AND oi.distributor_id in (2088,2071)
					AND ec.cat_name not like '%耗材%' and ec.cat_name != '虚拟商品'
				GROUP BY eog.rec_id
				having action_time  >= '$start_time' and action_time < '$end_time' ";
			
    		$orderItemList=$slave_db->createCommand($sql)->queryAll();
    		$index=1;
    		$record_id="";
    		try{
    			foreach ($orderItemList as $orderItem) {
	    			$insert_sql="replace into ecshop.brand_wyeth_order_info(
						rec_id,order_sn,distributor_name,taobao_user_id,order_time,order_status,order_amount,goods_amount,cat_name,goods_name,shipping_fee,
						taobao_order_sn,g_barcode,s_barcode,goods_price,goods_number,sale_price,sale_amount,province,city,shipping_name,action_time,platname
					)values('".$orderItem['rec_id']."','".$orderItem['order_sn']."','".$orderItem['name']."','".
							$orderItem['taobao_user_id']."','".$orderItem['order_time']."','".$orderItem['order_status']."','".
							$orderItem['order_amount']."','".$orderItem['goods_amount']."','".$orderItem['cat_name']."','".$orderItem['goods_name']."','".
							$orderItem['shipping_fee']."','".$orderItem['taobao_order_sn']."','".$orderItem['g_barcode']."','".$orderItem['s_barcode']."','".
							$orderItem['goods_price']."','".$orderItem['goods_number']."','".$orderItem['sale_price']."','".$orderItem['sale_amount']."','".
							$orderItem['province']."','".$orderItem['city']."','".$orderItem['shipping_name']."','".$orderItem['action_time']."','".$orderItem['shop_name']."')";
					
					$record_id=$orderItem['rec_id'];
		    		$result = $db->createCommand($insert_sql)->execute();
	    			echo ">>>Insert ".$index."th record.The rec_id=".$record_id."\n";
		    		$index +=1;
	    		}
	    		$this->log(">>>Total selection:".($index-1).",Total insertion:".($index-1).",Operation Successful...");
    		}catch(Exception $e){
    			$index +=1;
    			$this->log('\n Insert exception: ' . $e->getMessage());
    			$this->sendMail("Wyeth insert Error ", "\n>>>第".$index."条记录插入出错！\n>>>出错记录的rec_id=".$record_id);
    		}
    		echo "====================================\n";
			 }
	}//函数结束
}

class Mail{
	 public function sendMail($subject, $body, $path=null, $file_name=null) {
	 	require_once ROOT_PATH . 'admin/includes/init.php';
        require_once(ROOT_PATH. 'includes/helper/mail.php');
        $mail=Helper_Mail::smtp();
        $mail->IsSMTP();                 // 启用SMTP
        $mail->Host="smtp.exmail.qq.com";      //smtp服务器 以126邮箱为例子
        $mail->SMTPAuth = true;         //启用smtp认证
        $mail->Username =$GLOBALS['emailUsername'];   // 你的邮箱地址
        $mail->Password = $GLOBALS['emailPassword'];      //你的邮箱密码  */
        $mail->CharSet='UTF-8';
        $mail->Subject=$subject;
        $mail->SetFrom($GLOBALS['emailUsername'], '乐其网络科技');
        $mail->AddAddress('zjli@leqee.com', '李志杰');
        $mail->AddAddress('hzhang1@leqee.com', '张欢');
        $mail->Body = date("Y-m-d H:i:s") . " " . $body;
        if($path != null && $file_name != null){
            $mail->AddAttachment($path, $file_name);
        }
        try {
        	$mail->Send();
        } catch(Exception $e) {
        }
    }
}

function ShutdownHandler(){
	if ($e = error_get_last())
	{
		ini_set('memory_limit','10M');
		echo "=====Memory Exhausted Get======".PHP_EOL;
	    print_r($e);  
	    $mail = new Mail();
	    $mail->sendMail("【ERROR】Wyeth Memory Exhausted Error ", "惠氏CRM查询数据过大内存溢出，查询失败！【建议缩小查询时间段手动调度】\n");
		echo "=====Memory Exhausted Send Mail Success======".PHP_EOL;
	}
}
?>
