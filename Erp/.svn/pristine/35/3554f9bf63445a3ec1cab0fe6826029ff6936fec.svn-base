<?php

interface excludeParty{
	function menuList();
}

class third_party_warehouse implements excludeParty{
	
	function menuList(){
		$facility_menu_list = array(
		'03_purchase_manage' => //采购管理
		   array(
		   		'05_supplier_return_request',//供应商退货仓库操作
		   ),
		'14_wms' =>   //仓库管理
			array(
				'01_menu_in_storage',
				'02_menu_out_storage',
				'03_menu_in_out_storage',
				'04_menu_facility_search',
				'05_menu_facility_manage',
				'06_after_sale',
			),
		'02_order_manage' => //售后
			array(
				'02_after_sale',	
			),
		);
		return $facility_menu_list;
	}
}
class zhongliang_ERP_system implements excludeParty{
	
	function menuList(){
		$facility_menu_list = array(
			'16_zhongliang' => //中粮项目
				array(
				'01_customer_service',
				'02_order_sync',
				'03_inventory_sync',
				'04_zhongliang_b2b_out',//B2B出库
				'05_zhongliang_b2b_out_list', //B2B出库详情
				'06_generate_c_order',//下采购订单
				'07_zhongliang_Batch_order',//中粮批量采购
				'08_zhongliang_inventory_apply',//中粮盘点申请
				'09_zhongliang_inventory_apply_out', //中粮盘点调整
				'10_zhongliang_inventory_item',//出入库记录
				'11_zhongliang_inventory_query', //库存查询
				),
			'04_goods_manage' => //商品管理
			    array('01_goods_list', //商品添加/编辑
			    ),
			'15_analyze' => //统计信息
				array('01_report',   //常用报表
				),
		);
		return $facility_menu_list;
	}
}

class ecco_ERP_system implements excludeParty{
	
	function menuList(){
		$facility_menu_list = array(
			'18_ecco' => //ecco项目
				array(
				'01_print_goods_identify' //打印商品标识
				)
		);
		return $facility_menu_list;
	}
}
?>
