<?php
/**
 * 淘宝店铺管理
 * @author ncchen
 */
define('IN_ECS', true);
require('../includes/init.php');
require_once(ROOT_PATH . "admin/function.php");
require_once(ROOT_PATH . "RomeoApi/lib_inventory.php");
require_once(ROOT_PATH . "includes/helper/array.php");

admin_priv('taobao_shop_conf');

extract($_POST);
 $taobao_shop_conf_page = false;
if ($_REQUEST['add_area']) {
    $taobao_shop_conf_page = true;   
};

$shop_type_list=array(
'taobao' => '淘宝',
'360buy' => '京东',
'360buy_overseas' => '京东境外',
'yhd' => '一号店',
'vipshop' => '唯品会',
'miya' => '蜜芽',
'suning' => '苏宁',
'weixin' => '微信',
'weigou' => '微购',
'ChinaMobile' => '积分商城',
'jumei' => '聚美',
'sfhk' => '顺丰优选',
'amazon' => '亚马逊',
'scn' => '名鞋库',
'weixinqs' => '惠氏微信',
'koudaitong' => '口袋通',
'weixinjf' => '微信人头马',
'baidumall' => '百度Mall',
'budweiser' => '百威礼物社交',
'pinduoduo' => '拼多多',
'cuntao' => '村淘',
'kaola' => '网易考拉',
'other' => '其他',
    );

$act = isset($_REQUEST['act']) ? trim($_REQUEST['act']) : false;
$taobao_shop_conf_id=$_REQUEST['taobao_shop_conf_id'];
$taobao_api_params_id=$_REQUEST['taobao_api_params_id'];
$application_key=$_REQUEST['application_key'];



// 判断是否有店铺需要修改
$old_shop = 0;
if($taobao_shop_conf_id==null||$taobao_shop_conf_id==''){
    $old_shop = 1;
};
    // 取得用户信息
    $user_id = intval($user_id);
    $user = $db->getRow("SELECT user_id, userId, user_name FROM ecs_users WHERE user_id = {$user_id} LIMIT 1");

    // 取得支付信息
    $pay_id = intval($pay_id);
    $payment = $db->getRow("SELECT pay_id, pay_name FROM ecs_payment WHERE pay_id = {$pay_id} LIMIT 1");


if ($act == 'update'){
  
    // 添加
    if (intval($taobao_shop_conf_id) == 0) {
        $sql = "INSERT INTO taobao_api_params (app_key, app_secret, session_id, is_sandbox) 
            VALUES ('{$app_key}', '{$app_secret}', '{$session_id}', '{$is_sandbox}') ";
        $db->query($sql);
        $taobao_api_params_id = $db->insert_id();
        $sql = "INSERT INTO taobao_shop_conf 
                (role, nick, shop_type, user_id, userId, user_name, pay_id, pay_name, application_key, 
                taobao_api_params_id, status, party_id, facility_id, distributor_id, shipping_id,
                type, is_erp_display, is_stock_update)
                VALUES ('{$role}', '{$nick}', '{$shop_type}','{$user['user_id']}', '{$user['userId']}', 
                '{$user['user_name']}', '{$payment['pay_id']}', '{$payment['pay_name']}', 
                '{$user['userId']}', '{$taobao_api_params_id}', '{$status}',
                '{$party_id}', '{$facility_id}', '{$distributor_id}', '{$shipping_id}', 
                '{$type}', '{$is_erp_display}', '{$is_stock_update}') ";
        $db->query($sql);
        $taobao_shop_conf_id = $db->insert_id();
        
         //Erp系统创建店铺同时聚石塔同步创建 by hchen1
//           if($shop_type == "taobao" && $taobao_shop_conf_id!=null && $taobao_shop_conf_id!='' && $app_key!=null &&
//           $app_secret!=null && $taobao_api_params_id!=null)
//           {
//           		$handle=soap_get_client('JushitaService','ERPTAOBAOSYNC');
//					$object=$handle->CreateTaobaoShopConf(array(
//				        'username'=>JSTUsername,
//                        'password'=>md5(JSTPassword),
//						'taobao_api_params_id'=>$taobao_api_params_id,
//						'app_key'=>$app_key,
//						'app_secret'=>$app_secret,
//						'session_id'=>$session_id,
//		                'is_sandbox'=>$is_sandbox,
//		                'taobao_shop_conf_id'=>$taobao_shop_conf_id,
//		                'role'=>$role,
//		                'nick'=>$nick,
//		                'shop_type'=>$shop_type,
//		                'user_id'=>$user['user_id'],
//		                'userid'=>$user['userId'],
//		                'user_name'=>$user_name,
//		                'pay_id'=>$pay_id,
//		                'pay_name'=>$payment['pay_name'],
//		                'application_key'=>$user['userId'],
//		                'status'=>$status,
//		                'party_id'=>$party_id,
//		                'facility_id'=>$facility_id,
//		                'distributor_id'=>$distributor_id,
//		                'shipping_id'=>$shipping_id,
//		                'type'=>$type,
//		                'is_erp_display'=>$is_erp_display,
//		                'is_stock_update'=>$is_stock_update,
//						
//					));
//           }
           
    } 
    else { 
       $sql = "SELECT c.*, p.*, f.taobao_fenxiao_shop_conf_id
                FROM ecshop.taobao_shop_conf c 
                INNER JOIN ecshop.taobao_api_params p ON c.taobao_api_params_id = p.taobao_api_params_id
                LEFT JOIN ecshop.taobao_fenxiao_shop_conf f ON c.application_key = f.application_key
                WHERE taobao_shop_conf_id='{$taobao_shop_conf_id}'";
        $taobao_shop_conf_display_list=$db->getRow($sql);

		

    }
    // 重置淘宝应用配置
    
    /**
     * 本地测试romeo不支持，暂时先注释掉
     */
    require_once(ROOT_PATH . 'RomeoApi/lib_soap.php');
    $taobao_soapclient = soap_get_client('TaobaoApplicationService');
    $taobao_soapclient->resetTaobaoShopConfList();
}


if($act=='save'){
      

        $sql = "UPDATE taobao_shop_conf 
                SET role = '{$role}', nick = '{$nick}', shop_type='{$shop_type}',user_id = '{$user['user_id']}', 
                    userId = '{$user['userId']}', user_name = '{$user['user_name']}',
                    pay_id = '{$payment['pay_id']}', pay_name = '{$payment['pay_name']}', 
                    status = '{$status}', party_id = '{$party_id}',
                    facility_id = '{$facility_id}', distributor_id = '{$distributor_id}', shipping_id = '{$shipping_id}',
                    type = '{$type}', is_erp_display = '{$is_erp_display}', is_stock_update = '{$is_stock_update}'
                    WHERE taobao_shop_conf_id = {$taobao_shop_conf_id} ";
        $db->query($sql);
        $sql = "UPDATE taobao_api_params 
                SET app_key = '{$app_key}', app_secret = '{$app_secret}', session_id = '{$session_id}', 
                    is_sandbox = '{$is_sandbox}' 
                WHERE taobao_api_params_id = {$taobao_api_params_id} ";
        $db->query($sql);
		//wlchen_begin
		
			global $erp_taobao_sync_soapclient;
			$erpsync_http_auth_array['trace'] = true;
			if(defined('ERPSYNC_HTTP_USER') && ERPSYNC_HTTP_USER) $erpsync_http_auth_array['login'] = ERPSYNC_HTTP_USER;
			if(defined('ERPSYNC_HTTP_PASS') && ERPSYNC_HTTP_PASS) $erpsync_http_auth_array['password'] = ERPSYNC_HTTP_PASS;
			$erp_taobao_sync_soapclient = new SoapClient(SYNCJUSHITA_WEBSERVICE_URL."JushitaService?wsdl", $erpsync_http_auth_array);
			$erp_taobao_sync_soapclient->UpdateSessionId(array('applicationKey'=>$application_key, 'sessionId'=>$session_id,'username'=>JSTUsername ,'password'=>md5(JSTPassword)  ));

			
			
		//wlchen_end
		
        // if( $GLOBALS['db']->query($sql) ){
        //     $message = "店铺信息修改成功！";
        // };
        
}
				
$sql = "SELECT c.*, p.*, f.taobao_fenxiao_shop_conf_id
		FROM ecshop.taobao_shop_conf c INNER JOIN ecshop.taobao_api_params p ON c.taobao_api_params_id = p.taobao_api_params_id
		LEFT JOIN ecshop.taobao_fenxiao_shop_conf f ON c.application_key = f.application_key 
       ";
$taobao_shop_conf_list = $db->getAll($sql);

// 分销店铺列表
$distributor_list = Helper_Array::toHashmap(
    (array)$slave_db->getAll("SELECT distributor_id, name FROM distributor WHERE status = 'NORMAL'"),
    'distributor_id',
    'name'
);

// 配送方式列表
$shipping_list=(array)$slave_db->getAll("select shipping_id, shipping_name from ecs_shipping where support_cod = 0");
$shipping_list = Helper_Array::toHashmap($shipping_list,'shipping_id','shipping_name');

// 支付方式列表
$payments = getPayments();
$payment_list = Helper_Array::toHashmap($payments, 'pay_id', 'pay_name');

// var_dump($payment_list);
$smarty->assign('is_sandbox_list', array('Y' => '测试', 'N' => '正式'));
$smarty->assign('is_erp_display_list', array('Y' => '是', 'N' => '否'));
$smarty->assign('is_stock_update_list', array('Y' => '是', 'N' => '否'));
$smarty->assign('status_list', array('OK' => '启用', 'DELETE' => '停用'));
$smarty->assign('party_id_list', party_list());
$smarty->assign('facility_id_list', facility_list());
$smarty->assign('distributor_list', $distributor_list);
$smarty->assign('role_list', array('seller' => '卖家', 'professional' => '专家',));
$smarty->assign('type_list', array('zhixiao' => '直销', 'fenxiao' => '分销',));
$smarty->assign('shipping_list', $shipping_list);
$smarty->assign('payment_list', $payment_list);
$smarty->assign('taobao_shop_conf_list', $taobao_shop_conf_list);
$smarty->assign( 'taobao_shop_conf_page',$taobao_shop_conf_page);
$smarty->assign( 'taobao_shop_conf_display_list',$taobao_shop_conf_display_list);
$smarty->assign( 'old_shop',$old_shop);
$smarty->assign( 'shop_type_list',$shop_type_list);
$smarty->assign( 'message',$message);




$smarty->display('taobao/taobao_shop_conf.htm');

?>