<?php
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');

//require_once(ROOT_PATH . 'includes/helper/array.php');
//Yii::import('application.commands.LockedCommand', true);
/*
 * Created on 2013-9-23
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
class VirtualDeliverInventoryCommand extends CConsoleCommand
{
	private $db;  // db数据库
	private $soapclient;
	private $prepaySoapclient;
	public $actionUser = 'cronjob';
	public $party_mode = 2;
	public $orderStatus = 11;
	public function actionIndex()
    {
        $currentTime=microtime(true);
        // 商品同步
        $this->run(array('Deliver'));
    }
    //外包仓  92718101 外包仓的facility_id
    public function actionDeliver($type=0,$monthFor3=1)
    {
    	if($type==0){
    		echo("[". date('c'). "] TYPE_ZERO_GO".PHP_EOL);

    		$start_time=microtime(true);
    		$sum=0;

    		//筛选 已经批拣的热敏外包仓订单 外包发货
		    $sql = "SELECT os.order_id,bp.party_id
					from romeo.out_batch_pick bp 
					inner join romeo.out_batch_pick_mapping bpm on bp.batch_pick_id = bpm.batch_pick_id
					inner join romeo.order_shipment os on os.shipment_id = bpm.shipment_id
					inner join romeo.shipment s on os.shipment_id = s.shipment_id
	                inner join romeo.order_inv_reserved ir on cast(os.order_id as unsigned) = ir.order_id 
					where bp.check_status = 'F' and s.STATUS = 'SHIPMENT_INPUT' and ir.status = 'Y' and bpm.created_stamp > date_sub(NOW(),interval 1 day)
					LIMIT 2000 -- added by Sinri
		 	".' -- VDI '.__LINE__.PHP_EOL;
		 	$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);

		 	$sum+=count($orderIds);
			
	        $sql = "SELECT os.order_id,bp.party_id
					from romeo.out_batch_pick bp 
					inner join romeo.out_batch_pick_mapping bpm on bp.batch_pick_id = bpm.batch_pick_id
					inner join romeo.order_shipment os on os.shipment_id = bpm.shipment_id
					inner join romeo.shipment s on os.shipment_id = s.shipment_id 
					inner join ecshop.ecs_order_info oi on os.order_id = oi.order_id 
					inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id and ir.status = 'Y'
					where bp.check_status = 'F'    and s.STATUS = 'SHIPMENT_SHIPPED'  and oi.shipping_status = 0 and bpm.created_stamp  > date_sub(NOW(),interval 1 day) 
					LIMIT 2000 -- added by Sinri
					".' -- VDI '.__LINE__.PHP_EOL;
		 	$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);

		 	$sum+=count($orderIds);
		 	
	
		 	//所有快递都是只要打标，就会虚拟出入库(不能保证一个月前因没有库存导致出库失败的订单再次出库，调度每5分钟一次)
			$sql = "SELECT outo.order_id,t.party_id
					from ecshop.ecs_out_ship_order_task t 
					inner join ecshop.ecs_out_ship_order outo on t.task_id = outo.task_id
					inner join ecshop.ecs_order_info oi on outo.order_id = oi.order_id
					inner join romeo.order_inv_reserved ir on ir.order_id = oi.order_id and ir.status = 'Y'
					where oi.shipping_status=0 and outo.create_time > date_sub(NOW(),interval 1 month)
					LIMIT 2000 -- added by Sinri
				 ".' -- VDI '.__LINE__.PHP_EOL;
		 	$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);

		 	$sum+=count($orderIds);

		 	$end_time=microtime(true);

		 	echo("[". date('c'). "] TYPE_ZERO_OVER {$sum} orders with ".($end_time-$start_time).' seconds'.PHP_EOL);

    	}elseif($type==1){
    		//订单状态为11，并且在4个外包仓的，预定成功的，自动出库
		 	$sql = "SELECT  
		 			oi.order_id,oi.party_id
				from  ecshop.ecs_order_info oi
				    inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id
				where  oi.order_status = 11 and oi.shipping_status = 0 and oi.facility_id in ('119603093', '92718101','119603091','119603092','149849257') and ir.status = 'Y'
				      limit 500
		 	  ".' -- VDI '.__LINE__.PHP_EOL;
		 	$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);
    	}elseif($type==2){
    		//保税仓订单自动出库
    		define('IN_ECS', true);
    		require_once ROOT_PATH.'admin/bwshop/lib_bw_party.php';
			$bw_parties_sql="'".implode("','", BWPartyAgent::getAllBWParties())."'";
			$bw_facility_id=BWPartyAgent::getBWFacilityId();
	   		
	   		$sql = "SELECT oi.order_id,oi.party_id
	   			from ecshop.bw_order_info boi
	   			inner join ecshop.ecs_order_info oi  on oi.taobao_order_sn = boi.outer_order_sn 
	   			inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id 
	   			inner join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8) 
	   			where oi.shipping_status = 0 and oi.order_status = 1 and boi.apply_status='ACCEPT' and oi.facility_id in ('149849262','181093103','19568549')
	   				and boi.shipping_status='24' and boi.tracking_number is not null  and ir.status='Y' 
	   				and ir.party_id in ($bw_parties_sql)
	   			limit 500
	   		".' -- VDI '.__LINE__.PHP_EOL;
	   		$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);	
		 	$this->updateTrackingNumber($orderIds);	
    	}elseif($type==3){
    		$sql = "select party_id from romeo.party where parent_party_id = 65658 and is_leaf='Y' and SYSTEM_MODE = 2 and STATUS = 'ok'";
    		$party_ids = $this->getDB()->createCommand($sql)->queryColumn();
    		//跨境电商 虚拟发货2：杭州菜鸟仓,杭州空港仓,上海菜鸟仓,宁波百世仓,嘉里线下仓,嘉里菜鸟仓,香港GPN直邮仓,香港中外运直邮仓,广州白云机场保税仓,跨境品牌直邮仓,跨境菜鸟集货仓
    		//订单已确认已付款已预订并已存在面单信息即可操作出库发货
		 	$sql = "SELECT oi.order_id,oi.party_id 
		 		from ecshop.ecs_order_info oi FORCE index (order_info_multi_index)
		 		inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id		 
		 		inner join romeo.shipment s on s.primary_order_id = convert(oi.order_id using utf8)	 
		 		where oi.party_id in (".implode(",",$party_ids).") and oi.order_time >date_sub(NOW(),interval {$monthFor3} month) -- from 3 changed to 1 month, by Sinri 151126
		 			and oi.facility_id in ('178036539','181093103','181093102','184099370','185963143','185963144','231292324','237191026','222187981','237191027','251234673')  and s.tracking_number is not null 
		 			and oi.shipping_status = 0 and oi.order_status = 1 and (ir.status='Y' or ir.status='F')
		 		limit 500	".' -- VDI '.__LINE__.PHP_EOL;
	   		$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);	
		 	
		 	//跨境电商 虚拟发货3：香港中外运仓,香港宝易拢仓,香港GPN仓订单,跨境品牌直货仓 已确认已付款已预订 即可操作出库发货（不需要面单）
		 	$sql = "SELECT oi.order_id,oi.party_id 
		 		from ecshop.ecs_order_info oi 	 
		 		inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id		 
		 		inner join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8)	 
		 		inner join romeo.shipment s on s.shipment_id = os.shipment_id		 
		 		where oi.party_id in (".implode(",",$party_ids).") and oi.facility_id in ('224734293','184099369','227708742','227708743') 
		 			and oi.order_time >date_sub(NOW(),interval {$monthFor3} month)
		 			and oi.shipping_status = 0 and oi.order_status = 1 and ir.status='Y' 
		 		limit 500	".' -- VDI '.__LINE__.PHP_EOL;
	   		$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);
    	}elseif($type==4){
    		//百威英博：成都越海龙泉仓,华南花都云时仓,华东奉贤云时仓,华北空港云时仓 订单 已确认已付款已预订并已存在面单信息即可操作出库发货
		 	$sql = "SELECT oi.order_id,oi.party_id 
		 		from ecshop.ecs_order_info oi 	 
		 		inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id		 
		 		inner join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8)	 
		 		inner join romeo.shipment s on s.shipment_id = os.shipment_id		 
		 		where oi.party_id = 65614 and oi.order_time >date_sub(NOW(),interval 1 month)		 
		 			and oi.facility_id in ('224734292','149849263','149849264','149849265','149849266')  and s.tracking_number is not null 
		 			and oi.shipping_status = 0 and oi.order_status = 1 and ir.status='Y' and oi.distributor_id = 2524
		 		limit 500	".' -- VDI '.__LINE__.PHP_EOL;
	   		$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);	
    	}elseif($type==5){
    		/*
    		 * 品牌商直配仓
    		 * 设定虚拟出库的条件：
				1、品牌直配仓
				2、订单状态：已确认、已付款、已预订
				3、不需要导入面单
    		 */ 
    		
    		$sql = "select oi.order_id,oi.party_id 
				 from ecshop.ecs_order_info oi 
				 inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id 
				 where oi.facility_id = '100170591' and oi.order_status=1 and oi.pay_status = 2 
				 and oi.shipping_status=0 and ir.status = 'Y' and oi.order_time >date_sub(NOW(),interval 1 month) ";
			$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);	
    	}elseif($type==6){ 
    		// 乐其自采国内 虚拟发货：广州白云机场保税仓
    		//订单已确认已付款已预订并已存在面单信息即可操作出库发货
		 	$sql = "SELECT oi.order_id,oi.party_id 
		 		from ecshop.ecs_order_info oi  
		 		inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id		 
		 		inner join romeo.shipment s on s.primary_order_id = convert(oi.order_id using utf8)	 
		 		where oi.party_id =65656 and oi.order_time >date_sub(NOW(),interval 1 month)  
		 			and oi.facility_id ='237191026'  and s.tracking_number is not null 
		 			and oi.shipping_status = 0 and oi.order_status = 1 and (ir.status='Y' or ir.status='F')
		 		limit 500	".' -- VDI '.__LINE__.PHP_EOL;
	   		$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		 	$this->deliverOrderParty($orderIds);	 
    		
    	}
	    
    }
    
    
    /*
     * 为避免其他虚拟出库的影响，菜鸟的虚拟出库单独写一个调度
     */
    public function actionDeliverExpressBird()
    {
    	 	$lock_name = "DeliverExpressBird";
		    $lock_file_name = $this->get_file_lock_path($lock_name, 'virtual_deliver');
		    $lock_file_point = fopen($lock_file_name, "w+");
		    $would_block = false;
		    if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
			 	//上传到菜鸟且已发货的订单，预定成功的，且ERP中订单状态为“未发货”，ERP中需要虚拟出库
			 	$sql = "SELECT 
					oi.order_id,oi.party_id 
					FROM ecshop.express_bird_indicate ebi
					inner JOIN ecshop.ecs_order_info oi on oi.taobao_order_sn is not null and ebi.out_biz_code=if(oi.order_type_id ='SALE',oi.taobao_order_sn, CONCAT(oi.taobao_order_sn,'-h'))
					inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id
					where ebi.logistics_status='已发货' and ir.status = 'Y' and oi.shipping_status=0
					and oi.order_type_id in ('SALE','RMA_EXCHANGE') 
					and oi.party_id in (65614,65558,65632,65553)  and oi.order_time > date_sub(NOW(),interval 1 month)
					and ir.facility_id in ('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265')
				".' -- VDI '.__LINE__.PHP_EOL; 
				
			 	$orderIds = $this->getDB()->createCommand($sql)->queryAll();
			 	$this->deliverOrderParty($orderIds);
			 	$this->updateTrackingNumber($orderIds);
			 	
			 	flock($lock_file_point, LOCK_UN);
        		fclose($lock_file_point);
		 	}else{
			    	fclose($lock_file_point);
	    			echo("同业务组有人正在完结，请稍后");
			}
    }
    
    /*
     * 乐其跨境的菜鸟虚拟出库（临时使用）
     */
    public function actionDeliverExpressKuajingBird()
    {
    		//天猫流转菜鸟仓库已经发货的订单，且ERP中订单状态为“未发货”，已上传发货单，ERP中需要虚拟出库
    		$sql = "select distinct(o.order_id),o.order_time,i.INVENTORY_ITEM_DETAIL_ID
					from ecshop.sync_taobao_order_info s 
					left join ecshop.ecs_order_info o on s.tid=o.taobao_order_sn
					left join romeo.inventory_item_detail i on i.ORDER_ID = convert(o.order_id using utf8)
					where o.shipping_status=1 
						and o.order_time>'2016-04-01 09:00' and o.order_time<'2016-04-08' and i.INVENTORY_ITEM_DETAIL_ID is null
						and s.application_key in 
    					('3abb1df4b866102ea5bd003048df78e2','5bf19b786039103289d9003048df78e2','8e54c07642a710339950003048df78e2',
						'dd2f9bfafc53102fa5bd003048df78e2','eb719f8207a6103289d9003048df78e2','ddc350e42d56103289d9003048df78e2',
						'4a8111d07581102eab6d001d0907b999','5d2e925476a210339950003048df78e2','98d25cfabeba103289d9003048df78e2',
						'f6a3d0d0395110348080003048df78e2','a4ab91b2a0f11033b6f2003048df78e2','428454387f7a10309a21003048df78e2')
    				".' -- VDI '.__LINE__.PHP_EOL;
    		
			$orderIds = $this->getDB()->createCommand($sql)->queryAll();
    		$this->deliverOrderParty($orderIds);
    }										
    
    /*
     * 跨境购虚拟出库写的一个调度 by hzhang1 2015-07-27
     */
    public function actionDeliverKuajinggou(){
    	//上传到跨境购平台的，且中海贸已发货的订单，由宁波保达仓库使用中海贸系统发货的，预定成功的，且ERP中订单状态为“未发货”，ERP中需要虚拟出库
	 	//正式代码
	 	$sql = "SELECT 
	 				oi.order_id,oi.party_id 
	 			FROM ecshop.sync_kjg_order_status os
					inner JOIN ecshop.ecs_order_info oi on os.taobao_order_sn=if(oi.order_type_id ='SALE',oi.taobao_order_sn, CONCAT(oi.taobao_order_sn,'-h'))
					inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id
				where os.status='海关货物放行' and oi.facility_id in ('222187982','247410612') and ir.status = 'Y' and shipping_status=0
					and oi.order_type_id in ('SALE') and os.logistics_no != ''
		".' -- VDI '.__LINE__.PHP_EOL; 
	 	$orderIds = $this->getDB()->createCommand($sql)->queryAll();
	 	$this->deliverOrderParty($orderIds);
	 	$this->updateTrackingNumber($orderIds);

    }
    
    
    /*
     * 订单虚拟出库之后，将从各个对接的发货系统获取的运单号信息写回ERP中
     */
    public function  updateTrackingNumber($orderIds){
    	
    	foreach($orderIds as $ordernew){
    	 	 $sql="SELECT
				      os.shipment_id,oi.order_id,oi.party_id, oi.distributor_id,
			          oi.carrier_bill_id,oi.order_status,oi.shipping_status,oi.pay_status			          			       
				   FROM
				      ecshop.ecs_order_info oi
				      left join romeo.order_shipment os ON os.order_id = convert(oi.order_id using utf8)
				      left join romeo.shipment s ON os.shipment_id = s.shipment_id
				   where oi.order_id={$ordernew['order_id']} limit 1
			  ".' -- VDI '.__LINE__.PHP_EOL;
			  $order=$this->getDB()->createCommand($sql)->queryAll();
			   
			  $tracking_number='';
			  $shipping_name = '';
			 
			  $tracking_numbers = array();
			  $shipping_names = array();
			  
			  //跨境电商下面的所有业务组
			  $sql_parties = "select party_id from romeo.party where PARENT_PARTY_ID = '65658'".' -- VDI '.__LINE__.PHP_EOL;
			  $parties=$this->getDB()->createCommand($sql_parties)->queryColumn();
			  
			  //百威英博从express_bird_actual表获取运单号和快递方式
			  //百威业务组下以下店铺走菜鸟物流发货：百威英博官方旗舰店，百威啤酒官方旗舰店    桂格业务组走菜鸟物流发货：quaker桂格旗舰店
			  // 金佰利业务组织下只有以下店铺需加取消订单判断：好奇官方旗舰店
			  if( ($order[0]['party_id']=='65614' && ($order[0]['distributor_id']=='1921' || $order[0]['distributor_id']=='2524'))
			  		|| ( $order[0]['party_id']=='65558' && $order[0]['distributor_id']=='317' )
			  		||  ( $order[0]['party_id']=='65632' && $order[0]['distributor_id']=='2507' )   
			  		|| ($order[0]['party_id']=='65553' && $order[0]['distributor_id']=='177')){
			  	 $sql1 = "SELECT 
			  			eba.logistic_code,eba.company_code
					FROM
						ecshop.express_bird_actual eba
						inner join ecshop.express_bird_indicate ebi on eba.order_code=ebi.order_code
						inner JOIN ecshop.ecs_order_info oi on ebi.out_biz_code=if(oi.order_type_id ='SALE',oi.taobao_order_sn, CONCAT(oi.taobao_order_sn,'-h'))
					where oi.order_type_id in ('SALE','RMA_EXCHANGE') and oi.order_id='{$order[0]['order_id']}' limit 1
					".' -- VDI '.__LINE__.PHP_EOL;
					$track=$this->getDB()->createCommand($sql1)->queryAll();	
					$tracking_number=$track[0]['logistic_code'];
					$shipping_name = $track[0]['company_code'];
					
					$tracking_numbers = explode("," , $tracking_number) ;
					$shipping_names = explode("," , $shipping_name ) ;
					
			  }else if($order[0]['party_id']=='65638' || in_array($order[0]['party_id'],$parties)){
			  	//保税仓订单,再次确认订单状态为“已发货”
				  	$sql2 = "SELECT boi.tracking_number,boi.shipping_id 
					  	from ecshop.ecs_order_info oi 
					  	inner join ecshop.bw_order_info boi on oi.taobao_order_sn = boi.outer_order_sn 
					  	where oi.order_id = '{$order[0]['order_id']}' and oi.shipping_status = 1 and oi.order_status = 1  
					".' -- VDI '.__LINE__.PHP_EOL; 
				  	$track=$this->getDB()->createCommand($sql2)->queryAll();	
				  	if(!empty($track)){
						$tracking_number=$track[0]['tracking_number'];
						$shipping_id = $track[0]['shipping_id'];
						if($shipping_id == 0 || $shipping_id == 2){
							$shipping_name = "邮政国内小包";
						}else if($shipping_id == 1){
							$shipping_name = "顺丰快递";
						}else{
							$shipping_name = "中通快递";
						}
				  	}
					
				  	//跨境购订单从sync_kjg_order_status表获取运单号和快递方式
				  	 $sql1 = "SELECT 
				  			os.logistics_no, oi.shipping_name
						FROM
							ecshop.sync_kjg_order_status os
							inner JOIN ecshop.ecs_order_info oi on os.taobao_order_sn=oi.taobao_order_sn
						where oi.order_id='{$order[0]['order_id']}' and os.status = '海关货物放行' limit 1 ".' -- VDI '.__LINE__.PHP_EOL;
					$track=$this->getDB()->createCommand($sql1)->queryAll();	
					if(!empty($track)){
						$tracking_number=$track[0]['logistics_no'];
						$shipping_name = $track[0]['shipping_name'];
					}
					
					$tracking_numbers[0] = $tracking_number;
					$shipping_names[0] = $shipping_name ;
			  }
			 
			 
			 
			 //追加菜鸟面单号（适应多面单的情况）
			 
			 
			 $size =  min( count($shipping_names)  , count( $tracking_numbers)) ;  //取出两个数组较小的那个，避免数组越界
			 for ($i = 0; $i < $size; $i++) {
    			
    			$sql = "SELECT shipping_id,default_carrier_id from ecshop.ecs_shipping where shipping_name='{$shipping_names[$i]}' limit 1";
				$shipping_id= '' ;
				$carrier_id = '';
				$shipping=$this->getDB()->createCommand($sql)->queryAll();
				if(!empty($shipping)){
					$shipping_id=$shipping[0]['shipping_id'];
				 	$carrier_id = $shipping[0]['default_carrier_id'];
				}
				echo "carrier_id:" .$carrier_id. " - " .$shipping_id ;
				 
		    	if($i == 0){   
			    	//修改ecs_order_info表中的快递方式和快递名称 （只需要修改一次）
			    	$sql = "update ecshop.ecs_order_info set shipping_time=UNIX_TIMESTAMP(), shipping_id='{$shipping_id}', shipping_name='{$shipping_names[$i]}' where order_id='{$order[0]['order_id']}' limit 1";
					$update=$this->getDB()->createCommand($sql)->execute();
					//修改shipment表中的快递方式和运单号	
					$sql = "update romeo.shipment set shipment_type_id='{$shipping_id}',CARRIER_ID = '{$carrier_id}',tracking_number='{$tracking_numbers[$i]}'  where shipment_id='{$order[0]['shipment_id']}' limit 1";
					$update=$this->getDB()->createCommand($sql)->execute();
					
					// killed by Sinri 20160105
					// $sql=sprintf("UPDATE ecshop.ecs_carrier_bill SET bill_no = '%s',carrier_id='%d' WHERE bill_id = '%d' LIMIT 1",
					  				// $tracking_number,$carrier_id,$order[0]['carrier_bill_id']);
					// $update=$this->getDB()->createCommand($sql)->execute();
		    	}else{//针对多面单追加面单
					try{
	        			$handle=soap_get_client('ShipmentService','ERPSYNC');
	        			$object=$handle->createShipment(array(
	        		        'orderId' => $order[0]['order_id'],
	        				'partyId' => $order[0]['party_id'],
	        				'shipmentTypeId'=>$shipping_id ,
	        				'carrierId'=> $carrier_id,
	        				'trackingNumber'=>$tracking_numbers[$i] ,
	        				'createdByUserLogin'=>'system'
	        			));
	        			
            			$handle->createOrderShipment(array(
            				'orderId'=>$order[0]['order_id'] ,
            				'shipmentId'=>$object->return
            			));
	        			
	    			}
	    			catch (Exception $e){
	    				echo "多面单追加面单失败！异常信息为：".$e;
	    			}
			    		
		    	}
			 }
			 
			 $note = "订单虚拟出库, 使用的面单号为：{$tracking_number}";
			 $sql = " INSERT INTO ecshop.ecs_order_action 
			        (order_id, order_status, shipping_status, pay_status, action_time, action_note, action_user) VALUES 
			        ('{$order[0]['order_id']}', '{$order[0]['order_status']}', '{$order[0]['shipping_status']}', '{$order[0]['pay_status']}', NOW(), '{$note}', 'WEB_SERVICE')
			  ";
			 $update=$this->getDB()->createCommand($sql)->execute();
		 
			 echo " updateTrackingNumber(".$ordernew['order_id'].") ".PHP_EOL;
			 echo " tracking_number=".$tracking_number.PHP_EOL;
			 echo " shipping_name=".$shipping_name.PHP_EOL;
			 
    	}
    }
    
    /*
     * APO 德国药房的虚拟出库调度 by stsun 2016-04-23
     */
    public function actionDeliverApo() {
    	//德国仓库已发货的，预定成功的，且ERP中订单状态为“未发货”，ERP中需要虚拟出库
    	$sql = "SELECT
	 				oi.order_id,oi.party_id
	 			FROM ecshop.brand_apo_order_info a
				inner join ecshop.ecs_order_info oi on oi.order_id=a.order_id
				inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id
				where a.status='SHIPPED' and oi.facility_id ='237191027' and ir.status = 'Y' and oi.shipping_status=0
					and oi.order_type_id in ('SALE') and oi.party_id=65666
		".' -- VDI '.__LINE__.PHP_EOL;
    	$orderIds = $this->getDB()->createCommand($sql)->queryAll();
    	$this->deliverOrderParty($orderIds);
    }
     
    /*
     * 超市品牌商仓库虚拟出库
     * */
    public function actionDeliverFacility($days=30)
    {
    	$sql = "SELECT  
	 			oi.order_id,oi.party_id
			from  ecshop.ecs_order_info oi
			    inner join romeo.order_inv_reserved ir on oi.order_id = ir.order_id
				inner join romeo.party_facility pf on oi.facility_id = pf.facility_id and convert(oi.party_id using utf8)= pf.party_id
				inner join romeo.party p
					on p.party_id = convert(oi.party_id using utf8)
					where oi.order_status = 1 and oi.shipping_status = 0 and ir.status = 'Y' and oi.order_time >  date_sub(NOW(),interval {$days} day)
			      and pf.is_delete = 0 and p.system_mode = '{$this->party_mode}' limit 100
	 	  ".' -- VDI '.__LINE__.PHP_EOL;
	 	$orderIds = $this->getDB()->createCommand($sql)->queryAll();
		$this->deliverOrderParty($orderIds);
		
		$sql = "SELECT 
	 			oi.order_id,os.shipment_id
			from  ecshop.ecs_order_info oi force index (order_info_multi_index)
			    inner join romeo.order_shipment os on os.order_id = convert(oi.order_id using utf8)
				inner join romeo.party_facility pf on oi.facility_id = pf.facility_id and convert(oi.party_id using utf8)= pf.party_id
					where oi.order_status = 1 and oi.shipping_status = 8 and oi.order_time >  date_sub(NOW(),interval {$days} day)
			      and pf.is_delete = 0 limit 100
	 	  ".' -- VDI '.__LINE__.PHP_EOL;
	 	$order_shipments = $this->getDB()->createCommand($sql)->queryAll();
	 	foreach($order_shipments as $order_shipment){
	 	  	$this->updatePrepaymentAndShipmentStatus($order_shipment['order_id'],$order_shipment['shipment_id']);
	 	}
    }
    public function deliverOrderParty($orderIds){
    	foreach($orderIds as $orderId)
	 	{
	 		$lock_name = "party_{$orderId['party_id']}";
		    $lock_file_name = $this->get_file_lock_path($lock_name, 'virtual_pick');
		    $lock_file_point = fopen($lock_file_name, "w+");
		    $would_block = false;
		    if(flock($lock_file_point, LOCK_EX|LOCK_NB, $would_block)){
		    	$this->run(array('DeliverOrder','--orderId='.$orderId['order_id']));
		    	flock($lock_file_point, LOCK_UN);
        		fclose($lock_file_point);
		    }else{
		    	fclose($lock_file_point);
    			echo("同业务组有人正在完结，请稍后");
		    }
	 	}
    }
    public function actionDeliverOrder($orderId)
    {
        $sql = "SELECT os.shipment_id from romeo.order_shipment os
				inner join romeo.shipment s on os.shipment_id = s.shipment_id
			where os.order_id = '{$orderId}' and s.SHIPPING_CATEGORY = 'SHIPPING_SEND'
	 	  ".' -- VDI '.__LINE__.PHP_EOL;
	    $orderShipments = $this->getDB()->createCommand($sql)->queryAll();
	    if(empty($orderShipments)){
	    	echo("orderId:{$orderId}not has shipment!");
	    }else{
	    	$shipmentId = $orderShipments[0]['shipment_id'];
    		$is_delivery_ok = $this->delNewInv($orderId);
    		if($is_delivery_ok) { $this->updateStatusPicked($shipmentId);
    		                      $this->updatePrepaymentAndShipmentStatus($orderId,$shipmentId); }
	    }

    }
    
    /*======复制订单调度========*/
    public function actionBirdOrderCopy()
    {
        ini_set('default_socket_timeout', 600);
       
       	$sql = "select eoi.order_id from ecshop.express_bird_indicate ebi
				INNER JOIN ecshop.ecs_order_info eoi on ebi.out_biz_code = eoi.taobao_order_sn
				where eoi.facility_id not in('224734292','149849263','149849264','149849265','149849266','173433261','173433262','173433263','173433264','173433265')
				and eoi.order_type_id = 'SALE' and ebi.indicate_status='推送成功' and eoi.order_time > '2015-11-11'
				and eoi.order_status=2 and eoi.order_id not in (10460544)";
        // 远程服务
        $orderList = $this->getDB()->createCommand($sql)->queryAll();
        $client=Yii::app()->getComponent('erpsync')->SyncTaobaoService;
        try
        {
			foreach ($orderList as $orderId){
				 $request=array("orderId"=>$orderId['order_id']);
				 print_r($request);
				 $response=$client->DeepCloneBirdOrder($request);
            	 print_r($response);
			}
        }
        catch(Exception $e)
        {
            echo("|  Exception: ".$e->getMessage()."\n");
        }
    }  
    
    
    function updatePrepaymentAndShipmentStatus($orderId,$shipmentId){
    	if($this->updatePrepayment($orderId)){
			$this->updateStatusShipped($shipmentId);
			$sql  = "UPDATE ecshop.ecs_order_info SET shipping_time=UNIX_TIMESTAMP() WHERE order_id='{$orderId}'";
            $this->getDB()->createCommand($sql)->execute();
		}
    }
    function actionTestSys()
    {
    	while(true){
			$request = array('data1' => '123',
				'data2' => '123');
			$response = $this->getSoapClient()->outMethod($request);
    	}
	}
    function delNewInv($orderId)
    {
	  $request = array('orderId' => $orderId,
	  					'actionUser' => $this->actionUser);
	  try{ 
		  $response = $this->getSoapClient()->oneKeyOrderPick($request);
	  	  return true;
	  }catch (Exception $e) {
	      echo("[". date('c'). "] oneKeyOrderPick soap call exception:".$e->getMessage().PHP_EOL);
	      return false;
	  }
	}
	protected function updateStatusPicked($shipmentId)
	{
	  $request = array('shipmentId' => $shipmentId,
				'actionUser' => $this->actionUser);
	  try{ 
		  $response = $this->getSoapClient()->updateShipmentStatusPicked($request);
	  	  return true;
	  }catch (Exception $e) {
	      echo("oneKeyOrderPick soap call exception:".$e->getMessage());
	      return false;
	  }
	}
	protected function updateStatusShipped($shipmentId)
	{
	  $request = array('shipmentId' => $shipmentId,
				'actionUser' => $this->actionUser);
	  try{ 
		  $response = $this->getSoapClient()->updateShipmentStatusShipped($request);
	  	  return true;
	  }catch (Exception $e) {
	      echo("oneKeyOrderPick soap call exception:".$e->getMessage());
	      return false;
	  }
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
	protected function getSoapClient()
	{
		if(!$this->soapclient)
		{
			$this->soapclient = Yii::app()->getComponent('romeo')->InventoryService;
		}
		return $this->soapclient;
	}
	protected function getPrepaySoapClient(){
		if(!$this->prepaySoapclient)
		{
			$this->prepaySoapclient = Yii::app()->getComponent('romeo')->PrepaymentService;
		}
		return $this->prepaySoapclient;
	}
	 /**
	 * 获得文件锁路径
	 *
	 * @param string $file_name
	 * @return string
	 */
	protected function get_file_lock_path($file_name = '', $namespace = null) {
		if (!defined('ROOT_PATH')) {
			define('ROOT_PATH', preg_replace('/admin(.*)/', '', str_replace('\\', '/', __FILE__)));
		}
	    if ($namespace == null) {
	    	preg_match('/\/([^\/]*)\.php/', $_SERVER['SCRIPT_URL'], $matches);
	        $namespace = $matches[1];
	    }
		return ROOT_PATH. "admin/filelock/{$namespace}.{$file_name}";
	}
	private function updatePrepayment($order_id){
		//销售订单 
		$sql="select party_id, order_id, order_sn, taobao_order_sn, order_time, distributor_id, order_type_id, facility_id
			from ecshop.ecs_order_info 
			where order_id = {$order_id} and order_type_id = 'SALE' ";
			
		$order = $this->getDB()->createCommand($sql)->queryRow();
		if(empty($order)){
			echo("[". date('c'). "] 订单不需要扣预存款,order_id:".$order_id."\n");
        	return true;
        }
	    // 电教或金佰利或亨氏分销订单 要抵扣预存款
        $sql = "
            select md.main_distributor_id 
            from ecshop.distributor d 
            left join ecshop.main_distributor md on d.main_distributor_id = md.main_distributor_id 
            where d.distributor_id = '{$order['distributor_id']}' and type = 'fenxiao' and d.is_prepayment = 'Y' limit 1 ;" ;
        $main_distributor_id = $this->getOneBySql($sql,'main_distributor_id');
        if(empty($main_distributor_id)){
        	echo("[". date('c'). "] 订单不需要扣预存款,order_id:".$order_id."\n");
        	return true;
        }
        $supplier_id = strval($main_distributor_id);
        
        //跨进电商且仓库为香港GPN直邮仓，香港中外运直邮仓 需扣预存款
        $sql = "select parent_party_id from romeo.party where party_id = {$order['party_id']} LIMIT 1";
        $parent_party_id = $this->getOneBySql($sql,'parent_party_id');
        
        //电教、金佰利、亨氏、惠氏、雀巢
        $edu_adjust_need = ($order['party_id'] == 16 
        						|| $order['party_id'] == 65558 
        						|| $order['party_id'] == 65609 
        						|| $order['party_id'] == 65553
        						|| $order['party_id'] == 65617
        						|| ($parent_party_id == 65658 && in_array($order['facility_id'], array('222187981', '231292324')))
//        						|| $order['party_id'] == 65638  //保税仓又说不扣预存款了。。
                          );
        if ($edu_adjust_need){
        	// 分销的销售订单，抵扣预付款
        	echo("[". date('c'). "] 订单扣预存款,order_id:".$order_id."begin\n");
        	$result = $this->_distribution_edu_order_adjustment($order, $supplier_id);
        	echo("[". date('c'). "] 订单扣预存款,order_id:".$order_id."end\n");
        	return $result;
        }else{
        	echo("[". date('c'). "] 订单不需要扣预存款,order_id:".$order_id."\n");
        	return true;
        }
	}
	private function getOneBySql($sql,$columnName){
		$results = $this->getDB()->createCommand($sql)->queryAll();
		if(empty($results)){
			return null;
		}else{
			return $results[0][$columnName];
		}
	}
private function _distribution_edu_order_adjustment($order, $supplier_id){
	$result = null;
	$adjust = $this->_distribution_get_edu_adjust($order);
	echo("[". date('c'). "] 扣款金额:".$adjust."\n");
	if ($adjust > 0) {
		$note = "订单调价,ERP订单号 {$order['order_sn']},淘宝订单号{$order['taobao_order_sn']}";
		$prepayment_transaction_id = $this->_prepay_consume(
                        $supplier_id,                         // 合作伙伴ID 
                        $order['party_id'],                            // 组织
                        'DISTRIBUTOR',                                 // 账户类型
                        $adjust,                                        // 使用金额
                        NULL,                                           // 账单
                        $this->actionUser,
                        $note,                                          // 备注
                        NULL                                            // 支票号
                    );
		if ($prepayment_transaction_id ) {
			// 标识该淘宝订单已经扣过预付款
            $sql  = "INSERT INTO distribution_order_adjustment_log (taobao_order_sn,prepayment_transaction_id,status) VALUES ('{$order['order_sn']}','{$prepayment_transaction_id}','CONSUMED')";
            $this->getDB()->createCommand($sql)->execute();
            $sql  = "UPDATE distribution_order_adjustment SET status = 'CONSUMED', prepayment_transaction_id = '{$prepayment_transaction_id}' WHERE order_id='{$order['order_id']}' AND status='INIT'";
            $this->getDB()->createCommand($sql)->execute();
        }else {
            return false;
        }
	}
	return true;
}
function actionTestAccount(){
	try {
    	$supplier_id = '1810';
		$party_id = '65609';
		$account_type_id = 'DISTRIBUTOR';
		$data = array('arg0'=>$supplier_id,'arg1'=>$party_id,'arg2'=>$account_type_id);
		//$wsdl = "http://192.168.37.130:8080/romeo/PrepaymentService?wsdl";
		//$client = new SoapClient($wsdl, array('trace' => 1));
		//echo "REQUEST:\n" . $client->__getLastRequest() . "\n";
		//$account_id = $this->getPrepaySoapClient()->getAccountIdByPartnerId($data);
		
		$this->updatePrepaymentAndShipmentStatus('275369','275369');
    } catch (SoapFault $e) {
    	echo("[". date('c'). "] 账号错误supplier_id".$supplier_id.'party_id'.$party_id.'account_type_id'.$account_type_id."\n");
    	echo("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})");
        return false;
    }
}
function _prepay_consume($supplier_id, $party_id, $account_type_id, $amount, $purchase_bill_id, $created_by_user_login, $note = '', $cheque_no = '', $is_rebate=0)
{
	// 首先判断是否有预付款账号
    try {
    	echo("[". date('c'). "] 账号查找supplier_id：".$supplier_id.'party_id：'.$party_id.'account_type_id：'.$account_type_id."\n");
		$data = array('arg0'=>$supplier_id,'arg1'=>$party_id,'arg2'=>$account_type_id);
        $account_id = $this->getPrepaySoapClient()->getAccountIdByPartnerId($data)->return;
    } catch (SoapFault $e) {
    	echo("[". date('c'). "] 账号错误supplier_id".$supplier_id.'party_id'.$party_id.'account_type_id'.$account_type_id."\n");
    	echo("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})");
        return false;
    }
	
    // 取得可用金额
    try {
		$data = array('arg0'=>$supplier_id,'arg1'=>$party_id,'arg2'=>$account_type_id);
        $available = $this->getPrepaySoapClient()->getAvailablePrepayment($data)->return;
    } catch (SoapFault $e) {
    	echo("[". date('c'). "] 金额错误supplier_id".$supplier_id.'party_id'.$party_id.'account_type_id'.$account_type_id."\n");
    	echo("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})");
        return false;
    }
    // 判断余额
	if ($available < $amount) {
		echo("[". date('c'). "] 金额不足available".$available.'need：'.$amount."\n");
	    return false;	
	}
		

    // 消耗预付款
    try {
		$data = array('arg0'=>$account_id,'arg1'=>$purchase_bill_id,'arg2'=>$created_by_user_login,'arg3'=>$amount,'arg4'=>$note,'arg5'=>$cheque_no,'arg6'=>$is_rebate);
        $prepaymentId = $this->getPrepaySoapClient()->consumePrepayment($data)->return;
		echo("[". date('c'). "] pay success \n");
		return $prepaymentId;
    } catch (SoapFault $e) {
    	echo("[". date('c'). "] 金额不足available".$available.'need：'.$amount."\n");
    	echo("SOAP调用失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})");
        return false;
    }
    
}
function _distribution_get_edu_adjust($order) {
    // 在此时间点之前的订单不计算调价
    if (strtotime($order['order_time']) < strtotime('2010-08-31 04:00:00')) {
        return 0;
    }
    // 该淘宝订单已经扣过了 
	$sql = "SELECT taobao_order_sn FROM distribution_order_adjustment_log WHERE taobao_order_sn = '{$order['order_sn']}' LIMIT 1";
    $consumed = $this->getOneBySql($sql,'taobao_order_sn');
    if(!empty($consumed)){
    	return 0;
    }
    
    // 已经计算过调价金额
    $sql = "SELECT SUM(amount) as adjust FROM distribution_order_adjustment WHERE order_id='{$order['order_id']}' AND status='INIT' ";
    $adjust = $this->getOneBySql($sql,'adjust');
    if(!empty($adjust)){
    	return $adjust;
    }
    if($order['party_id'] == '65638'){
    	$sql = "select order_amount from ecshop.ecs_order_info where order_id = '{$order['order_id']}' ";
    	return $this->getDB()->createCommand($sql)->queryScalar();
    }
	
    // 调价金额
    $adjust = 0;
    $datetime = date('Y-m-d H:i:s');

    // 计算运费的调价金额
    $sql = "SELECT og.goods_id, og.style_id, og.goods_number, g.goods_name 
    		FROM ecshop.ecs_order_goods og
    		inner join ecs_goods AS g on og.goods_id = g.goods_id
    		WHERE og.order_id='{$order['order_id']}' ";
    $goods = $this->getDB()->createCommand($sql)->queryAll();
    $adjust = 0;
    foreach($goods as $good){
			// 查询商品调价的SQL
	        $sql = "
	            SELECT adjust_fee 
	            FROM ecshop.distribution_sale_price 
	            WHERE (distributor_id = 0 or distributor_id = ". $order['distributor_id'] .") AND goods_id = '{$good['goods_id']}' AND '{$order['order_time']}' >= valid_from
	            ORDER BY distributor_id DESC, valid_from DESC  
	            limit 1  
	        ";
	        $adjust_fee = $this->getOneBySql($sql,'adjust_fee');
	        if ($adjust_fee && $adjust_fee > 0) {
	    	$adjust_fee = $adjust_fee * $good['goods_number'];
	        $sql = "INSERT INTO distribution_order_adjustment (order_id, goods_id, style_id, goods_name, num, amount, type, status, created_by_user_login, created) VALUES " .
	        		"('{$order['order_id']}', '{$good['goods_id']}', '{$good['style_id']}', '{$good['goods_name']}', '{$good['goods_number']}', '{$adjust_fee}', 'GOODS_ADJUSTMENT', 'INIT', '{$this->actionUser}', '{$datetime}')";
	    	$this->getDB()->createCommand($sql)->execute();
	    	$adjust += $adjust_fee;
		}
    }
	return $adjust;
}
	
}
?>
