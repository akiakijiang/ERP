<?php

/**
 * $Id: master_config.php 104975 2016-06-22 02:24:52Z ljni $
 * 
 * @modby Zandy 2007-10-17
 */

/*OUKOO图片路径*/
define('ImagePath','http://img.ouku.com/imgs/');
// 定义论坛路径
define('BBSPath','http://localbbs.ouku.com/');
define('MESSAGE_URL','http://sdk4report.eucp.b2m.cn:8080/sdk/SDKService');
define('MESSAGE_SERIALNUMBER','6SDK-EMY-6688-JCYOL');
define('MESSAGE_PASSWORD','251248');
define('MESSAGE_SESSIONKEY','654256');
$application_key = array(
    '1' => "5b4b488ae337e982205fb0fec6034089",
    '8' => "c892ba238c98835d4d53a3faed43ee52",
    '16' => "f2c6d0dacf32102aa822001d0907b75a",
    '64' => "fd42e8aeb24b4b9295b32055391e9dd2",
    '128' => "d1ac25f28f324361a9a1ea634d52dfc0",
    '65538' => "56d31a317f3148fab96c10d4762e1efe",
    '65539' => "239133b81b0b4f0ca086fba086fec6d5",
    '65540' => "11b038f042054e27bbb427dfce973307",
    '65546' => "ee0daa3431074905faf68cddf9869895",
    '65547' => "ee6a834daa61d3a7d8c7011e482d3de5",
    '65548' => "7f83e72fde61caba008bad0d21234104",
    '65550' => "fba27c5113229aa0062b826c998796c6",
    '65552' => "f38958a9b99df8f806646dc393fdaff4",
    '65553' => "62f6bb9e07d14157b8fa75824400981f",
    '65555' => "753980cc6efb478f8ee22a0ff1113538",
    '65557' => "589e7a67c0f94fb686a9287aaa9107db",
    '65558' => "f1cfc3f7859f47fa8e7c150c2be35bfc",
    '65559' => "9f6ca417106894739e99ebcbf511e82f",
    '65562' => "dccd25640ed712229d50e48f2170f7fd",
    '65568' => "6ecd27fb75354272ba07f08a2507fa40",
    '65569' => "85b1cf4b507b497e844c639733788480",
    '65571' => "7626299ed42c46b0b2ef44a68083d49a", 
);  

//187的数据库

//2.2
$d22 = 0;
$jqzhang = 0;
$wamp = 0;
$d28 = 0;
$rds=1;
if($d22){
	// database host
	$db_host   = "127.0.0.1:33306";
	// database name
	$db_name   = "ecshop";
	// database username
	$db_user   = "dev";
	// database password
	$db_pass   = "wJSdQVNvS6PqN8wG";
}else if($jqzhang){
    //wamp
    $db_host   = "172.16.0.5:3306";
    $db_name   = "ecshop";
    $db_user   = "root";
    $db_pass   = "123456";
}else if($wamp){
    //wamp
    $db_host   = "127.0.0.1:3306";
    $db_name   = "ecshop";
    $db_user   = "root";
    $db_pass   = "";
}else if($d28){
	$db_host   = "127.0.0.1:32224";
	$db_name   = "ecshop";
	$db_user   = "dev";
	$db_pass   = "dev";
}
elseif($rds){
    $db_host   = "rdsjjw98q0455p35wi06o.mysql.rds.aliyuncs.com:3306";
    $db_name   = "ecshop";
    $db_user   = "kaihatsu";
    $db_pass   = "LzRgCvBwtnBDni35";
}
else{
	pp('no database');
}



$slave_db_host = $db_host;
$slave_db_name = $db_name;
$slave_db_user = $db_user;
$slave_db_pass = $db_pass;


////187的数据库
//$db187_host = "localhost:3306";
//$db187_name = "ecshop";
//$db187_user = "root";
//$db187_pass = "123456";
//
//// database host
//$db_host   = "localhost:3306";
//// database name
//$db_name   = "ecshop";
//// database username
//$db_user   = "root";
//// database password
//$db_pass   = "123456";
//
//

//手机频道配置 add by taofei
//// database host
$mc_db_host   = "localhost:3306";
//// database name
$mc_db_name   = "CP_PDAFANS_RESOURCE";
$crawler_db_name   = "CRAWLER";
//// database username
$mc_db_user   = "ecshop";
//// database password
$mc_db_pass   = "ecshopMySQL";

//bdfclub restful API
$restful_bdf_order_api = "http://www.bdfwechat.com/api/rest_test/order_list";
$restful_bdf_ordergoods_api = "http://www.bdfwechat.com/api/rest_test/order_goods";

// BBSAPI
$bbsapi = "http://192.168.1.3/bbs/rpc.php";

// COOKIE_DOMAIN
$COOKIE_DOMAIN = "";

// membership rpc host
$membership_rpc_host = "192.168.1.3";
$membership_rpc_path = "/RpcService.php";
$membership_rpc_port = "81";

// biaoju rpc host
$biaoju_rpc_host = "localhost";
$biaoju_rpc_path = "/rpc/jrpc";
$biaoju_rpc_port = "8080";

// sso rpc host
$sso_rpc_host = 'localhost';
$sso_rpc_path = '/rpc/jrpc';
$sso_rpc_port = '8080';

// payment rpc host
$payment_rpc_host = 'localhost';
$payment_rpc_path = '/rpc/jrpc';
$payment_rpc_port = '8080';

// payment rpc host
$search_rpc_host = 'localhost';
$search_rpc_path = '/rpc/jrpc';
$search_rpc_port = '8080';

//多美滋订单同步
define('DUMEX_SYNC_URL','http://221.133.247.221:8070/CustomServices/Order/QSOrderManagement.svc?wsdl');

// 指定一个时间，在此时间前的订单除了指定人以外，其他人无法修改订单数据
$erp_admin = array("pgu", "liangliang");
$erp_time = "2007/05/20";

//$kefu_email = "kefu@oukoo.com";
$kefu_email = "";

//是否显示mysql错误信息 
define('SHOW_MYSQL_ERROR', true);

//发送短信带短信回复
//define('MESSAGE_URL','http://sdkhttp.eucp.b2m.cn/sdk/SDKService');
//define('MESSAGE_SERIALNUMBER','3SDK-EHF-0130-MFTQT');
//define('MESSAGE_PASSWORD','841613');
//define('MESSAGE_SESSIONKEY','621163');


// message 发短信
$is_message = false;
$message_rpc_host = "localhost";
$message_rpc_path = "/rpc/jrpc";
$message_rpc_port = "8080";
//$application_key = "5b4b488ae337e982205fb0fec6034089";
/**
 * 1:erp
 * 2:oppo
 */
/**
 * $application_key = array(
	'1' => "5b4b488ae337e982205fb0fec6034089",
	'8' => "c892ba238c98835d4d53a3faed43ee52"
  );
 */

$call_center_db_host = '192.168.1.19';
$call_center_db_user = 'sa';
$call_center_db_pass = '!@#';
$call_center_db_name = 'NTTMOK';

$mps_db_host = "localhost:3306";
$mps_db_name = "mps";
$mps_db_user = "root";
$mps_db_pass = "123456";

// 订单中心domain
$kfc_domain = "http://192.168.1.167:8080/";
define('kfc_domain', $kfc_domain);

// table prefix
$prefix    = "ecs_";

$timezone    = "Asia/Shanghai";

$cookie_path    = "/";

$cookie_domain    = "";

$admin_dir = "admin";

$session = "1440000";

// {{{ 商品评论用
define('BBSAPI', $bbsapi);
define('COOKIE_DOMAIN', $COOKIE_DOMAIN);


//$romeo_webservice_url = "http://localhost:8080/romeo/InventoryService?wsdl";
$romeo_webservice_url = "http://testecs.leqeewechat.com/romeo/";//"http://localhost:8080/romeo/";
//$romeo_webservice_url = "http://172.16.1.158:8080/romeo/";

$erpsync_webservice_url = "http://testecs.leqeewechat.com/erpsync/";//"http://localhost:8080/erpsync/";


//$romeo_webservice_url = "http://172.16.1.2:8080/romeo_branch/"; // 公用linux版romeo
//$romeo_webservice_url = "http://172.16.1.2:8080/romeo_merge/"; // 公用linux版romeo

define('ROMEO_WEBSERVICE_URL', $romeo_webservice_url);
define('ROMEO_HTTP_USER', '');
define('ROMEO_HTTP_PASS', '');

// 报表服务
define('REPORT_WEBSERVICE_URL', 'http://localhost:8080/report/service/');
define('REPORT_HTTP_USER', '');
define('REPORT_HTTP_PASS', '');

//temp
define('ERPSYNC_WEBSERVICE_URL', $erpsync_webservice_url);
define('SYNCJUSHITA_WEBSERVICE_URL', $romeo_webservice_url);

// }}}


/**
 * 定义facility container的常量
 *
 */
define('FACILITY_OUKU_SH', '74539');
define('FACILITY_OUKU_DG', '1442078');
define('FACILITY_LEQEE_DG', '1442079');
define('FACILITY_LEQEE_SH', '1450562');

define('CONTAINER_OUKU_SH', '74741');
define('CONTAINER_OUKU_DG', '1442179');
define('CONTAINER_LEQEE_DG', '1442180');
define('CONTAINER_LEQEE_SH', '1450663');
define('CRM_WEBSERVICE_URL', 'http://localhost');

/**
 * 定义facilityId到名字的mapping
 */
$facility_mapping = array(
    FACILITY_OUKU_SH => '欧酷上海仓',
    FACILITY_OUKU_DG => '欧酷东莞仓',
    FACILITY_LEQEE_DG => '乐其东莞仓',
    FACILITY_LEQEE_SH => '乐其上海仓',
);

/**
 * party下的facility
 */
$party_facility = array(
    1 => array(FACILITY_OUKU_SH, FACILITY_OUKU_DG),
    4 => array(FACILITY_OUKU_SH), 
    8 => array(FACILITY_LEQEE_DG, FACILITY_LEQEE_SH), 
    16 => array(FACILITY_LEQEE_DG, FACILITY_LEQEE_SH),
);

/**
 * 默认的facility_id配置
 */
$default_facility_id = array(
    1 => FACILITY_OUKU_SH,
    4 => FACILITY_OUKU_SH, 
    8 => FACILITY_LEQEE_DG, 
    16 => FACILITY_LEQEE_DG,
);

/**
 * facility下的container
 * 目前facility和container是一对一的关系，如果出现一对多，需要用户选择
 */
$facility_container_mapping = array(
    FACILITY_OUKU_SH => CONTAINER_OUKU_SH,
    FACILITY_OUKU_DG => CONTAINER_OUKU_DG,
    FACILITY_LEQEE_DG => CONTAINER_LEQEE_DG,
    FACILITY_LEQEE_SH => CONTAINER_LEQEE_SH,
);

/**
 * 定义分销系统使用常量
 * 
 * @+{
 */

define('DIS_MOB_CAT_ID', '1508');  // 乐其手机分类ID
define('DIS_FIT_CAT_ID', '1509');  // 乐其配件分类ID
define('DIS_EDU_CAT_ID', '1512');  // 乐其电教分类ID
define('DIS_BOK_CAT_ID', '1517');  // 乐其电教教材分类ID

define('DIS_USER_ID', 1);  // 分销的订单都挂在此用户下面

/**
 * @-}
 */
define('JJSHOUSE_WEBSERVICE_URL','http://localcms.jjshouse.com/?q=syncServer');
define('JJSHOUSE_HTTP_USER', '');
define('JJSHOUSE_HTTP_PASS', '');

define('REPORT_URL', 'http://localerp.jjshouse.com:8080/report');

// 正式环境不要加这行定义
define('TEST_MAIL', 'xxxxx@i9i8.com');
?>
