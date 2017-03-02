<?php

/**
 * 菜单数组
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

/* 双十一预案 */
if(check_admin_priv('permission_for_1111'))
{
	$modules['11_for1111']['01_batch_change_delivery'] = 'batch_change_delivery.php';
	$modules['11_for1111']['01_order_tracking'] = 'order_tracking_for_11111.php';
	$modules['11_for1111']['01_all_order_tracking'] = 'all_order_tracking_for_11111.php';
}

if(check_admin_priv('ERP_DEV')){
	//重写界面管理
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_purchase']['00_REWITE_workflow_purchase_generate'] = 'order/purchase_order/generate_purchase_order.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_purchase']['00_REWITE_workflow_purchase_verify'] = 'order/purchase_order/purchase_order_blank.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_purchase']['00_REWITE_workflow_purchase_order_list'] = 'order/purchase_order/purchase_order_blank.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_purchase']['00_REWITE_workflow_purchase_checked_order_list'] = 'order/purchase_order/checked_purchase_order_list.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_sales']['00_REWITE_workflow_sales_generate'] = 'order/sales_order/generate_sales_order.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_sales']['00_REWITE_workflow_sales_verify'] = 'order/sales_order/sales_order_blank.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_sales']['00_REWITE_workflow_sales_order_list'] = 'order/sales_order/sales_order_blank.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_sales']['00_REWITE_workflow_sales_shipment_list'] = 'order/sales_order/sales_order_blank.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_warehouse_in_stock']['00_REWITE_workflow_warehouse_in_stock_order_list'] = 'order/in_stock/in_stock_order_list.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_warehouse_in_stock']['00_REWITE_workflow_warehouse_in_stock_recv_rf'] = 'order/in_stock/receive_rf_scan.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_warehouse_in_stock']['00_REWITE_workflow_warehouse_in_stock_ground_rf'] = 'order/in_stock/grouding_rf_scan.php';
	$modules['00_REWITE_WORKFLOW']['00_REWITE_workflow_warehouse_out_stock']['00_REWITE_workflow_warehouse_out_stock_order_list'] = 'order/out_stock/out_stock_blank.php';
	
	unset($modules['00_REWITE_WORKFLOW']);
	//废老库存测试监控界面
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_purchase']['00_MINUS_OUKOO_ERP_purchase_action'] = 'SinriTest/purchase_order_info_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_purchase']['00_MINUS_OUKOO_ERP_purchase_new_stock_in'] = 'SinriTest/purchase_order_new_stock_in_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_purchase']['00_MINUS_OUKOO_ERP_purchase_old_stock_in'] = 'SinriTest/purchase_order_old_stock_in_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_sales']['00_MINUS_OUKOO_ERP_shipment_pick_zhixiao'] = 'SinriTest/shipment_pick_zhixiao_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_sales']['00_MINUS_OUKOO_ERP_shipment_pick_fenxiao'] = 'SinriTest/shipment_pick_fenxiao_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_sales']['00_MINUS_OUKOO_ERP_distribution_order'] = 'SinriTest/distribution_order_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_sales']['00_MINUS_OUKOO_ERP_one_key_batch_pick'] = 'SinriTest/one_key_batch_pick_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_sales']['00_MINUS_OUKOO_ERP_recheck'] = 'SinriTest/sale_recheck_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_back_change']['00_MINUS_OUKOO_ERP_back_change_service'] = 'SinriTest/back_change_service_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_back_change']['00_MINUS_OUKOO_ERP_back_change_goods_in'] = 'SinriTest/back_change_goods_in_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_back_change']['00_MINUS_OUKOO_ERP_back_change_complete'] = 'SinriTest/back_change_complete_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_back_change']['00_MINUS_OUKOO_ERP_shipped_cancel'] = 'SinriTest/shipped_cancel_monitor.php';
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_back_change']['00_MINUS_OUKOO_ERP_auto_service'] = 'SinriTest/auto_service_monitor.php';	
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_supplier_return'] = 'SinriTest/supplier_return_monitor.php'; // -gt
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_h_borrow_return'] = 'SinriTest/h_borrow_return_monitor.php'; // 借还机
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_supplier_bill'] = 'SinriTest/supplier_bill_monitor.php'; // 内部结账
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_physical_inventory'] = 'SinriTest/inventory_virance_monitor.php'; //盘点
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_supplier_return_request_list'] = 'SinriTest/supplier_return_request_list_monitor.php'; //盘点
	$modules['00_MINUS_OUKOO_ERP']['00_MINUS_OUKOO_ERP_sale_order_refund'] = 'SinriTest/refund_monitor.php'; //盘点
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_ADD_NOTE'] = 'SinriTest/sales_order_edit/order_action_add_note_monitor.php'; //添加备注
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_ORDER_CONFIRM'] = 'SinriTest/sales_order_edit/order_action_order_confirm_monitor.php'; //订单确认
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_ORDER_CANCEL'] = 'SinriTest/sales_order_edit/order_action_order_cancel_monitor.php'; //订单取消
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_REC_CONFIRM'] = 'SinriTest/sales_order_edit/order_action_rec_confirm_monitor.php'; //收货确认
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_REC_REJECT'] = 'SinriTest/sales_order_edit/order_action_rec_reject_monitor.php'; //收货确认
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_ORDER_RECOVER'] = 'SinriTest/sales_order_edit/order_action_order_recover_monitor.php'; //订单恢复
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_MERGE_ORDER_EDIT'] = 'SinriTest/sales_order_edit/order_action_order_merge_monitor.php'; //合并拆分订单
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_CONSIGNEE_EDIT'] = 'SinriTest/sales_order_edit/order_action_consignee_edit_monitor.php'; //修改支付方式
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_PAYMENT_EDIT'] = 'SinriTest/sales_order_edit/order_action_payment_edit_monitor.php'; //修改支付方式
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_FACILITY_EDIT'] = 'SinriTest/sales_order_edit/order_action_facility_edit_monitor.php'; //转仓
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_SHIPPING_EDIT'] = 'SinriTest/sales_order_edit/order_action_shipping_edit_monitor.php'; //修改快递方式
	$modules['00_MINUS_OUKOO_ERP']['00_NEW_ORDER_DETAIL']['00_NEW_ORDER_DETAIL_GOODS_EDIT'] = 'SinriTest/sales_order_edit/order_action_order_goods_list_monitor.php'; //修改订单商品
	
	unset($modules['00_MINUS_OUKOO_ERP']);
	
	//测试界面管理
	$modules['01_ERPDEV']['01_ERP_DEV_01_temp']['01_in_storage_order_display'] = 'display_instorage_order_listV5.php';	//待入库订单列表
	$modules['01_ERPDEV']['01_ERP_DEV_01_temp']['10_common_grouding_rf_scan'] = 'common_grouding_rf_scan.php'; // 通用上架RF枪扫描(-t,-h,-gh)
	$modules['01_ERPDEV']['01_ERP_DEV_01_temp']['99_common_undercarriage_rf_scan'] = 'common_undercarriage_rf_scan.php'; // 通用下架RF枪扫描(-gt,-gh)
	$modules['01_ERPDEV']['01_ERP_DEV_01_temp']['99_batch_pick_rf_scan_smarty'] = 'batch_pick_rf_scan_n1.php'; // 批拣rf枪扫描
	$modules['01_ERPDEV']['01_ERP_DEV_01_temp']['99_rf_sickness'] = 'RF_sick_shipment.php';//病单RF补拣
    $modules['01_ERPDEV']['01_ERP_DEV_01_temp']['99_deal_sickness'] = 'query_sick_shipment.php';//病单查询和处理
	//暗链接管理
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['01_ERP_DEV_02_secret_01_toolkit'] = 'toolkit.php';	//超级工具
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['01_ERP_DEV_02_secret_02_facility'] = 'auto_deliver_party_facility.php';	//天猫超市等自动出库设置
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['01_ERP_DEV_02_secret_03_order_check'] = 'order_check.php';	//订单监控页
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['01_ERP_DEV_02_secret_04_bpsn_status_check'] = 'SinriTest/sinri_wms_batch_pick_debug.php?bpsn=';	//批拣单状态监控
	// $modules['01_ERPDEV']['01_ERP_DEV_02_secret']['01_ERP_DEV_02_secret_05_v_inventory_result_search'] = 'search_v_inventory_result.php';
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['01_ERP_DEV_02_secret_06_storage_toolkit'] = 'storage_toolkit.php';//库存管理工具
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['02_stock_take_adjust'] = 'virance_inventory/physical_inventory_out_inventory.php';  //盘点库存调整
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['02_order_batch']       = 'order_batch_validate.php?act=list'; //批量确认订单
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['02_order_batch_double11']       = 'order_batch_validate_double11.php'; //【双十一专用】批量确认订单
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['02_finance_adjustmant']       = 'finance_adjustment_import.php'; //财务调账
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['02_prepayment_ajustment'] = 'prepayment_ajustment.php';      //预存款调整
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['02_erp_command']       = 'erp_command.php'; //erp调度管理
	$modules['01_ERPDEV']['01_ERP_DEV_02_secret']['02_cronjob_viewer'] = 'SinriTest/cronjob_viewer.php';
	//测试数据监控
	$modules['01_ERPDEV']['00_TEST_DATA_MONITOR']['00_OR_MONITOR'] = 'SinriTest/OR_integration_monitor.php'; //OR监控界面
	$modules['01_ERPDEV']['00_TEST_DATA_MONITOR']['00_OR_MONITOR_HEADER'] = 'SinriTest/OR_integration_header_list_monitor.php'; //OR监控界面
	$modules['01_ERPDEV']['00_TEST_DATA_MONITOR']['00_OR_MONITOR_ORDERS'] = 'SinriTest/OR_integration_curr_order_monitor.php'; //OR监控界面
	$modules['01_ERPDEV']['00_TEST_DATA_MONITOR']['01_BABYNES_MONITOR_3PL'] = 'SinriTest/Babynes_integration_monitor.php'; //OR监控界面
	$modules['01_ERPDEV']['00_TEST_DATA_MONITOR']['02_BEST_INDICATE'] = 'SinriTest/best_indicate_monitor.php'; //best监控界面
	$modules['01_ERPDEV']['00_TEST_DATA_MONITOR']['03_GYMBOREE_MONITOR'] = 'SinriTest/gymboree_order_monitor.php'; //gymboree监控界面
	// 平台对接数据监控
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['01_BIRD_MONITOR'] = 'SinriTest/bird_indicate_monitor.php'; //菜鸟监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['02_TMALL_MONITOR'] = 'SinriTest/tmall_order_monitor.php'; //天猫监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['03_JD_MONITOR'] = 'SinriTest/jd_order_monitor.php'; //京东监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['04_YHD_MONITOR'] = 'SinriTest/yhd_order_monitor.php'; //一号店监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['05_JM_MONITOR'] = 'SinriTest/jm_order_monitor.php'; //聚美优品监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['06_VIPSHOP_MONITOR'] = 'SinriTest/vipshop_order_monitor.php'; //唯品会监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['07_SUNING_MONITOR'] = 'SinriTest/suning_order_monitor.php'; //苏宁易购监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['08_MIYA_MONITOR'] = 'SinriTest/miya_order_monitor.php'; //蜜芽宝贝监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['09_BAIDUMALL_MONITOR'] = 'SinriTest/baidumall_order_monitor.php'; //蜜芽宝贝监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['10_SFHK_MONITOR'] = 'SinriTest/sfhk_order_monitor.php'; //顺丰优选监控界面
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['12_COMBI_CRM_MONITOR'] = 'SinriTest/combi_crm_monitor.php'; //康贝订单监控
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['13_BUDWEISER_MONITOR'] = 'SinriTest/budweiser_gift_monitor.php'; //百威订单监控	
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['14_PINDUODUO_MONITOR'] = 'SinriTest/pinduoduo_order_monitor.php'; //拼多多订单监控	
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['15_KUAJINGGOU_MONITOR'] = 'SinriTest/kuajinggou_order_monitor.php'; //跨境购订单监控	
	$modules['01_ERPDEV']['02_PLATFORM_ORDER_MONITOR']['16_HAIGUAN_MONITOR'] = 'SinriTest/haiguan_order_monitor.php';
	
	// 微信商城数据监控
	$modules['01_ERPDEV']['04_WECHAT_DATE_MONITOR']['01_WYETH_MONITOR'] = 'SinriTest/wyeth_wechatshop_monitor.php'; //惠氏-齐数微商城监控界面
	$modules['01_ERPDEV']['04_WECHAT_DATE_MONITOR']['02_KDT_MONITOR'] = 'SinriTest/kdt_wechatshop_monitor.php'; //有赞微商城监控界面
	$modules['01_ERPDEV']['04_WECHAT_DATE_MONITOR']['03_LEQEE_MONITOR'] = 'SinriTest/leqee_wechatshop_monitor.php'; //乐其微商城监控界面	
	$modules['01_ERPDEV']['04_WECHAT_DATE_MONITOR']['04_RTM_MONITOR'] = 'SinriTest/rtm_wechatshop_monitor.php'; //人头马-蓝门微商城监控界面
	$modules['01_ERPDEV']['04_WECHAT_DATE_MONITOR']['05_NKB_MONITOR'] = 'SinriTest/nkb_wechatshop_monitor.php'; //尿裤宝微商城监控界面
	$modules['01_ERPDEV']['04_WECHAT_DATE_MONITOR']['06_HQ_MONITOR'] = 'SinriTest/hq_wechatshop_monitor.php'; //好奇微商城监控界面


	//保税仓监控
	$modules['01_ERPDEV']['03_ERP_BWSHOP_NODE']['03_ERP_BWSHOP_SHOP_AGENT'] = 'bwshop/bwshop_agent.php';
	$modules['01_ERPDEV']['03_ERP_BWSHOP_NODE']['03_ERP_BWSHOP_ORDER_AGENT'] = 'bwshop/bw_order_monitor.php';
	$modules['01_ERPDEV']['03_ERP_BWSHOP_NODE']['03_ERP_BWSHOP_SIGHT_ORDERS'] = 'bwshop/bw_order_sight.php';
	$modules['01_ERPDEV']['03_ERP_BWSHOP_NODE']['03_ERP_BWSHOP_IKENIE_AGENT'] = 'bwshop/bw_mibun_pool.php';
	$modules['01_ERPDEV']['03_ERP_BWSHOP_NODE']['03_ERP_BWSHOP_ZEIRITSU'] = 'bwshop/bw_goods_tax.php';
	$modules['01_ERPDEV']['03_ERP_BWSHOP_NODE']['03_ERP_BWSHOP_ISSUE_ORDERS'] = 'bwshop/bw_issue_orders.php';
}

/*订单管理*/
//客服管理
$modules['02_order_manage']['01_customer_service']['01_order_list'] = 'order_shipment.php'; // 合并订单
$modules['02_order_manage']['01_customer_service']['02_customer_service_manage_order_paid_unconfirmed'] = 'csmo.php?type=paid_unconfirmed'; // 先款后货未确认订单
$modules['02_order_manage']['01_customer_service']['03_customer_service_manage_order_cod_unconfirmed']	 = 'csmo.php?type=cod_unconfirmed'; // 货到付款未确认订单
$modules['02_order_manage']['01_customer_service']['04_order_entry']   = 'distribution_order.php';//销售订单录入
$modules['02_order_manage']['01_customer_service']['05_batch_dis_order']   = 'batchDisOrderV2.php'; // 批量录单
$modules['02_order_manage']['01_customer_service']['06_invoice_add']   = 'invoice_manage/invoice_add.php';  //添加补寄发票
$modules['02_order_manage']['01_customer_service']['07_order_check']   = 'csmo.php?type=hand_order_search'; // 录单订单查询...待开发
$modules['02_order_manage']['01_customer_service']['08_search_user_order_info']   = 'search_history_order.php'; // 历史订单查询
$modules['02_order_manage']['01_customer_service']['09_taobao_sales_add'] = 'taobao/taobao_sales_add.php';   //添加淘宝旺旺客服
$modules['02_order_manage']['01_customer_service']['10_taobao_consult_import'] = 'taobao/taobao_consult_import.php';  // 咨询内容导入
$modules['02_order_manage']['01_customer_service']['11_order_list_new_order'] = 'order_shipment_new_order.php';  // 中粮-合并订单 创建新订单的方式 合并订单  
$modules['02_order_manage']['01_customer_service']['13_huawang_data_import'] = 'huawang_data_import.php';	//花王数据导入

//订单售后管理
$modules['02_order_manage']['02_after_sale']['01_change_service']	= 'sale_serviceV3.php?service_type=1&service_status=0&back_shipping_status=0';//换货申请
$modules['02_order_manage']['02_after_sale']['02_back_service'] = 'sale_serviceV3.php?service_type=2&service_status=0&back_shipping_status=0';//退货申请
$modules['02_order_manage']['02_after_sale']['06_customer_service_refund_list']   = 'refund_list_new.php?view=1&auto_refresh=1'; //退款申请列表
$modules['02_order_manage']['02_after_sale']['04_sale_support_center_cached'] = 'sale_support/sale_support_center_cached.php';//
$modules['02_order_manage']['02_after_sale']['05_sale_support_status'] = 'sale_support/sale_support_status.php'; //实时售后任务统计
$modules['02_order_manage']['02_after_sale']['07_heinz_shipping_code_input'] = 'heinz_return_coin_shipping_code_input.php'; //亨氏退换货物流码输入
//外包订单管理
$modules['02_order_manage']['03_out_shipment']['01_out_ship_goods'] = 'taobao/taobao_out_ship_goods_configure.php';   //淘宝外包发货商品设置
$modules['02_order_manage']['03_out_shipment']['02_out_ship_order'] = 'out_ship_order.php';  // 订单打标
$modules['02_order_manage']['03_out_shipment']['03_out_ship_pull_tn'] = 'out_pull_tracking_number.php'; //面单导入
$modules['02_order_manage']['03_out_shipment']['03_pinduoduo_ship_pull_tn'] = 'pinduoduo_tracking_number.php'; //拼多多面單導入
$modules['02_order_manage']['03_out_shipment']['03_wxgrd_ship_pull_tn'] = 'wxgrd_tracking_number.php'; //万象物流隔日达运单导入
$modules['02_order_manage']['03_out_shipment']['04_out_shipment_print'] = 'search_out_batch_pick.php';  //打印面单
$modules['02_order_manage']['03_out_shipment']['05_out_shipment_recheck'] = 'out_shipment_recheck.php';  //复核 
$modules['02_order_manage']['03_out_shipment']['06_out_receive_rf_scan'] = 'out_receive_rf_scan.php'; // 外包收货RF枪扫描
$modules['02_order_manage']['03_out_shipment']['07_out_batch_or_in_storage'] = 'out_batch_or_in_storage.php'; // 批次号批量入库
$modules['02_order_manage']['03_out_shipment']['08_out_back_good'] = 'out_back_goodsV3.php?back_shipping_status=0';   //退换货收货、验货
$modules['02_order_manage']['03_out_shipment']['09_out_supplier_return_request_inventory'] = 'supplier_return/out_supplier_return_goods_request_list.php?view=facility';  //外包仓供应商退货仓库操作
$modules['02_order_manage']['03_out_shipment']['10_out_supplier_return_request'] = 'supplier_return/out_supplier_return_goods_request_list.php?view=purchase';  //外包仓供应商退货一览
$modules['02_order_manage']['03_out_shipment']['11_check_out_batch_with_tn'] = 'SinriTest/checkOutBatchWithTN.php';  //根据快递单号查外包批次

//同步订单管理
$modules['02_order_manage']['04_order_sync']['01_taobao_order_list'] = 'taobao/taobao_order_list.php';  // 淘宝订单列表
$modules['02_order_manage']['04_order_sync']['02_taobao_zhixiao_order_list'] = 'taobao/taobao_zhixiao_order_list.php';   //淘宝直销订单列表
$modules['02_order_manage']['04_order_sync']['03_taobao_fenxiao_order_list'] = 'taobao/taobao_fenxiao_order_list.php';   //淘宝分销订单列表
$modules['02_order_manage']['04_order_sync']['04_jd_order_list'] = 'jd/jd_order_list.php';  //京东商品列表
$modules['02_order_manage']['04_order_sync']['05_weixin_order_list'] = 'weixin/weixin_order_list.php';
$modules['02_order_manage']['04_order_sync']['06_amazon_order_list'] = 'amazon/amazon_order_list.php';
$modules['02_order_manage']['04_order_sync']['07_yhd_order_list'] = 'yhd/yhd_order_list.php';
$modules['02_order_manage']['04_order_sync']['08_heinz_sync_order'] = 'taobao/heinz_sync_order.php';   //亨氏问题查询和映射数据录入
$modules['02_order_manage']['04_order_sync']['09_jm_sync_order'] = 'weixin/jm_order_list.php';
$modules['02_order_manage']['04_order_sync']['10_apo_order_list'] = 'taobao/apo_order_list.php';  //德国药房订单同步


//淘宝外部订单导出
$modules['02_order_manage']['05_taobao_outside_shipped_order_export'] = 'taobao/taobao_outside_shipped_order_export.php';   //淘宝外部订单导出
//订单转仓管理（商品转仓申请页面）
$modules['02_order_manage']['06_order_facility'] = 'exchange_facility_express.php';   //订单转仓管理。。。待开发
//商品转仓规则管理
$modules['02_order_manage']['07_goods_facility_mapping'] = 'taobao/goods_facility_mapping.php';   //商品转仓规则管理
$modules['02_order_manage']['08_auto_confirm_control']    = 'auto_confirm_control.php';   //自动确认订单设置
$modules['02_order_manage']['09_claims_settlement']    = 'claims_settlement.php'; //理赔订单管理
//同步发货实验室
$modules['02_order_manage']['10_catholic_sync_delivery']['01_catholic_order_mapping_monitor'] = 'SinriTest/catholic_order_mapping_monitor.php';
$modules['02_order_manage']['10_catholic_sync_delivery']['02_taobao_split_oid_monitor'] = 'SinriTest/taobao_split_oid_monitor.php';
// 第三方物流订单同步管理
$modules['02_order_manage']['11_threeparts_sync_delivery']['01_bwshop_sync_order']    = 'jd/bwshop_show.php'; //保税仓订单同步监控
$modules['02_order_manage']['11_threeparts_sync_delivery']['02_bird_retro']    = 'SinriTest/bird_retro.php'; //菜鸟推送状态回退



/*采购管理*/
$modules['03_purchase_manage']['01_purchase_provider'] = 'buyer_supplier-manage.php';//供应商管理
$modules['03_purchase_manage']['02_generate_c_order'] = 'generate_c_orderV2.php';//下采购订单（-c）
$modules['03_purchase_manage']['03_batch_order_check'] = 'batch_order_statistics.php'; //采购订单查询
//采购发票维护
$modules['03_purchase_manage']['04_purchase_invoice']['01_purchase_invoice_request_list'] = 'purchase_invoice/purchase_invoice_request_list.php';   //开票清单管理
$modules['03_purchase_manage']['04_purchase_invoice']['01_purchase_invoice_request_list_new'] = 'purchase_invoice/purchase_invoice_request_list_new.php';   //新开票清单管理
$modules['03_purchase_manage']['04_purchase_invoice']['02_purchase_invoice_list'] = 'purchase_invoice/purchase_invoice_list.php';         //采购发票管理
$modules['03_purchase_manage']['05_supplier_return_request'] = 'supplier_return/supplier_return_goods_request.php';//供应商退货申请
$modules['03_purchase_manage']['06_generate_batch_sn_gt'] = 'generate_batch_sn_gt.php';  //供应商批次号退货申请
$modules['03_purchase_manage']['07_supplier_return_request'] = 'supplier_return/supplier_return_goods_request_list.php?view=purchase';//供应商退货一览
$modules['03_purchase_manage']['10_supplier_batch_dt_request'] = 'supplier_return/supplier_dt_goods_request.php';
$modules['03_purchase_manage']['11_generate_gt_batch_dt'] = 'generate_supplier_batch_dt.php';
$modules['03_purchase_manage']['12_supplier_dt_goods_request_list'] = 'supplier_return/supplier_dt_goods_request_list.php?view=purchase';
//-v管理
$modules['03_purchase_manage']['08_v_root']['01_v_apply'] = 'virance_inventory/physical_inventory_apply_order.php';  //盘点库存调整（-v）申请
$modules['03_purchase_manage']['08_v_root']['03_stock_take_adjust'] = 'virance_inventory/physical_inventory_out_inventory.php';  //盘点库存调整
$modules['03_purchase_manage']['08_v_root']['04_stock_take_adjust_batch'] = 'virance_inventory/batch_inventory_out.php';  //盘点库存调整批量调整

$modules['03_purchase_manage']['08_v_root']['02_v_apply_batch_sn'] = 'virance_inventory/physical_inventory_apply_order_batchsn.php';  //待批次号的盘点库存调整申请
$modules['03_purchase_manage']['08_v_root']['02_stock_take_adjust_batch_sn'] = 'virance_inventory/physical_inventory_out_inventory_batchsn.php';  // 带批次号的 盘点库存调整
$modules['03_purchase_manage']['08_v_root']['02_stock_take_adjust_batch_batch_sn'] = 'virance_inventory/batch_inventory_out_batchsn.php';  // 批量 带批次号的 盘点库存调整

$modules['03_purchase_manage']['08_v_root']['05_physical_inventory_apply_order_list'] = 'virance_inventory/physical_inventory_apply_order_detail.php'; // -v申请出入库明细
//库存调拨管理（先-gt 后-c问题 可参考中粮）
$modules['03_purchase_manage']['09_gt_c'] = 'blank.php';//库存调拨管理。。。待开发

/*商品管理*/
$modules['04_goods_manage']['01_goods_list'] = 'goods_index.php?act=index';   //商品添加/编辑
$modules['04_goods_manage']['02_goods_tags'] = 'taobao/tags.php';         // 商品标签
$modules['04_goods_manage']['03_goods_tags_category'] = 'taobao/tag_category.php';         // 商品标签
$modules['04_goods_manage']['04_category_add'] = 'category_index.php?act=index';  //商品分类添加
$modules['04_goods_manage']['05_goods_style_import'] = 'goods_style_import.php';  //商品批量导入
$modules['04_goods_manage']['06_goods_identify'] = 'goods_identify.php';  //商品标识维护
$modules['04_goods_manage']['07_inventory_item_detail'] = 'inventory_item_detail.php'; // 商品出入库明细
$modules['04_goods_manage']['08_taobao_items_list'] = 'taobao/taobao_items_list.php';   //淘宝直销商品
$modules['04_goods_manage']['09_taobao_fenxiao_items_list'] = 'taobao/taobao_fenxiao_items_list.php';   //淘宝分销商品

//对接管理-OR/LM
$modules['04_goods_manage']['10_integration']['01_brand_goods']       = 'brand_goods_maintain.php';    // 品牌商商品维护
$modules['04_goods_manage']['10_integration']['02_brand_integration_monitor']       = 'brand_integration_monitor.php';    // 品牌商对接监控


/*店铺管理*/
//$modules['05_shop_manage']['01_taobao_shop_conf']   = 'taobao/taobao_shop_conf.php';  // 淘宝店铺管理
//$modules['05_shop_manage']['02_taobao_erp_goods_manager'] = 'taobao_erp_goods_manager.php';
//$modules['05_shop_manage']['03_taobao_statistics'] = 'taobao/taobao_statistics.php';  // 淘宝店铺数据统计

/*活动管理*/
$modules['06_activity_manage']['01_whitelist_gifts_manage'] = 'gifts_manage/whitelist_gifts_manage.php';  //回赠白名单
$modules['06_activity_manage']['02_gifts_manage'] = 'gifts_manage/gifts_manage.php';  //添加赠品
$modules['06_activity_manage']['02_gift_activity'] = 'gift_activity/gift_activity.php';  //赠品活动【新】
// $modules['06_activity_manage']['03_msg_template_manage']	    = 'msg_template.php?act=list';   //短信模板
// $modules['06_activity_manage']['04_msg_send']	            = 'sendmessage.php';  //短信发送
// $modules['06_activity_manage']['05_msg_send_batch'] = 'sendmessagebatch.php';  //短信批量发送
$modules['06_activity_manage']['06_distribution_group_goods'] = 'distribution_group_goods.php';  // 套餐管理
$modules['06_activity_manage']['07_message_send_status'] = 'message_send_status.php';  // 短信发送列表
$modules['06_activity_manage']['08_crsms_status'] = 'crsms/index.php'; // CRSMS短信监控

/*库存管理*/
$modules['07_inventory_manage']['01_inventory_sync']['01_taobao_items_update'] = 'taobao/taobao_items_update.php';   //淘宝直销库存同步
$modules['07_inventory_manage']['01_inventory_sync']['02_taobao_fenxiao_items_update'] = 'taobao/taobao_fenxiao_items_update.php';   //淘宝分销库存同步
$modules['07_inventory_manage']['01_inventory_sync']['03_jd_items_update'] = 'jd/jd_items_update.php';  //京东库存同步
$modules['07_inventory_manage']['01_inventory_sync']['04_yhd_items_update'] = 'yhd/yhd_items_update.php';   //一号店库存同步
$modules['07_inventory_manage']['01_inventory_sync']['05_bird_budweiser_update'] = 'bird_wlb_items_update.php'; //菜鸟百威商品同步
$modules['07_inventory_manage']['01_inventory_sync']['06_baiduMall_items_update'] = 'jd/baiduMall_items_update.php'; //百度Mall商品同步
$modules['07_inventory_manage']['01_inventory_sync']['07_miya_items_update'] = 'jd/miya_items_update.php';
$modules['07_inventory_manage']['01_inventory_sync']['08_suning_items_update'] = 'jd/suning_items_update.php';
$modules['07_inventory_manage']['01_inventory_sync']['09_suning_facility_synchronize'] = 'suning_facility_synchronize.php';//分仓库同步
$modules['07_inventory_manage']['02_inventory_check'] = 'inventory_check.php'; // 库存查询
$modules['07_inventory_manage']['03_inventory_validity_check'] = 'inventory_validity_check.php'; // 查询库存有效期
$modules['07_inventory_manage']['04_inventory_syn_warning'] = 'taobao/inventory_syn_warning.php';   //库存同步预警页面设置
$modules['07_inventory_manage']['11_zero_inventory'] = 'import_zero_inventory.php';//零库存表导出


/*分销管理*/
$modules['08_distribution_manage']['01_main_distributor_manage'] = 'main_distributor_manage.php';  // 分销商维护
$modules['08_distribution_manage']['02_distributor_manage'] = 'distributor_manage.php';  // 分销店铺维护
//$modules['08_distribution_manage']['03_distribution_order_manage'] = 'distribution_order_manage.php'; //分销订单管理
//$modules['08_distribution_manage']['04_distribution_order_manage_type'] = 'distribution_order_manage.php?type=fenxiao'; //分销未确认订单管理
$modules['08_distribution_manage']['03_distribution_info_manage'] = 'distribution_info_manage.php'; //分销店铺详情维护
$modules['08_distribution_manage']['05_distribution_sale_price'] = 'distribution_sale_price.php';  // 商品预存款设置
$modules['08_distribution_manage']['06_distribution_product_track'] = 'distribution_product_track.php';  // 串号产品跟踪
$modules['08_distribution_manage']['07_distribution_order_adjustment'] = 'distribution_order_adjustment.php';            //预付款扣款查询
$modules['08_distribution_manage']['08_edu_sale_item'] = 'edu_sale_item.php';            //业务销量


/*运单管理*/
$modules['09_waybill_manage']['01_waybill_push_to_taobao'] = 'waybill_push_to_taobao.php';//直销运单推送到淘宝
$modules['09_waybill_manage']['02_purchase_arata_manage'] = 'thermal_manage.php';//快递热敏面单资源管理
$modules['09_waybill_manage']['03_export_tracking_number'] = 'export_tracking_number.php'; //导出快递单号

/*跨境管理*/
//订单管理
$modules['10_kuajing_manage']['01_kuajing_order_manage']['01_order_split_import'] = 'order_split_haiwai.php';//跨境购订单拆分
$modules['10_kuajing_manage']['01_kuajing_order_manage']['02_ERP_BWSHOP_ORDER_AGENT'] = 'bwshop/bw_order_monitor.php';//保税仓订单管理
//$modules['10_kuajing_manage']['01_kuajing_order_manage']['03_ERP_BWSHOP_ISSUE_ORDERS'] = 'bwshop/bw_issue_orders.php'; //E2B转化遗漏搜救
$modules['10_kuajing_manage']['01_kuajing_order_manage']['03_ERP_BWSHOP_SIGHT_ORDERS'] = 'bwshop/bw_order_sight.php'; //保税仓实时报表
$modules['10_kuajing_manage']['01_kuajing_order_manage']['04_ERP_BWSHOP_SIGHT_INSERT'] = 'bwshop/import/'; //Bwshop订单录入
$modules['10_kuajing_manage']['01_kuajing_order_manage']['05_haiguan_order'] = 'haiguan_order.php'; //申报系统订单监控
$modules['10_kuajing_manage']['01_kuajing_order_manage']['06_haiguan_payInfo_import'] = 'payInfoImport.php'; //申报系统交易信息导入
//$modules['10_kuajing_manage']['01_kuajing_order_manage']['07_haiguan_order_import'] = 'kuajinggou/import'; //申报系统订单导入
$modules['10_kuajing_manage']['01_kuajing_order_manage']['08_haiguan_pay'] = 'haiguan_pay_info.php'; //申报系统支付方式维护
$modules['10_kuajing_manage']['01_kuajing_order_manage']['08_haiguan_order_import'] = 'declaration_order_import/'; //申报系统订单导入
$modules['10_kuajing_manage']['01_kuajing_order_manage']['09_declaration_order_check'] = 'declaration_order_check.php'; //申报系统订单比例监控
$modules['10_kuajing_manage']['01_kuajing_order_manage']['10_HAIGUAN_SIGHT_ORDERS'] = 'haiguan_order_sight.php';//申报系统订单异常报表
$modules['10_kuajing_manage']['01_kuajing_order_manage']['11_HAIGUAN_FENXIO_ORDERS'] = 'haiguan_fenxiao_order_check.php';//申报系统订单异常报表
$modules['10_kuajing_manage']['01_kuajing_order_manage']['12_GZ_HAIGUAN_ORDERS'] = 'gz_haiguan_order.php';//广州海关订单
//商品管理
$modules['10_kuajing_manage']['02_kuajing_product_manage']['01_kuajing_items'] = 'kuajing_items.php';//菜鸟商品维护
$modules['10_kuajing_manage']['02_kuajing_product_manage']['02_haiguan_goods']  = 'haiguan_goods_info.php';//申报系统商品维护
$modules['10_kuajing_manage']['02_kuajing_product_manage']['03_haiguan_batch'] = 'batchGoodsOrder.php'; //申报系统商品批量录入
$modules['10_kuajing_manage']['02_kuajing_product_manage']['04_ERP_BWSHOP_ZEIRITSU'] = 'bwshop/bw_goods_tax.php'; //保税仓税率管理
$modules['10_kuajing_manage']['02_kuajing_product_manage']['05_gz_haiguan_goods'] = 'gz_haiguan_goods_info.php'; //海关商品维护
//店铺维护
$modules['10_kuajing_manage']['03_kuajing_shop_manage']['01_haiguan_shop'] = 'haiguan_shop_info.php';//申报系统店铺维护
$modules['10_kuajing_manage']['03_kuajing_shop_manage']['02_ERP_BWSHOP_SHOP_AGENT'] = 'bwshop/bwshop_agent.php';//保税仓店铺管理
$modules['10_kuajing_manage']['03_kuajing_shop_manage']['03_ERP_BWSHOP_IKENIE_AGENT'] = 'bwshop/bw_mibun_pool.php'; //保税仓生贄管理

/*财务管理*/
//订单管理
$modules['11_finance_manage']['01_order_manage']['01_financeV2'] = 'financeV2.php';                                //财务收款
$modules['11_finance_manage']['01_order_manage']['02_batch_payment'] = 'batch_payment.php';                             //批量付款
$modules['11_finance_manage']['01_order_manage']['03_finance_payment_import'] = 'finance_payment_import.php';       //批量收款
$modules['11_finance_manage']['01_order_manage']['04_finance_payment_import_fenxiao'] = 'finance_payment_import_fenxiao.php';       //分销批量收款
$modules['11_finance_manage']['01_order_manage']['05_query_order_relation'] = 'queryorderrelation.php';             //查询销售订单-t-h
$modules['11_finance_manage']['01_order_manage']['06_query_purchase_supplier_order'] = 'query_purchase_supplier_order.php';         //查询采购 -gt订单
$modules['11_finance_manage']['01_order_manage']['08_refund_list_new'] = 'refund_list_new.php?view=3&auto_refresh=1';   		 //退款申请列表	
$modules['11_finance_manage']['01_order_manage']['09_payment_transaction_list'] = 'payment_transaction_list.php';   //支付交易列表
$modules['11_finance_manage']['01_order_manage']['10_currency_scale'] = 'currency_scale.php';     //汇率管理
// 采购发票管理
$modules['11_finance_manage']['02_purchase_invoice']['01_purchase_invoice_request_list'] = 'purchase_invoice/purchase_invoice_request_list.php';   //开票清单管理
$modules['11_finance_manage']['02_purchase_invoice']['01_purchase_invoice_request_list_new'] = 'purchase_invoice/purchase_invoice_request_list_new.php';   //新开票清单管理
$modules['11_finance_manage']['02_purchase_invoice']['02_purchase_invoice_list'] = 'purchase_invoice/purchase_invoice_list.php';         //采购发票管理
$modules['11_finance_manage']['02_purchase_invoice']['03_purchase_uninvoiced_product'] = 'purchase_invoice/purchase_uninvoiced_product.php';     //未开票商品明细
$modules['11_finance_manage']['02_purchase_invoice']['04_c2c_buy_sale'] = 'c2c_buy_sale.php';    //内部结账
//销售发票管理
$modules['11_finance_manage']['03_sales_invoice']['01_sales_invoice_manager'] = 'sales_invoice_manage.php';        //销售发票管理
$modules['11_finance_manage']['03_sales_invoice']['02_no_shipping_invoice'] = 'no_shipping_invoice.php';          //直销待开票订单
$modules['11_finance_manage']['03_sales_invoice']['03_sales_invoice_request_list'] = 'sales_invoice_request_list.php';             //销售发票请求
$modules['11_finance_manage']['03_sales_invoice']['04_sales_invoice_list'] = 'sales_invoice_list.php';        //销售发票列表
$modules['11_finance_manage']['03_sales_invoice']['06_print_invoice'] = 'invoice_manage/print_invoice.php?action=list';      //打印补寄发票
$modules['11_finance_manage']['03_sales_invoice']['07_batch_print_invoice'] = 'invoice_manage/batch_print_invoice.php?action=list';      //批量打印补寄发票
//预存款管理
$modules['11_finance_manage']['04_pre_deposit']['01_preparment'] = 'prepayment.php';          //预付款管理
$modules['11_finance_manage']['04_pre_deposit']['02_distribution_order_adjustment'] = 'distribution_order_adjustment.php';            //预付款扣款查询
$modules['11_finance_manage']['04_pre_deposit']['03_report'] = 'report.php';      //预付款账单
$modules['11_finance_manage']['04_pre_deposit']['05_adjustment_total_report'] = 'adjustment_total_report.php';      //预付款账单汇总
$modules['11_finance_manage']['04_pre_deposit']['04_rebeat'] = 'rebate.php?act=select_total';         //返点管理
//快递费管理
$modules['11_finance_manage']['05_shipping_fee']['01_express_fee_clearing']='express_clearing.php';	//物流对账结算
$modules['11_finance_manage']['05_shipping_fee']['02_freight_details'] = 'freight_details.php';          //运费对账明细
//查询管理
$modules['11_finance_manage']['06_finance_search']['01_current_inventory_balance_query'] = 'current_inventory_balance_query.php';          //运费对账明细

/* 物流管理*/
$modules['12_priv_admin']['01_carrier_manage']          = 'carrier.php';   //承运商管理
$modules['12_priv_admin']['05_area_list_hakobiya']		= 'area_manage_hakobiya.php?act=list';  //物流费用
$modules['12_priv_admin']['06_distribution_shipping']   = 'distribution_shipping_manage.php';//分销快递公司选择
$modules['12_priv_admin']['08_consumable_party_facility'] = 'consumable_party_facility.php';//耗材出库仓库设置
$modules['12_priv_admin']['09_party_assign_shipping'] = 'party_assign_shipping_manage.php'; // 组织最优快递设置
$modules['12_priv_admin']['10_distributor_assign_shipping'] = 'distributor_assign_shipping_manage.php'; // 店铺最优快递设置
$modules['12_priv_admin']['04_area_list']               = 'area_manage.php?act=list';  //地区列表


/* 仓库管理*/
//入库业务
$modules['14_wms']['01_menu_in_storage']['01_purchase_order_display'] = 'purchase_order_list_displayV5.php'; // 显示采购订单
$modules['14_wms']['01_menu_in_storage']['02_receive_rf_scan'] = 'receive_rf_scan.php'; // 收货RF枪扫描
$modules['14_wms']['01_menu_in_storage']['03_grouding_rf_scan'] = 'grouding_rf_scan.php'; // 上架RF枪扫描
$modules['14_wms']['01_menu_in_storage']['04_moving_rf_scan'] = 'moving_rf_scan.php';//移库RF枪扫描
$modules['14_wms']['01_menu_in_storage']['05_t_in'] = 't_in_info.php'; //销退入库详情
$modules['14_wms']['01_menu_in_storage']['06_batch_in_storage'] = 'batch_in_storage.php'; // 批量入库
$modules['14_wms']['01_menu_in_storage']['07_batch_or_in_storage'] = 'batch_or_in_storage.php'; // 批次号批量入库
$modules['14_wms']['01_menu_in_storage']['08_double_eleven_shelves'] = 'double_eleven_shelves.php'; // 上架工具
$modules['14_wms']['01_menu_in_storage']['09_po_in'] = 'in_storage.php?act=today'; // 收货入库
$modules['14_wms']['01_menu_in_storage']['10_inventory_location'] = 'inventory_location.php';//容器管理
$modules['14_wms']['01_menu_in_storage']['11_purchase_order_display_new'] = 'purchase_order_list_displayV6.php'; // 显示采购订单新

// 出库业务
$modules['14_wms']['02_menu_out_storage']['01_batch_picking_list'] = 'shipment_listV5.php'; // 打印批拣单
$modules['14_wms']['02_menu_out_storage']['02_batch_picking_list_recommand'] = 'shipment_listV5_for_1111.php'; // 打印批拣单（自动推荐）
$modules['14_wms']['02_menu_out_storage']['03_deal_shipment'] = 'Deal_Shipment_Print.php'; // 打印批拣发货单
$modules['14_wms']['02_menu_out_storage']['04_deal_carrierbill'] = 'Deal_CarrierBill_Print.php'; // 打印批拣面单
$modules['14_wms']['02_menu_out_storage']['05_print_carrier_bill_arata'] = 'Deal_CarrierBill_Print_Arata.php'; // 热敏快递单打印
$modules['14_wms']['02_menu_out_storage']['06_deal_card_print'] = 'Deal_Card_Print.php'; //打印贺卡
$modules['14_wms']['02_menu_out_storage']['08_shipment_recheck'] = 'shipment_recheck.php';//发货单复核
$modules['14_wms']['02_menu_out_storage']['09_shipment_batch_recheck'] = 'shipment_batch_recheck.php';//发货单复核(预包装)
$modules['14_wms']['02_menu_out_storage']['10_add_order_shipment'] = 'add_order_shipment.php';//追加面单
$modules['14_wms']['02_menu_out_storage']['11_batch_add_order_shipment_arata_add'] = 'batch_add_order_shipment_arata_add.php';//追加面单的再次打印
$modules['14_wms']['02_menu_out_storage']['12_shipment'] = 'shipment.php'; // 订单称重发货
$modules['14_wms']['02_menu_out_storage']['13_weighted_not_shipment'] = 'weighted_not_shipment.php'; // 已称重未发货
$modules['14_wms']['02_menu_out_storage']['14_shipment_limit_party_list'] = 'distribution_delivery_list.php'; //串号商品发货
$modules['14_wms']['02_menu_out_storage']['15_distribution_purchase_request'] = 'distribution_purchase_request.php';//提货清单
$modules['14_wms']['02_menu_out_storage']['16_distribution_delivery'] = 'distribution_delivery.php';//电教发货
$modules['14_wms']['02_menu_out_storage']['17_issue'] = 'issue.php'; // 订单不称重发货
$modules['14_wms']['02_menu_out_storage']['18_dcV2'] = 'dcV2.php'; // 待发货
$modules['14_wms']['02_menu_out_storage']['19_picking_list_inputted'] = 'shipment_list_for_input_shipment.php';  //打印导入拣货单
$modules['14_wms']['02_menu_out_storage']['20_shipment_pick'] = 'shipment_pick.php';   //配货出库
$modules['14_wms']['02_menu_out_storage']['21_distribution_dph'] = 'distribution_dph.php?red_notice=3'; // 待配货
$modules['14_wms']['02_menu_out_storage']['22_dispatch_edit'] = 'dispatch_edit.php';//订单-发货单编辑
$modules['14_wms']['02_menu_out_storage']['23_shipment_batch_pick_recheck'] = 'shipment_batch_pick_recheck.php';//批次号复核
$modules['14_wms']['02_menu_out_storage']['24_add_order_shipment_new'] = 'add_order_shipment_new.php';//追加普通面单
$modules['14_wms']['02_menu_out_storage']['25_add_arata_order_shipment_new'] = 'add_arata_order_shipment_new.php';//追加热敏面单
$modules['14_wms']['02_menu_out_storage']['26_add_order_shipment_arata_add_new'] = 'add_order_shipment_arata_add_new.php';//追加热敏面单再次单独打印
$modules['14_wms']['02_menu_out_storage']['27_facility_staff_info_import'] = 'facility_staff_info_import.php';//批拣人员信息录入
$modules['14_wms']['02_menu_out_storage']['28_batch_pick'] = 'batch_pick.php';//分配批拣单
$modules['14_wms']['02_menu_out_storage']['29_batch_pick'] = 'pallet_bind.php';//绑定码托条码
$modules['14_wms']['02_menu_out_storage']['30_batch_pick'] = 'pallet_unbind.php';//解绑码托条码
$modules['14_wms']['02_menu_out_storage']['31_pallet_shipment'] = 'pallet_shipment.php';//码托交接发货

//借还机业务
$modules['14_wms']['03_menu_in_out_storage']['01_h_borrow'] = 'h_borrow.php';//借机
$modules['14_wms']['03_menu_in_out_storage']['02_h_return'] = 'h_return.php';//还击
// 查询业务
$modules['14_wms']['04_menu_facility_search']['01_inventory_check'] = 'inventory_check.php'; // 库存查询
$modules['14_wms']['04_menu_facility_search']['02_inventory_validity_check'] = 'inventory_validity_check.php'; // 查询库存有效期
$modules['14_wms']['04_menu_facility_search']['03_purchase'] = 'purchase.php?pfrom=wms'; //仓库查询                       
$modules['14_wms']['04_menu_facility_search']['04_search_batch_pick'] = 'search_batch_pick.php'; //批拣单完结与查询
$modules['14_wms']['04_menu_facility_search']['05_search_sick_batch_pick'] = 'search_sick_batch_pick.php'; //问题批拣单查询
$modules['14_wms']['04_menu_facility_search']['06_search_shipment_bill'] = 'search_shipment_bill.php'; //发货单查询
$modules['14_wms']['04_menu_facility_search']['07_inventory_item_detail'] = 'inventory_item_detail.php'; // 查询商品出入库明细
$modules['14_wms']['04_menu_facility_search']['08_unshipping_order_clean'] = 'unshipping_order_clean.php'; // 未发货订单清理
$modules['14_wms']['04_menu_facility_search']['09_order_batch_pick_checker'] = 'SinriTest/order_batch_pick_checker.php';//
$modules['14_wms']['04_menu_facility_search']['10_search_pallet_bind'] = 'search_pallet_bind.php';//包裹码托查询
$modules['14_wms']['04_menu_facility_search']['12_search_pallet_bind_new'] = 'search_pallet_bind_new.php';//包裹码托查询
$modules['14_wms']['04_menu_facility_search']['11_shipping_handover'] = 'shipping_handover.php';//快递交接单查询
//设备管理
$modules['14_wms']['05_menu_facility_manage']['03_facility_manage'] = 'facility_manage.php'; // 仓库设施管理
$modules['14_wms']['05_menu_facility_manage']['04_office_shipment'] = 'office_shipment.php?act=insert'; // 办公件管理
$modules['14_wms']['05_menu_facility_manage']['05_print_barcode'] = 'print_barcodeV5.php'; // 条码打印
$modules['14_wms']['05_menu_facility_manage']['06_location_barcodes_xls'] = 'inventory_location_barcodes_xls.php'; // 库位条码批量打印
$modules['14_wms']['05_menu_facility_manage']['07_print_goods_identify'] = 'print_goods_identify.php'; // 商品标识打印
$modules['14_wms']['05_menu_facility_manage']['08_facility_location_manage'] = 'facility_location_manage.php';  //仓库库位管理
$modules['14_wms']['05_menu_facility_manage']['09_search_product_facility_location'] = 'search_product_facility_location.php';  //商品库位查询
$modules['14_wms']['05_menu_facility_manage']['10_print_after_scan_code'] = 'print_after_scan_code.php'; // 扫码打印
//售后处理
$modules['14_wms']['06_after_sale']['01_th_order'] = 'rejected_order.php'; // 拒收收货
$modules['14_wms']['06_after_sale']['02_back_good'] = 'back_goodsV3.php?back_shipping_status=0';   //退换货收货、验货
$modules['14_wms']['06_after_sale']['08_wl_refund_list'] = 'refund_list_new.php?view=2&auto_refresh=1';  //退款申请列表
$modules['14_wms']['06_after_sale']['04_supplier_return_request_inventory'] = 'supplier_return/supplier_return_goods_request_list.php?view=facility';  //供应商退货仓库操作
$modules['14_wms']['06_after_sale']['06_export_bill_no'] = 'export_bill_no.php'; // 导出快递交接信息
$modules['14_wms']['06_after_sale']['07_shipped_cancel'] = 'shipped_cancel.php'; // 追回货物
//库存冻结
$modules['14_wms']['07_goods_facility_reserved'] = 'taobao/goods_facility_reserved.php';//商品预留设置

/* 系统设置*/
$modules['13_system']['01_party_manage']        = 'party_manage.php';   //PARTY管理
$modules['13_system']['01_shop_distributor_manage'] = 'shop_maintenance/shop_distributor_info.php'; //店铺供应商维护
$modules['13_system']['02_role_manage']			= 'role.php?act=list';//角色管理
$modules['13_system']['03_admin_list']             = 'privilege.php?act=list';   //管理员列表
$modules['13_system']['04_admin_logs']             = 'admin_logs.php?act=list';   //管理员日志
$modules['13_system']['05_priv_list']              = 'admin_priv_list.php';    //管理员权限查看
$modules['13_system']['02_payment_list']            = 'payment.php?act=list';   //支付方式
$modules['13_system']['03_shipping_list']           = 'shipping.php?act=list&self_list=1';   //配送方式
$modules['13_system']['11_database_status'] = 'database_status.php';//数据库状态
$modules['13_system']['12_shop_weight_list'] = 'shop_weight_list.php';//店铺权重设置
$modules['13_system']['01_taobao_shop_conf']   = 'taobao/taobao_shop_conf.php';  // 淘宝店铺管理
$modules['13_system']['00_ecshop_config'] = 'shop_config.php?act=list_edit';//办公IP设定 监工的ip修改大触


/* 统计信息*/
$modules['15_analyze']['01_report_version_2'] = 'in_out_main.php?version=2';   //常用报表
$modules['15_analyze']['02_analyze_shipping']= 'analyze_shipping.php';   //发货统计
$modules['15_analyze']['03_dianzhangreport'] = 'dianzhang_report.php';  //店长报表数据导出
$modules['15_analyze']['04_indicate_query'] = 'indicate.php';   //指示查询
$modules['15_analyze']['05_wyeth_report'] = 'wyeth_report.php';   //指示查询
$modules['15_analyze']['09_old_report'] = 'in_out_main.php';   //常用报表(old)

/*中粮项目*/
if(check_admin_priv('zhongliang_sync_info')){
	///客服管理
	$modules['16_zhongliang']['01_customer_service']['01_zhongliang_order_entry'] = 'distribution_order.php';//销售订单录入
	$modules['16_zhongliang']['01_customer_service']['02_zhongliang_batch_order'] = 'batchDisOrder_zl.php';  //中粮批量录单
	$modules['16_zhongliang']['01_customer_service']['03_zhongliang_paid_unconfirmed'] = 'csmo.php?type=paid_unconfirmed'; // 先款后货未确认订单
	$modules['16_zhongliang']['01_customer_service']['04_zhongliang_back_service'] = 'sale_serviceV3.php?service_type=2&service_status=0&back_shipping_status=0';//退货申请
	$modules['16_zhongliang']['01_customer_service']['05_zhongliang_dealer_tc'] = 'zhongliang_dealer_tc_mapping.php';//中粮喜宴tc设置
	
	///订单同步信息
	$modules['16_zhongliang']['02_order_sync']['01_zhongliang_purchase_order_list'] = 'taobao/zhongliang_purchase_order_list.php';   //中粮采购订单同步
	$modules['16_zhongliang']['02_order_sync']['02_zhongliang_sales_list'] = 'taobao/zhongliang_order_list.php';   //中粮销售订单同步
	$modules['16_zhongliang']['02_order_sync']['03_zhongliang_return_order_list'] = 'taobao/zhongliang_return_order_list.php';   //中粮退换货订单同步
	$modules['16_zhongliang']['02_order_sync']['04_zhongliang_b2b_order_list'] = 'taobao/zhongliang_b2b_order_list.php';   //中粮B2B订单同步
	///库存同步信息
	$modules['16_zhongliang']['03_inventory_sync']['01_zhongliang_inventory_compare'] = 'inventory_cofco_sync.php';   //中粮同步信息
	$modules['16_zhongliang']['03_inventory_sync']['02_goods_facility_reserved'] = 'taobao/goods_facility_reserved.php';   //库存预留设置
	$modules['16_zhongliang']['03_inventory_sync']['03_zhongliang_inventory_ratio_edit'] = 'inventory_ratio_zl.php';  //中粮店铺库存比例设置
	
	$modules['16_zhongliang']['04_zhongliang_b2b_out'] = 'generate_cofco_gt.php';   //中粮B2B出库
	$modules['16_zhongliang']['05_zhongliang_b2b_out_list'] = 'supplier_return/zhongliang_return_goods_request_list.php?view=purchase';  //中粮B2B出库详情
	$modules['16_zhongliang']['06_generate_c_order'] = 'generate_cofco_c_order.php';  //下入库单
	$modules['16_zhongliang']['07_zhongliang_Batch_order'] = 'batch_order_statistics.php';  //入库详情
	$modules['16_zhongliang']['08_zhongliang_inventory_apply'] = 'virance_inventory/physical_inventory_apply_order.php';    //中粮盘点申请
	$modules['16_zhongliang']['09_zhongliang_inventory_apply_out'] = 'virance_inventory/physical_inventory_out_inventory.php';   //中粮盘点调整
	$modules['16_zhongliang']['10_zhongliang_inventory_item'] = 'inventory_item_detail.php';  //中粮出入库记录
	$modules['16_zhongliang']['11_zhongliang_inventory_query'] = 'inventory_check.php';   //库存查询	
}

/*金宝贝项目*/
//$modules['17_gymboree']['01_gymboree_order_list'] = 'gymboree_order_list.php';   //金宝贝订单同步

/*ecco项目*/
if(check_admin_priv('print_goods_identify')){
	$modules['18_ecco']['01_print_goods_identify'] = 'print_goods_identify.php';//销售订单录入
}


if(check_admin_priv('best_express_out_ship') && !(check_admin_priv('kf_order_search','inventory_picking','finance_order'))){
	unset($modules);
	$modules['02_order_manage']['03_out_shipment']['04_out_shipment_print'] = 'search_out_batch_pick.php';  //打印面单
	$modules['02_order_manage']['03_out_shipment']['11_check_out_batch_with_tn'] = 'SinriTest/checkOutBatchWithTN.php';  //根据快递单号查外包批次
	$modules['02_order_manage']['03_out_shipment']['06_out_receive_rf_scan'] = 'out_receive_rf_scan.php'; // 外包收货RF枪扫描
	$modules['02_order_manage']['03_out_shipment']['07_out_batch_or_in_storage'] = 'out_batch_or_in_storage.php'; // 批次号批量入库
	$modules['02_order_manage']['03_out_shipment']['09_out_supplier_return_request_inventory'] = 'supplier_return/out_supplier_return_goods_request_list.php?view=facility';  //外包仓供应商退货仓库操作
}
?>