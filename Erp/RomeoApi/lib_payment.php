<?php

/**
 * 支付相关服务接口
 * 
 * @author yxiang@oukoo.com
 * @copyright ouku.com
 */
 
require_once('lib_soap.php');


// 支付交易状态
define('PMT_TRANS_STTS_RECEIVED', 'RECEIVED');  // 接收
define('PMT_TRANS_STTS_USED',     'USED');      // 已使用


/**
 * 通过订单创建支付交易
 * 
 * @param mixed $order 订单信息或订单id
 * @param float $received_amount 已收到的金额
 * @param string $note 备注信息
 * @param string $account_from 付款来源，用户的32位id
 * @param int $pay_log_id 支付log (对应ecs_pay_log表)
 * @param string $created_by_user_login 创建人
 *  
 * @return 成功返回支付交易编号， 失败返回false
 */
function paytrans_create_by_order($order, $received_amount, $note, $account_from = null, $pay_log_id = null, $created_by_user_login = null)
{
    if (!function_exists('order_info') || !function_exists('user_info')) {
        require_once(ROOT_PATH .'includes/lib_order.php');            
    }
    
    if (is_numeric($order)) { 
        $order = order_info($order); 
    }
    else if (is_array($order) && isset($order['order_id']) && isset($order['pay_id'])) { 
        // 传入的$order包含了order_id, pay_id
    }
    else {
        $order = false;  
    }
	
    if (!$order) { return false; }  
	
    // 如果没有支付来源，则尝试获取
    if (is_null($account_from)) {
        $user = user_info($order['user_id']);
        $account_from = $user ? $user['userId'] : 'unknow' ;
    }
	
    // 如果没有指定支付创建人，则尝试使用管理员账号
    if (is_null($created_by_user_login))
        $created_by_user_login = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'unknow' ;
    	
    try {
        $handle = paytrans_get_soap_client();
        
        // 构造交易对象
        $trans = new stdClass();
        $trans->orderId            = $order['order_id'];
        $trans->payId              = $order['pay_id'];
        $trans->status             = PMT_TRANS_STTS_RECEIVED;
        $trans->note               = $note;
        $trans->receivedAmount     = $received_amount;  // 为收到的金额
        $trans->accountFrom        = $account_from;     // 为付款的来源（如果是用户，为用户的userId）
        $trans->accountTo          = 'OUKU';
        $trans->createdByUserLogin = $created_by_user_login;
        if (!is_null($pay_log_id))
            $trans->payLogId       = $pay_log_id;
        
        return $handle->createPaymentTransaction($trans); 
    }	
    catch (SoapFault $e) {
        trigger_error("SOAP创建支付交易失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
    }
	
    return false;
}

/**
 * 收款
 * 如果订单有未使用的支付交易，则尝试匹配， 如果匹配到有未使用的支付金额与收款金额一致，则使用；如果没有匹配到, 则返回错误。
 * 如果不存在支付交易，或存在的支付交易已全部被使用，则创建一条支付交易。
 * 支付交易使用成功后会更新订单的real_paid。
 * 
 * @param mixed $order 订单信息或ID， 如果传入的是订单数组，需要有order_id, pay_id, user_id, real_paid
 * @param float $received_amount 收款金额
 * @param string $note 备注
 * @param string $account_from 来源账户 
 * 
 * @return false|string 成功返回支付被使用的支付交易号
 */
function paytrans_receive($order, $received_amount, $note, $account_from = null, & $failed = null)
{    
    // 取得订单信息
    if (is_numeric($order)) { 
        $order = $GLOBALS['db']->getRow("SELECT order_id, pay_id, user_id, real_paid FROM {$GLOBALS['ecs']->table('order_info')} WHERE order_id = '{$order}'"); 
    }
    // 传入的$order没有order_id, pay_id信息
    else if (!is_array($order) || !isset($order['order_id']) || !isset($order['pay_id']) || !isset($order['real_paid'])) {
        $order = false;  
    }
    if (!$order) {
        $failed[] = "传入的订单信息不完整，需要有order_id, pay_id, real_paid 和 user_id";
        return false; 
    }  
    
    // 如果没有支付来源，则尝试获取
    if (is_null($account_from)) {
        $userId = $GLOBALS['db']->getRow("SELECT userId FROM {$GLOBALS['ecs']->table('users')} WHERE user_id = '{$order['user_id']}'");
        $account_from = $userId ? $userId: 'unknow' ;
    }
    
    // 查询是否有已存在的支付交易
    try {
        $handle = paytrans_get_soap_client();
        $response = $handle->getPaymentTransactionByOrderId($order['order_id']);
    } catch (SoapFault $e) {
        $failed[] = "SOAP查询该订单的支付交易失败: 订单号{$order['order_sn']}, (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
        return false;
    }

    $received_amount = (float)$received_amount;
    
    // 已存在支付交易，则尝试匹配
    if ($response->total > 0) {
        $used    = true;   // 已存在的支付交易是否已全部使用
        $matched = false;  // 未使用的支付交易是否有匹配的

        $list = array();
        if (is_object($response->resultList->PaymentTransaction))
            $list[0] = $response->resultList->PaymentTransaction;
        else if (is_array($response->resultList->PaymentTransaction))
            $list = $response->resultList->PaymentTransaction;

        // 未使用的支付金额
        $_pmt_trans_received = array();
        foreach ($list as $item) {
            if ($item->status == PMT_TRANS_STTS_RECEIVED) {  // 如果有支付交易未使用
                $used = false;
                $_pmt_trans_received[] = sprintf('%01.2f', $item->receivedAmount);

                if ((float)$item->receivedAmount === $received_amount) {
                    if (!$matched) {  // 有多比交易匹配时，只匹配一次
                        $matched = true;
                        $transId = $item->paymentTransactionId;
                    }
                }
            }
        }

        // 已存在没使用的支付交易但没有匹配金额的，并提示未使用的支付交易金额
        if (!$used && !$matched) {
            $failed[] = "没有一笔未使用的支付交易与要使用的金额（". sprintf('%01.2f', $received_amount) ."）相匹配，已存在未使用的支付金额分别为:". implode('， ', $_pmt_trans_received);
            return false;
        }
    }

    // 不存在支付交易, 或者存在支付交易但已全部使用，则创建一条支付交易
    if ($response->total == 0 || $used === true) {
        try {
            // 构造交易对象
            $trans = new stdClass();
            $trans->orderId            = $order['order_id'];
            $trans->payId              = $order['pay_id'];
            $trans->status             = PMT_TRANS_STTS_RECEIVED;
            $trans->note               = $note;
            $trans->receivedAmount     = $received_amount;  // 为收到的金额
            $trans->accountFrom        = $account_from;     // 为付款的来源（如果是用户，为用户的user_name）
            $trans->accountTo          = 'OUKU';
            $trans->createdByUserLogin = $_SESSION['admin_name'];

            $transId = $handle->createPaymentTransaction($trans);
        } catch (SoapFault $e) {
            $failed[] = "SOAP创建支付交易失败: 订单ID：{$order['order_id']}, (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
            return false;
        }
    }

    // 使用一笔支付交易
    if ($transId) {
        try {
            $amount = $handle->usePaymentTransaction($transId, $note);
            // 更新订单的实付金额
            $GLOBALS['db']->query("UPDATE {$GLOBALS['ecs']->table('order_info')} SET real_paid = real_paid + ". (float)$amount ." WHERE order_id = '{$order['order_id']}' LIMIT 1");
        } catch (SoapFault $e) {
            $failed[] = "SOAP使用交易支付失败: 订单ID：{$order['order_id']}, 支付交易ID: {$transId}, (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
            return false;
        }
    }
    
    return $transId;
}

/**
 * 通过查询条件获取支付交易列表
 * 
 * @param array $conditions 查询条件
 * 
 * @return array
 */
function paytrans_get_all_by_conditions($conditions)
{
    // 查询条件
    $cond = _paytrans_helper_conditions($conditions);
    $list = array();
 
    try {
        $handle = paytrans_get_soap_client();
        $response = $handle->getPaymentTransaction($cond);
        if (is_object($response->resultList->PaymentTransaction))
            $list[] = $response->resultList->PaymentTransaction;
        elseif (is_array($response->resultList->PaymentTransaction))
            $list = $response->resultList->PaymentTransaction;
    } catch (SoapFault $e) {
        trigger_error("SOAP查询支付交易列表失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
    }
	
    if (empty($list)) { return $list; }
	
    // 取得对应的订单详细信息, 和支付信息
    $oIds = $pIds = $uIds = array();
    foreach ($list as $item) {
        $oIds[] = $item->orderId;
        $pIds[] = $item->payId;
        $uIds[] = $item->accountFrom;
    }
    if (!empty($oIds)) {
        // 取得订单及订单的支付方式
        $sql = "
            SELECT o.order_id, o.order_sn, o.order_time, o.consignee, p.pay_code, p.pay_name
            FROM {$GLOBALS['ecs']->table('order_info')} AS o LEFT JOIN {$GLOBALS['ecs']->table('payment')} p ON p.pay_id = o.pay_id
            WHERE ". db_create_in($oIds, 'o.order_id') ."
        ";
        $ref_value = $ref_orders = array();
        $GLOBALS['db']->getAllRefby($sql, array('order_id'), $ref_value, $ref_orders, false);
        
        // 取得支付方式
        $sql = "SELECT p.pay_id, p.pay_code, p.pay_name FROM {$GLOBALS['ecs']->table('payment')} AS p WHERE ". db_create_in($pIds, 'p.pay_id');
        $ref_value2 = $ref_pmts = array();
        $GLOBALS['db']->getAllRefby($sql, array('pay_id'), $ref_value2, $ref_pmts, false);
         
        // 取得来源方对应的用户名
        $sql = "SELECT u.userId, u.user_name FROM {$GLOBALS['ecs']->table('users')} AS u WHERE ". db_create_in($uIds, 'u.userId');
        $ref_value3 = $ref_users = array();
        $GLOBALS['db']->getAllRefby($sql, array('userId'), $ref_value3, $ref_users, false);
        
        // 将订单信息和支付信息组装到列表
        foreach ($list as $k => $v) {
            $list[$k]->orderInfo = (object)$ref_orders['order_id'][$v->orderId][0];
            $list[$k]->payment = (object)$ref_pmts['pay_id'][$v->payId][0];
            $list[$k]->accountFromUserName = isset($ref_users['userId'][$v->accountFrom][0]) ?
                $ref_users['userId'][$v->accountFrom][0] : $v->accountFrom;
        }
    }

    return $list;	
}

/**
 * 通过查询条件取得记录数 
 *
 * @return int
 */
function paytrans_get_count_by_conditions($conditions)
{
    // 查询条件
    $cond = _paytrans_helper_conditions($conditions);
    $cond->offset = 0;
    $cond->limit  = 1;
    
    try {
        $handle = paytrans_get_soap_client();
        $response = $handle->getPaymentTransaction($cond);
        return is_numeric($response->total) ? $response->total : 0;
    } catch (SoapFault $e) {
        trigger_error("SOAP查询退款申请列表失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
    }
    return 0;	
}

/**
 * 返回支付交易状态列表
 * 
 * @return array
 */
function paytrans_status_list()
{
    return array
    (
        PMT_TRANS_STTS_RECEIVED => '收到',
        PMT_TRANS_STTS_USED     => '已使用', 
    );	
}

/**
 * 返回支付交易Soap服务句柄
 * 
 * @return object SoapClient
 */
function paytrans_get_soap_client()
{
    return soap_get_client('PaymentTransactionService', 'ROMEO', 'Soap_Client');
}

/**
 * 助手函数， 用来构造查询条件
 * 
 * @access private
 * 
 * @return object 
 */
function _paytrans_helper_conditions($conditions)
{
    $cond = new stdClass();
    $cond->paymentTransactionId  = is_numeric($conditions['payment_transaction_id']) ? $conditions['payment_transaction_id'] : NULL ;
    $cond->orderId               = is_numeric($conditions['order_id']) ? $conditions['order_id'] : NULL ;
    $cond->payId                 = is_numeric($conditions['pay_id']) ? $conditions['pay_id'] : NULL ;
    $cond->status                = isset($conditions['status']) ? $conditions['status'] : NULL ;
    $cond->accountFrom           = isset($conditions['account_from']) ? $conditions['account_from'] : NULL ;
    $cond->accountTo             = isset($conditions['account_to']) ? $conditions['account_to'] : NULL ;
    if (isset($conditions['start']) && strtotime($conditions['start']) !== false)
        $cond->createdStampBegin = $conditions['start'];
    if (isset($conditions['end']) && strtotime($conditions['end']) !== false) 
        $cond->createdStampEnd   = $conditions['end'];
    $cond->offset                = is_numeric($conditions['offset']) && ($conditions['offset'] >= 0) ? $conditions['offset'] : 0 ;
    $cond->limit                 = is_numeric($conditions['limit']) && ($conditions['limit'] > 0) ? $conditions['limit'] : 20 ;
    
    return $cond;
}

/**
 * 通过订单创建支付交易
 * 
 * @param array $order 订单
 * @param string $trans_id 订单支付交易id
 * @param string $note 备注
 *  
 * @return 成功返回支付交易编号， 失败返回false
 */
function paytrans_used_by_trans_id($order, $trans_id, $note) {
    // 使用一笔支付交易
    if ($trans_id) {
        try {
            $handle = paytrans_get_soap_client();
            $amount = $handle->usePaymentTransaction($trans_id, $note);
            // 更新订单 real_paid
            $real_paid = (float)$order['real_paid'] + (float)$amount;
            require_once(ROOT_PATH. "includes/lib_order.php");
            update_order($order['order_id'], array('real_paid' => $real_paid));
        } catch (SoapFault $e) {
            $message = "SOAP使用交易支付失败: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})";
            require_once(ROOT_PATH. "includes/lib_common.php");
            send_mail("bug", "bug@oukoo.com", "支付交易添加实收失败", $message);
        }
    }
}

/**
 * 预付款部分
 * 
 * @+{
 */

/**
 * 返回付款类型列表
 * 
 * @return array
 */
function prepay_payment_type_list()
{
    static $payment_type_list;
	
    if (!isset($payment_type_list)) {
        $payment_type_list = array();
        try {
            $handle = prepay_get_soap_client();
            $response = $handle->getAllPrepaymentPaymentType();
            if (isset($response->PrepaymentPaymentType)) {
                $list = wrap_object_to_array($response->PrepaymentPaymentType);
                foreach ($list as $item) {
                    $payment_type_list[$item->prepaymentPaymentTypeId] = $item;		
                }
            }
        } catch (SoapFault $e) {
        
        }
    }

    return $payment_type_list;
}

/**
 * 添加预付款, 如果供应商不存在则尝试创建，返回结果代码
 * 
 * @param int $supplier_id 供应商id
 * @param int $party_id 组织id
 * @param int $payment_type_id 付款类型
 * @param decimal $min_amount 最小金额
 * @param decimal $amount 金额
 * @param string $pay_time 付款时间
 * @param string $created_by_user_login 创建人
 * @param string $note 备注信息
 * @param
 * 
 * @return int -1:添加供应商失败  0:添加预付款失败  1:成功添加预付款  2:成功添加供应商和预付款
 */
function prepay_add($partner_id, $party_id, $payment_type_id, $min_amount, $amount, $pay_time, $created_by_user_login, $note = '', $account_type_id, $is_rebate=0, $currency='RMB')
{
    $code = 1;
    $handle = prepay_get_soap_client();

    // 查询该账号是否存在
    try {
        $account_id = $handle->getAccountIdByPartnerId($partner_id, $party_id, $account_type_id);
    } catch (SoapFault $e) {
    }

    // 如果账户不存在则创建账户
    if (!is_numeric($account_id)) {  //  不存在的时候会一个空对象, 所以要用这个判断
    
		if($account_type_id == 'SUPPLIER'){
			$sql = "select currency from ecshop.ecs_provider where provider_id = {$partner_id} limit 1";
	 		$provider_currency = $GLOBALS['db']->getOne($sql);
	 		
	 		if($provider_currency != $currency){
	 			return -2;
	 		}
		}
 		
        try {
            $account_id = $handle->createAccount($partner_id,$party_id,$account_type_id, $min_amount, $currency); 
            $code = 2;
        } catch (SoapFault $e) {
            return -1;
        }
    }
	
    // 添加预付款
    try {
    	return $handle->addPrepayment($account_id, $payment_type_id, $amount, $pay_time, $created_by_user_login, $note, $is_rebate, $currency);
    } catch (SoapFault $e) {
        return 0;
    }
    
    return $code;
}

/**
 * 返回供应商的可用金额
 * 
 * @param int $supplier_id 供应商id
 * @param int $party_id 组织id
 * 
 * @return float | false  
 */
function prepay_get_available_amount($supplier_id, $party_id, $account_type_id)
{
    $handle = prepay_get_soap_client();
	
    // 首先判断是否有预付款账号
    try {
        $exists = $handle->getAccountIdByPartnerId($supplier_id, $party_id, $account_type_id);
    } catch (SoapFault $e) {
        $exists = false;
    }
	
    if (!is_numeric($exists)) { return false; }

    // 取得可用金额
    try {
        $amount = $handle->getAvailablePrepayment($supplier_id, $party_id, $account_type_id);
    } catch (SoapFault $e) {
        $amount = 0;
    }
	
    return $amount;
}

//取得预付款账号币种
function prepay_get_currency($supplier_id, $party_id, $account_type_id){	

    $sql = "select currency " .
    		"from romeo.prepayment_account " .
    		"where supplier_id = ".$supplier_id.
			" and party_id = ".$party_id.
			" and PREPAYMENT_ACCOUNT_TYPE_ID = '".$account_type_id."'";
	$currency = $GLOBALS['db'] -> getRow($sql);
	
	return $currency;
}

/**
 * 消耗预付款，返回结果代码
 * 
 * @param int $supplier_id 供应商id 
 * @param int $party_id 组织id
 * @param decimal $amount 金额
 * @param int $purchase_bill_id 付款批次号
 * @param string $created_by_user_login 创建人
 * @param string $note 备注信息
 * @param string $cheque_no 支票号
 * 
 * @return int -1:账户不存在  0:使用预付款失败  1：使用预付款成功
 */
function prepay_consume($supplier_id, $party_id, $account_type_id, $amount, $purchase_bill_id, $created_by_user_login, $note = '', $cheque_no = '', $is_rebate=0, $currency='RMB')
{
    // 取得账户信息
    try {
        $handle = prepay_get_soap_client();
        $_account_id = $handle->getAccountIdByPartnerId($supplier_id, $party_id, $account_type_id);
    } catch (SoapFault $e) {
        return -1;
    }

    // 消耗预付款
    try {
        return $handle->consumePrepayment(
            $_account_id, $purchase_bill_id, $created_by_user_login, $amount, $note, $cheque_no, $is_rebate, $currency
        );
    } catch (SoapFault $e) {
        return 0;
    }
}

/**
 * 取得预付款账号总数
 * 
 * @param string $cond
 * @param int $party_id 组织id
 * 
 * @return int
 */
function prepay_get_account_count_by_conditions($account_type_id='DISTRIBUTOR', $cond, $party_id)
{
    try {
        $handle = prepay_get_soap_client();
        $response = $handle->getPrepaymentAccountByCondition($account_type_id, $cond, $party_id, 0, -1);
        return is_numeric($response->total) ? $response->total : 0;
    } catch (SoapFault $e) {
        trigger_error("SOAP错误: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
    }
    return 0;
}

/**
 * 按分页取得所有预付款账号列表
 * 
 * @param string $cond
 * @param int $party_id 组织id
 * @param int $offset
 * @param int $limit
 * 
 * @return int
 */
function prepay_get_all_account_by_conditions($account_type_id='DISTRIBUTOR', $cond, $party_id, $offset = 0, $limit = 20)
{
    global $db, $ecs;
	
    try {
        $handle = prepay_get_soap_client();
        $response = $handle->getPrepaymentAccountByCondition($account_type_id, $cond, $party_id, $offset, $limit);
        $list = wrap_object_to_array($response->result->PrepaymentAccount);
        if (!empty($list)) {
            // 取得账号对应的供应商 （账号与供应商一一对应）
            $supplierIds = array();
            $distributorIds = array();
            foreach ($list as $item) {
              	if ($item->prepaymentAccountTypeId=='SUPPLIER') {
                    $supplierIds[] = $item->supplierId;	
              	} else {
                    $distributorIds[] = $item->supplierId;	
              	}
            }
            
            if (!empty($supplierIds)) {
	            $sql = "
	                SELECT provider_id as id, provider_name as name FROM {$ecs->table('provider')} WHERE provider_id 
				" . db_create_in($supplierIds);
	            $ref_field = $ref = array();
	            $db->getAllRefby($sql, array('id'), $ref_field, $ref);
	            foreach ($list as $key => $item) {
	                if ($item->prepaymentAccountTypeId=='SUPPLIER' && isset($ref['id'][$item->supplierId])) {
	                    $list[$key]->partner = (object)reset($ref['id'][$item->supplierId]);
	                }
	            }
            }
            
            if (!empty($distributorIds)) {
                $sql = "
                    SELECT main_distributor_id as id, name FROM main_distributor WHERE main_distributor_id 
                " . db_create_in($distributorIds);
                $ref_field = $ref = array();
                $db->getAllRefby($sql, array('id'), $ref_field, $ref);
                foreach ($list as $key => $item) {
                    if ($item->prepaymentAccountTypeId=='DISTRIBUTOR' && isset($ref['id'][$item->supplierId])) {
                        $list[$key]->partner = (object)reset($ref['id'][$item->supplierId]);
                    }
                }
            }
        }
    } catch (SoapFault $e) {
        trigger_error("SOAP错误: (错误代码: {$e->faultcode}, 错误信息: {$e->faultstring})", E_USER_WARNING);
    }
    
    return $list;
}

/**
 * 查找预付款抵扣明细的总数
 * 
 * @param account_id 用户账号
 * @param bill_id    预付款批次号
 * @param status     状态
 * @param start      付款起始日期
 * @param end        付款结束日期
 * @param type       consume（消费）, add（增加）, all（所有）
 * 
 * @return  PrepaymentTransaction的列表
 */
function prepay_get_transaction_count_by_conditions($account_id, $bill_id = null, $status = null, 
    $start = null, $end = null, $type= 'all')
{
    try {
        $handle = prepay_get_soap_client();
    	$response = $handle->getPrepaymentTransaction($account_id, $bill_id, $status, $start, $end, $type, 0, 1);
    	$total = is_numeric($response->total) ? $response->total : 0 ;
    } catch (SoapFault $e) {
    	$total = 0 ;
    }
    
    return $total;
}

/**
 * 查找预付款抵扣明细,数据内容是原始数据
 * 
 * @param account_id 用户账号
 * @param bill_id    预付款批次号
 * @param status     状态
 * @param start      付款起始日期
 * @param end        付款结束日期
 * @param type       consume（消费）, add（增加）, all（所有）
 * 
 * @return  PrepaymentTransaction的列表
 */
function prepay_get_all_transaction_by_conditions($account_id, $bill_id = null, $status = null, 
    $start = null, $end = null, $type= 'all', $offset = 0, $limit = 20)
{
    try {
        $handle = prepay_get_soap_client();
    	$response = $handle->getPrepaymentTransaction($account_id, $bill_id, $status, $start, $end, $type, $offset, $limit);
    	$list = isset($response->result->PrepaymentTransaction) 
    		? wrap_object_to_array($response->result->PrepaymentTransaction) 
    		: array() ;
    } catch (SoapFault $e) {
    	$list = array();
    }
    
    return $list;
}

/**
 * 返回预付款Soap服务句柄
 * 
 * @return object SoapClient
 */
function prepay_get_soap_client()
{
    return soap_get_client('PrepaymentService', 'ROMEO', 'Soap_Client');
}

/**
 * 预付款部分
 * 
 * @-}
 */
