<?php
/*
* 可优化：
* 1. 缓存
*/
require_once ('cls_sales_order.php');
require_once('../../includes/helper/array.php');
require_once('cls_sales_order_tools.php');

class ClsSalesOrderDetail extends ClsSalesOrderBasic{
	static protected function GetAllowedActionList(){
		return array('query');
	}
	function  __construct($data_map){
    	parent::__construct($data_map);
		$this->order_id_ = $data_map['order_id'] ? $data_map['order_id'] : 0;
	}

	static function get_erp_best_shipping_info($order_id) {
		global $db;
		$sql =" 
		    select oi.order_id,
			group_concat(distinct pfn.facility_name) as pf_facility_name,group_concat(distinct convert(pf.facility_id using utf8)) as pf_facility_id,
			group_concat(distinct rfn.facility_name) as rf_facility_name,group_concat(distinct convert(rf.facility_id using utf8)) as rf_facility_id,
			group_concat(distinct psn.shipping_name) as ps_shipping_name,group_concat(distinct convert(ps.shipping_id using utf8)) as ps_shipping_id,
			group_concat(distinct rsn.shipping_name) as rs_shipping_name,group_concat(distinct convert(rs.shipping_id using utf8)) as rs_shipping_id,
			group_concat(distinct dfn.facility_name) as df_facility_name,group_concat(distinct convert(df.facility_id using utf8)) as df_facility_id,
			group_concat(distinct drfn.facility_name) as drf_facility_name,group_concat(distinct convert(drf.facility_id using utf8)) as drf_facility_id,
			group_concat(distinct dsn.shipping_name) as ds_shipping_name,group_concat(distinct convert(ds.shipping_id using utf8)) as ds_shipping_id,
			group_concat(distinct drsn.shipping_name) as drs_shipping_name,group_concat(distinct convert(drs.shipping_id using utf8)) as drs_shipping_id,
			p.name as party_name,d.name as distributor_name,cf.facility_name as party_facility_name,cf.facility_id as party_facility_id,cs.shipping_name as party_shipping_name,cs.shipping_id as party_shipping_id,
			dcf.facility_name as distributor_facility_name,dcf.facility_id as distributor_facility_id,dcs.shipping_name as distributor_shipping_name,dcs.shipping_id as distributor_shipping_id
			from ecshop.ecs_order_info oi
			left join ecshop.ecs_party_assign_facility pf ON oi.party_id = pf.party_id
			left join romeo.facility pfn ON pf.facility_id = pfn.facility_id
			left join ecshop.ecs_party_region_assign_facility rf ON oi.party_id = rf.party_id and oi.province = rf.region_id
			left join romeo.facility rfn ON rfn.facility_id = rf.facility_id
			left join ecshop.ecs_party_assign_shipping ps ON oi.party_id = ps.party_id
			left join ecshop.ecs_shipping psn ON ps.shipping_id = psn.shipping_id
			left join ecshop.ecs_party_region_assign_shipping rs ON oi.party_id = rs.party_id and oi.province = rs.region_id
			left join ecshop.ecs_shipping rsn ON rs.shipping_id = rsn.shipping_id
			left join ecshop.ecs_distributor_assign_facility df ON oi.distributor_id = df.distributor_id
			left join romeo.facility dfn ON dfn.facility_id = df.facility_id
			left join ecshop.ecs_distributor_region_assign_facility drf ON oi.distributor_id = drf.distributor_id and oi.province = drf.region_id
			left join romeo.facility drfn ON drfn.facility_id = drf.facility_id
			left join ecshop.ecs_distributor_assign_shipping ds ON oi.distributor_id = ds.distributor_id
			left join ecshop.ecs_shipping dsn ON ds.shipping_id = dsn.shipping_id
			left join ecshop.ecs_distributor_region_assign_shipping drs ON oi.distributor_id = drs.distributor_id and oi.province = drs.region_id
			left join ecshop.ecs_shipping drsn ON drs.shipping_id = drsn.shipping_id
			left join romeo.party p ON oi.party_id = p.party_id
			left join ecshop.distributor d ON oi.distributor_id = d.distributor_id
			left join ecshop.taobao_shop_conf c ON oi.party_id = c.party_id
			left join romeo.facility cf ON c.facility_id = cf.facility_id
			left join ecshop.ecs_shipping cs ON c.shipping_id = cs.shipping_id
			left join ecshop.taobao_shop_conf dc ON oi.party_id = dc.party_id and oi.distributor_id = dc.distributor_id
			left join romeo.facility dcf ON dc.facility_id = dcf.facility_id
			left join ecshop.ecs_shipping dcs ON dc.shipping_id = dcs.shipping_id
			where oi.order_id='{$order_id}' group by oi.order_id";
		$erp_best_shipping_info = $db->getRow($sql);
		return $erp_best_shipping_info;
	}


	function QueryData(){
		global $db, $_CFG;
		$sql = "select *,from_unixtime(reserved_time) as reserved_time_
				from ecshop.ecs_order_info eoi
				where eoi.order_id = '{$this->order_id_}'";
		$order_result = $db->getRow($sql);
		if($order_result['reserved_time_'] == '1970-01-01 08:00:00') {

			$sql = "SELECT reserved_time from romeo.order_inv_reserved WHERE order_id = '{$this->order_id_}' ORDER BY reserved_time desc limit 1 ";
		    $hehe_reserved_time = $db->getRow($sql);
		    if(empty($hehe_reserved_time)){
		    	$this->reserved_time_ = '未预定';
		    }else {
		    	$this->reserved_time_ = '预定失败 时间:'.$hehe_reserved_time['reserved_time'];
		    }
		} else {
	        $this->reserved_time_ = '预定时间：'.$order_result['reserved_time_'];
		}
	
		//抵用券红包信息
		require_once ('cls_sales_order_pay_info.php');
		$this->fee_list_ = new ClsSalesOrderPayInfo(array('order_id'=>$this->order_id_, 'content_type'=>'pay_info'));
		$this->fee_list_->QueryData();
		
		//操作记录
		require_once ('cls_sales_order_action_records.php');
		$this->action_list_ = new ClsSalesOrderActionRecords(array('order_id'=>$this->order_id_, 'content_type'=>'action_records'));
		$this->action_list_->QueryData();
		$this->action_list_ = $this->action_list_->action_list_;
		
		//最优快递信息
		require_once ('cls_sales_order_logistic.php');
		$this->best_express_list_ = new ClsSalesOrderLogisticInfo(array('order_id'=>$this->order_id_, 'content_type'=>'logistic_info'));
		$this->best_express_list_->QueryData();
		
		//收货人信息
		require_once ('cls_sales_order_consignee.php');
		$this->consigne_info_ = new ClsSalesOrderConsignee(array('order_id'=>$this->order_id_, 'content_type'=>'consignee'));
		$this->consigne_info_->SetClsDataFromOrderInfo($order_result);

		// 支付方式
		require_once ('cls_sales_order_payment.php');
		$this->pay_info_ = new ClsSalesOrderPayment(array('order_id'=>$this->order_id_, 'content_type'=>'payment'));
		$this->pay_info_->QueryData();
		$this->payment_group_data_ = ClsSalesOrderTools::GetGroupedPaymentList();
		$this->kjg_payment_ = $_CFG['adminvars']['kjg_payment'];

		// 快递方式
		require_once ('cls_sales_order_express.php');
		$this->shipping_info_ = new ClsSalesOrderExpress(array('order_id'=>$this->order_id_, 'content_type'=>'express'));
		$this->shipping_info_->SetClsDataFromOrderInfo($order_result);
		$this->shipping_group_data_ = ClsSalesOrderTools::GetGroupedShippingList();

		// 仓库
		require_once ('cls_sales_order_facility.php');
		$this->facility_info_ = new ClsSalesOrderFacility(array('order_id'=>$this->order_id_, 'content_type'=>'facility'));
		$this->facility_info_->SetClsDataFromOrderInfo($order_result);

		// 最优仓库 快递 
		$this->best_facility_shipping_info_ = ClsSalesOrderDetail::get_erp_best_shipping_info($this->order_id_); 

		// 可达性			
		$sql = "select ar.arrived
			from ecshop.ecs_order_info oi
			inner join ecshop.ecs_shipping_area sa ON oi.shipping_id = sa.shipping_id
			inner join ecshop.ecs_area_region ar ON sa.shipping_area_id = ar.shipping_area_id
			where (oi.province = ar.region_id or oi.city = ar.region_id or oi.district = ar.region_id)
			and oi.order_id = '{$this->order_id_}'
			order by ar.region_id desc 
			limit 1";
		$arrived = $db->getOne($sql);
		$this->arrived_type_ = ($arrived == 'ALL') ? '全境可达' : 
									($arrived == 'PARTLY' ? '部分可达' : '不可达');

		// 发票信息
		$this->inv_info_ = new ClsInvoiceInfoOfOrderDetail;
		$sql = "select * from order_vat_invoice where order_id = '{$order_result['order_id']}' ";
		$vat_inv_result = $db->getRow($sql);
		if(isset($vat_inv_result) && !empty($vat_inv_result)){
			//增殖税发票
			$this->inv_info_->invoice_type_ = '增殖税发票';
			$this->inv_info_->confirmed_type_ = (isset($vat_inv_result['status']) && $vat_inv_result['status'] == 'confirmed');
			$this->inv_info_->invoice_attr_list_['单位名称'] = $vat_inv_result['company'];
			$this->inv_info_->invoice_attr_list_['纳税人识别号'] = $vat_inv_result['identify_no'];
			$this->inv_info_->invoice_attr_list_['注册地址'] = $vat_inv_result['address'];
			$this->inv_info_->invoice_attr_list_['注册电话'] = $vat_inv_result['telphone'];
			$this->inv_info_->invoice_attr_list_['银行账户'] = $vat_inv_result['account'];
			$this->inv_info_->invoice_attr_list_['开户银行'] = $vat_inv_result['bank'];
		}else{
			if(!isset($order_result['need_invoice']) || $order_result['need_invoice'] == 'N'){
				//不需要发票
				$this->inv_info_->invoice_type_ = '不需要发票';
			}else if($order_result['need_invoice'] == 'Y'){
				//普通发票
				$this->inv_info_->invoice_type_ = '普通发票';
				$this->inv_info_->invoice_no_ = $order_result['invoice_no'];
//				$this->inv_info_->invoice_attr_list_['发票电话'] = $order_result['inv_phone'];
//				$this->inv_info_->invoice_attr_list_['发票邮编'] = $order_result['inv_zipcode'];
//				$this->inv_info_->invoice_attr_list_['发票地址'] = $order_result['inv_address'];
//				$this->inv_info_->invoice_attr_list_['发票内容'] = $order_result['inv_content'];
				$this->inv_info_->invoice_attr_list_['发票抬头'] = $order_result['inv_payee'];
			}else{
				//error
				$this->inv_info_->invoice_type_ = '发票类型错误，请联系ERP！';
			}
		}

		require_once ('cls_sales_order_goods_list.php');
		$order_goods_list = new ClsOrderGoodsList(array('order_id'=>$this->order_id_, 'action_type'=>'query', 'content_type'=>'goods_list'));
		$order_goods_list->SetClsDataFromOrderInfo($order_result);
		$this->goods_list_ = $order_goods_list;
	}

	//
	var $order_id_;
	// 收货人信息
	var	$consigne_info_ = null;

	// 支付和配送方式
	var $payment_and_express_info_ = null;
	var $payment_group_data_ = array();
	var $shipping_group_data_ = array();
	var $arrived_type_;
    var $best_facility_shipping_info_ = array( ); 
	// 发票信息
	var $inv_info_ = null;

	// 商品清单
	var $goods_list_;
}

class ClsInvoiceInfoOfOrderDetail{
	//
	var $invoice_type_; // 'NONE':不需要发票 'COMMON':普通发票 'VAT':增殖税发票 'ERR':错误
	var $confirmed_type_; 
	var $invoice_no_;
	var $invoice_attr_list_ = array();
}
?>