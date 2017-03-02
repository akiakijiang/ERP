<?php

/**
 * 配置一些变量
 * @author : Zandy <zandyo@gmail.com, yzhang@oukoo.com>
 * @version : 0.0.1
 * $Id$
 */
global $_CFG;
// 邮件编码
$_CFG['mail_charset'] = 'UTF8';

// 订单状态
$_CFG['adminvars']['order_status'] = array(
	0 => '未确认',
	1 => '已确认',
	2 => '作废',
//	3 => '无效',
	4 => '拒收',
//	5 => '完成',
//	6 => '无货',
//	7 => '补货',
	8 => '超卖',
	11 => '外包发货',
	12 => '预定失败',
);

// 付款状态
$_CFG['adminvars']['pay_status'] = array(
	0 => '未付款',
	1 => '付款中',
	2 => '已付款',
	3 => '待退款',
	4 => '已退款',
);

// 发货状态
$_CFG['adminvars']['shipping_status'] = array(
	0 => '待配货',
	1 => '已发货',
	2 => '收货确认',
	3 => '拒收退回',
	4 => '已发往自提点',
	5 => '等待用户自提',
	6 => '已自提',
	7 => '自提取消',
    8 => '已出库/复核，待发货',
    9 => '已配货，待出库',
//    10 => '已配货，但商品改变',
    11 => '已追回',
    12 => '已拣货出库,待复核',
    13 => '批拣中',
    15 => '已确认，未预定',
	16 => '已推送WMS',//一种特殊的待配货状态
);

// 订单与运单对应关系
$_CFG['adminvars']['shipment_type'] = array(
	0 => '1个订单多个运单',
	1 => '1个订单1个运单',
	2 => '1个运单多个订单',
	3 => '多个运单多个订单',
);

// 外部多美滋订单状态
$_CFG['adminvars']['dumex_order_status'] = array(
    '00' => '订单生成',
    '02' => '线索已分配',
    '04' => '联系失败',
    '06' => '订单已取消',
    '08' => '订单已确认',
    '10' => '已发货',
    '12' => '配送成功',
    '14' => '配送失败',
    'NotExist' => '订单不存在',
);

// 抓取快递状态
$_CFG['adminvars']['shipping_data_status'] = array(
    'REJECT' => '返货',
    'SIGNIN' => '签收',
    'ONWAY' => '途中',
    'FAIL' => '抓取失败',
);

// 发票状态
$_CFG['adminvars']['invoice_status'] = array(
	0 => '待确认',
	1 => '待用户修改',
	2 => '用户已修改',
	3 => '已确认',
);

// 商品状态
$_CFG['adminvars']['goods_status'] = array(
	0  => '正常',
	1  => '申请退货',
	2  => '退货审核',
	4  => '退货已审核',
	5  => '退货退回',
	6  => '完成退货',
	7  => '申请换货',
	8  => '换货审核',
	9  => '换货已审核',
	10 => '批准换货',
	11 => '换货退回',
	12 => '换货发出',
	13 => '换货收到',
	14 => '完成换货',
	15 => '申请补货',
	16 => '补货已审核',
	17 => '批准补货',
	18 => '补货发出',
	19 => '补货收到',
	20 => '完成补货',
	21 => '无货',
	22 => '等待',
	23 => '到货',
	24 => '断货',
);

// 供应商上传状态
$_CFG['adminvars']['provider_upload_status'] = array(
	0 => '未处理',
	1 => '处理中',
	2 => '错误格式',
	3 => '待确认',
	4 => '处理完成',
	5 => '已删除',
);

// 供应商种类
$_CFG['adminvars']['provider_type'] = array(
	0 => '制造商',
	1 => '全国代理',
	2 => '省级代理',
	3 => '区域代理',
	4 => '其他',
);

// 供应商上传种类
$_CFG['adminvars']['provider_upload_type'] = array(
	0 => '价格更新',
	1 => '产品更新',
	2 => '整体下架更新',
);

// erp 添加供应商，里面的“主营商品类别”
$_CFG['adminvars']['provider_category'] = array(
	1 => "手机大全",
	119 => "数码相机",
	166 => "数码摄像机",
	179 => "MP3",
	260 => "MP4",
	336 => "车载电子",
	341 => "电玩 电教",
	414 => "笔记本",
	454 => "家用电子",
	1142 => "美容护理",
	837 => "配件",
	2334 => "婚纱礼服及配件",
	2394 => "faucetland",
	
);

// erp 添加供应商，里面的“渠道类型”
$_CFG['adminvars']['provider_type'] = array(
	0 => "制造商",
	1 => "全国代理",
	2 => "省级代理",
	3 => "区域代理",
	4 => "其他",
);


$_CFG['adminvars']['comment_cat'] = array(
//	0 => "其他问题",
	1 => "订单支付",
	2 => "物流配送",
//	3 => "售后服务",
	4 => "订单确认",
    5 => "订单修改",   // modified by zwsun 2008-6-27
    6 => "订单加急",
    7 => "订单未确认反馈",
    8 => "订单未发货反馈",
);

// 采购付款方式
$_CFG['adminvars']['purchase_paid_type'] = array(
//	1 => '银行付款',
	2 => '现金',
//	3 => '网银',
	4 => '支票',
);

// 售后服务类型（客服）
$_CFG['adminvars']['service_type_mapping'] = array(
    1 => '换货',
    2 => '退货',
    5 => '保价',
    6 => "补寄",
//    3 => '保修',
    4 => '售后咨询',
    7 => '返修',
);

// 售后服务处理结果（客服）
$_CFG['adminvars']['status_mapping'] = array(
	0 => "未审核",
//	1 => "审核中",
//	2 => "审核通过",
	3 => "审核未通过",
	4 => "已回复",
	5 => "换货",
	6 => "退货",	
	7 => "保价",	
	8 => "补寄",
);


// 售后问题跟踪状态
$_CFG['adminvars']['oukoo_service_status'] = array(
	1 => '待补偿',
	2 => '待跟踪',
	3 => '待换货',
	4 => '待退货',
	5 => '待退款',
//	6 => '关机',
//	7 => '停机',
	8 => '完成',
//	9 => '无人接听',
	9 => '待补寄',
	10 => '拒绝',
	11 => '审核未通过，待客服回访',
	12 => '客服已回访，审核未通过',
	13 => '验货未通过，待客服回访',
);

// 售后问题跟踪类型
$_CFG['adminvars']['oukoo_service_type'] = array(
//	1 => 'COD不支持',
//	2 => '保价',
	3 => '换货',
//	4 => '拒收',
//	5 => '特殊问题',
	6 => '退货',
//	7 => '无货',
//	8 => '延误',
//	9 => '质量问题',
//	10 => '漏寄',
//	11 => '描述误解',
//	12 => '发错货',
	13 => '补偿',
	14 => '撤单',
	15 => '补寄',
);

//售后沟通类型
$_CFG['adminvars']['support_type'] = array(
    1 => '错发/漏发',
	2 => '破损',
	3 => '未收到货',
	4 => '质量问题',
	5 => '7天无理由退货',
	6 => '未按约定时间/缺货',
	7 => '发票问题',
	8 => '退运费',
	9 => '其他',
);
/**
上面的是最新的售后沟通类型
下面的是老的
by Sinri 2014 05 30 要毕不了业了呜呜呜
**/
/*
array(
    1 => '疑似发错货',
	2 => '运单号无物流信息',
	3 => '疑似质量问题',
	4 => '疑似漏发',
	5 => '疑似快递丢件',
	6 => '追件',
	7 => '物流组收到件后，反馈信息',
	8 => '其它类型',
);
*/

// 售后问题跟踪原因
$_CFG['adminvars']['oukoo_service_reason'] = array(
	1 => 'COD不支持',
	2 => '保价',
//	3 => '换货',
	4 => '拒收',
//	5 => '特殊问题',
//	6 => '退货',
	7 => '无货',
	8 => '延误',
	9 => '质量问题',
	10 => '漏寄',
	11 => '描述误解',
	12 => '发错货',
	13 => '无货延迟',
	14 => '手机无货取消',
	15 => '配件无货取消'
);

/* 红包配置状态 */
$_CFG['ms']['gtc_state'] = array(
	1 => '禁用',
	2 => '有时间限制启用',
	3 => '无时间限制启用',
);

/* 红包的使用状态 */
$_CFG['ms']['gt_state'] = array(
	1 => '禁用',
	2 => '有时间限制启用',
	3 => '无时间限制启用',
	4 => '已经使用',
);

define('UPLOAD_DIR', '/var/oukoo/data/provider_uploaded/');
define('PROVIDER_UPLOAD_PRICE', 0);
define('PROVIDER_UPLOAD_PRODUCT', 1);
define('PROVIDER_UPLOAD_WHOLE', 2);


//////// 新的售后状态

//退款原因
define('BACK_AMOUNT_CANCEL_ORDER', 1);
define('BACK_AMOUNT_REFUSE_ORDER', 2);
define('BACK_AMOUNT_BACK_ORDER', 3);
define('BACK_AMOUNT_PRICE_PROTECT', 4);
$_CFG['adminvars']['back_amount_reason_mapping'] = array(
  BACK_AMOUNT_CANCEL_ORDER   => '取消订单退款',
  BACK_AMOUNT_REFUSE_ORDER   => '拒收货物退款',
  BACK_AMOUNT_BACK_ORDER     => '退货退款',
  BACK_AMOUNT_PRICE_PROTECT  => '价格保护退款',
);

global $back_amount_reason_mapping;
$back_amount_reason_mapping = $_CFG['adminvars']['back_amount_reason_mapping'];

//售后服务的类型
define('SERVICE_TYPE_CHANGE', 1);
define('SERVICE_TYPE_BACK', 2);
define('SERVICE_TYPE_PRICE_PROTECTED', 5);
define('SERVICE_TYPE_RE_SEND', 6);
define('SERVICE_TYPE_WARRANTY', 7);

//售后服务映射
$_CFG['adminvars']['service_type_mapping'] = array(
    SERVICE_TYPE_CHANGE          => '换货申请',
    SERVICE_TYPE_BACK            => '退货申请',
    SERVICE_TYPE_PRICE_PROTECTED => '保价申请',
    SERVICE_TYPE_RE_SEND         => '漏寄申请',
    SERVICE_TYPE_WARRANTY        => '返修申请',
);

global $service_type_mapping;
$service_type_mapping = $_CFG['adminvars']['service_type_mapping'];

//退货状态
$_CFG['adminvars']['back_shipping_status_mapping'] = array(    // 合并收货、验货流程后，保留此状态，意义：在退换货验货入库之前设置此标志位，方便入库不成功时的原因查找
  12 => "货已收到,待验货",
);

global $back_shipping_status_mapping;
$back_shipping_status_mapping = $_CFG['adminvars']['back_shipping_status_mapping'];

// 退货换操作记录状态
$_CFG['adminvars']['back_action_type_mapping'] = array( 
  5 => " 等待消费者寄回货物",
);

global $back_action_type_mapping;
$back_action_type_mapping = $_CFG['adminvars']['back_action_type_mapping'];


//检测状态 要么是检测，要么是验货
$_CFG['adminvars']['outer_check_status_mapping'] = array(
  21 => "货物待检测",
  22 => "货物检测中",
  23 => "检测完成,有质量问题",
  24 => "检测完成,无质量问题",  
);
global $outer_check_status_mapping;
$outer_check_status_mapping = $_CFG['adminvars']['outer_check_status_mapping'];

//验货状态 要么是检测，要么是验货
$_CFG['adminvars']['inner_check_status_mapping'] = array(
  32 => "验货通过",
  33 => "验货未通过",
  31 =>'验货失败', //仅用于仓库登记方式发起退货申请，操作入库失败 状态
);
global $inner_check_status_mapping;
$inner_check_status_mapping = $_CFG['adminvars']['inner_check_status_mapping'];

//换货状态 或者原样寄回的
$_CFG['adminvars']['change_shipping_status_mapping'] = array(
  
  42 => "已配货,待出库",
  43 => "已出库,待发货",
  44 => "已发货,待签收", 
  45 => "已签收",
  
  //原样寄回的
  52 => "退回货物已经寄走,待用户签收",
  53 => "退回货物用户已签收,换货申请结束",
  
  //漏寄的物流状态
  62 => "漏寄货物已寄走,待签收",
  63 => "漏寄货物用户已签收,漏寄申请结束",
);
global $change_shipping_status_mapping;
$change_shipping_status_mapping = $_CFG['adminvars']['change_shipping_status_mapping'];

//返修付款状态
$_CFG['adminvars']['warranty_pay_status_mapping'] = array(
  2 => "待用户付费维修",
  4 => "维修款项已收到,待维修",
);
global $warranty_pay_status_mapping;
$warranty_pay_status_mapping = $_CFG['adminvars']['warranty_pay_status_mapping'];
//返修送检状态
$_CFG['adminvars']['warranty_check_status_mapping'] = array(
  21 => "货物待送修",
  22 => "货物送修中",
  23 => "维修费用待确认",
  24 => "货物待维修",
  25 => "货物维修中",
  26 => "货物保修中",
  27 => "送修完成,已修复",
  28 => "送修完成,未修复",  
);
global $warranty_check_status_mapping;
$warranty_check_status_mapping = $_CFG['adminvars']['warranty_check_status_mapping'];
//返修快递状态
$_CFG['adminvars']['warranty_shipping_status_mapping'] = array(
  44 => "已发货,待签收", 
  45 => "已签收",
);
global $warranty_shipping_status_mapping;
$warranty_shipping_status_mapping = $_CFG['adminvars']['warranty_shipping_status_mapping'];

//售后服务审核状态
define('SERVICE_STATUS_PENDING', 0);
define('SERVICE_STATUS_REVIEWING', 1);
define('SERVICE_STATUS_OK', 2);
define('SERVICE_STATUS_DENIED', 3);

//售后服务审核状态映射
$_CFG['adminvars']['service_status_mapping'] = array(
	SERVICE_STATUS_PENDING => "待审核",
	SERVICE_STATUS_REVIEWING => "已审核",
	SERVICE_STATUS_DENIED=> "审核未通过",
	SERVICE_STATUS_OK => "审核通过", 
);
global $service_status_mapping;
$service_status_mapping = $_CFG['adminvars']['service_status_mapping'];

//售后回访状态
define('SERVICE_CALL_STATUS_NEEDCALL', 1);
define('SERVICE_CALL_STATUS_CALLED', 2);

//售后回访状态映射
$_CFG['adminvars']['service_call_status_mapping'] = array(
  SERVICE_CALL_STATUS_NEEDCALL => "待回访",
  SERVICE_CALL_STATUS_CALLED => "已回访",
);
global $service_call_status_mapping;
$service_call_status_mapping = $_CFG['adminvars']['service_call_status_mapping'];

//退款状态映射
$_CFG['adminvars']['service_pay_status_mapping'] = array(
  2 => "已退款,待用户确认",
  4 => "用户确认收款,申请结束",
);
global $service_pay_status_mapping;
$service_pay_status_mapping = $_CFG['adminvars']['service_pay_status_mapping'];

$_CFG['adminvars']['service_return_key_mapping'] = array(
  'account_number'=>'开户帐号',
  'open_bank'=>'开户行',
  'account_name'=>'开户名', 
  'account_province'=>'所在省',
  'account_city'=>'所在市',
  'alipay_account'=>'支付宝帐号',
  'tenpay_account'=>'财付通帐号', 

  'deliver_company'=>'快递公司',
  'deliver_number'=>'快递单号',
  'deliver_fee'=>'快递费用',
);

global $service_return_key_mapping;
$service_return_key_mapping = $_CFG['adminvars']['service_return_key_mapping'];
//
///新的售后状态结束


// 采购发票状态
$_CFG['adminvars']['purchase_invoice_status_mapping'] = array(
	'INIT' => '未审核',
	'CONFIRM' => '已审核',
	'CLOSE' => '已复审',
);

$_CFG['adminvars']['purchase_invoice_request_status_mapping'] = array(
	'INIT' => '未确认',
	'CONFIRM' => '已确认',
	'CLOSE' => '已关闭',
	'CANCEL' => '已撤销',
);
$_CFG['adminvars']['purchase_invoice_request_type_mapping'] = array(
	'AVERAGE' => '平均价格',
	'ORIGINAL' => '原始价格',
);

// 商品销售状态
$_CFG['adminvars']['goods_sale_status'] = array(
	'normal'       =>      '在售',
	'shortage'     =>      '缺货',
	'tosale'       =>      '即将上市',
	'withdrawn'    =>      '下市',
);
// 商品库存状态
$_CFG['adminvars']['goods_storage_status'] = array(
	'NEW'          =>      '新品',
	'SECOND_HAND'  =>      '二手',
	'DISCARD'      =>      '报废',
	'NONE'         =>      '无',
	''             =>      '无',
);


global $rma_track_attribute_type;
//售后跟踪属性的类型 VALUE，TEXT，CHECK，CHOICE，
$rma_track_attribute_type = array(
    array('NOISE', '机头', 'CHOICE', '未封,已封'),
    array('BACK_CAP', '后盖', 'VALUE', ''),
    array('PEN', '手写笔', 'VALUE', ''),
    array('INVOICE', '发票', 'CHOICE', 'RECEIVED,NOT_RECEIVED'),
    array('INVOICE_NO', '发票号码', 'TEXT', ''),
    array('WARRANTY_CARD', '保修卡', 'CHOICE', 'RECEIVED,NOT_RECEIVED'),
    array('WARRANTY_TAB', '保修标签', 'CHECK', 'A,B,C,D,E'),
    array('CARD_SOCKET', '卡套', 'VALUE', ''),
    array('MEMORY_CARD1_NAME', '存储卡1', 'TEXT', ''),
    array('MEMORY_CARD1_CAP', '存储卡1', 'VALUE', ''),
    array('MEMORY_CARD1_NUMBER', '存储卡1', 'VALUE', ''),
    
    array('MEMORY_CARD2_NAME', '存储卡1', 'TEXT', ''),
    array('MEMORY_CARD2_CAP', '存储卡1', 'VALUE', ''),
    array('MEMORY_CARD2_NUMBER', '存储卡1', 'VALUE', ''),
    
    array('BATTERY1_CAP', '电池容量', 'VALUE', ''),
    array('BATTERY1_NUMBER', '电池个数', 'VALUE', ''),
    array('COPY_OF_ID_CARD', '身份证复印件', 'CHOICE', 'RECEIVED,NOT_RECEIVED'),
    array('INSPECTION_FORM', '检测单类型', 'CHOICE', 'DAP,NO_DAP'),
    array('INSPECTION_FORM_PAGE', '检测单联', 'CHOICE', 'RECEIVED,NOT_RECEIVED'),
    array('STAND', '车载支架', 'VALUE', ''),
    array('OUTER_PACK', '外包装', 'CHOICE', 'OK,DAMAGED'),
    array('INNER_PACK', '内包装', 'CHOICE', 'OK,DAMAGED'),
    array('MATERIALS_MISSED', '缺失物品', 'CHOICE', 'Y,N'),
    array('MATERIALS_RECEIVED', '包含的物品', 'CHECK', '光盘,旅充,座充,车充,耳机,线控,说明书,蓝牙耳机,数据线,皮套'),
    array('OTHER_MATERIAL', '其他物品', 'TEXT', ''),   
    array('ALL_RETURNED', '退回标配齐全', 'CHOICE', 'Y,N'),
    
);

// {{{ membership tables //die("是时候封印这群妖怪了。铲平忌术部宣。(涉及到古老的欧币系统，已被停用。)");
// define('DB_TBL_ADM_ACCESS',            'membership.adm_access');            # 
// define('DB_TBL_ADM_ACCESS_PCA',        'membership.adm_access_pca');        # 
// define('DB_TBL_ADM_ACCOUNT',           'membership.adm_account');           # 权限分配表
// define('DB_TBL_OK_CURRENCY_LOG',       'membership.ok_currency_log');       # 欧币券使用生成表
// define('DB_TBL_OK_CURRENCY_SYSTEM',    'membership.ok_currency_system');    # 欧币券配制表
// define('DB_TBL_OK_GIFT_TICKET',        'membership.ok_gift_ticket');        # 红包
// define('DB_TBL_OK_GIFT_TICKET_CONFIG', 'membership.ok_gift_ticket_config'); # 红包配置表           对发放的红包整体进行控制
// define('DB_TBL_OK_GIFT_TICKET_LOG',    'membership.ok_gift_ticket_log');    # 红包使用日志
// define('DB_TBL_OK_POINT_EXCHANGE_LOG', 'membership.ok_point_exchange_log'); # 欧币券兑换欧币日志
// define('DB_TBL_OK_POINT_LOG',          'membership.ok_point_log');          # 欧币使用表
// define('DB_TBL_OK_SITE',               'membership.ok_site');               # 合作网站
// define('DB_TBL_OK_USER',               'membership.ok_user');               # 欧币用户表
// define('DB_TBL_OK_USER_RANK',          'membership.ok_user_rank');          # 用户等级  对ecshop的用户等级进行补充
// define('DB_TBL_XX_SHOP_USER_RANK',     'membership.xx_shop_user_rank');     # 用户等级  对ecshop的用户等级进行补充
// }}}


// 组织定义
// 定义了PARTY常量和ID的对应关系
define('PARTY_OUKU_MOBILE',  1);  // 欧酷手机业务
define('PARTY_OUKU_SHOES',   4);  // 欧酷鞋子业务
define('PARTY_LEQEE_MOBILE', 8);  // 乐其手机业务
define('PARTY_LEQEE_EDU',   16);  // 乐其电教业务
define('PARTY_LEQEE_BAG',   32);  // 乐其箱包
define('PARTY_LEQEE_DVD',   64);   // 乐其DVD
define('PARTY_EB_HUAIXUAN', 128); // 怀轩
define('PARTY_OUKU',        5);   // 欧酷
define('PARTY_LEQEE',     120);  // 乐其
define('PARTY_EB_PLATFORM', 32640); // 电商平台
define('PARTY_JJSHOUSE', 65545); // JJsHouse海外业务
define('PARTY_DRAGONFLY', 65543); // JJsHouse海外业务
define('PARTY_TYSP', 65595); // 通用商品组织
define('PARTY_ALL',     65535);  // 所有组织  

// 定义仓库
define('FACILITY_TYSP_SH', 83077348); // 通用商品上海仓
define('FACILITY_TYSP_DG', 83077349); // 通用商品东莞仓
define('FACILITY_TYSP_BJ', 83077350); // 通用商品北京仓
define('FACILITY_TYSP_CD', 137059431); // 通用商品成都仓
define('FACILITY_TYSP_JXSG', 193328443); // 通用商品嘉兴水果仓
define('FACILITY_TYSP_SHSG', 193328444); // 通用商品上海水果仓
define('FACILITY_TYSP_SZSG', 193328445); // 通用商品苏州水果仓
define('FACILITY_TYSP_CDSG', 193328446); // 通用商品成都水果仓
define('FACILITY_TYSP_WHSG', 193328447); // 通用商品武汉水果仓
define('FACILITY_TYSP_BJSG', 193328448); // 通用商品北京水果仓
define('FACILITY_TYSP_SHZSG', 193328449); // 通用商品深圳水果仓

//ERP颜色
define('PARTY_COLOR', FALSE);
define("BORDER_COLOR", "#483D8B");
define("PADDING_COLOR", "#7B68EE");

$_CFG['adminvars']['inventory_transaction_type_id'] = array(
    'ITT_INIT'            =>  '初始化库存',
    'ITT_SALE'            =>  '销售出库',
    'ITT_INSPECT'         =>  '采购商品入检测库',
    'ITT_PURCHASE'        =>  '采购入库',
    'ITT_SO_RET'          =>  '销售订单退回（售后服务-t订单）',
    'ITT_SO_REJECT'       =>  '销售订单拒收（拒收生成-t订单）',
    'ITT_SO_CANCEL'       =>  '销售订单取消入库（取消追回订单）',
    'ITT_SO_CHANGE'       =>  '销售订单调整入库'.
        '（用于配货后，发货前更改串号或者客服删除商品时商品自动入库）',
    'ITT_SO_UNKNOWN'      =>  '莫名入库',
    'ITT_RMA_DENIED'      =>  '销售退换货拒绝',
    'ITT_CHECK'           =>  '检查商品（内部或者外部）'. 
        '（用于：1 跟供应商换货退货时，先到次品库；2 RMA库到RMA检测中心，正式库二手库等）',
    'ITT_CHECK_RET'       =>  '商品从检测中心送回（用于RMA检测中心到RMA库）',
    'ITT_PO_CHANGE'       =>  '采购订单调整出库（目前没有用）',
    'ITT_PO_RET'          =>  '采购订单退回出库（-gt订单出库）',
    'ITT_PO_RET_REJECT'   =>  '采购订单退回遭拒绝（目前还未用的）',
    'ITT_PO_EXCHANGE_OUT' =>  '采购换货订单出库 （目前的-gh订单中的供应商换货出库）',
    'ITT_PO_EXCHANGE_IN'  =>  '采购换货订单入库 （目前的-gh订单中的供应商换货入库）',
    'ITT_PO_CANCEL'       =>  '采购订单取消出库 （用于废除订单）',
    'ITT_BORROW'          =>  '内部借用出库',
    'ITT_BORROW_RET'      =>  '内部借用入库',
    'ITT_MISC_IN'         =>  '杂收（目前只用于采购更改串号之用）',
    'ITT_MISC_OUT'        =>  '杂发(目前只用于采购更改串号之用)',
    'ITT_VIRANCE_ADD'     =>  '盘盈',
    'ITT_VIRANCE_MINUS'   =>  '盘亏',
);
$_CFG['adminvars']['order_type_transaction_type_map_out'] = array(
 	'RMA_RETURN' 		=> 'ITT_SO_RET',
 	'RMA_EXCHANGE' 		=> 'ITT_SO_RET',
 	'SUPPLIER_EXCHANGE' => 'ITT_PO_EXCHANGE_OUT',
 	'SUPPLIER_RETURN' 	=> 'ITT_PO_RET',
 	'BORROW' 			=> 'ITT_BORROW',
);
$_CFG['adminvars']['order_type_id'] = array(
 	'BORROW' 			=> '-gh 借机',
 	'PURCHASE' 			=> '-c 采购',
 	'RMA_EXCHANGE' 		=> '-h 换货',
 	'RMA_RETURN' 		=> '-t 退货',
 	'SALE' 				=> '销售',
 	'SHIP_ONLY' 		=> '-b 补寄',
 	'SUPPLIER_EXCHANGE' => '-gh 供应商换货',
 	'SUPPLIER_RETURN' 	=> '-gt 供应商退货',
 	'SUPPLIER_SALE' 	=> '-gt 销售',
 	'VARIANCE_ADD' 		=> '-v 盘盈',
 	'VARIANCE_MINUS' 	=> '-v 盘亏',
 	'SUPPLIER_TRANSFER' => '调拨出库',
 	'PURCHASE_TRANSFER' => '调拨入库',
);
$_CFG['adminvars']['order_type_transaction_type_map_in'] = array(
 	'RMA_EXCHANGE' 		=> 'ITT_SO_CHANGE',
 	'SUPPLIER_EXCHANGE' => 'ITT_PO_EXCHANGE_IN',
 	'BORROW' 			=> 'ITT_BORROW_RET',
);

$_CFG['adminvars']['inventory_status_id'] = array(
    'INV_STTS_INSPECT'            =>  '检测库',
    'INV_STTS_AVAILABLE'          =>  '正式库',
    'INV_STTS_DELIVER'            =>  '发货库',
    'INV_STTS_RETURNED'           =>  'RMA库',
    'INV_STTS_RETURNED_DELIVER'   =>  'RMA发货库',
    'INV_STTS_DEFECTIVE'          =>  '次品库',
    'INV_STTS_USED'               =>  '二手库',
);


$_CFG['adminvars']['order_action_note_type'] = array(
    '' => '无',
    'SHIPPING' => '配送',
);

$_CFG['adminvars']['outer_type'] = array(
    'taobao'            => '淘宝',
    '360buy'            => '京东',
    '360buy_overseas'	=> '京东全球购',
    'weixin'            => '微信',
    'amazon'            => '亚马逊',
    'baidumall'			=> '百度Mall',
    'ChinaMobile'		=> '移动积分商城',
    'jumei'				=> '聚美',
    'koudaitong'		=> '口袋通',
    'miya'				=> '蜜芽宝贝',
    'scn'				=> '名鞋库',//似乎已经不做了
    'sfhk'				=> '顺丰优选',
    'suning'			=> '苏宁',
    'vipshop'			=> '唯品会',
    'weigou'			=> '微购',
    'weixin'			=> '微信商城',
    'weixinqs'			=> '微信奇数',
    'weixinjf'			=> '微信积分商城',
    'yhd'				=> '一号店',
    'pinduoduo'         => '拼多多',
    'budweiser'         => '百威礼物社交',
    'kaola'				=> '网易考拉',
	'other'				=> '其他',
);
//分销类型
$_CFG['adminvars']['distribution_type'] = array(
    'fenxiao'           => '分销',
    'zhixiao'           => '直销',
);

$_CFG['adminvars']['sub_outer_type'] = array(
    'taobao'    =>
        array(
            'zhuang_tb1@163.com'        => '惠普笔记本专供店', // taobao
            'jmhu@ouku.com'             => '欧酷数码专营店(淘宝)', // taobao
            'erpchenlei@163.com'        => 'lchen1979', // taobao
            'huaixuan_tb@163.com'       => '怀轩名品专营店', // taobao
            ),
);
//shipment 	SHIPPING_CATEGORY 
$_CFG['adminvars']['shipping_category'] = array(
    'SHIPPING_SEND' => '正常补寄的运单',
    'SHIPPING_RETURN' => '返区件的运单',
    'SHIPPING_INVOICE' => '补寄发票的运单',
);

//编辑商品信息中状态
$_CFG['adminvars']['taobao_goods_status'] = array(
    'OK'     =>  '同步',
    'STOP'   =>  '不同步',
    'DELETE' =>  '删除' ,
);

//订单面单对应关系
define('ORDER_SHIPMENT_ONE_TO_ONE', 1);
define('ORDER_SHIPMENT_ONE_TO_MANY', 2);
define('ORDER_SHIPMENT_MANY_TO_ONE', 3);
define('ORDER_SHIPMENT_MANY_TO_MANY', 4);
$_CFG['adminvars']['order_shipment_mapping'] = array(
  ORDER_SHIPMENT_ONE_TO_ONE   => '1:1',
  ORDER_SHIPMENT_ONE_TO_MANY   => '1:N',
  ORDER_SHIPMENT_MANY_TO_ONE     => 'N:1',
  ORDER_SHIPMENT_MANY_TO_MANY  => 'M:N',
);
global $order_shipment_mapping;
$order_shipment_mapping = $_CFG['adminvars']['order_shipment_mapping'];

//入库模式
$_CFG['adminvars']['in_storage_mode'] = array(
    0   =>  '新流程扫描枪收货入库',
    1   =>  '老流程收货入库',
    2   =>  '新流程批量入库',
    3   =>  '新流程批次号批量入库',
);

//理赔责任人
$_CFG['adminvars']['responsible_party'] = array(
	'WZTK' => '无责退款',
	'KD' => '快递',
	'PPS' => '品牌商',
	'YY' => '运营',
	'KF' => '客服',
	'CK' => '仓库',
	'CW' => '财务',
	'ERP' => 'ERP',
	'YWZ' => '业务组',
	'XXPF' => '先行赔付',
	'HZCN' => '杭州菜鸟',
	'NBZZ' => '宁波正正',
	'SHCN' => '上海菜鸟',
	'BS' => '百世',
	'SF' => '三方',
	'JLBDC' => '嘉里保达仓',
	'JLCN' => '嘉里菜鸟',
	'BSCN' => '百世菜鸟',
	'HQ400' => '好奇400',
	'CNJH' => '菜鸟集货',
	'NBBD' => '宁波保达',
	'XGZWY' => '香港中外运',
	'GYL' => '供应链',
	'WBC' => '外包仓'
);

$_CFG['adminvars']['after_sales_type_list'] = array(
    '1' => '无责破损(外包装完好/本人签收的内物破损)',
    '2' => '无责漏发(外包装完好/本人签收仓库核实无果的漏发)',
    '3' => '无责错发(外包装完好/本人签收仓库核实无果的错发)',
    '4' => '正常退款(未发货退款  正常的退货退款等)',
    '5' => '恶意售后(顾客恶意申请退款、恶意威胁)',
    '6' => '退差价(活动差价或优惠券差价、好评返现、半价活动、免单)',
    '7' => '商品问题(顾客认为是质量问题/描述不符，品牌商不予承担)',
    '8' => '质量问题(顾客对商品质量提出质疑或明显的质量问题，核实过后定为品牌商承担)',
    '9' => '原单退回(原单退回破损，但快递和仓库不承认)',
    '10' => '顾客退货(顾客退货和仓库收到实物不符，必须以顾客的为准)',
    '11' => '液体商品(液体商品破损快递不赔/液体破损直接弃件)',
    '12' => '急速退款(急速退款，顾客填写订单号无效，时间将至，联系无果)',
    '13' => '投诉举报(工商投诉赔款、举报处理)',
    '14' => '特殊业务(品牌商故意或者失误导致的售后)',
    '15' => '责任明确(已经明确责任人，以定责的选项为准)',
    '16' => '其他平台(由于平台/仓库产生的售后)'    
);


$_CFG['adminvars']['declaration_facility'] = array(
    '1' => '宁波保达电子商务有限公司',
    '2' => '宁波保税区嘉里大通物流有限公司',
    '3' => '百世物流科技（宁波保税区）有限公司'
);

//申报系统支付方式
$_CFG['adminvars']['kjg_payment'] = array(
    '01' => '中国银联',
    '02' => '支付宝',
    '03' => '盛付通',
    '04' => '建设银行',
    '05' => '中国银行',
    '06' => '易付宝',
    '07' => '农业银行',
    '08' => '京东网银在线',
    '09' => '国际支付宝',
    '10' => '甬易支付',
    '11' => '富友支付',
    '12' => '京东网银在线',
    '13' => '财付通',
    '14' => '快钱',
    '15' => '网易宝',
    '16' => '微支付'
);

?>
