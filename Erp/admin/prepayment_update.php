<?php
 
/**
 * 编辑预付款
 * 
 * @author yxiang@leqee.com 
 */

define('IN_ECS', true);
require_once('includes/init.php');
admin_priv('cw_prepayment');
require_once('function.php');
require_once(ROOT_PATH . 'RomeoApi/lib_payment.php');
require_once(ROOT_PATH . 'includes/helper/array.php');
include_once(ROOT_PATH . 'RomeoApi/lib_currency.php');

$type =  // 类型，是供应商还是分销商
    isset($_REQUEST['type']) && trim($_REQUEST['type'])
    ? $_REQUEST['type']
    : 'DISTRIBUTOR';
    
$partner_id = 
    isset($_REQUEST['partner_id'])
    ? $_REQUEST['partner_id']
    : 0 ;


// 添加预付款
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST)) {
    do {
        $pay = $_POST['payment'];
		$is_rebate = (isset($pay['is_rebate']) && ($pay['is_rebate'] == 1)) ? $pay['is_rebate'] : 0;

        // 验证操作人员所属的组织
        if (!party_explicit($_SESSION['party_id'])) {
            $smarty->assign('message', '操作人员业务形态不确定');
            break;
        }
        
        // 账户类型
        if ($pay['prepayment_account_type_id']=='SUPPLIER') {
            if (!$pay['provider_id']) {
                $smarty->assign('message', '请选择账户类型');
                break;
            }
            $partner_id = $pay['provider_id'];
        }
        elseif ($pay['prepayment_account_type_id']=='DISTRIBUTOR') {
            if (!$pay['distributor_id']) {
                $smarty->assign('message', '请选择账户类型');
                break;
            }
            $partner_id = $pay['distributor_id'];
        }
        else {
            $smarty->assign('message', '请选择账户类型');
            break;
        }
        
        // 验证数据的金额
        $pay['amount'] = floatval($pay['amount']);
        if ($pay['amount'] == 0) {
            $smarty->assign('message', '输入的金额不对');
            break;    
        }
        
        // 验证付款时间
        if (!$pay['pay_time'] || !strtotime($pay['pay_time'])) {
            $pay['pay_time'] = date('Y-m-d H:i:s');   
        }
        
        $sql = "select mobile from ecshop.main_distributor where main_distributor_id = {$partner_id} limit 1";
        $mobile = $db->getOne($sql);
        
        // 添加预付款 (如果不存在供应商账户，同时会添加供应商账户)
        if ($pay['amount'] > 0) {
            // 验证付款方式
            if (!is_numeric($pay['payment_type_id'])) {
                $smarty->assign('message', '没有选择付款方式');
                break;                
            }
     		
            // 添加预付款
            $result = prepay_add(
                $partner_id,                        // 供应商
                $_SESSION['party_id'],              // 组织ID
                $pay['payment_type_id'],            // 支付方式
                $pay['min_amount'],                 // 最小金额
                $pay['amount'],                     // 预付金额
                $pay['pay_time'],                   // 付款时间
                $_SESSION['admin_name'],            // 交易创建人
                $pay['note'],                       // 备注
                $pay['prepayment_account_type_id'], 
                $is_rebate,                        //是否为返点
                $pay['currency']                   //币种
            );
            switch ($result) {
                case -1 :
                    $smarty->assign('message', '添加账户失败');
                    break;
                case 0:
                    $smarty->assign('message', '添加预付款失败');
                    break;
                case 2:
                    header('Location:prepayment.php?type='.$type.'&info='.urlencode('成功添加该账户，并添加预付款'));
                    exit;
                case -2:
                	$smarty->assign('message', '币种与供应商币种不同，添加账户失败');
                	break;
                default:
                    $sql = "select round(amount, 2) from romeo.prepayment_account where supplier_id = '{$partner_id}' limit 1 ";
                    $amount = $db->getOne($sql);
                    if ($_SESSION['party_id'] == "65558") {
                    	$party = "金佰利";
                    } else {
                    	$party = "乐其";
                    }
                    $content = "【{$party}】本次充值金额{$pay['amount']}，账户剩余金额合计：{$amount}";
                    // $send_result = sendMessage($mobile, $content, $_SESSION['party_id'], 'emay');
                    $send_result=sendMessageWithCRSMS($mobile,$content,$party);
                    header('Location:prepayment.php?type='.$type.'&info='.urlencode('成功添加预付款'));
                    exit;
            }
        }
        // 为负数,则消耗预付款
        else if ($pay['amount'] < 0) {
            $saving = prepay_get_available_amount($partner_id, $_SESSION['party_id'], $pay['prepayment_account_type_id']);
            if ($saving === false || $saving < abs($pay['amount'])) {
                $smarty->assign('message', '预付款账号不存在，或者余额不足');
                break;
            }
            
            // 消耗预付款
            $result = prepay_consume(
                $partner_id,                         // 供应商
                $_SESSION['party_id'],               // 用户组织
                $pay['prepayment_account_type_id'],  // 账户类型
                abs($pay['amount']),                 // 消耗金额
                NULL,                                // 付款批次
                $_SESSION['admin_name'],             // 交易创建人
                $pay['note'],                        // 备注
                $pay['prepayment_account_type_id'],
                $is_rebate                           // 是否为返点
            );
            
            switch ($result) {
                case -1 :
                    $smarty->assign('message', '该供应商没有预付款账号，不能使用预付款');
                    break;
                case 0 :
                    sys_msg('使用预付款失败', 1);
                    break;
                default :
                    header('Location:prepayment.php?type='.$type.'&info='.urlencode('消耗预付款金额'.abs($pay['amount'])));
                    exit;   
            }
        }

    } while (false);
}


$distributorSql="SELECT main_distributor_id, name FROM main_distributor as m where " . party_sql ( 'm.PARTY_ID' );;
// 分销商
$distributor_list = $db->getAll($distributorSql);
$distributor_list = Helper_Array::toHashmap((array)$distributor_list, 'main_distributor_id','name');

// 供应商
$provider_list = (array)Helper_Array::toHashmap((array)getProviders(), 'provider_id', 'provider_name');

$currency_list = get_currencys();
$currency = prepay_get_currency($partner_id, $_SESSION['party_id'], $type);

$smarty->assign('type', $type);  // 账户类型
$smarty->assign('partner_id', $partner_id);  // 供应商或分销商ID
$smarty->assign('payment_type_list', prepay_payment_type_list());  // 支付方式列表 
$smarty->assign('provider_list',     $provider_list);  // 供应商列表
$smarty->assign('distributor_list',  $distributor_list);   // 分销商列表

$smarty->assign('currency', $currency ? $currency['currency'] : '');
$smarty->assign('currency_list', $currency_list);
$smarty->display('oukooext/prepayment_update.htm');

function sendMessageWithCRSMS($mobile,$content,$party){
    require_once(ROOT_PATH.'admin/includes/lib_crsms.php');
    $done=send_message_with_crsms($content, $mobile, $party, $response);
    if ($done == 1) {
        return "短息发送成功";
    } else {
        return "短信发送失败,错误编码:{$response}";
    }
}

// function sendMessage($mobile, $content, $party_id, $server_name) {
// 	global $application_key, $db;
// 	require_once(ROOT_PATH.'admin/includes/cls_message2.php');
	
// 	//得到用户
// 	$sql = "select user_id from message.message_user where user_key = '{$application_key[$party_id]}' limit 1";
// 	$user_id = $db->getOne($sql);
// 	if (is_null($user_id)) $user_id = 1;
	
// 	if(! preg_match("/^1[0-9]{10}/", $mobile)) {
//         return "手机号码错误:{$mobile}, 发送短信失败";
//     } 
    
//     $mobile = is_array($mobile) ? $mobile : array($mobile);
	
// 	$message_url = trim(MESSAGE_URL);
// 	$message_serialnumber = trim(MESSAGE_SERIALNUMBER);
// 	$message_password = trim(MESSAGE_PASSWORD);
// 	$message_sessionkey = trim(MESSAGE_SESSIONKEY);
// 	$MessageClient = new Client($message_url,$message_serialnumber,$message_password,$message_sessionkey);
// 	$MessageClient->setOutgoingEncoding("UTF-8");
// 	$send_result = $MessageClient->sendSMS($mobile, $content);
	
// 	foreach ($mobile as $m) {
//         $sql ="insert into message.message_history
//                      (result, type, send_time, dest_mobile, user_id, content, server_name) 
//                 values 
//                      ({$send_result}, 'BATCH', now(), '{$m}', {$user_id}, '{$content}', '{$server_name}')   
//         ";
//         $db->query($sql);
//     }
	
// 	if ($send_result == 0) {
// 		return "短息发送成功";
// 	} else {
// 		return "短信发送失败,错误编码:{$send_result}";
// 	}

// }