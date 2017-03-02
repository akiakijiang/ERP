<?php
define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require(ROOT_PATH . "/includes/lib_order.php");
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH.'RomeoApi/lib_inventory.php');
require_once(ROOT_PATH.'RomeoApi/lib_RMATrack.php');
require_once ROOT_PATH . 'admin/includes/lib_service.php';
include_once ROOT_PATH . 'admin/function.php';
Yii::import('application.commands.LockedCommand', true);
/**
 * 定时取gymboree服务器上的XML文件，做处理，插入brand_gymboree_product数据
 * @author qxu@i9i8.com
 * @copyright Copyright &copy; 2012 leqee.com
 */

class GymboreeCommand extends CConsoleCommand {
	
	private $master; // Master数据库    
	
	/**
	 * 当不指定ActionName时的默认调用
	 */
	public function actionIndex(){
		$this->run(array('DownFiles'));
		$this->run(array('CheckFiles'));
	}
	//零售单指示-（已确认，已付款）销售订单 ->normal->sended  -->ecshop.brand_gymboree_sales_order_info
	public function actionRecSalseOrder(){
		echo("[".date('c')."] "." RecSalseOrder start \n");
//		$time = date('Ymd',time());
//		$cont = "";
//		if($time<'20150519'){
//			$cont = " and ((SELECT count(1) from ecshop.ecs_order_goods og where  og.order_id = oi.order_id and og.goods_id = '195215' and og.style_id ='386' ) = 0)  ";
//		}
		$transfer_errors = "";
		global $db;
		$Warehouse = '1200';
		$facility_id = GYMBOREE_FACILITY_ID;
//		$sql = "select oi.order_id,oi.order_sn,consignee,country,province,city,district,zipcode,address, postscript,oi.shipping_fee,oi.order_amount,oi.goods_amount,
//			shipping_id,shipping_name,mobile,tel,oi.order_time,oi.taobao_order_sn,ifnull(oa.attr_value,'') as Customer_name ,oa1.attr_value,oi.bonus
//			from ecshop.ecs_order_info oi  
//			LEFT JOIN ecshop.brand_gymboree_sales_order_info gsoi on gsoi.ShipmentID = convert(oi.order_id using utf8) 
//			LEFT JOIN romeo.order_inv_reserved oir on oir.order_id = oi.order_id  
//			left join ecshop.order_attribute oa on oi.order_id=oa.order_id and oa.attr_name = 'TAOBAO_USER_ID' 
//			left join ecshop.order_attribute oa1 ON oi.order_id = oa1.order_id and oa1.attr_name = 'OUTER_TYPE'
//			where oi.order_type_id in ('SALE','RMA_EXCHANGE') and oi.party_id = '65574'  AND
//			((oi.order_type_id ='SALE' AND oi.pay_status = 2)  OR (oi.order_type_id='RMA_EXCHANGE'))    
//			and order_status = 1 and shipping_status=0 and  gsoi.sales_order_info_id is NULL AND oi.facility_id = '{$facility_id}'
//			and ((SELECT count(1) from ecshop.ecs_order_goods og where  og.order_id = oi.order_id ) != 0) {$cont}
//			and oir.STATUS = 'Y' 
//			ORDER BY oi.order_time limit 100";
		$sql = "select DISTINCT oi.order_id,oi.order_sn,consignee,country,province,city,district,zipcode,address, postscript,oi.shipping_fee,oi.order_amount,oi.goods_amount,
					shipping_id,shipping_name,mobile,tel,oi.order_time,oi.taobao_order_sn,ifnull(oi.nick_name,'') as Customer_name ,oi.source_type as attr_value,oi.bonus
					from ecshop.ecs_order_info oi
					INNER JOIN ecshop.ecs_order_goods eog on eog.order_id = oi.order_id
					LEFT JOIN ecshop.brand_gymboree_sales_order_info gsoi on gsoi.ShipmentID = convert(oi.order_id using utf8) 
					LEFT JOIN romeo.order_inv_reserved oir on oir.order_id = oi.order_id  
					where  oi.party_id = '65574'  AND
					((oi.order_type_id ='SALE' AND oi.pay_status = 2)  OR (oi.order_type_id='RMA_EXCHANGE'))    
					and order_status = 1 and shipping_status=0 and  gsoi.sales_order_info_id is NULL AND oi.facility_id = '{$facility_id}'
					and oir.STATUS = 'Y' and  oi.reserved_time > (UNIX_TIMESTAMP() - 3600 * 48)
					ORDER BY oi.order_time limit 500
				";
		$orders = $this->getMaster()->createCommand($sql)->queryAll();
		echo("[".date('c')."] "." RecSalseOrder ".count($orders)." items \n");
		if(!empty($orders)){
			echo("[".date('c')."] "." RecSalseOrder insert sales info_goods start \n");
			foreach($orders as $order){
				$order_bonus = abs($order['bonus']);
				$order_id = $order['order_id'];
				$goods_sql = "select rec_id,if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) as barcode,og.goods_number,og.goods_name,s.color,og.goods_price " .
						" from ecshop.ecs_order_goods og" .
						" left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and is_delete=0 " .
						" left join ecshop.ecs_goods g on g.goods_id = og.goods_id " .
						" left join ecshop.ecs_style s on s.style_id = gs.style_id " .
						" where og.order_id = '{$order_id}' ";
				$order_goods = $this->getMaster()->createCommand($goods_sql)->queryAll();
				if(empty($order_goods)){
					continue;
				}
				$countrySQL = "select region_name from ecshop.ecs_region where region_id = '{$order['country']}' and region_type=0";
			    $country_name = $this->getMaster()->createCommand($countrySQL)->queryScalar();
			
			    $provinceSQL = "select region_name from ecshop.ecs_region where region_id = '{$order['province']}' and region_type=1";
			    $province_name = $this->getMaster()->createCommand($provinceSQL)->queryScalar();
			
			    $citySQL = "select region_name from ecshop.ecs_region where region_id = '{$order['city']}' and region_type=2";
			    $city_name = $this->getMaster()->createCommand($citySQL)->queryScalar();  
			    
			    $districtSQL = "SELECT region_name FROM ecshop.ecs_region WHERE region_id = '{$order['district']}' and region_type=3";
			    $district_name = $this->getMaster()->createCommand($districtSQL)->queryScalar();
			    $sql = "select carrier_code from ecshop.brand_gym_giessen_shipping_carrier where shipping_id = '{$order['shipping_id']}'";
				$Carrier = $db->getOne($sql);
	    		
				$sales_order_array = array(
					'Warehouse' => $Warehouse,
					'Interface_Record_ID' => $order['order_sn'],
					'ShipmentID' => $order['order_id'],
					'Company' =>'GYMBOREE',
					'Customer_name' => htmlspecialchars($order['Customer_name'], ENT_QUOTES),
					'CUSTOMER_PHONE_NUM' =>$order['order_amount'],
					'CUSTOMER_FAX_NUM' => $order['shipping_fee'],
					'SHIP_To_Name' => htmlspecialchars($order['consignee'], ENT_QUOTES),
					'SHIP_TO_COUNTRY' => $country_name,
					'SHIP_TO_STATE' => $province_name,
					'SHIP_TO_CITY' => $city_name,
					'Customer_Address3' => $district_name,
					'SHIP_TO_POSTAL_CODE' => $order['zipcode'],
					'SHIP_TO_ADDRESS1' => htmlspecialchars($order['address'], ENT_QUOTES),
					'Carrier' => $Carrier,
					'SHIP_TO_PHONE_NUM' => $order['tel'],
					'SHIP_TO_FAX_NUM' => $order['mobile'],
					'User_def6' => $order['order_time'],
					'User_def4' => ($order['attr_value']=='taobao')?'天猫':($order['attr_value']=='360buy'?'京东':$order['attr_value']),
					'User_def5' => $order['taobao_order_sn'],
					'Parties' => trim($order['postscript']),
				);
				$transaction = $this->getMaster()->beginTransaction();
				if(!$this->GymboreeInsertSql($sales_order_array,"ecshop.brand_gymboree_sales_order_info")){
					$transfer_errors .= "[".date('c')."] "." insert order info fail! order_id :".$order['order_id']."\n";
					$transaction->rollback();
					continue;
				}
				$user_bonus = 0;
				foreach($order_goods as $key=>$good){
					
					if(count($order_goods)-$key !=1){
						if($order['goods_amount'] != 0) {
							$good_bonus = round($order_bonus * $good['goods_price'] / $order['goods_amount'],2);
							$good_price = round($good['goods_price'] -$good_bonus,2);
							$user_bonus = $user_bonus+$good_bonus*$good['goods_number'];
						} else {
							$good_price = 0;
						}					
					}else{
						$good_price = $good['goods_price']-round(($order_bonus-$user_bonus)/$good['goods_number'],2);
					}
					$sales_good_array = array(
						'Interface_Link_ID' => $order['order_id'],
						'Interface_Record_ID' => $good['rec_id'],
						'ERP_Order_Line_Num' => $good['rec_id'],
						'Company' => 'GYMBOREE',
						'Item' => $good['barcode'],
						'ITEM_LIST_PRICE' =>$good_price,
						'Total_qty' => $good['goods_number'],
						'Goods_Name' => mysql_escape_string($good['goods_name']),
						'Style_Name' => $good['color'],
					);
					if(!$this->GymboreeInsertSql($sales_good_array,"ecshop.brand_gymboree_sales_order_goods")){
						$transfer_errors .= "[".date('c')."] "." insert goods info fail! order_id :".$order['order_id'].",rec_id :".$good['rec_id']."\n";
						$transaction->rollback();
						continue 2;
					}
				}
				$transaction->commit();
				echo("[".date('c')."] "." RecSalseOrder insert sales info_goods. order_id: " . $order['order_id'] . " \n");
			}
			echo("[".date('c')."] "." RecSalseOrder insert sales info_goods end \n");
		}
		
		//零售单指示-传送给基森
		$sql = "select soi.* from ecshop.brand_gymboree_sales_order_info soi " .
				" left join ecshop.ecs_order_info oi on oi.order_id = soi.shipmentID " .
				"where soi.transfer_status = 'NORMAL' and oi.order_status = 1 and oi.pay_status = 2 " .
				"and oi.shipping_status=0 and  oi.order_time >'2015-05-09 9:59:00' " .
				" order by created_time limit 500";
		$sales_orders = $this->getMaster()->createCommand($sql)->queryAll();
		if(!empty($sales_orders)){
			echo("[".date('c')."] "." RecSalseOrder send sales info_goods begin \n");
			foreach($sales_orders as $sales_order){
				$start_time = microtime(TRUE);
				$ShipmentID = $sales_order['ShipmentID'];
				$sql = "select Interface_Link_ID,Interface_Record_ID,ERP_Order_Line_Num,Company,Item,Goods_Name,Style_Name ,
					ITEM_LIST_PRICE,Total_qty,ITEM_COLOR,ITEM_SIZE,fchrItemCode,User_def2,User_def3
					from ecshop.brand_gymboree_sales_order_goods   
					where Interface_Link_ID = '{$ShipmentID}'";
				$sales_goods = $this->getMaster()->createCommand($sql)->queryAll();
				if(empty($sales_goods)){
					$transfer_errors .= "[".date('c')."] "." empty goods,send fail! ShipmentID :".$ShipmentID."\n";
					continue;
				}
				$strSalsInfo = '{"Header":[{';
				foreach($sales_order as $key2=>$value2){
					if(!in_array($key2,array('sales_order_info_id','transfer_status','transfer_note','created_time','updated_time','time_cost'))){
						$strSalsInfo .='"'.$key2.'":"'.$value2.'",';
					}
				}
				$strSalsInfo = substr($strSalsInfo,0,-1).'}],"Details":[';
				foreach($sales_goods as $value){
					$strSalsInfo .='{';
					foreach($value as $key2=>$value2){
						$strSalsInfo .='"'.$key2.'":"'.$value2.'",';
					}
					$strSalsInfo = substr($strSalsInfo,0,-1)."},";
				}
				$strSalsInfo = substr($strSalsInfo,0,-1)."]}";
//				echo("[".date('c')."] "."order_id:".$ShipmentID."strSalsInfo:".$strSalsInfo." \n");
				$send_flag = true;
				try {
					$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
					$response = $sc->RecSalseOrder(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD,'strSalsInfo'=>$strSalsInfo));
				} catch (Exception $e) {
					$send_flag = false;
					echo("[".date('c')."] "." RecSalseOrder send sales info_goods. Connect Fail! order_id:".$ShipmentID." \n");
					echo("[".date('c')."] "."strSalsInfo:".$strSalsInfo." \n");
					$transfer_errors .= "[".date('c')."] "." connect fail! connect RecSalseOrder Exception :".$e->getMessage()."strSalsInfo".$strSalsInfo."\n";
				}
				$end_time = microtime(TRUE);
				$time_cost = $end_time - $start_time;
				if($send_flag){
					$str = $response->RecSalseOrderResult->any;
					$b= (stripos($str,"<ErrorInfo>"));
					$c= (stripos($str,"</ErrorInfo>"));
					$length = strlen("<ErrorInfo>");
					$return = substr($str,$b+$length,$c-$b-$length);
					if($return=='T'){
						$sql = "update ecshop.brand_gymboree_sales_order_info set transfer_status='SENDED',updated_time=NOW(),time_cost = '{$time_cost}' where sales_order_info_id = '{$sales_order['sales_order_info_id']}' ";
						echo("[".date('c')."] "." RecSalseOrder send sales info_goods success! order_id: ".$ShipmentID."\n");
					}else if($return=='F'){
						$sql = "update ecshop.brand_gymboree_sales_order_info set transfer_status='FAIL',transfer_note='strSalseInfo传输错误',updated_time=NOW(),time_cost = '{$time_cost}' where sales_order_info_id = '{$sales_order['sales_order_info_id']}' ";
						echo("[".date('c')."] "." RecSalseOrder send sales info_goods fail! error strSalsInfo :".$strSalsInfo."\n");
						$transfer_errors .= "[".date('c')."] "." send fail! error strSalsInfo :".$strSalsInfo." \n";
					}else{
						$sql = "update ecshop.brand_gymboree_sales_order_info set transfer_status='FAIL',transfer_note='{$return}',updated_time=NOW(),time_cost = '{$time_cost}' where sales_order_info_id = '{$sales_order['sales_order_info_id']}' ";
						echo("[".date('c')."] "." RecSalseOrder send sales info_goods fail! errorInfo :".$return."\n");
					}
					$this->getMaster()->createCommand($sql)->execute();
				}
			}
			echo("[".date('c')."] "." RecSalseOrder send sales info_goods end \n");
		}
		if($transfer_errors != ""){
			$this->sendMail("【Gymboree搬仓】【ERROR】RecSalseOrder",$transfer_errors);
			echo("[".date('c')."] "."【ERROR】RecSalseOrder:".$transfer_errors." \n");
		}
		echo("[".date('c')."] "." RecSalseOrder end \n");
		
	}
	
	//零售单实绩 -获取数据
	public function actionRecSalseOrderConfirm($hours = 2){
		$strBeginTime = date('YmdHi',strtotime("-".$hours." hours"));
        $strEndTime = date('YmdHi',time());
        
        echo("[".date('c')."] "." RecSalseOrderConfirm start \n");
      	echo("[".date('c')."] "." RecSalseOrderConfirm startTime:".$strBeginTime.", endDate:".$strEndTime."\n");
		$transfer_errors = "";
		$insert_flag = true;
		try {
			$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
			$response = $sc->RecSalseOrderConfirm(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD,'strBeginTime'=>$strBeginTime,'strEndTime'=>$strEndTime));
		}catch (Exception $e){
			$insert_flag = false;
			echo("[".date('c')."] "." RecSalseOrderConfirm connect fail! connect RecSalseOrderConfirm Exception :".$e->getMessage()."\n");
			$transfer_errors .="[".date('c')."] "." RecSalseOrderConfirm connect fail! connect Exception :".$e->getMessage()."\n";
		}
		if($insert_flag){
			$str = $response->RecSalseOrderConfirmResult->any;
			$document = simplexml_load_string($str);
			$error_info = $document->NewDataSet->Details->ErrorInfo;
			if($error_info != "" && $error_info != 'No Data!'){
				$transfer_errors .="[".date('c')."] "."访问RecSalseOrderConfirm接口错误".$error_info."\n";
			}else if($error_info == ""){
				$header_array = array();
				if(empty($document->NewDataSet->Details) && !empty($document->NewDataSet->Header)){
					$transfer_errors .=" 实绩中只有header数据，details数据丢失 !  \n";
				}
				foreach($document->NewDataSet->Header as $aa){
					$header_li = array();
					$header_li['Warehouse'] = trim($aa->Warehouse);
					$header_li['ShipmentID'] = trim($aa->ShipmentID);
					if($header_li['ShipmentID']==""){
						$transfer_errors .="[".date('c')."] "." ShipmentID is null !! \n";
						continue;
					}
					$header_li['Company'] = trim($aa->Company);
					$header_li['ACTUAL_SHIP_DATE_TIME'] = trim($aa->ACTUAL_SHIP_DATE_TIME);
					if($header_li['ACTUAL_SHIP_DATE_TIME']==""){
						$transfer_errors .="[".date('c')."] "." ACTUAL_SHIP_DATE_TIME is null !! \n";
						continue;
					}
					$header_li['Carrier'] = trim($aa->Carrier);
					if($header_li['Carrier']==""){
						$transfer_errors .="[".date('c')."] "." Carrier is null !! \n";
						continue;
					}
					$header_li['User_def2'] = trim($aa->User_def2);
					if($header_li['User_def2']==""){
						$transfer_errors .="[".date('c')."] "." User_def2 is null !! \n";
						continue;
					}
					$header_li['User_def7'] = trim($aa->User_def7);
					$header_li['Customer_Address1'] = trim($aa->Customer_Address1);
					$header_li['User_def4'] = trim($aa->User_def4);
					$header_li['Order_type'] = trim($aa->Order_type);
					array_push($header_array,$header_li);
				}
				echo("[".date('c')."] "." RecSalseOrderConfirm ".count($header_array)." items \n");
				
				$detail_array = array();
				foreach($document->NewDataSet->Details as $de){
					$detail_li = array();
					$detail_li['COMPANY'] = trim($de->COMPANY);
					$detail_li['ShipmentID'] = trim($de->ShipmentID);
					
					if($detail_li['ShipmentID']==""){
						$transfer_errors .="[".date('c')."] "." Detail ShipmentID is null !! \n";
						continue;
					}
					$detail_li['Item'] = trim($de->Item);
					if($detail_li['Item']==""){
						$transfer_errors .="[".date('c')."] "." Detail Item is null !! \n";
						continue;
					}
					$detail_li['shipped_qty'] = trim($de->shipped_qty);
					if($detail_li['shipped_qty']==""){
						$transfer_errors .="[".date('c')."] "." Detail shipped_qty is null !! \n";
						continue;
					}
					$detail_li['User_def1'] = trim($de->User_def1);
					$detail_li['Order_type'] = trim($de->Order_type);
					$detail_li['User_def3'] = trim($de->User_def3);
					$detail_li['User_def2'] = trim($de->User_def2);
					array_push($detail_array,$detail_li);
				}
				if(!empty($detail_array) && !empty($header_array)){
					echo("[".date('c')."] "." RecSalseOrderConfirm insert sales confirm_details begin \n");
					foreach($header_array as $header_array_key => $header_value){
						//1.需要先排除已存在的数据ecshop.brand_gymboree_sales_order_confirm
						$shipmentID = $header_value['ShipmentID'];
						$sql = "select count(1) from ecshop.brand_gymboree_sales_order_confirm where ShipmentID = '{$shipmentID}' limit 1";
						$exist_ShipmentID_count = $this->getMaster()->createCommand($sql)->queryScalar();
						if($exist_ShipmentID_count!=0){
							continue;
						}
						//组装sql
						$header_str = "";
						foreach($header_value as $header_key=>$header_value2){
							if($header_key=="User_def7"){
								$header_str .= $header_value2.","; 
							}else{
								$header_str .= "'".$header_value2."',";
							}
						}
						$detail_strs = array();
						foreach($detail_array as $key=>$detail_value){
							if($shipmentID != $detail_value['ShipmentID']){
								continue;
							}
							$detail_str = "";
							foreach($detail_value as $detail_key=>$detail_value2){
								if($detail_key=='shipped_qty'){
									$detail_str .= $detail_value2.","; 
								}else{
									$detail_str .= "'".$detail_value2."',";
								}
								
							}
							array_push($detail_strs,$detail_str);
						}
						
						if(!empty($header_str) && !empty($detail_strs)){
							$transaction = $this->getMaster()->beginTransaction();
							echo("[".date('c')."] "." RecSalseOrderConfirm insert into confirm. order_id: " . $shipmentID . " \n");
							$sql = "INSERT INTO ecshop.brand_gymboree_sales_order_confirm(Warehouse,ShipmentID,Company,ACTUAL_SHIP_DATE_TIME," .
									"Carrier,User_def2,User_def7,Customer_Address1,User_def4,Order_type,created_time,updated_time) " .
								" VALUES(".$header_str."NOW(),NOW())";
							if(!$this->getMaster()->createCommand($sql)->execute()){
								echo("[".date('c')."] "." RecSalseOrderConfirm insert confirm fail! fail_insert_sql :".$sql."\n");
								$transfer_errors .="[".date('c')."] "." insert confirm fail! fail_insert_sql :".$sql."\n";
								$transaction->rollback();
								continue ;
							}
							foreach($detail_strs as $detail_str){
								echo("[".date('c')."] "." RecSalseOrderConfirm insert into confirm_detail. order_id: " . $shipmentID . " \n");							
								$sql = "INSERT INTO ecshop.brand_gymboree_sales_order_confirm_detail(COMPANY,ShipmentID,Item,shipped_qty," .
										"User_def1,Order_type,User_def3,User_def2,created_time,updated_time) " .
								" VALUES(".$detail_str."NOW(),NOW())";
								if(!$this->getMaster()->createCommand($sql)->execute()){
									echo("[".date('c')."] "." RecSalseOrderConfirm insert details fail! fail_insert_sql :".$sql."\n");
									$transfer_errors .="[".date('c')."] "." insert confirm_details fail! fail_insert_sql :".$sql."\n";
									$transaction->rollback();
									continue 2;
								}
							}
							$transaction->commit();
						}
					}
				}
				echo("[".date('c')."] "." RecSalseOrderConfirm insert sales confirm_details end \n");
			}
		}
		if($transfer_errors != ""){
			$this->sendMail("【Gymboree搬仓】【ERROR】RecSalseOrderConfirm",$transfer_errors);
			echo("[".date('c')."] "." 【ERROR】RecSalseOrderConfirm".$transfer_errors."\n");
		}
		global $db;
		$sql ="select soc.*,soi.carrier soi_carrier from ecshop.brand_gymboree_sales_order_confirm soc " .
			" inner join ecshop.brand_gymboree_sales_order_info soi on soi.shipmentID = soc.shipmentID " .
			" left join ecshop.ecs_order_info oi on oi.order_id = soi.shipmentID " .
			" where oi.order_status=1 and oi.shipping_status=0 and oi.party_id ='65574' and soc.transfer_status='WMS_SHIP'" .
			"  and soi.transfer_status='SENDED'  and oi.order_time>'2015-05-09 9:59:00'  " .
			" order by oi.order_time ";
		$shipment_infos = $db->getAll($sql);
		require_once(ROOT_PATH . 'admin/includes/lib_order_mixed_status.php');
		if(!empty($shipment_infos)){
			echo("[".date('c')."] "." RecSalseOrderConfirm sales inventory_out begin \n");
			foreach($shipment_infos as $shipment_info){
				$order_id = $shipment_info['ShipmentID'];
				//将订单虚拟出库并将transfer_status改掉
				$order_list_sql = "select og.rec_id as order_goods_id,og.goods_id,og.style_id,og.goods_number,og.order_id,oi.facility_id " .
						"  from ecshop.ecs_order_info oi  " .
						" LEFT JOIN ecshop.ecs_order_goods og on og.order_id = oi.order_id " .
						" where oi.order_id = '{$order_id}'";
				$order_list = $db->getAll($order_list_sql);	
	
				$transfer_note = '';	
				foreach($order_list as $goods){
					$is_equal = "select count(*) from ecshop.brand_gymboree_sales_order_confirm_detail d 
						INNER JOIN ecshop.ecs_goods_style gs on gs.barcode = d.Item and gs.is_delete=0
						where d.ShipmentID = '{$order_id}' and gs.goods_id = '{$goods['goods_id']}' and gs.style_id = '{$goods['style_id']}' and d.shipped_qty = '{$goods['goods_number']}' limit 1";
					$equal = $db->getOne($is_equal);
					if($equal==0) {
						$transfer_note = $order_id.'零售单实绩反馈发货数量与零售单数量不等！';	
						$order_list = array();
						break;
					}
				}
				if($transfer_note ==''){
					foreach ($order_list as $item ) {
						$info = check_out_goods($item);
			 			if ($info['msg'] != 'success') {
				 			$transfer_note = $order_id."零售单实绩，erp系统出库出错！message_check_out_goods: {$info['back']}";
				 			break;
						}
					}
				}
				
				if ($transfer_note =='' && check_batch_out_storage_status($order_id)) {
					
			 		$sql1 = "update ecshop.brand_gymboree_sales_order_confirm set transfer_status='ERP_SHIP',updated_time=now() where ShipmentID = '{$order_id}'";
			 		$sql2 = "update ecshop.brand_gymboree_sales_order_info set transfer_status='ERP_SHIP',updated_time=now() where ShipmentID = '{$order_id}'"; 
		 			$tracking_number = $shipment_info['User_def2'];
		 			$sql = "select os.shipment_id from ecshop.ecs_order_info oi  
		 				  left join romeo.order_shipment os on os.order_id = convert(oi.order_id USING utf8)  
		 				  where oi.order_id = '{$order_id}' ";
		 			$info = $db->getRow($sql);
		 			$sql = "select shipping_id,shipping_name,carrier_id from ecshop.brand_gym_giessen_shipping_carrier where carrier_code = '{$shipment_info['Carrier']}' limit 1 ";
				 	$shipping_new = $db->getRow($sql);
		 			$shipping_name = $shipping_new['shipping_name'];
			 		$shipping_id = $shipping_new['shipping_id'];
			 		$carrier_id = $shipping_new['carrier_id'];
			 		//$carrier_code-->shipping_id
					if($shipment_info['soi_carrier'] != $shipment_info['Carrier']){
				 		$sql = "select shipping_name from ecshop.ecs_order_info where order_id = '{$order_id}' limit 1";
				 		$shipping_name_old = $db->getOne($sql);
						$orderActionNote = " 修改快递方式：从{$shipping_name_old}改为{$shipping_name} 运单号：{$tracking_number}";
					}else{
						$orderActionNote = " 运单号：{$tracking_number}";
					}
					$action_note = "giessen操作发货" . $orderActionNote;
					$db->start_transaction();
					
					$tracking_numbers = explode('#',$tracking_number);
	            	$bill_no = trim($tracking_numbers[0]);
					$sql_order_ship = "UPDATE ecshop.ecs_order_info SET shipping_status = 1,shipping_id='{$shipping_id}',shipping_time = UNIX_TIMESTAMP() where order_id = {$order_id}";
		 			$sql_shipment ="UPDATE romeo.shipment set tracking_number='{$bill_no}',shipment_type_id='{$shipping_id}',carrier_id='{$carrier_id}'   where shipment_id = '{$info['shipment_id']}'";
					$sql_action = "INSERT INTO ecshop.ecs_order_action(order_id, order_status, shipping_status, pay_status, action_time," .
							" action_note, action_user) VALUES ('{$order_id}', '1', '1', '2', NOW(), '{$action_note}', 'system')";
					if ($db->query($sql1) && $db->query($sql2) && $db->query($sql_order_ship) && $db->query($sql_shipment) &&  $db->query($sql_action)) {
						
						echo("[".date('c')."] "."RecSalseOrderConfirm inventory_out success.order_id:".$order_id."shipmentTypeId:".$shipping_id."trackingNumber".trim($bill_no));
						/**
						 * tracking_number 多面单 处理，参考追加面单页面add_order_shipment.php
						 */
						try {
							if(count($tracking_numbers)!=1){
								unset($tracking_numbers[0]); 
								foreach($tracking_numbers as $key=>$value){
			            			$handle=soap_get_client('ShipmentService');
			            			$object=$handle->createShipment(array(
			            		        'orderId' => $order_id,
			            				'partyId' => '65574',
			            				'shipmentTypeId'=>$shipping_id,
			            				'carrierId'=>$carrier_id,
			            				'trackingNumber'=>trim($value),
			            				'createdByUserLogin'=>'system'
			            			));
			            			
			            			$handle->createOrderShipment(array(
			            				'orderId'=>$order_id,
			            				'shipmentId'=>$object->return,
			            			));
			            			echo("[".date('c')."] "."RecSalseOrderConfirm more tracking_numbers.order_id:".$order_id."shipmentTypeId:".$shipping_id."trackingNumber".trim($value)."\n");
								}
							}
							update_order_mixed_status($order_id, array('warehouse_status'=>'delivered'), 'worker');							
						} catch (Exception $e) {
							$transfer_note = "ERP订单状态更新失败！order_id:".$order_id;
							$db->rollback();
						}
						$db->commit();
					} else {
			 			$transfer_note = "已成功出库，订单状态更新失败！shipmentID:".$order_id;
			 			echo("[".date('c')."] "."RecSalseOrderConfirm  renew status fail. order_id:".$order_id."\n");
			 			$db->rollback();
					}
			 		
			 	}else if($transfer_note ==''){
			 		$transfer_note = "零售单实绩，erp系统订单未完全出库！order_id:".$order_id;
			 	}
			 	if($transfer_note!=''){
			 		$this->sendMail("【Gymboree搬仓】【ERROR】RecSalseOrderConfirm","[".date('c')."] "." RecSalseOrderConfirm ".$transfer_note);
			 		echo("[".date('c')."] "."【ERROR】RecSalseOrderConfirm".$transfer_note."\n");
			 		$sql3 = "update ecshop.brand_gymboree_sales_order_info set transfer_status='FAIL',transfer_note='{$transfer_note}',updated_time=now() where shipmentID = '{$order_id}'"; 
			 		$db->query($sql3);
			 		
			 	}
			}
			echo("[".date('c')."] "." RecSalseOrderConfirm sales inventory_out end \n");
		}
		echo("[".date('c')."] "." RecSalseOrderConfirm end \n");
	}

	//店存出库—用于专库或残次品出库（-gt） 
	public function actionGetStorageOut($hours=6){
		$strBeginTime = date('YmdHi',strtotime("-".$hours." hours"));
        $strEndTime = date('YmdHi',time());
        echo("[".date('c')."] "." GetStorageOut start \n");
      	echo("[".date('c')."] "." GetStorageOut startTime:".$strBeginTime.", endDate:".$strEndTime."\n");
		$insert_flag = true;
		try {
			$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
			$response = $sc->GetStorageOut(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD,'strBeginTime'=>$strBeginTime,'strEndTime'=>$strEndTime));
		}catch (Exception $e){
			$insert_flag = false;
			echo("[".date('c')."] "." GetStorageOut fail to connect! "." Exception: " . $e->getMessage()."\n");
			$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageOut","[".date('c')."] "." GetStorageOut fail to connect! "." Exception: " . $e->getMessage());
		}
		if($insert_flag){
			echo("[".date('c')."] "." GetStorageOut insert giessen_storage_out begin \n");
			$str = $response->GetStorageOutResult->any;
			$document = simplexml_load_string($str);
			$error_info = $document->NewDataSet->Details->ErrorInfo;
			if($error_info != "" && $error_info != "No Data!"){
				$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageOut","访问GetStorageOut接口错误".$error_info);
				echo("[".date('c')."] "." 【ERROR】GetStorageOut! ". $error_info."\n");
			}else if($error_info == "" ){
				$transfer_errors = "";
				$array = array();
				foreach($document->NewDataSet->Details as $aa){
					$data_li = array();
					$data_li['fchWarehouseID'] = trim($aa->fchWarehouseID);
					if($data_li['fchWarehouseID']==""){
						$transfer_errors.=" fchWarehouseID is null !! \n";
						continue;
					}
					$data_li['Item'] = trim($aa->Item);
					if($data_li['Item']==""){
						$transfer_errors.=" Item is null !! \n";
						continue;
					}
					$data_li['Total_qty'] = trim($aa->Total_qty);
					if($data_li['Total_qty']==""){
						$transfer_errors.=" Total_qty is null !! \n";
						continue;
					}
					$data_li['inout_time'] = trim($aa->inout_time);
					if($data_li['inout_time']==""){
						$transfer_errors.=" inout_time is null !! \n";
						continue;
					}
					$data_li['User_def4'] = trim($aa->User_def4);
					$data_li['move_to'] = trim($aa->move_to);
					if($data_li['move_to']==""){
						$transfer_errors.=" move_to is null !! \n";
						continue;
					}
					$data_li['ERP_vouch_code'] = trim($aa->ERP_vouch_code);
					if($data_li['ERP_vouch_code']==""){
						$transfer_errors.=" ERP_vouch_code is null !! \n";
						continue;
					}
					array_push($array,$data_li);
				}
				
				foreach($array as $value){
					//1.需要先排除已存在的数据ecshop.brand_gymboree_giessen_storage_out
					$erp_vouch_code = $value['ERP_vouch_code'];
					$item = $value['Item'];
					$sql = "select count(1) from ecshop.brand_gymboree_giessen_storage_out where ERP_vouch_code = '{$erp_vouch_code}' and Item='{$item}' limit 1";
					$exist_ERP_vouch_code = $this->getMaster()->createCommand($sql)->queryScalar();
					if($exist_ERP_vouch_code!=0){
						continue;
					}
					//2.组装insert数据
					$str = "";
					foreach($value as $key=>$value2){
						if($key=="Total_qty"){
							$str .= $value2.","; 
						}else{
							$str .= "'".$value2."',";
						}
					}
					if($str!=""){
						$sql = "INSERT INTO ecshop.brand_gymboree_giessen_storage_out(fchrWarehouseID,Item,Total_qty,inout_time," .
							" User_def4,move_to,ERP_vouch_code,created_time,updated_time) " .
							" VALUES(".$str."NOW(),NOW())";
						$this->getMaster()->createCommand($sql)->execute();
					}
				}
				if($transfer_errors != ""){
					$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageOut",$transfer_errors);
					echo("[".date('c')."] "." 【ERROR】GetStorageOut ".$transfer_errors."\n");
				}
			}
			echo("[".date('c')."] "." GetStorageOut insert giessen_storage_out end \n");
		}
		
		//店存出库—用于专库或残次品出库（-gt）对NORMAL的订单进行-gt申请，审核通过，生成订单号并出库
		$sql = "select ggso.brand_gymboree_giessen_storage_out_id out_id,bgw.fchrWhCode,bgw.fchrWhName,bgw.fchrWarehouseID,   
				 ggso.Item barcode,Total_qty total_qty, t.product_id,p.product_name,ggso.move_to    
	    		from ecshop.brand_gymboree_giessen_storage_out ggso    
	    		left join ecshop.brand_gymboree_warehouse bgw on bgw.fchrWhCode = ggso.move_to    
	    		left join (select product_id,if(gs.barcode is null or gs.barcode=null,g.barcode,gs.barcode) barcode from romeo.product_mapping pm
					inner join ecshop.ecs_goods_style gs on gs.goods_id = pm.ecs_goods_id and gs.style_id = pm.ecs_style_id and gs.is_delete=0
					inner join ecshop.ecs_goods g on g.goods_id = pm.ecs_goods_id
					where gs.is_delete = 0 and g.goods_party_id = '65574'
					) as t on t.barcode = ggso.Item 
				left join romeo.product p on p.product_id = t.product_id    
	    		where ggso.transfer_status='NORMAL' ";  
		$goods_list = $this->getMaster()->createCommand($sql)->queryAll();
    	if(!empty($goods_list)){
    		echo("[".date('c')."] "." GetStorageOut SUPPLIER_RETURN begin \n");
    		require_once(ROOT_PATH . 'RomeoApi/lib_inventory.php');
			require_once(ROOT_PATH . 'RomeoApi/lib_supplier_return.php');
    		foreach($goods_list as $key=>$goods){
    			$error_transfer_note = '';
    			$facility_id = GYMBOREE_FACILITY_ID;
    			$provider_id = '479';
				if($goods['move_to']=='GYM50'){
			    	$INV_STTS_STATUS = 'INV_STTS_USED' ;//二手库出
			    }else{
			    	$INV_STTS_STATUS = 'INV_STTS_AVAILABLE' ;//一般按照全新处理
			    }
    			$sql = "select unit_cost from romeo.inventory_item 
					where quantity_on_hand_total > 0 and facility_id = '{$facility_id}' 
					and  product_id = '{$goods['product_id']}'
				    and status_id = '{$INV_STTS_STATUS}' and provider_id = '{$provider_id}' ";
			   	$unit_price = $this->getMaster()->createCommand($sql)->queryScalar();
				if(empty($unit_price)){
					$error_transfer_note = "乐其系统中商品库存不足，请立即核实！！！";
			   	}else if(empty($goods['fchrWarehouseID'])){
    				$error_transfer_note = "乐其系统中找不到对应退货库";
    			}else if(empty($goods['product_id'])){
    				$error_transfer_note = "乐其系统中找不到对应商品";
    			}else{
    				try{
    					$return_items = array() ;
						$item = new stdClass();
			    	    $item -> amount = 0 ; 
			    	    $item -> serialNumber = null ;
			    	    $item -> supplierReturnOrderSn = null ;
				    	$return_items[] = $item ;
				    	
				    	$ret_req = new stdClass();
				    	$ret_req -> inventoryItemTypeId = 'NON-SERIALIZED' ;
					    $ret_req -> taxRate = 1.17;//折扣   是否需要
					    $ret_req -> excutedAmount = 0 ;
					    $ret_req -> returnOrderAmount = $goods['total_qty'];
					    $ret_req -> orderTypeId = 'SUPPLIER_RETURN';
					    $ret_req -> returnSupplierId = $provider_id; 
					    $ret_req -> originalSupplierId = $provider_id; //采购供应商，与退还供应商相同处理
					    $ret_req -> paymentTypeId = 1; //1.银行付款 2.现金
					    $ret_req -> partyId = '65574';
					    $ret_req -> facilityId = $facility_id;  
					    $ret_req -> productId = $goods['product_id'] ; 
					    if($goods['move_to']=='GYM50'){
					    	$ret_req -> statusId = 'INV_STTS_USED' ;//二手库出
					    }else{
					    	$ret_req -> statusId = 'INV_STTS_AVAILABLE' ;//一般按照全新处理
					    }
					    
					    
					    $ret_req -> unitPrice = $unit_price ;
					    $ret_req -> currency = 'RMB' ;
					    $ret_req -> purchaseUnitPrice =  $unit_price;  
					    $ret_req -> createdUserByLogin = 'giessen';
					    $ret_req -> fchrWarehouseID = $goods['fchrWarehouseID'];  
				    	$ret_req -> note = "金宝贝退货库ID:".$goods['fchrWarehouseID'].":".$goods['fchrWhName'].":".'giessen店存出库';//$obj_data->remark;
				    	
				    	$result = create_supplier_return_request($ret_req, $return_items);
			    		$supRetReqId = $result->return;
    				}catch(SoapFault $e){
    					$error_transfer_note = "退货申请创建失败";
    				}
    				if(preg_match("/^\d*$/",$supRetReqId)){
    					create_supplier_return_order($supRetReqId,"giessen");
    					$sql = "select supplier_return_gt_sn from romeo.supplier_return_request_gt where supplier_return_id = '{$supRetReqId}'";
    					$supplier_return_gt_sn = $this->getMaster()->createCommand($sql)->queryScalar();
    					
    					if(!$supplier_return_gt_sn){
    						//如果失败，则取消申请
    						$error_transfer_note = "-gt审核与订单号生成失败";
    						$sql = "update romeo.supplier_return_request 
			     	            set check_status = 'DENY',check_user = 'system'
			     	            where supplier_return_id = '{$supRetReqId}'
			     	       ";
			     	     	$result =$this->getMaster()->createCommand($sql)->execute();
    					}else{
    						$out_id = $goods['out_id'];
    						$transaction = $this->getMaster()->beginTransaction();
    						$sql = "update ecshop.brand_gymboree_giessen_storage_out set leqee_erp_gt_sn='{$supplier_return_gt_sn}',updated_time=NOW() where brand_gymboree_giessen_storage_out_id = '{$out_id}' ";
    						$result = $this->getMaster()->createCommand($sql)->execute();
    						//4.-gt出库
    						$ret_req = new stdClass();
							$ret_req -> supplierReturnRequestId = $supRetReqId;
							$ret_req -> partyId = '65574';
							$supplier_return_request = get_supplier_return_request($ret_req); 
							$supRetReq = $supplier_return_request[$supRetReqId];
							$out_num = $goods['total_qty'];
							$message = deliver_supplier_return_order_inventory ($supRetReq, $out_num);
							echo("[".date('c')."] "." GetStorageOut SUPPLIER_RETURN"."leqee_erp_gt_sn:".$supplier_return_gt_sn." \n");
							if($message=="出库成功"){
								$sql = "update ecshop.brand_gymboree_giessen_storage_out set transfer_status='SUCCESS',updated_time=NOW() where brand_gymboree_giessen_storage_out_id = '{$out_id}' ";
    							$result = $this->getMaster()->createCommand($sql)->execute();
    							$transaction->commit();
							}else{
								$transaction->rollback();
								$error_transfer_note = $message;
//    							$this->log("errorInfo = ".$message."\n");
							}
    					}
    				}else{
    					$error_transfer_note = "退货申请创建失败";
//    					$this->log("errorInfo = 退货申请创建失败 \n");
    				}
    			}
    			if($error_transfer_note!=""){
    				$error_transfer_note = mysql_escape_string($error_transfer_note);
    				$out_id = $goods['out_id'];
    				$sql = "update ecshop.brand_gymboree_giessen_storage_out set transfer_status='FAIL',transfer_note='{$error_transfer_note}',updated_time=NOW() where brand_gymboree_giessen_storage_out_id = '{$out_id}' ";
    				$result = $this->getMaster()->createCommand($sql)->execute();
    				$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageOut", $error_transfer_note."product:".$goods['product_id']."product_name:".$goods['product_name']." \n" );
    				echo("[".date('c')."] "."【ERROR】GetStorageOut". $error_transfer_note."product:".$goods['product_id']." \n" );
    			}
    		}
    		echo("[".date('c')."] "." GetStorageOut SUPPLIER_RETURN end \n");
    	}
    	echo("[".date('c')."] "." GetStorageOut end \n");
	}
	//其他出入库（-v盘点）
	public function actionGetStorageOutOthers($hours=3){
        $strBeginTime = date('YmdHi',strtotime("-".$hours." hours"));
        $strEndTime = date('YmdHi',time());
        echo("[".date('c')."] "." GetStorageOutOthers start \n");
      	echo("[".date('c')."] "." GetStorageOutOthers startTime:".$strBeginTime.", endDate:".$strEndTime."\n");
		$insert_flag = true;
		try {
			$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
			$response = $sc->GetStorageOutOthers(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD,'strBeginTime'=>$strBeginTime,'strEndTime'=>$strEndTime));
		}catch (Exception $e){
			$insert_flag = false;
			$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageOutOthers","[".date('c')."] "." fail to connect! "." Exception: " . $e->getMessage());
		}
		if($insert_flag){
			echo("[".date('c')."] "." GetStorageOutOthers insert giessen_storage_out_others begin \n");
			$str = $response->GetStorageOutOthersResult->any;
			$document = simplexml_load_string($str);
			$error_info = $document->NewDataSet->Details->ErrorInfo;
			if($error_info != "" && $error_info != "No Data!"){
				$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageOutOthers","访问GetStorageOut接口错误".$error_info." \n");
			}else if($error_info == "" ){
				$array = array();
				$transfer_errors = "";
				foreach($document->NewDataSet->Details as $aa){
					//inout_type","Item","Total_qty","inout_time","ERP_vouch_code
					$data_li = array();
					$data_li['inout_type'] = trim($aa->inout_type);
					if($data_li['inout_type']==""){
						$transfer_errors .= " inout_type is null !! \n";
						continue;
					}
					$data_li['Item'] = trim($aa->Item);
					if($data_li['Item']==""){
						$transfer_errors .= " Item is null !! \n";
						continue;
					}
					$data_li['Total_qty'] = trim($aa->Total_qty);
					if($data_li['Total_qty']==""){
						$transfer_errors .= " Total_qty is null !! \n";
						continue;
					}
					$data_li['inout_time'] = trim($aa->inout_time);
					if($data_li['inout_time']==""){
						$transfer_errors .= " inout_time is null !! \n";
						continue;
					}
					$data_li['User_def4'] = trim($aa->User_def4);
					$data_li['ERP_vouch_code'] = trim($aa->ERP_vouch_code);
					if($data_li['ERP_vouch_code']==""){
						$transfer_errors .= " ERP_vouch_code is null !! \n";
						continue;
					}
					array_push($array,$data_li);
				}
				if($transfer_errors != ""){
					$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageOutOthers",$transfer_errors);
				}
				if(!empty($array)){
					foreach($array as $value){
						//1.需要先排除已存在的数据ecshop.brand_gymboree_giessen_storage_out（根据ERP_vouch_code和Item共同决定）
						$erp_vouch_code = $value['ERP_vouch_code'];
						$item = $value['Item']; 
						$sql = "select count(1) from ecshop.brand_gymboree_giessen_storage_out_others where ERP_vouch_code = '{$erp_vouch_code}' and Item = '{$item}' limit 1";
						$exist_ERP_vouch_code = $this->getMaster()->createCommand($sql)->queryScalar();
						if($exist_ERP_vouch_code!=0){
							continue;
						}
						//2.组装insert数据
						$str = "";
						foreach($value as $key=>$value2){
							if(in_array($key,array("inout_type","Item","inout_time","User_def4","ERP_vouch_code"))){
								$str .= "'".$value2."',";
							}else if($key=="Total_qty"){
								$str .= $value2.","; 
							}
						}
						if($str!=""){
							$sql = "INSERT INTO ecshop.brand_gymboree_giessen_storage_out_others(inout_type,Item,Total_qty,inout_time," .
								" User_def4,ERP_vouch_code,created_time,updated_time) " .
								" VALUES(".$str."NOW(),NOW())";
							$this->getMaster()->createCommand($sql)->execute();
						}
					}
				}
			}
			echo("[".date('c')."] "." GetStorageOutOthers insert giessen_storage_out_others end \n");
		}
		//其他出入库（-v盘点） -v申请审核并操作
		$sql = "select brand_gymboree_giessen_storage_out_others_id ooid,inout_type,User_def4,if(egs.goods_id is null or egs.goods_id = '',g.goods_id,egs.goods_id) goodsId," .
				" if(egs.style_id is null or egs.style_id = '',0,egs.style_id)  styleId,Total_qty goodsCount,inout_time " .
				" from ecshop.brand_gymboree_giessen_storage_out_others soo " .
				" left join ecshop.ecs_goods_style egs on egs.barcode = soo.Item and egs.is_delete=0 " .
				" left join ecshop.ecs_goods g on g.barcode = soo.Item " .
				" where soo.transfer_status='NORMAL' ";
		$goods_inventory_out_list = $this->getMaster()->createCommand($sql)->queryAll();
    	if(!empty($goods_inventory_out_list)){
    		echo("[".date('c')."] "." GetStorageOutOthers VARIANCE begin \n");
    		foreach($goods_inventory_out_list as $key=>$goods){
    			$comment = "giessen其他出入库"; //调整原因
				$facility_id=GYMBOREE_FACILITY_ID;//调整仓库，暂时与出库保持统一
				$goods_inventory_out_list[$key]['statusId'] = (isset($goods['User_def4'])&&!empty($goods['User_def4']))?trim($goods['User_def4']):'INV_STTS_AVAILABLE';
				$goods_inventory_out_list[$key]['purchase_paid_amount'] = 0.00; 
				$admin_id = 947;
				$order_type_id = ($goods['inout_type']=='VARIANCE_IN')?'VARIANCE_ADD':'VARIANCE_MINUS';
				$result_back = $this->create_inventory_virance_order($comment,$facility_id,$goods_inventory_out_list[$key],$order_type_id,$admin_id);
				$error_message = '';
				if(preg_match("/^\d*$/",$result_back)){
					$sql = "select oi.order_sn,og.rec_id from ecshop.ecs_order_info oi " .
							" inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id " .
							" where oi.order_id = '{$result_back}'";
					$order_infos = $this->getMaster()->createCommand($sql)->queryRow();
					$order_sn = $order_infos['order_sn'];
					$order_goods_id = $order_infos['rec_id'];
					
					$sql = "update ecshop.brand_gymboree_giessen_storage_out_others set leqee_erp_v_sn='{$order_sn}',updated_time=NOW() where brand_gymboree_giessen_storage_out_others_id = '{$goods['ooid']}' ";
    				$result = $this->getMaster()->createCommand($sql)->execute();
    				$result = $this->deliver_inventory_virance_order_inventory($order_goods_id);
    				if(!($result)||($result->get("status")->stringValue != 'OK')){
				    	$error_message = ('盘点错误 orderGoodsId:'.$order_goods_id);
				    	//删除申请
					    $sql = "delete from ecshop.ecs_order_goods where rec_id = '{$order_goods_id}'";
					    $this->getMaster()->createCommand($sql)->execute();
				    }else{
				    	$sql = "update ecshop.brand_gymboree_giessen_storage_out_others set transfer_status='SUCCESS',updated_time=NOW() where brand_gymboree_giessen_storage_out_others_id = '{$goods['ooid']}' ";
    					$result = $this->getMaster()->createCommand($sql)->execute();
				    }
				}else{
					$error_message = "-v申请创建失败：".$result_back;
				}
				if($error_message !=''){
					$sql = "update ecshop.brand_gymboree_giessen_storage_out_others set transfer_status='FAIL',transfer_note='{$error_message}',updated_time=NOW() where brand_gymboree_giessen_storage_out_others_id = '{$goods['ooid']}' ";
    				$result = $this->getMaster()->createCommand($sql)->execute();
    				$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageOutOthers","[".date('c')."] "." error :".$error_message." \n");
				}
    		}
    		echo("[".date('c')."] "." GetStorageOutOthers VARIANCE end \n");
    	}
    	 echo("[".date('c')."] "." GetStorageOutOthers end \n");
	}
	//店存入库 （-c）from gymboree 存储记录
	public function actionGetStorageIn(){
		global $db;
		$dir = ROOT_PATH."../gymboree";
		$gymboree_array = listDir($dir);
		echo("[".date('c')."] "." GetStorageIn start \n");
		foreach($gymboree_array as $filename){
			$result = array();
			$gymboree_vouch_file_name = $filename['item_value'];
			$filename = ROOT_PATH."../gymboree/".$filename['item_value'].".xml";
			echo("[".date('c')."] "." GetStorageIn filename:".$filename." start \n");
			$error_infos = "";
			//加载并将对应关系存储在数据库中
			$result = $this->load_file($filename);
			if($result['error']==''){
				$content = $result['content'];
				$fchrVouch = $result['fchrVouch'];
				/*
				 * gymboree 暂时不用店间调货，后期可能加入，暂时不使用表ecshop.brand_gymboree_storage_in_vouch，同时不对GiessenStorageIn，GymboreePurchaseIn配置调度
				 * by ytchen 20150415
				 */
				/**
				 * 
				$db->start_transaction();   
				//全量插入到数据表中
				if(!$this->GymboreeInsertSql($fchrVouch[0],"ecshop.brand_gymboree_storage_in_vouch")){
					$db->rollback();
					continue;
				}
				foreach($content as $key=>$value){
					if(!$this->GymboreeInsertSql($value,"ecshop.brand_gymboree_storage_in_vouch_detail")){
						$db->rollback();
						continue 2;
					}		
				}*/
				
				$sql = "insert into ecshop.brand_gymboree_inoutvouch (fchrInOutVouchID,filename,is_send,create_timeStamp,upload_timeStamp)
					VALUES ('{$fchrVouch[0]['fchrInOutVouchID']}','{$gymboree_vouch_file_name}','false',NOW(),NOW());
				";
		        if(!$db->query($sql)){
		            $db->rollback();
		            continue;
		        }
		       /**
		        *  $db->commit();
		        //如果为大仓发货，直接操作入库
		        if($fchrVouch[0]['fchrMemo']==''){
		        */
		        	$vouchID = $fchrVouch[0]['fchrInOutVouchID'];
					$warehouse = $fchrVouch[0]['fchrWarehouseID']; 
		        	$error_infos = $this->DoStorageIn($vouchID,$warehouse,$content,$gymboree_vouch_file_name);
		        	if($error_infos ==''){
			        	$sql = "update ecshop.brand_gymboree_inoutvouch set is_send = 'true',upload_timeStamp = NOW() where fchrInOutVouchID = '{$vouchID}'";
						$db->query($sql);
		        	}else{
		        		$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageIn","[".date('c')."] "." GetStorageIn inventory_in error:".$error_infos." \n");
		        	}
		        /**}*/
		        
			}else{
				$this->sendMail("【gymboree搬仓】【ERROR】GetStorageIn","[".date('c')."] "." GetStorageIn error:".$result['error']." \n");
	            continue;
			}
			echo("[".date('c')."] "." GetStorageIn filename:".$filename." end \n");
		} 
		echo("[".date('c')."] "." GetStorageIn end \n");
	}
	
	/*
	 * 店存入库 （-c） from giessen 存储记录 ,暂未使用
	 */
	/**
	public function actionGiessenStorageIn($strBeginTime,$strEndTime){
		try {
			$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
			$response = $sc->GetStorageIn(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD,'strBeginTime'=>$strBeginTime,'strEndTime'=>$strEndTime));
		}catch (Exception $e){
			$this->sendMail("[GiessenStorageIn]GetStorageIn fail to connect ", "Call ".GYMBOREE_WEBSERVICE_URL."GetStorageIn Exception: " . $e->getMessage());
			$this->log("GiessenStorageIn fail! \n");
		}
		$str = $response->GetStorageInResult->any;
		$document = simplexml_load_string($str);
		$error_info = $document->NewDataSet->Details->ErrorInfo;
		if($error_info != ""){
			echo("访问GetStorageIn接口错误".$error_info." \n");
		}else{
			$detail_array = array();
			foreach($document->NewDataSet->Details as $aa){
				//fchWarehouseID','Item','Total_qty','inout_time','receive_from','ERP_vouch_code','GY_code
				$data_li = array();
				$data_li['fchWarehouseID'] = trim($aa->fchWarehouseID);
				if($data_li['fchWarehouseID']==""){
					echo("店存入库-关键字段fchWarehouseID不允许为空,请联系基森查询!");
					continue;
				}
				$data_li['Item'] = trim($aa->Item);
				if($data_li['Item']==""){
					echo("店存入库-关键字段Item不允许为空,请联系基森查询!");
					continue;
				}
				$data_li['Total_qty'] = trim($aa->Total_qty);
				if($data_li['Total_qty']==""){
					echo("店存入库-关键字段Total_qty不允许为空,请联系基森查询!");
					continue;
				}
				$data_li['inout_time'] = trim($aa->inout_time);
				if($data_li['inout_time']==""){
					echo("店存入库-关键字段inout_time不允许为空,请联系基森查询!");
					continue;
				}
				$data_li['User_def4'] = trim($aa->User_def4);
				$data_li['receive_from'] = trim($aa->receive_from);
				if($data_li['receive_from']==""){
					echo("店存入库-关键字段receive_from不允许为空,请联系基森查询!");
					continue;
				}
				$data_li['ERP_vouch_code'] = trim($aa->ERP_vouch_code);
				if($data_li['ERP_vouch_code']==""){
					echo("店存入库-关键字段ERP_vouch_code不允许为空,请联系基森查询!");
					continue;
				}
				$data_li['GY_code'] = trim($aa->GY_code);
				if($data_li['GY_code']==""){
					echo("店存入库-关键字段GY_code不允许为空,请联系基森查询!");
					continue;
				}
				array_push($detail_array,$data_li);
			}
			if(!empty($array)){
				$detail_strs = array();
				foreach($detail_array as $key=>$detail_value){
					$detail_str = "";
					foreach($detail_value as $detail_key=>$detail_value2){
						if($detail_key=='Total_qty'){
							$detail_str .= $detail_value2.","; 
						}else{
							$detail_str .= "'".$detail_value2."',";
						}
					}
					array_push($detail_strs,$detail_str);
				}
				
				if(!empty($detail_strs)){
					$transaction = $this->getMaster()->beginTransaction();
					foreach($detail_strs as $detail_str){
						$sql = "INSERT INTO ecshop.brand_gymboree_giessen_storage_in(fchWarehouseID,Item,Total_qty,inout_time,User_def4,receive_from," .
								"ERP_vouch_code,GY_code,created_time,updated_time) " .
						" VALUES(".$detail_str."NOW(),NOW())";
						if(!$this->getMaster()->createCommand($sql)->execute()){
							$transaction->rollback();
							continue;
						}
					}
					$transaction->commit();
				}
			}
		}
	}
	*/
	
	/*
	 * 店存入库 之 店间调拨  暂不上线且不做测试
	 *  by ytchen 20150415
	 */
	 /**
	public function actionGymboreePurchaseIn(){
		global $db;
		$sql_si_header = "select GY_code,receive_from,count(Item) count_si from ecshop.brand_gymboree_giessen_storage_in" .
				" where transfer_status = 'NORMAL' and GY_code is not null and GY_code != '' " .
				" group by GY_code,receive_from";
		$giessen_ins = $db->getAll($sql_si_header);
		foreach($giessen_ins as $giessen_in){
			$fchrMemo = $giessen_in['GY_code'];
			$item_count_si = $giessen_in['count_si'];
			$fchrWhCode = $giessen_in['receive_from'];
			$sql_iv_header = "select iv.fchrInOutVouchID,iv.fchrWarehouseID,count(fchrBarCode) count_iv,i.filename from ecshop.brand_gymboree_storage_in_vouch iv " .
					" inner join ecshop.brand_gymboree_storage_in_vouch_detail ivd on ivd.fchrInOutVouchID = iv.fchrInOutVouchID " .
					" inner join ecshop.brand_gymboree_inoutvouch i on i.fchrInOutVouchID = iv.fchrInOutVouchID " .
					" where iv.fchrMemo = '{$fchrMemo}' and iv.fchrWhCode = '{$fchrWhCode}' " .
					" group by iv.fchrInOutVouchID,iv.fchrWarehouseID "; 
			$gymboree_in = $db->getRow($sql_iv_header);
			if(empty($gymboree_in)){
				$this->log("金宝贝单据号为".$fchrMemo.",调拨来源为".$fchrWhCode."的xml文件没有找到！ \n");
				$this->sendMail("[GymboreePurchaseIn] errorInfo ", "金宝贝单据号为".$fchrMemo.",调拨来源为".$fchrWhCode."的xml文件没有找到！ \n");
				continue;
			}else if($gymboree_in['count_iv'] != $item_count_si){
				$this->log("金宝贝单据号为".$fchrMemo.",调拨来源为".$fchrWhCode."的xml文件与基森反馈barcode数量不等！ \n");
				$this->sendMail("[GymboreePurchaseIn] errorInfo ", "金宝贝单据号为".$fchrMemo.",调拨来源为".$fchrWhCode."的xml文件与基森反馈barcode数量不等！ \n");
				continue;
			}
			$sql_item_num = "select ivd.fchrBarCode,sum(ivd.goods_number) goods_number ,sum(si.Total_qty) giessen_num " .
				" from ecshop.brand_gymboree_storage_in_vouch_detail ivd " .
				" inner join ecshop.brand_gymboree_giessen_storage_in si on si.GY_code = '{$fchrMemo}' and si.Item =ivd.fchrBarCode " .
				" where ivd.fchrInOutVouchID = '{$gymboree_in['fchrInOutVouchID']}' " .
				" group by ivd.fchrBarCode " .
				" having goods_number >= giessen_num"; 
			$items = $db->getAll($sql_item_num);	
			if(count($items)!= $item_count_si){
				$this->log("金宝贝单据号为".$fchrMemo.",调拨来源为".$fchrWhCode."的xml文件与基森反馈barcode匹配异常，请ERP查询！ \n");
				$this->sendMail("[GymboreePurchaseIn] errorInfo ", "金宝贝单据号为".$fchrMemo.",调拨来源为".$fchrWhCode."的xml文件与基森反馈barcode匹配异常，请ERP查询！ \n");
				continue;
			}
			//组合content
			$content = array();
			foreach($items as $key=>$item){
				$giessen_num = $item['giessen_num']; 
				$sql = " select gymboree_vouch_detailID, goods_number,goods_name,goods_id,color,style_id,purchase_paid_amount,purchase_added_fee " .
					" from ecshop.brand_gymboree_storage_in_vouch_detail where fchrBarCode = '{$item['fchrBarCode']}' and return_number = 0";
				$ivd_items = $db->getAll($sql);
				foreach($ivd_items as $key=>$ivd_item){
					if($ivd_item['goods_number'] < $giessen_num){
						$content[] = $ivd_item;
						$giessen_num -= $ivd_item['goods_number'];
					}else{
						$ivd_items[$key]['goods_number'] = $giessen_num;
						$content[] = $ivd_items[$key];
						break;
					}
				}
			}
			var_dump("content:");
			var_dump($content);
			
			$error_infos = $this->DoStorageIn($gymboree_in['fchrInOutVouchID'],$gymboree_in['fchrWarehouseID'],$content,$gymboree_in['filename']);
			//将出库数据记录到数据库中，并将giessen回传normal->received
			if($error_infos ==''){
				foreach($content as $key=>$value){
					$sql[] = "update ecshop.brand_gymboree_storage_in_vouch_detail set return_number = '{$value['goods_number']}' where gymboree_vouch_detailID = '{$value['gymboree_vouch_detailID']}'";
				}
				$sql[] = "update ecshop.brand_gymboree_storage_in_vouch set transfer_status='RECEIVED' where fchrInOutVouchID = '{$vouchID}' "; 
				$sql[] = "update ecshop.brand_gymboree_giessen_storage_in set transfer_status = 'RECEIVED' where GY_code = '{$fchrMemo}' ";
				$sql[] = "update ecshop.brand_gymboree_inoutvouch set is_send = 'true',upload_timeStamp = NOW() where fchrInOutVouchID = '{$vouchID}'";
				$transaction = $this->getMaster()->beginTransaction();
				foreach($sql as $sql_){
					if(!$db->query($sql_)){
						$transaction->rollback();
					}
				}
				$transaction->commit();
			}else{
				$this->log("[GymboreePurchaseIn] errorInfo".$error_infos);
				$this->sendMail("[GymboreePurchaseIn] errorInfo ", $error_infos);
			}
		}
			
	}
	*/
	//店存入库 - 具体操作
	public function DoStorageIn($vouchID,$warehouse,$content,$gymboree_vouch_file_name){
		
		global $db;
		$error_infos = "";
		$result = array();
		//-c申请
		$provider_id = '479';
	    $sql = "select provider_order_type from ecshop.ecs_provider where provider_id = '{$provider_id}' limit 1";
	    $order_type = $db->getOne($sql);
		$db->start_transaction();        //开始事务
        $facility_id = GYMBOREE_FACILITY_ID;
		do {
			$batch_order_sn = get_batch_order_sn(); //获取新订单号
	        $sql = "INSERT INTO ecshop.ecs_batch_order_info
	                    (batch_order_sn, party_id, facility_id, order_time, purchase_user,in_storage_user,is_cancelled,is_in_storage,currency,
	                    provider_id,purchaser,order_type,action_user,provider_order_sn, provider_out_order_sn, inventory_type, remark
	                    )
	                    VALUES('{$batch_order_sn}', '65574', '{$facility_id}', NOW(),'gymboree','','N','N','RMB',
	                    '{$provider_id}','system','{$order_type}','gymboree','' , '', '', ''
	                    )";	
			$db->query ( $sql, 'SILENT' );
			$error_no = $db->errno ();
			if ($error_no > 0 && $error_no != 1062) {
	            $db->rollback();
	            $this->log("InOutVouchFromGymboree_err_no:2-".$gymboree_vouch_file_name."采购订单生成失败 \n");
            	$error_infos .= " err_no:2-".$gymboree_vouch_file_name."采购订单生成失败 \n" ;
            	continue;
			}
		} while ( $error_no == 1062 ); //如果是订单号重复则重新提交数据
		    $batch_order_id = $db->insert_id();
  $this->log("batch_order_sn：".$batch_order_sn." ; batch_order_id:".$batch_order_id."\n");
        $total_pay = 0;    // 计算总采购费
	    $rebate_strategy_data = array();    // 用于返利策略分配的数据
	    $status_id = 'INV_STTS_AVAILABLE';
        foreach($content as $cont){
        	$goods_name = $cont['goods_name'];
        	$goods_id = $cont['goods_id'];
        	$color = $cont['color'];
        	$style_id = $cont['style_id'];
        	$goods_number = $cont['goods_number'];
        	$purchase_paid_amount = $cont['purchase_paid_amount'];
        	$customized = 'false';
        	$purchase_added_fee = $cont['purchase_added_fee'];
        	$gymboree_vouch_detailID=$cont['gymboree_vouch_detailID'];
        	if (!$goods_id || !$goods_number) {
	            $this->log("InOutVouchFromGymboree_err_no:3-".$gymboree_vouch_file_name."采购订单生成失败，商品信息不存在\n");
        		$error_infos .= " err_no:3-".$gymboree_vouch_file_name."采购订单生成失败，商品信息不存在\n" ;
            	continue 2; 
        	}
	        
	        $pay = $purchase_paid_amount*$goods_number;
	        $total_pay += $pay;
//        		$sql = "SELECT user_id FROM oukoo_universal.ok_user WHERE user_name = '{$_SESSION['admin_name']}'";
//        		$uuid = $db->getOne($sql);
	        
	        $error_no = 0;
	        do {
	            $order_sn = get_order_sn() . "-c"; //获取新订单号
	            $sql = "INSERT INTO ecshop.ecs_order_info 
	                    (order_sn, order_time, order_status, pay_status, user_id, 
	                    party_id, facility_id, currency, order_type_id)
	                    VALUES('{$order_sn}', NOW(), 2, 2, '0',                      
	                    '65574', '{$facility_id}', 'RMB', 'PURCHASE')";
	            $db->query($sql, 'SILENT');
	            $error_no = $db->errno();
	            if ($error_no > 0 && $error_no != 1062) {
	                $db->rollback();
		            $this->log("InOutVouchFromGymboree_err_no:4-".$gymboree_vouch_file_name."采购订单生成失败 \n");
                	$error_infos .= " err_no:4-".$gymboree_vouch_file_name."采购订单生成失败 \n" ;
            		continue 3;
	            }
	        } while ($error_no == 1062); //如果是订单号重复则重新提交数据
	        $sqls[] = $sql;
	        $order_id = $db->insert_id();
//        $this->log("order_sn：".$order_sn." ; order_id:".$order_id."\n");
			//记录采购订单信息
	        $sql = "INSERT INTO romeo.purchase_order_info
	                    (order_id, purchase_paid_amount, purchaser, order_type, is_serial)
	                    VALUES('{$order_id}', '{$purchase_paid_amount}', 'gymboree', 'B2C', 'N')";
	        if(false == $db->query($sql, 'SILENT')){
	            $db->rollback();
	            $this->log("InOutVouchFromGymboree_err_no:5-".$gymboree_vouch_file_name."采购订单生成失败 \n");
                $error_infos .= " err_no:5-".$gymboree_vouch_file_name."采购订单生成失败 \n" ;
            	continue 2;
	        }
	        $purchase_order_id = $db->insert_id();
//		$this->log("purchase_order_id:".$purchase_order_id."\n");	        
	        //将采购订单号插入到此批次采购订单映射表中 
	        $sql = "INSERT INTO ecshop.ecs_batch_order_mapping (batch_order_id, order_id)
	                    VALUES('{$batch_order_id}', '{$order_id}')";
	        if(false == $db->query($sql, 'SILENT')){
	            $db->rollback();
	            $this->log("InOutVouchFromGymboree_err_no:6-".$gymboree_vouch_file_name."采购订单生成失败 \n");
                $error_infos .= " err_no:6-".$gymboree_vouch_file_name."采购订单生成失败 \n"  ;
            	continue 2;
	        }
	        $db->insert_id();
//		  $this->log("batch_order_mapping \n");              
        	//金宝贝特殊处理
        	$sql = "insert into ecshop.order_attribute (order_id,attr_name,attr_value) 
        			values ('{$order_id}','Gymboree_Warehouse_ID','{$warehouse}'),
        			('{$order_id}','gymboree_vouchID','{$vouchID}'),
        			('{$order_id}','gymboree_vouch_detailID','{$gymboree_vouch_detailID}')";
            if(false == $db->query($sql)){
                $db->rollback();
                $this->log("InOutVouchFromGymboree_err_no:7-".$gymboree_vouch_file_name."采购订单生成失败 \n");
                $error_infos .= " err_no:7-".$gymboree_vouch_file_name."采购订单生成失败 \n" ;
            	continue 2;
            }
	        // 返利策略数据
	        $rebate_strategy_data[] = array('order_id' => $order_id, 'pay' => $pay);
	
	        $sql = "SELECT * FROM ecshop.ecs_goods WHERE goods_id = '$goods_id'";
	        $goods = $db->getRow($sql);
	
	        if ($style_id > 0) {
	            $sql = "SELECT *, IF (gs.goods_color = '', s.color, gs.goods_color) AS color FROM ecshop.ecs_goods_style gs, ecshop.ecs_style s WHERE gs.goods_id = '{$goods['goods_id']}' AND gs.style_id = s.style_id and gs.is_delete=0 AND s.style_id = '{$style_id}'";
	            $style = $db->getRow($sql);
	            $goods['goods_name'] .= " {$style['color']}";
    			$goods['shop_price'] = $style['style_price'];
	        }
	
	        //对order_goods表数据进行修改
	        $goods_name = addslashes($goods['goods_name']);
	        $sql = "INSERT INTO ecshop.ecs_order_goods (order_id, goods_id, goods_name, goods_number, goods_price, style_id, customized, added_fee, status_id) 
	        					VALUES('{$order_id}', '{$goods_id}', '{$goods_name}', '{$goods_number}', '{$goods['shop_price']}', {$style_id}, '{$customized}', '{$purchase_added_fee}', '{$status_id}')";
	        if(false == $db->query($sql)){
	            $db->rollback();
	            $this->log("InOutVouchFromGymboree_err_no:9-".$gymboree_vouch_file_name."采购订单生成失败 \n");
                $error_infos .= " err_no:9-".$gymboree_vouch_file_name."采购订单生成失败 \n";
            	continue 2;
	        }
	        $order_goods_id = $db->insert_id();
	        $sqls[] = $sql;
//		$this->log("order_goods_id:".$order_goods_id."\n");	        
	        // 把供价记录到价格跟踪系统中去
	        $sql = "SELECT goods_style_id FROM ecshop.ecs_goods_style WHERE goods_id = '{$goods_id}' AND style_id = '{$style_id}' and is_delete=0 ";
	        $goods_style_id = $db->getOne($sql);
	        $sql = "INSERT INTO PRICE_TRACKER.PROVIDER_PRICE (GOODS_ID, goods_style_id, PROVIDER_ID, SUPPLY_PRICE, CREATED_BY, CREATED_DATETIME) " .
	        		"VALUE ('$goods_id', '$goods_style_id', '{$provider_id}', '$purchase_paid_amount', '$uuid', NOW())";
	        if(false == $db->query($sql)){
	            $db->rollback();
	            $this->log("InOutVouchFromGymboree_err_no:11-".$gymboree_vouch_file_name."采购订单生成失败 \n");
                $error_infos .= " err_no:11-".$gymboree_vouch_file_name."采购订单生成失败 \n" ;
            	continue 2;
	        }
        }
        if(!$batch_order_id){
        	$db->rollback();
        	$this->log("InOutVouchFromGymboree--ERROR: 没有生成批次订单！请重试".$gymboree_vouch_file_name."\n");
            $this->sendMail("[InOutVouchFromGymboree] ERROR mes","没有生成批次订单！请重试".$gymboree_vouch_file_name."\n" );
            continue;    
        }
        //采购入库
        $c_order_ids = "select og.goods_number,og.order_id,oi.facility_id " .
				" from ecshop.ecs_batch_order_mapping m " .
				" LEFT JOIN ecshop.ecs_order_info oi on oi.order_id = m.order_id" .
				" LEFT JOIN ecshop.ecs_order_goods og on og.order_id = m.order_id" .
			" where m.batch_order_id = '{$batch_order_id}' ";
		$order_ins = $db->getAll($c_order_ids);
//	    		$this->log("c_order_ids_sql :".$c_order_ids);
		foreach($order_ins as $order_in){
			//入库
			global $is_command;
			$is_command = true;
			$result2 = actual_inventory_in($order_in['order_id'], $order_in['goods_number'], true, 'INV_STTS_AVAILABLE', $order_in['facility_id'], 'GymboreeCommand');
			if($result2['res'] == 'fail'){
				$db->rollback();
				$this->log("InOutVouchFromGymboree_err_no:12-".$gymboree_vouch_file_name." 采购入库失败-".$result2['back']."\n");
                $error_infos .= " err_no:12-".$gymboree_vouch_file_name." 采购入库失败-".$result2['back']."\n" ;
            	continue 2;
			}
		}
		$sql1 = "update ecshop.ecs_batch_order_info set is_in_storage = 'Y' where batch_order_id = '{$batch_order_id}' ";
		$sql2 = "update ecshop.ecs_batch_order_mapping set is_in_storage = 'Y' where batch_order_id = '{$batch_order_id}' ";
		if(!($db->query($sql1) && $db->query($sql2))){
			$error_infos .="batch_order_id:".$batch_order_id."  采购订单入库状态更改失败 \n";
		}
		$db->commit();
		return $error_infos;
	}
  	
	//退货指示 - 筛选已发货的退货订单 -->ecshop.brand_gymboree_return_order_info
	public function actionReturnStorageOrder(){
		echo("[".date('c')."] "." ReturnStorageOrder start \n");
		global $db;
		$Warehouse = '1200';
		$sql = "SELECT distinct(oi.order_id),oi1.order_sn,oi1.taobao_order_sn  
			FROM ecshop.ecs_order_info oi
			INNER JOIN ecshop.service s on s.back_order_id = oi.order_id
			INNER JOIN ecshop.ecs_order_info oi1 on oi1.order_id = s.order_id 
			left join ecshop.brand_gymboree_return_order_info groi on groi.Receipt_ID = oi.order_id  
			LEFT JOIN ecshop.brand_gymboree_sales_order_info gsoi on gsoi.shipmentID = s.order_id 
			inner join ecshop.ecs_order_goods og on oi.order_id = og.order_id 
			left join ecshop.ecs_goods_style gs on og.goods_id = gs.goods_id and og.style_id = gs.style_id and gs.is_delete=0
			where oi.party_id = '65574'  and oi.order_type_id = 'RMA_RETURN'
			and s.service_type IN ('1', '2')  AND s.service_status = '1' AND s.back_shipping_status IN(0,5)
			and groi.return_order_info_id is null and gsoi.transfer_status = 'ERP_SHIP' limit 50";
		$orders = $this->getMaster()->createCommand($sql)->queryAll();
		if(!empty($orders)){
			echo("[".date('c')."] "." ReturnStorageOrder insert return info_goods start \n");
			foreach($orders as $order){
				$order_id = $order['order_id'];
				$goods_sql = "select rec_id,if(gs.barcode is NULL or gs.barcode = '',g.barcode,gs.barcode) barcode," .
						"sum(og.goods_number) as goods_number,og.goods_name,s.color " .
						" from ecshop.ecs_order_goods og" .
						" left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0 " .
						" left join ecshop.ecs_goods g on g.goods_id = og.goods_id " .
						" left join ecshop.ecs_style s on s.style_id = gs.style_id " .
						" where og.order_id = '{$order_id}' " .
						" group by barcode";
				$order_goods = $this->getMaster()->createCommand($goods_sql)->queryAll();
				if(empty($order_goods)){
					continue;
				}
	    		$transaction = $this->getMaster()->beginTransaction();
				$return_order_array = array(
					'Warehouse' => $Warehouse,
					'Interface_Record_ID' => $order['order_sn'],
					'Receipt_ID' => $order['order_id'],
					'Company' =>'GYMBOREE',
					'Receipt_ID_Type' => 'RMA_IN',
					'User_def1' => $order['taobao_order_sn'],
				);
				if(!$this->GymboreeInsertSql($return_order_array,"ecshop.brand_gymboree_return_order_info")){
					$this->sendMail("【Gymboree搬仓】【ERROR】ReturnStorageOrder ","[".date('c')."] "." insert return order info fail! order_id :".$order['order_id']."\n");
					$transaction->rollback();
					continue;
				}
				foreach($order_goods as $good){
					$return_good_array = array(
						'Interface_Link_ID' => $order['order_id'],
						'Interface_Record_ID' => $good['rec_id'],
						'Company' => 'GYMBOREE',
						'Item' => $good['barcode'],
						'Total_qty' => $good['goods_number'],
						'Goods_Name' => mysql_escape_string($good['goods_name']),
						'Style_Name' => $good['color'],
					);
					if(!$this->GymboreeInsertSql($return_good_array,"ecshop.brand_gymboree_return_order_goods")){
						$this->sendMail("【Gymboree搬仓】【ERROR】ReturnStorageOrder ","[".date('c')."] "." insert return_order_goods fail! order_id :".$order['order_id'].",rec_id :".$good['rec_id']."\n");
						echo("[".date('c')."] "."【ERROR】ReturnStorageOrder "." insert return_order_goods fail! order_id :".$order['order_id'].",rec_id :".$good['rec_id']."\n");
						$transaction->rollback();
						continue 2;
					}
				}
				$transaction->commit();
			}
			echo("[".date('c')."] "." ReturnStorageOrder insert return info_goods end \n");
		}
		$sql = "select soi.* from ecshop.brand_gymboree_return_order_info soi " .
				" left join ecshop.ecs_order_info oi on oi.order_id = soi.Receipt_ID " .
				"where soi.transfer_status = 'NORMAL' ";
		$return_orders = $this->getMaster()->createCommand($sql)->queryAll();
		if(!empty($return_orders)){
			echo("[".date('c')."] "." ReturnStorageOrder send return info_goods begin \n");
		
			foreach($return_orders as $return_order){
				$Receipt_ID = $return_order['Receipt_ID'];
				$sql = "select Interface_Link_ID,Interface_Record_ID,Company,Item,Goods_Name,Style_Name,ITEM_LIST_PRICE, 
					Total_qty,ITEM_COLOR,ITEM_SIZE,User_def1,ERP_Order_Line_Num,ERP_ORDER_TYPE,User_def3
					from ecshop.brand_gymboree_return_order_goods   
					where Interface_Link_ID = '{$Receipt_ID}'";
				$return_goods = $this->getMaster()->createCommand($sql)->queryAll();
				if(empty($return_goods)){
					echo("[".date('c')."] "." ReturnStorageOrder send fail! lost Receipt_ID :".$Receipt_ID." \n");
					continue;
				}
				$strReturn = '{"Header":[{';
				foreach($return_order as $key2=>$value2){
					if(!in_array($key2,array('return_order_info_id','transfer_status','transfer_note','created_time','updated_time'))){
						$strReturn .='"'.$key2.'":"'.$value2.'",';
					}
				}
				$strReturn = substr($strReturn,0,-1).'}],"Details":[';
				foreach($return_goods as $value){
					$strReturn .='{';
					foreach($value as $key2=>$value2){
						$strReturn .='"'.$key2.'":"'.$value2.'",';
					}
					$strReturn = substr($strReturn,0,-1)."},";
				}
				$strReturn = substr($strReturn,0,-1)."]}";
				$send_flag = true;
				echo("[".date('c')."] "."ReturnStorageOrder info:".$strReturn);
				try {
					$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
					$response = $sc->ReturnStorageXML(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD,'strReturn'=>$strReturn));
				} catch (Exception $e) {
					$send_flag = false;
					$this->sendMail("【Gymboree搬仓】【ERROR】ReturnStorageOrder ","[".date('c')."] "." connect fail! connect Exception :".$e->getMessage()."\n");
				}
				if($send_flag){
					$str = $response->ReturnStorageXMLResult->any;
					if(strstr($str,"ErrorType")){
						$b= (stripos($str,"<ErrorInfo>"));
						$c= (stripos($str,"</ErrorInfo>"));
						//搜索到errorInfo
						$length = strlen("<ErrorInfo>");
						$error_info=substr($str,$b+$length,$c-$b-$length);
						$this->sendMail("【Gymboree搬仓】【ERROR】ReturnStorageOrder ","[".date('c')."] "." errorInfo :".$error_info."\n");
					}else{
						$b= (stripos($str,"<STATUS>"));
						$c= (stripos($str,"</STATUS>"));
						$length = strlen("<STATUS>");
						$return = substr($str,$b+$length,$c-$b-$length);
						if($return=='True'){
							$sql = "update ecshop.brand_gymboree_return_order_info set transfer_status='SENDED',updated_time=NOW() where return_order_info_id = '{$return_order['return_order_info_id']}' ";
						}else if($return=='False'){
							$sql = "update ecshop.brand_gymboree_return_order_info set transfer_status='FAIL',transfer_note='strReturn传输错误',updated_time=NOW() where return_order_info_id = '{$return_order['return_order_info_id']}' ";
							$this->sendMail("【Gymboree搬仓】【ERROR】ReturnStorageOrder ","[".date('c')."] "." error strSalsInfo :".$strReturn."\n");
						}
						$this->getMaster()->createCommand($sql)->execute();
					}
				}
			}
			echo("[".date('c')."] "." ReturnStorageOrder send return info_goods end \n");
		}
		echo("[".date('c')."] "." ReturnStorageOrder end \n");
	}
	
	//退货实绩 
	public function actionRecReturnConfirm($hours=2){
        $strBeginTime = date('YmdHi',strtotime("-".$hours." hours"));
        $strEndTime = date('YmdHi',time());
        echo("[".date('c')."] "." RecReturnConfirm start \n");
      	echo("[".date('c')."] "." RecReturnConfirm startTime:".$strBeginTime.", endDate:".$strEndTime."\n");
		
		$insert_flag = true;
		try {
			$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
			$response = $sc->RecReturnConfirm(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD,'strBeginTime'=>$strBeginTime,'strEndTime'=>$strEndTime));
		}catch (Exception $e){
			$insert_flag = false;
			$this->sendMail("【Gymboree搬仓】【ERROR】RecReturnConfirm ","[".date('c')."] "." RecReturnConfirm fail to connect | Exception: " . $e->getMessage());
		}
		if($insert_flag){
			echo("[".date('c')."] "." RecReturnConfirm insert return confirm_details begin \n");
			$str = $response->RecReturnConfirmResult->any;
			$document = simplexml_load_string($str);
			$error_info = $document->NewDataSet->Details->ErrorInfo;
			if($error_info != "" && $error_info != "No Data!"){
				$this->sendMail("【Gymboree搬仓】【ERROR】RecReturnConfirm ","访问RecSalseOrderConfirm接口错误".$error_info);
			}else if($error_info == "" ){
				$header_array = array();
				$transfer_errors = "";
				foreach($document->NewDataSet->Header as $aa){
					$header_li = array();
					$header_li['Receipt_ID'] = trim($aa->Receipt_ID);
					if($header_li['Receipt_ID']==""){
						$transfer_errors .=" Receipt_ID is null !! \n";
						continue;
					}
					$header_li['Receipt_ID_Type'] = trim($aa->Receipt_ID_Type);
					
					$header_li['Company'] = trim($aa->Company);
					$header_li['ARRIVED_DATE_TIME'] = trim($aa->ARRIVED_DATE_TIME);
					if($header_li['ARRIVED_DATE_TIME']==""){
						$transfer_errors .=" ARRIVED_DATE_TIME is null !! \n";
						continue;
					}
					$header_li['User_def1'] = trim($aa->User_def1);
					array_push($header_array,$header_li);
				}
				$detail_array = array();
				if(empty($document->NewDataSet->Details) && !empty($document->NewDataSet->Header)){
					$transfer_errors .=" 实绩中只有header数据，details数据丢失 !  \n";
				}
				foreach($document->NewDataSet->Details as $de){
					$detail_li = array();
					$detail_li['Receipt_ID'] = trim($de->Receipt_ID);
					if($detail_li['Receipt_ID']==""){
						$transfer_errors .="details Receipt_ID is null !! \n";
						continue;
					}
					$detail_li['ERP_Order_Line_Num'] = trim($de->ERP_Order_Line_Num);
					$detail_li['ORDER_TYPE'] = trim($de->ORDER_TYPE);
					$detail_li['Company'] = trim($de->Company);
					$detail_li['Item'] = trim($de->Item);
					if($detail_li['Item']==""){
						$transfer_errors .="details Item is null !! \n";
						continue;
					}
					$detail_li['Total_qty'] = trim($de->Total_qty);
					if($detail_li['Total_qty']==""){
						$transfer_errors .="details Total_qty is null !! \n";
						continue;
					}
					$detail_li['User_def4'] = trim($de->User_def4);
					$detail_li['Available_qty'] = trim($de->Available_qty);
					$detail_li['Defective_qty'] = trim($de->Defective_qty);
					array_push($detail_array,$detail_li);
				}
				if($transfer_errors != ""){
					$this->sendMail("【Gymboree搬仓】【ERROR】RecReturnConfirm ",$transfer_errors);
				}
				if(empty($detail_array) || empty($header_array)){
					echo("[".date('c')."] "." RecReturnConfirm empty" );
				}else{
					foreach($header_array as $header_value){
						//1.需要先排除已存在的数据ecshop.brand_gymboree_return_order_confirm
						$Receipt_ID = $header_value['Receipt_ID'];
						$sql = "select count(1) from ecshop.brand_gymboree_return_order_confirm where Receipt_ID = '{$Receipt_ID}' limit 1";
						$exist_Receipt_ID_count = $this->getMaster()->createCommand($sql)->queryScalar();
						if($exist_Receipt_ID_count!=0){
							continue;
						}
						echo("[".date('c')."] "." RecReturnConfirm info: order_id:".$Receipt_ID."\n");
						
						
						//组装sql
						$header_str = "";
						foreach($header_value as $header_key=>$header_value2){
							$header_str .= "'".$header_value2."',";
						}
						$detail_strs = array();
						foreach($detail_array as $key=>$detail_value){
							if($Receipt_ID != $detail_value['Receipt_ID']){
								continue;
							}
							$detail_str = "";
							foreach($detail_value as $detail_key=>$detail_value2){
								if(in_array($detail_key,array('Total_qty','Available_qty','Defective_qty'))){
									$detail_str .= $detail_value2.","; 
								}else{
									$detail_str .= "'".$detail_value2."',";
								}
							}
							array_push($detail_strs,$detail_str);
						}
						
						if(!empty($header_str) && !empty($detail_strs)){
							$transaction = $this->getMaster()->beginTransaction();
							$sql = "INSERT INTO ecshop.brand_gymboree_return_order_confirm(Receipt_ID,Receipt_ID_Type,Company," .
									" ARRIVED_DATE_TIME,User_def1,created_time,updated_time) " .
								" VALUES(".$header_str."NOW(),NOW())";
							if(!$this->getMaster()->createCommand($sql)->execute()){
								$this->sendMail("【Gymboree搬仓】【ERROR】RecReturnConfirm ","[".date('c')."] "." RecReturnConfirm insert confirm fail! fail_insert_sql :".$sql."\n");
								$transaction->rollback();
								continue;
							}
							foreach($detail_strs as $detail_str){
								$sql = "INSERT INTO ecshop.brand_gymboree_return_order_confirm_detail(Receipt_ID,ERP_Order_Line_Num," .
										"ORDER_TYPE,Company,Item,Total_qty,User_def4,Available_qty,Defective_qty,created_time,updated_time) " .
								" VALUES(".$detail_str."NOW(),NOW())";
								if(!$this->getMaster()->createCommand($sql)->execute()){
									$this->sendMail("【Gymboree搬仓】【ERROR】RecReturnConfirm ","[".date('c')."] "." RecReturnConfirm insert details fail! fail_insert_sql :".$sql."\n");
									$transaction->rollback();
									continue;
								}
							}
							$transaction->commit();
						}
					}
				}
			}
			echo("[".date('c')."] "." RecReturnConfirm insert return confirm_details begin \n");
		}
		global $db;
		$facility_id = GYMBOREE_FACILITY_ID;
		$sql = "select s.service_id,og.order_id,og.rec_id,rocd.Item barcode,og.goods_number,rocd.Total_qty,rocd.Available_qty,rocd.Defective_qty,og.goods_name
			from ecshop.brand_gymboree_return_order_confirm groc
			INNER JOIN ecshop.brand_gymboree_return_order_confirm_detail rocd on groc.Receipt_id = rocd.Receipt_ID
			inner join ecshop.ecs_order_info eoi on groc.Receipt_ID = eoi.order_id 
			INNER JOIN ecshop.service s on s.back_order_id = eoi.order_id
			left join ecshop.ecs_goods_style gs on  gs.barcode = rocd.Item and gs.is_delete=0 
			INNER JOIN ecshop.ecs_order_goods og on og.order_id = eoi.order_id and gs.goods_id = og.goods_id  and gs.style_id = og.style_id  
			left join ecshop.ecs_goods g on g.goods_id = og.goods_id and g.barcode = rocd.Item
			where eoi.facility_id='{$facility_id}' and s.service_type IN ('1', '2') AND s.party_id = '65574' AND s.service_status = '1' AND s.back_shipping_status IN(0,5)  and groc.transfer_status = 'WMS_RETURN' limit 50 ";
		$ref_keys = $ref_values = array();
		$db->getAllRefBy($sql,array('service_id'),$ref_keys,$ref_values);
		$orders = $ref_values['service_id'];
		if(!empty($orders)){
			echo("[".date('c')."] "." RecReturnConfirm return INVENTORY_RETURN begin \n");
			foreach($orders as $service_id=>$order){
				$Receipt_id = '';
				foreach($order as $key=>$value){
					if($value['goods_number'] != $value['Total_qty']){
						$this->sendMail("【Gymboree搬仓】【ERROR】RecReturnConfirm ","[".date('c')."] order_id:".$value['order_id']." 实绩反馈退货总数!=申请退货数 \n");
						continue 2;
					}
					if($value['Total_qty'] != ($value['Available_qty']+$value['Defective_qty'])){
						$this->sendMail("【Gymboree搬仓】【ERROR】RecReturnConfirm ","[".date('c')."] 实绩反馈".$value['order_id']."总退货数!=良品+不良品数 \n");
						continue 2;
					}
					$Receipt_id = $value['order_id'];
				}
				$transfer_note="";	
				$info = auto_service($service_id);
				$sql1 = "update ecshop.brand_gymboree_return_order_confirm set transfer_status='ERP_RETURN',updated_time=now() where Receipt_ID = '{$Receipt_id}'";
			 	$sql2 = "update ecshop.brand_gymboree_return_order_info set transfer_status='ERP_RETURN',updated_time=now() where Receipt_ID = '{$Receipt_id}'"; 
				if(!($info['res']=='success' && $db->query($sql1) && $db->query($sql2))){
					$transfer_note = "erp退货失败service_id:".$service_id;
				}else if($info['res']!='success'){
					$transfer_note = "erp已退货，状态更新失败Receipt_id：".$Receipt_id;
				}
				if($transfer_note!=""){
					$sql3 = "update ecshop.brand_gymboree_return_order_info set transfer_status='FAIL',transfer_note='{$transfer_note}',updated_time=now() where Receipt_id = '{$Receipt_id}'"; 
			 		$db->query($sql3);
			 		$this->sendMail("【Gymboree搬仓】【ERROR】RecReturnConfirm ","[".date('c')."] ".$transfer_note." \n");
				}
			}
			echo("[".date('c')."] "." RecReturnConfirm return INVENTORY_RETURN end \n");
		}
	}	
	
	//日在库存数据
	public function actionDailyStorageInformation(){
		echo("[".date('c')."] "." DailyStorageInformation start \n");
		$insert_flag = true;
		try {
			$sc = new SoapClient(GYMBOREE_WEBSERVICE_URL."?wsdl", array('location' => GYMBOREE_WEBSERVICE_URL,'connection_timeout'=>600)); //FILE_WSDL为要访问的https地址;
			$response = $sc->GetStorageInforation(array('strUserId'=>GYMBOREE_USER_ID,'strPassword'=>GYMBOREE_PASSWORD));
		}catch (Exception $e){
			$insert_flag = false;
			$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageInforation ","[".date('c')."] "." DailyStorageInformation fail to connect | Exception: " . $e->getMessage());
		}
		if($insert_flag){
			echo("[".date('c')."] "." DailyStorageInformation insert storage_information begin \n");
			$str = $response->GetStorageInforationResult->any;
			$document = simplexml_load_string($str);
			$error_info = $document->NewDataSet->Details->ErrorInfo;
			if($error_info != "" && $error_info !="No Data!"){
				$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageInforation ","访问GetStorageInforation接口错误".$error_info." \n");
			}else if($error_info==""){
				$array = array();
				$transfer_errors = "";
				foreach($document->NewDataSet->Details as $aa){
					//Warehouse","Company","Item","On_hand_qty","Inventory_Status
					$data_li = array();
					$data_li['Warehouse'] = trim($aa->Warehouse);
					if($data_li['Warehouse']==""){
						$transfer_errors .=" Warehouse is null !! \n";
						continue;
					}
					$data_li['Company'] = trim($aa->Company);
					
					$data_li['Item'] = trim($aa->Item);
					if($data_li['Item']==""){
						$transfer_errors .=" Item is null !! \n";
						continue;
					}
					$data_li['On_hand_qty'] = trim($aa->On_hand_qty);
					if($data_li['On_hand_qty']==""){
						$transfer_errors .=" On_hand_qty is null !! \n";
						continue;
					}
					$data_li['Inventory_Status'] = trim($aa->Inventory_Status);
					
					array_push($array,$data_li);
				}
				if(!empty($array)){
					
				//查询是否存在，存在则update，否则insert
					$transaction = $this->getMaster()->beginTransaction();
					foreach($array as $detail_array){
						$warehouse = $detail_array['Warehouse'];
						$item = $detail_array['Item'];
						$inventory_Status = $detail_array['Inventory_Status'];
						$sql = "select On_hand_qty from ecshop.brand_gymboree_storage_information where Warehouse = '{$warehouse}' and Item='{$item}' and Inventory_Status = '{$inventory_Status}' limit 1";
						$on_hand_qty = $this->getMaster()->createCommand($sql)->queryScalar();
						if(empty($on_hand_qty)){
							if(!$this->GymboreeInsertSql($detail_array,"ecshop.brand_gymboree_storage_information")){
								$transaction->rollback();
								$transfer_errors .="[".date('c')."] "."  insert fail! \n";
								return;
							}
						}else {
							$sql = "update ecshop.brand_gymboree_storage_information set ".
							" On_hand_qty = '{$detail_array['On_hand_qty']}',updated_time = NOW() " .
							" WHERE Warehouse = '{$warehouse}' AND Item = '{$item}' AND Inventory_Status = '{$inventory_Status}' ";
							if(!$this->getMaster()->createCommand($sql)->execute()){
								$transaction->rollback();
								$transfer_errors .="[".date('c')."] "." update fail! \n";
								return;
							}
						}
					}
					$transaction->commit();
				}
				if($transfer_errors != ""){
					$this->sendMail("【Gymboree搬仓】【ERROR】GetStorageInforation ",$transfer_errors);
				}
				echo("[".date('c')."] "." DailyStorageInformation insert storage_information end \n");
			}
		}
		echo("[".date('c')."] "." DailyStorageInformation end \n");
	}
	
	
	/** 取得master数据库连接
     * 
     * @return CDbConnection
     */
    protected function getMaster() {
        if (! $this->master) {
            $this->master = Yii::app()->getDb();
            $this->master->setActive(true);
        }
        return $this->master;
    }
	public function getLastFile($ftp,$file,$lastFile){
		$file_time = ftp_mdtm($ftp,$file);
		$lastFile_time = ftp_mdtm($ftp,$lastFile);
		if($lastFile_time == -1){
			return $file;
		}
		ftp_chdir($ftp,'/ExportFileXml');
	    $file_name = basename($file);    // 获取文件名
	    $lastFile_name = basename($lastFile);
		if($file_name > $lastFile_name){
			$this->removeFile($ftp, $lastFile_name, $lastFile_name, true);
			return $file;
		}else if($file_name <= $lastFile_name){
			$this->removeFile($ftp, $file_name, $file_name, true);
			return $lastFile;
		}
	}
	
	public function actionCheckFiles() {
		global $ftp_url;  //服务器地址
		global $ftp_name; //用户名
		global $ftp_pwd;  //密码
		
		$phpftp_port = "21";// 服务器端口
		$ftp = ftp_connect($ftp_url,$phpftp_port);    // 连接ftp服务器
		
		
		if($ftp) {
			if(ftp_login($ftp, $ftp_name, $ftp_pwd)) {    // 登录
				ftp_pasv($ftp, true);//改变FTP传输数据模式
				$this->checkXmlFile($ftp);
				$this->checkExcelFile($ftp);
			} else {
				$this->sendMessage("ftp服务器登录失败【乐其Gymboree监控】");
			}
		} else {
			$this->sendMessage("ftp服务器连接失败【乐其Gymboree监控】");
		}
	}
	
	public function checkXmlFile($ftp) {
		$RetailVouch = "RetailVouch" . date("Ymd", time());
		$file_name_list = ftp_nlist($ftp, "/ImportReceiptXml/");
		foreach ($file_name_list as $item) {
			$file_name = basename($item);
			if (strstr($file_name, $RetailVouch)) {
				$this->sendMail("对方还未处理xml文件【乐其Gymboree监控】");
				return ;
			}
		}
		
		$file_name_list = ftp_nlist($ftp, "/ImportReceiptXml/Success/");
		foreach ($file_name_list as $item) {
			$file_name = basename($item);
			if (strstr($file_name, $RetailVouch)) {
				return ;
			}
		}
		
		$file_name_list = ftp_nlist($ftp, "/ImportReceiptXml/Failure/");
		foreach ($file_name_list as $item) {
			$file_name = basename($item);
			if (strstr($file_name, $RetailVouch)) {
				$this->sendMail("对方xml文件处理失败【乐其Gymboree监控】");
				return ;
			}
		}
		$this->sendMessage("xml文件未上传ftp，请尽快处理【乐其Gymboree监控】");
	}
	
	public function checkExcelFile($ftp) {
		$file = date("Ymd", time());
		$file_name_list = ftp_nlist($ftp, "/GymboreeOrderReport/");
		foreach ($file_name_list as $item) {
			$file_name = basename($item);
			if (strstr($file_name, $file)) {
				$this->sendMail("对方还未处理excel文件【乐其Gymboree监控】");
				return ;
			}
		}
		
		$file_name_list = ftp_nlist($ftp, "/GymboreeHo/GymboreeOrderReport/");
		foreach ($file_name_list as $item) {
			$file_name = basename($item);
			if (strstr($file_name, $file)) {
				return ;
			}
		}
		
		$this->sendMessage("excel文件未上传ftp，请尽快处理【乐其Gymboree监控】");
	}

	public function actionDownFiles() {
		global $ftp_url;  //服务器地址
		global $ftp_name; //用户名
		global $ftp_pwd;  //密码
		
//		$this->backupFiles();
		
		$phpftp_port = "21";            // 服务器端口
		 
		$ftp = ftp_connect($ftp_url,$phpftp_port);    // 连接ftp服务器
	  
		if($ftp) {
			if(ftp_login($ftp, $ftp_name, $ftp_pwd)) {    // 登录
				ftp_pasv($ftp, true);//改变FTP传输数据模式
				
				$arrFiles = ftp_nlist($ftp, '/ExportFileXml/');
//				$inOutVouch_last = "";
				$item_last = "";
				$itemType_last = "";
				$barCodeRule_last = "";
				$itemAllotAnalysis_last = "";
				$userDefine_last = "";
				$warehouse_last = "";
				$VIPCardClass_last = "";
				
//				$inout_files = $this->listFiles(ROOT_PATH . '../gymboree');
//				$is_down_inout = false;//标志是否有下载过入库通知单
				$down_inout_files = array();
				foreach ($arrFiles as $file) {
					if(strpos($file,"InOutVouch") !== false){
//						$down_inout = true;
//						foreach($inout_files as $inout){//遍历gymboree文件夹，跟欲下载的文件一一进行比对。重复的就不下载
//							if($this->md5FileCheck($inout, $file)){
//								$down_inout = false;
//								break;
//							}
//						}
//						if($down_inout){
//							$is_down_inout = true;
//							$this->downFile($ftp, "ExportFileXml", $file);
//						}
						$file_name = basename($file);
						$this->downFile($ftp, "ExportFileXml", $file_name,"../gymboree/temp/");
						array_push($down_inout_files,$file_name);
						ftp_chdir($ftp,'/ExportFileXml');
						$this->removeFile($ftp, $file_name, $file_name, true);
						continue;
					}elseif(strpos($file,"ItemAllotAnalysis") !== false){
						$itemAllotAnalysis_last = $this->getLastFile($ftp,$file,$itemAllotAnalysis_last);
						continue;
					}elseif(strpos($file,"ItemType") !== false){
						$itemType_last = $this->getLastFile($ftp,$file,$itemType_last);
						continue;
					}elseif(strpos($file,"Item") !== false){
						$item_last = $this->getLastFile($ftp,$file,$item_last);
						continue;
					}elseif(strpos($file,"BarCodeRule") !== false){
						$barCodeRule_last = $this->getLastFile($ftp,$file,$barCodeRule_last);
						continue;
					}elseif(strpos($file,"UserDefine") !== false){
						$userDefine_last = $this->getLastFile($ftp,$file,$userDefine_last);
						continue;
					}elseif(strpos($file,"Warehouse") !== false){
						$warehouse_last = $this->getLastFile($ftp,$file,$warehouse_last);
						continue;
					}elseif(strpos($file,"VIPCardClass") !== false){
						$VIPCardClass_last = $this->getLastFile($ftp,$file,$VIPCardClass_last);
					}
				}
				
				$remove_files = $this->checkDown($down_inout_files);//将没有重复的文件转移到gymboree文件夹
				
//				$inOutVouch_last = basename($inOutVouch_last);
				$item_last = basename($item_last);
				$itemType_last = basename($itemType_last);
				$barCodeRule_last = basename($barCodeRule_last);
				$itemAllotAnalysis_last = basename($itemAllotAnalysis_last);
				$userDefine_last = basename($userDefine_last);
				$warehouse_last = basename($warehouse_last);
				
				if(!$this->downFile($ftp, "LogFile/ExportLog", "ExportLog.txt","../gymboree/")){
					$this->log("请手动创建ExportLog.txt文件");
				}
				
				ftp_cdup($ftp);
				
				//下载入库通知单
				try {
					$mail = Yii::app ()->getComponent ( 'mail' );
					$mail->Subject = "金宝贝入库通知单";
					if($remove_files != ""){//下载文件
						$mail->Body = "您好，金宝贝的入库通知单".$remove_files."已经下载，请及时处理";
						//验证文件准确性,即验证文件是否有某个标签,
						//代码逻辑改变，不进行校验
//						//$this->checkFile($ftp,$inOutVouch_last, "InOutVouchDetail");
					} else{
						$mail->Body = "您好，金宝贝没有传入库通知单";
					}
					$mail->ClearAddresses ();
					$mail->AddAddress ( 'zjli@leqee.com', '李志杰' );
					$mail->AddAddress ( 'mjzhou@leqee.com', '周明杰' );
					$mail->AddAddress ( 'ytchen@leqee.com', '陈艳婷' );
					$mail->send ();
				 } catch ( Exception $e ) {
				 	$this->log ( "发送邮件异常" );
				 }
				//下载商品信息
			    try {
					$mail = Yii::app ()->getComponent ( 'mail' );
					$mail->Subject = "金宝贝入库通知单";
					if($this->downFile($ftp, "ExportFileXml", $item_last,"../gymboree/") && $this->downFile($ftp, "ExportFileXml", $itemType_last,"../gymboree/") &&
					   $this->downFile($ftp, "ExportFileXml", $barCodeRule_last,"../gymboree/") && $this->downFile($ftp, "ExportFileXml", $warehouse_last,"../gymboree/") &&
					   $this->downFile($ftp, "ExportFileXml", $userDefine_last,"../gymboree/") && $this->downFile($ftp, "ExportFileXml", $itemAllotAnalysis_last,"../gymboree/")	){//下载文件
					   	$mail->Body = "您好，金宝贝的商品信息已经下载，请及时处理";
						//验证文件准确性,即验证文件是否有某个标签,如果文件无误，则调用处理文件的方法
						if(($this->checkFile($ftp,$item_last, "Item") && $this->checkFile($ftp,$itemType_last, "ItemType")
						&& $this->checkFile($ftp,$barCodeRule_last, "BarCodeRule") && $this->checkFile($ftp,$warehouse_last, "Warehouse")
						&& $this->checkFile($ftp,$userDefine_last, "UserDefine") && $this->checkFile($ftp,$itemAllotAnalysis_last, "ItemAllotAnalysis"))){
							$this->gymboreeItemInsert($item_last,$itemType_last,$barCodeRule_last,$warehouse_last);
						}
					} else{
						$mail->Body = "您好，金宝贝没有传商品信息或商品信息同步失败，请关注近期采购入库商品";
					}
					$mail->ClearAddresses ();
					$mail->AddAddress ( 'zjli@leqee.com', '李志杰' );
					$mail->AddAddress ( 'mjzhou@leqee.com', '周明杰' );
					$mail->AddAddress ( 'ytchen@leqee.com', '陈艳婷' );
					$mail->send ();
				 } catch ( Exception $e ) {
				 	$this->log ( "发送邮件异常" );
				 }
			}
			$this->uploadFile($ftp, "LogFile/ExportLog/ExportLog.txt", ROOT_PATH."../gymboree/ExportLog.txt");
		}
		ftp_quit($ftp);
	}

	public function gymboreeItemInsert($item_file,$itemType_file,$barcode_file,$warehouse_file){
		$fileDir = ROOT_PATH."../gymboree/";
		 
		$nowdate = time();
		$date=date("Y-m-d H:i:s",$nowdate);

		$is_insert = true;

		$error_message = "";
			
		$item = simplexml_load_file($fileDir.$item_file);
		$item_type = simplexml_load_file($fileDir.$itemType_file);
		$BarCodeRule = simplexml_load_file($fileDir.$barcode_file);
		
		$arr_item = array();
		foreach($item->Item->Row as $ItemDetail){
			$arr_item_detail = array();
			$arr_item_detail['fchrItemID'] = "".$ItemDetail->fchrItemID;
			$arr_item_detail['fchrItemName'] = "".$ItemDetail->fchrItemName;
			$arr_item_detail['fchrAddCode'] = "".$ItemDetail->fchrAddCode;
			$arr_item_detail['fbitNoUsed'] = "".$ItemDetail->fbitNoUsed;
			$arr_item_detail['fchrBarCode'] = "".$ItemDetail->fchrBarCode;
			$arr_item_detail['fchrItemCode'] = "".$ItemDetail->fchrItemCode;
			$arr_item_detail['fchrUnitName'] = "".$ItemDetail->fchrUnitName;
			$arr_item_detail['flotQuotePrice'] = "".$ItemDetail->flotQuotePrice;
			$arr_item_detail['fchrItemTypeID'] = "".$ItemDetail->fchrItemTypeID;
			$arr_item_detail['fchrItemID'] = "".$ItemDetail->fchrItemID;
			if(!array_key_exists ("".$ItemDetail->fchrItemID,$arr_item)){
				$arr_item["".$ItemDetail->fchrItemID] = $arr_item_detail;
			}else{
				$is_insert = false;
				$error_message .= "Item.xml数据有重复，";
				$this->addLog($item."数据有重复");
			}
		}
		//释放内存，这个变量占据内存很大，用完马上释放
		$item = null;
		 
		$arr_itemType = array();
		foreach ($item_type->ItemType->Row as $ItemTypeDetail){
			$arr_itemType_detail = array();
			$arr_itemType_detail['fchrItemTypeID'] = "".$ItemTypeDetail->fchrItemTypeID;
			$arr_itemType_detail['fchrItemTypeName'] = "".$ItemTypeDetail->fchrItemTypeName;
			if(!array_key_exists ("".$ItemTypeDetail->fchrItemTypeID,$arr_itemType)){
				$arr_itemType["".$ItemTypeDetail->fchrItemTypeID] = $arr_itemType_detail;
			}else{
				$is_insert = false;
				$error_message .= "ItemType.xml数据有重复，";
				$this->addLog($item_type."数据有重复");
			}
		}
		//释放内存，这个变量占据内存很大，用完马上释放
		$item_type = null;
		 
		$arr_barcodeRule = array();
		foreach ($BarCodeRule->BarCodeRuleCollateDetail->Row as $barcodeDetail){
			if ($barcodeDetail->fchrItemID == "08000000-0001-db10-0000-0000e3e13ea0" && $barcodeDetail->fchrFree2 == "130/59P") {
				$barcodeDetail->fchrFree2 = "130/59";
			}
			$arr_barcodeRule_detail = array();
			$arr_barcodeRule_detail['fchrItemID'] = "".$barcodeDetail->fchrItemID;
			$arr_barcodeRule_detail['fchrBarCodeNO'] = "".$barcodeDetail->fchrBarCodeNO;
			$arr_barcodeRule_detail['fchrProduceNO'] = "". $barcodeDetail->fchrProduceNO;
			$arr_barcodeRule_detail['fdtmProduceDate'] = "".$barcodeDetail->fdtmProduceDate;
			$arr_barcodeRule_detail['fchrFree2'] = "".$barcodeDetail->fchrFree2;
			$arr_barcodeRule_detail['flotQuantity'] = "".$barcodeDetail->flotQuantity;
			$arr_barcodeRule_detail['flotMoney'] = "".$barcodeDetail->flotMoney;
			if(!array_key_exists ("".$barcodeDetail->fchrBarCodeNO, $arr_barcodeRule)){
				$arr_barcodeRule["".$barcodeDetail->fchrBarCodeNO] = $arr_barcodeRule_detail;
			}else{
				$is_insert = false;
				$error_message .= "BarcodeRule.xml数据有重复，";
				$this->addLog($barcode_file."数据有重复");
			}
		}
		//释放内存，这个变量占据内存很大，用完马上释放 
		$BarCodeRule = null;
		 
		$arr_product = array();
		$n = 0;
		try {
			foreach ($arr_barcodeRule as $barcode){
				$arr_product_detail = array();
				$arr_product_detail['fchrItemID'] = $barcode['fchrItemID'];
				$arr_product_detail['fchrItemName'] = $arr_item[$barcode['fchrItemID']]['fchrItemName'];
				$arr_product_detail['fchrAddCode'] = $arr_item[$barcode['fchrItemID']]['fchrAddCode'];
				$arr_product_detail['fbitNoUsed'] = $arr_item[$barcode['fchrItemID']]['fbitNoUsed'];
				$arr_product_detail['fchrItemTypeName'] = $arr_itemType[$arr_item[$barcode['fchrItemID']]['fchrItemTypeID']]['fchrItemTypeName'];
				$arr_product_detail['fchrBarCode'] =  $arr_item[$barcode['fchrItemID']]['fchrBarCode'];
				$arr_product_detail['fchrItemCode'] =  $arr_item[$barcode['fchrItemID']]['fchrItemCode'];
				$arr_product_detail['fchrUnitName'] =  $arr_item[$barcode['fchrItemID']]['fchrUnitName'];
				$arr_product_detail['flotQuotePrice'] =  $arr_item[$barcode['fchrItemID']]['flotQuotePrice'];
				$arr_product_detail['fchrBarCodeNO'] = $barcode['fchrBarCodeNO'];
				$arr_product_detail['fchrProduceNO'] = $barcode['fchrProduceNO'];
				$arr_product_detail['fdtmProduceDate'] = $barcode['fdtmProduceDate'];
				$arr_product_detail['fchrFree2'] = $barcode['fchrFree2'];
				$arr_product_detail['flotQuantity'] = $barcode['flotQuantity'];
				$arr_product_detail['flotMoney'] = $barcode['flotMoney'];
				 
				$arr_product[$n++] = $arr_product_detail;
			}
		} catch (Exception $e) {
			$is_insert = false;
			$error_message .= "数据异常，关联不上";
			$this->addLog("数据异常，关联不上");
		}
		 
		if($is_insert){
			$db=Yii::app()->getDb();
			for($index = 0 ; $index < count($arr_product) ; $index++){
				$sql = "select brand_gymboree_product_id from ecshop.brand_gymboree_product where fchrBarCodeNO = '".$arr_product[$index]['fchrBarCodeNO']."' limit 1";
				 
				$id = $db->createCommand($sql)->queryScalar();
				$builder = $db->getCommandBuilder ();
				if(empty($id)){
					$msg_data = array();
					$msg_data['brand_gymboree_product_id'] = null;
					$msg_data['fchrItemID']=$arr_product[$index]['fchrItemID'];
					$msg_data['fchrItemName']=$arr_product[$index]['fchrItemName'];
					$msg_data['fchrAddCode']=$arr_product[$index]['fchrAddCode'];
					$msg_data['fbitNoUsed']=$arr_product[$index]['fbitNoUsed'];
					$msg_data['fchrItemTypeName']=$arr_product[$index]['fchrItemTypeName'];
					$msg_data['fchrBarCode']=$arr_product[$index]['fchrBarCode'];
					$msg_data['fchrItemCode']=$arr_product[$index]['fchrItemCode'];
					$msg_data['fchrUnitName']=$arr_product[$index]['fchrUnitName'];
					$msg_data['flotQuotePrice']=$arr_product[$index]['flotQuotePrice'];
					$msg_data['fchrBarCodeNO']=$arr_product[$index]['fchrBarCodeNO'];
					$msg_data['fchrProduceNO']=$arr_product[$index]['fchrProduceNO'];
					$msg_data['fdtmProduceDate']=$arr_product[$index]['fdtmProduceDate'];
					$msg_data['fchrFree2']=$arr_product[$index]['fchrFree2'];
					$msg_data['flotQuantity']=$arr_product[$index]['flotQuantity'];
					$msg_data['flotMoney']=$arr_product[$index]['flotMoney'];
					$msg_data['create_stamp']=$date;
					$msg_data['last_update_stamp']=$date;
					$table = $db->getSchema()->getTable('ecshop.brand_gymboree_product');
					$builder->createInsertCommand($table, $msg_data)->execute();
				}else{
					$contion = "fchrItemID='".$arr_product[$index]['fchrItemID']."'";
					$contion .= ",fchrItemName='".$arr_product[$index]['fchrItemName']."'";
					$contion .= ",fchrAddCode='".$arr_product[$index]['fchrAddCode']."'";
					$contion .= ",fbitNoUsed='".$arr_product[$index]['fbitNoUsed']."'";
					$contion .= ",fchrItemTypeName='".$arr_product[$index]['fchrItemTypeName']."'";
					$contion .= ",fchrBarCode='".$arr_product[$index]['fchrBarCode']."'";
					$contion .= ",fchrItemCode='".$arr_product[$index]['fchrItemCode']."'";
					$contion .= ",fchrUnitName='".$arr_product[$index]['fchrUnitName']."'";
					$contion .= ",flotQuotePrice='".$arr_product[$index]['flotQuotePrice']."'";
					$contion .= ",fchrProduceNO='".$arr_product[$index]['fchrProduceNO']."'";
					$contion .= ",fdtmProduceDate='".$arr_product[$index]['fdtmProduceDate']."'";
					$contion .= ",fchrFree2='".$arr_product[$index]['fchrFree2']."'";
					$contion .= ",flotQuantity='".$arr_product[$index]['flotQuantity']."'";
					$contion .= ",flotMoney='".$arr_product[$index]['flotMoney']."'";
					$contion .= ",last_update_stamp='".$date."'";
					$sql = "update ecshop.brand_gymboree_product set ".$contion." where brand_gymboree_product_id = '".$id."'";
					$rowCount = $db->createCommand($sql)->execute();
				}
			}
		}else{
			try {
				$mail = Yii::app ()->getComponent ( 'mail' );
				$mail->Subject = "【ERROR】金宝贝商品单数据错误";
				$mail->Body = "金宝贝的商品单数据有错误，请及时处理 . /n" .$error_message ;
				$mail->ClearAddresses ();
				$mail->AddAddress ( 'qxu@leqee.com', '许强' );
				$mail->AddAddress ( 'mjzhou@leqee.com', '周明杰' );
				$mail->AddAddress ( 'jwang@leqee.com', '王健' );
				$mail->AddAddress ( 'ytchen@leqee.com', '陈艳婷' );
				$mail->send ();
			} catch ( Exception $e ) {
				$this->log ( "发送邮件异常" );
			}
		}
		
		// 加上 将仓库信息同步到ERP系统的逻辑，
		//1.加载仓库文件的XML
		$warehouse = simplexml_load_file($fileDir.$warehouse_file);
		$db=Yii::app()->getDb();
		$sql = "TRUNCATE ecshop.brand_gymboree_warehouse";
		$rowCount = $db->createCommand($sql)->execute();
		foreach ($warehouse->Warehouse->Row as $item) {
			$msg_data = array();
			$msg_data['brand_gymboree_Warehouse_id']=null;
			$msg_data['brand_gymboree_Warehouse_id']=''.$item->brand_gymboree_Warehouse_id;
			$msg_data['fchrWhCode']=''.$item->fchrWhCode;
			$msg_data['fchrWhName']=''.$item->fchrWhName;
			$msg_data['fchrMemo']=''.$item->fchrMemo;
			$msg_data['fchrWarehouseID']=''.$item->fchrWarehouseID;
			$msg_data['fchrAddress']=''.$item->fchrAddress;
			$msg_data['fchrPhone']=''.$item->fchrPhone;
			$msg_data['fchrLinkman']=''.$item->fchrLinkman;
			$msg_data['fchrStoreFlag']=''.$item->fchrStoreFlag;
			$msg_data['fchrWarehouseOrgID']=''.$item->fchrWarehouseOrgID;
			$msg_data['fbitNoUsed']=''.$item->fbitNoUsed;
			$msg_data['fbitWhPos']=''.$item->fbitWhPos;
			$msg_data['fchrAccountID']=''.$item->fchrAccountID;
			$msg_data['fchrOrgCode']=''.$item->fchrOrgCode;
			$msg_data['fchrOrgName']=''.$item->fchrOrgName;
			$table = $db->getSchema()->getTable('ecshop.brand_gymboree_warehouse');
			$builder->createInsertCommand($table, $msg_data)->execute();
		}
	}

	protected  function backupFiles(){
		$nowdate = time();
		$deleteXml=$nowdate - 24*60*60*11;
		
		$path = ROOT_PATH."../gymboree/";
		$files = array();
		
		//PHP遍历文件夹下所有文件,将所有文件名存放在$files数组
		$handle=opendir($path.".");
		while (false !== ($file = readdir($handle))){
			if (is_file($path.$file)) {
				array_push($files,$file);
			}
		}
		
		foreach ($files as $file){
			$filetime = filemtime($path.$file);
			if($filetime < $deleteXml){
				$this->backupFile($path.$file);
			}
		} 
		
		closedir($handle);
		return $files;
	}

	protected function backupFile($file){
		if(file_exists($file)){
			$path = dirname($file);
			$name = basename($file);
			copy($file,$path."/backup/".$name);
			@unlink($file);
		}
	}

	protected function removeFile($ftp,$src_dir,$tag_dir,$remove){
		$fileDir = ROOT_PATH."../gymboree/";
		
		$nowdate = time();
		$date=date("Y-m-d H:i:s",$nowdate);
		
		if($remove){
			$buff = ftp_mdtm($ftp, "Success/".$tag_dir);
			if($buff != -1){
				ftp_delete($ftp,"Success/".$tag_dir);
			}
			$off = ftp_rename($ftp,$src_dir,"Success/".$tag_dir);
			$this->addLog($tag_dir."处理成功，已经移入Success文件夹\n");
		}else{
			$buff = ftp_mdtm($ftp, "Failure/".$tag_dir);
			if($buff != -1){
				ftp_delete($ftp,"Failure/".$tag_dir);
			}
			$off = ftp_rename($ftp,$src_dir,"Failure/".$tag_dir);
			$this->addLog($tag_dir."处理成功，已经移入Success文件夹\n");
		}
		if(!$off)
			$this->addLog($src_dir."文件移动失败");
	}

	protected function checkFile($ftp,$filename,$xml_detail){
		$fileDir = ROOT_PATH."../gymboree/";
		$remove = true;
		if(filesize($fileDir.$filename) === 0){
			$this->addLog($filename."文件是空的，请核实");
			$this->removeFile($ftp, $filename, $filename, false);
			return false;
		}
		$inout = simplexml_load_file($fileDir.$filename);
		if($inout === false){
			$this->addLog($filename."文件格式错误，无法加载");
			$remove = false;
		}else if($inout->xpath($xml_detail) === false){
			$this->addLog($filename."文件找不到{$xml_detail}节点");
			$remove = false;
		}
		$this->removeFile($ftp, $filename, $filename, $remove);
		return $remove;
	}
	
	//在FTP上下载文件的函数。   $ftp是连接，$path是路径，$file是文件名
	protected function  downFile($ftp,$path,$file,$local_path){
		ftp_cdup($ftp);
		$fileDir = ROOT_PATH.$local_path;
		$buff = ftp_mdtm($ftp, $path.'/'.$file);
		if($buff != -1){
			if(@ftp_chdir($ftp,$path)) {            // 假如指定路径正确
				$tempFile = $fileDir.$file;
			}
			return ftp_get($ftp, $tempFile, $file, FTP_BINARY);// 下载指定的文件到指定目录,失败返回false，成功返回true
		}else{
			$this->log($file."文件不存在");
		}
		return false;
	}
	
	
	
	//在FTP上上传文件。  $ftp是连接，$path是远程文件名，$file是本地文件名
	protected function uploadFile($ftp,$path,$file){
		ftp_cdup($ftp);
		$buff = ftp_mdtm($ftp, $path);
		if($buff != -1){
			ftp_delete($ftp,$path);
			
		}
		if (!ftp_put($ftp, $path, $file, FTP_ASCII)) {
			$this->log($file."上传失败");
		}
	}
	
	protected function addLog($content){
		$fileDir = ROOT_PATH."../gymboree/";
		$nowdate = time();
		$date=date("Y-m-d H:i:s",$nowdate);
		$con = file_get_contents($fileDir."ExportLog.txt");
		$con = $con.$date." ".$content;
		file_put_contents($fileDir."ExportLog.txt",$con);
	}
	
	
	public function checkDown($down_inout_files){
		$tag_dir = ROOT_PATH."../gymboree/";
		$src_dir = ROOT_PATH."../gymboree/temp/";
		
		$result = "";
		
		$tag_files = $this->listFiles($tag_dir);
		foreach ($down_inout_files as $src_file) {
			$can_copy = true;
			foreach ($tag_files as $tag_file){
				if($this->md5FileCheck($tag_dir.$tag_file,$src_dir.$src_file)){
					$can_copy = false;
				}
			}
			if($can_copy){
				copy($src_dir."/".$src_file,$tag_dir."/".$src_file);
				$result .= $src_file + ",";
			}else{
				$this->addLog("该入库通知单".$src_file."已经处理过了,\n");
			}
		}
		return $result;
	}
	
	protected function md5FileCheck($filename1, $filename2){
		$m1 = md5_file($filename1);
		$m2 = md5_file($filename2);
		$result = array();
		if($m1 == $m2){
			return true;
		}else{
			return false;
		}
	}
	
	protected function listFiles($path){
		$array = array();
		$handle=opendir($path);
		while (false !== ($file = readdir($handle))){
			if (is_file($path.'/'.$file) && strpos($file,"InOutVouch") !== false) {
				array_push($array,$file);
			}
		}
		closedir($handle);
		return $array;
	} 
	
	function sendMessage($content) {
		for ($i=0; $i < 10; $i++) {
	 		$send=send_message($content, array('13567177855', '13588396979', '13738196896', '13567191020'), null, 'emay');
			if ($send==0){
				$this->log("短信发送成功 {$content}");
			} else {
				$this->log("短信发送失败 {$content}");
			}
	 		usleep(60000000);
		}
		$this->sendMail($content);
		
 	}
 	
 	function sendMail($subject, $body = null) {
 		try {
			$mail = Yii::app ()->getComponent ( 'mail' );
			$mail->Subject = $subject;
			if($body != ""){
				$mail->Body = $body;
			} else{
				$mail->Body =$subject;
			}
			$mail->ClearAddresses ();
			$mail->AddAddress ( 'zjli@leqee.com', '李志杰' );
			$mail->AddAddress ( 'ytchen@leqee.com', '陈艳婷' );
			$mail->AddAddress ( 'yjchen@leqee.com', '陈越佳' );
			$mail->send ();
		 } catch ( Exception $e ) {
		 	$this->log ( $subject."发送邮件异常 \n" );
		 }
	}
 	
 	private function log ($m) {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
	}
	private function create_inventory_virance_order($comment,$facility_id,$goods,$order_type_id,$admin_id){
		global $db,$ecs; 
		$error_no = 0;
	    do {
	        $order_sn = get_order_sn()."-v";
	        $sql = "INSERT INTO ecshop.ecs_order_info
	                (order_sn, order_time, order_status, shipping_status , pay_status, user_id, postscript, 
	                order_type_id, party_id, facility_id)
	                VALUES('{$order_sn}', NOW(), 2, 0, 0, {$admin_id},
	                         '库存调整订单 {$comment}', '{$order_type_id}', '65574', '{$facility_id}')";
	        $db->query($sql, 'SILENT');
	        $error_no = $db->errno();
	        if ($error_no > 0 && $error_no != 1062) {
	            return $db->errorMsg();
	        }
	    } while ($error_no == 1062); // 如果是订单号重复则重新提交数据
	    $sqls[] = $sql;
	    $order_id = $db->insert_id();
	    
    	$goodsId = trim($goods['goodsId']);
        $styleId = intval($goods['styleId']);
        $statusId = trim($goods['statusId']);
        $goods_count = intval($goods['goodsCount']);
        
        $sql = "select p.product_name from romeo.product_mapping pm 
        			inner join romeo.product p on pm.product_id = p.product_id
        			where pm.ecs_goods_id = '{$goodsId}' and pm.ecs_style_id = '{$styleId}'";
        $goods_name = $db->getOne($sql);
    	$goods_name = mysql_real_escape_string($goods_name); 
        $purchase_paid_amount = trim($goods['purchase_paid_amount']);
        // 插入对应的记录到order_goods表
        $sql = "INSERT INTO {$ecs->table('order_goods')}
                            (order_id, goods_id, style_id, goods_name, goods_number, goods_price, status_id) 
                      VALUES('{$order_id}', '{$goodsId}', '{$styleId}', '{$goods_name}', 
                               '{$goods_count}', '{$purchase_paid_amount}','{$statusId}')";
        $db->query($sql);
        return $order_id;
	}
    private function deliver_inventory_virance_order_inventory($orderGoodsId){
		global $db;
		$sql = "select oi.order_id,oi.facility_id,og.status_id,pm.product_id,og.goods_number,og.goods_number,og.goods_price,oi.postscript,oi.order_type_id
				from ecshop.ecs_order_goods og
				inner join ecshop.ecs_order_info oi on og.order_id = oi.order_id
				inner join romeo.product_mapping pm on og.goods_id = pm.ecs_goods_id and og.style_id = pm.ecs_style_id
				where og.rec_id = '{$orderGoodsId}'";
		$row = $db->getRow($sql);
		
		$productId = $row['product_id'];
		$facilityId = $row['facility_id'];
		$statusId = $row['status_id'];
		$unitCost = $row['goods_price'];
		
		$comment = $row['postscript'];
		$order_type_id = $row['order_type_id'];
		$orderId = $row['order_id'];
		$quantityOnHandVar = $row['goods_number'];
		
		$sql = " select physical_inventory_id 
				from romeo.inventory_item_detail where order_goods_id = '{$orderGoodsId}'";
		$physicalInventoryId = $db->getOne($sql);
		if(empty($physicalInventoryId)){
			$physicalInventoryId = createPhysicalInventory($comment);
		}
		if(empty($physicalInventoryId)){
			return false;
		}
		
		$sql = "select INVENTORY_ITEM_ACCT_TYPE_ID,INVENTORY_ITEM_TYPE_ID 
					from romeo.inventory_item 
				where product_id = '{$productId}' and facility_id = '{$facilityId}' 
				";
		$row = $db->getRow($sql);
		if(empty($row)){
			return false;
		}
		$inventoryItemTypeName = $row['INVENTORY_ITEM_TYPE_ID'];// SERIALIZED, NON-SERIALIZED
		$inventoryItemAcctTypeName = $row['INVENTORY_ITEM_ACCT_TYPE_ID'];// B2C, C2C
		if($inventoryItemTypeName == 'SERIALIZED'){
			$quantityOnHandVar = 1;
		}
		if($order_type_id == VARIANCE_MINUS){
			$quantityOnHandVar = -$quantityOnHandVar;
		}
		$availableToPromiseVar = $quantityOnHandVar;
		$result = createInventoryItemVarianceByProductId(
	            $productId, $inventoryItemAcctTypeName, $inventoryItemTypeName, 
	            $statusId, '', $quantityOnHandVar, 
	            $availableToPromiseVar, $physicalInventoryId,
	            $unitCost, $facilityId,$comment,$orderId,$orderGoodsId);
	    return $result;
	}
    
    private function load_file($filename){
    	global $db;
    	$result = array('error'=>"",  'content'=>array() , 'fchrVouch'=>array());
   		$xml = simplexml_load_file($filename);
		if(!$xml){
			$result['error'] = $filename."文件加载不成功";
		}
		if(!$xml->xpath("InOutVouch")){
			$result['error'] = $filename."采购订单文件不合法，未找到InOutVouch";
		}
		if(!$xml->xpath("InOutVouchDetail")){
			$result['error'] = $filename."采购订单文件不合法，未找到InOutVouchDetail";
		}
		$vouchID = "".$xml->InOutVouch->Row->fchrInOutVouchID;
		$sql = "
			select count(*) from ecshop.brand_gymboree_inoutvouch where fchrInOutVouchID = '{$vouchID}' 
		";
		if($db->getOne($sql) == '0' && $result['error'] == ""){
			$fchrVouch = array();
			$fchrVouch['fchrWarehouseID'] = "".$xml->InOutVouch->Row->fchrWarehouseID;
			$fchrVouch['fchrInOutVouchID'] = "".$xml->InOutVouch->Row->fchrInOutVouchID;
			$fchrVouch['fchrMemo'] = "".$xml->InOutVouch->Row->fchrMemo;
			$fchrVouch['fchrWhCode'] = "".$xml->InOutVouch->Row->fchrWhCode;
			$fchrVouch['fchrRequireWhCode'] = "".$xml->InOutVouch->Row->fchrRequireWhCode;
			array_push($result['fchrVouch'],$fchrVouch);
			foreach ($xml->InOutVouchDetail->Row as $InOutVouchDetail){
			
				$number = $InOutVouchDetail->flotQuantity;
				$itemID = $InOutVouchDetail->fchrItemID;
				$fchrFree2 = $InOutVouchDetail->fchrFree2;
				$vouchDetailID = "".$InOutVouchDetail->fchrInOutVouchDetailID;
		
				$barcode_sql = "select fchrBarCodeNO 
				from ecshop.brand_gymboree_product 
				where fchrItemID = '{$itemID}' 
				and fchrFree2 = '{$fchrFree2}'" ;
				
				$barcode = $db->getOne($barcode_sql);
				
				if(!empty($barcode)){
					$sql = "select g.goods_name,s.color,g.goods_id,s.style_id,g.added_fee
						from ecshop.ecs_goods_style gs 
						inner join ecshop.ecs_goods g on g.goods_id = gs.goods_id
						inner join ecshop.ecs_style s on s.style_id = gs.style_id
						where if(gs.barcode is null or gs.barcode=null,g.barcode,gs.barcode) = '{$barcode}' 
						and g.goods_party_id = '65574' and gs.is_delete=0 ";
					
					$goods_attr = $db->getAll($sql);
					
					if(array_key_exists(1, $goods_attr)){
						$result['error'] = $filename."根据SKU条码:{$barcode}找到了多条商品信息";
						break;
					}
					
					if(!empty($goods_attr)){
						$added_good = array();
						$added_good['fchrInOutVouchID'] = "".$xml->InOutVouch->Row->fchrInOutVouchID;
						$added_good['goods_name'] = $goods_attr[0]['goods_name'];
						$added_good['goods_id'] = $goods_attr[0]['goods_id'];
						$added_good['color'] = $goods_attr[0]['color'];
						$added_good['style_id'] = $goods_attr[0]['style_id'];
						$added_good['goods_number'] = floatval($number);
						$added_good['purchase_paid_amount'] = 0;
						$added_good['purchase_added_fee'] = ($goods_attr['added_fee']==null || $goods_attr['added_fee']=='')?1.17:$goods_attr['added_fee'] ;
						$added_good['gymboree_vouch_detailID'] = $vouchDetailID;
						$added_good['itemID'] = $itemID;
						$added_good['fchrFree2'] = $fchrFree2;
						$added_good['fchrBarCode'] = $InOutVouchDetail->fchrBarCode;
						array_push($result['content'],$added_good);
					}else{
						$result['error'] = $filename."根据barcode：".$barcode."找不到商品";
						break;
					}
				}else{
					$result['error'] = $filename."根据ItemId：{$itemID}和尺码：{$fchrFree2}没能找到商品barcode";
					break;
				}
			} 	
		}
		return $result;
	} 
	
	private function GymboreeInsertSql($sql_array,$table_name){
		foreach($sql_array as $k=>$v){
			$set[] = "$k = '$v'";
		}
	    $set = join(", ", $set);
		$sql = "insert into ".$table_name." set $set ,created_time = NOW(),updated_time=now() ";
		if(!$this->getMaster()->createCommand($sql)->execute()){
			return false;
		}else{
			return true;
		}
	}
}