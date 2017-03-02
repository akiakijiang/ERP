<?php

define('IN_ECS', true);
define("ROOT_PATH", dirname(__FILE__) . '/../../');
require_once ROOT_PATH . 'admin/includes/init.php';
require_once ROOT_PATH . 'data/master_config.php';
require_once ROOT_PATH . 'includes/helper/mail.php';
require_once(ROOT_PATH.'RomeoApi/lib_soap.php');
require_once(ROOT_PATH.'RomeoApi/lib_RMATrack.php');
require_once ROOT_PATH . 'admin/includes/lib_service.php';
include_once ROOT_PATH . 'admin/function.php';
 

class IndicateCommand extends CConsoleCommand {
	
    private $master; // Master数据库    
    
    /**
     * 当不指定ActionName时的默认调用
     */
    public function actionIndex() {
    	//生成出库指示数据
        $this->run(array('IndicateInventoryOutCreate'));
        
        //生成退货指示数据
        $this->run(array('IndicateInventoryReturnCreate'));
        
        //生成入库指示数据
        $this->run(array('IndicateInventoryInCreate'));
        
        //生成退库指示数据
        $this->run(array('IndicateSupplierReturnCreate'));
        
		//入库指示（采购订单 ）并将指示上传到ftp服务器
		$this->run(array('InventoryInIndicate'));
		
		//出库指示（销售订单）并将指示上传到ftp服务器
		$this->run(array('InventoryOutIndicate'));
		
		//退货指示（退货订单）并将指示上传到ftp服务器
		$this->run(array('InventoryReturnIndicate'));
		
		//返厂指示（供应商退货订单）并将指示上传到ftp服务器
		$this->run(array('SupplierReturnIndicate'));
		
		//读取实绩 查看指定目录中未处理实绩文件 根据实绩内容调用不同的接口
		$this->run(array('Actual'));
		
		//同步商品
		$this->run(array('ErpGoodsToNewBalance'));
		
		//处理入库实绩、自动入库
		$this->run(array("InventoryInActual"));
		
 		//处理出库实绩、自动出库发货
 		$this->run(array("InventoryOutActul"));
		
		//处理退货实绩、自动退货入库
		$this->run(array("InventoryReturnActual"));
		
		//处理退库实绩、自动-gt出库
		$this->run(array("SupplierReturnActual"));
		
    }
    

    function createIndicate($orders, $indicate_type, $indicate_media) {
    	$order_count = 0;
    	$indicate_types = array('INVENTORY_OUT', 'INVENTORY_IN', 'INVENTORY_RETURN', 'SUPPLIER_RETURN');
		$indicate_medias = array('TXT', 'XML', 'JSON');
		$indicate_types_mapping = array (
			'INVENTORY_OUT' => '出库指示', 
			'INVENTORY_IN' => '入库指示', 
			'INVENTORY_RETURN' => '退货指示', 
			'SUPPLIER_RETURN' => '退库指示', 
		);
		
		if (! $orders) {
			return ;
		}
		
		if (! in_array($indicate_type, $indicate_types)) {
			return ;
		}
		
		if (! in_array($indicate_media, $indicate_medias)) {
			return ;
		}
		
		$start = microtime(true);
		foreach ($orders as $order) {	
			try {
				$id = $order['id'];
				if (! $id) {
					continue;
				}
				
				if ($indicate_type == 'INVENTORY_RETURN') {
					$response=Yii::app()->getComponent('romeo')->IndicateService->createIndicateInventoryReturnNew(
			   		array('serviceId'=>$id, 'indicateType'=>$indicate_type, 'indicateMedia'=>$indicate_media)
			    	);
					
				} elseif ($indicate_type == 'SUPPLIER_RETURN') {
					$response=Yii::app()->getComponent('romeo')->IndicateService->createIndicateSupplierReturn(
			   		array('supplierReturnId'=>$id, 'indicateType'=>$indicate_type, 'indicateMedia'=>$indicate_media)
			    	);
				} elseif ($indicate_type == 'INVENTORY_OUT'){
					$response=Yii::app()->getComponent('romeo')->IndicateService->createIndicate(
			   		array('orderId'=>$id, 'indicateType'=>$indicate_type, 'indicateMedia'=>$indicate_media)
			    	);
				} else {
					$response=Yii::app()->getComponent('romeo')->IndicateService->createIndicateInventoryIn(
			   		array('batchOrderId'=>$id, 'indicateType'=>$indicate_type, 'indicateMedia'=>$indicate_media)
			    	);
				}
				
		    	$code = $response->return->code;
		    	$msg = $response->return->msg;
		    	if ($code == "SUCCEED") {
		   		$order_count++;
		   		$result = $response->return->result;
		   		echo "[" . date('c'). "] ". $id . " " . $indicate_types_mapping[$indicate_type] . " create succeed " . $result . " \n";
		    	} else {
		   		echo "[" . date('c'). "] ". $id . " " . $indicate_types_mapping[$indicate_type] . " create fail " . $msg . " \n";
		    	}
		    	
			} catch (Exception $e) {
				echo "[" . date('c'). "] ". $id . " " . $indicate_types_mapping[$indicate_type] . " create exception: " . $e->getMessage()."\n";
			}
		}
        
        echo "[". date('c'). "] " . $indicate_types_mapping[$indicate_type] . " 创建：共" . count($orders) . " 成功" . $order_count . " 耗时：".(microtime(true)-$start)."\n";
    }
    
     /**
     * 创建出库指示
     */
    public function actionIndicateInventoryOutCreate() {
    	echo "[". date('c'). "] begin IndicateInventoryOutCreate \n";
    	$start = microtime(true);
    	$indicate_type = 'INVENTORY_OUT';
    	$indicate_media = 'TXT';
    	$party_id = 65585;
    	$start_date = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	$end_date = date("Y-m-d H:i:s", time());
        $sql = "
        	select distinct o.order_id as id
        	from ecshop.ecs_order_info o
        	left join ecshop.ecs_indicate i on o.order_id = i.order_id and i.indicate_type = '{$indicate_type}' 
        	inner join romeo.order_inv_reserved r on r.order_id = convert(o.order_id using utf8)
        	where o.order_type_id in ('SALE', 'RMA_EXCHANGE', 'SHIP_ONLY') 
        	and o.party_id = {$party_id} 
        	and o.order_status = 1 
			and o.shipping_status = 0 
        	and i.indicate_id is null
        	and r.status <> 'N'
        	and r.reserved_time >= '$start_date'
        	and r.reserved_time < '$end_date'
        ";
        $orders = $this->getMaster()->createCommand ($sql)->queryAll();
        $this->createIndicate($orders,$indicate_type, $indicate_media);
        echo "[". date('c'). "]  总耗时：".(microtime(true)-$start)."\n";
        
    }
    
     /**
     * 创建退货指示
     */
    public function actionIndicateInventoryReturnCreate() {
    	echo "[". date('c'). "] begin IndicateInventoryReturnCreate \n";
    	$start = microtime(true);
    	$indicate_type = 'INVENTORY_RETURN';
    	$indicate_media = 'TXT';
    	$party_id = 65585;
    	$start_date = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	$end_date = date("Y-m-d H:i:s", time());
        $sql = "
        	select distinct s.service_id as id
			from ecshop.service s
			inner join ecshop.ecs_order_info o on s.order_id = o.order_id 
			left join ecshop.ecs_indicate i on s.service_id = i.service_id and i.indicate_type = '{$indicate_type}'
			where o.party_id = {$party_id} 
			and s.service_type in (1, 2)
			and s.back_shipping_status = 0 
			and s.service_status = 1 
			and i.indicate_id is null
        	and s.apply_datetime >= '$start_date'
        	and s.apply_datetime < '$end_date'
        ";
        
        $orders = $this->getMaster()->createCommand ($sql)->queryAll();
        $this->createIndicate($orders,$indicate_type, $indicate_media);
        echo "[". date('c'). "]  总耗时：".(microtime(true)-$start)."\n";
        
    }
    
    /**
     * 创建入库指示
     */
    public function actionIndicateInventoryInCreate() {
    	echo "[". date('c'). "] begin IndicateInventoryInCreate \n";
    	$start = microtime(true);
    	$indicate_type = 'INVENTORY_IN';
    	$indicate_media = 'TXT';
    	$party_id = 65585;
    	$start_date = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	$end_date = date("Y-m-d H:i:s", time());
        $sql = "
        	select distinct o.batch_order_id as id
        	from ecshop.ecs_batch_order_info o 
        	left join ecshop.ecs_indicate i on o.batch_order_id = i.order_id and i.indicate_type = '{$indicate_type}'
        	where o.party_id = {$party_id} 
        	and i.indicate_id is null
        	and o.is_cancelled <> 'Y'
        	and o.is_over_c <> 'Y'
        	and o.is_in_storage <> 'Y'
        	and o.order_time >= '$start_date'
        	and o.order_time < '$end_date'
        ";
        $orders = $this->getMaster()->createCommand ($sql)->queryAll();
        $this->createIndicate($orders,$indicate_type, $indicate_media);
        echo "[". date('c'). "]  总耗时：".(microtime(true)-$start)."\n";
        
    }
    
     /**
     * 创建退库指示
     */
    public function actionIndicateSupplierReturnCreate() {
    	echo "[". date('c'). "] begin IndicateSupplierReturnCreate \n";
    	$start = microtime(true);
    	$indicate_type = 'SUPPLIER_RETURN';
    	$indicate_media = 'TXT';
    	$party_id = '65585';
    	$start_date = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	$end_date = date("Y-m-d H:i:s", time());
        $sql = "
			select distinct r.supplier_return_id as id
			from romeo.supplier_return_request r 
			left join ecshop.ecs_indicate i on r.supplier_return_id = i.supplier_return_id and i.indicate_type = '{$indicate_type}'
			where r.created_stamp >= '$start_date'
			and r.created_stamp < '$end_date'
			and r.party_id = '{$party_id}'
			and r.status = 'CREATED'
			and r.check_status = 'PASS'
			and i.indicate_id is null 
        ";
        $orders = $this->getMaster()->createCommand ($sql)->queryAll();
        $this->createIndicate($orders,$indicate_type, $indicate_media);
        echo "[". date('c'). "]  总耗时：".(microtime(true)-$start)."\n";
        
    }
        
    
    /**
     * 取得master数据库连接
     * 
     * @return CDbConnection
     */
    protected function getMaster() {
        if (! $this->master) {
            $this->master = Yii::app ()->getDb ();
            $this->master->setActive ( true );
        }
        return $this->master;
    } 

 	/**
 	 * 商品同步
 	 */
 	public function actionErpGoodsToNewBalance (){
		$start = time();
		echo date('c') . " ErpGoodsToNewBalance start \n";
		$update_stamp = date('Y-m-d h:i:s', strtotime("-30 minutes"));
		$db=Yii::app()->getDb();
		$sql = "
			select g.goods_id, IF(gs.style_id is null, g.barcode, gs.barcode) as barcode, g.goods_name, s.color as size, 
				IF(gs.style_id is null, g.is_delete, gs.is_delete) as is_delete
			from ecshop.ecs_goods g 
			left join ecshop.ecs_goods_style gs on g.goods_id = gs.goods_id and gs.is_delete=0
			left join ecshop.ecs_style s on gs.style_id = s.style_id
			where g.goods_party_id = 65585 and if(gs.style_id is null, g.barcode, gs.barcode) is not null 
			and if(gs.style_id is null, g.barcode, gs.barcode) not in ('null', '')
			and if(gs.style_id is null, g.last_update_stamp, gs.last_update_stamp) >= '{$update_stamp}'
		";
		$goods_list = $db->createCommand($sql)->queryAll();
		$sql_time = time();
		echo date('c') ." mysql execute time:" . ($sql_time-$start) . "\n";
		if (!empty($goods_list)) {
			$goods_info = array();
			foreach ($goods_list as $key => $goods_item) {
				$color_name = "";
				$goods_name = $goods_item['goods_name'];
				if (!empty($goods_item['goods_name'])) {
					$color = explode(" ", $goods_item['goods_name']);
					if (is_array($color) && count($color) > 1) {
						$color_name = array_pop($color);
						$goods_name = implode(" ", $color);   
					} else {
						$color_name = "";
					}
				}
				if ($goods_item['is_delete']) {
					$is_active = "N";
				} else {
					$is_active = "Y";
				}
				$goods_name = trim($goods_name);
				$goods_info[] = array("IMDN", "{$goods_item['barcode']}", "NB-FX", "{$goods_item['barcode']}", "{$goods_name}", "{$color_name}", "{$goods_item['size']}",
					"", "", "", "", $is_active);
			}
			$time = date("YmdHis", time());
			$file_name = "Item_". $time .".im";
			$this->IndicationFile($file_name, $goods_info);
		} else {
			echo date('c') . " goods_list is empty \n";
		}
		echo date('c') .  " ErpGoodsToNewBalance end total_time:" .(time()-$start). " \n";
 	}
 	
 	//入库指示（采购订单）
 	public function actionInventoryInIndicate(){
		$start = time();
		echo date('c'). " InventoryInIndicate start \n";
		//查询入库指示列表
		
		$client=Yii::app()->getComponent('romeo')->IndicateService;
		$c = new stdClass();
		$c->partyId = 65585;  //业务组织
		$c->indicateType = "INVENTORY_IN"; //入库指示测试
		$c->indicateMedia = "TXT";  		//文本类型
		$c->indicateStatus = "INIT";			//初始化状态
		$c->startDate = date("Y-m-d 00:00:00", strtotime("-7 day")); //订单起始时间
		$c->endDate =  date("Y-m-d H:i:s", time()); //订单结束时间
		$res = $client->getIndicateList($c);   //创建指示记录
		$order_list = array();
		if ($res->return->code != "SUCCEED") {
			echo date('c'). " InventoryInIndicate getIndicateList msg:" .$res->return->msg ."\n";
			return;
		} 
		$indicate_list_time = time();
		echo date('c'). " getIndicateList total_time:".($indicate_list_time - $start). " \n";
		if (!empty($res->return->result)) {
			$time = date("YmdHis", time());
			$file_name = "Receipt_". $time .".rc";
			$txt_info = $indicate_id_list = array();
			
			if (!is_array($res->return->result->Indicate)) {
				$order_list[] = $res->return->result->Indicate;
			} else {
				$order_list = $res->return->result->Indicate;
			}
			foreach ($order_list as $key => $item) {
				$indicate_id_list[] = $item->orderId;
				//入库头指示
				$txt_info[] = array("RCHDRDNNEW", "ILC-SH", "{$item->indicateId}", "{$item->indicateId}", "NB-FX", "分销入库", "");
				//商品明细
				if (!empty($item->indicateDetailList)) {
					$detail = array();
					if (!is_array($item->indicateDetailList->IndicateDetail)){
						$detail[] = $item->indicateDetailList->IndicateDetail;
					} else {
						$detail = $item->indicateDetailList->IndicateDetail;
					}
					foreach ($detail as $goods_item) {
						$color = "";
						if (!empty($goods_item->goodsName)) {
							$color = explode(" ", $goods_item->goodsName);
							if (is_array($color) && count($color) > 1) {
								if (count($color) == 2) {
									$color = array_pop($color);
								} else {
									$color = $color[1];
								}
							} else {
								$color = "";
							}
						}
						$txt_info[] = array("RCDTLDNNEW", "{$item->indicateId}", "{$goods_item->indicateDetailId}", "NB-FX", "{$goods_item->barcode}", "", 
										"{$goods_item->goodsNumber}", "{$color}", "{$goods_item->styleName}", "", "{$goods_item->indicateDetailId}", "分销入库", "{$goods_item->indicateDetailId}");
					} 
				}
			}
			$upload_result = $this->IndicationFile($file_name, $txt_info);
			//上传成功后修改为已发出
			if ($upload_result) {
				if (!empty($indicate_id_list)) {
					$this->updateIndicateStatusBatch($client, $indicate_id_list, "INVENTORY_IN", "SENDED");
				} else {
					echo date("c")." InventoryInIndicate order_id is empty \n";
				}
			}
		}
		echo date('c') ." InventoryInIndicate end " ." total_time:" .(time() - $start) ." \n";
 	}
 	//出库指示（销售订单）
 	public function actionInventoryOutIndicate(){
		$start = time();
		echo date('c')." InventoryOutIndicate start \n"; 
		//查询出库指示列表
		$client=Yii::app()->getComponent('romeo')->IndicateService;
		$c = new stdClass();
		$c->partyId = 65585;  //业务组织
		$c->indicateType = "INVENTORY_OUT"; //出库指示测试
		$c->indicateMedia = "TXT";  		//文本类型
		$c->indicateStatus = "INIT";			//初试话状态
		$c->startDate = date("Y-m-d 00:00:00", strtotime("-7 day")); //订单起始时间
		$c->endDate =  date("Y-m-d H:i:s", time()); //订单结束时间
		$res = $client->getIndicateList($c);   //创建指示记录
		$order_list = array();
		if ($res->return->code != "SUCCEED") {
			echo $res->return->code. " ". $res->return->msg ."\n";
			return;
		}
		$indicate_list_time = time();
		echo date('c') ." getIndicateList total_time:".($indicate_list_time - $start) ." \n";
		if (!empty($res->return->result)) {
			$time = date("YmdHis", time());
			$file_name = "Deliver_". $time .".sh";
			$txt_info = $indicate_id_list = array();
			//todo对入库指示数据进行处理
			if (!is_array($res->return->result->Indicate)) {
				$order_list[] = $res->return->result->Indicate;
			} else {
				$order_list = $res->return->result->Indicate;
			}
			foreach ($order_list as $key => $item) {
				$indicate_id_list[] = $item->orderId; 
				if (empty($item->zipcode)) {
					$item->zipcode = "100000";
				}
				if (trim($item->tel) == "-") {
					$item->tel = "";
				}
				$shipping_name = $this->get_shipping_name($item->shippingName);
				$order_time = date("Y-m-d H:i:s", strtotime($item->orderTime));
				$a =  array("\r\n", "\n", "\r");
				$postscript = str_replace($a, ' ', $item->postscript);
				$address = str_replace($a, ' ', $item->address);
				$consignee = str_replace($a, ' ', $item->consignee);
				$txt_info[] = array("SHHDRDNNEW", "ILC-SH", "{$item->indicateId}", "{$item->indicateId}",
				 "NB-FX", "{$item->taobaoUserId}", "{$item->goodsAmount}", "{$item->shippingFee}", "", "",
				 "{$consignee}", "CN", "{$item->provinceName}", "{$item->cityName}", "{$item->districtName}",
				 "{$item->zipcode}", "{$address}", "", "", "{$shipping_name}", "{$shipping_name}",
				 "{$item->tel}", "{$item->mobile}", "{$order_time}", "", 0, "{$postscript}", "", "{$item->taobaoOrderSn}",
				 "Y", "分销订单"); 
				if (!empty($item->indicateDetailList)) {
					$detail = array();
					if (!is_array($item->indicateDetailList->IndicateDetail)){
						$detail[] = $item->indicateDetailList->IndicateDetail;
					} else {
						$detail = $item->indicateDetailList->IndicateDetail;
					}
					foreach ($detail as $goods_item) {
						$color = "";
						if (!empty($goods_item->goodsName)) {
							$color = explode(" ", $goods_item->goodsName);
							if (is_array($color) && count($color) > 1) {
								if (count($color) == 2) {
									$color = array_pop($color);
								} else {
									$color = $color[1];
								}
							} else {
								$color = "";
							}
						}
						//样式名称 size
						$txt_info[] = array("SHDTLDNNEW", "{$item->indicateId}", "{$goods_item->indicateDetailId}", "{$goods_item->indicateDetailId}", "NB-FX", 
							"{$goods_item->barcode}", "", "{$goods_item->goodsNumber}","{$color}", "{$goods_item->styleName}", "", "","{$goods_item->indicateDetailId}");
					}
				}
			}
			$upload_result = $this->IndicationFile($file_name, $txt_info);
			//上传成功后修改为已发出
			if ($upload_result) {
				if (!empty($indicate_id_list)) {
					$this->updateIndicateStatusBatch($client, $indicate_id_list, "INVENTORY_OUT", "SENDED");
				} else {
					echo date("c")." InventoryOutIndicate indicate_id is empty \n";
				}
			}
		}
		echo date('c'). " InventoryOutIndicate end total_time:".(time() - $start) ." \n";
 	}
 	
 	//退货指示（退货订单）
 	public function actionInventoryReturnIndicate(){
		echo date('c'). " InventoryReturnIndicate start \n";
		$start = time();
		$client=Yii::app()->getComponent('romeo')->IndicateService;
		$c = new stdClass();
		$c->partyId = 65585;  //业务组织
		$c->indicateType = "INVENTORY_RETURN"; //退货指示
		$c->indicateMedia = "TXT";  		   //文本类型
		$c->indicateStatus = "INIT";		   //初试话状态
		$c->startDate = date("Y-m-d 00:00:00", strtotime("-7 day")); //订单起始时间
		$c->endDate =  date("Y-m-d H:i:s", time()); //订单结束时间
		$res = $client->getIndicateList($c);   //创建指示记录
		if ($res->return->code != "SUCCEED") {
			echo $res->return->code. " ". $res->return->msg ."\n";
			return;
		} 
		$indicate_list_time = time();
		echo date('c') ." getIndicateList total_time:". ($indicate_list_time-$start) ." \n";
		$order_list = $txt_info = $indicate_id_list = array();
		if (!empty($res->return->result)) {
			$time = date("YmdHis", time());
			$file_name = "Deliver_". $time .".rc";
			//todo对入库指示数据进行处理
			if (!is_array($res->return->result->Indicate)) {
				$order_list[] = $res->return->result->Indicate;
			} else {
				$order_list = $res->return->result->Indicate;
			}
			foreach ($order_list as $key => $item) {
				$indicate_id_list[] = $item->indicateId; 
				if (empty($item->zipcode)) {
					$item->zipcode = "100000";
				}
				if (trim($item->tel) == "-") {
					$item->tel = "";
				}
				$a =  array("\r\n", "\n", "\r");
				$address = str_replace($a, ' ', $item->address);
				$consignee = str_replace($a, ' ', $item->consignee);
				$txt_info[] = array("RCHDRRTNEW", "ILC-SH", "{$item->indicateId}", "{$item->indicateId}",
				 "NB-FX", "{$item->taobaoUserId}", "", "", "{$consignee}", "{$item->provinceName}",
				 "{$item->cityName}", "{$item->districtName}", "{$item->zipcode}", "{$address}", "", "", 
			     "", "{$item->tel}", "{$item->mobile}", "", "{$item->taobaoOrderSn}", "分销退货", "",
				); 
				if (!empty($item->indicateDetailList)) {
					$detail = array();
					if (!is_array($item->indicateDetailList->IndicateDetail)){
						$detail[] = $item->indicateDetailList->IndicateDetail;
					} else {
						$detail = $item->indicateDetailList->IndicateDetail;
					}
					foreach ($detail as $goods_item) {
						$color = "";
						if (!empty($goods_item->goodsName)) {
							$color = explode(" ", $goods_item->goodsName);
							if (is_array($color) && count($color) > 1) {
								if (count($color) == 2) {
									$color = array_pop($color);
								} else {
									$color = $color[1];
								}
							} else {
								$color = "";
							}
						}
						//样式名称 size
						$txt_info[] = array("RCDTLDNNEW", "{$item->indicateId}", "{$goods_item->indicateDetailId}", "NB-FX", "{$goods_item->barcode}", 
							"0", "{$goods_item->goodsNumber}","{$color}", "{$goods_item->styleName}", "", "{$goods_item->indicateDetailId}", "分销退货", "{$goods_item->indicateDetailId}");
					}
				}
			}
			$upload_result = $this->IndicationFile($file_name, $txt_info);
			if ($upload_result) {
				if (!empty($indicate_id_list)) {
					$this->updateIndicateStatusBatch($client, $indicate_id_list, "INVENTORY_RETURN", "SENDED");
				} else {
					echo date("c")." InventoryReturnIndicate indicate_id is empty \n";
				}
			}
		}
		echo date('c'). " InventoryReturnIndicate end total_time:" . (time()-$start)." \n";
 	}
 	//返厂指示（供应商退货）
 	public function actionSupplierReturnIndicate(){
		$start = time();
		echo date('c'). " InventoryReturnIndicate start \n";
		$client=Yii::app()->getComponent('romeo')->IndicateService;
		$c = new stdClass();
		$c->partyId = 65585;  //业务组织
		$c->indicateType = "SUPPLIER_RETURN";  //返厂指示
		$c->indicateMedia = "TXT";  		   //文本类型
		$c->indicateStatus = "INIT";		   //初试话状态
		$c->startDate = date("Y-m-d 00:00:00", strtotime("-7 day")); //订单起始时间
		$c->endDate =  date("Y-m-d H:i:s", time()); //订单结束时间
		$res = $client->getIndicateList($c);   //创建指示记录
		if ($res->return->code != "SUCCEED") {
			echo $res->return->code. " ". $res->return->msg ."\n";
			return;
		}
		$indicate_list_time = time();
		echo date('c'). " getIndicateList total_time:" . ($indicate_list_time-$start). " \n";
		if (!empty($res->return->result)) {
			$order_list = $txt_info = $indicate_id_list = array();
			$time = date("YmdHis", time());
			$file_name = "ReturnNote_". $time .".sh";
			if (!is_array($res->return->result->Indicate)) {
				$order_list[] = $res->return->result->Indicate;
			} else {
				$order_list = $res->return->result->Indicate;
			}
			$indicateId = null;
			$flag = true;
			//todo对入库指示数据进行处理
			foreach ($order_list as $key => $item) {
				$indicate_id_list[] = $item->indicateId; 
				if (trim($item->tel) == "-") {
					$item->tel = "";
				}
				$item->zipcode = TEMP_ZIPCOED;
				if (empty($item->zipcode)) {
					$item->zipcode = "100000";
				}
				$order_time = date("Ymd", strtotime($item->createdStamp));
				if ($flag) {
					$txt_info[] = array("SHHDRRTNEW", "ILC-SH", "{$item->indicateId}", "{$item->indicateId}",
					 "NB-FX", TEMP_CONSIGNEE, "CN", TEMP_PROVINCE, 
					 TEMP_CITY, TEMP_DISTRICT, "{$item->zipcode}", TEMP_ADDRESS, "", "", 
				     TEMP_SHIPPING, TEMP_SHIPPING, TEMP_MOBILE, "{$order_time}", "0", "", "",  "Y", "分销返厂",
					); 
					$flag = false;
					$indicateId = $item->indicateId;
				}
				
				$item->indicateId = $indicateId;
				if (!empty($item->indicateDetailList)) {
					$detail = array();
					if (!is_array($item->indicateDetailList->IndicateDetail)){
						$detail[] = $item->indicateDetailList->IndicateDetail;
					} else {
						$detail = $item->indicateDetailList->IndicateDetail;
					}
					foreach ($detail as $goods_item) {
						//样式名称 size
						$txt_info[] = array("SHDTLRTNEW", "{$item->indicateId}", "{$goods_item->indicateDetailId}", "{$goods_item->indicateDetailId}", "NB-FX", 
							"{$goods_item->barcode}", "", "{$goods_item->goodsNumber}","", "", "", "{$goods_item->indicateDetailId}", "{$goods_item->goodsType}");
					}
				}
			}
			$upload_result = $this->IndicationFile($file_name, $txt_info);
			if ($upload_result) {
				if (!empty($indicate_id_list)) {
					$this->updateIndicateStatusBatch($client, $indicate_id_list, "SUPPLIER_RETURN", "SENDED");
				} else {
					echo date("c")." SupplierReturnIndicate order_id is empty \n";
				}
			}
		}
		echo date('c'). " InventoryReturnIndicate end total_time:" . (time()- $start)." \n";
 	}
 	
 	/**
 	 * 查看ftp中已出库未读取订单列表
 	 */
 	public function actionActual() {
		$start = time();
		echo date('c') . " Actual start \n";
		//登录ftp 切换目录  下载文件
		$conn_ftp = $this->erpLoginFtp();
		if ($conn_ftp) {
			global $nb_ftp_performance;
			$file_name_list = ftp_nlist($conn_ftp, $nb_ftp_performance);
			foreach ($file_name_list as $item) {
				$name = basename($item);
				if (strpos($name, ".")) {
					$down_result = $this->downloadFile($conn_ftp, $name, $nb_ftp_performance);
					if ($down_result) {
						$this->backupFile($conn_ftp, $name);
					}
				}
			}
			foreach ($file_name_list as $item) {
				$file_name = basename($item);
				if (strpos($file_name,".")) {
					$content = $this->readTxtFile($file_name);
					if (!empty($content)) {
						// 根据文件类型调用不同方法处理
						if (strpos($file_name, "SHIP") !== false) {
							$this->InventoryOutOrReturnTxt($content);
						} elseif (strpos($file_name, "RECV") !== false) {
							$this->InventoryInOrSupplierReturnTxt($content);
						} elseif (strpos($file_name, "IBAL") !== false) {
							//todo 库存实绩
							$this->ActualInventoryTxt($content);
						} else {
							echo date('c'). " ".$file_name . " is error \n";
							return ;
						}
					}
				}
			}
		}
		ftp_close($conn_ftp);
		
 	}
 	/**
 	 * 转换库存实绩数据
 	 * @param array content
 	 */
 	protected function ActualInventoryTxt($content) {
		$status = array("良品", "不良品");
		$start = time();
		if (empty($content) || !is_array($content)) {
			echo "ActualInventoryTxt param valid \n";
			return false;
		}
		$info = array();
		$client=Yii::app()->getComponent('romeo')->ActualInventoryService;
		foreach ($content as $key => $row) {
			$status_erp = "";
			if (empty($row) || count($row) <= 1) {
				continue;
			}
			if (empty($row[3])) {
				echo date('c') . " ActualInventoryTxt sku is empty \n";
				continue;
			}
			if (!in_array(trim($row[5]),$status)) {
				echo date('c') . " ActualInventoryTxt status:". trim($row[5]). " is error \n";
				continue;
			} else {
				if (trim($row[5]) == "良品") {
					$status_erp = "INV_STTS_AVAILABLE";
				} else if (trim($row[5]) == "不良品") {
					$status_erp = "INV_STTS_USED";
				}
			}
			$info[] = array("barcode" => trim($row[3]), "stockQuantity" =>  trim($row[4]), "status" => $status_erp);
		}
		foreach ($info as $item) {
			$c = new stdClass();
			$c->barcode = $item['barcode'];
			$c->stockQuantity = (int) $item['stockQuantity'];
			$c->status = $item['status'];
			$c->partyId = 65585;
			$res= $client->createActualInventory($c);
			if ($res->return->code != "SUCCEED") {
				echo date('c'). " ActualInventoryTxt code:" . $res->return->code ." msg:" . $res->return->msg ." \n";
			}
		}
		echo date('c')." ActualInventoryTxt total_time:".(time()-$start) ." total_number:".count($info)."\n";
		return null;
 	}
 	/**
 	 * 实际数据处理并调用java接口
 	 * 入库实际、退货实绩 
 	 * RECV-YYYYMMDDHHMMSS.rcupl
 	 */
 	protected function InventoryInOrSupplierReturnTxt($content){
		$start = time();
		if (!empty($content) && is_array($content)) {
			$client=Yii::app()->getComponent('romeo')->IndicateService;
			$info = array();
			foreach($content as $key => $row) {
				if (empty($row) || count($row) <= 1) {
					continue;
				}
				$indicate_id = $row[1];
				if (empty($indicate_id)) {
					echo date('c') ." InventoryReturnTxt indicate_id is empty \n";
				}
				//头指示
				if (trim($row[0]) == "RCHDRUP" || substr(trim($row[0]), -7) == "RCHDRUP") {
					if (empty($info[$indicate_id])) {
						$info[$indicate_id] = $row;
					} else {
						echo date('c') ." InventoryReturnTxt indicate_id SHHDRUP is exists". $row ." \n";
					}
				}
				//详情
				if (trim($row[0]) == "RCDTLUP" || substr(trim($row[0]), -7) == "RCDTLUP") {
					$info[$indicate_id]['indicate_detail'][] = $row;
				}
			}
			foreach ($info as $item) {
				$c = new stdClass();
				$c->indicateId = (int)$item[1]; 
				$c->inOutTimeStr = date("Y-m-d H:i:s", strtotime($item[4]));
				foreach($item['indicate_detail'] as $item_detail){
					$c->actualDetailList[] = array("indicateDetailId" => (int)$item_detail[2], "indicateId" => (int)$item_detail[1], 
						"goodsNumber" => $item_detail[6], "goodsType" => trim($item_detail[7]));
				}
				$res= $client->createActual(array('actual' => $c));
				if ($res->return->code != "SUCCEED") {
					echo date('c'). " InventoryReturnTxt code:" . $res->return->code ." msg:" . $res->return->msg ." \n";
				}
			}
			echo date('c') . " InventoryReturnTxt total_time:".(time()-$start) ." total_number:". count($info) ." \n";
		}
 	}
 	/**
 	 * 实际数据处理并调用java接口
 	 * 出库实绩、返厂实绩：SHIP-YYYYMMDDHHMMSS.shupl
 	 * @param array 文本文件内容
 	 */
 	protected function InventoryOutOrReturnTxt($content) {
		$start = time();
		if (!empty($content) && is_array($content)) {
			$client=Yii::app()->getComponent('romeo')->IndicateService;
			$info = array();
			foreach($content as $key => $row) {
				if (empty($row) || count($row) <= 1) {
					continue;
				}
				$indicate_id = trim($row[2]);
				if (empty($indicate_id)) {
					echo date('c') ." InventoryReturnTxt indicate_id is empty \n";
				}
				//头指示
				if ( (trim($row[0]) == "SHHDRUP") || (substr(trim($row[0]), -7) == "SHHDRUP")) {
					if (empty($info[$indicate_id])) {
						$info[$indicate_id] = $row;
					} else {
						print date('c') ." InventoryReturnTxt indicate_id SHHDRUP is exists". $row ." \n";
					}
				}
				//详情
				if ((trim($row[0]) == "SHDTLUP") ||trim(substr($row[0], -7)) == "SHDTLUP") {
					$info[$indicate_id]['indicate_detail'][] = $row;
				}
			}
			$db=Yii::app()->getDb();
			foreach ($info as $item) {
				if ("分销返厂" == trim($item[10])) {
					$c = new stdClass();
					$c->indicateId = (int)$item[2]; 
					$c->carrierName = $item[5];
					$c->trackingNumber = $item[6];
					$c->inOutTimeStr = date("Y-m-d H:i:s", strtotime($item[4]));
					foreach($item['indicate_detail'] as $item_detail){
						$indicate_detail_id = (int)$item_detail[7];
						$sql = "select indicate_id from ecshop.ecs_indicate_detail where indicate_detail_id = {$indicate_detail_id}";
						$indicateId2 = $db->createCommand($sql)->queryScalar();
						if ((int)$item[2] != (int)$indicateId2) {
							$c2 = new stdClass();
							$c2->indicateId = (int)$indicateId2;
							$c2->carrierName = $item[5];
							$c2->trackingNumber = $item[6];
							$c2->inOutTimeStr = date("Y-m-d H:i:s", strtotime($item[4]));
							$c2->actualDetailList[] = array("indicateDetailId" => (int)$item_detail[7], "indicateId" => (int)$indicateId2, 
							"goodsType" => trim($item_detail[8]), "goodsNumber" => $item_detail[4]);
							$res= $client->createActual(array('actual' => $c2));
							$this->log("supplier_return_actual_convert# indicate_detail_id: {$indicate_detail_id} from:{$item[2]} to:{$indicateId2} success");
							continue;
						}
						$c->actualDetailList[] = array("indicateDetailId" => (int)$item_detail[7], "indicateId" => (int)$item_detail[2], 
							"goodsType" => trim($item_detail[8]), "goodsNumber" => $item_detail[4]);
					}
					if ($c->actualDetailList) {
						$res= $client->createActual(array('actual' => $c));
					}
					if ($res->return->code != "SUCCEED") {
						echo date('c'). " InventoryReturnTxt code:" . $res->return->code ." msg:" . $res->return->msg ." \n";
					}
				} else {
					$c = new stdClass();
					$c->indicateId = (int)$item[2]; 
					$c->carrierName = $item[5];
					$c->trackingNumber = $item[6];
					$c->inOutTimeStr = date("Y-m-d H:i:s", strtotime($item[4]));
					foreach($item['indicate_detail'] as $item_detail){
						$c->actualDetailList[] = array("indicateDetailId" => (int)$item_detail[7], "indicateId" => (int)$item_detail[2], 
							"goodsType" => trim($item_detail[8]), "goodsNumber" => $item_detail[4]);
					}
					$res= $client->createActual(array('actual' => $c));
					if ($res->return->code != "SUCCEED") {
						echo date('c'). " InventoryReturnTxt code:" . $res->return->code ." msg:" . $res->return->msg ." \n";
					}
				}
			}
			echo date('c') . " InventoryReturnTxt total_time:".(time()-$start) ." total_number:". count($info) ." \n";
		}
 	}
 	/**
 	 * 读取数据后并进行ERP操作
 	 * 读取数据(出库实绩、返厂实绩、入库实绩、退货实绩)
 	 * 出库实绩、返厂实绩：SHIP-YYYYMMDDHHMMSS.shupl
 	 * 入库实绩、退货实绩：RECV-YYYYMMDDHHMMSS.rcupl
 	 * 库存实际 IBAL-YYYYMMDDHHMMSS.ibupl
 	 * @param string 文件名
 	 */
 	protected function readTxtFile($ftp_path_name){
		$start = time();
		global $nb_ftp_performance;
		$ftp_path_name = ROOT_PATH."../newbalance/".$nb_ftp_performance . "/". $ftp_path_name;
		$order_info = array();
		$handle = @fopen($ftp_path_name, "r");
		if ($handle) {
			$content = stream_get_contents($handle);
			$file_content_list = explode("\n", $content);
			//定义出库实绩、返厂实绩、入库实绩、退货实绩
			$S_list = $GT_list = $C_list = $T_list = array();
			//只读取信息，一个订单中存在多个商品的情况
			foreach ($file_content_list as $key => $item) {
				$row = explode(chr(hexdec("0x08")), $item);
				if (empty($row) || count($row) <= 1) {
					continue;
				}
				$order_info[] = $row;
			}
			fclose($handle);
		} else {
			echo date('c'). " readTxtFile ftp_path_name:" .$ftp_path_name ." opened file failed \n";
		}
		echo date('c')." readTxtFile ftp_path_name:".$ftp_path_name." total_time:". (time()-$start) ." \n";
		return $order_info;
 	}
 	/**
 	 * 批量修改指示状态
 	 * @param obj romeo对象
 	 * @param array $order_id_list 订单号
 	 * @param string 指示类型
 	 * @param string 指示状态
 	 */
 	protected function updateIndicateStatusBatch ($client, $id_list, $indicate_type, $indicate_status) {
		$start = time();
		$result = false;
		if (empty($id_list) || empty($indicate_type) || empty($indicate_status)) {
			return false;
		}
		foreach ($id_list as $id) {
			if ($indicate_type == "INVENTORY_IN" || $indicate_type == "INVENTORY_OUT") {
				$c = new stdClass();
				$c->orderId = $id;  
				$c->indicateType = $indicate_type; 
				$c->indicateStatus = $indicate_status;		//已发出
				$res = $client->updateIndicateStatusByOrderIdIndicateType($c);   //创建指示记录
				if ($res->return->code != "SUCCEED") {
					echo date('c'). " order_id:" . $id . " indicate_type ". $indicate_type . " ". $res->return->code ." " .$res->return->msg . "\n";
				}
			} elseif ($indicate_type == "SUPPLIER_RETURN" || $indicate_type == "INVENTORY_RETURN" ) {
				$c = new stdClass();
				$c->indicateId = $id; 
				$c->indicateStatus = $indicate_status;		//已发出
				$res = $client->updateIndicateStatus($c);   //创建指示记录
				if ($res->return->code != "SUCCEED") {
					echo date('c'). " indicate_id:" . $id . " indicate_type ". $indicate_type . " ". $res->return->code ." " .$res->return->msg . "\n";
				}
			}
			
		}
		echo date('c') . " updateIndicateStatusBatch total_time:" .(time()-$start) ."　total_orde_number:" .count($id_list) ." \n";
 	}
 	
 	/**
 	 * 修改指示状态
 	 */
 	 protected function updateIndicateStatus($client, $indicateId, $indicateStatus) {
		if (! $client ) {
			return;
		}
		if (! $indicateId) {
			return;
		}
		if (! $indicateStatus) {
			return;
		}
		
		$c = new stdClass();
		$c->indicateId = $indicateId;
		$c->indicateStatus=  $indicateStatus;
		$res = $client->updateIndicateStatus($c);
		if ($res->return->code != "SUCCEED") {
			$this->log("updateIndicateStatus fail：{$res->return->msg}");
		} 
 	 }
 	
 	/**
 	 * 修改实绩状态
 	 */
 	protected function updateActualStatus($client, $actualId, $actualStatus) {
		if (! $client ) {
			return;
		}
		if (! $actualId) {
			return;
		}
		if (! $actualStatus) {
			return;
		}
		
		$c = new stdClass();
		$c->actualId = $actualId;
		$c->actualStatus=  $actualStatus;
		$res = $client->updateActualStatus($c);
		if ($res->return->code != "SUCCEED") {
			$this->log("updateActualStatus fail：{$res->return->msg}");
		} 
	}
	
 	/**
 	 * 修改实绩明细状态
 	 */
 	protected function updateActualDetailStatus($client, $actualDetailId, $actualDetailStatus, $msg) {
		if (! $client ) {
			return;
		}
		if (! $actualDetailId) {
			return;
		}
		if (! $actualDetailStatus) {
			return;
		}
		
		$c = new stdClass();
		$c->actualDetailId = $actualDetailId;
		$c->actualDetailStatus=  $actualDetailStatus;
		$c->msg = $msg;
		$res = $client->updateActualDetailStatus($c);
		if ($res->return->code != "SUCCEED") {
			$this->log("updateActualDetailStatus fail：{$res->return->msg}");
		} 
 	}
 	
 	
 	/**
 	 * 生成txt文件，并上传ftp指定目录
 	 * @param string ftp文件名
 	 * @param array 已处理上传订单数据信息（根据指示不同，处理为不同数据）
 	 * @return 处理成功返回true 否则返回false
 	 */
 	protected function IndicationFile ($file_name, $order_list) {
		$start = time();
		//ftp 上传目录
		$result = false;
		if (empty($file_name) || empty($order_list)) {
			return $result;
		}
		//将指定数据写入ftp指定目录下的文件  start
		$write_result = $this->writeFile(ROOT_PATH."../newbalance/indicates_data/".$file_name, $order_list);
		$write_time = time();
		echo date('c') . " ftp_file_name: " .$file_name . " totaltime: " . ($write_time -$start) ."\n";
		if ($write_result){
			//登录ftp
			$conn_id = $this->erpLoginFtp();
			if ($conn_id !== null) {
				$result = $this->uploadFile($conn_id, $file_name, ROOT_PATH."../newbalance/indicates_data/".$file_name);
				ftp_close($conn_id);  
			}
		}
		return $result;
 	}

 	/**
 	 * 删除源文件
 	 * @param resource ftp链接
 	 * @param string ftp文件名
 	 * @param  boolean 移动成功返回true 否则返回false
 	 */
 	protected function backupFile($conn_ftp,$file_name){
		$start = time();
		$date=date("Y-m-d H:i:s", time());
		$buff = ftp_mdtm($conn_ftp, $file_name);
		$off = false;
		if($buff != -1){
			$off = ftp_delete($conn_ftp, $file_name);
		} 
		echo date('c') . " backupFile ftp_name:".$file_name." total_time:" .(time()-$start) . "\n";
		return $off;
	}
 	/**
 	 * 从ftp服务器中下载文件 
 	 * @param resource ftp链接
 	 * @param string ftp需要下载文件目录
 	 * @param string ftp存储下载文件目录
 	 * @param string 需要下载的文件名
 	 * @return boolean 下载成功返回true 否则返回false
 	 */
 	protected function downloadFile ($conn_ftp, $file_name, $file_url) {
		$start = time();
		ftp_cdup($conn_ftp);
		$buff = ftp_mdtm($conn_ftp, $file_url.'/'.$file_name);
		if($buff != -1){
			if(@ftp_chdir($conn_ftp,$file_url)) {            // 假如指定路径正确
				$tempFile = ROOT_PATH."../newbalance/".$file_url."/".$file_name;
			}
			return ftp_get($conn_ftp, $tempFile, $file_name, FTP_BINARY);// 下载指定的文件到指定目录,失败返回false，成功返回true
		}else{
			echo ($file_name."文件不存在");
		}
		echo date('c') ." downloadFile ftp_dir:".$file_url." file_name:".$file_name ." total_time:".(time() - $start) ."\n";
		return false;
 	}
 	/**
 	 * 创建指示文件并打开文件，在文件中写入内容
 	 * @param string 文件名
 	 * @param array 文件内容
 	 * @return boolean 文件写入结果
 	 */
 	protected function writeFile($file_name, $content) {
		$flag = false;
		if (empty($content)) {
			echo date('c'). " writeFile file_name:".$file_name. " content is empty \n";
			return $flag;
		}
		$file_content = "";
		if (is_array($content)) {
			foreach ($content as $row) {
				$file_content .= implode(chr(hexdec("0x08")), $row) . "\n";
			}
		}
		if (!empty($file_content)) {
			//判断文件是否可写
			$fp = fopen($file_name, 'w+');
			if (is_writable($file_name)) {
				if (fwrite($fp, $file_content) === FALSE) {
			        echo date('c'). " writeFile file_content". $file_content ." file_name: ".$file_name." write failed \n";
			    } else {
			    	$flag = true;
			    }	
			} else {
				echo date('c'). " writeFile file_name:" . $file_name . " is not avaiavble to write \n";
			}
			fclose($fp);
		}
		return $flag;
 	}
 	/**
 	 * @param resource ftp服务链接
 	 * @param string ftp文件名
 	 * @param string 文件名
 	 * @param string 打开的文件链接
 	 * @return boolean 成功true  否则false
 	 */
 	protected function uploadFile ($conn_id, $ftp_file_name, $file_name) {
		global $nb_ftp_indicates;
		$ftp_dir_file = $nb_ftp_indicates."/".$ftp_file_name;
		$result = false;
		$start = time();
		if(ftp_put($conn_id, $ftp_dir_file, $file_name, FTP_ASCII)) {
		    echo date('c')." loal_dir_file: " .$file_name." uploaded succeed \n";
		    $result = true;
		} else {
		    echo date('c')." loal_dir_file: " .$file_name." uploaded failed \n";
		}
		echo date('c') ." uploadFile total_time:" . (time() - $start) ." \n";
		return $result;
	}
 	/**
 	 * 登录指定的ftp服务
 	 * 记得在程序中退出ftp链接
 	 * @param string ftp服务ip地址
 	 * @param int ftp服务端口号
 	 * @param string ftp服务用户名
 	 * @param string ftp服务密码
 	 * @return 登录成功返回 resource ftp服务链接 否则返回false
 	 */
 	protected function erpLoginFtp() {
 	    global $nb_ftp_url;
        global $nb_ftp_port;
        global $nb_ftp_name;
        global $nb_ftp_pwd;
        $conn_id = ftp_connect($nb_ftp_url, $nb_ftp_port) or die("Could not connect");
        echo date('c') . " erpLoginFtp connect" . " \n";
        $login_result = ftp_login($conn_id, $nb_ftp_name, $nb_ftp_pwd);
        echo date('c') . " erpLoginFtp connect" . " \n";
        
 		if(!$conn_id) {
	    	echo "ftp connect failed: host:".$nb_ftp_url." port:".$nb_ftp_port." user: ". $nb_ftp_name ."\n";
 		}
 		if (!$login_result) {
 			$conn_id = null;
 			echo "ftp login failed: host:".$nb_ftp_url." port:".$nb_ftp_port." user:". $nb_ftp_name ."\n";
 		}
 		return $conn_id;
 	}
 	
 	/**
 	 * 处理入库实绩、自动入库
 	 */
 	public function actionInventoryInActual() {
		$this->log("InventoryInActual begin");
		
		$client=Yii::app()->getComponent('romeo')->IndicateService;
		
		$party_id = 65585;
		$inventory_type = 'INVENTORY_IN';
		$status_received = 'RECEIVED';
		$status_finished = 'FINISHED';
		$status_error = 'ERROR';
		
		$start = microtime(true);
		$countAll = 0;
		$countIndicateFinish = 0;
		$countActualFinish = 0;
		$countActualError = 0;
		$countActualDetailFinish = 0;
		$countActualDetailError = 0;
		
		$start_date = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	$end_date = date("Y-m-d H:i:s", time());
		
		// 获得实绩
		$sql = "select i.indicate_id, d.actual_id, d.actual_detail_id,
				 (select dd.order_goods_id from ecshop.ecs_indicate_detail dd where d.indicate_detail_id = dd.indicate_detail_id) as order_goods_id,
				i.party_id, i.order_id, d.goods_type, d.goods_number, i.facility_id
			from ecshop.ecs_indicate i 
			inner join ecshop.ecs_actual_detail d on i.indicate_id = d.indicate_id 
			where i.party_id = {$party_id} 
			and i.indicate_type = '{$inventory_type}'
			and i.indicate_status = '{$status_received}'
			and d.actual_detail_status = '{$status_received}'
			and d.created_stamp >= '{$start_date}'
			and d.created_stamp < '{$end_date}'
			order by i.indicate_id, d.actual_id, d.actual_detail_id
		";
		$orders = $this->getMaster()->createCommand ($sql)->queryAll();
		$countAll = count($orders);
		
		if ($orders) {
		foreach ($orders as $order) {
			$msg = null;
			global $facility_id;
			$facility_id = $order['facility_id'];
			
			if ($order['goods_type'] == '良品' || $order['goods_type'] == "不良品") {
				//入库
				$inventory_status = $order['goods_type'] == '良品' ? 'INV_STTS_AVAILABLE' : 'INV_STTS_USED';
				$orderId = $this->getMaster()->createCommand ("select order_id from ecshop.ecs_order_goods where rec_id = {$order['order_goods_id']}")->queryScalar();
				$info = actual_inventory_in($orderId, $order['goods_number'], true, $inventory_status, $order['facility_id'], 'IndicateCommand');
				if ($info['res'] != 'success') {
					$msg = "actual_inventory_in: {$info['back']}";
					$this->log("actualDetailId: {$order['actual_detail_id']} error: {$msg}");
					//记录实绩明细错误信息
					$this->updateActualDetailStatus($client, $order['actual_detail_id'], $status_error, $msg);
					$countActualDetailError++;
				} else {
					//记录实绩明细完成信息
					$this->log("actualDetailId: {$order['actual_detail_id']} finish");
					$this->updateActualDetailStatus($client, $order['actual_detail_id'], $status_finished, $msg);
					$countActualDetailFinish++;
				}
				
			} else {
				$msg = "actual_inventory_in: goods_type unknown";
				$this->log("actualDetailId: {$order['actual_detail_id']} error: {$msg}");
				//记录实绩明细错误信息
				$this->updateActualDetailStatus($client, $order['actual_detail_id'], $status_error, $msg);
				$countActualDetailError++;
			}
			
			//如果已经执行完一个实绩的所有明细，则修改实绩状态
			$res = $client->hasActualDetailByActualAndStatus(array("actualId"=>$order['actual_id'], "actualDetailStatus"=>$status_received))->return; 
			if (! $res) {
				//判断是否有执行错误的实绩明细
				$res = $client->hasActualDetailByActualAndStatus(array("actualId"=>$order['actual_id'], "actualDetailStatus"=>$status_error))->return;
				if ($res) {
					//实绩状态改为错误
					$this->log("actualId: {$order['actual_id']} error ");
					$this->updateActualStatus($client, $order['actual_id'], $status_error);
					$countActualError++;
				} else {
					//实绩状态改为完成
					$this->log("actualId: {$order['actual_id']} finish ");
					$this->updateActualStatus($client, $order['actual_id'], $status_finished);
					$countActualFinish++;
				}
			}
			
			//如果已经执行完一个指示的所有实绩，并且该指示的采购订单已全部入库,则将指示状态改为完成
			$res = $client->hasActualByIndicateAndStatus(array("indicateId"=>$order['indicate_id'], "actualStatus"=>$status_received))->return;
			
			$sql = "select m.order_id, m.is_cancelled from ecshop.ecs_batch_order_mapping m 
				where m.batch_order_id = {$order['order_id']}
				and m.is_cancelled <> 'Y' and m.is_over_c <> 'Y'
			";
			$purchase_orders = $this->getMaster()->createCommand ($sql)->queryAll();
			$is_in_storage = true;
			foreach($purchase_orders as $purchase_order) {
				if (! check_order_all_in_storage($purchase_order['order_id'])) {
					$is_in_storage = false;
					break;
				}
			}
			if (! $res && $is_in_storage) {
				$this->log("indicateId: {$order['indicate_id']} finish ");
				$this->updateIndicateStatus($client, $order['indicate_id'], $status_finished);
				$countIndicateFinish++;
			}
		}
		}
		
		$this->log("总实绩明细记数: {$countAll}, 实绩明细错误记数: {$countActualDetailError}, 实绩明细完成记数: {$countActualDetailFinish}" . 
		           "实绩错误记数: {$countActualError}, 实绩完成记数: {$countActualFinish}, 指示完成记数: {$countIndicateFinish}" . 
		          "耗时: " . (microtime(true)-$start));
		$this->log("InventoryInActual end");
	}
	
    /**
     * 处理出库实绩、自动出库发货
     */
    public function actionInventoryOutActul() {
    	//验证是否满足出库条件 单个商品出库
    	//检查实绩出库明细、修改出库实绩状态
    	//检查出库实绩状态 修改出库指示状态
    	//根据指示状态、修改订单状态出库发货
    	$this->log(" InventoryOutActul begin ");
    	$client=Yii::app()->getComponent('romeo')->IndicateService;
    	$party_id = 65585;//TODO
 		$inventory_type = 'INVENTORY_OUT';
 		$status_received = 'RECEIVED';
 		$status_finished = 'FINISHED';
 		$status_error = 'ERROR';
 		
 		$start_date = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	$end_date = date("Y-m-d H:i:s", time());
 		
 		$start = microtime(true);
 		$countAll = $countIndicateFinish = $countActualFinish = $countActualError = 
 			$countActualDetailFinish = $countActualDetailError = 0;
 		
 		// 获得实绩
 		$sql = "
			select i.indicate_id, d.actual_id, d.actual_detail_id, i.party_id, i.order_id, d.goods_type, d.goods_number, 
                    id.order_goods_id, id.goods_id, id.style_id, i.facility_id 
            from ecshop.ecs_indicate i 
            inner join ecshop.ecs_actual_detail d on i.indicate_id = d.indicate_id
            left join ecshop.ecs_indicate_detail id on id.indicate_detail_id = d.indicate_detail_id
            where i.party_id = {$party_id}
            and i.indicate_type = '{$inventory_type}'
            and i.indicate_status = '{$status_received}'
            and d.actual_detail_status = '{$status_received}'
            and d.created_stamp >= '{$start_date}'
			and d.created_stamp < '{$end_date}'
            order by i.indicate_id, d.actual_id
 		";
 		$actual_list = $this->getMaster()->createCommand ($sql)->queryAll();
		$countAll = count($actual_list);
		if (!empty($actual_list)) {
			foreach ($actual_list as $item ) {
				$msg = null;
 				$is_error = false;
				$inventory_status = $item['goods_type'] == '良品' ? 'INV_STTS_AVAILABLE' : 'INV_STTS_USED';
				//已配货待出库
				//先检查订单状态是否已配货否则先进行配货
				$sql = "select shipping_status from ecshop.ecs_order_info where order_id = {$item['order_id']} limit 1";
				$shipping_status = $this->getMaster()->createCommand($sql)->queryScalar();
				if ($shipping_status == 0) {
					$shipping_9 = update_shipping_status($item['order_id'], 9);
	 				if ($shipping_9['msg'] != 'success') {
	 					$this->log($shipping_9['back']);
	 					//TODO 
	 				}
				}
	 			$info = check_out_goods($item);
 				if ($info['msg'] != 'success') {
	 				$msg = "check_out_goods: {$info['back']}";
	 				$this->log("actualDetailId: {$item['actual_detail_id']} error: {$msg}");
	 				//记录实绩明细错误信息
	 				$this->updateActualDetailStatus($client, $item['actual_detail_id'], $status_error, $msg);
	 				$countActualDetailError++;
	 				$is_error = true;
 				}
 				if (! $is_error) {
					//记录实绩明细完成信息
					$this->log("actualDetailId: {$order['actual_detail_id']} finish");
					$this->updateActualDetailStatus($client, $item['actual_detail_id'], $status_finished, $msg);
					$countActualDetailFinish++;
				}
				//如果已经执行完一个实绩的所有明细，则修改实绩状态
	 			$res = $client->hasActualDetailByActualAndStatus(array("actualId"=>$item['actual_id'], "actualDetailStatus"=>$status_received))->return; 
	 			if (! $res) {
	 				//判断是否有执行错误的实绩明细
	 				$res = $client->hasActualDetailByActualAndStatus(array("actualId"=>$item['actual_id'], "actualDetailStatus"=>$status_error))->return;
	 				if ($res) {
	 					//实绩状态改为错误
	 					$this->log("actualId: {$order['actual_id']} error ");
	 					$this->updateActualStatus($client, $item['actual_id'], $status_error);
	 					$countActualError++;
	 				} else {
	 					//实绩状态改为完成
	 					$this->log("actualId: {$order['actual_id']} finish ");
	 					$this->updateActualStatus($client, $item['actual_id'], $status_finished);
	 					$countActualFinish++;
	 				}
	 			}
	 			
	 			//如果已经执行完一个指示的所有实绩，并且该指示的采购订单已全部入库,则将指示状态改为完成
	 			$res = $client->hasActualByIndicateAndStatus(array("indicateId"=>$item['indicate_id'], "actualStatus"=>$status_received))->return;
	 			if (! $res && check_batch_out_storage_status($item['order_id'])) {
	 				$this->log("indicateId: {$order['indicate_id']} finish ");
	 				$res = $this->updateIndicateStatus($client, $item['indicate_id'], $status_finished);
	 				$countIndicateFinish++;
 					//已出库待发货
	 				$shipping_8 = update_shipping_status($item['order_id'], 8);
	 				if ($shipping_8['msg'] == "success") {
	 					//已发货
	 					$shipping_result = update_shipping_status($item['order_id'], 1);
 						$this->log($shipping_result['back']);
	 				} else {
	 					$this->log($shipping_8['back']);
	 				}
	 			}
			}
		}
    	$this->log("总实绩明细记数: {$countAll}, 实绩明细错误记数: {$countActualDetailError}, 实绩明细完成记数: {$countActualDetailFinish}" . 
 		           "实绩错误记数: {$countActualError}, 实绩完成记数: {$countActualFinish}, 指示完成记数: {$countIndicateFinish}" . 
 		          "耗时: " . (microtime(true)-$start));
 		$this->log("InventoryInActual end");
    }
	
	
	/**
	 * 处理退货实绩、自动退货入库
	 */
 	public function actionInventoryReturnActual() {
		$this->log("InventoryReturnActual begin");
		
		$client=Yii::app()->getComponent('romeo')->IndicateService;
		
		$party_id = 65585;
		$inventory_type = 'INVENTORY_RETURN';
		$status_received = 'RECEIVED';
		$status_finished = 'FINISHED';
		$status_error = 'ERROR';
		
		$start = microtime(true);
		$countAll = 0;
		$countIndicateFinish = 0;
		$countActualFinish = 0;
		$countActualError = 0;
		$countActualDetailFinish = 0;
		$countActualDetailError = 0;
		
		$start_date = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	$end_date = date("Y-m-d H:i:s", time());
		
		// 获得实绩
		$sql = "select i.indicate_id, i.service_id, i.order_id, sum(d.goods_number) as goods_number, 
				(select sum(id.goods_number) from ecshop.ecs_indicate_detail id where id.indicate_id = i.indicate_id ) as indicate_goods_number 
			from ecshop.ecs_indicate i 
			inner join ecshop.ecs_actual_detail d on i.indicate_id = d.indicate_id 
			where i.party_id = {$party_id} 
			and i.indicate_type = '{$inventory_type}'
			and i.indicate_status = '{$status_received}'
			and d.actual_detail_status = '{$status_received}'
			and d.created_stamp >= '{$start_date}'
			and d.created_stamp < '{$end_date}'
			group by i.indicate_id
			having goods_number >= indicate_goods_number 
		";
		$orders = $this->getMaster()->createCommand ($sql)->queryAll();
		foreach ($orders as $order) {
			$indicate_id = $order['indicate_id'];
			$service_id = $order['service_id'];
			
			//判断数量是否合法
			$sql = "select d.goods_number, sum(ad.goods_number)
				from ecshop.ecs_indicate_detail d
				inner join ecshop.ecs_actual_detail ad on d.indicate_detail_id = ad.indicate_detail_id
				where d.indicate_id = {$indicate_id} and ad.actual_detail_status = '{$status_received}'
				group by d.indicate_detail_id 
				having d.goods_number <> sum(ad.goods_number)
			 ";
			$d = $this->getMaster()->createCommand ($sql)->queryAll();
			if (empty($d)) {
				//自动售后退货入库
				$info = auto_service($service_id);
			} else {
				$info = array ('res' => 'fail', 'back' => '退货实绩数量大于指示数量');
			}
			
			$service_result = $client->getActualList(array("indicateId"=>$indicate_id, "actualStatus"=>$status_received))->return;
			if ($service_result->code != 'SUCCEED') {
				$this->log("getActualList FAIL indicateId: {$indicate_id}, actualStatus: {$status_received}, msg: {$service_result->msg}");
				continue;
			} 
			
			if (empty($service_result->result->Actual)) {
				$this->log("getActualList FAIL indicateId: {$indicate_id}, actualStatus: {$status_received}, result is empty");
				continue;
			} 
			$actual_list = wrap_object_to_array($service_result->result->Actual);
			$status = $info['res'] != 'success' ? $status_error : $status_finished;
			$msg = $info['back'];
			foreach($actual_list as $actual) {
					$actualDetailList = wrap_object_to_array($actual->actualDetailList->ActualDetail);
				foreach ( $actualDetailList as $actual_detail ) {
					$this->log("actualDetailId: {$actual_detail->actualDetailId} {$status}");
					$this->updateActualDetailStatus($client, $actual_detail->actualDetailId, $status, $msg);
					$info['res'] != 'success' ? $countActualDetailError++ : $countActualDetailFinish++;
					$countAll++;
				}
				$this->log("actualId: {$actual->actualId} {$status} ");
				$this->updateActualStatus($client, $actual->actualId, $status);
				$info['res'] != 'success' ? $countActualError++ : $countActualFinish++;
			}
			
			//如果service有back_order_id，并且已全部入库,则将指示状态改为完成
			if ($info['res'] == 'success') {
				$back_order_id = get_back_order_id($service_id);
				if ($back_order_id && check_back_order_all_in_storage($back_order_id)) {
					$this->log("indicateId: {$indicate_id} finish ");
					$this->updateIndicateStatus($client, $indicate_id, $status_finished);
					$countIndicateFinish++;
				}
				
			}
		}
		
		$this->log("总实绩明细记数: {$countAll}, 实绩明细错误记数: {$countActualDetailError}, 实绩明细完成记数: {$countActualDetailFinish}" . 
		           "实绩错误记数: {$countActualError}, 实绩完成记数: {$countActualFinish}, 指示完成记数: {$countIndicateFinish}" . 
		          "耗时: " . (microtime(true)-$start));
		$this->log("InventoryReturnActual end");
	}
	
	
	/**
	 * 处理退库实绩、自动-gt出库
	 */
 	public function actionSupplierReturnActual() {
		$this->log("SupplierReturnActual begin");
		
		$client=Yii::app()->getComponent('romeo')->IndicateService;
		
		$party_id = 65585;
		$inventory_type = 'SUPPLIER_RETURN';
		$status_received = 'RECEIVED';
		$status_finished = 'FINISHED';
		$status_error = 'ERROR';
		
		$start = microtime(true);
		$countAll = 0;
		$countIndicateFinish = 0;
		$countActualFinish = 0;
		$countActualError = 0;
		$countActualDetailFinish = 0;
		$countActualDetailError = 0;
		
		$start_date = date('Y-m-d 00:00:00', strtotime("-7 day"));
    	$end_date = date("Y-m-d H:i:s", time());
		
		// 获得实绩
		$sql = "select i.party_id, i.indicate_id, i.supplier_return_id, d.actual_id, 
				d.actual_detail_id, d.goods_type, d.goods_number 
			from ecshop.ecs_indicate i 
			inner join ecshop.ecs_actual_detail d on i.indicate_id = d.indicate_id 
			where i.party_id = {$party_id} 
			and i.indicate_type = '{$inventory_type}'
			and i.indicate_status = '{$status_received}'
			and d.actual_detail_status = '{$status_received}'
			and d.created_stamp >= '{$start_date}'
			and d.created_stamp < '{$end_date}'
		";
		$orders = $this->getMaster()->createCommand ($sql)->queryAll();
		foreach ($orders as $order) {
			$msg = null;
			
			//判断数量是否合法
			$supplier_return_amount = get_supplier_return_amount($order['supplier_return_id']);
			if ($supplier_return_amount['amount'] + $order['goods_number'] <= $supplier_return_amount['return_order_amount']) {
				//自动-gt出库
				$info = actual_supplier_return($order['party_id'], $order['supplier_return_id'], $order['goods_number']);
				
			} else {
				$info = array ('res' => 'fail', 'back' => '本次出库数量加上已出库数量，大于申请数量');
			}
			if ( $info['res'] != 'success') {
				$msg = "actual_supplier_return: {$info['back']}";
				$this->log("actualDetailId: {$order['actual_detail_id']} error: {$msg}");
				
				//记录实绩明细错误信息
				$this->updateActualDetailStatus($client, $order['actual_detail_id'], $status_error, $msg);
				$countActualDetailError++;
			} else {
				//记录实绩明细完成信息
				$this->log("actualDetailId: {$order['actual_detail_id']} finish");
				$this->updateActualDetailStatus($client, $order['actual_detail_id'], $status_finished, $msg);
				$countActualDetailFinish++;
			}
			
			//如果已经执行完一个实绩的所有明细，则修改实绩状态
			$res = $client->hasActualDetailByActualAndStatus(array("actualId"=>$order['actual_id'], "actualDetailStatus"=>$status_received))->return; 
			if (! $res) {
				//判断是否有执行错误的实绩明细
				$res = $client->hasActualDetailByActualAndStatus(array("actualId"=>$order['actual_id'], "actualDetailStatus"=>$status_error))->return;
				if ($res) {
					//实绩状态改为错误
					$this->log("actualId: {$order['actual_id']} error ");
					$this->updateActualStatus($client, $order['actual_id'], $status_error);
					$countActualError++;
				} else {
					//实绩状态改为完成
					$this->log("actualId: {$order['actual_id']} finish ");
					$this->updateActualStatus($client, $order['actual_id'], $status_finished);
					$countActualFinish++;
				}
			}
			
			//如果已经执行完一个指示的所有实绩，并且该-gt申请已经完成,则将指示状态改为完成
			$res = $client->hasActualByIndicateAndStatus(array("indicateId"=>$order['indicate_id'], "actualStatus"=>$status_received))->return;
			if (! $res && get_supplier_return_status($order['supplier_return_id']) == 'COMPLETION') {
				$this->log("indicateId: {$order['indicate_id']} finish ");
				$this->updateIndicateStatus($client, $order['indicate_id'], $status_finished);
				$countIndicateFinish++;
			}
		}
		
		$this->log("总实绩明细记数: {$countAll}, 实绩明细错误记数: {$countActualDetailError}, 实绩明细完成记数: {$countActualDetailFinish}" . 
		           "实绩错误记数: {$countActualError}, 实绩完成记数: {$countActualFinish}, 指示完成记数: {$countIndicateFinish}" . 
		          "耗时: " . (microtime(true)-$start));
		$this->log("SupplierReturnActual end");
	}
	
	private function log ($m) {
		print date("Y-m-d H:i:s") . " " . $m . "\r\n";
    }
    /**
     * 传入ERP快递方式的名称，转换为NB的快递名称
     */
	private function get_shipping_name ($erp_shipping_name) {
		$shipping_list = array(
			"EMS快递" => "EMS快递",
			"顺丰快递" => "顺丰快递",
			"申通快递" => "申通快递",
			"顺丰（陆运）" => "顺丰快递",
			"圆通快递" => "圆通快递",
		);
		if (array_key_exists($erp_shipping_name, $shipping_list)) {
			return $shipping_list[$erp_shipping_name] ;
		} else {
			return $erp_shipping_name ;
		}
	}
	
	 /**
 	 * 查看ftp中对方读取超时的文件
 	 */
 	public function actionCheckFile() {
		$start = time();
		echo date('c') . " CheckFile start \n";
		//登录ftp 读取文件列表
		$conn_ftp = $this->erpLoginFtp();
		$file_names = null;
		if ($conn_ftp) {
			global $nb_ftp_indicates;
			$file_name_list = ftp_nlist($conn_ftp, $nb_ftp_indicates);
			foreach ($file_name_list as $item) {
				$file_name = basename($item);
				$pp = preg_split('/\./', $file_name);
				$datetime = substr($pp[0], strlen($pp[0])-14, strlen($pp[0]));
				
				//文件名时间，与当期时间相差35分钟以上
				if ((time() - strtotime($datetime)) >= 60 * 35) {
					$this->log($file_name . " 未及时处理");
					$file_names .= $file_name . "<br>";
				}
			}
		}
		ftp_close($conn_ftp);
		
		if ($file_names) {
			//邮件通知
			try {
				$mail = Yii::app ()->getComponent ( 'mail' );
				$mail->Subject = "伊藤忠未及时处理指示文件";
				$mail->Body = $file_names;
				$mail->ClearAddresses ();
				$mail->AddAddress ( 'jwang@leqee.com', '王健' );
				$mail->send ();
			} catch ( Exception $e ) {
				var_dump ( "发送邮件异常" );
			}
			$this->sendMessage("伊藤忠未及时处理指示文件【乐其NB监控】");
		}
 	}
 	
 	//错误日志处理
 	function actionErrorLog() {
		$start = time();
		echo date('c') . " ErrorLog start \n";
		//登录ftp 切换目录  下载文件
		$conn_ftp = $this->erpLoginFtp();
		$file_names = null;
		if ($conn_ftp) {
			global $nb_ftp_error_log;
			$file_name_list = ftp_nlist($conn_ftp, $nb_ftp_error_log);
			foreach ($file_name_list as $item) {
				$name = basename($item);
				if (strpos($name, ".")) {
					$down_result = $this->downloadFile($conn_ftp, $name, $nb_ftp_error_log);
					if ($down_result) {
						$this->backupFile($conn_ftp, $name);
					}
				}
			}
			foreach ($file_name_list as $item) {
				$file_name = basename($item);
				if (strpos($file_name,".")) {
					$this->log($file_name . " 错误日志");
					$file_names .= $file_name . "<br>";
				}
			}
		}
		ftp_close($conn_ftp);
		
		if ($file_names) {
			//邮件通知
			try {
				$mail = Yii::app ()->getComponent ( 'mail' );
				$mail->Subject = "伊藤忠有错误日志返回";
				$mail->Body = $file_names;
				$mail->ClearAddresses ();
				$mail->AddAddress ( 'jwang@leqee.com', '王健' );
				$mail->send ();
			} catch ( Exception $e ) {
				var_dump ( "发送邮件异常" );
			}
			
			$this->sendMessage("伊藤忠有错误日志返回，请及时处理【乐其NB监控】");
		}
		
 	
 	}
 	
 	function sendMessage($content) {
 		$send=send_message($content, array('13567177855'), null, 'emay');
		if ($send==0){
			$this->log("短信发送成功{$content}");
		} else {
			$this->log("短信发送失败{$content}");
		}
 	}
}	
	

