<?php
/**
 * 监工说，要把面单搞成div拼起来的。
 * 于是我们伸出了触手。
 * 社畜们被逼上了绝路！
 * 这里只处理热敏的。
 */
require_once('mini_init.php');
require_once(ROOT_PATH . 'admin/function.php');
include_once ('lib_order.php');
require_once (ROOT_PATH . 'includes/debug/lib_log.php');
require_once (ROOT_PATH . 'admin/PartyXTelephone.php');
require_once('lib_sf_arata_insure.php');

/**
* LibSinriDivCarrierBill 使（邪恶）用（的）说明（忠告）
* ----------------------------------------------------
* 本类只负责找出订单对应的热敏的面单打印模板和上面的参数。
* 不负责更新面单号什么的。这个类只管读数据库。
* 打印模板在 waybill_div 目录下面。
* 参数就是assignment里面的。
*/
class LibSinriDivCarrierBill
{
	public $order_id;
	public $tracking_number;

	public $order_info;

	public $assignment;

	private $db_cache;

	private function db(){
		global $db;
		return $db;
	}

	function __construct($order_id,&$db_cache_obj=null)
	{
		if(empty($order_id)){
			throw new Exception("是谁传了个空的订单号进来的，拖出去打一顿！", 1);
		}

		if(is_a($db_cache_obj, 'LibSinriDivCarrierBillDBCache')){
			$this->db_cache=$db_cache_obj;
		}else{
			$this->db_cache=null;
		}
		
		$this->assignment=array(
			'order'=>array('order_id'=>$this->order_id),
			'arata'=>array(),
			'goods'=>array(),
			'rank'=>'',
		);
		
		$this->order_id=$order_id;
		$this->tracking_number=$this->getTrackingNumber(); //$tracking_number;

		$this->updateOrderInfo();

		
	}

	public function getTrackingNumber(){
		if($this->db_cache){
			return $this->db_cache->getTrackingNumber($this->order_id);
		}else{
			$sql="SELECT s.tracking_number,oa.attr_value 
			FROM ecshop.ecs_order_info oi 
			INNER JOIN romeo.order_shipment os on convert(oi.order_id using utf8) = os.order_id
			INNER JOIN romeo.shipment s ON os.shipment_id=s.shipment_id
			LEFT JOIN ecshop.order_attribute oa on oa.order_id = oi.order_id and attr_name='JDsendCode' and oi.shipping_id=146
			WHERE s.status!='SHIPMENT_CANCELLED' and s.tracking_number is not null -- and s.tracking_number!='' 
			and oi.order_id={$this->order_id}
			";
			$lines=$this->db()->getRow($sql);
			if(empty($lines)){
				return '';
			}elseif(empty($lines['tracking_number'])){
				return 200;
			}else{
				return $lines['tracking_number'];
			}
		}
	}

	/**
	 * 返回模板的文件名(位于 .../waybill_div/XXX)
	 * @return STRING 模板的文件名
	 */
	public function getTPL(){
		$tpl="test.htm";
		switch ($this->order_info['carrier_id']) {
			case 3://圆通快递
				$tpl = 'yto-arata-div.htm';
				break;
			case 5://宅急送
				$tpl = "zjs-arata-div.htm";
				break;
			case 10://顺丰快递
			case 44://顺丰快递 るゆん
				$tpl = "sf-arata-div.htm";
				break;
			case 20://申通快递
				$tpl = "sto-arata-div.htm";
				break;
			case 28://汇通快递
				$tpl = 'ht-arata-div.htm';
				break;
			case 29://韵达快递
				$tpl = 'yunda-arata-div.htm';
				break;
			case 41://中通快递
				$tpl = 'zto-arata-div.htm';
				break;
			case 61://速达快递
				$tpl = 'sd-arata-div.htm';
				break;
			case 62://京东COD
				$tpl = 'jd-cod-arata-div.htm';
				break;
			case 63://京东配送
				$tpl = 'jdps-arata-div.htm';
				break;
			case 13 ://万象物流
				$tpl = 'wx-arata-div.htm';
				break;
			default:
				throw new Exception("然而并没有能用的模板。订单号：".$this->order_info['order_id'].'；订单CARRIER_ID：'.$this->order_info['carrier_id'], 1);
				break;
		}
		return $tpl;
	}

	/**
	 * 返回传入模板的参数列表
	 * @return ARRAY 传入模板的参数列表
	 */
	public function getAssignments(){
		
		// COMMON
		$this->assignment['order']['consignee']=$this->order_info['consignee'];
		$this->assignment['order']['mobile']=$this->order_info['mobile'];
		$this->assignment['order']['tel']=$this->order_info['tel'];
		$this->assignment['order']['province']=$this->order_info['province_name'];
		$this->assignment['order']['city']=$this->order_info['city_name'];
		$this->assignment['order']['district']=$this->order_info['district_name'];
		$this->assignment['order']['address']=$this->order_info['address'];
		$this->assignment['order']['order_amount']=$this->order_info['order_amount'];

		$this->assignment['order']['remarks'] = '';
		$this->assignment['order']['need_insure'] = false;
		$this->assignment['order']['is_sf_cod'] = false;
		$this->assignment['order']['sf_cod_note'] = false;// sf cod，babynes的特殊日志
		$this->assignment['order']['sf_note'] = false; //路易十三 顺丰热敏备注

		if(empty($this->assignment['order']['tel'])){
			$this->assignment['order']['tel']='';
		}
		if(empty($this->assignment['order']['mobile'])){
			$this->assignment['order']['mobile']=$this->assignment['order']['tel'];
		}

		if('65565' == $this->order_info['party_id']){
		   //乐贝蓝光
		   $this->assignment['order']['c_tel'] = '0571-28181301';
		}else{
		   $this->assignment['order']['c_tel'] = sinri_get_telephone_for_order_to_print($this->order_id);
		}
		if('65670' == $this->order_info['party_id'] && in_array($this->order_info['carrier_id'],array(10,44))){
			$this->assignment['order']['sf_note'] = true;
		}
		$this->updateGoodsInfo();

		switch ($this->order_info['carrier_id']) {
			case 3://圆通快递
				$this->getAssignmentsForYTO();
				break;
			case 5://宅急送
				$this->getAssignmentsForZJS();
				break;
			case 10://顺丰快递
			case 44:
				$this->getAssignmentsForSF();
				break;
			case 20://申通快递
				$this->getAssignmentsForSTO();
				break;
			case 28://汇通快递
				$this->getAssignmentsForHT();
				break;
			case 29://韵达快递
				$this->getAssignmentsForYunda();
				break;
			case 41://中通快递
				$this->getAssignmentsForZTO();
				break;
			case 61://速达快递
				$this->getAssignmentsForSD();
				break;
			case 62://京东COD
				$this->getAssignmentsForJDCOD();
				break;
			case 63://京东配送
				$this->getAssignmentsForJDPS();
				break;
			case 13://万象物流
				$this->getAssignmentsForWX();
				break;
			default:
				// throw new Exception("然而并没有能用的模板", 1);
				break;
		}
		
		return $this->assignment;		
	}

	private function updateOrderInfo(){
		if($this->db_cache){
			$this->order_info = $this->db_cache->getOrderInfo($this->order_id);
		}else{
			$sql="SELECT
				oi.order_id,
				oi.order_sn,
				oi.taobao_order_sn,
				es.default_carrier_id carrier_id,
				oi.shipping_id,
				oi.province,
				rp.region_name province_name,
				oi.city,
				rc.region_name city_name,
				oi.district,
				rd.region_name district_name,
				oi.address,
				oi.distributor_id,
				oi.tel,
				oi.mobile,
				oi.consignee,
				oi.facility_id,
				oi.party_id,
				p.`NAME` party_name,
				oi.order_amount,
				oi.bonus,
				ccm.city_code sf_city_code
			FROM
				ecshop.ecs_order_info oi
			LEFT JOIN ecshop.ecs_shipping es ON oi.shipping_id = es.shipping_id
			LEFT JOIN ecshop.ecs_region rp ON oi.province = rp.region_id
			LEFT JOIN ecshop.ecs_region rc ON oi.city = rc.region_id
			LEFT JOIN ecshop.ecs_region rd ON oi.district = rd.region_id
			LEFT JOIN romeo.party p ON CONVERT (oi.party_id USING utf8) = p.PARTY_ID
			LEFT JOIN ecshop.ecs_city_code_mapping ccm ON ccm.city_id = oi.city -- 顺丰快递查出城市编码
			WHERE
				oi.order_id = ".$this->order_id;
			$this->order_info = $this->db()->getRow($sql);
		}
		$this->order_info['facility_type']=$this->getFacilityType();

		if(strlen($this->order_info['city_name'])>20){
			$this->assignment['order']['bigPen'] = $this->order_info['province_name'].$this->order_info['district_name'];
		}elseif(in_array($this->order_info['province_name'],array('北京','上海','天津','重庆'))){
			$this->assignment['order']['bigPen'] = $this->order_info['province_name'].$this->order_info['city_name'];	
		}else{
			$this->assignment['order']['bigPen'] = $this->order_info['province_name'].$this->order_info['city_name'].$this->order_info['district_name'];	
		}

		$this->order_info['goods_type']=sinri_get_goods_type_for_party_to_print($this->order_info['party_id'],$this->order_info['distributor_id']);
		//乐其电教的顺丰东莞快递order_type变为 点读机+配件，并且显示数量 -> 这个有必要做进热敏吗好可疑所以先不管吧

		$this->order_info['goods_amount'] = $this->order_info['goods_amount'] + $this->order_info['bonus'];
		$this->order_info['tracking_number'] = $tracking_num;
	}

	private function getFacilityType(){
		$F=array();
		$F['SH'] = array('176053000','137059424','120801050','12768420', '19568549', '22143846', '22143847','24196974', 
		'3633071', '69897656', '81569822', '81569823', '83972713', '83972714',
		'119603093','119603091','137059426','149849259','194788297');
		$F['DG'] = array('105142919','19568548','3580046','3580047','49858449','53316284','76065524','83077349');
		$F['BJ'] = array('100170589','42741887','79256821','83077350','119603092');
		$F['CD'] = array("137059428","137059431");
		$F['JX'] = array('149849256');
		$F['WH'] = array('137059427');
		$F['KBFX'] = array('119603094');
		$sql="SELECT facility_id from romeo.facility where is_out_ship = 'Y'";
		$F['OUTSOURCE'] = $this->db()->getCol($sql);
		$F['FRUIT'] = array('185963128','185963130','185963132','185963134','185963136','185963138','185963140','185963142','185963147','185963148');
		$F['WLBJC'] = array('253372945'); //万霖北京仓
		$F['HQWHC'] = array('253372944'); //华庆武汉仓

		foreach ($F as $f_type => $f_list) {
			if(in_array($this->order_info['facility_id'], $f_list)){
				return $f_type;
			}
		}
		return 'UNKNOWN';
	}

	private function updateGoodsInfo(){
		if($this->order_info['facility_type']=='OUTSOURCE'){
			$item_sql = " SELECT A.ORDER_ID,@rank:=@rank+1 as pm  
					     FROM     
					     ( SELECT bpm.shipment_id,os2.ORDER_ID from romeo.out_batch_pick_mapping bpm  
					INNER JOIN romeo.order_shipment os2 on os2.shipment_id = bpm.shipment_id 
					INNER JOIN romeo.out_batch_pick_mapping bpm2 on bpm2.batch_pick_id = bpm.batch_pick_id
					INNER JOIN romeo.order_shipment os on os.SHIPMENT_ID = bpm2.shipment_id
					where os.ORDER_ID = '{$this->order_id}'
					     ) A,(SELECT @rank:=0) B   ";
			$rank = $this->db()->getAll($item_sql);
			foreach($rank as $key=>$value){
				if($value['ORDER_ID'] == $order_id){
					// $smarty->assign('rank', $value['pm']);
					$this->assignment['rank']=$value['pm'];
					break;
				}
			}
			
			$sql = "SELECT 
						'out' as tc_code,
						if(group_name is null or group_name='',
						concat_ws('_',goods_name,goods_number),
						concat_ws('_',group_name,group_number)) as sku_num 
					FROM ecshop.ecs_order_goods 
					where order_id = '{$this->order_id}' limit 1 ";
		}else{
			//检查合并订单
			$order_id_arr = $this->db()->getCol("SELECT DISTINCT os2.order_id
		         from romeo.order_shipment os1
				inner join romeo.order_shipment os2 on os1.shipment_id = os2.shipment_id 
		        where os1.order_id = '{$this->order_id}' ");
			$order_id_str = implode("','",$order_id_arr);	
			$sql = "SELECT '' as tc_code,
				concat_ws('-',if(gs.barcode is null or gs.barcode='' ,g.barcode,gs.barcode),sum(og.goods_number)) as sku_num 
			from ecshop.ecs_order_goods og 
			left join ecshop.ecs_goods_style gs on gs.goods_id = og.goods_id and gs.style_id = og.style_id and gs.is_delete=0
			left join ecshop.ecs_goods g on g.goods_id = og.goods_id 
			where og.order_id in ('{$order_id_str}') 
			group by og.goods_id,og.style_id ";
		}
		$goods = $this->db()->getAll($sql); 
		if(count($goods)<=5){
			// $smarty->assign('goods', $goods);  // 面单上添加对应商品条码及数量
			$this->assignment['goods']=$goods;
		}
	}

	// Beneath for each CARRIER
	
	private function getAssignmentsForZTO(){
		$this->assignment['arata']['sentBranch']='';
		$this->assignment['arata']['ztoSender']='';
		$this->assignment['arata']['tracking_number']='';
		$this->assignment['arata']['service_type']='';
		
		$this->assignment['order']['c_tel']='';
		$this->assignment['order']['company_address']='';

		$sql = "SELECT attr_value from ecshop.order_attribute where order_id = '{$this->order_id}' and attr_name='ztoBigPen' ";
		$zto_mark = $this->db()->getOne($sql);
		if(!empty($zto_mark)) $this->assignment['order']['bigPen'] = $zto_mark;
		if($this->order_info['facility_type']=='SH'){
			$this->assignment['arata']['ztoSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='上海市场部';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			$this->assignment['order']['company_address'] = '上海市场部已验视';
		}elseif($this->order_info['facility_type']=='BJ'){
			$this->assignment['arata']['ztoSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='北京业务部';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			$this->assignment['order']['company_address'] = '北京业务部已验视';
		}elseif($this->order_info['facility_type']=='JX'){
			$this->assignment['arata']['ztoSender']='拼好货商城';
			$this->assignment['arata']['sentBranch']='拼好货商城';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']='生鲜';
			$this->assignment['order']['company_address'] = '嘉兴市秀洲区嘉欣丝绸工业园24号';
		}elseif($this->order_info['facility_type']=='CD'){
			$this->assignment['arata']['ztoSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='成都市场部';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			$this->assignment['order']['company_address'] = '成都市场部已验视';
			$this->assignment['order']['c_tel'] = '028-85712080';
		}elseif($this->order_info['facility_type']=='OUTSOURCE' || $this->order_info['facility_type']=='FRUIT'){
			$this->assignment['arata']['ztoSender']='乐其';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			if(in_array($this->order_info['facility_id'],array('185963127','185963128'))){
				$this->assignment['arata']['sentBranch']='北京';
				$this->assignment['order']['company_address'] = '北京市大兴区小龙庄路63号';
			}elseif(in_array($this->order_info['facility_id'],array('185963131','185963132'))){
				$this->assignment['arata']['sentBranch']='浙江嘉兴';
				$this->assignment['order']['company_address'] = '浙江省嘉兴市南湖区';
			}elseif(in_array($this->order_info['facility_id'],array('185963133','185963134'))){
				$this->assignment['arata']['sentBranch']='上海青浦';
				$this->assignment['order']['company_address'] = '上海市青浦区青赵公路5009号';
			}elseif(in_array($this->order_info['facility_id'],array('185963137','185963138'))){
				$this->assignment['arata']['sentBranch']='江苏苏州';
				$this->assignment['order']['company_address'] = '江苏省苏州市高新区';
			}elseif(in_array($this->order_info['facility_id'],array('185963139','185963140'))){
				$this->assignment['arata']['sentBranch']='四川成都';
				$this->assignment['order']['company_address'] = '四川省成都市新都区';
			}elseif(in_array($this->order_info['facility_id'],array('185963141','185963142'))){
				$this->assignment['arata']['sentBranch']='湖北武汉';
				$this->assignment['order']['company_address'] = '湖北省武汉市东西湖区';
			}elseif(in_array($this->order_info['facility_id'],array('185963146','185963147'))){
				$this->assignment['arata']['sentBranch']='广东深圳';
				$this->assignment['order']['company_address'] = '广东省深圳市光明新区';
			}elseif(in_array($this->order_info['facility_id'],array('185963129','185963130'))){
				$this->assignment['arata']['sentBranch']='广东广州';
				$this->assignment['order']['company_address'] = '广东省广州市';
			}elseif(in_array($this->order_info['facility_id'],array('185963135','185963136'))){
				$this->assignment['arata']['sentBranch']='江苏南京';
				$this->assignment['order']['company_address'] = '江苏省南京市江宁区';
			}
		}		
	}

	private function getAssignmentsForZJS(){
		if($this->order_info['facility_type']=='OUTSOURCE' || $this->order_info['facility_type']=='FRUIT'){
			$this->assignment['arata']['zjsSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			if(in_array($this->order_info['facility_id'],array('185963127','185963128'))){
				$this->assignment['arata']['sentBranch']='北京';
				$this->assignment['order']['company_address'] = '北京市大兴区小龙庄路63号';
			}elseif(in_array($this->order_info['facility_id'],array('185963131','185963132'))){
				$this->assignment['arata']['sentBranch']='浙江嘉兴';
				$this->assignment['order']['company_address'] = '浙江省嘉兴市南湖区';
			}elseif(in_array($this->order_info['facility_id'],array('185963133','185963134'))){
				$this->assignment['arata']['sentBranch']='上海青浦';
				$this->assignment['order']['company_address'] = '上海市青浦区青赵公路5009号';
			}elseif(in_array($this->order_info['facility_id'],array('185963137','185963138'))){
				$this->assignment['arata']['sentBranch']='江苏苏州';
				$this->assignment['order']['company_address'] = '江苏省苏州市高新区';
			}elseif(in_array($this->order_info['facility_id'],array('185963139','185963140'))){
				$this->assignment['arata']['sentBranch']='四川成都';
				$this->assignment['order']['company_address'] = '四川省成都市新都区';
			}elseif(in_array($this->order_info['facility_id'],array('185963141','185963142'))){
				$this->assignment['arata']['sentBranch']='湖北武汉';
				$this->assignment['order']['company_address'] = '湖北省武汉市东西湖区';
			}elseif(in_array($this->order_info['facility_id'],array('185963146','185963147'))){
				$this->assignment['arata']['sentBranch']='广东深圳';
				$this->assignment['order']['company_address'] = '广东省深圳市光明新区';
			}elseif(in_array($this->order_info['facility_id'],array('185963129','185963130'))){
				$this->assignment['arata']['sentBranch']='广东广州';
				$this->assignment['order']['company_address'] = '广东省广州市';
			}elseif(in_array($this->order_info['facility_id'],array('185963135','185963136'))){
				$this->assignment['arata']['sentBranch']='江苏南京';
				$this->assignment['order']['company_address'] = '江苏省南京市江宁区';
			}
		}
	}

	private function getAssignmentsForYunda(){
		$sql = "SELECT a.* from ecshop.ecs_order_yunda_mailno_apply a 
		inner join romeo.order_shipment s on concat(s.shipment_id,0) = a.shipment_id 
		where s.order_id in ('{$this->order_id}')
		";
		$yunda_order = $this->db()->getRow($sql);
		// print_r($yunda_order);
		$this->assignment['yunda_order']=$yunda_order;

		if($this->order_info['facility_id'] =='24196974'){
		 //    $apply_mails=$yunda_order['pdf_info'];
			// $smarty->assign('pdf_infos',$apply_mails);
			// $tpl='waybill/yd-arata.htm';
			throw new Exception("贝亲青浦仓的韵达采用PDF格式打印，还是用iframe靠谱。", 1);
			
		}elseif($this->order_info['facility_type']=='SH'){
			$this->assignment['arata']['ydSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address'] = '上海市青浦区';
		}elseif($this->order_info['facility_type']=='OUTSOURCE' || $this->order_info['facility_type']=='FRUIT'){
			$this->assignment['arata']['ydSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			
			if(in_array($this->order_info['facility_id'],array('185963127','185963128'))){
				$this->assignment['order']['company_address'] = '北京市大兴区';
			}elseif(in_array($this->order_info['facility_id'],array('185963131','185963132'))){
				$this->assignment['order']['company_address'] = '浙江省嘉兴市';
			}elseif(in_array($this->order_info['facility_id'],array('185963133','185963134'))){
				$this->assignment['order']['company_address'] = '上海市青浦区';
			}elseif(in_array($this->order_info['facility_id'],array('185963137','185963138'))){
				$this->assignment['order']['company_address'] = '江苏省苏州市';
			}elseif(in_array($this->order_info['facility_id'],array('185963139','185963140'))){
				$this->assignment['order']['company_address'] = '四川省成都市';
			}elseif(in_array($this->order_info['facility_id'],array('185963141','185963142'))){
				$this->assignment['order']['company_address'] = '湖北省武汉市';
			}elseif(in_array($this->order_info['facility_id'],array('185963146','185963147'))){
				$this->assignment['order']['company_address'] = '广东省深圳市';
			}elseif(in_array($this->order_info['facility_id'],array('185963129','185963130'))){
				$this->assignment['order']['company_address'] = '广东省广州市';
			}elseif(in_array($this->order_info['facility_id'],array('185963135','185963136'))){
				$this->assignment['order']['company_address'] = '江苏省南京市';
			}
		}
	}

	private function getAssignmentsForYTO(){
		$sql = "SELECT attr_value from ecshop.order_attribute where attr_name='ytoBigPen' and order_id = '{$this->order_id}' and attr_value!='' limit 1";
		$ytoBigPen =  $this->db()->getOne($sql);
		if(!empty($ytoBigPen)) $this->assignment['order']['bigPen'] = $ytoBigPen;
		if($this->order_info['facility_type']=='WH'){
			$this->assignment['arata']['ytoSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='湖北武汉';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address'] = '湖北省武汉市东西湖区张柏路210号';
		}elseif($this->order_info['facility_type']=='DG'){
			$this->assignment['arata']['ytoSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='广东东莞';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address'] = '长安镇步步高大道126号';
		}elseif($this->order_info['facility_type']=='OUTSOURCE' || $this->order_info['facility_type']=='FRUIT'){
			$this->assignment['arata']['ytoSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			if(in_array($this->order_info['facility_id'],array('185963127','185963128'))){
				$this->assignment['arata']['sentBranch']='北京';
				$this->assignment['order']['company_address'] = '北京市大兴区小龙庄路63号';
			}elseif(in_array($this->order_info['facility_id'],array('185963131'))){
				$this->assignment['arata']['sentBranch']='浙江嘉兴';
				$this->assignment['order']['company_address'] = '';
			}elseif(in_array($this->order_info['facility_id'],array('185963132'))){
				$this->assignment['arata']['sentBranch']='浙江嘉兴';
				$this->assignment['order']['company_address'] = '浙江省嘉兴市南湖区';
			}elseif(in_array($this->order_info['facility_id'],array('185963133','185963134'))){
				$this->assignment['arata']['sentBranch']='上海青浦';
				$this->assignment['order']['company_address'] = '上海市青浦区青赵公路5009号';
			}elseif(in_array($this->order_info['facility_id'],array('185963137','185963138'))){
				$this->assignment['arata']['sentBranch']='江苏苏州';
				$this->assignment['order']['company_address'] = '江苏省苏州市高新区';
			}elseif(in_array($this->order_info['facility_id'],array('185963139','185963140'))){
				$this->assignment['arata']['sentBranch']='四川成都';
				$this->assignment['order']['company_address'] = '四川省成都市新都区';
			}elseif(in_array($this->order_info['facility_id'],array('185963141','185963142'))){
				$this->assignment['arata']['sentBranch']='湖北武汉';
				$this->assignment['order']['company_address'] = '湖北省武汉市东西湖区';
			}elseif(in_array($this->order_info['facility_id'],array('185963146','185963147'))){
				$this->assignment['arata']['sentBranch']='广东深圳';
				$this->assignment['order']['company_address'] = '广东省深圳市光明新区';
			}elseif(in_array($this->order_info['facility_id'],array('185963129','185963130'))){
				$this->assignment['arata']['sentBranch']='广东广州';
				$this->assignment['order']['company_address'] = '广东省广州市';
			}elseif(in_array($this->order_info['facility_id'],array('185963135','185963136'))){
				$this->assignment['arata']['sentBranch']='江苏南京';
				$this->assignment['order']['company_address'] = '江苏省南京市江宁区';
			}
		}elseif($this->order_info['facility_type']=='HQWHC'){
			$this->assignment['arata']['ytoSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='湖北武汉';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address'] = '';//湖北省武汉市东西湖区张柏路220号
		}
	}

	private function getAssignmentsForSTO(){
		if($this->order_info['facility_type']=='BJ'){
			$this->assignment['arata']['stoSender']='金百合在线';
			$this->assignment['arata']['sentBranch']='北京';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address'] = '北京市通州区张家湾镇西定福庄182号 01069578550';
		}elseif($this->order_info['facility_type']=='DG'){
			$this->assignment['arata']['stoSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='东莞';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address'] = '广东东莞';
		}
	}

	private function getAssignmentsForSF(){
		$this->assignment['order']['city_code']=$this->order_info['sf_city_code'];
		$this->assignment['order']['goods_type']=$this->order_info['goods_type'];
		$this->assignment['order']['order_sn']=$this->order_info['order_sn'];
		$this->assignment['order']['taobao_order_sn']=$this->order_info['taobao_order_sn'];
		if($this->order_info['carrier_id']=='44'){
			$this->assignment['arata']['service_type']='顺丰隔日';
		}else if($this->order_info['carrier_id']=='10'){
			$this->assignment['arata']['service_type']='顺丰次日';
		}else{
			$this->assignment['arata']['service_type']='顺丰特惠';
		}
		
		if (in_array($this->order_info['facility_id'], array('137059426','120801050','137059424','176053000'))) { //上海精品仓
			$this->assignment['arata']['sentBranch']='LEQEE';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['date']=date('Y-m-d');
			$this->assignment['arata']['time']=date('Y-m-d H:i');
			$this->assignment['arata']['sender']='LEQEE';
			$this->assignment['arata']['sf_account']='0219175065';
			$this->assignment['arata']['sf_payment_method']='寄付月结转第三方付';
			$this->assignment['arata']['sf_tp_areacode']='021LK';

			
			$this->assignment['order']['company_address']='上海市青浦区新丹路359号5栋4楼';//精品
			$this->assignment['order']['send_addr_code']='021'; //仓库地址码
			$this->assignment['order']['station_no']=$this->order_info['sf_city_code'];//收件地址码
			
			//保价
			$this->assignment['order']['insurance']=SFArataInsure::getInsuranceForOrder($this->order_id);//$order['order_amount'];//0;//0 is not in insurance
			$this->assignment['order']['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($this->assignment['order']['is_sf_cod']==false){
				$this->assignment['order']['is_sf_cod']=false;
				$this->assignment['order']['sf_cod_note']='￥0.00';
			}
		}elseif($this->order_info['facility_id']=='185963134'){//水果上海仓
			$this->assignment['arata']['sentBranch']='LEQEE';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['date']=date('Y-m-d');
			$this->assignment['arata']['time']=date('Y-m-d H:i');
			$this->assignment['arata']['sender']='LEQEE';
			$this->assignment['arata']['sf_account']='0219175065';
			$this->assignment['arata']['sf_payment_method']='寄付月结';
			$this->assignment['arata']['sf_tp_areacode']='';

			
			$this->assignment['order']['company_address']='上海市青浦区青赵公路5009号北面车间';//水果是  徐春建 13764432969
			$this->assignment['order']['send_addr_code']='021LK'; //仓库地址码
			$this->assignment['order']['station_no']=$this->order_info['sf_city_code'];//收件地址码
			
			//保价
			$this->assignment['order']['insurance']=SFArataInsure::getInsuranceForOrder($this->order_id);//$order['order_amount'];//0;//0 is not in insurance
			$this->assignment['order']['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($this->assignment['order']['is_sf_cod']==false){
				$this->assignment['order']['is_sf_cod']=false;
				$this->assignment['order']['sf_cod_note']='￥0.00';
			}
		}elseif (in_array($this->order_info['facility_id'], array('185963138'))) { //双11苏州水果仓-正常
			$this->assignment['arata']['sentBranch']='LEQEE';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['date']=date('Y-m-d');
			$this->assignment['arata']['time']=date('Y-m-d H:i');
			$this->assignment['arata']['sender']='LEQEE';
			$this->assignment['arata']['sf_account']='0219175065';
			$this->assignment['arata']['sf_payment_method']='寄付月结转第三方付';
			$this->assignment['arata']['sf_tp_areacode']='021LK';

			
			$this->assignment['order']['company_address']='江苏省苏州市高新区浒墅关工业园青花路128号安博物流园4号库';
			$this->assignment['order']['send_addr_code']='512'; //仓库地址码
			$this->assignment['order']['station_no']=$this->order_info['sf_city_code'];//收件地址码
			
			//保价
			$this->assignment['order']['insurance']=SFArataInsure::getInsuranceForOrder($this->order_id);//$order['order_amount'];//0;//0 is not in insurance
			$this->assignment['order']['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($this->assignment['order']['is_sf_cod']==false){
				$this->assignment['order']['is_sf_cod']=false;
				$this->assignment['order']['sf_cod_note']='￥0.00';
			}
		}elseif (in_array($this->order_info['facility_id'], array('79256821'))) { //电商服务北京仓
			if(in_array($this->order_info['party_id'], array(65621))){//百吉福
				$this->assignment['arata']['sentBranch']='LEQEE';
				$this->assignment['arata']['tracking_number']=$this->tracking_number;
				$this->assignment['arata']['date']=date('Y-m-d');
				$this->assignment['arata']['time']=date('Y-m-d H:i');
				$this->assignment['arata']['sender']='LEQEE';
				$this->assignment['arata']['sf_account']='7698041295';
				$this->assignment['arata']['sf_payment_method']='寄付月结转第三方付';
				$this->assignment['arata']['sf_tp_areacode']='769FF';

				
				$this->assignment['order']['company_address']='北京市通州市张家湾镇西定福庄村182号7号库';//北京的地址
				$this->assignment['order']['send_addr_code']='010GEE'; //仓库地址码
				$this->assignment['order']['station_no']=$this->order_info['sf_city_code'];//收件地址码
				
				//保价
				$this->assignment['order']['insurance']=SFArataInsure::getInsuranceForOrder($this->order_id);//$order['order_amount'];//0;//0 is not in insurance
				$this->assignment['order']['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

				//cod 应该不使用
				// $order['is_sf_cod']=true;
				// $order['sf_cod_note']='￥100.00';
				if($this->assignment['order']['is_sf_cod']==false){
					$this->assignment['order']['is_sf_cod']=false;
					$this->assignment['order']['sf_cod_note']='￥0.00';
				}
			}
		}
		elseif (in_array($this->order_info['facility_id'], array('185963128'))){
			//水果北京仓：月结账号——东莞仓   第三方地区： E769FF      仓库地址码：010BJJ 北京市大兴区后辛庄村立阳路临12号 李博学 13311158377
			$this->assignment['arata']['sentBranch']='LEQEE';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['date']=date('Y-m-d');
			$this->assignment['arata']['time']=date('Y-m-d H:i');
			$this->assignment['arata']['sender']='LEQEE';
			$this->assignment['arata']['sf_account']='7698041295';
			$this->assignment['arata']['sf_payment_method']='寄付月结转第三方付';
			$this->assignment['arata']['sf_tp_areacode']='E769FF';

			
			$this->assignment['order']['company_address']='北京市大兴区后辛庄村立阳路临12号';//北京的地址
			$this->assignment['order']['send_addr_code']='010BJJ'; //仓库地址码
			$this->assignment['order']['station_no']=$this->order_info['sf_city_code'];//收件地址码
			
			//保价
			$this->assignment['order']['insurance']=SFArataInsure::getInsuranceForOrder($this->order_id);//$order['order_amount'];//0;//0 is not in insurance
			$this->assignment['order']['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';

			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($this->assignment['order']['is_sf_cod']==false){
				$this->assignment['order']['is_sf_cod']=false;
				$this->assignment['order']['sf_cod_note']='￥0.00';
			}
		}
		elseif (in_array($this->order_info['facility_id'], array('19568549','194788297'))) { // 嘉善仓（电商服务嘉善仓，电商服务上海仓）
			$this->assignment['arata']['sentBranch']='LEQEE';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['date']=date('Y-m-d');
			$this->assignment['arata']['time']=date('Y-m-d H:i');
			$this->assignment['arata']['sender']='LEQEE';
			$this->assignment['arata']['sf_account']='7698041295';
			$this->assignment['arata']['sf_payment_method']='寄付月结转第三方付';
			$this->assignment['arata']['sf_tp_areacode']='E769FF';
			$this->assignment['order']['company_address']='浙江省嘉善县惠民街道松海路88号晋亿物流集团2号仓库';//精品
			$this->assignment['order']['send_addr_code']='573TH'; //仓库地址码
			$this->assignment['order']['station_no']=$this->order_info['sf_city_code'];//收件地址码
			
			//保价
			$this->assignment['order']['insurance']=SFArataInsure::getInsuranceForOrder($this->order_id);//$order['order_amount'];//0;//0 is not in insurance
			$this->assignment['order']['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			//不到转寄
			$this->assignment['order']['sf_note_js'] = "不到转寄";
			
			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($this->assignment['order']['is_sf_cod']==false){
				$this->assignment['order']['is_sf_cod']=false;
				$this->assignment['order']['sf_cod_note']='￥0.00';
			}
		}elseif (in_array($this->order_info['facility_id'], array('253372943'))) { // 水果深圳仓
			$this->assignment['arata']['sentBranch']='LEQEE';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['date']=date('Y-m-d');
			$this->assignment['arata']['time']=date('Y-m-d H:i');
			$this->assignment['arata']['sender']='LEQEE';
			$this->assignment['arata']['sf_account']='7698041295';
			$this->assignment['arata']['sf_payment_method']='寄付月结转第三方付';
			$this->assignment['arata']['sf_tp_areacode']='E769FF';

			
			$this->assignment['order']['company_address']='广东省深圳市光明新区塘家大道6号';
			$this->assignment['order']['send_addr_code']='755H'; //仓库地址码
			$this->assignment['order']['station_no']=$this->order_info['sf_city_code'];//收件地址码
			
			//保价
			$this->assignment['order']['insurance']=SFArataInsure::getInsuranceForOrder($this->order_id);//$order['order_amount'];//0;//0 is not in insurance
			$this->assignment['order']['insurance_payment']='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			
			//cod 应该不使用
			// $order['is_sf_cod']=true;
			// $order['sf_cod_note']='￥100.00';
			if($this->assignment['order']['is_sf_cod']==false){
				$this->assignment['order']['is_sf_cod']=false;
				$this->assignment['order']['sf_cod_note']='￥0.00';
			}
		}
		if('65690' == $this->order_info['party_id']){//La Prairie 莱珀妮  65690
			$this->assignment['arata']['sender']='';
			$this->assignment['order']['company_address']='上海市青浦区新丹路359号5栋5楼';
		}
	}

	private function getAssignmentsForSD(){
		if($this->order_info['facility_type']=='BJ'){
			$this->assignment['arata']['sentBranch']='北京通州';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			$this->assignment['order']['company_address']='北京市通州区张家湾镇西定福庄182号';
		}
	}

	private function getAssignmentsForJDPS(){
		$jd_distributor_ids = array('1950','2010');
		if(in_array($this->order_info['distributor_id'], $jd_distributor_ids)){
			$this->assignment['arata']['sentBranch']='上海';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			$this->assignment['arata']['print_date']=date('Y-m-d',time());
			$this->assignment['order']['company_address']='上海市青浦区';
		}
	}

	private function getAssignmentsForJDCOD(){
		$array = array('JDsendCode','siteId','siteName','sourcetSortCenterId','sourcetSortCenterName','originalCrossCode','originalTabletrolleyCode','targetSortCenterId',
'targetSortCenterName','destinationCrossCode','destinationTabletrolleyCode','aging','agingName');
		foreach($array as $attr_name){
			$sql = "select attr_value from ecshop.order_attribute where order_id = {$this->order_id} and attr_name= '{$attr_name}'";
			$this->assignment['arata'][$attr_name]= $this->db()->getOne($sql);
		}
		$jd_distributor_ids = array('2836');
		if(in_array($this->order_info['distributor_id'], $jd_distributor_ids) && $this->order_info['facility_id']=='185963138'){
			$this->assignment['arata']['sentBranch']='苏州';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			$this->assignment['arata']['print_date']=date('Y-m-d',time());
			$this->assignment['order']['company_address']='江苏苏州高新区浒墅关工业园';
		}
	}

	private function getAssignmentsForHT(){
		$sql = "SELECT attr_value from ecshop.order_attribute where attr_name='htBigPen' and order_id = '{$this->order_id}' and attr_value!='' limit 1";
		$htBigPen =  $this->db()->getOne($sql);
		if(!empty($htBigPen)) $this->assignment['order']['bigPen'] = $htBigPen;
		if($this->order_info['facility_type']=='SH'){
			$this->assignment['arata']['htSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='青浦二部';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address']='青浦二部已验视';
		}elseif($this->order_info['facility_type']=='BJ'){
			$this->assignment['arata']['htSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='北京通州';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address']='北京市通州区张家湾镇西定福庄182号';
		}elseif($this->order_info['facility_type']=='KBFX'){
			$this->assignment['arata']['htSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='上海奉贤';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address']='上海市奉贤区金钱公路888号';
		}elseif($this->order_info['facility_type']=='OUTSOURCE' || $this->order_info['facility_type']=='FRUIT'){
			$this->assignment['arata']['htSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];


			if(in_array($this->order_info['facility_id'],array('185963127','185963128'))){
				$this->assignment['arata']['sentBranch']='北京';
				$this->assignment['order']['company_address'] = '北京市大兴区小龙庄路63号';
			}elseif(in_array($this->order_info['facility_id'],array('185963131'))){
				$this->assignment['arata']['sentBranch']='浙江嘉兴';
				$this->assignment['order']['company_address'] = '';
			}elseif(in_array($this->order_info['facility_id'],array('185963132'))){
				$this->assignment['arata']['sentBranch']='浙江嘉兴';
				$this->assignment['order']['company_address'] = '浙江省嘉兴市南湖区';
			}elseif(in_array($this->order_info['facility_id'],array('185963133','185963134'))){
				$this->assignment['arata']['sentBranch']='上海青浦';
				$this->assignment['order']['company_address'] = '上海市青浦区青赵公路5009号';
			}elseif(in_array($this->order_info['facility_id'],array('185963137','185963138'))){
				$this->assignment['arata']['sentBranch']='江苏苏州';
				$this->assignment['order']['company_address'] = '江苏省苏州市高新区';
			}elseif(in_array($this->order_info['facility_id'],array('185963139','185963140'))){
				$this->assignment['arata']['sentBranch']='四川成都';
				$this->assignment['order']['company_address'] = '四川省成都市新都区';
			}elseif(in_array($this->order_info['facility_id'],array('185963141','185963142'))){
				$this->assignment['arata']['sentBranch']='湖北武汉';
				$this->assignment['order']['company_address'] = '湖北省武汉市东西湖区';
			}elseif(in_array($this->order_info['facility_id'],array('185963146','185963147'))){
				$this->assignment['arata']['sentBranch']='广东深圳';
				$this->assignment['order']['company_address'] = '广东省深圳市光明新区';
			}elseif(in_array($this->order_info['facility_id'],array('185963129','185963130'))){
				$this->assignment['arata']['sentBranch']='广东广州';
				$this->assignment['order']['company_address'] = '广东省广州市';
			}elseif(in_array($this->order_info['facility_id'],array('185963135','185963136'))){
				$this->assignment['arata']['sentBranch']='江苏南京';
				$this->assignment['order']['company_address'] = '江苏省南京市江宁区';
			}
		}elseif($this->order_info['facility_type']=='WLBJC'){//万霖北京仓
			$this->assignment['arata']['htSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='北京通州';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];

			$this->assignment['order']['company_address']='';//北京通州张家湾西定福庄182号
		}
	}
	private function getAssignmentsForWX(){
		if($this->order_info['facility_type']=='SH'){
			$this->assignment['arata']['htSender']='乐其-'.$this->order_info['party_name'];
			$this->assignment['arata']['sentBranch']='青浦二部';
			$this->assignment['arata']['tracking_number']=$this->tracking_number;
			$this->assignment['arata']['service_type']=$this->order_info['goods_type'];
			$this->assignment['order']['company_address']='青浦二部已验视';
		}
	}
}

/**
* 
*/
class LibSinriDivCarrierBillDBCache
{
	private $order_ids;
	private $oids;

	public $tracking_number_db;
	public $order_info_db;

	private function db(){
		global $db;
		return $db;
	}
	
	function __construct($order_ids=array())
	{
		$this->order_ids=$order_ids;
		$this->oids=implode(',',$this->order_ids);
		$this->queryTrackingNumberData();
		$this->queryOrderInfo();
	}

	function queryTrackingNumberData(){
		
		$sql="SELECT oi.order_id,s.tracking_number 
		FROM ecshop.ecs_order_info oi 
		INNER JOIN romeo.order_shipment os on convert(oi.order_id using utf8) = os.order_id
		INNER JOIN romeo.shipment s ON os.shipment_id=s.shipment_id
		WHERE s.status!='SHIPMENT_CANCELLED' and s.tracking_number is not null and s.tracking_number!=''
		and oi.order_id in ({$this->oids})
		group by oi.order_id
		";
		$rows=$this->db()->getAll($sql);
		$this->tracking_number_db=array();
		foreach ($rows as $line) {
			$this->tracking_number_db[$line['order_id']]=$line['tracking_number'];
		}
	}

	public function getTrackingNumber($order_id){
		$tn=$this->tracking_number_db[$order_id];
		return $tn;
	}

	function queryOrderInfo(){
		$sql="SELECT
			oi.order_id,
			oi.order_sn,
			oi.taobao_order_sn,
			es.default_carrier_id carrier_id,
			oi.shipping_id,
			oi.province,
			rp.region_name province_name,
			oi.city,
			rc.region_name city_name,
			oi.district,
			rd.region_name district_name,
			oi.address,
			oi.distributor_id,
			oi.tel,
			oi.mobile,
			oi.consignee,
			oi.facility_id,
			oi.party_id,
			p.`NAME` party_name,
			oi.order_amount,
			oi.bonus,
			ccm.city_code sf_city_code
		FROM
			ecshop.ecs_order_info oi
		LEFT JOIN ecshop.ecs_shipping es ON oi.shipping_id = es.shipping_id
		LEFT JOIN ecshop.ecs_region rp ON oi.province = rp.region_id
		LEFT JOIN ecshop.ecs_region rc ON oi.city = rc.region_id
		LEFT JOIN ecshop.ecs_region rd ON oi.district = rd.region_id
		LEFT JOIN romeo.party p ON CONVERT (oi.party_id USING utf8) = p.PARTY_ID
		LEFT JOIN ecshop.ecs_city_code_mapping ccm ON ccm.city_id = oi.city -- 顺丰快递查出城市编码
		WHERE
			oi.order_id in ({$this->oids}) 
		group by oi.order_id
		";
		$rows = $this->db()->getAll($sql);
		$this->order_info_db = array();

		foreach ($rows as $key => $value) {
			$this->order_info_db[$value['order_id']]=$value;
		}
	}

	public function getOrderInfo($order_id){
		$order_info=$this->order_info_db[$order_id];
		return $order_info;
	}
}