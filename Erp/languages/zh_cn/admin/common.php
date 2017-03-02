<?php

/**
 * ECSHOP 管理中心共用语言文件
 * ============================================================================
 * 版权所有 (C) 2005-2006 北京亿商互动科技发展有限公司，并保留所有权利。
 * 网站地址: http://www.ecshop.com
 * ----------------------------------------------------------------------------
 * 这是一个免费开源的软件；这意味着您可以在不用于商业目的的前提下对程序代码
 * 进行修改、使用和再发布。
 * ============================================================================
 * @author:     Weber Liu <weberliu@hotmail.com>
 * @version:    v2.0
 * ---------------------------------------------
 * $Author: weberliu $
 * $Date: 2007-05-08 20:49:11 +0800 (星期二, 08 五月 2007) $
 * $Id: common.php 8524 2007-05-08 12:49:11Z weberliu $
*/

$_LANG['app_name'] = 'LEQEE';
$_LANG['cp_home'] = 'LEQEE管理系统';
$_LANG['copyright'] = '版权所有 &copy; 2009 ouku.com, 2010-2011 leqee.com';
$_LANG['query_info'] = '共执行 %d 个查询，用时 %s 秒';
$_LANG['memory_info'] = '，内存占用 %0.3f MB';
$_LANG['gzip_enabled'] = '，Gzip 已启用';
$_LANG['gzip_disabled'] = '，Gzip 已禁用';
$_LANG['loading'] = '正在处理您的请求...';
$_LANG['js_languages']['process_request'] = '正在处理您的请求...';
$_LANG['auto_redirection'] = '如果您不做出选择，将在 <span id="spanSeconds">3</span> 秒后跳转到第一个链接地址。';
$_LANG['password_rule'] = '密码应只包含英文字符、数字.长度在6--16位之间';
$_LANG['username_rule'] = '用户名应为汉字、英文字符、数字组合，3到15位';
$_LANG['plugins_not_found'] = '插件 %s 无法定位';
$_LANG['no_records'] = '没有找到任何记录';

$_LANG['require_field'] = '<span class="require-field">*</span>';
$_LANG['yes'] = '是';
$_LANG['no'] = '否';
$_LANG['record_id'] = '编号';
$_LANG['handler'] = '操作';
$_LANG['install'] = '安装';
$_LANG['uninstall'] = '卸载';
$_LANG['list'] = '列表';
$_LANG['add'] = '添加';
$_LANG['edit'] = '编辑';
$_LANG['view'] = '查看';
$_LANG['remove'] = '移除';
$_LANG['drop'] = '删除';
$_LANG['disabled'] = '禁用';
$_LANG['enabled'] = '启用';
$_LANG['setup'] = '设置';
$_LANG['success'] = '成功';
$_LANG['sort_order'] = '排序';
$_LANG['trash'] = '回收站';
$_LANG['restore'] = '还原';
$_LANG['close_window'] = '关闭窗口';

$_LANG['empty'] = '不能为空';
$_LANG['repeat'] = '已存在';
$_LANG['is_int'] = '应该为整数';

$_LANG['button_submit'] = ' 确定 ';
$_LANG['button_save'] = ' 保存 ';
$_LANG['button_reset'] = ' 重置 ';
$_LANG['button_search'] = ' 搜索 ';

$_LANG['priv_error'] = '对不起,您没有执行此项操作的权限!';
$_LANG['drop_confirm'] = '您确认要删除这条记录吗?';
$_LANG['form_notice'] = '点击此处查看提示信息';
$_LANG['upfile_type_error'] = '上传文件的类型不正确!';
$_LANG['upfile_error'] = '上传文件失败!';

$_LANG['go_back'] = '返回上一页';
$_LANG['back'] = '返回';
$_LANG['continue'] = '继续';
$_LANG['system_message'] = '系统信息';
$_LANG['check_all'] = '全选';
$_LANG['select_please'] = '请选择...';
$_LANG['all_category'] = '所有分类';
$_LANG['all_brand'] = '所有品牌';
$_LANG['refresh'] = '刷新';
$_LANG['update_sort'] = '更新排序';
$_LANG['modify_failure'] = '修改失败!';
$_LANG['attradd_succed'] = '操作成功!';

/* 编码 */
$_LANG['charset']['utf8'] = '国际化编码（utf8）';
$_LANG['charset']['zh_cn'] = '简体中文';
$_LANG['charset']['zh_tw'] = '繁体中文';
$_LANG['charset']['en_us'] = '美国英语';
$_LANG['charset']['en_uk'] = '英文';

/* 新订单通知 */
$_LANG['order_notify'] = '新订单通知';
$_LANG['new_order_1'] = '您有 ';
$_LANG['new_order_2'] = ' 个新订单以及 ';
$_LANG['new_order_3'] = ' 个新付款的订单';
$_LANG['new_order_link'] = '点击查看新订单';

/*语言项*/
$_LANG['chinese_simplified'] = '简体中文';
$_LANG['english'] = '英文';

/* 分页 */
$_LANG['total_records'] = '总计 ';
$_LANG['total_pages'] = '个记录分为';
$_LANG['page_size'] = '页，每页';
$_LANG['page_current'] = '页当前第';
$_LANG['page_first'] = '第一页';
$_LANG['page_prev'] = '上一页';
$_LANG['page_next'] = '下一页';
$_LANG['page_last'] = '最末页';
$_LANG['admin_home'] = '起始页';

/* 重量 */
$_LANG['gram'] = '克';
$_LANG['kilogram'] = '千克';

/* cls_image类的语言项 */
$_LANG['directory_readonly'] = '目录 % 不存在或不可写';
$_LANG['invalid_upload_image_type'] = '不是允许的图片格式';
$_LANG['upload_failure'] = '文件 %s 上传失败。';
$_LANG['missing_gd'] = '没有安装GD库';
$_LANG['missing_orgin_image'] = '找不到原始图片 %s ';
$_LANG['nonsupport_type'] = '不支持该图像格式 %s ';
$_LANG['creating_failure'] = '创建图片失败';
$_LANG['writting_failure'] = '图片写入失败';
$_LANG['empty_watermark'] = '水印文件参数不能为空';
$_LANG['missing_watermark'] = '找不到水印文件%s';
$_LANG['create_watermark_res'] = '创建水印图片资源失败。水印图片类型为%s';
$_LANG['create_origin_image_res'] = '创建原始图片资源失败，原始图片类型%s';
$_LANG['invalid_image_type'] = '无法识别水印图片 %s ';
$_LANG['file_unavailable'] = '文件 %s 不存在或不可读';

/* 邮件发送错误信息 */
$_LANG['smtp_setting_error'] = '邮件服务器设置信息不完整';
$_LANG['smtp_connect_failure'] = '无法连接到邮件服务器 %s';
$_LANG['smtp_login_failure'] = '邮件服务器验证帐号或密码不正确';
$_LANG['smtp_refuse'] = '服务器拒绝发送该邮件';


/* 菜单分类部分 （一级菜单）*/
$_LANG['11_for1111'] = '双十一预案';
$_LANG['00_REWITE_WORKFLOW'] = '重写流程';
$_LANG['00_MINUS_OUKOO_ERP'] = '废老库存';
$_LANG['01_ERPDEV'] = 'ERP开发管理';
$_LANG['02_order_manage'] = '订单管理';
$_LANG['03_purchase_manage'] = '采购管理';
$_LANG['04_goods_manage']='商品管理 ';
$_LANG['05_shop_manage'] = '店铺管理';
$_LANG['06_activity_manage'] = '活动管理';
$_LANG['07_inventory_manage'] = '库存管理';
$_LANG['08_distribution_manage'] = '分销管理';
$_LANG['09_waybill_manage'] = '运单管理';
$_LANG['10_kuajing_manage'] = '跨境管理';
$_LANG['11_finance_manage'] = '财务管理';
$_LANG['12_priv_admin'] = '物流管理';
$_LANG['13_system'] = '系统设置';
$_LANG['14_wms'] = '仓库管理';
$_LANG['15_analyze'] = '统计信息';
$_LANG['16_zhongliang'] = '中粮项目';
$_LANG['17_gymboree'] = '金宝贝项目';
$_LANG['18_ecco'] = 'ecco项目'; 


/* 双十一预案 */
$_LANG['01_batch_change_delivery'] = '批量修改面单';
$_LANG['01_order_tracking'] = '订单状态追踪';
$_LANG['01_all_order_tracking'] = '全部订单状态追踪';

/*重写流程*/
$_LANG['00_REWITE_workflow_purchase'] = '采购流程';
$_LANG['00_REWITE_workflow_purchase_generate'] = '采购申请[运营]';
$_LANG['00_REWITE_workflow_purchase_verify'] = '采购审批[运营]';
$_LANG['00_REWITE_workflow_purchase_order_list'] = '采购订单列表[运营]';
$_LANG['00_REWITE_workflow_purchase_checked_order_list'] = '可入库采购订单查看[物流]';
$_LANG['00_REWITE_workflow_sales'] = '销售流程';
$_LANG['00_REWITE_workflow_sales_generate'] = '销售订单录入(申请)[客服]';
$_LANG['00_REWITE_workflow_sales_verify'] = '销售订单审核[客服]';
$_LANG['00_REWITE_workflow_sales_order_list'] = '销售订单操作[客服、运营]';
$_LANG['00_REWITE_workflow_sales_shipment_list'] = '可拣货发货单查看[物流]';
$_LANG['00_REWITE_workflow_warehouse_in_stock'] = '仓库入库流程';
$_LANG['00_REWITE_workflow_warehouse_in_stock_order_list'] = '显示入库单';
$_LANG['00_REWITE_workflow_warehouse_in_stock_recv_rf'] = 'RF枪收货';
$_LANG['00_REWITE_workflow_warehouse_in_stock_ground_rf'] = 'RF枪上架';
$_LANG['00_REWITE_workflow_warehouse_out_stock'] = '仓库出库流程';
$_LANG['00_REWITE_workflow_warehouse_out_stock_order_list'] = '显示出库单';

/*废老库存*/
$_LANG['00_MINUS_OUKOO_ERP_purchase'] = '采购流程';
$_LANG['00_MINUS_OUKOO_ERP_sales'] = '销售流程';
$_LANG['00_MINUS_OUKOO_ERP_back_change'] = '退换货流程';
$_LANG['00_MINUS_OUKOO_ERP_purchase_action'] = '采购订单操作';
$_LANG['00_MINUS_OUKOO_ERP_purchase_new_stock_in'] = '收货入库(新流程)';
$_LANG['00_MINUS_OUKOO_ERP_purchase_old_stock_in'] = '收货入库(老流程)';
$_LANG['00_MINUS_OUKOO_ERP_shipment_pick_zhixiao'] = '配货出库(直销)';
$_LANG['00_MINUS_OUKOO_ERP_shipment_pick_fenxiao'] = '配货出库(分销)';
$_LANG['00_MINUS_OUKOO_ERP_distribution_order'] = '分销录入订单';
$_LANG['00_MINUS_OUKOO_ERP_one_key_batch_pick'] = '一键完结';
$_LANG['00_MINUS_OUKOO_ERP_recheck'] = '复核';
$_LANG['00_MINUS_OUKOO_ERP_back_change_service'] = '退换货申请、审核';
$_LANG['00_MINUS_OUKOO_ERP_back_change_goods_in'] = '退换货收货、验货';
$_LANG['00_MINUS_OUKOO_ERP_back_change_complete'] = '退换货完结（-h）';
$_LANG['00_MINUS_OUKOO_ERP_shipped_cancel'] = '取消追回、拒收收货（-t）';
$_LANG['00_MINUS_OUKOO_ERP_auto_service'] = '自动售后收货入库（-t）';
$_LANG['00_MINUS_OUKOO_h_borrow_return'] = '借还机';
$_LANG['00_MINUS_OUKOO_ERP_supplier_return'] = '-gt流程';
$_LANG['00_MINUS_OUKOO_ERP_supplier_bill'] = '内部结账';
$_LANG['00_MINUS_OUKOO_ERP_physical_inventory'] = '-v盘点';
$_LANG['00_MINUS_OUKOO_ERP_supplier_return_request_list'] = '（-gt）Supplier_return_id查询';
$_LANG['00_MINUS_OUKOO_ERP_sale_order_refund'] = '销售退款';

/* ERP开发管理 */
/*测试数据监控*/
$_LANG['00_TEST_DATA_MONITOR'] = '测试数据监控';
$_LANG['00_OR_MONITOR'] = 'OR对接监控';
$_LANG['00_OR_MONITOR_HEADER'] = 'OR对接监控-header';
$_LANG['00_OR_MONITOR_ORDERS'] = 'OR对接监控-当前订单同步情况';
$_LANG['01_BABYNES_MONITOR_3PL'] = 'Babynes对接监控(3PL)';
$_LANG['02_BEST_INDICATE'] = '中粮对接监控';
$_LANG['03_GYMBOREE_MONITOR'] = '金宝贝对接监控';


/*平台对接数据监控*/
$_LANG['02_PLATFORM_ORDER_MONITOR'] = '平台对接数据监控';
$_LANG['01_BIRD_MONITOR'] = '菜鸟对接监控';
$_LANG['02_TMALL_MONITOR'] = '天猫对接监控';
$_LANG['03_JD_MONITOR'] = '京东对接监控';
$_LANG['04_YHD_MONITOR'] = '一号店对接监控';
$_LANG['05_JM_MONITOR'] = '聚美优品对接监控';
$_LANG['06_VIPSHOP_MONITOR'] = '唯品会对接监控';
$_LANG['07_SUNING_MONITOR'] = '苏宁易购对接监控';
$_LANG['08_MIYA_MONITOR'] = '蜜芽宝贝对接监控';
$_LANG['09_BAIDUMALL_MONITOR'] = '百度MALL对接监控';
$_LANG['10_SFHK_MONITOR'] = '顺丰优选对接监控';
$_LANG['12_COMBI_CRM_MONITOR'] = '康贝订单监控';
$_LANG['13_BUDWEISER_MONITOR'] = '百威礼物社交对接监控';
$_LANG['14_PINDUODUO_MONITOR'] = '拼多多对接监控';
$_LANG['15_KUAJINGGOU_MONITOR'] = '跨境购对接监控';
$_LANG['16_HAIGUAN_MONITOR'] = '申报系统导单监控';


/*微信商城数据监控*/
$_LANG['04_WECHAT_DATE_MONITOR'] = '微信商城数据监控';
$_LANG['01_WYETH_MONITOR'] = '惠氏-齐数微商城数据监控';
$_LANG['02_KDT_MONITOR'] = '有赞微商城数据监控';
$_LANG['03_LEQEE_MONITOR'] = '乐其微商城数据监控';
$_LANG['04_RTM_MONITOR'] = '人头马-蓝门微商城数据监控';
$_LANG['05_NKB_MONITOR'] = '尿裤宝微商城数据监控';
$_LANG['06_HQ_MONITOR'] = '好奇微商城数据监控';







/*新订单详情页*/
$_LANG['00_NEW_ORDER_DETAIL'] = '新订单详情页';
$_LANG['00_NEW_ORDER_DETAIL_ADD_NOTE'] = '添加备注';
$_LANG['00_NEW_ORDER_DETAIL_ORDER_CONFIRM'] = '确认订单';
$_LANG['00_NEW_ORDER_DETAIL_ORDER_CANCEL'] = '取消订单';
$_LANG['00_NEW_ORDER_DETAIL_REC_CONFIRM'] = '收货确认';
$_LANG['00_NEW_ORDER_DETAIL_REC_REJECT'] = '拒收';
$_LANG['00_NEW_ORDER_DETAIL_ORDER_RECOVER'] = '恢复订单';
$_LANG['00_NEW_ORDER_DETAIL_MERGE_ORDER_EDIT'] = '合并/拆分订单';
$_LANG['00_NEW_ORDER_DETAIL_CONSIGNEE_EDIT'] = '修改收货信息';
$_LANG['00_NEW_ORDER_DETAIL_PAYMENT_EDIT'] = '修改支付方式';
$_LANG['00_NEW_ORDER_DETAIL_SHIPPING_EDIT'] = '修改快递方式';
$_LANG['00_NEW_ORDER_DETAIL_FACILITY_EDIT'] = '转仓';
$_LANG['00_NEW_ORDER_DETAIL_GOODS_EDIT'] = '修改订单商品';
/*测试界面管理*/
$_LANG['01_ERP_DEV_01_temp'] = '测试界面管理';
$_LANG['01_in_storage_order_display'] = '入库订单列表';
$_LANG['10_common_grouding_rf_scan'] = '通用上架RF枪扫描(-t,-h,-gh)';
$_LANG['99_common_undercarriage_rf_scan'] = '通用下架RF枪扫描(-gt,-gh)';
$_LANG['99_batch_pick_rf_scan_smarty'] = '批拣RF枪扫描';
$_LANG['99_rf_sickness'] = '病单RF补拣';
$_LANG['99_deal_sickness'] = '病单查询和处理';
/*暗链接管理*/
$_LANG['01_ERP_DEV_02_secret'] = '暗链接管理';
$_LANG['01_ERP_DEV_02_secret_01_toolkit'] = '超级工具';
$_LANG['01_ERP_DEV_02_secret_02_facility'] = '自动出库设置';
$_LANG['01_ERP_DEV_02_secret_03_order_check'] = '订单监控页';
$_LANG['01_ERP_DEV_02_secret_04_bpsn_status_check'] = '批拣单状态监控页';
$_LANG['01_ERP_DEV_02_secret_05_v_inventory_result_search'] = '盘点差异';
$_LANG['01_ERP_DEV_02_secret_06_storage_toolkit'] = '库存管理工具';
$_LANG['02_stock_take_adjust'] = '盘点库存调整';
$_LANG['02_order_batch'] = '批量确定订单';
$_LANG['02_order_batch_double11'] = '【双11专用】订单批量处理';
$_LANG['02_finance_adjustmant'] = '财务调账';
$_LANG['02_prepayment_ajustment'] = '预存款调整';
$_LANG['02_erp_command'] = 'ERP调度执行';
$_LANG['02_cronjob_viewer'] = '当前定时任务配置查看';
/*保税仓混沌监控*/
$_LANG['03_ERP_BWSHOP_NODE'] = '保税仓混沌监控';
$_LANG['03_ERP_BWSHOP_SHOP_AGENT'] = '保税仓店铺管理';
$_LANG['03_ERP_BWSHOP_SIGHT_ORDERS'] = '保税仓实时报表';
$_LANG['03_ERP_BWSHOP_ORDER_AGENT'] = '保税仓订单管理';
$_LANG['03_ERP_BWSHOP_IKENIE_AGENT'] = '保税仓生贄管理';
$_LANG['03_ERP_BWSHOP_ZEIRITSU'] = '保税仓税率管理';
$_LANG['03_ERP_BWSHOP_ISSUE_ORDERS'] = 'E2B转化遗漏搜救';


/*订单管理*/
/* 客服管理 */
$_LANG['01_customer_service'] = '客服管理';
$_LANG['01_order_list'] = '合并订单';
$_LANG['11_order_list_new_order'] = '中粮-合并订单';
$_LANG['02_customer_service_manage_order_paid_unconfirmed'] = '先款后货未确认订单';
$_LANG['03_customer_service_manage_order_cod_unconfirmed'] = '货到付款未确认订单';
$_LANG['04_order_entry'] = '销售订单录入';
$_LANG['05_batch_dis_order'] = '批量录单';
$_LANG['06_invoice_add'] = '添加补寄发票';
$_LANG['07_order_check'] = '录单订单查询';
$_LANG['08_search_user_order_info'] = '历史订单查询';
$_LANG['09_taobao_sales_add'] = '添加淘宝旺旺客服';
$_LANG['10_taobao_consult_import'] = '咨询内容导入';
$_LANG['13_huawang_data_import'] = '花王数据导入';





/*订单售后管理*/
$_LANG['02_after_sale'] = '订单售后管理';
$_LANG['01_change_service'] = '换货申请（-h）';
$_LANG['02_back_service'] = '退货申请（-t）';
$_LANG['06_customer_service_refund_list'] = '退款申请列表';
$_LANG['04_sale_support_center_cached'] = '售后处理中心';
$_LANG['05_sale_support_status'] = '实时售后任务统计';
$_LANG['07_heinz_shipping_code_input'] = '亨氏退换货物流码输入';

/*外包订单管理*/
$_LANG['03_out_shipment'] = '外包订单管理';
$_LANG['01_out_ship_goods'] = '外包发货商品设置';
$_LANG['02_out_ship_order'] = '订单打标';
$_LANG['03_out_ship_pull_tn'] = '面单导入';
$_LANG['03_pinduoduo_ship_pull_tn'] = '拼多多面单导入';
$_LANG['03_wxgrd_ship_pull_tn'] = '万象隔日达面单导入';
$_LANG['04_out_shipment_print'] = '打印面单';
$_LANG['05_out_shipment_recheck'] = '复核';
$_LANG['06_out_receive_rf_scan'] = '外包收货RF枪扫描';
$_LANG['07_out_batch_or_in_storage'] = '外包批次号批量入库';
$_LANG['08_out_back_good'] = '外包退换货收货验货';
$_LANG['09_out_supplier_return_request_inventory'] = '外包供应商退货仓库操作';
$_LANG['10_out_supplier_return_request'] = '外包供应商退货一览';
$_LANG['11_check_out_batch_with_tn'] = '根据快递单号查外包批次';


/*同步订单管理*/
$_LANG['04_order_sync'] = '同步订单管理';
$_LANG['01_taobao_order_list'] = '淘宝订单关系列表';
$_LANG['02_taobao_zhixiao_order_list'] = '淘宝直销订单同步';
$_LANG['03_taobao_fenxiao_order_list'] = '淘宝分销订单同步';
$_LANG['04_jd_order_list'] = '京东订单同步';
$_LANG['05_weixin_order_list'] = '好奇微信订单同步';
$_LANG['06_amazon_order_list'] = 'ecco亚马逊订单同步';
$_LANG['07_yhd_order_list'] = '一号店订单同步';
$_LANG['08_heinz_sync_order'] = '亨氏订单同步设置及商品映射录入';
$_LANG['09_jm_sync_order'] = '聚美优品订单同步';
$_LANG['10_apo_order_list'] = '德国药房订单同步';

/*淘宝外部订单导出*/
$_LANG['05_taobao_outside_shipped_order_export'] = '淘宝外部订单导出';
/*订单转仓管理(新增页面)*/
$_LANG['06_order_facility'] = '订单转仓管理';
/*商品转仓规则管理*/
$_LANG['07_goods_facility_mapping'] = '商品转仓规则管理';
$_LANG['08_auto_confirm_control'] = '自动确认订单设置';
$_LANG['09_claims_settlement'] = '理赔订单管理';
/* 统一的发货同步 */
$_LANG['10_catholic_sync_delivery'] = '统一发货同步管理';
$_LANG['01_catholic_order_mapping_monitor'] = '统一发货同步监控';
$_LANG['02_taobao_split_oid_monitor'] = '淘宝多订单多面单监控';
/*第三方物流订单同步管理*/
$_LANG['11_threeparts_sync_delivery'] = '第三方物流订单同步管理';
$_LANG['01_bwshop_sync_order'] = '保税仓订单同步监控';
$_LANG['02_bird_retro'] = '菜鸟订单同步监控';

/*采购管理*/
$_LANG['01_purchase_provider'] = '供应商管理';
$_LANG['02_generate_c_order'] = '下采购订单（-c）';
$_LANG['03_batch_order_check'] = '采购订单查询';
/*采购发票维护*/
$_LANG['04_purchase_invoice'] = '采购发票维护';
$_LANG['01_purchase_invoice_request_list'] = '开票清单管理';
$_LANG['01_purchase_invoice_request_list_new'] = '新开票清单管理';
$_LANG['02_purchase_invoice_list'] = '采购发票管理';
$_LANG['05_supplier_return_request'] = '供应商退货申请（-gt）';
$_LANG['06_generate_batch_sn_gt'] = '供应商批次号退货申请';
$_LANG['07_supplier_return_request'] = '供应商退货一览';
$_LANG['10_supplier_batch_dt_request'] = '供应商调拨申请';
$_LANG['11_generate_gt_batch_dt'] = '供应商批次号调拨申请';
$_LANG['12_supplier_dt_goods_request_list'] = '供应商调拨一览';
/*(-v)管理*/
$_LANG['08_v_root'] = '（-v）管理';
$_LANG['01_v_apply'] = '(-V)申请';
$_LANG['02_v_apply_batch_sn'] = '批次号-V申请';             //新增
$_LANG['02_stock_take_adjust_batch_sn'] = '批次号-库存调整';             //新增
$_LANG['02_stock_take_adjust_batch_batch_sn'] = '批次号-库存批量调整';  
$_LANG['03_stock_take_adjust'] = '库存调整';
$_LANG['04_stock_take_adjust_batch'] = '库存批量调整';           
$_LANG['05_physical_inventory_apply_order_list'] = '（-v）申请出入库明细';
//$_LANG['09_gt_c'] = '库存调拨管理';                  //库存调拨管理（新增页面）

/*商品管理*/
$_LANG['01_goods_list'] = '商品添加/编辑';
$_LANG['02_goods_tags'] = '商品标签';
$_LANG['03_goods_tags_category'] = '商品标签分类';
$_LANG['04_category_add'] = '商品分类添加';
$_LANG['05_goods_style_import'] = '商品批量导入';
$_LANG['06_goods_identify'] = '商品标识维护';
$_LANG['07_inventory_item_detail'] = '商品出入库明细';
$_LANG['08_taobao_items_list'] = '淘宝直销商品';
$_LANG['09_taobao_fenxiao_items_list'] = '淘宝分销商品';
/*对接管理*/
$_LANG['10_integration']='对接管理-OR/LM/BB';
$_LANG['01_brand_goods'] = '品牌商商品维护';
$_LANG['02_brand_integration_monitor'] = '品牌商对接监控';


/*店铺管理*/
$_LANG['01_taobao_shop_conf'] = '淘宝店铺管理';
$_LANG['02_taobao_erp_goods_manager'] = '店铺商品列表';
$_LANG['03_taobao_statistics'] = '淘宝店铺数据统计';

/*活动管理*/
$_LANG['01_whitelist_gifts_manage'] = '回赠白名单管理';
$_LANG['02_gifts_manage'] = '添加赠品';
$_LANG['02_gift_activity'] = '赠品活动【新】';
$_LANG['03_msg_template_manage'] = '短信模板';
$_LANG['04_msg_send'] = '短信发送';
$_LANG['05_msg_send_batch'] = '短信批量发送';
$_LANG['06_distribution_group_goods'] = '套餐管理';
$_LANG['07_message_send_status'] = '短信发送列表'; 
$_LANG['08_crsms_status'] = 'CRSMS短信监控';


/*库存管理*/
/*库存同步管理*/
$_LANG['01_inventory_sync'] = '库存同步管理';
$_LANG['01_taobao_items_update'] = '淘宝直销库存同步';
$_LANG['02_taobao_fenxiao_items_update'] = '淘宝分销库存同步';
$_LANG['03_jd_items_update'] = '京东库存同步';
$_LANG['04_yhd_items_update'] = '一号店库存同步';
$_LANG['05_bird_budweiser_update'] = '菜鸟库存同步';
$_LANG['06_baiduMall_items_update'] = '百度Mall库存同步';
$_LANG['07_miya_items_update'] = '蜜芽宝贝库存同步';
$_LANG['08_suning_items_update'] = '苏宁库存同步';
$_LANG['09_suning_facility_synchronize'] = '分仓库同步';
/*库存查询*/
$_LANG['02_inventory_check'] = '库存查询';
$_LANG['03_inventory_validity_check'] = '库存有效期查询';
/*库存同步预警页面设置*/
$_LANG['04_inventory_syn_warning'] = '库存同步预警页面设置';
$_LANG['11_zero_inventory'] = '零库存表导出';

/*分销管理*/
$_LANG['01_main_distributor_manage'] = '主分销商维护';
$_LANG['02_distributor_manage'] = '分销店铺维护';
$_LANG['03_distribution_info_manage'] = '分销店铺详情维护';
$_LANG['03_distribution_order_manage'] = '分销订单管理';
$_LANG['04_distribution_order_manage_type'] = '分销未确认订单管理';
$_LANG['05_distribution_sale_price'] = '商品预存款设置';
$_LANG['06_distribution_product_track'] = '串号产品跟踪';
$_LANG['07_distribution_order_adjustment'] = '预存款扣款查询';
$_LANG['08_edu_sale_item'] = '业务销量';

/*运单管理*/
$_LANG['01_waybill_push_to_taobao'] = '直销运单推送到淘宝';
$_LANG['02_purchase_arata_manage'] = '快递热敏面单资源管理';
$_LANG['03_export_tracking_number'] = '导出快递单号';

/*跨境管理*/
$_LANG['01_kuajing_order_manage'] = '订单管理';
$_LANG['01_order_split_import'] = '含税订单拆分';
$_LANG['02_ERP_BWSHOP_ORDER_AGENT'] = 'Bwshop订单列表';
$_LANG['03_ERP_BWSHOP_SIGHT_ORDERS'] = 'Bwshop异常报表';
$_LANG['04_ERP_BWSHOP_SIGHT_INSERT'] = 'Bwshop订单录入';
$_LANG['05_haiguan_order'] = '申报系统订单监控';
$_LANG['06_haiguan_payInfo_import'] = '申报系统交易信息导入';
//$_LANG['07_haiguan_order_import'] ='申报系统订单导入';
$_LANG['08_haiguan_pay'] = '跨境录单支付方式维护';
$_LANG['08_haiguan_order_import'] = '申报系统分销订单录入';
$_LANG['09_declaration_order_check'] = '申报系统订单比例监控';
$_LANG['10_HAIGUAN_SIGHT_ORDERS'] = '申报系统异常报表';
$_LANG['11_HAIGUAN_FENXIO_ORDERS'] = '申报系统分销导单监控';
$_LANG['12_GZ_HAIGUAN_ORDERS'] = '广州海关订单监控';

$_LANG['02_kuajing_product_manage'] = '商品维护';
$_LANG['01_kuajing_items'] = '菜鸟商品维护';
$_LANG['02_haiguan_goods'] = '申报系统商品维护';
$_LANG['03_haiguan_batch'] = '申报系统商品批量录入';
$_LANG['04_ERP_BWSHOP_ZEIRITSU'] = '商品税率维护';
$_LANG['05_gz_haiguan_goods'] = '广州海关商品维护';

$_LANG['03_kuajing_shop_manage'] = '店铺维护';
$_LANG['01_haiguan_shop'] = '申报系统店铺维护';
$_LANG['02_ERP_BWSHOP_SHOP_AGENT'] = 'Bwshop店铺维护';
$_LANG['03_ERP_BWSHOP_IKENIE_AGENT'] = 'Bwshop身份证库';

/*财务管理*/
/*订单管理*/
$_LANG['01_order_manage'] = '订单管理';
$_LANG['01_financeV2'] = '财务收款';
$_LANG['02_batch_payment'] = '批量付款';
$_LANG['03_finance_payment_import'] = '批量收款';
$_LANG['04_finance_payment_import_fenxiao'] = '分销批量收款';
$_LANG['05_query_order_relation'] = '查询销售订单-t-h';
$_LANG['06_query_purchase_supplier_order'] = '查询采购 -gt订单';
$_LANG['08_refund_list_new'] = '退款申请列表';
$_LANG['09_payment_transaction_list'] = '支付交易列表';
$_LANG['10_currency_scale'] = '汇率管理';
/*采购发票管理*/
$_LANG['02_purchase_invoice'] = '采购发票管理';
$_LANG['01_purchase_invoice_request_list'] = '开票清单管理';
$_LANG['01_purchase_invoice_request_list_new'] = '新开票清单管理';
$_LANG['02_purchase_invoice_list'] = '采购发票管理';
$_LANG['03_purchase_uninvoiced_product'] = '未开票商品明细';
$_LANG['04_c2c_buy_sale'] = '内部结账';
/*销售发票管理*/
$_LANG['03_sales_invoice'] = '销售发票管理';
$_LANG['01_sales_invoice_manager'] = '销售发票管理';
$_LANG['02_no_shipping_invoice'] = '直销待开票订单';
$_LANG['03_sales_invoice_request_list'] = '销售发票请求';
$_LANG['04_sales_invoice_list'] = '销售发票列表';
$_LANG['06_print_invoice'] = '打印补寄发票';
$_LANG['07_batch_print_invoice'] = '批量打印补寄发票';
/*预存款管理*/
$_LANG['04_pre_deposit'] = '预存款管理';
$_LANG['01_preparment'] = '预付款管理';
$_LANG['02_distribution_order_adjustment'] = '预付款扣款查询';
$_LANG['03_report'] = '预付款账单';
$_LANG['05_adjustment_total_report'] = '预付款账单汇总';
$_LANG['04_rebeat'] = '返点管理';
$_LANG['06_prepayment_ajustment'] = '预存款调整';
/*快递费管理*/
$_LANG['05_shipping_fee'] = '快递费管理';
$_LANG['01_express_fee_clearing'] = '物流对账结算';
$_LANG['02_freight_details'] = '运费对账明细';
/*查询管理*/
$_LANG['06_finance_search'] = '查询管理';
$_LANG['01_current_inventory_balance_query'] = '实时库存余额查询';


/*权限管理*/
$_LANG['01_party_manage'] = 'PARTY管理';
$_LANG['02_role_manage'] = '角色管理';
$_LANG['03_admin_list'] = '管理员列表';
$_LANG['04_admin_logs'] = '管理员日志';
$_LANG['05_priv_list'] = '权限列表查看';

/*系统设置*/
$_LANG['01_carrier_manage'] = '承运商管理';
$_LANG['02_payment_list'] = '支付方式';
$_LANG['03_shipping_list'] = '配送方式';
$_LANG['04_area_list'] = '地区列表';
$_LANG['05_area_list_hakobiya'] = '物流费用';
$_LANG['06_distribution_shipping'] = '分销快递公司选择';
$_LANG['08_consumable_party_facility'] = '耗材出库仓库设置';
$_LANG['09_party_assign_shipping'] = '组织最优快递设置';
$_LANG['10_distributor_assign_shipping'] = '店铺最优快递设置';
$_LANG['11_database_status'] = '数据库状态';
$_LANG['12_shop_weight_list'] = '店铺快递权重设置';
$_LANG['00_ecshop_config'] = '办公IP设定';

/*店铺供应商维护*/
$_LANG['01_shop_distributor_manage'] = '店铺供应商维护';

/*仓库管理*/
/*入库业务*/
$_LANG['01_menu_in_storage'] = '入库业务';
$_LANG['01_purchase_order_display'] = '显示采购订单';
$_LANG['02_receive_rf_scan'] = '收货RF枪扫描';
$_LANG['03_grouding_rf_scan'] = '上架RF枪扫描';
$_LANG['04_moving_rf_scan'] = '移库RF枪扫描';
$_LANG['05_t_in'] = '销退入库详情';
$_LANG['06_batch_in_storage'] = '批量入库';
$_LANG['07_batch_or_in_storage'] = '批次号批量入库';
$_LANG['08_double_eleven_shelves'] = '上架工具';
$_LANG['09_po_in'] = '收货入库';
$_LANG['10_inventory_location'] = '容器管理';
$_LANG['11_purchase_order_display_new'] = '显示采购订单V2';

/*出库业务*/
$_LANG['02_menu_out_storage'] = '出库业务';
$_LANG['01_batch_picking_list'] = '打印批拣单';
$_LANG['02_batch_picking_list_recommand'] = '打印批拣单(自动推荐)';
$_LANG['03_deal_shipment'] = '打印批拣发货单';
$_LANG['04_deal_carrierbill'] = '打印批拣面单';
$_LANG['05_print_carrier_bill_arata'] = '打印热敏面单';
$_LANG['06_deal_card_print'] = '打印贺卡';
$_LANG['08_shipment_recheck'] = '发货单复核';
$_LANG['09_shipment_batch_recheck'] = '发货单复核(预包装)';
//$_LANG['10_add_order_shipment'] = '追加面单';
$_LANG['11_batch_add_order_shipment_arata_add'] = '追加面单的再次打印';
$_LANG['12_shipment'] = '订单称重发货';
$_LANG['13_weighted_not_shipment'] = '已称重未发货';
$_LANG['14_shipment_limit_party_list'] = '串号商品发货';
$_LANG['15_distribution_purchase_request'] = '提货清单';
$_LANG['16_distribution_delivery'] = '电教发货';
$_LANG['17_issue'] = '订单不称重发货';
$_LANG['18_dcV2'] = '待发货';
$_LANG['19_picking_list_inputted'] = '打印导入拣货单';
$_LANG['20_shipment_pick'] = '配货出库';
$_LANG['21_distribution_dph'] = '待配货';
$_LANG['22_dispatch_edit'] = '订单-发货单编辑';
$_LANG['23_shipment_batch_pick_recheck'] = '批次号复核';
$_LANG['24_add_order_shipment_new'] = '追加普通面单';
$_LANG['25_add_arata_order_shipment_new'] = '追加热敏面单';
$_LANG['26_add_order_shipment_arata_add_new'] = '追加热敏单独打印';
$_LANG['27_facility_staff_info_import'] = '拣货员工信息录入';
$_LANG['28_batch_pick'] = '分配批拣单';
$_LANG['29_batch_pick'] = '绑定码托条码';
$_LANG['30_batch_pick'] = '解绑码托条码';
$_LANG['31_pallet_shipment'] = '码托交接发货';

/*借还机业务*/
$_LANG['03_menu_in_out_storage'] = '借还机业务';
$_LANG['01_h_borrow'] = '借机';
$_LANG['02_h_return'] = '还机';
/*查询业务*/
$_LANG['04_menu_facility_search'] = '查询业务';
$_LANG['01_inventory_check'] = '库存查询';
$_LANG['02_inventory_validity_check'] = '库存有效期查询';
$_LANG['03_purchase'] = '仓库查询';
$_LANG['04_search_batch_pick'] = '批拣单完结与查询';
$_LANG['05_search_sick_batch_pick'] = '问题批拣单查询';
$_LANG['06_search_shipment_bill'] = '发货单查询';
$_LANG['07_inventory_item_detail'] = '查询商品出入库明细';
$_LANG['08_unshipping_order_clean'] = '未发货订单清理';
$_LANG['09_order_batch_pick_checker'] = '订单进入批拣问题诊断';
$_LANG['10_search_pallet_bind'] = '包裹码托查询';
$_LANG['11_shipping_handover'] = '快递交接单查询';
$_LANG['12_search_pallet_bind_new'] = '新包裹码托查询';

/*设备管理*/
$_LANG['05_menu_facility_manage'] = '设备管理';
$_LANG['03_facility_manage'] = '仓库设施管理';
$_LANG['04_office_shipment'] = '办公件管理';
$_LANG['05_print_barcode'] = '条码打印';
$_LANG['06_location_barcodes_xls'] = '库位条码批量打印';
$_LANG['07_print_goods_identify'] = '商品标识打印';
$_LANG['08_facility_location_manage'] = '仓库库位管理';
$_LANG['09_search_product_facility_location'] = '商品库位查询';
$_LANG['10_print_after_scan_code'] = '扫码打印';
/*售后处理*/
$_LANG['06_after_sale'] = '售后处理';
$_LANG['01_th_order'] = '拒收收货';
$_LANG['02_back_good'] = '退换货收货、验货';
$_LANG['08_wl_refund_list'] = '退款申请列表';
$_LANG['04_supplier_return_request_inventory'] = '供应商退货仓库操作';
$_LANG['06_export_bill_no'] = '导出快递交接信息';
$_LANG['07_shipped_cancel'] = '追回货物';
/*库存冻结*/
$_LANG['07_goods_facility_reserved'] = '库存冻结';

/* 统计 */
$_LANG['09_old_report'] = '常用报表(旧)';
$_LANG['01_report_version_2'] = '常用报表';
$_LANG['02_analyze_shipping'] = '发货统计';
$_LANG['03_dianzhangreport'] = '店长报表数据导出';
$_LANG['04_indicate_query'] = '指示查询';
$_LANG['05_wyeth_report'] = '惠氏报表显示';

/*中粮项目*/
/*客服管理*/
$_LANG['01_customer_service'] = '客服管理';
$_LANG['01_zhongliang_order_entry'] = '销售订单录入';
$_LANG['02_zhongliang_batch_order'] = '中粮批量录单';
$_LANG['03_zhongliang_paid_unconfirmed'] = '先款后货未确认订单';
$_LANG['04_zhongliang_back_service'] = '退货申请';
$_LANG['05_zhongliang_dealer_tc'] = '喜宴套餐设置';
/*订单同步信息*/
$_LANG['02_order_sync'] = '订单同步信息';
$_LANG['01_zhongliang_purchase_order_list'] = '中粮采购订单同步';
$_LANG['02_zhongliang_sales_list'] = '中粮销售订单同步';
$_LANG['03_zhongliang_return_order_list'] = '中粮退换货订单同步';
$_LANG['04_zhongliang_b2b_order_list'] = '中粮B2B订单同步';
/*库存同步信息*/
$_LANG['03_inventory_sync'] = '库存同步信息';
$_LANG['01_zhongliang_inventory_compare'] = '中粮同步信息';
$_LANG['02_goods_facility_reserved'] = '库存冻结';
$_LANG['03_zhongliang_inventory_ratio_edit'] = '中粮店铺库存比例设置';
$_LANG['04_zhongliang_b2b_out'] = '中粮B2B出库';
$_LANG['05_zhongliang_b2b_out_list'] = '中粮B2B出库详情';
$_LANG['06_generate_c_order'] = '下入库单';
$_LANG['07_zhongliang_Batch_order'] = '入库详情';
$_LANG['08_zhongliang_inventory_apply'] = '中粮盘点申请';
$_LANG['09_zhongliang_inventory_apply_out'] = '中粮盘点调整';
$_LANG['10_zhongliang_inventory_item'] = '中粮出入库记录';
$_LANG['11_zhongliang_inventory_query'] = '库存查询';

/*金宝贝项目*/
//$_LANG['01_gymboree_order_list'] = '金宝贝订单同步';

/*ecco项目*/
$_LANG['01_print_goods_identify'] = '打印商品标识';
?>
